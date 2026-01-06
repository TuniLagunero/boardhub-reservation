<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $room_id = $_POST['room_id'];
    $date = $_POST['date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    
    $requester = $_POST['requester_name'];
    $dept = $_POST['department'];
    $subject = $_POST['subject'];
    $notes = $_POST['notes'];

    if (!$room_id || !$date || !$start_time || !$end_time) {
        die("Error: Missing required fields. <a href='reserve.php'>Go back</a>");
    }

    $start_dt = $date . ' ' . $start_time;
    $end_dt = $date . ' ' . $end_time;

    $seconds = strtotime($end_dt) - strtotime($start_dt);
    $minutes = $seconds / 60;

    if ($minutes > 120) die("Error: Max duration is 2 hours. <a href='reserve.php'>Go back</a>");
    if ($minutes < 30)  die("Error: Min duration is 30 minutes. <a href='reserve.php'>Go back</a>");

    $stmt = $pdo->prepare("SELECT count(*) FROM reservations WHERE room_id = ? AND start_time < ? AND end_time > ?");
    $stmt->execute([$room_id, $end_dt, $start_dt]);
    
    if ($stmt->fetchColumn() > 0) {
        die("Error: This time slot is already taken. <a href='reserve.php'>Go back</a>");
    }

    $random_str = strtoupper(substr(md5(uniqid(rand(), true)), 0, 5));
    $booking_code = "RES-" . $random_str;

    $sql = "INSERT INTO reservations (booking_code, room_id, requester_name, department, subject, notes, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    try {
        if ($stmt->execute([$booking_code, $room_id, $requester, $dept, $subject, $notes, $start_dt, $end_dt])) {

            $_SESSION['booking_code'] = $booking_code;
            header("Location: booking_success.php");
            exit;
            
        } else {
            echo "Failed to save reservation.";
        }
    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
}
?>