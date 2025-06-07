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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        try {
            // Отримуємо дані кімнати
            $roomData = $this->getRoomData($roomId);

            if (!$roomData) {
                header('Location: /BuildMaster/calculator/order-rooms');
                exit;
            }

            // Перевіряємо права доступу
            if (!$this->hasAccessToRoom($roomId)) {
                header('Location: /BuildMaster/calculator/order-rooms');
                exit;
            }

            // Отримуємо вибрані послуги для кімнати
            $selectedServices = $this->getSelectedServices($roomId);

            // Передаємо дані в view
            include __DIR__ . '/../Views/room-edit.php';

        } catch (\Exception $e) {
            error_log("Error loading room edit page: " . $e->getMessage());
            header('Location: /BuildMaster/calculator/order-rooms');
            exit;
        }
    }

    /**
     * Отримує дані кімнати
     */
    private function getRoomData($roomId)
    {
        try {
            $stmt = $this->database->prepare("
                SELECT 
                    or.id,
                    or.room_name,
                    or.wall_area,
                    or.floor_area,
                    or.room_type_id,
                    rt.name as room_type_name,
                    o.id as order_id,
                    o.user_id
                FROM order_rooms or
                JOIN room_types rt ON or.room_type_id = rt.id
                JOIN orders o ON or.order_id = o.id
                WHERE or.id = ?
            ");
            $stmt->execute([$roomId]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error getting room data: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Перевіряє права доступу до кімнати
     */
    private function hasAccessToRoom($roomId)
    {
        try {
            $userId = $_SESSION['user']['id'] ?? null;
            $currentOrderId = $_SESSION['current_order_id'] ?? null;

            if (!$userId && !$currentOrderId) {
                return false;
            }

            $stmt = $this->database->prepare("
                SELECT COUNT(*) as count
                FROM order_rooms or
                JOIN orders o ON or.order_id = o.id
                WHERE or.id = ? 
                AND (o.user_id = ? OR o.id = ?)
            ");
            $stmt->execute([$roomId, $userId, $currentOrderId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            return $result['count'] > 0;
        } catch (\Exception $e) {
            error_log("Error checking room access: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Отримує вибрані послуги для кімнати
     */
    private function getSelectedServices($roomId)
    {
        try {
            $stmt = $this->database->prepare("
                SELECT 
                    ors.service_id,
                    ors.quantity,
                    ors.unit_price,
                    ors.total_price,
                    s.name as service_name,
                    s.price_per_sqm,
                    a.area_type
                FROM order_room_services ors
                JOIN services s ON ors.service_id = s.id
                LEFT JOIN service_area sa ON s.id = sa.service_id
                LEFT JOIN areas a ON sa.area_id = a.id
                WHERE ors.order_room_id = ? AND ors.is_selected = 1
            ");
            $stmt->execute([$roomId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error getting selected services: " . $e->getMessage());
            return [];
        }
    }

    /**
     * API метод для отримання даних кімнати в JSON форматі
     */
    public function getRoomDataJson($roomId)
    {
        header('Content-Type: application/json');

        try {
            if (!$this->hasAccessToRoom($roomId)) {
                http_response_code(403);
                echo json_encode(['error' => 'Доступ заборонено']);
                return;
            }

            $roomData = $this->getRoomData($roomId);
            if (!$roomData) {
                http_response_code(404);
                echo json_encode(['error' => 'Кімнату не знайдено']);
                return;
            }

            $selectedServices = $this->getSelectedServices($roomId);

            echo json_encode([
                'room' => $roomData,
                'selected_services' => $selectedServices
            ]);

        } catch (\Exception $e) {
            error_log("Error getting room data JSON: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Внутрішня помилка сервера']);
        }
    }

    /**
     * API метод для отримання послуг для редагування
     */
    public function getServicesForEdit($roomId)
    {
        header('Content-Type: application/json');

        try {
            if (!$this->hasAccessToRoom($roomId)) {
                http_response_code(403);
                echo json_encode(['error' => 'Доступ заборонено']);
                return;
            }

            $roomData = $this->getRoomData($roomId);
            if (!$roomData) {
                http_response_code(404);
                echo json_encode(['error' => 'Кімнату не знайдено']);
                return;
            }

            // Використовуємо метод з ServiceCalculatorController для отримання послуг
            $services = $this->serviceCalculatorController->getGroupedServicesByRoomType($roomData['room_type_id']);

            // Отримуємо вибрані послуги
            $selectedServices = $this->getSelectedServices($roomId);
            $selectedServiceIds = array_column($selectedServices, 'service_id');

            // Додаємо інформацію про вибрані послуги
            foreach ($services as &$area) {
                foreach ($area['service_blocks'] as &$block) {
                    foreach ($block['services'] as &$service) {
                        $service['is_selected'] = in_array($service['id'], $selectedServiceIds);

                        // Знаходимо дані вибраної послуги
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

            echo json_encode([
                'services' => $services,
                'room_data' => $roomData
            ]);

        } catch (\Exception $e) {
            error_log("Error getting services for edit: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Помилка отримання послуг']);
        }
    }

    /**
     * Оновлює кімнату та її послуги
     */
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

            // Оновлюємо загальну суму замовлення
            $roomData = $this->getRoomData($roomId);
            $this->serviceCalculatorController->updateOrderTotal($roomData['order_id']);

            $this->database->commit();

            echo json_encode([
                'success' => true,
                'room_id' => $roomId,
                'room_total' => $totalRoomAmount,
                'services_added' => $addedServicesCount,
                'message' => 'Кімнату успішно оновлено'
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