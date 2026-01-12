import React, { useState, useRef } from 'react';
import {
    ArrowLeft, Save, Globe, Shield, Plus, Trash2, ChevronDown, ChevronUp, Edit2, X, Upload,
    GripVertical, AlertCircle, Check, Clock, Calendar, Users, BookOpen, FileText, Award, Settings,
    PlayCircle, Image as ImageIcon, Video
} from 'lucide-react';
import { useApp } from '../../../context/AppContext';
import { Course, CourseType, CourseVisibility, DifficultyLevel, CurriculumItem, Quiz } from '../../../types';

const CourseEditor: React.FC<{ 
  course: Course; 
  onSave: (c: Course) => void; 
  onBack: () => void;
}> = ({ course, onSave, onBack }) => {
  const [activeTab, setActiveTab] = useState<'definition' | 'planning' | 'certificate'>('definition');
  const [formData, setFormData] = useState<Course>(course);

  // Auto-save effect or manual save wrapper
  const updateField = (field: keyof Course, value: any) => {
    const updated = { ...formData, [field]: value };
    setFormData(updated);
    // Debounced save could go here
  };

  const handleManualSave = () => {
    onSave(formData);
    // Show feedback
  };

  return (
    <div className="flex flex-col h-full bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden animate-fadeIn">
       {/* Editor Header */}
       <div className="flex items-center justify-between px-6 py-4 border-b border-slate-200 bg-slate-50">
          <div className="flex items-center gap-4">
             <button onClick={onBack} className="p-2 hover:bg-white rounded-full text-slate-500 transition-colors border border-transparent hover:border-slate-200">
                <ArrowLeft size={20} />
             </button>
             <div>
                <input 
                   type="text" 
                   value={formData.title}
                   onChange={(e) => updateField('title', e.target.value)}
                   className="bg-transparent text-xl font-bold text-slate-800 focus:outline-none focus:bg-white focus:ring-2 focus:ring-brand-500 rounded px-2 -ml-2"
                   placeholder="Course Title"
                />
                <div className="text-xs text-slate-500 px-1">
                   {formData.published ? 'Published' : 'Draft'} • Last saved just now
                </div>
             </div>
          </div>
          <button onClick={handleManualSave} className="flex items-center gap-2 bg-brand-600 text-white px-4 py-2 rounded-lg hover:bg-brand-700 transition-colors font-medium">
             <Save size={18} /> Save Changes
          </button>
       </div>

       {/* Tabs */}
       <div className="flex border-b border-slate-200 px-6">
          <button 
            onClick={() => setActiveTab('definition')}
            className={`px-4 py-3 text-sm font-medium border-b-2 transition-colors flex items-center gap-2 ${activeTab === 'definition' ? 'border-brand-600 text-brand-600' : 'border-transparent text-slate-500 hover:text-slate-700'}`}
          >
             <Layout size={16} /> Course Definition
          </button>
          <button 
            onClick={() => setActiveTab('planning')}
            className={`px-4 py-3 text-sm font-medium border-b-2 transition-colors flex items-center gap-2 ${activeTab === 'planning' ? 'border-brand-600 text-brand-600' : 'border-transparent text-slate-500 hover:text-slate-700'}`}
          >
             <List size={16} /> Course Planning
          </button>
          <button 
            onClick={() => setActiveTab('certificate')}
            className={`px-4 py-3 text-sm font-medium border-b-2 transition-colors flex items-center gap-2 ${activeTab === 'certificate' ? 'border-brand-600 text-brand-600' : 'border-transparent text-slate-500 hover:text-slate-700'}`}
          >
             <Award size={16} /> Certificate
          </button>
       </div>

       {/* Tab Content */}
       <div className="flex-1 overflow-y-auto bg-slate-50/50 p-6">
          {activeTab === 'definition' && (
             <DefinitionTab data={formData} onChange={setFormData} />
          )}
          {activeTab === 'planning' && (
             <PlanningTab data={formData} onChange={setFormData} />
          )}
          {activeTab === 'certificate' && (
             <CertificateTab data={formData} onChange={setFormData} />
          )}
       </div>
    </div>
  );
};

const DefinitionTab: React.FC<{ data: Course; onChange: (c: Course) => void }> = ({ data, onChange }) => {
   const handleChange = (field: keyof Course, value: any) => {
      onChange({ ...data, [field]: value });
   };

   return (
      <div className="max-w-4xl mx-auto space-y-8">
         {/* Basic Info Section */}
         <section className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <h3 className="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
               <BookOpen size={20} className="text-brand-600" /> Basic Information
            </h3>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
               <div className="col-span-full">
                  <label className="block text-sm font-medium text-slate-700 mb-1">Description</label>
                  <textarea 
                     rows={4}
                     className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none"
                     value={data.description}
                     onChange={(e) => handleChange('description', e.target.value)}
                  />
               </div>
               <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">Course Type</label>
                  <select 
                     className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none bg-white"
                     value={data.type}
                     onChange={(e) => handleChange('type', e.target.value)}
                  >
                     <option value={CourseType.ONLINE}>Online Course</option>
                     <option value={CourseType.IN_PERSON}>In-Person Class</option>
                     <option value={CourseType.BLENDED}>Blended Learning</option>
                  </select>
               </div>
               <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">Author / Instructor</label>
                  <input 
                     type="text" 
                     className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none"
                     value={data.author}
                     onChange={(e) => handleChange('author', e.target.value)}
                  />
               </div>
            </div>
         </section>

         {/* Participation & Visibility */}
         <section className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <h3 className="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
               <Eye size={20} className="text-brand-600" /> Participation & Visibility
            </h3>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
               <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">Visibility</label>
                  <select 
                     className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none bg-white"
                     value={data.visibility}
                     onChange={(e) => handleChange('visibility', e.target.value)}
                  >
                     <option value={CourseVisibility.PRIVATE}>🔒 Private (Draft)</option>
                     <option value={CourseVisibility.INTERNAL}>🏢 Internal Only</option>
                     <option value={CourseVisibility.PUBLIC}>🌍 Public</option>
                  </select>
               </div>
               <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">Max Participants</label>
                  <input 
                     type="number" 
                     placeholder="Unlimited"
                     className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none"
                     value={data.maxParticipants || ''}
                     onChange={(e) => handleChange('maxParticipants', parseInt(e.target.value) || undefined)}
                  />
               </div>
               <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">Start Date</label>
                  <input 
                     type="date" 
                     className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none"
                     value={data.startDate || ''}
                     onChange={(e) => handleChange('startDate', e.target.value)}
                  />
               </div>
               <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">End Date</label>
                  <input 
                     type="date" 
                     className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none"
                     value={data.endDate || ''}
                     onChange={(e) => handleChange('endDate', e.target.value)}
                  />
               </div>
               <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">Category</label>
                  <input 
                     type="text" 
                     className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none"
                     value={data.category}
                     onChange={(e) => handleChange('category', e.target.value)}
                  />
               </div>
               <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">Difficulty</label>
                  <select 
                     className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none bg-white"
                     value={data.difficulty}
                     onChange={(e) => handleChange('difficulty', e.target.value)}
                  >
                     <option value={DifficultyLevel.BEGINNER}>Beginner</option>
                     <option value={DifficultyLevel.INTERMEDIATE}>Intermediate</option>
                     <option value={DifficultyLevel.ADVANCED}>Advanced</option>
                  </select>
               </div>
            </div>
         </section>

         {/* Media */}
         <section className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <h3 className="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
               <ImageIcon size={20} className="text-brand-600" /> Media
            </h3>
            <div className="grid grid-cols-1 gap-6">
               <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">Cover Image URL</label>
                  <div className="flex gap-4">
                     <input 
                        type="text" 
                        className="flex-1 border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none"
                        value={data.coverImage}
                        onChange={(e) => handleChange('coverImage', e.target.value)}
                     />
                     <div className="w-24 h-16 bg-slate-100 rounded-lg border border-slate-200 overflow-hidden">
                        {data.coverImage && <img src={data.coverImage} alt="Preview" className="w-full h-full object-cover" />}
                     </div>
                  </div>
               </div>
               <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">Intro Video URL</label>
                  <div className="flex gap-4">
                     <div className="relative flex-1">
                        <Video className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
                        <input 
                           type="text" 
                           placeholder="https://vimeo.com/..."
                           className="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 outline-none"
                           value={data.introVideoUrl || ''}
                           onChange={(e) => handleChange('introVideoUrl', e.target.value)}
                        />
                     </div>
                  </div>
               </div>
            </div>
         </section>
      </div>
   );
};

const PlanningTab: React.FC<{ data: Course; onChange: (c: Course) => void }> = ({ data, onChange }) => {
   const [openTopicId, setOpenTopicId] = useState<string | null>(null);
   const [editingItem, setEditingItem] = useState<{ item: Lesson | Quiz; topicId: string } | null>(null);
   const [isAddingLesson, setIsAddingLesson] = useState<string | null>(null); // Topic ID for adding
   const { lessons, setLessons } = useApp();

   const addTopic = () => {
      const newTopic: Topic = {
         id: `t_${Date.now()}`,
         title: 'New Topic',
         summary: '',
         items: []
      };
      onChange({ ...data, curriculum: [...data.curriculum, newTopic] });
      setOpenTopicId(newTopic.id);
   };

   const deleteTopic = (id: string) => {
      if (confirm('Delete this topic and all its content?')) {
         onChange({ ...data, curriculum: data.curriculum.filter(t => t.id !== id) });
      }
   };

   const updateTopic = (id: string, field: keyof Topic, value: any) => {
      onChange({
         ...data,
         curriculum: data.curriculum.map(t => t.id === id ? { ...t, [field]: value } : t)
      });
   };

   const addExistingLessonToTopic = (topicId: string, lessonId: string) => {
        const lesson = lessons.find(l => l.id === lessonId);
        if (!lesson) return;
        
        const updatedCurriculum = data.curriculum.map(t => {
            if (t.id === topicId) {
                return { ...t, items: [...t.items, lesson] };
            }
            return t;
        });
        onChange({ ...data, curriculum: updatedCurriculum });
        setIsAddingLesson(null);
   };

   const createNewLessonInTopic = (topicId: string) => {
      const newLesson: Lesson = { 
          id: `l_${Date.now()}`, 
          type: 'lesson', 
          title: 'New Lesson', 
          content: '', 
          mediaUrls: [], 
          fileAttachments: [] 
      };
      
      // Update Global Lessons
      setLessons([...lessons, newLesson]);

      // Add to Topic
      const updatedCurriculum = data.curriculum.map(t => {
         if (t.id === topicId) {
            return { ...t, items: [...t.items, newLesson] };
         }
         return t;
      });
      onChange({ ...data, curriculum: updatedCurriculum });
      setEditingItem({ item: newLesson, topicId });
      setIsAddingLesson(null);
   };

   const addQuizToTopic = (topicId: string) => {
      const newQuiz: Quiz = { 
          id: `q_${Date.now()}`, 
          type: 'quiz', 
          title: 'New Quiz', 
          summary: '', 
          questions: [], 
          settings: { allowedAttempts: 0, passingScore: 70, shuffleQuestions: false, layout: 'Single Page', feedbackMode: 'Immediate' } 
      };

      const updatedCurriculum = data.curriculum.map(t => {
         if (t.id === topicId) {
            return { ...t, items: [...t.items, newQuiz] };
         }
         return t;
      });
      onChange({ ...data, curriculum: updatedCurriculum });
      setEditingItem({ item: newQuiz, topicId });
   };

   // Save item details back to the main state
   const handleSaveItem = (updatedItem: Lesson | Quiz) => {
      if (!editingItem) return;
      
      // If it's a lesson, update global state too
      if(updatedItem.type === 'lesson') {
          setLessons(lessons.map(l => l.id === updatedItem.id ? (updatedItem as Lesson) : l));
      }

      const updatedCurriculum = data.curriculum.map(t => {
         if (t.id === editingItem.topicId) {
            return {
               ...t,
               items: t.items.map(i => i.id === updatedItem.id ? updatedItem : i)
            };
         }
         return t;
      });
      onChange({ ...data, curriculum: updatedCurriculum });
      setEditingItem(null);
   };

   if (editingItem) {
      if (editingItem.item.type === 'lesson') {
         // Use the NEW lesson editor even within course planning, but map it back to standard lesson type
         // For simplicity in this scope, we might need a simpler editor or mock groups.
         // Assuming groups are optional or we pass an empty list.
         return <LessonEditor 
            lesson={editingItem.item as ExtendedLesson} 
            groups={[]} 
            onSave={(l) => handleSaveItem(l)} 
            onCancel={() => setEditingItem(null)} 
         />;
      } else {
         return <QuizEditor quiz={editingItem.item as Quiz} onSave={handleSaveItem} onCancel={() => setEditingItem(null)} />;
      }
   }

   return (
      <div className="max-w-4xl mx-auto space-y-6">
         <div className="flex justify-between items-center">
            <h3 className="text-lg font-bold text-slate-800">Course Curriculum</h3>
            <button onClick={addTopic} className="flex items-center gap-2 text-brand-600 font-medium hover:bg-brand-50 px-3 py-2 rounded-lg transition-colors">
               <Plus size={18} /> Add Topic
            </button>
         </div>

         <div className="space-y-4">
            {data.curriculum.length === 0 && (
               <div className="text-center py-12 border-2 border-dashed border-slate-200 rounded-xl text-slate-400">
                  <List size={48} className="mx-auto mb-2 opacity-50" />
                  <p>No topics yet. Start planning your course!</p>
               </div>
            )}

            {data.curriculum.map((topic, index) => (
               <div key={topic.id} className="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                  {/* Topic Header */}
                  <div className="p-4 bg-slate-50 border-b border-slate-100 flex items-start gap-3 group">
                     <button onClick={() => setOpenTopicId(openTopicId === topic.id ? null : topic.id)} className="mt-1 text-slate-400 hover:text-slate-600">
                        {openTopicId === topic.id ? <ChevronDown size={20} /> : <ChevronRight size={20} />}
                     </button>
                     <div className="flex-1 space-y-2">
                        <input 
                           className="font-bold text-slate-800 bg-transparent border-none focus:ring-0 p-0 w-full"
                           value={topic.title}
                           onChange={(e) => updateTopic(topic.id, 'title', e.target.value)}
                           placeholder="Topic Title"
                        />
                        <input 
                           className="text-sm text-slate-500 bg-transparent border-none focus:ring-0 p-0 w-full"
                           value={topic.summary}
                           onChange={(e) => updateTopic(topic.id, 'summary', e.target.value)}
                           placeholder="Brief summary of this topic..."
                        />
                     </div>
                     <button onClick={() => deleteTopic(topic.id)} className="text-slate-400 hover:text-rose-600 p-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <Trash2 size={16} />
                     </button>
                  </div>

                  {/* Topic Items */}
                  {openTopicId === topic.id && (
                     <div className="p-4 bg-white space-y-3">
                        {topic.items.map((item, i) => (
                           <div key={item.id} className="flex items-center gap-3 p-3 border border-slate-100 rounded-lg hover:bg-slate-50 group transition-colors">
                              <div className="text-slate-300 cursor-move"><GripVertical size={16} /></div>
                              <div className={`p-2 rounded-lg ${item.type === 'lesson' ? 'bg-blue-50 text-blue-600' : 'bg-purple-50 text-purple-600'}`}>
                                 {item.type === 'lesson' ? <FileText size={18} /> : <HelpCircle size={18} />}
                              </div>
                              <div className="flex-1">
                                 <span className="font-medium text-slate-700">{item.title}</span>
                                 <span className="text-xs text-slate-400 ml-2 uppercase">{item.type}</span>
                              </div>
                              <button onClick={() => setEditingItem({ item, topicId: topic.id })} className="p-2 text-slate-400 hover:text-brand-600">
                                 <Edit2 size={16} />
                              </button>
                              <button 
                                 onClick={() => {
                                    if(confirm('Delete item?')) {
                                       const updatedItems = topic.items.filter(x => x.id !== item.id);
                                       onChange({...data, curriculum: data.curriculum.map(t => t.id === topic.id ? {...t, items: updatedItems} : t)});
                                    }
                                 }} 
                                 className="p-2 text-slate-400 hover:text-rose-600"
                              >
                                 <Trash2 size={16} />
                              </button>
                           </div>
                        ))}

                        <div className="flex gap-3 pt-2">
                           {isAddingLesson === topic.id ? (
                               <div className="flex-1 p-3 border border-slate-200 bg-slate-50 rounded-lg animate-fadeIn">
                                   <p className="text-xs font-bold text-slate-500 mb-2 uppercase">Add Lesson</p>
                                   <div className="flex gap-2 mb-3">
                                       <button onClick={() => createNewLessonInTopic(topic.id)} className="flex-1 bg-white border border-slate-300 hover:border-brand-500 text-slate-700 py-2 rounded-lg text-sm font-medium">Create New</button>
                                       <div className="relative flex-1">
                                            <select 
                                                className="w-full h-full bg-white border border-slate-300 rounded-lg text-sm pl-2 focus:border-brand-500 outline-none"
                                                onChange={(e) => { if(e.target.value) addExistingLessonToTopic(topic.id, e.target.value); }}
                                            >
                                                <option value="">Select Existing...</option>
                                                {lessons.map(l => (
                                                    <option key={l.id} value={l.id}>{l.title}</option>
                                                ))}
                                            </select>
                                       </div>
                                   </div>
                                   <button onClick={() => setIsAddingLesson(null)} className="text-xs text-slate-400 hover:text-slate-600 w-full text-center">Cancel</button>
                               </div>
                           ) : (
                                <button onClick={() => setIsAddingLesson(topic.id)} className="flex-1 py-2 border border-dashed border-slate-300 rounded-lg text-slate-500 text-sm font-medium hover:bg-slate-50 flex justify-center items-center gap-2">
                                    <FileText size={16} /> Add Lesson
                                </button>
                           )}
                           
                           <button onClick={() => addQuizToTopic(topic.id)} className="flex-1 py-2 border border-dashed border-slate-300 rounded-lg text-slate-500 text-sm font-medium hover:bg-slate-50 flex justify-center items-center gap-2">
                              <HelpCircle size={16} /> Add Test
                           </button>
                        </div>
                     </div>
                  )}
               </div>
            ))}
         </div>
      </div>
   );
};

const CertificateTab: React.FC<{ data: Course; onChange: (c: Course) => void }> = ({ data, onChange }) => {
   const { badges } = useApp();
   
   const updateCert = (field: keyof CertificateSettings, value: any) => {
      onChange({ ...data, certificate: { ...data.certificate, [field]: value } });
   };

   const updateBadge = (id: string) => {
       onChange({ ...data, awardedBadgeId: id });
   };

   return (
      <div className="max-w-4xl mx-auto space-y-8">
         <section className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <div className="flex items-center justify-between mb-6">
               <h3 className="text-lg font-bold text-slate-800 flex items-center gap-2">
                  <Award size={20} className="text-brand-600" /> Course Certificate
               </h3>
               <label className="relative inline-flex items-center cursor-pointer">
                  <input type="checkbox" className="sr-only peer" checked={data.certificate.enabled} onChange={(e) => updateCert('enabled', e.target.checked)} />
                  <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                  <span className="ml-3 text-sm font-medium text-slate-700">Enable Certificate</span>
               </label>
            </div>

            {data.certificate.enabled && (
               <div className="space-y-6 animate-fadeIn">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                     <div>
                        <label className="block text-sm font-medium text-slate-700 mb-1">Certificate Template</label>
                        <select 
                           className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none bg-white"
                           value={data.certificate.templateId}
                           onChange={(e) => updateCert('templateId', e.target.value)}
                        >
                           <option value="default">Classic (Default)</option>
                           <option value="modern">Modern Minimal</option>
                           <option value="safety">Compliance & Safety</option>
                        </select>
                     </div>
                     <div>
                        <label className="block text-sm font-medium text-slate-700 mb-1">Signature Name</label>
                        <input 
                           type="text" 
                           placeholder="e.g. John Doe, CEO"
                           className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none"
                           value={data.certificate.signatureText}
                           onChange={(e) => updateCert('signatureText', e.target.value)}
                        />
                     </div>
                     <div>
                        <label className="block text-sm font-medium text-slate-700 mb-1">Validity (Months)</label>
                        <input 
                           type="number" 
                           placeholder="Forever"
                           className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none"
                           value={data.certificate.validityMonths || ''}
                           onChange={(e) => updateCert('validityMonths', parseInt(e.target.value) || undefined)}
                        />
                     </div>
                     <div className="flex items-center pt-6">
                        <label className="flex items-center gap-2 cursor-pointer">
                           <input 
                              type="checkbox" 
                              className="rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                              checked={data.certificate.showScore}
                              onChange={(e) => updateCert('showScore', e.target.checked)}
                           />
                           <span className="text-sm text-slate-700">Show Final Score on Certificate</span>
                        </label>
                     </div>
                  </div>

                  <div className="border border-slate-200 rounded-lg p-4 bg-slate-50 text-center">
                     <p className="text-sm text-slate-500 mb-2">Preview</p>
                     <div className="aspect-video bg-white border-4 border-slate-200 mx-auto max-w-md flex items-center justify-center shadow-sm">
                        <div className="text-center">
                           <Award size={48} className="mx-auto text-brand-200 mb-2" />
                           <h4 className="font-serif text-xl text-slate-800">Certificate of Completion</h4>
                           <p className="text-xs text-slate-400 mt-1">Template: {data.certificate.templateId}</p>
                        </div>
                     </div>
                  </div>
               </div>
            )}
         </section>

         {/* Badge Section */}
         <section className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
             <h3 className="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                <Shield size={20} className="text-indigo-600" /> Awarded Badge / Certification
             </h3>
             <div className="space-y-4">
                 <label className="block text-sm font-medium text-slate-700">Select Badge awarded on course completion</label>
                 <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                     {badges.map(b => (
                         <div 
                            key={b.id}
                            onClick={() => updateBadge(b.id)}
                            className={`p-3 rounded-lg border cursor-pointer flex items-center gap-3 transition-all ${
                                data.awardedBadgeId === b.id 
                                    ? 'bg-indigo-50 border-indigo-500 ring-1 ring-indigo-500'
                                    : 'bg-white border-slate-200 hover:border-indigo-200'
                            }`}
                         >
                             <div className={`p-2 rounded-full ${b.color}`}>
                                {b.icon === 'Shield' && <Shield size={16} />}
                                {b.icon === 'Award' && <Award size={16} />}
                                {b.icon === 'Check' && <Check size={16} />}
                                {b.icon === 'Activity' && <Activity size={16} />}
                                {b.icon === 'Heart' && <Heart size={16} />}
                             </div>
                             <span className="font-medium text-sm text-slate-700">{b.name}</span>
                         </div>
                     ))}
                     <div 
                        onClick={() => updateBadge('')}
                        className={`p-3 rounded-lg border cursor-pointer flex items-center justify-center transition-all ${
                            !data.awardedBadgeId 
                                ? 'bg-slate-100 border-slate-400 ring-1 ring-slate-400'
                                : 'bg-white border-slate-200 hover:border-slate-300'
                        }`}
                     >
                         <span className="text-sm text-slate-500">No Badge</span>
                     </div>
                 </div>
             </div>
         </section>
      </div>
   );
};

// --- MODAL EDITORS ---


export default CourseEditor;
