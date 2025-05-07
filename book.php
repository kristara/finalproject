<?php
include 'config.php'; // database connection

// initialise variables
$destination_name = "";
$destination_country = "";
$destination_id = isset($_GET['destination_id']) ? intval($_GET['destination_id']) : 0;

// Check if destination_id is provided in URL
if ($destination_id > 0) {
    // Fetch all destinations
    $sql = "SELECT name, country FROM destinations WHERE destination_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $destination_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $destination = $result->fetch_assoc();
        $destination_name = $destination['name'];
        $destination_country = $destination['country'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Book a Flight - HolidayMatch</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css.css">
</head>

<body>
    <div id="pagewrapper">
        <h2>Book a Flight</h2>
        <form action="book_flight.php" method="POST">
            <label>From:</label>
            <input type="text" name="origin" value="London Heathrow (LHR)" readonly><br><br>

            <label>To:</label>
            <input type="text" name="destination" value="<?php echo $destination_name . ', ' . $destination_country; ?>" readonly><br><br>
            <input type="hidden" name="destination_id" value="<?php echo $destination_id; ?>">

            <label>Departure Date:</label>
            <input type="date" name="departure_date" required><br><br>

            <label>Number of Passengers:</label>
            <input type="number" name="number_of_passengers" min="1" required><br><br>

            <label>Seat Class:</label>
            <select name="seat_class" required>
                <option value="economy">Economy</option>
                <option value="business">Business</option>
                <option value="first class">First Class</option>
            </select><br><br>

            <button type="submit">Confirm Booking</button>
        </form>
    </div>
</body>
</html>

<?php $conn->close();
?>