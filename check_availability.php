<?php
require 'config/db.php';

date_default_timezone_set('Asia/Manila'); 

header('Content-Type: application/json');

if (isset($_GET['room_id']) && isset($_GET['date'])) {
    $room_id = $_GET['room_id'];
    $date = $_GET['date'];
    $stmt = $pdo->prepare("SELECT start_time, end_time FROM reservations WHERE room_id = ? AND DATE(start_time) = ? AND status != 'cancelled'");
    $stmt->execute([$room_id, $date]);
    $reservations = $stmt->fetchAll();

    $booked_slots = [];
    foreach ($reservations as $res) {
        $start_ts = strtotime($res['start_time']); 
        $end_ts   = strtotime($res['end_time']);   

        $current = $start_ts;
        while ($current < $end_ts) {
            $booked_slots[] = date('H:i', $current);
            $current = strtotime('+30 minutes', $current);
        }
    }
    if ($date == date('Y-m-d')) {
        $now = time();
        $op_start = strtotime("$date 07:00");
        $op_end   = strtotime("$date 23:30");
        
        $curr_slot = $op_start;
        while ($curr_slot <= $op_end) {
            if ($curr_slot < $now) {
                $time_str = date('H:i', $curr_slot);
                
                if (!in_array($time_str, $booked_slots)) {
                    $booked_slots[] = $time_str;
                }
            }
            $curr_slot = strtotime('+30 minutes', $curr_slot);
        }
    }

    echo json_encode($booked_slots);
} else {
    echo json_encode([]);
}
?>