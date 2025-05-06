<?php
include 'config.php'; // database connection

// check for required POST values
if (!isset($_POST['flight_id'], $_POST['number_of_passengers'], $_POST['seat_class'])) {
    exit("Missing flight selection.");
}

$flight_id = intval($_POST['flight_id']);
$passengers = intval($_POST['number_of_passengers']);
$seat_class = $_POST['seat_class'];

//validate seat class
$valid_classes = ['economy', 'business', 'first class'];
if (!in_array($seat_class, $valid_classes)) {
    exit("Invalid seat class.");
}

// get full flight details
$seat_column = match($seat_class) {
    'economy' => 'economy_seats',
    'business' => 'business_seats',
    'first class' => 'first_class_seats',
};

$sql = "SELECT f.*, d.name AS destination_name, d.country
        FROM flights f
        JOIN destinations d ON f.destination_id = d.destination_id
        WHERE f.flight_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $flight_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    exit("Flight not found.");
}

$flight = $result->fetch_assoc();
$available = $flight[$seat_column];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Confirm Your Booking</title>
</head>
<body>
    <h2>Confirm Booking</h2>

    <p><strong>Destination:</strong> <?php echo htmlspecialchars($flight['destination_name']) . ", " . htmlspecialchars($flight['country']); ?></p>
    <p><strong>Departure:</strong> <?php echo $flight['departure_time']; ?></p>
    <p><strong>Arrival:</strong> <?php echo $flight['arrival_time']; ?></p>
    <p><strong>Seat Class:</strong> <?php echo htmlspecialchars($seat_class); ?></p>
    <p><strong>Passengers:</strong> <?php echo $passengers; ?></p>
    <p><strong>Available Seats:</strong> <?php echo $available; ?></p>

    <?php if ($available < $passengers): ?>
        <p style="color:red;">Flight is sold out.</p>
    <?php else: ?>
        <form action="finalise_booking.php" method="POST">
            <label for="user_id">Enter your User ID:</label>
            <input type="number" name="user_id" id="user_id" required><br><br>

            <!-- Pass booking data forward -->
            <input type="hidden" name="flight_id" value="<?php echo $flight_id; ?>">
            <input type="hidden" name="number_of_passengers" value="<?php echo $passengers; ?>">
            <input type="hidden" name="seat_class" value="<?php echo htmlspecialchars($seat_class); ?>">

            <button type="submit">Confirm Booking</button>
        </form>
    <?php endif; ?>

</body>
</html>

// Close the database connection
<?php
$stmt->close();
$conn->close();
?>