<?php
// Controllers/UserOrdersController.php

class UserOrdersController {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function index() {
        // Перевіряємо чи користувач залогінений
        if (!isset($_SESSION['user']) || !$_SESSION['user']['authenticated']) {
            header('Location: /BuildMaster/');
            exit();
        }

        $userId = (int)$_SESSION['user']['id'];
        $userEmail = $_SESSION['user']['email'];

        // Детальна відладка
        error_log("UserOrdersController: User ID from session: " . $userId . " (type: " . gettype($userId) . ")");
        error_log("UserOrdersController: User email from session: " . $userEmail);

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        $status_filter = isset($_GET['status']) ? $_GET['status'] : '';

        try {
            // Перевіримо користувача в базі
            $userCheckSql = "SELECT id, email, first_name, last_name FROM users WHERE id = :user_id";
            $userCheckStmt = $this->db->prepare($userCheckSql);
            $userCheckStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $userCheckStmt->execute();
            $userFromDb = $userCheckStmt->fetch(PDO::FETCH_ASSOC);

            if (!$userFromDb) {
                error_log("UserOrdersController: User with ID $userId not found in database!");
                session_destroy();
                header('Location: /BuildMaster/');
                exit();
            }

            error_log("UserOrdersController: User found in DB: " . json_encode($userFromDb));

            // ВИПРАВЛЕНО: Спрощений запит для перевірки замовлень
            $debugSql = "SELECT COUNT(*) as total FROM orders WHERE user_id = :user_id OR (guest_email = :user_email AND guest_email IS NOT NULL AND guest_email != '')";
            $debugStmt = $this->db->prepare($debugSql);
            $debugStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $debugStmt->bindValue(':user_email', $userEmail, PDO::PARAM_STR);
            $debugStmt->execute();
            $debugResult = $debugStmt->fetch(PDO::FETCH_ASSOC);
            error_log("UserOrdersController: Total orders for user $userId or email $userEmail: " . $debugResult['total']);

            // ДОДАНО: Перевіримо структуру таблиці orders
            $tableStructureSql = "DESCRIBE orders";
            $tableStmt = $this->db->prepare($tableStructureSql);
            $tableStmt->execute();
            $tableStructure = $tableStmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("UserOrdersController: Orders table structure: " . json_encode($tableStructure));

            // ДОДАНО: Перевіримо всі замовлення користувача
            $allOrdersSql = "SELECT id, user_id, guest_email, status, total_amount, created_at FROM orders WHERE user_id = :user_id OR (guest_email = :user_email AND guest_email IS NOT NULL AND guest_email != '') ORDER BY created_at DESC LIMIT 10";
            $allOrdersStmt = $this->db->prepare($allOrdersSql);
            $allOrdersStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $allOrdersStmt->bindValue(':user_email', $userEmail, PDO::PARAM_STR);
            $allOrdersStmt->execute();
            $allOrders = $allOrdersStmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("UserOrdersController: User's orders: " . json_encode($allOrders));

            // ВИПРАВЛЕНО: Формуємо WHERE clause з правильною логікою
            $whereClause = 'WHERE (o.user_id = :user_id OR (o.guest_email = :user_email AND o.guest_email IS NOT NULL AND o.guest_email != \'\'))';
            $params = [
                ':user_id' => $userId,
                ':user_email' => $userEmail
            ];

            if (!empty($status_filter)) {
                $whereClause .= " AND o.status = :status";
                $params[':status'] = $status_filter;
            }

            // ВИПРАВЛЕНО: Оновлений SQL запит
            $sql = "
                SELECT 
                    o.id, 
                    o.status, 
                    COALESCE(o.total_amount, 0) as total_amount, 
                    o.created_at, 
                    o.notes, 
                    o.guest_name,
                    COUNT(DISTINCT or_rooms.id) as rooms_count
                FROM orders o 
                LEFT JOIN order_rooms or_rooms ON o.id = or_rooms.order_id
                $whereClause
                GROUP BY o.id, o.status, o.total_amount, o.created_at, o.notes, o.guest_name
                ORDER BY o.created_at DESC 
                LIMIT :limit OFFSET :offset
            ";

            error_log("UserOrdersController: Final SQL Query: " . $sql);
            error_log("UserOrdersController: Query params: " . json_encode($params));

            $stmt = $this->db->prepare($sql);

            // Прив'язуємо параметри
            foreach ($params as $key => $value) {
                if ($key === ':user_id') {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value, PDO::PARAM_STR);
                }
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            error_log("UserOrdersController: Found orders: " . count($orders));
            error_log("UserOrdersController: Orders data: " . json_encode($orders));

            // Підраховуємо загальну кількість замовлень
            $countSql = "SELECT COUNT(*) FROM orders o $whereClause";
            $countStmt = $this->db->prepare($countSql);

            foreach ($params as $key => $value) {
                if ($key === ':user_id') {
                    $countStmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $countStmt->bindValue($key, $value, PDO::PARAM_STR);
                }
            }
            $countStmt->execute();
            $totalOrders = $countStmt->fetchColumn();
            $totalPages = ceil($totalOrders / $limit);

            error_log("UserOrdersController: Total orders count: " . $totalOrders);

            // Статистика для користувача
            $statsQuery = "
                SELECT 
                    COUNT(*) as total_orders,
                    COUNT(CASE WHEN status = 'new' THEN 1 END) as new_orders,
                    COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_orders,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
                    COALESCE(SUM(total_amount), 0) as total_spent
                FROM orders 
                WHERE user_id = :user_id OR (guest_email = :user_email AND guest_email IS NOT NULL AND guest_email != '')
            ";

            $statsStmt = $this->db->prepare($statsQuery);
            $statsStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $statsStmt->bindValue(':user_email', $userEmail, PDO::PARAM_STR);
            $statsStmt->execute();
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

            error_log("UserOrdersController: Stats: " . json_encode($stats));

        } catch (PDOException $e) {
            error_log("Error in user orders: " . $e->getMessage());
            error_log("Error details: " . $e->getTraceAsString());

            // ВИПРАВЛЕНО: Забезпечуємо що змінні завжди ініціалізовані
            $orders = [];
            $totalOrders = 0;
            $totalPages = 1;
            $stats = [
                'total_orders' => 0,
                'new_orders' => 0,
                'in_progress_orders' => 0,
                'completed_orders' => 0,
                'total_spent' => 0
            ];
        }

        // ВИПРАВЛЕНО: Гарантуємо що всі змінні встановлені
        if (!isset($orders)) $orders = [];
        if (!isset($stats)) {
            $stats = [
                'total_orders' => 0,
                'new_orders' => 0,
                'in_progress_orders' => 0,
                'completed_orders' => 0,
                'total_spent' => 0
            ];
        }
        if (!isset($totalOrders)) $totalOrders = 0;
        if (!isset($totalPages)) $totalPages = 1;

        // Підготовка даних для представлення
        $data = [
            'orders' => $orders,
            'stats' => $stats,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalOrders' => $totalOrders,
            'status_filter' => $status_filter,
            'user' => $_SESSION['user']
        ];

        // ДОДАНО: Логуємо фінальні дані
        error_log("UserOrdersController: Final data for view: " . json_encode([
                'orders_count' => count($data['orders']),
                'stats' => $data['stats'],
                'totalOrders' => $data['totalOrders']
            ]));

        // ВИПРАВЛЕНО: Перевіряємо чи файл існує
        $viewPath = __DIR__ . '/../Views/home/user-orders.php';
        if (!file_exists($viewPath)) {
            error_log("UserOrdersController: View file not found at: " . $viewPath);
            echo "Помилка: файл представлення не знайдено";
            return;
        }

        // Використовуємо буферизацію для генерації HTML
        ob_start();
        include $viewPath;
        $output = ob_get_clean();

        echo $output;
    }

    public function getOrderDetails() {
        if (!isset($_SESSION['user']) || !$_SESSION['user']['authenticated']) {
            echo json_encode(['success' => false, 'message' => 'Не авторизований']);
            return;
        }

        $orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $userId = (int)$_SESSION['user']['id'];
        $userEmail = $_SESSION['user']['email'];

        if (!$orderId) {
            echo json_encode(['success' => false, 'message' => 'Невірний ID замовлення']);
            return;
        }

        try {
            // ВИПРАВЛЕНО: Перевіряємо що замовлення належить користувачу
            $stmt = $this->db->prepare("
                SELECT o.*, u.first_name, u.last_name, u.email as user_email, u.phone as user_phone
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                WHERE o.id = :id AND (o.user_id = :user_id OR (o.guest_email = :user_email AND o.guest_email IS NOT NULL AND o.guest_email != ''))
            ");
            $stmt->bindParam(':id', $orderId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':user_email', $userEmail, PDO::PARAM_STR);
            $stmt->execute();
            $order = $stmt->fetch();

            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Замовлення не знайдено']);
                return;
            }

            // Отримуємо кімнати замовлення
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

            ob_start();
            $this->renderOrderDetails($order, $rooms);
            $html = ob_get_clean();

            echo json_encode(['success' => true, 'html' => $html]);

        } catch (PDOException $e) {
            error_log("Error in getOrderDetails: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Помилка: ' . $e->getMessage()]);
        }
    }

    private function renderOrderDetails($order, $rooms) {
        ?>
        <div class="order-details-content">
            <!-- Інформація про замовлення -->
            <div class="order-header">
                <h3>Замовлення #<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?></h3>
                <div class="order-status">
                    <span class="badge status-<?= $order['status'] ?>">
                        <?php
                        $statusLabels = [
                            'draft' => 'Чернетка',
                            'new' => 'Новий',
                            'in_progress' => 'В роботі',
                            'completed' => 'Завершено'
                        ];
                        echo $statusLabels[$order['status']] ?? $order['status'];
                        ?>
                    </span>
                </div>
            </div>

            <!-- Кімнати та послуги -->
            <div class="order-section">
                <h4><i class="fas fa-home"></i> Кімнати та послуги</h4>

                <?php foreach ($rooms as $room): ?>
                    <div class="room-block">
                        <div class="room-header">
                            <h5><?= htmlspecialchars($room['room_name']) ?> (<?= htmlspecialchars($room['room_type_name']) ?>)</h5>
                            <div class="room-areas">
                                <span>Площа стін: <?= number_format($room['wall_area'], 2) ?> м²</span>
                                <span>Площа підлоги: <?= number_format($room['floor_area'], 2) ?> м²</span>
                            </div>
                        </div>

                        <?php if (!empty($room['services'])): ?>
                            <div class="services-table">
                                <table class="table">
                                    <thead>
                                    <tr>
                                        <th>Послуга</th>
                                        <th>Кількість</th>
                                        <th>Ціна за од.</th>
                                        <th>Сума</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($room['services'] as $service): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($service['service_name']) ?></td>
                                            <td><?= number_format($service['quantity'], 2) ?> <?= htmlspecialchars($service['unit']) ?></td>
                                            <td><?= number_format($service['unit_price'], 2) ?> грн</td>
                                            <td><?= number_format($service['total_price'], 2) ?> грн</td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Загальна інформація -->
            <div class="order-summary">
                <h4><i class="fas fa-calculator"></i> Підсумок</h4>
                <div class="summary-row">
                    <span><strong>Загальна сума:</strong></span>
                    <span><strong><?= number_format($order['total_amount'] ?? 0, 2) ?> грн</strong></span>
                </div>
                <div class="summary-row">
                    <span>Дата створення:</span>
                    <span><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></span>
                </div>
                <?php if (!empty($order['notes'])): ?>
                    <div class="summary-row">
                        <span>Мої нотатки:</span>
                        <span><?= nl2br(htmlspecialchars($order['notes'])) ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($order['admin_notes'])): ?>
                    <div class="summary-row">
                        <span>Коментар менеджера:</span>
                        <span><?= nl2br(htmlspecialchars($order['admin_notes'])) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}