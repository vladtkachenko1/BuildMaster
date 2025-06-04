<?php

namespace BuildMaster\Controllers;

class ServiceCalculatorController
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    /**
     * Отримує всі блоки послуг для конкретного типу кімнати
     */
    public function getServiceBlocksByRoomType($roomTypeId)
    {
        try {
            $stmt = $this->database->prepare("
                SELECT id, name, slug, description, sort_order 
                FROM service_blocks 
                WHERE room_type_id = ? 
                ORDER BY sort_order ASC
            ");
            $stmt->execute([$roomTypeId]);
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            return $result;
        } catch (\Exception $e) {
            error_log("Error getting service blocks: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Отримує всі послуги для конкретного блоку послуг
     */
    public function getServicesByBlock($serviceBlockId)
    {
        try {
            $stmt = $this->database->prepare("
                SELECT id, name, slug, description, price_per_sqm 
                FROM services 
                WHERE service_block_id = ? 
                ORDER BY name ASC
            ");
            $stmt->execute([$serviceBlockId]);
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            return $result;
        } catch (\Exception $e) {
            error_log("Error getting services: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Отримує всі послуги згруповані по блокам для конкретного типу кімнати
     */
    public function getGroupedServicesByRoomType($roomTypeId)
    {
        try {
            $stmt = $this->database->prepare("
            SELECT 
                sb.id as block_id,
                sb.name as block_name,
                sb.slug as block_slug,
                sb.description as block_description,
                sb.sort_order as block_sort_order,
                s.id as service_id,
                s.name as service_name,
                s.slug as service_slug,
                s.description as service_description,
                s.price_per_sqm,
                a.id as area_id,
                a.name as area_name,
                a.slug as area_slug,
                a.area_type
            FROM service_blocks sb
            LEFT JOIN services s ON sb.id = s.service_block_id
            LEFT JOIN service_area sa ON s.id = sa.service_id
            LEFT JOIN areas a ON sa.area_id = a.id
            WHERE sb.room_type_id = ?
            ORDER BY a.id ASC, sb.sort_order ASC, s.name ASC
        ");
            $stmt->execute([$roomTypeId]);
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $groupedByArea = [];

            foreach ($result as $row) {
                $areaId = $row['area_id'];
                $blockId = $row['block_id'];

                // Пропускаємо рядки без area_id (послуги без прив'язки до областей)
                if (!$areaId) {
                    continue;
                }

                // Створюємо групу для області якщо її ще немає
                if (!isset($groupedByArea[$areaId])) {
                    $groupedByArea[$areaId] = [
                        'area_id' => $row['area_id'],
                        'area_name' => $row['area_name'],
                        'area_slug' => $row['area_slug'],
                        'area_type' => $row['area_type'],
                        'service_blocks' => []
                    ];
                }

                // Створюємо блок послуг якщо його ще немає в цій області
                if (!isset($groupedByArea[$areaId]['service_blocks'][$blockId])) {
                    $groupedByArea[$areaId]['service_blocks'][$blockId] = [
                        'id' => $row['block_id'],
                        'name' => $row['block_name'],
                        'slug' => $row['block_slug'],
                        'description' => $row['block_description'],
                        'sort_order' => $row['block_sort_order'],
                        'services' => []
                    ];
                }

                // Додаємо послугу якщо вона існує
                if ($row['service_id']) {
                    // Перевіряємо чи послуга вже додана (щоб уникнути дублікатів)
                    $serviceExists = false;
                    foreach ($groupedByArea[$areaId]['service_blocks'][$blockId]['services'] as $existingService) {
                        if ($existingService['id'] == $row['service_id']) {
                            $serviceExists = true;
                            break;
                        }
                    }

                    if (!$serviceExists) {
                        $groupedByArea[$areaId]['service_blocks'][$blockId]['services'][] = [
                            'id' => $row['service_id'],
                            'name' => $row['service_name'],
                            'slug' => $row['service_slug'],
                            'description' => $row['service_description'],
                            'price_per_sqm' => $row['price_per_sqm'],
                            'area_type' => $row['area_type'] // Додаємо тип області для розрахунку площі
                        ];
                    }
                }
            }

            // Конвертуємо асоціативні масиви в індексовані
            $result = [];
            foreach ($groupedByArea as $area) {
                $area['service_blocks'] = array_values($area['service_blocks']);
                $result[] = $area;
            }

            return $result;

        } catch (\Exception $e) {
            error_log("Error getting grouped services: " . $e->getMessage());
            return [];
        }
    }

// Додатковий метод для отримання правильної площі на основі типу області
    public function getAreaByType($roomId, $areaType)
    {
        try {
            $columnMap = [
                'floor' => 'floor_area',
                'walls' => 'wall_area',
                'ceiling' => 'ceiling_area'
            ];

            if (!isset($columnMap[$areaType])) {
                throw new \Exception("Unknown area type: " . $areaType);
            }

            $column = $columnMap[$areaType];

            $stmt = $this->database->prepare("
            SELECT {$column} as area 
            FROM rooms 
            WHERE id = ?
        ");
            $stmt->execute([$roomId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            return $result ? (float)$result['area'] : 0;

        } catch (\Exception $e) {
            error_log("Error getting area by type: " . $e->getMessage());
            return 0;
        }
    }
    /**
     * Розрахунок загальної вартості на основі вибраних послуг
     */
    public function calculateTotal($selectedServices, $wallArea, $roomArea)
    {
        $total = 0;

        foreach ($selectedServices as $serviceId) {
            try {
                $stmt = $this->database->prepare("
                    SELECT price_per_sqm 
                    FROM services 
                    WHERE id = ?
                ");
                $stmt->execute([$serviceId]);
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($result) {
                    // Тут можна додати логіку для різних типів розрахунків
                    // Наприклад, деякі послуги рахуються від площі стін, інші від площі кімнати
                    $total += $result['price_per_sqm'] * $wallArea;
                }
            } catch (\Exception $e) {
                error_log("Error calculating service cost: " . $e->getMessage());
            }
        }

        return $total;
    }

    /**
     * API метод для отримання послуг в JSON форматі
     */
    public function getServicesJson()
    {
        header('Content-Type: application/json');

        $roomTypeId = $_GET['room_type_id'] ?? null;

        if (!$roomTypeId) {
            http_response_code(400);
            echo json_encode(['error' => 'room_type_id is required']);
            return;
        }

        $services = $this->getGroupedServicesByRoomType($roomTypeId);
        echo json_encode($services);
    }

    /**
     * API метод для розрахунку вартості
     */
    public function calculateJson()
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);

        $selectedServices = $input['services'] ?? [];
        $wallArea = floatval($input['wall_area'] ?? 0);
        $roomArea = floatval($input['room_area'] ?? 0);

        if (empty($selectedServices) || $wallArea <= 0 || $roomArea <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input data']);
            return;
        }

        $total = $this->calculateTotal($selectedServices, $wallArea, $roomArea);

        echo json_encode([
            'total' => $total,
            'wall_area' => $wallArea,
            'room_area' => $roomArea,
            'services_count' => count($selectedServices)
        ]);
    }
}