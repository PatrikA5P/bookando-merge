import React, { useState, useRef, useEffect } from 'react';
import { Plus, Trash2 } from 'lucide-react';
import { useApp } from '../../../context/AppContext';
import { OfferExtra } from '../../../types';

interface ExtrasTabProps {
    createTrigger?: number;
}

const ExtrasTab: React.FC<ExtrasTabProps> = ({ createTrigger }) => {
    const { offerExtras, addOfferExtra, deleteOfferExtra, systemCurrency } = useApp();
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [form, setForm] = useState<OfferExtra>({ id: '', name: '', price: 0, priceType: 'Fixed', currency: systemCurrency });

    const previousTrigger = useRef<number | undefined>(createTrigger);

    useEffect(() => {
        if (createTrigger !== undefined && previousTrigger.current !== createTrigger) {
            setForm({ id: '', name: '', price: 0, priceType: 'Fixed', currency: systemCurrency });
            setIsModalOpen(true);
        }
        previousTrigger.current = createTrigger;
    }, [createTrigger, systemCurrency]);

    const handleSave = () => {
        if (form.name) {
            addOfferExtra({ ...form, id: Date.now().toString() });
            setIsModalOpen(false);
        }
    };

    return (
        <div className="flex-1 p-6 animate-fadeIn">
            <div className="space-y-4 max-w-4xl mx-auto">
                {offerExtras.map(extra => (
                    <div key={extra.id} className="flex items-center justify-between p-4 bg-white border border-slate-200 rounded-xl shadow-sm hover:shadow-md transition-all">
                        <div className="flex items-center gap-4">
                            <div className="p-3 bg-emerald-50 text-emerald-600 rounded-lg">
                                <Plus size={20} />
                            </div>
                            <div>
                                <h4 className="font-bold text-slate-800">{extra.name}</h4>
                                <p className="text-sm text-slate-500">{extra.description || 'No description'}</p>
                            </div>
                        </div>
                        <div className="flex items-center gap-6">
                            <span className="font-mono font-medium text-slate-700 bg-slate-100 px-3 py-1 rounded-lg">
                                + {extra.price} {extra.priceType === 'Percentage' ? '%' : extra.currency}
                            </span>
                            <button onClick={() => deleteOfferExtra(extra.id)} className="text-slate-400 hover:text-rose-500"><Trash2 size={18} /></button>
                        </div>
                    </div>
                ))}

                <button
                    onClick={() => { setForm({ id: '', name: '', price: 0, priceType: 'Fixed', currency: systemCurrency }); setIsModalOpen(true); }}
                    className="w-full py-4 border-2 border-dashed border-slate-300 rounded-xl text-slate-500 font-medium hover:border-brand-400 hover:text-brand-600 transition-colors flex items-center justify-center gap-2"
                >
                    <Plus size={20} /> Add New Extra
                </button>
            </div>

            {isModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
                    <div className="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
                        <h3 className="font-bold text-lg mb-4">Add Upsell Item</h3>
                        <div className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">Item Name</label>
                                <input className="w-full border border-slate-300 rounded-lg px-3 py-2" value={form.name} onChange={e => setForm({...form, name: e.target.value})} placeholder="e.g. Towel Rental" />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">Description</label>
                                <input className="w-full border border-slate-300 rounded-lg px-3 py-2" value={form.description || ''} onChange={e => setForm({...form, description: e.target.value})} />
                            </div>
                            <div className="flex gap-4">
                                <div className="flex-1">
                                    <label className="block text-sm font-medium text-slate-700 mb-1">Price Type</label>
                                    <select className="w-full border border-slate-300 rounded-lg px-3 py-2 bg-white" value={form.priceType} onChange={e => setForm({...form, priceType: e.target.value as any})}>
                                        <option value="Fixed">Fixed Amount</option>
                                        <option value="Percentage">Percentage</option>
                                    </select>
                                </div>
                                <div className="flex-1">
                                    <label className="block text-sm font-medium text-slate-700 mb-1">Value ({form.priceType === 'Percentage' ? '%' : systemCurrency})</label>
                                    <input type="number" className="w-full border border-slate-300 rounded-lg px-3 py-2" value={form.price} onChange={e => setForm({...form, price: parseFloat(e.target.value)})} />
                                </div>
                            </div>
                        </div>
                        <div className="mt-6 flex justify-end gap-2">
                            <button onClick={() => setIsModalOpen(false)} className="px-4 py-2 text-slate-600 hover:bg-slate-50 rounded-lg">Cancel</button>
                            <button onClick={handleSave} className="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700">Save</button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

export default ExtrasTab;
