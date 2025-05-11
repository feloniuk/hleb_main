<?php
$pageTitle = 'Друк замовлення';

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Перевірка доступу
if (!checkAccess(['manager'])) {
    header("Location: ../../index.php");
    exit;
}

// Отримання ID замовлення
$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($orderId <= 0) {
    die('Не вказано ID замовлення');
}

$connection = connectDatabase();

// Отримання даних замовлення
$orderQuery = "
    SELECT z.*, k.name as client_name, k.fio, k.tel, k.city, k.adres, 
           p.nazvanie as product_name, p.ves, p.zena
    FROM zayavki z
    JOIN klientu k ON z.idklient = k.id
    JOIN product p ON z.id = p.id
    WHERE z.idd = ?
";
$stmt = mysqli_prepare($connection, $orderQuery);
mysqli_stmt_bind_param($stmt, "i", $orderId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    die('Замовлення не знайдено');
}

$order = mysqli_fetch_assoc($result);

// Розрахунок загальної суми та ваги
$totalPrice = $order['kol'] * $order['zena'];
$totalWeight = $order['kol'] * $order['ves'];

// Визначення статусу замовлення
$statusText = '';
switch ($order['status']) {
    case 'нове':
        $statusText = 'Нове';
        break;
    case 'в обробці':
        $statusText = 'В обробці';
        break;
    case 'виконано':
        $statusText = 'Виконано';
        break;
    case 'скасовано':
        $statusText = 'Скасовано';
        break;
    default:
        $statusText = 'Невідомо';
        break;
}

// CSS для друку
$css = '
<style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 20px;
        font-size: 12pt;
    }
    .invoice-header {
        text-align: center;
        margin-bottom: 30px;
    }
    .invoice-header h1 {
        margin-bottom: 5px;
        font-size: 18pt;
    }
    .invoice-header p {
        margin-top: 0;
        color: #666;
    }
    .invoice-details {
        margin-bottom: 30px;
    }
    .invoice-details .row {
        display: flex;
        margin-bottom: 10px;
    }
    .invoice-details .label {
        font-weight: bold;
        width: 150px;
    }
    .invoice-details .value {
        flex: 1;
    }
    .client-info, .product-info {
        margin-bottom: 20px;
    }
    .client-info h3, .product-info h3 {
        border-bottom: 1px solid #ddd;
        padding-bottom: 5px;
        margin-bottom: 15px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 30px;
    }
    table, th, td {
        border: 1px solid #ddd;
    }
    th, td {
        padding: 10px;
        text-align: left;
    }
    th {
        background-color: #f2f2f2;
    }
    .totals {
        width: 300px;
        margin-left: auto;
        margin-bottom: 30px;
    }
    .totals .row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
    }
    .totals .total-row {
        font-weight: bold;
        border-top: 1px solid #ddd;
        padding-top: 5px;
    }
    .footer {
        margin-top: 30px;
        padding-top: 10px;
        border-top: 1px solid #ddd;
        display: flex;
        justify-content: space-between;
    }
    .no-print {
        text-align: center;
        margin-bottom: 20px;
    }
    @media print {
        .no-print {
            display: none;
        }
    }
</style>
';

?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Замовлення №<?php echo $orderId; ?></title>
    <?php echo $css; ?>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print();" style="padding: 10px 20px; font-size: 14px; cursor: pointer;">
            <i class="fas fa-print"></i> Друкувати замовлення
        </button>
        <button onclick="window.close();" style="padding: 10px 20px; font-size: 14px; cursor: pointer; margin-left: 10px;">
            Закрити
        </button>
    </div>

    <div class="invoice-header">
        <h1>ТОВ "Одеський Коровай"</h1>
        <p>м. Одеса, вул. Пекарська, 123</p>
        <p>Тел: +38 (048) 123-45-67, Email: info@ok.com</p>
        <h2>Замовлення №<?php echo $orderId; ?></h2>
    </div>

    <div class="invoice-details">
        <div class="row">
            <div class="label">Дата замовлення:</div>
            <div class="value"><?php echo formatDate($order['data']); ?></div>
        </div>
        <div class="row">
            <div class="label">Зміна:</div>
            <div class="value"><?php echo ($order['doba'] == 'денна') ? 'Денна' : 'Нічна'; ?></div>
        </div>
        <div class="row">
            <div class="label">Статус:</div>
            <div class="value"><?php echo $statusText; ?></div>
        </div>
    </div>

    <div class="client-info">
        <h3>Інформація про клієнта</h3>
        <div class="row">
            <div class="label">Назва компанії:</div>
            <div class="value"><?php echo htmlspecialchars($order['client_name']); ?></div>
        </div>
        <div class="row">
            <div class="label">Контактна особа:</div>
            <div class="value"><?php echo htmlspecialchars($order['fio']); ?></div>
        </div>
        <div class="row">
            <div class="label">Телефон:</div>
            <div class="value"><?php echo htmlspecialchars($order['tel']); ?></div>
        </div>
        <div class="row">
            <div class="label">Адреса доставки:</div>
            <div class="value"><?php echo htmlspecialchars($order['city'] . ', ' . $order['adres']); ?></div>
        </div>
    </div>

    <div class="product-info">
        <h3>Деталі замовлення</h3>
        <table>
            <thead>
                <tr>
                    <th>№</th>
                    <th>Назва продукту</th>
                    <th>Кількість</th>
                    <th>Вага одиниці</th>
                    <th>Загальна вага</th>
                    <th>Ціна за одиницю</th>
                    <th>Сума</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                    <td><?php echo $order['kol']; ?> шт.</td>
                    <td><?php echo $order['ves']; ?> кг</td>
                    <td><?php echo number_format($totalWeight, 2); ?> кг</td>
                    <td><?php echo number_format($order['zena'], 2); ?> грн</td>
                    <td><?php echo number_format($totalPrice, 2); ?> грн</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="totals">
        <div class="row">
            <div>Загальна кількість:</div>
            <div><?php echo $order['kol']; ?> шт.</div>
        </div>
        <div class="row">
            <div>Загальна вага:</div>
            <div><?php echo number_format($totalWeight, 2); ?> кг</div>
        </div>
        <div class="row total-row">
            <div>Загальна сума:</div>
            <div><?php echo number_format($totalPrice, 2); ?> грн</div>
        </div>
    </div>

    <div class="footer">
        <div>
            <p>Замовлення сформовано: <?php echo date('d.m.Y H:i:s'); ?></p>
        </div>
        <div>
            <p>________________</p>
            <p>Підпис</p>
        </div>
    </div>
</body>
</html>