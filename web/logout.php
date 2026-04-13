<?php
/**
 * logout.php - Universal logout handler
 * Clears sessions, chatbot local storage, and prevents back-button access.
 */
if (session_status() === PHP_SESSION_NONE)
    session_start();

// Determine redirect portal based on type_id BEFORE clearing session
$type_id = (int)($_SESSION['type_id'] ?? 1);
$redirect_url = in_array($type_id, [2, 4, 5, 6]) ? 'login.php' : 'signup.php';

// Clear and destroy session
session_unset();
session_destroy();

// Prevent back-button cache access
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Logging Out...</title>
</head>
<body style="background: #f8fafc; font-family: sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0;">
    <div style="text-align: center;">
        <div style="border: 3px solid #e2e8f0; border-top: 3px solid #0d6efd; border-radius: 50%; width: 24px; height: 24px; animation: spin 0.8s linear infinite; margin: 0 auto 15px;"></div>
        <p style="color: #64748b; font-size: 0.9rem;">Signing you out securely...</p>
    </div>

    <style>@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>

    <script>
        // 1. Clear chatbot data
        const keysToRemove = [
            'chatbot_history', 
            'chatbot_messages_html', 
            'chatbot_is_open', 
            'chatbot_chips_hidden'
        ];
        keysToRemove.forEach(k => localStorage.removeItem(k));
        
        // 2. Prevent back-button navigation to protected pages
        const target = '<?php echo $redirect_url; ?>';
        if (window.history.replaceState) {
            window.history.replaceState(null, null, target);
        }

        // 3. Final redirect
        setTimeout(() => {
            window.location.replace(target);
        }, 300);
    </script>
</body>
</html>