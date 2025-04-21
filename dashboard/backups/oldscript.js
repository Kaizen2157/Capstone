// // Toggle dropdown menu visibility
// function toggleDropdown() {
//     document.getElementById("profileDropdown").classList.toggle("show");
// }

// // Close the dropdown menu if the user clicks outside of it
// window.onclick = function(event) {
//     if (!event.target.matches('.dropbtn')) {
//         const dropdowns = document.getElementsByClassName("dropdown-content");
//         for (let i = 0; i < dropdowns.length; i++) {
//             const openDropdown = dropdowns[i];
//             if (openDropdown.classList.contains('show')) {
//                 openDropdown.classList.remove('show');
//             }
//         }
//     }
// };

// // Toggle Dark/Light Mode
// function toggleDarkMode() {
//     const body = document.body;
//     const darkModeIcon = document.querySelector('.dark-icon');
//     const lightModeIcon = document.querySelector('.light-icon');
//     const modeText = document.getElementById("modeText");

//     // Check if modeText exists before trying to change its textContent
//     if (modeText) {
//         if (body.classList.toggle('dark-mode')) {
//             darkModeIcon.style.display = 'inline';
//             lightModeIcon.style.display = 'none';
//             modeText.textContent = "Light Mode"; // Change text to Light Mode
//         } else {
//             darkModeIcon.style.display = 'none';
//             lightModeIcon.style.display = 'inline';
//             modeText.textContent = "Dark Mode"; // Change text to Dark Mode
//         }
//     } else {
//         console.error('modeText element not found');
//     }
// }

document.addEventListener('DOMContentLoaded', () => {
    const usernameDisplay = document.getElementById('username-display');
    const dropdownMenu = document.getElementById('dropdown-menu');

    // Get username from sessionStorage or localStorage
    const username = sessionStorage.getItem('username') || localStorage.getItem('username') || 'User';

    // Display username
    usernameDisplay.textContent = username;

    // Toggle dropdown on click
    usernameDisplay.addEventListener('click', () => {
        dropdownMenu.classList.toggle('show');
    });

    // Optional: hide dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.user-dropdown')) {
            dropdownMenu.classList.remove('show');
        }
    });
});


// Logout function
function confirmLogout() {
    window.location.href = '../frontend/home/index.html'; // Redirect to the logout page
}

// Constants
const SLOT_COST = 40;
const selectedSlots = new Set();



// Select all clickable slot elements
const slotElements = document.querySelectorAll('.bookslot .slots .slotone > p');

slotElements.forEach(slot => {
    // Check if slot is not occupied
    if (!slot.classList.contains('occupied')) {
        slot.classList.add('available');

        slot.addEventListener('click', () => {
            const slotNumber = slot.textContent;

            // Toggle selection
            if (selectedSlots.has(slotNumber)) {
                selectedSlots.delete(slotNumber);
                slot.classList.remove('selected');
                slot.classList.add('available');
            } else {
                selectedSlots.add(slotNumber);
                slot.classList.add('selected');
                slot.classList.remove('available');
            }

            updateBookingDetails();
        });
    }
});

// Allow only letters for first and last name
document.getElementById('first-name').addEventListener('input', function () {
    this.value = this.value.replace(/[^A-Za-z\s]/g, '');
});
document.getElementById('last-name').addEventListener('input', function () {
    this.value = this.value.replace(/[^A-Za-z\s]/g, '');
});



// Update the booking info display
function updateBookingDetails() {
    const details = document.getElementById('selected-slots');
    const total = document.getElementById('total-cost');
    const firstName = document.getElementById('first-name').value;
    const lastName = document.getElementById('last-name').value;
    const contactNumber = document.getElementById('contact-number').value;
    
    const selectedArray = Array.from(selectedSlots);

    details.textContent = selectedArray.length > 0 
        ? `Selected Slot(s): ${selectedArray.join(', ')}`
        : 'No Selected Slot';

    total.textContent = selectedArray.length > 0 
        ? `Total Cost: ₱${selectedArray.length * SLOT_COST}` 
        : '';

    // Update user info in the booking details
    const userInfo = document.getElementById('user-info');
    if (firstName && lastName && contactNumber) {
        userInfo.textContent = `Name: ${firstName} ${lastName} | Contact: ${contactNumber}`;
    } else {
        userInfo.textContent = 'Please fill in your name and contact details';
    }
}

// ⬇️ ADDED: Proceed button logic with user info fields validation
document.getElementById('proceed-btn').addEventListener('click', () => {
    const startTime = document.getElementById('start-time').value;
    const endTime = document.getElementById('end-time').value;
    const firstName = document.getElementById('first-name').value;
    const lastName = document.getElementById('last-name').value;
    const contactNumber = document.getElementById('contact-number').value;

    if (selectedSlots.size === 0) {
        alert("Please select at least one slot to proceed.");
        return;
    }

    if (!startTime || !endTime) {
        alert('Please select both start and end time before proceeding.');
        return;
    }

    // Validate user info
    if (!firstName || !lastName || !contactNumber) {
        alert("Please enter your first name, last name, and contact number.");
        return;
    }

    // const selected = Array.from(selectedSlots).join(',');
    // const cost = selectedSlots.size * SLOT_COST;

    // // Store in sessionStorage so we can pass to next page
    // sessionStorage.setItem('slots', selected);
    // sessionStorage.setItem('startTime', startTime);
    // sessionStorage.setItem('endTime', endTime);
    // sessionStorage.setItem('cost', cost);
    // sessionStorage.setItem('firstName', firstName);
    // sessionStorage.setItem('lastName', lastName);
    // sessionStorage.setItem('contactNumber', contactNumber);

    // // Redirect to the e-receipt page
    // // window.location.href = 'e-receipt.html';
});




document.addEventListener("DOMContentLoaded", function () {
    const slots = document.querySelectorAll(".slot.available");
    const selectedSlots = [];

    slots.forEach(slot => {
        slot.addEventListener("click", () => {
            const slotNumber = slot.textContent;

            // If already selected, unselect it
            if (slot.classList.contains("selected")) {
                slot.classList.remove("selected");
                selectedSlots.splice(selectedSlots.indexOf(slotNumber), 1);
            } else {
                slot.classList.add("selected");
                selectedSlots.push(slotNumber);
            }

        });
    });
});

const slots = document.querySelectorAll('.slot.available');

slots.forEach(slot => {
    slot.addEventListener('click', () => {
        slot.classList.toggle('selected');
    });
});


document.getElementById('proceed-btn').addEventListener('click', function () {
    const firstName = document.getElementById('first-name').value.trim();
    const lastName = document.getElementById('last-name').value.trim();
    const contact = document.getElementById('contact-number').value.trim();
    const bookingDate = document.getElementById("booking-date").value;
const startTime = document.getElementById("start-time").value;
const endTime = document.getElementById("end-time").value;

document.getElementById("receipt-time").textContent =
    `Date: ${bookingDate} | Time: ${startTime} - ${endTime}`;


    const selectedSlotElements = document.querySelectorAll('.slot.selected');
    const selectedSlots = Array.from(selectedSlotElements).map(slot => slot.textContent).join(', ');
    

    const receiptBox = document.getElementById('receipt-popup');

    if (firstName && lastName && contact && startTime && endTime && selectedSlots) {
        document.getElementById('receipt-user').textContent = `Name: ${firstName} ${lastName}`;
        document.getElementById('receipt-contact').textContent = `Contact: ${contact}`;
        document.getElementById('receipt-slot').textContent = `Slot(s): ${selectedSlots}`;
        function formatTime12hr(timeStr) {
            const [hour, minute] = timeStr.split(':');
            const h = parseInt(hour);
            const ampm = h >= 12 ? 'PM' : 'AM';
            const hour12 = h % 12 === 0 ? 12 : h % 12;
            return `${hour12}:${minute} ${ampm}`;
        }
        
        const formattedStart = formatTime12hr(startTime);
        const formattedEnd = formatTime12hr(endTime);
        
        document.getElementById('receipt-time').textContent = `Time: ${formattedStart} - ${formattedEnd}`;
        
        document.getElementById('proceed-btn').addEventListener('click', () => {
            const date = document.getElementById("booking-date").value;
            const startTime = document.getElementById("start-time").value;
            const endTime = document.getElementById("end-time").value;
            const fullTime = `${startTime} - ${endTime}`;

            document.getElementById("receipt-date").textContent = "Date: " + date;
            document.getElementById("receipt-time").textContent = "Time: " + fullTime;
        
            // Validate the input times
            if (!startTime || !endTime) {
                alert("Please enter both start and end times.");
                return;
            }
        
            // Create date objects
            const start = new Date(`1970-01-01T${startTime}:00`);
            const end = new Date(`1970-01-01T${endTime}:00`);
        
            // Check if end time is earlier than start time, assume next day if so
            if (end <= start) {
                end.setDate(end.getDate() + 1); // move end to the next day
            }
        
            let diffMs = end - start;
            let diffHrs = Math.ceil(diffMs / (1000 * 60 * 60)); // round up to nearest hour
        
            // If the time difference is zero or negative, show an error
            if (diffHrs <= 0) {
                alert("End time must be later than start time.");
                return;
            }
        
            const costPerHour = 40;
            const totalCost = diffHrs * costPerHour;
        
            // Display the total cost in the receipt
            document.getElementById('receipt-total').textContent = `Total Cost: ₱${totalCost}`;
        
            // Show the receipt
            receiptBox.style.display = 'flex';
        });
    
    }
});

function closeReceipt() {
    document.getElementById('receipt-popup').style.display = 'none';
}
