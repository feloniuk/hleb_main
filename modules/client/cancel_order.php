<?php
/**
 * Обробник скасування замовлення
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

// Перевірка, чи замовлення належить даному клієнту і має статус 'нове'
$checkQuery = "SELECT * FROM zayavki WHERE idd = ? AND idklient = ? AND status = 'нове'";
$stmt = mysqli_prepare($connection, $checkQuery);
mysqli_stmt_bind_param($stmt, "ii", $orderId, $clientId);
mysqli_stmt_execute($stmt);
$checkResult = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($checkResult) !== 1) {
    header("Location: orders.php?error=" . urlencode('Неможливо скасувати замовлення. Дозволено скасовувати тільки нові замовлення.'));
    exit;
}

// Перевірка часу до доставки (мінімум 6 годин)
$order = mysqli_fetch_assoc($checkResult);
$orderDate = new DateTime($order['data']);
$now = new DateTime();
$interval = $now->diff($orderDate);

// Якщо різниця менше 6 годин (0.25 днів)
if ($interval->days === 0 && $order['doba'] === 'денна' && $now->format('H') >= 2) {
    header("Location: orders.php?error=" . urlencode('Неможливо скасувати замовлення. Скасування можливе не пізніше ніж за 6 годин до доставки.'));
    exit;
}

// Скасування замовлення
$updateQuery = "UPDATE zayavki SET status = 'скасовано' WHERE idd = ?";
$stmt = mysqli_prepare($connection, $updateQuery);
mysqli_stmt_bind_param($stmt, "i", $orderId);

if (mysqli_stmt_execute($stmt)) {
    header("Location: orders.php?success=" . urlencode('Замовлення успішно скасовано'));
} else {
    header("Location: orders.php?error=" . urlencode('Помилка при скасуванні замовлення: ' . mysqli_error($connection)));
}
exit;