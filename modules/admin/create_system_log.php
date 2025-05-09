<?php
$pageTitle = 'Створення таблиці системного журналу';

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

// Перевірка, чи існує таблиця
$tableExistsQuery = "SHOW TABLES LIKE 'system_log'";
$tableExistsResult = mysqli_query($connection, $tableExistsQuery);

if (mysqli_num_rows($tableExistsResult) > 0) {
    $error = 'Таблиця системного журналу вже існує.';
} else {
    // Створення таблиці журналу
    $createTableQuery = "CREATE TABLE system_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        action VARCHAR(255) NOT NULL,
        user_id INT,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        details TEXT,
        ip_address VARCHAR(45),
        level VARCHAR(20) DEFAULT 'info'
    )";
    
    if (mysqli_query($connection, $createTableQuery)) {
        $success = 'Таблицю системного журналу успішно створено.';
        
        // Створення першого запису в журналі
        $userId = $_SESSION['id'] ?? 0;
        $timestamp = date('Y-m-d H:i:s');
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $details = 'Таблицю системного журналу створено';
        
        $insertLogQuery = "INSERT INTO system_log (action, user_id, timestamp, details, ip_address) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($connection, $insertLogQuery);
        mysqli_stmt_bind_param($stmt, "sisss", $action, $userId, $timestamp, $details, $ipAddress);
        
        $action = 'Створення системного журналу';
        mysqli_stmt_execute($stmt);
    } else {
        $error = 'Помилка при створенні таблиці системного журналу: ' . mysqli_error($connection);
    }
}

// Оновлення налаштувань для журналу
if (empty($error)) {
    // Створення таблиці налаштувань, якщо не існує
    $settingsTableExistsQuery = "SHOW TABLES LIKE 'system_settings'";
    $settingsTableExistsResult = mysqli_query($connection, $settingsTableExistsQuery);
    
    if (mysqli_num_rows($settingsTableExistsResult) == 0) {
        $createSettingsTableQuery = "CREATE TABLE system_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(255) NOT NULL UNIQUE,
            setting_value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        if (!mysqli_query($connection, $createSettingsTableQuery)) {
            $error = 'Помилка при створенні таблиці налаштувань: ' . mysqli_error($connection);
        }
    }
    
    if (empty($error)) {
        // Додавання налаштувань для журналу
        $enableLogging = 1;
        $logLevel = 'info';
        $logRetention = 30;
        
        $settingsToSave = [
            'enable_logging' => $enableLogging,
            'log_level' => $logLevel,
            'log_retention' => $logRetention
        ];
        
        foreach ($settingsToSave as $key => $value) {
            $saveQuery = "INSERT INTO system_settings (setting_key, setting_value)
                        VALUES (?, ?)
                        ON DUPLICATE KEY UPDATE setting_value = ?";
            $stmt = mysqli_prepare($connection, $saveQuery);
            mysqli_stmt_bind_param($stmt, "sss", $key, $value, $value);
            
            if (!mysqli_stmt_execute($stmt)) {
                $error = 'Помилка при збереженні налаштування "' . $key . '": ' . mysqli_error($connection);
                break;
            }
        }
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

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-database me-2"></i> Створення таблиці системного журналу
        </h5>
    </div>
    <div class="card-body">
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <p><i class="fas fa-check-circle me-2"></i> <?php echo $success; ?></p>
                <p>Тепер ви можете налаштувати параметри журналювання у розділі налаштувань системи.</p>
            </div>
            
            <a href="settings.php#logging" class="btn btn-primary">
                <i class="fas fa-cogs me-1"></i> Перейти до налаштувань журналу
            </a>
            
            <a href="system_log.php" class="btn btn-info ms-2">
                <i class="fas fa-clipboard-list me-1"></i> Переглянути журнал
            </a>
        <?php elseif (!empty($error)): ?>
            <div class="alert alert-danger">
                <p><i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?></p>
                <p>Спробуйте перевірити налаштування бази даних або зверніться до адміністратора.</p>
            </div>
            
            <a href="settings.php" class="btn btn-primary">
                <i class="fas fa-arrow-left me-1"></i> Повернутися до налаштувань
            </a>
        <?php else: ?>
            <div class="alert alert-info">
                <p><i class="fas fa-info-circle me-2"></i> Ініціалізація таблиці системного журналу...</p>
            </div>
            
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Завантаження...</span>
                </div>
                <p class="mt-2">Зачекайте, будь ласка...</p>
            </div>
            
            <script>
                // Перезавантаження сторінки для продовження процесу
                setTimeout(function() {
                    window.location.reload();
                }, 2000);
            </script>
        <?php endif; ?>
    </div>
</div>

<?php
include_once '../../includes/footer.php';
?>