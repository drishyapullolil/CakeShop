// add-to-cart.js - Add to Cart functionality
const CART_API_URL = 'http://localhost/cake/cart.php';

// Initialize cart count on page load
window.addEventListener('DOMContentLoaded', function() {
    updateCartCountDisplay();
});

// Add to cart function
async function addToCart(cakeId, quantity = 1) {
    try {
        const response = await fetch(`${CART_API_URL}?action=add`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                cake_id: cakeId,
                quantity: quantity
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Show success notification
            showNotification('✅ Item added to cart!', 'success');
            
            // Update cart count
            updateCartCountDisplay();
            
            // Optional: Add animation to cart icon
            animateCartIcon();
        } else {
            showNotification('❌ ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error adding to cart:', error);
        showNotification('❌ Error adding to cart', 'error');
    }
}

// Update cart count display
async function updateCartCountDisplay() {
    try {
        const response = await fetch(`${CART_API_URL}?action=count`);
        const data = await response.json();
        
        if (data.success) {
            const cartCountElement = document.getElementById('cartCount');
            if (cartCountElement) {
                cartCountElement.textContent = data.count || 0;
                
                // Add pulse animation if count increased
                if (data.count > 0) {
                    cartCountElement.style.animation = 'none';
                    setTimeout(() => {
                        cartCountElement.style.animation = 'pulse 0.5s';
                    }, 10);
                }
            }
        }
    } catch (error) {
        console.error('Error updating cart count:', error);
    }
}

// Animate cart icon
function animateCartIcon() {
    const cartIcon = document.querySelector('.cart-icon');
    if (cartIcon) {
        cartIcon.style.animation = 'bounce 0.5s';
        setTimeout(() => {
            cartIcon.style.animation = '';
        }, 500);
    }
}

// Show notification
function showNotification(message, type = 'success') {
    // Remove existing notifications
    const existingNotification = document.querySelector('.cart-notification');
    if (existingNotification) {
        existingNotification.remove();
    }
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `cart-notification ${type}`;
    notification.textContent = message;
    
    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#4caf50' : '#f44336'};
        color: white;
        padding: 15px 25px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 10000;
        font-size: 16px;
        font-weight: 500;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    @keyframes pulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.2);
        }
    }
    
    @keyframes bounce {
        0%, 100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-10px);
        }
    }
`;
document.head.appendChild(style);