document.addEventListener('DOMContentLoaded', () => {
    const usernameDisplay = document.getElementById('username-display');
    const dropdownMenu = document.getElementById('dropdown-menu');
    const SLOT_COST = 40;
    const selectedSlots = new Set();

    // Show username from storage
    const username = sessionStorage.getItem('username') || localStorage.getItem('username') || 'User';
    usernameDisplay.textContent = username;
    // console.log("Username from storage:", username);
    
    // sessionStorage.clear();
    // localStorage.clear();
    // username = "<?php echo $_SESSION['first_name'] ?? 'User'; ?>";
    // sessionStorage.setItem('username', username);
    


    // Dropdown toggle
    usernameDisplay.addEventListener('click', () => dropdownMenu.classList.toggle('show'));
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.user-dropdown')) {
            dropdownMenu.classList.remove('show');
        }
    });

    // Fetch reserved slots
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
            inputField.style.borderColor = "red"; // Highlight in red if the input is empty
        } else {
            inputField.style.borderColor = ""; // Remove the red border if input is not empty
        }
    }

    // Slot click logic
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

    // Update booking summary
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

        const userInfo = document.getElementById('user-info');
        userInfo.textContent = (firstName && lastName && contactNumber)
            ? `Name: ${firstName} ${lastName} | Contact: ${contactNumber}`
            : 'Please fill in your name and contact details';
    }

    const proceedBtn = document.getElementById('proceed-btn');
if (proceedBtn) {
    proceedBtn.addEventListener('click', (e) => {
        e.preventDefault();

        const firstName = document.getElementById('first-name').value.trim();
        const lastName = document.getElementById('last-name').value.trim();
        const contact = document.getElementById('contact-number').value.trim();
        const bookingDate = document.getElementById("booking-date").value;
        const startTime = document.getElementById("start-time").value;
        const endTime = document.getElementById("end-time").value;
        const receiptBox = document.getElementById('receipt-popup');

        if (!firstName || !lastName || !contact || !startTime || !endTime || selectedSlots.size === 0) {
            alert("Please fill in all required details and select at least one slot.");
            return;
        }

        // Format slots and time
        const selectedArray = Array.from(selectedSlots);
        document.getElementById('receipt-user').textContent = `Name: ${firstName} ${lastName}`;
        document.getElementById('receipt-contact').textContent = `Contact: ${contact}`;
        document.getElementById('receipt-slot').textContent = `Slot(s): ${selectedArray.join(', ')}`;
        document.getElementById("receipt-date").textContent = "Date: " + bookingDate;

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

        // Time difference calculation
        const start = new Date(`1970-01-01T${startTime}:00`);
        const end = new Date(`1970-01-01T${endTime}:00`);
        if (end <= start) end.setDate(end.getDate() + 1);
        const hours = Math.ceil((end - start) / (1000 * 60 * 60));
        if (hours <= 0) return alert("End time must be later than start time.");

        const totalCost = hours * SLOT_COST;
        document.getElementById('receipt-total').textContent = `Total Cost: ₱${totalCost}`;

        // Show receipt
        receiptBox.style.display = 'flex';

        // Save booking details to the server (optional)
        const form = document.getElementById('booking-form');
        const formData = new FormData(form);
        formData.append('total_cost', totalCost); // Add total cost to form data
        formData.append('selected_slot', selectedArray.join(',')); // Add selected slots to form data

        fetch('home-dashboard.php', {
            method: 'POST',
            body: formData,
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Booking saved successfully');
                receiptBox.style.display = 'flex';
            } else {
                alert('Error saving booking: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('There was an error processing your booking.');
        });
    });
}

        
});

function closeReceipt() {
    document.getElementById('receipt-popup').style.display = 'none';
    window.location.href = 'dashboard.html';
}


function confirmLogout() {
    if (confirm("Are you sure you want to logout?")) {
        window.location.href = "logout.php";
    }
}