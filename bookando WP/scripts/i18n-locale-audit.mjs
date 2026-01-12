#!/usr/bin/env node
 

import fs from 'node:fs';
import path from 'node:path';

const PROJECT_ROOT = process.cwd();
const TARGET_DIRS = [
  path.join(PROJECT_ROOT, 'src', 'modules'),
  path.join(PROJECT_ROOT, 'src', 'Core'),
];

const argv = process.argv.slice(2);
const WRITE = argv.includes('--write');

function read(p){ return fs.readFileSync(p, 'utf8'); }
function write(p, s){ fs.writeFileSync(p, s, 'utf8'); }
function exists(p){ try { return fs.existsSync(p); } catch { return false; } }
function backup(p){ if (exists(p)) fs.copyFileSync(p, `${p}.bak`); }
function walk(dir, out = []) {
  if (!exists(dir)) return out;
  for (const ent of fs.readdirSync(dir, { withFileTypes:true })) {
    const p = path.join(dir, ent.name);
    if (ent.isDirectory()) walk(p, out);
    else if (ent.isFile() && p.endsWith('.vue')) out.push(p);
  }
  return out;
}
function toLC(src, idx){
  let line = 1, col = 1;
  for (let i=0; i<idx; i++) {
    if (src[i] === '\n') { line++; col = 1; } else col++;
  }
  return { line, col };
}

/** find all <template> blocks (usually one) */
function extractTemplates(src){
  const out = [];
  const re = /<template[^>]*>([\s\S]*?)<\/template>/gi;
  let m;
  while ((m = re.exec(src))) out.push({content: m[1], start: m.index + m[0].indexOf(m[1])});
  return out;
}

/** find all <script> blocks (setup oder normal) */
function extractScripts(src){
  const out = [];
  const re = /<script\b[^>]*>([\s\S]*?)<\/script>/gi;
  let m;
  while ((m = re.exec(src))) out.push({content: m[1], start: m.index + m[0].indexOf(m[1])});
  return out;
}

/** findet alle Destrukturierungen aus useI18n(): const { ... } = useI18n(...) */
function findI18nDestructurings(script){
  const out = [];
  const re = /const\s*{\s*([^}]*)\s*}\s*=\s*useI18n\s*\([^)]*\)/g;
  let m;
  while ((m = re.exec(script.content))) {
    const full = m[0];
    const inner = m[1]; // Inhalt der Klammern {...}
    const absStart = script.start + m.index;
    const absEnd   = absStart + full.length;

    // parse inner: sammeln wir binding-namen inkl. alias
    // Beispiele: "t, locale", "locale, t", "t, locale: loc, d"
    // Wir interessieren uns nur für "locale" binding.
    // capture aliasName = rechte Seite bei "locale: alias" oder "locale"
    const aliasMatch = inner.match(/(^|,)\s*locale\s*:\s*([A-Za-z_$][\w$]*)\s*(?=,|$)/);
    const plainMatch = inner.match(/(^|,)\s*locale\s*(?=,|$)/);

    if (!aliasMatch && !plainMatch) continue;

    const aliasName = aliasMatch ? aliasMatch[2] : 'locale';

    out.push({
      full, inner, absStart, absEnd,
      aliasName,
      innerStart: absStart + full.indexOf(inner),
      innerEnd: absStart + full.indexOf(inner) + inner.length
    });
  }
  return out;
}

/** prüft, ob tokenName irgendwo außerhalb der Deklaration benutzt wird (script + alle templates) */
function isUsed(name, fileSrc, excludedRanges, _templates){
  // baue Regex mit Wortgrenzen
  const re = new RegExp(`\\b${name}\\b`, 'g');

  // Suche im gesamten File …
  let m;
  while ((m = re.exec(fileSrc))) {
    const idx = m.index;

    // … aber Ignoriere Vorkommen, die innerhalb der Deklarationsbereiche liegen
    const inExcluded = excludedRanges.some(r => idx >= r[0] && idx < r[1]);
    if (inExcluded) continue;

    // Wenn im <template>, ist es ebenfalls eine echte Verwendung -> passt
    // (wir müssen nichts gesondert tun; wir suchen bereits im ganzen File)
    return true;
  }
  return false;
}

/** entfernt "locale" aus der Destrukturierung */
function removeLocaleFromInner(inner){
  let s = inner;

  // alias-Form entfernen: locale: alias
  s = s.replace(/(^|,)\s*locale\s*:\s*[A-Za-z_$][\w$]*\s*(?=,|$)/g, (m, p1) => p1 ? '' : '');

  // plain-Form entfernen: locale
  s = s.replace(/(^|,)\s*locale\s*(?=,|$)/g, (m, p1) => p1 ? '' : '');

  // aufräumen: doppelte/trailing commas, Spaces normalisieren
  s = s.replace(/^\s*,\s*/,'').replace(/\s*,\s*$/,'').replace(/\s*,\s*,\s*/g, ', ');
  s = s.split(',').map(p => p.trim()).filter(Boolean).join(', ');

  return s;
}

const files = TARGET_DIRS.flatMap(d => walk(d));
const results = [];
let fixedFiles = 0;
let fixedBindings = 0;

for (const file of files) {
  const src = read(file);

  const scripts = extractScripts(src);
  if (!scripts.length) continue;

  // Finde alle Destrukturierungen mit locale
  const matches = scripts.flatMap(s => findI18nDestructurings(s));
  if (!matches.length) continue;

  // Prüfe Verwendung
  const unused = [];
  for (const m of matches) {
    const used = isUsed(m.aliasName, src, [[m.absStart, m.absEnd]], extractTemplates(src));
    if (!used) {
      const { line, col } = toLC(src, m.absStart);
      unused.push({ aliasName: m.aliasName, start: m.absStart, end: m.absEnd, innerStart: m.innerStart, innerEnd: m.innerEnd, line, col, inner: m.inner, full: m.full });
    }
  }

  if (!unused.length) continue;

  results.push({
    file,
    items: unused.map(u => ({
      line: u.line, col: u.col, aliasName: u.aliasName, sample: u.full.slice(0, 140) + (u.full.length>140?'…':'')
    }))
  });

  if (WRITE) {
    let out = src;
    // Von hinten nach vorne ersetzen
    const sorted = [...unused].sort((a,b) => b.innerStart - a.innerStart);
    let didChange = false;

    for (const u of sorted) {
      const inner = out.slice(u.innerStart, u.innerEnd);
      const cleaned = removeLocaleFromInner(inner);

      if (!cleaned.trim()) {
        // würde leere Destrukturierung hinterlassen -> nicht anfassen (nur melden)
        continue;
      }

      if (cleaned !== inner) {
        out = out.slice(0, u.innerStart) + cleaned + out.slice(u.innerEnd);
        didChange = true;
        fixedBindings++;
      }
    }

    if (didChange && out !== src) {
      backup(file);
      write(file, out);
      fixedFiles++;
    }
  }
}

// Report
console.log('\n=== i18n: locale-Audit ===');
console.log(`Gesuchte Bereiche : ${files.length} .vue-Dateien`);
console.log(`Funde (unused)    : ${results.reduce((s,r)=>s+r.items.length,0)} Binding(s) in ${results.length} Datei(en)\n`);

if (results.length) {
  for (const r of results) {
    console.log('•', path.relative(PROJECT_ROOT, r.file));
    for (const it of r.items) {
      console.log(`   - [${it.line}:${it.col}] unused locale ('${it.aliasName}')  → ${it.sample}`);
    }
  }
  console.log('');
} else {
  console.log('Keine ungenutzten locale-Bindings gefunden.\n');
}

if (WRITE) {
  console.log(`Auto-Fix: ${fixedBindings} Binding(s) in ${fixedFiles} Datei(en) bereinigt. (.bak erstellt)\n`);
} else {
  console.log('Hinweis: Mit --write werden ungenutzte locale-Bindings automatisch entfernt (mit .bak-Backup).\n');
}
