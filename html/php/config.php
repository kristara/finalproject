<?php
$servername = "localhost"; // Or your database server
$username = "root"; // Your MySQL username
$password = "Mikins2022**"; // Your MySQL password (empty if using XAMPP)
$dbname = "reservation_system"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// if (mysqli_connect_errno()) {
//    printf("Connect failed: %s <br />",
//   mysqli_connect_error());
//exit();
//}
?>
