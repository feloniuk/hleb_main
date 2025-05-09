<?php
$pageTitle = 'Друк звіту';

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Перевірка доступу
if (!checkAccess(['manager'])) {
    header("Location: ../../index.php");
    exit;
}

$connection = connectDatabase();

// Отримання параметрів для звіту
$reportType = isset($_GET['type']) ? $_GET['type'] : '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$clientId = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;
$productId = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

// CSS для сторінки друку
$css = '
<style>
    body {
        font-family: "Arial", sans-serif;
        font-size: 11pt;
        line-height: 1.5;
        margin: 0;
        padding: 15px;
    }
    .report-header {
        text-align: center;
        margin-bottom: 20px;
    }
    .report-header h1 {
        font-size: 18pt;
        margin-bottom: 5px;
    }
    .report-header p {
        font-size: 12pt;
        color: #555;
    }
    .report-info {
        margin: 20px 0;
        padding: 10px;
        background-color: #f9f9f9;
        border: 1px solid #ddd;
    }
    .info-item {
        margin-bottom: 5px;
    }
    .info-label {
        font-weight: bold;
        display: inline-block;
        width: 200px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
    }
    table, th, td {
        border: 1px solid #ddd;
    }
    th, td {
        padding: 8px;
        text-align: left;
    }
    th {
        background-color: #f2f2f2;
    }
    .section-header {
        background-color: #eee;
        padding: 8px;
        margin-top: 25px;
        font-weight: bold;
    }
    .totals {
        margin-top: 20px;
        text-align: right;
    }
    .print-footer {
        text-align: center;
        margin-top: 30px;
        font-size: 10pt;
        color: #777;
    }
    @media print {
        .no-print {
            display: none;
        }
    }
</style>';

// HTML заголовок
echo '<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Друк звіту - ' . htmlspecialchars($reportType) . '</title>
    ' . $css . '
</head>
<body>';

// Додавання кнопки друку
echo '<div class="no-print" style="text-align: center; margin-bottom: 20px;">
    <button onclick="window.print();" style="padding: 10px 20px; font-size: 14px; cursor: pointer;">
        <i class="fas fa-print"></i> Друкувати звіт
    </button>
    <button onclick="window.close();" style="padding: 10px 20px; font-size: 14px; cursor: pointer; margin-left: 10px;">
        Закрити
    </button>
</div>';

// Вибір типу звіту та генерація контенту
switch ($reportType) {
    case 'general':
        generateGeneralReport($connection, $startDate, $endDate);
        break;
    case 'client':
        generateClientReport($connection, $clientId, $startDate, $endDate);
        break;
    case 'product':
        generateProductReport($connection, $productId, $startDate, $endDate);
        break;
    default:
        echo '<div class="report-header">
            <h1>Помилка: Тип звіту не вказаний</h1>
        </div>';
        break;
}

// Додавання підвалу звіту
echo '<div class="print-footer">
    <p>ТОВ "Одеський Коровай" | Звіт сформовано: ' . date('d.m.Y H:i:s') . '</p>
</div>';

echo '</body>
</html>';

/**
 * Генерація загального звіту
 * 
 * @param mysqli $connection Підключення до БД
 * @param string $startDate Початкова дата
 * @param string $endDate Кінцева дата
 */
function generateGeneralReport($connection, $startDate, $endDate) {
    // Заголовок звіту
    echo '<div class="report-header">
        <h1>Загальний звіт</h1>
        <p>Період: ' . formatDate($startDate) . ' - ' . formatDate($endDate) . '</p>
    </div>';
    
    // Запит на отримання загальної статистики
    $generalStatsQuery = "
        SELECT 
            COUNT(DISTINCT z.idd) as total_orders,
            SUM(z.kol) as total_quantity,
            SUM(z.kol * p.zena) as total_sales,
            COUNT(DISTINCT z.idklient) as total_clients,
            COUNT(DISTINCT z.id) as total_products
        FROM zayavki z
        JOIN product p ON z.id = p.id
        WHERE z.data BETWEEN ? AND ?
    ";
    
    $stmt = mysqli_prepare($connection, $generalStatsQuery);
    mysqli_stmt_bind_param($stmt, "ss", $startDate, $endDate);
    mysqli_stmt_execute($stmt);
    $generalStatsResult = mysqli_stmt_get_result($stmt);
    $generalStats = mysqli_fetch_assoc($generalStatsResult);
    
    // Загальна статистика
    echo '<div class="report-info">
        <div class="info-item"><span class="info-label">Загальна кількість замовлень:</span> ' . $generalStats['total_orders'] . '</div>
        <div class="info-item"><span class="info-label">Загальна кількість одиниць:</span> ' . $generalStats['total_quantity'] . '</div>
        <div class="info-item"><span class="info-label">Загальна сума продажів:</span> ' . number_format($generalStats['total_sales'], 2) . ' грн</div>
        <div class="info-item"><span class="info-label">Кількість клієнтів:</span> ' . $generalStats['total_clients'] . '</div>
        <div class="info-item"><span class="info-label">Кількість унікальних продуктів:</span> ' . $generalStats['total_products'] . '</div>
    </div>';
    
    // Топ-5 продуктів за кількістю замовлень
    echo '<div class="section-header">Топ-5 продуктів за кількістю замовлень</div>';
    echo '<table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Назва продукту</th>
                <th>Кількість</th>
                <th>Сума продажів (грн)</th>
            </tr>
        </thead>
        <tbody>';
    
    $topProductsQuery = "
        SELECT p.id, p.nazvanie, SUM(z.kol) as total_quantity, SUM(z.kol * p.zena) as total_sales
        FROM zayavki z
        JOIN product p ON z.id = p.id
        WHERE z.data BETWEEN ? AND ?
        GROUP BY p.id
        ORDER BY total_quantity DESC
        LIMIT 5
    ";
    
    $stmt = mysqli_prepare($connection, $topProductsQuery);
    mysqli_stmt_bind_param($stmt, "ss", $startDate, $endDate);
    mysqli_stmt_execute($stmt);
    $topProductsResult = mysqli_stmt_get_result($stmt);
    
    while ($product = mysqli_fetch_assoc($topProductsResult)) {
        echo '<tr>
            <td>' . $product['id'] . '</td>
            <td>' . htmlspecialchars($product['nazvanie']) . '</td>
            <td>' . $product['total_quantity'] . '</td>
            <td>' . number_format($product['total_sales'], 2) . ' грн</td>
        </tr>';
    }
    
    echo '</tbody>
    </table>';
    
    // Топ-5 клієнтів за сумою замовлень
    echo '<div class="section-header">Топ-5 клієнтів за сумою замовлень</div>';
    echo '<table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Назва клієнта</th>
                <th>Кількість замовлень</th>
                <th>Загальна кількість</th>
                <th>Сума продажів (грн)</th>
            </tr>
        </thead>
        <tbody>';
    
    $topClientsQuery = "
        SELECT k.id, k.name, COUNT(DISTINCT z.idd) as order_count, SUM(z.kol) as total_quantity, SUM(z.kol * p.zena) as total_sales
        FROM zayavki z
        JOIN klientu k ON z.idklient = k.id
        JOIN product p ON z.id = p.id
        WHERE z.data BETWEEN ? AND ?
        GROUP BY k.id
        ORDER BY total_sales DESC
        LIMIT 5
    ";
    
    $stmt = mysqli_prepare($connection, $topClientsQuery);
    mysqli_stmt_bind_param($stmt, "ss", $startDate, $endDate);
    mysqli_stmt_execute($stmt);
    $topClientsResult = mysqli_stmt_get_result($stmt);
    
    while ($client = mysqli_fetch_assoc($topClientsResult)) {
        echo '<tr>
            <td>' . $client['id'] . '</td>
            <td>' . htmlspecialchars($client['name']) . '</td>
            <td>' . $client['order_count'] . '</td>
            <td>' . $client['total_quantity'] . '</td>
            <td>' . number_format($client['total_sales'], 2) . ' грн</td>
        </tr>';
    }
    
    echo '</tbody>
    </table>';
}

/**
 * Генерація звіту по клієнту
 * 
 * @param mysqli $connection Підключення до БД
 * @param int $clientId ID клієнта
 * @param string $startDate Початкова дата
 * @param string $endDate Кінцева дата
 */
function generateClientReport($connection, $clientId, $startDate, $endDate) {
    // Отримання інформації про клієнта
    $clientQuery = "SELECT * FROM klientu WHERE id = ?";
    $stmt = mysqli_prepare($connection, $clientQuery);
    mysqli_stmt_bind_param($stmt, "i", $clientId);
    mysqli_stmt_execute($stmt);
    $clientResult = mysqli_stmt_get_result($stmt);
    $client = mysqli_fetch_assoc($clientResult);
    
    if (!$client) {
        echo '<div class="report-header">
            <h1>Помилка: Клієнт не знайдений</h1>
        </div>';
        return;
    }
    
    // Заголовок звіту
    echo '<div class="report-header">
        <h1>Звіт по клієнту: ' . htmlspecialchars($client['name']) . '</h1>
        <p>Період: ' . formatDate($startDate) . ' - ' . formatDate($endDate) . '</p>
    </div>';
    
    // Інформація про клієнта
    echo '<div class="report-info">
        <div class="info-item"><span class="info-label">Компанія:</span> ' . htmlspecialchars($client['name']) . '</div>
        <div class="info-item"><span class="info-label">Контактна особа:</span> ' . htmlspecialchars($client['fio']) . '</div>
        <div class="info-item"><span class="info-label">Посада:</span> ' . htmlspecialchars($client['dolj']) . '</div>
        <div class="info-item"><span class="info-label">Телефон:</span> ' . htmlspecialchars($client['tel']) . '</div>
        <div class="info-item"><span class="info-label">Email:</span> ' . htmlspecialchars($client['mail']) . '</div>
        <div class="info-item"><span class="info-label">Місто:</span> ' . htmlspecialchars($client['city']) . '</div>
        <div class="info-item"><span class="info-label">Адреса:</span> ' . htmlspecialchars($client['adres']) . '</div>
        <div class="info-item"><span class="info-label">Відстань:</span> ' . $client['rast'] . ' км</div>
    </div>';
    
    // Отримання статистики по клієнту
    $clientStatsQuery = "
        SELECT 
            COUNT(DISTINCT z.idd) as total_orders,
            SUM(z.kol) as total_quantity,
            SUM(z.kol * p.zena) as total_sales
        FROM zayavki z
        JOIN product p ON z.id = p.id
        WHERE z.idklient = ? AND z.data BETWEEN ? AND ?
    ";
    
    $stmt = mysqli_prepare($connection, $clientStatsQuery);
    mysqli_stmt_bind_param($stmt, "iss", $clientId, $startDate, $endDate);
    mysqli_stmt_execute($stmt);
    $clientStatsResult = mysqli_stmt_get_result($stmt);
    $clientStats = mysqli_fetch_assoc($clientStatsResult);
    
    // Статистика замовлень
    echo '<div class="section-header">Статистика замовлень</div>';
    echo '<div class="report-info">
        <div class="info-item"><span class="info-label">Кількість замовлень:</span> ' . $clientStats['total_orders'] . '</div>
        <div class="info-item"><span class="info-label">Загальна кількість продукції:</span> ' . $clientStats['total_quantity'] . '</div>
        <div class="info-item"><span class="info-label">Загальна сума:</span> ' . number_format($clientStats['total_sales'], 2) . ' грн</div>
    </div>';
    
    // Список замовлень клієнта
    echo '<div class="section-header">Список замовлень за обраний період</div>';
    echo '<table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Продукт</th>
                <th>Кількість</th>
                <th>Сума (грн)</th>
                <th>Дата</th>
                <th>Зміна</th>
                <th>Статус</th>
            </tr>
        </thead>
        <tbody>';
    
    $clientOrdersQuery = "
        SELECT z.idd, z.id, p.nazvanie, z.kol, z.data, z.doba, z.status, (z.kol * p.zena) as total_price
        FROM zayavki z
        JOIN product p ON z.id = p.id
        WHERE z.idklient = ? AND z.data BETWEEN ? AND ?
        ORDER BY z.data DESC, z.idd DESC
    ";
    
    $stmt = mysqli_prepare($connection, $clientOrdersQuery);
    mysqli_stmt_bind_param($stmt, "iss", $clientId, $startDate, $endDate);
    mysqli_stmt_execute($stmt);
    $clientOrdersResult = mysqli_stmt_get_result($stmt);
    
    $totalAmount = 0;
    while ($order = mysqli_fetch_assoc($clientOrdersResult)) {
        $totalAmount += $order['total_price'];
        echo '<tr>
            <td>' . $order['idd'] . '</td>
            <td>' . htmlspecialchars($order['nazvanie']) . '</td>
            <td>' . $order['kol'] . '</td>
            <td>' . number_format($order['total_price'], 2) . ' грн</td>
            <td>' . formatDate($order['data']) . '</td>
            <td>' . $order['doba'] . '</td>
            <td>' . $order['status'] . '</td>
        </tr>';
    }
    
    echo '</tbody>
    </table>';
    
    // Підсумкова інформація
    echo '<div class="totals">
        <h3>Загальна сума: ' . number_format($totalAmount, 2) . ' грн</h3>
    </div>';
}

/**
 * Генерація звіту по продукту
 * 
 * @param mysqli $connection Підключення до БД
 * @param int $productId ID продукту
 * @param string $startDate Початкова дата
 * @param string $endDate Кінцева дата
 */
function generateProductReport($connection, $productId, $startDate, $endDate) {
    // Отримання інформації про продукт
    $productQuery = "SELECT * FROM product WHERE id = ?";
    $stmt = mysqli_prepare($connection, $productQuery);
    mysqli_stmt_bind_param($stmt, "i", $productId);
    mysqli_stmt_execute($stmt);
    $productResult = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($productResult);
    
    if (!$product) {
        echo '<div class="report-header">
            <h1>Помилка: Продукт не знайдений</h1>
        </div>';
        return;
    }
    
    // Заголовок звіту
    echo '<div class="report-header">
        <h1>Звіт по продукту: ' . htmlspecialchars($product['nazvanie']) . '</h1>
        <p>Період: ' . formatDate($startDate) . ' - ' . formatDate($endDate) . '</p>
    </div>';
    
    // Інформація про продукт
    echo '<div class="report-info">
        <div class="info-item"><span class="info-label">Назва:</span> ' . htmlspecialchars($product['nazvanie']) . '</div>
        <div class="info-item"><span class="info-label">Вага:</span> ' . $product['ves'] . ' кг</div>
        <div class="info-item"><span class="info-label">Строк реалізації:</span> ' . $product['srok'] . ' годин</div>
        <div class="info-item"><span class="info-label">Собівартість:</span> ' . $product['stoimost'] . ' грн</div>
        <div class="info-item"><span class="info-label">Ціна:</span> ' . $product['zena'] . ' грн</div>
        <div class="info-item"><span class="info-label">Прибуток:</span> ' . number_format($product['zena'] - $product['stoimost'], 2) . ' грн</div>
    </div>';
    
    // Отримання статистики по продукту
    $productStatsQuery = "
        SELECT 
            COUNT(DISTINCT z.idd) as total_orders,
            SUM(z.kol) as total_quantity,
            SUM(z.kol * p.zena) as total_sales,
            COUNT(DISTINCT z.idklient) as total_clients
        FROM zayavki z
        JOIN product p ON z.id = p.id
        WHERE z.id = ? AND z.data BETWEEN ? AND ?
    ";
    
    $stmt = mysqli_prepare($connection, $productStatsQuery);
    mysqli_stmt_bind_param($stmt, "iss", $productId, $startDate, $endDate);
    mysqli_stmt_execute($stmt);
    $productStatsResult = mysqli_stmt_get_result($stmt);
    $productStats = mysqli_fetch_assoc($productStatsResult);
    
    // Статистика продажів
    echo '<div class="section-header">Статистика продажів</div>';
    echo '<div class="report-info">
        <div class="info-item"><span class="info-label">Кількість замовлень:</span> ' . $productStats['total_orders'] . '</div>
        <div class="info-item"><span class="info-label">Загальна кількість одиниць:</span> ' . $productStats['total_quantity'] . '</div>
        <div class="info-item"><span class="info-label">Загальна сума продажів:</span> ' . number_format($productStats['total_sales'], 2) . ' грн</div>
        <div class="info-item"><span class="info-label">Кількість клієнтів:</span> ' . $productStats['total_clients'] . '</div>
    </div>';
    
    // Топ клієнтів по продукту
    echo '<div class="section-header">Топ клієнтів по замовленнях продукту</div>';
    echo '<table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Клієнт</th>
                <th>Кількість замовлень</th>
                <th>Загальна кількість</th>
                <th>Сума продажів (грн)</th>
            </tr>
        </thead>
        <tbody>';
    
    $topClientsQuery = "
        SELECT k.id, k.name, SUM(z.kol) as total_quantity, COUNT(DISTINCT z.idd) as order_count
        FROM zayavki z
        JOIN klientu k ON z.idklient = k.id
        WHERE z.id = ? AND z.data BETWEEN ? AND ?
        GROUP BY k.id
        ORDER BY total_quantity DESC
        LIMIT 5
    ";
    
    $stmt = mysqli_prepare($connection, $topClientsQuery);
    mysqli_stmt_bind_param($stmt, "iss", $productId, $startDate, $endDate);
    mysqli_stmt_execute($stmt);
    $topClientsResult = mysqli_stmt_get_result($stmt);
    
    while ($client = mysqli_fetch_assoc($topClientsResult)) {
        echo '<tr>
            <td>' . $client['id'] . '</td>
            <td>' . htmlspecialchars($client['name']) . '</td>
            <td>' . $client['order_count'] . '</td>
            <td>' . $client['total_quantity'] . '</td>
            <td>' . number_format($client['total_quantity'] * $product['zena'], 2) . ' грн</td>
        </tr>';
    }
    
    echo '</tbody>
    </table>';
    
    // Список замовлень продукту
    echo '<div class="section-header">Список замовлень за обраний період</div>';
    echo '<table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Клієнт</th>
                <th>Кількість</th>
                <th>Сума (грн)</th>
                <th>Дата</th>
                <th>Зміна</th>
                <th>Статус</th>
            </tr>
        </thead>
        <tbody>';
    
    $productOrdersQuery = "
        SELECT z.idd, z.idklient, k.name as client_name, z.kol, z.data, z.doba, z.status, (z.kol * p.zena) as total_price
        FROM zayavki z
        JOIN klientu k ON z.idklient = k.id
        JOIN product p ON z.id = p.id
        WHERE z.id = ? AND z.data BETWEEN ? AND ?
        ORDER BY z.data DESC, z.idd DESC
    ";
    
    $stmt = mysqli_prepare($connection, $productOrdersQuery);
    mysqli_stmt_bind_param($stmt, "iss", $productId, $startDate, $endDate);
    mysqli_stmt_execute($stmt);
    $productOrdersResult = mysqli_stmt_get_result($stmt);
    
    $totalAmount = 0;
    while ($order = mysqli_fetch_assoc($productOrdersResult)) {
        $totalAmount += $order['total_price'];
        echo '<tr>
            <td>' . $order['idd'] . '</td>
            <td>' . htmlspecialchars($order['client_name']) . '</td>
            <td>' . $order['kol'] . '</td>
            <td>' . number_format($order['total_price'], 2) . ' грн</td>
            <td>' . formatDate($order['data']) . '</td>
            <td>' . $order['doba'] . '</td>
            <td>' . $order['status'] . '</td>
        </tr>';
    }
    
    echo '</tbody>
    </table>';
    
    // Підсумкова інформація
    echo '<div class="totals">
        <h3>Загальна сума: ' . number_format($totalAmount, 2) . ' грн</h3>
    </div>';
}
?>