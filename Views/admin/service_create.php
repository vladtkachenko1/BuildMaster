<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управління послугами - BuildMaster</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/BuildMaster/UI/css/service-create.css">
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
            Управління послугами
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
            <li><a href="?action=orders">
                    <i class="fas fa-shopping-cart"></i>
                    Замовлення
                </a></li>
            <li><a href="?page=services" class="active">
                    <i class="fas fa-cogs"></i>
                    Послуги
                </a></li>
        </ul>
    </div>
</nav>

<div class="container">
    <!-- Панель масових дій -->
    <div class="card">
        <div class="card-body">
            <div class="bulk-actions-panel">
                <div class="bulk-select">
                    <label class="checkbox-custom">
                        <input type="checkbox" id="select-all-services">
                        <span class="checkmark"></span>
                    </label>
                    <span>Вибрати всі послуги</span>
                </div>
                <div class="bulk-actions">
                    <button id="bulk-delete-btn" class="btn btn-danger" disabled>
                        <i class="fas fa-trash"></i>
                        Видалити вибрані
                    </button>
                    <a href="?page=services&action=add" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Додати послугу
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Таблиця послуг -->
    <div class="card">
        <div class="card-header">
            <div>
                <i class="fas fa-cogs"></i>
                Послуги
            </div>
            <div class="stats-badge">
                Всього: <span id="services-count">24</span>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="admin-table" id="services-table">
                    <thead>
                    <tr>
                        <th style="width: 40px;">
                            <label class="checkbox-custom">
                                <input type="checkbox" id="select-all-header">
                                <span class="checkmark"></span>
                            </label>
                        </th>
                        <th>Назва послуги</th>
                        <th>Блок послуг</th>
                        <th>Ціна</th>
                        <th>Статус</th>
                        <th>Залежить від</th>
                        <th style="width: 120px;">Дії</th>
                    </tr>
                    </thead>
                    <tbody>
                    <!-- Service rows will be populated here -->
                    <tr data-service-id="1">
                        <td>
                            <label class="checkbox-custom">
                                <input type="checkbox" class="service-checkbox" value="1">
                                <span class="checkmark"></span>
                            </label>
                        </td>
                        <td>
                            <div class="service-name">Демонтаж старої шпалери</div>
                        </td>
                        <td>
                            <span class="service-block">Підготовчі роботи</span>
                        </td>
                        <td>
                                    <span class="service-price"
                                          data-service-id="1"
                                          data-service-name="Демонтаж старої шпалери"
                                          data-current-price="25.50">
                                        25.50 грн/м²
                                    </span>
                        </td>
                        <td>
                            <span class="service-status active">Активна</span>
                        </td>
                        <td>-</td>
                        <td>
                            <div class="action-buttons">
                                <button class="edit-price-btn"
                                        data-service-id="1"
                                        data-service-name="Демонтаж старої шпалери"
                                        data-current-price="25.50"
                                        title="Редагувати ціну">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="delete-service-btn"
                                        data-service-id="1"
                                        data-service-name="Демонтаж старої шпалери"
                                        title="Видалити послугу">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>

                    <tr data-service-id="2">
                        <td>
                            <label class="checkbox-custom">
                                <input type="checkbox" class="service-checkbox" value="2">
                                <span class="checkmark"></span>
                            </label>
                        </td>
                        <td>
                            <div class="service-name">Грунтування стін</div>
                        </td>
                        <td>
                            <span class="service-block">Підготовчі роботи</span>
                        </td>
                        <td>
                                    <span class="service-price"
                                          data-service-id="2"
                                          data-service-name="Грунтування стін"
                                          data-current-price="15.00">
                                        15.00 грн/м²
                                    </span>
                        </td>
                        <td>
                            <span class="service-status active">Активна</span>
                        </td>
                        <td>Демонтаж старої шпалери</td>
                        <td>
                            <div class="action-buttons">
                                <button class="edit-price-btn"
                                        data-service-id="2"
                                        data-service-name="Грунтування стін"
                                        data-current-price="15.00"
                                        title="Редагувати ціну">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="delete-service-btn"
                                        data-service-id="2"
                                        data-service-name="Грунтування стін"
                                        title="Видалити послугу">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>

                    <tr data-service-id="3">
                        <td>
                            <label class="checkbox-custom">
                                <input type="checkbox" class="service-checkbox" value="3">
                                <span class="checkmark"></span>
                            </label>
                        </td>
                        <td>
                            <div class="service-name">Поклейка шпалер</div>
                        </td>
                        <td>
                            <span class="service-block">Оздоблювальні роботи</span>
                        </td>
                        <td>
                                    <span class="service-price"
                                          data-service-id="3"
                                          data-service-name="Поклейка шпалер"
                                          data-current-price="85.00">
                                        85.00 грн/м²
                                    </span>
                        </td>
                        <td>
                            <span class="service-status active">Активна</span>
                        </td>
                        <td>Грунтування стін</td>
                        <td>
                            <div class="action-buttons">
                                <button class="edit-price-btn"
                                        data-service-id="3"
                                        data-service-name="Поклейка шпалер"
                                        data-current-price="85.00"
                                        title="Редагувати ціну">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="delete-service-btn"
                                        data-service-id="3"
                                        data-service-name="Поклейка шпалер"
                                        title="Видалити послугу">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Модальні вікна -->

<!-- Модальне вікно підтвердження видалення -->
<div id="delete-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-exclamation-triangle text-warning"></i>
                Підтвердження видалення
            </h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <p>Ви дійсно хочете видалити послугу <strong id="delete-service-name"></strong>?</p>
            <p class="text-danger">
                <i class="fas fa-warning"></i>
                Ця дія незворотна!
            </p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="cancel-delete">Скасувати</button>
            <button type="button" class="btn btn-danger" id="confirm-delete">
                <i class="fas fa-trash"></i>
                Видалити
            </button>
        </div>
    </div>
</div>

<!-- Модальне вікно масового видалення -->
<div id="bulk-delete-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-exclamation-triangle text-warning"></i>
                Масове видалення послуг
            </h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <p>Ви дійсно хочете видалити <strong id="bulk-delete-count">0</strong> вибраних послуг?</p>
            <div id="bulk-delete-list" class="selected-services-list"></div>
            <p class="text-danger">
                <i class="fas fa-warning"></i>
                Ця дія незворотна!
            </p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="cancel-bulk-delete">Скасувати</button>
            <button type="button" class="btn btn-danger" id="confirm-bulk-delete">
                <i class="fas fa-trash"></i>
                Видалити всі
            </button>
        </div>
    </div>
</div>

<!-- Модальне вікно редагування ціни -->
<div id="price-edit-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-edit text-primary"></i>
                Редагування ціни
            </h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="price-edit-form">
                <div class="form-group">
                    <label>Послуга:</label>
                    <div id="price-edit-service-name" class="form-control-static"></div>
                </div>
                <div class="form-group">
                    <label for="new-price">Нова ціна (грн/м²):</label>
                    <input type="number"
                           id="new-price"
                           name="price"
                           class="form-control"
                           step="0.01"
                           min="0.01"
                           required>
                    <div class="form-help">
                        Поточна ціна: <span id="current-price-display"></span> грн/м²
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="cancel-price-edit">Скасувати</button>
            <button type="button" class="btn btn-primary" id="save-price-edit">
                <i class="fas fa-save"></i>
                Зберегти
            </button>
        </div>
    </div>
</div>

<!-- Модальне вікно повідомлення про помилку -->
<div id="error-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-exclamation-circle text-danger"></i>
                Помилка
            </h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div id="error-message"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" id="close-error">Зрозуміло</button>
        </div>
    </div>
</div>

<!-- Модальне вікно успішної операції -->
<div id="success-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-check-circle text-success"></i>
                Успіх
            </h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div id="success-message"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" id="close-success">OK</button>
        </div>
    </div>
</div>

<!-- Модальне вікно завантаження -->
<div id="loading-modal" class="modal">
    <div class="modal-content loading-content">
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i>
        </div>
        <div class="loading-text">Обробка запиту...</div>
    </div>
</div>
<script src="/UI/js/service.js"></script>

</body>
</html>