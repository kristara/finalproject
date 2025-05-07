<?php
include 'config.php'; // Connect to the database

// Check if the form submitted keywords and they are in an array format
if (isset($_POST['keywords']) && is_array($_POST['keywords'])) {
    $keywords = $_POST['keywords'];

    // Sanitise each keyword to prevent SQL injection by escaping special characters
    // Then wrap each in quotes for safe use in the SQL IN() clause
    $safe_keywords = array_map(function($kw) use ($conn) {
        return "'" . mysqli_real_escape_string($conn, $kw) . "'";
    }, $keywords);

    // Convert the array of quoted keywords into a comma-separated string
    $keyword_list = implode(",", $safe_keywords);

     // Price range filtering
    $price_filter = "";
    if (isset($_POST['price_ranges']) && is_array($_POST['price_ranges'])) {
        $price_conditions = [];
        foreach ($_POST['price_ranges'] as $range) {
            list($min, $max) = explode('-', $range);
            $price_conditions[] = "(f.price BETWEEN $min AND $max)";
        }
        $price_filter = " AND (" . implode(" OR ", $price_conditions) . ")";
    }

    // SQL query to find distinct destinations that match any of the selected keywords and price
    $sql = "SELECT DISTINCT d.destination_id, d.name, d.country, MIN(f.price) AS lowest_price
            FROM destinations d
            JOIN destination_keywords dk ON d.destination_id = dk.destination_id
            JOIN flights f ON f.destination_id = d.destination_id
            WHERE dk.keyword IN ($keyword_list) $price_filter
            GROUP BY d.destination_id";

    // run the query
    $result = $conn->query($sql);

    // If matches were found then display them
    if ($result && $result->num_rows > 0) {
        echo "<h1>Destinations matching your choices:</h1>";
        echo "<ul>";

        // Loop through each matching destination and display it with a Book button
        while ($row = $result->fetch_assoc()) {
            // Output destination name and country safely
            echo "<li>" . htmlspecialchars($row['name']) . " (" . htmlspecialchars($row['country']) . ") - £" . htmlspecialchars($row['lowest_price']) . "";
            echo " <a href='book.php?destination_id=" . $row['destination_id'] . "'>Book</a>";
            echo "</li>";
        }
        echo "</ul>";
    } else {
        // No destinations matched the selected keywords
        echo "<p>No destinations found for your selected preferences.</p>";
    }
} else {
    // No keywords were selected in the form
    echo "<p>Select at least one keyword.</p>";
}
// Close the database connection
$conn->close();
?>