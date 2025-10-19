<?php
// logout.php - OOP Based Logout System
session_start();

// Session Management Class
class SessionManager {
    private $redirectUrl;
    
    public function __construct($redirectUrl = 'login.php') {
        $this->redirectUrl = $redirectUrl;
    }
    
    // Destroy all session data
    public function logout() {
        // Unset all session variables
        $_SESSION = array();
        
        // Destroy the session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy the session
        session_destroy();
        
        return true;
    }
    
    // Redirect to specified URL
    public function redirect($url = null) {
        $redirectTo = $url ? $url : $this->redirectUrl;
        header("Location: " . $redirectTo);
        exit();
    }
    
    // Get user name before logout
    public function getUserName() {
        return isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest';
    }
    
    // Check if user was logged in
    public function wasLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
}

// Initialize Session Manager
$sessionManager = new SessionManager('login.php');

// Check if user wants to logout immediately
if (isset($_GET['instant']) && $_GET['instant'] === 'yes') {
    $sessionManager->logout();
    $sessionManager->redirect('login.php');
}

// Store user name before logout for display
$userName = $sessionManager->getUserName();
$wasLoggedIn = $sessionManager->wasLoggedIn();

// Handle logout confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_logout'])) {
    $sessionManager->logout();
    $sessionManager->redirect('login.php?logout=success');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout - Sweet Delights</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .logout-container {
            max-width: 500px;
            width: 100%;
            background: white;
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .logout-header {
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
            padding: 50px 30px;
            text-align: center;
            color: white;
        }

        .logout-icon {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }

        .logout-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .logout-header p {
            font-size: 16px;
            opacity: 0.95;
        }

        .logout-content {
            padding: 40px 30px;
        }

        .user-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
            border-left: 4px solid #667eea;
        }

        .user-info h3 {
            color: #333;
            font-size: 18px;
            margin-bottom: 10px;
        }

        .user-info p {
            color: #666;
            font-size: 14px;
        }

        .logout-message {
            text-align: center;
            margin-bottom: 30px;
        }

        .logout-message h2 {
            color: #333;
            font-size: 22px;
            margin-bottom: 10px;
        }

        .logout-message p {
            color: #666;
            font-size: 15px;
            line-height: 1.6;
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }

        .btn {
            flex: 1;
            padding: 16px;
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

        .btn-logout {
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
            color: white;
            box-shadow: 0 5px 15px rgba(235, 51, 73, 0.4);
        }

        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(235, 51, 73, 0.6);
        }

        .btn-cancel {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-cancel:hover {
            background: #f0f3ff;
            transform: translateY(-2px);
        }

        .quick-links {
            margin-top: 30px;
            padding-top: 25px;
            border-top: 2px solid #f0f0f0;
        }

        .quick-links h4 {
            color: #333;
            font-size: 14px;
            margin-bottom: 15px;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .link-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .quick-link {
            padding: 12px;
            background: #f8f9fa;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            color: #667eea;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .quick-link:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }

        .countdown {
            text-align: center;
            margin-top: 20px;
            color: #999;
            font-size: 13px;
        }

        .countdown-timer {
            color: #667eea;
            font-weight: bold;
            font-size: 16px;
        }

        @media (max-width: 500px) {
            .logout-container {
                border-radius: 15px;
            }

            .logout-header h1 {
                font-size: 24px;
            }

            .logout-content {
                padding: 30px 20px;
            }

            .button-group {
                flex-direction: column;
            }

            .link-grid {
                grid-template-columns: 1fr;
            }
        }

        .info-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 12px;
            padding: 15px;
            margin-top: 20px;
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .info-box-icon {
            font-size: 20px;
            flex-shrink: 0;
        }

        .info-box-text {
            color: #856404;
            font-size: 13px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="logout-header">
            <div class="logout-icon">👋</div>
            <h1>Logout Confirmation</h1>
            <p>We're sad to see you go!</p>
        </div>

        <div class="logout-content">
            <?php if ($wasLoggedIn): ?>
                <div class="user-info">
                    <h3>👤 <?= htmlspecialchars($userName) ?></h3>
                    <p>Currently logged in</p>
                </div>
            <?php endif; ?>

            <div class="logout-message">
                <h2>Are you sure you want to logout?</h2>
                <p>You will need to login again to access your account and place orders.</p>
            </div>

            <form method="POST" action="">
                <div class="button-group">
                    <button type="submit" name="confirm_logout" class="btn btn-logout">
                        Yes, Logout
                    </button>
                    <a href="index.php" class="btn btn-cancel">
                        Cancel
                    </a>
                </div>
            </form>

            <div class="info-box">
                <div class="info-box-icon">💡</div>
                <div class="info-box-text">
                    Your cart items and preferences will be saved for your next visit!
                </div>
            </div>

            <div class="quick-links">
                <h4>Quick Navigation</h4>
                <div class="link-grid">
                    <a href="index.php" class="quick-link">🏠 Home</a>
                    <a href="my_orders.php" class="quick-link">📦 My Orders</a>
                    <a href="index.php#products" class="quick-link">🎂 Products</a>
                    <a href="index.php#contact" class="quick-link">📞 Contact</a>
                </div>
            </div>

            <div class="countdown" id="autoRedirect">
                <p>Auto-redirecting to home in <span class="countdown-timer" id="timer">30</span> seconds</p>
            </div>
        </div>
    </div>

    <script>
        // Auto redirect countdown
        let timeLeft = 30;
        const timerElement = document.getElementById('timer');
        
        const countdown = setInterval(() => {
            timeLeft--;
            timerElement.textContent = timeLeft;
            
            if (timeLeft <= 0) {
                clearInterval(countdown);
                window.location.href = 'index.php';
            }
        }, 1000);

        // Stop countdown if user interacts with the page
        document.addEventListener('click', () => {
            clearInterval(countdown);
            document.getElementById('autoRedirect').style.display = 'none';
        });

        // Keyboard shortcut: Press 'Y' to logout, 'N' to cancel
        document.addEventListener('keydown', (e) => {
            if (e.key.toLowerCase() === 'y') {
                document.querySelector('button[name="confirm_logout"]').click();
            } else if (e.key.toLowerCase() === 'n') {
                window.location.href = 'index.php';
            }
        });
    </script>
</body>
</html>