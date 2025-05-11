<?php
$pageTitle = 'Нові замовлення';

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

// Обробка затвердження замовлення
if (isset($_GET['approve']) && !empty($_GET['approve'])) {
    $orderId = intval($_GET['approve']);
    
    // Отримуємо інформацію про нове замовлення
    $getOrderQuery = "SELECT * FROM newzayavki WHERE idd = ?";
    $stmt = mysqli_prepare($connection, $getOrderQuery);
    mysqli_stmt_bind_param($stmt, "i", $orderId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 1) {
        $order = mysqli_fetch_assoc($result);
        
        // Додаємо замовлення до основної таблиці
        $insertQuery = "INSERT INTO zayavki (idklient, id, kol, data, doba, status) 
                       VALUES (?, ?, ?, ?, ?, 'нове')";
        $stmt = mysqli_prepare($connection, $insertQuery);
        mysqli_stmt_bind_param($stmt, "iiiss", 
            $order['idklient'], $order['id'], $order['kol'], $order['data'], $order['doba']);
        
        if (mysqli_stmt_execute($stmt)) {
            // Видаляємо з таблиці нових замовлень
            $deleteQuery = "DELETE FROM newzayavki WHERE idd = ?";
            $stmt = mysqli_prepare($connection, $deleteQuery);
            mysqli_stmt_bind_param($stmt, "i", $orderId);
            mysqli_stmt_execute($stmt);
            
            $success = 'Замовлення успішно затверджено і додано до списку замовлень.';
        } else {
            $error = 'Помилка при затвердженні замовлення: ' . mysqli_error($connection);
        }
    } else {
        $error = 'Замовлення не знайдено.';
    }
}

// Обробка відхилення замовлення
if (isset($_GET['reject']) && !empty($_GET['reject'])) {
    $orderId = intval($_GET['reject']);
    
    // Видаляємо замовлення
    $deleteQuery = "DELETE FROM newzayavki WHERE idd = ?";
    $stmt = mysqli_prepare($connection, $deleteQuery);
    mysqli_stmt_bind_param($stmt, "i", $orderId);
    
    if (mysqli_stmt_execute($stmt)) {
        $success = 'Замовлення успішно відхилено.';
    } else {
        $error = 'Помилка при відхиленні замовлення: ' . mysqli_error($connection);
    }
}

// Отримання списку нових замовлень
$query = "SELECT nz.idd, nz.idklient, k.name as client_name, nz.id, p.nazvanie as product_name, 
         nz.kol, nz.data, nz.doba
         FROM newzayavki nz
         JOIN klientu k ON nz.idklient = k.id
         JOIN product p ON nz.id = p.id
         ORDER BY nz.data DESC, nz.idd DESC";

$result = mysqli_query($connection, $query);

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
            <a class="nav-link" href="clients.php">
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

<!-- Підменю для замовлень -->
<div class="row mb-4">
    <div class="col-md-12">
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link" href="orders.php">
                    <i class="fas fa-list"></i> Всі замовлення
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="new_orders.php">
                    <i class="fas fa-plus-circle"></i> Нові замовлення
                    <?php
                    $countQuery = "SELECT COUNT(*) as count FROM newzayavki";
                    $countResult = mysqli_query($connection, $countQuery);
                    $count = mysqli_fetch_assoc($countResult)['count'];
                    if ($count > 0) {
                        echo '<span class="badge bg-danger ms-1">' . $count . '</span>';
                    }
                    ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="order_add.php">
                    <i class="fas fa-plus"></i> Додати замовлення
                </a>
            </li>
        </ul>
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

<!-- Таблиця нових замовлень -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-plus-circle me-2"></i> Нові замовлення
        </h5>
    </div>
    <div class="card-body">
        <?php if (mysqli_num_rows($result) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Клієнт</th>
                            <th>Продукт</th>
                            <th>Кількість</th>
                            <th>Дата</th>
                            <th>Зміна</th>
                            <th>Дії</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo $order['idd']; ?></td>
                                <td><?php echo htmlspecialchars($order['client_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                <td><?php echo $order['kol']; ?></td>
                                <td><?php echo formatDate($order['data']); ?></td>
                                <td>
                                    <?php if ($order['doba'] == 'денна'): ?>
                                    <span class="badge shift-badge shift-day">Денна</span>
                                    <?php else: ?>
                                    <span class="badge shift-badge shift-night">Нічна</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#orderDetailsModal<?php echo $order['idd']; ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <a href="new_orders.php?approve=<?php echo $order['idd']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Затвердити це замовлення?');">
                                            <i class="fas fa-check"></i>
                                        </a>
                                        <a href="new_orders.php?reject=<?php echo $order['idd']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Ви впевнені, що хочете відхилити це замовлення?');">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    </div>
                                    
                                    <!-- Модальне вікно з деталями замовлення -->
                                    <div class="modal fade" id="orderDetailsModal<?php echo $order['idd']; ?>" tabindex="-1" aria-labelledby="orderDetailsModalLabel<?php echo $order['idd']; ?>" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="orderDetailsModalLabel<?php echo $order['idd']; ?>">Деталі замовлення #<?php echo $order['idd']; ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <?php
                                                    // Отримання детальної інформації про замовлення
                                                    $detailsQuery = "SELECT nz.*, k.*, p.* 
                                                                   FROM newzayavki nz
                                                                   JOIN klientu k ON nz.idklient = k.id
                                                                   JOIN product p ON nz.id = p.id
                                                                   WHERE nz.idd = ?";
                                                    $stmt = mysqli_prepare($connection, $detailsQuery);
                                                    mysqli_stmt_bind_param($stmt, "i", $order['idd']);
                                                    mysqli_stmt_execute($stmt);
                                                    $detailsResult = mysqli_stmt_get_result($stmt);
                                                    $details = mysqli_fetch_assoc($detailsResult);
                                                    
                                                    // Розрахунок загальної суми
                                                    $totalPrice = $details['kol'] * $details['zena'];
                                                    ?>
                                                    
                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <h6>Інформація про клієнта</h6>
                                                            <p><strong>Назва компанії:</strong> <?php echo htmlspecialchars($details['name']); ?></p>
                                                            <p><strong>Контактна особа:</strong> <?php echo htmlspecialchars($details['fio']); ?></p>
                                                            <p><strong>Телефон:</strong> <?php echo htmlspecialchars($details['tel']); ?></p>
                                                            <p><strong>Адреса:</strong> <?php echo htmlspecialchars($details['city'] . ', ' . $details['adres']); ?></p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <h6>Інформація про замовлення</h6>
                                                            <p><strong>Дата:</strong> <?php echo formatDate($details['data']); ?></p>
                                                            <p><strong>Зміна:</strong> <?php echo ($details['doba'] == 'денна') ? 'Денна' : 'Нічна'; ?></p>
                                                            <p><strong>Продукт:</strong> <?php echo htmlspecialchars($details['nazvanie']); ?></p>
                                                            <p><strong>Кількість:</strong> <?php echo $details['kol']; ?></p>
                                                            <p><strong>Ціна за одиницю:</strong> <?php echo $details['zena']; ?> грн</p>
                                                            <p><strong>Загальна сума:</strong> <?php echo number_format($totalPrice, 2); ?> грн</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрити</button>
                                                    <a href="new_orders.php?approve=<?php echo $order['idd']; ?>" class="btn btn-success" onclick="return confirm('Затвердити це замовлення?');">
                                                        <i class="fas fa-check me-1"></i> Затвердити
                                                    </a>
                                                    <a href="new_orders.php?reject=<?php echo $order['idd']; ?>" class="btn btn-danger" onclick="return confirm('Ви впевнені, що хочете відхилити це замовлення?');">
                                                        <i class="fas fa-times me-1"></i> Відхилити
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i> Нових замовлень не знайдено.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
include_once '../../includes/footer.php';
?>