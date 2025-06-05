<?php
// Отримуємо дані з сесії або параметрів
$roomTypeId = $_SESSION['room_type_id'] ?? $_GET['room_type_id'] ?? null;
$wallArea = $_SESSION['wall_area'] ?? $_GET['wall_area'] ?? 0;
$roomArea = $_SESSION['room_area'] ?? $_GET['room_area'] ?? 0;

if (!$roomTypeId || $wallArea <= 0 || $roomArea <= 0) {
    header('Location: /BuildMaster/calculator');
    exit;
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вибір послуг - BuildMaster</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/BuildMaster/UI/css/services-selection.css">
    <link rel="stylesheet" href="/BuildMaster/UI/css/calculator.css">

</head>
<body>
<div class="calculator-container">
    <!-- Header -->
    <header class="calculator-header">
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-list-check"></i>
                <h1>Вибір послуг</h1>
            </div>
            <p class="subtitle">Оберіть необхідні роботи для вашого ремонту</p>
            <button id="back-to-form" class="back-btn">
                <i class="fas fa-arrow-left"></i>
            </button>
        </div>
    </header>

    <main class="calculator-main">
        <div class="services-container">
            <div class="services-header">

                <!-- Інформація про проект -->
                <div class="project-info">
                    <div class="info-item">
                        <i class="fas fa-expand-arrows-alt"></i>
                        <span>Підлога: <strong><?= htmlspecialchars($roomArea) ?> м²</strong></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-vector-square"></i>
                        <span>Стіни: <strong><?= htmlspecialchars($wallArea) ?> м²</strong></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-expand-arrows-alt"></i>
                        <span>Стеля: <strong><?= htmlspecialchars($roomArea) ?> м²</strong></span>
                    </div>
                </div>

                <!-- Прогрес-бар -->
                <div class="progress-container">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 66%"></div>
                    </div>
                </div>
            </div>

            <!-- Services Selection -->
            <div class="services-content">
                <!-- Список послуг (ширший, по центру) -->
                <div class="services-list" id="services-list">
                    <div class="loading-spinner">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p>Завантаження послуг...</p>
                    </div>
                </div>

                <!-- Sidebar з розрахунком (знизу з такою ж шириною) -->
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

                        <button id="continue-btn" class="primary-btn" disabled>
                            <i class="fas fa-arrow-right"></i>
                            Далі
                        </button>
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
</div>

<script>
    window.calculatorData = {
        roomTypeId: <?= json_encode($roomTypeId) ?>,
        wallArea: <?= json_encode(floatval($wallArea)) ?>,
        roomArea: <?= json_encode(floatval($roomArea)) ?>
    };
</script>
<script src="/BuildMaster/UI/js/services-selection.js"></script>
</body>
</html>