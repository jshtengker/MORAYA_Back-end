<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../model/User_model.php';

header('Content-Type: application/json');

class User {
    private $db;
    private $User_model;

    public function __construct() {
        try {
            $database = new Database();
            $this->db = $database->getConnection();
            $this->User_model = new User_Model($this->db);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()]);
            exit();  // Stop execution
        }
    }

    public function authenticate() {
        $username = $_POST['username'] ?? null;
        $password = $_POST['password'] ?? null;
    
        if (!empty($username) && !empty($password)) {
            $result = $this->User_model->login($username, $password);
            
            if ($result) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Login successful',
                    'user' => [
                        'user_id' => $result['user_id'],
                        'username' => $result['username'],
                        'role' => $result['role'],
                        'ruangan' => $result['ruangan']
                    ]
                ]);
                exit();
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid username or password']);
                exit();
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Please provide username and password']);
            exit();
        }
    }

    public function change_password() {
        // Get input from POST or any request data
        $username = isset($_POST['username']) ? $_POST['username'] : null;
        $role = isset($_POST['role']) ? $_POST['role'] : null;
        $user_id = isset($_POST['user_id']) ? $_POST['user_id'] : null;
        $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : null;
    

        echo $username, $user_id, $role;

        // Validate the inputs
        if (empty($username) || empty($role) || empty($user_id) || empty($newPassword)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Username, role, user_id are required.'
            ]);
            return;
        }

        // Call the model function to update the password
        $result = $this->User_model->updatePasswordByUsernameRoleAndRuangan($username, $role, $user_id, $newPassword);

        // Handle the response from the model
        if ($result) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Password updated successfully.'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to update password. Check the inputs or try again later.'
            ]);
        }
    }
}

?>
