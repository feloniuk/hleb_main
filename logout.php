<?php
require_once 'includes/auth.php';

// Знищення сесії
logoutUser();

// Перенаправлення на сторінку входу
header("Location: index.php");
exit;
?>