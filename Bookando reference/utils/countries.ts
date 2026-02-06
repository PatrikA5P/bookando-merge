
export interface Country {
  code: string;
  name: string;
  dial_code: string;
  flag: string;
}

export const countries: Country[] = [
  { code: 'CH', name: 'Switzerland', dial_code: '+41', flag: '🇨🇭' },
  { code: 'DE', name: 'Germany', dial_code: '+49', flag: '🇩🇪' },
  { code: 'AT', name: 'Austria', dial_code: '+43', flag: '🇦🇹' },
  { code: 'FR', name: 'France', dial_code: '+33', flag: '🇫🇷' },
  { code: 'IT', name: 'Italy', dial_code: '+39', flag: '🇮🇹' },
  { code: 'LI', name: 'Liechtenstein', dial_code: '+423', flag: '🇱🇮' },
  { code: 'GB', name: 'United Kingdom', dial_code: '+44', flag: '🇬🇧' },
  { code: 'US', name: 'United States', dial_code: '+1', flag: '🇺🇸' },
  { code: 'ES', name: 'Spain', dial_code: '+34', flag: '🇪🇸' },
  { code: 'PT', name: 'Portugal', dial_code: '+351', flag: '🇵🇹' },
  { code: 'NL', name: 'Netherlands', dial_code: '+31', flag: '🇳🇱' },
  { code: 'BE', name: 'Belgium', dial_code: '+32', flag: '🇧🇪' },
  { code: 'PL', name: 'Poland', dial_code: '+48', flag: '🇵🇱' },
  { code: 'TR', name: 'Turkey', dial_code: '+90', flag: '🇹🇷' },
  { code: 'CN', name: 'China', dial_code: '+86', flag: '🇨🇳' },
  { code: 'JP', name: 'Japan', dial_code: '+81', flag: '🇯🇵' },
  { code: 'IN', name: 'India', dial_code: '+91', flag: '🇮🇳' },
  { code: 'BR', name: 'Brazil', dial_code: '+55', flag: '🇧🇷' },
];
