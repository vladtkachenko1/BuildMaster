<?php
// CalculatorController - виправлена версія

namespace BuildMaster\Controllers;

class CalculatorController
{
    private $db;

    public function __construct($database = null)
    {
        $this->db = $database;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function index()
    {
        return $this->view('calculator/calculator');
    }

    // ВИДАЛЯЄМО метод createNewOrder - він не потрібен тут
    // Замовлення буде створюватися в OrderController при додаванні кімнати

    public function getRoomTypes()
    {
        try {
            if (!$this->db) {
                throw new \Exception('База даних не підключена');
            }

            $stmt = $this->db->prepare("SELECT id, name, slug FROM room_types ORDER BY name");
            $stmt->execute();
            $roomTypes = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $roomTypes]);
        } catch (\Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Помилка завантаження типів кімнат: ' . $e->getMessage()]);
        }
        exit;
    }

    public function getProjectForm()
    {
        return $this->view('calculator/project-form');
    }

    public function servicesSelection()
    {
        return $this->view('calculator/services-selection');
    }

// У CalculatorController замінити метод createProject():

    public function createProject()
    {
        header('Content-Type: application/json');

        try {
            $roomTypeId = $_POST['room_type_id'] ?? null;
            $wallArea = floatval($_POST['wall_area'] ?? 0);
            $roomArea = floatval($_POST['room_area'] ?? 0);

            if (!$roomTypeId || $wallArea <= 0 || $roomArea <= 0) {
                throw new \Exception('Некоректні дані форми');
            }

            // Перевіряємо чи є активне замовлення, якщо ні - створюємо
            if (!isset($_SESSION['current_order_id'])) {
                // ВИПРАВЛЕНО: створюємо нове замовлення з правильними NULL значеннями
                $stmt = $this->db->prepare("
                INSERT INTO orders (user_id, guest_name, guest_email, guest_phone, status, total_amount, notes, admin_notes, created_at, updated_at) 
                VALUES (NULL, NULL, NULL, NULL, 'draft', 0.00, NULL, NULL, NOW(), NOW())
            ");
                $stmt->execute();
                $_SESSION['current_order_id'] = $this->db->lastInsertId();
            }

            $orderId = $_SESSION['current_order_id'];

            $this->db->beginTransaction();

            // ВИПРАВЛЕНО: додаємо room_type_id в запит
            $stmt = $this->db->prepare("
            INSERT INTO order_rooms (order_id, room_type_id, wall_area, floor_area, room_name, created_at) 
            VALUES (?, ?, ?, ?, 'Нова кімната', NOW())
        ");
            $stmt->execute([$orderId, $roomTypeId, $wallArea, $roomArea]);

            $roomId = $this->db->lastInsertId();

            $this->db->commit();

            // Зберігаємо ID кімнати в сесії
            $_SESSION['current_room_id'] = $roomId;

            echo json_encode([
                'success' => true,
                'message' => 'Кімнату створено',
                'room_id' => $roomId,
                'redirect_url' => '/BuildMaster/calculator/services-selection?room_type_id=' . $roomTypeId . '&wall_area=' . $wallArea . '&room_area=' . $roomArea . '&room_id=' . $roomId
            ]);

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Помилка збереження даних: ' . $e->getMessage()
            ]);
        }
    }
    public function saveRoomWithServices()
    {
        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $selectedServices = $input['services'] ?? [];
            $roomName = $input['room_name'] ?? 'Кімната';
            $roomId = $_SESSION['current_room_id'] ?? null;

            if (!isset($_SESSION['current_order_id']) || !$roomId) {
                throw new \Exception('Дані сесії відсутні');
            }

            $orderId = $_SESSION['current_order_id'];

            // Отримуємо дані кімнати
            $stmt = $this->db->prepare("SELECT * FROM order_rooms WHERE id = ? AND order_id = ?");
            $stmt->execute([$roomId, $orderId]);
            $room = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$room) {
                throw new \Exception('Кімната не знайдена');
            }

            $this->db->beginTransaction();

            // Оновлюємо назву кімнати якщо потрібно
            if ($roomName !== 'Кімната') {
                $stmt = $this->db->prepare("UPDATE order_rooms SET room_name = ? WHERE id = ?");
                $stmt->execute([$roomName, $roomId]);
            }

            $totalRoomPrice = 0;

            // Додаємо послуги
            foreach ($selectedServices as $serviceId) {
                $stmt = $this->db->prepare("SELECT name, price_per_sqm FROM services WHERE id = ?");
                $stmt->execute([$serviceId]);
                $service = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($service) {
                    $serviceName = strtolower($service['name']);
                    $unitPrice = floatval($service['price_per_sqm']);

                    if (strpos($serviceName, 'підлог') !== false ||
                        strpos($serviceName, 'пол') !== false ||
                        strpos($serviceName, 'стел') !== false ||
                        strpos($serviceName, 'потолок') !== false) {
                        $quantity = $room['floor_area'];
                    } else {
                        $quantity = $room['wall_area'];
                    }

                    $totalPrice = $unitPrice * $quantity;
                    $totalRoomPrice += $totalPrice;

                    $stmt = $this->db->prepare("
                    INSERT INTO order_room_services 
                    (order_room_id, service_id, quantity, unit_price, total_price, is_selected, created_at) 
                    VALUES (?, ?, ?, ?, ?, 1, NOW())
                ");
                    $stmt->execute([$roomId, $serviceId, $quantity, $unitPrice, $totalPrice]);
                }
            }

            // Оновлюємо загальну суму замовлення
            $stmt = $this->db->prepare("
            UPDATE orders 
            SET total_amount = total_amount + ?, updated_at = NOW() 
            WHERE id = ?
        ");
            $stmt->execute([$totalRoomPrice, $orderId]);

            $this->db->commit();

            // Очищуємо дані кімнати з сесії
            unset($_SESSION['current_room_id']);

            echo json_encode([
                'success' => true,
                'message' => 'Кімнату додано до замовлення',
                'room_id' => $roomId,
                'total_price' => $totalRoomPrice,
                'redirect_url' => '/BuildMaster/calculator/order-rooms'
            ]);

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Помилка збереження кімнати: ' . $e->getMessage()
            ]);
        }
    }
    public function getCurrentOrderRooms()
    {
        header('Content-Type: application/json');

        try {
            if (!isset($_SESSION['current_order_id'])) {
                echo json_encode(['rooms' => []]);
                return;
            }

            $orderId = $_SESSION['current_order_id'];

            $stmt = $this->db->prepare("
                SELECT 
                    or.id,
                    or.room_name,
                    or.wall_area,
                    or.floor_area,
                    rt.name as room_type_name,
                    COUNT(ors.id) as services_count,
                    SUM(ors.total_price) as room_total
                FROM order_rooms or
                LEFT JOIN room_types rt ON or.room_type_id = rt.id
                LEFT JOIN order_room_services ors ON or.id = ors.order_room_id AND ors.is_selected = 1
                WHERE or.order_id = ?
                GROUP BY or.id
                ORDER BY or.created_at DESC
            ");
            $stmt->execute([$orderId]);
            $rooms = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            echo json_encode(['rooms' => $rooms]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Помилка завантаження кімнат: ' . $e->getMessage()
            ]);
        }
    }

    public function getRoomDetails($roomId)
    {
        header('Content-Type: application/json');

        try {
            $stmt = $this->db->prepare("
                SELECT 
                    or.*,
                    rt.name as room_type_name,
                    rt.id as room_type_id
                FROM order_rooms or
                LEFT JOIN room_types rt ON or.room_type_id = rt.id
                WHERE or.id = ?
            ");
            $stmt->execute([$roomId]);
            $room = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$room) {
                throw new \Exception('Кімнату не знайдено');
            }

            $stmt = $this->db->prepare("
                SELECT 
                    ors.*,
                    s.name as service_name,
                    s.description as service_description
                FROM order_room_services ors
                LEFT JOIN services s ON ors.service_id = s.id
                WHERE ors.order_room_id = ? AND ors.is_selected = 1
                ORDER BY s.name
            ");
            $stmt->execute([$roomId]);
            $services = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $room['services'] = $services;

            echo json_encode([
                'success' => true,
                'room' => $room
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Помилка завантаження деталей: ' . $e->getMessage()
            ]);
        }
    }

    public function getGroupedServicesByRoomType($roomTypeId)
    {
        try {
            $stmt = $this->db->prepare("
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
                    s.price_per_sqm
                FROM service_blocks sb
                LEFT JOIN services s ON sb.id = s.service_block_id
                WHERE sb.room_type_id = ?
                ORDER BY sb.sort_order ASC, s.name ASC
            ");
            $stmt->execute([$roomTypeId]);
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $areas = [
                [
                    'area_id' => 1,
                    'area_name' => 'Підлога',
                    'area_type' => 'floor',
                    'service_blocks' => []
                ],
                [
                    'area_id' => 2,
                    'area_name' => 'Стіни',
                    'area_type' => 'walls',
                    'service_blocks' => []
                ],
                [
                    'area_id' => 3,
                    'area_name' => 'Стеля',
                    'area_type' => 'ceiling',
                    'service_blocks' => []
                ]
            ];

            $groupedByBlock = [];

            foreach ($result as $row) {
                $blockId = $row['block_id'];

                if (!isset($groupedByBlock[$blockId])) {
                    $groupedByBlock[$blockId] = [
                        'id' => $row['block_id'],
                        'name' => $row['block_name'],
                        'slug' => $row['block_slug'],
                        'description' => $row['block_description'],
                        'sort_order' => $row['block_sort_order'],
                        'services' => []
                    ];
                }

                if ($row['service_id']) {
                    $groupedByBlock[$blockId]['services'][] = [
                        'id' => $row['service_id'],
                        'name' => $row['service_name'],
                        'slug' => $row['service_slug'],
                        'description' => $row['service_description'],
                        'price_per_sqm' => $row['price_per_sqm']
                    ];
                }
            }

            foreach ($groupedByBlock as $block) {
                $blockName = strtolower($block['name']);

                if (strpos($blockName, 'підлог') !== false || strpos($blockName, 'пол') !== false) {
                    $areas[0]['service_blocks'][] = $block;
                } elseif (strpos($blockName, 'стел') !== false || strpos($blockName, 'потолок') !== false) {
                    $areas[2]['service_blocks'][] = $block;
                } else {
                    $areas[1]['service_blocks'][] = $block;
                }
            }

            $areas = array_filter($areas, function($area) {
                return !empty($area['service_blocks']);
            });

            return array_values($areas);

        } catch (\Exception $e) {
            error_log("Error getting grouped services: " . $e->getMessage());
            return [];
        }
    }

    private function calculateTotal($selectedServices, $wallArea, $roomArea)
    {
        $total = 0;

        foreach ($selectedServices as $serviceId) {
            try {
                $stmt = $this->db->prepare("
                    SELECT price_per_sqm, name
                    FROM services 
                    WHERE id = ?
                ");
                $stmt->execute([$serviceId]);
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($result) {
                    $price = floatval($result['price_per_sqm']);
                    $serviceName = strtolower($result['name']);

                    if (strpos($serviceName, 'підлог') !== false || strpos($serviceName, 'пол') !== false ||
                        strpos($serviceName, 'стел') !== false || strpos($serviceName, 'потолок') !== false) {
                        $area = $roomArea;
                    } else {
                        $area = $wallArea;
                    }

                    $total += $price * $area;
                }
            } catch (\Exception $e) {
                error_log("Error calculating service cost: " . $e->getMessage());
            }
        }

        return $total;
    }

    public function getServicesJson()
    {
        header('Content-Type: application/json');

        $roomTypeId = $_GET['room_type_id'] ?? null;

        if (!$roomTypeId) {
            http_response_code(400);
            echo json_encode(['error' => 'room_type_id is required']);
            return;
        }

        try {
            $services = $this->getGroupedServicesByRoomType($roomTypeId);
            echo json_encode($services);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Помилка завантаження послуг: ' . $e->getMessage()]);
        }
    }

    public function calculateJson()
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);

        $selectedServices = $input['services'] ?? [];
        $wallArea = floatval($input['wall_area'] ?? 0);
        $roomArea = floatval($input['room_area'] ?? 0);
        $roomTypeId = intval($input['room_type_id'] ?? 0);

        if (empty($selectedServices) || $wallArea <= 0 || $roomArea <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input data']);
            return;
        }

        try {
            $total = $this->calculateTotal($selectedServices, $wallArea, $roomArea);

            echo json_encode([
                'total' => $total,
                'wall_area' => $wallArea,
                'room_area' => $roomArea,
                'services_count' => count($selectedServices),
                'room_type_id' => $roomTypeId
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Помилка розрахунку: ' . $e->getMessage()]);
        }
    }

    public function result()
    {
        if (!isset($_SESSION['current_project'])) {
            header('Location: /BuildMaster/calculator');
            exit;
        }

        return $this->view('calculator/result', ['project' => $_SESSION['current_project']]);
    }

    private function view($viewName, $data = [])
    {
        extract($data);
        $viewPath = __DIR__ . "/../Views/{$viewName}.php";

        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            echo "Помилка: не знайдено файл виду {$viewPath}";
        }
    }
}