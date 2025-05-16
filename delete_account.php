<?php
session_start(); // start the session
require_once 'config.php'; // database connection

// check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// handle account deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // delete user account and all related bookings
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);

    if ($stmt->execute()) {
        // clear session and redirect
        session_unset();
        session_destroy();
        header('Location: account_deleted.php');
        exit();
    } else {
        $message = "<div class='error-message'>Unable to delete account. Please try again later.</div>";
    }

    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Account</title>
    <link rel="stylesheet" href="css.css">
</head>
<body>
    <div id="pagewrapper" class="centered-form">
        <h1>Delete Account</h1>

        <p>Are you sure you want to delete your account? This action cannot be undone.</p>

        <form method="POST" action="delete_account.php">
            <button type="submit" class="button">Delete Account</button>
            <a href="account.php" class="button">Cancel</a>
        </form>

        <div class="message-box">
            <?= $message ?>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>
