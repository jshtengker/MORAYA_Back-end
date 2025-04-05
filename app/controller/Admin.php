<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../core/Database.php'; // Include Database class
require_once __DIR__ . '/../model/Admin_model.php'; // Include Admin model


header('Content-Type: application/json');

class Admin {
    private $db;
    private $Admin_model;

    public function __construct() {
        try {
            // Create database connection
            $database = new Database();
            $this->db = $database->getConnection();

            // Instantiate the Admin model
            $this->Admin_model = new Admin_Model($this->db);
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

            $result = $this->Admin_model->getBedQuantity($ruangan);

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

    // updating data
    public function update_admin() {
        try {
            // Debug incoming POST data
            error_log('POST Data: ' . print_r($_POST, true));
    
            // Get ruangan and tanggal from the request
            $ruangan = isset($_POST['ruangan']) ? trim($_POST['ruangan']) : null;
            $tanggal = isset($_POST['tanggal']) ? trim($_POST['tanggal']) : date('Y-m-d'); // Default to current date
    
            // Validate required fields
            if (empty($ruangan)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Ruangan harus diberikan']);
                return;
            }
    
            // Prepare the data for updating
            $data = [
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
    
            // Call the model function to perform the update
            if ($this->Admin_model->update($tanggal, $data, $ruangan)) {
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

    // updating bed count
    public function update_bed_count() {
        try {
            // Retrieve parameters from POST request
            $ruangan = $_POST['ruangan'] ?? null;
            $jumlah_bed = $_POST['jumlah_bed'] ?? null;
    
            // Validate parameters
            if (empty($ruangan) || $jumlah_bed === null) {
                echo json_encode(['status' => 'error', 'message' => 'Ruangan and jumlah_bed parameters are required.']);
                http_response_code(400); // Bad Request
                return;
            }
    
            if (!is_numeric($jumlah_bed) || $jumlah_bed < 0) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid jumlah_bed. It must be a non-negative number.']);
                http_response_code(400); // Bad Request
                return;
            }
    
            // Call the model function
            $result = $this->Admin_model->update_bed($ruangan, intval($jumlah_bed));
    
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
   
    
    
    
}

?>