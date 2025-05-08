<?php
include 'config.php'; // database connection

// Check if the form is submitted
if (isset($_SERVER["REQUEST_METHOD"]) && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reservation_id'])) {
    // Retrieve user inputs
    $reservation_id = intval($_POST['reservation_id']);
    $new_departure_date = $_POST['new_departure_date'];
    $new_passengers = intval($_POST['new_passengers']);
    $new_seat_class = $_POST['new_seat_class'];

    // Validate the inputs
    if (empty($reservation_id) || empty($new_departure_date) || $new_passengers <= 0 || empty($new_seat_class)) {
        die("Invalid input. Please provide all required details.");
    }

    // check if the reservation exists
    $sql = "SELECT r.reservation_id, r.status, f.flight_id
            FROM reservations r
            JOIN flights f ON r.flight_id = f.flight_id
            WHERE r.reservation_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $reservation = $result->fetch_assoc();

        // ensure the booking is not cancelled
        if ($reservation['status'] === 'cancelled') {
            echo "<h3>This booking is already cancelled and cannot be modified.</h3>";
        } else {
            // update the booking with new values
            $update_sql = "UPDATE reservations
                           SET departure_date = ?, number_of_passengers = ?, seat_class = ?
                           WHERE reservation_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("sisi", $new_departure_date, $new_passengers, $new_seat_class, $reservation_id);
            $update_stmt->execute();

            if ($update_stmt->affected_rows > 0) {
                echo "<h3>Booking Updated Successfully!</h3>";
                echo "<p>Your booking has been updated with the following details:</p>";
                echo "<p><strong>Departure Date:</strong> $new_departure_date</p>";
                echo "<p><strong>Number of Passengers:</strong> $new_passengers</p>";
                echo "<p><strong>Seat Class:</strong> " . ucfirst($new_seat_class) . "</p>";
            } else {
                echo "<p>No changes were made to your booking.</p>";
            }
            $update_stmt->close();
        }
    } else {
        echo "<p>No booking found with the provided reservation number.</p>";
    }
    $stmt->close();
} else {
    echo "<p>Invalid request. Please use the booking page to modify your reservation.</p>";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<title>holidayMatch</title>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css.css">
</head>

<body>
	<div id="pagewrapper">
		<nav id="headerlinks">
			<ul>
				<li><a href="registration.html">Registration</a></li>
				<li><a href="login.html">Log in</a></li>
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
			<p><a href="managebooking.php">Return to Manage Booking</a></p>
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