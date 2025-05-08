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
$dayOrdersQuery = "SELECT COUNT(*) as total FROM zayavki WHERE doba='денна' AND DATE(data) = CURDATE()";
$dayOrdersResult = mysqli_query($connection, $dayOrdersQuery);
$dayOrdersCount = mysqli_fetch_assoc($dayOrdersResult)['total'];

// Кількість замовлень на нічну зміну
$nightOrdersQuery = "SELECT COUNT(*) as total FROM zayavki WHERE doba='нічна' AND DATE(data) = CURDATE()";
$nightOrdersResult = mysqli_query($connection, $nightOrdersQuery);
$nightOrdersCount = mysqli_fetch_assoc($nightOrdersResult)['total'];

// Загальна кількість продуктів
$productsQuery = "SELECT COUNT(*) as total FROM product";
$productsResult = mysqli_query($connection, $productsQuery);
$productsCount = mysqli_fetch_assoc($productsResult)['total'];

// Останні замовлення на денну зміну
$recentDayOrdersQuery = "SELECT z.idd, p.nazvanie, SUM(z.kol) as total_quantity, z.data
                         FROM zayavki z
                         JOIN product p ON z.id = p.id
                         WHERE z.doba='денна' 
                         GROUP BY z.id
                         ORDER BY z.data DESC, z.idd DESC
                         LIMIT 5";
$recentDayOrdersResult = mysqli_query($connection, $recentDayOrdersQuery);

// Останні замовлення на нічну зміну
$recentNightOrdersQuery = "SELECT z.idd, p.nazvanie, SUM(z.kol) as total_quantity, z.data
                         FROM zayavki z
                         JOIN product p ON z.id = p.id
                         WHERE z.doba='нічна' 
                         GROUP BY z.id
                         ORDER BY z.data DESC, z.idd DESC
                         LIMIT 5";
$recentNightOrdersResult = mysqli_query($connection, $recentNightOrdersQuery);

include_once '../../includes/header.php';
?>

<div class="container-fluid">
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
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-calendar-day"></i> Зміни
                </a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                    <a class="dropdown-item" href="shifts_day.php">Денна зміна</a>
                    <a class="dropdown-item" href="shifts_night.php">Нічна зміна</a>
                </div>
                <a class="nav-link" href="scanner.php">
                    <i class="fas fa-barcode"></i> Сканер
                </a>
                <a class="nav-link" href="videos.php">
                    <i class="fas fa-video"></i> Відео
                </a>
                <a class="nav-link" href="products.php">
                    <i class="fas fa-bread-slice"></i> Продукція
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
                    <h5 class="card-title">Денна зміна</h5>
                    <p class="card-text display-4"><?php echo $dayOrdersCount; ?></p>
                    <p class="card-text">Активних замовлень</p>
                    <a href="shifts_day.php" class="btn btn-warning">Переглянути</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="fas fa-moon fa-3x mb-3 text-primary"></i>
                    <h5 class="card-title">Нічна зміна</h5>
                    <p class="card-text display-4"><?php echo $nightOrdersCount; ?></p>
                    <p class="card-text">Активних замовлень</p>
                    <a href="shifts_night.php" class="btn btn-primary">Переглянути</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="fas fa-bread-slice fa-3x mb-3 text-success"></i>
                    <h5 class="card-title">Продукція</h5>
                    <p class="card-text display-4"><?php echo $productsCount; ?></p>
                    <p class="card-text">Видів продукції</p>
                    <a href="products.php" class="btn btn-success">Переглянути</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Швидкі дії -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-bolt"></i> Швидкі дії
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="scanner.php" class="btn btn-lg btn-outline-primary w-100 h-100 d-flex flex-column justify-content-center align-items-center p-4">
                                <i class="fas fa-barcode fa-2x mb-2"></i>
                                <span>Відкрити сканер</span>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="shifts_day.php" class="btn btn-lg btn-outline-warning w-100 h-100 d-flex flex-column justify-content-center align-items-center p-4">
                                <i class="fas fa-sun fa-2x mb-2"></i>
                                <span>Денні замовлення</span>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="shifts_night.php" class="btn btn-lg btn-outline-primary w-100 h-100 d-flex flex-column justify-content-center align-items-center p-4">
                                <i class="fas fa-moon fa-2x mb-2"></i>
                                <span>Нічні замовлення</span>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="reports.php" class="btn btn-lg btn-outline-success w-100 h-100 d-flex flex-column justify-content-center align-items-center p-4">
                                <i class="fas fa-file-pdf fa-2x mb-2"></i>
                                <span>Сформувати звіт</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Таблиці замовлень -->
    <div class="row">
        <!-- Денна зміна -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-sun me-2"></i> Денна зміна - останні замовлення
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Продукт</th>
                                    <th>Кількість</th>
                                    <th>Дата</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($recentDayOrdersResult) > 0): ?>
                                    <?php while ($order = mysqli_fetch_assoc($recentDayOrdersResult)): ?>
                                        <tr>
                                            <td><?php echo $order['idd']; ?></td>
                                            <td><?php echo htmlspecialchars($order['nazvanie']); ?></td>
                                            <td><?php echo $order['total_quantity']; ?></td>
                                            <td><?php echo formatDate($order['data']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">Немає замовлень на денну зміну</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <a href="shifts_day.php" class="btn btn-warning">
                            <i class="fas fa-eye me-1"></i> Переглянути всі замовлення
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Нічна зміна -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-moon me-2"></i> Нічна зміна - останні замовлення
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Продукт</th>
                                    <th>Кількість</th>
                                    <th>Дата</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($recentNightOrdersResult) > 0): ?>
                                    <?php while ($order = mysqli_fetch_assoc($recentNightOrdersResult)): ?>
                                        <tr>
                                            <td><?php echo $order['idd']; ?></td>
                                            <td><?php echo htmlspecialchars($order['nazvanie']); ?></td>
                                            <td><?php echo $order['total_quantity']; ?></td>
                                            <td><?php echo formatDate($order['data']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">Немає замовлень на нічну зміну</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <a href="shifts_night.php" class="btn btn-primary">
                            <i class="fas fa-eye me-1"></i> Переглянути всі замовлення
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?>