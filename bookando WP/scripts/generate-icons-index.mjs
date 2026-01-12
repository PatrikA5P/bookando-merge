// scripts/generate-icons-index.mjs

import fs from 'fs'

const iconsDir = './src/Core/Design/assets/icons'
const indexFile = './src/Core/Design/assets/icons/index.ts'   // <<== KORRIGIERT: in icons-Ordner!
const files = fs.readdirSync(iconsDir).filter(f => f.endsWith('.svg'))

function iconVar(filename) {
  // z.B. "user-plus.svg" -> "UserPlus"
  const name = filename.replace('.svg', '')
  return (
    name
      .split('-')
      .map(part => part.charAt(0).toUpperCase() + part.slice(1))
      .join('')
  )
}
function iconKey(filename) {
  // z.B. "user-plus.svg" -> "user-plus"
  return filename.replace('.svg', '')
}

const imports = files
  .map(f => `import ${iconVar(f)} from './${f}?component'`)
  .join('\n')

const exports =
  `export const icons = {\n` +
  files.map(f => `  '${iconKey(f)}': ${iconVar(f)},`).join('\n') +
  `\n}`

const content = `${imports}\n\n${exports}\n`

fs.writeFileSync(indexFile, content)
console.log('✅ icons/index.ts generated!')
