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

    // Додати цей метод в OrderController:

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
            // Отримуємо ID користувача якщо він залогінений
            $userId = $_SESSION['user']['id'] ?? null;

            // Перевіряємо чи є вже активне замовлення для цього користувача
            if ($userId) {
                // Шукаємо активне замовлення користувача
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
                // Для гостя перевіряємо сесію
                echo json_encode([
                    'success' => true,
                    'order_id' => $_SESSION['current_order_id'],
                    'redirect_url' => '/BuildMaster/calculator/project-form'
                ]);
                return;
            }

            $this->db->beginTransaction();

            // Створюємо нове замовлення з ID користувача якщо він залогінений
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
            // Отримуємо ID користувача якщо він залогінений
            $userId = $_SESSION['user']['id'] ?? null;

            // Перевіряємо чи є вже активне замовлення
            if ($userId) {
                // Для залогіненого користувача шукаємо активне замовлення
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
                // Для гостя перевіряємо сесію
                echo json_encode([
                    'success' => true,
                    'message' => 'Замовлення вже існує',
                    'order_id' => $_SESSION['current_order_id']
                ]);
                return;
            }

            // Створюємо нове замовлення з ID користувача
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

        // Якщо вже є активне замовлення в сесії, нічого не робимо
        if (isset($_SESSION['current_order_id'])) {
            return;
        }

        // Перевіряємо чи користувач залогінений
        $userId = $_SESSION['user']['id'] ?? null;

        if ($userId) {
            try {
                // Шукаємо останнє активне замовлення користувача
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
    public function updateRoomWithServices()
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
        $orderId = $_SESSION['current_order_id'] ?? null;
        $roomId = $_SESSION['current_room_id'] ?? null;

        error_log("=== UPDATE ROOM WITH SERVICES ===");
        error_log("Order ID: " . $orderId);
        error_log("Room ID: " . $roomId);
        error_log("Input: " . json_encode($input));

        if (!$orderId || !$roomId) {
            http_response_code(400);
            echo json_encode(['error' => 'Замовлення або кімната не знайдена']);
            return;
        }

        if (!isset($input['selected_services']) || empty($input['selected_services'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Оберіть хоча б одну послугу']);
            return;
        }

        try {
            $this->db->beginTransaction();

            // Отримуємо дані кімнати
            $stmt = $this->db->prepare("SELECT * FROM order_rooms WHERE id = ? AND order_id = ?");
            $stmt->execute([$roomId, $orderId]);
            $room = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$room) {
                throw new \Exception('Кімната не знайдена');
            }

            // Видаляємо старі послуги кімнати
            $stmt = $this->db->prepare("DELETE FROM order_room_services WHERE order_room_id = ?");
            $stmt->execute([$roomId]);

            $totalRoomCost = 0;

            // Додаємо нові послуги
            foreach ($input['selected_services'] as $serviceData) {
                $serviceId = $serviceData['id'];
                $areaType = $serviceData['area_type'];
                $pricePerSqm = floatval($serviceData['price_per_sqm']);

                error_log("Processing service: ID={$serviceId}, area_type={$areaType}, price={$pricePerSqm}");

                // Визначаємо кількість (площу) для послуги
                $quantity = $this->getQuantityByAreaType($areaType, $room['wall_area'], $room['floor_area']);
                $totalPrice = $quantity * $pricePerSqm;
                $totalRoomCost += $totalPrice;

                error_log("Service calculation: quantity={$quantity}, unit_price={$pricePerSqm}, total={$totalPrice}");

                // ВИПРАВЛЕНО: використовуємо правильні назви колонок
                $stmt = $this->db->prepare("
                INSERT INTO order_room_services (order_room_id, service_id, quantity, unit_price, is_selected, created_at) 
                VALUES (?, ?, ?, ?, 1, NOW())
            ");

                if (!$stmt->execute([$roomId, $serviceId, $quantity, $pricePerSqm])) {
                    error_log("Failed to insert service: " . implode(", ", $stmt->errorInfo()));
                    throw new \Exception('Помилка додавання послуги');
                }
            }

            // Оновлюємо загальну суму замовлення
            $this->updateOrderTotalAmount($orderId);

            $this->db->commit();

            // Очищуємо тимчасові дані
            unset($_SESSION['current_room_id']);
            unset($_SESSION['room_type_id']);
            unset($_SESSION['wall_area']);
            unset($_SESSION['room_area']);
            unset($_SESSION['selected_services']);

            error_log("Room services updated successfully. Room total: " . $totalRoomCost);

            echo json_encode([
                'success' => true,
                'room_cost' => $totalRoomCost,
                'redirect_url' => '/BuildMaster/calculator/order-rooms'
            ]);

        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Error updating room services: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Помилка оновлення послуг кімнати: ' . $e->getMessage()]);
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

        // Перевіряємо чи є кімнати в замовленні
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM order_rooms WHERE order_id = ?");
        $stmt->execute([$orderId]);
        $roomsCount = $stmt->fetchColumn();

        if ($roomsCount == 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Немає кімнат для завершення замовлення']);
            return;
        }

        // Валідуємо дані клієнта
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

            // Оновлюємо замовлення контактними даними та змінюємо статус
            $stmt = $this->db->prepare("
                UPDATE orders 
                SET guest_name = ?, guest_email = ?, guest_phone = ?, notes = ?, status = 'new', updated_at = NOW()
                WHERE id = ?
            ");

            if (!$stmt->execute([$guestName, $guestEmail, $guestPhone, $notes, $orderId])) {
                throw new \Exception('Не вдалося оновити замовлення');
            }

            $this->db->commit();

            // Очищуємо сесію
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

    // Методи для роботи з кімнатами
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

            // ВИПРАВЛЕННЯ: Ініціалізуємо масив послуг для кожної кімнати
            foreach ($orderRooms as $index => &$room) {
                // Обов'язково ініціалізуємо порожній масив послуг
                $room['services'] = [];
                $room['room_total_cost'] = 0;
                $room['services_count'] = 0;

                error_log("Processing room " . $room['id'] . " (" . $room['room_name'] . ")");

                // Отримуємо послуги ТІЛЬКИ для цієї конкретної кімнати
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

                // Присвоюємо послуги конкретно цій кімнаті
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
                $room['total_cost'] = $roomTotalCost; // для сумісності
                $room['room_area'] = floatval($room['floor_area']); // для сумісності

                error_log("Room " . $room['id'] . " final cost: " . $roomTotalCost);
            }

            // Очищуємо посилання
            unset($room);

            // Перерахунок загальної суми замовлення
            $totalAmount = $this->updateOrderTotalAmount($orderId);

            $roomTypes = $this->getRoomTypes();
            $selected_services = $_SESSION['selected_services'] ?? [];
            $room_area = $_SESSION['room_area'] ?? 0;

            error_log("Final order summary - Order ID: {$orderId}, Rooms count: " . count($orderRooms) . ", Total: {$totalAmount}");

            // Детальний лог для кожної кімнати
            foreach ($orderRooms as $room) {
                error_log("FINAL ROOM DATA - ID: " . $room['id'] . ", Name: " . $room['room_name'] . ", Services count: " . count($room['services']) . ", Cost: " . $room['room_total_cost']);
            }

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

            // Спочатку отримуємо суму кімнати для логування
            $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(total_price), 0) as room_total 
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
    private function updateOrderTotalAmount($orderId)
    {
        try {
            // ВИПРАВЛЕНО: використовуємо обчислювальну колонку total_price
            $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(ors.total_price), 0) as total
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
}