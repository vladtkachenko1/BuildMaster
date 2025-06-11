
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Калькулятор вартості ремонту - BuildMaster</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/BuildMaster/UI/css/calculator.css">
</head>
<body>
<div class="calculator-container">
    <!-- Header -->
    <header class="calculator-header">
        <button id="back-btn" class="back-btn">
            <i class="fas fa-arrow-left"></i>
        </button>
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-calculator"></i>
                <h1>Калькулятор ремонту</h1>
            </div>
            <p class="subtitle">Розрахуйте вартість вашого ремонту за кілька кроків</p>
        </div>
    </header>
<main class="calculator-main">
    <div id="project-form-screen" class="screen active">
        <div class="form-container">
            <div class="form-header">

                <h2>Створення проекту</h2>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 33%"></div>
                </div>
            </div>

            <form id="project-form" class="project-form">
                <div class="form-group">
                    <label for="room-type">
                        <i class="fas fa-door-open"></i>
                        Тип кімнати
                    </label>
                    <div class="select-wrapper">
                        <select id="room-type" name="room_type_id" required>
                            <option value="">Оберіть тип кімнати...</option>
                        </select>
                        <i class="fas fa-chevron-down select-arrow"></i>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="wall-area">
                            <i class="fas fa-expand-arrows-alt"></i>
                            Площа стін (м²)
                        </label>
                        <input type="number" id="wall-area" name="wall_area"
                               step="0.1" min="0.1" placeholder="Введіть площу стін" required>
                    </div>

                    <div class="form-group">
                        <label for="room-area">
                            <i class="fas fa-vector-square"></i>
                            Площа кімнати (м²)
                        </label>
                        <input type="number" id="room-area" name="room_area"
                               step="0.1" min="0.1" placeholder="Введіть площу кімнати" required>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="primary-btn">
                        <i class="fas fa-arrow-right"></i>
                        Далі
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

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

<script src="/BuildMaster/UI/js/calculators.js"></script>
</body>
</html>