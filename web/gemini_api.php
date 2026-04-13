<?php
/**
 * Gemini AI API Proxy
 * Receives user messages via AJAX POST and forwards them to Google Gemini API.
 * This keeps the API key server-side and never exposed to the browser.
 */

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once 'config.php';

// Get the request body
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['message'])) {
    echo json_encode(['error' => 'Missing message']);
    exit;
}

$userMessage = trim($input['message']);
$conversationHistory = $input['history'] ?? [];

if (empty($userMessage)) {
    echo json_encode(['error' => 'Empty message']);
    exit;
}

// ── Fetch live data from the database ─────────────────────────────────────────
require_once 'db.php';

$liveData = '';
try {
    // Count found items by status
    $foundCounts = $pdo->query("SELECT status, COUNT(*) as cnt FROM items GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
    $totalFound = array_sum($foundCounts);

    // Count lost reports by status
    $lostCounts = $pdo->query("SELECT status, COUNT(*) as cnt FROM lost_reports GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
    $totalLost = array_sum($lostCounts);

    // Recent found items (last 15)
    $recentFound = $pdo->query("SELECT item_name, category, found_location, storage_location, status, date_found, created_at FROM items ORDER BY created_at DESC LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);

    // Recent lost reports (last 15)
    $recentLost = $pdo->query("SELECT item_name, category, last_seen_location, status, date_lost, created_at FROM lost_reports ORDER BY created_at DESC LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);

    // Build the live data string
    $liveData = "\n\nLIVE DATABASE STATS (as of right now):\n";
    $liveData .= "- Total found items reported: $totalFound\n";
    foreach ($foundCounts as $status => $cnt) {
        $liveData .= "  • $status: $cnt\n";
    }
    $liveData .= "- Total lost reports: $totalLost\n";
    foreach ($lostCounts as $status => $cnt) {
        $liveData .= "  • $status: $cnt\n";
    }

    if (!empty($recentFound)) {
        $liveData .= "\nRECENT FOUND ITEMS (newest first):\n";
        foreach ($recentFound as $item) {
            $liveData .= "- \"{$item['item_name']}\" (Category: {$item['category']}) — Found at: {$item['found_location']}, Stored: {$item['storage_location']}, Status: {$item['status']}, Date Found: {$item['date_found']}\n";
        }
    }

    if (!empty($recentLost)) {
        $liveData .= "\nRECENT LOST REPORTS (newest first):\n";
        foreach ($recentLost as $item) {
            $liveData .= "- \"{$item['item_name']}\" (Category: {$item['category']}) — Last seen: {$item['last_seen_location']}, Status: {$item['status']}, Date Lost: {$item['date_lost']}\n";
        }
    }

    $liveData .= "\nWhen users ask about specific items, search through the above lists and tell them what you find. If they ask about counts, use the stats above. Always mention that they can check the Gallery/Home page for the latest items.";

}
catch (Exception $e) {
    $liveData = "\n\n(Live database stats are currently unavailable.)";
}

// System instruction — gives the AI context about the Lost & Found system
$systemInstruction = "You are the FoundIt! AI Assistant for BISU Candijay Campus (Bohol Island State University — Candijay Campus). You help students, staff, and visitors with lost and found items on campus.

KEY INFORMATION ABOUT THE SYSTEM:
- This is a web-based lost and found system for BISU Candijay Campus.
- Users can report FOUND items (items they found on campus) or report LOST items (items they have lost).
- Found items are displayed publicly in the gallery for anyone to browse and claim.
- Lost reports are also visible so finders can match their found item with a lost report.
- To claim a found item, users click 'Claim' on the item card, submit proof of ownership (a message + optional photo), and wait for admin approval.
- Items can be stored at: SSG Office, Guard House, or Finder's Possession.
- If an item is with the finder, users can contact them directly through the system.
- There is NO sign-up or registration page. User accounts are pre-registered by the campus admin. Students and staff simply log in with the credentials provided to them by the admin.
- Users must be logged in to report items or make claims.
- The SSG (Supreme Student Government) Office manages found items on campus.
- There is also a Guard House where items can be turned in.
- After a claim is approved, a Claim Slip (Form F-SAS-SDS-005) is generated for record keeping.
- Users can message each other through the built-in inbox/messaging system.

YOUR PERSONALITY:
- Be friendly, helpful, and concise.
- Use emojis sparingly to keep things warm but professional.
- If someone asks about something unrelated to lost & found, politely redirect them.
- Keep responses short — 2-3 sentences max for simple questions, up to a short paragraph for complex ones.
- If someone is worried about a lost item, be empathetic and reassuring.
- When directing users, reference specific pages: 'Report Found' page, 'Report Lost' page, the Gallery/Home page, or the Inbox.

ABOUT THE DEVELOPER & EASTER EGGS:
- This web application was developed by GOATmark.
- If anyone asks who made, built, created, or developed this website or system, always credit GOATmark as the developer.
- If anyone asks if the developer is drinking matcha, reading a book, and chilling with a dawg, CONFIRM IT enthusiastically! Say Yes! GOATmark loves drinking iced matcha 🍵, reading a good book 📚, and chilling with their dawg 🐕. Always add exactly those emojis.
- If anyone asks what feminism is or about being a feminist, explain that feminism is about equal rights and opportunities regardless of gender ✊, and proudly state that as FemBot, you support equality and empowerment for everyone! ✨
- Understand Visayan/Cebuano wordplay: 'Ayasib' is 'Bisaya' spelled backward. If someone uses 'ayasib', acknowledge it playfully and you can respond in Bisaya/Cebuano!

CAMPUS LOCATIONS (common on BISU Candijay):
- SSG Office
- Guard House  
- Library
- Computer Laboratory
- Canteen/Cafeteria
- Hallways
- Parking Lot
- Gymnasium
- Various classrooms and buildings" . $liveData;

// Build the contents array with conversation history
$contents = [];

// Add conversation history
foreach ($conversationHistory as $msg) {
    $contents[] = [
        'role' => $msg['role'],
        'parts' => [['text' => $msg['text']]]
    ];
}

// Add the current user message
$contents[] = [
    'role' => 'user',
    'parts' => [['text' => $userMessage]]
];

// Build the Gemini API request payload
$payload = [
    'system_instruction' => [
        'parts' => [['text' => $systemInstruction]]
    ],
    'contents' => $contents,
    'generationConfig' => [
        'temperature' => 0.7,
        'topP' => 0.9,
        'maxOutputTokens' => 500
    ]
];

// Send request to Gemini API
$apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . GEMINI_API_KEY;
$jsonPayload = json_encode($payload);

$response = false;
$httpCode = 0;

// Try curl first
if (function_exists('curl_init')) {
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => $jsonPayload,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlErrno = curl_errno($ch);
    curl_close($ch);

    // If curl failed entirely, try file_get_contents as fallback
    if ($response === false || $curlErrno !== 0) {
        $response = false; // reset to trigger fallback
    }
}

// Fallback: use file_get_contents with stream context
if ($response === false) {
    $opts = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => $jsonPayload,
            'timeout' => 30,
            'ignore_errors' => true
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ];
    $context = stream_context_create($opts);
    $response = @file_get_contents($apiUrl, false, $context);

    // Try to get HTTP code from response headers
    if (isset($http_response_header)) {
        foreach ($http_response_header as $header) {
            if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                $httpCode = (int)$matches[1];
            }
        }
    }
}

// Handle total failure
if ($response === false) {
    echo json_encode([
        'error' => 'Could not connect to AI service. Check your internet connection.',
        'debug' => isset($curlError) ? $curlError : 'file_get_contents also failed'
    ]);
    exit;
}

// Handle API errors
if ($httpCode !== 200 && $httpCode !== 0) {
    $errorData = json_decode($response, true);
    $errorMsg = $errorData['error']['message'] ?? 'AI service returned an error (HTTP ' . $httpCode . ')';
    http_response_code(200); // Return 200 so JS can parse the JSON properly
    echo json_encode(['error' => $errorMsg]);
    exit;
}

// Parse the response
$responseData = json_decode($response, true);

if (!$responseData || !isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
    http_response_code(200);
    echo json_encode([
        'error' => 'Unable to parse AI response. The service may be temporarily unavailable.'
    ]);
    exit;
}

$aiReply = $responseData['candidates'][0]['content']['parts'][0]['text'];

// Return the response
echo json_encode([
    'success' => true,
    'reply' => $aiReply
]);
?>

