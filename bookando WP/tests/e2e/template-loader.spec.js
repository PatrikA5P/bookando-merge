/* globals process */
import path from 'node:path'
import { execFileSync } from 'node:child_process'
import { test, expect } from '@playwright/test'

function runScenario(scenario) {
  const script = path.join(process.cwd(), 'tests/e2e/template-runner.php')
  const output = execFileSync('php', [script, scenario], { encoding: 'utf8' })
  return output.trim()
}

test('falls back to plugin template when theme override is missing', async () => {
  expect(runScenario('plugin')).toBe('plugin-template')
})

test('prefers theme override when present', async () => {
  expect(runScenario('override')).toBe('theme-template')
})
