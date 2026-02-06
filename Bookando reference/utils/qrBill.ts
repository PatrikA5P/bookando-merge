
import QRCode from 'qrcode';
import { Invoice, CompanySettings } from '../types';

/* 
  Swiss QR Bill Standards & User Overrides
  Page: A6 Landscape (210mm x 105mm)
  Separator: x=62mm
  
  User Specifics:
  - Left Alignment (Zahlteil, QR, Amount): x=67mm
  - Zahlteil Title: Top (y=5mm)
  - QR Code: Below Title (y=12mm)
  - QR Size: 46mm x 46mm
*/

// --- CONSTANTS ---
const FONT_FAMILY = "Helvetica, Arial, sans-serif";
const FONT_SIZE_TITLE = 3.5; // ~11pt
const FONT_SIZE_HEADING = 2.5; // ~8pt
const FONT_SIZE_BODY = 3.0; // ~9pt -> Adjusted for better readability
const LINE_HEIGHT = 3.5;

// --- HELPERS ---

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

const formatAmount = (amount: number): string => {
    return amount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, " ");
};

const formatReferenceDisplay = (ref: string, type: 'QRR' | 'SCOR' | 'NON'): string => {
    if (!ref) return '';
    if (type === 'QRR') {
        let clean = ref.replace(/\s/g, '');
        // Groups of 2, 5, 5, 5, 5, 5
        if (clean.length === 27) {
             return clean.replace(/^(\d{2})(\d{5})(\d{5})(\d{5})(\d{5})(\d{5})$/, '$1 $2 $3 $4 $5 $6');
        }
        return clean;
    }
    if (type === 'SCOR') {
        return ref.replace(/(.{4})/g, '$1 ').trim();
    }
    return ref;
};

// --- DATA INTERFACES & VALIDATION ---

export interface QRBillData {
    currency: string;
    amount: number;
    creditor: {
        name: string;
        address: string;
        zip: string;
        city: string;
        country: string;
        account: string;
    };
    debtor: {
        name: string;
        address: string;
        zip: string;
        city: string;
        country: string;
        account: string; // usually empty for debtor
    };
    reference: string;
    referenceType: 'QRR' | 'SCOR' | 'NON';
    message: string;
    qrString: string;
}

function createSPCDataString(data: QRBillData): string {
    // Swiss Payments Code - 31 lines exactly
    const lines = [
        'SPC',                // 01. QRType
        '0200',               // 02. Version
        '1',                  // 03. Coding (1=UTF-8)
        data.creditor.account.replace(/\s/g, ''), // 04. IBAN
        'K',                  // 05. Creditor Addr Type
        data.creditor.name,   // 06. Name
        data.creditor.address,// 07. Address 
        `${data.creditor.zip} ${data.creditor.city}`, // 08. Zip+City
        '', '',               // 09, 10. Empty
        data.creditor.country,// 11. Country
        '', '', '', '', '', '', '', // 12-18. Ult. Creditor (7 lines)
        data.amount.toFixed(2), // 19. Amount
        data.currency,        // 20. Currency
        'K',                  // 21. Debtor Addr Type
        data.debtor.name,     // 22. Name
        data.debtor.address,  // 23. Address
        `${data.debtor.zip} ${data.debtor.city}`, // 24. Zip+City
        '', '',               // 25, 26. Empty
        data.debtor.country,  // 27. Country
        data.referenceType,   // 28. Ref Type
        data.reference,       // 29. Reference
        data.message,         // 30. Unstructured Message
        'EPD',                // 31. Trailer
        '', ''                // 32, 33. Alt Schema (Optional)
    ];

    return lines.join('\r\n');
}

export function generateQRBillData(invoice: Invoice, companySettings: CompanySettings): QRBillData {
  // 1. Validate Creditor
  if (!companySettings.name || !companySettings.address || !companySettings.zip || !companySettings.city || !companySettings.qrIban && !companySettings.iban) {
      throw new Error("Missing Company Settings (Name, Address, Zip, City, IBAN). Please configure in Settings.");
  }

  // 2. Validate Debtor (Mock check, in real app invoice.client should link to full customer)
  // For this demo, we use fallbacks, but in strict mode we would throw.
  const debtorName = (invoice.client || 'Unknown Client').substring(0, 70);
  
  const creditor = {
      name: companySettings.name.substring(0, 70),
      address: companySettings.address.substring(0, 70),
      zip: companySettings.zip.substring(0, 16),
      city: companySettings.city.substring(0, 35),
      country: (companySettings.country === 'Switzerland' ? 'CH' : (companySettings.country || 'CH').substring(0, 2)).toUpperCase(),
      account: (companySettings.qrIban || companySettings.iban || '').replace(/\s/g, '')
  };

  const debtor = {
      name: debtorName,
      address: 'Musterstrasse 12', // Fallback for demo
      zip: '8000',
      city: 'Zurich',
      country: 'CH',
      account: ''
  };

  // Reference Logic
  const refBase = invoice.id.replace(/[^a-zA-Z0-9]/g, '');
  let reference = '';
  let refType: 'QRR' | 'SCOR' | 'NON' = 'NON';

  if (companySettings.qrReferenceType === 'QR') {
      reference = generateQrReference(refBase); // Calculate Checksum
      refType = 'QRR';
  } else if (companySettings.qrReferenceType === 'SCOR') {
      reference = generateScorReference(refBase); // Calculate ISO Checksum
      refType = 'SCOR';
  } else {
      // NON
      reference = '';
      refType = 'NON';
  }

  const data: QRBillData = {
      currency: invoice.currency || 'CHF',
      amount: invoice.amount,
      creditor,
      debtor,
      reference,
      referenceType: refType,
      message: refType === 'NON' ? `Invoice ${invoice.id}` : '', // Message allowed if Ref is used? Yes, but strictly Unstructured.
      qrString: ''
  };

  data.qrString = createSPCDataString(data);
  return data;
}

// --- RENDER LOGIC ---

export async function renderQRBillImage(qrData: QRBillData): Promise<string> {
    // 1. Generate QR Code
    const svgString = await QRCode.toString(qrData.qrString, {
        type: 'svg',
        errorCorrectionLevel: 'M',
        margin: 0,
        color: { dark: '#000000', light: '#ffffff' }
    });

    // Parse native size
    const viewBoxMatch = svgString.match(/viewBox="0 0 (\d+) (\d+)"/);
    const nativeSize = viewBoxMatch ? parseFloat(viewBoxMatch[1]) : 37; 
    
    // Extract Path - Remove SVG tags robustly
    const qrPath = svgString.replace(/<\/?svg[^>]*>/g, '');

    // 2. Dimensions
    const QR_SIZE = 46; // mm
    const scale = QR_SIZE / nativeSize;

    // 3. Swiss Cross Logo (Centered at 23, 23)
    // Official Proportions: 
    // Logo Area: 7x7mm
    // Cross Arms ratio: Length is ~7/6 of width
    // Colors: Swiss Red (#D52B1E)
    
    const crossGroup = `
        <g transform="translate(23, 23)">
            <!-- 1. Mask / White Background to clear QR modules (7x7mm) -->
            <rect x="-3.5" y="-3.5" width="7" height="7" fill="white"/>
            
            <!-- 2. Black Border (6.6x6.6mm) -->
            <rect x="-3.3" y="-3.3" width="6.6" height="6.6" fill="black"/>
            
            <!-- 3. White Inner Border (6x6mm) -->
            <rect x="-3.0" y="-3.0" width="6.0" height="6.0" fill="white"/>
            
            <!-- 4. Red Background (5.2x5.2mm) -->
            <rect x="-2.6" y="-2.6" width="5.2" height="5.2" fill="#D52B1E"/> 
            
            <!-- 5. White Cross -->
            <!-- Thickness ~1.15mm, Total Length ~3.85mm to maintain ~7/6 arm ratio -->
            <!-- Vertical Bar -->
            <rect x="-0.575" y="-1.925" width="1.15" height="3.85" fill="white"/>
            <!-- Horizontal Bar -->
            <rect x="-1.925" y="-0.575" width="3.85" height="1.15" fill="white"/>
        </g>
    `;

    // 4. Layout Constants (mm)
    const SEPARATOR_X = 62;
    const MARGIN = 5;
    
    // Payment Part Layout
    // Anchor: 67mm (Left align for Title, QR, Amount)
    const LEFT_COL_X = SEPARATOR_X + MARGIN; // 67mm
    
    // Positions
    const TITLE_Y = 5;
    const QR_Y = 12; // Below Title
    const AMOUNT_Y = QR_Y + QR_SIZE + 5; // Below QR (approx 63mm)
    
    const INFO_X = LEFT_COL_X + QR_SIZE + MARGIN; // 67 + 46 + 5 = 118mm
    const INFO_Y = 5; // Align top

    // Text Helper
    const Text = (x: number, y: number, text: string, size: number, bold = false, align = 'start') => 
        `<text x="${x}" y="${y}" font-family="${FONT_FAMILY}" font-size="${size}" font-weight="${bold ? 'bold' : 'normal'}" text-anchor="${align}" dominant-baseline="hanging">${text}</text>`;

    const Block = (x: number, y: number, label: string, lines: string[]) => {
        let content = Text(x, y, label, FONT_SIZE_HEADING, true);
        lines.forEach((l, i) => {
            if(l) content += Text(x, y + 3.5 + (i * LINE_HEIGHT), l, FONT_SIZE_BODY);
        });
        return content;
    };

    const creditorLines = [
        qrData.creditor.name,
        qrData.creditor.address,
        `${qrData.creditor.zip} ${qrData.creditor.city}`
    ];

    const debtorLines = [
        qrData.debtor.name,
        qrData.debtor.address,
        `${qrData.debtor.zip} ${qrData.debtor.city}`
    ];

    // Amount Formatting
    const amountBlock = `
        ${Text(0, 0, "Währung", FONT_SIZE_HEADING, true)}
        ${Text(0, 4, qrData.currency, FONT_SIZE_BODY)}
        ${Text(15, 0, "Betrag", FONT_SIZE_HEADING, true)}
        ${Text(15, 4, formatAmount(qrData.amount), FONT_SIZE_BODY)}
    `;

    // Layout Construction
    return `
    <svg xmlns="http://www.w3.org/2000/svg" width="210mm" height="105mm" viewBox="0 0 210 105">
        <defs>
            <style>
                text { fill: #000; }
            </style>
        </defs>
        <rect width="210" height="105" fill="white"/>
        
        <!-- Cut Line -->
        <path d="M${SEPARATOR_X} 0 V105" stroke="black" stroke-width="0.25" stroke-dasharray="1 1"/>
        <text x="${SEPARATOR_X}" y="5" text-anchor="middle" font-size="4">✂️</text>

        <!-- === RECEIPT (Left) === -->
        <g transform="translate(5, 5)">
            ${Text(0, 0, "Empfangsschein", FONT_SIZE_TITLE, true)}
            
            <!-- Creditor -->
            <g transform="translate(0, 9)">
                ${Block(0, 0, "Konto / Zahlbar an", [qrData.creditor.account, ...creditorLines])}
            </g>

            <!-- Reference (if any) -->
            ${qrData.referenceType !== 'NON' ? `
                <g transform="translate(0, 32)">
                    ${Block(0, 0, "Referenz", [formatReferenceDisplay(qrData.reference, qrData.referenceType)])}
                </g>
            ` : ''}

            <!-- Debtor -->
            <g transform="translate(0, 47)">
                ${Block(0, 0, "Zahlbar durch", debtorLines)}
            </g>

            <!-- Amount -->
            <g transform="translate(0, 70)">
                ${amountBlock}
            </g>
        </g>

        <!-- === PAYMENT PART (Right) === -->
        
        <!-- Title "Zahlteil" -->
        <g transform="translate(${LEFT_COL_X}, ${TITLE_Y})">
             ${Text(0, 0, "Zahlteil", FONT_SIZE_TITLE, true)}
        </g>

        <!-- QR Code Group -->
        <g transform="translate(${LEFT_COL_X}, ${QR_Y})">
            <!-- White background for QR to ensure contrast -->
            <rect width="${QR_SIZE}" height="${QR_SIZE}" fill="white"/>
            <g transform="scale(${scale})">
                ${qrPath}
            </g>
            ${crossGroup}
        </g>

        <!-- Information Column (Right of QR) -->
        <g transform="translate(${INFO_X}, ${INFO_Y})">
            <g>
                ${Block(0, 0, "Konto / Zahlbar an", [qrData.creditor.account, ...creditorLines])}
            </g>

            <g transform="translate(0, 23)">
                ${qrData.referenceType !== 'NON' ? 
                    Block(0, 0, "Referenz", [formatReferenceDisplay(qrData.reference, qrData.referenceType)]) 
                    : ''}
            </g>

            <g transform="translate(0, 38)">
                ${qrData.message ? 
                    Block(0, 0, "Zusätzliche Informationen", [qrData.message]) 
                    : ''}
            </g>

            <g transform="translate(0, 53)">
                ${Block(0, 0, "Zahlbar durch", debtorLines)}
            </g>
        </g>

        <!-- Amount Section (Below QR) -->
        <g transform="translate(${LEFT_COL_X}, ${AMOUNT_Y})">
            ${amountBlock}
        </g>

    </svg>
    `;
}
