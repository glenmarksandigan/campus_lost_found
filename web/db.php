
<?php
$host = 'localhost';
$dbname = 'campus_lost_found';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// some legacy scripts use a mysqli connection named $conn; create it here as well
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("MySQL connection failed: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');
?>