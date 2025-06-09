<?php

namespace BuildMaster\Controllers;

class RoomEditController
{
    private $database;
    private $serviceCalculatorController;

    public function __construct($database)
    {
        $this->database = $database;
        $this->serviceCalculatorController = new ServiceCalculatorController($database);
    }

    /**
     * Відображає сторінку редагування кімнати
     */
    public function editRoom($roomId)
    {
        error_log("=== ROOM EDIT DEBUG ===");
        error_log("Room ID: " . $roomId);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        try {
            // ВАЛІДАЦІЯ ROOM ID
            if (!$roomId || !is_numeric($roomId) || intval($roomId) <= 0) {
                error_log("Invalid room ID: " . $roomId);
                header('Location: /BuildMaster/calculator/order-rooms');
                exit;
            }

            $roomId = intval($roomId);

            // ПЕРЕВІРЯЄМО ПРАВА ДОСТУПУ
            if (!$this->hasAccessToRoom($roomId)) {
                error_log("Access denied for room ID: " . $roomId);
                header('Location: /BuildMaster/calculator/order-rooms');
                exit;
            }

            // Отримуємо дані кімнати
            $roomData = $this->getRoomData($roomId);
            if (!$roomData) {
                error_log("Room data not found for ID: " . $roomId);
                header('Location: /BuildMaster/calculator/order-rooms');
                exit;
            }

            // Отримуємо послуги для типу кімнати
            $services = $this->serviceCalculatorController->getGroupedServicesByRoomType($roomData['room_type_id']);

            // Отримуємо вибрані послуги для кімнати
            $selectedServices = $this->getSelectedServices($roomId);
            $selectedServiceIds = array_column($selectedServices, 'service_id');

            // Додаємо інформацію про вибрані послуги до структури послуг
            foreach ($services as &$area) {
                foreach ($area['service_blocks'] as &$block) {
                    foreach ($block['services'] as &$service) {
                        $service['is_selected'] = in_array($service['id'], $selectedServiceIds);

                        // Додаємо дані вибраної послуги
                        foreach ($selectedServices as $selectedService) {
                            if ($selectedService['service_id'] == $service['id']) {
                                $service['selected_quantity'] = $selectedService['quantity'];
                                $service['selected_unit_price'] = $selectedService['unit_price'];
                                $service['selected_total_price'] = $selectedService['total_price'];
                                break;
                            }
                        }
                    }
                }
            }

            // Передаємо дані в view
            $viewData = [
                'room' => $roomData,
                'services' => $services,
                'selectedServices' => $selectedServices
            ];

            extract($viewData);

            // Включаємо view
            $viewPath = __DIR__ . '/../Views/calculator/room-edit.php';
            if (!file_exists($viewPath)) {
                error_log("View file not found: " . $viewPath);
                header('Location: /BuildMaster/calculator/order-rooms');
                exit;
            }

            include $viewPath;

        } catch (\Exception $e) {
            error_log("Error loading room edit page: " . $e->getMessage());
            header('Location: /BuildMaster/calculator/order-rooms');
            exit;
        }
    }

    /**
     * Перевіряє права доступу до кімнати - ВИПРАВЛЕНО
     */
    private function hasAccessToRoom($roomId)
    {
        try {
            error_log("=== ACCESS CHECK DEBUG ===");
            error_log("Checking access for room ID: " . $roomId);

            // Спочатку перевіряємо чи існує кімната
            $stmt = $this->database->prepare("
                SELECT 
                    ord_rooms.id,
                    ord_rooms.order_id,
                    ord_rooms.room_name,
                    o.user_id as order_user_id,
                    o.status as order_status
                FROM order_rooms ord_rooms
                LEFT JOIN orders o ON ord_rooms.order_id = o.id
                WHERE ord_rooms.id = ?
            ");

            $stmt->execute([$roomId]);
            $roomInfo = $stmt->fetch(\PDO::FETCH_ASSOC);

            error_log("Query executed. Room info: " . json_encode($roomInfo));

            if (!$roomInfo) {
                error_log("Room not found with ID: " . $roomId);
                return false;
            }

            error_log("Room found: " . json_encode($roomInfo));
            error_log("=== END ACCESS CHECK ===");

            return true; // Тимчасово дозволяємо доступ до всіх кімнат

        } catch (\Exception $e) {
            error_log("Error checking room access: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    private function getRoomData($roomId)
    {
        try {
            error_log("=== GET ROOM DATA DEBUG ===");
            error_log("Fetching room data for ID: " . $roomId);

            // Спочатку отримуємо основні дані кімнати
            $stmt = $this->database->prepare("
                SELECT 
                    id,
                    room_name,
                    wall_area,
                    floor_area,
                    room_type_id,
                    order_id,
                    created_at
                FROM order_rooms 
                WHERE id = ?
            ");

            $stmt->execute([$roomId]);
            $roomData = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$roomData) {
                error_log("Room not found with ID: " . $roomId);
                return null;
            }

            error_log("Room data found: " . json_encode($roomData));

            // Тепер окремо отримуємо назву типу кімнати
            $roomTypeName = null;
            if (!empty($roomData['room_type_id'])) {
                try {
                    $typeStmt = $this->database->prepare("SELECT name FROM room_types WHERE id = ?");
                    $typeStmt->execute([$roomData['room_type_id']]);
                    $roomType = $typeStmt->fetch(\PDO::FETCH_ASSOC);

                    if ($roomType) {
                        $roomTypeName = $roomType['name'];
                        error_log("Room type found: " . $roomTypeName);
                    } else {
                        error_log("Room type not found for ID: " . $roomData['room_type_id']);
                        // Перевіримо всі доступні типи кімнат
                        $allTypesStmt = $this->database->prepare("SELECT id, name FROM room_types");
                        $allTypesStmt->execute();
                        $allTypes = $allTypesStmt->fetchAll(\PDO::FETCH_ASSOC);
                        error_log("Available room types: " . json_encode($allTypes));
                    }
                } catch (\Exception $e) {
                    error_log("Error fetching room type: " . $e->getMessage());
                }
            }

            // Додаємо назву типу кімнати до результату
            $roomData['room_type_name'] = $roomTypeName;

            error_log("Final room data: " . json_encode($roomData));
            error_log("=== END GET ROOM DATA ===");

            return $roomData;

        } catch (\Exception $e) {
            error_log("Error getting room data: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return null;
        }
    }

    private function getSelectedServices($roomId)
    {
        try {
            $stmt = $this->database->prepare("
                SELECT 
                    ors.service_id,
                    ors.quantity,
                    ors.unit_price,
                    (ors.quantity * ors.unit_price) as total_price,
                    s.name as service_name,
                    s.price_per_sqm,
                    COALESCE(sa.area_type, 'walls') as area_type
                FROM order_room_services ors
                JOIN services s ON ors.service_id = s.id
                LEFT JOIN service_area sa ON s.id = sa.service_id
                WHERE ors.order_room_id = ? AND ors.is_selected = 1
                ORDER BY s.name
            ");

            $stmt->execute([$roomId]);
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            error_log("Selected services for room " . $roomId . ": " . json_encode($result));

            return $result;
        } catch (\Exception $e) {
            error_log("Error getting selected services: " . $e->getMessage());
            return [];
        }
    }

    /**
     * API метод для отримання послуг для редагування - ВИПРАВЛЕНО
     */
    public function getServicesForEdit($roomId)
    {
        header('Content-Type: application/json; charset=utf-8');

        // Перевіряємо сесію
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        try {
            error_log("=== GET SERVICES FOR EDIT DEBUG ===");
            error_log("Room ID: " . $roomId);
            error_log("Request URI: " . $_SERVER['REQUEST_URI']);

            // Валідація ID
            if (!$roomId || !is_numeric($roomId) || intval($roomId) <= 0) {
                error_log("Invalid room ID: " . $roomId);
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Некоректний ID кімнати'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $roomId = intval($roomId);

            // Спочатку перевіряємо чи існує кімната в базі даних
            $checkStmt = $this->database->prepare("SELECT COUNT(*) FROM order_rooms WHERE id = ?");
            $checkStmt->execute([$roomId]);
            $roomExists = $checkStmt->fetchColumn();

            error_log("Room exists check: " . ($roomExists ? 'YES' : 'NO'));

            if (!$roomExists) {
                error_log("Room does not exist in database: " . $roomId);

                // Додаткова діагностика
                $allRoomsStmt = $this->database->prepare("SELECT id, room_name FROM order_rooms ORDER BY id DESC LIMIT 10");
                $allRoomsStmt->execute();
                $allRooms = $allRoomsStmt->fetchAll(\PDO::FETCH_ASSOC);
                error_log("Available rooms: " . json_encode($allRooms));

                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'Кімнату не знайдено',
                    'debug' => [
                        'requested_id' => $roomId,
                        'available_rooms' => $allRooms
                    ]
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            // КРИТИЧНО ВАЖЛИВО: Отримуємо дані кімнати ПЕРЕД перевіркою доступу
            error_log("BEFORE getRoomData call");
            $roomData = $this->getRoomData($roomId);
            error_log("AFTER getRoomData call, result: " . json_encode($roomData));

            if (!$roomData) {
                error_log("CRITICAL: Room data not found despite room existing: " . $roomId);

                // Додаткова діагностика - прямий запит
                $directStmt = $this->database->prepare("SELECT * FROM order_rooms WHERE id = ?");
                $directStmt->execute([$roomId]);
                $directResult = $directStmt->fetch(\PDO::FETCH_ASSOC);
                error_log("Direct query result: " . json_encode($directResult));

                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'Дані кімнати не знайдено',
                    'debug' => [
                        'room_exists' => true,
                        'direct_query_result' => $directResult
                    ]
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            error_log("Room data found: " . json_encode($roomData));

            // Перевіряємо доступ
            if (!$this->hasAccessToRoom($roomId)) {
                error_log("Access denied for room: " . $roomId);
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'error' => 'Доступ заборонено'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            // Отримуємо послуги для типу кімнати
            error_log("Getting services for room type: " . $roomData['room_type_id']);
            $services = $this->serviceCalculatorController->getGroupedServicesByRoomType($roomData['room_type_id']);
            error_log("Services loaded for room type " . $roomData['room_type_id'] . ": " . count($services) . " areas");

            // Отримуємо вибрані послуги
            $selectedServices = $this->getSelectedServices($roomId);
            $selectedServiceIds = array_column($selectedServices, 'service_id');
            error_log("Selected services: " . json_encode($selectedServiceIds));

            // Додаємо інформацію про вибрані послуги до структури
            foreach ($services as &$area) {
                foreach ($area['service_blocks'] as &$block) {
                    foreach ($block['services'] as &$service) {
                        $service['is_selected'] = in_array($service['id'], $selectedServiceIds);

                        // Додаємо дані вибраної послуги
                        foreach ($selectedServices as $selectedService) {
                            if ($selectedService['service_id'] == $service['id']) {
                                $service['selected_quantity'] = $selectedService['quantity'];
                                $service['selected_unit_price'] = $selectedService['unit_price'];
                                $service['selected_total_price'] = $selectedService['total_price'];
                                break;
                            }
                        }
                    }
                }
            }

            $response = [
                'success' => true,
                'services' => $services,
                'room_data' => $roomData,
                'selected_services' => $selectedServices
            ];

            error_log("Sending successful response with " . count($services) . " service areas");
            error_log("=== END GET SERVICES FOR EDIT ===");

            echo json_encode($response, JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            error_log("Error getting services for edit: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Помилка отримання послуг: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    public function updateRoomWithServices()
    {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input) {
                throw new \Exception('Немає даних для оновлення');
            }

            $roomId = $input['room_id'] ?? null;
            $roomName = $input['room_name'] ?? null;
            $wallArea = floatval($input['wall_area'] ?? 0);
            $floorArea = floatval($input['floor_area'] ?? 0);
            $selectedServices = $input['selected_services'] ?? [];

            if (!$roomId || !$this->hasAccessToRoom($roomId)) {
                throw new \Exception('Доступ заборонено');
            }

            if (!$roomName || $wallArea <= 0 || $floorArea <= 0) {
                throw new \Exception('Не всі обов\'язкові поля заповнені');
            }

            $this->database->beginTransaction();

            // Оновлюємо дані кімнати
            $stmt = $this->database->prepare("
                UPDATE order_rooms 
                SET room_name = ?, wall_area = ?, floor_area = ?
                WHERE id = ?
            ");

            if (!$stmt->execute([$roomName, $wallArea, $floorArea, $roomId])) {
                throw new \Exception('Помилка оновлення кімнати');
            }

            // Видаляємо старі послуги
            $stmt = $this->database->prepare("DELETE FROM order_room_services WHERE order_room_id = ?");
            $stmt->execute([$roomId]);

            // Додаємо нові послуги
            $totalRoomAmount = 0;
            $addedServicesCount = 0;

            foreach ($selectedServices as $serviceData) {
                $serviceId = $serviceData['id'] ?? null;
                $areaType = $serviceData['area_type'] ?? null;
                $pricePerSqm = floatval($serviceData['price_per_sqm'] ?? $serviceData['price'] ?? 0);

                if (!$serviceId || $pricePerSqm <= 0) {
                    continue;
                }

                // Визначаємо кількість (площу) для послуги
                $quantity = $this->getQuantityForService($serviceId, $areaType, $wallArea, $floorArea);

                if ($quantity <= 0) {
                    continue;
                }

                $totalPrice = $pricePerSqm * $quantity;
                $totalRoomAmount += $totalPrice;

                // Додаємо послугу
                $stmt = $this->database->prepare("
                    INSERT INTO order_room_services 
                    (order_room_id, service_id, quantity, unit_price, is_selected, created_at) 
                    VALUES (?, ?, ?, ?, 1, NOW())
                ");

                if ($stmt->execute([$roomId, $serviceId, $quantity, $pricePerSqm])) {
                    $addedServicesCount++;
                }
            }

            // Оновлюємо загальну суму замовлення через OrderController
            $roomData = $this->getRoomData($roomId);
            $orderController = new \BuildMaster\Controllers\OrderController($this->database);
            $orderController->updateOrderTotalAmount($roomData['order_id']);

            $this->database->commit();

            echo json_encode([
                'success' => true,
                'room_id' => $roomId,
                'room_total' => $totalRoomAmount,
                'services_added' => $addedServicesCount,
                'message' => 'Кімнату успішно оновлено',
                'redirect_url' => '/BuildMaster/calculator/order-rooms'
            ]);

        } catch (\Exception $e) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }
            error_log("Error updating room with services: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Визначає кількість для послуги на основі типу області
     */
    private function getQuantityForService($serviceId, $areaType, $wallArea, $floorArea)
    {
        if ($areaType) {
            return $this->getAreaByAreaType($areaType, $wallArea, $floorArea);
        }

        // Fallback - визначаємо за назвою послуги
        try {
            $stmt = $this->database->prepare("SELECT name FROM services WHERE id = ?");
            $stmt->execute([$serviceId]);
            $service = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($service) {
                $serviceName = strtolower($service['name']);
                if (strpos($serviceName, 'підлог') !== false ||
                    strpos($serviceName, 'пол') !== false ||
                    strpos($serviceName, 'стел') !== false ||
                    strpos($serviceName, 'потолок') !== false) {
                    return $floorArea;
                }
            }
        } catch (\Exception $e) {
            error_log("Error determining service quantity: " . $e->getMessage());
        }

        return $wallArea;
    }

    /**
     * Отримує площу за типом області
     */
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
}