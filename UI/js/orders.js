document.addEventListener('DOMContentLoaded', function() {
    const serviceForm = document.getElementById('serviceForm');
    const slugInput = document.getElementById('slug');
    const nameInput = document.getElementById('name');
    const serviceBlockSelect = document.getElementById('service_block_id');
    const dependsOnSelect = document.getElementById('depends_on_service_id');
    const submitBtn = document.getElementById('submitBtn');
    const loadingDiv = document.getElementById('loading');
    const errorDiv = document.getElementById('error');
    const successDiv = document.getElementById('success');

    // Автоматичне генерування slug з назви
    if (nameInput && slugInput) {
        nameInput.addEventListener('input', function() {
            const name = this.value.trim();
            if (name) {
                const slug = generateSlug(name);
                slugInput.value = slug;
            }
        });
    }

    // Завантаження послуг при зміні блоку
    if (serviceBlockSelect && dependsOnSelect) {
        serviceBlockSelect.addEventListener('change', function() {
            const blockId = this.value;
            loadServicesByBlock(blockId);
        });
    }

    // Обробка форми
    if (serviceForm) {
        serviceForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Валідація перед відправкою
            if (!validateForm()) {
                showError('Будь ласка, заповніть всі обов\'язкові поля правильно');
                return;
            }

            submitService();
        });
    }

    // Функція генерації slug
    function generateSlug(text) {
        const cyrillicToLatin = {
            'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'ґ': 'g',
            'д': 'd', 'е': 'e', 'є': 'ie', 'ж': 'zh', 'з': 'z',
            'и': 'y', 'і': 'i', 'ї': 'yi', 'й': 'y', 'к': 'k',
            'л': 'l', 'м': 'm', 'н': 'n', 'о': 'o', 'п': 'p',
            'р': 'r', 'с': 's', 'т': 't', 'у': 'u', 'ф': 'f',
            'х': 'kh', 'ц': 'ts', 'ч': 'ch', 'ш': 'sh', 'щ': 'shch',
            'ь': '', 'ю': 'yu', 'я': 'ya'
        };

        return text
            .toLowerCase()
            .split('')
            .map(char => cyrillicToLatin[char] || char)
            .join('')
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '');
    }

    // Завантаження послуг за блоком
    function loadServicesByBlock(blockId) {
        if (!blockId) {
            dependsOnSelect.innerHTML = '<option value="">Оберіть послугу</option>';
            return;
        }

        // Показати індикатор завантаження
        dependsOnSelect.innerHTML = '<option value="">Завантаження...</option>';
        dependsOnSelect.disabled = true;

        // Формуємо URL для AJAX запиту
        const url = window.location.href.split('?')[0] + '?ajax_action=get_services_by_block&block_id=' + blockId;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    dependsOnSelect.innerHTML = '<option value="">Немає залежності</option>';

                    data.services.forEach(service => {
                        const option = document.createElement('option');
                        option.value = service.id;
                        option.textContent = service.name;
                        dependsOnSelect.appendChild(option);
                    });
                } else {
                    showError(data.message || 'Помилка завантаження послуг');
                    dependsOnSelect.innerHTML = '<option value="">Помилка завантаження</option>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Помилка з\'єднання з сервером');
                dependsOnSelect.innerHTML = '<option value="">Помилка завантаження</option>';
            })
            .finally(() => {
                dependsOnSelect.disabled = false;
            });
    }

    // Відправка форми
    function submitService() {
        const formData = new FormData(serviceForm);

        // Показати індикатор завантаження
        showLoading();
        hideMessages();

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Збереження...';
        }

        // Визначаємо URL для відправки
        const isEdit = serviceForm.querySelector('input[name="service_id"]');
        const action = isEdit ? 'update_service' : 'store_service';
        const url = window.location.href.split('?')[0] + '?action=' + action;

        fetch(url, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess(data.message);

                    if (!isEdit) {
                        serviceForm.reset();
                        // Скидаємо залежні поля
                        if (dependsOnSelect) {
                            dependsOnSelect.innerHTML = '<option value="">Оберіть послугу</option>';
                        }
                    }

                    // Перенаправлення через 2 секунди
                    setTimeout(() => {
                        window.location.href = window.location.href.split('?')[0] + '?page=services';
                    }, 2000);
                } else {
                    showError(data.message || 'Помилка при збереженні послуги');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Помилка з\'єднання з сервером');
            })
            .finally(() => {
                hideLoading();
                if (submitBtn) {
                    submitBtn.disabled = false;
                    const buttonText = serviceForm.querySelector('input[name="service_id"]') ?
                        'Оновити послугу' : 'Зберегти послугу';
                    submitBtn.innerHTML = '<i class="fas fa-save"></i> ' + buttonText;
                }
            });
    }

    // Валідація форми
    function validateForm() {
        let isValid = true;
        const requiredFields = ['name', 'slug', 'service_block_id', 'price_per_sqm'];

        requiredFields.forEach(fieldName => {
            const field = document.getElementById(fieldName);
            if (field && !validateField(field)) {
                isValid = false;
            }
        });

        // Перевірка областей застосування
        const areaCheckboxes = document.querySelectorAll('input[name="areas[]"]:checked');
        if (areaCheckboxes.length === 0) {
            const areaError = document.getElementById('areaError');
            if (areaError) {
                areaError.style.display = 'block';
                areaError.textContent = 'Оберіть хоча б одну область застосування';
            }
            isValid = false;
        }

        return isValid;
    }

    // Показати повідомлення про завантаження
    function showLoading() {
        if (loadingDiv) {
            loadingDiv.style.display = 'block';
        }
    }

    // Сховати повідомлення про завантаження
    function hideLoading() {
        if (loadingDiv) {
            loadingDiv.style.display = 'none';
        }
    }

    // Показати повідомлення про помилку
    function showError(message) {
        if (errorDiv) {
            errorDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${message}`;
            errorDiv.style.display = 'block';

            // Автоматично приховати через 5 секунд
            setTimeout(() => {
                errorDiv.style.display = 'none';
            }, 5000);
        }
    }

    // Показати повідомлення про успіх
    function showSuccess(message) {
        if (successDiv) {
            successDiv.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
            successDiv.style.display = 'block';
        }
    }

    // Сховати всі повідомлення
    function hideMessages() {
        if (errorDiv) errorDiv.style.display = 'none';
        if (successDiv) successDiv.style.display = 'none';
    }

    // Валідація форми в реальному часі
    const requiredFields = ['name', 'slug', 'service_block_id', 'price_per_sqm'];

    requiredFields.forEach(fieldName => {
        const field = document.getElementById(fieldName);
        if (field) {
            field.addEventListener('blur', function() {
                validateField(this);
            });

            field.addEventListener('input', function() {
                if (this.classList.contains('error')) {
                    validateField(this);
                }
            });
        }
    });

    // Валідація окремого поля
    function validateField(field) {
        const value = field.value.trim();
        const fieldName = field.getAttribute('name') || field.id;

        // Видалити попередні класи помилок
        field.classList.remove('error');

        // Перевірка на порожнє поле
        if (!value && requiredFields.includes(fieldName)) {
            field.classList.add('error');
            return false;
        }

        // Перевірка специфічних полів
        if (fieldName === 'price_per_sqm' && (isNaN(value) || parseFloat(value) <= 0)) {
            field.classList.add('error');
            return false;
        }

        if (fieldName === 'slug' && value && !/^[a-z0-9-]+$/.test(value)) {
            field.classList.add('error');
            return false;
        }

        return true;
    }

    // Обробка чекбоксів для областей застосування
    const areaCheckboxes = document.querySelectorAll('input[name="areas[]"]');
    const areaError = document.getElementById('areaError');

    areaCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const checkedBoxes = document.querySelectorAll('input[name="areas[]"]:checked');

            if (checkedBoxes.length === 0) {
                if (areaError) {
                    areaError.style.display = 'block';
                    areaError.textContent = 'Оберіть хоча б одну область застосування';
                }
            } else {
                if (areaError) {
                    areaError.style.display = 'none';
                }
            }
        });
    });

    // Підказки для полів
    const tooltips = document.querySelectorAll('[data-tooltip]');

    tooltips.forEach(element => {
        element.addEventListener('mouseenter', function() {
            const tooltip = this.getAttribute('data-tooltip');
            showTooltip(this, tooltip);
        });

        element.addEventListener('mouseleave', function() {
            hideTooltip();
        });
    });

    function showTooltip(element, text) {
        // Видаляємо попередню підказку, якщо є
        hideTooltip();

        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = text;
        tooltip.style.position = 'absolute';
        tooltip.style.background = '#333';
        tooltip.style.color = 'white';
        tooltip.style.padding = '8px 12px';
        tooltip.style.borderRadius = '4px';
        tooltip.style.fontSize = '12px';
        tooltip.style.zIndex = '1000';
        tooltip.style.whiteSpace = 'nowrap';
        tooltip.style.pointerEvents = 'none';

        document.body.appendChild(tooltip);

        const rect = element.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();

        tooltip.style.left = (rect.left + rect.width / 2 - tooltipRect.width / 2) + 'px';
        tooltip.style.top = (rect.top - tooltipRect.height - 5) + 'px';

        tooltip.id = 'current-tooltip';
    }

    function hideTooltip() {
        const tooltip = document.getElementById('current-tooltip');
        if (tooltip) {
            tooltip.remove();
        }
    }

    // Додаткова валідація для числових полів
    const numericFields = ['price_per_sqm', 'sort_order'];

    numericFields.forEach(fieldName => {
        const field = document.getElementById(fieldName);
        if (field) {
            field.addEventListener('input', function() {
                // Дозволяємо тільки числа та крапку
                this.value = this.value.replace(/[^0-9.]/g, '');

                // Дозволяємо тільки одну крапку
                const parts = this.value.split('.');
                if (parts.length > 2) {
                    this.value = parts[0] + '.' + parts.slice(1).join('');
                }
            });
        }
    });

    // Автоматичне заповнення одиниці вимірювання
    const unitField = document.getElementById('unit');
    if (unitField && !unitField.value) {
        unitField.value = 'м²';
    }
});