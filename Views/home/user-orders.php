<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Перевірка авторизації
$isLoggedIn = isset($_SESSION['user']) && $_SESSION['user']['authenticated'] === true;
if (!$isLoggedIn) {
    header('Location: /BuildMaster/');
    exit;
}

// ДОДАНО: Відладжувальна інформація
error_log("View: Rendering user-orders.php");
error_log("View: Data variable exists: " . (isset($data) ? 'YES' : 'NO'));

if (isset($data)) {
    error_log("View: Data content: " . json_encode([
            'orders_count' => count($data['orders'] ?? []),
            'stats' => $data['stats'] ?? [],
            'totalOrders' => $data['totalOrders'] ?? 0
        ]));
} else {
    error_log("View: Data variable is not set, using defaults");
}

// ВИПРАВЛЕНО: Ініціалізація змінної $data з перевіркою
if (!isset($data) || !is_array($data)) {
    error_log("View: Initializing default data");
    $data = [
        'stats' => [
            'total_orders' => 0,
            'new_orders' => 0,
            'in_progress_orders' => 0,
            'completed_orders' => 0,
            'total_spent' => 0
        ],
        'orders' => [],
        'status_filter' => isset($_GET['status']) ? $_GET['status'] : '',
        'currentPage' => isset($_GET['page']) ? (int)$_GET['page'] : 1,
        'totalPages' => 1,
        'totalOrders' => 0
    ];
}

// ДОДАНО: Забезпечуємо що всі необхідні ключі існують
$data['stats'] = $data['stats'] ?? [
    'total_orders' => 0,
    'new_orders' => 0,
    'in_progress_orders' => 0,
    'completed_orders' => 0,
    'total_spent' => 0
];

$data['orders'] = $data['orders'] ?? [];
$data['status_filter'] = $data['status_filter'] ?? '';
$data['currentPage'] = $data['currentPage'] ?? 1;
$data['totalPages'] = $data['totalPages'] ?? 1;
$data['totalOrders'] = $data['totalOrders'] ?? 0;

$isAdmin = $isLoggedIn && isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'] === true;

// ДОДАНО: Фінальна відладка перед рендерингом
error_log("View: Final data before rendering: " . json_encode([
        'orders_count' => count($data['orders']),
        'total_orders_stat' => $data['stats']['total_orders'],
        'total_orders_count' => $data['totalOrders']
    ]));
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мої замовлення - BuildMaster</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/BuildMaster/UI/css/home.css">
    <link rel="stylesheet" href="/BuildMaster/UI/css/user-orders.css">
</head>
<body>

<!-- Navigation -->
<nav class="navbar" id="navbar">
    <div class="nav-container">
        <div class="logo">
            <a href="http://localhost/BuildMaster/" style="color: inherit; text-decoration: none;">BuildMaster</a>
        </div>
        <ul class="nav-links">
            <?php if ($isAdmin): ?>
                <li><a href="#" id="adminpanel"><i class="fas fa-cogs"></i> Адмін панель</a></li>
            <?php endif; ?>
            <li><a href="http://localhost/BuildMaster/">Головна</a></li>
            <li><a href="http://localhost/BuildMaster/#services">Послуги</a></li>
            <li><a href="http://localhost/BuildMaster/Calculator">Калькулятор</a></li>
            <li><a href="http://localhost/BuildMaster/users-orders" class="active">Мої замовлення</a></li>
            <li><a href="#" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Вийти</a></li>
        </ul>
    </div>
</nav>

<div class="user-orders-page">
    <div class="orders-container">
        <!-- Header -->
        <div class="orders-header">
            <h1><i class="fas fa-clipboard-list"></i> Мої замовлення</h1>
            <p>Тут ви можете переглянути всі свої замовлення та відстежити їх статус</p>

            <!-- ДОДАНО: Відладжувальна інформація (видаліть після налагодження) -->
            <?php if (isset($_GET['debug'])): ?>
                <div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 5px;">
                    <strong>Debug Info:</strong><br>
                    Total Orders: <?= $data['totalOrders'] ?><br>
                    Orders Array Count: <?= count($data['orders']) ?><br>
                    Stats: <?= json_encode($data['stats']) ?><br>
                    User ID: <?= $_SESSION['user']['id'] ?? 'NOT SET' ?><br>
                    User Email: <?= $_SESSION['user']['email'] ?? 'NOT SET' ?>
                </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-shopping-cart"></i>
                    <h3><?= $data['stats']['total_orders'] ?></h3>
                    <p>Всього замовлень</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-clock"></i>
                    <h3><?= $data['stats']['new_orders'] + $data['stats']['in_progress_orders'] ?></h3>
                    <p>Активних</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-check-circle"></i>
                    <h3><?= $data['stats']['completed_orders'] ?></h3>
                    <p>Завершених</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-hryvnia-sign"></i>
                    <h3><?= number_format($data['stats']['total_spent'], 0) ?></h3>
                    <p>Витрачено грн</p>
                </div>
            </div>
        </div>

        <!-- Controls -->
        <div class="orders-controls">
            <div class="filter-group">
                <label for="statusFilter">Фільтр за статусом:</label>
                <select id="statusFilter" class="filter-select" onchange="filterOrders()">
                    <option value="">Всі замовлення</option>
                    <option value="draft" <?= $data['status_filter'] === 'draft' ? 'selected' : '' ?>>Чернетка</option>
                    <option value="new" <?= $data['status_filter'] === 'new' ? 'selected' : '' ?>>Нові</option>
                    <option value="in_progress" <?= $data['status_filter'] === 'in_progress' ? 'selected' : '' ?>>В роботі</option>
                    <option value="completed" <?= $data['status_filter'] === 'completed' ? 'selected' : '' ?>>Завершені</option>
                </select>
            </div>

            <a href="http://localhost/BuildMaster/Calculator" class="btn-calculator">
                <i class="fas fa-calculator"></i>
                Створити замовлення
            </a>
        </div>
    </div>

    <?php
    // ВИПРАВЛЕНО: Додаткова перевірка наявності замовлень
    $hasOrders = !empty($data['orders']) && is_array($data['orders']) && count($data['orders']) > 0;
    error_log("View: Has orders check: " . ($hasOrders ? 'YES' : 'NO'));
    ?>

    <?php if ($hasOrders): ?>
        <!-- Orders Table -->
        <div class="orders-table">
            <table class="table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Статус</th>
                    <th>Кімнат</th>
                    <th>Сума</th>
                    <th>Дата створення</th>
                    <th>Дії</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($data['orders'] as $order): ?>
                    <tr>
                        <td><?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?></td>
                        <td>
                            <span class="status-badge status-<?= $order['status'] ?>">
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
                        <td><?= $order['rooms_count'] ?? 0 ?></td>
                        <td class="order-amount"><?= number_format($order['total_amount'] ?? 0, 0) ?> грн</td>
                        <td class="order-date"><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                        <td>
                            <button class="btn-view" onclick="showOrderDetails(<?= $order['id'] ?>)">
                                <i class="fas fa-eye"></i>
                                Переглянути
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>


        <!-- Pagination -->
        <?php if ($data['totalPages'] > 1): ?>
            <div class="pagination">
                <?php if ($data['currentPage'] > 1): ?>
                    <a href="?page=<?= $data['currentPage'] - 1 ?><?= !empty($data['status_filter']) ? '&status=' . $data['status_filter'] : '' ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $data['totalPages']; $i++): ?>
                    <?php if ($i == $data['currentPage']): ?>
                        <span class="current"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?page=<?= $i ?><?= !empty($data['status_filter']) ? '&status=' . $data['status_filter'] : '' ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($data['currentPage'] < $data['totalPages']): ?>
                    <a href="?page=<?= $data['currentPage'] + 1 ?><?= !empty($data['status_filter']) ? '&status=' . $data['status_filter'] : '' ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- Empty State -->
        <div class="empty-state">
            <i class="fas fa-clipboard-list"></i>
            <h3>У вас поки немає замовлень</h3>
            <p>Скористайтеся нашим калькулятором, щоб створити перше замовлення</p>
            <a href="http://localhost/BuildMaster/Calculator" class="btn-calculator">
                <i class="fas fa-calculator"></i>
                Перейти до калькулятора
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Order Details Modal -->
<div id="orderModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Деталі замовлення</h2>
            <span class="close" onclick="closeOrderModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div class="loading">
                <i class="fas fa-spinner"></i>
                <p>Завантаження...</p>
            </div>
        </div>
    </div>
</div>

<script>
    function filterOrders() {
        const status = document.getElementById('statusFilter').value;
        let url = window.location.pathname;

        if (status) {
            url += '?status=' + status;
        }

        window.location.href = url;
    }

    // Показати деталі замовлення
    function showOrderDetails(orderId) {
        const modal = document.getElementById('orderModal');
        const modalBody = modal.querySelector('.modal-body');

        // Показуємо модальне вікно
        modal.classList.add('active');

        // Показуємо лоадер
        modalBody.innerHTML = `
        <div class="loading">
            <i class="fas fa-spinner"></i>
            <p>Завантаження...</p>
        </div>
    `;

        // Завантажуємо деталі замовлення
        fetch(`/BuildMaster/api/order-details?id=${orderId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    modalBody.innerHTML = data.html;
                } else {
                    modalBody.innerHTML = `
                    <div style="text-align: center; padding: 40px;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 2rem; color: #e74c3c; margin-bottom: 15px;"></i>
                        <h3>Помилка</h3>
                        <p>${data.message}</p>
                        <button onclick="closeOrderModal()" class="btn-view">Закрити</button>
                    </div>
                `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                modalBody.innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 2rem; color: #e74c3c; margin-bottom: 15px;"></i>
                    <h3>Помилка з'єднання</h3>
                    <p>Не вдалося завантажити деталі замовлення</p>
                    <button onclick="closeOrderModal()" class="btn-view">Закрити</button>
                </div>
            `;
            });
    }

    // Закрити модальне вікно
    function closeOrderModal() {
        const modal = document.getElementById('orderModal');
        modal.classList.remove('active');
    }

    // Закрити модальне вікно при кліку поза ним
    window.onclick = function(event) {
        const modal = document.getElementById('orderModal');
        if (event.target == modal) {
            closeOrderModal();
        }
    }

    // Кнопка виходу
    document.getElementById('logoutBtn').addEventListener('click', function(e) {
        e.preventDefault();
        if (confirm('Ви впевнені, що хочете вийти?')) {
            // Тут можна додати логіку виходу
            fetch('/BuildMaster/api/logout', {
                method: 'POST'
            }).then(() => {
                window.location.href = 'http://localhost/BuildMaster/';
            });
        }
    });

    // Кнопка повернення на головну
    function goToHome() {
        window.location.href = 'http://localhost/BuildMaster/';
    }

    // Додаємо обробник для всіх кнопок повернення на головну
    document.addEventListener('DOMContentLoaded', function() {
        const homeButtons = document.querySelectorAll('.btn-home');
        homeButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                goToHome();
            });
        });
    });

    // Smooth scroll animation для навігації
    document.addEventListener('DOMContentLoaded', function() {
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    });

    // Обробник для адмін панелі
    document.addEventListener('DOMContentLoaded', function() {
        const adminLink = document.getElementById('adminpanel');
        if (adminLink) {
            adminLink.addEventListener('click', function(event) {
                event.preventDefault();
                window.location.href = '/BuildMaster/admin';
            });
        }
    });
</script>

</body>
</html>