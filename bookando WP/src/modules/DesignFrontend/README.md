# DesignFrontend Modul

Das **DesignFrontend Modul** ermöglicht die Anzeige von Buchungsangeboten, Kundenportal und Mitarbeiterportal auf der Website - entweder via WordPress Shortcodes oder direkte SaaS-Links.

## Features

### 🎯 Shortcodes

#### 1. Angebote anzeigen
```php
[bookando_offers]
[bookando_offers category="driving"]
[bookando_offers tag="beginner"]
[bookando_offers ids="1,2,3"]
[bookando_offers layout="grid" columns="3"]
[bookando_offers featured="true"]
```

#### 2. Kundenportal
```php
[bookando_customer_portal]
[bookando_customer_portal theme="light"]
```

#### 3. Mitarbeiterportal
```php
[bookando_employee_portal]
[bookando_employee_portal theme="dark"]
```

#### 4. Buchungs-Widget
```php
[bookando_booking offer_id="123"]
[bookando_booking offer_id="123" offer_type="course"]
```

### 🔗 SaaS-Links (direkte URLs)

- **Kundenportal**: `https://example.com/bookando/portal/customer`
- **Mitarbeiterportal**: `https://example.com/bookando/portal/employee`
- **Angebote**: `https://example.com/bookando/offers/driving/beginner`
- **Buchung**: `https://example.com/bookando/booking/123`

### 🔐 Authentifizierung

Unterstützt drei Authentifizierungsmethoden:

1. **Email + Passwort**
2. **Google OAuth** (Google-Konto)
3. **Apple Sign In** (Apple ID)

## Datenbank-Schema

Das Modul erstellt folgende Tabellen:

- `wp_bookando_frontend_pages` - Custom Landing Pages
- `wp_bookando_frontend_shortcodes` - Shortcode-Konfigurationen
- `wp_bookando_frontend_auth_sessions` - Authentifizierungs-Sessions
- `wp_bookando_frontend_auth_providers` - OAuth Provider-Konfigurationen (Google, Apple)
- `wp_bookando_frontend_email_verifications` - Email-Verifizierungs-Tokens
- `wp_bookando_frontend_offer_displays` - Angebots-Anzeigeeinstellungen

## REST API Endpoints

### Öffentlich (keine Authentifizierung)

- `GET /bookando/v1/frontend/offers` - Alle Angebote
- `GET /bookando/v1/frontend/offers/{id}` - Einzelnes Angebot
- `POST /bookando/v1/frontend/auth/email/login` - Email-Login
- `POST /bookando/v1/frontend/auth/google/login` - Google-Login
- `POST /bookando/v1/frontend/auth/apple/login` - Apple-Login

### Authentifiziert (Customer)

- `GET /bookando/v1/frontend/portal/customer/bookings` - Meine Buchungen
- `GET /bookando/v1/frontend/portal/customer/invoices` - Meine Rechnungen
- `GET /bookando/v1/frontend/portal/customer/progress` - Mein Lernfortschritt
- `POST /bookando/v1/frontend/booking` - Neue Buchung erstellen

### Authentifiziert (Employee)

- `GET /bookando/v1/frontend/portal/employee/schedule` - Mein Terminplan
- `GET /bookando/v1/frontend/portal/employee/students` - Meine Schüler

## Integration mit anderen Modulen

Das DesignFrontend Modul integriert sich mit:

- **Appointments** - Buchungen und Terminplanung
- **Academy** - Kursangebote und Lernfortschritt
- **Finance** - Rechnungen und Zahlungen
- **Customers** - Kundenprofile

## OAuth Konfiguration

### Google OAuth

1. Google Cloud Console öffnen
2. OAuth 2.0 Client ID erstellen
3. Redirect URI: `https://your-site.com/bookando/auth/google/callback`
4. Client ID und Secret in Admin-Panel eingeben

### Apple Sign In

1. Apple Developer Account
2. App ID konfigurieren
3. Services aktivieren: "Sign in with Apple"
4. Client ID und Secret in Admin-Panel eingeben

## Lizenz

Dieses Modul erfordert einen **Professional Plan** oder höher.

## Entwicklung

### Frontend Components (Vue 3)

Die Frontend-Komponenten befinden sich in:
```
src/modules/DesignFrontend/assets/vue/
```

### Build

```bash
cd src/modules/DesignFrontend/assets/vue
npm install
npm run build
```

## Support

Bei Fragen: https://github.com/PatrikA5P/bookando-merge/issues
