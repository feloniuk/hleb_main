<?php 
$pageTitle = 'Відновлення бази даних';

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

// Перевірка наявності імені файлу
if (!isset($_GET['file']) || empty($_GET['file'])) {
    header("Location: backup.php");
    exit;
}

$fileName = basename($_GET['file']);
$filePath = '../../backups/' . $fileName;

// Перевірка існування файлу
if (!file_exists($filePath)) {
    header("Location: backup.php?error=" . urlencode('Файл не знайдено'));
    exit;
}

// Перевірка розширення файлу (безпека)
$fileExt = pathinfo($filePath, PATHINFO_EXTENSION);
if ($fileExt !== 'sql') {
    header("Location: backup.php?error=" . urlencode('Невірний тип файлу'));
    exit;
}

// Підтвердження відновлення
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_restore'])) {
    // Отримання даних про з'єднання
    $host = DB_SERVER;
    $user = DB_USER;
    $password = DB_PASSWORD;
    $database = DB_NAME;
    
    // Створення команди для відновлення
    $command = "mysql --host=$host --user=$user";
    if (!empty($password)) {
        $command .= " --password=$password";
    }
    $command .= " $database < \"$filePath\"";
    
    // Виконання команди
    system($command, $returnVar);
    
    if ($returnVar === 0) {
        // Запис в журнал
        logAction($connection, 'Відновлення бази даних', 'Відновлено базу даних з резервної копії: ' . $fileName);
        
        // Перенаправлення на сторінку успіху
        header("Location: backup.php?success=" . urlencode('Базу даних успішно відновлено з резервної копії: ' . $fileName));
        exit;
    } else {
        $error = 'Помилка при відновленні бази даних';
    }
}

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

<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Підтвердження відновлення -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-undo me-2"></i> Відновлення бази даних
        </h5>
    </div>
    <div class="card-body">
        <div class="alert alert-warning mb-4">
            <i class="fas fa-exclamation-triangle me-2"></i> <strong>Увага!</strong> Ви збираєтесь відновити базу даних з резервної копії. Це призведе до заміни всіх поточних даних. Ця дія незворотна.
        </div>
        
        <p><strong>Файл резервної копії:</strong> <?php echo htmlspecialchars($fileName); ?></p>
        <p><strong>Дата створення:</strong> <?php echo date('d.m.Y H:i:s', filemtime($filePath)); ?></p>
        <p><strong>Розмір файлу:</strong> <?php echo formatFileSize(filesize($filePath)); ?></p>
        
        <form action="" method="POST" class="mt-4">
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="backup_agreement" required>
                <label class="form-check-label" for="backup_agreement">
                    Я розумію, що відновлення бази даних призведе до перезапису всіх поточних даних, і хочу продовжити
                </label>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="backup.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Скасувати
                </a>
                <button type="submit" name="confirm_restore" id="confirm_restore" class="btn btn-danger" disabled>
                    <i class="fas fa-undo me-1"></i> Відновити базу даних
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Активація кнопки при погодженні
        document.getElementById('backup_agreement').addEventListener('change', function() {
            document.getElementById('confirm_restore').disabled = !this.checked;
        });
    });
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

/**
 * Форматування розміру файлу
 * 
 * @param int $bytes Розмір у байтах
 * @return string Відформатований розмір
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

include_once '../../includes/footer.php';