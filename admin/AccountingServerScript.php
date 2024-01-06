
<?php
include '../database.php';

// Check if the 'concernSql' parameter is set in the POST request
if (isset($_POST['concernSql'])) {
    $concernSql = $_POST['concernSql'];

    // Execute the SQL query
    $result = $conn->query($concernSql);

    if ($result) {
        $concerns = array();

        // Fetch data and store it in an array
        while ($row = $result->fetch_assoc()) {
            $concerns[] = $row['full_name'];
        }

        // Return the array as JSON
        echo json_encode($concerns);
    } else {
        // Handle the case when the query fails
        echo json_encode(array('error' => 'Query failed'));
    }
} else {
    // Handle the case when the 'concernSql' parameter is not set
    echo json_encode(array('error' => 'Missing parameter'));
}

// Close the database connection
$conn->close();

?>