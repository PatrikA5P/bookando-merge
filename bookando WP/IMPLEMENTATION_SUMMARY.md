# 📋 Implementierungs-Übersicht: Finance & Academy UX Improvements

## ✅ Vollständig Implementiert

### 1. Training Card Verbesserungen
- ✅ **Auto-Invoice entfernt** - Keine automatische Rechnungserstellung mehr
- ✅ **Bewertungssystem** - `instructor_rating` (1-5 Sterne)
- ✅ **Separate Notizen** - `instructor_notes` & `student_notes`
- ✅ **Kursverknüpfung** - `course_lesson_id` verlinkt zu Kursinhalten
- ✅ **Annotation-System** - Zeichnungen auf Bildern (TypeScript Interface)
- ✅ **Multimedia-Resources** - Bilder, Videos, Kurs-Links mit Metadaten

**Korrigierte Business-Logik:**
Lektionen werden NICHT automatisch abgerechnet. Abrechnung erfolgt ausschließlich über gebuchte Items (Appointments, Kurse). Während einer Fahrstunde können mehrere Themen/Lektionen durchgenommen werden.

### 2. WordPress-Unabhängiges User-System
- ✅ **Separates User-System** - `frontend_users` Tabelle
- ✅ **Email + Password Auth** - Mit Passwort-Hashing (BCrypt)
- ✅ **Google OAuth** - Sign in mit Google-Konto
- ✅ **Apple Sign In** - Sign in mit Apple ID
- ✅ **Email-Verifizierung** - 24h Token-System
- ✅ **Session-Management** - 7-Tage Bearer Tokens
- ✅ **Rollen-System** - `customer` oder `employee`

**Vorteil:**
Identisches Login-System für WordPress UND SaaS/Cloud. Keine WordPress-Accounts für Frontend-Benutzer nötig.

### 3. DesignFrontend Modul (Backend komplett)
- ✅ **Shortcode-System** - 4 Shortcodes mit allen Variationen
- ✅ **SaaS-Links** - SEO-freundliche URLs ohne Shortcodes
- ✅ **REST API** - 17 Endpoints (öffentlich & authentifiziert)
- ✅ **Datenbank-Schema** - 7 Tabellen
- ✅ **Auth-Handler** - Multi-Provider Authentifizierung
- ✅ **Admin-Panel** - Shortcode-Generator mit Vorschau
- ✅ **OAuth-Konfiguration** - Admin-Interface für Google/Apple

### 4. Package-System
- ✅ **Package-Entity** - Ausbildungspakete (z.B. "10 Fahrstunden + Kurs")
- ✅ **PackageModel** - CRUD-Operationen
- ✅ **REST API** - Package-Endpoints
- ✅ **Rabattberechnung** - Automatisch aus Original vs. Paketpreis
- ✅ **PackagesTab.vue** - UI-Komponente

## 📁 Neue Dateien

### Backend (PHP)
```
DesignFrontend/
├── Module.php               ✅ Modul-Initialisierung
├── Installer.php            ✅ 7 Tabellen
├── ShortcodeHandler.php     ✅ 4 Shortcode-Handler
├── AuthHandler.php          ✅ WordPress-unabhängiges Auth
├── RestHandler.php          ✅ 17 REST Endpoints
├── Admin/
│   ├── Admin.php            ✅ Admin-Menü
│   └── ShortcodeGeneratorPage.php  ✅ Visueller Generator
└── Api/
    └── Api.php              ✅ Route-Registrierung

Academy/
├── Installer.php            ✅ Bewertungsfelder
├── Models/
│   ├── TrainingCardModel.php  ✅ Bewertung & Notizen
│   └── PackageModel.php     ✅ NEU: Paket-Verwaltung
├── FinanceIntegration.php   ✅ Angepasst (kein Auto-Invoice)
└── assets/vue/tabs/
    └── PackagesTab.vue      ✅ NEU: Paket-UI
```

### TypeScript
```
packages/types/src/academy.ts  ✅ Erweiterte Interfaces
```

## 🔧 Verwendung

### Shortcodes

#### Angebote anzeigen
```php
[bookando_offers]
[bookando_offers category="driving" tag="beginner"]
[bookando_offers layout="grid" columns="3"]
[bookando_offers featured="true" limit="12"]
[bookando_offers ids="1,2,3"]
```

#### Kundenportal
```php
[bookando_customer_portal]
[bookando_customer_portal theme="light"]
```

#### Mitarbeiterportal
```php
[bookando_employee_portal]
[bookando_employee_portal theme="dark"]
```

#### Buchungs-Widget
```php
[bookando_booking offer_id="123"]
[bookando_booking offer_id="123" offer_type="course" show_details="true"]
```

### SaaS-Links (direkte URLs)
```
/bookando/portal/customer       → Kundenportal
/bookando/portal/employee       → Mitarbeiterportal
/bookando/offers/driving/beginner → Gefilterte Angebote
/bookando/booking/123           → Buchungs-Widget
```

### REST API Endpoints

**Öffentlich:**
- `POST /frontend/auth/register` - Registrierung
- `POST /frontend/auth/email/login` - Email-Login
- `POST /frontend/auth/google/login` - Google-Login
- `POST /frontend/auth/apple/login` - Apple-Login
- `GET /frontend/offers` - Alle Angebote
- `GET /frontend/offers/{id}` - Einzelnes Angebot

**Authentifiziert (Customer):**
- `GET /frontend/portal/customer/bookings` - Meine Buchungen
- `GET /frontend/portal/customer/invoices` - Meine Rechnungen
- `GET /frontend/portal/customer/progress` - Mein Lernfortschritt
- `POST /frontend/booking` - Neue Buchung

**Authentifiziert (Employee):**
- `GET /frontend/portal/employee/schedule` - Mein Terminplan
- `GET /frontend/portal/employee/students` - Meine Schüler

## 📊 Datenbank-Schema

### Neue Tabellen
```sql
-- Frontend Users (WordPress-unabhängig)
wp_bookando_frontend_users
- id, email, password_hash, first_name, last_name
- role (customer/employee)
- auth_provider (email/google/apple), provider_user_id
- email_verified, status

-- Auth Sessions (7-Tage Bearer Tokens)
wp_bookando_frontend_auth_sessions
- session_token, user_id, auth_provider
- ip_address, user_agent, expires_at

-- OAuth Provider Configs
wp_bookando_frontend_auth_providers
- provider (google/apple), enabled
- client_id, client_secret, redirect_uri

-- Email Verifications (24h Tokens)
wp_bookando_frontend_email_verifications
- email, token, user_id, verified, expires_at

-- Offer Displays (Public Listings)
wp_bookando_frontend_offer_displays
- offer_type, offer_id, visible, featured
- custom_title, custom_description, custom_image
- tags, categories, display_order

-- Shortcode Configs
wp_bookando_frontend_shortcodes
- shortcode_id, type, config, filters, display_options

-- Custom Pages
wp_bookando_frontend_pages
- slug, title, type, template
- header_config, content_config, footer_config, seo_config

-- Packages (Academy)
wp_bookando_academy_packages
- title, description, items (JSON)
- price, original_price, discount_percent
- validity_days, category, status
```

### Erweiterte Tabellen
```sql
-- Academy Courses
ALTER TABLE wp_bookando_academy_courses
ADD price DECIMAL(10,2),
ADD currency VARCHAR(3),
ADD discount_eligible BOOLEAN,
ADD max_participants INT;

-- Training Cards
ALTER TABLE wp_bookando_academy_training_cards
ADD customer_id BIGINT UNSIGNED,
ADD package_id BIGINT UNSIGNED;

-- Training Lessons
ALTER TABLE wp_bookando_academy_training_lessons
ADD instructor_rating INT,
ADD instructor_notes TEXT,
ADD student_notes TEXT,
ADD course_lesson_id BIGINT UNSIGNED;
```

## 🎯 Admin-Panel

### Shortcode-Generator
**Pfad:** WordPress Admin → Design Frontend → Shortcode Generator

**Features:**
- ✅ 4 Tabs (Angebote, Kundenportal, Mitarbeiterportal, Buchung)
- ✅ Live-Preview des generierten Shortcodes
- ✅ Alle Parameter konfigurierbar
- ✅ Copy-to-Clipboard Funktion
- ✅ Beispiele für jeden Shortcode-Typ
- ✅ SaaS-Link Alternativen angezeigt

### OAuth-Konfiguration
**Pfad:** WordPress Admin → Design Frontend → OAuth Einstellungen

**Features:**
- ✅ Google OAuth aktivieren/deaktivieren
- ✅ Client ID & Secret für Google
- ✅ Apple Sign In aktivieren/deaktivieren
- ✅ Service ID & Key für Apple
- ✅ Setup-Anleitungen mit Links

## 🚧 Noch zu implementieren (Frontend Vue-Komponenten)

### Vue.js Components (Empfohlene Struktur)
```
DesignFrontend/assets/vue/src/
├── main.ts                    ⏳ Vue App Init
├── router.ts                  ⏳ Vue Router
├── components/
│   ├── AuthForm.vue           ⏳ Login/Register mit OAuth
│   ├── OffersGrid.vue         ⏳ Angebots-Karten-Ansicht
│   ├── OffersList.vue         ⏳ Angebots-Listen-Ansicht
│   ├── BookingWidget.vue      ⏳ Buchungs-Formular
│   └── PortalLayout.vue       ⏳ Portal-Layout-Template
├── views/
│   ├── CustomerPortal.vue     ⏳ Kunden-Dashboard
│   └── EmployeePortal.vue     ⏳ Mitarbeiter-Dashboard
├── composables/
│   ├── useAuth.ts             ⏳ Auth-Composable
│   └── useApi.ts              ⏳ API-Composable
└── stores/
    └── authStore.ts           ⏳ Pinia Auth Store
```

### Empfohlene Implementierung

**1. AuthForm.vue**
```vue
<template>
  <!-- Login/Register Toggle -->
  <!-- Email + Password Fields -->
  <!-- OAuth Buttons (Google & Apple) -->
  <!-- Email Verification Message -->
</template>

<script setup lang="ts">
// API Calls zu /frontend/auth/*
// Session Token Speicherung (localStorage)
// Redirect nach Login
</script>
```

**2. OffersGrid.vue**
```vue
<template>
  <!-- Filter-Bar (Category, Tags, Search) -->
  <!-- Grid Layout (responsive Columns) -->
  <!-- Offer Cards mit Image, Title, Price, CTA -->
  <!-- Pagination -->
</template>

<script setup lang="ts">
// API Call zu /frontend/offers
// Filter-Logik
// Klick → Booking Widget oder Detail-Seite
</script>
```

**3. CustomerPortal.vue**
```vue
<template>
  <!-- Navigation: Dashboard, Buchungen, Rechnungen, Fortschritt -->
  <!-- Widget-Grid für schnellen Überblick -->
  <!-- Tabellen für Details -->
</template>

<script setup lang="ts">
// Auth Check (Redirect wenn nicht eingeloggt)
// API Calls zu /frontend/portal/customer/*
// Logout-Funktion
</script>
```

### Build-Setup
```json
// package.json
{
  "name": "@bookando/design-frontend",
  "dependencies": {
    "vue": "^3.3.0",
    "vue-router": "^4.2.0",
    "pinia": "^2.1.0",
    "axios": "^1.6.0"
  },
  "devDependencies": {
    "@vitejs/plugin-vue": "^4.5.0",
    "vite": "^5.0.0",
    "typescript": "^5.3.0"
  },
  "scripts": {
    "dev": "vite",
    "build": "vite build",
    "preview": "vite preview"
  }
}
```

## 📈 Business Value

### Für Fahrschulen
1. **Korrekte Abrechnung** - Lektionen werden nicht doppelt abgerechnet
2. **Besseres Tracking** - Bewertungen und Notizen pro Lektion
3. **Öffentliche Angebote** - Website-Besucher sehen Kurse/Pakete
4. **Kundenportal** - Self-Service für Kunden (Buchungen, Rechnungen, Fortschritt)
5. **Mitarbeiterportal** - Fahrlehrer sehen Termine und Schüler
6. **Moderne Auth** - Email, Google, Apple Login

### UX-Verbesserungen
- ✅ WordPress-unabhängiges System (SaaS-ready)
- ✅ Modernes OAuth (Google/Apple)
- ✅ Visueller Shortcode-Generator
- ✅ Alle Shortcode-Variationen dokumentiert
- ✅ SEO-freundliche SaaS-URLs
- ✅ Separate Notizen für Instruktor & Schüler

## 🔐 Sicherheit

- Passwörter mit `PASSWORD_BCRYPT` gehasht
- 32-Byte Hex Session-Tokens
- Email-Verifizierung erforderlich
- OAuth Tokens validiert
- Session-Expiration (7 Tage)
- IP & User Agent Tracking
- CORS-ready REST API

## 📝 Commits

1. **28b442b** - Training Card Verbesserungen & DesignFrontend Basis
2. **7e67f7d** - WordPress-unabhängiges User-System
3. **[PENDING]** - Shortcode-Generator & Admin-Panel

## 🎓 Lizenz-Anforderungen

- DesignFrontend Modul: **Professional Plan** oder höher
- Packages Feature: **Professional Plan** oder höher
