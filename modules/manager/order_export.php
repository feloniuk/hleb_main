<?php
$pageTitle = 'Експорт замовлень';

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Перевірка доступу
if (!checkAccess(['manager'])) {
    header("Location: ../../index.php");
    exit;
}

$connection = connectDatabase();

// Отримання параметрів фільтрації
$clientId = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;
$productId = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';
$doba = isset($_GET['doba']) ? $_GET['doba'] : '';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Назва файлу для експорту
$fileName = 'orders_export_' . date('Y-m-d') . '.csv';

// Встановлення заголовків для завантаження файлу
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $fileName . '"');

// Створення файлового потоку на вивід
$output = fopen('php://output', 'w');

// Додавання BOM для правильного відображення кирилиці в Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Додавання заголовків для стовпців CSV
fputcsv($output, ['ID', 'Клієнт', 'Продукт', 'Кількість', 'Загальна вага (кг)', 'Ціна за одиницю (грн)', 'Загальна сума (грн)', 'Дата', 'Зміна', 'Статус']);

// Підготовка запиту для вибірки замовлень
$query = "
    SELECT z.idd, k.name as client_name, p.nazvanie as product_name, z.kol, 
           p.ves, p.zena, (z.kol * p.ves) as total_weight, (z.kol * p.zena) as total_price,
           z.data, z.doba, z.status
    FROM zayavki z
    JOIN klientu k ON z.idklient = k.id
    JOIN product p ON z.id = p.id
    WHERE 1=1
";

// Додавання параметрів фільтрації
$bindParams = [];
$bindTypes = '';

if ($clientId > 0) {
    $query .= " AND z.idklient = ?";
    $bindParams[] = $clientId;
    $bindTypes .= 'i';
}

if ($productId > 0) {
    $query .= " AND z.id = ?";
    $bindParams[] = $productId;
    $bindTypes .= 'i';
}

if (!empty($status)) {
    $query .= " AND z.status = ?";
    $bindParams[] = $status;
    $bindTypes .= 's';
}

if (!empty($doba)) {
    $query .= " AND z.doba = ?";
    $bindParams[] = $doba;
    $bindTypes .= 's';
}

if (!empty($dateFrom)) {
    $query .= " AND z.data >= ?";
    $bindParams[] = $dateFrom;
    $bindTypes .= 's';
}

if (!empty($dateTo)) {
    $query .= " AND z.data <= ?";
    $bindParams[] = $dateTo;
    $bindTypes .= 's';
}

// Додавання сортування
$query .= " ORDER BY z.data DESC, z.idd DESC";

// Підготовка запиту
$stmt = mysqli_prepare($connection, $query);

// Привязка параметрів, якщо вони є
if (!empty($bindParams)) {
    array_unshift($bindParams, $bindTypes);
    call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt], $bindParams));
}

// Виконання запиту
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Запис даних у CSV
while ($row = mysqli_fetch_assoc($result)) {
    // Форматування дати
    $date = formatDate($row['data']);
    
    // Підготовка даних для CSV-рядка
    $csvRow = [
        $row['idd'],
        $row['client_name'],
        $row['product_name'],
        $row['kol'],
        number_format($row['total_weight'], 2),
        number_format($row['zena'], 2),
        number_format($row['total_price'], 2),
        $date,
        $row['doba'],
        $row['status']
    ];
    
    // Запис рядка у файл
    fputcsv($output, $csvRow);
}

// Закриття файлового потоку
fclose($output);

// Завершення скрипту
exit;
?>