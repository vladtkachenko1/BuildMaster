<?php

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

    public function createProject()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Неправильний метод запиту']);
            exit;
        }

        $roomTypeId = $_POST['room_type_id'] ?? null;
        $wallArea = isset($_POST['wall_area']) ? floatval($_POST['wall_area']) : 0;
        $roomArea = isset($_POST['room_area']) ? floatval($_POST['room_area']) : 0;

        // Валідація
        if (!$roomTypeId) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Оберіть тип кімнати']);
            exit;
        }

        if ($wallArea <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Введіть коректну площу стін']);
            exit;
        }

        if ($roomArea <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Введіть коректну площу кімнати']);
            exit;
        }

        if ($wallArea < $roomArea) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Площа стін не може бути менше площі кімнати']);
            exit;
        }

        try {
            if (!$this->db) {
                throw new \Exception('База даних не підключена');
            }

            $stmt = $this->db->prepare("SELECT name, slug FROM room_types WHERE id = ?");
            $stmt->execute([$roomTypeId]);
            $roomType = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$roomType) {
                throw new \Exception('Тип кімнати не знайдено');
            }

            // Зберігаємо дані в сесії для подальшого використання
            $_SESSION['current_project'] = [
                'room_type_id' => $roomTypeId,
                'room_type_name' => $roomType['name'],
                'room_type_slug' => $roomType['slug'],
                'wall_area' => $wallArea,
                'room_area' => $roomArea,
                'created_at' => date('Y-m-d H:i:s')
            ];

            // Зберігаємо також для сторінки вибору послуг
            $_SESSION['room_type_id'] = $roomTypeId;
            $_SESSION['wall_area'] = $wallArea;
            $_SESSION['room_area'] = $roomArea;

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'redirect' => '/BuildMaster/calculator/services-selection?room_type_id=' . urlencode($roomTypeId) . '&wall_area=' . urlencode($wallArea) . '&room_area=' . urlencode($roomArea)
            ]);

        } catch (\Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Помилка створення проекту: ' . $e->getMessage()]);
        }
        exit;
    }

    // Метод для відображення сторінки вибору послуг
    public function servicesSelection()
    {
        // Отримуємо дані з сесії або параметрів URL
        $roomTypeId = $_SESSION['room_type_id'] ?? $_GET['room_type_id'] ?? null;
        $wallArea = $_SESSION['wall_area'] ?? $_GET['wall_area'] ?? 0;
        $roomArea = $_SESSION['room_area'] ?? $_GET['room_area'] ?? 0;

        if (!$roomTypeId || $wallArea <= 0 || $roomArea <= 0) {
            header('Location: /BuildMaster/calculator');
            exit;
        }

        // Зберігаємо дані в сесії, якщо вони прийшли через GET
        $_SESSION['room_type_id'] = $roomTypeId;
        $_SESSION['wall_area'] = $wallArea;
        $_SESSION['room_area'] = $roomArea;

        return $this->view('calculator/services-selection', [
            'roomTypeId' => $roomTypeId,
            'wallArea' => $wallArea,
            'roomArea' => $roomArea
        ]);
    }

    // Використовуємо ServiceCalculatorController для роботи з послугами
    public function getServicesJson()
    {
        // Створюємо екземпляр ServiceCalculatorController
        $serviceController = new ServiceCalculatorController($this->db);

        header('Content-Type: application/json');

        $roomTypeId = $_GET['room_type_id'] ?? null;

        if (!$roomTypeId) {
            http_response_code(400);
            echo json_encode(['error' => 'room_type_id is required']);
            return;
        }

        try {
            $services = $serviceController->getGroupedServicesByRoomType($roomTypeId);
            echo json_encode($services);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Помилка завантаження послуг: ' . $e->getMessage()]);
        }
        exit;
    }

    public function calculateJson()
    {
        // Створюємо екземпляр ServiceCalculatorController
        $serviceController = new ServiceCalculatorController($this->db);

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

        try {
            $total = $serviceController->calculateTotal($selectedServices, $wallArea, $roomArea);

            echo json_encode([
                'total' => $total,
                'wall_area' => $wallArea,
                'room_area' => $roomArea,
                'services_count' => count($selectedServices)
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Помилка розрахунку: ' . $e->getMessage()]);
        }
        exit;
    }

    public function materials($slug)
    {
        if (!isset($_SESSION['current_project'])) {
            header('Location: /BuildMaster/calculator');
            exit;
        }

        $project = $_SESSION['current_project'];

        if ($project['room_type_slug'] !== $slug) {
            header('Location: /BuildMaster/calculator');
            exit;
        }

        return $this->view('calculator/materials', ['project' => $project]);
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