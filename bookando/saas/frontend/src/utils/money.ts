/**
 * Money Utility — Integer Minor Units (Rappen/Cents)
 *
 * Alle Geldbetraege werden als Integer in der kleinsten Waehrungseinheit
 * gespeichert und transportiert (z.B. 8500 = CHF 85.00).
 *
 * Diese Utility-Funktionen verhindern Float-Rundungsfehler.
 */

// ============================================================================
// CONVERSION
// ============================================================================

/**
 * Konvertiert einen Major-Unit-Betrag (z.B. 85.00) in Minor Units (z.B. 8500).
 * Rundet auf die naechste ganze Zahl um Float-Ungenauigkeiten zu vermeiden.
 */
export function toMinorUnits(majorAmount: number): number {
  return Math.round(majorAmount * 100);
}

/**
 * Konvertiert Minor Units (z.B. 8500) in Major Units (z.B. 85.00).
 */
export function toMajorUnits(minorAmount: number): number {
  return minorAmount / 100;
}

// ============================================================================
// FORMATTING
// ============================================================================

const CURRENCY_CONFIG: Record<string, { locale: string; symbol: string; decimals: number }> = {
  CHF: { locale: 'de-CH', symbol: 'CHF', decimals: 2 },
  EUR: { locale: 'de-DE', symbol: '\u20AC', decimals: 2 },
  USD: { locale: 'en-US', symbol: '$', decimals: 2 },
};

/**
 * Formatiert Minor Units als Waehrungsstring.
 *
 * @example
 * formatMoney(8500, 'CHF')       // "CHF 85.00"
 * formatMoney(8500, 'CHF', true)  // "85.00"
 * formatMoney(0, 'CHF')           // "CHF 0.00"
 * formatMoney(-1500, 'CHF')       // "-CHF 15.00"
 */
export function formatMoney(minorAmount: number, currency: string = 'CHF', omitSymbol: boolean = false): string {
  const config = CURRENCY_CONFIG[currency] || CURRENCY_CONFIG.CHF;
  const majorAmount = toMajorUnits(minorAmount);
  const isNegative = majorAmount < 0;
  const absAmount = Math.abs(majorAmount);

  const formatted = absAmount.toFixed(config.decimals);

  if (omitSymbol) {
    return isNegative ? `-${formatted}` : formatted;
  }

  const prefix = isNegative ? '-' : '';
  return `${prefix}${config.symbol}\u00A0${formatted}`;
}

/**
 * Formatiert Minor Units als kompakte Kurzform.
 *
 * @example
 * formatMoneyShort(850000, 'CHF')  // "CHF 8'500"
 * formatMoneyShort(8500, 'CHF')    // "CHF 85"
 */
export function formatMoneyShort(minorAmount: number, currency: string = 'CHF'): string {
  const config = CURRENCY_CONFIG[currency] || CURRENCY_CONFIG.CHF;
  const majorAmount = Math.abs(toMajorUnits(minorAmount));
  const prefix = minorAmount < 0 ? '-' : '';

  // Keine Dezimalstellen wenn ganzer Betrag
  const hasDecimals = majorAmount % 1 !== 0;
  const formatted = hasDecimals
    ? majorAmount.toFixed(config.decimals)
    : majorAmount.toLocaleString(config.locale, { maximumFractionDigits: 0 });

  return `${prefix}${config.symbol}\u00A0${formatted}`;
}

// ============================================================================
// ARITHMETIC (sicher fuer Integer)
// ============================================================================

/**
 * Addiert mehrere Minor-Unit-Betraege.
 */
export function addMoney(...amounts: number[]): number {
  return amounts.reduce((sum, a) => sum + a, 0);
}

/**
 * Berechnet einen Prozent-Rabatt auf einen Minor-Unit-Betrag.
 * Rundet kaufmaennisch.
 */
export function applyPercentDiscount(amountMinor: number, percent: number): number {
  return Math.round(amountMinor * (1 - percent / 100));
}

/**
 * Berechnet den Rabattbetrag in Minor Units.
 */
export function calcPercentDiscountAmount(amountMinor: number, percent: number): number {
  return Math.round(amountMinor * percent / 100);
}

/**
 * Berechnet MwSt-Betrag aus Netto-Betrag.
 */
export function calcVat(netAmountMinor: number, vatRatePercent: number): number {
  return Math.round(netAmountMinor * vatRatePercent / 100);
}

/**
 * Berechnet Brutto aus Netto + MwSt-Satz.
 */
export function calcGross(netAmountMinor: number, vatRatePercent: number): number {
  return netAmountMinor + calcVat(netAmountMinor, vatRatePercent);
}

// ============================================================================
// VALIDATION
// ============================================================================

/**
 * Prueft ob ein Wert ein gueltiger Minor-Unit-Betrag ist (Integer >= 0).
 */
export function isValidMinorAmount(value: unknown): value is number {
  return typeof value === 'number' && Number.isInteger(value) && value >= 0;
}

/**
 * Parst einen User-Input-String (z.B. "85.00" oder "85,50") zu Minor Units.
 * Gibt null zurueck bei ungueltigem Input.
 */
export function parseMoneyInput(input: string): number | null {
  if (!input || input.trim() === '') return null;

  // Komma durch Punkt ersetzen (CH/DE Format)
  const normalized = input.trim().replace(',', '.');

  const parsed = parseFloat(normalized);
  if (isNaN(parsed) || parsed < 0) return null;

  return toMinorUnits(parsed);
}
