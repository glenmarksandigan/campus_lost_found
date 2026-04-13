<?php
// mail_helper.php
require_once 'config.php';
require_once 'mailer_init.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Sends a credential email to a user.
 * 
 * @param string $toEmail The recipient's email.
 * @param string $fullName The recipient's name.
 * @param string $password The generated or reset password.
 * @param string $type The email type: 'new_account', 'approve', or 'reset'.
 * @return bool True if successful, false otherwise.
 */
function sendCredentialEmail($toEmail, $fullName, $password, $type = 'new_account') {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        // Recipients
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $fullName);

        // Content
        $mail->isHTML(true);

        if ($type === 'reset') {
            $mail->Subject = 'FoundIt! Account Password Reset';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 10px;'>
                    <h2 style='color: #0d6efd;'>Password Reset</h2>
                    <p>Hello <strong>$fullName</strong>,</p>
                    <p>Your account password has been reset by an administrator.</p>
                    <div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                        <p style='margin: 0;'><strong>Username/Email:</strong> $toEmail</p>
                        <p style='margin: 10px 0 0 0;'><strong>New Temporary Password:</strong> <span style='font-family: monospace; font-size: 1.2rem; color: #dc3545;'>$password</span></p>
                    </div>
                    <p>For security, please log in and update your password immediately.</p>
                    <p>Thank you,<br>The FoundIt! Team</p>
                </div>";
        } elseif ($type === 'approve') {
            $mail->Subject = 'FoundIt! Account Approved';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 10px;'>
                    <h2 style='color: #198754;'>Account Approved!</h2>
                    <p>Hello <strong>$fullName</strong>,</p>
                    <p>Great news! Your registration on the <strong>FoundIt!</strong> platform has been approved by an administrator.</p>
                    <p>You can now log in using the email and password you provided during registration.</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='http://localhost/lost_and_foundCC/web/auth.php' style='background-color: #198754; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Log In Now</a>
                    </div>
                    <p>Thank you,<br>The FoundIt! Team</p>
                </div>";
        } else {
            $mail->Subject = 'Welcome to FoundIt! - Your Account Credentials';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 10px;'>
                    <h2 style='color: #0d6efd;'>Welcome to FoundIt!</h2>
                    <p>Hello <strong>$fullName</strong>,</p>
                    <p>An account has been created for you on the FoundIt! platform.</p>
                    <div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                        <p style='margin: 0;'><strong>Username/Email:</strong> $toEmail</p>
                        <p style='margin: 10px 0 0 0;'><strong>Temporary Password:</strong> <span style='font-family: monospace; font-size: 1.2rem; color: #0d6efd;'>$password</span></p>
                    </div>
                    <p>Please use these credentials to log in. You will be asked to set a new password upon your first login.</p>
                    <p>Thank you,<br>The FoundIt! Team</p>
                </div>";
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        // In a production app, log this error $mail->ErrorInfo
        return false;
    }
}
