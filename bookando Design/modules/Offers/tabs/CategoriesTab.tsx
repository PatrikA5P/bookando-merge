import React, { useState, useRef, useEffect } from 'react';
import { Plus, Edit2, Trash2, Layers, X, Image as ImageIcon } from 'lucide-react';
import { useApp } from '../../../context/AppContext';
import { OfferCategory } from '../../../types';

interface CategoriesTabProps {
  createTrigger?: number;
  searchTerm?: string;
}

const CategoriesTab: React.FC<CategoriesTabProps> = ({ createTrigger, searchTerm = '' }) => {
    const { offerCategories, addOfferCategory, updateOfferCategory, deleteOfferCategory, services, setServices } = useApp();
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingCategory, setEditingCategory] = useState<OfferCategory | null>(null);
    const [viewUsageFor, setViewUsageFor] = useState<OfferCategory | null>(null);

    const previousTrigger = useRef<number | undefined>(createTrigger);

    useEffect(() => {
        if (createTrigger !== undefined && previousTrigger.current !== createTrigger) {
            setEditingCategory(null);
            setIsModalOpen(true);
        }
        previousTrigger.current = createTrigger;
    }, [createTrigger]);

    const handleCreate = () => {
        const newCat: OfferCategory = {
            id: `cat_${Date.now()}`,
            name: '',
            color: '#3b82f6',
            image: ''
        };
        setEditingCategory(newCat);
        setIsModalOpen(true);
    };

    const handleSave = () => {
        if (!editingCategory?.name) return;
        if (offerCategories.find(c => c.id === editingCategory.id)) {
            updateOfferCategory(editingCategory);
        } else {
            addOfferCategory(editingCategory);
        }
        setIsModalOpen(false);
    };

    const getUsageCount = (catName: string) => {
        return services.filter(s => s.category === catName || (s.categories && s.categories.includes(catName))).length;
    };

    const filteredCategories = offerCategories.filter(c => c.name.toLowerCase().includes(searchTerm.toLowerCase()));

    // Logic to reassign services from one category to another
    const handleReassign = (serviceId: string, oldCatName: string, newCatId: string) => {
        const newCategory = offerCategories.find(c => c.id === newCatId);
        if (!newCategory) return;

        const updatedServices = services.map(s => {
            if (s.id === serviceId) {
                let newCats = s.categories || [];
                if (newCats.includes(oldCatName)) {
                    newCats = newCats.map(c => c === oldCatName ? newCategory.name : c);
                }
                return { ...s, categories: newCats, category: newCategory.name }; // Update primary too if matches
            }
            return s;
        });
        setServices(updatedServices);
    };

    return (
        <div className="flex-1 flex flex-col h-full animate-fadeIn p-6 overflow-y-auto">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                {filteredCategories.map(cat => (
                    <div key={cat.id} className="border border-slate-200 rounded-xl overflow-hidden group hover:shadow-md transition-all bg-white flex flex-col">
                        <div className="h-32 overflow-hidden relative bg-slate-100">
                            {cat.image ? (
                                <img src={cat.image} className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt={cat.name} />
                            ) : (
                                <div className="w-full h-full flex items-center justify-center text-slate-300">
                                    <ImageIcon size={32} />
                                </div>
                            )}
                            <div className="absolute top-0 left-0 w-full h-1" style={{ backgroundColor: cat.color }}></div>
                        </div>
                        <div className="p-4 flex-1 flex flex-col">
                            <div className="flex justify-between items-start mb-2">
                                <h4 className="font-bold text-slate-800 text-lg">{cat.name}</h4>
                                <button onClick={() => { setEditingCategory(cat); setIsModalOpen(true); }} className="p-2 text-slate-400 hover:text-brand-600 hover:bg-slate-50 rounded-lg">
                                    <Edit2 size={16} />
                                </button>
                            </div>
                            <div className="mt-auto pt-4 border-t border-slate-50 flex justify-between items-center">
                                <button
                                    onClick={() => setViewUsageFor(cat)}
                                    className="text-xs font-medium text-slate-500 hover:text-brand-600 flex items-center gap-1 bg-slate-50 px-2 py-1 rounded"
                                >
                                    <Layers size={12} /> {getUsageCount(cat.name)} Offers
                                </button>
                                <button onClick={() => deleteOfferCategory(cat.id)} className="text-slate-300 hover:text-rose-500 p-1">
                                    <Trash2 size={14} />
                                </button>
                            </div>
                        </div>
                    </div>
                ))}

                {/* Add Card */}
                <button
                    onClick={handleCreate}
                    className="border-2 border-dashed border-slate-200 rounded-xl p-6 flex flex-col items-center justify-center text-slate-400 hover:border-brand-300 hover:text-brand-600 hover:bg-slate-50 transition-all min-h-[200px]"
                >
                    <div className="w-12 h-12 rounded-full bg-slate-50 flex items-center justify-center mb-3 group-hover:bg-brand-50">
                        <Plus size={24} />
                    </div>
                    <span className="font-medium">Create Category</span>
                </button>
            </div>

            {/* Edit Modal */}
            {isModalOpen && editingCategory && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
                    <div className="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
                        <h3 className="font-bold text-lg mb-4">{editingCategory.id.startsWith('cat_') ? 'New Category' : 'Edit Category'}</h3>
                        <div className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">Name</label>
                                <input className="w-full border border-slate-300 rounded-lg px-3 py-2" value={editingCategory.name} onChange={e => setEditingCategory({...editingCategory, name: e.target.value})} />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">Color</label>
                                <div className="flex gap-2">
                                    <input type="color" className="h-10 w-10 border-0 p-0 rounded cursor-pointer" value={editingCategory.color} onChange={e => setEditingCategory({...editingCategory, color: e.target.value})} />
                                    <input type="text" className="flex-1 border border-slate-300 rounded-lg px-3 py-2 font-mono text-sm" value={editingCategory.color} onChange={e => setEditingCategory({...editingCategory, color: e.target.value})} />
                                </div>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">Image URL</label>
                                <input className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" value={editingCategory.image || ''} onChange={e => setEditingCategory({...editingCategory, image: e.target.value})} placeholder="https://..." />
                            </div>
                        </div>
                        <div className="mt-6 flex justify-end gap-2">
                            <button onClick={() => setIsModalOpen(false)} className="px-4 py-2 text-slate-600 hover:bg-slate-50 rounded-lg">Cancel</button>
                            <button onClick={handleSave} className="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700">Save</button>
                        </div>
                    </div>
                </div>
            )}

            {/* Usage Manager Modal */}
            {viewUsageFor && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
                    <div className="bg-white rounded-xl shadow-2xl w-full max-w-2xl p-6 max-h-[80vh] flex flex-col">
                        <div className="flex justify-between items-center mb-4">
                            <h3 className="font-bold text-lg">Offers in "{viewUsageFor.name}"</h3>
                            <button onClick={() => setViewUsageFor(null)}><X size={20} className="text-slate-400" /></button>
                        </div>
                        <div className="flex-1 overflow-y-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="bg-slate-50 text-slate-500 font-medium">
                                    <tr>
                                        <th className="p-3">Offer Name</th>
                                        <th className="p-3">Action</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {services.filter(s => s.category === viewUsageFor.name || s.categories?.includes(viewUsageFor.name)).map(s => (
                                        <tr key={s.id}>
                                            <td className="p-3 font-medium">{s.title}</td>
                                            <td className="p-3">
                                                <select
                                                    className="border border-slate-300 rounded px-2 py-1 text-xs"
                                                    onChange={(e) => {
                                                        if (e.target.value) handleReassign(s.id, viewUsageFor.name, e.target.value);
                                                    }}
                                                    value=""
                                                >
                                                    <option value="">Move to...</option>
                                                    {offerCategories.filter(c => c.id !== viewUsageFor.id).map(c => (
                                                        <option key={c.id} value={c.id}>{c.name}</option>
                                                    ))}
                                                </select>
                                            </td>
                                        </tr>
                                    ))}
                                    {services.filter(s => s.category === viewUsageFor.name).length === 0 && (
                                        <tr><td colSpan={2} className="p-4 text-center text-slate-400">No offers currently in this category.</td></tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

export default CategoriesTab;
