<?php
$pageTitle = 'Деталі продукту';

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

// Перевірка ID продукту
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: products.php");
    exit;
}

$productId = intval($_GET['id']);

// Отримання даних продукту
$query = "SELECT * FROM product WHERE id = ?";
$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, "i", $productId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) != 1) {
    header("Location: products.php");
    exit;
}

$product = mysqli_fetch_assoc($result);

// Отримання статистики замовлень цього продукту
$ordersQuery = "SELECT COUNT(*) as orders_count, SUM(kol) as total_quantity, MAX(data) as last_order_date 
               FROM zayavki 
               WHERE id = ?";
$stmt = mysqli_prepare($connection, $ordersQuery);
mysqli_stmt_bind_param($stmt, "i", $productId);
mysqli_stmt_execute($stmt);
$ordersResult = mysqli_stmt_get_result($stmt);
$ordersData = mysqli_fetch_assoc($ordersResult);

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
            <a class="nav-link active" href="products.php">
                <i class="fas fa-bread-slice"></i> Продукція
            </a>
            <a class="nav-link" href="orders.php">
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
                <i class="fas fa-bread-slice me-2"></i> Деталі продукту: <?php echo htmlspecialchars($product['nazvanie']); ?>
            </h5>
            <div>
                <a href="product_edit.php?id=<?php echo $productId; ?>" class="btn btn-warning">
                    <i class="fas fa-edit me-1"></i> Редагувати
                </a>
                <a href="products.php?delete=<?php echo $productId; ?>" class="btn btn-danger ms-2" onclick="return confirm('Ви впевнені, що хочете видалити цей продукт?');">
                    <i class="fas fa-trash me-1"></i> Видалити
                </a>
                <a href="products.php" class="btn btn-secondary ms-2">
                    <i class="fas fa-arrow-left me-1"></i> Повернутися
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Основна інформація про продукт -->
<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-image me-2"></i> Зображення продукту
                </h5>
            </div>
            <div class="card-body text-center">
                <?php
                $imagePath = !empty($product['image']) ? '../../' . $product['image'] : '../../assets/img/product-placeholder.jpg';
                ?>
                <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($product['nazvanie']); ?>" class="img-fluid" style="max-height: 300px;">
            </div>
        </div>
    </div>
    
    <div class="col-md-8 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i> Інформація про продукт
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="fw-bold">ID продукту:</label>
                            <p><?php echo $product['id']; ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="fw-bold">Назва продукту:</label>
                            <p><?php echo htmlspecialchars($product['nazvanie']); ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="fw-bold">Категорія:</label>
                            <p>
                                <?php 
                                $category = $product['category'] ?? null;
                                switch ($category) {
                                    case 1:
                                        echo 'Хліб';
                                        break;
                                    case 2:
                                        echo 'Булочки';
                                        break;
                                    case 3:
                                        echo 'Пиріжки';
                                        break;
                                    case 4:
                                        echo 'Кондитерські вироби';
                                        break;
                                    default:
                                        echo 'Не вказано';
                                        break;
                                }
                                ?>
                            </p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="fw-bold">Вага (кг):</label>
                            <p><?php echo $product['ves']; ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="fw-bold">Строк реалізації (годин):</label>
                            <p><?php echo $product['srok']; ?></p>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="fw-bold">Собівартість (грн):</label>
                            <p><?php echo number_format($product['stoimost'], 2); ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="fw-bold">Ціна (грн):</label>
                            <p><?php echo number_format($product['zena'], 2); ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="fw-bold">Маржа (%):</label>
                            <p>
                                <?php 
                                $margin = ($product['stoimost'] > 0) ? (($product['zena'] - $product['stoimost']) / $product['stoimost'] * 100) : 0;
                                echo number_format($margin, 2) . '%';
                                ?>
                            </p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="fw-bold">Прибуток з одиниці (грн):</label>
                            <p><?php echo number_format($product['zena'] - $product['stoimost'], 2); ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="fw-bold">Статус:</label>
                            <p>
                                <?php if (isset($product['available']) && $product['available'] == 1): ?>
                                    <span class="badge bg-success">Доступний</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Недоступний</span>
                                <?php endif; ?>
                                
                                <?php if (isset($product['featured']) && $product['featured'] == 1): ?>
                                    <span class="badge bg-primary ms-1">Рекомендований</span>
                                <?php endif; ?>
                                
                                <?php if (isset($product['new']) && $product['new'] == 1): ?>
                                    <span class="badge bg-info ms-1">Новинка</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <label class="fw-bold">Опис продукту:</label>
                        <p><?php echo nl2br(htmlspecialchars($product['description'] ?? 'Опис відсутній')); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Статистика та замовлення -->
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-chart-bar me-2"></i> Статистика
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6>Кількість замовлень</h6>
                                <p class="display-6"><?php echo $ordersData['orders_count'] ?? 0; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6>Загальна кількість</h6>
                                <p class="display-6"><?php echo $ordersData['total_quantity'] ?? 0; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6>Загальна сума продажів</h6>
                                <p class="display-6">
                                    <?php 
                                    $totalSales = ($ordersData['total_quantity'] ?? 0) * $product['zena'];
                                    echo number_format($totalSales, 2) . ' грн';
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6>Загальний прибуток</h6>
                                <p class="display-6">
                                    <?php 
                                    $totalProfit = ($ordersData['total_quantity'] ?? 0) * ($product['zena'] - $product['stoimost']);
                                    echo number_format($totalProfit, 2) . ' грн';
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="fw-bold">Останнє замовлення:</label>
                    <p>
                        <?php 
                        echo ($ordersData['last_order_date']) ? formatDate($ordersData['last_order_date']) : 'Немає даних';
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-clipboard-list me-2"></i> Останні замовлення
                </h5>
            </div>
            <div class="card-body">
                <?php
                // Отримання останніх замовлень цього продукту
                $recentOrdersQuery = "SELECT z.*, k.name as client_name
                                    FROM zayavki z
                                    JOIN klientu k ON z.idklient = k.id
                                    WHERE z.id = ?
                                    ORDER BY z.data DESC, z.idd DESC
                                    LIMIT 5";
                $stmt = mysqli_prepare($connection, $recentOrdersQuery);
                mysqli_stmt_bind_param($stmt, "i", $productId);
                mysqli_stmt_execute($stmt);
                $recentOrdersResult = mysqli_stmt_get_result($stmt);
                
                if (mysqli_num_rows($recentOrdersResult) > 0):
                ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Клієнт</th>
                                <th>Кількість</th>
                                <th>Дата</th>
                                <th>Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = mysqli_fetch_assoc($recentOrdersResult)): ?>
                                <tr>
                                    <td><?php echo $order['idd']; ?></td>
                                    <td><?php echo htmlspecialchars($order['client_name']); ?></td>
                                    <td><?php echo $order['kol']; ?></td>
                                    <td><?php echo formatDate($order['data']); ?></td>
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
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle me-2"></i> Замовлень для цього продукту ще немає
                </div>
                <?php endif; ?>
                
                <div class="mt-3">
                    <a href="orders.php?filter_product=<?php echo $productId; ?>" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i> Переглянути всі замовлення
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include_once '../../includes/footer.php';
?>