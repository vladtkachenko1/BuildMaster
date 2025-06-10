<?php

class ServiceController {
    private $conn; // PDO connection

    public function __construct($connection) {
        $this->conn = $connection;
    }

    public function index() {
        $services = $this->getAllServices();
        include 'Views/admin/services.php';
    }

    public function create() {
        $serviceBlocks = $this->getServiceBlocks();
        $areas = $this->getAreas();
        include 'Views/admin/service_create.php';
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('HTTP/1.1 405 Method Not Allowed');
            exit;
        }

        try {
            // Валідація даних
            $name = trim($_POST['name'] ?? '');
            $slug = trim($_POST['slug'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $service_block_id = intval($_POST['service_block_id'] ?? 0);
            $price_per_sqm = floatval($_POST['price_per_sqm'] ?? 0);
            $unit = trim($_POST['unit'] ?? 'м²');
            $depends_on_service_id = !empty($_POST['depends_on_service_id']) ? intval($_POST['depends_on_service_id']) : null;
            $is_required = isset($_POST['is_required']) ? 1 : 0;
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $sort_order = intval($_POST['sort_order'] ?? 0);
            $selected_areas = $_POST['areas'] ?? [];

            // Перевірка обов'язкових полів
            if (empty($name) || empty($slug) || $service_block_id <= 0 || $price_per_sqm <= 0) {
                throw new Exception('Всі обов\'язкові поля повинні бути заповнені');
            }

            // Перевірка унікальності slug
            if ($this->slugExists($slug)) {
                throw new Exception('URL-слаг вже існує');
            }

            // Початок транзакції
            $this->conn->beginTransaction();

            // Вставка послуги
            $stmt = $this->conn->prepare("
                INSERT INTO services (name, slug, description, service_block_id, price_per_sqm, unit, 
                                    depends_on_service_id, is_required, is_active, sort_order, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $name, $slug, $description, $service_block_id,
                $price_per_sqm, $unit, $depends_on_service_id, $is_required,
                $is_active, $sort_order
            ]);

            $service_id = $this->conn->lastInsertId();

            // Додавання зв'язків з областями застосування
            if (!empty($selected_areas)) {
                $area_stmt = $this->conn->prepare("INSERT INTO service_area (service_id, area_id) VALUES (?, ?)");
                foreach ($selected_areas as $area_id) {
                    $area_id = intval($area_id);
                    $area_stmt->execute([$service_id, $area_id]);
                }
            }

            // Підтвердження транзакції
            $this->conn->commit();

            // Повернення успішної відповіді
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Послугу успішно створено',
                'service_id' => $service_id
            ]);

        } catch (Exception $e) {
            // Відкат транзакції при помилці
            $this->conn->rollback();

            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function edit($id) {
        $service = $this->getServiceById($id);
        if (!$service) {
            header('Location: ?page=services&error=Service not found');
            exit;
        }

        $serviceBlocks = $this->getServiceBlocks();
        $areas = $this->getAreas();
        $serviceAreas = $this->getServiceAreas($id);

        include 'Views/admin/service_edit.php';
    }

    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('HTTP/1.1 405 Method Not Allowed');
            exit;
        }

        try {
            // Валідація даних
            $name = trim($_POST['name'] ?? '');
            $slug = trim($_POST['slug'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $service_block_id = intval($_POST['service_block_id'] ?? 0);
            $price_per_sqm = floatval($_POST['price_per_sqm'] ?? 0);
            $unit = trim($_POST['unit'] ?? 'м²');
            $depends_on_service_id = !empty($_POST['depends_on_service_id']) ? intval($_POST['depends_on_service_id']) : null;
            $is_required = isset($_POST['is_required']) ? 1 : 0;
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $sort_order = intval($_POST['sort_order'] ?? 0);
            $selected_areas = $_POST['areas'] ?? [];

            // Перевірка обов'язкових полів
            if (empty($name) || empty($slug) || $service_block_id <= 0 || $price_per_sqm <= 0) {
                throw new Exception('Всі обов\'язкові поля повинні бути заповнені');
            }

            // Перевірка унікальності slug
            if ($this->slugExists($slug, $id)) {
                throw new Exception('URL-слаг вже існує');
            }

            // Початок транзакції
            $this->conn->beginTransaction();

            // Оновлення послуги
            $stmt = $this->conn->prepare("
                UPDATE services SET 
                    name = ?, slug = ?, description = ?, service_block_id = ?, 
                    price_per_sqm = ?, unit = ?, depends_on_service_id = ?, 
                    is_required = ?, is_active = ?, sort_order = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $name, $slug, $description, $service_block_id,
                $price_per_sqm, $unit, $depends_on_service_id, $is_required,
                $is_active, $sort_order, $id
            ]);

            // Видалення старих зв'язків з областями
            $delete_stmt = $this->conn->prepare("DELETE FROM service_area WHERE service_id = ?");
            $delete_stmt->execute([$id]);

            // Додавання нових зв'язків з областями застосування
            if (!empty($selected_areas)) {
                $area_stmt = $this->conn->prepare("INSERT INTO service_area (service_id, area_id) VALUES (?, ?)");
                foreach ($selected_areas as $area_id) {
                    $area_id = intval($area_id);
                    $area_stmt->execute([$id, $area_id]);
                }
            }

            // Підтвердження транзакції
            $this->conn->commit();

            // Повернення успішної відповіді
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Послугу успішно оновлено'
            ]);

        } catch (Exception $e) {
            // Відкат транзакції при помилці
            $this->conn->rollback();

            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function delete($id) {
        try {
            // Перевірка чи існує послуга
            $service = $this->getServiceById($id);
            if (!$service) {
                throw new Exception('Послуга не знайдена');
            }

            // Початок транзакції
            $this->conn->beginTransaction();

            // Видалення зв'язків з областями
            $delete_areas = $this->conn->prepare("DELETE FROM service_area WHERE service_id = ?");
            $delete_areas->execute([$id]);

            // Видалення послуги
            $delete_service = $this->conn->prepare("DELETE FROM services WHERE id = ?");
            $delete_service->execute([$id]);

            // Підтвердження транзакції
            $this->conn->commit();

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Послугу успішно видалено'
            ]);

        } catch (Exception $e) {
            // Відкат транзакції при помилці
            $this->conn->rollback();

            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    private function getAllServices() {
        try {
            $query = "
                SELECT s.*, sb.name as block_name, 
                       GROUP_CONCAT(DISTINCT a.name SEPARATOR ', ') as areas
                FROM services s
                LEFT JOIN service_blocks sb ON s.service_block_id = sb.id
                LEFT JOIN service_area sa ON s.id = sa.service_id
                LEFT JOIN areas a ON sa.area_id = a.id
                GROUP BY s.id
                ORDER BY sb.name, s.sort_order, s.name
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database error in getAllServices: " . $e->getMessage());
            return [];
        }
    }

    private function getServiceById($id) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM services WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Database error in getServiceById: " . $e->getMessage());
            return false;
        }
    }

    private function getServiceBlocks() {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM service_blocks WHERE is_active = 1 ORDER BY sort_order, name");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database error in getServiceBlocks: " . $e->getMessage());
            return [];
        }
    }

    private function getAreas() {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM areas ORDER BY area_type, name");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database error in getAreas: " . $e->getMessage());
            return [];
        }
    }

    private function getServiceAreas($service_id) {
        try {
            $stmt = $this->conn->prepare("SELECT area_id FROM service_area WHERE service_id = ?");
            $stmt->execute([$service_id]);
            $result = $stmt->fetchAll();

            $areas = [];
            foreach ($result as $row) {
                $areas[] = $row['area_id'];
            }
            return $areas;
        } catch (PDOException $e) {
            error_log("Database error in getServiceAreas: " . $e->getMessage());
            return [];
        }
    }

    private function getServicesByBlock($block_id) {
        try {
            $stmt = $this->conn->prepare("SELECT id, name FROM services WHERE service_block_id = ? AND is_active = 1 ORDER BY sort_order, name");
            $stmt->execute([$block_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database error in getServicesByBlock: " . $e->getMessage());
            return [];
        }
    }

    private function slugExists($slug, $exclude_id = null) {
        try {
            $query = "SELECT id FROM services WHERE slug = ?";
            $params = [$slug];

            if ($exclude_id) {
                $query .= " AND id != ?";
                $params[] = $exclude_id;
            }

            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetch();

            return $result !== false;
        } catch (PDOException $e) {
            error_log("Database error in slugExists: " . $e->getMessage());
            return false;
        }
    }

    public function getServicesByBlockAjax() {
        header('Content-Type: application/json');

        $block_id = intval($_GET['block_id'] ?? 0);

        if ($block_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Невірний ID блоку']);
            return;
        }

        $services = $this->getServicesByBlock($block_id);

        echo json_encode([
            'success' => true,
            'services' => $services
        ]);
    }

    // Методи для AJAX запитів
    public function handleAjaxRequest() {
        $action = $_GET['ajax_action'] ?? '';

        switch ($action) {
            case 'get_services_by_block':
                $this->getServicesByBlockAjax();
                break;
            default:
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Невідома дія']);
        }
    }
}