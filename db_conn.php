<?php

$servername = "localhost";
$username = "root"; // Your database username
$password = ""; // Your database password
$dbname = "research_work";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}