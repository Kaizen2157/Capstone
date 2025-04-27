document.addEventListener('DOMContentLoaded', () => {
    const SLOT_COST = 40; // Cost per slot per hour
    const selectedSlots = new Set();
    const usernameDisplay = document.getElementById('username-display');
    const dropdownMenu = document.getElementById('dropdown-menu');
    

    // Fetch username logic (if applicable)
    const username = sessionStorage.getItem('username') || localStorage.getItem('username') || 'User';
    usernameDisplay.addEventListener('click', () => dropdownMenu.classList.toggle('show'));
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.user-dropdown')) {
            dropdownMenu.classList.remove('show');
        }
    });


    fetch('get-user.php')
        .then(response => response.json())
        .then(data => {
            usernameDisplay.textContent = data.name || 'User';
        })
        .catch(err => {
            console.error('Failed to fetch username:', err);
            usernameDisplay.textContent = 'User';
        });

    // Handle slot selection
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

    // Update booking details
    function updateBookingDetails() {
        const details = document.getElementById('selected-slots');
        const total = document.getElementById('total-cost');
        const firstName = document.getElementById('first-name').value;
        const lastName = document.getElementById('last-name').value;
        const contactNumber = document.getElementById('contact-number').value;

        details.textContent = selectedSlots.size > 0 ? `Selected Slot(s): ${[...selectedSlots].join(', ')}` : 'No Selected Slot';
        total.textContent = selectedSlots.size > 0 ? `Total Cost: ₱${selectedSlots.size * SLOT_COST}` : '';

        const userInfo = document.getElementById('user-info');
        userInfo.textContent = (firstName && lastName && contactNumber) ? `Name: ${firstName} ${lastName} | Contact: ${contactNumber}` : 'Please fill in your name and contact details';
    }

    // Handle Proceed Button Click for E-Receipt
    document.getElementById('proceed-btn').addEventListener('click', function (e) {
        e.preventDefault();
          
        const firstName = document.getElementById('first-name').value.trim();
        const lastName = document.getElementById('last-name').value.trim();
        const contact = document.getElementById('contact-number').value.trim();
        const bookingDate = document.getElementById("booking-date").value;
        const startTime = document.getElementById("start-time").value;
        const durationHours = parseInt(document.getElementById("duration").value, 10);
        const selectedArray = [...selectedSlots]; // Array of selected slots
    
        // FIX: calculate total cost based on number of slots * hours * rate
        const ratePerHour = SLOT_COST; // ₱40 for now
        const totalCost = selectedArray.length * ratePerHour * durationHours;
    
        // Calculate End Time
        const [startHour, startMinute] = startTime.split(':').map(Number);
        const endDateObj = new Date(`${bookingDate}T${startTime}`);
        endDateObj.setHours(endDateObj.getHours() + durationHours);
    
        const endHours = String(endDateObj.getHours()).padStart(2, '0');
        const endMinutes = String(endDateObj.getMinutes()).padStart(2, '0');
        const endTime = `${endHours}:${endMinutes}`;
    
        // Validation (same as before)
        if (!firstName || !lastName || !contact || selectedArray.length === 0 || !bookingDate || !startTime || !durationHours) {
            alert('Please fill in all required fields and select at least one slot.');
            return;
        }
    
        const nameRegex = /^[a-zA-Z\s\-]+$/;
        if (!nameRegex.test(firstName) || !nameRegex.test(lastName)) {
            alert('Name can only contain letters, spaces, and hyphens.');
            return;
        }
    
        const plateNumber = document.getElementById('car-plate').value.trim();
        document.getElementById('receipt-plate').textContent = `Plate Number: ${plateNumber}`;
        const endDate = new Date(`${bookingDate}T${endTime}`).toISOString().split('T')[0];
    
        document.getElementById('receipt-user').textContent = `Name: ${firstName} ${lastName}`;
        document.getElementById('receipt-contact').textContent = `Contact: ${contact}`;
        document.getElementById('receipt-slot').textContent = `Slot(s): ${selectedArray.join(', ')}`;
        document.getElementById('receipt-date').textContent = `Start Date & Time: ${bookingDate} ${startTime}`;
        document.getElementById('receipt-end-date').textContent = `End Time: ${endTime}`;
        document.getElementById('receipt-total').textContent = `Total Cost: ₱${totalCost}`;
    
        document.getElementById('receipt-popup').style.display = 'flex';
    });
});

function closeReceipt() {
    document.getElementById('receipt-popup').style.display = 'none';
    window.location.href = "dashboard.html"
}

function confirmLogout() {
    if (confirm("Are you sure you want to logout?")) {
        window.location.href = "logout.php";
    }
}