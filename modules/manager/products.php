<?php
$pageTitle = 'Управління продукцією';

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

// Обробка видалення продукту
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $productId = $_GET['delete'];
    
    $deleteQuery = "DELETE FROM product WHERE id = ?";
    $stmt = mysqli_prepare($connection, $deleteQuery);
    mysqli_stmt_bind_param($stmt, "i", $productId);
    
    if (mysqli_stmt_execute($stmt)) {
        $success = 'Продукт успішно видалено.';
    } else {
        $error = 'Помилка при видаленні продукту: ' . mysqli_error($connection);
    }
    
    mysqli_stmt_close($stmt);
}

// Отримання списку продукції
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$sortColumn = isset($_GET['sort']) ? $_GET['sort'] : 'nazvanie';
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'ASC';

// Валідація сортування
$allowedColumns = ['id', 'nazvanie', 'ves', 'srok', 'stoimost', 'zena'];
if (!in_array($sortColumn, $allowedColumns)) {
    $sortColumn = 'nazvanie';
}

$allowedOrders = ['ASC', 'DESC'];
if (!in_array($sortOrder, $allowedOrders)) {
    $sortOrder = 'ASC';
}

$query = "SELECT * FROM product WHERE 1=1";

if (!empty($searchTerm)) {
    $searchTerm = '%' . $searchTerm . '%';
    $query .= " AND (nazvanie LIKE ? OR id LIKE ?)";
}

$query .= " ORDER BY $sortColumn $sortOrder";

$stmt = mysqli_prepare($connection, $query);

if (!empty($searchTerm)) {
    mysqli_stmt_bind_param($stmt, "ss", $searchTerm, $searchTerm);
}

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

<!-- Панель управління -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="mb-0">
                    <i class="fas fa-bread-slice me-2"></i> Список продукції
                </h5>
            </div>
            <div class="col-md-6 text-end">
                <a href="product_add.php" class="btn btn-success">
                    <i class="fas fa-plus me-1"></i> Додати продукт
                </a>
                <a href="product_export.php" class="btn btn-info ms-2">
                    <i class="fas fa-file-export me-1"></i> Експорт в PDF
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Форма пошуку -->
<div class="card mb-4">
    <div class="card-body">
        <form action="" method="GET" class="row g-3">
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" name="search" placeholder="Пошук за назвою або ID..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                </div>
            </div>
            <div class="col-md-2">
                <select name="sort" class="form-select">
                    <option value="nazvanie" <?php echo ($sortColumn == 'nazvanie') ? 'selected' : ''; ?>>Назва</option>
                    <option value="id" <?php echo ($sortColumn == 'id') ? 'selected' : ''; ?>>ID</option>
                    <option value="ves" <?php echo ($sortColumn == 'ves') ? 'selected' : ''; ?>>Вага</option>
                    <option value="srok" <?php echo ($sortColumn == 'srok') ? 'selected' : ''; ?>>Строк реалізації</option>
                    <option value="stoimost" <?php echo ($sortColumn == 'stoimost') ? 'selected' : ''; ?>>Собівартість</option>
                    <option value="zena" <?php echo ($sortColumn == 'zena') ? 'selected' : ''; ?>>Ціна</option>
                </select>
            </div>
            <div class="col-md-1">
                <select name="order" class="form-select">
                    <option value="ASC" <?php echo ($sortOrder == 'ASC') ? 'selected' : ''; ?>>За зростанням</option>
                    <option value="DESC" <?php echo ($sortOrder == 'DESC') ? 'selected' : ''; ?>>За спаданням</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100">Фільтр</button>
            </div>
        </form>
    </div>
</div>

<!-- Таблиця продукції -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Зображення</th>
                        <th>Назва</th>
                        <th>Вага (кг)</th>
                        <th>Строк реалізації (год)</th>
                        <th>Собівартість (грн)</th>
                        <th>Ціна (грн)</th>
                        <th>Дії</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($product = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo $product['id']; ?></td>
                                <td>
                                    <?php 
                                    $imagePath = !empty($product['image']) ? '../../' . $product['image'] : '../../assets/img/product-placeholder.jpg';
                                    ?>
                                    <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($product['nazvanie']); ?>" style="width: 50px; height: 50px; object-fit: cover;" class="img-thumbnail">
                                </td>
                                <td><?php echo htmlspecialchars($product['nazvanie']); ?></td>
                                <td><?php echo $product['ves']; ?></td>
                                <td><?php echo $product['srok']; ?></td>
                                <td><?php echo $product['stoimost']; ?></td>
                                <td><?php echo $product['zena']; ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="product_details.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Перегляд">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="product_edit.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-warning" data-bs-toggle="tooltip" title="Редагувати">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="products.php?delete=<?php echo $product['id']; ?>" class="btn btn-sm btn-danger btn-delete" data-bs-toggle="tooltip" title="Видалити" onclick="return confirm('Ви впевнені, що хочете видалити цей продукт?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">Продукти не знайдено</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Пагінація -->
<?php
$totalQuery = "SELECT COUNT(*) as total FROM product";
$totalResult = mysqli_query($connection, $totalQuery);
$totalProducts = mysqli_fetch_assoc($totalResult)['total'];
$totalPages = ceil($totalProducts / 10); // 10 продуктів на сторінку

$currentPage = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($currentPage < 1) $currentPage = 1;
if ($currentPage > $totalPages) $currentPage = $totalPages;

if ($totalPages > 1):
?>
<div class="d-flex justify-content-center mt-4">
    <nav aria-label="Навігація по сторінкам">
        <ul class="pagination">
            <li class="page-item <?php echo ($currentPage == 1) ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=1<?php echo !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo !empty($_GET['sort']) ? '&sort=' . urlencode($_GET['sort']) : ''; ?><?php echo !empty($_GET['order']) ? '&order=' . urlencode($_GET['order']) : ''; ?>" aria-label="Перша">
                    <span aria-hidden="true">&laquo;&laquo;</span>
                </a>
            </li>
            <li class="page-item <?php echo ($currentPage == 1) ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $currentPage - 1; ?><?php echo !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo !empty($_GET['sort']) ? '&sort=' . urlencode($_GET['sort']) : ''; ?><?php echo !empty($_GET['order']) ? '&order=' . urlencode($_GET['order']) : ''; ?>" aria-label="Попередня">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
            
            <?php
            $startPage = max(1, $currentPage - 2);
            $endPage = min($totalPages, $currentPage + 2);
            
            for ($i = $startPage; $i <= $endPage; $i++):
            ?>
            <li class="page-item <?php echo ($i == $currentPage) ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo !empty($_GET['sort']) ? '&sort=' . urlencode($_GET['sort']) : ''; ?><?php echo !empty($_GET['order']) ? '&order=' . urlencode($_GET['order']) : ''; ?>"><?php echo $i; ?></a>
            </li>
            <?php endfor; ?>
            
            <li class="page-item <?php echo ($currentPage == $totalPages) ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $currentPage + 1; ?><?php echo !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo !empty($_GET['sort']) ? '&sort=' . urlencode($_GET['sort']) : ''; ?><?php echo !empty($_GET['order']) ? '&order=' . urlencode($_GET['order']) : ''; ?>" aria-label="Наступна">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
            <li class="page-item <?php echo ($currentPage == $totalPages) ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $totalPages; ?><?php echo !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo !empty($_GET['sort']) ? '&sort=' . urlencode($_GET['sort']) : ''; ?><?php echo !empty($_GET['order']) ? '&order=' . urlencode($_GET['order']) : ''; ?>" aria-label="Остання">
                    <span aria-hidden="true">&raquo;&raquo;</span>
                </a>
            </li>
        </ul>
    </nav>
</div>
<?php endif; ?>

<!-- Статистика -->
<div class="card mt-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="text-center">
                    <h5>Загальна кількість продуктів</h5>
                    <p class="display-6"><?php echo $totalProducts; ?></p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center">
                    <h5>Середня вага</h5>
                    <?php
                    $avgWeightQuery = "SELECT AVG(ves) as avg_weight FROM product";
                    $avgWeightResult = mysqli_query($connection, $avgWeightQuery);
                    $avgWeight = mysqli_fetch_assoc($avgWeightResult)['avg_weight'];
                    ?>
                    <p class="display-6"><?php echo number_format($avgWeight, 2); ?> кг</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center">
                    <h5>Середня ціна</h5>
                    <?php
                    $avgPriceQuery = "SELECT AVG(zena) as avg_price FROM product";
                    $avgPriceResult = mysqli_query($connection, $avgPriceQuery);
                    $avgPrice = mysqli_fetch_assoc($avgPriceResult)['avg_price'];
                    ?>
                    <p class="display-6"><?php echo number_format($avgPrice, 2); ?> грн</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include_once '../../includes/footer.php';
?>