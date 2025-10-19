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
        $stmt = $this->conn->prepare("SELECT * FROM cakes WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $cake = $result->fetch_assoc();
        $stmt->close();
        return $cake;
    }
    
    public function searchCakes($searchTerm) {
        $stmt = $this->conn->prepare("SELECT * FROM cakes WHERE name LIKE ? ORDER BY id ASC");
        $search = "%{$searchTerm}%";
        $stmt->bind_param("s", $search);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
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
        return isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    public function getUserName() {
        return isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
    }
    
    public function getUserId() {
        return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
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
        // Using reliable image sources with cake-themed colors
        $this->cakeImages = [
            1 => "https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=400&h=300&fit=crop",
            2 => "https://thenovicechefblog.com/wp-content/uploads/2013/08/Vanilla-Cake-Image-560x560.jpg",
            3 => "https://images.unsplash.com/photo-1565958011703-44f9829ba187?w=400&h=300&fit=crop",
            4 => "https://images.unsplash.com/photo-1606890737304-57a1ca8a5b62?w=400&h=300&fit=crop",
            5 => "https://images.unsplash.com/photo-1464349095431-e9a21285b5f3?w=400&h=300&fit=crop",
            6 => "https://images.unsplash.com/photo-1563729784474-d77dbb933a9e?w=400&h=300&fit=crop",
            7 => "https://images.unsplash.com/photo-1576618148400-f54bed99fcfd?w=400&h=300&fit=crop",
            8 => "https://images.unsplash.com/photo-1557925923-cd4648e211a0?w=400&h=300&fit=crop",
            9 => "https://images.unsplash.com/photo-1586985289688-ca3cf47d3e6e?w=400&h=300&fit=crop",
            10 => "https://images.unsplash.com/photo-1571115177098-24ec42ed204d?w=400&h=300&fit=crop",
            11 => "https://www.tamingtwins.com/wp-content/uploads/2018/06/oreo-cheesecake-pin-2-680x1020.jpg",
            12 => "https://images.unsplash.com/photo-1621303837174-89787a7d4729?w=400&h=300&fit=crop"
        ];
        
        $this->cakeDescriptions = [
            1 => "Rich, moist chocolate cake with velvety ganache frosting and premium cocoa",
            2 => "Classic vanilla sponge with buttercream - perfect for any celebration",
            3 => "Luxurious red velvet with cream cheese frosting, smooth and decadent",
            4 => "Traditional Black Forest with cherries, chocolate shavings and whipped cream",
            5 => "Fresh strawberry cream cake with real fruit pieces and light whipped topping",
            6 => "Sweet butterscotch cake with caramel sauce and crunchy toffee bits",
            7 => "Tropical pineapple cake with cream cheese frosting and fruit chunks",
            8 => "Rich coffee walnut cake with espresso buttercream and crunchy walnuts",
            9 => "Seasonal mango cake made with fresh mango pulp and cream",
            10 => "Premium chocolate truffle with dark chocolate ganache layers",
            11 => "Creamy Oreo cheesecake with cookie crumbs and smooth filling",
            12 => "Fresh blueberry cake with cream cheese frosting and real berries"
        ];
    }
    
    public function getCakeImage($cakeId) {
        if (isset($this->cakeImages[$cakeId])) {
            return $this->cakeImages[$cakeId];
        }
        // Fallback: Generate a colorful placeholder with cake emoji
        $colors = ['667eea', 'f093fb', 'f5576c', '38ef7d', 'ffd700', 'ff6b6b'];
        $randomColor = $colors[($cakeId - 1) % count($colors)];
        return "https://via.placeholder.com/400x300/{$randomColor}/ffffff?text=🎂+Cake+#{$cakeId}";
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
                
                <nav class="nav-links">
                    <a href="index.php">🏠 Home</a>
                   
                    <a href="Add prodect.php">🎂Add Products</a>
                    <?php if ($isLoggedIn): ?>
                        <a href="my_orders.php">📦 My Orders</a>
                    <?php endif; ?>
                    <a href="#contact">📞 Contact</a>
                </nav>
                
                <div class="user-section">
                    <?php if ($isLoggedIn): ?>
                        <span class="user-name">👋 <?= htmlspecialchars($userName) ?></span>
                        <a href="logout.php" class="auth-btn">Logout</a>
                    <?php else: ?>
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
                <img src="<?= $imgSrc ?>" 
                     alt="<?= htmlspecialchars($cake['name']) ?>"
                     loading="lazy"
                     onerror="this.src='https://via.placeholder.com/400x300/667eea/ffffff?text=🎂+<?= urlencode($cake['name']) ?>'">
                <div class="badge">⭐ Bestseller</div>
            </div>
            <div class="cake-info">
                <h3><?= htmlspecialchars($cake['name']) ?></h3>
                <div class="rating">
                    <span class="stars">⭐⭐⭐⭐⭐</span>
                    <span class="rating-text">(4.8)</span>
                </div>
                <p class="cake-description"><?= $description ?></p>
                <div class="price-tag">
                    <span class="price">₹<?= number_format($cake['price'], 2) ?></span>
                    <span class="price-label">per kg</span>
                </div>
                <a href="payment.php?id=<?= $cake['id'] ?>&name=<?= urlencode($cake['name']) ?>&price=<?= $cake['price'] ?>" class="btn">
                    🛍️ Order Now
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
    <title>Sweet Delights - Premium Cake Shop</title>
    <meta name="description" content="Order delicious handcrafted cakes online. Premium quality, fresh ingredients, delivered to your doorstep.">
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
            gap: 30px;
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

        .nav-links {
            display: flex;
            gap: 25px;
            align-items: center;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-size: 15px;
            font-weight: 600;
            padding: 8px 15px;
            border-radius: 20px;
            transition: all 0.3s;
        }

        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }

        .user-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-name {
            color: white;
            font-weight: 600;
            font-size: 15px;
            background: rgba(255,255,255,0.2);
            padding: 8px 16px;
            border-radius: 20px;
        }

        .auth-btn {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 10px 25px;
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
            top: 15px;
            right: 15px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(245, 87, 108, 0.4);
        }

        .cake-info {
            padding: 25px;
        }

        .cake-card h3 {
            color: #333;
            font-size: 22px;
            margin-bottom: 12px;
            font-weight: 700;
        }

        .rating {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .stars {
            color: #ffd700;
        }

        .rating-text {
            color: #888;
            font-size: 14px;
        }

        .cake-description {
            color: #666;
            font-size: 14px;
            margin-bottom: 20px;
            line-height: 1.6;
            height: 65px;
            overflow: hidden;
        }

        .price-tag {
            display: flex;
            align-items: baseline;
            gap: 8px;
            margin-bottom: 20px;
        }

        .price {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 32px;
            font-weight: bold;
        }

        .price-label {
            color: #999;
            font-size: 14px;
        }

        .btn {
            display: inline-block;
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 15px;
            font-weight: 700;
            text-align: center;
            transition: all 0.3s;
            font-size: 16px;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.6);
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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

        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #999;
            font-size: 18px;
            grid-column: 1 / -1;
        }

        /* Responsive */
        @media (max-width: 768px) {
            h1 {
                font-size: 32px;
            }
            
            .header-container {
                flex-wrap: wrap;
            }

            .nav-links {
                order: 3;
                width: 100%;
                justify-content: center;
                flex-wrap: wrap;
                gap: 10px;
            }

            .nav-links a {
                font-size: 13px;
                padding: 6px 12px;
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
            opacity: 0;
        }

        .cake-card:nth-child(1) { animation-delay: 0.1s; }
        .cake-card:nth-child(2) { animation-delay: 0.2s; }
        .cake-card:nth-child(3) { animation-delay: 0.3s; }
        .cake-card:nth-child(4) { animation-delay: 0.4s; }
        .cake-card:nth-child(5) { animation-delay: 0.5s; }
        .cake-card:nth-child(6) { animation-delay: 0.6s; }
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
        <?php 
        $hasResults = false;
        while($cake = $cakes->fetch_assoc()) { 
            $hasResults = true;
            $cakeView->renderCakeCard($cake);
        } 
        
        if (!$hasResults) {
            echo '<div class="no-results">No cakes available at the moment. Please check back later!</div>';
        }
        ?>
    </div>

    <footer id="contact">
        <p><strong>🎂 Sweet Delights - Where Every Bite is Pure Joy!</strong></p>
        <p>📧 contact@sweetdelights.com | 📞 +91 98765 43210</p>
        <p>📍 123 Baker Street, Erāttupetta, Kerala, India</p>
        <p>&copy; 2025 Sweet Delights Cake Shop. Baked with ❤️</p>
    </footer>

    <script>
        function filterCakes() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const cards = document.querySelectorAll('.cake-card');
            let visibleCount = 0;
            
            cards.forEach(card => {
                const name = card.getAttribute('data-name');
                if (name.includes(filter)) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Show "no results" message if needed
            const grid = document.getElementById('cakeGrid');
            let noResultsMsg = grid.querySelector('.no-results');
            
            if (visibleCount === 0 && filter !== '') {
                if (!noResultsMsg) {
                    noResultsMsg = document.createElement('div');
                    noResultsMsg.className = 'no-results';
                    noResultsMsg.textContent = '😕 No cakes found matching "' + filter + '"';
                    grid.appendChild(noResultsMsg);
                }
            } else if (noResultsMsg) {
                noResultsMsg.remove();
            }
        }

        // Scroll to top button functionality
        window.addEventListener('scroll', function() {
            // Add any scroll-based animations here if needed
        });
    </script>
</body>
</html>