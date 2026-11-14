document.addEventListener('DOMContentLoaded', function() {
    // Initialize cart from sessionStorage or empty array
    let cart = JSON.parse(sessionStorage.getItem('cart')) || [];
    
    // Get DOM elements
    const cartItemsEl = document.getElementById('cart-items');
    const cartTotalEl = document.getElementById('cart-total');
    const submitOrderBtn = document.getElementById('submit-order');
    const orderNotesEl = document.getElementById('order-notes');
    
    // Get location from URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    const locationLabel = urlParams.get('location');
    
    // Render the cart
    function renderCart() {
        cartItemsEl.innerHTML = '';
        let total = 0;
        
        if (cart.length === 0) {
            cartItemsEl.innerHTML = '<p>Your cart is empty</p>';
            cartTotalEl.textContent = 'Total: $0.00';
            return;
        }
        
        cart.forEach((item, index) => {
            const itemTotal = item.price * item.quantity;
            total += itemTotal;
            
            const itemEl = document.createElement('div');
            itemEl.className = 'cart-item';
            itemEl.innerHTML = `
                <div class="cart-item-name">${item.name}</div>
                <div class="cart-item-quantity">
                    <button class="quantity-btn" data-index="${index}" data-action="decrease">-</button>
                    ${item.quantity}
                    <button class="quantity-btn" data-index="${index}" data-action="increase">+</button>
                </div>
                <div class="cart-item-price">$${itemTotal.toFixed(2)}</div>
                <div class="cart-item-actions">
                    <button class="remove-btn" data-index="${index}">Remove</button>
                </div>
            `;
            cartItemsEl.appendChild(itemEl);
        });
        
        cartTotalEl.textContent = `Total: $${total.toFixed(2)}`;
        
        // Add event listeners to quantity buttons
        document.querySelectorAll('.quantity-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const index = parseInt(this.getAttribute('data-index'));
                const action = this.getAttribute('data-action');
                
                if (action === 'increase') {
                    cart[index].quantity += 1;
                } else if (action === 'decrease') {
                    cart[index].quantity -= 1;
                    if (cart[index].quantity < 1) {
                        cart.splice(index, 1);
                    }
                }
                
                sessionStorage.setItem('cart', JSON.stringify(cart));
                renderCart();
            });
        });
        
        // Add event listeners to remove buttons
        document.querySelectorAll('.remove-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const index = parseInt(this.getAttribute('data-index'));
                cart.splice(index, 1);
                sessionStorage.setItem('cart', JSON.stringify(cart));
                renderCart();
            });
        });
    }
    
    // Add to cart functionality
    document.querySelectorAll('.add-to-cart').forEach(btn => {
        btn.addEventListener('click', function() {
            const serviceCard = this.closest('.service-card');
            const serviceId = parseInt(serviceCard.getAttribute('data-service-id'));
            const serviceName = serviceCard.querySelector('h3').textContent;
            const servicePrice = parseFloat(serviceCard.querySelector('.price').textContent.replace('$', ''));
            
            // Check if item already in cart
            const existingItem = cart.find(item => item.id === serviceId);
            
            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                cart.push({
                    id: serviceId,
                    name: serviceName,
                    price: servicePrice,
                    quantity: 1
                });
            }
            
            sessionStorage.setItem('cart', JSON.stringify(cart));
            renderCart();
            
            // Show feedback
            const feedback = document.createElement('div');
            feedback.textContent = `${serviceName} added to cart`;
            feedback.style.position = 'fixed';
            feedback.style.bottom = '20px';
            feedback.style.right = '20px';
            feedback.style.backgroundColor = '#2ecc71';
            feedback.style.color = 'white';
            feedback.style.padding = '10px 20px';
            feedback.style.borderRadius = '4px';
            feedback.style.zIndex = '1000';
            document.body.appendChild(feedback);
            
            setTimeout(() => {
                feedback.style.transition = 'opacity 0.5s';
                feedback.style.opacity = '0';
                setTimeout(() => feedback.remove(), 500);
            }, 2000);
        });
    });
    
    // Submit order functionality
    // Modify your existing submit order code:
        submitOrderBtn.addEventListener('click', function() {
            if (cart.length === 0) {
                alert('Your cart is empty. Please add items before submitting.');
                return;
            }

            if (!locationLabel) {
                alert('Location not detected. Please scan the QR code again.');
                return;
            }

            fetch('../includes/submit_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    location_label: locationLabel,
                    items: cart,
                    notes: orderNotesEl.value.trim()
                })
            })
            .then(response => response.json())
            .then(data => {
        if (data.success) {
            // Show success message
            const successDiv = document.createElement('div');
            successDiv.className = 'order-success';
            successDiv.innerHTML = `
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <h3>Order Submitted Successfully!</h3>
                    <p>Your order #${data.order_id} has been received.</p>
                    <div class="social-media-connect">
                        <p>Connect with us:</p>
                        <div class="social-icons">
                            <a href="https://facebook.com/yourhotel" target="_blank" class="social-icon">
                                <i class="fab fa-facebook"></i>
                            </a>
                            <a href="https://twitter.com/yourhotel" target="_blank" class="social-icon">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="https://instagram.com/yourhotel" target="_blank" class="social-icon">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="https://linkedin.com/company/yourhotel" target="_blank" class="social-icon">
                                <i class="fab fa-linkedin"></i>
                            </a>
                        </div>
                        <p class="close-message">You may now close this window.</p>
                    </div>
                </div>
            `;
            
            // Replace page content
            document.body.innerHTML = '';
            document.body.appendChild(successDiv);
            
            // Close after 5 seconds (optional)
            setTimeout(() => {
                window.close(); // Will work if opened via window.open()
            }, 5000);
        } else {
            alert('Error: ' + (data.message || 'Failed to submit order'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error submitting order. Please try again.');
    });
    });
    
    // Initial render
    renderCart();
});

// Add this CSS to your style.css or in a style tag
const style = document.createElement('style');
style.textContent = `
    .order-success {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        background-color: #f5f5f5;
    }
    
    .success-message {
        text-align: center;
        padding: 30px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        max-width: 400px;
    }
    
    .success-message i {
        font-size: 60px;
        color: #2ecc71;
        margin-bottom: 20px;
    }
    
    .success-message h3 {
        color: #2ecc71;
        margin-bottom: 10px;
    }
    
    .social-media-connect {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #eee;
    }
    
    .social-icons {
        display: flex;
        justify-content: center;
        gap: 15px;
        margin: 15px 0;
    }
    
    .social-icon {
        font-size: 24px;
        transition: color 0.3s;
    }
    
    .social-icon:hover {
        color: #3498db;
    }
    
    .close-message {
        color: #777;
        font-size: 14px;
        margin-top: 20px;
    }
`;
document.head.appendChild(style);