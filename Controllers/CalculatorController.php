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

            $_SESSION['current_project'] = [
                'room_type_id' => $roomTypeId,
                'room_type_name' => $roomType['name'],
                'room_type_slug' => $roomType['slug'],
                'wall_area' => $wallArea,
                'room_area' => $roomArea,
                'created_at' => date('Y-m-d H:i:s')
            ];

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'redirect' => '/BuildMaster/calculator/materials/' . $roomType['slug']
            ]);

        } catch (\Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Помилка створення проекту: ' . $e->getMessage()]);
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