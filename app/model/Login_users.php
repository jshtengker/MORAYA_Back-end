<?php
class Login_users {
    private $conn;
    private $table = 'users';  // Assuming 'users' is your table name

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login($username, $password) {
        $query = "SELECT user_id, username, role, password, ruangan FROM " . $this->table . " WHERE username = ? LIMIT 1";
    
        if ($stmt = $this->conn->prepare($query)) {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $stmt->store_result();
    
            if ($stmt->num_rows > 0) { // Username exists
                $stmt->bind_result($user_id, $username, $role, $stored_password, $ruangan);
                $stmt->fetch();
    
                // DEBUG: Print retrieved values
                // echo("DEBUG: Found user - ID: $id, Username: $username, Role: $role, Stored Password: $stored_password");
    
                // Directly compare passwords (since they are stored in plain text)
                if ($password === $stored_password) {
                    return ['user_id' => $user_id, 'username' => $username,  'role' => $role, 'password' => $stored_password, 'ruangan' => $ruangan];
                } else {
                    error_log("DEBUG: Password mismatch");
                }
            } else {
                error_log("DEBUG: Username not found");
            }
    
            $stmt->close();
        }
        
        return false;
    }
    
}
?>
