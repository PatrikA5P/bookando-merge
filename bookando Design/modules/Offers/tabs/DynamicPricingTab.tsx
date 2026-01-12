import React, { useState, useRef, useEffect } from 'react';
import {
    Plus, Trash2, Clock, Zap, CalendarDays, TrendingUp, History, Activity,
    Sliders, Info, Hash, Shield, LinkIcon, ExternalLink, ArrowLeft, Save,
    AlertCircle, X
} from 'lucide-react';
import { useApp } from '../../../context/AppContext';
import { DynamicPricingRule, SeasonalRule, DaySchedule, TimeSlot, ServiceItem } from '../../../types';

interface DynamicPricingTabProps {
    createTrigger?: number;
    handleEditService?: (id: string) => void;
    services: ServiceItem[];
}

const DynamicPricingTab: React.FC<DynamicPricingTabProps> = ({ createTrigger, handleEditService, services }) => {
    const { pricingRules, setPricingRules, systemCurrency } = useApp();
    const [viewMode, setViewMode] = useState<'overview' | 'editor'>('overview');
    const [selectedRuleId, setSelectedRuleId] = useState<string | null>(null);
    const [formData, setFormData] = useState<DynamicPricingRule | null>(null);

    // Styling constants
    const inputClass = "w-full border border-slate-300 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm transition-shadow";
    const labelClass = "block text-sm font-medium text-slate-700 mb-1";

    // Handle Create Trigger
    const previousTrigger = useRef<number | undefined>(createTrigger);
    useEffect(() => {
        if (createTrigger !== undefined && previousTrigger.current !== createTrigger) {
            handleCreateNew();
        }
        previousTrigger.current = createTrigger;
    }, [createTrigger]);

    const handleCreateNew = () => {
        const newRule: DynamicPricingRule = {
            id: `pr_${Date.now()}`,
            name: 'New Pricing Strategy',
            type: 'EarlyBird',
            active: false,
            roundingValue: 0.05,
            roundingMethod: 'Nearest',
            priceEnding: 'None',
            maxIncreasePercent: 20,
            maxDecreasePercent: 20,
            aggressiveness: 'Neutral',
            tiers: [{ id: 't1', conditionValue: 1, conditionUnit: 'Days', adjustmentValue: 0, adjustmentType: 'Percentage' }],
            seasonalRules: []
        };
        setFormData(newRule);
        setViewMode('editor');
    };

    const handleEdit = (id: string) => {
        const rule = pricingRules.find(r => r.id === id);
        if (rule) {
            setFormData({ ...rule });
            setSelectedRuleId(id);
            setViewMode('editor');
        }
    };

    const handleDelete = (id: string, e: React.MouseEvent) => {
        e.stopPropagation();
        if (confirm('Delete this pricing strategy? Services using it will revert to base price.')) {
            setPricingRules(pricingRules.filter(r => r.id !== id));
        }
    };

    const handleSave = () => {
        if (!formData) return;

        if (selectedRuleId) {
            setPricingRules(pricingRules.map(r => r.id === selectedRuleId ? formData : r));
        } else {
            setPricingRules([...pricingRules, formData]);
        }
        setViewMode('overview');
        setSelectedRuleId(null);
        setFormData(null);
    };

    const updateField = (field: keyof DynamicPricingRule, value: any) => {
        if (formData) setFormData({ ...formData, [field]: value });
    };

    const getLinkedServices = (ruleId: string) => {
        return services.filter(s => s.pricingRuleId === ruleId);
    };

    const getTypeColor = (type: string) => {
        switch(type) {
            case 'EarlyBird': return 'text-blue-600 bg-blue-50';
            case 'LastMinute': return 'text-amber-600 bg-amber-50';
            case 'Season': return 'text-emerald-600 bg-emerald-50';
            case 'Demand': return 'text-purple-600 bg-purple-50';
            case 'History': return 'text-rose-600 bg-rose-50';
            default: return 'text-slate-600 bg-slate-50';
        }
    };

    const getTypeIcon = (type: string) => {
        switch(type) {
            case 'EarlyBird': return <Clock size={20}/>;
            case 'LastMinute': return <Zap size={20}/>;
            case 'Season': return <CalendarDays size={20}/>;
            case 'Demand': return <TrendingUp size={20}/>;
            case 'History': return <History size={20}/>;
            default: return <Activity size={20}/>;
        }
    }

    if (viewMode === 'overview') {
        return (
            <div className="flex-1 p-6 overflow-y-auto animate-fadeIn">
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <button
                        onClick={handleCreateNew}
                        className="border-2 border-dashed border-slate-300 rounded-xl p-6 flex flex-col items-center justify-center text-slate-400 hover:border-brand-400 hover:text-brand-600 hover:bg-slate-50 transition-all min-h-[200px]"
                    >
                        <div className="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mb-3 group-hover:bg-brand-50">
                            <Plus size={24} />
                        </div>
                        <span className="font-bold">Create New Strategy</span>
                    </button>

                    {pricingRules.map(rule => {
                        const linkedCount = getLinkedServices(rule.id).length;
                        return (
                            <div
                                key={rule.id}
                                onClick={() => handleEdit(rule.id)}
                                className="bg-white rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-all p-5 flex flex-col cursor-pointer group relative"
                            >
                                <div className="flex justify-between items-start mb-4">
                                    <div className={`p-3 rounded-lg ${getTypeColor(rule.type)}`}>
                                        {getTypeIcon(rule.type)}
                                    </div>
                                    <div className={`px-2 py-1 rounded text-xs font-bold ${rule.active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'}`}>
                                        {rule.active ? 'Active' : 'Draft'}
                                    </div>
                                </div>
                                <h3 className="font-bold text-slate-800 text-lg mb-1">{rule.name}</h3>
                                <p className="text-sm text-slate-500 mb-4">{rule.type} Strategy</p>

                                <div className="mt-auto pt-4 border-t border-slate-100 flex justify-between items-center text-sm">
                                    <span className="text-slate-500 flex items-center gap-1">
                                        <LinkIcon size={14}/> {linkedCount} Services
                                    </span>
                                    <div className="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button
                                            onClick={(e) => handleDelete(rule.id, e)}
                                            className="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded transition-colors"
                                        >
                                            <Trash2 size={16} />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>
        );
    }

    // EDITOR MODE
    if (!formData) return null;

    return (
        <div className="flex-1 flex flex-col h-full bg-slate-50/50 overflow-hidden animate-fadeIn">
            {/* Header */}
            <div className="p-4 border-b border-slate-200 bg-white flex justify-between items-center shadow-sm z-10">
                <div className="flex items-center gap-4">
                    <button onClick={() => setViewMode('overview')} className="text-slate-500 hover:text-slate-700 flex items-center gap-1 text-sm font-medium">
                        <ArrowLeft size={16} /> Back to Overview
                    </button>
                    <div className="h-6 w-px bg-slate-200"></div>
                    <span className="font-bold text-slate-800">Edit Strategy</span>
                </div>
                <div className="flex gap-3">
                    <button onClick={() => setViewMode('overview')} className="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 font-medium text-sm">
                        Cancel
                    </button>
                    <button onClick={handleSave} className="px-6 py-2 bg-brand-600 text-white rounded-lg font-medium hover:bg-brand-700 text-sm flex items-center gap-2 shadow-sm">
                        <Save size={16} /> Save Changes
                    </button>
                </div>
            </div>

            <div className="flex-1 overflow-y-auto p-6 md:p-8">
                <div className="max-w-5xl mx-auto space-y-6">

                    {/* General Settings Card */}
                    <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                        <div className="flex justify-between items-start mb-6">
                            <div className="flex-1 mr-8">
                                <label className={labelClass}>Strategy Name</label>
                                <input
                                    className={`${inputClass} text-lg font-bold`}
                                    value={formData.name}
                                    onChange={e => updateField('name', e.target.value)}
                                    placeholder="e.g. Summer Early Bird"
                                />
                            </div>
                            <div className="flex items-center gap-2 pt-6">
                                <label className="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" className="sr-only peer" checked={formData.active} onChange={e => updateField('active', e.target.checked)} />
                                    <div className="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                                    <span className="ml-3 text-sm font-medium text-slate-700">{formData.active ? 'Active' : 'Draft'}</span>
                                </label>
                                <button onClick={(e) => handleDelete(formData.id, e)} className="p-2 text-slate-400 hover:text-rose-600 bg-slate-50 rounded hover:bg-rose-50 ml-2">
                                    <Trash2 size={18}/>
                                </button>
                            </div>
                        </div>

                        <div className="mb-6">
                            <label className={labelClass}>Strategy Type</label>
                            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2">
                                {['EarlyBird', 'LastMinute', 'Season', 'Demand', 'History'].map(type => (
                                    <button
                                        key={type}
                                        onClick={() => updateField('type', type as any)}
                                        className={`
                                            flex flex-col items-center justify-center p-3 rounded-lg border transition-all text-center
                                            ${formData.type === type ? 'bg-indigo-50 border-indigo-500 text-indigo-700 ring-1 ring-indigo-500' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'}
                                        `}
                                    >
                                        <div className="mb-1 opacity-80">{getTypeIcon(type)}</div>
                                        <span className="text-xs font-bold">
                                            {type === 'EarlyBird' ? 'Early Bird' : type === 'LastMinute' ? 'Last Minute' : type}
                                        </span>
                                    </button>
                                ))}
                            </div>
                        </div>
                    </div>

                    {/* Configuration Card */}
                    <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                        <h4 className="font-bold text-slate-800 text-lg mb-4 flex items-center gap-2">
                            <Sliders size={20} className="text-brand-600"/> Logic Configuration
                        </h4>

                        {/* --- LOGIC: TIERS (EarlyBird / LastMinute) --- */}
                        {(formData.type === 'EarlyBird' || formData.type === 'LastMinute') && (
                            <div className="space-y-4">
                                <div className="bg-slate-50 p-4 rounded-lg border border-slate-200 text-sm text-slate-600 mb-4 flex gap-3">
                                    <Info className="shrink-0 text-brand-500" size={20} />
                                    <div>
                                        <p className="font-bold text-slate-800 mb-1">How it works:</p>
                                        {formData.type === 'EarlyBird'
                                            ? "Discounts apply when customers book far in advance. Define tiers based on 'Time Before Event'."
                                            : "Prices increase as the event gets closer. Define tiers based on 'Time Before Event'."
                                        }
                                    </div>
                                </div>

                                <div className="space-y-3">
                                    {formData.tiers?.map((tier, idx) => (
                                        <div key={idx} className="flex flex-wrap md:flex-nowrap items-end gap-3 p-4 bg-slate-50 border border-slate-200 rounded-lg group">
                                            <div className="w-full md:w-auto">
                                                <label className="text-xs font-bold text-slate-500 mb-1 block">Time to Event</label>
                                                <div className="flex gap-2">
                                                    <input
                                                        type="number" className={`${inputClass} w-20`}
                                                        value={tier.conditionValue}
                                                        onChange={e => {
                                                            const newTiers = [...(formData.tiers || [])];
                                                            newTiers[idx].conditionValue = parseFloat(e.target.value);
                                                            updateField('tiers', newTiers);
                                                        }}
                                                    />
                                                    <select
                                                        className={`${inputClass} w-24`}
                                                        value={tier.conditionUnit}
                                                        onChange={e => {
                                                            const newTiers = [...(formData.tiers || [])];
                                                            newTiers[idx].conditionUnit = e.target.value as any;
                                                            updateField('tiers', newTiers);
                                                        }}
                                                    >
                                                        <option value="Days">Days</option>
                                                        <option value="Hours">Hours</option>
                                                        <option value="Weeks">Weeks</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div className="w-full md:w-auto">
                                                <label className="text-xs font-bold text-slate-500 mb-1 block">Adjustment</label>
                                                <div className="flex gap-2">
                                                    <select
                                                        className={`${inputClass} w-16`}
                                                        value={tier.adjustmentValue >= 0 ? '+' : '-'}
                                                        onChange={e => {
                                                            const newTiers = [...(formData.tiers || [])];
                                                            newTiers[idx].adjustmentValue = Math.abs(newTiers[idx].adjustmentValue) * (e.target.value === '+' ? 1 : -1);
                                                            updateField('tiers', newTiers);
                                                        }}
                                                    >
                                                        <option value="+">+</option>
                                                        <option value="-">-</option>
                                                    </select>
                                                    <input
                                                        type="number" className={`${inputClass} w-24`}
                                                        value={Math.abs(tier.adjustmentValue)}
                                                        onChange={e => {
                                                            const sign = tier.adjustmentValue >= 0 ? 1 : -1;
                                                            const newTiers = [...(formData.tiers || [])];
                                                            newTiers[idx].adjustmentValue = parseFloat(e.target.value) * sign;
                                                            updateField('tiers', newTiers);
                                                        }}
                                                    />
                                                    <select
                                                        className={`${inputClass} w-28`}
                                                        value={tier.adjustmentType}
                                                        onChange={e => {
                                                            const newTiers = [...(formData.tiers || [])];
                                                            newTiers[idx].adjustmentType = e.target.value as any;
                                                            updateField('tiers', newTiers);
                                                        }}
                                                    >
                                                        <option value="Percentage">%</option>
                                                        <option value="FixedAmount">{systemCurrency}</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div className="w-full md:w-auto flex-1">
                                                <label className="text-xs font-bold text-slate-500 mb-1 block">
                                                    {formData.type === 'LastMinute' ? 'Not Until' : 'Limit Condition'}
                                                </label>
                                                <div className="flex gap-2">
                                                    <input
                                                        type="number" className={`${inputClass} w-20`}
                                                        value={tier.limitValue || 0}
                                                        onChange={e => {
                                                            const newTiers = [...(formData.tiers || [])];
                                                            newTiers[idx].limitValue = parseFloat(e.target.value);
                                                            updateField('tiers', newTiers);
                                                        }}
                                                    />
                                                    <select
                                                        className={`${inputClass}`}
                                                        value={tier.limitMetric || 'CapacityPercent'}
                                                        onChange={e => {
                                                            const newTiers = [...(formData.tiers || [])];
                                                            newTiers[idx].limitMetric = e.target.value as any;
                                                            updateField('tiers', newTiers);
                                                        }}
                                                    >
                                                        <option value="CapacityPercent">% Capacity</option>
                                                        <option value="BookingsCount">Bookings</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <button
                                                onClick={() => {
                                                    const newTiers = formData.tiers?.filter((_, i) => i !== idx);
                                                    updateField('tiers', newTiers);
                                                }}
                                                className="p-2 text-slate-400 hover:text-rose-500 mb-0.5"
                                            >
                                                <Trash2 size={18} />
                                            </button>
                                        </div>
                                    ))}
                                    <button
                                        onClick={() => updateField('tiers', [...(formData.tiers || []), { id: `t${Date.now()}`, conditionValue: 1, conditionUnit: 'Days', adjustmentValue: 0, adjustmentType: 'Percentage' }])}
                                        className="w-full py-3 border-2 border-dashed border-slate-300 rounded-lg text-slate-500 font-bold text-sm hover:border-brand-400 hover:text-brand-600 flex items-center justify-center gap-2 transition-colors"
                                    >
                                        <Plus size={16} /> Add Tier
                                    </button>
                                </div>
                            </div>
                        )}

                        {/* --- LOGIC: SEASON / DAYTIME --- */}
                        {formData.type === 'Season' && (
                            <div className="space-y-6">
                                <SeasonalEditor
                                    rules={formData.seasonalRules || []}
                                    onChange={newRules => updateField('seasonalRules', newRules)}
                                    currency={systemCurrency}
                                />
                            </div>
                        )}

                        {/* --- LOGIC: DEMAND & HISTORY (AI) --- */}
                        {(formData.type === 'Demand' || formData.type === 'History') && (
                            <div className="bg-indigo-50 border border-indigo-100 rounded-xl p-6">
                                <div className="flex items-start gap-4">
                                    <div className="p-3 bg-indigo-100 text-indigo-700 rounded-lg shadow-sm">
                                        <Activity size={24} />
                                    </div>
                                    <div className="flex-1">
                                        <h4 className="font-bold text-indigo-900 text-lg mb-1">
                                            {formData.type === 'Demand' ? 'Demand Surge Engine' : 'Predictive AI Model'}
                                        </h4>
                                        <p className="text-sm text-indigo-800 mb-4 leading-relaxed">
                                            {formData.type === 'Demand'
                                                ? "Our algorithms analyze real-time booking velocity to detect surges. Prices will be automatically adjusted based on short-term demand spikes to maximize revenue."
                                                : "Our algorithms analyze historical booking data to predict demand curves. Prices are optimized daily based on past performance and booking trends."
                                            }
                                        </p>

                                        <div className="bg-white/50 rounded-lg p-4 border border-indigo-100">
                                            <label className="block text-xs font-bold text-indigo-900 uppercase mb-3">Model Aggressiveness</label>
                                            <div className="flex bg-white rounded-lg border border-indigo-200 p-1">
                                                {['Mild', 'Neutral', 'Aggressive'].map(level => (
                                                    <button
                                                        key={level}
                                                        onClick={() => updateField('aggressiveness', level as any)}
                                                        className={`flex-1 py-2 text-sm font-medium rounded-md transition-all ${
                                                            formData.aggressiveness === level
                                                                ? 'bg-indigo-600 text-white shadow-sm'
                                                                : 'text-indigo-600 hover:bg-indigo-50'
                                                        }`}
                                                    >
                                                        {level}
                                                    </button>
                                                ))}
                                            </div>
                                            <p className="text-xs text-indigo-600 mt-2 text-center italic">
                                                {formData.aggressiveness === 'Mild' && "Conservative adjustments. Prioritizes occupancy over yield."}
                                                {formData.aggressiveness === 'Neutral' && "Balanced approach. Standard yield management."}
                                                {formData.aggressiveness === 'Aggressive' && "Maximizes price during peaks. May impact occupancy."}
                                            </p>
                                        </div>

                                        {formData.type === 'Demand' && (
                                            <div className="mt-4 grid grid-cols-2 gap-4">
                                                <div>
                                                    <label className="text-xs font-bold text-indigo-900 mb-1 block">Lookback Period</label>
                                                    <select className={inputClass} value={formData.demandConfig?.lookbackHours || 4} onChange={e => updateField('demandConfig', {...(formData.demandConfig || {}), lookbackHours: parseInt(e.target.value)})}>
                                                        <option value={1}>Last 1 Hour</option>
                                                        <option value={4}>Last 4 Hours</option>
                                                        <option value={12}>Last 12 Hours</option>
                                                        <option value={24}>Last 24 Hours</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label className="text-xs font-bold text-indigo-900 mb-1 block">Surge Threshold</label>
                                                    <div className="flex items-center gap-2">
                                                        <input type="number" className={inputClass} value={formData.demandConfig?.velocityThreshold || 5} onChange={e => updateField('demandConfig', {...(formData.demandConfig || {}), velocityThreshold: parseInt(e.target.value)})} />
                                                        <span className="text-xs text-indigo-700">bookings</span>
                                                    </div>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Bottom Row: Rounding & Limits */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {/* Rounding Rules */}
                        <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                            <h4 className="font-bold text-slate-800 mb-4 flex items-center gap-2">
                                <Hash size={18} className="text-slate-400"/> Rounding Rules
                            </h4>
                            <div className="space-y-4">
                                <div>
                                    <label className={labelClass}>Rounding Step</label>
                                    <select
                                        className={inputClass}
                                        value={formData.roundingValue}
                                        onChange={e => updateField('roundingValue', parseFloat(e.target.value))}
                                    >
                                        <option value={0.01}>0.01</option>
                                        <option value={0.05}>0.05</option>
                                        <option value={0.10}>0.10</option>
                                        <option value={0.50}>0.50</option>
                                        <option value={1.00}>1.00</option>
                                        <option value={5.00}>5.00</option>
                                        <option value={10.00}>10.00</option>
                                    </select>
                                </div>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className={labelClass}>Direction</label>
                                        <select
                                            className={inputClass}
                                            value={formData.roundingMethod}
                                            onChange={e => updateField('roundingMethod', e.target.value)}
                                        >
                                            <option value="Nearest">Nearest</option>
                                            <option value="Up">Up</option>
                                            <option value="Down">Down</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label className={labelClass}>Ending</label>
                                        <select
                                            className={inputClass}
                                            value={formData.priceEnding}
                                            onChange={e => updateField('priceEnding', e.target.value)}
                                        >
                                            <option value="None">None</option>
                                            <option value=".99">.99</option>
                                            <option value=".95">.95</option>
                                            <option value=".49">.49</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Safety Limits (Only for Demand/History) */}
                        {(formData.type === 'Demand' || formData.type === 'History') && (
                            <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                                <h4 className="font-bold text-slate-800 mb-4 flex items-center gap-2">
                                    <Shield size={18} className="text-slate-400"/> Safety Limits
                                </h4>
                                <div className="space-y-4">
                                    <div className="flex gap-4">
                                        <div className="flex-1">
                                            <label className={labelClass}>Max Decrease</label>
                                            <div className="relative">
                                                <span className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm font-bold">-</span>
                                                <input
                                                    type="number" min="0" className={`${inputClass} pl-6 pr-8`}
                                                    value={formData.maxDecreasePercent || 0}
                                                    onChange={e => updateField('maxDecreasePercent', parseFloat(e.target.value))}
                                                />
                                                <span className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm font-bold">%</span>
                                            </div>
                                        </div>
                                        <div className="flex-1">
                                            <label className={labelClass}>Max Increase</label>
                                            <div className="relative">
                                                <span className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm font-bold">+</span>
                                                <input
                                                    type="number" min="0" className={`${inputClass} pl-6 pr-8`}
                                                    value={formData.maxIncreasePercent || 0}
                                                    onChange={e => updateField('maxIncreasePercent', parseFloat(e.target.value))}
                                                />
                                                <span className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm font-bold">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    {(formData.maxDecreasePercent || 0) > (formData.maxIncreasePercent || 0) && (
                                        <p className="text-xs text-amber-600 flex items-center gap-1">
                                            <AlertCircle size={12}/> Warning: Decrease limit is higher than increase.
                                        </p>
                                    )}
                                    <p className="text-xs text-slate-400 mt-2">
                                        Hard limits prevent the algorithm from setting prices too high or too low regardless of demand.
                                    </p>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Linked Services */}
                    <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                        <h4 className="font-bold text-slate-800 mb-4 flex items-center gap-2">
                            <LinkIcon size={18} className="text-slate-400"/> Linked Offers
                        </h4>
                        <div className="space-y-2">
                            {getLinkedServices(formData.id).map(service => (
                                <div key={service.id} className="flex justify-between items-center p-3 border border-slate-100 rounded-lg hover:bg-slate-50">
                                    <span className="text-sm font-medium text-slate-700">{service.title}</span>
                                    {handleEditService && (
                                        <button
                                            onClick={() => handleEditService(service.id)}
                                            className="text-xs text-brand-600 hover:underline flex items-center gap-1"
                                        >
                                            Edit Offer <ExternalLink size={10}/>
                                        </button>
                                    )}
                                </div>
                            ))}
                            {getLinkedServices(formData.id).length === 0 && (
                                <p className="text-sm text-slate-400 italic">No services are currently using this strategy.</p>
                            )}
                        </div>
                    </div>

                </div>
            </div>
        </div>
    );
};

const SeasonalEditor: React.FC<{
    rules: SeasonalRule[];
    onChange: (rules: SeasonalRule[]) => void;
    currency: string;
}> = ({ rules, onChange, currency }) => {

    // Helper: Days of Week
    const daysOfWeek = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

    const handleAdd = (type: 'Range' | 'SpecificDate') => {
        const newRule: SeasonalRule = {
            id: `sr_${Date.now()}`,
            type,
            startDate: '', endDate: '', specificDate: '',
            name: type === 'Range' ? 'New Date Range' : 'New Specific Date',
            dayConfigs: {} // Initialize empty
        };
        onChange([...rules, newRule]);
    };

    const updateRule = (id: string, updates: Partial<SeasonalRule>) => {
        onChange(rules.map(r => r.id === id ? { ...r, ...updates } : r));
    };

    const deleteRule = (id: string) => {
        onChange(rules.filter(r => r.id !== id));
    };

    const toggleDay = (ruleId: string, day: string) => {
        const rule = rules.find(r => r.id === ruleId);
        if(!rule) return;

        const currentConfig = rule.dayConfigs?.[day];
        const newConfig: DaySchedule = currentConfig?.active
            ? { ...currentConfig, active: false }
            : { active: true, slots: currentConfig?.slots?.length ? currentConfig.slots : [{ startTime: '09:00', endTime: '18:00', adjustmentValue: 0, adjustmentType: 'Percentage' }] };

        updateRule(ruleId, {
            dayConfigs: { ...(rule.dayConfigs || {}), [day]: newConfig }
        });
    };

    const updateDaySlot = (ruleId: string, day: string, slotIdx: number, field: keyof TimeSlot, value: any) => {
        const rule = rules.find(r => r.id === ruleId);
        if (!rule || !rule.dayConfigs?.[day]) return;

        const slots = [...rule.dayConfigs[day].slots];
        slots[slotIdx] = { ...slots[slotIdx], [field]: value };

        updateRule(ruleId, {
            dayConfigs: { ...rule.dayConfigs, [day]: { ...rule.dayConfigs[day], slots } }
        });
    };

    const addDaySlot = (ruleId: string, day: string) => {
        const rule = rules.find(r => r.id === ruleId);
        if (!rule || !rule.dayConfigs?.[day]) return;

        updateRule(ruleId, {
            dayConfigs: {
                ...rule.dayConfigs,
                [day]: {
                    ...rule.dayConfigs[day],
                    slots: [...rule.dayConfigs[day].slots, { startTime: '12:00', endTime: '13:00', adjustmentValue: 0, adjustmentType: 'Percentage' }]
                }
            }
        });
    };

    const removeDaySlot = (ruleId: string, day: string, slotIdx: number) => {
        const rule = rules.find(r => r.id === ruleId);
        if (!rule || !rule.dayConfigs?.[day]) return;

        const slots = rule.dayConfigs[day].slots.filter((_, i) => i !== slotIdx);
        updateRule(ruleId, {
            dayConfigs: { ...rule.dayConfigs, [day]: { ...rule.dayConfigs[day], slots } }
        });
    };

    // Styling
    const inputClass = "border border-slate-300 rounded-lg px-2 py-1 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm";

    return (
        <div className="space-y-4">
            {rules.map((rule, idx) => (
                <div key={rule.id} className="bg-slate-50 border border-slate-200 rounded-lg p-4 relative group">
                    <button onClick={() => deleteRule(rule.id)} className="absolute top-4 right-4 text-slate-400 hover:text-rose-500"><Trash2 size={16}/></button>

                    {/* Header */}
                    <div className="mb-4 pr-8">
                        <label className="text-xs font-bold text-slate-500 block mb-1">Rule Name</label>
                        <input className={`${inputClass} w-full md:w-1/2 font-bold`} value={rule.name} onChange={e => updateRule(rule.id, { name: e.target.value })} placeholder="Rule Name" />
                    </div>

                    {rule.type === 'SpecificDate' ? (
                        <div className="flex gap-4 items-end flex-wrap">
                            <div>
                                <label className="text-xs font-bold text-slate-500 block mb-1">Date</label>
                                <input type="date" className={inputClass} value={rule.specificDate} onChange={e => updateRule(rule.id, { specificDate: e.target.value })} />
                            </div>
                            <div>
                                <label className="text-xs font-bold text-slate-500 block mb-1">Label</label>
                                <select className={inputClass} value={rule.labelType || 'SpecialDay'} onChange={e => updateRule(rule.id, { labelType: e.target.value as any })}>
                                    <option value="Holiday">Holiday (Red)</option>
                                    <option value="SpecialDay">Special Day (Green)</option>
                                </select>
                            </div>
                            <div>
                                <label className="text-xs font-bold text-slate-500 block mb-1">Adjustment</label>
                                <div className="flex gap-2">
                                    <input type="number" className={`${inputClass} w-20`} value={rule.adjustmentValue || 0} onChange={e => updateRule(rule.id, { adjustmentValue: parseFloat(e.target.value) })} />
                                    <select className={inputClass} value={rule.adjustmentType} onChange={e => updateRule(rule.id, { adjustmentType: e.target.value as any })}>
                                        <option value="Percentage">%</option>
                                        <option value="FixedAmount">{currency}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    ) : (
                        // DATE RANGE LOGIC
                        <div className="space-y-4">
                            <div className="flex gap-4 items-center">
                                <div>
                                    <label className="text-xs font-bold text-slate-500 block mb-1">Start Date</label>
                                    <input type="date" className={inputClass} value={rule.startDate} onChange={e => updateRule(rule.id, { startDate: e.target.value })} />
                                </div>
                                <div>
                                    <label className="text-xs font-bold text-slate-500 block mb-1">End Date</label>
                                    <input type="date" className={inputClass} value={rule.endDate} onChange={e => updateRule(rule.id, { endDate: e.target.value })} />
                                </div>
                            </div>

                            {/* Daily Config */}
                            <div className="space-y-2 mt-2">
                                <label className="text-xs font-bold text-slate-500 block mb-1">Daily Schedule & Adjustments</label>
                                <div className="space-y-2">
                                    {daysOfWeek.map(day => {
                                        const config = rule.dayConfigs?.[day];
                                        const isActive = config?.active;

                                        return (
                                            <div key={day} className={`border rounded-lg p-2 transition-colors ${isActive ? 'bg-white border-brand-200 shadow-sm' : 'bg-slate-100 border-slate-200 opacity-75'}`}>
                                                <div className="flex items-center gap-3">
                                                    <div className="flex items-center gap-2 w-20 shrink-0">
                                                        <input
                                                            type="checkbox"
                                                            checked={isActive || false}
                                                            onChange={() => toggleDay(rule.id, day)}
                                                            className="rounded text-brand-600 focus:ring-brand-500"
                                                        />
                                                        <span className={`font-bold text-sm ${isActive ? 'text-slate-800' : 'text-slate-500'}`}>{day}</span>
                                                    </div>

                                                    {/* Slots */}
                                                    {isActive && (
                                                        <div className="flex-1 space-y-2">
                                                            {config?.slots?.map((slot, sIdx) => (
                                                                <div key={sIdx} className="flex flex-wrap items-center gap-2 text-sm">
                                                                    <input type="time" className={inputClass} value={slot.startTime} onChange={e => updateDaySlot(rule.id, day, sIdx, 'startTime', e.target.value)} />
                                                                    <span className="text-slate-400">-</span>
                                                                    <input type="time" className={inputClass} value={slot.endTime} onChange={e => updateDaySlot(rule.id, day, sIdx, 'endTime', e.target.value)} />

                                                                    <div className="flex gap-1 items-center ml-2">
                                                                        <span className="text-xs text-slate-500 font-bold">Adj:</span>
                                                                        <input
                                                                            type="number"
                                                                            className={`${inputClass} w-20`}
                                                                            value={slot.adjustmentValue}
                                                                            onChange={e => updateDaySlot(rule.id, day, sIdx, 'adjustmentValue', parseFloat(e.target.value))}
                                                                        />
                                                                        <select
                                                                            className={inputClass}
                                                                            value={slot.adjustmentType}
                                                                            onChange={e => updateDaySlot(rule.id, day, sIdx, 'adjustmentType', e.target.value)}
                                                                        >
                                                                            <option value="Percentage">%</option>
                                                                            <option value="FixedAmount">{currency}</option>
                                                                        </select>
                                                                    </div>

                                                                    <button onClick={() => removeDaySlot(rule.id, day, sIdx)} className="text-slate-300 hover:text-rose-500 ml-auto">
                                                                        <X size={14}/>
                                                                    </button>
                                                                </div>
                                                            ))}
                                                            <button
                                                                onClick={() => addDaySlot(rule.id, day)}
                                                                className="text-xs text-brand-600 font-medium hover:underline flex items-center gap-1 mt-1"
                                                            >
                                                                <Plus size={12}/> Add Time Slot
                                                            </button>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            ))}

            <div className="flex gap-2">
                <button onClick={() => handleAdd('Range')} className="flex-1 py-2 bg-white border border-slate-300 rounded-lg text-sm font-medium hover:bg-slate-50">
                    + Add Date Range
                </button>
                <button onClick={() => handleAdd('SpecificDate')} className="flex-1 py-2 bg-white border border-slate-300 rounded-lg text-sm font-medium hover:bg-slate-50">
                    + Add Specific Date
                </button>
            </div>
        </div>
    );
};

export default DynamicPricingTab;
