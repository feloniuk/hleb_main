<?php 
$pageTitle = 'Редагування замовлення';

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

// Перевірка наявності ID замовлення
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: orders.php");
    exit;
}

$orderId = intval($_GET['id']);

// Отримання даних замовлення
$query = "SELECT z.*, k.name as client_name, p.nazvanie as product_name, p.zena
          FROM zayavki z
          JOIN klientu k ON z.idklient = k.id
          JOIN product p ON z.id = p.id
          WHERE z.idd = ?";
$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, "i", $orderId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) != 1) {
    header("Location: orders.php");
    exit;
}

$order = mysqli_fetch_assoc($result);

// Обробка форми
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Отримання даних з форми
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
    $orderDate = isset($_POST['order_date']) ? $_POST['order_date'] : '';
    $shift = isset($_POST['shift']) ? $_POST['shift'] : '';
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    
    // Валідація даних
    if ($quantity <= 0) {
        $error = 'Будь ласка, введіть коректну кількість';
    } elseif (empty($orderDate)) {
        $error = 'Будь ласка, виберіть дату замовлення';
    } elseif (empty($shift)) {
        $error = 'Будь ласка, виберіть зміну';
    } elseif (empty($status)) {
        $error = 'Будь ласка, виберіть статус';
    } else {
        // Оновлення замовлення
        $updateQuery = "UPDATE zayavki SET kol = ?, data = ?, doba = ?, status = ? WHERE idd = ?";
        $stmt = mysqli_prepare($connection, $updateQuery);
        mysqli_stmt_bind_param($stmt, "isssi", $quantity, $orderDate, $shift, $status, $orderId);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = 'Замовлення успішно оновлено';
            
            // Запис в журнал
            logAction($connection, 'Оновлення замовлення', 'Оновлено замовлення ID: ' . $orderId);
            
            // Перенаправлення на сторінку деталей замовлення
            header("Location: order_details.php?id=$orderId&success=" . urlencode($success));
            exit;
        } else {
            $error = 'Помилка при оновленні замовлення: ' . mysqli_error($connection);
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

<!-- Форма редагування замовлення -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-edit me-2"></i> Редагування замовлення #<?php echo $orderId; ?>
        </h5>
    </div>
    <div class="card-body">
        <form action="" method="POST" class="needs-validation" novalidate>
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Інформація про замовлення</h6>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Клієнт</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($order['client_name']); ?>" readonly>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Продукт</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($order['product_name']); ?>" readonly>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="quantity" class="form-label">Кількість *</label>
                                    <input type="number" class="form-control" id="quantity" name="quantity" min="1" value="<?php echo $order['kol']; ?>" required>
                                    <div class="invalid-feedback">
                                        Будь ласка, введіть кількість
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="total_amount" class="form-label">Загальна сума (грн)</label>
                                    <input type="text" class="form-control" id="total_amount" readonly value="<?php echo number_format($order['kol'] * $order['zena'], 2); ?> грн">
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="status" class="form-label">Статус *</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="нове" <?php echo ($order['status'] == 'нове') ? 'selected' : ''; ?>>Нове</option>
                                        <option value="в обробці" <?php echo ($order['status'] == 'в обробці') ? 'selected' : ''; ?>>В обробці</option>
                                        <option value="виконано" <?php echo ($order['status'] == 'виконано') ? 'selected' : ''; ?>>Виконано</option>
                                        <option value="скасовано" <?php echo ($order['status'] == 'скасовано') ? 'selected' : ''; ?>>Скасовано</option>
                                    </select>
                                    <div class="invalid-feedback">
                                        Будь ласка, виберіть статус
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="order_date" class="form-label">Дата замовлення *</label>
                                    <input type="date" class="form-control" id="order_date" name="order_date" value="<?php echo $order['data']; ?>" required>
                                    <div class="invalid-feedback">
                                        Будь ласка, виберіть дату замовлення
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="shift" class="form-label">Зміна *</label>
                                    <select class="form-select" id="shift" name="shift" required>
                                        <option value="денна" <?php echo ($order['doba'] == 'денна') ? 'selected' : ''; ?>>Денна</option>
                                        <option value="нічна" <?php echo ($order['doba'] == 'нічна') ? 'selected' : ''; ?>>Нічна</option>
                                    </select>
                                    <div class="invalid-feedback">
                                        Будь ласка, виберіть зміну
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="order_details.php?id=<?php echo $orderId; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Повернутися
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Зберегти зміни
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Розрахунок суми при зміні кількості
        var quantityInput = document.getElementById('quantity');
        var totalAmountInput = document.getElementById('total_amount');
        var pricePerUnit = <?php echo $order['zena']; ?>;
        
        quantityInput.addEventListener('input', function() {
            var quantity = parseInt(this.value) || 0;
            var totalAmount = quantity * pricePerUnit;
            totalAmountInput.value = totalAmount.toFixed(2) + ' грн';
        });
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