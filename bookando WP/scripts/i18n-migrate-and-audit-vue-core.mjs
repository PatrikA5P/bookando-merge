#!/usr/bin/env node
 

/**
 * Bookando i18n Audit & Migration – fokussiert (+ Revert + verbesserte Audit-Übersicht)
 *
 * Modi:
 *   - Audit (Default): Übersicht + Reports
 *   - Migrate/Fix (--write | --mode=migrate): sichere Ersetzungen anwenden
 *   - Revert (--mode=revert | --revert): Backups zurückspielen, .bak/.migrated löschen
 *
 * Flags (Auszug – kompatibel mit deiner bisherigen Nutzung):
 *   --write                      Migration anwenden
 *   --backup=false               KEINE .bak-Dateien (Default: true)
 *   --add_missing_keys=false     de.json NICHT auto-ergänzen (Default: true)
 *   --include-complex=true       komplexe gebundene Ausdrücke im Audit listen (kein Auto-Fix)
 *   --ignore="\\.spec\\.vue$"    Dateien ignorieren (mehrfach nutzbar)
 *   --report="pfad.json"         Pfad für detaillierten Audit-Report
 *   --de="pfad/zu/de.json"       Pfad zu de.json
 *   --glob="src"                 (Revert) Such-Root für *.bak
 */

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname  = path.dirname(__filename);

// ------------------ CLI & Pfade ------------------
const argv = process.argv.slice(2);
const flags = Object.fromEntries(
  argv.map(a => {
    const [k,v] = a.includes('=') ? a.split('=') : [a, true];
    return [k.replace(/^--/, ''), v];
  })
);

const PROJECT_ROOT = path.resolve(process.cwd());
const TARGET_DIRS = [
  path.resolve(PROJECT_ROOT, 'src/modules'),
  path.resolve(PROJECT_ROOT, 'src/Core'),
];

const DE_JSON = path.resolve(
  PROJECT_ROOT,
  flags.de ? String(flags.de) : 'src/Core/Design/i18n/de.json'
);

const REPORT_AUDIT_PATH   = path.resolve(PROJECT_ROOT, flags.report ? String(flags.report) : 'i18n-audit-vue-core-report.json');
const REPORT_PLAN_PATH    = path.resolve(PROJECT_ROOT, 'i18n-migration-plan.json');
const REPORT_MIGR_PATH    = path.resolve(PROJECT_ROOT, 'i18n-migration-report.json');
// NEU: kompakte Übersicht zusätzlich
const REPORT_SUMMARY_PATH = path.resolve(PROJECT_ROOT, 'i18n-audit-summary.json');

const MAX_CONTEXT = Number(flags.context || 80);

const MODE_MIGRATE = String(flags.mode || '').toLowerCase() === 'migrate' || !!flags.migrate || !!flags.write;
const DO_WRITE     = !!flags.write || MODE_MIGRATE;
const DO_BACKUP    = flags.backup === undefined ? true : String(flags.backup) !== 'false';
const ADD_MISSING_KEYS = flags.add_missing_keys === undefined ? true : String(flags.add_missing_keys) !== 'false';

// Standard: KEINE komplexen gebundenen Ausdrücke in Findings
const INCLUDE_COMPLEX = String(flags['include-complex'] || 'false') === 'true';

// Revert-Spezifisches
const MODE = (flags.mode ? String(flags.mode) : (flags.revert ? 'revert' : (DO_WRITE ? 'migrate' : 'audit'))).toLowerCase();
const REVERT_DRY = !!flags['dry-run'];
const REVERT_KEEP_CURRENT = flags['keep-current'] === undefined ? true : String(flags['keep-current']) === 'true';
const REVERT_GLOB = String(flags.glob || 'src'); // unterhalb dieses Pfades werden .vue.bak gesucht

// ------------------ Ignore & Whitelist ------------------
const DEFAULT_IGNORE = [/AppTableStickyTest\.vue$/i];
const IGNORE_RE = []
  .concat(DEFAULT_IGNORE)
  .concat(flags.ignore ? String(flags.ignore).split(',').filter(Boolean).map(s => new RegExp(s, 'i')) : []);

const WHITELIST_STRINGS = new Set(
  ['Bookando'].concat(flags.whitelist ? String(flags.whitelist).split(',').map(s => s.trim()).filter(Boolean) : [])
);

function shouldIgnore(filePath){ return IGNORE_RE.some(re => re.test(filePath)); }
function isWhitelistedText(s){ return WHITELIST_STRINGS.has(String(s).trim()); }

// ------------------ Utils ------------------
function read(p){ return fs.readFileSync(p, 'utf8'); }
function write(p, s){ fs.writeFileSync(p, s, 'utf8'); }
function exists(p){ try { return fs.existsSync(p); } catch { return false; } }
function safeReadJson(p){
  try { return JSON.parse(read(p)); }
  catch(e){ console.error(`[ERR] de.json konnte nicht gelesen werden: ${p}`); console.error(e); process.exit(1); }
}
function flattenKeys(obj, prefix = '', out = new Set()){
  if (obj && typeof obj === 'object' && !Array.isArray(obj)) {
    for (const [k,v] of Object.entries(obj)) {
      const p = prefix ? `${prefix}.${k}` : k;
      if (v && typeof v === 'object' && !Array.isArray(v)) flattenKeys(v, p, out);
      else out.add(p);
    }
  }
  return out;
}
function ensurePath(obj, keyPath, value){
  const parts = keyPath.split('.');
  let cur = obj;
  for (let i=0;i<parts.length;i++){
    const k = parts[i];
    if (i === parts.length - 1) {
      if (cur[k] === undefined) cur[k] = value;
    } else {
      if (cur[k] === undefined || typeof cur[k] !== 'object' || Array.isArray(cur[k])) cur[k] = {};
      cur = cur[k];
    }
  }
}
function walk(dir, out = []){
  if (!fs.existsSync(dir)) return out;
  for (const ent of fs.readdirSync(dir, { withFileTypes:true })) {
    const p = path.join(dir, ent.name);
    if (ent.isDirectory()) walk(p, out);
    else if (ent.isFile() && path.extname(ent.name) === '.vue') out.push(p);
  }
  return out;
}
function walkFiles(root, pred, out = []){
  if (!fs.existsSync(root)) return out;
  for (const ent of fs.readdirSync(root, { withFileTypes:true })) {
    const p = path.join(root, ent.name);
    if (ent.isDirectory()) walkFiles(p, pred, out);
    else if (ent.isFile() && pred(p)) out.push(p);
  }
  return out;
}
function indexToLineCol(src, idx){
  let line = 1, col = 1;
  for (let i=0;i<idx;i++){
    if (src[i] === '\n') { line++; col = 1; } else col++;
  }
  return { line, col };
}
function snippet(src, start, end){
  const a = Math.max(0, start - MAX_CONTEXT);
  const b = Math.min(src.length, end + MAX_CONTEXT);
  return src.slice(a,b).replace(/\r?\n/g, ' ');
}
function isLikelyText(s){ return /[A-Za-zÄÖÜäöüß0-9]/.test(s); }
function looksUrl(s){ return /^https?:\/\//i.test(s); }
function slugify(s, prefix){
  return `${prefix}.` + s
    .toLowerCase()
    .replace(/\s+/g,'_')
    .replace(/[^a-z0-9_./-]/g,'')
    .slice(0,64);
}
function escSingleQuotes(s){ return String(s).replace(/\\/g, '\\\\').replace(/'/g, "\\'"); }
function del(p){ try { fs.unlinkSync(p); } catch (_) { void _; } }

// ------------------ Erkenner (Regex-Konstanten) ------------------
// t('key') / $t('key') / i18n.t('key')
const T_CALL = /\b(?:t|\$t|i18n\.t)\(\s*(['"])([^'"()]+)\1\s*[),]?/g;

// Nur Template-Teil
const TEMPLATE_RE = /<template[^>]*>([\s\S]*?)<\/template>/i;
// Scripts
const SCRIPT_SETUP_RE = /<script[^>]*\bsetup\b[^>]*>([\s\S]*?)<\/script>/i;
const SCRIPT_ANY_RE   = /<script[^>]*>([\s\S]*?)<\/script>/i;

// Attributnamen (Default nur die geforderten)
const ATTR_CANDIDATES_DEFAULT = ['label','placeholder','title','aria-label','alt','tooltip'];
const USER_ATTRS = (flags.attrs ? String(flags.attrs).split(',').map(s=>s.trim()).filter(Boolean) : null);
const ATTR_CANDIDATES = USER_ATTRS && USER_ATTRS.length ? USER_ATTRS : ATTR_CANDIDATES_DEFAULT;

// Regex aus erlaubten Attributen
const ATTR_RE = new RegExp(
  String.raw`(\s|^)(?:${ATTR_CANDIDATES.map(a=>a.replace(/-/g,'\\-')).join('|')})\s*=\s*(["'])((?:(?!\2).)+)\2`,
  'gi'
);
// Gebunden: :label="…"
const BOUND_ATTR_RE = new RegExp(
  String.raw`(\s|^):(?:${ATTR_CANDIDATES.map(a=>a.replace(/-/g,'\\-')).join('|')})\s*=\s*(["'])((?:(?!\2).)+)\2`,
  'gi'
);

// Textknoten: > Text <
const TEXT_NODE_RE = />\s*([^<>{}][^<>{}]*)\s*</g;
// Mustache-Literal: {{ 'Text' }}
const MUSTACHE_LITERAL_RE = /\{\{\s*(['"])([^'"]+)\1\s*\}\}/g;

// Mapping bekannter Texte -> Keys
const KNOWN_MAP = new Map([
  ['OK', 'core.common.ok'],
  ['Ok', 'core.common.ok'],
  ['ok', 'core.common.ok'],
  ['Aktionen', 'ui.table.actions'],
  ['Bitte eine gültige Zeit im Format HH:MM eingeben.', 'ui.time.invalid_format_hint'],
  ['Uhr', 'ui.a11y.time'],
  ['Kalender', 'ui.a11y.calendar'],
  ['Spalte verschieben', 'ui.table.move_column'],
  ['Upgrade erforderlich', 'mod.license.upgrade_required'],
  ['Dieses Modul ist im aktuellen Plan nicht enthalten.', 'mod.license.not_in_plan'],
  ['ist erforderlich.', 'mod.license.required'],
  ['Jetzt upgraden', 'mod.license.upgrade_now'],
  ['Lizenz erforderlich', 'mod.license.upgrade_required'],
  ['nicht freigeschaltet.', 'mod.license.not_in_plan'],
  ['Ländersuche', 'ui.search.countries'],
  ['Vorwahl zurücksetzen', 'ui.phone.clear_country'],
  ['Image', 'ui.common.image'],
  ['Reset Filter', 'ui.filter.reset_all'],
  ['z.B. Sa 10–14 Uhr -10%', 'mod.services.form.pricing.example_label'],
  ['Auswählen', 'ui.common.select'],
  ['Abbrechen', 'core.common.cancel'],
  ['Feld leeren', 'ui.input.clear_field'],
]);

function mapTextToKey(text){
  const t = text.trim();
  if (KNOWN_MAP.has(t)) return KNOWN_MAP.get(t);
  return null;
}

// ------------------ Revert-Modus ------------------
if (MODE === 'revert') {
  const startDir = path.join(PROJECT_ROOT, REVERT_GLOB);
  const vueBaks = walkFiles(startDir, p => p.endsWith('.vue.bak'));
  const migratedFiles = walkFiles(startDir, p => p.endsWith('.vue.migrated')); // vorhandene .migrated (werden gelöscht)

  if (!vueBaks.length && !fs.existsSync(`${DE_JSON}.bak`)) {
    console.log('Keine .bak Dateien gefunden.');
    process.exit(0);
  }

  let restored = 0;
  console.log('=== REVERT: Stelle ursprüngliche Dateien aus Backups wieder her ===');

  for (const bak of vueBaks) {
    const target = bak.slice(0, -4); // foo.vue.bak -> foo.vue
    const rel = path.relative(PROJECT_ROOT, target);

    if (REVERT_DRY) {
      console.log(`↺ (DRY) Restore ${rel}  ←  ${path.basename(bak)}`);
      continue;
    }

    // Restore
    fs.copyFileSync(bak, target);
    // Backup löschen
    del(bak);

    console.log(`↺ Restore ${rel}`);
    restored++;
  }

  // de.json.bak wiederherstellen (wenn vorhanden)
  if (fs.existsSync(`${DE_JSON}.bak`)) {
    const rel = path.relative(PROJECT_ROOT, DE_JSON);
    if (REVERT_DRY) {
      console.log(`↺ (DRY) Restore ${rel}  ←  ${path.basename(`${DE_JSON}.bak`)}`);
    } else {
      fs.copyFileSync(`${DE_JSON}.bak`, DE_JSON);
      del(`${DE_JSON}.bak`);
      console.log(`↺ Restore ${rel}`);
      restored++;
    }
  }

  // vorhandene .migrated löschen (falls von früheren Runs vorhanden)
  if (!REVERT_DRY && migratedFiles.length) {
    for (const m of migratedFiles) del(m);
    console.log(`🧹 Entfernt: ${migratedFiles.length} *.migrated Datei(en)`);
  }

  console.log(`\n✓ Revert abgeschlossen: ${restored} Datei(en) wiederhergestellt${REVERT_DRY ? ' (DRY-RUN)' : ''}.`);
  process.exit(0);
}

// ------------------ Scan ------------------
const deObj = safeReadJson(DE_JSON);
const allJsonKeys = flattenKeys(deObj);

const files = TARGET_DIRS.flatMap(d => walk(d)).filter(f => !shouldIgnore(f)).sort();
const usedKeySet = new Set();
const usedKeysPerFile = new Map();

const findings = [];
const byFileMap = new Map();

// Für Audit-Übersicht:
const filesMissingSetup = [];   // nutzt t(), aber Import/Destructuring fehlt
const filesNoScriptSetup = [];  // nutzt t(), aber kein <script> gefunden
const notI18nFiles = new Set(); // keine t(), aber harte Texte vorhanden
const partialI18nFiles = new Set(); // t() vorhanden und harte Texte vorhanden
const hardTextFiles = new Map(); // file -> count harter Stellen
const missingKeysByFile = new Map(); // file -> Set(keys)

for (const file of files) {
  const src = read(file);
  const rel = path.relative(PROJECT_ROOT, file);

  // Keys aus t(...) in kompletter Datei erfassen + per Datei
  let tm;
  while ((tm = T_CALL.exec(src))) {
    const k = (tm[2] || '').trim();
    if (k) {
      usedKeySet.add(k);
      if (!usedKeysPerFile.has(rel)) usedKeysPerFile.set(rel, new Set());
      usedKeysPerFile.get(rel).add(k);
    }
  }

  // Template extrahieren
  const mt = TEMPLATE_RE.exec(src);
  if (!mt) {
    // Kein Template → trotzdem Setup prüfen, wenn t() in Script benutzt wird
    const usesT = /\bt\(/.test(src) || /\bi18n\.t\(/.test(src);
    if (usesT) {
      const sm = SCRIPT_SETUP_RE.exec(src) || SCRIPT_ANY_RE.exec(src);
      if (!sm) { filesNoScriptSetup.push(rel); continue; }
      const sc = sm[1] || '';

      // 1) Import: erlaubt Alias u. zusätzliche Importe im selben Block
      const hasImport = /import\s*{\s*[^}]*\buseI18n\b[^}]*}\s*from\s*['"]vue-i18n['"]/.test(sc);

      // 2) t-Available:
      //    a) direkte Destrukturierung aus useI18n(...)
      //    b) t = useI18n(...).t
      //    c) Zwischenvariable: const i18 = useI18n(...); const { t } = i18
      let hasT =
      /const\s*{\s*[^}]*\bt\b[^}]*}\s*=\s*useI18n\s*\(\s*[^)]*\)/.test(sc) ||
      /const\s+t\s*=\s*useI18n\s*\(\s*[^)]*\)\s*\.t/.test(sc);

      if (!hasT) {
      const mVar = /const\s+([A-Za-z_$][\w$]*)\s*=\s*useI18n\s*\(\s*[^)]*\)/.exec(sc);
      if (mVar) {
          const varName = mVar[1].replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
          const reDestrFromVar = new RegExp(`const\\s*{\\s*[^}]*\\bt\\b[^}]*}\\s*=\\s*${varName}`);
          hasT = reDestrFromVar.test(sc);
      }
      }

      if (!hasImport || !hasT) {
        filesMissingSetup.push({ file: rel, hasImport, hasT });
      }
    }
    continue;
  }

  const tpl = mt[1];
  const tplOffset = mt.index + mt[0].indexOf(tpl);

  // Ungebundene Attribute: label="Text"
  let a;
  let hardCount = 0;
  while ((a = ATTR_RE.exec(tpl))) {
    const full = a[0];
    const val = (a[3] ? a[3] : a[4] ? a[4] : a[2]) || a[0]; // defensive
    const text = (a[3] || a[4] || '').trim();
    if (!text) continue;
    if (!isLikelyText(text)) continue;
    if (looksUrl(text)) continue;
    if (/{{|}}/.test(full)) continue;
    if (/\bt\(['"]/.test(full)) continue;

    const absIndex = tplOffset + a.index;
    const { line, col } = indexToLineCol(src, absIndex);
    const attrName = full.split('=')[0].trim();

    const item = {
      file, line, col,
      kind: 'attr',
      attr: attrName,
      text: text,
      full,
      start: absIndex,
      end: absIndex + full.length,
      context: snippet(src, absIndex, absIndex + full.length),
      suggestion_key: mapTextToKey(text) || slugify(text, 'PENDING.KEY')
    };
    findings.push(item);
    if (!byFileMap.has(file)) byFileMap.set(file, []);
    byFileMap.get(file).push(item);
    hardCount++;
  }

  // Gebundene Attribute: :label="'Text'" oder Ternary mit Literalen
  let b;
  while ((b = BOUND_ATTR_RE.exec(tpl))) {
    const full = b[0];
    const expr = (b[3] || '').trim();
    if (!expr) continue;

    if (/`/.test(expr) || /\$\{/.test(expr)) {
      if (INCLUDE_COMPLEX) {
        const absIndex = tplOffset + b.index;
        const { line, col } = indexToLineCol(src, absIndex);
        const item = {
          file, line, col,
          kind: 'bound-attr-complex',
          attr: full.split('=')[0].trim(),
          text: expr,
          full,
          start: absIndex,
          end: absIndex + full.length,
          context: snippet(src, absIndex, absIndex + full.length),
          suggestion_key: null
        };
        findings.push(item);
        if (!byFileMap.has(file)) byFileMap.set(file, []);
        byFileMap.get(file).push(item);
        hardCount++;
      }
      continue;
    }
    if (/\bt\(['"]/.test(expr)) continue; // bereits übersetzt

    const literalMatch = expr.match(/^\s*(['"])([^'"]+)\1\s*$/);
    const ternaryMatch = expr.match(/^\s*([^?]+?)\?\s*(['"])([^'"]+)\2\s*:\s*(['"])([^'"]+)\4\s*$/);

    if (!literalMatch && !ternaryMatch) {
      if (INCLUDE_COMPLEX) {
        const absIndex = tplOffset + b.index;
        const { line, col } = indexToLineCol(src, absIndex);
        const item = {
          file, line, col,
          kind: 'bound-attr-complex',
          attr: full.split('=')[0].trim(),
          text: expr,
          full,
          start: absIndex,
          end: absIndex + full.length,
          context: snippet(src, absIndex, absIndex + full.length),
          suggestion_key: null
        };
        findings.push(item);
        if (!byFileMap.has(file)) byFileMap.set(file, []);
        byFileMap.get(file).push(item);
        hardCount++;
      }
      continue;
    }

    const absIndex = tplOffset + b.index;
    const { line, col } = indexToLineCol(src, absIndex);

    if (literalMatch) {
      const lit = literalMatch[2];
      if (!isLikelyText(lit) || looksUrl(lit)) continue;
      const item = {
        file, line, col,
        kind: 'bound-attr-literal',
        attr: full.split('=')[0].trim(),
        text: lit,
        full,
        start: absIndex,
        end: absIndex + full.length,
        context: snippet(src, absIndex, absIndex + full.length),
        suggestion_key: mapTextToKey(lit) || slugify(lit, 'PENDING.KEY')
      };
      findings.push(item);
      if (!byFileMap.has(file)) byFileMap.set(file, []);
      byFileMap.get(file).push(item);
      hardCount++;
      continue;
    }

    if (ternaryMatch) {
      const cond   = ternaryMatch[1].trim();
      const left   = ternaryMatch[3];
      const right  = ternaryMatch[5];
      const leftKey  = mapTextToKey(left)  || slugify(left,  'PENDING.KEY');
      const rightKey = mapTextToKey(right) || slugify(right, 'PENDING.KEY');

      const item = {
        file, line, col,
        kind: 'bound-attr-ternary',
        attr: full.split('=')[0].trim(),
        text: expr,
        cond, left, right,
        leftKey, rightKey,
        full,
        start: absIndex,
        end: absIndex + full.length,
        context: snippet(src, absIndex, absIndex + full.length)
      };
      findings.push(item);
      if (!byFileMap.has(file)) byFileMap.set(file, []);
      byFileMap.get(file).push(item);
      hardCount++;
      continue;
    }
  }

  // Plain-Text
  let tnode;
  while ((tnode = TEXT_NODE_RE.exec(tpl))) {
    const matchStr = tnode[0];
    const val = (tnode[1] || '').trim();
    if (!val) continue;
    if (!isLikelyText(val)) continue;
    if (/^\s*<!--/.test(val)) continue;               // Kommentar
    if (/\{\{[^}]*\bt\(['"]/.test(matchStr)) continue; // schon übersetzt
    const innerIdx = matchStr.indexOf(val);
    if (innerIdx < 0) continue;

    const absIndex = tplOffset + tnode.index + innerIdx;
    const { line, col } = indexToLineCol(src, absIndex);

    const item = {
      file, line, col,
      kind: 'text',
      text: val,
      start: absIndex,
      end: absIndex + val.length,
      context: snippet(src, absIndex, absIndex + val.length),
      suggestion_key: mapTextToKey(val) || slugify(val, 'PENDING.TEXT')
    };
    findings.push(item);
    if (!byFileMap.has(file)) byFileMap.set(file, []);
    byFileMap.get(file).push(item);
    hardCount++;
  }

  // Mustache-Literal {{ 'Text' }}
  let ml;
  while ((ml = MUSTACHE_LITERAL_RE.exec(tpl))) {
    const full = ml[0];
    const val  = (ml[2] || '').trim();
    if (!isLikelyText(val)) continue;
    if (/\bt\(['"]/.test(full)) continue;

    const absIndex = tplOffset + ml.index;
    const { line, col } = indexToLineCol(src, absIndex);
    const item = {
      file, line, col,
      kind: 'mustache-literal',
      text: val,
      full,
      start: absIndex,
      end: absIndex + full.length,
      context: snippet(src, absIndex, absIndex + full.length),
      suggestion_key: mapTextToKey(val) || slugify(val, 'PENDING.TEXT')
    };
    findings.push(item);
    if (!byFileMap.has(file)) byFileMap.set(file, []);
    byFileMap.get(file).push(item);
    hardCount++;
  }

  if (hardCount > 0) hardTextFiles.set(rel, hardCount);

  // i18n-Setup nur prüfen, wenn irgendwo t() benutzt wird
  const usesT = /\bt\(/.test(src) || /\bi18n\.t\(/.test(src) || /\{\{\s*t\(/.test(src);
  if (usesT) {
    const sm = SCRIPT_SETUP_RE.exec(src) || SCRIPT_ANY_RE.exec(src);
    if (!sm) {
      filesNoScriptSetup.push(rel);
    } else {
      const sc = sm[1] || '';
      const hasImport = /import\s*{\s*useI18n\s*}\s*from\s*['"]vue-i18n['"]/.test(sc);
      const hasT      = /const\s*{\s*t\s*}\s*=\s*useI18n\s*\(\s*\)/.test(sc);
      if (!hasImport || !hasT) filesMissingSetup.push({ file: rel, hasImport, hasT });
    }
  }

  // Klassifizierung: nicht i18n vs. teilweise i18n
  if (hardCount > 0) {
    if (usesT) partialI18nFiles.add(rel);
    else notI18nFiles.add(rel);
  }

  // pro Datei fehlende Keys (aus benutzten t('key'))
  const perFileKeys = usedKeysPerFile.get(rel);
  if (perFileKeys && perFileKeys.size) {
    for (const k of perFileKeys) {
      if (!allJsonKeys.has(k)) {
        if (!missingKeysByFile.has(rel)) missingKeysByFile.set(rel, new Set());
        missingKeysByFile.get(rel).add(k);
      }
    }
  }
}

// ------------------ Key-Statistiken (global) ------------------
const unusedKeys  = [...allJsonKeys].filter(k => !usedKeySet.has(k)).sort();
const missingKeys = [...usedKeySet].filter(k => !allJsonKeys.has(k)).sort();

// ------------------ Audit-Ausgabe (Detail) ------------------
console.log('\n=== i18n Audit (Vue in src/modules + src/Core) ===\n');
console.log(`Projekt:   ${PROJECT_ROOT}`);
console.log(`de.json:   ${DE_JSON}`);
console.log(`Plan:      ${REPORT_PLAN_PATH}`);
console.log(`Report:    ${REPORT_AUDIT_PATH}\n`);

const filesWithFindings = [...byFileMap.keys()];
console.log(`.vue-Dateien gescannt: ${files.length}`);
console.log(`Dateien mit unübersetztem Text: ${filesWithFindings.length}`);
console.log(`Fundstellen gesamt: ${findings.length}`);
console.log(`Ungelesene Keys in de.json: ${unusedKeys.length}`);
console.log(`Fehlende Übersetzungen (im Code benutzt, nicht in de.json): ${missingKeys.length}\n`);

if (byFileMap.size) {
  console.log('--- Dateien mit unübersetztem Text (vollständige Liste) ---\n');
  for (const file of [...byFileMap.keys()].sort()) {
    const items = byFileMap.get(file);
    console.log(`• ${path.relative(PROJECT_ROOT, file)}  (${items.length} Fundstellen)`);
    for (const f of items) {
      const where = `[${f.line}:${f.col}]`;
      let info = '';
      if (f.kind === 'attr') info = `${f.kind} ${f.attr}="${f.text}"`;
      else if (f.kind === 'bound-attr-literal') info = `${f.kind} ${f.attr}="${f.text}"`;
      else if (f.kind === 'bound-attr-ternary') info = `${f.kind} ${f.attr}="${f.text}"`;
      else if (f.kind === 'mustache-literal') info = `${f.kind} "{{ '${f.text}' }}"`;
      else info = `${f.kind} "${f.text}"`;
      console.log(`   - ${where}  ${info}`);
      if (f.suggestion_key) console.log(`     ↳ key: ${f.suggestion_key}`);
      if (f.left && f.right) console.log(`     ↳ keys: ${f.leftKey} / ${f.rightKey}`);
      console.log(`     ↳ ctx: ${f.context}\n`);
    }
  }
  console.log('');
}

// JSON-Audit (Detail) schreiben – BEIBEHALTEN
const auditReport = {
  root: PROJECT_ROOT,
  scanned: files.map(f => path.relative(PROJECT_ROOT, f)).sort(),
  findings,
  filesWithFindings: filesWithFindings.map(f => path.relative(PROJECT_ROOT, f)).sort(),
  unusedKeys,
  missingKeys,
  stats: {
    filesScanned: files.length,
    filesWithFindings: byFileMap.size,
    findingCount: findings.length,
    unusedKeyCount: unusedKeys.length,
    missingKeyCount: missingKeys.length
  },
  options: {
    ignore: IGNORE_RE.map(r => String(r)),
    whitelist: [...WHITELIST_STRINGS],
    attrs: ATTR_CANDIDATES,
    includeComplex: INCLUDE_COMPLEX
  }
};
write(REPORT_AUDIT_PATH, JSON.stringify(auditReport, null, 2));

// ------------------ NEU: kompakte Audit-Übersicht ------------------
const missingSetupSimple = filesMissingSetup.map(x => x.file).sort();
const notI18nList        = [...notI18nFiles].sort();
const partialI18nList    = [...partialI18nFiles].sort();
const hardTextList       = [...hardTextFiles.entries()].sort((a,b)=>b[1]-a[1]).map(([f,c])=>({file:f,count:c}));

const missingKeysSummary = [...missingKeysByFile.entries()]
  .map(([f,set]) => ({ file: f, keys: [...set].sort() }))
  .sort((a,b)=> a.file.localeCompare(b.file));

const missingKeysAll = new Set();
for (const {keys} of missingKeysSummary) for (const k of keys) missingKeysAll.add(k);

const summary = {
  root: PROJECT_ROOT,
  deJson: path.relative(PROJECT_ROOT, DE_JSON),
  filesScanned: files.length,
  summary: {
    missingSetupFiles: missingSetupSimple.length,
    notI18nFiles: notI18nList.length,
    partialI18nFiles: partialI18nList.length,
    filesWithHardText: hardTextList.length,
    missingKeysFiles: missingKeysSummary.length,
    missingKeysTotal: missingKeysAll.size,
    unusedKeysInDe: unusedKeys.length
  },
  lists: {
    missingSetupFiles: missingSetupSimple,
    notI18nFiles: notI18nList,
    partialI18nFiles: partialI18nList,
    filesWithHardText: hardTextList,
    missingKeysByFile: missingKeysSummary,
    unusedKeysInDe: unusedKeys
  }
};
write(REPORT_SUMMARY_PATH, JSON.stringify(summary, null, 2));

// Konsolen-Übersicht
console.log('=== i18n AUDIT – Übersicht ===');
console.log(`• Ohne i18n-Setup (nutzt t(), Setup fehlt): ${missingSetupSimple.length}`);
if (missingSetupSimple.length) for (const f of missingSetupSimple) console.log(`   - ${f}`);
console.log(`• Nicht i18n (keine t(), harte Texte): ${notI18nList.length}`);
if (notI18nList.length) for (const f of notI18nList) console.log(`   - ${f}`);
console.log(`• Teilweise i18n (t() + harte Texte): ${partialI18nList.length}`);
if (partialI18nList.length) for (const f of partialI18nList) console.log(`   - ${f}`);
console.log(`• Dateien mit fehlenden Keys in de.json: ${missingKeysSummary.length}`);
if (missingKeysSummary.length) {
  for (const x of missingKeysSummary) {
    console.log(`   - ${x.file}`);
    console.log(`       → ${x.keys.join(', ')}`);
  }
}
console.log(`• Unbenutzte Keys in de.json: ${unusedKeys.length}`);
console.log(`Summary: ${path.relative(PROJECT_ROOT, REPORT_SUMMARY_PATH)}\n`);

// ------------------ Migrations-Plan (BEIBEHALTEN) ------------------
function buildPlanForFile(file, fileFindings, src){
  const edits = [];
  const keysToAdd = new Map();

  function addKeyIfNeeded(key, val){
    if (!key) return;
    if (!allJsonKeys.has(key)) keysToAdd.set(key, val);
  }

  for (const f of fileFindings) {
    if (f.kind === 'attr') {
      const attrName = f.attr.replace(/^\s+/, '');
      const key = f.suggestion_key;
      const fallback = escSingleQuotes(f.text);
      const bound = attrName.startsWith(':') ? attrName : ':'+attrName;
      const replacement = `${bound}="t('${key}') || '${fallback}'"`; // label="X" -> :label="t('k') || 'X'"
      edits.push({ start: f.start, end: f.end, replacement, kind: f.kind, key, previewBefore: src.slice(Math.max(0,f.start-40), Math.min(src.length,f.end+40)), previewAfter: replacement });
      addKeyIfNeeded(key, f.text);
      continue;
    }
    if (f.kind === 'bound-attr-literal') {
      const attrName = f.attr;
      const key = f.suggestion_key;
      const fallback = escSingleQuotes(f.text);
      const replacement = `${attrName}="t('${key}') || '${fallback}'"`; // :label="'X'" -> :label="t('k') || 'X'"
      edits.push({ start: f.start, end: f.end, replacement, kind: f.kind, key, previewBefore: src.slice(Math.max(0,f.start-40), Math.min(src.length,f.end+40)), previewAfter: replacement });
      addKeyIfNeeded(key, f.text);
      continue;
    }
    if (f.kind === 'bound-attr-ternary') {
      const attrName = f.attr;
      const leftFB  = escSingleQuotes(f.left);
      const rightFB = escSingleQuotes(f.right);
      const replacement = `${attrName}="${f.cond} ? (t('${f.leftKey}') || '${leftFB}') : (t('${f.rightKey}') || '${rightFB}')"`; 
      edits.push({ start: f.start, end: f.end, replacement, kind: f.kind, keys: [f.leftKey, f.rightKey], previewBefore: src.slice(Math.max(0,f.start-40), Math.min(src.length,f.end+40)), previewAfter: replacement });
      addKeyIfNeeded(f.leftKey,  f.left);
      addKeyIfNeeded(f.rightKey, f.right);
      continue;
    }
    if (f.kind === 'text') {
      const key = f.suggestion_key;
      const replacement = `{{ t('${key}') }}`;
      edits.push({ start: f.start, end: f.end, replacement, kind: f.kind, key, previewBefore: src.slice(Math.max(0,f.start-40), Math.min(src.length,f.end+40)), previewAfter: replacement });
      addKeyIfNeeded(key, f.text);
      continue;
    }
    if (f.kind === 'mustache-literal') {
      const key = f.suggestion_key;
      const replacement = `{{ t('${key}') }}`;
      edits.push({ start: f.start, end: f.end, replacement, kind: f.kind, key, previewBefore: src.slice(Math.max(0,f.start-40), Math.min(src.length,f.end+40)), previewAfter: replacement });
      addKeyIfNeeded(key, f.text);
      continue;
    }
  }

  const skipped = fileFindings.filter(f => f.kind === 'bound-attr-complex');
  const canApply = skipped.length === 0 && edits.length > 0;

  return { file, canApply, edits, skipped, keysToAdd: Object.fromEntries(keysToAdd) };
}

const plan = [];
for (const file of [...byFileMap.keys()].sort()) {
  const src = read(file);
  const fileFindings = byFileMap.get(file) || [];
  const p = buildPlanForFile(file, fileFindings, src);
  plan.push(p);
}
const planSummary = {
  filesPlanned: plan.length,
  filesApplicable: plan.filter(p => p.canApply).length,
  totalEdits: plan.reduce((s,p)=>s+p.edits.length,0),
  totalSkipped: plan.reduce((s,p)=>s+p.skipped.length,0),
  totalKeysToAdd: plan.reduce((s,p)=>s+Object.keys(p.keysToAdd).length,0),
};
write(REPORT_PLAN_PATH, JSON.stringify({ summary: planSummary, plan }, null, 2));

console.log('--- Migrations-Plan ---');
console.log(`Anwendbare Dateien: ${planSummary.filesApplicable}/${planSummary.filesPlanned}`);
console.log(`Geplante Edits:     ${planSummary.totalEdits}`);
console.log(`Übersprungen:       ${planSummary.totalSkipped}`);
console.log(`Neue Keys:          ${planSummary.totalKeysToAdd}`);
console.log(`Plan:               ${REPORT_PLAN_PATH}\n`);

if (!DO_WRITE) {
  console.log('Nur Audit + Plan erzeugt (kein --write / --mode=migrate).');
  console.log('→ Prüfe die Zusammenfassung oben und führe dann mit --write aus.\n');
  process.exit(0);
}

// ------------------ Migration anwenden (BEIBEHALTEN) ------------------
console.log('=== Migration wird angewendet (nur sichere Edits) ===');

const migrationReport = { applied: [], skipped: [], addedKeys: [], errors: [] };

// 1) Fehlende Keys in de.json ergänzen (optional)
if (ADD_MISSING_KEYS) {
  let keysAdded = 0;
  for (const p of plan) {
    for (const [k, v] of Object.entries(p.keysToAdd)) {
      if (!allJsonKeys.has(k)) {
        ensurePath(deObj, k, v);
        allJsonKeys.add(k);
        migrationReport.addedKeys.push({ key: k, value: v });
        keysAdded++;
      }
    }
  }
  if (keysAdded > 0) {
    if (DO_BACKUP && fs.existsSync(DE_JSON)) {
      fs.copyFileSync(DE_JSON, `${DE_JSON}.bak`);
    }
    write(DE_JSON, JSON.stringify(deObj, null, 2));
    console.log(`→ de.json aktualisiert: ${keysAdded} Keys ergänzt`);
  } else {
    console.log('→ de.json: keine neuen Keys erforderlich');
  }
} else {
  console.log('→ Ergänzung fehlender Keys ist deaktiviert (--add_missing_keys=false)');
}

// 2) Dateien patchen
for (const p of plan) {
  if (!p.canApply || p.edits.length === 0) {
    migrationReport.skipped.push({ file: p.file, reason: p.skipped.length ? 'complex-expressions' : 'no-edits' });
    continue;
  }
  try {
    const src = read(p.file);
    const sorted = [...p.edits].sort((a,b)=> b.start - a.start);
    let out = src;
    for (const e of sorted) out = out.slice(0, e.start) + e.replacement + out.slice(e.end);
    if (DO_BACKUP) fs.copyFileSync(p.file, `${p.file}.bak`);
    write(p.file, out);
    migrationReport.applied.push({ file: p.file, edits: p.edits.length });
    console.log(`✓ ${path.relative(PROJECT_ROOT, p.file)}  (${p.edits.length} Edits)`);
  } catch (err) {
    migrationReport.errors.push({ file: p.file, error: String(err) });
    console.error(`✗ Fehler bei ${p.file}:`, err);
  }
}

write(REPORT_MIGR_PATH, JSON.stringify(migrationReport, null, 2));
console.log(`\nFertig. Bericht: ${REPORT_MIGR_PATH}\n`);
