<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../core/Database.php'; // Include Database class
require_once __DIR__ . '/../model/Guest_model.php'; // Include InputNurse model


header('Content-Type: application/json');

class Guest {
    private $db;
    private $Guest_model;

    public function __construct() {
        try {
            // Create database connection
            $database = new Database();
            $this->db = $database->getConnection();

            // Instantiate the InputNurse model
            $this->Guest_model = new Guest_Model($this->db);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()]);
            exit();
        }
    }

     // Get indicator values based on ruangan and tanggal daily
     public function get_daily_indicators_ruangan() {
        try {
            // Get parameters from the request
            $ruangan = $_POST['ruangan'] ?? null;
            $tanggal = $_POST['tanggal'] ?? date('Y-m-d'); // Default to today

            // Validate required fields
            if (empty($ruangan)) {
                echo json_encode(['status' => 'error', 'message' => 'Ruangan is required']);
                return;
            }

            // Validate if ruangan exists in predefined list
            $valid_ruangan = ['Mujair A', 'Mujair B', 'Mujair C', 'Nike', 'Payangka', 'Bomboya', 'Karper', 'Icu', 'Neonati'];
            if (!in_array($ruangan, $valid_ruangan)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid room specified']);
                http_response_code(400);
                return;
            }

            // Fetch indicator values from the model
            $result = $this->Guest_model->dailyIndicatorsRooms($ruangan, $tanggal);

            if (!$result || isset($result['status']) && $result['status'] === 'error') {
                echo json_encode([
                    'status' => 'error',
                    'message' => $result['message'] ?? 'No data found for ' . $tanggal . '. Data might not be updated yet.'
                ]);
                http_response_code(404);
                return;
            }
            
            echo json_encode([
                'status' => 'success',
                'indicators' => $result
            ]);

        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

     // Fetch monthly indicators for the given ruangan and month
     public function get_monthly_indicators_ruangan() {
        try {
            // Get parameters from POST
            $ruangan = isset($_POST['ruangan']) ? trim($_POST['ruangan']) : null;
            $month = isset($_POST['month']) ? trim($_POST['month']) : null; // Month as '08' for August, etc.

            if (empty($ruangan)) {
                echo json_encode(['status' => 'error', 'message' => 'Ruangan is required']);
                return;
            }

            if (empty($month)) {
                echo json_encode(['status' => 'error', 'message' => 'Month is required']);
                return;
            }

            // Fetch indicators from model
            $result = $this->Guest_model->monthlyIndicatorsRooms($ruangan, $month);

            if ($result['status'] === 'error') {
                // Return error if no data found
                echo json_encode($result);
                http_response_code(404);
                return;
            }

            // Return success with the calculated indicators
            echo json_encode($result);

        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

     // Fetch range indicators by given ruangan, start, end date
    public function get_range_indicators_ruangan() {
        try {
            // Get parameters from POST request
            $ruangan = isset($_POST['ruangan']) ? trim($_POST['ruangan']) : null;
            $start_date = isset($_POST['start_date']) ? trim($_POST['start_date']) : null; // Format: 'YYYY-MM-DD'
            $end_date = isset($_POST['end_date']) ? trim($_POST['end_date']) : null; // Format: 'YYYY-MM-DD'

            // Validate required parameters
            if (empty($ruangan)) {
                echo json_encode(['status' => 'error', 'message' => 'Ruangan is required']);
                return;
            }

            if (empty($start_date)) {
                echo json_encode(['status' => 'error', 'message' => 'Start date is required']);
                return;
            }

            if (empty($end_date)) {
                echo json_encode(['status' => 'error', 'message' => 'End date is required']);
                return;
            }

            // Validate date format
            if (!strtotime($start_date) || !strtotime($end_date)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid date format']);
                return;
            }

            // Fetch indicators from the model using the provided date range
            $result = $this->Guest_model->dateRangeIndicatorsRooms($ruangan, $start_date, $end_date);

            // Check if the result contains any errors
            if ($result['status'] === 'error') {
                // Return error response if no data found or any other issue
                echo json_encode($result);
                http_response_code(404);
                return;
            }

            // Return the calculated indicators
            echo json_encode($result);

        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }
    
    
}

?>