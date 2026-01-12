// scripts/cleanup.mjs
import fs from 'fs';
import path from 'path';

const file = path.resolve('src/Core/Design/assets/vue/dist/ignore.js');

if (fs.existsSync(file)) {
  fs.unlinkSync(file);
  console.log('✅ ignore.js entfernt');
} else {
  console.log('ℹ️ ignore.js nicht gefunden – nichts zu löschen');
}
