<?php
session_start(); // start the session
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<title>Holiday Match</title>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css.css">
	<script>
		<!-- javaScript for Toggle Effect -->
        document.addEventListener("DOMContentLoaded", function() {
            // destination features
            document.querySelectorAll(".filter-option").forEach(button => {
                button.addEventListener("click", function() {
                    this.classList.toggle("active");
                    updateSelectedKeywords();
                });
            });

            //price range
            document.querySelectorAll(".filter-price").forEach(button => {
                button.addEventListener("click", function() {
                    document.querySelectorAll(".filter-price").forEach(btn => btn.classList.remove("active"));
                    this.classList.add("active");
                    updateSelectedPrice();
                });
            });

			// update selected keywords
            function updateSelectedKeywords() {
                const selectedKeywords = Array.from(document.querySelectorAll(".filter-option.active"));
                if (selectedKeywords.length > 3) {
                    alert("You can only select up to 3 keywords.");
                    selectedKeywords[selectedKeywords.length - 1].classList.remove("active");
                }
                document.getElementById("selected-keywords").value = selectedKeywords.map(btn => btn.getAttribute("data-value")).join(",");
            }

			//update selected price
            function updateSelectedPrice() {
                const selectedPrice = document.querySelector(".filter-price.active")?.getAttribute("data-value") || "";
                document.getElementById("selected-price-range").value = selectedPrice;
            }

            //ensure form submits correctly
            document.getElementById("filter-form").addEventListener("submit", function(event) {
                updateSelectedKeywords();
                updateSelectedPrice();

                const keywords = document.getElementById("selected-keywords").value;
                const price = document.getElementById("selected-price-range").value;

                if (!keywords || !price) {
                    alert("Please select at least one keyword and one price range.");
                    event.preventDefault();
                }
            });
        });
    </script>
</head>

<body>
	<div id="pagewrapper">
		<nav id="headerlinks">
			<ul>
				<?php if (isset($_SESSION['user_id'])): ?>
					<li><a href="account.php">My Account &#124;</a></li>
					<li><a href="logout.php">Log Out</a></li>
				<?php else: ?>
					<li><a href="registration.php">Register &#124;</a></li>
					<li><a href="login.php">Log In</a></li>
				<?php endif; ?>
			</ul>
		</nav>

		<!-- shared header and primary nav -->
		<?php include 'primarynav.php'; ?>

		<main>
			<!-- Form that submits selected keywords to the PHP filtering script -->
            <form action="filter_destinations.php" method="POST" id="filter-form">
                <div class="section-filters">
                    <h2>Choose Your Destination</h2>
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

					<!--  filters for price filtering script -->
                    <h2>Price Range</h2>
                    <div class="filter-buttons">
                        <button type="button" class="filter-price" data-value="0_500">Under £500</button>
                        <button type="button" class="filter-price" data-value="500_1000">£500 - £1,000</button>
                        <button type="button" class="filter-price" data-value="1000_2000">£1,000 - £2,000</button>
                        <button type="button" class="filter-price" data-value="2000_3000">£2,000 - £3,000</button>
                    </div>

					<input type="hidden" name="keywords" id="selected-keywords">
                    <input type="hidden" name="price_range" id="selected-price-range">
                </div>
				<!-- submit button to trigger PHP filter script -->
				<button type="submit" class="find-btn">Find Destinations</button>
			</form>
		</main>

		<!-- shared footer -->
		<?php include 'footerlinks.php'; ?>
	</div>
</body>
</html>