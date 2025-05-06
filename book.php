<?php
include 'config.php'; // database connection

// Fetch all destinations
$sql = "SELECT destination_id, name, country FROM destinations ORDER BY name ASC";
$result = $conn->query($sql);
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
        <nav id="headerlinks">
            <ul>
                <li><a href="login.html">Log in</a></li>
            </ul>
        </nav>

        <header>
            <h1><a href="holidayMatch.html">Holiday Match</a></h1>
        </header>

        <nav id="primarynav">
            <ul>
                <li><a href="holidayMatch.html">Home</a></li>
                <li><a href="explore.html">Explore</a></li>
                <li class="current"><a href="/book.php">Book</a></li>
                <li><a href="/managebooking.html">Manage Booking</a></li>
            </ul>
        </nav>
        <!-- Main booking form section -->
        <section>
            <h2>Book a Flight</h2>
            <!-- Flight search form. Data is submitted to the PHP backend script-->
            <form action="http://localhost:8000/book_flight.php" method="POST">
                <!-- Departure always as LHR -->
                <label for="origin">From:</label>
                <input type="text" name="origin" id="origin" value="London Heathrow (LHR)" readonly><br><br>
                <!-- Destination from db -->
                <label for="destination_id">To:</label>
                <select name="destination_id" id="destination_id" required>
                    <option value="">Select destination</option>
                    <?php
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='{$row['destination_id']}'>" . htmlspecialchars($row['name']) . ", " . htmlspecialchars($row['country']) . "</option>";
                        }
                    } else {
                        echo "<option value=''>No destinations available</option>";
                    }
                    ?>
                </select><br><br>

                <!-- Departure Date -->
                <label for="departure_date">Departure Date:</label>
                <input type="date" name="departure_date" id="departure_date" required><br><br>
                <!-- Passengers -->
                <label for="number_of_passengers">Number of Passengers:</label>
                <input type="number" name="number_of_passengers" id="number_of_passengers" min="1" required><br><br>
                <!-- Seat Class -->
                <label for="seat_class">Seat Class:</label>
                <select name="seat_class" id="seat_class" required>
                    <option value="economy">Economy</option>
                    <option value="business">Business</option>
                    <option value="first class">First Class</option>
                </select><br><br>
                <!-- Submit button to trigger flight search -->
                <button type="submit">Search Flights</button>
            </form>
        </section>

        <footer>
            <nav id="footerlinks">
                <ul>
                    <li><a href="termsofuse.html">Terms of Use &#124;</a></li>
                    <li><a href="copyright.html">Copyright &#124;</a></li>
                    <li><a href="contactus.html">Contact Us</a></li>
                </ul>
            </nav>
        </footer>
    </div>
</body>
</html>

<?php
$conn->close(); // Close DB connection
?>