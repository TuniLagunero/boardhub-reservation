<?php
require 'config/db.php';
$rooms = $pdo->query("SELECT * FROM rooms ORDER BY name ASC")->fetchAll();

$departments = $pdo->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll();
$start = strtotime('07:00');
// Set end to 23:30 so the last 30-min slot is 11:30 PM - 12:00 AM
$end   = strtotime('23:30');
$time_slots = [];
while ($start <= $end) {
    $time_slots[] = date('H:i', $start);
    $start = strtotime('+30 minutes', $start);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Reservation</title>
    
    <link rel="stylesheet" href="css/reservestyle.css">
    <style>
        #time_validation {
            opacity: 0;
            width: 1px;
            height: 1px;
            border: none;
            position: absolute;
            bottom: 10px;
            left: 150px;
            pointer-events: none;
            z-index: -1;
        }
    </style>
</head>
<body>
    <nav class="navbar" style="height: 75px !important; min-height: 75px !important;">
        <a href="index.php" class="logo">
            <img src="/boardhub/images/amertron_logo.png" alt="Amertron" style="height: 50px; width: auto; margin-right: 10px;">
        </a>
        <div class="nav-actions">
        <a href="index.php" class="btn-secondary">Back to Dashboard</a>
        </div>
    </nav>

    <div class="main-content">
        <div class="container">
            <h1 class="page-title">New Reservation</h1>
            <p class="page-subtitle">Book a board room for your meeting or event.</p>

            <form action="save_booking.php" method="POST" id="bookingForm">
                
                <div class="form-step">
                    <div class="step-header">
                        <div class="step-icon">
                            <img src="images/meeting.png" alt="Room" 
                                style="width: 35px; height: auto;">
                        </div> 
                        <h2 class="step-title">Select Board Room</h2>
                    </div>
                    <div class="room-grid">
                        <?php foreach($rooms as $room): ?>
                            <label class="room-card-label">
                                <input type="radio" name="room_id" value="<?= $room['id'] ?>" class="room-card-input" required onclick="checkAvailability()">
                                <div class="room-card">
                                    <span class="room-name"><?= htmlspecialchars($room['name']) ?></span>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-step">
                    <div class="step-header">
                        <div class="step-icon">
                            <img src="images/reserved.png" alt="Date" 
                                style="width: 30px; height: auto;">
                        </div>
                        <h2 class="step-title">Select Date</h2>
                    </div>
                    <div class="form-group">
                        <div style="position: relative;">
                             <input type="date" name="date" id="dateInput" class="form-input" 
                                    min="<?= date('Y-m-d') ?>" required onchange="checkAvailability()"
                                    style="padding-right: 45px;"> <img src="images/reserved.png" alt="Select Date" 
                                  style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); width:15px; height: 15px; pointer-events: none;">
                        </div>
                    </div>
                </div>

                <div class="form-step" style="position: relative;">
                    <div class="step-header">
                        <div class="step-icon">
                            <img src="images/clock.png" alt="Time" 
                                style="width:30px; height: auto; ">
                        </div>
                        <h2 class="step-title">Select Time</h2>
                        <input type="text" id="time_validation" required oninvalid="this.setCustomValidity('Please select a time slot.')" oninput="this.setCustomValidity('')">
                    </div>
                    
                    <div class="legend">
                        <div class="legend-item"><span class="dot dot-available"></span> Available</div>
                        <div class="legend-item"><span class="dot dot-selected"></span> Selected</div>
                        <div class="legend-item"><span class="dot dot-unavailable"></span> Unavailable</div>
                    </div>

                    <div id="selection-summary" class="selection-summary" style="display: none;">
                        Selected: <span id="display-start">--:--</span> to <span id="display-end">--:--</span> 
                        (<span id="display-duration">0 min</span>)
                    </div>

                    <input type="hidden" name="start_time" id="input_start_time">
                    <input type="hidden" name="end_time" id="input_end_time">

                    <div id="loading-msg" style="display:none; color:var(--primary-blue); font-size:0.9rem; margin-bottom:15px; font-weight: 500;">Checking availability...</div>

                    <div class="time-grid" id="timeGrid">
                        <?php foreach($time_slots as $slot): ?>
                            <div class="time-slot-btn" id="slot-<?= str_replace(':', '', $slot) ?>" data-time="<?= $slot ?>">
                                <?= date('g:i A', strtotime($slot)) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p id="time-error" style="color:#ef4444; font-size:0.9rem; margin-top:10px; display:none;"></p>
                </div>

                <div class="form-step" style="margin-bottom: 0;">
                    <div class="step-header">
                        <div class="step-icon">
                            <img src="images/clipboard.png" alt="Details" 
                                style="width: 30px; height: auto;">
                        </div>
                        <h2 class="step-title">Reservation Details</h2>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Requester Name</label>
                            <input type="text" name="requester_name" class="form-input" required placeholder="Name">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Department</label>
                            <div class="select-wrapper">
                                <select name="department" class="form-select" required>
                                    <option value="" disabled selected>Select Department...</option>
                                    <?php foreach($departments as $dept): ?>
                                        <option value="<?= htmlspecialchars($dept['name']) ?>">
                                            <?= htmlspecialchars($dept['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="select-icon">▾</span>
                            </div>
                        </div>
                        
                    </div>
                    <div class="form-group">
                        <label class="form-label">Purpose of Meeting</label>
                        <input type="text" name="subject" class="form-input" required placeholder="Purpose">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="requester_email" class="form-input" required placeholder="name@example.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Additional Notes</label>
                        <textarea name="notes" class="form-textarea" placeholder="Any specific requirements..."></textarea>
                    </div>
                </div>

                <div class="submit-section">
                    <button type="submit" class="btn-primary">
                        <span style="font-size: 1.3rem;">✓</span>
                        Submit Reservation
                    </button>
                </div>

            </form>
        </div>
    </div>

    <script>
        const bookingForm = document.getElementById('bookingForm');
        const slots = document.querySelectorAll('.time-slot-btn');
        const startInput = document.getElementById('input_start_time');
        const endInput = document.getElementById('input_end_time');
        
        const validationInput = document.getElementById('time_validation'); 
        
        const errorMsg = document.getElementById('time-error');
        const summaryBox = document.getElementById('selection-summary');
        const displayStart = document.getElementById('display-start');
        const displayEnd = document.getElementById('display-end');
        const displayDuration = document.getElementById('display-duration');
        
        let selectionState = 'none'; 
        let startIndex = -1;

        function checkAvailability() {
            const dateVal = document.getElementById('dateInput').value;
            const roomVal = document.querySelector('input[name="room_id"]:checked')?.value;

            if (!dateVal || !roomVal) return;

            document.getElementById('loading-msg').style.display = 'block';

            resetSelection();
            
            slots.forEach(slot => slot.classList.remove('disabled'));

            fetch(`check_availability.php?room_id=${roomVal}&date=${dateVal}&t=${new Date().getTime()}`)
                .then(response => response.json())
                .then(bookedSlots => {
                    document.getElementById('loading-msg').style.display = 'none';
                    bookedSlots.forEach(time => {
                        const btn = document.querySelector(`.time-slot-btn[data-time="${time}"]`);
                        if (btn) btn.classList.add('disabled');
                    });
                })
                .catch(err => console.error("Error:", err));
        }

        slots.forEach((slot, index) => {
            slot.addEventListener('click', () => {
                if (slot.classList.contains('disabled')) return;

                if (selectionState === 'none' || selectionState === 'range_selected') {
                    resetSelection();
                    startIndex = index;
                    highlightSlot(index);
                    updateInputs(slot.dataset.time, addMinutes(slot.dataset.time, 30));
                    selectionState = 'start_selected';
                }
                else if (selectionState === 'start_selected') {
                    if (index < startIndex) {
                        resetSelection();
                        startIndex = index;
                        highlightSlot(index);
                        updateInputs(slot.dataset.time, addMinutes(slot.dataset.time, 30));
                        selectionState = 'start_selected';
                    }
                    else {
                        let isValidRange = true;
                        for (let k = startIndex; k <= index; k++) {
                            if (slots[k].classList.contains('disabled')) {
                                isValidRange = false;
                                break;
                            }
                        }

                        if (!isValidRange) {
                            errorMsg.textContent = "You cannot select a range that includes booked slots.";
                            errorMsg.style.display = 'block';
                            return;
                        }

                        let slotsCount = index - startIndex + 1;
                        if (slotsCount > 4) {
                            errorMsg.textContent = "Maximum duration is 2 hours.";
                            errorMsg.style.display = 'block';
                            return; 
                        }

                        errorMsg.style.display = 'none';
                        for (let i = startIndex; i <= index; i++) {
                            slots[i].classList.add('selected');
                        }

                        let calculatedEndTime = addMinutes(slot.dataset.time, 30);
                        updateInputs(slots[startIndex].dataset.time, calculatedEndTime);
                        selectionState = 'range_selected';
                    }
                }
            });
        });

        function highlightSlot(index) {
            slots[index].classList.add('selected');
        }

        function resetSelection() {
            slots.forEach(s => s.classList.remove('selected'));
            startInput.value = '';
            endInput.value = '';
            validationInput.value = ''; 
            validationInput.setCustomValidity('');
            startIndex = -1;
            errorMsg.style.display = 'none';
            summaryBox.style.display = 'none';
        }

        function updateInputs(start, end) {
            startInput.value = start;
            endInput.value = end;
            validationInput.value = 'valid'; 
            validationInput.setCustomValidity('');
            summaryBox.style.display = 'block';
            displayStart.textContent = formatTime(start);
            displayEnd.textContent = formatTime(end);
            
            let startMin = timeToMins(start);
            let endMin = timeToMins(end);
            let diff = endMin - startMin;
            let hours = Math.floor(diff / 60);
            let mins = diff % 60;
            let durationText = "";
            if(hours > 0) durationText += hours + " hr ";
            if(mins > 0) durationText += mins + " min";
            displayDuration.textContent = durationText.trim();
        }

        function addMinutes(timeStr, minsToAdd) {
            let [hours, mins] = timeStr.split(':').map(Number);
            let date = new Date();
            date.setHours(hours, mins + minsToAdd);
            let h = date.getHours().toString().padStart(2, '0');
            let m = date.getMinutes().toString().padStart(2, '0');
            return `${h}:${m}`;
        }

        function timeToMins(timeStr) {
            let [h, m] = timeStr.split(':').map(Number);
            return (h * 60) + m;
        }

        function formatTime(timeStr) {
            let [h, m] = timeStr.split(':');
            let ampm = h >= 12 ? 'PM' : 'AM';
            h = h % 12;
            h = h ? h : 12; 
            return `${h}:${m} ${ampm}`;
        }
    </script>
</body>
</html>