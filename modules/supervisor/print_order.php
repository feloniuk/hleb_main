<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Перевірка доступу
if (!checkAccess(['brigadir'])) {
    header("Location: ../../index.php");
    exit;
}

// Перевірка наявності ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('ID замовлення не вказано');
}

$orderId = intval($_GET['id']);
$connection = connectDatabase();

// Запит для отримання детальної інформації про замовлення
$query = "SELECT z.*, k.name as client_name, k.fio, k.tel, k.city, k.adres, 
          p.nazvanie as product_name, p.ves, p.zena, p.image
          FROM zayavki z
          JOIN klientu k ON z.idklient = k.id
          JOIN product p ON z.id = p.id
          WHERE z.idd = ?";

$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, "i", $orderId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) !== 1) {
    die('Замовлення не знайдено');
}

$order = mysqli_fetch_assoc($result);

// Розрахунок загальної суми та ваги
$order['total_price'] = $order['kol'] * $order['zena'];
$order['total_weight'] = $order['kol'] * $order['ves'];

// Шлях до зображення
$imagePath = !empty($order['image']) ? '../../' . $order['image'] : '../../assets/img/product-placeholder.jpg';

// Статус у текстовому вигляді
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

// Зміна у текстовому вигляді
$dobaText = $order['doba'] === 'денна' ? 'Денна' : 'Нічна';

// Форматування дати
$formattedDate = formatDate($order['data']);

// Генерація HTML для друку
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Замовлення #<?php echo $orderId; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 14px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo {
            max-width: 200px;
            margin-bottom: 10px;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .subtitle {
            font-size: 16px;
            margin-bottom: 20px;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .row {
            display: flex;
            margin-bottom: 5px;
        }
        .label {
            font-weight: bold;
            width: 150px;
        }
        .value {
            flex-grow: 1;
        }
        .product-image {
            max-width: 150px;
            max-height: 150px;
            margin-right: 20px;
        }
        .product-info {
            display: flex;
            margin-bottom: 20px;
        }
        .total-row {
            font-weight: bold;
            margin-top: 10px;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .signature {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        .signature-line {
            width: 200px;
            border-bottom: 1px solid #000;
            margin-bottom: 5px;
        }
        .print-date {
            margin-top: 50px;
            text-align: right;
            font-style: italic;
        }
        .qr-code {
            text-align: center;
            margin-top: 30px;
        }
        .qr-code img {
            max-width: 100px;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                padding: 0;
                margin: 0;
            }
            .page-break {
                page-break-before: always;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" class="btn btn-primary">Друкувати</button>
        <button onclick="window.close()" class="btn btn-secondary">Закрити</button>
    </div>
    
    <div class="header">
        <img src="../../assets/img/logo.png" alt="ТОВ Одеський Коровай" class="logo">
        <div class="title">ТОВ "Одеський Коровай"</div>
        <div class="subtitle">Накладна замовлення #<?php echo $orderId; ?></div>
    </div>
    
    <div class="section">
        <div class="section-title">Інформація про замовлення</div>
        <div class="row">
            <div class="label">ID замовлення:</div>
            <div class="value"><?php echo $orderId; ?></div>
        </div>
        <div class="row">
            <div class="label">Дата:</div>
            <div class="value"><?php echo $formattedDate; ?></div>
        </div>
        <div class="row">
            <div class="label">Зміна:</div>
            <div class="value"><?php echo $dobaText; ?></div>
        </div>
        <div class="row">
            <div class="label">Статус:</div>
            <div class="value"><?php echo $statusText; ?></div>
        </div>
    </div>
    
    <div class="section">
        <div class="section-title">Інформація про клієнта</div>
        <div class="row">
            <div class="label">Клієнт:</div>
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
    
    <div class="section">
        <div class="section-title">Інформація про продукт</div>
        
        <div class="product-info">
            <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($order['product_name']); ?>" class="product-image">
            <div>
                <div class="row">
                    <div class="label">Продукт:</div>
                    <div class="value"><?php echo htmlspecialchars($order['product_name']); ?></div>
                </div>
                <div class="row">
                    <div class="label">Кількість:</div>
                    <div class="value"><?php echo $order['kol']; ?> шт.</div>
                </div>
                <div class="row">
                    <div class="label">Ціна за одиницю:</div>
                    <div class="value"><?php echo number_format($order['zena'], 2); ?> грн</div>
                </div>
                <div class="row">
                    <div class="label">Вага одиниці:</div>
                    <div class="value"><?php echo number_format($order['ves'], 2); ?> кг</div>
                </div>
                <div class="row total-row">
                    <div class="label">Загальна сума:</div>
                    <div class="value"><?php echo number_format($order['total_price'], 2); ?> грн</div>
                </div>
                <div class="row total-row">
                    <div class="label">Загальна вага:</div>
                    <div class="value"><?php echo number_format($order['total_weight'], 2); ?> кг</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="signature">
        <div>
            <div class="signature-line"></div>
            <div>Підпис бригадира</div>
        </div>
        <div>
            <div class="signature-line"></div>
            <div>Підпис представника клієнта</div>
        </div>
    </div>
    
    <div class="qr-code">
        <!-- Тут можна додати QR-код з інформацією про замовлення, якщо потрібно -->
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=OrderID:<?php echo $orderId; ?>" alt="QR-код">
    </div>
    
    <div class="print-date">
        Документ сформовано: <?php echo date('d.m.Y H:i:s'); ?>
    </div>
    
    <script>
        // Автоматичний друк при завантаженні сторінки (опціонально)
        // window.onload = function() {
        //     window.print();
        // };
    </script>
</body>
</html>