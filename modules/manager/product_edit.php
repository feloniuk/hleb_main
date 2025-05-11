<?php
$pageTitle = 'Редагування продукту';

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

// Отримання ID продукту
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($productId <= 0) {
    $error = 'Не вказано ID продукту';
} else {
    // Отримання даних продукту
    $productQuery = "SELECT * FROM product WHERE id = ?";
    $stmt = mysqli_prepare($connection, $productQuery);
    mysqli_stmt_bind_param($stmt, "i", $productId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 0) {
        $error = 'Продукт не знайдений';
    } else {
        $product = mysqli_fetch_assoc($result);
    }
}

// Обробка форми
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($product)) {
    // Отримання даних з форми
    $nazvanie = trim($_POST['nazvanie'] ?? '');
    $ves = floatval($_POST['ves'] ?? 0);
    $srok = intval($_POST['srok'] ?? 0);
    $stoimost = floatval($_POST['stoimost'] ?? 0);
    $zena = floatval($_POST['zena'] ?? 0);
    $image = trim($_POST['image'] ?? '');
    
    // Якщо шлях до зображення вказано, додаємо префікс
    if (!empty($image)) {
        $image = 'assets/img/product/' . $image;
    } else {
        $image = $product['image']; // Зберігаємо попереднє значення
    }
    
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
        // Перевірка, чи існує вже продукт з такою ж назвою (крім поточного)
        $checkQuery = "SELECT COUNT(*) as count FROM product WHERE nazvanie = ? AND id != ?";
        $stmt = mysqli_prepare($connection, $checkQuery);
        mysqli_stmt_bind_param($stmt, "si", $nazvanie, $productId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $count = mysqli_fetch_assoc($result)['count'];
        
        if ($count > 0) {
            $error = 'Продукт з такою назвою вже існує';
        } else {
            // Оновлення даних продукту
            $query = "UPDATE product SET nazvanie = ?, ves = ?, srok = ?, stoimost = ?, zena = ?, image = ? WHERE id = ?";
            $stmt = mysqli_prepare($connection, $query);
            mysqli_stmt_bind_param($stmt, "sdiddsi", $nazvanie, $ves, $srok, $stoimost, $zena, $image, $productId);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = 'Дані продукту успішно оновлено';
                
                // Оновлення даних в змінній $product для відображення на сторінці
                $product['nazvanie'] = $nazvanie;
                $product['ves'] = $ves;
                $product['srok'] = $srok;
                $product['stoimost'] = $stoimost;
                $product['zena'] = $zena;
                $product['image'] = $image;
            } else {
                $error = 'Помилка при оновленні даних продукту: ' . mysqli_error($connection);
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

<?php if (isset($product)): ?>
<!-- Форма редагування продукту -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-edit me-2"></i> Редагування продукту: <?php echo htmlspecialchars($product['nazvanie']); ?>
        </h5>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-4 text-center">
                <?php 
                $imagePath = !empty($product['image']) ? '../../' . $product['image'] : '../../assets/img/product-placeholder.jpg';
                ?>
                <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($product['nazvanie']); ?>" class="img-fluid rounded mb-3" style="max-height: 200px;">
                <p class="text-muted">Поточне зображення</p>
            </div>
            
            <div class="col-md-8">
                <form action="" method="POST" class="needs-validation" novalidate>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="nazvanie" class="form-label">Назва продукту *</label>
                            <input type="text" class="form-control" id="nazvanie" name="nazvanie" value="<?php echo htmlspecialchars($product['nazvanie']); ?>" required>
                            <div class="invalid-feedback">
                                Будь ласка, введіть назву продукту
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="ves" class="form-label">Вага (кг) *</label>
                            <input type="number" class="form-control" id="ves" name="ves" step="0.01" min="0.01" value="<?php echo $product['ves']; ?>" required>
                            <div class="invalid-feedback">
                                Вага повинна бути більше нуля
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="srok" class="form-label">Строк реалізації (год) *</label>
                            <input type="number" class="form-control" id="srok" name="srok" min="1" value="<?php echo $product['srok']; ?>" required>
                            <div class="invalid-feedback">
                                Строк реалізації повинен бути більше нуля
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="image" class="form-label">Зображення</label>
                            <div class="input-group">
                                <span class="input-group-text">assets/img/product/</span>
                                <input type="text" class="form-control" id="image" name="image" placeholder="Нове зображення">
                            </div>
                            <div class="form-text">
                                Залиште порожнім, щоб використовувати поточне зображення
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="stoimost" class="form-label">Собівартість (грн) *</label>
                            <input type="number" class="form-control" id="stoimost" name="stoimost" step="0.01" min="0.01" value="<?php echo $product['stoimost']; ?>" required>
                            <div class="invalid-feedback">
                                Собівартість повинна бути більше нуля
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="zena" class="form-label">Ціна (грн) *</label>
                            <input type="number" class="form-control" id="zena" name="zena" step="0.01" min="0.01" value="<?php echo $product['zena']; ?>" required>
                            <div class="invalid-feedback">
                                Ціна повинна бути більше нуля
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="profit" class="form-label">Прибуток (грн)</label>
                            <input type="text" class="form-control" id="profit" readonly>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="product_details.php?id=<?php echo $productId; ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Скасувати
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Зберегти зміни
                        </button>
                    </div>
                </form>
            </div>
        </div>
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
<?php endif; ?>

<?php
include_once '../../includes/footer.php';
?>