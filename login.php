<?php
session_start(); // start the session
require_once 'config.php'; // database connection

$message = ""; // initialise message variable

// check if form is submitted
if (isset($_SERVER["REQUEST_METHOD"]) && $_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim(htmlspecialchars($_POST['email'] ?? ''));
    $password = trim($_POST['password'] ?? '');

    // validate email and password
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($password)) {
        $message = "Invalid email or password.";
    } else {
        // check if the user exists
        $stmt = $conn->prepare("
            SELECT user_id, email, password_hash
                FROM users
                WHERE email = ?
        ");
        $stmt->bind_param('s', $email);

        if (! $stmt->execute()) {
            $message = '<p class="error-message">Database error: '
                    . htmlspecialchars($conn->error)
                    . '</p>';
        } else {
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                $message = '<p class="error-message">No account found with that email.</p>';
            } else {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password_hash'])) {
                    // successful login
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['email']   = $user['email'];

                    // if they came from a pending booking then finish that first
                    if (isset($_SESSION['pending_booking'])) {
                        header('Location: finalise_booking.php');
                        exit;
                    }
                    // otherwise go to their account
                    header('Location: account.php');
                    exit;
                } else {
                    $message = '<p class="error-message">Incorrect password.</p>';
                }
            }
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<title>Log In</title>
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
                <?php endif; ?>
			</ul>
		</nav>

        <!-- shared header and primary nav -->
        <?php include 'primarynav.php'; ?>

        <main>
            <section>
                <h2>Login</h2>
                <form action="login.php" method="POST">
                    <label>Email:</label>
                    <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"><br><br>

                    <label>Password:</label>
                    <input type="password" name="password" required><br><br>

                    <button type="submit">Log In</button>
                </form>
            </section>

            <div class="message-box"><?= $message ?></div>

        </main>
        <!-- shared footer -->
        <?php include 'footerlinks.php'; ?>
	</div>
</body>
</html>