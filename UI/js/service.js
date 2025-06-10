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

    // Автоматична генерація slug з назви
    nameInput.addEventListener('input', function() {
        const slug = this.value
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
        slugInput.value = slug;
    });

    // Завантаження послуг при зміні блоку
    if (serviceBlockSelect && dependsOnSelect) {
        serviceBlockSelect.addEventListener('change', function() {
            const blockId = this.value;
            loadServicesByBlock(blockId);
        });
    }

    // Обробка відправки форми
    serviceForm.addEventListener('submit', function(e) {
        e.preventDefault();

        // Перевірка вибраних областей
        const areas = document.querySelectorAll('input[name="areas[]"]:checked');
        if (areas.length === 0) {
            document.getElementById('areaError').style.display = 'block';
            return;
        }
        document.getElementById('areaError').style.display = 'none';

        // Показуємо індикатор завантаження
        loadingDiv.style.display = 'block';
        errorDiv.style.display = 'none';
        successDiv.style.display = 'none';

        // Збираємо дані форми
        const formData = new FormData(serviceForm);

        // Відправляємо запит
        fetch('/BuildMaster/admin?action=service_store', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Error parsing JSON:', text);
                    throw new Error('Invalid JSON response from server');
                }
            });
        })
        .then(data => {
            loadingDiv.style.display = 'none';
            
            if (data.success) {
                successDiv.textContent = data.message;
                successDiv.style.display = 'block';
                // Перенаправлення на сторінку послуг через 2 секунди
                setTimeout(() => {
                    window.location.href = '/BuildMaster/admin?action=services';
                }, 2000);
            } else {
                errorDiv.textContent = data.message || 'Помилка при збереженні послуги';
                errorDiv.style.display = 'block';
            }
        })
        .catch(error => {
            loadingDiv.style.display = 'none';
            errorDiv.textContent = 'Помилка при збереженні послуги: ' + error.message;
            errorDiv.style.display = 'block';
            console.error('Error:', error);
        });
    });

    // Завантаження послуг за блоком
    function loadServicesByBlock(blockId) {
        if (!blockId) {
            dependsOnSelect.innerHTML = '<option value="">Оберіть послугу</option>';
            return;
        }

        // Показати індикатор завантаження
        dependsOnSelect.innerHTML = '<option value="">Завантаження...</option>';
        dependsOnSelect.disabled = true;

        fetch(`?action=get_services_by_block&block_id=${blockId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    dependsOnSelect.innerHTML = '<option value="">Оберіть послугу</option>';

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
        const fieldName = field.getAttribute('name');

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

        if (fieldName === 'slug' && !/^[a-z0-9-]+$/.test(value)) {
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

        document.body.appendChild(tooltip);

        const rect = element.getBoundingClientRect();
        tooltip.style.left = rect.left + 'px';
        tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';

        tooltip.id = 'current-tooltip';
    }

    function hideTooltip() {
        const tooltip = document.getElementById('current-tooltip');
        if (tooltip) {
            tooltip.remove();
        }
    }
});