
import React, { useState } from 'react';
import { Plus, Settings, Search, Filter, ChevronDown, Paperclip, Edit2, BookOpen } from 'lucide-react';
import { useApp } from '../../../context/AppContext';
import { Lesson, LessonGroup, ExtendedLesson } from '../../../types';

// Import initial data
const initialLessonGroups: LessonGroup[] = [
    { id: 'lg1', title: 'Basics' },
    { id: 'lg2', title: 'Advanced Techniques' },
    { id: 'lg3', title: 'Theory & Background' }
];

// Importing sub-components (will be defined separately)
import LessonEditor from '../editors/LessonEditor';
import GroupManagerModal from '../components/GroupManagerModal';

const LessonsTab: React.FC = () => {
    const { lessons, setLessons } = useApp();
    const [groups, setGroups] = useState<LessonGroup[]>(initialLessonGroups);
    const [selectedGroupId, setSelectedGroupId] = useState<string>('all'); 
    const [searchTerm, setSearchTerm] = useState('');
    const [editingLesson, setEditingLesson] = useState<ExtendedLesson | null>(null);
    const [isGroupManagerOpen, setIsGroupManagerOpen] = useState(false);

    const handleCreateLesson = () => {
        const newLesson: ExtendedLesson = { 
            id: `l_${Date.now()}`, 
            type: 'lesson', 
            title: 'New Untitled Lesson', 
            content: '', 
            mediaUrls: [], 
            fileAttachments: [],
            groupId: selectedGroupId !== 'all' ? selectedGroupId : undefined,
            structuredAttachments: []
        };
        setEditingLesson(newLesson);
    };

    const handleSaveLesson = (updatedLesson: ExtendedLesson) => {
        const standardLesson: Lesson = {
            ...updatedLesson,
            mediaUrls: updatedLesson.structuredAttachments?.filter(a => a.type !== 'document').map(a => a.url) || [],
            fileAttachments: updatedLesson.structuredAttachments?.filter(a => a.type === 'document').map(a => a.url) || []
        };

        if (lessons.find(l => l.id === updatedLesson.id)) {
            setLessons(lessons.map(l => l.id === updatedLesson.id ? standardLesson : l));
        } else {
            setLessons([...lessons, standardLesson]);
        }
        setEditingLesson(null);
    };

    // Group Management Logic moved to Modal
    const handleAddGroup = (title: string) => {
        setGroups([...groups, { id: `lg_${Date.now()}`, title }]);
    };

    const handleDeleteGroup = (id: string) => {
        setGroups(groups.filter(g => g.id !== id));
        if (selectedGroupId === id) setSelectedGroupId('all');
    };

    const filteredLessons = lessons.filter(l => {
        const matchesSearch = l.title.toLowerCase().includes(searchTerm.toLowerCase());
        const lessonWithGroup = l as ExtendedLesson;
        const matchesGroup = selectedGroupId === 'all' ? true : lessonWithGroup.groupId === selectedGroupId;
        return matchesSearch && matchesGroup;
    });

    if (editingLesson) {
        return <LessonEditor 
            lesson={editingLesson} 
            groups={groups}
            onSave={handleSaveLesson} 
            onCancel={() => setEditingLesson(null)} 
        />;
    }

    return (
        <div className="flex flex-col h-full space-y-6">
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h2 className="text-xl font-bold text-slate-800">Lessons Library</h2>
                    <p className="text-sm text-slate-500">Manage and organize your teaching content.</p>
                </div>
                <div className="flex gap-2">
                    <button 
                        onClick={() => setIsGroupManagerOpen(true)}
                        className="p-2 bg-white border border-slate-300 text-slate-600 hover:bg-slate-50 rounded-lg shadow-sm transition-colors"
                        title="Manage Groups"
                    >
                        <Settings size={20} />
                    </button>
                    <button 
                        onClick={handleCreateLesson}
                        className="bg-brand-600 border border-transparent text-white hover:bg-brand-700 px-4 py-2 rounded-lg shadow-sm flex items-center gap-2 font-medium transition-colors"
                    >
                        <Plus size={18} /> Create Lesson
                    </button>
                </div>
            </div>

            <div className="bg-white rounded-xl border border-slate-200 shadow-sm flex flex-col flex-1 overflow-hidden">
                {/* Filter Toolbar */}
                <div className="p-4 border-b border-slate-100 flex gap-4 items-center">
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
                    
                    <div className="relative min-w-[180px]">
                        <div className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 pointer-events-none">
                            <Filter size={16} />
                        </div>
                        <select
                            className="w-full pl-9 pr-8 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 bg-white appearance-none cursor-pointer"
                            value={selectedGroupId}
                            onChange={(e) => setSelectedGroupId(e.target.value)}
                        >
                            <option value="all">All Groups</option>
                            {groups.map(g => (
                                <option key={g.id} value={g.id}>{g.title}</option>
                            ))}
                        </select>
                        <ChevronDown size={14} className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                    </div>
                </div>

                {/* Lesson List */}
                <div className="overflow-y-auto flex-1">
                    <table className="w-full text-left">
                        <thead className="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase sticky top-0">
                            <tr>
                                <th className="p-4">Lesson Title</th>
                                <th className="p-4">Group</th>
                                <th className="p-4">Attachments</th>
                                <th className="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {filteredLessons.map(lesson => (
                                <tr key={lesson.id} className="hover:bg-slate-50 group">
                                    <td className="p-4">
                                        <div className="font-medium text-slate-800">{lesson.title}</div>
                                        <div className="text-xs text-slate-400 line-clamp-1 max-w-md">
                                            {lesson.content ? lesson.content.replace(/<[^>]*>?/gm, '') : 'No content'}
                                        </div>
                                    </td>
                                    <td className="p-4 text-sm text-slate-500">
                                        {(lesson as ExtendedLesson).groupId 
                                            ? <span className="bg-indigo-50 text-indigo-700 px-2 py-1 rounded text-xs font-medium border border-indigo-100">{groups.find(g => g.id === (lesson as ExtendedLesson).groupId)?.title}</span>
                                            : <span className="text-slate-400 italic text-xs">Unassigned</span>}
                                    </td>
                                    <td className="p-4 text-sm text-slate-500">
                                        <div className="flex items-center gap-2">
                                            {(lesson.mediaUrls?.length > 0 || lesson.fileAttachments?.length > 0) ? (
                                                <>
                                                    <Paperclip size={14} />
                                                    {(lesson.mediaUrls?.length || 0) + (lesson.fileAttachments?.length || 0)} items
                                                </>
                                            ) : '-'}
                                        </div>
                                    </td>
                                    <td className="p-4 text-right">
                                        <button 
                                            onClick={() => setEditingLesson(lesson as ExtendedLesson)}
                                            className="p-2 text-slate-400 hover:text-brand-600 rounded-full hover:bg-brand-50 transition-colors"
                                        >
                                            <Edit2 size={16} />
                                        </button>
                                    </td>
                                </tr>
                            ))}
                            {filteredLessons.length === 0 && (
                                <tr>
                                    <td colSpan={4} className="p-12 text-center text-slate-400">
                                        <div className="flex flex-col items-center">
                                            <BookOpen size={48} className="opacity-20 mb-4" />
                                            <p>No lessons found matching your filters.</p>
                                        </div>
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Group Manager Modal */}
            {isGroupManagerOpen && (
                <GroupManagerModal 
                    groups={groups} 
                    onAdd={handleAddGroup} 
                    onDelete={handleDeleteGroup} 
                    onClose={() => setIsGroupManagerOpen(false)} 
                />
            )}
        </div>
    );
};

export default LessonsTab;
