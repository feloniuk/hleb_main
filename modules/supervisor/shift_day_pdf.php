<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Перевірка доступу
if (!checkAccess(['brigadir'])) {
    header("Location: ../../index.php");
    exit;
}

// Підключення бібліотеки для генерації PDF
require_once '../../assets/vendor/autoload.php';

$connection = connectDatabase();

// Клас для генерації PDF з підтримкою кирилиці
class PDF extends tFPDF
{
    // Заголовок сторінки
    function Header()
    {
        // Логотип
        $this->Image('../../assets/img/logo.png', 10, 6, 30);
        // DejaVu - шрифт із підтримкою UTF-8
        $this->AddFont('DejaVu', '', 'DejaVuSans.ttf', true);
        $this->AddFont('DejaVu', 'B', 'DejaVuSans-Bold.ttf', true);
        // Шрифт
        $this->SetFont('DejaVu', 'B', 15);
        // Переміщення вправо
        $this->Cell(80);
        // Заголовок
        $this->Cell(30, 10, 'ТОВ "Одеський Коровай"', 0, 0, 'C');
        // Підзаголовок
        $this->Ln(10);
        $this->Cell(80);
        $this->SetFont('DejaVu', 'B', 12);
        $this->Cell(30, 10, 'Звіт по денній зміні на ' . date('d.m.Y'), 0, 0, 'C');
        // Відступ
        $this->Ln(20);
    }

    // Нижній колонтитул
    function Footer()
    {
        // Позиція 1.5 см від нижнього краю
        $this->SetY(-15);
        // DejaVu курсив
        $this->SetFont('DejaVu', '', 8);
        // Номер сторінки
        $this->Cell(0, 10, 'Сторінка ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

// Створення PDF документа
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('DejaVu', '', 12);

// Отримання замовлень денної зміни
$ordersQuery = "SELECT z.idd, z.idklient, k.name as client_name, z.id, p.nazvanie as product_name, 
                z.kol, z.data, z.doba
                FROM zayavki z
                JOIN klientu k ON z.idklient = k.id
                JOIN product p ON z.id = p.id
                WHERE z.doba='денна' AND DATE(z.data) = CURDATE()
                ORDER BY z.idd DESC";
$ordersResult = mysqli_query($connection, $ordersQuery);

// Групування замовлень за продуктами
$ingredientsQuery = "SELECT z.id, p.nazvanie, SUM(z.kol) as total_quantity
                    FROM zayavki z
                    JOIN product p ON z.id = p.id
                    WHERE z.doba='денна' AND DATE(z.data) = CURDATE()
                    GROUP BY z.id
                    ORDER BY p.nazvanie";
$ingredientsResult = mysqli_query($connection, $ingredientsQuery);

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
if (mysqli_num_rows($ingredientsResult) > 0) {
    while ($product = mysqli_fetch_assoc($ingredientsResult)) {
        // Обчислення необхідної кількості сировини в залежності від продукту
        switch($product['id']) {
            case 1:
                $flourHighGrade += $product['total_quantity'] * 0.4;
                $flourFirstGrade += $product['total_quantity'] * 0.3;
                $water += $product['total_quantity'] * 0.45;
                $salt += $product['total_quantity'] * 0.002;
                $sugar += $product['total_quantity'] * 0.003;
                $yeast += $product['total_quantity'] * 0.02;
                break;
                
            case 2:
                $flourHighGrade += $product['total_quantity'] * 0.6;
                $milk += $product['total_quantity'] * 0.15;
                $butter += $product['total_quantity'] * 0.05;
                $sugar += $product['total_quantity'] * 0.003;
                $water += $product['total_quantity'] * 0.2;
                $yeast += $product['total_quantity'] * 0.015;
                break;
                
            case 3:
                $flourHighGrade += $product['total_quantity'] * 0.25;
                $water += $product['total_quantity'] * 0.15;
                $salt += $product['total_quantity'] * 0.001;
                $yeast += $product['total_quantity'] * 0.01;
                break;
                
            default:
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
$uniqueProductsQuery = "SELECT COUNT(DISTINCT id) as count FROM zayavki WHERE doba='денна' AND DATE(data) = CURDATE()";
$uniqueProductsResult = mysqli_query($connection, $uniqueProductsQuery);
$uniqueProductsCount = mysqli_fetch_assoc($uniqueProductsResult)['count'];

$totalQuantityQuery = "SELECT SUM(kol) as total FROM zayavki WHERE doba='денна' AND DATE(data) = CURDATE()";
$totalQuantityResult = mysqli_query($connection, $totalQuantityQuery);
$totalQuantity = mysqli_fetch_assoc($totalQuantityResult)['total'];

// Додавання загальної інформації
$pdf->SetFont('DejaVu', 'B', 12);
$pdf->Cell(0, 10, 'Загальна інформація:', 0, 1);
$pdf->SetFont('DejaVu', '', 12);
$pdf->Cell(0, 10, 'Унікальних продуктів: ' . $uniqueProductsCount, 0, 1);
$pdf->Cell(0, 10, 'Загальна кількість: ' . $totalQuantity, 0, 1);
$pdf->Ln(5);

// Додавання інформації про необхідну сировину
$pdf->SetFont('DejaVu', 'B', 12);
$pdf->Cell(0, 10, 'Необхідна сировина:', 0, 1);
$pdf->SetFont('DejaVu', '', 12);

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
$pdf->SetFont('DejaVu', 'B', 12);
$pdf->Cell(0, 10, 'Список продуктів для виробництва:', 0, 1);

// Заголовки таблиці
$pdf->SetFillColor(200, 220, 255);
$pdf->SetFont('DejaVu', 'B', 10);
$pdf->Cell(20, 7, 'ID', 1, 0, 'C', true);
$pdf->Cell(120, 7, 'Назва продукту', 1, 0, 'C', true);
$pdf->Cell(50, 7, 'Загальна кількість', 1, 1, 'C', true);

// Дані таблиці
$pdf->SetFont('DejaVu', '', 10);
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
$pdf->SetFont('DejaVu', 'B', 12);
$pdf->Cell(0, 10, 'Детальний список замовлень:', 0, 1);

// Заголовки таблиці
$pdf->SetFillColor(200, 220, 255);
$pdf->SetFont('DejaVu', 'B', 10);
$pdf->Cell(20, 7, 'ID', 1, 0, 'C', true);
$pdf->Cell(60, 7, 'Клієнт', 1, 0, 'C', true);
$pdf->Cell(60, 7, 'Продукт', 1, 0, 'C', true);
$pdf->Cell(30, 7, 'Кількість', 1, 0, 'C', true);
$pdf->Cell(20, 7, 'Дата', 1, 1, 'C', true);

// Дані таблиці
$pdf->SetFont('DejaVu', '', 10);
mysqli_data_seek($ordersResult, 0);
if (mysqli_num_rows($ordersResult) > 0) {
    while ($order = mysqli_fetch_assoc($ordersResult)) {
        $pdf->Cell(20, 6, $order['idd'], 1, 0, 'C');
        $pdf->Cell(60, 6, $order['client_name'], 1, 0, 'L');
        $pdf->Cell(60, 6, $order['product_name'], 1, 0, 'L');
        $pdf->Cell(30, 6, $order['kol'], 1, 0, 'C');
        $pdf->Cell(20, 6, date('d.m.Y', strtotime($order['data'])), 1, 1, 'C');
    }
} else {
    $pdf->Cell(190, 6, 'Немає замовлень на денну зміну', 1, 1, 'C');
}

// Додавання підписів
$pdf->Ln(15);
$pdf->Cell(95, 10, 'Підпис бригадира: __________________', 0, 0, 'L');
$pdf->Cell(95, 10, 'Підпис технолога: __________________', 0, 1, 'R');

// Вивід PDF
$pdf->Output('D', 'Денна_зміна_' . date('Y-m-d') . '.pdf');