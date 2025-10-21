<?php
// process_order.php
session_start();
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in JSON response
ini_set('log_errors', 1);

// Database Configuration Class
class Database {
    private $host = "localhost";
    private $username = "root";
    private $password = "";
    private $database = "cake_shop";
    private $conn;
    
    public function __construct() {
        $this->connect();
    }
    
    private function connect() {
        try {
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->database);
            if ($this->conn->connect_error) {
                throw new Exception("Database connection failed: " . $this->conn->connect_error);
            }
            $this->conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            error_log("DB Connection Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function closeConnection() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

// Order Model Class
class Order {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }
    
    public function createOrder($data) {
        try {
            // Validate required fields
            $requiredFields = ['user_id', 'cake_id', 'cake_name', 'price', 'quantity', 'total_amount', 
                              'payment_id', 'customer_name', 'customer_email', 'customer_phone', 'customer_address'];
            
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    error_log("Missing field (unset): $field");
                    throw new Exception("Missing required field: $field");
                }
                // For string fields, disallow empty string; for numeric fields, allow 0 where valid
                if (in_array($field, ['cake_name','payment_id','customer_name','customer_email','customer_phone','customer_address'])) {
                    if (trim((string)$data[$field]) === '') {
                        error_log("Missing field (empty string): $field");
                        throw new Exception("Missing required field: $field");
                    }
                }
            }
            
            // Sanitize inputs using prepared statements
            $query = "INSERT INTO orders (
                        user_id, cake_id, cake_name, price, quantity, total_amount, 
                        payment_id, razorpay_order_id, customer_name, customer_email, 
                        customer_phone, customer_address, special_instructions, 
                        order_date, payment_date, status
                      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), 'completed')";
            
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                error_log("Prepare failed: " . $this->conn->error);
                throw new Exception("Failed to prepare statement: " . $this->conn->error);
            }
            
            $stmt->bind_param(
                "iisdidsssssss",
                $data['user_id'],
                $data['cake_id'],
                $data['cake_name'],
                $data['price'],
                $data['quantity'],
                $data['total_amount'],
                $data['payment_id'],
                $data['razorpay_order_id'],
                $data['customer_name'],
                $data['customer_email'],
                $data['customer_phone'],
                $data['customer_address'],
                $data['special_instructions']
            );
            
            if ($stmt->execute()) {
                $orderId = $stmt->insert_id;
                $stmt->close();
                return $orderId;
            } else {
                error_log("Execute failed: " . $stmt->error);
                throw new Exception("Failed to execute statement: " . $stmt->error);
            }
            
        } catch (Exception $e) {
            error_log("Create Order Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function getOrderById($orderId) {
        $stmt = $this->conn->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $stmt->close();
        return $order;
    }
    
    public function getAllOrders($userId = null) {
        if ($userId) {
            $stmt = $this->conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC");
            $stmt->bind_param("i", $userId);
        } else {
            $stmt = $this->conn->prepare("SELECT * FROM orders ORDER BY order_date DESC");
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    }
    
    public function __destruct() {
        $this->db->closeConnection();
    }
}

// Process the order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_order') {
    
    try {
        // Log incoming data for debugging
        error_log("Received POST data: " . print_r($_POST, true));
        
        $orderModel = new Order();
        
        // Prepare order data with defaults
        $orderData = [
            'user_id' => isset($_POST['user_id']) ? intval($_POST['user_id']) : 0,
            'cake_id' => isset($_POST['cake_id']) ? intval($_POST['cake_id']) : 0,
            'cake_name' => isset($_POST['cake_name']) ? trim($_POST['cake_name']) : '',
            'price' => isset($_POST['price']) ? floatval($_POST['price']) : 0,
            'quantity' => isset($_POST['quantity']) ? intval($_POST['quantity']) : 1,
            'total_amount' => isset($_POST['total_amount']) ? floatval($_POST['total_amount']) : 0,
            'payment_id' => isset($_POST['payment_id']) ? trim($_POST['payment_id']) : '',
            'razorpay_order_id' => isset($_POST['razorpay_order_id']) ? trim($_POST['razorpay_order_id']) : '',
            'customer_name' => isset($_POST['customer_name']) ? trim($_POST['customer_name']) : '',
            'customer_email' => isset($_POST['customer_email']) ? trim($_POST['customer_email']) : '',
            'customer_phone' => isset($_POST['customer_phone']) ? trim($_POST['customer_phone']) : '',
            'customer_address' => isset($_POST['customer_address']) ? trim($_POST['customer_address']) : '',
            'special_instructions' => isset($_POST['special_instructions']) ? trim($_POST['special_instructions']) : ''
        ];

        // If this is a cart checkout, ensure cake_id references a valid cake (use first cart item)
        if ((int)$orderData['cake_id'] <= 0 && isset($_POST['cart_items'])) {
            $cartItemsJson = $_POST['cart_items'];
            $decoded = json_decode($cartItemsJson, true);
            if (is_array($decoded) && count($decoded) > 0 && isset($decoded[0]['id'])) {
                $orderData['cake_id'] = (int)$decoded[0]['id'];
            }
        }
        
        // Validate critical fields
        if ($orderData['user_id'] <= 0) {
            throw new Exception("Invalid user ID");
        }
        
        // Validate cake_id after potential cart adjustment
        if ($orderData['cake_id'] <= 0) {
            throw new Exception("Invalid cake ID");
        }
        
        if (empty($orderData['payment_id'])) {
            throw new Exception("Payment ID is required");
        }
        
        $orderId = $orderModel->createOrder($orderData);
        
        if ($orderId) {
            error_log("Order created successfully with ID: $orderId");
            // Clear cart if this was a cart checkout
            if (isset($_POST['cart_items'])) {
                $_SESSION['cart'] = [];
            }
            echo json_encode([
                'success' => true,
                'message' => 'Order placed successfully',
                'order_id' => $orderId
            ]);
        } else {
            throw new Exception("Failed to create order - no order ID returned");
        }
        
    } catch (Exception $e) {
        error_log("Order Processing Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
    
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method or missing action parameter'
    ]);
}
?>