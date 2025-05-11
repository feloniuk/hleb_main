<?php
$pageTitle = 'Деталі замовлення';

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Перевірка доступу
if (!checkAccess(['client'])) {
    header("Location: ../../index.php");
    exit;
}

$connection = connectDatabase();
$clientId = $_SESSION['id'];

// Перевірка наявності ID замовлення
$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($orderId <= 0) {
    header("Location: orders.php");
    exit;
}

// Отримання інформації про замовлення
$orderQuery = "SELECT z.*, k.name as client_name, k.fio, k.tel, k.city, k.adres, 
                      p.nazvanie as product_name, p.ves, p.zena, p.image, (z.kol * p.zena) as total_price
               FROM zayavki z
               JOIN klientu k ON z.idklient = k.id
               JOIN product p ON z.id = p.id
               WHERE z.idd = ? AND z.idklient = ?";
$stmt = mysqli_prepare($connection, $orderQuery);
mysqli_stmt_bind_param($stmt, "ii", $orderId, $clientId);
mysqli_stmt_execute($stmt);
$orderResult = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($orderResult) === 0) {
    header("Location: orders.php");
    exit;
}

$order = mysqli_fetch_assoc($orderResult);

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

<!-- Навігаційні хлібні крихти -->
<div class="row mb-4">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Головна</a></li>
                <li class="breadcrumb-item"><a href="orders.php">Мої замовлення</a></li>
                <li class="breadcrumb-item active" aria-current="page">Замовлення #<?php echo $orderId; ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-clipboard-list me-2"></i> Деталі замовлення #<?php echo $orderId; ?>
            </h5>
            <div>
                <?php if ($order['status'] === 'нове'): ?>
                    <a href="cancel_order.php?id=<?php echo $orderId; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Ви впевнені, що хочете скасувати це замовлення?');">
                        <i class="fas fa-times me-1"></i> Скасувати
                    </a>
                <?php endif; ?>
                <a href="repeat_order.php?id=<?php echo $orderId; ?>" class="btn btn-sm btn-success">
                    <i class="fas fa-redo me-1"></i> Повторити
                </a>
                <a href="orders.php" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> До списку
                </a>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <h6 class="mb-3"><i class="fas fa-info-circle me-2"></i> Інформація про замовлення</h6>
                <table class="table table-bordered table-striped">
                    <tbody>
                        <tr>
                            <th scope="row" width="40%">Номер замовлення</th>
                            <td><?php echo $orderId; ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Статус</th>
                            <td>
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
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Дата доставки</th>
                            <td><?php echo formatDate($order['data']); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Зміна доставки</th>
                            <td><?php echo $order['doba'] === 'денна' ? 'Денна (8:00 - 14:00)' : 'Нічна (22:00 - 4:00)'; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="col-md-6">
                <h6 class="mb-3"><i class="fas fa-map-marker-alt me-2"></i> Інформація про доставку</h6>
                <table class="table table-bordered table-striped">
                    <tbody>
                        <tr>
                            <th scope="row" width="40%">Клієнт</th>
                            <td><?php echo htmlspecialchars($order['client_name']); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Контактна особа</th>
                            <td><?php echo htmlspecialchars($order['fio']); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Телефон</th>
                            <td><?php echo htmlspecialchars($order['tel']); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Адреса доставки</th>
                            <td><?php echo htmlspecialchars($order['city'] . ', ' . $order['adres']); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <h6 class="mb-3"><i class="fas fa-shopping-basket me-2"></i> Товари у замовленні</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Фото</th>
                                <th>Продукт</th>
                                <th>Ціна</th>
                                <th>Кількість</th>
                                <th>Вага (кг)</th>
                                <th>Сума</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td width="80">
                                    <?php 
                                    $imagePath = !empty($order['image']) ? '../../' . $order['image'] : '../../assets/img/product-placeholder.jpg';
                                    ?>
                                    <img src="<?php echo $imagePath; ?>" class="img-thumbnail" alt="<?php echo htmlspecialchars($order['product_name']); ?>" width="60">
                                </td>
                                <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                <td><?php echo number_format($order['zena'], 2); ?> грн</td>
                                <td><?php echo $order['kol']; ?> шт.</td>
                                <td><?php echo number_format($order['ves'] * $order['kol'], 2); ?> кг</td>
                                <td><?php echo number_format($order['total_price'], 2); ?> грн</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" class="text-end"><strong>Загальна сума:</strong></td>
                                <td><strong><?php echo number_format($order['total_price'], 2); ?> грн</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Статус замовлення і очікуваний час доставки -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-truck me-2"></i> Статус доставки
        </h5>
    </div>
    <div class="card-body">
        <?php
        // Визначення етапів доставки та поточного етапу
        $stages = [
            'нове' => 1,
            'в обробці' => 2,
            'виконано' => 3
        ];
        
        $currentStage = isset($stages[$order['status']]) ? $stages[$order['status']] : 0;
        
        // Якщо замовлення скасовано, показуємо відповідне повідомлення
        if ($order['status'] === 'скасовано') {
            echo '<div class="alert alert-danger text-center">';
            echo '<i class="fas fa-times-circle fa-2x mb-2"></i>';
            echo '<h5>Замовлення скасовано</h5>';
            echo '</div>';
        } else {
        ?>
            <div class="position-relative pt-4 pb-2">
                <!-- Лінія прогресу -->
                <div class="progress" style="height: 5px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo min(($currentStage - 1) * 50, 100); ?>%"></div>
                </div>
                
                <!-- Етапи доставки -->
                <div class="position-absolute" style="top: 0; left: 0; right: 0;">
                    <div class="row text-center">
                        <div class="col-4 position-relative">
                            <div class="rounded-circle <?php echo $currentStage >= 1 ? 'bg-success' : 'bg-secondary'; ?> text-white mx-auto d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                            <div class="mt-2"><small>Замовлення прийнято</small></div>
                        </div>
                        <div class="col-4 position-relative">
                            <div class="rounded-circle <?php echo $currentStage >= 2 ? 'bg-success' : 'bg-secondary'; ?> text-white mx-auto d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fas fa-bread-slice"></i>
                            </div>
                            <div class="mt-2"><small>Підготовка замовлення</small></div>
                        </div>
                        <div class="col-4 position-relative">
                            <div class="rounded-circle <?php echo $currentStage >= 3 ? 'bg-success' : 'bg-secondary'; ?> text-white mx-auto d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fas fa-truck"></i>
                            </div>
                            <div class="mt-2"><small>Доставлено</small></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 text-center">
                <p class="mb-2">
                    <i class="fas fa-clock me-1"></i> Очікуваний час доставки:
                    <strong>
                        <?php 
                        echo formatDate($order['data']); 
                        echo ' • ';
                        echo $order['doba'] === 'денна' ? '8:00 - 14:00' : '22:00 - 4:00';
                        ?>
                    </strong>
                </p>
                
                <?php if ($order['status'] === 'нове'): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> Ваше замовлення прийнято і буде оброблено найближчим часом.
                    </div>
                <?php elseif ($order['status'] === 'в обробці'): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-hourglass-half me-2"></i> Ваше замовлення в процесі підготовки до доставки.
                    </div>
                <?php elseif ($order['status'] === 'виконано'): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i> Ваше замовлення успішно доставлено. Дякуємо за замовлення!
                    </div>
                <?php endif; ?>
            </div>
        <?php } ?>
    </div>
</div>

<!-- Дії з замовленням -->
<div class="d-flex justify-content-between mb-4">
    <a href="orders.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i> Повернутися до списку замовлень
    </a>
    <div>
        <?php if ($order['status'] === 'нове'): ?>
            <a href="cancel_order.php?id=<?php echo $orderId; ?>" class="btn btn-danger me-2" onclick="return confirm('Ви впевнені, що хочете скасувати це замовлення?');">
                <i class="fas fa-times me-1"></i> Скасувати замовлення
            </a>
        <?php endif; ?>
        <a href="repeat_order.php?id=<?php echo $orderId; ?>" class="btn btn-success">
            <i class="fas fa-redo me-1"></i> Повторити замовлення
        </a>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?>