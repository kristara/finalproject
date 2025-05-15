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

if ($destination_id > 0) {
    // fetch the destination name and country from the database
    $stmt = $conn->prepare("
        SELECT name, country
        FROM destinations
        WHERE destination_id = ?
    ");
    $stmt->bind_param('i', $destination_id);
    $stmt->execute();
    $dest = $stmt->get_result()->fetch_assoc();
    if ($dest) {
        $destination_name    = $dest['name'];
        $destination_country = $dest['country'];
    }
    $stmt->close();
}

// fetch full destination list for the select dropdown
$allDestStmt = $conn->query("
    SELECT DISTINCT destination_id, name, country
    FROM destinations
    ORDER BY name
");

// fetch available dates for the selected destination
$availableDatesStmt = $conn->prepare("
    SELECT DISTINCT f.departure_date
    FROM flights f
    WHERE f.destination_id = ? AND f.departure_date >= CURDATE()
");
$availableDatesStmt->bind_param('i', $destination_id);
$availableDatesStmt->execute();
$availableDatesResult = $availableDatesStmt->get_result();
$availableDates = [];
while ($row = $availableDatesResult->fetch_assoc()) {
    // format the dates to 'Y-m-d' format before passing to JS
    $availableDates[] = $row['departure_date'];  // store available dates
}
$availableDatesStmt->close();

// execute the query to fetch available flights
$flightStmt = $conn->prepare("
    SELECT * FROM flights
    WHERE destination_id = ? AND departure_date = ?
");

$flightStmt->bind_param('is', $destination_id, $departure_date);

// Execute the statement and check if successful
$flights_result = null; // Initialize it before checking
if ($flightStmt->execute()) {
    $flights_result = $flightStmt->get_result();
} else {
    echo "Error executing the query: " . $flightStmt->error;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Book a Flight</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
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

                <!-- Flight Selection Form -->
                <form action="confirm_booking.php" method="POST">
                    <div class="form-group">
                        <label>From:</label>
                        <input type="text" name="origin" value="London Heathrow (LHR)" readonly>
                    </div>

                    <!-- destination selector -->
                    <div class="form-group">
                        <label>To:</label>
                        <select name="destination_id" required>
                            <option value="">Select destination</option>
                            <?php while ($row = $allDestStmt->fetch_assoc()): ?>
                                <option value="<?= $row['destination_id'] ?>" <?= (int)$row['destination_id'] === (int)$destination_id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($row['name']) ?>, <?= htmlspecialchars($row['country']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- departure date input (dynamically populated) -->
                    <div class="form-group">
                        <label>Departure Date:</label>
                        <input type="text" id="departure_date" name="departure_date" value="<?= htmlspecialchars($departure_date) ?>" required>
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

                    <div class="form-group">
                        <button type="submit">Proceed</button>
                    </div>
                </form>

                <!-- display available flights based on destination and date -->
                <h2>Available Flights for <?= htmlspecialchars($destination_name) ?> on <?= htmlspecialchars($departure_date) ?>:</h2>
                <?php if ($flights_result && $flights_result->num_rows > 0): ?>
                    <ul>
                        <?php while ($flight = $flights_result->fetch_assoc()): ?>
                            <li>
                                Flight ID: <?= $flight['flight_id'] ?>,
                                Departure Date: <?= $flight['departure_date'] ?>,
                                Origin: <?= $flight['origin'] ?>
                                <form action="confirm_booking.php" method="POST">
                                    <input type="hidden" name="flight_id" value="<?= $flight['flight_id'] ?>" />
                                    <input type="hidden" name="departure_date" value="<?= $flight['departure_date'] ?>" />
                                    <button type="submit">Confirm Booking</button>
                                </form>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p>No flights available for the selected date.</p>
                <?php endif; ?>
            </section>
        </main>

        <!-- shared footer -->
        <?php include 'footerlinks.php'; ?>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const availableDates = <?php echo json_encode($availableDates); ?>;

                flatpickr("#departure_date", {
                    dateFormat: "Y-m-d",
                    disable: [
                        function(date) {
                            // if using the correct date format and checking against available dates
                            return !availableDates.includes(flatpickr.formatDate(date, "Y-m-d"));
                        }
                    ],
                    onDayCreate: function(dObj, dStr, dHTML) {
                        let date = flatpickr.parseDate(dStr, "Y-m-d");
                        let dateString = flatpickr.formatDate(date, "Y-m-d");
                        
                        if (availableDates.includes(dateString)) {
                            dHTML.classList.add("available"); // highlight available dates
                        } else {
                            dHTML.classList.add("unavailable"); // highlight unavailable dates
                        }
                    }
                });
            });
        </script>
    </div>
</body>
</html>

<?php
$allDestStmt->free(); // clean up
$conn->close();
?>