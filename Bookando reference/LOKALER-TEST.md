# 🚀 Bookando Lokal Testen - Schritt für Schritt Anleitung

Diese Anleitung zeigt Ihnen, wie Sie die Bookando-Plattform lokal auf Ihrem Computer starten und testen können.

## 📋 Voraussetzungen

- ✅ Docker Desktop (läuft)
- ✅ Node.js 18+ (mit npm)
- ✅ Visual Studio Code (optional, aber empfohlen)

## 🔧 Schritt 1: Datenbanken starten (PostgreSQL + Redis)

Öffnen Sie ein Terminal im Projekt-Hauptverzeichnis und starten Sie die Docker-Container:

```bash
# Im Hauptverzeichnis: /home/user/bookando-monorepo
docker-compose up -d
```

**Was passiert:**
- PostgreSQL wird auf Port `5432` gestartet
- Redis wird auf Port `6379` gestartet

**Prüfen ob die Container laufen:**
```bash
docker ps
```

Sie sollten zwei Container sehen: `bookando-postgres` und `bookando-redis`

## 🗄️ Schritt 2: Backend Setup

### 2.1 Dependencies installieren

```bash
cd backend
npm install
```

### 2.2 Datenbank-Schema erstellen

```bash
# Prisma Client generieren
npm run prisma:generate

# Datenbank-Migration ausführen (erstellt alle Tabellen)
npm run prisma:migrate
# Wenn gefragt, geben Sie einen Namen ein, z.B.: "init"
```

### 2.3 Test-Daten laden (optional, aber empfohlen)

```bash
npm run prisma:seed
```

**Dies erstellt:**
- 1 Demo-Organisation ("Demo Fahrschule")
- 1 Admin-User (admin@demo.ch / Password123!)
- 1 Employee (Max Muster)
- 2 Kunden
- 2 Kurse
- 1 Service
- Beispiel-Buchungen

### 2.4 Backend starten

```bash
npm run dev
```

**Backend läuft jetzt auf:** `http://localhost:3001`

**Wichtige Endpoints:**
- API: `http://localhost:3001/api`
- Health Check: `http://localhost:3001/health`

Lassen Sie dieses Terminal-Fenster offen!

## 🎨 Schritt 3: Frontend starten

### 3.1 Neues Terminal öffnen

Öffnen Sie ein **neues Terminal** (das Backend-Terminal muss weiterlaufen!)

### 3.2 Dependencies installieren und starten

```bash
# Zurück ins Hauptverzeichnis
cd /home/user/bookando-monorepo

# Dependencies installieren (falls noch nicht geschehen)
npm install

# Frontend starten
npm run dev
```

**Frontend läuft jetzt auf:** `http://localhost:5173`

## 🧪 Schritt 4: Die App testen

### 4.1 Verwaltungs-Login (Admin-Bereich)

1. Öffnen Sie im Browser: **`http://localhost:5173`**
2. Sie sehen das Bookando Dashboard
3. Login-Daten aus dem Seed:
   - **Email:** `admin@demo.ch`
   - **Password:** `Password123!`

**Was Sie hier testen können:**
- ✅ Dashboard mit Übersicht
- ✅ **Customers** (Kunden verwalten)
- ✅ **Employees** (Mitarbeiter verwalten)
- ✅ **Academy** (Kurse/Events verwalten)
- ✅ **Appointments** (Buchungen/Termine)
- ✅ **Finance** (Rechnungen)
- ✅ **Resources** (Standorte, Räume)
- ✅ **Settings** (Organisationseinstellungen)

### 4.2 Öffentliches Buchungsformular

Das öffentliche Buchungsformular ist in diesem Projekt noch nicht vollständig implementiert.

**Über die API testen:**

Sie können Buchungen über die API erstellen:

```bash
# Beispiel: Verfügbare Kurse abrufen
curl http://localhost:3001/api/courses

# Beispiel: Kurs-Details abrufen
curl http://localhost:3001/api/courses/{course-id}

# Beispiel: Verfügbare Sessions für einen Kurs
curl http://localhost:3001/api/courses/{course-id}/sessions
```

### 4.3 Prisma Studio (Datenbank-Viewer)

Für direkten Einblick in die Datenbank können Sie Prisma Studio öffnen:

```bash
# In neuem Terminal, im backend Verzeichnis
cd backend
npm run prisma:studio
```

**Öffnet sich automatisch auf:** `http://localhost:5555`

Hier sehen Sie alle Tabellen und können Daten direkt bearbeiten.

## 🔍 Was Sie testen sollten

### ✅ Backend-Tests

1. **Authentifizierung:**
   - Login mit admin@demo.ch
   - Token wird korrekt gespeichert
   - Logout funktioniert

2. **Course Management:**
   - Neuen Kurs erstellen (`/api/courses`)
   - Kurs bearbeiten
   - Kurs-Sessions erstellen
   - Tags zuweisen

3. **Notifications:**
   - Notification-Einstellungen konfigurieren (`/api/notifications/settings`)
   - Event-Trigger testen

4. **Calendar Integration:**
   - Google Calendar verbinden (benötigt OAuth Setup)
   - Events synchronisieren

### ✅ Frontend-Tests

1. **Navigation:**
   - Zwischen Modulen wechseln
   - UI ist responsiv

2. **Academy-Modul:**
   - Kurse werden angezeigt
   - Kategorie-Filter funktioniert
   - Kurs-Details aufrufen

3. **Customers:**
   - Kundenliste durchsuchen
   - Neuen Kunden anlegen
   - Kundendetails bearbeiten

4. **Employees:**
   - Mitarbeiterliste
   - Skills & Qualifikationen

## 🐛 Troubleshooting

### Problem: Backend startet nicht

**Fehler:** `Error: connect ECONNREFUSED 127.0.0.1:5432`

**Lösung:**
```bash
# Prüfen ob PostgreSQL Container läuft
docker ps

# Falls nicht, neu starten
docker-compose up -d
```

### Problem: "Prisma Client not generated"

**Lösung:**
```bash
cd backend
npm run prisma:generate
```

### Problem: Frontend zeigt "Network Error"

**Prüfen Sie:**
1. Läuft das Backend auf Port 3001?
2. Ist CORS korrekt konfiguriert in `.env`?
3. Browser-Konsole für Fehler prüfen (F12)

### Problem: Migration schlägt fehl

**Fehler:** `P1001: Can't reach database server`

**Lösung:**
```bash
# Datenbank zurücksetzen
docker-compose down -v
docker-compose up -d

# 10 Sekunden warten, dann erneut versuchen
cd backend
npm run prisma:migrate
```

## 🛑 App beenden

### Backend beenden
In dem Terminal wo `npm run dev` läuft: `Ctrl+C`

### Frontend beenden
In dem Terminal wo `npm run dev` läuft: `Ctrl+C`

### Docker Container stoppen
```bash
# Im Hauptverzeichnis
docker-compose down

# ODER: Container + alle Daten löschen (Vorsicht!)
docker-compose down -v
```

## 📚 Nützliche Befehle

```bash
# Datenbank zurücksetzen (alle Daten löschen!)
cd backend
npx prisma migrate reset

# Neue Migration erstellen (nach Schema-Änderungen)
npm run prisma:migrate

# Prisma Studio öffnen (Datenbank-GUI)
npm run prisma:studio

# Backend Tests ausführen
npm test

# Docker Logs ansehen
docker-compose logs -f postgres
docker-compose logs -f redis
```

## 🎯 Nächste Schritte

Nach dem erfolgreichen lokalen Test können Sie:

1. **Eigene Daten anlegen:** Erstellen Sie Ihre eigenen Kurse, Kunden, etc.
2. **API testen:** Nutzen Sie Postman oder curl für API-Tests
3. **Frontend erweitern:** Passen Sie die Module an Ihre Bedürfnisse an
4. **Deployment vorbereiten:** Siehe `backend/README.md` für Production-Setup

## 💡 Tipps

- **VS Code Extensions:**
  - Prisma (Syntax Highlighting)
  - Thunder Client (API Testing)
  - Docker (Container Management)

- **Browser DevTools:**
  - Network Tab: API-Calls überwachen
  - Console: React-Fehler sehen
  - Application Tab: LocalStorage/Cookies prüfen

- **Datenbank-Zugriff:**
  - Prisma Studio ist am einfachsten
  - Alternativ: DBeaver, pgAdmin, TablePlus

---

**Bei Fragen oder Problemen:** Prüfen Sie die Logs in den Terminal-Fenstern!
