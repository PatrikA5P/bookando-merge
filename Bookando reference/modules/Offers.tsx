
import React, { useState, useRef, useMemo, useEffect } from 'react';
import {
  Tag, Clock, Check, Plus, Search, Filter, MoreHorizontal,
  Package, Ticket, Layers, DollarSign, Edit2, Image as ImageIcon,
  Users, Video, Calendar, Globe, Eye, Trash2, X, Upload, Save, CheckCircle, AlertCircle,
  MapPin, Box, Bell, FileText, Settings, Shield, ArrowRight, Layout,
  Bold, Italic, List, Link as LinkIcon, CreditCard, User, Gift, Percent,
  LayoutTemplate, Move, Code, ChevronDown, ChevronUp, BookOpen, TrendingUp, Zap, Hash, History,
  Activity, Gauge, Sliders, Sun, Moon, CalendarDays, ArrowUpRight, ArrowLeft, ExternalLink, CheckSquare, Square,
  Info, GripVertical, ArrowDown, Type, AlignLeft, Paperclip, Disc, CheckSquare as CheckSquareIcon, Maximize, Columns, Grid as GridIcon, Download, MousePointer2
} from 'lucide-react';
import { useApp } from '../context/AppContext';
import { DynamicPricingRule, PricingStrategyType, PricingTier, SeasonalRule, DaySchedule, TimeSlot, ServiceItem, BundleItem, VoucherItem, EventSession, OfferType, VoucherCategory, PaymentOption, EventStructure, VoucherType, FormTemplate, FormElement, FormElementType, FormElementSource, FormElementWidth, OfferCategory, OfferExtra } from '../types';
import ModuleLayout from '../components/ModuleLayout';
import { getModuleDesign } from '../utils/designTokens';
import { ModalTab } from './Offers/types';
import CategoriesTab from './Offers/tabs/CategoriesTab';
import TagsTab from './Offers/tabs/TagsTab';
import ExtrasTab from './Offers/tabs/ExtrasTab';
import DynamicPricingTab from './Offers/tabs/DynamicPricingTab';
import BookingFormsTab from './Offers/tabs/BookingFormsTab';
import CatalogTab from './Offers/tabs/CatalogTab';
import BundlesTab from './Offers/tabs/BundlesTab';
import VouchersTab from './Offers/tabs/VouchersTab';
import OfferModal from './Offers/components/OfferModal';

// --- Mock Data ---

const mockStaff = [
    { id: 'emp1', name: 'Sarah Jenkins' },
    { id: 'emp2', name: 'Mike Ross' },
    { id: 'emp3', name: 'Jessica Pearson' }
];

// --- MAIN MODULE COMPONENT ---

// --- MAIN MODULE COMPONENT ---

const OffersModule: React.FC = () => {
  const { t } = useApp();
  const [activeTab, setActiveTab] = useState<'catalog' | 'categories' | 'bundles' | 'vouchers' | 'forms' | 'tags' | 'extras' | 'pricing'>('catalog');

  // Data State
  const { services, setServices, bundles, setBundles, vouchers, setVouchers, offerCategories, offerTags, offerExtras, pricingRules } = useApp();

  // Deep-link state for opening service in specific tab
  const [forceTab, setForceTab] = useState<ModalTab | undefined>(undefined);

  // Filter State
  const [searchQuery, setSearchQuery] = useState('');
  const [filterCategory, setFilterCategory] = useState('All');
  const [filterType, setFilterType] = useState('All');
  const [filterStatus, setFilterStatus] = useState('All');
  const [showFilters, setShowFilters] = useState(false);

  // Modal State
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [modalMode, setModalMode] = useState<'create' | 'edit'>('create');
  const [editingItem, setEditingItem] = useState<any>(null); // Generic container for edit
  const [targetType, setTargetType] = useState<'service' | 'bundle' | 'voucher'>('service');
  
  // Triggers for sub-tabs creation
  const [categoryCreateTrigger, setCategoryCreateTrigger] = useState(0);
  const [tagCreateTrigger, setTagCreateTrigger] = useState(0);
  const [extraCreateTrigger, setExtraCreateTrigger] = useState(0);
  const [pricingCreateTrigger, setPricingCreateTrigger] = useState(0);

  // --- CRUD Actions ---

  const handleMainCreate = () => {
      // Delegate create action based on active tab
      if (activeTab === 'categories') {
          setCategoryCreateTrigger(prev => prev + 1);
      } else if (activeTab === 'tags') {
          setTagCreateTrigger(prev => prev + 1);
      } else if (activeTab === 'extras') {
          setExtraCreateTrigger(prev => prev + 1);
      } else if (activeTab === 'pricing') {
          setPricingCreateTrigger(prev => prev + 1);
      } else {
          // Standard modal for offers/bundles/vouchers
          openCreateModal();
      }
  };

  const openCreateModal = () => {
    setModalMode('create');
    setEditingItem(null);
    if (activeTab === 'catalog') setTargetType('service');
    else if (activeTab === 'bundles') setTargetType('bundle');
    else setTargetType('voucher');
    setIsModalOpen(true);
  };

  const openEditModal = (item: any, type: 'service' | 'bundle' | 'voucher', initialTab?: ModalTab) => {
    setModalMode('edit');
    setEditingItem({ item, initialTab: initialTab || forceTab });
    setTargetType(type);
    setIsModalOpen(true);
    setForceTab(undefined); // Reset after using
  };

  // Deep-link handler for opening service from forms
  const handleOpenServiceProcess = (serviceId: string) => {
      const service = services.find(s => s.id === serviceId);
      if (service) {
          setActiveTab('catalog');
          setForceTab('process');
          setTimeout(() => openEditModal(service, 'service', 'process'), 100);
      }
  };

  const handleDelete = (id: string, type: 'service' | 'bundle' | 'voucher') => {
    if (!window.confirm('Are you sure you want to delete this item?')) return;
    
    if (type === 'service') setServices(services.filter(s => s.id !== id));
    if (type === 'bundle') setBundles(bundles.filter(b => b.id !== id));
    if (type === 'voucher') setVouchers(vouchers.filter(v => v.id !== id));
  };

  const handleSave = (data: any) => {
    if (targetType === 'service') {
      if (modalMode === 'create') {
        setServices([...services, { ...data, id: Date.now().toString() }]);
      } else {
        setServices(services.map(s => s.id === data.id ? data : s));
      }
    } else if (targetType === 'bundle') {
      if (modalMode === 'create') {
        setBundles([...bundles, { ...data, id: Date.now().toString() }]);
      } else {
        setBundles(bundles.map(b => b.id === data.id ? data : b));
      }
    } else if (targetType === 'voucher') {
      if (modalMode === 'create') {
        setVouchers([...vouchers, { ...data, id: Date.now().toString() }]);
      } else {
        setVouchers(vouchers.map(v => v.id === data.id ? data : v));
      }
    }
    setIsModalOpen(false);
  };

  const toggleStatus = (id: string, type: 'service' | 'bundle') => {
    if (type === 'service') {
        setServices(services.map(s => s.id === id ? {...s, active: !s.active} : s));
    } else {
        setBundles(bundles.map(b => b.id === id ? {...b, active: !b.active} : b));
    }
  };

  const handleEditServiceFromPricing = (id: string) => {
      const service = services.find(s => s.id === id);
      if (service) {
          openEditModal(service, 'service', 'pricing');
      }
  };

  // Export functionality
  const handleExport = () => {
    let headers: string[] = [];
    let rows: (string | number | boolean | null | undefined)[][] = [];

    if (activeTab === 'bundles') {
      headers = ['ID', 'Title', 'Items', 'Price', 'Original Price', 'Savings', 'Active'];
      rows = filteredBundles.map(bundle => [
        bundle.id,
        bundle.title,
        bundle.items.join(' | '),
        bundle.price,
        bundle.originalPrice,
        bundle.savings,
        bundle.active ? 'Active' : 'Inactive'
      ]);
    } else if (activeTab === 'vouchers') {
      headers = ['ID', 'Title', 'Category', 'Code', 'Discount Type', 'Value', 'Uses', 'Max Uses', 'Status'];
      rows = filteredVouchers.map(voucher => [
        voucher.id,
        voucher.title,
        voucher.category,
        voucher.code || '',
        voucher.discountType,
        voucher.discountValue ?? voucher.fixedValue ?? (voucher.allowCustomAmount ? 'Custom' : ''),
        voucher.uses || 0,
        voucher.maxUses || '',
        voucher.status
      ]);
    } else if (activeTab === 'categories') {
      headers = ['ID', 'Name', 'Color', 'Description'];
      rows = offerCategories.filter(c => c.name.toLowerCase().includes(searchQuery.toLowerCase())).map(cat => [cat.id, cat.name, cat.color, cat.description || '']);
    } else if (activeTab === 'tags') {
      headers = ['ID', 'Name', 'Color'];
      rows = offerTags.map(tag => [tag.id, tag.name, tag.color]);
    } else if (activeTab === 'extras') {
      headers = ['ID', 'Name', 'Description', 'Price Type', 'Price', 'Currency'];
      rows = offerExtras.map(extra => [
        extra.id,
        extra.name,
        extra.description || '',
        extra.priceType,
        extra.price,
        extra.currency || ''
      ]);
    } else {
      // catalog (services)
      headers = ['ID', 'Title', 'Category', 'Type', 'Price', 'Currency', 'Active'];
      rows = filteredServices.map(service => [
        service.id,
        service.title,
        service.category,
        service.type,
        service.price,
        service.currency,
        service.active ? 'Active' : 'Inactive'
      ]);
    }

    const csvContent = 'data:text/csv;charset=utf-8,'
      + headers.join(',') + '\n'
      + rows.map(r => r.map(val => typeof val === 'string' && val.includes(',') ? `"${val}"` : val).join(',')).join('\n');

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', `offers_${activeTab}_export.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  // --- Filtering Logic ---

  const filteredServices = services.filter(item => {
    const matchesSearch = item.title.toLowerCase().includes(searchQuery.toLowerCase()) || (item.productCode || '').toLowerCase().includes(searchQuery.toLowerCase());
    const matchesCategory = filterCategory === 'All' || item.category === filterCategory || item.categories?.includes(filterCategory);
    const matchesType = filterType === 'All' || item.type === filterType;
    const matchesStatus = filterStatus === 'All' 
      ? true 
      : filterStatus === 'Active' ? item.active 
      : !item.active;
    return matchesSearch && matchesCategory && matchesType && matchesStatus;
  });

  const filteredBundles = bundles.filter(item => 
    item.title.toLowerCase().includes(searchQuery.toLowerCase())
  );

  const filteredVouchers = vouchers.filter(item => {
    const matchesSearch = (item.title + (item.code || '')).toLowerCase().includes(searchQuery.toLowerCase());
    const matchesStatus = filterStatus === 'All' || item.status === filterStatus;
    return matchesSearch && matchesStatus;
  });

  const moduleDesign = getModuleDesign('offers');

  const tabs = [
    { id: 'catalog', icon: Layers, label: 'All Offers' },
    { id: 'categories', icon: List, label: 'Categories' },
    { id: 'bundles', icon: Package, label: 'Packages & Bundles' },
    { id: 'vouchers', icon: Ticket, label: 'Coupons & Gift Cards' },
    { id: 'forms', icon: LayoutTemplate, label: 'Booking Forms' },
    { id: 'tags', icon: Tag, label: 'Tags', badge: 0 },
    { id: 'extras', icon: Plus, label: 'Extras & Upsells' },
    { id: 'pricing', icon: TrendingUp, label: 'Dynamic Pricing' },
  ];

  return (
    <div className="flex flex-col min-h-full">
      <ModuleLayout
        variant="mixed"
        moduleName="Offers"
        hero={{
          icon: Tag,
          title: 'Offers & Catalog',
          description: 'Manage services, pricing, and sales channels.',
          gradient: moduleDesign.gradient
        }}
        tabs={tabs}
        activeTab={activeTab}
        onTabChange={(tabId) => setActiveTab(tabId as any)}
        searchQuery={['catalog', 'bundles', 'vouchers', 'categories'].includes(activeTab) ? searchQuery : undefined}
        onSearchChange={['catalog', 'bundles', 'vouchers', 'categories'].includes(activeTab) ? setSearchQuery : undefined}
        showFilter={activeTab === 'catalog' ? showFilters : undefined}
        onToggleFilter={activeTab === 'catalog' ? () => setShowFilters(!showFilters) : undefined}
        filterContent={activeTab === 'catalog' ? (
          <div className="space-y-4">
            <div>
              <label className="block text-xs font-bold text-slate-500 uppercase mb-2">Category</label>
              <select
                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-brand-500"
                value={filterCategory}
                onChange={(e) => setFilterCategory(e.target.value)}
              >
                <option value="All">All Categories</option>
                {offerCategories.map(cat => (
                  <option key={cat.id} value={cat.name}>{cat.name}</option>
                ))}
              </select>
            </div>
            <div>
              <label className="block text-xs font-bold text-slate-500 uppercase mb-2">Type</label>
              <select
                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-brand-500"
                value={filterType}
                onChange={(e) => setFilterType(e.target.value)}
              >
                <option value="All">All Types</option>
                <option value="Service">Service</option>
                <option value="Event">Event</option>
                <option value="Online Course">Online Course</option>
              </select>
            </div>
            <div>
              <label className="block text-xs font-bold text-slate-500 uppercase mb-2">Status</label>
              <select
                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-brand-500"
                value={filterStatus}
                onChange={(e) => setFilterStatus(e.target.value)}
              >
                <option value="All">All Status</option>
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
              </select>
            </div>
          </div>
        ) : undefined}
        actions={
          ['catalog', 'bundles', 'vouchers', 'categories', 'tags', 'extras'].includes(activeTab) ? (
            <button
              onClick={handleExport}
              className="flex items-center gap-2 px-4 py-2 border border-slate-200 rounded-xl text-slate-600 hover:bg-slate-50 text-sm font-medium transition-colors"
            >
              <Download size={16} />
              <span className="hidden md:inline">Export</span>
            </button>
          ) : undefined
        }
        primaryAction={{
          label: activeTab === 'catalog' ? 'Create Offer' :
                 activeTab === 'bundles' ? 'Create Bundle' :
                 activeTab === 'vouchers' ? 'Create Voucher' :
                 activeTab === 'forms' ? 'Create Form' :
                 'Create',
          icon: Plus,
          onClick: handleMainCreate
        }}
      >
        {/* Content Body */}
        <div className="flex-1 overflow-hidden flex flex-col">
              {/* Tab Content: Services Catalog */}
              {activeTab === 'catalog' && (
                <CatalogTab
                  services={filteredServices}
                  onEdit={(service) => openEditModal(service, 'service')}
                  onDelete={(id) => handleDelete(id, 'service')}
                  onToggleStatus={(id) => toggleStatus(id, 'service')}
                />
              )}

              {/* Tab Content: Categories */}
              {activeTab === 'categories' && <CategoriesTab createTrigger={categoryCreateTrigger} searchTerm={searchQuery} />}

              {/* Tab Content: Tags */}
              {activeTab === 'tags' && <TagsTab createTrigger={tagCreateTrigger} />}

              {/* Tab Content: Extras */}
              {activeTab === 'extras' && <ExtrasTab createTrigger={extraCreateTrigger} />}

              {/* Tab Content: Pricing */}
              {activeTab === 'pricing' && (
                  <DynamicPricingTab 
                    createTrigger={pricingCreateTrigger} 
                    handleEditService={handleEditServiceFromPricing}
                    services={services}
                  />
              )}

              {/* Tab Content: Bundles */}
              {activeTab === 'bundles' && (
                <BundlesTab
                  bundles={filteredBundles}
                  onEdit={(bundle) => openEditModal(bundle, 'bundle')}
                  onDelete={(id) => handleDelete(id, 'bundle')}
                />
              )}

              {/* Tab Content: Vouchers & Gift Cards */}
              {activeTab === 'vouchers' && (
                <VouchersTab
                  vouchers={filteredVouchers}
                  onEdit={(voucher) => openEditModal(voucher, 'voucher')}
                  onDelete={(id) => handleDelete(id, 'voucher')}
                />
              )}

              {/* Tab Content: Booking Forms */}
              {activeTab === 'forms' && <BookingFormsTab onOpenService={handleOpenServiceProcess} />}
        </div>
      </ModuleLayout>

      {/* Modal Overlay */}
      {isModalOpen && (
        <OfferModal
          mode={modalMode}
          type={targetType}
          initialData={editingItem?.item}
          initialTab={editingItem?.initialTab}
          availableServices={services}
          onClose={() => setIsModalOpen(false)}
          onSave={handleSave}
        />
      )}
    </div>
  );
};

export default OffersModule;
