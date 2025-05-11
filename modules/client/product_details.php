<?php
$pageTitle = 'Деталі продукту';

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Перевірка доступу
if (!checkAccess(['client'])) {
    header("Location: ../../index.php");
    exit;
}

$connection = connectDatabase();

// Перевірка наявності ID продукту
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($productId <= 0) {
    header("Location: products.php");
    exit;
}

// Отримання інформації про продукт
$productQuery = "SELECT * FROM product WHERE id = ?";
$stmt = mysqli_prepare($connection, $productQuery);
mysqli_stmt_bind_param($stmt, "i", $productId);
mysqli_stmt_execute($stmt);
$productResult = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($productResult) === 0) {
    header("Location: products.php");
    exit;
}

$product = mysqli_fetch_assoc($productResult);

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
            <a class="nav-link active" href="products.php">
                <i class="fas fa-bread-slice"></i> Каталог продукції
            </a>
            <a class="nav-link" href="cart.php">
                <i class="fas fa-shopping-cart"></i> Кошик
            </a>
            <a class="nav-link" href="profile.php">
                <i class="fas fa-user"></i> Профіль
            </a>
        </nav>
    </div>
</div>

<!-- Навігаційні хлібні крихти -->
<div class="row mb-4">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Головна</a></li>
                <li class="breadcrumb-item"><a href="products.php">Каталог продукції</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product['nazvanie']); ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <!-- Зображення продукту -->
            <div class="col-md-5">
                <?php 
                $imagePath = !empty($product['image']) ? '../../' . $product['image'] : '../../assets/img/product-placeholder.jpg';
                ?>
                <div class="product-image-container">
                    <img src="<?php echo $imagePath; ?>" class="img-fluid rounded" alt="<?php echo htmlspecialchars($product['nazvanie']); ?>">
                </div>
            </div>
            
            <!-- Інформація про продукт -->
            <div class="col-md-7">
                <h3><?php echo htmlspecialchars($product['nazvanie']); ?></h3>
                
                <div class="mb-3">
                    <span class="badge bg-primary me-2">Код: <?php echo $product['id']; ?></span>
                    <span class="badge bg-info me-2">Вага: <?php echo $product['ves']; ?> кг</span>
                    <span class="badge bg-warning">Термін: <?php echo $product['srok']; ?> год</span>
                </div>
                
                <div class="product-price mb-4">
                    <h4><?php echo number_format($product['zena'], 2); ?> грн</h4>
                    <small class="text-muted">Собівартість: <?php echo number_format($product['stoimost'], 2); ?> грн</small>
                </div>
                
                <div class="mb-4">
                    <p>Опис продукту та характеристики:</p>
                    <ul>
                        <li>Вага одиниці продукції: <?php echo $product['ves']; ?> кг</li>
                        <li>Термін зберігання: <?php echo $product['srok']; ?> годин</li>
                        <li>Пакування: стандартне</li>
                        <li>Склад: борошно вищого ґатунку, вода, сіль, дріжджі</li>
                    </ul>
                </div>
                
                <div class="mb-4">
                    <form id="add-to-cart-form" class="row g-3 align-items-center">
                        <div class="col-auto">
                            <label for="quantity" class="col-form-label">Кількість:</label>
                        </div>
                        <div class="col-md-3">
                            <div class="input-group">
                                <button type="button" class="btn btn-outline-secondary decrease-qty">-</button>
                                <input type="number" class="form-control text-center" id="quantity" value="1" min="1">
                                <button type="button" class="btn btn-outline-secondary increase-qty">+</button>
                            </div>
                        </div>
                        <div class="col-auto">
                            <button type="button" id="add-to-cart-btn" class="btn btn-warning" data-id="<?php echo $product['id']; ?>" data-name="<?php echo htmlspecialchars($product['nazvanie']); ?>" data-price="<?php echo $product['zena']; ?>">
                                <i class="fas fa-cart-plus me-1"></i> Додати до кошика
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> Мінімальне замовлення: 200 грн. Доставка здійснюється власним транспортом підприємства.
                </div>
                
                <div class="d-flex mt-4">
                    <a href="products.php" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left me-1"></i> Назад до каталогу
                    </a>
                    <a href="cart.php" class="btn btn-primary">
                        <i class="fas fa-shopping-cart me-1"></i> Перейти до кошика
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Пов'язані продукти -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-bread-slice me-2"></i> Схожі продукти
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <?php
            // Отримання інших продуктів
            $similarProductsQuery = "SELECT * FROM product WHERE id != ? ORDER BY RAND() LIMIT 4";
            $stmt = mysqli_prepare($connection, $similarProductsQuery);
            mysqli_stmt_bind_param($stmt, "i", $productId);
            mysqli_stmt_execute($stmt);
            $similarProductsResult = mysqli_stmt_get_result($stmt);
            
            while ($similarProduct = mysqli_fetch_assoc($similarProductsResult)):
                $imagePath = !empty($similarProduct['image']) ? '../../' . $similarProduct['image'] : '../../assets/img/product-placeholder.jpg';
            ?>
                <div class="col-md-3 mb-3">
                    <div class="card h-100 product-card">
                        <img src="<?php echo $imagePath; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($similarProduct['nazvanie']); ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($similarProduct['nazvanie']); ?></h5>
                            <p class="card-text">
                                <span class="badge bg-light text-dark me-2">Вага: <?php echo $similarProduct['ves']; ?> кг</span>
                                <span class="badge bg-light text-dark">Термін: <?php echo $similarProduct['srok']; ?> год</span>
                            </p>
                            <p class="card-text">
                                <span class="price"><?php echo number_format($similarProduct['zena'], 2); ?> грн</span>
                            </p>
                            <div class="d-flex justify-content-between">
                                <a href="product_details.php?id=<?php echo $similarProduct['id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> Деталі
                                </a>
                                <button class="btn btn-sm btn-warning add-similar-to-cart" 
                                        data-id="<?php echo $similarProduct['id']; ?>" 
                                        data-name="<?php echo htmlspecialchars($similarProduct['nazvanie']); ?>" 
                                        data-price="<?php echo $similarProduct['zena']; ?>">
                                    <i class="fas fa-cart-plus"></i> Додати
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<!-- Модальне вікно успішного додавання товару -->
<div class="modal fade" id="addToCartModal" tabindex="-1" aria-labelledby="addToCartModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="addToCartModalLabel">
                    <i class="fas fa-check-circle me-2"></i> Товар додано до кошика
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Товар <strong id="addedProductName"></strong> успішно додано до кошика в кількості: <strong id="addedQuantity">1</strong> шт.</p>
                <p>Кількість товарів у кошику: <strong id="cartItemCount">0</strong></p>
                <p>Загальна сума: <strong id="cartTotal">0.00</strong> грн</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-shopping-bag me-1"></i> Продовжити покупки
                </button>
                <a href="cart.php" class="btn btn-primary">
                    <i class="fas fa-shopping-cart me-1"></i> Перейти до кошика
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Функція для оновлення лічильника товарів у кошику
        function updateCartCount() {
            let cart = JSON.parse(localStorage.getItem('cart')) || [];
            return cart.length;
        }

        // Зменшення кількості товару
        document.querySelector('.decrease-qty').addEventListener('click', function() {
            const quantityInput = document.getElementById('quantity');
            const currentValue = parseInt(quantityInput.value);
            if (currentValue > 1) {
                quantityInput.value = currentValue - 1;
            }
        });

        // Збільшення кількості товару
        document.querySelector('.increase-qty').addEventListener('click', function() {
            const quantityInput = document.getElementById('quantity');
            const currentValue = parseInt(quantityInput.value);
            quantityInput.value = currentValue + 1;
        });

        // Додавання товару до кошика
        document.getElementById('add-to-cart-btn').addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            const productName = this.getAttribute('data-name');
            const productPrice = parseFloat(this.getAttribute('data-price'));
            const quantity = parseInt(document.getElementById('quantity').value);
            
            if (quantity < 1) {
                alert('Кількість не може бути менше 1');
                return;
            }
            
            // Отримання поточного кошика
            let cart = JSON.parse(localStorage.getItem('cart')) || [];
            
            // Перевірка, чи товар вже є в кошику
            const existingIndex = cart.findIndex(item => item.id === productId);
            
            if (existingIndex !== -1) {
                // Оновлення кількості
                cart[existingIndex].quantity += quantity;
            } else {
                // Додавання нового товару
                cart.push({
                    id: productId,
                    name: productName,
                    price: productPrice,
                    quantity: quantity
                });
            }
            
            // Збереження оновленого кошика
            localStorage.setItem('cart', JSON.stringify(cart));
            
            // Розрахунок загальної суми
            const totalAmount = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            
            // Показ модального вікна
            document.getElementById('addedProductName').textContent = productName;
            document.getElementById('addedQuantity').textContent = quantity;
            document.getElementById('cartItemCount').textContent = cart.length;
            document.getElementById('cartTotal').textContent = totalAmount.toFixed(2);
            
            const modal = new bootstrap.Modal(document.getElementById('addToCartModal'));
            modal.show();
        });

        // Додавання схожих товарів до кошика
        document.querySelectorAll('.add-similar-to-cart').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-id');
                const productName = this.getAttribute('data-name');
                const productPrice = parseFloat(this.getAttribute('data-price'));
                
                // Отримання поточного кошика
                let cart = JSON.parse(localStorage.getItem('cart')) || [];
                
                // Перевірка, чи товар вже є в кошику
                const existingIndex = cart.findIndex(item => item.id === productId);
                
                if (existingIndex !== -1) {
                    // Оновлення кількості
                    cart[existingIndex].quantity += 1;
                } else {
                    // Додавання нового товару
                    cart.push({
                        id: productId,
                        name: productName,
                        price: productPrice,
                        quantity: 1
                    });
                }
                
                // Збереження оновленого кошика
                localStorage.setItem('cart', JSON.stringify(cart));
                
                // Розрахунок загальної суми
                const totalAmount = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                
                // Показ модального вікна
                document.getElementById('addedProductName').textContent = productName;
                document.getElementById('addedQuantity').textContent = 1;
                document.getElementById('cartItemCount').textContent = cart.length;
                document.getElementById('cartTotal').textContent = totalAmount.toFixed(2);
                
                const modal = new bootstrap.Modal(document.getElementById('addToCartModal'));
                modal.show();
            });
        });
    });
</script>

<?php include_once '../../includes/footer.php'; ?>