<?php
/**
 * Експорт замовлень в Excel
 */

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Підключення бібліотеки для Excel
// В реальному проекті слід використовувати PhpSpreadsheet або PHPExcel
// Сторонніх бібліотек ми не використовуємо, тому створюємо CSV, який можна відкрити в Excel

// Перевірка доступу
if (!checkAccess(['brigadir'])) {
    header("Location: ../../index.php");
    exit;
}

// Встановлення заголовків для CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="orders_' . date('Y-m-d') . '.csv"');

// Створення файлового потоку для CSV
$output = fopen('php://output', 'w');

// Додавання BOM для правильного відображення UTF-8 в Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Запис заголовків
fputcsv($output, [
    'ID',
    'Клієнт',
    'Контактна особа',
    'Продукт',
    'Кількість',
    'Ціна (грн)',
    'Сума (грн)',
    'Дата',
    'Зміна',
    'Статус'
]);

// Отримання параметрів фільтрації з URL
$client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : null;
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : null;
$doba = isset($_GET['doba']) ? $_GET['doba'] : null;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : null;
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : null;
$status = isset($_GET['status']) ? $_GET['status'] : null;

// Підключення до БД
$connection = connectDatabase();

// Базовий запит
$query = "SELECT z.idd, z.idklient, k.name as client_name, k.fio, z.id, p.nazvanie as product_name, 
                z.kol, z.data, z.doba, z.status, p.zena, (z.kol * p.zena) as total_price
          FROM zayavki z
          JOIN klientu k ON z.idklient = k.id
          JOIN product p ON z.id = p.id
          WHERE 1=1";

// Додавання умов фільтрації
$params = [];
$types = '';

if ($client_id !== null) {
    $query .= " AND z.idklient = ?";
    $params[] = $client_id;
    $types .= 'i';
}

if ($product_id !== null) {
    $query .= " AND z.id = ?";
    $params[] = $product_id;
    $types .= 'i';
}

if ($doba !== null) {
    $query .= " AND z.doba = ?";
    $params[] = $doba;
    $types .= 's';
}

if ($status !== null) {
    $query .= " AND z.status = ?";
    $params[] = $status;
    $types .= 's';
}

if (!empty($date_from)) {
    $query .= " AND z.data >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if (!empty($date_to)) {
    $query .= " AND z.data <= ?";
    $params[] = $date_to;
    $types .= 's';
}

// Додавання сортування
$query .= " ORDER BY z.data DESC, z.idd DESC";

// Підготовка та виконання запиту
$stmt = mysqli_prepare($connection, $query);

if (!empty($params)) {
    $bindParams = array_merge([$stmt, $types], $params);
    call_user_func_array('mysqli_stmt_bind_param', $bindParams);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Запис даних у CSV
while ($row = mysqli_fetch_assoc($result)) {
    // Перетворення статусу
    switch ($row['status']) {
        case 'нове':
            $status = 'Нове';
            break;
        case 'в обробці':
            $status = 'В обробці';
            break;
        case 'виконано':
            $status = 'Виконано';
            break;
        case 'скасовано':
            $status = 'Скасовано';
            break;
        default:
            $status = 'Невідомо';
            break;
    }
    
    // Форматування значень
    $date = date('d.m.Y', strtotime($row['data']));
    $price = number_format($row['zena'], 2, '.', '');
    $total_price = number_format($row['total_price'], 2, '.', '');
    $doba = ($row['doba'] === 'денна') ? 'Денна' : 'Нічна';
    
    // Запис рядка в CSV
    fputcsv($output, [
        $row['idd'],
        $row['client_name'],
        $row['fio'],
        $row['product_name'],
        $row['kol'],
        $price,
        $total_price,
        $date,
        $doba,
        $status
    ]);
}

// Закриття підключення до БД
mysqli_close($connection);

// Закриття файлового потоку
fclose($output);