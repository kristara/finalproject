<?php
session_start(); // start the session
require_once 'config.php'; // database connection

$message = ""; // initialise message variable

// check request
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation_id'])) {
    $reservation_id = intval($_POST['reservation_id']);

    // ensure the reservation ID is valid
    if ($reservation_id <= 0) {
        $message = "<h1>Error: Invalid reservation ID.</h1>";
    } else {
        // if user is logged in then use their user ID for cancellation
        if (isset($_SESSION['user_id'])) {
            $stmt = $conn->prepare("DELETE FROM reservations WHERE reservation_id = ? AND user_id = ?");
            $stmt->bind_param('ii', $reservation_id, $_SESSION['user_id']);
        } else {
            // for guests the directly delete without user_id
            if (isset($_SESSION['reservation_id'], $_SESSION['reservation_name'])) {
                $session_reservation_id = $_SESSION['reservation_id'];
                $lastName = $_SESSION['reservation_name'];

                // check if session reservation ID matches the submitted ID
                if ($session_reservation_id === $reservation_id) {
                    $stmt = $conn->prepare("
                        DELETE FROM reservations
                        WHERE reservation_id = ?
                        AND user_id = (
                            SELECT user_id FROM users
                            WHERE LOWER(last_name) = LOWER(?)
                            LIMIT 1
                        )
                    ");
                    $stmt->bind_param('is', $reservation_id, $lastName);
                } else {
                    $message = "<h1>Error: Reservation ID mismatch.</h1>";
                }
            } else {
                $message = "<h1>Error: No booking found to cancel. (Session Missing)</h1>";
            }
        }

        // cancellation
        if (isset($stmt)) {
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                // session reservation for guests
                unset($_SESSION['reservation_id'], $_SESSION['reservation_name']);
                $message = "<h1>Booking Cancelled Successfully!</h1><p>Your booking has been cancelled.</p>";
            } else {
                $message = "<h1>Error: No matching booking found to cancel.</h1>";
            }
            $stmt->close();
        } else {
            $message = "<h1>Error: Cancellation process failed.</h1>";
        }
    }
} else {
    $message = "<h1>Error: No booking found to cancel.</h1>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cancel Booking</title>
    <link rel="stylesheet" href="css.css">
</head>
<body>
    <div id="pagewrapper">
        <?php include 'primarynav.php'; ?>

        <main class="centered-form">
            <h1>Cancel Booking</h1>

            <div class="message-box">
                <?= $message ?>
            </div>
            <p><a href="managebooking.php">Return to Manage Booking</a></p>
        </main>

        <?php include 'footerlinks.php'; ?>
    </div>
</body>
</html>

<?php
$conn->close();
?>
