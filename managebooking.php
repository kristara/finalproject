<?php
include 'config.php'; // database connection
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
				<li><a href="book.php">Book</a></li>
				<li><a href="managebooking.php">Manage Booking</a></li>
			</ul>
		</nav>
		<!-- Section for looking up a booking by reservation number and last name -->
		<h2>Look Up Your Booking</h2>
        <form action="managebooking.php" method="POST">
            <label for="reservation_id">Reservation Number:</label>
            <input type="text" name="reservation_id" id="reservation_id" required><br><br>

            <label for="last_name">Last Name:</label>
            <input type="text" name="last_name" id="last_name" required><br><br>

            <button type="submit">Look Up Booking</button>
        </form>

        <?php
        // check if form is submitted
        if (isset($_SERVER["REQUEST_METHOD"]) && $_SERVER["REQUEST_METHOD"] == "POST") {
            // Retrieve user input
			$reservation_id = intval($_POST['reservation_id']);
            $last_name = trim($_POST['last_name']);

            // SQL query to find the booking based on reservation number and last name
            $sql = "SELECT r.reservation_id, f.origin, d.name AS destination, f.departure_time,
                           r.number_of_passengers, r.seat_class, r.status, u.last_name
                    FROM reservations r
                    JOIN flights f ON r.flight_id = f.flight_id
                    JOIN destinations d ON f.destination_id = d.destination_id
                    JOIN users u ON r.user_id = u.user_id
                    WHERE r.reservation_id = ? AND u.last_name = ?";
			// prepared statement to prevent SQL injection
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $reservation_id, $last_name);
            $stmt->execute();
            $result = $stmt->get_result();
			// If the query fails then display an error
            if (!$result) {
                echo "<p>Error: " . $conn->error . "</p>";
            }
			// If a matching booking is found then display the details
			elseif ($result->num_rows > 0) {
                $booking = $result->fetch_assoc();
                echo "<h3>Booking Details:</h3>";
                echo "<p><strong>Reservation Number:</strong> " . $booking['reservation_id'] . "</p>";
                echo "<p><strong>Name:</strong> " . htmlspecialchars($last_name) . "</p>";
                echo "<p><strong>From:</strong> " . htmlspecialchars($booking['origin']) . "</p>";
                echo "<p><strong>To:</strong> " . htmlspecialchars($booking['destination']) . "</p>";
                echo "<p><strong>Departure:</strong> " . $booking['departure_time'] . "</p>";
                echo "<p><strong>Passengers:</strong> " . $booking['number_of_passengers'] . "</p>";
                echo "<p><strong>Class:</strong> " . ucfirst($booking['seat_class']) . "</p>";
                echo "<p><strong>Status:</strong> " . ucfirst($booking['status']) . "</p>";

                // Show modify and cancel options
                if ($booking['status'] !== 'cancelled') {
                    echo "<h3>Modify Booking</h3>";
                    echo "<form action='modify_booking.php' method='POST'>";
                    echo "<input type='hidden' name='reservation_id' value='" . $booking['reservation_id'] . "'>";
                    echo "<label>Change Departure Date:</label>";
                    echo "<input type='date' name='new_departure_date' value='" . substr($booking['departure_time'], 0, 10) . "' required><br><br>";

                    echo "<label>Change Number of Passengers:</label>";
                    echo "<input type='number' name='new_passengers' value='" . $booking['number_of_passengers'] . "' min='1' required><br><br>";

                    echo "<label>Change Seat Class:</label>";
                    echo "<select name='new_seat_class' required>
                            <option value='economy' " . ($booking['seat_class'] == 'economy' ? 'selected' : '') . ">Economy</option>
                            <option value='business' " . ($booking['seat_class'] == 'business' ? 'selected' : '') . ">Business</option>
                            <option value='first' " . ($booking['seat_class'] == 'first' ? 'selected' : '') . ">First Class</option>
                          	</select><br><br>";

                    echo "<button type='submit'>Save Changes</button>";
                    echo "</form>";
                } else {
                    echo "<p><em>This booking is already cancelled.</em></p>";
                }
            } else {
                echo "<p>No booking found with that information.</p>";
            }
        }

		$conn->close();
        ?>

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