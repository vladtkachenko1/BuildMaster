<?php

namespace BuildMaster\Controllers;

class ServiceCalculatorController
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }


    public function getServiceBlocksByRoomType($roomTypeId)
    {
        try {
            $stmt = $this->database->prepare("
                SELECT id, name, slug, description, sort_order 
                FROM service_blocks 
                WHERE room_type_id = ? 
                ORDER BY sort_order ASC
            ");
            $stmt->execute([$roomTypeId]);
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            return $result;
        } catch (\Exception $e) {
            error_log("Error getting service blocks: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Отримує всі послуги для конкретного блоку послуг
     */
    public function getServicesByBlock($serviceBlockId)
    {
        try {
            $stmt = $this->database->prepare("
                SELECT id, name, slug, description, price_per_sqm 
                FROM services 
                WHERE service_block_id = ? 
                ORDER BY name ASC
            ");
            $stmt->execute([$serviceBlockId]);
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            return $result;
        } catch (\Exception $e) {
            error_log("Error getting services: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Отримує всі послуги згруповані по блокам для конкретного типу кімнати
     */
    public function getGroupedServicesByRoomType($roomTypeId)
    {
        try {
            $stmt = $this->database->prepare("
            SELECT 
                sb.id as block_id,
                sb.name as block_name,
                sb.slug as block_slug,
                sb.description as block_description,
                sb.sort_order as block_sort_order,
                s.id as service_id,
                s.name as service_name,
                s.slug as service_slug,
                s.description as service_description,
                s.price_per_sqm,
                a.id as area_id,
                a.name as area_name,
                a.slug as area_slug,
                a.area_type
            FROM service_blocks sb
            LEFT JOIN services s ON sb.id = s.service_block_id
            LEFT JOIN service_area sa ON s.id = sa.service_id
            LEFT JOIN areas a ON sa.area_id = a.id
            WHERE sb.room_type_id = ?
            ORDER BY a.id ASC, sb.sort_order ASC, s.name ASC
        ");
            $stmt->execute([$roomTypeId]);
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $groupedByArea = [];

            foreach ($result as $row) {
                $areaId = $row['area_id'];
                $blockId = $row['block_id'];

                // Пропускаємо рядки без area_id (послуги без прив'язки до областей)
                if (!$areaId) {
                    continue;
                }

                // Створюємо групу для області якщо її ще немає
                if (!isset($groupedByArea[$areaId])) {
                    $groupedByArea[$areaId] = [
                        'area_id' => $row['area_id'],
                        'area_name' => $row['area_name'],
                        'area_slug' => $row['area_slug'],
                        'area_type' => $row['area_type'],
                        'service_blocks' => []
                    ];
                }

                // Створюємо блок послуг якщо його ще немає в цій області
                if (!isset($groupedByArea[$areaId]['service_blocks'][$blockId])) {
                    $groupedByArea[$areaId]['service_blocks'][$blockId] = [
                        'id' => $row['block_id'],
                        'name' => $row['block_name'],
                        'slug' => $row['block_slug'],
                        'description' => $row['block_description'],
                        'sort_order' => $row['block_sort_order'],
                        'services' => []
                    ];
                }

                // Додаємо послугу якщо вона існує
                if ($row['service_id']) {
                    // Перевіряємо чи послуга вже додана (щоб уникнути дублікатів)
                    $serviceExists = false;
                    foreach ($groupedByArea[$areaId]['service_blocks'][$blockId]['services'] as $existingService) {
                        if ($existingService['id'] == $row['service_id']) {
                            $serviceExists = true;
                            break;
                        }
                    }

                    if (!$serviceExists) {
                        $groupedByArea[$areaId]['service_blocks'][$blockId]['services'][] = [
                            'id' => $row['service_id'],
                            'name' => $row['service_name'],
                            'slug' => $row['service_slug'],
                            'description' => $row['service_description'],
                            'price_per_sqm' => $row['price_per_sqm'],
                            'area_type' => $row['area_type'] // Додаємо тип області для розрахунку площі
                        ];
                    }
                }
            }

            // Конвертуємо асоціативні масиви в індексовані
            $result = [];
            foreach ($groupedByArea as $area) {
                $area['service_blocks'] = array_values($area['service_blocks']);
                $result[] = $area;
            }

            return $result;

        } catch (\Exception $e) {
            error_log("Error getting grouped services: " . $e->getMessage());
            return [];
        }
    }

    public function saveRoomWithServices()
    {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input) {
                throw new \Exception('Немає даних для збереження');
            }

            error_log("=== SAVE ROOM WITH SERVICES ===");
            error_log("Input data: " . json_encode($input));

            // Отримуємо дані з запиту
            $roomTypeId = $input['room_type_id'] ?? null;
            $wallArea = floatval($input['wall_area'] ?? 0);
            $floorArea = floatval($input['floor_area'] ?? 0);
            $roomName = $input['room_name'] ?? 'Нова кімната';
            $selectedServices = $input['selected_services'] ?? [];

            // Якщо немає даних в запиті, спробуємо взяти з сесії
            if (!$roomTypeId && isset($_SESSION['room_data'])) {
                $roomTypeId = $_SESSION['room_data']['room_type_id'];
                $wallArea = $_SESSION['room_data']['wall_area'];
                $floorArea = $_SESSION['room_data']['floor_area'];
            }

            // Валідація
            if (!$roomTypeId || $wallArea <= 0 || $floorArea <= 0 || empty($selectedServices)) {
                throw new \Exception('Не всі обов\'язкові поля заповнені');
            }

            $this->database->beginTransaction();

            // Отримуємо або створюємо замовлення для сесії
            $orderId = $this->getOrCreateDraftOrder();
            error_log("Order ID: {$orderId}");

            // Додаємо кімнату
            $stmt = $this->database->prepare("
            INSERT INTO order_rooms (order_id, room_type_id, wall_area, floor_area, room_name, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");

            if (!$stmt->execute([$orderId, $roomTypeId, $wallArea, $floorArea, $roomName])) {
                error_log("Failed to insert room: " . implode(", ", $stmt->errorInfo()));
                throw new \Exception('Помилка створення кімнати');
            }

            $roomId = $this->database->lastInsertId();
            error_log("Created room with ID: {$roomId}");

            // Додаємо послуги для кімнати
            $totalRoomAmount = 0;
            $addedServicesCount = 0;

            foreach ($selectedServices as $serviceData) {
                $serviceId = $serviceData['id'] ?? null;
                $areaType = $serviceData['area_type'] ?? null;
                $pricePerSqm = floatval($serviceData['price_per_sqm'] ?? $serviceData['price'] ?? 0);

                if (!$serviceId || $pricePerSqm <= 0) {
                    error_log("Invalid service data: " . json_encode($serviceData));
                    continue;
                }

                // Визначаємо площу залежно від типу послуги або area_type
                if ($areaType) {
                    $quantity = $this->getAreaByAreaType($areaType, $wallArea, $floorArea);
                } else {
                    // Fallback - визначаємо за назвою послуги
                    $stmt = $this->database->prepare("SELECT name FROM services WHERE id = ?");
                    $stmt->execute([$serviceId]);
                    $service = $stmt->fetch(\PDO::FETCH_ASSOC);

                    if ($service) {
                        $serviceName = strtolower($service['name']);
                        if (strpos($serviceName, 'підлог') !== false ||
                            strpos($serviceName, 'пол') !== false ||
                            strpos($serviceName, 'стел') !== false ||
                            strpos($serviceName, 'потолок') !== false) {
                            $quantity = $floorArea;
                        } else {
                            $quantity = $wallArea;
                        }
                    } else {
                        $quantity = $wallArea;
                    }
                }

                if ($quantity <= 0) {
                    error_log("Invalid quantity for service: {$serviceId}");
                    continue;
                }

                $totalPrice = $pricePerSqm * $quantity;
                $totalRoomAmount += $totalPrice;

                error_log("Service: ID={$serviceId}, quantity={$quantity}, price={$pricePerSqm}, total={$totalPrice}");

                // Додаємо послугу до order_room_services
                $stmt = $this->database->prepare("
                INSERT INTO order_room_services 
                (order_room_id, service_id, quantity, unit_price, is_selected, created_at) 
                VALUES (?, ?, ?, ?, 1, NOW())
            ");

                if ($stmt->execute([$roomId, $serviceId, $quantity, $pricePerSqm])) {
                    $addedServicesCount++;
                    error_log("Successfully added service {$serviceId} to room {$roomId}");
                } else {
                    error_log("Failed to insert service {$serviceId}: " . implode(", ", $stmt->errorInfo()));
                }
            }

            if ($addedServicesCount === 0) {
                throw new \Exception('Жодну послугу не було додано');
            }

            // Оновлюємо загальну суму замовлення
            $this->updateOrderTotal($orderId);

            $this->database->commit();

            // Очищуємо дані з сесії
            unset($_SESSION['room_data']);

            error_log("Room saved successfully. Room total: {$totalRoomAmount}, Services added: {$addedServicesCount}");

            echo json_encode([
                'success' => true,
                'order_id' => $orderId,
                'room_id' => $roomId,
                'room_total' => $totalRoomAmount,
                'services_added' => $addedServicesCount,
                'message' => 'Кімнату успішно додано до замовлення',
                'redirect_url' => '/BuildMaster/calculator/order-rooms'
            ]);

        } catch (\Exception $e) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }
            error_log("Error saving room with services: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    private function getAreaByAreaType($areaType, $wallArea, $floorArea)
    {
        switch ($areaType) {
            case 'walls':
                return (float)$wallArea;
            case 'floor':
            case 'ceiling':
                return (float)$floorArea;
            default:
                return (float)$wallArea;
        }
    }

    /**
     * Отримує або створює чернетку замовлення
     */
    private function getOrCreateDraftOrder()
    {
        // Спочатку перевіряємо, чи є активне замовлення в сесії
        if (isset($_SESSION['current_order_id'])) {
            $stmt = $this->database->prepare("SELECT id FROM orders WHERE id = ? AND status = 'draft'");
            $stmt->execute([$_SESSION['current_order_id']]);
            if ($stmt->fetch()) {
                return $_SESSION['current_order_id'];
            }
        }

        // Перевіряємо чи користувач залогінений
        $userId = $_SESSION['user']['id'] ?? null;

        if ($userId) {
            // Шукаємо існуюче draft замовлення користувача
            $stmt = $this->database->prepare("
            SELECT id FROM orders 
            WHERE user_id = ? AND status = 'draft' 
            ORDER BY created_at DESC LIMIT 1
        ");
            $stmt->execute([$userId]);
            $existingOrder = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($existingOrder) {
                $_SESSION['current_order_id'] = $existingOrder['id'];
                return $existingOrder['id'];
            }
        }

        // Створюємо нове замовлення
        $stmt = $this->database->prepare("
        INSERT INTO orders (user_id, status, total_amount, created_at, updated_at) 
        VALUES (?, 'draft', 0.00, NOW(), NOW())
    ");
        $stmt->execute([$userId]);
        $orderId = $this->database->lastInsertId();

        // Зберігаємо в сесії
        $_SESSION['current_order_id'] = $orderId;

        return $orderId;
    }
    /**
     * Оновлює загальну суму замовлення
     */
    private function updateOrderTotal($orderId)
    {
        try {
            // Використовуємо обчислювальне поле total_price з таблиці order_room_services
            $stmt = $this->database->prepare("
            SELECT COALESCE(SUM(ors.total_price), 0) as total
            FROM order_room_services ors
            JOIN order_rooms or_room ON ors.order_room_id = or_room.id
            WHERE or_room.order_id = ? AND ors.is_selected = 1
        ");
            $stmt->execute([$orderId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            $total = $result['total'] ?? 0;

            $stmt = $this->database->prepare("
            UPDATE orders 
            SET total_amount = ?, updated_at = NOW() 
            WHERE id = ?
        ");
            $stmt->execute([$total, $orderId]);

            error_log("Updated order total: {$total} for order ID: {$orderId}");

            return $total;
        } catch (\Exception $e) {
            error_log("Error updating order total: " . $e->getMessage());
            throw $e;
        }
    }    /**
     * Отримує поточні кімнати замовлення
     */
    public function getCurrentOrderRooms()
    {
        header('Content-Type: application/json');

        try {
            $orderId = $_SESSION['current_order_id'] ?? null;

            if (!$orderId) {
                echo json_encode(['rooms' => []]);
                return;
            }

            $stmt = $this->database->prepare("
            SELECT 
                ord_room.id,
                ord_room.room_name,
                ord_room.wall_area,
                ord_room.floor_area,
                rt.name as room_type_name,
                COALESCE(SUM(CASE WHEN ors.is_selected = 1 THEN ors.total_price ELSE 0 END), 0) as total_cost,
                COUNT(CASE WHEN ors.is_selected = 1 THEN ors.id END) as services_count
            FROM order_rooms ord_room
            LEFT JOIN room_types rt ON ord_room.room_type_id = rt.id
            LEFT JOIN order_room_services ors ON ord_room.id = ors.order_room_id 
            WHERE ord_room.order_id = ?
            GROUP BY ord_room.id, ord_room.room_name, ord_room.wall_area, ord_room.floor_area, rt.name
            ORDER BY ord_room.created_at DESC
        ");
            $stmt->execute([$orderId]);
            $rooms = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Додаємо детальну інформацію про послуги для кожної кімнати
            foreach ($rooms as &$room) {
                $stmt = $this->database->prepare("
                SELECT 
                    ors.id,
                    ors.quantity,
                    ors.unit_price,
                    ors.total_price,
                    s.name as service_name,
                    s.description as service_description,
                    sb.name as block_name
                FROM order_room_services ors
                JOIN services s ON ors.service_id = s.id
                JOIN service_blocks sb ON s.service_block_id = sb.id
                WHERE ors.order_room_id = ? AND ors.is_selected = 1
                ORDER BY sb.sort_order, s.name
            ");
                $stmt->execute([$room['id']]);
                $room['services'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }

            echo json_encode(['rooms' => $rooms]);

        } catch (\Exception $e) {
            error_log("Error getting current order rooms: " . $e->getMessage());
            echo json_encode(['error' => $e->getMessage()]);
        }
    }


    public function getRoomDetails($roomId)
    {
        header('Content-Type: application/json');

        try {
            // Отримуємо основну інформацію про кімнату
            $stmt = $this->database->prepare("
            SELECT 
                or.*,
                rt.name as room_type_name,
                COALESCE(SUM(CASE WHEN ors.is_selected = 1 THEN ors.total_price ELSE 0 END), 0) as room_total_cost
            FROM order_rooms or
            LEFT JOIN room_types rt ON or.room_type_id = rt.id
            LEFT JOIN order_room_services ors ON or.id = ors.order_room_id
            WHERE or.id = ?
            GROUP BY or.id
        ");
            $stmt->execute([$roomId]);
            $room = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$room) {
                throw new Exception('Кімнату не знайдено');
            }

            // Отримуємо послуги кімнати з детальною інформацією
            $stmt = $this->database->prepare("
            SELECT 
                ors.id,
                ors.quantity,
                ors.unit_price,
                ors.total_price,
                ors.is_selected,
                s.name as service_name,
                s.description as service_description,
                s.price_per_sqm,
                sb.name as block_name,
                sb.description as block_description,
                a.area_type,
                a.name as area_name
            FROM order_room_services ors
            JOIN services s ON ors.service_id = s.id
            JOIN service_blocks sb ON s.service_block_id = sb.id
            LEFT JOIN service_area sa ON s.id = sa.service_id
            LEFT JOIN areas a ON sa.area_id = a.id
            WHERE ors.order_room_id = ? AND ors.is_selected = 1
            ORDER BY sb.sort_order, s.name
        ");
            $stmt->execute([$roomId]);
            $services = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $room['services'] = $services;

            echo json_encode($room);

        } catch (Exception $e) {
            error_log("Error getting room details: " . $e->getMessage());
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    public function getAreaByType($roomId, $areaType)
    {
        try {
            $columnMap = [
                'floor' => 'floor_area',
                'walls' => 'wall_area',
                'ceiling' => 'ceiling_area'
            ];

            if (!isset($columnMap[$areaType])) {
                throw new \Exception("Unknown area type: " . $areaType);
            }

            $column = $columnMap[$areaType];

            $stmt = $this->database->prepare("
            SELECT {$column} as area 
            FROM rooms 
            WHERE id = ?
        ");
            $stmt->execute([$roomId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            return $result ? (float)$result['area'] : 0;

        } catch (\Exception $e) {
            error_log("Error getting area by type: " . $e->getMessage());
            return 0;
        }
    }
    /**
     * Розрахунок загальної вартості на основі вибраних послуг
     */
    public function calculateTotal($selectedServices, $wallArea, $roomArea)
    {
        $total = 0;

        foreach ($selectedServices as $serviceId) {
            try {
                $stmt = $this->database->prepare("
                    SELECT price_per_sqm 
                    FROM services 
                    WHERE id = ?
                ");
                $stmt->execute([$serviceId]);
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($result) {
                    // Тут можна додати логіку для різних типів розрахунків
                    // Наприклад, деякі послуги рахуються від площі стін, інші від площі кімнати
                    $total += $result['price_per_sqm'] * $wallArea;
                }
            } catch (\Exception $e) {
                error_log("Error calculating service cost: " . $e->getMessage());
            }
        }

        return $total;
    }

    /**
     * API метод для отримання послуг в JSON форматі
     */
    public function getServicesJson()
    {
        header('Content-Type: application/json');

        $roomTypeId = $_GET['room_type_id'] ?? null;

        if (!$roomTypeId) {
            http_response_code(400);
            echo json_encode(['error' => 'room_type_id is required']);
            return;
        }

        $services = $this->getGroupedServicesByRoomType($roomTypeId);
        echo json_encode($services);
    }

    /**
     * API метод для розрахунку вартості
     */
    public function calculateJson()
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);

        $selectedServices = $input['services'] ?? [];
        $wallArea = floatval($input['wall_area'] ?? 0);
        $roomArea = floatval($input['room_area'] ?? 0);

        if (empty($selectedServices) || $wallArea <= 0 || $roomArea <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input data']);
            return;
        }

        $total = $this->calculateTotal($selectedServices, $wallArea, $roomArea);

        echo json_encode([
            'total' => $total,
            'wall_area' => $wallArea,
            'room_area' => $roomArea,
            'services_count' => count($selectedServices)
        ]);
    }
}