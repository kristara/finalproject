<?php
session_start(); // start the session
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<title>Holiday Match</title>
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
				<h1>Welcome to Holiday Match</h1>
				<p>Find and book your perfect trip!</p>
			</section>

			<section>
				<!-- Form that submits selected keywords to the PHP filtering script -->
				<form action="filter_destinations.php" method="POST">
					<h2>Choose destination features:</h2>
					<!-- Checkbox filters for destination types -->
					<label><input type="checkbox" name="keywords[]" value="beach"> Beach</label>
					<label><input type="checkbox" name="keywords[]" value="city"> City</label>
					<label><input type="checkbox" name="keywords[]" value="mountains"> Mountains</label>
					<label><input type="checkbox" name="keywords[]" value="snow"> Snow</label>
					<label><input type="checkbox" name="keywords[]" value="adventure"> Adventure</label>
					<label><input type="checkbox" name="keywords[]" value="luxury"> Luxury</label>
					<label><input type="checkbox" name="keywords[]" value="budget"> Budget</label>
					<label><input type="checkbox" name="keywords[]" value="cultural"> Cultural</label>
					<label><input type="checkbox" name="keywords[]" value="island"> Island</label>
					<label><input type="checkbox" name="keywords[]" value="romantic"> Romantic</label>

					<!--  filters for price -->
					<h3>Select Price Range:</h3>
					<label><input type="checkbox" name="price_ranges[]" value="0-500"> Under £500</label>
					<label><input type="checkbox" name="price_ranges[]" value="500-1000"> £500 - £1,000</label>
					<label><input type="checkbox" name="price_ranges[]" value="1000-1500"> £1,000 - £2,000</label>
					<label><input type="checkbox" name="price_ranges[]" value="1500-2000"> £2,000 - £3,000</label>
					<!-- Submit button to trigger PHP filter script -->
					<br><br>
					<button type="submit">Find Destinations</button>
				</form>
			</section>
		</main>

		<!-- shared footer -->
		<?php include 'footerlinks.php'; ?>

	</div>
</body>
</html>