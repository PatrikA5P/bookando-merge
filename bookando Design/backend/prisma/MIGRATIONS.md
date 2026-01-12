# Prisma Migrationen

Dieses Dokument erklärt, wie Sie Datenbankmigrationen erstellen und anwenden.

## 🚀 Erste Migration erstellen (Setup)

Wenn Sie das Projekt zum ersten Mal aufsetzen:

```bash
cd backend

# 1. Datenbank muss laufen (Docker)
docker-compose up -d postgres

# 2. Prisma Client generieren
npm run prisma:generate

# 3. Erste Migration erstellen
npx prisma migrate dev --name init

# 4. Seed-Daten laden (optional)
npm run prisma:seed
```

Die erste Migration erstellt alle Tabellen basierend auf `schema.prisma`.

## 📝 Neue Migration erstellen (nach Schema-Änderungen)

Nachdem Sie `schema.prisma` geändert haben:

```bash
# Migration erstellen und anwenden
npx prisma migrate dev --name beschreibung_der_aenderung

# z.B.:
npx prisma migrate dev --name add_user_phone_field
npx prisma migrate dev --name update_customer_status_enum
```

Was passiert:
1. Prisma vergleicht Schema mit aktueller DB
2. Erstellt SQL-Migration in `prisma/migrations/`
3. Wendet Migration auf Datenbank an
4. Aktualisiert Prisma Client

## 🌍 Production Migrations (ohne Prompts)

Für CI/CD und Production:

```bash
# Migration anwenden ohne Prompts
npx prisma migrate deploy
```

Verwendet nur die Migration-Files aus `prisma/migrations/`.

## 🔍 Status prüfen

```bash
# Zeigt angewendete und ausstehende Migrations
npx prisma migrate status
```

## ⚠️ Wichtige Hinweise

### ✅ DO:
- **Immer** `prisma migrate dev` in Entwicklung
- Migrations in Git commiten
- Migration-Namen beschreibend wählen
- Vor Production-Deploy: Migration testen

### ❌ DON'T:
- Niemals Migrations manuell aus `prisma/migrations/` löschen
- Nicht `prisma db push` in Production (nur für Prototyping)
- Keine Breaking Changes ohne Migrationsstrategie

## 🛠️ Troubleshooting

### Problem: Migration schlägt fehl

```bash
# Schema und DB synchronisieren (VORSICHT: Datenverlust!)
npx prisma migrate reset

# Dann:
npm run prisma:seed
```

### Problem: "P3009: Migrations have been modified"

Migrations wurden manuell geändert. Optionen:

```bash
# 1. Reset (Datenverlust!)
npx prisma migrate reset

# 2. Baseline (für bestehende DBs)
npx prisma migrate resolve --applied "migration-name"
```

### Problem: Datenbank zurücksetzen

```bash
# Alle Daten löschen und Migrations neu anwenden
npx prisma migrate reset

# Seed-Daten neu laden
npm run prisma:seed
```

## 📚 Migration-Strategien

### Breaking Changes vermeiden

**Problem:** Feld umbenennen

❌ **Falsch:**
```prisma
// Alt: name: String
// Neu: fullName: String
```
→ Datenverlust!

✅ **Richtig:**
1. Neues Feld `fullName` hinzufügen
2. Migration erstellen mit Daten-Migration:
```sql
UPDATE "User" SET "fullName" = "name";
```
3. Alte Feld `name` als optional markieren
4. Nach Deploy: `name` entfernen (separate Migration)

### Mehrere Environments

**Development:**
```bash
npx prisma migrate dev --name change_description
```

**Staging/Production:**
```bash
npx prisma migrate deploy
```

## 🎯 Best Practices

1. **Eine Änderung pro Migration**
   - Leichter zu revertieren
   - Bessere Nachvollziehbarkeit

2. **Descriptive Namen**
   ```bash
   # Gut:
   npx prisma migrate dev --name add_customer_birthday_field
   npx prisma migrate dev --name update_user_unique_constraint

   # Schlecht:
   npx prisma migrate dev --name update
   npx prisma migrate dev --name fix
   ```

3. **Migrations testen**
   - Auf Kopie der Production-DB testen
   - Rollback-Plan haben

4. **Seed-Daten aktuell halten**
   - Nach Schema-Änderungen `seed.ts` anpassen
   - Seed sollte immer lauffähig sein

## 📖 Weitere Ressourcen

- [Prisma Migrate Docs](https://www.prisma.io/docs/concepts/components/prisma-migrate)
- [Migration Troubleshooting](https://www.prisma.io/docs/guides/migrate/troubleshooting-development)
- [Production Best Practices](https://www.prisma.io/docs/guides/migrate/production-troubleshooting)

---

## 🚦 Aktueller Stand dieses Projekts

**Migrations-Status:** Noch keine Migrations vorhanden

**Nächste Schritte:**
1. Erste Migration erstellen: `npx prisma migrate dev --name init`
2. Seed-Daten laden: `npm run prisma:seed`
3. Backend starten: `npm run dev`

**Schema-Änderungen seit letztem Commit:**
- User: `@@unique([organizationId, email])` (Multi-Tenancy)
- RefreshToken: Besteht bereits
- Customer: Status-Filter Support

Führen Sie die erste Migration aus, um diese Änderungen in die Datenbank zu übernehmen.
