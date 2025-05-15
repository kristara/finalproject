<?php
session_start(); //start session
require_once 'config.php';  // database connection

// secure access check
$resId = intval($_GET['res_id'] ?? 0);

// ensure only users who accessed the booking correctly can modify it
if (!isset($_SESSION['reservation_id']) || intval($_SESSION['reservation_id']) !== $resId) {
    header("Location: managebooking.php");
    exit();
}
// if user is not logged in but has entered booking details
if (!isset($_SESSION['user_id'])) {
    $lastName = $_SESSION['reservation_name'] ?? '';
    $stmt = $conn->prepare("
        SELECT r.reservation_id
        FROM reservations r
        JOIN users u ON r.user_id = u.user_id
        WHERE r.reservation_id = ?
        AND LOWER(u.last_name) = LOWER(?)
    ");
    $stmt->bind_param('is', $resId, $lastName);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        unset($_SESSION['reservation_id']);
        unset($_SESSION['reservation_name']);
        header("Location: managebooking.php");
        exit();
    }
    $stmt->close();
}

$message = ""; // initialise message variable
$redirect = false; // flag to control redirection

// check if reservation ID is provided
$booking = null;

if (isset($_SESSION['user_id'])) {
    // when user is logged in then use user_id to fetch booking
    $stmt = $conn->prepare("
        SELECT flight_id, seat_class, number_of_passengers
        FROM reservations
        WHERE reservation_id = ?
        AND user_id = ?
    ");
    $stmt->bind_param('ii', $resId, $_SESSION['user_id']);
} else {
    // User is not logged in - use reservation and last name for lookup
    if (!isset($_SESSION['reservation_name'])) {
        die('Booking not found.');
    }

    $lastName = $_SESSION['reservation_name'];
    $stmt = $conn->prepare("
        SELECT r.flight_id, r.seat_class, r.number_of_passengers
        FROM reservations r
        JOIN users u ON r.user_id = u.user_id
        WHERE r.reservation_id = ?
        AND LOWER(u.last_name) = LOWER(?)
    ");
    $stmt->bind_param('is', $resId, $lastName);
}

// execute the query
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (! $booking) {
    die('Booking not found.');
}

// set initial values for form
$oldClass      = $booking['seat_class'];
$oldPassengers = $booking['number_of_passengers'];
$flightId      = $booking['flight_id'];

// check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newClass      = $_POST['seat_class'] ?? '';
    $newPassengers = max(1, intval($_POST['passengers'] ?? 0));
    $delta         = $newPassengers - $oldPassengers;

    // validation
    if (! in_array($newClass, ['economy','business','first'], true)) {
        $message = '<p>Please select a valid class.</p>';
    } elseif ($newPassengers < 1) {
        $message = '<p>At least one passenger is required.</p>';
    } else {
        // start transaction
        $conn->begin_transaction();

        // lock both old and new class rows
        $lock = $conn->prepare("
            SELECT seat_class, available_seats
                FROM flight_seats
                WHERE flight_id = ?
                AND seat_class IN (?, ?)
                FOR UPDATE
        ");
        $lock->bind_param('iss', $flightId, $oldClass, $newClass);
        $lock->execute();
        $lockedRows = $lock->get_result()->fetch_all(MYSQLI_ASSOC);
        $lock->close();

        // extract availability for new class
        $avail = 0;
        foreach ($lockedRows as $row) {
            if ($row['seat_class'] === $newClass) {
                $avail = (int)$row['available_seats'];
            }
        }

        // if raising passenger count in same class then check availability
        if ($delta > 0 && $oldClass === $newClass && $avail < $delta) {
        // fetch current availability
            $conn->rollback();
            $message = "<p>Only {$avail} seats left in {$newClass} class.</p>";
        } else {
            // update reservation
            $upd = $conn->prepare("
                UPDATE reservations
                    SET number_of_passengers = ?,
                        seat_class           = ?
                    WHERE reservation_id      = ?
            ");
            $upd->bind_param('isi', $newPassengers, $newClass, $resId);
            $upd->execute();
            $upd->close();

            // recalculate total_price using flight_seats.price_per_seat
            $pstmt = $conn->prepare("
                SELECT price_per_seat
                    FROM flight_seats
                    WHERE flight_id  = ?
                    AND seat_class = ?
            ");
            $pstmt->bind_param('is', $flightId, $newClass);
            $pstmt->execute();
            $unit = (float)$pstmt->get_result()->fetch_assoc()['price_per_seat'];
            $pstmt->close();
            $newTotal = $unit * $newPassengers;

            $tstmt = $conn->prepare("
                UPDATE reservations
                    SET total_price = ?
                    WHERE reservation_id = ?
            ");
            $tstmt->bind_param('di', $newTotal, $resId);
            $tstmt->execute();
            $tstmt->close();

            // return old seats to the oldClass bucket
            $ret = $conn->prepare("
                UPDATE flight_seats
                    SET available_seats = available_seats + ?
                    WHERE flight_id  = ?
                    AND seat_class = ?
            ");
            $ret->bind_param('iis', $oldPassengers, $flightId, $oldClass);
            $ret->execute();
            $ret->close();

            // deduct new seats
            $ded = $conn->prepare("
                UPDATE flight_seats
                    SET available_seats = available_seats - ?
                    WHERE flight_id  = ?
                    AND seat_class = ?
            ");
            $ded->bind_param('iis', $newPassengers, $flightId, $newClass);
            $ded->execute();
            $ded->close();

            //transaction
            if ($conn->commit()) {
                $message  = "
                    <div class='success-message'>
                        <h3>Booking Updated Successfully!</h3>
                        <p>Your booking has been updated with the following details:</p>
                        <ul>
                            <li><strong>Passengers:</strong> {$newPassengers}</li>
                            <li><strong>Class:</strong> " . ucfirst(htmlspecialchars($newClass)) . "</li>
                            <li><strong>Total Price:</strong> £" . number_format($newTotal,2) . "</li>
                        </ul>
                        <p><strong>Redirecting back in 5 seconds…</strong></p>
                    </div>
                    ";
                $redirect = true;
            } else {
                $conn->rollback();
                $message = '<p>Unable to apply changes, please try again.</p>';
            }
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<title>Modify Booking</title>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css.css">
    <?php if ($redirect): ?>
        <script>setTimeout(() => window.location='managebooking.php', 5000);</script>
    <?php endif; ?>
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
                <h1>Modify Booking</h1>
                <div class="message-box"><?= $message ?></div>

                <?php if (!$redirect): ?>
                <form method="POST" action="modify_booking.php?res_id=<?= $resId ?>">
                    <input type="hidden" name="reservation_id" value="<?= $resId ?>">
                    
                    <div class="form-group">
                        <label for="seat_class">Seat Class:</label>
                        <select name="seat_class" id="seat_class" required>
                            <option value="economy" <?= $oldClass === 'economy' ? 'selected' : '' ?>>Economy</option>
                            <option value="business" <?= $oldClass === 'business' ? 'selected' : '' ?>>Business</option>
                            <option value="first" <?= $oldClass === 'first' ? 'selected' : '' ?>>First Class</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="passengers">Passengers:</label>
                        <input type="number" name="passengers" id="passengers" min="1" value="<?= $oldPassengers ?>" required>
                    </div>

                    <div class="form-group">
                        <button type="submit">Update Booking</button>
                    </div>
                </form>
                <?php endif; ?>

                <p><a href="managebooking.php">Return to Manage Booking</a></p>
            </section>
        </main>

        <!-- shared footer -->
        <?php include 'footerlinks.php'; ?>

    </div>
</body>
</html>