<?php
/**
 * Обробник повторення замовлення
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

// Перевірка наявності ID замовлення
$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($orderId <= 0) {
    header("Location: orders.php?error=" . urlencode('Невірний ID замовлення'));
    exit;
}

// Перевірка, чи замовлення належить даному клієнту
$checkQuery = "SELECT * FROM zayavki WHERE idd = ? AND idklient = ?";
$stmt = mysqli_prepare($connection, $checkQuery);
mysqli_stmt_bind_param($stmt, "ii", $orderId, $clientId);
mysqli_stmt_execute($stmt);
$checkResult = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($checkResult) !== 1) {
    header("Location: orders.php?error=" . urlencode('Замовлення не знайдено або не належить вам'));
    exit;
}

$order = mysqli_fetch_assoc($checkResult);

// Отримання інформації про продукт
$productId = $order['id'];
$quantity = $order['kol'];

// Створення нового замовлення
$data = date('Y-m-d', strtotime('+1 day')); // Дата доставки - завтра
$doba = $order['doba']; // Та ж сама зміна

$insertQuery = "INSERT INTO zayavki (idklient, id, kol, data, doba, status) VALUES (?, ?, ?, ?, ?, 'нове')";
$stmt = mysqli_prepare($connection, $insertQuery);
mysqli_stmt_bind_param($stmt, "iiiss", $clientId, $productId, $quantity, $data, $doba);

if (mysqli_stmt_execute($stmt)) {
    $newOrderId = mysqli_insert_id($connection);
    header("Location: orders.php?success=" . urlencode('Замовлення успішно повторено. Новий номер замовлення: ' . $newOrderId));
} else {
    header("Location: orders.php?error=" . urlencode('Помилка при повторенні замовлення: ' . mysqli_error($connection)));
}
exit;