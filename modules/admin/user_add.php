<?php
$pageTitle = 'Додавання нового користувача';

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

// Обробка форми
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Отримання даних з форми
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $role = $_POST['role'] ?? '';
    
    // Валідація даних
    if (empty($login)) {
        $error = 'Будь ласка, введіть логін користувача';
    } elseif (empty($password)) {
        $error = 'Будь ласка, введіть пароль';
    } elseif ($password !== $confirmPassword) {
        $error = 'Паролі не співпадають';
    } elseif (empty($name)) {
        $error = 'Будь ласка, введіть ім\'я користувача';
    } elseif (empty($role)) {
        $error = 'Будь ласка, виберіть роль користувача';
    } else {
        // Перевірка, чи існує вже користувач з таким логіном
        $checkQuery = "SELECT COUNT(*) as count FROM polzovateli WHERE login = ?";
        $stmt = mysqli_prepare($connection, $checkQuery);
        mysqli_stmt_bind_param($stmt, "s", $login);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $count = mysqli_fetch_assoc($result)['count'];
        
        if ($count > 0) {
            $error = 'Користувач з таким логіном вже існує';
        } else {
            // Додавання користувача до бази даних
            $insertQuery = "INSERT INTO polzovateli (login, password, name) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($connection, $insertQuery);
            mysqli_stmt_bind_param($stmt, "sss", $login, $password, $name);
            
            if (mysqli_stmt_execute($stmt)) {
                $userId = mysqli_insert_id($connection);
                $success = 'Користувача успішно додано';
                
                // Запис в журнал (якщо такий існує)
                logAction($connection, 'Додавання користувача', 'Додано нового користувача: ' . $name . ' (' . $login . ')');
                
                // Якщо користувач - клієнт, створюємо запис у таблиці klientu
                if ($role === 'client') {
                    $clientQuery = "INSERT INTO klientu (name, fio, login, password) VALUES (?, ?, ?, ?)";
                    $stmt = mysqli_prepare($connection, $clientQuery);
                    mysqli_stmt_bind_param($stmt, "ssss", $name, $name, $login, $password);
                    mysqli_stmt_execute($stmt);
                }
                
                // Перенаправлення на сторінку користувачів
                header("Location: users.php?success=" . urlencode($success));
                exit;
            } else {
                $error = 'Помилка при додаванні користувача: ' . mysqli_error($connection);
            }
            
            mysqli_stmt_close($stmt);
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

<!-- Форма додавання користувача -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-user-plus me-2"></i> Додавання нового користувача
        </h5>
    </div>
    <div class="card-body">
        <form action="" method="POST" class="needs-validation" novalidate>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="login" class="form-label">Логін *</label>
                    <input type="text" class="form-control" id="login" name="login" required>
                    <div class="invalid-feedback">
                        Будь ласка, введіть логін користувача
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label for="name" class="form-label">Ім'я *</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                    <div class="invalid-feedback">
                        Будь ласка, введіть ім'я користувача
                    </div>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="password" class="form-label">Пароль *</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <div class="invalid-feedback">
                        Будь ласка, введіть пароль
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label for="confirm_password" class="form-label">Підтвердження пароля *</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    <div class="invalid-feedback">
                        Будь ласка, підтвердіть пароль
                    </div>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="role" class="form-label">Роль користувача *</label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="">-- Виберіть роль --</option>
                        <option value="admin">Адміністратор</option>
                        <option value="manager">Менеджер</option>
                        <option value="brigadir">Бригадир</option>
                        <option value="client">Клієнт</option>
                    </select>
                    <div class="invalid-feedback">
                        Будь ласка, виберіть роль користувача
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mt-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="active" name="active" value="1" checked>
                            <label class="form-check-label" for="active">
                                Активний користувач
                            </label>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="send_credentials" name="send_credentials" value="1">
                            <label class="form-check-label" for="send_credentials">
                                Надіслати облікові дані електронною поштою
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mb-3" id="client_fields" style="display: none;">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Додаткова інформація для клієнта</h6>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="tel" class="form-label">Телефон</label>
                                    <input type="tel" class="form-control" id="tel" name="tel" placeholder="+38 (___) ___-__-__">
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="mail" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="mail" name="mail" placeholder="example@example.com">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="city" class="form-label">Місто</label>
                                    <input type="text" class="form-control" id="city" name="city">
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="adres" class="form-label">Адреса</label>
                                    <input type="text" class="form-control" id="adres" name="adres">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="users.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Повернутися
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Зберегти користувача
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Показ/сховання полів для клієнта при зміні ролі
        document.getElementById('role').addEventListener('change', function() {
            var clientFields = document.getElementById('client_fields');
            
            if (this.value === 'client') {
                clientFields.style.display = 'block';
            } else {
                clientFields.style.display = 'none';
            }
        });
        
        // Перевірка співпадіння паролів
        document.getElementById('confirm_password').addEventListener('input', function() {
            var password = document.getElementById('password').value;
            var confirm = this.value;
            
            if (password !== confirm) {
                this.setCustomValidity('Паролі не співпадають');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Маска для телефону
        var telInput = document.getElementById('tel');
        
        telInput.addEventListener('input', function(e) {
            var x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,2})(\d{0,2})/);
            e.target.value = !x[2] ? x[1] : '+38 (' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '') + (x[4] ? '-' + x[4] : '');
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

include_once '../../includes/footer.php';
?>