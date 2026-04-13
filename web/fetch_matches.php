<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit;
}
include 'db.php';

$reportId = $_GET['report_id'] ?? null;
if (!$reportId) {
    echo json_encode(['success' => false, 'error' => 'Missing Report ID']); exit;
}

try {
    // 1. Get the lost report details
    $reportStmt = $pdo->prepare("SELECT * FROM lost_reports WHERE id = ?");
    $reportStmt->execute([$reportId]);
    $report = $reportStmt->fetch(PDO::FETCH_ASSOC);

    if (!$report) {
        echo json_encode(['success' => false, 'error' => 'Report not found']); exit;
    }

    // 2. Build the matching query
    // We match by:
    // a) Same category
    // b) Keywords in item_name (simple LIKE for now)
    // d) Status must be 'Published' or 'Claiming' (available items)

    $category = $report['category'];
    $itemName = $report['item_name'];
    
    // Split item name into keywords (min 3 chars)
    $keywords = array_filter(explode(' ', $itemName), function($k) { return strlen($k) > 2; });
    
    $sql = "SELECT i.*, 
                   CONCAT(u.fname, ' ', u.lname) as finder_name
            FROM items i
            LEFT JOIN users u ON i.user_id = u.id
            WHERE i.status IN ('Published', 'Claiming')
            AND (i.category = ? ";
    
    $params = [$category];
    
    if (!empty($keywords)) {
        foreach ($keywords as $k) {
            $sql .= " OR i.item_name LIKE ? OR i.description LIKE ?";
            $params[] = "%$k%";
            $params[] = "%$k%";
        }
    }
    
    $sql .= ") ORDER BY i.created_at DESC LIMIT 10";
    
    $matchStmt = $pdo->prepare($sql);
    $matchStmt->execute($params);
    $matches = $matchStmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. (Optional) Enhance with similarity score
    // For now, we just return the raw matches. 
    // We can add logic here to check extra_ fields if they exist in both.
    
    echo json_encode(['success' => true, 'matches' => $matches]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
