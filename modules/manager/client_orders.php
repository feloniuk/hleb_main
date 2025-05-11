<?php
$pageTitle = 'Замовлення клієнта';

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

// Отримання ID клієнта
$clientId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($clientId <= 0) {
    $error = 'Не вказано ID клієнта';
} else {
    // Отримання даних клієнта
    $clientQuery = "SELECT * FROM klientu WHERE id = ?";
    $stmt = mysqli_prepare($connection, $clientQuery);
    mysqli_stmt_bind_param($stmt, "i", $clientId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 0) {
        $error = 'Клієнт не знайдений';
    } else {
        $client = mysqli_fetch_assoc($result);
    }
}

// Отримання фільтрів
$filterStatus = isset($_GET['status']) ? $_GET['status'] : '';
$filterDoba = isset($_GET['doba']) ? $_GET['doba'] : '';
$filterDateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$filterDateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$sortColumn = isset($_GET['sort']) ? $_GET['sort'] : 'z.data';
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// Валідація сортування
$allowedColumns = ['z.idd', 'p.nazvanie', 'z.kol', 'z.data', 'z.doba', 'z.status', 'total_price'];
if (!in_array($sortColumn, $allowedColumns)) {
    $sortColumn = 'z.data';
}

$allowedOrders = ['ASC', 'DESC'];
if (!in_array($sortOrder, $allowedOrders)) {
    $sortOrder = 'DESC';
}

// Запит для отримання замовлень клієнта
$query = "SELECT z.idd, z.id, p.nazvanie, z.kol, z.data, z.doba, z.status, (z.kol * p.zena) as total_price
         FROM zayavki z
         JOIN product p ON z.id = p.id
         WHERE z.idklient = ?";

// Додавання фільтрів до запиту
if (!empty($filterStatus)) {
    $query .= " AND z.status = ?";
}

if (!empty($filterDoba)) {
    $query .= " AND z.doba = ?";
}

if (!empty($filterDateFrom)) {
    $query .= " AND z.data >= ?";
}

if (!empty($filterDateTo)) {
    $query .= " AND z.data <= ?";
}

// Додавання сортування
$query .= " ORDER BY $sortColumn $sortOrder";

// Підготовка запиту
$stmt = mysqli_prepare($connection, $query);

// Створюємо масив для параметрів
$bindParams = [];
$bindParams[] = $clientId;

// Додаємо параметри, якщо вони є
if (!empty($filterStatus)) {
    $bindParams[] = $filterStatus;
}

if (!empty($filterDoba)) {
    $bindParams[] = $filterDoba;
}

if (!empty($filterDateFrom)) {
    $bindParams[] = $filterDateFrom;
}

if (!empty($filterDateTo)) {
    $bindParams[] = $filterDateTo;
}

// Створюємо тип параметрів
$types = "i"; // перший параметр - clientId (integer)
if (!empty($filterStatus)) $types .= "s";
if (!empty($filterDoba)) $types .= "s";
if (!empty($filterDateFrom)) $types .= "s";
if (!empty($filterDateTo)) $types .= "s";

// Прив'язуємо параметри
if (count($bindParams) > 1) {
    // Створюємо масив із реальними параметрами для bind_param
    $refParams = [];
    $refParams[] = $stmt;
    $refParams[] = $types;
    
    foreach ($bindParams as $key => $value) {
        $refParams[] = &$bindParams[$key];
    }
    
    // Викликаємо bind_param з правильними референсами
    call_user_func_array('mysqli_stmt_bind_param', $refParams);
} else {
    // Якщо тільки clientId
    mysqli_stmt_bind_param($stmt, "i", $clientId);
}

// Виконання запиту
mysqli_stmt_execute($stmt);
$ordersResult = mysqli_stmt_get_result($stmt);

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
            <a class="nav-link active" href="clients.php">
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

<?php if (isset($client)): ?>
<!-- Інформація про клієнта -->
<div class="card mb-4">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-clipboard-list me-2"></i> Замовлення клієнта: <?php echo htmlspecialchars($client['name']); ?>
            </h5>
            <div>
                <a href="client_details.php?id=<?php echo $clientId; ?>" class="btn btn-info">
                    <i class="fas fa-user me-1"></i> Деталі клієнта
                </a>
                <a href="order_add.php?client_id=<?php echo $clientId; ?>" class="btn btn-success ms-2">
                    <i class="fas fa-plus me-1"></i> Нове замовлення
                </a>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <p><strong>Компанія:</strong> <?php echo htmlspecialchars($client['name']); ?></p>
                <p><strong>Контактна особа:</strong> <?php echo htmlspecialchars($client['fio']); ?></p>
                <p><strong>Телефон:</strong> <?php echo htmlspecialchars($client['tel']); ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>Місто:</strong> <?php echo htmlspecialchars($client['city']); ?></p>
                <p><strong>Адреса:</strong> <?php echo htmlspecialchars($client['adres']); ?></p>
                <p><strong>Відстань:</strong> <?php echo $client['rast']; ?> км</p>
            </div>
        </div>
        
        <!-- Форма фільтрації замовлень -->
        <div class="card mb-4">
            <div class="card-body">
                <form action="" method="GET" class="row g-3">
                    <input type="hidden" name="id" value="<?php echo $clientId; ?>">
                    
                    <div class="col-md-2">
                        <select name="status" class="form-select">
                            <option value="">Всі статуси</option>
                            <option value="нове" <?php echo ($filterStatus == 'нове') ? 'selected' : ''; ?>>Нове</option>
                            <option value="в обробці" <?php echo ($filterStatus == 'в обробці') ? 'selected' : ''; ?>>В обробці</option>
                            <option value="виконано" <?php echo ($filterStatus == 'виконано') ? 'selected' : ''; ?>>Виконано</option>
                            <option value="скасовано" <?php echo ($filterStatus == 'скасовано') ? 'selected' : ''; ?>>Скасовано</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <select name="doba" class="form-select">
                            <option value="">Всі зміни</option>
                            <option value="денна" <?php echo ($filterDoba == 'денна') ? 'selected' : ''; ?>>Денна</option>
                            <option value="нічна" <?php echo ($filterDoba == 'нічна') ? 'selected' : ''; ?>>Нічна</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <input type="date" class="form-control" name="date_from" placeholder="Від" value="<?php echo $filterDateFrom; ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <input type="date" class="form-control" name="date_to" placeholder="До" value="<?php echo $filterDateTo; ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <select name="sort" class="form-select">
                            <option value="z.data" <?php echo ($sortColumn == 'z.data') ? 'selected' : ''; ?>>Дата</option>
                            <option value="p.nazvanie" <?php echo ($sortColumn == 'p.nazvanie') ? 'selected' : ''; ?>>Назва продукту</option>
                            <option value="z.kol" <?php echo ($sortColumn == 'z.kol') ? 'selected' : ''; ?>>Кількість</option>
                            <option value="total_price" <?php echo ($sortColumn == 'total_price') ? 'selected' : ''; ?>>Сума</option>
                        </select>
                    </div>
                    
                    <div class="col-md-1">
                        <select name="order" class="form-select">
                            <option value="DESC" <?php echo ($sortOrder == 'DESC') ? 'selected' : ''; ?>>За спаданням</option>
                            <option value="ASC" <?php echo ($sortOrder == 'ASC') ? 'selected' : ''; ?>>За зростанням</option>
                        </select>
                    </div>
                    
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Статистика замовлень клієнта -->
        <?php
        // Загальна кількість замовлень
        $totalOrdersQuery = "SELECT COUNT(*) as count FROM zayavki WHERE idklient = ?";
        $stmt = mysqli_prepare($connection, $totalOrdersQuery);
        mysqli_stmt_bind_param($stmt, "i", $clientId);
        mysqli_stmt_execute($stmt);
        $totalOrdersResult = mysqli_stmt_get_result($stmt);
        $totalOrders = mysqli_fetch_assoc($totalOrdersResult)['count'];
        
        // Загальна сума
        $totalSumQuery = "SELECT SUM(z.kol * p.zena) as total FROM zayavki z JOIN product p ON z.id = p.id WHERE z.idklient = ?";
        $stmt = mysqli_prepare($connection, $totalSumQuery);
        mysqli_stmt_bind_param($stmt, "i", $clientId);
        mysqli_stmt_execute($stmt);
        $totalSumResult = mysqli_stmt_get_result($stmt);
        $totalSum = mysqli_fetch_assoc($totalSumResult)['total'];
        
        // Кількість виконаних замовлень
        $completedOrdersQuery = "SELECT COUNT(*) as count FROM zayavki WHERE idklient = ? AND status = 'виконано'";
        $stmt = mysqli_prepare($connection, $completedOrdersQuery);
        mysqli_stmt_bind_param($stmt, "i", $clientId);
        mysqli_stmt_execute($stmt);
        $completedOrdersResult = mysqli_stmt_get_result($stmt);
        $completedOrders = mysqli_fetch_assoc($completedOrdersResult)['count'];
        
        // Найпопулярніший продукт
        $mostPopularProductQuery = "SELECT p.nazvanie, SUM(z.kol) as total_quantity 
                                   FROM zayavki z 
                                   JOIN product p ON z.id = p.id 
                                   WHERE z.idklient = ? 
                                   GROUP BY z.id 
                                   ORDER BY total_quantity DESC 
                                   LIMIT 1";
        $stmt = mysqli_prepare($connection, $mostPopularProductQuery);
        mysqli_stmt_bind_param($stmt, "i", $clientId);
        mysqli_stmt_execute($stmt);
        $mostPopularProductResult = mysqli_stmt_get_result($stmt);
        $mostPopularProduct = mysqli_num_rows($mostPopularProductResult) > 0 ? 
                            mysqli_fetch_assoc($mostPopularProductResult)['nazvanie'] : 'Немає даних';
        ?>
        
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Всього замовлень</h5>
                        <p class="display-6"><?php echo $totalOrders; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Загальна сума</h5>
                        <p class="display-6"><?php echo number_format($totalSum, 2); ?> грн</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Виконано замовлень</h5>
                        <p class="display-6"><?php echo $completedOrders; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Популярний продукт</h5>
                        <p class="fs-6"><?php echo htmlspecialchars($mostPopularProduct); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Список замовлень клієнта -->
        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Продукт</th>
                        <th>Кількість</th>
                        <th>Сума</th>
                        <th>Дата</th>
                        <th>Зміна</th>
                        <th>Статус</th>
                        <th>Дії</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($ordersResult) > 0): ?>
                        <?php while ($order = mysqli_fetch_assoc($ordersResult)): ?>
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
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="order_details.php?id=<?php echo $order['idd']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Перегляд">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="order_edit.php?id=<?php echo $order['idd']; ?>" class="btn btn-sm btn-warning" data-bs-toggle="tooltip" title="Редагувати">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="orders.php?complete=<?php echo $order['idd']; ?>" class="btn btn-sm btn-success" data-bs-toggle="tooltip" title="Відмітити як виконане" onclick="return confirm('Відмітити замовлення як виконане?');">
                                            <i class="fas fa-check"></i>
                                        </a>
                                        <a href="orders.php?cancel=<?php echo $order['idd']; ?>" class="btn btn-sm btn-danger" data-bs-toggle="tooltip" title="Скасувати" onclick="return confirm('Ви впевнені, що хочете скасувати це замовлення?');">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">Замовлень не знайдено</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer text-center">
        <a href="clients.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Повернутися до списку клієнтів
        </a>
        
        <a href="export_report.php?type=client&client_id=<?php echo $clientId; ?>&start_date=<?php echo $filterDateFrom; ?>&end_date=<?php echo $filterDateTo; ?>" class="btn btn-success ms-2">
            <i class="fas fa-file-excel me-1"></i> Експортувати в Excel
        </a>
        
        <a href="print_report.php?type=client&client_id=<?php echo $clientId; ?>&start_date=<?php echo $filterDateFrom; ?>&end_date=<?php echo $filterDateTo; ?>" class="btn btn-primary ms-2" target="_blank">
            <i class="fas fa-print me-1"></i> Друкувати
        </a>
    </div>
</div>
<?php endif; ?>

<?php
include_once '../../includes/footer.php';
?>