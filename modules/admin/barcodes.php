<?php
/**
 * Управління штрих-кодами продуктів
 * 
 * Файл: modules/admin/barcodes.php
 */

$pageTitle = 'Управління штрих-кодами';

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/barcode_functions.php';

// Перевірка доступу
if (!checkAccess(['admin'])) {
    header("Location: ../../index.php");
    exit;
}

$connection = connectDatabase();
$success = '';
$error = '';

// Обробка оновлення штрих-коду
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_barcode'])) {
    $productId = intval($_POST['product_id']);
    $newBarcode = $_POST['barcode'];
    
    // Валідація
    if (!validateBarcodeFormat($newBarcode)) {
        $error = "Невірний формат штрих-коду. Має бути 8-значне число.";
    } else if (isBarcodeExists($connection, $newBarcode, $productId)) {
        $error = "Штрих-код $newBarcode вже використовується іншим продуктом.";
    } else {
        // Оновлення
        $updateQuery = "UPDATE product SET barcode = ? WHERE id = ?";
        $stmt = mysqli_prepare($connection, $updateQuery);
        mysqli_stmt_bind_param($stmt, "si", $newBarcode, $productId);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = "Штрих-код успішно оновлено.";
        } else {
            $error = "Помилка при оновленні: " . mysqli_error($connection);
        }
    }
}

// Генерація нових штрих-кодів для всіх продуктів без коду
if (isset($_POST['generate_all'])) {
    $updated = updateMissingBarcodes($connection);
    $success = "Згенеровано штрих-коди для $updated продуктів.";
}

// Отримання списку продуктів
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$query = "SELECT * FROM product";
if ($searchTerm) {
    $query .= " WHERE nazvanie LIKE ? OR barcode LIKE ?";
}
$query .= " ORDER BY id ASC";

if ($searchTerm) {
    $stmt = mysqli_prepare($connection, $query);
    $searchPattern = "%$searchTerm%";
    mysqli_stmt_bind_param($stmt, "ss", $searchPattern, $searchPattern);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($connection, $query);
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
            <a class="nav-link" href="products.php">
                <i class="fas fa-bread-slice"></i> Продукція
            </a>
            <a class="nav-link active" href="barcodes.php">
                <i class="fas fa-barcode"></i> Штрих-коди
            </a>
            <a class="nav-link" href="settings.php">
                <i class="fas fa-cogs"></i> Налаштування
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

<!-- Панель інструментів -->
<div class="card mb-4">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-barcode me-2"></i> Управління штрих-кодами продуктів
            </h5>
            <form method="post" action="" class="d-inline">
                <button type="submit" name="generate_all" class="btn btn-success" 
                        onclick="return confirm('Згенерувати штрих-коди для всіх продуктів без коду?');">
                    <i class="fas fa-magic me-1"></i> Згенерувати відсутні
                </button>
            </form>
        </div>
    </div>
    <div class="card-body">
        <form method="get" action="" class="row g-3">
            <div class="col-md-9">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" name="search" 
                           placeholder="Пошук за назвою або штрих-кодом" 
                           value="<?php echo htmlspecialchars($searchTerm); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-1"></i> Знайти
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Таблиця продуктів -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-list me-2"></i> Список продуктів та їх штрих-коди
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Зображення</th>
                        <th>Назва продукту</th>
                        <th>Штрих-код</th>
                        <th>Відформатований</th>
                        <th>Дії</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($product = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo $product['id']; ?></td>
                            <td>
                                <?php 
                                $imagePath = !empty($product['image']) 
                                    ? '../../' . $product['image'] 
                                    : '../../assets/img/product-placeholder.jpg';
                                ?>
                                <img src="<?php echo $imagePath; ?>" 
                                     alt="<?php echo htmlspecialchars($product['nazvanie']); ?>"
                                     class="rounded"
                                     style="width: 50px; height: 50px; object-fit: cover;">
                            </td>
                            <td><?php echo htmlspecialchars($product['nazvanie']); ?></td>
                            <td>
                                <?php if ($product['barcode']): ?>
                                    <span class="badge bg-success"><?php echo $product['barcode']; ?></span>
                                <?php else: ?>
                                    <span class="badge bg-warning">Відсутній</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($product['barcode']): ?>
                                    <code><?php echo formatBarcodeDisplay($product['barcode']); ?></code>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" 
                                        class="btn btn-sm btn-outline-primary edit-barcode" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editBarcodeModal"
                                        data-id="<?php echo $product['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($product['nazvanie']); ?>"
                                        data-barcode="<?php echo $product['barcode']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if (!$product['barcode']): ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-success generate-barcode"
                                            data-id="<?php echo $product['id']; ?>">
                                        <i class="fas fa-magic"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if ($product['barcode']): ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-info print-barcode"
                                            data-barcode="<?php echo $product['barcode']; ?>"
                                            data-name="<?php echo htmlspecialchars($product['nazvanie']); ?>">
                                        <i class="fas fa-print"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Модальне вікно редагування -->
<div class="modal fade" id="editBarcodeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Редагування штрих-коду</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <input type="hidden" name="product_id" id="edit-product-id">
                    <div class="mb-3">
                        <label class="form-label">Продукт</label>
                        <input type="text" class="form-control" id="edit-product-name" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="edit-barcode" class="form-label">Штрих-код (8 цифр)</label>
                        <input type="text" 
                               class="form-control" 
                               name="barcode" 
                               id="edit-barcode" 
                               pattern="\d{8}" 
                               maxlength="8"
                               required>
                        <div class="form-text">Формат: 10000001, 10000002, тощо</div>
                    </div>
                    <div class="mb-3">
                        <button type="button" class="btn btn-sm btn-secondary" id="suggest-barcode">
                            <i class="fas fa-lightbulb me-1"></i> Запропонувати код
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                    <button type="submit" name="update_barcode" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Зберегти
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Редагування штрих-коду
    document.querySelectorAll('.edit-barcode').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.id;
            const productName = this.dataset.name;
            const barcode = this.dataset.barcode;
            
            document.getElementById('edit-product-id').value = productId;
            document.getElementById('edit-product-name').value = productName;
            document.getElementById('edit-barcode').value = barcode || '';
        });
    });
    
    // Пропозиція штрих-коду
    document.getElementById('suggest-barcode').addEventListener('click', function() {
        const productId = document.getElementById('edit-product-id').value;
        const suggestedBarcode = '1000000' + productId.padStart(1, '0');
        document.getElementById('edit-barcode').value = suggestedBarcode.substr(-8);
    });
    
    // Швидка генерація
    document.querySelectorAll('.generate-barcode').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.id;
            if (confirm('Згенерувати штрих-код для цього продукту?')) {
                // Тут можна додати AJAX запит для генерації
                window.location.href = '?generate=' + productId;
            }
        });
    });
    
    // Друк штрих-коду
    document.querySelectorAll('.print-barcode').forEach(button => {
        button.addEventListener('click', function() {
            const barcode = this.dataset.barcode;
            const name = this.dataset.name;
            // Відкрити вікно друку штрих-коду
            window.open('print_barcode.php?code=' + barcode + '&name=' + encodeURIComponent(name), 
                       '_blank', 'width=400,height=300');
        });
    });
    
    // Валідація вводу
    document.getElementById('edit-barcode').addEventListener('input', function(e) {
        this.value = this.value.replace(/\D/g, '').substr(0, 8);
    });
});
</script>

<?php include_once '../../includes/footer.php'; ?>