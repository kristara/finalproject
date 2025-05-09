<?php
include 'config.php'; // database connection

// Check if the form was submitted and if the reservation ID is provided
if (isset($_SERVER["REQUEST_METHOD"]) && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reservation_id'])) {
    // Retrieve the reservation ID from the form
    $reservation_id = intval($_POST['reservation_id']);

    //validate that a reservation ID is provided
    if (!$reservation_id) {
        die("Invalid reservation ID.");
    }

    // check if the reservation exists
    $sql = "SELECT status FROM reservations WHERE reservation_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // if the reservation exists
    if ($result->num_rows > 0) {
        $reservation = $result->fetch_assoc();

        // Check if the reservation is cancelled
        if ($reservation['status'] === 'cancelled') {
            echo "<p>This booking is already cancelled.</p>";
        } else {
            // Update the reservation status to "cancelled"
            $update_sql = "UPDATE reservations SET status = 'cancelled' WHERE reservation_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $reservation_id);
            $update_stmt->execute();

            if ($update_stmt->affected_rows > 0) {
                echo "<h3>Booking Cancelled Successfully!</h3>";
                echo "<p>Your booking has been successfully cancelled.</p>";
            } else {
                echo "<p>Failed to cancel the booking. Please try again.</p>";
            }

            $update_stmt->close();
        }
    } else {
        // If the reservation does not exist
        echo "<p>No booking found with the provided reservation number.</p>";
    }

    // Close the statement
    $stmt->close();
} else {
    // If the script is accessed directly or without a reservation ID then deny access
    echo "<p>Invalid request. Please use the booking page to cancel your reservation.</p>";
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
				<?php
                session_start();
                if (isset($_SESSION['user_id'])) {
                    echo '<li><a href="account.php">My Account</a></li>';
                    echo '<li><a href="logout.php">Log Out</a></li>';
                } else {
                    echo '<li><a href="registration.php">Register</a></li>';
                    echo '<li><a href="login.php">Log In</a></li>';
                }
                ?>
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
			<h2>Cancel Booking</h2>
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