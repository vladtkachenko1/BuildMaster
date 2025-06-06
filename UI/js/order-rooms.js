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

    // Відкриття форми проекту для нової кімнати (БЕЗ модального вікна)
    if (addRoomBtn) {
        addRoomBtn.addEventListener('click', function() {
            // Очищуємо дані попередньої кімнати
            sessionStorage.removeItem('selected_room_type_id');
            sessionStorage.removeItem('selected_room_name');
            sessionStorage.removeItem('wall_area');
            sessionStorage.removeItem('room_area');

            // Переходимо прямо до форми проекту
            window.location.href = '/BuildMaster/calculator/project-form';
        });
    }

    if (firstRoomBtn) {
        firstRoomBtn.addEventListener('click', function() {
            // Очищуємо дані попередньої кімнати
            sessionStorage.removeItem('selected_room_type_id');
            sessionStorage.removeItem('selected_room_name');
            sessionStorage.removeItem('wall_area');
            sessionStorage.removeItem('room_area');

            // Переходимо прямо до форми проекту
            window.location.href = '/BuildMaster/calculator/project-form';
        });
    }

    // Відкриття модального вікна оформлення замовлення
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

                        window.location.href = data.redirect_url;
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

    // Обробка кнопок редагування кімнат
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-room-btn') || e.target.parentElement.classList.contains('edit-room-btn')) {
            const button = e.target.classList.contains('edit-room-btn') ? e.target : e.target.parentElement;
            const roomId = button.getAttribute('data-room-id');
            console.log('Editing room:', roomId);
            editRoom(roomId);
        }

        if (e.target.classList.contains('remove-room-btn') || e.target.parentElement.classList.contains('remove-room-btn')) {
            const button = e.target.classList.contains('remove-room-btn') ? e.target : e.target.parentElement;
            const roomId = button.getAttribute('data-room-id');

            if (confirm('Ви впевнені, що хочете видалити цю кімнату з замовлення?')) {
                console.log('Removing room:', roomId);
                removeRoom(roomId);
            }
        }
    });

    // Функція редагування кімнати
    function editRoom(roomId) {
        console.log('Edit room function called with ID:', roomId);

        fetch('/BuildMaster/calculator/edit-room', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ room_id: roomId })
        })
            .then(response => {
                console.log('Edit room response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Edit room response:', data);

                if (data.success) {
                    window.location.href = data.redirect_url;
                } else {
                    showError(data.error || 'Помилка редагування кімнати');
                }
            })
            .catch(error => {
                console.error('Edit room error:', error);
                showError('Помилка з\'єднання з сервером');
            });
    }

    // Функція видалення кімнати
    function removeRoom(roomId) {
        console.log('Remove room function called with ID:', roomId);

        fetch('/BuildMaster/calculator/remove-room', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ room_id: roomId })
        })
            .then(response => {
                console.log('Remove room response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Remove room response:', data);

                if (data.success) {
                    // Перезавантажуємо сторінку для оновлення списку
                    window.location.reload();
                } else {
                    showError(data.error || 'Помилка видалення кімнати');
                }
            })
            .catch(error => {
                console.error('Remove room error:', error);
                showError('Помилка з\'єднання з сервером');
            });
    }

    // Функція показу помилки
    function showError(message) {
        console.log('Showing error:', message);
        document.getElementById('error-message').textContent = message;
        errorModal.style.display = 'flex';
    }

    // Анімації для кнопок
    const buttons = document.querySelectorAll('.primary-btn, .secondary-btn, .edit-room-btn, .remove-room-btn');
    buttons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });

        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Автозаповнення полів з localStorage (якщо користувач раніше щось вводив)
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