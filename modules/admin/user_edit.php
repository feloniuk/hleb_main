<?php
$pageTitle = 'Редагування користувача';

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
$userId = intval($user['id']);
// Якщо користувач - клієнт, отримати додаткову інформацію
$isClient = false;
$clientData = null;

if ($userId > 3) { // Перевіряємо, чи це не адмін, менеджер або бригадир
    $clientQuery = "SELECT * FROM klientu WHERE login = ?";
    $stmt = mysqli_prepare($connection, $clientQuery);
    mysqli_stmt_bind_param($stmt, "s", $user['login']);
    mysqli_stmt_execute($stmt);
    $clientResult = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($clientResult) > 0) {
        $isClient = true;
        $clientData = mysqli_fetch_assoc($clientResult);
    }
}

// Обробка форми
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Отримання даних з форми
    $login = trim($_POST['login'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $role = $_POST['role'] ?? '';
    $active = isset($_POST['active']) ? 1 : 0;
    
    // Валідація даних
    if (empty($login)) {
        $error = 'Будь ласка, введіть логін користувача';
    } elseif (empty($name)) {
        $error = 'Будь ласка, введіть ім\'я користувача';
    } elseif (empty($role)) {
        $error = 'Будь ласка, виберіть роль користувача';
    } else {
        // Перевірка, чи змінився логін і чи не існує вже користувач з таким логіном
        if ($login != $user['login']) {
            $checkQuery = "SELECT COUNT(*) as count FROM polzovateli WHERE login = ? AND id != ?";
            $stmt = mysqli_prepare($connection, $checkQuery);
            mysqli_stmt_bind_param($stmt, "si", $login, $userId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $count = mysqli_fetch_assoc($result)['count'];
            
            if ($count > 0) {
                $error = 'Користувач з таким логіном вже існує';
            }
        }
        
        if (empty($error)) {
            // Оновлення даних користувача
            $updateQuery = "UPDATE polzovateli SET login = ?, name = ? WHERE id = ?";
            $stmt = mysqli_prepare($connection, $updateQuery);
            mysqli_stmt_bind_param($stmt, "ssi", $login, $name, $userId);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = 'Дані користувача успішно оновлено';
                
                // Оновлення даних клієнта, якщо це клієнт
                if ($role === 'client') {
                    $tel = trim($_POST['tel'] ?? '');
                    $mail = trim($_POST['mail'] ?? '');
                    $city = trim($_POST['city'] ?? '');
                    $adres = trim($_POST['adres'] ?? '');
                    
                    if ($isClient) {
                        // Оновлення існуючого клієнта
                        $clientUpdateQuery = "UPDATE klientu SET name = ?, fio = ?, tel = ?, mail = ?, city = ?, adres = ?, login = ? WHERE id = ?";
                        $stmt = mysqli_prepare($connection, $clientUpdateQuery);
                        mysqli_stmt_bind_param($stmt, "sssssssi", $name, $name, $tel, $mail, $city, $adres, $login, $clientData['id']);
                        mysqli_stmt_execute($stmt);
                    } else {
                        // Створення нового клієнта
                        $clientInsertQuery = "INSERT INTO klientu (name, fio, tel, mail, city, adres, login, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                        $stmt = mysqli_prepare($connection, $clientInsertQuery);
                        mysqli_stmt_bind_param($stmt, "ssssssss", $name, $name, $tel, $mail, $city, $adres, $login, $user['password']);
                        mysqli_stmt_execute($stmt);
                    }
                } else if ($isClient) {
                    // Якщо користувач був клієнтом, але тепер інша роль - оновити логін в таблиці клієнтів
                    $clientUpdateQuery = "UPDATE klientu SET login = ? WHERE login = ?";
                    $stmt = mysqli_prepare($connection, $clientUpdateQuery);
                    mysqli_stmt_bind_param($stmt, "ss", $login, $user['login']);
                    mysqli_stmt_execute($stmt);
                }
                
                // Запис в журнал
                logAction($connection, 'Оновлення користувача', 'Оновлено дані користувача: ' . $name . ' (' . $login . ')');
                
                // Оновлення даних користувача в сесії
                $user = [
                    'id' => $userId,
                    'login' => $login,
                    'name' => $name
                ];
            } else {
                $error = 'Помилка при оновленні даних користувача: ' . mysqli_error($connection);
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

<!-- Форма редагування користувача -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-user-edit me-2"></i> Редагування користувача
        </h5>
    </div>
    <div class="card-body">
        <form action="" method="POST" class="needs-validation" novalidate>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="login" class="form-label">Логін *</label>
                    <input type="text" class="form-control" id="login" name="login" value="<?php echo htmlspecialchars($user['login']); ?>" required>
                    <div class="invalid-feedback">
                        Будь ласка, введіть логін користувача
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label for="name" class="form-label">Ім'я *</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    <div class="invalid-feedback">
                        Будь ласка, введіть ім'я користувача
                    </div>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="role" class="form-label">Роль користувача *</label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="">-- Виберіть роль --</option>
                        <option value="admin" <?= ($userId == 3) ? 'selected' : ''; ?>>Адміністратор</option>
                        <option value="manager" <?= ($userId == 1) ? 'selected' : ''; ?>>Менеджер</option>
                        <option value="brigadir" <?= ($userId == 2) ? 'selected' : ''; ?>>Бригадир</option>
                        <option value="client" <?= ($isClient) ? 'selected' : ''; ?>>Клієнт</option>
                    </select>
                    <div class="invalid-feedback">
                        Будь ласка, виберіть роль користувача
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mt-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="active" name="active" value="1" <?php echo (isset($user['active']) && $user['active'] == 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="active">
                                Активний користувач
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mb-3" id="client_fields" style="display: <?php echo ($isClient) ? 'block' : 'none'; ?>;">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Додаткова інформація для клієнта</h6>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="tel" class="form-label">Телефон</label>
                                    <input type="tel" class="form-control" id="tel" name="tel" placeholder="+38 (___) ___-__-__" value="<?php echo htmlspecialchars($clientData['tel'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="mail" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="mail" name="mail" placeholder="example@example.com" value="<?php echo htmlspecialchars($clientData['mail'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="city" class="form-label">Місто</label>
                                    <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($clientData['city'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="adres" class="form-label">Адреса</label>
                                    <input type="text" class="form-control" id="adres" name="adres" value="<?php echo htmlspecialchars($clientData['adres'] ?? ''); ?>">
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
                <div>
                    <a href="user_password.php?id=<?php echo $userId; ?>" class="btn btn-info me-2">
                        <i class="fas fa-key me-1"></i> Змінити пароль
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Зберегти зміни
                    </button>
                </div>
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