<?php
declare(strict_types=1);

/**
 * Google OAuth Configuration
 * Replace with your actual credentials from Google Cloud Console
 */
return [
    // ⚠️ REPLACE WITH YOUR ACTUAL CREDENTIALS
    'client_id' => '561273890017-jqb5ji0nhfle5ae2780ggefkrb1mgj2d.apps.googleusercontent.com',
    'client_secret' => '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',
    'redirect_uri' => 'http://localhost/Campus-Food-Ordering-System/Public/google-callback.php',
    
    'scopes' => [
        'openid',
        'email',
        'profile'
    ],
    
    'auth_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
    'token_url' => 'https://oauth2.googleapis.com/token',
    'userinfo_url' => 'https://www.googleapis.com/oauth2/v2/userinfo',
];