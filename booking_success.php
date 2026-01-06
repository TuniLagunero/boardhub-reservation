<?php 
session_start();
require 'config/db.php';

// Ensure correct timezone for the "Date Requested" display
date_default_timezone_set('Asia/Manila');

if(!isset($_SESSION['booking_code'])) { header("Location: index.php"); exit; }
$code = $_SESSION['booking_code'];

$sql = "SELECT r.*, rm.name as room_name 
        FROM reservations r 
        JOIN rooms rm ON r.room_id = rm.id 
        WHERE r.booking_code = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$code]);
$booking = $stmt->fetch();

if(!$booking) {
    die("Booking not found.");
}

// Format Dates
$dateStr = date('F d, Y', strtotime($booking['start_time']));
$timeStr = date('h:i A', strtotime($booking['start_time'])) . ' - ' . date('h:i A', strtotime($booking['end_time']));
$dateRequested = date('F d, Y h:i A', strtotime($booking['created_at'])); // <--- NEW VARIABLE
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed</title>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="success-page">
    
    <nav class="navbar" style="flex: none; height: 75px !important; min-height: 75px !important; z-index: 10;">
        <a href="index.php" class="logo">
            <img src="/boardhub/images/amertron_logo.png" alt="Amertron" style="height: 50px; width: auto; margin-right: 10px;">
        </a>
        <div class="nav-actions">
            <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </nav>

    <div class="scroll-container">
        
        <div class="success-page-container">
            
            <div class="success-icon-circle">
                <i class="ph-bold ph-check"></i>
            </div>
            <h1 class="success-title">Reservation Confirmed!</h1>
            <p class="success-subtext">
                Your room has been successfully reserved. Below are the details of your booking.
            </p>

            <div class="booking-card">
                <div class="card-header">Booking Details</div>
                
                <div class="details-grid">
                    <div class="detail-item">
                        <span class="detail-label">Room Name</span>
                        <span class="detail-value"><?= htmlspecialchars($booking['room_name']) ?></span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Date</span>
                        <span class="detail-value"><?= $dateStr ?></span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Time</span>
                        <span class="detail-value"><?= $timeStr ?></span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Booked By</span>
                        <span class="detail-value"><?= htmlspecialchars($booking['requester_name']) ?></span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Date Requested</span>
                        <span class="detail-value" style=" font-size: 0.95rem;"><?= $dateRequested ?></span>
                    </div>

                </div>
            </div>

            <div class="cancellation-code-container">
                <div class="code-content">
                    <span class="code-label">CANCELLATION CODE</span>
                    <span class="code-text" id="codeText"><?= htmlspecialchars($booking['booking_code']) ?></span>
                    <span class="code-note">Please save this code for future reference.</span>
                </div>
                
                <button class="btn-copy" id="copyBtn" title="Copy to clipboard" data-code="<?= htmlspecialchars($booking['booking_code']) ?>">
                    <i class="ph-bold ph-copy"></i>
                </button>
            </div>

            <div class="action-buttons">
                <a href="reserve.php" class="btn btn-primary">Book Another Room</a>
            </div>

        </div> 
    </div>

    <script>
        document.getElementById('copyBtn').addEventListener('click', function() {
            const codeToCopy = this.getAttribute('data-code');
            
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(codeToCopy).then(() => {
                    showSuccess(this);
                }).catch(err => {
                    fallbackCopyTextToClipboard(codeToCopy, this);
                });
            } else {
                fallbackCopyTextToClipboard(codeToCopy, this);
            }
        });

        function fallbackCopyTextToClipboard(text, btnElement) {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.top = "0";
            textArea.style.left = "0";
            textArea.style.position = "fixed";

            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    showSuccess(btnElement);
                } else {
                    alert('Copy failed. Please copy manually: ' + text);
                }
            } catch (err) {
                console.error('Fallback: Oops, unable to copy', err);
            }

            document.body.removeChild(textArea);
        }

        function showSuccess(btnElement) {
            const icon = btnElement.querySelector('i');
            const originalClass = icon.className;
            
            icon.className = 'ph-bold ph-check';
            icon.style.color = '#10b981';
            btnElement.style.borderColor = '#10b981';

            setTimeout(() => {
                icon.className = originalClass;
                icon.style.color = '';
                btnElement.style.borderColor = '';
            }, 2000);
        }
    </script>

</body>
</html>