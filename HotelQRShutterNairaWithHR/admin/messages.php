<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

//requireAuth();
//requireStaffOrHigher();

// Handle marking complaint as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_as_read'])) {
    $complaint_id = $_POST['complaint_id'];
    $sql = "UPDATE complaints SET status = 'read' WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$complaint_id]);
}

// Get all complaints
$unreadComplaints = getComplaintsByStatus($pdo, 'unread');
$readComplaints = getComplaintsByStatus($pdo, 'read');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Complaints - Hotel QR System</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .complaint-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .complaint-card.unread {
            border-left: 4px solid #e74c3c;
        }
        .complaint-card.read {
            border-left: 4px solid #2ecc71;
        }
        .complaint-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .complaint-location {
            font-weight: bold;
            color: #2c3e50;
        }
        .complaint-date {
            color: #7f8c8d;
            font-size: 0.9em;
        }
        .complaint-text {
            margin: 15px 0;
            line-height: 1.6;
        }
        .complaint-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
        }
        .status-unread {
            background: #e74c3c;
            color: white;
        }
        .status-read {
            background: #2ecc71;
            color: white;
        }
    </style>
</head>
<body>
    <?php include 'admin_header.php'; ?>
    
    <div class="container">
        <h1>Manage Complaints</h1>
        
        <div class="tabs">
            <button class="tab-btn active" onclick="openTab('unread-complaints')">Unread Complaints</button>
            <button class="tab-btn" onclick="openTab('read-complaints')">Read Complaints</button>
        </div>
        
        <div id="unread-complaints" class="tab-content" style="display: block;">
            <?php if (count($unreadComplaints) > 0): ?>
                <?php foreach ($unreadComplaints as $complaint): ?>
                    <div class="complaint-card unread">
                        <div class="complaint-header">
                            <span class="complaint-location"><?= htmlspecialchars($complaint['location']) ?></span>
                            <span class="complaint-date"><?= date('M j, Y H:i', strtotime($complaint['created_at'])) ?></span>
                        </div>
                        <div class="complaint-text"><?= nl2br(htmlspecialchars($complaint['complaint_text'])) ?></div>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="complaint_id" value="<?= $complaint['id'] ?>">
                            <button type="submit" name="mark_as_read" class="btn small success">
                                <i class="fas fa-check"></i> Mark as Read
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No unread complaints at this time.</p>
            <?php endif; ?>
        </div>
        
        <div id="read-complaints" class="tab-content">
            <?php if (count($readComplaints) > 0): ?>
                <?php foreach ($readComplaints as $complaint): ?>
                    <div class="complaint-card read">
                        <div class="complaint-header">
                            <span class="complaint-location"><?= htmlspecialchars($complaint['location']) ?></span>
                            <span class="complaint-date"><?= date('M j, Y H:i', strtotime($complaint['created_at'])) ?></span>
                        </div>
                        <div class="complaint-text"><?= nl2br(htmlspecialchars($complaint['complaint_text'])) ?></div>
                        <span class="complaint-status status-read">READ</span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No read complaints yet.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // function openTab(tabId) {
        //     // Hide all tab content
        //     document.querySelectorAll('.tab-content').forEach(tab => {
        //         tab.style.display = 'none';
        //     });
            
        //     // Remove active class from all tab buttons
        //     document.querySelectorAll('.tab-btn').forEach(btn => {
        //         btn.classList.remove('active');
        //     });
            
        //     // Show the selected tab content
        //     document.getElementById(tabId).style.display = 'block';
            
        //     // Add active class to the clicked button
        //     event.currentTarget.classList.add('active');
        // }
    </script>
</body>
</html>