<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../core/Database.php'; // Include Database class
require_once __DIR__ . '/../model/Login_nurse.php'; // Include Login_nurse model

header('Content-Type: application/json');

class Login{
    private $db;
    private $Login_nurse;

    public function __construct() {
        try {
            // Create database connection
            $database = new Database();
            $this->db = $database->getConnection();  // Now, you can call getConnection()

            // Instantiate the Login_nurse model
            $this->Login_nurse = new Login_nurse($this->db);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()]);
            exit();
        }
    }

    public function authenticate() {
        // Retrieve POST data (x-www-form-urlencoded)
        $username = isset($_POST['username']) ? $_POST['username'] : null;
        $password = isset($_POST['password']) ? $_POST['password'] : null;

        if (!empty($username) && !empty($password)) {
            $result = $this->Login_nurse->login($username, $password);
            if ($result) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Login successful',
                    'user' => [
                        'id' => $result['id'],
                        'username' => $result['username'],
                        'password' => $result['password']

                    ]
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid credentials']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Please provide username and password']);
        }
    }
}

// Instantiate and call the controller's authenticate method.
$controller = new Login();
$controller->authenticate();
?>
