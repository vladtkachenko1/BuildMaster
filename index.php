<?php
// index.php
session_start();

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
});

// Простий роутер
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);
$query = parse_url($request, PHP_URL_QUERY);

// Видаляємо базовий шлях якщо сайт в підпапці
$basePath = dirname($_SERVER['SCRIPT_NAME']);
if ($basePath !== '/') {
    $path = substr($path, strlen($basePath));
}

// Маршрути
switch ($path) {
    case '/':
    case '/home':
        $controller = new HomeController();
        $controller->index();
        break;

    case '/contact':
        $controller = new HomeController();
        $controller->contact();
        break;

    case '/login':
        $controller = new AuthController();
        $controller->login();
        break;

    case '/register':
        $controller = new AuthController();
        $controller->register();
        break;

    case '/calculator':
        // Перевіряємо чи користувач авторизований
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login?redirect=calculator');
            exit;
        }
        $controller = new CalculatorController();
        $controller->index();
        break;

    case '/admin':
        if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
            header('Location: /login');
            exit;
        }
        $controller = new AdminController();
        $controller->dashboard();
        break;

    default:
        // 404 сторінка
        http_response_code(404);
        include 'view/errors/404.php';
        break;
}
?>