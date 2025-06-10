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
    <link rel="stylesheet" href="/BuildMaster/UI/css/room-edit.css">

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

                <div style="display: flex; justify-content: flex-end;margin-left: auto; padding-right: 50px;">
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
                    <button class="toggle-btn">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
            </div>
            <div class="service-block-content">
                <div class="services-grid">
                    <!-- Services will be inserted here -->
                </div>
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

<script>
    window.roomEditData = {
        roomId: <?= json_encode($roomId) ?>,
        roomTypeId: <?= json_encode($roomTypeId) ?>,
        initialRoomName: <?= json_encode($roomName) ?>,
        initialWallArea: <?= json_encode(floatval($wallArea)) ?>,
        initialFloorArea: <?= json_encode(floatval($floorArea)) ?>
    };
</script>
<script src="/BuildMaster/UI/js/room-edit.js"></script>
</body>
</html>