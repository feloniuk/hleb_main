<?php
/**
 * Файл заголовка для всіх сторінок системи "Одеський Коровай"
 */

// Визначення заголовка сторінки
$pageTitle = $pageTitle ?? 'Система управління "Одеський Коровай"';

// Отримання поточного користувача
$currentUser = null;
$userType = getUserType();

if (isset($_SESSION['id'])) {
    $userId = $_SESSION['id'];
    
    if ($userType === 'client') {
        // Отримання даних клієнта
        $userQuery = "SELECT * FROM klientu WHERE id = ?";
    } else {
        // Отримання даних співробітника
        $userQuery = "SELECT * FROM polzovateli WHERE id = ?";
    }
    
    $stmt = mysqli_prepare($connection, $userQuery);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $userResult = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($userResult) === 1) {
        $currentUser = mysqli_fetch_assoc($userResult);
    }
}

// Визначення ролі користувача
$userRole = getUserRole();

// Визначення URL для кабінету користувача
$dashboardUrl = '#';
if ($userType === 'client') {
    $dashboardUrl = '../../modules/client/dashboard.php';
} else {
    switch ($userRole) {
        case 'manager':
            $dashboardUrl = '../../modules/manager/dashboard.php';
            break;
        case 'brigadir':
            $dashboardUrl = '../../modules/supervisor/dashboard.php';
            break;
        case 'admin':
            $dashboardUrl = '../../modules/admin/dashboard.php';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/styles.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
        <div class="container">
            <a class="navbar-brand" href="<?php echo $dashboardUrl; ?>">
                <img src="../../assets/img/logo.png" alt="ТОВ Одеський Коровай" class="logo">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if ($userRole): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user me-1"></i>
                                <?php 
                                if ($currentUser) {
                                    // Для клієнтів показуємо назву організації
                                    if ($userType === 'client') {
                                        echo htmlspecialchars($currentUser['name']);
                                    } else {
                                        // Для співробітників показуємо ім'я
                                        echo htmlspecialchars($currentUser['name']);
                                    }
                                } else {
                                    echo 'Користувач';
                                }
                                ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <li>
                                    <span class="dropdown-item-text text-muted">
                                        <?php 
                                        if ($userType === 'client') {
                                            echo 'Клієнт';
                                        } else {
                                            switch ($userRole) {
                                                case 'manager':
                                                    echo 'Менеджер';
                                                    break;
                                                case 'brigadir':
                                                    echo 'Бригадир';
                                                    break;
                                                case 'admin':
                                                    echo 'Адміністратор';
                                                    break;
                                                default:
                                                    echo 'Користувач';
                                                    break;
                                            }
                                        }
                                        ?>
                                    </span>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <?php if ($userType === 'client'): ?>
                                <li><a class="dropdown-item" href="../../modules/client/profile.php"><i class="fas fa-user-cog me-1"></i> Профіль</a></li>
                                <li><a class="dropdown-item" href="../../modules/client/orders.php"><i class="fas fa-clipboard-list me-1"></i> Мої замовлення</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item" href="../../logout.php"><i class="fas fa-sign-out-alt me-1"></i> Вийти</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../../index.php"><i class="fas fa-sign-in-alt me-1"></i> Увійти</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Заголовок сторінки -->
        <div class="row mb-4">
            <div class="col-md-12">
                <h2><?php echo $pageTitle; ?></h2>
                <hr>
            </div>
        </div>