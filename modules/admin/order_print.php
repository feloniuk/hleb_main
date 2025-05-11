<?php 
$pageTitle = 'Друк замовлення';

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Перевірка доступу
if (!checkAccess(['admin'])) {
    header("Location: ../../index.php");
    exit;
}

$connection = connectDatabase();

// Перевірка наявності ID замовлення
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: orders.php");
    exit;
}

$orderId = intval($_GET['id']);

// Отримання даних замовлення
$query = "SELECT z.*, k.name as client_name, k.fio, k.tel, k.city, k.adres, 
           p.nazvanie as product_name, p.ves, p.zena
          FROM zayavki z
          JOIN klientu k ON z.idklient = k.id
          JOIN product p ON z.id = p.id
          WHERE z.idd = ?";
$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, "i", $orderId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) != 1) {
    header("Location: orders.php");
    exit;
}

$order = mysqli_fetch_assoc($result);

// Розрахунок деяких даних
$totalPrice = $order['kol'] * $order['zena'];
$totalWeight = $order['kol'] * $order['ves'];

// Отримання налаштувань компанії
$companySettings = [];
$settingsQuery = "SELECT * FROM system_settings WHERE setting_key IN ('company_name', 'company_address', 'company_phone', 'company_email')";
$settingsResult = mysqli_query($connection, $settingsQuery);

while ($row = mysqli_fetch_assoc($settingsResult)) {
    $companySettings[$row['setting_key']] = $row['setting_value'];
}

// Логування події друку
logAction($connection, 'Друк замовлення', 'Надруковано замовлення ID: ' . $orderId);

// Спеціальний заголовок для сторінки друку
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Друк замовлення #<?php echo $orderId; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        
        .print-header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .print-logo {
            max-width: 200px;
            margin-bottom: 10px;
        }
        
        .order-info {
            margin-bottom: 20px;
        }
        
        .order-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .order-table th, .order-table td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        
        .order-table th {
            background-color: #f2f2f2;
        }
        
        .signature-area {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        
        .signature-line {
            width: 200px;
            border-top: 1px solid #000;
            margin-top: 30px;
            text-align: center;
        }
        
        @media print {
            .no-print {
                display: none;
            }
            
            body {
                margin: 0;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="no-print mb-3">
        <button onclick="window.print();" class="btn btn-primary">
            <i class="fas fa-print me-1"></i> Друкувати
        </button>
        <a href="order_details.php?id=<?php echo $orderId; ?>" class="btn btn-secondary ms-2">
            <i class="fas fa-arrow-left me-1"></i> Повернутися
        </a>
    </div>
    
    <div class="print-header">
        <img src="../../assets/img/logo.png" alt="ТОВ Одеський Коровай" class="print-logo">
        <h4><?php echo htmlspecialchars($companySettings['company_name'] ?? 'ТОВ "Одеський Коровай"'); ?></h4>
        <p><?php echo htmlspecialchars($companySettings['company_address'] ?? 'м. Одеса, вул. Пекарська, 10'); ?></p>
        <p>
            Тел: <?php echo htmlspecialchars($companySettings['company_phone'] ?? '+38 (048) 123-45-67'); ?> | 
            Email: <?php echo htmlspecialchars($companySettings['company_email'] ?? 'info@odesskiy-korovay.com'); ?>
        </p>
    </div>
    
    <h3 class="text-center mb-4">ЗАМОВЛЕННЯ #<?php echo $orderId; ?> від <?php echo formatDate($order['data']); ?></h3>
    
    <div class="row order-info">
        <div class="col-md-6">
            <h5>Інформація про клієнта</h5>
            <p><strong>Назва:</strong> <?php echo htmlspecialchars($order['client_name']); ?></p>
            <p><strong>Контактна особа:</strong> <?php echo htmlspecialchars($order['fio']); ?></p>
            <p><strong>Телефон:</strong> <?php echo htmlspecialchars($order['tel']); ?></p>
            <p><strong>Адреса:</strong> <?php echo htmlspecialchars($order['city'] . ', ' . $order['adres']); ?></p>
        </div>
        
        <div class="col-md-6">
            <h5>Інформація про замовлення</h5>
            <p><strong>Номер замовлення:</strong> <?php echo $orderId; ?></p>
            <p><strong>Дата замовлення:</strong> <?php echo formatDate($order['data']); ?></p>
            <p><strong>Зміна:</strong> <?php echo $order['doba']; ?></p>
            <p><strong>Статус:</strong> <?php echo $order['status']; ?></p>
        </div>
    </div>
    
    <h5>Деталі замовлення</h5>
    <table class="order-table">
        <thead>
            <tr>
                <th>№</th>
                <th>Продукт</th>
                <th>Кількість</th>
                <th>Вага од. (кг)</th>
                <th>Загальна вага (кг)</th>
                <th>Ціна од. (грн)</th>
                <th>Загальна сума (грн)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                <td><?php echo $order['kol']; ?></td>
                <td><?php echo $order['ves']; ?></td>
                <td><?php echo number_format($totalWeight, 2); ?></td>
                <td><?php echo number_format($order['zena'], 2); ?></td>
                <td><?php echo number_format($totalPrice, 2); ?></td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4">Всього:</th>
                <th><?php echo number_format($totalWeight, 2); ?> кг</th>
                <th></th>
                <th><?php echo number_format($totalPrice, 2); ?> грн</th>
            </tr>
        </tfoot>
    </table>
    
    <div class="signature-area">
        <div>
            <div class="signature-line">Менеджер</div>
        </div>
        
        <div>
            <div class="signature-line">Клієнт</div>
        </div>
    </div>
    
    <div class="text-center mt-5">
        <p>Дата оформлення: <?php echo date('d.m.Y H:i'); ?></p>
    </div>
    
    <script>
        // Автоматичний друк при завантаженні сторінки
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>

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