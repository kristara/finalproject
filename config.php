<?php
$servername = "localhost"; //  database server
$username = "root"; //  MySQL username
$password = "Mikins2022**"; //  MySQL password
$dbname = "reservation_system"; // database name

// create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo "Connected successfully!";
}
?>