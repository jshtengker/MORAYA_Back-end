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

    // inserting data
    public function insert($data) {
        try {
            // Define the table name directly
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
                "iiiiiiiiiiiiiiiis", // Adjusted parameter types (integer for numbers, string for tanggal)
                $data['user_id'], // Assuming user_id is provided
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
                $response = [
                    'status' => 'success',
                    'message' => 'Data inserted successfully'
                ];
                echo json_encode($response);
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
    
    
    

}


?>