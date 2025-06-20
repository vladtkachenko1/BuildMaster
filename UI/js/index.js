// Utility functions - place at the top
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

    if (!/^\+?\d{9,15}$/.test(phone)) {
        showError("Введіть дійсний номер телефону (лише цифри, від 9 до 15 символів).", errorTargetId);
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
}