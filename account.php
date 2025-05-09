<?php
session_start(); // Start the session
include 'config.php'; // database connection

// Check whether user is logged in already
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
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
            xxxxxxxxxxxxxxxx
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