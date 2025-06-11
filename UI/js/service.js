// Service Management JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Основні елементи
    const servicesTable = document.getElementById('services-table');
    const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
    const selectAllCheckbox = document.getElementById('select-all-services');
    const serviceCheckboxes = document.querySelectorAll('.service-checkbox');

    // Модальні вікна
    const deleteModal = document.getElementById('delete-modal');
    const bulkDeleteModal = document.getElementById('bulk-delete-modal');
    const priceEditModal = document.getElementById('price-edit-modal');
    const errorModal = document.getElementById('error-modal');
    const successModal = document.getElementById('success-modal');

    // Ініціалізація
    initializeEventListeners();
    initializeCheckboxHandlers();
    initializePriceEditing();
    initializeServiceDependencies();

    /**
     * Ініціалізація всіх обробників подій
     */
    function initializeEventListeners() {
        // Обробка кнопок видалення послуг
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('delete-service-btn') ||
                e.target.closest('.delete-service-btn')) {

                const btn = e.target.closest('.delete-service-btn');
                const serviceId = btn.getAttribute('data-service-id');
                const serviceName = btn.getAttribute('data-service-name');

                showDeleteConfirmation(serviceId, serviceName);
            }

            // Обробка кнопок швидкого редагування ціни
            if (e.target.classList.contains('edit-price-btn') ||
                e.target.closest('.edit-price-btn')) {

                const btn = e.target.closest('.edit-price-btn');
                const serviceId = btn.getAttribute('data-service-id');
                const serviceName = btn.getAttribute('data-service-name');
                const currentPrice = btn.getAttribute('data-current-price');

                showPriceEditModal(serviceId, serviceName, currentPrice);
            }
        });

        // Обробка масового видалення
        if (bulkDeleteBtn) {
            bulkDeleteBtn.addEventListener('click', function() {
                const selectedServices = getSelectedServices();
                if (selectedServices.length === 0) {
                    showError('Оберіть послуги для видалення');
                    return;
                }
                showBulkDeleteConfirmation(selectedServices);
            });
        }

        // Закриття модальних вікон
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-close')) {
                closeAllModals();
            }

            // Закриття при кліку поза модальним вікном
            if (e.target.classList.contains('modal')) {
                closeAllModals();
            }
        });

        // Escape для закриття модальних вікон
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAllModals();
            }
        });
    }

    /**
     * Ініціалізація обробників чекбоксів
     */
    function initializeCheckboxHandlers() {
        // Обробка "Вибрати всі"
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.service-checkbox:not(:disabled)');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateBulkDeleteButton();
            });
        }

        // Обробка індивідуальних чекбоксів
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('service-checkbox')) {
                updateBulkDeleteButton();
                updateSelectAllCheckbox();
            }
        });
    }

    /**
     * Ініціалізація швидкого редагування цін
     */
    function initializePriceEditing() {
        // Обробка подвійного кліку на ціну для швидкого редагування
        document.addEventListener('dblclick', function(e) {
            if (e.target.classList.contains('service-price')) {
                const serviceId = e.target.getAttribute('data-service-id');
                const serviceName = e.target.getAttribute('data-service-name');
                const currentPrice = e.target.textContent.replace(/[^\d.,]/g, '');

                showPriceEditModal(serviceId, serviceName, currentPrice);
            }
        });

        // Обробка форми редагування ціни
        const priceEditForm = document.getElementById('price-edit-form');
        if (priceEditForm) {
            priceEditForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const serviceId = this.getAttribute('data-service-id');
                const newPrice = document.getElementById('new-price').value;

                if (!newPrice || parseFloat(newPrice) <= 0) {
                    showError('Введіть коректну ціну');
                    return;
                }

                updateServicePrice(serviceId, newPrice);
            });
        }
    }

    /**
     * Ініціалізація залежностей послуг
     */
    function initializeServiceDependencies() {
        const serviceBlockSelect = document.getElementById('service_block_id');
        const dependsOnSelect = document.getElementById('depends_on_service_id');

        if (serviceBlockSelect && dependsOnSelect) {
            serviceBlockSelect.addEventListener('change', function() {
                const blockId = this.value;
                if (blockId) {
                    loadServicesByBlock(blockId, dependsOnSelect);
                } else {
                    dependsOnSelect.innerHTML = '<option value="">Оберіть спочатку блок послуг</option>';
                }
            });
        }
    }

    /**
     * Показати модальне вікно підтвердження видалення
     */
    function showDeleteConfirmation(serviceId, serviceName) {
        const modal = document.getElementById('delete-confirmation-modal') || createDeleteModal();
        const serviceNameSpan = modal.querySelector('#delete-service-name');
        const confirmBtn = modal.querySelector('#confirm-delete');

        serviceNameSpan.textContent = serviceName;

        // Очистити попередні обробники
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

        newConfirmBtn.addEventListener('click', function() {
            deleteService(serviceId);
            closeAllModals();
        });

        modal.style.display = 'flex';
    }

    /**
     * Показати модальне вікно масового видалення
     */
    function showBulkDeleteConfirmation(selectedServices) {
        const modal = document.getElementById('bulk-delete-confirmation-modal') || createBulkDeleteModal();
        const countSpan = modal.querySelector('#selected-services-count');
        const confirmBtn = modal.querySelector('#confirm-bulk-delete');

        countSpan.textContent = selectedServices.length;

        // Очистити попередні обробники
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

        newConfirmBtn.addEventListener('click', function() {
            bulkDeleteServices(selectedServices);
            closeAllModals();
        });

        modal.style.display = 'flex';
    }

    /**
     * Показати модальне вікно редагування ціни
     */
    function showPriceEditModal(serviceId, serviceName, currentPrice) {
        const modal = document.getElementById('price-edit-modal') || createPriceEditModal();
        const serviceNameSpan = modal.querySelector('#edit-price-service-name');
        const priceInput = modal.querySelector('#new-price');
        const form = modal.querySelector('#price-edit-form');

        serviceNameSpan.textContent = serviceName;
        priceInput.value = currentPrice;
        form.setAttribute('data-service-id', serviceId);

        modal.style.display = 'flex';
        priceInput.focus();
        priceInput.select();
    }

    /**
     * Видалення послуги
     */
    function deleteService(serviceId) {
        showLoading(true);

        fetch(`?page=services&ajax_action=delete_service&service_id=${serviceId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
            .then(response => response.json())
            .then(data => {
                showLoading(false);

                if (data.success) {
                    showSuccess(data.message);
                    removeServiceFromTable(serviceId);
                    updateStats();
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                showLoading(false);
                console.error('Delete service error:', error);
                showError('Помилка з\'єднання з сервером');
            });
    }

    /**
     * Масове видалення послуг
     */
    function bulkDeleteServices(serviceIds) {
        showLoading(true);

        const formData = new FormData();
        serviceIds.forEach(id => {
            formData.append('service_ids[]', id);
        });

        fetch(`?page=services&ajax_action=bulk_delete`, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                showLoading(false);

                if (data.success) {
                    showSuccess(`${data.successfully_deleted} послуг успішно видалено`);

                    // Видаляємо успішно видалені послуги з таблиці
                    data.deleted_services.forEach(service => {
                        removeServiceFromTable(service.id);
                    });

                    // Показуємо помилки якщо є
                    if (data.errors && data.errors.length > 0) {
                        setTimeout(() => {
                            showError('Деякі послуги не вдалося видалити:\n' + data.errors.join('\n'));
                        }, 2000);
                    }

                    updateStats();
                    resetCheckboxes();
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                showLoading(false);
                console.error('Bulk delete error:', error);
                showError('Помилка з\'єднання з сервером');
            });
    }

    /**
     * Оновлення ціни послуги
     */
    function updateServicePrice(serviceId, newPrice) {
        showLoading(true);

        const formData = new FormData();
        formData.append('price', newPrice);

        fetch(`?page=services&ajax_action=update_price&service_id=${serviceId}`, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                showLoading(false);
                closeAllModals();

                if (data.success) {
                    showSuccess(`Ціну послуги "${data.service_name}" оновлено з ${data.old_price} на ${data.new_price}`);
                    updatePriceInTable(serviceId, data.new_price);
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                showLoading(false);
                console.error('Update price error:', error);
                showError('Помилка з\'єднання з сервером');
            });
    }

    /**
     * Завантаження послуг за блоком
     */
    function loadServicesByBlock(blockId, targetSelect) {
        showLoading(true);

        fetch(`?page=services&ajax_action=get_services_by_block&block_id=${blockId}`)
            .then(response => response.json())
            .then(data => {
                showLoading(false);

                if (data.success) {
                    targetSelect.innerHTML = '<option value="">Оберіть послугу</option>';
                    data.services.forEach(service => {
                        const option = document.createElement('option');
                        option.value = service.id;
                        option.textContent = service.name;
                        targetSelect.appendChild(option);
                    });
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                showLoading(false);
                console.error('Load services error:', error);
                showError('Помилка завантаження послуг');
            });
    }

    /**
     * Утилітарні функції
     */
    function getSelectedServices() {
        const checkboxes = document.querySelectorAll('.service-checkbox:checked');
        return Array.from(checkboxes).map(cb => cb.value);
    }

    function updateBulkDeleteButton() {
        const selectedCount = getSelectedServices().length;
        if (bulkDeleteBtn) {
            bulkDeleteBtn.disabled = selectedCount === 0;
            bulkDeleteBtn.textContent = selectedCount > 0 ?
                `Видалити вибрані (${selectedCount})` : 'Видалити вибрані';
        }
    }

    function updateSelectAllCheckbox() {
        if (!selectAllCheckbox) return;

        const checkboxes = document.querySelectorAll('.service-checkbox:not(:disabled)');
        const checkedBoxes = document.querySelectorAll('.service-checkbox:checked');

        if (checkedBoxes.length === 0) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = false;
        } else if (checkedBoxes.length === checkboxes.length) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = true;
        } else {
            selectAllCheckbox.indeterminate = true;
        }
    }

    function removeServiceFromTable(serviceId) {
        const row = document.querySelector(`tr[data-service-id="${serviceId}"]`);
        if (row) {
            row.remove();
        }
    }

    function updatePriceInTable(serviceId, newPrice) {
        const priceCell = document.querySelector(`[data-service-id="${serviceId}"] .service-price`);
        if (priceCell) {
            priceCell.textContent = `${newPrice} грн/м²`;
        }
    }

    function resetCheckboxes() {
        document.querySelectorAll('.service-checkbox').forEach(cb => cb.checked = false);
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        }
        updateBulkDeleteButton();
    }

    function updateStats() {
        // Оновлення статистики кількості послуг
        const serviceRows = document.querySelectorAll('#services-table tbody tr');
        const statsElement = document.getElementById('services-count');
        if (statsElement) {
            statsElement.textContent = serviceRows.length;
        }
    }

    /**
     * Функції для показу повідомлень та модальних вікон
     */
    function showLoading(show) {
        let loader = document.getElementById('page-loader');
        if (show && !loader) {
            loader = document.createElement('div');
            loader.id = 'page-loader';
            loader.innerHTML = `
                <div class="loader-backdrop">
                    <div class="loader-content">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p>Обробка запиту...</p>
                    </div>
                </div>
            `;
            document.body.appendChild(loader);
        } else if (!show && loader) {
            loader.remove();
        }
    }

    function showSuccess(message) {
        showNotification(message, 'success');
    }

    function showError(message) {
        showNotification(message, 'error');
    }

    function showNotification(message, type) {
        // Видаляємо попередні повідомлення
        document.querySelectorAll('.notification').forEach(n => n.remove());

        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                <span>${message}</span>
                <button class="notification-close">&times;</button>
            </div>
        `;

        document.body.appendChild(notification);

        // Автоматичне приховування через 5 секунд
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);

        // Обробка кнопки закриття
        notification.querySelector('.notification-close').addEventListener('click', () => {
            notification.remove();
        });
    }

    function closeAllModals() {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.style.display = 'none';
        });
    }

    /**
     * Створення модальних вікон якщо їх немає в HTML
     */
    function createDeleteModal() {
        const modal = document.createElement('div');
        modal.id = 'delete-confirmation-modal';
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Підтвердження видалення</h3>
                    <button class="modal-close">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Ви впевнені, що хочете видалити послугу "<span id="delete-service-name"></span>"?</p>
                    <p class="warning">Цю дію неможливо скасувати!</p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary modal-close">Скасувати</button>
                    <button id="confirm-delete" class="btn btn-danger">Видалити</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        return modal;
    }

    function createBulkDeleteModal() {
        const modal = document.createElement('div');
        modal.id = 'bulk-delete-confirmation-modal';
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Масове видалення</h3>
                    <button class="modal-close">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Ви впевнені, що хочете видалити <span id="selected-services-count"></span> послуг?</p>
                    <p class="warning">Цю дію неможливо скасувати!</p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary modal-close">Скасувати</button>
                    <button id="confirm-bulk-delete" class="btn btn-danger">Видалити всі</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        return modal;
    }

    function createPriceEditModal() {
        const modal = document.createElement('div');
        modal.id = 'price-edit-modal';
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Редагування ціни</h3>
                    <button class="modal-close">&times;</button>
                </div>
                <form id="price-edit-form">
                    <div class="modal-body">
                        <p>Послуга: <strong id="edit-price-service-name"></strong></p>
                        <div class="form-group">
                            <label for="new-price">Нова ціна (грн/м²):</label>
                            <input type="number" id="new-price" step="0.01" min="0.01" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary modal-close">Скасувати</button>
                        <button type="submit" class="btn btn-primary">Зберегти</button>
                    </div>
                </form>
            </div>
        `;
        document.body.appendChild(modal);
        return modal;
    }
    document.addEventListener("DOMContentLoaded", function () {
        const backBtnCalculator = document.getElementById("back-btn-calculator");
        if (backBtnCalculator) {
            backBtnCalculator.addEventListener("click", function (e) {
                e.preventDefault();
                window.location.href = "/BuildMaster/Calculator";
            });
        }
    });
    // Експортуємо функції для використання ззовні
    window.ServiceManager = {
        deleteService,
        updateServicePrice,
        loadServicesByBlock,
        showSuccess,
        showError
    };
});