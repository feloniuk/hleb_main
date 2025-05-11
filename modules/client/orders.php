<?php
$pageTitle = 'Мої замовлення';

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Перевірка доступу
if (!checkAccess(['client'])) {
    header("Location: ../../index.php");
    exit;
}

$connection = connectDatabase();
$clientId = $_SESSION['id'];

// Обробка параметра success
$success = isset($_GET['success']) ? $_GET['success'] : '';

// Параметри фільтрації та пагінації
$status = isset($_GET['status']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Формування базового запиту
$query = "SELECT z.*, p.nazvanie as product_name, p.ves, p.zena, (z.kol * p.zena) as total_price
           FROM zayavki z
           JOIN product p ON z.id = p.id
           WHERE z.idklient = ?";

$countQuery = "SELECT COUNT(*) as total FROM zayavki z WHERE z.idklient = ?";

$params = [$clientId];
$types = "i";

// Додавання фільтрації за статусом
if (!empty($status)) {
    $query .= " AND z.status = ?";
    $countQuery .= " AND z.status = ?";
    $params[] = $status;
    $types .= "s";
}

// Додавання сортування та обмеження
$query .= " ORDER BY z.data DESC, z.idd DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

// Виконання запиту для підрахунку загальної кількості
$countStmt = mysqli_prepare($connection, $countQuery);
if (count($params) == 1) {
    mysqli_stmt_bind_param($countStmt, "i", $params[0]);
} else {
    mysqli_stmt_bind_param($countStmt, "is", $params[0], $params[1]);
}
mysqli_stmt_execute($countStmt);
$countResult = mysqli_stmt_get_result($countStmt);
$totalCount = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalCount / $limit);

// Виконання основного запиту
$stmt = mysqli_prepare($connection, $query);
array_unshift($params, $types);
call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt], $params));
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

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
                <i class="fas fa-clipboard-list"></i> Мої замовлення
            </a>
            <a class="nav-link" href="products.php">
                <i class="fas fa-bread-slice"></i> Каталог продукції
            </a>
            <a class="nav-link" href="cart.php">
                <i class="fas fa-shopping-cart"></i> Кошик
            </a>
            <a class="nav-link" href="profile.php">
                <i class="fas fa-user"></i> Профіль
            </a>
        </nav>
    </div>
</div>

<?php if (!empty($success)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo htmlspecialchars($success); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-clipboard-list me-2"></i> Мої замовлення
            </h5>
            <a href="new_order.php" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Нове замовлення
            </a>
        </div>
    </div>
    <div class="card-body">
        <!-- Фільтри -->
        <div class="row mb-4">
            <div class="col-md-12">
                <form action="" method="GET" class="d-flex">
                    <div class="me-2">
                        <select name="status" class="form-select">
                            <option value="" <?php echo empty($status) ? 'selected' : ''; ?>>Всі статуси</option>
                            <option value="нове" <?php echo $status === 'нове' ? 'selected' : ''; ?>>Нові</option>
                            <option value="в обробці" <?php echo $status === 'в обробці' ? 'selected' : ''; ?>>В обробці</option>
                            <option value="виконано" <?php echo $status === 'виконано' ? 'selected' : ''; ?>>Виконані</option>
                            <option value="скасовано" <?php echo $status === 'скасовано' ? 'selected' : ''; ?>>Скасовані</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="fas fa-filter me-1"></i> Фільтрувати
                    </button>
                </form>
            </div>
        </div>

        <!-- Таблиця замовлень -->
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
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
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($order = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo $order['idd']; ?></td>
                                <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                <td><?php echo $order['kol']; ?> шт.</td>
                                <td><?php echo number_format($order['zena'], 2); ?> грн</td>
                                <td><?php echo number_format($order['total_price'], 2); ?> грн</td>
                                <td><?php echo formatDate($order['data']); ?></td>
                                <td><?php echo $order['doba'] === 'денна' ? 'Денна' : 'Нічна'; ?></td>
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
                                    <a href="order_details.php?id=<?php echo $order['idd']; ?>" class="btn btn-sm btn-info" title="Деталі">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($order['status'] === 'нове'): ?>
                                        <a href="cancel_order.php?id=<?php echo $order['idd']; ?>" class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Ви впевнені, що хочете скасувати це замовлення?');" title="Скасувати">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="repeat_order.php?id=<?php echo $order['idd']; ?>" class="btn btn-sm btn-success" title="Повторити">
                                        <i class="fas fa-redo"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center">У вас ще немає замовлень</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Пагінація -->
        <?php if ($totalPages > 1): ?>
            <nav aria-label="Навігація по сторінках">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $status; ?>" aria-label="Попередня">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $status; ?>" aria-label="Наступна">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?>