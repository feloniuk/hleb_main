<?php
/**
 * API для збереження відсканованих продуктів
 * 
 * Цей файл використовується для збереження даних,
 * отриманих через сканер штрих-кодів.
 */

// Встановлення заголовків для CORS та JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Підключення необхідних файлів
require_once '../config/database.php';

// Перевірка методу запиту
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method Not Allowed'
    ]);
    exit;
}

// Отримання даних з тіла запиту
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Перевірка наявності необхідних даних
if (!isset($data['products']) || !is_array($data['products'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Некоректні дані'
    ]);
    exit;
}

$connection = connectDatabase();

// Створення нового запису в таблиці zakazu
$query = "INSERT INTO zakazu (data, doba) VALUES (CURDATE(), 'денна')";
$result = mysqli_query($connection, $query);

if (!$result) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Помилка при створенні замовлення: ' . mysqli_error($connection)
    ]);
    exit;
}

$zakazId = mysqli_insert_id($connection);

// Внесення кожного продукту в таблицю newzakaz
$successCount = 0;
$errorMessages = [];

foreach ($data['products'] as $product) {
    if (!isset($product['id']) || !isset($product['quantity'])) {
        continue;
    }
    
    $productId = intval($product['id']);
    $quantity = intval($product['quantity']);
    
    // Отримання інформації про клієнта (в даному випадку використовуємо тестового клієнта)
    $clientId = 1; // ID тестового клієнта
    
    $insertQuery = "INSERT INTO newzayavki (idklient, id, kol, data, doba) 
                   VALUES (?, ?, ?, CURDATE(), 'денна')";
    $stmt = mysqli_prepare($connection, $insertQuery);
    mysqli_stmt_bind_param($stmt, "iii", $clientId, $productId, $quantity);
    
    if (mysqli_stmt_execute($stmt)) {
        $successCount++;
    } else {
        $errorMessages[] = "Помилка при додаванні продукту ID $productId: " . mysqli_error($connection);
    }
    
    mysqli_stmt_close($stmt);
}

// Закриття підключення до БД
mysqli_close($connection);

if (count($errorMessages) === 0) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => "Успішно збережено $successCount продуктів"
    ]);
} else {
    http_response_code(207); // Multi-Status
    echo json_encode([
        'success' => true,
        'message' => "Збережено $successCount продуктів з деякими помилками",
        'errors' => $errorMessages
    ]);
}