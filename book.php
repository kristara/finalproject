<?php
session_start(); // start the session
require_once 'config.php'; // database connection

// check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// initialise variables
$destination_id = intval($_GET['destination_id'] ?? 0);
$departure_date = $_GET['departure_date'] ?? '';
$destination_name = '';
$destination_country = '';
$return_date = '';

// fetch destination details
if ($destination_id > 0) {
    $stmt = $conn->prepare("
        SELECT name, country
        FROM destinations
        WHERE destination_id = ?");
    $stmt->bind_param('i', $destination_id);
    $stmt->execute();
    $dest = $stmt->get_result()->fetch_assoc();
    if ($dest) {
        $destination_name = $dest['name'];
        $destination_country = $dest['country'];
    }
    $stmt->close();
}

// calculate return date
if (!empty($departure_date)) {
    $return_date = date('Y-m-d', strtotime($departure_date . ' +7 days'));
}

// fetch full destination list for dropdown
$allDestStmt = $conn->query("
    SELECT destination_id, name, country
    FROM destinations
    ORDER BY name ASC");

// fetch available flights for the destination
$flights_result = [];
if ($destination_id > 0 && !empty($departure_date)) {
    $stmt = $conn->prepare("
        SELECT f.flight_id, f.departure_date, fs.price_per_seat AS price, fs.seat_class
        FROM flights f
        JOIN flight_seats fs ON fs.flight_id = f.flight_id
        WHERE f.destination_id = ? AND f.departure_date >= ?
        ORDER BY f.departure_date ASC");
    $stmt->bind_param('is', $destination_id, $departure_date);
    $stmt->execute();
    $flights_result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
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
            const departureInput = document.getElementById('departure_date');
            const returnDateInput = document.getElementById('return_date');
            const flightsList = document.getElementById('available-flights');
            const seatClassSelect = document.querySelector('select[name="seat_class"]');

            // filter flights by seat class
            seatClassSelect.addEventListener('change', function() {
                const selectedClass = this.value;
                document.querySelectorAll('.available-flight').forEach(function(flight) {
                    const flightClass = flight.dataset.class;
                    flight.style.display = (selectedClass === '' || flightClass === selectedClass) ? 'block' : 'none';
                });
            });

            // filter flights by date
            departureInput.addEventListener('change', function() {
                const departureDate = new Date(departureInput.value);
                if (departureDate) {
                    const returnDate = new Date(departureDate);
                    returnDate.setDate(returnDate.getDate() + 7);
                    returnDateInput.value = returnDate.toISOString().split('T')[0];

                    document.querySelectorAll('.available-flight').forEach(function(flight) {
                        const flightDate = new Date(flight.dataset.departure);
                        flight.style.display = (flightDate >= departureDate) ? 'block' : 'none';
                    });
                }
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

                <form action="confirm_booking.php" method="POST">
                    <input type="hidden" name="flight_id" value="<?= $selected_flight_id ?? '' ?>">
                    <input type="hidden" name="departure_date" value="<?= htmlspecialchars($departure_date) ?>">

                    <div class="form-group">
                        <label>From:</label>
                        <input type="text" name="origin" value="London Heathrow (LHR)" readonly>
                    </div>

                    <!-- destination selector -->
                    <div class="form-group">
                        <label>To:</label>
                        <input type="text" value="<?= htmlspecialchars($destination_name) ?>, <?= htmlspecialchars($destination_country) ?>" readonly>
                        <input type="hidden" name="destination_id" value="<?= $destination_id ?>">
                    </div>

                    <div class="form-group">
                        <label>Departure Date:</label>
                        <input type="date" id="departure_date" name="departure_date" value="<?= htmlspecialchars($departure_date) ?>" min="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Return Date:</label>
                        <input type="text" id="return_date" value="<?= htmlspecialchars($return_date) ?>" readonly>
                    </div>

                    <!-- number of passengers -->
                    <div class="form-group">
                        <input type="hidden" name="departure_date" value="<?= htmlspecialchars($departure_date) ?>">
                        <label>Number of Passengers:</label>
                        <input type="number" name="number_of_passengers" id="number_of_passengers" min="1" value="1" required>
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

                    <!-- display available flights based on destination and date -->
                    <h2>Available Flights:</h2>
                    <ul id="available-flights" class="available-flights">
                        <?php if (!empty($flights_result)): ?>
                            <?php foreach ($flights_result as $flight): ?>
                                <li class="available-flight" data-departure="<?= $flight['departure_date'] ?>" data-class="<?= $flight['seat_class'] ?>">
                                    <div>
                                        Departure: <?= $flight['departure_date'] ?>,
                                        Class: <?= ucfirst($flight['seat_class']) ?>,
                                        Price: £<?= number_format($flight['price'], 2) ?>
                                    </div>
                                    <form action="confirm_booking.php" method="POST">
                                        <input type="hidden" name="flight_id" value="<?= $flight['flight_id'] ?>" />
                                        <input type="hidden" name="departure_date" value="<?= $flight['departure_date'] ?>" />
                                        <input type="hidden" name="seat_class" value="<?= $flight['seat_class'] ?>" />
                                        <input type="hidden" name="number_of_passengers" value="1">
                                        <button type="submit">Proceed</button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No available flights for the selected destination and date.</p>
                        <?php endif; ?>
                    </ul>
                </form>
            </section>
        </main>

        <script>
            document.getElementById('number_of_passengers').addEventListener('input', function() {
                const passengerCount = this.value;
                document.querySelectorAll('#available-flights form input[name="number_of_passengers"]').forEach(input => {
                    input.value = passengerCount;
                });
            });
        </script>

        <!-- shared footer -->
        <?php include 'footerlinks.php'; ?>

    </div>
</body>
</html>