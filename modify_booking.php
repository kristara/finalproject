<?php
session_start(); //start session
require_once 'config.php';  // database connection

$message = ""; // initialise message variable
$redirect = false; // flag to control redirection

// check if reservation ID is provided
$resId = intval($_GET['res_id'] ?? $_POST['reservation_id'] ?? 0);
if ($resId <= 0) {
    die('Booking not found.');
}

// fetch existing booking
$stmt = $conn->prepare("
    SELECT flight_id, seat_class, number_of_passengers
        FROM reservations
        WHERE reservation_id = ?
        AND user_id = ?
");
$stmt->bind_param('ii', $resId, $_SESSION['user_id']);
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
                    <h3>Booking Updated Successfully!</h3>
                    <p>Your booking has been updated with the following details:</p>
                    <ul>
                        <li><strong>Passengers:</strong> {$newPassengers}</li>
                        <li><strong>Class:</strong> " . ucfirst(htmlspecialchars($newClass)) . "</li>
                        <li><strong>Total Price:</strong> £" . number_format($newTotal,2) . "</li>
                    </ul>
                    <p><strong>Redirecting back in 3 seconds…</strong></p>
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
        <script>setTimeout(() => window.location='managebooking.php', 3000);</script>
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
            <h1>Modify Booking</h1>
            <div class="message-box"><?= $message ?></div>

            <?php if (! $redirect): ?>
            <form method="POST" action="modify_booking.php?res_id=<?= $resId ?>">
                <input type="hidden" name="reservation_id" value="<?= $resId ?>">

                <label>
                    Seat Class:
                    <select name="seat_class" required>
                        <option value="economy"  <?= $oldClass==='economy'  ? 'selected' : '' ?>>Economy</option>
                        <option value="business" <?= $oldClass==='business' ? 'selected' : '' ?>>Business</option>
                        <option value="first"    <?= $oldClass==='first'    ? 'selected' : '' ?>>First Class</option>
                    </select>
                </label>
                <br><br>

                <label>
                    Passengers:
                    <input type="number"
                            name="passengers"
                            min="1"
                            value="<?= $oldPassengers ?>"
                            required>
                </label>
                <br><br>

                <button type="submit">Update Booking</button>
            </form>
            <?php endif; ?>

            <p><a href="managebooking.php">Return to Manage Booking</a></p>
        </main>

        <!-- shared footer -->
        <?php include 'footerlinks.php'; ?>

    </div>
</body>
</html>