<?php
session_start(); // Start the session
include 'config.php'; // database connection

// Check whether user is logged in already
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$email = $_SESSION['email'];

// Fetch user details
$sql = "SELECT first_name, last_name FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$conn->close();

// Fetch user reservations
$sql = "SELECT r.reservation_id, d.name AS destination, r.departure_date, r.seat_class, r.number_of_passengers, r.status
        FROM reservations r
        JOIN flights f ON r.flight_id = f.flight_id
        JOIN destinations d ON f.destination_id = d.destination_id
        WHERE r.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$reservations = $stmt->get_result();

$stmt->close();
$conn->close()
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
				<li><a href="logout.php">Registration</a></li>
			</ul>
		</nav>
	    <header>
			<h1><a href="holidayMatch.html">Holiday Match</a></h1>
            <h1>Welcome, <?php echo htmlspecialchars($user['first_name']) . ' ' . htmlspecialchars($user['last_name']); ?></h1>
            <p><?php echo htmlspecialchars($email); ?></p>
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
            <h1>Your Reservations</h1>
            <?php if ($reservations->num_rows > 0): ?>
                <ul>
                    <?php while ($row = $reservations->fetch_assoc()): ?>
                        <li>
                            <strong>Destination:</strong> <?php echo htmlspecialchars($row['destination']); ?><br>
                            <strong>Departure:</strong> <?php echo $row['departure_date']; ?><br>
                            <strong>Class:</strong> <?php echo ucfirst($row['seat_class']); ?><br>
                            <strong>Passengers:</strong> <?php echo $row['number_of_passengers']; ?><br>
                            <strong>Status:</strong> <?php echo ucfirst($row['status']); ?><br>
                            <a href="modify_booking.php?reservation_id=<?php echo $row['reservation_id']; ?>">Modify</a> |
                            <a href="cancel_booking.php?reservation_id=<?php echo $row['reservation_id']; ?>">Cancel</a>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>You have no reservations yet. <a href="book.php">Book a flight now</a>.</p>
            <?php endif; ?>
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