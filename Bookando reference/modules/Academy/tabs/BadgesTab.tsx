
import React, { useState } from 'react';
import { Plus, Trash2, Award, Shield, Check, Activity, Heart, CreditCard } from 'lucide-react';
import { useApp } from '../../../context/AppContext';
import { Badge } from '../../../types';

const BadgesTab: React.FC = () => {
    const { badges, addBadge, deleteBadge } = useApp();
    const [isAdding, setIsAdding] = useState(false);
    const [newBadge, setNewBadge] = useState<Partial<Badge>>({ name: '', icon: 'Shield', color: 'bg-indigo-100 text-indigo-800' });

    const handleSave = () => {
        if (!newBadge.name) return;
        addBadge({
            id: `b_${Date.now()}`,
            name: newBadge.name,
            icon: newBadge.icon || 'Shield',
            color: newBadge.color || 'bg-slate-100 text-slate-800',
            description: newBadge.description
        });
        setNewBadge({ name: '', icon: 'Shield', color: 'bg-indigo-100 text-indigo-800' });
        setIsAdding(false);
    };

    return (
        <div className="space-y-6 animate-fadeIn">
            <div className="flex justify-between items-center">
                <h2 className="text-xl font-bold text-slate-800">Badges & Education Cards</h2>
                <button 
                    onClick={() => setIsAdding(true)} 
                    className="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg font-medium flex items-center gap-2"
                >
                    <Plus size={18} /> Create Badge
                </button>
            </div>

            {isAdding && (
                <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm mb-6">
                    <h4 className="font-bold text-slate-800 mb-4">New Badge Details</h4>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">Badge Name</label>
                            <input 
                                className="w-full border border-slate-300 rounded-lg px-3 py-2" 
                                value={newBadge.name} 
                                onChange={e => setNewBadge({...newBadge, name: e.target.value})} 
                                placeholder="e.g. Safety Level 1"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">Style</label>
                            <select 
                                className="w-full border border-slate-300 rounded-lg px-3 py-2" 
                                value={newBadge.color}
                                onChange={e => setNewBadge({...newBadge, color: e.target.value})}
                            >
                                <option value="bg-slate-100 text-slate-800">Slate (Default)</option>
                                <option value="bg-brand-100 text-brand-800">Brand Blue</option>
                                <option value="bg-emerald-100 text-emerald-800">Success Green</option>
                                <option value="bg-amber-100 text-amber-800">Warning Amber</option>
                                <option value="bg-rose-100 text-rose-800">Danger Red</option>
                                <option value="bg-purple-100 text-purple-800">Royal Purple</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">Icon</label>
                            <select 
                                className="w-full border border-slate-300 rounded-lg px-3 py-2" 
                                value={newBadge.icon}
                                onChange={e => setNewBadge({...newBadge, icon: e.target.value})}
                            >
                                <option value="Shield">Shield</option>
                                <option value="Award">Award</option>
                                <option value="Check">Check</option>
                                <option value="Activity">Activity</option>
                                <option value="Heart">Heart</option>
                            </select>
                        </div>
                    </div>
                    <div className="flex justify-end gap-2">
                        <button onClick={() => setIsAdding(false)} className="px-4 py-2 text-slate-600 hover:bg-slate-50 rounded-lg">Cancel</button>
                        <button onClick={handleSave} className="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700">Save Badge</button>
                    </div>
                </div>
            )}

            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                {badges.map(badge => (
                    <div key={badge.id} className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow flex items-center justify-between group">
                        <div className="flex items-center gap-3">
                            <div className={`p-2 rounded-full ${badge.color}`}>
                                {badge.icon === 'Shield' && <Shield size={20} />}
                                {badge.icon === 'Award' && <Award size={20} />}
                                {badge.icon === 'Check' && <Check size={20} />}
                                {badge.icon === 'Activity' && <Activity size={20} />}
                                {badge.icon === 'Heart' && <Heart size={20} />}
                            </div>
                            <span className="font-medium text-slate-800">{badge.name}</span>
                        </div>
                        <button onClick={() => deleteBadge(badge.id)} className="text-slate-300 hover:text-rose-500 opacity-0 group-hover:opacity-100 transition-opacity">
                            <Trash2 size={16} />
                        </button>
                    </div>
                ))}
            </div>

            {/* Education Cards Preview Section */}
            <div className="mt-8 pt-8 border-t border-slate-200">
                <h3 className="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <CreditCard size={20} /> Education Cards Preview
                </h3>
                <div className="p-6 bg-slate-50 rounded-xl border border-slate-200 grid grid-cols-1 md:grid-cols-2 gap-6">
                    {/* Card 1 */}
                    <div className="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden flex flex-col">
                        <div className="h-2 bg-brand-500"></div>
                        <div className="p-5">
                            <div className="flex justify-between items-start mb-4">
                                <div>
                                    <h4 className="font-bold text-lg text-slate-800">Certified Safety Instructor</h4>
                                    <p className="text-sm text-slate-500">Level 2 Qualification</p>
                                </div>
                                <div className="w-10 h-10 bg-brand-50 text-brand-600 rounded-full flex items-center justify-center">
                                    <Award size={20} />
                                </div>
                            </div>
                            <div className="space-y-3">
                                <p className="text-xs font-bold text-slate-400 uppercase">Required Badges</p>
                                <div className="flex flex-wrap gap-2">
                                    {badges.slice(0,3).map(b => (
                                        <span key={b.id} className={`text-xs px-2 py-1 rounded border ${b.color.replace('bg-', 'border-').replace('text-', 'text-opacity-80 ')} bg-white`}>
                                            {b.name}
                                        </span>
                                    ))}
                                </div>
                            </div>
                        </div>
                        <div className="bg-slate-50 p-3 border-t border-slate-100 text-xs text-center text-slate-500 font-mono">
                            ID: CARD-SAF-02
                        </div>
                    </div>

                    {/* Card 2 */}
                    <div className="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden flex flex-col">
                        <div className="h-2 bg-emerald-500"></div>
                        <div className="p-5">
                            <div className="flex justify-between items-start mb-4">
                                <div>
                                    <h4 className="font-bold text-lg text-slate-800">Master Yogi</h4>
                                    <p className="text-sm text-slate-500">Advanced Practitioner</p>
                                </div>
                                <div className="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center">
                                    <Activity size={20} />
                                </div>
                            </div>
                            <div className="space-y-3">
                                <p className="text-xs font-bold text-slate-400 uppercase">Required Badges</p>
                                <div className="flex flex-wrap gap-2">
                                    {badges.slice(1,2).map(b => (
                                        <span key={b.id} className={`text-xs px-2 py-1 rounded border ${b.color.replace('bg-', 'border-').replace('text-', 'text-opacity-80 ')} bg-white`}>
                                            {b.name}
                                        </span>
                                    ))}
                                </div>
                            </div>
                        </div>
                        <div className="bg-slate-50 p-3 border-t border-slate-100 text-xs text-center text-slate-500 font-mono">
                            ID: CARD-YOG-09
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default BadgesTab;
