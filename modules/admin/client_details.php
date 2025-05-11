<?php
$pageTitle = 'Деталі клієнта';

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

// Перевірка ID клієнта
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: clients.php");
    exit;
}

$clientId = intval($_GET['id']);

// Отримання даних клієнта
$query = "SELECT * FROM klientu WHERE id = ?";
$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, "i", $clientId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) != 1) {
    header("Location: clients.php");
    exit;
}

$client = mysqli_fetch_assoc($result);

// Отримання статистики замовлень цього клієнта
$ordersQuery = "SELECT COUNT(*) as orders_count, SUM(z.kol) as total_quantity, MAX(z.data) as last_order_date, 
                SUM(z.kol * p.zena) as total_amount
                FROM zayavki z
                JOIN product p ON z.id = p.id
                WHERE z.idklient = ?";
$stmt = mysqli_prepare($connection, $ordersQuery);
mysqli_stmt_bind_param($stmt, "i", $clientId);
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
            <a class="nav-link active" href="clients.php">
                <i class="fas fa-user-tie"></i> Клієнти
            </a>
            <a class="nav-link" href="products.php">
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
                <i class="fas fa-user-tie me-2"></i> Деталі клієнта: <?php echo htmlspecialchars($client['name']); ?>
            </h5>
            <div>
                <a href="client_edit.php?id=<?php echo $clientId; ?>" class="btn btn-warning">
                    <i class="fas fa-edit me-1"></i> Редагувати
                </a>
                <a href="client_orders.php?id=<?php echo $clientId; ?>" class="btn btn-info ms-2">
                    <i class="fas fa-clipboard-list me-1"></i> Замовлення
                </a>
                <a href="order_add.php?client_id=<?php echo $clientId; ?>" class="btn btn-success ms-2">
                    <i class="fas fa-plus me-1"></i> Нове замовлення
                </a>
                <a href="clients.php" class="btn btn-secondary ms-2">
                    <i class="fas fa-arrow-left me-1"></i> Повернутися
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Основна інформація про клієнта -->
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i> Загальна інформація
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="fw-bold">ID клієнта:</label>
                            <p><?php echo $client['id']; ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="fw-bold">Назва компанії/організації:</label>
                            <p><?php echo htmlspecialchars($client['name']); ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="fw-bold">ПІБ контактної особи:</label>
                            <p><?php echo htmlspecialchars($client['fio']); ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="fw-bold">Посада:</label>
                            <p><?php echo htmlspecialchars($client['dolj'] ?? '-'); ?></p>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="fw-bold">Телефон:</label>
                            <p><?php echo htmlspecialchars($client['tel']); ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="fw-bold">Email:</label>
                            <p><?php echo htmlspecialchars($client['mail'] ?? '-'); ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="fw-bold">Тип клієнта:</label>
                            <p>
                                <?php 
                                $clientType = $client['client_type'] ?? null;
                                switch ($clientType) {
                                    case 1:
                                        echo 'Ресторан/кафе';
                                        break;
                                    case 2:
                                        echo 'Магазин/супермаркет';
                                        break;
                                    case 3:
                                        echo 'Готель';
                                        break;
                                    case 4:
                                        echo 'Заклад громадського харчування';
                                        break;
                                    case 5:
                                        echo 'Інше';
                                        break;
                                    default:
                                        echo 'Не вказано';
                                        break;
                                }
                                ?>
                            </p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="fw-bold">Логін:</label>
                            <p><?php echo htmlspecialchars($client['login']); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label class="fw-bold">Коментар:</label>
                            <p><?php echo nl2br(htmlspecialchars($client['comment'] ?? 'Коментар відсутній')); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-map-marker-alt me-2"></i> Адреса та розташування
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="fw-bold">Місто:</label>
                            <p><?php echo htmlspecialchars($client['city']); ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="fw-bold">Адреса:</label>
                            <p><?php echo htmlspecialchars($client['adres'] ?? '-'); ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="fw-bold">Відстань (км):</label>
                            <p><?php echo $client['rast']; ?></p>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <!-- Карта або додаткова інформація -->
                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <h6 class="card-title">Маршрут доставки</h6>
                                <p class="card-text">
                                    <?php 
                                    // Формування посилання на Google Maps
                                    $mapAddress = urlencode($client['city'] . ', ' . ($client['adres'] ?? ''));
                                    ?>
                                    <a href="https://www.google.com/maps/search/?api=1&query=<?php echo $mapAddress; ?>" target="_blank" class="btn btn-sm btn-primary mt-2">
                                        <i class="fas fa-map-marked-alt me-1"></i> Відкрити на карті
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Статистика та останні замовлення -->
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
                                <h6>Загальна кількість одиниць</h6>
                                <p class="display-6"><?php echo $ordersData['total_quantity'] ?? 0; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6>Загальна сума замовлень</h6>
                                <p class="display-6">
                                    <?php 
                                    $totalAmount = $ordersData['total_amount'] ?? 0;
                                    echo number_format($totalAmount, 2) . ' грн';
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6>Середня сума замовлення</h6>
                                <p class="display-6">
                                    <?php 
                                    $avgOrderAmount = ($ordersData['orders_count'] > 0) ? $totalAmount / $ordersData['orders_count'] : 0;
                                    echo number_format($avgOrderAmount, 2) . ' грн';
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
                
                <div class="mb-3">
                    <label class="fw-bold">Статус клієнта:</label>
                    <p>
                        <?php
                        $lastOrderDate = $ordersData['last_order_date'] ?? null;
                        if ($lastOrderDate) {
                            $lastOrderTime = strtotime($lastOrderDate);
                            $currentTime = time();
                            $daysSinceLastOrder = floor(($currentTime - $lastOrderTime) / (60 * 60 * 24));
                            
                            if ($daysSinceLastOrder <= 30) {
                                echo '<span class="badge bg-success">Активний</span>';
                            } elseif ($daysSinceLastOrder <= 90) {
                                echo '<span class="badge bg-warning">Менш активний</span>';
                            } else {
                                echo '<span class="badge bg-danger">Неактивний</span>';
                            }
                            
                            echo ' (останнє замовлення ' . $daysSinceLastOrder . ' днів тому)';
                        } else {
                            echo '<span class="badge bg-secondary">Новий</span> (ще немає замовлень)';
                        }
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
                // Отримання останніх замовлень цього клієнта
                $recentOrdersQuery = "SELECT z.*, p.nazvanie as product_name
                                     FROM zayavki z
                                     JOIN product p ON z.id = p.id
                                     WHERE z.idklient = ?
                                     ORDER BY z.data DESC, z.idd DESC
                                     LIMIT 5";
                $stmt = mysqli_prepare($connection, $recentOrdersQuery);
                mysqli_stmt_bind_param($stmt, "i", $clientId);
                mysqli_stmt_execute($stmt);
                $recentOrdersResult = mysqli_stmt_get_result($stmt);
                
                if (mysqli_num_rows($recentOrdersResult) > 0):
                ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Продукт</th>
                                <th>Кількість</th>
                                <th>Дата</th>
                                <th>Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = mysqli_fetch_assoc($recentOrdersResult)): ?>
                                <tr>
                                    <td><?php echo $order['idd']; ?></td>
                                    <td><?php echo htmlspecialchars($order['product_name']); ?></td>
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
                    <i class="fas fa-info-circle me-2"></i> Замовлень від цього клієнта ще немає
                </div>
                <?php endif; ?>
                
                <div class="mt-3">
                    <a href="client_orders.php?id=<?php echo $clientId; ?>" class="btn btn-primary">
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