<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../model/Login_users.php';

header('Content-Type: application/json');

class Login {
    private $db;
    private $Login_users;

    public function __construct() {
        try {
            $database = new Database();
            $this->db = $database->getConnection();
            $this->Login_users = new Login_users($this->db);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()]);
            exit();  // Stop execution
        }
    }

    public function authenticate() {
        $username = $_POST['username'] ?? null;
        $password = $_POST['password'] ?? null;

        if (!empty($username) && !empty($password)) {
            $result = $this->Login_users->login($username, $password);
            
            if ($result) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Login successful',
                    'user' => [
                        'user_id' => $result['user_id'],
                        'username' => $result['username'],
                        'password' => $result['password'],
                        'role' => $result['role'],
                        'ruangan' => $result['ruangan']

                    ]
                ]);
                exit(); // Stop execution
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid username or password']);
                exit(); // Stop execution
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Please provide username and password']);
            exit(); // Stop execution
        }
    }
}

$controller = new Login();
$controller->authenticate();
?>
