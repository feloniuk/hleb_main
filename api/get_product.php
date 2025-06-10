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

// Валідація штрих-коду (має бути 8-значним числом)
if (!preg_match('/^\d{8}$/', $barcode)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Невірний формат штрих-коду. Має бути 8-значне число'
    ]);
    exit;
}

$connection = connectDatabase();

// Пошук продукту за штрих-кодом
$query = "SELECT * FROM product WHERE barcode = ?";
$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, "s", $barcode);
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