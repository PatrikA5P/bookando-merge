import React, { useState, useMemo, useEffect } from 'react';
import ModuleLayout, { LayoutVariant } from '../components/ModuleLayout';
import { Layers, List, Package, Ticket, LayoutTemplate, Tag, Plus, Edit2, Trash2, Clock, ChevronLeft, ChevronRight, ToggleLeft } from 'lucide-react';

// Mock content to populate the layout
const DummyContent: React.FC<{ filterText?: string }> = ({ filterText = '' }) => {
  const items = [
      { id: 1, title: 'Deep Tissue Massage', price: 120, type: 'Wellness', desc: 'Relieve tension with this intensive treatment targeting deep muscle layers.' },
      { id: 2, title: 'Swedish Massage', price: 90, type: 'Wellness', desc: 'Relaxing full body massage to improve circulation.' },
      { id: 3, title: 'Yoga Basics', price: 25, type: 'Fitness', desc: 'Introduction to yoga poses and breathing techniques.' },
      { id: 4, title: 'Pilates Reformer', price: 40, type: 'Fitness', desc: 'Strength training using the reformer machine.' },
      { id: 5, title: 'Nutrition Consultation', price: 150, type: 'Health', desc: 'Personalized diet plan and lifestyle assessment.' },
      { id: 6, title: 'Acupuncture', price: 110, type: 'Health', desc: 'Traditional Chinese medicine to balance energy flow.' },
      { id: 7, title: 'Meditation Circle', price: 15, type: 'Mindfulness', desc: 'Group meditation session for stress relief.' },
      { id: 8, title: 'Hot Stone Therapy', price: 130, type: 'Wellness', desc: 'Therapeutic massage using smooth, heated stones.' },
      { id: 9, title: 'Crossfit Intro', price: 30, type: 'Fitness', desc: 'High intensity interval training introduction.' },
      { id: 10, title: 'Physiotherapy', price: 140, type: 'Health', desc: 'Rehabilitation exercises and manual therapy.' },
      { id: 11, title: 'Reiki Healing', price: 80, type: 'Mindfulness', desc: 'Energy healing technique to promote relaxation.' },
      { id: 12, title: 'Spin Class', price: 20, type: 'Fitness', desc: 'High energy indoor cycling workout.' },
      { id: 13, title: 'Facial Treatment', price: 95, type: 'Beauty', desc: 'Rejuvenating skin treatment with organic products.' },
      { id: 14, title: 'Manicure Deluxe', price: 50, type: 'Beauty', desc: 'Complete nail care including hand massage.' },
      { id: 15, title: 'Personal Training', price: 100, type: 'Fitness', desc: 'One-on-one customized workout session.' }
  ];

  // Pagination State
  const [currentPage, setCurrentPage] = useState(1);
  const [itemsPerPage, setItemsPerPage] = useState(6); // Grid-friendly defaults (divisible by 2 and 3)

  // Reset pagination when filter changes
  useEffect(() => {
      setCurrentPage(1);
  }, [filterText, itemsPerPage]);

  const filteredItems = useMemo(() => {
      if (!filterText) return items;
      const lower = filterText.toLowerCase();
      return items.filter(i => 
          i.title.toLowerCase().includes(lower) || 
          i.type.toLowerCase().includes(lower) ||
          i.desc.toLowerCase().includes(lower)
      );
  }, [items, filterText]);

  // Pagination Logic
  const totalItems = filteredItems.length;
  const totalPages = Math.ceil(totalItems / itemsPerPage);
  const paginatedItems = filteredItems.slice(
      (currentPage - 1) * itemsPerPage, 
      currentPage * itemsPerPage
  );

  // Helper to generate page numbers (Smart Pagination like in Customers)
  const getPageNumbers = () => {
      const delta = 1; // Numbers to show around current
      const range = [];
      const rangeWithDots = [];
      let l;

      range.push(1);
      for (let i = currentPage - delta; i <= currentPage + delta; i++) {
          if (i < totalPages && i > 1) {
              range.push(i);
          }
      }
      if(totalPages > 1) range.push(totalPages);

      for (let i of range) {
          if (l) {
              if (i - l === 2) {
                  rangeWithDots.push(l + 1);
              } else if (i - l !== 1) {
                  rangeWithDots.push('...');
              }
          }
          rangeWithDots.push(i);
          l = i;
      }
      return rangeWithDots;
  };

  return (
    <div className="flex flex-col h-full">
        {/* Content Area - Natural flow. Mobile padding p-2, Desktop p-6 */}
        <div className="p-2 md:p-6 flex-1">
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-2 md:gap-6">
            {paginatedItems.map((item) => (
                <div key={item.id} className="group bg-white rounded-xl border border-slate-200 shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col overflow-hidden">
                    <div className="h-40 bg-slate-100 relative overflow-hidden">
                        <div className="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-60"></div>
                        <div className="absolute bottom-3 left-3">
                        <span className="flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold shadow-sm border backdrop-blur-md bg-white/95 text-blue-700">
                            <Clock size={14} /> Service
                        </span>
                        </div>
                    </div>
                    <div className="p-5 flex-1 flex flex-col">
                        <div className="flex justify-between items-start mb-2">
                        <span className="text-[10px] font-bold text-slate-500 uppercase tracking-wider border border-slate-200 px-1.5 rounded bg-slate-50">{item.type}</span>
                        <div className="font-bold text-lg text-slate-900">${item.price}</div>
                        </div>
                        <h3 className="font-bold text-slate-800 text-lg mb-2 leading-tight">{item.title}</h3>
                        <p className="text-slate-500 text-sm mb-4 line-clamp-2 flex-1">{item.desc}</p>
                        <div className="mt-auto pt-4 border-t border-slate-100 flex justify-between items-center">
                        <div className="flex gap-2">
                            <button className="p-1.5 text-slate-400 hover:text-brand-600 bg-slate-50 rounded-md"><Edit2 size={16}/></button>
                            <button className="p-1.5 text-slate-400 hover:text-rose-600 bg-slate-50 rounded-md"><Trash2 size={16}/></button>
                        </div>
                        </div>
                    </div>
                </div>
            ))}
            {paginatedItems.length === 0 && (
                <div className="col-span-full py-12 text-center text-slate-400">
                    <p className="text-lg font-medium">No services found matching "{filterText}"</p>
                </div>
            )}
            </div>
        </div>

        {/* Pagination Footer - Sticky at bottom or flows naturally */}
        <div className="p-4 border-t border-slate-200 bg-slate-50 flex flex-col md:flex-row items-center justify-between gap-4 mt-auto">
            <div className="flex items-center gap-4 text-sm text-slate-600">
                <span>
                    Showing <span className="font-bold text-slate-900">{totalItems > 0 ? (currentPage - 1) * itemsPerPage + 1 : 0}</span> to <span className="font-bold text-slate-900">{Math.min(currentPage * itemsPerPage, totalItems)}</span> of <span className="font-bold text-slate-900">{totalItems}</span> results
                </span>
                <select 
                    className="bg-white border border-slate-300 rounded-lg px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 cursor-pointer"
                    value={itemsPerPage}
                    onChange={(e) => setItemsPerPage(Number(e.target.value))}
                >
                    <option value={6}>6 per page</option>
                    <option value={12}>12 per page</option>
                    <option value={24}>24 per page</option>
                    <option value={48}>48 per page</option>
                </select>
            </div>

            <div className="flex items-center gap-1">
                <button 
                    onClick={() => setCurrentPage(prev => Math.max(prev - 1, 1))}
                    disabled={currentPage === 1}
                    className="p-2 rounded-lg border border-slate-300 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                    <ChevronLeft size={16} />
                </button>
                
                {getPageNumbers().map((page, idx) => (
                    <button
                        key={idx}
                        onClick={() => typeof page === 'number' && setCurrentPage(page)}
                        disabled={typeof page !== 'number'}
                        className={`
                            min-w-[32px] h-8 px-2 rounded-lg text-sm font-medium transition-colors
                            ${page === currentPage 
                                ? 'bg-brand-600 text-white shadow-sm' 
                                : typeof page === 'number' ? 'bg-white border border-slate-300 text-slate-600 hover:bg-slate-50' : 'text-slate-400 cursor-default'}
                        `}
                    >
                        {page}
                    </button>
                ))}

                <button 
                    onClick={() => setCurrentPage(prev => Math.min(prev + 1, totalPages))}
                    disabled={currentPage === totalPages || totalPages === 0}
                    className="p-2 rounded-lg border border-slate-300 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                    <ChevronRight size={16} />
                </button>
            </div>
        </div>
    </div>
  );
};

const DesignTemplates: React.FC = () => {
  const [activeVariant] = useState<LayoutVariant>('mixed'); 
  const [useTabs, setUseTabs] = useState(true);
  
  const [activeTab, setActiveTab] = useState('catalog');
  const [searchQuery, setSearchQuery] = useState('');
  const [showFilter, setShowFilter] = useState(false);

  const heroDetails = {
    icon: Layers,
    title: 'Offers & Catalog',
    description: 'Manage services, pricing, and sales channels.',
    gradient: 'from-blue-900 to-slate-800'
  };

  const tabs = [
    { id: 'catalog', label: 'All Offers', icon: Layers },
    { id: 'categories', label: 'Categories', icon: List },
    { id: 'bundles', label: 'Packages', icon: Package },
    { id: 'vouchers', label: 'Coupons', icon: Ticket },
    { id: 'forms', label: 'Booking Forms', icon: LayoutTemplate },
    { id: 'tags', label: 'Tags', icon: Tag },
  ];

  return (
    // IMPORTANT: For 'mixed', we remove h-full/overflow-hidden to allow page scroll
    <div className={`flex flex-col ${activeVariant === 'mixed' ? 'min-h-full' : 'h-full overflow-hidden'}`}>
       
       {/* The Module Layout Component */}
       <div className={`flex-1 ${activeVariant === 'mixed' ? 'relative' : 'overflow-hidden relative'}`}>
          <ModuleLayout
             variant={activeVariant}
             moduleName="Offers"
             hero={heroDetails}
             // Toggle Tabs: Pass tabs or empty array based on state
             tabs={useTabs ? tabs : []}
             activeTab={activeTab}
             onTabChange={setActiveTab}
             searchQuery={searchQuery}
             onSearchChange={setSearchQuery}
             showFilter={showFilter}
             onToggleFilter={() => setShowFilter(!showFilter)}
             primaryAction={{
                label: 'Create Offer',
                icon: Plus,
                onClick: () => alert('Create Clicked')
             }}
             actions={
                // Demo Toggle for Tabs/NoTabs
                <button 
                    onClick={() => setUseTabs(!useTabs)}
                    className="flex items-center gap-2 px-3 py-2 border rounded-lg bg-white text-xs font-medium text-slate-600 hover:text-brand-600 transition-colors"
                    title="Toggle Layout Mode (Demo)"
                >
                    <ToggleLeft size={16} className={useTabs ? "text-brand-600" : "text-slate-400"} />
                    {useTabs ? "With Tabs" : "Full Width"}
                </button>
             }
             filterContent={
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                   <select className="input-field"><option>All Categories</option></select>
                   <select className="input-field"><option>All Types</option></select>
                   <select className="input-field"><option>Active Only</option></select>
                </div>
             }
          >
             {/* Main Content */}
             <DummyContent filterText={searchQuery} />
          </ModuleLayout>
       </div>
       
       <style>{`
         .input-field {
            width: 100%;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 0.5rem;
            font-size: 0.875rem;
            background-color: white;
         }
       `}</style>
    </div>
  );
};

export default DesignTemplates;