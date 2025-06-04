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

    /**
     * Додаємо відсутній метод servicesSelection
     */
    public function servicesSelection()
    {
        return $this->view('calculator/services-selection');
    }

    /**
     * Додаємо відсутній метод createProject
     */
    public function createProject()
    {
        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input) {
                throw new \Exception('Некоректні дані');
            }

            // Зберігаємо дані проекту в сесії
            $_SESSION['current_project'] = $input;

            echo json_encode(['success' => true, 'message' => 'Проект створено']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Помилка створення проекту: ' . $e->getMessage()]);
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

            // Створюємо фейкові області для підлоги, стін та стелі
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

            // Групуємо по блоках
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

            // Розподіляємо блоки по областях (логіка може варіюватися)
            foreach ($groupedByBlock as $block) {
                // Простий розподіл: перші блоки - підлога, середні - стіни, останні - стеля
                $blockName = strtolower($block['name']);

                if (strpos($blockName, 'підлог') !== false || strpos($blockName, 'пол') !== false) {
                    $areas[0]['service_blocks'][] = $block;
                } elseif (strpos($blockName, 'стел') !== false || strpos($blockName, 'потолок') !== false) {
                    $areas[2]['service_blocks'][] = $block;
                } else {
                    // За замовчуванням - стіни
                    $areas[1]['service_blocks'][] = $block;
                }
            }

            // Видаляємо порожні області
            $areas = array_filter($areas, function($area) {
                return !empty($area['service_blocks']);
            });

            return array_values($areas);

        } catch (\Exception $e) {
            error_log("Error getting grouped services: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Спрощений розрахунок вартості
     */
    private function calculateTotal($selectedServices, $wallArea, $roomArea)
    {
        $total = 0;

        foreach ($selectedServices as $serviceId) {
            try {
                // Виправляємо $this->database на $this->db
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

                    // Простий розподіл по типу послуги на основі назви
                    if (strpos($serviceName, 'підлог') !== false || strpos($serviceName, 'пол') !== false ||
                        strpos($serviceName, 'стел') !== false || strpos($serviceName, 'потолок') !== false) {
                        $area = $roomArea; // Підлога та стеля = площа кімнати
                    } else {
                        $area = $wallArea; // Стіни та інше = площа стін
                    }

                    $total += $price * $area;
                }
            } catch (\Exception $e) {
                error_log("Error calculating service cost: " . $e->getMessage());
            }
        }

        return $total;
    }

    /**
     * API метод для отримання послуг в JSON форматі
     */
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

    /**
     * API метод для розрахунку вартості
     */
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