<?php 
$pageTitle = 'Експорт замовлень';

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Перевірка доступу
if (!checkAccess(['admin'])) {
    header("Location: ../../index.php");
    exit;
}

$connection = connectDatabase();

// Отримання параметрів фільтрації з URL
$clientId = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;
$productId = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';
$doba = isset($_GET['doba']) ? $_GET['doba'] : '';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Формування запиту з урахуванням фільтрів
$query = "SELECT z.idd, z.data, z.doba, z.status, z.kol, 
           k.name as client_name, k.city,
           p.nazvanie as product_name, p.ves, p.zena,
           (z.kol * p.zena) as total_price,
           (z.kol * p.ves) as total_weight
          FROM zayavki z
          JOIN klientu k ON z.idklient = k.id
          JOIN product p ON z.id = p.id
          WHERE 1=1";

$params = [];
$types = '';

if ($clientId > 0) {
    $query .= " AND z.idklient = ?";
    $params[] = $clientId;
    $types .= 'i';
}

if ($productId > 0) {
    $query .= " AND z.id = ?";
    $params[] = $productId;
    $types .= 'i';
}

if (!empty($status)) {
    $query .= " AND z.status = ?";
    $params[] = $status;
    $types .= 's';
}

if (!empty($doba)) {
    $query .= " AND z.doba = ?";
    $params[] = $doba;
    $types .= 's';
}

if (!empty($dateFrom)) {
    $query .= " AND z.data >= ?";
    $params[] = $dateFrom;
    $types .= 's';
}

if (!empty($dateTo)) {
    $query .= " AND z.data <= ?";
    $params[] = $dateTo;
    $types .= 's';
}

$query .= " ORDER BY z.data DESC, z.idd DESC";

// Виконання запиту
$stmt = mysqli_prepare($connection, $query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Запис в журнал
logAction($connection, 'Експорт замовлень', 'Експортовано список замовлень в Excel');

// Формування Excel-файлу
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="orders_' . date('Y-m-d') . '.xls"');

echo '<table border="1">';
echo '<tr>';
echo '<th>ID</th>';
echo '<th>Дата</th>';
echo '<th>Клієнт</th>';
echo '<th>Місто</th>';
echo '<th>Продукт</th>';
echo '<th>Кількість</th>';
echo '<th>Вага од. (кг)</th>';
echo '<th>Загальна вага (кг)</th>';
echo '<th>Ціна од. (грн)</th>';
echo '<th>Загальна сума (грн)</th>';
echo '<th>Зміна</th>';
echo '<th>Статус</th>';
echo '</tr>';

while ($order = mysqli_fetch_assoc($result)) {
    echo '<tr>';
    echo '<td>' . $order['idd'] . '</td>';
    echo '<td>' . $order['data'] . '</td>';
    echo '<td>' . htmlspecialchars($order['client_name']) . '</td>';
    echo '<td>' . htmlspecialchars($order['city']) . '</td>';
    echo '<td>' . htmlspecialchars($order['product_name']) . '</td>';
    echo '<td>' . $order['kol'] . '</td>';
    echo '<td>' . $order['ves'] . '</td>';
    echo '<td>' . number_format($order['total_weight'], 2) . '</td>';
    echo '<td>' . number_format($order['zena'], 2) . '</td>';
    echo '<td>' . number_format($order['total_price'], 2) . '</td>';
    echo '<td>' . $order['doba'] . '</td>';
    echo '<td>' . $order['status'] . '</td>';
    echo '</tr>';
}

echo '</table>';
exit;