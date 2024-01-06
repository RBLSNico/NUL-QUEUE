<?php
include '../database.php';
session_start();

// Check if the user is already logged in, and redirect to AccountingHome.php if necessary
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('Location: AccountingHome.php'); // Redirect to the dashboard or another page
    exit();
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $office = '';

    // Query to check if the user exists in the database and is in the Accounting office
    $sql = "SELECT * FROM user_accounts WHERE username = '$username' AND password = '$password'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        // Successful login
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['office'] = $office;
        header('Location: AccountingHome.php');
        exit(); // Redirect to a dashboard page
    } else {
        // Invalid credentials or wrong office
        $message = "Login failed. Please check your username, password, or office.";
        echo '<script>alert("' . $message . '"); window.location.href="AccountingLogin.php";</script>';
        exit(); // Terminate the script
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NU Accounting Office</title>
    <link href='http://fonts.googleapis.com/css?family=' rel='stylesheet' type='text/css'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm"
        crossorigin="anonymous"></script>
    <link rel="stylesheet" type="text/css" href="styles.css">
</head>

<body>
    
    <div class="container-fluid">
        <div class="row">
            <div class="rounded-end-5 col-4 blue-bg">
                <img src="assets/nu logo.webp" alt="Image" class="img-fluid img_logo"
                    style="max-height: auto; max-width: 40%;">
                <div class="mt-4">
                    <h1 class="fw-bolder text-light text-center">NU LAGUNA</h1>
                    <h4 class="fw-bold text-light text-center">QUEUING SYSTEM</h4>
                </div>

            </div>
            <div class="col-8 p-5">
                <h4 class="fst-italic fs-3 p-5 fw-bold text-center nu_color">Admin.</h4>
              
<!-- log in page goes here-->

<div class="login">
    <form id="login" method="post" action="AccountingLogin.php">
        <h2 class="fst-italic fw-bold">Admin Access</h2>
        <label><b>Username</b></label>
        <input type="text" name="username" id="email" placeholder="Username">
        <br><br>
        <label><b>Password</b></label>
        <input type="password" name="password" id="password" placeholder="Password">
        <br><br>
        <input type="submit" name="log" id="log" value="Log In">
        <br><br>
    </form>
</div>

<!-- end of log page-->

                </div>
            </div>
        </div>
    </div>
    </div>
    </div>

    
</body>

</html>

<script>
    // Check if the user is already logged in and prevent going back to the login page
    window.addEventListener('load', function () {
        if (<?php echo isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true ? 'true' : 'false'; ?>) {
            window.location.replace('MainPage.php'); // Replace with the URL of your main page
        }
    });
</script>
</body>

</html>
