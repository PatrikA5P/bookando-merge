import React from 'react';
import { Clock, Calendar, Video, Tag, Eye, Edit2, Trash2 } from 'lucide-react';
import { ServiceItem } from '../../../types';

interface CatalogTabProps {
    services: ServiceItem[];
    onEdit: (service: ServiceItem) => void;
    onDelete: (id: string) => void;
    onToggleStatus: (id: string) => void;
}

const CatalogTab: React.FC<CatalogTabProps> = ({ services, onEdit, onDelete, onToggleStatus }) => {

    const getTypeIcon = (type: string) => {
        switch (type) {
            case 'Service': return <Clock size={14} />;
            case 'Event': return <Calendar size={14} />;
            case 'Online Course': return <Video size={14} />;
            default: return <Tag size={14} />;
        }
    };

    const getTypeColor = (type: string) => {
        switch (type) {
            case 'Service': return 'bg-blue-50 text-blue-700 border-blue-200';
            case 'Event': return 'bg-purple-50 text-purple-700 border-purple-200';
            case 'Online Course': return 'bg-amber-50 text-amber-700 border-amber-200';
            default: return 'bg-slate-50 text-slate-700 border-slate-200';
        }
    };

    return (
        <div className="flex-1 overflow-y-auto p-6">
            <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                {services.map((service) => (
                    <div key={service.id} className="group bg-white rounded-xl border border-slate-200 shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col overflow-hidden">
                        <div className="relative h-40 overflow-hidden bg-slate-100">
                            <img src={service.image} alt={service.title} className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" />
                            <div className="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-60"></div>
                            <div className="absolute top-3 right-3">
                                <button
                                    onClick={(e) => { e.stopPropagation(); onToggleStatus(service.id); }}
                                    className={`flex items-center gap-1 text-[10px] font-bold px-2 py-1 rounded-full shadow-sm backdrop-blur-sm transition-colors ${
                                        service.active ? 'bg-emerald-500/90 text-white hover:bg-emerald-600' : 'bg-slate-500/90 text-white hover:bg-slate-600'
                                    }`}
                                >
                                    {service.active ? 'ACTIVE' : 'HIDDEN'}
                                </button>
                            </div>
                            <div className="absolute bottom-3 left-3">
                                <span className={`flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold shadow-sm border backdrop-blur-md ${getTypeColor(service.type)} bg-white/95`}>
                                    {getTypeIcon(service.type)}
                                    {service.type}
                                </span>
                            </div>
                        </div>

                        <div className="p-5 flex-1 flex flex-col">
                            <div className="flex justify-between items-start mb-2">
                                <div className="flex flex-wrap gap-1">
                                    {service.categories?.map(cat => (
                                        <span key={cat} className="text-[10px] font-bold text-slate-500 uppercase tracking-wider border border-slate-200 px-1.5 rounded bg-slate-50">{cat}</span>
                                    ))}
                                    {!service.categories && <span className="text-[10px] font-bold text-slate-500 uppercase tracking-wider">{service.category}</span>}
                                </div>
                                <div className="font-bold text-lg text-slate-900">
                                    {service.price} {service.currency}
                                    {service.salePrice ? <span className="text-xs text-rose-500 ml-2 line-through font-normal">Sale</span> : null}
                                </div>
                            </div>
                            <h3 className="font-bold text-slate-800 text-lg mb-2 leading-tight group-hover:text-brand-600 transition-colors line-clamp-1">{service.title}</h3>
                            <div className="text-xs text-slate-400 mb-2 font-mono">SKU: {service.productCode || 'N/A'}</div>
                            {/* Description Preview - Strip HTML */}
                            <p className="text-slate-500 text-sm mb-4 line-clamp-2 flex-1">
                                {service.description.replace(/<[^>]*>?/gm, '')}
                            </p>
                            <div className="mt-auto pt-4 border-t border-slate-100 space-y-2">
                                {service.type === 'Service' && <div className="flex items-center gap-2 text-sm text-slate-600"><Clock size={16} className="text-slate-400"/><span>{service.duration} minutes</span></div>}
                                {service.type === 'Event' && <div className="flex items-center gap-2 text-sm text-slate-600"><Calendar size={16} className="text-slate-400"/><span>{service.eventStructure === 'Single' ? 'One-time Event' : 'Series/Course'}</span></div>}
                                {service.type === 'Online Course' && <div className="flex items-center gap-2 text-sm text-slate-600"><Video size={16} className="text-slate-400"/><span>{service.lessons} Video Lessons</span></div>}
                            </div>
                        </div>

                        <div className="bg-slate-50 p-3 border-t border-slate-100 flex justify-between items-center">
                            <button className="text-xs font-medium text-slate-500 hover:text-slate-800 flex items-center gap-1">
                                <Eye size={14} /> Preview
                            </button>
                            <div className="flex gap-2">
                                <button onClick={() => onEdit(service)} className="p-1.5 text-slate-400 hover:text-brand-600 hover:bg-white rounded-md transition-colors"><Edit2 size={16} /></button>
                                <button onClick={() => onDelete(service.id)} className="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-white rounded-md transition-colors"><Trash2 size={16} /></button>
                            </div>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
};

export default CatalogTab;
