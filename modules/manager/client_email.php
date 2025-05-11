<?php
$pageTitle = 'Розсилка клієнтам';

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

// Отримання списку клієнтів з email
$clientsQuery = "SELECT id, name, mail FROM klientu WHERE mail != '' AND mail IS NOT NULL ORDER BY name";
$clientsResult = mysqli_query($connection, $clientsQuery);
$clients = [];
while ($client = mysqli_fetch_assoc($clientsResult)) {
    $clients[] = $client;
}

// Отримання списку продуктів для шаблонів розсилок
$productsQuery = "SELECT id, nazvanie FROM product ORDER BY nazvanie";
$productsResult = mysqli_query($connection, $productsQuery);
$products = [];
while ($product = mysqli_fetch_assoc($productsResult)) {
    $products[] = $product;
}

// Обробка форми
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $selectedClients = $_POST['client_ids'] ?? [];
    
    // Валідація даних
    if (empty($subject)) {
        $error = 'Будь ласка, введіть тему повідомлення';
    } elseif (empty($message)) {
        $error = 'Будь ласка, введіть текст повідомлення';
    } elseif (empty($selectedClients)) {
        $error = 'Будь ласка, виберіть хоча б одного клієнта';
    } else {
        // Відправка листів
        $successCount = 0;
        $errorCount = 0;
        
        // Підготовка заголовків листа
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: ТОВ Одеський Коровай <info@ok.com>\r\n";
        $headers .= "Reply-To: info@ok.com\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        // Підготовка HTML-шаблону
        $htmlMessage = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>' . htmlspecialchars($subject) . '</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #f8f9fa; padding: 15px; text-align: center; margin-bottom: 20px; }
                .content { padding: 15px; }
                .footer { background-color: #f8f9fa; padding: 15px; font-size: 12px; text-align: center; margin-top: 20px; }
                .btn { display: inline-block; padding: 8px 16px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>ТОВ "Одеський Коровай"</h2>
                </div>
                <div class="content">
                    ' . nl2br(htmlspecialchars($message)) . '
                </div>
                <div class="footer">
                    <p>© ' . date('Y') . ' ТОВ "Одеський Коровай". Всі права захищені.</p>
                    <p>Адреса: м. Одеса, вул. Пекарська, 123 | Телефон: +38 (048) 123-45-67</p>
                </div>
            </div>
        </body>
        </html>
        ';
        
        // Відправка листів клієнтам
        foreach ($selectedClients as $clientId) {
            // Отримання даних клієнта
            $clientQuery = "SELECT name, mail FROM klientu WHERE id = ?";
            $stmt = mysqli_prepare($connection, $clientQuery);
            mysqli_stmt_bind_param($stmt, "i", $clientId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $client = mysqli_fetch_assoc($result);
            
            if ($client && !empty($client['mail'])) {
                // Персоналізація повідомлення
                $personalMessage = str_replace('{COMPANY_NAME}', $client['name'], $htmlMessage);
                
                // Відправка листа (для демонстрації)
                // В реальному проекті використовуйте PHP Mailer або інші бібліотеки
                $sent = mail($client['mail'], $subject, $personalMessage, $headers);
                
                if ($sent) {
                    $successCount++;
                    
                    // Запис в журнал (в реальному проекті)
                    $logQuery = "INSERT INTO system_log (action, user_id, details, ip_address, level) 
                                 VALUES (?, ?, ?, ?, ?)";
                    $action = 'Відправлено email';
                    $userId = $_SESSION['id'];
                    $details = "Відправлено email клієнту {$client['name']} ({$client['mail']})";
                    $ipAddress = $_SERVER['REMOTE_ADDR'];
                    $level = 'info';
                    
                    // Підготовка запиту не працює в даному контексті (для демонстрації)
                    // $logStmt = mysqli_prepare($connection, $logQuery);
                    // mysqli_stmt_bind_param($logStmt, "sisss", $action, $userId, $details, $ipAddress, $level);
                    // mysqli_stmt_execute($logStmt);
                } else {
                    $errorCount++;
                }
            } else {
                $errorCount++;
            }
        }
        
        if ($successCount > 0) {
            $success = "Успішно відправлено листів: $successCount";
            if ($errorCount > 0) {
                $success .= ", не вдалося відправити: $errorCount";
            }
        } else {
            $error = "Не вдалося відправити жодного листа. Перевірте налаштування поштового сервера.";
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
            <a class="nav-link" href="orders.php">
                <i class="fas fa-clipboard-list"></i> Замовлення
            </a>
            <a class="nav-link active" href="clients.php">
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
        <form action="" method="POST">
            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="subject" class="form-label">Тема повідомлення *</label>
                    <input type="text" class="form-control" id="subject" name="subject" required>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="message" class="form-label">Текст повідомлення *</label>
                    <textarea class="form-control" id="message" name="message" rows="6" required></textarea>
                    <div class="form-text">
                        Ви можете використовувати {COMPANY_NAME} для підстановки назви компанії клієнта.
                    </div>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Шаблони повідомлень</label>
                    <div class="list-group">
                        <button type="button" class="list-group-item list-group-item-action" onclick="loadTemplate('greeting')">
                            Вітальне повідомлення
                        </button>
                        <button type="button" class="list-group-item list-group-item-action" onclick="loadTemplate('promotion')">
                            Акційна пропозиція
                        </button>
                        <button type="button" class="list-group-item list-group-item-action" onclick="loadTemplate('newProduct')">
                            Новинка продукції
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Вибір клієнтів для розсилки *</label>
                    <div class="card">
                        <div class="card-body">
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="searchClient" placeholder="Пошук клієнтів...">
                                        <button class="btn btn-outline-secondary" type="button" onclick="clearClientSearch()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6 text-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllClients()">
                                        Вибрати всіх
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAllClients()">
                                        Зняти вибір
                                    </button>
                                </div>
                            </div>
                            
                            <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                <table class="table table-hover table-striped">
                                    <thead>
                                        <tr>
                                            <th style="width: 40px;"></th>
                                            <th>Компанія</th>
                                            <th>Email</th>
                                        </tr>
                                    </thead>
                                    <tbody id="clientsTableBody">
                                        <?php if (count($clients) > 0): ?>
                                            <?php foreach ($clients as $client): ?>
                                                <tr class="client-row">
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input client-checkbox" type="checkbox" name="client_ids[]" value="<?php echo $client['id']; ?>" id="client<?php echo $client['id']; ?>">
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <label class="form-check-label" for="client<?php echo $client['id']; ?>">
                                                            <?php echo htmlspecialchars($client['name']); ?>
                                                        </label>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($client['mail']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3" class="text-center">Клієнтів з email не знайдено</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="clients.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Повернутися
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane me-1"></i> Відправити повідомлення
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Пошук клієнтів
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchClient');
        const clientRows = document.querySelectorAll('.client-row');
        
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            clientRows.forEach(row => {
                const clientName = row.querySelector('label').textContent.toLowerCase();
                const clientEmail = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                
                if (clientName.includes(searchTerm) || clientEmail.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
    
    // Очищення пошуку
    function clearClientSearch() {
        document.getElementById('searchClient').value = '';
        document.querySelectorAll('.client-row').forEach(row => {
            row.style.display = '';
        });
    }
    
    // Вибір всіх клієнтів
    function selectAllClients() {
        document.querySelectorAll('.client-checkbox').forEach(checkbox => {
            checkbox.checked = true;
        });
    }
    
    // Зняття вибору з усіх клієнтів
    function deselectAllClients() {
        document.querySelectorAll('.client-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
    }
    
    // Завантаження шаблонів
    function loadTemplate(template) {
        const subjectInput = document.getElementById('subject');
        const messageInput = document.getElementById('message');
        
        switch (template) {
            case 'greeting':
                subjectInput.value = "Вітаємо з початком співпраці!";
                messageInput.value = "Шановні партнери {COMPANY_NAME}!\n\nРаді вітати вас у числі наших клієнтів. Ми цінуємо ваш вибір та готові забезпечити найвищу якість продукції та обслуговування.\n\nЗ повагою,\nКоманда ТОВ \"Одеський Коровай\"";
                break;
                
            case 'promotion':
                subjectInput.value = "Спеціальна пропозиція для вас!";
                messageInput.value = "Шановні партнери {COMPANY_NAME}!\n\nРаді повідомити про спеціальну пропозицію для наших постійних клієнтів. З 15 по 25 травня при замовленні від 100 одиниць продукції ви отримуєте знижку 15%.\n\nСкористайтеся цією можливістю вже зараз!\n\nЗ повагою,\nКоманда ТОВ \"Одеський Коровай\"";
                break;
                
            case 'newProduct':
                subjectInput.value = "Новинка в асортименті!";
                messageInput.value = "Шановні партнери {COMPANY_NAME}!\n\nРаді представити нову продукцію в нашому асортименті - Хліб \"Зерновий Преміум\".\n\nОсобливості продукту:\n- Виготовлений з цільнозернового борошна\n- Містить суміш 5 видів насіння\n- Тривалий термін зберігання (72 години)\n\nЗамовляйте зразки вже зараз!\n\nЗ повагою,\nКоманда ТОВ \"Одеський Коровай\"";
                break;
        }
    }
</script>

<?php
include_once '../../includes/footer.php';
?>