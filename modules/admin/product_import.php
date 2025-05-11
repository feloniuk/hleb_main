<?php 
$pageTitle = 'Імпорт продукції';

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
$importedCount = 0;
$skippedCount = 0;
$errorCount = 0;
$importDetails = [];

// Обробка форми
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])) {
    if ($_FILES['import_file']['error'] === UPLOAD_ERR_OK) {
        $fileType = pathinfo($_FILES['import_file']['name'], PATHINFO_EXTENSION);
        
        if ($fileType !== 'csv' && $fileType !== 'xls' && $fileType !== 'xlsx') {
            $error = 'Підтримуються тільки файли CSV, XLS та XLSX';
        } else {
            // Зчитування файлу
            $filePath = $_FILES['import_file']['tmp_name'];
            
            if ($fileType === 'csv') {
                // Обробка CSV-файлу
                $file = fopen($filePath, 'r');
                if ($file) {
                    // Пропуск заголовків
                    $headers = fgetcsv($file);
                    
                    // Обробка рядків
                    while (($row = fgetcsv($file)) !== false) {
                        if (count($row) >= 5) {
                            $nazvanie = trim($row[0]);
                            $ves = floatval(str_replace(',', '.', $row[1]));
                            $srok = intval($row[2]);
                            $stoimost = floatval(str_replace(',', '.', $row[3]));
                            $zena = floatval(str_replace(',', '.', $row[4]));
                            
                            // Перевірка обов'язкових полів
                            if (empty($nazvanie) || $ves <= 0 || $srok <= 0 || $stoimost <= 0 || $zena <= 0) {
                                $skippedCount++;
                                $importDetails[] = [
                                    'status' => 'skipped',
                                    'message' => 'Пропущено рядок з неповними даними: ' . implode(', ', $row)
                                ];
                                continue;
                            }
                            
                            // Перевірка, чи існує продукт з такою назвою
                            $checkQuery = "SELECT COUNT(*) as count FROM product WHERE nazvanie = ?";
                            $stmt = mysqli_prepare($connection, $checkQuery);
                            mysqli_stmt_bind_param($stmt, "s", $nazvanie);
                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);
                            $count = mysqli_fetch_assoc($result)['count'];
                            
                            if ($count > 0) {
                                // Оновлення існуючого продукту
                                $updateQuery = "UPDATE product SET ves = ?, srok = ?, stoimost = ?, zena = ? WHERE nazvanie = ?";
                                $stmt = mysqli_prepare($connection, $updateQuery);
                                mysqli_stmt_bind_param($stmt, "didds", $ves, $srok, $stoimost, $zena, $nazvanie);
                                
                                if (mysqli_stmt_execute($stmt)) {
                                    $importedCount++;
                                    $importDetails[] = [
                                        'status' => 'updated',
                                        'message' => 'Оновлено продукт: ' . $nazvanie
                                    ];
                                } else {
                                    $errorCount++;
                                    $importDetails[] = [
                                        'status' => 'error',
                                        'message' => 'Помилка при оновленні продукту: ' . $nazvanie . ' - ' . mysqli_error($connection)
                                    ];
                                }
                            } else {
                                // Додавання нового продукту
                                $insertQuery = "INSERT INTO product (nazvanie, ves, srok, stoimost, zena) VALUES (?, ?, ?, ?, ?)";
                                $stmt = mysqli_prepare($connection, $insertQuery);
                                mysqli_stmt_bind_param($stmt, "sdids", $nazvanie, $ves, $srok, $stoimost, $zena);
                                
                                if (mysqli_stmt_execute($stmt)) {
                                    $importedCount++;
                                    $importDetails[] = [
                                        'status' => 'imported',
                                        'message' => 'Додано новий продукт: ' . $nazvanie
                                    ];
                                } else {
                                    $errorCount++;
                                    $importDetails[] = [
                                        'status' => 'error',
                                        'message' => 'Помилка при додаванні продукту: ' . $nazvanie . ' - ' . mysqli_error($connection)
                                    ];
                                }
                            }
                        } else {
                            $skippedCount++;
                            $importDetails[] = [
                                'status' => 'skipped',
                                'message' => 'Пропущено рядок з недостатньою кількістю полів: ' . implode(', ', $row)
                            ];
                        }
                    }
                    
                    fclose($file);
                } else {
                    $error = 'Помилка при відкритті CSV-файлу';
                }
            } else {
                // Обробка Excel-файлу (XLS, XLSX)
                // Для повноцінної обробки Excel потрібна бібліотека PhpSpreadsheet або подібна
                $error = 'Для імпорту з Excel потрібно встановити бібліотеку PhpSpreadsheet';
            }
            
            if ($importedCount > 0) {
                $success = "Імпорт успішно завершено. Оброблено продуктів: $importedCount, пропущено: $skippedCount, помилок: $errorCount";
                
                // Запис в журнал
                logAction($connection, 'Імпорт продукції', "Імпортовано продуктів: $importedCount, пропущено: $skippedCount, помилок: $errorCount");
            } else {
                $error = 'Не вдалося імпортувати жодного продукту';
            }
        }
    } else {
        $error = 'Помилка при завантаженні файлу: ' . getUploadErrorMessage($_FILES['import_file']['error']);
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
            <a class="nav-link active" href="products.php">
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

<!-- Форма імпорту -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-file-import me-2"></i> Імпорт продукції
        </h5>
    </div>
    <div class="card-body">
        <div class="alert alert-info mb-4">
            <i class="fas fa-info-circle me-2"></i> Ви можете імпортувати продукцію з файлу CSV або Excel. Файл повинен мати наступні стовпці: Назва, Вага (кг), Строк реалізації (год), Собівартість (грн), Ціна (грн).
        </div>
        
        <form action="" method="POST" enctype="multipart/form-data" class="mb-4">
            <div class="mb-3">
                <label for="import_file" class="form-label">Виберіть файл для імпорту</label>
                <input type="file" class="form-control" id="import_file" name="import_file" accept=".csv, .xls, .xlsx" required>
                <div class="form-text">Підтримуються формати CSV, XLS та XLSX</div>
            </div>
            
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="update_existing" id="update_existing" value="1" checked>
                <label class="form-check-label" for="update_existing">
                    Оновлювати існуючі продукти
                </label>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="products.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Повернутися
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-file-import me-1"></i> Імпортувати
                </button>
            </div>
        </form>
        
        <div class="mb-4">
            <h5>Зразок файлу для імпорту</h5>
            <p>Завантажте <a href="download_product_template.php">шаблон CSV</a> для імпорту продукції.</p>
        </div>
        
        <?php if (!empty($importDetails)): ?>
            <div class="mt-4">
                <h5>Результати імпорту</h5>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Статус</th>
                                <th>Повідомлення</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($importDetails as $detail): ?>
                                <tr>
                                    <td>
                                        <?php
                                        switch ($detail['status']) {
                                            case 'imported':
                                                echo '<span class="badge bg-success">Додано</span>';
                                                break;
                                            case 'updated':
                                                echo '<span class="badge bg-info">Оновлено</span>';
                                                break;
                                            case 'skipped':
                                                echo '<span class="badge bg-warning">Пропущено</span>';
                                                break;
                                            case 'error':
                                                echo '<span class="badge bg-danger">Помилка</span>';
                                                break;
                                            default:
                                                echo '<span class="badge bg-secondary">Невідомо</span>';
                                                break;
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($detail['message']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
/**
 * Функція для отримання тексту помилки при завантаженні файлу
 * 
 * @param int $errorCode Код помилки
 * @return string Текст помилки
 */
function getUploadErrorMessage($errorCode) {
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
            return 'Розмір файлу перевищує допустимий ліміт, встановлений на сервері';
        case UPLOAD_ERR_FORM_SIZE:
            return 'Розмір файлу перевищує допустимий ліміт, встановлений у формі';
        case UPLOAD_ERR_PARTIAL:
            return 'Файл був завантажений лише частково';
        case UPLOAD_ERR_NO_FILE:
            return 'Файл не був завантажений';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Відсутня тимчасова директорія для завантаження файлів';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Не вдалося записати файл на диск';
        case UPLOAD_ERR_EXTENSION:
            return 'Завантаження файлу було припинено розширенням PHP';
        default:
            return 'Невідома помилка при завантаженні файлу';
    }
}

/**
 * Функція для запису дій у системний журнал
 * 
 * @param mysqli $connection Підключення до БД
 * @param string $action Тип дії
 * @param string $details Деталі дії
 * @return bool Результат запису
 */
function logAction($connection, $action, $details) {
    // Перевірка, чи існує таблиця
    $tableExistsQuery = "SHOW TABLES LIKE 'system_log'";
    $tableExistsResult = mysqli_query($connection, $tableExistsQuery);
    
    if (mysqli_num_rows($tableExistsResult) == 0) {
        return false;
    }
    
    $userId = $_SESSION['id'] ?? 0;
    $timestamp = date('Y-m-d H:i:s');
    
    $query = "INSERT INTO system_log (action, user_id, timestamp, details) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "siss", $action, $userId, $timestamp, $details);
    
    return mysqli_stmt_execute($stmt);
}

include_once '../../includes/footer.php';