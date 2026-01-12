import React, { useState } from 'react';
import { Plus, Trash2, X, Folder } from 'lucide-react';
import { LessonGroup } from '../../../types';

const GroupManagerModal: React.FC<{ 
    groups: LessonGroup[]; 
    onAdd: (title: string) => void; 
    onDelete: (id: string) => void; 
    onClose: () => void 
}> = ({ groups, onAdd, onDelete, onClose }) => {
    const [newGroupTitle, setNewGroupTitle] = useState('');

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (newGroupTitle.trim()) {
            onAdd(newGroupTitle);
            setNewGroupTitle('');
        }
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
            <div className="bg-white rounded-xl shadow-2xl w-full max-w-md animate-fadeIn flex flex-col max-h-[80vh]">
                <div className="p-6 border-b border-slate-100 flex justify-between items-center">
                    <h3 className="font-bold text-lg text-slate-800">Manage Lesson Groups</h3>
                    <button onClick={onClose}><X size={20} className="text-slate-400 hover:text-slate-600"/></button>
                </div>
                
                <div className="flex-1 overflow-y-auto p-6 bg-slate-50 space-y-2">
                    {groups.length === 0 && <p className="text-center text-slate-400 text-sm py-4">No groups created yet.</p>}
                    {groups.map(g => (
                        <div key={g.id} className="flex items-center justify-between p-3 bg-white border border-slate-200 rounded-lg shadow-sm">
                            <div className="flex items-center gap-3">
                                <Folder size={18} className="text-indigo-500" />
                                <span className="text-sm font-medium text-slate-700">{g.title}</span>
                            </div>
                            <button 
                                onClick={() => onDelete(g.id)} 
                                className="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded transition-colors"
                            >
                                <Trash2 size={16} />
                            </button>
                        </div>
                    ))}
                </div>

                <form onSubmit={handleSubmit} className="p-4 border-t border-slate-100 bg-white rounded-b-xl flex gap-2">
                    <input 
                        className="flex-1 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                        placeholder="New group name..."
                        value={newGroupTitle}
                        onChange={e => setNewGroupTitle(e.target.value)}
                        autoFocus
                    />
                    <button 
                        type="submit" 
                        disabled={!newGroupTitle.trim()}
                        className="px-4 py-2 bg-brand-600 disabled:bg-slate-300 text-white rounded-lg text-sm font-medium hover:bg-brand-700 transition-colors"
                    >
                        Add
                    </button>
                </form>
            </div>
        </div>
    );
}

// --- REFINED LESSON EDITOR ---


export default GroupManagerModal;
