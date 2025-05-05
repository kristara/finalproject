<?php
include 'config.php'; // database connection

if (!isset($_POST['user_id'], $_POST['destination_id'], $_POST['departure_date'], $_POST['number_of_passengers'], $_POST['seat_class'])) {
    exit("Missing required fields.");
}

// Check if all expected POST fields are present
if (!isset($_POST['destination_id'], $_POST['departure_date'], $_POST['number_of_passengers'], $_POST['seat_class'])) {
    exit("Missing search fields.");
}

// Extract input values
$destination_id = intval($_POST['destination_id']);
$departure_date = $_POST['departure_date'];
$passengers = intval($_POST['number_of_passengers']);
$seat_class = $_POST['seat_class'];

// Define allowed seat classes for validation
$valid_classes = ['economy', 'business', 'first class'];
if (!in_array($seat_class, $valid_classes)) {
    exit("Invalid seat class."); // Exit if user submits invalid class
}

// Map seat class to the corresponding column in the database
$seat_column = match($seat_class) {
    'economy' => 'economy_seats',
    'business' => 'business_seats',
    'first class' => 'first_class_seats',
};

// SQL query to find matching flights with enough seats on the selected date
$sql = "SELECT f.flight_id, f.departure_time, f.arrival_time, f.price, d.name AS destination_name, d.country, f.$seat_column AS available_seats
        FROM flights f
        JOIN destinations d ON f.destination_id = d.destination_id
        WHERE f.destination_id = ?
        AND DATE(f.departure_time) = ?
        AND f.$seat_column >= ?"; // only return flights that have enough seats available

// Prepare and execute the query
$stmt = $conn->prepare($sql);
$stmt->bind_param("isi", $destination_id, $departure_date, $passengers);
$stmt->execute();
$result = $stmt->get_result();

// If no matching flights found then display a message
if ($result->num_rows === 0) {
    echo "<p>No matching flights found.</p>";
} else {
    // Display a table of available flights
    echo "<h2>Available Flights:</h2>";
    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>Flight ID</th><th>Destination</th><th>Departure</th><th>Arrival</th><th>Price</th><th>Seats Available</th><th>Select</th></tr>";
    // Loop through each result row and display flight details
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['flight_id']}</td>";
        echo "<td>{$row['destination_name']}, {$row['country']}</td>";
        echo "<td>{$row['departure_time']}</td>";
        echo "<td>{$row['arrival_time']}</td>";
        echo "<td>£{$row['price']}</td>";
        echo "<td>{$row['available_seats']}</td>";
        // Form to send selected flight data to confirmation page
        echo "<td>
                <form action='confirm_booking.php' method='POST'>
                    <input type='hidden' name='flight_id' value='{$row['flight_id']}'>
                    <input type='hidden' name='number_of_passengers' value='$passengers'>
                    <input type='hidden' name='seat_class' value='$seat_class'>
                    <button type='submit'>Select</button>
                </form>
              </td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Close the database connection
$stmt->close();
$conn->close();
?>