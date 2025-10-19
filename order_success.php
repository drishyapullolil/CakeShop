<?php
// order_success.php
session_start();

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
        $this->conn = new mysqli($this->host, $this->username, $this->password, $this->database);
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
        $this->conn->set_charset("utf8mb4");
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
    
    public function getOrderById($orderId) {
        $stmt = $this->conn->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $stmt->close();
        return $order;
    }
    
    public function __destruct() {
        $this->db->closeConnection();
    }
}

// Check if order ID is provided
if (!isset($_GET['order_id'])) {
    header('Location: index.php');
    exit();
}

$orderId = intval($_GET['order_id']);
$orderModel = new Order();
$order = $orderModel->getOrderById($orderId);

if (!$order) {
    die("Order not found!");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Successful - Sweet Delights</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .success-container {
            max-width: 700px;
            width: 100%;
            background: white;
            border-radius: 25px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
            overflow: hidden;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .success-header {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            padding: 50px 30px;
            text-align: center;
            color: white;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 50px;
            animation: checkmark 0.5s ease-in-out 0.3s both;
        }

        @keyframes checkmark {
            0% {
                transform: scale(0);
            }
            50% {
                transform: scale(1.2);
            }
            100% {
                transform: scale(1);
            }
        }

        .success-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .success-header p {
            font-size: 16px;
            opacity: 0.95;
        }

        .order-details {
            padding: 40px;
        }

        .detail-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            border-left: 4px solid #38ef7d;
        }

        .detail-section h2 {
            color: #333;
            font-size: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #666;
            font-weight: 500;
        }

        .detail-value {
            color: #333;
            font-weight: 600;
            text-align: right;
            max-width: 60%;
            word-wrap: break-word;
        }

        .total-amount {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            margin: 25px 0;
        }

        .total-amount .label {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 10px;
        }

        .total-amount .amount {
            font-size: 36px;
            font-weight: bold;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.6);
        }

        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-secondary:hover {
            background: #f0f3ff;
        }

        .info-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 12px;
            padding: 20px;
            margin-top: 25px;
            display: flex;
            gap: 15px;
            align-items: start;
        }

        .info-box-icon {
            font-size: 24px;
            flex-shrink: 0;
        }

        .info-box-content h3 {
            color: #856404;
            font-size: 16px;
            margin-bottom: 8px;
        }

        .info-box-content p {
            color: #856404;
            font-size: 14px;
            line-height: 1.6;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            background: #d4edda;
            color: #155724;
            text-transform: capitalize;
        }

        .print-btn {
            background: #28a745;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }

        .print-btn:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            .action-buttons, .print-btn, .info-box {
                display: none;
            }
        }

        @media (max-width: 600px) {
            .action-buttons {
                flex-direction: column;
            }
            
            .success-header h1 {
                font-size: 24px;
            }

            .order-details {
                padding: 20px;
            }

            .detail-value {
                max-width: 50%;
            }

            .total-amount .amount {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-header">
            <div class="success-icon">✓</div>
            <h1>Order Placed Successfully!</h1>
            <p>Thank you for your order. We'll deliver it soon.</p>
        </div>

        <div class="order-details">
            <button class="print-btn" onclick="window.print()">
                🖨️ Print Order Details
            </button>

            <div class="detail-section">
                <h2>📋 Order Information</h2>
                <div class="detail-row">
                    <span class="detail-label">Order ID:</span>
                    <span class="detail-value">#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Order Date:</span>
                    <span class="detail-value"><?= date('M d, Y - h:i A', strtotime($order['order_date'])) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value">
                        <span class="status-badge"><?= ucfirst($order['status']) ?></span>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Payment ID:</span>
                    <span class="detail-value"><?= htmlspecialchars($order['payment_id']) ?></span>
                </div>
            </div>

            <div class="detail-section">
                <h2>🎂 Product Details</h2>
                <div class="detail-row">
                    <span class="detail-label">Cake:</span>
                    <span class="detail-value"><?= htmlspecialchars($order['cake_name']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Price per Unit:</span>
                    <span class="detail-value">₹<?= number_format($order['price'], 2) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Quantity:</span>
                    <span class="detail-value"><?= $order['quantity'] ?></span>
                </div>
            </div>

            <div class="detail-section">
                <h2>📍 Delivery Information</h2>
                <div class="detail-row">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value"><?= htmlspecialchars($order['customer_name']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Phone:</span>
                    <span class="detail-value"><?= htmlspecialchars($order['customer_phone']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value"><?= htmlspecialchars($order['customer_email']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Address:</span>
                    <span class="detail-value"><?= nl2br(htmlspecialchars($order['customer_address'])) ?></span>
                </div>
                <?php if (!empty($order['special_instructions'])): ?>
                <div class="detail-row">
                    <span class="detail-label">Special Instructions:</span>
                    <span class="detail-value"><?= nl2br(htmlspecialchars($order['special_instructions'])) ?></span>
                </div>
                <?php endif; ?>
            </div>

            <div class="total-amount">
                <div class="label">Total Amount Paid</div>
                <div class="amount">₹<?= number_format($order['total_amount'], 2) ?></div>
            </div>

            <div class="info-box">
                <div class="info-box-icon">ℹ️</div>
                <div class="info-box-content">
                    <h3>What's Next?</h3>
                    <p>We've received your order and payment. Our team will prepare your delicious cake and deliver it to your address. You'll receive updates via email and SMS.</p>
                </div>
            </div>

            <div class="action-buttons">
                <a href="index.php" class="btn btn-primary">Continue Shopping</a>
                <a href="my_orders.php" class="btn btn-secondary">View My Orders</a>
            </div>
        </div>
    </div>

    <script>
        // Auto-scroll to top on page load
        window.onload = function() {
            window.scrollTo(0, 0);
        };
    </script>
</body>
</html>