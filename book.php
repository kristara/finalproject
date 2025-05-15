<?php
session_start(); // start the session
require_once 'config.php'; // database connection

// check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// initialise variables
$destination_id      = intval($_GET['destination_id'] ?? 0);
$departure_date      = $_GET['departure_date'] ?? ''; // capture departure_date from URL
$destination_name    = '';
$destination_country = '';

// fetch destination details
if ($destination_id > 0) {
    $stmt = $conn->prepare("SELECT name, country FROM destinations WHERE destination_id = ?");
    $stmt->bind_param('i', $destination_id);
    $stmt->execute();
    $dest = $stmt->get_result()->fetch_assoc();
    if ($dest) {
        $destination_name = $dest['name'];
        $destination_country = $dest['country'];
    }
    $stmt->close();
}

// fetch full destination list for dropdown
$allDestStmt = $conn->query("SELECT destination_id, name, country FROM destinations ORDER BY name ASC");

// fetch available flights for the destination
$flightStmt = $conn->prepare("
    SELECT *
    FROM flights
    WHERE destination_id = ?
    AND departure_date >= ?
    ORDER BY departure_date ASC
");
$flightStmt->bind_param('is', $destination_id, $departure_date);
$flightStmt->execute();
$flights_result = $flightStmt->get_result();
$flightStmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Book a Flight</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css.css">
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('departure_date').addEventListener('change', function() {
                this.form.submit();
            });
        });
    </script>
</head>

<body>
    <div id="pagewrapper">
        <nav id="headerlinks">
            <ul>
                <?php if ($isLoggedIn): ?>
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
                <h1>Book a Flight</h1>

                <!-- flight form -->
                <form action="book.php" method="GET">
                    <div class="form-group">
                        <label>From:</label>
                        <input type="text" name="origin" value="London Heathrow (LHR)" readonly>
                    </div>

                    <!-- destination selector -->
                    <div class="form-group">
                        <label>To:</label>
                        <select name="destination_id" required onchange="this.form.submit()">
                            <option value="">Select destination</option>
                            <?php while ($row = $allDestStmt->fetch_assoc()): ?>
                                <option value="<?= $row['destination_id'] ?>" <?= (int)$row['destination_id'] === $destination_id ? 'selected' : '' ?>><?= htmlspecialchars($row['name']) ?>, <?= htmlspecialchars($row['country']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- departure date input -->
                    <div class="form-group">
                        <label>Departure Date:</label>
                        <input type="date" name="departure_date" value="<?= htmlspecialchars($departure_date) ?>" required onchange="this.form.submit()">
                    </div>

                    <!-- number of passengers -->
                    <div class="form-group">
                        <label>Number of Passengers:</label>
                        <input type="number" name="number_of_passengers" min="1" value="1" required>
                    </div>

                    <!-- seat class -->
                    <div class="form-group">
                        <label>Seat Class:</label>
                        <select name="seat_class" required>
                            <option value="economy">Economy</option>
                            <option value="business">Business</option>
                            <option value="first">First</option>
                        </select>
                    </div>
                </form>

                <!-- display available flights based on destination and date -->
                <h2>Earliest flights to <?= htmlspecialchars($destination_name) ?> available from <?= htmlspecialchars($departure_date) ?>:</h2>
                <?php if ($flights_result && $flights_result->num_rows > 0): ?>
                    <ul class="available-flights">
                        <?php while ($flight = $flights_result->fetch_assoc()): ?>
                            <li>
                                <div>
                                    Departure: <?= $flight['departure_date'] ?>
                                </div>
                                <form action="confirm_booking.php" method="POST">
                                    <input type="hidden" name="flight_id" value="<?= $flight['flight_id'] ?>" />
                                    <input type="hidden" name="departure_date" value="<?= $flight['departure_date'] ?>" />
                                    <button type="submit">Proceed</button>
                                </form>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php endif; ?>
            </section>
        </main>

        <!-- shared footer -->
        <?php include 'footerlinks.php'; ?>
    </div>
</body>
</html>