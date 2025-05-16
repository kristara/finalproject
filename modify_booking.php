<?php
session_start(); //start session
require_once 'config.php';  // database connection

// secure access check
$resId = intval($_GET['res_id'] ?? 0);

// initialise $booking to avoid undefined errors
$booking = null;

// bypass manual entry
if (isset($_SESSION['user_id'])) {
    // fetch booking for logged-in user
    $stmt = $conn->prepare("SELECT reservation_id, flight_id, seat_class, number_of_passengers FROM reservations WHERE reservation_id = ? AND user_id = ?");
    $stmt->bind_param('ii', $resId, $_SESSION['user_id']);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$booking) {
        die('Booking not found.');
    }
} else {
    // guests must manually enter reservation details
    if (isset($_SESSION['reservation_id'], $_SESSION['reservation_name'])) {
        $resId = intval($_SESSION['reservation_id']);
        $lastName = $_SESSION['reservation_name'];

        $stmt = $conn->prepare("
            SELECT r.reservation_id, r.flight_id, r.seat_class, r.number_of_passengers
            FROM reservations r
            JOIN users u ON r.user_id = u.user_id
            WHERE r.reservation_id = ?
            AND LOWER(u.last_name) = LOWER(?)
        ");
        $stmt->bind_param('is', $resId, $lastName);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

    // redirect if booking not found
    if (!$booking) {
        header("Location: managebooking.php");
        exit();
    }
}

// default values for modification form
$oldClass = $booking['seat_class'];
$oldPassengers = $booking['number_of_passengers'];
$flightId = $booking['flight_id'];

// handle booking modification
$message = "";
$redirect = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newClass = $_POST['seat_class'] ?? '';
    $newPassengers = max(1, intval($_POST['passengers'] ?? 0));

    // validate and update booking
    if (in_array($newClass, ['economy', 'business', 'first'], true)) {
        $stmt = $conn->prepare("UPDATE reservations SET seat_class = ?, number_of_passengers = ? WHERE reservation_id = ?");
        $stmt->bind_param('sii', $newClass, $newPassengers, $resId);
        $stmt->execute();
        $stmt->close();

        $message = "<div class='success-message'><h3>Booking Updated Successfully!</h3><p>Your booking has been updated.</p></div>";
        $redirect = true;
    } else {
        $message = "<p>Please select a valid class.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Modify Booking</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css.css">
</head>
<body>
    <div id="pagewrapper">
        <?php include 'primarynav.php'; ?>

        <main class="centered-form">
            <h1>Modify Booking</h1>
            <div class="message-box"><?= $message ?></div>

            <?php if (!$redirect): ?>
                <form method="POST" action="">
                    <label for="seat_class">Seat Class:</label>
                    <select name="seat_class" id="seat_class">
                        <option value="economy" <?= $oldClass === 'economy' ? 'selected' : '' ?>>Economy</option>
                        <option value="business" <?= $oldClass === 'business' ? 'selected' : '' ?>>Business</option>
                        <option value="first" <?= $oldClass === 'first' ? 'selected' : '' ?>>First Class</option>
                    </select>

                    <label for="passengers">Passengers:</label>
                    <input type="number" name="passengers" value="<?= $oldPassengers ?>" min="1" required>

                    <button type="submit">Update Booking</button>
                </form>
            <?php endif; ?>

            <p><a href="managebooking.php">Return to Manage Booking</a></p>
        </main>

        <?php include 'footerlinks.php'; ?>
    </div>
</body>
</html>