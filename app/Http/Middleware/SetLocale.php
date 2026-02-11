<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use App\Services\GeoLocationService;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     * Detects locale in this order:
     * 1. User Profile (if logged in)
     * 2. Session/Cookie (if manually set)
     * 3. Country-based detection (IP geolocation) - NEW!
     * 4. Browser Accept-Language header
     * 5. Default locale from config
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = null;
        $detectedCountry = null;

        // Layer 1: Check user profile (if authenticated)
        if ($request->user() && $request->user()->locale) {
            $locale = $request->user()->locale;
        }
        
        // Layer 2: Check cookie (manual selection)
        // Note: API routes don't have sessions, so we only check cookies
        if (!$locale) {
            $locale = $request->cookie('locale');
        }

        // Layer 3: Detect from country/IP geolocation
        if (!$locale) {
            $geoService = new GeoLocationService();
            
            // For testing: Allow override via query parameter or header
            // Usage: ?test_country=RS or X-Test-Country: RS header
            $testCountry = $request->query('test_country') 
                        ?? $request->header('X-Test-Country')
                        ?? $request->header('x-test-country'); // Try lowercase too
            
            // Debug logging (remove in production)
            if ($testCountry) {
                \Log::debug("Test country detected: {$testCountry}");
            }
            
            if ($testCountry && in_array(strtoupper($testCountry), ['RS', 'US', 'GB', 'ES', 'FR', 'DE', 'BA', 'ME'])) {
                // Use test country code directly
                $testCountry = strtoupper($testCountry);
                $detectedCountry = $testCountry;
                $detectedLocale = $geoService->getLocaleFromCountry($testCountry);
                if ($detectedLocale) {
                    $locale = $detectedLocale;
                }
            } else {
                // Normal geolocation detection
                $ipAddress = $request->ip();
                $detectedCountry = $geoService->getCountryFromIp($ipAddress);
                $detectedLocale = $geoService->getLocaleFromCountry($detectedCountry);
                
                if ($detectedLocale) {
                    $locale = $detectedLocale;
                }
            }
        }

        // Layer 4: Detect from browser Accept-Language header
        if (!$locale) {
            $preferredLanguage = $request->getPreferredLanguage(['en', 'en_US', 'en_GB', 'es', 'fr', 'de', 'sr']);
            if ($preferredLanguage) {
                $locale = $preferredLanguage;
            }
        }

        // Layer 5: Fallback to config default
        if (!$locale) {
            $locale = config('app.locale', 'en');
        }

        // Normalize locale (handle regional variants)
        $locale = $this->normalizeLocale($locale);

        // Set the application locale
        App::setLocale($locale);

        // Store in request for API responses
        $request->attributes->set('locale', $locale);
        
        // Store detected country if available
        if (isset($detectedCountry)) {
            $request->attributes->set('detected_country', $detectedCountry);
        }

        return $next($request);
    }

    /**
     * Normalize locale to handle regional variants and fallbacks.
     * 
     * @param string $locale
     * @return string
     */
    private function normalizeLocale(string $locale): string
    {
        // Map common browser locales to our supported locales
        $localeMap = [
            'en-US' => 'en_US',
            'en-GB' => 'en_GB',
            'en' => 'en',
            'es-ES' => 'es',
            'es-MX' => 'es',
            'es' => 'es',
            'fr-FR' => 'fr',
            'fr' => 'fr',
            'de-DE' => 'de',
            'de' => 'de',
            'sr-RS' => 'sr',
            'sr' => 'sr',
        ];

        // Check direct mapping
        if (isset($localeMap[$locale])) {
            return $localeMap[$locale];
        }

        // Check if locale starts with a mapped prefix
        foreach ($localeMap as $key => $value) {
            if (str_starts_with($locale, $key)) {
                return $value;
            }
        }

        // Fallback: extract language code (e.g., 'en' from 'en-US')
        $languageCode = explode('-', $locale)[0];
        return $localeMap[$languageCode] ?? config('app.locale', 'en');
    }
}
