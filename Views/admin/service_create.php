<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Додати нову послугу - Адміністративна панель</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="UI/css/admin.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .form-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #eee;
        }

        .form-header h2 {
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }

        .form-group label.required::after {
            content: " *";
            color: #dc2626;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-control.error {
            border-color: #dc2626;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 5px;
            border: 1px solid #dee2e6;
            transition: all 0.3s ease;
        }

        .checkbox-item:hover {
            background: #e9ecef;
        }

        .checkbox-item input[type="checkbox"] {
            margin: 0;
        }

        .checkbox-item label {
            margin: 0;
            font-weight: normal;
            cursor: pointer;
            flex: 1;
        }

        .area-type-badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .area-type-floor {
            background: #e3f2fd;
            color: #1976d2;
        }

        .area-type-walls {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .area-type-ceiling {
            background: #e8f5e8;
            color: #388e3c;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            text-decoration: none;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            display: none;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .loading {
            text-align: center;
            padding: 1rem;
            color: #666;
            display: none;
        }

        .help-text {
            font-size: 0.875rem;
            color: #666;
            margin-top: 0.25rem;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #667eea;
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .checkbox-group {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
<header class="admin-header">
    <div class="container">
        <h1>
            <i class="fas fa-cogs"></i>
            Адміністративна панель
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
            <li><a href="?action=services" class="active">
                    <i class="fas fa-wrench"></i>
                    Послуги
                </a></li>
        </ul>
    </div>
</nav>

<div class="container">
    <div class="form-container">
        <div class="form-header">
            <h2>
                <i class="fas fa-plus-circle"></i>
                Додати нову послугу
            </h2>
        </div>

        <div id="loading" class="loading">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Збереження...</p>
        </div>

        <div id="error" class="alert alert-danger"></div>
        <div id="success" class="alert alert-success"></div>

        <form id="serviceForm" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name" class="required">Назва послуги</label>
                <input type="text" id="name" name="name" class="form-control" required
                       placeholder="Введіть назву послуги"
                       data-tooltip="Введіть повну назву послуги">
                <div class="help-text">Повна назва послуги, яка буде відображатися клієнтам</div>
            </div>

            <div class="form-group">
                <label for="slug" class="required">URL-слаг</label>
                <input type="text" id="slug" name="slug" class="form-control" required
                       placeholder="url-slag-poslugi" pattern="[a-z0-9\-]+"
                       data-tooltip="URL-слаг генерується автоматично з назви">
                <div class="help-text">URL-слаг буде згенеровано автоматично з назви. Використовуйте тільки латинські літери, цифри та дефіси</div>
            </div>

            <div class="form-group">
                <label for="description">Опис послуги</label>
                <textarea id="description" name="description" class="form-control" rows="4"
                          placeholder="Детальний опис послуги..."
                          data-tooltip="Детальний опис послуги для клієнтів"></textarea>
                <div class="help-text">Детальний опис послуги, який допоможе клієнтам зрозуміти, що включає послуга</div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="service_block_id" class="required">Блок послуг</label>
                    <select id="service_block_id" name="service_block_id" class="form-control" required
                            data-tooltip="Оберіть блок, до якого належить послуга">
                        <option value="">Оберіть блок</option>
                        <?php foreach ($serviceBlocks as $block): ?>
                            <option value="<?= $block['id'] ?>"><?= htmlspecialchars($block['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="help-text">Блок послуг визначає категорію та контекст застосування послуги</div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="price_per_sqm" class="required">Ціна за м²</label>
                    <input type="number" id="price_per_sqm" name="price_per_sqm" class="form-control"
                           required min="0" step="0.01" placeholder="0.00"
                           data-tooltip="Вкажіть ціну послуги за квадратний метр">
                    <div class="help-text">Ціна послуги за одиницю вимірювання (грн)</div>
                </div>
            </div>

            <div class="form-group">
                <label>Області застосування</label>
                <div class="checkbox-group">
                    <?php foreach ($areas as $area): ?>
                        <div class="checkbox-item">
                            <input type="checkbox" id="area_<?= $area['id'] ?>"
                                   name="areas[]" value="<?= $area['id'] ?>">
                            <label for="area_<?= $area['id'] ?>">
                                <?= htmlspecialchars($area['name']) ?>
                                <span class="area-type-badge area-type-<?= $area['area_type'] ?>">
                                        <?= $area['area_type'] ?>
                                    </span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="help-text">Оберіть області, де може застосовуватися ця послуга</div>
                <div id="areaError" class="alert alert-danger" style="display: none; margin-top: 0.5rem;">
                    Оберіть принаймні одну область застосування
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="sort_order">Порядок сортування</label>
                    <input type="number" id="sort_order" name="sort_order" class="form-control"
                           value="0" min="0" step="1"
                           data-tooltip="Чим менше число, тим вище послуга в списку">
                    <div class="help-text">Порядок відображення послуги в списку (0 = перша)</div>
                </div>

                <div class="form-group">
                    <label>Налаштування</label>
                    <div style="display: flex; flex-direction: column; gap: 1rem; margin-top: 0.5rem;">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <label class="switch">
                                <input type="checkbox" id="is_active" name="is_active" checked>
                                <span class="slider"></span>
                            </label>
                            <label for="is_active" style="margin: 0; cursor: pointer;">
                                Активна послуга
                            </label>
                        </div>
                    </div>
                    <div class="help-text">Обов'язкова послуга додається автоматично до всіх замовлень</div>
                </div>
            </div>

            <div class="form-actions">
                <a href="?action=services" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    Скасувати
                </a>
                <button type="submit" id="submitBtn" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Зберегти послугу
                </button>
            </div>
        </form>
    </div>
</div>

<script src="/BuildMaster/UI/js/service.js"></script>
</body>
</html>