//local storage / validate
function submitStudentId() {
    var studentId = document.getElementById("studentId").value;
    var program = document.getElementById("program").value;

    if (studentId === "") {
        document.getElementById("error-message").style.display = "block";
        return;
    }

    // Set the student ID in local storage
    localStorage.setItem("studentId", studentId);
    localStorage.setItem("program", program);

    if (studentId === "") {
        return;
    }

    window.location.href = "nulqueue.php";
}


// queue student
function registerStudent() {
    var studentId = localStorage.getItem("studentId");
    var program = localStorage.getItem("program");
    var office = document.getElementById("modalTitle1").innerText;

    $.ajax({
        type: "POST",
        url: "process.php",
        data: { studentId: studentId, program: program, office: office },
        dataType: "json",
        success: function (response) {
            if (response.success) {
                var queueNumber = response.queue_number;
                document.getElementById("queueNumber").innerText = queueNumber;
                $('#thirdModal').modal('show');
            } else {
                alert("Error: " + response.message);
            }
        },
        error: function () {
            alert("An error occurred.");
        }
    });
}

// queue student
function registerGuest() {
    localStorage.setItem("studentId", "GUEST");
    localStorage.setItem("program", "-");
    var studentId = localStorage.getItem("studentId");
    var program = localStorage.getItem("program");
    var office = document.getElementById("modalTitle1").innerText;

    $.ajax({
        type: "POST",
        url: "process.php",
        data: {
            studentId: studentId,
            program: program,
            office: office
        },
        dataType: "json",
        success: function (response) {
            if (response.success) {
                var queueNumber = response.queue_number;
                // Set the queue number in the modal
                document.getElementById("queueNumber").innerText = queueNumber;
                // Show the third modal
                $('#thirdModal').modal('show');
            } else {
                alert("Error: " + response.message);
            }
        },
        error: function () {
            alert("An error occurred.");
        }
    });
}

// Function to update the modal titles
function updateModalTitle(modalId, title) {
    $(modalId).find(".modal-title").text(title);
}

// Event listener for button clicks

$(".btn").click(function () {
    var modalTitle = $(this).data("title");


    // Update modals
    updateModalTitle("#firstModal", modalTitle);
    updateModalTitle("#secondModal", modalTitle);
    updateModalTitle("#thirdModal", modalTitle);
    updateModalTitle("#acadModal", modalTitle);
    updateModalTitle("#acadModal2", modalTitle);
    updateModalTitle("#acadModal3", modalTitle);
    // populateProgramChairs(modalTitle);
});




// Handle the submit button click event
function insertAcads() {
    var studentId = localStorage.getItem("studentId");
    var selectedChair = $("#program-chair-select option:selected");

    var name = selectedChair.text().split('<---')[0].trim(); //cut the 
    var program = document.getElementById("modalTitle1").innerText;
    var program_queue = localStorage.getItem("program");
    var office = document.getElementById("modalTitle1").innerText;


    // Send data to the server to insert into the 'academics' table
    $.ajax({
        url: "academics.php",
        type: "POST",
        data: {
            concern: name,
            program: program,
            studentId: studentId,
            office: office,
            program_queue: program_queue,
           
        },
        dataType: "json",
        success: function (response) {
            if (response.success) {
                var queueNumber = response.queue_number;
                document.getElementById("queueNumber").innerText = queueNumber;
                $('#acadModal3').modal('show');
            } else {
                alert("Error: " + response.message);
            }
        },
        error: function () {
            alert("An error occurred.");
        }
    });
};

// $(document).ready(function () {
//     populateProgramChairs();
// });



function populateDropdown(programselected) {
    // Make an Ajax request to fetch data based on the selected program
    $.ajax({
        url: "academics.php",
        type: "GET",
        data: { program: programselected },
        dataType: "json",
        success: function (data) {
            // Clear existing options
            $('#program-chair-select').empty();

            // Add the retrieved options to the select element
            $.each(data, function (key, value) {
                $('#program-chair-select').append($('<option>', {
                    value: key,
                    text: value.full_name
                }));
            });
        },
        error: function () {
            console.error("Error fetching data from the server.");
        }
    });


    $("#done-button").click(function () {
        var selectedChair = $('#program-chair-select option:selected').text();

        $('#selected-chair').text(selectedChair);
    });
}


function updateCustomerCount() {
    $.ajax({
        url: 'db-process.php?action=customers',
        type: 'GET',
        success: function (data) {
            // Update count
            $('#customer-count').text(data);
        },
    });
}


function updateCompletedCount() {
    $.ajax({
        url: 'db-process.php?action=completed',
        type: 'GET',
        success: function (data) {
            // Update count
            $('#completed-count').text(data);
        },
    });
}

function updatePendingCount() {
    $.ajax({
        url: 'db-process.php?action=pending',
        type: 'GET',
        success: function (data) {
            // Update count
            $('#pending-count').text(data);
        },
    });
}

function updateAccountsCount() {
    $.ajax({
        url: 'db-process.php?action=accounts',
        type: 'GET',
        success: function (data) {
            // Update count
            $('#accounts-count').text(data);
        },
    });
}

function updateCollegeCount() {
    $.ajax({
        url: 'db-process.php?action=colleges',
        type: 'GET',
        success: function (data) {
            // Update count
            $('#colleges-count').text(data);
        },
    });
}

// GET OFFICE COUNTS STARTS
function updateOfficeCount() {
    $.ajax({
        url: 'db-process.php?action=offices',
        type: 'GET',
        success: function (data) {
            // Update count
            $('#offices-count').text(data);
        },
        error: function (error) {
            console.error('Error fetching office count:', error);
        }
    });
}
// GET OFFICE COUNTS ENDS

$(document).ready(function () {

    $('#check').click(function () {
        alert($(this).is(':checked'));
        $(this).is(':checked') ? $('#test-input').attr('type', 'text') : $('#test-input').attr('type', 'password');
    });
    updateCustomerCount();
    updateCompletedCount();
    updatePendingCount();
    updateAccountsCount();
    updateCollegeCount();
    updateOfficeCount();
    setInterval(updateOfficeCount, 1000);
    setInterval(updateCustomerCount, 1000);
    setInterval(updateCompletedCount, 1000);
    setInterval(updatePendingCount, 1000);
    setInterval(updateAccountsCount, 1000);
    setInterval(updateCollegeCount, 1000);
});


function togglePasswordVisibility() {
    var passwordInput = document.getElementById("passwordInput");
    var passwordCheckbox = document.getElementById("showPasswordCheckbox");

    if (passwordCheckbox.checked) {
        passwordInput.type = "text";
    } else {
        passwordInput.type = "password";
    }
}




setInterval(function () {
    $('.main-container').load('display_queue.php');
}, 1000);



function returnIndex() {
    window.location.href = 'index.php'
  }
