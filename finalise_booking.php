<?php
session_start(); // start the session
require_once 'config.php'; // database connection

// validate incoming form data
if (!isset($_SESSION['pending_booking'])) {
    die("Missing booking details.");
}

$booking = $_SESSION['pending_booking'];
$flight_id = $booking['flight_id'];
$departure_date = $booking['departure_date'];
$seat_class = $booking['seat_class'];
$number_of_passengers = $booking['number_of_passengers'];
$total_price = $booking['total_price'];

//check if user is  logged in
$isLoggedIn = isset($_SESSION['user_id']);
$user_details = [];

if ($isLoggedIn) {
    // Fetch user details
    $stmt = $conn->prepare("SELECT first_name, last_name, dob, email, city, post_code, country FROM users WHERE user_id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $user_details = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<title>My Account</title>
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

        <!-- shared header and primary nav -->
        <?php include 'primarynav.php'; ?>

        <section class="centered-form">
            <h1>Finalise Booking</h1>

            <h2>Flight Details:</h2>
            <p><strong>Departure Date:</strong> <?= htmlspecialchars($departure_date) ?></p>
            <p><strong>Class:</strong> <?= htmlspecialchars($seat_class) ?></p>
            <p><strong>Number of Passengers:</strong> <?= htmlspecialchars($number_of_passengers) ?></p>
            <p><strong>Total Price:</strong> £<?= number_format($total_price, 2) ?></p>

            <h2>Your Details:</h2>
            <form action="confirm_reservation.php" method="POST">
                <input type="hidden" name="flight_id" value="<?= $flight_id ?>">
                <input type="hidden" name="departure_date" value="<?= $departure_date ?>">
                <input type="hidden" name="seat_class" value="<?= $seat_class ?>">
                <input type="hidden" name="number_of_passengers" value="<?= $number_of_passengers ?>">
                <input type="hidden" name="total_price" value="<?= $total_price ?>">

                <label>First Name:</label>
                <input type="text" name="first_name" value="<?= htmlspecialchars($user_details['first_name'] ?? '') ?>" required><br>

                <label>Last Name:</label>
                <input type="text" name="last_name" value="<?= htmlspecialchars($user_details['last_name'] ?? '') ?>" required><br>

                <label>Email:</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user_details['email'] ?? '') ?>" required><br>

                <label>City:</label>
                <input type="text" name="city" value="<?= htmlspecialchars($user_details['city'] ?? '') ?>" required><br>

                <label>Postcode:</label>
                <input type="text" name="post_code" value="<?= htmlspecialchars($user_details['post_code'] ?? '') ?>" required><br>

                <label>Country:</label>
                <input type="text" name="country" value="<?= htmlspecialchars($user_details['country'] ?? '') ?>" required><br>

                <button type="submit">Book Now</button>
            </form>

            <?php if (!$isLoggedIn): ?>
                <p>Have an account? <a href="login.php">Log in</a> or <a href="registration.php">Register</a> to save your details.</p>
            <?php endif; ?>
        </main>
        <?php include 'footerlinks.php'; ?>
    </div>
</body>
</html>

<?php
$conn->close();
?>