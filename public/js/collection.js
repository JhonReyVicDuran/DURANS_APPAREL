/* =====================================================
   CHANGE PRODUCT QUANTITY
===================================================== */

function changeQuantity(productId, change) {
    const quantityInput = document.getElementById(
        "quantity-" + productId
    );

    if (!quantityInput) {
        return;
    }

    let quantity = parseInt(quantityInput.value) || 1;
    const max = parseInt(quantityInput.max) || 1;

    quantity += change;

    if (quantity < 1) {
        quantity = 1;
    }

    if (quantity > max) {
        quantity = max;
    }

    quantityInput.value = quantity;
}


/* =====================================================
   ADD PRODUCT TO CART
===================================================== */

async function addToCart(productId) {
    const quantityInput = document.getElementById(
        "quantity-" + productId
    );

    const sizeInput = document.getElementById(
        "size-" + productId
    );

    const productCard = document.querySelector(
        '.collection-card[data-product-id="' + productId + '"]'
    );

    const addButton = productCard?.querySelector(".add-cart");

    if (!quantityInput || !sizeInput || !addButton) {
        return;
    }

    const quantity = parseInt(quantityInput.value) || 1;
    const size = sizeInput.value;
    const addUrl = addButton.dataset.addUrl;

    if (!size) {
        showNotification("Please select a size.");
        return;
    }

    if (!addUrl) {
        showNotification("Unable to add this product right now.");
        return;
    }

    addButton.disabled = true;
    addButton.textContent = "ADDING...";

    try {
        const response = await fetch(addUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-Requested-With": "XMLHttpRequest"
            },
            body: JSON.stringify({
                size: size,
                quantity: quantity
            })
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            showNotification(
                result.message || "Unable to add product."
            );
            return;
        }

        showNotification(result.message);

        // Reset the selected quantity after adding.
        quantityInput.value = 1;

        // Update the cart badge if it exists.
        const cartCount = document.getElementById("cartCount");

        if (cartCount) {
            cartCount.textContent = result.cartCount;
        }

    } catch (error) {
        console.error("Add to cart error:", error);
        showNotification("Something went wrong. Please try again.");

    } finally {
        addButton.disabled = false;
        addButton.textContent = "ADD TO CART 🛒";
    }
}


/* =====================================================
   CART NOTIFICATION
===================================================== */

function showNotification(message) {
    const notification = document.getElementById(
        "cartNotification"
    );

    if (!notification) {
        return;
    }

    notification.textContent = message;
    notification.classList.add("show");

    if (notification.hideTimer) {
        clearTimeout(notification.hideTimer);
    }

    notification.hideTimer = setTimeout(function () {
        notification.classList.remove("show");
    }, 3000);
}