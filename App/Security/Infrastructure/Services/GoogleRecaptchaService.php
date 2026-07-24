<?php
declare(strict_types=1);

namespace App\Security\Infrastructure\Services;

use App\Security\Domain\Services\RecaptchaServiceInterface;

class GoogleRecaptchaService implements RecaptchaServiceInterface
{
    private string $siteKey;
    private string $secretKey;
    private string $verifyUrl;
    private bool $enabled;

    public function __construct()
    {
        // Load from config file
        $configPath = __DIR__ . '/../../../../inc/config/recaptcha.php';
        
        if (file_exists($configPath)) {
            $config = require_once $configPath;
            $this->siteKey = $config['site_key'] ?? '';
            $this->secretKey = $config['secret_key'] ?? '';
            $this->verifyUrl = $config['verify_url'] ?? 'https://www.google.com/recaptcha/api/siteverify';
        } else {
            // Fallback if config doesn't exist
            $this->siteKey = '';
            $this->secretKey = '';
            $this->verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
            error_log("reCAPTCHA config file not found at: " . $configPath);
        }
        
        $this->enabled = !empty($this->siteKey) && !empty($this->secretKey);
    }

    public function verify(string $token, ?string $remoteIp = null): bool
    {
        if (!$this->enabled) {
            return true;
        }

        if (empty($token)) {
            return false;
        }

        try {
            $response = $this->callGoogleApi($token, $remoteIp);
            return $this->parseResponse($response);
        } catch (\Exception $e) {
            error_log("reCAPTCHA verification error: " . $e->getMessage());
            return false;
        }
    }

    public function getSiteKey(): string
    {
        return $this->siteKey;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    private function callGoogleApi(string $token, ?string $remoteIp = null): array
    {
        $data = [
            'secret' => $this->secretKey,
            'response' => $token
        ];

        if ($remoteIp !== null) {
            $data['remoteip'] = $remoteIp;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->verifyUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException("cURL error: " . $error);
        }

        if ($httpCode !== 200) {
            throw new \RuntimeException("Google API returned HTTP {$httpCode}");
        }

        return json_decode($response, true) ?? [];
    }

    private function parseResponse(array $response): bool
    {
        if (isset($response['success']) && $response['success'] === true) {
            return true;
        }

        if (isset($response['error-codes']) && is_array($response['error-codes'])) {
            error_log("reCAPTCHA error codes: " . implode(', ', $response['error-codes']));
        }

        return false;
    }
}