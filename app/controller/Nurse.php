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

    // get bed quantity from table bed
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

    // check data existence
    public function check_data_exist() {
        // Get input from request (assuming POST method)
        $date = isset($_POST['date']) ? $_POST['date'] : null;
        $user_id = isset($_POST['user_id']) ? $_POST['user_id'] : null;

        // Validate inputs
        if (empty($date) || empty($user_id)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Date and user_id are required.'
            ]);
            return;
        }

        // Call the model's dataExists method
        $exists = $this->Nurse_model->dataExists($date, $user_id);

        // Handle cases when model returns an error
        if (is_array($exists) && isset($exists['status']) && $exists['status'] === 'error') {
            echo json_encode($exists);
            return;
        }

        // Return success response with existence check
        echo json_encode([
            'status' => 'success',
            'data' => ['exists' => $exists]
        ]);
    }

    // get data by date and user_id
    public function get_input_data() {
        // Get input from request (assuming POST method)
        $date = isset($_POST['date']) ? $_POST['date'] : null;
        $user_id = isset($_POST['user_id']) ? $_POST['user_id'] : null;

        // Validate inputs
        if (empty($date) || empty($user_id)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Date and User ID are required.'
            ]);
            return;
        }

        // Call the model's method to fetch data
        $response = $this->Nurse_model->getDataByDateAndUser($date, $user_id);

        // Check if data is found
        if (empty($response)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'No data found for the given date and user ID.'
            ]);
        } else {
            // Return the data if found
            echo json_encode($response);
        }
    }

    // inserting data
    public function insert_nurse() {
        try {
            // Debug incoming POST data
            error_log('POST Data: ' . print_r($_POST, true));
    
            // Get user_id from session (or request if needed)
            $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : null;
            $tanggal = isset($_POST['tanggal']) ? trim($_POST['tanggal']) : date('Y-m-d'); // Use current date if not provided
    
            // Debugging user_id
            error_log('User ID Diterima: ' . $user_id);
            error_log('Tanggal Diterima: ' . $tanggal);
    
            // Validate required fields
            if (empty($user_id)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'User ID harus diberikan']);
                return;
            }
    
            // Prepare data for insertion
            $data = [
                'user_id' => $user_id, // No need for ruangan
                'pasien_awal' => filter_var($_POST['pasien_awal'], FILTER_SANITIZE_NUMBER_INT),
                'pasien_masuk' => filter_var($_POST['pasien_masuk'], FILTER_SANITIZE_NUMBER_INT),
                'pasien_pindahan' => filter_var($_POST['pasien_pindahan'], FILTER_SANITIZE_NUMBER_INT),
                'pasien_dipindahkan' => filter_var($_POST['pasien_dipindahkan'], FILTER_SANITIZE_NUMBER_INT),
                'pasien_hidup' => filter_var($_POST['pasien_hidup'], FILTER_SANITIZE_NUMBER_INT),
                'pasien_rujuk' => filter_var($_POST['pasien_rujuk'], FILTER_SANITIZE_NUMBER_INT),
                'pasien_aps' => filter_var($_POST['pasien_aps'], FILTER_SANITIZE_NUMBER_INT),
                'pasien_lain_lain' => filter_var($_POST['pasien_lain_lain'], FILTER_SANITIZE_NUMBER_INT),
                'pasien_meninggal_kurang_dari_48_jam' => filter_var($_POST['pasien_meninggal_kurang_dari_48_jam'], FILTER_SANITIZE_NUMBER_INT),
                'pasien_meninggal_lebih_dari_48_jam' => filter_var($_POST['pasien_meninggal_lebih_dari_48_jam'], FILTER_SANITIZE_NUMBER_INT),
                'pasien_lama_dirawat' => filter_var($_POST['pasien_lama_dirawat'], FILTER_SANITIZE_NUMBER_INT),
                'pasien_keluar_masuk_hari_sama' => filter_var($_POST['pasien_keluar_masuk_hari_sama'], FILTER_SANITIZE_NUMBER_INT),
                'kelas_1' => filter_var($_POST['kelas_1'], FILTER_SANITIZE_NUMBER_INT),
                'kelas_2' => filter_var($_POST['kelas_2'], FILTER_SANITIZE_NUMBER_INT),
                'kelas_3' => filter_var($_POST['kelas_3'], FILTER_SANITIZE_NUMBER_INT),
                'tanggal' => $tanggal,
            ];
    
            // Debugging inserted data
            error_log('Data yang akan di-insert: ' . print_r($data, true));
    
            // Insert into input_nurses table
            if ($this->Nurse_model->insert($data)) {
                echo json_encode(['status' => 'success', 'message' => 'Data inserted successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Data insertion failed']);
            }
    
        } catch (Exception $e) {
            error_log('Exception: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    // updating data
    public function update_nurse() {
        try {
            // Debug incoming POST data
            error_log('POST Data: ' . print_r($_POST, true));
    
            // Get user_id from the POST data
            $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : null;
            $tanggal = isset($_POST['tanggal']) ? trim($_POST['tanggal']) : date('Y-m-d'); // Default to current date if not provided
    
            // Debugging user_id and tanggal
            error_log('User ID Diterima: ' . $user_id);
            error_log('Tanggal Diterima: ' . $tanggal);
    
            // Validate required fields
            if (empty($user_id)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'User ID harus diberikan']);
                return;
            }
    
            // Prepare the data for updating
            $data = [
                'user_id' => $user_id, 
                'pasien_awal' => filter_var($_POST['pasien_awal'], FILTER_SANITIZE_NUMBER_INT),
                'pasien_masuk' => filter_var($_POST['pasien_masuk'], FILTER_SANITIZE_NUMBER_INT),
                'pasien_pindahan' => filter_var($_POST['pasien_pindahan'], FILTER_SANITIZE_NUMBER_INT),
                'pasien_dipindahkan' => filter_var($_POST['pasien_dipindahkan'], FILTER_SANITIZE_NUMBER_INT),
                'pasien_hidup' => filter_var($_POST['pasien_hidup'], FILTER_SANITIZE_NUMBER_INT),
                'pasien_rujuk' => filter_var($_POST['pasien_rujuk'], FILTER_SANITIZE_NUMBER_INT),
                'pasien_aps' => filter_var($_POST['pasien_aps'], FILTER_SANITIZE_NUMBER_INT),
                'pasien_lain_lain' => filter_var($_POST['pasien_lain_lain'], FILTER_SANITIZE_NUMBER_INT),
                'pasien_meninggal_kurang_dari_48_jam' => filter_var($_POST['pasien_meninggal_kurang_dari_48_jam'], FILTER_SANITIZE_NUMBER_INT),
                'pasien_meninggal_lebih_dari_48_jam' => filter_var($_POST['pasien_meninggal_lebih_dari_48_jam'], FILTER_SANITIZE_NUMBER_INT),
                'pasien_lama_dirawat' => filter_var($_POST['pasien_lama_dirawat'], FILTER_SANITIZE_NUMBER_INT),
                'pasien_keluar_masuk_hari_sama' => filter_var($_POST['pasien_keluar_masuk_hari_sama'], FILTER_SANITIZE_NUMBER_INT),
                'kelas_1' => filter_var($_POST['kelas_1'], FILTER_SANITIZE_NUMBER_INT),
                'kelas_2' => filter_var($_POST['kelas_2'], FILTER_SANITIZE_NUMBER_INT),
                'kelas_3' => filter_var($_POST['kelas_3'], FILTER_SANITIZE_NUMBER_INT),
                'tanggal' => $tanggal, // Use the provided date or default
            ];
    
            // Debugging data before update
            error_log('Data yang akan di-update: ' . print_r($data, true));
    
            // Call the model to perform the update
            if ($this->Nurse_model->update($tanggal, $data)) {
                // Success response
                echo json_encode(['status' => 'success', 'message' => 'Data updated successfully']);
            } else {
                // Failure response
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Data update failed']);
            }
    
        } catch (Exception $e) {
            // Exception handling
            error_log('Exception: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    // mengambil pasien yang masih dirawat kemarin sebagai pasien awal hari ini
    public function get_pasien_masih_dirawat_kemarin() {
        // Get input from request (assuming POST method)
        $date = isset($_POST['date']) ? $_POST['date'] : null;
        $user_id = isset($_POST['user_id']) ? $_POST['user_id'] : null;
    
        // Validate inputs
        if (empty($date) || empty($user_id)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Date and User ID are required.'
            ]);
            return;
        }
    
        // Pass the exact date to the model (model handles conversion)
        $response = $this->Nurse_model->getYesterdayPatients($user_id, $date);
    
        // Check if data is found correctly
        if (!isset($response['pasien_masih_dirawat'])) {
            echo json_encode([
                'status' => 'error',
                'message' => 'No data found for the given date and user ID.'
            ]);
        } else {
            // Return the data if found
            echo json_encode($response);
        }
    }
    
    
    
}

?>