<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управління замовленнями - BuildMaster</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/BuildMaster/UI/css/admin.css">
    <link rel="stylesheet" href="/BuildMaster/UI/css/order.css">

</head>
<body>
<a href="/BuildMaster/" class="btn btn-primary back-to-site">
    <i class="fas fa-arrow-left"></i>
    Повернутися на сайт
</a>

<header class="admin-header">
    <div class="container">
        <h1>
            <i class="fas fa-shopping-cart"></i>
            Управління замовленнями
        </h1>
    </div>
</header>

<nav class="admin-nav">
    <div class="container">
        <ul>
            <li><a href="?action=dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    Головна
                </a></li>
            <li><a href="?action=users">
                    <i class="fas fa-users"></i>
                    Користувачі
                </a></li>
            <li><a href="?action=orders" class="active">
                    <i class="fas fa-shopping-cart"></i>
                    Замовлення
                </a></li>
            <li><a href="?action=services" >
                    <i class="fas fa-wrench"></i>
                    Послуги
                </a></li>
        </ul>
    </div>
</nav>

<div class="container">
    <!-- Панель фільтрів -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-filter"></i>
            Фільтри замовлень
        </div>
        <div class="card-body">
            <div class="search-form">
                <div class="search-group">
                    <select id="statusFilter" onchange="filterOrders(this.value)" class="filter-select">
                        <option value="">Всі статуси</option>
                        <option value="draft" <?= isset($status_filter) && $status_filter === 'draft' ? 'selected' : '' ?>>Чернетка</option>
                        <option value="new" <?= isset($status_filter) && $status_filter === 'new' ? 'selected' : '' ?>>Новий</option>
                        <option value="in_progress" <?= isset($status_filter) && $status_filter === 'in_progress' ? 'selected' : '' ?>>В роботі</option>
                        <option value="completed" <?= isset($status_filter) && $status_filter === 'completed' ? 'selected' : '' ?>>Завершено</option>
                    </select>
                    <?php if (isset($status_filter) && !empty($status_filter)): ?>
                        <a href="?action=orders" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Очистити
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Таблиця замовлень -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-shopping-cart"></i>
            Замовлення (<?= isset($totalOrders) ? $totalOrders : count($orders ?? []) ?> всього)
            <div class="header-actions">

            </div>
        </div>
        <div class="card-body">
            <?php if (!empty($orders)): ?>
                <div class="table-responsive">
                    <table class="admin-table" id="ordersTable">
                        <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th onclick="sortTable(1, 'ordersTable')" style="cursor: pointer;">ID</th>
                            <th onclick="sortTable(2, 'ordersTable')" style="cursor: pointer;">Клієнт</th>
                            <th onclick="sortTable(3, 'ordersTable')" style="cursor: pointer;">Статус</th>
                            <th onclick="sortTable(4, 'ordersTable')" style="cursor: pointer;">Кімнат</th>
                            <th onclick="sortTable(5, 'ordersTable')" style="cursor: pointer;">Сума</th>
                            <th onclick="sortTable(6, 'ordersTable')" style="cursor: pointer;">Дата створення</th>
                            <th>Дії</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr data-order-id="<?= $order['id'] ?>">
                                <td>
                                    <input type="checkbox" class="row-checkbox" value="<?= $order['id'] ?>">
                                </td>
                                <td>#<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?></td>
                                <td>
                                    <?php if (isset($order['user_id']) && $order['user_id']): ?>
                                        <div class="client-info">
                                            <strong><?= htmlspecialchars(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? '')) ?></strong>
                                            <div class="client-email"><?= htmlspecialchars($order['user_email'] ?? '') ?></div>

                                        </div>
                                    <?php else: ?>
                                        <div class="client-info">
                                            <strong><?= htmlspecialchars($order['guest_name'] ?? 'Невідомий') ?></strong>
                                            <div class="client-email"><?= htmlspecialchars($order['guest_email'] ?? '') ?></div>
                                            <div class="client-type">
                                                <i class="fas fa-user"></i> Гість
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
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
                                </td>
                                <td>
                                    <span class="rooms-count">
                                        <i class="fas fa-home"></i> <?= $order['rooms_count'] ?? 0 ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="amount">
                                        <?= number_format($order['total_amount'] ?? 0, 2) ?> грн
                                    </span>
                                </td>
                                <td>
                                    <span class="date">
                                        <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-sm btn-primary"
                                                onclick="viewOrder(<?= $order['id'] ?>)"
                                                title="Переглянути деталі">
                                            <i class="fas fa-eye"></i>
                                        </button>

                                        <button class="btn btn-sm btn-warning"
                                                onclick="editOrder(<?= $order['id'] ?>)"
                                                title="Редагувати">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger"
                                                onclick="deleteOrder(<?= $order['id'] ?>, '#<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?>')"
                                                title="Видалити">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Пагінація -->
                <?php if (isset($totalPages) && $totalPages > 1): ?>
                    <div class="pagination-wrapper">
                        <div class="pagination">
                            <?php
                            $queryParams = $_GET;
                            $currentPage = $page ?? 1;

                            // Попередня сторінка
                            if ($currentPage > 1):
                                $queryParams['page'] = $currentPage - 1;
                                $prevUrl = '?' . http_build_query($queryParams);
                                ?>
                                <a href="<?= $prevUrl ?>" class="pagination-btn">
                                    <i class="fas fa-chevron-left"></i>
                                    Попередня
                                </a>
                            <?php endif; ?>

                            <!-- Номери сторінок -->
                            <?php
                            $startPage = max(1, $currentPage - 2);
                            $endPage = min($totalPages, $currentPage + 2);

                            for ($i = $startPage; $i <= $endPage; $i++):
                                $queryParams['page'] = $i;
                                $pageUrl = '?' . http_build_query($queryParams);
                                ?>
                                <a href="<?= $pageUrl ?>"
                                   class="pagination-btn <?= $i === $currentPage ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>

                            <!-- Наступна сторінка -->
                            <?php if ($currentPage < $totalPages):
                                $queryParams['page'] = $currentPage + 1;
                                $nextUrl = '?' . http_build_query($queryParams);
                                ?>
                                <a href="<?= $nextUrl ?>" class="pagination-btn">
                                    Наступна
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="pagination-info">
                            Сторінка <?= $currentPage ?> з <?= $totalPages ?>
                            (показано <?= count($orders) ?> з <?= $totalOrders ?? count($orders) ?> записів)
                        </div>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-cart fa-3x"></i>
                    <h3>Замовлення не знайдені</h3>
                    <p>За вашими критеріями замовлень не знайдено.</p>
                    <?php if (isset($status_filter) && !empty($status_filter)): ?>
                        <a href="?action=orders" class="btn btn-primary">Показати всі замовлення</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Масові дії -->
    <div id="bulkActions" class="bulk-actions" style="display: none;">
        <div class="bulk-info">
            Вибрано: <span class="selected-count">0</span> замовлень
        </div>
        <div class="bulk-buttons">
            <button class="btn btn-warning" onclick="bulkUpdateStatus('in_progress')">
                <i class="fas fa-play"></i>
                В роботу
            </button>
            <button class="btn btn-success" onclick="bulkUpdateStatus('completed')">
                <i class="fas fa-check"></i>
                Завершити
            </button>
            <button class="btn btn-danger" onclick="bulkDelete()">
                <i class="fas fa-trash"></i>
                Видалити
            </button>
        </div>
    </div>
</div>

<!-- Модальне вікно для перегляду деталей замовлення -->
<div id="orderModal" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h2><i class="fas fa-file-invoice"></i> Деталі замовлення</h2>
            <div class="modal-actions">
                <span class="close">&times;</span>
            </div>
        </div>
        <div class="modal-body">
            <div id="orderDetails">
                <div class="loading">
                    <i class="fas fa-spinner fa-spin"></i> Завантаження...
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальне вікно для редагування замовлення -->
<div id="orderEditModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-edit"></i> Редагування замовлення</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form id="orderEditForm">
                <input type="hidden" name="order_id" value="">

                <div class="form-group">
                    <label for="orderStatus">Статус замовлення:</label>
                    <select id="orderStatus" name="status" class="form-control" required>
                        <option value="draft">Чернетка</option>
                        <option value="new">Новий</option>
                        <option value="in_progress">В роботі</option>
                        <option value="completed">Завершено</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="adminNotes">Нотатки адміністратора:</label>
                    <textarea id="adminNotes" name="admin_notes" class="form-control" rows="4" placeholder="Додайте нотатки..."></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Зберегти зміни
                    </button>
                    <button type="button" class="btn btn-secondary btn-close">
                        <i class="fas fa-times"></i>
                        Скасувати
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальне вікно підтвердження видалення -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>
                <i class="fas fa-exclamation-triangle"></i>
                Підтвердження видалення
            </h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <div class="delete-warning">
                <i class="fas fa-exclamation-triangle fa-3x"></i>
                <h3>Ви впевнені?</h3>
                <p>Ця дія незворотна. Замовлення буде повністю видалено з системи.</p>
                <div class="order-details" id="deleteOrderDetails"></div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-danger" id="confirmDelete">
                    <i class="fas fa-trash"></i>
                    Так, видалити
                </button>
                <button type="button" class="btn btn-secondary btn-close">
                    <i class="fas fa-times"></i>
                    Скасувати
                </button>
            </div>
        </div>
    </div>
</div>
<script src="/BuildMaster/UI/js/orders.js"></script>
</body>
</html>