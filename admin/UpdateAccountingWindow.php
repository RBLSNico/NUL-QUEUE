<?php
include '../database.php';
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

// Process the POST data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $selectedQueueNumber = $_POST['queue_number'];
    $userWindow = $_POST['user_window'];

    // Update the accounting table with the fetched window value
    $sqlUpdateAccounting = "UPDATE $office  SET window = '$userWindow' WHERE queue_number = '$selectedQueueNumber'";

    if ($conn->query($sqlUpdateAccounting) === TRUE) {
        echo "Record updated successfully";

        // Update the display table with the selected queue number and user window
        $sqlUpdateDisplayTable = "INSERT INTO display (queue_number, window, officeName)
                                  VALUES ('$selectedQueueNumber', '$userWindow', '$office ')
                                  ON DUPLICATE KEY UPDATE window = '$userWindow'";

        // Update the status column of the selected queue number in the queue table back to 0
        $sqlUpdateQueueStatus = "UPDATE queue SET status = 1 WHERE queue_number = '$selectedQueueNumber'";
        if ($conn->query($sqlUpdateQueueStatus) !== TRUE) {
        echo "Error updating status in queue table: " . $conn->error;
        }


        if ($conn->query($sqlUpdateDisplayTable) === TRUE) {
            echo "Display table updated successfully";

            // Console log for success
            error_log("[" . date("Y-m-d H:i:s") . "] Display table updated successfully for queue number $selectedQueueNumber. Window: $userWindow");
        } else {
            echo "Error updating display table: " . $conn->error;

            // Console log for error
            error_log("[" . date("Y-m-d H:i:s") . "] Error updating display table for queue number $selectedQueueNumber: " . $conn->error);
        }
    } else {
        echo "Error updating record: " . $conn->error;

        // Console log for error
        error_log("[" . date("Y-m-d H:i:s") . "] Error updating record for queue number $selectedQueueNumber: " . $conn->error);
    }
} else {
    echo "Invalid request";
}

// Close the database connection
$conn->close();

?>
