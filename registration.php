<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config.php';

$message = ""; // Initialise message variable

// Force check for REQUEST_METHOD to avoid errors
if (isset($_SERVER) && array_key_exists('REQUEST_METHOD', $_SERVER) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Form data and trim whitespace
    $first_name = trim(htmlspecialchars($_POST['first_name'] ?? ''));
    $middle_name = trim(htmlspecialchars($_POST['middle_name'] ?? ''));
    $last_name = trim(htmlspecialchars($_POST['last_name'] ?? ''));
    $email = trim(htmlspecialchars($_POST['email'] ?? ''));
    $password = trim($_POST['password'] ?? '');
    $phone_number = trim(htmlspecialchars($_POST['phone_number'] ?? ''));
    $post_code = trim(htmlspecialchars($_POST['post_code'] ?? ''));
    $city = trim(htmlspecialchars($_POST['city'] ?? ''));
    $country = trim(htmlspecialchars($_POST['country'] ?? ''));
    $dob = trim($_POST['dob'] ?? '');
    $address = trim(htmlspecialchars($_POST['address'] ?? ''));

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "<div class='error-message'>Invalid email format.</div>";
    } elseif (strlen($password) < 8 || !preg_match("/[A-Za-z]/", $password) || !preg_match("/[0-9]/", $password)) {
        $message = "<div class='error-message'>Password must be at least 8 characters long and contain both letters and numbers.</div>";
    } else {
        // Check if email already exists
        $email_check = "SELECT email FROM users WHERE email = ?";
        $stmt = $conn->prepare($email_check);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $message = "<div class='error-message'>An account with this email already exists.</div>";
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert user data into the database
            $query = "INSERT INTO users (first_name, middle_name, last_name, email, password_hash, phone_number, post_code, city, country, dob, address)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param(
                "sssssssssss",
                $first_name,
                $middle_name,
                $last_name,
                $email,
                $hashed_password,
                $phone_number,
                $post_code,
                $city,
                $country,
                $dob,
                $address
            );

            if ($stmt->execute()) {
                $message = "<div class='success-message'>Registration successful! You can now <a href='login.php'>log in here</a>.</div>";
            } else {
                $message = "<div class='error-message'>Error during registration: " . $conn->error . "</div>";
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
    <title>Registration - HolidayMatch</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css.css">
    <style>
        .message-box { margin-block-start: 15px; }
        .success-message { color: green; font-weight: bold; }
        .error-message { color: red; font-weight: bold; }
    </style>
</head>

<body>
    <div id="pagewrapper">
        <nav id="headerlinks">
			<ul>
				<li><a href="login.php">Log in</a></li>
			</ul>
		</nav>
        <header>
            <h1><a href="holidayMatch.html">Holiday Match</a></h1>
        </header>

        <nav id="primarynav">
            <ul>
                <li><a href="holidayMatch.html">Home</a></li>
                <li><a href="explore.html">Explore</a></li>
                <li><a href="/book.php">Book</a></li>
                <li><a href="/managebooking.php">Manage Booking</a></li>
            </ul>
        </nav>

        <section>
            <h2>Register</h2>
            <form action="registration.php" method="POST">
                <label>First Name:</label>
                <input type="text" name="first_name" required><br><br>

                <label>Middle Name (optional):</label>
                <input type="text" name="middle_name"><br><br>

                <label>Last Name:</label>
                <input type="text" name="last_name" required><br><br>

                <label>Email:</label>
                <input type="email" name="email" required><br><br>

                <label>Password:</label>
                <input type="password" name="password" required><br><br>

                <label>Phone Number (optional):</label>
                <input type="text" name="phone_number"><br><br>

                <label>City:</label>
                <input type="text" name="city" required><br><br>

                <label>Post Code:</label>
                <input type="text" name="post_code" required><br><br>

                <label>Country:</label>
                <input type="text" name="country" required><br><br>

                <label>Date of Birth:</label>
                <input type="date" name="dob" required><br><br>

                <label>Address (optional):</label>
                <input type="text" name="address"><br><br>

                <button type="submit">Register</button>
            </form>
        </section>

        <!-- Display message box -->
        <div class="message-box">
            <?php if (!empty($message)) echo $message; ?>
        </div>

        <footer>
            <nav id="footerlinks">
                <ul>
                    <li><a href="termsofuse.html">Terms of Use &#124;</a></li>
                    <li><a href="copyright.html">Copyright &#124;</a></li>
                    <li><a href="contactus.html">Contact Us</a></li>
                </ul>
            </nav>
        </footer>
    </div>
</body>
</html>