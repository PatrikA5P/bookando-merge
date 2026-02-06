import React from 'react';
import { Check, Trash2 } from 'lucide-react';
import { BundleItem } from '../../../types';

interface BundlesTabProps {
    bundles: BundleItem[];
    onEdit: (bundle: BundleItem) => void;
    onDelete: (id: string) => void;
}

const BundlesTab: React.FC<BundlesTabProps> = ({ bundles, onEdit, onDelete }) => {
    return (
        <div className="flex-1 overflow-y-auto p-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 animate-fadeIn">
                {bundles.map((bundle) => (
                    <div key={bundle.id} className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex flex-col group hover:shadow-md transition-shadow">
                        <div className="h-40 relative overflow-hidden">
                            <img src={bundle.image} alt={bundle.title} className="w-full h-full object-cover" />
                            <div className="absolute top-3 right-3 flex gap-2">
                                <div className="bg-emerald-500 text-white text-xs font-bold px-2 py-1 rounded shadow-sm">SAVE {bundle.savings}%</div>
                            </div>
                        </div>
                        <div className="p-6 flex-1 flex flex-col">
                            <div className="flex justify-between items-start mb-4">
                                <h3 className="font-bold text-lg text-slate-800 leading-tight">{bundle.title}</h3>
                                <div className="text-right shrink-0 ml-4">
                                    <div className="text-xl font-bold text-slate-900">${bundle.price}</div>
                                    <div className="text-sm text-slate-400 line-through">${bundle.originalPrice}</div>
                                </div>
                            </div>
                            <div className="space-y-2 mb-6 flex-1">
                                <p className="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Includes:</p>
                                {bundle.items.map((item, idx) => (
                                    <div key={idx} className="flex items-start gap-2 text-sm text-slate-700">
                                        <Check size={16} className="text-emerald-500 mt-0.5 shrink-0" />
                                        <span className="leading-snug">{item}</span>
                                    </div>
                                ))}
                            </div>
                            <div className="flex gap-3">
                                <button onClick={() => onEdit(bundle)} className="flex-1 py-2 border border-slate-200 rounded-lg text-sm font-medium text-slate-600 hover:text-brand-600 hover:border-brand-200 hover:bg-brand-50 transition-all">Edit Bundle</button>
                                <button onClick={() => onDelete(bundle.id)} className="p-2 border border-slate-200 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50"><Trash2 size={18} /></button>
                            </div>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
};

export default BundlesTab;
