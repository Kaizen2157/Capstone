let activeReservation = null; // global variable to store reservation data
let SLOT_COST = 0; // Initialize cost variable
let selectedSlot = null; // Store only one selected slot
let slotCheckInterval; // For periodic slot status checks

document.addEventListener('DOMContentLoaded', () => {
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
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (data.error) {
                console.error('Server error:', data.error);
                usernameDisplay.textContent = 'User';
            } else {
                usernameDisplay.textContent = data.name;
                console.log('Fetched username:', data.name);
            }
        })
        .catch(err => {
            console.error('Failed to fetch username:', err);
            fetch('get-user.php')
                .then(r => r.text())
                .then(text => console.error('Raw response:', text));
            usernameDisplay.textContent = 'User';
        });

    const bookingDateInput = document.getElementById("booking-date");

    // Get current date/time in Manila timezone
    function getManilaTime() {
        const now = new Date();
        const manilaTimeStr = now.toLocaleString('en-US', {
            timeZone: 'Asia/Manila',
            hour12: false
        });
        return new Date(manilaTimeStr);
    }

    let manilaTime = getManilaTime();
    let today = new Date(manilaTime);
    today.setHours(0, 0, 0, 0); // Start of day in Manila

    let maxBookingDate = new Date(today);
    maxBookingDate.setDate(today.getDate() + 2); // 2 days from today in Manila

    // Helper function to format date as YYYY-MM-DD
    let toDateInputFormat = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    // Set min/max dates
    bookingDateInput.min = toDateInputFormat(today);
    bookingDateInput.max = toDateInputFormat(maxBookingDate);

    // Set default value to today
    bookingDateInput.value = toDateInputFormat(today);

    // Additional validation when form is submitted
    document.getElementById('proceed-btn').addEventListener('click', function(e) {
        const selectedDate = new Date(bookingDateInput.value);
        const manilaNow = getManilaTime();
        
        const todayStart = new Date(manilaNow);
        todayStart.setHours(0, 0, 0, 0);

        const maxAllowedDate = new Date(todayStart);
        maxAllowedDate.setDate(todayStart.getDate() + 2);
        maxAllowedDate.setHours(23, 59, 59, 999); // End of day

        if (selectedDate < todayStart || selectedDate > maxAllowedDate) {
            e.preventDefault();
            alert("You can only book from today up to 2 days ahead (until 11:59 PM Manila time).");
            bookingDateInput.value = toDateInputFormat(todayStart);
            return false;
        }
    });

    // Update balance display
    fetch('get-balance.php')
        .then(response => response.json())
        .then(data => {
            const formattedBalance = Number(data.balance).toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            
            document.getElementById('balance-display').innerText = '₱' + formattedBalance;
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

    // Fetch slot cost from the server
    fetch('home-dashboard.php?get_cost=true')
        .then(response => response.json())
        .then(data => {
            SLOT_COST = data.cost || 40;
        })
        .catch(err => {
            console.error('Failed to fetch slot cost:', err);
            SLOT_COST = 40;
        });

    // Function to check and update slot status
    function checkSlotStatus() {
        fetch('get-slot-status.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update UI with current slot status
                    data.slots.forEach(slot => {
                        const slotElement = document.getElementById(`slot-${slot.id}`);
                        if (slotElement) {
                            if (slot.status === 'reserved') {
                                slotElement.classList.remove('available', 'selected');
                                slotElement.classList.add('reserved');
                                slotElement.style.cursor = "not-allowed";
                                slotElement.style.pointerEvents = 'none';
                            } else {
                                // Only make available if not selected or disabled
                                if (!slotElement.classList.contains('selected') && !slotElement.classList.contains('disabled')) {
                                    slotElement.classList.remove('reserved');
                                    slotElement.classList.add('available');
                                    slotElement.style.cursor = "pointer";
                                    slotElement.style.pointerEvents = 'auto';
                                }
                            }
                        }
                    });
                    
                    // If any slots were cleaned up, log it
                    if (data.cleaned > 0) {
                        console.log(`Cleaned up ${data.cleaned} expired reservations`);
                    }
                }
            })
            .catch(error => {
                console.error('Error checking slot status:', error);
            });
    }

    // Initial slot status check
    checkSlotStatus();
    
    // Set up periodic checking (every 30 seconds)
    slotCheckInterval = setInterval(checkSlotStatus, 30000);

    // Handle slot selection and active reservations
    fetch('check-active-reservation.php')
        .then(response => response.json())
        .then(data => {
            console.log("Active reservation check:", data);
            const hasActive = data.hasActiveReservation;
            const slotElements = document.querySelectorAll('.slots .slotone > p');

            slotElements.forEach(slot => {
                // Skip if slot is already marked as reserved
                if (slot.classList.contains('reserved')) {
                    return;
                }

                if (!slot.classList.contains('occupied')) {
                    if (hasActive) {
                        // Only disable the slots if the user has an active reservation
                        slot.classList.add('disabled');
                        slot.style.cursor = "not-allowed";
                        slot.style.pointerEvents = 'none';
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

                            updateBookingDetails();
                        });
                    }
                }
            });

            if (hasActive) {
                const msg = document.createElement('div');
                msg.textContent = "⚠️ You already have an active reservation. You can only reserve one slot at a time.";
                
                msg.style.backgroundColor = "#ffe0e0";
                msg.style.color = "#a94442";
                msg.style.border = "1px solid #f5c6cb";
                msg.style.padding = "12px";
                msg.style.borderRadius = "5px";
                msg.style.marginBottom = "15px";
                msg.style.fontWeight = "bold";
                msg.style.textAlign = "center";
                
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

    // Function to check time conflicts
    function checkTimeConflict(date, startTime, endTime, slotNumber) {
        return fetch('check-time-conflict.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                date: date,
                start_time: startTime,
                end_time: endTime,
                slot_number: slotNumber
            })
        })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            return data.hasConflict;
        });
    }

    // Function to show toast message
    function showToast(message) {
        const toastMessage = document.getElementById("toastMessage");
        toastMessage.textContent = message;
    
        const toastElement = new bootstrap.Toast(document.getElementById("errorToast"));
        toastElement.show();
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

        // Check for time conflicts before proceeding
        checkTimeConflict(bookingDate, startTime, endTime, selectedArray[0])
            .then(hasConflict => {
                if (hasConflict) {
                    showToast("This slot is already reserved for the selected time period. Please choose a different time or slot.");
                    return;
                }

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

                // Replace the fetch call in the proceed button click handler with this:
fetch('home-dashboard.php', {
    method: 'POST',
    body: formData
})
.then(response => {
    if (!response.ok) {
        throw new Error('Network response was not ok');
    }
    return response.text().then(text => {
        try {
            return text ? JSON.parse(text) : {};
        } catch (e) {
            return {
                success: false,
                message: text || 'Unknown error occurred'
            };
        }
    });
})
.then(data => {
    if (data.success) {
        showToast("Reservation created successfully!");
        setTimeout(() => {
            window.location.reload();
        }, 2000);
    } else {
        showToast(data.message || "Failed to create reservation");
    }
})
// .catch(error => {
//     console.error('Error:', error);
//     showToast("An error occurred while processing your request");
// });
            })
            .catch(error => {
                console.error('Error checking time conflict:', error);
                showToast("Error checking slot availability. Please try again.");
            });
    });
    
    // Duration input handling
    const durationInput = document.getElementById("duration");
    durationInput.min = 1;
    durationInput.max = 12;

    // Calculate cost when duration changes
    durationInput.addEventListener('input', () => {
        const duration = durationInput.value;
        if (duration > 0) {
            const totalCost = duration * SLOT_COST;
            document.getElementById("total-cost").textContent = "Total Cost: ₱" + totalCost.toFixed(2);
        }
    });

    // Check if duration is more than 12 hours
    durationInput.addEventListener('change', () => {
        const duration = durationInput.value;

        if (duration > 12) {
            alert('The duration cannot more than 12 hours.');
            durationInput.value = 12;
        } else if (duration < 1) {
            alert('The duration cannot be less than 1 hour.');
            durationInput.value = 1;
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

// Modern logout confirmation
document.addEventListener('DOMContentLoaded', function() {
    const logoutBtn = document.getElementById('confirmLogoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function() {
            logoutBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Logging out...';
            logoutBtn.disabled = true;
            window.location.href = "logout.php";
        });
    }
});

// Check for valid session on page load
document.addEventListener('DOMContentLoaded', function() {
    fetch('check-session.php')
        .then(response => response.json())
        .then(data => {
            if (!data.valid) {
                window.location.replace('../frontend/backups/login/login.html?session_expired=1');
            }
        });
});

// Modified logout function
function confirmLogout() {
    if (confirm("Are you sure you want to logout?")) {
        sessionStorage.clear();
        localStorage.clear();
        const logoutUrl = 'logout.php?t=' + new Date().getTime();
        window.location.href = logoutUrl;
    }
}

// Clear interval when leaving the page
window.addEventListener('beforeunload', () => {
    if (slotCheckInterval) {
        clearInterval(slotCheckInterval);
    }
});

// Fetch recent reservation data and populate the table
document.addEventListener("DOMContentLoaded", function () {
    fetch('get-recent-reservation.php')
        .then(response => response.json())
        .then(data => {
            const tableBody = document.querySelector('.reservation-history tbody');
            tableBody.innerHTML = '';

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
                <td>${reservation.status}</td>
                <td>${startDateTimeStr} - ${endDateTimeStr}</td>
                <td>₱${reservation.total_cost}</td>
            </tr>
        `;

        historyTableBody.innerHTML += row;
    });
})
.catch(err => {
    console.error("Error fetching reservation data", err);
});

// Function to fetch balance and transaction history
document.addEventListener('DOMContentLoaded', function () {
    fetch('check-reservation.php')
        .then(res => res.json())
        .then(data => {
            const section = document.getElementById('reservation-section');
            const messageBox = document.getElementById('message-box');

            if (data.hasReservation && data.reservation) {
                const r = data.reservation;

                const startDateTime = new Date(`${r.start_date}T${r.start_time}`);
                const endDateTime = new Date(`${r.start_date}T${r.end_time}`);

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

                const currentTime = new Date();

                const isPastReservation = currentTime >= endDateTime;

                let buttonsHTML = '';
                const isTooLateToStart = currentTime >= startDateTime;

                if (!isPastReservation && !isTooLateToStart && r.start_button_clicked !== 1) {
                    buttonsHTML += `<button id="cancel-reservation-btn" class="btn btn-danger me-2">Cancel Reservation</button>`;
                    // buttonsHTML += `<button id="start-now-btn" class="btn btn-success">Start Now</button>`;
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

                if (!isPastReservation && r.start_button_clicked !== 1) {
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

                    document.getElementById('start-now-btn').addEventListener('click', function() {
                        const startModal = new bootstrap.Modal(document.getElementById('startConfirmationModal'));
                        startModal.show();
                                        
                        const confirmBtn = document.getElementById('confirm-start-btn');
                        const newBtn = confirmBtn.cloneNode(true);
                        confirmBtn.parentNode.replaceChild(newBtn, confirmBtn);
                                        
                        newBtn.addEventListener('click', async function() {
                            try {
                                newBtn.disabled = true;
                                newBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
                            
                                const response = await fetch('start-reservation-now.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                    },
                                    body: JSON.stringify({ reservationId: r.id })
                                });
                            
                                if (!response.ok) {
                                    const errorText = await response.text();
                                    throw new Error(`Server error: ${response.status} - ${errorText}`);
                                }
                            
                                const contentType = response.headers.get('content-type');
                                if (!contentType || !contentType.includes('application/json')) {
                                    const text = await response.text();
                                    throw new Error(`Expected JSON but got: ${text.substring(0, 100)}...`);
                                }
                            
                                const data = await response.json();
                                console.log('Update response:', data);
                            
                                if (data.success) {
                                    startModal.hide();
                                    const messageBox = document.getElementById('message-box');
                                    messageBox.innerHTML = `
                                        <div class="alert alert-success">
                                            Reservation started successfully.
                                        </div>
                                    `;
                                    setTimeout(() => window.location.reload(), 2000);
                                } else {
                                    throw new Error(data.message || 'Failed to start reservation');
                                }
                            } catch (error) {
                                console.error('Error starting reservation:', error);
                                const messageBox = document.getElementById('message-box');
                                messageBox.innerHTML = `
                                    <div class="alert alert-danger">
                                        Error: ${error.message}
                                    </div>
                                `;
                            } finally {
                                newBtn.disabled = false;
                                newBtn.textContent = 'Confirm Start';
                            }
                        });
                    });                    
                }
            } else {
                section.innerHTML = '<p class="activealert">No active reservation found.</p>';
            }
        })
        .catch(error => {
            console.error('Error fetching reservation data:', error);
        });
});

function loadTransactionHistory() {
    fetch('get-transactions.php')
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            const container = document.querySelector('.transaction-list');
            
            if (data.error) {
                container.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                return;
            }

            if (!data.length) {
                container.innerHTML = `<div class="alert alert-info">No transactions found</div>`;
                return;
            }

            container.innerHTML = data.map(tx => `
                <div class="transaction-item ${tx.is_credit ? 'credit' : 'debit'}">
                    <div class="transaction-header">
                        <span class="tx-type">${tx.type.toUpperCase()}</span>
                        <span class="tx-date">${tx.date}</span>
                    </div>
                    <div class="transaction-details">
                        <span class="tx-description">${tx.description}</span>
                        <span class="tx-amount ${tx.is_credit ? 'text-success' : 'text-danger'}">
                            ${tx.is_credit ? '+' : '-'}₱${tx.display_amount}
                        </span>
                    </div>
                    <div class="transaction-footer">
                        <span class="tx-balance">Balance: ₱${tx.balance_after}</span>
                        ${tx.reference_id ? `<span class="tx-reference">Ref: #${tx.reference_id}</span>` : ''}
                    </div>
                </div>
            `).join('');
        })
        .catch(error => {
            console.error('Error:', error);
            document.querySelector('.transaction-list').innerHTML = `
                <div class="alert alert-danger">
                    Failed to load transactions: ${error.message}
                </div>
            `;
        });
}

// Load on page init
document.addEventListener('DOMContentLoaded', loadTransactionHistory);

// Function to explicitly clean up expired reservations
function cleanupExpiredReservations() {
    fetch('get-reserved-slots.php')
        .then(response => response.json())
        .then(data => {
            if (data.cleaned > 0) {
                console.log(`Automatically cleaned ${data.cleaned} expired reservations`);
                // Refresh slot status after cleanup
                checkSlotStatus();
            }
        })
        .catch(error => {
            console.error('Error cleaning expired reservations:', error);
        });
}

function updateReservationMonitor() {
    // Show loading state
    const monitorContent = document.getElementById('monitor-content');
    monitorContent.innerHTML = '<p>Loading reservation data...</p>';

    fetch('get-active-reservation.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Failed to fetch reservations');
            }

            if (data.reservations.length === 0) {
                monitorContent.innerHTML = '<p>No reservations found</p>';
                return;
            }

            let html = '<table style="width: 100%; border-collapse: collapse; margin-top: 10px;">';
            html += '<tr style="background: #19384a; color: white;">';
            html += '<th style="padding: 8px; text-align: left;">Slot</th>';
            html += '<th style="padding: 8px; text-align: left;">Date</th>';
            html += '<th style="padding: 8px; text-align: left;">Time</th>';
            html += '<th style="padding: 8px; text-align: left;">Status</th>';
            html += '</tr>';

            data.reservations.forEach(res => {
                try {
                    const formattedDate = new Date(res.date).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    });
                    
                    const startTime = new Date(`1970-01-01T${res.start_time}`).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    const endTime = new Date(`1970-01-01T${res.end_time}`).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    
                    // Apply red background only for current user's reservations
                    const rowStyle = res.is_current_user 
                        ? 'background-color: #ffe8e8;' 
                        : 'background-color: #ffffff;';
                    
                    // Make current user's reservations more prominent
                    const statusText = res.is_current_user 
                        ? `<strong>${res.status} (Yours)</strong>` 
                        : res.status;
                    
                    html += `<tr style="border-bottom: 1px solid #eee; ${rowStyle}">`;
                    html += `<td style="padding: 8px;">${res.slot}</td>`;
                    html += `<td style="padding: 8px;">${formattedDate}</td>`;
                    html += `<td style="padding: 8px;">${startTime} - ${endTime}</td>`;
                    html += `<td style="padding: 8px; text-transform: capitalize;">${statusText}</td>`;
                    html += `</tr>`;
                } catch (e) {
                    console.error('Error formatting reservation:', res, e);
                }
            });

            html += '</table>';
            monitorContent.innerHTML = html;
        })
        .catch(error => {
            console.error('Error fetching reservations:', error);
            monitorContent.innerHTML = `
                <div style="color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px;">
                    Error loading reservation data: ${error.message}
                </div>
            `;
        });
}

// Initial load with error handling
try {
    updateReservationMonitor();
} catch (e) {
    console.error('Initial load error:', e);
    document.getElementById('monitor-content').innerHTML = `
        <div style="color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px;">
            Failed to initialize reservation monitor
        </div>
    `;
}

// Refresh button with error handling
document.getElementById('refresh-monitor')?.addEventListener('click', () => {
    try {
        updateReservationMonitor();
    } catch (e) {
        console.error('Refresh error:', e);
    }
});

// Auto-refresh with error handling
const refreshInterval = setInterval(() => {
    try {
        updateReservationMonitor();
    } catch (e) {
        console.error('Auto-refresh error:', e);
    }
}, 30000);

// Clean up on page unload
window.addEventListener('beforeunload', () => {
    clearInterval(refreshInterval);
});



// Responsive initialization
function initResponsive() {
    // Adjust table headers for mobile
    if (window.innerWidth < 768) {
        document.querySelectorAll('.reservation-history th').forEach(th => {
            const text = th.textContent;
            if (text.length > 10) {
                th.setAttribute('data-short', text.split(' ')[0]);
            }
        });
    }
    
    // Balance display formatting
    const balanceDisplay = document.getElementById('balance-display');
    if (balanceDisplay && window.innerWidth < 576) {
        const balance = balanceDisplay.textContent;
        if (balance.length > 12) {
            balanceDisplay.textContent = balance.replace('Balance: ', '');
        }
    }
}

// Run on load and resize
window.addEventListener('DOMContentLoaded', initResponsive);
window.addEventListener('resize', initResponsive);

// Add this to your existing JavaScript

// Make reservation monitor collapsible on small screens
document.addEventListener('DOMContentLoaded', function() {
    // Create toggle button for mobile
    const monitor = document.getElementById('reservation-monitor');
    if (window.innerWidth <= 400) {
        const toggleBtn = document.createElement('button');
        toggleBtn.className = 'toggle-monitor';
        toggleBtn.innerHTML = '<i class="bx bx-info-circle"></i>';
        toggleBtn.addEventListener('click', function() {
            monitor.classList.toggle('collapsed');
        });
        monitor.insertBefore(toggleBtn, monitor.firstChild);
        monitor.classList.add('collapsed');
    }

    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth <= 400) {
            if (!document.querySelector('.toggle-monitor')) {
                const toggleBtn = document.createElement('button');
                toggleBtn.className = 'toggle-monitor';
                toggleBtn.innerHTML = '<i class="bx bx-info-circle"></i>';
                toggleBtn.addEventListener('click', function() {
                    monitor.classList.toggle('collapsed');
                });
                monitor.insertBefore(toggleBtn, monitor.firstChild);
            }
        } else {
            const toggleBtn = document.querySelector('.toggle-monitor');
            if (toggleBtn) {
                toggleBtn.remove();
            }
            monitor.classList.remove('collapsed');
        }
    });

    // Adjust slot sizes on load
    adjustSlotSizes();
    window.addEventListener('resize', adjustSlotSizes);
});

// Function to adjust slot sizes dynamically
function adjustSlotSizes() {
    const slots = document.querySelectorAll('.slotone > p');
    if (slots.length === 0) return;

    const containerWidth = document.querySelector('.slotone').clientWidth;
    const isMobile = window.innerWidth < 768;
    
    slots.forEach(slot => {
        if (isMobile) {
            slot.style.width = 'calc(50% - 20px)';
            slot.style.height = '15vh';
        } else {
            slot.style.width = '';
            slot.style.height = '';
        }
    });
}