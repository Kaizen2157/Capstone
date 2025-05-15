//logout button functionality
document.getElementById('logout-btn').addEventListener('click', function(e) {
    e.preventDefault(); // Stop normal link behavior

    // Show the loading modal
    document.getElementById('logout-modal').style.display = 'flex';

    // After 1.5 seconds, redirect to logout-admin.php
    setTimeout(function() {
        window.location.href = 'logout-admin.php'; // Redirect to logout page
    }, 1500); // 1500 ms = 1.5 seconds

    // Prevent using Back button after logout
    window.history.pushState(null, "", window.location.href);
    window.onpopstate = function () {
        window.location.href = "adminlog.html"; // Force redirect to login page
    };
});



function showToast(message) {
    const toast = document.getElementById('toast');
    toast.textContent = message; // Set the message
    toast.style.opacity = 1;
    toast.style.pointerEvents = 'auto';
  
    setTimeout(() => {
      toast.style.opacity = 0;
      toast.style.pointerEvents = 'none';
    }, 3000); // Hide after 3 seconds
}


document.querySelectorAll('.sidebar-nav a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelector(this.getAttribute('href')).scrollIntoView({
            behavior: 'smooth'
        });
    });
});