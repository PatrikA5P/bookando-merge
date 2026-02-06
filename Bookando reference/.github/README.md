# GitHub Actions CI/CD

Automatische Continuous Integration & Continuous Deployment Workflows für das Bookando Monorepo.

## 📋 Workflows

### 1. Frontend CI (`frontend.yml`)

Läuft bei jedem Push/PR der Frontend-Code ändert.

**Jobs:**
- **Build & Test** (Node 18.x & 20.x)
  - TypeScript Type-Checking
  - ESLint Linting
  - Build für Production
  - Tests ausführen
  - Bundle Size Check
  - Artifacts hochladen

- **Quality Checks**
  - TODO/FIXME/HACK Counter
  - console.log Detektion
  - types-api.ts Validierung

**Trigger:**
```yaml
on:
  push:
    branches: [main, develop, 'claude/**']
    paths: ['src/**', 'components/**', 'modules/**', ...]
```

**Status Badge:**
```markdown
![Frontend CI](https://github.com/OWNER/REPO/workflows/Frontend%20CI/badge.svg)
```

---

### 2. Backend CI (`backend.yml`)

Läuft bei jedem Push/PR der Backend-Code ändert.

**Jobs:**
- **Validate Schema**
  - Prisma Schema Validierung
  - Schema Format Check
  - Prisma Client Generation
  - Migration Count Check

- **Build & Test** (Node 18.x & 20.x)
  - PostgreSQL Service Container (für Tests)
  - TypeScript Type-Checking
  - Database Migration Deploy
  - Build für Production
  - Tests mit echter DB

- **Security Scan**
  - npm audit
  - Secret Detection (API Keys, Passwords)

**Services:**
```yaml
services:
  postgres:
    image: postgres:15
    env:
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: postgres
      POSTGRES_DB: bookando_test
```

**Status Badge:**
```markdown
![Backend CI](https://github.com/OWNER/REPO/workflows/Backend%20CI/badge.svg)
```

---

### 3. Monorepo CI (`monorepo.yml`)

Läuft bei jedem Push/PR auf main/develop.

**Jobs:**
- **Check All**
  - Repository Struktur Validierung
  - Große Dateien Detektion
  - Lines of Code Counter
  - Dokumentation Check

- **Compatibility Check**
  - types-api.ts ↔ Prisma Schema Sync
  - Migration Status (types.ts → types-api.ts)

**Checks:**
```bash
✅ Required files exist
✅ No large files (>5MB)
✅ Documentation complete
✅ Types in sync
```

**Status Badge:**
```markdown
![Monorepo CI](https://github.com/OWNER/REPO/workflows/Monorepo%20CI/badge.svg)
```

---

## 🚀 Wie es funktioniert

### Automatische Triggers

1. **Push zu Branch**
   ```bash
   git push origin claude/my-feature
   # → Löst Frontend & Backend CI aus (wenn Dateien geändert)
   ```

2. **Pull Request**
   ```bash
   gh pr create --base main
   # → Läuft alle relevanten Workflows
   ```

3. **Commit zu main/develop**
   ```bash
   git push origin main
   # → Läuft ALLE Workflows (Frontend, Backend, Monorepo)
   ```

### Job-Abhängigkeiten

```
Frontend CI:
  build-and-test → quality-checks → summary

Backend CI:
  validate-schema → build-and-test
                 → security-scan
                 → summary

Monorepo CI:
  check-all → compatibility-check → summary
```

---

## ✅ Was wird geprüft?

### Frontend
- ✅ TypeScript kompiliert ohne Fehler
- ✅ ESLint Rules befolgt
- ✅ Build funktioniert (Vite)
- ✅ Tests laufen durch
- ✅ Bundle Size akzeptabel
- ⚠️ Keine console.log im Code
- ⚠️ TODOs dokumentiert

### Backend
- ✅ Prisma Schema valide
- ✅ Schema richtig formatiert
- ✅ Prisma Client generierbar
- ✅ TypeScript kompiliert
- ✅ Migrations vorhanden
- ✅ Build funktioniert
- ✅ Tests mit PostgreSQL
- ✅ Keine Security Vulnerabilities
- ⚠️ Keine Secrets im Code

### Monorepo
- ✅ Alle Required Files vorhanden
- ✅ Keine riesigen Dateien
- ✅ Dokumentation komplett
- ✅ types-api.ts ↔ Prisma sync
- ℹ️ Code Statistics

---

## 🔧 Lokale Ausführung (Simulation)

### Frontend Checks lokal
```bash
# Type Check
npx tsc --noEmit

# Lint
npm run lint

# Build
npm run build

# Tests
npm test
```

### Backend Checks lokal
```bash
cd backend

# Prisma Validate
npx prisma validate

# Prisma Format Check
npx prisma format --check

# Generate Client
npx prisma generate

# Type Check
npx tsc --noEmit

# Build
npm run build

# Tests (requires PostgreSQL)
npm test
```

---

## 📊 Status Anzeigen

Füge Badges zu README.md hinzu:

```markdown
# Bookando

![Frontend CI](https://github.com/YOUR_ORG/bookando-monorepo/workflows/Frontend%20CI/badge.svg)
![Backend CI](https://github.com/YOUR_ORG/bookando-monorepo/workflows/Backend%20CI/badge.svg)
![Monorepo CI](https://github.com/YOUR_ORG/bookando-monorepo/workflows/Monorepo%20CI/badge.svg)
```

**Live Status:**
- ✅ Grün = Alle Checks passed
- ❌ Rot = Mindestens ein Check failed
- ⚪ Grau = Nicht gelaufen / Übersprungen

---

## 🐛 Troubleshooting

### "Prisma validation failed"
```bash
# Lokal prüfen
cd backend
npx prisma validate

# Häufige Ursachen:
# - Syntax Error im Schema
# - Fehlende Relations
# - Duplikate (@unique)
```

### "Build failed"
```bash
# Type Errors finden
npx tsc --noEmit

# Dependencies neu installieren
rm -rf node_modules package-lock.json
npm install
```

### "Tests failed"
```bash
# PostgreSQL läuft?
docker ps | grep postgres

# Test DB Connection
psql $DATABASE_URL
```

### "Secrets detected"
```bash
# Suche nach Secrets
grep -r "sk_live_\|pk_live_" backend/src/

# Verwende Environment Variables statt Hardcoded
# ❌ const key = "sk_live_abc123";
# ✅ const key = process.env.STRIPE_SECRET_KEY;
```

---

## 🔐 Secrets & Environment Variables

**GitHub Repository Settings:**
```
Settings → Secrets and variables → Actions
```

**Benötigte Secrets (für zukünftige Deployments):**
```
VERCEL_TOKEN
RAILWAY_TOKEN
DATABASE_URL (production)
JWT_SECRET (production)
STRIPE_SECRET_KEY
SENDGRID_API_KEY
```

**Aktuell:** Keine Secrets nötig für CI (nur Tests)

---

## 📈 Performance

**Durchschnittliche Laufzeiten:**
- Frontend CI: ~3-5 Minuten
- Backend CI: ~4-6 Minuten (inkl. PostgreSQL)
- Monorepo CI: ~1-2 Minuten

**Matrix Builds:**
- Node 18.x + 20.x = 2x parallel Jobs
- Schnellere Feedback-Loop

---

## 🎯 Best Practices

1. **Kleine Commits**
   - Jeder Commit triggert CI
   - Kleine Änderungen = schnellere Feedback

2. **Branch Naming**
   - `claude/**` branches automatisch getestet
   - `feature/**`, `fix/**` auch möglich

3. **Commit Messages**
   - Klar beschreiben was geändert wurde
   - CI-Log ist einfacher zu verstehen

4. **Vor dem Push**
   ```bash
   # Lokal prüfen
   npm run build
   cd backend && npx prisma validate
   ```

5. **Bei Fehlern**
   - GitHub Actions Tab öffnen
   - Logs analysieren
   - Lokal reproduzieren
   - Fixen & erneut pushen

---

## 📝 Updates & Erweiterungen

**Zukünftige Workflows:**
- Deployment zu Vercel (Frontend)
- Deployment zu Railway (Backend)
- E2E Tests (Playwright)
- Visual Regression Tests
- Performance Monitoring
- Automated Dependency Updates (Dependabot)

**Anpassungen in:**
- `.github/workflows/*.yml`

---

**Stand:** 2026-01-11
**Erstellt von:** Claude Code
**Status:** ✅ Aktiv
