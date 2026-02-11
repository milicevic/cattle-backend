# Geolocation-Based Translation Detection

## Overview

The system now automatically detects the user's country based on their IP address and shows the appropriate translation. For example, users from Serbia will automatically see Serbian translations.

## How It Works

### Detection Priority Order

1. **User Profile** - If logged in and has a saved locale preference
2. **Manual Selection** - Cookie/Session (if user manually changed language)
3. **Country Detection** - IP geolocation (NEW!)
4. **Browser Language** - Accept-Language header
5. **Default** - Falls back to English

### Country-to-Locale Mapping

The system maps countries to locales:

| Country Code | Country | Locale |
|-------------|---------|--------|
| RS | Serbia | `sr` (Serbian) |
| BA | Bosnia and Herzegovina | `sr` (Serbian) |
| ME | Montenegro | `sr` (Serbian) |
| US | United States | `en_US` |
| GB | United Kingdom | `en_GB` |
| ES | Spain | `es` (Spanish) |
| MX | Mexico | `es` (Spanish) |
| FR | France | `fr` (French) |
| DE | Germany | `de` (German) |

### Example Flow

**User from Serbia:**
1. User visits site from Serbia (IP detected)
2. System detects country code: `RS`
3. Maps `RS` → `sr` (Serbian)
4. Loads Serbian translations automatically
5. User sees interface in Serbian

**User from Serbia but browser set to English:**
1. User visits site from Serbia
2. System detects country: `RS` → `sr`
3. Shows Serbian (country takes priority over browser language)
4. User can manually override via language selector

## Implementation Details

### GeoLocationService

Located at: `app/Services/GeoLocationService.php`

- Uses free IP geolocation API (ipapi.co)
- Caches results for 24 hours to reduce API calls
- Handles local/private IPs gracefully
- Maps country codes to locales

### SetLocale Middleware

Located at: `app/Http/Middleware/SetLocale.php`

- Detects locale in priority order
- Stores detected country in request attributes
- Available to all controllers via `$request->attributes->get('detected_country')`

## API Response

When fetching translations, the API now returns:

```json
{
  "locale": "sr",
  "detected_country": "RS",
  "translations": {
    "common": {
      "welcome": "Dobrodošli",
      ...
    }
  }
}
```

## Testing

### Test from Serbia

1. Use a VPN or proxy from Serbia
2. Visit the site - should automatically show Serbian
3. Check API response: `GET /api/translations`
   - Should return `"locale": "sr"` and `"detected_country": "RS"`

### Test Locally

For local development, the system skips geolocation for private IPs (127.0.0.1, 192.168.x.x, etc.) and falls back to browser language or default.

To test country detection locally:
1. Use a VPN/proxy from Serbia
2. Or manually set locale via API: `PUT /api/translations/locale` with `{"locale": "sr"}`

## Adding More Countries

To add more country-to-locale mappings, edit `app/Services/GeoLocationService.php`:

```php
private const COUNTRY_LOCALE_MAP = [
    'RS' => 'sr',  // Serbia
    'HR' => 'hr',  // Croatia (add Croatian translations)
    'SI' => 'sl',  // Slovenia (add Slovenian translations)
    // ... add more
];
```

Then create the corresponding translation file: `lang/hr.json`, `lang/sl.json`, etc.

## Performance

- **Caching**: Country detection results are cached for 24 hours per IP
- **Timeout**: API calls timeout after 2 seconds to prevent delays
- **Fallback**: If geolocation fails, system falls back to browser language
- **Local IPs**: Private/local IPs skip geolocation entirely

## Privacy

- Only country code is detected (not exact location)
- IP address is not stored, only cached temporarily
- Users can always override via manual language selection
- Complies with GDPR (no personal data stored)
