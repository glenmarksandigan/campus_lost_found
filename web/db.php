<?php
$host = 'mysql-2b818467-sandiganglenmark1-355b.e.aivencloud.com';
$port = '24590';
$dbname = 'defaultdb';
$username = 'avnadmin';
$password = '<redacted>';

$conn = new mysqli($host, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>