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

// Sidebar toggle for mobile
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.sidebar');
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebarOverlay = document.createElement('div');
    sidebarOverlay.className = 'sidebar-overlay';
    document.body.appendChild(sidebarOverlay);

    // Toggle sidebar
    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('active');
        sidebarOverlay.classList.toggle('active');
    });

    // Close sidebar when clicking overlay
    sidebarOverlay.addEventListener('click', function() {
        sidebar.classList.remove('active');
        this.classList.remove('active');
    });

    // Adjust chart on resize
    window.addEventListener('resize', function() {
        if (analyticsChart) {
            analyticsChart.resize();
        }
    });

    // Responsive table handling
    makeTablesResponsive();
});

function makeTablesResponsive() {
    const tables = document.querySelectorAll('table');
    
    tables.forEach(table => {
        const wrapper = document.createElement('div');
        wrapper.className = 'table-responsive';
        table.parentNode.insertBefore(wrapper, table);
        wrapper.appendChild(table);
    });
}

// Handle window resize
let resizeTimer;
window.addEventListener('resize', function() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function() {
        // Update chart size if needed
        if (typeof analyticsChart !== 'undefined') {
            analyticsChart.resize();
        }
    }, 250);
});