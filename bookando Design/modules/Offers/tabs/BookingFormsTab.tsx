import React, { useState } from 'react';
import {
    Plus, Trash2, Edit2, X, LinkIcon, ExternalLink, ArrowLeft, Save,
    LayoutTemplate, Type, AlignLeft, ChevronDown, Disc, CheckSquare as CheckSquareIcon,
    Paperclip, MousePointer2, ArrowUpRight, Info, Maximize, Columns, Grid as GridIcon
} from 'lucide-react';
import { useApp } from '../../../context/AppContext';
import { FormTemplate, FormElement, FormElementType, FormElementSource } from '../../../types';

interface BookingFormsTabProps {
    onOpenService: (id: string) => void;
}

const BookingFormsTab: React.FC<BookingFormsTabProps> = ({ onOpenService }) => {
    const { bookingFormTemplates, addFormTemplate, updateFormTemplate, deleteFormTemplate, customerFieldDefinitions, services } = useApp();
    const [view, setView] = useState<'list' | 'editor'>('list');
    const [usageModalOpen, setUsageModalOpen] = useState(false);
    const [activeTemplateUsage, setActiveTemplateUsage] = useState<FormTemplate | null>(null);

    // --- Editor State ---
    const [editorForm, setEditorForm] = useState<FormTemplate | null>(null);
    const [activeElementId, setActiveElementId] = useState<string | null>(null);

    const handleCreate = () => {
        const newTpl: FormTemplate = {
            id: `ft_${Date.now()}`,
            name: 'New Booking Form',
            description: '',
            elements: [],
            active: true
        };
        setEditorForm(newTpl);
        setView('editor');
    };

    const handleEdit = (tpl: FormTemplate) => {
        setEditorForm(JSON.parse(JSON.stringify(tpl))); // Deep copy
        setView('editor');
    };

    const handleSave = () => {
        if (!editorForm) return;
        if (bookingFormTemplates.find(t => t.id === editorForm.id)) {
            updateFormTemplate(editorForm);
        } else {
            addFormTemplate(editorForm);
        }
        setView('list');
    };

    const getUsageCount = (tplId: string) => services.filter(s => s.formTemplateId === tplId).length;

    // --- EDITOR LOGIC ---

    const addElement = (source: FormElementSource, type: FormElementType, label: string, linkedFieldId?: string, linkedGroupId?: string) => {
        if (!editorForm) return;
        const newEl: FormElement = {
            id: `el_${Date.now()}`,
            label,
            type,
            source,
            width: 'full',
            required: false,
            linkedFieldId,
            linkedGroupId
        };
        setEditorForm({ ...editorForm, elements: [...editorForm.elements, newEl] });
    };

    const updateElement = (id: string, changes: Partial<FormElement>) => {
        if (!editorForm) return;
        setEditorForm({
            ...editorForm,
            elements: editorForm.elements.map(el => el.id === id ? { ...el, ...changes } : el)
        });
    };

    const removeElement = (id: string) => {
        if (!editorForm) return;
        setEditorForm({
            ...editorForm,
            elements: editorForm.elements.filter(el => el.id !== id)
        });
    };

    const moveElement = (idx: number, direction: 'up' | 'down') => {
        if (!editorForm) return;
        const els = [...editorForm.elements];
        if (direction === 'up' && idx > 0) {
            [els[idx], els[idx - 1]] = [els[idx - 1], els[idx]];
        } else if (direction === 'down' && idx < els.length - 1) {
            [els[idx], els[idx + 1]] = [els[idx + 1], els[idx]];
        }
        setEditorForm({ ...editorForm, elements: els });
    };

    if (view === 'list') {
        return (
            <div className="flex-1 p-6 animate-fadeIn">
                <div className="flex justify-between items-center mb-6">
                    <h3 className="font-bold text-xl text-slate-800">Booking Forms</h3>
                    <button onClick={handleCreate} className="bg-brand-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-brand-700 flex items-center gap-2 shadow-sm">
                        <Plus size={18} /> Create Form
                    </button>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {bookingFormTemplates.map(tpl => (
                        <div key={tpl.id} className="bg-white border border-slate-200 rounded-xl p-5 hover:shadow-md transition-all flex flex-col">
                            <div className="flex justify-between items-start mb-4">
                                <div className="p-3 bg-indigo-50 text-indigo-600 rounded-lg">
                                    <LayoutTemplate size={24} />
                                </div>
                                <div className={`px-2 py-1 rounded-full text-xs font-bold ${tpl.active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'}`}>
                                    {tpl.active ? 'Active' : 'Draft'}
                                </div>
                            </div>
                            <h4 className="font-bold text-slate-800 text-lg mb-1">{tpl.name}</h4>
                            <p className="text-sm text-slate-500 mb-6 flex-1">{tpl.description || 'No description'}</p>

                            <div className="flex justify-between items-center pt-4 border-t border-slate-100">
                                <button
                                    onClick={() => { setActiveTemplateUsage(tpl); setUsageModalOpen(true); }}
                                    className="text-xs font-medium text-slate-500 hover:text-brand-600 flex items-center gap-1 bg-slate-50 px-2 py-1 rounded"
                                >
                                    <LinkIcon size={12}/> {getUsageCount(tpl.id)} Active Usages
                                </button>
                                <div className="flex gap-2">
                                    <button onClick={() => handleEdit(tpl)} className="p-2 text-slate-400 hover:text-brand-600 hover:bg-slate-50 rounded-lg">
                                        <Edit2 size={16}/>
                                    </button>
                                    <button onClick={() => deleteFormTemplate(tpl.id)} className="p-2 text-slate-400 hover:text-rose-600 hover:bg-slate-50 rounded-lg">
                                        <Trash2 size={16}/>
                                    </button>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>

                {/* Usage Modal */}
                {usageModalOpen && activeTemplateUsage && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
                        <div className="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6 max-h-[80vh] flex flex-col">
                            <div className="flex justify-between items-center mb-4">
                                <h3 className="font-bold text-lg">Used in...</h3>
                                <button onClick={() => setUsageModalOpen(false)}><X size={20} className="text-slate-400" /></button>
                            </div>
                            <div className="flex-1 overflow-y-auto space-y-2">
                                {services.filter(s => s.formTemplateId === activeTemplateUsage.id).map(s => (
                                    <div key={s.id} className="flex justify-between items-center p-3 border border-slate-100 rounded-lg hover:bg-slate-50">
                                        <span className="font-medium text-sm text-slate-700">{s.title}</span>
                                        <button onClick={() => onOpenService(s.id)} className="text-xs text-brand-600 hover:underline flex items-center gap-1">
                                            Edit Service <ExternalLink size={10}/>
                                        </button>
                                    </div>
                                ))}
                                {getUsageCount(activeTemplateUsage.id) === 0 && <p className="text-slate-400 text-center italic p-4">Not used in any services yet.</p>}
                            </div>
                        </div>
                    </div>
                )}
            </div>
        );
    }

    // EDITOR VIEW
    if (!editorForm) return null;

    return (
        <div className="flex-1 flex flex-col h-full bg-slate-50/50">
            {/* Header */}
            <div className="p-4 border-b border-slate-200 bg-white flex justify-between items-center shadow-sm z-10">
                <div className="flex items-center gap-4">
                    <button onClick={() => setView('list')} className="text-slate-500 hover:text-slate-700 flex items-center gap-1 text-sm font-medium">
                        <ArrowLeft size={16} /> Back to Forms
                    </button>
                    <div className="h-6 w-px bg-slate-200"></div>
                    <input
                        className="font-bold text-lg text-slate-800 bg-transparent border-none focus:ring-0 p-0"
                        value={editorForm.name}
                        onChange={e => setEditorForm({...editorForm, name: e.target.value})}
                        placeholder="Form Name"
                    />
                </div>
                <div className="flex gap-3">
                    <button onClick={() => setView('list')} className="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 font-medium text-sm">Cancel</button>
                    <button onClick={handleSave} className="px-6 py-2 bg-brand-600 text-white rounded-lg font-medium hover:bg-brand-700 text-sm flex items-center gap-2 shadow-sm">
                        <Save size={16} /> Save Form
                    </button>
                </div>
            </div>

            <div className="flex-1 flex overflow-hidden">
                {/* TOOLBOX (Left) */}
                <div className="w-80 bg-white border-r border-slate-200 flex flex-col overflow-y-auto z-10 shadow-lg">
                    <div className="p-4 bg-slate-50 border-b border-slate-100">
                        <h4 className="font-bold text-slate-700 text-sm uppercase">Data Sources</h4>
                    </div>

                    <div className="p-4 space-y-6">
                        {/* Standard Fields */}
                        <div>
                            <h5 className="text-xs font-bold text-slate-400 uppercase mb-3">Standard Customer Data</h5>
                            <div className="grid grid-cols-2 gap-2">
                                {['First Name', 'Last Name', 'Email', 'Phone', 'Address', 'Birthday'].map(label => (
                                    <button
                                        key={label}
                                        onClick={() => addElement('linked', 'text', label, label.toLowerCase().replace(' ', '_'))}
                                        className="text-left px-3 py-2 text-xs font-medium bg-white border border-slate-200 rounded hover:border-brand-300 hover:text-brand-600 transition-colors"
                                    >
                                        + {label}
                                    </button>
                                ))}
                            </div>
                        </div>

                        {/* Master Data */}
                        {customerFieldDefinitions.length > 0 && (
                            <div>
                                <h5 className="text-xs font-bold text-slate-400 uppercase mb-3">Master Data Fields</h5>
                                {customerFieldDefinitions.map(group => (
                                    <div key={group.id} className="mb-3">
                                        <p className="text-xs font-semibold text-slate-600 mb-2 pl-1">{group.title}</p>
                                        <div className="space-y-1">
                                            {group.fields.map(field => (
                                                <button
                                                    key={field.id}
                                                    onClick={() => addElement('linked', field.type === 'select' ? 'select' : field.type === 'date' ? 'date' : 'text', field.label, field.id, group.id)}
                                                    className="w-full text-left px-3 py-2 text-xs text-slate-600 bg-slate-50 border border-transparent rounded hover:bg-white hover:border-brand-300 hover:shadow-sm transition-all flex justify-between items-center"
                                                >
                                                    <span>{field.label}</span>
                                                    <Plus size={12} className="text-slate-400"/>
                                                </button>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}

                        {/* Custom Fields */}
                        <div>
                            <h5 className="text-xs font-bold text-slate-400 uppercase mb-3">Custom Fields (Booking Only)</h5>
                            <div className="grid grid-cols-2 gap-2">
                                <button onClick={() => addElement('custom', 'text', 'Short Text')} className="p-2 border border-slate-200 rounded hover:bg-slate-50 flex flex-col items-center gap-1 text-xs font-medium text-slate-600">
                                    <Type size={16}/> Text
                                </button>
                                <button onClick={() => addElement('custom', 'textarea', 'Long Text')} className="p-2 border border-slate-200 rounded hover:bg-slate-50 flex flex-col items-center gap-1 text-xs font-medium text-slate-600">
                                    <AlignLeft size={16}/> Area
                                </button>
                                <button onClick={() => addElement('custom', 'select', 'Dropdown')} className="p-2 border border-slate-200 rounded hover:bg-slate-50 flex flex-col items-center gap-1 text-xs font-medium text-slate-600">
                                    <ChevronDown size={16}/> Dropdown
                                </button>
                                <button onClick={() => addElement('custom', 'radio', 'Single Choice')} className="p-2 border border-slate-200 rounded hover:bg-slate-50 flex flex-col items-center gap-1 text-xs font-medium text-slate-600">
                                    <Disc size={16}/> Radio
                                </button>
                                <button onClick={() => addElement('custom', 'multiselect', 'Multi Select')} className="p-2 border border-slate-200 rounded hover:bg-slate-50 flex flex-col items-center gap-1 text-xs font-medium text-slate-600">
                                    <CheckSquareIcon size={16}/> Multi
                                </button>
                                <button onClick={() => addElement('custom', 'file', 'File Upload')} className="p-2 border border-slate-200 rounded hover:bg-slate-50 flex flex-col items-center gap-1 text-xs font-medium text-slate-600">
                                    <Paperclip size={16}/> File
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {/* CANVAS (Right) */}
                <div className="flex-1 p-8 overflow-y-auto flex justify-center bg-slate-100">
                    <div className="w-full max-w-3xl bg-white min-h-[500px] shadow-sm border border-slate-200 rounded-xl p-8 h-fit">
                        <div className="text-center mb-8 border-b border-slate-100 pb-4">
                            <h2 className="text-2xl font-bold text-slate-800 mb-2">Booking Form Preview</h2>
                            <p className="text-sm text-slate-500">This is how your customers will see the form.</p>
                        </div>

                        {editorForm.elements.length === 0 ? (
                            <div className="text-center py-12 border-2 border-dashed border-slate-200 rounded-xl text-slate-400">
                                <MousePointer2 size={32} className="mx-auto mb-2 opacity-50"/>
                                <p>Click items on the left to add fields</p>
                            </div>
                        ) : (
                            <div className="flex flex-wrap -mx-2">
                                {editorForm.elements.map((el, idx) => (
                                    <div
                                        key={el.id}
                                        className={`px-2 mb-4 relative group ${el.width === 'half' ? 'w-1/2' : el.width === 'third' ? 'w-1/3' : 'w-full'}`}
                                        onClick={() => setActiveElementId(el.id)}
                                    >
                                        <div className={`p-4 border rounded-lg transition-all hover:shadow-md cursor-pointer bg-white relative ${activeElementId === el.id ? 'border-brand-500 ring-1 ring-brand-500' : 'border-slate-200'}`}>

                                            {/* Header / Label Editor */}
                                            <div className="flex justify-between items-start mb-2">
                                                <div className="flex-1 mr-2">
                                                    <input
                                                        className="font-bold text-sm text-slate-700 bg-transparent border-none p-0 focus:ring-0 w-full"
                                                        value={el.label}
                                                        onChange={e => updateElement(el.id, { label: e.target.value })}
                                                    />
                                                    <div className="flex items-center gap-2 mt-1">
                                                        <span className={`text-[10px] px-1.5 py-0.5 rounded uppercase font-bold tracking-wide ${el.source === 'linked' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700'}`}>
                                                            {el.source}
                                                        </span>
                                                        <span className="text-[10px] text-slate-400">{el.type}</span>
                                                    </div>
                                                </div>

                                                <div className="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity bg-white shadow-sm border border-slate-100 rounded p-1 absolute top-2 right-2">
                                                    <button onClick={(e) => { e.stopPropagation(); moveElement(idx, 'up') }} disabled={idx===0} className="p-1 text-slate-400 hover:text-slate-600 disabled:opacity-30"><ArrowUpRight size={14} className="-rotate-45"/></button>
                                                    <button onClick={(e) => { e.stopPropagation(); moveElement(idx, 'down') }} disabled={idx===editorForm.elements.length-1} className="p-1 text-slate-400 hover:text-slate-600 disabled:opacity-30"><ArrowUpRight size={14} className="rotate-135"/></button>
                                                    <div className="w-px h-3 bg-slate-200 mx-1"></div>
                                                    <button onClick={(e) => { e.stopPropagation(); removeElement(el.id) }} className="p-1 text-slate-400 hover:text-rose-500"><Trash2 size={14}/></button>
                                                </div>
                                            </div>

                                            {/* Field Preview (Dummy) */}
                                            <div className="bg-slate-50 h-8 rounded border border-slate-200 w-full mb-3"></div>

                                            {/* Configuration Footer */}
                                            {activeElementId === el.id && (
                                                <div className="pt-3 border-t border-slate-100 flex flex-wrap gap-3 items-center animate-fadeIn" onClick={e => e.stopPropagation()}>
                                                    <label className="flex items-center gap-1.5 cursor-pointer">
                                                        <input type="checkbox" checked={el.required} onChange={e => updateElement(el.id, { required: e.target.checked })} className="rounded text-brand-600"/>
                                                        <span className="text-xs font-medium text-slate-600">Required</span>
                                                    </label>

                                                    <div className="h-4 w-px bg-slate-200"></div>

                                                    <div className="flex bg-slate-100 rounded p-0.5">
                                                        <button onClick={() => updateElement(el.id, { width: 'full' })} className={`p-1 rounded ${el.width === 'full' ? 'bg-white shadow text-slate-800' : 'text-slate-400'}`} title="Full Width"><Maximize size={12}/></button>
                                                        <button onClick={() => updateElement(el.id, { width: 'half' })} className={`p-1 rounded ${el.width === 'half' ? 'bg-white shadow text-slate-800' : 'text-slate-400'}`} title="Half Width"><Columns size={12}/></button>
                                                        <button onClick={() => updateElement(el.id, { width: 'third' })} className={`p-1 rounded ${el.width === 'third' ? 'bg-white shadow text-slate-800' : 'text-slate-400'}`} title="Third Width"><GridIcon size={12}/></button>
                                                    </div>

                                                    <div className="h-4 w-px bg-slate-200"></div>

                                                    <button className="text-xs text-slate-500 hover:text-brand-600 flex items-center gap-1">
                                                        <Info size={12}/> Help Text
                                                    </button>

                                                    {(el.type === 'select' || el.type === 'radio' || el.type === 'multiselect') && (
                                                        <div className="w-full mt-2">
                                                            <label className="text-[10px] font-bold text-slate-400 uppercase mb-1 block">Options (comma separated)</label>
                                                            <input
                                                                className="w-full text-xs border border-slate-300 rounded px-2 py-1"
                                                                value={el.options?.join(', ') || ''}
                                                                onChange={e => updateElement(el.id, { options: e.target.value.split(',').map(s => s.trim()) })}
                                                                placeholder="Option 1, Option 2..."
                                                            />
                                                        </div>
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}

                        <div className="mt-8 pt-6 border-t border-slate-200">
                            <button className="w-full bg-brand-600 text-white py-3 rounded-lg font-bold shadow-sm">
                                Submit Booking
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default BookingFormsTab;
