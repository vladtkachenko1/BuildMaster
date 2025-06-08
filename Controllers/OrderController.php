<?php

namespace BuildMaster\Controllers;

use \PDO;

class OrderController
{
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    // Виправлення методу updateOrderTotalAmount
    public  function updateOrderTotalAmount($orderId)
    {
        try {
            // ВИПРАВЛЕНО: обчислюємо загальну суму через quantity * unit_price
            $stmt = $this->db->prepare("
                SELECT COALESCE(SUM(ors.quantity * ors.unit_price), 0) as total
                FROM order_rooms or_rooms
                JOIN order_room_services ors ON or_rooms.id = ors.order_room_id
                WHERE or_rooms.order_id = ? AND ors.is_selected = 1
            ");
            $stmt->execute([$orderId]);
            $totalAmount = $stmt->fetchColumn();

            $stmt = $this->db->prepare("UPDATE orders SET total_amount = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$totalAmount, $orderId]);

            error_log("Updated order total amount: " . $totalAmount . " for order ID: " . $orderId);

            return $totalAmount;
        } catch (\Exception $e) {
            error_log("Error updating order total: " . $e->getMessage());
            throw $e;
        }
    }

    // Виправлення методу showOrderRooms
    public function showOrderRooms()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->restoreActiveOrder();

        $orderId = $_SESSION['current_order_id'] ?? null;

        if (!$orderId) {
            header('Location: /BuildMaster/calculator');
            exit;
        }

        try {
            // Отримуємо замовлення
            $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = ?");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                header('Location: /BuildMaster/calculator');
                exit;
            }

            // Отримуємо всі кімнати замовлення
            $stmt = $this->db->prepare("
                SELECT 
                    or_rooms.id,
                    or_rooms.order_id,
                    or_rooms.room_type_id,
                    or_rooms.room_name,
                    or_rooms.wall_area,
                    or_rooms.floor_area,
                    or_rooms.created_at,
                    rt.name as room_type_name
                FROM order_rooms or_rooms
                LEFT JOIN room_types rt ON or_rooms.room_type_id = rt.id
                WHERE or_rooms.order_id = ?
                ORDER BY or_rooms.created_at
            ");
            $stmt->execute([$orderId]);
            $orderRooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

            error_log("Found " . count($orderRooms) . " rooms for order " . $orderId);

            // Ініціалізуємо масив послуг для кожної кімнати
            foreach ($orderRooms as $index => &$room) {
                $room['services'] = [];
                $room['room_total_cost'] = 0;
                $room['services_count'] = 0;

                error_log("Processing room " . $room['id'] . " (" . $room['room_name'] . ")");

                // ВИПРАВЛЕНО: отримуємо послуги з правильним обчисленням суми
                $stmt = $this->db->prepare("
                    SELECT 
                        ors.id,
                        ors.order_room_id,
                        ors.service_id,
                        ors.quantity,
                        ors.unit_price,
                        (ors.quantity * ors.unit_price) as total_price,
                        s.name as service_name, 
                        s.description,
                        sb.name as block_name
                    FROM order_room_services ors
                    JOIN services s ON ors.service_id = s.id
                    JOIN service_blocks sb ON s.service_block_id = sb.id
                    WHERE ors.order_room_id = ? AND ors.is_selected = 1
                    ORDER BY sb.sort_order, s.name
                ");
                $stmt->execute([$room['id']]);
                $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $room['services'] = $services;

                error_log("Room " . $room['id'] . " has " . count($services) . " services");

                // Обчислюємо загальну вартість кімнати
                $roomTotalCost = 0;
                foreach ($services as $service) {
                    $serviceTotal = floatval($service['quantity']) * floatval($service['unit_price']);
                    $roomTotalCost += $serviceTotal;
                    error_log("  - Service: " . $service['service_name'] . ", Quantity: " . $service['quantity'] . ", Unit Price: " . $service['unit_price'] . ", Total: " . $serviceTotal);
                }

                $room['room_total_cost'] = $roomTotalCost;
                $room['services_count'] = count($services);
                $room['total_cost'] = $roomTotalCost;
                $room['room_area'] = floatval($room['floor_area']);

                error_log("Room " . $room['id'] . " final cost: " . $roomTotalCost);
            }

            unset($room);

            // Перерахунок загальної суми замовлення
            $totalAmount = $this->updateOrderTotalAmount($orderId);

            $roomTypes = $this->getRoomTypes();
            $selected_services = $_SESSION['selected_services'] ?? [];
            $room_area = $_SESSION['room_area'] ?? 0;

            error_log("Final order summary - Order ID: {$orderId}, Rooms count: " . count($orderRooms) . ", Total: {$totalAmount}");

            $viewData = [
                'order' => $order,
                'orderRooms' => $orderRooms,
                'roomTypes' => $roomTypes,
                'totalAmount' => $totalAmount,
                'orderId' => $orderId,
                'selected_services' => $selected_services,
                'room_area' => $room_area
            ];

            extract($viewData);
            include 'views/calculator/order-rooms.php';

        } catch (\Exception $e) {
            error_log("Error in showOrderRooms: " . $e->getMessage());
            header('Location: /BuildMaster/calculator');
            exit;
        }
    }

    // Виправлення методу removeRoom
    public function removeRoom()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Метод не дозволений']);
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $roomId = $input['room_id'] ?? null;
        $orderId = $_SESSION['current_order_id'] ?? null;

        if (!$roomId || !$orderId) {
            http_response_code(400);
            echo json_encode(['error' => 'Некоректні дані']);
            return;
        }

        try {
            $this->db->beginTransaction();

            // ВИПРАВЛЕНО: правильно обчислюємо суму кімнати
            $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(quantity * unit_price), 0) as room_total 
            FROM order_room_services 
            WHERE order_room_id = ? AND is_selected = 1
        ");
            $stmt->execute([$roomId]);
            $roomTotal = $stmt->fetchColumn();

            // Видаляємо послуги кімнати
            $stmt = $this->db->prepare("DELETE FROM order_room_services WHERE order_room_id = ?");
            $stmt->execute([$roomId]);

            // Видаляємо кімнату
            $stmt = $this->db->prepare("DELETE FROM order_rooms WHERE id = ? AND order_id = ?");
            $stmt->execute([$roomId, $orderId]);

            // Оновлюємо загальну суму замовлення
            $this->updateOrderTotalAmount($orderId);

            $this->db->commit();

            error_log("Removed room {$roomId} with total cost {$roomTotal} from order {$orderId}");

            echo json_encode([
                'success' => true,
                'removed_amount' => $roomTotal
            ]);

        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Error removing room: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Помилка видалення кімнати']);
        }
    }
    public function createOrderForNewRoom()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Метод не дозволений']);
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        try {
            $userId = $_SESSION['user']['id'] ?? null;

            if ($userId) {
                $stmt = $this->db->prepare("
                    SELECT id FROM orders 
                    WHERE user_id = ? AND status = 'draft' 
                    ORDER BY created_at DESC LIMIT 1
                ");
                $stmt->execute([$userId]);
                $existingOrder = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existingOrder) {
                    $_SESSION['current_order_id'] = $existingOrder['id'];
                    echo json_encode([
                        'success' => true,
                        'order_id' => $existingOrder['id'],
                        'redirect_url' => '/BuildMaster/calculator/project-form'
                    ]);
                    return;
                }
            } elseif (isset($_SESSION['current_order_id'])) {
                echo json_encode([
                    'success' => true,
                    'order_id' => $_SESSION['current_order_id'],
                    'redirect_url' => '/BuildMaster/calculator/project-form'
                ]);
                return;
            }

            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                INSERT INTO orders (user_id, guest_name, guest_email, guest_phone, status, total_amount, created_at, updated_at) 
                VALUES (?, '', '', '', 'draft', 0, NOW(), NOW())
            ");

            if (!$stmt->execute([$userId])) {
                throw new \Exception('Не вдалося створити замовлення');
            }

            $orderId = $this->db->lastInsertId();
            $_SESSION['current_order_id'] = $orderId;

            $this->db->commit();

            error_log("Created empty order for new room with ID: " . $orderId . " for user: " . ($userId ?? 'guest'));

            echo json_encode([
                'success' => true,
                'order_id' => $orderId,
                'redirect_url' => '/BuildMaster/calculator/project-form'
            ]);

        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Error creating order for new room: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Помилка створення замовлення']);
        }
    }

    public function createEmptyOrder()
    {
        header('Content-Type: application/json');

        try {
            $userId = $_SESSION['user']['id'] ?? null;

            if ($userId) {
                $stmt = $this->db->prepare("
                    SELECT id FROM orders 
                    WHERE user_id = ? AND status = 'draft' 
                    ORDER BY created_at DESC LIMIT 1
                ");
                $stmt->execute([$userId]);
                $existingOrder = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existingOrder) {
                    $_SESSION['current_order_id'] = $existingOrder['id'];
                    echo json_encode([
                        'success' => true,
                        'message' => 'Замовлення вже існує',
                        'order_id' => $existingOrder['id']
                    ]);
                    return;
                }
            } elseif (isset($_SESSION['current_order_id'])) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Замовлення вже існує',
                    'order_id' => $_SESSION['current_order_id']
                ]);
                return;
            }

            $stmt = $this->db->prepare("
                INSERT INTO orders (user_id, guest_name, guest_email, guest_phone, status, total_amount, notes, admin_notes, created_at, updated_at) 
                VALUES (?, NULL, NULL, NULL, 'draft', 0.00, NULL, NULL, NOW(), NOW())
            ");
            $stmt->execute([$userId]);

            $orderId = $this->db->lastInsertId();
            $_SESSION['current_order_id'] = $orderId;

            echo json_encode([
                'success' => true,
                'message' => 'Пусте замовлення створено',
                'order_id' => $orderId
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Помилка створення замовлення: ' . $e->getMessage()
            ]);
        }
    }

    public function restoreActiveOrder()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['current_order_id'])) {
            return;
        }

        $userId = $_SESSION['user']['id'] ?? null;

        if ($userId) {
            try {
                $stmt = $this->db->prepare("
                    SELECT id FROM orders 
                    WHERE user_id = ? AND status = 'draft' 
                    ORDER BY updated_at DESC LIMIT 1
                ");
                $stmt->execute([$userId]);
                $activeOrder = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($activeOrder) {
                    $_SESSION['current_order_id'] = $activeOrder['id'];
                    error_log("Restored active order {$activeOrder['id']} for user {$userId}");
                }
            } catch (\Exception $e) {
                error_log("Error restoring active order: " . $e->getMessage());
            }
        }
    }
    public function completeOrder()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Метод не дозволений']);
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $orderId = $_SESSION['current_order_id'] ?? null;

        error_log("=== COMPLETE ORDER DEBUG ===");
        error_log("Order ID: " . $orderId);
        error_log("Input data: " . json_encode($input));

        if (!$orderId) {
            http_response_code(400);
            echo json_encode(['error' => 'Активне замовлення не знайдено']);
            return;
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM order_rooms WHERE order_id = ?");
        $stmt->execute([$orderId]);
        $roomsCount = $stmt->fetchColumn();

        if ($roomsCount == 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Немає кімнат для завершення замовлення']);
            return;
        }

        $guestName = trim($input['guest_name'] ?? '');
        $guestEmail = trim($input['guest_email'] ?? '');
        $guestPhone = trim($input['guest_phone'] ?? '');
        $notes = trim($input['notes'] ?? '');

        if (empty($guestName) || empty($guestEmail) || empty($guestPhone)) {
            http_response_code(400);
            echo json_encode(['error' => 'Заповніть всі обов\'язкові поля']);
            return;
        }

        if (!filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Некоректна email адреса']);
            return;
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                UPDATE orders 
                SET guest_name = ?, guest_email = ?, guest_phone = ?, notes = ?, status = 'new', updated_at = NOW()
                WHERE id = ?
            ");

            if (!$stmt->execute([$guestName, $guestEmail, $guestPhone, $notes, $orderId])) {
                throw new \Exception('Не вдалося оновити замовлення');
            }

            $this->db->commit();

            unset($_SESSION['current_order_id']);
            unset($_SESSION['current_room_id']);

            error_log("Order completed successfully with ID: " . $orderId);

            echo json_encode([
                'success' => true,
                'order_id' => $orderId,
                'redirect_url' => '/BuildMaster/calculator/order-success?order_id=' . $orderId
            ]);

        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Order completion error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Помилка завершення замовлення: ' . $e->getMessage()]);
        }
    }
// Виправлений метод editRoom в OrderController
    public function editRoom($roomId)
    {
        // Валідація roomId
        if (!$roomId || !is_numeric($roomId) || intval($roomId) <= 0) {
            header('Location: /BuildMaster/calculator/order-rooms');
            exit;
        }

        // Створюємо екземпляр RoomEditController і передаємо управління
        $roomEditController = new \BuildMaster\Controllers\RoomEditController($this->database);
        $roomEditController->editRoom(intval($roomId));
    }
    private function getSelectedServices($roomId)
    {
        try {
            $stmt = $this->db->prepare("
            SELECT 
                ors.service_id,
                ors.quantity,
                ors.unit_price,
                (ors.quantity * ors.unit_price) as total_price,
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

            $this->db->beginTransaction();

            // Оновлюємо дані кімнати
            $stmt = $this->db->prepare("
            UPDATE order_rooms 
            SET room_name = ?, wall_area = ?, floor_area = ?
            WHERE id = ?
        ");

            if (!$stmt->execute([$roomName, $wallArea, $floorArea, $roomId])) {
                throw new \Exception('Помилка оновлення кімнати');
            }

            // Видаляємо старі послуги
            $stmt = $this->db->prepare("DELETE FROM order_room_services WHERE order_room_id = ?");
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
                $stmt = $this->db->prepare("
                INSERT INTO order_room_services 
                (order_room_id, service_id, quantity, unit_price, is_selected, created_at) 
                VALUES (?, ?, ?, ?, 1, NOW())
            ");

                if ($stmt->execute([$roomId, $serviceId, $quantity, $pricePerSqm])) {
                    $addedServicesCount++;
                }
            }

            // Оновлюємо загальну суму замовлення
            $roomData = $this->getRoomForEdit($roomId);
            $this->updateOrderTotalAmount($roomData['order_id']);

            $this->db->commit();

            echo json_encode([
                'success' => true,
                'room_id' => $roomId,
                'room_total' => $totalRoomAmount,
                'services_added' => $addedServicesCount,
                'message' => 'Кімнату успішно оновлено',
                'redirect_url' => '/BuildMaster/calculator/order-rooms'
            ]);

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error updating room with services: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    private function getQuantityForService($serviceId, $areaType, $wallArea, $floorArea)
    {
        if ($areaType) {
            return $this->getAreaByAreaType($areaType, $wallArea, $floorArea);
        }

        // Fallback - визначаємо за назвою послуги
        try {
            $stmt = $this->db->prepare("SELECT name FROM services WHERE id = ?");
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
    // Допоміжні методи
    private function getRoomTypes()
    {
        try {
            $stmt = $this->db->query("SELECT * FROM room_types ORDER BY name");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("ERROR in getRoomTypes: " . $e->getMessage());
            return [];
        }
    }
    private function getRoomTypeById($id)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM room_types WHERE id = ?");
            $stmt->execute([(int)$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("ERROR in getRoomTypeById: " . $e->getMessage());
            return false;
        }
    }

    private function getQuantityByAreaType($areaType, $wallArea, $floorArea)
    {
        switch ($areaType) {
            case 'walls':
                return (float)$wallArea;
            case 'floor':
            case 'ceiling':
                return (float)$floorArea;
            default:
                error_log("Unknown area type: " . $areaType . ", defaulting to wall area");
                return (float)$wallArea;
        }
    }

    public function orderSuccess()
    {
        $orderId = $_GET['order_id'] ?? null;

        if (!$orderId) {
            header('Location: /BuildMaster/calculator');
            exit;
        }

        try {
            $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = ?");
            $stmt->execute([(int)$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                header('Location: /BuildMaster/calculator');
                exit;
            }

            include 'views/calculator/order-success.php';
        } catch (\Exception $e) {
            error_log("ERROR in orderSuccess: " . $e->getMessage());
            header('Location: /BuildMaster/calculator');
            exit;
        }
    }
    public function getRoomForEdit($roomId)
    {
        try {
            $stmt = $this->db->prepare("
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
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($result) {
                error_log("Room data retrieved successfully for ID: " . $roomId);
            } else {
                error_log("No room data found for ID: " . $roomId);
            }

            return $result;
        } catch (\Exception $e) {
            error_log("Error getting room data: " . $e->getMessage());
            return null;
        }
    }
    private function hasAccessToRoom($roomId)
    {
        try {
            $userId = $_SESSION['user']['id'] ?? null;
            $currentOrderId = $_SESSION['current_order_id'] ?? null;

            error_log("Checking access - User ID: " . ($userId ?? 'null') . ", Current Order ID: " . ($currentOrderId ?? 'null'));

            // Отримуємо інформацію про кімнату та замовлення
            $stmt = $this->db->prepare("
            SELECT 
                ord_room.id as room_id,
                ord_room.order_id,
                o.user_id as order_user_id,
                o.status as order_status
            FROM order_rooms ord_room
            JOIN orders o ON ord_room.order_id = o.id
            WHERE ord_room.id = ?
        ");
            $stmt->execute([$roomId]);
            $roomInfo = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$roomInfo) {
                error_log("Room not found: " . $roomId);
                return false;
            }

            // Перевіряємо доступ
            $hasAccess = false;

            // Якщо є користувач і він власник замовлення
            if ($userId && $roomInfo['order_user_id'] == $userId) {
                $hasAccess = true;
            }
            // Якщо є поточне замовлення і воно співпадає
            elseif ($currentOrderId && $roomInfo['order_id'] == $currentOrderId) {
                $hasAccess = true;
            }
            // Якщо замовлення має статус draft і немає користувача (гостьове замовлення)
            elseif ($roomInfo['order_status'] === 'draft' && is_null($roomInfo['order_user_id'])) {
                $hasAccess = true;
                // Встановлюємо поточне замовлення
                $_SESSION['current_order_id'] = $roomInfo['order_id'];
            }

            return $hasAccess;

        } catch (\Exception $e) {
            error_log("Error checking room access: " . $e->getMessage());
            return false;
        }
    }
}