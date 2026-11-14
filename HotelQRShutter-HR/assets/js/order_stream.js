
    //Real-time order notifications
    document.addEventListener('DOMContentLoaded', function() {
        const notificationBell = document.getElementById('notification-bell');
        const notificationCount = document.getElementById('notification-count');
        const notificationDropdown = document.getElementById('notification-dropdown');
        
        // Initialize EventSource
        const eventSource = new EventSource('../../admin/orders_stream.php');
        
        eventSource.addEventListener('new-order', function(e) {
            const order = JSON.parse(e.data);
            const count = parseInt(notificationCount.textContent) + 1;
            
            // Update notification count
            notificationCount.textContent = count;
            notificationCount.classList.remove('hidden');
            
            // Add new notification
            const notification = document.createElement('a');
            notification.href = `orders.php#order-${order.id}`;
            notification.className = 'notification-item';
            notification.innerHTML = `
                <div class="notification-icon">
                    <i class="fas fa-bell"></i>
                </div>
                <div class="notification-content">
                    <p>New order from ${order.location}</p>
                    <small>${order.itemCount} items ($${order.total}) • ${order.time}</small>
                </div>
            `;
            
            notificationDropdown.insertBefore(notification, notificationDropdown.firstChild);
            
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
        
        // Request notification permission
        if (window.Notification && Notification.permission !== "granted") {
            Notification.requestPermission();
        }
        
        // Toggle notification dropdown
        notificationBell.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationDropdown.classList.toggle('show');
            notificationCount.classList.add('hidden');
        });
        
        // Close dropdown when clicking elsewhere
        document.addEventListener('click', function() {
            notificationDropdown.classList.remove('show');
        });
    });