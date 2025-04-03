<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../core/Database.php';


class Guest_model {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // calculate indicators ruangan daily
    public function dailyIndicatorsRooms($ruangan, $tanggal) {
        try {
            // Step 1: Fetch user_id for the given ruangan
            $query = "SELECT user_id FROM users WHERE ruangan = ? LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $ruangan);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
    
            if ($result->num_rows === 0) {
                return ['status' => 'error', 'message' => 'No users found for this ruangan'];
            }
    
            $row = $result->fetch_assoc();
            $user_id = $row['user_id'];
    
            // Step 2: Fetch the data from input_nurse for the given user_id and tanggal
            $query = "SELECT pasien_awal, pasien_masuk, pasien_pindahan, pasien_lama_dirawat, 
                             pasien_meninggal_kurang_dari_48_jam, pasien_meninggal_lebih_dari_48_jam,
                             pasien_dipindahkan, pasien_hidup, pasien_rujuk, pasien_aps, pasien_lain_lain
                      FROM input_nurse 
                      WHERE tanggal = ? AND user_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("si", $tanggal, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
    
            if ($result->num_rows === 0) {
                return ['status' => 'error', 'message' => 'No data found for this date and room'];
            }
    
            $inputData = $result->fetch_assoc();
    
            // Step 3: Fetch bed quantity for the ruangan
            $query = "SELECT jumlah_bed as bed_quantity FROM bed WHERE ruangan = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $ruangan);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
    
            $bedData = $result->fetch_assoc();
            $bedQuantity = $bedData['bed_quantity'] ?? 0;
            if ($bedQuantity <= 0) {
                return ['status' => 'error', 'message' => 'No beds available for this ruangan'];
            }
    
            // Step 4: Set totalDays to 1 as per the requirement (since it's for a specific date)
            $totalDays = 1;
    
            // Step 5: Perform calculations based on the fetched data
    
            // Total patients: Sum of all categories as per the second code
            $totalPatients = (
                $inputData['pasien_dipindahkan'] + $inputData['pasien_hidup'] +
                $inputData['pasien_rujuk'] + $inputData['pasien_aps'] +
                $inputData['pasien_lain_lain'] + $inputData['pasien_meninggal_kurang_dari_48_jam'] +
                $inputData['pasien_meninggal_lebih_dari_48_jam']
            );
    
            // BOR: Bed Occupancy Rate
            $bor = round((($inputData['pasien_awal'] + $inputData['pasien_masuk'] + $inputData['pasien_pindahan']) / ($bedQuantity * $totalDays)) * 100);
    
            // AVLOS: Average Length of Stay
            $avlos = ($totalPatients > 0) ? round($inputData['pasien_lama_dirawat'] / $totalPatients) : 0;
    
            // TOI: Turnover Interval
            $toi = ($totalPatients > 0) ? round((($bedQuantity * $totalDays) - ($inputData['pasien_awal'] + $inputData['pasien_masuk'] + $inputData['pasien_pindahan'])) / $totalPatients) : 0;
    
            // BTO: Bed Turnover
            $bto = ($bedQuantity > 0) ? round($totalPatients / $bedQuantity) : 0;
    
            // GDR: Gross Death Rate
            $gdr = ($totalPatients > 0) ? round((($inputData['pasien_meninggal_kurang_dari_48_jam'] + $inputData['pasien_meninggal_lebih_dari_48_jam']) / $totalPatients) * 1000) : 0;
    
            // NDR: Net Death Rate
            $ndr = ($totalPatients > 0) ? round(($inputData['pasien_meninggal_lebih_dari_48_jam'] / $totalPatients) * 1000) : 0;
    
            // Return calculated indicators
            return [
                'data' => [
                    'BOR' => $bor,
                    'AVLOS' => $avlos,
                    'TOI' => $toi,
                    'BTO' => $bto,
                    'GDR' => $gdr,
                    'NDR' => $ndr
                    
                ]
            ];
    
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()];
        }
    }

    // calculate indicators ruangan monthly
    public function monthlyIndicatorsRooms($ruangan, $month) {
        try {
            // Step 1: Find the user_id for the given ruangan
            $query = "SELECT user_id FROM users WHERE ruangan = ? LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $ruangan);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
    
            if ($result->num_rows === 0) {
                return ['status' => 'error', 'message' => 'No users found for this ruangan'];
            }
    
            $row = $result->fetch_assoc();
            $user_id = $row['user_id'];
    
            // Step 2: Fetch and aggregate data from input_nurse for the given user_id and month
            $query = "SELECT 
                        DAY(tanggal) AS day,
                        SUM(pasien_awal) AS pasien_awal, 
                        SUM(pasien_masuk) AS pasien_masuk, 
                        SUM(pasien_pindahan) AS pasien_pindahan,
                        SUM(pasien_hidup) AS pasien_hidup,
                        SUM(pasien_rujuk) AS pasien_rujuk,
                        SUM(pasien_aps) AS pasien_aps,
                        SUM(pasien_dipindahkan) AS pasien_dipindahkan,
                        SUM(pasien_lain_lain) AS pasien_lain_lain,
                        SUM(pasien_lama_dirawat) AS pasien_lama_dirawat,
                        SUM(pasien_meninggal_kurang_dari_48_jam) AS pasien_meninggal_kurang_dari_48_jam, 
                        SUM(pasien_meninggal_lebih_dari_48_jam) AS pasien_meninggal_lebih_dari_48_jam
                      FROM input_nurse 
                      WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = YEAR(CURDATE()) AND user_id = ?
                      GROUP BY DAY(tanggal)";
    
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("si", $month, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
    
            if ($result->num_rows === 0) {
                return ['status' => 'error', 'message' => 'No any data found!'];
            }
    
            // Step 3: Initialize data arrays and collect the results
            $monthly_data = [];
            while ($row = $result->fetch_assoc()) {
                $monthly_data[$row['day']] = $row;
            }
    
            // Step 4: Get the number of days for the given month
            $year = date('Y');
            $days_in_month = cal_days_in_month(CAL_GREGORIAN, (int)$month, $year);
    
            // Step 5: Check for missing days (i.e., any day where data is missing)
            $missing_days = [];
            for ($day = 1; $day <= $days_in_month; $day++) {
                if (!isset($monthly_data[$day])) {
                    $missing_days[] = $day;
                }
            }
    
            // Step 6: If there are missing days, return an error with the missing days
            if (!empty($missing_days)) {
                return [
                    'status' => 'error',
                    'message' => 'Data is missing for the following days: ' . implode(', ', $missing_days)
                ];
            }
    
            // Step 7: Get bed quantity (jumlah_bed) for the ruangan
            $query = "SELECT jumlah_bed FROM bed WHERE ruangan = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $ruangan);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
    
            $bedData = $result->fetch_assoc();
            $bed_quantity = $bedData['jumlah_bed'] ?? 0;
    
            // Step 8: Calculate the indicators based on the aggregated data
            $pasien_awal_total = 0;
            $pasien_masuk_total = 0;
            $pasien_pindahan_total = 0;
            $pasien_dipindahkan_total = 0;
            $pasien_hidup_total = 0;
            $pasien_rujuk_total = 0;
            $pasien_aps_total = 0;
            $pasien_aps_lain_lain = 0;
            $pasien_lama_dirawat_total = 0;
            $pasien_meninggal_kurang_dari_48_jam_total = 0;
            $pasien_meninggal_lebih_dari_48_jam_total = 0;
    
            // Aggregate the totals for the month
            foreach ($monthly_data as $day_data) {
                $pasien_awal_total += $day_data['pasien_awal'];
                $pasien_masuk_total += $day_data['pasien_masuk'];
                $pasien_pindahan_total += $day_data['pasien_pindahan'];
                $pasien_hidup_total += $day_data['pasien_hidup'];
                $pasien_rujuk_total += $day_data['pasien_rujuk'];
                $pasien_lain_lain_total += $day_data['pasien_lain_lain'];
                $pasien_aps_total += $day_data['pasien_aps'];
                $pasien_dipindahkan_total += $day_data['pasien_dipindahkan'];
                $pasien_lama_dirawat_total += $day_data['pasien_lama_dirawat'];
                $pasien_meninggal_kurang_dari_48_jam_total += $day_data['pasien_meninggal_kurang_dari_48_jam'];
                $pasien_meninggal_lebih_dari_48_jam_total += $day_data['pasien_meninggal_lebih_dari_48_jam'];
            }
    
            // Calculate the total number of rows (patients)
            $pasien_keluar_hidup_mati = $pasien_dipindahkan_total + $pasien_hidup_total + $pasien_lain_lain_total + $pasien_rujuk_total + $pasien_aps_total + $pasien_meninggal_kurang_dari_48_jam_total + $pasien_meninggal_lebih_dari_48_jam_total;
    
            // Step 9: Calculate the indicators
            $bor = round((($pasien_awal_total + $pasien_masuk_total + $pasien_pindahan_total) / ($bed_quantity * $days_in_month)) * 100);
            $avlos = $pasien_keluar_hidup_mati > 0 ? round($pasien_lama_dirawat_total / $pasien_keluar_hidup_mati) : 0;
            $toi = $pasien_keluar_hidup_mati > 0 ? round((($bed_quantity * $days_in_month) - ($pasien_awal_total + $pasien_masuk_total + $pasien_pindahan_total)) / $pasien_keluar_hidup_mati) : 0;
            $bto = round($pasien_keluar_hidup_mati / $bed_quantity);
            $gdr = $pasien_keluar_hidup_mati > 0 ? round((($pasien_meninggal_kurang_dari_48_jam_total + $pasien_meninggal_lebih_dari_48_jam_total) / $pasien_keluar_hidup_mati) * 1000) : 0;
            $ndr = $pasien_keluar_hidup_mati > 0 ? round(($pasien_meninggal_lebih_dari_48_jam_total / $pasien_keluar_hidup_mati) * 1000) : 0;
    
            // Step 10: Return the calculated indicators and debug information
            return [
                'status' => 'success',
                'indicators' => [
                    'BOR' => $bor,
                    'AVLOS' => $avlos,
                    'TOI' => $toi,
                    'BTO' => $bto,
                    'GDR' => $gdr,
                    'NDR' => $ndr
                ],
                'debug_data' => [
                    'pasien_awal' => $pasien_awal_total,
                    'pasien_masuk' => $pasien_masuk_total,
                    'pasien_pindahan' => $pasien_pindahan_total,
                    'pasien_lama_dirawat' => $pasien_lama_dirawat_total,
                    'pasien_meninggal_kurang_dari_48_jam' => $pasien_meninggal_kurang_dari_48_jam_total,
                    'pasien_meninggal_lebih_dari_48_jam' => $pasien_meninggal_lebih_dari_48_jam_total,
                    'days_in_month' => $days_in_month,
                    'bed quantity' => $bed_quantity,
                    'total discharges' => $pasien_keluar_hidup_mati
                ]
            ];
    
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()];
        }
    }
    
    // calculate indicators ruangan by range date
    public function dateRangeIndicatorsRooms($ruangan, $startDate, $endDate) {
        try {
            // Generate the range of dates, ensure both dates are included
            $startDateObj = new DateTime($startDate . ' 00:00:00'); // Ensure start date is at midnight
            $endDateObj = new DateTime($endDate . ' 23:59:59'); // Ensure end date is at the last moment of the day
    
            $dateRange = [];
            while ($startDateObj <= $endDateObj) {
                $dateRange[] = $startDateObj->format('Y-m-d');
                $startDateObj->modify('+1 day');
            }
    
            // Calculate total days in the date range
            $totalDays = count($dateRange); // Directly count the days in the range
    
            // Step 1: Find the user_id for the given ruangan
            $query = "SELECT user_id FROM users WHERE ruangan = ? LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $ruangan);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
    
            if ($result->num_rows === 0) {
                return ['status' => 'error', 'message' => 'No users found for this ruangan'];
            }
    
            $row = $result->fetch_assoc();
            $user_id = $row['user_id'];
    
            // Step 2: Check for missing dates in the input_nurse table
            $queryDateCheck = "SELECT DISTINCT DATE(tanggal) AS tanggal FROM input_nurse WHERE tanggal BETWEEN ? AND ? AND user_id = ?";
            $stmtDateCheck = $this->conn->prepare($queryDateCheck);
            $stmtDateCheck->bind_param("ssi", $startDate, $endDate, $user_id);
            $stmtDateCheck->execute();
            $resultDateCheck = $stmtDateCheck->get_result();
            $existingDates = [];
            while ($row = $resultDateCheck->fetch_assoc()) {
                $existingDates[] = $row['tanggal'];
            }
    
            // Identify missing dates
            $missingDates = [];
            foreach ($dateRange as $date) {
                if (!in_array($date, $existingDates)) {
                    $missingDates[] = $date;
                }
            }
    
            // If there are missing dates, return an error
            if (!empty($missingDates)) {
                return [
                    'status' => 'error',
                    'message' => 'Data is missing for the following days: ' . implode(', ', $missingDates)
                ];
            }
    
            // Step 3: Retrieve the data for the specified date range
            $query = "SELECT 
                        SUM(pasien_awal + pasien_masuk + pasien_pindahan) AS PatientDays,
                        SUM(pasien_dipindahkan + pasien_hidup + pasien_rujuk + pasien_aps + pasien_lain_lain + pasien_meninggal_kurang_dari_48_jam + pasien_meninggal_lebih_dari_48_jam) AS Discharges,
                        SUM(pasien_meninggal_kurang_dari_48_jam + pasien_meninggal_lebih_dari_48_jam) AS Deaths,
                        SUM(pasien_meninggal_lebih_dari_48_jam) AS Deaths48,
                        SUM(pasien_lama_dirawat) AS PatientTreatment
                      FROM input_nurse 
                      WHERE tanggal BETWEEN ? AND ? AND user_id = ?";
    
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ssi", $startDate, $endDate, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $totalPatientDays = (int) $data['PatientDays'];
            $totalDischarges = (int) $data['Discharges'];
            $totalDeaths = (int) $data['Deaths'];
            $totalDeaths48 = (int) $data['Deaths48'];
            $totalPatientTreatment = (int) $data['PatientTreatment'];
    
            // Step 4: Get bed quantity for the given ruangan
            $queryBed = "SELECT jumlah_bed FROM bed WHERE ruangan = ?";
            $stmtBed = $this->conn->prepare($queryBed);
            $stmtBed->bind_param("s", $ruangan);
            $stmtBed->execute();
            $resultBed = $stmtBed->get_result();
            $bedData = $resultBed->fetch_assoc();
            $bedQuantity = $bedData['jumlah_bed'] ?? 0;
    
            // Debugging info
            $debugInfo = [
                'TotalPatientDays' => $totalPatientDays,
                'TotalDischarges' => $totalDischarges,
                'TotalDeaths' => $totalDeaths,
                'TotalDeaths48' => $totalDeaths48,
                'TotalPatientTreatment' => $totalPatientTreatment,
                'TotalBeds' => $bedQuantity,
                'TotalDays' => $totalDays,
            ];
            echo json_encode($debugInfo, JSON_PRETTY_PRINT);
    
            // Step 5: Calculate the statistics
            function calculate_stats_by_room($totalPatientDays, $totalBeds, $totalDischarges, $totalDeaths, $totalDeaths48, $totalDays, $totalPatientTreatment) {
                $totalBedDays = $totalBeds * $totalDays;
                $BOR = $totalBedDays > 0 ? round(($totalPatientDays / $totalBedDays) * 100) : 0; // Bed Occupancy Rate
                $AVLOS = $totalDischarges > 0 ? round($totalPatientTreatment / $totalDischarges) : 0; // Average Length of Stay
                $TOI = $totalDischarges > 0 ? round(($totalBedDays - $totalPatientDays) / $totalDischarges) : 0; // Turnover Interval
                $BTO = $totalBeds > 0 ? round($totalDischarges / $totalBeds) : 0; // Bed Turnover Rate
                $GDR = $totalDischarges > 0 ? round(($totalDeaths / $totalDischarges) * 1000) : 0; // Gross Death Rate
                $NDR = $totalDischarges > 0 ? round(($totalDeaths48 / $totalDischarges) * 1000) : 0; // Net Death Rate
                return [$BOR, $AVLOS, $TOI, $BTO, $GDR, $NDR];
            }
    
            list($BOR, $AVLOS, $TOI, $BTO, $GDR, $NDR) = calculate_stats_by_room($totalPatientDays, $bedQuantity, $totalDischarges, $totalDeaths, $totalDeaths48, $totalDays, $totalPatientTreatment);
    
            // Step 6: Return the calculated statistics
            return [
                'status' => 'success',
                'BOR' => $BOR,
                'AVLOS' => $AVLOS,
                'TOI' => $TOI,
                'BTO' => $BTO,
                'GDR' => $GDR,
                'NDR' => $NDR
            ];
    
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()];
        }
    }

    // calculate indicators rs daily
    public function calculate_stats_rs_daily($date) {
        // Validate the date format (YYYY-MM-DD)
        if (!$this->validate_date($date)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid date format. Expected format is YYYY-MM-DD.']);
            return false;
        }
    
        // Step 1: Check if there are records for the given date
        $queryCheck = "SELECT COUNT(*) AS dataCount FROM input_nurse WHERE tanggal = ?";
        $stmtCheck = $this->conn->prepare($queryCheck);
        if (!$stmtCheck) {
            echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $this->conn->error]);
            return false;
        }
    
        $stmtCheck->bind_param("s", $date);
        if (!$stmtCheck->execute()) {
            echo json_encode(['status' => 'error', 'message' => 'Execute failed: ' . $stmtCheck->error]);
            return false;
        }
    
        $resultCheck = $stmtCheck->get_result();
        $dataCheck = $resultCheck->fetch_assoc();
        if ((int)$dataCheck['dataCount'] === 0) {
            echo json_encode(['status' => 'error', 'message' => 'No data found for the given date.']);
            return false;
        }
    
        // Step 2: Calculate daily statistics by summing up the data from the `input_nurse` table
        $query = "
            SELECT 
                SUM(pasien_awal + pasien_masuk + pasien_pindahan) AS PatientDays,
                SUM(pasien_dipindahkan + pasien_hidup + pasien_rujuk + pasien_aps + pasien_lain_lain + pasien_meninggal_kurang_dari_48_jam + pasien_meninggal_lebih_dari_48_jam) AS Discharges,
                SUM(pasien_meninggal_kurang_dari_48_jam + pasien_meninggal_lebih_dari_48_jam) AS Deaths,
                SUM(pasien_meninggal_lebih_dari_48_jam) AS Deaths48,
                SUM(pasien_lama_dirawat) AS PatientTreatment
            FROM input_nurse 
            WHERE tanggal = ?";
    
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $this->conn->error]);
            return false;
        }
    
        $stmt->bind_param("s", $date);
        if (!$stmt->execute()) {
            echo json_encode(['status' => 'error', 'message' => 'Execute failed: ' . $stmt->error]);
            return false;
        }
    
        $result = $stmt->get_result();
    
        // Initialize variables to calculate the overall totals
        $totalPatientDays = $totalDischarges = $totalDeaths = $totalDeaths48 = $totalPatientTreatment = 0;
        $bedQuantity = 0;
    
        // Fetch total data from the input_nurse table
        if ($data = $result->fetch_assoc()) {
            $totalPatientDays += (int) $data['PatientDays'];
            $totalDischarges += (int) $data['Discharges'];
            $totalDeaths += (int) $data['Deaths'];
            $totalDeaths48 += (int) $data['Deaths48'];
            $totalPatientTreatment += (int) $data['PatientTreatment'];
        }
    
        // Step 3: Sum all beds for the day across all rooms (tables)
        $queryBeds = "SELECT SUM(jumlah_bed) AS TotalBeds FROM bed";
        $stmtBeds = $this->conn->prepare($queryBeds);
        if (!$stmtBeds) {
            echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $this->conn->error]);
            return false;
        }
    
        if (!$stmtBeds->execute()) {
            echo json_encode(['status' => 'error', 'message' => 'Execute failed: ' . $stmtBeds->error]);
            return false;
        }
    
        $resultBeds = $stmtBeds->get_result();
        if ($bedData = $resultBeds->fetch_assoc()) {
            $bedQuantity = (int) $bedData['TotalBeds'];
        }
    
        // Step 4: Calculate the statistics
        $totalDays = 1;  // Since it's daily, we just use 1 day
        list($BOR, $AVLOS, $TOI, $BTO, $GDR, $NDR) = $this->calculate_stats_by_room(
            $totalPatientDays, $bedQuantity, $totalDischarges, $totalDeaths, $totalDeaths48, $totalDays, $totalPatientTreatment
        );
    
        // Step 5: Return the calculated statistics as JSON
        $stats = [
            'date' => $date,
            'BOR' => $BOR,
            'AVLOS' => $AVLOS,
            'TOI' => $TOI,
            'BTO' => $BTO,
            'GDR' => $GDR,
            'NDR' => $NDR
        ];
    
        echo json_encode(['status' => 'success', 'data' => $stats]);
        return true;
    }
    
    private function calculate_stats_by_room($totalPatientDays, $totalBeds, $totalDischarges, $totalDeaths, $totalDeaths48, $totalDays, $totalPatientTreatment) {
        $totalBedDays = $totalBeds * $totalDays;
        $BOR = $totalBedDays > 0 ? round(($totalPatientDays / $totalBedDays) * 100) : 0; // Bed Occupancy Rate
        $AVLOS = $totalDischarges > 0 ? round($totalPatientTreatment / $totalDischarges) : 0; // Average Length of Stay
        $TOI = $totalDischarges > 0 ? round(($totalBedDays - $totalPatientDays) / $totalDischarges) : 0; // Turnover Interval
        $BTO = $totalBeds > 0 ? round($totalDischarges / $totalBeds) : 0; // Bed Turnover Rate
        $GDR = $totalDischarges > 0 ? round(($totalDeaths / $totalDischarges) * 1000) : 0; // Gross Death Rate
        $NDR = $totalDischarges > 0 ? round(($totalDeaths48 / $totalDischarges) * 1000) : 0; // Net Death Rate
        return [$BOR, $AVLOS, $TOI, $BTO, $GDR, $NDR];
    }
    

    
    
    
    
    
    
    
}




?>