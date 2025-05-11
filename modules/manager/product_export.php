<?php
$pageTitle = 'Експорт продукції';

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
$fileName = 'products_export_' . date('Y-m-d') . '.pdf';

// Перевірка наявності бібліотеки FPDF (використовуємо альтернативний підхід)
if (!file_exists('../../vendor/fpdf/fpdf.php')) {
    // Якщо FPDF не встановлено, виконуємо експорт в CSV
    $fileName = 'products_export_' . date('Y-m-d') . '.csv';
    
    // Встановлення заголовків для завантаження файлу
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    
    // Створення файлового потоку на вивід
    $output = fopen('php://output', 'w');
    
    // Додавання BOM для правильного відображення кирилиці в Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Додавання заголовків для стовпців CSV
    fputcsv($output, ['ID', 'Назва продукту', 'Вага (кг)', 'Строк реалізації (год)', 'Собівартість (грн)', 'Ціна (грн)', 'Прибуток (грн)']);
    
    // Запит для отримання продуктів
    $query = "SELECT * FROM product ORDER BY nazvanie ASC";
    $result = mysqli_query($connection, $query);
    
    // Запис даних у CSV
    while ($row = mysqli_fetch_assoc($result)) {
        // Розрахунок прибутку
        $profit = $row['zena'] - $row['stoimost'];
        
        // Підготовка даних для CSV-рядка
        $csvRow = [
            $row['id'],
            $row['nazvanie'],
            $row['ves'],
            $row['srok'],
            $row['stoimost'],
            $row['zena'],
            $profit
        ];
        
        // Запис рядка у файл
        fputcsv($output, $csvRow);
    }
    
    // Закриття файлового потоку
    fclose($output);
    
    // Завершення скрипту
    exit;
}

// Якщо FPDF встановлено, генеруємо PDF (цей код буде виконуватися тільки якщо бібліотека існує)
require_once('../../vendor/fpdf/fpdf.php');

class PDF extends FPDF {
    // Заголовок сторінки
    function Header() {
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, 'Каталог продукції "Одеський Коровай"', 0, 1, 'C');
        $this->SetFont('Arial', 'I', 10);
        $this->Cell(0, 6, 'Дата формування: ' . date('d.m.Y'), 0, 1, 'C');
        $this->Ln(5);
    }
    
    // Нижній колонтитул сторінки
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Сторінка ' . $this->PageNo(), 0, 0, 'C');
    }
}

// Створення екземпляра PDF
$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 10);

// Заголовки таблиці
$pdf->SetFillColor(200, 200, 200);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(10, 7, 'ID', 1, 0, 'C', true);
$pdf->Cell(70, 7, 'Назва продукту', 1, 0, 'C', true);
$pdf->Cell(20, 7, 'Вага (кг)', 1, 0, 'C', true);
$pdf->Cell(20, 7, 'Строк (год)', 1, 0, 'C', true);
$pdf->Cell(25, 7, 'Собівартість', 1, 0, 'C', true);
$pdf->Cell(20, 7, 'Ціна', 1, 0, 'C', true);
$pdf->Cell(25, 7, 'Прибуток', 1, 1, 'C', true);

// Запит для отримання продуктів
$query = "SELECT * FROM product ORDER BY nazvanie ASC";
$result = mysqli_query($connection, $query);

// Заповнення таблиці
$pdf->SetFont('Arial', '', 10);
$fill = false;

while ($row = mysqli_fetch_assoc($result)) {
    // Розрахунок прибутку
    $profit = $row['zena'] - $row['stoimost'];
    
    $pdf->Cell(10, 6, $row['id'], 1, 0, 'C', $fill);
    $pdf->Cell(70, 6, $row['nazvanie'], 1, 0, 'L', $fill);
    $pdf->Cell(20, 6, $row['ves'], 1, 0, 'C', $fill);
    $pdf->Cell(20, 6, $row['srok'], 1, 0, 'C', $fill);
    $pdf->Cell(25, 6, $row['stoimost'] . ' грн', 1, 0, 'R', $fill);
    $pdf->Cell(20, 6, $row['zena'] . ' грн', 1, 0, 'R', $fill);
    $pdf->Cell(25, 6, number_format($profit, 2) . ' грн', 1, 1, 'R', $fill);
    
    $fill = !$fill; // Чергування кольору рядків
}

// Вивід PDF
$pdf->Output('D', $fileName);
?>