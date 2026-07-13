<?php
declare(strict_types=1);

namespace App\User\Infrastructure\Services;

use App\User\Domain\Services\GoogleAuthServiceInterface;
use Google\Client;

class GoogleAuthService implements GoogleAuthServiceInterface
{
    private Client $client;
    private array $config;

    public function __construct()
    {
        $this->config = $this->loadConfig();
        $this->client = $this->initializeClient();
    }

    private function loadConfig(): array
    {
        // ✅ Load from your config file location
        $configPath = __DIR__ . '/../../../../inc/config/google-oauth.php';
        
        if (!file_exists($configPath)) {
            throw new \RuntimeException(
                'Google OAuth config file not found at: ' . $configPath
            );
        }

        $config = require $configPath;

        // Validate config
        if (empty($config['client_id'])) {
            throw new \RuntimeException(
                'Google Client ID is not configured in inc/config/google-oauth.php'
            );
        }

        if (empty($config['client_secret'])) {
            throw new \RuntimeException(
                'Google Client Secret is not configured in inc/config/google-oauth.php'
            );
        }

        return $config;
    }

    private function initializeClient(): Client
    {
        $client = new Client();
        $client->setClientId($this->config['client_id']);
        $client->setClientSecret($this->config['client_secret']);
        $client->setRedirectUri($this->config['redirect_uri']);
        $client->addScope($this->config['scopes']);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        return $client;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    public function authenticate(string $code): ?array
    {
        try {
            // Exchange code for access token
            $token = $this->client->fetchAccessTokenWithAuthCode($code);
            
            if (isset($token['error'])) {
                throw new \Exception($token['error_description'] ?? 'Failed to get access token');
            }

            // Get user info from Google
            $oauth2 = new \Google\Service\Oauth2($this->client);
            $userInfo = $oauth2->userinfo->get();

            return [
                'id' => $userInfo->getId(),
                'email' => $userInfo->getEmail(),
                'name' => $userInfo->getName(),
                'avatar' => $userInfo->getPicture(),
                'verified_email' => $userInfo->getVerifiedEmail()
            ];

        } catch (\Exception $e) {
            error_log('Google Auth Error: ' . $e->getMessage());
            return null;
        }
    }
}