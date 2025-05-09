<?php
$pageTitle = 'Системний журнал';

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

// Перевірка наявності таблиці журналу
$tableExistsQuery = "SHOW TABLES LIKE 'system_log'";
$tableExistsResult = mysqli_query($connection, $tableExistsQuery);

if (mysqli_num_rows($tableExistsResult) == 0) {
    header("Location: create_system_log.php");
    exit;
}

// Очищення журналу, якщо була натиснута кнопка
if (isset($_POST['clear_log'])) {
    $clearLogQuery = "TRUNCATE TABLE system_log";
    if (mysqli_query($connection, $clearLogQuery)) {
        $success = 'Системний журнал успішно очищено';
        
        // Запис нового повідомлення в журнал
        $userId = $_SESSION['id'] ?? 0;
        $timestamp = date('Y-m-d H:i:s');
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $details = 'Системний журнал було очищено адміністратором';
        
        $insertLogQuery = "INSERT INTO system_log (action, user_id, timestamp, details, ip_address) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($connection, $insertLogQuery);
        mysqli_stmt_bind_param($stmt, "sisss", $action, $userId, $timestamp, $details, $ipAddress);
        
        $action = 'Очищення журналу';
        mysqli_stmt_execute($stmt);
    } else {
        $error = 'Помилка при очищенні системного журналу: ' . mysqli_error($connection);
    }
}

// Параметри пагінації
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Фільтри
$filterLevel = isset($_GET['level']) ? $_GET['level'] : '';
$filterAction = isset($_GET['action']) ? $_GET['action'] : '';
$filterUser = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$filterDateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$filterDateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

// Формування запиту з урахуванням фільтрів
$query = "SELECT l.*, u.name as user_name 
         FROM system_log l
         LEFT JOIN polzovateli u ON l.user_id = u.id
         WHERE 1=1";

$params = [];
$types = '';

if (!empty($filterLevel)) {
    $query .= " AND l.level = ?";
    $params[] = $filterLevel;
    $types .= 's';
}

if (!empty($filterAction)) {
    $query .= " AND l.action = ?";
    $params[] = $filterAction;
    $types .= 's';
}

if ($filterUser > 0) {
    $query .= " AND l.user_id = ?";
    $params[] = $filterUser;
    $types .= 'i';
}

if (!empty($filterDateFrom)) {
    $query .= " AND DATE(l.timestamp) >= ?";
    $params[] = $filterDateFrom;
    $types .= 's';
}

if (!empty($filterDateTo)) {
    $query .= " AND DATE(l.timestamp) <= ?";
    $params[] = $filterDateTo;
    $types .= 's';
}

if (!empty($searchTerm)) {
    $searchTerm = '%' . $searchTerm . '%';
    $query .= " AND (l.action LIKE ? OR l.details LIKE ? OR u.name LIKE ?)";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'sss';
}

// Лічильник загальної кількості записів
$countQuery = "SELECT COUNT(*) as total FROM system_log l LEFT JOIN polzovateli u ON l.user_id = u.id WHERE 1=1";
$countParams = $params;
$countTypes = $types;

$countStmt = mysqli_prepare($connection, $countQuery);
if (!empty($countParams)) {
    mysqli_stmt_bind_param($countStmt, $countTypes, ...$countParams);
}
mysqli_stmt_execute($countStmt);
$countResult = mysqli_stmt_get_result($countStmt);
$totalRecords = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalRecords / $limit);

// Додавання сортування та пагінації до основного запиту
$query .= " ORDER BY l.timestamp DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = mysqli_prepare($connection, $query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Отримання унікальних дій для фільтру
$actionsQuery = "SELECT DISTINCT action FROM system_log ORDER BY action";
$actionsResult = mysqli_query($connection, $actionsQuery);

// Отримання унікальних рівнів для фільтру
$levelsQuery = "SELECT DISTINCT level FROM system_log ORDER BY level";
$levelsResult = mysqli_query($connection, $levelsQuery);

// Отримання користувачів для фільтру
$usersQuery = "SELECT DISTINCT u.id, u.name 
              FROM system_log l
              JOIN polzovateli u ON l.user_id = u.id
              ORDER BY u.name";
$usersResult = mysqli_query($connection, $usersQuery);

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
            <a class="nav-link active" href="settings.php">
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

<!-- Заголовок та інструменти -->
<div class="card mb-4">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-clipboard-list me-2"></i> Системний журнал
            </h5>
            <div>
                <a href="settings.php#logging" class="btn btn-primary">
                    <i class="fas fa-cogs me-1"></i> Налаштування журналу
                </a>
                <button type="button" class="btn btn-danger ms-2" data-bs-toggle="modal" data-bs-target="#clearLogModal">
                    <i class="fas fa-trash me-1"></i> Очистити журнал
                </button>
                <a href="export_log.php" class="btn btn-success ms-2">
                    <i class="fas fa-file-export me-1"></i> Експорт
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Фільтри -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-filter me-2"></i> Фільтри
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
            
            <div class="col-md-2">
                <select name="level" class="form-select">
                    <option value="">Всі рівні</option>
                    <?php 
                    while ($level = mysqli_fetch_assoc($levelsResult)) {
                        $selected = ($filterLevel == $level['level']) ? 'selected' : '';
                        echo '<option value="' . htmlspecialchars($level['level']) . '" ' . $selected . '>' . ucfirst(htmlspecialchars($level['level'])) . '</option>';
                    }
                    ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <select name="action" class="form-select">
                    <option value="">Всі дії</option>
                    <?php 
                    while ($action = mysqli_fetch_assoc($actionsResult)) {
                        $selected = ($filterAction == $action['action']) ? 'selected' : '';
                        echo '<option value="' . htmlspecialchars($action['action']) . '" ' . $selected . '>' . htmlspecialchars($action['action']) . '</option>';
                    }
                    ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <select name="user_id" class="form-select">
                    <option value="0">Всі користувачі</option>
                    <?php 
                    while ($user = mysqli_fetch_assoc($usersResult)) {
                        $selected = ($filterUser == $user['id']) ? 'selected' : '';
                        echo '<option value="' . $user['id'] . '" ' . $selected . '>' . htmlspecialchars($user['name']) . '</option>';
                    }
                    ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <div class="input-group">
                    <span class="input-group-text">Період</span>
                    <input type="date" class="form-control" name="date_from" placeholder="Від" value="<?php echo $filterDateFrom; ?>">
                    <input type="date" class="form-control" name="date_to" placeholder="До" value="<?php echo $filterDateTo; ?>">
                </div>
            </div>
            
            <div class="col-md-12">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter me-1"></i> Застосувати фільтри
                </button>
                <a href="system_log.php" class="btn btn-secondary ms-2">
                    <i class="fas fa-times me-1"></i> Скинути
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Таблиця журналу -->
<div class="card">
    <div class="card-body">
        <?php if (mysqli_num_rows($result) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Дата і час</th>
                            <th>Дія</th>
                            <th>Користувач</th>
                            <th>Деталі</th>
                            <th>IP-адреса</th>
                            <th>Рівень</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($log = mysqli_fetch_assoc($result)): ?>
                            <tr class="
                                <?php 
                                switch($log['level']) {
                                    case 'error':
                                        echo 'table-danger';
                                        break;
                                    case 'warning':
                                        echo 'table-warning';
                                        break;
                                    case 'debug':
                                        echo 'table-info';
                                        break;
                                    default:
                                        echo '';
                                        break;
                                }
                                ?>
                            ">
                                <td><?php echo $log['id']; ?></td>
                                <td><?php echo date('d.m.Y H:i:s', strtotime($log['timestamp'])); ?></td>
                                <td><?php echo htmlspecialchars($log['action']); ?></td>
                                <td><?php echo htmlspecialchars($log['user_name'] ?? 'Система'); ?></td>
                                <td><?php echo htmlspecialchars($log['details']); ?></td>
                                <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                <td>
                                    <?php
                                    switch($log['level']) {
                                        case 'error':
                                            echo '<span class="badge bg-danger">Помилка</span>';
                                            break;
                                        case 'warning':
                                            echo '<span class="badge bg-warning text-dark">Попередження</span>';
                                            break;
                                        case 'debug':
                                            echo '<span class="badge bg-info text-dark">Відлагодження</span>';
                                            break;
                                        default:
                                            echo '<span class="badge bg-secondary">Інформація</span>';
                                            break;
                                    }
                                    ?>
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
                        <a class="page-link" href="<?php echo ($page <= 1) ? '#' : '?page=' . ($page - 1) . '&search=' . urlencode($searchTerm) . '&level=' . urlencode($filterLevel) . '&action=' . urlencode($filterAction) . '&user_id=' . $filterUser . '&date_from=' . urlencode($filterDateFrom) . '&date_to=' . urlencode($filterDateTo); ?>" aria-label="Попередня">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    
                    <?php 
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    for ($i = $startPage; $i <= $endPage; $i++): 
                    ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($searchTerm); ?>&level=<?php echo urlencode($filterLevel); ?>&action=<?php echo urlencode($filterAction); ?>&user_id=<?php echo $filterUser; ?>&date_from=<?php echo urlencode($filterDateFrom); ?>&date_to=<?php echo urlencode($filterDateTo); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo ($page >= $totalPages) ? '#' : '?page=' . ($page + 1) . '&search=' . urlencode($searchTerm) . '&level=' . urlencode($filterLevel) . '&action=' . urlencode($filterAction) . '&user_id=' . $filterUser . '&date_from=' . urlencode($filterDateFrom) . '&date_to=' . urlencode($filterDateTo); ?>" aria-label="Наступна">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
            
            <div class="mt-3 text-muted text-center">
                Всього записів: <?php echo $totalRecords; ?> | 
                Сторінка <?php echo $page; ?> з <?php echo $totalPages; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i> Немає записів у журналі, які відповідають вказаним критеріям.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Модальне вікно підтвердження очищення журналу -->
<div class="modal fade" id="clearLogModal" tabindex="-1" aria-labelledby="clearLogModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="clearLogModalLabel">Підтвердження очищення журналу</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i> Увага! Ця дія видалить всі записи з системного журналу. Ця операція незворотна.
                </div>
                <p>Ви впевнені, що хочете очистити системний журнал?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                <form action="" method="POST">
                    <button type="submit" name="clear_log" class="btn btn-danger">Очистити журнал</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
include_once '../../includes/footer.php';
?>