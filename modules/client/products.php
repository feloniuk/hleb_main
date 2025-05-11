<?php
$pageTitle = 'Каталог продукції';

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Перевірка доступу
if (!checkAccess(['client'])) {
    header("Location: ../../index.php");
    exit;
}

$connection = connectDatabase();

// Параметри пошуку та пагінації
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 12; // Кількість продуктів на сторінці
$offset = ($page - 1) * $limit;

// Формування запиту
$query = "SELECT * FROM product WHERE 1=1";
$countQuery = "SELECT COUNT(*) as total FROM product WHERE 1=1";

$params = [];
$types = '';

if (!empty($search)) {
    $query .= " AND nazvanie LIKE ?";
    $countQuery .= " AND nazvanie LIKE ?";
    $searchParam = '%' . $search . '%';
    $params[] = $searchParam;
    $types .= 's';
}

$query .= " ORDER BY nazvanie LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

// Виконання запиту для підрахунку загальної кількості
$countStmt = mysqli_prepare($connection, $countQuery);
if (!empty($search)) {
    mysqli_stmt_bind_param($countStmt, "s", $searchParam);
}
mysqli_stmt_execute($countStmt);
$countResult = mysqli_stmt_get_result($countStmt);
$totalCount = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalCount / $limit);

// Виконання основного запиту
$stmt = mysqli_prepare($connection, $query);
if (!empty($params)) {
    array_unshift($params, $types);
    call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt], $params));
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

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

<div class="card mb-4">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-bread-slice me-2"></i> Каталог продукції
            </h5>
            <a href="cart.php" class="btn btn-warning position-relative">
                <i class="fas fa-shopping-cart me-1"></i> 
                Кошик
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger cart-count">
                    0
                    <span class="visually-hidden">товарів у кошику</span>
                </span>
            </a>
        </div>
    </div>
    <div class="card-body">
        <!-- Форма пошуку -->
        <form action="" method="GET" class="mb-4">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Пошук продукції..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search me-1"></i> Шукати
                </button>
                <?php if (!empty($search)): ?>
                    <a href="products.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i> Скинути
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Результати пошуку -->
        <?php if (!empty($search)): ?>
        <div class="alert alert-info mb-4">
            <i class="fas fa-search me-1"></i> Результати пошуку для: <strong><?php echo htmlspecialchars($search); ?></strong>
            (знайдено: <?php echo $totalCount; ?>)
        </div>
        <?php endif; ?>

        <!-- Каталог продукції -->
        <div class="row">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($product = mysqli_fetch_assoc($result)): ?>
                    <div class="col-md-3 mb-4">
                        <div class="card h-100 product-card">
                            <?php 
                            $imagePath = !empty($product['image']) ? '../../' . $product['image'] : '../../assets/img/product-placeholder.jpg';
                            ?>
                            <img src="<?php echo $imagePath; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['nazvanie']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['nazvanie']); ?></h5>
                                <p class="card-text">
                                    <span class="badge bg-light text-dark me-2">Вага: <?php echo $product['ves']; ?> кг</span>
                                    <span class="badge bg-light text-dark">Термін: <?php echo $product['srok']; ?> год</span>
                                </p>
                                <p class="card-text">
                                    <span class="price"><?php echo number_format($product['zena'], 2); ?> грн</span>
                                </p>
                                <div class="d-flex justify-content-between">
                                    <a href="product_details.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> Деталі
                                    </a>
                                    <button class="btn btn-sm btn-warning add-to-cart" 
                                            data-id="<?php echo $product['id']; ?>" 
                                            data-name="<?php echo htmlspecialchars($product['nazvanie']); ?>" 
                                            data-price="<?php echo $product['zena']; ?>">
                                        <i class="fas fa-cart-plus"></i> Додати
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo empty($search) ? 'Продукти не знайдено' : 'За вашим запитом продукти не знайдено'; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Пагінація -->
        <?php if ($totalPages > 1): ?>
            <nav aria-label="Навігація по сторінках" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" aria-label="Попередня">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    
                    <?php
                    // Обмежуємо кількість відображуваних сторінок
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    if ($startPage > 1) {
                        echo '<li class="page-item"><a class="page-link" href="?page=1&search=' . urlencode($search) . '">1</a></li>';
                        if ($startPage > 2) {
                            echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                        }
                    }
                    
                    for ($i = $startPage; $i <= $endPage; $i++) {
                        echo '<li class="page-item ' . ($page == $i ? 'active' : '') . '">';
                        echo '<a class="page-link" href="?page=' . $i . '&search=' . urlencode($search) . '">' . $i . '</a>';
                        echo '</li>';
                    }
                    
                    if ($endPage < $totalPages) {
                        if ($endPage < $totalPages - 1) {
                            echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                        }
                        echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . '&search=' . urlencode($search) . '">' . $totalPages . '</a></li>';
                    }
                    ?>
                    
                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" aria-label="Наступна">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
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
                <p>Товар <strong id="addedProductName"></strong> успішно додано до кошика.</p>
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
            document.querySelector('.cart-count').textContent = cart.length;
        }

        // Ініціалізація лічильника при завантаженні сторінки
        updateCartCount();

        // Додавання товару до кошика
        document.querySelectorAll('.add-to-cart').forEach(function(button) {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-id');
                const productName = this.getAttribute('data-name');
                const productPrice = parseFloat(this.getAttribute('data-price'));
                
                // Отримання поточного кошика з localStorage
                let cart = JSON.parse(localStorage.getItem('cart')) || [];
                
                // Перевірка, чи товар вже у кошику
                const existingItemIndex = cart.findIndex(item => item.id === productId);
                
                if (existingItemIndex !== -1) {
                    // Якщо товар вже є, збільшуємо кількість
                    cart[existingItemIndex].quantity += 1;
                } else {
                    // Якщо товару немає, додаємо його
                    cart.push({
                        id: productId,
                        name: productName,
                        price: productPrice,
                        quantity: 1
                    });
                }
                
                // Зберігаємо оновлений кошик
                localStorage.setItem('cart', JSON.stringify(cart));
                
                // Оновлюємо лічильник кошика
                updateCartCount();
                
                // Розрахунок загальної суми
                const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                
                // Показуємо модальне вікно з інформацією про додавання
                document.getElementById('addedProductName').textContent = productName;
                document.getElementById('cartItemCount').textContent = cart.length;
                document.getElementById('cartTotal').textContent = total.toFixed(2);
                
                const modal = new bootstrap.Modal(document.getElementById('addToCartModal'));
                modal.show();
            });
        });
    });
</script>

<?php include_once '../../includes/footer.php'; ?>