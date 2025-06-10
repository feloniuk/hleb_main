<?php
/**
 * Функції для роботи з штрих-кодами продуктів
 * 
 * Файл: includes/barcode_functions.php
 */

/**
 * Генерує 8-значний штрих-код для продукту
 * 
 * @param int $productId ID продукту
 * @return string 8-значний штрих-код
 */
function generateProductBarcode($productId) {
    // Базове значення 10000000 + ID продукту
    // Це забезпечує унікальність та 8-значний формат
    $barcode = str_pad(10000000 + $productId, 8, '0', STR_PAD_LEFT);
    return $barcode;
}

/**
 * Перевіряє чи штрих-код вже існує в базі даних
 * 
 * @param mysqli $connection Підключення до БД
 * @param string $barcode Штрих-код для перевірки
 * @param int|null $excludeProductId ID продукту для виключення з перевірки (при оновленні)
 * @return bool true якщо штрих-код вже існує
 */
function isBarcodeExists($connection, $barcode, $excludeProductId = null) {
    $query = "SELECT COUNT(*) as count FROM product WHERE barcode = ?";
    
    if ($excludeProductId !== null) {
        $query .= " AND id != ?";
        $stmt = mysqli_prepare($connection, $query);
        mysqli_stmt_bind_param($stmt, "si", $barcode, $excludeProductId);
    } else {
        $stmt = mysqli_prepare($connection, $query);
        mysqli_stmt_bind_param($stmt, "s", $barcode);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    return $row['count'] > 0;
}

/**
 * Генерує унікальний штрих-код для нового продукту
 * 
 * @param mysqli $connection Підключення до БД
 * @return string Унікальний 8-значний штрих-код
 */
function generateUniqueBarcode($connection) {
    // Отримуємо наступний доступний ID
    $query = "SELECT MAX(id) as max_id FROM product";
    $result = mysqli_query($connection, $query);
    $row = mysqli_fetch_assoc($result);
    
    $nextId = ($row['max_id'] ?? 0) + 1;
    
    // Генеруємо штрих-код
    $barcode = generateProductBarcode($nextId);
    
    // Перевіряємо унікальність (на випадок якщо хтось вручну додав такий код)
    while (isBarcodeExists($connection, $barcode)) {
        $nextId++;
        $barcode = generateProductBarcode($nextId);
    }
    
    return $barcode;
}

/**
 * Валідує формат штрих-коду
 * 
 * @param string $barcode Штрих-код для валідації
 * @return bool true якщо формат правильний
 */
function validateBarcodeFormat($barcode) {
    // Перевірка чи це 8-значне число
    return preg_match('/^\d{8}$/', $barcode);
}

/**
 * Форматує штрих-код для відображення
 * 
 * @param string $barcode Штрих-код
 * @return string Відформатований штрих-код
 */
function formatBarcodeDisplay($barcode) {
    // Можна додати форматування, наприклад: 1000-0001
    if (strlen($barcode) == 8) {
        return substr($barcode, 0, 4) . '-' . substr($barcode, 4, 4);
    }
    return $barcode;
}

/**
 * Генерує зображення штрих-коду (потребує бібліотеку для генерації штрих-кодів)
 * 
 * @param string $barcode Штрих-код
 * @param string $type Тип штрих-коду (EAN-8, Code128, тощо)
 * @return string URL або base64 зображення
 */
function generateBarcodeImage($barcode, $type = 'EAN8') {
    // Тут можна інтегрувати бібліотеку для генерації зображень штрих-кодів
    // Наприклад, використовуючи picqer/php-barcode-generator
    
    // Поки що повертаємо заглушку
    return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
}

/**
 * Отримує продукт за штрих-кодом
 * 
 * @param mysqli $connection Підключення до БД
 * @param string $barcode Штрих-код
 * @return array|null Масив з даними продукту або null
 */
function getProductByBarcode($connection, $barcode) {
    if (!validateBarcodeFormat($barcode)) {
        return null;
    }
    
    $query = "SELECT * FROM product WHERE barcode = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "s", $barcode);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 1) {
        return mysqli_fetch_assoc($result);
    }
    
    return null;
}

/**
 * Оновлює штрих-коди для всіх продуктів без штрих-коду
 * 
 * @param mysqli $connection Підключення до БД
 * @return int Кількість оновлених записів
 */
function updateMissingBarcodes($connection) {
    $query = "SELECT id FROM product WHERE barcode IS NULL OR barcode = ''";
    $result = mysqli_query($connection, $query);
    
    $updated = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        $barcode = generateProductBarcode($row['id']);
        
        $updateQuery = "UPDATE product SET barcode = ? WHERE id = ?";
        $stmt = mysqli_prepare($connection, $updateQuery);
        mysqli_stmt_bind_param($stmt, "si", $barcode, $row['id']);
        
        if (mysqli_stmt_execute($stmt)) {
            $updated++;
        }
    }
    
    return $updated;
}