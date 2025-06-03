<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Увімкни помилки для локального середовища
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Автозавантажувач класів
spl_autoload_register(function ($class) {
    $paths = [
        'controllers/',
        'models/',
        'database/',
        'core/'
    ];

    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }

    // Якщо клас не знайдено — повідомити
    die("Помилка: не знайдено клас {$class}");
});

// Отримуємо URI
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// !!! Виправлення: базовий шлях обробляється коректно
$scriptName = $_SERVER['SCRIPT_NAME']; // /project/index.php
$basePath = rtrim(dirname($scriptName), '/'); // /project

if ($basePath && strpos($path, $basePath) === 0) {
    $path = substr($path, strlen($basePath));
}

// Якщо порожній шлях — це домашня сторінка
if ($path === '' || $path === false) {
    $path = '/';
}

// Вивід для дебагу (можна прибрати)
echo "<pre>Request URI: $requestUri\nPath: $path\nBase path: $basePath</pre>";

// Роутер
switch ($path) {
    case '/':
    case '/home':
        try {
            $controller = new HomeController();
            $controller->index();
        } catch (Throwable $e) {
            echo "Помилка в HomeController@index: " . $e->getMessage();
        }
        break;

    case '/contact':
        try {
            $controller = new HomeController();
            $controller->contact();
        } catch (Throwable $e) {
            echo "Помилка в HomeController@contact: " . $e->getMessage();
        }
        break;

    case '/login':
        try {
            $controller = new AuthController();
            $controller->login();
        } catch (Throwable $e) {
            echo "Помилка в AuthController@login: " . $e->getMessage();
        }
        break;

    case '/register':
        try {
            $controller = new AuthController();
            $controller->register();
        } catch (Throwable $e) {
            echo "Помилка в AuthController@register: " . $e->getMessage();
        }
        break;

    case '/calculator':
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login?redirect=calculator');
            exit;
        }
        try {
            $controller = new CalculatorController();
            $controller->index();
        } catch (Throwable $e) {
            echo "Помилка в CalculatorController@index: " . $e->getMessage();
        }
        break;

    case '/admin':
        if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
            header('Location: /login');
            exit;
        }
        try {
            $controller = new AdminController();
            $controller->dashboard();
        } catch (Throwable $e) {
            echo "Помилка в AdminController@dashboard: " . $e->getMessage();
        }
        break;

    default:
        http_response_code(404);
        if (file_exists('view/errors/404.php')) {
            include 'view/errors/404.php';
        } else {
            echo "<h1>404 - Сторінку не знайдено</h1>";
        }
        break;
}
