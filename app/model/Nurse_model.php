<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../core/Database.php';


class Nurse_model {
    private $conn;

    // Constructor untuk menginisialisasi koneksi database
    public function __construct($db) {
        $this->conn = $db;
    }
    public function getBedQuantity($ruangan) {
        $query = "SELECT jumlah_bed FROM bed WHERE ruangan = ?";
        $stmt = $this->conn->prepare($query);
    
        if (!$stmt) {
            echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $this->conn->error]);
            return false;
        }
    
        $stmt->bind_param("s", $ruangan);
    
        if (!$stmt->execute()) {
            echo json_encode(['status' => 'error', 'message' => 'Execute failed: ' . $stmt->error]);
            return false;
        }
    
        $result = $stmt->get_result();
    
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return (int)$row['jumlah_bed'];
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No bed data found for the specified ruangan']);
            return false;
        }
    }

    // check data existence
    public function dataExists($date, $user_id) {
        // Check if user exists and get their ruangan
        $queryUser = "SELECT ruangan FROM users WHERE user_id = ? LIMIT 1";
        $stmtUser = $this->conn->prepare($queryUser);
    
        if (!$stmtUser) {
            error_log("User Query Error: " . $this->conn->error);
            return ['status' => 'error', 'message' => 'Database error fetching user'];
        }
    
        $stmtUser->bind_param("i", $user_id);
        $stmtUser->execute();
        $resultUser = $stmtUser->get_result();
        $userData = $resultUser->fetch_assoc();
    
        if (!$userData) {
            error_log("User ID Not Found: " . $user_id);
            return false; // User not found
        }
    
        $ruangan = $userData['ruangan'];
    
        // Check if data exists for the given date and ruangan
        $queryCheck = "SELECT 1 FROM input_nurse WHERE tanggal = ? AND user_id = ? LIMIT 1";
        $stmtCheck = $this->conn->prepare($queryCheck);
    
        if (!$stmtCheck) {
            error_log("Check Data Query Error: " . $this->conn->error);
            return ['status' => 'error', 'message' => 'Database error checking data'];
        }
    
        $stmtCheck->bind_param("si", $date, $user_id);
        $stmtCheck->execute();
        $resultCheck = $stmtCheck->get_result();
    
        if ($resultCheck->num_rows > 0) {
            return true; // Data exists
        } else {
            error_log("No Data Found for user_id: $user_id, date: $date");
            return false; // No data found
        }
    }

    // get all data by date and user_id
    public function getDataByDateAndUser($date, $user_id) {
        // Check if user exists and get their ruangan
        $queryUser = "SELECT ruangan FROM users WHERE user_id = ? LIMIT 1";
        $stmtUser = $this->conn->prepare($queryUser);
    
        if (!$stmtUser) {
            error_log("User Query Error: " . $this->conn->error);
            return ['status' => 'error', 'message' => 'Database error fetching user'];
        }
    
        $stmtUser->bind_param("i", $user_id);
        $stmtUser->execute();
        $resultUser = $stmtUser->get_result();
        $userData = $resultUser->fetch_assoc();
    
        if (!$userData) {
            error_log("User ID Not Found: " . $user_id);
            return ['status' => 'error', 'message' => 'User not found'];
        }
    
        $ruangan = $userData['ruangan'];
    
        // Fetch all data for the given date and user_id
        $queryData = "SELECT * FROM input_nurse WHERE tanggal = ? AND user_id = ?";
        $stmtData = $this->conn->prepare($queryData);
    
        if (!$stmtData) {
            error_log("Data Query Error: " . $this->conn->error);
            return ['status' => 'error', 'message' => 'Database error fetching data'];
        }
    
        $stmtData->bind_param("si", $date, $user_id);
        $stmtData->execute();
        $resultData = $stmtData->get_result();
    
        if ($resultData->num_rows > 0) {
            $data = [];
            while ($row = $resultData->fetch_assoc()) {
                $data[] = $row;
            }
            return ['status' => 'success', 'data' => $data];
        } else {
            error_log("No Data Found for user_id: $user_id, date: $date");
            return ['status' => 'error', 'message' => 'No data found for the given date and user ID'];
        }
    }
    
    

    // inserting data
    public function insert($data) {
        try {
            $table_name = "input_nurse";
    
            $query = "INSERT INTO $table_name (
                        user_id,
                        pasien_awal,
                        pasien_masuk,
                        pasien_pindahan,
                        pasien_dipindahkan,
                        pasien_hidup,
                        pasien_rujuk,
                        pasien_aps,
                        pasien_lain_lain,
                        pasien_meninggal_kurang_dari_48_jam,
                        pasien_meninggal_lebih_dari_48_jam,
                        pasien_lama_dirawat,
                        pasien_keluar_masuk_hari_sama,
                        kelas_1,
                        kelas_2,
                        kelas_3,
                        tanggal
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $this->conn->error]);
                return false;
            }
    
            $stmt->bind_param(
                "iiiiiiiiiiiiiiiis",
                $data['user_id'],
                $data['pasien_awal'],
                $data['pasien_masuk'],
                $data['pasien_pindahan'],
                $data['pasien_dipindahkan'],
                $data['pasien_hidup'],
                $data['pasien_rujuk'],
                $data['pasien_aps'],
                $data['pasien_lain_lain'],
                $data['pasien_meninggal_kurang_dari_48_jam'],
                $data['pasien_meninggal_lebih_dari_48_jam'],
                $data['pasien_lama_dirawat'],
                $data['pasien_keluar_masuk_hari_sama'],
                $data['kelas_1'],
                $data['kelas_2'],
                $data['kelas_3'],
                $data['tanggal']
            );
    
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Data inserted successfully']);
                return true;
            } else {
                // Handle duplicate key error
                if ($stmt->errno == 1062) {
                    echo json_encode([
                        'status' => 'warning',
                        'message' => 'Data already submitted for this date.'
                    ]);
                    return false;
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Execute failed: ' . $stmt->error]);
                    return false;
                }
            }
    
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
            return false;
        }
    }
    

    // updating data
    public function update($tanggal, $data) {
        try {
            // Define the table name
            $table_name = "input_nurse"; // Use your table name as needed
            
            // Prepare the SQL query
            $query = "UPDATE $table_name SET 
                        pasien_awal = ?,
                        pasien_masuk = ?,
                        pasien_pindahan = ?,
                        pasien_dipindahkan = ?,
                        pasien_hidup = ?,
                        pasien_rujuk = ?,
                        pasien_aps = ?,
                        pasien_lain_lain = ?,
                        pasien_meninggal_kurang_dari_48_jam = ?,
                        pasien_meninggal_lebih_dari_48_jam = ?,
                        pasien_lama_dirawat = ?,
                        pasien_keluar_masuk_hari_sama = ?,
                        kelas_1 = ?,
                        kelas_2 = ?,
                        kelas_3 = ?,
                        tanggal = ? 
                      WHERE tanggal = ? AND user_id = ?";
    
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $this->conn->error]);
                return false;
            }
    
            // Correct the number of parameters passed to match the placeholders
            $stmt->bind_param(
                "iiiiiiiiiiiiiiissi", // 18 bind parameters in total
                $data['pasien_awal'],
                $data['pasien_masuk'],
                $data['pasien_pindahan'],
                $data['pasien_dipindahkan'],
                $data['pasien_hidup'],
                $data['pasien_rujuk'],
                $data['pasien_aps'],
                $data['pasien_lain_lain'],
                $data['pasien_meninggal_kurang_dari_48_jam'],
                $data['pasien_meninggal_lebih_dari_48_jam'],
                $data['pasien_lama_dirawat'],
                $data['pasien_keluar_masuk_hari_sama'],
                $data['kelas_1'],
                $data['kelas_2'],
                $data['kelas_3'],
                $data['tanggal'],
                $tanggal, // the value for WHERE condition
                $data['user_id'] // the value for WHERE condition (user_id)
            );
    
            if ($stmt->execute()) {
                // Success response
                if ($stmt->affected_rows > 0) {
                    return true;
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'No rows were updated.']);
                    return false;
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Execute failed: ' . $stmt->error]);
                return false;
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
            return false;
        }
    }
    
    // get pasien masih dirawat kemarin menjadi pasien awal hari ini
    public function getYesterdayPatients($user_id, $date) {
        // Validate inputs
        if (empty($user_id) || empty($date)) {
            return ['status' => 'error', 'message' => 'User ID and Date are required'];
        }
    
        // Convert the given date to yesterday's date
        $yesterday = date('Y-m-d', strtotime($date . ' -1 day'));
    
        // SQL query to calculate patients still in treatment for yesterday (from the given date)
        $query = "
            SELECT 
                SUM(pasien_awal + pasien_masuk + pasien_pindahan) - 
                SUM(pasien_dipindahkan + pasien_hidup + pasien_rujuk + pasien_aps + pasien_lain_lain + pasien_meninggal_kurang_dari_48_jam + pasien_meninggal_lebih_dari_48_jam) 
                AS pasien_masih_dirawat
            FROM input_nurse
            WHERE tanggal = ? AND user_id = ?
        ";
    
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $yesterday, $user_id);
        
        if (!$stmt->execute()) {
            return ['status' => 'error', 'message' => 'Failed to fetch data'];
        }
    
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
    
        // Check if there is no matching data
        if (!$data || is_null($data['pasien_masih_dirawat'])) {
            return [
                'status' => 'error',
                'message' => 'No data found for the given date and user ID'
            ];
        }
    
        return [
            'status' => 'success',
            'requested_date' => $date,  // The original date user provided
            'fetching_data_from' => $yesterday,  // The actual date used in query
            'user_id' => $user_id,
            'pasien_masih_dirawat' => (int) $data['pasien_masih_dirawat']
        ];
    }

   
    
    
    
    
    
    
    
    
    
    

}


?>