<?php

require_once 'database/Database.php';

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
            $name = isset($_POST['name']) ? $_POST['name'] : '';
            $email = isset($_POST['email']) ? $_POST['email'] : '';
            $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
            $message = isset($_POST['message']) ? $_POST['message'] : '';

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Повідомлення відправлено!']);
            return;
        }
    }
}
