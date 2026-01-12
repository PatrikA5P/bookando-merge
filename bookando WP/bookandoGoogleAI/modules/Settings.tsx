
import React, { useState, useEffect } from 'react';
import { 
  Globe, Building, Link as LinkIcon, Shield, ToggleLeft, Lock, 
  CreditCard, Mail, Video, Save, Upload, Check, Key, AlertCircle, X, Plus, Trash2, Info,
  CheckCircle
} from 'lucide-react';
import { useApp } from '../context/AppContext';
import { ModuleName, Role, ModulePermission } from '../types';

type SettingsTab = 'general' | 'company' | 'integrations' | 'license' | 'modules' | 'permissions';

// --- VALIDATION LOGIC ---
function validateSwissIBAN(ibanInput: string) {
    // 1. Bereinigen: Leerzeichen und Bindestriche entfernen, alles Großbuchstaben
    const iban = ibanInput.replace(/[\s\-]/g, '').toUpperCase();

    // 2. Basispfrüfung: Länge und Ländercode
    // Schweizer & Lichtensteiner IBANs sind immer 21 Zeichen lang
    if (iban.length !== 21) {
        return { valid: false, error: "Länge muss genau 21 Zeichen sein." };
    }
    if (!iban.startsWith('CH') && !iban.startsWith('LI')) {
        return { valid: false, error: "Muss mit CH oder LI beginnen." };
    }

    // 3. Mathematische Prüfung (Modulo 97)
    // Ländercode (ersten 4 Zeichen) ans Ende schieben
    const rearranged = iban.substring(4) + iban.substring(0, 4);

    // Buchstaben in Zahlen umwandeln (A=10, B=11 ... Z=35)
    let numericString = "";
    for (let i = 0; i < rearranged.length; i++) {
        const char = rearranged[i];
        if (char >= '0' && char <= '9') {
            numericString += char;
        } else {
            // 'A' ist Code 65. Wir wollen 10. Also -55.
            numericString += (char.charCodeAt(0) - 55).toString();
        }
    }

    // BigInt Modulo Berechnung
    const remainder = BigInt(numericString) % 97n;

    if (remainder !== 1n) {
        return { valid: false, error: "Prüfziffer ungültig (Tippfehler?)." };
    }

    // 4. QR-IBAN Prüfung (Range 30000 - 31999)
    // Die IID (Clearing-Nummer) steht an Position 4 bis 8 (also 5. bis 9. Zeichen)
    const iidStr = iban.substring(4, 9);
    const iid = parseInt(iidStr, 10);

    const isQrIban = (iid >= 30000 && iid <= 31999);

    return {
        valid: true,
        isQrIban: isQrIban,
        formatted: iban, // Bereinigte IBAN zurückgeben
        error: null
    };
}

const SettingsModule: React.FC = () => {
  const [activeTab, setActiveTab] = useState<SettingsTab>('general');
  const { t } = useApp();

  const renderContent = () => {
    switch (activeTab) {
      case 'general': return <GeneralSettings />;
      case 'company': return <CompanySettings />;
      case 'integrations': return <IntegrationSettings />;
      case 'license': return <LicenseSettings />;
      case 'modules': return <ModuleConfig />;
      case 'permissions': return <PermissionSettings />;
      default: return <GeneralSettings />;
    }
  };

  return (
    <div className="flex flex-col md:flex-row h-[calc(100vh-140px)] gap-6">
      {/* Settings Nav - Fixed width on Desktop, Full width on Mobile */}
      <div className="w-full md:w-64 lg:w-72 flex-shrink-0">
        <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex flex-col h-full md:h-auto">
           <div className="p-4 border-b border-slate-100 bg-slate-50">
              <h3 className="font-bold text-slate-800 text-base md:text-lg">{t('settings_title')}</h3>
              <p className="text-xs text-slate-500">{t('settings_subtitle')}</p>
           </div>
           <nav className="p-2 space-y-1 overflow-y-auto">
              <SettingsNavItem active={activeTab === 'general'} onClick={() => setActiveTab('general')} icon={Globe} label={t('general')} />
              <SettingsNavItem active={activeTab === 'company'} onClick={() => setActiveTab('company')} icon={Building} label={t('company_details')} />
              <SettingsNavItem active={activeTab === 'integrations'} onClick={() => setActiveTab('integrations')} icon={LinkIcon} label={t('integrations')} />
              <SettingsNavItem active={activeTab === 'license'} onClick={() => setActiveTab('license')} icon={Key} label={t('license_plan')} />
              <SettingsNavItem active={activeTab === 'modules'} onClick={() => setActiveTab('modules')} icon={ToggleLeft} label={t('modules')} />
              <SettingsNavItem active={activeTab === 'permissions'} onClick={() => setActiveTab('permissions')} icon={Lock} label={t('roles_permissions')} />
           </nav>
        </div>
      </div>

      {/* Content - Grows to fill remaining space */}
      <div className="flex-1 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex flex-col min-w-0">
         {renderContent()}
      </div>
    </div>
  );
};

const SettingsNavItem: React.FC<{ active: boolean; onClick: () => void; icon: any; label: string }> = ({ active, onClick, icon: Icon, label }) => (
   <button
      onClick={onClick}
      className={`
         w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors text-sm font-medium
         ${active ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'}
      `}
   >
      <Icon size={18} />
      {label}
   </button>
);

// --- SUB-COMPONENTS ---

const GeneralSettings = () => {
   const { language, setLanguage, t } = useApp();

   return (
      <div className="flex-1 flex flex-col">
         <div className="p-6 border-b border-slate-100 flex justify-between items-center shrink-0">
            <h3 className="text-base md:text-lg font-bold text-slate-800">{t('general')}</h3>
            <button className="px-4 py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700 flex items-center gap-2">
               <Save size={16} /> {t('save_changes')}
            </button>
         </div>
         <div className="p-6 overflow-y-auto space-y-6 max-w-3xl">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
               <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">{t('system_language')}</label>
                  <select 
                     value={language}
                     onChange={(e) => setLanguage(e.target.value as any)}
                     className="w-full border border-slate-300 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm"
                  >
                     <option value="en">English (US)</option>
                     <option value="de">German (DE)</option>
                     <option value="fr">French (FR)</option>
                     <option value="it">Italian (IT)</option>
                  </select>
                  <p className="text-xs text-slate-500 mt-1">Affects menus, labels, and system messages.</p>
               </div>
               <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">{t('timezone')}</label>
                  <select className="w-full border border-slate-300 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm">
                     <option>(GMT+01:00) Zurich</option>
                     <option>(GMT+01:00) Berlin</option>
                     <option>(GMT+00:00) London</option>
                     <option>(GMT-05:00) New York</option>
                  </select>
               </div>
               <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">{t('date_format')}</label>
                  <select className="w-full border border-slate-300 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm">
                     <option>DD.MM.YYYY (24.10.2023)</option>
                     <option>MM/DD/YYYY (10/24/2023)</option>
                     <option>YYYY-MM-DD (2023-10-24)</option>
                  </select>
               </div>
               <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">{t('currency')}</label>
                  <select className="w-full border border-slate-300 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm">
                     <option>CHF (Swiss Franc)</option>
                     <option>EUR (Euro)</option>
                     <option>USD (US Dollar)</option>
                     <option>GBP (British Pound)</option>
                  </select>
               </div>
            </div>
         </div>
      </div>
   );
};

const CompanySettings = () => {
    const { t, companySettings, updateCompanySettings } = useApp();
    const [formData, setFormData] = useState(companySettings);
    
    const [ibanStatus, setIbanStatus] = useState<{ valid: boolean, isQr: boolean, error: string | null }>({ 
        valid: true, isQr: false, error: null 
    });

    // Validate initial IBAN on mount
    useEffect(() => {
        if (formData.qrIban) {
            handleIbanValidation(formData.qrIban);
        }
    }, []);

    const handleChange = (field: keyof typeof formData, value: any) => {
        setFormData(prev => ({ ...prev, [field]: value }));
    };

    const handleIbanValidation = (value: string) => {
        if (!value) {
            setIbanStatus({ valid: true, isQr: false, error: null });
            return;
        }

        const result = validateSwissIBAN(value);
        
        setIbanStatus({
            valid: result.valid,
            isQr: result.isQrIban,
            error: result.error || null
        });

        if (result.valid) {
            if (result.isQrIban) {
                // Force QR Reference
                setFormData(prev => ({ ...prev, qrReferenceType: 'QR' }));
            } else {
                // Force SCOR if currently QR
                setFormData(prev => {
                    if (prev.qrReferenceType === 'QR') return { ...prev, qrReferenceType: 'SCOR' };
                    return prev;
                });
            }
        }
    };

    const handleSave = () => {
        if (!ibanStatus.valid) return;
        updateCompanySettings(formData);
        alert('Company settings updated successfully.');
    };

    return (
   <div className="flex-1 flex flex-col h-full overflow-hidden">
      <div className="p-6 border-b border-slate-100 flex justify-between items-center shrink-0">
         <h3 className="text-base md:text-lg font-bold text-slate-800">{t('company_details')}</h3>
         <button 
            onClick={handleSave} 
            disabled={!ibanStatus.valid}
            className={`px-4 py-2 text-white rounded-lg text-sm font-medium flex items-center gap-2 ${!ibanStatus.valid ? 'bg-slate-300 cursor-not-allowed' : 'bg-brand-600 hover:bg-brand-700'}`}
         >
            <Save size={16} /> {t('save_changes')}
         </button>
      </div>
      <div className="flex-1 p-6 overflow-y-auto space-y-8 w-full">
         
         {/* Logo Section */}
         <div className="flex items-start gap-6">
            <div className="w-24 h-24 bg-slate-100 rounded-lg border border-slate-200 flex items-center justify-center overflow-hidden">
               <Building size={32} className="text-slate-300" />
            </div>
            <div>
               <h4 className="font-medium text-slate-800 mb-1">Company Logo</h4>
               <p className="text-sm text-slate-500 mb-3">Used on invoices and booking pages. Recommended size: 500x500px.</p>
               <button className="px-3 py-1.5 border border-slate-300 rounded-md text-sm font-medium text-slate-700 hover:bg-slate-50 flex items-center gap-2">
                  <Upload size={14} /> Upload New
               </button>
            </div>
         </div>

         <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="col-span-full">
               <label className="block text-sm font-medium text-slate-700 mb-1">Company Name</label>
               <input type="text" className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm" value={formData.name} onChange={e => handleChange('name', e.target.value)} />
            </div>
            <div>
               <label className="block text-sm font-medium text-slate-700 mb-1">Email Address</label>
               <input type="email" className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm" value={formData.email} onChange={e => handleChange('email', e.target.value)} />
            </div>
            <div>
               <label className="block text-sm font-medium text-slate-700 mb-1">Phone Number</label>
               <input type="tel" className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm" value={formData.phone} onChange={e => handleChange('phone', e.target.value)} />
            </div>
            <div className="col-span-full">
               <label className="block text-sm font-medium text-slate-700 mb-1">Address</label>
               <input type="text" className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 mb-3 text-sm" placeholder="Street" value={formData.address} onChange={e => handleChange('address', e.target.value)} />
               <div className="grid grid-cols-3 gap-3">
                  <input type="text" className="border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm" placeholder="Zip" value={formData.zip} onChange={e => handleChange('zip', e.target.value)} />
                  <input type="text" className="col-span-2 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm" placeholder="City" value={formData.city} onChange={e => handleChange('city', e.target.value)} />
               </div>
            </div>
         </div>

         <div className="pt-6 border-t border-slate-100">
            <h4 className="font-medium text-slate-800 mb-4">Legal & Financial</h4>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
               <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">Tax ID / VAT Number</label>
                  <input type="text" className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm" value={formData.vatId || ''} onChange={e => handleChange('vatId', e.target.value)} />
               </div>
               <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">Bank Name</label>
                  <input type="text" className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm" value={formData.bankName || ''} onChange={e => handleChange('bankName', e.target.value)} />
               </div>
               
               <div className="col-span-full border-t border-slate-100 pt-4 mt-2">
                  <h5 className="font-medium text-slate-700 mb-3">Swiss QR Bill Configuration</h5>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                      <div className="col-span-full md:col-span-1">
                          <label className="block text-sm font-medium text-slate-700 mb-1">IBAN or QR-IBAN</label>
                          <input 
                            type="text" 
                            className={`w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 text-sm transition-colors ${!ibanStatus.valid ? 'border-rose-300 focus:ring-rose-500 bg-rose-50' : 'border-slate-300 focus:ring-brand-500'}`} 
                            value={formData.qrIban || ''} 
                            onChange={e => {
                                handleChange('qrIban', e.target.value);
                                handleIbanValidation(e.target.value);
                            }}
                            placeholder="CHxx 3000 0xxx xxxx xxxx x"
                          />
                          
                          {/* Validation Feedback */}
                          <div className="mt-2 min-h-[24px]">
                              {!ibanStatus.valid && ibanStatus.error && (
                                  <p className="text-xs text-rose-600 flex items-center gap-1 font-medium">
                                      <AlertCircle size={12} /> {ibanStatus.error}
                                  </p>
                              )}
                              {ibanStatus.valid && formData.qrIban && ibanStatus.isQr && (
                                  <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                                      <CheckCircle size={12} /> QR-IBAN erkannt
                                  </span>
                              )}
                              {ibanStatus.valid && formData.qrIban && !ibanStatus.isQr && (
                                  <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                      <CheckCircle size={12} /> Standard-IBAN erkannt
                                  </span>
                              )}
                          </div>
                      </div>
                      <div>
                           <label className="block text-sm font-medium text-slate-700 mb-1">BESR-ID (Optional)</label>
                           <input 
                              type="text" 
                              className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm" 
                              value={formData.besrId || ''} 
                              onChange={e => handleChange('besrId', e.target.value)} 
                              placeholder="e.g. 123456"
                           />
                           <p className="text-xs text-slate-500 mt-1">Legacy ID for orange slips (ESR).</p>
                      </div>

                      <div className="col-span-full">
                          <div className="flex items-center gap-2 mb-2">
                              <label className="block text-sm font-medium text-slate-700">Reference Type</label>
                              <div className="relative group">
                                  <Info size={16} className="text-slate-400 cursor-help" />
                                  <div className="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 p-3 bg-slate-800 text-white text-xs rounded-lg shadow-xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50">
                                      <ul className="list-disc list-inside space-y-1">
                                          <li><strong>QR:</strong> Für QR-IBAN (27-stellig, numerisch).</li>
                                          <li><strong>SCOR:</strong> ISO-Standard für normale IBAN.</li>
                                          <li><strong>NON:</strong> Ohne Referenz (nur Mitteilungstext).</li>
                                      </ul>
                                      <div className="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-slate-800"></div>
                                  </div>
                              </div>
                          </div>
                          <div className="flex flex-col gap-2">
                              <label className={`flex items-center gap-2 cursor-pointer ${!ibanStatus.isQr ? 'opacity-50 cursor-not-allowed' : ''}`}>
                                  <input 
                                      type="radio" 
                                      name="qrReferenceType" 
                                      value="QR" 
                                      checked={formData.qrReferenceType === 'QR'} 
                                      onChange={() => handleChange('qrReferenceType', 'QR')}
                                      disabled={!ibanStatus.isQr}
                                      className="text-brand-600 focus:ring-brand-500"
                                  />
                                  <span className="text-sm text-slate-700">QR Reference (27-digit numeric, requires QR-IBAN)</span>
                              </label>
                              <label className={`flex items-center gap-2 cursor-pointer ${ibanStatus.isQr ? 'opacity-50 cursor-not-allowed' : ''}`}>
                                  <input 
                                      type="radio" 
                                      name="qrReferenceType" 
                                      value="SCOR" 
                                      checked={formData.qrReferenceType === 'SCOR'} 
                                      onChange={() => handleChange('qrReferenceType', 'SCOR')}
                                      disabled={ibanStatus.isQr}
                                      className="text-brand-600 focus:ring-brand-500"
                                  />
                                  <span className="text-sm text-slate-700">SCOR / Creditor Reference (ISO 11649, alphanumeric)</span>
                              </label>
                              <label className={`flex items-center gap-2 cursor-pointer ${ibanStatus.isQr ? 'opacity-50 cursor-not-allowed' : ''}`}>
                                  <input 
                                      type="radio" 
                                      name="qrReferenceType" 
                                      value="NON" 
                                      checked={formData.qrReferenceType === 'NON'} 
                                      onChange={() => handleChange('qrReferenceType', 'NON')}
                                      disabled={ibanStatus.isQr}
                                      className="text-brand-600 focus:ring-brand-500"
                                  />
                                  <span className="text-sm text-slate-700">Non / No Reference (Message only)</span>
                              </label>
                          </div>
                      </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>
)};

const IntegrationSettings = () => {
   const { t } = useApp();
   const [integrations] = useState([
      { id: 1, name: 'Stripe Payments', cat: 'Finance', connected: true, icon: CreditCard, apiKey: 'pk_live_...', webhook: '.../hooks/stripe' },
      { id: 2, name: 'Google Calendar', cat: 'Calendar', connected: true, icon: Building, apiKey: 'oauth_token', webhook: '' },
      { id: 3, name: 'Zoom Meetings', cat: 'Video', connected: false, icon: Video, apiKey: '', webhook: '' },
      { id: 4, name: 'SendGrid Email', cat: 'Communication', connected: true, icon: Mail, apiKey: 'SG.xyz...', webhook: '' },
   ]);

   const [configModal, setConfigModal] = useState<any>(null);

   return (
      <div className="flex-1 flex flex-col">
         <div className="p-6 border-b border-slate-100">
            <h3 className="text-base md:text-lg font-bold text-slate-800">{t('integrations')}</h3>
         </div>
         <div className="p-6 overflow-y-auto">
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
               {integrations.map(item => (
                  <div key={item.id} className="border border-slate-200 rounded-xl p-5 flex flex-col justify-between h-40 hover:shadow-md transition-shadow bg-white">
                     <div className="flex justify-between items-start">
                        <div className="p-2 bg-slate-100 rounded-lg text-slate-600">
                           <item.icon size={20} />
                        </div>
                        {item.connected ? (
                           <span className="flex items-center gap-1 text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-full">
                              <Check size={12} /> Connected
                           </span>
                        ) : (
                           <span className="text-xs font-medium text-slate-400 bg-slate-50 px-2 py-1 rounded-full">
                              Disconnected
                           </span>
                        )}
                     </div>
                     <div>
                        <h4 className="font-bold text-slate-800">{item.name}</h4>
                        <p className="text-xs text-slate-500">{item.cat}</p>
                     </div>
                     <button 
                        onClick={() => setConfigModal(item)}
                        className={`w-full py-2 rounded-lg text-sm font-medium transition-colors ${
                        item.connected 
                           ? 'border border-slate-200 text-slate-600 hover:bg-slate-50' 
                           : 'bg-brand-600 text-white hover:bg-brand-700'
                     }`}>
                        {item.connected ? 'Configure' : 'Connect'}
                     </button>
                  </div>
               ))}
            </div>
         </div>

         {/* Integration Config Modal */}
         {configModal && (
            <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
               <div className="bg-white rounded-xl shadow-2xl w-full max-w-md">
                  <div className="p-4 border-b border-slate-200 flex justify-between items-center">
                     <h3 className="font-bold text-slate-800 flex items-center gap-2">
                        <configModal.icon size={18} /> {configModal.name}
                     </h3>
                     <button onClick={() => setConfigModal(null)} className="text-slate-400 hover:text-slate-600">
                        <X size={20} />
                     </button>
                  </div>
                  <div className="p-6 space-y-4">
                     <div className="p-3 bg-slate-50 rounded border border-slate-200 text-sm text-slate-600">
                        {configModal.connected 
                           ? "This service is currently active and synchronizing data."
                           : "Enter your API credentials below to enable this integration."
                        }
                     </div>
                     <div>
                        <label className="block text-xs font-bold text-slate-500 uppercase mb-1">API Key / Secret</label>
                        <input type="password" defaultValue={configModal.apiKey} className="w-full border border-slate-300 rounded-lg px-3 py-2 font-mono text-sm" />
                     </div>
                     {configModal.connected && (
                        <div>
                           <label className="block text-xs font-bold text-slate-500 uppercase mb-1">Webhook URL</label>
                           <div className="flex gap-2">
                              <input readOnly defaultValue={configModal.webhook || 'https://api.bookando.com/hooks/v1/...'} className="flex-1 bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 font-mono text-xs text-slate-500" />
                           </div>
                        </div>
                     )}
                  </div>
                  <div className="p-4 border-t border-slate-200 bg-slate-50 rounded-b-xl flex justify-end gap-2">
                     <button onClick={() => setConfigModal(null)} className="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800">Cancel</button>
                     <button onClick={() => { alert('Configuration Saved'); setConfigModal(null); }} className="px-4 py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700">
                        Save Configuration
                     </button>
                  </div>
               </div>
            </div>
         )}
      </div>
   );
};

const LicenseSettings = () => {
   const { t } = useApp();
   return (
   <div className="flex-1 flex flex-col p-8 items-center justify-center bg-slate-50">
      <div className="w-full max-w-2xl bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
         <div className="bg-slate-900 p-8 text-center">
            <div className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-brand-400 to-brand-600 mb-4 shadow-lg shadow-brand-900/50">
               <Key className="text-white" size={32} />
            </div>
            <h3 className="text-2xl font-bold text-white mb-1">Enterprise Plan</h3>
            <p className="text-brand-200">Valid until Oct 24, 2025</p>
         </div>
         <div className="p-8">
            <div className="space-y-6">
               <div className="flex items-center justify-between p-4 bg-emerald-50 border border-emerald-100 rounded-xl">
                  <div className="flex items-center gap-3">
                     <div className="bg-emerald-100 p-2 rounded-full text-emerald-600">
                        <Check size={20} />
                     </div>
                     <div>
                        <div className="font-bold text-emerald-900">License Active</div>
                        <div className="text-xs text-emerald-700">Daily verification successful</div>
                     </div>
                  </div>
                  <span className="text-sm font-mono font-bold text-emerald-800">####-####-####-A1B2</span>
               </div>

               <div className="space-y-4">
                  <h4 className="font-bold text-slate-800">Plan Features</h4>
                  <ul className="grid grid-cols-2 gap-3">
                     {['Unlimited Users', 'Advanced Analytics', 'Partner Hub Access', 'Priority Support', 'API Access', 'Whitelabeling'].map(feat => (
                        <li key={feat} className="flex items-center gap-2 text-sm text-slate-600">
                           <Check size={14} className="text-brand-500" /> {feat}
                        </li>
                     ))}
                  </ul>
               </div>
            </div>
         </div>
      </div>
   </div>
)};

const ModuleConfig = () => {
   const { enabledModules, toggleModule, t } = useApp();

   const modules = Object.values(ModuleName).filter(m => m !== ModuleName.SETTINGS); 

   return (
      <div className="flex-1 flex flex-col">
         <div className="p-6 border-b border-slate-100">
            <h3 className="text-base md:text-lg font-bold text-slate-800">{t('module_config')}</h3>
            <p className="text-sm text-slate-500">{t('module_desc')}</p>
         </div>
         <div className="p-6 overflow-y-auto">
            <div className="space-y-4 max-w-3xl">
               {modules.map(moduleName => {
                  const isActive = enabledModules.includes(moduleName);
                  return (
                     <div key={moduleName} className="flex items-start justify-between p-4 border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">
                        <div>
                           <h4 className={`font-bold ${isActive ? 'text-slate-800' : 'text-slate-400'}`}>{t(moduleName)}</h4>
                        </div>
                        <button 
                           onClick={() => toggleModule(moduleName)}
                           className={`
                              relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2
                              ${isActive ? 'bg-brand-600' : 'bg-slate-200'}
                           `}
                        >
                           <span className={`
                              inline-block h-4 w-4 transform rounded-full bg-white transition-transform
                              ${isActive ? 'translate-x-6' : 'translate-x-1'}
                           `} />
                        </button>
                     </div>
                  );
               })}
            </div>
         </div>
      </div>
   );
};

const PermissionSettings = () => {
   const { roles, updateRole, addRole, deleteRole, t } = useApp();
   const [editingRole, setEditingRole] = useState<Role | null>(null);
   const [isCreating, setIsCreating] = useState(false);
   const [newRoleName, setNewRoleName] = useState('');
   const allModules = Object.values(ModuleName);

   const handleCreateRole = () => {
      if(!newRoleName.trim()) return;
      const newRole: Role = {
         id: `role_${Date.now()}`,
         name: newRoleName,
         permissions: {}
      };
      addRole(newRole);
      setNewRoleName('');
      setIsCreating(false);
      setEditingRole(newRole); // Open editor immediately
   };
   
   const getPermissionColor = (perm?: ModulePermission) => {
      if (!perm) return 'bg-slate-200'; // No perm implies none/grey
      if (perm.read && perm.write && perm.delete) return 'bg-emerald-500'; // All
      if (perm.read) return 'bg-amber-400'; // View only
      return 'bg-slate-200'; // None
   };

   return (
      <div className="flex-1 flex flex-col">
         <div className="p-6 border-b border-slate-100 flex justify-between items-center">
            <h3 className="text-base md:text-lg font-bold text-slate-800">{t('roles_permissions')}</h3>
            <button 
               onClick={() => setIsCreating(true)}
               className="px-4 py-2 bg-slate-800 text-white rounded-lg text-sm font-medium hover:bg-slate-900"
            >
               + {t('create_role')}
            </button>
         </div>
         
         {isCreating && (
            <div className="p-4 mx-6 mt-6 bg-slate-50 border border-slate-200 rounded-lg flex gap-4 items-center animate-fadeIn">
               <input 
                  autoFocus
                  placeholder={t('role_name')}
                  className="flex-1 border border-slate-300 rounded-lg px-3 py-2 text-sm"
                  value={newRoleName}
                  onChange={e => setNewRoleName(e.target.value)}
               />
               <button onClick={handleCreateRole} className="bg-brand-600 text-white px-4 py-2 rounded-lg text-sm font-medium">{t('add')}</button>
               <button onClick={() => setIsCreating(false)} className="text-slate-500 hover:text-slate-700 p-2"><X size={18}/></button>
            </div>
         )}

         <div className="p-6 overflow-y-auto">
            <table className="w-full text-left border-collapse">
               <thead className="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase">
                  <tr>
                     <th className="p-3">{t('role_name')}</th>
                     <th className="p-3 hidden md:table-cell">Access Overview</th>
                     <th className="p-3 text-right">{t('actions')}</th>
                  </tr>
               </thead>
               <tbody className="divide-y divide-slate-100 text-sm">
                  {roles.map((role) => (
                     <tr key={role.id} className="hover:bg-slate-50">
                        <td className="p-3 font-bold text-slate-800">{role.name}</td>
                        <td className="p-3 hidden md:flex gap-1 flex-wrap max-w-md">
                           {allModules.map(mod => {
                               const color = getPermissionColor(role.permissions[mod]);
                               return (
                                   <div key={mod} className={`w-3 h-3 rounded-full ${color}`} title={`${mod}: ${color === 'bg-emerald-500' ? 'Full' : color === 'bg-amber-400' ? 'View' : 'None'}`}></div>
                               )
                           })}
                        </td>
                        <td className="p-3 text-right">
                           <div className="flex justify-end gap-2">
                              <button 
                                 onClick={() => setEditingRole(role)}
                                 className="text-brand-600 hover:bg-brand-50 px-3 py-1 rounded text-xs font-medium transition-colors border border-brand-100"
                              >
                                 {t('edit_permissions')}
                              </button>
                              {role.id !== 'admin' && (
                                 <button onClick={() => deleteRole(role.id)} className="text-slate-400 hover:text-rose-600 p-1">
                                    <Trash2 size={16} />
                                 </button>
                              )}
                           </div>
                        </td>
                     </tr>
                  ))}
               </tbody>
            </table>
         </div>

         {/* Granular Permission Editor Modal */}
         {editingRole && (
            <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
               <div className="bg-white rounded-xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col">
                  <div className="p-4 border-b border-slate-200 flex justify-between items-center">
                     <div>
                        <h3 className="font-bold text-slate-800">{t('edit_permissions')}: {editingRole.name}</h3>
                        <p className="text-xs text-slate-500">Define granular access levels for each module.</p>
                     </div>
                     <button onClick={() => setEditingRole(null)} className="text-slate-400 hover:text-slate-600">
                        <X size={20} />
                     </button>
                  </div>
                  <div className="flex-1 overflow-y-auto p-0">
                     <table className="w-full text-left">
                        <thead className="bg-slate-50 text-xs uppercase text-slate-500 font-semibold sticky top-0 z-10 border-b border-slate-200">
                           <tr>
                              <th className="p-4 bg-slate-50 w-48">{t('module')}</th>
                              <th className="p-4 bg-slate-50 w-12"></th>
                              <th className="p-4 text-center bg-slate-50">{t('view')}</th>
                              <th className="p-4 text-center bg-slate-50">{t('create_edit')}</th>
                              <th className="p-4 text-center bg-slate-50">{t('delete_perm')}</th>
                           </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                           {allModules.map(mod => {
                              const p = editingRole.permissions[mod] || { read: false, write: false, delete: false };
                              const color = getPermissionColor(p);

                              const togglePerm = (type: keyof ModulePermission) => {
                                  const newPerm = { ...p, [type]: !p[type] };
                                  // Logic: if write/delete is true, read must be true. If read false, all false.
                                  if (type === 'read' && !newPerm.read) { newPerm.write = false; newPerm.delete = false; }
                                  if ((type === 'write' || type === 'delete') && newPerm[type]) { newPerm.read = true; }
                                  
                                  const newPermissions = { ...editingRole.permissions, [mod]: newPerm };
                                  setEditingRole({ ...editingRole, permissions: newPermissions });
                              };

                              return (
                                 <tr key={mod} className="hover:bg-slate-50">
                                    <td className="p-4 font-medium text-slate-800">{t(mod)}</td>
                                    <td className="p-4">
                                        <div className={`w-4 h-4 rounded-full ${color} border border-slate-100 shadow-sm`}></div>
                                    </td>
                                    <td className="p-4 text-center">
                                       <input 
                                          type="checkbox" 
                                          checked={p.read} 
                                          onChange={() => togglePerm('read')}
                                          className="w-5 h-5 rounded text-brand-600 focus:ring-brand-500" 
                                       />
                                    </td>
                                    <td className="p-4 text-center">
                                       <input 
                                          type="checkbox" 
                                          checked={p.write} 
                                          onChange={() => togglePerm('write')}
                                          className="w-5 h-5 rounded text-brand-600 focus:ring-brand-500" 
                                       />
                                    </td>
                                    <td className="p-4 text-center">
                                       <input 
                                          type="checkbox" 
                                          checked={p.delete} 
                                          onChange={() => togglePerm('delete')}
                                          className="w-5 h-5 rounded text-brand-600 focus:ring-brand-500" 
                                       />
                                    </td>
                                 </tr>
                              );
                           })}
                        </tbody>
                     </table>
                  </div>
                  <div className="p-4 border-t border-slate-200 bg-slate-50 rounded-b-xl flex justify-end gap-2">
                     <button onClick={() => setEditingRole(null)} className="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800">{t('cancel')}</button>
                     <button onClick={() => { updateRole(editingRole); setEditingRole(null); }} className="px-4 py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700">
                        {t('save_permissions')}
                     </button>
                  </div>
               </div>
            </div>
         )}
      </div>
   );
};

export default SettingsModule;
