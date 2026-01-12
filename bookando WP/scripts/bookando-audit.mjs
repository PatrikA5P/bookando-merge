#!/usr/bin/env node
/**
 * Bookando Audit Tool (Windows/VS Code freundlich)
 *
 * Features
 * - PSR-4 Mismatches (PHP, composer.json)
 * - Hook-Coverage (Closures ok; add_action/add_filter ↔ Callback-Existenz)
 * - Import-Graph (JS/TS/Vue) – inkl. import.meta.glob/globEager (BFS)
 * - SCSS-Graph (@use/@import/@forward)
 * - WP-Enqueues als Entry-Points (wp_enqueue_script/style)
 * - PHP require/include-Graph + ::class → verwaiste PHP-Dateien reduzieren
 * - Asset-Referenzen (CSS url() & JS/TS-Asset-Imports)
 * - Legacy-Heuristiken (class-*.php, no namespace) mit Template-Whitelist
 * - Konfig via audit.config.json (entries, allowUnused, treatAsTemplate, assumeUsedGlobs, assumeUsedNamespaces)
 *
 * Barrierefrei: php-parser optional. Fallback = Regex.
 */

import fs from "fs";
import fsp from "fs/promises";
import path from "path";
import { createRequire } from "module";
import { globSync } from "glob";
import { build as esbuildBuild } from "esbuild";
import { parse as parseJsonc } from "jsonc-parser";

// ----------------------------------------
// Setup
// ----------------------------------------
const requireCjs = createRequire(import.meta.url);
const ROOT = process.cwd();
const REPORT_DIR = path.join(ROOT, "scripts", "reports");
await fsp.mkdir(REPORT_DIR, { recursive: true });

// Optional libs
function tryRequire(name) {
  try { return requireCjs(name); } catch { return null; }
}
const _phpParser = tryRequire("php-parser"); // nice-to-have

// ----------------------------------------
// Utils
// ----------------------------------------
const exists = (p) => fs.existsSync(p);
const rel = (p) => path.relative(ROOT, p).replaceAll("\\", "/");
const readText = (p) => fsp.readFile(p, "utf8");
const readJson = async (p) => {
  const raw = await readText(p);
  try { return JSON.parse(raw); } catch { return parseJsonc(raw); }
};
const uniq = (arr) => Array.from(new Set(arr));

function collectDirectoriesNamed(rootDir, dirName) {
  const matches = [];
  const stack = [rootDir];

  while (stack.length) {
    const current = stack.pop();
    let entries;

    try {
      entries = fs.readdirSync(current, { withFileTypes: true });
    } catch {
      continue;
    }

    for (const entry of entries) {
      if (!entry.isDirectory()) continue;

      const nextPath = path.join(current, entry.name);

      if (entry.name === dirName) {
        matches.push(nextPath);
        // no need to traverse deeper into a legacy directory – but continue to find nested matches
      }

      stack.push(nextPath);
    }
  }

  return matches;
}

/** Robuste Glob→Regex (unterstützt **, *, ?) */
function globToRegex(glob) {
  let s = String(glob || "").replace(/\\/g, "/");
  s = s.replace(/([.+^$(){}|[\]\\])/g, "\\$1");           // escapen (ohne * ?)
  s = s.replace(/\*\*/g, "%%DOUBLESTAR%%");               // ** → Platzhalter
  s = s.replace(/\*/g, "[^/]*").replace(/\?/g, "[^/]");   // * / ?
  s = s.replace(/%%DOUBLESTAR%%/g, ".*");                 // ** → .*
  return new RegExp("^" + s + "$", "i");
}

// ----------------------------------------
// Load configs
// ----------------------------------------
const composerPath = path.join(ROOT, "composer.json");
const tsconfigPath = path.join(ROOT, "tsconfig.json");
const configPath   = path.join(ROOT, "audit.config.json");

const composer = exists(composerPath) ? await readJson(composerPath) : {};
const _tsconfig = exists(tsconfigPath) ? await readJson(tsconfigPath) : {};
const userCfg  = exists(configPath)   ? await readJson(configPath)   : {};

const cfgEntries            = Array.isArray(userCfg.entries) ? userCfg.entries : [];
const cfgAllowUnused        = Array.isArray(userCfg.allowUnused) ? userCfg.allowUnused : [];
const cfgTemplateAllow      = Array.isArray(userCfg.treatAsTemplate) ? userCfg.treatAsTemplate : [];
const cfgAssumeUsedGlobs    = Array.isArray(userCfg.assumeUsedGlobs) ? userCfg.assumeUsedGlobs : [];
const cfgAssumeUsedNS       = Array.isArray(userCfg.assumeUsedNamespaces) ? userCfg.assumeUsedNamespaces : [];

// Whitelists
const allowRegexes = [
  ...cfgAllowUnused.map(globToRegex),
  /\/e2e\//i, /\/tests?\//i, /\.spec\.[cm]?[jt]sx?$/i,
  /\/assets\/vendor\/select2\//i, /\/assets\/vendor\/select2\/i18n\//i,
  /\/dist\//i, /\/tmp\//i
];
const templateAllowRegexes = [
  ...cfgTemplateAllow.map(globToRegex),
  /\/Templates\//, /\/Design\/Templates\//
];
const assumeUsedRegexes = cfgAssumeUsedGlobs.map(globToRegex);

// ----------------------------------------
// Composer PSR-4
// ----------------------------------------
const autoloadPsr4    = (composer?.autoload && composer.autoload["psr-4"]) ? composer.autoload["psr-4"] : {};
const autoloadDevPsr4 = (composer && composer["autoload-dev"] && composer["autoload-dev"]["psr-4"]) ? composer["autoload-dev"]["psr-4"] : {};
const psr4 = { ...autoloadPsr4, ...autoloadDevPsr4 };

// ----------------------------------------
// File Inventar
// ----------------------------------------
const PHP_FILES   = globSync("src/**/*.php", { cwd: ROOT, nodir: true, dot: false });
const JS_TS_FILES = globSync("{src,assets}/**/*.{js,mjs,cjs,ts,tsx,jsx}", { cwd: ROOT, nodir: true });
const VUE_FILES   = globSync("{src,assets}/**/*.vue", { cwd: ROOT, nodir: true });
const CSS_FILES   = globSync("{src,assets}/**/*.{css,scss}", { cwd: ROOT, nodir: true });
const ASSET_FILES = globSync("{src,assets}/**/*.{svg,png,jpg,jpeg,webp,ico,gif,woff,woff2,ttf,eot,mp3,mp4,webm}", { cwd: ROOT, nodir: true });

const CODE_FILES  = uniq([...JS_TS_FILES, ...VUE_FILES]);

const modulesRoot = path.join(ROOT, "src", "modules");
const legacyTemplateDirs = exists(modulesRoot)
  ? collectDirectoriesNamed(modulesRoot, "templates")
  : [];

if (legacyTemplateDirs.length) {
  console.error("\n[bookando-audit] ⚠️ Legacy directory casing detected (use 'Templates/'):");
  for (const dir of legacyTemplateDirs) {
    console.error("  - " + rel(dir));
  }
  console.error("Rename the directories above to 'Templates/' to avoid issues on case-sensitive file systems.");
  process.exitCode = 1;
}

// ----------------------------------------
// PHP parsing (ns/classes/hooks/includes/new/::class)
// ----------------------------------------
const RE_NS            = /namespace\s+([^;{]+)\s*;/;
const RE_CLASS         = /class\s+([A-Za-z_][A-Za-z0-9_]*)\s*/g;
const RE_TRAIT         = /trait\s+([A-Za-z_][A-Za-z0-9_]*)\s*/g;
const RE_IFACE         = /interface\s+([A-Za-z_][A-Za-z0-9_]*)\s*/g;
const RE_FUNC          = /function\s+([A-Za-z_][A-Za-z0-9_]*)\s*\(/g;
const RE_ADD_ACTION    = /add_action\s*\(\s*['"]([^'"]+)['"]\s*,\s*([^)]+)\)/g;
const RE_ADD_FILTER    = /add_filter\s*\(\s*['"]([^'"]+)['"]\s*,\s*([^)]+)\)/g;
const RE_INCLUDE_STR   = /(require|include)(?:_once)?\s*\(\s*['"]([^'"]+)['"]\s*\)/g;
const RE_REQUIRE_ANY   = /(require|require_once|include|include_once)\s*\(/;
const RE_NEW_CLASS     = /\bnew\s+([A-Z_\\][A-Za-z0-9_\\]+)/g;
const RE_CLASS_CONST   = /([A-Z_\\][A-Za-z0-9_\\]*)::class\b/g;     // PHP ClassName::class
const RE_USE_IMPORT    = /\buse\s+([A-Z_\\][A-Za-z0-9_\\]*)\s*;/g;  // optional Hinweis

function fqcn(ns, name) {
  if (!name) return null;
  return (ns ? ns.replaceAll("\\\\", "\\") + "\\" : "") + name;
}

function parsePhpMeta(code, file) {
  const meta = {
    file, ns: null,
    classes: [], traits: [], interfaces: [], functions: [],
    hooks: [], legacyFlags: [], includes: [], news: [],
    classConsts: [], useImports: []
  };

  if (!meta.ns) {
    const m = code.match(RE_NS);
    meta.ns = m ? m[1].trim() : null;
  }
  for (const m of code.matchAll(RE_CLASS))      meta.classes.push({ name: m[1], ns: meta.ns });
  for (const m of code.matchAll(RE_TRAIT))      meta.traits.push({ name: m[1], ns: meta.ns });
  for (const m of code.matchAll(RE_IFACE))      meta.interfaces.push({ name: m[1], ns: meta.ns });
  for (const m of code.matchAll(RE_FUNC))       meta.functions.push({ name: m[1], ns: meta.ns });
  for (const m of code.matchAll(RE_ADD_ACTION)) meta.hooks.push({ type:"action", hook:m[1], cb:m[2].trim() });
  for (const m of code.matchAll(RE_ADD_FILTER)) meta.hooks.push({ type:"filter", hook:m[1], cb:m[2].trim() });
  for (const m of code.matchAll(RE_INCLUDE_STR))meta.includes.push(m[2]);
  for (const m of code.matchAll(RE_NEW_CLASS))  meta.news.push(m[1]);
  for (const m of code.matchAll(RE_CLASS_CONST))meta.classConsts.push(m[1]);
  for (const m of code.matchAll(RE_USE_IMPORT)) meta.useImports.push(m[1]);

  if (RE_REQUIRE_ANY.test(code)) meta.legacyFlags.push("has-require-include");
  return meta;
}

const phpMeta = [];
for (const f of PHP_FILES) {
  const abs = path.join(ROOT, f);
  const code = await readText(abs);
  phpMeta.push(parsePhpMeta(code, rel(abs)));
}

// ----------------------------------------
// PSR-4 mismatch
// ----------------------------------------
function expectedPsr4BasePath(fq) {
  const entries = Object.entries(psr4);
  let best = null;
  for (const [prefix, base] of entries) {
    if (!fq.startsWith(prefix)) continue;
    if (!best || prefix.length > best.prefix.length) best = { prefix, base };
  }
  return best;
}
const psr4Problems = [];
for (const m of phpMeta) {
  const defs = [...m.classes, ...m.traits, ...m.interfaces];
  for (const d of defs) {
    const nameFq = fqcn(d.ns, d.name);
    if (!nameFq) continue;
    const best = expectedPsr4BasePath(nameFq);
    if (!best) {
      psr4Problems.push({ type:"no-psr-4-prefix", file:m.file, fqcn:nameFq, message:"Kein PSR-4 Prefix in composer.json gefunden" });
      continue;
    }
    const relative = nameFq.substring(best.prefix.length).replaceAll("\\","/") + ".php";
    const expected = path.join(best.base, relative).replaceAll("\\","/").replace(/^\.?\//,"");
    if (m.file !== expected) {
      psr4Problems.push({ type:"psr4-mismatch", file:m.file, fqcn:nameFq, expected, message:"Pfad stimmt nicht mit PSR-4 ueberein" });
    }
  }
}

// ----------------------------------------
// Hook coverage (Closures ok)
// ----------------------------------------
const callbackCandidates = new Map(); // name -> [{file, kind}]
for (const m of phpMeta) {
  for (const f of m.functions) {
    const key = fqcn(m.ns, f.name) || f.name;
    if (!callbackCandidates.has(key)) callbackCandidates.set(key, []);
    callbackCandidates.get(key).push({ file:m.file, kind:"function" });
  }
  for (const c of m.classes) {
    const cName = fqcn(m.ns, c.name);
    if (!callbackCandidates.has(cName)) callbackCandidates.set(cName, []);
    callbackCandidates.get(cName).push({ file:m.file, kind:"class" });
  }
}
const hookIssues = [];
for (const m of phpMeta) {
  for (const h of m.hooks) {
    const cb = h.cb.replace(/\s+/g," ").replace(/^\[|\]$/g,"").trim();
    let ok = false; let hint = null;

    if (/^\s*function\b/i.test(cb)) { ok = true; hint = "Closure-Callback"; }
    else if (/^['"][A-Za-z_][A-Za-z0-9_\\]*['"]$/.test(cb)) {
      const name = cb.slice(1,-1);
      ok = callbackCandidates.has(name) || callbackCandidates.has("\\"+name);
      hint = ok ? null : "Callback-Name nicht gefunden";
    }
    else if (/\b::class\b/.test(cb) || /::/.test(cb) || /\$this/.test(cb)) {
      ok = true; hint = "Methoden-Callback (dynamisch/klassisch)";
    }

    if (!ok) {
      hookIssues.push({ type:"missing-callback", file:m.file, hook:h.hook, rawCallback:h.cb, message:hint || "Callback nicht aufloesbar" });
    }
  }
}

// ----------------------------------------
// PHP include/usage graph → verwaiste PHP-Dateien reduzieren
// ----------------------------------------
function resolvePhpInclude(fromFile, inc) {
  if (/^([A-Za-z]:)?\//.test(inc)) {
    const abs = path.isAbsolute(inc) ? inc : path.join(ROOT, inc);
    return exists(abs) ? rel(abs) : null;
  }
  const base = path.dirname(path.join(ROOT, fromFile));
  const cand = path.join(base, inc);
  return exists(cand) ? rel(cand) : null;
}
const phpRefs = new Map(PHP_FILES.map(f => [rel(path.join(ROOT,f)), new Set()]));

for (const m of phpMeta) {
  for (const inc of m.includes) {
    const target = resolvePhpInclude(m.file, inc);
    if (target && phpRefs.has(target)) phpRefs.get(target).add(m.file);
  }
}

const classToFile = new Map();
for (const m of phpMeta) {
  for (const c of m.classes) {
    const fqn = fqcn(c.ns, c.name);
    if (fqn) classToFile.set(c.name, m.file), classToFile.set(fqn, m.file);
  }
}
// new ClassName
for (const m of phpMeta) {
  for (const n of m.news) {
    const f = classToFile.get(n) || classToFile.get(n.split("\\").pop());
    if (f && phpRefs.has(f)) phpRefs.get(f).add(m.file);
  }
}
// ::class
for (const m of phpMeta) {
  for (const cn of m.classConsts) {
    const f = classToFile.get(cn) || classToFile.get(cn.split("\\").pop());
    if (f && phpRefs.has(f)) phpRefs.get(f).add(m.file);
  }
}

const PHP_ENTRY_NAME = new Set(["bookando.php","module.php","bootstrap.php","init.php","loader.php","plugin.php","index.php"]);
const phpUnusedRaw = [];
for (const f of phpRefs.keys()) {
  const bn = path.basename(f).toLowerCase();
  const isEntry = PHP_ENTRY_NAME.has(bn);
  const refCount = phpRefs.get(f).size;
  if (!isEntry && refCount === 0) phpUnusedRaw.push(f);
}

// assumeUsedNamespaces → alles darunter als benutzt markieren
const phpUnused = phpUnusedRaw.filter(f => {
  if (!cfgAssumeUsedNS.length) return true;
  const meta = phpMeta.find(m => m.file === f);
  if (!meta) return true;
  const ns = meta.ns || "";
  return !cfgAssumeUsedNS.some(prefix => ns.startsWith(prefix));
});

// ----------------------------------------
// WP enqueue → extra Entries (JS/CSS)
// ----------------------------------------
const RE_ENQUEUE = /wp_enqueue_(?:script|style)\s*\(\s*['"][^'"]+['"]\s*,\s*(?:plugins_url\s*\(\s*['"]([^'"]+)['"][^)]*\)|['"]([^'"]+)['"])/g;
function collectWpEntries() {
  const found = new Set();
  for (const file of PHP_FILES) {
    const abs = path.join(ROOT, file);
    const code = fs.readFileSync(abs, "utf8");
    let m;
    while ((m = RE_ENQUEUE.exec(code))) {
      let pth = m[1] || m[2]; if (!pth) continue;
      if (/^\/wp-content\/plugins\//i.test(pth)) {
        const seg = pth.split("/").slice(4).join("/");
        pth = seg;
      }
      const absCand = path.join(ROOT, pth);
      if (/\.(js|mjs|cjs|ts|tsx|jsx|css|scss)$/i.test(absCand) && exists(absCand)) {
        found.add(rel(absCand));
      }
    }
  }
  return Array.from(found);
}
const wpEntries = collectWpEntries();

// ----------------------------------------
// Import-Graph (JS/TS/Vue) – BFS + import.meta.glob
// ----------------------------------------
const RX_IMPORT = /import\s+(?:[^'"]*?\s+from\s+)?['"]([^'"]+)['"]|require\(\s*['"]([^'"]+)['"]\s*\)|import\(\s*['"]([^'"]+)\s*\)/g;
const RX_IMPORT_GLOB = /import\.meta\.(globEager|glob)\s*\(\s*(['"`])([^'"`]+)\2\s*(?:,\s*\{[^}]*\})?\)/g;

function resolveImport(fromFileRel, imp) {
  if (!imp || /^(https?:|@|#|~)/.test(imp)) return null;
  const fromAbs = path.join(ROOT, fromFileRel);
  const base = path.dirname(fromAbs);
  let relPath = imp.startsWith(".") ? path.resolve(base, imp) : null;
  if (!relPath) return null;
  const tryList = [
    relPath, relPath+".ts", relPath+".tsx", relPath+".js", relPath+".jsx", relPath+".mjs", relPath+".cjs", relPath+".vue",
    relPath+".scss", relPath+".css", relPath+".json",
    path.join(relPath, "index.ts"), path.join(relPath,"index.tsx"),
    path.join(relPath, "index.js"), path.join(relPath,"index.jsx"),
    path.join(relPath, "index.vue")
  ];
  for (const t of tryList) if (exists(t)) return rel(t);
  return null;
}

const fileTextCache = new Map();
async function readCodeRel(fileRel) {
  if (fileTextCache.has(fileRel)) return fileTextCache.get(fileRel);
  const abs = path.join(ROOT, fileRel);
  const txt = await readText(abs);
  fileTextCache.set(fileRel, txt);
  return txt;
}

function expandGlobRelative(fromFileRel, pattern) {
  const base = path.dirname(path.join(ROOT, fromFileRel));
  const matches = globSync(pattern, { cwd: base, nodir: true }).map(p => rel(path.join(base, p)));
  return matches;
}

async function buildJsVueGraph(entryRels) {
  const used = new Set(); const queue = [...entryRels];
  while (queue.length) {
    const cur = queue.pop();
    if (!cur || used.has(cur)) continue;
    used.add(cur);

    const txt = await readCodeRel(cur).catch(() => null);
    if (!txt) continue;

    // import / require / dynamic import
    let m; RX_IMPORT.lastIndex = 0;
    while ((m = RX_IMPORT.exec(txt))) {
      const imp = m[1] || m[2] || m[3];
      const target = resolveImport(cur, imp);
      if (target && !used.has(target)) queue.push(target);
    }

    // import.meta.glob / globEager
    RX_IMPORT_GLOB.lastIndex = 0;
    let g;
    while ((g = RX_IMPORT_GLOB.exec(txt))) {
      const pattern = g[3];
      const matches = expandGlobRelative(cur, pattern);
      for (const f of matches) if (!used.has(f)) queue.push(f);
    }

    // simple pass for .vue script content (gleiches Handling reicht)
  }
  return used;
}

// Heuristische Entries + cfg + WP
function discoverEntries() {
  const patterns = [
    "src/**/main.{ts,tsx,js,jsx}",
    "src/**/admin.{ts,tsx,js,jsx}",
    "src/**/frontend.{ts,tsx,js,jsx}",
    "src/**/ui.{ts,tsx,js,jsx}",
    "assets/**/main.{ts,tsx,js,jsx}",
    "assets/**/ui.{ts,tsx,js,jsx}"
  ];
  const s = new Set();
  for (const ptn of patterns) {
    for (const f of globSync(ptn, { cwd: ROOT, nodir: true })) s.add(rel(path.join(ROOT, f)));
  }
  return Array.from(s);
}
const cfgEntriesAbs    = cfgEntries.map(p => rel(path.join(ROOT, p)));
const heuristicEntries = discoverEntries();
const allEntries       = uniq([...cfgEntriesAbs, ...wpEntries, ...heuristicEntries]).filter(f => exists(path.join(ROOT, f)));

// esbuild (optional) – nur Zusatzsignal
const IgnoreNonJSPlugin = {
  name: "ignore-non-js",
  setup(build) {
    build.onResolve({ filter: /\.vue$/ }, args => ({ path: args.path, external: true }));
    build.onResolve({ filter: /^\/wp-content\// }, args => ({ path: args.path, external: true }));
    build.onResolve({ filter: /\.(svg|png|jpe?g|gif|webp|ico|woff2?|ttf|eot)$/i }, args => ({ path: args.path, external: true }));
    build.onLoad({ filter: /\.css$/ }, async () => ({ contents: "", loader: "css" }));
  }
};
async function esbuildInputs(entries) {
  if (!entries.length) return new Set();
  try {
    const res = await esbuildBuild({
      entryPoints: entries.map(e => path.join(ROOT, e)),
      bundle: true,
      metafile: true,
      outdir: path.join(ROOT, "scripts", "tmp-audit"),
      write: false,
      logLevel: "silent",
      platform: "browser",
      plugins: [IgnoreNonJSPlugin]
    });
    const inputs = Object.keys(res.metafile?.inputs || {});
    const asRel = inputs.map(p => rel(path.join(ROOT, p)));
    return new Set(asRel);
  } catch (e) {
    console.error("[esbuild] Metafile-Scan deaktiviert:", e.message);
    return new Set();
  }
}
const esbUsed   = await esbuildInputs(allEntries);
const jsVueUsed = await buildJsVueGraph(allEntries);

// ----------------------------------------
// SCSS Graph
// ----------------------------------------
const RX_SCSS_IMPORT = /@(?:use|import|forward)\s+['"]([^'"]+)['"]/g;
function resolveScss(fromFileRel, imp) {
  const fromAbs = path.join(ROOT, fromFileRel);
  const base = path.dirname(fromAbs);
  const file = imp.endsWith(".scss") ? imp : imp + ".scss";
  const leaf = "_" + path.basename(file);
  const dir  = path.dirname(file);
  const tries = [
    path.resolve(base, file),
    path.resolve(base, path.join(dir, leaf))
  ];
  for (const t of tries) if (exists(t)) return rel(t);
  return null;
}
const SCSS_ROOT_CANDS = uniq([
  ...cfgEntriesAbs.filter(f => f.endsWith(".scss")),
  "src/Core/Design/assets/scss/admin-ui.scss",
  ...Array.from(jsVueUsed).filter(f => f.endsWith(".scss"))
]).filter(f => exists(path.join(ROOT, f)));

async function buildScssGraph(roots) {
  const used = new Set(); const q = [...roots];
  while (q.length) {
    const cur = q.pop();
    if (used.has(cur)) continue;
    used.add(cur);
    const txt = await readText(path.join(ROOT,cur)).catch(()=>null);
    if (!txt) continue;
    let m; while ((m = RX_SCSS_IMPORT.exec(txt))) {
      const target = resolveScss(cur, m[1]);
      if (target && !used.has(target)) q.push(target);
    }
  }
  return used;
}
const scssUsed = await buildScssGraph(SCSS_ROOT_CANDS);

// ----------------------------------------
// Assets (CSS url() & JS/TS Asset-Imports)
// ----------------------------------------
const RX_CSS_URL = /url\(\s*(['"]?)([^'")]+)\1\s*\)/g;
const RX_ASSET_EXT = /\.(svg|png|jpe?g|gif|webp|ico|woff2?|ttf|eot)$/i;

function resolveAsset(fromFileRel, ref) {
  if (!ref || /^data:/.test(ref) || /^https?:/.test(ref)) return null;
  const fromAbs = path.join(ROOT, fromFileRel);
  const base = path.dirname(fromAbs);
  let cand = ref.startsWith("/") ? path.join(ROOT, ref) : path.resolve(base, ref);
  cand = cand.split("?")[0].split("#")[0];
  if (exists(cand)) return rel(cand);
  return null;
}

const assetRefs = new Set();
// CSS/SCSS url()
for (const f of CSS_FILES) {
  const txt = await readText(path.join(ROOT,f)).catch(()=>null);
  if (!txt) continue;
  let m; while ((m = RX_CSS_URL.exec(txt))) {
    const target = resolveAsset(f, m[2]);
    if (target) assetRefs.add(target);
  }
}
// JS/TS: asset-imports
for (const u of jsVueUsed) {
  const txt = await readText(path.join(ROOT,u)).catch(()=>null);
  if (!txt) continue;
  let m; while ((m = RX_IMPORT.exec(txt))) {
    const imp = m[1] || m[2] || m[3];
    if (!imp || !RX_ASSET_EXT.test(imp)) continue;
    const targ = resolveImport(u, imp);
    if (targ) assetRefs.add(targ);
  }
}

// ----------------------------------------
// Legacy-Heuristiken
// ----------------------------------------
const legacyIssues = [];
for (const m of phpMeta) {
  const isTemplate = templateAllowRegexes.some(rx => rx.test(m.file));
  if (/\/class-.*\.php$/i.test(m.file)) {
    legacyIssues.push({ type: "legacy-filepattern", file: m.file, message: "Dateiname im Stil class-*.php" });
  }
  if (m.ns && /Bookando\\(Core|Modules)/.test(m.ns) === false && m.ns?.includes("_") && !isTemplate) {
    legacyIssues.push({ type: "legacy-namespace", file: m.file, ns: m.ns, message: "Namespace mit Unterstrich/Altstil" });
  }
  if (!m.ns && !isTemplate) {
    legacyIssues.push({ type: "global-ns", file: m.file, message: "Globale Funktionen/Klassen ohne Namespace" });
  }
}

// ----------------------------------------
// Unused-Berechnung (+ assumeUsed)
// ----------------------------------------
const isAllowed = (p, regs) => regs.some(rx => rx.test(p.replace(/\\/g,"/")));

const usedJsTsVue = Array.from(jsVueUsed);
const _usedCssScss = uniq([...Array.from(scssUsed), ...usedJsTsVue.filter(f => f.endsWith(".css"))]);
const usedAssets  = Array.from(assetRefs);

// assumeUsedGlobs → als benutzt markieren
function _markAssumeUsed(list) {
  const outSet = new Set(list.map(x => x));
  for (const p of list) {
    if (isAllowed(p, assumeUsedRegexes)) outSet.add(p);
  }
  return outSet;
}
const _allCode = new Set(CODE_FILES.map(rel));
const _allCss  = new Set(CSS_FILES.map(rel));
const _allAss  = new Set(ASSET_FILES.map(rel));

const assumeCode = new Set(CODE_FILES.filter(p => isAllowed(p, assumeUsedRegexes)).map(rel));
const assumeCss  = new Set(CSS_FILES.filter(p => isAllowed(p, assumeUsedRegexes)).map(rel));
const assumeAss  = new Set(ASSET_FILES.filter(p => isAllowed(p, assumeUsedRegexes)).map(rel));

// roh
let unusedJsTsVue = CODE_FILES.filter(f => !jsVueUsed.has(rel(path.join(ROOT,f))));
let unusedScssCss = CSS_FILES.filter(f => !scssUsed.has(rel(path.join(ROOT,f))) && !usedJsTsVue.includes(rel(path.join(ROOT,f))));
let unusedAssets  = ASSET_FILES.filter(f => !usedAssets.includes(rel(path.join(ROOT,f))));
let unusedPhp     = phpUnused;

// Whitelists
unusedJsTsVue = unusedJsTsVue
  .map(rel)
  .filter(p => !isAllowed(p, allowRegexes))
  .filter(p => !assumeCode.has(p));
unusedScssCss = unusedScssCss
  .map(rel)
  .filter(p => !isAllowed(p, allowRegexes))
  .filter(p => !assumeCss.has(p));
unusedAssets  = unusedAssets
  .map(rel)
  .filter(p => !isAllowed(p, allowRegexes))
  .filter(p => !assumeAss.has(p));
unusedPhp     = unusedPhp
  .map(rel)
  .filter(p => !isAllowed(p, allowRegexes));

// esbuild Zusatzsignal: Inputs → nicht als unbenutzt werten
for (const inp of esbUsed) {
  const r = rel(path.join(ROOT, inp));
  const i1 = unusedJsTsVue.indexOf(r); if (i1>=0) unusedJsTsVue.splice(i1,1);
  const i2 = unusedScssCss.indexOf(r); if (i2>=0) unusedScssCss.splice(i2,1);
}

// ----------------------------------------
// Report
// ----------------------------------------
const report = {
  summary: {
    phpFiles: PHP_FILES.length,
    jsTsVueFiles: CODE_FILES.length,
    cssScssFiles: CSS_FILES.length,
    assetFiles: ASSET_FILES.length,
    psr4Problems: psr4Problems.length,
    hookIssues: hookIssues.length,
    legacyIssues: legacyIssues.length,
    unused: {
      jsTsVue: unusedJsTsVue.length,
      cssScss: unusedScssCss.length,
      assets: unusedAssets.length,
      php: unusedPhp.length
    }
  },
  entries: {
    cfg: cfgEntries.map(rel),
    wp: wpEntries,
    heuristic: heuristicEntries,
    all: allEntries
  },
  psr4Problems,
  hookIssues,
  legacyIssues,
  unused: {
    jsTsVue: unusedJsTsVue,
    cssScss: unusedScssCss,
    assets: unusedAssets,
    php: unusedPhp
  }
};

const stamp = new Date().toISOString().replace(/[:.]/g,"-");
const outfile = path.join(REPORT_DIR, `audit-${stamp}.json`);
await fsp.writeFile(outfile, JSON.stringify(report,null,2), "utf8");

// ----------------------------------------
// Console output
// ----------------------------------------
function logSection(title){ console.log("\n=== " + title + " ==="); }

console.log("Bookando Audit abgeschlossen");
console.log("Report:", rel(outfile));
console.log("PHP Dateien:", PHP_FILES.length);

logSection("PSR-4 Probleme");
if (!psr4Problems.length) console.log("✓ Keine gefunden");
else psr4Problems.slice(0,50).forEach(p => console.log("-", p.message, "|", p.fqcn, "→", p.file, "expected:", p.expected || "—"));

logSection("Hook-Issues");
if (!hookIssues.length) console.log("✓ Keine gefunden");
else hookIssues.slice(0,50).forEach(h => console.log("-", h.message, "|", h.hook, "cb:", h.rawCallback, "in", h.file));

logSection("Legacy-Heuristiken");
if (!legacyIssues.length) console.log("✓ Keine auffaelligen Muster");
else legacyIssues.slice(0,50).forEach(l => console.log("-", l.message, "|", l.file));

logSection("Unbenutzt – JS/TS/Vue");
if (!unusedJsTsVue.length) console.log("✓ Keine");
else unusedJsTsVue.slice(0,100).forEach(u => console.log("-", u));

logSection("Unbenutzt – CSS/SCSS");
if (!unusedScssCss.length) console.log("✓ Keine");
else unusedScssCss.slice(0,100).forEach(u => console.log("-", u));

logSection("Unbenutzt – Assets");
if (!unusedAssets.length) console.log("✓ Keine");
else unusedAssets.slice(0,100).forEach(u => console.log("-", u));

logSection("Unbenutzt – PHP");
if (!unusedPhp.length) console.log("✓ Keine");
else unusedPhp.slice(0,100).forEach(u => console.log("-", u));

process.exit(process.exitCode ?? 0);
