<?php
$pageTitle = 'Експорт звіту';

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Перевірка доступу
if (!checkAccess(['manager'])) {
    header("Location: ../../index.php");
    exit;
}

$connection = connectDatabase();

// Отримання параметрів для експорту
$reportType = isset($_GET['type']) ? $_GET['type'] : '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$clientId = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;
$productId = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

// Підключення бібліотеки PHPExcel (або альтернативи) для генерації Excel файлів
// Для демонстрації будемо виводити CSV

// Встановлення заголовків для завантаження файлу
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $reportType . '_report_' . date('Y-m-d') . '.csv"');

// Створення файлового потоку на вивід
$output = fopen('php://output', 'w');

// Додавання BOM для правильного відображення кирилиці в Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Вибір типу звіту та генерація даних
switch ($reportType) {
    case 'general':
        generateGeneralReport($connection, $output, $startDate, $endDate);
        break;
    case 'client':
        generateClientReport($connection, $output, $clientId, $startDate, $endDate);
        break;
    case 'product':
        generateProductReport($connection, $output, $productId, $startDate, $endDate);
        break;
    default:
        // Якщо тип звіту не вказаний, виводимо повідомлення про помилку
        fputcsv($output, ['Помилка: Тип звіту не вказаний']);
        break;
}

// Закриття файлового потоку
fclose($output);
exit;

/**
 * Генерація загального звіту
 * 
 * @param mysqli $connection Підключення до БД
 * @param resource $output Файловий потік виводу
 * @param string $startDate Початкова дата
 * @param string $endDate Кінцева дата
 */
function generateGeneralReport($connection, $output, $startDate, $endDate) {
    // Записуємо заголовок звіту
    fputcsv($output, ['Загальний звіт за період ' . formatDate($startDate) . ' - ' . formatDate($endDate)]);
    fputcsv($output, []);
    
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
    
    // Записуємо загальну статистику
    fputcsv($output, ['Загальна кількість замовлень', $generalStats['total_orders']]);
    fputcsv($output, ['Загальна кількість одиниць', $generalStats['total_quantity']]);
    fputcsv($output, ['Загальна сума продажів', number_format($generalStats['total_sales'], 2) . ' грн']);
    fputcsv($output, ['Кількість клієнтів', $generalStats['total_clients']]);
    fputcsv($output, ['Кількість унікальних продуктів', $generalStats['total_products']]);
    fputcsv($output, []);
    
    // Записуємо інформацію про топ-5 продуктів
    fputcsv($output, ['Топ-5 продуктів за кількістю замовлень']);
    fputcsv($output, ['ID', 'Назва продукту', 'Кількість', 'Сума продажів (грн)']);
    
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
        fputcsv($output, [
            $product['id'],
            $product['nazvanie'],
            $product['total_quantity'],
            number_format($product['total_sales'], 2)
        ]);
    }
    
    fputcsv($output, []);
    
    // Записуємо інформацію про топ-5 клієнтів
    fputcsv($output, ['Топ-5 клієнтів за сумою замовлень']);
    fputcsv($output, ['ID', 'Назва клієнта', 'Кількість замовлень', 'Загальна кількість', 'Сума продажів (грн)']);
    
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
        fputcsv($output, [
            $client['id'],
            $client['name'],
            $client['order_count'],
            $client['total_quantity'],
            number_format($client['total_sales'], 2)
        ]);
    }
}

/**
 * Генерація звіту по клієнту
 * 
 * @param mysqli $connection Підключення до БД
 * @param resource $output Файловий потік виводу
 * @param int $clientId ID клієнта
 * @param string $startDate Початкова дата
 * @param string $endDate Кінцева дата
 */
function generateClientReport($connection, $output, $clientId, $startDate, $endDate) {
    // Отримання інформації про клієнта
    $clientQuery = "SELECT * FROM klientu WHERE id = ?";
    $stmt = mysqli_prepare($connection, $clientQuery);
    mysqli_stmt_bind_param($stmt, "i", $clientId);
    mysqli_stmt_execute($stmt);
    $clientResult = mysqli_stmt_get_result($stmt);
    $client = mysqli_fetch_assoc($clientResult);
    
    if (!$client) {
        fputcsv($output, ['Помилка: Клієнт не знайдений']);
        return;
    }
    
    // Записуємо заголовок звіту
    fputcsv($output, ['Звіт по клієнту: ' . $client['name'] . ' за період ' . formatDate($startDate) . ' - ' . formatDate($endDate)]);
    fputcsv($output, []);
    
    // Записуємо інформацію про клієнта
    fputcsv($output, ['Інформація про клієнта']);
    fputcsv($output, ['Компанія', $client['name']]);
    fputcsv($output, ['Контактна особа', $client['fio']]);
    fputcsv($output, ['Посада', $client['dolj']]);
    fputcsv($output, ['Телефон', $client['tel']]);
    fputcsv($output, ['Email', $client['mail']]);
    fputcsv($output, ['Місто', $client['city']]);
    fputcsv($output, ['Адреса', $client['adres']]);
    fputcsv($output, ['Відстань', $client['rast'] . ' км']);
    fputcsv($output, []);
    
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
    
    // Записуємо статистику
    fputcsv($output, ['Статистика замовлень']);
    fputcsv($output, ['Кількість замовлень', $clientStats['total_orders']]);
    fputcsv($output, ['Загальна кількість продукції', $clientStats['total_quantity']]);
    fputcsv($output, ['Загальна сума', number_format($clientStats['total_sales'], 2) . ' грн']);
    fputcsv($output, []);
    
    // Записуємо список замовлень
    fputcsv($output, ['Список замовлень за обраний період']);
    fputcsv($output, ['ID', 'Продукт', 'Кількість', 'Сума (грн)', 'Дата', 'Зміна', 'Статус']);
    
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
    
    while ($order = mysqli_fetch_assoc($clientOrdersResult)) {
        fputcsv($output, [
            $order['idd'],
            $order['nazvanie'],
            $order['kol'],
            number_format($order['total_price'], 2),
            formatDate($order['data']),
            $order['doba'],
            $order['status']
        ]);
    }
}

/**
 * Генерація звіту по продукту
 * 
 * @param mysqli $connection Підключення до БД
 * @param resource $output Файловий потік виводу
 * @param int $productId ID продукту
 * @param string $startDate Початкова дата
 * @param string $endDate Кінцева дата
 */
function generateProductReport($connection, $output, $productId, $startDate, $endDate) {
    // Отримання інформації про продукт
    $productQuery = "SELECT * FROM product WHERE id = ?";
    $stmt = mysqli_prepare($connection, $productQuery);
    mysqli_stmt_bind_param($stmt, "i", $productId);
    mysqli_stmt_execute($stmt);
    $productResult = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($productResult);
    
    if (!$product) {
        fputcsv($output, ['Помилка: Продукт не знайдений']);
        return;
    }
    
    // Записуємо заголовок звіту
    fputcsv($output, ['Звіт по продукту: ' . $product['nazvanie'] . ' за період ' . formatDate($startDate) . ' - ' . formatDate($endDate)]);
    fputcsv($output, []);
    
    // Записуємо інформацію про продукт
    fputcsv($output, ['Інформація про продукт']);
    fputcsv($output, ['Назва', $product['nazvanie']]);
    fputcsv($output, ['Вага', $product['ves'] . ' кг']);
    fputcsv($output, ['Строк реалізації', $product['srok'] . ' годин']);
    fputcsv($output, ['Собівартість', $product['stoimost'] . ' грн']);
    fputcsv($output, ['Ціна', $product['zena'] . ' грн']);
    fputcsv($output, ['Прибуток', number_format($product['zena'] - $product['stoimost'], 2) . ' грн']);
    fputcsv($output, []);
    
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
    
    // Записуємо статистику
    fputcsv($output, ['Статистика продажів']);
    fputcsv($output, ['Кількість замовлень', $productStats['total_orders']]);
    fputcsv($output, ['Загальна кількість одиниць', $productStats['total_quantity']]);
    fputcsv($output, ['Загальна сума продажів', number_format($productStats['total_sales'], 2) . ' грн']);
    fputcsv($output, ['Кількість клієнтів', $productStats['total_clients']]);
    fputcsv($output, []);
    
    // Записуємо топ клієнтів по продукту
    fputcsv($output, ['Топ клієнтів по замовленнях продукту']);
    fputcsv($output, ['ID', 'Клієнт', 'Кількість замовлень', 'Загальна кількість', 'Сума продажів (грн)']);
    
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
        fputcsv($output, [
            $client['id'],
            $client['name'],
            $client['order_count'],
            $client['total_quantity'],
            number_format($client['total_quantity'] * $product['zena'], 2)
        ]);
    }
    
    fputcsv($output, []);
    
    // Записуємо список замовлень продукту
    fputcsv($output, ['Список замовлень за обраний період']);
    fputcsv($output, ['ID', 'Клієнт', 'Кількість', 'Сума (грн)', 'Дата', 'Зміна', 'Статус']);
    
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
    
    while ($order = mysqli_fetch_assoc($productOrdersResult)) {
        fputcsv($output, [
            $order['idd'],
            $order['client_name'],
            $order['kol'],
            number_format($order['total_price'], 2),
            formatDate($order['data']),
            $order['doba'],
            $order['status']
        ]);
    }
}
?>