
import React, { useState, useMemo, useEffect, useRef } from 'react';
import { Customer, CustomerStatus, CustomField, Booking, Appointment, FieldGroup, FieldDefinition } from '../types';
import {
  Search, Plus, Filter, MoreHorizontal, Mail, Phone, MapPin,
  Trash2, Edit2, Download, X, Save, Archive, Calendar, CheckCircle, Clock, XCircle, AlertCircle,
  ChevronLeft, ChevronRight, ChevronDown, ArrowUpDown, Check, Search as SearchIcon, Settings, Edit,
  Users
} from 'lucide-react';
import { useApp } from '../context/AppContext';
import { countries } from '../utils/countries';
import ModuleLayout from '../components/ModuleLayout';
import { getModuleDesign } from '../utils/designTokens';

// --- REUSABLE COMPONENTS ---

interface SearchableSelectProps {
    options: { value: string; label: string; subLabel?: string }[];
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    className?: string;
    renderOption?: (option: any) => React.ReactNode;
}

const SearchableSelect: React.FC<SearchableSelectProps> = ({ options, value, onChange, placeholder = "Select...", className, renderOption }) => {
    const [isOpen, setIsOpen] = useState(false);
    const [search, setSearch] = useState("");
    const containerRef = useRef<HTMLDivElement>(null);

    const filteredOptions = options.filter(opt => 
        opt.label.toLowerCase().includes(search.toLowerCase()) || 
        (opt.subLabel && opt.subLabel.toLowerCase().includes(search.toLowerCase()))
    );

    const selectedOption = options.find(o => o.value === value);

    useEffect(() => {
        const handleClickOutside = (event: MouseEvent) => {
            if (containerRef.current && !containerRef.current.contains(event.target as Node)) {
                setIsOpen(false);
            }
        };
        document.addEventListener("mousedown", handleClickOutside);
        return () => document.removeEventListener("mousedown", handleClickOutside);
    }, []);

    return (
        <div className={`relative ${className}`} ref={containerRef}>
            <div 
                onClick={() => setIsOpen(!isOpen)}
                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-white flex justify-between items-center cursor-pointer focus:ring-2 focus:ring-brand-500 min-h-[38px]"
            >
                <span className={selectedOption ? "text-slate-800" : "text-slate-400"}>
                    {selectedOption ? (renderOption ? renderOption(selectedOption) : selectedOption.label) : placeholder}
                </span>
                <ChevronDown size={16} className="text-slate-400" />
            </div>

            {isOpen && (
                <div className="absolute z-50 top-full left-0 right-0 mt-1 bg-white border border-slate-200 rounded-lg shadow-xl max-h-60 flex flex-col overflow-hidden">
                    <div className="p-2 border-b border-slate-100 bg-slate-50">
                        <div className="relative">
                            <SearchIcon size={14} className="absolute left-2 top-1/2 -translate-y-1/2 text-slate-400" />
                            <input 
                                autoFocus
                                className="w-full pl-8 pr-2 py-1 text-sm border border-slate-200 rounded focus:outline-none focus:border-brand-500"
                                placeholder="Search..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>
                    </div>
                    <div className="overflow-y-auto flex-1">
                        {filteredOptions.length > 0 ? (
                            filteredOptions.map(opt => (
                                <div 
                                    key={opt.value}
                                    onClick={() => { onChange(opt.value); setIsOpen(false); setSearch(""); }}
                                    className={`px-3 py-2 text-sm cursor-pointer hover:bg-slate-50 flex items-center justify-between ${value === opt.value ? 'bg-brand-50 text-brand-700' : 'text-slate-700'}`}
                                >
                                    {renderOption ? renderOption(opt) : <span>{opt.label}</span>}
                                    {value === opt.value && <Check size={14} />}
                                </div>
                            ))
                        ) : (
                            <div className="p-3 text-center text-xs text-slate-400">No results found</div>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
};

const CustomersModule: React.FC = () => {
  const { customers, addCustomer, updateCustomer, deleteCustomer, appointments } = useApp();
  
  // --- Filter & Pagination State ---
  const [searchQuery, setSearchQuery] = useState('');
  const [isFilterOpen, setIsFilterOpen] = useState(false);
  const [activeFilters, setActiveFilters] = useState({
      status: [] as CustomerStatus[],
      sortBy: 'name_asc' as 'name_asc' | 'name_desc' | 'newest',
      birthday: '', // Exact date
      gender: '' as 'Male' | 'Female' | 'Other' | '',
      country: '',
      city: ''
  });

  const [currentPage, setCurrentPage] = useState(1);
  const [itemsPerPage, setItemsPerPage] = useState(10);

  // --- Modal State ---
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [isConfigModalOpen, setIsConfigModalOpen] = useState(false);
  const [editingCustomer, setEditingCustomer] = useState<Customer | null>(null);
  const [viewingBookingsFor, setViewingBookingsFor] = useState<Customer | null>(null);

  // Reset pagination when filters change
  useEffect(() => {
      setCurrentPage(1);
  }, [searchQuery, activeFilters, itemsPerPage]);

  // --- Actions ---

  const handleSave = (customer: Customer) => {
    if (editingCustomer) {
      updateCustomer(customer);
    } else {
      const newCustomer = { ...customer, id: Math.random().toString(36).substr(2, 9) };
      addCustomer(newCustomer);
    }
    setIsModalOpen(false);
    setEditingCustomer(null);
  };

  const handleDelete = (id: string) => {
    if (window.confirm('Are you sure you want to move this customer to trash?')) {
        const customer = customers.find(c => c.id === id);
        if(customer) {
            updateCustomer({ ...customer, status: CustomerStatus.DELETED });
        }
    }
  };

  const handleExportCSV = () => {
    const headers = ['ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Status', 'Street', 'Zip', 'City', 'Country', 'Birthday', 'Gender', 'Notes'];
    const rows = customers.map(c => [
      c.id, c.firstName, c.lastName, c.email, c.phone, c.status, 
      c.street || '', c.zip || '', c.city || '', c.country || '', 
      c.birthday || '', c.gender || '', c.notes || ''
    ]);
    
    const csvContent = "data:text/csv;charset=utf-8," 
      + headers.join(",") + "\n" 
      + rows.map(e => e.join(",")).join("\n");
      
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "customers_export.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  const toggleStatusFilter = (status: CustomerStatus) => {
      setActiveFilters(prev => {
          const exists = prev.status.includes(status);
          return {
              ...prev,
              status: exists 
                  ? prev.status.filter(s => s !== status)
                  : [...prev.status, status]
          };
      });
  };

  // --- Data Processing ---

  const processedCustomers = useMemo(() => {
    let result = [...customers];

    // 1. Search
    if (searchQuery) {
        const q = searchQuery.toLowerCase();
        result = result.filter(c =>
          c.firstName.toLowerCase().includes(q) ||
          c.lastName.toLowerCase().includes(q) ||
          c.email.toLowerCase().includes(q) ||
          c.phone.includes(q)
        );
    }

    // 2. Status Filter
    if (activeFilters.status.length > 0) {
        result = result.filter(c => activeFilters.status.includes(c.status));
    }

    // 3. Advanced Filters
    if (activeFilters.gender) {
        result = result.filter(c => c.gender === activeFilters.gender);
    }
    if (activeFilters.birthday) {
        result = result.filter(c => c.birthday === activeFilters.birthday);
    }
    if (activeFilters.city) {
        result = result.filter(c => (c.city || '').toLowerCase().includes(activeFilters.city.toLowerCase()));
    }
    if (activeFilters.country) {
        result = result.filter(c => (c.country || '').toLowerCase().includes(activeFilters.country.toLowerCase()));
    }

    // 4. Sorting
    result.sort((a, b) => {
        switch (activeFilters.sortBy) {
            case 'name_asc': 
                return a.lastName.localeCompare(b.lastName);
            case 'name_desc': 
                return b.lastName.localeCompare(a.lastName);
            case 'newest':
                // Assuming ID is somewhat chronological or we'd need a createdAt field
                return b.id.localeCompare(a.id); 
            default: return 0;
        }
    });

    return result;
  }, [customers, searchQuery, activeFilters]);

  // Pagination Logic
  const totalItems = processedCustomers.length;
  const totalPages = Math.ceil(totalItems / itemsPerPage);
  const paginatedCustomers = processedCustomers.slice(
      (currentPage - 1) * itemsPerPage, 
      currentPage * itemsPerPage
  );

  // Helper to generate page numbers (Smart Pagination)
  const getPageNumbers = () => {
      const delta = 2; // Numbers to show around current
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

  // Helper to get booking count
  const getBookingCount = (customerId: string) => {
      return appointments.filter(a => a.customerId === customerId).length;
  };

  const isFilterActive = 
    activeFilters.status.length > 0 || 
    activeFilters.sortBy !== 'name_asc' || 
    activeFilters.gender !== '' || 
    activeFilters.birthday !== '' || 
    activeFilters.city !== '' || 
    activeFilters.country !== '';

  // Country Options for Dropdown
  const countryOptions = countries.map(c => ({ value: c.name, label: c.name, subLabel: c.code }));

  // Get module design from tokens
  const moduleDesign = getModuleDesign('customers');

  // Filter Content for ModuleLayout
  const filterContent = (
    <div className="space-y-4">
      <div>
        <label className="block text-xs font-bold text-slate-500 uppercase mb-2">Status</label>
        <div className="space-y-2">
          {[CustomerStatus.ACTIVE, CustomerStatus.BLOCKED, CustomerStatus.DELETED].map(status => (
            <label key={status} className="flex items-center gap-2 cursor-pointer group">
              <div className={`w-4 h-4 rounded border flex items-center justify-center transition-colors ${activeFilters.status.includes(status) ? 'bg-brand-600 border-brand-600' : 'border-slate-300 bg-white'}`}>
                {activeFilters.status.includes(status) && <Check size={10} className="text-white" />}
              </div>
              <input type="checkbox" className="hidden" checked={activeFilters.status.includes(status)} onChange={() => toggleStatusFilter(status)} />
              <span className="text-sm text-slate-700 group-hover:text-slate-900">{status}</span>
            </label>
          ))}
        </div>
      </div>

      <div className="grid grid-cols-2 gap-3">
        <div>
          <label className="block text-xs font-bold text-slate-500 uppercase mb-1">Gender</label>
          <select
            className="w-full border border-slate-300 rounded-lg px-2 py-1.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-brand-500"
            value={activeFilters.gender}
            onChange={(e) => setActiveFilters(prev => ({...prev, gender: e.target.value as any}))}
          >
            <option value="">Any</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
            <option value="Other">Other</option>
          </select>
        </div>
        <div>
          <label className="block text-xs font-bold text-slate-500 uppercase mb-1">Birthday</label>
          <input
            type="date"
            className="w-full border border-slate-300 rounded-lg px-2 py-1.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-brand-500"
            value={activeFilters.birthday}
            onChange={(e) => setActiveFilters(prev => ({...prev, birthday: e.target.value}))}
          />
        </div>
      </div>

      <div className="gap-3 space-y-3">
        <div>
          <label className="block text-xs font-bold text-slate-500 uppercase mb-1">Country</label>
          <SearchableSelect
            options={countryOptions}
            value={activeFilters.country}
            onChange={(val) => setActiveFilters(prev => ({...prev, country: val}))}
            placeholder="All Countries"
          />
        </div>
        <div>
          <label className="block text-xs font-bold text-slate-500 uppercase mb-1">City</label>
          <input
            type="text"
            placeholder="Filter City"
            className="w-full border border-slate-300 rounded-lg px-2 py-1.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-brand-500"
            value={activeFilters.city}
            onChange={(e) => setActiveFilters(prev => ({...prev, city: e.target.value}))}
          />
        </div>
      </div>

      <div>
        <label className="block text-xs font-bold text-slate-500 uppercase mb-2">Sort Order</label>
        <div className="relative">
          <select
            className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm appearance-none bg-slate-50 focus:bg-white focus:ring-2 focus:ring-brand-500 outline-none"
            value={activeFilters.sortBy}
            onChange={(e) => setActiveFilters(prev => ({...prev, sortBy: e.target.value as any}))}
          >
            <option value="name_asc">Name (A-Z)</option>
            <option value="name_desc">Name (Z-A)</option>
            <option value="newest">Newest First</option>
          </select>
          <ArrowUpDown size={14} className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
        </div>
      </div>
    </div>
  );

  return (
    <div className={`flex flex-col min-h-full`}>
      <ModuleLayout
        variant="mixed"
        moduleName="Customers"
        hero={{
          icon: Users,
          title: 'Customers',
          description: 'Manage your client relationships and data.',
          gradient: moduleDesign.gradient
        }}
        tabs={[]} // No tabs - full width layout
        searchQuery={searchQuery}
        onSearchChange={setSearchQuery}
        showFilter={isFilterOpen}
        onToggleFilter={() => setIsFilterOpen(!isFilterOpen)}
        filterContent={filterContent}
        actions={
          <>
            <button
              onClick={handleExportCSV}
              className="flex items-center gap-2 px-4 py-2 border border-slate-200 rounded-xl text-slate-600 hover:bg-slate-50 text-sm font-medium transition-colors"
            >
              <Download size={16} />
              <span className="hidden md:inline">Export CSV</span>
            </button>
            <button
              onClick={() => setIsConfigModalOpen(true)}
              className="p-2 bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-xl shadow-sm transition-colors"
              title="Configure Fields"
            >
              <Settings size={20} />
            </button>
          </>
        }
        primaryAction={{
          label: 'Add Customer',
          icon: Plus,
          onClick: () => { setEditingCustomer(null); setIsModalOpen(true); }
        }}
      >
        <div className="flex-1 overflow-y-auto">
          <table className="w-full text-left border-collapse">
            <thead className="bg-slate-50 border-b border-slate-200 sticky top-0 z-10">
              <tr>
                <th className="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Customer</th>
                <th className="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Contact</th>
                <th className="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Address</th>
                <th className="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                <th className="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Bookings</th>
                <th className="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-200">
              {paginatedCustomers.map((customer) => (
                <tr key={customer.id} className={`hover:bg-slate-50 transition-colors group ${customer.status === CustomerStatus.DELETED ? 'opacity-60 bg-slate-50' : ''}`}>
                  <td className="p-4">
                    <div className="flex items-center gap-3">
                      <div className="w-10 h-10 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center font-semibold text-sm uppercase shrink-0">
                        {customer.firstName[0]}{customer.lastName[0]}
                      </div>
                      <div>
                        <div className="font-medium text-slate-900">{customer.firstName} {customer.lastName}</div>
                        <div className="text-xs text-slate-500 flex gap-2">
                            <span>ID: #{customer.id}</span>
                            {customer.birthday && <span>• {customer.birthday}</span>}
                        </div>
                      </div>
                    </div>
                  </td>
                  <td className="p-4">
                    <div className="flex flex-col gap-1 text-sm text-slate-600">
                      <div className="flex items-center gap-2">
                        <Mail size={14} className="text-slate-400" />
                        {customer.email}
                      </div>
                      {customer.phone && (
                          <div className="flex items-center gap-2">
                            <Phone size={14} className="text-slate-400" />
                            {customer.phone}
                          </div>
                      )}
                    </div>
                  </td>
                  <td className="p-4">
                    <div className="flex flex-col gap-0.5 text-sm text-slate-600">
                        {(customer.street || customer.city) ? (
                            <>
                                {customer.street && <div>{customer.street}</div>}
                                {(customer.zip || customer.city) && <div>{customer.zip} {customer.city}</div>}
                                {customer.country && <div className="text-xs text-slate-400">{customer.country}</div>}
                            </>
                        ) : (
                            <span className="text-slate-400 italic">No address</span>
                        )}
                    </div>
                  </td>
                  <td className="p-4">
                    <span className={`
                      inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border
                      ${customer.status === CustomerStatus.ACTIVE ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : ''}
                      ${customer.status === CustomerStatus.BLOCKED ? 'bg-rose-50 text-rose-700 border-rose-100' : ''}
                      ${customer.status === CustomerStatus.DELETED ? 'bg-slate-100 text-slate-600 border-slate-200' : ''}
                    `}>
                      {customer.status}
                    </span>
                  </td>
                  <td className="p-4 text-sm">
                    <button 
                        onClick={() => setViewingBookingsFor(customer)}
                        className="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 font-medium transition-colors border border-indigo-100"
                    >
                        <Calendar size={15} />
                        {getBookingCount(customer.id)} Bookings
                    </button>
                  </td>
                  <td className="p-4 text-right">
                    <div className="flex justify-end gap-2">
                      <button 
                        onClick={() => { setEditingCustomer(customer); setIsModalOpen(true); }}
                        className="p-2 text-slate-400 hover:text-brand-600 hover:bg-brand-50 rounded-full transition-colors"
                      >
                        <Edit2 size={18} />
                      </button>
                      {customer.status !== CustomerStatus.DELETED && (
                        <button 
                          onClick={() => handleDelete(customer.id)}
                          className="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-full transition-colors"
                        >
                          <Trash2 size={18} />
                        </button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
              {paginatedCustomers.length === 0 && (
                  <tr>
                      <td colSpan={6} className="p-12 text-center">
                          <div className="flex flex-col items-center text-slate-400">
                              <Search size={48} className="mb-4 opacity-20" />
                              <p className="text-lg font-medium text-slate-600">No customers found</p>
                              <p className="text-sm">Try adjusting your search or filters.</p>
                          </div>
                      </td>
                  </tr>
              )}
            </tbody>
          </table>
        </div>

        {/* Pagination Footer */}
        <div className="p-4 border-t border-slate-200 bg-slate-50 flex flex-col md:flex-row items-center justify-between gap-4">
            <div className="flex items-center gap-4 text-sm text-slate-600">
                <span>
                    Showing <span className="font-bold text-slate-900">{processedCustomers.length > 0 ? (currentPage - 1) * itemsPerPage + 1 : 0}</span> to <span className="font-bold text-slate-900">{Math.min(currentPage * itemsPerPage, totalItems)}</span> of <span className="font-bold text-slate-900">{totalItems}</span> customers
                </span>
                <select 
                    className="bg-white border border-slate-300 rounded-lg px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 cursor-pointer"
                    value={itemsPerPage}
                    onChange={(e) => setItemsPerPage(Number(e.target.value))}
                >
                    <option value={10}>10 per page</option>
                    <option value={25}>25 per page</option>
                    <option value={50}>50 per page</option>
                    <option value={100}>100 per page</option>
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
      </ModuleLayout>

      {/* Edit/Create Modal */}
      {isModalOpen && (
        <CustomerModal 
          customer={editingCustomer} 
          onClose={() => setIsModalOpen(false)} 
          onSave={handleSave} 
        />
      )}

      {/* Configuration Modal */}
      {isConfigModalOpen && (
          <DataConfigurationModal 
            onClose={() => setIsConfigModalOpen(false)}
          />
      )}

      {/* Bookings History Modal */}
      {viewingBookingsFor && (
        <BookingsHistoryModal 
            customer={viewingBookingsFor}
            onClose={() => setViewingBookingsFor(null)}
        />
      )}
    </div>
  );
};

// --- Bookings History Modal (Unchanged) ---
const BookingsHistoryModal: React.FC<{
    customer: Customer;
    onClose: () => void;
}> = ({ customer, onClose }) => {
    const { appointments } = useApp();
    const bookings = appointments.filter(a => a.customerId === customer.id).sort((a,b) => new Date(b.date).getTime() - new Date(a.date).getTime());

    const getStatusColor = (status: string) => {
        switch(status) {
            case 'Completed': return 'text-emerald-600 bg-emerald-50 border-emerald-100';
            case 'Confirmed': return 'text-blue-600 bg-blue-50 border-blue-100';
            case 'Pending': return 'text-amber-600 bg-amber-50 border-amber-100';
            case 'Cancelled': return 'text-slate-500 bg-slate-100 border-slate-200 line-through';
            default: return 'text-slate-600 bg-slate-50 border-slate-100';
        }
    };

    const getStatusIcon = (status: string) => {
        switch(status) {
            case 'Completed': return <CheckCircle size={14} />;
            case 'Confirmed': return <CheckCircle size={14} />;
            case 'Pending': return <Clock size={14} />;
            case 'Cancelled': return <XCircle size={14} />;
            default: return <AlertCircle size={14} />;
        }
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
            <div className="bg-white rounded-xl shadow-2xl w-full max-w-3xl max-h-[80vh] flex flex-col">
                <div className="flex justify-between items-center p-6 border-b border-slate-200">
                    <div>
                        <h3 className="text-xl font-bold text-slate-800">Booking History</h3>
                        <p className="text-sm text-slate-500">
                            for {customer.firstName} {customer.lastName}
                        </p>
                    </div>
                    <button onClick={onClose} className="text-slate-400 hover:text-slate-600 p-2 rounded-full hover:bg-slate-100">
                        <X size={24} />
                    </button>
                </div>

                <div className="flex-1 overflow-y-auto p-6">
                    {bookings.length === 0 ? (
                        <div className="text-center py-12 text-slate-400 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                            <Calendar size={48} className="mx-auto mb-3 opacity-30" />
                            <p>No bookings found for this customer.</p>
                        </div>
                    ) : (
                        <div className="space-y-4">
                            {bookings.map((booking) => (
                                <div key={booking.id} className="flex flex-col md:flex-row items-start md:items-center justify-between p-4 bg-white border border-slate-200 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                                    <div className="flex items-start gap-4 mb-3 md:mb-0">
                                        <div className="p-3 bg-indigo-50 text-indigo-600 rounded-lg shrink-0">
                                            <Calendar size={20} />
                                        </div>
                                        <div>
                                            <h4 className="font-bold text-slate-800">{booking.serviceName}</h4>
                                            <div className="flex items-center gap-3 text-sm text-slate-500 mt-1">
                                                <span className="flex items-center gap-1">
                                                    <Calendar size={14} /> {booking.date}
                                                </span>
                                                <span className="flex items-center gap-1">
                                                    <Clock size={14} /> {booking.startTime}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-4 w-full md:w-auto justify-between md:justify-end">
                                        <span className="font-bold text-slate-700">${booking.price}</span>
                                        <span className={`flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border ${getStatusColor(booking.status)}`}>
                                            {getStatusIcon(booking.status)}
                                            {booking.status}
                                        </span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                <div className="p-4 border-t border-slate-200 bg-slate-50 rounded-b-xl text-right">
                    <button onClick={onClose} className="px-6 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-lg font-medium shadow-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
    );
};

// --- Data Configuration Modal ---
const DataConfigurationModal: React.FC<{ onClose: () => void }> = ({ onClose }) => {
    const { customerFieldDefinitions, setCustomerFieldDefinitions } = useApp();
    const [groups, setGroups] = useState<FieldGroup[]>(customerFieldDefinitions);
    const [newGroupTitle, setNewGroupTitle] = useState('');

    // State for adding/editing a field
    const [activeGroupId, setActiveGroupId] = useState<string | null>(null);
    const [editingFieldId, setEditingFieldId] = useState<string | null>(null); // If set, we are editing
    const [fieldForm, setFieldForm] = useState<FieldDefinition>({
        id: '', label: '', type: 'text', required: false, options: []
    });
    const [optionsString, setOptionsString] = useState('');

    const handleSave = () => {
        setCustomerFieldDefinitions(groups);
        onClose();
    };

    const addGroup = () => {
        if (!newGroupTitle.trim()) return;
        const newGroup: FieldGroup = {
            id: `grp_${Date.now()}`,
            title: newGroupTitle,
            fields: []
        };
        setGroups([...groups, newGroup]);
        setNewGroupTitle('');
    };

    const deleteGroup = (idx: number) => {
        if (confirm('Delete this group and all its fields?')) {
            const newGroups = [...groups];
            newGroups.splice(idx, 1);
            setGroups(newGroups);
        }
    };

    const saveFieldToGroup = () => {
        if (!activeGroupId || !fieldForm.label) return;
        
        const fieldData = {
            ...fieldForm,
            id: fieldForm.id || fieldForm.label.toLowerCase().replace(/\s+/g, '_'), // Generate ID if missing
            options: fieldForm.type === 'select' ? optionsString.split(',').map(s => s.trim()) : undefined
        };

        setGroups(groups.map(g => {
            if (g.id === activeGroupId) {
                if (editingFieldId) {
                    // Update existing
                    return {
                        ...g,
                        fields: g.fields.map(f => f.id === editingFieldId ? fieldData : f)
                    };
                } else {
                    // Add new
                    return { ...g, fields: [...g.fields, fieldData] };
                }
            }
            return g;
        }));

        // Reset
        resetFieldForm();
    };

    const startEditField = (groupId: string, field: FieldDefinition) => {
        setActiveGroupId(groupId);
        setEditingFieldId(field.id);
        setFieldForm(field);
        setOptionsString(field.options?.join(', ') || '');
    };

    const startAddField = (groupId: string) => {
        setActiveGroupId(groupId);
        resetFieldForm();
    };

    const resetFieldForm = () => {
        setEditingFieldId(null);
        setFieldForm({ id: '', label: '', type: 'text', required: false, options: [] });
        setOptionsString('');
        // Keep activeGroupId to stay in the form view if desired, or null to close
        // setActiveGroupId(null); 
    };

    const deleteField = (groupId: string, fieldIdx: number) => {
        if(!confirm("Delete field?")) return;
        setGroups(groups.map(g => {
            if (g.id === groupId) {
                const newFields = [...g.fields];
                newFields.splice(fieldIdx, 1);
                return { ...g, fields: newFields };
            }
            return g;
        }));
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
            <div className="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col animate-fadeIn">
                <div className="p-6 border-b border-slate-200 flex justify-between items-center bg-white rounded-t-xl">
                    <div>
                        <h3 className="text-xl font-bold text-slate-800">Master Data Configuration</h3>
                        <p className="text-sm text-slate-500">Define custom data fields and groups for customers.</p>
                    </div>
                    <button onClick={onClose} className="text-slate-400 hover:text-slate-600"><X size={24}/></button>
                </div>

                <div className="flex-1 overflow-y-auto p-6 bg-slate-50">
                    <div className="space-y-6">
                        {/* Group List */}
                        {groups.map((group, idx) => (
                            <div key={group.id} className="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                                <div className="p-4 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
                                    <h4 className="font-bold text-slate-700">{group.title}</h4>
                                    <button onClick={() => deleteGroup(idx)} className="text-slate-400 hover:text-rose-600"><Trash2 size={16} /></button>
                                </div>
                                <div className="p-4">
                                    {group.fields.length === 0 && <p className="text-sm text-slate-400 italic mb-3">No fields in this group yet.</p>}
                                    <div className="space-y-2">
                                        {group.fields.map((field, fIdx) => (
                                            <div key={field.id} className="flex items-center justify-between p-3 bg-slate-50 rounded border border-slate-100">
                                                <div>
                                                    <span className="font-medium text-sm text-slate-800">{field.label}</span>
                                                    <span className="text-xs text-slate-500 ml-2">({field.type})</span>
                                                    {field.required && <span className="ml-2 text-xs text-rose-500 font-bold">*Required</span>}
                                                </div>
                                                <div className="flex gap-2">
                                                    <button onClick={() => startEditField(group.id, field)} className="text-slate-400 hover:text-brand-600">
                                                        <Edit size={14} />
                                                    </button>
                                                    <button onClick={() => deleteField(group.id, fIdx)} className="text-slate-400 hover:text-rose-500">
                                                        <Trash2 size={14} />
                                                    </button>
                                                </div>
                                            </div>
                                        ))}
                                    </div>

                                    {/* Add/Edit Field Form */}
                                    {activeGroupId === group.id ? (
                                        <div className="mt-4 p-4 bg-indigo-50 rounded-lg border border-indigo-100 animate-fadeIn">
                                            <h5 className="text-xs font-bold text-indigo-800 uppercase mb-2">
                                                {editingFieldId ? 'Edit Field' : 'Add New Field'}
                                            </h5>
                                            <div className="grid grid-cols-2 gap-3 mb-3">
                                                <input 
                                                    placeholder="Field Label (e.g. License Number)" 
                                                    className="border border-indigo-200 rounded px-3 py-2 text-sm"
                                                    value={fieldForm.label}
                                                    onChange={e => {
                                                        const label = e.target.value;
                                                        // Only auto-update ID if adding new
                                                        const newId = editingFieldId ? fieldForm.id : label.toLowerCase().replace(/\s+/g, '_');
                                                        setFieldForm({...fieldForm, label, id: newId});
                                                    }}
                                                />
                                                <select 
                                                    className="border border-indigo-200 rounded px-3 py-2 text-sm bg-white"
                                                    value={fieldForm.type}
                                                    onChange={e => setFieldForm({...fieldForm, type: e.target.value as any})}
                                                >
                                                    <option value="text">Text</option>
                                                    <option value="number">Number</option>
                                                    <option value="date">Date</option>
                                                    <option value="select">Select / Dropdown</option>
                                                </select>
                                            </div>
                                            {fieldForm.type === 'select' && (
                                                <div className="mb-3">
                                                    <input 
                                                        placeholder="Options (comma separated: A, B, C)" 
                                                        className="w-full border border-indigo-200 rounded px-3 py-2 text-sm"
                                                        value={optionsString}
                                                        onChange={e => setOptionsString(e.target.value)}
                                                    />
                                                </div>
                                            )}
                                            <div className="flex items-center justify-between">
                                                <label className="flex items-center gap-2 cursor-pointer">
                                                    <input type="checkbox" checked={fieldForm.required} onChange={e => setFieldForm({...fieldForm, required: e.target.checked})} />
                                                    <span className="text-sm text-indigo-900">Mandatory Field</span>
                                                </label>
                                                <div className="flex gap-2">
                                                    <button onClick={() => setActiveGroupId(null)} className="text-xs text-slate-500 hover:text-slate-700">Cancel</button>
                                                    <button onClick={saveFieldToGroup} className="bg-indigo-600 text-white px-3 py-1.5 rounded text-xs font-bold hover:bg-indigo-700">
                                                        {editingFieldId ? 'Update Field' : 'Add Field'}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    ) : (
                                        <button 
                                            onClick={() => startAddField(group.id)}
                                            className="mt-3 w-full py-2 border-2 border-dashed border-slate-200 rounded-lg text-slate-500 text-sm font-medium hover:border-brand-300 hover:text-brand-600 flex items-center justify-center gap-2"
                                        >
                                            <Plus size={14} /> Add Field
                                        </button>
                                    )}
                                </div>
                            </div>
                        ))}

                        {/* Add Group */}
                        <div className="flex gap-3 p-4 bg-white rounded-xl border border-slate-200 items-center">
                            <input 
                                placeholder="New Category Title (e.g. Driving License Data)"
                                className="flex-1 border border-slate-300 rounded-lg px-3 py-2 text-sm"
                                value={newGroupTitle}
                                onChange={e => setNewGroupTitle(e.target.value)}
                            />
                            <button onClick={addGroup} className="bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-900">
                                Create Group
                            </button>
                        </div>
                    </div>
                </div>

                <div className="p-6 border-t border-slate-200 bg-white rounded-b-xl flex justify-end gap-3">
                    <button onClick={onClose} className="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button onClick={handleSave} className="px-6 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-medium shadow-sm">Save Configuration</button>
                </div>
            </div>
        </div>
    );
};

// --- Customer Modal Component ---
const CustomerModal: React.FC<{ 
  customer: Customer | null; 
  onClose: () => void; 
  onSave: (c: Customer) => void; 
}> = ({ customer, onClose, onSave }) => {
  const { customerFieldDefinitions } = useApp();

  // Parse initial phone to separate prefix and number
  const parsePhone = (phoneStr: string) => {
      if (!phoneStr) return { code: '+41', number: '' }; // Default to Swiss
      // Try to find a matching code from our list
      const match = countries.find(c => phoneStr.startsWith(c.dial_code));
      if (match) {
          return { code: match.dial_code, number: phoneStr.replace(match.dial_code, '').trim() };
      }
      return { code: '+41', number: phoneStr }; // Fallback
  };

  const initialPhone = parsePhone(customer?.phone || '');

  const [formData, setFormData] = useState<Customer>(customer || {
    id: '',
    firstName: '',
    lastName: '',
    email: '',
    phone: '',
    status: CustomerStatus.ACTIVE,
    bookings: [],
    street: '',
    zip: '',
    city: '',
    country: '',
    birthday: '',
    gender: 'Prefer not to say',
    notes: '',
    customFields: []
  });

  const [phoneDetails, setPhoneDetails] = useState({
      code: initialPhone.code,
      number: initialPhone.number
  });

  const [newCustomField, setNewCustomField] = useState({ key: '', value: '' });

  // Update full phone string whenever parts change
  useEffect(() => {
      const fullPhone = phoneDetails.number ? `${phoneDetails.code} ${phoneDetails.number}` : '';
      setFormData(prev => ({ ...prev, phone: fullPhone }));
  }, [phoneDetails]);

  const handleChange = (field: keyof Customer, value: any) => {
    setFormData(prev => ({ ...prev, [field]: value }));
  };

  const handleCustomFieldChange = (key: string, value: string) => {
      // Find if field exists
      const exists = formData.customFields?.find(f => f.key === key);
      let newFields = formData.customFields || [];
      
      if (exists) {
          newFields = newFields.map(f => f.key === key ? { ...f, value } : f);
      } else {
          newFields = [...newFields, { key, value }];
      }
      handleChange('customFields', newFields);
  };

  const getCustomFieldValue = (key: string) => {
      return formData.customFields?.find(f => f.key === key)?.value || '';
  };

  const addCustomField = () => {
    if (newCustomField.key && newCustomField.value) {
      setFormData(prev => ({
        ...prev,
        customFields: [...(prev.customFields || []), newCustomField]
      }));
      setNewCustomField({ key: '', value: '' });
    }
  };

  const removeCustomField = (index: number) => {
     setFormData(prev => ({
        ...prev,
        customFields: prev.customFields?.filter((_, i) => i !== index)
     }));
  };

  const dialCodeOptions = countries.map(c => ({
      value: c.dial_code,
      label: `${c.flag} ${c.dial_code}`,
      subLabel: c.name
  }));

  const countryOptions = countries.map(c => ({
      value: c.name,
      label: c.name,
      subLabel: c.code
  }));

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
      <div className="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto flex flex-col animate-fadeIn">
        <div className="flex justify-between items-center p-6 border-b border-slate-200 sticky top-0 bg-white z-10">
          <h3 className="text-xl font-bold text-slate-800">
            {customer ? 'Edit Customer' : 'New Customer'}
          </h3>
          <button onClick={onClose} className="text-slate-400 hover:text-slate-600">
            <X size={24} />
          </button>
        </div>
        
        <div className="p-6 space-y-6">
           {/* Basic Info */}
           <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-1">First Name</label>
                <input 
                  type="text" 
                  className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none"
                  value={formData.firstName}
                  onChange={e => handleChange('firstName', e.target.value)}
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-1">Last Name</label>
                <input 
                  type="text" 
                  className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none"
                  value={formData.lastName}
                  onChange={e => handleChange('lastName', e.target.value)}
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input 
                  type="email" 
                  className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none"
                  value={formData.email}
                  onChange={e => handleChange('email', e.target.value)}
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-1">Phone</label>
                <div className="flex gap-2">
                    <div className="w-32 shrink-0">
                        <SearchableSelect 
                            options={dialCodeOptions}
                            value={phoneDetails.code}
                            onChange={(val) => setPhoneDetails(prev => ({...prev, code: val}))}
                            renderOption={(opt) => <span className="font-mono">{opt.label}</span>}
                        />
                    </div>
                    <input 
                      type="tel" 
                      className="flex-1 border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none"
                      value={phoneDetails.number}
                      onChange={e => setPhoneDetails(prev => ({...prev, number: e.target.value}))}
                      placeholder="79 123 45 67"
                    />
                </div>
              </div>
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-1">Birthday</label>
                <input 
                  type="date" 
                  className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none"
                  value={formData.birthday || ''}
                  onChange={e => handleChange('birthday', e.target.value)}
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-1">Gender</label>
                <select 
                  className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none bg-white"
                  value={formData.gender}
                  onChange={e => handleChange('gender', e.target.value)}
                >
                   <option value="Male">Male</option>
                   <option value="Female">Female</option>
                   <option value="Other">Other</option>
                   <option value="Prefer not to say">Prefer not to say</option>
                </select>
              </div>
           </div>

           {/* Detailed Address */}
           <div className="border-t border-slate-100 pt-4">
              <label className="block text-sm font-bold text-slate-800 mb-3">Address Details</label>
              <div className="grid grid-cols-1 gap-4">
                  <div>
                      <label className="block text-xs font-medium text-slate-500 mb-1">Street</label>
                      <input 
                        type="text" 
                        className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none"
                        value={formData.street || ''}
                        onChange={e => handleChange('street', e.target.value)}
                        placeholder="e.g. Bahnhofstrasse 10"
                      />
                  </div>
                  <div className="grid grid-cols-3 gap-4">
                      <div className="col-span-1">
                          <label className="block text-xs font-medium text-slate-500 mb-1">Zip Code</label>
                          <input 
                            type="text" 
                            className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none"
                            value={formData.zip || ''}
                            onChange={e => handleChange('zip', e.target.value)}
                            placeholder="8001"
                          />
                      </div>
                      <div className="col-span-2">
                          <label className="block text-xs font-medium text-slate-500 mb-1">City</label>
                          <input 
                            type="text" 
                            className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none"
                            value={formData.city || ''}
                            onChange={e => handleChange('city', e.target.value)}
                            placeholder="Zurich"
                          />
                      </div>
                  </div>
                  <div>
                      <label className="block text-xs font-medium text-slate-500 mb-1">Country</label>
                      <SearchableSelect 
                        options={countryOptions}
                        value={formData.country || ''}
                        onChange={(val) => handleChange('country', val)}
                        placeholder="Select Country..."
                      />
                  </div>
              </div>
           </div>

           {/* Dynamic Field Groups */}
           {customerFieldDefinitions.map(group => (
               <div key={group.id} className="border-t border-slate-100 pt-4">
                   <label className="block text-sm font-bold text-slate-800 mb-3">{group.title}</label>
                   <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                       {group.fields.map(field => (
                           <div key={field.id} className={field.type === 'text' ? '' : ''}>
                               <label className="block text-sm font-medium text-slate-700 mb-1">
                                   {field.label} {field.required && <span className="text-rose-500">*</span>}
                               </label>
                               
                               {field.type === 'text' || field.type === 'number' ? (
                                   <input 
                                      type={field.type}
                                      className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none"
                                      value={getCustomFieldValue(field.id)}
                                      onChange={e => handleCustomFieldChange(field.id, e.target.value)}
                                   />
                               ) : field.type === 'date' ? (
                                   <input 
                                      type="date"
                                      className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none"
                                      value={getCustomFieldValue(field.id)}
                                      onChange={e => handleCustomFieldChange(field.id, e.target.value)}
                                   />
                               ) : field.type === 'select' ? (
                                   <select
                                      className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none bg-white"
                                      value={getCustomFieldValue(field.id)}
                                      onChange={e => handleCustomFieldChange(field.id, e.target.value)}
                                   >
                                       <option value="">Select...</option>
                                       {field.options?.map(opt => (
                                           <option key={opt} value={opt}>{opt}</option>
                                       ))}
                                   </select>
                               ) : null}
                           </div>
                       ))}
                   </div>
               </div>
           ))}

           {/* Status & Notes */}
           <div className="border-t border-slate-100 pt-4 grid grid-cols-1 gap-4">
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-1">Status</label>
                <select 
                  className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none bg-white"
                  value={formData.status}
                  onChange={e => handleChange('status', e.target.value)}
                >
                   <option value={CustomerStatus.ACTIVE}>Active</option>
                   <option value={CustomerStatus.BLOCKED}>Blocked</option>
                   <option value={CustomerStatus.DELETED}>Deleted</option>
                </select>
              </div>
              <div>
                 <label className="block text-sm font-medium text-slate-700 mb-1">Internal Notes</label>
                 <textarea 
                    className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none"
                    rows={3}
                    value={formData.notes}
                    onChange={e => handleChange('notes', e.target.value)}
                 />
              </div>
           </div>

           {/* Legacy Custom Fields (Manual) */}
           <div className="border-t border-slate-100 pt-4">
              <label className="block text-sm font-medium text-slate-500 mb-2 uppercase text-xs">Other Custom Fields (Legacy)</label>
              <div className="space-y-2 mb-3">
                 {formData.customFields?.filter(f => !customerFieldDefinitions.some(g => g.fields.some(field => field.id === f.key))).map((field, idx) => (
                    <div key={idx} className="flex gap-2">
                       <input disabled value={field.key} className="bg-slate-50 border border-slate-300 rounded px-2 py-1 text-sm w-1/3" />
                       <input disabled value={field.value} className="bg-slate-50 border border-slate-300 rounded px-2 py-1 text-sm flex-1" />
                       <button onClick={() => removeCustomField(idx)} className="text-rose-500 hover:text-rose-700">
                          <X size={16} />
                       </button>
                    </div>
                 ))}
              </div>
              <div className="flex gap-2">
                 <input 
                    placeholder="Field Name (e.g. Allergies)" 
                    className="border border-slate-300 rounded px-3 py-2 text-sm w-1/3 outline-none focus:border-brand-500"
                    value={newCustomField.key}
                    onChange={e => setNewCustomField({...newCustomField, key: e.target.value})}
                 />
                 <input 
                    placeholder="Value" 
                    className="border border-slate-300 rounded px-3 py-2 text-sm flex-1 outline-none focus:border-brand-500"
                    value={newCustomField.value}
                    onChange={e => setNewCustomField({...newCustomField, value: e.target.value})}
                 />
                 <button 
                    onClick={addCustomField}
                    type="button"
                    className="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 rounded-lg transition-colors"
                 >
                    <Plus size={18} />
                 </button>
              </div>
           </div>
        </div>

        <div className="p-6 border-t border-slate-200 bg-slate-50 sticky bottom-0 rounded-b-xl flex justify-end gap-3">
           <button onClick={onClose} className="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-white font-medium">
              Cancel
           </button>
           <button onClick={() => onSave(formData)} className="px-6 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-medium shadow-sm">
              Save Customer
           </button>
        </div>
      </div>
    </div>
  );
};

export default CustomersModule;