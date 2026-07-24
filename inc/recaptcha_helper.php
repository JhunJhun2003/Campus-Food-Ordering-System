<?php
/**
 * reCAPTCHA Helper Functions
 * Reads configuration from inc/config/recaptcha.php
 */

/**
 * Get reCAPTCHA site key from config
 */
function get_recaptcha_site_key(): string
{
    static $siteKey = null;
    
    if ($siteKey === null) {
        $configPath = __DIR__ . '/config/recaptcha.php';
        
        if (file_exists($configPath)) {
            $config = include $configPath; // Use include instead of require_once
            $siteKey = $config['site_key'] ?? '';
        } else {
            $siteKey = '';
        }
    }
    
    return $siteKey;
}

/**
 * Get reCAPTCHA secret key from config
 */
function get_recaptcha_secret_key(): string
{
    static $secretKey = null;
    
    if ($secretKey === null) {
        $configPath = __DIR__ . '/config/recaptcha.php';
        
        if (file_exists($configPath)) {
            $config = include $configPath; // Use include instead of require_once
            $secretKey = $config['secret_key'] ?? '';
        } else {
            $secretKey = '';
        }
    }
    
    return $secretKey;
}

/**
 * Check if reCAPTCHA is enabled
 */
function is_recaptcha_enabled(): bool
{
    static $enabled = null;
    
    if ($enabled === null) {
        $configPath = __DIR__ . '/config/recaptcha.php';
        
        if (file_exists($configPath)) {
            $config = include $configPath;
            $siteKey = $config['site_key'] ?? '';
            $secretKey = $config['secret_key'] ?? '';
            $enabled = !empty($siteKey) && !empty($secretKey);
        } else {
            $enabled = false;
        }
    }
    
    return $enabled;
}

/**
 * Render reCAPTCHA widget HTML
 */
function render_recaptcha_widget(string $theme = 'light', string $size = 'normal'): string
{
    if (!is_recaptcha_enabled()) {
        return '<!-- reCAPTCHA is disabled -->';
    }
    
    $siteKey = get_recaptcha_site_key();
    
    if (empty($siteKey)) {
        return '<!-- reCAPTCHA site key is missing -->';
    }
    
    return sprintf(
        '<div class="g-recaptcha" data-sitekey="%s" data-theme="%s" data-size="%s"></div>',
        htmlspecialchars($siteKey),
        htmlspecialchars($theme),
        htmlspecialchars($size)
    );
}

/**
 * Get reCAPTCHA script tag
 */
function render_recaptcha_script(): string
{
    if (!is_recaptcha_enabled()) {
        return '';
    }
    
    return '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
}

/**
 * Get reCAPTCHA validation JavaScript
 */
function get_recaptcha_validation_js(): string
{
    if (!is_recaptcha_enabled()) {
        return '';
    }
    
    return <<<JS
<script>
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (typeof grecaptcha !== 'undefined') {
                const response = grecaptcha.getResponse();
                const errorEl = document.getElementById('captcha-error');
                if (!response || response.length === 0) {
                    e.preventDefault();
                    if (errorEl) {
                        errorEl.classList.remove('hidden');
                    }
                    return false;
                }
                if (errorEl) {
                    errorEl.classList.add('hidden');
                }
            }
            return true;
        });
    });
});
</script>
JS;
}