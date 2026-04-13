<?php
include 'db.php';
try {
    $id = $_GET['id'] ?? 0;
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
        $stmt->execute([$id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<h3>Item Data</h3><pre>" . json_encode($item, JSON_PRETTY_PRINT) . "</pre>";
        
        $stmt = $pdo->prepare("SELECT * FROM claims WHERE item_id = ?");
        $stmt->execute([$id]);
        $claims = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Claims Data</h3><pre>" . json_encode($claims, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        $stmt = $pdo->query("SELECT id, item_name, found_location, location_found, created_at FROM items ORDER BY id DESC LIMIT 5");
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Recent Items</h3><pre>" . json_encode($items, JSON_PRETTY_PRINT) . "</pre>";
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
