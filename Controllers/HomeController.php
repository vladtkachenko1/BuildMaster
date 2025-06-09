<?php

require_once __DIR__ . '/../database/Database.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';


class HomeController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function index() {
        $data = [
            'room_types' => $this->getRoomTypes(),
            'featured_services' => $this->getFeaturedServices(),
            'stats' => $this->getStats()
        ];

        include 'Views/home/index.php';
    }

    private function getRoomTypes() {
        $stmt = $this->db->prepare("SELECT * FROM room_types WHERE is_active = 1 ORDER BY sort_order");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function getFeaturedServices() {
        $stmt = $this->db->prepare("
            SELECT sb.*, rt.name as room_type_name 
            FROM service_blocks sb 
            JOIN room_types rt ON sb.room_type_id = rt.id 
            WHERE sb.is_active = 1 
            ORDER BY sb.sort_order 
            LIMIT 6
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function getStats() {
        return [
            'completed_projects' => 150,
            'happy_clients' => 200,
            'years_experience' => 8,
            'team_members' => 12
        ];
    }

    public function contact() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $message = trim($_POST['message'] ?? '');

            if (empty($name) || empty($phone)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Ім’я та телефон є обов’язковими.']);
                return;
            }

            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'zigor274777@gmail.com';
                $mail->Password = 'kifg aflf kznj jobm';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port = 465;

                $mail->setFrom('zigor274777@gmail.com', 'BuildMaster');
                $mail->addAddress('zigor274777@gmail.com');

                if (!empty($email)) {
                    $mail->addReplyTo($email, $name);
                }

                $mail->CharSet = 'UTF-8';
                $mail->Encoding = 'base64';
                $mail->isHTML(false);
                $mail->Subject = "Нове повідомлення з сайту BildMaster від користувача $name";
                $mail->Body = "Ім’я: $name\nТелефон: $phone\nEmail: $email\n\nПовідомлення:\n$message";

                $mail->send();

                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Повідомлення відправлено успішно!']);
            } catch (Exception $e) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Помилка при відправці: ' . $mail->ErrorInfo
                ]);
            }
            return;
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Невірний метод запиту.']);
    }
}
