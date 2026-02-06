
import React, { useState } from 'react';
import { Plus, Trash2, GripVertical, X, ChevronDown, ChevronUp, CheckCircle, CheckSquare, Sliders, Move, AlignLeft, Type, Puzzle } from 'lucide-react';
import { Quiz, Question, QuestionType } from '../../../types';

const QuizEditor: React.FC<{ quiz: Quiz; onSave: (q: Quiz) => void; onCancel: () => void }> = ({ quiz, onSave, onCancel }) => {
   const [data, setData] = useState<Quiz>(quiz);
   const [tab, setTab] = useState<'general' | 'questions' | 'settings'>('general');

   const addQuestion = (type: QuestionType) => {
      const newQ: Question = {
         id: `q_${Date.now()}`,
         text: 'New Question',
         type,
         points: 1,
         options: ['Option 1', 'Option 2'],
         correctAnswer: 'Option 1'
      };
      setData({ ...data, questions: [...data.questions, newQ] });
   };

   return (
      <div className="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
         <div className="bg-white rounded-xl shadow-2xl w-full max-w-5xl h-[90vh] flex flex-col overflow-hidden">
            <div className="p-4 border-b border-slate-200 flex justify-between items-center bg-slate-50">
               <div className="flex items-center gap-4">
                  <h3 className="text-xl font-bold text-slate-800">Quiz Editor</h3>
                  <div className="flex bg-white rounded-lg border border-slate-200 p-1">
                     {['general', 'questions', 'settings'].map(t => (
                        <button 
                           key={t}
                           onClick={() => setTab(t as any)}
                           className={`px-3 py-1 text-sm font-medium rounded capitalize ${tab === t ? 'bg-brand-50 text-brand-700' : 'text-slate-500 hover:text-slate-800'}`}
                        >
                           {t}
                        </button>
                     ))}
                  </div>
               </div>
               <div className="flex gap-2">
                  <button onClick={onCancel} className="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-lg font-medium">Cancel</button>
                  <button onClick={() => onSave(data)} className="px-6 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 font-medium">Save Quiz</button>
               </div>
            </div>

            <div className="flex-1 overflow-y-auto p-6 bg-slate-50/50">
               {tab === 'general' && (
                  <div className="max-w-2xl mx-auto space-y-4 bg-white p-8 rounded-xl border border-slate-200 shadow-sm">
                     <div>
                        <label className="label">Quiz Title</label>
                        <input className="input" value={data.title} onChange={e => setData({...data, title: e.target.value})} />
                     </div>
                     <div>
                        <label className="label">Summary / Instructions</label>
                        <textarea className="input h-32" value={data.summary} onChange={e => setData({...data, summary: e.target.value})} />
                     </div>
                  </div>
               )}

               {tab === 'settings' && (
                  <div className="max-w-2xl mx-auto space-y-6 bg-white p-8 rounded-xl border border-slate-200 shadow-sm">
                     <div>
                        <h4 className="font-bold text-slate-800 border-b pb-2 mb-4">Attempts & Scoring</h4>
                        <div className="grid grid-cols-2 gap-4">
                           <div>
                              <label className="label">Allowed Attempts</label>
                              <input type="number" className="input" value={data.settings.allowedAttempts} onChange={e => setData({...data, settings: {...data.settings, allowedAttempts: parseInt(e.target.value)}})} />
                              <p className="text-xs text-slate-400 mt-1">0 for unlimited</p>
                           </div>
                           <div>
                              <label className="label">Passing Score (%)</label>
                              <input type="number" className="input" value={data.settings.passingScore} onChange={e => setData({...data, settings: {...data.settings, passingScore: parseInt(e.target.value)}})} />
                           </div>
                        </div>
                     </div>
                     <div>
                        <h4 className="font-bold text-slate-800 border-b pb-2 mb-4">Display & Behavior</h4>
                        <div className="space-y-3">
                           <label className="flex items-center gap-2">
                              <input type="checkbox" checked={data.settings.shuffleQuestions} onChange={e => setData({...data, settings: {...data.settings, shuffleQuestions: e.target.checked}})} />
                              <span className="text-sm text-slate-700">Shuffle Questions</span>
                           </label>
                           <div>
                              <label className="label">Layout</label>
                              <select className="input" value={data.settings.layout} onChange={e => setData({...data, settings: {...data.settings, layout: e.target.value as any}})}>
                                 <option value="Single Page">Single Page (All at once)</option>
                                 <option value="One per page">One Question per Page</option>
                              </select>
                           </div>
                           <div>
                              <label className="label">Feedback</label>
                              <select className="input" value={data.settings.feedbackMode} onChange={e => setData({...data, settings: {...data.settings, feedbackMode: e.target.value as any}})}>
                                 <option value="Immediate">Show Immediate Feedback</option>
                                 <option value="End of Quiz">Show Only at End</option>
                              </select>
                           </div>
                        </div>
                     </div>
                  </div>
               )}

               {tab === 'questions' && (
                  <div className="flex gap-6 h-full">
                     {/* Question List Sidebar */}
                     <div className="w-64 flex-shrink-0 flex flex-col gap-3">
                        <div className="dropdown relative group">
                           <button className="w-full py-2 bg-brand-600 text-white rounded-lg font-medium flex justify-center items-center gap-2 hover:bg-brand-700">
                              <Plus size={18} /> Add Question
                           </button>
                           {/* Simple Dropdown implementation */}
                           <div className="hidden group-hover:block absolute top-full left-0 w-64 bg-white border border-slate-200 shadow-xl rounded-xl mt-2 p-2 z-10 grid grid-cols-1 gap-1">
                              {[
                                 { type: QuestionType.SINGLE_CHOICE, label: 'Single Choice', icon: CheckCircle },
                                 { type: QuestionType.MULTIPLE_CHOICE, label: 'Multiple Choice', icon: CheckSquare },
                                 { type: QuestionType.TRUE_FALSE, label: 'True / False', icon: Sliders },
                                 { type: QuestionType.SLIDER, label: 'Slider', icon: Move },
                                 { type: QuestionType.ESSAY, label: 'Essay', icon: AlignLeft },
                                 { type: QuestionType.FILL_BLANKS, label: 'Fill Blanks', icon: Type },
                                 { type: QuestionType.MATCHING, label: 'Matching', icon: Puzzle },
                              ].map(q => (
                                 <button key={q.type} onClick={() => addQuestion(q.type)} className="text-left px-3 py-2 text-sm hover:bg-slate-50 rounded flex items-center gap-2 text-slate-700">
                                    <q.icon size={14} /> {q.label}
                                 </button>
                              ))}
                           </div>
                        </div>
                        
                        <div className="flex-1 overflow-y-auto space-y-2 pr-2">
                           {data.questions.map((q, i) => (
                              <div key={q.id} className="p-3 bg-white border border-slate-200 rounded-lg hover:border-brand-300 cursor-pointer group relative">
                                 <div className="text-xs font-bold text-slate-400 mb-1">Q{i + 1} • {q.type}</div>
                                 <div className="text-sm text-slate-800 truncate">{q.text}</div>
                                 <button 
                                    onClick={(e) => { e.stopPropagation(); setData({...data, questions: data.questions.filter(x => x.id !== q.id)})}}
                                    className="absolute top-2 right-2 text-slate-300 hover:text-rose-500 opacity-0 group-hover:opacity-100"
                                 >
                                    <X size={14} />
                                 </button>
                              </div>
                           ))}
                        </div>
                     </div>

                     {/* Question Detail Editor */}
                     <div className="flex-1 bg-white rounded-xl border border-slate-200 shadow-sm p-8 overflow-y-auto">
                        {data.questions.length > 0 ? (
                           // Simplified Question Editor (Normally would map selected question)
                           <div className="space-y-6">
                              <div className="flex justify-between">
                                 <h4 className="font-bold text-lg text-slate-800">Editing Question {data.questions.length}</h4>
                                 <div className="flex items-center gap-2">
                                    <span className="text-sm text-slate-500">Points:</span>
                                    <input type="number" className="w-16 border border-slate-300 rounded px-2 py-1 text-sm" defaultValue={1} />
                                 </div>
                              </div>

                              <div>
                                 <label className="label">Question Text</label>
                                 <textarea className="input h-24" placeholder="Enter your question here..." defaultValue={data.questions[data.questions.length-1].text} />
                              </div>

                              {/* Dynamic fields based on type would go here */}
                              <div className="p-4 bg-slate-50 rounded-lg border border-slate-100">
                                 <p className="text-sm font-bold text-slate-600 mb-3">Answers / Options</p>
                                 <div className="space-y-2">
                                    <div className="flex items-center gap-2">
                                       <input type="radio" name="correct" checked />
                                       <input className="input py-1.5" defaultValue="Option 1" />
                                       <button className="text-slate-400"><Trash2 size={14} /></button>
                                    </div>
                                    <div className="flex items-center gap-2">
                                       <input type="radio" name="correct" />
                                       <input className="input py-1.5" defaultValue="Option 2" />
                                       <button className="text-slate-400"><Trash2 size={14} /></button>
                                    </div>
                                    <button className="text-brand-600 text-sm font-medium mt-2">+ Add Option</button>
                                 </div>
                              </div>

                              <div>
                                 <label className="label">Media (Optional)</label>
                                 <input className="input" placeholder="Image or Video URL" />
                              </div>
                           </div>
                        ) : (
                           <div className="h-full flex items-center justify-center text-slate-400">
                              Select or add a question to edit
                           </div>
                        )}
                     </div>
                  </div>
               )}
            </div>
         </div>
         <style>{`
            .label { @apply block text-sm font-medium text-slate-700 mb-1; }
            .input { @apply w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none bg-white; }
         `}</style>
      </div>
   );
};

export default QuizEditor;
