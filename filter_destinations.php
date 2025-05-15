<?php
session_start(); // start the session
require_once 'config.php'; // database connection

// collect and validate filter inputs
$keywords = isset($_POST['keywords']) ? explode(',', $_POST['keywords']) : [];
$price_range = $_POST['price_range'] ?? '';

// query matching destinations
$sql = "
    SELECT
        d.destination_id,
        d.name,
        d.country,
        MIN(fs.price_per_seat) AS economy_price,
        SUM(fs.available_seats) AS total_economy_seats
    FROM destinations d
    JOIN flights f ON f.destination_id = d.destination_id
    JOIN flight_seats fs ON fs.flight_id = f.flight_id AND fs.seat_class = 'economy'
";

//keyword filter
if (!empty($keywords)) {
    $sql .= "JOIN destination_keywords dk ON dk.destination_id = d.destination_id ";
    $sql .= "WHERE dk.keyword IN (" . implode(',', array_fill(0, count($keywords), '?')) . ") ";
}

$sql .= "GROUP BY d.destination_id, d.name, d.country ORDER BY economy_price ASC";

// prepare statement
$stmt = $conn->prepare($sql);
if (!empty($keywords)) {
    $stmt->bind_param(str_repeat('s', count($keywords)), ...$keywords);
}

//execute and fetch
$stmt->execute();
$results = $stmt->get_result();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Available Destinations</title>
    <link rel="stylesheet" href="css.css">
</head>

<body>
    <div id="pagewrapper">
        <nav id="headerlinks">
            <ul>
                <?php if (isset($_SESSION['user_id'])): ?>
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
        <h1>Available Destinations</h1>

            <div class="destination-list">
                <?php while ($row = $results->fetch_assoc()): ?>
                    <div class="destination-card">
                        <h2><?= htmlspecialchars($row['name']) ?>, <?= htmlspecialchars($row['country']) ?></h2>
                        <p>From £<?= number_format($row['economy_price'], 2) ?> — <?= intval($row['total_economy_seats']) ?> seats available</p>
                        <form action="book.php" method="GET">
                            <input type="hidden" name="destination_id" value="<?= $row['destination_id'] ?>">
                            <label>Select Departure Date:</label>
                            <input type="date" name="departure_date" required>
                            <button type="submit">Book Now</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            </div>
        </main>

        <!-- shared footer -->
        <?php include 'footerlinks.php'; ?>
    </div>
</body>
</html>