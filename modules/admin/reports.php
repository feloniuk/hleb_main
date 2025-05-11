<?php
$pageTitle = 'Звіти';

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Перевірка доступу
if (!checkAccess(['admin'])) {
    header("Location: ../../index.php");
    exit;
}

$connection = connectDatabase();
$success = '';
$error = '';

// Тип звіту та параметри
$reportType = isset($_GET['type']) ? $_GET['type'] : '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // Початок поточного місяця
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'); // Поточна дата
$clientId = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;
$productId = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$exportFormat = isset($_GET['format']) ? $_GET['format'] : '';

// Отримання списку клієнтів для фільтра
$clientsQuery = "SELECT id, name FROM klientu ORDER BY name";
$clientsResult = mysqli_query($connection, $clientsQuery);

// Отримання списку продуктів для фільтра
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
            <a class="nav-link" href="users.php">
                <i class="fas fa-users"></i> Користувачі
            </a>
            <a class="nav-link" href="clients.php">
                <i class="fas fa-user-tie"></i> Клієнти
            </a>
            <a class="nav-link" href="products.php">
                <i class="fas fa-bread-slice"></i> Продукція
            </a>
            <a class="nav-link" href="orders.php">
                <i class="fas fa-clipboard-list"></i> Замовлення
            </a>
            <a class="nav-link" href="settings.php">
                <i class="fas fa-cogs"></i> Налаштування
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

<!-- Вибір типу звіту -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-chart-bar me-2"></i> Генерація звітів
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-clipboard-list fa-3x mb-3 text-primary"></i>
                        <h5 class="card-title">Звіт по замовленнях</h5>
                        <p class="card-text">Загальна статистика по замовленнях за обраний період</p>
                        <a href="?type=orders" class="btn btn-primary">Сформувати звіт</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-bread-slice fa-3x mb-3 text-warning"></i>
                        <h5 class="card-title">Звіт по продуктах</h5>
                        <p class="card-text">Аналіз продажів продукції за обраний період</p>
                        <a href="?type=products" class="btn btn-warning">Сформувати звіт</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-3x mb-3 text-success"></i>
                        <h5 class="card-title">Звіт по клієнтах</h5>
                        <p class="card-text">Аналіз активності клієнтів за обраний період</p>
                        <a href="?type=clients" class="btn btn-success">Сформувати звіт</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-money-bill-wave fa-3x mb-3 text-danger"></i>
                        <h5 class="card-title">Фінансовий звіт</h5>
                        <p class="card-text">Фінансовий аналіз продажів та прибутку</p>
                        <a href="?type=finance" class="btn btn-danger">Сформувати звіт</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($reportType): ?>
<!-- Фільтри звіту -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-filter me-2"></i> Параметри звіту
        </h5>
    </div>
    <div class="card-body">
        <form action="" method="GET" class="row g-3">
            <input type="hidden" name="type" value="<?php echo htmlspecialchars($reportType); ?>">
            
            <div class="col-md-3">
                <label for="start_date" class="form-label">Початкова дата</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
            </div>
            
            <div class="col-md-3">
                <label for="end_date" class="form-label">Кінцева дата</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
            </div>
            
            <?php if ($reportType == 'clients' || $reportType == 'orders'): ?>
            <div class="col-md-3">
                <label for="client_id" class="form-label">Клієнт</label>
                <select class="form-select" id="client_id" name="client_id">
                    <option value="0">Всі клієнти</option>
                    <?php 
                    mysqli_data_seek($clientsResult, 0);
                    while ($client = mysqli_fetch_assoc($clientsResult)) {
                        $selected = ($clientId == $client['id']) ? 'selected' : '';
                        echo '<option value="' . $client['id'] . '" ' . $selected . '>' . htmlspecialchars($client['name']) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <?php endif; ?>
            
            <?php if ($reportType == 'products' || $reportType == 'orders'): ?>
            <div class="col-md-3">
                <label for="product_id" class="form-label">Продукт</label>
                <select class="form-select" id="product_id" name="product_id">
                    <option value="0">Всі продукти</option>
                    <?php 
                    mysqli_data_seek($productsResult, 0);
                    while ($product = mysqli_fetch_assoc($productsResult)) {
                        $selected = ($productId == $product['id']) ? 'selected' : '';
                        echo '<option value="' . $product['id'] . '" ' . $selected . '>' . htmlspecialchars($product['nazvanie']) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div class="col-md-12">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter me-1"></i> Застосувати фільтри
                </button>
                
                <div class="btn-group ms-2">
                    <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-file-export me-1"></i> Експорт
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo $_SERVER['REQUEST_URI'] . '&format=excel'; ?>">Excel</a></li>
                        <li><a class="dropdown-item" href="<?php echo $_SERVER['REQUEST_URI'] . '&format=pdf'; ?>">PDF</a></li>
                        <li><a class="dropdown-item" href="<?php echo $_SERVER['REQUEST_URI'] . '&format=csv'; ?>">CSV</a></li>
                    </ul>
                </div>
                
                <a  href="javascript:void(0);" onclick="printDiv('reportResults');" class="btn btn-info ms-2">
                    <i class="fas fa-print me-1"></i> Друк
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Результати звіту -->
<div class="card" id="reportResults">
    <div class="card-header">
        <h5 class="mb-0">
            <?php
            $reportTitle = '';
            switch ($reportType) {
                case 'orders':
                    $reportTitle = 'Звіт по замовленнях';
                    break;
                case 'products':
                    $reportTitle = 'Звіт по продуктах';
                    break;
                case 'clients':
                    $reportTitle = 'Звіт по клієнтах';
                    break;
                case 'finance':
                    $reportTitle = 'Фінансовий звіт';
                    break;
                default:
                    $reportTitle = 'Звіт';
                    break;
            }
            echo '<i class="fas fa-file-alt me-2"></i> ' . $reportTitle . ' - ' . formatDate($startDate) . ' - ' . formatDate($endDate);
            ?>
        </h5>
    </div>
    <div class="card-body">
        <?php
        switch ($reportType) {
            case 'orders':
                generateOrdersReport($connection, $startDate, $endDate, $clientId, $productId);
                break;
            case 'products':
                generateProductsReport($connection, $startDate, $endDate, $productId);
                break;
            case 'clients':
                generateClientsReport($connection, $startDate, $endDate, $clientId);
                break;
            case 'finance':
                generateFinanceReport($connection, $startDate, $endDate);
                break;
            default:
                echo '<div class="alert alert-warning">Невідомий тип звіту</div>';
                break;
        }
        ?>
    </div>
</div>
<?php endif; ?>
<script>
function printDiv(divId) {
    // Создаем iframe элемент
    var printIframe = document.createElement('iframe');
    printIframe.style.position = 'absolute';
    printIframe.style.top = '-1000px';
    printIframe.style.left = '-1000px';
    printIframe.id = "printIframe";
    document.body.appendChild(printIframe);
    
    // Получаем содержимое div, которое нужно напечатать
    var divToPrint = document.getElementById(divId);
    
    // Получаем документ iframe
    var frameDoc = printIframe.contentWindow.document;
    
    // Открываем документ для записи
    frameDoc.open();
    
    // Добавляем базовую HTML структуру и стили
    frameDoc.write('<html><head><title>Друк</title>');
    
    // Копируем все стили с основной страницы
    var styles = document.getElementsByTagName('link');
    for (var i = 0; i < styles.length; i++) {
        if (styles[i].rel === 'stylesheet') {
            frameDoc.write('<link href="' + styles[i].href + '" rel="stylesheet" type="text/css" />');
        }
    }
    
    // Закрываем head и открываем body
    frameDoc.write('</head><body>');
    
    // Вставляем содержимое нужного div
    frameDoc.write(divToPrint.innerHTML);
    
    // Закрываем body и html
    frameDoc.write('</body></html>');
    frameDoc.close();
    
    // Ждем полной загрузки iframe
    setTimeout(function() {
        // Вызываем печать
        printIframe.contentWindow.focus();
        printIframe.contentWindow.print();
        
        // Удаляем iframe после печати
        setTimeout(function() {
            document.body.removeChild(printIframe);
        }, 1000);
    }, 500);
}
</script>
<?php
/**
 * Генерація звіту по замовленнях
 * 
 * @param mysqli $connection Підключення до БД
 * @param string $startDate Початкова дата
 * @param string $endDate Кінцева дата
 * @param int $clientId ID клієнта (0 - всі)
 * @param int $productId ID продукту (0 - всі)
 */
function generateOrdersReport($connection, $startDate, $endDate, $clientId = 0, $productId = 0) {
    // Формування запиту з урахуванням фільтрів
    $query = "SELECT z.*, k.name as client_name, p.nazvanie as product_name, p.zena, (z.kol * p.zena) as total_price
              FROM zayavki z
              JOIN klientu k ON z.idklient = k.id
              JOIN product p ON z.id = p.id
              WHERE z.data BETWEEN ? AND ?";
    
    $params = [$startDate, $endDate];
    $types = 'ss';
    
    if ($clientId > 0) {
        $query .= " AND z.idklient = ?";
        $params[] = $clientId;
        $types .= 'i';
    }
    
    if ($productId > 0) {
        $query .= " AND z.id = ?";
        $params[] = $productId;
        $types .= 'i';
    }
    
    $query .= " ORDER BY z.data DESC, z.idd DESC";
    
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    // Отримання підсумкових даних
    $totalOrdersQuery = "SELECT 
                        COUNT(DISTINCT z.idd) as orders_count,
                        SUM(z.kol) as total_quantity,
                        SUM(z.kol * p.zena) as total_amount
                        FROM zayavki z
                        JOIN product p ON z.id = p.id
                        WHERE z.data BETWEEN ? AND ?";
    
    $totalParams = [$startDate, $endDate];
    $totalTypes = 'ss';
    
    if ($clientId > 0) {
        $totalOrdersQuery .= " AND z.idklient = ?";
        $totalParams[] = $clientId;
        $totalTypes .= 'i';
    }
    
    if ($productId > 0) {
        $totalOrdersQuery .= " AND z.id = ?";
        $totalParams[] = $productId;
        $totalTypes .= 'i';
    }
    
    $totalStmt = mysqli_prepare($connection, $totalOrdersQuery);
    mysqli_stmt_bind_param($totalStmt, $totalTypes, ...$totalParams);
    mysqli_stmt_execute($totalStmt);
    $totalResult = mysqli_stmt_get_result($totalStmt);
    $totalData = mysqli_fetch_assoc($totalResult);
    
    // Відображення сумарної інформації
    echo '<div class="row mb-4">';
    echo '<div class="col-md-4">';
    echo '<div class="card bg-light">';
    echo '<div class="card-body text-center">';
    echo '<h6>Загальна кількість замовлень</h6>';
    echo '<p class="fs-4">' . $totalData['orders_count'] . '</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="col-md-4">';
    echo '<div class="card bg-light">';
    echo '<div class="card-body text-center">';
    echo '<h6>Загальна кількість одиниць</h6>';
    echo '<p class="fs-4">' . $totalData['total_quantity'] . '</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="col-md-4">';
    echo '<div class="card bg-light">';
    echo '<div class="card-body text-center">';
    echo '<h6>Загальна сума замовлень</h6>';
    echo '<p class="fs-4">' . number_format($totalData['total_amount'], 2) . ' грн</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    // Відображення результатів
    if (mysqli_num_rows($result) > 0) {
        echo '<div class="table-responsive">';
        echo '<table class="table table-striped table-hover">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>ID</th>';
        echo '<th>Клієнт</th>';
        echo '<th>Продукт</th>';
        echo '<th>Кількість</th>';
        echo '<th>Ціна</th>';
        echo '<th>Сума</th>';
        echo '<th>Дата</th>';
        echo '<th>Зміна</th>';
        echo '<th>Статус</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        while ($row = mysqli_fetch_assoc($result)) {
            echo '<tr>';
            echo '<td>' . $row['idd'] . '</td>';
            echo '<td>' . htmlspecialchars($row['client_name']) . '</td>';
            echo '<td>' . htmlspecialchars($row['product_name']) . '</td>';
            echo '<td>' . $row['kol'] . '</td>';
            echo '<td>' . number_format($row['zena'], 2) . ' грн</td>';
            echo '<td>' . number_format($row['total_price'], 2) . ' грн</td>';
            echo '<td>' . formatDate($row['data']) . '</td>';
            echo '<td>' . $row['doba'] . '</td>';
            echo '<td>' . $row['status'] . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    } else {
        echo '<div class="alert alert-info">Немає даних для відображення</div>';
    }
}

/**
 * Генерація звіту по продуктах
 * 
 * @param mysqli $connection Підключення до БД
 * @param string $startDate Початкова дата
 * @param string $endDate Кінцева дата
 * @param int $productId ID продукту (0 - всі)
 */
function generateProductsReport($connection, $startDate, $endDate, $productId = 0) {
    // Формування запиту з урахуванням фільтрів
    $query = "SELECT p.id, p.nazvanie, p.ves, p.stoimost, p.zena, 
              COUNT(z.idd) as orders_count,
              SUM(z.kol) as total_quantity,
              SUM(z.kol * p.zena) as total_sales,
              SUM(z.kol * (p.zena - p.stoimost)) as total_profit
              FROM product p
              LEFT JOIN zayavki z ON p.id = z.id AND z.data BETWEEN ? AND ?
              WHERE 1=1";
    
    $params = [$startDate, $endDate];
    $types = 'ss';
    
    if ($productId > 0) {
        $query .= " AND p.id = ?";
        $params[] = $productId;
        $types .= 'i';
    }
    
    $query .= " GROUP BY p.id ORDER BY total_quantity DESC";
    
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    // Отримання підсумкових даних
    $totalDataQuery = "SELECT 
                      COUNT(DISTINCT p.id) as products_count,
                      SUM(z.kol) as total_quantity,
                      SUM(z.kol * p.zena) as total_sales,
                      SUM(z.kol * (p.zena - p.stoimost)) as total_profit
                      FROM product p
                      LEFT JOIN zayavki z ON p.id = z.id AND z.data BETWEEN ? AND ?
                      WHERE 1=1";
    
    $totalParams = [$startDate, $endDate];
    $totalTypes = 'ss';
    
    if ($productId > 0) {
        $totalDataQuery .= " AND p.id = ?";
        $totalParams[] = $productId;
        $totalTypes .= 'i';
    }
    
    $totalStmt = mysqli_prepare($connection, $totalDataQuery);
    mysqli_stmt_bind_param($totalStmt, $totalTypes, ...$totalParams);
    mysqli_stmt_execute($totalStmt);
    $totalResult = mysqli_stmt_get_result($totalStmt);
    $totalData = mysqli_fetch_assoc($totalResult);
    
    // Відображення сумарної інформації
    echo '<div class="row mb-4">';
    echo '<div class="col-md-3">';
    echo '<div class="card bg-light">';
    echo '<div class="card-body text-center">';
    echo '<h6>Кількість продуктів</h6>';
    echo '<p class="fs-4">' . $totalData['products_count'] . '</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="col-md-3">';
    echo '<div class="card bg-light">';
    echo '<div class="card-body text-center">';
    echo '<h6>Загальна кількість продажів</h6>';
    echo '<p class="fs-4">' . ($totalData['total_quantity'] ?? 0) . '</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="col-md-3">';
    echo '<div class="card bg-light">';
    echo '<div class="card-body text-center">';
    echo '<h6>Загальна сума продажів</h6>';
    echo '<p class="fs-4">' . number_format($totalData['total_sales'] ?? 0, 2) . ' грн</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="col-md-3">';
    echo '<div class="card bg-light">';
    echo '<div class="card-body text-center">';
    echo '<h6>Загальний прибуток</h6>';
    echo '<p class="fs-4">' . number_format($totalData['total_profit'] ?? 0, 2) . ' грн</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    // Відображення результатів
    if (mysqli_num_rows($result) > 0) {
        echo '<div class="table-responsive">';
        echo '<table class="table table-striped table-hover">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>ID</th>';
        echo '<th>Назва продукту</th>';
        echo '<th>Вага (кг)</th>';
        echo '<th>Собівартість (грн)</th>';
        echo '<th>Ціна (грн)</th>';
        echo '<th>Маржа (%)</th>';
        echo '<th>Кількість замовлень</th>';
        echo '<th>Кількість одиниць</th>';
        echo '<th>Сума продажів (грн)</th>';
        echo '<th>Прибуток (грн)</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        while ($row = mysqli_fetch_assoc($result)) {
            $margin = ($row['stoimost'] > 0) ? (($row['zena'] - $row['stoimost']) / $row['stoimost'] * 100) : 0;
            
            echo '<tr>';
            echo '<td>' . $row['id'] . '</td>';
            echo '<td>' . htmlspecialchars($row['nazvanie']) . '</td>';
            echo '<td>' . $row['ves'] . '</td>';
            echo '<td>' . number_format($row['stoimost'], 2) . '</td>';
            echo '<td>' . number_format($row['zena'], 2) . '</td>';
            echo '<td>' . number_format($margin, 2) . '%</td>';
            echo '<td>' . ($row['orders_count'] ?? 0) . '</td>';
            echo '<td>' . ($row['total_quantity'] ?? 0) . '</td>';
            echo '<td>' . number_format($row['total_sales'] ?? 0, 2) . '</td>';
            echo '<td>' . number_format($row['total_profit'] ?? 0, 2) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    } else {
        echo '<div class="alert alert-info">Немає даних для відображення</div>';
    }
}

/**
 * Генерація звіту по клієнтах
 * 
 * @param mysqli $connection Підключення до БД
 * @param string $startDate Початкова дата
 * @param string $endDate Кінцева дата
 * @param int $clientId ID клієнта (0 - всі)
 */
function generateClientsReport($connection, $startDate, $endDate, $clientId = 0) {
    // Формування запиту з урахуванням фільтрів
    $query = "SELECT k.id, k.name, k.fio, k.city, k.tel,
              COUNT(DISTINCT z.idd) as orders_count,
              SUM(z.kol) as total_quantity,
              SUM(z.kol * p.zena) as total_amount,
              COUNT(DISTINCT z.id) as unique_products,
              MAX(z.data) as last_order_date
              FROM klientu k
              LEFT JOIN zayavki z ON k.id = z.idklient AND z.data BETWEEN ? AND ?
              LEFT JOIN product p ON z.id = p.id
              WHERE 1=1";
    
    $params = [$startDate, $endDate];
    $types = 'ss';
    
    if ($clientId > 0) {
        $query .= " AND k.id = ?";
        $params[] = $clientId;
        $types .= 'i';
    }
    
    $query .= " GROUP BY k.id ORDER BY total_amount DESC";
    
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    // Отримання підсумкових даних
    $totalDataQuery = "SELECT 
                      COUNT(DISTINCT k.id) as clients_count,
                      COUNT(DISTINCT z.idd) as orders_count,
                      SUM(z.kol) as total_quantity,
                      SUM(z.kol * p.zena) as total_amount
                      FROM klientu k
                      LEFT JOIN zayavki z ON k.id = z.idklient AND z.data BETWEEN ? AND ?
                      LEFT JOIN product p ON z.id = p.id
                      WHERE 1=1";
    
    $totalParams = [$startDate, $endDate];
    $totalTypes = 'ss';
    
    if ($clientId > 0) {
        $totalDataQuery .= " AND k.id = ?";
        $totalParams[] = $clientId;
        $totalTypes .= 'i';
    }
    
    $totalStmt = mysqli_prepare($connection, $totalDataQuery);
    mysqli_stmt_bind_param($totalStmt, $totalTypes, ...$totalParams);
    mysqli_stmt_execute($totalStmt);
    $totalResult = mysqli_stmt_get_result($totalStmt);
    $totalData = mysqli_fetch_assoc($totalResult);
    
    // Відображення сумарної інформації
    echo '<div class="row mb-4">';
    echo '<div class="col-md-3">';
    echo '<div class="card bg-light">';
    echo '<div class="card-body text-center">';
    echo '<h6>Кількість клієнтів</h6>';
    echo '<p class="fs-4">' . $totalData['clients_count'] . '</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="col-md-3">';
    echo '<div class="card bg-light">';
    echo '<div class="card-body text-center">';
    echo '<h6>Загальна кількість замовлень</h6>';
    echo '<p class="fs-4">' . ($totalData['orders_count'] ?? 0) . '</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="col-md-3">';
    echo '<div class="card bg-light">';
    echo '<div class="card-body text-center">';
    echo '<h6>Загальна кількість одиниць</h6>';
    echo '<p class="fs-4">' . ($totalData['total_quantity'] ?? 0) . '</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="col-md-3">';
    echo '<div class="card bg-light">';
    echo '<div class="card-body text-center">';
    echo '<h6>Загальна сума замовлень</h6>';
    echo '<p class="fs-4">' . number_format($totalData['total_amount'] ?? 0, 2) . ' грн</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    // Відображення результатів
    if (mysqli_num_rows($result) > 0) {
        echo '<div class="table-responsive">';
        echo '<table class="table table-striped table-hover">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>ID</th>';
        echo '<th>Назва компанії</th>';
        echo '<th>Контактна особа</th>';
        echo '<th>Місто</th>';
        echo '<th>Телефон</th>';
        echo '<th>Кількість замовлень</th>';
        echo '<th>Унікальних продуктів</th>';
        echo '<th>Загальна кількість</th>';
        echo '<th>Загальна сума</th>';
        echo '<th>Останнє замовлення</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        while ($row = mysqli_fetch_assoc($result)) {
            echo '<tr>';
            echo '<td>' . $row['id'] . '</td>';
            echo '<td>' . htmlspecialchars($row['name']) . '</td>';
            echo '<td>' . htmlspecialchars($row['fio']) . '</td>';
            echo '<td>' . htmlspecialchars($row['city']) . '</td>';
            echo '<td>' . htmlspecialchars($row['tel']) . '</td>';
            echo '<td>' . ($row['orders_count'] ?? 0) . '</td>';
            echo '<td>' . ($row['unique_products'] ?? 0) . '</td>';
            echo '<td>' . ($row['total_quantity'] ?? 0) . '</td>';
            echo '<td>' . number_format($row['total_amount'] ?? 0, 2) . ' грн</td>';
            echo '<td>' . ($row['last_order_date'] ? formatDate($row['last_order_date']) : '-') . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    } else {
        echo '<div class="alert alert-info">Немає даних для відображення</div>';
    }
}

/**
 * Генерація фінансового звіту
 * 
 * @param mysqli $connection Підключення до БД
 * @param string $startDate Початкова дата
 * @param string $endDate Кінцева дата
 */
function generateFinanceReport($connection, $startDate, $endDate) {
    // Отримання фінансових даних по місяцях
    $monthlyDataQuery = "SELECT 
                        DATE_FORMAT(z.data, '%Y-%m') as month,
                        SUM(z.kol * p.zena) as total_sales,
                        SUM(z.kol * p.stoimost) as total_cost,
                        SUM(z.kol * (p.zena - p.stoimost)) as total_profit,
                        COUNT(DISTINCT z.idd) as orders_count,
                        SUM(z.kol) as total_quantity
                        FROM zayavki z
                        JOIN product p ON z.id = p.id
                        WHERE z.data BETWEEN ? AND ?
                        GROUP BY DATE_FORMAT(z.data, '%Y-%m')
                        ORDER BY month ASC";
    
    $stmt = mysqli_prepare($connection, $monthlyDataQuery);
    mysqli_stmt_bind_param($stmt, "ss", $startDate, $endDate);
    mysqli_stmt_execute($stmt);
    $monthlyResult = mysqli_stmt_get_result($stmt);
    
    // Отримання фінансових даних по продуктах
    $productDataQuery = "SELECT 
                        p.id, p.nazvanie,
                        SUM(z.kol) as total_quantity,
                        SUM(z.kol * p.zena) as total_sales,
                        SUM(z.kol * p.stoimost) as total_cost,
                        SUM(z.kol * (p.zena - p.stoimost)) as total_profit
                        FROM zayavki z
                        JOIN product p ON z.id = p.id
                        WHERE z.data BETWEEN ? AND ?
                        GROUP BY p.id
                        ORDER BY total_profit DESC
                        LIMIT 10";
    
    $stmt = mysqli_prepare($connection, $productDataQuery);
    mysqli_stmt_bind_param($stmt, "ss", $startDate, $endDate);
    mysqli_stmt_execute($stmt);
    $productResult = mysqli_stmt_get_result($stmt);
    
    // Отримання загальних фінансових даних
    $totalDataQuery = "SELECT 
                      SUM(z.kol * p.zena) as total_sales,
                      SUM(z.kol * p.stoimost) as total_cost,
                      SUM(z.kol * (p.zena - p.stoimost)) as total_profit,
                      COUNT(DISTINCT z.idd) as orders_count,
                      COUNT(DISTINCT z.idklient) as clients_count,
                      SUM(z.kol) as total_quantity
                      FROM zayavki z
                      JOIN product p ON z.id = p.id
                      WHERE z.data BETWEEN ? AND ?";
    
    $stmt = mysqli_prepare($connection, $totalDataQuery);
    mysqli_stmt_bind_param($stmt, "ss", $startDate, $endDate);
    mysqli_stmt_execute($stmt);
    $totalResult = mysqli_stmt_get_result($stmt);
    $totalData = mysqli_fetch_assoc($totalResult);
    
    // Розрахунок середніх значень
    $avgOrderValue = ($totalData['orders_count'] > 0) ? $totalData['total_sales'] / $totalData['orders_count'] : 0;
    $avgClientValue = ($totalData['clients_count'] > 0) ? $totalData['total_sales'] / $totalData['clients_count'] : 0;
    $profitMargin = ($totalData['total_sales'] > 0) ? ($totalData['total_profit'] / $totalData['total_sales'] * 100) : 0;
    
    // Відображення сумарної інформації
    echo '<div class="row mb-4">';
    echo '<div class="col-md-3">';
    echo '<div class="card bg-light">';
    echo '<div class="card-body text-center">';
    echo '<h6>Загальний дохід</h6>';
    echo '<p class="fs-4">' . number_format($totalData['total_sales'] ?? 0, 2) . ' грн</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="col-md-3">';
    echo '<div class="card bg-light">';
    echo '<div class="card-body text-center">';
    echo '<h6>Загальні витрати</h6>';
    echo '<p class="fs-4">' . number_format($totalData['total_cost'] ?? 0, 2) . ' грн</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="col-md-3">';
    echo '<div class="card bg-light">';
    echo '<div class="card-body text-center">';
    echo '<h6>Загальний прибуток</h6>';
    echo '<p class="fs-4">' . number_format($totalData['total_profit'] ?? 0, 2) . ' грн</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="col-md-3">';
    echo '<div class="card bg-light">';
    echo '<div class="card-body text-center">';
    echo '<h6>Маржа прибутку</h6>';
    echo '<p class="fs-4">' . number_format($profitMargin, 2) . '%</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="row mb-4">';
    echo '<div class="col-md-4">';
    echo '<div class="card bg-light">';
    echo '<div class="card-body text-center">';
    echo '<h6>Кількість замовлень</h6>';
    echo '<p class="fs-4">' . ($totalData['orders_count'] ?? 0) . '</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="col-md-4">';
    echo '<div class="card bg-light">';
    echo '<div class="card-body text-center">';
    echo '<h6>Середня вартість замовлення</h6>';
    echo '<p class="fs-4">' . number_format($avgOrderValue, 2) . ' грн</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="col-md-4">';
    echo '<div class="card bg-light">';
    echo '<div class="card-body text-center">';
    echo '<h6>Середня вартість клієнта</h6>';
    echo '<p class="fs-4">' . number_format($avgClientValue, 2) . ' грн</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    // Щомісячні дані
    echo '<h5 class="mt-4">Фінансові показники по місяцях</h5>';
    
    if (mysqli_num_rows($monthlyResult) > 0) {
        echo '<div class="table-responsive">';
        echo '<table class="table table-striped table-hover">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Місяць</th>';
        echo '<th>Кількість замовлень</th>';
        echo '<th>Кількість одиниць</th>';
        echo '<th>Дохід (грн)</th>';
        echo '<th>Витрати (грн)</th>';
        echo '<th>Прибуток (грн)</th>';
        echo '<th>Маржа (%)</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        while ($row = mysqli_fetch_assoc($monthlyResult)) {
            $margin = ($row['total_sales'] > 0) ? ($row['total_profit'] / $row['total_sales'] * 100) : 0;
            
            echo '<tr>';
            echo '<td>' . formatMonth($row['month']) . '</td>';
            echo '<td>' . $row['orders_count'] . '</td>';
            echo '<td>' . $row['total_quantity'] . '</td>';
            echo '<td>' . number_format($row['total_sales'], 2) . '</td>';
            echo '<td>' . number_format($row['total_cost'], 2) . '</td>';
            echo '<td>' . number_format($row['total_profit'], 2) . '</td>';
            echo '<td>' . number_format($margin, 2) . '%</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    } else {
        echo '<div class="alert alert-info">Немає даних для відображення</div>';
    }
    
    // Дані по продуктах
    echo '<h5 class="mt-4">Топ-10 продуктів за прибутком</h5>';
    
    if (mysqli_num_rows($productResult) > 0) {
        echo '<div class="table-responsive">';
        echo '<table class="table table-striped table-hover">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>ID</th>';
        echo '<th>Назва продукту</th>';
        echo '<th>Кількість</th>';
        echo '<th>Дохід (грн)</th>';
        echo '<th>Витрати (грн)</th>';
        echo '<th>Прибуток (грн)</th>';
        echo '<th>Маржа (%)</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        while ($row = mysqli_fetch_assoc($productResult)) {
            $margin = ($row['total_sales'] > 0) ? ($row['total_profit'] / $row['total_sales'] * 100) : 0;
            
            echo '<tr>';
            echo '<td>' . $row['id'] . '</td>';
            echo '<td>' . htmlspecialchars($row['nazvanie']) . '</td>';
            echo '<td>' . $row['total_quantity'] . '</td>';
            echo '<td>' . number_format($row['total_sales'], 2) . '</td>';
            echo '<td>' . number_format($row['total_cost'], 2) . '</td>';
            echo '<td>' . number_format($row['total_profit'], 2) . '</td>';
            echo '<td>' . number_format($margin, 2) . '%</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    } else {
        echo '<div class="alert alert-info">Немає даних для відображення</div>';
    }
}

/**
 * Форматування місяця
 * 
 * @param string $yearMonth Рік і місяць у форматі YYYY-MM
 * @return string Відформатований місяць
 */
function formatMonth($yearMonth) {
    $months = [
        '01' => 'Січень', '02' => 'Лютий', '03' => 'Березень',
        '04' => 'Квітень', '05' => 'Травень', '06' => 'Червень',
        '07' => 'Липень', '08' => 'Серпень', '09' => 'Вересень',
        '10' => 'Жовтень', '11' => 'Листопад', '12' => 'Грудень'
    ];
    
    $parts = explode('-', $yearMonth);
    $year = $parts[0];
    $month = $parts[1];
    
    return $months[$month] . ' ' . $year;
}

include_once '../../includes/footer.php';
?>