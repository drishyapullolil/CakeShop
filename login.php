<?php
// login.php - OOP Based Login System
session_start();

// Default admin configuration
if (!defined('ADMIN_EMAIL')) {
    define('ADMIN_EMAIL', 'admin@sweetdelights.com');
}
if (!defined('ADMIN_NAME')) {
    define('ADMIN_NAME', 'Admin');
}
if (!defined('ADMIN_PHONE')) {
    define('ADMIN_PHONE', '0000000000');
}
if (!defined('ADMIN_DEFAULT_PASSWORD')) {
    define('ADMIN_DEFAULT_PASSWORD', 'Admin@123');
}

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
class Auth {
    private $db;
    private $conn;
    private $errors = [];
    private $success = "";
    
    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    // Ensure a default admin user exists
    public function ensureDefaultAdmin() {
        $email = ADMIN_EMAIL;
        $name = ADMIN_NAME;
        $phone = ADMIN_PHONE;
        $passwordPlain = ADMIN_DEFAULT_PASSWORD;

        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->store_result();
            $exists = $stmt->num_rows > 0;
            $stmt->close();
            if ($exists) return; // already present
        }

        // Create admin user
        $hashed = password_hash($passwordPlain, PASSWORD_DEFAULT);
        $stmt2 = $this->conn->prepare("INSERT INTO users (name, email, phone, password, created_at) VALUES (?, ?, ?, ?, NOW())");
        if ($stmt2) {
            $stmt2->bind_param('ssss', $name, $email, $phone, $hashed);
            $stmt2->execute();
            $stmt2->close();
        }
    }
    
    // Validate email format
    private function validateEmail($email) {
        if (empty($email)) {
            $this->errors[] = "Email is required";
            return false;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = "Invalid email format";
            return false;
        }
        return true;
    }
    
    // Validate password
    private function validatePassword($password, $minLength = 6) {
        if (empty($password)) {
            $this->errors[] = "Password is required";
            return false;
        }
        if (strlen($password) < $minLength) {
            $this->errors[] = "Password must be at least {$minLength} characters";
            return false;
        }
        return true;
    }
    
    // Login user
    public function login($email, $password) {
        $this->errors = [];
        
        if (!$this->validateEmail($email) || !$this->validatePassword($password)) {
            return false;
        }
        
        $stmt = $this->conn->prepare("SELECT id, name, email, phone, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_phone'] = $user['phone'];
                $_SESSION['logged_in'] = true;
                $_SESSION['is_admin'] = (strcasecmp($user['email'], ADMIN_EMAIL) === 0);
                
                $stmt->close();
                return true;
            } else {
                $this->errors[] = "Invalid email or password";
            }
        } else {
            $this->errors[] = "Invalid email or password";
        }
        
        $stmt->close();
        return false;
    }
    
    // Register new user
    public function register($name, $email, $phone, $password, $confirmPassword) {
        $this->errors = [];
        
        // Validate name
        if (empty($name)) {
            $this->errors[] = "Name is required";
        } elseif (strlen($name) < 3) {
            $this->errors[] = "Name must be at least 3 characters";
        }
        
        // Validate email
        $this->validateEmail($email);
        
        // Validate phone
        if (empty($phone)) {
            $this->errors[] = "Phone number is required";
        } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
            $this->errors[] = "Phone number must be 10 digits";
        }
        
        // Validate password
        $this->validatePassword($password, 6);
        
        // Check password confirmation
        if ($password !== $confirmPassword) {
            $this->errors[] = "Passwords do not match";
        }
        
        if (!empty($this->errors)) {
            return false;
        }
        
        // Check if email already exists
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $this->errors[] = "Email already registered";
            $stmt->close();
            return false;
        }
        $stmt->close();
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $this->conn->prepare("INSERT INTO users (name, email, phone, password, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssss", $name, $email, $phone, $hashedPassword);
        
        if ($stmt->execute()) {
            $userId = $stmt->insert_id;
            
            // Auto login after registration
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_phone'] = $phone;
            $_SESSION['logged_in'] = true;
            
            $this->success = "Registration successful! Welcome aboard.";
            $stmt->close();
            return true;
        } else {
            $this->errors[] = "Registration failed. Please try again.";
            $stmt->close();
            return false;
        }
    }
    
    // Check if user is already logged in
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    // Get errors
    public function getErrors() {
        return $this->errors;
    }
    
    // Get success message
    public function getSuccess() {
        return $this->success;
    }
    
    public function __destruct() {
        $this->db->closeConnection();
    }
}

// Initialize Auth class
$auth = new Auth();
// Ensure default admin exists
$auth->ensureDefaultAdmin();

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
        header('Location: admin_add_product.php');
    } else {
        header('Location: index.php');
    }
    exit();
}

// Handle form submissions
$errors = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        // Login form submitted
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        
        if ($auth->login($email, $password)) {
            if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
                header('Location: admin_add_product.php');
            } else {
                header('Location: index.php');
            }
            exit();
        } else {
            $errors = $auth->getErrors();
        }
    } elseif (isset($_POST['register'])) {
        // Registration form submitted
        $name = trim($_POST['name']);
        $email = trim($_POST['reg_email']);
        $phone = trim($_POST['phone']);
        $password = $_POST['reg_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        if ($auth->register($name, $email, $phone, $password, $confirmPassword)) {
            header('Location: index.php');
            exit();
        } else {
            $errors = $auth->getErrors();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login / Register - Sweet Delights</title>
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

        .login-container {
            max-width: 450px;
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
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }

        .logo-section h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .logo-section p {
            font-size: 16px;
            opacity: 0.9;
        }

        .tabs {
            display: flex;
            background: #f8f9fa;
        }

        .tab {
            flex: 1;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .tab.active {
            color: #667eea;
            background: white;
            border-bottom-color: #667eea;
        }

        .tab:hover {
            background: #f0f3ff;
        }

        .form-container {
            padding: 40px 30px;
        }

        .form-content {
            display: none;
        }

        .form-content.active {
            display: block;
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 18px;
        }

        .form-group input {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .is-invalid {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.12) !important;
        }

        .is-valid {
            border-color: #28a745 !important;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.12) !important;
        }

        .field-error {
            color: #dc3545;
            font-size: 12px;
            margin-top: 6px;
            min-height: 14px;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            font-size: 18px;
            user-select: none;
        }

        .submit-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.6);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            box-shadow: none;
        }

        .forgot-password {
            text-align: right;
            margin-top: 15px;
        }

        .forgot-password a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 25px 0;
            color: #999;
            font-size: 14px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e0e0e0;
        }

        .divider span {
            padding: 0 15px;
        }

        .guest-btn {
            width: 100%;
            padding: 14px;
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .guest-btn:hover {
            background: #f0f3ff;
            transform: translateY(-2px);
        }

        .alert {
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert ul {
            margin: 0;
            padding-left: 20px;
        }

        .alert li {
            margin: 5px 0;
        }

        .password-strength {
            margin-top: 8px;
            font-size: 12px;
        }

        .strength-bar {
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            margin-top: 5px;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s;
        }

        .strength-weak { width: 33%; background: #dc3545; }
        .strength-medium { width: 66%; background: #ffc107; }
        .strength-strong { width: 100%; background: #28a745; }

        @media (max-width: 500px) {
            .login-container {
                border-radius: 15px;
            }

            .logo-section h1 {
                font-size: 26px;
            }

            .form-container {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-section">
            <h1>🎂 Sweet Delights</h1>
            <p>Your favorite cake shop</p>
        </div>

        <div class="tabs">
            <div class="tab active" onclick="switchTab('login')">Login</div>
            <div class="tab" onclick="switchTab('register')">Register</div>
        </div>

        <div class="form-container">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <div id="loginForm" class="form-content active">
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Email Address</label>
                        <div class="input-wrapper">
                            <span class="input-icon">📧</span>
                            <input type="email" id="loginEmail" name="email" placeholder="Enter your email" required>
                        </div>
                        <div class="field-error" id="loginEmailError"></div>
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <div class="input-wrapper">
                            <span class="input-icon">🔒</span>
                            <input type="password" id="loginPassword" name="password" placeholder="Enter your password" required>
                            <span class="password-toggle" onclick="togglePassword('loginPassword')">👁️</span>
                        </div>
                        <div class="field-error" id="loginPasswordError"></div>
                    </div>

                    <button type="submit" id="loginSubmit" name="login" class="submit-btn" disabled>
                        Login to Account
                    </button>

                    <div class="forgot-password">
                        <a href="#" onclick="alert('Please contact support for password reset'); return false;">Forgot Password?</a>
                    </div>

                    <div class="divider">
                        <span>OR</span>
                    </div>

                    <button type="button" class="guest-btn" onclick="window.location.href='index.php'">
                        Continue as Guest
                    </button>
                </form>
            </div>

            <!-- Registration Form -->
            <div id="registerForm" class="form-content">
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Full Name</label>
                        <div class="input-wrapper">
                            <span class="input-icon">👤</span>
                            <input type="text" id="regName" name="name" placeholder="Enter your full name" required>
                        </div>
                        <div class="field-error" id="regNameError"></div>
                    </div>

                    <div class="form-group">
                        <label>Email Address</label>
                        <div class="input-wrapper">
                            <span class="input-icon">📧</span>
                            <input type="email" id="regEmail" name="reg_email" placeholder="Enter your email" required>
                        </div>
                        <div class="field-error" id="regEmailError"></div>
                    </div>

                    <div class="form-group">
                        <label>Phone Number</label>
                        <div class="input-wrapper">
                            <span class="input-icon">📱</span>
                            <input type="tel" id="regPhone" name="phone" placeholder="10 digit mobile number" pattern="[0-9]{10}" required>
                        </div>
                        <div class="field-error" id="regPhoneError"></div>
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <div class="input-wrapper">
                            <span class="input-icon">🔒</span>
                            <input type="password" id="regPassword" name="reg_password" placeholder="Create a password" required oninput="checkPasswordStrength(this.value)">
                            <span class="password-toggle" onclick="togglePassword('regPassword')">👁️</span>
                        </div>
                        <div class="password-strength">
                            <div class="strength-bar">
                                <div id="strengthFill" class="strength-fill"></div>
                            </div>
                            <span id="strengthText"></span>
                        </div>
                        <div class="field-error" id="regPasswordError"></div>
                    </div>

                    <div class="form-group">
                        <label>Confirm Password</label>
                        <div class="input-wrapper">
                            <span class="input-icon">🔒</span>
                            <input type="password" id="confirmPassword" name="confirm_password" placeholder="Re-enter your password" required>
                            <span class="password-toggle" onclick="togglePassword('confirmPassword')">👁️</span>
                        </div>
                        <div class="field-error" id="confirmPasswordError"></div>
                    </div>

                    <button type="submit" id="registerSubmit" name="register" class="submit-btn" disabled>
                        Create Account
                    </button>

                    <div class="divider">
                        <span>OR</span>
                    </div>

                    <button type="button" class="guest-btn" onclick="window.location.href='index.php'">
                        Continue as Guest
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            // Update tab styles
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');

            // Update form visibility
            const forms = document.querySelectorAll('.form-content');
            forms.forEach(f => f.classList.remove('active'));
            
            if (tab === 'login') {
                document.getElementById('loginForm').classList.add('active');
            } else {
                document.getElementById('registerForm').classList.add('active');
            }
        }

        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const toggle = event.target;
            
            if (input.type === 'password') {
                input.type = 'text';
                toggle.textContent = '🙈';
            } else {
                input.type = 'password';
                toggle.textContent = '👁️';
            }
        }

        function checkPasswordStrength(password) {
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            
            let strength = 0;
            
            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            strengthFill.className = 'strength-fill';
            
            if (strength <= 2) {
                strengthFill.classList.add('strength-weak');
                strengthText.textContent = 'Weak password';
                strengthText.style.color = '#dc3545';
            } else if (strength <= 4) {
                strengthFill.classList.add('strength-medium');
                strengthText.textContent = 'Medium password';
                strengthText.style.color = '#ffc107';
            } else {
                strengthFill.classList.add('strength-strong');
                strengthText.textContent = 'Strong password';
                strengthText.style.color = '#28a745';
            }
        }

        // Real-time validation (Flipkart-like)
        document.addEventListener('DOMContentLoaded', () => {
            // Login form elements
            const loginEmail = document.getElementById('loginEmail');
            const loginPassword = document.getElementById('loginPassword');
            const loginEmailError = document.getElementById('loginEmailError');
            const loginPasswordError = document.getElementById('loginPasswordError');
            const loginSubmit = document.getElementById('loginSubmit');

            // Registration form elements
            const regName = document.getElementById('regName');
            const regEmail = document.getElementById('regEmail');
            const regPhone = document.getElementById('regPhone');
            const regPassword = document.getElementById('regPassword');
            const confirmPassword = document.getElementById('confirmPassword');
            const regNameError = document.getElementById('regNameError');
            const regEmailError = document.getElementById('regEmailError');
            const regPhoneError = document.getElementById('regPhoneError');
            const regPasswordError = document.getElementById('regPasswordError');
            const confirmPasswordError = document.getElementById('confirmPasswordError');
            const registerSubmit = document.getElementById('registerSubmit');

            const emailRe = /^\S+@\S+\.\S+$/;
            const phoneRe = /^[0-9]{10}$/;

            function setValid(el, isValid, msgEl, msg) {
                if (!el) return false;
                if (isValid) {
                    el.classList.remove('is-invalid');
                    el.classList.add('is-valid');
                    if (msgEl) msgEl.textContent = '';
                } else {
                    el.classList.remove('is-valid');
                    el.classList.add('is-invalid');
                    if (msgEl) msgEl.textContent = msg || '';
                }
                return isValid;
            }

            function validateLogin() {
                const eValid = setValid(
                    loginEmail,
                    !!loginEmail && emailRe.test(loginEmail.value.trim()),
                    loginEmailError,
                    'Please enter a valid email'
                );
                // For login password: no real-time validation UI, only require non-empty to enable submit
                const pValid = !!loginPassword && loginPassword.value.length > 0;
                if (loginPassword) {
                    loginPassword.classList.remove('is-invalid');
                    loginPassword.classList.remove('is-valid');
                }
                if (loginPasswordError) loginPasswordError.textContent = '';
                if (loginSubmit) loginSubmit.disabled = !(eValid && pValid);
            }

            function validateRegister() {
                const nValid = setValid(
                    regName,
                    !!regName && regName.value.trim().length >= 3,
                    regNameError,
                    'Name must be at least 3 characters'
                );
                const eValid = setValid(
                    regEmail,
                    !!regEmail && emailRe.test(regEmail.value.trim()),
                    regEmailError,
                    'Please enter a valid email'
                );
                const phValid = setValid(
                    regPhone,
                    !!regPhone && phoneRe.test(regPhone.value.trim()),
                    regPhoneError,
                    'Enter 10 digit mobile number'
                );
                const rpValid = setValid(
                    regPassword,
                    !!regPassword && regPassword.value.length >= 6,
                    regPasswordError,
                    'Minimum 6 characters'
                );
                const cValid = setValid(
                    confirmPassword,
                    !!confirmPassword && confirmPassword.value === regPassword.value && confirmPassword.value.length > 0,
                    confirmPasswordError,
                    'Passwords do not match'
                );
                if (registerSubmit) registerSubmit.disabled = !(nValid && eValid && phValid && rpValid && cValid);
            }

            // Attach input listeners for real-time feedback
            if (loginEmail) loginEmail.addEventListener('input', validateLogin);
            // For login password: attach listener to enable button when user types (no inline errors/styling)
            if (loginPassword) loginPassword.addEventListener('input', validateLogin);
            if (regName) regName.addEventListener('input', validateRegister);
            if (regEmail) regEmail.addEventListener('input', validateRegister);
            if (regPhone) regPhone.addEventListener('input', validateRegister);
            if (regPassword) regPassword.addEventListener('input', validateRegister);
            if (confirmPassword) confirmPassword.addEventListener('input', validateRegister);

            // Initial validation on load and after autofill
            validateLogin();
            setTimeout(validateLogin, 150);
            validateRegister();
        });
    </script>
</body>
</html>
