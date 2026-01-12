// scripts/generate-available-languages.mjs
import fs from 'fs';

const i18nDir = './src/Core/Design/i18n';
const outputFile = './src/Core/Design/data/available-languages.ts';

const entries = fs.readdirSync(i18nDir)
  .filter(f => f.endsWith('.json'))
  .map(f => f.replace(/\.json$/, ''));

const output = `// Automatisch generiert: Liste aller verfügbaren App-Sprachen\n` +
               `export const AVAILABLE_LANGUAGES = ${JSON.stringify(entries, null, 2)};\n`;

fs.writeFileSync(outputFile, output, 'utf-8');
console.log(`✅ ${entries.length} Sprachen nach ${outputFile} geschrieben.`);
