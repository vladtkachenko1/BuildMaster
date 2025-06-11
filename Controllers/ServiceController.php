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

        // Увімкнути буферизацію
        ob_start();

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

            // Очистити буфер та повернути успішну відповідь
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Послугу успішно створено',
                'service_id' => $service_id
            ]);

        } catch (Exception $e) {
            // Відкат транзакції при помилці
            $this->conn->rollback();

            // Очистити буфер та повернути помилку
            ob_end_clean();
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

        // Увімкнути буферизацію
        ob_start();

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
                    is_required = ?, is_active = ?, sort_order = ?, updated_at = NOW()
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

            // Очистити буфер та повернути успішну відповідь
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Послугу успішно оновлено'
            ]);

        } catch (Exception $e) {
            // Відкат транзакції при помилці
            $this->conn->rollback();

            // Очистити буфер та повернути помилку
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Швидке оновлення ціни послуги
     */
    public function updatePrice($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('HTTP/1.1 405 Method Not Allowed');
            exit;
        }

        // Увімкнути буферизацію
        ob_start();

        try {
            // Валідація ID
            $service_id = intval($id);
            if ($service_id <= 0) {
                throw new Exception('Невірний ID послуги');
            }

            // Отримання та валідація нової ціни
            $new_price = floatval($_POST['price'] ?? 0);
            if ($new_price <= 0) {
                throw new Exception('Ціна повинна бути більше 0');
            }

            // Перевірка існування послуги
            $service = $this->getServiceById($service_id);
            if (!$service) {
                throw new Exception('Послуга не знайдена');
            }

            // Збереження старої ціни для логування
            $old_price = $service['price_per_sqm'];

            // Оновлення ціни
            $stmt = $this->conn->prepare("
                UPDATE services 
                SET price_per_sqm = ?, updated_at = NOW() 
                WHERE id = ?
            ");

            $stmt->execute([$new_price, $service_id]);

            // Перевірка чи оновлення пройшло успішно
            if ($stmt->rowCount() === 0) {
                throw new Exception('Не вдалося оновити ціну');
            }

            // Логування зміни ціни (якщо потрібно)
            error_log("Price updated for service ID {$service_id}: {$old_price} -> {$new_price}");

            // Очистити буфер та повернути успішну відповідь
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Ціну успішно оновлено',
                'old_price' => $old_price,
                'new_price' => $new_price,
                'service_name' => $service['name']
            ]);

        } catch (Exception $e) {
            // Очистити буфер та повернути помилку
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Видалення послуги з перевірками
     */
    public function delete($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            header('HTTP/1.1 405 Method Not Allowed');
            exit;
        }

        // Увімкнути буферизацію
        ob_start();

        try {
            $service_id = intval($id);
            if ($service_id <= 0) {
                throw new Exception('Невірний ID послуги');
            }

            // Перевірка чи існує послуга
            $service = $this->getServiceById($service_id);
            if (!$service) {
                throw new Exception('Послуга не знайдена');
            }

            // Перевірка чи послуга використовується в замовленнях
            if ($this->isServiceUsedInOrders($service_id)) {
                throw new Exception('Неможливо видалити послугу, яка використовується в замовленнях. Спочатку деактивуйте її.');
            }

            // Перевірка чи від неї залежать інші послуги
            if ($this->hasServiceDependencies($service_id)) {
                throw new Exception('Неможливо видалити послугу, від якої залежать інші послуги');
            }

            // Початок транзакції
            $this->conn->beginTransaction();

            // Видалення зв'язків з областями
            $delete_areas_stmt = $this->conn->prepare("DELETE FROM service_area WHERE service_id = ?");
            $delete_areas_stmt->execute([$service_id]);

            // Видалення послуги
            $delete_service_stmt = $this->conn->prepare("DELETE FROM services WHERE id = ?");
            $delete_service_stmt->execute([$service_id]);

            // Перевірка чи видалення пройшло успішно
            if ($delete_service_stmt->rowCount() === 0) {
                throw new Exception('Не вдалося видалити послугу');
            }

            // Підтвердження транзакції
            $this->conn->commit();

            // Логування видалення
            error_log("Service deleted: ID {$service_id}, Name: {$service['name']}");

            // Очистити буфер та повернути успішну відповідь
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Послугу "' . $service['name'] . '" успішно видалено',
                'deleted_service' => [
                    'id' => $service_id,
                    'name' => $service['name']
                ]
            ]);

        } catch (Exception $e) {
            // Відкат транзакції при помилці
            if ($this->conn->inTransaction()) {
                $this->conn->rollback();
            }

            // Очистити буфер та повернути помилку
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Масове видалення послуг
     */
    public function bulkDelete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('HTTP/1.1 405 Method Not Allowed');
            exit;
        }

        // Увімкнути буферизацію
        ob_start();

        try {
            $service_ids = $_POST['service_ids'] ?? [];

            if (empty($service_ids) || !is_array($service_ids)) {
                throw new Exception('Не вибрано жодної послуги для видалення');
            }

            // Валідація ID
            $validated_ids = [];
            foreach ($service_ids as $id) {
                $id = intval($id);
                if ($id > 0) {
                    $validated_ids[] = $id;
                }
            }

            if (empty($validated_ids)) {
                throw new Exception('Невірні ID послуг');
            }

            $deleted_services = [];
            $errors = [];

            // Початок транзакції
            $this->conn->beginTransaction();

            foreach ($validated_ids as $service_id) {
                try {
                    // Перевірка існування послуги
                    $service = $this->getServiceById($service_id);
                    if (!$service) {
                        $errors[] = "Послуга з ID {$service_id} не знайдена";
                        continue;
                    }

                    // Перевірки перед видаленням
                    if ($this->isServiceUsedInOrders($service_id)) {
                        $errors[] = "Послугу '{$service['name']}' неможливо видалити - використовується в замовленнях";
                        continue;
                    }

                    if ($this->hasServiceDependencies($service_id)) {
                        $errors[] = "Послугу '{$service['name']}' неможливо видалити - від неї залежать інші послуги";
                        continue;
                    }

                    // Видалення зв'язків з областями
                    $delete_areas_stmt = $this->conn->prepare("DELETE FROM service_area WHERE service_id = ?");
                    $delete_areas_stmt->execute([$service_id]);

                    // Видалення послуги
                    $delete_service_stmt = $this->conn->prepare("DELETE FROM services WHERE id = ?");
                    $delete_service_stmt->execute([$service_id]);

                    if ($delete_service_stmt->rowCount() > 0) {
                        $deleted_services[] = [
                            'id' => $service_id,
                            'name' => $service['name']
                        ];
                    }

                } catch (Exception $e) {
                    $errors[] = "Помилка при видаленні послуги з ID {$service_id}: " . $e->getMessage();
                }
            }

            // Підтвердження транзакції
            $this->conn->commit();

            // Логування
            if (!empty($deleted_services)) {
                $deleted_names = array_column($deleted_services, 'name');
                error_log("Bulk delete completed. Deleted services: " . implode(', ', $deleted_names));
            }

            // Очистити буфер та повернути результат
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => count($deleted_services) . ' послуг успішно видалено',
                'deleted_services' => $deleted_services,
                'errors' => $errors,
                'total_processed' => count($validated_ids),
                'successfully_deleted' => count($deleted_services)
            ]);

        } catch (Exception $e) {
            // Відкат транзакції при помилці
            if ($this->conn->inTransaction()) {
                $this->conn->rollback();
            }

            // Очистити буфер та повернути помилку
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    // Приватні методи для перевірок

    /**
     * Перевірка чи послуга використовується в замовленнях
     */
    private function isServiceUsedInOrders($service_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count 
                FROM order_services 
                WHERE service_id = ?
            ");
            $stmt->execute([$service_id]);
            $result = $stmt->fetch();
            return $result['count'] > 0;
        } catch (PDOException $e) {
            error_log("Database error in isServiceUsedInOrders: " . $e->getMessage());
            return true; // У випадку помилки вважаємо що використовується (безпечніше)
        }
    }

    /**
     * Перевірка чи від послуги залежать інші послуги
     */
    private function hasServiceDependencies($service_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count 
                FROM services 
                WHERE depends_on_service_id = ?
            ");
            $stmt->execute([$service_id]);
            $result = $stmt->fetch();
            return $result['count'] > 0;
        } catch (PDOException $e) {
            error_log("Database error in hasServiceDependencies: " . $e->getMessage());
            return true; // У випадку помилки вважаємо що є залежності (безпечніше)
        }
    }

    // Існуючі приватні методи

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
        // Увімкнути буферизацію
        ob_start();

        header('Content-Type: application/json');

        $block_id = intval($_GET['block_id'] ?? 0);

        if ($block_id <= 0) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Невірний ID блоку']);
            return;
        }

        $services = $this->getServicesByBlock($block_id);

        ob_end_clean();
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
            case 'update_price':
                $service_id = intval($_GET['service_id'] ?? 0);
                $this->updatePrice($service_id);
                break;
            case 'delete_service':
                $service_id = intval($_GET['service_id'] ?? 0);
                $this->delete($service_id);
                break;
            case 'bulk_delete':
                $this->bulkDelete();
                break;
            default:
                ob_start();
                header('Content-Type: application/json');
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Невідома дія']);
        }
    }
}