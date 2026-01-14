import { afterEach, beforeEach, describe, expect, it } from 'vitest'

import type { AvailabilitySlot } from '../../api/ResourcesApi'
import { formatAvailabilitySlot } from '../formatters'
import { setLocale } from '@core/Locale'

const baseSlot: AvailabilitySlot = {
  id: 'slot-1',
  date: '2025-03-15',
  start: '09:00',
  end: '11:30',
  capacity: 5,
  notes: 'Notiz',
}

describe('formatAvailabilitySlot', () => {
  beforeEach(() => {
    setLocale('de')
  })

  afterEach(() => {
    setLocale('de')
  })

  it('formats availability information for german locale', () => {
    expect(formatAvailabilitySlot(baseSlot, 'de-CH')).toBe('15.03.2025 · 09:00 – 11:30 · 5 · Notiz')
  })

  it('formats availability information for english locale', () => {
    setLocale('en')
    const result = formatAvailabilitySlot({ ...baseSlot, notes: '' }, 'en-US')
    expect(result).toBe('2025/03/15 · 09:00 – 11:30 · 5')
  })

  it('falls back to placeholders when parts are missing', () => {
    const partialSlot: AvailabilitySlot = {
      id: 'slot-2',
      date: null,
      start: null,
      end: null,
      capacity: null,
      notes: '',
    }

    expect(formatAvailabilitySlot(partialSlot, 'de-DE')).toBe('– · – · –')
  })
})
