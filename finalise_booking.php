<?php
session_start();           //start session
require_once 'config.php'; // db connection

if (! isset($_SESSION['user_id'], $_SESSION['pending_booking'])) {
    exit("No booking in progress.");
}

$pb         = $_SESSION['pending_booking'];
$user_id    = $_SESSION['user_id'];
$flight_id  = (int)$pb['flight_id'];
$passengers = (int)$pb['passengers'];
$seat_class = $pb['seat_class'];

//fetch price per seat
$stmt = $conn->prepare("
    SELECT price_per_seat
    FROM flight_seats
    WHERE flight_id  = ?
    AND seat_class = ?
");
$stmt->bind_param('is', $flight_id, $seat_class);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (! $row) {
    exit("Booking data invalid.");
}
$total = $row['price_per_seat'] * $passengers;

//insert reservation
$ins = $conn->prepare("
    INSERT INTO reservations
        (user_id, flight_id, number_of_passengers, seat_class, total_price)
    VALUES (?, ?, ?, ?, ?)
");
$ins->bind_param('iiisd', $user_id, $flight_id, $passengers, $seat_class, $total);
$ins->execute();
$res_id = $conn->insert_id;
$ins->close();

//deduct seats
$upd = $conn->prepare("
    UPDATE flight_seats
    SET available_seats = available_seats - ?
    WHERE flight_id  = ?
    AND seat_class = ?
");
$upd->bind_param('iis', $passengers, $flight_id, $seat_class);
$upd->execute();
$upd->close();

// clear pending and redirect
unset($_SESSION['pending_booking']);
$conn->close();

header("Location: booking_success.php?res_id={$res_id}");
exit;