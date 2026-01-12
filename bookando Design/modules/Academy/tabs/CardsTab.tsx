import React from 'react';
import { Plus, Edit2, Trash2, CreditCard, List, Zap } from 'lucide-react';
import { EducationCardTemplate } from '../../../types';

interface CardsTabProps {
    cards: EducationCardTemplate[];
    onCreateCard: () => void;
    onEditCard: (cardId: string) => void;
    onDeleteCard: (cardId: string) => void;
}

const CardsTab: React.FC<CardsTabProps> = ({ cards, onCreateCard, onEditCard, onDeleteCard }) => {
    return (
        <div className="space-y-6">
            <div className="flex justify-between items-center">
                <h2 className="text-xl font-bold text-slate-800">Education Cards</h2>
                <button
                    onClick={onCreateCard}
                    className="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg shadow-sm transition-colors flex items-center gap-2 font-medium"
                >
                    <Plus size={18} /> New Template
                </button>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {cards.map(card => (
                    <div key={card.id} className="bg-white rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-all p-5 flex flex-col group">
                        <div className="flex justify-between items-start mb-4">
                            <div className="p-3 bg-indigo-50 text-indigo-600 rounded-lg">
                                <CreditCard size={24} />
                            </div>
                            <div className="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button onClick={() => onEditCard(card.id)} className="p-2 text-slate-400 hover:text-brand-600 hover:bg-slate-50 rounded-lg transition-colors">
                                    <Edit2 size={16} />
                                </button>
                                <button onClick={() => onDeleteCard(card.id)} className="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors">
                                    <Trash2 size={16} />
                                </button>
                            </div>
                        </div>
                        <h3 className="font-bold text-slate-800 text-lg mb-1">{card.title}</h3>
                        <p className="text-sm text-slate-500 mb-4 flex-1">{card.description || 'No description.'}</p>
                        <div className="flex items-center gap-4 text-xs text-slate-500 pt-4 border-t border-slate-50">
                            <span className="flex items-center gap-1"><List size={14}/> {card.chapters.length} Chapters</span>
                            <span className="flex items-center gap-1"><Zap size={14} className={card.automation.enabled ? 'text-amber-500' : 'text-slate-300'} /> {card.automation.enabled ? 'Auto-Assign' : 'Manual'}</span>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
};

export default CardsTab;
