
import React, { useMemo, useRef, useState } from 'react';
import {
  FileText, Bell, Activity,
  BarChart3, PieChart, TrendingUp, Users, DollarSign, Download,
  Filter, Server, Cpu, HardDrive, RotateCcw, Palette, Globe, Monitor, Smartphone, Tablet, CheckSquare, Square, Eye,
  LayoutTemplate, Briefcase, ArrowLeft, Plus, Edit2, Trash2, Save, ChevronDown, ChevronRight, Type, MousePointer2,
  Database, Network, FileSpreadsheet, Upload, Key, Link, Shield, CheckCircle, Copy, FileCode, Loader2, AlertCircle, Wrench
} from 'lucide-react';
import { useApp } from '../context/AppContext';
import ModuleLayout from '../components/ModuleLayout';
import { getModuleDesign } from '../utils/designTokens';

type ToolSection = 'reports' | 'notifications' | 'system' | 'design' | 'import' | 'api';

const ToolsModule: React.FC = () => {
  const [activeSection, setActiveSection] = useState<ToolSection>('reports');
  const { t } = useApp();

  const moduleDesign = getModuleDesign('tools');

  const tabs = [
    { id: 'reports', icon: FileText, label: t('reports_analytics') },
    { id: 'import', icon: Database, label: 'Data Import' },
    { id: 'api', icon: Network, label: 'Interfaces (API)' },
    { id: 'design', icon: Palette, label: 'Design & Widgets' },
    { id: 'notifications', icon: Bell, label: t('notifications') },
    { id: 'system', icon: Activity, label: t('system_tools') },
  ];

  const renderSection = () => {
    switch (activeSection) {
      case 'reports': return <ReportsTool />;
      case 'notifications': return <NotificationsTool />;
      case 'system': return <SystemTools />;
      case 'design': return <DesignTool />;
      case 'import': return <ImportTool />;
      case 'api': return <InterfacesTool />;
      default: return <ReportsTool />;
    }
  };

  return (
    <div className="flex flex-col min-h-full">
      <ModuleLayout
        variant="mixed"
        moduleName="Tools"
        hero={{
          icon: Wrench,
          title: t('tools_title'),
          description: t('tools_subtitle'),
          gradient: moduleDesign.gradient
        }}
        tabs={tabs}
        activeTab={activeSection}
        onTabChange={(tabId) => setActiveSection(tabId as ToolSection)}
      >
        {renderSection()}
      </ModuleLayout>
    </div>
  );
};

const ToolNavItem: React.FC<{ 
  active: boolean; 
  onClick: () => void; 
  icon: any; 
  label: string; 
  desc: string;
}> = ({ active, onClick, icon: Icon, label, desc }) => (
  <button
    onClick={onClick}
    className={`
      w-full flex items-center gap-3 p-3 rounded-lg text-left transition-all
      ${active ? 'bg-brand-50 text-brand-700 ring-1 ring-brand-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'}
    `}
  >
    <div className={`p-2 rounded-lg ${active ? 'bg-white shadow-sm' : 'bg-slate-100'}`}>
      <Icon size={18} />
    </div>
    <div>
      <div className="font-medium text-sm">{label}</div>
      <div className="text-[10px] opacity-70">{desc}</div>
    </div>
  </button>
);

// --- SUB-COMPONENTS ---

const ImportTool = () => {
    const [uploadingState, setUploadingState] = useState<Record<string, boolean>>({});

    const downloadTemplate = (type: 'customers' | 'employees' | 'finance') => {
        let headers = "";
        let filename = "";

        switch (type) {
            case 'customers':
                headers = "First Name,Last Name,Email,Phone,Street,Zip,City,Country,Birthday,Gender";
                filename = "template_customers.csv";
                break;
            case 'employees':
                headers = "First Name,Last Name,Email,Position,Department,Hire Date,Role,Salary";
                filename = "template_employees.csv";
                break;
            case 'finance':
                headers = "Invoice Number,Client Name,Amount,Currency,Date,Due Date,Status,Type (Invoice/Expense)";
                filename = "template_finance.csv";
                break;
        }

        const blob = new Blob([headers], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    };

    const handleUpload = (type: string) => {
        setUploadingState(prev => ({ ...prev, [type]: true }));
        
        // Simulate upload delay
        setTimeout(() => {
            setUploadingState(prev => ({ ...prev, [type]: false }));
            alert(`Successfully imported data for ${type}. 12 records added.`);
        }, 2000);
    };

    const ImportCard = ({ title, type, icon: Icon, description }: { title: string, type: 'customers' | 'employees' | 'finance', icon: any, description: string }) => (
        <div className="border border-slate-200 rounded-xl p-6 flex flex-col bg-white hover:shadow-md transition-shadow">
            <div className="flex items-start justify-between mb-4">
                <div className="p-3 bg-slate-50 rounded-lg text-slate-600">
                    <Icon size={24} />
                </div>
                <span className="text-xs font-bold text-slate-400 bg-slate-50 px-2 py-1 rounded uppercase tracking-wider">CSV / Excel</span>
            </div>
            <h3 className="font-bold text-lg text-slate-800 mb-2">{title} Import</h3>
            <p className="text-sm text-slate-500 mb-6 flex-1">{description}</p>
            
            <div className="space-y-3">
                <button 
                    onClick={() => downloadTemplate(type)}
                    className="w-full py-2 px-4 border border-slate-200 rounded-lg text-slate-600 text-sm font-medium hover:bg-slate-50 flex items-center justify-center gap-2"
                >
                    <FileSpreadsheet size={16} /> Download Template
                </button>
                <div className="relative">
                    <input 
                        type="file" 
                        className="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                        onChange={() => handleUpload(type)}
                        disabled={uploadingState[type]}
                    />
                    <button 
                        className={`w-full py-2 px-4 rounded-lg text-white text-sm font-medium flex items-center justify-center gap-2 transition-colors ${uploadingState[type] ? 'bg-slate-400' : 'bg-brand-600 hover:bg-brand-700'}`}
                    >
                        {uploadingState[type] ? (
                            <>Processing...</>
                        ) : (
                            <> <Upload size={16} /> Upload Data </>
                        )}
                    </button>
                </div>
            </div>
        </div>
    );

    return (
        <div className="flex-1 flex flex-col h-full animate-fadeIn">
            <div className="p-6 border-b border-slate-100">
                <h3 className="text-base md:text-lg font-bold text-slate-800">Data Import Center</h3>
                <p className="text-sm text-slate-500">Bulk import data into your system using standard CSV templates.</p>
            </div>
            <div className="p-6 overflow-y-auto">
                <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <ImportCard 
                        title="Customers" 
                        type="customers" 
                        icon={Users} 
                        description="Import client details, addresses, and contact info. Duplicates based on email will be updated." 
                    />
                    <ImportCard 
                        title="Employees" 
                        type="employees" 
                        icon={Briefcase} 
                        description="Bulk create staff profiles. Passwords will be auto-generated and emailed if not provided." 
                    />
                    <ImportCard 
                        title="Finance" 
                        type="finance" 
                        icon={DollarSign} 
                        description="Import historical invoices and expenses. Useful for migration from other accounting systems." 
                    />
                </div>

                <div className="mt-8 p-4 bg-indigo-50 border border-indigo-100 rounded-xl flex items-start gap-4">
                    <div className="p-2 bg-indigo-100 text-indigo-600 rounded-lg shrink-0">
                        <Database size={20} />
                    </div>
                    <div>
                        <h4 className="font-bold text-indigo-900 text-sm mb-1">Need to migrate from a specific platform?</h4>
                        <p className="text-xs text-indigo-700">
                            We offer dedicated migration tools for Salesforce, HubSpot, and QuickBooks. 
                            Check the <span className="underline cursor-pointer hover:text-indigo-900">Integrations Market</span> or contact support for assistance.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
};

const InterfacesTool = () => {
    const [tokens, setTokens] = useState([
        { id: 1, name: 'Website Booking Widget', prefix: 'pk_live_', created: '2023-01-15', lastUsed: '2 hours ago' },
        { id: 2, name: 'Mobile App (iOS)', prefix: 'pk_live_', created: '2023-03-10', lastUsed: '5 mins ago' },
        { id: 3, name: 'Dev Test Token', prefix: 'pk_test_', created: '2023-10-20', lastUsed: 'Never' },
    ]);

    const revokeToken = (id: number) => {
        if(confirm('Are you sure you want to revoke this token? Any integration using it will stop working.')) {
            setTokens(tokens.filter(t => t.id !== id));
        }
    };

    return (
        <div className="flex-1 flex flex-col h-full animate-fadeIn">
            <div className="p-6 border-b border-slate-100 flex justify-between items-center">
                <div>
                    <h3 className="text-base md:text-lg font-bold text-slate-800">Developer Interfaces</h3>
                    <p className="text-sm text-slate-500">Manage API Access Tokens and Webhooks.</p>
                </div>
                <button className="bg-slate-900 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 hover:bg-slate-800">
                    <Plus size={16} /> Generate New Token
                </button>
            </div>
            
            <div className="p-6 overflow-y-auto space-y-8">
                {/* API Tokens */}
                <div>
                    <h4 className="font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <Key size={18} className="text-brand-600" /> Active API Keys
                    </h4>
                    <div className="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-slate-50 border-b border-slate-200 text-xs uppercase text-slate-500 font-semibold">
                                <tr>
                                    <th className="p-4">Name</th>
                                    <th className="p-4">Token Prefix</th>
                                    <th className="p-4">Created</th>
                                    <th className="p-4">Last Used</th>
                                    <th className="p-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {tokens.map(token => (
                                    <tr key={token.id} className="hover:bg-slate-50">
                                        <td className="p-4 font-medium text-slate-800">{token.name}</td>
                                        <td className="p-4 font-mono text-slate-500 bg-slate-50 w-fit">{token.prefix}****************</td>
                                        <td className="p-4 text-slate-500">{token.created}</td>
                                        <td className="p-4 text-slate-500">{token.lastUsed}</td>
                                        <td className="p-4 text-right">
                                            <button 
                                                onClick={() => revokeToken(token.id)}
                                                className="text-rose-600 hover:text-rose-800 text-xs font-bold border border-rose-200 bg-rose-50 px-3 py-1 rounded-lg hover:bg-rose-100 transition-colors"
                                            >
                                                Revoke
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Documentation */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="border border-slate-200 rounded-xl p-6 flex flex-col bg-slate-50">
                        <div className="flex items-center gap-3 mb-3">
                            <div className="p-2 bg-white border border-slate-200 rounded-lg text-brand-600">
                                <FileCode size={24} />
                            </div>
                            <h4 className="font-bold text-slate-800">API Documentation</h4>
                        </div>
                        <p className="text-sm text-slate-600 mb-4 flex-1">
                            Comprehensive guide for our REST API, including endpoints for Customers, Bookings, and Finance.
                        </p>
                        <div className="flex gap-2">
                            <button className="flex-1 py-2 bg-white border border-slate-300 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-100">
                                View Swagger UI
                            </button>
                            <button className="flex-1 py-2 bg-white border border-slate-300 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-100">
                                Download Postman Collection
                            </button>
                        </div>
                    </div>

                    <div className="border border-slate-200 rounded-xl p-6 flex flex-col bg-slate-50">
                        <div className="flex items-center gap-3 mb-3">
                            <div className="p-2 bg-white border border-slate-200 rounded-lg text-emerald-600">
                                <Activity size={24} />
                            </div>
                            <h4 className="font-bold text-slate-800">System Health & Webhooks</h4>
                        </div>
                        <div className="space-y-3 mb-4 flex-1">
                            <div className="flex justify-between items-center text-sm">
                                <span className="text-slate-600">API Latency</span>
                                <span className="font-mono text-emerald-600 font-bold">45ms</span>
                            </div>
                            <div className="flex justify-between items-center text-sm">
                                <span className="text-slate-600">Webhook Success Rate</span>
                                <span className="font-mono text-emerald-600 font-bold">99.9%</span>
                            </div>
                            <div className="flex justify-between items-center text-sm">
                                <span className="text-slate-600">Rate Limit</span>
                                <span className="font-mono text-slate-700">1000 req/min</span>
                            </div>
                        </div>
                        <button className="w-full py-2 bg-white border border-slate-300 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-100">
                            Configure Webhooks
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
};

const ReportsTool = () => {
  const { t } = useApp();
  const reports = [
    { title: 'Customer Growth', category: 'Customers', icon: Users, color: 'bg-blue-50 text-blue-600' },
    { title: 'Monthly Revenue', category: 'Finance', icon: DollarSign, color: 'bg-emerald-50 text-emerald-600' },
    { title: 'Appointment Volume', category: 'Operations', icon: BarChart3, color: 'bg-purple-50 text-purple-600' },
    { title: 'Partner Commissions', category: 'Partners', icon: PieChart, color: 'bg-amber-50 text-amber-600' },
    { title: 'Employee Performance', category: 'HR', icon: TrendingUp, color: 'bg-rose-50 text-rose-600' },
    { title: 'Custom Report', category: 'Custom', icon: FileText, color: 'bg-slate-100 text-slate-600' },
  ];

  return (
    <div className="flex-1 flex flex-col overflow-hidden">
      <div className="p-6 border-b border-slate-100 flex justify-between items-center">
        <h3 className="text-base md:text-lg font-bold text-slate-800">{t('reports_center')}</h3>
        <div className="flex gap-2">
          <button className="px-3 py-2 border border-slate-200 rounded-lg text-sm font-medium hover:bg-slate-50">
            {t('configure')}
          </button>
        </div>
      </div>
      
      <div className="p-6 overflow-y-auto">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {reports.map((report, idx) => (
            <div key={idx} className="border border-slate-200 rounded-xl p-5 hover:shadow-md transition-all cursor-pointer group">
              <div className="flex justify-between items-start mb-4">
                <div className={`p-3 rounded-lg ${report.color}`}>
                  <report.icon size={24} />
                </div>
                <button className="text-slate-300 hover:text-brand-600 opacity-0 group-hover:opacity-100 transition-opacity">
                  <Download size={18} />
                </button>
              </div>
              <h4 className="font-bold text-slate-800 mb-1">{report.title}</h4>
              <p className="text-sm text-slate-500 mb-4">{report.category} Report</p>
              <div className="h-24 bg-slate-50 rounded-lg flex items-end justify-between px-2 pb-2 gap-1">
                {[40, 70, 50, 90, 60, 80].map((h, i) => (
                  <div key={i} className="w-full bg-brand-200 rounded-t-sm group-hover:bg-brand-400 transition-colors" style={{ height: `${h}%` }}></div>
                ))}
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

// --- DESIGN TOOL ---

type DesignStep = 'selection' | 'templates' | 'editor';
type DesignContext = 'booking' | 'customer' | 'employee';
type DeviceType = 'desktop' | 'tablet' | 'mobile';

interface Template {
    id: string;
    name: string;
    lastModified: string;
}

// Complex Configuration Types
interface GlobalDesignConfig {
    fontFamily: string;
    fontScale: number; // Percentage
    colors: {
        primary: string;
        success: string;
        warning: string;
        error: string;
        sidebarBg: string;
        sidebarText: string;
        contentBg: string;
        contentHeading: string;
        contentText: string;
        formCheckbox: string;
        formBg: string;
        formBorder: string;
        formText: string;
        btnPrimaryBg: string;
        btnPrimaryText: string;
        btnSecondaryBg: string;
        btnSecondaryText: string;
    };
    borders: {
        width: number;
        radius: number;
        btnGlobalBorder: boolean; // Primary
        btnSecondaryBorder: boolean;
    };
}

interface CategoriesConfig {
    showPageColor: boolean;
    showAccentColor: boolean;
    showCount: boolean;
    labels: {
        primaryBtn: string;
        secondaryBtn: string;
    };
}

interface ServicesConfig {
    layout: {
        showBackground: boolean;
        showSidebar: boolean;
        showCardColors: boolean;
    };
    filters: {
        showSearch: boolean;
        showEmployeeFilter: boolean;
        showLocationFilter: boolean;
    };
    info: {
        showVat: boolean;
        showBadge: boolean;
        showPrice: boolean;
        showActionButton: boolean;
    };
    labels: {
        primaryBtn: string;
        secondaryBtn: string;
        service: string;
        bookNow: string;
        free: string;
        multipleLocations: string;
        viewPhotos: string;
        about: string;
        employees: string;
        vat: string;
        inclVat: string;
    };
}

interface BookingWidgetConfig {
    global: GlobalDesignConfig;
    categories: CategoriesConfig;
    services: ServicesConfig;
    packages: { enabled: boolean }; // Placeholder
    events: { enabled: boolean }; // Placeholder
    form: { enabled: boolean }; // Placeholder
}

const DesignTool = () => {
    const [step, setStep] = useState<DesignStep>('selection');
    const [context, setContext] = useState<DesignContext | null>(null);
    const [device, setDevice] = useState<DeviceType>('desktop');
    const [activeAccordion, setActiveAccordion] = useState<string | null>('global');
    const [isSaving, setIsSaving] = useState(false);
    const [saveStatus, setSaveStatus] = useState<'idle' | 'success' | 'error'>('idle');
    const [saveMessage, setSaveMessage] = useState<string | null>(null);

    // --- STATE INIT ---
    const [bookingConfig, setBookingConfig] = useState<BookingWidgetConfig>({
        global: {
            fontFamily: 'Inter',
            fontScale: 100,
            colors: {
                primary: '#0ea5e9', success: '#10b981', warning: '#f59e0b', error: '#ef4444',
                sidebarBg: '#f8fafc', sidebarText: '#475569',
                contentBg: '#ffffff', contentHeading: '#1e293b', contentText: '#64748b',
                formCheckbox: '#0ea5e9', formBg: '#ffffff', formBorder: '#e2e8f0', formText: '#1e293b',
                btnPrimaryBg: '#0ea5e9', btnPrimaryText: '#ffffff',
                btnSecondaryBg: '#f1f5f9', btnSecondaryText: '#475569'
            },
            borders: { width: 1, radius: 8, btnGlobalBorder: false, btnSecondaryBorder: true }
        },
        categories: {
            showPageColor: true, showAccentColor: true, showCount: true,
            labels: { primaryBtn: 'Select', secondaryBtn: 'Back' }
        },
        services: {
            layout: { showBackground: true, showSidebar: true, showCardColors: false },
            filters: { showSearch: true, showEmployeeFilter: true, showLocationFilter: true },
            info: { showVat: true, showBadge: true, showPrice: true, showActionButton: true },
            labels: {
                primaryBtn: 'Next', secondaryBtn: 'Back', service: 'Service', bookNow: 'Book Now',
                free: 'Free', multipleLocations: 'Multiple Locations', viewPhotos: 'View All Photos',
                about: 'About the Service', employees: 'Employees', vat: 'VAT', inclVat: 'Incl. VAT'
            }
        },
        packages: { enabled: true },
        events: { enabled: true },
        form: { enabled: true }
    });

    // Simple configs for other portals (placeholders)
    const [customerConfig, setCustomerConfig] = useState({ dashboard: true, profile: true, appointments: true, courses: true, quizzes: true, settings: true, logout: true });
    const [employeeConfig, setEmployeeConfig] = useState({ dashboard: true, schedule: true, timeTracking: true, absences: true, payroll: true });

    // --- HELPER COMPONENTS ---

    const ConfigSection = ({ id, title, icon: Icon, children }: any) => (
        <div className="border-b border-slate-100 last:border-0">
            <button 
                onClick={() => setActiveAccordion(activeAccordion === id ? null : id)}
                className="w-full flex items-center justify-between p-4 hover:bg-slate-50 transition-colors"
            >
                <div className="flex items-center gap-2 font-bold text-slate-700 text-sm">
                    <Icon size={16} className="text-slate-400" /> {title}
                </div>
                {activeAccordion === id ? <ChevronDown size={16} className="text-slate-400" /> : <ChevronRight size={16} className="text-slate-400" />}
            </button>
            {activeAccordion === id && (
                <div className="p-4 bg-slate-50/50 space-y-4 animate-slideDown">
                    {children}
                </div>
            )}
        </div>
    );

    const Toggle = ({ label, checked, onChange }: { label: string, checked: boolean, onChange: (v: boolean) => void }) => (
        <label className="flex items-center justify-between cursor-pointer group">
            <span className="text-sm text-slate-600 group-hover:text-slate-900">{label}</span>
            <button onClick={() => onChange(!checked)} className={`transition-colors ${checked ? 'text-brand-600' : 'text-slate-300'}`}>
                {checked ? <CheckSquare size={20} /> : <Square size={20} />}
            </button>
        </label>
    );

    const ColorInput = ({ label, value, onChange }: { label: string, value: string, onChange: (v: string) => void }) => (
        <div className="flex items-center justify-between">
            <span className="text-xs text-slate-500">{label}</span>
            <div className="flex items-center gap-2">
                <input type="text" value={value} onChange={e => onChange(e.target.value)} className="w-20 text-xs border border-slate-200 rounded px-1 py-0.5 font-mono bg-white" />
                <input type="color" value={value} onChange={e => onChange(e.target.value)} className="w-6 h-6 rounded cursor-pointer border-0 p-0 bg-transparent" />
            </div>
        </div>
    );

    const LabelInput = ({ label, value, onChange }: { label: string, value: string, onChange: (v: string) => void }) => (
        <div>
            <div className="flex justify-between mb-1">
                <span className="text-xs text-slate-500">{label}</span>
                <Globe size={10} className="text-slate-400" />
            </div>
            <input 
                type="text" 
                value={value} 
                onChange={e => onChange(e.target.value)}
                className="w-full border border-slate-200 rounded px-2 py-1 text-sm focus:ring-1 focus:ring-brand-500 outline-none"
            />
        </div>
    );

    const Slider = ({ label, value, onChange, min, max, unit }: any) => (
        <div>
            <div className="flex justify-between mb-1">
                <span className="text-xs text-slate-500">{label}</span>
                <span className="text-xs font-bold text-slate-700">{value}{unit}</span>
            </div>
            <input 
                type="range" min={min} max={max} value={value} 
                onChange={e => onChange(parseInt(e.target.value))}
                className="w-full accent-brand-600 h-1.5 bg-slate-200 rounded-lg appearance-none cursor-pointer"
            />
        </div>
    );

    // --- RENDER STEPS ---

    const persistDesignConfig = async (serializedConfig: string) => {
        // Replace with API call or context action to persist the editor configuration
        console.log('Persisting design configuration', serializedConfig);
        return new Promise<void>((resolve) => setTimeout(resolve, 800));
    };

    const handleSave = async () => {
        if (!context) {
            setSaveStatus('error');
            setSaveMessage('Please select a context before saving.');
            return;
        }

        setIsSaving(true);
        setSaveMessage(null);
        setSaveStatus('idle');

        const payload = {
            context,
            device,
            configs: {
                booking: bookingConfig,
                customer: customerConfig,
                employee: employeeConfig,
            },
            updatedAt: new Date().toISOString(),
        };

        try {
            const serialized = JSON.stringify(payload);
            await persistDesignConfig(serialized);
            setSaveStatus('success');
            setSaveMessage('Design settings saved successfully.');
        } catch (error) {
            console.error('Error saving design settings', error);
            setSaveStatus('error');
            setSaveMessage(error instanceof Error ? error.message : 'Saving failed. Please try again.');
        } finally {
            setIsSaving(false);
        }
    };

    if (step === 'selection') {
        return (
            <div className="flex-1 flex flex-col p-8 overflow-y-auto">
                <h2 className="text-2xl font-bold text-slate-800 mb-2">What would you like to customize?</h2>
                <p className="text-slate-500 mb-8">Select a portal or widget to configure its design and functionality.</p>
                
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <button onClick={() => { setContext('booking'); setStep('editor'); }} className="bg-white p-8 rounded-xl border border-slate-200 shadow-sm hover:shadow-xl hover:border-brand-500 transition-all text-left group">
                        <div className="w-16 h-16 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform"><LayoutTemplate size={32} /></div>
                        <h3 className="text-xl font-bold text-slate-800 mb-2">Booking Widget</h3>
                        <p className="text-sm text-slate-500">Embeddable widget for your website to accept bookings.</p>
                    </button>
                    <button onClick={() => { setContext('customer'); setStep('editor'); }} className="bg-white p-8 rounded-xl border border-slate-200 shadow-sm hover:shadow-xl hover:border-brand-500 transition-all text-left group">
                        <div className="w-16 h-16 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform"><Users size={32} /></div>
                        <h3 className="text-xl font-bold text-slate-800 mb-2">Customer Portal</h3>
                        <p className="text-sm text-slate-500">The login area for your clients to manage their profile.</p>
                    </button>
                    <button onClick={() => { setContext('employee'); setStep('editor'); }} className="bg-white p-8 rounded-xl border border-slate-200 shadow-sm hover:shadow-xl hover:border-brand-500 transition-all text-left group">
                        <div className="w-16 h-16 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform"><Briefcase size={32} /></div>
                        <h3 className="text-xl font-bold text-slate-800 mb-2">Employee Portal</h3>
                        <p className="text-sm text-slate-500">The dashboard for your staff to manage schedule and work.</p>
                    </button>
                </div>
            </div>
        );
    }

    // --- STEP 3: EDITOR ---
    
    const getPreviewWidth = () => {
        if (device === 'mobile') return 'max-w-[375px]';
        if (device === 'tablet') return 'max-w-[768px]';
        return 'max-w-full';
    };

    // Preview Styles derived from Config
    const previewStyle = context === 'booking' ? {
        '--font-family': bookingConfig.global.fontFamily,
        '--font-scale': `${bookingConfig.global.fontScale}%`,
        '--col-primary': bookingConfig.global.colors.primary,
        '--col-bg-sidebar': bookingConfig.global.colors.sidebarBg,
        '--col-text-sidebar': bookingConfig.global.colors.sidebarText,
        '--col-bg-content': bookingConfig.global.colors.contentBg,
        '--col-head-content': bookingConfig.global.colors.contentHeading,
        '--col-text-content': bookingConfig.global.colors.contentText,
        '--btn-prim-bg': bookingConfig.global.colors.btnPrimaryBg,
        '--btn-prim-text': bookingConfig.global.colors.btnPrimaryText,
        '--btn-sec-bg': bookingConfig.global.colors.btnSecondaryBg,
        '--btn-sec-text': bookingConfig.global.colors.btnSecondaryText,
        '--border-w': `${bookingConfig.global.borders.width}px`,
        '--border-r': `${bookingConfig.global.borders.radius}px`,
        fontFamily: 'var(--font-family)',
        fontSize: 'var(--font-scale)',
    } as React.CSSProperties : {};

    return (
        <div className="flex-1 flex flex-col h-full">
            {/* Editor Header */}
            <div className="p-4 border-b border-slate-200 flex justify-between items-center bg-white shadow-sm z-10">
                <div className="flex items-center gap-4">
                    <button onClick={() => setStep('selection')} className="text-slate-500 hover:text-slate-700 flex items-center gap-1 text-sm font-medium">
                        <ArrowLeft size={16} /> Back
                    </button>
                    <div className="h-6 w-px bg-slate-200"></div>
                    <div className="flex bg-slate-100 p-1 rounded-lg">
                        <button onClick={() => setDevice('desktop')} className={`p-2 rounded ${device === 'desktop' ? 'bg-white shadow text-slate-800' : 'text-slate-500 hover:text-slate-700'}`}><Monitor size={18} /></button>
                        <button onClick={() => setDevice('tablet')} className={`p-2 rounded ${device === 'tablet' ? 'bg-white shadow text-slate-800' : 'text-slate-500 hover:text-slate-700'}`}><Tablet size={18} /></button>
                        <button onClick={() => setDevice('mobile')} className={`p-2 rounded ${device === 'mobile' ? 'bg-white shadow text-slate-800' : 'text-slate-500 hover:text-slate-700'}`}><Smartphone size={18} /></button>
                    </div>
                </div>
                <div className="flex items-center gap-3">
                    <span className="text-xs text-slate-400 uppercase font-bold tracking-wider">{context} editor</span>
                    {saveMessage && (
                        <span
                            className={`text-xs font-medium ${saveStatus === 'success' ? 'text-emerald-600' : 'text-rose-600'}`}
                            role="status"
                        >
                            {saveMessage}
                        </span>
                    )}
                    <button
                        onClick={handleSave}
                        disabled={isSaving}
                        className={`bg-brand-600 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 shadow-sm transition-colors ${isSaving ? 'opacity-70 cursor-not-allowed' : 'hover:bg-brand-700'}`}
                    >
                        {isSaving ? <span className="animate-pulse h-4 w-4 rounded-full bg-white/70" /> : <Save size={16} />}
                        {isSaving ? 'Saving...' : 'Save'}
                    </button>
                </div>
            </div>

            <div className="flex-1 flex overflow-hidden">
                {/* CONFIGURATION SIDEBAR */}
                <div className="w-80 border-r border-slate-200 bg-white flex flex-col overflow-y-auto z-10 scrollbar-thin">
                    <div className="p-4 border-b border-slate-100">
                        <h4 className="text-xs font-bold text-slate-400 uppercase tracking-wider">Settings</h4>
                    </div>

                    {/* --- BOOKING WIDGET CONFIG --- */}
                    {context === 'booking' && (
                        <>
                            {/* GLOBAL */}
                            <ConfigSection id="global" title="Global Settings" icon={Globe}>
                                <div className="space-y-4">
                                    <div>
                                        <span className="text-xs font-bold text-slate-500 block mb-2">Typography</span>
                                        <select 
                                            className="w-full border border-slate-200 rounded text-sm p-1 mb-2" 
                                            value={bookingConfig.global.fontFamily}
                                            onChange={(e) => setBookingConfig(prev => ({...prev, global: {...prev.global, fontFamily: e.target.value}}))}
                                        >
                                            <option value="Inter">Inter (Default)</option>
                                            <option value="Roboto">Roboto</option>
                                            <option value="Open Sans">Open Sans</option>
                                            <option value="Lato">Lato</option>
                                        </select>
                                        <Slider label="Font Scale" unit="%" min={80} max={120} value={bookingConfig.global.fontScale} onChange={(v: number) => setBookingConfig(prev => ({...prev, global: {...prev.global, fontScale: v}}))} />
                                    </div>
                                    
                                    <div className="space-y-2">
                                        <span className="text-xs font-bold text-slate-500 block">Colors</span>
                                        <ColorInput label="Primary" value={bookingConfig.global.colors.primary} onChange={(v) => setBookingConfig(prev => ({...prev, global: {...prev.global, colors: {...prev.global.colors, primary: v}}}))} />
                                        <ColorInput label="Content Bg" value={bookingConfig.global.colors.contentBg} onChange={(v) => setBookingConfig(prev => ({...prev, global: {...prev.global, colors: {...prev.global.colors, contentBg: v}}}))} />
                                        <ColorInput label="Headings" value={bookingConfig.global.colors.contentHeading} onChange={(v) => setBookingConfig(prev => ({...prev, global: {...prev.global, colors: {...prev.global.colors, contentHeading: v}}}))} />
                                        <ColorInput label="Sidebar Bg" value={bookingConfig.global.colors.sidebarBg} onChange={(v) => setBookingConfig(prev => ({...prev, global: {...prev.global, colors: {...prev.global.colors, sidebarBg: v}}}))} />
                                        <ColorInput label="Sidebar Text" value={bookingConfig.global.colors.sidebarText} onChange={(v) => setBookingConfig(prev => ({...prev, global: {...prev.global, colors: {...prev.global.colors, sidebarText: v}}}))} />
                                    </div>

                                    <div className="space-y-2">
                                        <span className="text-xs font-bold text-slate-500 block">Buttons</span>
                                        <ColorInput label="Primary Bg" value={bookingConfig.global.colors.btnPrimaryBg} onChange={(v) => setBookingConfig(prev => ({...prev, global: {...prev.global, colors: {...prev.global.colors, btnPrimaryBg: v}}}))} />
                                        <ColorInput label="Primary Text" value={bookingConfig.global.colors.btnPrimaryText} onChange={(v) => setBookingConfig(prev => ({...prev, global: {...prev.global, colors: {...prev.global.colors, btnPrimaryText: v}}}))} />
                                        <Toggle label="Primary Border" checked={bookingConfig.global.borders.btnGlobalBorder} onChange={(v) => setBookingConfig(prev => ({...prev, global: {...prev.global, borders: {...prev.global.borders, btnGlobalBorder: v}}}))} />
                                    </div>

                                    <div>
                                        <span className="text-xs font-bold text-slate-500 block mb-2">Borders</span>
                                        <Slider label="Thickness" unit="px" min={0} max={5} value={bookingConfig.global.borders.width} onChange={(v: number) => setBookingConfig(prev => ({...prev, global: {...prev.global, borders: {...prev.global.borders, width: v}}}))} />
                                        <div className="h-2"></div>
                                        <Slider label="Radius" unit="px" min={0} max={20} value={bookingConfig.global.borders.radius} onChange={(v: number) => setBookingConfig(prev => ({...prev, global: {...prev.global, borders: {...prev.global.borders, radius: v}}}))} />
                                    </div>
                                </div>
                            </ConfigSection>

                            {/* CATEGORIES */}
                            <ConfigSection id="categories" title="Categories" icon={LayoutTemplate}>
                                <div className="space-y-4">
                                    <div>
                                        <span className="text-xs font-bold text-slate-500 block mb-2">Layout</span>
                                        <Toggle label="Show Page Color" checked={bookingConfig.categories.showPageColor} onChange={(v) => setBookingConfig(prev => ({...prev, categories: {...prev.categories, showPageColor: v}}))} />
                                        <Toggle label="Show Accent Color" checked={bookingConfig.categories.showAccentColor} onChange={(v) => setBookingConfig(prev => ({...prev, categories: {...prev.categories, showAccentColor: v}}))} />
                                        <Toggle label="Show Services Count" checked={bookingConfig.categories.showCount} onChange={(v) => setBookingConfig(prev => ({...prev, categories: {...prev.categories, showCount: v}}))} />
                                    </div>
                                    <div className="space-y-2">
                                        <span className="text-xs font-bold text-slate-500 block">Labels & Translation</span>
                                        <LabelInput label="Primary Button" value={bookingConfig.categories.labels.primaryBtn} onChange={(v) => setBookingConfig(prev => ({...prev, categories: {...prev.categories, labels: {...prev.categories.labels, primaryBtn: v}}}))} />
                                        <LabelInput label="Secondary Button" value={bookingConfig.categories.labels.secondaryBtn} onChange={(v) => setBookingConfig(prev => ({...prev, categories: {...prev.categories, labels: {...prev.categories.labels, secondaryBtn: v}}}))} />
                                    </div>
                                </div>
                            </ConfigSection>

                            {/* SERVICES */}
                            <ConfigSection id="services" title="Service List" icon={Briefcase}>
                                <div className="space-y-4">
                                    <div>
                                        <span className="text-xs font-bold text-slate-500 block mb-2">Layout</span>
                                        <Toggle label="Show Background" checked={bookingConfig.services.layout.showBackground} onChange={(v) => setBookingConfig(prev => ({...prev, services: {...prev.services, layout: {...prev.services.layout, showBackground: v}}}))} />
                                        <Toggle label="Show Sidebar" checked={bookingConfig.services.layout.showSidebar} onChange={(v) => setBookingConfig(prev => ({...prev, services: {...prev.services, layout: {...prev.services.layout, showSidebar: v}}}))} />
                                        <Toggle label="Use Card Colors" checked={bookingConfig.services.layout.showCardColors} onChange={(v) => setBookingConfig(prev => ({...prev, services: {...prev.services, layout: {...prev.services.layout, showCardColors: v}}}))} />
                                    </div>
                                    <div>
                                        <span className="text-xs font-bold text-slate-500 block mb-2">Filters</span>
                                        <Toggle label="Show Search" checked={bookingConfig.services.filters.showSearch} onChange={(v) => setBookingConfig(prev => ({...prev, services: {...prev.services, filters: {...prev.services.filters, showSearch: v}}}))} />
                                        <Toggle label="Employee Filter" checked={bookingConfig.services.filters.showEmployeeFilter} onChange={(v) => setBookingConfig(prev => ({...prev, services: {...prev.services, filters: {...prev.services.filters, showEmployeeFilter: v}}}))} />
                                        <Toggle label="Location Filter" checked={bookingConfig.services.filters.showLocationFilter} onChange={(v) => setBookingConfig(prev => ({...prev, services: {...prev.services, filters: {...prev.services.filters, showLocationFilter: v}}}))} />
                                    </div>
                                    <div>
                                        <span className="text-xs font-bold text-slate-500 block mb-2">Information</span>
                                        <Toggle label="Show VAT" checked={bookingConfig.services.info.showVat} onChange={(v) => setBookingConfig(prev => ({...prev, services: {...prev.services, info: {...prev.services.info, showVat: v}}}))} />
                                        <Toggle label="Show Price" checked={bookingConfig.services.info.showPrice} onChange={(v) => setBookingConfig(prev => ({...prev, services: {...prev.services, info: {...prev.services.info, showPrice: v}}}))} />
                                        <Toggle label="Action Button" checked={bookingConfig.services.info.showActionButton} onChange={(v) => setBookingConfig(prev => ({...prev, services: {...prev.services, info: {...prev.services.info, showActionButton: v}}}))} />
                                    </div>
                                    <div className="space-y-2">
                                        <span className="text-xs font-bold text-slate-500 block">Labels & Translation</span>
                                        <LabelInput label="Primary Button" value={bookingConfig.services.labels.primaryBtn} onChange={(v) => setBookingConfig(prev => ({...prev, services: {...prev.services, labels: {...prev.services.labels, primaryBtn: v}}}))} />
                                        <LabelInput label="Secondary Button" value={bookingConfig.services.labels.secondaryBtn} onChange={(v) => setBookingConfig(prev => ({...prev, services: {...prev.services, labels: {...prev.services.labels, secondaryBtn: v}}}))} />
                                        <LabelInput label="Book Now" value={bookingConfig.services.labels.bookNow} onChange={(v) => setBookingConfig(prev => ({...prev, services: {...prev.services, labels: {...prev.services.labels, bookNow: v}}}))} />
                                        <LabelInput label="Free" value={bookingConfig.services.labels.free} onChange={(v) => setBookingConfig(prev => ({...prev, services: {...prev.services, labels: {...prev.services.labels, free: v}}}))} />
                                    </div>
                                </div>
                            </ConfigSection>
                        </>
                    )}

                    {/* --- CUSTOMER PORTAL CONFIG (Simplified) --- */}
                    {context === 'customer' && (
                        <div className="p-4 space-y-1">
                            <Toggle label="Dashboard" checked={customerConfig.dashboard} onChange={() => setCustomerConfig(p => ({...p, dashboard: !p.dashboard}))} />
                            <Toggle label="My Profile" checked={customerConfig.profile} onChange={() => setCustomerConfig(p => ({...p, profile: !p.profile}))} />
                            <Toggle label="Appointments" checked={customerConfig.appointments} onChange={() => setCustomerConfig(p => ({...p, appointments: !p.appointments}))} />
                            <Toggle label="Courses" checked={customerConfig.courses} onChange={() => setCustomerConfig(p => ({...p, courses: !p.courses}))} />
                            <Toggle label="Quiz Attempts" checked={customerConfig.quizzes} onChange={() => setCustomerConfig(p => ({...p, quizzes: !p.quizzes}))} />
                            <div className="h-px bg-slate-100 my-2"></div>
                            <Toggle label="Settings (Notifications)" checked={customerConfig.settings} onChange={() => setCustomerConfig(p => ({...p, settings: !p.settings}))} />
                            <Toggle label="Logout Button" checked={customerConfig.logout} onChange={() => setCustomerConfig(p => ({...p, logout: !p.logout}))} />
                        </div>
                    )}

                    {/* --- EMPLOYEE PORTAL CONFIG (Simplified) --- */}
                    {context === 'employee' && (
                        <div className="p-4 space-y-1">
                            <Toggle label="Dashboard" checked={employeeConfig.dashboard} onChange={() => setEmployeeConfig(p => ({...p, dashboard: !p.dashboard}))} />
                            <Toggle label="My Schedule" checked={employeeConfig.schedule} onChange={() => setEmployeeConfig(p => ({...p, schedule: !p.schedule}))} />
                            <Toggle label="Time Tracking" checked={employeeConfig.timeTracking} onChange={() => setEmployeeConfig(p => ({...p, timeTracking: !p.timeTracking}))} />
                            <Toggle label="Absences" checked={employeeConfig.absences} onChange={() => setEmployeeConfig(p => ({...p, absences: !p.absences}))} />
                            <Toggle label="Payroll / Payslips" checked={employeeConfig.payroll} onChange={() => setEmployeeConfig(p => ({...p, payroll: !p.payroll}))} />
                        </div>
                    )}
                </div>

                {/* LIVE PREVIEW AREA */}
                <div className="flex-1 bg-slate-100 flex justify-center overflow-y-auto p-8">
                    <div 
                        style={previewStyle}
                        className={`
                            shadow-2xl transition-all duration-500 ease-in-out flex flex-col 
                            ${getPreviewWidth()} 
                            ${device === 'mobile' ? 'rounded-[3rem] border-[8px] border-slate-800' : 'rounded-lg border border-slate-300'} 
                            min-h-[600px] h-fit w-full bg-white overflow-hidden
                        `}
                    >
                        
                        {/* Browser Header */}
                        {device !== 'mobile' && (
                            <div className="h-8 bg-slate-100 border-b border-slate-200 flex items-center px-4 gap-2 shrink-0">
                                <div className="flex gap-1.5">
                                    <div className="w-3 h-3 rounded-full bg-rose-400"></div>
                                    <div className="w-3 h-3 rounded-full bg-amber-400"></div>
                                    <div className="w-3 h-3 rounded-full bg-emerald-400"></div>
                                </div>
                                <div className="flex-1 text-center text-[10px] text-slate-400 bg-white mx-8 rounded py-0.5 border border-slate-200">
                                    portal.bookando.com
                                </div>
                            </div>
                        )}

                        {/* Notch */}
                        {device === 'mobile' && (
                            <div className="absolute top-0 left-1/2 -translate-x-1/2 w-32 h-6 bg-slate-800 rounded-b-xl z-20"></div>
                        )}

                        {/* --- PREVIEW CONTENT RENDER --- */}
                        <div className={`flex-1 overflow-y-auto relative ${device === 'mobile' ? 'rounded-[2.5rem] pt-8' : ''}`} style={{ backgroundColor: 'var(--col-bg-content)' }}>
                            
                            {/* BOOKING WIDGET PREVIEW */}
                            {context === 'booking' && (
                                <div className="h-full flex flex-col">
                                    {/* Header / Sidebar Concept */}
                                    <div className="flex flex-1">
                                        {/* Optional Sidebar */}
                                        {bookingConfig.services.layout.showSidebar && (
                                            <div className="w-1/4 p-4 border-r hidden md:block" style={{ backgroundColor: 'var(--col-bg-sidebar)', color: 'var(--col-text-sidebar)', borderColor: 'var(--form-border)' }}>
                                                <div className="h-10 w-full bg-slate-200 rounded mb-6 opacity-50"></div>
                                                {bookingConfig.services.filters.showLocationFilter && <div className="h-4 w-3/4 bg-slate-200 rounded mb-2 opacity-50"></div>}
                                                {bookingConfig.services.filters.showEmployeeFilter && <div className="h-4 w-1/2 bg-slate-200 rounded mb-2 opacity-50"></div>}
                                            </div>
                                        )}

                                        <div className="flex-1 p-6 space-y-6">
                                            {/* Categories */}
                                            <h2 className="text-xl font-bold mb-4" style={{ color: 'var(--col-head-content)' }}>Categories</h2>
                                            <div className="grid grid-cols-3 gap-4 mb-8">
                                                {['Wellness', 'Fitness', 'Health'].map((cat, i) => (
                                                    <div 
                                                        key={i} 
                                                        className="rounded-lg p-4 flex flex-col items-center justify-center text-center border transition-all cursor-pointer"
                                                        style={{ 
                                                            backgroundColor: bookingConfig.categories.showPageColor ? (i===0 ? 'var(--col-primary)' : '#f8fafc') : '#fff', 
                                                            color: bookingConfig.categories.showPageColor && i===0 ? '#fff' : 'var(--col-text-content)',
                                                            borderColor: 'var(--form-border)',
                                                            borderWidth: 'var(--border-w)',
                                                            borderRadius: 'var(--border-r)'
                                                        }}
                                                    >
                                                        <span className="font-bold">{cat}</span>
                                                        {bookingConfig.categories.showCount && <span className="text-xs opacity-70">4 services</span>}
                                                    </div>
                                                ))}
                                            </div>

                                            {/* Service List */}
                                            <h2 className="text-xl font-bold mb-4" style={{ color: 'var(--col-head-content)' }}>{bookingConfig.services.labels.service}s</h2>
                                            
                                            {bookingConfig.services.filters.showSearch && (
                                                <div className="mb-4">
                                                    <div className="w-full h-10 border rounded px-3 flex items-center text-sm text-slate-400" style={{ borderColor: 'var(--form-border)', borderRadius: 'var(--border-r)', backgroundColor: 'var(--form-bg)' }}>Search...</div>
                                                </div>
                                            )}

                                            <div className="space-y-4">
                                                {[1, 2].map((item) => (
                                                    <div 
                                                        key={item} 
                                                        className="border p-4 flex gap-4 items-start transition-all"
                                                        style={{ 
                                                            backgroundColor: bookingConfig.services.layout.showBackground ? '#fff' : 'transparent',
                                                            borderColor: 'var(--form-border)',
                                                            borderWidth: 'var(--border-w)',
                                                            borderRadius: 'var(--border-r)'
                                                        }}
                                                    >
                                                        <div className="w-16 h-16 bg-slate-100 rounded-lg shrink-0" style={{ borderRadius: 'var(--border-r)' }}></div>
                                                        <div className="flex-1">
                                                            <div className="flex justify-between items-start">
                                                                <h3 className="font-bold" style={{ color: 'var(--col-head-content)' }}>Deep Tissue Massage</h3>
                                                                {bookingConfig.services.info.showPrice && <span className="font-bold" style={{ color: 'var(--col-primary)' }}>$120</span>}
                                                            </div>
                                                            <p className="text-sm mt-1 mb-2" style={{ color: 'var(--col-text-content)' }}>Relieve tension with this intensive treatment.</p>
                                                            
                                                            <div className="flex items-center gap-4 text-xs" style={{ color: 'var(--col-text-content)' }}>
                                                                <span>60 min</span>
                                                                {bookingConfig.services.info.showVat && <span>{bookingConfig.services.labels.inclVat}</span>}
                                                            </div>

                                                            <div className="flex justify-end mt-3 gap-2">
                                                                {bookingConfig.services.info.showActionButton && (
                                                                    <button 
                                                                        className="px-4 py-2 rounded text-sm font-medium"
                                                                        style={{ 
                                                                            backgroundColor: 'var(--btn-prim-bg)', 
                                                                            color: 'var(--btn-prim-text)',
                                                                            borderRadius: 'var(--border-r)',
                                                                            border: bookingConfig.global.borders.btnGlobalBorder ? `1px solid var(--form-border)` : 'none'
                                                                        }}
                                                                    >
                                                                        {bookingConfig.services.labels.bookNow}
                                                                    </button>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* CUSTOMER PORTAL PREVIEW */}
                            {context === 'customer' && (
                                <div className="flex h-full flex-col font-sans text-slate-600">
                                    <div className="h-14 border-b border-slate-100 flex items-center justify-between px-4 shrink-0 bg-white">
                                        <div className="font-bold text-brand-600">Logo</div>
                                        <div className="w-8 h-8 bg-slate-200 rounded-full"></div>
                                    </div>
                                    <div className="flex flex-1 bg-slate-50">
                                        {device !== 'mobile' && (
                                            <div className="w-48 border-r border-slate-100 bg-white p-2 space-y-1">
                                                {customerConfig.dashboard && <div className="p-2 bg-brand-50 text-brand-700 text-xs font-bold rounded">Dashboard</div>}
                                                {customerConfig.profile && <div className="p-2 hover:bg-slate-50 text-slate-600 text-xs rounded">My Profile</div>}
                                                {customerConfig.appointments && <div className="p-2 hover:bg-slate-50 text-slate-600 text-xs rounded">Appointments</div>}
                                                <div className="mt-auto pt-4">
                                                    {customerConfig.logout && <div className="p-2 text-rose-600 text-xs rounded font-medium">Logout</div>}
                                                </div>
                                            </div>
                                        )}
                                        <div className="flex-1 p-6 space-y-4">
                                            <div className="h-24 bg-white rounded-lg border border-slate-200 p-4 shadow-sm">
                                                <div className="h-4 w-32 bg-slate-100 rounded mb-2"></div>
                                                <div className="h-8 w-16 bg-slate-100 rounded"></div>
                                            </div>
                                        </div>
                                    </div>
                                    {device === 'mobile' && (
                                        <div className="h-14 border-t border-slate-100 flex justify-around items-center px-2 bg-white">
                                            {customerConfig.dashboard && <div className="w-6 h-6 bg-brand-100 rounded-full"></div>}
                                            {customerConfig.appointments && <div className="w-6 h-6 bg-slate-200 rounded-full"></div>}
                                            {customerConfig.settings && <div className="w-6 h-6 bg-slate-200 rounded-full"></div>}
                                        </div>
                                    )}
                                </div>
                            )}

                            {/* EMPLOYEE PORTAL PREVIEW */}
                            {context === 'employee' && (
                                <div className="flex h-full flex-col bg-slate-50 font-sans">
                                    <div className="bg-slate-800 text-white p-4">
                                        <div className="font-bold">Employee Hub</div>
                                    </div>
                                    <div className="p-4 space-y-4">
                                        {employeeConfig.dashboard && (
                                            <div className="bg-white p-4 rounded shadow-sm">
                                                <h5 className="text-xs font-bold text-slate-400 uppercase mb-2">Today</h5>
                                                <div className="flex gap-2">
                                                    <div className="h-12 w-12 bg-brand-100 rounded-full"></div>
                                                    <div className="space-y-1">
                                                        <div className="h-3 w-32 bg-slate-200 rounded"></div>
                                                        <div className="h-3 w-20 bg-slate-200 rounded"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}

                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

const NotificationsTool = () => {
   const { t } = useApp();
   type NotificationTemplate = {
      id: number;
      name: string;
      type: 'Email' | 'SMS';
      active: boolean;
      subject: string;
      body: string;
   };

   const initialTemplates: NotificationTemplate[] = [
      {
         id: 1,
         name: 'Booking Confirmation',
         type: 'Email',
         active: true,
         subject: 'Booking Confirmed: {{service_name}} with {{company_name}}',
         body: `Hi {{customer_first_name}},

We are excited to confirm your booking for {{service_name}}.

Date: {{booking_date}}
Time: {{booking_time}}
Location: {{location_address}}

If you need to reschedule, please click here: {{reschedule_link}}

Best regards,
The {{company_name}} Team`,
      },
      {
         id: 2,
         name: 'Appointment Reminder (24h)',
         type: 'SMS',
         active: true,
         subject: 'Reminder: {{service_name}} tomorrow at {{booking_time}}',
         body: 'Reminder: Your appointment for {{service_name}} is tomorrow at {{booking_time}}. Reply YES to confirm.',
      },
      {
         id: 3,
         name: 'Payment Receipt',
         type: 'Email',
         active: true,
         subject: 'Payment received for {{service_name}}',
         body: 'Hi {{customer_first_name}}, we have received your payment for {{service_name}}. Receipt #: {{receipt_number}}.',
      },
      {
         id: 4,
         name: 'Review Request',
         type: 'Email',
         active: false,
         subject: 'How was your {{service_name}}?',
         body: 'Hi {{customer_first_name}}, please share feedback for your recent {{service_name}}: {{review_link}}.',
      },
   ];

   const [templates, setTemplates] = useState<NotificationTemplate[]>(initialTemplates);
   const [selectedTemplateId, setSelectedTemplateId] = useState<number>(initialTemplates[0].id);
   const textareaRef = useRef<HTMLTextAreaElement | null>(null);

   const selectedTemplate = templates.find(t => t.id === selectedTemplateId);

   const updateSelectedTemplate = (changes: Partial<NotificationTemplate>) => {
      setTemplates(prev =>
         prev.map(template => (template.id === selectedTemplateId ? { ...template, ...changes } : template)),
      );
   };

   const handleNewTemplate = () => {
      const newTemplate: NotificationTemplate = {
         id: templates.length ? Math.max(...templates.map(t => t.id)) + 1 : 1,
         name: `New Template ${templates.length + 1}`,
         type: 'Email',
         active: true,
         subject: '',
         body: '',
      };

      setTemplates(prev => [...prev, newTemplate]);
      setSelectedTemplateId(newTemplate.id);
   };

   const handleWrapAction = (
      prefix: string,
      suffix: string,
      placeholder: string,
      body = selectedTemplate?.body ?? '',
   ) => {
      const textarea = textareaRef.current;
      if (!textarea || !selectedTemplate) return;

      const selectionStart = textarea.selectionStart ?? body.length;
      const selectionEnd = textarea.selectionEnd ?? body.length;
      const before = body.slice(0, selectionStart);
      const after = body.slice(selectionEnd);
      const selectedText = body.slice(selectionStart, selectionEnd);
      const hasSelection = selectedText.length > 0;

      const isWrapped =
         hasSelection &&
         selectionStart >= prefix.length &&
         selectionEnd + suffix.length <= body.length &&
         body.slice(selectionStart - prefix.length, selectionEnd + suffix.length) ===
            `${prefix}${selectedText}${suffix}`;

      let newValue: string;
      let newSelectionStart: number;
      let newSelectionEnd: number;

      if (isWrapped) {
         newValue =
            body.slice(0, selectionStart - prefix.length) + selectedText + body.slice(selectionEnd + suffix.length);
         newSelectionStart = selectionStart - prefix.length;
         newSelectionEnd = newSelectionStart + selectedText.length;
      } else {
         const insertionText = hasSelection ? selectedText : placeholder;
         newValue = `${before}${prefix}${insertionText}${suffix}${after}`;
         newSelectionStart = selectionStart + prefix.length;
         newSelectionEnd = newSelectionStart + insertionText.length;
      }

      updateSelectedTemplate({ body: newValue });

      requestAnimationFrame(() => {
         textarea.focus();
         textarea.setSelectionRange(newSelectionStart, newSelectionEnd);
      });
   };

   const handleLinkAction = () => {
      const textarea = textareaRef.current;
      const body = selectedTemplate?.body ?? '';
      if (!textarea || !selectedTemplate) return;

      const selectionStart = textarea.selectionStart ?? body.length;
      const selectionEnd = textarea.selectionEnd ?? body.length;
      const before = body.slice(0, selectionStart);
      const after = body.slice(selectionEnd);
      const selectedText = body.slice(selectionStart, selectionEnd);

      const linkPattern = /^\[([^\]]+)\]\(([^)]+)\)$/;
      const matches = selectedText.match(linkPattern);

      let newValue = body;
      let newSelectionStart = selectionStart;
      let newSelectionEnd = selectionEnd;

      if (matches) {
         const plainText = matches[1];
         newValue = `${before}${plainText}${after}`;
         newSelectionStart = selectionStart;
         newSelectionEnd = selectionStart + plainText.length;
      } else {
         const text = selectedText || 'link text';
         const linkMarkup = `[${text}](https://example.com)`;
         newValue = `${before}${linkMarkup}${after}`;
         newSelectionStart = selectionStart + 1;
         newSelectionEnd = newSelectionStart + text.length;
      }

      updateSelectedTemplate({ body: newValue });

      requestAnimationFrame(() => {
         textarea.focus();
         textarea.setSelectionRange(newSelectionStart, newSelectionEnd);
      });
   };

   return (
      <div className="flex-1 flex flex-col h-full">
         <div className="flex-1 flex overflow-hidden">
            {/* Template List */}
            <div className="w-72 border-r border-slate-200 bg-white flex flex-col flex-shrink-0">
               <div className="p-4 border-b border-slate-100">
               <h3 className="font-bold text-lg text-slate-800 mb-2">Templates</h3>
                  <button
                     className="w-full py-2 bg-brand-50 text-brand-700 rounded-lg text-sm font-medium flex justify-center items-center gap-2"
                     onClick={handleNewTemplate}
                  >
                     + New Template
                  </button>
               </div>
               <div className="overflow-y-auto p-2 space-y-1">
                  {templates.map(t => (
                     <button
                        key={t.id}
                        onClick={() => setSelectedTemplateId(t.id)}
                        className={`w-full text-left p-3 rounded-lg border transition-all group ${
                           selectedTemplateId === t.id
                              ? 'bg-brand-50 border-brand-200 shadow-sm'
                              : 'hover:bg-slate-50 border-transparent hover:border-slate-200'
                        }`}
                     >
                        <div className="flex justify-between items-center mb-1">
                           <span className="font-medium text-sm text-slate-800">{t.name}</span>
                           {t.active && <div className="w-2 h-2 rounded-full bg-emerald-500"></div>}
                        </div>
                        <span className="text-xs text-slate-500 flex items-center gap-1">
                           {t.type === 'Email' ? <FileText size={12} /> : <Bell size={12} />} {t.type}
                        </span>
                     </button>
                  ))}
               </div>
            </div>

            {/* Editor */}
            <div className="flex-1 flex flex-col bg-white">
               <div className="p-6 border-b border-slate-100 flex justify-between items-center">
                  <div>
                     <h3 className="font-bold text-lg text-slate-800">{selectedTemplate?.name}</h3>
                     <p className="text-sm text-slate-500">Trigger: After successful booking</p>
                  </div>
                  <button
                     className={`flex items-center gap-2 cursor-pointer px-2 py-1 rounded-full border transition-colors ${
                        selectedTemplate?.active ? 'bg-emerald-50 border-emerald-200' : 'bg-slate-50 border-slate-200'
                     }`}
                     onClick={() => updateSelectedTemplate({ active: !selectedTemplate?.active })}
                     aria-pressed={selectedTemplate?.active}
                     aria-label="Toggle active"
                  >
                     <span className="text-sm font-medium text-slate-600">Active</span>
                     <div
                        className={`w-11 h-6 rounded-full relative transition-colors ${
                           selectedTemplate?.active ? 'bg-emerald-500' : 'bg-slate-300'
                        }`}
                     >
                        <div
                           className={`absolute top-1 w-4 h-4 bg-white rounded-full shadow-sm transition-all ${
                              selectedTemplate?.active ? 'right-1' : 'left-1'
                           }`}
                        ></div>
                     </div>
                  </button>
               </div>

               <div className="flex-1 p-6 overflow-y-auto space-y-6">
                  <div>
                     <label className="block text-sm font-medium text-slate-700 mb-2">Subject Line</label>
                     <input
                        className="w-full border border-slate-300 rounded-lg px-3 py-2"
                        value={selectedTemplate?.subject ?? ''}
                        onChange={e => updateSelectedTemplate({ subject: e.target.value })}
                     />
                  </div>

                  <div className="flex-1 flex flex-col h-96">
                     <label className="block text-sm font-medium text-slate-700 mb-2">Email Body</label>
                     <div className="border border-slate-300 rounded-lg flex-1 flex flex-col overflow-hidden">
                        <div className="bg-slate-50 border-b border-slate-200 p-2 flex gap-2">
                           <button
                              className="px-2 py-1 bg-white border border-slate-200 rounded text-xs font-medium hover:bg-slate-100"
                              onClick={() => handleWrapAction('**', '**', 'bold text')}
                           >
                              Bold
                           </button>
                           <button
                              className="px-2 py-1 bg-white border border-slate-200 rounded text-xs font-medium hover:bg-slate-100"
                              onClick={() => handleWrapAction('*', '*', 'italic text')}
                           >
                              Italic
                           </button>
                           <button
                              className="px-2 py-1 bg-white border border-slate-200 rounded text-xs font-medium hover:bg-slate-100"
                              onClick={handleLinkAction}
                           >
                              Link
                           </button>
                           <button
                              className="px-2 py-1 bg-white border border-slate-200 rounded text-xs font-medium hover:bg-slate-100"
                              onClick={() => handleWrapAction('{{', '}}', 'variable_name')}
                           >
                              Variable
                           </button>
                        </div>
                        <textarea
                           className="flex-1 p-4 resize-none focus:outline-none font-mono text-sm"
                           ref={textareaRef}
                           value={selectedTemplate?.body ?? ''}
                           onChange={e => updateSelectedTemplate({ body: e.target.value })}
                        />
                     </div>
                     <div className="mt-2 text-xs text-slate-500">
                        Available Variables: <code className="bg-slate-100 px-1 rounded">{`{{customer_name}}`}</code>, <code className="bg-slate-100 px-1 rounded">{`{{booking_date}}`}</code>, <code className="bg-slate-100 px-1 rounded">{`{{service_name}}`}</code>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
   );
};

type LogLevel = 'Info' | 'Warning' | 'Error';
type LogEntry = {
   id: number;
   time: string;
   level: LogLevel;
   message: string;
   user: string;
};

const initialLogs: LogEntry[] = [
   { id: 1, time: '10:42:15', level: 'Info', message: 'Backup completed successfully', user: 'System' },
   { id: 2, time: '10:30:00', level: 'Warning', message: 'High memory usage detected (85%)', user: 'Monitor' },
   { id: 3, time: '09:15:22', level: 'Info', message: 'User Sarah J. updated settings', user: 'Sarah J.' },
   { id: 4, time: '08:45:33', level: 'Error', message: 'Failed to sync analytics service', user: 'Scheduler' },
   { id: 5, time: '08:00:01', level: 'Info', message: 'Daily cron jobs started', user: 'System' },
];

const SystemTools = () => {
   const { t } = useApp();
   const [logs, setLogs] = useState<LogEntry[]>(initialLogs);
   const [isFilterOpen, setIsFilterOpen] = useState(false);
   const [isDownloadOpen, setIsDownloadOpen] = useState(false);
   const [isRefreshing, setIsRefreshing] = useState(false);
   const [activeCacheAction, setActiveCacheAction] = useState<'app' | 'api' | 'all' | null>(null);
   const [lastUpdated, setLastUpdated] = useState<Date>(new Date());
   const [refreshError, setRefreshError] = useState<string | null>(null);
   const [cacheMessage, setCacheMessage] = useState<{ type: 'success' | 'error'; message: string } | null>(null);
   const [downloadError, setDownloadError] = useState<string | null>(null);
   const [appliedFilters, setAppliedFilters] = useState({ level: 'all', user: '', query: '' });
   const [draftFilters, setDraftFilters] = useState(appliedFilters);

   const hasActiveFilters = useMemo(
      () => appliedFilters.level !== 'all' || appliedFilters.user.trim() !== '' || appliedFilters.query.trim() !== '',
      [appliedFilters]
   );

   const filteredLogs = useMemo(() => {
      return logs.filter(log => {
         const levelMatch = appliedFilters.level === 'all' || log.level === appliedFilters.level;
         const userMatch = appliedFilters.user.trim() === '' || log.user.toLowerCase().includes(appliedFilters.user.toLowerCase());
         const queryMatch = appliedFilters.query.trim() === ''
            || log.message.toLowerCase().includes(appliedFilters.query.toLowerCase())
            || log.time.toLowerCase().includes(appliedFilters.query.toLowerCase());
         return levelMatch && userMatch && queryMatch;
      });
   }, [logs, appliedFilters]);

   const isCacheActionPending = activeCacheAction !== null;

   const handleApplyFilters = () => {
      setAppliedFilters(draftFilters);
      setIsFilterOpen(false);
   };

   const handleResetFilters = () => {
      const defaults = { level: 'all', user: '', query: '' };
      setAppliedFilters(defaults);
      setDraftFilters(defaults);
   };

   const handleRefresh = async () => {
      setIsRefreshing(true);
      setRefreshError(null);
      try {
         await new Promise(resolve => setTimeout(resolve, 900));
         const now = new Date();
         const refreshed: LogEntry[] = [
            {
               id: now.getTime(),
               time: now.toLocaleTimeString('en-GB', { hour12: false }),
               level: 'Info',
               message: 'Activity feed refreshed from server',
               user: 'System'
            },
            ...initialLogs,
         ];
         setLogs(refreshed);
         setLastUpdated(now);
      } catch (error) {
         setRefreshError('Aktualisierung fehlgeschlagen. Bitte erneut versuchen.');
      } finally {
         setIsRefreshing(false);
      }
   };

   const mockInvalidateCache = async (target: 'app' | 'api' | 'all') => {
      await new Promise(resolve => setTimeout(resolve, 800));
      return { invalidated: target };
   };

   const handleCacheAction = async (target: 'app' | 'api' | 'all') => {
      setActiveCacheAction(target);
      setCacheMessage(null);
      try {
         await mockInvalidateCache(target);
         const label = target === 'all' ? 'Alle Caches' : target === 'app' ? 'App Cache' : 'API Cache';
         setCacheMessage({ type: 'success', message: `${label} erfolgreich invalidiert.` });

         if (target === 'all') {
            await handleRefresh();
         }
      } catch (error) {
         setCacheMessage({ type: 'error', message: 'Cache konnte nicht geleert werden. Bitte erneut versuchen.' });
      } finally {
         setActiveCacheAction(null);
      }
   };

   const handleDownload = (format: 'csv' | 'json') => {
      setDownloadError(null);
      try {
         let content = '';
         let mime = '';

         if (format === 'json') {
            content = JSON.stringify(filteredLogs, null, 2);
            mime = 'application/json';
         } else {
            const headers = ['time', 'level', 'message', 'user'];
            const rows = filteredLogs.map(log => [log.time, log.level, log.message, log.user]
               .map(value => `"${value.replace(/"/g, '""')}"`).join(','));
            content = [headers.join(','), ...rows].join('\n');
            mime = 'text/csv';
         }

         const blob = new Blob([content], { type: mime });
         const url = URL.createObjectURL(blob);
         const link = document.createElement('a');
         link.href = url;
         link.download = `activity-logs.${format}`;
         link.click();
         URL.revokeObjectURL(url);
         setIsDownloadOpen(false);
      } catch (error) {
         setDownloadError('Export fehlgeschlagen. Bitte erneut versuchen.');
      }
   };

   return (
      <div className="flex-1 flex flex-col overflow-hidden">
         <div className="p-6 grid grid-cols-1 md:grid-cols-3 gap-6 border-b border-slate-100">
            <div className="bg-white p-4 border border-slate-200 rounded-xl flex items-center gap-4 shadow-sm">
               <div className="p-3 bg-emerald-50 text-emerald-600 rounded-lg"><Server size={24} /></div>
               <div>
                  <div className="text-sm text-slate-500">System Status</div>
                  <div className="font-bold text-slate-800">Operational</div>
               </div>
            </div>
            <div className="bg-white p-4 border border-slate-200 rounded-xl flex items-center gap-4 shadow-sm">
               <div className="p-3 bg-blue-50 text-blue-600 rounded-lg"><Cpu size={24} /></div>
               <div>
                  <div className="text-sm text-slate-500">CPU Load</div>
                  <div className="font-bold text-slate-800">12% Avg</div>
               </div>
            </div>
            <div className="bg-white p-4 border border-slate-200 rounded-xl flex items-center gap-4 shadow-sm">
               <div className="p-3 bg-amber-50 text-amber-600 rounded-lg"><HardDrive size={24} /></div>
               <div>
                  <div className="text-sm text-slate-500">Storage</div>
                  <div className="font-bold text-slate-800">450GB Free</div>
               </div>
            </div>
         </div>

         <div className="flex-1 p-6 overflow-y-auto">
            <div className="flex flex-col gap-3 mb-4">
               <div className="flex justify-between items-center gap-3 flex-wrap">
                  <h3 className="font-bold text-slate-800">Activity Logs</h3>
                  <div className="flex gap-2 relative">
                     <div className="relative">
                        <button
                           onClick={() => setIsFilterOpen(v => !v)}
                           className={`p-2 rounded-lg border text-sm font-medium flex items-center gap-2 transition-colors ${
                              hasActiveFilters ? 'border-brand-200 text-brand-700 bg-brand-50' : 'border-slate-200 text-slate-600 hover:bg-slate-50'
                           }`}
                        >
                           <Filter size={16} />
                           Filter
                           {hasActiveFilters && <span className="ml-1 text-xs text-brand-700">●</span>}
                        </button>
                        {isFilterOpen && (
                           <div className="absolute right-0 mt-2 w-72 bg-white border border-slate-200 rounded-xl shadow-lg z-20">
                              <div className="p-3 border-b border-slate-100">
                                 <div className="font-semibold text-slate-800">Filter erstellen</div>
                                 <p className="text-xs text-slate-500">Einschränken nach Level, Benutzer oder Textsuche.</p>
                              </div>
                              <div className="p-3 space-y-3">
                                 <div className="flex flex-col gap-1">
                                    <label className="text-xs font-medium text-slate-500">Level</label>
                                    <select
                                       value={draftFilters.level}
                                       onChange={e => setDraftFilters({ ...draftFilters, level: e.target.value })}
                                       className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-200"
                                    >
                                       <option value="all">Alle</option>
                                       <option value="Info">Info</option>
                                       <option value="Warning">Warning</option>
                                       <option value="Error">Error</option>
                                    </select>
                                 </div>
                                 <div className="flex flex-col gap-1">
                                    <label className="text-xs font-medium text-slate-500">Benutzer</label>
                                    <input
                                       type="text"
                                       value={draftFilters.user}
                                       onChange={e => setDraftFilters({ ...draftFilters, user: e.target.value })}
                                       placeholder="z.B. System, Sarah"
                                       className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-200"
                                    />
                                 </div>
                                 <div className="flex flex-col gap-1">
                                    <label className="text-xs font-medium text-slate-500">Suche</label>
                                    <input
                                       type="text"
                                       value={draftFilters.query}
                                       onChange={e => setDraftFilters({ ...draftFilters, query: e.target.value })}
                                       placeholder="Nach Meldung oder Zeit filtern"
                                       className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-200"
                                    />
                                 </div>
                              </div>
                              <div className="flex items-center justify-between p-3 border-t border-slate-100 bg-slate-50 rounded-b-xl">
                                 <button
                                    onClick={handleResetFilters}
                                    className="text-xs font-semibold text-slate-500 hover:text-slate-700"
                                 >
                                    Zurücksetzen
                                 </button>
                                 <div className="flex gap-2">
                                    <button
                                       onClick={() => setIsFilterOpen(false)}
                                       className="px-3 py-1.5 text-xs font-medium text-slate-600 hover:text-slate-800"
                                    >
                                       Abbrechen
                                    </button>
                                    <button
                                       onClick={handleApplyFilters}
                                       className="px-3 py-1.5 text-xs font-semibold bg-brand-600 text-white rounded-lg hover:bg-brand-700"
                                    >
                                       Anwenden
                                    </button>
                                 </div>
                              </div>
                           </div>
                        )}
                     </div>

                     <button
                        onClick={handleRefresh}
                        disabled={isRefreshing}
                        className={`p-2 rounded-lg border text-sm font-medium flex items-center gap-2 transition-colors ${
                           isRefreshing ? 'border-slate-200 text-slate-400 bg-slate-50 cursor-not-allowed' : 'border-slate-200 text-slate-600 hover:bg-slate-50'
                        }`}
                     >
                        {isRefreshing ? <Loader2 size={16} className="animate-spin" /> : <RotateCcw size={16} />}
                        {isRefreshing ? 'Aktualisiere...' : 'Refresh'}
                     </button>

                     <div className="relative">
                        <button
                           onClick={() => setIsDownloadOpen(v => !v)}
                           className="p-2 rounded-lg border border-slate-200 text-sm font-medium flex items-center gap-2 text-slate-600 hover:bg-slate-50"
                        >
                           <Download size={16} />
                           Download
                        </button>
                        {isDownloadOpen && (
                           <div className="absolute right-0 mt-2 w-48 bg-white border border-slate-200 rounded-xl shadow-lg z-20">
                              <div className="p-3 border-b border-slate-100">
                                 <div className="font-semibold text-slate-800 text-sm">Export</div>
                                 <p className="text-xs text-slate-500">Gefilterte Liste exportieren.</p>
                              </div>
                              <div className="p-2 flex flex-col">
                                 <button
                                    onClick={() => handleDownload('csv')}
                                    className="px-3 py-2 text-left text-sm rounded-lg hover:bg-slate-50"
                                 >
                                    CSV herunterladen
                                 </button>
                                 <button
                                    onClick={() => handleDownload('json')}
                                    className="px-3 py-2 text-left text-sm rounded-lg hover:bg-slate-50"
                                 >
                                    JSON herunterladen
                                 </button>
                              </div>
                           </div>
                        )}
                     </div>
                  </div>
               </div>

               <div className="flex items-center gap-3 text-xs text-slate-500">
                  <span className="px-2 py-1 rounded-full bg-slate-100 text-slate-600 font-semibold">{filteredLogs.length} Einträge</span>
                  <span>Zuletzt aktualisiert um {lastUpdated.toLocaleTimeString('de-DE')}</span>
                  {hasActiveFilters && <span className="flex items-center gap-1 text-brand-700">● Filter aktiv</span>}
               </div>
            </div>

            {refreshError && (
               <div className="mb-3 flex items-center gap-2 text-sm text-rose-600 bg-rose-50 border border-rose-200 rounded-lg px-3 py-2">
                  <AlertCircle size={16} /> {refreshError}
               </div>
            )}
            {downloadError && (
               <div className="mb-3 flex items-center gap-2 text-sm text-rose-600 bg-rose-50 border border-rose-200 rounded-lg px-3 py-2">
                  <AlertCircle size={16} /> {downloadError}
               </div>
            )}

            <div className="border border-slate-200 rounded-xl overflow-hidden">
               <table className="w-full text-left text-sm">
                  <thead className="bg-slate-50 border-b border-slate-200">
                     <tr>
                        <th className="p-3 font-medium text-slate-500">Timestamp</th>
                        <th className="p-3 font-medium text-slate-500">Level</th>
                        <th className="p-3 font-medium text-slate-500">Message</th>
                        <th className="p-3 font-medium text-slate-500">User</th>
                     </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                     {filteredLogs.length === 0 && (
                        <tr>
                           <td colSpan={4} className="p-6 text-center text-slate-500">Keine Logs für die aktuellen Filter gefunden.</td>
                        </tr>
                     )}
                     {filteredLogs.map(log => (
                        <tr key={log.id} className="hover:bg-slate-50">
                           <td className="p-3 text-slate-600 font-mono text-xs">{log.time}</td>
                           <td className="p-3">
                              <span className={`px-2 py-0.5 rounded text-xs font-medium ${
                                 log.level === 'Info' ? 'bg-blue-50 text-blue-700' :
                                 log.level === 'Warning' ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700'
                              }`}>
                                 {log.level}
                              </span>
                           </td>
                           <td className="p-3 text-slate-800">{log.message}</td>
                           <td className="p-3 text-slate-500">{log.user}</td>
                        </tr>
                     ))}
                  </tbody>
               </table>
            </div>

            <div className="mt-6 p-4 bg-slate-50 rounded-xl border border-slate-200">
               <h4 className="font-bold text-slate-800 mb-2 flex items-center gap-2">
                  <RotateCcw size={16} /> Cache Management
               </h4>
               <p className="text-sm text-slate-500 mb-4">Clear system caches to reflect recent changes immediately.</p>
               {cacheMessage && (
                  <div
                     className={`mb-3 flex items-center gap-2 text-sm px-3 py-2 rounded-lg border ${
                        cacheMessage.type === 'success'
                           ? 'text-emerald-700 bg-emerald-50 border-emerald-200'
                           : 'text-rose-700 bg-rose-50 border-rose-200'
                     }`}
                  >
                     {cacheMessage.type === 'success' ? <CheckCircle size={16} /> : <AlertCircle size={16} />}
                     {cacheMessage.message}
                  </div>
               )}

               <div className="flex gap-3">
                  <button
                     onClick={() => handleCacheAction('app')}
                     disabled={isCacheActionPending}
                     className={`px-3 py-2 bg-white border border-slate-300 rounded-lg text-sm font-medium flex items-center gap-2 ${
                        isCacheActionPending ? 'opacity-60 cursor-not-allowed' : 'hover:bg-slate-50'
                     }`}
                  >
                     {activeCacheAction === 'app' && <Loader2 size={16} className="animate-spin" />}
                     Clear App Cache
                  </button>
                  <button
                     onClick={() => handleCacheAction('api')}
                     disabled={isCacheActionPending}
                     className={`px-3 py-2 bg-white border border-slate-300 rounded-lg text-sm font-medium flex items-center gap-2 ${
                        isCacheActionPending ? 'opacity-60 cursor-not-allowed' : 'hover:bg-slate-50'
                     }`}
                  >
                     {activeCacheAction === 'api' && <Loader2 size={16} className="animate-spin" />}
                     Clear API Cache
                  </button>
                  <button
                     onClick={() => handleCacheAction('all')}
                     disabled={isCacheActionPending}
                     className={`px-3 py-2 bg-rose-50 border border-rose-200 text-rose-700 rounded-lg text-sm font-medium flex items-center gap-2 ${
                        isCacheActionPending ? 'opacity-60 cursor-not-allowed' : 'hover:bg-rose-100'
                     }`}
                  >
                     {activeCacheAction === 'all' && <Loader2 size={16} className="animate-spin" />}
                     Purge All
                  </button>
               </div>
            </div>
         </div>
      </div>
   );
};

export default ToolsModule;
