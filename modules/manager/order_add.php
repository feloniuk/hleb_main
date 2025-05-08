<?php
$pageTitle = 'Додавання нового замовлення';

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
    $idklient = isset($_POST['idklient']) ? intval($_POST['idklient']) : 0;
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $kol = isset($_POST['kol']) ? intval($_POST['kol']) : 0;
    $data = isset($_POST['data']) ? $_POST['data'] : date('Y-m-d');
    $doba = isset($_POST['doba']) ? $_POST['doba'] : 'денна';
    
    // Валідація даних
    if ($idklient <= 0) {
        $error = 'Будь ласка, виберіть клієнта';
    } elseif ($id <= 0) {
        $error = 'Будь ласка, виберіть продукт';
    } elseif ($kol <= 0) {
        $error = 'Кількість повинна бути більше нуля';
    } elseif (empty($data)) {
        $error = 'Будь ласка, виберіть дату';
    } else {
        // Додавання замовлення до бази даних
        $query = "INSERT INTO zayavki (idklient, id, kol, data, doba, status) VALUES (?, ?, ?, ?, ?, 'нове')";
        $stmt = mysqli_prepare($connection, $query);
        mysqli_stmt_bind_param($stmt, "iiiss", $idklient, $id, $kol, $data, $doba);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = 'Замовлення успішно додано';
            
            // Перенаправлення на сторінку замовлень
            header("Location: orders.php?success=" . urlencode($success));
            exit;
        } else {
            $error = 'Помилка при додаванні замовлення: ' . mysqli_error($connection);
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Отримання списку клієнтів
$clientsQuery = "SELECT id, name FROM klientu ORDER BY name";
$clientsResult = mysqli_query($connection, $clientsQuery);

// Отримання списку продуктів
$productsQuery = "SELECT id, nazvanie, zena FROM product ORDER BY nazvanie";
$productsResult = mysqli_query($connection, $productsQuery);

include_once '../../includes/header.php';
?>

<!-- Головне меню -->
<div class="row mb-4">
    <div class="col-md-12">
        <nav class="nav main-menu nav-pills nav-fill">
            <a class="nav-link" href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Головна
            </a>
            <a class="nav-link active" href="orders.php">
                <i class="fas fa-clipboard-list"></i> Замовлення
            </a>
            <a class="nav-link" href="clients.php">
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

<!-- Форма додавання замовлення -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-plus-circle me-2"></i> Додавання нового замовлення
        </h5>
    </div>
    <div class="card-body">
        <form action="" method="POST" class="needs-validation" novalidate>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="idklient" class="form-label">Клієнт *</label>
                    <select class="form-select" id="idklient" name="idklient" required>
                        <option value="">Виберіть клієнта</option>
                        <?php while ($client = mysqli_fetch_assoc($clientsResult)): ?>
                            <option value="<?php echo $client['id']; ?>"><?php echo htmlspecialchars($client['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                    <div class="invalid-feedback">
                        Будь ласка, виберіть клієнта
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label for="id" class="form-label">Продукт *</label>
                    <select class="form-select" id="id" name="id" required>
                        <option value="">Виберіть продукт</option>
                        <?php while ($product = mysqli_fetch_assoc($productsResult)): ?>
                            <option value="<?php echo $product['id']; ?>" data-price="<?php echo $product['zena']; ?>">
                                <?php echo htmlspecialchars($product['nazvanie']); ?> (<?php echo $product['zena']; ?> грн)
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <div class="invalid-feedback">
                        Будь ласка, виберіть продукт
                    </div>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="kol" class="form-label">Кількість *</label>
                    <input type="number" class="form-control" id="kol" name="kol" min="1" required>
                    <div class="invalid-feedback">
                        Кількість повинна бути більше нуля
                    </div>
                </div>
                
                <div class="col-md-4">
                    <label for="data" class="form-label">Дата *</label>
                    <input type="date" class="form-control" id="data" name="data" value="<?php echo date('Y-m-d'); ?>" required>
                    <div class="invalid-feedback">
                        Будь ласка, виберіть дату
                    </div>
                </div>
                
                <div class="col-md-4">
                    <label for="doba" class="form-label">Зміна *</label>
                    <select class="form-select" id="doba" name="doba" required>
                        <option value="денна">Денна</option>
                        <option value="нічна">Нічна</option>
                    </select>
                    <div class="invalid-feedback">
                        Будь ласка, виберіть зміну
                    </div>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="mb-3">Інформація про замовлення</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-2">Ціна за одиницю: <span id="unit-price" class="fw-bold">0.00 грн</span></div>
                                    <div>Загальна вартість: <span id="total-price" class="fw-bold">0.00 грн</span></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-2">Дата доставки: <span id="delivery-date" class="fw-bold">-</span></div>
                                    <div>Зміна доставки: <span id="delivery-shift" class="fw-bold">-</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="orders.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Повернутися
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Зберегти замовлення
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Оновлення інформації про замовлення
    document.addEventListener('DOMContentLoaded', function() {
        const productSelect = document.getElementById('id');
        const quantityInput = document.getElementById('kol');
        const dateInput = document.getElementById('data');
        const shiftSelect = document.getElementById('doba');
        
        const unitPriceSpan = document.getElementById('unit-price');
        const totalPriceSpan = document.getElementById('total-price');
        const deliveryDateSpan = document.getElementById('delivery-date');
        const deliveryShiftSpan = document.getElementById('delivery-shift');
        
        function updateOrderInfo() {
            // Отримання ціни продукту
            const selectedOption = productSelect.options[productSelect.selectedIndex];
            const price = selectedOption ? parseFloat(selectedOption.dataset.price || 0) : 0;
            
            // Отримання кількості
            const quantity = parseInt(quantityInput.value || 0);
            
            // Розрахунок загальної вартості
            const totalPrice = price * quantity;
            
            // Оновлення відображення ціни
            unitPriceSpan.textContent = price.toFixed(2) + ' грн';
            totalPriceSpan.textContent = totalPrice.toFixed(2) + ' грн';
            
            // Форматування дати доставки
            const dateValue = dateInput.value;
            if (dateValue) {
                const date = new Date(dateValue);
                deliveryDateSpan.textContent = date.toLocaleDateString('uk-UA');
            } else {
                deliveryDateSpan.textContent = '-';
            }
            
            // Зміна доставки
            deliveryShiftSpan.textContent = shiftSelect.value === 'денна' ? 'Денна' : 'Нічна';
        }
        
        // Відстеження змін у формі
        productSelect.addEventListener('change', updateOrderInfo);
        quantityInput.addEventListener('input', updateOrderInfo);
        dateInput.addEventListener('change', updateOrderInfo);
        shiftSelect.addEventListener('change', updateOrderInfo);
        
        // Ініціалізація при завантаженні сторінки
        updateOrderInfo();
    });
</script>

<?php
include_once '../../includes/footer.php';
?>