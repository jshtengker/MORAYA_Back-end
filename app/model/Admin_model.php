<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../core/Database.php';


class Admin_model {
    private $conn;

    // Constructor untuk menginisialisasi koneksi database
    public function __construct($db) {
        $this->conn = $db;
    }

    // get bed quantity
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


    // updating data
    public function update($tanggal, $data, $ruangan) {
        try {
            // Step 1: Find all user_id(s) that belong to the given ruangan
            $query = "SELECT user_id FROM users WHERE ruangan = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $ruangan);
            $stmt->execute();
            $result = $stmt->get_result();
    
            // Check if any users are found for the given ruangan
            if ($result->num_rows === 0) {
                echo json_encode(['status' => 'error', 'message' => 'No users found for this ruangan']);
                return false;
            }
    
            // Fetch all user_id(s) associated with this ruangan
            $user_ids = [];
            while ($row = $result->fetch_assoc()) {
                $user_ids[] = $row['user_id'];
            }
            $stmt->close();
    
            // Step 2: Prepare the UPDATE query for the input_nurse table
            // We will update all records where the user_id is in the retrieved list
            $placeholders = implode(',', array_fill(0, count($user_ids), '?'));
            $query = "UPDATE input_nurse SET 
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
                      WHERE tanggal = ? AND user_id IN ($placeholders)";
    
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $this->conn->error]);
                return false;
            }
    
            // Bind the parameters dynamically
            $types = "iiiiiiiiiiiiiiiss" . str_repeat("i", count($user_ids)); 
            $params = [
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
                $tanggal
            ];
            $params = array_merge($params, $user_ids); // Append the user_id(s)
    
            $stmt->bind_param($types, ...$params);
    
            // Step 3: Execute the update query
            if ($stmt->execute()) {
                return true;
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Execute failed: ' . $stmt->error]);
                return false;
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
            return false;
        }
    }

     // update bed count
     public function update_bed($ruangan, $jumlah_bed) {
        // Validate jumlah_bed
        if (!is_numeric($jumlah_bed) || $jumlah_bed < 0) {
            return json_encode(['status' => 'error', 'message' => 'Invalid jumlah_bed. It must be a non-negative number.']);
        }
    
        // Validate ruangan input
        if (empty($ruangan)) {
            return json_encode(['status' => 'error', 'message' => 'Ruangan cannot be empty.']);
        }
    
        // Prepare the update query
        $query = "
            UPDATE bed
            SET jumlah_bed = ?
            WHERE ruangan = ?
        ";
    
        $stmt = $this->conn->prepare($query);
    
        if (!$stmt) {
            return json_encode(['status' => 'error', 'message' => 'Failed to prepare statement: ' . $this->conn->error]);
        }
    
        // Bind parameters
        $stmt->bind_param('is', $jumlah_bed, $ruangan);
    
        // Execute the query
        if (!$stmt->execute()) {
            return json_encode(['status' => 'error', 'message' => 'Query execution failed: ' . $stmt->error]);
        }
    
        // Check if any row was affected
        if ($stmt->affected_rows > 0) {
            return json_encode(['status' => 'success', 'message' => 'Jumlah_bed updated successfully.']);
        } else {
            return json_encode(['status' => 'error', 'message' => 'No record updated. Check if the ruangan exists or make sure the amount of bed is not the same as the current value in database.']);
        }
    }
    
    
    
    

}


?>