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
            // Перевіряємо чи є вже активне замовлення
            if (isset($_SESSION['current_order_id'])) {
                // Якщо замовлення вже є, просто перенаправляємо на форму створення кімнати
                echo json_encode([
                    'success' => true,
                    'order_id' => $_SESSION['current_order_id'],
                    'redirect_url' => '/BuildMaster/calculator/project-form'
                ]);
                return;
            }

            $this->db->beginTransaction();

            // Створюємо нове пусте замовлення зі статусом 'draft'
            $stmt = $this->db->prepare("
            INSERT INTO orders (guest_name, guest_email, guest_phone, status, total_amount, created_at, updated_at) 
            VALUES ('', '', '', 'draft', 0, NOW(), NOW())
        ");

            if (!$stmt->execute()) {
                throw new \Exception('Не вдалося створити замовлення');
            }

            $orderId = $this->db->lastInsertId();

            // Зберігаємо ID замовлення в сесії
            $_SESSION['current_order_id'] = $orderId;

            $this->db->commit();

            error_log("Created empty order for new room with ID: " . $orderId);

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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Метод не дозволений']);
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        try {
            $this->db->beginTransaction();

            // Створюємо порожнє замовлення зі статусом 'draft'
            $stmt = $this->db->prepare("
                INSERT INTO orders (guest_name, guest_email, guest_phone, status, total_amount, created_at, updated_at) 
                VALUES ('', '', '', 'draft', 0, NOW(), NOW())
            ");

            if (!$stmt->execute()) {
                throw new \Exception('Не вдалося створити замовлення');
            }

            $orderId = $this->db->lastInsertId();

            // Зберігаємо ID замовлення в сесії
            $_SESSION['current_order_id'] = $orderId;

            $this->db->commit();

            error_log("Created empty order with ID: " . $orderId);

            echo json_encode([
                'success' => true,
                'order_id' => $orderId,
                'redirect_url' => '/BuildMaster/calculator/order-rooms'
            ]);

        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Error creating empty order: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Помилка створення замовлення']);
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
            foreach ($input['selected_services'] as $serviceId) {
                // Отримуємо дані послуги
                $stmt = $this->db->prepare("
                    SELECT s.*, sb.area_type 
                    FROM services s 
                    JOIN service_blocks sb ON s.service_block_id = sb.id 
                    WHERE s.id = ?
                ");
                $stmt->execute([$serviceId]);
                $service = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$service) {
                    continue;
                }

                // Визначаємо кількість (площу) для послуги
                $quantity = $this->getQuantityByAreaType($service['area_type'], $room['wall_area'], $room['floor_area']);
                $totalPrice = $quantity * $service['price_per_sqm'];
                $totalRoomCost += $totalPrice;

                // Додаємо послугу до кімнати
                $stmt = $this->db->prepare("
                    INSERT INTO order_room_services (order_room_id, service_id, quantity, unit_price, total_price, is_selected, created_at) 
                    VALUES (?, ?, ?, ?, ?, 1, NOW())
                ");
                $stmt->execute([$roomId, $serviceId, $quantity, $service['price_per_sqm'], $totalPrice]);
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

            error_log("Room services updated successfully");

            echo json_encode([
                'success' => true,
                'room_cost' => $totalRoomCost,
                'redirect_url' => '/BuildMaster/calculator/order-rooms'
            ]);

        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Error updating room services: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Помилка оновлення послуг кімнати']);
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

        $orderId = $_SESSION['current_order_id'] ?? null;

        if (!$orderId) {
            // Якщо немає активного замовлення, перенаправляємо на головну
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

            // Отримуємо кімнати замовлення з послугами
            $stmt = $this->db->prepare("
                SELECT 
                    or_rooms.*,
                    rt.name as room_type_name,
                    COALESCE(SUM(ors.total_price), 0) as room_total_cost,
                    COUNT(ors.id) as services_count
                FROM order_rooms or_rooms
                LEFT JOIN room_types rt ON or_rooms.room_type_id = rt.id
                LEFT JOIN order_room_services ors ON or_rooms.id = ors.order_room_id AND ors.is_selected = 1
                WHERE or_rooms.order_id = ?
                GROUP BY or_rooms.id
                ORDER BY or_rooms.created_at
            ");
            $stmt->execute([$orderId]);
            $orderRooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Отримуємо детальні послуги для кожної кімнати
            foreach ($orderRooms as &$room) {
                $stmt = $this->db->prepare("
                    SELECT ors.*, s.name as service_name, s.description 
                    FROM order_room_services ors
                    JOIN services s ON ors.service_id = s.id
                    WHERE ors.order_room_id = ? AND ors.is_selected = 1
                    ORDER BY s.name
                ");
                $stmt->execute([$room['id']]);
                $room['services'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $totalAmount = $order['total_amount'];
            $roomTypes = $this->getRoomTypes();

            error_log("Showing order rooms - Order ID: {$orderId}, Rooms count: " . count($orderRooms) . ", Total: {$totalAmount}");

            // Передаємо дані у view
            $viewData = [
                'order' => $order,
                'orderRooms' => $orderRooms,
                'roomTypes' => $roomTypes,
                'totalAmount' => $totalAmount,
                'orderId' => $orderId
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

            // Видаляємо послуги кімнати
            $stmt = $this->db->prepare("DELETE FROM order_room_services WHERE order_room_id = ?");
            $stmt->execute([$roomId]);

            // Видаляємо кімнату
            $stmt = $this->db->prepare("DELETE FROM order_rooms WHERE id = ? AND order_id = ?");
            $stmt->execute([$roomId, $orderId]);

            // Оновлюємо загальну суму замовлення
            $this->updateOrderTotalAmount($orderId);

            $this->db->commit();

            echo json_encode(['success' => true]);

        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Error removing room: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Помилка видалення кімнати']);
        }
    }

    private function updateOrderTotalAmount($orderId)
    {
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

        error_log("Updated order total amount: " . $totalAmount);
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