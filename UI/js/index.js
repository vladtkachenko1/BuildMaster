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

// Trigger counter animation on scroll
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
    document.getElementById('contactModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('contactModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

window.addEventListener('click', function (event) {
    const modal = document.getElementById('contactModal');
    if (event.target === modal) {
        closeModal();
    }
});

document.getElementById('contactForm')?.addEventListener('submit', async function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;

    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Відправляється...';
    submitBtn.disabled = true;

    try {
        const response = await fetch('controllers/HomeController.php?action=contact', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            alert('Дякуємо! Ваше повідомлення відправлено. Ми зв\'яжемося з вами найближчим часом.');
            this.reset();
            closeModal();
        } else {
            alert('Помилка при відправці повідомлення. Спробуйте ще раз.');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Помилка при відправці повідомлення. Спробуйте ще раз.');
    } finally {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
});

document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const href = this.getAttribute('href');
        if (href && href !== '#') {  // перевірка, що href валідний
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

// Fade-in animation on scroll
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
// Page load animation
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

// Submit форми входу
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
            showError(result.error || 'Помилка при вході в систему');
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Помилка при вході. Спробуйте ще раз.');
    } finally {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
});

//Submit форми реєстрації
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
        showError('Будь ласка, введіть ім’я.', 'registerErrorMsg');
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
            alert(result.error || 'Помилка при реєстрації');
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Помилка при реєстрації. Спробуйте ще раз.');
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


document.getElementById('calculateBtn').addEventListener('click', (e) => {
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




//Вихід з системи
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
            window.location.href = '/BuildMaster/';  // або '/' якщо корінь
        });
}

const logoutBtn = document.getElementById('logoutBtn');
if (logoutBtn) {
    logoutBtn.addEventListener('click', function(e) {
        e.preventDefault();
        logout();
    });
}

function showError(message, targetId) {
    const el = document.getElementById(targetId);
    if (!el) {
        console.warn(`showError: Element with id "${targetId}" not found.`);
        return;
    }

    el.textContent = message;
    el.style.display = 'block';

    requestAnimationFrame(() => {
        el.style.opacity = '1';
    });

    setTimeout(() => {
        el.style.opacity = '0';
        setTimeout(() => {
            el.style.display = 'none';
            el.textContent = '';
        }, 300);
    }, 3000);
}


