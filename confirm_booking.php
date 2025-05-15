<?php
session_start(); // start the session
require_once 'config.php'; // database connection

//validate incoming form data
if (!isset($_POST['flight_id'], $_POST['departure_date'], $_POST['seat_class'], $_POST['number_of_passengers'])) {
    die("Missing flight selection.");
}

$flight_id = intval($_POST['flight_id']);
$departure_date = $_POST['departure_date'];
$seat_class = $_POST['seat_class'];
$number_of_passengers = intval($_POST['number_of_passengers']);

// fetch flight and destination details in one query
$stmt = $conn->prepare("
    SELECT f.origin, d.name AS destination, d.country, fs.price_per_seat, fs.available_seats
    FROM flights f JOIN destinations d ON f.destination_id = d.destination_id
    JOIN flight_seats fs ON fs.flight_id = f.flight_id
    WHERE f.flight_id = ? AND fs.seat_class = ?");
$stmt->bind_param('is', $flight_id, $seat_class);
$stmt->execute();
$flight = $stmt->get_result()->fetch_assoc();
$stmt->close();

// set destination details
$destination_name = $destination['name'] ?? 'Unknown Destination';
$destination_country = $destination['country'] ?? 'Unknown Country';

//check if the flight is found
if (!$flight) {
    die('Flight not found.');
}

//ensure available seats exist
$available_seats = (int)$flight['available_seats'];

if ($available_seats < $number_of_passengers) {
    die("Only $available_seats seats left in $seat_class class.");
}

//calculate total
$total_price = $flight['price_per_seat'] * $number_of_passengers;

// checking if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// if a guest user then store pending in session
if (!isset($_SESSION['user_id'])) {
    $_SESSION['pending_booking'] = [
        'flight_id' => $flight_id,
        'departure_date' => $departure_date,
        'seat_class' => $seat_class,
        'number_of_passengers' => $number_of_passengers,
        'total_price' => $total_price
    ];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Confirm Booking</title>
    <link rel="stylesheet" href="css.css">
</head>

<body>
    <div id="pagewrapper">
        <nav id="headerlinks">
            <ul>
                <?php if ($isLoggedIn): ?>
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
            <section class="centered-form">
                <h1>Confirm Booking</h1>
                <p><strong>Origin:</strong> <?= htmlspecialchars($flight['origin'] ?? 'Unknown') ?></p>
                <p><strong>Destination:</strong> <?= htmlspecialchars($flight['destination'] ?? 'Unknown') ?>, <?= htmlspecialchars($flight['country'] ?? 'Unknown') ?></p>
                <p><strong>Departure Date:</strong> <?= htmlspecialchars($departure_date) ?></p>
                <p><strong>Class:</strong> <?= ucfirst(htmlspecialchars($seat_class)) ?></p>
                <p><strong>Number of Passengers:</strong> <?= htmlspecialchars($number_of_passengers) ?></p>
                <p><strong>Total Price:</strong> £<?= number_format($total_price, 2) ?></p>

                <?php if ($isLoggedIn): ?>
                    <form action="finalise_booking.php" method="POST">
                        <input type="hidden" name="flight_id" value="<?= $flight_id ?>">
                        <input type="hidden" name="departure_date" value="<?= $departure_date ?>">
                        <input type="hidden" name="seat_class" value="<?= $seat_class ?>">
                        <input type="hidden" name="number_of_passengers" value="<?= $number_of_passengers ?>">
                        <input type="hidden" name="total_price" value="<?= $total_price ?>">
                        <button type="submit">Confirm Booking</button>
                    </form>
                <?php else: ?>
                    <p>
                        Please <a href="login.php">log in</a> or
                        <a href="registration.php">register</a>
                        to complete your booking.
                    </p>
                <?php endif; ?>
            </section>
        </main>

        <?php include 'footerlinks.php'; ?>
    </div>
</body>
</html>

<?php
$conn->close();
?>