<?php
$pageTitle = 'Ваше замовлення - BuildMaster';

if (!isset($totalAmount) && isset($orderRooms)) {
    $totalAmount = 0;
    foreach ($orderRooms as $room) {
        $totalAmount += (float)($room['room_total_cost'] ?? 0);
    }
    error_log("Calculated totalAmount in view: " . $totalAmount);
}

// Перевіряємо чи є режим редагування
$isEditingMode = isset($_SESSION['editing_room_id']);
$editingRoomId = $_SESSION['editing_room_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/BuildMaster/UI/css/calculator.css">
    <link rel="stylesheet" href="/BuildMaster/UI/css/order-rooms.css">
</head>
<body>
<div class="calculator-container">
    <!-- Header -->
    <header class="calculator-header">
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-shopping-cart"></i>
                <h1>Ваше замовлення</h1>
            </div>
            <p class="subtitle">
                <?php if ($isEditingMode): ?>
                    Режим редагування кімнати
                <?php else: ?>
                    Перегляньте та редагуйте список кімнат для ремонту
                <?php endif; ?>
            </p>
        </div>
    </header>

    <main class="calculator-main">
        <div class="order-container">
            <!-- Список кімнат -->
            <div class="rooms-section">
                <div class="section-header">
                    <h2>
                        <i class="fas fa-home"></i>
                        Кімнати для ремонту
                        <?php if ($isEditingMode): ?>
                            <span class="editing-badge">
                                <i class="fas fa-edit"></i>
                                Редагування
                            </span>
                        <?php endif; ?>
                    </h2>
                    <?php if (!$isEditingMode): ?>
                        <button id="add-room-btn" class="secondary-btn">
                            <i class="fas fa-plus"></i>
                            Додати кімнату
                        </button>
                    <?php endif; ?>
                </div>

                <div class="rooms-list" id="rooms-list">
                    <?php if (empty($orderRooms)): ?>
                        <div class="empty-state">
                            <i class="fas fa-home"></i>
                            <h3>Поки що немає кімнат</h3>
                            <p>Додайте першу кімнату для розрахунку ремонту</p>
                            <button id="first-room-btn" class="primary-btn">
                                <i class="fas fa-plus"></i>
                                Додати кімнату
                            </button>
                        </div>
                    <?php else: ?>
                        <?php foreach ($orderRooms as $index => $room): ?>
                            <div class="room-card" data-room-id="<?= htmlspecialchars($room['id'] ?? '') ?>">
                                <div class="room-header">
                                    <div class="room-info">
                                        <h3 class="room-name"><?= htmlspecialchars($room['room_name'] ?? 'Без назви') ?></h3>
                                        <div class="room-details">
                                            <span><i class="fas fa-vector-square"></i> Стіни: <?= htmlspecialchars($room['wall_area'] ?? '0') ?> м²</span>
                                            <span><i class="fas fa-expand-arrows-alt"></i> Підлога: <?= htmlspecialchars($room['floor_area'] ?? '0') ?> м²</span>
                                        </div>
                                    </div>
                                    <div class="room-actions">
                                        <button class="edit-room-btn" data-room-id="<?= htmlspecialchars($room['id'] ?? '') ?>" title="Редагувати кімнату">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="remove-room-btn" data-room-id="<?= htmlspecialchars($room['id'] ?? '') ?>" title="Видалити кімнату">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="room-services">
                                    <h4>Вибрані послуги:</h4>
                                    <div class="services-list">
                                        <?php if (!empty($room['services']) && is_array($room['services'])): ?>
                                            <?php foreach ($room['services'] as $service): ?>
                                                <div class="service-item">
                                                    <span class="service-name"><?= htmlspecialchars($service['service_name'] ?? 'Невідома послуга') ?></span>
                                                    <div class="service-details">
                                                        <span class="service-area"><?= number_format($service['quantity'] ?? 0, 2) ?> м² × <?= number_format($service['unit_price'] ?? 0, 2) ?> ₴</span>
                                                        <span class="service-cost"><?= number_format($service['total_price'] ?? 0, 2) ?> ₴</span>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p class="no-services">Послуги не вибрані</p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="room-total">
                                    <strong>Вартість ремонту: <?= number_format($room['room_total_cost'] ?? 0, 2) ?> ₴</strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar з підсумком -->
            <?php if (!empty($orderRooms)): ?>
                <div class="order-sidebar">
                    <div class="order-summary">
                        <h3>
                            <i class="fas fa-calculator"></i>
                            Підсумок замовлення
                        </h3>

                        <div class="summary-details">
                            <div class="summary-row">
                                <span>Кількість кімнат:</span>
                                <strong><?= count($orderRooms) ?></strong>
                            </div>
                            <div class="summary-row total">
                                <span>Загальна вартість:</span>
                                <strong><?= number_format($totalAmount ?? 0, 2) ?> ₴</strong>
                            </div>
                        </div>

                        <button id="checkout-btn" class="primary-btn">
                            <i class="fas fa-check"></i>
                            Оформити замовлення
                        </button>
                    </div>

                    <div class="order-actions">
                        <a href="/BuildMaster/calculator" class="secondary-btn">
                            <i class="fas fa-arrow-left"></i>
                            Назад до калькулятора
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Модальне вікно оформлення замовлення -->
        <div id="checkout-modal" class="modal">
            <div class="modal-content large">
                <div class="modal-header">
                    <h3>Оформлення замовлення</h3>
                    <button class="close-btn" id="close-checkout-modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="checkout-form">
                        <div class="form-group">
                            <label for="guest-name">Ім'я *</label>
                            <input type="text" id="guest-name" placeholder="Введіть ваше ім'я" required>
                        </div>
                        <div class="form-group">
                            <label for="guest-email">Email *</label>
                            <input type="email" id="guest-email" placeholder="Введіть ваш email" required>
                        </div>
                        <div class="form-group">
                            <label for="guest-phone">Телефон *</label>
                            <input type="tel" id="guest-phone" placeholder="+380 XX XXX XX XX" required>
                        </div>
                        <div class="form-group">
                            <label for="order-notes">Додаткові примітки</label>
                            <textarea id="order-notes" placeholder="Додаткова інформація або побажання..." rows="4"></textarea>
                        </div>
                    </form>

                    <div class="order-final-summary">
                        <h4>Підсумок замовлення:</h4>
                        <div class="summary-items">
                            <?php if (!empty($orderRooms)): ?>
                                <?php foreach ($orderRooms as $room): ?>
                                    <div class="summary-item">
                                        <span><?= htmlspecialchars($room['room_name'] ?? 'Без назви') ?></span>
                                        <span><?= number_format($room['room_total_cost'] ?? 0, 2) ?> ₴</span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="final-total">
                            <span>Загальна вартість: <strong><?= number_format($totalAmount ?? 0, 2) ?> ₴</strong></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="cancel-checkout" class="secondary-btn">Скасувати</button>
                    <button id="confirm-checkout" class="primary-btn">
                        <i class="fas fa-check"></i>
                        Підтвердити замовлення
                    </button>
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
                <div class="modal-footer">
                    <button id="close-error-btn" class="primary-btn">Зрозуміло</button>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="/BuildMaster/UI/js/order-rooms.js"></script>
</body>
</html>