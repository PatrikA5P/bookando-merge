import React, { useState } from 'react';
import { 
    ArrowLeft, Save, Link, Video, Image, FileText, Upload, Trash2, Plus, X, 
    ChevronDown, Bold, Italic, List, Globe, Play, File, Paperclip 
} from 'lucide-react';
import { ExtendedLesson, LessonGroup } from '../../../types';

const LessonEditor: React.FC<{ 
    lesson: ExtendedLesson; 
    groups: LessonGroup[];
    onSave: (l: ExtendedLesson) => void; 
    onCancel: () => void 
}> = ({ lesson, groups, onSave, onCancel }) => {
   const [data, setData] = useState<ExtendedLesson>({
       ...lesson,
       structuredAttachments: lesson.structuredAttachments || [
           ...(lesson.mediaUrls || []).map((url, i) => ({ id: `old_m_${i}`, type: 'image', url, name: 'Legacy Media', description: '' } as StructuredAttachment)),
           ...(lesson.fileAttachments || []).map((url, i) => ({ id: `old_f_${i}`, type: 'document', url, name: 'Legacy File', description: '' } as StructuredAttachment))
       ]
   });
   
   const [contentMode, setContentMode] = useState<'visual' | 'html'>('visual');
   
   // Refs for file inputs
   const imageInputRef = useRef<HTMLInputElement>(null);
   const videoInputRef = useRef<HTMLInputElement>(null);
   const docInputRef = useRef<HTMLInputElement>(null);
   const editorRef = useRef<HTMLDivElement>(null);
   const textareaRef = useRef<HTMLTextAreaElement>(null);

   // --- Rich Text Helpers ---
   const handleFormat = (command: string, value?: string) => {
       if (contentMode === 'visual') {
           // Use execCommand for visual editing
           document.execCommand(command, false, value);
           if (editorRef.current) {
               editorRef.current.focus();
           }
       } else {
           // Use text insertion for HTML mode
           const textarea = textareaRef.current;
           if (!textarea) return;

           let tagStart = '';
           let tagEnd = '';

           switch(command) {
               case 'bold': tagStart = '<b>'; tagEnd = '</b>'; break;
               case 'italic': tagStart = '<i>'; tagEnd = '</i>'; break;
               case 'insertUnorderedList': tagStart = '<ul>\n<li>'; tagEnd = '</li>\n</ul>'; break;
               case 'createLink': tagStart = `<a href="${value || '#'}">`; tagEnd = '</a>'; break;
           }

           const start = textarea.selectionStart;
           const end = textarea.selectionEnd;
           const text = textarea.value;
           const before = text.substring(0, start);
           const selection = text.substring(start, end);
           const after = text.substring(end);

           const newText = before + tagStart + selection + tagEnd + after;
           setData({ ...data, content: newText });
           
           // Reset cursor position
           setTimeout(() => {
               textarea.focus();
               textarea.setSelectionRange(start + tagStart.length, end + tagStart.length);
           }, 0);
       }
   };

   const handleLink = () => {
       const url = prompt('Enter Link URL:');
       if (url) handleFormat('createLink', url);
   };

   // --- Attachment Helpers ---
   const handleFileUpload = (e: React.ChangeEvent<HTMLInputElement>, type: 'image' | 'video' | 'document') => {
       const file = e.target.files?.[0];
       if (!file) return;

       const reader = new FileReader();
       reader.onload = (event) => {
           if (event.target?.result) {
               const newAtt: StructuredAttachment = {
                   id: `att_${Date.now()}`,
                   type,
                   url: event.target.result as string,
                   name: file.name,
                   description: ''
               };
               setData(prev => ({
                   ...prev,
                   structuredAttachments: [...(prev.structuredAttachments || []), newAtt]
               }));
           }
       };
       reader.readAsDataURL(file);
       
       // Reset input to allow selecting same file again
       e.target.value = ''; 
   };

   const triggerFileUpload = (type: 'image' | 'video' | 'document') => {
       if (type === 'image') imageInputRef.current?.click();
       else if (type === 'video') videoInputRef.current?.click();
       else docInputRef.current?.click();
   };

   const updateAttachment = (id: string, changes: Partial<StructuredAttachment>) => {
       setData({
           ...data,
           structuredAttachments: data.structuredAttachments?.map(a => a.id === id ? { ...a, ...changes } : a)
       });
   };

   const removeAttachment = (id: string) => {
       setData({
           ...data,
           structuredAttachments: data.structuredAttachments?.filter(a => a.id !== id)
       });
   };

   const renderAttachmentList = (type: 'image' | 'video' | 'document') => {
       const items = data.structuredAttachments?.filter(a => a.type === type) || [];
       
       return (
           <div className="space-y-3">
               <div className="flex items-center justify-between">
                   <h4 className="text-sm font-bold text-slate-600 uppercase flex items-center gap-2">
                       {type === 'image' && <ImageIcon size={14}/>}
                       {type === 'video' && <Video size={14}/>}
                       {type === 'document' && <FileText size={14}/>}
                       {type}s
                   </h4>
                   <button 
                       onClick={() => triggerFileUpload(type)}
                       className="text-xs bg-white border border-slate-300 hover:bg-brand-50 hover:text-brand-700 hover:border-brand-300 px-2 py-1 rounded transition-colors flex items-center gap-1"
                   >
                       <Plus size={12} /> Add {type === 'document' ? 'File' : type}
                   </button>
                   {/* Hidden Inputs */}
                   {type === 'image' && <input type="file" ref={imageInputRef} className="hidden" accept="image/*" onChange={(e) => handleFileUpload(e, 'image')} />}
                   {type === 'video' && <input type="file" ref={videoInputRef} className="hidden" accept="video/*" onChange={(e) => handleFileUpload(e, 'video')} />}
                   {type === 'document' && <input type="file" ref={docInputRef} className="hidden" accept=".pdf,.doc,.docx,.txt" onChange={(e) => handleFileUpload(e, 'document')} />}
               </div>
               
               {items.length === 0 && (
                   <div className="p-4 border-2 border-dashed border-slate-100 rounded-lg text-center text-xs text-slate-400 bg-slate-50/50">
                       No {type}s attached
                   </div>
               )}

               <div className={`grid ${type === 'image' ? 'grid-cols-2 md:grid-cols-3 lg:grid-cols-4' : 'grid-cols-1'} gap-3`}>
                   {items.map(att => (
                       <div key={att.id} className={`relative group bg-white border border-slate-200 rounded-lg overflow-hidden shadow-sm ${type !== 'image' ? 'p-3 flex gap-3 items-start' : ''}`}>
                           {/* Preview Area */}
                           {type === 'image' && (
                               <div className="aspect-video bg-slate-100 relative overflow-hidden">
                                   <img src={att.url} className="w-full h-full object-cover" alt="prev" />
                                   <button 
                                       onClick={() => removeAttachment(att.id)}
                                       className="absolute top-2 right-2 bg-white/90 p-1 rounded-full text-rose-500 hover:text-rose-700 shadow-sm opacity-0 group-hover:opacity-100 transition-opacity"
                                   >
                                       <Trash2 size={14} />
                                   </button>
                               </div>
                           )}
                           
                           {type !== 'image' && (
                               <div className={`w-12 h-12 rounded-lg flex items-center justify-center shrink-0 ${type === 'video' ? 'bg-purple-50 text-purple-600' : 'bg-blue-50 text-blue-600'}`}>
                                   {type === 'video' ? <Video size={20}/> : <FileText size={20}/>}
                               </div>
                           )}

                           {/* Inputs */}
                           <div className={`flex-1 min-w-0 ${type === 'image' ? 'p-2' : ''}`}>
                               <input 
                                   className="w-full text-sm font-medium text-slate-800 border-none p-0 focus:ring-0 bg-transparent placeholder-slate-400 mb-1"
                                   value={att.name}
                                   onChange={e => updateAttachment(att.id, { name: e.target.value })}
                                   placeholder="Title"
                               />
                               <input 
                                   className="w-full text-xs text-slate-500 border-none p-0 focus:ring-0 bg-transparent placeholder-slate-300"
                                   value={att.description}
                                   onChange={e => updateAttachment(att.id, { description: e.target.value })}
                                   placeholder="Description..."
                               />
                           </div>

                           {/* List Remove Button */}
                           {type !== 'image' && (
                               <button onClick={() => removeAttachment(att.id)} className="text-slate-300 hover:text-rose-500 p-1">
                                   <Trash2 size={16} />
                               </button>
                           )}
                       </div>
                   ))}
               </div>
           </div>
       );
   }

   return (
      <div className="bg-white rounded-xl border border-slate-200 shadow-lg p-0 overflow-hidden flex flex-col h-full animate-fadeIn">
         {/* Header */}
         <div className="flex justify-between items-center p-6 border-b border-slate-100 bg-slate-50">
            <div className="flex items-center gap-4 w-full max-w-2xl">
                <button onClick={onCancel} className="text-slate-400 hover:text-slate-600"><ArrowLeft size={24} /></button>
                <div className="flex-1">
                    <input 
                        className="w-full bg-transparent font-bold text-xl text-slate-800 focus:outline-none placeholder-slate-300"
                        value={data.title}
                        onChange={e => setData({...data, title: e.target.value})}
                        placeholder="Lesson Title"
                    />
                </div>
            </div>
            <div className="flex items-center gap-4">
                <select 
                    className="bg-white border border-slate-300 text-slate-700 text-sm rounded-lg focus:ring-brand-500 focus:border-brand-500 block p-2.5"
                    value={data.groupId || ''}
                    onChange={e => setData({...data, groupId: e.target.value})}
                >
                    <option value="">Select Group...</option>
                    {groups.map(g => <option key={g.id} value={g.id}>{g.title}</option>)}
                </select>
                <button onClick={() => onSave(data)} className="px-6 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 font-medium flex items-center gap-2 shadow-sm">
                    <Save size={18} /> Save Lesson
                </button>
            </div>
         </div>
         
         <div className="flex-1 overflow-y-auto p-6 bg-slate-50/30">
            <div className="max-w-5xl mx-auto space-y-6">
                {/* Rich Content Editor */}
                <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    {/* Toolbar - Always Visible */}
                    <div className="flex items-center justify-between px-4 py-2 border-b border-slate-100 bg-slate-50/50 sticky top-0 z-10">
                        <div className="flex items-center gap-1">
                            <button onClick={() => handleFormat('bold')} className="p-1.5 hover:bg-slate-200 rounded text-slate-600" title="Bold"><Bold size={16}/></button>
                            <button onClick={() => handleFormat('italic')} className="p-1.5 hover:bg-slate-200 rounded text-slate-600" title="Italic"><Italic size={16}/></button>
                            <div className="w-px h-4 bg-slate-300 mx-1"></div>
                            <button onClick={() => handleFormat('insertUnorderedList')} className="p-1.5 hover:bg-slate-200 rounded text-slate-600" title="List"><ListIcon size={16}/></button>
                            <button onClick={handleLink} className="p-1.5 hover:bg-slate-200 rounded text-slate-600" title="Link"><LinkIcon size={16}/></button>
                        </div>
                        <button 
                            onClick={() => setContentMode(contentMode === 'visual' ? 'html' : 'visual')}
                            className={`flex items-center gap-2 px-3 py-1 rounded text-xs font-medium border transition-colors ${contentMode === 'html' ? 'bg-slate-800 text-white border-slate-800' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'}`}
                        >
                            <Code size={14} /> {contentMode === 'visual' ? 'Source' : 'Visual'}
                        </button>
                    </div>
                    
                    <div className="p-4 relative min-h-[20rem]">
                        {contentMode === 'visual' ? (
                            <div 
                                ref={editorRef}
                                className="w-full h-64 focus:outline-none resize-y leading-relaxed text-sm overflow-auto"
                                contentEditable
                                dangerouslySetInnerHTML={{ __html: data.content }}
                                onInput={(e) => setData({...data, content: e.currentTarget.innerHTML})}
                                style={{ minHeight: '16rem' }}
                            />
                        ) : (
                            <textarea 
                                ref={textareaRef}
                                className="w-full h-64 focus:outline-none resize-y leading-relaxed text-sm font-mono text-slate-600 bg-slate-50 p-2 rounded border border-slate-200"
                                value={data.content}
                                onChange={e => setData({...data, content: e.target.value})}
                                placeholder="<div>Enter HTML here...</div>"
                            />
                        )}
                    </div>
                </div>

                {/* Attachments Separated */}
                <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div className="p-4 border-b border-slate-100 bg-slate-50">
                        <h3 className="font-bold text-slate-800 flex items-center gap-2">
                            <Paperclip size={18} className="text-brand-600" /> Attachments & Media
                        </h3>
                    </div>
                    <div className="p-6 space-y-8">
                        {renderAttachmentList('image')}
                        <div className="h-px bg-slate-100 w-full"></div>
                        {renderAttachmentList('video')}
                        <div className="h-px bg-slate-100 w-full"></div>
                        {renderAttachmentList('document')}
                    </div>
                </div>
            </div>
         </div>
      </div>
   );
};

// --- BADGE MANAGER ---


export default LessonEditor;
