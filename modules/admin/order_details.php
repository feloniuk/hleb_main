<?php 
$pageTitle = 'Деталі замовлення';

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
$query = "SELECT z.*, k.name as client_name, k.fio, k.tel, k.city, k.adres, 
           p.nazvanie as product_name, p.ves, p.zena, p.image
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

// Розрахунок деяких даних
$totalPrice = $order['kol'] * $order['zena'];
$totalWeight = $order['kol'] * $order['ves'];

// Обробка зміни статусу
if (isset($_POST['update_status'])) {
    $newStatus = $_POST['status'];
    
    $updateQuery = "UPDATE zayavki SET status = ? WHERE idd = ?";
    $stmt = mysqli_prepare($connection, $updateQuery);
    mysqli_stmt_bind_param($stmt, "si", $newStatus, $orderId);
    
    if (mysqli_stmt_execute($stmt)) {
        $success = 'Статус замовлення успішно оновлено';
        
        // Оновлення даних замовлення в змінній
        $order['status'] = $newStatus;
        
        // Запис в журнал
        logAction($connection, 'Оновлення статусу замовлення', "Замовлення ID: $orderId, новий статус: $newStatus");
    } else {
        $error = 'Помилка при оновленні статусу замовлення: ' . mysqli_error($connection);
    }
    
    mysqli_stmt_close($stmt);
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

<!-- Заголовок та кнопки дій -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-clipboard-list me-2"></i> Деталі замовлення #<?php echo $orderId; ?>
            </h5>
            <div>
                <a href="order_edit.php?id=<?php echo $orderId; ?>" class="btn btn-warning">
                    <i class="fas fa-edit me-1"></i> Редагувати
                </a>
                <a href="order_print.php?id=<?php echo $orderId; ?>" class="btn btn-primary ms-2" target="_blank">
                    <i class="fas fa-print me-1"></i> Друк
                </a>
                <a href="orders.php" class="btn btn-secondary ms-2">
                    <i class="fas fa-arrow-left me-1"></i> Повернутися
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Інформація про замовлення -->
<div class="row">
    <div class="col-md-8 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i> Інформація про замовлення
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Номер замовлення:</strong> #<?php echo $orderId; ?></p>
                        <p><strong>Дата замовлення:</strong> <?php echo formatDate($order['data']); ?></p>
                        <p><strong>Зміна:</strong> <?php echo $order['doba']; ?></p>
                        <p>
                            <strong>Статус:</strong>
                            <span class="badge
                                <?php
                                switch ($order['status']) {
                                    case 'нове':
                                        echo 'bg-info';
                                        break;
                                    case 'в обробці':
                                        echo 'bg-warning';
                                        break;
                                    case 'виконано':
                                        echo 'bg-success';
                                        break;
                                    case 'скасовано':
                                        echo 'bg-danger';
                                        break;
                                    default:
                                        echo 'bg-secondary';
                                        break;
                                }
                                ?>
                            ">
                                <?php echo $order['status']; ?>
                            </span>
                            
                            <button type="button" class="btn btn-sm btn-outline-primary ms-2" data-bs-toggle="modal" data-bs-target="#changeStatusModal">
                                <i class="fas fa-exchange-alt me-1"></i> Змінити статус
                            </button>
                        </p>
                    </div>
                    
                    <div class="col-md-6">
                        <p><strong>Клієнт:</strong> <?php echo htmlspecialchars($order['client_name']); ?></p>
                        <p><strong>Контактна особа:</strong> <?php echo htmlspecialchars($order['fio']); ?></p>
                        <p><strong>Телефон:</strong> <?php echo htmlspecialchars($order['tel']); ?></p>
                        <p><strong>Адреса:</strong> <?php echo htmlspecialchars($order['city'] . ', ' . $order['adres']); ?></p>
                    </div>
                </div>
                
                <hr>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Продукт</th>
                                <th>Кількість</th>
                                <th>Вага од. (кг)</th>
                                <th>Загальна вага (кг)</th>
                                <th>Ціна од. (грн)</th>
                                <th>Загальна сума (грн)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                <td><?php echo $order['kol']; ?></td>
                                <td><?php echo $order['ves']; ?></td>
                                <td><?php echo number_format($totalWeight, 2); ?></td>
                                <td><?php echo number_format($order['zena'], 2); ?></td>
                                <td><?php echo number_format($totalPrice, 2); ?></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3">Всього:</th>
                                <th><?php echo number_format($totalWeight, 2); ?> кг</th>
                                <th></th>
                                <th><?php echo number_format($totalPrice, 2); ?> грн</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-bread-slice me-2"></i> Інформація про продукт
                </h5>
            </div>
            <div class="card-body text-center">
                <?php
                $imagePath = !empty($order['image']) ? '../../' . $order['image'] : '../../assets/img/product-placeholder.jpg';
                ?>
                <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($order['product_name']); ?>" class="img-fluid mb-3" style="max-height: 200px;">
                
                <h5><?php echo htmlspecialchars($order['product_name']); ?></h5>
                <div class="mt-3">
                    <p><strong>Вага одиниці:</strong> <?php echo $order['ves']; ?> кг</p>
                    <p><strong>Ціна одиниці:</strong> <?php echo number_format($order['zena'], 2); ?> грн</p>
                </div>
                
                <a href="product_details.php?id=<?php echo $order['id']; ?>" class="btn btn-info">
                    <i class="fas fa-info-circle me-1"></i> Деталі продукту
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Модальне вікно зміни статусу -->
<div class="modal fade" id="changeStatusModal" tabindex="-1" aria-labelledby="changeStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changeStatusModalLabel">Зміна статусу замовлення</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="status" class="form-label">Новий статус</label>
                        <select class="form-select" id="status" name="status">
                            <option value="нове" <?php echo ($order['status'] == 'нове') ? 'selected' : ''; ?>>Нове</option>
                            <option value="в обробці" <?php echo ($order['status'] == 'в обробці') ? 'selected' : ''; ?>>В обробці</option>
                            <option value="виконано" <?php echo ($order['status'] == 'виконано') ? 'selected' : ''; ?>>Виконано</option>
                            <option value="скасовано" <?php echo ($order['status'] == 'скасовано') ? 'selected' : ''; ?>>Скасовано</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                    <button type="submit" name="update_status" class="btn btn-primary">Зберегти</button>
                </div>
            </form>
        </div>
    </div>
</div>

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