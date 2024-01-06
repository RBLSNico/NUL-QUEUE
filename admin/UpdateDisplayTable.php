<?php

include '../database.php';

// Process the POST data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if the required parameters are set
    if (isset($_POST['queue_number'], $_POST['user_window'])) {
        $selectedQueueNumber = $_POST['queue_number'];
        $userWindow = $_POST['user_window'];

        // Update the display table with the fetched window value
        $sqlUpdateDisplayTable = "UPDATE display SET window = '$userWindow' WHERE queue_number = '$selectedQueueNumber'";

        if ($conn->query($sqlUpdateDisplayTable) === TRUE) {
            echo "Display table updated successfully";
        } else {
            echo "Error updating display table: " . $conn->error;
        }
    } else {
        echo "Invalid request: queue_number and user_window are required parameters";
    }
} else {
    echo "Invalid request";
}

// Close the database connection
$conn->close();

?>
