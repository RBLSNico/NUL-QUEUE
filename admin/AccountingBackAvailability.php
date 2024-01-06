<?php
session_start();

$office = $_SESSION['office'];
$office_logs = $office . "_logs";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: AccountingLogin.php');
    exit();
}

// Fetch the full name from the database based on the user_id (adjust this query based on your database structure)
$userID = $_SESSION['username'];
$sqlFullName = "SELECT full_name FROM user_accounts WHERE username = '$userID'";
$resultFullName = $conn->query($sqlFullName);


if ($resultFullName->num_rows > 0) {
    $rowFullName = $resultFullName->fetch_assoc();
    $_SESSION['full_name'] = $rowFullName['full_name'];
} else {
    // Handle the case where no full name was found
    $_SESSION['full_name'] = 'Unknown User';
}

$sqlOfficeName = "SELECT office FROM user_accounts WHERE username = '$userID'";
$resultOfficeName = $conn->query($sqlOfficeName);


if ($resultOfficeName->num_rows > 0) {
    $rowOfficeName = $resultOfficeName->fetch_assoc();
    $_SESSION['office'] = $rowOfficeName['office'];
} else {
    // Handle the case where no full name was found
    $_SESSION['office'] = 'Unknown User';
}

include '../database.php';

// Check if the connection was successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['queue_number'])) {
    $selectedQueueNumber = $_POST['queue_number'];

    // Update availability and window to 0 in the database
    $sqlUpdateData = "UPDATE $office SET availability = 0, window = 0 WHERE queue_number = '$selectedQueueNumber'";
    
    if ($conn->query($sqlUpdateData) !== TRUE) {
        $response = "Error updating data: " . $conn->error;
    } else {
        $response = "Data updated successfully";
    }

    echo $response;
} else {
    echo "Queue number not provided";
}
?>