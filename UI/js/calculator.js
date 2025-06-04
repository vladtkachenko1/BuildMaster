// Calculator JavaScript - Modern and Interactive

class CalculatorApp {
    constructor() {
        this.currentScreen = 'welcome-screen';
        this.roomTypes = [];
        this.basePath = '/BuildMaster'; // Додаємо базовий шлях
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadRoomTypes();

        setTimeout(() => {
            this.populateRoomTypeSelect();
        }, 1000);
    }

    bindEvents() {
        const createProjectBtn = document.getElementById("create-project-btn");
        if (createProjectBtn) {
            createProjectBtn.addEventListener("click", (event) => {
                event.preventDefault();
                window.location.href = this.basePath + "/calculator/project-form";
            });
        }

        document.addEventListener("click", (e) => {
            if (e.target && e.target.id === "back-btn") {
                this.goBack();
            }
        });

        const closeErrorBtn = document.getElementById('close-error-btn');
        if (closeErrorBtn) {
            closeErrorBtn.addEventListener('click', () => {
                this.hideModal('error-modal');
            });
        }

        const projectForm = document.getElementById('project-form');
        if (projectForm) {
            projectForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleFormSubmit();
            });
        }

        this.setupInputAnimations();

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.hideModal('error-modal');
            }
        });
    }

    goBack() {
        // Простіший спосіб повернення назад
        window.history.back();
    }

    setupInputAnimations() {
        const inputs = document.querySelectorAll('input, select');

        inputs.forEach(input => {
            input.addEventListener('focus', () => {
                input.parentElement.classList.add('focused');
            });

            input.addEventListener('blur', () => {
                input.parentElement.classList.remove('focused');
            });

            input.addEventListener('input', () => {
                if (input.value) {
                    input.parentElement.classList.add('has-value');
                } else {
                    input.parentElement.classList.remove('has-value');
                }
            });
        });
    }

    async loadRoomTypes() {
        try {
            const response = await fetch(this.basePath + '/calculator/room-types', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP помилка! статус: ${response.status}`);
            }

            const text = await response.text();

            if (!text.trim()) {
                throw new Error('Сервер повернув порожню відповідь');
            }

            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                throw new Error('Помилка при розборі JSON: ' + e.message + '\nСервер відповів:\n' + text);
            }

            if (data.success && Array.isArray(data.data)) {
                this.roomTypes = data.data;
                console.log('Типи кімнат завантажено:', this.roomTypes);
                this.populateRoomTypeSelect();
            } else {
                this.showError(data.error || 'Помилка завантаження типів кімнат');
            }
        } catch (error) {
            console.error('Помилка при завантаженні типів кімнат:', error);
            this.showError(error.message || 'Помилка з\'єднання з сервером');
        }
    }

    populateRoomTypeSelect() {
        const select = document.getElementById('room-type');
        if (!select || this.roomTypes.length === 0) {
            console.log('Select елемент не знайдено або типи кімнат не завантажені');
            return;
        }

        // Clear existing options except the first one
        while (select.children.length > 1) {
            select.removeChild(select.lastChild);
        }

        // Add room types
        this.roomTypes.forEach(roomType => {
            const option = document.createElement('option');
            option.value = roomType.id;
            option.textContent = roomType.name;
            option.dataset.slug = roomType.slug;
            select.appendChild(option);
        });

        // Add animation effect
        select.classList.add('loaded');
    }

    async handleFormSubmit() {
        const form = document.getElementById('project-form');
        const formData = new FormData(form);

        // Validate form
        if (!this.validateForm(formData)) {
            return;
        }

        // Show loading indicator
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Створення...';
        submitBtn.disabled = true;

        try {
            const response = await fetch(this.basePath + '/calculator/create', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP помилка! статус: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                // Переходимо на сторінку вибору послуг замість materials
                const roomTypeId = formData.get('room_type_id');
                const wallArea = formData.get('wall_area');
                const roomArea = formData.get('room_area');

                const redirectUrl = `${this.basePath}/calculator/services-selection?room_type_id=${encodeURIComponent(roomTypeId)}&wall_area=${encodeURIComponent(wallArea)}&room_area=${encodeURIComponent(roomArea)}`;

                setTimeout(() => {
                    window.location.href = redirectUrl;
                }, 1500);
            } else {
                this.showError(data.error || 'Помилка створення проекту');
            }
        } catch (error) {
            console.error('Error submitting form:', error);
            this.showError('Помилка з\'єднання з сервером: ' + error.message);
        } finally {
            // Restore button
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    }

    validateForm(formData) {
        const roomTypeId = formData.get('room_type_id');
        const wallArea = parseFloat(formData.get('wall_area'));
        const roomArea = parseFloat(formData.get('room_area'));

        if (!roomTypeId) {
            this.showError('Будь ласка, оберіть тип кімнати');
            this.focusField('room-type');
            return false;
        }

        if (!wallArea || wallArea <= 0) {
            this.showError('Будь ласка, введіть коректну площу стін');
            this.focusField('wall-area');
            return false;
        }

        if (!roomArea || roomArea <= 0) {
            this.showError('Будь ласка, введіть коректну площу кімнати');
            this.focusField('room-area');
            return false;
        }

        if (wallArea < roomArea) {
            this.showError('Площа стін не може бути менше площі кімнати');
            this.focusField('wall-area');
            return false;
        }

        return true;
    }

    focusField(fieldId) {
        const field = document.getElementById(fieldId);
        if (field) {
            setTimeout(() => {
                field.focus();
                if (field.select) field.select();
            }, 100);
        }
    }

    showScreen(screenId) {
        // Hide current screen
        const currentScreen = document.getElementById(this.currentScreen);
        if (currentScreen) {
            currentScreen.classList.remove('active');
        }

        // Show new screen with animation
        setTimeout(() => {
            const newScreen = document.getElementById(screenId);
            if (newScreen) {
                newScreen.classList.add('active');
                this.currentScreen = screenId;

                // Update progress bar if on form screen
                if (screenId === 'project-form-screen') {
                    this.updateProgress(33);
                }

                // Focus first input if form screen
                if (screenId === 'project-form-screen') {
                    setTimeout(() => {
                        const firstInput = newScreen.querySelector('select, input');
                        if (firstInput) firstInput.focus();
                    }, 300);
                }
            }
        }, 150);
    }

    updateProgress(percentage) {
        const progressFill = document.querySelector('.progress-fill');
        if (progressFill) {
            progressFill.style.width = percentage + '%';
        }
    }

    showError(message) {
        if (!document.getElementById('error-modal')) {
            alert(message);
            return;
        }

        const errorModal = document.getElementById('error-modal');
        const errorMessage = document.getElementById('error-message');

        if (errorModal && errorMessage) {
            errorMessage.textContent = message;
            this.showModal('error-modal');
        }
    }

    showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';

            // Focus trap for accessibility
            const focusableElements = modal.querySelectorAll(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            );
            if (focusableElements.length > 0) {
                focusableElements[0].focus();
            }
        }
    }

    hideModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    animateValue(element, start, end, duration) {
        const startTime = performance.now();
        const change = end - start;

        const animate = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);

            const easeOutCubic = 1 - Math.pow(1 - progress, 3);
            const current = start + (change * easeOutCubic);

            element.textContent = Math.round(current);

            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };

        requestAnimationFrame(animate);
    }

    // Add ripple effect to buttons
    addRippleEffect() {
        const buttons = document.querySelectorAll('.primary-btn, .secondary-btn');

        buttons.forEach(button => {
            button.addEventListener('click', (e) => {
                const ripple = document.createElement('span');
                const rect = button.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;

                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                ripple.classList.add('ripple');

                button.appendChild(ripple);

                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
    }
}

class FormValidator {
    static validateNumber(value, min = 0, max = Infinity) {
        const num = parseFloat(value);
        return !isNaN(num) && num >= min && num <= max;
    }

    static validateRequired(value) {
        return value && value.toString().trim().length > 0;
    }

    static formatNumber(value, decimals = 1) {
        return parseFloat(value).toFixed(decimals);
    }
}

class SmoothScroll {
    static to(element, duration = 300) {
        if (!element) return;

        element.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
    }
}

// Функція для показу помилок (залишається для сумісності)
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

// Initialize app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    const app = new CalculatorApp();

    // Add some nice loading animations
    const screens = document.querySelectorAll('.screen');
    screens.forEach((screen, index) => {
        screen.style.animationDelay = `${index * 0.1}s`;
    });

    // Add ripple effects to buttons
    app.addRippleEffect();
});

// Обробник для форми проекту (спрощений)
document.addEventListener('DOMContentLoaded', () => {
    const projectForm = document.getElementById("project-form");
    if (!projectForm) return;

    projectForm.addEventListener("submit", function (e) {
        e.preventDefault();

        const roomTypeId = document.getElementById("room-type").value;
        const wallArea = document.getElementById("wall-area").value;
        const roomArea = document.getElementById("room-area").value;

        if (!roomTypeId || wallArea <= 0 || roomArea <= 0) {
            // Показати модалку з помилкою
            const errorMessage = document.getElementById("error-message");
            const errorModal = document.getElementById("error-modal");

            if (errorMessage && errorModal) {
                errorMessage.textContent = "Будь ласка, заповніть усі поля коректно.";
                errorModal.style.display = "block";
            }
            return;
        }

        // Перехід на наступну сторінку з параметрами
        const url = `/BuildMaster/calculator/services-selection?room_type_id=${encodeURIComponent(roomTypeId)}&wall_area=${encodeURIComponent(wallArea)}&room_area=${encodeURIComponent(roomArea)}`;
        window.location.href = url;
    });
});

// CSS для ripple ефекту
const rippleCSS = `
    .primary-btn, .secondary-btn {
        position: relative;
        overflow: hidden;
    }
    
    .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        transform: scale(0);
        animation: ripple-animation 0.6s linear;
        pointer-events: none;
    }
    
    @keyframes ripple-animation {
        to {
            transform: scale(2);
            opacity: 0;
        }
    }
    
    .form-group.focused input,
    .form-group.focused select {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }
    
    .select-wrapper.loaded select {
        animation: fadeInUp 0.3s ease;
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
`;

const style = document.createElement('style');
style.textContent = rippleCSS;
document.head.appendChild(style);