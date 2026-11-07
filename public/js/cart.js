/* =====================================================
   SEND CART REQUEST
===================================================== */

async function sendCartRequest(url, data = {}) {
    const response = await fetch(url, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-Requested-With": "XMLHttpRequest"
        },
        body: JSON.stringify(data)
    });

    const result = await response.json();

    if (!response.ok || !result.success) {
        throw new Error(result.message || "Cart request failed.");
    }

    return result;
}


/* =====================================================
   CART NOTIFICATION
===================================================== */

function showCartNotification(message) {
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


/* =====================================================
   UPDATE ITEM QUANTITY
===================================================== */

document.querySelectorAll("[data-quantity-change]").forEach(button => {

    button.addEventListener("click", async function () {

        const itemKey = this.dataset.itemKey;
        const change = parseInt(this.dataset.quantityChange);

        const controls = this.closest(".cart-quantity-controls");
        const input = controls.querySelector("input");

        let newQuantity = parseInt(input.value) + change;

        const minimum = parseInt(input.min);
        const maximum = parseInt(input.max);

        if (newQuantity < minimum || newQuantity > maximum) {
            return;
        }

        controls.querySelectorAll("button").forEach(btn => {
            btn.disabled = true;
        });

        try {
            await sendCartRequest("/cart/update", {
                itemKey: itemKey,
                quantity: newQuantity
            });

            // Refresh totals and stock-related button states.
            window.location.reload();

        } catch (error) {
            showCartNotification(error.message);

            controls.querySelectorAll("button").forEach(btn => {
                btn.disabled = false;
            });
        }

    });

});


/* =====================================================
   REMOVE ITEM
===================================================== */

document.querySelectorAll("[data-remove-item]").forEach(button => {

    button.addEventListener("click", async function () {

        const itemKey = this.dataset.itemKey;

        if (!confirm("Are you sure you want to remove this product?")) {
            return;
        }

        this.disabled = true;

        try {
            await sendCartRequest("/cart/remove", {
                itemKey: itemKey
            });

            window.location.reload();

        } catch (error) {
            showCartNotification(error.message);
            this.disabled = false;
        }

    });

});


/* =====================================================
   CLEAR CART
===================================================== */

const clearCartButton = document.querySelector("[data-cart-clear]");

if (clearCartButton) {

    clearCartButton.addEventListener("click", async function () {

        if (!confirm("Are you sure you want to clear your entire cart?")) {
            return;
        }

        this.disabled = true;

        try {
            await sendCartRequest("/cart/clear");

            window.location.reload();

        } catch (error) {
            showCartNotification(error.message);
            this.disabled = false;
        }

    });

}


/* =====================================================
   CHECKOUT PLACEHOLDER
===================================================== */

const checkoutButton = document.querySelector("[data-checkout]");

if (checkoutButton) {

    checkoutButton.addEventListener("click", function () {

        showCartNotification(
            "Checkout is not available yet. The order system still needs to be connected."
        );

    });

}