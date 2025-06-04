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
            window.location.href = '/BuildMaster/calculator';
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
            // Виправлено URL для API
            const response = await fetch(`/BuildMaster/api/services?room_type_id=${window.calculatorData.roomTypeId}`);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const services = await response.json();
            this.servicesData = services;
            this.renderServices(services);
        } catch (error) {
            console.error('Error loading services:', error);
            this.showError('Помилка завантаження послуг. Спробуйте пізніше.');
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

        servicesData.forEach((block, index) => {
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
            }

            // Рендерим послуги в блоці
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

            // Обробник розгортання блоку
            const blockHeader = blockElement.querySelector('.service-block-header');
            if (blockHeader && toggleBtn) {
                blockHeader.addEventListener('click', () => {
                    this.toggleServiceBlock(serviceBlock);
                });
            }

            // Розгортаємо перший блок за замовчуванням
            if (index === 0) {
                serviceBlock.classList.add('expanded');
            }

            container.appendChild(blockElement);
        });
    }

    toggleServiceBlock(blockElement) {
        blockElement.classList.toggle('expanded');
    }

    handleServiceToggle(checkbox) {
        const serviceId = parseInt(checkbox.getAttribute('data-service-id'));
        const serviceName = checkbox.getAttribute('data-name');
        const price = parseFloat(checkbox.getAttribute('data-price'));
        const serviceItem = checkbox.closest('.service-item');

        if (checkbox.checked) {
            this.selectedServices.add({
                id: serviceId,
                name: serviceName,
                price: price
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

            const cost = (service.price * window.calculatorData.wallArea).toFixed(2);
            total += parseFloat(cost);

            serviceElement.innerHTML = `
                <span class="service-title">${service.name}</span>
                <span class="service-cost">${cost} ₴</span>
            `;

            selectedServicesList.appendChild(serviceElement);
        });

        totalCost.textContent = `${total.toFixed(2)} ₴`;
        continueBtn.disabled = false;
    }

    async proceedToFinalStep() {
        if (this.selectedServices.size === 0) {
            this.showError('Оберіть хоча б одну послугу');
            return;
        }

        const serviceIds = Array.from(this.selectedServices).map(service => service.id);

        try {
            // Виправлено URL для API
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

// Клас для головної сторінки калькулятора
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
        // Перенаправляємо на форму створення проекту
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