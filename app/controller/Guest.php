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
            $month = isset($_POST['month']) ? trim($_POST['month']) : null; // e.g., '08'
            $year = isset($_POST['year']) ? trim($_POST['year']) : null; // default to current year if not provided
    
            if (empty($ruangan)) {
                echo json_encode(['status' => 'error', 'message' => 'Ruangan is required']);
                return;
            }
    
            if (empty($month)) {
                echo json_encode(['status' => 'error', 'message' => 'Month is required']);
                return;
            }
    
            // if (!is_numeric($year) || strlen($year) !== 4) {
            //     echo json_encode(['status' => 'error', 'message' => 'Year must be a 4-digit number']);
            //     return;
            // }

            if (empty($year)) {
                echo json_encode(['status' => 'error', 'message' => 'Year is required']);
                return;
            }
    
            // Fetch indicators from model (now passing year as well)
            $result = $this->Guest_model->monthlyIndicatorsRooms($ruangan, $month, $year);
    
            if ($result['status'] === 'error') {
                echo json_encode($result);
                http_response_code(404);
                return;
            }
    
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
    
            // Check if end_date is before start_date
            if (strtotime($end_date) < strtotime($start_date)) {
                echo json_encode(['status' => 'error', 'message' => 'Start date tidak boleh setelah end date']);
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
    

    // Fetch stats rs daily
    public function get_stats_data_rs_daily() {
        try {
            $date = $_POST['tanggal'] ?? null;
            if (empty($date)) {
                echo json_encode(['status' => 'error', 'message' => 'Date parameter is required']);
                http_response_code(400); // Bad Request
                return;
            }

            $result = $this->Guest_model->calculate_stats_rs_daily($date);

            if ($result) {
                echo json_encode(['status' => 'success', 'data' => $result]);
                http_response_code(200);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No data found']);
                http_response_code(404); // Not Found
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
            http_response_code(500); // Internal Server Error
        }
    }

    // Fetch stats rs monthly
    public function get_stats_data_rs_monthly() {
        try {
            // Retrieve the month and year parameters from the POST data
            $month = $_POST['month'] ?? null;
            $year = $_POST['year'] ?? null;
    
            // Validate inputs
            if (empty($month)) {
                echo json_encode(['status' => 'error', 'message' => 'Month parameter is required']);
                return;
            }
    
            if (empty($year)) {
                echo json_encode(['status' => 'error', 'message' => 'Year parameter is required']);
                return;
            }
    
            // Call the model method with month and year
            $result = $this->Guest_model->calculateStatsMonthly($month, $year);
    
            // Check and return result
            if ($result) {
                // calculateStatsMonthly already echoes JSON, so no need to echo again
                http_response_code(200);  // OK
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No data found. Check if all data for that month is already inserted!']);
                http_response_code(404);  // Not Found
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
            http_response_code(500);  // Internal Server Error
        }
    }
    
    // Fetch stats rs by range date
    public function get_stats_data_rs_range() {
        // Retrieve input from the request (e.g., from $_POST)
        $startDate = isset($_POST['start_date']) ? $_POST['start_date'] : null;
        $endDate = isset($_POST['end_date']) ? $_POST['end_date'] : null;

        // Validate inputs
        if (empty($startDate) || empty($endDate)) {
            echo json_encode(['status' => 'error', 'message' => 'Both start_date and end_date are required.']);
            return false;
        }

        // Call the model function to calculate statistics
        $result = $this->Guest_model->calculateStatsInRange($startDate, $endDate);

        // If the model function fails, it already outputs the error, so no additional handling is needed
        if (!$result || $result['status'] === 'error') {
            echo json_encode($result);
            return false;
        }

        // Return success response (already handled inside model)
        echo json_encode($result, JSON_PRETTY_PRINT);
        return true;
    }

    // fetch data by indicators daily
    public function get_stats_by_indicator() {
        try {
            // Check if date and indicator are provided in the request
            $date = $_POST['tanggal'] ?? null;
            $indicator = $_POST['indicator'] ?? null;

            if (empty($date) || empty($indicator)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Date and indicator parameters are required'
                ]);
                http_response_code(400); // Bad Request
                return;
            }

            // Call the model function to fetch data
            $result = $this->Guest_model->fetch_stats_by_indicator($date, $indicator);

            // Check if data was retrieved successfully
            if ($result) {
                echo $result;
                http_response_code(200); // OK
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No data found for the given date and indicator'
                ]);
                http_response_code(404); // Not Found
            }

        } catch (Exception $e) {
            // Handle any exceptions
            echo json_encode([
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ]);
            http_response_code(500); // Internal Server Error
        }
    }

    // fetch data by indicators monthly
    public function get_monthly_stats_by_indicator() {
        try {
            // Retrieve parameters from POST request
            $month = $_POST['month'] ?? null;
            $year = $_POST['year'] ?? null;
            $indicator = $_POST['indicator'] ?? null;
    
            // Validate parameters
            if (empty($month) || empty($year) || empty($indicator)) {
                echo json_encode(['status' => 'error', 'message' => 'Month, year, and indicator parameters are required.']);
                http_response_code(400); // Bad Request
                return;
            }
    
            // Ensure valid numeric month and year
            if (!is_numeric($month) || !is_numeric($year) || $month < 1 || $month > 12 || $year < 2000) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid month or year format.']);
                http_response_code(400);
                return;
            }
    
            // Call the model function
            $result = $this->Guest_model->fetch_monthly_stats_by_indicator($month, $year, $indicator);
    
            // Decode the result to check the status
            $decodedResult = json_decode($result, true);
    
            if (isset($decodedResult['status']) && $decodedResult['status'] === 'success') {
                echo $result;
                http_response_code(200); // OK
            } else {
                echo $result; // Return the error message from the model
                http_response_code(404); // Not Found
            }
        } catch (Exception $e) {
            // Catch and return any exceptions
            echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
            http_response_code(500); // Internal Server Error
        }
    }

    // fetch data by indicators based on range date
    public function get_stats_indicator_by_range() {
        try {
            $startDate = $_POST['start_date'] ?? null;
            $endDate = $_POST['end_date'] ?? null;
            $indicator = $_POST['indicator'] ?? null;
    
            if (empty($startDate) || empty($endDate) || empty($indicator)) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Start date, end date, and indicator are required.'
                ]);
                return;
            }
    
            $result = $this->Guest_model->fetchStatsByRange($startDate, $endDate, $indicator);
    
            if (isset($result['error'])) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => $result['error']
                ]);
                return;
            }
    
            echo json_encode([
                'status' => 'success',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'indicator' => strtoupper($indicator),
                'data' => $result
            ], JSON_PRETTY_PRINT);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Exception occurred: ' . $e->getMessage()
            ]);
        }
    }
    
    


}

?>