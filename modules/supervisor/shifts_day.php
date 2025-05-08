<?php
$pageTitle = 'Замовлення денної зміни';

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

// Обробка відправки замовлень на виробництво
if (isset($_POST['send_to_production'])) {
    // Додавання даних в таблицю zakazu (запис про нове замовлення)
    $insertZakazuQuery = "INSERT INTO zakazu (data, doba) VALUES (CURDATE(), 'денна')";
    $insertZakazuResult = mysqli_query($connection, $insertZakazuQuery);
    
    if ($insertZakazuResult) {
        $zakazId = mysqli_insert_id($connection);
        
        // Отримання всіх денних замовлень та додавання їх в newzakaz
        $getDayOrdersQuery = "SELECT idd, idklient, id, kol, data, doba 
                             FROM zayavki 
                             WHERE doba='денна' AND DATE(data) = CURDATE()";
        $dayOrdersResult = mysqli_query($connection, $getDayOrdersQuery);
        
        $allInsertedSuccessfully = true;
        while ($order = mysqli_fetch_assoc($dayOrdersResult)) {
            $insertOrderQuery = "INSERT INTO newzakaz (idd, idklient, id, kol, data, doba)
                                VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($connection, $insertOrderQuery);
            mysqli_stmt_bind_param(
                $stmt, 
                "iiisss", 
                $order['idd'], 
                $order['idklient'], 
                $order['id'], 
                $order['kol'], 
                $order['data'], 
                $order['doba']
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                $allInsertedSuccessfully = false;
                $error = "Помилка при додаванні замовлення #" . $order['idd'] . ": " . mysqli_error($connection);
                break;
            }
        }
        
        if ($allInsertedSuccessfully) {
            $success = "Замовлення успішно відправлені на виробництво!";
        }
    } else {
        $error = "Помилка при створенні замовлення: " . mysqli_error($connection);
    }
}

// Отримання замовлень денної зміни
$ordersQuery = "SELECT z.idd, z.idklient, k.name as client_name, z.id, p.nazvanie as product_name, 
                z.kol, z.data, z.doba
                FROM zayavki z
                JOIN klientu k ON z.idklient = k.id
                JOIN product p ON z.id = p.id
                WHERE z.doba='денна' AND DATE(z.data) = CURDATE()
                ORDER BY z.idd DESC";
$ordersResult = mysqli_query($connection, $ordersQuery);

// Групування замовлень за продуктами для розрахунку сировини
$ingredientsQuery = "SELECT z.id, p.nazvanie, SUM(z.kol) as total_quantity
                    FROM zayavki z
                    JOIN product p ON z.id = p.id
                    WHERE z.doba='денна' AND DATE(z.data) = CURDATE()
                    GROUP BY z.id
                    ORDER BY p.nazvanie";
$ingredientsResult = mysqli_query($connection, $ingredientsQuery);

// Розрахунок необхідної кількості сировини
$flourHighGrade = 0;    // Мука вищого ґатунку
$flourFirstGrade = 0;   // Мука першого ґатунку
$flourSecondGrade = 0;  // Мука другого ґатунку
$ryeFlour = 0;          // Житнє борошно
$water = 0;             // Вода
$salt = 0;              // Сіль
$sugar = 0;             // Цукор
$yeast = 0;             // Дріжджі
$milk = 0;              // Молоко
$butter = 0;            // Масло
$seeds = 0;             // Насіння

// Коефіцієнти для різних видів продукції
// Обробка кожного продукту і розрахунок сировини
$products = [];
if (mysqli_num_rows($ingredientsResult) > 0) {
    while ($product = mysqli_fetch_assoc($ingredientsResult)) {
        $products[] = $product;
        
        // Обчислення необхідної кількості сировини в залежності від продукту
        // Приклад коефіцієнтів для кожного продукту
        switch($product['id']) {
            // Хліб Обідній
            case 1:
                $flourHighGrade += $product['total_quantity'] * 0.4; // 400г на одиницю
                $flourFirstGrade += $product['total_quantity'] * 0.3; // 300г на одиницю
                $water += $product['total_quantity'] * 0.45; // 450мл на одиницю
                $salt += $product['total_quantity'] * 0.002; // 2г на одиницю
                $sugar += $product['total_quantity'] * 0.003; // 3г на одиницю
                $yeast += $product['total_quantity'] * 0.02; // 20г на одиницю
                break;
                
            // Хліб Сімейний
            case 2:
                $flourHighGrade += $product['total_quantity'] * 0.6; // 600г на одиницю
                $milk += $product['total_quantity'] * 0.15; // 150мл на одиницю
                $butter += $product['total_quantity'] * 0.05; // 50г на одиницю
                $sugar += $product['total_quantity'] * 0.003; // 3г на одиницю
                $water += $product['total_quantity'] * 0.2; // 200мл на одиницю
                $yeast += $product['total_quantity'] * 0.015; // 15г на одиницю
                break;
                
            // Багет Французький
            case 3:
                $flourHighGrade += $product['total_quantity'] * 0.25; // 250г на одиницю
                $water += $product['total_quantity'] * 0.15; // 150мл на одиницю
                $salt += $product['total_quantity'] * 0.001; // 1г на одиницю
                $yeast += $product['total_quantity'] * 0.01; // 10г на одиницю
                break;
                
            // Інші продукти...
            default:
                // Середні значення для інших продуктів
                $flourHighGrade += $product['total_quantity'] * 0.3;
                $water += $product['total_quantity'] * 0.2;
                $salt += $product['total_quantity'] * 0.002;
                $sugar += $product['total_quantity'] * 0.003;
                $yeast += $product['total_quantity'] * 0.015;
                break;
        }
    }
}

// Отримання кількості унікальних продуктів
$uniqueProductsQuery = "SELECT COUNT(DISTINCT id) as count FROM zayavki WHERE doba='денна' AND DATE(data) = CURDATE()";
$uniqueProductsResult = mysqli_query($connection, $uniqueProductsQuery);
$uniqueProductsCount = mysqli_fetch_assoc($uniqueProductsResult)['count'];

// Отримання загальної кількості одиниць продукції
$totalQuantityQuery = "SELECT SUM(kol) as total FROM zayavki WHERE doba='денна' AND DATE(data) = CURDATE()";
$totalQuantityResult = mysqli_query($connection, $totalQuantityQuery);
$totalQuantity = mysqli_fetch_assoc($totalQuantityResult)['total'];

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
            <a class="nav-link active dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-calendar-day"></i> Зміни
            </a>
            <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                <a class="dropdown-item active" href="shifts_day.php">Денна зміна</a>
                <a class="dropdown-item" href="shifts_night.php">Нічна зміна</a>
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

<!-- Інформаційна панель -->
<div class="card mb-4">
    <div class="card-header bg-warning text-dark">
        <h5 class="mb-0">
            <i class="fas fa-sun me-2"></i> Замовлення на денну зміну - <?php echo date('d.m.Y'); ?>
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
                            <button type="submit" name="send_to_production" class="btn btn-warning btn-lg mt-3" <?php echo (mysqli_num_rows($ordersResult) == 0) ? 'disabled' : ''; ?> onclick="return confirm('Ви впевнені, що хочете відправити ці замовлення на виробництво?');">
                                <i class="fas fa-paper-plane me-2"></i> Відправити
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
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
                    <a href="shift_day_pdf.php" class="btn btn-success">
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
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">Немає замовлень на денну зміну</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?>