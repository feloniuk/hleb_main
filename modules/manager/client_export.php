<?php
$pageTitle = 'Експорт клієнтів';

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Перевірка доступу
if (!checkAccess(['manager'])) {
    header("Location: ../../index.php");
    exit;
}

$connection = connectDatabase();

// Назва файлу для експорту
$fileName = 'clients_export_' . date('Y-m-d') . '.xlsx';

// Так як PHPSpreadsheet може бути не встановлена, виконуємо експорт в CSV
$fileName = 'clients_export_' . date('Y-m-d') . '.csv';

// Встановлення заголовків для завантаження файлу
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $fileName . '"');

// Створення файлового потоку на вивід
$output = fopen('php://output', 'w');

// Додавання BOM для правильного відображення кирилиці в Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Додавання заголовків для стовпців CSV
fputcsv($output, ['ID', 'Назва компанії', 'Контактна особа', 'Посада', 'Телефон', 'Email', 'Місто', 'Адреса', 'Відстань (км)', 'Кількість замовлень', 'Загальна сума замовлень (грн)']);

// Запит для отримання клієнтів
$query = "SELECT k.* FROM klientu k ORDER BY k.name ASC";
$result = mysqli_query($connection, $query);

// Запис даних у CSV
while ($row = mysqli_fetch_assoc($result)) {
    // Отримання статистики по замовленнях клієнта
    $statsQuery = "
        SELECT COUNT(z.idd) as order_count, SUM(z.kol * p.zena) as total_sum
        FROM zayavki z
        JOIN product p ON z.id = p.id
        WHERE z.idklient = ?
    ";
    $stmt = mysqli_prepare($connection, $statsQuery);
    mysqli_stmt_bind_param($stmt, "i", $row['id']);
    mysqli_stmt_execute($stmt);
    $statsResult = mysqli_stmt_get_result($stmt);
    $stats = mysqli_fetch_assoc($statsResult);
    
    // Підготовка даних для CSV-рядка
    $csvRow = [
        $row['id'],
        $row['name'],
        $row['fio'],
        $row['dolj'],
        $row['tel'],
        $row['mail'],
        $row['city'],
        $row['adres'],
        $row['rast'],
        $stats['order_count'] ?? 0,
        number_format($stats['total_sum'] ?? 0, 2)
    ];
    
    // Запис рядка у файл
    fputcsv($output, $csvRow);
}

// Закриття файлового потоку
fclose($output);

// Завершення скрипту
exit;
?>