<?php
session_start(); // start the session
require_once 'config.php'; // database connection

//validate incoming form data
if (
    empty($_POST['flight_id']) ||
    empty($_POST['number_of_passengers']) ||
    empty($_POST['seat_class'])
) {
    exit("Missing flight selection.");
}

$flight_id  = (int) $_POST['flight_id'];
$passengers = max(1, (int) $_POST['number_of_passengers']);
$seat_class = $_POST['seat_class'];

//ensure the seat_class matches your enum
$valid = ['economy','business','first'];
if (! in_array($seat_class, $valid, true)) {
    exit("Invalid seat class.");
}

//fetch price and availability from flight_seats
$stmt = $conn->prepare("
        SELECT
            fs.price_per_seat,
            fs.available_seats,
            d.name   AS destination_name,
            d.country AS destination_country,
            f.departure_date
        FROM flight_seats fs
        JOIN flights f ON fs.flight_id = f.flight_id
        JOIN destinations d ON f.destination_id = d.destination_id
        WHERE fs.flight_id = ? AND fs.seat_class = ?
");
$stmt->bind_param('is', $flight_id, $seat_class);
$stmt->execute();
$flight = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (! $flight) {
    exit("Flight or class not found.");
}
if ($flight['available_seats'] < $passengers) {
    exit("Only {$flight['available_seats']} seats left in {$seat_class} class.");
}

//calculate total
$total      = $flight['price_per_seat'] * $passengers;
$isLoggedIn = isset($_SESSION['user_id']);

// if a guest user then store pending in session
if (! $isLoggedIn) {
    $_SESSION['pending_booking'] = [
        'flight_id'  => $flight_id,
        'passengers' => $passengers,
        'seat_class' => $seat_class,
    ];
}

// fetch available flights for the destination and date
$departure_date = $_POST['departure_date'] ?? '';
$flightStmt = $conn->prepare("
    SELECT * FROM flights
    WHERE destination_id = ? AND departure_date = ?
");
$flightStmt->bind_param('is', $flight['destination_id'], $departure_date);
$flightStmt->execute();
$flights = $flightStmt->get_result();
$flightStmt->close();

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
            <h1>Confirm Your Booking</h1>
            <p><strong>Destination:</strong>
                <?= htmlspecialchars($flight['destination_name']) ?>,
                <?= htmlspecialchars($flight['destination_country']) ?>
            </p>
            <p><strong>Departure Date:</strong>
                <?= htmlspecialchars($flight['departure_date']) ?>
            </p>
            <p><strong>Class:</strong>
                <?= htmlspecialchars(ucfirst($seat_class)) ?>
            </p>
            <p><strong>Passengers:</strong> <?= $passengers ?></p>
            <p><strong>Price per Seat:</strong>
                £<?= number_format($flight['price_per_seat'],2) ?>
            </p>
            <p><strong>Total Price:</strong>
                £<?= number_format($total,2) ?>
            </p>

            <?php if ($isLoggedIn): ?>
                <form action="finalise_booking.php" method="POST">
                    <input type="hidden" name="confirm" value="1">
                    <button type="submit">Confirm Booking</button>
                </form>
            <?php else: ?>
                <p>
                    Please <a href="login.php">log in</a> or
                    <a href="registration.php">register</a>
                    to complete your booking.
                </p>
            <?php endif; ?>
        </main>
        <?php include 'footerlinks.php'; ?>
    </div>
</body>
</html>