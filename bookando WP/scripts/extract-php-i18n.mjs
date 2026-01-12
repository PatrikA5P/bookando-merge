#!/usr/bin/env node

import fs from 'node:fs';
import path from 'node:path';
import { glob } from 'glob';
import engine from 'php-parser';

const projectRoot = process.cwd();

const parser = new engine({
  parser: {
    extractDoc: true,
    php7: true,
    suppressErrors: true,
  },
  ast: {
    withPositions: true,
    withLocations: true,
  },
  lexer: {
    all_tokens: true,
    comment_tokens: true,
    mode_eval: true,
  },
});

const simpleFns = new Set(['__', '_e', 'esc_html__', 'esc_attr__', 'esc_html_e', 'esc_attr_e', 'translate']);
const contextFns = new Set(['_x', '_ex', 'esc_html_x', 'esc_attr_x']);
const pluralFns = new Set(['_n']);
const pluralContextFns = new Set(['_nx']);
const allFallbackFns = new Set([...simpleFns, ...contextFns, ...pluralFns, ...pluralContextFns]);

function getFunctionName(node) {
  if (!node) return null;
  if (node.kind === 'identifier') return node.name;
  if (node.kind === 'name') return node.name;
  if (node.kind === 'nslookup') {
    if (node.name && node.name.kind === 'identifier') return node.name.name;
  }
  if (node.kind === 'staticlookup') {
    if (node.offset && node.offset.kind === 'identifier') return node.offset.name;
  }
  return null;
}

function getLiteralString(arg) {
  if (!arg) return null;
  if (arg.kind === 'string') return String(arg.value);
  if (arg.kind === 'bin') {
    const left = getLiteralString(arg.left);
    const right = getLiteralString(arg.right);
    if (left !== null && right !== null) return left + right;
    return null;
  }
  return null;
}

function isBookandoDomain(arg) {
  if (!arg) return false;
  const domain = getLiteralString(arg);
  return domain === 'bookando';
}

function addEntry(map, key, updater) {
  if (!map.has(key)) {
    map.set(key, updater({ refs: [] }));
  } else {
    const existing = map.get(key);
    updater(existing);
  }
}

function escapePoString(str) {
  return String(str).replace(/\\/g, '\\\\').replace(/"/g, '\\"');
}

function formatEntry(entry) {
  const lines = [];
  if (entry.refs.length > 0) {
    const refLine = entry.refs.map(r => r.replace(/\\/g, '/')).join(' ');
    lines.push(`#: ${refLine}`);
  }
  if (entry.msgctxt !== undefined) {
    lines.push(`msgctxt "${escapePoString(entry.msgctxt)}"`);
  }
  lines.push(`msgid "${escapePoString(entry.msgid)}"`);
  if (entry.msgid_plural !== undefined) {
    lines.push(`msgid_plural "${escapePoString(entry.msgid_plural)}"`);
    lines.push('msgstr[0] ""');
    lines.push('msgstr[1] ""');
  } else {
    lines.push('msgstr ""');
  }
  return lines.join('\n');
}

function relative(file) {
  return path.posix.join(...path.relative(projectRoot, file).split(path.sep));
}

function decodeEscape(quote, next) {
  switch (next) {
    case '\\': return '\\';
    case 'n': return '\n';
    case 'r': return '\r';
    case 't': return '\t';
    case '"': return '"';
    case "'": return "'";
    case '$': return '$';
    default:
      if (quote === "'") {
        if (next === "'" || next === '\\') return next;
        return `\\${next}`;
      }
      return next;
  }
}

function readPhpString(code, idx) {
  const quote = code[idx];
  if (quote !== "'" && quote !== '"') return null;
  let i = idx + 1;
  let value = '';
  while (i < code.length) {
    const ch = code[i];
    if (ch === '\\') {
      if (i + 1 >= code.length) return null;
      const next = code[i + 1];
      value += decodeEscape(quote, next);
      i += 2;
      continue;
    }
    if (ch === quote) {
      return { value, next: i + 1 };
    }
    value += ch;
    i++;
  }
  return null;
}

function skipWhitespaceAndComments(code, idx) {
  let i = idx;
  while (i < code.length) {
    const ch = code[i];
    if (ch === '/' && code[i + 1] === '/') {
      i += 2;
      while (i < code.length && code[i] !== '\n') i++;
      continue;
    }
    if (ch === '/' && code[i + 1] === '*') {
      i += 2;
      while (i + 1 < code.length && !(code[i] === '*' && code[i + 1] === '/')) i++;
      i += 2;
      continue;
    }
    if (ch === '#') {
      i++;
      while (i < code.length && code[i] !== '\n') i++;
      continue;
    }
    if (/\s/.test(ch)) {
      i++;
      continue;
    }
    break;
  }
  return i;
}

function parseStringLiteral(code, idx) {
  const start = skipWhitespaceAndComments(code, idx);
  const str = readPhpString(code, start);
  if (!str) return null;
  return { value: str.value, next: str.next };
}

function skipArgument(code, idx) {
  let i = skipWhitespaceAndComments(code, idx);
  let depth = 0;
  while (i < code.length) {
    const ch = code[i];
    if (ch === "'" || ch === '"') {
      const str = readPhpString(code, i);
      if (!str) return code.length;
      i = str.next;
      continue;
    }
    if (ch === '(' || ch === '[' || ch === '{') {
      depth++;
      i++;
      continue;
    }
    if (ch === ')' ) {
      if (depth === 0) return i;
      depth--;
      i++;
      continue;
    }
    if (ch === ',' && depth === 0) {
      return i + 1;
    }
    if (ch === '/' && code[i + 1] === '/') {
      i += 2;
      while (i < code.length && code[i] !== '\n') i++;
      continue;
    }
    if (ch === '/' && code[i + 1] === '*') {
      i += 2;
      while (i + 1 < code.length && !(code[i] === '*' && code[i + 1] === '/')) i++;
      i += 2;
      continue;
    }
    if (ch === '#') {
      i++;
      while (i < code.length && code[i] !== '\n') i++;
      continue;
    }
    i++;
  }
  return i;
}

function fallbackExtract(code, file, entries) {
  const makeLine = idx => code.slice(0, idx).split(/\r?\n/).length;
  const pattern = /\b(__|_e|esc_html__|esc_attr__|esc_html_e|esc_attr_e|translate|_x|_ex|esc_html_x|esc_attr_x|_n|_nx)\s*\(/g;
  let match;
  while ((match = pattern.exec(code)) !== null) {
    const fn = match[1];
    if (!allFallbackFns.has(fn)) continue;
    const line = makeLine(match.index);
    let idx = match.index + match[0].length;
    const first = parseStringLiteral(code, idx);
    if (!first) continue;
    idx = skipWhitespaceAndComments(code, first.next);
    if (code[idx] !== ',') continue;
    idx++;

    if (simpleFns.has(fn)) {
      const domain = parseStringLiteral(code, idx);
      if (!domain || domain.value !== 'bookando') continue;
      const key = JSON.stringify({ msgid: first.value });
      addEntry(entries, key, entry => {
        entry.msgid = first.value;
        entry.refs.push(`${relative(file)}:${line}`);
        return entry;
      });
      continue;
    }

    if (contextFns.has(fn)) {
      const context = parseStringLiteral(code, idx);
      if (!context) continue;
      idx = skipWhitespaceAndComments(code, context.next);
      if (code[idx] !== ',') continue;
      idx++;
      const domain = parseStringLiteral(code, idx);
      if (!domain || domain.value !== 'bookando') continue;
      const key = JSON.stringify({ msgid: first.value, msgctxt: context.value });
      addEntry(entries, key, entry => {
        entry.msgid = first.value;
        entry.msgctxt = context.value;
        entry.refs.push(`${relative(file)}:${line}`);
        return entry;
      });
      continue;
    }

    if (pluralFns.has(fn)) {
      const plural = parseStringLiteral(code, idx);
      if (!plural) continue;
      idx = skipArgument(code, plural.next);
      const domain = parseStringLiteral(code, idx);
      if (!domain || domain.value !== 'bookando') continue;
      const key = JSON.stringify({ msgid: first.value, msgid_plural: plural.value });
      addEntry(entries, key, entry => {
        entry.msgid = first.value;
        entry.msgid_plural = plural.value;
        entry.refs.push(`${relative(file)}:${line}`);
        return entry;
      });
      continue;
    }

    if (pluralContextFns.has(fn)) {
      const plural = parseStringLiteral(code, idx);
      if (!plural) continue;
      idx = skipArgument(code, plural.next);
      const context = parseStringLiteral(code, idx);
      if (!context) continue;
      idx = skipWhitespaceAndComments(code, context.next);
      if (code[idx] !== ',') continue;
      idx++;
      const domain = parseStringLiteral(code, idx);
      if (!domain || domain.value !== 'bookando') continue;
      const key = JSON.stringify({ msgid: first.value, msgid_plural: plural.value, msgctxt: context.value });
      addEntry(entries, key, entry => {
        entry.msgid = first.value;
        entry.msgid_plural = plural.value;
        entry.msgctxt = context.value;
        entry.refs.push(`${relative(file)}:${line}`);
        return entry;
      });
    }
  }
}

async function main() {
  const pattern = [
    'bookando.php',
    'src/**/*.php',
    'scripts/**/*.php',
    'tests/**/*.php',
  ];
  const files = await glob(pattern, { nodir: true, absolute: true });
  const entries = new Map();
  const skipped = [];
  const fallbackFiles = [];

  for (const file of files) {
    const code = fs.readFileSync(file, 'utf8');
    let ast;
    let parsed = false;
    try {
      ast = parser.parseCode(code, file);
      parsed = true;
    } catch (err) {
      fallbackExtract(code, file, entries);
      fallbackFiles.push(relative(file));
    }

    if (!parsed) {
      continue;
    }

    const stack = [ast];
    while (stack.length) {
      const node = stack.pop();
      if (!node || typeof node !== 'object') continue;
      if (Array.isArray(node)) {
        stack.push(...node);
        continue;
      }
      if (node.kind === 'call') {
        const fnName = getFunctionName(node.what);
        if (!fnName) {
          // noop
        } else if (simpleFns.has(fnName)) {
          if (!isBookandoDomain(node.arguments?.[1])) continue;
          const text = getLiteralString(node.arguments?.[0]);
          if (text === null) {
            skipped.push({ file, line: node.loc?.start?.line || 0, fn: fnName });
          } else {
            const key = JSON.stringify({ msgid: text });
            addEntry(entries, key, entry => {
              entry.msgid = text;
              entry.refs.push(`${relative(file)}:${node.loc?.start?.line || 0}`);
              return entry;
            });
          }
        } else if (contextFns.has(fnName)) {
          if (!isBookandoDomain(node.arguments?.[2])) continue;
          const text = getLiteralString(node.arguments?.[0]);
          const context = getLiteralString(node.arguments?.[1]);
          if (text === null || context === null) {
            skipped.push({ file, line: node.loc?.start?.line || 0, fn: fnName });
          } else {
            const key = JSON.stringify({ msgid: text, msgctxt: context });
            addEntry(entries, key, entry => {
              entry.msgid = text;
              entry.msgctxt = context;
              entry.refs.push(`${relative(file)}:${node.loc?.start?.line || 0}`);
              return entry;
            });
          }
        } else if (pluralFns.has(fnName)) {
          if (!isBookandoDomain(node.arguments?.[3])) continue;
          const singular = getLiteralString(node.arguments?.[0]);
          const plural = getLiteralString(node.arguments?.[1]);
          if (singular === null || plural === null) {
            skipped.push({ file, line: node.loc?.start?.line || 0, fn: fnName });
          } else {
            const key = JSON.stringify({ msgid: singular, msgid_plural: plural });
            addEntry(entries, key, entry => {
              entry.msgid = singular;
              entry.msgid_plural = plural;
              entry.refs.push(`${relative(file)}:${node.loc?.start?.line || 0}`);
              return entry;
            });
          }
        } else if (pluralContextFns.has(fnName)) {
          if (!isBookandoDomain(node.arguments?.[4])) continue;
          const singular = getLiteralString(node.arguments?.[0]);
          const plural = getLiteralString(node.arguments?.[1]);
          const context = getLiteralString(node.arguments?.[3]);
          if (singular === null || plural === null || context === null) {
            skipped.push({ file, line: node.loc?.start?.line || 0, fn: fnName });
          } else {
            const key = JSON.stringify({ msgid: singular, msgid_plural: plural, msgctxt: context });
            addEntry(entries, key, entry => {
              entry.msgid = singular;
              entry.msgid_plural = plural;
              entry.msgctxt = context;
              entry.refs.push(`${relative(file)}:${node.loc?.start?.line || 0}`);
              return entry;
            });
          }
        }
      }

      for (const value of Object.values(node)) {
        if (!value) continue;
        if (Array.isArray(value)) stack.push(...value);
        else if (value && typeof value === 'object') stack.push(value);
      }
    }
  }

  const sorted = Array.from(entries.values()).sort((a, b) => {
    const ctxA = a.msgctxt ?? '';
    const ctxB = b.msgctxt ?? '';
    if (ctxA !== ctxB) return ctxA.localeCompare(ctxB);
    if (a.msgid !== b.msgid) return a.msgid.localeCompare(b.msgid);
    const pluralA = a.msgid_plural ?? '';
    const pluralB = b.msgid_plural ?? '';
    return pluralA.localeCompare(pluralB);
  });

  const now = new Date();
  const isoDate = now.toISOString().replace(/T.*/, '');
  const header = [
    'msgid ""',
    'msgstr ""',
    '"Project-Id-Version: bookando\\n"',
    `"POT-Creation-Date: ${isoDate} 00:00+0000\\n"`,
    `"PO-Revision-Date: ${isoDate} 00:00+0000\\n"`,
    '"Language: \\n"',
    '"MIME-Version: 1.0\\n"',
    '"Content-Type: text/plain; charset=UTF-8\\n"',
    '"Content-Transfer-Encoding: 8bit\\n"',
    '"Plural-Forms: nplurals=2; plural=(n != 1);\\n"',
    '',
  ].join('\n');

  const body = sorted.map(formatEntry).join('\n\n');
  const potPath = path.join(projectRoot, 'languages', 'bookando.pot');
  fs.writeFileSync(potPath, `${header}\n${body}\n`, 'utf8');

  console.log(`Extracted ${sorted.length} entries to ${relative(potPath)}`);
  if (fallbackFiles.length) {
    console.warn(`Used regex fallback for ${fallbackFiles.length} file(s):`);
    fallbackFiles.slice(0, 10).forEach(f => console.warn(`  ${f}`));
    if (fallbackFiles.length > 10) console.warn('  ...');
  }
  if (skipped.length) {
    console.warn(`Skipped ${skipped.length} non-literal strings:`);
    for (const item of skipped.slice(0, 20)) {
      console.warn(`  ${relative(item.file)}:${item.line} (${item.fn})`);
    }
    if (skipped.length > 20) {
      console.warn('  ...');
    }
  }
}

main().catch(err => {
  console.error(err);
  process.exit(1);
});
