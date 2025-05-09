<?php
$pageTitle = 'Налаштування системи';

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

// Визначення поточних налаштувань
$settings = [];
$settingsQuery = "SHOW TABLES LIKE 'system_settings'";
$settingsResult = mysqli_query($connection, $settingsQuery);

if (mysqli_num_rows($settingsResult) > 0) {
    $getSettingsQuery = "SELECT * FROM system_settings";
    $settingsData = mysqli_query($connection, $getSettingsQuery);
    
    while ($row = mysqli_fetch_assoc($settingsData)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Обробка форми загальних налаштувань
if (isset($_POST['save_general'])) {
    $siteTitle = trim($_POST['site_title'] ?? '');
    $companyName = trim($_POST['company_name'] ?? '');
    $companyEmail = trim($_POST['company_email'] ?? '');
    $companyPhone = trim($_POST['company_phone'] ?? '');
    $companyAddress = trim($_POST['company_address'] ?? '');
    
    // Перевірка наявності таблиці налаштувань
    if (mysqli_num_rows($settingsResult) == 0) {
        // Створити таблицю, якщо не існує
        $createTableQuery = "CREATE TABLE system_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(255) NOT NULL UNIQUE,
            setting_value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        if (!mysqli_query($connection, $createTableQuery)) {
            $error = 'Помилка при створенні таблиці налаштувань: ' . mysqli_error($connection);
        }
    }
    
    if (empty($error)) {
        // Масив налаштувань для збереження
        $settingsToSave = [
            'site_title' => $siteTitle,
            'company_name' => $companyName,
            'company_email' => $companyEmail,
            'company_phone' => $companyPhone,
            'company_address' => $companyAddress
        ];
        
        // Збереження кожного налаштування
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
            
            // Оновлення масиву налаштувань
            $settings[$key] = $value;
        }
        
        if (empty($error)) {
            $success = 'Налаштування журналу успішно збережено';
            
            // Запис в журнал
            logAction($connection, 'Оновлення налаштувань', 'Оновлено налаштування системного журналу');
        }
    }
}

// Очищення журналу, якщо була натиснута кнопка
if (isset($_POST['clear_log'])) {
    $clearLogQuery = "TRUNCATE TABLE system_log";
    if (mysqli_query($connection, $clearLogQuery)) {
        $success = 'Системний журнал успішно очищено';
        
        // Запис нового повідомлення в журнал
        logAction($connection, 'Очищення журналу', 'Системний журнал було очищено адміністратором');
    } else {
        $error = 'Помилка при очищенні системного журналу: ' . mysqli_error($connection);
    }
}

// Створення резервної копії бази даних
if (isset($_POST['create_backup'])) {
    $backupDir = '../../backups';
    
    // Перевірка/створення директорії для резервних копій
    if (!file_exists($backupDir)) {
        if (!mkdir($backupDir, 0755, true)) {
            $error = 'Помилка при створенні директорії для резервних копій';
        }
    }
    
    if (empty($error)) {
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
            $success = 'Резервну копію бази даних успішно створено';
            
            // Запис в журнал
            logAction($connection, 'Створення резервної копії', 'Створено резервну копію бази даних: ' . basename($backupFile));
        } else {
            $error = 'Помилка при створенні резервної копії бази даних';
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

<!-- Вкладки налаштувань -->
<div class="card mb-4">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" id="settingsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link active" id="general-tab" data-bs-toggle="tab" href="#general" role="tab" aria-controls="general" aria-selected="true">
                    <i class="fas fa-cog me-1"></i> Загальні
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="security-tab" data-bs-toggle="tab" href="#security" role="tab" aria-controls="security" aria-selected="false">
                    <i class="fas fa-shield-alt me-1"></i> Безпека
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="logging-tab" data-bs-toggle="tab" href="#logging" role="tab" aria-controls="logging" aria-selected="false">
                    <i class="fas fa-clipboard-list me-1"></i> Журнал
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="backup-tab" data-bs-toggle="tab" href="#backup" role="tab" aria-controls="backup" aria-selected="false">
                    <i class="fas fa-database me-1"></i> Резервне копіювання
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="about-tab" data-bs-toggle="tab" href="#about" role="tab" aria-controls="about" aria-selected="false">
                    <i class="fas fa-info-circle me-1"></i> Про систему
                </a>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content" id="settingsTabsContent">
            <!-- Загальні налаштування -->
            <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                <form action="" method="POST">
                    <div class="mb-3">
                        <label for="site_title" class="form-label">Назва сайту</label>
                        <input type="text" class="form-control" id="site_title" name="site_title" value="<?php echo htmlspecialchars($settings['site_title'] ?? 'ТОВ "Одеський Коровай"'); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="company_name" class="form-label">Назва компанії</label>
                        <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo htmlspecialchars($settings['company_name'] ?? 'ТОВ "Одеський Коровай"'); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="company_email" class="form-label">Email компанії</label>
                        <input type="email" class="form-control" id="company_email" name="company_email" value="<?php echo htmlspecialchars($settings['company_email'] ?? 'info@odesskiy-korovay.com'); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="company_phone" class="form-label">Телефон компанії</label>
                        <input type="text" class="form-control" id="company_phone" name="company_phone" value="<?php echo htmlspecialchars($settings['company_phone'] ?? '+38 (048) 123-45-67'); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="company_address" class="form-label">Адреса компанії</label>
                        <textarea class="form-control" id="company_address" name="company_address" rows="2"><?php echo htmlspecialchars($settings['company_address'] ?? 'м. Одеса, вул. Пекарська, 10'); ?></textarea>
                    </div>
                    
                    <button type="submit" name="save_general" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Зберегти налаштування
                    </button>
                </form>
            </div>
            
            <!-- Налаштування безпеки -->
            <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                <form action="" method="POST">
                    <div class="mb-3">
                        <label for="password_min_length" class="form-label">Мінімальна довжина пароля</label>
                        <input type="number" class="form-control" id="password_min_length" name="password_min_length" min="6" max="20" value="<?php echo htmlspecialchars($settings['password_min_length'] ?? '8'); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="session_timeout" class="form-label">Час сесії (хвилини)</label>
                        <input type="number" class="form-control" id="session_timeout" name="session_timeout" min="5" max="240" value="<?php echo htmlspecialchars($settings['session_timeout'] ?? '30'); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="login_attempts" class="form-label">Максимальна кількість спроб входу</label>
                        <input type="number" class="form-control" id="login_attempts" name="login_attempts" min="3" max="10" value="<?php echo htmlspecialchars($settings['login_attempts'] ?? '5'); ?>">
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="two_factor" name="two_factor" <?php echo (isset($settings['two_factor']) && $settings['two_factor']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="two_factor">Увімкнути двофакторну автентифікацію</label>
                    </div>
                    
                    <button type="submit" name="save_security" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Зберегти налаштування безпеки
                    </button>
                </form>
            </div>
            
            <!-- Налаштування системного журналу -->
            <div class="tab-pane fade" id="logging" role="tabpanel" aria-labelledby="logging-tab">
                <form action="" method="POST">
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="enable_logging" name="enable_logging" <?php echo (isset($settings['enable_logging']) && $settings['enable_logging']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="enable_logging">Увімкнути системний журнал</label>
                    </div>
                    
                    <div class="mb-3">
                        <label for="log_level" class="form-label">Рівень журналювання</label>
                        <select class="form-select" id="log_level" name="log_level">
                            <option value="debug" <?php echo (isset($settings['log_level']) && $settings['log_level'] == 'debug') ? 'selected' : ''; ?>>Відлагодження (все)</option>
                            <option value="info" <?php echo (!isset($settings['log_level']) || $settings['log_level'] == 'info') ? 'selected' : ''; ?>>Інформація</option>
                            <option value="warning" <?php echo (isset($settings['log_level']) && $settings['log_level'] == 'warning') ? 'selected' : ''; ?>>Попередження</option>
                            <option value="error" <?php echo (isset($settings['log_level']) && $settings['log_level'] == 'error') ? 'selected' : ''; ?>>Помилки</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="log_retention" class="form-label">Зберігати журнал (днів)</label>
                        <input type="number" class="form-control" id="log_retention" name="log_retention" min="1" max="365" value="<?php echo htmlspecialchars($settings['log_retention'] ?? '30'); ?>">
                    </div>
                    
                    <div class="d-flex">
                        <button type="submit" name="save_log" class="btn btn-primary me-2">
                            <i class="fas fa-save me-1"></i> Зберегти налаштування журналу
                        </button>
                        
                        <button type="submit" name="clear_log" class="btn btn-danger" onclick="return confirm('Ви впевнені, що хочете очистити системний журнал? Всі записи будуть видалені!');">
                            <i class="fas fa-trash me-1"></i> Очистити журнал
                        </button>
                    </div>
                </form>
                
                <!-- Перегляд останніх записів журналу -->
                <div class="mt-4">
                    <h5>Останні записи журналу</h5>
                    <?php
                    $logQuery = "SHOW TABLES LIKE 'system_log'";
                    $logResult = mysqli_query($connection, $logQuery);
                    
                    if (mysqli_num_rows($logResult) > 0) {
                        $logEntriesQuery = "SELECT l.*, u.name as user_name 
                                          FROM system_log l
                                          LEFT JOIN polzovateli u ON l.user_id = u.id
                                          ORDER BY l.timestamp DESC
                                          LIMIT 10";
                        $logEntriesResult = mysqli_query($connection, $logEntriesQuery);
                        
                        if (mysqli_num_rows($logEntriesResult) > 0) {
                            echo '<div class="table-responsive">';
                            echo '<table class="table table-sm table-striped">';
                            echo '<thead><tr><th>Час</th><th>Користувач</th><th>Дія</th><th>Деталі</th></tr></thead>';
                            echo '<tbody>';
                            
                            while ($log = mysqli_fetch_assoc($logEntriesResult)) {
                                echo '<tr>';
                                echo '<td>' . date('d.m.Y H:i:s', strtotime($log['timestamp'])) . '</td>';
                                echo '<td>' . htmlspecialchars($log['user_name'] ?? 'Система') . '</td>';
                                echo '<td>' . htmlspecialchars($log['action']) . '</td>';
                                echo '<td>' . htmlspecialchars($log['details']) . '</td>';
                                echo '</tr>';
                            }
                            
                            echo '</tbody></table></div>';
                        } else {
                            echo '<div class="alert alert-info">У журналі немає записів</div>';
                        }
                    } else {
                        echo '<div class="alert alert-warning">Таблиця системного журналу не існує</div>';
                    }
                    ?>
                    
                    <a href="system_log.php" class="btn btn-info mt-2">
                        <i class="fas fa-list me-1"></i> Переглянути повний журнал
                    </a>
                </div>
            </div>
            
            <!-- Налаштування резервного копіювання -->
            <div class="tab-pane fade" id="backup" role="tabpanel" aria-labelledby="backup-tab">
                <form action="" method="POST">
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle me-2"></i> Резервне копіювання бази даних дозволяє зберегти всю інформацію в системі та відновити її у разі потреби.
                    </div>
                    
                    <div class="mb-3">
                        <button type="submit" name="create_backup" class="btn btn-primary">
                            <i class="fas fa-download me-1"></i> Створити резервну копію
                        </button>
                    </div>
                </form>
                
                <!-- Список існуючих резервних копій -->
                <div class="mt-4">
                    <h5>Існуючі резервні копії</h5>
                    <?php
                    $backupDir = '../../backups';
                    if (file_exists($backupDir)) {
                        $backupFiles = glob($backupDir . '/*.sql');
                        
                        if (!empty($backupFiles)) {
                            echo '<div class="table-responsive">';
                            echo '<table class="table table-sm table-striped">';
                            echo '<thead><tr><th>Назва файлу</th><th>Розмір</th><th>Дата створення</th><th>Дії</th></tr></thead>';
                            echo '<tbody>';
                            
                            // Сортування від найновішого до найстарішого
                            usort($backupFiles, function($a, $b) {
                                return filemtime($b) - filemtime($a);
                            });
                            
                            foreach ($backupFiles as $file) {
                                $fileSize = filesize($file);
                                $fileDate = date('d.m.Y H:i:s', filemtime($file));
                                $fileName = basename($file);
                                
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($fileName) . '</td>';
                                echo '<td>' . formatFileSize($fileSize) . '</td>';
                                echo '<td>' . $fileDate . '</td>';
                                echo '<td>';
                                echo '<a href="download_backup.php?file=' . urlencode($fileName) . '" class="btn btn-sm btn-primary me-1" title="Завантажити"><i class="fas fa-download"></i></a>';
                                echo '<a href="restore_backup.php?file=' . urlencode($fileName) . '" class="btn btn-sm btn-warning me-1" title="Відновити" onclick="return confirm(\'Ви впевнені, що хочете відновити базу даних з цієї резервної копії? Всі поточні дані будуть замінені!\');"><i class="fas fa-undo"></i></a>';
                                echo '<a href="delete_backup.php?file=' . urlencode($fileName) . '" class="btn btn-sm btn-danger" title="Видалити" onclick="return confirm(\'Ви впевнені, що хочете видалити цю резервну копію?\');"><i class="fas fa-trash"></i></a>';
                                echo '</td>';
                                echo '</tr>';
                            }
                            
                            echo '</tbody></table></div>';
                        } else {
                            echo '<div class="alert alert-info">Резервних копій не знайдено</div>';
                        }
                    } else {
                        echo '<div class="alert alert-warning">Директорія для резервних копій не існує</div>';
                    }
                    ?>
                </div>
            </div>
            
            <!-- Інформація про систему -->
            <div class="tab-pane fade" id="about" role="tabpanel" aria-labelledby="about-tab">
                <div class="text-center mb-4">
                    <img src="../../assets/img/logo.png" alt="ТОВ Одеський Коровай" style="max-width: 200px;">
                    <h4 class="mt-3">Система управління "Одеський Коровай"</h4>
                    <p class="text-muted">Версія 1.0.0</p>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="mb-0">Інформація про систему</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <strong>Версія PHP:</strong> <?php echo phpversion(); ?>
                                </div>
                                <div class="mb-2">
                                    <strong>Версія MySQL:</strong> <?php echo mysqli_get_server_info($connection); ?>
                                </div>
                                <div class="mb-2">
                                    <strong>Операційна система:</strong> <?php echo php_uname(); ?>
                                </div>
                                <div class="mb-2">
                                    <strong>Веб-сервер:</strong> <?php echo $_SERVER['SERVER_SOFTWARE']; ?>
                                </div>
                                <div class="mb-2">
                                    <strong>Використання пам'яті:</strong> <?php echo formatFileSize(memory_get_usage()); ?>
                                </div>
                                <div class="mb-2">
                                    <strong>Максимальний розмір завантаження:</strong> <?php echo ini_get('upload_max_filesize'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="mb-0">Статистика системи</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                // Кількість користувачів
                                $usersCountQuery = "SELECT COUNT(*) as count FROM polzovateli";
                                $usersCountResult = mysqli_query($connection, $usersCountQuery);
                                $usersCount = mysqli_fetch_assoc($usersCountResult)['count'];
                                
                                // Кількість клієнтів
                                $clientsCountQuery = "SELECT COUNT(*) as count FROM klientu";
                                $clientsCountResult = mysqli_query($connection, $clientsCountQuery);
                                $clientsCount = mysqli_fetch_assoc($clientsCountResult)['count'];
                                
                                // Кількість продуктів
                                $productsCountQuery = "SELECT COUNT(*) as count FROM product";
                                $productsCountResult = mysqli_query($connection, $productsCountQuery);
                                $productsCount = mysqli_fetch_assoc($productsCountResult)['count'];
                                
                                // Кількість замовлень
                                $ordersCountQuery = "SELECT COUNT(*) as count FROM zayavki";
                                $ordersCountResult = mysqli_query($connection, $ordersCountQuery);
                                $ordersCount = mysqli_fetch_assoc($ordersCountResult)['count'];
                                
                                // Розмір бази даних
                                $dbSizeQuery = "SELECT 
                                              SUM(data_length + index_length) as size 
                                              FROM information_schema.TABLES 
                                              WHERE table_schema = '" . DB_NAME . "'";
                                $dbSizeResult = mysqli_query($connection, $dbSizeQuery);
                                $dbSize = mysqli_fetch_assoc($dbSizeResult)['size'];
                                ?>
                                
                                <div class="mb-2">
                                    <strong>Кількість користувачів:</strong> <?php echo $usersCount; ?>
                                </div>
                                <div class="mb-2">
                                    <strong>Кількість клієнтів:</strong> <?php echo $clientsCount; ?>
                                </div>
                                <div class="mb-2">
                                    <strong>Кількість продуктів:</strong> <?php echo $productsCount; ?>
                                </div>
                                <div class="mb-2">
                                    <strong>Кількість замовлень:</strong> <?php echo $ordersCount; ?>
                                </div>
                                <div class="mb-2">
                                    <strong>Розмір бази даних:</strong> <?php echo formatFileSize($dbSize); ?>
                                </div>
                                <div class="mb-2">
                                    <strong>Дата встановлення:</strong> 01.04.2023
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Розробники</h5>
                    </div>
                    <div class="card-body">
                        <p>Система розроблена компанією <strong>TechSolutions</strong></p>
                        <p>Контакти для підтримки:</p>
                        <ul>
                            <li>Email: support@techsolutions.com</li>
                            <li>Телефон: +38 (050) 123-45-67</li>
                            <li>Веб-сайт: <a href="https://techsolutions.com" target="_blank">https://techsolutions.com</a></li>
                        </ul>
                        <p>© <?php echo date('Y'); ?> Всі права захищені</p>
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
    // Перевірка, чи увімкнено журналювання
    $loggingEnabled = isset($GLOBALS['settings']['enable_logging']) && $GLOBALS['settings']['enable_logging'] == 1;
    
    if (!$loggingEnabled) {
        return false;
    }
    
    // Перевірка, чи існує таблиця
    $tableExistsQuery = "SHOW TABLES LIKE 'system_log'";
    $tableExistsResult = mysqli_query($connection, $tableExistsQuery);
    
    if (mysqli_num_rows($tableExistsResult) == 0) {
        return false;
    }
    
    $userId = $_SESSION['id'] ?? 0;
    $timestamp = date('Y-m-d H:i:s');
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    
    $query = "INSERT INTO system_log (action, user_id, timestamp, details, ip_address) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "sisss", $action, $userId, $timestamp, $details, $ipAddress);
    
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
?>