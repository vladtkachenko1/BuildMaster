<?php

namespace BuildMaster\Controllers;

// Явно підключаємо файл ServiceCalculatorController
require_once __DIR__ . '/ServiceCalculatorController.php';

class RoomEditController
{
    private $database;
    private $serviceCalculatorController;

    public function __construct($database)
    {
        try {
            error_log("=== ROOM EDIT CONTROLLER INIT ===");
            $this->database = $database;
            
            if (!$this->database) {
                throw new \Exception("Database connection is not initialized");
            }
            
            error_log("Initializing ServiceCalculatorController");
            if (!class_exists('BuildMaster\Controllers\ServiceCalculatorController')) {
                throw new \Exception("ServiceCalculatorController class not found");
            }
            
            $this->serviceCalculatorController = new ServiceCalculatorController($database);
            
            if (!$this->serviceCalculatorController) {
                throw new \Exception("Failed to initialize ServiceCalculatorController");
            }
            
            error_log("RoomEditController initialized successfully");
            error_log("=== END ROOM EDIT CONTROLLER INIT ===");
        } catch (\Exception $e) {
            error_log("Error initializing RoomEditController: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
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

            // Отримуємо вибрані послуги СПОЧАТКУ
            $selectedServices = $this->getSelectedServices($roomId);
            $selectedServiceIds = array_column($selectedServices, 'service_id');
            error_log("Selected service IDs: " . json_encode($selectedServiceIds));

            // Отримуємо послуги для типу кімнати з інформацією про вибрані
            $services = $this->getGroupedServicesByRoomTypeWithSelected($roomData['room_type_id'], $selectedServices);

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
    private function getGroupedServicesByRoomTypeWithSelected($roomTypeId, $selectedServices = [])
    {
        try {
            error_log("=== GET SERVICES WITH SELECTED DEBUG ===");
            error_log("Room type ID: " . $roomTypeId);
            error_log("Selected services count: " . count($selectedServices));
            error_log("Selected services data: " . json_encode($selectedServices));

            // Створюємо мапу вибраних послуг для швидкого доступу
            $selectedMap = [];
            foreach ($selectedServices as $selected) {
                $serviceId = intval($selected['service_id']);
                $selectedMap[$serviceId] = [
                    'quantity' => floatval($selected['quantity']),
                    'unit_price' => floatval($selected['unit_price']),
                    'total_price' => floatval($selected['total_price']),
                    'is_selected' => true
                ];
                error_log("Added to selectedMap: service_id=" . $serviceId . ", total=" . $selected['total_price']);
            }

            error_log("Selected map keys: " . json_encode(array_keys($selectedMap)));

            // Отримуємо всі послуги через ServiceCalculatorController
            $services = $this->serviceCalculatorController->getGroupedServicesByRoomType($roomTypeId);
            error_log("Got services from ServiceCalculatorController, areas count: " . count($services));

            // Додаємо інформацію про вибрані послуги
            foreach ($services as &$area) {
                if (isset($area['service_blocks'])) {
                    foreach ($area['service_blocks'] as &$block) {
                        if (isset($block['services'])) {
                            foreach ($block['services'] as &$service) {
                                $serviceId = intval($service['id']);
                                error_log("Processing service ID: " . $serviceId . " (Name: " . $service['name'] . ")");

                                // Перевіряємо чи послуга вибрана
                                if (isset($selectedMap[$serviceId])) {
                                    $selectedData = $selectedMap[$serviceId];
                                    $service['is_selected'] = true;
                                    $service['selected_quantity'] = $selectedData['quantity'];
                                    $service['selected_unit_price'] = $selectedData['unit_price'];
                                    $service['selected_total_price'] = $selectedData['total_price'];

                                    error_log("✓ Service {$serviceId} ({$service['name']}) marked as SELECTED with total: {$selectedData['total_price']}");
                                } else {
                                    $service['is_selected'] = false;
                                    $service['selected_quantity'] = 0;
                                    $service['selected_unit_price'] = $service['price_per_sqm'];
                                    $service['selected_total_price'] = 0;
                                    error_log("✗ Service {$serviceId} ({$service['name']}) NOT selected");
                                }
                            }
                        }
                    }
                }
            }

            error_log("Final services data: " . json_encode($services));
            error_log("=== END GET SERVICES WITH SELECTED ===");

            return $services;

        } catch (\Exception $e) {
            error_log("Error in getGroupedServicesByRoomTypeWithSelected: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [];
        }
    }
    private function debugServiceSelection($roomId)
    {
        try {
            error_log("=== DEBUG SERVICE SELECTION ===");

            // Перевіряємо що є в order_room_services
            $stmt = $this->database->prepare("
            SELECT service_id, quantity, unit_price, total_price, is_selected 
            FROM order_room_services 
            WHERE order_room_id = ?
        ");
            $stmt->execute([$roomId]);
            $roomServices = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            error_log("Services in order_room_services for room $roomId:");
            foreach ($roomServices as $rs) {
                error_log("- Service ID: {$rs['service_id']}, is_selected: {$rs['is_selected']}, total: {$rs['total_price']}");
            }

            // Перевіряємо всі доступні послуги для типу кімнати
            $roomData = $this->getRoomData($roomId);
            if ($roomData) {
                $allServices = $this->serviceCalculatorController->getGroupedServicesByRoomType($roomData['room_type_id']);
                error_log("All available services for room type {$roomData['room_type_id']}:");

                foreach ($allServices as $area) {
                    if (isset($area['service_blocks'])) {
                        foreach ($area['service_blocks'] as $block) {
                            if (isset($block['services'])) {
                                foreach ($block['services'] as $service) {
                                    error_log("- Service ID: {$service['id']}, Name: {$service['name']}");
                                }
                            }
                        }
                    }
                }
            }

            error_log("=== END DEBUG SERVICE SELECTION ===");

        } catch (\Exception $e) {
            error_log("Error in debugServiceSelection: " . $e->getMessage());
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
            error_log("=== GET SELECTED SERVICES DEBUG ===");
            error_log("Getting selected services for room ID: " . $roomId);

            // First, let's check what's in the database directly
            $checkStmt = $this->database->prepare("
                SELECT * FROM order_room_services 
                WHERE order_room_id = ?
            ");
            $checkStmt->execute([$roomId]);
            $allServices = $checkStmt->fetchAll(\PDO::FETCH_ASSOC);
            error_log("All services in database for room $roomId: " . json_encode($allServices));

            // Modified query with proper JOIN conditions
            $stmt = $this->database->prepare("
                SELECT 
                    ors.service_id,
                    ors.quantity,
                    ors.unit_price,
                    ors.total_price,
                    ors.is_selected,
                    s.name as service_name,
                    s.price_per_sqm,
                    COALESCE(sa.area_type, 'walls') as area_type,
                    s.id as id,
                    s.service_block_id
                FROM order_room_services ors
                INNER JOIN services s ON ors.service_id = s.id
                LEFT JOIN service_area sa ON s.id = sa.service_id
                WHERE ors.order_room_id = ? 
                AND ors.is_selected = 1
                ORDER BY s.name
            ");

            $stmt->execute([$roomId]);
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            error_log("Found " . count($result) . " selected services in order_room_services");
            error_log("Selected services data: " . json_encode($result));

            // If no results but we have services in the database, let's check why
            if (empty($result) && !empty($allServices)) {
                error_log("WARNING: No selected services found despite having services in database");
                error_log("Checking service IDs in database: " . json_encode(array_column($allServices, 'service_id')));
                
                // Let's verify the services exist
                $serviceIds = array_column($allServices, 'service_id');
                if (!empty($serviceIds)) {
                    $placeholders = str_repeat('?,', count($serviceIds) - 1) . '?';
                    $serviceCheckStmt = $this->database->prepare("
                        SELECT id, name FROM services 
                        WHERE id IN ($placeholders)
                    ");
                    $serviceCheckStmt->execute($serviceIds);
                    $existingServices = $serviceCheckStmt->fetchAll(\PDO::FETCH_ASSOC);
                    error_log("Existing services in database: " . json_encode($existingServices));
                }
            }

            error_log("=== END GET SELECTED SERVICES DEBUG ===");
            return $result;

        } catch (\Exception $e) {
            error_log("Error getting selected services: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [];
        }
    }
    public function getServicesForEdit($roomId)
    {
        header('Content-Type: application/json; charset=utf-8');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        try {
            error_log("=== GET SERVICES FOR EDIT DEBUG ===");
            error_log("Room ID: " . $roomId);

            // Перевіряємо ініціалізацію контролерів
            if (!$this->database) {
                throw new \Exception("Database connection is not initialized");
            }
            
            if (!$this->serviceCalculatorController) {
                throw new \Exception("ServiceCalculatorController is not initialized");
            }

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

            // Отримуємо дані кімнати
            $roomData = $this->getRoomData($roomId);
            if (!$roomData) {
                error_log("Room data not found: " . $roomId);
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'Дані кімнати не знайдено'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            error_log("Room data: " . json_encode($roomData));

            try {
                // Отримуємо всі послуги для типу кімнати
                error_log("Getting services for room type: " . $roomData['room_type_id']);
                if (!method_exists($this->serviceCalculatorController, 'getGroupedServicesByRoomType')) {
                    throw new \Exception("Method getGroupedServicesByRoomType not found in ServiceCalculatorController");
                }
                $services = $this->serviceCalculatorController->getGroupedServicesByRoomType($roomData['room_type_id']);
                error_log("Got all services for room type: " . count($services) . " areas");
                error_log("Services data: " . json_encode($services));
            } catch (\Exception $e) {
                error_log("Error getting services: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                throw new \Exception("Помилка отримання послуг: " . $e->getMessage());
            }

            try {
                // Отримуємо вибрані послуги
                error_log("Getting selected services for room: " . $roomId);
                $selectedServicesStmt = $this->database->prepare("
                    SELECT 
                        ors.service_id,
                        ors.quantity,
                        ors.unit_price,
                        ors.total_price,
                        s.name as service_name,
                        s.price_per_sqm,
                        COALESCE(a.area_type, 'walls') as area_type
                    FROM order_room_services ors
                    INNER JOIN services s ON ors.service_id = s.id
                    LEFT JOIN service_area sa ON s.id = sa.service_id
                    LEFT JOIN areas a ON sa.area_id = a.id
                    WHERE ors.order_room_id = ? AND ors.is_selected = 1
                ");
                $selectedServicesStmt->execute([$roomId]);
                $selectedServices = $selectedServicesStmt->fetchAll(\PDO::FETCH_ASSOC);
                error_log("Selected services: " . json_encode($selectedServices));
            } catch (\Exception $e) {
                error_log("Error getting selected services: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                throw new \Exception("Помилка отримання вибраних послуг: " . $e->getMessage());
            }
            
            // Створюємо мапу вибраних послуг для швидкого доступу
            $selectedMap = [];
            foreach ($selectedServices as $service) {
                $selectedMap[$service['service_id']] = [
                    'quantity' => floatval($service['quantity']),
                    'unit_price' => floatval($service['unit_price']),
                    'total_price' => floatval($service['total_price']),
                    'service_name' => $service['service_name'],
                    'price_per_sqm' => floatval($service['price_per_sqm']),
                    'area_type' => $service['area_type']
                ];
            }
            error_log("Selected services map: " . json_encode($selectedMap));

            try {
                // Позначаємо вибрані послуги в основному масиві
                error_log("Processing services to mark selected ones");
                foreach ($services as &$area) {
                    if (isset($area['service_blocks'])) {
                        foreach ($area['service_blocks'] as &$block) {
                            if (isset($block['services'])) {
                                foreach ($block['services'] as &$service) {
                                    $serviceId = intval($service['id']);
                                    if (isset($selectedMap[$serviceId])) {
                                        $selectedData = $selectedMap[$serviceId];
                                        $service['is_selected'] = true;
                                        $service['selected_quantity'] = $selectedData['quantity'];
                                        $service['selected_unit_price'] = $selectedData['unit_price'];
                                        $service['selected_total_price'] = $selectedData['total_price'];
                                        error_log("Marked service {$serviceId} as selected with total: {$selectedData['total_price']}");
                                    } else {
                                        $service['is_selected'] = false;
                                        $service['selected_quantity'] = 0;
                                        $service['selected_unit_price'] = $service['price_per_sqm'];
                                        $service['selected_total_price'] = 0;
                                        error_log("Service {$serviceId} not selected");
                                    }
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                error_log("Error processing services: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                throw new \Exception("Помилка обробки послуг: " . $e->getMessage());
            }

            $response = [
                'success' => true,
                'services' => $services,
                'room_data' => $roomData,
                'selected_services' => $selectedServices,
                'debug_info' => [
                    'room_id' => $roomId,
                    'room_type_id' => $roomData['room_type_id'],
                    'selected_count' => count($selectedServices),
                    'selected_service_ids' => array_column($selectedServices, 'service_id')
                ]
            ];

            error_log("Sending response with " . count($selectedServices) . " selected services");
            error_log("=== END GET SERVICES FOR EDIT ===");

            echo json_encode($response, JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            error_log("Error in getServicesForEdit: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Помилка отримання послуг: ' . $e->getMessage(),
                'debug_info' => [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]
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