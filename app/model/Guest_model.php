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
    public function monthlyIndicatorsRooms($ruangan, $month, $year) {
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
    
            // Step 2: Get the number of days in the given month/year
            $days_in_month = cal_days_in_month(CAL_GREGORIAN, (int)$month, $year);
    
            // Step 3: Initialize list of all expected dates
            $all_dates = [];
            for ($day = 1; $day <= $days_in_month; $day++) {
                $formatted_day = str_pad($day, 2, '0', STR_PAD_LEFT);
                $formatted_month = str_pad($month, 2, '0', STR_PAD_LEFT);
                $all_dates[$day] = "$year-$formatted_month-$formatted_day";
            }
    
            // Step 4: Fetch data from input_nurse
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
                      WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = ? AND user_id = ?
                      GROUP BY DAY(tanggal)";
    
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("iii", $month, $year, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
    
            $monthly_data = [];
            while ($row = $result->fetch_assoc()) {
                $monthly_data[(int)$row['day']] = $row;
            }
    
            // Step 5: Identify missing dates
            $missing_dates = [];
            foreach ($all_dates as $day => $date) {
                if (!isset($monthly_data[$day])) {
                    $missing_dates[] = $date;
                }
            }
    
            // If all dates are missing, return immediately
            if (count($monthly_data) === 0) {
                return [
                    'status' => 'error',
                    'message' => 'Tidak ada satupun data tersedia di bulan ini.',
                    'missing_dates' => array_values($all_dates)
                ];
            }
    
            if (!empty($missing_dates)) {
                return [
                    'status' => 'error',
                    'message' => 'Data tidak tersedia untuk tanggal di bawah ini :',
                    'missing_dates' => $missing_dates
                ];
            }
    
            // Step 6: Get bed quantity (jumlah_bed) for the ruangan
            $query = "SELECT jumlah_bed FROM bed WHERE ruangan = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $ruangan);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
    
            $bedData = $result->fetch_assoc();
            $bed_quantity = $bedData['jumlah_bed'] ?? 0;
    
            // Step 7: Calculate totals
            $totals = [
                'pasien_awal' => 0,
                'pasien_masuk' => 0,
                'pasien_pindahan' => 0,
                'pasien_hidup' => 0,
                'pasien_rujuk' => 0,
                'pasien_aps' => 0,
                'pasien_dipindahkan' => 0,
                'pasien_lain_lain' => 0,
                'pasien_lama_dirawat' => 0,
                'pasien_meninggal_kurang_dari_48_jam' => 0,
                'pasien_meninggal_lebih_dari_48_jam' => 0,
            ];
    
            foreach ($monthly_data as $day_data) {
                foreach ($totals as $key => &$total) {
                    $total += $day_data[$key];
                }
            }
    
            $pasien_keluar_hidup_mati = $totals['pasien_dipindahkan'] + $totals['pasien_hidup'] + $totals['pasien_lain_lain'] + $totals['pasien_rujuk'] + $totals['pasien_aps'] + $totals['pasien_meninggal_kurang_dari_48_jam'] + $totals['pasien_meninggal_lebih_dari_48_jam'];
    
            // Step 8: Calculate the indicators
            $bor = round((($totals['pasien_awal'] + $totals['pasien_masuk'] + $totals['pasien_pindahan']) / ($bed_quantity * $days_in_month)) * 100);
            $avlos = $pasien_keluar_hidup_mati > 0 ? round($totals['pasien_lama_dirawat'] / $pasien_keluar_hidup_mati) : 0;
            $toi = $pasien_keluar_hidup_mati > 0 ? round((($bed_quantity * $days_in_month) - ($totals['pasien_awal'] + $totals['pasien_masuk'] + $totals['pasien_pindahan'])) / $pasien_keluar_hidup_mati) : 0;
            $bto = round($pasien_keluar_hidup_mati / $bed_quantity);
            $gdr = $pasien_keluar_hidup_mati > 0 ? round((($totals['pasien_meninggal_kurang_dari_48_jam'] + $totals['pasien_meninggal_lebih_dari_48_jam']) / $pasien_keluar_hidup_mati) * 1000) : 0;
            $ndr = $pasien_keluar_hidup_mati > 0 ? round(($totals['pasien_meninggal_lebih_dari_48_jam'] / $pasien_keluar_hidup_mati) * 1000) : 0;
    
            // Step 9: Return the result
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
                'debug_data' => array_merge($totals, [
                    'days_in_month' => $days_in_month,
                    'bed quantity' => $bed_quantity,
                    'total discharges' => $pasien_keluar_hidup_mati
                ])
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
                    'message' => 'Data tidak tersedia untuk tanggal di bawah ini : ' . implode(', ', $missingDates)
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
        // Step 1: Get all unique rooms from the users table (excluding admins)
        $queryRooms = "SELECT DISTINCT ruangan FROM users WHERE ruangan IS NOT NULL AND role != 'admin'";
        $stmtRooms = $this->conn->prepare($queryRooms);
    
        if (!$stmtRooms || !$stmtRooms->execute()) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to fetch room data']);
            return false;
        }
    
        $resultRooms = $stmtRooms->get_result();
        $rooms = [];
        while ($row = $resultRooms->fetch_assoc()) {
            $rooms[] = $row['ruangan'];
        }
    
        // Step 2: Get rooms that have data in input_nurse for the given date (excluding admins)
        $queryExistingRooms = "
            SELECT DISTINCT u.ruangan 
            FROM input_nurse i
            JOIN users u ON i.user_id = u.user_id 
            WHERE i.tanggal = ? AND u.role != 'admin'";
        
        $stmtExistingRooms = $this->conn->prepare($queryExistingRooms);
    
        if (!$stmtExistingRooms) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to prepare query for existing room data']);
            return false;
        }
    
        $stmtExistingRooms->bind_param("s", $date);
        if (!$stmtExistingRooms->execute()) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to execute query for existing room data']);
            return false;
        }
    
        $resultExistingRooms = $stmtExistingRooms->get_result();
        $existingRooms = [];
        while ($row = $resultExistingRooms->fetch_assoc()) {
            $existingRooms[] = $row['ruangan'];
        }
    
        // Step 3: Identify missing rooms
        $missingRooms = array_diff($rooms, $existingRooms);
        if (!empty($missingRooms)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Data is missing for some rooms on the given date.',
                'missing_rooms' => array_values($missingRooms)
            ]);
            return false;
        }
    
        // Step 4: Proceed with calculations since all rooms have data
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
    
        if ($data = $result->fetch_assoc()) {
            $totalPatientDays += (int) $data['PatientDays'];
            $totalDischarges += (int) $data['Discharges'];
            $totalDeaths += (int) $data['Deaths'];
            $totalDeaths48 += (int) $data['Deaths48'];
            $totalPatientTreatment += (int) $data['PatientTreatment'];
        }
    
        // Step 5: Get total beds
        $queryBeds = "SELECT SUM(jumlah_bed) AS TotalBeds FROM bed";
        $stmtBeds = $this->conn->prepare($queryBeds);
        if (!$stmtBeds || !$stmtBeds->execute()) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to fetch bed data']);
            return false;
        }
    
        $resultBeds = $stmtBeds->get_result();
        if ($bedData = $resultBeds->fetch_assoc()) {
            $bedQuantity = (int) $bedData['TotalBeds'];
        }
    
        // Step 6: Calculate statistics
        $totalDays = 1;
        list($BOR, $AVLOS, $TOI, $BTO, $GDR, $NDR) = $this->calculate_stats_by_room(
            $totalPatientDays, $bedQuantity, $totalDischarges, $totalDeaths, $totalDeaths48, $totalDays, $totalPatientTreatment
        );
    
        // Step 7: Return calculated statistics
        $stats = [
            'date' => $date,
            'BOR' => $BOR,
            'AVLOS' => $AVLOS,
            'TOI' => $TOI,
            'BTO' => $BTO,
            'GDR' => $GDR,
            'NDR' => $NDR,
            'totalBeds' => $bedQuantity
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

    // calculate indicators rs monthly
    public function calculateStatsMonthly($month, $year) {
        // Ensure the month is properly formatted as two digits
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
    
        // Get the number of days in the given month and year
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $monthPattern = "$year-$month-%"; // Format: YYYY-MM-%
    
        // Step 1: Get all unique rooms from the users table (excluding admins)
        $queryRooms = "SELECT DISTINCT ruangan FROM users WHERE ruangan IS NOT NULL AND role != 'admin'";
        $stmtRooms = $this->conn->prepare($queryRooms);
        
        if (!$stmtRooms || !$stmtRooms->execute()) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to fetch room data']);
            return false;
        }
    
        $resultRooms = $stmtRooms->get_result();
        $rooms = [];
        while ($row = $resultRooms->fetch_assoc()) {
            $rooms[] = $row['ruangan'];
        }
    
        // Step 2: Generate list of all days in the given month and year
        $allDaysInMonth = [];
        $currentDay = "$year-$month-01";
        for ($i = 0; $i < $daysInMonth; $i++) {
            $allDaysInMonth[] = date('Y-m-d', strtotime("$currentDay +$i days"));
        }
    
        // Step 3: Check for missing data for each room per day
        $missingData = [];
        foreach ($rooms as $room) {
            $missingDates = [];
            foreach ($allDaysInMonth as $day) {
                $queryCheck = "SELECT COUNT(*) AS dataCount FROM input_nurse i
                               JOIN users u ON i.user_id = u.user_id
                               WHERE u.ruangan = ? AND i.tanggal = ?";
                $stmtCheck = $this->conn->prepare($queryCheck);
                $stmtCheck->bind_param("ss", $room, $day);
    
                if (!$stmtCheck->execute()) {
                    echo json_encode(['status' => 'error', 'message' => 'Execute failed: ' . $stmtCheck->error]);
                    return false;
                }
    
                $resultCheck = $stmtCheck->get_result();
                $dataCheck = $resultCheck->fetch_assoc();
    
                if ($dataCheck['dataCount'] == 0) {
                    $missingDates[] = $day;
                }
            }
    
            if (!empty($missingDates)) {
                $missingData[$room] = $missingDates;
            }
        }
    
        if (!empty($missingData)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Data is missing for the following rooms on the given dates:',
                'missing_data' => $missingData
            ]);
            return false;
        }
    
        // Step 4: Get total bed count
        $queryBeds = "SELECT SUM(jumlah_bed) AS TotalBeds FROM bed";
        $stmtBeds = $this->conn->prepare($queryBeds);
        if (!$stmtBeds || !$stmtBeds->execute()) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to fetch bed data']);
            return false;
        }
    
        $resultBeds = $stmtBeds->get_result();
        $bedData = $resultBeds->fetch_assoc();
        $bedQuantity = (int) $bedData['TotalBeds'];
    
        // Step 5: Calculate statistics for the month/year
        $queryStats = "
            SELECT 
                SUM(pasien_awal + pasien_masuk + pasien_pindahan) AS PatientDays,
                SUM(pasien_dipindahkan + pasien_hidup + pasien_rujuk + pasien_aps + pasien_lain_lain + pasien_meninggal_kurang_dari_48_jam + pasien_meninggal_lebih_dari_48_jam) AS Discharges,
                SUM(pasien_meninggal_kurang_dari_48_jam + pasien_meninggal_lebih_dari_48_jam) AS Deaths,
                SUM(pasien_meninggal_lebih_dari_48_jam) AS Deaths48,
                SUM(pasien_lama_dirawat) AS PatientTreatment
            FROM input_nurse i
            JOIN users u ON i.user_id = u.user_id
            WHERE MONTH(i.tanggal) = ? AND YEAR(i.tanggal) = ? AND u.ruangan IS NOT NULL AND u.role != 'admin'";
    
        $stmtStats = $this->conn->prepare($queryStats);
        $stmtStats->bind_param("ii", $month, $year);
        if (!$stmtStats->execute()) {
            echo json_encode(['status' => 'error', 'message' => 'Execute failed: ' . $stmtStats->error]);
            return false;
        }
    
        $resultStats = $stmtStats->get_result();
        $data = $resultStats->fetch_assoc();
    
        $totalPatientDays = (int) $data['PatientDays'];
        $totalDischarges = (int) $data['Discharges'];
        $totalDeaths = (int) $data['Deaths'];
        $totalDeaths48 = (int) $data['Deaths48'];
        $totalPatientTreatment = (int) $data['PatientTreatment'];
    
        // Step 6: Calculate indicators
        // $BOR = ($bedQuantity * $daysInMonth) > 0 ? ($totalPatientDays / ($bedQuantity * $daysInMonth)) * 100 : 0;
        // $AVLOS = $totalDischarges > 0 ? $totalPatientTreatment / $totalDischarges : 0;
        // $TOI = $totalDischarges > 0 ? (($bedQuantity * $daysInMonth) - $totalPatientDays) / $totalDischarges : 0;
        // $BTO = $bedQuantity > 0 ? $totalDischarges / $bedQuantity : 0;
        // $GDR = $totalPatientTreatment > 0 ? $totalDeaths / $totalPatientTreatment : 0;
        // $NDR = $totalDischarges > 0 ? $totalDeaths48 / $totalDischarges : 0;
    
        $totalBedDays = $bedQuantity * $daysInMonth;
        $BOR = $totalBedDays > 0 ? round(($totalPatientDays / $totalBedDays) * 100) : 0; // Bed Occupancy Rate
        $AVLOS = $totalDischarges > 0 ? round($totalPatientTreatment / $totalDischarges) : 0; // Average Length of Stay
        $TOI = $totalDischarges > 0 ? round(($totalBedDays - $totalPatientDays) / $totalDischarges) : 0; // Turnover Interval
        $BTO = $bedQuantity > 0 ? round($totalDischarges / $bedQuantity) : 0; // Bed Turnover Rate
        $GDR = $totalDischarges > 0 ? round(($totalDeaths / $totalDischarges) * 1000) : 0; // Gross Death Rate
        $NDR = $totalDischarges > 0 ? round(($totalDeaths48 / $totalDischarges) * 1000) : 0; // Net Death Rate

        // Step 7: Return final result
        $stats = [
            'month' => $month,
            'year' => $year,
            'BOR' => $BOR,
            'AVLOS' => $AVLOS,
            'TOI' => $TOI,
            'BTO' => $BTO,
            'GDR' => $GDR,
            'NDR' => $NDR
        ];

        $debug = [
            'totalDeaths' => $totalDeaths,
            'totalDischarges' => $totalDischarges
        ];
    
        echo json_encode([
            'status' => 'success', 
            'data' => $stats,
            // 'debug' => $debug
        ]);
        return true;
    }

    // calculate indicators rs by range date
    public function calculateStatsInRange($startDate, $endDate) {
        // Validate date format
        $format = 'Y-m-d';
        $startDateObj = DateTime::createFromFormat($format, $startDate);
        $endDateObj = DateTime::createFromFormat($format, $endDate);
        
        if (!$startDateObj || !$endDateObj || $startDateObj->format($format) !== $startDate || $endDateObj->format($format) !== $endDate) {
            return ['status' => 'error', 'message' => 'Invalid date format. Expected format is YYYY-MM-DD.'];
        }
    
        // Ensure the start date is not after the end date
        if ($startDateObj > $endDateObj) {
            return ['status' => 'error', 'message' => 'Start date tidak boleh setelah end date.'];
        }
    
        // Step 1: Get all unique rooms from users table (excluding admins)
        $queryRooms = "SELECT DISTINCT ruangan FROM users WHERE ruangan IS NOT NULL AND role != 'admin'";
        $stmtRooms = $this->conn->prepare($queryRooms);
        
        if (!$stmtRooms || !$stmtRooms->execute()) {
            return ['status' => 'error', 'message' => 'Failed to fetch room data'];
        }
        
        $resultRooms = $stmtRooms->get_result();
        $rooms = [];
        while ($row = $resultRooms->fetch_assoc()) {
            $rooms[] = $row['ruangan'];
        }
    
        // Step 2: Generate all dates in the given range
        $allDaysInRange = [];
        $currentDay = clone $startDateObj;
        while ($currentDay <= $endDateObj) {
            $allDaysInRange[] = $currentDay->format('Y-m-d');
            $currentDay->modify('+1 day');
        }
    
        // Step 3: Check for missing data for each room
        $missingData = [];
        foreach ($rooms as $room) {
            $queryCheck = "
                SELECT DISTINCT tanggal FROM input_nurse i
                JOIN users u ON i.user_id = u.user_id
                WHERE u.ruangan = ? AND i.tanggal BETWEEN ? AND ?
            ";
            $stmtCheck = $this->conn->prepare($queryCheck);
            $stmtCheck->bind_param("sss", $room, $startDate, $endDate);
            
            if (!$stmtCheck->execute()) {
                return ['status' => 'error', 'message' => 'Execute failed: ' . $stmtCheck->error];
            }
    
            $resultCheck = $stmtCheck->get_result();
            $availableDates = [];
            while ($row = $resultCheck->fetch_assoc()) {
                $availableDates[] = $row['tanggal'];
            }
    
            // Find missing dates for this room
            $missingDates = array_diff($allDaysInRange, $availableDates);
            if (!empty($missingDates)) {
                $missingData[$room] = array_values($missingDates);
            }
        }
    
        // Step 4: If there are missing dates, return them
        if (!empty($missingData)) {
            return [
                'status' => 'error',
                'message' => 'Data is missing for the following rooms on the given dates:',
                'missing_data' => $missingData
            ];
        }
    
        // Step 5: Get total bed count
        $queryBeds = "SELECT SUM(jumlah_bed) AS TotalBeds FROM bed";
        $stmtBeds = $this->conn->prepare($queryBeds);
        if (!$stmtBeds || !$stmtBeds->execute()) {
            return ['status' => 'error', 'message' => 'Failed to fetch bed data'];
        }
    
        $resultBeds = $stmtBeds->get_result();
        $bedData = $resultBeds->fetch_assoc();
        $bedQuantity = (int) $bedData['TotalBeds'];
    
        // Step 6: Calculate statistics for all rooms
        $queryStats = "
            SELECT 
                SUM(pasien_awal + pasien_masuk + pasien_pindahan) AS PatientDays,
                SUM(pasien_dipindahkan + pasien_hidup + pasien_rujuk + pasien_aps + pasien_lain_lain + pasien_meninggal_kurang_dari_48_jam + pasien_meninggal_lebih_dari_48_jam) AS Discharges,
                SUM(pasien_meninggal_kurang_dari_48_jam + pasien_meninggal_lebih_dari_48_jam) AS Deaths,
                SUM(pasien_meninggal_lebih_dari_48_jam) AS Deaths48,
                SUM(pasien_lama_dirawat) AS PatientTreatment
            FROM input_nurse i
            JOIN users u ON i.user_id = u.user_id
            WHERE i.tanggal BETWEEN ? AND ? AND u.ruangan IS NOT NULL AND u.role != 'admin'";
    
        $stmtStats = $this->conn->prepare($queryStats);
        $stmtStats->bind_param("ss", $startDate, $endDate);
        if (!$stmtStats->execute()) {
            return ['status' => 'error', 'message' => 'Execute failed: ' . $stmtStats->error];
        }
    
        $resultStats = $stmtStats->get_result();
        $stats = $resultStats->fetch_assoc();
    
        // Step 7: Convert fetched data to integers
        $totalDays = count($allDaysInRange);
        $totalPatientDays = (int) $stats['PatientDays'];
        $totalDischarges = (int) $stats['Discharges'];
        $totalDeaths = (int) $stats['Deaths'];
        $totalDeaths48 = (int) $stats['Deaths48'];
        $totalPatientTreatment = (int) $stats['PatientTreatment'];
    
        // Step 8: Calculate hospital indicators
        $totalBedDays = $bedQuantity * $totalDays;
        $BOR = $totalBedDays > 0 ? round(($totalPatientDays / $totalBedDays) * 100) : 0; // Bed Occupancy Rate
        $AVLOS = $totalDischarges > 0 ? round($totalPatientTreatment / $totalDischarges) : 0; // Average Length of Stay
        $TOI = $totalDischarges > 0 ? round(($totalBedDays - $totalPatientDays) / $totalDischarges) : 0; // Turnover Interval
        $BTO = $bedQuantity > 0 ? round($totalDischarges / $bedQuantity) : 0; // Bed Turnover Rate
        $GDR = $totalDischarges > 0 ? round(($totalDeaths / $totalDischarges) * 1000) : 0; // Gross Death Rate
        $NDR = $totalDischarges > 0 ? round(($totalDeaths48 / $totalDischarges) * 1000) : 0; // Net Death Rate
    
        // Step 9: Return the calculated statistics
        return [
            'status' => 'success',
            'total_days' => $totalDays,
            'total_beds' => $bedQuantity,
            'total_patient_days' => $totalPatientDays,
            'total_discharges' => $totalDischarges,
            'total_deaths' => $totalDeaths,
            'total_deaths_48' => $totalDeaths48,
            'total_patient_treatment' => $totalPatientTreatment,
            'BOR' => $BOR,
            'AVLOS' => $AVLOS,
            'TOI' => $TOI,
            'BTO' => $BTO,
            'GDR' => $GDR,
            'NDR' => $NDR
        ];
    }

     // fetch stats by indicators daily
    public function fetch_stats_by_indicator($date, $indicator) {
        // List of valid indicators
        $validIndicators = ['BOR', 'AVLOS', 'TOI', 'BTO', 'GDR', 'NDR'];
    
        // Validate indicator
        if (!in_array($indicator, $validIndicators)) {
            return json_encode(['status' => 'error', 'message' => 'Invalid indicator. Valid indicators are: BOR, AVLOS, TOI, BTO, GDR, NDR.']);
        }
    
        // Step 1: Get all available rooms from non-admin users
        $roomQuery = "SELECT DISTINCT ruangan FROM users WHERE role != 'admin'";
        $roomResult = $this->conn->query($roomQuery);
    
        if (!$roomResult) {
            return json_encode(['status' => 'error', 'message' => 'Failed to fetch rooms: ' . $this->conn->error]);
        }
    
        // Store all rooms with default values
        $rooms = [];
        while ($row = $roomResult->fetch_assoc()) {
            $rooms[$row['ruangan']] = 'tidak ada data';
        }
    
        // Step 2: Query necessary data for all rooms
        $query = "
            SELECT 
                users.ruangan AS room,
                COALESCE(SUM(bed.jumlah_bed), NULL) AS bedQuantity,
                COALESCE(SUM(input_nurse.pasien_awal + input_nurse.pasien_masuk + input_nurse.pasien_pindahan), NULL) AS totalPatientDays,
                COALESCE(SUM(input_nurse.pasien_hidup + input_nurse.pasien_rujuk + input_nurse.pasien_aps + input_nurse.pasien_lain_lain + input_nurse.pasien_meninggal_kurang_dari_48_jam + input_nurse.pasien_meninggal_lebih_dari_48_jam), NULL) AS totalDischarges,
                COALESCE(SUM(input_nurse.pasien_meninggal_kurang_dari_48_jam), NULL) AS totalDeaths48,
                COALESCE(SUM(input_nurse.pasien_meninggal_lebih_dari_48_jam), NULL) AS totalDeaths
            FROM users
            LEFT JOIN bed ON users.user_id = bed.user_id
            LEFT JOIN input_nurse ON users.user_id = input_nurse.user_id AND input_nurse.tanggal = ?
            WHERE users.role != 'admin'
            GROUP BY users.ruangan
        ";
    
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            return json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $this->conn->error]);
        }
    
        $stmt->bind_param('s', $date);
        if (!$stmt->execute()) {
            return json_encode(['status' => 'error', 'message' => 'Execute failed: ' . $stmt->error]);
        }
    
        $result = $stmt->get_result();
    
        // Process the result and calculate the required indicator
        while ($row = $result->fetch_assoc()) {
            $bedQuantity = $row['bedQuantity'];
            $totalPatientDays = $row['totalPatientDays'];
            $totalDischarges = $row['totalDischarges'];
            $totalDeaths = $row['totalDeaths'];
            $totalDeaths48 = $row['totalDeaths48'];
    
            // If there's no data for this room, keep 'tidak ada data'
            if (is_null($bedQuantity) || is_null($totalPatientDays) || is_null($totalDischarges) || is_null($totalDeaths) || is_null($totalDeaths48)) {
                $rooms[$row['room']] = 'tidak ada data';
                continue;
            }
    
            // Assuming daily calculations use a 1-day period
            $totalBedDays = $bedQuantity * 1;
    
            // Compute the required indicator
            $indicatorValues = [
                'BOR' => $totalBedDays > 0 ? round(($totalPatientDays / $totalBedDays) * 100) : 'tidak ada data',
                'AVLOS' => $totalDischarges > 0 ? round($totalPatientDays / $totalDischarges) : 'tidak ada data',
                'TOI' => $totalDischarges > 0 ? round(($totalBedDays - $totalPatientDays) / $totalDischarges) : 'tidak ada data',
                'BTO' => $bedQuantity > 0 ? round($totalDischarges / $bedQuantity) : 'tidak ada data',
                'GDR' => $totalDischarges > 0 ? round(($totalDeaths / $totalDischarges) * 1000) : 'tidak ada data',
                'NDR' => $totalDischarges > 0 ? round(($totalDeaths48 / $totalDischarges) * 1000) : 'tidak ada data'
            ];
    
            // Assign the calculated value for the requested indicator
            $rooms[$row['room']] = $indicatorValues[$indicator];
        }
    
        // Return JSON response
        return json_encode([
            'status' => 'success',
            'date' => $date,
            'indicator' => $indicator,
            'data' => $rooms
        ], JSON_PRETTY_PRINT);
    }
    
    // fetch stats by indicator monthly
    public function fetch_monthly_stats_by_indicator($month, $year, $indicator) {
        // List of valid indicators
        $validIndicators = ['BOR', 'AVLOS', 'TOI', 'BTO', 'GDR', 'NDR'];
    
        // Validate indicator
        if (!in_array($indicator, $validIndicators)) {
            return json_encode(['status' => 'error', 'message' => 'Invalid indicator. Valid indicators are: BOR, AVLOS, TOI, BTO, GDR, NDR.']);
        }
    
        // Validate month and year
        if (!is_numeric($month) || !is_numeric($year) || $month < 1 || $month > 12 || $year < 2000) {
            return json_encode(['status' => 'error', 'message' => 'Invalid month or year.']);
        }
    
        // Get the number of days in the given month
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    
        // Step 1: Get all available rooms from non-admin users
        $roomQuery = "SELECT DISTINCT ruangan FROM users WHERE role != 'admin'";
        $roomResult = $this->conn->query($roomQuery);
    
        if (!$roomResult) {
            return json_encode(['status' => 'error', 'message' => 'Failed to fetch rooms: ' . $this->conn->error]);
        }
    
        // Store all rooms with default values
        $rooms = [];
        $rawData = []; // Store raw data for debugging
        while ($row = $roomResult->fetch_assoc()) {
            $rooms[$row['ruangan']] = 'tidak ada data';
            $rawData[$row['ruangan']] = []; // Initialize raw data for each room
        }
    
        // Step 2: Query necessary data for all rooms within the given month
        $query = "
            SELECT 
                users.ruangan AS room,
                MAX(bed.jumlah_bed) AS bedQuantity,
                COALESCE(SUM(input_nurse.pasien_awal + input_nurse.pasien_masuk + input_nurse.pasien_pindahan), NULL) AS totalPatientDays,
                COALESCE(SUM(input_nurse.pasien_hidup + input_nurse.pasien_rujuk + input_nurse.pasien_aps + input_nurse.pasien_lain_lain + input_nurse.pasien_meninggal_kurang_dari_48_jam + input_nurse.pasien_meninggal_lebih_dari_48_jam), NULL) AS totalDischarges,
                COALESCE(SUM(input_nurse.pasien_meninggal_kurang_dari_48_jam), NULL) AS totalDeaths48,
                COALESCE(SUM(input_nurse.pasien_meninggal_lebih_dari_48_jam), NULL) AS totalDeaths,
                COUNT(DISTINCT input_nurse.tanggal) AS recordedDays -- Count unique dates
            FROM users
            LEFT JOIN bed ON users.user_id = bed.user_id
            LEFT JOIN input_nurse ON users.user_id = input_nurse.user_id 
                AND MONTH(input_nurse.tanggal) = ? 
                AND YEAR(input_nurse.tanggal) = ?
            WHERE users.role != 'admin'
            GROUP BY users.ruangan
        ";
    
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            return json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $this->conn->error]);
        }
    
        $stmt->bind_param('ii', $month, $year);
        if (!$stmt->execute()) {
            return json_encode(['status' => 'error', 'message' => 'Execute failed: ' . $stmt->error]);
        }
    
        $result = $stmt->get_result();
    
        // Process the result and calculate the required indicator
        while ($row = $result->fetch_assoc()) {
            $bedQuantity = $row['bedQuantity'];
            $totalPatientDays = $row['totalPatientDays'];
            $totalDischarges = $row['totalDischarges'];
            $totalDeaths = $row['totalDeaths'];
            $totalDeaths48 = $row['totalDeaths48'];
            $recordedDays = $row['recordedDays']; // Number of recorded days in the month
    
            // Store raw data for debugging
            $rawData[$row['room']] = [
                'bedQuantity' => $bedQuantity,
                'totalPatientDays' => $totalPatientDays,
                'totalDischarges' => $totalDischarges,
                'totalDeaths' => $totalDeaths,
                'totalDeaths48' => $totalDeaths48,
                'recordedDays' => $recordedDays,
                'daysInMonth' => $daysInMonth
            ];
    
            // Check if any required data is missing or if not all days have records
            if ($recordedDays < $daysInMonth || is_null($bedQuantity) || is_null($totalPatientDays) || is_null($totalDischarges) || is_null($totalDeaths) || is_null($totalDeaths48) ||
                $bedQuantity == 0 || $totalPatientDays == 0 || $totalDischarges == 0) {
                $rooms[$row['room']] = 'data belum lengkap';
                continue;
            }
    
            // Monthly total bed days
            $totalBedDays = $bedQuantity * $daysInMonth;
    
            // Compute the required indicator
            $indicatorValues = [
                'BOR' => $totalBedDays > 0 ? round(($totalPatientDays / $totalBedDays) * 100) : 'data belum lengkap',
                'AVLOS' => $totalDischarges > 0 ? round($totalPatientDays / $totalDischarges) : 'data belum lengkap',
                'TOI' => $totalDischarges > 0 ? round(($totalBedDays - $totalPatientDays) / $totalDischarges) : 'data belum lengkap',
                'BTO' => $bedQuantity > 0 ? round($totalDischarges / $bedQuantity) : 'data belum lengkap',
                'GDR' => $totalDischarges > 0 ? round(($totalDeaths / $totalDischarges) * 1000) : 'data belum lengkap',
                'NDR' => $totalDischarges > 0 ? round(($totalDeaths48 / $totalDischarges) * 1000) : 'data belum lengkap'
            ];
    
            // Assign the calculated value for the requested indicator
            $rooms[$row['room']] = $indicatorValues[$indicator];
        }
    
        // Return JSON response
        return json_encode([
            'status' => 'success',
            'month' => $month,
            'year' => $year,
            'indicator' => $indicator,
            'data' => $rooms,
            'raw_data' => $rawData
        ], JSON_PRETTY_PRINT);
    }

    // fetch stats by indicator based on range date
    public function fetchStatsByRange($startDate, $endDate, $indicator) {
        $validIndicators = ['BOR', 'AVLOS', 'TOI', 'BTO', 'GDR', 'NDR'];
        if (!in_array(strtoupper($indicator), $validIndicators)) {
            return ['error' => 'Invalid indicator.'];
        }
    
        $totalDays = (new DateTime($startDate))->diff(new DateTime($endDate))->days + 1;
    
        $userQuery = "SELECT user_id, ruangan FROM users WHERE role != 'admin'";
        $userResult = $this->conn->query($userQuery);
        if (!$userResult) return ['error' => 'Failed to fetch users.'];
    
        $stats = [];
    
        while ($user = $userResult->fetch_assoc()) {
            $userId = $user['user_id'];
            $ruangan = $user['ruangan'];
    
            // Get bed quantity
            $bedQuery = "SELECT MAX(jumlah_bed) as bed_quantity FROM bed WHERE user_id = ?";
            $stmtBed = $this->conn->prepare($bedQuery);
            $stmtBed->bind_param('i', $userId);
            $stmtBed->execute();
            $bedResult = $stmtBed->get_result()->fetch_assoc();
            $bedQuantity = $bedResult['bed_quantity'] ?? 0;
    
            if (!$bedQuantity) {
                $stats[$ruangan] = 'data belum lengkap';
                continue;
            }
    
            // Check if records exist for every day in the range
            $dateCountQuery = "
                SELECT COUNT(DISTINCT tanggal) as date_count 
                FROM input_nurse 
                WHERE user_id = ? AND tanggal BETWEEN ? AND ?
            ";
            $stmtCount = $this->conn->prepare($dateCountQuery);
            $stmtCount->bind_param('iss', $userId, $startDate, $endDate);
            $stmtCount->execute();
            $countResult = $stmtCount->get_result()->fetch_assoc();
            $dateCount = (int) $countResult['date_count'];
    
            if ($dateCount < $totalDays) {
                $stats[$ruangan] = 'data belum lengkap';
                continue;
            }
    
            // Now fetch the aggregated values
            $nurseQuery = "
                SELECT 
                    SUM(pasien_awal + pasien_masuk + pasien_pindahan) AS PatientDays,
                    SUM(pasien_dipindahkan + pasien_hidup + pasien_rujuk + pasien_aps + pasien_lain_lain + pasien_meninggal_kurang_dari_48_jam + pasien_meninggal_lebih_dari_48_jam) AS Discharges,
                    SUM(pasien_meninggal_kurang_dari_48_jam + pasien_meninggal_lebih_dari_48_jam) AS Deaths,
                    SUM(pasien_meninggal_lebih_dari_48_jam) AS Deaths48,
                    SUM(pasien_lama_dirawat) AS PatientTreatment
                FROM input_nurse
                WHERE user_id = ? AND tanggal BETWEEN ? AND ?
            ";
            $stmtNurse = $this->conn->prepare($nurseQuery);
            $stmtNurse->bind_param('iss', $userId, $startDate, $endDate);
            $stmtNurse->execute();
            $data = $stmtNurse->get_result()->fetch_assoc();
    
            $totalBedDays = $bedQuantity * $totalDays;
    
            // Calculate indicators
            $BOR   = $totalBedDays > 0 ? round(($data['PatientDays'] / $totalBedDays) * 100) : 0;
            $AVLOS = $data['Discharges'] > 0 ? round($data['PatientTreatment'] / $data['Discharges']) : 0;
            $TOI   = $data['Discharges'] > 0 ? round(($totalBedDays - $data['PatientDays']) / $data['Discharges']) : 0;
            $BTO   = $bedQuantity > 0 ? round($data['Discharges'] / $bedQuantity) : 0;
            $GDR   = $data['Discharges'] > 0 ? round(($data['Deaths'] / $data['Discharges']) * 1000) : 0;
            $NDR   = $data['Discharges'] > 0 ? round(($data['Deaths48'] / $data['Discharges']) * 1000) : 0;
    
            $indicators = compact('BOR', 'AVLOS', 'TOI', 'BTO', 'GDR', 'NDR');
    
            $stats[$ruangan] = $indicators[strtoupper($indicator)];
        }
    
        return $stats;
    }

    
    
    
    
}

?>