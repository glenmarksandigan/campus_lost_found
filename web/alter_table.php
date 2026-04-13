<?php
include 'db.php';
try {
    $pdo->exec("ALTER TABLE users MODIFY COLUMN year VARCHAR(20) NULL");
    echo "Success: Column 'year' changed to VARCHAR(20).";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
