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