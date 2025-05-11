<?php
/**
 * Система автентифікації та авторизації
 */

session_start();

/**
 * Перевірка логіна та паролю користувача
 * 
 * @param string $login Логін користувача
 * @param string $password Пароль користувача
 * @return bool|array Повертає дані користувача або false
 */
function loginUser($login, $password) {
    // Очищення вхідних даних
    $login = trim(htmlspecialchars(stripslashes($login)));
    $password = trim(htmlspecialchars(stripslashes($password)));
    
    if (empty($login) || empty($password)) {
        return false;
    }
    
    $connection = connectDatabase();
    
    // Спочатку перевіряємо в таблиці співробітників (polzovateli)
    $sql = "SELECT * FROM polzovateli WHERE login=?";
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "s", $login);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Якщо паролі співпадають
        if ($password == $user['password']) {
            $_SESSION['login'] = $user['login'];
            $_SESSION['id'] = $user['id'];
            $_SESSION['user_type'] = 'staff'; // Позначаємо, що це співробітник
            return $user;
        }
    }
    
    // Якщо не знайдено в polzovateli, перевіряємо в таблиці клієнтів (klientu)
    $sql = "SELECT * FROM klientu WHERE login=?";
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "s", $login);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Якщо паролі співпадають
        if ($password == $user['password']) {
            $_SESSION['login'] = $user['login'];
            $_SESSION['id'] = $user['id'];
            $_SESSION['user_type'] = 'client'; // Позначаємо, що це клієнт
            return $user;
        }
    }
    
    mysqli_close($connection);
    return false;
}

/**
 * Перевірка чи користувач авторизований
 * 
 * @return bool
 */
function isUserLoggedIn() {
    return isset($_SESSION['id']) && !empty($_SESSION['id']);
}

/**
 * Отримати роль користувача
 * 
 * @return string
 */
function getUserRole() {
    if (!isUserLoggedIn()) {
        return null;
    }
    
    // Якщо це клієнт
    if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'client') {
        return 'client';
    }
    
    // Якщо це співробітник, перевіряємо конкретну роль
    $userId = $_SESSION['id'];
    
    switch ($userId) {
        case 1: 
            return 'manager';
        case 2: 
            return 'brigadir';
        case 3: 
            return 'admin';
        default: 
            return 'unknown'; // Невідома роль співробітника
    }
}

/**
 * Перевірка чи користувач має доступ до сторінки
 * 
 * @param array $allowedRoles Масив дозволених ролей
 * @return bool
 */
function checkAccess($allowedRoles) {
    $userRole = getUserRole();
    
    if (!$userRole) {
        return false;
    }
    
    return in_array($userRole, $allowedRoles);
}

/**
 * Отримати тип користувача (співробітник чи клієнт)
 * 
 * @return string|null
 */
function getUserType() {
    return isset($_SESSION['user_type']) ? $_SESSION['user_type'] : null;
}

/**
 * Отримати дані поточного авторизованого користувача
 * 
 * @return array|null
 */
function getCurrentUser() {
    if (!isUserLoggedIn()) {
        return null;
    }
    
    $connection = connectDatabase();
    $userId = $_SESSION['id'];
    $userType = getUserType();
    
    if ($userType === 'client') {
        // Отримуємо дані клієнта
        $sql = "SELECT * FROM klientu WHERE id = ?";
    } else {
        // Отримуємо дані співробітника
        $sql = "SELECT * FROM polzovateli WHERE id = ?";
    }
    
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Видаляємо пароль з даних користувача з міркувань безпеки
        unset($user['password']);
        
        mysqli_close($connection);
        return $user;
    }
    
    mysqli_close($connection);
    return null;
}

/**
 * Вихід з системи
 */
function logoutUser() {
    session_start();
    session_destroy();
    header("Location: index.php");
    exit;
}