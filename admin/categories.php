<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAuth();
requireManagerOrAdmin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        
        if (!empty($name)) {
            $sql = "INSERT INTO categories (name, description) VALUES (?, ?)";
            executeQuery($pdo, $sql, [$name, $description]);
            $message = 'Category added successfully!';
        } else {
            $error = 'Category name is required';
        }
    } elseif (isset($_POST['update_category'])) {
        $id = $_POST['id'];
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        
        if (!empty($name)) {
            $sql = "UPDATE categories SET name = ?, description = ? WHERE id = ?";
            executeQuery($pdo, $sql, [$name, $description, $id]);
            $message = 'Category updated successfully!';
        } else {
            $error = 'Category name is required';
        }
    } elseif (isset($_POST['delete_category'])) {
        $id = $_POST['id'];
        $sql = "DELETE FROM categories WHERE id = ?";
        executeQuery($pdo, $sql, [$id]);
        $message = 'Category deleted successfully!';
    }
}

// Get all categories
$categories = getAllCategories($pdo);
?>

<?php include 'admin_header.php'; ?>

<div class="content-header">
    <h1><i class="fas fa-list"></i> Manage Categories</h1>
    <button class="btn btn-primary" onclick="openModal('add-category-modal')">
        <i class="fas fa-plus"></i> Add New Category
    </button>
</div>

<?php if (isset($message)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?= $category['id'] ?></td>
                        <td><?= htmlspecialchars($category['name']) ?></td>
                        <td><?= htmlspecialchars($category['description']) ?></td>
                        <td class="actions">
                            <button class="btn btn-sm btn-edit" 
                                    onclick="editCategory(<?= $category['id'] ?>, '<?= htmlspecialchars($category['name']) ?>', '<?= htmlspecialchars($category['description']) ?>')">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="id" value="<?= $category['id'] ?>">
                                <button type="submit" name="delete_category" class="btn btn-sm btn-danger" 
                                        onclick="return confirm('Are you sure you want to delete this category?')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Category Modal -->
<div id="add-category-modal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('add-category-modal')">&times;</span>
        <h2>Add New Category</h2>
        <form method="POST">
            <div class="form-group">
                <label for="name">Category Name:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description"></textarea>
            </div>
            <button type="submit" name="add_category" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Category
            </button>
        </form>
    </div>
</div>

<!-- Edit Category Modal -->
<div id="edit-category-modal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('edit-category-modal')">&times;</span>
        <h2>Edit Category</h2>
        <form method="POST">
            <input type="hidden" name="id" id="edit_category_id">
            <div class="form-group">
                <label for="edit_name">Category Name:</label>
                <input type="text" id="edit_name" name="name" required>
            </div>
            <div class="form-group">
                <label for="edit_description">Description:</label>
                <textarea id="edit_description" name="description"></textarea>
            </div>
            <button type="submit" name="update_category" class="btn btn-primary">
                <i class="fas fa-save"></i> Update Category
            </button>
        </form>
    </div>
</div>

<script src="../assets/js/admin.js"></script>

<?php include 'admin_footer.php'; ?>