<?php
/**
 * Експорт замовлень в PDF
 */

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Підключення бібліотеки для генерації PDF
// В реальному проекті необхідно встановити FPDF через Composer
require_once '../../vendor/fpdf/fpdf.php';

// Перевірка доступу
if (!checkAccess(['brigadir'])) {
    header("Location: ../../index.php");
    exit;
}

// Отримання параметрів фільтрації з URL
$client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : null;
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : null;
$doba = isset($_GET['doba']) ? $_GET['doba'] : null;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : null;
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : null;
$status = isset($_GET['status']) ? $_GET['status'] : null;

// Підключення до БД
$connection = connectDatabase();

// Клас для генерації PDF
class OrdersPDF extends FPDF
{
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
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(30, 10, 'Звіт замовлень за ' . date('d.m.Y'), 0, 0, 'C');
        // Відступ
        $this->Ln(20);
        
        // Заголовки таблиці
        $this->SetFillColor(200, 220, 255);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(15, 7, 'ID', 1, 0, 'C', true);
        $this->Cell(40, 7, 'Клієнт', 1, 0, 'C', true);
        $this->Cell(40, 7, 'Продукт', 1, 0, 'C', true);
        $this->Cell(15, 7, 'К-сть', 1, 0, 'C', true);
        $this->Cell(25, 7, 'Сума (грн)', 1, 0, 'C', true);
        $this->Cell(25, 7, 'Дата', 1, 0, 'C', true);
        $this->Cell(15, 7, 'Зміна', 1, 0, 'C', true);
        $this->Cell(20, 7, 'Статус', 1, 0, 'C', true);
        $this->Ln();
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

// Базовий запит
$query = "SELECT z.idd, z.idklient, k.name as client_name, k.fio, z.id, p.nazvanie as product_name, 
                z.kol, z.data, z.doba, z.status, p.zena, (z.kol * p.zena) as total_price
          FROM zayavki z
          JOIN klientu k ON z.idklient = k.id
          JOIN product p ON z.id = p.id
          WHERE 1=1";

// Додавання умов фільтрації
$params = [];
$types = '';

if ($client_id !== null) {
    $query .= " AND z.idklient = ?";
    $params[] = $client_id;
    $types .= 'i';
}

if ($product_id !== null) {
    $query .= " AND z.id = ?";
    $params[] = $product_id;
    $types .= 'i';
}

if ($doba !== null) {
    $query .= " AND z.doba = ?";
    $params[] = $doba;
    $types .= 's';
}

if ($status !== null) {
    $query .= " AND z.status = ?";
    $params[] = $status;
    $types .= 's';
}

if (!empty($date_from)) {
    $query .= " AND z.data >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if (!empty($date_to)) {
    $query .= " AND z.data <= ?";
    $params[] = $date_to;
    $types .= 's';
}

// Додавання сортування
$query .= " ORDER BY z.data DESC, z.idd DESC";

// Підготовка та виконання запиту
$stmt = mysqli_prepare($connection, $query);

if (!empty($params)) {
    $bindParams = array_merge([$stmt, $types], $params);
    call_user_func_array('mysqli_stmt_bind_param', $bindParams);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Створення PDF документа
$pdf = new OrdersPDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 10);

// Додавання рядків до таблиці
$fill = false;
while ($row = mysqli_fetch_assoc($result)) {
    // Перетворення статусу
    switch ($row['status']) {
        case 'нове':
            $status = 'Нове';
            break;
        case 'в обробці':
            $status = 'В обробці';
            break;
        case 'виконано':
            $status = 'Виконано';
            break;
        case 'скасовано':
            $status = 'Скасовано';
            break;
        default:
            $status = 'Невідомо';
            break;
    }
    
    // Форматування значень
    $date = date('d.m.Y', strtotime($row['data']));
    $doba = ($row['doba'] === 'денна') ? 'Денна' : 'Нічна';
    
    // Додавання рядка в таблицю
    $pdf->Cell(15, 6, $row['idd'], 1, 0, 'C', $fill);
    $pdf->Cell(40, 6, substr($row['client_name'], 0, 20), 1, 0, 'L', $fill);
    $pdf->Cell(40, 6, substr($row['product_name'], 0, 20), 1, 0, 'L', $fill);
    $pdf->Cell(15, 6, $row['kol'], 1, 0, 'C', $fill);
    $pdf->Cell(25, 6, number_format($row['total_price'], 2, '.', ' '), 1, 0, 'R', $fill);
    $pdf->Cell(25, 6, $date, 1, 0, 'C', $fill);
    $pdf->Cell(15, 6, $doba, 1, 0, 'C', $fill);
    $pdf->Cell(20, 6, $status, 1, 0, 'C', $fill);
    $pdf->Ln();
    
    // Чергування кольору рядків
    $fill = !$fill;
}

// Отримання загальної статистики
$statsQuery = "SELECT 
                COUNT(z.idd) as total_orders,
                SUM(z.kol) as total_quantity,
                SUM(z.kol * p.zena) as total_amount
              FROM zayavki z
              JOIN product p ON z.id = p.id
              WHERE 1=1";

// Додавання умов фільтрації до запиту статистики
if (!empty($params)) {
    $statsQuery .= " AND " . substr($query, strpos($query, "WHERE 1=1") + 9, 
                                    strpos($query, "ORDER BY") - strpos($query, "WHERE 1=1") - 9);
    
    $stmtStats = mysqli_prepare($connection, $statsQuery);
    
    $bindParams = array_merge([$stmtStats, $types], $params);
    call_user_func_array('mysqli_stmt_bind_param', $bindParams);
    
    mysqli_stmt_execute($stmtStats);
    $statsResult = mysqli_stmt_get_result($stmtStats);
    $stats = mysqli_fetch_assoc($statsResult);
} else {
    $statsResult = mysqli_query($connection, $statsQuery);
    $stats = mysqli_fetch_assoc($statsResult);
}

// Додавання підсумків
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(95, 7, 'Загальна кількість замовлень: ' . $stats['total_orders'], 0, 0, 'L');
$pdf->Cell(100, 7, 'Загальна кількість одиниць: ' . $stats['total_quantity'], 0, 1, 'L');
$pdf->Cell(195, 7, 'Загальна сума: ' . number_format($stats['total_amount'], 2, '.', ' ') . ' грн', 0, 1, 'L');

// Додавання фільтрів, які були застосовані
$pdf->Ln(10);
$pdf->SetFont('Arial', 'I', 9);
$pdf->Cell(0, 7, 'Застосовані фільтри:', 0, 1);

if ($client_id !== null) {
    // Отримання імені клієнта
    $clientNameQuery = "SELECT name FROM klientu WHERE id = ?";
    $clientStmt = mysqli_prepare($connection, $clientNameQuery);
    mysqli_stmt_bind_param($clientStmt, "i", $client_id);
    mysqli_stmt_execute($clientStmt);
    $clientResult = mysqli_stmt_get_result($clientStmt);
    $clientName = mysqli_fetch_assoc($clientResult)['name'];
    
    $pdf->Cell(0, 7, 'Клієнт: ' . $clientName, 0, 1);
}

if ($product_id !== null) {
    // Отримання назви продукту
    $productNameQuery = "SELECT nazvanie FROM product WHERE id = ?";
    $productStmt = mysqli_prepare($connection, $productNameQuery);
    mysqli_stmt_bind_param($productStmt, "i", $product_id);
    mysqli_stmt_execute($productStmt);
    $productResult = mysqli_stmt_get_result($productStmt);
    $productName = mysqli_fetch_assoc($productResult)['nazvanie'];
    
    $pdf->Cell(0, 7, 'Продукт: ' . $productName, 0, 1);
}

if ($doba !== null) {
    $pdf->Cell(0, 7, 'Зміна: ' . ($doba === 'денна' ? 'Денна' : 'Нічна'), 0, 1);
}

if ($status !== null) {
    $pdf->Cell(0, 7, 'Статус: ' . $status, 0, 1);
}

if (!empty($date_from) && !empty($date_to)) {
    $pdf->Cell(0, 7, 'Період: з ' . date('d.m.Y', strtotime($date_from)) . ' по ' . date('d.m.Y', strtotime($date_to)), 0, 1);
} elseif (!empty($date_from)) {
    $pdf->Cell(0, 7, 'Період: з ' . date('d.m.Y', strtotime($date_from)), 0, 1);
} elseif (!empty($date_to)) {
    $pdf->Cell(0, 7, 'Період: по ' . date('d.m.Y', strtotime($date_to)), 0, 1);
}

// Додавання підписів
$pdf->Ln(15);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(95, 10, 'Підпис бригадира: __________________', 0, 0, 'L');
$pdf->Cell(95, 10, 'Дата формування: ' . date('d.m.Y H:i:s'), 0, 1, 'R');

// Закриття підключення до БД
mysqli_close($connection);

// Вивід PDF
$pdf->Output('D', 'Orders_' . date('Y-m-d') . '.pdf');