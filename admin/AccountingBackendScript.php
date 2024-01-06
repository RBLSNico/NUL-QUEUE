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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $queueNumber = $_POST['queue_number'];
    $timestamp = $_POST['timestamp'];
    $studentID = $_POST['student_id'];
    $endorsedFrom = $_POST['endorsed_from'];

    // Check if the key 'endorsed_to' exists in the $_POST array
    $endorsedTo = isset($_POST['endorsed_to']) ? $_POST['endorsed_to'] : 'None';

    $transaction = $_POST['transaction'];
    $remarks = isset($_POST['remarks']) ? $_POST['remarks'] : '';


    // Insert data into accounting_logs table
    $sqlInsertAccountingLogs = "INSERT INTO $office_logs (queue_number, timestamp, student_id, endorsed_from, transaction, remarks, endorsed_to) 
            VALUES ('$queueNumber', '$timestamp', '$studentID', '$endorsedFrom', 'Payment', '$remarks', 'Completed')";

    if ($conn->query($sqlInsertAccountingLogs) === TRUE) {
        // Insert data into queue_logs table after successful insertion into accounting_logs
        $sqlInsertQueueLogs = "INSERT INTO queue_logs (queue_number, student_id, office, remarks, endorsed) 
                               VALUES ('$queueNumber', '$studentID', '$office', '$remarks', 'Completed')";

        if ($conn->query($sqlInsertQueueLogs) !== TRUE) {
            echo "Error inserting data into queue_logs: " . $conn->error;
        }

        // Update status in the accounting table
        $sqlUpdateStatus = "UPDATE $office SET status = 1 WHERE queue_number = '$queueNumber'";
        if ($conn->query($sqlUpdateStatus) !== TRUE) {
            echo "Error updating status: " . $conn->error;
        } else {
            // Additional query to update the display table based on the queue_number and officeName condition
            $sqlUpdateDisplay = "UPDATE display SET status = 1 WHERE queue_number = '$queueNumber' AND officeName = '$office'";
            if ($conn->query($sqlUpdateDisplay) !== TRUE) {
                echo "Error updating display table: " . $conn->error;
            } else {
                // Query to delete the record from the accounting table
                $sqlDeleteAccounting = "DELETE FROM $office WHERE queue_number = '$queueNumber'";
                if ($conn->query($sqlDeleteAccounting) !== TRUE) {
                    echo "Error deleting record from $office table: " . $conn->error;
                } else {
                    // Query to update the studentstatus column in the queue table
                    $sqlUpdateQueue = "UPDATE queue SET studentstatus = 1 WHERE queue_number = '$queueNumber'";
                    if ($conn->query($sqlUpdateQueue) !== TRUE) {
                        echo "Error updating studentstatus in the queue table: " . $conn->error;
                    }
                }
            }
        }
    } else {
        echo "Error inserting data into $office_logs: " . $conn->error;
    }

    // Close connection
    $conn->close();
} else {
    echo "Invalid request method";
}
?>