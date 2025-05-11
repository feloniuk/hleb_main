<?php
$pageTitle = 'Замовлення нічної зміни';

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Перевірка доступу
if (!checkAccess(['brigadir'])) {
    header("Location: ../../index.php");
    exit;
}

$connection = connectDatabase();
$success = '';
$error = '';
$warning = '';

// Перевірка, чи є вже заказ в виробництві на поточну дату та зміну
$checkExistingQuery = "SELECT COUNT(*) as count FROM newzakaz2 WHERE DATE(data) = CURDATE() AND doba='нічна'";
$existingResult = mysqli_query($connection, $checkExistingQuery);
$existingCount = mysqli_fetch_assoc($existingResult)['count'];
$alreadyInProduction = ($existingCount > 0);

// Обробка відправки замовлень на виробництво
if (isset($_POST['send_to_production'])) {
    // Перевірка чи є замовлення в виробництві
    if ($alreadyInProduction) {
        $warning = "Замовлення на нічну зміну вже в виробництві. Якщо потрібно додати нові замовлення, зверніться до адміністратора.";
    } else {
        // Додавання даних в таблицю zakazu (запис про нове замовлення)
        $insertZakazuQuery = "INSERT INTO zakazu (data, doba) VALUES (CURDATE(), 'нічна')";
        $insertZakazuResult = mysqli_query($connection, $insertZakazuQuery);
        
        if ($insertZakazuResult) {
            $zakazId = mysqli_insert_id($connection);
            
            // Отримання всіх нічних замовлень та додавання їх в newzakaz2
            $getNightOrdersQuery = "SELECT idd, idklient, id, kol, data, doba 
                                FROM zayavki 
                                WHERE doba='нічна' AND DATE(data) = CURDATE()";
            $nightOrdersResult = mysqli_query($connection, $getNightOrdersQuery);
            
            $allInsertedSuccessfully = true;
            while ($order = mysqli_fetch_assoc($nightOrdersResult)) {
                $insertOrderQuery = "INSERT INTO newzakaz2 (idd, idklient, id, kol, data, doba)
                                 VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($connection, $insertOrderQuery);
                
                // Створення масиву параметрів з посиланнями
                $idd = $order['idd'];
                $idklient = $order['idklient'];
                $id = $order['id'];
                $kol = $order['kol'];
                $data = $order['data'];
                $doba = $order['doba'];
                
                mysqli_stmt_bind_param($stmt, "iiisss", $idd, $idklient, $id, $kol, $data, $doba);
                
                if (!mysqli_stmt_execute($stmt)) {
                    $allInsertedSuccessfully = false;
                    $error = "Помилка при додаванні замовлення #" . $order['idd'] . ": " . mysqli_error($connection);
                    break;
                }
            }
            
            if ($allInsertedSuccessfully) {
                $success = "Замовлення успішно відправлені на виробництво!";
                
                // Оновлення статусу замовлень на "у виробництві"
                $updateStatusQuery = "UPDATE zayavki SET status = 'у виробництві' 
                                     WHERE doba='нічна' AND DATE(data) = CURDATE()";
                mysqli_query($connection, $updateStatusQuery);
                
                // Оновлюємо стан виробництва після успішного додавання
                $alreadyInProduction = true;
            }
        } else {
            $error = "Помилка при створенні замовлення: " . mysqli_error($connection);
        }
    }
}

// Отримання замовлень нічної зміни
$ordersQuery = "SELECT z.idd, z.idklient, k.name as client_name, z.id, p.nazvanie as product_name, 
                z.kol, z.data, z.doba, z.status
                FROM zayavki z
                JOIN klientu k ON z.idklient = k.id
                JOIN product p ON z.id = p.id
                WHERE z.doba='нічна' AND DATE(z.data) = CURDATE()
                ORDER BY z.idd DESC";
$ordersResult = mysqli_query($connection, $ordersQuery);

// Групування замовлень за продуктами для розрахунку сировини
$ingredientsQuery = "SELECT z.id, p.nazvanie, SUM(z.kol) as total_quantity
                     FROM zayavki z
                     JOIN product p ON z.id = p.id
                     WHERE z.doba='нічна' AND DATE(z.data) = CURDATE()
                     GROUP BY z.id
                     ORDER BY p.nazvanie";
$ingredientsResult = mysqli_query($connection, $ingredientsQuery);

// Розрахунок необхідної кількості сировини
$flourHighGrade = 0;
$flourFirstGrade = 0;
$flourSecondGrade = 0;
$ryeFlour = 0;
$water = 0;
$salt = 0;
$sugar = 0;
$yeast = 0;
$milk = 0;
$butter = 0;
$seeds = 0;

// Обробка кожного продукту і розрахунок сировини
$products = [];
if (mysqli_num_rows($ingredientsResult) > 0) {
    while ($product = mysqli_fetch_assoc($ingredientsResult)) {
        $products[] = $product;
        
        // Обчислення необхідної кількості сировини в залежності від продукту
        switch($product['id']) {
            // Хліб Обідній
            case 1:
                $flourHighGrade += $product['total_quantity'] * 0.35;
                $flourFirstGrade += $product['total_quantity'] * 0.25;
                $water += $product['total_quantity'] * 0.4;
                $salt += $product['total_quantity'] * 0.002;
                $sugar += $product['total_quantity'] * 0.003;
                $yeast += $product['total_quantity'] * 0.018;
                break;
                
            // Хліб Сімейний
            case 2:
                $flourHighGrade += $product['total_quantity'] * 0.55;
                $milk += $product['total_quantity'] * 0.14;
                $butter += $product['total_quantity'] * 0.04;
                $sugar += $product['total_quantity'] * 0.003;
                $water += $product['total_quantity'] * 0.18;
                $yeast += $product['total_quantity'] * 0.014;
                break;
                
            // Багет Французький
            case 3:
                $flourHighGrade += $product['total_quantity'] * 0.23;
                $water += $product['total_quantity'] * 0.14;
                $salt += $product['total_quantity'] * 0.001;
                $yeast += $product['total_quantity'] * 0.009;
                break;
                
            // Інші продукти...
            default:
                // Середні значення для інших продуктів
                $flourHighGrade += $product['total_quantity'] * 0.28;
                $water += $product['total_quantity'] * 0.18;
                $salt += $product['total_quantity'] * 0.002;
                $sugar += $product['total_quantity'] * 0.003;
                $yeast += $product['total_quantity'] * 0.014;
                break;
        }
    }
}

// Отримання кількості унікальних продуктів
$uniqueProductsQuery = "SELECT COUNT(DISTINCT id) as count FROM zayavki WHERE doba='нічна' AND DATE(data) = CURDATE()";
$uniqueProductsResult = mysqli_query($connection, $uniqueProductsQuery);
$uniqueProductsCount = mysqli_fetch_assoc($uniqueProductsResult)['count'];

// Отримання загальної кількості одиниць продукції
$totalQuantityQuery = "SELECT SUM(kol) as total FROM zayavki WHERE doba='нічна' AND DATE(data) = CURDATE()";
$totalQuantityResult = mysqli_query($connection, $totalQuantityQuery);
$totalQuantity = mysqli_fetch_assoc($totalQuantityResult)['total'];

// Отримання інформації про статуси замовлень
$statusQuery = "SELECT 
                  SUM(CASE WHEN status = 'нове' THEN 1 ELSE 0 END) as new_count,
                  SUM(CASE WHEN status = 'у виробництві' THEN 1 ELSE 0 END) as in_production_count,
                  SUM(CASE WHEN status = 'виконано' THEN 1 ELSE 0 END) as completed_count,
                  SUM(CASE WHEN status = 'скасовано' THEN 1 ELSE 0 END) as cancelled_count,
                  COUNT(*) as total_count
                FROM zayavki 
                WHERE doba='нічна' AND DATE(data) = CURDATE()";
$statusResult = mysqli_query($connection, $statusQuery);
$statusInfo = mysqli_fetch_assoc($statusResult);

// Перевірка чи всі замовлення в виробництві (для показу відповідного повідомлення)
$allInProduction = ($statusInfo['in_production_count'] == $statusInfo['total_count'] && $statusInfo['total_count'] > 0);

include_once '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <nav class="nav main-menu nav-pills nav-fill">
            <a class="nav-link" href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Головна
            </a>
            <a class="nav-link" href="orders.php">
                <i class="fas fa-clipboard-list"></i> Замовлення
            </a>
            <a class="nav-link active dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-calendar-night"></i> Зміни
            </a>
            <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                <a class="dropdown-item" href="shifts_day.php">Денна зміна</a>
                <a class="dropdown-item active" href="shifts_night.php">Нічна зміна</a>
            </div>
            <a class="nav-link" href="scanner.php">
                <i class="fas fa-barcode"></i> Сканер
            </a>
            <a class="nav-link" href="videos.php">
                <i class="fas fa-video"></i> Відео
            </a>
            <a class="nav-link" href="products.php">
                <i class="fas fa-bread-slice"></i> Продукція
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

<?php if (!empty($warning)): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <?php echo $warning; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($alreadyInProduction): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="fas fa-info-circle me-2"></i> Замовлення на нічну зміну вже відправлено в виробництво.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Інформаційна панель -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <i class="fas fa-moon me-2"></i> Замовлення на нічну зміну - <?php echo date('d.m.Y'); ?>
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <h5 class="card-title">Унікальних продуктів</h5>
                        <p class="display-4"><?php echo $uniqueProductsCount; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <h5 class="card-title">Загальна кількість</h5>
                        <p class="display-4"><?php echo $totalQuantity; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <form method="post" action="">
                            <h5 class="card-title">Відправити на виробництво</h5>
                            <button type="submit" name="send_to_production" class="btn btn-primary btn-lg mt-3" 
                                <?php echo ($alreadyInProduction || mysqli_num_rows($ordersResult) == 0) ? 'disabled' : ''; ?> 
                                onclick="return confirm('Ви впевнені, що хочете відправити ці замовлення на виробництво?');">
                                <i class="fas fa-paper-plane me-2"></i> Відправити
                            </button>
                            <?php if ($alreadyInProduction): ?>
                                <div class="mt-2 text-muted">
                                    <small><i class="fas fa-info-circle me-1"></i> Замовлення вже відправлено в виробництво</small>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Статус замовлень -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-tasks me-2"></i> Статус замовлень
        </h5>
    </div>
    <div class="card-body">
        <!-- Прогрес бар для відображення статусу всіх замовлень -->
        <div class="progress mb-3" style="height: 25px;">
            <?php
            $newPercent = ($statusInfo['total_count'] > 0) ? ($statusInfo['new_count'] / $statusInfo['total_count'] * 100) : 0;
            $inProductionPercent = ($statusInfo['total_count'] > 0) ? ($statusInfo['in_production_count'] / $statusInfo['total_count'] * 100) : 0;
            $completedPercent = ($statusInfo['total_count'] > 0) ? ($statusInfo['completed_count'] / $statusInfo['total_count'] * 100) : 0;
            $cancelledPercent = ($statusInfo['total_count'] > 0) ? ($statusInfo['cancelled_count'] / $statusInfo['total_count'] * 100) : 0;
            ?>
            <?php if ($newPercent > 0): ?>
                <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $newPercent; ?>%" 
                    aria-valuenow="<?php echo $newPercent; ?>" aria-valuemin="0" aria-valuemax="100" 
                    data-bs-toggle="tooltip" title="Нові: <?php echo $statusInfo['new_count']; ?>">
                    Нові: <?php echo $statusInfo['new_count']; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($inProductionPercent > 0): ?>
                <div class="progress-bar bg-warning text-dark" role="progressbar" style="width: <?php echo $inProductionPercent; ?>%" 
                    aria-valuenow="<?php echo $inProductionPercent; ?>" aria-valuemin="0" aria-valuemax="100"
                    data-bs-toggle="tooltip" title="У виробництві: <?php echo $statusInfo['in_production_count']; ?>">
                    У виробництві: <?php echo $statusInfo['in_production_count']; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($completedPercent > 0): ?>
                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $completedPercent; ?>%" 
                    aria-valuenow="<?php echo $completedPercent; ?>" aria-valuemin="0" aria-valuemax="100"
                    data-bs-toggle="tooltip" title="Виконані: <?php echo $statusInfo['completed_count']; ?>">
                    Виконані: <?php echo $statusInfo['completed_count']; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($cancelledPercent > 0): ?>
                <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $cancelledPercent; ?>%" 
                    aria-valuenow="<?php echo $cancelledPercent; ?>" aria-valuemin="0" aria-valuemax="100"
                    data-bs-toggle="tooltip" title="Скасовані: <?php echo $statusInfo['cancelled_count']; ?>">
                    Скасовані: <?php echo $statusInfo['cancelled_count']; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Легенда -->
        <div class="row">
            <div class="col-md-3">
                <div class="d-flex align-items-center">
                    <div class="badge bg-info me-2">&nbsp;&nbsp;&nbsp;</div>
                    <div>Нові: <?php echo $statusInfo['new_count']; ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="d-flex align-items-center">
                    <div class="badge bg-warning me-2">&nbsp;&nbsp;&nbsp;</div>
                    <div>У виробництві: <?php echo $statusInfo['in_production_count']; ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="d-flex align-items-center">
                    <div class="badge bg-success me-2">&nbsp;&nbsp;&nbsp;</div>
                    <div>Виконані: <?php echo $statusInfo['completed_count']; ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="d-flex align-items-center">
                    <div class="badge bg-danger me-2">&nbsp;&nbsp;&nbsp;</div>
                    <div>Скасовані: <?php echo $statusInfo['cancelled_count']; ?></div>
                </div>
            </div>
        </div>
        
        <!-- Кнопка переходу на сторінку виробництва -->
        <?php if ($alreadyInProduction): ?>
            <div class="mt-3 text-center">
                <a href="production.php?date=<?php echo date('Y-m-d'); ?>&shift=нічна" class="btn btn-primary">
                    <i class="fas fa-industry me-1"></i> Перейти до управління виробництвом
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Необхідна сировина -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-calculator me-2"></i> Необхідна сировина
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php if ($flourHighGrade > 0): ?>
                    <div class="col-md-3 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Мука вищого ґатунку</h6>
                                <p class="card-text fw-bold"><?php echo number_format($flourHighGrade, 2); ?> кг</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($flourFirstGrade > 0): ?>
                    <div class="col-md-3 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Мука першого ґатунку</h6>
                                <p class="card-text fw-bold"><?php echo number_format($flourFirstGrade, 2); ?> кг</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($water > 0): ?>
                    <div class="col-md-3 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Вода</h6>
                                <p class="card-text fw-bold"><?php echo number_format($water, 2); ?> л</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($salt > 0): ?>
                    <div class="col-md-3 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Сіль</h6>
                                <p class="card-text fw-bold"><?php echo number_format($salt, 2); ?> кг</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($sugar > 0): ?>
                    <div class="col-md-3 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Цукор</h6>
                                <p class="card-text fw-bold"><?php echo number_format($sugar, 2); ?> кг</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($yeast > 0): ?>
                    <div class="col-md-3 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Дріжджі</h6>
                                <p class="card-text fw-bold"><?php echo number_format($yeast, 2); ?> кг</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($milk > 0): ?>
                    <div class="col-md-3 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Молоко</h6>
                                <p class="card-text fw-bold"><?php echo number_format($milk, 2); ?> л</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($butter > 0): ?>
                    <div class="col-md-3 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Масло</h6>
                                <p class="card-text fw-bold"><?php echo number_format($butter, 2); ?> кг</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="text-center mt-3">
                    <a href="shift_night_pdf.php" class="btn btn-success">
                        <i class="fas fa-file-pdf me-2"></i> Експорт в PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Список продуктів для виробництва -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-bread-slice me-2"></i> Список продуктів для виробництва
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Назва продукту</th>
                                <th>Загальна кількість</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Перезапуск результату запиту
                            mysqli_data_seek($ingredientsResult, 0);
                            if (mysqli_num_rows($ingredientsResult) > 0): 
                            ?>
                                <?php while ($product = mysqli_fetch_assoc($ingredientsResult)): ?>
                                    <tr>
                                        <td><?php echo $product['id']; ?></td>
                                        <td><?php echo htmlspecialchars($product['nazvanie']); ?></td>
                                        <td><?php echo $product['total_quantity']; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center">Немає продуктів для виробництва</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Детальний список замовлень -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-list me-2"></i> Детальний список замовлень
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Клієнт</th>
                        <th>Продукт</th>
                        <th>Кількість</th>
                        <th>Дата</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Перезапуск результату запиту
                    mysqli_data_seek($ordersResult, 0);
                    if (mysqli_num_rows($ordersResult) > 0): 
                    ?>
                        <?php while ($order = mysqli_fetch_assoc($ordersResult)): ?>
                            <tr>
                                <td><?php echo $order['idd']; ?></td>
                                <td><?php echo htmlspecialchars($order['client_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                <td><?php echo $order['kol']; ?></td>
                                <td><?php echo formatDate($order['data']); ?></td>
                                <td>
                                    <?php
                                    $statusClass = '';
                                    $statusText = '';
                                    
                                    switch ($order['status']) {
                                        case 'нове':
                                            $statusClass = 'bg-info';
                                            $statusText = 'Нове';
                                            break;
                                        case 'у виробництві':
                                            $statusClass = 'bg-warning text-dark';
                                            $statusText = 'У виробництві';
                                            break;
                                        case 'виконано':
                                            $statusClass = 'bg-success';
                                            $statusText = 'Виконано';
                                            break;
                                        case 'скасовано':
                                            $statusClass = 'bg-danger';
                                            $statusText = 'Скасовано';
                                            break;
                                        default:
                                            $statusClass = 'bg-secondary';
                                            $statusText = 'Невідомо';
                                            break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">Немає замовлень на нічну зміну</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Ініціалізація тултіпів для прогрес-бару
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>

<?php include_once '../../includes/footer.php'; ?>