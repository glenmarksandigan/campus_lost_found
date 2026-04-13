<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use App\Models\User;

class MailService
{
    public function sendNotification(User $user, $subject, $message)
    {
        Mail::raw($message, function ($mail) use ($user, $subject) {
            $mail->to($user->email)
                 ->subject($subject);
        });
    }

    public function notifyMatchedOwner($owner, $item)
    {
        $subject = "Possible Match for your Lost Item: {$item->item_name}";
        $message = "Hello {$owner->fname},\n\nA found item matching your report '{$item->item_name}' has been posted. Please check the portal to verify.";
        $this->sendNotification($owner, $subject, $message);
    }

    public function sendCredentialEmail(User $user, $password, $type = 'new_account')
    {
        $subject = match($type) {
            'reset' => 'FoundIt! Account Password Reset',
            'approve' => 'FoundIt! Account Approved',
            default => 'Welcome to FoundIt! - Your Account Credentials',
        };

        $fullName = "{$user->fname} {$user->lname}";
        
        // In a real app, we'd use a Blade-based Mailable for HTML formatting
        // For this migration, we'll use raw text that mimics the original body
        $message = match($type) {
            'reset' => "Hello {$fullName},\n\nYour account password has been reset by an administrator.\n\nUsername/Email: {$user->email}\nNew Temporary Password: {$password}\n\nPlease log in and update your password immediately.",
            'approve' => "Hello {$fullName},\n\nGreat news! Your registration on the FoundIt! platform has been approved.\n\nYou can now log in using the email and password you provided during registration.",
            default => "Hello {$fullName},\n\nAn account has been created for you on the FoundIt! platform.\n\nUsername/Email: {$user->email}\nTemporary Password: {$password}\n\nPlease use these credentials to log in.",
        };

        $this->sendNotification($user, $subject, $message);
    }
}
