<?php
session_start(); // Start the session
include 'config.php'; // database connection

$message = ""; // Initialise message variable

// Check if form is submitted
if (isset($_SERVER["REQUEST_METHOD"]) && $_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim(htmlspecialchars($_POST['email']));
    $password = trim($_POST['password']);

    // Validate email and password
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($password)) {
        $message = "Invalid email or password.";
    } else {
        // Check if the user exists
        $sql = "SELECT user_id, email, password_hash FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password_hash'])) {
                // Successful login
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['email'] = $user['email'];
                header("Location: account.php");
                exit();
            } else {
                $message = "Incorrect password.";
            }
        } else {
            $message = "No account found with that email.";
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<title>holidayMatch</title>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css.css">
</head>

<body>
	<div id="pagewrapper">
		<nav id="headerlinks">
			<ul>
				<li><a href="registration.php">Registration</a></li>
			</ul>
		</nav>
	    <header>
			<h1><a href="holidayMatch.html">Holiday Match</a></h1>
		</header>
	    <nav id="primarynav">
			<ul>
				<li class="current"><a href="holidayMatch.html">Home</a></li>
				<li><a href="explore.html">Explore</a></li>
				<li><a href="/book.php">Book</a></li>
				<li><a href="/managebooking.php">Manage Booking</a></li>
			</ul>
		</nav>

		<section>
            <h2>Login</h2>
            <form action="login.php" method="POST">
                <label>Email:</label>
                <input type="email" name="email" required><br><br>

                <label>Password:</label>
                <input type="password" name="password" required><br><br>

                <button type="submit">Log In</button>
            </form>
        </section>

        <div class="message-box">
            <?php if (!empty($message)) echo $message; ?>
        </div>

		<footer>
			<nav id="footerlinks">
				<ul>
					<li><a href="termsofuse.html"> Terms of Use &#124;</a></li>
					<li><a href="copyright.html">Copyright &#124;</a></li>
					<li><a href="contactus.html"> Contact Us</a></li>
				</ul>
			</nav>
		</footer>
	</div>
</body>
</html>