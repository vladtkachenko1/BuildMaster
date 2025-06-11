<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user']) && $_SESSION['user']['authenticated'] === true;
$isAdmin = $isLoggedIn && isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'] === true;
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BuildMaster - Професійні будівельні та ремонтні послуги</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/BuildMaster/UI/css/home.css">
</head>
<body>

<!-- Navigation -->
<nav class="navbar" id="navbar">
    <div class="nav-container">
        <div class="logo">BuildMaster</div>
        <ul class="nav-links">
            <?php if ($isAdmin): ?>
                <li><a href="#" id="adminpanel"><i class="fas fa-cogs"></i> Адмін панель</a></li>
            <?php endif; ?>
            <li><a href="#home">Головна</a></li>
            <li><a href="#services">Послуги</a></li>
            <li><a href="#portfolio">Портфоліо</a></li>
            <li><a href="#about">Про нас</a></li>
            <li><a href="#contact">Контакти</a></li>
            <?php if ($isLoggedIn): ?>
                <li><a href="http://localhost/BuildMaster/users-orders"></i> Мої замовлення</a></li>
                <li><a href="#" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Вийти</a></li>
            <?php else: ?>
                <li><a href="#" class="login-btn" onclick="openAuthModal('navbar')"><i class="fas fa-user"></i> Увійти</a></li>
            <?php endif; ?>
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

        <div class="hero-features">
            <div class="hero-feature">
                <i class="fas fa-award"></i>
                <h3>Гарантія якості</h3>
                <p>Всі роботи виконуються з гарантією від 2 років</p>
            </div>
            <div class="hero-feature">
                <i class="fas fa-clock"></i>
                <h3>Дотримання термінів</h3>
                <p>Завершуємо проекти точно в обумовлений час</p>
            </div>
            <div class="hero-feature">
                <i class="fas fa-users"></i>
                <h3>Досвідчена команда</h3>
                <p>8 років досвіду та 200+ задоволених клієнтів</p>
            </div>
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
                    Зв'язатись з менеджером
                </button>
                <button id="calculateBtn" class="btn btn-secondary">
                    <i class="fas fa-calculator"></i>
                    Розрахувати вартість
                </button>
            </div>
        </div>
    </div>
</section>

<!-- Звязатись з менеджером -->
<div id="contactModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Зв'яжіться з нами</h2>
        <form id="contactForm" method="post">
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
        <div id="contactFormError" class="error-message" style="display: none;"></div>
    </div>
</div>

<!-- Вікно входу -->
<div id="loginModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeAuthModal()">&times;</span>
        <h2>Вхід в систему</h2>
        <form id="loginFormElement">
            <div class="form-group">
                <label for="loginEmail">Email *</label>
                <input type="email" id="loginEmail" name="email" required>
            </div>
            <div class="form-group">
                <label for="loginPassword">Пароль *</label>
                <input type="password" id="loginPassword" name="password" required>
            </div>
            <button type="submit">Увійти</button>
        </form>
        <p class="switch-link">Немає акаунту? <a href="#" onclick="switchToRegister()">Зареєструватися</a></p>
        <div id="loginErrorMsg" class="error-message" style="display: none;"></div>
    </div>
</div>

<!-- Вікно реєстрації -->
<div id="registerModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeRegisterModal()">&times;</span>
        <h2>Реєстрація</h2>
        <form id="registerFormElement">
            <div id="registerErrorMsg" class="error-message" style="display:none;"></div>

            <div class="form-group">
                <label for="registerFirstName">Ім'я *</label>
                <input type="text" id="registerFirstName" name="first_name" required>
            </div>
            <div class="form-group">
                <label for="registerLastName">Прізвище *</label>
                <input type="text" id="registerLastName" name="last_name" required>
            </div>
            <div class="form-group">
                <label for="registerEmail">Email *</label>
                <input type="email" id="registerEmail" name="email" required>
            </div>
            <div class="form-group">
                <label for="registerPhone">Телефон</label>
                <input type="tel" id="registerPhone" name="phone">
            </div>
            <div class="form-group">
                <label for="registerPassword">Пароль *</label>
                <input type="password" id="registerPassword" name="password" required>
            </div>
            <button type="submit">Зареєструватися</button>
            <div id="registerErrorMsg" class="error-message" style="display: none;"></div>
        </form>
        <p class="switch-link"><a href="#" onclick="switchToLogin()">← Повернутися до входу</a></p>
    </div>
</div>

<script >// Utility functions - place at the top
    function showError(message, errorElementId = 'errorMessage', duration = 5000) {
        const errorElement = document.getElementById(errorElementId);

        if (!errorElement) {
            console.error(`Елемент з ID "${errorElementId}" не знайдено`);
            // Як резервний варіант - показати alert
            if (message) {
                alert(message);
            }
            return;
        }

        // Очищення попереднього таймера, якщо він існує
        if (errorElement.hideTimer) {
            clearTimeout(errorElement.hideTimer);
        }

        if (!message || message.trim() === '') {
            // Приховати помилку, якщо повідомлення порожнє
            errorElement.style.display = 'none';
            errorElement.textContent = '';
            errorElement.classList.remove('show', 'fade-in');
            return;
        }

        // Встановити текст помилки
        errorElement.textContent = message;

        // Показати елемент з анімацією
        errorElement.style.display = 'block';
        errorElement.classList.add('show', 'fade-in');

        // Автоматично приховати через заданий час
        if (duration > 0) {
            errorElement.hideTimer = setTimeout(() => {
                hideError(errorElementId);
            }, duration);
        }
    }

    /**
     * Приховання повідомлення про помилку з анімацією
     * @param {string} errorElementId - ID елемента помилки
     */
    function hideError(errorElementId = 'errorMessage') {
        const errorElement = document.getElementById(errorElementId);

        if (!errorElement) {
            return;
        }

        // Додати клас для анімації зникнення
        errorElement.classList.add('fade-out');
        errorElement.classList.remove('fade-in');

        // Приховати елемент після анімації
        setTimeout(() => {
            errorElement.style.display = 'none';
            errorElement.textContent = '';
            errorElement.classList.remove('show', 'fade-out');

            // Очистити таймер
            if (errorElement.hideTimer) {
                clearTimeout(errorElement.hideTimer);
                errorElement.hideTimer = null;
            }
        }, 300); // 300мс - час анімації
    }

    /**
     * Показати повідомлення про успіх
     * @param {string} message - Текст повідомлення
     * @param {string} elementId - ID елемента для відображення
     * @param {number} duration - Час показу в мілісекундах
     */
    function showSuccess(message, elementId = 'successMessage', duration = 3000) {
        const element = document.getElementById(elementId);

        if (!element) {
            console.error(`Елемент з ID "${elementId}" не знайдено`);
            alert(message);
            return;
        }

        if (element.hideTimer) {
            clearTimeout(element.hideTimer);
        }

        element.textContent = message;
        element.style.display = 'block';
        element.classList.add('show', 'fade-in');
        element.classList.remove('fade-out');

        if (duration > 0) {
            element.hideTimer = setTimeout(() => {
                element.classList.add('fade-out');
                element.classList.remove('fade-in');

                setTimeout(() => {
                    element.style.display = 'none';
                    element.classList.remove('show', 'fade-out');
                }, 300);
            }, duration);
        }
    }

    // Rest of your existing code continues here...
    window.addEventListener('scroll', () => {
        const navbar = document.getElementById('navbar');
        navbar.classList.toggle('scrolled', window.scrollY > 50);
    });

    document.addEventListener('DOMContentLoaded', () => {
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', async (e) => {
                e.preventDefault();
                try {
                    const response = await fetch('/BuildMaster/Controllers/AuthController.php?action=logout', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const data = await response.json();
                    if (data.success) {
                        location.reload();
                    }
                } catch (error) {
                    alert('Помилка при виході');
                }
            });
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        const adminLink = document.getElementById('adminpanel');
        if (adminLink) {
            adminLink.addEventListener('click', function(event) {
                event.preventDefault();
                window.location.href = '/BuildMaster/admin';
            });
        }
    });

    let currentSlideIndex = 0;
    const slides = document.querySelectorAll('.portfolio-slide');
    const dots = document.querySelectorAll('.slider-dot');

    function showSlide(index) {
        slides.forEach(slide => slide.classList.remove('active'));
        dots.forEach(dot => dot.classList.remove('active'));
        slides[index].classList.add('active');
        dots[index].classList.add('active');
    }

    function currentSlide(index) {
        currentSlideIndex = index - 1;
        showSlide(currentSlideIndex);
    }

    setInterval(() => {
        currentSlideIndex = (currentSlideIndex + 1) % slides.length;
        showSlide(currentSlideIndex);
    }, 5000);

    function animateCounters() {
        const counters = document.querySelectorAll('[data-count]');

        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-count'));
            const increment = target / 100;
            let current = 0;

            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    counter.textContent = target;
                    clearInterval(timer);
                } else {
                    counter.textContent = Math.ceil(current);
                }
            }, 30);
        });
    }

    const statsSection = document.querySelector('.stats');
    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounters();
                counterObserver.unobserve(entry.target);
            }
        });
    });
    if (statsSection) {
        counterObserver.observe(statsSection);
    }

    function openModal() {
        document.getElementById('contactModal').classList.add('active');
    }

    function closeModal() {
        document.getElementById('contactModal').classList.remove('active');
    }

    document.addEventListener('click', function(event) {
        const modals = ['contactModal', 'loginModal', 'registerModal'];

        modals.forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (event.target === modal) {
                modal.classList.remove('active');
            }
        });
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const activeModals = document.querySelectorAll('.modal.active');
            activeModals.forEach(modal => {
                modal.classList.remove('active');
            });
        }
    });

    window.addEventListener('click', function (event) {
        const modal = document.getElementById('contactModal');
        if (event.target === modal) {
            closeModal();
        }
    });

    document.getElementById('contactForm')?.addEventListener('submit', async function (e) {
        e.preventDefault();

        const form = this;
        const formData = new FormData(form);
        const name = form.name.value.trim();
        const phone = form.phone.value.trim();
        const email = form.email.value.trim();
        const errorTargetId = 'contactFormError';

        showError('', errorTargetId);

        if (name.length < 2) {
            showError("Будь ласка, введіть коректне ім'я (не менше 2 символів).", errorTargetId);
            return;
        }

        const cleanPhone = phone.replace(/[\s\-\(\)]+/g, '');
        if (!/^\+?\d{9,15}$/.test(cleanPhone)) {
            showError("Введіть дійсний номер телефону.", errorTargetId);
            return;
        }



        if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showError("Введіть коректний email або залиште це поле порожнім.", errorTargetId);
            return;
        }

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Відправляється...';
        submitBtn.disabled = true;

        try {
            const response = await fetch('controllers/contact-handler.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error('HTTP error ' + response.status);
            }

            const result = await response.json();

            if (result.success) {
                alert('Дякуємо! Ваше повідомлення відправлено. Ми зв\'яжемося з вами найближчим часом.');
                form.reset();
                closeModal?.();
            } else {
                showError(result.message || 'Помилка при відправці повідомлення. Спробуйте ще раз.', errorTargetId);
            }
        } catch (error) {
            console.error('Error:', error);
            showError('Сталася помилка при зєднанні з сервером.', errorTargetId);
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });

    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            if (href && href !== '#') {
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });

    window.addEventListener('scroll', () => {
        const parallax = document.querySelector('.hero');
        const speed = window.pageYOffset * 0.5;
        if (parallax) {
            parallax.style.transform = `translateY(${speed}px)`;
        }
    });

    const fadeOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -100px 0px'
    };

    const fadeObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, fadeOptions);

    document.querySelectorAll('.service-card, .section-title, .section-subtitle').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        fadeObserver.observe(el);
    });

    document.getElementById('phone')?.addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.startsWith('380')) {
            value = value.substring(3);
        }
        if (value.length > 0) {
            if (value.length <= 3) {
                value = `+380 (${value}`;
            } else if (value.length <= 6) {
                value = `+380 (${value.substring(0, 3)}) ${value.substring(3)}`;
            } else if (value.length <= 8) {
                value = `+380 (${value.substring(0, 3)}) ${value.substring(3, 6)}-${value.substring(6)}`;
            } else {
                value = `+380 (${value.substring(0, 3)}) ${value.substring(3, 6)}-${value.substring(6, 8)}-${value.substring(8, 10)}`;
            }
        }
        e.target.value = value;
    });

    window.addEventListener('load', () => {
        document.body.classList.add('loaded');
    });

    let loginContext = null;

    function openAuthModal(context = null) {
        loginContext = context;
        const loginModal = document.getElementById('loginModal');
        if (loginModal) {
            loginModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
    }

    function closeAuthModal() {
        const loginModal = document.getElementById('loginModal');
        if (loginModal) {
            loginModal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }

    function openRegisterModal() {
        document.getElementById('registerModal').style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    function closeRegisterModal() {
        document.getElementById('registerModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    function switchToRegister() {
        closeAuthModal();
        openRegisterModal();
    }

    function switchToLogin() {
        closeRegisterModal();
        openAuthModal();
    }

    document.getElementById('loginFormElement')?.addEventListener('submit', async function (e) {
        e.preventDefault();

        const emailInput = document.getElementById('loginEmail');
        const passwordInput = document.getElementById('loginPassword');
        const email = emailInput.value.trim();
        const password = passwordInput.value;

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!emailRegex.test(email)) {
            showError('Будь ласка, введіть коректний email.', 'loginErrorMsg');
            emailInput.focus();
            return;
        }

        if (password.length < 6) {
            showError('Пароль має містити щонайменше 6 символів.', 'loginErrorMsg');
            passwordInput.focus();
            return;
        }

        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Вхід...';
        submitBtn.disabled = true;

        try {
            const response = await fetch('/BuildMaster/Controllers/AuthController.php?action=login', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            const result = await response.json();

            if (response.ok && result.success) {
                closeAuthModal();

                if (loginContext === 'calculator') {
                    window.location.href = '/calculator.php';
                } else {
                    window.location.reload();
                }
            } else {
                showError(result.error || 'Помилка при вході в систему', 'loginErrorMsg');
            }
        } catch (error) {
            console.error('Error:', error);
            showError('Помилка при вході. Спробуйте ще раз.', 'loginErrorMsg');
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });

    document.getElementById('registerFormElement')?.addEventListener('submit', async function (e) {
        e.preventDefault();

        const firstNameInput = document.getElementById('registerFirstName');
        const lastNameInput = document.getElementById('registerLastName');
        const emailInput = document.getElementById('registerEmail');
        const phoneInput = document.getElementById('registerPhone');
        const passwordInput = document.getElementById('registerPassword');

        const firstName = firstNameInput.value.trim();
        const lastName = lastNameInput.value.trim();
        const email = emailInput.value.trim();
        const phone = phoneInput.value.trim();
        const password = passwordInput.value;

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const phoneRegex = /^[0-9+\-\s()]*$/;

        if (!firstName) {
            showError('Будь ласка, введіть імя.', 'registerErrorMsg');
            firstNameInput.focus();
            return;
        }

        if (!lastName) {
            showError('Будь ласка, введіть прізвище.', 'registerErrorMsg');
            lastNameInput.focus();
            return;
        }

        if (!emailRegex.test(email)) {
            showError('Введіть коректну email-адресу.', 'registerErrorMsg');
            emailInput.focus();
            return;
        }

        if (phone && !phoneRegex.test(phone)) {
            showError('Введіть коректний номер телефону.', 'registerErrorMsg');
            phoneInput.focus();
            return;
        }

        if (password.length < 6) {
            showError('Пароль має містити щонайменше 6 символів.', 'registerErrorMsg');
            passwordInput.focus();
            return;
        }

        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Реєстрація...';
        submitBtn.disabled = true;

        try {
            const response = await fetch('/BuildMaster/Controllers/AuthController.php?action=register', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            const result = await response.json();
            console.log(result);

            if (response.ok && result.success) {
                alert('Реєстрація пройшла успішно. Тепер можете увійти.');
                closeRegisterModal();
                openAuthModal();
            } else {
                showError(result.error || 'Помилка при реєстрації', 'registerErrorMsg');
            }
        } catch (error) {
            console.error('Error:', error);
            showError('Помилка при реєстрації. Спробуйте ще раз.', 'registerErrorMsg');
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });

    const loginBtn = document.querySelector('.login-btn');
    if (loginBtn) {
        loginBtn.addEventListener('click', (e) => {
            e.preventDefault();
            openAuthModal();
        });
    }

    document.getElementById('calculateBtn')?.addEventListener('click', (e) => {
        e.preventDefault();
        checkAuthAndRedirect();
    });

    function checkAuthAndRedirect() {
        fetch('/BuildMaster/Controllers/AuthController.php?action=check', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(response => response.json())
            .then(data => {
                if (data.authenticated) {
                    window.location.href = '/BuildMaster/Calculator';
                } else {
                    openAuthModal('calculator');
                }
            })
            .catch(error => {
                console.error('Auth check error:', error);
                openAuthModal('calculator');
            });
    }

    function logout() {
        fetch('/BuildMaster/Controllers/AuthController.php?action=logout', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.redirect) {
                    window.location.href = data.redirect;
                }
            })
            .catch(error => {
                console.error('Error logging out:', error);
                window.location.href = '/BuildMaster/';
            });
    }

    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            logout();
        });
    }</script>

</body>
</html>