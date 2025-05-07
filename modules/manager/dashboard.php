<?php
$pageTitle = 'Панель управління менеджера';

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Перевірка доступу
if (!checkAccess(['manager'])) {
    header("Location: ../../index.php");
    exit;
}

// Отримання статистики
$connection = connectDatabase();

// Загальна кількість клієнтів
$clientsQuery = "SELECT COUNT(*) as total FROM klientu";
$clientsResult = mysqli_query($connection, $clientsQuery);
$clientsCount = mysqli_fetch_assoc($clientsResult)['total'];

// Загальна кількість продуктів
$productsQuery = "SELECT COUNT(*) as total FROM product";
$productsResult = mysqli_query($connection, $productsQuery);
$productsCount = mysqli_fetch_assoc($productsResult)['total'];

// Кількість нових замовлень
$newOrdersQuery = "SELECT COUNT(*) as total FROM newzayavki";
$newOrdersResult = mysqli_query($connection, $newOrdersQuery);
$newOrdersCount = mysqli_fetch_assoc($newOrdersResult)['total'];

// Останні замовлення
$recentOrdersQuery = "SELECT z.idd, z.idklient, z.id, z.kol, z.data, z.doba, k.name as client_name, p.nazvanie as product_name
                      FROM zayavki z
                      JOIN klientu k ON z.idklient = k.id
                      JOIN product p ON z.id = p.id
                      ORDER BY z.data DESC LIMIT 5";
$recentOrdersResult = mysqli_query($connection, $recentOrdersQuery);

// Інформація про продукти з найбільшою популярністю
$popularProductsQuery = "SELECT p.id, p.nazvanie, COUNT(z.id) as order_count
                         FROM product p
                         JOIN zayavki z ON p.id = z.id
                         GROUP BY p.id
                         ORDER BY order_count DESC
                         LIMIT 5";
$popularProductsResult = mysqli_query($connection, $popularProductsQuery);

// Закриття з'єднання з базою даних
//mysqli_close($connection);

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
                <i class="fas fa-clipboard-list"></i> Замовлення
            </a>
            <a class="nav-link" href="clients.php">
                <i class="fas fa-users"></i> Клієнти
            </a>
            <a class="nav-link" href="products.php">
                <i class="fas fa-bread-slice"></i> Продукція
            </a>
            <a class="nav-link" href="reports.php">
                <i class="fas fa-chart-bar"></i> Звіти
            </a>
        </nav>
    </div>
</div>

<!-- Інформаційні картки -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-center h-100">
            <div class="card-body">
                <i class="fas fa-users fa-3x mb-3 text-primary"></i>
                <h5 class="card-title">Клієнти</h5>
                <p class="card-text display-4"><?php echo $clientsCount; ?></p>
                <a href="clients.php" class="btn btn-primary">Управління клієнтами</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card text-center h-100">
            <div class="card-body">
                <i class="fas fa-bread-slice fa-3x mb-3 text-warning"></i>
                <h5 class="card-title">Продукція</h5>
                <p class="card-text display-4"><?php echo $productsCount; ?></p>
                <a href="products.php" class="btn btn-warning">Управління продукцією</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card text-center h-100">
            <div class="card-body">
                <i class="fas fa-clipboard-list fa-3x mb-3 text-success"></i>
                <h5 class="card-title">Нові замовлення</h5>
                <p class="card-text display-4"><?php echo $newOrdersCount; ?></p>
                <a href="new_orders.php" class="btn btn-success">Переглянути нові замовлення</a>
            </div>
        </div>
    </div>
</div>

<!-- Останні замовлення -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-history"></i> Останні замовлення
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Клієнт</th>
                                <th>Продукт</th>
                                <th>Кількість</th>
                                <th>Дата</th>
                                <th>Зміна</th>
                                <th>Дії</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = mysqli_fetch_assoc($recentOrdersResult)): ?>
                            <tr>
                                <td><?php echo $order['idd']; ?></td>
                                <td><?php echo htmlspecialchars($order['client_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                <td><?php echo $order['kol']; ?></td>
                                <td><?php echo formatDate($order['data']); ?></td>
                                <td>
                                    <?php if ($order['doba'] == 'денна'): ?>
                                    <span class="badge shift-badge shift-day">Денна</span>
                                    <?php else: ?>
                                    <span class="badge shift-badge shift-night">Нічна</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="order_details.php?id=<?php echo $order['idd']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-3">
                    <a href="orders.php" class="btn btn-primary">Переглянути всі замовлення</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Популярні продукти -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-star"></i> Популярні продукти
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php while ($product = mysqli_fetch_assoc($popularProductsResult)): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <?php 
                            $productInfo = getProductById($connection, $product['id']);
                            $imagePath = !empty($productInfo['image']) ? '../../' . $productInfo['image'] : '../../assets/img/product-placeholder.jpg';
                            ?>
                            <img src="<?php echo $imagePath; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['nazvanie']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['nazvanie']); ?></h5>
                                <p class="card-text">
                                    <span class="badge bg-success">
                                        <i class="fas fa-shopping-cart"></i> <?php echo $product['order_count']; ?> замовлень
                                    </span>
                                </p>
                                <a href="product_details.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary">Детальніше</a>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include_once '../../includes/footer.php';
?>