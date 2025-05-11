<?php
$pageTitle = 'Зв\'язок з нами';

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

// Обробка відправки форми
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    
    if (empty($subject) || empty($message)) {
        $error = 'Будь ласка, заповніть всі обов\'язкові поля';
    } else {
        // В реальному проекті тут був би код для відправки повідомлення
        // Наприклад, через email або збереження в базі даних
        
        // Імітація успішної відправки
        $success = 'Ваше повідомлення успішно надіслано! Ми зв\'яжемося з вами найближчим часом.';
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
            <a class="nav-link" href="profile.php">
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
    <!-- Форма зворотного зв'язку -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-envelope me-2"></i> Напишіть нам
                </h5>
            </div>
            <div class="card-body">
                <form action="" method="POST" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="name" class="form-label">Ваше ім'я</label>
                        <input type="text" class="form-control" id="name" value="<?php echo htmlspecialchars($client['fio']); ?>" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Ваш email</label>
                        <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($client['mail'] ?: ''); ?>" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="subject" class="form-label">Тема повідомлення *</label>
                        <select class="form-select" id="subject" name="subject" required>
                            <option value="">-- Виберіть тему --</option>
                            <option value="Питання про замовлення">Питання про замовлення</option>
                            <option value="Пропозиція співпраці">Пропозиція співпраці</option>
                            <option value="Пропозиція щодо покращення">Пропозиція щодо покращення</option>
                            <option value="Скарга">Скарга</option>
                            <option value="Інше">Інше</option>
                        </select>
                        <div class="invalid-feedback">
                            Будь ласка, виберіть тему повідомлення
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="message" class="form-label">Ваше повідомлення *</label>
                        <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                        <div class="invalid-feedback">
                            Будь ласка, введіть текст повідомлення
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="privacy" required>
                        <label class="form-check-label" for="privacy">
                            Я даю згоду на обробку моїх персональних даних
                        </label>
                        <div class="invalid-feedback">
                            Ви повинні погодитися перед відправкою
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-1"></i> Надіслати повідомлення
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Контактна інформація -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i> Контактна інформація
                </h5>
            </div>
            <div class="card-body">
                <p><i class="fas fa-building me-2"></i> <strong>ТОВ "Одеський Коровай"</strong></p>
                <hr>
                
                <p><i class="fas fa-map-marker-alt me-2"></i> <strong>Адреса:</strong><br>
                   м. Одеса, вул. Пекарська, 10</p>
                
                <p><i class="fas fa-phone me-2"></i> <strong>Телефон:</strong><br>
                   +38 (048) 123-45-67</p>
                
                <p><i class="fas fa-envelope me-2"></i> <strong>Email:</strong><br>
                   info@odesskiy-korovay.com</p>
                
                <p><i class="fas fa-clock me-2"></i> <strong>Графік роботи:</strong><br>
                   Пн-Пт: 8:00 - 18:00<br>
                   Сб-Нд: 9:00 - 15:00</p>
                
                <div class="alert alert-info mt-4">
                    <i class="fas fa-info-circle me-2"></i> Ми відповідаємо на всі повідомлення протягом 24 годин в робочі дні.
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-map-marked-alt me-2"></i> Як нас знайти
                </h5>
            </div>
            <div class="card-body">
                <!-- Тут можна було б розмістити Google Maps або інший спосіб відображення карти -->
                <div class="ratio ratio-4x3 mb-3">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2747.6547850577307!2d30.7223543!3d46.4814283!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNDbCsDI4JzUzLjAiTiAzMMKwNDMnMDEuNiJF!5e0!3m2!1sen!2sua!4v1651841818792!5m2!1sen!2sua" 
                            allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
                
                <p><strong>Проїзд:</strong></p>
                <ul>
                    <li>Трамвай №5, 10 - зупинка "Центральна"</li>
                    <li>Тролейбус №7, 9 - зупинка "Пекарська"</li>
                    <li>Маршрутне таксі №145, 168, 242 - зупинка "Ринок"</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Валідація форми
    const form = document.querySelector('.needs-validation');
    
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        form.classList.add('was-validated');
    }, false);
});
</script>

<?php include_once '../../includes/footer.php'; ?>