<?php 
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Перевірка доступу
if (!checkAccess(['admin'])) {
    header("Location: ../../index.php");
    exit;
}

// Перевірка наявності імені файлу
if (!isset($_GET['file']) || empty($_GET['file'])) {
    header("Location: backup.php");
    exit;
}

$fileName = basename($_GET['file']);
$filePath = '../../backups/' . $fileName;

// Перевірка існування файлу
if (!file_exists($filePath)) {
    header("Location: backup.php?error=" . urlencode('Файл не знайдено'));
    exit;
}

// Перевірка розширення файлу (безпека)
$fileExt = pathinfo($filePath, PATHINFO_EXTENSION);
if ($fileExt !== 'sql') {
    header("Location: backup.php?error=" . urlencode('Невірний тип файлу'));
    exit;
}

// Запис в журнал
$connection = connectDatabase();
logAction($connection, 'Завантаження резервної копії', 'Завантажено резервну копію: ' . $fileName);

// Налаштування заголовків для завантаження
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;