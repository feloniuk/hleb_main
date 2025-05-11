<?php
$pageTitle = 'Кошик';

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Перевірка доступу
if (!checkAccess(['client'])) {
    header("Location: ../../index.php");
    exit;
}

$connection = connectDatabase();
$success = '';
$error = '';

// Отримання даних клієнта
$clientId = $_SESSION['id'];
$clientQuery = "SELECT * FROM klientu WHERE id = ?";
$stmt = mysqli_prepare($connection, $clientQuery);
mysqli_stmt_bind_param($stmt, "i", $clientId);
mysqli_stmt_execute($stmt);
$clientResult = mysqli_stmt_get_result($stmt);
$client = mysqli_fetch_assoc($clientResult);

include_once '../../includes/header.php';
?>

<!-- Головне меню -->
<div class="row mb-4">
    <div class="col-md-12">
        <nav class="nav main-menu nav-pills nav-fill">
            <a class="nav-link" href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Головна
            </a>
            <a class="nav-link" href="orders.php">
                <i class="fas fa-clipboard-list"></i> Мої замовлення
            </a>
            <a class="nav-link" href="products.php">
                <i class="fas fa-bread-slice"></i> Каталог продукції
            </a>
            <a class="nav-link active" href="cart.php">
                <i class="fas fa-shopping-cart"></i> Кошик
            </a>
            <a class="nav-link" href="profile.php">
                <i class="fas fa-user"></i> Профіль
            </a>
        </nav>
    </div>
</div>

<?php if (!empty($success)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo htmlspecialchars($success); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php if (!empty($error)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo htmlspecialchars($error); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-shopping-cart me-2"></i> Кошик покупок
        </h5>
    </div>
    <div class="card-body">
        <div id="cart-content">
            <!-- Вміст кошика буде заповнено через JavaScript -->
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Завантаження...</span>
                </div>
                <p class="mt-3">Завантаження кошика...</p>
            </div>
        </div>
    </div>
</div>

<!-- Форма оформлення замовлення -->
<div class="card mb-4" id="checkout-form" style="display: none;">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-clipboard-check me-2"></i> Оформлення замовлення
        </h5>
    </div>
    <div class="card-body">
        <form action="process_order.php" method="POST" id="order-form" class="needs-validation" novalidate>
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Інформація про замовлення</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="client-name" class="form-label">Клієнт</label>
                                <input type="text" class="form-control" id="client-name" value="<?php echo htmlspecialchars($client['name']); ?>" readonly>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="data" class="form-label">Дата доставки</label>
                                    <input type="date" class="form-control" id="data" name="data" value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                                    <div class="invalid-feedback">
                                        Виберіть дату доставки (мінімум завтрашній день)
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="doba" class="form-label">Зміна доставки</label>
                                    <select class="form-select" id="doba" name="doba" required>
                                        <option value="денна">Денна (8:00 - 14:00)</option>
                                        <option value="нічна">Нічна (22:00 - 4:00)</option>
                                    </select>
                                    <div class="invalid-feedback">
                                        Виберіть зміну доставки
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="delivery-address" class="form-label">Адреса доставки</label>
                                <textarea class="form-control" id="delivery-address" rows="2" readonly><?php echo htmlspecialchars($client['city'] . ', ' . $client['adres']); ?></textarea>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i> Щоб змінити адресу доставки, оновіть дані у <a href="profile.php">профілі</a>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="comments" class="form-label">Коментар до замовлення</label>
                                <textarea class="form-control" id="comments" name="comments" rows="3" placeholder="Додаткова інформація щодо замовлення"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h6 class="mb-0">Загальна інформація</h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> Мінімальний час для оформлення замовлення - 1 день.
                            </div>
                            
                            <p><i class="fas fa-truck me-2"></i> <strong>Доставка:</strong> Доставка здійснюється власним транспортом підприємства.</p>
                            <p><i class="fas fa-money-bill-wave me-2"></i> <strong>Оплата:</strong> Безготівковий розрахунок або за фактом отримання.</p>
                            <p><i class="fas fa-box me-2"></i> <strong>Мінімальне замовлення:</strong> від 200 грн.</p>
                            
                            <div class="alert alert-warning mt-3">
                                <i class="fas fa-exclamation-triangle me-2"></i> Скасування замовлення можливе не пізніше ніж за 6 годин до доставки.
                            </div>
                            
                            <div class="mt-4">
                                <h6><strong>Загальна сума: <span id="checkout-total">0.00</span> грн</strong></h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Прихований div для зберігання даних кошика -->
            <div id="cart-data" style="display: none;"></div>
            
            <div class="d-flex justify-content-between">
                <a href="products.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Повернутися до покупок
                </a>
                <button type="submit" class="btn btn-success" id="submit-order">
                    <i class="fas fa-check me-1"></i> Оформити замовлення
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Шаблон для пустого кошика -->
<template id="empty-cart-template">
    <div class="text-center py-5">
        <i class="fas fa-shopping-cart fa-4x mb-3 text-muted"></i>
        <h4>Ваш кошик порожній</h4>
        <p class="text-muted">Додайте товари з каталогу для оформлення замовлення</p>
        <a href="products.php" class="btn btn-primary mt-3">
            <i class="fas fa-shopping-bag me-1"></i> Перейти до каталогу
        </a>
    </div>
</template>

<!-- Шаблон для таблиці кошика -->
<template id="cart-table-template">
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Продукт</th>
                    <th>Ціна</th>
                    <th>Кількість</th>
                    <th>Сума</th>
                    <th>Дії</th>
                </tr>
            </thead>
            <tbody id="cart-items">
                <!-- Товари будуть додані через JavaScript -->
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-end"><strong>Загальна сума:</strong></td>
                    <td colspan="2" id="cart-total"><strong>0.00 грн</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <div class="d-flex justify-content-between mt-3">
        <button class="btn btn-outline-danger" id="clear-cart">
            <i class="fas fa-trash me-1"></i> Очистити кошик
        </button>
        <button class="btn btn-primary" id="proceed-to-checkout">
            <i class="fas fa-check me-1"></i> Оформити замовлення
        </button>
    </div>
</template>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Функція для оновлення вмісту кошика
        function updateCart() {
            const cartContent = document.getElementById('cart-content');
            const cart = JSON.parse(localStorage.getItem('cart')) || [];
            
            // Очищаємо попередній вміст
            cartContent.innerHTML = '';
            
            if (cart.length === 0) {
                // Кошик порожній
                const template = document.getElementById('empty-cart-template');
                cartContent.appendChild(template.content.cloneNode(true));
                document.getElementById('checkout-form').style.display = 'none';
            } else {
                // У кошику є товари
                const template = document.getElementById('cart-table-template');
                cartContent.appendChild(template.content.cloneNode(true));
                
                // Заповнюємо таблицю товарами
                const tbody = document.getElementById('cart-items');
                let totalAmount = 0;
                
                cart.forEach((item, index) => {
                    const row = document.createElement('tr');
                    const rowTotal = item.price * item.quantity;
                    totalAmount += rowTotal;
                    
                    row.innerHTML = `
                        <td>${index + 1}</td>
                        <td>${item.name}</td>
                        <td>${item.price.toFixed(2)} грн</td>
                        <td>
                            <div class="input-group input-group-sm">
                                <button class="btn btn-outline-secondary decrease-qty" data-id="${item.id}">-</button>
                                <input type="number" class="form-control text-center item-qty" value="${item.quantity}" min="1" data-id="${item.id}">
                                <button class="btn btn-outline-secondary increase-qty" data-id="${item.id}">+</button>
                            </div>
                        </td>
                        <td>${rowTotal.toFixed(2)} грн</td>
                        <td>
                            <button class="btn btn-sm btn-danger remove-item" data-id="${item.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    `;
                    
                    tbody.appendChild(row);
                });
                
                // Оновлюємо загальну суму
                document.getElementById('cart-total').innerHTML = `<strong>${totalAmount.toFixed(2)} грн</strong>`;
                document.getElementById('checkout-total').textContent = totalAmount.toFixed(2);
                
                // Додаємо обробники подій
                addCartEventListeners();
                
                // Підготовка даних для відправки на сервер
                const cartData = document.getElementById('cart-data');
                cartData.innerHTML = '';
                
                cart.forEach((item, index) => {
                    const productInput = document.createElement('input');
                    productInput.type = 'hidden';
                    productInput.name = `products[${index}]`;
                    productInput.value = item.id;
                    cartData.appendChild(productInput);
                    
                    const quantityInput = document.createElement('input');
                    quantityInput.type = 'hidden';
                    quantityInput.name = `quantities[${index}]`;
                    quantityInput.value = item.quantity;
                    cartData.appendChild(quantityInput);
                });
            }
        }
        
        // Функція для додавання обробників подій для елементів кошика
        function addCartEventListeners() {
            // Кнопки для зменшення кількості
            document.querySelectorAll('.decrease-qty').forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.getAttribute('data-id');
                    updateQuantity(productId, -1);
                });
            });
            
            // Кнопки для збільшення кількості
            document.querySelectorAll('.increase-qty').forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.getAttribute('data-id');
                    updateQuantity(productId, 1);
                });
            });
            
            // Поля для введення кількості
            document.querySelectorAll('.item-qty').forEach(input => {
                input.addEventListener('change', function() {
                    const productId = this.getAttribute('data-id');
                    const newQuantity = parseInt(this.value);
                    
                    if (newQuantity < 1) {
                        this.value = 1;
                        updateCartItem(productId, 1);
                    } else {
                        updateCartItem(productId, newQuantity);
                    }
                });
            });
            
            // Кнопки видалення товару
            document.querySelectorAll('.remove-item').forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.getAttribute('data-id');
                    removeFromCart(productId);
                });
            });
            
            // Кнопка очищення кошика
            document.getElementById('clear-cart').addEventListener('click', function() {
                if (confirm('Ви впевнені, що хочете очистити кошик?')) {
                    localStorage.removeItem('cart');
                    updateCart();
                }
            });
            
            // Кнопка оформлення замовлення
            document.getElementById('proceed-to-checkout').addEventListener('click', function() {
                document.getElementById('checkout-form').style.display = 'block';
                window.scrollTo({
                    top: document.getElementById('checkout-form').offsetTop - 20,
                    behavior: 'smooth'
                });
            });
        }
        
        // Функція для оновлення кількості товару
        function updateQuantity(productId, change) {
            const cart = JSON.parse(localStorage.getItem('cart')) || [];
            const index = cart.findIndex(item => item.id === productId);
            
            if (index !== -1) {
                cart[index].quantity += change;
                
                if (cart[index].quantity < 1) {
                    cart[index].quantity = 1;
                }
                
                localStorage.setItem('cart', JSON.stringify(cart));
                updateCart();
            }
        }
        
        // Функція для встановлення конкретної кількості товару
        function updateCartItem(productId, quantity) {
            const cart = JSON.parse(localStorage.getItem('cart')) || [];
            const index = cart.findIndex(item => item.id === productId);
            
            if (index !== -1) {
                cart[index].quantity = quantity;
                localStorage.setItem('cart', JSON.stringify(cart));
                updateCart();
            }
        }
        
        // Функція для видалення товару з кошика
        function removeFromCart(productId) {
            const cart = JSON.parse(localStorage.getItem('cart')) || [];
            const index = cart.findIndex(item => item.id === productId);
            
            if (index !== -1) {
                if (confirm(`Видалити товар "${cart[index].name}" з кошика?`)) {
                    cart.splice(index, 1);
                    localStorage.setItem('cart', JSON.stringify(cart));
                    updateCart();
                }
            }
        }
        
        // Ініціалізація кошика при завантаженні сторінки
        updateCart();
        
        // Валідація форми замовлення
        document.getElementById('order-form').addEventListener('submit', function(event) {
            if (!this.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            this.classList.add('was-validated');
            
            // Додаткова перевірка кошика
            const cart = JSON.parse(localStorage.getItem('cart')) || [];
            if (cart.length === 0) {
                event.preventDefault();
                alert('Кошик порожній. Додайте товари перед оформленням замовлення.');
            }
            
            // Перевірка мінімальної суми замовлення
            const totalAmount = cart.reduce((total, item) => total + (item.price * item.quantity), 0);
            if (totalAmount < 200) {
                event.preventDefault();
                alert('Мінімальна сума замовлення - 200 грн.');
            }
        });
    });
</script>

<?php include_once '../../includes/footer.php'; ?>