<!-- FILE: admin.php -->
<!-- Save this as admin.php in your Cake folder -->
<!-- This uses your EXISTING Db.php file with Database and CakeModel classes -->

<?php
session_start();

// Include your existing Database and CakeModel classes
require_once 'Db.php';

// Initialize database and cake model using YOUR existing classes
$cake = new CakeModel();

// Handle form submissions
$message = '';
$messageType = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['action'])) {
        switch($_POST['action']) {
            case 'create':
                $data = [
                    'name' => $_POST['name'],
                    'description' => $_POST['description'],
                    'price' => $_POST['price'],
                    'image' => $_POST['image'],
                    'category' => $_POST['category'],
                    'weight' => $_POST['weight'],
                    'availability' => $_POST['availability']
                ];
                
                if($cake->create($data)) {
                    $message = 'Cake added successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to add cake.';
                    $messageType = 'error';
                }
                break;

            case 'update':
                $data = [
                    'id' => $_POST['id'],
                    'name' => $_POST['name'],
                    'description' => $_POST['description'],
                    'price' => $_POST['price'],
                    'image' => $_POST['image'],
                    'category' => $_POST['category'],
                    'weight' => $_POST['weight'],
                    'availability' => $_POST['availability']
                ];
                
                if($cake->update($data)) {
                    $message = 'Cake updated successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to update cake.';
                    $messageType = 'error';
                }
                break;

            case 'delete':
                if($cake->delete($_POST['id'])) {
                    $message = 'Cake deleted successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to delete cake.';
                    $messageType = 'error';
                }
                break;
        }
    }
}

// Get all cakes or search results
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
if($searchTerm) {
    $cakes = $cake->search($searchTerm);
} else {
    $cakes = $cake->getAllCakes();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Sweet Delights</title>
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
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header h1 {
            color: #667eea;
            font-size: 32px;
        }

        .header p {
            color: #666;
            margin-top: 5px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-warning {
            background: #f59e0b;
            color: white;
        }

        .search-box {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .search-box input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
        }

        .search-box input:focus {
            outline: none;
            border-color: #667eea;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }

        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        th, td {
            padding: 15px;
            text-align: left;
        }

        th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 0.5px;
        }

        tbody tr {
            border-bottom: 1px solid #e5e7eb;
            transition: background 0.3s;
        }

        tbody tr:hover {
            background: #f9fafb;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            overflow-y: auto;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-content {
            background: white;
            border-radius: 15px;
            padding: 30px;
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
        }

        .modal-header h2 {
            color: #667eea;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #6b7280;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 15px;
            font-family: inherit;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .action-buttons button {
            padding: 8px 16px;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .table-container {
                overflow-x: auto;
            }

            table {
                min-width: 800px;
            }

            .header {
                text-align: center;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div>
                <h1>🎂 Sweet Delights Admin Panel</h1>
                <p>Manage your cake inventory</p>
            </div>
            <button class="btn btn-primary" onclick="openModal('add')">
                ➕ Add New Cake
            </button>
        </div>

        <!-- Alert Messages -->
        <?php if($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- Search Box -->
        <div class="search-box">
            <form method="GET" action="">
                <input type="text" name="search" placeholder="🔍 Search cakes by name or category..." 
                       value="<?php echo htmlspecialchars($searchTerm); ?>">
            </form>
        </div>

        <!-- Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price (₹)</th>
                        <th>Weight</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($cakes && count($cakes) > 0): ?>
                        <?php foreach($cakes as $row): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['category']); ?></td>
                            <td>₹<?php echo number_format($row['price'], 2); ?></td>
                            <td><?php echo htmlspecialchars($row['weight']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $row['availability'] == 'available' ? 'success' : 'danger'; ?>">
                                    <?php echo $row['availability']; ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-warning" onclick='editCake(<?php echo json_encode($row); ?>)'>
                                        ✏️ Edit
                                    </button>
                                    <form method="POST" style="display:inline;" 
                                          onsubmit="return confirm('Are you sure you want to delete this cake?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="btn btn-danger">🗑️ Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px;">No cakes found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    <div id="cakeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add New Cake</h2>
                <button class="close-modal" onclick="closeModal()">×</button>
            </div>
            <form method="POST" id="cakeForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="cakeId">

                <div class="form-group">
                    <label for="name">Cake Name *</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" required></textarea>
                </div>

                <div class="form-group">
                    <label for="price">Price (₹) *</label>
                    <input type="number" id="price" name="price" step="0.01" required>
                </div>

                <div class="form-group">
                    <label for="category">Category *</label>
                    <select id="category" name="category" required>
                        <option value="Chocolate">Chocolate</option>
                        <option value="Vanilla">Vanilla</option>
                        <option value="Fruit">Fruit</option>
                        <option value="Special">Special</option>
                        <option value="Caramel">Caramel</option>
                        <option value="Cheesecake">Cheesecake</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="weight">Weight *</label>
                    <select id="weight" name="weight" required>
                        <option value="500g">500g</option>
                        <option value="1 kg">1 kg</option>
                        <option value="1.5 kg">1.5 kg</option>
                        <option value="2 kg">2 kg</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="image">Image Filename *</label>
                    <input type="text" id="image" name="image" placeholder="e.g., chocolate-cake.jpg" required>
                </div>

                <div class="form-group">
                    <label for="availability">Availability *</label>
                    <select id="availability" name="availability" required>
                        <option value="available">Available</option>
                        <option value="out_of_stock">Out of Stock</option>
                    </select>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn" onclick="closeModal()" 
                            style="background: #6b7280; color: white;">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-success" id="submitBtn">
                        Save Cake
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(mode) {
            const modal = document.getElementById('cakeModal');
            const form = document.getElementById('cakeForm');
            
            if(mode === 'add') {
                document.getElementById('modalTitle').textContent = 'Add New Cake';
                document.getElementById('formAction').value = 'create';
                document.getElementById('submitBtn').textContent = 'Add Cake';
                form.reset();
            }
            
            modal.classList.add('active');
        }

        function closeModal() {
            const modal = document.getElementById('cakeModal');
            modal.classList.remove('active');
        }

        function editCake(cake) {
            document.getElementById('modalTitle').textContent = 'Edit Cake';
            document.getElementById('formAction').value = 'update';
            document.getElementById('submitBtn').textContent = 'Update Cake';
            document.getElementById('cakeId').value = cake.id;
            document.getElementById('name').value = cake.name;
            document.getElementById('description').value = cake.description;
            document.getElementById('price').value = cake.price;
            document.getElementById('category').value = cake.category;
            document.getElementById('weight').value = cake.weight;
            document.getElementById('image').value = cake.image;
            document.getElementById('availability').value = cake.availability;
            
            openModal('edit');
        }

        window.onclick = function(event) {
            const modal = document.getElementById('cakeModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html><!-- FILE: admin.php -->
<!-- Save this as admin.php in your Cake folder -->
<!-- This uses your EXISTING Db.php file with Database and CakeModel classes -->

<?php
session_start();

// Include your existing Database and CakeModel classes
require_once 'Db.php';

// Initialize database and cake model using YOUR existing classes
$cake = new CakeModel();

// Handle form submissions
$message = '';
$messageType = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['action'])) {
        switch($_POST['action']) {
            case 'create':
                $data = [
                    'name' => $_POST['name'],
                    'description' => $_POST['description'],
                    'price' => $_POST['price'],
                    'image' => $_POST['image'],
                    'category' => $_POST['category'],
                    'weight' => $_POST['weight'],
                    'availability' => $_POST['availability']
                ];
                
                if($cake->create($data)) {
                    $message = 'Cake added successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to add cake.';
                    $messageType = 'error';
                }
                break;

            case 'update':
                $data = [
                    'id' => $_POST['id'],
                    'name' => $_POST['name'],
                    'description' => $_POST['description'],
                    'price' => $_POST['price'],
                    'image' => $_POST['image'],
                    'category' => $_POST['category'],
                    'weight' => $_POST['weight'],
                    'availability' => $_POST['availability']
                ];
                
                if($cake->update($data)) {
                    $message = 'Cake updated successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to update cake.';
                    $messageType = 'error';
                }
                break;

            case 'delete':
                if($cake->delete($_POST['id'])) {
                    $message = 'Cake deleted successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to delete cake.';
                    $messageType = 'error';
                }
                break;
        }
    }
}

// Get all cakes or search results
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
if($searchTerm) {
    $cakes = $cake->search($searchTerm);
} else {
    $cakes = $cake->getAllCakes();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Sweet Delights</title>
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
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header h1 {
            color: #667eea;
            font-size: 32px;
        }

        .header p {
            color: #666;
            margin-top: 5px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-warning {
            background: #f59e0b;
            color: white;
        }

        .search-box {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .search-box input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
        }

        .search-box input:focus {
            outline: none;
            border-color: #667eea;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }

        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        th, td {
            padding: 15px;
            text-align: left;
        }

        th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 0.5px;
        }

        tbody tr {
            border-bottom: 1px solid #e5e7eb;
            transition: background 0.3s;
        }

        tbody tr:hover {
            background: #f9fafb;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            overflow-y: auto;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-content {
            background: white;
            border-radius: 15px;
            padding: 30px;
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
        }

        .modal-header h2 {
            color: #667eea;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #6b7280;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 15px;
            font-family: inherit;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .action-buttons button {
            padding: 8px 16px;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .table-container {
                overflow-x: auto;
            }

            table {
                min-width: 800px;
            }

            .header {
                text-align: center;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div>
                <h1>🎂 Sweet Delights Admin Panel</h1>
                <p>Manage your cake inventory</p>
            </div>
            <button class="btn btn-primary" onclick="openModal('add')">
                ➕ Add New Cake
            </button>
        </div>

        <!-- Alert Messages -->
        <?php if($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- Search Box -->
        <div class="search-box">
            <form method="GET" action="">
                <input type="text" name="search" placeholder="🔍 Search cakes by name or category..." 
                       value="<?php echo htmlspecialchars($searchTerm); ?>">
            </form>
        </div>

        <!-- Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price (₹)</th>
                        <th>Weight</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($cakes && count($cakes) > 0): ?>
                        <?php foreach($cakes as $row): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['category']); ?></td>
                            <td>₹<?php echo number_format($row['price'], 2); ?></td>
                            <td><?php echo htmlspecialchars($row['weight']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $row['availability'] == 'available' ? 'success' : 'danger'; ?>">
                                    <?php echo $row['availability']; ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-warning" onclick='editCake(<?php echo json_encode($row); ?>)'>
                                        ✏️ Edit
                                    </button>
                                    <form method="POST" style="display:inline;" 
                                          onsubmit="return confirm('Are you sure you want to delete this cake?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="btn btn-danger">🗑️ Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px;">No cakes found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    <div id="cakeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add New Cake</h2>
                <button class="close-modal" onclick="closeModal()">×</button>
            </div>
            <form method="POST" id="cakeForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="cakeId">

                <div class="form-group">
                    <label for="name">Cake Name *</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" required></textarea>
                </div>

                <div class="form-group">
                    <label for="price">Price (₹) *</label>
                    <input type="number" id="price" name="price" step="0.01" required>
                </div>

                <div class="form-group">
                    <label for="category">Category *</label>
                    <select id="category" name="category" required>
                        <option value="Chocolate">Chocolate</option>
                        <option value="Vanilla">Vanilla</option>
                        <option value="Fruit">Fruit</option>
                        <option value="Special">Special</option>
                        <option value="Caramel">Caramel</option>
                        <option value="Cheesecake">Cheesecake</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="weight">Weight *</label>
                    <select id="weight" name="weight" required>
                        <option value="500g">500g</option>
                        <option value="1 kg">1 kg</option>
                        <option value="1.5 kg">1.5 kg</option>
                        <option value="2 kg">2 kg</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="image">Image Filename *</label>
                    <input type="text" id="image" name="image" placeholder="e.g., chocolate-cake.jpg" required>
                </div>

                <div class="form-group">
                    <label for="availability">Availability *</label>
                    <select id="availability" name="availability" required>
                        <option value="available">Available</option>
                        <option value="out_of_stock">Out of Stock</option>
                    </select>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn" onclick="closeModal()" 
                            style="background: #6b7280; color: white;">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-success" id="submitBtn">
                        Save Cake
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(mode) {
            const modal = document.getElementById('cakeModal');
            const form = document.getElementById('cakeForm');
            
            if(mode === 'add') {
                document.getElementById('modalTitle').textContent = 'Add New Cake';
                document.getElementById('formAction').value = 'create';
                document.getElementById('submitBtn').textContent = 'Add Cake';
                form.reset();
            }
            
            modal.classList.add('active');
        }

        function closeModal() {
            const modal = document.getElementById('cakeModal');
            modal.classList.remove('active');
        }

        function editCake(cake) {
            document.getElementById('modalTitle').textContent = 'Edit Cake';
            document.getElementById('formAction').value = 'update';
            document.getElementById('submitBtn').textContent = 'Update Cake';
            document.getElementById('cakeId').value = cake.id;
            document.getElementById('name').value = cake.name;
            document.getElementById('description').value = cake.description;
            document.getElementById('price').value = cake.price;
            document.getElementById('category').value = cake.category;
            document.getElementById('weight').value = cake.weight;
            document.getElementById('image').value = cake.image;
            document.getElementById('availability').value = cake.availability;
            
            openModal('edit');
        }

        window.onclick = function(event) {
            const modal = document.getElementById('cakeModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>