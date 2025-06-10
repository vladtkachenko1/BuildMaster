
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
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-calculator"></i>
                <h1>Калькулятор ремонту</h1>
            </div>
            <p class="subtitle">Розрахуйте вартість вашого ремонту за кілька кроків</p>
        </div>
    </header>

    <!-- Main Content -->
    <main class="calculator-main">
        <!-- Welcome Screen -->
        <div id="welcome-screen" class="screen active">
            <div class="welcome-content">
                <div class="welcome-icon">
                    <i class="fas fa-home"></i>
                </div>
                <h2>Ласкаво просимо!</h2>
                <p>Наш калькулятор допоможе вам швидко розрахувати приблизну вартість ремонтних робіт для вашого приміщення.</p>
                <div class="features">
                    <div class="feature">
                        <i class="fas fa-clock"></i>
                        <span>Швидкий розрахунок</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-chart-line"></i>
                        <span>Точна оцінка</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-mobile-alt"></i>
                        <span>Зручний інтерфейс</span>
                    </div>
                </div>
                <button id="create-project-btn" class="primary-btn">
                    <i class="fas fa-plus"></i> Створити проект
                </button>

            </div>
        </div>

        <!-- Container for dynamic content -->
        <div id="screens-container"></div>

        <!-- Loading Screen -->
        <div id="loading-screen" class="screen">
            <div class="loading-content">
                <div class="loading-spinner">
                    <div class="spinner"></div>
                </div>
                <h3>Обробляємо ваші дані...</h3>
                <p>Будь ласка, зачекайте</p>
            </div>
        </div>
    </main>

    <!-- Error Modal -->
    <div id="error-modal" class="modal">
        <div class="modal-content error-modal">
            <div class="modal-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Помилка</h3>
            </div>
            <div class="modal-body">
                <p id="error-message"></p>
            </div>
            <div class="modal-actions">
                <button id="close-error-btn" class="secondary-btn">Закрити</button>
            </div>
        </div>
    </div>
</div>

<script src="/BuildMaster/UI/js/calculator.js"></script>
</body>
</html>