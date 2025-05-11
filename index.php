<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

// Якщо користувач вже авторизований, перенаправляємо його на відповідну сторінку
if (isUserLoggedIn()) {
    $userRole = getUserRole();
    $userType = getUserType();
    
    if ($userType === 'client') {
        // Перенаправлення клієнта
        header("Location: modules/client/dashboard.php");
        exit;
    } else {
        // Перенаправлення співробітника на основі ролі
        switch ($userRole) {
            case 'manager':
                header("Location: modules/manager/dashboard.php");
                break;
            case 'brigadir':
                header("Location: modules/supervisor/dashboard.php");
                break;
            case 'admin':
                header("Location: modules/admin/dashboard.php");
                break;
            default:
                // Для невідомої ролі перенаправляємо до загальної сторінки
                header("Location: dashboard.php");
                break;
        }
        exit;
    }
}

// Обробка відправки форми
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($login) || empty($password)) {
        $error = 'Будь ласка, введіть логін та пароль';
    } else {
        $user = loginUser($login, $password);
        
        if ($user) {
            // Успішна авторизація, перенаправляємо користувача
            $userRole = getUserRole();
            $userType = getUserType();
            
            if ($userType === 'client') {
                // Перенаправлення клієнта
                header("Location: modules/client/dashboard.php");
                exit;
            } else {
                // Перенаправлення співробітника на основі ролі
                switch ($userRole) {
                    case 'manager':
                        header("Location: modules/manager/dashboard.php");
                        break;
                    case 'brigadir':
                        header("Location: modules/supervisor/dashboard.php");
                        break;
                    case 'admin':
                        header("Location: modules/admin/dashboard.php");
                        break;
                    default:
                        // Для невідомої ролі перенаправляємо до загальної сторінки
                        header("Location: dashboard.php");
                        break;
                }
                exit;
            }
        } else {
            $error = 'Невірний логін або пароль';
        }
    }
}

// Додамо функцію створення облікового запису клієнта
$registrationSuccess = false;

if (isset($_POST['register'])) {
    $name = $_POST['reg_name'] ?? '';
    $fio = $_POST['reg_fio'] ?? '';
    $tel = $_POST['reg_tel'] ?? '';
    $email = $_POST['reg_email'] ?? '';
    $city = $_POST['reg_city'] ?? '';
    $adres = $_POST['reg_adres'] ?? '';
    $login = $_POST['reg_login'] ?? '';
    $password = $_POST['reg_password'] ?? '';
    $password_confirm = $_POST['reg_password_confirm'] ?? '';
    
    // Валідація даних
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Назва організації обов'язкова";
    }
    
    if (empty($fio)) {
        $errors[] = "ПІБ контактної особи обов'язкове";
    }
    
    if (empty($tel)) {
        $errors[] = "Телефон обов'язковий";
    }
    
    if (empty($city)) {
        $errors[] = "Місто обов'язкове";
    }
    
    if (empty($adres)) {
        $errors[] = "Адреса обов'язкова";
    }
    
    if (empty($login)) {
        $errors[] = "Логін обов'язковий";
    }
    
    if (empty($password)) {
        $errors[] = "Пароль обов'язковий";
    } elseif ($password !== $password_confirm) {
        $errors[] = "Паролі не співпадають";
    } elseif (strlen($password) < 6) {
        $errors[] = "Пароль повинен бути не менше 6 символів";
    }
    
    // Перевірка чи логін вже існує
    if (empty($errors)) {
        $connection = connectDatabase();
        
        // Перевірка в таблиці polzovateli
        $checkQuery = "SELECT * FROM polzovateli WHERE login = ?";
        $stmt = mysqli_prepare($connection, $checkQuery);
        mysqli_stmt_bind_param($stmt, "s", $login);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $errors[] = "Логін вже використовується співробітником. Оберіть інший.";
        } else {
            // Перевірка в таблиці klientu
            $checkQuery = "SELECT * FROM klientu WHERE login = ?";
            $stmt = mysqli_prepare($connection, $checkQuery);
            mysqli_stmt_bind_param($stmt, "s", $login);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) > 0) {
                $errors[] = "Логін вже використовується іншим клієнтом. Оберіть інший.";
            }
        }
        
        // Якщо немає помилок, створюємо обліковий запис
        if (empty($errors)) {
            $insertQuery = "INSERT INTO klientu (name, fio, tel, mail, city, adres, login, password) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($connection, $insertQuery);
            mysqli_stmt_bind_param($stmt, "ssssssss", $name, $fio, $tel, $email, $city, $adres, $login, $password);
            
            if (mysqli_stmt_execute($stmt)) {
                $registrationSuccess = true;
                $success = "Реєстрація успішна! Тепер ви можете увійти в систему.";
            } else {
                $error = "Помилка при створенні облікового запису: " . mysqli_error($connection);
            }
        } else {
            $error = implode("<br>", $errors);
        }
        
        mysqli_close($connection);
    } else {
        $error = implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ТОВ "Одеський Коровай" - Авторизація</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            background-image: linear-gradient(rgba(255, 255, 255, 0.5), rgba(255, 255, 255, 0.5)), url('assets/img/back.jpg');
            background-repeat: no-repeat;
            background-size: cover;
            background-position: center;
        }
        
        .login-container {
            max-width: 500px;
            padding: 30px;
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        .login-logo {
            margin-bottom: 20px;
            text-align: center;
        }
        
        .login-logo img {
            width: 200px;
            height: auto;
        }
        
        .nav-tabs .nav-link {
            color: #6c757d;
        }
        
        .nav-tabs .nav-link.active {
            color: #0d6efd;
            font-weight: bold;
        }
        
        .login-form {
            margin-top: 20px;
        }
        
        .login-footer {
            margin-top: 30px;
            text-align: center;
            font-size: 0.9rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container mx-auto">
            <div class="login-logo">
                <img src="assets/img/logo.png" alt="ТОВ Одеський Коровай">
            </div>
            
            <h4 class="text-center mb-4">Система управління</h4>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <ul class="nav nav-tabs" id="authTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login-content" type="button" role="tab" aria-controls="login-content" aria-selected="true">
                        <i class="fas fa-sign-in-alt me-1"></i> Вхід
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register-content" type="button" role="tab" aria-controls="register-content" aria-selected="false">
                        <i class="fas fa-user-plus me-1"></i> Реєстрація клієнта
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="authTabsContent">
                <!-- Форма входу -->
                <div class="tab-pane fade show active" id="login-content" role="tabpanel" aria-labelledby="login-tab">
                    <div class="login-form">
                        <form method="post" action="" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="login" class="form-label">Логін</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="login" name="login" placeholder="Введіть ваш логін" required>
                                </div>
                                <div class="invalid-feedback">
                                    Будь ласка, введіть логін
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label">Пароль</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Введіть ваш пароль" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">
                                    Будь ласка, введіть пароль
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sign-in-alt me-2"></i> Увійти
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Форма реєстрації -->
                <div class="tab-pane fade" id="register-content" role="tabpanel" aria-labelledby="register-tab">
                    <div class="login-form">
                        <form method="post" action="" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="reg_name" class="form-label">Назва організації</label>
                                <input type="text" class="form-control" id="reg_name" name="reg_name" placeholder="Назва вашої організації" required>
                                <div class="invalid-feedback">
                                    Будь ласка, введіть назву організації
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="reg_fio" class="form-label">ПІБ контактної особи</label>
                                <input type="text" class="form-control" id="reg_fio" name="reg_fio" placeholder="Введіть ПІБ" required>
                                <div class="invalid-feedback">
                                    Будь ласка, введіть ПІБ контактної особи
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="reg_tel" class="form-label">Телефон</label>
                                    <input type="tel" class="form-control" id="reg_tel" name="reg_tel" placeholder="+380501234567" required>
                                    <div class="invalid-feedback">
                                        Будь ласка, введіть телефон
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="reg_email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="reg_email" name="reg_email" placeholder="email@example.com">
                                    <div class="invalid-feedback">
                                        Будь ласка, введіть коректний email
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="reg_city" class="form-label">Місто</label>
                                    <input type="text" class="form-control" id="reg_city" name="reg_city" placeholder="Введіть місто" required>
                                    <div class="invalid-feedback">
                                        Будь ласка, введіть місто
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="reg_adres" class="form-label">Адреса</label>
                                    <input type="text" class="form-control" id="reg_adres" name="reg_adres" placeholder="Введіть адресу" required>
                                    <div class="invalid-feedback">
                                        Будь ласка, введіть адресу
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="reg_login" class="form-label">Логін</label>
                                <input type="text" class="form-control" id="reg_login" name="reg_login" placeholder="Придумайте логін" required>
                                <div class="invalid-feedback">
                                    Будь ласка, введіть логін
                                </div>
                                <small class="form-text text-muted">Логін повинен бути унікальним</small>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="reg_password" class="form-label">Пароль</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="reg_password" name="reg_password" placeholder="Придумайте пароль" required minlength="6">
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="reg_password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback">
                                        Пароль повинен бути не менше 6 символів
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="reg_password_confirm" class="form-label">Підтвердження паролю</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="reg_password_confirm" name="reg_password_confirm" placeholder="Повторіть пароль" required>
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="reg_password_confirm">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback">
                                        Паролі не співпадають
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    Я погоджуюсь з <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">умовами використання</a>
                                </label>
                                <div class="invalid-feedback">
                                    Ви повинні погодитись з умовами
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="register" class="btn btn-success btn-lg">
                                    <i class="fas fa-user-plus me-2"></i> Зареєструватися
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="login-footer">
                <p>ТОВ "Одеський Коровай" &copy; <?php echo date('Y'); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Модальне вікно з умовами використання -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termsModalLabel">Умови використання</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h5>Правила користування системою ТОВ "Одеський Коровай"</h5>
                    <p>Ласкаво просимо до системи управління ТОВ "Одеський Коровай". Реєструючись в системі, ви приймаєте наступні умови:</p>
                    
                    <h6>1. Загальні положення</h6>
                    <p>Система призначена для оформлення та управління замовленнями на хлібобулочну продукцію ТОВ "Одеський Коровай".</p>
                    
                    <h6>2. Реєстрація</h6>
                    <p>При реєстрації ви зобов'язуєтеся надати точну, актуальну та повну інформацію про себе. Ви несете відповідальність за збереження конфіденційності вашого логіна та пароля.</p>
                    
                    <h6>3. Замовлення</h6>
                    <p>Мінімальна сума замовлення становить 200 грн. Скасування замовлення можливе не пізніше ніж за 6 годин до доставки.</p>
                    
                    <h6>4. Доставка</h6>
                    <p>Доставка здійснюється власним транспортом підприємства. Мінімальний час для оформлення замовлення - 1 день.</p>
                    
                    <h6>5. Оплата</h6>
                    <p>Оплата здійснюється безготівковим розрахунком або за фактом отримання.</p>
                    
                    <h6>6. Конфіденційність</h6>
                    <p>Ми зобов'язуємося захищати вашу конфіденційну інформацію відповідно до Закону України "Про захист персональних даних".</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Зрозуміло</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Валідація форми
        (function() {
            'use strict';
            
            var forms = document.querySelectorAll('.needs-validation');
            
            Array.prototype.slice.call(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    
                    // Додаткова перевірка паролів
                    if (form.querySelector('#reg_password') && form.querySelector('#reg_password_confirm')) {
                        var password = form.querySelector('#reg_password').value;
                        var confirmPassword = form.querySelector('#reg_password_confirm').value;
                        
                        if (password !== confirmPassword) {
                            form.querySelector('#reg_password_confirm').setCustomValidity('Паролі не співпадають');
                            event.preventDefault();
                            event.stopPropagation();
                        } else {
                            form.querySelector('#reg_password_confirm').setCustomValidity('');
                        }
                    }
                    
                    form.classList.add('was-validated');
                }, false);
            });
        })();
        
        // Функція перемикання відображення пароля
        document.querySelectorAll('.toggle-password').forEach(function(button) {
            button.addEventListener('click', function() {
                var targetId = this.getAttribute('data-target');
                var passwordInput = document.getElementById(targetId);
                
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    this.innerHTML = '<i class="fas fa-eye-slash"></i>';
                } else {
                    passwordInput.type = 'password';
                    this.innerHTML = '<i class="fas fa-eye"></i>';
                }
            });
        });
        
        // Активація вкладки з помилкою або після успішної реєстрації
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($_POST['register']) && !$registrationSuccess): ?>
                document.getElementById('register-tab').click();
            <?php elseif ($registrationSuccess): ?>
                document.getElementById('login-tab').click();
            <?php endif; ?>
        });
    </script>
</body>
</html>