<?php
$pageTitle = 'Звіти';

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Перевірка доступу
if (!checkAccess(['manager'])) {
    header("Location: ../../index.php");
    exit;
}

$connection = connectDatabase();
$success = '';
$error = '';

// Обробка запиту на генерацію звіту
$reportType = isset($_GET['type']) ? $_GET['type'] : '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // Перший день поточного місяця
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'); // Поточний день

// Для звіту по клієнту
$clientId = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;

// Для звіту по продукту
$productId = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

// Отримання списку клієнтів для форми
$clientsQuery = "SELECT id, name FROM klientu ORDER BY name";
$clientsResult = mysqli_query($connection, $clientsQuery);

// Отримання списку продуктів для форми
$productsQuery = "SELECT id, nazvanie FROM product ORDER BY nazvanie";
$productsResult = mysqli_query($connection, $productsQuery);

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
                <i class="fas fa-clipboard-list"></i> Замовлення
            </a>
            <a class="nav-link" href="clients.php">
                <i class="fas fa-users"></i> Клієнти
            </a>
            <a class="nav-link" href="products.php">
                <i class="fas fa-bread-slice"></i> Продукція
            </a>
            <a class="nav-link active" href="reports.php">
                <i class="fas fa-chart-bar"></i> Звіти
            </a>
        </nav>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Картки з типами звітів -->
<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-chart-line fa-3x mb-3 text-primary"></i>
                <h5 class="card-title">Загальний звіт</h5>
                <p class="card-text">Загальна статистика по замовленнях, продажах і продукції</p>
                <a href="reports.php?type=general" class="btn btn-primary">Сформувати звіт</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-users fa-3x mb-3 text-success"></i>
                <h5 class="card-title">Звіт по клієнту</h5>
                <p class="card-text">Деталі замовлень по обраному клієнту</p>
                <a href="#" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#clientReportModal">Сформувати звіт</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-bread-slice fa-3x mb-3 text-warning"></i>
                <h5 class="card-title">Звіт по продукту</h5>
                <p class="card-text">Статистика продажів по обраному продукту</p>
                <a href="#" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#productReportModal">Сформувати звіт</a>
            </div>
        </div>
    </div>
</div>

<!-- Відображення відповідного звіту в залежності від обраного типу -->
<?php if ($reportType === 'general'): ?>
    <!-- Загальний звіт -->
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-chart-line me-2"></i> Загальний звіт
                </h5>
                <form action="" method="GET" class="d-flex">
                    <input type="hidden" name="type" value="general">
                    <input type="date" class="form-control me-2" name="start_date" value="<?php echo $startDate; ?>">
                    <input type="date" class="form-control me-2" name="end_date" value="<?php echo $endDate; ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sync-alt me-1"></i> Оновити
                    </button>
                </form>
            </div>
        </div>
        <div class="card-body">
            <?php
            // Запит на отримання загальної статистики
            $generalStatsQuery = "
                SELECT 
                    COUNT(DISTINCT z.idd) as total_orders,
                    SUM(z.kol) as total_quantity,
                    SUM(z.kol * p.zena) as total_sales,
                    COUNT(DISTINCT z.idklient) as total_clients,
                    COUNT(DISTINCT z.id) as total_products
                FROM zayavki z
                JOIN product p ON z.id = p.id
                WHERE z.data BETWEEN ? AND ?
            ";
            
            $stmt = mysqli_prepare($connection, $generalStatsQuery);
            mysqli_stmt_bind_param($stmt, "ss", $startDate, $endDate);
            mysqli_stmt_execute($stmt);
            $generalStatsResult = mysqli_stmt_get_result($stmt);
            $generalStats = mysqli_fetch_assoc($generalStatsResult);
            ?>
            
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h6 class="card-title">Загальна кількість замовлень</h6>
                            <p class="display-6"><?php echo $generalStats['total_orders']; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h6 class="card-title">Загальна кількість одиниць</h6>
                            <p class="display-6"><?php echo $generalStats['total_quantity']; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h6 class="card-title">Загальна сума продажів</h6>
                            <p class="display-6"><?php echo number_format($generalStats['total_sales'], 2); ?> грн</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h6 class="card-title">Кількість клієнтів</h6>
                            <p class="display-6"><?php echo $generalStats['total_clients']; ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Топ-5 продуктів -->
            <h5 class="mb-3">Топ-5 продуктів за кількістю замовлень</h5>
            <?php
            $topProductsQuery = "
                SELECT p.id, p.nazvanie, SUM(z.kol) as total_quantity, SUM(z.kol * p.zena) as total_sales
                FROM zayavki z
                JOIN product p ON z.id = p.id
                WHERE z.data BETWEEN ? AND ?
                GROUP BY p.id
                ORDER BY total_quantity DESC
                LIMIT 5
            ";
            
            $stmt = mysqli_prepare($connection, $topProductsQuery);
            mysqli_stmt_bind_param($stmt, "ss", $startDate, $endDate);
            mysqli_stmt_execute($stmt);
            $topProductsResult = mysqli_stmt_get_result($stmt);
            ?>
            
            <div class="table-responsive mb-4">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Назва продукту</th>
                            <th>Кількість</th>
                            <th>Сума продажів</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($product = mysqli_fetch_assoc($topProductsResult)): ?>
                            <tr>
                                <td><?php echo $product['id']; ?></td>
                                <td><?php echo htmlspecialchars($product['nazvanie']); ?></td>
                                <td><?php echo $product['total_quantity']; ?></td>
                                <td><?php echo number_format($product['total_sales'], 2); ?> грн</td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Топ-5 клієнтів -->
            <h5 class="mb-3">Топ-5 клієнтів за сумою замовлень</h5>
            <?php
            $topClientsQuery = "
                SELECT k.id, k.name, COUNT(DISTINCT z.idd) as order_count, SUM(z.kol) as total_quantity, SUM(z.kol * p.zena) as total_sales
                FROM zayavki z
                JOIN klientu k ON z.idklient = k.id
                JOIN product p ON z.id = p.id
                WHERE z.data BETWEEN ? AND ?
                GROUP BY k.id
                ORDER BY total_sales DESC
                LIMIT 5
            ";
            
            $stmt = mysqli_prepare($connection, $topClientsQuery);
            mysqli_stmt_bind_param($stmt, "ss", $startDate, $endDate);
            mysqli_stmt_execute($stmt);
            $topClientsResult = mysqli_stmt_get_result($stmt);
            ?>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Назва клієнта</th>
                            <th>Кількість замовлень</th>
                            <th>Загальна кількість</th>
                            <th>Сума продажів</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($client = mysqli_fetch_assoc($topClientsResult)): ?>
                            <tr>
                                <td><?php echo $client['id']; ?></td>
                                <td><?php echo htmlspecialchars($client['name']); ?></td>
                                <td><?php echo $client['order_count']; ?></td>
                                <td><?php echo $client['total_quantity']; ?></td>
                                <td><?php echo number_format($client['total_sales'], 2); ?> грн</td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="text-center mt-4">
                <a href="export_report.php?type=general&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="btn btn-success">
                    <i class="fas fa-file-excel me-1"></i> Експортувати в Excel
                </a>
                <a href="print_report.php?type=general&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="btn btn-primary ms-2" target="_blank">
                    <i class="fas fa-print me-1"></i> Друкувати
                </a>
            </div>
        </div>
    </div>

<?php elseif ($reportType === 'client' && $clientId > 0): ?>
    <!-- Звіт по клієнту -->
    <?php
    // Отримання інформації про клієнта
    $clientQuery = "SELECT * FROM klientu WHERE id = ?";
    $stmt = mysqli_prepare($connection, $clientQuery);
    mysqli_stmt_bind_param($stmt, "i", $clientId);
    mysqli_stmt_execute($stmt);
    $clientResult = mysqli_stmt_get_result($stmt);
    $client = mysqli_fetch_assoc($clientResult);
    
    if ($client):
    ?>
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-users me-2"></i> Звіт по клієнту: <?php echo htmlspecialchars($client['name']); ?>
                </h5>
                <form action="" method="GET" class="d-flex">
                    <input type="hidden" name="type" value="client">
                    <input type="hidden" name="client_id" value="<?php echo $clientId; ?>">
                    <input type="date" class="form-control me-2" name="start_date" value="<?php echo $startDate; ?>">
                    <input type="date" class="form-control me-2" name="end_date" value="<?php echo $endDate; ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sync-alt me-1"></i> Оновити
                    </button>
                </form>
            </div>
        </div>
        <div class="card-body">
            <!-- Інформація про клієнта -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Контактна інформація</h6>
                        </div>
                        <div class="card-body">
                            <p><strong>Компанія:</strong> <?php echo htmlspecialchars($client['name']); ?></p>
                            <p><strong>Контактна особа:</strong> <?php echo htmlspecialchars($client['fio']); ?></p>
                            <p><strong>Посада:</strong> <?php echo htmlspecialchars($client['dolj']); ?></p>
                            <p><strong>Телефон:</strong> <?php echo htmlspecialchars($client['tel']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($client['mail']); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Місцезнаходження та статистика</h6>
                        </div>
                        <div class="card-body">
                            <p><strong>Місто:</strong> <?php echo htmlspecialchars($client['city']); ?></p>
                            <p><strong>Адреса:</strong> <?php echo htmlspecialchars($client['adres']); ?></p>
                            <p><strong>Відстань:</strong> <?php echo $client['rast']; ?> км</p>
                            
                            <?php
                            // Отримання статистики по клієнту
                            $clientStatsQuery = "
                                SELECT 
                                    COUNT(DISTINCT z.idd) as total_orders,
                                    SUM(z.kol) as total_quantity,
                                    SUM(z.kol * p.zena) as total_sales
                                FROM zayavki z
                                JOIN product p ON z.id = p.id
                                WHERE z.idklient = ? AND z.data BETWEEN ? AND ?
                            ";
                            
                            $stmt = mysqli_prepare($connection, $clientStatsQuery);
                            mysqli_stmt_bind_param($stmt, "iss", $clientId, $startDate, $endDate);
                            mysqli_stmt_execute($stmt);
                            $clientStatsResult = mysqli_stmt_get_result($stmt);
                            $clientStats = mysqli_fetch_assoc($clientStatsResult);
                            ?>
                            
                            <p><strong>Кількість замовлень:</strong> <?php echo $clientStats['total_orders']; ?></p>
                            <p><strong>Загальна кількість продукції:</strong> <?php echo $clientStats['total_quantity']; ?></p>
                            <p><strong>Загальна сума:</strong> <?php echo number_format($clientStats['total_sales'], 2); ?> грн</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Список замовлень клієнта -->
            <h5 class="mb-3">Список замовлень за обраний період</h5>
            <?php
            $clientOrdersQuery = "
                SELECT z.idd, z.id, p.nazvanie, z.kol, z.data, z.doba, z.status, (z.kol * p.zena) as total_price
                FROM zayavki z
                JOIN product p ON z.id = p.id
                WHERE z.idklient = ? AND z.data BETWEEN ? AND ?
                ORDER BY z.data DESC, z.idd DESC
            ";
            
            $stmt = mysqli_prepare($connection, $clientOrdersQuery);
            mysqli_stmt_bind_param($stmt, "iss", $clientId, $startDate, $endDate);
            mysqli_stmt_execute($stmt);
            $clientOrdersResult = mysqli_stmt_get_result($stmt);
            ?>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Продукт</th>
                            <th>Кількість</th>
                            <th>Сума</th>
                            <th>Дата</th>
                            <th>Зміна</th>
                            <th>Статус</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($clientOrdersResult) > 0): ?>
                            <?php while ($order = mysqli_fetch_assoc($clientOrdersResult)): ?>
                                <tr>
                                    <td><?php echo $order['idd']; ?></td>
                                    <td><?php echo htmlspecialchars($order['nazvanie']); ?></td>
                                    <td><?php echo $order['kol']; ?></td>
                                    <td><?php echo number_format($order['total_price'], 2); ?> грн</td>
                                    <td><?php echo formatDate($order['data']); ?></td>
                                    <td>
                                        <?php if ($order['doba'] == 'денна'): ?>
                                        <span class="badge shift-badge shift-day">Денна</span>
                                        <?php else: ?>
                                        <span class="badge shift-badge shift-night">Нічна</span>
                                        <?php endif; ?>
                                    </td>
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
                                <td colspan="7" class="text-center">Замовлень не знайдено за обраний період</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="text-center mt-4">
                <a href="export_report.php?type=client&client_id=<?php echo $clientId; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="btn btn-success">
                    <i class="fas fa-file-excel me-1"></i> Експортувати в Excel
                </a>
                <a href="print_report.php?type=client&client_id=<?php echo $clientId; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="btn btn-primary ms-2" target="_blank">
                    <i class="fas fa-print me-1"></i> Друкувати
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

<?php elseif ($reportType === 'product' && $productId > 0): ?>
    <!-- Звіт по продукту -->
    <?php
    // Отримання інформації про продукт
    $productQuery = "SELECT * FROM product WHERE id = ?";
    $stmt = mysqli_prepare($connection, $productQuery);
    mysqli_stmt_bind_param($stmt, "i", $productId);
    mysqli_stmt_execute($stmt);
    $productResult = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($productResult);
    
    if ($product):
    ?>
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-bread-slice me-2"></i> Звіт по продукту: <?php echo htmlspecialchars($product['nazvanie']); ?>
                </h5>
                <form action="" method="GET" class="d-flex">
                    <input type="hidden" name="type" value="product">
                    <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
                    <input type="date" class="form-control me-2" name="start_date" value="<?php echo $startDate; ?>">
                    <input type="date" class="form-control me-2" name="end_date" value="<?php echo $endDate; ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sync-alt me-1"></i> Оновити
                    </button>
                </form>
            </div>
        </div>
        <div class="card-body">
            <!-- Інформація про продукт -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="text-center">
                        <?php 
                        $imagePath = !empty($product['image']) ? '../../' . $product['image'] : '../../assets/img/product-placeholder.jpg';
                        ?>
                        <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($product['nazvanie']); ?>" class="img-fluid rounded mb-3" style="max-height: 200px;">
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="card h-100">
                        <div class="card-header">
                            <h6 class="mb-0">Деталі продукту</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Назва:</strong> <?php echo htmlspecialchars($product['nazvanie']); ?></p>
                                    <p><strong>Вага:</strong> <?php echo $product['ves']; ?> кг</p>
                                    <p><strong>Строк реалізації:</strong> <?php echo $product['srok']; ?> годин</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Собівартість:</strong> <?php echo $product['stoimost']; ?> грн</p>
                                    <p><strong>Ціна:</strong> <?php echo $product['zena']; ?> грн</p>
                                    <p><strong>Прибуток:</strong> <?php echo number_format($product['zena'] - $product['stoimost'], 2); ?> грн</p>
                                </div>
                            </div>
                            
                            <?php
                            // Отримання статистики по продукту
                            $productStatsQuery = "
                                SELECT 
                                    COUNT(DISTINCT z.idd) as total_orders,
                                    SUM(z.kol) as total_quantity,
                                    SUM(z.kol * p.zena) as total_sales,
                                    COUNT(DISTINCT z.idklient) as total_clients
                                FROM zayavki z
                                JOIN product p ON z.id = p.id
                                WHERE z.id = ? AND z.data BETWEEN ? AND ?
                            ";
                            
                            $stmt = mysqli_prepare($connection, $productStatsQuery);
                            mysqli_stmt_bind_param($stmt, "iss", $productId, $startDate, $endDate);
                            mysqli_stmt_execute($stmt);
                            $productStatsResult = mysqli_stmt_get_result($stmt);
                            $productStats = mysqli_fetch_assoc($productStatsResult);
                            ?>
                            
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <p><strong>Кількість замовлень:</strong> <?php echo $productStats['total_orders']; ?></p>
                                    <p><strong>Загальна кількість одиниць:</strong> <?php echo $productStats['total_quantity']; ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Загальна сума продажів:</strong> <?php echo number_format($productStats['total_sales'], 2); ?> грн</p>
                                    <p><strong>Кількість клієнтів:</strong> <?php echo $productStats['total_clients']; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Топ клієнтів по продукту -->
            <h5 class="mb-3">Топ-5 клієнтів по замовленнях продукту</h5>
            <?php
            $topClientsQuery = "
                SELECT k.id, k.name, SUM(z.kol) as total_quantity, COUNT(DISTINCT z.idd) as order_count
                FROM zayavki z
                JOIN klientu k ON z.idklient = k.id
                WHERE z.id = ? AND z.data BETWEEN ? AND ?
                GROUP BY k.id
                ORDER BY total_quantity DESC
                LIMIT 5
            ";
            
            $stmt = mysqli_prepare($connection, $topClientsQuery);
            mysqli_stmt_bind_param($stmt, "iss", $productId, $startDate, $endDate);
            mysqli_stmt_execute($stmt);
            $topClientsResult = mysqli_stmt_get_result($stmt);
            ?>
            
            <div class="table-responsive mb-4">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Клієнт</th>
                            <th>Кількість замовлень</th>
                            <th>Загальна кількість</th>
                            <th>Сума продажів</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($topClientsResult) > 0): ?>
                            <?php while ($client = mysqli_fetch_assoc($topClientsResult)): ?>
                                <tr>
                                    <td><?php echo $client['id']; ?></td>
                                    <td><?php echo htmlspecialchars($client['name']); ?></td>
                                    <td><?php echo $client['order_count']; ?></td>
                                    <td><?php echo $client['total_quantity']; ?></td>
                                    <td><?php echo number_format($client['total_quantity'] * $product['zena'], 2); ?> грн</td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">Клієнтів не знайдено за обраний період</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Список замовлень продукту -->
            <h5 class="mb-3">Список замовлень за обраний період</h5>
            <?php
            $productOrdersQuery = "
                SELECT z.idd, z.idklient, k.name as client_name, z.kol, z.data, z.doba, z.status, (z.kol * p.zena) as total_price
                FROM zayavki z
                JOIN klientu k ON z.idklient = k.id
                JOIN product p ON z.id = p.id
                WHERE z.id = ? AND z.data BETWEEN ? AND ?
                ORDER BY z.data DESC, z.idd DESC
            ";
            
            $stmt = mysqli_prepare($connection, $productOrdersQuery);
            mysqli_stmt_bind_param($stmt, "iss", $productId, $startDate, $endDate);
            mysqli_stmt_execute($stmt);
            $productOrdersResult = mysqli_stmt_get_result($stmt);
            ?>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Клієнт</th>
                            <th>Кількість</th>
                            <th>Сума</th>
                            <th>Дата</th>
                            <th>Зміна</th>
                            <th>Статус</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($productOrdersResult) > 0): ?>
                            <?php while ($order = mysqli_fetch_assoc($productOrdersResult)): ?>
                                <tr>
                                    <td><?php echo $order['idd']; ?></td>
                                    <td><?php echo htmlspecialchars($order['client_name']); ?></td>
                                    <td><?php echo $order['kol']; ?></td>
                                    <td><?php echo number_format($order['total_price'], 2); ?> грн</td>
                                    <td><?php echo formatDate($order['data']); ?></td>
                                    <td>
                                        <?php if ($order['doba'] == 'денна'): ?>
                                        <span class="badge shift-badge shift-day">Денна</span>
                                        <?php else: ?>
                                        <span class="badge shift-badge shift-night">Нічна</span>
                                        <?php endif; ?>
                                    </td>
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
                                <td colspan="7" class="text-center">Замовлень не знайдено за обраний період</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="text-center mt-4">
                <a href="export_report.php?type=product&product_id=<?php echo $productId; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="btn btn-success">
                    <i class="fas fa-file-excel me-1"></i> Експортувати в Excel
                </a>
                <a href="print_report.php?type=product&product_id=<?php echo $productId; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="btn btn-primary ms-2" target="_blank">
                    <i class="fas fa-print me-1"></i> Друкувати
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Модальне вікно для вибору клієнта -->
<div class="modal fade" id="clientReportModal" tabindex="-1" aria-labelledby="clientReportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="clientReportModalLabel">Звіт по клієнту</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="GET">
                <div class="modal-body">
                    <input type="hidden" name="type" value="client">
                    
                    <div class="mb-3">
                        <label for="client_id" class="form-label">Виберіть клієнта</label>
                        <select class="form-select" id="client_id" name="client_id" required>
                            <option value="">-- Виберіть клієнта --</option>
                            <?php 
                            // Перезапуск результату запиту
                            mysqli_data_seek($clientsResult, 0);
                            while ($client = mysqli_fetch_assoc($clientsResult)): 
                            ?>
                                <option value="<?php echo $client['id']; ?>"><?php echo htmlspecialchars($client['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="start_date" class="form-label">Початкова дата</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $startDate; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="end_date" class="form-label">Кінцева дата</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $endDate; ?>" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                    <button type="submit" class="btn btn-success">Сформувати звіт</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальне вікно для вибору продукту -->
<div class="modal fade" id="productReportModal" tabindex="-1" aria-labelledby="productReportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productReportModalLabel">Звіт по продукту</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="GET">
                <div class="modal-body">
                    <input type="hidden" name="type" value="product">
                    
                    <div class="mb-3">
                        <label for="product_id" class="form-label">Виберіть продукт</label>
                        <select class="form-select" id="product_id" name="product_id" required>
                            <option value="">-- Виберіть продукт --</option>
                            <?php 
                            // Перезапуск результату запиту
                            mysqli_data_seek($productsResult, 0);
                            while ($product = mysqli_fetch_assoc($productsResult)): 
                            ?>
                                <option value="<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['nazvanie']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="start_date_product" class="form-label">Початкова дата</label>
                                <input type="date" class="form-control" id="start_date_product" name="start_date" value="<?php echo $startDate; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="end_date_product" class="form-label">Кінцева дата</label>
                                <input type="date" class="form-control" id="end_date_product" name="end_date" value="<?php echo $endDate; ?>" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                    <button type="submit" class="btn btn-warning">Сформувати звіт</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
include_once '../../includes/footer.php';
?>