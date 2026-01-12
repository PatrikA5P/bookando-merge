#!/usr/bin/env node

import { promises as fs } from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import process from 'node:process'
import Ajv from 'ajv'

const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)
const ROOT_DIR = path.resolve(__dirname, '..')
const MODULES_DIR = path.resolve(ROOT_DIR, 'src', 'modules')
const SCHEMA_PATH = path.resolve(ROOT_DIR, 'docs', 'module-schema.json')

async function loadJson(filePath) {
  const raw = await fs.readFile(filePath, 'utf8')
  try {
    return JSON.parse(raw)
  } catch (error) {
    throw new Error(`Could not parse JSON from ${path.relative(ROOT_DIR, filePath)}: ${error.message}`)
  }
}

const REQUIRED_FILES = [
  'Module.php',
  path.join('Api', 'Api.php'),
  'RestHandler.php',
  path.join('Admin', 'Admin.php'),
  path.join('Templates', 'admin-vue-container.php'),
  path.join('assets', 'vue', 'main.ts'),
  path.join('assets', 'css', 'admin.scss'),
  'module.json',
]

const TODO_FILE_EXTENSIONS = new Set(['.php', '.ts', '.js', '.vue', '.scss', '.css'])

const REST_HANDLER_IMPORT_CHECKS = [
  {
    usage: /RestModuleGuard::/,
    importPattern: /use\s+Bookando\\Core\\Dispatcher\\RestModuleGuard\s*;/,
    message:
      'RestHandler.php muss `use Bookando\\Core\\Dispatcher\\RestModuleGuard;` importieren.',
  },
  {
    usage: /WP_REST_Server::/,
    importPattern: /use\s+WP_REST_Server\s*;/,
    message: 'RestHandler.php muss `use WP_REST_Server;` importieren.',
  },
]

const MODULE_HOOK_CHECKS = [
  {
    test: /registerAdminHooks\s*\(/,
    message: 'Module.php muss `registerAdminHooks(...)` aufrufen.',
  },
  {
    test: /add_action\(\s*['"]bookando_register_module_menus['"]/,
    message: 'Module.php muss das Admin-Menü über `bookando_register_module_menus` registrieren.',
  },
  {
    test: /add_action\(\s*['"]admin_enqueue_scripts['"]/,
    message: 'Module.php muss Admin-Assets über `admin_enqueue_scripts` einbinden.',
  },
  {
    test: /registerRestRoutes\s*\(/,
    message: 'Module.php muss REST-Routen via `registerRestRoutes(...)` anmelden.',
  },
]

function collectModuleError(modulesWithErrors, moduleSlug, message) {
  if (!modulesWithErrors.has(moduleSlug)) {
    modulesWithErrors.set(moduleSlug, [])
  }
  modulesWithErrors.get(moduleSlug).push(message)
}

async function ensureRequiredFiles(moduleSlug, moduleDir, modulesWithErrors) {
  await Promise.all(
    REQUIRED_FILES.map(async relativePath => {
      const absolutePath = path.join(moduleDir, relativePath)
      try {
        await fs.access(absolutePath)
      } catch {
        collectModuleError(
          modulesWithErrors,
          moduleSlug,
          `Pflichtdatei fehlt: ${relativePath}`,
        )
      }
    }),
  )
}

async function findTodos(moduleSlug, moduleDir, modulesWithErrors) {
  async function walk(currentRelative = '.') {
    const currentDir = path.join(moduleDir, currentRelative)
    const entries = await fs.readdir(currentDir, { withFileTypes: true })
    for (const entry of entries) {
      if (entry.name.startsWith('.')) {
        continue
      }
      const relativePath = currentRelative === '.' ? entry.name : path.join(currentRelative, entry.name)
      const absolutePath = path.join(moduleDir, relativePath)
      if (entry.isDirectory()) {
        if (['node_modules', 'dist', 'tests', '__mocks__'].includes(entry.name)) {
          continue
        }
        await walk(relativePath)
        continue
      }

      const extension = path.extname(entry.name).toLowerCase()
      if (!TODO_FILE_EXTENSIONS.has(extension)) {
        continue
      }

      const content = await fs.readFile(absolutePath, 'utf8')
      const lines = content.split(/\r?\n/)
      lines.forEach((line, index) => {
        if (line.toUpperCase().includes('TODO')) {
          collectModuleError(
            modulesWithErrors,
            moduleSlug,
            `TODO-Platzhalter gefunden in ${relativePath}:${index + 1}`,
          )
        }
      })
    }
  }

  try {
    await walk()
  } catch (error) {
    collectModuleError(modulesWithErrors, moduleSlug, `Fehler beim Prüfen auf TODO-Kommentare: ${error.message}`)
  }
}

async function validateModuleHooks(moduleSlug, moduleDir, modulesWithErrors) {
  const modulePhpPath = path.join(moduleDir, 'Module.php')
  let source
  try {
    source = await fs.readFile(modulePhpPath, 'utf8')
  } catch (error) {
    collectModuleError(
      modulesWithErrors,
      moduleSlug,
      `Module.php konnte nicht gelesen werden: ${error.message}`,
    )
    return
  }

  for (const { test, message } of MODULE_HOOK_CHECKS) {
    if (!test.test(source)) {
      collectModuleError(modulesWithErrors, moduleSlug, message)
    }
  }
}

async function validateRestHandlerImports(moduleSlug, moduleDir, modulesWithErrors) {
  const restHandlerPath = path.join(moduleDir, 'RestHandler.php')
  let source
  try {
    source = await fs.readFile(restHandlerPath, 'utf8')
  } catch (error) {
    collectModuleError(
      modulesWithErrors,
      moduleSlug,
      `RestHandler.php konnte nicht gelesen werden: ${error.message}`,
    )
    return
  }

  for (const { usage, importPattern, message } of REST_HANDLER_IMPORT_CHECKS) {
    if (!usage.test(source)) {
      continue
    }

    if (!importPattern.test(source)) {
      collectModuleError(modulesWithErrors, moduleSlug, message)
    }
  }
}

async function main() {
  const schema = await loadJson(SCHEMA_PATH)
  const ajv = new Ajv({ allErrors: true })
  const validate = ajv.compile(schema)

  const entries = await fs.readdir(MODULES_DIR, { withFileTypes: true })
  const modulesWithErrors = new Map()

  for (const entry of entries) {
    if (!entry.isDirectory()) {
      continue
    }

    const moduleSlug = entry.name
    const moduleDir = path.join(MODULES_DIR, moduleSlug)
    const manifestPath = path.join(moduleDir, 'module.json')
    try {
      const manifest = await loadJson(manifestPath)
      const valid = validate(manifest)
      if (!valid) {
        const errors = validate.errors ?? []
        errors.forEach(error => {
          const errorPath = error.instancePath || error.dataPath || '/'
          collectModuleError(
            modulesWithErrors,
            moduleSlug,
            `${errorPath || '/'} ${error.message}`,
          )
        })
      }
    } catch (error) {
      collectModuleError(modulesWithErrors, moduleSlug, error.message)
      continue
    }

    await ensureRequiredFiles(moduleSlug, moduleDir, modulesWithErrors)
    await findTodos(moduleSlug, moduleDir, modulesWithErrors)
    await validateModuleHooks(moduleSlug, moduleDir, modulesWithErrors)
    await validateRestHandlerImports(moduleSlug, moduleDir, modulesWithErrors)
  }

  if (modulesWithErrors.size > 0) {
    for (const [moduleSlug, messages] of modulesWithErrors.entries()) {
      console.error(`\n❌ ${moduleSlug}`)
      messages.forEach(message => {
        console.error(`  - ${message}`)
      })
    }
    console.error('\nModule validation failed.')
    process.exitCode = 1
    return
  }

  console.log('✅ Alle Module entsprechen docs/module-schema.json und Governance-Richtlinien')
}

main().catch(error => {
  console.error(error)
  process.exit(1)
})
