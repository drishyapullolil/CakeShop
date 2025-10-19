<?php
// admin.php - Enhanced Admin Dashboard
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

// Cake Management Class
class CakeManager {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }
    
    public function getAllCakes($limit = null) {
        $query = "SELECT * FROM cakes ORDER BY id DESC";
        if ($limit) {
            $query .= " LIMIT " . intval($limit);
        }
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
    
    public function addCake($name, $description, $price, $category, $weight, $image, $availability) {
        $stmt = $this->conn->prepare("INSERT INTO cakes (name, description, price, category, weight, image, availability, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssdssss", $name, $description, $price, $category, $weight, $image, $availability);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    public function updateCake($id, $name, $description, $price, $category, $weight, $image, $availability) {
        $stmt = $this->conn->prepare("UPDATE cakes SET name = ?, description = ?, price = ?, category = ?, weight = ?, image = ?, availability = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("ssdssssi", $name, $description, $price, $category, $weight, $image, $availability, $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    public function deleteCake($id) {
        $stmt = $this->conn->prepare("DELETE FROM cakes WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    public function getTotalCakes() {
        $result = $this->conn->query("SELECT COUNT(*) as total FROM cakes");
        $row = $result->fetch_assoc();
        return $row['total'];
    }
    
    public function getTotalOrders() {
        $result = $this->conn->query("SELECT COUNT(*) as total FROM orders");
        $row = $result->fetch_assoc();
        return $row['total'];
    }
    
    public function getTotalRevenue() {
        $result = $this->conn->query("SELECT SUM(total_amount) as revenue FROM orders WHERE status = 'completed'");
        $row = $result->fetch_assoc();
        return $row['revenue'] ? $row['revenue'] : 0;
    }
    
    public function getTodayOrders() {
        $result = $this->conn->query("SELECT COUNT(*) as today FROM orders WHERE DATE(order_date) = CURDATE()");
        $row = $result->fetch_assoc();
        return $row['today'];
    }
    
    public function getRecentOrders($limit = 5) {
        $stmt = $this->conn->prepare("SELECT * FROM orders ORDER BY order_date DESC LIMIT ?");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    }
    
    public function __destruct() {
        $this->db->closeConnection();
    }
}

// Initialize Classes
$cakeManager = new CakeManager();

// Handle CRUD Operations
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_cake'])) {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $category = trim($_POST['category']);
        $weight = trim($_POST['weight']);
        $image = trim($_POST['image']);
        $availability = $_POST['availability'];
        
        if ($cakeManager->addCake($name, $description, $price, $category, $weight, $image, $availability)) {
            $message = "Cake added successfully!";
            $messageType = "success";
        } else {
            $message = "Failed to add cake!";
            $messageType = "error";
        }
    } elseif (isset($_POST['update_cake'])) {
        $id = intval($_POST['cake_id']);
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $category = trim($_POST['category']);
        $weight = trim($_POST['weight']);
        $image = trim($_POST['image']);
        $availability = $_POST['availability'];
        
        if ($cakeManager->updateCake($id, $name, $description, $price, $category, $weight, $image, $availability)) {
            $message = "Cake updated successfully!";
            $messageType = "success";
        } else {
            $message = "Failed to update cake!";
            $messageType = "error";
        }
    }
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($cakeManager->deleteCake($id)) {
        $message = "Cake deleted successfully!";
        $messageType = "success";
        header("Location: admin.php");
        exit();
    } else {
        $message = "Failed to delete cake!";
        $messageType = "error";
    }
}

// Get statistics
$totalCakes = $cakeManager->getTotalCakes();
$totalOrders = $cakeManager->getTotalOrders();
$totalRevenue = $cakeManager->getTotalRevenue();
$todayOrders = $cakeManager->getTodayOrders();

// Get edit cake data
$editCake = null;
if (isset($_GET['edit'])) {
    $editCake = $cakeManager->getCakeById($_GET['edit']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Sweet Delights</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        }

        .admin-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            color: #333;
            padding: 20px 30px;
            box-shadow: 0 5px 30px rgba(0,0,0,0.2);
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .admin-header h1 {
            font-size: 28px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .admin-nav {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .admin-nav a {
            color: #667eea;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 25px;
            transition: all 0.3s;
            font-weight: 600;
            background: rgba(102, 126, 234, 0.1);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .admin-nav a:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateY(-2px);
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 40px 30px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            padding: 35px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            transition: all 0.4s;
        }

        .stat-card:hover {
            transform: translateY(-10px);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            margin-bottom: 20px;
        }

        .stat-value {
            font-size: 42px;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #666;
            font-size: 16px;
            font-weight: 600;
        }

        .section {
            background: white;
            padding: 40px;
            border-radius: 25px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin-bottom: 40px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #f0f0f0;
        }

        .section-header h2 {
            color: #333;
            font-size: 26px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn {
            padding: 14px 30px;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .btn:hover {
            transform: translateY(-3px);
            opacity: 0.9;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 15px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
            font-family: inherit;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 18px;
            text-align: left;
            font-weight: 600;
        }

        td {
            padding: 18px;
            border-bottom: 1px solid #f0f0f0;
            color: #666;
        }

        tr:hover {
            background: #f8f9ff;
        }

        .cake-image-small {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 12px;
        }

        .status-badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            display: inline-block;
        }

        .status-available {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }

        .status-out {
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
            color: white;
        }

        .alert {
            padding: 20px 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            font-weight: 600;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .action-buttons .btn {
            padding: 10px 18px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="logo-section">
            <div class="logo-icon">🎂</div>
            <h1>Admin Dashboard</h1>
        </div>
        <nav class="admin-nav">
            <a href="index.php"><i class="fas fa-store"></i> View Store</a>
        </nav>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-birthday-cake"></i></div>
                <div class="stat-value"><?php echo $totalCakes; ?></div>
                <div class="stat-label">Total Cakes</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                <div class="stat-value"><?php echo $totalOrders; ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-rupee-sign"></i></div>
                <div class="stat-value">₹<?php echo number_format($totalRevenue, 0); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
                <div class="stat-value"><?php echo $todayOrders; ?></div>
                <div class="stat-label">Today Orders</div>
            </div>
        </div>

        <div class="section">
            <div class="section-header">
                <h2><?php echo $editCake ? 'Edit Cake' : 'Add New Cake'; ?></h2>
                <?php if ($editCake): ?>
                    <a href="admin.php" class="btn btn-warning">Cancel</a>
                <?php endif; ?>
            </div>

            <form method="POST" action="">
                <?php if ($editCake): ?>
                    <input type="hidden" name="cake_id" value="<?php echo $editCake['id']; ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Cake Name</label>
                        <input type="text" name="name" value="<?php echo $editCake ? htmlspecialchars($editCake['name']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Price (₹)</label>
                        <input type="number" name="price" step="0.01" value="<?php echo $editCake ? $editCake['price'] : ''; ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description"><?php echo $editCake ? htmlspecialchars($editCake['description']) : ''; ?></textarea>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Category</label>
                        <input type="text" name="category" value="<?php echo $editCake ? htmlspecialchars($editCake['category']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Weight</label>
                        <input type="text" name="weight" value="<?php echo $editCake ? htmlspecialchars($editCake['weight']) : ''; ?>">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Image URL</label>
                        <input type="url" name="image" value="<?php echo $editCake ? htmlspecialchars($editCake['image']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Availability</label>
                        <select name="availability" required>
                            <option value="available" <?php echo ($editCake && $editCake['availability'] == 'available') ? 'selected' : ''; ?>>Available</option>
                            <option value="out_of_stock" <?php echo ($editCake && $editCake['availability'] == 'out_of_stock') ? 'selected' : ''; ?>>Out of Stock</option>
                        </select>
                    </div>
                </div>

                <button type="submit" name="<?php echo $editCake ? 'update_cake' : 'add_cake'; ?>" class="btn btn-<?php echo $editCake ? 'success' : 'primary'; ?>">
                    <?php echo $editCake ? 'Update Cake' : 'Add Cake'; ?>
                </button>
            </form>
        </div>

        <div class="section">
            <div class="section-header">
                <h2>All Cakes</h2>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Weight</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $cakes = $cakeManager->getAllCakes();
                    if ($cakes && $cakes->num_rows > 0):
                        while ($cake = $cakes->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?php echo $cake['id']; ?></td>
                            <td>
                                <?php if ($cake['image']): ?>
                                    <img src="<?php echo htmlspecialchars($cake['image']); ?>" alt="<?php echo htmlspecialchars($cake['name']); ?>" class="cake-image-small">
                                <?php else: ?>
                                    <div style="width:70px;height:70px;background:#f0f0f0;border-radius:12px;"></div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($cake['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($cake['category']); ?></td>
                            <td><?php echo htmlspecialchars($cake['weight']); ?></td>
                            <td><strong>₹<?php echo number_format($cake['price'], 2); ?></strong></td>
                            <td>
                                <span class="status-badge status-<?php echo $cake['availability'] == 'available' ? 'available' : 'out'; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $cake['availability'])); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="admin.php?edit=<?php echo $cake['id']; ?>" class="btn btn-warning">Edit</a>
                                    <a href="admin.php?delete=<?php echo $cake['id']; ?>" class="btn btn-danger" onclick="return confirm('Delete this cake?')">Delete</a>
                                </div>
                            </td>
                        </tr>
                    <?php
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="8" style="text-align:center;padding:40px;">No cakes added yet</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>