document.addEventListener('DOMContentLoaded', () => {
    let SLOT_COST = 0; // Initialize cost variable
    let selectedSlot = null; // Store only one selected slot
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

    // Function to fetch and display balance
// Update balance display
fetch('get-balance.php')
    .then(response => response.json())
    .then(data => {
        // Format and show balance
        const formattedBalance = Number(data.balance).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        
        // Change this to balance-display (from balance)
        document.getElementById('balance-display').innerText = '₱' + formattedBalance;

        // Update transaction history
        const transactionList = document.getElementById('transaction-history');
        transactionList.innerHTML = '';
        data.transactions.forEach(transaction => {
            const formattedAmount = Number(transaction.amount).toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            const li = document.createElement('li');
            li.innerText = `${transaction.amount > 0 ? '-' : ''}₱${formattedAmount} - ${transaction.description} on ${transaction.created_at}`;
            transactionList.appendChild(li);
        });
    })
    .catch(error => console.error('Error fetching balance:', error));


    document.addEventListener('DOMContentLoaded', function () {
        fetchBalance();
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

    // Fetch slot cost from the server (assuming you have an endpoint to return the cost)
    fetch('home-dashboard.php?get_cost=true')
        .then(response => response.json())
        .then(data => {
            SLOT_COST = data.cost || 40; // Default to 40 if the fetch fails
        })
        .catch(err => {
            console.error('Failed to fetch slot cost:', err);
            SLOT_COST = 40; // Default to 40 if the fetch fails
        });

    // Handle slot selection
    const slotElements = document.querySelectorAll('.slots .slotone > p');
    slotElements.forEach(slot => {
        if (!slot.classList.contains('occupied')) {
            slot.classList.add('available');
            slot.addEventListener('click', () => {
                const slotNumber = slot.textContent;

                // Deselect previously selected slot if any
                if (selectedSlot !== null) {
                    const previousSlot = document.querySelector(`.slots .slotone > p.selected`);
                    if (previousSlot) {
                        previousSlot.classList.remove('selected');
                        previousSlot.classList.add('available');
                    }
                }

                // Select the clicked slot
                if (selectedSlot === slotNumber) {
                    selectedSlot = null; // Deselect if the same slot is clicked
                    slot.classList.remove('selected');
                    slot.classList.add('available');
                } else {
                    selectedSlot = slotNumber; // Update the selected slot
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

        details.textContent = selectedSlot ? `Selected Slot: ${selectedSlot}` : 'No Selected Slot';
        total.textContent = selectedSlot ? `Total Cost: ₱${SLOT_COST}` : '';

        const userInfo = document.getElementById('user-info');
        userInfo.textContent = (firstName && lastName && contactNumber) ? `Name: ${firstName} ${lastName} | Contact: ${contactNumber}` : 'Please fill in your name and contact details';
    }

    // Handle Proceed Button Click for E-Receipt
    document.getElementById('proceed-btn').addEventListener('click', function (e) {
        e.preventDefault();

        const firstName = document.getElementById('first-name').value.trim();
        const lastName = document.getElementById('last-name').value.trim();
        const contact = document.getElementById('contact-number').value.trim();
        const plateNumber = document.getElementById('car-plate').value.trim();
        const bookingDate = document.getElementById("booking-date").value;
        const startTime = document.getElementById("start-time").value;
        const durationHours = parseInt(document.getElementById("duration").value, 10);
        const selectedArray = selectedSlot ? [selectedSlot] : []; // Convert the selected slot to an array
    
        // Validation
        if (!firstName || !lastName || !contact || selectedArray.length === 0 || !bookingDate || !startTime || !durationHours) {
            alert('Please fill in all required fields and select at least one slot.');
            return;
        }
    
        const nameRegex = /^[a-zA-Z\s\-]+$/;
        if (!nameRegex.test(firstName) || !nameRegex.test(lastName)) {
            alert('Name can only contain letters, spaces, and hyphens.');
            return;
        }
    
        // Calculate End Time
        const [startHour, startMinute] = startTime.split(':').map(Number);
        const endDateObj = new Date(`${bookingDate}T${startTime}`);
        endDateObj.setHours(endDateObj.getHours() + durationHours);
    
        const endHours = String(endDateObj.getHours()).padStart(2, '0');
        const endMinutes = String(endDateObj.getMinutes()).padStart(2, '0');
        const endTime = `${endHours}:${endMinutes}`;
    
        // Calculate Total Cost
        const ratePerHour = SLOT_COST; // Dynamic cost fetched from the server
        const totalCost = selectedArray.length * ratePerHour * durationHours;
    
        // Update hidden inputs
        document.getElementById('hidden-selected-slot').value = selectedArray.join(', ');
        document.getElementById('hidden-total-cost').value = totalCost.toFixed(2);
    
        // Update Receipt
        document.getElementById('receipt-user').textContent = `Name: ${firstName} ${lastName}`;
        document.getElementById('receipt-contact').textContent = `Contact: ${contact}`;
        document.getElementById('receipt-plate').textContent = `Plate Number: ${plateNumber}`;
        document.getElementById('receipt-slot').textContent = `Slot: ${selectedArray.join(', ')}`;
        document.getElementById('receipt-date').textContent = `Start Date & Time: ${bookingDate} ${startTime}`;
        document.getElementById('receipt-end-date').textContent = `End Time: ${endTime}`;
        document.getElementById('receipt-total').textContent = `Total Cost: ₱${totalCost}`;
    
        // Show Receipt Popup
        document.getElementById('receipt-popup').style.display = 'flex';

        // Make AJAX Request to save booking details
        const formData = new FormData();
        formData.append('first_name', firstName);
        formData.append('last_name', lastName);
        formData.append('contact_number', contact);
        formData.append('car_plate', plateNumber);
        formData.append('slot_number', selectedArray.join(', '));
        formData.append('start_date', bookingDate);
        formData.append('start_time', startTime);
        formData.append('duration', durationHours);
        formData.append('total_cost', totalCost);

        fetch('home-dashboard.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log('Booking successful:', data);
        })
        .catch(error => {
            console.error('Error:', error);
        });
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

// setTimeout(function() {
//     window.location.href = "../frontend/backups/login/login.html"; // Redirect to login page after 3 seconds
// }, 3000);

// // Fetch recent reservation data and populate the table
document.addEventListener("DOMContentLoaded", function () {
    fetch('get-recent-reservation.php')
        .then(response => response.json())
        .then(data => {
            const tableBody = document.querySelector('.reservation-history tbody');
            tableBody.innerHTML = ''; // clear existing content

            if (data.length === 0) {
                const row = document.createElement('tr');
                row.innerHTML = `<td colspan="3">No reservations yet.</td>`;
                tableBody.appendChild(row);
                return;
            }

            data.forEach(item => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${item.slot}</td>
                    <td>${item.start_time} - ${item.end_time}</td>
                    <td>${item.status}</td>
                `;
                tableBody.appendChild(row);
            });
        })
        .catch(error => {
            console.error('Error fetching reservations:', error);
        });
});
// // Fetch reservation history and populate the table


// // Call the function when the page is loaded
// document.addEventListener('DOMContentLoaded', fetchReservationHistory);

fetch('get-reservation-history.php')
.then(response => response.json())
.then(data => {
  const historyTableBody = document.querySelector(".reservation-history tbody");
  historyTableBody.innerHTML = "";

  data.forEach(reservation => {
    const start = new Date(reservation.start_time);
    const end = new Date(reservation.end_time);

    if (isNaN(start) || isNaN(end)) {
      console.error("Invalid date format", reservation.start_time, reservation.end_time);
      return;
    }

    const dateOptions = { year: 'numeric', month: 'short', day: 'numeric' };
    const timeOptions = { hour: 'numeric', minute: '2-digit', hour12: true };

    const startDateTimeStr = `${start.toLocaleDateString(undefined, dateOptions)}, ${start.toLocaleTimeString(undefined, timeOptions)}`;
const endDateTimeStr = `${end.toLocaleDateString(undefined, dateOptions)}, ${end.toLocaleTimeString(undefined, timeOptions)}`;

const row = `
  <tr>
    <td>${reservation.slot_number}</td>
    <td>${startDateTimeStr} – ${endDateTimeStr}</td>
    <td>${reservation.status ?? '—'}</td>
  </tr>
`;

    historyTableBody.innerHTML += row;
  });
})
.catch(err => {
  console.error("Error fetching reservation data", err);
});




// // Call the function to load data on page load
// window.onload = fetchBalance;

