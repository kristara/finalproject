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
            <div class="destination-list">
                <?php
                $destinations = [
                    ['Bali', 'Indonesia'], ['Paris', 'France'], ['Swiss Alps', 'Switzerland'],
                    ['Dubai', 'UAE'], ['Maldives', 'Maldives'], ['Tokyo', 'Japan'],
                    ['New York', 'USA'], ['Zakynthos', 'Greece'], ['Banff', 'Canada'],
                    ['Machu Picchu', 'Peru'], ['Barcelona', 'Spain'], ['Hawaii', 'USA'],
                    ['Prague', 'Czech Republic'], ['Rome', 'Italy'], ['Sydney', 'Australia'],
                    ['Cape Town', 'South Africa'], ['Rio de Janeiro', 'Brazil'], ['Lapland', 'Finland'],
                    ['Vienna', 'Austria'], ['Tromso', 'Norway'], ['Amsterdam', 'Netherlands'],
                    ['Cairo', 'Egypt'], ['Los Angeles', 'USA'], ['Seoul', 'South Korea'],
                    ['Phuket', 'Thailand'], ['Lisbon', 'Portugal'], ['Venice', 'Italy'],
                    ['Edinburgh', 'Scotland'], ['Bora Bora', 'French Polynesia'], ['Bangkok', 'Thailand'],
                    ['Kathmandu', 'Nepal'], ['Hong Kong', 'China'], ['Mykonos', 'Greece'],
                    ['Santorini', 'Greece'], ['Reykjavik', 'Iceland'], ['Nairobi', 'Kenya'],
                    ['Buenos Aires', 'Argentina'], ['Marrakech', 'Morocco'], ['Auckland', 'New Zealand'],
                    ['Doha', 'Qatar'], ['Abu Dhabi', 'UAE'], ['Athens', 'Greece'],
                    ['Zermatt', 'Switzerland'], ['Ho Chi Minh City', 'Vietnam'], ['Havana', 'Cuba'],
                    ['Delhi', 'India'], ['Stockholm', 'Sweden'], ['Montreal', 'Canada'],
                    ['Oslo', 'Norway'], ['Fiji', 'Fiji']
                ];
                foreach ($destinations as $index => $destination) {
                    $name = $destination[0];
                    $country = $destination[1];
                    
                    // generate image filename for both .jpg and .jpeg
                    $imageBaseName = strtolower(str_replace(' ', '_', $name)) . "_" . strtolower(str_replace(' ', '_', $country));
                    $imagePathJpg = "images/{$imageBaseName}.jpg";
                    $imagePathJpeg = "images/{$imageBaseName}.jpeg";

                    // check if the image exists as .jpg or .jpeg
                    $imageSrc = file_exists($imagePathJpg) ? $imagePathJpg : (file_exists($imagePathJpeg) ? $imagePathJpeg : "images/default.jpg");
                ?>
                <div class="destination-card">
                    <img src="<?= $imageSrc ?>" alt="<?= $name ?>, <?= $country ?>">
                    <h2><?= $name ?>, <?= $country ?></h2>
                    <p>From £200 — 50 seats available</p>
                    <form action="book.php" method="GET">
                        <label>Select Departure Date:</label>
                        <input type="date" name="departure_date" required>
                        <input type="hidden" name="destination_id" value="<?= $index + 1 ?>">
                        <button type="submit">Book Now</button>
                    </form>
                </div>
                <?php } ?>
            </div>
        </main>

        <!-- shared footer -->
        <?php include 'footerlinks.php'; ?>
    </div>
</body>
</html>