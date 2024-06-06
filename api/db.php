<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "application_form"; // Make sure this is your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
