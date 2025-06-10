<?php
/**
 * Скрипт для оновлення штрих-кодів існуючих продуктів
 * 
 * Файл: scripts/update_barcodes.php
 * Запускати один раз після оновлення структури БД
 */

require_once '../config/database.php';
require_once '../includes/barcode_functions.php';

// Встановлення часового ліміту
set_time_limit(300);

echo "<!DOCTYPE html>
<html lang='uk'>
<head>
    <meta charset='UTF-8'>
    <title>Оновлення штрих-кодів</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        table { border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Оновлення штрих-кодів продуктів</h1>";

$connection = connectDatabase();

// Перевірка чи існує поле barcode
$checkFieldQuery = "SHOW COLUMNS FROM product LIKE 'barcode'";
$checkResult = mysqli_query($connection, $checkFieldQuery);

if (mysqli_num_rows($checkResult) == 0) {
    echo "<p class='error'>Помилка: Поле 'barcode' не існує в таблиці 'product'.</p>";
    echo "<p>Виконайте наступний SQL запит:</p>";
    echo "<pre>ALTER TABLE `product` ADD COLUMN `barcode` VARCHAR(8) UNIQUE AFTER `id`;</pre>";
    exit;
}

echo "<p class='info'>Починаємо оновлення штрих-кодів...</p>";

// Отримання всіх продуктів
$query = "SELECT id, nazvanie, barcode FROM product ORDER BY id";
$result = mysqli_query($connection, $query);

if (!$result) {
    echo "<p class='error'>Помилка при отриманні продуктів: " . mysqli_error($connection) . "</p>";
    exit;
}

$totalProducts = mysqli_num_rows($result);
$updatedCount = 0;
$errorCount = 0;

echo "<p>Знайдено продуктів: <strong>$totalProducts</strong></p>";
echo "<table>
        <tr>
            <th>ID</th>
            <th>Назва продукту</th>
            <th>Старий штрих-код</th>
            <th>Новий штрих-код</th>
            <th>Статус</th>
        </tr>";

while ($product = mysqli_fetch_assoc($result)) {
    $productId = $product['id'];
    $productName = $product['nazvanie'];
    $oldBarcode = $product['barcode'];
    
    echo "<tr>";
    echo "<td>$productId</td>";
    echo "<td>" . htmlspecialchars($productName) . "</td>";
    echo "<td>" . ($oldBarcode ? $oldBarcode : '<em>Відсутній</em>') . "</td>";
    
    // Генеруємо новий штрих-код тільки якщо його немає
    if (empty($oldBarcode)) {
        $newBarcode = generateProductBarcode($productId);
        
        // Перевірка унікальності
        if (isBarcodeExists($connection, $newBarcode)) {
            echo "<td class='error'>Конфлікт</td>";
            echo "<td class='error'>Штрих-код $newBarcode вже існує</td>";
            $errorCount++;
        } else {
            // Оновлення штрих-коду
            $updateQuery = "UPDATE product SET barcode = ? WHERE id = ?";
            $updateStmt = mysqli_prepare($connection, $updateQuery);
            mysqli_stmt_bind_param($updateStmt, "si", $newBarcode, $productId);
            
            if (mysqli_stmt_execute($updateStmt)) {
                echo "<td class='success'>" . formatBarcodeDisplay($newBarcode) . "</td>";
                echo "<td class='success'>Оновлено</td>";
                $updatedCount++;
            } else {
                echo "<td class='error'>Помилка</td>";
                echo "<td class='error'>" . mysqli_error($connection) . "</td>";
                $errorCount++;
            }
            
            mysqli_stmt_close($updateStmt);
        }
    } else {
        echo "<td>-</td>";
        echo "<td class='info'>Вже має штрих-код</td>";
    }
    
    echo "</tr>";
}

echo "</table>";

// Підсумок
echo "<h2>Підсумок оновлення:</h2>";
echo "<ul>";
echo "<li>Всього продуктів: <strong>$totalProducts</strong></li>";
echo "<li class='success'>Успішно оновлено: <strong>$updatedCount</strong></li>";
echo "<li class='error'>Помилок: <strong>$errorCount</strong></li>";
echo "<li class='info'>Вже мали штрих-код: <strong>" . ($totalProducts - $updatedCount - $errorCount) . "</strong></li>";
echo "</ul>";

// Перевірка цілісності даних
echo "<h2>Перевірка цілісності:</h2>";

// Перевірка на дублікати
$duplicateQuery = "SELECT barcode, COUNT(*) as count 
                   FROM product 
                   WHERE barcode IS NOT NULL 
                   GROUP BY barcode 
                   HAVING count > 1";
$duplicateResult = mysqli_query($connection, $duplicateQuery);

if (mysqli_num_rows($duplicateResult) > 0) {
    echo "<p class='error'>Знайдено дублікати штрих-кодів:</p>";
    echo "<ul>";
    while ($duplicate = mysqli_fetch_assoc($duplicateResult)) {
        echo "<li>Штрих-код " . $duplicate['barcode'] . " зустрічається " . $duplicate['count'] . " разів</li>";
    }
    echo "</ul>";
} else {
    echo "<p class='success'>Дублікатів штрих-кодів не знайдено.</p>";
}

// Перевірка на правильність формату
$wrongFormatQuery = "SELECT id, nazvanie, barcode 
                     FROM product 
                     WHERE barcode IS NOT NULL 
                     AND (LENGTH(barcode) != 8 OR barcode NOT REGEXP '^[0-9]+$')";
$wrongFormatResult = mysqli_query($connection, $wrongFormatQuery);

if (mysqli_num_rows($wrongFormatResult) > 0) {
    echo "<p class='error'>Знайдено штрих-коди з неправильним форматом:</p>";
    echo "<ul>";
    while ($wrong = mysqli_fetch_assoc($wrongFormatResult)) {
        echo "<li>ID: " . $wrong['id'] . ", Продукт: " . htmlspecialchars($wrong['nazvanie']) . 
             ", Штрих-код: " . $wrong['barcode'] . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p class='success'>Всі штрих-коди мають правильний формат.</p>";
}

mysqli_close($connection);

echo "<hr>";
echo "<p><a href='../index.php'>Повернутися на головну</a></p>";
echo "</body></html>";