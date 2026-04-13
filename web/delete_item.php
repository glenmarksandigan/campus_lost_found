if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['type_id'], [4, 6])) {
    http_response_code(403);
    exit("Unauthorized");
}
include 'db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        // 1. Fetch image to delete from folder
        $stmt = $pdo->prepare("SELECT image_path FROM items WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if ($row && !empty($row['image_path'])) {
            $filePath = "uploads/" . $row['image_path'];
            if (file_exists($filePath)) { @unlink($filePath); }
        }

        // 2. Delete from Database
        $deleteStmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
        $deleteStmt->execute([$id]);

        http_response_code(200); // Send success to JavaScript
    } catch (Exception $e) {
        http_response_code(500);
        echo $e->getMessage();
    }
} else {
    http_response_code(400);
}
exit;
?>