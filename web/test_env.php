<?php
echo "<h1>Environment Check</h1>";
echo "DB_HOST: " . (getenv('DB_HOST') ? "✅ Found" : "❌ NOT FOUND") . "<br>";
echo "DB_PORT: " . (getenv('DB_PORT') ? "✅ Found" : "❌ NOT FOUND") . "<br>";
echo "DB_USER: " . (getenv('DB_USER') ? "✅ Found" : "❌ NOT FOUND") . "<br>";
echo "DB_NAME: " . (getenv('DB_NAME') ? "✅ Found" : "❌ NOT FOUND") . "<br>";
echo "DB_PASSWORD: " . (getenv('DB_PASSWORD') ? "✅ Found" : "❌ NOT FOUND") . "<br>";
echo "DB_PASS: " . (getenv('DB_PASS') ? "✅ Found" : "❌ NOT FOUND") . "<br>";

if (getenv('DB_PASSWORD')) {
    echo "Password Length: " . strlen(getenv('DB_PASSWORD')) . " characters.<br>";
}
?>