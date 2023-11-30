<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $queueNumber = $_POST['queue_number'];
    $timestamp = $_POST['timestamp'];
    $studentID = $_POST['student_id'];
    $endorsedTo = $_POST['endorsed_to'];
    $transaction = $_POST['transaction'];
    $remarks = $_POST['remarks'];

    // Database credentials
    $servername = "localhost";
    $username = "root";
    $password = "";
    $database = "queuing_system";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $database);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Insert data into assets_logs table
    $sqlInsertClinic = "INSERT INTO clinic_logs (queue_number, timestamp, student_id, endorsed_to, transaction, remarks) 
            VALUES ('$queueNumber', '$timestamp', '$studentID', '$endorsedFrom', '$transaction', '$remarks')";
            if (!$conn->query($sqlInsertClinic)) {
                echo "Error inserting into queue_logs: " . $conn->error;
            }

    // Insert data into queue_logs table
    $sqlInsert = "INSERT INTO queue_logs (queue_number, timestamp, student_id, office, program, remarks, endorsed) 
            VALUES ('$queueNumber', '$timestamp', '$studentID', 'CLINIC', '$program', '$remarks', 'COMPLETED')";
            if (!$conn->query($sqlInsert)) {
            echo "Error inserting into queue_logs: " . $conn->error;
        }

    if ($conn->query($sqlInsertClinic) === TRUE) {
        // Update status in the accounting table
        $sqlUpdateStatus = "UPDATE clinic SET status = 1 WHERE queue_number = '$queueNumber'";
        if ($conn->query($sqlUpdateStatus) !== TRUE) {
            echo "Error updating status: " . $conn->error;
        } else {

            // Additional query to update the display table based on the queue_number and officeName condition
            $sqlUpdateDisplay = "UPDATE display SET status = 1 WHERE queue_number = '$queueNumber' AND officeName = 'Clinic'";
            if ($conn->query($sqlUpdateDisplay) !== TRUE) {
                echo "Error updating display table: " . $conn->error;
            } else {
                
            }

        
        }
    } else {
        echo "Error inserting data: " . $conn->error;
    }

    // Close connection
    $conn->close();
} else {
    echo "Invalid request method";
}
?>
