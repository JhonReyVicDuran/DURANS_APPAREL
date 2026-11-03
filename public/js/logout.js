function openLogoutPopup(event) {
    event.preventDefault();

    document
        .getElementById("logoutOverlay")
        .classList.add("active");
}

function closeLogoutPopup() {
    document
        .getElementById("logoutOverlay")
        .classList.remove("active");
}

function confirmLogout() {
    window.location.href = "/logout";
}