const fs = require('fs');
const glob = require('glob');

const searchDir = 'C:/Users/User/Local Sites/bookando-site/app/public/wp-content/plugins/bookando/src/Core/Design/assets/scss';
const excludeFiles = [
  '_variables', '_tokens', '_mixins', '_helpers', '_utilities'
];

const patterns = [
  /font-size:\s*\d/i,
  /margin(-top|-bottom|-left|-right)?:\s*\d/i,
  /padding(-top|-bottom|-left|-right)?:\s*\d/i,
  /border-radius:\s*\d/i,
  /color:\s*#/i,
  /background:\s*#/i
];

// **HIER die Änderung:**
const files = glob.sync(`${searchDir}/**/*.scss`, {});
files.forEach(file => {
  if (excludeFiles.some(name => file.endsWith(`${name}.scss`))) return;
  const lines = fs.readFileSync(file, 'utf-8').split('\n');
  lines.forEach((line, idx) => {
    patterns.forEach(pattern => {
      if (pattern.test(line)) {
        console.log(`${file}:${idx+1}: ${line.trim()}`);
      }
    });
  });
});
