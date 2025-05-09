<?php
session_start(); // Start the session
include 'config.php'; // database connection

// Check whether user is logged in already
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = ""; // Initialise message variable

// Fetch user details
$sql = "SELECT first_name, last_name, email, phone_number, city, country FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();
$stmt->close();

// Check if user is found
if (!$user) {
    die("User not found.");
}

// Fetch user reservations
$reservation_sql = "SELECT r.reservation_id, f.origin, d.name AS destination, r.departure_date, 
                    r.seat_class, r.number_of_passengers, r.status, r.total_price
                    FROM reservations r
                    JOIN flights f ON r.flight_id = f.flight_id
                    JOIN destinations d ON f.destination_id = d.destination_id
                    WHERE r.user_id = ?";
$res_stmt = $conn->prepare($reservation_sql);
$res_stmt->bind_param("i", $user_id);
$res_stmt->execute();
$reservations = $res_stmt->get_result();
$res_stmt->close();
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
                <li><a href="logout.php">Log Out</a></li>
            </ul>
        </nav>
	    <header>
            <h1>Welcome <?php echo htmlspecialchars($user['first_name']) . ' ' . htmlspecialchars($user['last_name']); ?></h1>
            <p><?php echo htmlspecialchars($user['email']); ?></p>
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
            <h1>Your Account</h1>
                <h3>Account Details</h3>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone_number']); ?></p>
                <p><strong>City:</strong> <?php echo htmlspecialchars($user['city']); ?></p>
                <p><strong>Country:</strong> <?php echo htmlspecialchars($user['country']); ?></p>

            <h2>Your Reservations</h2>
            <?php if ($reservations->num_rows > 0): ?>
                <ul style="list-style-type: none; padding: 0;">
                    <?php while ($res = $reservations->fetch_assoc()): ?>
                        <li style="padding: 10px; border-bottom: 1px solid #ccc;">
                            <strong>Reservation #:</strong> <?php echo $res['reservation_id']; ?><br>
                            <strong>From:</strong> <?php echo htmlspecialchars($res['origin']); ?> 
                            <strong>To:</strong> <?php echo htmlspecialchars($res['destination']); ?><br>
                            <strong>Departure:</strong> <?php echo $res['departure_date']; ?><br>
                            <strong>Class:</strong> <?php echo ucfirst($res['seat_class']); ?> 
                            <strong>Passengers:</strong> <?php echo $res['number_of_passengers']; ?><br>
                            <strong>Status:</strong> <?php echo ucfirst($res['status']); ?> 
                            <strong>Total Price:</strong> £<?php echo number_format($res['total_price'], 2); ?>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>You have no reservations. <a href="book.php">Book a flight</a>.</p>
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