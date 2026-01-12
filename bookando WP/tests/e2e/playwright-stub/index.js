const tests = []

export function test(name, fn) {
  if (typeof name !== 'string' || typeof fn !== 'function') {
    throw new TypeError('playwright-stub: test(name, fn) requires a string name and function callback')
  }
  tests.push({ name, fn })
}

export function expect(received) {
  return {
    toBe(expected) {
      if (received !== expected) {
        throw new Error(`Expected ${expected} but received ${received}`)
      }
    },
  }
}

export function clearTests() {
  tests.length = 0
}

export async function runTests() {
  const results = []
  for (const { name, fn } of tests) {
    try {
      await fn()
      console.log(`✓ ${name}`)
      results.push({ name, passed: true })
    } catch (error) {
      console.error(`✗ ${name}`)
      console.error(error instanceof Error ? error.stack : error)
      results.push({ name, passed: false, error })
    }
  }
  clearTests()
  return results
}

export function getRegisteredTests() {
  return [...tests]
}
