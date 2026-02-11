<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GeoLocationService
{
    /**
     * Map country codes to locales
     */
    private const COUNTRY_LOCALE_MAP = [
        'RS' => 'sr', // Serbia -> Serbian
        'BA' => 'sr', // Bosnia and Herzegovina -> Serbian
        'ME' => 'sr', // Montenegro -> Serbian
        'US' => 'en_US',
        'GB' => 'en_GB',
        'ES' => 'es',
        'MX' => 'es',
        'AR' => 'es',
        'CO' => 'es',
        'CL' => 'es',
        'PE' => 'es',
        'FR' => 'fr',
        'DE' => 'de',
        'AT' => 'de',
        'CH' => 'de',
    ];

    /**
     * Get country code from IP address.
     * Uses free IP geolocation API with caching.
     *
     * @param string|null $ipAddress
     * @return string|null Country code (e.g., 'RS', 'US') or null if detection fails
     */
    public function getCountryFromIp(?string $ipAddress): ?string
    {
        if (!$ipAddress) {
            return null;
        }

        // Skip local/private IPs
        if ($this->isLocalIp($ipAddress)) {
            return null;
        }

        // Check cache first (cache for 24 hours)
        $cacheKey = "geo_country_{$ipAddress}";
        $cachedCountry = Cache::get($cacheKey);
        
        if ($cachedCountry !== null) {
            return $cachedCountry;
        }

        try {
            // Use ipapi.co free tier (1000 requests/day)
            // Alternative: ip-api.com (45 requests/minute)
            $response = Http::timeout(2)->get("https://ipapi.co/{$ipAddress}/country_code/");
            
            if ($response->successful()) {
                $countryCode = trim($response->body());
                
                // Validate country code (2 uppercase letters)
                if (preg_match('/^[A-Z]{2}$/', $countryCode)) {
                    // Cache for 24 hours
                    Cache::put($cacheKey, $countryCode, now()->addHours(24));
                    return $countryCode;
                }
            }
        } catch (\Exception $e) {
            // Log error but don't fail - just return null
            Log::debug("GeoLocation detection failed for IP {$ipAddress}: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Get locale based on country code.
     *
     * @param string|null $countryCode
     * @return string|null Locale code or null if no mapping exists
     */
    public function getLocaleFromCountry(?string $countryCode): ?string
    {
        if (!$countryCode) {
            return null;
        }

        return self::COUNTRY_LOCALE_MAP[$countryCode] ?? null;
    }

    /**
     * Detect locale from IP address.
     * Combines IP -> Country -> Locale mapping.
     *
     * @param string|null $ipAddress
     * @return string|null Locale code or null if detection fails
     */
    public function detectLocaleFromIp(?string $ipAddress): ?string
    {
        $countryCode = $this->getCountryFromIp($ipAddress);
        return $this->getLocaleFromCountry($countryCode);
    }

    /**
     * Check if IP is local/private.
     *
     * @param string $ip
     * @return bool
     */
    private function isLocalIp(string $ip): bool
    {
        // Check for localhost
        if ($ip === '127.0.0.1' || $ip === '::1' || $ip === 'localhost') {
            return true;
        }

        // Check for private IP ranges
        $privateRanges = [
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
        ];

        foreach ($privateRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP is in CIDR range.
     *
     * @param string $ip
     * @param string $range
     * @return bool
     */
    private function ipInRange(string $ip, string $range): bool
    {
        [$subnet, $mask] = explode('/', $range);
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - (int)$mask);
        
        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }
}
