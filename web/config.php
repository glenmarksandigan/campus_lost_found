<?php
/**
 * Centralized configuration file for the Campus Lost & Found system.
 * API keys and sensitive config values go here — never expose these in frontend code.
 */

// Gemini API Key
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: 'AIzaSyAMYUdl6kym7T3mCkd7AlBHDng1OicHwJ4');

// SMTP Configuration (Gmail)
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USER', getenv('SMTP_USER') ?: 'sandiganglenmark1@gmail.com');
define('SMTP_PASS', getenv('SMTP_PASS') ?: 'jfdx utht lnwh yxbk');
define('SMTP_FROM', getenv('SMTP_FROM') ?: 'sandiganglenmark1@gmail.com');
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'FoundIt! Admin');
?>