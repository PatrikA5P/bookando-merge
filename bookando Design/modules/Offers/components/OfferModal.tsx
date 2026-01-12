import React, { useState, useRef } from 'react';
import {
    X, Upload, Bold, Italic, List, Save, ArrowRight, CheckCircle, AlertCircle,
    Plus, Trash2, Clock, Calendar, Users, MapPin, Settings, Shield, FileText,
    Box, Bell, DollarSign, Percent, CreditCard, User, Gift, ChevronDown, ChevronUp,
    Edit2, Check, Image as ImageIcon, Video, Globe, Eye, Package
} from 'lucide-react';
import { useApp } from '../../../context/AppContext';
import { 
    ServiceItem, BundleItem, VoucherItem, EventSession, PaymentOption, 
    EventStructure, VoucherType 
} from '../../../types';
import { ModalTab } from '../types';

interface OfferModalProps {
    mode: 'create' | 'edit';
    type: 'service' | 'bundle' | 'voucher';
    initialData: any;
    initialTab?: ModalTab;
    availableServices: ServiceItem[];
    onClose: () => void;
    onSave: (data: any) => void;
}


const OfferModal: React.FC<OfferModalProps> = ({ mode, type, initialData, initialTab, availableServices, onClose, onSave }) => {
    const [step, setStep] = useState<'type-select' | 'form'>(mode === 'create' && type === 'service' && !initialData ? 'type-select' : 'form');
    const [activeTab, setActiveTab] = useState<ModalTab>(initialTab || 'general');
    const fileInputRef = useRef<HTMLInputElement>(null);
    const { badges, lessons, offerTags, offerExtras, pricingRules, vatRates, setVatRates, bookingFormTemplates, offerCategories, systemCurrency, locations, rooms, equipment } = useApp();
    
    // Form Data State
    const emptyService: ServiceItem = {
        id: '', title: '', description: '', category: 'Wellness', categories: [], tags: [], price: 0, image: '', type: 'Service', active: true, duration: 60,
        bufferBefore: 0, bufferAfter: 0, capacity: 1, salePrice: 0, dynamicPricing: 'Off',
        requiredLocations: [], requiredRooms: [], requiredEquipment: [],
        isRecurring: false, defaultStatus: 'Confirmed',
        minNotice: [
            { type: 'booking', value: 24, unit: 'hours' },
            { type: 'cancel', value: 48, unit: 'hours' },
            { type: 'reschedule', value: 24, unit: 'hours' }
        ],
        noticeChange: { value: 24, unit: 'hours' }, customerLimits: {count: 0, period: 'month'},
        waitlistEnabled: false, waitlistCapacity: 5,
        paymentOptions: ['Credit Card', 'On Site'],
        integration: 'None',
        currency: systemCurrency, productCode: '', externalProductCode: '',
        vatEnabled: true, vatRateSales: 8.1, vatRatePurchase: 0,
        // Event Specific Defaults
        eventStructure: 'Single',
        sessions: [],
        allowGroupBooking: false,
        maxGroupSize: 1,
        minParticipants: 1,
        allowMultipleBookings: false,
        sharedCapacity: true,
        allowedExtras: [],
        customerSelectableResources: []
    };
    const emptyBundle: BundleItem = { id: '', title: '', items: [], price: 0, originalPrice: 0, savings: 0, image: '', active: true };
    const emptyVoucher: VoucherItem = { 
        id: '', title: '', category: 'Promotion', code: '', discountType: 'Percentage', discountValue: 10, 
        uses: 0, maxUses: 100, maxUsesPerCustomer: 1, expiry: '', status: 'Active',
        allowCustomAmount: false, minCustomAmount: 10, maxCustomAmount: 500, fixedValue: 50
    };

    const [formData, setFormData] = useState<any>(initialData || (type === 'service' ? emptyService : type === 'bundle' ? emptyBundle : emptyVoucher));
    const [expandedSessionId, setExpandedSessionId] = useState<string | null>(null);

    // Live Price Simulation for Dynamic Pricing
    const [simulatedPrice, setSimulatedPrice] = useState<number | null>(null);

    // Helper for standardized input style (same as Settings module)
    const inputClass = "w-full border border-slate-300 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm transition-shadow";
    const labelClass = "block text-sm font-medium text-slate-700 mb-1";

    // Text formatting
    const descriptionRef = useRef<HTMLTextAreaElement>(null);
    const applyFormat = (tag: string) => {
        const textarea = descriptionRef.current;
        if (!textarea) return;
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;
        const before = text.substring(0, start);
        const selection = text.substring(start, end);
        const after = text.substring(end);
        
        let formatted = '';
        if (tag === 'b') formatted = `<b>${selection}</b>`;
        else if (tag === 'i') formatted = `<i>${selection}</i>`;
        else if (tag === 'ul') formatted = `<ul>\n<li>${selection}</li>\n</ul>`;
        
        handleChange('description', before + formatted + after);
    };

    const handleImageUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            const reader = new FileReader();
            reader.onloadend = () => setFormData({ ...formData, image: reader.result as string });
            reader.readAsDataURL(file);
        }
    };

    const handleChange = (field: string, value: any) => {
        setFormData((prev: any) => ({ ...prev, [field]: value }));
    };

    const togglePaymentOption = (option: PaymentOption) => {
        const current = formData.paymentOptions || [];
        if (current.includes(option)) {
            handleChange('paymentOptions', current.filter((o: string) => o !== option));
        } else {
            handleChange('paymentOptions', [...current, option]);
        }
    };

    const toggleSelection = (field: string, value: string) => {
        const current = formData[field] || [];
        const updated = current.includes(value) ? current.filter((v: string) => v !== value) : [...current, value];
        handleChange(field, updated);
    };

    // Simulate Dynamic Price
    useEffect(() => {
        if (formData.price && formData.pricingRuleId) {
            const rule = pricingRules.find(r => r.id === formData.pricingRuleId);
            if (rule) {
                // Just a mock simulation: Apply first tier of rule
                let price = formData.price;
                if (rule.tiers && rule.tiers.length > 0) {
                    const tier = rule.tiers[0];
                    if (tier.adjustmentType === 'Percentage') {
                        price = price + (price * (tier.adjustmentValue / 100));
                    } else {
                        price = price + tier.adjustmentValue;
                    }
                }
                setSimulatedPrice(price);
            }
        } else {
            setSimulatedPrice(null);
        }
    }, [formData.price, formData.pricingRuleId, pricingRules]);

    // Session Management for Events
    const addSession = () => {
        const newSession: EventSession = {
            id: Math.random().toString(36).substr(2, 9),
            date: '', startTime: '09:00', endTime: '10:00', instructorId: '', locationId: '',
            title: '', description: '', awardedBadges: [], requiredBadges: []
        };
        handleChange('sessions', [...(formData.sessions || []), newSession]);
        setExpandedSessionId(newSession.id); // Auto-expand new session
    };

    const updateSession = (id: string, field: keyof EventSession, value: any) => {
        const updatedSessions = formData.sessions.map((s: EventSession) => s.id === id ? { ...s, [field]: value } : s);
        handleChange('sessions', updatedSessions);
    };

    const removeSession = (id: string) => {
        handleChange('sessions', formData.sessions.filter((s: EventSession) => s.id !== id));
    };

    const toggleSessionBadge = (sessionId: string, badgeId: string, field: 'awardedBadges' | 'requiredBadges') => {
        const session = formData.sessions.find((s: EventSession) => s.id === sessionId);
        if (!session) return;

        const current = session[field] || [];
        const updated = current.includes(badgeId)
            ? current.filter((id: string) => id !== badgeId)
            : [...current, badgeId];

        updateSession(sessionId, field, updated);
    };

    // Add VAT Rate Handler
    const handleAddVatRate = () => {
        const description = prompt('Enter VAT rate description (e.g., "Standard Rate"):');
        if (!description) return;
        const rateStr = prompt('Enter VAT rate percentage (e.g., 8.1):');
        if (!rateStr) return;
        const rate = parseFloat(rateStr);
        if (isNaN(rate)) {
            alert('Invalid rate. Please enter a number.');
            return;
        }
        const newVatRate: VATRate = {
            id: `vat_${Date.now()}`,
            description,
            rate,
            code: description.substring(0, 3).toUpperCase()
        };
        setVatRates([...vatRates, newVatRate]);
    };

    // Handle Service Type Selection
    if (step === 'type-select') {
        return (
            <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
                <div className="bg-white rounded-xl shadow-2xl w-full max-w-2xl p-8 animate-fadeIn">
                    <h3 className="text-2xl font-bold text-slate-800 mb-2">What would you like to create?</h3>
                    <p className="text-slate-500 mb-8">Select the type of offer to configure the correct fields.</p>
                    
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        {[
                            { id: 'Service', icon: Clock, title: 'Service', desc: 'Appointments, therapies, or consultations.' },
                            { id: 'Online Course', icon: Video, title: 'Course', desc: 'Digital content with lessons and modules.' },
                            { id: 'Event', icon: Calendar, title: 'Event / Class', desc: 'Group activities at specific times.' }
                        ].map((opt) => (
                            <button 
                                key={opt.id}
                                onClick={() => {
                                    handleChange('type', opt.id);
                                    setStep('form');
                                }}
                                className="flex flex-col items-center text-center p-6 border border-slate-200 rounded-xl hover:border-brand-500 hover:shadow-lg transition-all hover:bg-slate-50 group"
                            >
                                <div className="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center text-slate-600 mb-4 group-hover:bg-brand-100 group-hover:text-brand-600 transition-colors">
                                    <opt.icon size={24} />
                                </div>
                                <h4 className="font-bold text-slate-800 mb-1">{opt.title}</h4>
                                <p className="text-xs text-slate-500">{opt.desc}</p>
                            </button>
                        ))}
                    </div>
                    <div className="mt-8 text-center">
                        <button onClick={onClose} className="text-slate-400 hover:text-slate-600 text-sm">Cancel</button>
                    </div>
                </div>
            </div>
        );
    }

    // Main Form Render
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
            <div className="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden animate-fadeIn">
                <div className="p-6 border-b border-slate-200 flex justify-between items-center bg-white z-10">
                    <div>
                        <h3 className="text-xl font-bold text-slate-800 flex items-center gap-2">
                            {mode === 'create' ? 'Create' : 'Edit'} {type === 'service' ? formData.type : type === 'bundle' ? 'Bundle' : 'Voucher'}
                            {formData.title && <span className="text-slate-400 font-normal">: {formData.title}</span>}
                        </h3>
                        <p className="text-xs text-slate-500">{formData.id ? `ID: ${formData.id}` : 'Configure details'}</p>
                    </div>
                    <button onClick={onClose} className="text-slate-400 hover:text-slate-600"><X size={24} /></button>
                </div>

                {/* Tab Navigation for Services/Offers */}
                {type === 'service' && (
                     <div className="flex border-b border-slate-200 bg-slate-50 px-6 overflow-x-auto">
                        {[
                            { id: 'general', label: 'General', icon: Layout },
                            { id: 'pricing', label: 'Pricing', icon: DollarSign },
                            { id: 'scheduling', label: 'Scheduling', icon: Calendar },
                            { id: 'rules', label: 'Rules & Limits', icon: Shield },
                            { id: 'process', label: 'Process & Media', icon: Settings },
                        ].map(tab => (
                            <button
                                key={tab.id}
                                onClick={() => setActiveTab(tab.id as any)}
                                className={`
                                    py-3 px-4 text-sm font-medium flex items-center gap-2 border-b-2 transition-colors whitespace-nowrap
                                    ${activeTab === tab.id ? 'border-brand-600 text-brand-600 bg-white' : 'border-transparent text-slate-500 hover:text-slate-700'}
                                `}
                            >
                                <tab.icon size={16} /> {tab.label}
                            </button>
                        ))}
                     </div>
                )}

                <div className="flex-1 overflow-y-auto p-6 md:p-8 bg-slate-50/30">
                    
                    {/* --- SERVICE / COURSE / EVENT FORM --- */}
                    {type === 'service' && (
                        <div className="max-w-3xl mx-auto space-y-6">
                            
                            {/* Tab: General */}
                            {activeTab === 'general' && (
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6 animate-fadeIn">
                                    <div className="col-span-full">
                                        <label className={labelClass}>Title</label>
                                        <input className={inputClass} value={formData.title} onChange={e => handleChange('title', e.target.value)} placeholder="e.g. Deep Tissue Massage" />
                                    </div>
                                    
                                    {/* Moved Product Codes Here */}
                                    <div>
                                        <label className={labelClass}>Product Code (SKU)</label>
                                        <input className={inputClass} value={formData.productCode || ''} onChange={e => handleChange('productCode', e.target.value)} placeholder="SVC-001" />
                                    </div>
                                    <div>
                                        <label className={labelClass}>External ID (EAN/GTIN)</label>
                                        <input className={inputClass} value={formData.externalProductCode || ''} onChange={e => handleChange('externalProductCode', e.target.value)} placeholder="761..." />
                                    </div>

                                    {/* Multi-Select Category */}
                                    <div className="col-span-full">
                                        <label className={labelClass}>Categories</label>
                                        <div className="flex flex-wrap gap-2 mb-2 p-2 border border-slate-300 rounded-lg bg-white min-h-[42px]">
                                            {formData.categories?.map((cat: string) => (
                                                <span key={cat} className="bg-slate-100 text-slate-700 px-2 py-1 rounded text-xs flex items-center gap-1">
                                                    {cat} <button onClick={() => toggleSelection('categories', cat)} className="hover:text-rose-500"><X size={12}/></button>
                                                </span>
                                            ))}
                                            <select 
                                                className="bg-transparent text-sm outline-none flex-1 min-w-[100px]"
                                                onChange={(e) => { if(e.target.value) toggleSelection('categories', e.target.value); e.target.value = ''; }}
                                            >
                                                <option value="">+ Add Category</option>
                                                {offerCategories.map(c => <option key={c.id} value={c.name}>{c.name}</option>)}
                                            </select>
                                        </div>
                                    </div>

                                    {/* Tags Multi-select */}
                                    <div className="col-span-full">
                                        <label className={labelClass}>Tags</label>
                                        <div className="flex flex-wrap gap-2 mb-2">
                                            {formData.tags?.map((tag: string) => (
                                                <span key={tag} className="bg-brand-50 text-brand-700 px-2 py-1 rounded text-xs border border-brand-100 flex items-center gap-1">
                                                    {tag} <button onClick={() => toggleSelection('tags', tag)}><X size={12}/></button>
                                                </span>
                                            ))}
                                        </div>
                                        <select className={inputClass} onChange={(e) => { if(e.target.value) toggleSelection('tags', e.target.value); }}>
                                            <option value="">Select Tags...</option>
                                            {offerTags.map(t => <option key={t.id} value={t.name}>{t.name}</option>)}
                                        </select>
                                    </div>

                                    {/* Rich Text Description */}
                                    <div className="col-span-full">
                                        <label className={labelClass}>Description</label>
                                        <div className="border border-slate-300 rounded-lg bg-white overflow-hidden focus-within:ring-2 focus-within:ring-brand-500 focus-within:border-transparent">
                                            <div className="bg-slate-50 border-b border-slate-200 p-2 flex gap-1">
                                                <button onClick={() => applyFormat('b')} className="p-1 hover:bg-slate-200 rounded text-slate-600"><Bold size={14}/></button>
                                                <button onClick={() => applyFormat('i')} className="p-1 hover:bg-slate-200 rounded text-slate-600"><Italic size={14}/></button>
                                                <div className="w-px h-4 bg-slate-300 mx-1 self-center"></div>
                                                <button onClick={() => applyFormat('ul')} className="p-1 hover:bg-slate-200 rounded text-slate-600"><List size={14}/></button>
                                            </div>
                                            <textarea 
                                                ref={descriptionRef}
                                                className="w-full p-3 outline-none text-sm resize-y min-h-[120px]" 
                                                value={formData.description} 
                                                onChange={e => handleChange('description', e.target.value)} 
                                                placeholder="Write a detailed description..."
                                            />
                                        </div>
                                    </div>
                                    
                                    <div className="col-span-full">
                                        <label className="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" checked={formData.active} onChange={e => handleChange('active', e.target.checked)} className="w-4 h-4 text-brand-600 rounded" />
                                            <span className="text-sm text-slate-700 font-medium">Offer is Active (Visible for booking)</span>
                                        </label>
                                    </div>
                                </div>
                            )}

                            {/* Tab: Pricing */}
                            {activeTab === 'pricing' && (
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6 animate-fadeIn">
                                    <div className="col-span-full grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label className={labelClass}>Currency</label>
                                            <select className={inputClass} value={formData.currency || 'CHF'} onChange={e => handleChange('currency', e.target.value)}>
                                                <option value="CHF">CHF</option>
                                                <option value="EUR">EUR</option>
                                                <option value="USD">USD</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label className={labelClass}>Base Price</label>
                                            <input type="number" className={inputClass} value={formData.price} onChange={e => handleChange('price', parseFloat(e.target.value))} />
                                        </div>
                                        <div>
                                            <label className={labelClass}>Sale Price</label>
                                            <input type="number" className={inputClass} value={formData.salePrice || ''} onChange={e => handleChange('salePrice', parseFloat(e.target.value))} placeholder="Optional" />
                                        </div>
                                    </div>

                                    {/* Dynamic Pricing Selector */}
                                    <div className="col-span-full bg-indigo-50 p-4 rounded-xl border border-indigo-100">
                                        <h4 className="font-bold text-indigo-900 text-sm mb-3 flex items-center gap-2"><TrendingUp size={16}/> Dynamic Pricing</h4>
                                        <div className="grid grid-cols-2 gap-4">
                                            <div>
                                                <label className={labelClass}>Mode</label>
                                                <select className={inputClass} value={formData.dynamicPricing} onChange={e => handleChange('dynamicPricing', e.target.value)}>
                                                    <option value="Off">Fixed Price (Off)</option>
                                                    <option value="Manual">Manual Strategy</option>
                                                    <option value="Auto">Auto (AI Optimized)</option>
                                                </select>
                                            </div>
                                            {formData.dynamicPricing !== 'Off' && (
                                                <div>
                                                    <label className={labelClass}>Pricing Strategy</label>
                                                    <select className={inputClass} value={formData.pricingRuleId || ''} onChange={e => handleChange('pricingRuleId', e.target.value)}>
                                                        <option value="">Select Strategy...</option>
                                                        {pricingRules.filter(r => r.active).map(r => (
                                                            <option key={r.id} value={r.id}>{r.name} ({r.type})</option>
                                                        ))}
                                                    </select>
                                                </div>
                                            )}
                                        </div>
                                        {simulatedPrice !== null && (
                                            <div className="mt-3 text-xs text-indigo-700 bg-white/60 p-2 rounded inline-block">
                                                <span className="font-bold">Simulation:</span> Current calculated price would be ~{simulatedPrice.toFixed(2)} {formData.currency} based on selected rules.
                                            </div>
                                        )}
                                    </div>
                                    
                                    {/* VAT Configuration */}
                                    <div className="col-span-full bg-white p-4 rounded-lg border border-slate-200">
                                         <div className="flex justify-between items-center mb-4">
                                            <h4 className="font-bold text-slate-800 text-sm flex items-center gap-2"><Percent size={16}/> Tax Configuration</h4>
                                            <label className="flex items-center gap-2 cursor-pointer">
                                                <input type="checkbox" checked={formData.vatEnabled} onChange={e => handleChange('vatEnabled', e.target.checked)} className="text-brand-600 rounded" />
                                                <span className="text-sm text-slate-700">Enable VAT</span>
                                            </label>
                                        </div>
                                        {formData.vatEnabled && (
                                            <div className="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label className={labelClass}>Sales Tax Rate</label>
                                                    <div className="flex gap-2">
                                                        <select className={inputClass} value={formData.vatRateSales || ''} onChange={e => handleChange('vatRateSales', parseFloat(e.target.value))}>
                                                            <option value="">Select Rate...</option>
                                                            {vatRates.map(vat => (
                                                                <option key={vat.id} value={vat.rate}>{vat.description} ({vat.rate}%)</option>
                                                            ))}
                                                        </select>
                                                        <button type="button" onClick={handleAddVatRate} className="bg-slate-100 hover:bg-slate-200 p-2 rounded text-slate-600" title="Add new VAT rate"><Plus size={16}/></button>
                                                    </div>
                                                </div>
                                                <div>
                                                    <label className={labelClass}>Purchase Tax Rate</label>
                                                    <select className={inputClass} value={formData.vatRatePurchase || ''} onChange={e => handleChange('vatRatePurchase', parseFloat(e.target.value))}>
                                                        <option value="">Select Rate...</option>
                                                        {vatRates.map(vat => (
                                                            <option key={vat.id} value={vat.rate}>{vat.description} ({vat.rate}%)</option>
                                                        ))}
                                                    </select>
                                                </div>
                                            </div>
                                        )}
                                    </div>

                                    <div className="col-span-full border-t border-slate-200 pt-4">
                                        <label className={labelClass}>Accepted Payment Options</label>
                                        <div className="grid grid-cols-2 sm:grid-cols-3 gap-3 mt-2">
                                            {['On Site', 'Credit Card', 'Paypal', 'Invoice', 'Insurance'].map((opt) => (
                                                <label key={opt} className={`
                                                    flex items-center gap-2 p-3 border rounded-lg cursor-pointer transition-colors
                                                    ${formData.paymentOptions?.includes(opt) ? 'bg-brand-50 border-brand-200 text-brand-700' : 'bg-white border-slate-200 hover:bg-slate-50'}
                                                `}>
                                                    <input 
                                                        type="checkbox" 
                                                        checked={formData.paymentOptions?.includes(opt) || false}
                                                        onChange={() => togglePaymentOption(opt as PaymentOption)}
                                                        className="text-brand-600 rounded focus:ring-brand-500"
                                                    />
                                                    <span className="text-sm font-medium">{opt}</span>
                                                </label>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Tab: Scheduling & Resources */}
                            {activeTab === 'scheduling' && (
                                <div className="space-y-6 animate-fadeIn">
                                    {/* Time & Buffer */}
                                    <div className="grid grid-cols-3 gap-4">
                                        <div>
                                            <label className={labelClass}>Duration (min)</label>
                                            <input type="number" className={inputClass} value={formData.duration || ''} onChange={e => handleChange('duration', parseInt(e.target.value))} />
                                        </div>
                                        <div>
                                            <label className={labelClass}>Buffer Before</label>
                                            <input type="number" className={inputClass} value={formData.bufferBefore || 0} onChange={e => handleChange('bufferBefore', parseInt(e.target.value))} />
                                        </div>
                                        <div>
                                            <label className={labelClass}>Buffer After</label>
                                            <input type="number" className={inputClass} value={formData.bufferAfter || 0} onChange={e => handleChange('bufferAfter', parseInt(e.target.value))} />
                                        </div>
                                    </div>
                                    
                                    {/* Recurring */}
                                    {formData.type === 'Service' && (
                                        <div className="flex items-center pt-2">
                                            <label className="flex items-center gap-2 cursor-pointer">
                                                <input type="checkbox" checked={formData.isRecurring || false} onChange={e => handleChange('isRecurring', e.target.checked)} className="w-4 h-4 text-brand-600 rounded" />
                                                <span className="text-sm text-slate-700">Allow Recurring Bookings</span>
                                            </label>
                                        </div>
                                    )}

                                    {/* Resources Multi-Select */}
                                    <div className="border-t border-slate-200 pt-6">
                                        <h4 className="font-bold text-slate-800 mb-3 text-sm uppercase tracking-wider">Required Resources</h4>
                                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <label className={labelClass}>Locations</label>
                                                <div className="h-32 overflow-y-auto border border-slate-300 rounded-lg p-2 bg-white space-y-1">
                                                    {locations.map(loc => (
                                                        <label key={loc.id} className="flex items-center gap-2 text-sm p-1 hover:bg-slate-50 rounded cursor-pointer">
                                                            <input type="checkbox" className="rounded text-brand-600"
                                                                checked={formData.requiredLocations?.includes(loc.id)}
                                                                onChange={() => toggleSelection('requiredLocations', loc.id)}
                                                            />
                                                            {loc.name}
                                                        </label>
                                                    ))}
                                                    {locations.length === 0 && <p className="text-xs text-slate-400 p-2">No locations defined</p>}
                                                </div>
                                            </div>
                                            <div>
                                                <label className={labelClass}>Rooms</label>
                                                <div className="h-32 overflow-y-auto border border-slate-300 rounded-lg p-2 bg-white space-y-1">
                                                    {rooms.map(room => (
                                                        <label key={room.id} className="flex items-center gap-2 text-sm p-1 hover:bg-slate-50 rounded cursor-pointer">
                                                            <input type="checkbox" className="rounded text-brand-600"
                                                                checked={formData.requiredRooms?.includes(room.id)}
                                                                onChange={() => toggleSelection('requiredRooms', room.id)}
                                                            />
                                                            {room.name}
                                                        </label>
                                                    ))}
                                                    {rooms.length === 0 && <p className="text-xs text-slate-400 p-2">No rooms defined</p>}
                                                </div>
                                            </div>
                                            <div>
                                                <label className={labelClass}>Equipment</label>
                                                <div className="h-32 overflow-y-auto border border-slate-300 rounded-lg p-2 bg-white space-y-1">
                                                    {equipment.map(eq => (
                                                        <label key={eq.id} className="flex items-center gap-2 text-sm p-1 hover:bg-slate-50 rounded cursor-pointer">
                                                            <input type="checkbox" className="rounded text-brand-600"
                                                                checked={formData.requiredEquipment?.includes(eq.id)}
                                                                onChange={() => toggleSelection('requiredEquipment', eq.id)}
                                                            />
                                                            {eq.name}
                                                        </label>
                                                    ))}
                                                    {equipment.length === 0 && <p className="text-xs text-slate-400 p-2">No equipment defined</p>}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Extras Selection */}
                                    <div className="border-t border-slate-200 pt-6">
                                        <h4 className="font-bold text-slate-800 mb-3 text-sm uppercase tracking-wider">Upsells & Extras</h4>
                                        <div className="grid grid-cols-2 md:grid-cols-3 gap-2">
                                            {offerExtras.map(extra => (
                                                <label key={extra.id} className={`flex items-center gap-2 p-2 border rounded cursor-pointer ${formData.allowedExtras?.includes(extra.id) ? 'bg-indigo-50 border-indigo-200' : 'bg-white border-slate-200'}`}>
                                                    <input 
                                                        type="checkbox" 
                                                        className="rounded text-indigo-600" 
                                                        checked={formData.allowedExtras?.includes(extra.id)} 
                                                        onChange={() => toggleSelection('allowedExtras', extra.id)} 
                                                    />
                                                    <div>
                                                        <div className="text-sm font-medium text-slate-700">{extra.name}</div>
                                                        <div className="text-xs text-slate-500">+{extra.price} {extra.priceType === 'Percentage' ? '%' : ''}</div>
                                                    </div>
                                                </label>
                                            ))}
                                        </div>
                                    </div>

                                    {/* EVENT SPECIFIC SCHEDULING */}
                                    {formData.type === 'Event' && (
                                        <div className="border border-slate-200 rounded-xl overflow-hidden mt-4">
                                            <div className="bg-slate-50 p-4 border-b border-slate-200">
                                                <h4 className="font-bold text-slate-800 text-sm flex items-center gap-2">
                                                    <Calendar size={16} /> Event Schedule & Sessions
                                                </h4>
                                            </div>
                                            <div className="p-4 space-y-4">
                                                {/* Course Structure Logic */}
                                                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                                                    {[{ id: 'Single', label: 'Single Session' }, { id: 'Series_All', label: 'Course (Book All)' }, { id: 'Series_DropIn', label: 'Drop-In (Book Separate)' }].map(struct => (
                                                        <label key={struct.id} className={`
                                                            flex flex-col items-center p-3 border rounded-lg cursor-pointer text-center
                                                            ${formData.eventStructure === struct.id ? 'bg-indigo-50 border-indigo-200 ring-1 ring-indigo-300' : 'bg-white border-slate-200'}
                                                        `}>
                                                            <input type="radio" name="evtStruct" className="mb-1" checked={formData.eventStructure === struct.id} onChange={() => handleChange('eventStructure', struct.id)} />
                                                            <span className="text-xs font-bold text-slate-700">{struct.label}</span>
                                                        </label>
                                                    ))}
                                                </div>

                                                {/* Session List */}
                                                <div className="space-y-3">
                                                    {formData.sessions?.map((session: EventSession, index: number) => {
                                                        const isExpanded = expandedSessionId === session.id;
                                                        return (
                                                            <div key={session.id} className="bg-white border border-slate-200 rounded-lg overflow-hidden shadow-sm">
                                                                <div className="grid grid-cols-1 sm:grid-cols-4 gap-3 items-center p-4 bg-slate-50">
                                                                    <div className="sm:col-span-1">
                                                                        <label className="text-xs font-bold text-slate-500 uppercase mb-1 block">Date</label>
                                                                        <input 
                                                                            type="date" 
                                                                            className={inputClass} 
                                                                            value={session.date} 
                                                                            onChange={e => updateSession(session.id, 'date', e.target.value)} 
                                                                        />
                                                                    </div>
                                                                    <div className="sm:col-span-2">
                                                                        <label className="text-xs font-bold text-slate-500 uppercase mb-1 block">Time</label>
                                                                        <div className="flex items-center gap-2">
                                                                            <input type="time" className={inputClass} value={session.startTime} onChange={e => updateSession(session.id, 'startTime', e.target.value)} />
                                                                            <span className="text-slate-400 font-bold">-</span>
                                                                            <input type="time" className={inputClass} value={session.endTime} onChange={e => updateSession(session.id, 'endTime', e.target.value)} />
                                                                        </div>
                                                                    </div>
                                                                    <div className="flex gap-2 justify-end sm:col-span-1 mt-2 sm:mt-0">
                                                                        <button 
                                                                            onClick={() => setExpandedSessionId(isExpanded ? null : session.id)}
                                                                            className={`flex-1 sm:flex-none px-3 py-2 rounded border transition-colors text-xs font-medium flex items-center justify-center gap-1 ${isExpanded ? 'bg-indigo-100 text-indigo-700 border-indigo-200' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50'}`}
                                                                        >
                                                                            {isExpanded ? 'Less' : 'More Options'}
                                                                            {isExpanded ? <ChevronUp size={14} /> : <ChevronDown size={14} />}
                                                                        </button>
                                                                        <button onClick={() => removeSession(session.id)} className="p-2 bg-white border border-slate-300 text-rose-500 rounded hover:bg-rose-50">
                                                                            <Trash2 size={16} />
                                                                        </button>
                                                                    </div>
                                                                </div>

                                                                {isExpanded && (
                                                                    <div className="p-4 border-t border-slate-100 space-y-4 animate-slideDown">
                                                                        {/* Advanced Session Options */}
                                                                        <div className="col-span-full">
                                                                            <label className="text-xs text-slate-500 font-bold mb-1 block flex items-center gap-1"><BookOpen size={12}/> Linked Academy Lesson</label>
                                                                            <select 
                                                                                className={inputClass} 
                                                                                value={session.linkedLessonId || ''} 
                                                                                onChange={e => updateSession(session.id, 'linkedLessonId', e.target.value)}
                                                                            >
                                                                                <option value="">-- Select Lesson from Academy --</option>
                                                                                {lessons.map(l => (
                                                                                    <option key={l.id} value={l.id}>{l.title}</option>
                                                                                ))}
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                )}
                                                            </div>
                                                        );
                                                    })}
                                                    <button onClick={addSession} className="w-full py-2 border-2 border-dashed border-slate-300 rounded-lg text-slate-500 font-bold text-xs hover:bg-slate-50 flex justify-center items-center gap-2">
                                                        <Plus size={14} /> Add Session
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}

                            {/* Tab: Rules & Limits */}
                            {activeTab === 'rules' && (
                                <div className="space-y-6 animate-fadeIn">
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label className={labelClass}>Capacity / Participants</label>
                                            <input type="number" className={inputClass} value={formData.capacity} onChange={e => handleChange('capacity', parseInt(e.target.value))} />
                                        </div>
                                        {formData.type === 'Event' && (
                                            <div>
                                                <label className={labelClass}>Min Participants (Auto-Cancel)</label>
                                                <input type="number" className={inputClass} value={formData.minParticipants} onChange={e => handleChange('minParticipants', parseInt(e.target.value))} />
                                            </div>
                                        )}
                                    </div>

                                    {/* Notice Periods */}
                                    <div className="border-t border-slate-200 pt-6">
                                        <h4 className="font-bold text-slate-800 mb-4 text-sm">Minimum Notice Periods</h4>
                                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <label className="text-xs text-slate-500 mb-1 block">Before Booking</label>
                                                <div className="flex gap-2">
                                                    <input
                                                        type="number"
                                                        className={inputClass}
                                                        value={formData.minNotice?.find((n: any) => n.type === 'booking')?.value || ''}
                                                        onChange={e => {
                                                            const updated = formData.minNotice?.map((n: any) =>
                                                                n.type === 'booking' ? { ...n, value: parseInt(e.target.value) || 0 } : n
                                                            ) || [];
                                                            handleChange('minNotice', updated);
                                                        }}
                                                        placeholder="24"
                                                    />
                                                    <select
                                                        className="bg-slate-50 border border-slate-300 rounded-lg text-sm"
                                                        value={formData.minNotice?.find((n: any) => n.type === 'booking')?.unit || 'hours'}
                                                        onChange={e => {
                                                            const updated = formData.minNotice?.map((n: any) =>
                                                                n.type === 'booking' ? { ...n, unit: e.target.value } : n
                                                            ) || [];
                                                            handleChange('minNotice', updated);
                                                        }}
                                                    >
                                                        <option value="hours">Hours</option>
                                                        <option value="days">Days</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div>
                                                <label className="text-xs text-slate-500 mb-1 block">Before Cancellation</label>
                                                <div className="flex gap-2">
                                                    <input
                                                        type="number"
                                                        className={inputClass}
                                                        value={formData.minNotice?.find((n: any) => n.type === 'cancel')?.value || ''}
                                                        onChange={e => {
                                                            const updated = formData.minNotice?.map((n: any) =>
                                                                n.type === 'cancel' ? { ...n, value: parseInt(e.target.value) || 0 } : n
                                                            ) || [];
                                                            handleChange('minNotice', updated);
                                                        }}
                                                        placeholder="48"
                                                    />
                                                    <select
                                                        className="bg-slate-50 border border-slate-300 rounded-lg text-sm"
                                                        value={formData.minNotice?.find((n: any) => n.type === 'cancel')?.unit || 'hours'}
                                                        onChange={e => {
                                                            const updated = formData.minNotice?.map((n: any) =>
                                                                n.type === 'cancel' ? { ...n, unit: e.target.value } : n
                                                            ) || [];
                                                            handleChange('minNotice', updated);
                                                        }}
                                                    >
                                                        <option value="hours">Hours</option>
                                                        <option value="days">Days</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div>
                                                <label className="text-xs text-slate-500 mb-1 block">Before Changes (Reschedule)</label>
                                                <div className="flex gap-2">
                                                    <input
                                                        type="number"
                                                        className={inputClass}
                                                        value={formData.minNotice?.find((n: any) => n.type === 'reschedule')?.value || ''}
                                                        onChange={e => {
                                                            const updated = formData.minNotice?.map((n: any) =>
                                                                n.type === 'reschedule' ? { ...n, value: parseInt(e.target.value) || 0 } : n
                                                            ) || [];
                                                            handleChange('minNotice', updated);
                                                        }}
                                                        placeholder="24"
                                                    />
                                                    <select
                                                        className="bg-slate-50 border border-slate-300 rounded-lg text-sm"
                                                        value={formData.minNotice?.find((n: any) => n.type === 'reschedule')?.unit || 'hours'}
                                                        onChange={e => {
                                                            const updated = formData.minNotice?.map((n: any) =>
                                                                n.type === 'reschedule' ? { ...n, unit: e.target.value } : n
                                                            ) || [];
                                                            handleChange('minNotice', updated);
                                                        }}
                                                    >
                                                        <option value="hours">Hours</option>
                                                        <option value="days">Days</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Waitlist */}
                                    <div className="p-4 bg-slate-50 border border-slate-200 rounded-xl">
                                        <div className="flex justify-between items-center mb-3">
                                            <span className="font-bold text-slate-800 text-sm">Waitlist Configuration</span>
                                            <label className="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" className="sr-only peer" checked={formData.waitlistEnabled} onChange={e => handleChange('waitlistEnabled', e.target.checked)} />
                                                <div className="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                                            </label>
                                        </div>
                                        {formData.waitlistEnabled && (
                                            <div>
                                                <label className={labelClass}>Waitlist Capacity</label>
                                                <input type="number" className={inputClass} value={formData.waitlistCapacity} onChange={e => handleChange('waitlistCapacity', parseInt(e.target.value))} />
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}

                            {/* Tab: Process & Media */}
                            {activeTab === 'process' && (
                                <div className="space-y-6 animate-fadeIn">
                                    {/* Integration Selector */}
                                    <div className="bg-indigo-50 p-4 rounded-xl border border-indigo-100">
                                        <h4 className="font-bold text-indigo-900 text-sm mb-3 flex items-center gap-2">
                                            <LinkIcon size={16} /> Video Integration
                                        </h4>
                                        <select className={inputClass} value={formData.integration} onChange={e => handleChange('integration', e.target.value)}>
                                            <option value="None">No Integration (In Person)</option>
                                            <option value="Zoom">Zoom Meetings</option>
                                            <option value="Google Meet">Google Meet</option>
                                            <option value="Microsoft Teams">Microsoft Teams</option>
                                        </select>
                                        <p className="text-xs text-indigo-700 mt-2">Requires configured connection in Settings &gt; Integrations.</p>
                                    </div>

                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label className={labelClass}>Booking Form Template</label>
                                            <select className={inputClass} value={formData.formTemplateId || ''} onChange={e => handleChange('formTemplateId', e.target.value)}>
                                                <option value="">Default (Standard)</option>
                                                {bookingFormTemplates.map(t => (
                                                    <option key={t.id} value={t.id}>{t.name}</option>
                                                ))}
                                            </select>
                                        </div>
                                        <div>
                                            <label className={labelClass}>Default Status</label>
                                            <select className={inputClass} value={formData.defaultStatus} onChange={e => handleChange('defaultStatus', e.target.value)}>
                                                <option value="Confirmed">Confirmed Immediately</option>
                                                <option value="Pending">Pending Approval</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div className="border-t border-slate-200 pt-6">
                                        <label className={labelClass}>Cover Image</label>
                                        <div className="flex items-center gap-4 mb-4">
                                            <div className="w-24 h-16 bg-slate-100 rounded-lg overflow-hidden border border-slate-200 flex items-center justify-center">
                                                {formData.image ? <img src={formData.image} className="w-full h-full object-cover" /> : <ImageIcon className="text-slate-300" />}
                                            </div>
                                            <button onClick={() => fileInputRef.current?.click()} className="text-sm text-brand-600 font-medium hover:underline flex items-center gap-1">
                                                <Upload size={14} /> Upload Image
                                            </button>
                                            <input type="file" ref={fileInputRef} onChange={handleImageUpload} className="hidden" accept="image/*" />
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    )}

                    {/* --- BUNDLE FORM --- */}
                    {type === 'bundle' && (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div className="col-span-full">
                                <label className={labelClass}>Bundle Title</label>
                                <input className={inputClass} value={formData.title} onChange={e => handleChange('title', e.target.value)} />
                            </div>
                            <div>
                                <label className={labelClass}>Bundle Price ($)</label>
                                <input type="number" className={inputClass} value={formData.price} onChange={e => handleChange('price', parseFloat(e.target.value))} />
                            </div>
                            <div>
                                <label className={labelClass}>Original Price ($)</label>
                                <input type="number" className={inputClass} value={formData.originalPrice} onChange={e => handleChange('originalPrice', parseFloat(e.target.value))} />
                            </div>
                            <div className="col-span-full">
                                <label className={labelClass}>Included Services</label>
                                <div className="border border-slate-200 rounded-lg p-3 max-h-40 overflow-y-auto bg-white grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    {availableServices.map(svc => (
                                        <label key={svc.id} className="flex items-center gap-2 text-sm p-1 hover:bg-slate-50 rounded cursor-pointer">
                                            <input 
                                                type="checkbox" 
                                                checked={formData.items?.includes(svc.title)}
                                                onChange={() => {
                                                    const currentItems = formData.items || [];
                                                    if (currentItems.includes(svc.title)) {
                                                        handleChange('items', currentItems.filter((i: string) => i !== svc.title));
                                                    } else {
                                                        handleChange('items', [...currentItems, svc.title]);
                                                    }
                                                }}
                                                className="rounded text-brand-600 focus:ring-brand-500"
                                            />
                                            <span className="truncate">{svc.title}</span>
                                        </label>
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}

                    {/* --- VOUCHER FORM --- */}
                    {type === 'voucher' && (
                        <div className="space-y-6">
                            {/* Category Switcher */}
                            <div className="flex justify-center mb-6">
                                <div className="bg-slate-100 p-1 rounded-lg inline-flex">
                                    <button 
                                        onClick={() => handleChange('category', 'Promotion')}
                                        className={`px-4 py-2 text-sm font-medium rounded-md transition-all ${formData.category === 'Promotion' ? 'bg-white text-brand-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'}`}
                                    >
                                        Marketing Promotion
                                    </button>
                                    <button 
                                        onClick={() => handleChange('category', 'GiftCard')}
                                        className={`px-4 py-2 text-sm font-medium rounded-md transition-all ${formData.category === 'GiftCard' ? 'bg-white text-rose-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'}`}
                                    >
                                        Gift Card Product
                                    </button>
                                </div>
                            </div>

                            {formData.category === 'Promotion' ? (
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6 animate-fadeIn">
                                    <div className="col-span-full">
                                        <label className={labelClass}>Campaign Title</label>
                                        <input className={inputClass} value={formData.title} onChange={e => handleChange('title', e.target.value)} placeholder="e.g. Summer Sale 2023" />
                                    </div>
                                    <div className="col-span-full">
                                        <label className={labelClass}>Voucher Code</label>
                                        <input className={`${inputClass} font-mono uppercase`} value={formData.code} onChange={e => handleChange('code', e.target.value.toUpperCase())} placeholder="e.g. SAVE20" />
                                    </div>
                                    <div>
                                        <label className={labelClass}>Discount Type</label>
                                        <select className={inputClass} value={formData.discountType} onChange={e => handleChange('discountType', e.target.value)}>
                                            <option value="Percentage">Percentage (%)</option>
                                            <option value="Fixed">Fixed Amount ($)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label className={labelClass}>Value</label>
                                        <input type="number" className={inputClass} value={formData.discountValue} onChange={e => handleChange('discountValue', parseFloat(e.target.value))} />
                                    </div>
                                    <div>
                                        <label className={labelClass}>Total Global Uses</label>
                                        <input type="number" className={inputClass} value={formData.maxUses || ''} onChange={e => handleChange('maxUses', parseInt(e.target.value))} placeholder="Unlimited" />
                                    </div>
                                    <div>
                                        <label className={labelClass}>Limit Per Customer</label>
                                        <input type="number" className={inputClass} value={formData.maxUsesPerCustomer || ''} onChange={e => handleChange('maxUsesPerCustomer', parseInt(e.target.value))} placeholder="Unlimited" />
                                    </div>
                                </div>
                            ) : (
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6 animate-fadeIn">
                                    <div className="col-span-full">
                                        <label className={labelClass}>Product Title</label>
                                        <input className={inputClass} value={formData.title} onChange={e => handleChange('title', e.target.value)} placeholder="e.g. General Gift Card" />
                                    </div>
                                    <div className="col-span-full border-b border-slate-100 pb-4">
                                        <label className="flex items-center gap-3 p-4 border border-slate-200 rounded-lg cursor-pointer hover:bg-slate-50 transition-colors">
                                            <input 
                                                type="checkbox" 
                                                className="w-5 h-5 text-rose-600 rounded focus:ring-rose-500"
                                                checked={formData.allowCustomAmount}
                                                onChange={e => handleChange('allowCustomAmount', e.target.checked)}
                                            />
                                            <div>
                                                <span className="font-bold text-slate-800 block">Allow Custom Amount</span>
                                                <span className="text-xs text-slate-500">Customers can enter their desired value.</span>
                                            </div>
                                        </label>
                                    </div>
                                    {formData.allowCustomAmount ? (
                                        <>
                                            <div>
                                                <label className={labelClass}>Minimum Amount</label>
                                                <input type="number" className={inputClass} value={formData.minCustomAmount} onChange={e => handleChange('minCustomAmount', parseFloat(e.target.value))} />
                                            </div>
                                            <div>
                                                <label className={labelClass}>Maximum Amount</label>
                                                <input type="number" className={inputClass} value={formData.maxCustomAmount} onChange={e => handleChange('maxCustomAmount', parseFloat(e.target.value))} />
                                            </div>
                                        </>
                                    ) : (
                                        <div className="col-span-full">
                                            <label className={labelClass}>Fixed Card Value</label>
                                            <input type="number" className={inputClass} value={formData.fixedValue} onChange={e => handleChange('fixedValue', parseFloat(e.target.value))} placeholder="e.g. 50" />
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                    )}

                </div>

                <div className="p-6 border-t border-slate-200 bg-white flex justify-between items-center">
                    {step === 'form' && mode === 'create' && type === 'service' ? (
                         <button onClick={() => setStep('type-select')} className="text-slate-500 hover:text-slate-700 text-sm flex items-center gap-1">
                             <ArrowRight size={14} className="rotate-180" /> Back
                         </button>
                    ) : <div></div>}
                    <div className="flex gap-3">
                        <button onClick={onClose} className="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 font-medium text-sm">Cancel</button>
                        <button onClick={() => onSave(formData)} className="px-6 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-medium shadow-sm text-sm flex items-center gap-2">
                            <Save size={16} /> Save {type === 'voucher' ? 'Voucher' : 'Offer'}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );

export default OfferModal;
