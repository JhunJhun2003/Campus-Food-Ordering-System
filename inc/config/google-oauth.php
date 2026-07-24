<?php
declare(strict_types=1);

/**
 * Google OAuth Configuration
 * Uses environment variables when available and falls back to a host-aware redirect URI.
 */
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$redirectUri = getenv('GOOGLE_OAUTH_REDIRECT_URI');

if (empty($redirectUri)) {
    $redirectUri = $scheme . '://' . $host . '/Campus-Food-Ordering-System/Public/google-callback.php';
}

return [
    'client_id' => getenv('GOOGLE_CLIENT_ID') ?: '561273890017-jqb5ji0nhfle5ae2780ggefkrb1mgj2d.apps.googleusercontent.com',
    'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: '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',
    'redirect_uri' => $redirectUri,

    'scopes' => [
        'openid',
        'email',
        'profile'
    ],

    'auth_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
    'token_url' => 'https://oauth2.googleapis.com/token',
    'userinfo_url' => 'https://www.googleapis.com/oauth2/v2/userinfo',
];