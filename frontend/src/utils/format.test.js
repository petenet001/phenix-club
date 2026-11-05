import { describe, expect, it } from 'vitest'
import { formatDate, formatFcfa } from './format'

describe('format', () => {
  it('formate les montants en FCFA avec séparateur de milliers', () => {
    expect(formatFcfa(15000)).toBe('15 000 FCFA')
    expect(formatFcfa(0)).toBe('0 FCFA')
    expect(formatFcfa('1250000')).toBe('1 250 000 FCFA')
  })

  it('formate les dates en jj/mm/aaaa', () => {
    expect(formatDate('2026-11-01')).toBe('01/11/2026')
    expect(formatDate(null)).toBe('-')
  })
})
