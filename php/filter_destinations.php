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

    // SQL query to find distinct destinations that match any of the selected keywords
    $sql = "
        SELECT DISTINCT d.destination_id, d.name, d.country
        FROM destinations d
        JOIN destination_keywords dk ON d.destination_id = dk.destination_id
        WHERE dk.keyword IN ($keyword_list)
    ";

    // run the query
    $result = $conn->query($sql);

    // If matches were found then display them
    if ($result && $result->num_rows > 0) {
        echo "<h3>Destinations matching your choices:</h3>";
        echo "<ul>";

        // Loop through each matching destination and display it with a Book button
        while ($row = $result->fetch_assoc()) {
            echo "<li>";
            // Output destination name and country safely
            echo htmlspecialchars($row['name']) . " (" . htmlspecialchars($row['country']) . ")";
            // Form with hidden destination_id to pass to book.html
            echo " <form action='book.html' method='GET' style='display:inline;'>";
            echo "<input type='hidden' name='destination_id' value='" . $row['destination_id'] . "'>";
            echo "<button type='submit'>Book</button>";
            echo "</form>";

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