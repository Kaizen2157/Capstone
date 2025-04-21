// Toggle dropdown menu visibility
function toggleDropdown() {
    document.getElementById("profileDropdown").classList.toggle("show");
}

// Close the dropdown menu if the user clicks outside of it
window.onclick = function(event) {
    if (!event.target.matches('.dropbtn')) {
        const dropdowns = document.getElementsByClassName("dropdown-content");
        for (let i = 0; i < dropdowns.length; i++) {
            const openDropdown = dropdowns[i];
            if (openDropdown.classList.contains('show')) {
                openDropdown.classList.remove('show');
            }
        }
    }
};

// Toggle Dark/Light Mode
function toggleDarkMode() {
    const body = document.body;
    const darkModeIcon = document.querySelector('.dark-icon');
    const lightModeIcon = document.querySelector('.light-icon');
    const modeText = document.getElementById("modeText");

    // Toggle dark mode class on body
    if (body.classList.toggle('dark-mode')) {
        darkModeIcon.style.display = 'inline';
        lightModeIcon.style.display = 'none';
        modeText.textContent = "Light Mode"; // Change text to Light Mode
    } else {
        darkModeIcon.style.display = 'none';
        lightModeIcon.style.display = 'inline';
        modeText.textContent = "Dark Mode"; // Change text to Dark Mode
    }
}

function confirmLogout() {
    window.location.href = '../frontend/home/index.html'; // Redirect to the logout page
}
