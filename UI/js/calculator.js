// Calculator JavaScript - Виправлено маршрутизацію

class CalculatorApp {
    constructor() {
        this.currentScreen = 'welcome-screen';
        this.roomTypes = [];
        this.basePath = '/BuildMaster';
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
        // ВИПРАВЛЕНО: кнопка "Створити проект" має перекидати на order-rooms
        const createProjectBtn = document.getElementById("create-project-btn");
        if (createProjectBtn) {
            createProjectBtn.addEventListener("click", (event) => {
                event.preventDefault();
                console.log('Redirecting to order-rooms...');
                // ВИПРАВЛЕНО: спочатку створюємо порожнє замовлення, потім перекидаємо на order-rooms
                this.createEmptyOrderAndRedirect();
            });
        }

        // Перевіряємо існування кнопки "Назад"
        const backBtn = document.getElementById("back-btn");
        if (backBtn) {
            backBtn.addEventListener("click", function (e) {
                e.preventDefault();
                window.location.href = "/BuildMaster/calculator/";
            });
        }

        // Перевіряємо існування кнопки закриття помилки
        const closeErrorBtn = document.getElementById('close-error-btn');
        if (closeErrorBtn) {
            closeErrorBtn.addEventListener('click', () => {
                this.hideModal('error-modal');
            });
        }

        // ВИПРАВЛЕНО: обробка форми проекту - має перекидати на services-selection
        const projectForm = document.getElementById('project-form');
        if (projectForm) {
            projectForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleProjectFormSubmit();
            });
        }

        // Налаштовуємо анімації для інпутів тільки якщо вони існують
        this.setupInputAnimations();

        // Глобальний слухач для Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.hideModal('error-modal');
            }
        });
    }

    // НОВИЙ МЕТОД: створення порожнього замовлення перед переходом на order-rooms
    async createEmptyOrderAndRedirect() {
        try {
            const response = await fetch(this.basePath + '/calculator/create-empty-order', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                // Успішно створено порожнє замовлення, перекидаємо на order-rooms
                window.location.href = this.basePath + '/calculator/order-rooms';
            } else {
                // Якщо не вдалося створити замовлення, все одно перекидаємо (можливо вже є активне)
                console.warn('Could not create empty order:', data.error);
                window.location.href = this.basePath + '/calculator/order-rooms';
            }
        } catch (error) {
            console.error('Error creating empty order:', error);
            // У разі помилки все одно перекидаємо
            window.location.href = this.basePath + '/calculator/order-rooms';
        }
    }

    // ВИПРАВЛЕНО: новий метод для обробки форми проекту
    async handleProjectFormSubmit() {
        const form = document.getElementById('project-form');

        if (!form) {
            console.error('Форма проекту не знайдена');
            return;
        }

        const formData = new FormData(form);
        const roomTypeId = formData.get('room_type_id');
        const wallArea = parseFloat(formData.get('wall_area'));
        const roomArea = parseFloat(formData.get('room_area'));

        // Валідація форми
        if (!this.validateForm(formData)) {
            return;
        }

        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Збереження...';
            submitBtn.disabled = true;

            try {
                // ВИПРАВЛЕНО: спочатку створюємо замовлення для нової кімнати
                const response = await fetch(this.basePath + '/calculator/create-order-for-new-room', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        room_type_id: roomTypeId,
                        wall_area: wallArea,
                        room_area: roomArea
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Успішно створено замовлення, перекидаємо на вибір послуг
                    const url = `${this.basePath}/calculator/services-selection?room_type_id=${encodeURIComponent(roomTypeId)}&wall_area=${encodeURIComponent(wallArea)}&room_area=${encodeURIComponent(roomArea)}&room_id=${encodeURIComponent(data.room_id)}`;

                    // Зберігаємо дані в sessionStorage для використання на наступній сторінці
                    sessionStorage.setItem('selected_room_type_id', roomTypeId);
                    sessionStorage.setItem('wall_area', wallArea);
                    sessionStorage.setItem('room_area', roomArea);
                    sessionStorage.setItem('current_room_id', data.room_id);

                    window.location.href = url;
                } else {
                    throw new Error(data.error || 'Помилка створення замовлення');
                }

            } catch (error) {
                console.error('Error processing form:', error);
                this.showError('Помилка обробки форми: ' + error.message);
            } finally {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        }
    }

    // Метод для видалення кімнати - ВИПРАВЛЕНО URL
    async removeRoom(roomId) {
        try {
            const response = await fetch(this.basePath + "/calculator/remove-room", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ room_id: roomId })
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess('Кімнату успішно видалено!');
                setTimeout(() => {
                    location.reload();
                }, 1000);
                return data;
            } else {
                throw new Error(data.error || 'Помилка видалення кімнати');
            }
        } catch (error) {
            console.error('Error removing room:', error);
            this.showError('Помилка видалення кімнати: ' + error.message);
            throw error;
        }
    }

    goBack() {
        window.history.back();
    }

    setupInputAnimations() {
        const inputs = document.querySelectorAll('input, select');

        if (inputs.length === 0) {
            return;
        }

        inputs.forEach(input => {
            if (!input.parentElement) {
                return;
            }

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
            // ВИПРАВЛЕНО: URL відповідає методу getRoomTypes() в контролері
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

            const data = await response.json();

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

        if (!select) {
            console.log('Select елемент "room-type" не знайдено - це нормально для головної сторінки');
            return;
        }

        if (this.roomTypes.length === 0) {
            console.log('Типи кімнат ще не завантажено');
            return;
        }

        while (select.children.length > 1) {
            select.removeChild(select.lastChild);
        }

        this.roomTypes.forEach(roomType => {
            const option = document.createElement('option');
            option.value = roomType.id;
            option.textContent = roomType.name;
            option.dataset.slug = roomType.slug;
            select.appendChild(option);
        });

        select.classList.add('loaded');
    }

    async saveRoomWithServices(selectedServices, roomName = 'Кімната') {
        try {
            // ВИПРАВЛЕНО: URL відповідає методу saveRoomWithServices() в контролері
            const response = await fetch(this.basePath + '/calculator/save-room-services', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    services: selectedServices,
                    room_name: roomName
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP помилка! статус: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                this.showSuccess('Кімнату успішно додано до замовлення!');

                setTimeout(() => {
                    window.location.href = data.redirect_url;
                }, 2000);

                return data;
            } else {
                throw new Error(data.error || 'Помилка збереження кімнати');
            }
        } catch (error) {
            console.error('Error saving room:', error);
            this.showError('Помилка збереження кімнати: ' + error.message);
            throw error;
        }
    }

    async loadServicesForRoomType(roomTypeId) {
        try {
            // ВИПРАВЛЕНО: URL відповідає методу getServicesJson() в контролері
            const response = await fetch(this.basePath + '/calculator/services?room_type_id=' + encodeURIComponent(roomTypeId), {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP помилка! статус: ${response.status}`);
            }

            const data = await response.json();

            // ВИПРАВЛЕНО: метод getServicesJson() повертає масив безпосередньо
            if (Array.isArray(data)) {
                return data;
            } else {
                throw new Error('Помилка завантаження послуг');
            }
        } catch (error) {
            console.error('Error loading services:', error);
            this.showError('Помилка завантаження послуг: ' + error.message);
            return [];
        }
    }

    async loadCurrentOrderRooms() {
        try {
            // ВИПРАВЛЕНО: URL відповідає методу getCurrentOrderRooms() в контролері
            const response = await fetch(this.basePath + '/calculator/current-rooms', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP помилка! статус: ${response.status}`);
            }

            const data = await response.json();
            return data.rooms || [];
        } catch (error) {
            console.error('Error loading rooms:', error);
            return [];
        }
    }

    // Додано метод для отримання деталей кімнати
    async getRoomDetails(roomId) {
        try {
            const response = await fetch(this.basePath + '/calculator/room-details/' + roomId, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP помилка! статус: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                return data.room;
            } else {
                throw new Error(data.error || 'Помилка завантаження деталей кімнати');
            }
        } catch (error) {
            console.error('Error loading room details:', error);
            this.showError('Помилка завантаження деталей: ' + error.message);
            return null;
        }
    }

    // Додано метод для розрахунку вартості
    async calculateCost(services, wallArea, roomArea, roomTypeId) {
        try {
            const response = await fetch(this.basePath + '/calculate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    services: services,
                    wall_area: wallArea,
                    room_area: roomArea,
                    room_type_id: roomTypeId
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP помилка! статус: ${response.status}`);
            }

            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error calculating cost:', error);
            this.showError('Помилка розрахунку: ' + error.message);
            return null;
        }
    }

    showSuccess(message) {
        let successModal = document.getElementById('success-modal');

        if (!successModal) {
            alert(message);
            return;
        }

        const successMessage = document.getElementById('success-message');
        if (successMessage) {
            successMessage.textContent = message;
            this.showModal('success-modal');

            setTimeout(() => {
                this.hideModal('success-modal');
            }, 3000);
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
        } else {
            console.warn(`Поле з ID "${fieldId}" не знайдено`);
        }
    }

    showScreen(screenId) {
        const currentScreen = document.getElementById(this.currentScreen);
        if (currentScreen) {
            currentScreen.classList.remove('active');
        }

        setTimeout(() => {
            const newScreen = document.getElementById(screenId);
            if (newScreen) {
                newScreen.classList.add('active');
                this.currentScreen = screenId;

                if (screenId === 'project-form-screen') {
                    this.updateProgress(33);
                }

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
        const errorModal = document.getElementById('error-modal');
        const errorMessage = document.getElementById('error-message');

        if (!errorModal || !errorMessage) {
            alert(message);
            return;
        }

        errorMessage.textContent = message;
        this.showModal('error-modal');
    }

    showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';

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

    addRippleEffect() {
        const buttons = document.querySelectorAll('.primary-btn, .secondary-btn');

        if (buttons.length === 0) {
            return;
        }

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

    const screens = document.querySelectorAll('.screen');
    screens.forEach((screen, index) => {
        screen.style.animationDelay = `${index * 0.1}s`;
    });

    app.addRippleEffect();
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