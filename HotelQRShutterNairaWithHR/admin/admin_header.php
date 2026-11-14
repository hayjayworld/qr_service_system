<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
//echo "<script>alert('".$_SESSION['admin_role']."')</script>";
//requireAuth();
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
            const reserveCount = document.getElementById('reserve-count');
            //const notificationDropdown = document.getElementById('notification-dropdown');
            
            // Initialize EventSource
            const eventSource = new EventSource('orders_stream.php');
            const eventSource1 = new EventSource('messages_stream.php');
            const eventSource2 = new EventSource('reserve_stream.php');
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
            
            eventSource1.addEventListener('new-complaint', function(e) {
                const order = JSON.parse(e.data);
                const count = parseInt(messageCount.textContent) + 1;
                
                // Update notification count
                messageCount.textContent = count;
                messageCount.classList.remove('hidden');
                
                // Play sound
                const audio = new Audio('../assets/sounds/message.mp3');
                audio.play().catch(e => console.log('Audio play failed:', e));
            });
            eventSource2.addEventListener('new-reservation', function(e) {
                const order = JSON.parse(e.data);
                const count = parseInt(reserveCount.textContent) + 1;
                
                // Update notification count
                reserveCount.textContent = count;
                reserveCount.classList.remove('hidden');
                
                // Play sound
                const audio = new Audio('../assets/sounds/notification.mp3');
                audio.play().catch(e => console.log('Audio play failed:', e));
                
                // Show desktop notification if permitted
                if (Notification.permission === 'granted') {
                    new Notification(`New Reservation (${order.room_id})`, {
                        body: `${order.room_id} items - $${order.check_in}`
                    });
                }
            });
            // Request notification permission
            if (window.Notification && Notification.permission !== "granted") {
                Notification.requestPermission();
            }
        });
    </script>
    
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="mobile-menu-toggle" id="mobileMenuToggle">
            <i class="fas fa-bars"></i>
        </div>

        <aside class="sidebar">
            <div class="logo">
                <h2><i class="fas fa-qrcode"></i> Hotel QR System</h2>
            </div>
            
            <!-- Scrollable navigation -->
            <div class="sidebar-scroll">
                <nav>
                    <ul>
                        <li class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
                            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                        </li>
                        <?php if (canAccess('can_manage_hr')): ?>
                            <?php if($_SESSION['admin_role'] == "Human Resource Officer"){ ?>
                        <li class="<?= basename($_SERVER['PHP_SELF']) === 'hr_dashboard.php' ? 'active' : '' ?>">
                            <a href="hr_dashboard.php"><i class="fas fa-users"></i>HR Dashboard</a>
                        </li>
                        <li class="<?= basename($_SERVER['PHP_SELF']) === 'employees.php' ? 'active' : '' ?>">
                            <a href="employees.php"><i class="fas fa-user-plus"></i>Employees</a>
                        </li>
                        <li class="<?= basename($_SERVER['PHP_SELF']) === 'hr_attendance.php' ? 'active' : '' ?>">
                            <a href="hr_attendance.php"><i class="fas fa-clock"></i>HR Attendance</a>
                        </li>
                        <li class="<?= basename($_SERVER['PHP_SELF']) === 'leave_management.php' ? 'active' : '' ?>">
                            <a href="leave_management.php"><i class="fas fa-calendar"></i>Leave Management</a>
                        </li>
                        <li class="<?= basename($_SERVER['PHP_SELF']) === 'payroll.php' ? 'active' : '' ?>">
                            <a href="payroll.php"><i class="fas fa-calculator"></i>Payroll</a>
                        </li>
                        <li class="<?= basename($_SERVER['PHP_SELF']) === 'documents.php' ? 'active' : '' ?>">
                            <a href="documents.php"><i class="fas fa-users"></i>Documents</a>
                        </li>
                        <li class="<?= basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : '' ?>">
                            <a href="reports.php"><i class="fas fa-chart-bar"></i>Reports</a>
                        </li>
                        <?php }else{?>
                            <li class="has-submenu 
                            <?= basename($_SERVER['PHP_SELF']) === 'hr_dashboard.php' ? 'active' : '' ?>
                            <?= basename($_SERVER['PHP_SELF']) === 'employees.php' ? 'active' : '' ?>
                            <?= basename($_SERVER['PHP_SELF']) === 'hr_attendance.php' ? 'active' : '' ?>
                            <?= basename($_SERVER['PHP_SELF']) === 'leave_management.php' ? 'active' : '' ?>
                            <?= basename($_SERVER['PHP_SELF']) === 'payroll.php' ? 'active' : '' ?>
                            <?= basename($_SERVER['PHP_SELF']) === 'documents.php' ? 'active' : '' ?>
                            <?= basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : '' ?>
                            <?= basename($_SERVER['PHP_SELF']) === 'employee_profile.php' ? 'active' : '' ?>
                            ">
                                <a href="#"><i class="fas fa-users"></i> HR Management</a>
                                <ul class="submenu"> 
                                    <li class="<?= basename($_SERVER['PHP_SELF']) === 'hr_dashboard.php' ? 'active' : '' ?>">
                                        <a href="hr_dashboard.php">HR Dashboard</a></li>
                                    <li class="<?= basename($_SERVER['PHP_SELF']) === 'employees.php' ? 'active' : '' ?>
                                    <?= basename($_SERVER['PHP_SELF']) === 'employee_profile.php' ? 'active' : '' ?>">
                                        <a href="employees.php">Employees</a></li>
                                    <li class="<?= basename($_SERVER['PHP_SELF']) === 'hr_attendance.php' ? 'active' : '' ?>">
                                        <a href="hr_attendance.php">Attendance</a></li>
                                    <li class="<?= basename($_SERVER['PHP_SELF']) === 'leave_management.php' ? 'active' : '' ?>">
                                        <a href="leave_management.php">Leave Management</a></li>
                                    <li class="<?= basename($_SERVER['PHP_SELF']) === 'payroll.php' ? 'active' : '' ?>">
                                        <a href="payroll.php">Payroll</a></li>
                                    <li class="<?= basename($_SERVER['PHP_SELF']) === 'documents.php' ? 'active' : '' ?>">
                                        <a href="documents.php">Documents</a></li>
                                    <li class="<?= basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : '' ?>">
                                        <a href="reports.php">Reports</a></li>
                                </ul> 
                            </li>
                        <?php }?>
                        <?php endif; ?>
                        
                        <?php if (canAccess('can_manage_users')): ?>
                        <li class="<?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : '' ?>">
                            <a href="users.php"><i class="fas fa-user"></i> Users</a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (canAccess('can_manage_services')): ?>
                        <li class="<?= basename($_SERVER['PHP_SELF']) === 'services.php' ? 'active' : '' ?>">
                            <a href="services.php"><i class="fas fa-concierge-bell"></i> Services</a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (canAccess('can_manage_categories')): ?>
                        <li class="<?= basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'active' : '' ?>">
                            <a href="categories.php"><i class="fas fa-list"></i> Categories</a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (canAccess('can_manage_orders')): ?>
                        <li class="<?= basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : '' ?>">
                            <a href="orders.php"><i class="fas fa-clipboard-list"></i> Orders
                                <div class="notification-icon">
                                    <i class="fas fa-bell"></i>
                                    <span id="notification-count" class="notification-count hidden">0</span>
                                </div>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (canAccess('can_manage_qr')): ?>
                        <li class="<?= basename($_SERVER['PHP_SELF']) === 'qr_generator.php' ? 'active' : '' ?>">
                            <a href="qr_generator.php"><i class="fas fa-qrcode"></i> QR Codes</a>
                        </li>
                        <?php endif; ?>

                        <!--<?php if (canAccess('can_manage_rooms')): ?>
                            <li class="<?= basename($_SERVER['PHP_SELF']) === 'rooms.php' ? 'active' : '' ?>">
                                <a href="rooms.php"><i class="fas fa-bed"></i> Room Management</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php if (canAccess('can_manage_reservations')): ?>
                            <li class="<?= basename($_SERVER['PHP_SELF']) === 'reservations.php' ? 'active' : '' ?>">
                                <a href="reservations.php"><i class="fas fa-calendar-alt"></i> Reservations
                                    <div class="notification-icon">
                                        <i class="fas fa-bell"></i>
                                        <span id="reserve-count" class="notification-count hidden">0</span>
                                    </div>
                                </a>
                            </li>
                        <?php endif; ?>-->
                        
                        <?php if (canAccess('can_manage_complaints')): ?>
                        <li class="<?= basename($_SERVER['PHP_SELF']) === 'messages.php' ? 'active' : '' ?>">
                            <a href="messages.php"><i class="fas fa-clipboard-list"></i> Complaints
                            <div class="notification-icon">
                                <i class="fas fa-message"></i>
                                <span id="message-count" class="notification-count hidden">0</span>
                            </div>
                        </a>
                        </li>
                        <?php endif; ?>
                        
                        <!--<?php if (canAccess('can_manage_discounts')): ?>
                        <li class="<?= basename($_SERVER['PHP_SELF']) === 'discounts.php' ? 'active' : '' ?>">
                            <a href="discounts.php"><i class="fas fa-tag"></i> Discounts</a>
                        </li>
                        <?php endif; ?>-->
                        
                        <?php if (canAccess('can_view_analytics')): ?>
                        <li class="<?= basename($_SERVER['PHP_SELF']) === 'analytics.php' ? 'active' : '' ?>">
                            <a href="analytics.php"><i class="fas fa-chart-line"></i> Analytics</a>
                        </li>
                        <?php endif; ?>

                        <li class="<?= basename($_SERVER['PHP_SELF']) === 'attendance.php' ? 'active' : '' ?>">
                            <a href="attendance.php"><i class="fas fa-clock"></i>Attendance</a>
                        </li>
                        
                        <?php if (canAccess('can_manage_settings')): ?>
                        <li class="<?= basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : '' ?>">
                            <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            
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