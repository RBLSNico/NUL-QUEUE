
<?php
include '../database.php';
    

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selectedOffice']) && isset($_POST['selectedQueueNumber'])) {
    $selectedOffice = $_POST['selectedOffice'];
    $selectedQueueNumber = $_POST['selectedQueueNumber'];

    // Escape the selected office to prevent SQL injection (use prepared statements for better security)
    $escapedSelectedOffice = $conn->real_escape_string($selectedOffice);
    $escapedSelectedQueueNumber = $conn->real_escape_string($selectedQueueNumber);

    // Update the 'accounting' table with the selected office
    $sqlUpdateEndorsedTo = "UPDATE accounting SET endorsed_to = '$escapedSelectedOffice' WHERE queue_number = '$escapedSelectedQueueNumber'";

    if ($conn->query($sqlUpdateEndorsedTo) !== TRUE) {
        echo "Error updating endorsed_to column: " . $conn->error;
    }

    // Send a JSON response (adjust based on your requirements)
    $response = ['success' => true, 'message' => 'Selected office updated successfully'];
    header('Content-Type: application/json');
    echo json_encode($response);
}
?>
