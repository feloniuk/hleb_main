<?php
$pageTitle = 'Додавання нового клієнта';

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Перевірка доступу
if (!checkAccess(['manager'])) {
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
    
    // Для авторизації
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    // Встановлення за замовчуванням, якщо не вказано
    if (empty($login)) $login = '123';
    if (empty($password)) $password = '123';
    
    // Валідація даних
    if (empty($name)) {
        $error = 'Будь ласка, введіть назву компанії';
    } elseif (empty($fio)) {
        $error = 'Будь ласка, введіть ПІБ контактної особи';
    } elseif (empty($tel)) {
        $error = 'Будь ласка, введіть номер телефону';
    } elseif (empty($city)) {
        $error = 'Будь ласка, введіть місто';
    } elseif ($rast <= 0) {
        $error = 'Відстань повинна бути більше нуля';
    } else {
        // Перевірка, чи існує вже клієнт з такою ж назвою компанії
        $checkQuery = "SELECT COUNT(*) as count FROM klientu WHERE name = ?";
        $stmt = mysqli_prepare($connection, $checkQuery);
        mysqli_stmt_bind_param($stmt, "s", $name);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $count = mysqli_fetch_assoc($result)['count'];
        
        if ($count > 0) {
            $error = 'Клієнт з такою назвою компанії вже існує';
        } else {
            // Додавання клієнта до бази даних
            $query = "INSERT INTO klientu (name, fio, dolj, tel, mail, city, adres, rast, login, password) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($connection, $query);
            mysqli_stmt_bind_param($stmt, "sssssssdss", $name, $fio, $dolj, $tel, $mail, $city, $adres, $rast, $login, $password);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = 'Клієнта успішно додано';
                
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
            <a class="nav-link" href="orders.php">
                <i class="fas fa-clipboard-list"></i> Замовлення
            </a>
            <a class="nav-link active" href="clients.php">
                <i class="fas fa-users"></i> Клієнти
            </a>
            <a class="nav-link" href="products.php">
                <i class="fas fa-bread-slice"></i> Продукція
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
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="name" class="form-label">Назва компанії *</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                    <div class="invalid-feedback">
                        Будь ласка, введіть назву компанії
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
                        Будь ласка, введіть номер телефону
                    </div>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="mail" class="form-label">Email</label>
                    <input type="email" class="form-control" id="mail" name="mail" placeholder="example@example.com">
                    <div class="invalid-feedback">
                        Будь ласка, введіть коректний email
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label for="city" class="form-label">Місто *</label>
                    <input type="text" class="form-control" id="city" name="city" required>
                    <div class="invalid-feedback">
                        Будь ласка, введіть місто
                    </div>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-8">
                    <label for="adres" class="form-label">Адреса</label>
                    <input type="text" class="form-control" id="adres" name="adres">
                </div>
                
                <div class="col-md-4">
                    <label for="rast" class="form-label">Відстань (км) *</label>
                    <input type="number" class="form-control" id="rast" name="rast" step="0.1" min="0.1" required>
                    <div class="invalid-feedback">
                        Відстань повинна бути більше нуля
                    </div>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Дані для входу клієнта</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="login" class="form-label">Логін</label>
                                    <input type="text" class="form-control" id="login" name="login" placeholder="За замовчуванням: 123">
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="password" class="form-label">Пароль</label>
                                    <input type="text" class="form-control" id="password" name="password" placeholder="За замовчуванням: 123">
                                </div>
                            </div>
                            <div class="alert alert-info mt-3 mb-0">
                                <i class="fas fa-info-circle me-2"></i> Якщо поля логін та пароль залишити порожніми, за замовчуванням буде встановлено "123"
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
    // Маска для телефону
    document.addEventListener('DOMContentLoaded', function() {
        const telInput = document.getElementById('tel');
        
        telInput.addEventListener('input', function(e) {
            let x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,2})(\d{0,2})/);
            e.target.value = !x[2] ? x[1] : '+38 (' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '') + (x[4] ? '-' + x[4] : '');
        });
    });
</script>

<?php
include_once '../../includes/footer.php';
?>