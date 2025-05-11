<?php
$pageTitle = 'Зміна пароля користувача';

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

// Перевірка ID користувача
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: users.php");
    exit;
}

$userId = intval($_GET['id']);

// Отримання даних користувача
$query = "SELECT * FROM polzovateli WHERE id = ?";
$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) != 1) {
    header("Location: users.php");
    exit;
}

$user = mysqli_fetch_assoc($result);

// Перевірка, чи користувач - також клієнт
$clientQuery = "SELECT id FROM klientu WHERE login = ?";
$stmt = mysqli_prepare($connection, $clientQuery);
mysqli_stmt_bind_param($stmt, "s", $user['login']);
mysqli_stmt_execute($stmt);
$clientResult = mysqli_stmt_get_result($stmt);
$isClient = (mysqli_num_rows($clientResult) > 0);
$clientId = null;

if ($isClient) {
    $clientId = mysqli_fetch_assoc($clientResult)['id'];
}

// Обробка форми
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');
    
    // Валідація даних
    if (empty($newPassword)) {
        $error = 'Будь ласка, введіть новий пароль';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Паролі не співпадають';
    } else {
        // Оновлення пароля в таблиці користувачів
        $updateQuery = "UPDATE polzovateli SET password = ? WHERE id = ?";
        $stmt = mysqli_prepare($connection, $updateQuery);
        mysqli_stmt_bind_param($stmt, "si", $newPassword, $userId);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = 'Пароль користувача успішно змінено';
            
            // Оновлення пароля в таблиці клієнтів, якщо користувач - клієнт
            if ($isClient) {
                $clientUpdateQuery = "UPDATE klientu SET password = ? WHERE id = ?";
                $stmt = mysqli_prepare($connection, $clientUpdateQuery);
                mysqli_stmt_bind_param($stmt, "si", $newPassword, $clientId);
                mysqli_stmt_execute($stmt);
            }
            
            // Запис в журнал
            logAction($connection, 'Зміна пароля', 'Змінено пароль користувача: ' . $user['name'] . ' (' . $user['login'] . ')');
        } else {
            $error = 'Помилка при зміні пароля: ' . mysqli_error($connection);
        }
        
        mysqli_stmt_close($stmt);
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

<!-- Форма зміни пароля -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-key me-2"></i> Зміна пароля користувача: <?php echo htmlspecialchars($user['name']); ?> (<?php echo htmlspecialchars($user['login']); ?>)
        </h5>
    </div>
    <div class="card-body">
        <form action="" method="POST" class="needs-validation" novalidate>
            <div class="row mb-4">
                <div class="col-md-6 mx-auto">
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Новий пароль *</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <button class="btn btn-outline-secondary" type="button" id="toggle_password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">
                            Будь ласка, введіть новий пароль
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Підтвердження пароля *</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            <button class="btn btn-outline-secondary" type="button" id="toggle_confirm">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">
                            Будь ласка, підтвердіть пароль
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="send_credentials" name="send_credentials" value="1">
                            <label class="form-check-label" for="send_credentials">
                                Надіслати новий пароль електронною поштою
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="<?php echo $isClient ? 'clients.php' : 'users.php'; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Повернутися
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Змінити пароль
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Показ/сховання пароля
        document.getElementById('toggle_password').addEventListener('click', function() {
            var passField = document.getElementById('new_password');
            var icon = this.querySelector('i');
            
            if (passField.type === 'password') {
                passField.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passField.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        document.getElementById('toggle_confirm').addEventListener('click', function() {
            var passField = document.getElementById('confirm_password');
            var icon = this.querySelector('i');
            
            if (passField.type === 'password') {
                passField.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passField.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Перевірка співпадіння паролів
        document.getElementById('confirm_password').addEventListener('input', function() {
            var password = document.getElementById('new_password').value;
            var confirm = this.value;
            
            if (password !== confirm) {
                this.setCustomValidity('Паролі не співпадають');
            } else {
                this.setCustomValidity('');
            }
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