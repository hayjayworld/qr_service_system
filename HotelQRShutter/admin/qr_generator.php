<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

//requireAuth();
//requireManagerOrAdmin();
// Handle QR code creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_qr'])) {
    $label = trim($_POST['label']);
    
    if (!empty($label)) {
        try {
            $qr_id = createQRCode($pdo, $label);
            $message = 'QR code added successfully!';
            
            // Generate QR code URL
            $qrUrl = "http://" . $_SERVER['HTTP_HOST'] . "/HotelQRShutter/qr_pages/service_menu.php?location=" . urlencode($label);
            
            // Generate download link
            $downloadLink = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qrUrl);
            
            // Store download link in session temporarily
            $_SESSION['qr_download_link'] = $downloadLink;
            $_SESSION['qr_label'] = $label;
            
        } catch (PDOException $e) {
            $error = 'Error: This label already exists.';
        }
    } else {
        $error = 'Please enter a label for the QR code.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_qr'])) {
    $qr_id = (int)$_POST['qr_id'];
    deleteQRCode($pdo, $qr_id);
    $_SESSION['message'] = 'QR code deleted successfully';
    header('Location: qr_generator.php');
    exit();
}
// Get all QR codes
$qrCodes = getAllQRCodes($pdo);
?>

<?php include 'admin_header.php'; ?>

<div class="content-header">
    <h1><i class="fas fa-qrcode"></i> QR Code Generator</h1>
    <button class="btn btn-primary" onclick="openModal('add-qr-modal')">
        <i class="fas fa-plus"></i> Generate New QR Code
    </button>
</div>

<?php if (isset($message)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php if (isset($_SESSION['qr_download_link'])): ?>
        <div class="alert alert-info">
            <p>QR Code for "<?= htmlspecialchars($_SESSION['qr_label']) ?>" generated successfully!</p>
            <div class="qr-preview">
                <img src="<?= $_SESSION['qr_download_link'] ?>" alt="QR Code">
            </div>
            <div class="qr-actions">
                <a href="<?= $_SESSION['qr_download_link'] ?>" download="<?= htmlspecialchars($_SESSION['qr_label']) ?>-qrcode.png" class="btn btn-primary">
                    <i class="fas fa-download"></i> Download QR Code
                </a>
                <button class="btn btn-secondary" onclick="window.print()">
                    <i class="fas fa-print"></i> Print QR Code
                </button>
            </div>
        </div>
    <?php 
        unset($_SESSION['qr_download_link']);
        unset($_SESSION['qr_label']);
    endif; ?>
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
                    <th>Label</th>
                    <th>QR Code</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($qrCodes as $qr): 
                    $qrUrl = "http://" . $_SERVER['HTTP_HOST'] . "/HotelQRShutter/qr_pages/service_menu.php?location=" . urlencode($qr['label']);
                    $downloadLink = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($qrUrl);
                ?>
                    <tr>
                        <td><?= $qr['id'] ?></td>
                        <td><?= htmlspecialchars($qr['label']) ?></td>
                        <td>
                            <img src="<?= $downloadLink ?>" alt="QR Code" class="qr-thumbnail">
                        </td>
                        <td class="actions">
                            <a href="<?= $downloadLink ?>" download="<?= htmlspecialchars($qr['label']) ?>-qrcode.png" class="btn btn-sm btn-primary">
                                <i class="fas fa-download"></i> Download
                            </a>
                            <button class="btn btn-sm btn-secondary" onclick="printQR('<?= $downloadLink ?>', '<?= htmlspecialchars($qr['label']) ?>')">
                                <i class="fas fa-print"></i> Print
                            </button>
                            <form method="POST" action="qr_generator.php" style="display: inline;">
                                <input type="hidden" name="qr_id" value="<?= $qr['id'] ?>">
                                <button type="submit" name="delete_qr" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this QR code?')">
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

<!-- Add QR Modal -->
<div id="add-qr-modal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('add-qr-modal')">&times;</span>
        <h2>Generate New QR Code</h2>
        <form method="POST">
            <div class="form-group">
                <label for="label">QR Code Label:</label>
                <input type="text" id="label" name="label" placeholder="e.g. Room 101, Poolside Table 3" required>
                <small>This will be displayed when the QR code is scanned</small>
            </div>
            <button type="submit" name="add_qr" class="btn btn-primary">
                <i class="fas fa-qrcode"></i> Generate QR Code
            </button>
        </form>
    </div>
</div>

<script src="../assets/js/admin.js"></script>

<?php include 'admin_footer.php'; ?>