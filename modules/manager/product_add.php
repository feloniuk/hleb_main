<?php
$pageTitle = 'Додавання нового продукту';

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
    $nazvanie = trim($_POST['nazvanie'] ?? '');
    $ves = floatval($_POST['ves'] ?? 0);
    $srok = intval($_POST['srok'] ?? 0);
    $stoimost = floatval($_POST['stoimost'] ?? 0);
    $zena = floatval($_POST['zena'] ?? 0);
    $image = 'assets/img/product/' . trim($_POST['image'] ?? '');
    
    // Валідація даних
    if (empty($nazvanie)) {
        $error = 'Будь ласка, введіть назву продукту';
    } elseif ($ves <= 0) {
        $error = 'Вага продукту повинна бути більше нуля';
    } elseif ($srok <= 0) {
        $error = 'Строк реалізації повинен бути більше нуля';
    } elseif ($stoimost <= 0) {
        $error = 'Собівартість повинна бути більше нуля';
    } elseif ($zena <= 0) {
        $error = 'Ціна повинна бути більше нуля';
    } else {
        // Додавання продукту до бази даних
        $query = "INSERT INTO product (nazvanie, ves, srok, stoimost, zena, image) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($connection, $query);
        mysqli_stmt_bind_param($stmt, "sdddds", $nazvanie, $ves, $srok, $stoimost, $zena, $image);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = 'Продукт успішно додано';
            
            // Перенаправлення на сторінку продуктів
            header("Location: products.php?success=" . urlencode($success));
            exit;
        } else {
            $error = 'Помилка при додаванні продукту: ' . mysqli_error($connection);
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
            <a class="nav-link" href="orders.php">
                <i class="fas fa-clipboard-list"></i> Замовлення
            </a>
            <a class="nav-link" href="clients.php">
                <i class="fas fa-users"></i> Клієнти
            </a>
            <a class="nav-link active" href="products.php">
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

<!-- Форма додавання продукту -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-plus-circle me-2"></i> Додавання нового продукту
        </h5>
    </div>
    <div class="card-body">
        <form action="" method="POST" class="needs-validation" novalidate>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="nazvanie" class="form-label">Назва продукту *</label>
                    <input type="text" class="form-control" id="nazvanie" name="nazvanie" required>
                    <div class="invalid-feedback">
                        Будь ласка, введіть назву продукту
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label for="image" class="form-label">Шлях до зображення</label>
                    <div class="input-group">
                        <span class="input-group-text">assets/img/product/</span>
                        <input type="text" class="form-control" id="image" name="image" placeholder="photo.jpg">
                    </div>
                    <div class="form-text">
                        Залиште порожнім, щоб використовувати зображення за замовчуванням
                    </div>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="ves" class="form-label">Вага (кг) *</label>
                    <input type="number" class="form-control" id="ves" name="ves" step="0.01" min="0.01" required>
                    <div class="invalid-feedback">
                        Вага повинна бути більше нуля
                    </div>
                </div>
                
                <div class="col-md-4">
                    <label for="srok" class="form-label">Строк реалізації (год) *</label>
                    <input type="number" class="form-control" id="srok" name="srok" min="1" required>
                    <div class="invalid-feedback">
                        Строк реалізації повинен бути більше нуля
                    </div>
                </div>
                
                <div class="col-md-4">
                    <label for="stoimost" class="form-label">Собівартість (грн) *</label>
                    <input type="number" class="form-control" id="stoimost" name="stoimost" step="0.01" min="0.01" required>
                    <div class="invalid-feedback">
                        Собівартість повинна бути більше нуля
                    </div>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="zena" class="form-label">Ціна (грн) *</label>
                    <input type="number" class="form-control" id="zena" name="zena" step="0.01" min="0.01" required>
                    <div class="invalid-feedback">
                        Ціна повинна бути більше нуля
                    </div>
                </div>
                
                <div class="col-md-8">
                    <label for="profit" class="form-label">Прибуток (грн)</label>
                    <input type="text" class="form-control" id="profit" readonly>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-12">
                    <label class="form-label">Інгредієнти</label>
                    <div class="card">
                        <div class="card-body">
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <label for="ing-muka" class="form-label">Мука (кг)</label>
                                    <input type="number" class="form-control ingredient" id="ing-muka" step="0.01" min="0">
                                </div>
                                <div class="col-md-6">
                                    <label for="ing-voda" class="form-label">Вода (л)</label>
                                    <input type="number" class="form-control ingredient" id="ing-voda" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-4">
                                    <label for="ing-sahar" class="form-label">Цукор (кг)</label>
                                    <input type="number" class="form-control ingredient" id="ing-sahar" step="0.01" min="0">
                                </div>
                                <div class="col-md-4">
                                    <label for="ing-sol" class="form-label">Сіль (кг)</label>
                                    <input type="number" class="form-control ingredient" id="ing-sol" step="0.01" min="0">
                                </div>
                                <div class="col-md-4">
                                    <label for="ing-drozhzhi" class="form-label">Дріжджі (кг)</label>
                                    <input type="number" class="form-control ingredient" id="ing-drozhzhi" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="alert alert-info mt-3 mb-0">
                                <i class="fas fa-info-circle me-2"></i> Інформація про інгредієнти використовується для розрахунку виробничих потреб
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
    // Автоматичний розрахунок прибутку
    document.addEventListener('DOMContentLoaded', function() {
        const stoimostInput = document.getElementById('stoimost');
        const zenaInput = document.getElementById('zena');
        const profitInput = document.getElementById('profit');
        
        function calculateProfit() {
            const stoimost = parseFloat(stoimostInput.value) || 0;
            const zena = parseFloat(zenaInput.value) || 0;
            const profit = zena - stoimost;
            
            profitInput.value = profit.toFixed(2) + ' грн';
            
            if (profit > 0) {
                profitInput.classList.remove('text-danger');
                profitInput.classList.add('text-success');
            } else if (profit < 0) {
                profitInput.classList.remove('text-success');
                profitInput.classList.add('text-danger');
            } else {
                profitInput.classList.remove('text-success', 'text-danger');
            }
        }
        
        stoimostInput.addEventListener('input', calculateProfit);
        zenaInput.addEventListener('input', calculateProfit);
        
        // Ініціалізація
        calculateProfit();
    });
</script>

<?php
include_once '../../includes/footer.php';
?>