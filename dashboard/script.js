let activeReservation = null; // global variable to store reservation data

document.addEventListener('DOMContentLoaded', () => {
    let SLOT_COST = 0; // Initialize cost variable
    let selectedSlot = null; // Store only one selected slot

    const usernameDisplay = document.getElementById('username-display');
    const dropdownMenu = document.getElementById('dropdown-menu');

    const bookingDateInput = document.getElementById("booking-date");
    const today = new Date();
    const tomorrow = new Date();
    const dayAfterTomorrow = new Date();

    const startNowBtn = document.getElementById('start-now-btn');
    if (startNowBtn) {
        startNowBtn.addEventListener('click', function () {
        if (!activeReservation) {
            alert('No active reservation found.');
            return;
        }

        if (!activeReservation || !activeReservation.id) {
            alert('No active reservation found.');
            return;
        }
        const reservationId = activeReservation.id;
        

        fetch('start-reservation-now.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ reservationId: reservationId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Reservation started immediately');
                window.location.reload(); // optionally refresh to show new time
            } else {
                alert('Failed to start reservation');
            }
        })
        .catch(error => {
            console.error('Error starting reservation:', error);
        });
    });
}
    
    
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
fetch('check-active-reservation.php')
    .then(response => response.json())
    .then(data => {
        const hasActive = data.hasActiveReservation; 
        // const hasActive = data.reservation !== null;
        const slotElements = document.querySelectorAll('.slots .slotone > p');

        slotElements.forEach(slot => {
            if (!slot.classList.contains('occupied')) {
                if (hasActive) {
                    // Only disable the slots if the user has an active reservation
                    slot.classList.add('disabled');
                    slot.style.cursor = "not-allowed";
                } else {
                    // Allow click if there's no active reservation
                    slot.classList.add('available');
                    slot.addEventListener('click', () => {
                        const slotNumber = slot.textContent;

                        const previouslySelected = document.querySelector('.slots .slotone > p.selected');
                        if (previouslySelected) {
                            previouslySelected.classList.remove('selected');
                            previouslySelected.classList.add('available');
                        }

                        if (selectedSlot === slotNumber) {
                            selectedSlot = null;
                            slot.classList.remove('selected');
                            slot.classList.add('available');
                        } else {
                            selectedSlot = slotNumber;
                            slot.classList.add('selected');
                            slot.classList.remove('available');
                        }

                        updateBookingDetails();  // Update the booking details (make sure this function exists)
                    });
                }
            }
        });

        if (hasActive) {
            const msg = document.createElement('div');
            msg.textContent = "You already have an active reservation. You can only reserve one slot at a time.";
            msg.style.color = "red";
            msg.style.marginBottom = "10px";
            document.querySelector(".slots").prepend(msg);
        }
    })
    .catch(error => {
        console.error('Error checking active reservation:', error);
    });


    // Update booking details
    function updateBookingDetails() {
        const details = document.getElementById('selected-slots');
        const total = document.getElementById('total-cost');
        const firstName = document.getElementById('first-name').value;
        const lastName = document.getElementById('last-name').value;
        const contactNumber = document.getElementById('contact-number').value;

        details.textContent = selectedSlot ? `Selected Slot: ${selectedSlot}` : 'Please select a parking slot';
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
    const selectedArray = selectedSlot ? [selectedSlot] : [];

    // Update End Date and Time (UI)
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
    const endDateObj = new Date(startDateObj.getTime() + durationHours * 60 * 60 * 1000);

    const endDate = endDateObj.toISOString().slice(0, 10);
    const endHours = String(endDateObj.getHours()).padStart(2, '0');
    const endMinutes = String(endDateObj.getMinutes()).padStart(2, '0');
    const endTime = `${endHours}:${endMinutes}`;

    // Calculate Total Cost
    const ratePerHour = SLOT_COST;
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

    // AJAX to save booking
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
    
tomorrow.setDate(today.getDate() + 1);
dayAfterTomorrow.setDate(today.getDate() + 2);

const toDateInputFormat = (date) => date.toISOString().split("T")[0];

bookingDateInput.min = toDateInputFormat(today);
bookingDateInput.max = toDateInputFormat(dayAfterTomorrow);

// Limit duration between 1 and 12 hours
const durationInput = document.getElementById("duration");
durationInput.min = 1;
durationInput.max = 12;

// Calculate cost when the user selects duration
durationInput.addEventListener('input', () => {
    const duration = durationInput.value;

    if (duration > 0) {
        const totalCost = duration * SLOT_COST;
        document.getElementById("total-cost").textContent = "Total Cost: ₱" + totalCost.toFixed(2);
    }
});

// Check if duration is more than 8 hours
durationInput.addEventListener('change', () => {
    const duration = durationInput.value;

    if (duration > 12) {
        alert('The duration cannot more than 12 hours.');
        durationInput.value = 12; // Optionally set the duration back to 12 if it's above
    }else if (duration < 1) {
        alert('The duration cannot be less than 1 hour.');
        durationInput.value = 1; // Optionally set the duration back to 1 if it's below
    }

    if (duration > 0) {
        const totalCost = duration * SLOT_COST;
        document.getElementById("total-cost").textContent = "Total Cost: ₱" + totalCost.toFixed(2);
    }
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

// Fetch reservation history data and populate the table
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



document.addEventListener('DOMContentLoaded', function () {
    fetchBalance();
});

// // Call the function to load data on page load
// window.onload = fetchBalance;

// Function to fetch balance and transaction history
document.addEventListener('DOMContentLoaded', function () {
    fetch('check-reservation.php')
        .then(res => res.json())
        .then(data => {
            const section = document.getElementById('reservation-section');
            const messageBox = document.getElementById('message-box');

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

                let buttonsHTML = '';
                if (!isPastReservation) {
                    buttonsHTML += `<button id="cancel-reservation-btn" class="btn btn-danger me-2">Cancel Reservation</button>`;
                    buttonsHTML += `<button id="start-now-btn" class="btn btn-success">Start Now</button>`;
                }

                section.innerHTML = `
                  <div class="reservation-info">
                      <h3 class="activereservation">Active Reservation</h3>
                      <p><strong>Slot:</strong> ${r.slot_number}</p>
                      <p><strong>Start Date & Time:</strong> ${formattedStart}</p>
                      <p><strong>Total Cost:</strong> PHP ${parseFloat(r.total_cost).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</p>
                      ${isPastReservation ? `<p class="text-danger">This reservation has already ended.</p>` : buttonsHTML}
                  </div>
                `;

                if (!isPastReservation) {
                    document.getElementById('cancel-reservation-btn').addEventListener('click', function () {
                        const cancelModal = new bootstrap.Modal(document.getElementById('cancelConfirmationModal'));
                        cancelModal.show();

                        document.getElementById('confirm-cancel-btn').addEventListener('click', function () {
                            fetch('cancel-reservation.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({ reservationId: r.id })
                            })
                            .then(response => response.json())
                            .then(result => {
                                if (result.success) {
                                    messageBox.innerHTML = `<div class="alert alert-success">Reservation successfully canceled. <br>Refreshing in 3s...</div>`;
                                } else {
                                    messageBox.innerHTML = `<div class="alert alert-danger">An error occurred while canceling your reservation.</div>`;
                                }

                                setTimeout(() => {
                                    messageBox.innerHTML = '';
                                }, 5000);

                                if (result.success) {
                                    setTimeout(() => {
                                        window.location.reload();
                                    }, 3000);
                                }
                            })
                            .catch(error => {
                                console.error('Error canceling reservation:', error);
                                messageBox.innerHTML = `<div class="alert alert-danger">An error occurred while canceling your reservation.</div>`;
                            });

                            cancelModal.hide();
                        });
                    });

                    // Add "Start Now" button functionality
                    document.getElementById('start-now-btn').addEventListener('click', function () {
                        this.style.display = 'none';  // Hide "Start Now" button
                        startCountdown(endDateTime);  // Start countdown after button click
                    });
                }
            } else {
                section.innerHTML = '<p class="activealert">No active reservation found.</p>';
            }
        })
        .catch(error => {
            console.error('Error fetching reservation data:', error);
        });

    function startCountdown(endDateTime) {
        const countdownDisplay = document.createElement('p');
        countdownDisplay.setAttribute('id', 'countdown-display');
        document.querySelector('.reservation-info').appendChild(countdownDisplay);

        const interval = setInterval(function () {
            const now = new Date();
            const remainingTime = endDateTime - now;

            if (remainingTime <= 0) {
                clearInterval(interval);
                countdownDisplay.innerHTML = 'Reservation has ended.';
            } else {
                const days = Math.floor(remainingTime / (1000 * 60 * 60 * 24));
                const hours = Math.floor((remainingTime % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((remainingTime % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((remainingTime % (1000 * 60)) / 1000);

                //${days} day(s), 
                countdownDisplay.innerHTML = `Remaining Time: ${hours} hour(s), ${minutes} minute(s), ${seconds} second(s)`;
            }
        }, 1000);
    }
});

document.addEventListener("DOMContentLoaded", function () {
    // Fetch slot reservation status from the backend
    fetch('get-slot-status.php')
        .then(response => response.json())
        .then(data => {
            // Loop through the slot data and update the slot status
            data.slots.forEach(slot => {
                const slotElement = document.getElementById(`slot-${slot.id}`);
                
                // If the slot is reserved, turn it red
                if (slot.status === 'reserved') {
                    slotElement.classList.remove('available');
                    slotElement.classList.add('reserved');
                    slotElement.style.pointerEvents = 'none'; // Disable clicking
                } else {
                    // If the slot is available, make it green
                    slotElement.classList.remove('reserved');
                    slotElement.classList.add('available');
                    slotElement.style.pointerEvents = 'auto'; // Enable clicking
                }
            });
        })
        .catch(error => {
            console.error('Error fetching slot status:', error);
        });
});

fetch('get-active-reservation.php') // Fetch active reservation data
    .then(response => response.json())
    .then(data => {
        if (data && data.id) {
            activeReservation = data; // Store it globally
        } else {
            console.log('No active reservation found.');
        }
    })
    .catch(error => {
        console.error('Failed to fetch active reservation:', error);
    });


    document.addEventListener('DOMContentLoaded', () => {
        const cancelReservationBtn = document.getElementById('cancel-reservation-btn');
        
        if (cancelReservationBtn) {
            cancelReservationBtn.addEventListener('click', function () {
                if (!activeReservation || !activeReservation.id) {
                    alert('No active reservation found.');
                    return;
                }
    
                // Show confirmation before canceling
                const confirmCancel = confirm('Are you sure you want to cancel your reservation?');
                if (confirmCancel) {
                    // Send cancellation request to the server
                    fetch('cancel-reservation.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ reservationId: activeReservation.id })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Your reservation has been canceled successfully.');
                            window.location.reload(); // Optionally reload to reflect changes
                        } else {
                            alert('Failed to cancel reservation. Please try again later.');
                        }
                    })
                    .catch(error => {
                        console.error('Error canceling reservation:', error);
                        alert('An error occurred while canceling your reservation.');
                    });
                }
            });
        }
    });
    