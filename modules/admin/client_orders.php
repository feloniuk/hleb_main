<?php
$pageTitle = 'Замовлення клієнта';

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

// Перевірка ID клієнта
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: clients.php");
    exit;
}

$clientId = intval($_GET['id']);

// Отримання даних клієнта
$query = "SELECT * FROM klientu WHERE id = ?";
$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, "i", $clientId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) != 1) {
    header("Location: clients.php");
    exit;
}

$client = mysqli_fetch_assoc($result);

// Параметри пагінації
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Фільтри
$filterProduct = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
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

// Формування запиту з урахуванням фільтрів
$query = "SELECT z.*, p.nazvanie as product_name, p.zena, (z.kol * p.zena) as total_price
          FROM zayavki z
          JOIN product p ON z.id = p.id
          WHERE z.idklient = ?";

$countQuery = "SELECT COUNT(*) as total FROM zayavki z JOIN product p ON z.id = p.id WHERE z.idklient = ?";

$params = [$clientId];
$types = 'i';

if ($filterProduct > 0) {
    $query .= " AND z.id = ?";
    $countQuery .= " AND z.id = ?";
    $params[] = $filterProduct;
    $types .= 'i';
}

if (!empty($filterStatus)) {
    $query .= " AND z.status = ?";
    $countQuery .= " AND z.status = ?";
    $params[] = $filterStatus;
    $types .= 's';
}

if (!empty($filterDoba)) {
    $query .= " AND z.doba = ?";
    $countQuery .= " AND z.doba = ?";
    $params[] = $filterDoba;
    $types .= 's';
}

if (!empty($filterDateFrom)) {
    $query .= " AND z.data >= ?";
    $countQuery .= " AND z.data >= ?";
    $params[] = $filterDateFrom;
    $types .= 's';
}

if (!empty($filterDateTo)) {
    $query .= " AND z.data <= ?";
    $countQuery .= " AND z.data <= ?";
    $params[] = $filterDateTo;
    $types .= 's';
}

// Отримання загальної кількості замовлень з урахуванням фільтрів
$countStmt = mysqli_prepare($connection, $countQuery);
mysqli_stmt_bind_param($countStmt, $types, ...$params);
mysqli_stmt_execute($countStmt);
$countResult = mysqli_stmt_get_result($countStmt);
$totalOrders = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalOrders / $limit);

// Додавання сортування та пагінації до основного запиту
$query .= " ORDER BY $sortColumn $sortOrder LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Отримання списку продуктів для фільтра
$productsQuery = "SELECT DISTINCT p.id, p.nazvanie 
                 FROM product p 
                 JOIN zayavki z ON p.id = z.id 
                 WHERE z.idklient = ?
                 ORDER BY p.nazvanie";
$stmt = mysqli_prepare($connection, $productsQuery);
mysqli_stmt_bind_param($stmt, "i", $clientId);
mysqli_stmt_execute($stmt);
$productsResult = mysqli_stmt_get_result($stmt);

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
            <a class="nav-link active" href="clients.php">
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

<!-- Заголовок та кнопки дій -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-clipboard-list me-2"></i> Замовлення клієнта: <?php echo htmlspecialchars($client['name']); ?>
            </h5>
            <div>
                <a href="order_add.php?client_id=<?php echo $clientId; ?>" class="btn btn-success">
                    <i class="fas fa-plus me-1"></i> Нове замовлення
                </a>
                <a href="client_details.php?id=<?php echo $clientId; ?>" class="btn btn-info ms-2">
                    <i class="fas fa-info-circle me-1"></i> Деталі клієнта
                </a>
                <a href="clients.php" class="btn btn-secondary ms-2">
                    <i class="fas fa-arrow-left me-1"></i> Повернутися
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Фільтри -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-filter me-2"></i> Фільтри замовлень
        </h5>
    </div>
    <div class="card-body">
        <form action="" method="GET" class="row g-3">
            <input type="hidden" name="id" value="<?php echo $clientId; ?>">
            
            <div class="col-md-3">
                <select name="product_id" class="form-select">
                    <option value="">Всі продукти</option>
                    <?php 
                    while ($product = mysqli_fetch_assoc($productsResult)) {
                        $selected = ($filterProduct == $product['id']) ? 'selected' : '';
                        echo '<option value="' . $product['id'] . '" ' . $selected . '>' . htmlspecialchars($product['nazvanie']) . '</option>';
                    }
                    ?>
                </select>
            </div>
            
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
            
            <div class="col-md-3">
                <div class="input-group">
                    <span class="input-group-text">Період</span>
                    <input type="date" class="form-control" name="date_from" value="<?php echo $filterDateFrom; ?>">
                    <input type="date" class="form-control" name="date_to" value="<?php echo $filterDateTo; ?>">
                </div>
            </div>
            
            <div class="col-md-2">
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-1"></i> Застосувати
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Таблиця замовлень -->
<div class="card">
    <div class="card-body">
        <?php if (mysqli_num_rows($result) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Продукт</th>
                            <th>Кількість</th>
                            <th>Сума (грн)</th>
                            <th>Дата</th>
                            <th>Зміна</th>
                            <th>Статус</th>
                            <th>Дії</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo $order['idd']; ?></td>
                                <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                <td><?php echo $order['kol']; ?></td>
                                <td><?php echo number_format($order['total_price'], 2); ?></td>
                                <td><?php echo formatDate($order['data']); ?></td>
                                <td>
                                    <?php if ($order['doba'] == 'денна'): ?>
                                        <span class="badge shift-badge shift-day">Денна</span>
                                    <?php else: ?>
                                        <span class="badge shift-badge shift-night">Нічна</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <?php
                                        $statusClass = '';
                                        switch ($order['status']) {
                                            case 'нове':
                                                $statusClass = 'bg-info';
                                                break;
                                            case 'в обробці':
                                                $statusClass = 'bg-warning';
                                                break;
                                            case 'виконано':
                                                $statusClass = 'bg-success';
                                                break;
                                            case 'скасовано':
                                                $statusClass = 'bg-danger';
                                                break;
                                            default:
                                                $statusClass = 'bg-secondary';
                                                break;
                                        }
                                        ?>
                                        <button class="btn btn-sm dropdown-toggle <?php echo $statusClass; ?>" type="button" id="dropdownStatus<?php echo $order['idd']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                            <?php echo ucfirst($order['status']); ?>
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="dropdownStatus<?php echo $order['idd']; ?>">
                                            <li><a class="dropdown-item" href="orders.php?id=<?php echo $order['idd']; ?>&status=нове">Нове</a></li>
                                            <li><a class="dropdown-item" href="orders.php?id=<?php echo $order['idd']; ?>&status=в обробці">В обробці</a></li>
                                            <li><a class="dropdown-item" href="orders.php?id=<?php echo $order['idd']; ?>&status=виконано">Виконано</a></li>
                                            <li><a class="dropdown-item" href="orders.php?id=<?php echo $order['idd']; ?>&status=скасовано">Скасовано</a></li>
                                        </ul>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="order_details.php?id=<?php echo $order['idd']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Перегляд">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="order_edit.php?id=<?php echo $order['idd']; ?>" class="btn btn-sm btn-warning" data-bs-toggle="tooltip" title="Редагувати">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="order_print.php?id=<?php echo $order['idd']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Друк" target="_blank">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <a href="orders.php?delete=<?php echo $order['idd']; ?>" class="btn btn-sm btn-danger" data-bs-toggle="tooltip" title="Видалити" onclick="return confirm('Ви впевнені, що хочете видалити це замовлення?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Пагінація -->
            <?php if ($totalPages > 1): ?>
            <nav aria-label="Пагінація" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo ($page <= 1) ? '#' : '?id=' . $clientId . '&page=' . ($page - 1) . '&product_id=' . $filterProduct . '&status=' . urlencode($filterStatus) . '&doba=' . urlencode($filterDoba) . '&date_from=' . urlencode($filterDateFrom) . '&date_to=' . urlencode($filterDateTo) . '&sort=' . urlencode($sortColumn) . '&order=' . urlencode($sortOrder); ?>" aria-label="Попередня">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    
                    <?php 
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    for ($i = $startPage; $i <= $endPage; $i++): 
                    ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?id=<?php echo $clientId; ?>&page=<?php echo $i; ?>&product_id=<?php echo $filterProduct; ?>&status=<?php echo urlencode($filterStatus); ?>&doba=<?php echo urlencode($filterDoba); ?>&date_from=<?php echo urlencode($filterDateFrom); ?>&date_to=<?php echo urlencode($filterDateTo); ?>&sort=<?php echo urlencode($sortColumn); ?>&order=<?php echo urlencode($sortOrder); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo ($page >= $totalPages) ? '#' : '?id=' . $clientId . '&page=' . ($page + 1) . '&product_id=' . $filterProduct . '&status=' . urlencode($filterStatus) . '&doba=' . urlencode($filterDoba) . '&date_from=' . urlencode($filterDateFrom) . '&date_to=' . urlencode($filterDateTo) . '&sort=' . urlencode($sortColumn) . '&order=' . urlencode($sortOrder); ?>" aria-label="Наступна">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
            
            <!-- Статистика замовлень -->
            <div class="row mt-4">
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h6>Загальна кількість замовлень</h6>
                            <p class="fs-4"><?php echo $totalOrders; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h6>Загальна кількість одиниць</h6>
                            <?php 
                            $totalQuantityQuery = "SELECT SUM(kol) as total_quantity FROM zayavki WHERE idklient = ?";
                            $stmt = mysqli_prepare($connection, $totalQuantityQuery);
                            mysqli_stmt_bind_param($stmt, "i", $clientId);
                            mysqli_stmt_execute($stmt);
                            $totalQuantityResult = mysqli_stmt_get_result($stmt);
                            $totalQuantity = mysqli_fetch_assoc($totalQuantityResult)['total_quantity'];
                            ?>
                            <p class="fs-4"><?php echo number_format($totalQuantity); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h6>Загальна сума замовлень</h6>
                            <?php 
                            $totalAmountQuery = "SELECT SUM(z.kol * p.zena) as total_amount 
                                                FROM zayavki z 
                                                JOIN product p ON z.id = p.id 
                                                WHERE z.idklient = ?";
                            $stmt = mysqli_prepare($connection, $totalAmountQuery);
                            mysqli_stmt_bind_param($stmt, "i", $clientId);
                            mysqli_stmt_execute($stmt);
                            $totalAmountResult = mysqli_stmt_get_result($stmt);
                            $totalAmount = mysqli_fetch_assoc($totalAmountResult)['total_amount'];
                            ?>
                            <p class="fs-4"><?php echo number_format($totalAmount, 2); ?> грн</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h6>Середній розмір замовлення</h6>
                            <?php 
                            $avgOrderAmount = ($totalOrders > 0) ? $totalAmount / $totalOrders : 0;
                            ?>
                            <p class="fs-4"><?php echo number_format($avgOrderAmount, 2); ?> грн</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info mb-0">
                <i class="fas fa-info-circle me-2"></i> Замовлень від цього клієнта не знайдено
            </div>
            
            <div class="text-center mt-3">
                <a href="order_add.php?client_id=<?php echo $clientId; ?>" class="btn btn-success">
                    <i class="fas fa-plus me-1"></i> Створити перше замовлення
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
include_once '../../includes/footer.php';
?>