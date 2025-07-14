// Handle modals
function openModal(id) {
    document.getElementById(id).style.display = 'block';
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

// Edit category function
function editCategory(id, name, description) {
    document.getElementById('edit_category_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_description').value = description;
    openModal('edit-category-modal');
}

// Add this to your admin.js
function confirmDelete(event) {
    if (!confirm('Are you sure you want to delete this QR code? This action cannot be undone.')) {
        event.preventDefault();
    }
}

// Initialize delete buttons
document.querySelectorAll('button[name="delete_qr"]').forEach(button => {
    button.addEventListener('click', confirmDelete);
});

// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (mobileMenuToggle && sidebar) {
        mobileMenuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            
            // Change icon based on state
            const icon = this.querySelector('i');
            if (sidebar.classList.contains('active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });
        
        // Close menu when clicking on a nav link (for mobile)
        document.querySelectorAll('.sidebar nav a').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('active');
                    const icon = mobileMenuToggle.querySelector('i');
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            });
        });
    }
});

function openTab(tabId) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.style.display = 'none';
    });
    
    // Remove active class from all tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show the selected tab content
    document.getElementById(tabId).style.display = 'block';
    
    // Add active class to the clicked button
    event.currentTarget.classList.add('active');
} 

function editService(id, name, categoryId, description, price, isActive) {
    // Set form values
    document.getElementById('edit_service_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_category_id').value = categoryId;
    document.getElementById('edit_description').value = description;
    document.getElementById('edit_price').value = price;
    document.getElementById('edit_is_active').checked = isActive;
    
    // Show the edit tab
    openTab('edit-service');
}

function cancelEdit() {
    openTab('existing-services');
}

// Print QR code
function printQR(qrUrl, label) {
    var printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Print QR Code - ${label}</title>
            <style>
                body { text-align: center; padding: 20px; }
                h1 { margin-bottom: 10px; }
                img { margin: 20px auto; display: block; }
                p { margin-top: 10px; }
            </style>
        </head>
        <body>
            <h1>${label}</h1>
            <img src="${qrUrl}" alt="QR Code">
            <p>Scan this QR code to access our services</p>
            <script>
                window.onload = function() { window.print(); }
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}

const eventSource = new EventSource('../../admin/orders_stream.php');

eventSource.addEventListener('new-order', function(e) {
    const order = JSON.parse(e.data);
    
    // Refresh the orders table or add the new order to the top
    location.reload(); // Simple solution - or implement dynamic row addition
    
    // For a more advanced solution, you could:
    // 1. Add the new order to the table dynamically
    // 2. Highlight the new row
    // 3. Keep pagination in sync
});

document.querySelectorAll('.cancel-order-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (confirm('Are you sure you want to cancel this order?')) {
            fetch('../../admin/cancel_order.php', {
                method: 'POST',
                body: new FormData(this)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Order cancelled successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to cancel order'));
                }
            });
        }
    });
});

// Close modals when clicking outside
window.onclick = function(event) {
    if (event.target.className === 'modal') {
        event.target.style.display = 'none';
    }
}