class ServicesSelection {
    constructor() {
        this.selectedServices = new Set();
        this.servicesData = [];
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadServices();
    }

    bindEvents() {
        // Кнопка назад
        document.getElementById('back-to-form')?.addEventListener('click', () => {
            window.location.href = '/BuildMaster/calculator/project-form';
        });

        // Кнопка продовжити
        document.getElementById('continue-btn')?.addEventListener('click', () => {
            this.proceedToFinalStep();
        });

        // Закриття модального вікна помилки
        document.getElementById('close-error-btn')?.addEventListener('click', () => {
            this.hideError();
        });
    }

    async loadServices() {
        try {
            // Виправлений URL - без /BuildMaster на початку
            const response = await fetch(`/BuildMaster/api/services?room_type_id=${window.calculatorData.roomTypeId}`);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const services = await response.json();
            console.log('Loaded services data:', services);
            console.log('Type of services:', typeof services);
            console.log('Is array:', Array.isArray(services));

            // Перевіряємо на помилку в відповіді
            if (services.error) {
                throw new Error(services.error);
            }

            // Перевіряємо різні можливі структури даних
            if (!services) {
                throw new Error('No services data received');
            }

            // Якщо це об'єкт з властивістю data
            if (services.data && Array.isArray(services.data)) {
                this.servicesData = services.data;
                this.renderServices(services.data);
            }
            // Якщо це просто масив
            else if (Array.isArray(services)) {
                this.servicesData = services;
                this.renderServices(services);
            }
            // Якщо це пустий масив або об'єкт
            else {
                console.warn('Unexpected services data structure:', services);
                this.servicesData = [];
                this.renderServices([]);
            }
        } catch (error) {
            console.error('Error loading services:', error);
            this.showError('Помилка завантаження послуг. Спробуйте пізніше.');
            // Показуємо порожній стан
            this.renderServices([]);
        }
    }

    renderServices(servicesData) {
        const container = document.getElementById('services-list');
        const blockTemplate = document.getElementById('service-block-template');
        const itemTemplate = document.getElementById('service-item-template');

        if (!container || !blockTemplate || !itemTemplate) {
            console.error('Required templates not found');
            return;
        }

        // Очищуємо контейнер
        container.innerHTML = '';

        // Перевіряємо чи є дані
        if (!servicesData || !Array.isArray(servicesData) || servicesData.length === 0) {
            container.innerHTML = '<p class="no-services">Послуги не знайдено для цього типу кімнати</p>';
            return;
        }

        // Тепер servicesData - це масив областей (підлога, стіни, стеля)
        servicesData.forEach((area, areaIndex) => {
            // Перевіряємо структуру області
            if (!area || typeof area !== 'object') {
                console.warn('Invalid area data:', area);
                return;
            }

            // Створюємо секцію для області з випадаючим блоком
            const areaSection = document.createElement('div');
            areaSection.className = 'area-section';
            areaSection.setAttribute('data-area-type', area.area_type);

            // Визначаємо іконку та площу для області
            let areaIcon = 'fas fa-square';
            let areaSize = window.calculatorData.roomArea;

            switch (area.area_type) {
                case 'floor':
                    areaIcon = 'fas fa-expand-arrows-alt';
                    areaSize = window.calculatorData.roomArea;
                    break;
                case 'walls':
                    areaIcon = 'fas fa-vector-square';
                    areaSize = window.calculatorData.wallArea;
                    break;
                case 'ceiling':
                    areaIcon = 'fas fa-square';
                    areaSize = window.calculatorData.roomArea;
                    break;
            }

            // Створюємо заголовок області з кнопкою розгортання
            const areaHeader = document.createElement('div');
            areaHeader.className = 'area-header';
            areaHeader.innerHTML = `
                <h3 class="area-title">
                    <i class="${areaIcon}"></i>
                    ${area.area_name || 'Невідома область'}
                    <small>${areaSize} м²</small>
                </h3>
                <button class="toggle-btn">
                    <i class="fas fa-chevron-down"></i>
                </button>
            `;

            // Створюємо контент області
            const areaContent = document.createElement('div');
            areaContent.className = 'area-content';

            // Рендерим блоки послуг для кожної області
            if (area.service_blocks && Array.isArray(area.service_blocks)) {
                area.service_blocks.forEach((block, blockIndex) => {
                    const blockElement = blockTemplate.content.cloneNode(true);

                    // Заповнюємо дані блоку
                    const blockName = blockElement.querySelector('.block-name');
                    const blockDescription = blockElement.querySelector('.block-description');
                    const servicesGrid = blockElement.querySelector('.services-grid');
                    const toggleBtn = blockElement.querySelector('.toggle-btn');
                    const serviceBlock = blockElement.querySelector('.service-block');

                    if (blockName) blockName.textContent = block.name;
                    if (blockDescription) blockDescription.textContent = block.description;

                    // Додаємо ID для блоку
                    if (serviceBlock) {
                        serviceBlock.setAttribute('data-block-id', block.id);
                        serviceBlock.setAttribute('data-area-type', area.area_type);
                    }

                    // Рендерим послуги в блоці
                    if (block.services && Array.isArray(block.services)) {
                        block.services.forEach(service => {
                            const serviceElement = itemTemplate.content.cloneNode(true);

                            const serviceName = serviceElement.querySelector('.service-name');
                            const serviceDescription = serviceElement.querySelector('.service-description');
                            const priceValue = serviceElement.querySelector('.price-value');
                            const serviceCheck = serviceElement.querySelector('.service-check');
                            const serviceItem = serviceElement.querySelector('.service-item');

                            if (serviceName) serviceName.textContent = service.name;
                            if (serviceDescription) serviceDescription.textContent = service.description;
                            if (priceValue) priceValue.textContent = parseFloat(service.price_per_sqm).toFixed(2);

                            if (serviceCheck) {
                                serviceCheck.setAttribute('data-service-id', service.id);
                                serviceCheck.setAttribute('data-price', service.price_per_sqm);
                                serviceCheck.setAttribute('data-name', service.name);
                                serviceCheck.setAttribute('data-area-type', area.area_type);
                            }

                            if (serviceItem) {
                                serviceItem.addEventListener('click', (e) => {
                                    if (e.target.type !== 'checkbox') {
                                        serviceCheck.click();
                                    }
                                });
                            }

                            // Обробник зміни чекбоксу
                            if (serviceCheck) {
                                serviceCheck.addEventListener('change', (e) => {
                                    this.handleServiceToggle(e.target);
                                });
                            }

                            servicesGrid.appendChild(serviceElement);
                        });
                    }

                    // Обробник розгортання блоку
                    const blockHeader = blockElement.querySelector('.service-block-header');
                    if (blockHeader && toggleBtn) {
                        blockHeader.addEventListener('click', () => {
                            this.toggleServiceBlock(serviceBlock);
                        });
                    }

                    // Розгортаємо перший блок кожної області за замовчуванням
                    if (blockIndex === 0) {
                        serviceBlock.classList.add('expanded');
                    }

                    areaContent.appendChild(blockElement);
                });
            }

            // Обробник розгортання області
            areaHeader.addEventListener('click', () => {
                this.toggleAreaSection(areaSection);
            });

            // Збираємо секцію області
            areaSection.appendChild(areaHeader);
            areaSection.appendChild(areaContent);

            // Розгортаємо першу область за замовчуванням
            if (areaIndex === 0) {
                areaSection.classList.add('expanded');
            }

            container.appendChild(areaSection);
        });
    }

    toggleAreaSection(areaElement) {
        areaElement.classList.toggle('expanded');

        // Анімація для кнопки розгортання
        const toggleBtn = areaElement.querySelector('.area-header .toggle-btn i');
        if (toggleBtn) {
            if (areaElement.classList.contains('expanded')) {
                toggleBtn.style.transform = 'rotate(180deg)';
            } else {
                toggleBtn.style.transform = 'rotate(0deg)';
            }
        }
    }

    toggleServiceBlock(blockElement) {
        blockElement.classList.toggle('expanded');
    }

    handleServiceToggle(checkbox) {
        const serviceId = parseInt(checkbox.getAttribute('data-service-id'));
        const serviceName = checkbox.getAttribute('data-name');
        const price = parseFloat(checkbox.getAttribute('data-price'));
        const areaType = checkbox.getAttribute('data-area-type');
        const serviceItem = checkbox.closest('.service-item');

        if (checkbox.checked) {
            this.selectedServices.add({
                id: serviceId,
                name: serviceName,
                price: price,
                areaType: areaType
            });
            serviceItem?.classList.add('selected');
        } else {
            // Знаходимо і видаляємо сервіс
            for (let service of this.selectedServices) {
                if (service.id === serviceId) {
                    this.selectedServices.delete(service);
                    break;
                }
            }
            serviceItem?.classList.remove('selected');
        }

        this.updateCalculation();
    }

    updateCalculation() {
        const selectedServicesList = document.getElementById('selected-services-list');
        const totalCost = document.getElementById('total-cost');
        const continueBtn = document.getElementById('continue-btn');

        if (!selectedServicesList || !totalCost || !continueBtn) return;

        // Очищуємо список
        selectedServicesList.innerHTML = '';

        if (this.selectedServices.size === 0) {
            selectedServicesList.innerHTML = '<p class="no-services">Оберіть послуги для розрахунку</p>';
            totalCost.textContent = '0 ₴';
            continueBtn.disabled = true;
            return;
        }

        let total = 0;

        // Додаємо вибрані послуги
        this.selectedServices.forEach(service => {
            const serviceElement = document.createElement('div');
            serviceElement.className = 'selected-service';

            // Вибираємо правильну площу на основі типу області
            let area = 0;
            switch (service.areaType) {
                case 'floor':
                    area = window.calculatorData.roomArea;
                    break;
                case 'walls':
                    area = window.calculatorData.wallArea;
                    break;
                case 'ceiling':
                    area = window.calculatorData.roomArea;
                    break;
                default:
                    area = window.calculatorData.wallArea;
            }

            const cost = (service.price * area).toFixed(2);
            total += parseFloat(cost);

            // Додаємо інформацію про тип області
            const areaTypeText = this.getAreaTypeText(service.areaType);

            serviceElement.innerHTML = `
                <div class="service-info">
                    <span class="service-title">${service.name}</span>
                    <span class="service-area-type">(${areaTypeText})</span>
                </div>
                <span class="service-cost">${cost} ₴</span>
            `;

            selectedServicesList.appendChild(serviceElement);
        });

        totalCost.textContent = `${total.toFixed(2)} ₴`;
        continueBtn.disabled = false;
    }

    getAreaTypeText(areaType) {
        switch (areaType) {
            case 'floor':
                return 'підлога';
            case 'walls':
                return 'стіни';
            case 'ceiling':
                return 'стеля';
            default:
                return 'невідомо';
        }
    }

    async proceedToFinalStep() {
        if (this.selectedServices.size === 0) {
            this.showError('Оберіть хоча б одну послугу');
            return;
        }

        const serviceIds = Array.from(this.selectedServices).map(service => service.id);

        try {
            // Виправлений URL
            const response = await fetch('/BuildMaster/api/calculate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    services: serviceIds,
                    wall_area: window.calculatorData.wallArea,
                    room_area: window.calculatorData.roomArea,
                    room_type_id: window.calculatorData.roomTypeId
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            // Зберігаємо результат в сесії або передаємо на наступну сторінку
            sessionStorage.setItem('calculationResult', JSON.stringify({
                ...result,
                selectedServices: Array.from(this.selectedServices),
                roomTypeId: window.calculatorData.roomTypeId
            }));

            // Переходимо на сторінку результатів
            window.location.href = '/BuildMaster/calculator/result';

        } catch (error) {
            console.error('Error calculating:', error);
            this.showError('Помилка розрахунку. Спробуйте пізніше.');
        }
    }

    showError(message) {
        const modal = document.getElementById('error-modal');
        const errorMessage = document.getElementById('error-message');

        if (errorMessage) {
            errorMessage.textContent = message;
        }

        if (modal) {
            modal.style.display = 'block';
        }
    }

    hideError() {
        const modal = document.getElementById('error-modal');
        if (modal) {
            modal.style.display = 'none';
        }
    }
}

class CalculatorMain {
    constructor() {
        this.init();
    }

    init() {
        this.addRippleEffect();
        this.bindEvents();
    }

    bindEvents() {
        // Обробник для primary-btn (кнопка "Розрахувати вартість")
        const primaryBtn = document.querySelector('.primary-btn');
        if (primaryBtn) {
            primaryBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.openCalculator();
            });
        }
    }

    openCalculator() {
        window.location.href = '/BuildMaster/calculator/project-form';
    }

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

// Ініціалізуємо відповідний клас залежно від сторінки
document.addEventListener('DOMContentLoaded', () => {
    // Якщо це сторінка вибору послуг
    if (document.getElementById('services-list')) {
        new ServicesSelection();
    }
    // Якщо це головна сторінка калькулятора
    else if (document.querySelector('.primary-btn')) {
        new CalculatorMain();
    }
});

// Обробка закриття модального вікна при кліку поза ним
window.addEventListener('click', (event) => {
    const modal = document.getElementById('error-modal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
});