// admin.js - JavaScript для адміністраторської панелі

// Глобальні змінні
let currentModal = null;

// Ініціалізація після завантаження DOM
document.addEventListener('DOMContentLoaded', function() {
    initializeModals();
    initializeUserManagement();
    initializeOrderManagement();
    initializeBulkActions();
});

// Ініціалізація модальних вікон
function initializeModals() {
    // Отримуємо всі модальні вікна
    const modals = document.querySelectorAll('.modal');

    modals.forEach(modal => {
        // Закривання модального вікна при кліку на хрестик
        const closeBtn = modal.querySelector('.close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => closeModal(modal));
        }

        // Закривання модального вікна при кліку поза його межами
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal(modal);
            }
        });
    });

    // Закривання модального вікна при натисканні Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && currentModal) {
            closeModal(currentModal);
        }
    });
}

// Функція відкриття модального вікна
function openModal(modal) {
    modal.style.display = 'block';
    currentModal = modal;
    document.body.style.overflow = 'hidden';
}

// Функція закриття модального вікна
function closeModal(modal) {
    modal.style.display = 'none';
    currentModal = null;
    document.body.style.overflow = 'auto';
}

// Ініціалізація управління користувачами
function initializeUserManagement() {
    // Обробка форм редагування користувачів
    const userForms = document.querySelectorAll('.user-edit-form');
    userForms.forEach(form => {
        form.addEventListener('submit', handleUserUpdate);
    });

    // Обробка видалення користувачів
    const deleteButtons = document.querySelectorAll('.delete-user-btn');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', handleUserDelete);
    });
}

// Оновлення користувача
function updateUser(userId) {
    const row = document.querySelector(`tr[data-user-id="${userId}"]`);
    if (!row) return;

    const isAdminCheckbox = row.querySelector('.user-admin-checkbox');
    const statusSelect = row.querySelector('.user-status-select');

    const formData = new FormData();
    formData.append('user_id', userId);
    formData.append('is_admin', isAdminCheckbox.checked ? '1' : '0');
    formData.append('status', statusSelect.value);

    fetch('?action=user_update', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('success', data.message);
                // Оновлюємо відображення статусу
                updateUserRowDisplay(userId, isAdminCheckbox.checked, statusSelect.value);
            } else {
                showNotification('error', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'Помилка при оновленні користувача');
        });
}

// Видалення користувача
function deleteUser(userId) {
    if (!confirm('Ви впевнені, що хочете видалити цього користувача?')) {
        return;
    }

    const formData = new FormData();
    formData.append('user_id', userId);

    fetch('?action=user_delete', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('success', data.message);
                // Видаляємо рядок з таблиці
                const row = document.querySelector(`tr[data-user-id="${userId}"]`);
                if (row) {
                    row.remove();
                }
            } else {
                showNotification('error', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'Помилка при видаленні користувача');
        });
}

// Оновлення відображення рядка користувача
function updateUserRowDisplay(userId, isAdmin, status) {
    const row = document.querySelector(`tr[data-user-id="${userId}"]`);
    if (!row) return;

    const roleBadge = row.querySelector('.role-badge');
    const statusBadge = row.querySelector('.status-badge');

    if (roleBadge) {
        roleBadge.textContent = isAdmin ? 'Адмін' : 'Користувач';
        roleBadge.className = `badge ${isAdmin ? 'admin' : 'user'}`;
    }

    if (statusBadge) {
        const statusLabels = {
            'active': 'Активний',
            'inactive': 'Неактивний',
            'banned': 'Заблокований'
        };
        statusBadge.textContent = statusLabels[status] || status;
        statusBadge.className = `badge ${status}`;
    }
}

// Ініціалізація управління замовленнями
function initializeOrderManagement() {
    // Обробка форм редагування замовлень
    const orderForms = document.querySelectorAll('.order-edit-form');
    orderForms.forEach(form => {
        form.addEventListener('submit', handleOrderUpdate);
    });
}

// Перегляд деталей замовлення
function viewOrder(orderId) {
    const modal = document.getElementById('orderModal');
    const orderDetails = document.getElementById('orderDetails');
    
    // Показуємо модальне вікно
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    // Показуємо індикатор завантаження
    orderDetails.innerHTML = `
        <div class="loading">
            <i class="fas fa-spinner fa-spin"></i>
            Завантаження деталей замовлення...
        </div>
    `;
    
    // Отримуємо дані замовлення
    fetch(`?action=get_order&id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                orderDetails.innerHTML = data.html;
            } else {
                orderDetails.innerHTML = `
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        ${data.message || 'Помилка завантаження даних'}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            orderDetails.innerHTML = `
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    Помилка завантаження даних
                </div>
            `;
        });
}

// Оновлення замовлення
function updateOrder(orderId) {
    const modal = document.getElementById('orderEditModal');
    const form = modal.querySelector('#orderEditForm');

    if (!form) return;

    const formData = new FormData(form);
    formData.append('order_id', orderId);

    fetch('?action=order_update', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('success', data.message);
                closeModal(modal);
                // Оновлюємо сторінку або конкретний рядок
                location.reload();
            } else {
                showNotification('error', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'Помилка при оновленні замовлення');
        });
}

// Видалення замовлення
function deleteOrder(orderId) {
    if (!confirm('Ви впевнені, що хочете видалити це замовлення?')) {
        return;
    }

    const formData = new FormData();
    formData.append('order_id', orderId);

    fetch('?action=order_delete', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('success', data.message);
                // Видаляємо рядок з таблиці
                const row = document.querySelector(`tr[data-order-id="${orderId}"]`);
                if (row) {
                    row.remove();
                }
            } else {
                showNotification('error', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'Помилка при видаленні замовлення');
        });
}

// Ініціалізація масових дій
function initializeBulkActions() {
    // Вибір всіх чекбоксів
    const selectAllCheckbox = document.getElementById('selectAllUsers');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const userCheckboxes = document.querySelectorAll('.user-checkbox');
            userCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkActionButtons();
        });
    }

    // Обробка індивідуальних чекбоксів
    const userCheckboxes = document.querySelectorAll('.user-checkbox');
    userCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActionButtons);
    });

    // Кнопки масових дій
    const bulkStatusBtn = document.getElementById('bulkStatusBtn');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');

    if (bulkStatusBtn) {
        bulkStatusBtn.addEventListener('click', showBulkStatusModal);
    }

    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', bulkDeleteUsers);
    }
}

// Оновлення стану кнопок масових дій
function updateBulkActionButtons() {
    const selectedUsers = document.querySelectorAll('.user-checkbox:checked');
    const bulkActions = document.querySelector('.bulk-actions');

    if (bulkActions) {
        if (selectedUsers.length > 0) {
            bulkActions.style.display = 'block';
            bulkActions.querySelector('.selected-count').textContent = selectedUsers.length;
        } else {
            bulkActions.style.display = 'none';
        }
    }
}

// Показати модальне вікно для зміни статусу
function showBulkStatusModal() {
    const modal = document.getElementById('bulkStatusModal');
    if (modal) {
        openModal(modal);
    }
}

// Масова зміна статусу користувачів
function bulkUpdateStatus() {
    const selectedUsers = Array.from(document.querySelectorAll('.user-checkbox:checked'))
        .map(checkbox => checkbox.value);

    const statusSelect = document.getElementById('bulkStatusSelect');
    const status = statusSelect.value;

    if (selectedUsers.length === 0 || !status) {
        showNotification('warning', 'Виберіть користувачів та статус');
        return;
    }

    const requestData = {
        user_ids: selectedUsers,
        status: status
    };

    fetch('?action=bulk_update_users', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestData)
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('success', data.message);
                closeModal(document.getElementById('bulkStatusModal'));
                location.reload();
            } else {
                showNotification('error', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'Помилка при оновленні статусу');
        });
}

// Масове видалення користувачів
function bulkDeleteUsers() {
    const selectedUsers = Array.from(document.querySelectorAll('.user-checkbox:checked'))
        .map(checkbox => checkbox.value);

    if (selectedUsers.length === 0) {
        showNotification('warning', 'Виберіть користувачів для видалення');
        return;
    }

    if (!confirm(`Ви впевнені, що хочете видалити ${selectedUsers.length} користувачів?`)) {
        return;
    }

    const requestData = {
        user_ids: selectedUsers
    };

    fetch('?action=bulk_delete_users', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestData)
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('success', data.message);
                location.reload();
            } else {
                showNotification('error', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'Помилка при видаленні користувачів');
        });
}

// Показ повідомлень
function showNotification(type, message) {
    // Створюємо елемент повідомлення
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas ${getNotificationIcon(type)}"></i>
            <span>${message}</span>
            <button class="notification-close" onclick="closeNotification(this)">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;

    // Додаємо повідомлення до контейнера
    let container = document.getElementById('notifications');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notifications';
        container.className = 'notifications-container';
        document.body.appendChild(container);
    }

    container.appendChild(notification);

    // Автоматичне видалення через 5 секунд
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 5000);
}

// Отримання іконки для повідомлення
function getNotificationIcon(type) {
    switch (type) {
        case 'success':
            return 'fa-check-circle';
        case 'error':
            return 'fa-exclamation-circle';
        case 'warning':
            return 'fa-exclamation-triangle';
        case 'info':
            return 'fa-info-circle';
        default:
            return 'fa-info-circle';
    }
}

// Закриття повідомлення
function closeNotification(button) {
    const notification = button.closest('.notification');
    if (notification && notification.parentNode) {
        notification.parentNode.removeChild(notification);
    }
}

// Експорт користувачів
function exportUsers() {
    const searchParam = new URLSearchParams(window.location.search).get('search') || '';
    const exportUrl = `?action=export_users${searchParam ? '&search=' + encodeURIComponent(searchParam) : ''}`;

    // Створюємо невидиме посилання для завантаження
    const link = document.createElement('a');
    link.href = exportUrl;
    link.download = `users_export_${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    showNotification('info', 'Експорт розпочато...');
}

// Пошук користувачів
function searchUsers() {
    const searchInput = document.getElementById('userSearch');
    if (searchInput) {
        const searchTerm = searchInput.value.trim();
        const currentUrl = new URL(window.location);

        if (searchTerm) {
            currentUrl.searchParams.set('search', searchTerm);
        } else {
            currentUrl.searchParams.delete('search');
        }

        // Видаляємо параметр сторінки при новому пошуку
        currentUrl.searchParams.delete('page');

        window.location.href = currentUrl.toString();
    }
}

// Очистка пошуку
function clearSearch() {
    const searchInput = document.getElementById('userSearch');
    if (searchInput) {
        searchInput.value = '';
        searchUsers();
    }
}

// Обробка натискання Enter в полі пошуку
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('userSearch');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchUsers();
            }
        });
    }
});

// Функції для роботи з таблицями
function sortTable(columnIndex, tableId = 'dataTable') {
    const table = document.getElementById(tableId);
    if (!table) return;

    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));

    // Визначаємо напрямок сортування
    const header = table.querySelectorAll('th')[columnIndex];
    const isAscending = !header.classList.contains('sort-desc');

    // Очищаємо всі класи сортування
    table.querySelectorAll('th').forEach(th => {
        th.classList.remove('sort-asc', 'sort-desc');
    });

    // Додаємо клас для поточного стовпця
    header.classList.add(isAscending ? 'sort-asc' : 'sort-desc');

    // Сортуємо рядки
    rows.sort((a, b) => {
        const aValue = a.cells[columnIndex].textContent.trim();
        const bValue = b.cells[columnIndex].textContent.trim();

        // Перевіряємо, чи це числа
        const aNum = parseFloat(aValue);
        const bNum = parseFloat(bValue);

        if (!isNaN(aNum) && !isNaN(bNum)) {
            return isAscending ? aNum - bNum : bNum - aNum;
        }

        // Сортування як рядки
        if (isAscending) {
            return aValue.localeCompare(bValue, 'uk');
        } else {
            return bValue.localeCompare(aValue, 'uk');
        }
    });

    // Вставляємо відсортовані рядки назад в таблицю
    rows.forEach(row => tbody.appendChild(row));
}

// Фільтрація таблиці
function filterTable(filterValue, columnIndex, tableId = 'dataTable') {
    const table = document.getElementById(tableId);
    if (!table) return;

    const rows = table.querySelectorAll('tbody tr');

    rows.forEach(row => {
        const cellValue = row.cells[columnIndex].textContent.trim().toLowerCase();
        const shouldShow = !filterValue || cellValue.includes(filterValue.toLowerCase());

        row.style.display = shouldShow ? '' : 'none';
    });
}

// Зміна кількості записів на сторінці
function changePageSize(newSize) {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('limit', newSize);
    currentUrl.searchParams.delete('page'); // Скидаємо на першу сторінку

    window.location.href = currentUrl.toString();
}

// Перехід на конкретну сторінку
function goToPage(page) {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('page', page);

    window.location.href = currentUrl.toString();
}

// Закриття модальних вікон
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal') || e.target.classList.contains('close') || e.target.classList.contains('btn-close')) {
        e.target.closest('.modal').style.display = 'none';
        document.body.style.overflow = '';
    }
});