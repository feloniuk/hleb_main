<?php
$pageTitle = 'Додавання нового клієнта';

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
    $name = trim($_POST['name'] ?? '');
    $fio = trim($_POST['fio'] ?? '');
    $dolj = trim($_POST['dolj'] ?? '');
    $tel = trim($_POST['tel'] ?? '');
    $mail = trim($_POST['mail'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $adres = trim($_POST['adres'] ?? '');
    $rast = floatval($_POST['rast'] ?? 0);
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

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
    } elseif (empty($password)) {
        $error = 'Будь ласка, введіть пароль';
    } elseif ($password !== $confirmPassword) {
        $error = 'Паролі не співпадають';
    } else {
        // Перевірка, чи існує клієнт з таким логіном
        $checkQuery = "SELECT COUNT(*) as count FROM klientu WHERE login = ?";
        $stmt = mysqli_prepare($connection, $checkQuery);
        mysqli_stmt_bind_param($stmt, "s", $login);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $count = mysqli_fetch_assoc($result)['count'];
        
        if ($count > 0) {
            $error = 'Клієнт з таким логіном вже існує';
        } else {
            // Додавання клієнта до бази даних
            $insertQuery = "INSERT INTO klientu (name, fio, dolj, tel, mail, city, adres, rast, login, password) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($connection, $insertQuery);
            mysqli_stmt_bind_param($stmt, "sssssssdss", $name, $fio, $dolj, $tel, $mail, $city, $adres, $rast, $login, $password);
            
            if (mysqli_stmt_execute($stmt)) {
                $clientId = mysqli_insert_id($connection);
                $success = 'Клієнта успішно додано';
                
                // Створення запису в таблиці користувачів
                $createUserQuery = "INSERT INTO polzovateli (login, password, name) VALUES (?, ?, ?)";
                $stmt = mysqli_prepare($connection, $createUserQuery);
                mysqli_stmt_bind_param($stmt, "sss", $login, $password, $name);
                mysqli_stmt_execute($stmt);
                
                // Запис в журнал
                logAction($connection, 'Додавання клієнта', 'Додано нового клієнта: ' . $name);
                
                // Якщо вибрано опцію надіслати облікові дані
                if (isset($_POST['send_credentials']) && $_POST['send_credentials'] == 1 && !empty($mail)) {
                    // Тут можна додати код для відправки листа з обліковими даними
                }
                
                // Перенаправлення на сторінку клієнтів
                header("Location: clients.php?success=" . urlencode($success));
                exit;
            } else {
                $error = 'Помилка при додаванні клієнта: ' . mysqli_error($connection);
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
            <a class="nav-link" href="video_surveillance.php">
                <i class="fas fa-video"></i> Відеоспостереження
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

<!-- Форма додавання клієнта -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-user-plus me-2"></i> Додавання нового клієнта
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
                                    <input type="text" class="form-control" id="name" name="name" required>
                                    <div class="invalid-feedback">
                                        Будь ласка, введіть назву компанії/організації
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="fio" class="form-label">ПІБ контактної особи *</label>
                                    <input type="text" class="form-control" id="fio" name="fio" required>
                                    <div class="invalid-feedback">
                                        Будь ласка, введіть ПІБ контактної особи
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="dolj" class="form-label">Посада</label>
                                    <input type="text" class="form-control" id="dolj" name="dolj">
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="tel" class="form-label">Телефон *</label>
                                    <input type="tel" class="form-control" id="tel" name="tel" placeholder="+38 (___) ___-__-__" required>
                                    <div class="invalid-feedback">
                                        Будь ласка, введіть телефон
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="mail" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="mail" name="mail" placeholder="example@example.com">
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="client_type" class="form-label">Тип клієнта</label>
                                    <select class="form-select" id="client_type" name="client_type">
                                        <option value="">-- Виберіть тип клієнта --</option>
                                        <option value="1">Ресторан/кафе</option>
                                        <option value="2">Магазин/супермаркет</option>
                                        <option value="3">Готель</option>
                                        <option value="4">Заклад громадського харчування</option>
                                        <option value="5">Інше</option>
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
                                    <input type="text" class="form-control" id="city" name="city" required>
                                    <div class="invalid-feedback">
                                        Будь ласка, введіть місто
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="adres" class="form-label">Адреса</label>
                                    <input type="text" class="form-control" id="adres" name="adres">
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="rast" class="form-label">Відстань (км)</label>
                                    <input type="number" class="form-control" id="rast" name="rast" step="0.1" min="0">
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
                                <div class="col-md-4">
                                    <label for="login" class="form-label">Логін *</label>
                                    <input type="text" class="form-control" id="login" name="login" required>
                                    <div class="invalid-feedback">
                                        Будь ласка, введіть логін
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="password" class="form-label">Пароль *</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <button class="btn btn-outline-secondary" type="button" id="toggle_password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback">
                                        Будь ласка, введіть пароль
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
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
                            </div>
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="send_credentials" name="send_credentials" value="1">
                                        <label class="form-check-label" for="send_credentials">
                                            Надіслати облікові дані електронною поштою
                                        </label>
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
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Зберегти клієнта
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Показ/приховання пароля
        document.getElementById('toggle_password').addEventListener('click', function() {
            var passField = document.getElementById('password');
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
        
        // Маска для телефону
        var telInput = document.getElementById('tel');
        
        telInput.addEventListener('input', function(e) {
            var x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,2})(\d{0,2})/);
            e.target.value = !x[2] ? x[1] : '+38 (' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '') + (x[4] ? '-' + x[4] : '');
        });
        
        // Генерація логіна з назви компанії
        document.getElementById('name').addEventListener('blur', function() {
            var login = document.getElementById('login');
            
            if (!login.value) {
                var companyName = this.value.trim();
                if (companyName) {
                    // Формування логіна: перші слова компанії в нижньому регістрі без пробілів та спеціальних символів
                    var generatedLogin = companyName.toLowerCase()
                        .replace(/[^a-zA-Zа-яА-ЯіІїЇєЄ0-9]/g, '') // Видаляємо всі символи, окрім букв та цифр
                        .substring(0, 20); // Обмежуємо до 20 символів
                    
                    login.value = generatedLogin;
                }
            }
        });
        
        // Генерація пароля
        document.getElementById('generate_password').addEventListener('click', function() {
            var passwordField = document.getElementById('password');
            var confirmField = document.getElementById('confirm_password');
            
            // Функція для генерації випадкового пароля
            function generatePassword(length = 8) {
                var charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+";
                var password = "";
                
                for (var i = 0; i < length; i++) {
                    password += charset.charAt(Math.floor(Math.random() * charset.length));
                }
                
                return password;
            }
            
            var newPassword = generatePassword(10);
            passwordField.value = newPassword;
            confirmField.value = newPassword;
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