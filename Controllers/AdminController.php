<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/AuthController.php';

class AdminController {
    private $db;
    private $auth;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->auth = new AuthController();

        // Перевіряємо чи користувач є адміном
        $this->auth->requireAdmin();
    }

    public function handleRequest() {
        $action = isset($_GET['action']) ? $_GET['action'] : 'dashboard';

        switch ($action) {
            case 'dashboard':
                $this->dashboard();
                break;
            case 'users':
                $this->users();
                break;
            case 'user_update':
                $this->updateUser();
                break;
            case 'user_delete':
                $this->deleteUser();
                break;
            case 'bulk_update_users':
                $this->bulkUpdateUsers();
                break;
            case 'bulk_delete_users':
                $this->bulkDeleteUsers();
                break;
            case 'orders':
                $this->orders();
                break;
            case 'order_update':
                $this->updateOrder();
                break;
            case 'order_delete':
                $this->deleteOrder();
                break;
            case 'get_order':
                $this->getOrder();
                break;
            case 'statistics':
                $this->statistics();
                break;
            case 'export_users':
                $this->exportUsers();
                break;
            default:
                $this->dashboard();
                break;
        }
    }

    public function users() {
        $action = 'users';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';

        // Ініціалізуємо змінні за замовчуванням
        $users = [];
        $totalUsers = 0;
        $totalPages = 1;

        // Формуємо запит з пошуком
        $whereClause = '';
        $params = [];

        if (!empty($search)) {
            $whereClause = "WHERE (first_name LIKE :search_first OR last_name LIKE :search_last OR email LIKE :search_email)";
            $params[':search_first'] = '%' . $search . '%';
            $params[':search_last'] = '%' . $search . '%';
            $params[':search_email'] = '%' . $search . '%';
        }

        try {
            // Підраховуємо загальну кількість користувачів
            $countSql = "SELECT COUNT(*) FROM users $whereClause";
            $stmt = $this->db->prepare($countSql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $totalUsers = $stmt->fetchColumn();
            $totalPages = ceil($totalUsers / $limit);

            // Отримуємо користувачів з пагінацією
            $sql = "
                SELECT id, first_name, last_name, email, phone, is_admin, status, created_at 
                FROM users 
                $whereClause
                ORDER BY created_at DESC 
                LIMIT :limit OFFSET :offset
            ";

            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            
            error_log("Executing users query with params: " . json_encode($params));
            $stmt->execute();
            $users = $stmt->fetchAll();
            error_log("Found " . count($users) . " users");

        } catch (PDOException $e) {
            error_log("Error in users method: " . $e->getMessage());
        }

        include __DIR__ . '/../Views/admin/users.php';
    }

    public function updateUser() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
            $isAdmin = isset($_POST['is_admin']) ? 1 : 0;
            $status = isset($_POST['status']) ? $_POST['status'] : 'active';

            if (!in_array($status, ['active', 'inactive', 'banned'])) {
                $status = 'active';
            }

            $currentUser = $this->auth->getCurrentUser();

            // Заборонити зміну власного статусу адміна
            if ($userId === $currentUser['id'] && !$isAdmin) {
                echo json_encode(['success' => false, 'message' => 'Не можна змінити власний статус адміна']);
                return;
            }

            try {
                $stmt = $this->db->prepare("
                    UPDATE users 
                    SET is_admin = :is_admin, status = :status 
                    WHERE id = :id
                ");
                $stmt->bindParam(':is_admin', $isAdmin, PDO::PARAM_INT);
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':id', $userId, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Користувача успішно оновлено']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Помилка оновлення']);
                }
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Помилка: ' . $e->getMessage()]);
            }
            return;
        }

        http_response_code(405);
        echo json_encode(['error' => 'Метод не дозволено']);
    }

    public function deleteUser() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
            $currentUser = $this->auth->getCurrentUser();

            // Заборонити видалення самого себе
            if ($userId === $currentUser['id']) {
                echo json_encode(['success' => false, 'message' => 'Не можна видалити власний акаунт']);
                return;
            }

            try {
                $this->db->beginTransaction();

                // Оновлюємо замовлення користувача (встановлюємо user_id = NULL)
                $stmt = $this->db->prepare("UPDATE orders SET user_id = NULL WHERE user_id = :id");
                $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
                $stmt->execute();

                // Видаляємо користувача
                $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
                $stmt->bindParam(':id', $userId, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    $this->db->commit();
                    echo json_encode(['success' => true, 'message' => 'Користувача успішно видалено']);
                } else {
                    $this->db->rollBack();
                    echo json_encode(['success' => false, 'message' => 'Помилка видалення']);
                }
            } catch (PDOException $e) {
                $this->db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Помилка: ' . $e->getMessage()]);
            }
            return;
        }

        http_response_code(405);
        echo json_encode(['error' => 'Метод не дозволено']);
    }

    public function bulkUpdateUsers() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $userIds = isset($input['user_ids']) ? $input['user_ids'] : [];
            $status = isset($input['status']) ? $input['status'] : '';

            if (empty($userIds) || !in_array($status, ['active', 'inactive', 'banned'])) {
                echo json_encode(['success' => false, 'message' => 'Невірні параметри']);
                return;
            }

            $currentUser = $this->auth->getCurrentUser();

            // Видаляємо поточного користувача зі списку
            $userIds = array_filter($userIds, function($id) use ($currentUser) {
                return (int)$id !== (int)$currentUser['id'];
            });

            if (empty($userIds)) {
                echo json_encode(['success' => false, 'message' => 'Не можна змінити власний статус']);
                return;
            }

            try {
                $placeholders = str_repeat('?,', count($userIds) - 1) . '?';
                $stmt = $this->db->prepare("UPDATE users SET status = ? WHERE id IN ($placeholders)");
                $params = array_merge([$status], $userIds);

                if ($stmt->execute($params)) {
                    echo json_encode(['success' => true, 'message' => 'Статус ' . count($userIds) . ' користувачів оновлено']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Помилка оновлення']);
                }
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Помилка: ' . $e->getMessage()]);
            }
            return;
        }

        http_response_code(405);
        echo json_encode(['error' => 'Метод не дозволено']);
    }

    public function bulkDeleteUsers() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $userIds = isset($input['user_ids']) ? $input['user_ids'] : [];

            if (empty($userIds)) {
                echo json_encode(['success' => false, 'message' => 'Не вибрано користувачів']);
                return;
            }

            $currentUser = $this->auth->getCurrentUser();

            // Видаляємо поточного користувача зі списку
            $userIds = array_filter($userIds, function($id) use ($currentUser) {
                return (int)$id !== (int)$currentUser['id'];
            });

            if (empty($userIds)) {
                echo json_encode(['success' => false, 'message' => 'Не можна видалити власний акаунт']);
                return;
            }

            try {
                $this->db->beginTransaction();

                $placeholders = str_repeat('?,', count($userIds) - 1) . '?';

                // Оновлюємо замовлення (встановлюємо user_id = NULL)
                $stmt = $this->db->prepare("UPDATE orders SET user_id = NULL WHERE user_id IN ($placeholders)");
                $stmt->execute($userIds);

                // Видаляємо користувачів
                $stmt = $this->db->prepare("DELETE FROM users WHERE id IN ($placeholders)");
                $stmt->execute($userIds);

                $this->db->commit();
                echo json_encode(['success' => true, 'message' => count($userIds) . ' користувачів видалено']);
            } catch (PDOException $e) {
                $this->db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Помилка: ' . $e->getMessage()]);
            }
            return;
        }

        http_response_code(405);
        echo json_encode(['error' => 'Метод не дозволено']);
    }
    public function orders() {
        $action = 'orders';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        $status_filter = isset($_GET['status']) ? $_GET['status'] : '';

        // Формуємо запит з фільтрацією
        $whereClause = '';
        $params = [];

        if (!empty($status_filter)) {
            $whereClause = "WHERE o.status = :status";
            $params[':status'] = $status_filter;
        }

        try {
            // Отримуємо замовлення з пагінацією
            $sql = "
            SELECT o.id, o.user_id, o.guest_name, o.guest_email, o.guest_phone, 
                   o.status, o.total_amount, o.created_at, o.notes, o.admin_notes,
                   u.first_name, u.last_name, u.email as user_email, u.phone as user_phone,
                   COUNT(DISTINCT or_rooms.id) as rooms_count
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            LEFT JOIN order_rooms or_rooms ON o.id = or_rooms.order_id
            $whereClause
            GROUP BY o.id, o.user_id, o.guest_name, o.guest_email, o.guest_phone, 
                     o.status, o.total_amount, o.created_at, o.notes, o.admin_notes,
                     u.first_name, u.last_name, u.email, u.phone
            ORDER BY o.created_at DESC 
            LIMIT :limit OFFSET :offset
        ";

            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            
            error_log("Executing orders query with params: " . json_encode($params));
            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Found " . count($orders) . " orders");

            // Підраховуємо загальну кількість замовлень
            $countSql = "SELECT COUNT(DISTINCT o.id) FROM orders o $whereClause";
            $stmt = $this->db->prepare($countSql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $totalOrders = $stmt->fetchColumn();
            $totalPages = ceil($totalOrders / $limit);

            // Якщо немає замовлень, створюємо пустий масив
            if (empty($orders)) {
                $orders = [];
            }

        } catch (PDOException $e) {
            error_log("Error in orders method: " . $e->getMessage());
            $orders = [];
            $totalOrders = 0;
            $totalPages = 1;
        }

        include __DIR__ . '/../Views/admin/orders.php';
    }
    public function getOrder() {
        $orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if (!$orderId) {
            echo json_encode(['success' => false, 'message' => 'Невірний ID замовлення']);
            return;
        }

        try {
            // Отримуємо основні дані замовлення
            $stmt = $this->db->prepare("
            SELECT o.*, u.first_name, u.last_name, u.email as user_email, u.phone as user_phone
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            WHERE o.id = :id
        ");
            $stmt->bindParam(':id', $orderId, PDO::PARAM_INT);
            $stmt->execute();
            $order = $stmt->fetch();

            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Замовлення не знайдено']);
                return;
            }

            // Отримуємо кімнати замовлення з розрахунком вартості
            $stmt = $this->db->prepare("
            SELECT or_rooms.*, rt.name as room_type_name,
                   (SELECT COALESCE(SUM(ors.total_price), 0) 
                    FROM order_room_services ors 
                    WHERE ors.order_room_id = or_rooms.id AND ors.is_selected = 1) as total_amount
            FROM order_rooms or_rooms
            LEFT JOIN room_types rt ON or_rooms.room_type_id = rt.id
            WHERE or_rooms.order_id = :order_id
            ORDER BY or_rooms.id
        ");
            $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
            $stmt->execute();
            $rooms = $stmt->fetchAll();

            // Для кожної кімнати отримуємо послуги
            foreach ($rooms as &$room) {
                $stmt = $this->db->prepare("
                SELECT ors.*, s.name as service_name, s.unit, s.price_per_sqm as price
                FROM order_room_services ors
                LEFT JOIN services s ON ors.service_id = s.id
                WHERE ors.order_room_id = :room_id AND ors.is_selected = 1
                ORDER BY s.name
            ");
                $stmt->bindParam(':room_id', $room['id'], PDO::PARAM_INT);
                $stmt->execute();
                $services = $stmt->fetchAll();
                $room['services'] = $services;
            }

            $html = $this->generateOrderDetailsHtml($order, $rooms);
            echo json_encode(['success' => true, 'html' => $html]);

        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Помилка: ' . $e->getMessage()]);
        }
    }

    public function updateOrder() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
            $status = isset($_POST['status']) ? $_POST['status'] : '';
            $adminNotes = isset($_POST['admin_notes']) ? trim($_POST['admin_notes']) : '';

            if (!in_array($status, ['draft', 'new', 'in_progress', 'completed'])) {
                echo json_encode(['success' => false, 'message' => 'Невірний статус']);
                return;
            }

            try {
                $stmt = $this->db->prepare("
                    UPDATE orders 
                    SET status = :status, admin_notes = :admin_notes, updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id
                ");
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':admin_notes', $adminNotes);
                $stmt->bindParam(':id', $orderId, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Замовлення успішно оновлено']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Помилка оновлення']);
                }
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Помилка: ' . $e->getMessage()]);
            }
            return;
        }

        http_response_code(405);
        echo json_encode(['error' => 'Метод не дозволено']);
    }

    public function deleteOrder() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;

            if (!$orderId) {
                echo json_encode(['success' => false, 'message' => 'Невірний ID замовлення']);
                return;
            }

            try {
                $this->db->beginTransaction();

                // Видаляємо послуги кімнат
                $stmt = $this->db->prepare("
                    DELETE FROM order_room_services 
                    WHERE order_room_id IN (SELECT id FROM order_rooms WHERE order_id = ?)
                ");
                $stmt->execute([$orderId]);

                // Видаляємо кімнати
                $stmt = $this->db->prepare("DELETE FROM order_rooms WHERE order_id = ?");
                $stmt->execute([$orderId]);

                // Видаляємо замовлення
                $stmt = $this->db->prepare("DELETE FROM orders WHERE id = ?");
                $stmt->execute([$orderId]);

                $this->db->commit();
                echo json_encode(['success' => true, 'message' => 'Замовлення успішно видалено']);
            } catch (PDOException $e) {
                $this->db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Помилка: ' . $e->getMessage()]);
            }
            return;
        }

        http_response_code(405);
        echo json_encode(['error' => 'Метод не дозволено']);
    }

    public function exportUsers() {
        try {
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';

            $whereClause = '';
            $params = [];

            if (!empty($search)) {
                $whereClause = "WHERE first_name LIKE :search OR last_name LIKE :search OR email LIKE :search";
                $params[':search'] = '%' . $search . '%';
            }

            $sql = "
                SELECT id, first_name, last_name, email, phone, is_admin, status, created_at 
                FROM users 
                $whereClause
                ORDER BY created_at DESC
            ";

            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $users = $stmt->fetchAll();

            // Встановлюємо заголовки для завантаження CSV
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=users_export_' . date('Y-m-d_H-i-s') . '.csv');

            $output = fopen('php://output', 'w');

            // BOM для правильного відображення UTF-8 в Excel
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            // Заголовки CSV
            fputcsv($output, [
                'ID',
                'Ім\'я',
                'Прізвище',
                'Email',
                'Телефон',
                'Роль',
                'Статус',
                'Дата реєстрації'
            ]);

            // Дані користувачів
            foreach ($users as $user) {
                fputcsv($output, [
                    $user['id'],
                    $user['first_name'],
                    $user['last_name'],
                    $user['email'],
                    $user['phone'] ?: '',
                    $user['is_admin'] ? 'Адміністратор' : 'Користувач',
                    $this->getStatusLabel($user['status']),
                    date('d.m.Y H:i', strtotime($user['created_at']))
                ]);
            }

            fclose($output);
            exit;

        } catch (PDOException $e) {
            error_log("Error in exportUsers: " . $e->getMessage());
            http_response_code(500);
            echo "Помилка експорту даних";
        }
    }

    public function dashboard() {
        $action = 'dashboard';
        $stats = $this->getDashboardStats();
        $recentUsers = $this->getRecentUsers();
        $recentOrders = $this->getRecentOrders();

        include __DIR__ . '/../Views/admin/adminpanel.php';
    }

    public function statistics() {
        $action = 'statistics';
        $monthlyStats = $this->getMonthlyStats();
        $ordersByStatus = $this->getOrdersByStatus();
        $topServices = $this->getTopServices();
        $avgOrderAmount = $this->getAverageOrderAmount();

        include __DIR__ . '/../Views/admin/statistics.php';
    }

    private function getDashboardStats() {
        $stats = [];

        // Загальна кількість користувачів
        $stmt = $this->db->query("SELECT COUNT(*) FROM users");
        $stats['total_users'] = $stmt->fetchColumn();

        // Кількість адміністраторів
        $stmt = $this->db->query("SELECT COUNT(*) FROM users WHERE is_admin = 1");
        $stats['total_admins'] = $stmt->fetchColumn();

        // Активні користувачі
        $stmt = $this->db->query("SELECT COUNT(*) FROM users WHERE status = 'active'");
        $stats['active_users'] = $stmt->fetchColumn();

        // Нові користувачі за тиждень
        $stmt = $this->db->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)");
        $stats['new_users_week'] = $stmt->fetchColumn();

        // Загальна кількість замовлень
        $stmt = $this->db->query("SELECT COUNT(*) FROM orders");
        $stats['total_orders'] = $stmt->fetchColumn();

        // Нові замовлення
        $stmt = $this->db->query("SELECT COUNT(*) FROM orders WHERE status = 'new'");
        $stats['new_orders'] = $stmt->fetchColumn();

        return $stats;
    }

    private function getRecentUsers($limit = 5) {
        $stmt = $this->db->prepare("
            SELECT id, first_name, last_name, email, is_admin, status, created_at 
            FROM users 
            ORDER BY created_at DESC 
            LIMIT :limit
        ");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function getRecentOrders($limit = 5) {
        $stmt = $this->db->prepare("
            SELECT o.id, o.user_id, o.guest_name, o.guest_email, o.status, 
                   o.total_amount, o.created_at,
                   u.first_name, u.last_name
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            ORDER BY o.created_at DESC 
            LIMIT :limit
        ");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function getMonthlyStats() {
        $stmt = $this->db->query("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as orders_count,
                SUM(total_amount) as total_amount
            FROM orders 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ");
        return $stmt->fetchAll();
    }

    private function getOrdersByStatus() {
        $stmt = $this->db->query("
            SELECT status, COUNT(*) as count 
            FROM orders 
            GROUP BY status
        ");
        return $stmt->fetchAll();
    }

    private function getTopServices() {
        $stmt = $this->db->query("
            SELECT s.name, COUNT(*) as usage_count, SUM(ors.total_price) as total_revenue
            FROM order_room_services ors
            LEFT JOIN services s ON ors.service_id = s.id
            WHERE ors.is_selected = 1
            GROUP BY s.id, s.name
            ORDER BY usage_count DESC
            LIMIT 10
        ");
        return $stmt->fetchAll();
    }

    private function getAverageOrderAmount() {
        $stmt = $this->db->query("SELECT AVG(total_amount) FROM orders WHERE total_amount > 0");
        return $stmt->fetchColumn();
    }

    private function getStatusLabel($status) {
        $labels = [
            'active' => 'Активний',
            'inactive' => 'Неактивний',
            'banned' => 'Заблокований'
        ];
        return $labels[$status] ?? $status;
    }
    private function generateOrderDetailsHtml($order, $rooms) {
        $html = '<div class="order-details-content">';

        // Інформація про замовлення
        $html .= '<div class="order-header">';
        $html .= '<h3>Замовлення #' . str_pad($order['id'], 4, '0', STR_PAD_LEFT) . '</h3>';
        $html .= '<div class="order-status">';
        $html .= '<span class="badge status-' . $order['status'] . '">';

        $statusLabels = [
            'draft' => 'Чернетка',
            'new' => 'Новий',
            'in_progress' => 'В роботі',
            'completed' => 'Завершено'
        ];
        $html .= $statusLabels[$order['status']] ?? $order['status'];
        $html .= '</span>';
        $html .= '</div>';
        $html .= '</div>';

        // Інформація про клієнта
        $html .= '<div class="order-section">';
        $html .= '<h4><i class="fas fa-user"></i> Інформація про клієнта</h4>';
        if ($order['user_id']) {
            $html .= '<div class="info-row">';
            $html .= '<span>Клієнт:</span>';
            $html .= '<span>' . htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) . '</span>';
            $html .= '</div>';
            $html .= '<div class="info-row">';
            $html .= '<span>Email:</span>';
            $html .= '<span>' . htmlspecialchars($order['user_email']) . '</span>';
            $html .= '</div>';
            $html .= '<div class="info-row">';
            $html .= '<span>Телефон:</span>';
            $html .= '<span>' . htmlspecialchars($order['user_phone']) . '</span>';
            $html .= '</div>';
        } else {
            $html .= '<div class="info-row">';
            $html .= '<span>Ім\'я:</span>';
            $html .= '<span>' . htmlspecialchars($order['guest_name'] ?? 'Не вказано') . '</span>';
            $html .= '</div>';
            $html .= '<div class="info-row">';
            $html .= '<span>Email:</span>';
            $html .= '<span>' . htmlspecialchars($order['guest_email'] ?? 'Не вказано') . '</span>';
            $html .= '</div>';
            $html .= '<div class="info-row">';
            $html .= '<span>Телефон:</span>';
            $html .= '<span>' . htmlspecialchars($order['guest_phone'] ?? 'Не вказано') . '</span>';
            $html .= '</div>';
        }
        $html .= '</div>';

        // Кімнати та послуги
        $html .= '<div class="order-section">';
        $html .= '<h4><i class="fas fa-home"></i> Кімнати та послуги</h4>';
        
        foreach ($rooms as $room) {
            $html .= '<div class="room-block">';
            $html .= '<div class="room-header">';
            $html .= '<h5>' . htmlspecialchars($room['room_name']) . ' (' . htmlspecialchars($room['room_type_name']) . ')</h5>';
            $html .= '<div class="room-areas">';
            $html .= '<span>Площа стін: ' . number_format($room['wall_area'], 2) . ' м²</span>';
            $html .= '<span>Площа підлоги: ' . number_format($room['floor_area'], 2) . ' м²</span>';
            $html .= '</div>';
            $html .= '</div>';

            if (!empty($room['services'])) {
                $html .= '<div class="services-table">';
                $html .= '<table class="table">';
                $html .= '<thead><tr>';
                $html .= '<th>Послуга</th>';
                $html .= '<th>Кількість</th>';
                $html .= '<th>Ціна за од.</th>';
                $html .= '<th>Сума</th>';
                $html .= '</tr></thead>';
                $html .= '<tbody>';
                
                foreach ($room['services'] as $service) {
                    $html .= '<tr>';
                    $html .= '<td>' . htmlspecialchars($service['service_name']) . '</td>';
                    $html .= '<td>' . number_format($service['quantity'], 2) . ' ' . htmlspecialchars($service['unit']) . '</td>';
                    $html .= '<td>' . number_format($service['unit_price'], 2) . ' грн</td>';
                    $html .= '<td>' . number_format($service['total_price'], 2) . ' грн</td>';
                    $html .= '</tr>';
                }
                
                $html .= '</tbody>';
                $html .= '</table>';
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }

        // Загальна інформація
        $html .= '<div class="order-summary">';
        $html .= '<h4><i class="fas fa-calculator"></i> Підсумок</h4>';
        $html .= '<div class="summary-row">';
        $html .= '<span><strong>Загальна сума:</strong></span>';
        $html .= '<span><strong>' . number_format($order['total_amount'] ?? 0, 2) . ' грн</strong></span>';
        $html .= '</div>';
        $html .= '<div class="summary-row">';
        $html .= '<span>Дата створення:</span>';
        $html .= '<span>' . date('d.m.Y H:i', strtotime($order['created_at'])) . '</span>';
        $html .= '</div>';
        if (!empty($order['notes'])) {
            $html .= '<div class="summary-row">';
            $html .= '<span>Нотатки клієнта:</span>';
            $html .= '<span>' . nl2br(htmlspecialchars($order['notes'])) . '</span>';
            $html .= '</div>';
        }
        if (!empty($order['admin_notes'])) {
            $html .= '<div class="summary-row">';
            $html .= '<span>Нотатки адміністратора:</span>';
            $html .= '<span>' . nl2br(htmlspecialchars($order['admin_notes'])) . '</span>';
            $html .= '</div>';
        }
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

// Додати методи для масових операцій, які викликаються з JS
    public function bulkUpdateStatus() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $orderIds = json_decode($_POST['order_ids'] ?? '[]', true);
            $status = $_POST['status'] ?? '';

            if (empty($orderIds) || !in_array($status, ['draft', 'new', 'in_progress', 'completed'])) {
                echo json_encode(['success' => false, 'message' => 'Невірні параметри']);
                return;
            }

            try {
                $placeholders = str_repeat('?,', count($orderIds) - 1) . '?';
                $sql = "UPDATE orders SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id IN ($placeholders)";

                $params = array_merge([$status], $orderIds);
                $stmt = $this->db->prepare($sql);

                if ($stmt->execute($params)) {
                    echo json_encode(['success' => true, 'message' => 'Статус замовлень успішно оновлено']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Помилка оновлення']);
                }
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Помилка: ' . $e->getMessage()]);
            }
            return;
        }

        http_response_code(405);
        echo json_encode(['error' => 'Метод не дозволено']);
    }

    public function bulkDelete() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $orderIds = json_decode($_POST['order_ids'] ?? '[]', true);

            if (empty($orderIds)) {
                echo json_encode(['success' => false, 'message' => 'Не вибрано замовлень']);
                return;
            }

            try {
                $this->db->beginTransaction();

                $placeholders = str_repeat('?,', count($orderIds) - 1) . '?';

                // Видаляємо послуги кімнат
                $sql = "DELETE FROM order_room_services WHERE order_room_id IN (
                        SELECT id FROM order_rooms WHERE order_id IN ($placeholders)
                    )";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($orderIds);

                // Видаляємо кімнати
                $sql = "DELETE FROM order_rooms WHERE order_id IN ($placeholders)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($orderIds);

                // Видаляємо замовлення
                $sql = "DELETE FROM orders WHERE id IN ($placeholders)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($orderIds);

                $this->db->commit();
                echo json_encode(['success' => true, 'message' => 'Замовлення успішно видалено']);
            } catch (PDOException $e) {
                $this->db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Помилка: ' . $e->getMessage()]);
            }
            return;
        }

        http_response_code(405);
        echo json_encode(['error' => 'Метод не дозволено']);
    }
}