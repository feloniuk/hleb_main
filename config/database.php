<?php
/**
 * Конфігурація бази даних
 */

define('DB_SERVER', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'hleb-main');

/**
 * Підключення до бази даних
 */
function connectDatabase() {
    $connection = mysqli_connect(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
    
    if (!$connection) {
        die("Помилка підключення до бази даних: " . mysqli_connect_error());
    }
    
    mysqli_set_charset($connection, "utf8");
    
    return $connection;
}