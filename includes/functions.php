<?php
/**
 * Загальні функції системи
 */

/**
 * Генерує безпечний URL
 * 
 * @param string $text Текст для перетворення
 * @return string
 */
function generateSlug($text) {
    // Транслітерація кирилиці
    $cyr = [
        'а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п',
        'р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я',
        'А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','М','Н','О','П',
        'Р','С','Т','У','Ф','Х','Ц','Ч','Ш','Щ','Ъ','Ы','Ь','Э','Ю','Я',
        'ї','і','є','ґ','Ї','І','Є','Ґ'
    ];
    
    $lat = [
        'a','b','v','g','d','e','io','zh','z','i','y','k','l','m','n','o','p',
        'r','s','t','u','f','h','ts','ch','sh','sht','a','i','y','e','yu','ya',
        'A','B','V','G','D','E','Io','Zh','Z','I','Y','K','L','M','N','O','P',
        'R','S','T','U','F','H','Ts','Ch','Sh','Sht','A','I','Y','e','Yu','Ya',
        'yi','i','ye','g','Yi','I','Ye','G'
    ];
    
    $text = str_replace($cyr, $lat, $text);
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    $text = trim($text, '-');
    
    return $text;
}

/**
 * Форматує дату у зручний формат
 * 
 * @param string $date Дата у форматі YYYY-MM-DD
 * @param string $format Формат виведення
 * @return string
 */
function formatDate($date, $format = 'd.m.Y') {
    $dateObj = new DateTime($date);
    return $dateObj->format($format);
}

/**
 * Показує повідомлення про помилку
 * 
 * @param string $message Текст повідомлення
 * @return string HTML код повідомлення
 */
function showError($message) {
    return '
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Помилка!</strong> ' . htmlspecialchars($message) . '
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
}

/**
 * Показує повідомлення про успіх
 * 
 * @param string $message Текст повідомлення
 * @return string HTML код повідомлення
 */
function showSuccess($message) {
    return '
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>Успіх!</strong> ' . htmlspecialchars($message) . '
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
}

/**
 * Отримати список продукції
 * 
 * @param PDO $connection Підключення до бази даних
 * @return array Масив продукції
 */
function getProducts($connection) {
    $sql = "SELECT * FROM product ORDER BY nazvanie ASC";
    $result = mysqli_query($connection, $sql);
    
    $products = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
    
    return $products;
}

/**
 * Отримати список клієнтів
 * 
 * @param PDO $connection Підключення до бази даних
 * @return array Масив клієнтів
 */
function getClients($connection) {
    $sql = "SELECT * FROM klientu ORDER BY name ASC";
    $result = mysqli_query($connection, $sql);
    
    $clients = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $clients[] = $row;
    }
    
    return $clients;
}

/**
 * Отримати дані про клієнта за ID
 * 
 * @param PDO $connection Підключення до бази даних
 * @param int $id ID клієнта
 * @return array|false Дані клієнта або false
 */
function getClientById($connection, $id) {
    $sql = "SELECT * FROM klientu WHERE id = ?";
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 1) {
        return mysqli_fetch_assoc($result);
    }
    
    return false;
}

/**
 * Отримати дані про продукт за ID
 * 
 * @param PDO $connection Підключення до бази даних
 * @param int $id ID продукту
 * @return array|false Дані продукту або false
 */
function getProductById($connection, $id) {
    $sql = "SELECT * FROM product WHERE id = ?";
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 1) {
        return mysqli_fetch_assoc($result);
    }
    
    return false;
}