<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAuth();
requireHR();

// Handle document actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_document'])) {
        $docId = (int)$_POST['doc_id'];
        
        // Get file path before deletion
        $stmt = $pdo->prepare("SELECT file_path FROM employee_documents WHERE id = ?");
        $stmt->execute([$docId]);
        $document = $stmt->fetch();
        
        if ($document && file_exists($document['file_path'])) {
            unlink($document['file_path']);
        }
        
        $stmt = $pdo->prepare("DELETE FROM employee_documents WHERE id = ?");
        $stmt->execute([$docId]);
        
        $_SESSION['success'] = "Document deleted successfully!";
        header('Location: documents.php');
        exit();
    }
}

// Get all documents with employee info
$documents = $pdo->query("
    SELECT ed.*, a.first_name, a.last_name, a.employee_id, a.department,
           uploader.first_name as uploader_first, uploader.last_name as uploader_last
    FROM employee_documents ed
    JOIN admin a ON ed.admin_id = a.id
    JOIN admin uploader ON ed.uploaded_by = uploader.id
    ORDER BY ed.created_at DESC
")->fetchAll();

// Get document statistics
$docStats = $pdo->query("
    SELECT document_type, COUNT(*) as count 
    FROM employee_documents 
    GROUP BY document_type
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Document Management - HR System</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'admin_header.php'; ?>
    
    <div class="container">
        <h1><i class="fas fa-file-alt"></i> Document Management</h1>
        
        <?php displayMessages(); ?>
        
        <!-- Document Statistics -->
        <div class="stats-grid">
            <?php
            $totalDocs = $pdo->query("SELECT COUNT(*) FROM employee_documents")->fetchColumn();
            $todayDocs = $pdo->query("SELECT COUNT(*) FROM employee_documents WHERE DATE(created_at) = CURDATE()")->fetchColumn();
            $contractDocs = $pdo->query("SELECT COUNT(*) FROM employee_documents WHERE document_type = 'contract'")->fetchColumn();
            ?>
            <div class="stat-card">
                <div class="stat-number"><?= $totalDocs ?></div>
                <div>Total Documents</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $todayDocs ?></div>
                <div>Uploaded Today</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $contractDocs ?></div>
                <div>Employment Contracts</div>
            </div>
        </div>

        <!-- Document Type Filter -->
        <div class="card">
            <h3>Document Type Distribution</h3>
            <div class="doc-type-filters">
                <button class="btn active" onclick="filterDocuments('all')">All Documents</button>
                <?php foreach ($docStats as $stat): ?>
                    <button class="btn" onclick="filterDocuments('<?= $stat['document_type'] ?>')">
                        <?= ucfirst($stat['document_type']) ?> (<?= $stat['count'] ?>)
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Documents List -->
        <div class="card">
            <h3>All Documents</h3>
            <div class="documents-list">
                <?php foreach ($documents as $doc): ?>
                <div class="document-item" data-type="<?= $doc['document_type'] ?>">
                    <div class="doc-icon">
                        <i class="fas fa-file-pdf" style="color: #e74c3c;"></i>
                    </div>
                    <div class="doc-info">
                        <h4><?= htmlspecialchars($doc['document_name']) ?></h4>
                        <p>
                            <strong>Employee:</strong> <?= htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']) ?> 
                            (<?= $doc['employee_id'] ?>)<br>
                            <strong>Type:</strong> <?= ucfirst($doc['document_type']) ?> • 
                            <strong>Uploaded by:</strong> <?= $doc['uploader_first'] . ' ' . $doc['uploader_last'] ?> • 
                            <strong>Date:</strong> <?= date('M j, Y H:i', strtotime($doc['created_at'])) ?>
                        </p>
                    </div>
                    <div class="doc-actions">
                        <a href="<?= $doc['file_path'] ?>" target="_blank" class="btn btn-sm">
                            <i class="fas fa-eye"></i> View
                        </a>
                        <a href="<?= $doc['file_path'] ?>" download class="btn btn-sm">
                            <i class="fas fa-download"></i> Download
                        </a>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="doc_id" value="<?= $doc['id'] ?>">
                            <button type="submit" name="delete_document" class="btn btn-danger btn-sm" 
                                    onclick="return confirm('Are you sure you want to delete this document?')">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
    function filterDocuments(type) {
        const items = document.querySelectorAll('.document-item');
        const buttons = document.querySelectorAll('.doc-type-filters .btn');
        
        // Update active button
        buttons.forEach(btn => btn.classList.remove('active'));
        event.currentTarget.classList.add('active');
        
        // Filter items
        items.forEach(item => {
            if (type === 'all' || item.getAttribute('data-type') === type) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }
    </script>

    <style>
    .doc-type-filters { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
    .document-item { 
        display: flex; 
        align-items: center; 
        padding: 15px; 
        border: 1px solid #eee; 
        border-radius: 5px; 
        margin-bottom: 10px; 
        gap: 15px;
    }
    .doc-icon { font-size: 2em; }
    .doc-info { flex: 1; }
    .doc-actions { display: flex; gap: 5px; }
    @media (max-width: 768px) {
        .document-item { flex-direction: column; align-items: start; }
        .doc-actions { align-self: end; }
    }
    </style>
</body>
</html>