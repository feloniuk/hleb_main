<?php 
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Перевірка доступу
if (!checkAccess(['admin'])) {
    header("Location: ../../index.php");
    exit;
}

// Створення CSV-файлу
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="product_import_template.csv"');

// Створення файлового потоку
$output = fopen('php://output', 'w');

// Додавання BOM для підтримки UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Запис заголовків колонок
fputcsv($output, ['Назва', 'Вага (кг)', 'Строк реалізації (год)', 'Собівартість (грн)', 'Ціна (грн)']);

// Додавання прикладів
fputcsv($output, ['Хліб Обідній', '0.5', '48', '12.50', '18.00']);
fputcsv($output, ['Багет Французький', '0.3', '24', '10.80', '16.00']);
fputcsv($output, ['Булочки з маком', '0.1', '36', '4.50', '7.50']);

// Закриття файлового потоку
fclose($output);
exit;