<?php
session_start(); // start the session
require_once 'config.php'; // database connection

// collect and validate filter inputs
$keywords = isset($_POST['keywords']) ? explode(',', $_POST['keywords']) : [];
$price_range = $_POST['price_range'] ?? '';

// validate price range
$valid_price_ranges = ['0_500', '500_1000', '1000_2000', '2000_3000'];
if (!in_array($price_range, $valid_price_ranges)) {
    die("Error: Invalid price range format. Please select a valid price range.");
}

// query matching destinations using subquery for lowest price per seat class
$sql = "
    SELECT
        d.destination_id,
        d.name,
        d.country,
        MIN(CASE WHEN fs.seat_class = 'economy' THEN fs.price_per_seat ELSE NULL END) AS economy_price,
        MIN(CASE WHEN fs.seat_class = 'business' THEN fs.price_per_seat ELSE NULL END) AS business_price,
        MIN(CASE WHEN fs.seat_class = 'first' THEN fs.price_per_seat ELSE NULL END) AS first_price,
        COUNT(DISTINCT dk.keyword) AS matching_keywords
    FROM destinations d
    JOIN flights f ON f.destination_id = d.destination_id
    JOIN flight_seats fs ON fs.flight_id = f.flight_id
    LEFT JOIN destination_keywords dk ON dk.destination_id = d.destination_id
";

// filters
$conditions = [];
$params = [];
$types = '';

// keyword filter
if (!empty($keywords)) {
    $keywordCount = count($keywords);
    $placeholders = implode(',', array_fill(0, $keywordCount, '?'));
    $conditions[] = "dk.keyword IN ($placeholders)";
    $types .= str_repeat('s', $keywordCount);
    $params = array_merge($params, $keywords);
}

// apply price filter
if ($price_range) {
    list($minPrice, $maxPrice) = explode('_', $price_range);
    $conditions[] = "(
        (fs.seat_class = 'economy' AND fs.price_per_seat BETWEEN ? AND ?) OR
        (fs.seat_class = 'business' AND fs.price_per_seat BETWEEN ? AND ?) OR
        (fs.seat_class = 'first' AND fs.price_per_seat BETWEEN ? AND ?)
    )";
    $types .= 'dddddd';
    $params[] = (float)$minPrice;
    $params[] = (float)$maxPrice;
    $params[] = (float)$minPrice;
    $params[] = (float)$maxPrice;
    $params[] = (float)$minPrice;
    $params[] = (float)$maxPrice;
}

// combine conditions
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}

// grouping by destination and counting matching keywords
$sql .= "
    GROUP BY d.destination_id, d.name, d.country
    HAVING
        (COUNT(DISTINCT dk.keyword) >= 1 AND $keywordCount = 1) OR
        (COUNT(DISTINCT dk.keyword) = 2 AND $keywordCount = 2) OR
        (COUNT(DISTINCT dk.keyword) >= 2 AND $keywordCount >= 3)
    ORDER BY
        LEAST(
            IFNULL(economy_price, 999999),
            IFNULL(business_price, 999999),
            IFNULL(first_price, 999999)
        ) ASC
";

// prepare statement
$stmt = $conn->prepare($sql);

// bind parameters dynamically
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
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
                <?php while ($row = $results->fetch_assoc()):
                    // Determine image path
                    $imageBaseName = strtolower(str_replace(' ', '_', $row['name'])) . "_" . strtolower(str_replace(' ', '_', $row['country']));
                    $imagePathJpg = "images/{$imageBaseName}.jpg";
                    $imagePathJpeg = "images/{$imageBaseName}.jpeg";
                    $imageSrc = file_exists($imagePathJpg) ? $imagePathJpg : (file_exists($imagePathJpeg) ? $imagePathJpeg : "images/default.jpg");
                ?>
                    <div class="destination-card">
                        <img src="<?= $imageSrc ?>" alt="<?= htmlspecialchars($row['name']) ?>, <?= htmlspecialchars($row['country']) ?>" class="destination-image">
                        <h2><?= htmlspecialchars($row['name']) ?>, <?= htmlspecialchars($row['country']) ?></h2>
                        <p>
                            Starting from £<?php
                            $lowest_price = min(
                                $row['economy_price'] ?? 999999,
                                $row['business_price'] ?? 999999,
                                $row['first_price'] ?? 999999
                            );
                            echo number_format($lowest_price, 2);
                            ?>
                        </p>
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