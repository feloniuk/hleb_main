<?php
$pageTitle = 'Панель адміністратора';

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Перевірка доступу
if (!checkAccess(['admin'])) {
    header("Location: ../../index.php");
    exit;
}

// Отримання статистики
$connection = connectDatabase();

// Загальна кількість користувачів
$usersQuery = "SELECT COUNT(*) as total FROM polzovateli";
$usersResult = mysqli_query($connection, $usersQuery);
$usersCount = mysqli_fetch_assoc($usersResult)['total'];

// Кількість клієнтів
$clientsQuery = "SELECT COUNT(*) as total FROM klientu";
$clientsResult = mysqli_query($connection, $clientsQuery);
$clientsCount = mysqli_fetch_assoc($clientsResult)['total'];

// Загальна кількість продуктів
$productsQuery = "SELECT COUNT(*) as total FROM product";
$productsResult = mysqli_query($connection, $productsQuery);
$productsCount = mysqli_fetch_assoc($productsResult)['total'];

// Кількість замовлень
$ordersQuery = "SELECT COUNT(*) as total FROM zayavki";
$ordersResult = mysqli_query($connection, $ordersQuery);
$ordersCount = mysqli_fetch_assoc($ordersResult)['total'];

// Останні замовлення
$recentOrdersQuery = "SELECT z.idd, z.idklient, z.id, z.kol, z.data, z.doba, z.status, 
                      k.name as client_name, p.nazvanie as product_name
                      FROM zayavki z
                      JOIN klientu k ON z.idklient = k.id
                      JOIN product p ON z.id = p.id
                      ORDER BY z.data DESC, z.idd DESC LIMIT 5";
$recentOrdersResult = mysqli_query($connection, $recentOrdersQuery);

// Останні додані клієнти
$recentClientsQuery = "SELECT * FROM klientu ORDER BY id DESC LIMIT 5";
$recentClientsResult = mysqli_query($connection, $recentClientsQuery);

// Системний журнал (припустимо, він існує)
$systemLogQuery = "SELECT action, user_id, timestamp, details FROM system_log ORDER BY timestamp DESC LIMIT 10";
$systemLogResult = mysqli_query($connection, $systemLogQuery);

// Перевірка помилок в підключенні до БД
$dbError = mysqli_error($connection);

include_once '../../includes/header.php';
?>

<!-- Головне меню -->
<div class="row mb-4">
    <div class="col-md-12">
        <nav class="nav main-menu nav-pills nav-fill">
            <a class="nav-link active" href="dashboard.php">
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
            <a class="nav-link" href="reports.php">
                <i class="fas fa-chart-bar"></i> Звіти
            </a>
        </nav>
    </div>
</div>

<?php if (!empty($dbError)): ?>
    <div class="alert alert-danger" role="alert">
        <strong>Помилка підключення до бази даних:</strong> <?php echo $dbError; ?>
    </div>
<?php endif; ?>

<!-- Статистика -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center h-100">
            <div class="card-body">
                <i class="fas fa-users fa-3x mb-3 text-primary"></i>
                <h5 class="card-title">Користувачі</h5>
                <p class="card-text display-4"><?php echo $usersCount; ?></p>
                <a href="users.php" class="btn btn-primary">Управління користувачами</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-center h-100">
            <div class="card-body">
                <i class="fas fa-user-tie fa-3x mb-3 text-success"></i>
                <h5 class="card-title">Клієнти</h5>
                <p class="card-text display-4"><?php echo $clientsCount; ?></p>
                <a href="clients.php" class="btn btn-success">Управління клієнтами</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-center h-100">
            <div class="card-body">
                <i class="fas fa-bread-slice fa-3x mb-3 text-warning"></i>
                <h5 class="card-title">Продукція</h5>
                <p class="card-text display-4"><?php echo $productsCount; ?></p>
                <a href="products.php" class="btn btn-warning">Управління продукцією</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-center h-100">
            <div class="card-body">
                <i class="fas fa-clipboard-list fa-3x mb-3 text-danger"></i>
                <h5 class="card-title">Замовлення</h5>
                <p class="card-text display-4"><?php echo $ordersCount; ?></p>
                <a href="orders.php" class="btn btn-danger">Управління замовленнями</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Останні замовлення -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-history"></i> Останні замовлення
                    </h5>
                    <a href="orders.php" class="btn btn-sm btn-primary">Переглянути всі</a>
                </div>
            </div>
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
                                <th>Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($recentOrdersResult) > 0): ?>
                                <?php while ($order = mysqli_fetch_assoc($recentOrdersResult)): ?>
                                    <tr>
                                        <td><?php echo $order['idd']; ?></td>
                                        <td><?php echo htmlspecialchars($order['client_name']); ?></td>
                                        <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                        <td><?php echo $order['kol']; ?></td>
                                        <td><?php echo formatDate($order['data']); ?></td>
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
                                    <td colspan="6" class="text-center">Замовлень не знайдено</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Останні додані клієнти -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-user-plus"></i> Останні додані клієнти
                    </h5>
                    <a href="clients.php" class="btn btn-sm btn-success">Переглянути всіх</a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Назва</th>
                                <th>Контакт</th>
                                <th>Місто</th>
                                <th>Телефон</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($recentClientsResult) > 0): ?>
                                <?php while ($client = mysqli_fetch_assoc($recentClientsResult)): ?>
                                    <tr>
                                        <td><?php echo $client['id']; ?></td>
                                        <td><?php echo htmlspecialchars($client['name']); ?></td>
                                        <td><?php echo htmlspecialchars($client['fio']); ?></td>
                                        <td><?php echo htmlspecialchars($client['city']); ?></td>
                                        <td><?php echo htmlspecialchars($client['tel']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">Клієнтів не знайдено</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Швидкі дії -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-bolt"></i> Швидкі дії
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <a href="user_add.php" class="btn btn-lg btn-outline-primary w-100 h-100 d-flex flex-column justify-content-center align-items-center p-4">
                            <i class="fas fa-user-plus fa-2x mb-2"></i>
                            <span>Додати користувача</span>
                        </a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="client_add.php" class="btn btn-lg btn-outline-success w-100 h-100 d-flex flex-column justify-content-center align-items-center p-4">
                            <i class="fas fa-user-tie fa-2x mb-2"></i>
                            <span>Додати клієнта</span>
                        </a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="product_add.php" class="btn btn-lg btn-outline-warning w-100 h-100 d-flex flex-column justify-content-center align-items-center p-4">
                            <i class="fas fa-bread-slice fa-2x mb-2"></i>
                            <span>Додати продукт</span>
                        </a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="backup.php" class="btn btn-lg btn-outline-danger w-100 h-100 d-flex flex-column justify-content-center align-items-center p-4">
                            <i class="fas fa-database fa-2x mb-2"></i>
                            <span>Резервне копіювання</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Системний журнал -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-clipboard-list"></i> Системний журнал
                    </h5>
                    <a href="system_log.php" class="btn btn-sm btn-secondary">Переглянути весь журнал</a>
                </div>
            </div>
            <div class="card-body">
                <?php if (isset($systemLogResult) && mysqli_num_rows($systemLogResult) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>Дія</th>
                                    <th>Користувач</th>
                                    <th>Час</th>
                                    <th>Деталі</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($log = mysqli_fetch_assoc($systemLogResult)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($log['action']); ?></td>
                                        <td>
                                            <?php 
                                            // Отримання імені користувача
                                            $userQuery = "SELECT name FROM polzovateli WHERE id = ?";
                                            $stmt = mysqli_prepare($connection, $userQuery);
                                            mysqli_stmt_bind_param($stmt, "i", $log['user_id']);
                                            mysqli_stmt_execute($stmt);
                                            $userResult = mysqli_stmt_get_result($stmt);
                                            if ($user = mysqli_fetch_assoc($userResult)) {
                                                echo htmlspecialchars($user['name']);
                                            } else {
                                                echo 'Невідомо';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo date('d.m.Y H:i:s', strtotime($log['timestamp'])); ?></td>
                                        <td><?php echo htmlspecialchars($log['details']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i> Системний журнал порожній або таблиця не існує
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="create_system_log.php" class="btn btn-primary">
                            <i class="fas fa-plus-circle me-1"></i> Створити таблицю журналу
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Інформація про систему -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-info-circle"></i> Інформація про систему
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="mb-3">
                    <h6>Версія системи</h6>
                    <p>ТОВ "Одеський Коровай" v1.0.0</p>
                </div>
                <div class="mb-3">
                    <h6>Версія PHP</h6>
                    <p><?php echo phpversion(); ?></p>
                </div>
                <div class="mb-3">
                    <h6>Версія MySQL</h6>
                    <p><?php echo mysqli_get_server_info($connection); ?></p>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="mb-3">
                    <h6>Операційна система</h6>
                    <p><?php echo php_uname(); ?></p>
                </div>
                <div class="mb-3">
                    <h6>Сервер</h6>
                    <p><?php echo $_SERVER['SERVER_SOFTWARE']; ?></p>
                </div>
                <div class="mb-3">
                    <h6>Поточний час сервера</h6>
                    <p><?php echo date('d.m.Y H:i:s'); ?></p>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="mb-3">
                    <h6>Використання пам'яті</h6>
                    <p><?php echo round(memory_get_usage() / 1024 / 1024, 2) . ' МБ'; ?></p>
                </div>
                <div class="mb-3">
                    <h6>Максимальний розмір завантаження</h6>
                    <p><?php echo ini_get('upload_max_filesize'); ?></p>
                </div>
                <div class="mb-3">
                    <h6>Часовий пояс</h6>
                    <p><?php echo date_default_timezone_get(); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include_once '../../includes/footer.php';
?>