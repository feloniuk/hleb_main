<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

// Якщо користувач вже авторизований, перенаправляємо його на відповідну сторінку
if (isUserLoggedIn()) {
    $userRole = getUserRole();
    
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
        case 'client':
            header("Location: modules/client/dashboard.php");
            break;
    }
    
    exit;
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
                case 'client':
                    header("Location: modules/client/dashboard.php");
                    break;
            }
            
            exit;
        } else {
            $error = 'Невірний логін або пароль';
        }
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
            max-width: 450px;
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
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <div class="login-form">
                <form method="post" action="" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="login" class="form-label">Логін</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="login" name="login" placeholder="Введіть ваш логін" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">Пароль</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Введіть ваш пароль" required>
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i> Увійти
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="login-footer">
                <p>ТОВ "Одеський Коровай" &copy; <?php echo date('Y'); ?></p>
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
                    
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>
</html>