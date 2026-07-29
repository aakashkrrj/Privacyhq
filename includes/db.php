<?php
// governance/includes/db.php
$host = "127.0.0.1";
$user = "root";
$pass = "";
$dbname = "privacyhq";
$port = 3306;

$conn = new mysqli($host, $user, $pass, $dbname, $port);

if ($conn->connect_error) {
    die(json_encode([
        "status" => "error",
        "message" => "Database connection failed: " . $conn->connect_error
    ]));
}
?>