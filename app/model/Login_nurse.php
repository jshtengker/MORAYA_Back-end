<?php
class Login_nurse {
    private $conn;
    private $table = 'users';  // Assuming 'users' is your table name

    public $id;
    public $name;
    public $username;
    public $password;

    public function __construct($db) {
        $this->conn = $db;

    }

    public function login($username, $password) {
        // SQL query to fetch user data by username
        $query = "SELECT id, username, password FROM " . $this->table . " WHERE username = ? LIMIT 1";

        // Prepare the statement
        if ($stmt = $this->conn->prepare($query)) {
            // Bind the username
            $stmt->bind_param('s', $username);  // 's' for string parameter

            // Execute the statement
            $stmt->execute();

            // Get the result
            $stmt->store_result();

            // Check if the username exists
            if ($stmt->num_rows > 0) {
                // Bind the result to variables
                $stmt->bind_result($id, $username, $stored_password);
                $stmt->fetch();

                // Check if the passwords match directly (no hashing yet)
                if ($password === $stored_password) {
                    // Return user data if password is correct
                    return [
                        'id' => $id,
                        'username' => $username,
                        'password' => $password,
                    ];
                }
            }

            // Close the statement
            $stmt->close();
        }

        // Return false if login fails
        return false;
    }
}
?>