
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Підключення файлів
require_once __DIR__ . '/database/Database.php';
require_once __DIR__ . '/Controllers/CalculatorController.php';
require_once __DIR__ . '/Controllers/ServiceCalculatorController.php';
require_once __DIR__ . '/Controllers/OrderController.php';

use BuildMaster\Controllers\CalculatorController;
use BuildMaster\Controllers\ServiceCalculatorController;
use BuildMaster\Controllers\OrderController;

// Налаштування
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Підключення бази даних
$dbInstance = Database::getInstance();
$database = $dbInstance->getConnection();

// Автозавантажувач класів
spl_autoload_register(function ($class) {
    $paths = ['Controllers/', 'Models/', 'Database/', 'Core/'];

    foreach ($paths as $path) {
        $file = __DIR__ . '/' . $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Обробка URL
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$scriptName = $_SERVER['SCRIPT_NAME'];
$basePath = rtrim(dirname($scriptName), '/');

if ($basePath && strpos($path, $basePath) === 0) {
    $path = substr($path, strlen($basePath));
}

$path = $path ?: '/';

/**
 * Безпечне виконання контролера з обробкою помилок
 */
function executeController(callable $callback, string $errorMessage = 'Помилка виконання') {
    try {
        return $callback();
    } catch (Throwable $e) {
        error_log("Router Error: {$errorMessage} - " . $e->getMessage());

        if (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false ||
            strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $errorMessage]);
        } else {
            echo "<h1>Помилка</h1><p>{$errorMessage}</p>";
            if (ini_get('display_errors')) {
                echo "<pre>" . $e->getMessage() . "</pre>";
            }
        }
    }
}

// ================================
// МАРШРУТИЗАЦІЯ
// ================================

switch ($path) {

    // ================================
    // ГОЛОВНІ СТОРІНКИ
    // ================================

    case '/':
    case '/home':
        executeController(function() {
            if (class_exists('HomeController')) {
                $controller = new HomeController();
                $controller->index();
            } else {
                echo "HomeController не знайдено";
            }
        }, 'Помилка завантаження головної сторінки');
        break;

    case '/contact':
        executeController(function() {
            if (class_exists('HomeController')) {
                $controller = new HomeController();
                $controller->contact();
            } else {
                echo "HomeController не знайдено";
            }
        }, 'Помилка завантаження сторінки контактів');
        break;

    // ================================
    // АУТЕНТИФІКАЦІЯ
    // ================================

    case '/login':
        executeController(function() {
            if (class_exists('AuthController')) {
                $controller = new AuthController();
                $controller->login();
            } else {
                echo "AuthController не знайдено";
            }
        }, 'Помилка завантаження сторінки входу');
        break;

    case '/register':
        executeController(function() {
            if (class_exists('AuthController')) {
                $controller = new AuthController();
                $controller->register();
            } else {
                echo "AuthController не знайдено";
            }
        }, 'Помилка завантаження сторінки реєстрації');
        break;

    // ================================
    // АДМІНІСТРАТИВНА ПАНЕЛЬ
    // ================================

    case '/admin':
        if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
            header('Location: /BuildMaster/login');
            exit;
        }

        executeController(function() {
            if (class_exists('AdminController')) {
                $controller = new AdminController();
                $controller->dashboard();
            } else {
                echo "AdminController не знайдено";
            }
        }, 'Помилка завантаження адміністративної панелі');
        break;

    // ================================
    // КАЛЬКУЛЯТОР - ГОЛОВНІ СТОРІНКИ
    // ================================

    case '/calculator':
    case '/Calculator':
        executeController(function() use ($database) {
            $controller = new CalculatorController($database);
            $controller->index();
        }, 'Помилка завантаження калькулятора');
        break;

    case '/calculator/project-form':
    case '/Calculator/project-form':
        executeController(function() use ($database) {
            $controller = new CalculatorController($database);
            $controller->getProjectForm();
        }, 'Помилка завантаження форми проекту');
        break;

    case '/calculator/services-selection':
        executeController(function() use ($database) {
            $controller = new CalculatorController($database);
            $controller->servicesSelection();
        }, 'Помилка завантаження вибору послуг');
        break;

    // ================================
    // КАЛЬКУЛЯТОР - API ENDPOINTS
    // ================================

    case '/calculator/room-types':
        executeController(function() use ($database) {
            $controller = new CalculatorController($database);
            $controller->getRoomTypes();
        }, 'Помилка завантаження типів кімнат');
        break;

    // ВИПРАВЛЕНО: додано правильний маршрут для отримання послуг
    case '/calculator/services':
        executeController(function() use ($database) {
            $controller = new CalculatorController($database);
            $controller->getServicesJson();
        }, 'Помилка завантаження послуг');
        break;

    case '/calculator/services-json':
    case '/api/services':
        executeController(function() use ($database) {
            $controller = new CalculatorController($database);
            $controller->getServicesJson();
        }, 'Помилка завантаження послуг');
        break;

    case '/calculator/calculate-json':
    case '/api/calculate':
    case '/calculate':
        executeController(function() use ($database) {
            $controller = new CalculatorController($database);
            $controller->calculateJson();
        }, 'Помилка розрахунку');
        break;

    // ВИПРАВЛЕНО: цей маршрут тепер не використовується для форми проекту
    case '/calculator/create':
    case '/calculator/create-project':
        executeController(function() use ($database) {
            $controller = new CalculatorController($database);
            $controller->createProject();
        }, 'Помилка створення проекту');
        break;

    case '/calculator/save-room-services':
        executeController(function() use ($database) {
            $controller = new CalculatorController($database);
            $controller->saveRoomWithServices();
        }, 'Помилка збереження кімнати з послугами');
        break;

    case '/calculator/current-rooms':
        executeController(function() use ($database) {
            $controller = new CalculatorController($database);
            $controller->getCurrentOrderRooms();
        }, 'Помилка завантаження поточних кімнат');
        break;

    case '/calculator/result':
        executeController(function() use ($database) {
            $controller = new CalculatorController($database);
            $controller->result();
        }, 'Помилка завантаження результатів');
        break;

    // ================================
    // ЗАМОВЛЕННЯ - ГОЛОВНІ СТОРІНКИ
    // ================================

    case '/calculator/order-rooms':
    case '/order/rooms':
        executeController(function() use ($database) {
            $controller = new OrderController($database);
            $controller->showOrderRooms();
        }, 'Помилка завантаження кімнат замовлення');
        break;

    case '/calculator/order-success':
    case '/order/success':
        executeController(function() use ($database) {
            $controller = new OrderController($database);
            $controller->orderSuccess();
        }, 'Помилка завантаження сторінки успіху');
        break;

    // ================================
    // ЗАМОВЛЕННЯ - API ENDPOINTS
    // ================================

    case '/calculator/create-order-for-new-room':
        executeController(function() use ($database) {
            $controller = new OrderController($database);
            $controller->createOrderForNewRoom();
        }, 'Помилка створення замовлення для нової кімнати');
        break;

    case '/calculator/create-empty-order':
    case '/order/create-empty':
        executeController(function() use ($database) {
            $controller = new OrderController($database);
            $controller->createEmptyOrder();
        }, 'Помилка створення порожнього замовлення');
        break;

    case '/calculator/update-room-services':
        executeController(function() use ($database) {
            $controller = new OrderController($database);
            $controller->updateRoomWithServices();
        }, 'Помилка оновлення послуг кімнати');
        break;

    case '/calculator/complete-order':
    case '/order/complete':
        executeController(function() use ($database) {
            $controller = new OrderController($database);
            $controller->completeOrder();
        }, 'Помилка завершення замовлення');
        break;

    case '/calculator/remove-room':
    case '/order/remove-room':
        executeController(function() use ($database) {
            $controller = new OrderController($database);
            $controller->removeRoom();
        }, 'Помилка видалення кімнати');
        break;

    // ================================
    // ЗАСТАРІЛІ/АЛЬТЕРНАТИВНІ МАРШРУТИ
    // ================================

    case '/calculator/create-new-order':
        // Перенаправляємо на новий endpoint
        header('Location: /BuildMaster/calculator/create-empty-order');
        exit;
        break;

    case '/calculator/add-room':
        // Цей маршрут може бути перенаправлений або обробленй
        executeController(function() use ($database) {
            $controller = new OrderController($database);
            if (method_exists($controller, 'addRoomToOrder')) {
                $controller->addRoomToOrder();
            } else {
                // Перенаправляємо на форму створення проекту
                header('Location: /BuildMaster/calculator/project-form');
                exit;
            }
        }, 'Помилка додавання кімнати');
        break;

    case '/calculator/edit-room':
        executeController(function() use ($database) {
            $controller = new OrderController($database);
            if (method_exists($controller, 'editRoom')) {
                $controller->editRoom();
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Метод не реалізовано']);
            }
        }, 'Помилка редагування кімнати');
        break;

    case '/calculator/update-room':
        // Перенаправляємо на правильний endpoint
        header('Location: /BuildMaster/calculator/update-room-services');
        exit;
        break;

    // ================================
    // 404 - СТОРІНКУ НЕ ЗНАЙДЕНО
    // ================================

    default:
        http_response_code(404);

        if (file_exists(__DIR__ . '/Views/errors/404.php')) {
            include __DIR__ . '/Views/errors/404.php';
        } else {
            echo "
            <html>
            <head>
                <title>404 - Сторінку не знайдено</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 50px; }
                    h1 { color: #d32f2f; }
                    .info { background: #f5f5f5; padding: 15px; border-radius: 5px; }
                </style>
            </head>
            <body>
                <h1>404 - Сторінку не знайдено</h1>
                <div class='info'>
                    <p><strong>Запитаний шлях:</strong> " . htmlspecialchars($path) . "</p>
                    <p><strong>Повний URI:</strong> " . htmlspecialchars($requestUri) . "</p>
                    <p><a href='/BuildMaster/'>← Повернутися на головну</a></p>
                </div>
            </body>
            </html>";
        }
        break;
}