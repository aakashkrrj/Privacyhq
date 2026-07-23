<?php
// governance/includes/db.php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "privacy_governance";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die(json_encode([
        "status" => "error",
        "message" => "Database connection failed: " . $conn->connect_error
    ]));
}
?>