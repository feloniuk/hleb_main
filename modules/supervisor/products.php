<?php
$pageTitle = 'Продукція';

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Перевірка доступу
if (!checkAccess(['brigadir'])) {
    header("Location: ../../index.php");
    exit;
}

$connection = connectDatabase();

// Отримання списку продукції
$query = "SELECT * FROM product ORDER BY nazvanie ASC";
$result = mysqli_query($connection, $query);

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
            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-calendar-day"></i> Зміни
            </a>
            <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                <a class="dropdown-item" href="shifts_day.php">Денна зміна</a>
                <a class="dropdown-item" href="shifts_night.php">Нічна зміна</a>
            </div>
            <a class="nav-link" href="scanner.php">
                <i class="fas fa-barcode"></i> Сканер
            </a>
            <a class="nav-link" href="videos.php">
                <i class="fas fa-video"></i> Відео
            </a>
            <a class="nav-link active" href="products.php">
                <i class="fas fa-bread-slice"></i> Продукція
            </a>
        </nav>
    </div>
</div>

<div class="row">
    <?php while ($product = mysqli_fetch_assoc($result)): ?>
        <div class="col-md-4 mb-4">
            <div class="card product-card">
                <img 
                    src="<?php 
                        echo !empty($product['image']) 
                            ? '../../' . $product['image'] 
                            : '../../assets/img/product-placeholder.jpg'; 
                    ?>" 
                    class="card-img-top" 
                    alt="<?php echo htmlspecialchars($product['nazvanie']); ?>"
                >
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($product['nazvanie']); ?></h5>
                    <p class="card-text">
                        <strong>Вага:</strong> <?php echo $product['ves']; ?> кг<br>
                        <strong>Термін зберігання:</strong> <?php echo $product['srok']; ?> год<br>
                        <span class="price">
                            <?php echo number_format($product['zena'], 2); ?> грн
                        </span>
                    </p>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<?php include_once '../../includes/footer.php'; ?>