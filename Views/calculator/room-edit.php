<?php
// Отримуємо дані з контролера
$roomId = $roomData['id'] ?? null;
$roomName = $roomData['room_name'] ?? 'Кімната';
$wallArea = $roomData['wall_area'] ?? 0;
$floorArea = $roomData['floor_area'] ?? 0;
$roomTypeId = $roomData['room_type_id'] ?? null;
$roomTypeName = $roomData['room_type_name'] ?? 'Невідомий тип';

if (!$roomId) {
    header('Location: /BuildMaster/calculator/order-rooms');
    exit;
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редагування кімнати - BuildMaster</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/BuildMaster/UI/css/services-selection.css">
    <link rel="stylesheet" href="/BuildMaster/UI/css/calculator.css">
    <script>
        window.roomEditData = {
            roomId: <?php echo json_encode($roomId); ?>,
            roomName: <?php echo json_encode($roomName); ?>,
            wallArea: <?php echo json_encode($wallArea); ?>,
            floorArea: <?php echo json_encode($floorArea); ?>,
            roomTypeId: <?php echo json_encode($roomTypeId); ?>,
            roomTypeName: <?php echo json_encode($roomTypeName); ?>,
            selectedServices: <?php echo json_encode($selectedServices ?? []); ?>
        };
    </script>
    <style>
        .room-edit-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 0;
            color: white;
            margin-bottom: 2rem;
        }

        .room-edit-title {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .room-edit-info {
            background: rgba(255, 255, 255, 0.1);
            padding: 1rem;
            border-radius: 8px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .room-edit-field {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .room-edit-field label {
            font-weight: 500;
            opacity: 0.9;
        }

        .room-edit-field input {
            padding: 0.75rem;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            background: rgba(255, 255, 255, 0.9);
        }

        .room-edit-field input:focus {
            outline: none;
            background: white;
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.5);
        }

        .save-changes-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 15px ;
        }

        .save-changes-btn:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }

        .save-changes-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .delete-room-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .delete-room-btn:hover {
            background: #c82333;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }

        .changes-indicator {
            background: #ffc107;
            color: #212529;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
            display: none;
            align-items: center;
            gap: 0.5rem;
            margin-left: 10px ;
        }

        .changes-indicator.show {
            display: flex;
        }

        .area-section {
            margin-bottom: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
        }

        .area-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background-color: #f8f9fa;
            cursor: pointer;
        }

        .area-title {
            margin: 0;
            font-size: 1.2em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .area-title small {
            font-size: 0.8em;
            color: #666;
            margin-left: 10px;
        }

        .area-content {
            display: none;
            padding: 15px;
        }

        .area-section.expanded .area-content {
            display: block;
        }

        .service-block {
            margin-bottom: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            overflow: hidden;
        }

        .service-block-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background-color: #f8f9fa;
            cursor: pointer;
        }

        .block-info {
            flex: 1;
        }

        .block-title {
            margin: 0;
            font-size: 1.1em;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .block-description {
            margin: 5px 0 0;
            font-size: 0.9em;
            color: #666;
        }

        .service-block-content {
            display: none;
            padding: 12px;
        }

        .service-block.expanded .service-block-content {
            display: block;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
        }

        .service-item {
            display: flex;
            gap: 15px;
            padding: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            background-color: #fff;
            transition: all 0.3s ease;
        }

        .service-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .service-item.selected {
            background-color: #f0f7ff;
            border-color: #007bff;
        }

        .service-checkbox {
            position: relative;
            width: 24px;
            height: 24px;
        }

        .service-check {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }

        .checkmark {
            position: absolute;
            top: 0;
            left: 0;
            height: 24px;
            width: 24px;
            background-color: #fff;
            border: 2px solid #ddd;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .service-check:checked ~ .checkmark {
            background-color: #007bff;
            border-color: #007bff;
        }

        .checkmark:after {
            content: "";
            position: absolute;
            display: none;
            left: 8px;
            top: 4px;
            width: 5px;
            height: 10px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }

        .service-check:checked ~ .checkmark:after {
            display: block;
        }

        .service-info {
            flex: 1;
        }

        .service-name {
            margin: 0 0 5px;
            font-size: 1.1em;
            color: #333;
        }

        .service-description {
            margin: 0 0 10px;
            font-size: 0.9em;
            color: #666;
        }

        .service-price {
            display: flex;
            align-items: baseline;
            gap: 5px;
            margin-bottom: 5px;
        }

        .price-value {
            font-size: 1.2em;
            font-weight: bold;
            color: #007bff;
        }

        .price-unit {
            font-size: 0.9em;
            color: #666;
        }

        .service-calculation {
            font-size: 0.9em;
            color: #666;
        }

        .calculation-details {
            display: block;
            margin-top: 5px;
        }

        .toggle-btn {
            background: none;
            border: none;
            padding: 5px;
            cursor: pointer;
            color: #666;
            transition: transform 0.3s ease;
        }

        .service-block.expanded .toggle-btn i,
        .area-section.expanded .toggle-btn i {
            transform: rotate(180deg);
        }

        .no-services {
            text-align: center;
            padding: 30px;
            color: #666;
        }

        /* Додаткові стилі для кращого відображення */
        .service-block-header:hover {
            background-color: #f0f0f0;
        }

        .service-item {
            position: relative;
        }

        .service-item:hover {
            border-color: #007bff;
        }

        .service-checkbox {
            margin-top: 2px;
        }

        .block-toggle {
            display: flex;
            align-items: center;
        }

        .toggle-btn i {
            transition: transform 0.3s ease;
        }

        .service-block-content {
            background-color: #fff;
        }

        .services-grid {
            margin-top: 10px;
        }
    </style>
</head>
<body>
<div class="calculator-container">
    <!-- Header -->
    <header class="room-edit-header">
        <div class="header-content">
            <div class="room-edit-title">
                <i class="fas fa-edit"></i>
                <h1>Редагування кімнати</h1>
                <button id="back-to-rooms" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                </button>
            </div>

            <div class="room-edit-info">
                <div class="room-edit-field">
                    <label for="room-name">Назва кімнати:</label>
                    <input type="text" id="room-name" value="<?= htmlspecialchars($roomName) ?>" placeholder="Введіть назву кімнати">
                </div>

                <div class="room-edit-field">
                    <label for="wall-area">Площа стін (м²):</label>
                    <input type="number" id="wall-area" value="<?= htmlspecialchars($wallArea) ?>" step="0.01" min="0.01" placeholder="0.00">
                </div>

                <div class="room-edit-field">
                    <label for="floor-area">Площа підлоги (м²):</label>
                    <input type="number" id="floor-area" value="<?= htmlspecialchars($floorArea) ?>" step="0.01" min="0.01" placeholder="0.00">
                </div>

                <div class="room-edit-field">
                    <label>Тип кімнати:</label>
                    <input type="text" value="<?= htmlspecialchars($roomTypeName) ?>" readonly style="background: rgba(255,255,255,0.5);">
                </div>
            </div>

            <div class="action-buttons">
                <div class="changes-indicator" id="changes-indicator">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>Є незбережені зміни</span>
                </div>

                <div style="display: flex; gap: 1rem;">
                    <button id="save-changes-btn" class="save-changes-btn" disabled>
                        <i class="fas fa-save"></i>
                        Зберегти зміни
                    </button>
                </div>
            </div>
        </div>
    </header>

    <main class="calculator-main">
        <div class="services-container">
            <div class="services-header">
                <!-- Інформація про проект -->
                <div class="project-info">
                    <div class="info-item">
                        <i class="fas fa-expand-arrows-alt"></i>
                        <span>Підлога: <strong id="floor-display"><?= htmlspecialchars($floorArea) ?> м²</strong></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-vector-square"></i>
                        <span>Стіни: <strong id="wall-display"><?= htmlspecialchars($wallArea) ?> м²</strong></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-expand-arrows-alt"></i>
                        <span>Стеля: <strong id="ceiling-display"><?= htmlspecialchars($floorArea) ?> м²</strong></span>
                    </div>
                </div>
            </div>

            <!-- Services Selection -->
            <div class="services-content">
                <div class="services-list" id="services-list">
                    <div class="loading-spinner">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p>Завантаження послуг...</p>
                    </div>
                </div>

                <div class="calculation-sidebar">
                    <div class="calculation-card">
                        <h3>
                            <i class="fas fa-calculator"></i>
                            Розрахунок вартості
                        </h3>

                        <div class="calculation-details">
                            <div class="selected-services" id="selected-services-list">
                                <p class="no-services">Оберіть послуги для розрахунку</p>
                            </div>

                            <div class="calculation-total">
                                <div class="total-row">
                                    <span>Загальна вартість:</span>
                                    <strong id="total-cost">0 ₴</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Service Block Template -->
    <template id="service-block-template">
        <div class="service-block">
            <div class="service-block-header">
                <div class="block-info">
                    <h3 class="block-title">
                        <i class="fas fa-tools"></i>
                        <span class="block-name"></span>
                    </h3>
                    <p class="block-description"></p>
                </div>
                <div class="block-toggle">
                    <button class="toggle-btn" type="button">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
            </div>
            <div class="service-block-content">
                <div class="services-grid"></div>
            </div>
        </div>
    </template>

    <!-- Service Item Template -->
    <template id="service-item-template">
        <div class="service-item">
            <div class="service-checkbox">
                <input type="checkbox" class="service-check">
                <span class="checkmark"></span>
            </div>
            <div class="service-info">
                <h4 class="service-name"></h4>
                <p class="service-description"></p>
                <div class="service-price">
                    <span class="price-value"></span>
                    <span class="price-unit">₴/м²</span>
                </div>
                <div class="service-calculation">
                    <span class="calculation-details"></span>
                </div>
            </div>
        </div>
    </template>

    <!-- Confirmation Modal -->
    <div id="confirmation-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-question-circle"></i> <span id="modal-title">Підтвердження</span></h3>
                <button id="close-modal-btn" class="close-btn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p id="modal-message"></p>
            </div>
            <div class="modal-footer">
                <button id="cancel-btn" class="secondary-btn">Скасувати</button>
                <button id="confirm-btn" class="primary-btn">Підтвердити</button>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div id="error-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Помилка</h3>
                <button id="close-error-btn" class="close-btn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p id="error-message"></p>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="success-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-check-circle"></i> Успішно</h3>
                <button id="close-success-btn" class="close-btn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p id="success-message"></p>
            </div>
        </div>
    </div>
</div>

<script src="/BuildMaster/UI/js/room-edit.js"></script>
</body>
</html>