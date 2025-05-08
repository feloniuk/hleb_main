<?php
/**
 * API для отримання інформації про продукт за штрих-кодом
 * 
 * Цей файл використовується для отримання інформації про продукт
 * за допомогою сканера штрих-кодів.
 */

// Встановлення заголовків для CORS та JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Підключення необхідних файлів
require_once '../config/database.php';

// Перевірка наявності параметра штрих-коду
if (!isset($_GET['barcode']) || empty($_GET['barcode'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Параметр штрих-коду не вказано'
    ]);
    exit;
}

$barcode = $_GET['barcode'];
$connection = connectDatabase();

// В реальному проекті тут буде пошук за реальним штрих-кодом
// Для демонстрації використовуємо ID продукту
$productId = intval($barcode);

// Отримання інформації про продукт
$query = "SELECT * FROM product WHERE id = ?";
$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, "i", $productId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 1) {
    $product = mysqli_fetch_assoc($result);
    
    // Формування повного шляху до зображення
    if (!empty($product['image'])) {
        $product['image_url'] = 'http://' . $_SERVER['HTTP_HOST'] . '/' . $product['image'];
    } else {
        $product['image_url'] = 'http://' . $_SERVER['HTTP_HOST'] . '/assets/img/product-placeholder.jpg';
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Продукт знайдено',
        'product' => $product
    ]);
} else {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Продукт не знайдено за штрих-кодом ' . $barcode
    ]);
}

// Закриття підключення до БД
mysqli_close($connection);