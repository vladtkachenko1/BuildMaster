/**
 * JavaScript для сторінки редагування кімнати
 */

class RoomEditor {
    constructor() {
        this.roomData = window.roomEditData || {};
        this.services = [];
        this.selectedServices = new Map();
        this.hasChanges = false;
        this.isLoading = false;

        this.init();
    }

    init() {
        console.log('Initializing RoomEditor with data:', this.roomData);
        this.bindEvents();
        this.loadServices();
        this.initializeFormTracking();
        this.initializeSelectedServices();
    }

    bindEvents() {
        // Кнопка повернення
        const backBtn = document.getElementById('back-to-rooms');
        if (backBtn) {
            backBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleBackNavigation();
            });
        }

        // Кнопка збереження
        const saveBtn = document.getElementById('save-changes-btn');
        if (saveBtn) {
            saveBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.saveChanges();
            });
        }

        // Закриття модальних вікон
        this.bindModalEvents();

        // Відстеження змін у полях форми
        this.bindFormFieldEvents();
    }

    bindFormFieldEvents() {
        const fields = ['room-name', 'wall-area', 'floor-area'];

        fields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.addEventListener('input', () => {
                    this.markAsChanged();
                    this.updateDisplayValues();
                });

                field.addEventListener('blur', () => {
                    this.validateField(field);
                });
            }
        });
    }

    validateField(field) {
        const value = field.value.trim();
        let isValid = true;

        switch (field.id) {
            case 'room-name':
                isValid = value.length > 0;
                break;
            case 'wall-area':
            case 'floor-area':
                const numValue = parseFloat(value);
                isValid = !isNaN(numValue) && numValue > 0;
                break;
        }

        field.classList.toggle('error', !isValid);
        return isValid;
    }

    initializeFormTracking() {
        // Зберігаємо початкові значення для порівняння
        this.initialValues = {
            roomName: document.getElementById('room-name')?.value || '',
            wallArea: document.getElementById('wall-area')?.value || '0',
            floorArea: document.getElementById('floor-area')?.value || '0'
        };
    }

    initializeSelectedServices() {
        // Ініціалізуємо selectedServices з початкових даних, якщо є
        if (this.roomData.selectedServices) {
            this.roomData.selectedServices.forEach(service => {
                this.selectedServices.set(service.service_id, {
                    id: service.service_id,
                    name: service.service_name,
                    price_per_sqm: service.unit_price,
                    area_type: service.area_type,
                    quantity: service.quantity,
                    total: service.total_price
                });
            });
        }
    }

    markAsChanged() {
        const currentValues = {
            roomName: document.getElementById('room-name')?.value || '',
            wallArea: document.getElementById('wall-area')?.value || '0',
            floorArea: document.getElementById('floor-area')?.value || '0'
        };

        // Перевіряємо чи змінились значення форми
        const formChanged = JSON.stringify(currentValues) !== JSON.stringify(this.initialValues);

        this.hasChanges = formChanged;
        this.updateUI();
    }

    updateUI() {
        const changesIndicator = document.getElementById('changes-indicator');
        const saveBtn = document.getElementById('save-changes-btn');

        if (changesIndicator) {
            changesIndicator.classList.toggle('show', this.hasChanges);
        }

        if (saveBtn) {
            saveBtn.disabled = !this.hasChanges || this.isLoading;
        }
    }

    updateDisplayValues() {
        const wallArea = parseFloat(document.getElementById('wall-area')?.value || 0);
        const floorArea = parseFloat(document.getElementById('floor-area')?.value || 0);

        // Оновлюємо відображення площ
        const wallDisplay = document.getElementById('wall-display');
        const floorDisplay = document.getElementById('floor-display');
        const ceilingDisplay = document.getElementById('ceiling-display');

        if (wallDisplay) wallDisplay.textContent = `${wallArea.toFixed(2)} м²`;
        if (floorDisplay) floorDisplay.textContent = `${floorArea.toFixed(2)} м²`;
        if (ceilingDisplay) ceilingDisplay.textContent = `${floorArea.toFixed(2)} м²`;

        // Перерахуємо послуги
        this.recalculateServices();
    }

    async loadServices() {
        if (!this.roomData.roomId) {
            this.showError('Не вдалося знайти ID кімнати');
            return;
        }

        try {
            this.showLoading(true);
            const response = await fetch(`/BuildMaster/calculator/room-edit-services/${this.roomData.roomId}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Помилка отримання даних');
            }

            // Оновлюємо дані кімнати
            if (data.room_data) {
                this.roomData = {
                    ...this.roomData,
                    ...data.room_data
                };
            }
            
            // Фільтруємо пусті блоки послуг
            this.services = (data.services || []).filter(area => 
                area.service_blocks && 
                area.service_blocks.length > 0 && 
                area.service_blocks.some(block => block.services && block.services.length > 0)
            );
            
            // Очищаємо та оновлюємо вибрані послуги
            this.selectedServices.clear();
            this.services.forEach(area => {
                if (area.service_blocks) {
                    area.service_blocks.forEach(block => {
                        if (block.services) {
                            block.services.forEach(service => {
                                if (service.is_selected) {
                                    this.selectedServices.set(service.id, {
                                        id: service.id,
                                        name: service.name,
                                        price_per_sqm: service.selected_unit_price,
                                        area_type: service.area_type,
                                        quantity: service.selected_quantity,
                                        total: service.selected_total_price
                                    });
                                }
                            });
                        }
                    });
                }
            });

            // Оновлюємо форму
            const roomNameInput = document.getElementById('room-name');
            const wallAreaInput = document.getElementById('wall-area');
            const floorAreaInput = document.getElementById('floor-area');

            if (roomNameInput) roomNameInput.value = this.roomData.room_name || '';
            if (wallAreaInput) wallAreaInput.value = this.roomData.wall_area || 0;
            if (floorAreaInput) floorAreaInput.value = this.roomData.floor_area || 0;

            // Оновлюємо відображення
            this.renderServices();
            this.updateDisplayValues();
            this.calculateTotal();
            this.updateSelectedServicesList();
        } catch (error) {
            console.error('Error loading services:', error);
            this.showError('Помилка завантаження послуг: ' + error.message);
        } finally {
            this.showLoading(false);
        }
    }

    renderServices() {
        const servicesList = document.getElementById('services-list');
        if (!servicesList) {
            console.error('Services list element not found');
            return;
        }

        if (this.services.length === 0) {
            servicesList.innerHTML = '<div class="no-services"><p>Послуги не знайдено</p></div>';
            return;
        }

        // Очищаємо контейнер
        servicesList.innerHTML = '';

        // Отримуємо шаблони
        const blockTemplate = document.getElementById('service-block-template');
        const itemTemplate = document.getElementById('service-item-template');

        if (!blockTemplate || !itemTemplate) {
            console.error('Templates not found');
            return;
        }

        // Рендеримо кожну область
        this.services.forEach((area, areaIndex) => {
            if (!area || typeof area !== 'object') {
                console.warn('Invalid area data:', area);
                return;
            }

            // Створюємо секцію для області
            const areaSection = document.createElement('div');
            areaSection.className = 'area-section';
            areaSection.setAttribute('data-area-type', area.area_type);

            // Визначаємо іконку та площу для області
            let areaIcon = 'fas fa-square';
            let areaSize = this.roomData.floor_area;

            switch (area.area_type) {
                case 'floor':
                    areaIcon = 'fas fa-expand-arrows-alt';
                    areaSize = this.roomData.floor_area;
                    break;
                case 'walls':
                    areaIcon = 'fas fa-vector-square';
                    areaSize = this.roomData.wall_area;
                    break;
                case 'ceiling':
                    areaIcon = 'fas fa-bars';
                    areaSize = this.roomData.floor_area;
                    break;
            }

            // Створюємо заголовок області
            const areaHeader = document.createElement('div');
            areaHeader.className = 'area-header';
            areaHeader.innerHTML = `
                <h3 class="area-title">
                    <i class="${areaIcon}"></i>
                    ${this.escapeHtml(area.area_name)}
                    <small>${areaSize} м²</small>
                </h3>
                <button class="toggle-btn">
                    <i class="fas fa-chevron-down"></i>
                </button>
            `;

            // Створюємо контент області
            const areaContent = document.createElement('div');
            areaContent.className = 'area-content';

            // Рендеримо блоки послуг
            if (area.service_blocks && Array.isArray(area.service_blocks)) {
                area.service_blocks.forEach((block, blockIndex) => {
                    const blockElement = blockTemplate.content.cloneNode(true);
                    const serviceBlock = blockElement.querySelector('.service-block');
                    const blockHeader = blockElement.querySelector('.service-block-header');
                    const blockContent = blockElement.querySelector('.service-block-content');
                    const toggleBtn = blockElement.querySelector('.toggle-btn');
                    const servicesGrid = blockElement.querySelector('.services-grid');

                    // Заповнюємо дані блоку
                    const blockName = blockElement.querySelector('.block-name');
                    const blockDescription = blockElement.querySelector('.block-description');

                    if (blockName) blockName.textContent = block.name;
                    if (blockDescription) blockDescription.textContent = block.description;

                    // Додаємо ID для блоку
                    if (serviceBlock) {
                        serviceBlock.setAttribute('data-block-id', block.id);
                        serviceBlock.setAttribute('data-area-type', area.area_type);
                    }

                    // Рендеримо послуги в блоці
                    if (block.services && Array.isArray(block.services)) {
                        block.services.forEach(service => {
                            const serviceElement = itemTemplate.content.cloneNode(true);
                            const serviceItem = serviceElement.querySelector('.service-item');
                            const serviceCheck = serviceElement.querySelector('.service-check');
                            const serviceName = serviceElement.querySelector('.service-name');
                            const serviceDescription = serviceElement.querySelector('.service-description');
                            const priceValue = serviceElement.querySelector('.price-value');
                            const calculationDetails = serviceElement.querySelector('.calculation-details');

                            if (serviceName) serviceName.textContent = service.name;
                            if (serviceDescription) serviceDescription.textContent = service.description;
                            if (priceValue) priceValue.textContent = parseFloat(service.price_per_sqm).toFixed(2);

                            // Додаємо розрахунок для вибраної послуги
                            if (service.is_selected) {
                                const calculation = this.getServiceCalculation(service);
                                if (calculationDetails) {
                                    calculationDetails.textContent = calculation;
                                }
                            }

                            if (serviceCheck) {
                                serviceCheck.setAttribute('data-service-id', service.id);
                                serviceCheck.setAttribute('data-price', service.price_per_sqm);
                                serviceCheck.setAttribute('data-name', service.name);
                                serviceCheck.setAttribute('data-area-type', area.area_type);
                                
                                if (service.is_selected) {
                                    serviceCheck.checked = true;
                                    serviceItem.classList.add('selected');
                                }
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
                                    if (e.target.checked) {
                                        this.addService(service.id);
                                    } else {
                                        this.removeService(service.id);
                                    }
                                });
                            }

                            servicesGrid.appendChild(serviceElement);
                        });
                    }

                    // Обробник розгортання блоку
                    if (blockHeader && toggleBtn) {
                        blockHeader.addEventListener('click', (e) => {
                            e.preventDefault();
                            e.stopPropagation();
                            serviceBlock.classList.toggle('expanded');
                            blockContent.style.display = serviceBlock.classList.contains('expanded') ? 'block' : 'none';
                            toggleBtn.querySelector('i').style.transform = serviceBlock.classList.contains('expanded') ? 'rotate(180deg)' : 'rotate(0)';
                        });
                    }

                    // Розгортаємо перший блок кожної області за замовчуванням
                    if (blockIndex === 0) {
                        serviceBlock.classList.add('expanded');
                        blockContent.style.display = 'block';
                        toggleBtn.querySelector('i').style.transform = 'rotate(180deg)';
                    }

                    areaContent.appendChild(blockElement);
                });
            }

            // Обробник розгортання області
            areaHeader.addEventListener('click', (e) => {
                e.preventDefault();
                areaSection.classList.toggle('expanded');
                areaContent.style.display = areaSection.classList.contains('expanded') ? 'block' : 'none';
                areaHeader.querySelector('.toggle-btn i').style.transform = areaSection.classList.contains('expanded') ? 'rotate(180deg)' : 'rotate(0)';
            });

            // Збираємо секцію області
            areaSection.appendChild(areaHeader);
            areaSection.appendChild(areaContent);

            // Розгортаємо першу область за замовчуванням
            if (areaIndex === 0) {
                areaSection.classList.add('expanded');
                areaContent.style.display = 'block';
                areaHeader.querySelector('.toggle-btn i').style.transform = 'rotate(180deg)';
            }

            servicesList.appendChild(areaSection);
        });

        // Оновлюємо список вибраних послуг
        this.updateSelectedServicesList();
    }

    getServiceCalculation(service) {
        let quantity = 0;
        switch (service.area_type) {
            case 'floor':
            case 'ceiling':
                quantity = parseFloat(this.roomData.floor_area) || 0;
                break;
            case 'walls':
                quantity = parseFloat(this.roomData.wall_area) || 0;
                break;
            default:
                quantity = parseFloat(this.roomData.wall_area) || 0;
        }

        const total = quantity * parseFloat(service.price_per_sqm);
        return `${quantity.toFixed(2)} м² × ${parseFloat(service.price_per_sqm).toFixed(2)} ₴ = ${total.toFixed(2)} ₴`;
    }

    bindServiceEvents() {
        // Bind toggle buttons
        document.querySelectorAll('.service-block .toggle-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                const block = e.target.closest('.service-block');
                const content = block.querySelector('.service-block-content');
                const icon = button.querySelector('i');

                if (content.style.display === 'none') {
                    content.style.display = 'block';
                    icon.classList.remove('fa-chevron-right');
                    icon.classList.add('fa-chevron-down');
                } else {
                    content.style.display = 'none';
                    icon.classList.remove('fa-chevron-down');
                    icon.classList.add('fa-chevron-right');
                }
            });
        });

        // Bind service checkboxes
        document.querySelectorAll('.service-check').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                const serviceItem = e.target.closest('.service-item');
                const serviceId = parseInt(serviceItem.dataset.serviceId);

                if (e.target.checked) {
                    this.addService(serviceId);
                } else {
                    this.removeService(serviceId);
                }
            });
        });
    }

    addService(serviceId) {
        const service = this.findServiceById(serviceId);
        if (!service) {
            console.error('Service not found:', serviceId);
            return;
        }

        // Визначаємо кількість (площу) для послуги
        let quantity = 0;
        switch (service.area_type) {
            case 'floor':
            case 'ceiling':
                quantity = parseFloat(this.roomData.floor_area) || 0;
                break;
            case 'walls':
                quantity = parseFloat(this.roomData.wall_area) || 0;
                break;
            default:
                quantity = parseFloat(this.roomData.wall_area) || 0;
        }

        if (quantity <= 0) {
            this.showError('Не вдалося визначити площу для послуги');
            return;
        }

        const total = quantity * parseFloat(service.price_per_sqm);

        // Додаємо послугу до вибраних
        this.selectedServices.set(service.id, {
            id: service.id,
            name: service.name,
            price_per_sqm: service.price_per_sqm,
            area_type: service.area_type,
            quantity: quantity,
            total: total
        });

        // Оновлюємо відображення
        this.markAsChanged();
        this.updateSelectedServicesList();
        this.calculateTotal();

        // Оновлюємо розрахунок в списку послуг
        const serviceElement = document.querySelector(`.service-check[data-service-id="${serviceId}"]`);
        if (serviceElement) {
            const serviceItem = serviceElement.closest('.service-item');
            const calculationDetails = serviceItem.querySelector('.calculation-details');
            if (calculationDetails) {
                calculationDetails.textContent = this.getServiceCalculation(service);
            }
            serviceItem.classList.add('selected');
        }
    }

    removeService(serviceId) {
        // Видаляємо послугу з вибраних
        this.selectedServices.delete(serviceId);

        // Оновлюємо відображення
        this.markAsChanged();
        this.updateSelectedServicesList();
        this.calculateTotal();

        // Оновлюємо розрахунок в списку послуг
        const serviceElement = document.querySelector(`.service-check[data-service-id="${serviceId}"]`);
        if (serviceElement) {
            const serviceItem = serviceElement.closest('.service-item');
            const calculationDetails = serviceItem.querySelector('.calculation-details');
            if (calculationDetails) {
                calculationDetails.textContent = '';
            }
            serviceItem.classList.remove('selected');
        }
    }

    findServiceById(serviceId) {
        for (const area of this.services) {
            if (area.service_blocks) {
                for (const block of area.service_blocks) {
                    if (block.services) {
                        for (const service of block.services) {
                            if (service.id == serviceId) {
                                return service;
                            }
                        }
                    }
                }
            }
        }
        return null;
    }

    recalculateServices() {
        // Оновлюємо розрахунки для всіх вибраних послуг
        this.selectedServices.forEach((service, serviceId) => {
            let quantity = 0;
            switch (service.area_type) {
                case 'floor':
                case 'ceiling':
                    quantity = parseFloat(this.roomData.floor_area) || 0;
                    break;
                case 'walls':
                    quantity = parseFloat(this.roomData.wall_area) || 0;
                    break;
                default:
                    quantity = parseFloat(this.roomData.wall_area) || 0;
            }

            const total = quantity * parseFloat(service.price_per_sqm);

            // Оновлюємо дані послуги
            this.selectedServices.set(serviceId, {
                ...service,
                quantity: quantity,
                total: total
            });

            // Оновлюємо розрахунок в списку послуг
            const serviceElement = document.querySelector(`.service-check[data-service-id="${serviceId}"]`);
            if (serviceElement) {
                const serviceItem = serviceElement.closest('.service-item');
                const calculationDetails = serviceItem.querySelector('.calculation-details');
                if (calculationDetails) {
                    calculationDetails.textContent = this.getServiceCalculation(service);
                }
            }
        });

        // Оновлюємо відображення
        this.markAsChanged();
        this.updateSelectedServicesList();
        this.calculateTotal();
    }

    calculateTotal() {
        let total = 0;
        this.selectedServices.forEach(service => {
            total += service.total;
        });

        // Оновлюємо відображення загальної суми
        const totalElement = document.getElementById('total-amount');
        if (totalElement) {
            totalElement.textContent = `${total.toFixed(2)} ₴`;
        }

        return total;
    }

    updateSelectedServicesList() {
        const selectedList = document.getElementById('selected-services-list');
        if (!selectedList) return;

        // Очищаємо список
        selectedList.innerHTML = '';

        if (this.selectedServices.size === 0) {
            selectedList.innerHTML = '<div class="no-services"><p>Не вибрано жодної послуги</p></div>';
            return;
        }

        // Створюємо елементи для кожної вибраної послуги
        this.selectedServices.forEach((service, serviceId) => {
            const serviceElement = document.createElement('div');
            serviceElement.className = 'selected-service-item';
            serviceElement.innerHTML = `
                <div class="service-info">
                    <span class="service-name">${this.escapeHtml(service.name)}</span>
                    <span class="service-area">${this.getAreaTypeText(service.area_type)}</span>
                </div>
                <div class="service-details">
                    <span class="service-quantity">${service.quantity} м²</span>
                    <span class="service-price">${service.price_per_sqm} ₴/м²</span>
                    <span class="service-total">${service.total} ₴</span>
                </div>
                <button class="remove-service" data-service-id="${serviceId}">
                    <i class="fas fa-times"></i>
                </button>
            `;

            // Додаємо обробник для кнопки видалення
            const removeBtn = serviceElement.querySelector('.remove-service');
            if (removeBtn) {
                removeBtn.addEventListener('click', () => {
                    this.removeService(serviceId);
                });
            }

            selectedList.appendChild(serviceElement);
        });

        // Оновлюємо загальну суму
        const totalElement = document.getElementById('total-amount');
        if (totalElement) {
            const total = this.calculateTotal();
            totalElement.textContent = `${total.toFixed(2)} ₴`;
        }
    }

    getAreaTypeText(areaType) {
        switch (areaType) {
            case 'floor':
                return 'Підлога';
            case 'walls':
                return 'Стіни';
            case 'ceiling':
                return 'Стеля';
            default:
                return 'Інше';
        }
    }

    async saveChanges() {
        if (this.isLoading) return;

        try {
            this.isLoading = true;
            const saveBtn = document.getElementById('save-changes-btn');
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Збереження...';
            }

            // Валідація форми
            const roomName = document.getElementById('room-name')?.value?.trim();
            const wallArea = parseFloat(document.getElementById('wall-area')?.value || 0);
            const floorArea = parseFloat(document.getElementById('floor-area')?.value || 0);

            if (!roomName) {
                throw new Error('Введіть назву кімнати');
            }

            if (wallArea <= 0) {
                throw new Error('Площа стін повинна бути більше 0');
            }

            if (floorArea <= 0) {
                throw new Error('Площа підлоги повинна бути більше 0');
            }

            // Підготовка даних для відправки
            const selectedServicesArray = Array.from(this.selectedServices.values()).map(service => ({
                id: service.id,
                price_per_sqm: service.price_per_sqm,
                area_type: service.area_type
            }));

            const requestData = {
                room_id: this.roomData.roomId,
                room_name: roomName,
                wall_area: wallArea,
                floor_area: floorArea,
                selected_services: selectedServicesArray
            };

            console.log('Sending data:', requestData);

            const response = await fetch('/BuildMaster/calculator/update-room-with-services', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.error || 'Помилка збереження');
            }

            // Оновлюємо початкові значення
            this.initialValues = {
                roomName: roomName,
                wallArea: wallArea.toString(),
                floorArea: floorArea.toString()
            };

            this.hasChanges = false;
            this.updateUI();

            this.showSuccess('Зміни успішно збережено!');

        } catch (error) {
            console.error('Error saving changes:', error);
            this.showError('Помилка збереження: ' + error.message);
        } finally {
            this.isLoading = false;
            const saveBtn = document.getElementById('save-changes-btn');
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fas fa-save"></i> Зберегти зміни';
            }
        }
    }

    handleBackNavigation() {
        if (this.hasChanges) {
            this.showConfirmModal(
                'Незбережені зміни',
                'У вас є незбережені зміни. Ви дійсно хочете залишити сторінку?',
                () => this.goBackToRooms()
            );
        } else {
            this.goBackToRooms();
        }
    }

    goBackToRooms() {
        window.location.href = '/BuildMaster/calculator/order-rooms';
    }

    showLoading(show) {
        const servicesList = document.getElementById('services-list');
        if (!servicesList) return;

        if (show) {
            servicesList.innerHTML = `
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Завантаження послуг...</p>
                </div>
            `;
        }
    }

    showConfirmModal(title, message, onConfirm) {
        const modal = document.getElementById('confirmation-modal');
        const titleEl = document.getElementById('modal-title');
        const messageEl = document.getElementById('modal-message');
        const confirmBtn = document.getElementById('confirm-btn');

        if (titleEl) titleEl.textContent = title;
        if (messageEl) messageEl.textContent = message;

        if (modal) modal.style.display = 'flex';

        if (confirmBtn) {
            // Видаляємо попередні обробники
            const newConfirmBtn = confirmBtn.cloneNode(true);
            confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

            newConfirmBtn.onclick = () => {
                modal.style.display = 'none';
                onConfirm();
            };
        }
    }

    showError(message) {
        const modal = document.getElementById('error-modal');
        const messageEl = document.getElementById('error-message');

        if (messageEl) messageEl.textContent = message;
        if (modal) modal.style.display = 'flex';
    }

    showSuccess(message) {
        const modal = document.getElementById('success-modal');
        const messageEl = document.getElementById('success-message');

        if (messageEl) messageEl.textContent = message;
        if (modal) modal.style.display = 'flex';
    }

    bindModalEvents() {
        // Закриття модальних вікон
        document.addEventListener('click', (e) => {
            if (e.target.matches('#close-modal-btn, #cancel-btn')) {
                const modal = document.getElementById('confirmation-modal');
                if (modal) modal.style.display = 'none';
            }

            if (e.target.matches('#close-error-btn')) {
                const modal = document.getElementById('error-modal');
                if (modal) modal.style.display = 'none';
            }

            if (e.target.matches('#close-success-btn')) {
                const modal = document.getElementById('success-modal');
                if (modal) modal.style.display = 'none';
            }

            // Закриття по кліку поза модальним вікном
            if (e.target.classList.contains('modal')) {
                e.target.style.display = 'none';
            }
        });

        // Закриття по Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.style.display = 'none';
                });
            }
        });
    }

    // Допоміжна функція для екранування HTML
    escapeHtml(text) {
        if (typeof text !== 'string') return text;

        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };

        return text.replace(/[&<>"']/g, (m) => map[m]);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    try {
        new RoomEditor();
    } catch (error) {
        console.error('Failed to initialize RoomEditor:', error);
    }
});