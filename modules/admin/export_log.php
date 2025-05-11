<?php 
$pageTitle = 'Експорт системного журналу';

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Перевірка доступу
if (!checkAccess(['admin'])) {
    header("Location: ../../index.php");
    exit;
}

$connection = connectDatabase();

// Перевірка наявності таблиці журналу
$tableExistsQuery = "SHOW TABLES LIKE 'system_log'";
$tableExistsResult = mysqli_query($connection, $tableExistsQuery);

if (mysqli_num_rows($tableExistsResult) == 0) {
    header("Location: create_system_log.php");
    exit;
}

// Отримання параметрів фільтрації з URL
$filterLevel = isset($_GET['level']) ? $_GET['level'] : '';
$filterAction = isset($_GET['action']) ? $_GET['action'] : '';
$filterUser = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$filterDateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$filterDateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$format = isset($_GET['format']) ? $_GET['format'] : 'csv';

// Формування запиту з урахуванням фільтрів
$query = "SELECT l.*, u.name as user_name 
         FROM system_log l
         LEFT JOIN polzovateli u ON l.user_id = u.id
         WHERE 1=1";

$params = [];
$types = '';

if (!empty($filterLevel)) {
    $query .= " AND l.level = ?";
    $params[] = $filterLevel;
    $types .= 's';
}

if (!empty($filterAction)) {
    $query .= " AND l.action = ?";
    $params[] = $filterAction;
    $types .= 's';
}

if ($filterUser > 0) {
    $query .= " AND l.user_id = ?";
    $params[] = $filterUser;
    $types .= 'i';
}

if (!empty($filterDateFrom)) {
    $query .= " AND DATE(l.timestamp) >= ?";
    $params[] = $filterDateFrom;
    $types .= 's';
}

if (!empty($filterDateTo)) {
    $query .= " AND DATE(l.timestamp) <= ?";
    $params[] = $filterDateTo;
    $types .= 's';
}

if (!empty($searchTerm)) {
    $searchTerm = '%' . $searchTerm . '%';
    $query .= " AND (l.action LIKE ? OR l.details LIKE ? OR u.name LIKE ?)";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'sss';
}

// Додавання сортування до основного запиту
$query .= " ORDER BY l.timestamp DESC";

// Виконання запиту
$stmt = mysqli_prepare($connection, $query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Налаштування експорту в залежності від формату
switch ($format) {
    case 'csv':
        // Встановлення заголовків для CSV-файлу
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="system_log_' . date('Y-m-d') . '.csv"');
        
        // Створення файлового потоку
        $output = fopen('php://output', 'w');
        
        // Додавання BOM для підтримки UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Запис заголовків колонок
        fputcsv($output, [
            'ID', 'Дія', 'Користувач', 'Час', 'Деталі', 'IP-адреса', 'Рівень'
        ]);
        
        // Запис даних
        while ($row = mysqli_fetch_assoc($result)) {
            fputcsv($output, [
                $row['id'],
                $row['action'],
                $row['user_name'] ?? 'Система',
                $row['timestamp'],
                $row['details'],
                $row['ip_address'],
                $row['level'] ?? 'info'
            ]);
        }
        
        // Закриття файлового потоку
        fclose($output);
        exit;
        break;
        
    case 'excel':
        // Для експорту в Excel потрібна бібліотека PhpSpreadsheet або подібна
        // В цьому прикладі ми просто формуємо CSV, але з іншими заголовками
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="system_log_' . date('Y-m-d') . '.xls"');
        
        echo '<table border="1">';
        echo '<tr>';
        echo '<th>ID</th>';
        echo '<th>Дія</th>';
        echo '<th>Користувач</th>';
        echo '<th>Час</th>';
        echo '<th>Деталі</th>';
        echo '<th>IP-адреса</th>';
        echo '<th>Рівень</th>';
        echo '</tr>';
        
        while ($row = mysqli_fetch_assoc($result)) {
            echo '<tr>';
            echo '<td>' . $row['id'] . '</td>';
            echo '<td>' . htmlspecialchars($row['action']) . '</td>';
            echo '<td>' . htmlspecialchars($row['user_name'] ?? 'Система') . '</td>';
            echo '<td>' . $row['timestamp'] . '</td>';
            echo '<td>' . htmlspecialchars($row['details']) . '</td>';
            echo '<td>' . htmlspecialchars($row['ip_address']) . '</td>';
            echo '<td>' . htmlspecialchars($row['level'] ?? 'info') . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        exit;
        break;
        
    case 'pdf':
        // Для генерації PDF потрібна бібліотека TCPDF, FPDF або подібна
        // В цьому прикладі просто повертаємо повідомлення
        include_once '../../includes/header.php';
        ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i> Для експорту в PDF потрібно встановити бібліотеку TCPDF або FPDF.
        </div>
        
        <a href="system_log.php" class="btn btn-primary">
            <i class="fas fa-arrow-left me-1"></i> Повернутися до журналу
        </a>
        <?php
        include_once '../../includes/footer.php';
        exit;
        break;
        
    default:
        // Якщо невідомий формат - перенаправляємо назад
        header("Location: system_log.php");
        exit;
        break;
}