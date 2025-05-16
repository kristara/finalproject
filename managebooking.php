<?php
session_start(); // start session
require_once 'config.php';// database connection

$message = '';
$booking = null;

// check lookup form
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservation_id = intval($_POST['reservation_id'] ?? 0);

    // validate reservation ID
    if ($reservation_id <= 0) {
        $message = '<p>Please enter a valid reservation number.</p>';
    } else {
        //  search with user_id registered user
        if (isset($_SESSION['user_id'])) {
            $stmt = $conn->prepare("
                SELECT
                    r.reservation_id,
                    d.name AS destination,
                    f.departure_date,
                    r.number_of_passengers,
                    r.seat_class,
                    r.status,
                    r.total_price
                FROM reservations r
                JOIN flights f ON r.flight_id = f.flight_id
                JOIN destinations d ON f.destination_id = d.destination_id
                WHERE r.reservation_id = ? AND r.user_id = ?
            ");
            $stmt->bind_param('ii', $reservation_id, $_SESSION['user_id']);
        } else {
            // search only by reservation_id
            $stmt = $conn->prepare("
                SELECT
                    r.reservation_id,
                    d.name AS destination,
                    f.departure_date,
                    r.number_of_passengers,
                    r.seat_class,
                    r.status,
                    r.total_price
                FROM reservations r
                JOIN flights f ON r.flight_id = f.flight_id
                JOIN destinations d ON f.destination_id = d.destination_id
                WHERE r.reservation_id = ? AND r.user_id IS NULL
            ");
            $stmt->bind_param('i', $reservation_id);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $message = '<p>No reservation found matching the provided details.</p>';
        } else {
            $booking = $result->fetch_assoc();
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<title>Manage Booking</title>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css.css">
</head>

<body>
	<div id="pagewrapper">
		<!--session based header links -->
        <nav id="headerlinks">
            <ul>
                <?php if (isset($_SESSION['user_id'])): ?>
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
                <!-- Section for looking up a booking by reservation number and last name -->
                <h1>Look Up Your Booking</h1>
                <form method="POST" action="managebooking.php">
                    <div class="form-group">
                        <label for="reservation_id">Reservation Number:</label>
                        <input
                            type="number"
                            name="reservation_id"
                            id="reservation_id"
                            min="1"
                            required
                            value="<?= htmlspecialchars($_POST['reservation_id'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="last_name">Last Name:</label>
                        <input
                            type="text"
                            name="last_name"
                            id="last_name"
                            required
                            value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <button type="submit" class="find-btn">Look Up Booking</button>
                    </div>
                </form>

                <div class="message-box"><?= $message ?></div>

                <?php if ($booking): ?>
                    <div class="booking-details">
                        <h2>Booking Details</h2>
                        <p><strong>Reservation #:</strong> <?= htmlspecialchars($booking['reservation_id']) ?></p>
                        <p><strong>Destination:</strong> <?= htmlspecialchars($booking['destination']) ?></p>
                        <p><strong>Departure Date:</strong> <?= htmlspecialchars($booking['departure_date']) ?></p>
                        <p><strong>Passengers:</strong> <?= htmlspecialchars($booking['number_of_passengers']) ?></p>
                        <p><strong>Class:</strong> <?= ucfirst(htmlspecialchars($booking['seat_class'])) ?></p>
                        <p><strong>Status:</strong> <?= ucfirst(htmlspecialchars($booking['status'])) ?></p>

                        <?php if ($booking['status'] !== 'cancelled'): ?>
                            <div class="booking-actions">
                                <p>
                                    <a href="modify_booking.php?res_id=<?= urlencode($booking['reservation_id']) ?>">
                                        Modify Booking
                                    </a>
                                    |
                                    <a href="cancel_booking.php?res_id=<?= urlencode($booking['reservation_id']) ?>"
                                        onclick="return confirm('Are you sure you want to cancel this booking?');">
                                        Cancel Booking
                                    </a>
                                </div>
                            </p>
                        <?php else: ?>
                            <p><em>This booking has already been cancelled.</em></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>

        <!-- shared footer -->
        <?php include 'footerlinks.php'; ?>
	</div>
</body>
</html>