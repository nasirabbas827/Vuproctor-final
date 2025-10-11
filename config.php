<?php
// Connect to the database
$conn = new mysqli("localhost", "root", "", "vuproctor_prototype");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>