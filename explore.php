<?php
session_start(); // start the session
require_once 'config.php'; // database connection
if (!isset($conn) || !$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// fetch and validate filter inputs
$keywords = isset($_POST['keywords']) ? explode(',', $_POST['keywords']) : [];
$price_range = $_POST['price_range'] ?? '';

// sql query
$sql = "
    SELECT
        d.destination_id,
        d.name,
        d.country,
        MIN(fs.price_per_seat) AS economy_price
    FROM destinations d
    JOIN flights f ON f.destination_id = d.destination_id
    JOIN flight_seats fs ON fs.flight_id = f.flight_id
    WHERE fs.seat_class = 'economy'
";

// keyword filtering
if (!empty($keywords)) {
    $sql .= " AND d.destination_id IN (
                SELECT dk.destination_id
                FROM destination_keywords dk
                WHERE dk.keyword IN ('" . implode("','", array_map('trim', $keywords)) . "')
            )";
}

// apply price range if selected
if (!empty($price_range)) {
    list($min_price, $max_price) = explode('_', $price_range);
    $sql .= " AND fs.price_per_seat BETWEEN $min_price AND $max_price";
}

$sql .= " GROUP BY d.destination_id, d.name, d.country ORDER BY d.name ASC";

$stmt = $conn->prepare($sql);
$stmt->execute();
$results = $stmt->get_result();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Explore Destinations</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css.css">
    <script src="js/filter.js"></script>
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
            <div class="destination-list">
                <?php while ($row = $results->fetch_assoc()): ?>
                    <?php
                        // generate image filename for both .jpg and .jpeg
                        $imageBaseName = strtolower(str_replace(' ', '_', $row['name'])) . "_" . strtolower(str_replace(' ', '_', $row['country']));
                        $imagePathJpg = "images/{$imageBaseName}.jpg";
                        $imagePathJpeg = "images/{$imageBaseName}.jpeg";

                        //check if the image exists as .jpg or .jpeg
                        if (file_exists($imagePathJpg)) {
                            $imageSrc = $imagePathJpg;
                        } elseif (file_exists($imagePathJpeg)) {
                            $imageSrc = $imagePathJpeg;
                        } else {
                            $imageSrc = "images/default.jpg";
                        }
                    ?>
                    <div class="destination-card">
                        <img src="<?= $imageSrc ?>" alt="<?= htmlspecialchars($row['name']) ?>, <?= htmlspecialchars($row['country']) ?>" width="300" height="200">
                        <h2><?= htmlspecialchars($row['name']) ?>, <?= htmlspecialchars($row['country']) ?></h2>
                        <p>From £<?= number_format($row['economy_price'], 2) ?></p>
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