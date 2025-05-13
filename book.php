<?php
session_start(); // start the session
require_once 'config.php'; // database connection

// check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// initialise variables
$destination_id      = intval($_GET['destination_id'] ?? 0);
$destination_name    = '';
$destination_country = '';

if ($destination_id > 0) {
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Book a Flight</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css.css">
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
            <section>
                <h1>Book a Flight</h1>
                <form action="confirm_booking.php" method="POST">
                    <label>From:</label>
                    <input
                        type="text"
                        name="origin"
                        value="London Heathrow (LHR)"
                        readonly
                    ><br><br>

                    <!-- destination selector -->
                    <label>To:</label>
                    <select name="destination_id" required>
                        <option value="">Select destination</option>
                        <?php while ($row = $allDestStmt->fetch_assoc()): ?>
                            <option
                                value="<?= $row['destination_id'] ?>"
                                <?= (int)$row['destination_id'] === (int)$destination_id ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($row['name']) ?>,
                                <?= htmlspecialchars($row['country']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select><br><br>

                    <!-- departure date input -->
                    <label>Departure Date:</label>
                    <input type="date" name="departure_date" required><br><br>

                    <!-- number of passengers -->
                    <label>Number of Passengers:</label>
                    <input
                        type="number"
                        name="number_of_passengers"
                        min="1"
                        value="1"
                        required
                    ><br><br>

                    <!-- seat class -->
                    <label>Seat Class:</label>
                    <select name="seat_class" required>
                        <option value="economy">Economy</option>
                        <option value="business">Business</option>
                        <option value="first">First</option>
                    </select><br><br>

                    <button type="submit">Proceed</button>
                </form>
            </section>
    	</main>

	    <!-- shared footer -->
	    <?php include 'footerlinks.php'; ?>
    </div>
</body>
</html>

<?php
$allDestStmt->free(); // clean up
$conn->close();
?>