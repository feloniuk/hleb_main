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

// Обробка відправки замовлень на виробництво
if (isset($_POST['send_to_production'])) {
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

// Отримання замовлень нічної зміни
$ordersQuery = "SELECT z.idd, z.idklient, k.name as client_name, z.id, p.nazvanie as product_name, 
                z.kol, z.data, z.doba
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

// Розрахунок необхідної кількості сировини (аналогічно shifts_day.php)
$flourHighGrade = 0;
$flourFirstGrade = 0;
$water = 0;
$salt = 0;
$sugar = 0;
$yeast = 0;
$milk = 0;
$butter = 0;

$products = [];
if (mysqli_num_rows($ingredientsResult) > 0) {
    while ($product = mysqli_fetch_assoc($ingredientsResult)) {
        $products[] = $product;
        
        // Коефіцієнти для нічної зміни можуть бути трохи іншими
        switch($product['id']) {
            case 1:
                $flourHighGrade += $product['total_quantity'] * 0.35;
                $water += $product['total_quantity'] * 0.4;
                $salt += $product['total_quantity'] * 0.002;
                $yeast += $product['total_quantity'] * 0.018;
                break;
            // Додати інші продукти з відповідними коефіцієнтами
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

include_once '../../includes/header.php';
?>

<!-- Аналогічно shifts_day.php, але для нічної зміни -->
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

<!-- Весь інший вміст аналогічний shifts_day.php, 
     але з налаштуваннями для нічної зміни -->

<?php include_once '../../includes/footer.php'; ?>