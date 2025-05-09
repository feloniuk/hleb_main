<?php
$pageTitle = 'Управління замовленнями';

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

// Обробка видалення замовлення
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $orderId = $_GET['delete'];
    
    $deleteQuery = "DELETE FROM zayavki WHERE idd = ?";
    $stmt = mysqli_prepare($connection, $deleteQuery);
    mysqli_stmt_bind_param($stmt, "i", $orderId);
    
    if (mysqli_stmt_execute($stmt)) {
        $success = 'Замовлення успішно видалено.';
        
        // Запис в журнал (якщо такий існує)
        logAction($connection, 'Видалення замовлення', 'Видалено замовлення ID: ' . $orderId);
    } else {
        $error = 'Помилка при видаленні замовлення: ' . mysqli_error($connection);
    }
    
    mysqli_stmt_close($stmt);
}

// Обробка оновлення статусу замовлення
if (isset($_GET['status']) && !empty($_GET['status']) && isset($_GET['id']) && !empty($_GET['id'])) {
    $orderId = $_GET['id'];
    $newStatus = $_GET['status'];
    
    $validStatuses = ['нове', 'в обробці', 'виконано', 'скасовано'];
    if (in_array($newStatus, $validStatuses)) {
        $updateQuery = "UPDATE zayavki SET status = ? WHERE idd = ?";
        $stmt = mysqli_prepare($connection, $updateQuery);
        mysqli_stmt_bind_param($stmt, "si", $newStatus, $orderId);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = 'Статус замовлення успішно оновлено.';
            
            // Запис в журнал (якщо такий існує)
            logAction($connection, 'Оновлення статусу замовлення', "Замовлення ID: $orderId, новий статус: $newStatus");
        } else {
            $error = 'Помилка при оновленні статусу замовлення: ' . mysqli_error($connection);
        }
        
        mysqli_stmt_close($stmt);
    } else {
        $error = 'Недійсний статус замовлення.';
    }
}

// Параметри фільтрації та пагінації
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$sortColumn = isset($_GET['sort']) ? $_GET['sort'] : 'z.data';
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'DESC';
$filterStatus = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';
$filterClient = isset($_GET['filter_client']) ? intval($_GET['filter_client']) : 0;
$filterProduct = isset($_GET['filter_product']) ? intval($_GET['filter_product']) : 0;
$filterDoba = isset($_GET['filter_doba']) ? $_GET['filter_doba'] : '';
$filterDateFrom = isset($_GET['filter_date_from']) ? $_GET['filter_date_from'] : '';
$filterDateTo = isset($_GET['filter_date_to']) ? $_GET['filter_date_to'] : '';

// Валідація сортування
$allowedColumns = ['z.idd', 'k.name', 'p.nazvanie', 'z.kol', 'z.data', 'z.doba', 'z.status'];
if (!in_array($sortColumn, $allowedColumns)) {
    $sortColumn = 'z.data';
}

$allowedOrders = ['ASC', 'DESC'];
if (!in_array($sortOrder, $allowedOrders)) {
    $sortOrder = 'DESC';
}

// Формування запиту з урахуванням фільтрів
$query = "SELECT z.*, k.name as client_name, p.nazvanie as product_name, (z.kol * p.zena) as total_price
          FROM zayavki z
          JOIN klientu k ON z.idklient = k.id
          JOIN product p ON z.id = p.id
          WHERE 1=1";

$countQuery = "SELECT COUNT(*) as total
               FROM zayavki z
               JOIN klientu k ON z.idklient = k.id
               JOIN product p ON z.id = p.id
               WHERE 1=1";

$params = [];
$types = '';

if (!empty($searchTerm)) {
    $searchTerm = '%' . $searchTerm . '%';
    $query .= " AND (k.name LIKE ? OR p.nazvanie LIKE ?)";
    $countQuery .= " AND (k.name LIKE ? OR p.nazvanie LIKE ?)";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ss';
}

if (!empty($filterStatus)) {
    $query .= " AND z.status = ?";
    $countQuery .= " AND z.status = ?";
    $params[] = $filterStatus;
    $types .= 's';
}

if ($filterClient > 0) {
    $query .= " AND z.idklient = ?";
    $countQuery .= " AND z.idklient = ?";
    $params[] = $filterClient;
    $types .= 'i';
}

if ($filterProduct > 0) {
    $query .= " AND z.id = ?";
    $countQuery .= " AND z.id = ?";
    $params[] = $filterProduct;
    $types .= 'i';
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

$query .= " ORDER BY $sortColumn $sortOrder LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

// Отримання загальної кількості замовлень з урахуванням фільтрів
$countStmt = mysqli_prepare($connection, $countQuery);
if (!empty($params)) {
    $countTypes = substr($types, 0, -2); // Видаляємо 'ii' для limit та offset
    $countParams = array_slice($params, 0, -2); // Видаляємо останні два параметри (limit та offset)
    
    if (!empty($countTypes)) {
        mysqli_stmt_bind_param($countStmt, $countTypes, ...$countParams);
    }
}
mysqli_stmt_execute($countStmt);
$countResult = mysqli_stmt_get_result($countStmt);
$totalOrders = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalOrders / $limit);

// Отримання списку замовлень
$stmt = mysqli_prepare($connection, $query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

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
                    <i class="fas fa-clipboard-list me-2"></i> Управління замовленнями
                </h5>
            </div>
            <div class="col-md-6 text-end">
                <a href="order_add.php" class="btn btn-success">
                    <i class="fas fa-plus me-1"></i> Нове замовлення
                </a>
                <a href="orders_export.php" class="btn btn-info ms-2">
                    <i class="fas fa-file-export me-1"></i> Експорт замовлень
                </a>
                <a href="order_statistics.php" class="btn btn-warning ms-2">
                    <i class="fas fa-chart-line me-1"></i> Статистика
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
            <div class="col-md-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" name="search" placeholder="Пошук..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                </div>
            </div>
            
            <div class="col-md-3">
                <select name="filter_client" class="form-select">
                    <option value="">Всі клієнти</option>
                    <?php 
                    while ($client = mysqli_fetch_assoc($clientsResult)) {
                        $selected = ($filterClient == $client['id']) ? 'selected' : '';
                        echo '<option value="' . $client['id'] . '" ' . $selected . '>' . htmlspecialchars($client['name']) . '</option>';
                    }
                    ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <select name="filter_product" class="form-select">
                    <option value="">Всі продукти</option>
                    <?php 
                    while ($product = mysqli_fetch_assoc($productsResult)) {
                        $selected = ($filterProduct == $product['id']) ? 'selected' : '';
                        echo '<option value="' . $product['id'] . '" ' . $selected . '>' . htmlspecialchars($product['nazvanie']) . '</option>';
                    }
                    ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <select name="filter_status" class="form-select">
                    <option value="">Всі статуси</option>
                    <option value="нове" <?php echo ($filterStatus == 'нове') ? 'selected' : ''; ?>>Нове</option>
                    <option value="в обробці" <?php echo ($filterStatus == 'в обробці') ? 'selected' : ''; ?>>В обробці</option>
                    <option value="виконано" <?php echo ($filterStatus == 'виконано') ? 'selected' : ''; ?>>Виконано</option>
                    <option value="скасовано" <?php echo ($filterStatus == 'скасовано') ? 'selected' : ''; ?>>Скасовано</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <select name="filter_doba" class="form-select">
                    <option value="">Всі зміни</option>
                    <option value="денна" <?php echo ($filterDoba == 'денна') ? 'selected' : ''; ?>>Денна</option>
                    <option value="нічна" <?php echo ($filterDoba == 'нічна') ? 'selected' : ''; ?>>Нічна</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <div class="input-group">
                    <span class="input-group-text">Від</span>
                    <input type="date" class="form-control" name="filter_date_from" value="<?php echo $filterDateFrom; ?>">
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="input-group">
                    <span class="input-group-text">До</span>
                    <input type="date" class="form-control" name="filter_date_to" value="<?php echo $filterDateTo; ?>">
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="d-flex">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter me-1"></i> Застосувати
                    </button>
                    <a href="orders.php" class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i> Скинути
                    </a>
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
                            <th>Клієнт</th>
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
                                <td><?php echo htmlspecialchars($order['client_name']); ?></td>
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
                        <a class="page-link" href="<?php echo ($page <= 1) ? '#' : '?page=' . ($page - 1) . '&search=' . urlencode($searchTerm) . '&filter_status=' . urlencode($filterStatus) . '&filter_client=' . $filterClient . '&filter_product=' . $filterProduct . '&filter_doba=' . urlencode($filterDoba) . '&filter_date_from=' . urlencode($filterDateFrom) . '&filter_date_to=' . urlencode($filterDateTo) . '&sort=' . urlencode($sortColumn) . '&order=' . urlencode($sortOrder); ?>" aria-label="Попередня">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    
                    <?php 
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    for ($i = $startPage; $i <= $endPage; $i++): 
                    ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($searchTerm); ?>&filter_status=<?php echo urlencode($filterStatus); ?>&filter_client=<?php echo $filterClient; ?>&filter_product=<?php echo $filterProduct; ?>&filter_doba=<?php echo urlencode($filterDoba); ?>&filter_date_from=<?php echo urlencode($filterDateFrom); ?>&filter_date_to=<?php echo urlencode($filterDateTo); ?>&sort=<?php echo urlencode($sortColumn); ?>&order=<?php echo urlencode($sortOrder); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo ($page >= $totalPages) ? '#' : '?page=' . ($page + 1) . '&search=' . urlencode($searchTerm) . '&filter_status=' . urlencode($filterStatus) . '&filter_client=' . $filterClient . '&filter_product=' . $filterProduct . '&filter_doba=' . urlencode($filterDoba) . '&filter_date_from=' . urlencode($filterDateFrom) . '&filter_date_to=' . urlencode($filterDateTo) . '&sort=' . urlencode($sortColumn) . '&order=' . urlencode($sortOrder); ?>" aria-label="Наступна">
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
                            $totalQuantityQuery = "SELECT SUM(kol) as total_quantity FROM zayavki";
                            $totalQuantityResult = mysqli_query($connection, $totalQuantityQuery);
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
                            $totalAmountQuery = "SELECT SUM(z.kol * p.zena) as total_amount FROM zayavki z JOIN product p ON z.id = p.id";
                            $totalAmountResult = mysqli_query($connection, $totalAmountQuery);
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
                <i class="fas fa-info-circle me-2"></i> Замовлень не знайдено за заданими критеріями
            </div>
        <?php endif; ?>
    </div>
</div>

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