<?php
$pageTitle = 'Управління замовленнями';

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

// Відмітка замовлення як виконаного
if (isset($_GET['complete']) && !empty($_GET['complete'])) {
    $orderId = $_GET['complete'];
    
    $updateQuery = "UPDATE zayavki SET status = 'виконано' WHERE idd = ?";
    $stmt = mysqli_prepare($connection, $updateQuery);
    mysqli_stmt_bind_param($stmt, "i", $orderId);
    
    if (mysqli_stmt_execute($stmt)) {
        $success = 'Замовлення успішно відмічено як виконане.';
    } else {
        $error = 'Помилка при оновленні статусу замовлення: ' . mysqli_error($connection);
    }
    
    mysqli_stmt_close($stmt);
}

// Відмітка замовлення як скасованого
if (isset($_GET['cancel']) && !empty($_GET['cancel'])) {
    $orderId = $_GET['cancel'];
    
    $updateQuery = "UPDATE zayavki SET status = 'скасовано' WHERE idd = ?";
    $stmt = mysqli_prepare($connection, $updateQuery);
    mysqli_stmt_bind_param($stmt, "i", $orderId);
    
    if (mysqli_stmt_execute($stmt)) {
        $success = 'Замовлення успішно відмічено як скасоване.';
    } else {
        $error = 'Помилка при оновленні статусу замовлення: ' . mysqli_error($connection);
    }
    
    mysqli_stmt_close($stmt);
}

// Отримання списку замовлень
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$sortColumn = isset($_GET['sort']) ? $_GET['sort'] : 'z.data';
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'DESC';
$filterStatus = isset($_GET['status']) ? $_GET['status'] : '';
$filterDoba = isset($_GET['doba']) ? $_GET['doba'] : '';
$filterDate = isset($_GET['date']) ? $_GET['date'] : '';

// Валідація сортування
$allowedColumns = ['z.idd', 'k.name', 'p.nazvanie', 'z.kol', 'z.data', 'z.doba', 'z.status'];
if (!in_array($sortColumn, $allowedColumns)) {
    $sortColumn = 'z.data';
}

$allowedOrders = ['ASC', 'DESC'];
if (!in_array($sortOrder, $allowedOrders)) {
    $sortOrder = 'DESC';
}

$query = "SELECT z.idd, z.idklient, k.name as client_name, z.id, p.nazvanie as product_name, 
          z.kol, z.data, z.doba, z.status
          FROM zayavki z
          JOIN klientu k ON z.idklient = k.id
          JOIN product p ON z.id = p.id
          WHERE 1=1";

if (!empty($searchTerm)) {
    $searchTerm = '%' . $searchTerm . '%';
    $query .= " AND (k.name LIKE ? OR p.nazvanie LIKE ?)";
}

if (!empty($filterStatus)) {
    $query .= " AND z.status = ?";
}

if (!empty($filterDoba)) {
    $query .= " AND z.doba = ?";
}

if (!empty($filterDate)) {
    $query .= " AND DATE(z.data) = ?";
}

$query .= " ORDER BY $sortColumn $sortOrder";

$stmt = mysqli_prepare($connection, $query);

$bindParams = [];
$bindTypes = '';

if (!empty($searchTerm)) {
    $bindTypes .= 'ss';
    $bindParams[] = $searchTerm;
    $bindParams[] = $searchTerm;
}

if (!empty($filterStatus)) {
    $bindTypes .= 's';
    $bindParams[] = $filterStatus;
}

if (!empty($filterDoba)) {
    $bindTypes .= 's';
    $bindParams[] = $filterDoba;
}

if (!empty($filterDate)) {
    $bindTypes .= 's';
    $bindParams[] = $filterDate;
}

if (!empty($bindParams)) {
    array_unshift($bindParams, $bindTypes);
    call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt], $bindParams));
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

include_once '../../includes/header.php';
?>

<!-- Головне меню -->
<div class="row mb-4">
    <div class="col-md-12">
        <nav class="nav main-menu nav-pills nav-fill">
            <a class="nav-link" href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Головна
            </a>
            <a class="nav-link active" href="orders.php">
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
                    <i class="fas fa-clipboard-list me-2"></i> Список замовлень
                </h5>
            </div>
            <div class="col-md-6 text-end">
                <a href="order_add.php" class="btn btn-success">
                    <i class="fas fa-plus me-1"></i> Додати замовлення
                </a>
                <a href="order_export.php" class="btn btn-info ms-2">
                    <i class="fas fa-file-export me-1"></i> Експорт в Excel
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Форма пошуку та фільтрації -->
<div class="card mb-4">
    <div class="card-body">
        <form action="" method="GET" class="row g-3">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" name="search" placeholder="Пошук за клієнтом або продуктом..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                </div>
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
            
            <div class="col-md-2">
                <input type="date" class="form-control" name="date" value="<?php echo htmlspecialchars($_GET['date'] ?? ''); ?>">
            </div>
            
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100">Фільтр</button>
            </div>
            
            <div class="col-md-1">
                <a href="orders.php" class="btn btn-secondary w-100">
                    <i class="fas fa-sync-alt"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Таблиця замовлень -->
<div class="card">
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
                        <th>Статус</th>
                        <th>Дії</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($order = mysqli_fetch_assoc($result)): ?>
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
</div>

<!-- Статистика -->
<div class="card mt-4">
    <div class="card-body">
        <h5 class="mb-3">Статистика замовлень</h5>
        <div class="row">
            <div class="col-md-3">
                <div class="text-center">
                    <h6>Всього замовлень</h6>
                    <?php
                    $totalQuery = "SELECT COUNT(*) as total FROM zayavki";
                    $totalResult = mysqli_query($connection, $totalQuery);
                    $totalOrders = mysqli_fetch_assoc($totalResult)['total'];
                    ?>
                    <p class="display-6"><?php echo $totalOrders; ?></p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center">
                    <h6>Нові замовлення</h6>
                    <?php
                    $newQuery = "SELECT COUNT(*) as total FROM zayavki WHERE status = 'нове'";
                    $newResult = mysqli_query($connection, $newQuery);
                    $newOrders = mysqli_fetch_assoc($newResult)['total'];
                    ?>
                    <p class="display-6"><?php echo $newOrders; ?></p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center">
                    <h6>Виконані замовлення</h6>
                    <?php
                    $completedQuery = "SELECT COUNT(*) as total FROM zayavki WHERE status = 'виконано'";
                    $completedResult = mysqli_query($connection, $completedQuery);
                    $completedOrders = mysqli_fetch_assoc($completedResult)['total'];
                    ?>
                    <p class="display-6"><?php echo $completedOrders; ?></p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center">
                    <h6>Скасовані замовлення</h6>
                    <?php
                    $canceledQuery = "SELECT COUNT(*) as total FROM zayavki WHERE status = 'скасовано'";
                    $canceledResult = mysqli_query($connection, $canceledQuery);
                    $canceledOrders = mysqli_fetch_assoc($canceledResult)['total'];
                    ?>
                    <p class="display-6"><?php echo $canceledOrders; ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include_once '../../includes/footer.php';
?>