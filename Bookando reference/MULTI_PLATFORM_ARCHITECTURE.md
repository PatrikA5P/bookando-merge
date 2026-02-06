# Bookando - Multi-Platform Architektur (SaaS + WordPress + Mobile)

## 1. Plattform-Übersicht

```
┌─────────────────────────────────────────────────────────────────┐
│                    BOOKANDO ECOSYSTEM                            │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────┐  ┌─────────────────────┐  ┌──────────────┐
│   SaaS Cloud        │  │  WordPress Plugin   │  │  Mobile Apps │
│   (Primary)         │  │  (Website Booking)  │  │  (iOS/Android│
└──────────┬──────────┘  └──────────┬──────────┘  └──────┬───────┘
           │                        │                     │
           └────────────────────────┼─────────────────────┘
                                    │
                          ┌─────────▼─────────┐
                          │   Central API     │
                          │   (Single Source  │
                          │    of Truth)      │
                          └─────────┬─────────┘
                                    │
                          ┌─────────▼─────────┐
                          │   PostgreSQL      │
                          │   (Shared DB)     │
                          └───────────────────┘

┌──────────────────────────────────────────────────────────────────┐
│                         ACCESS POINTS                             │
├────────────────┬─────────────────┬─────────────────┬─────────────┤
│ Admin Panel    │ Employee Portal │ Customer Portal │ Public Site │
│ (Desktop/Web)  │ (Mobile-First)  │ (Mobile-First)  │ (WordPress) │
└────────────────┴─────────────────┴─────────────────┴─────────────┘
```

---

## 2. Architektur-Komponenten

### 2.1 Central API (Core Backend)

**Technologie:** Node.js + Express + TypeScript + Prisma

**Rolle:** Single Source of Truth für alle Plattformen
- Authentifizierung (JWT)
- Business Logic
- Data Persistence
- WebSocket (Real-time)

**Endpoints:**
- REST API für CRUD
- GraphQL (optional, für Mobile Apps)
- WebSocket für Live-Updates
- Webhooks für WordPress

```typescript
// Central API Structure
backend/
├── src/
│   ├── api/
│   │   ├── rest/           # REST endpoints
│   │   ├── graphql/        # GraphQL schema (optional)
│   │   └── websocket/      # WebSocket handlers
│   ├── services/
│   │   ├── auth/
│   │   ├── booking/
│   │   ├── course/
│   │   ├── finance/
│   │   └── wordpress/      # WordPress sync service
│   ├── models/             # Prisma models
│   ├── middleware/
│   │   ├── auth.ts
│   │   ├── license.ts
│   │   └── rateLimit.ts
│   ├── jobs/               # Background jobs (BullMQ)
│   │   ├── emailQueue.ts
│   │   ├── smsQueue.ts
│   │   └── wordpressSync.ts
│   └── utils/
└── prisma/
    └── schema.prisma
```

---

### 2.2 SaaS Cloud Platform (Admin Panel)

**Technologie:** React + TypeScript + Vite (bereits vorhanden)

**Zielgruppe:** Fahrschul-Administratoren (Desktop primär, Mobile-fähig)

**Features:**
- Alle 14 Module vollständig
- Desktop-optimiert, aber Responsive
- PWA-fähig für Mobile-Zugriff
- Offline-Fähigkeit (Service Worker)

**Deployment:** Vercel Edge

```
bookando-admin/  (aktuelles Frontend)
├── modules/     # 14 Module
├── components/
├── context/
└── utils/
```

---

### 2.3 Employee Portal (Mobile-First Web App)

**Technologie:** React Native Web oder Flutter Web

**Zielgruppe:** Fahrlehrer, Instruktoren (Mobile primär)

**Features:**
- Mein Terminkalender (Heute, Diese Woche)
- Kunden-Details (Schnellansicht)
- Time Tracking (Clock In/Out)
- Abwesenheiten beantragen
- Notifications
- Offline-Modus (für unterwegs)

**Screens:**
```
1. Login
2. Dashboard (Meine heutigen Termine)
3. Kalender (Woche/Monat)
4. Appointment Details
5. Customer Quick View
6. Time Tracking
7. Profile/Settings
```

**Deployment:** Vercel oder als subdomain (employees.bookando.ch)

---

### 2.4 Customer Portal (Mobile-First Web App)

**Technologie:** React Native Web oder Flutter Web

**Zielgruppe:** Fahrschüler, Kunden (Mobile primär)

**Features:**
- Meine Buchungen
- Kurse buchen
- Rechnungen einsehen
- Zahlungen
- Education Card (Fortschritt)
- Notifications
- Support Chat

**Screens:**
```
1. Login/Register
2. Dashboard (Meine Buchungen, Nächste Termine)
3. Kurs-Katalog
4. Booking Flow (3-4 Steps)
5. Meine Kurse (Fortschritt)
6. Rechnungen
7. Zahlungen
8. Profil/Settings
```

**Deployment:** Vercel oder als subdomain (my.bookando.ch)

---

### 2.5 WordPress Plugin

**Technologie:** PHP + JavaScript (React Gutenberg Blocks)

**Zielgruppe:** Fahrschul-Website-Besucher (Öffentlich)

**Features:**
- Kurs-Katalog anzeigen (Shortcode/Block)
- Booking-Formular (Shortcode/Block)
- Preise anzeigen
- Verfügbare Termine
- **Synchronisation mit Central API**

**Plugin-Struktur:**
```
bookando-wordpress-plugin/
├── bookando.php              # Main plugin file
├── includes/
│   ├── class-bookando.php
│   ├── class-api-client.php  # Kommunikation mit Central API
│   ├── class-shortcodes.php
│   └── class-gutenberg-blocks.php
├── admin/                     # WordPress Admin Settings
│   ├── settings.php
│   └── sync.php
├── public/                    # Frontend Display
│   ├── booking-form.php
│   ├── course-catalog.php
│   └── assets/
│       ├── css/
│       └── js/
└── assets/
    └── blocks/               # Gutenberg Blocks (React)
        ├── course-catalog/
        └── booking-form/
```

**Synchronisation:**
```php
// WordPress calls Central API
class Bookando_API_Client {
    private $api_url;
    private $api_key;

    public function get_courses() {
        $response = wp_remote_get($this->api_url . '/api/courses', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'X-WordPress-Site' => get_site_url()
            ]
        ]);
        return json_decode(wp_remote_retrieve_body($response));
    }

    public function create_booking($data) {
        $response = wp_remote_post($this->api_url . '/api/bookings', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($data)
        ]);
        return json_decode(wp_remote_retrieve_body($response));
    }
}
```

**Webhooks (Central API → WordPress):**
```typescript
// backend/services/wordpress/webhooks.ts
export async function notifyWordPress(event: string, data: any) {
    const wpSites = await prisma.wordPressSite.findMany({
        where: { webhooksEnabled: true }
    });

    for (const site of wpSites) {
        await axios.post(site.webhookUrl, {
            event,
            data,
            timestamp: new Date().toISOString()
        }, {
            headers: {
                'X-Bookando-Signature': generateSignature(data, site.secret)
            }
        });
    }
}

// WordPress empfängt Webhook
add_action('rest_api_init', function() {
    register_rest_route('bookando/v1', '/webhook', [
        'methods' => 'POST',
        'callback' => 'bookando_handle_webhook'
    ]);
});

function bookando_handle_webhook($request) {
    $signature = $request->get_header('X-Bookando-Signature');
    // Verify signature
    $event = $request->get_param('event');
    $data = $request->get_param('data');

    switch($event) {
        case 'booking.created':
            // Update local cache
            break;
        case 'course.updated':
            // Invalidate cache
            break;
    }
}
```

---

### 2.6 Mobile Apps (iOS + Android)

**Technologie:** React Native oder Flutter

**Empfehlung:** React Native (TypeScript-Sharing mit Web)

**Zwei Apps:**
1. **Bookando Employee** (für Mitarbeiter)
2. **Bookando** (für Kunden)

**Shared Features:**
- Native Navigation
- Push Notifications (Firebase)
- Offline-Modus (AsyncStorage + SQLite)
- Biometric Login (Face ID, Fingerprint)
- Camera (für Dokumente, z.B. Führerschein-Scan)
- Calendar Integration (iOS/Android Calendar)

**App-Struktur:**
```
bookando-mobile/
├── apps/
│   ├── employee/           # Employee App
│   │   ├── src/
│   │   ├── ios/
│   │   └── android/
│   └── customer/           # Customer App
│       ├── src/
│       ├── ios/
│       └── android/
├── packages/               # Shared code
│   ├── components/         # Shared UI components
│   ├── api/               # API client
│   ├── types/             # Shared TypeScript types
│   └── utils/
└── package.json
```

**API Client (Shared):**
```typescript
// packages/api/client.ts
import axios from 'axios';
import AsyncStorage from '@react-native-async-storage/async-storage';

class BookandoAPI {
    private baseURL = 'https://api.bookando.ch';

    async login(email: string, password: string) {
        const { data } = await axios.post(`${this.baseURL}/auth/login`, {
            email,
            password,
            platform: 'mobile'
        });

        await AsyncStorage.setItem('auth_token', data.token);
        await AsyncStorage.setItem('refresh_token', data.refreshToken);

        return data;
    }

    async getBookings() {
        const token = await AsyncStorage.getItem('auth_token');
        const { data } = await axios.get(`${this.baseURL}/bookings`, {
            headers: { Authorization: `Bearer ${token}` }
        });
        return data;
    }
}
```

**Push Notifications:**
```typescript
// packages/utils/notifications.ts
import messaging from '@react-native-firebase/messaging';

export async function requestPermission() {
    const authStatus = await messaging().requestPermission();
    const enabled =
        authStatus === messaging.AuthorizationStatus.AUTHORIZED ||
        authStatus === messaging.AuthorizationStatus.PROVISIONAL;

    if (enabled) {
        const fcmToken = await messaging().getToken();
        // Send token to backend
        await api.registerDeviceToken(fcmToken);
    }
}

// Backend sends notification
import admin from 'firebase-admin';

export async function sendPushNotification(userId: string, notification: {
    title: string;
    body: string;
    data?: any;
}) {
    const device = await prisma.device.findFirst({
        where: { userId }
    });

    if (device?.fcmToken) {
        await admin.messaging().send({
            token: device.fcmToken,
            notification: {
                title: notification.title,
                body: notification.body
            },
            data: notification.data
        });
    }
}
```

---

## 3. Daten-Synchronisation

### 3.1 WordPress ↔ Central API

**Strategie:** API-First mit WordPress als Display Layer

**WordPress speichert NICHTS, sondern:**
- Cached Daten (Transients, 15 Minuten)
- Proxied alle Anfragen zur Central API
- Empfängt Webhooks für Cache-Invalidation

```php
// WordPress Caching
function bookando_get_courses() {
    $cache_key = 'bookando_courses';
    $courses = get_transient($cache_key);

    if (false === $courses) {
        $api = new Bookando_API_Client();
        $courses = $api->get_courses();
        set_transient($cache_key, $courses, 15 * MINUTE_IN_SECONDS);
    }

    return $courses;
}

// Cache invalidation via webhook
function bookando_handle_webhook($request) {
    $event = $request->get_param('event');

    if ($event === 'course.updated' || $event === 'course.created') {
        delete_transient('bookando_courses');
    }
}
```

**WordPress Settings (Admin Panel):**
```
Bookando Settings
├── API Configuration
│   ├── API URL: https://api.bookando.ch
│   ├── API Key: [Generate in Bookando Admin]
│   └── Organization ID: org_123456
├── Synchronization
│   ├── Cache Duration: 15 minutes
│   ├── Webhooks Enabled: ✓
│   └── Webhook URL: https://yoursite.com/wp-json/bookando/v1/webhook
└── Display Settings
    ├── Show Prices: ✓
    ├── Show Availability: ✓
    └── Booking Redirect: Customer Portal / Stay on Page
```

---

### 3.2 Mobile Apps ↔ Central API

**Strategie:** Real-time mit Offline-Support

**Offline-First Architecture:**
```typescript
// Offline Queue
class OfflineQueue {
    private queue: QueueItem[] = [];

    async addToQueue(action: 'create' | 'update' | 'delete', endpoint: string, data: any) {
        this.queue.push({ action, endpoint, data, timestamp: Date.now() });
        await AsyncStorage.setItem('offline_queue', JSON.stringify(this.queue));
    }

    async sync() {
        const isOnline = await NetInfo.fetch().then(state => state.isConnected);

        if (isOnline) {
            for (const item of this.queue) {
                try {
                    await api.request(item.endpoint, item.action, item.data);
                    // Remove from queue
                    this.queue = this.queue.filter(i => i !== item);
                } catch (error) {
                    console.error('Sync failed:', error);
                }
            }
            await AsyncStorage.setItem('offline_queue', JSON.stringify(this.queue));
        }
    }
}
```

**Real-time Updates (WebSocket):**
```typescript
// Mobile app connects to WebSocket
import io from 'socket.io-client';

const socket = io('https://api.bookando.ch', {
    auth: { token: await AsyncStorage.getItem('auth_token') }
});

socket.on('booking.created', (booking) => {
    // Update local state
    dispatch({ type: 'ADD_BOOKING', payload: booking });
    // Show notification
    showNotification('New booking received!');
});

socket.on('booking.updated', (booking) => {
    dispatch({ type: 'UPDATE_BOOKING', payload: booking });
});
```

---

## 4. Deployment-Strategie

### 4.1 SaaS Cloud (Multi-Tenant)

**Hosting:** Vercel (Frontend) + Railway (Backend)

**Subdomains:**
```
https://app.bookando.ch        → Admin Panel (SaaS)
https://my.bookando.ch         → Customer Portal
https://team.bookando.ch       → Employee Portal
https://api.bookando.ch        → Central API
https://{org}.bookando.ch      → Custom Subdomain (Enterprise)
```

**Multi-Tenant Implementation:**
```typescript
// Middleware: Tenant Detection
app.use((req, res, next) => {
    const subdomain = req.hostname.split('.')[0];

    if (subdomain !== 'api' && subdomain !== 'app' && subdomain !== 'www') {
        // Custom subdomain = Organization
        req.organization = await prisma.organization.findUnique({
            where: { subdomain }
        });
    } else {
        // Extract from JWT or header
        const token = req.headers.authorization?.split(' ')[1];
        const decoded = jwt.verify(token, JWT_SECRET);
        req.organization = decoded.organizationId;
    }

    next();
});

// All DB queries scoped to organization
app.get('/api/customers', async (req, res) => {
    const customers = await prisma.customer.findMany({
        where: { organizationId: req.organization.id }
    });
    res.json(customers);
});
```

---

### 4.2 WordPress Plugin

**Distribution:**
1. **WordPress Plugin Directory** (wordpress.org/plugins)
2. **Download von Bookando Website**
3. **Auto-Update via WordPress**

**Installation:**
```bash
# Via WordPress Admin
Plugins → Add New → Search "Bookando"

# Via Upload
Plugins → Add New → Upload Plugin → bookando.zip

# Via FTP
wp-content/plugins/bookando/
```

**Activation:**
```
1. Install & Activate Plugin
2. Bookando Settings erscheint im Admin Menu
3. Connect to Bookando Cloud:
   - Enter API Key (from Bookando Admin)
   - Enter Organization ID
   - Test Connection
4. Configure Display Settings
5. Add Shortcodes/Blocks to Pages
```

**Shortcodes:**
```
[bookando_courses]
[bookando_booking_form service_id="123"]
[bookando_calendar]
[bookando_pricing]
```

**Gutenberg Blocks:**
```jsx
// blocks/course-catalog/index.js
import { registerBlockType } from '@wordpress/blocks';

registerBlockType('bookando/course-catalog', {
    title: 'Bookando Course Catalog',
    icon: 'book',
    category: 'widgets',
    attributes: {
        categoryFilter: { type: 'string', default: '' },
        layout: { type: 'string', default: 'grid' }
    },
    edit: ({ attributes, setAttributes }) => {
        return (
            <div className="bookando-block-editor">
                {/* Block Editor UI */}
                <SelectControl
                    label="Category Filter"
                    value={attributes.categoryFilter}
                    onChange={(val) => setAttributes({ categoryFilter: val })}
                />
            </div>
        );
    },
    save: ({ attributes }) => {
        // Render shortcode (PHP will handle actual display)
        return `[bookando_courses category="${attributes.categoryFilter}"]`;
    }
});
```

---

### 4.3 Mobile Apps

**Distribution:**
1. **Google Play Store** (Android)
2. **Apple App Store** (iOS)

**App Store Metadata:**
```yaml
App Name: Bookando (Customer) / Bookando Employee
Category: Business / Productivity
Price: Free (In-App Purchases for Premium)
Description: |
  Bookando ist die All-in-One Buchungs- und Kursmanagement-App für
  Fahrschulen, Wellness-Center und Bildungseinrichtungen.

  Features:
  - Kurse buchen
  - Termine einsehen
  - Rechnungen bezahlen
  - Fortschritt tracken
  - Push-Benachrichtigungen

Screenshots:
  - Login Screen
  - Dashboard
  - Course Catalog
  - Booking Flow
  - My Courses
```

**Build & Deploy:**
```bash
# iOS (requires Mac + Xcode)
cd apps/customer
npx react-native run-ios
# Build for App Store
xcodebuild -workspace ios/Bookando.xcworkspace \
           -scheme Bookando \
           -configuration Release \
           -archivePath build/Bookando.xcarchive \
           archive

# Android
cd apps/customer
npx react-native run-android
# Build for Play Store
cd android
./gradlew bundleRelease
```

**CI/CD (GitHub Actions):**
```yaml
# .github/workflows/mobile-deploy.yml
name: Deploy Mobile Apps

on:
  push:
    branches: [main]
    paths:
      - 'bookando-mobile/**'

jobs:
  deploy-ios:
    runs-on: macos-latest
    steps:
      - uses: actions/checkout@v2
      - name: Build iOS
        run: |
          cd bookando-mobile/apps/customer
          npx react-native bundle --platform ios
          xcodebuild archive
      - name: Upload to App Store
        uses: apple-actions/upload-testflight-build@v1

  deploy-android:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Build Android
        run: |
          cd bookando-mobile/apps/customer/android
          ./gradlew bundleRelease
      - name: Upload to Play Store
        uses: r0adkll/upload-google-play@v1
```

---

## 5. Lizenzierung: Multi-Platform

### 5.1 License Enforcement

**Pro Plattform:**
```typescript
interface License {
    platforms: {
        saas: boolean;        // Admin Panel
        wordpress: boolean;   // WordPress Plugin
        mobileApps: boolean;  // iOS/Android Apps
        customerPortal: boolean;
        employeePortal: boolean;
    };
}

// Beispiel: Professional Tier
{
    tier: 'professional',
    platforms: {
        saas: true,
        wordpress: true,
        mobileApps: true,
        customerPortal: true,
        employeePortal: true
    }
}

// Beispiel: Starter Tier
{
    tier: 'starter',
    platforms: {
        saas: true,
        wordpress: false,    // Nur in Professional+
        mobileApps: false,   // Nur in Professional+
        customerPortal: true,
        employeePortal: false
    }
}
```

**Enforcement:**
```typescript
// WordPress Plugin Check
add_action('admin_init', function() {
    $license = bookando_get_license();

    if (!$license->platforms->wordpress) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('WordPress Plugin requires Professional or Enterprise license.');
    }
});

// Mobile App Check
async function checkLicense() {
    const { data } = await api.get('/auth/license');

    if (!data.platforms.mobileApps) {
        Alert.alert(
            'Upgrade Required',
            'Mobile apps require Professional or Enterprise license.',
            [{ text: 'Upgrade Now', onPress: () => Linking.openURL('https://bookando.ch/upgrade') }]
        );
        // Logout user
        await logout();
    }
}
```

---

## 6. Update-Mechanismus

### 6.1 SaaS (Automatisch)

**Vercel Auto-Deploy:**
- Git Push → Vercel Deploy → Live in 2 Minuten
- Zero-Downtime Deployment
- Rollback in 1 Klick

**Database Migrations:**
```bash
# Prisma Migrations
npx prisma migrate deploy  # In Production

# Background Job: Notify all connected clients
WebSocket.broadcast({
    type: 'UPDATE_AVAILABLE',
    version: '2.0.0',
    message: 'New features available! Refresh to update.'
});
```

---

### 6.2 WordPress Plugin (WP Auto-Update)

**WordPress Update Mechanism:**
```php
// bookando.php
/*
Plugin Name: Bookando
Version: 1.2.0
*/

// Enable auto-updates
add_filter('auto_update_plugin', function($update, $item) {
    if ($item->slug === 'bookando') {
        return true; // Auto-update enabled
    }
    return $update;
}, 10, 2);

// Update notification
add_action('in_plugin_update_message-bookando/bookando.php', function($data) {
    echo '<br>New version available! Auto-update enabled.';
});
```

**Custom Update Server (für Premium):**
```php
// Fetch updates from Bookando server (not wordpress.org)
add_filter('pre_set_site_transient_update_plugins', function($transient) {
    $license_key = get_option('bookando_license_key');

    $response = wp_remote_get('https://api.bookando.ch/wp-plugin/update-check', [
        'headers' => ['X-License-Key' => $license_key]
    ]);

    $update_info = json_decode(wp_remote_retrieve_body($response));

    if ($update_info->new_version > BOOKANDO_VERSION) {
        $transient->response['bookando/bookando.php'] = (object) [
            'new_version' => $update_info->new_version,
            'package' => $update_info->download_url,
            'slug' => 'bookando'
        ];
    }

    return $transient;
});
```

---

### 6.3 Mobile Apps (Store Updates)

**Over-The-Air (OTA) Updates (Code-Push):**
```typescript
// For non-native code updates (JS/assets)
import CodePush from 'react-native-code-push';

const App = () => {
    useEffect(() => {
        CodePush.sync({
            updateDialog: {
                title: 'Update verfügbar',
                optionalUpdateMessage: 'Neue Features sind verfügbar!',
                optionalInstallButtonLabel: 'Jetzt installieren'
            },
            installMode: CodePush.InstallMode.IMMEDIATE
        });
    }, []);

    return <AppContent />;
};

export default CodePush(App);
```

**Store Updates (Full App):**
```typescript
// Check for required update
async function checkAppVersion() {
    const { data } = await api.get('/app/version');

    const currentVersion = DeviceInfo.getVersion();

    if (semver.lt(currentVersion, data.minimumVersion)) {
        // Force update
        Alert.alert(
            'Update erforderlich',
            'Bitte aktualisieren Sie die App.',
            [{ text: 'Zum Store', onPress: () => {
                const storeUrl = Platform.OS === 'ios'
                    ? 'https://apps.apple.com/app/bookando/id123456'
                    : 'https://play.google.com/store/apps/details?id=ch.bookando';
                Linking.openURL(storeUrl);
            }}]
        );
    }
}
```

---

## 7. Zusammenfassung: Technologie-Entscheidungen

| Komponente | Technologie | Begründung |
|------------|-------------|------------|
| **Central API** | Node.js + Express + TypeScript | TypeScript-Sharing, Performance, Ecosystem |
| **Database** | PostgreSQL 15 | Robust, ACID, JSON Support, Multi-Tenant |
| **ORM** | Prisma | Type-safe, Migrations, Auto-generated Client |
| **Admin Panel** | React + TypeScript (vorhanden) | Already built, production-ready |
| **Customer Portal** | React + TypeScript | Code-Sharing mit Admin Panel |
| **Employee Portal** | React + TypeScript | Code-Sharing, Mobile-First |
| **WordPress Plugin** | PHP + React (Gutenberg) | WordPress Standard, Modern Blocks |
| **Mobile Apps** | React Native | Code-Sharing (95%), Native Performance |
| **Real-time** | Socket.io | WebSocket, Fallbacks, Room Support |
| **Queue** | BullMQ | Reliable, Redis-backed, Retry Logic |
| **Email** | SendGrid | Deliverability, Templates, Analytics |
| **SMS** | Twilio | Global, Reliable, API-first |
| **Payments** | Stripe + PayPal | Market Leader, Easy Integration |
| **File Storage** | AWS S3 / Cloudflare R2 | Scalable, CDN-backed |
| **Push Notifications** | Firebase Cloud Messaging | Cross-platform, Free, Reliable |
| **Monitoring** | Sentry + PostHog | Error Tracking + Analytics |
| **Hosting (API)** | Railway | Auto-scaling, Easy Deploy, Affordable |
| **Hosting (Frontend)** | Vercel | Edge Network, Auto-SSL, Git Integration |

---

## 8. Nächste Schritte

Siehe `PHASE_1_IMPLEMENTATION.md` für detaillierte Umsetzungsschritte.

**Prioritäten:**
1. ✅ Central API Setup
2. ✅ Database Schema
3. ✅ Authentication
4. ✅ Core Endpoints (Customers, Courses, Services, Bookings)
5. WordPress Plugin (MVP)
6. Customer Portal (MVP)
7. Mobile Apps (MVP)

**Timeline:** 6 Wochen für Fundament + 8 Wochen für Kernfunktionen = 14 Wochen
