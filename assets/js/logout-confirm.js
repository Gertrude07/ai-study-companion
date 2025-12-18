// Logout Confirmation Script
document.addEventListener('DOMContentLoaded', function () {
    // Add confirmation to all logout links
    const logoutLinks = document.querySelectorAll('a.logout, a[href*="logout.php"]');

    logoutLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();

            if (confirm('Are you sure you want to log out?')) {
                window.location.href = this.href;
            }
        });
    });
});
