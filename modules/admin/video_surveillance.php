<?php
$pageTitle = 'Відеоспостереження';

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Перевірка доступу
if (!checkAccess(['admin'])) {
    header("Location: ../../index.php");
    exit;
}

$connection = connectDatabase();

// Отримання налаштувань камер з бази даних
$camerasQuery = "SELECT * FROM video_cameras ORDER BY location";
$camerasResult = mysqli_query($connection, $camerasQuery);

// Якщо таблиця не існує, створимо її
if (!$camerasResult) {
    $createTableQuery = "CREATE TABLE IF NOT EXISTS `video_cameras` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `location` varchar(255) NOT NULL,
        `stream_url` varchar(500) NOT NULL,
        `snapshot_url` varchar(500) DEFAULT NULL,
        `is_active` tinyint(1) DEFAULT 1,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
    
    mysqli_query($connection, $createTableQuery);
    
    // Додаємо тестові камери
    $insertQuery = "INSERT INTO `video_cameras` (`name`, `location`, `stream_url`, `snapshot_url`) VALUES
        ('Камера 1', 'Головний вхід', 'http://192.168.1.100:8080/video', 'http://192.168.1.100:8080/shot.jpg'),
        ('Камера 2', 'Виробничий цех', 'http://192.168.1.101:8080/video', 'http://192.168.1.101:8080/shot.jpg'),
        ('Камера 3', 'Склад готової продукції', 'http://192.168.1.102:8080/video', 'http://192.168.1.102:8080/shot.jpg'),
        ('Камера 4', 'Завантажувальна зона', 'http://192.168.1.103:8080/video', 'http://192.168.1.103:8080/shot.jpg')";
    
    mysqli_query($connection, $insertQuery);
    
    // Повторно отримуємо камери
    $camerasResult = mysqli_query($connection, $camerasQuery);
}

// Проверяем, есть ли уже localhost камера
$checkLocalhostQuery = "SELECT id FROM video_cameras WHERE stream_url LIKE '%localhost%' OR stream_url LIKE '%127.0.0.1%' LIMIT 1";
$localhostResult = mysqli_query($connection, $checkLocalhostQuery);

if (mysqli_num_rows($localhostResult) == 0) {
    // Если нет localhost камеры, добавляем её
    $insertLocalhostQuery = "INSERT INTO video_cameras (name, location, stream_url, snapshot_url, is_active) VALUES 
        ('Веб-камера ПК', 'Локальний комп\'ютер', 'http://localhost:8080/video', 'http://localhost:8080/shot.jpg', 1)";
    
    mysqli_query($connection, $insertLocalhostQuery);
    
    // Логируем добавление
    error_log("Добавлена локальная веб-камера в систему видеонаблюдения");
}

// Обробка додавання нової камери
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_camera'])) {
    $name = mysqli_real_escape_string($connection, $_POST['camera_name']);
    $location = mysqli_real_escape_string($connection, $_POST['camera_location']);
    $stream_url = mysqli_real_escape_string($connection, $_POST['stream_url']);
    $snapshot_url = mysqli_real_escape_string($connection, $_POST['snapshot_url']);
    
    $insertQuery = "INSERT INTO video_cameras (name, location, stream_url, snapshot_url) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($connection, $insertQuery);
    mysqli_stmt_bind_param($stmt, "ssss", $name, $location, $stream_url, $snapshot_url);
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: video_surveillance.php?success=1");
        exit;
    }
}

// Обробка видалення камери
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $cameraId = intval($_GET['delete']);
    $deleteQuery = "DELETE FROM video_cameras WHERE id = ?";
    $stmt = mysqli_prepare($connection, $deleteQuery);
    mysqli_stmt_bind_param($stmt, "i", $cameraId);
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: video_surveillance.php?deleted=1");
        exit;
    }
}

// Обробка зміни статусу камери
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $cameraId = intval($_GET['toggle']);
    $toggleQuery = "UPDATE video_cameras SET is_active = NOT is_active WHERE id = ?";
    $stmt = mysqli_prepare($connection, $toggleQuery);
    mysqli_stmt_bind_param($stmt, "i", $cameraId);
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: video_surveillance.php");
        exit;
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
            <a class="nav-link" href="users.php">
                <i class="fas fa-users"></i> Користувачі
            </a>
            <a class="nav-link" href="clients.php">
                <i class="fas fa-user-tie"></i> Клієнти
            </a>
            <a class="nav-link" href="products.php">
                <i class="fas fa-bread-slice"></i> Продукція
            </a>
            <a class="nav-link" href="orders.php">
                <i class="fas fa-clipboard-list"></i> Замовлення
            </a>
            <a class="nav-link active" href="video_surveillance.php">
                <i class="fas fa-video"></i> Відеоспостереження
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

<!-- Повідомлення про успіх -->
<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i> Камеру успішно додано!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i> Камеру успішно видалено!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Панель керування -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-video me-2"></i> Система відеоспостереження
                    </h5>
                    <div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCameraModal">
                            <i class="fas fa-plus-circle me-1"></i> Додати камеру
                        </button>
                        <button class="btn btn-success" onclick="refreshAllCameras()">
                            <i class="fas fa-sync-alt me-1"></i> Оновити всі
                        </button>
                        <button class="btn btn-secondary" onclick="toggleFullscreen()">
                            <i class="fas fa-expand me-1"></i> На весь екран
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row" id="camerasGrid">
                    <?php if (mysqli_num_rows($camerasResult) > 0): ?>
                        <?php while ($camera = mysqli_fetch_assoc($camerasResult)): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100 camera-card">
                                    <div class="card-header bg-dark text-white">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">
                                                <i class="fas fa-video me-1"></i> <?php echo htmlspecialchars($camera['name']); ?>
                                            </h6>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-sm btn-success" onclick="refreshCamera(<?php echo $camera['id']; ?>)" title="Оновити">
                                                    <i class="fas fa-sync-alt"></i>
                                                </button>
                                                <button class="btn btn-sm btn-info" onclick="fullscreenCamera(<?php echo $camera['id']; ?>)" title="Повний екран">
                                                    <i class="fas fa-expand"></i>
                                                </button>
                                                <a href="?toggle=<?php echo $camera['id']; ?>" class="btn btn-sm btn-warning" title="Вимкнути/Увімкнути">
                                                    <i class="fas fa-power-off"></i>
                                                </a>
                                                <a href="?delete=<?php echo $camera['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Ви впевнені, що хочете видалити цю камеру?')" title="Видалити">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body p-0 position-relative">
                                        <div class="camera-container" id="camera-<?php echo $camera['id']; ?>">
                                            <?php if ($camera['is_active']): ?>
                                                <img src="<?php echo htmlspecialchars($camera['snapshot_url']); ?>" 
                                                     class="camera-image img-fluid" 
                                                     alt="<?php echo htmlspecialchars($camera['name']); ?>"
                                                     onerror="this.src='../../assets/img/camera-offline.jpg'">
                                                <div class="camera-overlay">
                                                    <div class="camera-info">
                                                        <small class="text-white">
                                                            <i class="fas fa-map-marker-alt me-1"></i> 
                                                            <?php echo htmlspecialchars($camera['location']); ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <div class="camera-offline d-flex align-items-center justify-content-center">
                                                    <div class="text-center text-muted">
                                                        <i class="fas fa-video-slash fa-3x mb-2"></i>
                                                        <p>Камера вимкнена</p>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="camera-timestamp position-absolute bottom-0 end-0 p-2">
                                            <small class="text-white bg-dark px-2 py-1 rounded">
                                                <span id="timestamp-<?php echo $camera['id']; ?>"><?php echo date('H:i:s'); ?></span>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> 
                                Камери не налаштовані. Натисніть "Додати камеру" для початку роботи.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Журнал подій -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i> Журнал подій відеоспостереження
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Час</th>
                                <th>Камера</th>
                                <th>Подія</th>
                                <th>Деталі</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php echo date('d.m.Y H:i:s', strtotime('-5 minutes')); ?></td>
                                <td>Камера 1 - Головний вхід</td>
                                <td><span class="badge bg-warning">Рух</span></td>
                                <td>Виявлено рух в зоні спостереження</td>
                            </tr>
                            <tr>
                                <td><?php echo date('d.m.Y H:i:s', strtotime('-15 minutes')); ?></td>
                                <td>Камера 3 - Склад</td>
                                <td><span class="badge bg-info">Відкриття</span></td>
                                <td>Відкрито двері складу</td>
                            </tr>
                            <tr>
                                <td><?php echo date('d.m.Y H:i:s', strtotime('-30 minutes')); ?></td>
                                <td>Камера 2 - Виробничий цех</td>
                                <td><span class="badge bg-success">Початок зміни</span></td>
                                <td>Розпочато денну зміну</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальне вікно додавання камери -->
<div class="modal fade" id="addCameraModal" tabindex="-1" aria-labelledby="addCameraModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCameraModalLabel">
                    <i class="fas fa-plus-circle me-2"></i> Додати нову камеру
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="camera_name" class="form-label">Назва камери</label>
                        <input type="text" class="form-control" id="camera_name" name="camera_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="camera_location" class="form-label">Розташування</label>
                        <input type="text" class="form-control" id="camera_location" name="camera_location" required>
                    </div>
                    <div class="mb-3">
                        <label for="stream_url" class="form-label">URL потоку відео</label>
                        <input type="url" class="form-control" id="stream_url" name="stream_url" 
                               placeholder="http://192.168.1.100:8080/video" required>
                        <small class="form-text text-muted">Введіть URL для отримання відеопотоку з камери</small>
                    </div>
                    <div class="mb-3">
                        <label for="snapshot_url" class="form-label">URL знімку</label>
                        <input type="url" class="form-control" id="snapshot_url" name="snapshot_url" 
                               placeholder="http://192.168.1.100:8080/shot.jpg">
                        <small class="form-text text-muted">Введіть URL для отримання знімків з камери</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                    <button type="submit" name="add_camera" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Зберегти
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- CSS стилі для камер -->
<style>
    .camera-card {
        border: 1px solid #dee2e6;
        transition: box-shadow 0.3s ease;
    }
    
    .camera-card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
    
    .camera-container {
        position: relative;
        background-color: #000;
        min-height: 240px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .camera-image {
        width: 100%;
        height: auto;
        max-height: 300px;
        object-fit: contain;
    }
    
    .camera-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        background: linear-gradient(to bottom, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0) 100%);
        padding: 10px;
    }
    
    .camera-offline {
        width: 100%;
        height: 240px;
        background-color: #f8f9fa;
    }
    
    .camera-timestamp {
        z-index: 10;
    }
    
    .fullscreen-camera {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: #000;
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .fullscreen-camera img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }
    
    .fullscreen-controls {
        position: absolute;
        top: 20px;
        right: 20px;
        z-index: 10000;
    }
</style>

<!-- JavaScript для оновлення камер -->
<script>
    // Масив для збереження інтервалів оновлення
    let refreshIntervals = {};
    
    // Функція оновлення окремої камери
    function refreshCamera(cameraId) {
        const cameraElement = document.querySelector(`#camera-${cameraId} img`);
        if (cameraElement) {
            const originalSrc = cameraElement.src.split('?')[0];
            cameraElement.src = originalSrc + '?t=' + new Date().getTime();
            
            // Оновлення часової мітки
            const timestampElement = document.querySelector(`#timestamp-${cameraId}`);
            if (timestampElement) {
                timestampElement.textContent = new Date().toLocaleTimeString('uk-UA');
            }
        }
    }
    
    // Функція оновлення всіх камер
    function refreshAllCameras() {
        const cameras = document.querySelectorAll('.camera-image');
        cameras.forEach((camera) => {
            const cameraId = camera.closest('.camera-container').id.split('-')[1];
            refreshCamera(cameraId);
        });
    }
    
    // Автоматичне оновлення кожні 5 секунд
    <?php 
    mysqli_data_seek($camerasResult, 0);
    while ($camera = mysqli_fetch_assoc($camerasResult)): 
        if ($camera['is_active']):
    ?>
        refreshIntervals[<?php echo $camera['id']; ?>] = setInterval(function() {
            refreshCamera(<?php echo $camera['id']; ?>);
        }, 5000);
    <?php 
        endif;
    endwhile; 
    ?>
    
    // Функція для відображення камери на повний екран
    function fullscreenCamera(cameraId) {
        const cameraImage = document.querySelector(`#camera-${cameraId} img`);
        if (cameraImage) {
            const fullscreenDiv = document.createElement('div');
            fullscreenDiv.className = 'fullscreen-camera';
            fullscreenDiv.innerHTML = `
                <div class="fullscreen-controls">
                    <button class="btn btn-light" onclick="closeFullscreen()">
                        <i class="fas fa-times"></i> Закрити
                    </button>
                </div>
                <img src="${cameraImage.src}" alt="Повноекранний перегляд">
            `;
            document.body.appendChild(fullscreenDiv);
            
            // Оновлення зображення кожні 2 секунди в повноекранному режимі
            const fullscreenInterval = setInterval(function() {
                const img = fullscreenDiv.querySelector('img');
                if (img) {
                    const originalSrc = img.src.split('?')[0];
                    img.src = originalSrc + '?t=' + new Date().getTime();
                }
            }, 2000);
            
            // Збереження інтервалу для очищення
            fullscreenDiv.dataset.interval = fullscreenInterval;
        }
    }
    
    // Функція закриття повноекранного режиму
    function closeFullscreen() {
        const fullscreenDiv = document.querySelector('.fullscreen-camera');
        if (fullscreenDiv) {
            clearInterval(fullscreenDiv.dataset.interval);
            fullscreenDiv.remove();
        }
    }
    
    // Функція для переходу в повноекранний режим всієї сторінки
    function toggleFullscreen() {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen();
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            }
        }
    }
    
    // Обробка клавіші Escape для виходу з повноекранного режиму
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeFullscreen();
        }
    });
    
    // Очищення інтервалів при виході зі сторінки
    window.addEventListener('beforeunload', function() {
        Object.values(refreshIntervals).forEach(interval => clearInterval(interval));
    });

    (function() {
    // Проверяем поддержку getUserMedia
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        console.warn('getUserMedia не поддерживается в этом браузере');
        return;
    }

    // Функция для проверки, является ли URL локальным
    function isLocalhost(url) {
        return url && (url.includes('localhost') || url.includes('127.0.0.1'));
    }

    // Функция для инициализации локальной веб-камеры
    async function initLocalWebcam(cameraId, containerElement) {
        try {
            // Получаем доступ к веб-камере
            const stream = await navigator.mediaDevices.getUserMedia({ 
                video: {
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                },
                audio: false 
            });

            // Создаем video элемент
            const videoElement = document.createElement('video');
            videoElement.autoplay = true;
            videoElement.playsinline = true;
            videoElement.muted = true;
            videoElement.className = 'camera-image img-fluid';
            videoElement.style.width = '100%';
            videoElement.style.height = 'auto';
            videoElement.style.maxHeight = '300px';
            videoElement.style.objectFit = 'contain';

            // Подключаем поток к video элементу
            videoElement.srcObject = stream;

            // Заменяем img на video элемент
            const imgElement = containerElement.querySelector('img');
            if (imgElement) {
                imgElement.replaceWith(videoElement);
            }

            // Сохраняем ссылку на поток для последующей остановки
            containerElement.dataset.stream = 'active';
            window.localWebcamStreams = window.localWebcamStreams || {};
            window.localWebcamStreams[cameraId] = stream;

            // Обновляем функцию refresh для этой камеры
            window['refreshCamera' + cameraId] = function() {
                // Для локальной камеры просто обновляем timestamp
                const timestampElement = document.querySelector(`#timestamp-${cameraId}`);
                if (timestampElement) {
                    timestampElement.textContent = new Date().toLocaleTimeString('uk-UA');
                }
            };

            // Переопределяем функцию полноэкранного режима для видео
            window['fullscreenCamera' + cameraId] = function() {
                const fullscreenDiv = document.createElement('div');
                fullscreenDiv.className = 'fullscreen-camera';
                fullscreenDiv.style.cursor = 'pointer';
                
                const videoClone = videoElement.cloneNode(true);
                videoClone.style.maxWidth = '100%';
                videoClone.style.maxHeight = '100%';
                videoClone.srcObject = stream;
                
                fullscreenDiv.innerHTML = `
                    <div class="fullscreen-controls">
                        <button class="btn btn-light" onclick="closeFullscreen()">
                            <i class="fas fa-times"></i> Закрити
                        </button>
                    </div>
                `;
                fullscreenDiv.appendChild(videoClone);
                
                // Закрытие по клику на видео
                videoClone.addEventListener('click', function() {
                    closeFullscreen();
                });
                
                document.body.appendChild(fullscreenDiv);
            };

            console.log(`Локальная веб-камера успешно подключена к камере ${cameraId}`);

        } catch (error) {
            console.error('Ошибка доступа к веб-камере:', error);
            
            // Показываем сообщение об ошибке
            const errorDiv = document.createElement('div');
            errorDiv.className = 'camera-offline d-flex align-items-center justify-content-center';
            errorDiv.innerHTML = `
                <div class="text-center text-muted">
                    <i class="fas fa-video-slash fa-3x mb-2"></i>
                    <p>Веб-камера недоступна</p>
                    <small>${error.message}</small>
                </div>
            `;
            
            const imgElement = containerElement.querySelector('img');
            if (imgElement) {
                imgElement.replaceWith(errorDiv);
            }
        }
    }

    // Функция для поиска и инициализации всех localhost камер
    function initializeLocalCameras() {
        // Получаем все активные камеры
        const cameraContainers = document.querySelectorAll('.camera-container');
        
        cameraContainers.forEach(container => {
            const cameraId = container.id.split('-')[1];
            const imgElement = container.querySelector('img');
            
            if (imgElement && imgElement.src) {
                // Проверяем, является ли URL локальным
                if (isLocalhost(imgElement.src)) {
                    console.log(`Обнаружена локальная камера: ${cameraId}`);
                    initLocalWebcam(cameraId, container);
                }
            }
        });
    }

    // Переопределяем глобальные функции
    const originalRefreshCamera = window.refreshCamera;
    window.refreshCamera = function(cameraId) {
        // Проверяем, есть ли специальная функция для этой камеры
        if (window['refreshCamera' + cameraId]) {
            window['refreshCamera' + cameraId]();
        } else {
            originalRefreshCamera(cameraId);
        }
    };

    const originalFullscreenCamera = window.fullscreenCamera;
    window.fullscreenCamera = function(cameraId) {
        // Проверяем, есть ли специальная функция для этой камеры
        if (window['fullscreenCamera' + cameraId]) {
            window['fullscreenCamera' + cameraId]();
        } else {
            originalFullscreenCamera(cameraId);
        }
    };

    // Функция для остановки всех локальных потоков
    function stopAllLocalStreams() {
        if (window.localWebcamStreams) {
            Object.values(window.localWebcamStreams).forEach(stream => {
                if (stream && stream.getTracks) {
                    stream.getTracks().forEach(track => track.stop());
                }
            });
            window.localWebcamStreams = {};
        }
    }

    // Добавляем остановку потоков при выходе со страницы
    window.addEventListener('beforeunload', stopAllLocalStreams);

    // Ждем загрузки DOM и инициализируем
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeLocalCameras);
    } else {
        // DOM уже загружен
        setTimeout(initializeLocalCameras, 100);
    }

    // Добавляем стили для видео элементов
    const style = document.createElement('style');
    style.textContent = `
        .camera-container video {
            background-color: #000;
            display: block;
        }
        
        .fullscreen-camera video {
            cursor: pointer;
        }
        
        /* Анимация для индикации работы камеры */
        .camera-container[data-stream="active"]::before {
            content: "LIVE";
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #dc3545;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            z-index: 100;
            animation: blink 2s infinite;
        }
        
        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0.3; }
        }
    `;
    document.head.appendChild(style);

})();
</script>

<!-- Дополнительная информация для пользователя -->
<script>
// Показываем уведомление при первом посещении
document.addEventListener('DOMContentLoaded', function() {
    // Проверяем, есть ли localhost камеры
    const hasLocalhostCameras = Array.from(document.querySelectorAll('.camera-image')).some(img => 
        img.src && (img.src.includes('localhost') || img.src.includes('127.0.0.1'))
    );
    
    if (hasLocalhostCameras && !localStorage.getItem('webcamNoticeShown')) {
        // Создаем уведомление
        const notice = document.createElement('div');
        notice.className = 'alert alert-info alert-dismissible fade show position-fixed';
        notice.style.cssText = 'top: 20px; right: 20px; z-index: 1050; max-width: 400px;';
        notice.innerHTML = `
            <h6 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Використання веб-камери</h6>
            <p class="mb-2">Система виявила камери з локальними адресами (localhost). 
            Браузер запитає дозвіл на доступ до вашої веб-камери.</p>
            <small>Натисніть "Дозволити" для підключення камери.</small>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        document.body.appendChild(notice);
        
        // Сохраняем, что уведомление было показано
        localStorage.setItem('webcamNoticeShown', 'true');
        
        // Автоматически скрываем через 10 секунд
        setTimeout(() => {
            notice.classList.remove('show');
            setTimeout(() => notice.remove(), 300);
        }, 10000);
    }
});
</script>

<?php
include_once '../../includes/footer.php';
?>