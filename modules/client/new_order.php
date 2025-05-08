<?php
$pageTitle = 'Нове замовлення';

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Перевірка доступу
if (!checkAccess(['client'])) {
    header("Location: ../../index.php");
    exit;
}

$connection = connectDatabase();
$success = '';
$error = '';

// Отримання даних клієнта
$clientId = $_SESSION['id'];

$clientQuery = "SELECT * FROM klientu WHERE id = ?";
$stmt = mysqli_prepare($connection, $clientQuery);
mysqli_stmt_bind_param($stmt, "i", $clientId);
mysqli_stmt_execute($stmt);
$clientResult = mysqli_stmt_get_result($stmt);
$client = mysqli_fetch_assoc($clientResult);

// Отримання списку продуктів
$productsQuery = "SELECT * FROM product ORDER BY nazvanie";
$productsResult = mysqli_query($connection, $productsQuery);

// Обробка форми створення замовлення
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $products = $_POST['products'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    $data = $_POST['data'] ?? date('Y-m-d');
    $doba = $_POST['doba'] ?? 'денна';
    
    // Валідація даних
    if (empty($products) || empty($quantities)) {
        $error = 'Виберіть хоча б один продукт';
    } else {
        // Лічильник успішних вставок
        $successCount = 0;
        
        // Вставка кожного замовленого продукту
        for ($i = 0; $i < count($products); $i++) {
            if (empty($products[$i]) || empty($quantities[$i]) || $quantities[$i] <= 0) {
                continue;
            }
            
            $productId = intval($products[$i]);
            $quantity = intval($quantities[$i]);
            
            $query = "INSERT INTO zayavki (idklient, id, kol, data, doba, status) VALUES (?, ?, ?, ?, ?, 'нове')";
            $stmt = mysqli_prepare($connection, $query);
            mysqli_stmt_bind_param($stmt, "iiiss", $clientId, $productId, $quantity, $data, $doba);
            
            if (mysqli_stmt_execute($stmt)) {
                $successCount++;
            } else {
                $error = 'Помилка при створенні замовлення: ' . mysqli_error($connection);
                break;
            }
            
            mysqli_stmt_close($stmt);
        }
        
        if ($successCount > 0) {
            $success = 'Замовлення успішно створено!';
            
            // Перенаправлення на сторінку замовлень після успішного створення
            header("Location: orders.php?success=" . urlencode($success));
            exit;
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
            <a class="nav-link" href="profile.php">
                <i class="fas fa-user"></i> Профіль
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

<!-- Форма нового замовлення -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-plus-circle me-2"></i> Створення нового замовлення
        </h5>
    </div>
    <div class="card-body">
        <form action="" method="POST" id="order-form" class="needs-validation" novalidate>
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Інформація про замовлення</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="client-name" class="form-label">Клієнт</label>
                                <input type="text" class="form-control" id="client-name" value="<?php echo htmlspecialchars($client['name']); ?>" readonly>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="data" class="form-label">Дата доставки</label>
                                    <input type="date" class="form-control" id="data" name="data" value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                                    <div class="invalid-feedback">
                                        Виберіть дату доставки (мінімум завтрашній день)
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="doba" class="form-label">Зміна доставки</label>
                                    <select class="form-select" id="doba" name="doba" required>
                                        <option value="денна">Денна (8:00 - 14:00)</option>
                                        <option value="нічна">Нічна (22:00 - 4:00)</option>
                                    </select>
                                    <div class="invalid-feedback">
                                        Виберіть зміну доставки
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="delivery-address" class="form-label">Адреса доставки</label>
                                <textarea class="form-control" id="delivery-address" rows="2" readonly><?php echo htmlspecialchars($client['city'] . ', ' . $client['adres']); ?></textarea>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i> Щоб змінити адресу доставки, оновіть дані у <a href="profile.php">профілі</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h6 class="mb-0">Загальна інформація</h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> Мінімальний час для оформлення замовлення - 1 день.
                            </div>
                            
                            <p><i class="fas fa-truck me-2"></i> <strong>Доставка:</strong> Доставка здійснюється власним транспортом підприємства.</p>
                            <p><i class="fas fa-money-bill-wave me-2"></i> <strong>Оплата:</strong> Безготівковий розрахунок або за фактом отримання.</p>
                            <p><i class="fas fa-box me-2"></i> <strong>Мінімальне замовлення:</strong> від 200 грн.</p>
                            
                            <div class="alert alert-warning mt-3">
                                <i class="fas fa-exclamation-triangle me-2"></i> Скасування замовлення можливе не пізніше ніж за 6 годин до доставки.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Вибір продуктів</h6>
                        <button type="button" id="add-product-row" class="btn btn-sm btn-success">
                            <i class="fas fa-plus me-1"></i> Додати ще продукт
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="products-container">
                        <div class="row mb-3 product-row">
                            <div class="col-md-6">
                                <select class="form-select product-select" name="products[]" required>
                                    <option value="">-- Виберіть продукт --</option>
                                    <?php 
                                    // Перезапуск результату запиту
                                    mysqli_data_seek($productsResult, 0);
                                    while ($product = mysqli_fetch_assoc($productsResult)): 
                                    ?>
                                        <option value="<?php echo $product['id']; ?>" data-price="<?php echo $product['zena']; ?>"><?php echo htmlspecialchars($product['nazvanie']); ?> (<?php echo $product['zena']; ?> грн)</option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="invalid-feedback">
                                    Виберіть продукт
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="input-group">
                                    <input type="number" class="form-control quantity-input" name="quantities[]" placeholder="Кількість" min="1" required>
                                    <span class="input-group-text">шт.</span>
                                    <div class="invalid-feedback">
                                        Вкажіть кількість
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <span class="form-control product-total">0.00 грн</span>
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-danger remove-product" <?php echo (mysqli_num_rows($productsResult) > 0) ? '' : 'disabled'; ?>>
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-9 text-end">
                            <strong>Загальна сума:</strong>
                        </div>
                        <div class="col-md-2">
                            <strong id="total-amount">0.00 грн</strong>
                        </div>
                        <div class="col-md-1"></div>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Повернутися
                </a>
                <button type="submit" class="btn btn-primary" id="submit-order">
                    <i class="fas fa-save me-1"></i> Оформити замовлення
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Функція оновлення загальної суми
        function updateTotals() {
            let totalAmount = 0;
            
            document.querySelectorAll('.product-row').forEach(function(row) {
                const select = row.querySelector('.product-select');
                const quantity = row.querySelector('.quantity-input');
                const totalSpan = row.querySelector('.product-total');
                
                if (select.selectedIndex > 0 && quantity.value > 0) {
                    const price = parseFloat(select.options[select.selectedIndex].dataset.price);
                    const qty = parseInt(quantity.value);
                    const rowTotal = price * qty;
                    
                    totalSpan.textContent = rowTotal.toFixed(2) + ' грн';
                    totalAmount += rowTotal;
                } else {
                    totalSpan.textContent = '0.00 грн';
                }
            });
            
            document.getElementById('total-amount').textContent = totalAmount.toFixed(2) + ' грн';
            
            // Деактивація кнопки видалення, якщо залишився тільки один рядок
            const removeButtons = document.querySelectorAll('.remove-product');
            if (removeButtons.length === 1) {
                removeButtons[0].disabled = true;
            } else {
                removeButtons.forEach(button => button.disabled = false);
            }
        }
        
        // Функція додавання нового рядка з продуктом
        document.getElementById('add-product-row').addEventListener('click', function() {
            const container = document.getElementById('products-container');
            const firstRow = container.querySelector('.product-row');
            const newRow = firstRow.cloneNode(true);
            
            // Очищення значень у новому рядку
            newRow.querySelector('.product-select').selectedIndex = 0;
            newRow.querySelector('.quantity-input').value = '';
            newRow.querySelector('.product-total').textContent = '0.00 грн';
            
            // Додавання обробників подій для нового рядка
            newRow.querySelector('.product-select').addEventListener('change', updateTotals);
            newRow.querySelector('.quantity-input').addEventListener('input', updateTotals);
            newRow.querySelector('.remove-product').addEventListener('click', function() {
                newRow.remove();
                updateTotals();
            });
            
            container.appendChild(newRow);
            updateTotals();
        });
        
        // Додавання обробників подій для існуючих рядків
        document.querySelectorAll('.product-select').forEach(select => {
            select.addEventListener('change', updateTotals);
        });
        
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('input', updateTotals);
        });
        
        document.querySelectorAll('.remove-product').forEach(button => {
            button.addEventListener('click', function() {
                if (document.querySelectorAll('.product-row').length > 1) {
                    this.closest('.product-row').remove();
                    updateTotals();
                }
            });
        });
        
        // Валідація форми
        document.getElementById('order-form').addEventListener('submit', function(event) {
            if (!this.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            this.classList.add('was-validated');
            
            // Перевірка на мінімальну суму замовлення
            const totalAmount = parseFloat(document.getElementById('total-amount').textContent);
            if (totalAmount < 200) {
                event.preventDefault();
                alert('Мінімальна сума замовлення - 200 грн.');
            }
        });
        
        // Ініціалізація
        updateTotals();
    });
</script>

<?php
include_once '../../includes/footer.php';
?>