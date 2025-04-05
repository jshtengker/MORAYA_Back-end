<?php
class User_model {
    private $conn;
    private $table = 'users';  // Assuming 'users' is your table name

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login($username, $password) {
        $query = "SELECT user_id, username, role, password, ruangan FROM {$this->table} WHERE username = ? LIMIT 1";
    
        if ($stmt = $this->conn->prepare($query)) {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $stmt->store_result();
    
            if ($stmt->num_rows === 1) {
                $stmt->bind_result($user_id, $fetchedUsername, $role, $stored_password, $ruangan);
                $stmt->fetch();
    
                // Optional: log values for debug (REMOVE in production)
                // error_log("Entered password: $password");
                // error_log("Stored hash: $stored_password");
    
                // Check hashed password
                if (password_verify($password, $stored_password)) {
                    // Optional: update hash if it's outdated
                    if (password_needs_rehash($stored_password, PASSWORD_DEFAULT)) {
                        $newHash = password_hash($password, PASSWORD_DEFAULT);
                        $updateQuery = "UPDATE {$this->table} SET password = ? WHERE user_id = ?";
                        $updateStmt = $this->conn->prepare($updateQuery);
                        $updateStmt->bind_param('si', $newHash, $user_id);
                        $updateStmt->execute();
                        $updateStmt->close();
                    }
    
                    return [
                        'user_id' => $user_id,
                        'username' => $fetchedUsername,
                        'role' => $role,
                        'ruangan' => $ruangan
                    ];
                } else {
                    error_log("DEBUG: Password mismatch for user: $username");
                }
            } else {
                error_log("DEBUG: Username not found: $username");
            }
    
            $stmt->close();
        } else {
            error_log("DEBUG: Failed to prepare login statement");
        }
    
        return false;
    }

    // change password
    public function updatePasswordByUsernameRoleAndRuangan($username, $role, $user_id, $newPassword) {
        // Validate inputs
        if (empty($username) || empty($role) || empty($user_id) || empty($newPassword)) {
            echo json_encode(['status' => 'error', 'message' => 'Username, role, ruangan, and password cannot be empty.']);
            return false;
        }
    
        // Step 1: Retrieve the current password for the given username, role, and ruangan
        // Use LOWER() function to make the comparisons case-insensitive
        $selectQuery = "SELECT password FROM users WHERE username = ? AND role = ? AND user_id = ?";
        $selectStmt = $this->conn->prepare($selectQuery);
        if (!$selectStmt) {
            echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $this->conn->error]);
            return false;
        }
    
        $selectStmt->bind_param("sss", $username, $role, $user_id);
        if ($selectStmt->execute()) {
            $result = $selectStmt->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $currentPassword = $row['password'];
    
                // Step 2: Check if the new password is the same as the current password
                if (password_verify($newPassword, $currentPassword)) {
                    echo json_encode(['status' => 'error', 'message' => 'The new password is the same as the current password. No changes made.']);
                    return false;
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No matching records found for the given username, role, and ruangan.']);
                return false;
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Execute failed: ' . $selectStmt->error]);
            return false;
        }
    
        // Step 3: Hash the new password before updating
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
        // Step 4: Update the password if it is different
        $updateQuery = "UPDATE users SET password = ? WHERE username = ? AND role = ? AND user_id = ?";
        $updateStmt = $this->conn->prepare($updateQuery);
        if (!$updateStmt) {
            echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $this->conn->error]);
            return false;
        }
    
        $updateStmt->bind_param("ssss", $hashedPassword, $username, $role, $user_id);
        if ($updateStmt->execute()) {
            if ($updateStmt->affected_rows > 0) {
                echo json_encode(['status' => 'success', 'message' => 'Password updated successfully.']);
                return true;
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Password update failed. No records updated.']);
                return false;
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Execute failed: ' . $updateStmt->error]);
            return false;
        }
    }
    
}
?>
