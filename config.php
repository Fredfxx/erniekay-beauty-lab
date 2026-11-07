<?php
/**
 * Erniekay Beauty Lab - Configuration
 */

// Resend API Configuration
define('RESEND_API_KEY', getenv('RESEND_API_KEY') ?: 'YOUR_RESEND_API_KEY_HERE');
define('OWNER_EMAIL', 'alfredoteng027@gmail.com');
define('FROM_EMAIL', 'Erniekay Beauty Lab <onboarding@resend.dev>');

// Business Details (Change these anytime you sell or rebrand the site)
define('BUSINESS_NAME', 'Erniekay Beauty Lab');
define('BUSINESS_PHONE', '+233 50 124 6710');
define('BUSINESS_WHATSAPP', '233501246710'); // Without '+' for wa.me links
define('BUSINESS_LOCATION', 'Kumasi, Ghana');

// Database Configuration (For CEISCY hosting / phpMyAdmin)
define('DB_HOST', 'localhost');
define('DB_USER', 'ceiscy_ae117');
define('DB_PASS', ''); // Set your database password here when deploying to CEISCY
define('DB_NAME', 'ceiscy_ae117');
