// JavaScript для роботи з замовленнями
let currentOrderId = null;
let currentDeleteOrderId = null;

// Функція для фільтрації замовлень по статусу
function filterOrders(status) {
    const currentUrl = new URL(window.location);

    if (status) {
        currentUrl.searchParams.set('status', status);
    } else {
        currentUrl.searchParams.delete('status');
    }

    currentUrl.searchParams.delete('page'); // Скидаємо на першу сторінку
    window.location.href = currentUrl.toString();
}

// Керування модальними вікнами
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    document.body.style.overflow = '';
}

// Закриття модальних вікон
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal') || e.target.classList.contains('close') || e.target.classList.contains('btn-close')) {
        e.target.closest('.modal').style.display = 'none';
        document.body.style.overflow = '';
    }
});

// Функція для перегляду замовлення
function viewOrder(orderId) {
    const detailsDiv = document.getElementById('orderDetails');
    detailsDiv.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Завантаження...</div>';

    fetch(`?action=get_order&id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                detailsDiv.innerHTML = data.html;
            } else {
                detailsDiv.innerHTML = '<div class="alert alert-danger">' + (data.message || 'Помилка завантаження даних') + '</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            detailsDiv.innerHTML = '<div class="alert alert-danger">Помилка завантаження даних</div>';
        });

    openModal('orderModal');
}

// Редагування замовлення
function editOrder(orderId) {
    currentOrderId = orderId;
    const row = document.querySelector(`tr[data-order-id="${orderId}"]`);
    const cells = row.querySelectorAll('td');

    const status = cells[3].querySelector('.badge').className.includes('status-draft') ? 'draft' :
        cells[3].querySelector('.badge').className.includes('status-new') ? 'new' :
            cells[3].querySelector('.badge').className.includes('status-in_progress') ? 'in_progress' : 'completed';

    // Заповнюємо форму
    document.querySelector('#orderEditForm input[name="order_id"]').value = orderId;
    document.querySelector('#orderEditForm select[name="status"]').value = status;
    document.querySelector('#orderEditForm textarea[name="admin_notes"]').value = '';

    openModal('orderEditModal');
}

// Видалення замовлення
function deleteOrder(orderId, orderNumber) {
    currentDeleteOrderId = orderId;
    document.getElementById('deleteOrderDetails').innerHTML = `
            <strong>Замовлення:</strong> ${orderNumber}<br>
            <strong>ID:</strong> ${orderId}
        `;
    openModal('deleteModal');
}

// Обробка форми редагування
document.getElementById('orderEditForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('?action=order_update', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message || 'Замовлення успішно оновлено', 'success');
                closeModal('orderEditModal');
                location.reload();
            } else {
                showNotification(data.message || 'Помилка оновлення замовлення', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Помилка оновлення замовлення', 'error');
        });
});

// Підтвердження видалення
document.getElementById('confirmDelete').addEventListener('click', function() {
    const formData = new FormData();
    formData.append('order_id', currentDeleteOrderId);

    fetch('?action=order_delete', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message || 'Замовлення успішно видалено', 'success');
                closeModal('deleteModal');
                location.reload();
            } else {
                showNotification(data.message || 'Помилка видалення замовлення', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Помилка видалення замовлення', 'error');
        });
});

// Масове вибір
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
    updateBulkActions();
});

// Оновлення масових дій
function updateBulkActions() {
    const selected = document.querySelectorAll('.row-checkbox:checked');
    const bulkActions = document.getElementById('bulkActions');
    const count = document.querySelector('.selected-count');

    if (selected.length > 0) {
        bulkActions.style.display = 'flex';
        count.textContent = selected.length;
    } else {
        bulkActions.style.display = 'none';
    }
}

// Слухачі для чекбоксів
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('row-checkbox')) {
        updateBulkActions();
    }
});

// Експорт замовлень
function exportOrders() {
    const params = new URLSearchParams(window.location.search);
    params.set('action', 'export_orders');
    window.open('?' + params.toString(), '_blank');
}

// Масове оновлення статусу
function bulkUpdateStatus(status) {
    const selectedOrders = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);

    if (selectedOrders.length === 0) {
        showNotification('Не вибрано жодного замовлення', 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('order_ids', JSON.stringify(selectedOrders));
    formData.append('status', status);

    fetch('?action=bulk_update_status', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message || 'Статус замовлень успішно оновлено', 'success');
                location.reload();
            } else {
                showNotification(data.message || 'Помилка оновлення статусу', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Помилка оновлення статусу', 'error');
        });
}

// Масове видалення
function bulkDelete() {
    const selectedOrders = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);

    if (selectedOrders.length === 0) {
        showNotification('Не вибрано жодного замовлення', 'warning');
        return;
    }

    if (!confirm(`Ви впевнені, що хочете видалити ${selectedOrders.length} замовлень? Ця дія незворотна.`)) {
        return;
    }

    const formData = new FormData();
    formData.append('order_ids', JSON.stringify(selectedOrders));

    fetch('?action=bulk_delete', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message || 'Замовлення успішно видалено', 'success');
                location.reload();
            } else {
                showNotification(data.message || 'Помилка видалення замовлень', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Помилка видалення замовлень', 'error');
        });
}

// Функція сортування таблиці
function sortTable(columnIndex, tableId) {
    const table = document.getElementById(tableId);
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));

    // Визначаємо напрямок сортування
    const currentSort = table.getAttribute('data-sort-column');
    const currentDirection = table.getAttribute('data-sort-direction') || 'asc';
    const newDirection = (currentSort == columnIndex && currentDirection === 'asc') ? 'desc' : 'asc';

    // Сортуємо рядки
    rows.sort((a, b) => {
        const aValue = a.cells[columnIndex].textContent.trim();
        const bValue = b.cells[columnIndex].textContent.trim();

        // Спробуємо порівняти як числа
        const aNum = parseFloat(aValue.replace(/[^\d.-]/g, ''));
        const bNum = parseFloat(bValue.replace(/[^\d.-]/g, ''));

        if (!isNaN(aNum) && !isNaN(bNum)) {
            return newDirection === 'asc' ? aNum - bNum : bNum - aNum;
        }

        // Порівнюємо як текст
        if (newDirection === 'asc') {
            return aValue.localeCompare(bValue, 'uk');
        } else {
            return bValue.localeCompare(aValue, 'uk');
        }
    });

    // Оновлюємо таблицю
    rows.forEach(row => tbody.appendChild(row));

    // Зберігаємо стан сортування
    table.setAttribute('data-sort-column', columnIndex);
    table.setAttribute('data-sort-direction', newDirection);

    // Оновлюємо індикатори сортування в заголовках
    const headers = table.querySelectorAll('th');
    headers.forEach((header, index) => {
        header.classList.remove('sort-asc', 'sort-desc');
        if (index === columnIndex) {
            header.classList.add('sort-' + newDirection);
        }
    });
}

// Функція показу повідомлень
function showNotification(message, type = 'info') {
    // Видаляємо існуючі повідомлення
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());

    // Створюємо нове повідомлення
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);

    // Автоматично видаляємо через 5 секунд
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

// Функція пошуку в таблиці
function searchTable() {
    const searchInput = document.getElementById('searchInput');
    if (!searchInput) return;

    const searchTerm = searchInput.value.toLowerCase();
    const rows = document.querySelectorAll('#ordersTable tbody tr');

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
}

// Ініціалізація при завантаженні сторінки
document.addEventListener('DOMContentLoaded', function() {
    // Автоматичне оновлення часу
    setInterval(function() {
        const timeElements = document.querySelectorAll('.auto-time');
        timeElements.forEach(element => {
            const timestamp = element.getAttribute('data-timestamp');
            if (timestamp) {
                const date = new Date(timestamp * 1000);
                element.textContent = date.toLocaleString('uk-UA');
            }
        });
    }, 60000); // Оновлюємо кожну хвилину

    // Ініціалізація підказок
    const tooltipElements = document.querySelectorAll('[title]');
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = this.getAttribute('title');
            document.body.appendChild(tooltip);

            const rect = this.getBoundingClientRect();
            tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';
            tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
        });

        element.addEventListener('mouseleave', function() {
            const tooltip = document.querySelector('.tooltip');
            if (tooltip) tooltip.remove();
        });
    });
});

// Обробка помилок JavaScript
window.addEventListener('error', function(e) {
    console.error('JavaScript Error:', e.error);
    showNotification('Виникла помилка. Перезавантажте сторінку.', 'error');
});

// Обробка відключення інтернету
window.addEventListener('online', function() {
    showNotification('З\'єднання відновлено', 'success');
});

window.addEventListener('offline', function() {
    showNotification('Відсутнє з\'єднання з інтернетом', 'warning');
});

// Функція для швидкого пошуку
function quickSearch(query) {
    const rows = document.querySelectorAll('#ordersTable tbody tr');
    const searchTerm = query.toLowerCase();

    rows.forEach(row => {
        const shouldShow = Array.from(row.cells).some(cell =>
            cell.textContent.toLowerCase().includes(searchTerm)
        );
        row.style.display = shouldShow ? '' : 'none';
    });
}

// Функція оновлення сторінки без перезавантаження
function refreshOrders() {
    const currentUrl = new URL(window.location);

    fetch(currentUrl.toString())
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const newDoc = parser.parseFromString(html, 'text/html');
            const newTable = newDoc.querySelector('#ordersTable tbody');
            const currentTable = document.querySelector('#ordersTable tbody');

            if (newTable && currentTable) {
                currentTable.innerHTML = newTable.innerHTML;
                showNotification('Дані оновлено', 'success');
            }
        })
        .catch(error => {
            console.error('Error refreshing orders:', error);
            showNotification('Помилка оновлення даних', 'error');
        });
}

// Гарячі клавіші
document.addEventListener('keydown', function(e) {
    // Ctrl + F для пошуку
    if (e.ctrlKey && e.key === 'f') {
        e.preventDefault();
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.focus();
        }
    }

    if (e.key === 'Escape') {
        const openModals = document.querySelectorAll('.modal[style*="block"]');
        openModals.forEach(modal => {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        });
    }

    if (e.key === 'F5' && !e.ctrlKey) {
        e.preventDefault();
        refreshOrders();
    }
});