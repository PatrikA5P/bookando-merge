import React, { useState } from 'react';
import {
    ArrowLeft, Save, Plus, Trash2, ChevronDown, ChevronUp, Settings, X, GripVertical,
    Layers, Star, Zap, BookOpen, CheckCircle
} from 'lucide-react';
import { useApp } from '../../../context/AppContext';
import { EducationCardTemplate, CardChapter } from '../../../types';

const EducationCardEditor: React.FC<{ 
    template: EducationCardTemplate; 
    onSave: (t: EducationCardTemplate) => void; 
    onBack: () => void 
}> = ({ template, onSave, onBack }) => {
    const [data, setData] = useState<EducationCardTemplate>(template);
    const [activeTab, setActiveTab] = useState<'structure' | 'config'>('structure');
    const { lessons, offerCategories, services } = useApp();
    
    // Lesson Import State
    const [importModalOpen, setImportModalOpen] = useState(false);
    const [targetChapterId, setTargetChapterId] = useState<string | null>(null);

    // Item Edit State
    const [editingItemId, setEditingItemId] = useState<string | null>(null);
    
    const handleManualSave = () => {
        onSave(data);
    };

    const updateField = (field: keyof EducationCardTemplate, value: any) => {
        setData({ ...data, [field]: value });
    };

    // --- Structure Logic ---
    const addChapter = () => {
        const newChapter: EducationChapter = { id: `c_${Date.now()}`, title: 'New Chapter', items: [] };
        setData({ ...data, chapters: [...data.chapters, newChapter] });
    };

    const updateChapterTitle = (id: string, title: string) => {
        setData({ ...data, chapters: data.chapters.map(c => c.id === id ? { ...c, title } : c) });
    };

    const deleteChapter = (id: string) => {
        if(confirm('Delete chapter?')) {
            setData({ ...data, chapters: data.chapters.filter(c => c.id !== id) });
        }
    };

    const addLessonToChapter = (chapterId: string) => {
        const newItem: EducationItem = { id: `i_${Date.now()}`, title: 'New Lesson', media: [] };
        setData({
            ...data,
            chapters: data.chapters.map(c => c.id === chapterId ? { ...c, items: [...c.items, newItem] } : c)
        });
        setEditingItemId(newItem.id);
    };

    const importLessonToChapter = (lesson: Lesson) => {
        if (!targetChapterId) return;
        const newItem: EducationItem = { 
            id: `i_${Date.now()}`, 
            title: lesson.title, 
            description: 'Imported from lessons',
            originalLessonId: lesson.id,
            media: lesson.mediaUrls?.map(url => ({ type: 'image', url, label: 'Resource' })) || []
        };
        setData({
            ...data,
            chapters: data.chapters.map(c => c.id === targetChapterId ? { ...c, items: [...c.items, newItem] } : c)
        });
        setImportModalOpen(false);
        setTargetChapterId(null);
    };

    const deleteItem = (chapterId: string, itemId: string) => {
        setData({
            ...data,
            chapters: data.chapters.map(c => c.id === chapterId ? { ...c, items: c.items.filter(i => i.id !== itemId) } : c)
        });
    };

    const updateItem = (itemId: string, changes: Partial<EducationItem>) => {
        // Need to find which chapter it belongs to first, or iterate all
        setData({
            ...data,
            chapters: data.chapters.map(c => ({
                ...c,
                items: c.items.map(i => i.id === itemId ? { ...i, ...changes } : i)
            }))
        });
    };

    const getEditingItem = () => {
        for (const c of data.chapters) {
            const item = c.items.find(i => i.id === editingItemId);
            if (item) return item;
        }
        return null;
    };

    const renderGradingPreview = () => {
        const { type, min, max } = data.grading;
        const elements = [];
        for (let i = min; i <= max; i++) {
            elements.push(i);
        }

        return (
            <div className="p-4 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-center gap-2">
                {type === 'buttons' && elements.map(v => (
                    <div key={v} className="w-10 h-10 rounded-lg border border-slate-300 bg-white flex items-center justify-center font-bold text-slate-700 shadow-sm">
                        {v}
                    </div>
                ))}
                {type === 'stars' && elements.map(v => (
                    <Star key={v} size={24} className="text-slate-300 fill-current" />
                ))}
                {type === 'slider' && (
                    <div className="w-full max-w-md">
                        <input type="range" min={min} max={max} className="w-full" disabled />
                        <div className="flex justify-between text-xs text-slate-500 mt-1">
                            <span>{min} ({data.grading.labels?.min || 'Min'})</span>
                            <span>{max} ({data.grading.labels?.max || 'Max'})</span>
                        </div>
                    </div>
                )}
            </div>
        );
    };

    return (
        <div className="flex flex-col h-full bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden animate-fadeIn">
            {/* Header */}
            <div className="flex items-center justify-between px-6 py-4 border-b border-slate-200 bg-slate-50">
                <div className="flex items-center gap-4">
                    <button onClick={onBack} className="p-2 hover:bg-white rounded-full text-slate-500 transition-colors border border-transparent hover:border-slate-200">
                        <ArrowLeft size={20} />
                    </button>
                    <div>
                        <input 
                            type="text" 
                            value={data.title}
                            onChange={(e) => updateField('title', e.target.value)}
                            className="bg-transparent text-xl font-bold text-slate-800 focus:outline-none focus:bg-white focus:ring-2 focus:ring-brand-500 rounded px-2 -ml-2"
                            placeholder="Education Plan Title"
                        />
                        <input 
                            type="text"
                            value={data.description}
                            onChange={(e) => updateField('description', e.target.value)}
                            className="block text-xs text-slate-500 bg-transparent border-none p-0 px-1 w-96 focus:ring-0"
                            placeholder="Add description..."
                        />
                    </div>
                </div>
                <button onClick={handleManualSave} className="flex items-center gap-2 bg-brand-600 text-white px-4 py-2 rounded-lg hover:bg-brand-700 transition-colors font-medium">
                    <Save size={18} /> Save Template
                </button>
            </div>

            {/* Tabs */}
            <div className="flex border-b border-slate-200 px-6">
                <button 
                    onClick={() => setActiveTab('structure')}
                    className={`px-4 py-3 text-sm font-medium border-b-2 transition-colors flex items-center gap-2 ${activeTab === 'structure' ? 'border-brand-600 text-brand-600' : 'border-transparent text-slate-500 hover:text-slate-700'}`}
                >
                    <List size={16} /> Structure & Content
                </button>
                <button 
                    onClick={() => setActiveTab('config')}
                    className={`px-4 py-3 text-sm font-medium border-b-2 transition-colors flex items-center gap-2 ${activeTab === 'config' ? 'border-brand-600 text-brand-600' : 'border-transparent text-slate-500 hover:text-slate-700'}`}
                >
                    <Settings size={16} /> Grading & Automation
                </button>
            </div>

            {/* Content */}
            <div className="flex-1 overflow-y-auto bg-slate-50/50 p-6">
                
                {activeTab === 'structure' && (
                    <div className="max-w-4xl mx-auto space-y-6">
                        {data.chapters.map(chapter => (
                            <div key={chapter.id} className="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                                <div className="p-3 bg-slate-50 border-b border-slate-100 flex items-center gap-2 group">
                                    <GripVertical size={16} className="text-slate-300 cursor-move" />
                                    <input 
                                        className="font-bold text-slate-800 bg-transparent border-none focus:ring-0 p-0 flex-1"
                                        value={chapter.title}
                                        onChange={(e) => updateChapterTitle(chapter.id, e.target.value)}
                                        placeholder="Chapter Title"
                                    />
                                    <div className="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button 
                                            onClick={() => { setTargetChapterId(chapter.id); setImportModalOpen(true); }}
                                            className="text-xs bg-white border border-slate-200 text-slate-600 px-2 py-1 rounded hover:text-brand-600"
                                        >
                                            Import Lesson
                                        </button>
                                        <button onClick={() => deleteChapter(chapter.id)} className="text-slate-400 hover:text-rose-600 p-1"><Trash2 size={16}/></button>
                                    </div>
                                </div>
                                <div className="p-3 space-y-2">
                                    {chapter.items.map(item => (
                                        <div key={item.id} className="flex items-center gap-3 p-3 border border-slate-100 rounded-lg hover:bg-slate-50 transition-colors group">
                                            <div className="p-2 bg-indigo-50 text-indigo-600 rounded-lg">
                                                <BookOpen size={16} />
                                            </div>
                                            <div className="flex-1">
                                                <div className="font-medium text-sm text-slate-700">{item.title}</div>
                                                {item.media.length > 0 && (
                                                    <div className="flex gap-2 mt-1">
                                                        {item.media.map((m, i) => (
                                                            <span key={i} className="text-[10px] bg-slate-100 text-slate-500 px-1.5 rounded border border-slate-200 flex items-center gap-1">
                                                                {m.type === 'video' ? <Video size={10}/> : <ImageIcon size={10}/>} {m.label}
                                                            </span>
                                                        ))}
                                                    </div>
                                                )}
                                            </div>
                                            <button 
                                                onClick={() => setEditingItemId(item.id)} 
                                                className="p-2 text-slate-400 hover:text-brand-600 hover:bg-white rounded-lg transition-colors"
                                                title="Edit Details"
                                            >
                                                <Edit2 size={16} />
                                            </button>
                                            <button onClick={() => deleteItem(chapter.id, item.id)} className="p-2 text-slate-400 hover:text-rose-600 hover:bg-white rounded-lg opacity-0 group-hover:opacity-100 transition-opacity">
                                                <Trash2 size={16} />
                                            </button>
                                        </div>
                                    ))}
                                    <button 
                                        onClick={() => addLessonToChapter(chapter.id)}
                                        className="w-full py-2 border-2 border-dashed border-slate-200 rounded-lg text-slate-400 text-xs font-bold hover:border-brand-300 hover:text-brand-600 flex justify-center items-center gap-1 transition-colors"
                                    >
                                        <Plus size={14} /> Add Lesson
                                    </button>
                                </div>
                            </div>
                        ))}
                        
                        <button onClick={addChapter} className="flex items-center gap-2 text-brand-600 font-medium hover:bg-brand-50 px-4 py-2 rounded-lg transition-colors mx-auto">
                            <Plus size={18} /> Add New Chapter
                        </button>
                    </div>
                )}

                {activeTab === 'config' && (
                    <div className="max-w-3xl mx-auto space-y-8">
                        {/* Grading Config */}
                        <section className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                            <h3 className="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                                <Star size={20} className="text-brand-600" /> Grading System
                            </h3>
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-1">Visual Style</label>
                                    <select 
                                        className="w-full border border-slate-300 rounded-lg px-3 py-2 bg-white"
                                        value={data.grading.type}
                                        onChange={(e) => setData({...data, grading: {...data.grading, type: e.target.value as any}})}
                                    >
                                        <option value="buttons">Buttons (Numbers)</option>
                                        <option value="slider">Slider</option>
                                        <option value="stars">Stars</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-1">Min Value</label>
                                    <input type="number" className="w-full border border-slate-300 rounded-lg px-3 py-2" value={data.grading.min} onChange={(e) => setData({...data, grading: {...data.grading, min: parseInt(e.target.value)}})} />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-1">Max Value</label>
                                    <input type="number" className="w-full border border-slate-300 rounded-lg px-3 py-2" value={data.grading.max} onChange={(e) => setData({...data, grading: {...data.grading, max: parseInt(e.target.value)}})} />
                                </div>
                            </div>
                            <div className="mb-2 text-sm font-medium text-slate-500">Preview</div>
                            {renderGradingPreview()}
                        </section>

                        {/* Automation Rules */}
                        <section className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                            <div className="flex justify-between items-center mb-4">
                                <h3 className="text-lg font-bold text-slate-800 flex items-center gap-2">
                                    <Zap size={20} className="text-amber-500" /> Automation Rules
                                </h3>
                                <label className="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" className="sr-only peer" checked={data.automation.enabled} onChange={(e) => setData({...data, automation: {...data.automation, enabled: e.target.checked}})} />
                                    <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                                    <span className="ml-3 text-sm font-medium text-slate-700">Enable Auto-Assign</span>
                                </label>
                            </div>
                            
                            {data.automation.enabled && (
                                <div className="space-y-4 p-4 bg-amber-50 rounded-lg border border-amber-100 animate-fadeIn">
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label className="block text-xs font-bold text-amber-800 uppercase mb-1">Trigger Type</label>
                                            <select 
                                                className="w-full border border-amber-200 rounded-lg px-3 py-2 bg-white focus:ring-amber-500"
                                                value={data.automation.triggerType}
                                                onChange={(e) => setData({...data, automation: {...data.automation, triggerType: e.target.value as any}})}
                                            >
                                                <option value="Service">Booked Service</option>
                                                <option value="Category">Booked Category</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label className="block text-xs font-bold text-amber-800 uppercase mb-1">
                                                {data.automation.triggerType === 'Service' ? 'Specific Service' : 'Category Name'}
                                            </label>
                                            <select 
                                                className="w-full border border-amber-200 rounded-lg px-3 py-2 bg-white focus:ring-amber-500"
                                                value={data.automation.triggerId}
                                                onChange={(e) => setData({...data, automation: {...data.automation, triggerId: e.target.value}})}
                                            >
                                                <option value="">Select...</option>
                                                {data.automation.triggerType === 'Service' 
                                                    ? services.map(s => <option key={s.id} value={s.id}>{s.title}</option>)
                                                    : offerCategories.map(c => <option key={c.id} value={c.name}>{c.name}</option>)
                                                }
                                            </select>
                                        </div>
                                    </div>
                                    <label className="flex items-center gap-2 cursor-pointer pt-2">
                                        <input 
                                            type="checkbox" 
                                            className="rounded text-amber-600 focus:ring-amber-500" 
                                            checked={data.automation.allowMultiple}
                                            onChange={(e) => setData({...data, automation: {...data.automation, allowMultiple: e.target.checked}})}
                                        />
                                        <span className="text-sm text-amber-900">Allow assigning multiple cards to the same customer (e.g. for re-training)</span>
                                    </label>
                                </div>
                            )}
                        </section>
                    </div>
                )}
            </div>

            {/* Modals */}
            {importModalOpen && (
                <div className="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-[60] p-4">
                    <div className="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
                        <h3 className="font-bold text-lg mb-4">Import Lesson</h3>
                        <div className="max-h-60 overflow-y-auto border border-slate-200 rounded-lg mb-4">
                            {lessons.map(l => (
                                <button 
                                    key={l.id} 
                                    onClick={() => importLessonToChapter(l)}
                                    className="w-full text-left p-3 hover:bg-slate-50 border-b border-slate-100 last:border-0 flex justify-between items-center"
                                >
                                    <span className="text-sm font-medium text-slate-700">{l.title}</span>
                                    <Plus size={14} className="text-slate-400" />
                                </button>
                            ))}
                        </div>
                        <button onClick={() => setImportModalOpen(false)} className="w-full py-2 border border-slate-300 rounded-lg text-slate-600 text-sm font-medium hover:bg-slate-50">Cancel</button>
                    </div>
                </div>
            )}

            {editingItemId && (
                <div className="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-[60] p-4">
                    <div className="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6 animate-slideUp">
                        <div className="flex justify-between items-center mb-4">
                            <h3 className="font-bold text-lg">Edit Lesson Details</h3>
                            <button onClick={() => setEditingItemId(null)}><X size={20} className="text-slate-400" /></button>
                        </div>
                        
                        {(() => {
                            const item = getEditingItem();
                            if (!item) return null;
                            
                            const addMedia = (type: 'image' | 'video') => {
                                const url = prompt(`${type === 'image' ? 'Image' : 'Video'} URL:`);
                                const label = prompt("Label / Caption:");
                                if(url && label) {
                                    updateItem(item.id, { media: [...item.media, { type, url, label }] });
                                }
                            };

                            const removeMedia = (index: number) => {
                                updateItem(item.id, { media: item.media.filter((_, i) => i !== index) });
                            }

                            return (
                                <div className="space-y-4">
                                    <div>
                                        <label className="block text-sm font-medium text-slate-700 mb-1">Lesson Title</label>
                                        <input 
                                            className="w-full border border-slate-300 rounded-lg px-3 py-2"
                                            value={item.title}
                                            onChange={(e) => updateItem(item.id, { title: e.target.value })}
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-slate-700 mb-1">Description / Notes</label>
                                        <textarea 
                                            className="w-full border border-slate-300 rounded-lg px-3 py-2 h-24 text-sm"
                                            value={item.description || ''}
                                            onChange={(e) => updateItem(item.id, { description: e.target.value })}
                                            placeholder="Instructions for the instructor..."
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-slate-700 mb-2">Attached Media</label>
                                        <div className="space-y-2 mb-3">
                                            {item.media.map((m, i) => (
                                                <div key={i} className="flex items-center justify-between p-2 bg-slate-50 rounded border border-slate-200">
                                                    <div className="flex items-center gap-2 text-sm">
                                                        {m.type === 'video' ? <Video size={14} className="text-purple-500"/> : <ImageIcon size={14} className="text-blue-500"/>}
                                                        <span className="font-medium">{m.label}</span>
                                                        <span className="text-xs text-slate-400 truncate max-w-[150px]">{m.url}</span>
                                                    </div>
                                                    <button onClick={() => removeMedia(i)} className="text-rose-500 hover:text-rose-700"><Trash2 size={14}/></button>
                                                </div>
                                            ))}
                                            {item.media.length === 0 && <p className="text-xs text-slate-400 italic">No media attached.</p>}
                                        </div>
                                        <div className="flex gap-2">
                                            <button onClick={() => addMedia('image')} className="flex-1 py-2 bg-white border border-slate-300 hover:bg-slate-50 rounded text-xs font-medium flex items-center justify-center gap-1">
                                                <ImageIcon size={14} /> Add Image
                                            </button>
                                            <button onClick={() => addMedia('video')} className="flex-1 py-2 bg-white border border-slate-300 hover:bg-slate-50 rounded text-xs font-medium flex items-center justify-center gap-1">
                                                <Video size={14} /> Add Video
                                            </button>
                                        </div>
                                    </div>
                                    <div className="pt-4 border-t border-slate-100 text-right">
                                        <button onClick={() => setEditingItemId(null)} className="bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-medium">Done</button>
                                    </div>
                                </div>
                            );
                        })()}
                    </div>
                </div>
            )}
        </div>
    );
};


export default EducationCardEditor;
