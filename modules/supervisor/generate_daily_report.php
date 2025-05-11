<?php
/**
 * Генерація щоденного звіту
 */

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Підключення бібліотеки для генерації PDF
require_once '../../vendor/fpdf/fpdf.php';

// Перевірка доступу
if (!checkAccess(['brigadir'])) {
    header("Location: ../../index.php");
    exit;
}

// Перевірка наявності дати
if (!isset($_GET['date']) || empty($_GET['date'])) {
    die('Дата не вказана');
}

$date = $_GET['date'];
$shift = isset($_GET['shift']) ? $_GET['shift'] : null;

$connection = connectDatabase();

// Клас для генерації PDF
class DailyReportPDF extends FPDF
{
    // Інформація про дату та зміну
    protected $reportDate;
    protected $reportShift;
    
    function __construct($date, $shift = null)
    {
        parent::__construct();
        $this->reportDate = $date;
        $this->reportShift = $shift;
    }
    
    // Заголовок сторінки
    function Header()
    {
        // Логотип
        $this->Image('../../assets/img/logo.png', 10, 6, 30);
        // Arial жирний 15
        $this->SetFont('Arial', 'B', 15);
        // Переміщення вправо
        $this->Cell(80);
        // Заголовок
        $this->Cell(30, 10, 'ТОВ "Одеський Коровай"', 0, 0, 'C');
        // Перехід на новий рядок
        $this->Ln(10);
        $this->Cell(80);
        
        // Підзаголовок з датою та зміною
        $this->SetFont('Arial', 'B', 12);
        $reportTitle = 'Щоденний звіт за ' . date('d.m.Y', strtotime($this->reportDate));
        if ($this->reportShift) {
            $shiftText = ($this->reportShift === 'денна') ? 'Денна зміна' : 'Нічна зміна';
            $reportTitle .= ' - ' . $shiftText;
        }
        $this->Cell(30, 10, $reportTitle, 0, 0, 'C');
        
        // Відступ
        $this->Ln(20);
    }

    // Нижній колонтитул
    function Footer()
    {
        // Позиція 1.5 см від нижнього краю
        $this->SetY(-15);
        // Arial курсив 8
        $this->SetFont('Arial', 'I', 8);
        // Номер сторінки
        $this->Cell(0, 10, 'Сторінка ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

// Базовий запит на отримання замовлень за день
$ordersQuery = "SELECT z.idd, z.idklient, k.name as client_name, k.fio, z.id, p.nazvanie as product_name, 
                z.kol, z.data, z.doba, z.status, p.zena, p.ves,
                (z.kol * p.zena) as total_price, (z.kol * p.ves) as total_weight
                FROM zayavki z
                JOIN klientu k ON z.idklient = k.id
                JOIN product p ON z.id = p.id
                WHERE DATE(z.data) = ?";

$params = [$date];
$types = "s";

// Додавання фільтру по зміні, якщо вказано
if ($shift) {
    $ordersQuery .= " AND z.doba = ?";
    $params[] = $shift;
    $types .= "s";
}

// Додавання сортування
$ordersQuery .= " ORDER BY z.idd ASC";

// Підготовка та виконання запиту
$stmt = mysqli_prepare($connection, $ordersQuery);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$ordersResult = mysqli_stmt_get_result($stmt);

// Групування замовлень за продуктами для розрахунку сировини
$ingredientsQuery = "SELECT z.id, p.nazvanie, SUM(z.kol) as total_quantity
                    FROM zayavki z
                    JOIN product p ON z.id = p.id
                    WHERE DATE(z.data) = ?";

if ($shift) {
    $ingredientsQuery .= " AND z.doba = ?";
}

$ingredientsQuery .= " GROUP BY z.id ORDER BY p.nazvanie";

$ingredientsStmt = mysqli_prepare($connection, $ingredientsQuery);
mysqli_stmt_bind_param($ingredientsStmt, $types, ...$params);
mysqli_stmt_execute($ingredientsStmt);
$ingredientsResult = mysqli_stmt_get_result($ingredientsStmt);

// Розрахунок необхідної кількості сировини
$flourHighGrade = 0;
$flourFirstGrade = 0;
$flourSecondGrade = 0;
$ryeFlour = 0;
$water = 0;
$salt = 0;
$sugar = 0;
$yeast = 0;
$milk = 0;
$butter = 0;
$seeds = 0;

// Обробка кожного продукту і розрахунок сировини
$products = [];
if (mysqli_num_rows($ingredientsResult) > 0) {
    while ($product = mysqli_fetch_assoc($ingredientsResult)) {
        $products[] = $product;
        
        // Обчислення необхідної кількості сировини в залежності від продукту
        switch($product['id']) {
            // Хліб Обідній
            case 1:
                $flourHighGrade += $product['total_quantity'] * 0.4;
                $flourFirstGrade += $product['total_quantity'] * 0.3;
                $water += $product['total_quantity'] * 0.45;
                $salt += $product['total_quantity'] * 0.002;
                $sugar += $product['total_quantity'] * 0.003;
                $yeast += $product['total_quantity'] * 0.02;
                break;
                
            // Хліб Сімейний
            case 2:
                $flourHighGrade += $product['total_quantity'] * 0.6;
                $milk += $product['total_quantity'] * 0.15;
                $butter += $product['total_quantity'] * 0.05;
                $sugar += $product['total_quantity'] * 0.003;
                $water += $product['total_quantity'] * 0.2;
                $yeast += $product['total_quantity'] * 0.015;
                break;
                
            // Багет Французький
            case 3:
                $flourHighGrade += $product['total_quantity'] * 0.25;
                $water += $product['total_quantity'] * 0.15;
                $salt += $product['total_quantity'] * 0.001;
                $yeast += $product['total_quantity'] * 0.01;
                break;
                
            // Інші продукти...
            default:
                // Середні значення для інших продуктів
                $flourHighGrade += $product['total_quantity'] * 0.3;
                $water += $product['total_quantity'] * 0.2;
                $salt += $product['total_quantity'] * 0.002;
                $sugar += $product['total_quantity'] * 0.003;
                $yeast += $product['total_quantity'] * 0.015;
                break;
        }
    }
}

// Отримання кількості унікальних продуктів та загальної кількості
$statsQuery = "SELECT 
                COUNT(DISTINCT z.id) as unique_products,
                SUM(z.kol) as total_quantity,
                SUM(z.kol * p.zena) as total_amount,
                COUNT(DISTINCT z.idklient) as unique_clients
              FROM zayavki z
              JOIN product p ON z.id = p.id
              WHERE DATE(z.data) = ?";

if ($shift) {
    $statsQuery .= " AND z.doba = ?";
}

$statsStmt = mysqli_prepare($connection, $statsQuery);
mysqli_stmt_bind_param($statsStmt, $types, ...$params);
mysqli_stmt_execute($statsStmt);
$statsResult = mysqli_stmt_get_result($statsStmt);
$stats = mysqli_fetch_assoc($statsResult);

// Створення PDF документа
$pdf = new DailyReportPDF($date, $shift);
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 12);

// Додавання загальної інформації
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Загальна інформація:', 0, 1);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, 'Унікальних продуктів: ' . $stats['unique_products'], 0, 1);
$pdf->Cell(0, 10, 'Загальна кількість: ' . $stats['total_quantity'], 0, 1);
$pdf->Cell(0, 10, 'Загальна сума: ' . number_format($stats['total_amount'], 2) . ' грн', 0, 1);
$pdf->Cell(0, 10, 'Унікальних клієнтів: ' . $stats['unique_clients'], 0, 1);
$pdf->Ln(5);

// Додавання інформації про необхідну сировину
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Необхідна сировина:', 0, 1);
$pdf->SetFont('Arial', '', 12);

if ($flourHighGrade > 0) {
    $pdf->Cell(0, 10, 'Мука вищого ґатунку: ' . number_format($flourHighGrade, 2) . ' кг', 0, 1);
}
if ($flourFirstGrade > 0) {
    $pdf->Cell(0, 10, 'Мука першого ґатунку: ' . number_format($flourFirstGrade, 2) . ' кг', 0, 1);
}
if ($water > 0) {
    $pdf->Cell(0, 10, 'Вода: ' . number_format($water, 2) . ' л', 0, 1);
}
if ($salt > 0) {
    $pdf->Cell(0, 10, 'Сіль: ' . number_format($salt, 2) . ' кг', 0, 1);
}
if ($sugar > 0) {
    $pdf->Cell(0, 10, 'Цукор: ' . number_format($sugar, 2) . ' кг', 0, 1);
}
if ($yeast > 0) {
    $pdf->Cell(0, 10, 'Дріжджі: ' . number_format($yeast, 2) . ' кг', 0, 1);
}
if ($milk > 0) {
    $pdf->Cell(0, 10, 'Молоко: ' . number_format($milk, 2) . ' л', 0, 1);
}
if ($butter > 0) {
    $pdf->Cell(0, 10, 'Масло: ' . number_format($butter, 2) . ' кг', 0, 1);
}

$pdf->Ln(5);

// Таблиця продуктів для виробництва
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Список продуктів для виробництва:', 0, 1);

// Заголовки таблиці
$pdf->SetFillColor(200, 220, 255);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(20, 7, 'ID', 1, 0, 'C', true);
$pdf->Cell(120, 7, 'Назва продукту', 1, 0, 'C', true);
$pdf->Cell(50, 7, 'Загальна кількість', 1, 1, 'C', true);

// Дані таблиці
$pdf->SetFont('Arial', '', 10);
mysqli_data_seek($ingredientsResult, 0);
if (mysqli_num_rows($ingredientsResult) > 0) {
    while ($product = mysqli_fetch_assoc($ingredientsResult)) {
        $pdf->Cell(20, 6, $product['id'], 1, 0, 'C');
        $pdf->Cell(120, 6, $product['nazvanie'], 1, 0, 'L');
        $pdf->Cell(50, 6, $product['total_quantity'], 1, 1, 'C');
    }
} else {
    $pdf->Cell(190, 6, 'Немає продуктів для виробництва', 1, 1, 'C');
}

$pdf->Ln(5);

// Детальний список замовлень
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Детальний список замовлень:', 0, 1);

// Заголовки таблиці
$pdf->SetFillColor(200, 220, 255);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(15, 7, 'ID', 1, 0, 'C', true);
$pdf->Cell(55, 7, 'Клієнт', 1, 0, 'C', true);
$pdf->Cell(55, 7, 'Продукт', 1, 0, 'C', true);
$pdf->Cell(20, 7, 'Кількість', 1, 0, 'C', true);
$pdf->Cell(20, 7, 'Сума', 1, 0, 'C', true);
$pdf->Cell(25, 7, 'Зміна', 1, 1, 'C', true);

// Дані таблиці
$pdf->SetFont('Arial', '', 10);
mysqli_data_seek($ordersResult, 0);
if (mysqli_num_rows($ordersResult) > 0) {
    while ($order = mysqli_fetch_assoc($ordersResult)) {
        $pdf->Cell(15, 6, $order['idd'], 1, 0, 'C');
        $pdf->Cell(55, 6, substr($order['client_name'], 0, 24), 1, 0, 'L');
        $pdf->Cell(55, 6, substr($order['product_name'], 0, 24), 1, 0, 'L');
        $pdf->Cell(20, 6, $order['kol'], 1, 0, 'C');
        $pdf->Cell(20, 6, number_format($order['total_price'], 2), 1, 0, 'R');
        $pdf->Cell(25, 6, ($order['doba'] === 'денна') ? 'Денна' : 'Нічна', 1, 1, 'C');
    }
} else {
    $pdf->Cell(190, 6, 'Немає замовлень за вказану дату', 1, 1, 'C');
}

// Додавання підписів
$pdf->Ln(15);
$pdf->Cell(95, 10, 'Підпис бригадира: __________________', 0, 0, 'L');
$pdf->Cell(95, 10, 'Дата: ' . date('d.m.Y'), 0, 1, 'R');

// Закриття підключення до БД
mysqli_close($connection);

// Вивід PDF
$reportName = 'Щоденний_звіт_' . date('Y-m-d', strtotime($date));
if ($shift) {
    $reportName .= '_' . $shift;
}
$reportName .= '.pdf';

$pdf->Output('D', $reportName);