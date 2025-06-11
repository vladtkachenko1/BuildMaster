<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../database/Database.php';

class AuthController {
    public function handleRequest() {
        $action = isset($_GET['action']) ? $_GET['action'] : '';

        switch ($action) {
            case 'login':
                $this->login();
                break;
            case 'register':
                $this->register();
                break;
            case 'logout':
                $this->logout();
                break;
            case 'check':
                $this->checkAuth();
                break;
            default:
                http_response_code(404);
                echo json_encode(['error' => 'Action not found']);
                break;
        }
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $password = isset($_POST['password']) ? trim($_POST['password']) : '';

            if (empty($email) || empty($password)) {
                http_response_code(400);
                echo json_encode(['error' => 'Email та пароль обов\'язкові']);
                return;
            }

            try {
                $db = Database::getInstance()->getConnection();

                // ОНОВЛЕНО: Додано is_admin та status в запит
                $stmt = $db->prepare("SELECT id, email, password, is_admin, status, first_name, last_name FROM users WHERE email = :email");
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->execute();

                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    // Перевірка статусу користувача
                    if ($user['status'] === 'banned') {
                        http_response_code(403);
                        echo json_encode(['error' => 'Ваш акаунт заблоковано']);
                        return;
                    }

                    if ($user['status'] === 'inactive') {
                        http_response_code(403);
                        echo json_encode(['error' => 'Ваш акаунт неактивний']);
                        return;
                    }

                    // ВИПРАВЛЕНО: Гарантуємо що ID зберігається як число
                    $_SESSION['user'] = [
                        'id' => (int)$user['id'], // ВИПРАВЛЕНО: приводимо до int
                        'email' => $user['email'],
                        'first_name' => $user['first_name'],
                        'last_name' => $user['last_name'],
                        'is_admin' => (bool)$user['is_admin'],
                        'authenticated' => true
                    ];

                    // ДОДАНО: Логуємо для відладки
                    error_log("AuthController: User logged in with ID: " . $_SESSION['user']['id'] . " (type: " . gettype($_SESSION['user']['id']) . ")");

                    // Відновлюємо активне замовлення користувача
                    $this->restoreUserActiveOrder($db, (int)$user['id']); // ВИПРАВЛЕНО: приводимо до int

                    http_response_code(200);
                    echo json_encode([
                        'success' => true,
                        'is_admin' => (bool)$user['is_admin']
                    ]);
                } else {
                    http_response_code(401);
                    echo json_encode(['error' => 'Невірний логін або пароль']);
                }

            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Помилка сервера: ' . $e->getMessage()]);
            }
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Метод не дозволено']);
        }
    }

    private function restoreUserActiveOrder($db, $userId) {
        try {
            // ВИПРАВЛЕНО: Явно приводимо до int
            $userId = (int)$userId;

            $stmt = $db->prepare("
                SELECT id FROM orders 
                WHERE user_id = ? AND status = 'draft' 
                ORDER BY updated_at DESC LIMIT 1
            ");
            $stmt->execute([$userId]);
            $activeOrder = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($activeOrder) {
                $_SESSION['current_order_id'] = (int)$activeOrder['id']; // ВИПРАВЛЕНО: приводимо до int
                error_log("Restored active order {$activeOrder['id']} for user {$userId} after login");
            }
        } catch (Exception $e) {
            error_log("Error restoring user active order: " . $e->getMessage());
        }
    }

    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
            $last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
            $password = isset($_POST['password']) ? trim($_POST['password']) : '';

            if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
                http_response_code(400);
                echo json_encode(['error' => 'Заповніть обов\'язкові поля']);
                return;
            }

            try {
                $db = Database::getInstance()->getConnection();

                // Перевірка чи email вже існує
                $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->execute();
                if ($stmt->fetch()) {
                    http_response_code(409);
                    echo json_encode(['error' => 'Користувач з таким email вже існує']);
                    return;
                }

                // Хешуємо пароль
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                // Вставляємо нового користувача (is_admin за замовчуванням 0)
                $stmt = $db->prepare("INSERT INTO users (first_name, last_name, email, phone, password, is_admin, status) VALUES (:first_name, :last_name, :email, :phone, :password, 0, 'active')");
                $stmt->bindParam(':first_name', $first_name, PDO::PARAM_STR);
                $stmt->bindParam(':last_name', $last_name, PDO::PARAM_STR);
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
                $stmt->bindParam(':password', $passwordHash, PDO::PARAM_STR);

                if ($stmt->execute()) {
                    http_response_code(201);
                    echo json_encode(['success' => true, 'message' => 'Реєстрація пройшла успішно']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Не вдалося створити користувача']);
                }

            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Помилка сервера: ' . $e->getMessage()]);
            }

        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Метод не дозволено']);
        }
    }

    public function logout() {
        session_unset();
        session_destroy();

        // Якщо це AJAX запит
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(['success' => true, 'redirect' => '/']);
        } else {
            header('Location: /');
            exit;
        }
    }

    public function checkAuth() {
        header('Content-Type: application/json');
        echo json_encode([
            'authenticated' => $this->isAuthenticated(),
            'is_admin' => $this->isAdmin()
        ]);
    }

    public function isAuthenticated() {
        return isset($_SESSION['user']) &&
            isset($_SESSION['user']['authenticated']) &&
            $_SESSION['user']['authenticated'] === true;
    }

    // НОВА ФУНКЦІЯ: Перевірка чи користувач є адміном
    public function isAdmin() {
        return $this->isAuthenticated() &&
            isset($_SESSION['user']['is_admin']) &&
            $_SESSION['user']['is_admin'] === true;
    }

    // НОВА ФУНКЦІЯ: Перевірка доступу для адмінів
    public function requireAdmin() {
        if (!$this->isAdmin()) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                http_response_code(403);
                echo json_encode(['error' => 'Доступ заборонено. Потрібні права адміністратора']);
            } else {
                header('Location: /?error=access_denied');
            }
            exit;
        }
    }

    public function requireAuth() {
        if (!$this->isAuthenticated()) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                http_response_code(401);
                echo json_encode(['error' => 'Authentication required']);
            } else {
                header('Location: /?login=required');
            }
            exit;
        }
    }

    // НОВА ФУНКЦІЯ: Отримання інформації про поточного користувача
    public function getCurrentUser() {
        if ($this->isAuthenticated()) {
            return $_SESSION['user'];
        }
        return null;
    }
}

if ($_SERVER['SCRIPT_NAME'] === '/controllers/AuthController.php' ||
    basename($_SERVER['SCRIPT_NAME']) === 'AuthController.php') {

    header('Content-Type: application/json');
    $controller = new AuthController();
    $controller->handleRequest();
}
?>