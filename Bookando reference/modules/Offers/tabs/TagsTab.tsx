import React, { useState } from 'react';
import { Trash2 } from 'lucide-react';
import { useApp } from '../../../context/AppContext';

interface TagsTabProps {
  createTrigger?: number;
}

const TagsTab: React.FC<TagsTabProps> = ({ createTrigger }) => {
    const { offerTags, addOfferTag, deleteOfferTag } = useApp();
    const [newTag, setNewTag] = useState('');
    const [newColor, setNewColor] = useState('bg-slate-100 text-slate-700');

    // Color presets
    const colors = [
        { label: 'Slate', val: 'bg-slate-100 text-slate-700' },
        { label: 'Blue', val: 'bg-blue-100 text-blue-700' },
        { label: 'Green', val: 'bg-emerald-100 text-emerald-700' },
        { label: 'Red', val: 'bg-rose-100 text-rose-700' },
        { label: 'Purple', val: 'bg-purple-100 text-purple-700' },
        { label: 'Amber', val: 'bg-amber-100 text-amber-700' },
    ];

    const handleAdd = () => {
        if (newTag) {
            addOfferTag({ id: Date.now().toString(), name: newTag, color: newColor });
            setNewTag('');
        }
    };

    return (
        <div className="flex-1 p-6 animate-fadeIn">
            <div className="max-w-4xl mx-auto">
                <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm mb-8">
                    <h3 className="font-bold text-slate-800 mb-4">Create New Tag</h3>
                    <div className="flex gap-4 items-end">
                        <div className="flex-1">
                            <label className="block text-sm font-medium text-slate-700 mb-1">Tag Name</label>
                            <input
                                className="w-full border border-slate-300 rounded-lg px-3 py-2"
                                value={newTag}
                                onChange={e => setNewTag(e.target.value)}
                                placeholder="e.g. Best Seller"
                            />
                        </div>
                        <div className="flex-1">
                            <label className="block text-sm font-medium text-slate-700 mb-1">Color Style</label>
                            <div className="flex gap-2">
                                {colors.map(c => (
                                    <button
                                        key={c.label}
                                        onClick={() => setNewColor(c.val)}
                                        className={`w-8 h-8 rounded-full ${c.val} border-2 ${newColor === c.val ? 'border-slate-400' : 'border-transparent'}`}
                                        title={c.label}
                                    />
                                ))}
                            </div>
                        </div>
                        <button onClick={handleAdd} className="bg-brand-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-brand-700">Add</button>
                    </div>
                </div>

                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    {offerTags.map(tag => (
                        <div key={tag.id} className="flex items-center justify-between p-3 bg-white border border-slate-200 rounded-lg shadow-sm">
                            <span className={`px-2 py-1 rounded text-sm font-medium ${tag.color}`}>{tag.name}</span>
                            <button onClick={() => deleteOfferTag(tag.id)} className="text-slate-400 hover:text-rose-500"><Trash2 size={16}/></button>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
};

export default TagsTab;
