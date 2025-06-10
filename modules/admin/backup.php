<?php 
$pageTitle = 'Резервне копіювання бази даних';

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

// Директорія для резервних копій
$backupDir = '../../backups';

// Перевірка/створення директорії для резервних копій
if (!file_exists($backupDir)) {
    if (!mkdir($backupDir, 0755, true)) {
        $error = 'Помилка при створенні директорії для резервних копій';
    }
}

// Обробка створення резервної копії
if (isset($_POST['create_backup']) && empty($error)) {
    $backupFile = $backupDir . '/backup_' . date('Y-m-d_H-i-s') . '.sql';
    
    // Отримання даних про з'єднання
    $host = DB_SERVER;
    $user = DB_USER;
    $password = DB_PASSWORD;
    $database = DB_NAME;
    
    // Створення команди для dump
    $command = "mysqldump --opt --host=$host --user=$user";
    if (!empty($password)) {
        $command .= " --password=$password";
    }
    $command .= " $database > $backupFile";
    
    // Виконання команди
    system($command, $returnVar);
    
    if ($returnVar === 0) {
        $success = 'Резервну копію бази даних успішно створено: ' . basename($backupFile);
        
        // Запис в журнал
        logAction($connection, 'Створення резервної копії', 'Створено резервну копію бази даних: ' . basename($backupFile));
    } else {
        $error = 'Помилка при створенні резервної копії бази даних';
    }
}

// Обробка видалення резервної копії
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $fileName = basename($_GET['delete']);
    $filePath = $backupDir . '/' . $fileName;
    
    if (file_exists($filePath)) {
        if (unlink($filePath)) {
            $success = 'Резервну копію успішно видалено: ' . $fileName;
            
            // Запис в журнал
            logAction($connection, 'Видалення резервної копії', 'Видалено резервну копію бази даних: ' . $fileName);
        } else {
            $error = 'Помилка при видаленні резервної копії';
        }
    } else {
        $error = 'Файл резервної копії не знайдено';
    }
}

// Отримання списку резервних копій
$backupFiles = [];
if (file_exists($backupDir)) {
    $files = glob($backupDir . '/*.sql');
    
    foreach ($files as $file) {
        $backupFiles[] = [
            'name' => basename($file),
            'size' => filesize($file),
            'date' => filemtime($file)
        ];
    }
    
    // Сортування від найновішого до найстарішого
    usort($backupFiles, function($a, $b) {
        return $b['date'] - $a['date'];
    });
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
            <a class="nav-link" href="video_surveillance.php">
                <i class="fas fa-video"></i> Відеоспостереження
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

<!-- Інформація та створення резервної копії -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-database me-2"></i> Резервне копіювання бази даних
        </h5>
    </div>
    <div class="card-body">
        <div class="alert alert-info mb-4">
            <i class="fas fa-info-circle me-2"></i> Резервне копіювання бази даних дозволяє зберегти всю інформацію в системі та відновити її у разі потреби. Рекомендується створювати резервні копії регулярно.
        </div>
        
        <form action="" method="POST">
            <div class="mb-3">
                <button type="submit" name="create_backup" class="btn btn-primary">
                    <i class="fas fa-download me-1"></i> Створити резервну копію
                </button>
                
                <a href="settings.php#backup" class="btn btn-secondary ms-2">
                    <i class="fas fa-arrow-left me-1"></i> Повернутися до налаштувань
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Список резервних копій -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-list me-2"></i> Існуючі резервні копії
        </h5>
    </div>
    <div class="card-body">
        <?php if (!empty($backupFiles)): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Назва файлу</th>
                            <th>Розмір</th>
                            <th>Дата створення</th>
                            <th>Дії</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backupFiles as $file): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($file['name']); ?></td>
                                <td><?php echo formatFileSize($file['size']); ?></td>
                                <td><?php echo date('d.m.Y H:i:s', $file['date']); ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="download_backup.php?file=<?php echo urlencode($file['name']); ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Завантажити">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <a href="restore_backup.php?file=<?php echo urlencode($file['name']); ?>" class="btn btn-sm btn-warning" data-bs-toggle="tooltip" title="Відновити" onclick="return confirm('Ви впевнені, що хочете відновити базу даних з цієї резервної копії? Всі поточні дані будуть замінені!');">
                                            <i class="fas fa-undo"></i>
                                        </a>
                                        <a href="backup.php?delete=<?php echo urlencode($file['name']); ?>" class="btn btn-sm btn-danger" data-bs-toggle="tooltip" title="Видалити" onclick="return confirm('Ви впевнені, що хочете видалити цю резервну копію?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info mb-0">
                <i class="fas fa-info-circle me-2"></i> Резервних копій не знайдено
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