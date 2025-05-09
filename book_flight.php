<?php
session_start();
include 'config.php'; // database connection

// Validate required fields
if (!isset($_POST['destination_id'], $_POST['departure_date'], $_POST['number_of_passengers'], $_POST['seat_class'])) {
    exit("Missing required fields.");
}

// Retrieve user inputs
$destination_id = intval($_POST['destination_id']);
$departure_date = $_POST['departure_date'];
$passengers = intval($_POST['number_of_passengers']);
$seat_class = $_POST['seat_class'];

// Check  if the user is logged in
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Ensure user_id is properly handled
if (empty($user_id)) {
    $user_id = null; // guest user
}

// Check the correct seat column based on selected class
$seat_column = match ($seat_class) {
    'economy' => 'economy_seats',
    'business' => 'business_seats',
    'first class' => 'first_class_seats',
    default => exit("Invalid seat class.")
};

// Find the flight
$sql = "SELECT flight_id, price, $seat_column AS available_seats
        FROM flights
        WHERE destination_id = ?
        AND DATE(departure_time) = ?
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $destination_id, $departure_date);
$stmt->execute();
$result = $stmt->get_result();

// Check if flight exists
if ($result->num_rows === 0) {
    $conn->close();
    exit("No matching flight found for the selected destination and date.");
}

$flight = $result->fetch_assoc();
$flight_id = $flight['flight_id'];
$price = $flight['price'] * $passengers;

// Ensure enough seats are available
if ($flight['available_seats'] < $passengers) {
    $conn->close();
    exit("Only {$flight['available_seats']} seat(s) available.");
}

// Decrease the available seats based on selected class
$update_seats_sql = "UPDATE flights SET $seat_column = $seat_column - ? WHERE flight_id = ?";
$update_stmt = $conn->prepare($update_seats_sql);
$update_stmt->bind_param("ii", $passengers, $flight_id);
$update_stmt->execute();

// Insert reservation
$insert_sql = "INSERT INTO reservations (user_id, flight_id, departure_date, number_of_passengers, seat_class, total_price, status) 
               VALUES (?, ?, ?, ?, ?, ?, 'reserved')";
$insert_stmt = $conn->prepare($insert_sql);

// Handling guest booking without user_id
$insert_stmt->bind_param("iisssd", $user_id, $flight_id, $departure_date, $passengers, $seat_class, $price);
$insert_stmt->execute();

// Close the connection before redirecting
$conn->close();

// Redirect to confirmation page if successful
if ($insert_stmt->affected_rows > 0) {
    header("Location: confirm_booking.php?reservation_id=" . $insert_stmt->insert_id);
    exit();
} else {
    exit("Booking failed: " . $conn->error);
}
?>