<?php
$pageTitle = 'Профіль клієнта';

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Перевірка доступу
if (!checkAccess(['client'])) {
    header("Location: ../../index.php");
    exit;
}

$connection = connectDatabase();
$clientId = $_SESSION['id'];
$success = '';
$error = '';

// Отримання даних клієнта
$clientQuery = "SELECT * FROM klientu WHERE id = ?";
$stmt = mysqli_prepare($connection, $clientQuery);
mysqli_stmt_bind_param($stmt, "i", $clientId);
mysqli_stmt_execute($stmt);
$clientResult = mysqli_stmt_get_result($stmt);
$client = mysqli_fetch_assoc($clientResult);

// Обробка форми оновлення профілю
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fio = $_POST['fio'] ?? '';
    $dolj = $_POST['dolj'] ?? '';
    $tel = $_POST['tel'] ?? '';
    $mail = $_POST['mail'] ?? '';
    $city = $_POST['city'] ?? '';
    $adres = $_POST['adres'] ?? '';
    
    // Валідація даних
    if (empty($fio) || empty($tel) || empty($city) || empty($adres)) {
        $error = 'Заповніть всі обов\'язкові поля';
    } else {
        // Оновлення даних клієнта
        $updateQuery = "UPDATE klientu SET fio = ?, dolj = ?, tel = ?, mail = ?, city = ?, adres = ? WHERE id = ?";
        $stmt = mysqli_prepare($connection, $updateQuery);
        mysqli_stmt_bind_param($stmt, "ssssssi", $fio, $dolj, $tel, $mail, $city, $adres, $clientId);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = 'Профіль успішно оновлено';
            
            // Оновлення даних клієнта для відображення на сторінці
            $stmt = mysqli_prepare($connection, $clientQuery);
            mysqli_stmt_bind_param($stmt, "i", $clientId);
            mysqli_stmt_execute($stmt);
            $clientResult = mysqli_stmt_get_result($stmt);
            $client = mysqli_fetch_assoc($clientResult);
        } else {
            $error = 'Помилка при оновленні профілю: ' . mysqli_error($connection);
        }
    }
}

// Обробка форми зміни пароля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Валідація даних
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'Заповніть всі поля для зміни пароля';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Новий пароль і підтвердження не співпадають';
    } elseif ($current_password !== $client['password']) {
        $error = 'Поточний пароль невірний';
    } else {
        // Оновлення пароля
        $updatePasswordQuery = "UPDATE klientu SET password = ? WHERE id = ?";
        $stmt = mysqli_prepare($connection, $updatePasswordQuery);
        mysqli_stmt_bind_param($stmt, "si", $new_password, $clientId);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = 'Пароль успішно змінено';
        } else {
            $error = 'Помилка при зміні пароля: ' . mysqli_error($connection);
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
            <a class="nav-link" href="orders.php">
                <i class="fas fa-clipboard-list"></i> Мої замовлення
            </a>
            <a class="nav-link" href="products.php">
                <i class="fas fa-bread-slice"></i> Каталог продукції
            </a>
            <a class="nav-link" href="cart.php">
                <i class="fas fa-shopping-cart"></i> Кошик
            </a>
            <a class="nav-link active" href="profile.php">
                <i class="fas fa-user"></i> Профіль
            </a>
        </nav>
    </div>
</div>

<?php if (!empty($success)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo htmlspecialchars($success); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php if (!empty($error)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo htmlspecialchars($error); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="row">
    <!-- Профіль клієнта -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-user-edit me-2"></i> Редагування профілю
                </h5>
            </div>
            <div class="card-body">
                <form action="" method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Назва організації</label>
                            <input type="text" class="form-control" id="name" value="<?php echo htmlspecialchars($client['name']); ?>" readonly>
                            <div class="form-text">Назву організації можна змінити тільки через адміністратора</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="fio" class="form-label">ПІБ контактної особи *</label>
                            <input type="text" class="form-control" id="fio" name="fio" value="<?php echo htmlspecialchars($client['fio']); ?>" required>
                            <div class="invalid-feedback">
                                Введіть ПІБ контактної особи
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="dolj" class="form-label">Посада</label>
                            <input type="text" class="form-control" id="dolj" name="dolj" value="<?php echo htmlspecialchars($client['dolj']); ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="tel" class="form-label">Телефон *</label>
                            <input type="tel" class="form-control" id="tel" name="tel" value="<?php echo htmlspecialchars($client['tel']); ?>" placeholder="+380XXXXXXXXX" required>
                            <div class="invalid-feedback">
                                Введіть номер телефону
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="mail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="mail" name="mail" value="<?php echo htmlspecialchars($client['mail']); ?>" placeholder="example@domain.com">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="city" class="form-label">Місто *</label>
                            <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($client['city']); ?>" required>
                            <div class="invalid-feedback">
                                Введіть місто
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="adres" class="form-label">Адреса *</label>
                            <input type="text" class="form-control" id="adres" name="adres" value="<?php echo htmlspecialchars($client['adres']); ?>" required>
                            <div class="invalid-feedback">
                                Введіть адресу
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <p class="text-muted">* - обов'язкові поля</p>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Зберегти зміни
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Зміна пароля -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-key me-2"></i> Зміна пароля
                </h5>
            </div>
            <div class="card-body">
                <form action="" method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="change_password" value="1">
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Поточний пароль</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                        <div class="invalid-feedback">
                            Введіть поточний пароль
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Новий пароль</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <div class="invalid-feedback">
                            Введіть новий пароль
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Підтвердження пароля</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        <div class="invalid-feedback">
                            Підтвердіть новий пароль
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-key me-1"></i> Змінити пароль
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Додаткова інформація -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i> Інформація про клієнта
                </h5>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="flex-shrink-0">
                        <i class="fas fa-building fa-3x text-primary"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h5><?php echo htmlspecialchars($client['name']); ?></h5>
                        <p class="text-muted mb-0">ID: <?php echo $client['id']; ?></p>
                    </div>
                </div>
                
                <hr>
                
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-user me-2"></i> Контактна особа</span>
                        <span class="text-primary"><?php echo htmlspecialchars($client['fio']); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-briefcase me-2"></i> Посада</span>
                        <span class="text-primary"><?php echo htmlspecialchars($client['dolj'] ?: 'Не вказано'); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-phone me-2"></i> Телефон</span>
                        <span class="text-primary"><?php echo htmlspecialchars($client['tel']); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-envelope me-2"></i> Email</span>
                        <span class="text-primary"><?php echo htmlspecialchars($client['mail'] ?: 'Не вказано'); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-map-marker-alt me-2"></i> Місто</span>
                        <span class="text-primary"><?php echo htmlspecialchars($client['city']); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-home me-2"></i> Адреса</span>
                        <span class="text-primary"><?php echo htmlspecialchars($client['adres']); ?></span>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Статистика замовлень -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-chart-line me-2"></i> Статистика замовлень
                </h5>
            </div>
            <div class="card-body">
                <?php
                // Отримання статистики замовлень
                $statsQuery = "SELECT 
                                COUNT(z.idd) as total_orders,
                                SUM(z.kol) as total_quantity,
                                SUM(z.kol * p.zena) as total_amount
                              FROM zayavki z
                              JOIN product p ON z.id = p.id
                              WHERE z.idklient = ?";
                $stmt = mysqli_prepare($connection, $statsQuery);
                mysqli_stmt_bind_param($stmt, "i", $clientId);
                mysqli_stmt_execute($stmt);
                $statsResult = mysqli_stmt_get_result($stmt);
                $stats = mysqli_fetch_assoc($statsResult);
                ?>
                
                <div class="d-flex justify-content-between mb-3">
                    <span>Всього замовлень:</span>
                    <span class="badge bg-primary"><?php echo $stats['total_orders'] ?: 0; ?></span>
                </div>
                
                <div class="d-flex justify-content-between mb-3">
                    <span>Загальна кількість:</span>
                    <span class="badge bg-success"><?php echo $stats['total_quantity'] ?: 0; ?> шт.</span>
                </div>
                
                <div class="d-flex justify-content-between">
                    <span>Загальна сума:</span>
                    <span class="badge bg-warning"><?php echo number_format($stats['total_amount'] ?: 0, 2); ?> грн</span>
                </div>
            </div>
        </div>
        
        <!-- Контактна інформація пекарні -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i> Контактна інформація
                </h5>
            </div>
            <div class="card-body">
                <p><i class="fas fa-building me-2"></i> ТОВ "Одеський Коровай"</p>
                <p><i class="fas fa-map-marker-alt me-2"></i> м. Одеса, вул. Пекарська, 10</p>
                <p><i class="fas fa-phone me-2"></i> +38 (048) 123-45-67</p>
                <p><i class="fas fa-envelope me-2"></i> info@odesskiy-korovay.com</p>
                <p><i class="fas fa-clock me-2"></i> Пн-Пт: 8:00 - 18:00, Сб-Нд: 9:00 - 15:00</p>
                
                <div class="d-grid gap-2 mt-3">
                    <a href="contact.php" class="btn btn-primary">
                        <i class="fas fa-envelope me-1"></i> Написати повідомлення
                    </a>
                    <a href="tel:+380481234567" class="btn btn-outline-primary">
                        <i class="fas fa-phone me-1"></i> Зателефонувати
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Валідація форм
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
    
    // Перевірка співпадіння паролів
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    
    if (newPassword && confirmPassword) {
        confirmPassword.addEventListener('input', function() {
            if (newPassword.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Паролі не співпадають');
            } else {
                confirmPassword.setCustomValidity('');
            }
        });
        
        newPassword.addEventListener('input', function() {
            if (newPassword.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Паролі не співпадають');
            } else {
                confirmPassword.setCustomValidity('');
            }
        });
    }
});
</script>

<?php include_once '../../includes/footer.php'; ?>