<?php
session_start(); // start the session
require_once 'config.php'; // database connection

$message = ""; // initialise message variable

// grab the request method to avoid undefined index
$method = $_SERVER['REQUEST_METHOD'] ?? '';

// Check if the form was submitted and if the reservation ID is provided
if ($method === 'POST' && !empty($_POST['reservation_id'])) {
    // Retrieve the reservation ID from the form
    $reservation_id = intval($_POST['reservation_id']);

    //validate that a reservation ID is provided
    if ($reservation_id <= 0) {
        $message = "<p class='error-message'>Invalid reservation ID.</p>";
    } else {
        //check current status
        $stmt = $conn->prepare("
            SELECT status
            FROM reservations
            WHERE reservation_id = ?
        ");
        $stmt->bind_param('i', $reservation_id);
        $stmt->execute();
        $result = $stmt->get_result();

        // check if reservation exists then fetch status
        if ($row = $result->fetch_assoc()) {
            if ($row['status'] === 'cancelled') {
                $message = "<p>This booking is already cancelled.</p>";
            } else {
                 // update the reservation status to "cancelled"
                $upd = $conn->prepare("
                    UPDATE reservations
                    SET status = 'cancelled'
                    WHERE reservation_id = ?
                ");
                $upd->bind_param('i', $reservation_id);
                $upd->execute();
                //check if update succeeded
                if ($upd->affected_rows > 0) {
                    $message = "
                        <h1>Booking Cancelled Successfully!</h1>
                        <p>Your booking has been cancelled.</p>
                    ";
                } else {
                    $message = "<p class='error-message'>Failed to cancel the booking. Please try again.</p>";
                }
                $upd->close();
            }
        } else {
        // If the reservation does not exist
            $message = "<p class='error-message'>No booking found with that reservation number.</p>";
        }
        // close the statement
        $stmt->close();
    }
} else {
    // if not a valid request
    $message = "<p>Invalid request. Please use the booking page to cancel your reservation.</p>";
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<title>Cancel Booking</title>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css.css">
</head>

<body>
	<div id="pagewrapper">
		<nav id="headerlinks">
			<ul>
				<?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="account.php">My Account</a></li>
                    <li><a href="logout.php">Log Out</a></li>
                <?php else: ?>
                    <li><a href="registration.php">Register</a></li>
                    <li><a href="login.php">Log In</a></li>
                <?php endif; ?>
			</ul>
		</nav>

        <!-- shared header and primary nav -->
	    <?php include 'primarynav.php'; ?>

	    <main>
		    <section>
			    <h1>Cancel Booking</h1>
                <div class="message-box">
                    <?= htmlspecialchars($message) ?>
                </div>
                <p><a href="managebooking.php">Return to Manage Booking</a></p>
            </section>
        </main>

        <!-- shared footer -->
        <?php include 'footerlinks.php'; ?>
	</div>
</body>
</html>