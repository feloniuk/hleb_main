<?php
$pageTitle = 'Додавання нового продукту';

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
    $nazvanie = trim($_POST['nazvanie'] ?? '');
    $ves = floatval($_POST['ves'] ?? 0);
    $srok = intval($_POST['srok'] ?? 0);
    $stoimost = floatval($_POST['stoimost'] ?? 0);
    $zena = floatval($_POST['zena'] ?? 0);

    // Валідація даних
    if (empty($nazvanie)) {
        $error = 'Будь ласка, введіть назву продукту';
    } elseif ($ves <= 0) {
        $error = 'Будь ласка, введіть коректну вагу продукту';
    } elseif ($srok <= 0) {
        $error = 'Будь ласка, введіть коректний строк реалізації';
    } elseif ($stoimost <= 0) {
        $error = 'Будь ласка, введіть коректну собівартість';
    } elseif ($zena <= 0) {
        $error = 'Будь ласка, введіть коректну ціну';
    } else {
        // Перевірка, чи існує продукт з такою назвою
        $checkQuery = "SELECT COUNT(*) as count FROM product WHERE nazvanie = ?";
        $stmt = mysqli_prepare($connection, $checkQuery);
        mysqli_stmt_bind_param($stmt, "s", $nazvanie);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $count = mysqli_fetch_assoc($result)['count'];
        
        if ($count > 0) {
            $error = 'Продукт з такою назвою вже існує';
        } else {
            // Обробка завантаження зображення
            $imagePath = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../../assets/img/products/';
                
                // Перевірка/створення директорії для зображень
                if (!file_exists($uploadDir)) {
                    if (!mkdir($uploadDir, 0755, true)) {
                        $error = 'Помилка при створенні директорії для зображень';
                    }
                }
                
                if (empty($error)) {
                    $fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $fileName = generateSlug($nazvanie) . '_' . time() . '.' . $fileExtension;
                    $uploadPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                        $imagePath = 'assets/img/products/' . $fileName;
                    } else {
                        $error = 'Помилка при завантаженні зображення';
                    }
                }
            }
            
            if (empty($error)) {
                // Додавання продукту до бази даних
                $insertQuery = "INSERT INTO product (nazvanie, ves, srok, stoimost, zena, image) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($connection, $insertQuery);
                mysqli_stmt_bind_param($stmt, "sdidds", $nazvanie, $ves, $srok, $stoimost, $zena, $imagePath);
                
                if (mysqli_stmt_execute($stmt)) {
                    $productId = mysqli_insert_id($connection);
                    $success = 'Продукт успішно додано';
                    
                    // Запис в журнал
                    logAction($connection, 'Додавання продукту', 'Додано новий продукт: ' . $nazvanie);
                    
                    // Перенаправлення на сторінку продуктів
                    header("Location: products.php?success=" . urlencode($success));
                    exit;
                } else {
                    $error = 'Помилка при додаванні продукту: ' . mysqli_error($connection);
                }
                
                mysqli_stmt_close($stmt);
            }
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
            <a class="nav-link" href="clients.php">
                <i class="fas fa-user-tie"></i> Клієнти
            </a>
            <a class="nav-link active" href="products.php">
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

<!-- Форма додавання продукту -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-plus me-2"></i> Додавання нового продукту
        </h5>
    </div>
    <div class="card-body">
        <form action="" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
            <div class="row mb-3">
                <div class="col-md-9">
                    <div class="mb-3">
                        <label for="nazvanie" class="form-label">Назва продукту *</label>
                        <input type="text" class="form-control" id="nazvanie" name="nazvanie" required>
                        <div class="invalid-feedback">
                            Будь ласка, введіть назву продукту
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="ves" class="form-label">Вага (кг) *</label>
                            <input type="number" class="form-control" id="ves" name="ves" step="0.01" min="0.01" required>
                            <div class="invalid-feedback">
                                Будь ласка, введіть вагу продукту
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="srok" class="form-label">Строк реалізації (год) *</label>
                            <input type="number" class="form-control" id="srok" name="srok" min="1" required>
                            <div class="invalid-feedback">
                                Будь ласка, введіть строк реалізації
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="category" class="form-label">Категорія</label>
                            <select class="form-select" id="category" name="category">
                                <option value="">-- Виберіть категорію --</option>
                                <option value="1">Хліб</option>
                                <option value="2">Булочки</option>
                                <option value="3">Пиріжки</option>
                                <option value="4">Кондитерські вироби</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="stoimost" class="form-label">Собівартість (грн) *</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="stoimost" name="stoimost" step="0.01" min="0.01" required>
                                <span class="input-group-text">грн</span>
                                <div class="invalid-feedback">
                                    Будь ласка, введіть собівартість
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="zena" class="form-label">Ціна (грн) *</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="zena" name="zena" step="0.01" min="0.01" required>
                                <span class="input-group-text">грн</span>
                                <div class="invalid-feedback">
                                    Будь ласка, введіть ціну
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Опис продукту</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="image" class="form-label">Зображення продукту</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        <div class="form-text">Рекомендований розмір: 500x500px. Максимальний розмір: 2MB</div>
                    </div>
                    
                    <div class="mt-3">
                        <div class="card">
                            <div class="card-body">
                                <div id="image-preview" class="text-center">
                                    <img src="../../assets/img/product-placeholder.jpg" alt="Зображення продукту" class="img-fluid" style="max-height: 200px;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Додаткова інформація</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="available" name="available" value="1" checked>
                                        <label class="form-check-label" for="available">
                                            Доступний для замовлення
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="featured" name="featured" value="1">
                                        <label class="form-check-label" for="featured">
                                            Рекомендований продукт
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="new" name="new" value="1" checked>
                                        <label class="form-check-label" for="new">
                                            Новинка
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="products.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Повернутися
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Зберегти продукт
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Попередній перегляд зображення
        document.getElementById('image').addEventListener('change', function() {
            var fileInput = this;
            var imagePreview = document.getElementById('image-preview').querySelector('img');
            
            if (fileInput.files && fileInput.files[0]) {
                var reader = new FileReader();
                
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                };
                
                reader.readAsDataURL(fileInput.files[0]);
            }
        });
        
        // Автоматичне обчислення маржі
        function calculateMargin() {
            var stoimost = parseFloat(document.getElementById('stoimost').value) || 0;
            var zena = parseFloat(document.getElementById('zena').value) || 0;
            
            if (stoimost > 0 && zena > 0) {
                var margin = ((zena - stoimost) / stoimost * 100).toFixed(2);
                document.getElementById('margin').textContent = margin + '%';
            } else {
                document.getElementById('margin').textContent = '0%';
            }
        }
        
        document.getElementById('stoimost').addEventListener('input', calculateMargin);
        document.getElementById('zena').addEventListener('input', calculateMargin);
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