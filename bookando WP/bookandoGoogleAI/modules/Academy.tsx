
import React, { useState } from 'react';
import { 
  Course, CourseType, CourseVisibility, DifficultyLevel, Topic, Lesson, Quiz, 
  Question, QuestionType, QuizSettings, CertificateSettings, Badge
} from '../types';
import { 
  BookOpen, Users, Award, PlayCircle, MoreVertical, Search, Plus, 
  ArrowLeft, Save, Layout, List, Settings, Image as ImageIcon, Video, 
  FileText, Trash2, Edit2, GripVertical, CheckSquare, CheckCircle, 
  HelpCircle, ChevronDown, ChevronRight, Move, Clock, Eye, MonitorPlay,
  Sliders, AlignLeft, Type, Puzzle, X, Shield, Activity, Heart, Check, CreditCard,
  GraduationCap, LayoutGrid, Book
} from 'lucide-react';
import { useApp } from '../context/AppContext';

// --- Component ---

const AcademyModule: React.FC = () => {
  const [view, setView] = useState<'list' | 'editor'>('list');
  const [activeTab, setActiveTab] = useState<'courses' | 'lessons' | 'badges'>('courses');
  const [selectedCourseId, setSelectedCourseId] = useState<string | null>(null);
  const { courses, setCourses } = useApp();

  const handleEditCourse = (courseId: string) => {
    setSelectedCourseId(courseId);
    setView('editor');
  };

  const handleCreateCourse = () => {
    const newCourse: Course = {
      id: `new_${Date.now()}`,
      title: 'New Untitled Course',
      description: '',
      type: CourseType.ONLINE,
      author: 'Current User',
      visibility: CourseVisibility.PRIVATE,
      category: 'Uncategorized',
      tags: [],
      difficulty: DifficultyLevel.BEGINNER,
      coverImage: '',
      studentsCount: 0,
      published: false,
      certificate: { enabled: false, templateId: 'default', showScore: true, signatureText: '' },
      curriculum: []
    };
    setCourses([...courses, newCourse]);
    setSelectedCourseId(newCourse.id);
    setView('editor');
  };

  const handleSaveCourse = (updatedCourse: Course) => {
    setCourses(courses.map(c => c.id === updatedCourse.id ? updatedCourse : c));
  };

  const handleBack = () => {
    setView('list');
    setSelectedCourseId(null);
  };

  // Editor View takes over full screen content area
  if (view === 'editor' && selectedCourseId) {
    const course = courses.find(c => c.id === selectedCourseId);
    if (course) {
      return <CourseEditor course={course} onSave={handleSaveCourse} onBack={handleBack} />;
    }
  }

  return (
    <div className="flex flex-col md:flex-row h-full gap-6">
        {/* Sidebar Navigation (Resources Style) */}
        <div className="w-full md:w-64 lg:w-72 flex-shrink-0 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden h-fit">
            <div className="p-4 border-b border-slate-100 bg-slate-50">
                <h2 className="text-base md:text-lg font-bold text-slate-800">Academy</h2>
                <p className="text-xs text-slate-500">Learning Management</p>
            </div>
            <nav className="p-2 space-y-1">
                {[
                    { id: 'courses', icon: LayoutGrid, label: 'Courses' },
                    { id: 'lessons', icon: Book, label: 'Lessons' },
                    { id: 'badges', icon: Award, label: 'Badges & Certs' },
                ].map(tab => (
                    <button
                        key={tab.id}
                        onClick={() => setActiveTab(tab.id as any)}
                        className={`w-full text-left px-4 py-2.5 rounded-md text-sm font-medium flex items-center gap-3 transition-all ${
                            activeTab === tab.id ? 'bg-brand-50 text-brand-700 shadow-sm ring-1 ring-brand-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
                        }`}
                    >
                        <tab.icon size={18} /> {tab.label}
                    </button>
                ))}
            </nav>
        </div>

        {/* Main Content */}
        <div className="flex-1 min-w-0 animate-fadeIn">
            {/* Courses Tab */}
            {activeTab === 'courses' && (
                <div className="space-y-6">
                    <div className="flex justify-between items-center">
                        <h2 className="text-xl font-bold text-slate-800">All Courses</h2>
                        <button 
                            onClick={handleCreateCourse}
                            className="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg shadow-sm transition-colors flex items-center gap-2 font-medium"
                        >
                            <Plus size={18} /> Create Course
                        </button>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                        {courses.map(course => (
                        <div 
                            key={course.id} 
                            onClick={() => handleEditCourse(course.id)}
                            className="group bg-white rounded-xl border border-slate-200 shadow-sm hover:shadow-lg hover:border-brand-200 transition-all cursor-pointer overflow-hidden flex flex-col"
                        >
                            <div className="h-40 bg-slate-100 relative overflow-hidden">
                            {course.coverImage ? (
                                <img src={course.coverImage} alt={course.title} className="w-full h-full object-cover" />
                            ) : (
                                <div className="w-full h-full flex items-center justify-center bg-slate-100 text-slate-300">
                                <ImageIcon size={48} />
                                </div>
                            )}
                            <div className="absolute top-3 right-3">
                                {course.published ? (
                                    <span className="bg-emerald-500/90 text-white text-xs font-bold px-2 py-1 rounded backdrop-blur-sm">PUBLISHED</span>
                                ) : (
                                    <span className="bg-slate-500/90 text-white text-xs font-bold px-2 py-1 rounded backdrop-blur-sm">DRAFT</span>
                                )}
                            </div>
                            </div>
                            <div className="p-5 flex-1 flex flex-col">
                            <div className="text-xs font-semibold text-brand-600 mb-1 uppercase tracking-wide">{course.category}</div>
                            <h3 className="text-lg font-bold text-slate-800 mb-2 leading-tight group-hover:text-brand-600 transition-colors">{course.title}</h3>
                            <p className="text-sm text-slate-500 line-clamp-2 mb-4 flex-1">{course.description || 'No description provided.'}</p>
                            
                            <div className="flex items-center justify-between pt-4 border-t border-slate-100 text-xs text-slate-500">
                                <span className="flex items-center gap-1"><Users size={14} /> {course.studentsCount} Students</span>
                                <span className="flex items-center gap-1"><List size={14} /> {course.curriculum.length} Modules</span>
                            </div>
                            </div>
                        </div>
                        ))}
                    </div>
                </div>
            )}

            {/* Lessons Tab */}
            {activeTab === 'lessons' && <LessonsTab />}

            {/* Badges Tab */}
            {activeTab === 'badges' && <BadgeManager />}
        </div>
    </div>
  );
};

// --- LESSONS TAB ---

const LessonsTab: React.FC = () => {
    const { lessons, setLessons } = useApp();
    const [searchTerm, setSearchTerm] = useState('');

    const filteredLessons = lessons.filter(l => l.title.toLowerCase().includes(searchTerm.toLowerCase()));

    return (
        <div className="space-y-6">
            <div className="flex justify-between items-center">
                <h2 className="text-xl font-bold text-slate-800">Lesson Repository</h2>
                <button className="bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 px-4 py-2 rounded-lg shadow-sm flex items-center gap-2 font-medium">
                    <Plus size={18} /> Create Lesson
                </button>
            </div>

            <div className="bg-white rounded-xl border border-slate-200 shadow-sm flex flex-col">
                <div className="p-4 border-b border-slate-100 flex gap-4">
                    <div className="relative flex-1 max-w-md">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={16} />
                        <input 
                            type="text" 
                            placeholder="Search lessons..." 
                            value={searchTerm}
                            onChange={e => setSearchTerm(e.target.value)}
                            className="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                        />
                    </div>
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full text-left">
                        <thead className="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase">
                            <tr>
                                <th className="p-4">Lesson Title</th>
                                <th className="p-4">Type</th>
                                <th className="p-4">Media</th>
                                <th className="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {filteredLessons.map(lesson => (
                                <tr key={lesson.id} className="hover:bg-slate-50 group">
                                    <td className="p-4 font-medium text-slate-800">{lesson.title}</td>
                                    <td className="p-4 text-sm text-slate-500 capitalize">{lesson.type}</td>
                                    <td className="p-4 text-sm text-slate-500">{lesson.mediaUrls?.length || 0} items</td>
                                    <td className="p-4 text-right">
                                        <button className="p-2 text-slate-400 hover:text-brand-600 rounded-full hover:bg-brand-50">
                                            <Edit2 size={16} />
                                        </button>
                                    </td>
                                </tr>
                            ))}
                            {filteredLessons.length === 0 && (
                                <tr>
                                    <td colSpan={4} className="p-8 text-center text-slate-400">No lessons found.</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
};

// --- BADGE MANAGER ---

const BadgeManager: React.FC = () => {
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

// --- EDITOR SUB-COMPONENTS ---

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
         return <LessonEditor lesson={editingItem.item as Lesson} onSave={handleSaveItem} onCancel={() => setEditingItem(null)} />;
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

const LessonEditor: React.FC<{ lesson: Lesson; onSave: (l: Lesson) => void; onCancel: () => void }> = ({ lesson, onSave, onCancel }) => {
   const [data, setData] = useState<Lesson>(lesson);

   return (
      <div className="bg-white rounded-xl border border-slate-200 shadow-lg p-6 animate-fadeIn">
         <div className="flex justify-between items-center mb-6 pb-4 border-b border-slate-100">
            <h3 className="text-xl font-bold text-slate-800">Edit Lesson</h3>
            <button onClick={onCancel} className="text-slate-400 hover:text-slate-600"><X size={24} /></button>
         </div>
         
         <div className="space-y-4">
            <div>
               <label className="label">Lesson Name</label>
               <input 
                  className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none"
                  value={data.title}
                  onChange={e => setData({...data, title: e.target.value})}
               />
            </div>
            <div>
               <label className="label">Content</label>
               <textarea 
                  className="w-full border border-slate-300 rounded-lg px-3 py-2 h-48 focus:ring-2 focus:ring-brand-500 outline-none font-mono text-sm"
                  value={data.content}
                  onChange={e => setData({...data, content: e.target.value})}
                  placeholder="<div>Enter HTML or rich text here...</div>"
               />
            </div>
            
            <div className="grid grid-cols-2 gap-4">
               <div>
                  <label className="label">Images & Media (URLs)</label>
                  <div className="flex gap-2">
                     <input placeholder="https://..." className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" />
                     <button className="p-2 bg-slate-100 rounded-lg"><Plus size={16} /></button>
                  </div>
               </div>
               <div>
                  <label className="label">Files / Attachments</label>
                  <div className="flex gap-2">
                     <input placeholder="Upload or URL..." className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" />
                     <button className="p-2 bg-slate-100 rounded-lg"><Plus size={16} /></button>
                  </div>
               </div>
            </div>

            <div className="flex justify-end gap-3 mt-6">
               <button onClick={onCancel} className="px-4 py-2 text-slate-600 hover:bg-slate-50 rounded-lg">Cancel</button>
               <button onClick={() => onSave(data)} className="px-6 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700">Save Lesson</button>
            </div>
         </div>
         <style>{`.label { @apply block text-sm font-medium text-slate-700 mb-1; }`}</style>
      </div>
   );
};

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

export default AcademyModule;
