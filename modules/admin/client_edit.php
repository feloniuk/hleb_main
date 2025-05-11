<?php
$pageTitle = 'Редагування клієнта';

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

// Перевірка ID клієнта
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: clients.php");
    exit;
}

$clientId = intval($_GET['id']);

// Отримання даних клієнта
$query = "SELECT * FROM klientu WHERE id = ?";
$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, "i", $clientId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) != 1) {
    header("Location: clients.php");
    exit;
}

$client = mysqli_fetch_assoc($result);

// Обробка форми
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Отримання даних з форми
    $name = trim($_POST['name'] ?? '');
    $fio = trim($_POST['fio'] ?? '');
    $dolj = trim($_POST['dolj'] ?? '');
    $tel = trim($_POST['tel'] ?? '');
    $mail = trim($_POST['mail'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $adres = trim($_POST['adres'] ?? '');
    $rast = floatval($_POST['rast'] ?? 0);
    $login = trim($_POST['login'] ?? '');

    // Валідація даних
    if (empty($name)) {
        $error = 'Будь ласка, введіть назву компанії/організації';
    } elseif (empty($fio)) {
        $error = 'Будь ласка, введіть ПІБ контактної особи';
    } elseif (empty($tel)) {
        $error = 'Будь ласка, введіть телефон';
    } elseif (empty($city)) {
        $error = 'Будь ласка, введіть місто';
    } elseif (empty($login)) {
        $error = 'Будь ласка, введіть логін';
    } else {
        // Перевірка, чи змінився логін і чи не існує вже клієнт з таким логіном
        if ($login != $client['login']) {
            $checkQuery = "SELECT COUNT(*) as count FROM klientu WHERE login = ? AND id != ?";
            $stmt = mysqli_prepare($connection, $checkQuery);
            mysqli_stmt_bind_param($stmt, "si", $login, $clientId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $count = mysqli_fetch_assoc($result)['count'];
            
            if ($count > 0) {
                $error = 'Клієнт з таким логіном вже існує';
            }
        }
        
        if (empty($error)) {
            // Оновлення даних клієнта
            $updateQuery = "UPDATE klientu SET name = ?, fio = ?, dolj = ?, tel = ?, mail = ?, city = ?, adres = ?, rast = ?, login = ? WHERE id = ?";
            $stmt = mysqli_prepare($connection, $updateQuery);
            mysqli_stmt_bind_param($stmt, "sssssssdssi", $name, $fio, $dolj, $tel, $mail, $city, $adres, $rast, $login, $clientId);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = 'Дані клієнта успішно оновлено';
                
                // Оновлення запису в таблиці користувачів, якщо є
                $checkUserQuery = "SELECT id FROM polzovateli WHERE login = ?";
                $stmt = mysqli_prepare($connection, $checkUserQuery);
                mysqli_stmt_bind_param($stmt, "s", $client['login']);
                mysqli_stmt_execute($stmt);
                $userResult = mysqli_stmt_get_result($stmt);
                
                if (mysqli_num_rows($userResult) > 0) {
                    $userId = mysqli_fetch_assoc($userResult)['id'];
                    
                    $updateUserQuery = "UPDATE polzovateli SET login = ?, name = ? WHERE id = ?";
                    $stmt = mysqli_prepare($connection, $updateUserQuery);
                    mysqli_stmt_bind_param($stmt, "ssi", $login, $name, $userId);
                    mysqli_stmt_execute($stmt);
                }
                
                // Запис в журнал
                logAction($connection, 'Оновлення клієнта', 'Оновлено дані клієнта: ' . $name);
                
                // Оновлення даних клієнта в змінній
                $client = [
                    'id' => $clientId,
                    'name' => $name,
                    'fio' => $fio,
                    'dolj' => $dolj,
                    'tel' => $tel,
                    'mail' => $mail,
                    'city' => $city,
                    'adres' => $adres,
                    'rast' => $rast,
                    'login' => $login,
                    'password' => $client['password']
                ];
            } else {
                $error = 'Помилка при оновленні даних клієнта: ' . mysqli_error($connection);
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
            <a class="nav-link" href="users.php">
                <i class="fas fa-users"></i> Користувачі
            </a>
            <a class="nav-link active" href="clients.php">
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

<!-- Форма редагування клієнта -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-user-edit me-2"></i> Редагування клієнта: <?php echo htmlspecialchars($client['name']); ?>
        </h5>
    </div>
    <div class="card-body">
        <form action="" method="POST" class="needs-validation" novalidate>
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Загальна інформація</h6>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Назва компанії/організації *</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($client['name']); ?>" required>
                                    <div class="invalid-feedback">
                                        Будь ласка, введіть назву компанії/організації
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="fio" class="form-label">ПІБ контактної особи *</label>
                                    <input type="text" class="form-control" id="fio" name="fio" value="<?php echo htmlspecialchars($client['fio']); ?>" required>
                                    <div class="invalid-feedback">
                                        Будь ласка, введіть ПІБ контактної особи
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="dolj" class="form-label">Посада</label>
                                    <input type="text" class="form-control" id="dolj" name="dolj" value="<?php echo htmlspecialchars($client['dolj'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="tel" class="form-label">Телефон *</label>
                                    <input type="tel" class="form-control" id="tel" name="tel" value="<?php echo htmlspecialchars($client['tel']); ?>" required>
                                    <div class="invalid-feedback">
                                        Будь ласка, введіть телефон
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="mail" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="mail" name="mail" value="<?php echo htmlspecialchars($client['mail'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="client_type" class="form-label">Тип клієнта</label>
                                    <select class="form-select" id="client_type" name="client_type">
                                        <option value="">-- Виберіть тип клієнта --</option>
                                        <option value="1" <?php echo (isset($client['client_type']) && $client['client_type'] == 1) ? 'selected' : ''; ?>>Ресторан/кафе</option>
                                        <option value="2" <?php echo (isset($client['client_type']) && $client['client_type'] == 2) ? 'selected' : ''; ?>>Магазин/супермаркет</option>
                                        <option value="3" <?php echo (isset($client['client_type']) && $client['client_type'] == 3) ? 'selected' : ''; ?>>Готель</option>
                                        <option value="4" <?php echo (isset($client['client_type']) && $client['client_type'] == 4) ? 'selected' : ''; ?>>Заклад громадського харчування</option>
                                        <option value="5" <?php echo (isset($client['client_type']) && $client['client_type'] == 5) ? 'selected' : ''; ?>>Інше</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Адреса</h6>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="city" class="form-label">Місто *</label>
                                    <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($client['city']); ?>" required>
                                    <div class="invalid-feedback">
                                        Будь ласка, введіть місто
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="adres" class="form-label">Адреса</label>
                                    <input type="text" class="form-control" id="adres" name="adres" value="<?php echo htmlspecialchars($client['adres'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="rast" class="form-label">Відстань (км)</label>
                                    <input type="number" class="form-control" id="rast" name="rast" step="0.1" min="0" value="<?php echo $client['rast']; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Облікові дані</h6>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="login" class="form-label">Логін *</label>
                                    <input type="text" class="form-control" id="login" name="login" value="<?php echo htmlspecialchars($client['login']); ?>" required>
                                    <div class="invalid-feedback">
                                        Будь ласка, введіть логін
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="password" class="form-label">Пароль</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password" value="********" disabled>
                                        <a href="client_password.php?id=<?php echo $clientId; ?>" class="btn btn-info">
                                            <i class="fas fa-key me-1"></i> Змінити пароль
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="clients.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Повернутися
                </a>
                <div>
                    <a href="client_orders.php?id=<?php echo $clientId; ?>" class="btn btn-info me-2">
                        <i class="fas fa-clipboard-list me-1"></i> Замовлення клієнта
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