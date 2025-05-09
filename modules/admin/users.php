<?php
$pageTitle = 'Управління користувачами';

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

// Обробка видалення користувача
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $userId = $_GET['delete'];
    
    // Перевірка, чи не намагаємось видалити адміна
    if ($userId == 3) { // ID адміністратора
        $error = 'Неможливо видалити основного адміністратора системи';
    } else {
        $deleteQuery = "DELETE FROM polzovateli WHERE id = ?";
        $stmt = mysqli_prepare($connection, $deleteQuery);
        mysqli_stmt_bind_param($stmt, "i", $userId);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = 'Користувача успішно видалено.';
            
            // Запис в журнал (якщо такий існує)
            logAction($connection, 'Видалення користувача', 'Видалено користувача ID: ' . $userId);
        } else {
            $error = 'Помилка при видаленні користувача: ' . mysqli_error($connection);
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Отримання списку користувачів
$usersQuery = "SELECT * FROM polzovateli ORDER BY id";
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
            <a class="nav-link active" href="users.php">
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
                    <i class="fas fa-users me-2"></i> Управління користувачами системи
                </h5>
            </div>
            <div class="col-md-6 text-end">
                <a href="user_add.php" class="btn btn-success">
                    <i class="fas fa-user-plus me-1"></i> Додати нового користувача
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Таблиця користувачів -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Логін</th>
                        <th>Ім'я</th>
                        <th>Роль</th>
                        <th>Дії</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($usersResult) > 0): ?>
                        <?php while ($user = mysqli_fetch_assoc($usersResult)): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['login']); ?></td>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td>
                                    <?php
                                    switch ($user['id']) {
                                        case 1:
                                            echo '<span class="badge bg-info">Менеджер</span>';
                                            break;
                                        case 2:
                                            echo '<span class="badge bg-warning">Бригадир</span>';
                                            break;
                                        case 3:
                                            echo '<span class="badge bg-danger">Адміністратор</span>';
                                            break;
                                        default:
                                            echo '<span class="badge bg-secondary">Користувач</span>';
                                            break;
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="user_edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning" data-bs-toggle="tooltip" title="Редагувати">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="user_password.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Змінити пароль">
                                            <i class="fas fa-key"></i>
                                        </a>
                                        <?php if ($user['id'] != 3): // Не дозволяємо видаляти адміністратора ?>
                                            <a href="users.php?delete=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" data-bs-toggle="tooltip" title="Видалити" onclick="return confirm('Ви впевнені, що хочете видалити цього користувача?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">Користувачів не знайдено</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Опис ролей -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-info-circle me-2"></i> Опис ролей системи
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 mb-3">
                <div class="card bg-light h-100">
                    <div class="card-body">
                        <h5 class="card-title text-danger">Адміністратор</h5>
                        <p class="card-text">Повний доступ до всіх функцій системи, управління користувачами, налаштування системи, резервне копіювання даних.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card bg-light h-100">
                    <div class="card-body">
                        <h5 class="card-title text-info">Менеджер</h5>
                        <p class="card-text">Управління клієнтами, продукцією, обробка замовлень, формування звітів, комунікація з клієнтами.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card bg-light h-100">
                    <div class="card-body">
                        <h5 class="card-title text-warning">Бригадир</h5>
                        <p class="card-text">Управління виробництвом, організація робочих змін, контроль якості, облік продукції через сканер штрих-кодів.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card bg-light h-100">
                    <div class="card-body">
                        <h5 class="card-title text-secondary">Клієнт</h5>
                        <p class="card-text">Перегляд каталогу продукції, створення замовлень, перегляд історії замовлень, оновлення профілю.</p>
                    </div>
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