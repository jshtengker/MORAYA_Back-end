<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../core/Database.php'; // Include Database class
require_once __DIR__ . '/../model/Nurse_model.php'; // Include InputNurse model


header('Content-Type: application/json');

class Nurse {
    private $db;
    private $Nurse_model;

    public function __construct() {
        try {
            // Create database connection
            $database = new Database();
            $this->db = $database->getConnection();

            // Instantiate the InputNurse model
            $this->Nurse_model = new Nurse_Model($this->db);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()]);
            exit();
        }
    }

    public function get_bed_quantity() {
        try {
            $ruangan = $_POST['ruangan'] ?? null;

            if (empty($ruangan)) {
                echo json_encode(['status' => 'error', 'message' => 'Ruangan parameters are required']);
                return;
            }

            if (!in_array($ruangan, ['Mujair A', 'Mujair B', 'Mujair C', 'Nike', 'Payangka', 'Bomboya', 'Karper', 'Icu', 'Neonati'])) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid room specified']);
                http_response_code(400);
                return;
            }

            $result = $this->Nurse_model->getBedQuantity($ruangan);

            if ($result) {
                echo json_encode(['status' => 'success', 'jumlah bed' .' '. $ruangan => $result]);
                http_response_code(200);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No data found']);
                http_response_code(404);  // Not Found
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }
}

?>