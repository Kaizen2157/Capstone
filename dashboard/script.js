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

        function updateEndDateTime() {
            const bookingDate = document.getElementById("booking-date").value;
            const startTime = document.getElementById("start-time").value;
            const durationHours = parseInt(document.getElementById("duration").value, 10);
        
            if (!bookingDate || !startTime || isNaN(durationHours)) return;
        
            const startDateTime = new Date(`${bookingDate}T${startTime}`);
            const endDateTime = new Date(startDateTime.getTime() + durationHours * 60 * 60 * 1000);
        
            const endDate = endDateTime.toISOString().slice(0, 10);
            const endHours = String(endDateTime.getHours()).padStart(2, '0');
            const endMinutes = String(endDateTime.getMinutes()).padStart(2, '0');
            const endTime = `${endHours}:${endMinutes}`;
        
            // Optional: update UI if you have elements showing this
            const endDateInput = document.getElementById("end-date");
            const endTimeInput = document.getElementById("end-time");
        
            if (endDateInput) endDateInput.value = endDate;
            if (endTimeInput) endTimeInput.value = endTime;
        }

        document.getElementById("booking-date").addEventListener("change", updateEndDateTime);
        document.getElementById("start-time").addEventListener("change", updateEndDateTime);
        document.getElementById("duration").addEventListener("change", updateEndDateTime);

        
    
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
        const startDateObj = new Date(`${bookingDate}T${startTime}`);
        const endDateObj = new Date(startDateObj.getTime() + durationHours * 60 * 60 * 1000); // Add hours in ms

        // Format end time and date (local format)
        const endDate = endDateObj.toISOString().slice(0, 10); // YYYY-MM-DD
        let endTime = endDateObj.toTimeString().slice(0, 5); // HH:MM

    
        const endHours = String(endDateObj.getHours()).padStart(2, '0');
        const endMinutes = String(endDateObj.getMinutes()).padStart(2, '0');
        endTime = `${endHours}:${endMinutes}`; // ✅ Now it's reassigned, not redeclared
    
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
        document.getElementById('receipt-end-date').textContent = `End Date & Time: ${endDate} ${endTime}`;
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
        formData.append('end_date', endDate);
        formData.append('end_time', endTime);
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
                    <td>${item.price}</td>
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
        <td>₱${reservation.total_cost}</td>
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

document.addEventListener('DOMContentLoaded', function () {
    fetch('check-reservation.php')
        .then(res => res.json())
        .then(data => {
            const section = document.getElementById('reservation-section');
            const messageBox = document.getElementById('message-box'); // A container for success/error messages

            if (data.hasReservation && data.reservation) {
                const r = data.reservation;

                // Combine and format the dates
                const startDateTime = new Date(`${r.start_date}T${r.start_time}`);
                const endDateTime = new Date(`${r.start_date}T${r.end_time}`);

                // If end time is past midnight, assume end date is next day
                if (endDateTime <= startDateTime) {
                    endDateTime.setDate(endDateTime.getDate() + 1);
                }

                const formatOptions = {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                };

                const formattedStart = startDateTime.toLocaleString('en-US', formatOptions);
                const formattedEnd = endDateTime.toLocaleString('en-US', formatOptions);

                // Check if the reservation time has already passed
                const currentTime = new Date();

                // Hide cancel button if reservation time has passed
                const isPastReservation = currentTime >= endDateTime;

                section.innerHTML = `
                    <div class="reservation-info">
                        <h3 class="activereservation">Active Reservation</h3>
                        <p><strong>Slot:</strong> ${r.slot_number}</p>
                        <p><strong>Start Date & Time:</strong> ${formattedStart}</p>
                        <!--<p><strong>End Date & Time:</strong> ${formattedEnd}</p>-->
                        <p><strong>Total Cost:</strong> PHP ${parseFloat(r.total_cost).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</p>
                        <!--<p><strong>Status:</strong> ${r.status}</p>-->
                        ${
                            isPastReservation ? 
                            `<p class="text-danger">This reservation has already ended.</p>` : 
                            `<button id="cancel-reservation-btn" class="btn btn-danger">Cancel Reservation</button>`
                        }
                    </div>
                `;

                if (!isPastReservation) {
                    document.getElementById('cancel-reservation-btn').addEventListener('click', function () {
                        // Show the modal when the cancel button is clicked
                        const cancelModal = new bootstrap.Modal(document.getElementById('cancelConfirmationModal'));
                        cancelModal.show();

                        // Add event listener for the actual cancellation inside the modal
                        document.getElementById('confirm-cancel-btn').addEventListener('click', function () {
                            fetch('cancel-reservation.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: 'cancel=true'
                            })
                            .then(response => response.json())
                            .then(result => {
                                // Show success or error message
                                if (result.success) {
                                    messageBox.innerHTML = `<div class="alert alert-success">Reservation successfully canceled.</div>`;
                                } else {
                                    messageBox.innerHTML = `<div class="alert alert-danger">An error occurred while canceling your reservation.</div>`;
                                }

                                // Hide the message after a few seconds
                                setTimeout(() => {
                                    messageBox.innerHTML = '';
                                }, 5000); // 5 seconds

                                // Reload the page if successful
                                if (result.success) {
                                    setTimeout(() => {
                                        window.location.reload();
                                    }, 1000); // 1 second delay
                                }
                            });

                            // Close the modal after confirmation
                            cancelModal.hide();
                        });
                    });
                }
            } else {
                section.innerHTML = '<p class="activealert">No active reservation found.</p>';
            }
        });
});



// === Fetch Active Reservation === (perfect calculation of time and date for active reservation but not used in the dashboard)
// Fetch active reservation data and display it in the dashboard
// fetch('get-active-reservation.php')
//   .then(response => response.json())
//   .then(data => {
//     const container = document.getElementById("activeReservationContainer");

//     if (!data) {
//       container.innerHTML = "<p>No active reservation found.</p>";
//       return;
//     }

//     const start = new Date(data.start_time);
//     const end = new Date(data.end_time);

//     const dateOptions = { year: 'numeric', month: 'short', day: 'numeric' };
//     const timeOptions = { hour: 'numeric', minute: '2-digit', hour12: true };

//     const formattedStart = `${start.toLocaleDateString(undefined, dateOptions)}, ${start.toLocaleTimeString(undefined, timeOptions)}`;
//     const formattedEnd = `${end.toLocaleDateString(undefined, dateOptions)}, ${end.toLocaleTimeString(undefined, timeOptions)}`;

//     container.innerHTML = `
//       <div class="reservation-card">
//         <p><strong>Slot:</strong> ${data.slot_number}</p>
//         <p><strong>Start:</strong> ${formattedStart}</p>
//         <p><strong>End:</strong> ${formattedEnd}</p>
//         <p><strong>Total Cost:</strong> ₱${data.total_cost}</p>
//       </div>
//     `;
//   })
//   .catch(error => {
//     console.error("Error fetching active reservation:", error);
//     document.getElementById("activeReservationContainer").innerHTML = "<p>Error loading active reservation.</p>";
//   });



