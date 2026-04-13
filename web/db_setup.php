<?php
include 'db.php';

echo "<h1>FoundIt! Database Setup</h1>";

$tables = [
    "users" => "CREATE TABLE IF NOT EXISTS users (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        fname VARCHAR(100) NOT NULL,
        lname VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        email_verified_at TIMESTAMP NULL,
        password VARCHAR(255) NOT NULL,
        type_id INT DEFAULT 1,
        contact_number VARCHAR(20) NULL,
        status VARCHAR(20) DEFAULT 'Active',
        student_id VARCHAR(30) NULL,
        address VARCHAR(255) NULL,
        zipcode VARCHAR(10) NULL,
        organizer_role VARCHAR(50) NULL,
        can_edit TINYINT(1) DEFAULT 1,
        remember_token VARCHAR(100) NULL,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL
    )",
    "items" => "CREATE TABLE IF NOT EXISTS items (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NULL,
        item_name VARCHAR(255) NOT NULL,
        category VARCHAR(100) NULL,
        description TEXT NULL,
        found_location VARCHAR(255) NULL,
        storage_location VARCHAR(255) NULL,
        found_date DATE NULL,
        image_path VARCHAR(255) NULL,
        status VARCHAR(50) DEFAULT 'Published',
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )",
    "lost_reports" => "CREATE TABLE IF NOT EXISTS lost_reports (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        item_name VARCHAR(255) NOT NULL,
        description TEXT NULL,
        last_seen_location VARCHAR(255) NULL,
        image_path VARCHAR(255) NULL,
        status VARCHAR(50) DEFAULT 'Lost',
        owner_name VARCHAR(255) NULL,
        owner_contact VARCHAR(50) NULL,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "claims" => "CREATE TABLE IF NOT EXISTS claims (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        item_id BIGINT UNSIGNED NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        claim_message TEXT NULL,
        image_path VARCHAR(255) NULL,
        status VARCHAR(50) DEFAULT 'Pending',
        claimed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "messages" => "CREATE TABLE IF NOT EXISTS messages (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        sender_id BIGINT UNSIGNED NOT NULL,
        receiver_id BIGINT UNSIGNED NOT NULL,
        subject VARCHAR(255) NULL,
        body TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "activity_logs" => "CREATE TABLE IF NOT EXISTS activity_logs (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        action_type VARCHAR(255) NOT NULL,
        target_type VARCHAR(255) NOT NULL,
        target_id BIGINT UNSIGNED NOT NULL,
        details TEXT NULL,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "admin_tasks" => "CREATE TABLE IF NOT EXISTS admin_tasks (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        assigned_to BIGINT UNSIGNED NOT NULL,
        assigned_by BIGINT UNSIGNED NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT NULL,
        priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
        status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
        due_date DATE NULL,
        completed_at TIMESTAMP NULL,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE CASCADE
    )",
    "lost_contacts" => "CREATE TABLE IF NOT EXISTS lost_contacts (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        report_id BIGINT UNSIGNED NOT NULL,
        finder_name VARCHAR(255) NOT NULL,
        finder_contact VARCHAR(50) NULL,
        message TEXT NULL,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        FOREIGN KEY (report_id) REFERENCES lost_reports(id) ON DELETE CASCADE
    )"
];

foreach ($tables as $name => $sql) {
    try {
        $pdo->exec($sql);
        echo "<p style='color:green;'>✔ Table '$name' created or already exists.</p>";
    } catch (PDOException $e) {
        echo "<p style='color:red;'>✘ Error creating table '$name': " . $e->getMessage() . "</p>";
    }
}

// Create default admin if not exists
try {
    $admin_email = 'admin@school.edu.ph';
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$admin_email]);
    if (!$stmt->fetch()) {
        $pass = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (fname, lname, email, password, type_id, status) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute(['System', 'Admin', $admin_email, $pass, 4, 'Active']);
        echo "<p style='color:blue;'>ℹ Default admin created: <strong>$admin_email</strong> / <strong>admin123</strong></p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:orange;'>⚠ Info: Could not check/create admin user: " . $e->getMessage() . "</p>";
}

echo "<h2>Setup Complete!</h2>";
echo "<p>Please <strong>delete</strong> this file (db_setup.php) via GitHub/Render for security.</p>";
?>