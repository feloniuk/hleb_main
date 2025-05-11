<?php
$pageTitle = 'Деталі продукту';

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

// Отримання ID продукту
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($productId <= 0) {
    $error = 'Не вказано ID продукту';
} else {
    // Отримання даних продукту
    $productQuery = "SELECT * FROM product WHERE id = ?";
    $stmt = mysqli_prepare($connection, $productQuery);
    mysqli_stmt_bind_param($stmt, "i", $productId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 0) {
        $error = 'Продукт не знайдений';
    } else {
        $product = mysqli_fetch_assoc($result);
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
                <i class="fas fa-clipboard-list"></i> Замовлення
            </a>
            <a class="nav-link" href="clients.php">
                <i class="fas fa-users"></i> Клієнти
            </a>
            <a class="nav-link active" href="products.php">
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

<?php if (isset($product)): ?>
<!-- Деталі продукту -->
<div class="card mb-4">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-bread-slice me-2"></i> Деталі продукту: <?php echo htmlspecialchars($product['nazvanie']); ?>
            </h5>
            <div>
                <a href="product_edit.php?id=<?php echo $productId; ?>" class="btn btn-warning">
                    <i class="fas fa-edit me-1"></i> Редагувати
                </a>
                <a href="reports.php?type=product&product_id=<?php echo $productId; ?>" class="btn btn-primary ms-2">
                    <i class="fas fa-chart-bar me-1"></i> Статистика
                </a>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <?php 
                $imagePath = !empty($product['image']) ? '../../' . $product['image'] : '../../assets/img/product-placeholder.jpg';
                ?>
                <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($product['nazvanie']); ?>" class="img-fluid rounded mb-3">
                
                <!-- Основні параметри продукту -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">Основні параметри</h6>
                    </div>
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-5 fw-bold">ID продукту:</div>
                            <div class="col-7"><?php echo $product['id']; ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-5 fw-bold">Вага:</div>
                            <div class="col-7"><?php echo $product['ves']; ?> кг</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-5 fw-bold">Строк реалізації:</div>
                            <div class="col-7"><?php echo $product['srok']; ?> годин</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <h4 class="mb-3"><?php echo htmlspecialchars($product['nazvanie']); ?></h4>
                
                <!-- Фінансова інформація -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">Фінансова інформація</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="text-center">
                                    <h6>Собівартість</h6>
                                    <p class="display-6"><?php echo number_format($product['stoimost'], 2); ?> грн</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <h6>Ціна</h6>
                                    <p class="display-6"><?php echo number_format($product['zena'], 2); ?> грн</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <h6>Прибуток</h6>
                                    <p class="display-6 <?php echo ($product['zena'] - $product['stoimost'] > 0) ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo number_format($product['zena'] - $product['stoimost'], 2); ?> грн
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="progress mt-3" style="height: 20px;">
                            <?php 
                            $percentage = ($product['stoimost'] / $product['zena']) * 100;
                            $profit_percentage = 100 - $percentage;
                            ?>
                            <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $percentage; ?>%" 
                                aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                Собівартість (<?php echo number_format($percentage, 1); ?>%)
                            </div>
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $profit_percentage; ?>%" 
                                aria-valuenow="<?php echo $profit_percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                Прибуток (<?php echo number_format($profit_percentage, 1); ?>%)
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Статистика продажів -->
                <?php
                // Загальна кількість проданих одиниць
                $totalSoldQuery = "SELECT SUM(kol) as total FROM zayavki WHERE id = ?";
                $stmt = mysqli_prepare($connection, $totalSoldQuery);
                mysqli_stmt_bind_param($stmt, "i", $productId);
                mysqli_stmt_execute($stmt);
                $totalSoldResult = mysqli_stmt_get_result($stmt);
                $totalSold = mysqli_fetch_assoc($totalSoldResult)['total'] ?? 0;
                
                // Загальна сума продажів
                $totalSalesQuery = "SELECT SUM(kol) * ? as total FROM zayavki WHERE id = ?";
                $stmt = mysqli_prepare($connection, $totalSalesQuery);
                mysqli_stmt_bind_param($stmt, "di", $product['zena'], $productId);
                mysqli_stmt_execute($stmt);
                $totalSalesResult = mysqli_stmt_get_result($stmt);
                $totalSales = mysqli_fetch_assoc($totalSalesResult)['total'] ?? 0;
                
                // Кількість клієнтів, які замовляли цей продукт
                $clientsCountQuery = "SELECT COUNT(DISTINCT idklient) as count FROM zayavki WHERE id = ?";
                $stmt = mysqli_prepare($connection, $clientsCountQuery);
                mysqli_stmt_bind_param($stmt, "i", $productId);
                mysqli_stmt_execute($stmt);
                $clientsCountResult = mysqli_stmt_get_result($stmt);
                $clientsCount = mysqli_fetch_assoc($clientsCountResult)['count'] ?? 0;
                ?>
                
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">Статистика продажів</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="text-center">
                                    <h6>Продано одиниць</h6>
                                    <p class="display-6"><?php echo $totalSold ?? 0; ?></p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <h6>Сума продажів</h6>
                                    <p class="display-6"><?php echo number_format($totalSales, 2); ?> грн</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <h6>Кількість клієнтів</h6>
                                    <p class="display-6"><?php echo $clientsCount; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Топ клієнтів по продукту -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Топ-5 клієнтів за кількістю замовлень</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        $topClientsQuery = "SELECT k.id, k.name, SUM(z.kol) as total_quantity
                                          FROM zayavki z
                                          JOIN klientu k ON z.idklient = k.id
                                          WHERE z.id = ?
                                          GROUP BY z.idklient
                                          ORDER BY total_quantity DESC
                                          LIMIT 5";
                        $stmt = mysqli_prepare($connection, $topClientsQuery);
                        mysqli_stmt_bind_param($stmt, "i", $productId);
                        mysqli_stmt_execute($stmt);
                        $topClientsResult = mysqli_stmt_get_result($stmt);
                        ?>
                        
                        <?php if (mysqli_num_rows($topClientsResult) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Клієнт</th>
                                        <th>Кількість</th>
                                        <th>Сума (грн)</th>
                                        <th>Дії</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($client = mysqli_fetch_assoc($topClientsResult)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($client['name']); ?></td>
                                        <td><?php echo $client['total_quantity']; ?></td>
                                        <td><?php echo number_format($client['total_quantity'] * $product['zena'], 2); ?></td>
                                        <td>
                                            <a href="client_details.php?id=<?php echo $client['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> У цього продукту ще немає замовлень.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer text-center">
        <a href="products.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Повернутися до списку продуктів
        </a>
    </div>
</div>
<?php endif; ?>

<?php
include_once '../../includes/footer.php';
?>