<?php
/**
 * Отримання деталей замовлення для модального вікна
 */

require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Встановлення заголовків для JSON
header("Content-Type: application/json; charset=UTF-8");

// Перевірка доступу
if (!checkAccess(['brigadir'])) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Доступ заборонено'
    ]);
    exit;
}

// Перевірка наявності ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'ID замовлення не вказано'
    ]);
    exit;
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

if (mysqli_num_rows($result) === 1) {
    $order = mysqli_fetch_assoc($result);
    
    // Розрахунок загальної суми та ваги
    $order['total_price'] = $order['kol'] * $order['zena'];
    $order['total_weight'] = $order['kol'] * $order['ves'];
    
    // Додавання URL для зображення
    if (!empty($order['image'])) {
        $order['image_url'] = 'http://' . $_SERVER['HTTP_HOST'] . '/' . $order['image'];
    } else {
        $order['image_url'] = 'http://' . $_SERVER['HTTP_HOST'] . '/assets/img/product-placeholder.jpg';
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Замовлення знайдено',
        'data' => $order
    ]);
} else {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Замовлення не знайдено'
    ]);
}

// Закриття підключення до БД
mysqli_close($connection);