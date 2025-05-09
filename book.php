<?php
session_start(); // start the session
include 'config.php'; // database connection

// check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);

// initialise variables
$destination_id = "";
$destination_name = "";
$destination_country = "";

// Check if destination_id is provided in URL
if (isset($_GET['destination_id'])) {
    $destination_id = intval($_GET['destination_id']);
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
    <link rel="stylesheet" href="../css.css">
</head>

<body>
    <div id="pagewrapper">
        <nav id="headerlinks">
            <ul>
				<?php
                session_start();
                if (isset($_SESSION['user_id'])) {
                    echo '<li><a href="account.php">My Account</a></li>';
                    echo '<li><a href="logout.php">Log Out</a></li>';
                } else {
                    echo '<li><a href="registration.php">Register</a></li>';
                    echo '<li><a href="login.php">Log In</a></li>';
                }
                ?>
			</ul>
        </nav>

        <header>
            <h1><a href="holidayMatch.html">Holiday Match</a></h1>
        </header>

        <nav id="primarynav">
            <ul>
                <li><a href="holidayMatch.html">Home</a></li>
                <li><a href="explore.html">Explore</a></li>
                <li class="current"><a href="book.php">Book</a></li>
                <li><a href="managebooking.php">Manage Booking</a></li>
            </ul>
        </nav>

        <section>
            <h2>Book a Flight</h2>
            <form action="book_flight.php" method="POST">
                <label>From:</label>
                <input type="text" name="origin" value="London Heathrow (LHR)" readonly><br><br>

                <label>To:</label>
                <select name="destination_id" required>
                    <option value="">Select destination</option>
                    <?php
                    $destinations = $conn->query("SELECT destination_id, name, country FROM destinations ORDER BY name ASC");
                    while ($row = $destinations->fetch_assoc()) {
                        $selected = ($row['destination_id'] == $destination_id) ? 'selected' : '';
                        echo "<option value='{$row['destination_id']}' $selected>{$row['name']}, {$row['country']}</option>";
                    }
                    ?>
                </select><br><br>

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