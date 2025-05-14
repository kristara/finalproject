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
			<section class="section-filters">
				<!-- Form that submits selected keywords to the PHP filtering script -->
				<form action="filter_destinations.php" method="POST">
					<h2>Choose destination features:</h2>
					<div class="filter-buttons">
						<button type="button" class="filter-option" data-value="beach">🏖️ Beach</button>
						<button type="button" class="filter-option" data-value="city">🏙️ City</button>
						<button type="button" class="filter-option" data-value="mountains">⛰️ Mountains</button>
						<button type="button" class="filter-option" data-value="snow">❄️ Snow</button>
						<button type="button" class="filter-option" data-value="adventure">🏕️ Adventure</button>
						<button type="button" class="filter-option" data-value="luxury">💎 Luxury</button>
						<button type="button" class="filter-option" data-value="budget">💰 Budget</button>
						<button type="button" class="filter-option" data-value="cultural">🏛️ Cultural</button>
						<button type="button" class="filter-option" data-value="island">🏝️ Island</button>
						<button type="button" class="filter-option" data-value="romantic">❤️ Romantic</button>
					</div>

					<!--  filters for price -->
					<h3>Price Range</h3>
					<div class="filter-buttons">
						<button type="button" class="filter-price" data-value="under_500">Under £500</button>
						<button type="button" class="filter-price" data-value="500_1000">£500 - £1,000</button>
						<button type="button" class="filter-price" data-value="1000_2000">£1,000 - £2,000</button>
						<button type="button" class="filter-price" data-value="2000_3000">£2,000 - £3,000</button>
					</div>

					<form action="explore.php" method="GET">
							<input type="hidden" name="keywords" id="selected-keywords">
							<input type="hidden" name="price_range" id="selected-price-range">

					<!-- Submit button to trigger PHP filter script -->
					<button type="submit" class="find-btn">Find Destinations</button>
				</form>
			</section>
		</main>

		<!-- shared footer -->
		<?php include 'footerlinks.php'; ?>

		<!-- javaScript for Toggle Effect -->
				
		<script>
			document.addEventListener("DOMContentLoaded", function() {
				// Toggle selection for destination features
				document.querySelectorAll(".filter-option").forEach(button => {
					button.addEventListener("click", function() {
						this.classList.toggle("active");
						updateSelectedKeywords();
					});
				});

				// selection for price range
				document.querySelectorAll(".filter-price").forEach(button => {
					button.addEventListener("click", function() {
						document.querySelectorAll(".filter-price").forEach(btn => btn.classList.remove("active"));
						this.classList.add("active");
						updateSelectedPrice();
					});
				});

				function updateSelectedKeywords() {
					const selectedKeywords = Array.from(document.querySelectorAll(".filter-option.active"))
						.map(button => button.getAttribute("data-value"));
					document.getElementById("selected-keywords").value = selectedKeywords.join(",");
				}

				function updateSelectedPrice() {
					const selectedPrice = document.querySelector(".filter-price.active")?.getAttribute("data-value") || "";
					document.getElementById("selected-price-range").value = selectedPrice;
				}
			});
		</script>

	</div>
</body>
</html>