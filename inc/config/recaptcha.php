<?php
/**
 * Google reCAPTCHA Configuration
 * 
 * Get your keys from: https://www.google.com/recaptcha/admin
 */

return [
    // reCAPTCHA v2 - "I'm not a robot" checkbox
    'site_key' => '6Leme18tAAAAAEY6UXKeLBBheMrx2y_RdebURGZF',
    'secret_key' => '6Leme18tAAAAAEnDugnPOiaB-R9pWJMGG-dMbff0',
    
    // API endpoint
    'verify_url' => 'https://www.google.com/recaptcha/api/siteverify',
    
    // Score threshold (for v3, not used in v2)
    'threshold' => 0.5,
];