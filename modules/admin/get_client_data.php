<?php 
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Перевірка доступу
if (!checkAccess(['admin'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Доступ заборонено']);
    exit;
}

// Перевірка наявності ID клієнта
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID клієнта не вказано']);
    exit;
}

$clientId = intval($_GET['id']);
$connection = connectDatabase();

// Отримання даних клієнта
$query = "SELECT * FROM klientu WHERE id = ?";
$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, "i", $clientId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 1) {
    $client = mysqli_fetch_assoc($result);
    
    // Повертаємо дані у форматі JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'client' => $client
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Клієнта не знайдено']);
}
exit;