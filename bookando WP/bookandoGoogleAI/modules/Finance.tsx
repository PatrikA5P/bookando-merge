
import React, { useState, useRef, useEffect } from 'react';
import { Invoice, InvoiceStatus, DunningLevel, Account, SoldVoucher, PayrollRun, VatRate, FiscalYear, InvoiceTemplate, VatTaxType, CompanySettings } from '../types';
import { 
  FileText, Download, Send, PlusCircle, PieChart, TrendingUp, 
  AlertCircle, Wallet, Receipt, Users, Gift, CreditCard, 
  Landmark, ArrowRight, Search, Filter, MoreHorizontal, CheckCircle, XCircle,
  CalendarDays, Percent, Book, Settings, FileInput, Briefcase, Edit2, Trash2, Save, X,
  Layout, Type, Image as ImageIcon, Palette, MapPin, AlignLeft, AlignRight, Plus, QrCode
} from 'lucide-react';
import { useApp } from '../context/AppContext';
import { generateQRBillData, renderQRBillImage } from '../utils/qrBill';

// --- SWISS QR VISUAL COMPONENT (A6) ---
const SwissQrBillPreview: React.FC<{ invoice: Invoice; company: CompanySettings; onClose: () => void }> = ({ invoice, company, onClose }) => {
    const [qrCodeSvg, setQrCodeSvg] = useState<string>('');

    useEffect(() => {
        const load = async () => {
            try {
                const data = generateQRBillData(invoice, company);
                const svg = await renderQRBillImage(data);
                setQrCodeSvg(svg);
            } catch (error) {
                console.error("Failed to render QR Bill:", error);
            }
        };
        load();
    }, [invoice, company]);

    return (
        <div className="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/80 backdrop-blur-sm p-4">
            <div className="bg-white w-[230mm] h-[90vh] shadow-2xl rounded-lg overflow-hidden flex flex-col">
                <div className="p-4 bg-slate-50 border-b flex justify-between items-center shrink-0">
                    <h3 className="font-bold text-slate-800 flex items-center gap-2"><QrCode size={20}/> Swiss QR Bill Preview</h3>
                    <button onClick={onClose}><X size={24} className="text-slate-500 hover:text-slate-700"/></button>
                </div>
                
                <div className="flex-1 overflow-y-auto bg-slate-100 p-8 flex justify-center">
                    {/* A4 Page Simulation */}
                    <div className="bg-white w-[210mm] min-h-[297mm] shadow-lg flex flex-col relative">
                        
                        {/* Invoice Body (Mock Content for Context) */}
                        <div className="p-[20mm] flex-1">
                            <div className="flex justify-between items-start mb-12">
                                <div>
                                    <h1 className="text-2xl font-bold text-slate-800">INVOICE</h1>
                                    <p className="text-slate-500">#{invoice.id}</p>
                                    <p className="text-sm text-slate-500 mt-1">Date: {invoice.date}</p>
                                </div>
                                <div className="text-right text-sm text-slate-600">
                                    <p className="font-bold text-slate-800">{company.name}</p>
                                    <p>{company.address}</p>
                                    <p>{company.zip} {company.city}</p>
                                    <p>{company.country}</p>
                                </div>
                            </div>

                            <div className="mb-12 bg-slate-50 p-4 rounded border border-slate-100">
                                <p className="text-xs font-bold text-slate-400 uppercase mb-1">Billed to</p>
                                <p className="font-bold text-slate-800">{invoice.client}</p>
                                {/* Mock address since invoice object only has client name */}
                                <p className="text-sm text-slate-600">Musterstrasse 12</p>
                                <p className="text-sm text-slate-600">8000 Zürich</p>
                            </div>

                            <table className="w-full text-sm mb-8">
                                <thead className="border-b border-slate-200">
                                    <tr>
                                        <th className="py-2 text-left font-semibold text-slate-600">Description</th>
                                        <th className="py-2 text-right font-semibold text-slate-600">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td className="py-4 text-slate-800">Services Rendered</td>
                                        <td className="py-4 text-right font-mono">{invoice.amount.toFixed(2)} {invoice.currency || 'CHF'}</td>
                                    </tr>
                                </tbody>
                                <tfoot className="border-t border-slate-200 font-bold">
                                    <tr>
                                        <td className="py-4 text-right">Total</td>
                                        <td className="py-4 text-right font-mono text-lg">{invoice.amount.toFixed(2)} {invoice.currency || 'CHF'}</td>
                                    </tr>
                                </tfoot>
                            </table>
                            
                            <div className="text-sm text-slate-500 mt-8">
                                <p>Payment terms: 30 days.</p>
                                <p>Thank you for your business.</p>
                            </div>
                        </div>

                        {/* QR Bill Section - Pushed to bottom */}
                        <div className="mt-auto">
                            {/* Optional Separator Line for visual clarity if not perforated */}
                            <div className="border-t border-dashed border-slate-300 w-full relative mb-0">
                                <span className="absolute -top-3 left-8 bg-white px-1 text-slate-400 text-xs">✂️</span>
                            </div>
                            
                            {/* SVG Container - 105mm height for A6 landscape */}
                            {qrCodeSvg ? (
                                <div 
                                    dangerouslySetInnerHTML={{ __html: qrCodeSvg }} 
                                    className="w-full block" 
                                    style={{ height: '105mm' }} 
                                />
                            ) : (
                                <div className="h-[105mm] flex items-center justify-center bg-slate-50 text-slate-400">
                                    Generating QR Bill...
                                </div>
                            )}
                        </div>
                    </div>
                </div>
                
                <div className="p-4 bg-slate-50 border-t flex justify-end shrink-0">
                    <button className="bg-slate-800 text-white px-4 py-2 rounded text-sm hover:bg-slate-900 flex items-center gap-2 shadow-sm">
                        <Download size={16} /> Download PDF
                    </button>
                </div>
            </div>
        </div>
    );
};

// --- MOCK DATA REMOVED (Now using AppContext) ---

const mockFiscalYears: FiscalYear[] = [
   { id: 'fy23', name: 'Fiscal Year 2023', startDate: '2023-01-01', endDate: '2023-12-31', status: 'Closed', isCurrent: false },
   { id: 'fy24', name: 'Fiscal Year 2024', startDate: '2024-01-01', endDate: '2024-12-31', status: 'Open', isCurrent: true },
];

const mockAccounts: Account[] = [
  { id: '1000', code: '1000', name: 'Kasse / Cash', type: 'Asset', group: 'Liquid Assets', balance: 1250.50 },
  { id: '1020', code: '1020', name: 'Bank Account', type: 'Asset', group: 'Liquid Assets', balance: 45200.00 },
  { id: '1100', code: '1100', name: 'Debitors (FLL)', type: 'Asset', group: 'Receivables', balance: 5600.00 },
  { id: '2000', code: '2000', name: 'Creditors (VLL)', type: 'Liability', group: 'Short-term Liabilities', balance: 2300.00 },
  { id: '2030', code: '2030', name: 'Prepaid Vouchers', type: 'Liability', group: 'Short-term Liabilities', balance: 1200.00 },
  { id: '3000', code: '3000', name: 'Product Sales', type: 'Revenue', group: 'Sales', defaultTaxRateId: 'vat1', balance: 15000.00 },
  { id: '3400', code: '3400', name: 'Service Revenue', type: 'Revenue', group: 'Services', defaultTaxRateId: 'vat1', balance: 32000.00 },
  { id: '5000', code: '5000', name: 'Salaries', type: 'Expense', group: 'Personnel', balance: 20000.00 },
  { id: '6000', code: '6000', name: 'Rent', type: 'Expense', group: 'Space', defaultTaxRateId: 'vat3', balance: 4500.00 },
];

const mockVouchers: SoldVoucher[] = [
  { id: 'v-123', code: 'GIFT-AB92', purchaseDate: '2023-09-15', purchaser: 'Sarah Connor', originalAmount: 100, usedAmount: 25, remainingBalance: 75, status: 'Active' },
  { id: 'v-124', code: 'GIFT-XY88', purchaseDate: '2023-08-10', purchaser: 'Kyle Reese', originalAmount: 50, usedAmount: 50, remainingBalance: 0, status: 'Redeemed' },
];

const mockPayroll: PayrollRun[] = [
  { id: 'pay-1', employeeId: 'EMP-001', employeeName: 'Sarah Jenkins', period: 'October 2023', grossSalary: 5000, deductions: 850, netSalary: 4150, status: 'Paid' },
];

// --- SUB-COMPONENTS ---

const OverviewTab = () => {
  const { invoices } = useApp();
  const revenue = invoices.filter(i => i.category === 'Customer' && i.status === 'Paid').reduce((acc, curr) => acc + curr.amount, 0);
  const open = invoices.filter(i => i.category === 'Customer' && i.status !== 'Paid' && i.status !== 'Cancelled').reduce((acc, curr) => acc + curr.amount, 0);

  return (
  <div className="space-y-6 animate-fadeIn">
    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
       <div className="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
          <div className="flex justify-between items-start mb-4">
             <div className="p-2 bg-indigo-50 text-indigo-600 rounded-lg"><Wallet size={20} /></div>
             <span className="text-xs font-bold text-emerald-600 flex items-center gap-1"><TrendingUp size={12} /> +12%</span>
          </div>
          <div className="text-2xl font-bold text-slate-800">$46,450.50</div>
          <div className="text-xs text-slate-500">Total Cash & Bank</div>
       </div>
       <div className="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
          <div className="flex justify-between items-start mb-4">
             <div className="p-2 bg-emerald-50 text-emerald-600 rounded-lg"><PieChart size={20} /></div>
             <span className="text-xs font-bold text-emerald-600 flex items-center gap-1"><TrendingUp size={12} /> +5%</span>
          </div>
          <div className="text-2xl font-bold text-slate-800">${revenue.toFixed(2)}</div>
          <div className="text-xs text-slate-500">Revenue YTD</div>
       </div>
       <div className="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
          <div className="flex justify-between items-start mb-4">
             <div className="p-2 bg-rose-50 text-rose-600 rounded-lg"><AlertCircle size={20} /></div>
             <span className="text-xs font-bold text-slate-500">Open Invoices</span>
          </div>
          <div className="text-2xl font-bold text-slate-800">${open.toFixed(2)}</div>
          <div className="text-xs text-slate-500">Receivables</div>
       </div>
       <div className="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
          <div className="flex justify-between items-start mb-4">
             <div className="p-2 bg-amber-50 text-amber-600 rounded-lg"><Gift size={20} /></div>
          </div>
          <div className="text-2xl font-bold text-slate-800">$1,200.00</div>
          <div className="text-xs text-slate-500">Open Voucher Liabilities</div>
       </div>
    </div>
  </div>
)};

const InvoicingTab = () => {
   const { invoices, updateInvoice, deleteInvoice, invoiceTemplates, companySettings } = useApp();
   const [invoiceType, setInvoiceType] = useState<'Customer' | 'Supplier'>('Customer');
   const [viewMode, setViewMode] = useState<'list' | 'templates'>('list');
   const [qrPreviewInvoice, setQrPreviewInvoice] = useState<Invoice | null>(null);

   const handleSend = (inv: Invoice) => {
       updateInvoice({...inv, status: InvoiceStatus.SENT});
       alert(`Invoice ${inv.id} sent to ${inv.client}!`);
   };

   const handleDownload = (inv: Invoice) => {
       alert(`Downloading PDF for ${inv.id}...`);
   };

   const handleDelete = (id: string) => {
       if(confirm("Are you sure? This action cannot be undone.")) {
           deleteInvoice(id);
       }
   };

   if (viewMode === 'templates') {
       return <InvoiceTemplateManager onBack={() => setViewMode('list')} />;
   }

   return (
  <div className="flex-1 flex flex-col h-full animate-fadeIn">
     {qrPreviewInvoice && (
         <SwissQrBillPreview 
            invoice={qrPreviewInvoice} 
            company={companySettings} 
            onClose={() => setQrPreviewInvoice(null)} 
         />
     )}

     <div className="flex justify-between items-center mb-6">
        <div className="flex items-center gap-2">
            <div className="bg-slate-100 p-1 rounded-lg flex mr-4">
               <button onClick={() => setInvoiceType('Customer')} className={`px-3 py-1.5 text-sm font-medium rounded-md transition-all ${invoiceType === 'Customer' ? 'bg-white shadow-sm text-slate-800' : 'text-slate-500 hover:text-slate-700'}`}>Customer</button>
               <button onClick={() => setInvoiceType('Supplier')} className={`px-3 py-1.5 text-sm font-medium rounded-md transition-all ${invoiceType === 'Supplier' ? 'bg-white shadow-sm text-slate-800' : 'text-slate-500 hover:text-slate-700'}`}>Supplier (Bills)</button>
            </div>
            <div className="relative">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={16} />
              <input type="text" placeholder="Search..." className="pl-9 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500" />
            </div>
        </div>
        <div className="flex gap-2">
           <button 
             onClick={() => setViewMode('templates')}
             className="px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm font-medium hover:bg-slate-50 flex items-center gap-2"
           >
              <FileText size={16} /> Templates
           </button>
           <button className="bg-brand-600 text-white px-4 py-2 rounded-lg hover:bg-brand-700 flex items-center gap-2 text-sm font-medium shadow-sm">
              <PlusCircle size={16} /> {invoiceType === 'Customer' ? 'Create Invoice' : 'New Bill'}
           </button>
        </div>
     </div>

     <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex-1">
        <div className="overflow-x-auto">
          <table className="w-full text-left">
            <thead className="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase">
              <tr>
                <th className="p-4">Number / Type</th>
                <th className="p-4">{invoiceType === 'Customer' ? 'Client' : 'Supplier'}</th>
                <th className="p-4">Date / Due</th>
                <th className="p-4">Amount</th>
                <th className="p-4">Status</th>
                <th className="p-4">Dunning</th>
                <th className="p-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-200 text-sm">
              {invoices.filter(i => i.category === invoiceType).map((inv) => (
                <tr key={inv.id} className="hover:bg-slate-50 group">
                  <td className="p-4">
                    <div className="font-medium text-indigo-600">{inv.id}</div>
                    <div className="text-xs text-slate-500">{inv.type}</div>
                  </td>
                  <td className="p-4 font-medium text-slate-800">{inv.client}</td>
                  <td className="p-4 text-slate-600">
                    <div>{inv.date}</div>
                    <div className={`text-xs ${new Date(inv.dueDate) < new Date() && inv.status !== 'Paid' ? 'text-rose-500 font-bold' : 'text-slate-400'}`}>Due: {inv.dueDate}</div>
                  </td>
                  <td className="p-4 font-bold text-slate-800">
                     <span className={inv.amount < 0 ? 'text-slate-600' : ''}>
                        {inv.amount < 0 ? '' : ''}${Math.abs(inv.amount).toFixed(2)}
                     </span>
                  </td>
                  <td className="p-4">
                     <span className={`px-2 py-1 rounded-full text-xs font-bold ${
                        inv.status === InvoiceStatus.PAID ? 'bg-emerald-100 text-emerald-700' :
                        inv.status === InvoiceStatus.OVERDUE ? 'bg-rose-100 text-rose-700' :
                        inv.status === InvoiceStatus.SENT ? 'bg-blue-100 text-blue-700' :
                        'bg-slate-100 text-slate-700'
                     }`}>
                        {inv.status}
                     </span>
                  </td>
                  <td className="p-4">
                     {inv.dunningLevel > 0 ? (
                        <span className="flex items-center gap-1 text-xs font-bold text-amber-600">
                           <AlertCircle size={12} /> Level {inv.dunningLevel}
                        </span>
                     ) : (
                        <span className="text-xs text-slate-400">-</span>
                     )}
                  </td>
                  <td className="p-4 text-right">
                     <div className="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        {invoiceType === 'Customer' && (companySettings.qrIban || companySettings.iban) && (
                            <button onClick={() => setQrPreviewInvoice(inv)} className="p-1.5 text-rose-600 hover:bg-rose-50 rounded" title="Swiss QR">
                                <QrCode size={16} />
                            </button>
                        )}
                        {invoiceType === 'Customer' && inv.status === InvoiceStatus.DRAFT && (
                           <button onClick={() => handleSend(inv)} className="p-1.5 text-blue-600 hover:bg-blue-50 rounded" title="Send">
                              <Send size={16} />
                           </button>
                        )}
                        <button onClick={() => handleDownload(inv)} className="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-slate-100 rounded" title="Download">
                           <Download size={16} />
                        </button>
                        <button className="p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded" title="Edit">
                           <Edit2 size={16} />
                        </button>
                        <button onClick={() => handleDelete(inv.id)} className="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-slate-100 rounded" title="Delete">
                           <Trash2 size={16} />
                        </button>
                     </div>
                  </td>
                </tr>
              ))}
              {invoices.filter(i => i.category === invoiceType).length === 0 && (
                 <tr>
                    <td colSpan={7} className="p-8 text-center text-slate-400">
                       No {invoiceType.toLowerCase()} invoices found.
                    </td>
                 </tr>
              )}
            </tbody>
          </table>
        </div>
     </div>
  </div>
)};

// --- INVOICE TEMPLATE MANAGER & EDITOR ---

const InvoiceTemplateManager: React.FC<{ onBack: () => void }> = ({ onBack }) => {
    const { invoiceTemplates, deleteInvoiceTemplate, addInvoiceTemplate, updateInvoiceTemplate } = useApp();
    const [editingTemplate, setEditingTemplate] = useState<InvoiceTemplate | null>(null);

    const handleCreate = () => {
        const newTemplate: InvoiceTemplate = {
            id: `tpl_${Date.now()}`,
            name: 'New Template',
            isDefault: false,
            accentColor: '#0ea5e9',
            fontFamily: 'Inter',
            addressWindowPosition: 'Left',
            senderLine: 'Company Name • Street 1 • 1234 City',
            senderBlock: '',
            introText: 'Thank you for your order.',
            outroText: 'Please pay within 30 days.',
            footerColumn1: 'Bank Details',
            footerColumn2: 'Registry Info',
            footerColumn3: 'Contact'
        };
        addInvoiceTemplate(newTemplate);
        setEditingTemplate(newTemplate);
    };

    const handleSave = (tpl: InvoiceTemplate) => {
        updateInvoiceTemplate(tpl);
        setEditingTemplate(null);
    };

    if (editingTemplate) {
        return <InvoiceTemplateEditor template={editingTemplate} onSave={handleSave} onCancel={() => setEditingTemplate(null)} />;
    }

    return (
        <div className="h-full flex flex-col animate-fadeIn">
            <div className="flex justify-between items-center mb-6">
                <div className="flex items-center gap-4">
                    <button onClick={onBack} className="p-2 hover:bg-slate-100 rounded-full text-slate-500"><X size={20} /></button>
                    <h3 className="text-lg font-bold text-slate-800">Invoice Templates</h3>
                </div>
                <button onClick={handleCreate} className="bg-brand-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-brand-700 flex items-center gap-2">
                    <Plus size={16} /> Create Template
                </button>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {invoiceTemplates.map(tpl => (
                    <div key={tpl.id} className="border border-slate-200 rounded-xl p-5 bg-white hover:shadow-md transition-all group relative">
                        <div className="flex justify-between items-start mb-4">
                            <div className="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500">
                                <FileText size={20} />
                            </div>
                            {tpl.isDefault && <span className="bg-emerald-100 text-emerald-700 text-xs font-bold px-2 py-1 rounded-full">Default</span>}
                        </div>
                        <h4 className="font-bold text-slate-800 mb-1">{tpl.name}</h4>
                        <p className="text-xs text-slate-500 mb-4">Window: {tpl.addressWindowPosition}</p>
                        
                        <div className="flex gap-2 mt-4">
                            <button onClick={() => setEditingTemplate(tpl)} className="flex-1 py-2 border border-slate-200 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-50">Edit</button>
                            <button onClick={() => deleteInvoiceTemplate(tpl.id)} className="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg"><Trash2 size={18} /></button>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
};

const InvoiceTemplateEditor: React.FC<{ template: InvoiceTemplate; onSave: (t: InvoiceTemplate) => void; onCancel: () => void }> = ({ template, onSave, onCancel }) => {
    const [data, setData] = useState<InvoiceTemplate>(template);
    const [activeSection, setActiveSection] = useState<string | null>('general');

    // Section Component
    const Section = ({ id, title, icon: Icon, children }: any) => (
        <div className="border-b border-slate-200 last:border-0">
            <button 
                onClick={() => setActiveSection(activeSection === id ? null : id)}
                className="w-full flex items-center justify-between p-4 hover:bg-slate-50 transition-colors bg-white"
            >
                <div className="flex items-center gap-2 font-bold text-slate-700 text-sm">
                    <Icon size={16} className="text-brand-600" /> {title}
                </div>
                {activeSection === id ? <X size={16} className="text-slate-400 rotate-45" /> : <div className="text-slate-400">+</div>}
            </button>
            {activeSection === id && (
                <div className="p-4 bg-slate-50/50 space-y-4 border-t border-slate-100 animate-fadeIn">
                    {children}
                </div>
            )}
        </div>
    );

    const Input = ({ label, value, onChange, type = 'text' }: any) => (
        <div>
            <label className="block text-xs font-bold text-slate-500 mb-1 uppercase">{label}</label>
            <input 
                type={type} 
                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 outline-none bg-white"
                value={value}
                onChange={e => onChange(e.target.value)}
            />
        </div>
    );

    const TextArea = ({ label, value, onChange, rows = 3 }: any) => (
        <div>
            <label className="block text-xs font-bold text-slate-500 mb-1 uppercase">{label}</label>
            <textarea 
                rows={rows}
                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 outline-none bg-white resize-y"
                value={value}
                onChange={e => onChange(e.target.value)}
            />
        </div>
    );

    return (
        <div className="h-full flex flex-col bg-slate-100 -m-6 md:-m-8"> {/* Negative margin to fill parent */}
            {/* Header */}
            <div className="bg-white border-b border-slate-200 px-6 py-3 flex justify-between items-center shadow-sm z-10">
                <div className="flex items-center gap-4">
                    <button onClick={onCancel} className="text-slate-500 hover:text-slate-700 flex items-center gap-1 text-sm font-medium">
                        <ArrowRight size={16} className="rotate-180" /> Back
                    </button>
                    <div className="h-6 w-px bg-slate-200"></div>
                    <span className="font-bold text-slate-800">Template Editor: {data.name}</span>
                </div>
                <button onClick={() => onSave(data)} className="bg-brand-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-brand-700 shadow-sm flex items-center gap-2">
                    <Save size={16} /> Save Template
                </button>
            </div>

            <div className="flex-1 flex overflow-hidden">
                {/* Left Sidebar: Controls */}
                <div className="w-96 bg-white border-r border-slate-200 overflow-y-auto flex-shrink-0 shadow-xl z-10">
                    <Section id="general" title="General Settings" icon={Settings}>
                        <Input label="Template Name" value={data.name} onChange={(v: string) => setData({...data, name: v})} />
                        <div className="flex items-center gap-2 mt-2">
                            <input type="checkbox" checked={data.isDefault} onChange={e => setData({...data, isDefault: e.target.checked})} className="rounded text-brand-600 focus:ring-brand-500" />
                            <span className="text-sm text-slate-700">Set as Default Template</span>
                        </div>
                    </Section>

                    <Section id="design" title="Design & Branding" icon={Palette}>
                        <Input label="Accent Color" type="color" value={data.accentColor} onChange={(v: string) => setData({...data, accentColor: v})} />
                        <div>
                            <label className="block text-xs font-bold text-slate-500 mb-1 uppercase">Font Family</label>
                            <select className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-white" value={data.fontFamily} onChange={e => setData({...data, fontFamily: e.target.value})}>
                                <option value="Inter">Inter (Modern)</option>
                                <option value="Times New Roman">Times New Roman (Classic)</option>
                                <option value="Courier New">Courier (Mono)</option>
                            </select>
                        </div>
                        <div className="p-4 border border-dashed border-slate-300 rounded-lg text-center bg-slate-50">
                            <ImageIcon size={24} className="mx-auto text-slate-400 mb-2" />
                            <span className="text-xs text-slate-500 block">Logo Upload Placeholder</span>
                            {data.logoUrl && <div className="text-xs text-emerald-600 mt-1">Logo set</div>}
                        </div>
                    </Section>

                    <Section id="header" title="Header & Address" icon={MapPin}>
                        <div>
                            <label className="block text-xs font-bold text-slate-500 mb-1 uppercase">Window Position</label>
                            <div className="flex bg-slate-100 p-1 rounded-lg">
                                <button onClick={() => setData({...data, addressWindowPosition: 'Left'})} className={`flex-1 py-1 text-xs font-medium rounded ${data.addressWindowPosition === 'Left' ? 'bg-white shadow text-slate-800' : 'text-slate-500'}`}>Left</button>
                                <button onClick={() => setData({...data, addressWindowPosition: 'Right'})} className={`flex-1 py-1 text-xs font-medium rounded ${data.addressWindowPosition === 'Right' ? 'bg-white shadow text-slate-800' : 'text-slate-500'}`}>Right</button>
                            </div>
                        </div>
                        <Input label="Sender Line (Small)" value={data.senderLine} onChange={(v: string) => setData({...data, senderLine: v})} />
                        <TextArea label="Sender Block (Header)" value={data.senderBlock} onChange={(v: string) => setData({...data, senderBlock: v})} rows={4} />
                    </Section>

                    <Section id="content" title="Document Content" icon={Type}>
                        <TextArea label="Intro Text" value={data.introText} onChange={(v: string) => setData({...data, introText: v})} />
                        <TextArea label="Closing / Outro" value={data.outroText} onChange={(v: string) => setData({...data, outroText: v})} />
                    </Section>

                    <Section id="footer" title="Footer Columns" icon={Layout}>
                        <TextArea label="Column 1 (Bank)" value={data.footerColumn1} onChange={(v: string) => setData({...data, footerColumn1: v})} />
                        <TextArea label="Column 2 (Legal)" value={data.footerColumn2} onChange={(v: string) => setData({...data, footerColumn2: v})} />
                        <TextArea label="Column 3 (Contact)" value={data.footerColumn3} onChange={(v: string) => setData({...data, footerColumn3: v})} />
                    </Section>
                </div>

                {/* Right Side: Live Preview */}
                <div className="flex-1 bg-slate-100 p-8 overflow-y-auto flex justify-center">
                    {/* A4 Sheet Ratio: 210mm x 297mm ~ 1:1.414 */}
                    <div 
                        className="bg-white shadow-2xl transition-all duration-300 relative flex flex-col"
                        style={{ 
                            width: '210mm', 
                            minHeight: '297mm', 
                            height: 'fit-content',
                            fontFamily: data.fontFamily,
                            color: '#1e293b'
                        }}
                    >
                        {/* PAGE CONTENT */}
                        <div className="p-[20mm] flex-1 flex flex-col relative">
                            
                            {/* Header: Logo & Sender Block */}
                            <div className="flex justify-between items-start mb-12 h-24">
                                <div className="w-40 h-16 bg-slate-100 flex items-center justify-center text-slate-400 text-xs border border-dashed border-slate-300">
                                    {data.logoUrl ? <img src={data.logoUrl} alt="Logo" /> : 'LOGO'}
                                </div>
                                <div className="text-right text-xs leading-relaxed whitespace-pre-wrap text-slate-500">
                                    {data.senderBlock || 'Your Company\nAddress Line 1\nCity, Country'}
                                </div>
                            </div>

                            {/* Address Window Area (45mm x 100mm approx) */}
                            <div className={`mb-16 flex ${data.addressWindowPosition === 'Right' ? 'justify-end' : 'justify-start'}`}>
                                <div className="w-[85mm] h-[40mm] rounded p-2">
                                    <p className="text-[8px] underline mb-2 text-slate-400">{data.senderLine}</p>
                                    <div className="text-sm leading-snug">
                                        Max Mustermann<br/>
                                        Musterstrasse 12<br/>
                                        1234 Musterstadt
                                    </div>
                                </div>
                            </div>

                            {/* Meta Data Line */}
                            <div className="flex justify-between items-end border-b-2 pb-4 mb-8" style={{ borderColor: data.accentColor }}>
                                <div>
                                    <h1 className="text-2xl font-bold" style={{ color: data.accentColor }}>INVOICE</h1>
                                    <p className="text-sm font-bold text-slate-400">#INV-2023-001</p>
                                </div>
                                <div className="text-right text-sm">
                                    <div className="flex gap-8">
                                        <div>
                                            <span className="block text-xs text-slate-400">Date</span>
                                            <span>24.10.2023</span>
                                        </div>
                                        <div>
                                            <span className="block text-xs text-slate-400">Due Date</span>
                                            <span>23.11.2023</span>
                                        </div>
                                        <div>
                                            <span className="block text-xs text-slate-400">Customer ID</span>
                                            <span>C-9912</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Intro */}
                            <div className="mb-8 text-sm whitespace-pre-wrap">
                                {data.introText}
                            </div>

                            {/* Table */}
                            <table className="w-full text-sm mb-8">
                                <thead className="border-b border-slate-200">
                                    <tr>
                                        <th className="py-2 text-left font-bold w-12">Pos</th>
                                        <th className="py-2 text-left font-bold">Description</th>
                                        <th className="py-2 text-right font-bold w-20">Qty</th>
                                        <th className="py-2 text-right font-bold w-24">Price</th>
                                        <th className="py-2 text-right font-bold w-24">Total</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    <tr>
                                        <td className="py-3">1</td>
                                        <td className="py-3">Web Design Services</td>
                                        <td className="py-3 text-right">10</td>
                                        <td className="py-3 text-right">120.00</td>
                                        <td className="py-3 text-right">1'200.00</td>
                                    </tr>
                                    <tr>
                                        <td className="py-3">2</td>
                                        <td className="py-3">Hosting (Annual)</td>
                                        <td className="py-3 text-right">1</td>
                                        <td className="py-3 text-right">240.00</td>
                                        <td className="py-3 text-right">240.00</td>
                                    </tr>
                                </tbody>
                                <tfoot className="border-t border-slate-300 font-bold">
                                    <tr>
                                        <td colSpan={4} className="py-3 text-right">Subtotal</td>
                                        <td className="py-3 text-right">1'440.00</td>
                                    </tr>
                                    <tr>
                                        <td colSpan={4} className="py-2 text-right text-xs text-slate-500 font-normal">VAT (8.1%)</td>
                                        <td className="py-2 text-right text-xs text-slate-500 font-normal">116.65</td>
                                    </tr>
                                    <tr className="text-lg" style={{ color: data.accentColor }}>
                                        <td colSpan={4} className="py-4 text-right">Total CHF</td>
                                        <td className="py-4 text-right">1'556.65</td>
                                    </tr>
                                </tfoot>
                            </table>

                            {/* Outro */}
                            <div className="mb-12 text-sm whitespace-pre-wrap">
                                {data.outroText}
                            </div>

                            {/* Footer - Pushed to bottom */}
                            <div className="mt-auto pt-8 border-t border-slate-200 grid grid-cols-3 gap-8 text-[9px] text-slate-500 leading-relaxed">
                                <div className="whitespace-pre-wrap">{data.footerColumn1}</div>
                                <div className="whitespace-pre-wrap text-center">{data.footerColumn2}</div>
                                <div className="whitespace-pre-wrap text-right">{data.footerColumn3}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

const AccountingTab = () => {
  const { vatRates, setVatRates } = useApp();
  const [subTab, setSubTab] = useState<'journal' | 'coa' | 'reports' | 'vat' | 'fiscal'>('coa');
  const [isVatModalOpen, setIsVatModalOpen] = useState(false);
  const [editingVat, setEditingVat] = useState<VatRate | null>(null);

  // Vat Handler
  const handleSaveVat = (rate: VatRate) => {
     if (editingVat) {
        setVatRates(vatRates.map(v => v.id === rate.id ? rate : v));
     } else {
        setVatRates([...vatRates, { ...rate, id: Math.random().toString(36).substr(2,9) }]);
     }
     setIsVatModalOpen(false);
     setEditingVat(null);
  };

  const handleDeleteVat = (id: string) => {
      if(window.confirm("Delete this VAT rate?")) {
          setVatRates(vatRates.filter(v => v.id !== id));
      }
  };

  const handleEditVat = (rate: VatRate) => {
      setEditingVat(rate);
      setIsVatModalOpen(true);
  };

  const handleAddVat = () => {
      setEditingVat(null);
      setIsVatModalOpen(true);
  };
  
  return (
     <div className="flex-1 flex flex-col h-full animate-fadeIn">
        <div className="flex gap-4 mb-6 border-b border-slate-200 overflow-x-auto pb-2">
           {[
             {id: 'coa', label: 'Chart of Accounts'},
             {id: 'journal', label: 'Journal'},
             {id: 'vat', label: 'VAT Rates'},
             {id: 'reports', label: 'Reports'},
             {id: 'fiscal', label: 'Fiscal Years'}
           ].map(t => (
              <button 
                key={t.id}
                onClick={() => setSubTab(t.id as any)}
                className={`pb-2 text-sm font-medium border-b-2 transition-colors whitespace-nowrap ${subTab === t.id ? 'border-brand-600 text-brand-600' : 'border-transparent text-slate-500 hover:text-slate-700'}`}
              >
                 {t.label}
              </button>
           ))}
        </div>

        {/* CHART OF ACCOUNTS */}
        {subTab === 'coa' && (
           <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex-1 overflow-y-auto">
              <table className="w-full text-left">
                 <thead className="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase">
                    <tr>
                       <th className="p-3">Code</th>
                       <th className="p-3">Account Name</th>
                       <th className="p-3">Group</th>
                       <th className="p-3">Type</th>
                       <th className="p-3">Default VAT</th>
                       <th className="p-3 text-right">Balance (CHF)</th>
                    </tr>
                 </thead>
                 <tbody className="divide-y divide-slate-200 text-sm">
                    {mockAccounts.sort((a,b) => a.code.localeCompare(b.code)).map(acc => (
                       <tr key={acc.id} className="hover:bg-slate-50">
                          <td className="p-3 font-mono text-slate-600">{acc.code}</td>
                          <td className="p-3 font-medium text-slate-800">{acc.name}</td>
                          <td className="p-3 text-slate-500">{acc.group}</td>
                          <td className="p-3">
                             <span className={`px-2 py-0.5 rounded text-xs ${
                                acc.type === 'Asset' ? 'bg-blue-50 text-blue-700' :
                                acc.type === 'Liability' ? 'bg-amber-50 text-amber-700' :
                                acc.type === 'Revenue' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'
                             }`}>{acc.type}</span>
                          </td>
                          <td className="p-3 text-xs text-slate-500">
                             {acc.defaultTaxRateId ? vatRates.find(v => v.id === acc.defaultTaxRateId)?.code : '-'}
                          </td>
                          <td className="p-3 text-right font-medium">{acc.balance.toFixed(2)}</td>
                       </tr>
                    ))}
                 </tbody>
              </table>
           </div>
        )}

        {/* VAT RATES */}
        {subTab === 'vat' && (
           <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-6 overflow-y-auto">
              <div className="flex justify-between items-center mb-4">
                 <h3 className="font-bold text-slate-800">Defined Tax Rates</h3>
                 <button onClick={handleAddVat} className="px-3 py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700 transition-colors shadow-sm">
                    + Add Rate
                 </button>
              </div>
              <table className="w-full text-left">
                 <thead className="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase">
                    <tr>
                       <th className="p-3">Code</th>
                       <th className="p-3">Description</th>
                       <th className="p-3">Rate %</th>
                       <th className="p-3">Type</th>
                       <th className="p-3">Ziffer</th>
                       <th className="p-3">Konto</th>
                       <th className="p-3">Valid From</th>
                       <th className="p-3">Valid To</th>
                       <th className="p-3">Status</th>
                       <th className="p-3 text-right">Actions</th>
                    </tr>
                 </thead>
                 <tbody className="divide-y divide-slate-200 text-sm">
                    {vatRates.map(vat => (
                       <tr key={vat.id} className="hover:bg-slate-50">
                          <td className="p-3 font-bold text-slate-700">{vat.code}</td>
                          <td className="p-3">{vat.description}</td>
                          <td className="p-3 font-bold">{vat.rate}%</td>
                          <td className="p-3 text-xs text-slate-500 max-w-[150px] truncate" title={vat.type}>{vat.type}</td>
                          <td className="p-3 font-mono text-xs"><span className="bg-slate-100 rounded px-1">{vat.formCode}</span></td>
                          <td className="p-3 font-mono text-slate-600">{vat.linkedAccountId}</td>
                          <td className="p-3 text-slate-500 text-xs">{vat.validFrom}</td>
                          <td className="p-3 text-slate-500 text-xs">{vat.validTo || '-'}</td>
                          <td className="p-3">
                             {vat.active ? <span className="text-emerald-600 text-xs font-bold bg-emerald-50 px-2 py-0.5 rounded-full">Active</span> : <span className="text-slate-400 text-xs bg-slate-100 px-2 py-0.5 rounded-full">Inactive</span>}
                          </td>
                          <td className="p-3 text-right">
                              <div className="flex justify-end gap-2">
                                <button onClick={() => handleEditVat(vat)} className="p-1.5 text-slate-400 hover:text-brand-600 hover:bg-slate-100 rounded"><Edit2 size={14}/></button>
                                <button onClick={() => handleDeleteVat(vat.id)} className="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-slate-100 rounded"><Trash2 size={14}/></button>
                              </div>
                          </td>
                       </tr>
                    ))}
                 </tbody>
              </table>
           </div>
        )}

        {/* FISCAL YEAR */}
        {subTab === 'fiscal' && (
            <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-6 max-w-3xl">
                <h3 className="font-bold text-slate-800 mb-4">Fiscal Years Management</h3>
                <div className="space-y-4">
                    {mockFiscalYears.map(fy => (
                        <div key={fy.id} className="p-4 border border-slate-200 rounded-lg flex justify-between items-center">
                            <div>
                                <div className="flex items-center gap-2">
                                   <h4 className="font-bold text-slate-800">{fy.name}</h4>
                                   {fy.isCurrent && <span className="px-2 py-0.5 bg-indigo-100 text-indigo-700 text-xs font-bold rounded-full">Current</span>}
                                </div>
                                <div className="text-sm text-slate-500 mt-1">
                                    {fy.startDate} to {fy.endDate}
                                </div>
                            </div>
                            <div className="flex items-center gap-3">
                                <span className={`text-sm font-medium ${fy.status === 'Open' ? 'text-emerald-600' : 'text-slate-400'}`}>
                                    {fy.status}
                                </span>
                                <button className="p-2 text-slate-400 hover:text-slate-600"><Settings size={16}/></button>
                            </div>
                        </div>
                    ))}
                    <button className="w-full py-3 border-2 border-dashed border-slate-200 rounded-lg text-slate-500 hover:border-brand-300 hover:text-brand-600 font-medium">
                        + Open New Fiscal Year
                    </button>
                </div>
            </div>
        )}

        {subTab === 'reports' && (
           <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                 <h3 className="font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <Landmark size={18} /> Balance Sheet
                 </h3>
                 <div className="space-y-4">
                    <div>
                       <h4 className="text-xs font-bold text-slate-500 uppercase border-b pb-1 mb-2">Assets</h4>
                       <div className="flex justify-between text-sm mb-1"><span>Current Assets</span> <span>46,450.50</span></div>
                       <div className="flex justify-between text-sm mb-1"><span>Fixed Assets</span> <span>120,000.00</span></div>
                       <div className="flex justify-between font-bold text-sm pt-2 border-t"><span>Total Assets</span> <span>166,450.50</span></div>
                    </div>
                    <div>
                       <h4 className="text-xs font-bold text-slate-500 uppercase border-b pb-1 mb-2">Liabilities & Equity</h4>
                       <div className="flex justify-between text-sm mb-1"><span>Current Liabilities</span> <span>3,500.00</span></div>
                       <div className="flex justify-between text-sm mb-1"><span>Equity</span> <span>162,950.50</span></div>
                       <div className="flex justify-between font-bold text-sm pt-2 border-t"><span>Total L&E</span> <span>166,450.50</span></div>
                    </div>
                 </div>
              </div>

              <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                 <h3 className="font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <TrendingUp size={18} /> Income Statement (P&L)
                 </h3>
                 <div className="space-y-2">
                    <div className="flex justify-between text-sm"><span>Revenue</span> <span className="font-medium">47,000.00</span></div>
                    <div className="flex justify-between text-sm text-rose-600"><span>Expenses</span> <span>-24,500.00</span></div>
                    <div className="flex justify-between font-bold text-lg pt-4 border-t border-slate-200 mt-4">
                       <span>Net Profit</span> 
                       <span className="text-emerald-600">22,500.00</span>
                    </div>
                 </div>
              </div>
           </div>
        )}

        {subTab === 'journal' && (
           <div className="flex-1 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-center text-slate-400">
              Journal Entry UI Placeholder
           </div>
        )}

        {/* VAT MODAL */}
        {isVatModalOpen && (
            <VatRateModal 
                rate={editingVat} 
                onClose={() => setIsVatModalOpen(false)} 
                onSave={handleSaveVat} 
            />
        )}
     </div>
  );
};

const VatRateModal: React.FC<{ rate: VatRate | null; onClose: () => void; onSave: (r: VatRate) => void }> = ({ rate, onClose, onSave }) => {
    const [formData, setFormData] = useState<VatRate>(rate || {
        id: '', code: '', description: '', rate: 8.1, type: 'Geschuldete MWST (Umsatzsteuer)', formCode: '300', active: true, validFrom: new Date().toISOString().split('T')[0], validTo: '', linkedAccountId: ''
    });

    const vatTypes: VatTaxType[] = [
      'Bezugsteuer MWST Investitionen, uebriger Betriebsaufwand',
      'Bezugsteuer MWST Material, Waren, Dienstleistungen, Energie',
      'Vorsteuer MWST Investitionen, uebriger Betriebsaufwand',
      'Vorsteuer MWST Material, Waren, Dienstleistungen, Energie',
      'Zollsteuer MWST Investitionen, uebriger Betriebsaufwand',
      'Zollsteuer MWST Material, Waren, Dienstleistungen, Energie',
      'Geschuldete MWST (Umsatzsteuer)',
      'Optierte geschuldete MWST (Umsatzsteuer)',
      'Nichtentgelt',
      'Nicht steuerpflichtiger Umsatz',
      'Saldosteuersatz',
      'Optierter Saldosteuersatz'
    ];

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
            <div className="bg-white rounded-xl shadow-2xl w-full max-w-lg animate-fadeIn">
                <div className="p-5 border-b border-slate-200 flex justify-between items-center">
                    <h3 className="font-bold text-slate-800">{rate ? 'Edit VAT Rate' : 'Add VAT Rate'}</h3>
                    <button onClick={onClose} className="text-slate-400 hover:text-slate-600"><X size={20}/></button>
                </div>
                <div className="p-6 space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">Code</label>
                            <input className="w-full border border-slate-300 rounded-lg px-3 py-2" value={formData.code} onChange={e => setFormData({...formData, code: e.target.value})} placeholder="e.g. MWST81" />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">Rate (%)</label>
                            <input type="number" step="0.1" className="w-full border border-slate-300 rounded-lg px-3 py-2" value={formData.rate} onChange={e => setFormData({...formData, rate: parseFloat(e.target.value)})} />
                        </div>
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-slate-700 mb-1">Description</label>
                        <input className="w-full border border-slate-300 rounded-lg px-3 py-2" value={formData.description} onChange={e => setFormData({...formData, description: e.target.value})} placeholder="Standard Rate" />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-slate-700 mb-1">Tax Type (Swiss)</label>
                        <select className="w-full border border-slate-300 rounded-lg px-3 py-2 bg-white text-sm" value={formData.type} onChange={e => setFormData({...formData, type: e.target.value as VatTaxType})}>
                            {vatTypes.map(t => (
                                <option key={t} value={t}>{t}</option>
                            ))}
                        </select>
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">Form Code (Ziffer)</label>
                            <input className="w-full border border-slate-300 rounded-lg px-3 py-2" value={formData.formCode || ''} onChange={e => setFormData({...formData, formCode: e.target.value})} placeholder="e.g. 302" />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">Account / Konto</label>
                            <input className="w-full border border-slate-300 rounded-lg px-3 py-2" value={formData.linkedAccountId || ''} onChange={e => setFormData({...formData, linkedAccountId: e.target.value})} placeholder="e.g. 2200" />
                        </div>
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">Valid From</label>
                            <input type="date" className="w-full border border-slate-300 rounded-lg px-3 py-2" value={formData.validFrom} onChange={e => setFormData({...formData, validFrom: e.target.value})} />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">Valid To</label>
                            <input type="date" className="w-full border border-slate-300 rounded-lg px-3 py-2" value={formData.validTo || ''} onChange={e => setFormData({...formData, validTo: e.target.value})} />
                        </div>
                    </div>
                    <div className="pt-2">
                        <label className="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" checked={formData.active} onChange={e => setFormData({...formData, active: e.target.checked})} className="w-4 h-4 text-brand-600 rounded" />
                            <span className="text-sm font-medium text-slate-700">Rate is Active</span>
                        </label>
                    </div>
                </div>
                <div className="p-5 border-t border-slate-200 flex justify-end gap-2 bg-slate-50 rounded-b-xl">
                    <button onClick={onClose} className="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-white font-medium text-sm">Cancel</button>
                    <button onClick={() => onSave(formData)} className="px-4 py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700 shadow-sm">Save Rate</button>
                </div>
            </div>
        </div>
    );
};

const PayrollTab = () => (
   <div className="flex-1 flex flex-col h-full animate-fadeIn">
      <div className="flex justify-between items-center mb-6">
         <h3 className="font-bold text-lg text-slate-800">Payroll Run: October 2023</h3>
         <button className="bg-brand-600 text-white px-4 py-2 rounded-lg hover:bg-brand-700 text-sm font-medium shadow-sm">
            Run Payroll
         </button>
      </div>
      <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
         <table className="w-full text-left">
            <thead className="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase">
               <tr>
                  <th className="p-4">Employee</th>
                  <th className="p-4">Period</th>
                  <th className="p-4 text-right">Gross Salary</th>
                  <th className="p-4 text-right">Deductions</th>
                  <th className="p-4 text-right">Net Pay</th>
                  <th className="p-4 text-center">Status</th>
                  <th className="p-4 text-right">Slip</th>
               </tr>
            </thead>
            <tbody className="divide-y divide-slate-200 text-sm">
               {mockPayroll.map(pay => (
                  <tr key={pay.id} className="hover:bg-slate-50">
                     <td className="p-4 font-medium text-slate-800">{pay.employeeName}</td>
                     <td className="p-4 text-slate-600">{pay.period}</td>
                     <td className="p-4 text-right">{pay.grossSalary.toFixed(2)}</td>
                     <td className="p-4 text-right text-rose-600">-{pay.deductions.toFixed(2)}</td>
                     <td className="p-4 text-right font-bold text-emerald-600">{pay.netSalary.toFixed(2)}</td>
                     <td className="p-4 text-center">
                        <span className={`px-2 py-1 rounded-full text-xs font-bold ${pay.status === 'Paid' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'}`}>
                           {pay.status}
                        </span>
                     </td>
                     <td className="p-4 text-right">
                        <button className="text-indigo-600 hover:underline">PDF</button>
                     </td>
                  </tr>
               ))}
            </tbody>
         </table>
      </div>
   </div>
);

const VouchersTab = () => (
   <div className="flex-1 flex flex-col h-full animate-fadeIn">
      <div className="grid grid-cols-3 gap-4 mb-6">
         <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <div className="text-xs text-slate-500 font-bold uppercase mb-1">Outstanding Liability</div>
            <div className="text-2xl font-bold text-slate-800">$1,200.00</div>
         </div>
         <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <div className="text-xs text-slate-500 font-bold uppercase mb-1">Redeemed YTD</div>
            <div className="text-2xl font-bold text-emerald-600">$4,500.00</div>
         </div>
         <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <div className="text-xs text-slate-500 font-bold uppercase mb-1">Expired (Profit)</div>
            <div className="text-2xl font-bold text-indigo-600">$250.00</div>
         </div>
      </div>

      <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex-1">
         <div className="p-4 border-b border-slate-200 flex justify-between items-center">
            <h3 className="font-bold text-slate-800">Sold Gift Cards (Ledger)</h3>
            <div className="relative">
               <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={14} />
               <input type="text" placeholder="Search code..." className="pl-8 pr-4 py-1.5 border border-slate-200 rounded-lg text-sm" />
            </div>
         </div>
         <table className="w-full text-left">
            <thead className="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase">
               <tr>
                  <th className="p-4">Code</th>
                  <th className="p-4">Purchaser / Date</th>
                  <th className="p-4 text-right">Original Amount</th>
                  <th className="p-4 text-right">Used</th>
                  <th className="p-4 text-right">Balance</th>
                  <th className="p-4 text-center">Status</th>
               </tr>
            </thead>
            <tbody className="divide-y divide-slate-200 text-sm">
               {mockVouchers.map(v => (
                  <tr key={v.id} className="hover:bg-slate-50">
                     <td className="p-4 font-mono font-medium text-indigo-600">{v.code}</td>
                     <td className="p-4">
                        <div className="text-slate-800">{v.purchaser}</div>
                        <div className="text-xs text-slate-500">{v.purchaseDate}</div>
                     </td>
                     <td className="p-4 text-right font-medium">${v.originalAmount.toFixed(2)}</td>
                     <td className="p-4 text-right text-slate-600">${v.usedAmount.toFixed(2)}</td>
                     <td className="p-4 text-right font-bold">${v.remainingBalance.toFixed(2)}</td>
                     <td className="p-4 text-center">
                        <span className={`px-2 py-0.5 rounded-full text-xs font-bold ${
                           v.status === 'Active' ? 'bg-emerald-100 text-emerald-700' :
                           v.status === 'Redeemed' ? 'bg-slate-100 text-slate-500' : 'bg-rose-100 text-rose-700'
                        }`}>
                           {v.status}
                        </span>
                     </td>
                  </tr>
               ))}
            </tbody>
         </table>
      </div>
   </div>
);

// --- MAIN COMPONENT ---

const FinanceModule: React.FC = () => {
  const [activeTab, setActiveTab] = useState<'overview' | 'invoicing' | 'accounting' | 'payroll' | 'commissions' | 'vouchers'>('overview');

  return (
    <div className="flex flex-col md:flex-row h-[calc(100vh-140px)] gap-6">
      {/* Navigation Sidebar */}
      <div className="w-full md:w-64 lg:w-72 flex-shrink-0">
         <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div className="p-4 border-b border-slate-100 bg-slate-50">
               <h2 className="text-base md:text-lg font-bold text-slate-800">Finance</h2>
               <p className="text-xs text-slate-500">Accounting & Billing</p>
            </div>
            <nav className="p-2 space-y-1">
               <FinanceNavBtn active={activeTab === 'overview'} onClick={() => setActiveTab('overview')} icon={PieChart} label="Overview" />
               <FinanceNavBtn active={activeTab === 'invoicing'} onClick={() => setActiveTab('invoicing')} icon={FileText} label="Invoices & Bills" />
               <FinanceNavBtn active={activeTab === 'accounting'} onClick={() => setActiveTab('accounting')} icon={Landmark} label="Accounting (FIBU)" />
               <FinanceNavBtn active={activeTab === 'payroll'} onClick={() => setActiveTab('payroll')} icon={Users} label="Payroll" />
               <FinanceNavBtn active={activeTab === 'vouchers'} onClick={() => setActiveTab('vouchers')} icon={Gift} label="Voucher Ledger" />
               <FinanceNavBtn active={activeTab === 'commissions'} onClick={() => setActiveTab('commissions')} icon={TrendingUp} label="Partner Commissions" />
            </nav>
         </div>
      </div>

      {/* Main Content */}
      <div className="flex-1 min-w-0">
         {activeTab === 'overview' && <OverviewTab />}
         {activeTab === 'invoicing' && <InvoicingTab />}
         {activeTab === 'accounting' && <AccountingTab />}
         {activeTab === 'payroll' && <PayrollTab />}
         {activeTab === 'vouchers' && <VouchersTab />}
         {activeTab === 'commissions' && (
            <div className="flex items-center justify-center h-full bg-white rounded-xl border border-slate-200 text-slate-400">
               Partner Commissions Module Placeholder
            </div>
         )}
      </div>
    </div>
  );
};

const FinanceNavBtn: React.FC<{ active: boolean; onClick: () => void; icon: any; label: string }> = ({ active, onClick, icon: Icon, label }) => (
   <button
      onClick={onClick}
      className={`w-full text-left px-4 py-2.5 rounded-md text-sm font-medium flex items-center gap-3 transition-all ${
         active ? 'bg-brand-50 text-brand-700 shadow-sm ring-1 ring-brand-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
      }`}
   >
      <Icon size={18} /> {label}
   </button>
);

export default FinanceModule;
