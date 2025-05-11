<?php 
$pageTitle = 'Експорт списку клієнтів';

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Перевірка доступу
if (!checkAccess(['admin'])) {
    header("Location: ../../index.php");
    exit;
}

$connection = connectDatabase();

// Отримання списку клієнтів
$query = "SELECT id, name, fio, dolj, tel, mail, city, adres, rast FROM klientu ORDER BY name";
$result = mysqli_query($connection, $query);

// Запис в журнал
logAction($connection, 'Експорт клієнтів', 'Експортовано список клієнтів в Excel');

// Формування Excel-файлу
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="clients_' . date('Y-m-d') . '.xls"');

echo '<table border="1">';
echo '<tr>';
echo '<th>ID</th>';
echo '<th>Назва компанії</th>';
echo '<th>ПІБ контактної особи</th>';
echo '<th>Посада</th>';
echo '<th>Телефон</th>';
echo '<th>Email</th>';
echo '<th>Місто</th>';
echo '<th>Адреса</th>';
echo '<th>Відстань (км)</th>';
echo '</tr>';

while ($client = mysqli_fetch_assoc($result)) {
    echo '<tr>';
    echo '<td>' . $client['id'] . '</td>';
    echo '<td>' . htmlspecialchars($client['name']) . '</td>';
    echo '<td>' . htmlspecialchars($client['fio']) . '</td>';
    echo '<td>' . htmlspecialchars($client['dolj']) . '</td>';
    echo '<td>' . htmlspecialchars($client['tel']) . '</td>';
    echo '<td>' . htmlspecialchars($client['mail']) . '</td>';
    echo '<td>' . htmlspecialchars($client['city']) . '</td>';
    echo '<td>' . htmlspecialchars($client['adres']) . '</td>';
    echo '<td>' . $client['rast'] . '</td>';
    echo '</tr>';
}

echo '</table>';
exit;