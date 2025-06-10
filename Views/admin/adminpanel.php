<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Адміністраторська панель - BuildMaster</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/BuildMaster/UI/css/admin.css">
</head>
<body>
<a href="/BuildMaster/" class="btn btn-primary back-to-site">
    <i class="fas fa-arrow-left"></i>
    Повернутися на сайт
</a>

<header class="admin-header">
    <div class="container">
        <h1>
            <i class="fas fa-cogs"></i>
            Адміністраторська панель BuildMaster
        </h1>
    </div>
</header>

<nav class="admin-nav">
    <div class="container">
        <ul>
            <li><a href="?action=dashboard" class="<?= $action === 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    Головна
                </a></li>
            <li><a href="?action=users" class="<?= $action === 'users' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i>
                    Користувачі
                </a></li>
            <li><a href="?action=orders" class="<?= $action === 'orders' ? 'active' : '' ?>">
                    <i class="fas fa-shopping-cart"></i>
                    Замовлення
                </a></li>
            <li><a href="?action=create" class="<?= $action === 'create' ? 'active' : '' ?>">
                    <i class="fas fa-wrench"></i>
                    Послуги
                </a></li>
        </ul>
    </div>
</nav>

<div class="container">
    <!-- Статистичні картки -->
    <div class="stats-grid">
        <div class="stat-card users">
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
            <h3><?= $stats['total_users'] ?></h3>
            <p>Всього користувачів</p>
        </div>

        <div class="stat-card admins">
            <div class="icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <h3><?= $stats['total_admins'] ?></h3>
            <p>Адміністраторів</p>
        </div>

        <div class="stat-card active">
            <div class="icon">
                <i class="fas fa-user-check"></i>
            </div>
            <h3><?= $stats['active_users'] ?></h3>
            <p>Активних користувачів</p>
        </div>

        <div class="stat-card new">
            <div class="icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <h3><?= $stats['new_users_week'] ?></h3>
            <p>Нових за тиждень</p>
        </div>
    </div>

    <!-- Контент -->
    <div class="content-grid">
        <!-- Останні користувачі -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-users"></i>
                Останні користувачі
                <a href="?action=users" class="btn btn-sm btn-primary">Всі користувачі</a>
            </div>
            <div class="card-body">
                <?php if (!empty($recentUsers)): ?>
                    <?php foreach ($recentUsers as $user): ?>
                        <div class="order-item">
                            <div class="user-info">
                                <h4><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h4>
                                <div class="order-details">
                                    Email: <?= htmlspecialchars($user['email']) ?><br>
                                    Реєстрація: <?= date('d.m.Y H:i', strtotime($user['created_at'])) ?>
                                </div>
                            </div>
                            <div class="action-buttons">
                                <span class="badge <?= $user['is_admin'] ? 'admin' : 'user' ?>">
                                    <?= $user['is_admin'] ? 'Адмін' : 'Користувач' ?>
                                </span>
                                <span class="badge <?= $user['status'] ?>">
                                    <?php
                                    switch ($user['status']) {
                                        case 'active':
                                            echo 'Активний';
                                            break;
                                        case 'inactive':
                                            echo 'Неактивний';
                                            break;
                                        case 'banned':
                                            echo 'Заблокований';
                                            break;
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Немає користувачів</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Останні замовлення -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-shopping-cart"></i>
                Останні замовлення
                <a href="?action=orders" class="btn btn-sm btn-primary">Всі замовлення</a>
            </div>
            <div class="card-body">
                <?php if (!empty($recentOrders)): ?>
                    <?php foreach ($recentOrders as $order): ?>
                        <div class="order-item">
                            <div class="user-info">
                                <h4>Замовлення #<?= str_pad($order['id'], 3, '0', STR_PAD_LEFT) ?></h4>
                                <div class="order-details">
                                    <?php if ($order['user_id']): ?>
                                        Клієнт: <?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?><br>
                                    <?php else: ?>
                                        Гість: <?= htmlspecialchars($order['guest_name']) ?><br>
                                    <?php endif; ?>
                                    Сума: <?= number_format($order['total_amount'], 2) ?> грн<br>
                                    Дата: <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?>
                                </div>
                            </div>
                            <div class="action-buttons">
                                <span class="badge <?= $order['status'] ?>">
                                    <?php
                                    switch ($order['status']) {
                                        case 'draft':
                                            echo 'Чернетка';
                                            break;
                                        case 'new':
                                            echo 'Новий';
                                            break;
                                        case 'in_progress':
                                            echo 'В роботі';
                                            break;
                                        case 'completed':
                                            echo 'Завершено';
                                            break;
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Немає замовлень</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Модальне вікно для перегляду замовлення -->
<div id="orderModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Деталі замовлення</h2>
        <div id="orderDetails"></div>
    </div>
</div>

<script src="/BuildMaster/UI/js/admin.js"></script>
</body>
</html>