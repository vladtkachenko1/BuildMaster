<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BuildMaster - Професійні будівельні та ремонтні послуги</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="UI/css/index.css">
</head>
<body>
<!-- Navigation -->
<nav class="navbar" id="navbar">
    <div class="nav-container">
        <div class="logo">BuildMaster</div>
        <ul class="nav-links">
            <li><a href="#home">Головна</a></li>
            <li><a href="#services">Послуги</a></li>
            <li><a href="#portfolio">Портфоліо</a></li>
            <li><a href="#about">Про нас</a></li>
            <li><a href="#contact">Контакти</a></li>
        </ul>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero" id="home">
    <div class="floating-elements">
        <i class="fas fa-hammer floating-element"></i>
        <i class="fas fa-tools floating-element"></i>
        <i class="fas fa-home floating-element"></i>
    </div>

    <div class="hero-content">
        <h1>Професійні будівельні та ремонтні послуги</h1>
        <p>Перетворюємо ваші мрії про ідеальний дім на реальність з гарантією якості та в строк</p>
        <div class="cta-buttons">
            <button class="btn btn-primary" onclick="openModal()">
                <i class="fas fa-phone"></i>
                Зв'язатись з менеджером
            </button>
            <a href="#calculator" class="btn btn-secondary">
                <i class="fas fa-calculator"></i>
                Розрахувати вартість
            </a>
        </div>
    </div>
</section>

<!-- Services Section -->
<section class="services" id="services">
    <div class="container">
        <h2 class="section-title">Наші послуги</h2>
        <p class="section-subtitle">Повний спектр будівельних та ремонтних робіт для вашого комфорту</p>

        <div class="services-grid">
            <div class="service-card">
                <i class="fas fa-bath service-icon"></i>
                <h3>Ремонт ванних кімнат</h3>
                <p>Повний комплекс робіт: від демонтажу до фінішного оздоблення. Сучасні матеріали та професійний підхід.</p>
            </div>

            <div class="service-card">
                <i class="fas fa-utensils service-icon"></i>
                <h3>Ремонт кухонь</h3>
                <p>Створюємо функціональні та красиві кухні. Укладання плитки, встановлення техніки, дизайн-проекти.</p>
            </div>

            <div class="service-card">
                <i class="fas fa-bed service-icon"></i>
                <h3>Ремонт спалень</h3>
                <p>Затишні та комфортні спальні кімнати. Оздоблення стін, монтаж освітлення, дизайнерські рішення.</p>
            </div>

            <div class="service-card">
                <i class="fas fa-couch service-icon"></i>
                <h3>Ремонт вітальень</h3>
                <p>Стильні простори для відпочинку та прийому гостей. Сучасне оздоблення та меблювання.</p>
            </div>

            <div class="service-card">
                <i class="fas fa-paint-roller service-icon"></i>
                <h3>Малярні роботи</h3>
                <p>Професійне фарбування стін та стель. Широкий вибір матеріалів та кольорових рішень.</p>
            </div>

            <div class="service-card">
                <i class="fas fa-hammer service-icon"></i>
                <h3>Загальнобудівельні роботи</h3>
                <p>Капітальний ремонт, перепланування, встановлення перегородок та інші будівельні послуги.</p>
            </div>
        </div>
    </div>
</section>

<!-- Portfolio Section -->
<section class="portfolio" id="portfolio">
    <div class="container">
        <h2 class="section-title" style="color: white;">Наші роботи</h2>
        <p class="section-subtitle" style="color: rgba(255,255,255,0.8);">Портфоліо завершених проектів наших клієнтів</p>

        <div class="portfolio-slider">
            <div class="portfolio-slide active">
                <img src="https://images.unsplash.com/photo-1586023492125-27b2c045efd7?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80" alt="Сучасна ванна кімната">
                <div class="portfolio-info">
                    <h3>Сучасна ванна кімната</h3>
                    <p>Повний ремонт ванної кімнати в мінімалістичному стилі з використанням натурального каменю та сучасної сантехніки.</p>
                </div>
            </div>

            <div class="portfolio-slide">
                <img src="https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80" alt="Елегантна кухня">
                <div class="portfolio-info">
                    <h3>Елегантна кухня</h3>
                    <p>Кухня-студія з островом та сучасними фасадами. Інтеграція техніки та функціональне зонування простору.</p>
                </div>
            </div>

            <div class="portfolio-slide">
                <img src="https://images.unsplash.com/photo-1586047844297-d9f85ee3d5a6?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80" alt="Затишна спальня">
                <div class="portfolio-info">
                    <h3>Затишна спальня</h3>
                    <p>Спальня в скандинавському стилі з м'якими тонами та природними матеріалами для максимального комфорту.</p>
                </div>
            </div>
        </div>

        <div class="slider-nav">
            <span class="slider-dot active" onclick="currentSlide(1)"></span>
            <span class="slider-dot" onclick="currentSlide(2)"></span>
            <span class="slider-dot" onclick="currentSlide(3)"></span>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="stats">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-item">
                <h3 data-count="150">0</h3>
                <p>Завершених проектів</p>
            </div>
            <div class="stat-item">
                <h3 data-count="200">0</h3>
                <p>Задоволених клієнтів</p>
            </div>
            <div class="stat-item">
                <h3 data-count="8">0</h3>
                <p>Років досвіду</p>
            </div>
            <div class="stat-item">
                <h3 data-count="12">0</h3>
                <p>Фахівців у команді</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content">
            <h2>Готові розпочати ваш проєкт?</h2>
            <p>Зв'яжіться з нами для безкоштовної консультації та розрахунку вартості</p>
            <div class="cta-buttons">
                <button class="btn btn-primary" onclick="openModal()">
                    <i class="fas fa-phone"></i>
                    Зв'язатись зараз
                </button>
                <a href="#calculator" class="btn btn-secondary">
                    <i class="fas fa-calculator"></i>
                    Калькулятор вартості
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Contact Modal -->
<div id="contactModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Зв'яжіться з нами</h2>
        <form id="contactForm">
            <div class="form-group">
                <label for="name">Ваше ім'я *</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="phone">Телефон *</label>
                <input type="tel" id="phone" name="phone" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email">
            </div>
            <div class="form-group">
                <label for="message">Повідомлення</label>
                <textarea id="message" name="message" rows="4" placeholder="Розкажіть про ваш проект..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">
                <i class="fas fa-paper-plane"></i>
                Відправити повідомлення
            </button>
        </form>
    </div>
</div>

</body>
</html>