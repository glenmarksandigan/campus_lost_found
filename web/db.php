<?php
// Database configuration - Use environment variables for security
$host = getenv('DB_HOST') ?: 'mysql-2b818467-sandiganglenmark1-355b.e.aivencloud.com';
$port = getenv('DB_PORT') ?: '24590';
$dbname = getenv('DB_NAME') ?: getenv('DB_DATABASE') ?: 'defaultdb';
$username = getenv('DB_USER') ?: 'avnadmin';
$password = getenv('DB_PASS') ?: getenv('DB_PASSWORD') ?: '*********';

// --- MySQLi Connection (Used by many files) ---
$conn = mysqli_init();
// Aiven requires SSL. We don't need a specific CA file usually if the system CA is okay
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);

if (!@mysqli_real_connect($conn, $host, $username, $password, $dbname, $port, NULL, MYSQLI_CLIENT_SSL)) {
    die("MySQLi Connection failed: " . mysqli_connect_error());
}

// --- PDO Connection (Expected by check_schema.php and others) ---
try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_SSL_CA => true, // Enable SSL for PDO
    ];
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (\PDOException $e) {
    // We don't die here just in case only mysqli is used, but it's good for debugging
    error_log("PDO Connection failed: " . $e->getMessage());
}
?>