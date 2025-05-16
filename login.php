<?php
session_start(); // start the session
require_once 'config.php'; // database connection

$message = ""; // initialise message variable

// check if form is submitted
if (isset($_POST['email'], $_POST['password'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // check if the user exists
    $stmt = $conn->prepare("SELECT user_id, password_hash FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($password, $user['password_hash'])) {
        // login successful
        $_SESSION['user_id'] = $user['user_id'];

        // if they came from a pending booking then finish that first
        if (isset($_SESSION['pending_booking'])) {
            header('Location: finalise_booking.php');
            exit;
        }

        // otherwise go to their account
        header('Location: account.php');
        exit;
    } else {
        $error = "Invalid email or password.";
    }
}
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
            <section class="centered-form">
                <h1>Login</h1>
                <form action="login.php" method="POST">
                        <div class="form-group">
                            <label>Email:</label>
                            <input type="email" name="email" required value="<?= htmlspecialchars($email ?? '') ?>"><br><br>
                        </div>
                        <div class="form-group">
                            <label>Password:</label>
                            <input type="password" name="password" required><br><br>
                        </div>
                        <div class="form-group">
                            <button type="submit">Log In</button>
                        </div>
                    <div class="message-box"><?= $message ?></div>
                </form>
            </section>
        </main>

        <!-- shared footer -->
        <?php include 'footerlinks.php'; ?>

	</div>
</body>
</html>