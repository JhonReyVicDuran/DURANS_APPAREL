/* ==========================================
   LOGOUT MODAL
========================================== */

function openLogoutPopup(event) {
    event.preventDefault();

    const overlay = document.getElementById("logoutOverlay");

    if (overlay) {
        overlay.classList.add("active");
    }
}

function closeLogoutPopup() {
    const overlay = document.getElementById("logoutOverlay");

    if (overlay) {
        overlay.classList.remove("active");
    }
}

function confirmLogout() {
    window.location.href = "/logout";
}


/* ==========================================
   STAFF STATUS CONFIRMATION MODAL
========================================== */

let pendingStaffStatusForm = null;

function openStaffStatusModal(event, button) {
    event.preventDefault();

    const overlay = document.getElementById("staffStatusOverlay");
    const title = document.getElementById("staffStatusTitle");
    const message = document.getElementById("staffStatusMessage");
    const confirmButton = document.getElementById("staffStatusConfirm");

    if (!overlay || !title || !message || !confirmButton) {
        return;
    }

    pendingStaffStatusForm = button.closest("form");

    if (!pendingStaffStatusForm) {
        return;
    }

    const staffName = button.dataset.staffName;
    const action = button.dataset.action;

    if (action === "deactivate") {
        title.textContent = "Deactivate Staff Account?";
        message.textContent =
            "Are you sure you want to deactivate " +
            staffName +
            "? This will mark the account as inactive. You can activate it again later.";

        confirmButton.textContent = "DEACTIVATE";
    } else {
        title.textContent = "Activate Staff Account?";
        message.textContent =
            "Are you sure you want to activate " +
            staffName +
            "? This will restore the account's active status.";

        confirmButton.textContent = "ACTIVATE";
    }

    overlay.classList.add("active");
}

function closeStaffStatusModal() {
    const overlay = document.getElementById("staffStatusOverlay");

    if (overlay) {
        overlay.classList.remove("active");
    }

    pendingStaffStatusForm = null;
}

function confirmStaffStatusChange() {
    if (pendingStaffStatusForm) {
        pendingStaffStatusForm.submit();
    }
}


/* ==========================================
   CUSTOMER STATUS CONFIRMATION MODAL
========================================== */

let pendingCustomerStatusForm = null;

function openCustomerStatusModal(event, button) {
    event.preventDefault();

    const overlay = document.getElementById("customerStatusOverlay");
    const title = document.getElementById("customerStatusTitle");
    const message = document.getElementById("customerStatusMessage");
    const confirmButton = document.getElementById("customerStatusConfirm");

    if (!overlay || !title || !message || !confirmButton) {
        return;
    }

    pendingCustomerStatusForm = button.closest("form");

    if (!pendingCustomerStatusForm) {
        return;
    }

    const customerName = button.dataset.customerName;
    const action = button.dataset.action;

    if (action === "deactivate") {
        title.textContent = "Deactivate Customer Account?";
        message.textContent =
            "Are you sure you want to deactivate " +
            customerName +
            "? This will mark the account as inactive. You can activate it again later.";

        confirmButton.textContent = "DEACTIVATE";
    } else {
        title.textContent = "Activate Customer Account?";
        message.textContent =
            "Are you sure you want to activate " +
            customerName +
            "? This will restore the account's active status.";

        confirmButton.textContent = "ACTIVATE";
    }

    overlay.classList.add("active");
}

function closeCustomerStatusModal() {
    const overlay = document.getElementById("customerStatusOverlay");

    if (overlay) {
        overlay.classList.remove("active");
    }

    pendingCustomerStatusForm = null;
}

function confirmCustomerStatusChange() {
    if (pendingCustomerStatusForm) {
        pendingCustomerStatusForm.submit();
    }
}