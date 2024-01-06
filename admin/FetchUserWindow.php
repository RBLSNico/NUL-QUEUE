<?php
// Fetch the currently logged-in username using your authentication mechanism
// For example, if you are using sessions:
include '../database.php';
session_start();
$currentUsername = $_SESSION['username']; // Replace with your actual session variable



// Perform the database query
$sqlFetchWindow = "SELECT window FROM user_accounts WHERE username = '$currentUsername'";
$resultWindow = $conn->query($sqlFetchWindow);

// Check for errors and send the result back to the client
if ($resultWindow) {
    if ($resultWindow->num_rows > 0) {
        $rowWindow = $resultWindow->fetch_assoc();
        $userWindow = isset($rowWindow['window']) ? $rowWindow['window'] : null;

        // Output the fetched window value
        echo $userWindow;
    } else {
        echo "No rows found for the current user.";
    }
} else {
    echo "Error fetching window: " . $conn->error;
}

// Close the connection
$conn->close();
?>