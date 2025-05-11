<?php
$pageTitle = 'Замовлення';

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

// Фільтри для пошуку
$client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : null;
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : null;
$doba = isset($_GET['doba']) ? $_GET['doba'] : null;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-7 days'));
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
$status = isset($_GET['status']) ? $_GET['status'] : null;

// Отримання списку клієнтів для фільтра
$clientsQuery = "SELECT * FROM klientu ORDER BY name ASC";
$clientsResult = mysqli_query($connection, $clientsQuery);

// Отримання списку продуктів для фільтра
$productsQuery = "SELECT * FROM product ORDER BY nazvanie ASC";
$productsResult = mysqli_query($connection, $productsQuery);

// Базовий запит для отримання замовлень з фільтрами
$ordersQuery = "SELECT z.idd, z.idklient, k.name as client_name, k.fio, z.id, p.nazvanie as product_name, 
                z.kol, z.data, z.doba, z.status, 
                (z.kol * p.zena) as total_price,
                p.zena, p.image
                FROM zayavki z
                JOIN klientu k ON z.idklient = k.id
                JOIN product p ON z.id = p.id
                WHERE 1=1";

// Додавання умов фільтрації
$params = [];
$types = '';

if ($client_id !== null) {
    $ordersQuery .= " AND z.idklient = ?";
    $params[] = $client_id;
    $types .= 'i';
}

if ($product_id !== null) {
    $ordersQuery .= " AND z.id = ?";
    $params[] = $product_id;
    $types .= 'i';
}

if ($doba !== null) {
    $ordersQuery .= " AND z.doba = ?";
    $params[] = $doba;
    $types .= 's';
}

if ($status !== null) {
    $ordersQuery .= " AND z.status = ?";
    $params[] = $status;
    $types .= 's';
}

if (!empty($date_from)) {
    $ordersQuery .= " AND z.data >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if (!empty($date_to)) {
    $ordersQuery .= " AND z.data <= ?";
    $params[] = $date_to;
    $types .= 's';
}

// Додавання сортування
$ordersQuery .= " ORDER BY z.data DESC, z.idd DESC";

// Підготовка та виконання запиту
$stmt = mysqli_prepare($connection, $ordersQuery);

if (!empty($params)) {
    // Прив'язка параметрів
    $bindParams = array_merge([$stmt, $types], $params);
    call_user_func_array('mysqli_stmt_bind_param', $bindParams);
}

mysqli_stmt_execute($stmt);
$ordersResult = mysqli_stmt_get_result($stmt);

// Отримання статистики для обраних фільтрів
$statsQuery = "SELECT 
                COUNT(z.idd) as total_orders,
                SUM(z.kol) as total_quantity,
                SUM(z.kol * p.zena) as total_amount,
                COUNT(DISTINCT z.id) as unique_products,
                COUNT(DISTINCT z.idklient) as unique_clients
              FROM zayavki z
              JOIN product p ON z.id = p.id
              WHERE 1=1";

// Додавання умов фільтрації
if (!empty($params)) {
    $statsQuery .= " AND " . substr($ordersQuery, strpos($ordersQuery, "WHERE 1=1") + 9, 
                                     strpos($ordersQuery, "ORDER BY") - strpos($ordersQuery, "WHERE 1=1") - 9);
    
    $stmtStats = mysqli_prepare($connection, $statsQuery);
    
    // Прив'язка параметрів
    $bindParams = array_merge([$stmtStats, $types], $params);
    call_user_func_array('mysqli_stmt_bind_param', $bindParams);
    
    mysqli_stmt_execute($stmtStats);
    $statsResult = mysqli_stmt_get_result($stmtStats);
    $stats = mysqli_fetch_assoc($statsResult);
} else {
    $statsResult = mysqli_query($connection, $statsQuery);
    $stats = mysqli_fetch_assoc($statsResult);
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

<!-- Фільтри -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-filter me-2"></i> Фільтри замовлень
        </h5>
    </div>
    <div class="card-body">
        <form method="get" action="" id="filter-form">
            <div class="row mb-3">
                <div class="col-md-3">
                    <label for="client_id" class="form-label">Клієнт</label>
                    <select class="form-select" id="client_id" name="client_id">
                        <option value="">Всі клієнти</option>
                        <?php while ($client = mysqli_fetch_assoc($clientsResult)): ?>
                            <option value="<?php echo $client['id']; ?>" <?php echo ($client_id === (int)$client['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($client['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="product_id" class="form-label">Продукт</label>
                    <select class="form-select" id="product_id" name="product_id">
                        <option value="">Всі продукти</option>
                        <?php mysqli_data_seek($productsResult, 0); ?>
                        <?php while ($product = mysqli_fetch_assoc($productsResult)): ?>
                            <option value="<?php echo $product['id']; ?>" <?php echo ($product_id === (int)$product['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($product['nazvanie']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="doba" class="form-label">Зміна</label>
                    <select class="form-select" id="doba" name="doba">
                        <option value="">Всі зміни</option>
                        <option value="денна" <?php echo ($doba === 'денна') ? 'selected' : ''; ?>>Денна</option>
                        <option value="нічна" <?php echo ($doba === 'нічна') ? 'selected' : ''; ?>>Нічна</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">Статус</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Всі статуси</option>
                        <option value="нове" <?php echo ($status === 'нове') ? 'selected' : ''; ?>>Нове</option>
                        <option value="в обробці" <?php echo ($status === 'в обробці') ? 'selected' : ''; ?>>В обробці</option>
                        <option value="виконано" <?php echo ($status === 'виконано') ? 'selected' : ''; ?>>Виконано</option>
                        <option value="скасовано" <?php echo ($status === 'скасовано') ? 'selected' : ''; ?>>Скасовано</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date_range" class="form-label">Діапазон дат</label>
                    <div class="input-group">
                        <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                        <span class="input-group-text">-</span>
                        <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i> Фільтрувати
                    </button>
                    <a href="orders.php" class="btn btn-outline-secondary">
                        <i class="fas fa-sync-alt me-1"></i> Скинути фільтри
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Статистика замовлень -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="card text-center h-100">
            <div class="card-body">
                <h5 class="card-title">Всього замовлень</h5>
                <p class="display-6"><?php echo $stats['total_orders']; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center h-100">
            <div class="card-body">
                <h5 class="card-title">Загальна кількість</h5>
                <p class="display-6"><?php echo $stats['total_quantity']; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center h-100">
            <div class="card-body">
                <h5 class="card-title">Сума (грн)</h5>
                <p class="display-6"><?php echo number_format($stats['total_amount'], 2); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center h-100">
            <div class="card-body">
                <h5 class="card-title">Унікальних продуктів</h5>
                <p class="display-6"><?php echo $stats['unique_products']; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center h-100">
            <div class="card-body">
                <h5 class="card-title">Унікальних клієнтів</h5>
                <p class="display-6"><?php echo $stats['unique_clients']; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Список замовлень -->
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i> Список замовлень
            </h5>
            <div>
                <button type="button" class="btn btn-sm btn-success" id="export-excel">
                    <i class="fas fa-file-excel me-1"></i> Експорт в Excel
                </button>
                <button type="button" class="btn btn-sm btn-danger" id="export-pdf">
                    <i class="fas fa-file-pdf me-1"></i> Експорт в PDF
                </button>
            </div>
        </div>
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
                        <th>Ціна</th>
                        <th>Сума</th>
                        <th>Дата</th>
                        <th>Зміна</th>
                        <th>Статус</th>
                        <th>Дії</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($ordersResult) > 0): ?>
                        <?php while ($order = mysqli_fetch_assoc($ordersResult)): ?>
                            <tr>
                                <td><?php echo $order['idd']; ?></td>
                                <td>
                                    <span data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo htmlspecialchars($order['fio']); ?>">
                                        <?php echo htmlspecialchars($order['client_name']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                <td><?php echo $order['kol']; ?></td>
                                <td><?php echo number_format($order['zena'], 2); ?> грн</td>
                                <td><?php echo number_format($order['total_price'], 2); ?> грн</td>
                                <td><?php echo formatDate($order['data']); ?></td>
                                <td>
                                    <?php if ($order['doba'] === 'денна'): ?>
                                        <span class="badge bg-warning text-dark">Денна</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary">Нічна</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    switch ($order['status']) {
                                        case 'нове':
                                            echo '<span class="badge bg-info">Нове</span>';
                                            break;
                                        case 'в обробці':
                                            echo '<span class="badge bg-warning text-dark">В обробці</span>';
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
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary view-order" data-id="<?php echo $order['idd']; ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-warning edit-status" data-id="<?php echo $order['idd']; ?>" data-status="<?php echo $order['status']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center">Немає замовлень за обраними фільтрами</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Модальне вікно для перегляду замовлення -->
<div class="modal fade" id="viewOrderModal" tabindex="-1" aria-labelledby="viewOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewOrderModalLabel">Деталі замовлення</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="border-bottom pb-2 mb-3">Інформація про замовлення</h6>
                        <div class="row mb-2">
                            <div class="col-4 fw-bold">ID замовлення:</div>
                            <div class="col-8" id="order-id"></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-4 fw-bold">Дата:</div>
                            <div class="col-8" id="order-date"></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-4 fw-bold">Зміна:</div>
                            <div class="col-8" id="order-doba"></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-4 fw-bold">Статус:</div>
                            <div class="col-8" id="order-status"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="border-bottom pb-2 mb-3">Інформація про клієнта</h6>
                        <div class="row mb-2">
                            <div class="col-4 fw-bold">Клієнт:</div>
                            <div class="col-8" id="client-name"></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-4 fw-bold">Контактна особа:</div>
                            <div class="col-8" id="client-fio"></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-4 fw-bold">Телефон:</div>
                            <div class="col-8" id="client-tel"></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-4 fw-bold">Адреса:</div>
                            <div class="col-8" id="client-address"></div>
                        </div>
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-md-12">
                        <h6 class="border-bottom pb-2 mb-3">Інформація про продукт</h6>
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <img id="product-image" src="" alt="Зображення продукту" class="img-fluid rounded" style="max-height: 150px;">
                            </div>
                            <div class="col-md-9">
                                <div class="row mb-2">
                                    <div class="col-4 fw-bold">Продукт:</div>
                                    <div class="col-8" id="product-name"></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-4 fw-bold">Кількість:</div>
                                    <div class="col-8" id="product-quantity"></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-4 fw-bold">Ціна за одиницю:</div>
                                    <div class="col-8" id="product-price"></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-4 fw-bold">Загальна сума:</div>
                                    <div class="col-8" id="order-total-price"></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-4 fw-bold">Вага одиниці:</div>
                                    <div class="col-8" id="product-weight"></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-4 fw-bold">Загальна вага:</div>
                                    <div class="col-8" id="order-total-weight"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрити</button>
                <button type="button" class="btn btn-primary" id="print-order">Друкувати</button>
            </div>
        </div>
    </div>
</div>

<!-- Модальне вікно для зміни статусу замовлення -->
<div class="modal fade" id="editStatusModal" tabindex="-1" aria-labelledby="editStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editStatusModalLabel">Змінити статус замовлення</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editStatusForm">
                    <input type="hidden" id="edit-order-id" name="order_id">
                    <div class="mb-3">
                        <label for="edit-status" class="form-label">Статус замовлення</label>
                        <select class="form-select" id="edit-status" name="status" required>
                            <option value="нове">Нове</option>
                            <option value="в обробці">В обробці</option>
                            <option value="виконано">Виконано</option>
                            <option value="скасовано">Скасовано</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit-comment" class="form-label">Коментар (опціонально)</label>
                        <textarea class="form-control" id="edit-comment" name="comment" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                <button type="button" class="btn btn-primary" id="save-status">Зберегти</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Ініціалізація підказок
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Обробник для перегляду деталей замовлення
        document.querySelectorAll('.view-order').forEach(function(button) {
            button.addEventListener('click', function() {
                var orderId = this.getAttribute('data-id');
                
                // AJAX запит для отримання інформації про замовлення
                fetch('get_order_details.php?id=' + orderId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            var order = data.data;
                            
                            // Заповнення модального вікна даними про замовлення
                            document.getElementById('order-id').textContent = order.idd;
                            document.getElementById('order-date').textContent = formatDate(order.data);
                            document.getElementById('order-doba').textContent = order.doba === 'денна' ? 'Денна' : 'Нічна';
                            
                            // Статус
                            var statusBadge = '';
                            switch (order.status) {
                                case 'нове':
                                    statusBadge = '<span class="badge bg-info">Нове</span>';
                                    break;
                                case 'в обробці':
                                    statusBadge = '<span class="badge bg-warning text-dark">В обробці</span>';
                                    break;
                                case 'виконано':
                                    statusBadge = '<span class="badge bg-success">Виконано</span>';
                                    break;
                                case 'скасовано':
                                    statusBadge = '<span class="badge bg-danger">Скасовано</span>';
                                    break;
                                default:
                                    statusBadge = '<span class="badge bg-secondary">Невідомо</span>';
                                    break;
                            }
                            document.getElementById('order-status').innerHTML = statusBadge;
                            
                            // Інформація про клієнта
                            document.getElementById('client-name').textContent = order.client_name;
                            document.getElementById('client-fio').textContent = order.fio;
                            document.getElementById('client-tel').textContent = order.tel;
                            document.getElementById('client-address').textContent = order.city + ', ' + order.adres;
                            
                            // Інформація про продукт
                            document.getElementById('product-name').textContent = order.product_name;
                            document.getElementById('product-quantity').textContent = order.kol + ' шт.';
                            document.getElementById('product-price').textContent = parseFloat(order.zena).toFixed(2) + ' грн';
                            document.getElementById('order-total-price').textContent = parseFloat(order.total_price).toFixed(2) + ' грн';
                            document.getElementById('product-weight').textContent = parseFloat(order.ves).toFixed(2) + ' кг';
                            document.getElementById('order-total-weight').textContent = parseFloat(order.total_weight).toFixed(2) + ' кг';
                            
                            // Встановлення зображення продукту
                            var imagePath = order.image_url || '../../assets/img/product-placeholder.jpg';
                            document.getElementById('product-image').src = imagePath;
                            
                            // Показати модальне вікно
                            var viewOrderModal = new bootstrap.Modal(document.getElementById('viewOrderModal'));
                            viewOrderModal.show();
                        } else {
                            alert('Помилка при отриманні даних: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Помилка при обробці запиту.');
                    });
            });
        });
        
        // Обробник для зміни статусу замовлення
        document.querySelectorAll('.edit-status').forEach(function(button) {
            button.addEventListener('click', function() {
                var orderId = this.getAttribute('data-id');
                var currentStatus = this.getAttribute('data-status');
                
                document.getElementById('edit-order-id').value = orderId;
                document.getElementById('edit-status').value = currentStatus;
                
                var editStatusModal = new bootstrap.Modal(document.getElementById('editStatusModal'));
                editStatusModal.show();
            });
        });
        
        // Обробник для збереження статусу замовлення
        document.getElementById('save-status').addEventListener('click', function() {
            var orderId = document.getElementById('edit-order-id').value;
            var status = document.getElementById('edit-status').value;
            var comment = document.getElementById('edit-comment').value;
            
            // AJAX запит для збереження статусу
            fetch('update_order_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    order_id: orderId,
                    status: status,
                    comment: comment
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Закриття модального вікна
                    var editStatusModal = bootstrap.Modal.getInstance(document.getElementById('editStatusModal'));
                    editStatusModal.hide();
                    
                    // Оновлення сторінки
                    window.location.reload();
                } else {
                    alert('Помилка при збереженні статусу: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Помилка при обробці запиту.');
            });
        });
        
        // Обробник для друку замовлення
        document.getElementById('print-order').addEventListener('click', function() {
            var orderId = document.getElementById('order-id').textContent;
            window.open('print_order.php?id=' + orderId, '_blank');
        });
        
        // Обробник для експорту в Excel
        document.getElementById('export-excel').addEventListener('click', function() {
            // Отримання параметрів фільтрації
            var params = new URLSearchParams(window.location.search);
            window.location.href = 'export_orders_excel.php?' + params.toString();
        });
        
        // Обробник для експорту в PDF
        document.getElementById('export-pdf').addEventListener('click', function() {
            // Отримання параметрів фільтрації
            var params = new URLSearchParams(window.location.search);
            window.location.href = 'export_orders_pdf.php?' + params.toString();
        });
        
        // Функція форматування дати
        function formatDate(dateString) {
            var date = new Date(dateString);
            var day = date.getDate().toString().padStart(2, '0');
            var month = (date.getMonth() + 1).toString().padStart(2, '0');
            var year = date.getFullYear();
            
            return day + '.' + month + '.' + year;
        }
    });
</script>

<?php include_once '../../includes/footer.php'; ?>