<?php 
class Database {
    private $server_name = HOST_DB;
    private $db_name = NAME_DB;
    private $user_name = USER_DB;
    private $password = PASS_DB;

    private $con;

    public function __construct() {
        // Create the database connection
        $this->con = $this->db_connection($this->server_name, $this->user_name, $this->password, $this->db_name);
        
        // Check if the connection is established
        if ($this->con == false) {
            // Handle connection error
            echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
            exit();
        }
    }

    private function db_connection($srvr_nm, $usr_nm, $psswrd, $db_nm) {
        // Create the connection using mysqli
        $conn = new mysqli($srvr_nm, $usr_nm, $psswrd, $db_nm);

        // Check if the connection failed
        if ($conn->connect_error) {
            return false;
        }

        return $conn;
    }

    // This method will be used to get the database connection
    public function getConnection() {
        return $this->con;
    }

    public function query($sql) {
        return $this->con->query($sql);
    }

    public function db_close() {
        $this->con->close();
    }
}
?>
