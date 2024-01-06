let endorsementPopup = document.getElementById("endorsementPopup");
    let Nestedpopup = document.querySelector(".nestedpopup"); // Adjust this to target the nested popup element correctly
    let call = document.getElementById("call")

    // JavaScript to open the endorsement popup
    function openEndorsementPopup() {
        endorsementPopup.style.display = "block"; // Show the popup form
        endorsementPopup.classList.add("open-popup");
        backgroundOverlay.style.display = "block"; // Show the background overlay
    }

    // JavaScript to close the endorsement popup
    function closeEndorsementPopup() {
        endorsementPopup.style.display = "none"; // Hide the popup form
        endorsementPopup.classList.remove("open-popup");
        backgroundOverlay.style.display = "none"; // Hide the background overlay
    }

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
    // JavaScript to open the notification popop
    function openNotif(){
        call.classList.add("open-notif");
        call.style.display = "block";
    }
    // JavaScript to close the notification popop
    function closeNotif(){
        call.classList.remove("open-notif");
        call.style,display = "none"
    }
    function exit() {
        window.location.href = "logout.php";
    }
    // Function to handle the AJAX request
    function sendAjaxRequest() {
        $.ajax({
            url: "AccountingHome.php", // Change to your PHP script file
            method: "POST",
            data: {
                key: 'value' // Add your data here
            },
            success: function(response) {
                // Handle the response from the server
                console.log(response); // Log the response to the console
                // Perform any other actions as needed
            },
            error: function(xhr, status, error) {
                // Handle errors here
                console.error(error); // Log the error to the console
            }
        });
    }
    window.onload = function() {
        sendAjaxRequest();
    };
    // Call the function when needed
    sendAjaxRequest();

    function sendRequest() {
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                console.log(this.responseText); // You can handle the response here
            }
        };
        xhttp.open("GET", "AccountingHome.php", true); // Use the correct path to your PHP file
        xhttp.send();
    }
