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

    // Додавання кімнати до замовлення або оновлення існуючої
    public function addRoomToOrder()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Метод не дозволений']);
            return;
        }

        // Починаємо сесію якщо вона не розпочата
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // ДІАГНОСТИКА: Перевіряємо що приходить
        $input = json_decode(file_get_contents('php://input'), true);
        error_log("=== ADD ROOM TO ORDER DEBUG ===");
        error_log("Received input: " . json_encode($input));
        error_log("Current session data: " . json_encode($_SESSION));

        // Перевіряємо чи це редагування існуючої кімнати
        $editingRoomId = $_SESSION['editing_room_id'] ?? null;

        if ($editingRoomId) {
            error_log("Editing existing room: " . $editingRoomId);
            return $this->updateRoomFromServices();
        }

        // Валідація вхідних даних
        if (!$input) {
            error_log("ERROR: No input data received");
            http_response_code(400);
            echo json_encode(['error' => 'Дані не отримано']);
            return;
        }

        // Перевіряємо всі необхідні поля
        $requiredFields = ['room_type_id', 'wall_area', 'room_area', 'selected_services'];
        foreach ($requiredFields as $field) {
            if (!isset($input[$field])) {
                error_log("ERROR: Missing field: " . $field);
                http_response_code(400);
                echo json_encode(['error' => "Відсутнє поле: {$field}"]);
                return;
            }
        }

        // Отримуємо дані кімнати
        $roomTypeId = (int)$input['room_type_id'];
        $wallArea = (float)$input['wall_area'];
        $roomArea = (float)$input['room_area'];
        $selectedServices = $input['selected_services'];
        $roomName = $input['room_name'] ?? '';

        error_log("Processing room data: roomTypeId={$roomTypeId}, wallArea={$wallArea}, roomArea={$roomArea}");
        error_log("Selected services: " . json_encode($selectedServices));

        // Валідація значень
        if ($roomTypeId <= 0) {
            error_log("ERROR: Invalid room type ID: " . $roomTypeId);
            http_response_code(400);
            echo json_encode(['error' => 'Некоректний тип кімнати']);
            return;
        }

        if ($wallArea <= 0 || $roomArea <= 0) {
            error_log("ERROR: Invalid areas - wall: {$wallArea}, room: {$roomArea}");
            http_response_code(400);
            echo json_encode(['error' => 'Некоректні значення площі']);
            return;
        }

        if (empty($selectedServices) || !is_array($selectedServices)) {
            error_log("ERROR: No services selected");
            http_response_code(400);
            echo json_encode(['error' => 'Оберіть хоча б одну послугу']);
            return;
        }

        // Отримуємо інформацію про тип кімнати
        $roomType = $this->getRoomTypeById($roomTypeId);
        if (!$roomType) {
            error_log("ERROR: Room type not found: " . $roomTypeId);
            http_response_code(404);
            echo json_encode(['error' => 'Тип кімнати не знайдено']);
            return;
        }

        error_log("Room type found: " . json_encode($roomType));

        // Отримуємо детальну інформацію про послуги
        $servicesDetails = $this->getServicesDetails($selectedServices);

        if (empty($servicesDetails)) {
            error_log("ERROR: Services details not found for: " . json_encode($selectedServices));
            http_response_code(404);
            echo json_encode(['error' => 'Послуги не знайдено']);
            return;
        }

        error_log("Services details: " . json_encode($servicesDetails));

        // Підраховуємо вартість
        $totalCost = 0;
        foreach ($servicesDetails as $service) {
            $area = $this->getAreaByType($service['area_type'], $wallArea, $roomArea);
            $serviceCost = $service['price_per_sqm'] * $area;
            $totalCost += $serviceCost;

            error_log("Service {$service['name']}: area={$area}, price_per_sqm={$service['price_per_sqm']}, cost={$serviceCost}");
        }

        error_log("Total cost calculated: " . $totalCost);

        // Створюємо об'єкт кімнати
        $roomData = [
            'id' => uniqid('room_', true),
            'room_type_id' => $roomTypeId,
            'room_type_name' => $roomType['name'],
            'room_name' => $roomName ?: $roomType['name'],
            'wall_area' => $wallArea,
            'room_area' => $roomArea,
            'selected_services' => $servicesDetails,
            'total_cost' => $totalCost
        ];

        // Ініціалізуємо масив кімнат якщо його немає
        if (!isset($_SESSION['order_rooms'])) {
            $_SESSION['order_rooms'] = [];
        }

        $_SESSION['order_rooms'][] = $roomData;

        // Очищуємо тимчасові дані з сесії
        unset($_SESSION['room_type_id']);
        unset($_SESSION['wall_area']);
        unset($_SESSION['room_area']);
        unset($_SESSION['selected_services']);

        error_log("Room added to session successfully");
        error_log("Total rooms in session: " . count($_SESSION['order_rooms']));

        echo json_encode([
            'success' => true,
            'room_id' => $roomData['id'],
            'total_cost' => $totalCost,
            'rooms_count' => count($_SESSION['order_rooms']),
            'redirect_url' => '/BuildMaster/calculator/order-rooms'
        ]);
    }

    // Виправлений метод getServicesDetails
    private function getServicesDetails($serviceIds)
    {
        if (empty($serviceIds) || !is_array($serviceIds)) {
            error_log("ERROR: getServicesDetails - invalid service IDs: " . json_encode($serviceIds));
            return [];
        }

        try {
            // Конвертуємо всі ID в числа для безпеки
            $serviceIds = array_map('intval', $serviceIds);
            $serviceIds = array_filter($serviceIds, function($id) { return $id > 0; });

            if (empty($serviceIds)) {
                error_log("ERROR: No valid service IDs after filtering");
                return [];
            }

            $placeholders = str_repeat('?,', count($serviceIds) - 1) . '?';

            $stmt = $this->db->prepare("
                SELECT 
                    s.id,
                    s.name,
                    s.description,
                    s.price_per_sqm,
                    sb.area_type
                FROM services s 
                JOIN service_blocks sb ON s.service_block_id = sb.id 
                WHERE s.id IN ($placeholders)
                ORDER BY s.name
            ");

            if (!$stmt->execute($serviceIds)) {
                error_log("ERROR: Failed to execute getServicesDetails query: " . json_encode($stmt->errorInfo()));
                return [];
            }

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("getServicesDetails query result: " . json_encode($result));

            return $result;

        } catch (\Exception $e) {
            error_log("ERROR in getServicesDetails: " . $e->getMessage());
            return [];
        }
    }

    // Виправлений метод getAreaByType
    private function getAreaByType($areaType, $wallArea, $roomArea)
    {
        error_log("getAreaByType called with: areaType={$areaType}, wallArea={$wallArea}, roomArea={$roomArea}");

        switch ($areaType) {
            case 'walls':
                return (float)$wallArea;
            case 'floor':
            case 'ceiling':
                return (float)$roomArea;
            default:
                error_log("WARNING: Unknown area type '{$areaType}', using wall area as default");
                return (float)$wallArea;
        }
    }

    // Виправлений метод оформлення замовлення
    public function completeOrder()
    {
        // Додаємо заголовок для JSON відповіді
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Метод не дозволений']);
            return;
        }

        // Починаємо сесію якщо вона не розпочата
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $orderRooms = $_SESSION['order_rooms'] ?? [];

        error_log("=== COMPLETE ORDER DEBUG ===");
        error_log("Input data: " . json_encode($input));
        error_log("Session order_rooms count: " . count($orderRooms));
        error_log("Session ID: " . session_id());

        if (empty($orderRooms)) {
            error_log("ERROR: No rooms in order");
            http_response_code(400);
            echo json_encode(['error' => 'Немає кімнат для замовлення. Спочатку додайте кімнати.']);
            return;
        }

        // Валідуємо дані клієнта
        $guestName = trim($input['guest_name'] ?? '');
        $guestEmail = trim($input['guest_email'] ?? '');
        $guestPhone = trim($input['guest_phone'] ?? '');
        $notes = trim($input['notes'] ?? '');

        error_log("Customer data: name={$guestName}, email={$guestEmail}, phone={$guestPhone}");

        if (empty($guestName) || empty($guestEmail) || empty($guestPhone)) {
            error_log("ERROR: Missing required customer data");
            http_response_code(400);
            echo json_encode(['error' => 'Заповніть всі обов\'язкові поля (ім\'я, email, телефон)']);
            return;
        }

        // Валідація email
        if (!filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
            error_log("ERROR: Invalid email: " . $guestEmail);
            http_response_code(400);
            echo json_encode(['error' => 'Некоректна email адреса']);
            return;
        }

        try {
            $this->db->beginTransaction();
            error_log("Transaction started");

            // Підраховуємо загальну суму
            $totalAmount = 0;
            foreach ($orderRooms as $room) {
                $totalAmount += (float)$room['total_cost'];
            }

            error_log("Creating order with total amount: " . $totalAmount);

            // Створюємо замовлення
            $stmt = $this->db->prepare("
                INSERT INTO orders (guest_name, guest_email, guest_phone, status, total_amount, notes, created_at, updated_at) 
                VALUES (?, ?, ?, 'pending', ?, ?, NOW(), NOW())
            ");

            $result = $stmt->execute([$guestName, $guestEmail, $guestPhone, $totalAmount, $notes]);

            if (!$result) {
                error_log("ERROR: Failed to execute order insert: " . json_encode($stmt->errorInfo()));
                throw new \Exception('Не вдалося створити замовлення: ' . implode(', ', $stmt->errorInfo()));
            }

            $orderId = $this->db->lastInsertId();

            if (!$orderId) {
                error_log("ERROR: No order ID returned");
                throw new \Exception('Не вдалося отримати ID замовлення');
            }

            error_log("Order created with ID: " . $orderId);

            // Додаємо кімнати до замовлення
            foreach ($orderRooms as $roomIndex => $room) {
                error_log("Processing room {$roomIndex}: " . $room['room_name']);

                // Перевіряємо обов'язкові поля кімнати
                if (!isset($room['room_type_id'], $room['wall_area'], $room['room_area'], $room['room_name'])) {
                    error_log("ERROR: Missing required room data in room {$roomIndex}");
                    throw new \Exception("Недостатньо даних для кімнати {$roomIndex}");
                }

                $stmt = $this->db->prepare("
                    INSERT INTO order_rooms (order_id, room_type_id, wall_area, floor_area, room_name, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");

                $result = $stmt->execute([
                    $orderId,
                    (int)$room['room_type_id'],
                    (float)$room['wall_area'],
                    (float)$room['room_area'],
                    $room['room_name']
                ]);

                if (!$result) {
                    error_log("ERROR: Failed to insert room {$roomIndex}: " . json_encode($stmt->errorInfo()));
                    throw new \Exception('Не вдалося додати кімнату до замовлення: ' . implode(', ', $stmt->errorInfo()));
                }

                $orderRoomId = $this->db->lastInsertId();

                if (!$orderRoomId) {
                    error_log("ERROR: No room ID returned for room {$roomIndex}");
                    throw new \Exception('Не вдалося отримати ID кімнати');
                }

                error_log("Room added with ID: " . $orderRoomId);

                // Додаємо послуги для кімнати
                if (!empty($room['selected_services'])) {
                    foreach ($room['selected_services'] as $service) {
                        $area = $this->getAreaByType($service['area_type'], $room['wall_area'], $room['room_area']);
                        $serviceCost = (float)$service['price_per_sqm'] * $area;

                        error_log("Adding service to room: serviceId={$service['id']}, area={$area}, cost={$serviceCost}");

                        $stmt = $this->db->prepare("
                            INSERT INTO order_room_services (order_room_id, service_id, area_used, price_per_sqm, total_cost, created_at) 
                            VALUES (?, ?, ?, ?, ?, NOW())
                        ");

                        $result = $stmt->execute([
                            $orderRoomId,
                            (int)$service['id'],
                            $area,
                            (float)$service['price_per_sqm'],
                            $serviceCost
                        ]);

                        if (!$result) {
                            error_log("ERROR: Failed to insert service: " . json_encode($stmt->errorInfo()));
                            throw new \Exception('Не вдалося додати послугу до кімнати');
                        }
                    }
                } else {
                    error_log("WARNING: No services found for room {$roomIndex}");
                }
            }

            $this->db->commit();
            error_log("Order completed successfully with ID: " . $orderId);

            // Очищуємо сесію після успішного збереження
            unset($_SESSION['order_rooms']);
            unset($_SESSION['room_type_id']);
            unset($_SESSION['wall_area']);
            unset($_SESSION['room_area']);
            unset($_SESSION['editing_room_id']);
            unset($_SESSION['selected_services']);

            echo json_encode([
                'success' => true,
                'order_id' => $orderId,
                'redirect_url' => '/BuildMaster/calculator/order-success?order_id=' . $orderId
            ]);

        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Order creation error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode(['error' => 'Помилка створення замовлення: ' . $e->getMessage()]);
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

        $orderRooms = $_SESSION['order_rooms'] ?? [];
        $roomTypes = $this->getRoomTypes();
        $totalAmount = 0;

        foreach ($orderRooms as $room) {
            $totalAmount += (float)$room['total_cost'];
        }

        error_log("Showing order rooms - count: " . count($orderRooms) . ", total: " . $totalAmount);

        include 'views/calculator/order-rooms.php';
    }

    public function editRoom()
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

        error_log("Edit room request for ID: " . $roomId);

        if (!$roomId || !isset($_SESSION['order_rooms'])) {
            http_response_code(404);
            echo json_encode(['error' => 'Кімнату не знайдено']);
            return;
        }

        $roomIndex = null;
        foreach ($_SESSION['order_rooms'] as $index => $room) {
            if ($room['id'] === $roomId) {
                $roomIndex = $index;
                break;
            }
        }

        if ($roomIndex === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Кімнату не знайдено']);
            return;
        }

        $room = $_SESSION['order_rooms'][$roomIndex];

        // Зберігаємо дані кімнати в сесію для редагування
        $_SESSION['room_type_id'] = $room['room_type_id'];
        $_SESSION['wall_area'] = $room['wall_area'];
        $_SESSION['room_area'] = $room['room_area'];
        $_SESSION['editing_room_id'] = $roomId;
        $_SESSION['selected_services'] = array_column($room['selected_services'], 'id');

        error_log("Room data set for editing: " . json_encode([
                'room_type_id' => $_SESSION['room_type_id'],
                'wall_area' => $_SESSION['wall_area'],
                'room_area' => $_SESSION['room_area'],
                'editing_room_id' => $_SESSION['editing_room_id'],
                'selected_services' => $_SESSION['selected_services']
            ]));

        echo json_encode([
            'success' => true,
            'redirect_url' => '/BuildMaster/calculator/services-selection'
        ]);
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

        error_log("Remove room request for ID: " . $roomId);

        if (!$roomId || !isset($_SESSION['order_rooms'])) {
            http_response_code(404);
            echo json_encode(['error' => 'Кімнату не знайдено']);
            return;
        }

        $_SESSION['order_rooms'] = array_filter($_SESSION['order_rooms'], function($room) use ($roomId) {
            return $room['id'] !== $roomId;
        });

        $_SESSION['order_rooms'] = array_values($_SESSION['order_rooms']);

        error_log("Room removed. Remaining rooms: " . count($_SESSION['order_rooms']));

        echo json_encode(['success' => true]);
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

    public function updateRoomFromServices()
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
        $editingRoomId = $_SESSION['editing_room_id'] ?? null;

        error_log("=== UPDATE ROOM FROM SERVICES ===");
        error_log("Editing room ID: " . $editingRoomId);
        error_log("Input data: " . json_encode($input));

        if (!$editingRoomId) {
            error_log("No editing room ID - treating as new room");
            return $this->addRoomToOrder();
        }

        if (!isset($_SESSION['order_rooms'])) {
            http_response_code(404);
            echo json_encode(['error' => 'Замовлення не знайдено']);
            return;
        }

        $roomIndex = null;
        foreach ($_SESSION['order_rooms'] as $index => $room) {
            if ($room['id'] === $editingRoomId) {
                $roomIndex = $index;
                break;
            }
        }

        if ($roomIndex === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Кімнату не знайдено']);
            return;
        }

        if (!$input || !isset($input['room_type_id'], $input['wall_area'], $input['room_area'], $input['selected_services'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Не всі дані передано']);
            return;
        }

        $roomTypeId = (int)$input['room_type_id'];
        $wallArea = (float)$input['wall_area'];
        $roomArea = (float)$input['room_area'];
        $selectedServices = $input['selected_services'];
        $roomName = $input['room_name'] ?? '';

        $roomType = $this->getRoomTypeById($roomTypeId);
        if (!$roomType) {
            http_response_code(404);
            echo json_encode(['error' => 'Тип кімнати не знайдено']);
            return;
        }

        $servicesDetails = $this->getServicesDetails($selectedServices);

        if (empty($servicesDetails)) {
            http_response_code(404);
            echo json_encode(['error' => 'Послуги не знайдено']);
            return;
        }

        $totalCost = 0;
        foreach ($servicesDetails as $service) {
            $area = $this->getAreaByType($service['area_type'], $wallArea, $roomArea);
            $totalCost += $service['price_per_sqm'] * $area;
        }

        $_SESSION['order_rooms'][$roomIndex] = [
            'id' => $editingRoomId,
            'room_type_id' => $roomTypeId,
            'room_type_name' => $roomType['name'],
            'room_name' => $roomName ?: $roomType['name'],
            'wall_area' => $wallArea,
            'room_area' => $roomArea,
            'selected_services' => $servicesDetails,
            'total_cost' => $totalCost
        ];

        // Очищуємо тимчасові дані редагування
        unset($_SESSION['editing_room_id']);
        unset($_SESSION['room_type_id']);
        unset($_SESSION['wall_area']);
        unset($_SESSION['room_area']);
        unset($_SESSION['selected_services']);

        error_log("Room updated successfully");

        echo json_encode([
            'success' => true,
            'room_id' => $editingRoomId,
            'total_cost' => $totalCost,
            'redirect_url' => '/BuildMaster/calculator/order-rooms'
        ]);
    }
}