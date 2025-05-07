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
    
    $userId = $_SESSION['id'];
    
    switch ($userId) {
        case 1: 
            return 'manager';
        case 2: 
            return 'brigadir';
        case 3: 
            return 'admin';
        default: 
            return 'client';
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
 * Вихід з системи
 */
function logoutUser() {
    session_start();
    session_destroy();
    header("Location: index.php");
    exit;
}