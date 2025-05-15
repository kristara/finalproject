<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();           //start session
require_once 'config.php'; // db connection

$message = ""; // initialise message variable

// force check for REQUEST_METHOD to avoid errors
if (!empty($_POST['first_name']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // form data and trim whitespace
    $first_name   = htmlspecialchars(trim($_POST['first_name'] ?? ''));
    $middle_name  = htmlspecialchars(trim($_POST['middle_name'] ?? ''));
    $last_name    = htmlspecialchars(trim($_POST['last_name'] ?? ''));
    $email        = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password     = $_POST['password'] ?? '';
    $phone_number = htmlspecialchars(trim($_POST['phone_number'] ?? ''));
    $city         = htmlspecialchars(trim($_POST['city'] ?? ''));
    $post_code    = htmlspecialchars(trim($_POST['post_code'] ?? ''));
    $country      = htmlspecialchars(trim($_POST['country'] ?? ''));
    $dob          = $_POST['dob'] ?? '';
    $address      = htmlspecialchars(trim($_POST['address'] ?? ''));

    // validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '<div class="error-message">Invalid email format.</div>';
    } elseif (strlen($password) < 8
                || !preg_match("/[A-Za-z]/", $password)
                || !preg_match("/[0-9]/", $password)) {
        $message = "<div class='error-message'>Password must be at least 8 characters long and contain both letters and numbers.</div>";
    } else {
        // check if email already exists
        $stmt = $conn->prepare('SELECT 1 FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "<div class='error-message'>An account with this email already exists.</div>";
            $stmt->close();
        } else {
            $stmt->close();

            // hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // insert user data into the database
            $stmt = $conn->prepare( "INSERT INTO users (first_name, middle_name, last_name, email, password_hash, phone_number, post_code, city, country, dob, address)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
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
                // set session variables
                $_SESSION['user_id'] = $conn->insert_id;
                $_SESSION['email'] = $email;

                // redirect to finalise booking if pending
                if (isset($_SESSION['pending_booking'])) {
                    header('Location: finalise_booking.php');
                    exit;
                }

                $message = "<div class='success-message'>
                    Registration successful! You can now <a href='login.php'>log in here</a>.
                </div>";
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
    <title>Register</title>
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
            <section class="centered-form">
                <h1>Register</h1>
                <form action="registration.php" method="POST">
                    <div class="form-group">
                        <label>First Name:</label>
                        <input type="text" name="first_name" required><br><br>
                    </div>
                    <div class="form-group">
                        <label>Middle Name (optional):</label>
                        <input type="text" name="middle_name"><br><br>
                    </div>
                    <div class="form-group">
                        <label>Last Name:</label>
                        <input type="text" name="last_name" required><br><br>
                    </div>
                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" name="email" required><br><br>
                    </div>
                    <div class="form-group">
                        <label>Password:</label>
                        <input type="password" name="password" required><br><br>
                    </div>
                    <div class="form-group">
                        <label>Phone Number (optional):</label>
                        <input type="text" name="phone_number"><br><br>
                    </div>
                    <div class="form-group">
                        <label>City:</label>
                        <input type="text" name="city" required><br><br>
                    </div>
                    <div class="form-group">
                        <label>Post Code:</label>
                        <input type="text" name="post_code" required><br><br>
                    </div>
                    <div class="form-group">
                        <label>Country:</label>
                        <input type="text" name="country" required><br><br>
                    </div>
                    <div class="form-group">
                        <label>Date of Birth:</label>
                        <input type="date" name="dob" required><br><br>
                    </div>
                    <div class="form-group">
                        <label>Address (optional):</label>
                        <input type="text" name="address"><br><br>
                    </div>
                    <div class="form-group">
                        <button type="submit">Register</button>
                    </div>
                </form>
                <div class="message-box">
                    <?= $message ?>
                </div>
            </section>
        </main>

        <!-- shared footer -->
        <?php include 'footerlinks.php'; ?>

    </div>
</body>
</html>