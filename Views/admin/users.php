<?php
// Views/admin/users.php
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управління користувачами - BuildMaster</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/BuildMaster/UI/css/admin.css">
    <link rel="stylesheet" href="/BuildMaster/UI/css/order.css">

    <style>
        /* Стилі для модальних вікон */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 0;
            border: none;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            animation: slideIn 0.3s;
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }

        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }

        .close:hover {
            opacity: 0.7;
        }

        .modal-body {
            padding: 20px;
        }

        .user-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .user-info h4 {
            margin: 0 0 10px 0;
            color: #333;
        }

        .user-info p {
            margin: 5px 0;
            color: #666;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            cursor: pointer;
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            transition: all 0.3s;
        }

        .checkbox-label:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }

        .checkbox-label input[type="checkbox"] {
            margin-right: 10px;
            transform: scale(1.2);
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-help {
            color: #6c757d;
            font-size: 12px;
            margin-top: 5px;
        }

        .form-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 30px;
        }

        .delete-warning {
            text-align: center;
            padding: 20px;
        }

        .delete-warning i {
            color: #dc3545;
            margin-bottom: 15px;
        }

        .delete-warning h3 {
            color: #dc3545;
            margin-bottom: 10px;
        }

        .user-details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            text-align: left;
        }

        /* Стилі для масових дій */
        .bulk-actions {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            padding: 15px 20px;
            border-radius: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 20px;
            z-index: 100;
        }

        .bulk-info {
            font-weight: 600;
            color: #333;
        }

        .selected-count {
            color: #667eea;
        }

        .bulk-buttons {
            display: flex;
            gap: 10px;
        }

        /* Анімації */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Стилі для таблиці */
        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge.admin {
            background: #667eea;
            color: white;
        }

        .badge.user {
            background: #6c757d;
            color: white;
        }

        .badge.status-active {
            background: #28a745;
            color: white;
        }

        .badge.status-inactive {
            background: #ffc107;
            color: #333;
        }

        .badge.status-banned {
            background: #dc3545;
            color: white;
        }

        /* Повідомлення */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 6px;
            color: white;
            font-weight: 600;
            z-index: 1001;
            animation: slideInRight 0.3s;
        }

        .notification.success {
            background: #28a745;
        }

        .notification.error {
            background: #dc3545;
        }

        .notification.warning {
            background: #ffc107;
            color: #333;
        }

        @keyframes slideInRight {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }
    </style>
</head>
<body>
<a href="/BuildMaster/" class="btn btn-primary back-to-site">
    <i class="fas fa-arrow-left"></i>
    Повернутися на сайт
</a>

<header class="admin-header">
    <div class="container">
        <h1>
            <i class="fas fa-users"></i>
            Управління користувачами
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
            <li><a href="?action=users" class="active">
                    <i class="fas fa-users"></i>
                    Користувачі
                </a></li>
            <li><a href="?action=orders">
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
    <!-- Панель пошуку та фільтрів -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-search"></i>
            Пошук користувачів
        </div>
        <div class="card-body">
            <form id="userSearchForm" class="search-form">
                <div class="search-group">
                    <input type="text" name="search" placeholder="Пошук за ім'ям, прізвищем або email"
                           value="<?= htmlspecialchars($search ?? '') ?>" class="form-control">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        Пошук
                    </button>
                    <?php if (!empty($search)): ?>
                        <a href="?action=users" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Очистити
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Таблиця користувачів -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-users"></i>
            Користувачі (<?= $totalUsers ?> всього)
            <div class="header-actions">
                <button class="btn btn-sm btn-success" onclick="exportUsers()">
                    <i class="fas fa-download"></i>
                    Експорт
                </button>
            </div>
        </div>
        <div class="card-body">
            <?php if (!empty($users)): ?>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th>ID</th>
                            <th>Ім'я</th>
                            <th>Email</th>
                            <th>Телефон</th>
                            <th>Роль</th>
                            <th>Статус</th>
                            <th>Дата реєстрації</th>
                            <th>Дії</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr data-user-id="<?= $user['id'] ?>">
                                <td>
                                    <input type="checkbox" class="row-checkbox" value="<?= $user['id'] ?>">
                                </td>
                                <td><?= $user['id'] ?></td>
                                <td>
                                    <div class="user-info">
                                        <strong><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></strong>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= $user['phone'] ? htmlspecialchars($user['phone']) : '-' ?></td>
                                <td>
                                    <span class="badge <?= $user['is_admin'] ? 'admin' : 'user' ?>">
                                        <i class="fas <?= $user['is_admin'] ? 'fa-user-shield' : 'fa-user' ?>"></i>
                                        <?= $user['is_admin'] ? 'Адміністратор' : 'Користувач' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge status-<?= $user['status'] ?>">
                                        <?php
                                        switch ($user['status']) {
                                            case 'active':
                                                echo '<i class="fas fa-check-circle"></i> Активний';
                                                break;
                                            case 'inactive':
                                                echo '<i class="fas fa-pause-circle"></i> Неактивний';
                                                break;
                                            case 'banned':
                                                echo '<i class="fas fa-ban"></i> Заблокований';
                                                break;
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td><?= date('d.m.Y H:i', strtotime($user['created_at'])) ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-sm btn-primary"
                                                onclick="editUser(<?= $user['id'] ?>)"
                                                title="Редагувати користувача">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($user['id'] !== $this->auth->getCurrentUser()['id']): ?>
                                            <button class="btn btn-sm btn-danger"
                                                    onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>')"
                                                    title="Видалити користувача">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Пагінація -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination-wrapper">
                        <div class="pagination">
                            <?php
                            $queryParams = $_GET;

                            // Попередня сторінка
                            if ($page > 1):
                                $queryParams['page'] = $page - 1;
                                $prevUrl = '?' . http_build_query($queryParams);
                                ?>
                                <a href="<?= $prevUrl ?>" class="pagination-btn">
                                    <i class="fas fa-chevron-left"></i>
                                    Попередня
                                </a>
                            <?php endif; ?>

                            <!-- Номери сторінок -->
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);

                            for ($i = $startPage; $i <= $endPage; $i++):
                                $queryParams['page'] = $i;
                                $pageUrl = '?' . http_build_query($queryParams);
                                ?>
                                <a href="<?= $pageUrl ?>"
                                   class="pagination-btn <?= $i === $page ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>

                            <!-- Наступна сторінка -->
                            <?php if ($page < $totalPages):
                                $queryParams['page'] = $page + 1;
                                $nextUrl = '?' . http_build_query($queryParams);
                                ?>
                                <a href="<?= $nextUrl ?>" class="pagination-btn">
                                    Наступна
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="pagination-info">
                            Сторінка <?= $page ?> з <?= $totalPages ?>
                            (показано <?= count($users) ?> з <?= $totalUsers ?> записів)
                        </div>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-users fa-3x"></i>
                    <h3>Користувачі не знайдені</h3>
                    <p>За вашим запитом користувачів не знайдено.</p>
                    <?php if (!empty($search)): ?>
                        <a href="?action=users" class="btn btn-primary">Показати всіх користувачів</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Масові дії -->
    <div id="bulkActions" class="bulk-actions" style="display: none;">
        <div class="bulk-info">
            Вибрано: <span class="selected-count">0</span> користувачів
        </div>
        <div class="bulk-buttons">
            <button class="btn btn-warning" onclick="bulkUpdateStatus('inactive')">
                <i class="fas fa-pause"></i>
                Деактивувати
            </button>
            <button class="btn btn-success" onclick="bulkUpdateStatus('active')">
                <i class="fas fa-check"></i>
                Активувати
            </button>
            <button class="btn btn-danger" onclick="bulkDelete()">
                <i class="fas fa-trash"></i>
                Видалити
            </button>
        </div>
    </div>
</div>

<!-- Модальне вікно редагування користувача -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>
                <i class="fas fa-user-edit"></i>
                Редагування користувача
            </h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <div class="user-info" id="userInfo"></div>
            <form id="userForm">
                <input type="hidden" name="user_id" value="">

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_admin">
                        <i class="fas fa-user-shield"></i>
                        Адміністратор
                    </label>
                    <small class="form-help">
                        Надає повний доступ до адміністративної панелі
                    </small>
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-toggle-on"></i>
                        Статус користувача:
                    </label>
                    <select name="status" class="form-control">
                        <option value="active">Активний</option>
                        <option value="inactive">Неактивний</option>
                        <option value="banned">Заблокований</option>
                    </select>
                    <small class="form-help">
                        Неактивні користувачі не можуть входити в систему.
                        Заблоковані користувачі повністю позбавлені доступу.
                    </small>
                </div>

                <div class="form-buttons">
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
                <p>Ця дія незворотна. Користувач буде повністю видалений з системи.</p>
                <div class="user-details" id="deleteUserDetails"></div>
            </div>
            <div class="form-buttons">
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

<script>
    // JavaScript для роботи з користувачами
    let currentUserId = null;
    let currentDeleteUserId = null;

    // Керування модальними вікнами
    function openModal(modalId) {
        document.getElementById(modalId).style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
        document.body.style.overflow = '';
    }

    // Закриття модальних вікон
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal') || e.target.classList.contains('close') || e.target.classList.contains('btn-close')) {
            e.target.closest('.modal').style.display = 'none';
            document.body.style.overflow = '';
        }
    });

    // Редагування користувача
    function editUser(userId) {
        currentUserId = userId;
        const row = document.querySelector(`tr[data-user-id="${userId}"]`);
        const cells = row.querySelectorAll('td');

        const name = cells[2].textContent.trim();
        const email = cells[3].textContent.trim();
        const phone = cells[4].textContent.trim();
        const isAdmin = cells[5].textContent.includes('Адміністратор');
        const status = cells[6].querySelector('.badge').classList.contains('status-active') ? 'active' :
            cells[6].querySelector('.badge').classList.contains('status-inactive') ? 'inactive' : 'banned';

        // Заповнюємо форму
        document.querySelector('#userForm input[name="user_id"]').value = userId;
        document.querySelector('#userForm input[name="is_admin"]').checked = isAdmin;
        document.querySelector('#userForm select[name="status"]').value = status;

        // Заповнюємо інформацію про користувача
        document.getElementById('userInfo').innerHTML = `
        <h4>${name}</h4>
        <p><i class="fas fa-envelope"></i> ${email}</p>
        <p><i class="fas fa-phone"></i> ${phone !== '-' ? phone : 'Не вказано'}</p>
    `;

        openModal('userModal');
    }

    // Видалення користувача
    function deleteUser(userId, userName) {
        currentDeleteUserId = userId;
        document.getElementById('deleteUserDetails').innerHTML = `
        <strong>Користувач:</strong> ${userName}<br>
        <strong>ID:</strong> ${userId}
    `;
        openModal('deleteModal');
    }

    // Обробка форми редагування
    document.getElementById('userForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        fetch('?action=user_update', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    closeModal('userModal');
                    location.reload();
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Помилка оновлення користувача', 'error');
            });
    });

    // Підтвердження видалення
    document.getElementById('confirmDelete').addEventListener('click', function() {
        const formData = new FormData();
        formData.append('user_id', currentDeleteUserId);

        fetch('?action=user_delete', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    closeModal('deleteModal');
                    location.reload();
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Помилка видалення користувача', 'error');
            });
    });

    // Масове вибір
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.row-checkbox');
        checkboxes.forEach(cb => cb.checked = this.checked);
        updateBulkActions();
    });

    // Оновлення масових дій
    function updateBulkActions() {
        const selected = document.querySelectorAll('.row-checkbox:checked');
        const bulkActions = document.getElementById('bulkActions');
        const count = document.querySelector('.selected-count');

        if (selected.length > 0) {
            bulkActions.style.display = 'flex';
            count.textContent = selected.length;
        } else {
            bulkActions.style.display = 'none';
        }
    }

    // Слухачі для чекбоксів
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('row-checkbox')) {
            updateBulkActions();
        }
    });

    // Експорт користувачів
    function exportUsers() {
        const params = new URLSearchParams(window.location.search);
        params.set('action', 'export_users');
        window.open('?' + params.toString(), '_blank');
    }

    // Масове оновлення статусу
    function bulkUpdateStatus(status) {
        const selectedUsers = Array.from(document.querySelectorAll('.row-checkbox:checked'))
            .map(cb => cb.value);

        if (selectedUsers.length === 0) {
            showNotification('Не вибрано жодного користувача', 'warning');
            return;
        }

        const statusLabels = {
            'active': 'Активний',
            'inactive': 'Неактивний',
            'banned': 'Заблокований'
        };

        if (confirm(`Змінити статус для ${selectedUsers.length} користувачів на "${statusLabels[status]}"?`)) {
            fetch('?action=bulk_update_users', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_ids: selectedUsers,
                    status: status
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        location.reload();
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Помилка оновлення користувачів', 'error');
                });
        }
    }

    // Масове видалення
    function bulkDelete() {
        const selectedUsers = Array.from(document.querySelectorAll('.row-checkbox:checked'))
            .map(cb => cb.value);

        if (selectedUsers.length === 0) {
            showNotification('Не вибрано жодного користувача', 'warning');
            return;
        }

        if (confirm(`Видалити ${selectedUsers.length} користувачів? Цю дію неможливо скасувати!`)) {
            fetch('?action=bulk_delete_users', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_ids: selectedUsers
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        location.reload();
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Помилка видалення користувачів', 'error');
                });
        }
    }

    // Показ повідомлень
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 5000);
    }

    // Пошук користувачів
    document.getElementById('userSearchForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const search = this.querySelector('input[name="search"]').value;
        const url = new URL(window.location);
        url.searchParams.set('action', 'users');
        if (search) {
            url.searchParams.set('search', search);
        } else {
            url.searchParams.delete('search');
        }
        url.searchParams.delete('page');
        window.location = url;
    });
</script>
</body>
</html>