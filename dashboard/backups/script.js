fetch('/capstone/dashboard/get-user.php')
    .then(response => response.json())
    .then(data => {
      document.getElementById('username-display').textContent = data.name;
    });

    // Prevent back button after logging out
function preventBack(){window.history.forward();}
  setTimeout("preventBack()", 0);
  window.onunload=function(){null}


document.addEventListener('DOMContentLoaded', () => {
    const usernameDisplay = document.getElementById('username-display');
    const dropdownMenu = document.getElementById('dropdown-menu');
    const SLOT_COST = 40;
    const selectedSlots = new Set();

    // Show username from storage
    const username = sessionStorage.getItem('username') || localStorage.getItem('username') || 'User';
    usernameDisplay.textContent = username;

    // Dropdown toggle
    usernameDisplay.addEventListener('click', () => dropdownMenu.classList.toggle('show'));
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.user-dropdown')) {
            dropdownMenu.classList.remove('show');
        }
    });

    // // Logout redirect
    // window.confirmLogout = () => {
    //     window.location.href = '../frontend/home/index.html';
    // };

    // // Dark mode toggle
    // window.toggleDarkMode = () => {
    //     const body = document.body;
    //     const darkModeIcon = document.querySelector('.dark-icon');
    //     const lightModeIcon = document.querySelector('.light-icon');
    //     const modeText = document.getElementById("modeText");

    //     if (modeText) {
    //         if (body.classList.toggle('dark-mode')) {
    //             darkModeIcon.style.display = 'inline';
    //             lightModeIcon.style.display = 'none';
    //             modeText.textContent = "Light Mode";
    //         } else {
    //             darkModeIcon.style.display = 'none';
    //             lightModeIcon.style.display = 'inline';
    //             modeText.textContent = "Dark Mode";
    //         }
    //     }
    // };

    // Only letters for names
    document.getElementById('first-name').addEventListener('input', function () {
        this.value = this.value.replace(/[^A-Za-z\s]/g, '');
    });
    document.getElementById('last-name').addEventListener('input', function () {
        this.value = this.value.replace(/[^A-Za-z\s]/g, '');
    });

    // 🔄 Fetch reserved slots
fetch('get-reserved-slots.php?date=' + new Date().toISOString().split('T')[0])
.then(response => response.json())
.then(reservedSlots => {
  const slotElements = document.querySelectorAll('.bookslot .slots .slotone > p');

  slotElements.forEach(slot => {
    const slotNumber = slot.textContent;

    if (reservedSlots.includes(slotNumber)) {
      // Make slot occupied
      slot.classList.add('occupied');
      slot.classList.remove('available');
      slot.style.backgroundColor = '#899197'; // or your preferred color
    }
  });
});

 // // Slot click logic changing color when its being clicked or selected
    // document.querySelectorAll('.slot.available').forEach(slot => {
    //     slot.addEventListener('click', function() {
    //       if (!slot.classList.contains('occupied')) {
    //         slot.classList.toggle('selected');
    //         // Toggle green and gray by class
    //         if (slot.classList.contains('selected')) {
    //           slot.style.backgroundColor = '#899197';
    //         } else {
    //           slot.style.backgroundColor = '#7ad75d';
    //         }
    //       }
    //     });
    //   });


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

    // Receipt Logic
    document.getElementById('proceed-btn').addEventListener('click', (e) => {
        e.preventDefault(); // ✅ Correct spelling here
    

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
});

function closeReceipt() {
    document.getElementById('receipt-popup').style.display = 'none';
    // 👇 Redirect only after closing the receipt
    window.location.href = 'dashboard.html';
}
