<?php
$pageTitle = 'Деталі замовлення';

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

// Отримання ID замовлення
$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($orderId <= 0) {
    $error = 'Не вказано ID замовлення';
} else {
    // Отримання даних замовлення
    $orderQuery = "
        SELECT z.*, k.name as client_name, k.fio, k.tel, k.city, k.adres, 
               p.nazvanie as product_name, p.ves, p.zena, p.image
        FROM zayavki z
        JOIN klientu k ON z.idklient = k.id
        JOIN product p ON z.id = p.id
        WHERE z.idd = ?
    ";
    $stmt = mysqli_prepare($connection, $orderQuery);
    mysqli_stmt_bind_param($stmt, "i", $orderId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 0) {
        $error = 'Замовлення не знайдено';
    } else {
        $order = mysqli_fetch_assoc($result);
    }
}

// Обробка оновлення статусу замовлення
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = $_POST['status'];
    
    $updateQuery = "UPDATE zayavki SET status = ? WHERE idd = ?";
    $stmt = mysqli_prepare($connection, $updateQuery);
    mysqli_stmt_bind_param($stmt, "si", $newStatus, $orderId);
    
    if (mysqli_stmt_execute($stmt)) {
        $success = 'Статус замовлення успішно оновлено';
        
        // Оновлення статусу в об'єкті замовлення
        $order['status'] = $newStatus;
    } else {
        $error = 'Помилка при оновленні статусу: ' . mysqli_error($connection);
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

<?php if (isset($order)): ?>
<!-- Деталі замовлення -->
<div class="card mb-4">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-clipboard-list me-2"></i> Деталі замовлення #<?php echo $orderId; ?>
            </h5>
            <div>
                <a href="order_edit.php?id=<?php echo $orderId; ?>" class="btn btn-warning">
                    <i class="fas fa-edit me-1"></i> Редагувати
                </a>
                <a href="order_print.php?id=<?php echo $orderId; ?>" class="btn btn-primary ms-2" target="_blank">
                    <i class="fas fa-print me-1"></i> Друкувати
                </a>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">Інформація про замовлення</h6>
                    </div>
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-md-4 fw-bold">ID замовлення:</div>
                            <div class="col-md-8"><?php echo $order['idd']; ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4 fw-bold">Дата:</div>
                            <div class="col-md-8"><?php echo formatDate($order['data']); ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4 fw-bold">Зміна:</div>
                            <div class="col-md-8">
                                <?php if ($order['doba'] == 'денна'): ?>
                                <span class="badge shift-badge shift-day">Денна</span>
                                <?php else: ?>
                                <span class="badge shift-badge shift-night">Нічна</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4 fw-bold">Статус:</div>
                            <div class="col-md-8">
                                <?php
                                switch ($order['status']) {
                                    case 'нове':
                                        echo '<span class="badge bg-info">Нове</span>';
                                        break;
                                    case 'в обробці':
                                        echo '<span class="badge bg-warning">В обробці</span>';
                                        break;
                                    case 'виконано':
                                        echo '<span class="badge bg-success">Виконано</span>';
                                        break;
                                    case 'скасовано':
                                        echo '<span class="badge bg-danger">Скасовано</span>';
                                        break;
                                    default:
                                        echo '<span class="badge bg-secondary">Невідомо</span>';
                                        break;
                                }
                                ?>
                            </div>
                        </div>
                        
                        <!-- Форма зміни статусу -->
                        <hr>
                        <form action="" method="POST" class="mt-3">
                            <div class="input-group">
                                <select name="status" class="form-select">
                                    <option value="нове" <?php echo ($order['status'] == 'нове') ? 'selected' : ''; ?>>Нове</option>
                                    <option value="в обробці" <?php echo ($order['status'] == 'в обробці') ? 'selected' : ''; ?>>В обробці</option>
                                    <option value="виконано" <?php echo ($order['status'] == 'виконано') ? 'selected' : ''; ?>>Виконано</option>
                                    <option value="скасовано" <?php echo ($order['status'] == 'скасовано') ? 'selected' : ''; ?>>Скасовано</option>
                                </select>
                                <button type="submit" name="update_status" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Оновити статус
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">Інформація про клієнта</h6>
                    </div>
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-md-4 fw-bold">Назва компанії:</div>
                            <div class="col-md-8"><?php echo htmlspecialchars($order['client_name']); ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4 fw-bold">Контактна особа:</div>
                            <div class="col-md-8"><?php echo htmlspecialchars($order['fio']); ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4 fw-bold">Телефон:</div>
                            <div class="col-md-8"><?php echo htmlspecialchars($order['tel']); ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4 fw-bold">Адреса доставки:</div>
                            <div class="col-md-8">
                                <?php echo htmlspecialchars($order['city']); ?>, 
                                <?php echo htmlspecialchars($order['adres']); ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 text-end mt-3">
                                <a href="client_details.php?id=<?php echo $order['idklient']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-user me-1"></i> Детальніше про клієнта
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Деталі продукту</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <?php 
                                $imagePath = !empty($order['image']) ? '../../' . $order['image'] : '../../assets/img/product-placeholder.jpg';
                                ?>
                                <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($order['product_name']); ?>" class="img-fluid rounded mb-3" style="max-height: 150px;">
                            </div>
                            <div class="col-md-9">
                                <div class="row mb-2">
                                    <div class="col-md-3 fw-bold">Назва продукту:</div>
                                    <div class="col-md-9"><?php echo htmlspecialchars($order['product_name']); ?></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-md-3 fw-bold">Вага:</div>
                                    <div class="col-md-9"><?php echo $order['ves']; ?> кг</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-md-3 fw-bold">Ціна за одиницю:</div>
                                    <div class="col-md-9"><?php echo $order['zena']; ?> грн</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-md-3 fw-bold">Кількість:</div>
                                    <div class="col-md-9"><?php echo $order['kol']; ?> шт.</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-md-3 fw-bold">Загальна вага:</div>
                                    <div class="col-md-9"><?php echo number_format($order['ves'] * $order['kol'], 2); ?> кг</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-md-3 fw-bold">Загальна сума:</div>
                                    <div class="col-md-9 fw-bold text-success"><?php echo number_format($order['zena'] * $order['kol'], 2); ?> грн</div>
                                </div>
                                <div class="row">
                                    <div class="col-12 text-end mt-3">
                                        <a href="product_details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-bread-slice me-1"></i> Детальніше про продукт
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer text-center">
        <a href="orders.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Повернутися до списку замовлень
        </a>
    </div>
</div>
<?php endif; ?>

<?php
include_once '../../includes/footer.php';
?>