<?php
/**
 * API для системи "Одеський Коровай"
 * 
 * Цей файл є основним вхідним пунктом для API, яке забезпечує 
 * взаємодію між мобільним додатком та системою управління.
 */

// Встановлення заголовків для CORS та JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Попередній запит OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Підключення необхідних файлів
require_once '../config/database.php';
require_once '../includes/functions.php';

// Отримання параметрів запиту
$request_uri = $_SERVER['REQUEST_URI'];
$uri_parts = explode('/', trim(parse_url($request_uri, PHP_URL_PATH), '/'));

// Визначення кінцевої частини URI (ендпоінт API)
$endpoint = end($uri_parts);

// Отримання HTTP методу
$method = $_SERVER['REQUEST_METHOD'];

// Обробка вхідних даних для POST та PUT запитів
$data = [];
if ($method === 'POST' || $method === 'PUT') {
    $input = file_get_contents('php://input');
    if (!empty($input)) {
        $data = json_decode($input, true);
    } else {
        $data = $_POST;
    }
}

// Ініціалізація підключення до бази даних
$connection = connectDatabase();

// Обробка запитів
$response = [
    'status' => 'error',
    'message' => 'Unknown endpoint',
];

/**
 * Ендпоінти API:
 * 
 * GET /api/products - отримати список продуктів
 * GET /api/products/{id} - отримати інформацію про конкретний продукт
 * GET /api/clients - отримати список клієнтів
 * GET /api/clients/{id} - отримати інформацію про конкретного клієнта
 * GET /api/orders - отримати список замовлень
 * GET /api/orders/{id} - отримати інформацію про конкретне замовлення
 * POST /api/orders - створити нове замовлення
 * PUT /api/orders/{id} - оновити існуюче замовлення
 * DELETE /api/orders/{id} - видалити замовлення
 * POST /api/auth - автентифікація користувача
 */

// Перевірка токена для більшості ендпоінтів
$requireAuth = true;
$excludeEndpoints = ['auth', 'products']; // ендпоінти, які не потребують авторизації

if (!in_array($endpoint, $excludeEndpoints) && $requireAuth) {
    $headers = getallheaders();
    $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';
    
    // Перевірка токена (в реальному проекті потрібно використовувати більш безпечний механізм)
    if (!validateToken($token)) {
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => 'Unauthorized'
        ]);
        exit;
    }
}

// Обробка різних ендпоінтів
switch ($endpoint) {
    case 'products':
        handleProductsEndpoint($method, $data, $connection);
        break;
        
    case 'clients':
        handleClientsEndpoint($method, $data, $connection);
        break;
        
    case 'orders':
        handleOrdersEndpoint($method, $data, $connection);
        break;
        
    case 'auth':
        handleAuthEndpoint($method, $data, $connection);
        break;
        
    default:
        // Перевірка на ендпоінт з ID
        $id = null;
        if (preg_match('/^(products|clients|orders)\/(\d+)$/', $endpoint, $matches)) {
            $endpoint = $matches[1];
            $id = $matches[2];
            
            switch ($endpoint) {
                case 'products':
                    handleProductEndpoint($method, $id, $data, $connection);
                    break;
                    
                case 'clients':
                    handleClientEndpoint($method, $id, $data, $connection);
                    break;
                    
                case 'orders':
                    handleOrderEndpoint($method, $id, $data, $connection);
                    break;
                    
                default:
                    http_response_code(404);
                    echo json_encode($response);
                    break;
            }
        } else {
            http_response_code(404);
            echo json_encode($response);
        }
        break;
}

// Закриття підключення до БД
mysqli_close($connection);

/**
 * Перевірка JWT токена
 * 
 * @param string $token JWT токен
 * @return bool Результат перевірки токена
 */
function validateToken($token) {
    // В реальному проекті потрібно використовувати бібліотеку для JWT
    // Наприклад, firebase/php-jwt
    
    // Спрощена перевірка для демонстрації
    if (empty($token)) {
        return false;
    }
    
    // Перевірка токена в базі даних або декодування JWT
    return true;
}

/**
 * Обробка ендпоінту автентифікації
 * 
 * @param string $method HTTP метод
 * @param array $data Дані запиту
 * @param mysqli $connection Підключення до БД
 */
function handleAuthEndpoint($method, $data, $connection) {
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'status' => 'error',
            'message' => 'Method Not Allowed'
        ]);
        return;
    }
    
    // Перевірка наявності необхідних полів
    if (empty($data['login']) || empty($data['password'])) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing required fields'
        ]);
        return;
    }
    
    $login = $data['login'];
    $password = $data['password'];
    
    // Перевірка логіна та пароля
    $query = "SELECT * FROM polzovateli WHERE login = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "s", $login);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Перевірка пароля (в реальному проекті потрібно використовувати хешування)
        if ($password === $user['password']) {
            // Генерація токена (в реальному проекті потрібно використовувати JWT)
            $token = bin2hex(random_bytes(32));
            $userId = $user['id'];
            $userRole = getUserRoleById($userId);
            
            // Збереження токена в базі даних
            // (в реальному проекті - збереження у Redis або використання JWT)
            
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'message' => 'Authentication successful',
                'data' => [
                    'token' => $token,
                    'user' => [
                        'id' => $userId,
                        'name' => $user['name'],
                        'role' => $userRole
                    ]
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid credentials'
            ]);
        }
    } else {
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid credentials'
        ]);
    }
}

/**
 * Визначення ролі користувача за ID
 * 
 * @param int $userId ID користувача
 * @return string Роль користувача
 */
function getUserRoleById($userId) {
    switch ($userId) {
        case 1: 
            return 'manager';
        case 2: 
            return 'brigadir';
        case 3: 
            return 'admin';
        default: 
            return 'client';
    }
}

/**
 * Обробка ендпоінту продуктів
 * 
 * @param string $method HTTP метод
 * @param array $data Дані запиту
 * @param mysqli $connection Підключення до БД
 */
function handleProductsEndpoint($method, $data, $connection) {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode([
            'status' => 'error',
            'message' => 'Method Not Allowed'
        ]);
        return;
    }
    
    // Параметри пагінації та фільтрації
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    // Розрахунок зміщення для пагінації
    $offset = ($page - 1) * $limit;
    
    // Формування запиту з урахуванням пошуку
    $query = "SELECT * FROM product WHERE 1=1";
    $countQuery = "SELECT COUNT(*) as total FROM product WHERE 1=1";
    
    $params = [];
    $types = '';
    
    if (!empty($search)) {
        $query .= " AND nazvanie LIKE ?";
        $countQuery .= " AND nazvanie LIKE ?";
        $searchParam = '%' . $search . '%';
        $params[] = $searchParam;
        $types .= 's';
    }
    
    $query .= " ORDER BY nazvanie LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    // Виконання запиту для підрахунку загальної кількості
    $countStmt = mysqli_prepare($connection, $countQuery);
    if (!empty($search)) {
        mysqli_stmt_bind_param($countStmt, "s", $searchParam);
    }
    mysqli_stmt_execute($countStmt);
    $countResult = mysqli_stmt_get_result($countStmt);
    $totalCount = mysqli_fetch_assoc($countResult)['total'];
    
    // Виконання основного запиту
    $stmt = mysqli_prepare($connection, $query);
    if (!empty($params)) {
        array_unshift($params, $types);
        call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt], $params));
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $products = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Додавання URL для зображення
        if (!empty($row['image'])) {
            $row['image_url'] = 'http://' . $_SERVER['HTTP_HOST'] . '/' . $row['image'];
        } else {
            $row['image_url'] = 'http://' . $_SERVER['HTTP_HOST'] . '/assets/img/product-placeholder.jpg';
        }
        
        $products[] = $row;
    }
    
    // Формування відповіді з метаданими для пагінації
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Products retrieved',
        'meta' => [
            'total' => $totalCount,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($totalCount / $limit)
        ],
        'data' => $products
    ]);
}

/**
 * Обробка ендпоінту для конкретного продукту
 * 
 * @param string $method HTTP метод
 * @param int $id ID продукту
 * @param array $data Дані запиту
 * @param mysqli $connection Підключення до БД
 */
function handleProductEndpoint($method, $id, $data, $connection) {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode([
            'status' => 'error',
            'message' => 'Method Not Allowed'
        ]);
        return;
    }
    
    // Отримання інформації про продукт
    $query = "SELECT * FROM product WHERE id = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 1) {
        $product = mysqli_fetch_assoc($result);
        
        // Додавання URL для зображення
        if (!empty($product['image'])) {
            $product['image_url'] = 'http://' . $_SERVER['HTTP_HOST'] . '/' . $product['image'];
        } else {
            $product['image_url'] = 'http://' . $_SERVER['HTTP_HOST'] . '/assets/img/product-placeholder.jpg';
        }
        
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Product retrieved',
            'data' => $product
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Product not found'
        ]);
    }
}

/**
 * Обробка ендпоінту клієнтів
 * 
 * @param string $method HTTP метод
 * @param array $data Дані запиту
 * @param mysqli $connection Підключення до БД
 */
function handleClientsEndpoint($method, $data, $connection) {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode([
            'status' => 'error',
            'message' => 'Method Not Allowed'
        ]);
        return;
    }
    
    // Параметри пагінації та фільтрації
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    // Розрахунок зміщення для пагінації
    $offset = ($page - 1) * $limit;
    
    // Формування запиту з урахуванням пошуку
    $query = "SELECT * FROM klientu WHERE 1=1";
    $countQuery = "SELECT COUNT(*) as total FROM klientu WHERE 1=1";
    
    $params = [];
    $types = '';
    
    if (!empty($search)) {
        $query .= " AND (name LIKE ? OR fio LIKE ? OR city LIKE ?)";
        $countQuery .= " AND (name LIKE ? OR fio LIKE ? OR city LIKE ?)";
        $searchParam = '%' . $search . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'sss';
    }
    
    $query .= " ORDER BY name LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    // Виконання запиту для підрахунку загальної кількості
    $countStmt = mysqli_prepare($connection, $countQuery);
    if (!empty($search)) {
        mysqli_stmt_bind_param($countStmt, "sss", $searchParam, $searchParam, $searchParam);
    }
    mysqli_stmt_execute($countStmt);
    $countResult = mysqli_stmt_get_result($countStmt);
    $totalCount = mysqli_fetch_assoc($countResult)['total'];
    
    // Виконання основного запиту
    $stmt = mysqli_prepare($connection, $query);
    if (!empty($params)) {
        array_unshift($params, $types);
        call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt], $params));
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $clients = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Видалення чутливих даних
        unset($row['password']);
        
        $clients[] = $row;
    }
    
    // Формування відповіді з метаданими для пагінації
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Clients retrieved',
        'meta' => [
            'total' => $totalCount,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($totalCount / $limit)
        ],
        'data' => $clients
    ]);
}

/**
 * Обробка ендпоінту для конкретного клієнта
 * 
 * @param string $method HTTP метод
 * @param int $id ID клієнта
 * @param array $data Дані запиту
 * @param mysqli $connection Підключення до БД
 */
function handleClientEndpoint($method, $id, $data, $connection) {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode([
            'status' => 'error',
            'message' => 'Method Not Allowed'
        ]);
        return;
    }
    
    // Отримання інформації про клієнта
    $query = "SELECT * FROM klientu WHERE id = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 1) {
        $client = mysqli_fetch_assoc($result);
        
        // Видалення чутливих даних
        unset($client['password']);
        
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Client retrieved',
            'data' => $client
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Client not found'
        ]);
    }
}

/**
 * Обробка ендпоінту замовлень
 * 
 * @param string $method HTTP метод
 * @param array $data Дані запиту
 * @param mysqli $connection Підключення до БД
 */
function handleOrdersEndpoint($method, $data, $connection) {
    switch ($method) {
        case 'GET':
            // Параметри пагінації та фільтрації
            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
            $clientId = isset($_GET['client_id']) ? intval($_GET['client_id']) : null;
            $status = isset($_GET['status']) ? $_GET['status'] : null;
            $doba = isset($_GET['doba']) ? $_GET['doba'] : null;
            
            // Розрахунок зміщення для пагінації
            $offset = ($page - 1) * $limit;
            
            // Формування запиту з урахуванням фільтрів
            $query = "SELECT z.*, k.name as client_name, p.nazvanie as product_name, p.zena 
                    FROM zayavki z
                    JOIN klientu k ON z.idklient = k.id
                    JOIN product p ON z.id = p.id
                    WHERE 1=1";
            $countQuery = "SELECT COUNT(*) as total FROM zayavki z WHERE 1=1";
            
            $params = [];
            $types = '';
            
            if ($clientId !== null) {
                $query .= " AND z.idklient = ?";
                $countQuery .= " AND z.idklient = ?";
                $params[] = $clientId;
                $types .= 'i';
            }
            
            if ($status !== null) {
                $query .= " AND z.status = ?";
                $countQuery .= " AND z.status = ?";
                $params[] = $status;
                $types .= 's';
            }
            
            if ($doba !== null) {
                $query .= " AND z.doba = ?";
                $countQuery .= " AND z.doba = ?";
                $params[] = $doba;
                $types .= 's';
            }
            
            $query .= " ORDER BY z.data DESC, z.idd DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $types .= 'ii';
            
            // Виконання запиту для підрахунку загальної кількості
            $countStmt = mysqli_prepare($connection, $countQuery);
            if (!empty($params) && count($params) > 2) {
                $countTypes = substr($types, 0, -2);
                $countParams = array_slice($params, 0, -2);
                array_unshift($countParams, $countTypes);
                call_user_func_array('mysqli_stmt_bind_param', array_merge([$countStmt], $countParams));
            }
            mysqli_stmt_execute($countStmt);
            $countResult = mysqli_stmt_get_result($countStmt);
            $totalCount = mysqli_fetch_assoc($countResult)['total'];
            
            // Виконання основного запиту
            $stmt = mysqli_prepare($connection, $query);
            if (!empty($params)) {
                array_unshift($params, $types);
                call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt], $params));
            }
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $orders = [];
            while ($row = mysqli_fetch_assoc($result)) {
                // Розрахунок загальної суми
                $row['total_price'] = $row['kol'] * $row['zena'];
                
                $orders[] = $row;
            }
            
            // Формування відповіді з метаданими для пагінації
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'message' => 'Orders retrieved',
                'meta' => [
                    'total' => $totalCount,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($totalCount / $limit)
                ],
                'data' => $orders
            ]);
            break;
            
        case 'POST':
            // Перевірка наявності необхідних полів
            if (empty($data['idklient']) || empty($data['id']) || empty($data['kol']) || empty($data['data']) || empty($data['doba'])) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Missing required fields'
                ]);
                return;
            }
            
            // Додавання нового замовлення
            $query = "INSERT INTO zayavki (idklient, id, kol, data, doba, status) VALUES (?, ?, ?, ?, ?, 'нове')";
            $stmt = mysqli_prepare($connection, $query);
            mysqli_stmt_bind_param($stmt, "iiiss", $data['idklient'], $data['id'], $data['kol'], $data['data'], $data['doba']);
            
            if (mysqli_stmt_execute($stmt)) {
                $orderId = mysqli_insert_id($connection);
                
                // Отримання інформації про створене замовлення
                $orderQuery = "SELECT z.*, k.name as client_name, p.nazvanie as product_name, p.zena 
                              FROM zayavki z
                              JOIN klientu k ON z.idklient = k.id
                              JOIN product p ON z.id = p.id
                              WHERE z.idd = ?";
                $orderStmt = mysqli_prepare($connection, $orderQuery);
                mysqli_stmt_bind_param($orderStmt, "i", $orderId);
                mysqli_stmt_execute($orderStmt);
                $orderResult = mysqli_stmt_get_result($orderStmt);
                $order = mysqli_fetch_assoc($orderResult);
                
                // Розрахунок загальної суми
                $order['total_price'] = $order['kol'] * $order['zena'];
                
                http_response_code(201);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Order created',
                    'data' => $order
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Failed to create order: ' . mysqli_error($connection)
                ]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode([
                'status' => 'error',
                'message' => 'Method Not Allowed'
            ]);
            break;
    }
}

/**
 * Обробка ендпоінту для конкретного замовлення
 * 
 * @param string $method HTTP метод
 * @param int $id ID замовлення
 * @param array $data Дані запиту
 * @param mysqli $connection Підключення до БД
 */
function handleOrderEndpoint($method, $id, $data, $connection) {
    switch ($method) {
        case 'GET':
            // Отримання інформації про замовлення
            $query = "SELECT z.*, k.name as client_name, k.fio, k.tel, k.city, k.adres, 
                             p.nazvanie as product_name, p.ves, p.zena, p.image
                      FROM zayavki z
                      JOIN klientu k ON z.idklient = k.id
                      JOIN product p ON z.id = p.id
                      WHERE z.idd = ?";
            $stmt = mysqli_prepare($connection, $query);
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) === 1) {
                $order = mysqli_fetch_assoc($result);
                
                // Розрахунок загальної суми та ваги
                $order['total_price'] = $order['kol'] * $order['zena'];
                $order['total_weight'] = $order['kol'] * $order['ves'];
                
                // Додавання URL для зображення
                if (!empty($order['image'])) {
                    $order['image_url'] = 'http://' . $_SERVER['HTTP_HOST'] . '/' . $order['image'];
                } else {
                    $order['image_url'] = 'http://' . $_SERVER['HTTP_HOST'] . '/assets/img/product-placeholder.jpg';
                }
                
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Order retrieved',
                    'data' => $order
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Order not found'
                ]);
            }
            break;
            
        case 'PUT':
            // Перевірка наявності необхідних полів
            if (empty($data) || (!isset($data['kol']) && !isset($data['status']))) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Missing required fields'
                ]);
                return;
            }
            
            // Оновлення кількості
            if (isset($data['kol'])) {
                $query = "UPDATE zayavki SET kol = ? WHERE idd = ?";
                $stmt = mysqli_prepare($connection, $query);
                mysqli_stmt_bind_param($stmt, "ii", $data['kol'], $id);
                
                if (!mysqli_stmt_execute($stmt)) {
                    http_response_code(500);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Failed to update order quantity: ' . mysqli_error($connection)
                    ]);
                    return;
                }
            }
            
            // Оновлення статусу
            if (isset($data['status'])) {
                $query = "UPDATE zayavki SET status = ? WHERE idd = ?";
                $stmt = mysqli_prepare($connection, $query);
                mysqli_stmt_bind_param($stmt, "si", $data['status'], $id);
                
                if (!mysqli_stmt_execute($stmt)) {
                    http_response_code(500);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Failed to update order status: ' . mysqli_error($connection)
                    ]);
                    return;
                }
            }
            
            // Отримання оновленого замовлення
            $orderQuery = "SELECT z.*, k.name as client_name, p.nazvanie as product_name, p.zena 
                          FROM zayavki z
                          JOIN klientu k ON z.idklient = k.id
                          JOIN product p ON z.id = p.id
                          WHERE z.idd = ?";
            $orderStmt = mysqli_prepare($connection, $orderQuery);
            mysqli_stmt_bind_param($orderStmt, "i", $id);
            mysqli_stmt_execute($orderStmt);
            $orderResult = mysqli_stmt_get_result($orderStmt);
            
            if (mysqli_num_rows($orderResult) === 1) {
                $order = mysqli_fetch_assoc($orderResult);
                
                // Розрахунок загальної суми
                $order['total_price'] = $order['kol'] * $order['zena'];
                
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Order updated',
                    'data' => $order
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Order not found after update'
                ]);
            }
            break;
            
        case 'DELETE':
            // Видалення замовлення
            $query = "DELETE FROM zayavki WHERE idd = ?";
            $stmt = mysqli_prepare($connection, $query);
            mysqli_stmt_bind_param($stmt, "i", $id);
            
            if (mysqli_stmt_execute($stmt)) {
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Order deleted'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Failed to delete order: ' . mysqli_error($connection)
                ]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode([
                'status' => 'error',
                'message' => 'Method Not Allowed'
            ]);
            break;
    }
}