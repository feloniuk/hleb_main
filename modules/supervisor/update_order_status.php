<?php
/**
 * Оновлення статусу замовлення
 */

require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Встановлення заголовків для JSON
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Перевірка доступу
if (!checkAccess(['brigadir'])) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Доступ заборонено'
    ]);
    exit;
}

// Перевірка методу запиту
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Метод не дозволено'
    ]);
    exit;
}

// Отримання даних з тіла запиту
$data = json_decode(file_get_contents("php://input"), true);

// Перевірка наявності необхідних полів
if (!isset($data['order_id']) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Відсутні обов\'язкові поля'
    ]);
    exit;
}

$orderId = intval($data['order_id']);
$status = $data['status'];
$comment = isset($data['comment']) ? $data['comment'] : '';

// Перевірка валідності статусу
$validStatuses = ['нове', 'в обробці', 'виконано', 'скасовано'];
if (!in_array($status, $validStatuses)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Неправильний статус'
    ]);
    exit;
}

$connection = connectDatabase();

// Оновлення статусу замовлення
$query = "UPDATE zayavki SET status = ? WHERE idd = ?";
$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, "si", $status, $orderId);
$result = mysqli_stmt_execute($stmt);

if ($result) {
    // Додавання запису в журнал системи (якщо є таблиця)
    if (!empty($comment)) {
        $logQuery = "INSERT INTO system_log (action, user_id, details, ip_address) 
                     VALUES (?, ?, ?, ?)";
        $action = "Зміна статусу замовлення #" . $orderId . " на " . $status;
        $userId = $_SESSION['id'];
        $details = "Коментар: " . $comment;
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        
        $logStmt = mysqli_prepare($connection, $logQuery);
        mysqli_stmt_bind_param($logStmt, "siss", $action, $userId, $details, $ipAddress);
        mysqli_stmt_execute($logStmt);
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Статус замовлення успішно оновлено'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Помилка при оновленні статусу: ' . mysqli_error($connection)
    ]);
}

// Закриття підключення до БД
mysqli_close($connection);