<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;

class TranslationController extends Controller
{
    /**
     * Get all translations for the current locale.
     * Returns translations based on the locale detected by SetLocale middleware.
     */
    public function index(Request $request): JsonResponse
    {
        $locale = $request->attributes->get('locale') ?? App::getLocale();
        $detectedCountry = $request->attributes->get('detected_country');
        
        // Load translations from JSON file
        $translations = $this->loadTranslations($locale);
        
        return response()->json([
            'locale' => $locale,
            'detected_country' => $detectedCountry,
            'translations' => $translations,
        ]);
    }

    /**
     * Update user's locale preference.
     */
    public function updateLocale(Request $request): JsonResponse
    {
        $request->validate([
            'locale' => 'required|string|in:en,en_US,en_GB,es,fr,de,sr',
        ]);

        $user = $request->user();
        
        if ($user) {
            $user->locale = $request->locale;
            $user->save();
        }

        // Set cookie for guest users
        $cookie = cookie('locale', $request->locale, 60 * 24 * 365); // 1 year

        return response()->json([
            'message' => 'Locale updated successfully',
            'locale' => $request->locale,
        ])->cookie($cookie);
    }

    /**
     * Load translations from JSON file with fallback support.
     * 
     * @param string $locale
     * @return array
     */
    private function loadTranslations(string $locale): array
    {
        $langPath = base_path('lang');
        $translations = [];

        // Try to load the specific locale file
        $localeFile = "{$langPath}/{$locale}.json";
        
        if (File::exists($localeFile)) {
            $content = File::get($localeFile);
            $translations = json_decode($content, true) ?? [];
        } else {
            // Fallback to base language (e.g., en_GB -> en)
            $baseLocale = explode('_', $locale)[0];
            $baseFile = "{$langPath}/{$baseLocale}.json";
            
            if (File::exists($baseFile)) {
                $content = File::get($baseFile);
                $translations = json_decode($content, true) ?? [];
            }
        }

        // Final fallback to English
        if (empty($translations)) {
            $fallbackFile = "{$langPath}/en.json";
            if (File::exists($fallbackFile)) {
                $content = File::get($fallbackFile);
                $translations = json_decode($content, true) ?? [];
            }
        }

        return $translations;
    }
}
