<?php
/**
 * Settings Helper Functions
 * Provides global access to application settings with caching
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\User\Infrastructure\Repositories\UserRepository;

/**
 * Get a setting value with optional default
 * Caches all settings in static variable for performance
 */
function app_setting(string $key, $default = null)
{
    static $settings = null;
    
    if ($settings === null) {
        try {
            $repo = new UserRepository();
            $settings = $repo->getAllSettings();
        } catch (\Exception $e) {
            error_log("Failed to load settings: " . $e->getMessage());
            $settings = [];
        }
    }
    
    return $settings[$key] ?? $default;
}

/**
 * Get currency symbol from currency code
 */
function app_currency_symbol(): string
{
    $currencyCode = app_setting('currency', 'USD');
    return get_currency_symbol($currencyCode);
}

/**
 * Format price with currency symbol
 */
function app_format_price(float $price): string
{
    $symbol = app_currency_symbol();
    $decimalPlaces = (int) app_setting('currency_decimal_places', 2);
    $decimalSeparator = app_setting('currency_decimal_separator', '.');
    $thousandsSeparator = app_setting('currency_thousands_separator', ',');
    
    return $symbol . number_format($price, $decimalPlaces, $decimalSeparator, $thousandsSeparator);
}

/**
 * Get site name
 */
function app_site_name(): string
{
    return app_setting('site_name', 'FOODIE');
}

/**
 * Get site email
 */
function app_site_email(): string
{
    return app_setting('site_email', 'admin@foodie.com');
}

/**
 * Get site phone
 */
function app_site_phone(): string
{
    return app_setting('site_phone', '');
}

/**
 * Get timezone
 */
function app_timezone(): string
{
    return app_setting('timezone', 'Asia/Manila');
}

/**
 * Check if maintenance mode is enabled
 */
function app_maintenance_mode(): bool
{
    return (bool) app_setting('maintenance_mode', 0);
}

/**
 * Get preparation time
 */
function app_preparation_time(): int
{
    return (int) app_setting('preparation_time', 15);
}

/**
 * Get currency symbol from currency code
 */
function get_currency_symbol(string $currencyCode): string
{
    $symbols = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'JPY' => '¥',
        'MMK' => 'K',
        'PHP' => '₱',
        'SGD' => 'S$',
        'MYR' => 'RM',
        'THB' => '฿',
        'VND' => '₫',
        'IDR' => 'Rp',
        'AUD' => 'A$',
        'CAD' => 'C$',
        'CHF' => 'CHF',
        'CNY' => '¥',
        'HKD' => 'HK$',
        'KRW' => '₩',
        'NZD' => 'NZ$',
        'SEK' => 'kr',
        'NOK' => 'kr',
        'DKK' => 'kr',
        'ZAR' => 'R',
        'BRL' => 'R$',
        'INR' => '₹',
        'RUB' => '₽',
    ];
    
    return $symbols[strtoupper($currencyCode)] ?? '$';
}