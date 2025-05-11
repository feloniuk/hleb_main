/**
 * JavaScript функції для клієнтської частини
 * "Одеський Коровай"
 */

document.addEventListener('DOMContentLoaded', function() {
    // Ініціалізація кошика при завантаженні сторінки
    initializeCart();
    
    // Оновлення лічильника товарів у кошику на всіх сторінках
    updateCartCounter();
    
    // Додавання обробників подій для кнопок додавання товару
    setupAddToCartButtons();
    
    // Налаштування форми валідації 
    setupFormValidation();
});

/**
 * Ініціалізація кошика
 */
function initializeCart() {
    // Перевірка, чи є кошик у localStorage
    if (!localStorage.getItem('cart')) {
        localStorage.setItem('cart', JSON.stringify([]));
    }
}

/**
 * Оновлення лічильника товарів у кошику
 */
function updateCartCounter() {
    const cartCounters = document.querySelectorAll('.cart-count');
    if (cartCounters.length > 0) {
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        cartCounters.forEach(counter => {
            counter.textContent = cart.length;
        });
    }
}

/**
 * Налаштування кнопок додавання товару до кошика
 */
function setupAddToCartButtons() {
    const addButtons = document.querySelectorAll('.add-to-cart, .add-similar-to-cart');
    
    addButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            const productName = this.getAttribute('data-name');
            const productPrice = parseFloat(this.getAttribute('data-price'));
            const quantity = 1;
            
            addToCart(productId, productName, productPrice, quantity);
        });
    });
}

/**
 * Додавання товару до кошика
 * 
 * @param {string} productId ID товару
 * @param {string} productName Назва товару
 * @param {number} productPrice Ціна товару
 * @param {number} quantity Кількість
 */
function addToCart(productId, productName, productPrice, quantity = 1) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    
    // Пошук товару в кошику
    const existingItemIndex = cart.findIndex(item => item.id === productId);
    
    if (existingItemIndex !== -1) {
        // Якщо товар вже є, збільшуємо кількість
        cart[existingItemIndex].quantity += quantity;
    } else {
        // Якщо товару немає, додаємо його
        cart.push({
            id: productId,
            name: productName,
            price: productPrice,
            quantity: quantity
        });
    }
    
    // Зберігаємо оновлений кошик
    localStorage.setItem('cart', JSON.stringify(cart));
    
    // Оновлюємо лічильник кошика
    updateCartCounter();
    
    // Повідомлення про успішне додавання
    showAddToCartNotification(productName, quantity);
    
    return cart;
}

/**
 * Видалення товару з кошика
 * 
 * @param {string} productId ID товару
 */
function removeFromCart(productId) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    
    // Пошук товару в кошику
    const existingItemIndex = cart.findIndex(item => item.id === productId);
    
    if (existingItemIndex !== -1) {
        // Видалення товару
        cart.splice(existingItemIndex, 1);
        
        // Зберігаємо оновлений кошик
        localStorage.setItem('cart', JSON.stringify(cart));
        
        // Оновлюємо лічильник кошика
        updateCartCounter();
    }
    
    return cart;
}

/**
 * Оновлення кількості товару в кошику
 * 
 * @param {string} productId ID товару
 * @param {number} quantity Нова кількість
 */
function updateCartItemQuantity(productId, quantity) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    
    // Пошук товару в кошику
    const existingItemIndex = cart.findIndex(item => item.id === productId);
    
    if (existingItemIndex !== -1) {
        // Оновлення кількості
        cart[existingItemIndex].quantity = quantity;
        
        // Зберігаємо оновлений кошик
        localStorage.setItem('cart', JSON.stringify(cart));
        
        // Оновлюємо лічильник кошика
        updateCartCounter();
    }
    
    return cart;
}

/**
 * Очищення кошика
 */
function clearCart() {
    localStorage.setItem('cart', JSON.stringify([]));
    updateCartCounter();
}

/**
 * Розрахунок загальної суми кошика
 * 
 * @returns {number} Загальна сума
 */
function calculateCartTotal() {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    return cart.reduce((total, item) => total + (item.price * item.quantity), 0);
}

/**
 * Показати повідомлення про додавання товару
 * 
 * @param {string} productName Назва товару
 * @param {number} quantity Кількість
 */
function showAddToCartNotification(productName, quantity) {
    // Перевіряємо, чи існує елемент для сповіщення
    let notificationElement = document.getElementById('add-to-cart-notification');
    
    if (!notificationElement) {
        // Створюємо елемент для сповіщення, якщо він відсутній
        notificationElement = document.createElement('div');
        notificationElement.id = 'add-to-cart-notification';
        notificationElement.className = 'toast-notification';
        document.body.appendChild(notificationElement);
        
        // Додаємо стилі для сповіщення
        const style = document.createElement('style');
        style.textContent = `
            .toast-notification {
                position: fixed;
                bottom: 20px;
                right: 20px;
                background-color: #28a745;
                color: white;
                padding: 15px 20px;
                border-radius: 5px;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                z-index: 1050;
                transition: opacity 0.3s ease-in-out;
                opacity: 0;
            }
            .toast-notification.show {
                opacity: 1;
            }
        `;
        document.head.appendChild(style);
    }
    
    // Оновлюємо вміст сповіщення
    notificationElement.innerHTML = `
        <i class="fas fa-check-circle me-2"></i>
        <strong>${productName}</strong> додано до кошика (${quantity} шт.)
    `;
    
    // Показуємо сповіщення
    notificationElement.classList.add('show');
    
    // Автоматично сховати сповіщення через 3 секунди
    setTimeout(() => {
        notificationElement.classList.remove('show');
    }, 3000);
}

/**
 * Налаштування валідації форм
 */
function setupFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
}