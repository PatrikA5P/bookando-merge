#!/usr/bin/env node
/* globals process */
import fs from 'node:fs'
import path from 'node:path'
import { pathToFileURL } from 'node:url'
import { clearTests, runTests } from '../index.js'

function collectSpecFiles(startDir) {
  const entries = fs.existsSync(startDir) ? fs.readdirSync(startDir, { withFileTypes: true }) : []
  const files = []
  for (const entry of entries) {
    const fullPath = path.join(startDir, entry.name)
    if (entry.isDirectory()) {
      files.push(...collectSpecFiles(fullPath))
    } else if (entry.isFile() && entry.name.endsWith('.spec.js')) {
      files.push(fullPath)
    }
  }
  return files
}

async function run() {
  const cwd = process.cwd()
  const testDir = path.join(cwd, 'tests/e2e')
  const specs = collectSpecFiles(testDir)

  if (specs.length === 0) {
    console.log('No Playwright stub tests found in tests/e2e')
    return
  }

  let exitCode = 0

  for (const spec of specs) {
    clearTests()
    await import(pathToFileURL(spec).href)
    console.log(`\nRunning ${path.relative(cwd, spec)}`)
    const results = await runTests()
    if (results.some((result) => !result.passed)) {
      exitCode = 1
    }
  }

  process.exit(exitCode)
}

run().catch((error) => {
  console.error(error)
  process.exit(1)
})
