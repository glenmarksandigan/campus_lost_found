<?php
// get_dropdowns.php
// Handles AJAX requests for cascading college → department → course dropdowns

header('Content-Type: application/json');

require_once 'db.php'; // adjust to your DB connection file

$action = $_GET['action'] ?? '';

switch ($action) {

    case 'colleges':
        $stmt = $pdo->query("SELECT id, college_name FROM colleges ORDER BY college_name");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    // --- YOUR NEW CODE START ---
    case 'all_departments':
        $data = $pdo->query("SELECT id, department_name FROM departments ORDER BY department_name")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($data);
        exit;
    // --- YOUR NEW CODE END ---

    case 'departments':
        $college_id = intval($_GET['college_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT id, department_name FROM departments WHERE college_id = ? ORDER BY department_name");
        $stmt->execute([$college_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'courses':
        $dept_id = intval($_GET['department_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT id, course_name FROM courses WHERE department_id = ? ORDER BY course_name");
        $stmt->execute([$dept_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}