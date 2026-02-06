# Testing Guide - Phase B

Backend mit Multi-Tenancy, Auth und Customer CRUD.

## ✅ Was ist implementiert

### 1. Multi-Tenancy Middleware (`src/middleware/tenancy.ts`)
- **KRITISCH:** Verhindert Datenlecks zwischen Organisationen
- organizationId wird automatisch aus JWT/Subdomain/Header extrahiert
- Prisma Client wird "scoped" - alle Queries filtern automatisch
- Development Mode: Erstellt Default-Organisation automatisch

### 2. Authentication (`src/middleware/auth.ts` + `src/services/AuthService.ts`)
- JWT-basierte Authentifizierung
- Password Hashing mit bcrypt
- Refresh Tokens (7 Tage Gültigkeit)
- Access Tokens (15 Minuten Gültigkeit)

### 3. Customer CRUD (`src/services/CustomerService.ts` + `src/routes/customers.ts`)
- Proof-of-Concept für Multi-Tenancy
- Alle Queries automatisch auf organizationId gefiltert
- Soft Delete (ARCHIVED statt gelöscht)
- Search-Funktion

---

## 🧪 Testing

### Schnell-Test (Automatisch)

```bash
cd backend

# Server starten (in separatem Terminal)
npm run dev

# Test laufen lassen
./test-multi-tenancy.sh
```

**Was das Script macht:**
1. Fordert Sie auf, 2 Organisationen zu erstellen (via Prisma Studio)
2. Registriert User für beide Orgs
3. Erstellt Kunden für beide Orgs
4. Verifiziert dass Org A nur ihre Kunden sieht
5. ✅ **Erfolg** = Multi-Tenancy funktioniert!

---

### Manueller Test (mit cURL)

#### 1. Server starten
```bash
cd backend
npm run dev

# Sollte ausgeben:
# 🚀 Bookando Backend Server
# 📡 Server running on http://localhost:3001
```

#### 2. Health Checks
```bash
# Server alive?
curl http://localhost:3001/health

# Database connected?
curl http://localhost:3001/health/db

# Erwartete Ausgabe: {"status":"ok","database":"connected",...}
```

#### 3. Organisation erstellen

**Option A: Via Prisma Studio (empfohlen)**
```bash
npx prisma studio

# Browser öffnet http://localhost:5555
# → Organization Tabelle
# → Add record
#   name: "Fahrschule Müller"
#   email: "mueller@example.com"
#   country: "CH"
#   language: "de"
#   timezone: "Europe/Zurich"
#   currency: "CHF"
# → Save
# → Kopiere ID (z.B. "cm5abc123...")
```

**Option B: Via SQL**
```sql
-- In PostgreSQL
INSERT INTO organizations (id, name, email, country, language, timezone, currency)
VALUES (
  gen_random_uuid(),
  'Fahrschule Müller',
  'mueller@example.com',
  'CH',
  'de',
  'Europe/Zurich',
  'CHF'
);

-- Hole ID
SELECT id, name FROM organizations;
```

#### 4. User registrieren
```bash
# Ersetze YOUR_ORG_ID mit der kopierten ID
curl -X POST http://localhost:3001/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@muller.com",
    "password": "password123",
    "firstName": "Hans",
    "lastName": "Müller",
    "organizationId": "YOUR_ORG_ID"
  }'

# Erwartete Ausgabe:
# {
#   "user": {...},
#   "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
#   "refreshToken": "..."
# }

# Kopiere den token Wert!
```

#### 5. Login (falls bereits registriert)
```bash
curl -X POST http://localhost:3001/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@muller.com",
    "password": "password123"
  }'
```

#### 6. Kunden erstellen
```bash
# Ersetze YOUR_TOKEN mit dem kopierten Token
TOKEN="YOUR_TOKEN"

curl -X POST http://localhost:3001/api/customers \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "firstName": "Max",
    "lastName": "Mustermann",
    "email": "max@example.com",
    "phone": "+41 79 123 45 67",
    "city": "Zürich"
  }'

# Erwartete Ausgabe:
# {
#   "data": {
#     "id": "...",
#     "organizationId": "YOUR_ORG_ID",
#     "firstName": "Max",
#     ...
#   },
#   "message": "Customer created successfully"
# }
```

#### 7. Kunden abrufen
```bash
curl http://localhost:3001/api/customers \
  -H "Authorization: Bearer $TOKEN"

# Erwartete Ausgabe:
# {
#   "data": [...],
#   "organizationId": "YOUR_ORG_ID",
#   "count": 1
# }
```

#### 8. Kunden suchen
```bash
curl "http://localhost:3001/api/customers/search?q=Max" \
  -H "Authorization: Bearer $TOKEN"
```

#### 9. Kunde updaten
```bash
CUSTOMER_ID="..." # ID aus obiger Response

curl -X PUT http://localhost:3001/api/customers/$CUSTOMER_ID \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "phone": "+41 79 999 99 99"
  }'
```

#### 10. Kunde löschen (soft delete)
```bash
curl -X DELETE http://localhost:3001/api/customers/$CUSTOMER_ID \
  -H "Authorization: Bearer $TOKEN"

# Kunde wird auf status: 'ARCHIVED' gesetzt
```

---

### Multi-Tenancy Proof

**Test:** Zwei Organisationen können gegenseitig Daten NICHT sehen.

```bash
# 1. Erstelle Org A + User A + Customer A
# (wie oben)

# 2. Erstelle Org B + User B
curl -X POST http://localhost:3001/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@schmidt.com",
    "password": "password123",
    "firstName": "Klaus",
    "lastName": "Schmidt",
    "organizationId": "ORG_B_ID"
  }'

# Speichere TOKEN_B

# 3. Erstelle Customer für Org B
curl -X POST http://localhost:3001/api/customers \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN_B" \
  -d '{
    "firstName": "Peter",
    "lastName": "Weber",
    "email": "peter@example.com"
  }'

# 4. VERIFIKATION: User A sieht nur Max, User B sieht nur Peter

# Als User A:
curl http://localhost:3001/api/customers \
  -H "Authorization: Bearer $TOKEN_A"
# → count: 1, nur Max

# Als User B:
curl http://localhost:3001/api/customers \
  -H "Authorization: Bearer $TOKEN_B"
# → count: 1, nur Peter

# ✅ Wenn das stimmt: Multi-Tenancy funktioniert!
```

---

## ❌ Troubleshooting

### "Invalid token"
- Token ist abgelaufen (15 Min Gültigkeit)
- Lösung: Erneut einloggen oder Refresh Token nutzen

### "Organization not specified"
- JWT enthält keine organizationId
- Lösung: Erneut registrieren/einloggen

### "Customer not found"
- Customer gehört zu anderer Organisation
- **Das ist gut!** Multi-Tenancy funktioniert

### "Customer with this email already exists"
- Email ist unique pro Organization
- Lösung: Andere Email verwenden

### Server startet nicht
```bash
# Prüfe Logs
cd backend
npm run dev

# Häufige Ursachen:
# - Port 3001 bereits in Benutzung
# - PostgreSQL nicht erreichbar
# - .env fehlt oder falsch
```

### Prisma Fehler
```bash
# Regeneriere Client
cd backend
npx prisma generate

# Migrations neu ausführen
npx prisma migrate reset
npx prisma migrate dev
```

---

## 📊 Nächste Schritte

**Phase B ist fertig wenn:**
- ✅ Multi-Tenancy Middleware funktioniert
- ✅ Auth (Login/Register) funktioniert
- ✅ Customer CRUD funktioniert
- ✅ Org A sieht nur ihre Daten
- ✅ Org B sieht nur ihre Daten

**Dann:**
→ Phase C: Booking System mit Event-Driven Architecture
→ Phase D: Course Management
→ Phase E: Frontend Integration

---

**Stand:** 2026-01-11
**Status:** Phase B Implementierung komplett
**Bereit für:** Multi-Tenancy Proof-of-Concept Test
