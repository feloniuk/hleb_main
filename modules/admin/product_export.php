<?php 
$pageTitle = 'Експорт каталогу продукції';

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Перевірка доступу
if (!checkAccess(['admin'])) {
    header("Location: ../../index.php");
    exit;
}

$connection = connectDatabase();

// Отримання списку продуктів
$query = "SELECT id, nazvanie, ves, srok, stoimost, zena FROM product ORDER BY nazvanie";
$result = mysqli_query($connection, $query);

// Запис в журнал
logAction($connection, 'Експорт продукції', 'Експортовано каталог продукції в Excel');

// Формування Excel-файлу
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="products_' . date('Y-m-d') . '.xls"');

echo '<table border="1">';
echo '<tr>';
echo '<th>ID</th>';
echo '<th>Назва продукту</th>';
echo '<th>Вага (кг)</th>';
echo '<th>Строк реалізації (год)</th>';
echo '<th>Собівартість (грн)</th>';
echo '<th>Ціна (грн)</th>';
echo '<th>Прибуток (грн)</th>';
echo '<th>Маржа (%)</th>';
echo '</tr>';

while ($product = mysqli_fetch_assoc($result)) {
    $profit = $product['zena'] - $product['stoimost'];
    $margin = ($product['stoimost'] > 0) ? ($profit / $product['stoimost'] * 100) : 0;
    
    echo '<tr>';
    echo '<td>' . $product['id'] . '</td>';
    echo '<td>' . htmlspecialchars($product['nazvanie']) . '</td>';
    echo '<td>' . $product['ves'] . '</td>';
    echo '<td>' . $product['srok'] . '</td>';
    echo '<td>' . number_format($product['stoimost'], 2) . '</td>';
    echo '<td>' . number_format($product['zena'], 2) . '</td>';
    echo '<td>' . number_format($profit, 2) . '</td>';
    echo '<td>' . number_format($margin, 2) . '%</td>';
    echo '</tr>';
}

echo '</table>';
exit;