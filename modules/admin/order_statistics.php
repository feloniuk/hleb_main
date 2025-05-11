<?php 
$pageTitle = 'Статистика замовлень';

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Перевірка доступу
if (!checkAccess(['admin'])) {
    header("Location: ../../index.php");
    exit;
}

$connection = connectDatabase();

// Отримання параметрів фільтрації з URL
$period = isset($_GET['period']) ? $_GET['period'] : 'month';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01'); // Початок поточного місяця
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d'); // Поточна дата

// Визначення дат для фільтрації залежно від періоду
if ($period == 'week') {
    $dateFrom = date('Y-m-d', strtotime('-1 week'));
} elseif ($period == 'month') {
    $dateFrom = date('Y-m-01');
} elseif ($period == 'quarter') {
    $dateFrom = date('Y-m-d', strtotime('-3 months'));
} elseif ($period == 'year') {
    $dateFrom = date('Y-01-01');
}

// Отримання загальної статистики замовлень
$totalStatsQuery = "SELECT 
                    COUNT(DISTINCT z.idd) as orders_count,
                    SUM(z.kol) as total_quantity,
                    SUM(z.kol * p.zena) as total_sales,
                    SUM(z.kol * p.stoimost) as total_cost,
                    SUM(z.kol * (p.zena - p.stoimost)) as total_profit,
                    COUNT(DISTINCT z.idklient) as clients_count
                  FROM zayavki z
                  JOIN product p ON z.id = p.id
                  WHERE z.data BETWEEN ? AND ?";
$stmt = mysqli_prepare($connection, $totalStatsQuery);
mysqli_stmt_bind_param($stmt, "ss", $dateFrom, $dateTo);
mysqli_stmt_execute($stmt);
$totalStatsResult = mysqli_stmt_get_result($stmt);
$totalStats = mysqli_fetch_assoc($totalStatsResult);

// Статистика по статусах замовлень
$statusStatsQuery = "SELECT 
                    z.status,
                    COUNT(DISTINCT z.idd) as orders_count,
                    SUM(z.kol) as total_quantity,
                    SUM(z.kol * p.zena) as total_sales
                  FROM zayavki z
                  JOIN product p ON z.id = p.id
                  WHERE z.data BETWEEN ? AND ?
                  GROUP BY z.status";
$stmt = mysqli_prepare($connection, $statusStatsQuery);
mysqli_stmt_bind_param($stmt, "ss", $dateFrom, $dateTo);
mysqli_stmt_execute($stmt);
$statusStatsResult = mysqli_stmt_get_result($stmt);

// Статистика по продуктах
$productStatsQuery = "SELECT 
                      p.id, p.nazvanie,
                      COUNT(DISTINCT z.idd) as orders_count,
                      SUM(z.kol) as total_quantity,
                      SUM(z.kol * p.zena) as total_sales,
                      SUM(z.kol * (p.zena - p.stoimost)) as total_profit
                    FROM zayavki z
                    JOIN product p ON z.id = p.id
                    WHERE z.data BETWEEN ? AND ?
                    GROUP BY p.id
                    ORDER BY total_sales DESC
                    LIMIT 10";
$stmt = mysqli_prepare($connection, $productStatsQuery);
mysqli_stmt_bind_param($stmt, "ss", $dateFrom, $dateTo);
mysqli_stmt_execute($stmt);
$productStatsResult = mysqli_stmt_get_result($stmt);

// Статистика по клієнтах
$clientStatsQuery = "SELECT 
                    k.id, k.name,
                    COUNT(DISTINCT z.idd) as orders_count,
                    SUM(z.kol) as total_quantity,
                    SUM(z.kol * p.zena) as total_sales
                  FROM zayavki z
                  JOIN klientu k ON z.idklient = k.id
                  JOIN product p ON z.id = p.id
                  WHERE z.data BETWEEN ? AND ?
                  GROUP BY k.id
                  ORDER BY total_sales DESC
                  LIMIT 10";
$stmt = mysqli_prepare($connection, $clientStatsQuery);
mysqli_stmt_bind_param($stmt, "ss", $dateFrom, $dateTo);
mysqli_stmt_execute($stmt);
$clientStatsResult = mysqli_stmt_get_result($stmt);

// Статистика по містах
$cityStatsQuery = "SELECT 
                  k.city,
                  COUNT(DISTINCT z.idd) as orders_count,
                  SUM(z.kol) as total_quantity,
                  SUM(z.kol * p.zena) as total_sales
                FROM zayavki z
                JOIN klientu k ON z.idklient = k.id
                JOIN product p ON z.id = p.id
                WHERE z.data BETWEEN ? AND ? AND k.city != ''
                GROUP BY k.city
                ORDER BY total_sales DESC";
$stmt = mysqli_prepare($connection, $cityStatsQuery);
mysqli_stmt_bind_param($stmt, "ss", $dateFrom, $dateTo);
mysqli_stmt_execute($stmt);
$cityStatsResult = mysqli_stmt_get_result($stmt);

// Статистика по днях за останній місяць (для графіка)
$dailyStatsQuery = "SELECT 
                    z.data,
                    COUNT(DISTINCT z.idd) as orders_count,
                    SUM(z.kol * p.zena) as total_sales
                  FROM zayavki z
                  JOIN product p ON z.id = p.id
                  WHERE z.data BETWEEN DATE_SUB(?, INTERVAL 30 DAY) AND ?
                  GROUP BY z.data
                  ORDER BY z.data";
$stmt = mysqli_prepare($connection, $dailyStatsQuery);
mysqli_stmt_bind_param($stmt, "ss", $dateTo, $dateTo);
mysqli_stmt_execute($stmt);
$dailyStatsResult = mysqli_stmt_get_result($stmt);

// Зібрання даних для графіка
$chartDates = [];
$chartSales = [];
$chartOrders = [];

while ($day = mysqli_fetch_assoc($dailyStatsResult)) {
    $chartDates[] = $day['data'];
    $chartSales[] = floatval($day['total_sales']);
    $chartOrders[] = intval($day['orders_count']);
}

include_once '../../includes/header.php';
?>

<!-- Головне меню -->
<div class="row mb-4">
    <div class="col-md-12">
        <nav class="nav main-menu nav-pills nav-fill">
            <a class="nav-link" href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Головна
            </a>
            <a class="nav-link" href="users.php">
                <i class="fas fa-users"></i> Користувачі
            </a>
            <a class="nav-link" href="clients.php">
                <i class="fas fa-user-tie"></i> Клієнти
            </a>
            <a class="nav-link" href="products.php">
                <i class="fas fa-bread-slice"></i> Продукція
            </a>
            <a class="nav-link active" href="orders.php">
                <i class="fas fa-clipboard-list"></i> Замовлення
            </a>
            <a class="nav-link" href="settings.php">
                <i class="fas fa-cogs"></i> Налаштування
            </a>
            <a class="nav-link" href="reports.php">
                <i class="fas fa-chart-bar"></i> Звіти
            </a>
        </nav>
    </div>
</div>

<!-- Фільтри статистики -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-filter me-2"></i> Фільтри статистики
        </h5>
    </div>
    <div class="card-body">
        <form action="" method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="period" class="form-label">Період</label>
                <select class="form-select" id="period" name="period">
                    <option value="week" <?php echo ($period == 'week') ? 'selected' : ''; ?>>Останній тиждень</option>
                    <option value="month" <?php echo ($period == 'month') ? 'selected' : ''; ?>>Поточний місяць</option>
                    <option value="quarter" <?php echo ($period == 'quarter') ? 'selected' : ''; ?>>Останній квартал</option>
                    <option value="year" <?php echo ($period == 'year') ? 'selected' : ''; ?>>Поточний рік</option>
                    <option value="custom" <?php echo ($period == 'custom') ? 'selected' : ''; ?>>Довільний період</option>
                </select>
            </div>
            
            <div class="col-md-3" id="date_from_container" style="<?php echo ($period == 'custom') ? '' : 'display: none;'; ?>">
                <label for="date_from" class="form-label">З дати</label>
                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $dateFrom; ?>">
            </div>
            
            <div class="col-md-3" id="date_to_container" style="<?php echo ($period == 'custom') ? '' : 'display: none;'; ?>">
                <label for="date_to" class="form-label">До дати</label>
                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $dateTo; ?>">
            </div>
            
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter me-1"></i> Застосувати
                </button>
                
                <a href="orders_export.php?date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>" class="btn btn-success ms-2">
                    <i class="fas fa-file-export me-1"></i> Експорт
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Загальна статистика -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-chart-bar me-2"></i> Загальна статистика за період <?php echo formatDate($dateFrom); ?> - <?php echo formatDate($dateTo); ?>
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 mb-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h6>Кількість замовлень</h6>
                        <p class="display-6"><?php echo $totalStats['orders_count'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h6>Загальна сума (грн)</h6>
                        <p class="display-6"><?php echo number_format($totalStats['total_sales'] ?? 0, 2); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h6>Кількість клієнтів</h6>
                        <p class="display-6"><?php echo $totalStats['clients_count'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h6>Прибуток (грн)</h6>
                        <p class="display-6"><?php echo number_format($totalStats['total_profit'] ?? 0, 2); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Графік продажів -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-chart-line me-2"></i> Графік продажів
        </h5>
    </div>
    <div class="card-body">
        <div id="sales_chart" style="width: 100%; height: 400px;"></div>
    </div>
</div>

<!-- Статистика за статусами -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-tasks me-2"></i> Замовлення за статусами
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Статус</th>
                        <th>Кількість замовлень</th>
                        <th>Кількість одиниць</th>
                        <th>Загальна сума (грн)</th>
                        <th>%</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $totalOrdersCount = $totalStats['orders_count'] ?? 0;
                    $totalSalesAmount = $totalStats['total_sales'] ?? 0;
                    
                    mysqli_data_seek($statusStatsResult, 0);
                    while ($status = mysqli_fetch_assoc($statusStatsResult)):
                        $percentOrders = ($totalOrdersCount > 0) ? ($status['orders_count'] / $totalOrdersCount * 100) : 0;
                        $percentSales = ($totalSalesAmount > 0) ? ($status['total_sales'] / $totalSalesAmount * 100) : 0;
                    ?>
                        <tr>
                            <td>
                                <?php
                                switch ($status['status']) {
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
                                        echo '<span class="badge bg-secondary">' . $status['status'] . '</span>';
                                        break;
                                }
                                ?>
                            </td>
                            <td><?php echo $status['orders_count']; ?></td>
                            <td><?php echo $status['total_quantity']; ?></td>
                            <td><?php echo number_format($status['total_sales'], 2); ?> грн</td>
                            <td>
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" style="width: <?php echo number_format($percentSales, 2); ?>%;" aria-valuenow="<?php echo number_format($percentSales, 2); ?>" aria-valuemin="0" aria-valuemax="100"><?php echo number_format($percentSales, 2); ?>%</div>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="row">
    <!-- Топ продуктів -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-bread-slice me-2"></i> Топ-10 продуктів
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Продукт</th>
                                <th>Кількість</th>
                                <th>Сума (грн)</th>
                                <th>Прибуток (грн)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            mysqli_data_seek($productStatsResult, 0);
                            while ($product = mysqli_fetch_assoc($productStatsResult)): 
                            ?>
                                <tr>
                                    <td><a href="product_details.php?id=<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['nazvanie']); ?></a></td>
                                    <td><?php echo $product['total_quantity']; ?></td>
                                    <td><?php echo number_format($product['total_sales'], 2); ?></td>
                                    <td><?php echo number_format($product['total_profit'], 2); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Топ клієнтів -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-user-tie me-2"></i> Топ-10 клієнтів
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Клієнт</th>
                                <th>Замовлень</th>
                                <th>Кількість</th>
                                <th>Сума (грн)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            mysqli_data_seek($clientStatsResult, 0);
                            while ($client = mysqli_fetch_assoc($clientStatsResult)): 
                            ?>
                                <tr>
                                    <td><a href="client_details.php?id=<?php echo $client['id']; ?>"><?php echo htmlspecialchars($client['name']); ?></a></td>
                                    <td><?php echo $client['orders_count']; ?></td>
                                    <td><?php echo $client['total_quantity']; ?></td>
                                    <td><?php echo number_format($client['total_sales'], 2); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Статистика по містах -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-map-marker-alt me-2"></i> Статистика за містами
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Місто</th>
                        <th>Кількість замовлень</th>
                        <th>Кількість одиниць</th>
                        <th>Загальна сума (грн)</th>
                        <th>%</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    mysqli_data_seek($cityStatsResult, 0);
                    while ($city = mysqli_fetch_assoc($cityStatsResult)):
                        $percentCity = ($totalSalesAmount > 0) ? ($city['total_sales'] / $totalSalesAmount * 100) : 0;
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($city['city']); ?></td>
                            <td><?php echo $city['orders_count']; ?></td>
                            <td><?php echo $city['total_quantity']; ?></td>
                            <td><?php echo number_format($city['total_sales'], 2); ?> грн</td>
                            <td>
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" style="width: <?php echo number_format($percentCity, 2); ?>%;" aria-valuenow="<?php echo number_format($percentCity, 2); ?>" aria-valuemin="0" aria-valuemax="100"><?php echo number_format($percentCity, 2); ?>%</div>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Завантаження Google Charts -->
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
    google.charts.load('current', {'packages':['corechart']});
    google.charts.setOnLoadCallback(drawCharts);
    
    function drawCharts() {
        // Графік продажів
        var salesData = new google.visualization.DataTable();
        salesData.addColumn('string', 'Дата');
        salesData.addColumn('number', 'Сума продажів (грн)');
        salesData.addColumn('number', 'Кількість замовлень');
        
        <?php
        $jsChartData = [];
        for ($i = 0; $i < count($chartDates); $i++) {
            $jsChartData[] = "['" . formatDate($chartDates[$i]) . "', " . $chartSales[$i] . ", " . $chartOrders[$i] . "]";
        }
        ?>
        
        salesData.addRows([
            <?php echo implode(",\n            ", $jsChartData); ?>
        ]);
        
        var salesOptions = {
            title: 'Динаміка продажів за період',
            height: 400,
            seriesType: 'bars',
            series: {1: {type: 'line', targetAxisIndex: 1}},
            vAxes: {
                0: {title: 'Сума продажів (грн)'},
                1: {title: 'Кількість замовлень'}
            },
            hAxis: {title: 'Дата'},
            colors: ['#4285F4', '#DB4437']
        };
        
        var salesChart = new google.visualization.ComboChart(document.getElementById('sales_chart'));
        salesChart.draw(salesData, salesOptions);
    }
</script>

<?php
/**
 * Функція для запису дій у системний журнал
 * 
 * @param mysqli $connection Підключення до БД
 * @param string $action Тип дії
 * @param string $details Деталі дії
 * @return bool Результат запису
 */
function logAction($connection, $action, $details) {
    // Перевірка, чи існує таблиця
    $tableExistsQuery = "SHOW TABLES LIKE 'system_log'";
    $tableExistsResult = mysqli_query($connection, $tableExistsQuery);
    
    if (mysqli_num_rows($tableExistsResult) == 0) {
        return false;
    }
    
    $userId = $_SESSION['id'] ?? 0;
    $timestamp = date('Y-m-d H:i:s');
    
    $query = "INSERT INTO system_log (action, user_id, timestamp, details) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "siss", $action, $userId, $timestamp, $details);
    
    return mysqli_stmt_execute($stmt);
}

include_once '../../includes/footer.php';