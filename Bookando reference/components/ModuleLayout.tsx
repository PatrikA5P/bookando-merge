import React, { useState, useEffect } from 'react';
import { LucideIcon, Search, Filter, Plus } from 'lucide-react';

export type LayoutVariant = 'app' | 'mixed';

interface NavTab {
  id: string;
  label: string;
  icon: LucideIcon;
  badge?: number;
}

interface ModuleLayoutProps {
  variant?: LayoutVariant;
  moduleName: string;
  hero: {
    icon: LucideIcon;
    title: string;
    description: string;
    gradient?: string; 
    image?: string;
  };
  tabs?: NavTab[];
  activeTab?: string;
  onTabChange?: (tabId: string) => void;

  // Header Actions
  searchQuery?: string;
  onSearchChange?: (val: string) => void;
  showFilter?: boolean;
  onToggleFilter?: () => void;
  filterContent?: React.ReactNode;

  actions?: React.ReactNode;
  primaryAction?: {
    label: string;
    icon?: LucideIcon;
    onClick: () => void;
  };

  children: React.ReactNode;
}

// Hook to detect scroll direction within the main app container
function useScrollDirection() {
  const [scrollDirection, setScrollDirection] = useState<'up' | 'down' | null>(null);
  const [scrolledToTop, setScrolledToTop] = useState(true);

  useEffect(() => {
    // Target the main scrollable area of the app layout
    const mainContainer = document.querySelector('main');
    if (!mainContainer) return;

    let lastScrollY = mainContainer.scrollTop;

    const updateScrollDirection = () => {
      const scrollY = mainContainer.scrollTop;
      const direction = scrollY > lastScrollY ? 'down' : 'up';
      
      // Add a small threshold to prevent jitter
      if (direction !== scrollDirection && Math.abs(scrollY - lastScrollY) > 5) {
        setScrollDirection(direction);
      }
      setScrolledToTop(scrollY < 10);
      lastScrollY = scrollY > 0 ? scrollY : 0;
    };

    mainContainer.addEventListener("scroll", updateScrollDirection);
    return () => {
      mainContainer.removeEventListener("scroll", updateScrollDirection);
    }
  }, [scrollDirection]);

  return { scrollDirection, scrolledToTop };
}

const ModuleLayout: React.FC<ModuleLayoutProps> = ({
  variant = 'mixed',
  moduleName,
  hero,
  tabs = [],
  activeTab,
  onTabChange,
  searchQuery,
  onSearchChange,
  showFilter,
  onToggleFilter,
  filterContent,
  actions,
  primaryAction,
  children
}) => {
  const { scrollDirection, scrolledToTop } = useScrollDirection();
  // Header is visible if we are scrolling up OR if we are at the very top
  const isHeaderVisible = scrollDirection === 'up' || scrolledToTop;
  
  // Determine if we have tabs to show sidebar
  const hasTabs = tabs && tabs.length > 0;

  // --- RENDER FUNCTIONS ---

  const renderHeroSection = (isBanner = false, fullHeight = false) => (
    <div className={`
      relative overflow-hidden shrink-0 transition-all
      ${variant === 'mixed' ? 'bg-gradient-to-br ' + (hero.gradient || 'from-slate-800 to-slate-900') + ' text-white p-6 shadow-lg' : ''}
      ${variant === 'app' ? 'bg-white p-6 rounded-3xl shadow-sm border border-slate-100' : ''}
      ${isBanner ? 'rounded-xl mb-6' : 'rounded-xl'}
      ${fullHeight ? 'h-full flex flex-col justify-center' : 'flex flex-col justify-center'}
    `}>
      <div className="relative z-10">
        <div className="flex items-center gap-3 mb-2">
          <div className={`
            ${variant === 'app' ? 'p-3 bg-brand-50 text-brand-600 rounded-2xl' : ''}
          `}>
            <hero.icon
              className={`
                ${variant === 'mixed' ? 'text-white/80' : ''}
                ${variant === 'app' ? 'text-brand-600' : ''}
              `}
              size={variant === 'app' ? 28 : 24}
            />
          </div>
          <h2 className={`
            font-bold
            ${variant === 'app' ? 'text-2xl text-slate-900 tracking-tight' : 'text-xl'}
          `}>{hero.title}</h2>
        </div>
        <p className={`
          text-xs max-w-2xl
          ${variant === 'mixed' ? 'text-white/70' : 'text-slate-500'}
        `}>
          {hero.description}
        </p>
      </div>
      {variant === 'mixed' && (
        <div className="absolute right-0 top-0 opacity-10 transform translate-x-2 -translate-y-2 pointer-events-none">
          <hero.icon size={100} />
        </div>
      )}
    </div>
  );

  const renderNavigation = () => {
    if (!hasTabs) return null;

    return (
      <nav className={`
        ${variant === 'app' ? 'flex overflow-x-auto gap-2 pb-2 no-scrollbar' : ''}
        ${variant === 'mixed' ? 'bg-white rounded-xl border border-slate-200 shadow-sm p-2 space-y-1' : ''}
      `}>
        {tabs.map(tab => {
          const isActive = activeTab === tab.id;
          return (
            <button
              key={tab.id}
              onClick={() => onTabChange?.(tab.id)}
              className={`
                flex items-center gap-3 transition-all whitespace-nowrap
                ${variant === 'mixed' ? `w-full text-left px-4 py-2.5 rounded-lg text-sm font-medium ${isActive ? 'bg-slate-100 text-slate-900 font-bold shadow-sm' : 'text-slate-600 hover:bg-slate-50'}` : ''}
                ${variant === 'app' ? `px-5 py-2.5 rounded-full text-sm font-bold border ${isActive ? 'bg-slate-900 text-white border-slate-900 shadow-lg' : 'bg-white text-slate-600 border-slate-200'}` : ''}
              `}
            >
              <tab.icon size={18} />
              {tab.label}
              {tab.badge ? (
                <span className={`ml-auto text-[10px] px-1.5 py-0.5 rounded-full ${isActive ? 'bg-white text-slate-900' : 'bg-slate-200 text-slate-600'}`}>
                  {tab.badge}
                </span>
              ) : null}
            </button>
          );
        })}
      </nav>
    );
  };

  // Helper for Search, Filter, Buttons (separated from Primary Action for better flex control)
  // Accepts options to override default search bar styling logic
  const renderSearchAndActions = (options: { isFullWidth?: boolean, searchClassName?: string } = {}) => {
    const { isFullWidth = false, searchClassName } = options;
    
    // Default class logic if no override provided
    const defaultSearchClass = isFullWidth 
        ? 'flex-1' 
        : 'w-full md:w-auto md:min-w-[240px]';

    const finalSearchClass = searchClassName || defaultSearchClass;

    return (
      <>
        {/* Search */}
        {onSearchChange && (
          <div className={`relative ${finalSearchClass}`}>
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={16} />
            <input
              type="text"
              placeholder="Search..."
              value={searchQuery}
              onChange={(e) => onSearchChange(e.target.value)}
              className={`
                w-full pl-9 pr-4 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition-all
                ${variant === 'app' ? 'bg-slate-100 border-transparent rounded-2xl py-2' : 'py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:bg-white'}
              `}
            />
          </div>
        )}

        {/* Filter Toggle */}
        {onToggleFilter && (
          <button
            onClick={onToggleFilter}
            className={`
              transition-colors flex items-center justify-center shrink-0
              ${variant !== 'app' ? 'border rounded-xl p-2.5' : 'rounded-full w-10 h-10 border-none bg-slate-100 p-2'}
              ${showFilter ? 'bg-brand-50 border-brand-200 text-brand-700' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'}
            `}
          >
            <Filter size={18} />
          </button>
        )}

        {/* Extra Actions */}
        {actions}
      </>
    );
  };

  const renderPrimaryAction = () => {
      if (!primaryAction) return null;
      return (
        <button
          onClick={primaryAction.onClick}
          className={`
            flex items-center gap-2 shadow-sm whitespace-nowrap transition-colors shrink-0
            ${variant === 'mixed' ? 'bg-brand-600 hover:bg-brand-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold' : ''}
            ${variant === 'app' ? 'fixed bottom-6 right-6 w-14 h-14 bg-slate-900 text-white rounded-full shadow-2xl flex items-center justify-center z-50 md:static md:w-auto md:h-auto md:px-5 md:py-2.5 md:rounded-full' : ''}
          `}
        >
          {primaryAction.icon && <primaryAction.icon size={18} />}
          <span className={`${variant === 'app' ? 'hidden md:inline' : ''}`}>{primaryAction.label}</span>
        </button>
      );
  };

  // Standard Header (With Tabs) - Refactored for better responsiveness
  const renderStandardHeader = () => {
    const activeTabObj = tabs.find(t => t.id === activeTab);
    const title = activeTabObj ? activeTabObj.label : moduleName;

    return (
      <div className={`
        flex flex-col md:flex-row gap-4 justify-between items-center shrink-0 z-20
        ${variant === 'app' ? 'p-4' : ''}
        ${variant === 'mixed' ? 'p-4 bg-white' : ''}
      `}>
        <div className="flex flex-col mr-auto">
          <h3 className={`
            font-bold text-slate-800
            ${variant === 'app' ? 'text-2xl' : 'text-lg'}
          `}>{title}</h3>
          <p className="text-xs text-slate-500">Manage your {title.toLowerCase()} inventory</p>
        </div>

        <div className="flex items-center gap-2 w-full md:w-auto justify-end flex-wrap">
          {renderSearchAndActions()}
          {renderPrimaryAction()}
        </div>
      </div>
    );
  };

  const renderFilterPanel = () => {
    if (!showFilter || !filterContent) return null;
    return (
      <div className={`
        animate-slideDown shrink-0
        ${variant === 'app' ? 'p-4 bg-slate-50' : ''}
        ${variant === 'mixed' ? 'p-4 bg-slate-50 border-t border-slate-100' : ''}
      `}>
        {filterContent}
      </div>
    );
  };

  // --- LAYOUT STRUCTURES ---

  if (variant === 'app') {
    return (
      <div className="flex flex-col h-full bg-slate-50">
        {/* Mobile Header for App Variant */}
        <div className="p-4 lg:hidden flex justify-between items-center bg-white shadow-sm z-30 sticky top-0">
          <div className="flex items-center gap-3">
            <div className="p-2 bg-slate-100 rounded-full"><hero.icon size={20} /></div>
            <span className="font-bold text-lg">{moduleName}</span>
          </div>
        </div>

        <div className="flex-1 flex flex-col lg:flex-row overflow-hidden max-w-7xl mx-auto w-full lg:p-6 gap-6">
          <aside className="hidden lg:flex w-64 flex-col gap-6">
            {renderHeroSection()}
            <div className="flex flex-col gap-2">
              {tabs.map(t => (
                <button key={t.id} onClick={() => onTabChange?.(t.id)} className={`p-4 rounded-2xl text-left font-bold flex items-center gap-3 transition-all ${activeTab === t.id ? 'bg-white shadow-lg shadow-slate-200 scale-105 border border-slate-100 text-slate-900' : 'text-slate-500 hover:bg-white hover:text-slate-700'}`}>
                  <t.icon size={20} /> {t.label}
                </button>
              ))}
            </div>
          </aside>

          <main className="flex-1 flex flex-col bg-white lg:rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
            {renderStandardHeader()}
            {renderFilterPanel()}
            <div className="flex-1 overflow-y-auto p-4 lg:p-8">
              {children}
            </div>
          </main>
        </div>
      </div>
    );
  }

  // Mixed / Default (Scroll Away Mobile)
  return (
    <div className="flex flex-col min-h-full bg-slate-50/50">

      {/* ===========================================================================
          MOBILE & TABLET LAYOUT (Sticky Scroll Away - Up to LG breakpoint)
          =========================================================================== */}
      
      <div className="lg:hidden flex flex-col min-h-screen">
          {/* 
             Sticky Header Container
             Includes Title, Tabs, and Search in ONE blue block
          */}
          <div
            className={`sticky top-0 left-0 right-0 z-20 transition-transform duration-300 ease-in-out shadow-lg bg-gradient-to-r ${hero.gradient || 'from-slate-800 to-slate-900'} text-white ${isHeaderVisible ? 'translate-y-0' : '-translate-y-full'}`}
          >
              {/* Part 1: Title & Tabs */}
              <div className="px-4 pt-4 pb-2">
                <div className="flex items-center gap-2 mb-3">
                    <hero.icon size={20} className="text-white/80" />
                    <h2 className="font-bold text-lg">{hero.title}</h2>
                </div>
                
                {/* Scrollable Tabs - Horizontal (Only if tabs exist) */}
                {hasTabs && (
                  <div className="flex overflow-x-auto no-scrollbar gap-2 pb-1">
                      {tabs.map(t => {
                        const active = activeTab === t.id;
                        return (
                            <button 
                              key={t.id}
                              onClick={() => onTabChange?.(t.id)}
                              className={`flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-medium transition-all whitespace-nowrap ${active ? 'bg-white text-brand-700 shadow-sm' : 'bg-white/10 text-white hover:bg-white/20'}`}
                            >
                              <t.icon size={16} />
                              {active && <span>{t.label}</span>}
                            </button>
                        )
                      })}
                  </div>
                )}
              </div>

              {/* Part 2: Integrated Search & Filter (Blue Background, Glassmorphism Inputs) */}
              <div className="px-4 pb-3 flex gap-2">
                {onSearchChange && (
                    <div className="relative flex-1">
                      <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-white/70" size={16} />
                      <input
                          type="text"
                          placeholder="Search..."
                          value={searchQuery}
                          onChange={(e) => onSearchChange(e.target.value)}
                          className="w-full pl-9 pr-4 py-2 rounded-lg text-sm bg-white/10 border border-white/20 text-white placeholder-white/60 focus:bg-white/20 focus:outline-none focus:ring-1 focus:ring-white/50"
                      />
                    </div>
                )}
                {onToggleFilter && (
                    <button 
                      onClick={onToggleFilter} 
                      className={`p-2 rounded-lg border border-white/20 transition-colors ${showFilter ? 'bg-white text-brand-700' : 'bg-white/10 text-white hover:bg-white/20'}`}
                    >
                      <Filter size={20} />
                    </button>
                )}
              </div>

              {/* Filter Content Panel (if open) - Drops down from blue header */}
              {showFilter && filterContent && (
                <div className="bg-white border-b border-slate-200 p-4 shadow-inner max-h-[50vh] overflow-y-auto text-slate-800">
                    {filterContent}
                </div>
              )}
          </div>
          
          {/* Content Wrapper */}
          <div className="flex-1">
             {children}
          </div>

          {/* Mobile Floating Action Button */}
          {primaryAction && (
            <button
              onClick={primaryAction.onClick}
              className="fixed bottom-6 right-6 w-14 h-14 bg-brand-600 text-white rounded-full shadow-2xl flex items-center justify-center z-50 hover:bg-brand-700 active:scale-95 transition-all"
            >
              {primaryAction.icon ? <primaryAction.icon size={28} /> : <Plus size={28} />}
            </button>
          )}
      </div>


      {/* ===========================================================================
          DESKTOP LAYOUT (Large Screens)
          =========================================================================== */}
      <div className="hidden lg:flex min-h-full p-6 gap-6 items-start">
        
        {/* Scenario 1: WITH TABS (Sidebar) */}
        {hasTabs && (
          <aside className="w-72 flex-shrink-0 flex flex-col gap-6 sticky top-6 self-start z-40">
            {renderHeroSection()}
            {renderNavigation()}
          </aside>
        )}

        {/* Main Content Area */}
        <div className="flex-1 flex flex-col min-w-0 h-full">
          
          {/* Scenario 2: NO TABS (Full Width) */}
          {!hasTabs && (
            <>
              {/* Sticky Header Wrapper - Using bg-slate-50 to mask content scrolling behind */}
              <div className="sticky top-0 z-30 bg-slate-50 pt-6 pb-6 -mt-6">
                  <div className="flex gap-6 items-stretch">
                      
                      {/* Left: Module Identity (Reusing Hero Section style) */}
                      {/* Fixed width w-72 to match sidebar width, reuse renderHeroSection for gradient look. 
                          Pass true to enforce full height filling */}
                      <div className="w-72 shrink-0 z-20 relative">
                          {renderHeroSection(false, true)}
                      </div>

                      {/* Right: Actions Toolbar (Like Header) */}
                      <div className="flex-1 bg-white p-6 rounded-xl shadow-sm border border-slate-200 flex flex-col justify-center relative z-20">
                          <div className="flex items-center gap-3 w-full justify-between">
                              <div className="flex-1 flex gap-3">
                                {renderSearchAndActions({ isFullWidth: true })}
                              </div>
                              <div className="pl-3 border-l border-slate-100">
                                {renderPrimaryAction()}
                              </div>
                          </div>
                          {/* Filter Expansion inside the action box */}
                          {showFilter && filterContent && (
                              <div className="mt-4 pt-4 border-t border-slate-100 animate-slideDown">
                                  {filterContent}
                              </div>
                          )}
                      </div>
                  </div>
              </div>

              {/* Content Body */}
              <main className="bg-white border border-slate-200 shadow-sm flex-1 flex flex-col z-0 relative rounded-xl overflow-hidden">
                  {children}
              </main>
            </>
          )}

          {/* Scenario 1: WITH TABS (Main Content part) */}
          {hasTabs && (
            <div className="flex-1 flex flex-col min-w-0">
                {/* 
                    Sticky Header Container: 
                    - Flex Wrap enabled
                    - Title: Order 1 (Always left)
                    - Primary Action: Order 2 (On Mobile/Tablet: stays right of title). Order 3 (Desktop: moves to far right).
                    - Search/Filter: Order 3 (On Mobile/Tablet: drops to new line). Order 2 (Desktop: sits between title and button).
                */}
                <div className="sticky top-0 z-30 bg-slate-50 pt-6 -mt-6">
                    <div className="bg-white p-6 rounded-t-xl rounded-b-none border border-slate-200 border-b-0 shadow-sm flex flex-wrap justify-between items-center gap-y-0 relative z-20">
                         {/* Title Section (Order 1) */}
                         <div className="flex flex-col min-w-[140px] order-1">
                            <h3 className="font-bold text-slate-800 text-lg truncate">
                                {tabs.find(t => t.id === activeTab)?.label || moduleName}
                            </h3>
                            <p className="text-xs text-slate-500 truncate">Manage your {tabs.find(t => t.id === activeTab)?.label.toLowerCase() || moduleName.toLowerCase()} inventory</p>
                         </div>

                         {/* Actions Section (Search/Filter) - Order 3 (Mobile/Tablet), Order 2 (Desktop) */}
                         {/* width: full on mobile/tablet to force break, auto on desktop */}
                         <div className="order-3 w-full xl:order-2 xl:w-auto xl:flex-1 xl:flex xl:justify-end gap-3 flex items-center mt-4 xl:mt-0">
                             {renderSearchAndActions({ 
                                 // On Tablet/Mobile (< XL), the search bar should grow (flex-1) to fill the left side of the second row.
                                 // On Desktop (>= XL), it acts as a standard sized input.
                                 searchClassName: "flex-1 min-w-0 xl:w-auto xl:min-w-[240px] xl:flex-none" 
                             })}
                         </div>

                         {/* Primary Action Button - Order 2 (Mobile/Tablet), Order 3 (Desktop) */}
                         {primaryAction && (
                            <div className="order-2 xl:order-3 shrink-0">
                                {renderPrimaryAction()}
                            </div>
                         )}
                    </div>
                    
                    {/* Filter Panel (Expands the container visually) */}
                    {showFilter && filterContent && (
                        <div className="bg-white border-x border-slate-200 border-b-0 p-6 relative z-10 animate-slideDown">
                            {filterContent}
                        </div>
                    )}
                </div>

                {/* Content Card */}
                {/* Standard card style, flows naturally below the sticky header. 
                    Border-t-0 and rounded-t-none to seamlessly merge with the sticky header above.
                */}
                <main className="bg-white border border-slate-200 border-t-0 shadow-sm rounded-b-xl rounded-t-none overflow-hidden flex-1 relative z-0">
                    {children}
                </main>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default ModuleLayout;