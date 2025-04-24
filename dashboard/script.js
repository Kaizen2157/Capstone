document.addEventListener('DOMContentLoaded', () => {
    const usernameDisplay = document.getElementById('username-display');
    const dropdownMenu = document.getElementById('dropdown-menu');
    const SLOT_COST = 40;  // Cost per slot per hour
    const selectedSlots = new Set();

    const username = sessionStorage.getItem('username') || localStorage.getItem('username') || 'User';
    fetch('get-user.php')
    .then(response => response.json())
    .then(data => {
        usernameDisplay.textContent = data.name || 'User';
    })
    .catch(err => {
        console.error('Failed to fetch username:', err);
        usernameDisplay.textContent = 'User';
    });

    usernameDisplay.addEventListener('click', () => dropdownMenu.classList.toggle('show'));
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.user-dropdown')) {
            dropdownMenu.classList.remove('show');
        }
    });


// // Real-time car plate validation
// document.getElementById('car-plate').addEventListener('input', function () {
//     const value = this.value.trim().toUpperCase();
//     const pattern = /^[A-Z]{3} ?[0-9]{3}$/;
//     this.value = value;

//     let warning = document.getElementById('car-plate-warning');
//     if (!pattern.test(value)) {
//         if (!warning) {
//             warning = document.createElement('p');
//             warning.id = 'car-plate-warning';
//             warning.textContent = 'Format: ABC 123';
//             warning.style.color = 'red';
//             warning.style.fontSize = '0.8rem';
//             warning.style.display = 'block';
//             this.insertAdjacentElement('afterend', warning); // 👈 insert warning below input
//         }
//     } else {
//         if (warning) warning.remove();
//     }
// });

// // Real-time contact number validation
// document.getElementById('contact-number').addEventListener('input', function () {
//     const value = this.value.trim();
//     let warning = document.getElementById('contact-warning');

//     if (value.length !== 11) {
//         if (!warning) {
//             warning = document.createElement('p');
//             warning.id = 'contact-warning';
//             warning.textContent = 'Must be 11 digits';
//             warning.style.color = 'red';
//             warning.style.fontSize = '0.8rem';
//             warning.style.display = 'block';
//             this.insertAdjacentElement('afterend', warning); // 👈 insert below input
//         }
//     } else {
//         if (warning) warning.remove();
//     }
// });


// Real-time start/end date-time validation
['start-time', 'end-time', 'booking-date', 'end-date'].forEach(id => {
    const el = document.getElementById(id);
    if (el) {
        el.addEventListener('change', function () {
            const startDate = document.getElementById('booking-date').value;
            const startTime = document.getElementById('start-time').value;
            const endDate = document.getElementById('end-date').value;
            const endTime = document.getElementById('end-time').value;
            const warningId = 'date-warning';

            const start = new Date(`${startDate}T${startTime}`);
            const end = new Date(`${endDate}T${endTime}`);
            const existingWarn = document.getElementById(warningId);

            if (start && end && end <= start) {
                if (!existingWarn) {
                    const warn = document.createElement('p');
                    warn.id = warningId;
                    warn.textContent = 'End time must be after start time.';
                    warn.style.color = 'red';
                    document.querySelector('.time').appendChild(warn);
                }
            } else {
                if (existingWarn) existingWarn.remove();
            }
        });
    }
});




    fetch('get-reserved-slots.php?date=' + new Date().toISOString().split('T')[0])
    .then(response => response.json())
    .then(reservedSlots => {
        const slotElements = document.querySelectorAll('.bookslot .slots .slotone > p');

        slotElements.forEach(slot => {
            const slotNumber = slot.textContent;
            if (reservedSlots.includes(slotNumber)) {
                slot.classList.add('occupied');
                slot.classList.remove('available');
                slot.style.backgroundColor = '#899197';
            }
        });
    });

    function validateNameInput(inputField) {
        const value = inputField.value.trim();
        if (value === "") {
            inputField.style.borderColor = "red";
        } else {
            inputField.style.borderColor = "";
        }
    }

    const slotElements = document.querySelectorAll('.slots .slotone > p');
    slotElements.forEach(slot => {
        if (!slot.classList.contains('occupied')) {
            slot.classList.add('available');
            slot.addEventListener('click', () => {
                const slotNumber = slot.textContent;
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

    function updateBookingDetails() {
        const details = document.getElementById('selected-slots');
        const total = document.getElementById('total-cost');
        const firstName = document.getElementById('first-name').value;
        const lastName = document.getElementById('last-name').value;
        const contactNumber = document.getElementById('contact-number').value;
        const selectedArray = Array.from(selectedSlots);
        const userInfo = document.getElementById('user-info');

        details.textContent = selectedArray.length > 0
            ? `Selected Slot(s): ${selectedArray.join(', ')}`
            : 'No Selected Slot';

        total.textContent = selectedArray.length > 0
            ? `Total Cost: ₱${selectedArray.length * SLOT_COST}`
            : '';

        userInfo.textContent = (firstName && lastName && contactNumber)
            ? `Name: ${firstName} ${lastName} | Contact: ${contactNumber}`
            : 'Please fill in your name and contact details';
    }

    document.getElementById('proceed-btn').addEventListener('click', function(e) {
        e.preventDefault();
    
        const firstName = document.getElementById('first-name').value.trim();
        const lastName = document.getElementById('last-name').value.trim();
        const contact = document.getElementById('contact-number').value.trim();
        const bookingDate = document.getElementById("booking-date").value;
        const startTime = document.getElementById("start-time").value;
        const endTime = document.getElementById("end-time").value;
        const selectedArray = Array.from(selectedSlots);
        const totalCost = selectedArray.length * SLOT_COST;
    
        if (!firstName || !lastName || !contact || selectedArray.length === 0 || !bookingDate || !startTime || !endTime) {
            alert('Please fill in all required fields and select at least one slot.');
            return;
        }

        const nameRegex = /^[a-zA-Z\s\-]+$/;
        if (!nameRegex.test(firstName) || !nameRegex.test(lastName)) {
            alert('Name can only contain letters, spaces, and hyphens. No numbers or symbols allowed.');
            return;
        }

        
    
        // Time logic starts here
        const now = new Date();
        const start = new Date(`${bookingDate}T${startTime}`);
        const end = new Date(`${bookingDate}T${endTime}`);
    
        // If end time is before start, assume it's next day
        if (end <= start) end.setDate(end.getDate() + 1);
    
        const timeUntilStart = (start - now) / (1000 * 60); // minutes
        const duration = (end - start) / (1000 * 60); // minutes
    
        if (timeUntilStart < 30) {
            alert("Start time must be at least 30 minutes from now.");
            return;
        }
    
        if (duration < 30) {
            alert("End time must be at least 30 minutes after start time.");
            return;
        }

        const plateNumber = document.getElementById('car-plate').value.trim();
        document.getElementById('receipt-plate').textContent = `Plate Number: ${plateNumber}`;
        
        const endDate = end.toISOString().split('T')[0]; // formats YYYY-MM-DD
        document.getElementById('receipt-end-date').textContent = `End Date: ${endDate}`;
    
        // Set hidden input values for form submission
        document.getElementById('hidden-selected-slot').value = selectedArray.join(', ');
        document.getElementById('hidden-total-cost').value = totalCost;
    
        // Fill in receipt popup content
        document.getElementById('receipt-user').textContent = `Name: ${firstName} ${lastName}`;
        document.getElementById('receipt-contact').textContent = `Contact: ${contact}`;
        document.getElementById('receipt-plate').textContent = `Plate Number: ${plateNumber}`;
        document.getElementById('receipt-slot').textContent = `Slot(s): ${selectedArray.join(', ')}`;
        document.getElementById("receipt-date").textContent = "Start Date: " + bookingDate;
        document.getElementById("receipt-end-date").textContent = `End Date: ${endDate}`;

    
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
    
        const hours = Math.ceil(duration / 60);
        const finalCost = selectedArray.length * SLOT_COST * hours;
        document.getElementById('receipt-total').textContent = `Total Cost: ₱${finalCost}`;
    
        // Show receipt popup
        document.getElementById('receipt-popup').style.display = 'flex';
    });
});

function closeReceipt() {
    document.getElementById('receipt-popup').style.display = 'none';
    window.location.href = "dashboard.html";
}

function confirmLogout() {
    if (confirm("Are you sure you want to logout?")) {
        window.location.href = "logout.php";
    }
}
