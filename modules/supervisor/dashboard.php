<?php
$pageTitle = 'Панель управління бригадира';

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Перевірка доступу
if (!checkAccess(['brigadir'])) {
    header("Location: ../../index.php");
    exit;
}

// Отримання статистики
$connection = connectDatabase();

// Кількість замовлень на денну зміну
$dayOrdersQuery = "SELECT COUNT(*) as total FROM zakazu WHERE doba = 'денна'";
$dayOrdersResult = mysqli_query($connection, $dayOrdersQuery);
$dayOrdersCount = mysqli_fetch_assoc($dayOrdersResult)['total'];

// Кількість замовлень на нічну зміну
$nightOrdersQuery = "SELECT COUNT(*) as total FROM zakazu WHERE doba = 'нічна'";
$nightOrdersResult = mysqli_query($connection, $nightOrdersQuery);
$nightOrdersCount = mysqli_fetch_assoc($nightOrdersResult)['total'];

// Кількість продуктів
$productsQuery = "SELECT COUNT(*) as total FROM product";
$productsResult = mysqli_query($connection, $productsQuery);
$productsCount = mysqli_fetch_assoc($productsResult)['total'];

// Отримання останніх замовлень для денної зміни
$recentDayOrdersQuery = "SELECT z.idz, z.data, z.doba, COUNT(DISTINCT zv.id) as product_count, SUM(zv.kol) as total_quantity
                         FROM zakazu z
                         JOIN zayavki zv ON z.idz = zv.idd
                         WHERE z.doba = 'денна'
                         GROUP BY z.idz
                         ORDER BY z.data DESC
                         LIMIT 5";
$recentDayOrdersResult = mysqli_query($connection, $recentDayOrdersQuery);

// Отримання останніх замовлень для нічної зміни
$recentNightOrdersQuery = "SELECT z.idz, z.data, z.doba, COUNT(DISTINCT zv.id) as product_count, SUM(zv.kol) as total_quantity
                          FROM zakazu z
                          JOIN zayavki zv ON z.idz = zv.idd
                          WHERE z.doba = 'нічна'
                          GROUP BY z.idz
                          ORDER BY z.data DESC
                          LIMIT 5";
$recentNightOrdersResult = mysqli_query($connection, $recentNightOrdersQuery);

include_once '../../includes/header.php';
?>

<!-- Головне меню -->
<div class="row mb-4">
    <div class="col-md-12">
        <nav class="nav main-menu nav-pills nav-fill">
            <a class="nav-link active" href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Головна
            </a>
            <a class="nav-link" href="shifts.php">
                <i class="fas fa-clock"></i> Зміни
            </a>
            <a class="nav-link" href="scanner.php">
                <i class="fas fa-barcode"></i> Сканер
            </a>
            <a class="nav-link" href="products.php">
                <i class="fas fa-bread-slice"></i> Продукція
            </a>
            <a class="nav-link" href="video.php">
                <i class="fas fa-video"></i> Відео
            </a>
        </nav>
    </div>
</div>

<!-- Інформаційні картки -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-center h-100">
            <div class="card-body">
                <i class="fas fa-sun fa-3x mb-3 text-warning"></i>
                <h5 class="card-title">Замовлення на денну зміну</h5>
                <p class="card-text display-4"><?php echo $dayOrdersCount; ?></p>
                <a href="day_shift.php" class="btn btn-warning">
                    <i class="fas fa-clipboard-list me-1"></i> Переглянути замовлення
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card text-center h-100">
            <div class="card-body">
                <i class="fas fa-moon fa-3x mb-3 text-primary"></i>
                <h5 class="card-title">Замовлення на нічну зміну</h5>
                <p class="card-text display-4"><?php echo $nightOrdersCount; ?></p>
                <a href="night_shift.php" class="btn btn-primary">
                    <i class="fas fa-clipboard-list me-1"></i> Переглянути замовлення
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card text-center h-100">
            <div class="card-body">
                <i class="fas fa-bread-slice fa-3x mb-3 text-success"></i>
                <h5 class="card-title">Продукція</h5>
                <p class="card-text display-4"><?php echo $productsCount; ?></p>
                <a href="products.php" class="btn btn-success">
                    <i class="fas fa-list me-1"></i> Переглянути продукцію
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">
                    <i class="fas fa-sun me-2"></i> Останні замовлення на денну зміну
                </h5>
            </div>
            <div class="card-body">
                <?php if (mysqli_num_rows($recentDayOrdersResult) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Дата</th>
                                    <th>Кількість продуктів</th>
                                    <th>Загальна кількість</th>
                                    <th>Дії</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = mysqli_fetch_assoc($recentDayOrdersResult)): ?>
                                <tr>
                                    <td><?php echo $order['idz']; ?></td>
                                    <td><?php echo formatDate($order['data']); ?></td>
                                    <td><?php echo $order['product_count']; ?></td>
                                    <td><?php echo $order['total_quantity']; ?></td>
                                    <td>
                                        <a href="order_details.php?id=<?php echo $order['idz']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <a href="day_shift.php" class="btn btn-warning">Всі замовлення денної зміни</a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i> Немає замовлень на денну зміну
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-moon me-2"></i> Останні замовлення на нічну зміну
                </h5>
            </div>
            <div class="card-body">
                <?php if (mysqli_num_rows($recentNightOrdersResult) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Дата</th>
                                    <th>Кількість продуктів</th>
                                    <th>Загальна кількість</th>
                                    <th>Дії</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = mysqli_fetch_assoc($recentNightOrdersResult)): ?>
                                <tr>
                                    <td><?php echo $order['idz']; ?></td>
                                    <td><?php echo formatDate($order['data']); ?></td>
                                    <td><?php echo $order['product_count'];