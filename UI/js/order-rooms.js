document.addEventListener('DOMContentLoaded', function() {
    // Елементи
    const addRoomBtn = document.getElementById('add-room-btn');
    const firstRoomBtn = document.getElementById('first-room-btn');
    const checkoutBtn = document.getElementById('checkout-btn');

    // Модальні вікна
    const checkoutModal = document.getElementById('checkout-modal');
    const errorModal = document.getElementById('error-modal');

    // Кнопки закриття модальних вікон
    const closeCheckoutModal = document.getElementById('close-checkout-modal');
    const closeErrorBtn = document.getElementById('close-error-btn');

    // Кнопки підтвердження
    const confirmCheckout = document.getElementById('confirm-checkout');
    const cancelCheckout = document.getElementById('cancel-checkout');

    // Обробка кнопки додавання першої кімнати
    if (firstRoomBtn) {
        firstRoomBtn.addEventListener('click', function() {
            // Очищуємо дані попередньої кімнати і режим редагування
            clearRoomSessionData();

            // Переходимо прямо до форми проекту
            window.location.href = '/BuildMaster/calculator/project-form';
        });
    }

    // Обробка кнопки додавання нової кімнати
    if (addRoomBtn) {
        addRoomBtn.addEventListener('click', function() {
            // Очищуємо дані попередньої кімнати і режим редагування
            clearRoomSessionData();

            // Переходимо прямо до форми проекту
            window.location.href = '/BuildMaster/calculator/project-form';
        });
    }

    // Функція очищення даних сесії
    function clearRoomSessionData() {
        // Очищуємо sessionStorage
        sessionStorage.removeItem('selected_room_type_id');
        sessionStorage.removeItem('selected_room_name');
        sessionStorage.removeItem('wall_area');
        sessionStorage.removeItem('room_area');
        sessionStorage.removeItem('selected_services');
        sessionStorage.removeItem('editing_room_id');

        // Також очищуємо localStorage якщо використовується
        localStorage.removeItem('selected_room_type_id');
        localStorage.removeItem('selected_room_name');
        localStorage.removeItem('wall_area');
        localStorage.removeItem('room_area');
        localStorage.removeItem('selected_services');
        localStorage.removeItem('editing_room_id');
    }

    // Обробка кнопки відкриття модального вікна оформлення замовлення
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', function() {
            checkoutModal.style.display = 'flex';
        });
    }

    // Закриття модальних вікон
    if (closeCheckoutModal) {
        closeCheckoutModal.addEventListener('click', function() {
            checkoutModal.style.display = 'none';
        });
    }

    if (closeErrorBtn) {
        closeErrorBtn.addEventListener('click', function() {
            errorModal.style.display = 'none';
        });
    }

    if (cancelCheckout) {
        cancelCheckout.addEventListener('click', function() {
            checkoutModal.style.display = 'none';
        });
    }

    // Закриття модальних вікон при кліку поза ними
    window.addEventListener('click', function(e) {
        if (e.target === checkoutModal) {
            checkoutModal.style.display = 'none';
        }
        if (e.target === errorModal) {
            errorModal.style.display = 'none';
        }
    });

    // Оформлення замовлення
    if (confirmCheckout) {
        confirmCheckout.addEventListener('click', function() {
            const guestName = document.getElementById('guest-name').value.trim();
            const guestEmail = document.getElementById('guest-email').value.trim();
            const guestPhone = document.getElementById('guest-phone').value.trim();
            const notes = document.getElementById('order-notes').value.trim();

            console.log('Checkout data:', { guestName, guestEmail, guestPhone, notes });

            if (!guestName || !guestEmail || !guestPhone) {
                showError('Будь ласка, заповніть всі обов\'язкові поля');
                return;
            }

            // Валідація email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(guestEmail)) {
                showError('Будь ласка, введіть коректну email адресу');
                return;
            }

            // Валідація телефону
            const phoneRegex = /^[\+]?[0-9\s\-\(\)]{10,}$/;
            if (!phoneRegex.test(guestPhone)) {
                showError('Будь ласка, введіть коректний номер телефону');
                return;
            }

            // Показуємо індикатор завантаження
            confirmCheckout.disabled = true;
            confirmCheckout.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Обробка...';

            // Відправляємо дані для оформлення замовлення
            const orderData = {
                guest_name: guestName,
                guest_email: guestEmail,
                guest_phone: guestPhone,
                notes: notes
            };

            console.log('Sending order data:', orderData);

            fetch('/BuildMaster/calculator/complete-order', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(orderData)
            })
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);

                    if (data.success) {
                        // Очищуємо localStorage після успішного замовлення
                        localStorage.removeItem('guest_name');
                        localStorage.removeItem('guest_email');
                        localStorage.removeItem('guest_phone');

                        // Закриваємо модальне вікно оформлення
                        checkoutModal.style.display = 'none';

                        // Показуємо повідомлення про успіх з callback для перенаправлення
                        showSuccessMessage('Ваше замовлення прийняте, очікуйте відповідь менеджера.', function() {
                            // Очищуємо всі дані замовлення
                            localStorage.clear();
                            sessionStorage.clear();

                            // Перенаправляємо на сторінку кімнат замовлення
                            window.location.href = '/BuildMaster/calculator/order-rooms';
                        });
                    } else {
                        showError(data.error || 'Помилка оформлення замовлення');
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    showError('Помилка з\'єднання з сервером');
                })
                .finally(() => {
                    // Повертаємо кнопку в нормальний стан
                    confirmCheckout.disabled = false;
                    confirmCheckout.innerHTML = '<i class="fas fa-check"></i> Підтвердити замовлення';
                });
        });
    }

    // Обробка кнопок редагування та видалення кімнат
    document.addEventListener('click', function(e) {
        // Обробка кнопки редагування - пряме перенаправлення на сторінку редагування
        if (e.target.classList.contains('edit-room-btn') || e.target.parentElement.classList.contains('edit-room-btn')) {
            e.preventDefault();

            const button = e.target.classList.contains('edit-room-btn') ? e.target : e.target.parentElement;
            const roomId = button.getAttribute('data-room-id');

            console.log('Edit room clicked, roomId:', roomId);

            if (roomId) {
                window.location.href = '/BuildMaster/calculator/room-edit/' + roomId;
            } else {
                console.error('Room ID not found');
                showError('Ідентифікатор кімнати не знайдено');
            }
        }

        // Обробка кнопки видалення кімнати
        if (e.target.classList.contains('remove-room-btn') || e.target.parentElement.classList.contains('remove-room-btn')) {
            e.preventDefault();

            const button = e.target.classList.contains('remove-room-btn') ? e.target : e.target.parentElement;
            const roomId = button.getAttribute('data-room-id');

            if (confirm('Ви впевнені, що хочете видалити цю кімнату з замовлення?')) {
                // Показуємо індикатор завантаження
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                // Відправляємо запит на видалення
                fetch('/BuildMaster/calculator/remove-room', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        room_id: roomId
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Перезавантажуємо сторінку для оновлення списку кімнат
                            window.location.reload();
                        } else {
                            showError(data.error || 'Помилка видалення кімнати');
                            // Повертаємо кнопку в нормальний стан
                            button.disabled = false;
                            button.innerHTML = '<i class="fas fa-trash"></i>';
                        }
                    })
                    .catch(error => {
                        console.error('Remove room error:', error);
                        showError('Помилка з\'єднання з сервером');
                        // Повертаємо кнопку в нормальний стан
                        button.disabled = false;
                        button.innerHTML = '<i class="fas fa-trash"></i>';
                    });
            }
        }
    });

    // Функція відображення помилок
    function showError(message) {
        console.log('Showing error:', message);
        document.getElementById('error-message').textContent = message;
        errorModal.style.display = 'flex';
    }

    // Анімації для кнопок
    const buttons = document.querySelectorAll('.primary-btn, .secondary-btn, .edit-room-btn, .remove-room-btn');
    buttons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            if (!this.disabled) {
                this.style.transform = 'translateY(-2px)';
            }
        });

        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Автозаповнення полів з localStorage
    const guestNameField = document.getElementById('guest-name');
    const guestEmailField = document.getElementById('guest-email');
    const guestPhoneField = document.getElementById('guest-phone');

    if (guestNameField && guestEmailField && guestPhoneField) {
        const savedName = localStorage.getItem('guest_name');
        const savedEmail = localStorage.getItem('guest_email');
        const savedPhone = localStorage.getItem('guest_phone');

        if (savedName) guestNameField.value = savedName;
        if (savedEmail) guestEmailField.value = savedEmail;
        if (savedPhone) guestPhoneField.value = savedPhone;

        // Збереження даних при введенні
        guestNameField.addEventListener('input', function() {
            localStorage.setItem('guest_name', this.value);
        });

        guestEmailField.addEventListener('input', function() {
            localStorage.setItem('guest_email', this.value);
        });

        guestPhoneField.addEventListener('input', function() {
            localStorage.setItem('guest_phone', this.value);
        });

        // Форматування номера телефону
        guestPhoneField.addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');

            if (value.startsWith('380')) {
                value = '+' + value;
                value = value.replace(/(\+380)(\d{2})(\d{3})(\d{2})(\d{2})/, '$1 $2 $3 $4 $5');
            } else if (value.startsWith('0')) {
                value = value.replace(/(\d{3})(\d{3})(\d{2})(\d{2})/, '$1 $2 $3 $4');
            }

            this.value = value;
        });
    }
});

// ВИПРАВЛЕНА функція showSuccessMessage з підтримкою callback
function showSuccessMessage(message, callback) {
    // Створюємо елемент повідомлення
    const successMessage = document.createElement('div');
    successMessage.className = 'success-message';
    successMessage.innerHTML = `
        <div class="success-content">
            <i class="fas fa-check-circle"></i>
            <h3>${message}</h3>
            <button class="success-close-btn">Зрозуміло</button>
        </div>
    `;

    // Додаємо стилі
    const style = document.createElement('style');
    style.textContent = `
        .success-message {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .success-content {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            text-align: center;
            max-width: 400px;
        }
        .success-content i {
            font-size: 3rem;
            color: #4CAF50;
            margin-bottom: 1rem;
        }
        .success-content h3 {
            margin: 0 0 1.5rem 0;
            color: #333;
            font-size: 1.2rem;
        }
        .success-close-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
        }
        .success-close-btn:hover {
            background: #45a049;
        }
    `;

    document.head.appendChild(style);
    document.body.appendChild(successMessage);

    // Функція для закриття повідомлення та виконання callback
    function closeMessage() {
        document.body.removeChild(successMessage);
        document.head.removeChild(style);

        // Виконуємо callback якщо він передан
        if (typeof callback === 'function') {
            callback();
        }
    }

    // Обробник закриття повідомлення
    const closeBtn = successMessage.querySelector('.success-close-btn');
    closeBtn.addEventListener('click', closeMessage);

    // Закриття при кліку поза модальним вікном
    successMessage.addEventListener('click', function(e) {
        if (e.target === successMessage) {
            closeMessage();
        }
    });
}