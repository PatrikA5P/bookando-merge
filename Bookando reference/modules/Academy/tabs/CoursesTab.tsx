
import React from 'react';
import { Users, List, Image as ImageIcon } from 'lucide-react';
import { Course } from '../../../types';

interface CoursesTabProps {
    courses: Course[];
    onEditCourse: (courseId: string) => void;
}

const CoursesTab: React.FC<CoursesTabProps> = ({ courses, onEditCourse }) => {
    return (
        <div className="p-6">
            <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                {courses.map(course => (
                    <div
                        key={course.id}
                        onClick={() => onEditCourse(course.id)}
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
                            <div className="text-xs font-semibold text-brand-600 mb-1 uppercase tracking-wide">{course.category || 'Uncategorized'}</div>
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
    );
};

export default CoursesTab;
