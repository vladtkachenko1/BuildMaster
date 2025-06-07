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

            if (data.error) {
                throw new Error(data.error);
            }

            this.services = data.services || [];
            this.renderServices();
            this.calculateTotal();

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

        let html = '';

        this.services.forEach(area => {
            html += `<div class="services-area">`;
            html += `<h2 class="area-title"><i class="fas fa-layer-group"></i> ${this.escapeHtml(area.area_name)}</h2>`;

            if (area.service_blocks && area.service_blocks.length > 0) {
                area.service_blocks.forEach(block => {
                    html += this.renderServiceBlock(block);
                });
            }

            html += `</div>`;
        });

        servicesList.innerHTML = html;
        this.bindServiceEvents();
    }

    renderServiceBlock(block) {
        let html = `
            <div class="service-block">
                <div class="service-block-header">
                    <div class="block-info">
                        <h3 class="block-title">
                            <i class="fas fa-tools"></i>
                            <span class="block-name">${this.escapeHtml(block.name)}</span>
                        </h3>
                        ${block.description ? `<p class="block-description">${this.escapeHtml(block.description)}</p>` : ''}
                    </div>
                    <div class="block-toggle">
                        <button class="toggle-btn" type="button">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                </div>
                <div class="service-block-content">
                    <div class="services-grid">
        `;

        if (block.services && block.services.length > 0) {
            block.services.forEach(service => {
                html += this.renderServiceItem(service);
            });
        }

        html += `
                    </div>
                </div>
            </div>
        `;

        return html;
    }

    renderServiceItem(service) {
        const isSelected = service.is_selected || this.selectedServices.has(service.id);
        const serviceData = this.selectedServices.get(service.id) || service;

        return `
            <div class="service-item ${isSelected ? 'selected' : ''}" data-service-id="${service.id}">
                <div class="service-checkbox">
                    <input type="checkbox" class="service-check" ${isSelected ? 'checked' : ''}>
                    <span class="checkmark"></span>
                </div>
                <div class="service-info">
                    <h4 class="service-name">${this.escapeHtml(service.name)}</h4>
                    ${service.description ? `<p class="service-description">${this.escapeHtml(service.description)}</p>` : ''}
                    <div class="service-price">
                        <span class="price-value">${service.price_per_sqm || service.price}</span>
                        <span class="price-unit">₴/м²</span>
                    </div>
                    <div class="service-calculation">
                        <span class="calculation-details">
                            ${this.getServiceCalculation(service)}
                        </span>
                    </div>
                </div>
            </div>
        `;
    }

    getServiceCalculation(service) {
        const wallArea = parseFloat(document.getElementById('wall-area')?.value || this.roomData.initialWallArea || 0);
        const floorArea = parseFloat(document.getElementById('floor-area')?.value || this.roomData.initialFloorArea || 0);

        let quantity = wallArea; // За замовчуванням стіни

        if (service.area_type === 'floor' || service.area_type === 'ceiling') {
            quantity = floorArea;
        }

        const pricePerSqm = parseFloat(service.price_per_sqm || service.price || 0);
        const total = quantity * pricePerSqm;

        return `${quantity.toFixed(2)} м² × ${pricePerSqm} ₴ = ${total.toFixed(2)} ₴`;
    }

    bindServiceEvents() {
        // Обробка вибору послуг
        document.querySelectorAll('.service-check').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                const serviceItem = e.target.closest('.service-item');
                const serviceId = parseInt(serviceItem.dataset.serviceId);

                if (e.target.checked) {
                    serviceItem.classList.add('selected');
                    this.addService(serviceId);
                } else {
                    serviceItem.classList.remove('selected');
                    this.removeService(serviceId);
                }

                this.markAsChanged();
                this.calculateTotal();
            });
        });

        // Обробка розгортання блоків
        document.querySelectorAll('.toggle-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const block = e.target.closest('.service-block');
                const content = block.querySelector('.service-block-content');
                const icon = btn.querySelector('i');

                content.classList.toggle('collapsed');
                icon.classList.toggle('fa-chevron-down');
                icon.classList.toggle('fa-chevron-up');
            });
        });
    }

    addService(serviceId) {
        const service = this.findServiceById(serviceId);
        if (!service) {
            console.error('Service not found:', serviceId);
            return;
        }

        const wallArea = parseFloat(document.getElementById('wall-area')?.value || this.roomData.initialWallArea || 0);
        const floorArea = parseFloat(document.getElementById('floor-area')?.value || this.roomData.initialFloorArea || 0);

        let quantity = wallArea;
        if (service.area_type === 'floor' || service.area_type === 'ceiling') {
            quantity = floorArea;
        }

        const pricePerSqm = parseFloat(service.price_per_sqm || service.price || 0);

        this.selectedServices.set(serviceId, {
            id: serviceId,
            name: service.name,
            price_per_sqm: pricePerSqm,
            area_type: service.area_type,
            quantity: quantity,
            total: quantity * pricePerSqm
        });
    }

    removeService(serviceId) {
        this.selectedServices.delete(serviceId);
    }

    findServiceById(serviceId) {
        for (const area of this.services) {
            if (area.service_blocks) {
                for (const block of area.service_blocks) {
                    if (block.services) {
                        for (const service of block.services) {
                            if (service.id === serviceId) {
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
        const wallArea = parseFloat(document.getElementById('wall-area')?.value || 0);
        const floorArea = parseFloat(document.getElementById('floor-area')?.value || 0);

        // Оновлюємо вибрані послуги
        this.selectedServices.forEach((service, serviceId) => {
            let quantity = wallArea;
            if (service.area_type === 'floor' || service.area_type === 'ceiling') {
                quantity = floorArea;
            }

            service.quantity = quantity;
            service.total = quantity * service.price_per_sqm;
        });

        // Оновлюємо відображення
        document.querySelectorAll('.service-item').forEach(item => {
            const serviceId = parseInt(item.dataset.serviceId);
            const service = this.findServiceById(serviceId);

            if (service) {
                const calculationEl = item.querySelector('.calculation-details');
                if (calculationEl) {
                    calculationEl.textContent = this.getServiceCalculation(service);
                }
            }
        });

        this.calculateTotal();
    }

    calculateTotal() {
        let total = 0;
        this.selectedServices.forEach(service => {
            total += service.total || 0;
        });

        // Оновлюємо відображення загальної суми
        const totalEl = document.getElementById('total-cost');
        if (totalEl) {
            totalEl.textContent = `${total.toFixed(2)} ₴`;
        }

        // Оновлюємо список вибраних послуг
        this.updateSelectedServicesList();
    }

    updateSelectedServicesList() {
        const selectedList = document.getElementById('selected-services-list');
        if (!selectedList) return;

        if (this.selectedServices.size === 0) {
            selectedList.innerHTML = '<p class="no-services">Оберіть послуги для розрахунку</p>';
            return;
        }

        let html = '';
        this.selectedServices.forEach(service => {
            html += `
                <div class="selected-service-item">
                    <span class="service-name">${this.escapeHtml(service.name)}</span>
                    <span class="service-calculation">${service.quantity.toFixed(2)} м² × ${service.price_per_sqm} ₴</span>
                    <span class="service-total">${service.total.toFixed(2)} ₴</span>
                </div>
            `;
        });

        selectedList.innerHTML = html;
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