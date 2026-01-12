#!/usr/bin/env node

import fs from 'fs'
import path from 'path'
import { execSync, spawnSync } from 'child_process'
import inquirer from 'inquirer'
import open from 'open'

const slug = process.argv.find(arg => arg.startsWith('--module='))?.split('=')[1]
if (!slug) {
  console.error('❌ Bitte rufe das Review mit --module=MODULNAME auf!')
  process.exit(1)
}
const basePath = path.resolve('src/modules', slug)
const readme = path.join(basePath, 'README.md')
if (!fs.existsSync(readme)) {
  console.error('❌ Modul/README nicht gefunden:', readme)
  process.exit(1)
}
const content = fs.readFileSync(readme, 'utf-8')

// Checkliste parsen
const checklistMatch = content.match(/## ✅ Review- & Test-Checkliste([\s\S]+?)---/i)
if (!checklistMatch) {
  console.error('❌ Keine Checkliste im README gefunden!')
  process.exit(1)
}
const rawItems = checklistMatch[1].split('\n').filter(line => line.trim().startsWith('- [ ]'))
const items = rawItems.map(line => line.replace(/^- \[ \]/, '').trim())

// Utility: Lint/Tests/Build ausführen
function runCommand(cmd, opts = {}) {
  try {
    const r = execSync(cmd, { stdio: 'pipe', ...opts })
    return { ok: true, out: r.toString() }
  } catch (err) {
    return { ok: false, out: err.stdout?.toString() + err.stderr?.toString() }
  }
}

async function review() {
  const results = []
  console.log('\n🔎 Starte automatisierte Modul-Review...\n')

  // 1. Lint-Check (eslint)
  if (fs.existsSync('node_modules/.bin/eslint')) {
    process.stdout.write('➡️  Linting (eslint): ')
    const lint = runCommand(`npx eslint ${basePath}/assets/vue --ext .js,.vue`)
    results.push({ step: 'Lint', ok: lint.ok })
    if (!lint.ok) console.log('\n❌ Lint-Fehler:\n', lint.out)
    else console.log('✅ OK')
  }

  // 2. Unit-Tests (vitest)
  if (fs.existsSync('node_modules/.bin/vitest')) {
    process.stdout.write('➡️  Unit-Tests (vitest): ')
    const unit = runCommand(`npx vitest run --dir ${basePath}/assets/vue`)
    results.push({ step: 'Unit-Tests', ok: unit.ok })
    if (!unit.ok) console.log('\n❌ Fehler bei Unit-Tests:\n', unit.out)
    else console.log('✅ OK')
  }

  // 3. E2E (Playwright)
  if (fs.existsSync('node_modules/.bin/playwright')) {
    process.stdout.write('➡️  E2E-Tests (playwright): ')
    const e2ePath = path.join(basePath, 'e2e')
    if (fs.existsSync(e2ePath)) {
      const e2e = runCommand(`npx playwright test ${e2ePath}`)
      results.push({ step: 'E2E-Tests', ok: e2e.ok })
      if (!e2e.ok) console.log('\n❌ Fehler bei E2E-Tests:\n', e2e.out)
      else console.log('✅ OK')
    }
  }

  // 4. Build-Check (vite build)
  process.stdout.write('➡️  Build (vite): ')
  const build = runCommand('npm run build')
  results.push({ step: 'Build', ok: build.ok })
  if (!build.ok) console.log('\n❌ Fehler beim Build:\n', build.out)
  else console.log('✅ OK')

  // 5. Accessibility Quick-Check (Suche nach aria- und role-Attributen in Hauptkomponenten)
  const mainVueFile = path.join(basePath, 'assets/vue/views/Admin.vue')
  let a11yPassed = true
  if (fs.existsSync(mainVueFile)) {
    const vueContent = fs.readFileSync(mainVueFile, 'utf-8')
    if (!vueContent.match(/aria-|role=/)) {
      console.log('⚠️  Accessibility-Hinweis: In Admin.vue fehlen explizite aria-/role-Attribute.')
      a11yPassed = false
    } else {
      console.log('✅ Accessibility-Hauptkomponente enthält aria-/role-Attribute.')
    }
  }
  results.push({ step: 'Accessibility', ok: a11yPassed })

  // 6. Schrittweise Checkliste (README)
  console.log('\n== Manuelle Review-Checkliste ==\n')
  const manualResults = []
  for (const item of items) {
    const { ok } = await inquirer.prompt({
      type: 'confirm',
      name: 'ok',
      message: item,
      default: false,
    })
    manualResults.push({ item, ok })
  }

  // 7. Coverage-Bericht öffnen (optional)
  if (fs.existsSync('coverage/index.html')) {
    const { openCoverage } = await inquirer.prompt({
      type: 'confirm',
      name: 'openCoverage',
      message: 'Coverage-Report im Browser öffnen?',
      default: false
    })
    if (openCoverage) await open('coverage/index.html')
  }

  // 8. Abschluss-Report
  const allPassed = results.every(r => r.ok) && manualResults.every(r => r.ok)
  const passed = manualResults.filter(r => r.ok).length
  console.log('\n== Review-Abschluss ==')
  if (allPassed) {
    console.log(`\n✅ Alle automatischen und manuellen Checks bestanden!`)
  } else {
    console.log('\n❌ Fehler/Hinweise:')
    results.filter(r => !r.ok).forEach(r => console.log('❌', r.step))
    manualResults.filter(r => !r.ok).forEach(r => console.log('❌', r.item))
  }
  fs.writeFileSync(
    path.join(basePath, 'REVIEW_REPORT.txt'),
    [
      '# Automatisierte Checks:',
      ...results.map(r => `${r.ok ? '[x]' : '[ ]'} ${r.step}`),
      '',
      '# Manuelle Checkliste:',
      ...manualResults.map(r => `${r.ok ? '[x]' : '[ ]'} ${r.item}`),
    ].join('\n')
  )
  console.log('\n📋 Review-Report gespeichert in REVIEW_REPORT.txt')
  process.exit(allPassed ? 0 : 1)
}

review()
