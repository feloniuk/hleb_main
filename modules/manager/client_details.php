<?php
$pageTitle = 'Деталі клієнта';

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

// Отримання ID клієнта
$clientId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($clientId <= 0) {
    $error = 'Не вказано ID клієнта';
} else {
    // Отримання даних клієнта
    $clientQuery = "SELECT * FROM klientu WHERE id = ?";
    $stmt = mysqli_prepare($connection, $clientQuery);
    mysqli_stmt_bind_param($stmt, "i", $clientId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 0) {
        $error = 'Клієнт не знайдений';
    } else {
        $client = mysqli_fetch_assoc($result);
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
            <a class="nav-link active" href="clients.php">
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

<?php if (isset($client)): ?>
<!-- Деталі клієнта -->
<div class="card mb-4">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-user me-2"></i> Деталі клієнта: <?php echo htmlspecialchars($client['name']); ?>
            </h5>
            <div>
                <a href="client_edit.php?id=<?php echo $clientId; ?>" class="btn btn-warning">
                    <i class="fas fa-edit me-1"></i> Редагувати
                </a>
                <a href="client_orders.php?id=<?php echo $clientId; ?>" class="btn btn-primary ms-2">
                    <i class="fas fa-clipboard-list me-1"></i> Замовлення
                </a>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">Контактна інформація</h6>
                    </div>
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-md-4 fw-bold">ID клієнта:</div>
                            <div class="col-md-8"><?php echo $client['id']; ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4 fw-bold">Назва компанії:</div>
                            <div class="col-md-8"><?php echo htmlspecialchars($client['name']); ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4 fw-bold">Контактна особа:</div>
                            <div class="col-md-8"><?php echo htmlspecialchars($client['fio']); ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4 fw-bold">Посада:</div>
                            <div class="col-md-8"><?php echo htmlspecialchars($client['dolj']); ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4 fw-bold">Телефон:</div>
                            <div class="col-md-8">
                                <a href="tel:<?php echo htmlspecialchars($client['tel']); ?>">
                                    <?php echo htmlspecialchars($client['tel']); ?>
                                </a>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4 fw-bold">Email:</div>
                            <div class="col-md-8">
                                <?php if (!empty($client['mail'])): ?>
                                <a href="mailto:<?php echo htmlspecialchars($client['mail']); ?>">
                                    <?php echo htmlspecialchars($client['mail']); ?>
                                </a>
                                <?php else: ?>
                                <span class="text-muted">Не вказано</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">Адреса та додаткова інформація</h6>
                    </div>
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-md-4 fw-bold">Місто:</div>
                            <div class="col-md-8"><?php echo htmlspecialchars($client['city']); ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4 fw-bold">Адреса:</div>
                            <div class="col-md-8"><?php echo htmlspecialchars($client['adres']); ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4 fw-bold">Відстань:</div>
                            <div class="col-md-8"><?php echo $client['rast']; ?> км</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4 fw-bold">Логін:</div>
                            <div class="col-md-8"><?php echo htmlspecialchars($client['login']); ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4 fw-bold">Пароль:</div>
                            <div class="col-md-8">
                                <span class="text-muted">***********</span>
                                <button type="button" class="btn btn-sm btn-outline-secondary ms-2" id="showPasswordBtn">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <span id="passwordText" class="d-none"><?php echo htmlspecialchars($client['password']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Статистика клієнта -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">Статистика клієнта</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php
                    // Кількість замовлень
                    $ordersCountQuery = "SELECT COUNT(*) as count FROM zayavki WHERE idklient = ?";
                    $stmt = mysqli_prepare($connection, $ordersCountQuery);
                    mysqli_stmt_bind_param($stmt, "i", $clientId);
                    mysqli_stmt_execute($stmt);
                    $ordersCountResult = mysqli_stmt_get_result($stmt);
                    $ordersCount = mysqli_fetch_assoc($ordersCountResult)['count'];
                    
                    // Загальна сума замовлень
                    $totalSumQuery = "SELECT SUM(z.kol * p.zena) as total_sum 
                                     FROM zayavki z 
                                     JOIN product p ON z.id = p.id 
                                     WHERE z.idklient = ?";
                    $stmt = mysqli_prepare($connection, $totalSumQuery);
                    mysqli_stmt_bind_param($stmt, "i", $clientId);
                    mysqli_stmt_execute($stmt);
                    $totalSumResult = mysqli_stmt_get_result($stmt);
                    $totalSum = mysqli_fetch_assoc($totalSumResult)['total_sum'];
                    
                    // Останнє замовлення
                    $lastOrderQuery = "SELECT z.data 
                                      FROM zayavki z 
                                      WHERE z.idklient = ? 
                                      ORDER BY z.data DESC LIMIT 1";
                    $stmt = mysqli_prepare($connection, $lastOrderQuery);
                    mysqli_stmt_bind_param($stmt, "i", $clientId);
                    mysqli_stmt_execute($stmt);
                    $lastOrderResult = mysqli_stmt_get_result($stmt);
                    $lastOrderDate = mysqli_num_rows($lastOrderResult) > 0 ? 
                                    formatDate(mysqli_fetch_assoc($lastOrderResult)['data']) : 'Немає замовлень';
                    ?>
                    
                    <div class="col-md-4">
                        <div class="text-center">
                            <h6>Кількість замовлень</h6>
                            <p class="display-6"><?php echo $ordersCount; ?></p>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="text-center">
                            <h6>Загальна сума</h6>
                            <p class="display-6"><?php echo number_format($totalSum, 2); ?> грн</p>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="text-center">
                            <h6>Останнє замовлення</h6>
                            <p class="display-6"><?php echo $lastOrderDate; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Останні замовлення клієнта -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">Останні замовлення</h6>
            </div>
            <div class="card-body">
                <?php
                $recentOrdersQuery = "SELECT z.idd, z.id, p.nazvanie, z.kol, z.data, z.doba, z.status, (z.kol * p.zena) as total_price
                                     FROM zayavki z
                                     JOIN product p ON z.id = p.id
                                     WHERE z.idklient = ?
                                     ORDER BY z.data DESC, z.idd DESC
                                     LIMIT 5";
                $stmt = mysqli_prepare($connection, $recentOrdersQuery);
                mysqli_stmt_bind_param($stmt, "i", $clientId);
                mysqli_stmt_execute($stmt);
                $recentOrdersResult = mysqli_stmt_get_result($stmt);
                ?>
                
                <?php if (mysqli_num_rows($recentOrdersResult) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Продукт</th>
                                <th>Кількість</th>
                                <th>Сума</th>
                                <th>Дата</th>
                                <th>Зміна</th>
                                <th>Статус</th>
                                <th>Дії</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = mysqli_fetch_assoc($recentOrdersResult)): ?>
                            <tr>
                                <td><?php echo $order['idd']; ?></td>
                                <td><?php echo htmlspecialchars($order['nazvanie']); ?></td>
                                <td><?php echo $order['kol']; ?></td>
                                <td><?php echo number_format($order['total_price'], 2); ?> грн</td>
                                <td><?php echo formatDate($order['data']); ?></td>
                                <td>
                                    <?php if ($order['doba'] == 'денна'): ?>
                                    <span class="badge shift-badge shift-day">Денна</span>
                                    <?php else: ?>
                                    <span class="badge shift-badge shift-night">Нічна</span>
                                    <?php endif; ?>
                                </td>
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
                                <td>
                                    <a href="order_details.php?id=<?php echo $order['idd']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="text-center mt-3">
                    <a href="client_orders.php?id=<?php echo $clientId; ?>" class="btn btn-primary">
                        <i class="fas fa-clipboard-list me-1"></i> Всі замовлення клієнта
                    </a>
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> У клієнта поки що немає замовлень.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="card-footer text-center">
        <a href="clients.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Повернутися до списку клієнтів
        </a>
    </div>
</div>
<?php endif; ?>

<script>
    // Функція для відображення/приховування пароля
    document.addEventListener('DOMContentLoaded', function() {
        const showPasswordBtn = document.getElementById('showPasswordBtn');
        const passwordText = document.getElementById('passwordText');
        
        if (showPasswordBtn && passwordText) {
            showPasswordBtn.addEventListener('click', function() {
                if (passwordText.classList.contains('d-none')) {
                    passwordText.classList.remove('d-none');
                    showPasswordBtn.innerHTML = '<i class="fas fa-eye-slash"></i>';
                } else {
                    passwordText.classList.add('d-none');
                    showPasswordBtn.innerHTML = '<i class="fas fa-eye"></i>';
                }
            });
        }
    });
</script>

<?php
include_once '../../includes/footer.php';
?>