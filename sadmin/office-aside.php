<?php
@include '../database.php';
session_start();

if (!isset($_SESSION['email'])) {
    header('location: index.php');
    exit();
}

//FOR ADDING OFFICES TWO TABLE ONE WITH LOGS
// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the office name and acronym from the form
    $officeName = strtoupper($_POST["officeName"]);
    $acronym = strtoupper($_POST["acronym"]);
    $otherOffices = isset($_POST["otherOffices"]) ? 1 : 0;

    // Validate input to check for spaces
    if (strpos($officeName, ' ') !== false || strpos($acronym, ' ') !== false) {
        echo '<script>alert("Office name and acronym should not contain spaces");</script>';
    } else {
        // Create the original table name without acronym
        $tableName = preg_replace("/[^a-zA-Z0-9_]/", "", $officeName);

        // Append "_logs" to the original table name
        $logsTableName = $tableName . "_logs";

        // SQL to check if table exists
        $checkTableExistsSQL = "SHOW TABLES LIKE '$tableName'";
        $result = $conn->query($checkTableExistsSQL);

        if ($result->num_rows > 0) {
            echo '<script>alert("Table ' . $tableName . ' already exists");</script>';
        } else {
            // SQL to create original table without acronym
            $sql = "CREATE TABLE $tableName (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `queue_number` varchar(255) NOT NULL,
                `student_id` varchar(12) NOT NULL,
                `endorsed_from` varchar(255) DEFAULT NULL,
                `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
                `timeout` timestamp NULL DEFAULT NULL,
                `remarks` int(11) DEFAULT NULL,
                `transaction` varchar(255) DEFAULT NULL,
                `status` int(11) DEFAULT 0,
                `availability` int(11) DEFAULT 0,
                `window` varchar(255) NOT NULL,
                `endorsed_to` varchar(255) DEFAULT NULL,
                PRIMARY KEY (`id`)
            )";

            if ($conn->query($sql) === TRUE) {
                // Insert the office details into the existing "offices" table with otherOffices value
                $insertOfficeSQL = "INSERT INTO offices (acronym, officeName, office) VALUES ('$acronym', '$officeName', '$otherOffices')";
                if ($conn->query($insertOfficeSQL) === TRUE) {
                    echo '<script>alert("Table ' . $tableName . ' created successfully, and office details added to offices table");</script>';
                } else {
                    echo '<script>alert("Error inserting office details: ' . $conn->error . '");</script>';
                }

                // SQL to create second table with "_logs"
                $logsTableSQL = "CREATE TABLE $logsTableName (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `queue_number` varchar(255) NOT NULL,
                    `student_id` varchar(12) NOT NULL,
                    `endorsed_from` varchar(255) DEFAULT NULL,
                    `endorsed_to` varchar(255) DEFAULT NULL,
                    `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
                    `timeout` timestamp NULL DEFAULT NULL,
                    `remarks` int(11) DEFAULT NULL,
                    `transaction` varchar(255) DEFAULT NULL,
                    `status` int(11) DEFAULT NULL,
                    PRIMARY KEY (`id`)
                )";

                if ($conn->query($logsTableSQL) !== TRUE) {
                    echo '<script>alert("Error creating table: ' . $conn->error . '");</script>';
                }
            } else {
                echo '<script>alert("Error creating table: ' . $conn->error . '");</script>';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OFFICES</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="../styles/index.css">

    <link rel="stylesheet" href="../styles/offices.css">
    <link rel="stylesheet" href="../styles/users.css">
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'aside.php'; ?>
            <div class="col-9 offset-3">

                <h4 class="fs-2 pt-5 ps-5 pb-2 nu_color text-start">OFFICES</h4>
                <hr>

                <div class="row no-gutters g-0 text-center">
                    <div class="col-sm-5 col-md-7">
                        <div class="col-sm-3 mb-3" style="width: 14rem;">
                            <div class="card card-db">
                                <div class="card-body gap-3 py-2">
                                    <div class="d-flex gap-4 align-items-center justify-content-center py-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" fill="#FFD41C" class="bi bi-building-fill-gear" viewBox="0 0 16 16">
                                            <path d="M1.5 3A1.5 1.5 0 0 0 0 4.5V6a.5.5 0 0 0 .5.5 1.5 1.5 0 1 1 0 3 .5.5 0 0 0-.5.5v1.5A1.5 1.5 0 0 0 1.5 13h13a1.5 1.5 0 0 0 1.5-1.5V10a.5.5 0 0 0-.5-.5 1.5 1.5 0 0 1 0-3A.5.5 0 0 0 16 6V4.5A1.5 1.5 0 0 0 14.5 3h-13Z" />
                                        </svg>

                                        <div class="align-items-center justify-content-center">
                                            <h2 class="my-0 me-4 fs-2 fw-bold nu_color px-auto mx-auto" id="offices-count">
                                                <div class="spinner-border" role="status">
                                                    <!-- <span class="visually-hidden">Loading...</span> -->
                                                </div>
                                            </h2>
                                            <p class="fs-5 mt-0 nu_color">OFFICES</p>
                                        </div>
                                    </div>
                                </div>


                            </div>
                        </div>

                    </div>
                    <div class="col-6 col-md-3 align-self-end mb-3 search-container position-relative d-flex justify-content-end">
                        <form action="" method="get" class="py-0">
                            <i class="bi bi-search"></i>
                            <input name="searchOffices" class="search mb-0 rounded" type="search" placeholder="SEARCH" aria-label="Search" required value="<?php if (isset($_GET['searchUser'])) {
                                                                                                                                                                echo $_GET['searchOffices'];
                                                                                                                                                            } ?>" id="searchOfficesInput">
                        </form>
                    </div>
                    <!-- Button to open the Add Office modal -->
                    <div class="col-1 col-md-2 align-self-end justify-content-start mb-3">
                        <a href="#" data-bs-toggle="modal" data-bs-target="#addOfficeModal" class="btn btn-green btn-lg my-2 my-sm-0 mx-2" type="submit">ADD OFFICE</a>
                    </div>
                </div>

                <div class="row g-0 text-center">
                    <table class="table-bordered text-center border-black">
                        <thead>
                            <tr class="background-blue">
                                <!-- <th scope="col">ID</th> -->
                                <th scope="col">Acronym</th>
                                <th scope="col">Offices Name</th>
                                <th scope="col" class="col-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <?php

                                $results_per_page = 8;

                                if (isset($_GET['page'])) {
                                    $page = $_GET['page'];
                                } else {
                                    $page = 1;
                                }

                                $start_from = ($page - 1) * $results_per_page;

                                if (isset($_GET['searchOffices'])) {
                                    $filtervalues = $_GET['searchOffices'];
                                    $query = "SELECT * FROM offices WHERE CONCAT(acronym, officeName) LIKE '%$filtervalues%' ";
                                    $query_run = mysqli_query($conn, $query);

                                    if (mysqli_num_rows($query_run) > 0) {
                                        foreach ($query_run as $items) {
                                            // Output data for search results
                                            echo "<tr>";
                                            //echo "<td>" . $items['ID'] . "</td>";
                                            echo "<td>" . $items['acronym'] . "</td>";
                                            echo "<td>" . $items['officeName'] . "</td>";
                                            echo "<td><button class='btn btn-edit my-2 my-sm-0 mx-2' type='submit'>Edit</button>";
                                            echo "<button class='btn btn-red my-2 my-sm-0 mx-2' type='submit'>Delete</button></td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='7'>No matching records found</td></tr>";
                                    }
                                } else {
                                    // Retrieve data from the "user_accounts" table when the search bar is empty
                                    $sql = "SELECT * FROM offices LIMIT $start_from, $results_per_page";
                                    $result = mysqli_query($conn, $sql);

                                    while ($row = mysqli_fetch_assoc($result)) {
                                        // Output data for all records
                                        echo "<tr>";
                                        //echo "<td>" . $row['ID'] . "</td>";
                                        echo "<td>" . $row['acronym'] . "</td>";
                                        echo "<td>" . $row['officeName'] . "</td>";
                                        echo "<td><button class='btn btn-edit my-2 my-sm-0 mx-2 edit-button' data-officeid='" . $row['ID'] . "' onclick='openEditOfficeModal(" . $row['ID'] . ", \"" . $row['acronym'] . "\", \"" . $row['officeName'] . "\")'>Edit</button>";
                                        echo "<button class='btn btn-red my-2 my-sm-0 mx-2' type='button' onclick='deleteOffice(" . $row['ID'] . ", \"" . $row['officeName'] . "\")'>Delete</button>";
                                        echo "</tr>";
                                    }
                                }
                                ?>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="pagination-container d-flex justify-content-between align-items-center">
                    <span class="page-label" style="align-self-end;"></span>
                    <div class="pagination">
                        <?php
                        $sql = "SELECT COUNT(*) AS total FROM offices";
                        $result = mysqli_query($conn, $sql);
                        $row = mysqli_fetch_assoc($result);
                        $total_pages = ceil($row["total"] / $results_per_page);

                        echo "<span class='page-label1'>PAGE</span>";
                        for ($i = 1; $i <= $total_pages; $i++) {
                            echo "<a href='office-aside.php?page=$i'";
                            if ($i == $page)
                                echo " class='active'";
                            echo ">$i</a>";
                        }
                        ?>
                    </div>

                </div>

            </div>
        </div>
        <!-- ADD OFFICE MODAL STARTS -->
        <div class="modal fade" id="addOfficeModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header d-block border-0 pb-0">
                        <h1 class="modal-title fs-3 text-center custom-bold custom-secondary-color" id="modalTitle2">
                            ADD OFFICE</h1>
                        <p class="modal-secondary fs-4 fst-italic fw-bold text-center custom-primary-color p-0 m-0">
                            Enter the office name and acronym to add a new office.</p>
                    </div>
                    <form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post">
                        <div class="modal-body pb-0 my-3">
                            <div class="mb-2">
                                <label for="officeName" class="text-start">Office Name</label>
                                <input type="text" id="officeName" name="officeName" class="form-control text-start rounded border-1 border-dark custom-primary-color font-weight-bold" required>
                            </div>
                            <div class="mb-2">
                                <label for="acronym" class="text-start">Acronym</label>
                                <input type="text" id="acronym" name="acronym" class="form-control text-start rounded border-1 border-dark custom-primary-color font-weight-bold" required>
                            </div>
                            <div class="mb-2 form-check">
                                <input type="checkbox" id="otherOffices" name="otherOffices" class="form-check-input">
                                <label for="otherOffices" class="form-check-label">Other Offices</label>
                            </div>
                        </div>
                        <div class="modal-footer d-flex justify-content-end border-0 col">
                            <button type="button" class="btn btn-white px-4" data-bs-dismiss="modal">CANCEL</button>
                            <button type="submit" class="btn btn-green px-4" id="addOfficeBtn">ADD OFFICE</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- ADD OFFICE MODAL ENDS -->

        <!-- EDIT OFFICE MODAL STARTS -->
        <div class="modal fade" id="editOfficeModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header d-block border-0 pb-0">
                        <h1 class="modal-title fs-3 text-center custom-bold custom-secondary-color" id="modalTitle2">
                            EDIT OFFICE</h1>
                        <p class="modal-secondary fs-4 fst-italic fw-bold text-center custom-primary-color p-0 m-0">
                            Edit the office details.</p>
                    </div>
                    <form id="editOfficeForm" action="editOffice.php" method="post">
                        <div class="modal-body pb-0  my-3">
                            <input type="hidden" id="ID" name="ID">
                            <label for="acronym" class="text-start">Acronym</label>
                            <input type="text" id="acronym" name="acronym" class="form-control text-start rounded border-1 border-dark custom-primary-color font-weight-bold mb-2">
                            <p class="text-danger" id="error_acronym" style="display: none;">Please enter
                                Acronym</p>

                            <div class="row">
                                <div class="col">
                                    <label for="OfficeName" class="text-start">Office Name</label>
                                    <input type="text" id="OfficeName" name="OfficeName" class="form-control text-start rounded border-1 border-dark custom-primary-color font-weight-bold mb-2">
                                    <p class="text-danger" id="error-officeName" style="display: none;">Please enter Office
                                        Name</p>
                                </div>
                            </div>
                            <div class="mb-2 form-check">
                                <input type="checkbox" id="otherOfficesUpdated" name="otherOfficesUpdated" class="form-check-input">
                                <label for="otherOfficesUpdated" class="form-check-label">Other Offices</label>
                            </div>
                        </div>

                        <div class="modal-footer d-flex justify-content-end border-0 col">
                            <button type="button" class="btn btn-white px-4" data-bs-dismiss="modal">CANCEL</button>
                            <input type="submit" class="btn btn-green px-4" name="edit_Office" value="SAVE CHANGES">
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- EDIT OFFICE MODAL ENDS -->

        <!-- DELETE OFFICE MODAL STARTS -->
        <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                    </div>
                    <div class="modal-body" id="deleteModalBody">
                        <!-- Modal body content will be dynamically set by JavaScript -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirmDeleteButton">Delete</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- DELETE OFFICE MODAL ENDS -->
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../script/script.js"></script>
    <script src="../script/offices.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="/chart.js"></script>
    <script src="../script/college.js"></script>
</body>

</html>
