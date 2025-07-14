<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
//echo "<script>alert('".$_SESSION['admin_role']."')</script>";
requireAuth();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <meta name="stream-token" content="<?= generateStreamToken($_SESSION['admin_id']) ?>"> -->
    <title>Hotel QR System - Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- <script src="/assets/js/realtime.js" defer></script> -->
    <!-- Add this before the closing </head> tag -->
    <script>
        // Real-time order notifications
        document.addEventListener('DOMContentLoaded', function() {
            //const notificationBell = document.getElementById('notification-bell');
            const notificationCount = document.getElementById('notification-count');
            const messageCount = document.getElementById('message-count');
            //const notificationDropdown = document.getElementById('notification-dropdown');
            
            // Initialize EventSource
            const eventSource = new EventSource('orders_stream.php');
            const eventSource1 = new EventSource('messages_stream.php');
            
            eventSource.addEventListener('new-order', function(e) {
                const order = JSON.parse(e.data);
                const count = parseInt(notificationCount.textContent) + 1;
                
                // Update notification count
                notificationCount.textContent = count;
                notificationCount.classList.remove('hidden');
                
                // Play sound
                const audio = new Audio('../assets/sounds/notification.mp3');
                audio.play().catch(e => console.log('Audio play failed:', e));
                
                // Show desktop notification if permitted
                if (Notification.permission === 'granted') {
                    new Notification(`New Order (${order.location})`, {
                        body: `${order.itemCount} items - $${order.total}`
                    });
                }
            });
            
            eventSource1.addEventListener('new-order', function(e) {
                const order = JSON.parse(e.data);
                const count = parseInt(messageCount.textContent) + 1;
                
                // Update notification count
                messageCount.textContent = count;
                messageCount.classList.remove('hidden');
                
                // Play sound
                const audio = new Audio('../assets/sounds/message.mp3');
                audio.play().catch(e => console.log('Audio play failed:', e));
            });
            // Request notification permission
            if (window.Notification && Notification.permission !== "granted") {
                Notification.requestPermission();
            }
        });
    </script>
    <style>
        :root {
            --header-height: 60px;
            --footer-height: 50px;
        }
        
        body {
            margin: 0;
            padding: 0;
            display: grid;
            grid-template-rows: var(--header-height) 1fr var(--footer-height);
            min-height: 100vh;
            overflow: hidden;
        }
        
        header.admin-header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: #2c3e50;
            color: white;
            padding: 0 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .main-content {
            overflow-y: auto;
            padding: 20px;
        }
        
        footer.admin-footer {
            background: #34495e;
            color: white;
            padding: 10px 20px;
            text-align: center;
        }

        /* Mobile menu toggle styles */
        .mobile-menu-toggle {
            display: none;
            cursor: pointer;
            padding: 10px;
            position: absolute;
            left: 10px;
            top: 10px;
            z-index: 101;
            color: white;
        }

        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
            }
            
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                position: fixed;
                height: 100vh;
                z-index: 100;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="mobile-menu-toggle" id="mobileMenuToggle">
            <i class="fas fa-bars"></i>
        </div>
        
        <aside class="sidebar" id="sidebar">
            <div class="logo">
                <h2><i class="fas fa-qrcode"></i> Hotel QR System</h2>
            </div>
            <nav>
                <ul>
                    <li class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
                        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    </li>
                    <?php if ($_SESSION['admin_role'] === 'admin'): ?>
                    <li class="<?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : '' ?>">
                        <a href="users.php"><i class="fas fa-user"></i> Users</a>
                    </li>
                    <?php endif; ?>
                    <?php if (canManageServices()): ?>
                    <li class="<?= basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'active' : '' ?>">
                        <a href="categories.php"><i class="fas fa-list"></i> Categories</a>
                    </li>
                    <li class="<?= basename($_SERVER['PHP_SELF']) === 'services.php' ? 'active' : '' ?>">
                        <a href="services.php"><i class="fas fa-concierge-bell"></i> Services</a>
                    </li>
                    <?php endif; ?>
                    <li class="<?= basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : '' ?>">
                        <a href="orders.php"><i class="fas fa-clipboard-list"></i> Orders
                            <div class="notification-icon">
                                <i class="fas fa-bell"></i>
                                <span id="notification-count" class="notification-count hidden">0</span>
                            </div>
                        </a>
                    </li>
                    <?php if (canManageQR()): ?>
                    <li class="<?= basename($_SERVER['PHP_SELF']) === 'qr_generator.php' ? 'active' : '' ?>">
                        <a href="qr_generator.php"><i class="fas fa-qrcode"></i> QR Codes</a>
                    </li>
                    <?php endif; ?>
                    <?php if (canManageServices()): ?>
                        <li class="<?= basename($_SERVER['PHP_SELF']) === 'messages.php' ? 'active' : '' ?>">
                        <a href="messages.php"><i class="fas fa-clipboard-list"></i> Complaints
                            <div class="notification-icon">
                                <i class="fas fa-message"></i>
                                <span id="message-count" class="notification-count hidden">0</span>
                            </div>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="<?= basename($_SERVER['PHP_SELF']) === 'analytics.php' ? 'active' : '' ?>">
                        <a href="analytics.php"><i class="fas fa-chart-line"></i> Analytics</a>
                    </li>
                    <li class="<?= basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : '' ?>">
                        <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                    </li>
                </ul>
            </nav>
            
            <div class="admin-user">
                <span>
                    <i class="fas fa-user"></i> 
                    <?= htmlspecialchars($_SESSION['admin_username']) ?>
                    <span class="badge <?= $_SESSION['admin_role'] ?>">
                        <?= ucfirst($_SESSION['admin_role']) ?>
                    </span>
                </span>
                <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </aside>
        <script src="../assets/js/admin.js"></script>
        <main class="main-content">