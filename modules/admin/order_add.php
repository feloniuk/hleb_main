<?php
// This file contains the missing components for the admin module
// I'll organize it into sections for each missing functionality

/************************************************
 * 1. ORDER_ADD.PHP - Create new order
 ************************************************/

$pageTitle = 'Додавання нового замовлення';

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

// Отримання параметрів з URL
$clientId = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;

// Обробка форми
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Отримання даних з форми
    $clientId = isset($_POST['client_id']) ? intval($_POST['client_id']) : 0;
    $productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
    $orderDate = isset($_POST['order_date']) ? $_POST['order_date'] : date('Y-m-d');
    $shift = isset($_POST['shift']) ? $_POST['shift'] : 'денна';
    
    // Валідація даних
    if ($clientId <= 0) {
        $error = 'Будь ласка, виберіть клієнта';
    } elseif ($productId <= 0) {
        $error = 'Будь ласка, виберіть продукт';
    } elseif ($quantity <= 0) {
        $error = 'Будь ласка, введіть коректну кількість';
    } elseif (empty($orderDate)) {
        $error = 'Будь ласка, виберіть дату замовлення';
    } else {
        // Перевірка наявності клієнта
        $clientQuery = "SELECT id FROM klientu WHERE id = ?";
        $stmt = mysqli_prepare($connection, $clientQuery);
        mysqli_stmt_bind_param($stmt, "i", $clientId);
        mysqli_stmt_execute($stmt);
        $clientResult = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($clientResult) == 0) {
            $error = 'Вибраний клієнт не існує';
        } else {
            // Перевірка наявності продукту
            $productQuery = "SELECT id FROM product WHERE id = ?";
            $stmt = mysqli_prepare($connection, $productQuery);
            mysqli_stmt_bind_param($stmt, "i", $productId);
            mysqli_stmt_execute($stmt);
            $productResult = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($productResult) == 0) {
                $error = 'Вибраний продукт не існує';
            } else {
                // Додавання замовлення
                $insertQuery = "INSERT INTO zayavki (idklient, id, kol, data, doba, status) VALUES (?, ?, ?, ?, ?, 'нове')";
                $stmt = mysqli_prepare($connection, $insertQuery);
                mysqli_stmt_bind_param($stmt, "iiiss", $clientId, $productId, $quantity, $orderDate, $shift);
                
                if (mysqli_stmt_execute($stmt)) {
                    $orderId = mysqli_insert_id($connection);
                    $success = 'Замовлення успішно створено';
                    
                    // Запис в журнал
                    logAction($connection, 'Створення замовлення', 'Створено нове замовлення ID: ' . $orderId);
                    
                    // Перенаправлення на сторінку замовлень або деталі замовлення
                    header("Location: order_details.php?id=$orderId&success=" . urlencode($success));
                    exit;
                } else {
                    $error = 'Помилка при створенні замовлення: ' . mysqli_error($connection);
                }
                
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// Отримання списку клієнтів
$clientsQuery = "SELECT id, name FROM klientu ORDER BY name";
$clientsResult = mysqli_query($connection, $clientsQuery);

// Отримання списку продуктів
$productsQuery = "SELECT id, nazvanie, ves, zena FROM product ORDER BY nazvanie";
$productsResult = mysqli_query($connection, $productsQuery);

// Дані обраного клієнта
$clientData = null;
if ($clientId > 0) {
    $clientDataQuery = "SELECT * FROM klientu WHERE id = ?";
    $stmt = mysqli_prepare($connection, $clientDataQuery);
    mysqli_stmt_bind_param($stmt, "i", $clientId);
    mysqli_stmt_execute($stmt);
    $clientDataResult = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($clientDataResult) > 0) {
        $clientData = mysqli_fetch_assoc($clientDataResult);
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
            <a class="nav-link" href="products.php">
                <i class="fas fa-bread-slice"></i> Продукція
            </a>
            <a class="nav-link active" href="orders.php">
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

<!-- Форма створення замовлення -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-clipboard-plus me-2"></i> Створення нового замовлення
        </h5>
    </div>
    <div class="card-body">
        <form action="" method="POST" class="needs-validation" novalidate>
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Клієнт</h6>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="client_id" class="form-label">Виберіть клієнта *</label>
                                    <select class="form-select" id="client_id" name="client_id" required>
                                        <option value="">-- Виберіть клієнта --</option>
                                        <?php 
                                        mysqli_data_seek($clientsResult, 0);
                                        while ($client = mysqli_fetch_assoc($clientsResult)) {
                                            $selected = ($clientId == $client['id']) ? 'selected' : '';
                                            echo '<option value="' . $client['id'] . '" ' . $selected . '>' . htmlspecialchars($client['name']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                    <div class="invalid-feedback">
                                        Будь ласка, виберіть клієнта
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Інформація про обраного клієнта -->
                            <div id="client_info" class="row" style="<?php echo ($clientData) ? '' : 'display: none;'; ?>">
                                <div class="col-md-12">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p><strong>Назва:</strong> <span id="client_name"><?php echo htmlspecialchars($clientData['name'] ?? ''); ?></span></p>
                                                    <p><strong>Контактна особа:</strong> <span id="client_fio"><?php echo htmlspecialchars($clientData['fio'] ?? ''); ?></span></p>
                                                    <p><strong>Телефон:</strong> <span id="client_tel"><?php echo htmlspecialchars($clientData['tel'] ?? ''); ?></span></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong>Місто:</strong> <span id="client_city"><?php echo htmlspecialchars($clientData['city'] ?? ''); ?></span></p>
                                                    <p><strong>Адреса:</strong> <span id="client_adres"><?php echo htmlspecialchars($clientData['adres'] ?? ''); ?></span></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Деталі замовлення</h6>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="product_id" class="form-label">Продукт *</label>
                                    <select class="form-select" id="product_id" name="product_id" required>
                                        <option value="">-- Виберіть продукт --</option>
                                        <?php 
                                        mysqli_data_seek($productsResult, 0);
                                        while ($product = mysqli_fetch_assoc($productsResult)) {
                                            echo '<option value="' . $product['id'] . '" data-price="' . $product['zena'] . '" data-weight="' . $product['ves'] . '">' . 
                                                 htmlspecialchars($product['nazvanie']) . ' (' . $product['ves'] . ' кг, ' . $product['zena'] . ' грн)' . '</option>';
                                        }
                                        ?>
                                    </select>
                                    <div class="invalid-feedback">
                                        Будь ласка, виберіть продукт
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="quantity" class="form-label">Кількість *</label>
                                    <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                                    <div class="invalid-feedback">
                                        Будь ласка, введіть кількість
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="total_amount" class="form-label">Сума (грн)</label>
                                    <input type="text" class="form-control" id="total_amount" readonly>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="order_date" class="form-label">Дата замовлення *</label>
                                    <input type="date" class="form-control" id="order_date" name="order_date" value="<?php echo date('Y-m-d'); ?>" required>
                                    <div class="invalid-feedback">
                                        Будь ласка, виберіть дату замовлення
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="shift" class="form-label">Зміна *</label>
                                    <select class="form-select" id="shift" name="shift" required>
                                        <option value="денна">Денна</option>
                                        <option value="нічна">Нічна</option>
                                    </select>
                                    <div class="invalid-feedback">
                                        Будь ласка, виберіть зміну
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="total_weight" class="form-label">Загальна вага (кг)</label>
                                    <input type="text" class="form-control" id="total_weight" readonly>
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
                    <i class="fas fa-save me-1"></i> Створити замовлення
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Завантаження даних клієнта при зміні
        document.getElementById('client_id').addEventListener('change', function() {
            var clientId = this.value;
            
            if (clientId) {
                // AJAX-запит для отримання даних клієнта
                fetch('get_client_data.php?id=' + clientId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('client_info').style.display = 'block';
                            document.getElementById('client_name').textContent = data.client.name || '';
                            document.getElementById('client_fio').textContent = data.client.fio || '';
                            document.getElementById('client_tel').textContent = data.client.tel || '';
                            document.getElementById('client_city').textContent = data.client.city || '';
                            document.getElementById('client_adres').textContent = data.client.adres || '';
                        } else {
                            document.getElementById('client_info').style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching client data:', error);
                        document.getElementById('client_info').style.display = 'none';
                    });
            } else {
                document.getElementById('client_info').style.display = 'none';
            }
        });
        
        // Розрахунок суми при зміні продукту або кількості
        function calculateTotal() {
            var productSelect = document.getElementById('product_id');
            var quantityInput = document.getElementById('quantity');
            var totalAmountInput = document.getElementById('total_amount');
            var totalWeightInput = document.getElementById('total_weight');
            
            var selectedOption = productSelect.options[productSelect.selectedIndex];
            var price = selectedOption ? parseFloat(selectedOption.getAttribute('data-price')) || 0 : 0;
            var weight = selectedOption ? parseFloat(selectedOption.getAttribute('data-weight')) || 0 : 0;
            var quantity = parseInt(quantityInput.value) || 0;
            
            var totalAmount = price * quantity;
            var totalWeight = weight * quantity;
            
            totalAmountInput.value = totalAmount.toFixed(2) + ' грн';
            totalWeightInput.value = totalWeight.toFixed(2) + ' кг';
        }
        
        document.getElementById('product_id').addEventListener('change', calculateTotal);
        document.getElementById('quantity').addEventListener('input', calculateTotal);
        
        // Ініціалізація розрахунку
        calculateTotal();
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