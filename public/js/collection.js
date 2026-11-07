// ==========================================
// CHANGE PRODUCT QUANTITY
// ==========================================

function changeQuantity(productId, change) {
    const quantityInput = document.getElementById(
        `quantity-${productId}`
    );

    if (!quantityInput) {
        return;
    }

    const currentQuantity = parseInt(quantityInput.value) || 1;
    const minimum = parseInt(quantityInput.min) || 1;
    const maximum = parseInt(quantityInput.max) || 1;

    const newQuantity = currentQuantity + change;

    if (newQuantity >= minimum && newQuantity <= maximum) {
        quantityInput.value = newQuantity;
    }
}


// ==========================================
// ADD PRODUCT TO CART
// ==========================================

async function addToCart(productId) {
    const productCard = document.querySelector(
        `.collection-card[data-product-id="${productId}"]`
    );

    if (!productCard) {
        showNotification('Product not found.');
        return;
    }

    const quantityInput = document.getElementById(
        `quantity-${productId}`
    );

    const sizeInput = document.getElementById(
        `size-${productId}`
    );

    const addButton = productCard.querySelector('.add-cart');

    if (!quantityInput || !sizeInput || !addButton) {
        showNotification('Unable to get product information.');
        return;
    }

    const quantity = parseInt(quantityInput.value);
    const size = sizeInput.value;

    if (!size) {
        showNotification('Please select a size first.');
        return;
    }

    if (!quantity || quantity < 1) {
        showNotification('Please select a valid quantity.');
        return;
    }

    const addUrl = addButton.dataset.addUrl;

    const loginUrl = document.querySelector(
        '.collections-main'
    )?.dataset.loginUrl;

    try {
        addButton.disabled = true;
        addButton.textContent = 'ADDING...';

        const response = await fetch(addUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                quantity: quantity,
                size: size
            })
        });

        const result = await response.json();

        // Customer is not logged in.
        if (result.login_required) {
            showNotification('Please log in before adding products.');

            setTimeout(() => {
                window.location.href = loginUrl;
            }, 1000);

            return;
        }

        if (!response.ok || !result.success) {
            showNotification(result.message || 'Unable to add product.');
            return;
        }

        showNotification(result.message);

        // Update cart badge if one exists in the navbar.
        const cartCount = document.getElementById('cartCount');

        if (cartCount) {
            cartCount.textContent = result.cartCount;
        }

        // Reset quantity after successful addition.
        quantityInput.value = 1;

    } catch (error) {
        console.error('Add to cart error:', error);
        showNotification('Something went wrong. Please try again.');

    } finally {
        addButton.disabled = false;
        addButton.textContent = 'ADD TO CART 🛒';
    }
}


// ==========================================
// DISPLAY NOTIFICATION
// ==========================================

function showNotification(message) {
    const notification = document.getElementById('cartNotification');

    if (!notification) {
        alert(message);
        return;
    }

    notification.textContent = message;
    notification.classList.add('show');

    setTimeout(() => {
        notification.classList.remove('show');
    }, 3000);
}