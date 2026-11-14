<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

//requireAuth();
//requireManagerOrAdmin();

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ensure uploads folder exists
    $uploadDir = "../uploads/services/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Handle image upload function
    function handleImageUpload($file) {
        global $uploadDir;
        if (isset($file['name']) && $file['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid("service_") . "." . strtolower($ext);
            $destination = $uploadDir . $filename;
            move_uploaded_file($file['tmp_name'], $destination);
            return "uploads/services/" . $filename;
        }
        return null;
    }

    if (isset($_POST['add_service'])) {
        // Add new service
        $category_id = $_POST['category_id'];
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = (float)$_POST['price'];
        $imagePath = handleImageUpload($_FILES['image']);

        if (!empty($name) && $price > 0) {
            createService($pdo, $category_id, $name, $description, $price, $imagePath);
            $message = 'Service added successfully!';
        } else {
            $error = 'Please fill all required fields correctly.';
        }
    } elseif (isset($_POST['update_service'])) {
        // Update existing service
        $service_id = $_POST['service_id'];
        $category_id = $_POST['category_id'];
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = (float)$_POST['price'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        $imagePath = handleImageUpload($_FILES['image']);
        if (!$imagePath) {
            // If no new image uploaded, keep old image
            $stmt = $pdo->prepare("SELECT image FROM services WHERE id = ?");
            $stmt->execute([$service_id]);
            $row = $stmt->fetch();
            $imagePath = $row['image'];
        }

        if (!empty($name) && $price > 0) {
            updateService($pdo, $service_id, $category_id, $name, $description, $price, $is_active, $imagePath);
            $message = 'Service updated successfully!';
        } else {
            $error = 'Please fill all required fields correctly.';
        }
    } elseif (isset($_POST['delete_service'])) {
        // Delete service
        $service_id = $_POST['service_id'];
        deleteService($pdo, $service_id);
        $message = 'Service deleted successfully!';
    }
}

// Get all services and categories
$services = getAllServices($pdo);
$categories = getAllCategories($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Services - Hotel QR System</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'admin_header.php'; ?>
    
    <div class="container">
        <h1>Manage Services</h1>
        
        <?php if ($message): ?>
            <div class="alert success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="tabs">
            <button class="tab-btn active" onclick="openTab('existing-services')">Existing Services</button>
            <button class="tab-btn" onclick="openTab('add-service')">Add New Service</button>
        </div>
        
        <div id="existing-services" class="tab-content" style="display: block;">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($services as $service): ?>
                        <tr>
                            <td><?= htmlspecialchars($service['name']) ?></td>
                            <td><?= htmlspecialchars($service['category_name']) ?></td>
                            <td><?= htmlspecialchars($service['description']) ?></td>
                            <td>₦<?= number_format($service['price'], 2) ?></td>
                            <td><?= $service['is_active'] ? 'Active' : 'Inactive' ?></td>
                            <td>
                                <button class="btn small" onclick="editService(
                                    <?= $service['id'] ?>,
                                    '<?= htmlspecialchars($service['name'], ENT_QUOTES) ?>',
                                    <?= $service['category_id'] ?>,
                                    '<?= htmlspecialchars($service['description'], ENT_QUOTES) ?>',
                                    <?= $service['price'] ?>,
                                    <?= $service['is_active'] ?>
                                )">Edit</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div id="add-service" class="tab-content">
            <form method="POST" action="services.php" enctype="multipart/form-data">
                <input type="hidden" name="add_service" value="1">
                
                <div class="form-group">
                    <label for="category_id">Category:</label>
                    <select id="category_id" name="category_id" required>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="name">Service Name:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="price">Price:</label>
                    <input type="number" id="price" name="price" min="0" step="0.01" required>
                </div>

                <div class="form-group">
                    <label for="image">Service Image:</label>
                    <input type="file" id="image" name="image" accept="image/*" onchange="previewImage(event, 'add-preview')">
                    <img id="add-preview" src="" alt="Preview" style="display:none; max-width:150px; margin-top:10px;">
                </div>
                
                <button type="submit" class="btn">Add Service</button>
            </form>
        </div>
        
        <div id="edit-service" class="tab-content">
            <form method="POST" action="services.php" enctype="multipart/form-data">
                <input type="hidden" id="edit_service_id" name="service_id">
                
                <div class="form-group">
                    <label for="edit_category_id">Category:</label>
                    <select id="edit_category_id" name="category_id" required>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_name">Service Name:</label>
                    <input type="text" id="edit_name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_description">Description:</label>
                    <textarea id="edit_description" name="description"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="edit_price">Price:</label>
                    <input type="number" id="edit_price" name="price" min="0" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="edit_is_active" name="is_active" value="1" checked>
                        Active
                    </label>
                </div>

                <div class="form-group">
                    <label for="edit_image">Service Image:</label>
                    <input type="file" id="edit_image" name="image" accept="image/*" onchange="previewImage(event, 'edit-preview')">
                    <img id="edit-preview" src="" alt="Preview" style="display:none; max-width:150px; margin-top:10px;">
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="update_service" class="btn">Update Service</button>
                    <button type="button" class="btn secondary" onclick="cancelEdit()">Cancel</button>
                    <button type="submit" name="delete_service" value="1" class="btn danger" onclick="return confirm('Are you sure you want to delete this service?')">Delete Service</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="../assets/js/admin.js"></script>
    <script>
        function previewImage(event, previewId) {
            const reader = new FileReader();
            reader.onload = function(){
                const output = document.getElementById(previewId);
                output.src = reader.result;
                output.style.display = "block";
            };
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>

</body>
</html>