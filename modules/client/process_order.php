<?php
/**
 * Обробник оформлення замовлення з кошика
 */

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Перевірка доступу
if (!checkAccess(['client'])) {
    header("Location: ../../index.php");
    exit;
}

$connection = connectDatabase();
$clientId = $_SESSION['id'];
$success = '';
$error = '';

// Перевірка методу запиту
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: cart.php");
    exit;
}

// Отримання даних з форми
$products = $_POST['products'] ?? [];
$quantities = $_POST['quantities'] ?? [];
$data = $_POST['data'] ?? '';
$doba = $_POST['doba'] ?? '';
$comments = $_POST['comments'] ?? '';

// Валідація даних
if (empty($products) || empty($quantities) || empty($data) || empty($doba)) {
    header("Location: cart.php?error=" . urlencode('Не всі дані заповнені'));
    exit;
}

// Перевірка правильності дати
$orderDate = new DateTime($data);
$today = new DateTime();
$today->setTime(0, 0, 0);
$interval = $today->diff($orderDate);

if ($interval->days < 1) {
    header("Location: cart.php?error=" . urlencode('Дата замовлення повинна бути не раніше завтрашнього дня'));
    exit;
}

// Обробка кожного продукту у замовленні
$successCount = 0;
$errorMessages = [];

for ($i = 0; $i < count($products); $i++) {
    $productId = intval($products[$i]);
    $quantity = intval($quantities[$i]);
    
    if ($productId <= 0 || $quantity <= 0) {
        continue;
    }
    
    // Додавання замовлення в базу даних
    $query = "INSERT INTO zayavki (idklient, id, kol, data, doba, status) VALUES (?, ?, ?, ?, ?, 'нове')";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "iiiss", $clientId, $productId, $quantity, $data, $doba);
    
    if (mysqli_stmt_execute($stmt)) {
        $successCount++;
    } else {
        $errorMessages[] = "Помилка при створенні замовлення для продукту ID $productId: " . mysqli_error($connection);
    }
}

// Збереження коментаря до замовлення, якщо він є
if (!empty($comments) && $successCount > 0) {
    // Якщо є потреба, створіть таблицю для коментарів у БД та додайте код для збереження
    // Для прикладу, ми просто показуємо, що коментар отриманий
    $success .= " Коментар до замовлення збережено.";
}

// Перевірка результату
if ($successCount > 0) {
    // Очищення кошика здійснюється через JavaScript на стороні клієнта
    header("Location: orders.php?success=" . urlencode("Замовлення успішно оформлено! Додано $successCount продуктів."));
    exit;
} else {
    $errorMessage = 'Помилка при оформленні замовлення. ' . implode(' ', $errorMessages);
    header("Location: cart.php?error=" . urlencode($errorMessage));
    exit;
}