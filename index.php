<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Підключення файлів із правильними шляхами:
require_once __DIR__ . '/database/Database.php';
require_once __DIR__ . '/Controllers/CalculatorController.php';

// Імпорт неймспейсу контролера, якщо він у файлі є
use BuildMaster\Controllers\CalculatorController;

$dbInstance = Database::getInstance();
$database = $dbInstance->getConnection();

ini_set('display_errors', 1);
error_reporting(E_ALL);

spl_autoload_register(function ($class) {
    $paths = [
        'Controllers/',
        'Models/',
        'Database/',
        'Core/'
    ];

    foreach ($paths as $path) {
        $file = __DIR__ . '/' . $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

$scriptName = $_SERVER['SCRIPT_NAME']; // /BuildMaster/index.php
$basePath = rtrim(dirname($scriptName), '/'); // /BuildMaster

if ($basePath && strpos($path, $basePath) === 0) {
    $path = substr($path, strlen($basePath));
}

if ($path === '' || $path === false) {
    $path = '/';
}

// Видалити debug вивід для продакшн
// echo "<pre>Request URI: $requestUri\nPath: $path\nBase path: $basePath</pre>";

// Роутер
switch ($path) {
    case '/':
    case '/home':
        try {
            if (class_exists('HomeController')) {
                $controller = new HomeController();
                $controller->index();
            } else {
                echo "HomeController не знайдено";
            }
        } catch (Throwable $e) {
            echo "Помилка в HomeController@index: " . $e->getMessage();
        }
        break;

    case '/Calculator':
    case '/calculator':
        try {
            // Показати головну сторінку калькулятора
            include __DIR__ . '/Views/calculator/calculator.php';
        } catch (Throwable $e) {
            echo "Помилка завантаження калькулятора: " . $e->getMessage();
        }
        break;

    case '/Calculator/project-form':
    case '/calculator/project-form':
        try {
            // Показати форму створення проекту
            include __DIR__ . '/Views/calculator/project-form.php';
        } catch (Throwable $e) {
            echo "Помилка завантаження форми проекту: " . $e->getMessage();
        }
        break;

    case '/calculator/room-types':
        try {
            $controller = new CalculatorController($database);
            $controller->getRoomTypes();
        } catch (Throwable $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Помилка завантаження типів кімнат: ' . $e->getMessage()]);
        }
        break;

    case '/calculator/create':
        try {
            $controller = new CalculatorController($database);
            $controller->createProject();
        } catch (Throwable $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Помилка: ' . $e->getMessage()]);
        }
        break;

    case (preg_match('#^/calculator/materials/([\w-]+)$#', $path, $matches) ? true : false):
        try {
            $slug = $matches[1];
            $controller = new CalculatorController($database);
            $controller->materials($slug);
        } catch (Throwable $e) {
            echo "Помилка: " . $e->getMessage();
        }
        break;

    case '/contact':
        try {
            if (class_exists('HomeController')) {
                $controller = new HomeController();
                $controller->contact();
            } else {
                echo "HomeController не знайдено";
            }
        } catch (Throwable $e) {
            echo "Помилка в HomeController@contact: " . $e->getMessage();
        }
        break;

    case '/login':
        try {
            if (class_exists('AuthController')) {
                $controller = new AuthController();
                $controller->login();
            } else {
                echo "AuthController не знайдено";
            }
        } catch (Throwable $e) {
            echo "Помилка в AuthController@login: " . $e->getMessage();
        }
        break;

    case '/register':
        try {
            if (class_exists('AuthController')) {
                $controller = new AuthController();
                $controller->register();
            } else {
                echo "AuthController не знайдено";
            }
        } catch (Throwable $e) {
            echo "Помилка в AuthController@register: " . $e->getMessage();
        }
        break;

    case '/admin':
        if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
            header('Location: /BuildMaster/login');
            exit;
        }
        try {
            if (class_exists('AdminController')) {
                $controller = new AdminController();
                $controller->dashboard();
            } else {
                echo "AdminController не знайдено";
            }
        } catch (Throwable $e) {
            echo "Помилка в AdminController@dashboard: " . $e->getMessage();
        }
        break;

    default:
        http_response_code(404);
        if (file_exists(__DIR__ . '/Views/errors/404.php')) {
            include __DIR__ . '/Views/errors/404.php';
        } else {
            echo "<h1>404 - Сторінку не знайдено</h1>";
            echo "<p>Запитаний шлях: " . htmlspecialchars($path) . "</p>";
        }
        break;
}