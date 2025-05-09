<?php
$pageTitle = 'Управління клієнтами';

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

// Обробка видалення клієнта
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $clientId = $_GET['delete'];
    
    // Перевірка наявності замовлень у клієнта
    $checkOrdersQuery = "SELECT COUNT(*) as count FROM zayavki WHERE idklient = ?";
    $stmt = mysqli_prepare($connection, $checkOrdersQuery);
    mysqli_stmt_bind_param($stmt, "i", $clientId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $ordersCount = mysqli_fetch_assoc($result)['count'];
    
    if ($ordersCount > 0) {
        $error = 'Неможливо видалити клієнта, оскільки у нього є активні замовлення (' . $ordersCount . '). Будь ласка, видаліть або перенесіть замовлення перед видаленням клієнта.';
    } else {
        $deleteQuery = "DELETE FROM klientu WHERE id = ?";
        $stmt = mysqli_prepare($connection, $deleteQuery);
        mysqli_stmt_bind_param($stmt, "i", $clientId);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = 'Клієнта успішно видалено.';
            
            // Запис в журнал (якщо такий існує)
            logAction($connection, 'Видалення клієнта', 'Видалено клієнта ID: ' . $clientId);
        } else {
            $error = 'Помилка при видаленні клієнта: ' . mysqli_error($connection);
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Отримання списку клієнтів
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$sortColumn = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'ASC';

// Валідація сортування
$allowedColumns = ['id', 'name', 'fio', 'dolj', 'city', 'rast'];
if (!in_array($sortColumn, $allowedColumns)) {
    $sortColumn = 'name';
}

$allowedOrders = ['ASC', 'DESC'];
if (!in_array($sortOrder, $allowedOrders)) {
    $sortOrder = 'ASC';
}

$query = "SELECT * FROM klientu WHERE 1=1";

if (!empty($searchTerm)) {
    $searchTerm = '%' . $searchTerm . '%';
    $query .= " AND (name LIKE ? OR fio LIKE ? OR city LIKE ?)";
}

$query .= " ORDER BY $sortColumn $sortOrder";

$stmt = mysqli_prepare($connection, $query);

if (!empty($searchTerm)) {
    mysqli_stmt_bind_param($stmt, "sss", $searchTerm, $searchTerm, $searchTerm);
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

<!-- Панель управління -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="mb-0">
                    <i class="fas fa-user-tie me-2"></i> Список клієнтів
                </h5>
            </div>
            <div class="col-md-6 text-end">
                <a href="client_add.php" class="btn btn-success">
                    <i class="fas fa-user-plus me-1"></i> Додати клієнта
                </a>
                <a href="client_export.php" class="btn btn-info ms-2">
                    <i class="fas fa-file-export me-1"></i> Експорт в Excel
                </a>
                <a href="client_email.php" class="btn btn-warning ms-2">
                    <i class="fas fa-envelope me-1"></i> Розсилка
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
                    <input type="text" class="form-control" name="search" placeholder="Пошук за назвою компанії, ПІБ або містом..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                </div>
            </div>
            <div class="col-md-2">
                <select name="sort" class="form-select">
                    <option value="name" <?php echo ($sortColumn == 'name') ? 'selected' : ''; ?>>Компанія</option>
                    <option value="fio" <?php echo ($sortColumn == 'fio') ? 'selected' : ''; ?>>ПІБ</option>
                    <option value="dolj" <?php echo ($sortColumn == 'dolj') ? 'selected' : ''; ?>>Посада</option>
                    <option value="city" <?php echo ($sortColumn == 'city') ? 'selected' : ''; ?>>Місто</option>
                    <option value="rast" <?php echo ($sortColumn == 'rast') ? 'selected' : ''; ?>>Відстань</option>
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

<!-- Таблиця клієнтів -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Компанія</th>
                        <th>ПІБ</th>
                        <th>Посада</th>
                        <th>Телефон</th>
                        <th>Email</th>
                        <th>Місто</th>
                        <th>Відстань (км)</th>
                        <th>Дії</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($client = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo $client['id']; ?></td>
                                <td><?php echo htmlspecialchars($client['name']); ?></td>
                                <td><?php echo htmlspecialchars($client['fio']); ?></td>
                                <td><?php echo htmlspecialchars($client['dolj']); ?></td>
                                <td><?php echo htmlspecialchars($client['tel']); ?></td>
                                <td><?php echo htmlspecialchars($client['mail']); ?></td>
                                <td><?php echo htmlspecialchars($client['city']); ?></td>
                                <td><?php echo $client['rast']; ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="client_orders.php?id=<?php echo $client['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Замовлення">
                                            <i class="fas fa-clipboard-list"></i>
                                        </a>
                                        <a href="client_details.php?id=<?php echo $client['id']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Перегляд">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="client_edit.php?id=<?php echo $client['id']; ?>" class="btn btn-sm btn-warning" data-bs-toggle="tooltip" title="Редагувати">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="clients.php?delete=<?php echo $client['id']; ?>" class="btn btn-sm btn-danger" data-bs-toggle="tooltip" title="Видалити" onclick="return confirm('Ви впевнені, що хочете видалити цього клієнта?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center">Клієнтів не знайдено</td>
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
        <div class="row">
            <div class="col-md-3">
                <div class="text-center">
                    <h5>Загальна кількість клієнтів</h5>
                    <?php
                    $totalQuery = "SELECT COUNT(*) as total FROM klientu";
                    $totalResult = mysqli_query($connection, $totalQuery);
                    $totalClients = mysqli_fetch_assoc($totalResult)['total'];
                    ?>
                    <p class="display-6"><?php echo $totalClients; ?></p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center">
                    <h5>Середня відстань</h5>
                    <?php
                    $avgDistanceQuery = "SELECT AVG(rast) as avg_distance FROM klientu WHERE rast > 0";
                    $avgDistanceResult = mysqli_query($connection, $avgDistanceQuery);
                    $avgDistance = mysqli_fetch_assoc($avgDistanceResult)['avg_distance'];
                    ?>
                    <p class="display-6"><?php echo number_format($avgDistance, 2); ?> км</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center">
                    <h5>Найактивніший клієнт</h5>
                    <?php
                    $topClientQuery = "SELECT k.name, COUNT(z.id) as order_count 
                                     FROM klientu k 
                                     JOIN zayavki z ON k.id = z.idklient 
                                     GROUP BY k.id 
                                     ORDER BY order_count DESC 
                                     LIMIT 1";
                    $topClientResult = mysqli_query($connection, $topClientQuery);
                    if ($topClient = mysqli_fetch_assoc($topClientResult)) {
                        echo '<p class="fs-6">' . htmlspecialchars($topClient['name']) . ' (' . $topClient['order_count'] . ' замовлень)</p>';
                    } else {
                        echo '<p class="fs-6">Немає даних</p>';
                    }
                    ?>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center">
                    <h5>Клієнти за містами</h5>
                    <?php
                    $citiesQuery = "SELECT city, COUNT(*) as city_count 
                                   FROM klientu 
                                   WHERE city != ''
                                   GROUP BY city 
                                   ORDER BY city_count DESC 
                                   LIMIT 1";
                    $citiesResult = mysqli_query($connection, $citiesQuery);
                    if ($city = mysqli_fetch_assoc($citiesResult)) {
                        echo '<p class="fs-6">' . htmlspecialchars($city['city']) . ' (' . $city['city_count'] . ' клієнтів)</p>';
                    } else {
                        echo '<p class="fs-6">Немає даних</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
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