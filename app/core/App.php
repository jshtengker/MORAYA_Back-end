<?php
class App {
    // Global variables
    public $controller = "";
    public $method = "";
    public $parameter = "";

    // PHP Constructor
    public function __construct(){
        // Initialize with default controller, method, and parameters
        $this->initDefaultController("Home", "index", "");

        // Parse the URL and handle routing
        $url = $this->parseURL();

        // Debugging: Check what URL parts we are getting
        // var_dump($url);  // This will display the parts of the URL in the browser

        // Handle the controller based on the URL
        if(!empty($url)){
            // Check if the controller file exists in the 'controller' folder
            if(file_exists('../app/controller/' . ucfirst($url[0]) . '.php')) {
                // Change controller name (capitalizing the first letter for consistency)
                $this->controller = ucfirst($url[0]); // Capitalize first letter of controller
                unset($url[0]); // Remove the controller part from URL
            }
        }

        // Include the controller file
        require_once '../app/controller/' . $this->controller . '.php';

        // Instantiate the controller
        $this->controller = new $this->controller;

        // Debugging: Check the controller being used
        // var_dump($this->controller);

        // Handle the method in the URL
        if(isset($url[1])) {
            $method_name = $url[1];
            if(method_exists($this->controller, $method_name)) {
                // Change the method name
                $this->method = $method_name;
                unset($url[1]); // Remove the method part from URL
            }
        }

        // Handle parameters (if any)
        $this->parameter = !empty($url) ? array_values($url) : [];

        // Debugging: Check the final parameters
        // var_dump($this->parameter);

        // Call the controller method with parameters
        call_user_func_array([$this->controller, $this->method], $this->parameter);
    }

    // Helper function to check if a string starts with a given prefix
    private function starts_with($str, $prefix){
        return strpos($str, $prefix) === 0;
    }

    // Initialize default controller, method, and parameters
    private function initDefaultController($controller, $method, $param){
        $this->controller = $controller;
        $this->method = $method;
        $this->parameter = $param;
    }

    // Parse the URL to get segments
    public function parseURL(){
        if(isset($_GET['url'])){
            // Remove trailing slashes
            $url = rtrim($_GET['url'], '/');
            // Sanitize the URL to avoid special characters
            $url = filter_var($url, FILTER_SANITIZE_URL);
            // Split the URL into an array based on slashes
            return explode('/', $url);
        }
    }
}
?>
