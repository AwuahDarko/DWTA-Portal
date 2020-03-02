<?php
$servername = "localhost";
$username = "admin"; //wealthbankers_wbwins
$password = "";// eB7Hs@Ldi,ka
$dbname = "affiliate";//wealthbankers_wbsites


// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


