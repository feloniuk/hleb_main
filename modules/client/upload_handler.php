<?php
/**
 * Обробник завантаження файлів
 * 
 * Цей файл використовується для завантаження файлів від клієнта,
 * наприклад, для прикріплення документів до замовлень.
 */

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Перевірка доступу
if (!checkAccess(['client'])) {
    header("Location: ../../index.php");
    exit;
}

// Перевірка методу запиту
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit(json_encode([
        'success' => false,
        'message' => 'Неправильний метод запиту'
    ]));
}

// Перевірка наявності файлу
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    exit(json_encode([
        'success' => false,
        'message' => 'Помилка завантаження файлу: ' . getUploadErrorMessage($_FILES['file']['error'])
    ]));
}

// Перевірка типу файлу
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
$fileType = $_FILES['file']['type'];

if (!in_array($fileType, $allowedTypes)) {
    exit(json_encode([
        'success' => false,
        'message' => 'Недопустимий тип файлу. Дозволені типи: JPEG, PNG, GIF, PDF, DOC, DOCX'
    ]));
}

// Перевірка розміру файлу (максимум 5 МБ)
$maxFileSize = 5 * 1024 * 1024; // 5 MB
if ($_FILES['file']['size'] > $maxFileSize) {
    exit(json_encode([
        'success' => false,
        'message' => 'Файл занадто великий. Максимальний розмір: 5 МБ'
    ]));
}

// Створення директорії для зберігання файлів, якщо вона не існує
$uploadDirectory = '../../uploads/client_files/';
if (!file_exists($uploadDirectory)) {
    mkdir($uploadDirectory, 0755, true);
}

// Генерація унікального імені файлу
$filename = uniqid() . '_' . basename($_FILES['file']['name']);
$targetPath = $uploadDirectory . $filename;

// Переміщення файлу
if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
    // Збереження інформації про файл у базі даних, якщо потрібно
    $clientId = $_SESSION['id'];
    $fileUrl = 'uploads/client_files/' . $filename;
    $uploadDate = date('Y-m-d H:i:s');
    
    $connection = connectDatabase();
    
    // Тут код для збереження інформації про файл у БД
    // Для прикладу, ми припускаємо, що є таблиця client_files
    /*
    $query = "INSERT INTO client_files (client_id, file_name, file_path, file_type, upload_date) 
              VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "issss", $clientId, $filename, $fileUrl, $fileType, $uploadDate);
    mysqli_stmt_execute($stmt);
    */
    
    exit(json_encode([
        'success' => true,
        'message' => 'Файл успішно завантажено',
        'fileName' => $filename,
        'fileUrl' => $fileUrl
    ]));
} else {
    exit(json_encode([
        'success' => false,
        'message' => 'Помилка при переміщенні файлу'
    ]));
}

/**
 * Отримати текстове повідомлення про помилку завантаження файлу
 * 
 * @param int $errorCode Код помилки
 * @return string Текстове повідомлення
 */
function getUploadErrorMessage($errorCode) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE => 'Файл перевищує максимальний розмір, визначений у php.ini',
        UPLOAD_ERR_FORM_SIZE => 'Файл перевищує максимальний розмір, визначений у формі',
        UPLOAD_ERR_PARTIAL => 'Файл був завантажений лише частково',
        UPLOAD_ERR_NO_FILE => 'Файл не був завантажений',
        UPLOAD_ERR_NO_TMP_DIR => 'Відсутня тимчасова папка',
        UPLOAD_ERR_CANT_WRITE => 'Не вдалося записати файл на диск',
        UPLOAD_ERR_EXTENSION => 'Завантаження файлу було зупинено розширенням PHP'
    ];
    
    return isset($errorMessages[$errorCode]) ? $errorMessages[$errorCode] : 'Невідома помилка';
}