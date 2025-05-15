<?php
session_start(); // start the session
require_once 'config.php'; // database connection

// check whether user is logged in already
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id']; // fetch user id

// fetch users account info
$stmt = $conn->prepare("
    SELECT first_name, last_name, email, phone_number, city, country, dob, address
    FROM users
    WHERE user_id = ?
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// fetch users reservations
$resStmt = $conn->prepare("
    SELECT
        r.reservation_id,
        f.origin,
        d.name AS destination,
        f.departure_date,
        r.seat_class,
        r.number_of_passengers,
        r.status,
        r.total_price
    FROM reservations r
    JOIN flights f ON r.flight_id = f.flight_id
    JOIN destinations d ON f.destination_id = d.destination_id
    WHERE r.user_id = ?
    ORDER BY f.departure_date ASC
");
$resStmt->bind_param('i', $user_id);
$resStmt->execute();
$reservations = $resStmt->get_result();
$resStmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<title>My Account</title>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css.css">
</head>

<body>
	<div id="pagewrapper">
		<nav id="headerlinks">
            <ul>
                <li><a href="logout.php">Log Out</a></li>
            </ul>
        </nav>

        <!-- shared header and primary nav -->
        <?php include 'primarynav.php'; ?>

        <main class="account-page">
            <div class="account-info">
                <h1>Welcome, <?= htmlspecialchars($user['first_name']) ?></h1>
                <div class="form-group">
                    <div><strong>First Name:</strong> <?= htmlspecialchars($user['first_name']) ?></div>
                    <div><strong>Last Name:</strong> <?= htmlspecialchars($user['last_name']) ?></div>
                    <div><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></div>
                    <div><strong>Phone:</strong> <?= htmlspecialchars($user['phone_number'] ?: 'Not Provided') ?></div>
                    <div><strong>City:</strong> <?= htmlspecialchars($user['city']) ?></div>
                    <div><strong>Country:</strong> <?= htmlspecialchars($user['country']) ?></div>
                    <div><strong>Date of Birth:</strong> <?= htmlspecialchars($user['dob']) ?></div>
                    <div><strong>Address:</strong> <?= htmlspecialchars($user['address'] ?: 'Not Provided') ?></div>
                </div>

                <div class="account-buttons">
                    <button onclick="confirmDelete()">Delete Account</button>
                </div>
            </div>

            <div class="reservations-table">
                <h2>Your Reservations</h2>
                <?php if ($reservations->num_rows === 0): ?>
                    <p>You have no reservations.</p>
                    <p><a href="book.php" class="btn">Book a flight</a></p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Date</th>
                                <th>Class</th>
                                <th>Passengers</th>
                                <th>Status</th>
                                <th>Total</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($res = $reservations->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($res['reservation_id']) ?></td>
                                <td><?= htmlspecialchars($res['origin']) ?></td>
                                <td><?= htmlspecialchars($res['destination']) ?></td>
                                <td><?= htmlspecialchars($res['departure_date']) ?></td>
                                <td><?= ucfirst(htmlspecialchars($res['seat_class'])) ?></td>
                                <td><?= intval($res['number_of_passengers']) ?></td>
                                <td><?= ucfirst(htmlspecialchars($res['status'])) ?></td>
                                <td>£<?= number_format($res['total_price'], 2) ?></td>
                                <td>
                                    <a href="modify_booking.php?res_id=<?= urlencode($res['reservation_id']) ?>" class="btn">Modify</a>
                                    |
                                    <form action="cancel_booking.php" method="POST" class="inline">
                                        <input type="hidden" name="reservation_id" value="<?= htmlspecialchars($res['reservation_id']) ?>">
                                        <button type="submit" class="cancel" onclick="return confirm('Cancel this booking?')">Cancel</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>

        <!-- shared footer -->
        <?php include 'footerlinks.php'; ?>
        <script>
            function confirmDelete() {
                if (confirm("Are you sure you want to delete your account? This action cannot be undone.")) {
                    window.location.href = 'delete_account.php';
                }
            }
        </script>
    </div>
</body>
</html>