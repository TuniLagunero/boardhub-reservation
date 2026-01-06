<?php
require 'config/db.php';
$msg = "";
$msg_type = "";

date_default_timezone_set('Asia/Manila');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $code = trim($_POST['booking_code']);
    $stmt = $pdo->prepare("SELECT * FROM reservations WHERE booking_code = ?");
    $stmt->execute([$code]);
    $reservation = $stmt->fetch();

    if ($reservation) {
        $current_time = time();
        $start_ts = strtotime($reservation['start_time']);
        $end_ts   = strtotime($reservation['end_time']);

        if ($reservation['status'] === 'cancelled') {
            $msg = "This reservation is already cancelled.";
            $msg_type = "error";
        }
        elseif ($current_time > $end_ts) {
            $msg = "You cannot cancel a past meeting.";
            $msg_type = "error";
        }
        elseif ($current_time >= $start_ts && $current_time <= $end_ts) {
            $msg = "You cannot cancel a meeting that is currently in progress.";
            $msg_type = "error";
        }
        else {
            $update = $pdo->prepare("UPDATE reservations SET status = 'cancelled', cancelled_at = NOW() WHERE id = ?");
            $update->execute([$reservation['id']]);
            
            $msg = "Reservation has been successfully cancelled.";
            $msg_type = "success";
        }

    } else {
        $msg = "Invalid Cancellation Code. Please check and try again.";
        $msg_type = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Reservation</title>
    
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        .alert.success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert.error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .cancel-container {
            max-width: 500px; 
            margin: 50px auto; 
            padding: 0 20px;
        }

        @media (max-width: 600px) {
            .cancel-container {
                margin-top: 20px;
                padding: 15px;
                width: 100%;
            }
            .page-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    
    <nav class="navbar" style="height: 75px !important; min-height: 75px !important;">
        <a href="index.php" class="logo">
            <img src="/boardhub/images/amertron_logo.png" alt="Amertron" style="height: 50px; width: auto; margin-right: 10px;">
        </a>
        <div class="nav-actions">
            <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </nav>

    <div class="cancel-container">
        <h1 class="page-title" style="text-align:center;">Cancel Reservation</h1>
        
        <?php if($msg): ?>
            <div class="alert <?= $msg_type ?>"><?= $msg ?></div>
            <?php if($msg_type == 'success'): ?>
                <a href="index.php" class="btn-primary" style="display:block; text-align:center; text-decoration:none;">Return to Dashboard</a>
                <?php exit; ?>
            <?php endif; ?>
        <?php endif; ?>

        <div class="form-step">
            <p style="color:var(--text-muted); margin-bottom:20px; text-align:center;">
                Enter the unique code provided when you booked the room.
            </p>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label" style="text-align: center;">Cancellation Code</label>
                    <input type="text" name="booking_code" class="form-input" placeholder="e.g. RES-8X92B" required style="text-align:center; font-size:1.2rem; letter-spacing:1px; text-transform:uppercase;">
                </div>
                
                <button type="submit" class="btn-primary" style="width:100%; background-color:#ef4444; margin-top:10px;">Cancel Booking</button>
            </form>
        </div>
    </div>
</body>
</html>