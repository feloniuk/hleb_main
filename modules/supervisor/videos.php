<?php
$pageTitle = 'Навчальні відео';

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Перевірка доступу
if (!checkAccess(['brigadir'])) {
    header("Location: ../../index.php");
    exit;
}

$connection = connectDatabase();

// Масив з навчальними відео
$videosList = [
    [
        'id' => 1,
        'title' => 'Процес випікання хліба',
        'description' => 'Детальний огляд процесу випікання хліба від замішування тіста до готового продукту.',
        'link' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        'category' => 'Виробництво'
    ],
    [
        'id' => 2,
        'title' => 'Використання сканера штрих-кодів',
        'description' => 'Інструкція з використання сканера штрих-кодів для обліку продукції.',
        'link' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        'category' => 'Обладнання'
    ],
    [
        'id' => 3,
        'title' => 'Підготовка звітів по зміні',
        'description' => 'Правила підготовки та формування звітів по денній та нічній змінах.',
        'link' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        'category' => 'Документація'
    ],
    [
        'id' => 4,
        'title' => 'Контроль якості готової продукції',
        'description' => 'Методи перевірки якості готової хлібобулочної продукції.',
        'link' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        'category' => 'Якість'
    ],
    [
        'id' => 5,
        'title' => 'Техніка безпеки на виробництві',
        'description' => 'Основні правила техніки безпеки на хлібопекарському виробництві.',
        'link' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        'category' => 'Безпека'
    ],
    [
        'id' => 6,
        'title' => 'Робота з клієнтами',
        'description' => 'Рекомендації щодо ефективної взаємодії з клієнтами та вирішення конфліктних ситуацій.',
        'link' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        'category' => 'Комунікація'
    ],
];

// Фільтрація за категорією, якщо вказана
$filteredVideos = $videosList;
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $category = $_GET['category'];
    $filteredVideos = array_filter($videosList, function($video) use ($category) {
        return $video['category'] === $category;
    });
}

// Отримання унікальних категорій
$categories = array_unique(array_column($videosList, 'category'));

include_once '../../includes/header.php';
?>

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
            <a class="nav-link active" href="videos.php">
                <i class="fas fa-video"></i> Відео
            </a>
            <a class="nav-link" href="products.php">
                <i class="fas fa-bread-slice"></i> Продукція
            </a>
        </nav>
    </div>
</div>

<!-- Фільтр за категоріями -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-filter me-2"></i> Фільтр відео
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex flex-wrap">
                    <a href="videos.php" class="btn <?php echo (!isset($_GET['category'])) ? 'btn-primary' : 'btn-outline-primary'; ?> me-2 mb-2">
                        Всі відео
                    </a>
                    <?php foreach ($categories as $category): ?>
                        <a href="videos.php?category=<?php echo urlencode($category); ?>" class="btn <?php echo (isset($_GET['category']) && $_GET['category'] === $category) ? 'btn-primary' : 'btn-outline-primary'; ?> me-2 mb-2">
                            <?php echo htmlspecialchars($category); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Список відео -->
<div class="row">
    <?php foreach ($filteredVideos as $video): ?>
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo htmlspecialchars($video['title']); ?></h5>
                </div>
                <div class="card-body">
                    <div class="embed-responsive embed-responsive-16by9 mb-3">
                        <iframe class="embed-responsive-item w-100" height="300" src="<?php echo $video['link']; ?>" allowfullscreen></iframe>
                    </div>
                    <p><?php echo htmlspecialchars($video['description']); ?></p>
                    <span class="badge bg-primary"><?php echo htmlspecialchars($video['category']); ?></span>
                </div>
                <div class="card-footer">
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#videoModal<?php echo $video['id']; ?>">
                        <i class="fas fa-play-circle me-1"></i> Переглянути у повноекранному режимі
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Модальне вікно для перегляду відео на повний екран -->
        <div class="modal fade" id="videoModal<?php echo $video['id']; ?>" tabindex="-1" aria-labelledby="videoModalLabel<?php echo $video['id']; ?>" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="videoModalLabel<?php echo $video['id']; ?>"><?php echo htmlspecialchars($video['title']); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="embed-responsive embed-responsive-16by9">
                            <iframe class="embed-responsive-item w-100" height="600" src="<?php echo $video['link']; ?>" allowfullscreen></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    
    <?php if (count($filteredVideos) === 0): ?>
        <div class="col-md-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i> Немає відео для обраної категорії. 
                <a href="videos.php" class="alert-link">Показати всі відео</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include_once '../../includes/footer.php'; ?>