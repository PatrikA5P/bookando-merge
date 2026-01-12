
import QRCode from 'qrcode';
import { Invoice, CompanySettings } from '../types';

// --- HELPER FUNCTIONS ---

/**
 * Generates the QR Reference (27 digits numerical) with Modulo 10 check digit.
 * Used for QR-IBANs.
 */
export function generateQrReference(input: string): string {
  let raw = input.replace(/\D/g, '');
  raw = raw.padStart(26, '0').slice(-26);

  const table = [0, 9, 4, 6, 8, 2, 7, 1, 3, 5];
  let carry = 0;

  for (let i = 0; i < raw.length; i++) {
    carry = table[(carry + parseInt(raw.charAt(i), 10)) % 10];
  }
  
  const checkDigit = (10 - carry) % 10;
  return raw + checkDigit;
}

/**
 * Generates the SCOR (Structured Creditor Reference) according to ISO 11649.
 * Format: RF + 2 Check Digits + Alphanumeric Reference.
 */
export function generateScorReference(input: string): string {
  const raw = input.replace(/\s/g, '');
  const tempRef = raw + "RF00";

  let numericString = "";
  for (let i = 0; i < tempRef.length; i++) {
    const charCode = tempRef.charCodeAt(i);
    if (charCode >= 48 && charCode <= 57) {
      numericString += tempRef[i]; 
    } else if (charCode >= 65 && charCode <= 90) {
      numericString += (charCode - 55).toString();
    } else if (charCode >= 97 && charCode <= 122) {
      numericString += (charCode - 87).toString();
    }
  }

  const mod97 = BigInt(numericString) % 97n;
  const checkDigit = Number(98n - mod97);
  const checkDigitStr = checkDigit < 10 ? '0' + checkDigit : checkDigit.toString();

  return "RF" + checkDigitStr + raw;
}

/**
 * Formats an amount to 2 decimal places (e.g., "120.50").
 */
const formatAmount = (amount: number): string => {
    return amount.toFixed(2);
};

/**
 * Formats a reference string with spaces for better readability.
 * QRR: Groups of 5.
 * SCOR: Groups of 4.
 */
const formatReferenceDisplay = (ref: string, type: 'QRR' | 'SCOR' | 'NON'): string => {
    if (type === 'QRR') {
        return ref.replace(/(.{5})/g, '$1 ').trim();
    }
    if (type === 'SCOR') {
        return ref.replace(/(.{4})/g, '$1 ').trim();
    }
    return ref;
};

// --- DATA GENERATION ---

export interface QRBillData {
    currency: string;
    amount: number;
    creditor: {
        name: string;
        address: string;
        zip: string;
        city: string;
        country: string;
        account: string; // IBAN
    };
    debtor?: {
        name: string;
        address: string;
        zip: string;
        city: string;
        country: string;
    };
    reference: string;
    referenceType: 'QRR' | 'SCOR' | 'NON';
    message: string;
    additionalInfo: string;
    qrString: string; // The raw string encoded in the QR Code
}

/**
 * Prepares the data object required for rendering.
 */
export function generateQRBillData(invoice: Invoice, companySettings: CompanySettings): QRBillData {
  let reference = '';
  let message = '';
  // Using invoice.id or client reference if available. For demo, we use invoice ID.
  // In a real app, verify characters are allowed.
  const refInput = invoice.id.replace(/[^a-zA-Z0-9]/g, ''); 

  const refType = companySettings.qrReferenceType || 'NON';

  if (refType === 'QR') { // Mapped from 'QR' in settings to 'QRR' in logic
      reference = generateQrReference(refInput);
  } else if (refType === 'SCOR') {
      reference = generateScorReference(refInput);
  } else {
      // NON
      reference = '';
      message = `Invoice ${invoice.id}`;
  }

  // Construct Swiss QR Code String (SPC Payload)
  // Format: SPC\nVersion\nCoding\nIBAN\n...\n
  const creditor = {
      name: companySettings.name.substring(0, 70),
      address: companySettings.address.substring(0, 70),
      zip: companySettings.zip.substring(0, 16),
      city: companySettings.city.substring(0, 35),
      country: companySettings.country === 'Switzerland' ? 'CH' : (companySettings.country || 'CH').substring(0, 2),
      account: (companySettings.qrIban || companySettings.iban || '').replace(/\s/g, '')
  };

  // Mock Debtor (In real app, pass debtor object)
  const debtor = {
      name: invoice.client.substring(0, 70),
      address: 'Musterstrasse 99', // Placeholder
      zip: '9000',
      city: 'St. Gallen',
      country: 'CH'
  };

  const qrType = refType === 'QR' ? 'QRR' : 'SCOR';
  const trailer = 'EPD'; // End Payment Data

  // Line-by-line construction of SPC content
  const spc = [
      'SPC', // QRType
      '0200', // Version
      '1', // Coding (UTF-8)
      creditor.account, // IBAN
      'K', // Creditor Address Type (K = Combined)
      creditor.name,
      creditor.address,
      `${creditor.zip} ${creditor.city}`,
      '', '', // Unused structured addr fields
      creditor.country,
      '', '', '', '', '', '', '', // Ultimate Creditor (unused)
      formatAmount(invoice.amount), // Amount
      invoice.currency || 'CHF', // Currency
      'K', // Debtor Address Type
      debtor.name,
      debtor.address,
      `${debtor.zip} ${debtor.city}`,
      '', '', // Unused structured addr fields
      debtor.country,
      qrType, // Reference Type
      reference, // Reference
      message, // Unstructured Message
      trailer, // Trailer
      '' // Billing Info (optional)
  ].join('\n');

  return {
      currency: invoice.currency || 'CHF',
      amount: invoice.amount,
      creditor,
      debtor,
      reference,
      referenceType: refType === 'QR' ? 'QRR' : refType === 'SCOR' ? 'SCOR' : 'NON',
      message,
      additionalInfo: '',
      qrString: spc
  };
}

// --- SVG RENDERING ---

/**
 * Renders the QR-Bill as an SVG string.
 * 
 * Layout Dimensions (A6 landscape bottom):
 * Width: 210mm
 * Height: 105mm
 * Receipt (Left): 62mm width
 * Payment Part (Right): 148mm width
 */
export async function renderQRBillImage(qrData: QRBillData): Promise<string> {
    // 1. Generate QR Code Matrix
    // errorCorrectionLevel 'M' is required by Swiss implementation guidelines
    const qrSvg = await QRCode.toString(qrData.qrString, {
        type: 'svg',
        margin: 0,
        errorCorrectionLevel: 'M',
        color: {
            dark: '#000000',
            light: '#ffffff'
        }
    });

    // Extract viewBox and path to scale correctly
    const qrViewBox = qrSvg.match(/viewBox="([^"]*)"/)?.[1] || '0 0 50 50';
    const qrPathMatch = qrSvg.match(/<path[^>]*d="([^"]*)"/);
    const qrPath = qrPathMatch ? qrPathMatch[1] : '';

    // 2. Swiss Cross SVG Definition
    // Correct layout: Black square (quiet) -> Red Square -> White Cross
    const swissCross = `
        <g transform="translate(23, 23)">
            <rect x="-3.5" y="-3.5" width="7" height="7" fill="black"/>
            <rect x="-3" y="-3" width="6" height="6" fill="#ff0000"/>
            <rect x="-0.8" y="-2.2" width="1.6" height="4.4" fill="white"/>
            <rect x="-2.2" y="-0.8" width="4.4" height="1.6" fill="white"/>
        </g>
    `;

    // 3. Define Layout Constants (in mm)
    const width = 210;
    const height = 105;
    const receiptWidth = 62;
    const paymentX = 67; 
    const font = 'Helvetica, Arial, sans-serif';
    
    // Font Sizes in MM (Approximate mapping from pt to mm for SVG user units)
    const fsTitle = 3.8; // ~11pt
    const fsLabel = 2.1; // ~6pt
    const fsValue = 3.0; // ~8.5pt
    
    // Format Address Block
    const creditorBlock = [
        qrData.creditor.account,
        qrData.creditor.name,
        qrData.creditor.address,
        `${qrData.creditor.zip} ${qrData.creditor.city}`
    ];

    const debtorBlock = qrData.debtor ? [
        qrData.debtor.name,
        qrData.debtor.address,
        `${qrData.debtor.zip} ${qrData.debtor.city}`
    ] : [];

    // Helper to render multiline text with MM based spacing
    const renderLines = (lines: string[], x: number, y: number, size: number, weight: string = 'normal') => {
        const lineHeight = size * 1.2; 
        return lines.map((line, i) => 
            `<text x="${x}" y="${y + (i * lineHeight)}" font-family="${font}" font-size="${size}" font-weight="${weight}">${line}</text>`
        ).join('');
    };

    // 4. Build SVG
    // Note: ViewBox is 0 0 210 105 (mm units)
    return `
    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="210mm" height="105mm" viewBox="0 0 210 105">
        <defs>
            <style>
                text { fill: black; }
                .label { font-size: ${fsLabel}px; font-weight: bold; }
                .value { font-size: ${fsValue}px; }
                .title { font-size: ${fsTitle}px; font-weight: bold; }
                .scissor { stroke-dasharray: 4, 2; stroke: black; stroke-width: 0.2; }
            </style>
        </defs>

        <rect width="100%" height="100%" fill="white"/>

        <!-- SCISSORS LINE -->
        <line x1="${receiptWidth}" y1="0" x2="${receiptWidth}" y2="105" class="scissor" />
        <text x="${receiptWidth}" y="5" text-anchor="middle" font-size="3">✂️</text>

        <!-- === RECEIPT PART (Left) === -->
        <g transform="translate(5, 5)">
            <text x="0" y="10" class="title">Empfangsschein</text>
            
            <!-- Account / Payable to -->
            <g transform="translate(0, 18)">
                <text x="0" y="0" class="label">Konto / Zahlbar an</text>
                <g transform="translate(0, 4)">
                    ${renderLines(creditorBlock, 0, 0, fsValue)}
                </g>
            </g>

            <!-- Reference -->
            <g transform="translate(0, 44)">
               ${qrData.reference ? `
                <text x="0" y="0" class="label">Referenz</text>
                <text x="0" y="4" class="value">${formatReferenceDisplay(qrData.reference, qrData.referenceType)}</text>
               ` : ''}
            </g>

            <!-- Payable by -->
            <g transform="translate(0, 58)">
                <text x="0" y="0" class="label">Zahlbar durch</text>
                <g transform="translate(0, 4)">
                    ${renderLines(debtorBlock, 0, 0, fsValue)}
                </g>
            </g>

            <!-- Amount -->
            <g transform="translate(0, 82)">
                <text x="0" y="0" class="label">Währung</text>
                <text x="0" y="4" class="value">${qrData.currency}</text>
                
                <text x="15" y="0" class="label">Betrag</text>
                <text x="15" y="4" class="value">${formatAmount(qrData.amount)}</text>
            </g>
        </g>

        <!-- === PAYMENT PART (Right) === -->
        <g transform="translate(${paymentX}, 5)">
            <text x="0" y="10" class="title">Zahlteil</text>

            <!-- QR Code (46x46mm) -->
            <g transform="translate(0, 15)">
                <svg x="0" y="0" width="46" height="46" viewBox="${qrViewBox}">
                     <rect width="100%" height="100%" fill="white" />
                     <path d="${qrPath}" fill="black" />
                </svg>
                <!-- Swiss Cross Overlay -->
                ${swissCross}
            </g>

            <!-- Text Column (Right of QR) -->
            <g transform="translate(50, 0)">
                <!-- Account -->
                <g transform="translate(0, 15)">
                    <text x="0" y="0" class="label">Konto / Zahlbar an</text>
                    <g transform="translate(0, 4)">
                        ${renderLines(creditorBlock, 0, 0, fsValue)}
                    </g>
                </g>

                <!-- Reference -->
                <g transform="translate(0, 42)">
                   ${qrData.reference ? `
                    <text x="0" y="0" class="label">Referenz</text>
                    <text x="0" y="4" class="value" font-family="monospace">${formatReferenceDisplay(qrData.reference, qrData.referenceType)}</text>
                   ` : ''}
                </g>

                <!-- Additional Info (Message) -->
                <g transform="translate(0, 55)">
                   ${qrData.message ? `
                    <text x="0" y="0" class="label">Zusätzliche Informationen</text>
                    <text x="0" y="4" class="value">${qrData.message}</text>
                   ` : ''}
                </g>

                <!-- Payable by -->
                <g transform="translate(0, 68)">
                    <text x="0" y="0" class="label">Zahlbar durch</text>
                    <g transform="translate(0, 4)">
                        ${renderLines(debtorBlock, 0, 0, fsValue)}
                    </g>
                </g>
            </g>

            <!-- Amount Section (Below QR) -->
            <g transform="translate(0, 70)">
                <g transform="translate(0, 0)">
                    <text x="0" y="0" class="label">Währung</text>
                    <text x="0" y="4" class="value">${qrData.currency}</text>
                </g>
                <g transform="translate(15, 0)">
                    <text x="0" y="0" class="label">Betrag</text>
                    <text x="0" y="4" class="value">${formatAmount(qrData.amount)}</text>
                </g>
            </g>
        </g>
    </svg>
    `;
}
