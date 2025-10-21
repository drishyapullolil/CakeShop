<?php
// admin_add_product.php - Simple admin page to add products (cakes)
session_start();

// Require admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.php');
    exit();
}

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

class Product {
    private $conn;
    public function __construct($conn) { $this->conn = $conn; }
    public function validate($name, $price) {
        $errors = [];
        if ($name === '' || strlen($name) < 2) { $errors[] = 'Name is required (min 2 characters).'; }
        if ($price === '' || !is_numeric($price) || (float)$price < 0) { $errors[] = 'Price must be a non-negative number.'; }
        return $errors;
    }
    public function create($name, $price) {
        $stmt = $this->conn->prepare("INSERT INTO cakes (name, price) VALUES (?, ?)");
        if (!$stmt) { return [false, 'Failed to prepare statement: ' . $this->conn->error]; }
        $p = (float)$price;
        $stmt->bind_param('sd', $name, $p);
        if ($stmt->execute()) {
            $msg = 'Product added successfully (ID: ' . $stmt->insert_id . ').';
            $stmt->close();
            return [true, $msg];
        }
        $err = 'Insert failed: ' . $stmt->error;
        $stmt->close();
        return [false, $err];
    }
    public function listRecent($limit = 50) {
        $limit = (int)$limit;
        $rows = [];
        $result = $this->conn->query("SELECT id, name, price FROM cakes ORDER BY id DESC LIMIT $limit");
        if ($result) {
            while ($row = $result->fetch_assoc()) { $rows[] = $row; }
        }
        return $rows;
    }
}

class AdminPageController {
    private $db;
    private $conn;
    private $product;
    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
        $this->product = new Product($this->conn);
    }
    public function handle() {
        $errors = [];
        $success = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $price = trim($_POST['price'] ?? '');
            $errors = $this->product->validate($name, $price);
            if (empty($errors)) {
                list($ok, $msg) = $this->product->create($name, $price);
                if ($ok) { $success = $msg; } else { $errors[] = $msg; }
            }
        }
        $cakes = $this->product->listRecent(50);
        return [$errors, $success, $cakes];
    }
}

$controller = new AdminPageController();
list($errors, $success, $cakes) = $controller->handle();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Add Product</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f7f7fb; margin: 0; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; }
        .card { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 10px 20px rgba(0,0,0,0.06); margin-bottom: 20px; }
        h1 { margin: 0 0 16px; color: #333; }
        label { display: block; margin: 12px 0 6px; font-weight: 600; color: #444; }
        input[type=text], input[type=number] { width: 100%; padding: 12px 14px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 14px; }
        input[type=text]:focus, input[type=number]:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.12); }
        .row { display: grid; grid-template-columns: 1fr 200px; gap: 12px; }
        .actions { display: flex; gap: 10px; align-items: center; margin-top: 16px; }
        .btn { padding: 12px 18px; border: none; border-radius: 10px; color: white; font-weight: 700; cursor: pointer; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); box-shadow: 0 6px 16px rgba(102, 126, 234, 0.35); }
        .btn-secondary { background: #6b7280; }
        .alert { padding: 12px 14px; border-radius: 10px; margin-bottom: 14px; font-size: 14px; }
        .alert-danger { background: #fde2e2; color: #7f1d1d; border: 1px solid #fecaca; }
        .alert-success { background: #dcfce7; color: #14532d; border: 1px solid #bbf7d0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #f3f4f6; color: #374151; }
        .topbar { display:flex; justify-content: space-between; align-items:center; margin-bottom: 16px; }
        a.link { color:#667eea; text-decoration:none; font-weight:600; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="topbar">
                <h1>🛠️ Admin: Add Product</h1>
                <div>
                    <a class="link" href="index.php">← Back to Shop</a>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $e): ?>
                        <div>• <?= htmlspecialchars($e) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <label for="name">Product Name</label>
                <input type="text" id="name" name="name" placeholder="e.g., Chocolate Truffle" required>

                <div class="row">
                    <div>
                        <label for="price">Price (₹)</label>
                        <input type="number" step="0.01" min="0" id="price" name="price" placeholder="e.g., 499.00" required>
                    </div>
                    <div class="actions">
                        <button type="submit" class="btn btn-primary">Add Product</button>
                        <button type="reset" class="btn btn-secondary">Clear</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="card">
            <h2 style="margin-top:0;">📋 Recent Products</h2>
            <?php if (empty($cakes)): ?>
                <p>No products found.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Price (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cakes as $c): ?>
                            <tr>
                                <td>#<?= (int)$c['id'] ?></td>
                                <td><?= htmlspecialchars($c['name']) ?></td>
                                <td>₹<?= number_format((float)$c['price'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
