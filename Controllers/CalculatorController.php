<?php
// CalculatorController - виправлена версія

namespace BuildMaster\Controllers;

class CalculatorController
{
    private $db;
    private $serviceCalculatorController;

    public function __construct($database = null)
    {
        $this->db = $database;
        // Ініціалізуємо ServiceCalculatorController
        $this->serviceCalculatorController = new ServiceCalculatorController($database);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
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

            // Зберігаємо дані кімнати в сесії для подальшого використання
            $_SESSION['room_data'] = [
                'room_type_id' => $roomTypeId,
                'wall_area' => $wallArea,
                'floor_area' => $roomArea
            ];

            echo json_encode([
                'success' => true,
                'message' => 'Дані кімнати збережено',
                'redirect_url' => '/BuildMaster/calculator/services-selection?room_type_id=' . $roomTypeId . '&wall_area=' . $wallArea . '&room_area=' . $roomArea
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Помилка збереження даних: ' . $e->getMessage()
            ]);
        }
    }
    private function updateOrderTotal($orderId)
    {
        try {
            $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(ors.total_price), 0) as total
            FROM order_room_services ors
            JOIN order_rooms ord_room ON ors.order_room_id = ord_room.id
            WHERE ord_room.order_id = ? AND ors.is_selected = 1
        ");
            $stmt->execute([$orderId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            $total = $result['total'] ?? 0;

            $stmt = $this->db->prepare("UPDATE orders SET total_amount = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$total, $orderId]);

        } catch (\Exception $e) {
            error_log("Error updating order total: " . $e->getMessage());
            throw $e;
        }
    }
    public function getServicesJson()
    {
        return $this->serviceCalculatorController->getServicesJson();
    }
    public function calculateJson()
    {
        return $this->serviceCalculatorController->calculateJson();
    }
    public function saveRoomWithServices()
    {
        return $this->serviceCalculatorController->saveRoomWithServices();
    }
    public function getCurrentOrderRooms()
    {
        return $this->serviceCalculatorController->getCurrentOrderRooms();
    }
    public function getRoomDetails($roomId)
    {
        return $this->serviceCalculatorController->getRoomDetails($roomId);
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