<?php
// cart_payment.php - Handle Payment for Full Cart
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

// User Authentication Class
class User {
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function getUserName() {
        return isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest User';
    }
    
    public function getUserEmail() {
        return isset($_SESSION['user_email']) ? $_SESSION['user_email'] : 'guest@example.com';
    }
    
    public function getUserPhone() {
        return isset($_SESSION['user_phone']) ? $_SESSION['user_phone'] : '';
    }
    
    public function getUserId() {
        return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
    }
}

// Payment Handler Class
class PaymentHandler {
    private $razorpayKey = "rzp_test_qz3vZymFK7JynA";
    
    public function getRazorpayKey() {
        return $this->razorpayKey;
    }
}

// Initialize
$userModel = new User();
$paymentHandler = new PaymentHandler();

// Check if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit();
}

$cartItems = $_SESSION['cart'];
$totalAmount = 0;
$totalQuantity = 0;

foreach ($cartItems as $item) {
    $totalAmount += $item['price'] * $item['quantity'];
    $totalQuantity += $item['quantity'];
}

// User details
$userName = $userModel->getUserName();
$userEmail = $userModel->getUserEmail();
$userPhone = $userModel->getUserPhone();
$userId = $userModel->getUserId();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart Checkout - Sweet Delights</title>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
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
        }

        .container {
            max-width: 1100px;
            margin: 40px auto;
            background: white;
            border-radius: 25px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            text-align: center;
            color: white;
        }

        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 16px;
            opacity: 0.9;
        }

        .content {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 30px;
            padding: 40px;
        }

        .order-summary {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 20px;
            border: 2px solid #667eea;
        }

        .order-summary h2 {
            color: #764ba2;
            margin-bottom: 25px;
            font-size: 24px;
        }

        .cart-items-list {
            max-height: 400px;
            overflow-y: auto;
            margin-bottom: 20px;
        }

        .cart-item-row {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .cart-item-row h4 {
            color: #333;
            font-size: 16px;
            margin-bottom: 8px;
        }

        .cart-item-details {
            display: flex;
            justify-content: space-between;
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .cart-item-total {
            color: #667eea;
            font-weight: bold;
            font-size: 16px;
            margin-top: 8px;
            text-align: right;
        }

        .price-breakdown {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-top: 20px;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            margin: 12px 0;
            font-size: 16px;
        }

        .price-row.total {
            border-top: 2px solid #667eea;
            padding-top: 15px;
            margin-top: 15px;
            font-size: 22px;
            font-weight: bold;
            color: #764ba2;
        }

        .customer-details {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 20px;
            border: 2px solid #f093fb;
        }

        .customer-details h2 {
            color: #764ba2;
            margin-bottom: 25px;
            font-size: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #555;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .payment-btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
            margin-top: 20px;
        }

        .payment-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(102, 126, 234, 0.6);
        }

        .payment-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .back-btn {
            display: inline-block;
            padding: 12px 30px;
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            text-decoration: none;
            border-radius: 10px;
            margin-top: 20px;
            transition: all 0.3s;
            font-weight: 600;
        }

        .back-btn:hover {
            background: rgba(102, 126, 234, 0.2);
            transform: translateX(-5px);
        }

        .secure-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
            color: #28a745;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .content {
                grid-template-columns: 1fr;
            }
            
            .header h1 {
                font-size: 24px;
            }
        }

        .success-message {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            margin: 20px 0;
            display: none;
        }

        .error-message {
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            margin: 20px 0;
            display: none;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 10px;
            color: #667eea;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛒 Complete Your Cart Purchase</h1>
            <p>Secure payment powered by Razorpay</p>
        </div>

        <div id="successMessage" class="success-message">
            <h2>✅ Payment Successful!</h2>
            <p>Your order has been placed successfully. Redirecting...</p>
        </div>

        <div id="errorMessage" class="error-message">
            <h2>❌ Payment Failed!</h2>
            <p id="errorText">Something went wrong. Please try again.</p>
        </div>

        <div id="loadingMessage" class="loading">
            <p>⏳ Processing your order... Please wait...</p>
        </div>

        <div class="content">
            <div class="order-summary">
                <h2>📋 Order Summary</h2>
                
                <div class="cart-items-list">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="cart-item-row">
                            <h4><?= htmlspecialchars($item['name']) ?></h4>
                            <div class="cart-item-details">
                                <span>Price: ₹<?= number_format($item['price'], 2) ?></span>
                                <span>Qty: <?= $item['quantity'] ?></span>
                            </div>
                            <div class="cart-item-total">
                                Subtotal: ₹<?= number_format($item['price'] * $item['quantity'], 2) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="price-breakdown">
                    <div class="price-row">
                        <span>Total Items:</span>
                        <span><?= count($cartItems) ?> products</span>
                    </div>
                    <div class="price-row">
                        <span>Total Quantity:</span>
                        <span><?= $totalQuantity ?> items</span>
                    </div>
                    <div class="price-row">
                        <span>Delivery Charges:</span>
                        <span style="color: #28a745;">FREE</span>
                    </div>
                    <div class="price-row total">
                        <span>Total Amount:</span>
                        <span id="totalAmount">₹<?= number_format($totalAmount, 2) ?></span>
                    </div>
                </div>

                <a href="cart.php" class="back-btn">← Back to Cart</a>
            </div>

            <div class="customer-details">
                <h2>👤 Customer Details</h2>
                
                <form id="paymentForm">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" id="customerName" value="<?= htmlspecialchars($userName) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="email" id="customerEmail" value="<?= htmlspecialchars($userEmail) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Phone Number *</label>
                        <input type="tel" id="customerPhone" value="<?= htmlspecialchars($userPhone) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Delivery Address *</label>
                        <textarea id="customerAddress" required placeholder="Enter your complete delivery address"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Special Instructions (Optional)</label>
                        <textarea id="specialInstructions" placeholder="Any special requests or delivery instructions"></textarea>
                    </div>

                    <button type="button" class="payment-btn" id="paymentButton" onclick="initiatePayment()">
                        🔒 Pay ₹<?= number_format($totalAmount, 2) ?>
                    </button>

                    <div class="secure-badge">
                        <span>🔐</span>
                        <span>100% Secure Payment via Razorpay</span>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const cartItems = <?= json_encode(array_values($cartItems)) ?>;
        const totalAmount = <?= $totalAmount ?>;
        const userId = <?= $userId ?>;

        function initiatePayment() {
            const name = document.getElementById('customerName').value.trim();
            const email = document.getElementById('customerEmail').value.trim();
            const phone = document.getElementById('customerPhone').value.trim();
            const address = document.getElementById('customerAddress').value.trim();

            if (!name || !email || !phone || !address) {
                alert('Please fill all required fields!');
                return;
            }

            // Disable button to prevent double clicks
            const paymentBtn = document.getElementById('paymentButton');
            paymentBtn.disabled = true;
            paymentBtn.textContent = 'Opening Payment Gateway...';

            var options = {
                "key": "<?= $paymentHandler->getRazorpayKey() ?>",
                "amount": totalAmount * 100, // Amount in paise
                "currency": "INR",
                "name": "Sweet Delights",
                "description": "Cart Purchase - " + cartItems.length + " items",
                "image": "https://cdn-icons-png.flaticon.com/512/3081/3081559.png",
                "handler": function (response) {
                    console.log("Payment successful:", response);
                    // Payment successful - save order
                    saveOrder(response.razorpay_payment_id, response.razorpay_order_id || 'NA');
                },
                "prefill": {
                    "name": name,
                    "email": email,
                    "contact": phone
                },
                "notes": {
                    "address": address,
                    "total_items": cartItems.length
                },
                "theme": {
                    "color": "#667eea"
                },
                "modal": {
                    "ondismiss": function() {
                        console.log("Payment cancelled");
                        paymentBtn.disabled = false;
                        paymentBtn.textContent = '🔒 Pay ₹' + totalAmount.toFixed(2);
                        showError("Payment cancelled by user");
                    }
                }
            };

            var rzp = new Razorpay(options);
            rzp.on('payment.failed', function (response) {
                console.log("Payment failed:", response);
                paymentBtn.disabled = false;
                paymentBtn.textContent = '🔒 Pay ₹' + totalAmount.toFixed(2);
                showError(response.error.description);
            });
            
            try {
                rzp.open();
            } catch (error) {
                console.error("Error opening Razorpay:", error);
                paymentBtn.disabled = false;
                paymentBtn.textContent = '🔒 Pay ₹' + totalAmount.toFixed(2);
                showError("Failed to open payment gateway");
            }
        }

        function saveOrder(paymentId, orderId) {
            console.log("Saving cart order with:", {paymentId, orderId});
            
            // Show loading
            document.getElementById('loadingMessage').style.display = 'block';
            document.getElementById('paymentButton').disabled = true;
            
            // Save order to database
            const formData = new FormData();
            formData.append('action', 'save_cart_order');
            formData.append('user_id', userId);
            formData.append('cart_items', JSON.stringify(cartItems));
            formData.append('total_amount', totalAmount);
            formData.append('payment_id', paymentId);
            formData.append('razorpay_order_id', orderId);
            formData.append('customer_name', document.getElementById('customerName').value);
            formData.append('customer_email', document.getElementById('customerEmail').value);
            formData.append('customer_phone', document.getElementById('customerPhone').value);
            formData.append('customer_address', document.getElementById('customerAddress').value);
            formData.append('special_instructions', document.getElementById('specialInstructions').value);

            fetch('process_cart_order.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log("Response status:", response.status);
                return response.text();
            })
            .then(text => {
                console.log("Response text:", text);
                try {
                    const data = JSON.parse(text);
                    console.log("Parsed data:", data);
                    
                    document.getElementById('loadingMessage').style.display = 'none';
                    
                    if (data.success) {
                        showSuccess();
                        setTimeout(() => {
                            window.location.href = 'order_success.php?order_id=' + data.order_id;
                        }, 2000);
                    } else {
                        showError("Failed to save order: " + (data.message || "Unknown error"));
                    }
                } catch (e) {
                    console.error("JSON parse error:", e);
                    showError("Server error: Invalid response format. Response was: " + text.substring(0, 100));
                }
            })
            .catch(error => {
                console.error("Fetch error:", error);
                document.getElementById('loadingMessage').style.display = 'none';
                showError("Network error: " + error.message);
            });
        }

        function showSuccess() {
            document.getElementById('successMessage').style.display = 'block';
            document.querySelector('.content').style.display = 'none';
            window.scrollTo(0, 0);
        }

        function showError(message) {
            document.getElementById('errorText').textContent = message;
            document.getElementById('errorMessage').style.display = 'block';
            window.scrollTo(0, 0);
            setTimeout(() => {
                document.getElementById('errorMessage').style.display = 'none';
            }, 5000);
        }
    </script>
</body>
</html>