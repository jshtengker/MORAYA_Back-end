<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../libraries/fpdf.php';



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

    // get data input 
    public function getDataByDateAndRuangan($date, $ruangan) {
        try {
            // Step 1: Get user_id(s) by ruangan
            $query = "SELECT user_id FROM users WHERE ruangan = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $ruangan);
            $stmt->execute();
            $result = $stmt->get_result();
    
            if ($result->num_rows === 0) {
                return ['status' => 'error', 'message' => 'No users found for this ruangan'];
            }
    
            $user_ids = [];
            while ($row = $result->fetch_assoc()) {
                $user_ids[] = $row['user_id'];
            }
            $stmt->close();
    
            // Step 2: Use the user_ids to get data from input_nurse for the given date
            $placeholders = implode(',', array_fill(0, count($user_ids), '?'));
            $types = str_repeat('i', count($user_ids));
    
            $query = "SELECT * FROM input_nurse WHERE tanggal = ? AND user_id IN ($placeholders)";
            $stmt = $this->conn->prepare($query);
    
            // Merge date + user_ids for bind_param
            $bind_types = 's' . $types;
            $params = array_merge([$date], $user_ids);
    
            $stmt->bind_param($bind_types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
    
            if ($result->num_rows > 0) {
                $data = [];
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
                return ['status' => 'success', 'data' => $data];
            } else {
                return ['status' => 'error', 'message' => 'No data found for the given date and ruangan'];
            }
    
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // download input data as pdf
    public function exportTableToPDF($ruangan, $month) {
        ob_start();

        try {
            // Fetch user_ids for ruangan
            $queryUsers = "SELECT user_id FROM users WHERE ruangan = ?";
            $stmtUsers = $this->conn->prepare($queryUsers);
            $stmtUsers->bind_param("s", $ruangan);
            $stmtUsers->execute();
            $resultUsers = $stmtUsers->get_result();

            $userIds = [];
            while ($row = $resultUsers->fetch_assoc()) {
                $userIds[] = $row['user_id'];
            }

            if (empty($userIds)) {
                ob_end_clean();
                echo json_encode(['status' => 'error', 'message' => 'No users found for the specified ruangan.']);
                return;
            }

            $placeholders = implode(',', array_fill(0, count($userIds), '?'));
            $types = str_repeat('i', count($userIds)) . 'i';

            $query = "SELECT * FROM input_nurse WHERE user_id IN ($placeholders) AND MONTH(tanggal) = ? ORDER BY tanggal ASC";
            $stmt = $this->conn->prepare($query);
            $params = array_merge($userIds, [$month]);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                ob_end_clean();
                echo json_encode(['status' => 'error', 'message' => 'No data found for the specified month.']);
                return;
            }

            $pdf = new FPDF('L', 'mm', 'A3');
            $pdf->SetMargins(10, 10, 10);
            $pdf->SetAutoPageBreak(true, 10); // Trim bottom space
            $pdf->AddPage();
            $pdf->SetDrawColor(0);      // Consistent border color
            $pdf->SetLineWidth(0.2);    // Consistent border thickness
            $pdf->SetFont('Arial', 'B', 12);

            $monthName = DateTime::createFromFormat('!m', $month)->format('F');
            $pdf->Cell(0, 10, "Rekap input bulan $monthName untuk ruangan $ruangan", 0, 1, 'C');
            $pdf->Ln(10);

            $headers = [
                "tanggal", "pasien_awal", "pasien_masuk", "pasien_pindahan", "pasien_dipindahkan",
                "pasien_hidup", "pasien_rujuk", "pasien_aps", "pasien_lain_lain",
                "pasien_meninggal_kurang_dari_48_jam", "pasien_meninggal_lebih_dari_48_jam",
                "jumlah_pasien_masuk_ruangan", "jumlah_pasien_keluar_hidup",
                "jumlah_pasien_keluar_meninggal", "total_pasien_keluar",
                "pasien_yang_masih_dirawat", "jumlah_hari_perawatan"
            ];

            $headerLabels = [
                "tanggal" => "Tanggal",
                "pasien_awal" => "Pasien\nAwal",
                "pasien_masuk" => "Pasien\nMasuk",
                "pasien_pindahan" => "Pasien\nPindahan",
                "pasien_dipindahkan" => "Pasien\nDipindahkan",
                "pasien_hidup" => "Pasien\nHidup",
                "pasien_rujuk" => "Pasien\nRujuk",
                "pasien_aps" => "Pasien\nAPS",
                "pasien_lain_lain" => "Pasien\nLain-lain",
                "pasien_meninggal_kurang_dari_48_jam" => "Meninggal\n kurang dari 48 Jam",
                "pasien_meninggal_lebih_dari_48_jam" => "Meninggal\n lebih dari 48 Jam",
                "jumlah_pasien_masuk_ruangan" => "Jumlah\nMasuk",
                "jumlah_pasien_keluar_hidup" => "Keluar\nHidup",
                "jumlah_pasien_keluar_meninggal" => "Keluar\nMeninggal",
                "total_pasien_keluar" => "Total\nKeluar",
                "pasien_yang_masih_dirawat" => "Masih\nDirawat",
                "jumlah_hari_perawatan" => "Hari\nPerawatan"
            ];

            $pdf->SetFont('Arial', 'B', 8);
            $minWidth = 20;
            $maxWidth = 45;
            $colWidths = [];

            foreach ($headers as $header) {
                $label = $headerLabels[$header];
                $labelLines = explode("\n", $label);
                $maxLine = max(array_map(fn($line) => $pdf->GetStringWidth($line), $labelLines));
                $colWidths[$header] = max($minWidth, min($maxLine + 6, $maxWidth));
            }

            $dataRows = [];

            while ($row = $result->fetch_assoc()) {
                $tanggal = $row['tanggal'];
                $awal = (int)$row['pasien_awal'];
                $masuk = (int)$row['pasien_masuk'];
                $pindahan = (int)$row['pasien_pindahan'];
                $dipindahkan = (int)$row['pasien_dipindahkan'];
                $hidup = (int)$row['pasien_hidup'];
                $rujuk = (int)$row['pasien_rujuk'];
                $aps = (int)$row['pasien_aps'];
                $lain = (int)$row['pasien_lain_lain'];
                $meninggal_48 = (int)$row['pasien_meninggal_kurang_dari_48_jam'];
                $meninggal_lebih = (int)$row['pasien_meninggal_lebih_dari_48_jam'];

                $jumlah_masuk = $awal + $masuk + $pindahan;
                $keluar_hidup = $dipindahkan + $hidup + $rujuk + $aps + $lain;
                $keluar_meninggal = $meninggal_48 + $meninggal_lebih;
                $total_keluar = $dipindahkan + $hidup + $aps + $lain + $meninggal_48 + $meninggal_lebih;
                $masih_dirawat = ($awal + $masuk + $dipindahkan) - ($dipindahkan + $hidup + $rujuk + $aps + $lain + $meninggal_48 + $meninggal_lebih);
                $jumlah_hari_perawatan = $awal + $masuk + $pindahan;

                $values = [
                    "tanggal" => $tanggal, "pasien_awal" => $awal, "pasien_masuk" => $masuk,
                    "pasien_pindahan" => $pindahan, "pasien_dipindahkan" => $dipindahkan,
                    "pasien_hidup" => $hidup, "pasien_rujuk" => $rujuk, "pasien_aps" => $aps,
                    "pasien_lain_lain" => $lain,
                    "pasien_meninggal_kurang_dari_48_jam" => $meninggal_48,
                    "pasien_meninggal_lebih_dari_48_jam" => $meninggal_lebih,
                    "jumlah_pasien_masuk_ruangan" => $jumlah_masuk,
                    "jumlah_pasien_keluar_hidup" => $keluar_hidup,
                    "jumlah_pasien_keluar_meninggal" => $keluar_meninggal,
                    "total_pasien_keluar" => $total_keluar,
                    "pasien_yang_masih_dirawat" => $masih_dirawat,
                    "jumlah_hari_perawatan" => $jumlah_hari_perawatan
                ];

                foreach ($values as $key => $val) {
                    $w = $pdf->GetStringWidth((string)$val) + 4;
                    $colWidths[$key] = max($colWidths[$key], min($w, $maxWidth));
                }

                $dataRows[] = $values;
            }

            $totalWidth = array_sum($colWidths);
            $pageWidth = $pdf->GetPageWidth() - 20;
            if ($totalWidth > $pageWidth) {
                $scale = $pageWidth / $totalWidth;
                foreach ($colWidths as $key => $width) {
                    $colWidths[$key] = $width * $scale;
                }
            }

            // Header function for reuse
            $renderHeader = function () use ($pdf, $headers, $headerLabels, $colWidths) {
                $pdf->SetFont('Arial', 'B', 8);
                $startY = $pdf->GetY();
                $maxHeight = 0;
                foreach ($headers as $header) {
                    $x = $pdf->GetX();
                    $y = $pdf->GetY();
                    $pdf->MultiCell($colWidths[$header], 5, $headerLabels[$header], 1, 'C');
                    $maxHeight = max($maxHeight, $pdf->GetY() - $y);
                    $pdf->SetXY($x + $colWidths[$header], $y);
                }
                $pdf->Ln($maxHeight);
            };

            // Draw header
            $renderHeader();

            // Table data
            $pdf->SetFont('Arial', '', 8);
            foreach ($dataRows as $row) {
                // Check for page overflow before writing row
                if ($pdf->GetY() + 10 > $pdf->GetPageHeight() - 10) {
                    $pdf->AddPage();
                    $renderHeader();
                    $pdf->SetFont('Arial', '', 8);
                }

                foreach ($headers as $header) {
                    $pdf->Cell($colWidths[$header], 10, $row[$header], 1, 0, 'C');
                }
                $pdf->Ln();
            }

            ob_end_clean();
            $file_name = "rekap_{$ruangan}_bulan_{$month}.pdf";
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $file_name . '"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            $pdf->Output('D', $file_name);

        } catch (Exception $e) {
            ob_end_clean();
            echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
            return;
        }
    }

    
    
    
    
    
    

    
    
    
    
    
    

}


?>