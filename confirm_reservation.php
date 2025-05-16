<?php
session_start(); // start the session
require_once 'config.php'; // database connection

// validate incoming form data
if (!isset($_POST['flight_id'], $_POST['departure_date'], $_POST['seat_class'], $_POST['number_of_passengers'], $_POST['total_price'])) {
    die("Missing booking details.");
}

$flight_id = intval($_POST['flight_id']);
$departure_date = $_POST['departure_date'];
$seat_class = $_POST['seat_class'];
$number_of_passengers = intval($_POST['number_of_passengers']);
$total_price = floatval($_POST['total_price']);

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// user details
if ($isLoggedIn) {
    // fetch user details from the database
    $stmt = $conn->prepare("SELECT first_name, last_name, email, city, post_code, country FROM users WHERE user_id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $user_details = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // use fetched user details
    $first_name = $user_details['first_name'];
    $last_name = $user_details['last_name'];
    $email = $user_details['email'];
    $city = $user_details['city'];
    $post_code = $user_details['post_code'];
    $country = $user_details['country'];

} else {
    if (!isset($_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['city'], $_POST['post_code'], $_POST['country'])) {
        die("Missing user details.");
    }

    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $city = trim($_POST['city']);
    $post_code = trim($_POST['post_code']);
    $country = trim($_POST['country']);
}

// set the current date as the booking date
$booking_date = date('Y-m-d H:i:s');

// insert reservation into the database
$stmt = $conn->prepare("
    INSERT INTO reservations
    (flight_id, user_id, seat_class, number_of_passengers, total_price, status, booking_date)
    VALUES (?, ?, ?, ?, ?, 'Confirmed', ?)
");
$stmt->bind_param('iisids', $flight_id, $_SESSION['user_id'], $seat_class, $number_of_passengers, $total_price, $booking_date);
$stmt->execute();
$reservation_id = $stmt->insert_id;
$stmt->close();

$_SESSION['pending_booking'] = [
    'flight_id' => $flight_id,
    'departure_date' => $departure_date,
    'seat_class' => $seat_class,
    'number_of_passengers' => $number_of_passengers,
    'total_price' => $total_price
];

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
            <h1>Your Reservation is Confirmed!</h1>

            <h2>Reservation Details:</h2>
            <div class="form-group">
                <p><strong>Reservation ID:</strong> <?= htmlspecialchars($reservation_id) ?></p>
                <p><strong>Flight ID:</strong> <?= htmlspecialchars($flight_id) ?></p>
                <p><strong>Departure Date:</strong> <?= htmlspecialchars($departure_date) ?></p>
                <p><strong>Class:</strong> <?= htmlspecialchars($seat_class) ?></p>
                <p><strong>Number of Passengers:</strong> <?= htmlspecialchars($number_of_passengers) ?></p>
                <p><strong>Total Price:</strong> £<?= number_format($total_price, 2) ?></p>
            </div>
            <h2>Your Details:</h2>
            <div class="form-group">
                <p><strong>Name:</strong> <?= htmlspecialchars($first_name . ' ' . $last_name) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($email) ?></p>
                <p><strong>City:</strong> <?= htmlspecialchars($city) ?></p>
                <p><strong>Postcode:</strong> <?= htmlspecialchars($post_code) ?></p>
                <p><strong>Country:</strong> <?= htmlspecialchars($country) ?></p>
            </div>
            <a href="account.php">Go to My Account</a>
        </main>

        <?php include 'footerlinks.php'; ?>
    </div>
</body>
</html>

<?php
$conn->close();
?>