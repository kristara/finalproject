<?php
session_start(); // start the session
require_once 'config.php'; // database connection

// fetch all destinations with their cheapest economy price and seat availability
$sql = "
    SELECT
        d.destination_id,
        d.name,
        d.country,
        MIN(fs.price_per_seat) AS economy_price,
        SUM(fs.available_seats) AS total_economy_seats
    FROM destinations d
    JOIN flights f ON f.destination_id = d.destination_id
    JOIN flight_seats fs ON fs.flight_id = f.flight_id
        AND fs.seat_class = 'economy'
    GROUP BY d.destination_id, d.name, d.country
    ORDER BY d.name ASC
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$results = $stmt->get_result();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Explore Destinations</title>
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
            <h1>Explore Destinations</h1>
            <section class="explore-section">
                <?php if ($results->num_rows === 0): ?>
                    <p>No destinations are currently available.</p>
                <?php else: ?>
                    <?php while ($row = $results->fetch_assoc()): ?>
                        <div class="destination-card">
                            <!-- Placeholder image, replace with actual images later -->
                            <img src="images/paris.webp" alt="Paris">
                            <h2>
                                <?= htmlspecialchars($row['name']) ?>
                                (<?= htmlspecialchars($row['country']) ?>)
                            </h2>
                            <p>
                                From £<?= number_format($row['economy_price'], 2) ?><br>
                                <?= intval($row['total_economy_seats']) ?> seats available
                            </p>

                            <!-- Add a date picker for dynamic departure date -->
                            <form action="book.php" method="GET">
                                <input type="hidden" name="destination_id" value="<?= $row['destination_id'] ?>">
                                <label for="departure_date_<?= $row['destination_id'] ?>">Select Departure Date:</label>
                                <input type="date" name="departure_date" id="departure_date_<?= $row['destination_id'] ?>" required>
                                <button type="submit">Book Now</button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </section>
        </main>

        <!-- shared footer -->
        <?php include 'footerlinks.php'; ?>
    </div>
</body>
</html>