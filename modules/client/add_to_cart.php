<?php
/**
 * Обробник додавання товару в кошик
 * 
 * Оскільки кошик реалізовано через localStorage,
 * цей скрипт просто перенаправляє назад на сторінку продуктів
 * або на деталі продукту, залежно від параметра.
 */

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Перевірка доступу
if (!checkAccess(['client'])) {
    header("Location: ../../index.php");
    exit;
}

// Отримання параметрів
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$returnUrl = isset($_GET['return']) ? $_GET['return'] : 'products.php';

// Перевірка наявності ID продукту
if ($productId <= 0) {
    header("Location: $returnUrl");
    exit;
}

// Перенаправлення назад (JavaScript-скрипт для додавання в кошик виконається на сторінці)
if ($returnUrl === 'details') {
    header("Location: product_details.php?id=$productId");
} else {
    header("Location: products.php");
}
exit;