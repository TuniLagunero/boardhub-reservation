<?php
require 'config/db.php';

// 1. View Toggle Logic
$view = isset($_GET['view']) ? $_GET['view'] : 'month'; 

$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$day = isset($_GET['day']) ? $_GET['day'] : date('d'); 

date_default_timezone_set('Asia/Manila');

// --- Month View
$firstDayOfMonth = strtotime("$year-$month-01");
$totalDays = date('t', $firstDayOfMonth);
$startWeekDay = date('w', $firstDayOfMonth);
$monthName = date('F Y', $firstDayOfMonth);

$prevMonth = date('m', strtotime("-1 month", $firstDayOfMonth));
$prevYear = date('Y', strtotime("-1 month", $firstDayOfMonth));
$nextMonth = date('m', strtotime("+1 month", $firstDayOfMonth));
$nextYear = date('Y', strtotime("+1 month", $firstDayOfMonth));

// --- Day View Variables ---
$currentDayStr = "$year-$month-$day";
$dayNameHeader = date('F d, Y', strtotime($currentDayStr));

$prevDayTS = strtotime("-1 day", strtotime($currentDayStr));
$nextDayTS = strtotime("+1 day", strtotime($currentDayStr));

$prevDayD = date('d', $prevDayTS); $prevDayM = date('m', $prevDayTS); $prevDayY = date('Y', $prevDayTS);
$nextDayD = date('d', $nextDayTS); $nextDayM = date('m', $nextDayTS); $nextDayY = date('Y', $nextDayTS);

// SQL Logic
if($view === 'day') {
    $start_date = "$year-$month-$day 00:00:00";
    $end_date   = "$year-$month-$day 23:59:59";
} else {
    $start_date = "$year-$month-01";
    $end_date = "$year-$month-$totalDays 23:59:59";
}

$sql = "SELECT r.*, rm.name as room_name 
        FROM reservations r 
        JOIN rooms rm ON r.room_id = rm.id 
        WHERE r.start_time BETWEEN ? AND ? 
        AND (
            status != 'cancelled' 
            OR 
            (status = 'cancelled' AND cancelled_at > NOW() - INTERVAL 1 DAY)
        )
        ORDER BY r.start_time ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$start_date, $end_date]);
$reservations = $stmt->fetchAll();

// Fetch Rooms for Day View Y-Axis
$all_rooms = $pdo->query("SELECT * FROM rooms ORDER BY name ASC")->fetchAll();

$calendar_events = [];
$timeline_events = []; 
$current_time = time();
$today_date = date('Y-m-d');

foreach ($reservations as $res) {
    $dayNum = date('j', strtotime($res['start_time']));
    $res_date = date('Y-m-d', strtotime($res['start_time']));
    
    $start_ts = strtotime($res['start_time']);
    $end_ts = strtotime($res['end_time']);

    // Hide Past Dates
    if ($res_date < $today_date) {
        continue; 
    }
    
    $db_status = isset($res['status']) ? $res['status'] : 'confirmed';
    
    if ($db_status === 'cancelled') {
        $res['status'] = 'cancelled';
    } else {
        if ($current_time >= $start_ts && $current_time <= $end_ts) {
            $res['status'] = 'ongoing';
        } elseif ($current_time > $end_ts) {
            $res['status'] = 'completed';
        } else {
            $res['status'] = 'upcoming';
        }
    }

    if($view === 'month') {
        $calendar_events[$dayNum][] = $res;
    } else {
        $timeline_events[$res['room_id']][] = $res;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Dashboard</title>
    <meta http-equiv="refresh" content="300">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php require 'nav.php'; ?>

    <div class="dashboard-container">
        
        <main class="main-content" style="min-width: 0;">
            <div class="cal-header">
                
                <div class="view-toggle">
                    <a href="?view=month" class="view-btn <?= $view == 'month' ? 'active' : '' ?>">Month</a>
                    <a href="?view=day&day=<?= date('d') ?>&month=<?= date('m') ?>&year=<?= date('Y') ?>" class="view-btn <?= $view == 'day' ? 'active' : '' ?>">Day</a>
                </div>

                <div class="cal-nav">
                    <?php if($view === 'month'): ?>
                        <a href="?view=month&month=<?= $prevMonth ?>&year=<?= $prevYear ?>">&lt;</a>
                        <h2 style="margin:0 10px;"><?= $monthName ?></h2>
                        <a href="?view=month&month=<?= $nextMonth ?>&year=<?= $nextYear ?>">&gt;</a>
                    <?php else: ?>
                        <a href="?view=day&day=<?= $prevDayD ?>&month=<?= $prevDayM ?>&year=<?= $prevDayY ?>">&lt;</a>
                        <h2 style="margin:0 10px;"><?= $dayNameHeader ?></h2>
                        <a href="?view=day&day=<?= $nextDayD ?>&month=<?= $nextDayM ?>&year=<?= $nextDayY ?>">&gt;</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if($view === 'month'): ?>
            <div class="calendar-grid">
                <div class="day-name">Sun</div><div class="day-name">Mon</div><div class="day-name">Tue</div>
                <div class="day-name">Wed</div><div class="day-name">Thu</div><div class="day-name">Fri</div><div class="day-name">Sat</div>

                <?php for($i = 0; $i < $startWeekDay; $i++): ?>
                    <div class="calendar-day empty"></div>
                <?php endfor; ?>

                <?php for($day = 1; $day <= $totalDays; $day++): ?>
                    <?php 
                        $isToday = ($day == date('j') && $month == date('m') && $year == date('Y')) ? 'today' : ''; 
                    ?>
                    <div class="calendar-day <?= $isToday ?>">
                        <span class="day-number"><?= $day ?></span>
                        
                        <?php if(isset($calendar_events[$day])): ?>
                            <?php foreach($calendar_events[$day] as $evt): ?>
                                <?php 
                                    $statusClass = ($evt['status'] === 'cancelled') ? 'cancelled' : $evt['status'];
                                    $displayStatus = ucfirst($evt['status']);
                                ?>
                                <div class="event-chip <?= $statusClass ?>" 
                                    title="<?= $evt['requester_name'] ?> - <?= $evt['subject'] ?>"
                                    style="cursor: pointer;"
                                    onclick="openModal(this)"
                                    data-room="<?= htmlspecialchars($evt['room_name']) ?>"
                                    data-requester="<?= htmlspecialchars($evt['requester_name']) ?>"
                                    data-email="<?= htmlspecialchars($evt['requester_email'] ?? '') ?>"
                                    data-subject="<?= htmlspecialchars($evt['subject']) ?>"
                                    data-department="<?= htmlspecialchars($evt['department'] ?? '') ?>"
                                    data-notes="<?= htmlspecialchars($evt['notes'] ?? '') ?>"
                                    data-time="<?= date('h:i A', strtotime($evt['start_time'])) ?> - <?= date('h:i A', strtotime($evt['end_time'])) ?>"
                                    data-status="<?= $displayStatus ?>"
                                    data-created="<?= !empty($evt['created_at']) ? date('M d, Y h:i A', strtotime($evt['created_at'])) : 'N/A' ?>"
                                >
                                    <?= date('g:ia', strtotime($evt['start_time'])) ?> <?= substr($evt['room_name'], 0, 8) ?>...
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>
                
                <?php 
                $remaining = 7 - (($startWeekDay + $totalDays) % 7);
                if($remaining < 7) {
                    for($i=0; $i < $remaining; $i++) echo '<div class="calendar-day empty"></div>';
                }
                ?>
            </div>

            <?php else: ?>
            <div class="timeline-container">
                <div class="timeline-header">
                    <div class="timeline-header-room">Room</div>
                    
                    <?php for($h=7; $h<=23; $h++): ?>
                        <div class="time-label"><?= ($h > 12 ? $h-12 : $h) . ($h >= 12 ? ' PM' : ' AM') ?></div>
                    <?php endfor; ?>
                </div>

                <?php foreach($all_rooms as $room): ?>
                <div class="timeline-row">
                    <div class="timeline-room"><?= htmlspecialchars($room['name']) ?></div>

                    <?php for($s=1; $s<=34; $s++): ?>
                        <div class="timeline-slot" style="grid-column: <?= $s+1 ?>"></div>
                    <?php endfor; ?>

                    <?php if(isset($timeline_events[$room['id']])): ?>
                        <?php foreach($timeline_events[$room['id']] as $evt): ?>
                            <?php
                                // Math for Grid Positioning
                                $start_h = (int)date('H', strtotime($evt['start_time']));
                                $start_m = (int)date('i', strtotime($evt['start_time']));
                                
                                // (Hour - 7) * 2 + (Minutes / 30) + 2 offset
                                $col_start = (($start_h - 7) * 2) + ($start_m / 30) + 2;
                                
                                $duration_mins = (strtotime($evt['end_time']) - strtotime($evt['start_time'])) / 60;
                                $span = $duration_mins / 30;

                                $statusClass = $evt['status'];
                                $color = '#0284c7'; $bg = '#e0f2fe';
                                if($statusClass == 'ongoing') { $color='#b91c1c'; $bg='#fee2e2'; }
                                if($statusClass == 'completed') { $color='#15803d'; $bg='#dcfce7'; }
                            ?>
                            <div class="timeline-event" 
                                onclick="openModal(this)"
                                style="grid-column: <?= $col_start ?> / span <?= $span ?>; background-color:<?= $bg ?>; color:<?= $color ?>; border-left-color: <?= $color ?>;"
                                data-room="<?= htmlspecialchars($evt['room_name']) ?>"
                                data-requester="<?= htmlspecialchars($evt['requester_name']) ?>"
                                data-email="<?= htmlspecialchars($evt['requester_email'] ?? '') ?>"
                                data-subject="<?= htmlspecialchars($evt['subject']) ?>"
                                data-department="<?= htmlspecialchars($evt['department'] ?? '') ?>"
                                data-notes="<?= htmlspecialchars($evt['notes'] ?? '') ?>"
                                data-time="<?= date('h:i A', strtotime($evt['start_time'])) ?> - <?= date('h:i A', strtotime($evt['end_time'])) ?>"
                                data-status="<?= ucfirst($evt['status']) ?>"
                                data-created="<?= !empty($evt['created_at']) ? date('M d, Y h:i A', strtotime($evt['created_at'])) : 'N/A' ?>"
                            >
                                <div class="chip-subject"><?= htmlspecialchars($evt['subject']) ?></div>
                                <div class="chip-time"><?= date('h:i', strtotime($evt['start_time'])) ?> - <?= date('h:i', strtotime($evt['end_time'])) ?></div>
                                <div class="chip-meta"><?= htmlspecialchars($evt['requester_name']) ?> • <?= htmlspecialchars($evt['department'] ?? '') ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </main>

        <aside class="sidebar">
    
    <?php
    // --- MINI CALENDAR LOGIC (Original) ---
    $mini_month = date('m');
    $mini_year = date('Y');
    $mini_monthName = date('F Y');

    $mini_firstDay = strtotime("$mini_year-$mini_month-01");
    $mini_totalDays = date('t', $mini_firstDay);
    $mini_startDayOfWeek = date('w', $mini_firstDay);

    $total_rooms = $pdo->query("SELECT count(*) FROM rooms")->fetchColumn();
    $daily_capacity_mins = $total_rooms * 660; 

    // Fetch Daily Stats
    $sql_stats = "SELECT 
                DAY(start_time) as day_num, 
                SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)) as total_mins
                FROM reservations 
                WHERE MONTH(start_time) = ? AND YEAR(start_time) = ?
                AND end_time >= NOW()
                AND (status IS NULL OR status != 'cancelled') 
                GROUP BY day_num";
    
    $stats_stmt = $pdo->prepare($sql_stats);
    $stats_stmt->execute([$mini_month, $mini_year]);
    $daily_stats = $stats_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    ?>

    <div class="widget">
        <h4 style="text-align:center; margin-bottom:10px;"><?= $mini_monthName ?></h4>
        
        <div class="mini-cal-grid">
            <span class="mini-day-label">S</span><span class="mini-day-label">M</span>
            <span class="mini-day-label">T</span><span class="mini-day-label">W</span>
            <span class="mini-day-label">T</span><span class="mini-day-label">F</span>
            <span class="mini-day-label">S</span>

            <?php for($i=0; $i < $mini_startDayOfWeek; $i++): ?>
                <div class="mini-day empty"></div>
            <?php endfor; ?>

            <?php for($day=1; $day <= $mini_totalDays; $day++): ?>
                <?php
                $class = '';
                $today_check = ($day == date('j') && $mini_month == date('m')) ? 'is-today' : '';

                if (isset($daily_stats[$day])) {
                    $mins_booked = $daily_stats[$day];
                    if ($mins_booked >= ($daily_capacity_mins * 0.8)) {
                        $class = 'fully-booked';
                    } else {
                        $class = 'has-booking';
                    }
                }
                ?>
                <div class="mini-day <?= $class ?> <?= $today_check ?>">
                <?= $day ?>
                </div>
            <?php endfor; ?>
        </div>
    </div>

<div class="widget">
    <h3 style="margin-bottom:15px;">Upcoming Reservations</h3>

    <div class="upcoming-list"> 
        <?php 
        $sql_upcoming = "SELECT r.*, rm.name as room_name 
                FROM reservations r 
                JOIN rooms rm ON r.room_id = rm.id 
                WHERE r.end_time > NOW() 
                AND status != 'cancelled'  
                ORDER BY r.start_time ASC";
        $upcoming = $pdo->query($sql_upcoming)->fetchAll();

        date_default_timezone_set('Asia/Manila');
        $current_time = time();
        ?>

        <?php if(count($upcoming) > 0): ?>
            <?php foreach($upcoming as $up): ?>
                
                <?php
                $start_ts = strtotime($up['start_time']);
                $end_ts   = strtotime($up['end_time']);
                
                $status_label = 'Upcoming';
                $icon_class = 'icon-blue';
                $now_badge = false;

                if (isset($up['status']) && $up['status'] === 'cancelled') {
                    $status_label = 'Cancelled';
                    $icon_class = 'icon-gray'; 
                } 
                elseif ($current_time >= $start_ts && $current_time <= $end_ts) {
                    $status_label = 'Ongoing';
                    $icon_class = 'icon-red';
                    $now_badge = true;
                }
                ?>
                
                <div class="upcoming-item"
                     style="cursor: pointer;" 
                     onclick="openModal(this)"
                     data-room="<?= htmlspecialchars($up['room_name']) ?>"
                     data-requester="<?= htmlspecialchars($up['requester_name']) ?>"
                     data-email="<?= htmlspecialchars($up['requester_email']) ?>"
                     data-subject="<?= htmlspecialchars($up['subject']) ?>"
                     data-department="<?= htmlspecialchars($up['department']) ?>"
                     data-notes="<?= htmlspecialchars($up['notes']) ?>"
                     data-time="<?= date('h:i A', strtotime($up['start_time'])) ?> - <?= date('h:i A', strtotime($up['end_time'])) ?>"
                     data-status="<?= $status_label ?>"
                     data-created="<?= !empty($evt['created_at']) ? date('M d, Y h:i A', strtotime($evt['created_at'])) : 'N/A' ?>"
                >
                <div class="icon-box <?= $icon_class ?>">📅</div>
                
                <div class="event-details">
                    <h4><?= htmlspecialchars($up['room_name']) ?></h4>
                    
                    <p style="font-size: 0.85rem; font-weight: 500; color: var(--text-main);">
                        <?= htmlspecialchars($up['requester_name']) ?>
                        <span style="color: var(--text-muted); font-size: 0.75rem; font-weight: 400;">
                            • <?= htmlspecialchars($up['department']) ?>
                        </span>
                    </p>
                </div>

                <div class="event-time" style="text-align: right; min-width: 85px;">
                    
                    <?php if($now_badge): ?>
                        <div style="color:#dc2626; font-size:0.7rem; font-weight:800; margin-bottom:2px; letter-spacing:0.5px; text-transform: uppercase;">
                            ● Now
                        </div>
                    <?php endif; ?>
                    
                    <?php if($status_label === 'Cancelled'): ?>
                        <div style="color:#94a3b8; font-size:0.7rem; font-weight:800; margin-bottom:2px; letter-spacing:0.5px; text-transform: uppercase;">
                            Cancelled
                        </div>
                    <?php endif; ?>

                    <div style="font-size:0.8rem; font-weight:600; white-space:nowrap;">
                        <?= date('h:i A', strtotime($up['start_time'])) ?> - <?= date('h:i A', strtotime($up['end_time'])) ?>
                    </div>
                    <div style="font-size:0.7rem; color:var(--text-muted); margin-top:2px;">
                        <?= date('M d', strtotime($up['start_time'])) ?>
                    </div>
                </div>
                </div>

            <?php endforeach; ?>
        <?php else: ?>
            <p style="color:var(--text-muted); padding:10px;">No upcoming bookings.</p>
        <?php endif; ?>

    </div> 
</div>

</aside>

    </div>
    
    <div id="eventModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalRoom">Room Name</h3>
                <button class="btn-close" onclick="closeModal()">&times;</button>
            </div>
            
            <div class="modal-row split-row">
                <div class="split-col">
                <span class="modal-label">Time</span>
                <div class="modal-value" id="modalTime">--</div>
                </div>
                <div class="split-col">
                <span class="modal-label">Status</span>
                <div class="modal-value" id="modalStatus" style="font-weight: 700; color: #0284c7;">--</div>
                </div>
            </div>

            <div class="modal-row split-row">
                <div class="split-col">
                <span class="modal-label">Booked By</span>
                <div class="modal-value" id="modalRequester">--</div>
                <div class="modal-value" id="modalEmail" style="font-size: 0.85rem; color: #64748b;">--</div>
                </div>
                <div class="split-col">
                <span class="modal-label">Department</span>
                <div class="modal-value" id="modalDept">--</div>
                </div>
            </div>

            <div class="modal-row split-row">
                <div class="split-col">
                <span class="modal-label">Purpose</span>
                <div class="modal-value" id="modalSubject">--</div>
                </div>
                <div class="split-col">
                <span class="modal-label">Date Requested</span>
                <div class="modal-value" id="modalCreated" style="font-size: 0.9rem; color: #64748b;">--</div>
                </div>
            </div>

            <div class="modal-row">
                <span class="modal-label">Additional Notes</span>
                <div class="modal-value notes-text" id="modalNotes">--</div>
            </div>
        </div>
    </div>

    <script>
        function openModal(element) {
            var room = element.getAttribute('data-room');
            var time = element.getAttribute('data-time');
            var requester = element.getAttribute('data-requester');
            var email = element.getAttribute('data-email');
            var dept = element.getAttribute('data-department');
            var subject = element.getAttribute('data-subject');
            var notes = element.getAttribute('data-notes');
            var status = element.getAttribute('data-status');
            var created = element.getAttribute('data-created');

            document.getElementById('modalRoom').textContent = room;
            document.getElementById('modalTime').textContent = time;
            document.getElementById('modalRequester').textContent = requester;
            document.getElementById('modalEmail').textContent = email;
            document.getElementById('modalDept').textContent = dept;
            document.getElementById('modalSubject').textContent = subject;

            var createdField = document.getElementById('modalCreated');
            if (createdField) {
                createdField.textContent = created ? created : 'N/A';
            }

            var statusEl = document.getElementById('modalStatus');
            statusEl.textContent = status;
            statusEl.style.color = '#0284c7'; 
            
            if(status === 'Ongoing') {
                statusEl.style.color = '#dc2626'; 
            } else if (status === 'Cancelled') {
                statusEl.style.color = '#94a3b8'; 
            } else if (status === 'Completed') {
                statusEl.style.color = '#15803d'; 
            }

            var notesField = document.getElementById('modalNotes');
            if(notes && notes.trim().length > 0) {
                notesField.textContent = notes;
                notesField.style.display = 'block';
                if(notesField.previousElementSibling) {
                notesField.previousElementSibling.style.display = 'block';
                }
            } else {
                notesField.style.display = 'none';
                if(notesField.previousElementSibling) {
                notesField.previousElementSibling.style.display = 'none';
                }
            }

            var modal = document.getElementById('eventModal');
            modal.classList.add("show");
        }

        function closeModal() {
            var modal = document.getElementById('eventModal');
            modal.classList.remove("show");
        }

        window.onclick = function(event) {
            var modal = document.getElementById('eventModal');
            if (event.target == modal) {
                closeModal();
            }
        }

       
            
    </script>
</body>
</html>

