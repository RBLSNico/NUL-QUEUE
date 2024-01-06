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





if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['office'])) {
    $selectedQueueNumber = $_POST['queue_number'];
    $selectedTimestamp = $_POST['timestamp'];
    $selectedStudentID = $_POST['student_id'];
    $selectedOffice = $_POST['office'];
    $selectedRemarks = $_POST['remarks'];
    $selectedTransaction = $_POST['transaction'];

    // Capitalize the first letter of the selectedOffice
    $formattedSelectedOffice = ucfirst($selectedOffice);

    // Determine the table name based on the selected office
    $tableName = strtolower($formattedSelectedOffice); // Assuming the table names are lowercase

    // Initialize SQL statements
    $sqlInsert = "";
    $selectedProgram = "";


    if ($formattedSelectedOffice === 'Academics') {
        // If the selected office is 'Academics', also save data in the 'academics' table
        $selectedProgram = $_POST['program'];
        $selectedConcern = $_POST['concern'];
        $selectedTransaction = isset($_POST['transaction']) ? $_POST['transaction'] : '';

        // Fetch the course value from the program_chairs table based on the selected concern
        $courseSql = "SELECT course FROM program_chairs WHERE full_name = '$selectedConcern'";
        $courseResult = $conn->query($courseSql);

        if ($courseResult->num_rows > 0) {
            $courseRow = $courseResult->fetch_assoc();
            $selectedCourse = $courseRow['course'];

            // Include the selected course in your SQL query
            $sqlInsert = "INSERT INTO academics_queue (queue_number, timestamp, student_id, program, concern, course, remarks, endorsed_from, transaction) 
            VALUES ('$selectedQueueNumber', '$selectedTimestamp', '$selectedStudentID', '$selectedProgram', '$selectedConcern', '$selectedCourse', '$selectedRemarks', '$office', '$selectedTransaction')";
            if ($conn->query($sqlInsert) !== TRUE) {
                echo "Error inserting data into academics table: " . $conn->error;
            }
        } else {
            echo "Error fetching course value for selected concern";
        }
    } elseif ($formattedSelectedOffice === 'Admission') {
        // If the selected office is 'Admission', get the selected program from the dropdown


        // Add your query to insert data into the 'admission' table
        $sqlInsert = "INSERT INTO admission (queue_number, timestamp, student_id,  remarks, endorsed_from, transaction) 
        VALUES ('$selectedQueueNumber', '$selectedTimestamp', '$selectedStudentID', '$selectedRemarks', '$office', '$selectedTransaction')";

        if ($conn->query($sqlInsert) !== TRUE) {
            echo "Error inserting data into admission table: " . $conn->error;
        }
    } else {
        // If the selected office is not 'Academics' or 'Admission', save data in the respective table
        $sqlInsert = "INSERT INTO $tableName (queue_number, timestamp, student_id, remarks, endorsed_from, transaction) VALUES ('$selectedQueueNumber', '$selectedTimestamp', '$selectedStudentID', '$selectedRemarks', '$office', '$selectedTransaction')";


        if ($conn->query($sqlInsert) !== TRUE) {
            echo "Error inserting data into $tableName table: " . $conn->error;
        }
    }

    // Fetch endorsed_from value from the Accounting table
    $sqlFetchEndorsedFrom = "SELECT endorsed_from FROM $office WHERE queue_number = '$selectedQueueNumber'";
    $resultFetchEndorsedFrom = $conn->query($sqlFetchEndorsedFrom);

    if ($resultFetchEndorsedFrom->num_rows > 0) {
        $rowFetchEndorsedFrom = $resultFetchEndorsedFrom->fetch_assoc();
        $selectedEndorsedFrom = $rowFetchEndorsedFrom['endorsed_from'];

        // Insert data into accounting_logs table
        $sqlInsertIntoAccountingLogs = "INSERT INTO $office_logs (queue_number, timestamp, student_id, remarks, endorsed_from, transaction)
     VALUES ('$selectedQueueNumber', '$selectedTimestamp', '$selectedStudentID', '$selectedRemarks', '$selectedEndorsedFrom', '$selectedTransaction')";

        // Execute the insert query for accounting_logs table
        if ($conn->query($sqlInsertIntoAccountingLogs) === TRUE) {
            // If insert is successful, update the 'endorsed_to' column in the 'accounting_logs' table
            $sqlUpdateEndorsedToLogs = "UPDATE $office_logs SET endorsed_to = '$formattedSelectedOffice' WHERE queue_number = '$selectedQueueNumber'";

            // Execute the update query for the 'accounting_logs' table
            if ($conn->query($sqlUpdateEndorsedToLogs) !== TRUE) {
                echo "Error updating endorsed_to column in $office_logs: " . $conn->error;
            }

            // Update status column in the 'accounting' table and display table
            $sqlUpdateStatusAndDisplay = "UPDATE $office, display
         SET $office.status = 1,
             display.status = 1
         WHERE $office.queue_number = '$selectedQueueNumber'
             AND display.queue_number = '$selectedQueueNumber'
             AND display.officeName = '$office'";

            if ($conn->query($sqlUpdateStatusAndDisplay) !== TRUE) {
                echo "Error updating status and display table: " . $conn->error;
            }

            // Delete the data from the 'accounting' table
            $sqlDeleteFromAccounting = "DELETE FROM $office WHERE queue_number = '$selectedQueueNumber'";

            // Execute the delete query for the 'accounting' table
            if ($conn->query($sqlDeleteFromAccounting) !== TRUE) {
                echo "Error deleting data from $office table: " . $conn->error;
            }

            // Insert data into queue_logs table
            $sqlInsertIntoQueueLogs = "INSERT INTO queue_logs (queue_number, student_id, office, remarks, endorsed) 
             VALUES ('$selectedQueueNumber', '$selectedStudentID', '$office', '$selectedRemarks', '$selectedOffice')";

            // Execute the insert query for queue_logs table
            if ($conn->query($sqlInsertIntoQueueLogs) !== TRUE) {
                echo "Error inserting data into queue_logs: " . $conn->error;
            }

            // Update status of the selected queue number in the queue table back to 0
            $sqlUpdateQueueStatus = "UPDATE queue SET status = 0 WHERE queue_number = '$selectedQueueNumber'";
            if ($conn->query($sqlUpdateQueueStatus) !== TRUE) {
                echo "Error updating status in queue table: " . $conn->error;
            }
        } else {
            echo "Error inserting data into $office_logs table: " . $conn->error;
        }
    } else {
        echo "Invalid request method";
    }
}

$office = $_SESSION['office'];
$office_logs = $office . "_logs";


// Add this code at the beginning of your PHP script to define $selectedQueueNumber
$selectedQueueNumber = isset($_POST['queue_number']) ? $_POST['queue_number'] : '';

// Fetch data from the "accounting" table where status is 0 and availability is 0, ordered by timestamp in descending order
$sqlAccounting = "SELECT queue_number, timestamp, student_id, transaction, endorsed_from, remarks FROM $office WHERE status = 0 AND availability = 0 ORDER BY timestamp DESC";
// echo $sqlAccounting;
$resultAccounting = $conn->query($sqlAccounting);

// Initialize an array to store the fetched accounting data
$accounting_data = [];

if ($resultAccounting->num_rows > 0) {
    while ($rowAccounting = $resultAccounting->fetch_assoc()) {
        $accounting_data[] = $rowAccounting;
    }
}




// Fetch data from the "queue" table where office is "ACCOUNTING" and status is 0, ordered by timestamp in descending order
$sqlQueue = "SELECT queue_number, student_id, timestamp, remarks, endorsed FROM queue WHERE office = 'A' AND status = 0 ORDER BY timestamp DESC";
$resultQueue = $conn->query($sqlQueue);

// Initialize an array to store the fetched queue data
$queue_data = [];

if ($resultQueue->num_rows > 0) {
    while ($rowQueue = $resultQueue->fetch_assoc()) {
        $queue_data[] = $rowQueue;
    }
}


// Combine the data into a single array, with data from "accounting" at the top
$combined_data = array_merge($accounting_data, $queue_data);

// Sort the combined data based on the queued time or date (assuming 'timestamp' is the key)
usort($combined_data, function ($a, $b) {
    return strtotime($a['timestamp']) - strtotime($b['timestamp']);
});




// Check if the form is submitted
if (isset($_POST['update_button'])) {
    // Retrieve the queue number from the form
    $selectedQueueNumber = $_POST['queue_number'];

    // Update the database (replace 'your_table_name' and 'your_column_name' with your actual table and column names)
    $sqlUpdate = "UPDATE $office SET window = 1 WHERE queue_number = '$selectedQueueNumber'";

    if ($conn->query($sqlUpdate) === TRUE) {
        echo "Database updated successfully!";
    } else {
        echo "Error updating database: " . $conn->error;
    }
}





// Fetch student_id and transaction data from the database
$sqlFetchData = "SELECT student_id FROM queue WHERE queue_number = '$selectedQueueNumber'";
$resultFetchData = $conn->query($sqlFetchData);

if ($resultFetchData->num_rows > 0) {
    $row = $resultFetchData->fetch_assoc();
    $selectedStudentID = $row['student_id'];
    //$selectedTransaction = $row['transaction'];

}



// Fetch student_id, transaction, and endorsed from the database
$sqlFetchData = "SELECT student_id FROM queue WHERE queue_number = '$selectedQueueNumber'";
$resultFetchData = $conn->query($sqlFetchData);

if ($resultFetchData->num_rows > 0) {
    $row = $resultFetchData->fetch_assoc();
    $selectedStudentID = $row['student_id'];
    //$selectedTransaction = $row['transaction'];
    //$selectedEndorsed = $row['endorsed_from'];

} else {

    // Handle the case where no data was found
    // You can display a message or take appropriate action here
}

// Inside the PHP script, check if the key "action" exists in the $_POST array before using it

if (isset($_POST['action']) && $_POST['action'] === 'post_combined_data') {
    // Assuming $combined_data is the array containing the data
    $encoded_data = json_encode($combined_data);
    echo $encoded_data;
    exit(); // Make sure to exit the script after sending the response
}




// Close the database connection

?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NU Accounting Office</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm"
        crossorigin="anonymous"></script>
    <link rel="stylesheet" type="text/css" href="styles.css">
    <link rel="stylesheet" href="buttons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    


</head>
<header>
    <div class="header">
        <img src="assets/nu logo.webp" alt="Image" class="" style="max-height: auto; max-width: 10%; padding: 1%;">
        <div class="mt-4">
            <h1 class="fw-bolder text-light text-center">NU LAGUNA</h1>
            <h4 class="fw-bold text-light text-center">QUEUING SYSTEM</h4>
        </div>

        <!-- Display the full name in the header -->
        <p id="greeting" class="fw-bold text-light text-center" style="margin-top: 3%;">Hi there,
            <?php echo $_SESSION['full_name']; ?>!
        </p>

        <button class="icon" onclick="exit()">
            <p style="font-size:20px; margin-top: 60%;">Logout</p>
        </button>

        <button class="round" id="newqueue" style="position: absolute; right: 0; margin-top:11%; margin-right:1%"
            onclick="openQue()">New Queue <i class="fa fa-plus-square"></i></button>

        <!-- Include jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

        <!-- Your button -->
        <button class="round" style="position: absolute; left: 80; margin-top:11%; margin-left:20%"
            onclick="returnToQueue()"><i class="fa fa-chevron-left"></i> Back to Queue</button>


    </div>
</header>


<style>
    .center-content {
        text-align: left;
        margin: 0 auto;
        max-width: 40%;
        /* Adjust the width as needed */
        padding: 3%;
        margin-left: 35%;
        margin-top: 3%;
    }

    .remarkac {
        font-weight: bold;
        color: #333;
        border: 1px solid white;
        border-radius: 5px;
        padding-bottom: 60px;
        background-color: lightgrey;

    }

    #greeting {
        font-weight: bold;
        color: #ffffff;
        /* Text color (white in this example) */
        text-align: center;
        margin-top: 30px;
        /* Adjust the top margin as needed */
        font-size: 18px;
        /* Adjust the font size as needed */
        margin-left: 65%;
    }

    .txtque {
        font-size: 65px;
        font-weight: bold;
    }

    #formContainer {
        text-align: center;
        margin: 0 auto;
        width: 50%;
        padding: 20px;
        border: 1px solid #ccc;
        color: black;
    }

    /* Custom styles for the side navigation */
    .sidenav {
        height: 74vh;
        width: 340px;
        position: absolute;
        margin-top: 3.4%;
        left: 0;
        background-color: #EDEDED;
        overflow-y: auto;

    }

    .sidetxt {
        font-weight: bold;
        color: black;
        font-size: 34.7px;
        font-family: sans-serif;
    }

    .sidenav button {
        width: 100%;
        text-align: left;
        padding: 8px;
        background-color: transparent;
        cursor: pointer;
        border-bottom: solid 1px grey;
        border-top: 0px;
        border-left: 0px;
        border-right: 0px;
        border-radius: 0px;
    }

    .sidenav button:hover {
        background-color: #d1d1d1;
    }

    /* Adjust the main content area to give space for the side navigation */
    .main-content {
        margin-left: 220px;
        /* Match the width of the sidebar */
        padding: 15px;
    }

    .drop {
        padding-right: 15%;
        padding-left: 20px;
        font-size: 30px;
        border: solid 1px grey;
        border-radius: 5px;
        position: relative;
        display: inline-block;
    }

    .drop-item {
        background-color: #ced4da;
    }

    .drop-item:focus {
        background-color: red;
    }

    /* Center the Popup */
    #endorsementPopup {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 1;
        width: 600px;
        height: 650px;
        background-color: #fefefe;
        padding: 20px;
        border: 1px solid #888;
        border-radius: 10px;
        font-size: 20px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        text-align: left;
    }

    /* Style the Popup Content */
    #endorsementPopup p {
        margin-bottom: 10px;
        margin-left: 35px;
    }

    /* Style the Dropdowns */
    .drop {
        width: 65%;
        padding: 8px;
        font-size: 20px;
        margin: 4px 0;
        box-sizing: border-box;
    }

    .transaction-textbox {
        width: 65%;
        padding: 4px;
        font-size: 20px;
        margin: 4px 0;
        box-sizing: border-box;
    }

    /* Style the Textarea */
    .remarkac {
        width: 100%;
        padding: 8px;
        box-sizing: border-box;

    }


    /* Apply Responsive Styling */
    @media screen and (max-width: 600px) {

        /* Adjust styles for smaller screens if needed */
        #endorsementPopup {
            width: 80%;
        }
    }

    /* Center the Popup */
    #doneendorsementPopup {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 1;
        width: 600px;
        height: 500px;
        background-color: #fefefe;
        padding: 20px;
        border: 1px solid #888;
        border-radius: 10px;
        font-size: 20px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        text-align: left;
    }

    /* Style the Popup Content */
    #doneendorsementPopup p {
        margin-bottom: 10px;
        margin-left: 35px;
    }

    /* Style the Dropdowns */
    .drop {
        width: 65%;
        padding: 8px;
        font-size: 20px;
        margin: 4px 0;
        box-sizing: border-box;
    }

    .transaction-textbox {
        width: 65%;
        padding: 4px;
        font-size: 20px;
        margin: 4px 0;
        box-sizing: border-box;
    }

    /* Style the Textarea */
    .remarkac {
        width: 100%;
        padding: 8px;
        box-sizing: border-box;

    }


    /* Apply Responsive Styling */
    @media screen and (max-width: 600px) {

        /* Adjust styles for smaller screens if needed */
        #endorsementPopup {
            width: 80%;
        }
    }



    .popup {
        z-index: 2;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0.1);
        background-color: white;
        padding: 2%;
        width: 30%;
        height: 75%;
        border-radius: 35px;
        display: none;
        text-align: left;
        padding-left: 5%;
        font-size: 30px;
    }

    .open-popup {
        opacity: 1;
        /* Show the popup */
        top: 50%;
        transform: translate(-50%, -50%) scale(1);
    }

    .background-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.7);
        /* Semi-transparent black background */
        backdrop-filter: blur(4px);
        /* Apply a blur effect to the background */
        z-index: 1;
        /* Place it above other content */
        display: none;
        /* Initially hidden */

    }

    .nestedpopup {
        z-index: 3;
        width: 400px;
        height: 280px;
        background-color: white;
        border-radius: 30px;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(1);
        display: none;
        text-align: center;
    }

    .dept {
        color: #34418E;
        font-size: 30px;
        font-weight: bold;
        font-family: sans-serif;
        line-height: 0%;
    }

    .endorseto {
        color: #ffdb58;
        font-weight: bold;
        font-family: sans-serif;
        font-style: italic;
        font-size: 20px;
    }

    .quenum {
        color: #34418E;
        font-size: 45px;
        font-weight: bold;
        font-family: sans-serif;
        line-height: 0%;
    }

    .icon {
        font-size: 40px;
        padding: 0px;
        color: white;
        border: none;
        background-color: transparent;
        margin: 0px;
        position: absolute;
        border: none;
        border-color: transparent;
        right: 0;
        margin-right: 5%;
        margin-top: 3%;
        line-height: 30%;
    }

    .notif {
        bottom: 0;
        right: 0;
        background-color: #D5E0FF;
        border-radius: 30px;
        border: none;
        font-size: 20px;
        padding-left: 70px;
        padding-right: 20px;
        padding-top: 0%;
        color: black;
        position: absolute;
        line-height: 60%;
        transform: scale(0.1);
        display: none;
    }

    .open-notif {
        opacity: 1;
        transform: scale(1);
    }

    .trnum {
        text-align: left;
        font-size: 40px;
    }

    .bullicon {
        text-align: left;
        font-size: 50px;
        position: relative;
        top: 45px;
        right: 55px;
    }

    .close {
        background-color: transparent;
        position: absolute;
        margin-left: 55%;
        margin-top: 10%;
        border: none;
        cursor: pointer;
    }

    .side_navtxt {
        background-color: #FFD41C;
        padding: .1% 2.2% .1% 2.3%;
        text-align: center;
        font-size: 24px;
        font-family: sans-serif;
        position: absolute;
        z-index: 1;
    }

    .side_navtxt p {
        margin-top: 10px;

    }

    .sidenav::-webkit-scrollbar {
        width: 12px;
        /* width of the scrollbar */
    }

    .sidenav::-webkit-scrollbar-thumb {
        background-color: #6a6a6a;
        /* color of the thumb */
        border-radius: 5px;
        /* roundness of the thumb */
    }

    .sidenav::-webkit-scrollbar-track {
        background-color: #e0e0e0;
        /* color of the track */
    }

    #confirmationModal {
        display: none;
        position: fixed;
        z-index: 1;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.4);
    }

    #confirmationModal .modal-content {
        background-color: #fefefe;
        margin: 15% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 20%;
        /* Adjust the width as needed */
        height: 15%;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        text-align: center;
        /* Center the text */
    }

    #confirmationModal .confirmation-message {
        font-size: 20px;
        /* Adjust the font size */
        margin-bottom: 15px;
    }

    /* Style for the textarea with the remarkac class */
    textarea.remarkac {
        width: 90%;
        padding: 8px;
        margin-top: 8px;
        margin-bottom: 16px;
        border: 1px solid #ccc;
        border-radius: 5px;
        resize: vertical;
        /* Allow vertical resizing */
    }

    .round {
        border-radius: 5px;
        background-color: green;
        color: white;
        border: none;
        font-family: sans-serif;
        font-size: 15px;
        padding: .5%;
        font-weight: bold;

    }
</style>

<body>


    <!-- Side navigation -->
    <div class="side_navtxt">
        <p><?php echo strtoupper($office); ?> QUEUE</p>
    </div>
    <!-- Side navigation for combined data -->


    <div class="sidenav" id="sidenav">


        <!-- Display queue numbers from both "queue" and "accounting" tables where status is 0 -->
        <?php foreach ($combined_data as $itemCombined): ?>
            <form method="post" action="" id="">
                <input type="hidden" name="queue_number" value="<?php echo $itemCombined['queue_number']; ?>">
                <input type="hidden" name="timestamp" value="<?php echo $itemCombined['timestamp']; ?>">
                <input type="hidden" name="student_id" value="<?php echo $itemCombined['student_id']; ?>">
                <?php if (isset($itemCombined['remarks'])): ?>
                    <input type="hidden" name="remarks" value="<?php echo $itemCombined['remarks']; ?>">
                <?php endif; ?>
                <?php if (isset($itemCombined['transaction'])): ?>
                    <input type="hidden" name="transaction" value="<?php echo $itemCombined['transaction']; ?>">
                <?php endif; ?>
                <?php if (isset($itemCombined['endorsed'])): ?>
                    <input type="hidden" name="endorsed" value="<?php echo $itemCombined['endorsed']; ?>">
                <?php endif; ?>
                <button class="btn btn-outline-primary" type="submit" name="show_details">
                    <div class="sidetxt">
                        <?php echo $itemCombined['queue_number']; ?>
                    </div>
                </button>
            </form>
        <?php endforeach; ?>
    </div>

    <!-- Side navigation -->


    <!-- Center Content -->
    <?php if (isset($_POST['show_details'])): ?>
        <?php
        $selectedQueueNumber = $_POST['queue_number'];
        $selectedTimestamp = $_POST['timestamp'];
        $selectedStudentID = $_POST['student_id'];
        $selectedRemarks = isset($_POST['remarks']) ? $_POST['remarks'] : '';
        $selectedTransaction = isset($_POST['transaction']) ? $_POST['transaction'] : '';
        $selectedEndorsed = isset($_POST['endorsed_from']) ? $_POST['endorsed_from'] : '';

        // Retrieve the previously selected queue number from the session
        $previouslySelectedQueueNumber = isset($_SESSION['previouslySelectedQueueNumber']) ? $_SESSION['previouslySelectedQueueNumber'] : null;

        // Update the "availability" column only for the selected queue number in the "accounting" table
        $sqlUpdateAvailability = "UPDATE $office SET availability = 1 WHERE queue_number = '$selectedQueueNumber'";
        $conn->query($sqlUpdateAvailability);

        // Reset the "availability" column to 0 for the previously selected queue number in the "accounting" table
        if ($previouslySelectedQueueNumber !== null && $previouslySelectedQueueNumber !== $selectedQueueNumber) {
            $sqlResetAvailability = "UPDATE $office SET availability = 0 WHERE queue_number = '$previouslySelectedQueueNumber'";
            $conn->query($sqlResetAvailability);
        }

        // Store the currently selected queue number in the session for future reference
        $_SESSION['previouslySelectedQueueNumber'] = $selectedQueueNumber;

        // Fetch the "endorsed" data from the "accounting" table
        $sqlFetchEndorsed = "SELECT endorsed_from FROM $office WHERE queue_number = '$selectedQueueNumber'";
        $resultEndorsed = $conn->query($sqlFetchEndorsed);

        if ($resultEndorsed->num_rows > 0) {
            $rowEndorsed = $resultEndorsed->fetch_assoc();
            $selectedEndorsed = $rowEndorsed['endorsed_from'];
        }
        ?>

        <div id="center" class="center-content">
            <p class="txtque" id="selectedQueueNumber">
                <?php echo $selectedQueueNumber; ?>
            </p>
            <p style="font-style: italic; font-size: 18px; opacity: 60%;">Queued in:
                <?php echo $selectedTimestamp; ?>
            </p>
            <p><strong>Student Number:</strong>
                <?php echo $selectedStudentID; ?>
            </p>
            <p><strong>Endorsed From:</strong>
                <?php echo $selectedEndorsed; ?>
            </p>
            <p><strong>Transaction:</strong>
                <?php echo $selectedTransaction; ?>
            </p>

            <?php if (!empty($selectedRemarks)): ?>
                <p><strong>Remarks:</strong></p>
                <p class="remarkac">
                    <?php echo $selectedRemarks; ?>
                </p>
            <?php endif; ?>

            <!-- Notify button -->
            <button style="margin-left: 8%;" class="button" type="button" onclick="openConfirmationModal()"><i
                    class="fa fa-bell"></i> NOTIFY</button>

            <!-- Add this code within the PHP if statement -->
            <div id="confirmationModal" class="modal">
                <div class="modal-content">
                    <p class="confirmation-message">Are you sure you want to notify?</p>
                    <div class="confirmation-buttons">
                        <button class="button confirm-button" onclick="confirmNotify()"><i class="fa fa-check"> YES
                            </i></button>
                        <button class="button cancel-button" onclick="closeConfirmationModal()"><i class="fa fa-close"> NO
                            </i></button>
                    </div>
                </div>
            </div>

            <!-- Endorse button (disable it initially) -->
            <button style="margin-left: 2%;" class="button" type="button" id="endorseButton"
                onclick="openEndorsementPopup()" disabled>
                <i class="fa fa-paper-plane"></i> ENDORSE
            </button>

            <!-- End button (disable it initially) -->
            <button id="endButton" style="margin-left: 2%;" class="button" type="button"
                onclick="openDoneEndorsementPopup()" disabled>
                <i class="fa fa-times-circle"></i> END (5s)
            </button>
        </div>

        <!-- JavaScript to update the button text and enable the button after a delay -->
        <script>
            function openConfirmationModal() {
                var modal = document.getElementById('confirmationModal');
                modal.style.display = 'block';
            }

            function closeConfirmationModal() {
                var modal = document.getElementById('confirmationModal');
                modal.style.display = 'none';
            }

            function confirmNotify() {
                // Add logic for confirming notification here

                // Close the confirmation modal
                closeConfirmationModal();

                // Enable the Endorse button
                document.getElementById('endorseButton').disabled = false;

                // Start the countdown for the End button
                updateButtonCountdown();

                // Perform the Notify action (replace this with your existing logic)
                fetchUserWindowAndNotify();
            }

            var countdownSeconds = 5; // Set the initial countdown value
            var endButton = document.getElementById('endButton');

            function updateButtonCountdown() {
                endButton.innerHTML = '<i class="fa fa-times-circle"></i> END (' + countdownSeconds + 's)';
                countdownSeconds--;

                if (countdownSeconds >= 0) {
                    setTimeout(updateButtonCountdown, 1000); // Update every 1 second (1000 milliseconds)
                } else {
                    endButton.disabled = false;
                    endButton.innerHTML = '<i class="fa fa-times-circle"></i> END'; // Reset button text
                }
            }

            function fetchUserWindowAndNotify() {
                var xhr = new XMLHttpRequest();
                xhr.open("POST", "FetchUserWindow.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function () {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        var userWindow = xhr.responseText;
                        console.log("Fetched Window:", userWindow);

                        // Now, you can use the fetched window value to update the accounting table
                        updateAccountingTable(userWindow);
                    }
                };

                // No need to send data as the server should fetch based on the currently logged-in user
                xhr.send();
            }

            function updateAccountingTable(userWindow) {
                var xhr = new XMLHttpRequest();
                xhr.open("POST", "UpdateAccountingWindow.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function () {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        console.log(xhr.responseText);
                    }
                };

                var data =
                    "queue_number=" + encodeURIComponent('<?php echo $selectedQueueNumber; ?>') +
                    "&user_window=" + encodeURIComponent(userWindow);

                xhr.send(data);
            }
        </script>
    <?php endif; ?>



    <!-- Done Popup HTML -->
    <?php if (isset($_POST['show_details'])): ?>
        <div id="backgroundOverlay" class="background-overlay"></div>
        <div class="popup" id="doneendorsementPopup">
            <p style="text-align: center; font-size: 35px; margin-right: 70px; font-weight: 700; color: #34418E;"> END
                TRANSACTION </p>
            <p style="margin-top:20px;"><strong>Student ID:</strong>
                <?php echo $selectedStudentID; ?>
            </p>
            <p><strong>Transaction:</strong>
                <?php echo $selectedTransaction; ?>
            </p>

            <!-- Office selection and remarks form -->
            <form method="post" action="" id="endorsementForm">
                <input type="hidden" name="queue_number" value="<?php echo $selectedQueueNumber; ?>">
                <input type="hidden" name="timestamp" value="<?php echo $selectedTimestamp; ?>">
                <input type="hidden" name="student_id" value="<?php echo $selectedStudentID; ?>">
                <input type="hidden" name="transaction" value="<?php echo $selectedTransaction; ?>">
                <input type="hidden" name="course" id="course" value="" required>

                <p><strong>Remarks:</strong><br>
                    <textarea class="remarkac" rows="2" cols="27" id="remarks" name="remarks" required></textarea><br><br>

                    <!-- Apply the same button class as the "Cancel" button, set a fixed width, and add some margin -->
                    <button class="button" type="button"
                        style="width: 115px; margin-right: 15px; margin-left:20%; margin-top: -50px;"
                        onclick="closeDoneEndorsementPopup()">CANCEL</button>

                    <!-- Apply the same button class, set a fixed width, and style as the "Cancel" button -->
                    <button class="button" style="width: 115px; margin-left: 10px; margin-top: -50px;" id="doneButton"
                        onclick="markAsDone()">DONE</button>
            </form>
        </div>

        <script>
            function markAsDone() {
                var endButton = document.getElementById('endButton');
                // Disable the "End" button to prevent intentional clicks
                endButton.disabled = true;

                var endorsedFrom = '<?php echo $selectedEndorsed; ?>';
                var transaction = document.getElementById('transaction').value; // Get the transaction value
                var remarks = document.getElementById('remarks').value; // Get the user input from the remarks textarea

                var xhr = new XMLHttpRequest();
                xhr.open("POST", "AccountingBackendScript.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function () {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        console.log(xhr.responseText);
                        document.getElementById('center').innerHTML = xhr.responseText;
                    }
                };

                var data =
                    "queue_number=" + encodeURIComponent('<?php echo $selectedQueueNumber; ?>') +
                    "&timestamp=" + encodeURIComponent('<?php echo $selectedTimestamp; ?>') +
                    "&student_id=" + encodeURIComponent('<?php echo $selectedStudentID; ?>') +
                    "&endorsed_from=" + encodeURIComponent(endorsedFrom) +
                    "&transaction=" + encodeURIComponent(transaction) +
                    "&remarks=" + encodeURIComponent(remarks); // Use the user input from the remarks textarea

                xhr.send(data);
            }

            function openDoneEndorsementPopup() {
                var doneendorsementPopup = document.getElementById('doneendorsementPopup');
                var backgroundOverlay = document.getElementById('backgroundOverlay');

                if (doneendorsementPopup && backgroundOverlay) {
                    doneendorsementPopup.style.display = "block"; // Show the popup form
                    doneendorsementPopup.classList.add("open-popup");
                    backgroundOverlay.style.display = "block"; // Show the background overlay
                }
            }

            // JavaScript to close the endorsement popup
            function closeDoneEndorsementPopup() {
                var doneendorsementPopup = document.getElementById('doneendorsementPopup');
                var backgroundOverlay = document.getElementById('backgroundOverlay');

                if (doneendorsementPopup && backgroundOverlay) {
                    doneendorsementPopup.style.display = "none"; // Hide the popup form
                    doneendorsementPopup.classList.remove("open-popup");
                    backgroundOverlay.style.display = "none"; // Hide the background overlay
                }
            }

            // JavaScript to open the endorsement popup
            function openEndorsementPopup() {
                var endorsementPopup = document.getElementById('endorsementPopup');
                var backgroundOverlay = document.getElementById('backgroundOverlay');

                if (endorsementPopup && backgroundOverlay) {
                    endorsementPopup.style.display = "block"; // Show the popup form
                    endorsementPopup.classList.add("open-popup");
                    backgroundOverlay.style.display = "block"; // Show the background overlay
                }
            }


            // JavaScript to close the endorsement popup
            function closeEndorsementPopup() {
                var endorsementPopup = document.getElementById('endorsementPopup');
                var backgroundOverlay = document.getElementById('backgroundOverlay');

                if (endorsementPopup && backgroundOverlay) {
                    endorsementPopup.style.display = "none"; // Hide the popup form
                    endorsementPopup.classList.remove("open-popup");
                    backgroundOverlay.style.display = "none"; // Hide the background overlay
                }
            }

        </script>

    <?php endif; ?>




    <!-- Popup HTML -->
    <?php if (isset($_POST['show_details'])): ?>
        <div id="backgroundOverlay" class="background-overlay"></div>
        <div class="popup" id="endorsementPopup">
            <p style="text-align: center; font-size: 35px; margin-right: 70px; font-weight: 700; color: #34418E;">
                ENDORSEMENT FORM </p>

            <p style="margin-top:20px; text-align:left;"><strong>Student ID:</strong>
                <?php echo $selectedStudentID; ?>
            </p>
            <p><strong>Transaction:</strong>
                <?php echo $selectedTransaction; ?>
            </p>

            <!-- Office selection and remarks form -->
            <form method="post" action="" id="endorsementForm">
                <input type="hidden" name="queue_number" value="<?php echo $selectedQueueNumber; ?>">
                <input type="hidden" name="timestamp" value="<?php echo $selectedTimestamp; ?>">
                <input type="hidden" name="student_id" value="<?php echo $selectedStudentID; ?>">
                <input type="hidden" name="transaction" value="<?php echo $selectedTransaction; ?>">
                <input type="hidden" name="course" id="course" value="" required>

                <p><strong>Endorse To:</strong>
                    <select id="office" name="office" class="drop" onchange="handleOfficeChange()" required>
                        <option value="" disabled selected>Choose an Office</option>
                        <?php
                        // Fetch options from the database
                        $sql = "SELECT officeName FROM offices";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='" . strtolower($row['officeName']) . "'>" . $row['officeName'] . "</option>";
                            }
                        }
                        ?>
                    </select>
                </p>

                <!-- Additional textbox for transaction -->
                <p><strong>Transaction:</strong>
                    <input type="text" name="transaction" id="transaction" class="transaction-textbox" required>
                </p>

                <!-- Additional dropdown options for Academics -->
                <div id="academicsDropdown" style="display: none;">
                    <p><strong>Program:</strong>
                        <select name="program" class="drop" id="program" onchange="updateConcernDropdown()">
                            <option value="" disabled selected>Choose a program</option>
                            <?php
                            // Fetch program options from the colleges table under acronym column
                            $programSql = "SELECT acronym FROM colleges";
                            $programResult = $conn->query($programSql);

                            if ($programResult->num_rows > 0) {
                                while ($programRow = $programResult->fetch_assoc()) {
                                    echo "<option value='" . strtoupper($programRow['acronym']) . "'>" . strtoupper($programRow['acronym']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </p>

                    <p id="concernDropdownContainer" style="display: none;"><strong>Concern:</strong>
                        <select name="concern" class="drop" id="concern">
                            <option value="" disabled selected>Choose a concern</option>
                            <?php
                            // Fetch concern options from the program_chairs table where status is "available"
                            $concernSql = "SELECT full_name, course FROM program_chairs";
                            $concernResult = $conn->query($concernSql);

                            if ($concernResult->num_rows > 0) {
                                while ($concernRow = $concernResult->fetch_assoc()) {
                                    // Convert to uppercase using strtoupper
                                    $fullName = strtoupper($concernRow['full_name']);
                                    $course = strtoupper($concernRow['course']);
                                    echo "<option value='" . $fullName . "' data-course='" . $course . "'>" . $fullName . "</option>";
                                }
                            } else {
                                // If no available concerns, display a default option or handle it as needed
                                echo "<option value=''>No available concerns</option>";
                            }
                            ?>
                        </select>
                    </p>
                </div>

                <!-- Additional dropdown options for Admission -->
                <div id="admissionOptionsDropdownContainer" style="display: none;">

                    </p>
                </div>

                <p><strong>Remarks:</strong><br>
                    <textarea class="remarkac" rows="2" cols="27" id="remarks" name="remarks" required></textarea><br><br>

                    <!-- Apply the same button class as the "Cancel" button, set a fixed width, and add some margin -->
                    <button class="btn" type="button"
                        style="width: 115px; margin-right: 15px; margin-left:20%; margin-top: -50px; font-weight: bold; padding:2%;"
                        onclick="closeEndorsementPopup()"> CANCEL</button>

                    <!-- Apply the same button class, set a fixed width, and style as the "Cancel" button -->
                    <button class="btn"
                        style="width: 115px; margin-left: 10px; margin-top: -50px; font-weight: bold; padding:2%;"
                        id="doneButton" onclick="submitFormPopup()"> DONE</button>
            </form>
        </div>



        <!-- Nested popup -->
        <div class="backgroundOverlay" id="backgroundOverlayNested">
            <div class="nestedpopup" id="Nestedpopup">
                <p class="dept" style="margin-top:30px;">REGISTRAR</p>
                <p class="endorseto" style="margin-top:30px;">ENDORSED SUCCESSFULLY</p>
                <p class="endorseto" style="margin-bottom:40px;">TRACKING NUMBER IS:</p>
                <p class="quenum">
                    <?php echo $selectedQueueNumber; ?>
                </p>
            </div>
        </div>

        <!-- JavaScript to handle the visibility of the Concern dropdown -->
        <script>

            // Add this function to your existing JavaScript code
            function submitFormPopup() {
                // Your existing code for form submission

                // Add the following AJAX code to send a request to the server
                var xhr = new XMLHttpRequest();
                xhr.open("POST", ".php", true);
                xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

                // Additional data to send to the server, if needed
                var formData = "done_button_clicked=true";

                // Set up the callback function to handle the server's response
                xhr.onreadystatechange = function () {
                    if (xhr.readyState == 4 && xhr.status == 200) {
                        var response = JSON.parse(xhr.responseText);
                        if (response.status === 'success') {
                            // The server-side action was successful, you can handle it here
                            console.log(response.message);
                        } else {
                            // Handle errors or other cases
                            console.error(response.message);
                        }
                    }
                };
                


                xhr.send(formData);

            }

            function updateConcernDropdown() {
                var selectedProgram = document.getElementById("program").value;
                var concernDropdownContainer = document.getElementById("concernDropdownContainer");

                if (selectedProgram) {
                    // If a program is selected, display the Concern dropdown
                    concernDropdownContainer.style.display = "block";
                    updateConcernOptions(selectedProgram);
                } else {
                    // If no program is selected, hide the Concern dropdown
                    concernDropdownContainer.style.display = "none";
                }
            }

            function updateConcernOptions(selectedProgram) {
                // Fetch all concern options from the program_chairs table without filtering by status
                var concernSql = "SELECT full_name FROM program_chairs WHERE program = '" + selectedProgram + "'";
                var concernDropdown = document.getElementById("concern");

                // Clear previous options
                concernDropdown.innerHTML = '';

                // Fetch data from the server using AJAX or fetch API
                fetch('./AccountingServerScript.php', {
                    method: 'POST',
                    body: new URLSearchParams('concernSql=' + encodeURIComponent(concernSql)),
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Populate the Concern dropdown with fetched options
                        data.forEach(concern => {
                            var option = document.createElement("option");
                            option.text = concern;
                            concernDropdown.add(option);
                        });
                    })
                    .catch(error => console.error('Error fetching concern options:', error));
            }

            function handleOfficeChange() {
                var selectedOffice = document.getElementById("office").value;
                console.log("Selected Office: " + selectedOffice); // Add this line for console log
                var academicsDropdown = document.getElementById("academicsDropdown");
                var admissionOptionsDropdownContainer = document.getElementById("admissionOptionsDropdownContainer");

                // Hide both dropdowns initially
                academicsDropdown.style.display = "none";
                admissionOptionsDropdownContainer.style.display = "none";

                if (selectedOffice.toLowerCase() === 'academics') {
                    academicsDropdown.style.display = "block";
                } else if (selectedOffice.toLowerCase() === 'admission') {
                    // If the selected office is "admission," display the admission options dropdown
                    admissionOptionsDropdownContainer.style.display = "block";
                }
            }

            function logSelectedOffice() {
                var selectedOffice = document.getElementById("office").value;
                console.log("Selected Office: " + selectedOffice);
            }
        </script>


    <?php endif; ?>



    <script>
        let endorsementPopup = document.getElementById("endorsementPopup");
        let Nestedpopup = document.querySelector(".nestedpopup"); // Adjust this to target the nested popup element correctly
        let call = document.getElementById("call")


        function returnToQueue() {
            // Assuming you have the necessary data available (e.g., selectedQueueNumber)
            var selectedQueueNumber = $('#selectedQueueNumber').text(); // adjust this based on your actual HTML structure
            center.innerHTML = "";

            // Send AJAX request to update availability
            $.ajax({
                type: 'POST',
                url: 'AccountingBackAvailability.php', // adjust the URL based on your file structure
                data: { queue_number: selectedQueueNumber },
                success: function (response) {
                    // Handle the success response
                    console.log(response);
                    // You can also perform additional actions after the update
                },
                error: function (error) {
                    // Handle the error
                    console.error(error);
                }
            });
        }




        // Redirect to Que main
        document.getElementById('newqueue').addEventListener('click', function () {
            // URL for the new tab
            var newTabUrl = '../index.php';

            // Open a new tab/window with the specified URL
            window.open(newTabUrl, '_blank');
        });


        // JavaScript to open nested popup and close endorsement popup
        function openNestedpopup(event) {
            event.preventDefault();
            Nestedpopup.style.display = "block"; // Modify to target the correct nested popup element
            closeEndorsementPopup();
            backgroundOverlay.style.display = "block";
        }
        // JavaScript to close nested popup without affecting the endorsement popup
        function closeNestedpopup() {
            Nestedpopup.style.display = "none"; // Modify to target the correct nested popup element
            backgroundOverlay.style.display = "none";
        }
        //logout the user
        function exit() {
            window.location.href = "AccountingLogout.php";
        }
        function updateSideNav(data) {
            // Assuming your data is in JSON format, parse the data
            const parsedData = JSON.parse(data);

            // Assuming you have a div with the ID "sidenav" where you display the data
            const sideNav = document.getElementById("sidenav");

            // Clear the existing content in the side navigation
            sideNav.innerHTML = "";

            // Iterate through the parsed data and update the side navigation accordingly
            parsedData.forEach(item => {
                const form = document.createElement("form");
                form.method = "post";
                form.action = "";

                const inputQueueNumber = document.createElement("input");
                inputQueueNumber.type = "hidden";
                inputQueueNumber.name = "queue_number";
                inputQueueNumber.value = item.queue_number;

                const inputTimestamp = document.createElement("input");
                inputTimestamp.type = "hidden";
                inputTimestamp.name = "timestamp";
                inputTimestamp.value = item.timestamp;

                const inputStudentID = document.createElement("input");
                inputStudentID.type = "hidden";
                inputStudentID.name = "student_id";
                inputStudentID.value = item.student_id;

                const inputRemarks = document.createElement("input");
                inputRemarks.type = "hidden";
                inputRemarks.name = "remarks";
                inputRemarks.value = item.remarks;

                const inputTransaction = document.createElement("input");
                inputTransaction.type = "hidden";
                inputTransaction.name = "transaction";
                inputTransaction.value = item.transaction;

                const inputEndorsed = document.createElement("input");
                inputEndorsed.type = "hidden";
                inputEndorsed.name = "endorsed_from";
                inputEndorsed.value = item.endorsed;

                const button = document.createElement("button");
                button.className = "btn btn-outline-primary";
                button.type = "submit";
                button.name = "show_details";

                const div = document.createElement("div");
                div.className = "sidetxt";
                div.innerText = item.queue_number;

                button.appendChild(div);
                form.appendChild(inputQueueNumber);
                form.appendChild(inputTimestamp);
                form.appendChild(inputStudentID);
                form.appendChild(inputRemarks);
                form.appendChild(inputTransaction);
                form.appendChild(inputEndorsed);
                form.appendChild(button);
                sideNav.appendChild(form);
            });
        }
        function notifpopup(data) {
            // Assuming your data is in JSON format, parse the data
            const parsedData = JSON.parse(data);

            // Assuming you have a div with the ID "sidenav" where you display the data
            const sideNav = document.getElementById("call");

            // Clear the existing content in the side navigation
            sideNav.innerHTML = "";

            // Iterate through the parsed data and update the side navigation accordingly
            parsedData.forEach(item => {
                const form = document.createElement("form");
                form.method = "post";
                form.action = "";

                const inputQueue = document.createElement("input");
                inputQueue.type = "hidden";
                inputQueue.name = "queue_number";
                inputQueue.value = item.queue_number;

                form.appendChild(inputQueue);
            });
        }

        // Function to send the AJAX request
        function sendAjaxRequest() {
            $.ajax({
                url: "AccountingHome.php", // Change to your PHP script file
                method: "POST",
                contentType: "application/x-www-form-urlencoded",
                data: {
                    action: 'post_combined_data'
                },
                success: function (response) {
                    // Handle the response from the server
                    console.log(response); // Log the response to the console
                    updateSideNav(response); // Update the side navigation with the fetched data
                },
                error: function (xhr, status, error) {
                    // Handle errors here
                    console.error(error); // Log the error to the console
                }
            });
        }


        // Call the function at regular intervals (e.g., every 2 seconds)
        window.onload = function () {
            sendAjaxRequest();
            setInterval(sendAjaxRequest, 1000); // 2000 milliseconds = 2 seconds
        };


    </script>
</body>

</html>
