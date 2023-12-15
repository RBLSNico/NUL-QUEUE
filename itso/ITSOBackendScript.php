<?php
include '../database.php';
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $queueNumber = $_POST['queue_number'];
    $timestamp = $_POST['timestamp'];
    $studentID = $_POST['student_id'];
    $endorsedFrom = $_POST['endorsed_from'];

    // Check if the key 'endorsed_to' exists in the $_POST array
    $endorsedTo = isset($_POST['endorsed_to']) ? $_POST['endorsed_to'] : 'None';
    
    $transaction = $_POST['transaction'];
    $remarks = isset($_POST['remarks']) ? $_POST['remarks'] : '';

  
    // Insert data into itso_logs table
    $sqlInsertItsoLogs = "INSERT INTO itso_logs (queue_number, timestamp, student_id, endorsed_from, transaction, remarks, endorsed_to) 
            VALUES ('$queueNumber', '$timestamp', '$studentID', '$endorsedFrom', 'Payment', '$remarks', 'Completed')";

    if ($conn->query($sqlInsertItsoLogs) === TRUE) {
        // Insert data into queue_logs table after successful insertion into itso_logs
        $sqlInsertQueueLogs = "INSERT INTO queue_logs (queue_number, timestamp, student_id, office, remarks, endorsed) 
                               VALUES ('$queueNumber', '$timestamp', '$studentID', 'Itso', '$remarks', 'Completed')";

        if ($conn->query($sqlInsertQueueLogs) !== TRUE) {
            echo "Error inserting data into queue_logs: " . $conn->error;
        }

        // Update status in the itso table
        $sqlUpdateStatus = "UPDATE itso SET status = 1 WHERE queue_number = '$queueNumber'";
        if ($conn->query($sqlUpdateStatus) !== TRUE) {
            echo "Error updating status: " . $conn->error;
        } else {
            // Additional query to update the display table based on the queue_number and officeName condition
            $sqlUpdateDisplay = "UPDATE display SET status = 1 WHERE queue_number = '$queueNumber' AND officeName = 'Itso'";
            if ($conn->query($sqlUpdateDisplay) !== TRUE) {
                echo "Error updating display table: " . $conn->error;
            } else {
                // Query to delete the record from the itso table
                $sqlDeleteItso = "DELETE FROM itso WHERE queue_number = '$queueNumber'";
                if ($conn->query($sqlDeleteItso) !== TRUE) {
                    echo "Error deleting record from itso table: " . $conn->error;
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
        echo "Error inserting data into itso_logs: " . $conn->error;
    }

    // Close connection
    $conn->close();
} else {
    echo "Invalid request method";
}
?>
