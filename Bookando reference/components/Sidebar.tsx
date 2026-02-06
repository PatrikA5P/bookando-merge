import React from 'react';
import { NavItem, ModuleName } from '../types';
import { ChevronRight, Command, Menu } from 'lucide-react';
import { useApp } from '../context/AppContext';

interface SidebarProps {
  navItems: NavItem[];
  activeModule: ModuleName;
  onNavigate: (module: ModuleName) => void;
  isOpen: boolean;
  toggleSidebar: () => void;
}

const Sidebar: React.FC<SidebarProps> = ({ navItems, activeModule, onNavigate, isOpen, toggleSidebar }) => {
  const { enabledModules, t } = useApp();

  // Filter nav items based on enabled modules from settings
  const visibleItems = navItems.filter(item => enabledModules.includes(item.name));

  return (
    <aside 
      className={`
        bg-slate-900 text-white transition-all duration-300 ease-in-out flex border-slate-800 z-50
        md:border-r md:h-screen md:static
        flex-col w-full sticky top-0 border-b
        ${isOpen ? 'md:w-64' : 'md:w-20'}
      `}
    >
      {/* Logo Area & Toggle */}
      <div className={`
        h-16 flex items-center px-4 border-b border-slate-800 shrink-0 transition-all
        ${isOpen ? 'justify-between' : 'md:justify-center justify-between'}
      `}>
        {/* Logo & Title: Hidden on Desktop if Collapsed, Visible on Mobile or if Open */}
        <div className={`
          flex items-center gap-3 overflow-hidden transition-opacity duration-200
          ${!isOpen ? 'md:hidden md:w-0 md:opacity-0' : 'w-auto opacity-100'}
        `}>
          <div className="bg-brand-600 p-1.5 rounded-lg shadow-lg shadow-brand-900/50 shrink-0">
            <Command size={20} className="text-white" />
          </div>
          <span className="font-bold text-lg tracking-tight whitespace-nowrap text-slate-100">
            Bookando
          </span>
        </div>

        {/* Hamburger Menu */}
        <button 
          onClick={toggleSidebar}
          className="p-2 hover:bg-slate-800 rounded-lg text-slate-400 hover:text-white transition-colors focus:outline-none"
        >
          <Menu size={20} />
        </button>
      </div>

      {/* Navigation Items Container */}
      <div className={`
        flex flex-col flex-1 overflow-hidden transition-all duration-300
        ${isOpen ? 'max-h-[calc(100vh-4rem)] opacity-100' : 'max-h-0 opacity-0 md:max-h-full md:opacity-100'}
      `}>
        
        {/* Scrollable Nav List */}
        <nav className="flex-1 overflow-y-auto overflow-x-hidden scrollbar-hide py-4">
          <ul className={`space-y-1 ${isOpen ? 'px-3' : 'px-2'}`}>
            {visibleItems.map((item) => {
              const isActive = activeModule === item.name;
              const translatedName = t(item.name);

              return (
                <li key={item.name}>
                  <button
                    onClick={() => {
                      onNavigate(item.name);
                      // On mobile, close menu after selection
                      if (window.innerWidth < 768) toggleSidebar();
                    }}
                    className={`
                      w-full flex items-center py-2.5 rounded-lg transition-all duration-200 group relative
                      ${isOpen 
                        ? 'gap-3 px-3 justify-start' 
                        : 'justify-center px-0' // Center icons when collapsed
                      }
                      ${isActive 
                        ? 'bg-brand-600 text-white shadow-md shadow-brand-900/20' 
                        : 'text-slate-400 hover:bg-slate-800 hover:text-slate-100'
                      }
                    `}
                  >
                    <item.icon 
                      size={20} 
                      className={`shrink-0 ${isActive ? 'text-white' : 'text-slate-400 group-hover:text-white'}`} 
                    />
                    
                    {/* Text: Hidden if collapsed on Desktop */}
                    <span className={`
                      text-sm font-medium whitespace-nowrap flex-1 text-left transition-opacity duration-200
                      ${!isOpen ? 'md:hidden md:opacity-0 md:w-0' : 'opacity-100'}
                    `}>
                      {translatedName}
                    </span>

                    {/* Active Indicator Dot (Desktop Collapsed Only) */}
                    {!isOpen && isActive && (
                      <div className="hidden md:block absolute top-2 right-2 w-1.5 h-1.5 rounded-full bg-brand-400" />
                    )}

                    {/* Notification Badge - Only visible when open */}
                    {item.badge && isOpen && (
                      <span className={`
                        text-xs font-bold px-2 py-0.5 rounded-full
                        ${isActive ? 'bg-brand-500 text-white' : 'bg-slate-700 text-slate-300'}
                      `}>
                        {item.badge}
                      </span>
                    )}
                    
                    {/* Tooltip for collapsed state (Desktop only) */}
                    {!isOpen && (
                      <div className="hidden md:block absolute left-14 ml-2 px-2 py-1 bg-slate-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 shadow-lg border border-slate-700 transition-opacity">
                        {translatedName}
                      </div>
                    )}
                  </button>
                </li>
              );
            })}
          </ul>
        </nav>

        {/* User Profile / Footer - Pinned to bottom */}
        <div className={`
          p-4 border-t border-slate-800 mt-auto bg-slate-900
          ${!isOpen ? 'md:hidden' : ''}
        `}>
          <button className="flex items-center gap-3 w-full hover:bg-slate-800 p-2 rounded-lg transition-colors">
            <img 
              src="https://picsum.photos/100/100" 
              alt="Admin User" 
              className="w-8 h-8 rounded-full border-2 border-slate-700 shrink-0"
            />
            <div className="flex-1 text-left overflow-hidden">
              <p className="text-sm font-medium text-slate-200 truncate">Admin User</p>
              <p className="text-xs text-slate-500 truncate">admin@bookando.com</p>
            </div>
            <ChevronRight size={16} className="text-slate-500" />
          </button>
        </div>
      </div>
    </aside>
  );
};

export default Sidebar;