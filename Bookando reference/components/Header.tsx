import React from 'react';
import { Search, Bell, HelpCircle } from 'lucide-react';
import { useApp } from '../context/AppContext';

interface HeaderProps {
  title: string;
}

const Header: React.FC<HeaderProps> = ({ title }) => {
  const { t } = useApp();

  return (
    <header className="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-4 md:px-8 sticky top-0 z-10 shadow-sm">
      <div className="flex items-center gap-4">
        <h1 className="text-xl font-semibold text-slate-800">{t(title)}</h1>
      </div>

      <div className="flex items-center gap-2 md:gap-4">
        {/* Search Bar */}
        <div className="relative hidden md:block">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
          <input 
            type="text" 
            placeholder={t('search')} 
            className="pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent w-64 transition-all"
          />
          <div className="absolute right-3 top-1/2 -translate-y-1/2 flex gap-1">
            <span className="text-xs text-slate-400 border border-slate-200 rounded px-1.5 bg-white">⌘K</span>
          </div>
        </div>

        {/* Actions */}
        <div className="flex items-center gap-1">
          <button className="p-2 text-slate-500 hover:bg-slate-100 rounded-full transition-colors relative">
            <Bell size={20} />
            <span className="absolute top-2 right-2 w-2 h-2 bg-rose-500 rounded-full border border-white"></span>
          </button>
          <button className="p-2 text-slate-500 hover:bg-slate-100 rounded-full transition-colors">
            <HelpCircle size={20} />
          </button>
        </div>
      </div>
    </header>
  );
};

export default Header;