<?php
$pageTitle = 'Виробництво';

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

// Отримання дати для відображення (поточна, якщо не вказана)
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$shift = isset($_GET['shift']) ? $_GET['shift'] : 'денна';

// Обробка відправки форми для оновлення статусу виробництва
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_production'])) {
    $productionIds = $_POST['production_id'] ?? [];
    $statuses = $_POST['status'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    
    if (!empty($productionIds)) {
        $successCount = 0;
        
        // Обробка кожного елемента виробництва
        for ($i = 0; $i < count($productionIds); $i++) {
            if (isset($productionIds[$i]) && isset($statuses[$i]) && isset($quantities[$i])) {
                $productionId = intval($productionIds[$i]);
                $status = $statuses[$i];
                $quantity = intval($quantities[$i]);
                
                // Перевірка валідності статусу
                if (!in_array($status, ['в черзі', 'у виробництві', 'виконано', 'скасовано'])) {
                    continue;
                }
                
                // Оновлення бази даних
                $updateQuery = "UPDATE zayavki SET status = ?, kol = ? WHERE idd = ?";
                $stmt = mysqli_prepare($connection, $updateQuery);
                mysqli_stmt_bind_param($stmt, "sii", $status, $quantity, $productionId);
                
                if (mysqli_stmt_execute($stmt)) {
                    $successCount++;
                } else {
                    $error = "Помилка при оновленні статусу: " . mysqli_error($connection);
                    break;
                }
            }
        }
        
        if (!$error) {
            $success = "Успішно оновлено $successCount записів виробництва";
        }
    }
}

// Отримання списку продукції для виробництва
$productionQuery = "SELECT z.idd, p.id as product_id, p.nazvanie as product_name, p.ves, p.image,
                    z.kol, z.data, z.doba, z.status, k.name as client_name
                    FROM zayavki z
                    JOIN product p ON z.id = p.id
                    JOIN klientu k ON z.idklient = k.id
                    WHERE DATE(z.data) = ? AND z.doba = ?
                    ORDER BY 
                    CASE 
                        WHEN z.status = 'в черзі' THEN 1
                        WHEN z.status = 'у виробництві' THEN 2
                        WHEN z.status = 'виконано' THEN 3
                        WHEN z.status = 'скасовано' THEN 4
                        ELSE 5
                    END, p.nazvanie";

$stmt = mysqli_prepare($connection, $productionQuery);
mysqli_stmt_bind_param($stmt, "ss", $date, $shift);
mysqli_stmt_execute($stmt);
$productionResult = mysqli_stmt_get_result($stmt);

// Отримання загальної статистики по виробництву
$statsQuery = "SELECT 
                COUNT(z.idd) as total_orders,
                SUM(z.kol) as total_quantity,
                SUM(z.kol * p.ves) as total_weight,
                COUNT(DISTINCT z.id) as unique_products,
                SUM(CASE WHEN z.status = 'в черзі' THEN z.kol ELSE 0 END) as queue_quantity,
                SUM(CASE WHEN z.status = 'у виробництві' THEN z.kol ELSE 0 END) as in_progress_quantity,
                SUM(CASE WHEN z.status = 'виконано' THEN z.kol ELSE 0 END) as completed_quantity
              FROM zayavki z
              JOIN product p ON z.id = p.id
              WHERE DATE(z.data) = ? AND z.doba = ?";

$statsStmt = mysqli_prepare($connection, $statsQuery);
mysqli_stmt_bind_param($statsStmt, "ss", $date, $shift);
mysqli_stmt_execute($statsStmt);
$statsResult = mysqli_stmt_get_result($statsStmt);
$stats = mysqli_fetch_assoc($statsResult);

// Отримання унікальних дат для навігації
$datesQuery = "SELECT DISTINCT DATE(data) as date FROM zayavki ORDER BY date DESC LIMIT 10";
$datesResult = mysqli_query($connection, $datesQuery);

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
            <a class="nav-link active" href="production.php">
                <i class="fas fa-industry"></i> Виробництво
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

<!-- Фільтри по даті та зміні -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-filter me-2"></i> Фільтри
        </h5>
    </div>
    <div class="card-body">
        <form method="get" action="" class="row">
            <div class="col-md-4">
                <label for="date" class="form-label">Дата:</label>
                <select class="form-select" id="date" name="date">
                    <?php 
                    // Додавання поточної дати
                    $today = date('Y-m-d');
                    echo '<option value="' . $today . '"' . ($date === $today ? ' selected' : '') . '>Сьогодні (' . date('d.m.Y') . ')</option>';
                    
                    // Додавання дат з бази даних
                    while ($dateRow = mysqli_fetch_assoc($datesResult)):
                        if ($dateRow['date'] !== $today): // Пропускаємо сьогоднішню дату (вже додали)
                    ?>
                        <option value="<?php echo $dateRow['date']; ?>" <?php echo ($date === $dateRow['date']) ? 'selected' : ''; ?>>
                            <?php echo date('d.m.Y', strtotime($dateRow['date'])); ?>
                        </option>
                    <?php 
                        endif;
                    endwhile; 
                    ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="shift" class="form-label">Зміна:</label>
                <select class="form-select" id="shift" name="shift">
                    <option value="денна" <?php echo ($shift === 'денна') ? 'selected' : ''; ?>>Денна</option>
                    <option value="нічна" <?php echo ($shift === 'нічна') ? 'selected' : ''; ?>>Нічна</option>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-1"></i> Застосувати фільтри
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Картки зі статистикою -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center h-100">
            <div class="card-body">
                <i class="fas fa-clipboard-list fa-3x mb-3 text-primary"></i>
                <h5 class="card-title">Всього замовлень</h5>
                <p class="card-text display-6"><?php echo $stats['total_orders']; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center h-100">
            <div class="card-body">
                <i class="fas fa-box fa-3x mb-3 text-success"></i>
                <h5 class="card-title">Загальна кількість</h5>
                <p class="card-text display-6"><?php echo $stats['total_quantity']; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center h-100">
            <div class="card-body">
                <i class="fas fa-weight-hanging fa-3x mb-3 text-warning"></i>
                <h5 class="card-title">Загальна вага</h5>
                <p class="card-text display-6"><?php echo number_format($stats['total_weight'], 2); ?> кг</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center h-100">
            <div class="card-body">
                <i class="fas fa-bread-slice fa-3x mb-3 text-danger"></i>
                <h5 class="card-title">Унікальних продуктів</h5>
                <p class="card-text display-6"><?php echo $stats['unique_products']; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Прогрес виробництва -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-chart-pie me-2"></i> Прогрес виробництва
        </h5>
    </div>
    <div class="card-body">
        <?php
        // Розрахунок відсотків
        $totalItems = $stats['total_quantity'] > 0 ? $stats['total_quantity'] : 1; // Запобігання ділення на нуль
        $queuePercent = round(($stats['queue_quantity'] / $totalItems) * 100);
        $inProgressPercent = round(($stats['in_progress_quantity'] / $totalItems) * 100);
        $completedPercent = round(($stats['completed_quantity'] / $totalItems) * 100);
        ?>
        <div class="progress" style="height: 30px;">
            <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $queuePercent; ?>%" 
                aria-valuenow="<?php echo $queuePercent; ?>" aria-valuemin="0" aria-valuemax="100">
                В черзі (<?php echo $stats['queue_quantity']; ?>)
            </div>
            <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $inProgressPercent; ?>%" 
                aria-valuenow="<?php echo $inProgressPercent; ?>" aria-valuemin="0" aria-valuemax="100">
                У виробництві (<?php echo $stats['in_progress_quantity']; ?>)
            </div>
            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $completedPercent; ?>%" 
                aria-valuenow="<?php echo $completedPercent; ?>" aria-valuemin="0" aria-valuemax="100">
                Виконано (<?php echo $stats['completed_quantity']; ?>)
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-4">
                <div class="d-flex align-items-center">
                    <div class="badge bg-warning me-2">&nbsp;</div>
                    <span>В черзі: <?php echo $stats['queue_quantity']; ?> (<?php echo $queuePercent; ?>%)</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="d-flex align-items-center">
                    <div class="badge bg-info me-2">&nbsp;</div>
                    <span>У виробництві: <?php echo $stats['in_progress_quantity']; ?> (<?php echo $inProgressPercent; ?>%)</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="d-flex align-items-center">
                    <div class="badge bg-success me-2">&nbsp;</div>
                    <span>Виконано: <?php echo $stats['completed_quantity']; ?> (<?php echo $completedPercent; ?>%)</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Список продукції у виробництві -->
<form method="post" action="">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-industry me-2"></i> Виробництво на <?php echo date('d.m.Y', strtotime($date)); ?> (<?php echo ($shift === 'денна' ? 'Денна зміна' : 'Нічна зміна'); ?>)
            </h5>
            <button type="submit" name="update_production" class="btn btn-success">
                <i class="fas fa-save me-1"></i> Зберегти зміни
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Продукт</th>
                            <th>Кількість</th>
                            <th>Клієнт</th>
                            <th>Стан</th>
                            <th>Дії</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($productionResult) > 0): ?>
                            <?php while ($item = mysqli_fetch_assoc($productionResult)): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php 
                                            $imagePath = !empty($item['image']) ? '../../' . $item['image'] : '../../assets/img/product-placeholder.jpg';
                                            ?>
                                            <img src="<?php echo $imagePath; ?>" class="rounded me-2" style="width: 50px; height: 50px; object-fit: cover;" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                                <small class="text-muted">ID: <?php echo $item['product_id']; ?> | Вага: <?php echo $item['ves']; ?> кг</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="hidden" name="production_id[]" value="<?php echo $item['idd']; ?>">
                                        <input type="number" class="form-control" name="quantity[]" value="<?php echo $item['kol']; ?>" min="1" required>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['client_name']); ?></td>
                                    <td>
                                        <select class="form-select" name="status[]">
                                            <option value="в черзі" <?php echo ($item['status'] === 'в черзі') ? 'selected' : ''; ?>>В черзі</option>
                                            <option value="у виробництві" <?php echo ($item['status'] === 'у виробництві') ? 'selected' : ''; ?>>У виробництві</option>
                                            <option value="виконано" <?php echo ($item['status'] === 'виконано') ? 'selected' : ''; ?>>Виконано</option>
                                            <option value="скасовано" <?php echo ($item['status'] === 'скасовано') ? 'selected' : ''; ?>>Скасовано</option>
                                        </select>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="print_order.php?id=<?php echo $item['idd']; ?>" class="btn btn-outline-primary btn-sm" target="_blank">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-info btn-sm view-details" data-id="<?php echo $item['idd']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">Немає продукції у виробництві за обраними параметрами</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" name="update_production" class="btn btn-success">
                <i class="fas fa-save me-1"></i> Зберегти зміни
            </button>
        </div>
    </div>
</form>

<!-- Модальне вікно для перегляду деталей -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailsModalLabel">Деталі замовлення</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Завантаження...</span>
                    </div>
                    <p>Завантаження деталей...</p>
                </div>
                <div id="orderDetails" style="display: none;">
                    <!-- Деталі замовлення будуть завантажені через Ajax -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрити</button>
                <a href="#" class="btn btn-primary" id="printOrderBtn" target="_blank">
                    <i class="fas fa-print me-1"></i> Друкувати
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Обробка кліку на кнопці перегляду деталей
        document.querySelectorAll('.view-details').forEach(function(button) {
            button.addEventListener('click', function() {
                const orderId = this.getAttribute('data-id');
                
                // Скидаємо модальне вікно
                document.querySelector('.spinner-border').style.display = 'inline-block';
                document.querySelector('#orderDetails').style.display = 'none';
                document.querySelector('#printOrderBtn').href = 'print_order.php?id=' + orderId;
                
                // Показуємо модальне вікно
                const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
                modal.show();
                
                // Завантажуємо дані через Ajax
                fetch('get_order_details.php?id=' + orderId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Приховуємо спіннер
                            document.querySelector('.spinner-border').style.display = 'none';
                            
                            // Створюємо HTML з деталями замовлення
                            const order = data.data;
                            
                            let statusBadge = '';
                            switch (order.status) {
                                case 'в черзі':
                                    statusBadge = '<span class="badge bg-warning">В черзі</span>';
                                    break;
                                case 'у виробництві':
                                    statusBadge = '<span class="badge bg-info">У виробництві</span>';
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
                            
                            let html = `
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5 class="border-bottom pb-2 mb-3">Інформація про замовлення</h5>
                                        <p><strong>ID замовлення:</strong> ${order.idd}</p>
                                        <p><strong>Дата:</strong> ${new Date(order.data).toLocaleDateString()}</p>
                                        <p><strong>Зміна:</strong> ${order.doba === 'денна' ? 'Денна' : 'Нічна'}</p>
                                        <p><strong>Статус:</strong> ${statusBadge}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <h5 class="border-bottom pb-2 mb-3">Інформація про клієнта</h5>
                                        <p><strong>Клієнт:</strong> ${order.client_name}</p>
                                        <p><strong>Контактна особа:</strong> ${order.fio}</p>
                                        <p><strong>Телефон:</strong> ${order.tel}</p>
                                        <p><strong>Адреса:</strong> ${order.city}, ${order.adres}</p>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <h5 class="border-bottom pb-2 mb-3">Інформація про продукт</h5>
                                        <div class="d-flex">
                                            <img src="${order.image_url}" class="rounded me-3" style="width: 100px; height: 100px; object-fit: cover;" alt="${order.product_name}">
                                            <div>
                                                <p><strong>Назва:</strong> ${order.product_name}</p>
                                                <p><strong>Кількість:</strong> ${order.kol}</p>
                                                <p><strong>Вага одиниці:</strong> ${order.ves} кг</p>
                                                <p><strong>Загальна вага:</strong> ${(order.kol * order.ves).toFixed(2)} кг</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                            
                            // Додаємо HTML до модального вікна
                            document.querySelector('#orderDetails').innerHTML = html;
                            document.querySelector('#orderDetails').style.display = 'block';
                        } else {
                            document.querySelector('.spinner-border').style.display = 'none';
                            document.querySelector('#orderDetails').innerHTML = '<div class="alert alert-danger">Помилка при завантаженні деталей: ' + data.message + '</div>';
                            document.querySelector('#orderDetails').style.display = 'block';
                        }
                    })
                    .catch(error => {
                        document.querySelector('.spinner-border').style.display = 'none';
                        document.querySelector('#orderDetails').innerHTML = '<div class="alert alert-danger">Помилка при завантаженні деталей. Спробуйте пізніше.</div>';
                        document.querySelector('#orderDetails').style.display = 'block';
                        console.error('Error:', error);
                    });
            });
        });
    });
</script>

<?php include_once '../../includes/footer.php'; ?>