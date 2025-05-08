<?php
$pageTitle = 'Особистий кабінет клієнта';

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Перевірка доступу
if (!checkAccess(['client'])) {
    header("Location: ../../index.php");
    exit;
}

// Отримання даних клієнта
$connection = connectDatabase();
$clientId = $_SESSION['id'];

$clientQuery = "SELECT * FROM klientu WHERE id = ?";
$stmt = mysqli_prepare($connection, $clientQuery);
mysqli_stmt_bind_param($stmt, "i", $clientId);
mysqli_stmt_execute($stmt);
$clientResult = mysqli_stmt_get_result($stmt);
$client = mysqli_fetch_assoc($clientResult);

// Отримання останніх замовлень клієнта
$ordersQuery = "SELECT z.idd, z.id, p.nazvanie as product_name, z.kol, z.data, z.doba, z.status, (z.kol * p.zena) as total_price
                FROM zayavki z
                JOIN product p ON z.id = p.id
                WHERE z.idklient = ?
                ORDER BY z.data DESC, z.idd DESC
                LIMIT 5";
$stmt = mysqli_prepare($connection, $ordersQuery);
mysqli_stmt_bind_param($stmt, "i", $clientId);
mysqli_stmt_execute($stmt);
$ordersResult = mysqli_stmt_get_result($stmt);

// Отримання статистики замовлень
$statsQuery = "SELECT 
                COUNT(z.idd) as total_orders,
                SUM(z.kol) as total_quantity,
                SUM(z.kol * p.zena) as total_amount,
                COUNT(DISTINCT z.id) as unique_products
              FROM zayavki z
              JOIN product p ON z.id = p.id
              WHERE z.idklient = ?";
$stmt = mysqli_prepare($connection, $statsQuery);
mysqli_stmt_bind_param($stmt, "i", $clientId);
mysqli_stmt_execute($stmt);
$statsResult = mysqli_stmt_get_result($stmt);
$stats = mysqli_fetch_assoc($statsResult);

// Отримання популярних продуктів клієнта
$popularProductsQuery = "SELECT p.id, p.nazvanie, p.zena, p.image, SUM(z.kol) as total_ordered
                        FROM zayavki z
                        JOIN product p ON z.id = p.id
                        WHERE z.idklient = ?
                        GROUP BY p.id
                        ORDER BY total_ordered DESC
                        LIMIT 3";
$stmt = mysqli_prepare($connection, $popularProductsQuery);
mysqli_stmt_bind_param($stmt, "i", $clientId);
mysqli_stmt_execute($stmt);
$popularProductsResult = mysqli_stmt_get_result($stmt);

// Отримання останніх новин/акцій
$newsQuery = "SELECT * FROM imgs ORDER BY id DESC LIMIT 3";
$newsResult = mysqli_query($connection, $newsQuery);

include_once '../../includes/header.php';
?>

<!-- Головне меню -->
<div class="row mb-4">
    <div class="col-md-12">
        <nav class="nav main-menu nav-pills nav-fill">
            <a class="nav-link active" href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Головна
            </a>
            <a class="nav-link" href="orders.php">
                <i class="fas fa-clipboard-list"></i> Мої замовлення
            </a>
            <a class="nav-link" href="products.php">
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

<!-- Вітання та інформація про клієнта -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <h4>Вітаємо, <?php echo htmlspecialchars($client['fio']); ?>!</h4>
                <p class="text-muted"><?php echo htmlspecialchars($client['name']); ?></p>
                <p><i class="fas fa-map-marker-alt me-2"></i> <?php echo htmlspecialchars($client['city']); ?>, <?php echo htmlspecialchars($client['adres']); ?></p>
                <p><i class="fas fa-phone me-2"></i> <?php echo htmlspecialchars($client['tel']); ?></p>
                <p><i class="fas fa-envelope me-2"></i> <?php echo htmlspecialchars($client['mail']); ?></p>
            </div>
            <div class="col-md-4 text-end">
                <a href="profile.php" class="btn btn-outline-primary mb-2">
                    <i class="fas fa-user-edit me-1"></i> Редагувати профіль
                </a>
                <a href="new_order.php" class="btn btn-warning">
                    <i class="fas fa-plus me-1"></i> Нове замовлення
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Статистика замовлень -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center h-100">
            <div class="card-body">
                <i class="fas fa-clipboard-list fa-3x mb-3 text-primary"></i>
                <h5 class="card-title">Всього замовлень</h5>
                <p class="card-text display-6"><?php echo $stats['total_orders']; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center h-100">
            <div class="card-body">
                <i class="fas fa-box fa-3x mb-3 text-success"></i>
                <h5 class="card-title">Загальна кількість</h5>
                <p class="card-text display-6"><?php echo $stats['total_quantity']; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center h-100">
            <div class="card-body">
                <i class="fas fa-hryvnia fa-3x mb-3 text-warning"></i>
                <h5 class="card-title">Сума замовлень</h5>
                <p class="card-text display-6"><?php echo number_format($stats['total_amount'], 2); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center h-100">
            <div class="card-body">
                <i class="fas fa-bread-slice fa-3x mb-3 text-danger"></i>
                <h5 class="card-title">Унікальних продуктів</h5>
                <p class="card-text display-6"><?php echo $stats['unique_products']; ?></p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Останні замовлення -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i> Останні замовлення
                    </h5>
                    <a href="orders.php" class="btn btn-sm btn-primary">
                        Переглянути всі
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Продукт</th>
                                <th>Кількість</th>
                                <th>Сума</th>
                                <th>Дата</th>
                                <th>Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($ordersResult) > 0): ?>
                                <?php while ($order = mysqli_fetch_assoc($ordersResult)): ?>
                                    <tr>
                                        <td><?php echo $order['idd']; ?></td>
                                        <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                        <td><?php echo $order['kol']; ?></td>
                                        <td><?php echo number_format($order['total_price'], 2); ?> грн</td>
                                        <td><?php echo formatDate($order['data']); ?></td>
                                        <td>
                                            <?php
                                            switch ($order['status']) {
                                                case 'нове':
                                                    echo '<span class="badge bg-info">Нове</span>';
                                                    break;
                                                case 'в обробці':
                                                    echo '<span class="badge bg-warning">В обробці</span>';
                                                    break;
                                                case 'виконано':
                                                    echo '<span class="badge bg-success">Виконано</span>';
                                                    break;
                                                case 'скасовано':
                                                    echo '<span class="badge bg-danger">Скасовано</span>';
                                                    break;
                                                default:
                                                    echo '<span class="badge bg-secondary">Невідомо</span>';
                                                    break;
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">У вас ще немає замовлень</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Популярні продукти -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-star me-2"></i> Ваші улюблені продукти
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php if (mysqli_num_rows($popularProductsResult) > 0): ?>
                        <?php while ($product = mysqli_fetch_assoc($popularProductsResult)): ?>
                            <div class="col-md-4">
                                <div class="card product-card h-100">
                                    <?php 
                                    $imagePath = !empty($product['image']) ? '../../' . $product['image'] : '../../assets/img/product-placeholder.jpg';
                                    ?>
                                    <img src="<?php echo $imagePath; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['nazvanie']); ?>">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($product['nazvanie']); ?></h5>
                                        <p class="card-text">
                                            <span class="price"><?php echo number_format($product['zena'], 2); ?> грн</span>
                                        </p>
                                        <p class="card-text">
                                            <span class="badge bg-success">
                                                <i class="fas fa-shopping-cart"></i> Замовлено <?php echo $product['total_ordered']; ?> шт.
                                            </span>
                                        </p>
                                        <div class="d-flex justify-content-between">
                                            <a href="product_details.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> Деталі
                                            </a>
                                            <a href="add_to_cart.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-cart-plus"></i> Додати
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <p class="text-center">У вас ще немає улюблених продуктів</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Акції та оголошення -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-bullhorn me-2"></i> Акції та оголошення
                </h5>
            </div>
            <div class="card-body">
                <?php if (mysqli_num_rows($newsResult) > 0): ?>
                    <?php while ($news = mysqli_fetch_assoc($newsResult)): ?>
                        <div class="card mb-3">
                            <?php if (!empty($news['content'])): ?>
                                <img src="../../<?php echo $news['content']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($news['zag']); ?>">
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($news['zag']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars($news['comment']); ?></p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-center">Наразі немає активних акцій</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Контактна інформація пекарні -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i> Контактна інформація
                </h5>
            </div>
            <div class="card-body">
                <p><i class="fas fa-building me-2"></i> ТОВ "Одеський Коровай"</p>
                <p><i class="fas fa-map-marker-alt me-2"></i> м. Одеса, вул. Пекарська, 10</p>
                <p><i class="fas fa-phone me-2"></i> +38 (048) 123-45-67</p>
                <p><i class="fas fa-envelope me-2"></i> info@odesskiy-korovay.com</p>
                <p><i class="fas fa-clock me-2"></i> Пн-Пт: 8:00 - 18:00, Сб-Нд: 9:00 - 15:00</p>
                
                <div class="d-grid gap-2 mt-3">
                    <a href="contact.php" class="btn btn-primary">
                        <i class="fas fa-envelope me-1"></i> Написати повідомлення
                    </a>
                    <a href="tel:+380481234567" class="btn btn-outline-primary">
                        <i class="fas fa-phone me-1"></i> Зателефонувати
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include_once '../../includes/footer.php';
?>