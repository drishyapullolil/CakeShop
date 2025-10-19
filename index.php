<?php
// index.php
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

// Cake Model Class
class Cake {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }
    
    public function getAllCakes() {
        $query = "SELECT * FROM cakes ORDER BY id ASC";
        $result = $this->conn->query($query);
        return $result;
    }
    
    public function getCakeById($id) {
        $id = $this->conn->real_escape_string($id);
        $query = "SELECT * FROM cakes WHERE id = '$id'";
        $result = $this->conn->query($query);
        return $result->fetch_assoc();
    }
    
    public function searchCakes($searchTerm) {
        $searchTerm = $this->conn->real_escape_string($searchTerm);
        $query = "SELECT * FROM cakes WHERE name LIKE '%$searchTerm%' ORDER BY id ASC";
        $result = $this->conn->query($query);
        return $result;
    }
    
    public function __destruct() {
        $this->db->closeConnection();
    }
}

// User Authentication Class
class User {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function getUserName() {
        return isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
    }
    
    public function getUserId() {
        return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    }
    
    public function login($email, $password) {
        $email = $this->conn->real_escape_string($email);
        $query = "SELECT * FROM users WHERE email = '$email'";
        $result = $this->conn->query($query);
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                return true;
            }
        }
        return false;
    }
    
    public function logout() {
        session_destroy();
        return true;
    }
    
    public function __destruct() {
        $this->db->closeConnection();
    }
}

// CakeView Class - Handles Display Logic
class CakeView {
    private $cakeImages;
    private $cakeDescriptions;
    
    public function __construct() {
        $this->cakeImages = [
            1 => "https://www.warmoven.in/cdn/shop/files/duel-delight-chocolate_-cake.jpg?v=1749833568&width=1080",
            2 => "https://stordflolretailpd.blob.core.windows.net/df-us/lolretail/media/lolr-media/recipe-collections/2025/april/retail_collection_birthday-cake-recipes_birthday-cake_850x800.webp?ext=.webp",
            3 => "https://images.unsplash.com/photo-1627308595229-7830a5c91f9f?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=400"
        ];
        
        $this->cakeDescriptions = [
            1 => "Indulgent chocolate layers with velvety ganache and rich cocoa flavor",
            2 => "Classic vanilla sponge with buttercream frosting - perfect for celebrations",
            3 => "Fresh strawberry cream with real fruit pieces and whipped delight"
        ];
    }
    
    public function getCakeImage($cakeId) {
        return isset($this->cakeImages[$cakeId]) 
            ? $this->cakeImages[$cakeId] 
            : "https://via.placeholder.com/300x250/667eea/ffffff?text=Delicious+Cake";
    }
    
    public function getCakeDescription($cakeId) {
        return isset($this->cakeDescriptions[$cakeId]) 
            ? $this->cakeDescriptions[$cakeId] 
            : "Freshly baked with premium ingredients and lots of love";
    }
    
    public function renderHeader($user) {
        $isLoggedIn = $user->isLoggedIn();
        $userName = $user->getUserName();
        ?>
        <header>
            <div class="header-container">
                <a href="index.php" class="logo">
                    <span>🎂</span>
                    <div>Sweet Delights</div>
                </a>
                
                <div class="user-section">
                    <?php if ($isLoggedIn): ?>
                        <span class="user-name">Welcome, <?= htmlspecialchars($userName) ?>!</span>
                        <a href="profile.php" class="user-icon" title="My Profile">👤</a>
                        <a href="logout.php" class="auth-btn">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="user-icon" title="Login / Sign Up">🔒</a>
                        <a href="login.php" class="auth-btn">Login / Sign Up</a>
                    <?php endif; ?>
                </div>
            </div>
        </header>
        <?php
    }
    
    public function renderCakeCard($cake) {
        $imgSrc = $this->getCakeImage($cake['id']);
        $description = $this->getCakeDescription($cake['id']);
        ?>
        <div class="cake-card" data-name="<?= strtolower(htmlspecialchars($cake['name'])) ?>">
            <div class="cake-image-wrapper">
                <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($cake['name']) ?>">
                <div class="badge">⭐ Bestseller</div>
            </div>
            <div class="cake-info">
                <h3><?= htmlspecialchars($cake['name']) ?></h3>
                <div class="rating">
                    <span class="stars">⭐⭐⭐⭐⭐</span>
                    <span style="color: #888;">(4.8)</span>
                </div>
                <p class="cake-description"><?= $description ?></p>
                <div class="price">₹<?= number_format($cake['price'], 2) ?></div>
                <a href="payment.php?id=<?= $cake['id'] ?>&name=<?= urlencode($cake['name']) ?>&price=<?= $cake['price'] ?>" class="btn">
                    🛍️ Buy Now
                </a>
            </div>
        </div>
        <?php
    }
}

// Initialize Objects
$cakeModel = new Cake();
$userModel = new User();
$cakeView = new CakeView();

// Get all cakes
$cakes = $cakeModel->getAllCakes();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sweet Delights Cake Shop</title>
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
        }

        /* Header Styles */
        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px 0;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-container {
            max-width: 1300px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 30px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            font-size: 28px;
            font-weight: bold;
            text-decoration: none;
            transition: transform 0.3s;
        }

        .logo:hover {
            transform: scale(1.05);
        }

        .logo span {
            font-size: 36px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        .user-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(245, 87, 108, 0.4);
        }

        .user-icon:hover {
            transform: scale(1.15) rotate(10deg);
            box-shadow: 0 6px 20px rgba(245, 87, 108, 0.6);
        }

        .user-name {
            color: white;
            font-weight: 600;
            font-size: 16px;
            background: rgba(255,255,255,0.2);
            padding: 8px 16px;
            border-radius: 20px;
        }

        .auth-btn {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 12px 28px;
            border-radius: 25px;
            text-decoration: none;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(245, 87, 108, 0.3);
        }

        .auth-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(245, 87, 108, 0.5);
        }

        /* Hero Section */
        .hero-section {
            text-align: center;
            padding: 50px 20px;
            max-width: 900px;
            margin: 0 auto;
        }

        h1 {
            color: #764ba2;
            font-size: 48px;
            margin-bottom: 15px;
            text-shadow: 3px 3px 6px rgba(118, 75, 162, 0.2);
            animation: fadeInDown 0.8s;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .subtitle {
            color: #555;
            font-size: 20px;
            margin-bottom: 30px;
            font-weight: 500;
        }

        .search-bar {
            max-width: 550px;
            margin: 30px auto;
            position: relative;
        }

        .search-bar input {
            width: 100%;
            padding: 16px 25px;
            border: 3px solid #667eea;
            border-radius: 30px;
            font-size: 17px;
            outline: none;
            transition: all 0.3s;
            background: white;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }

        .search-bar input:focus {
            border-color: #f5576c;
            box-shadow: 0 8px 25px rgba(245, 87, 108, 0.3);
            transform: translateY(-2px);
        }

        /* Cake Grid */
        .cake-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 35px;
            max-width: 1300px;
            margin: 50px auto;
            padding: 0 30px;
        }

        .cake-card {
            background: white;
            border-radius: 25px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 3px solid transparent;
        }

        .cake-card:hover {
            transform: translateY(-15px) scale(1.02);
            box-shadow: 0 20px 50px rgba(102, 126, 234, 0.3);
            border-color: #667eea;
        }

        .cake-image-wrapper {
            width: 100%;
            height: 250px;
            overflow: hidden;
            position: relative;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .cake-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s;
        }

        .cake-card:hover img {
            transform: scale(1.15) rotate(2deg);
        }

        .badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 8px 18px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(245, 87, 108, 0.4);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .cake-info {
            padding: 25px;
        }

        .cake-card h3 {
            color: #333;
            font-size: 24px;
            margin-bottom: 12px;
            font-weight: 700;
        }

        .price {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 18px;
            display: inline-block;
        }

        .cake-description {
            color: #666;
            font-size: 15px;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .rating {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 20px;
            font-size: 18px;
        }

        .stars {
            color: #ffd700;
        }

        .btn {
            display: inline-block;
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 15px;
            font-weight: 700;
            text-align: center;
            transition: all 0.3s;
            font-size: 16px;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            transition: left 0.4s;
            z-index: -1;
        }

        .btn:hover::before {
            left: 0;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.6);
        }

        /* Footer */
        footer {
            text-align: center;
            padding: 40px 20px;
            color: #555;
            margin-top: 80px;
            background: rgba(255,255,255,0.5);
            border-top: 3px solid #667eea;
        }

        footer p {
            margin: 8px 0;
            font-size: 15px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            h1 {
                font-size: 36px;
            }
            
            .header-container {
                flex-direction: column;
                gap: 20px;
            }
            
            .cake-grid {
                grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
                gap: 25px;
                padding: 0 20px;
            }

            .user-name {
                display: none;
            }
        }

        /* Loading Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .cake-card {
            animation: fadeIn 0.6s ease-out forwards;
        }

        .cake-card:nth-child(1) { animation-delay: 0.1s; }
        .cake-card:nth-child(2) { animation-delay: 0.2s; }
        .cake-card:nth-child(3) { animation-delay: 0.3s; }
        .cake-card:nth-child(4) { animation-delay: 0.4s; }
    </style>
</head>
<body>
    <?php $cakeView->renderHeader($userModel); ?>

    <div class="hero-section">
        <h1>🎂 Welcome to Sweet Delights!</h1>
        <p class="subtitle">Premium handcrafted cakes made with love & finest ingredients ✨</p>
        
        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="🔍 Search your favorite cake..." onkeyup="filterCakes()">
        </div>
    </div>

    <div class="cake-grid" id="cakeGrid">
        <?php while($cake = $cakes->fetch_assoc()) { 
            $cakeView->renderCakeCard($cake);
        } ?>
    </div>

    <footer>
        <p><strong>🎂 Sweet Delights - Where Every Bite is Pure Joy!</strong></p>
        <p>📧 contact@sweetdelights.com | 📞 +91 98765 43210</p>
        <p>&copy; 2025 Sweet Delights Cake Shop. Baked with ❤️</p>
    </footer>

    <script>
        function filterCakes() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const cards = document.querySelectorAll('.cake-card');
            
            cards.forEach(card => {
                const name = card.getAttribute('data-name');
                if (name.includes(filter)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>