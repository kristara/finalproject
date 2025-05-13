<?php
session_start(); // start the session
require_once 'config.php';// database connection

$error  = '';
$results = null;

//collect and validate filter inputs
$keywords     = $_POST['keywords']     ?? [];
$price_ranges = $_POST['price_ranges'] ?? [];

if (!is_array($keywords) || empty($keywords)) {
    $error = 'Please select at least one destination feature.';
} else {
    //build dynamic WHERE clauses
    $whereClauses = [];
    $params       = [];
    $types        = '';

    //keyword filter
    $placeholders = implode(',', array_fill(0, count($keywords), '?'));
    $whereClauses[] = "d.destination_id IN (
        SELECT destination_id
            FROM destination_keywords
            WHERE keyword IN ($placeholders)
    )";
    foreach ($keywords as $kw) {
        $types    .= 's';
        $params[]  = $kw;
    }

    //price range filter on fs.price_per_seat
    if (is_array($price_ranges) && count($price_ranges) > 0) {
        $sub = [];
        foreach ($price_ranges as $range) {
            list($min, $max) = explode('-', $range);
            $sub[]    = "(fs.price_per_seat BETWEEN ? AND ?)";
            $types   .= 'dd';
            $params[] = (float)$min;
            $params[] = (float)$max;
        }
        $whereClauses[] = '(' . implode(' OR ', $sub) . ')';
    }

    // combine
    $whereSQL = '';
    if ($whereClauses) {
        $whereSQL = 'AND ' . implode(' AND ', $whereClauses);
    }

    // query matching destinations
    $sql = "
        SELECT
            d.destination_id,
            d.name,
            d.country,
            MIN(fs.price_per_seat)   AS economy_price,
            SUM(fs.available_seats)  AS total_economy_seats
        FROM destinations d
        JOIN flights f
            ON f.destination_id = d.destination_id
        JOIN flight_seats fs
            ON fs.flight_id  = f.flight_id
            AND fs.seat_class = 'economy'
        WHERE 1=1
            {$whereSQL}
        GROUP BY d.destination_id, d.name, d.country
        ORDER BY economy_price ASC
    ";

    //prepare and combine
    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }

    //execute and fetch
    if ($stmt->execute()) {
        $results = $stmt->get_result();
    } else {
        $error = 'Database error: ' . htmlspecialchars($stmt->error);
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<title>Available Destinations</title>
	<meta charset="UTF-8">
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

            <?php if ($error): ?>
                <p class="error-message"><?= htmlspecialchars($error) ?></p>

            <?php elseif ($results && $results->num_rows === 0): ?>
                <p>No destinations found for your selected preferences.</p>

            <?php elseif ($results): ?>
                <ul class="destination-list">
                    <?php while ($row = $results->fetch_assoc()): ?>
                        <li>
                            <h2>
                                <?= htmlspecialchars($row['name']) ?>
                                (<?= htmlspecialchars($row['country']) ?>)
                            </h2>
                            <p>
                                From £<?= number_format($row['economy_price'], 2) ?>
                                &mdash; <?= intval($row['total_economy_seats']) ?>
                                seats available in Economy
                            </p>
                            <a href="book.php?destination_id=<?= $row['destination_id'] ?>">
                                Book Now
                            </a>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php endif; ?>
        </main>

        <!-- Footer -->
        <?php include 'footerlinks.php'; ?>

    </div>
</body>
</html>