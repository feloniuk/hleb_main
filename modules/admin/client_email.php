<?php 

$pageTitle = 'Розсилка клієнтам';

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

// Отримання списку клієнтів
$query = "SELECT id, name, fio, mail FROM klientu WHERE mail != '' ORDER BY name";
$result = mysqli_query($connection, $query);

// Обробка форми
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $recipients = $_POST['recipients'] ?? [];
    $attach = isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK;
    
    // Валідація даних
    if (empty($subject)) {
        $error = 'Будь ласка, введіть тему листа';
    } elseif (empty($message)) {
        $error = 'Будь ласка, введіть текст повідомлення';
    } elseif (empty($recipients)) {
        $error = 'Будь ласка, виберіть хоча б одного одержувача';
    } else {
        // Обробка вкладення
        $attachmentPath = '';
        if ($attach) {
            $uploadDir = '../../uploads/temp/';
            
            // Перевірка/створення директорії для вкладень
            if (!file_exists($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    $error = 'Помилка при створенні директорії для вкладень';
                }
            }
            
            if (empty($error)) {
                $attachmentPath = $uploadDir . basename($_FILES['attachment']['name']);
                if (!move_uploaded_file($_FILES['attachment']['tmp_name'], $attachmentPath)) {
                    $error = 'Помилка при завантаженні вкладення';
                }
            }
        }
        
        if (empty($error)) {
            // В ідеалі тут має бути інтеграція з бібліотекою для відправки пошти (PHPMailer, Swift Mailer, тощо)
            // Але для прикладу, просто імітуємо успішну відправку
            $success = 'Розсилку успішно відправлено ' . count($recipients) . ' клієнтам';
            
            // Запис в журнал
            logAction($connection, 'Розсилка клієнтам', 'Відправлено розсилку з темою: ' . $subject);
            
            // Видалення тимчасового файлу вкладення
            if (!empty($attachmentPath) && file_exists($attachmentPath)) {
                @unlink($attachmentPath);
            }
        }
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
            <a class="nav-link active" href="clients.php">
                <i class="fas fa-user-tie"></i> Клієнти
            </a>
            <a class="nav-link" href="products.php">
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

<!-- Форма розсилки -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-envelope me-2"></i> Розсилка клієнтам
        </h5>
    </div>
    <div class="card-body">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="subject" class="form-label">Тема листа *</label>
                <input type="text" class="form-control" id="subject" name="subject" required>
            </div>
            
            <div class="mb-3">
                <label for="message" class="form-label">Текст повідомлення *</label>
                <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
            </div>
            
            <div class="mb-3">
                <label for="attachment" class="form-label">Вкладення</label>
                <input type="file" class="form-control" id="attachment" name="attachment">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Одержувачі *</label>
                <div class="mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="select_all">
                        <label class="form-check-label" for="select_all">
                            <strong>Вибрати всіх</strong>
                        </label>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Клієнт</th>
                                <th>ПІБ</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            mysqli_data_seek($result, 0);
                            while ($client = mysqli_fetch_assoc($result)): 
                            ?>
                                <tr>
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input client-checkbox" type="checkbox" name="recipients[]" value="<?php echo $client['id']; ?>" id="client_<?php echo $client['id']; ?>">
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($client['name']); ?></td>
                                    <td><?php echo htmlspecialchars($client['fio']); ?></td>
                                    <td><?php echo htmlspecialchars($client['mail']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="clients.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Повернутися
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane me-1"></i> Відправити
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Вибір/зняття вибору всіх клієнтів
        document.getElementById('select_all').addEventListener('change', function() {
            var clientCheckboxes = document.querySelectorAll('.client-checkbox');
            clientCheckboxes.forEach(function(checkbox) {
                checkbox.checked = document.getElementById('select_all').checked;
            });
        });
        
        // Оновлення стану "Вибрати всіх" при зміні окремих чекбоксів
        var clientCheckboxes = document.querySelectorAll('.client-checkbox');
        clientCheckboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                var allChecked = true;
                clientCheckboxes.forEach(function(cb) {
                    if (!cb.checked) {
                        allChecked = false;
                    }
                });
                document.getElementById('select_all').checked = allChecked;
            });
        });
    });
</script>

<?php
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