# Translation System Documentation

## Overview

This application implements a three-layer translation system that automatically detects the user's locale based on:
1. **User Profile** (if logged in)
2. **Session/Cookie** (if manually set)
3. **Browser Accept-Language Header** (automatic detection)
4. **Default Locale** (fallback to English)

## Architecture

### Layer A: Middleware (Detection)
- **File**: `app/Http/Middleware/SetLocale.php`
- **Purpose**: Detects and sets the locale on every request
- **Priority Order**:
  1. User profile locale (if authenticated)
  2. Cookie/Session locale (if manually set)
  3. Browser Accept-Language header
  4. Config default locale

### Layer B: File Structure
Language files are stored in `lang/` directory:
- `lang/en.json` - Generic English
- `lang/en_US.json` - US English
- `lang/en_GB.json` - UK English
- `lang/es.json` - Spanish
- Additional languages can be added as needed

### Layer C: API Endpoints
- `GET /api/translations` - Get all translations for current locale (public)
- `PUT /api/translations/locale` - Update user's locale preference (protected)

## Usage

### Backend (Laravel)

#### Adding New Translations

1. Add translations to the appropriate JSON file in `lang/`:
```json
{
  "common": {
    "new_key": "New Translation"
  }
}
```

2. Use in controllers/services:
```php
use Illuminate\Support\Facades\Lang;

$message = Lang::get('common.new_key');
// or
$message = __('common.new_key');
```

### Frontend (Next.js)

#### Using Translations in Components

```tsx
"use client"

import { useTranslations } from "@/hooks/useTranslations"

export function MyComponent() {
  const { t, locale, updateLocale } = useTranslations()

  return (
    <div>
      <h1>{t("common.welcome")}</h1>
      <p>{t("dashboard.title")}</p>
      
      {/* Change locale */}
      <button onClick={() => updateLocale("es")}>
        Switch to Spanish
      </button>
    </div>
  )
}
```

#### Initialize Translations on App Startup

In your root layout or app component:

```tsx
"use client"

import { useEffect } from "react"
import { initTranslations } from "@/lib/translations"

export default function RootLayout({ children }) {
  useEffect(() => {
    initTranslations()
  }, [])

  return <>{children}</>
}
```

## Supported Locales

Currently supported locales:
- `en` - English (generic)
- `en_US` - US English
- `en_GB` - UK English
- `es` - Spanish
- `fr` - French (can be added)
- `de` - German (can be added)
- `sr` - Serbian (can be added)

## Adding New Languages

1. Create a new JSON file in `lang/` directory (e.g., `lang/fr.json`)
2. Copy structure from `lang/en.json` and translate values
3. Add locale to middleware's `getPreferredLanguage()` array
4. Add locale to validation in `TranslationController::updateLocale()`

## Database Schema

The `users` table includes a `locale` column to store user preferences:
- Type: `string(10)`
- Nullable: Yes
- Default: Uses browser detection

## Migration

Run the migration to add locale column:
```bash
php artisan migrate
```

## Testing

Test locale detection:
1. Set browser language preference
2. Make API request - should detect automatically
3. Manually set locale via API: `PUT /api/translations/locale` with `{"locale": "es"}`
4. Verify translations change accordingly
