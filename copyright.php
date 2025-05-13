<?php
session_start(); // start the session
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<title>Copyright</title>
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
			<section>
				<h1>Copyright</h1>
				<p>&copy; <?= date('Y') ?> Holiday Match. All rights reserved.</p>
			</section>
		</main>

		<!-- shared footer -->
		<?php include 'footerlinks.php'; ?>
	</div>
</body>
</html>