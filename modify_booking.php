<?php
include 'config.php'; // database connection

$message = ""; // Initialise message variable
$redirect = false; // Flag to control redirection

// Check if the form is submitted
if (isset($_SERVER["REQUEST_METHOD"]) && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reservation_id'])) {
    // Retrieve user inputs
    $reservation_id = intval($_POST['reservation_id']);
    $new_departure_date = trim($_POST['new_departure_date']);
    $new_passengers = intval($_POST['new_passengers']);
    $new_seat_class = trim($_POST['new_seat_class']);

    // Validate the inputs
    if (empty($reservation_id) || empty($new_departure_date) || $new_passengers <= 0 || empty($new_seat_class)) {
        $message = "Please provide all required details.";
    } else {
        // Validate date format
        if (!strtotime($new_departure_date)) {
            $message = "Invalid date format for departure date.";
        } else {
            // check if the reservation exists
            $sql = "SELECT r.reservation_id, r.status
                    FROM reservations r
                    WHERE r.reservation_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $reservation_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $reservation = $result->fetch_assoc();

                // Ensure the booking is not cancelled
                if ($reservation['status'] === 'cancelled') {
                    $message = "<h3>This booking is already cancelled and cannot be modified.</h3>";
                } else {
                    // Update the booking with new values
                    $update_sql = "UPDATE reservations
                                   SET departure_date = ?, number_of_passengers = ?, seat_class = ?
                                   WHERE reservation_id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("sisi", $new_departure_date, $new_passengers, $new_seat_class, $reservation_id);
                    $update_stmt->execute();

                    if ($update_stmt->affected_rows > 0) {
                        $message = "<h3>Booking Updated Successfully!</h3>
                                    <p>Your booking has been updated with the following details:</p>
                                    <ul>
                                        <li><strong>Departure Date:</strong> " . htmlspecialchars($new_departure_date) . "</li>
                                        <li><strong>Number of Passengers:</strong> " . htmlspecialchars($new_passengers) . "</li>
                                        <li><strong>Seat Class:</strong> " . ucfirst(htmlspecialchars($new_seat_class)) . "</li>
                                    </ul>
                                    <p><strong>Redirecting to Manage Booking in 3 seconds...</strong></p>";
                                $redirect = true; // trigger redirect
                    } else {
                        $message = "<p>No changes were made to your booking.</p>";
                    }
                    $update_stmt->close();
                }
            } else {
                $message = "<p>No booking found with the provided reservation number.</p>";
            }
            $stmt->close();
        }
    }
} else {
    $message = "<p>Invalid request. Please use the booking page to modify your reservation.</p>";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<title>Modify Booking - HolidayMatch</title>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css.css">
    <script>
        // Auto redirect after modification
        <?php if ($redirect) : ?>
        setTimeout(() => {
            window.location.href = "managebooking.php";
        }, 3000); // redirect after 3 seconds
        <?php endif; ?>
    </script>
</head>
<body>
	<div id="pagewrapper">
		<nav id="headerlinks">
			<ul>
				<li><a href="registration.html">Registration</a></li>
				<li><a href="login.php">Log in</a></li>
			</ul>
		</nav>
	    <header>
			<h1><a href="holidayMatch.html">Holiday Match</a></h1>
		</header>
	    <nav id="primarynav">
			<ul>
				<li class="current"><a href="holidayMatch.html">Home</a></li>
				<li><a href="explore.html">Explore</a></li>
				<li><a href="/book.php">Book</a></li>
				<li><a href="/managebooking.php">Manage Booking</a></li>
			</ul>
		</nav>

		<section>
			<h2>Modify Booking</h2>
            <div class="message-box">
                <?php echo $message; ?>
            </div>
            <div class="action-links">
                <p><a href="managebooking.php">Return to Manage Booking</a></p>
                <p><a href="holidayMatch.html">Go to Home Page</a></p>
                <p><a href="book.php">Go to Book a Flight</a></p>
            </div>
		</section>

		<footer>
			<nav id="footerlinks">
				<ul>
					<li><a href="termsofuse.html"> Terms of Use &#124;</a></li>
					<li><a href="copyright.html">Copyright &#124;</a></li>
					<li><a href="contactus.html"> Contact Us</a></li>
				</ul>
			</nav>
		</footer>
	</div>
</body>
</html>