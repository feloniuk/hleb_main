<?php
$pageTitle = 'Управління продукцією';

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

// Обробка видалення продукту
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $productId = $_GET['delete'];
    
    // Перевірка наявності замовлень з цим продуктом
    $checkOrdersQuery = "SELECT COUNT(*) as count FROM zayavki WHERE id = ?";
    $stmt = mysqli_prepare($connection, $checkOrdersQuery);
    mysqli_stmt_bind_param($stmt, "i", $productId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $ordersCount = mysqli_fetch_assoc($result)['count'];
    
    if ($ordersCount > 0) {
        $error = 'Неможливо видалити продукт, оскільки він використовується в замовленнях (' . $ordersCount . '). Будь ласка, видаліть або змініть замовлення перед видаленням продукту.';
    } else {
        $deleteQuery = "DELETE FROM product WHERE id = ?";
        $stmt = mysqli_prepare($connection, $deleteQuery);
        mysqli_stmt_bind_param($stmt, "i", $productId);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = 'Продукт успішно видалено.';
            
            // Запис в журнал (якщо такий існує)
            logAction($connection, 'Видалення продукту', 'Видалено продукт ID: ' . $productId);
        } else {
            $error = 'Помилка при видаленні продукту: ' . mysqli_error($connection);
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Отримання списку продукції
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$sortColumn = isset($_GET['sort']) ? $_GET['sort'] : 'nazvanie';
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'ASC';

// Валідація сортування
$allowedColumns = ['id', 'nazvanie', 'ves', 'srok', 'stoimost', 'zena'];
if (!in_array($sortColumn, $allowedColumns)) {
    $sortColumn = 'nazvanie';
}

$allowedOrders = ['ASC', 'DESC'];
if (!in_array($sortOrder, $allowedOrders)) {
    $sortOrder = 'ASC';
}

$query = "SELECT * FROM product WHERE 1=1";

if (!empty($searchTerm)) {
    $searchTerm = '%' . $searchTerm . '%';
    $query .= " AND (nazvanie LIKE ? OR id LIKE ?)";
}

$query .= " ORDER BY $sortColumn $sortOrder";

$stmt = mysqli_prepare($connection, $query);

if (!empty($searchTerm)) {
    mysqli_stmt_bind_param($stmt, "ss", $searchTerm, $searchTerm);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Отримання загальної кількості продуктів
$totalQuery = "SELECT COUNT(*) as total FROM product";
$totalResult = mysqli_query($connection, $totalQuery);
$totalProducts = mysqli_fetch_assoc($totalResult)['total'];

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
            <a class="nav-link active" href="products.php">
                <i class="fas fa-bread-slice"></i> Продукція
            </a>
            <a class="nav-link" href="orders.php">
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

<!-- Панель управління -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="mb-0">
                    <i class="fas fa-bread-slice me-2"></i> Список продукції
                </h5>
            </div>
            <div class="col-md-6 text-end">
                <a href="product_add.php" class="btn btn-success">
                    <i class="fas fa-plus me-1"></i> Додати продукт
                </a>
                <a href="product_export.php" class="btn btn-info ms-2">
                    <i class="fas fa-file-export me-1"></i> Експорт каталогу
                </a>
                <a href="product_import.php" class="btn btn-warning ms-2">
                    <i class="fas fa-file-import me-1"></i> Імпорт продукції
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Форма пошуку -->
<div class="card mb-4">
    <div class="card-body">
        <form action="" method="GET" class="row g-3">
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" name="search" placeholder="Пошук за назвою або ID..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                </div>
            </div>
            <div class="col-md-2">
                <select name="sort" class="form-select">
                    <option value="nazvanie" <?php echo ($sortColumn == 'nazvanie') ? 'selected' : ''; ?>>Назва</option>
                    <option value="id" <?php echo ($sortColumn == 'id') ? 'selected' : ''; ?>>ID</option>
                    <option value="ves" <?php echo ($sortColumn == 'ves') ? 'selected' : ''; ?>>Вага</option>
                    <option value="srok" <?php echo ($sortColumn == 'srok') ? 'selected' : ''; ?>>Строк реалізації</option>
                    <option value="stoimost" <?php echo ($sortColumn == 'stoimost') ? 'selected' : ''; ?>>Собівартість</option>
                    <option value="zena" <?php echo ($sortColumn == 'zena') ? 'selected' : ''; ?>>Ціна</option>
                </select>
            </div>
            <div class="col-md-1">
                <select name="order" class="form-select">
                    <option value="ASC" <?php echo ($sortOrder == 'ASC') ? 'selected' : ''; ?>>За зростанням</option>
                    <option value="DESC" <?php echo ($sortOrder == 'DESC') ? 'selected' : ''; ?>>За спаданням</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100">Фільтр</button>
            </div>
        </form>
    </div>
</div>

<!-- Таблиця продукції -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Зображення</th>
                        <th>Назва</th>
                        <th>Вага (кг)</th>
                        <th>Строк реалізації (год)</th>
                        <th>Собівартість (грн)</th>
                        <th>Ціна (грн)</th>
                        <th>Прибуток (грн)</th>
                        <th>Дії</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($product = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo $product['id']; ?></td>
                                <td>
                                    <?php 
                                    $imagePath = !empty($product['image']) ? '../../' . $product['image'] : '../../assets/img/product-placeholder.jpg';
                                    ?>
                                    <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($product['nazvanie']); ?>" style="width: 50px; height: 50px; object-fit: cover;" class="img-thumbnail">
                                </td>
                                <td><?php echo htmlspecialchars($product['nazvanie']); ?></td>
                                <td><?php echo $product['ves']; ?></td>
                                <td><?php echo $product['srok']; ?></td>
                                <td><?php echo number_format($product['stoimost'], 2); ?></td>
                                <td><?php echo number_format($product['zena'], 2); ?></td>
                                <td>
                                    <?php 
                                    $profit = $product['zena'] - $product['stoimost'];
                                    $profitClass = $profit > 0 ? 'text-success' : 'text-danger';
                                    echo '<span class="' . $profitClass . '">' . number_format($profit, 2) . '</span>';
                                    ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="product_details.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Перегляд">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="product_edit.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-warning" data-bs-toggle="tooltip" title="Редагувати">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="products.php?delete=<?php echo $product['id']; ?>" class="btn btn-sm btn-danger" data-bs-toggle="tooltip" title="Видалити" onclick="return confirm('Ви впевнені, що хочете видалити цей продукт?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center">Продукти не знайдено</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Статистика -->
<div class="card mt-4">
    <div class="card-body">
        <h5 class="mb-3">Статистика продукції</h5>
        <div class="row">
            <div class="col-md-3">
                <div class="text-center">
                    <h6>Кількість продуктів</h6>
                    <p class="display-6"><?php echo $totalProducts; ?></p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center">
                    <h6>Середня вага</h6>
                    <?php
                    $avgWeightQuery = "SELECT AVG(ves) as avg_weight FROM product";
                    $avgWeightResult = mysqli_query($connection, $avgWeightQuery);
                    $avgWeight = mysqli_fetch_assoc($avgWeightResult)['avg_weight'];
                    ?>
                    <p class="display-6"><?php echo number_format($avgWeight, 2); ?> кг</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center">
                    <h6>Середня ціна</h6>
                    <?php
                    $avgPriceQuery = "SELECT AVG(zena) as avg_price FROM product";
                    $avgPriceResult = mysqli_query($connection, $avgPriceQuery);
                    $avgPrice = mysqli_fetch_assoc($avgPriceResult)['avg_price'];
                    ?>
                    <p class="display-6"><?php echo number_format($avgPrice, 2); ?> грн</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center">
                    <h6>Середня собівартість</h6>
                    <?php
                    $avgCostQuery = "SELECT AVG(stoimost) as avg_cost FROM product";
                    $avgCostResult = mysqli_query($connection, $avgCostQuery);
                    $avgCost = mysqli_fetch_assoc($avgCostResult)['avg_cost'];
                    ?>
                    <p class="display-6"><?php echo number_format($avgCost, 2); ?> грн</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Графік ціни і собівартості -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-chart-bar me-2"></i> Графік ціни і собівартості
        </h5>
    </div>
    <div class="card-body">
        <div id="price-cost-chart" style="width: 100%; height: 400px;"></div>
    </div>
</div>

<!-- Завантаження Google Charts -->
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
    google.charts.load('current', {'packages':['corechart']});
    google.charts.setOnLoadCallback(drawChart);

    function drawChart() {
        var data = google.visualization.arrayToDataTable([
            ['Продукт', 'Собівартість', 'Ціна'],
            <?php 
            // Отримання даних для графіка
            mysqli_data_seek($result, 0);
            $count = 0;
            while ($product = mysqli_fetch_assoc($result)) {
                if ($count++ < 10) { // Обмеження кількості продуктів для графіка
                    echo "['" . addslashes($product['nazvanie']) . "', " . $product['stoimost'] . ", " . $product['zena'] . "],";
                }
            }
            ?>
        ]);

        var options = {
            title: 'Співвідношення ціни і собівартості продукції',
            hAxis: {title: 'Продукт'},
            vAxis: {title: 'Ціна (грн)'},
            legend: { position: 'top' },
            colors: ['#e67e22', '#3498db'],
            bar: { groupWidth: '70%' }
        };

        var chart = new google.visualization.ColumnChart(document.getElementById('price-cost-chart'));
        chart.draw(data, options);
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
?>