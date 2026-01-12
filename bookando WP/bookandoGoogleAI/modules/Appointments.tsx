
import React, { useState, useMemo, useRef, useEffect } from 'react';
import { 
  Calendar as CalendarIcon, Clock, User, MapPin, MoreVertical, Check, X, 
  ChevronLeft, ChevronRight, Search, Filter, Plus, List, Grid, AlignJustify,
  Phone, Mail, DollarSign, Tag, Briefcase, ChevronDown, ChevronUp,
  Trash2, Save
} from 'lucide-react';
import { useApp } from '../context/AppContext';
import { ServiceItem, DynamicPricingRule, Appointment, AppointmentStatus } from '../types';

type ViewMode = 'calendar' | 'list';
type CalendarMode = 'day' | 'week' | 'month';

const mockEmployees = [
  { id: 'emp1', name: 'Sarah Jenkins' },
  { id: 'emp2', name: 'Mike Ross' },
  { id: 'emp3', name: 'Jessica Pearson' },
];

// --- Helper Functions ---

const getStatusColor = (status: AppointmentStatus) => {
  switch (status) {
    case 'Confirmed': return 'bg-emerald-100 text-emerald-700 border-emerald-200';
    case 'Pending': return 'bg-amber-100 text-amber-700 border-amber-200';
    case 'Cancelled': return 'bg-rose-100 text-rose-700 border-rose-200';
    case 'Completed': return 'bg-blue-100 text-blue-700 border-blue-200';
    case 'No-Show': return 'bg-slate-200 text-slate-600 border-slate-300';
    default: return 'bg-slate-100 text-slate-600';
  }
};

const formatDate = (date: Date): string => {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

const addDays = (date: Date, days: number): Date => {
  const result = new Date(date);
  result.setDate(result.getDate() + days);
  return result;
};

const getStartOfWeek = (date: Date): Date => {
  const d = new Date(date);
  const day = d.getDay();
  const diff = d.getDate() - day + (day === 0 ? -6 : 1); // Adjust when day is Sunday
  return new Date(d.setDate(diff));
};

const getWeekNumber = (d: Date): number => {
    const date = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
    date.setUTCDate(date.getUTCDate() + 4 - (date.getUTCDay() || 7));
    const yearStart = new Date(Date.UTC(date.getUTCFullYear(), 0, 1));
    return Math.ceil((((date.getTime() - yearStart.getTime()) / 86400000) + 1) / 7);
};

// --- Main Component ---

const AppointmentsModule: React.FC = () => {
  const { t, services, pricingRules, appointments, addAppointment, updateAppointment, deleteAppointment } = useApp();
  const dateInputRef = useRef<HTMLInputElement>(null);
  
  // State
  const [viewMode, setViewMode] = useState<ViewMode>('calendar');
  const [calendarMode, setCalendarMode] = useState<CalendarMode>('week');
  const [currentDate, setCurrentDate] = useState<Date>(new Date('2023-10-24')); // Fixed date for mock data demo
  const [isFilterExpanded, setIsFilterExpanded] = useState(false);
  
  // Filters
  const [searchQuery, setSearchQuery] = useState('');
  const [filters, setFilters] = useState({
    employeeId: 'all',
    customerId: '',
    category: 'all',
    status: 'all',
  });

  // Modal
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingAppointment, setEditingAppointment] = useState<Appointment | null>(null);

  // --- Actions ---

  const handleDateChange = (direction: 'prev' | 'next' | 'today' | 'pick', pickedDate?: string) => {
    if (direction === 'pick' && pickedDate) {
        setCurrentDate(new Date(pickedDate));
        return;
    }
    
    if (direction === 'today') {
      setCurrentDate(new Date());
      return;
    }

    const amount = direction === 'next' ? 1 : -1;
    
    if (calendarMode === 'day') {
      setCurrentDate(addDays(currentDate, amount));
    } else if (calendarMode === 'week') {
      setCurrentDate(addDays(currentDate, amount * 7));
    } else {
      // Month
      const newDate = new Date(currentDate);
      newDate.setMonth(newDate.getMonth() + amount);
      setCurrentDate(newDate);
    }
  };

  const getHeaderLabel = () => {
      if (calendarMode === 'day') {
          return currentDate.toLocaleDateString('en-US', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
      } else if (calendarMode === 'week') {
          const start = getStartOfWeek(currentDate);
          const end = addDays(start, 6);
          const kw = getWeekNumber(currentDate);
          return `KW ${kw} (${start.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${end.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })})`;
      } else {
          return currentDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
      }
  };

  const handleSave = (apt: Appointment) => {
    if (editingAppointment) {
      updateAppointment(apt);
    } else {
      const newId = Math.random().toString(36).substr(2, 9);
      addAppointment({ ...apt, id: newId });
    }
    setIsModalOpen(false);
    setEditingAppointment(null);
  };

  const handleDelete = (id: string) => {
    if(window.confirm("Cancel this appointment?")) {
      // Just changing status instead of hard delete for history
      const appt = appointments.find(a => a.id === id);
      if(appt) {
          updateAppointment({...appt, status: 'Cancelled'});
      }
    }
  };

  // --- Filtering Logic ---

  const filteredAppointments = useMemo(() => {
    return appointments.filter(apt => {
      // Text Search
      const searchMatch = 
        apt.customerName.toLowerCase().includes(searchQuery.toLowerCase()) ||
        apt.serviceName.toLowerCase().includes(searchQuery.toLowerCase()) ||
        apt.title.toLowerCase().includes(searchQuery.toLowerCase());

      // Sidebar Filters
      const empMatch = filters.employeeId === 'all' || apt.employeeId === filters.employeeId;
      const catMatch = filters.category === 'all' || apt.category === filters.category;
      const statusMatch = filters.status === 'all' || apt.status === filters.status;
      
      return searchMatch && empMatch && catMatch && statusMatch;
    });
  }, [appointments, searchQuery, filters]);

  // Grouping for List View
  const groupedAppointments = useMemo(() => {
    const groups: Record<string, Appointment[]> = {};
    filteredAppointments
      .sort((a, b) => new Date(a.date + 'T' + a.startTime).getTime() - new Date(b.date + 'T' + b.startTime).getTime())
      .forEach(apt => {
        if (!groups[apt.date]) groups[apt.date] = [];
        groups[apt.date].push(apt);
      });
    return groups;
  }, [filteredAppointments]);

  // --- Render ---

  return (
    <div className="h-full flex flex-col space-y-4 animate-fadeIn">
      {/* Enhanced Header */}
      <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col gap-4">
          {/* Top Row: Nav & Controls */}
          <div className="flex flex-col lg:flex-row justify-between items-center gap-4">
             {/* Date Navigation */}
            <div className="flex items-center gap-4 w-full lg:w-auto justify-between lg:justify-start">
                <div className="flex items-center bg-slate-100 rounded-lg p-1">
                    <button onClick={() => handleDateChange('prev')} className="p-1 hover:bg-white rounded-md text-slate-600 shadow-sm transition-all"><ChevronLeft size={20} /></button>
                    <button onClick={() => handleDateChange('today')} className="px-3 py-1 text-sm font-semibold text-slate-700 hover:bg-white rounded-md mx-1 transition-all">Today</button>
                    <button onClick={() => handleDateChange('next')} className="p-1 hover:bg-white rounded-md text-slate-600 shadow-sm transition-all"><ChevronRight size={20} /></button>
                </div>
                <div className="relative group cursor-pointer" onClick={() => dateInputRef.current?.showPicker()}>
                    <h2 className="text-xl font-bold text-slate-800 hover:text-brand-600 transition-colors select-none">
                        {getHeaderLabel()}
                    </h2>
                    <input 
                        type={calendarMode === 'month' ? "month" : "date"}
                        ref={dateInputRef}
                        className="absolute inset-0 opacity-0 cursor-pointer"
                        value={calendarMode === 'month' ? formatDate(currentDate).slice(0, 7) : formatDate(currentDate)}
                        onChange={(e) => handleDateChange('pick', e.target.value)}
                    />
                </div>
            </div>

            {/* Controls */}
            <div className="flex flex-wrap gap-3 w-full lg:w-auto justify-end">
                <div className="flex bg-slate-100 p-1 rounded-lg">
                    <button 
                    onClick={() => setViewMode('calendar')} 
                    className={`flex items-center gap-2 px-3 py-1.5 rounded-md text-sm font-medium transition-all ${viewMode === 'calendar' ? 'bg-white shadow text-brand-700' : 'text-slate-500 hover:text-slate-700'}`}
                    >
                    <CalendarIcon size={16} /> Calendar
                    </button>
                    <button 
                    onClick={() => setViewMode('list')} 
                    className={`flex items-center gap-2 px-3 py-1.5 rounded-md text-sm font-medium transition-all ${viewMode === 'list' ? 'bg-white shadow text-brand-700' : 'text-slate-500 hover:text-slate-700'}`}
                    >
                    <List size={16} /> List
                    </button>
                </div>

                {viewMode === 'calendar' && (
                    <div className="flex bg-slate-100 p-1 rounded-lg">
                    {['day', 'week', 'month'].map((m) => (
                        <button 
                        key={m}
                        onClick={() => setCalendarMode(m as CalendarMode)}
                        className={`px-3 py-1.5 rounded-md text-sm font-medium capitalize transition-all ${calendarMode === m ? 'bg-white shadow text-indigo-700' : 'text-slate-500 hover:text-slate-700'}`}
                        >
                        {m}
                        </button>
                    ))}
                    </div>
                )}

                <button 
                    onClick={() => { setEditingAppointment(null); setIsModalOpen(true); }}
                    className="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg shadow-sm flex items-center gap-2 text-sm font-medium transition-colors"
                >
                    <Plus size={18} /> New Booking
                </button>
            </div>
          </div>

          {/* Second Row: Filter & Search (Directly Below) */}
          <div className="flex flex-col sm:flex-row gap-4 border-t border-slate-100 pt-4">
              <div className="relative flex-1">
                  <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={16} />
                  <input 
                      value={searchQuery}
                      onChange={e => setSearchQuery(e.target.value)}
                      placeholder="Search customer, service, or title..." 
                      className="w-full pl-9 pr-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 outline-none bg-slate-50 focus:bg-white transition-colors"
                  />
              </div>
              <button 
                  onClick={() => setIsFilterExpanded(!isFilterExpanded)}
                  className={`flex items-center gap-2 px-4 py-2 border rounded-lg text-sm font-medium transition-colors ${isFilterExpanded ? 'bg-brand-50 border-brand-200 text-brand-700' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'}`}
              >
                  <Filter size={16} /> Filters
                  {isFilterExpanded ? <ChevronUp size={14} /> : <ChevronDown size={14} />}
              </button>
          </div>

          {/* Third Row: Expandable Filters (Accordion style) */}
          {isFilterExpanded && (
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2 animate-slideDown">
                  <div>
                    <label className="block text-xs font-bold text-slate-500 uppercase mb-1">Employee</label>
                    <select 
                       className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-slate-50 focus:bg-white"
                       value={filters.employeeId}
                       onChange={e => setFilters({...filters, employeeId: e.target.value})}
                    >
                       <option value="all">All Employees</option>
                       {mockEmployees.map(e => <option key={e.id} value={e.id}>{e.name}</option>)}
                    </select>
                 </div>
                 <div>
                    <label className="block text-xs font-bold text-slate-500 uppercase mb-1">Category</label>
                    <select 
                       className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-slate-50 focus:bg-white"
                       value={filters.category}
                       onChange={e => setFilters({...filters, category: e.target.value})}
                    >
                       <option value="all">All Categories</option>
                       <option value="Wellness">Wellness</option>
                       <option value="Fitness">Fitness</option>
                       <option value="Health">Health</option>
                    </select>
                 </div>
                 <div>
                    <label className="block text-xs font-bold text-slate-500 uppercase mb-1">Status</label>
                    <select 
                       className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-slate-50 focus:bg-white"
                       value={filters.status}
                       onChange={e => setFilters({...filters, status: e.target.value})}
                    >
                       <option value="all">All Statuses</option>
                       <option value="Confirmed">Confirmed</option>
                       <option value="Pending">Pending</option>
                       <option value="Cancelled">Cancelled</option>
                       <option value="Completed">Completed</option>
                    </select>
                 </div>
              </div>
          )}
      </div>

      <div className="flex-1 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex flex-col min-h-0">
            
            {/* --- LIST VIEW --- */}
            {viewMode === 'list' && (
               <div className="flex-1 overflow-y-auto p-6">
                  {Object.keys(groupedAppointments).length === 0 ? (
                     <div className="h-full flex flex-col items-center justify-center text-slate-400">
                        <CalendarIcon size={48} className="mb-4 opacity-20" />
                        <p>No appointments found for the selected filters.</p>
                     </div>
                  ) : (
                     Object.entries(groupedAppointments).map(([dateStr, group]: [string, Appointment[]]) => (
                        <div key={dateStr} className="mb-8 last:mb-0">
                           <div className="sticky top-0 bg-white/95 backdrop-blur-sm py-2 z-10 border-b border-slate-100 mb-4 flex items-center gap-2">
                              <CalendarIcon size={18} className="text-brand-600" />
                              <h3 className="font-bold text-slate-800">
                                 {new Date(dateStr).toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' })}
                              </h3>
                              <span className="bg-slate-100 text-slate-500 text-xs px-2 py-0.5 rounded-full">{group.length}</span>
                           </div>
                           <div className="space-y-3">
                              {group.map(apt => (
                                 <div key={apt.id} className="group flex flex-col md:flex-row items-start md:items-center gap-4 p-4 border border-slate-200 rounded-xl hover:border-brand-300 hover:shadow-sm transition-all bg-white">
                                    <div className="w-20 flex flex-col items-center justify-center p-2 bg-slate-50 rounded-lg border border-slate-100 shrink-0">
                                       <span className="text-sm font-bold text-slate-800">{apt.startTime}</span>
                                       <span className="text-xs text-slate-500">{apt.endTime}</span>
                                    </div>
                                    
                                    <div className="flex-1 min-w-0">
                                       <div className="flex justify-between items-start">
                                          <div>
                                             <h4 className="font-bold text-slate-800 truncate">{apt.serviceName}</h4>
                                             <div className="flex items-center gap-2 text-sm text-slate-500 mt-1">
                                                <span className="flex items-center gap-1"><User size={14} /> {apt.customerName}</span>
                                                <span className="hidden md:inline mx-1">•</span>
                                                <span className="flex items-center gap-1"><MapPin size={14} /> {apt.location}</span>
                                             </div>
                                          </div>
                                          <span className={`px-2.5 py-1 rounded-full text-xs font-bold border ${getStatusColor(apt.status)}`}>
                                             {apt.status}
                                          </span>
                                       </div>
                                       <div className="flex items-center gap-4 mt-3 text-xs text-slate-500">
                                          <span className="flex items-center gap-1 bg-slate-50 px-2 py-1 rounded"><Briefcase size={12}/> {apt.employeeName}</span>
                                          <span className="flex items-center gap-1 bg-slate-50 px-2 py-1 rounded"><Tag size={12}/> {apt.category}</span>
                                          <span className="flex items-center gap-1 font-medium text-slate-700"><DollarSign size={12}/> {apt.price}</span>
                                       </div>
                                    </div>

                                    <div className="flex gap-2 w-full md:w-auto justify-end mt-2 md:mt-0">
                                       <button 
                                          onClick={() => { setEditingAppointment(apt); setIsModalOpen(true); }}
                                          className="p-2 text-slate-400 hover:text-brand-600 hover:bg-slate-50 rounded-lg transition-colors"
                                       >
                                          <MoreVertical size={18} />
                                       </button>
                                    </div>
                                 </div>
                              ))}
                           </div>
                        </div>
                     ))
                  )}
               </div>
            )}

            {/* --- CALENDAR VIEW --- */}
            {viewMode === 'calendar' && (
               <div className="flex-1 overflow-y-auto p-2">
                  {calendarMode === 'month' ? (
                     <div className="grid grid-cols-7 gap-px bg-slate-200 rounded-lg overflow-hidden border border-slate-200">
                        {/* Week Headers */}
                        {['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].map(day => (
                           <div key={day} className="bg-slate-50 p-2 text-center text-xs font-bold text-slate-500 uppercase">{day}</div>
                        ))}
                        {/* Days Grid */}
                        {Array(35).fill(null).map((_, i) => {
                            // Simplified date logic for visualization
                           const cellDate = new Date(currentDate.getFullYear(), currentDate.getMonth(), i - new Date(currentDate.getFullYear(), currentDate.getMonth(), 1).getDay() + 1);
                           const isToday = cellDate.toDateString() === new Date().toDateString();
                           const dayStr = formatDate(cellDate);
                           const dayApts = filteredAppointments.filter(a => a.date === dayStr);
                           
                           return (
                              <div key={i} className={`bg-white min-h-[100px] p-2 transition-colors hover:bg-slate-50 ${isToday ? 'bg-indigo-50/30' : ''}`}>
                                 <div className="flex justify-between items-center mb-1">
                                    <span className={`text-sm font-medium ${isToday ? 'bg-brand-600 text-white w-6 h-6 rounded-full flex items-center justify-center' : 'text-slate-700'}`}>
                                       {cellDate.getDate()}
                                    </span>
                                 </div>
                                 <div className="space-y-1">
                                    {dayApts.slice(0, 3).map(apt => (
                                       <div 
                                          key={apt.id} 
                                          onClick={() => { setEditingAppointment(apt); setIsModalOpen(true); }}
                                          className={`text-[10px] px-1.5 py-1 rounded border truncate cursor-pointer hover:opacity-80 ${getStatusColor(apt.status)}`}
                                       >
                                          {apt.startTime} {apt.customerName}
                                       </div>
                                    ))}
                                    {dayApts.length > 3 && <div className="text-[10px] text-slate-400 text-center">+{dayApts.length - 3} more</div>}
                                 </div>
                              </div>
                           );
                        })}
                     </div>
                  ) : (
                     // WEEK / DAY VIEW (Time Grid)
                     <div className="flex h-full min-w-[800px]">
                        {/* Time Column */}
                        <div className="w-16 flex-shrink-0 border-r border-slate-200 bg-slate-50 pt-10">
                           {Array.from({length: 13}, (_, i) => i + 8).map(hour => (
                              <div key={hour} className="h-20 border-b border-slate-200 text-right pr-2 text-xs text-slate-400 relative">
                                 <span className="-top-2 relative">{hour}:00</span>
                              </div>
                           ))}
                        </div>
                        {/* Day Columns */}
                        <div className="flex-1 flex overflow-x-auto">
                           {(calendarMode === 'day' ? [currentDate] : Array.from({length: 7}, (_, i) => addDays(getStartOfWeek(currentDate), i))).map((dayDate, i) => {
                              const dateStr = formatDate(dayDate);
                              const dayApts = filteredAppointments.filter(a => a.date === dateStr);
                              const isToday = dayDate.toDateString() === new Date().toDateString();

                              return (
                                 <div key={i} className="flex-1 min-w-[140px] border-r border-slate-200 relative">
                                    <div className={`h-10 text-center border-b border-slate-200 flex flex-col justify-center ${isToday ? 'bg-indigo-50' : 'bg-white'}`}>
                                       <span className={`text-xs font-bold ${isToday ? 'text-brand-600' : 'text-slate-500'}`}>{dayDate.toLocaleDateString('en-US', { weekday: 'short' })}</span>
                                       <span className={`text-sm font-bold ${isToday ? 'text-brand-700' : 'text-slate-800'}`}>{dayDate.getDate()}</span>
                                    </div>
                                    <div className="relative h-[1040px] bg-white">
                                       {/* Background Grid Lines */}
                                       {Array.from({length: 13}, (_, h) => (
                                          <div key={h} className="h-20 border-b border-slate-100 box-border"></div>
                                       ))}
                                       
                                       {/* Appointments */}
                                       {dayApts.map(apt => {
                                          const startHour = parseInt(apt.startTime.split(':')[0]);
                                          const startMin = parseInt(apt.startTime.split(':')[1]);
                                          const top = ((startHour - 8) * 80) + ((startMin / 60) * 80);
                                          const height = (apt.duration / 60) * 80;
                                          
                                          return (
                                             <div 
                                                key={apt.id}
                                                onClick={(e) => { e.stopPropagation(); setEditingAppointment(apt); setIsModalOpen(true); }}
                                                className={`absolute left-1 right-1 rounded-md p-2 border-l-4 cursor-pointer hover:brightness-95 hover:z-10 shadow-sm transition-all ${getStatusColor(apt.status)}`}
                                                style={{ top: `${top}px`, height: `${height}px` }}
                                             >
                                                <div className="font-bold text-xs truncate">{apt.serviceName}</div>
                                                <div className="text-[10px] truncate opacity-90">{apt.startTime} - {apt.customerName}</div>
                                                {height > 40 && (
                                                   <div className="text-[10px] mt-1 flex items-center gap-1 truncate opacity-75">
                                                      <MapPin size={10} /> {apt.location}
                                                   </div>
                                                )}
                                             </div>
                                          )
                                       })}
                                    </div>
                                 </div>
                              );
                           })}
                        </div>
                     </div>
                  )}
               </div>
            )}
      </div>

      {/* Booking Modal */}
      {isModalOpen && (
         <AppointmentModal 
            appointment={editingAppointment}
            onClose={() => setIsModalOpen(false)}
            onSave={handleSave}
            onDelete={editingAppointment ? handleDelete : undefined}
         />
      )}
    </div>
  );
};

// --- MODAL COMPONENT ---

interface ModalProps {
  appointment: Appointment | null;
  onClose: () => void;
  onSave: (apt: Appointment) => void;
  onDelete?: (id: string) => void;
}

const AppointmentModal: React.FC<ModalProps> = ({ appointment, onClose, onSave, onDelete }) => {
  const { services, pricingRules, systemCurrency, customers } = useApp();
  
  // Form State
  const [formData, setFormData] = useState<Appointment>(appointment || {
    id: '', title: '', customerId: '', customerName: '', employeeId: 'emp1', employeeName: 'Sarah Jenkins',
    serviceId: '', serviceName: '', category: 'Wellness', date: formatDate(new Date()), 
    startTime: '09:00', endTime: '10:00', duration: 60, type: 'Service', 
    location: 'Room A', status: 'Confirmed', price: 0, notes: ''
  });

  // Re-calculate price when relevant fields change
  useEffect(() => {
      if (appointment) return; // Don't auto-calc on edit unless explicit change (simplified)
      
      const selectedService = services.find(s => s.id === formData.serviceId);
      if (selectedService) {
          calculatePrice(selectedService, formData.date, formData.startTime);
      }
  }, [formData.serviceId, formData.date, formData.startTime]);

  const calculatePrice = (service: ServiceItem, dateStr: string, timeStr: string) => {
      let finalPrice = service.price;
      // eslint-disable-next-line @typescript-eslint/no-unused-vars
      let originalPrice = service.price;
      let label = '';

      if (service.dynamicPricing !== 'Off' && service.pricingRuleId) {
          const rule = pricingRules.find(r => r.id === service.pricingRuleId);
          if (rule && rule.active) {
              const apptDate = new Date(`${dateStr}T${timeStr}`);
              const now = new Date(); // Mock "now", normally would be actual Date.now()
              
              // Mock logic for demo
              if (rule.type === 'EarlyBird') {
                  // Calculate days difference
                  const diffTime = Math.abs(apptDate.getTime() - now.getTime());
                  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
                  
                  // Check tiers
                  if (rule.tiers) {
                      for (const tier of rule.tiers) {
                          if (diffDays >= tier.conditionValue) {
                              const adjustment = tier.adjustmentType === 'Percentage' 
                                  ? (finalPrice * (tier.adjustmentValue / 100)) 
                                  : tier.adjustmentValue;
                              finalPrice += adjustment; // adjustments can be negative
                              label = 'Early Bird';
                              break; // Apply first matching tier
                          }
                      }
                  }
              } else if (rule.type === 'LastMinute') {
                   // Calculate hours difference
                   const diffTime = apptDate.getTime() - now.getTime();
                   const diffHours = diffTime / (1000 * 60 * 60);

                   if (rule.tiers) {
                       for (const tier of rule.tiers) {
                           if (diffHours > 0 && diffHours <= tier.conditionValue) {
                               const adjustment = tier.adjustmentType === 'Percentage' 
                                  ? (finalPrice * (tier.adjustmentValue / 100)) 
                                  : tier.adjustmentValue;
                               finalPrice += adjustment;
                               label = 'Last Minute';
                               break;
                           }
                       }
                   }
              } else if (rule.type === 'Season') {
                  // Check seasonal rules
                  if (rule.seasonalRules) {
                      for (const season of rule.seasonalRules) {
                          if (season.type === 'Range' && season.startDate && season.endDate) {
                              if (dateStr >= season.startDate && dateStr <= season.endDate) {
                                  // Check day specific
                                  const dayName = apptDate.toLocaleDateString('en-US', {weekday: 'short'}); // Mon, Tue...
                                  const dayConfig = season.dayConfigs?.[dayName];
                                  
                                  if (dayConfig && dayConfig.active && dayConfig.slots) {
                                      // Check time slots
                                      for (const slot of dayConfig.slots) {
                                          if (timeStr >= slot.startTime && timeStr <= slot.endTime) {
                                              const adjustment = slot.adjustmentType === 'Percentage' 
                                                  ? (finalPrice * (slot.adjustmentValue / 100)) 
                                                  : slot.adjustmentValue;
                                              finalPrice += adjustment;
                                              label = 'Seasonal/Peak';
                                              break;
                                          }
                                      }
                                  }
                              }
                          }
                      }
                  }
              }

              // Apply Rounding
              if (rule.roundingValue) {
                  if (rule.roundingMethod === 'Up') {
                      finalPrice = Math.ceil(finalPrice / rule.roundingValue) * rule.roundingValue;
                  } else if (rule.roundingMethod === 'Down') {
                      finalPrice = Math.floor(finalPrice / rule.roundingValue) * rule.roundingValue;
                  } else {
                      finalPrice = Math.round(finalPrice / rule.roundingValue) * rule.roundingValue;
                  }
              }
              
              if (rule.priceEnding && rule.priceEnding !== 'None') {
                  const decimal = parseFloat(rule.priceEnding);
                  finalPrice = Math.floor(finalPrice) + decimal;
              }
          }
      }

      // Ensure no negative price
      finalPrice = Math.max(0, finalPrice);
      
      setFormData(prev => ({ ...prev, price: finalPrice, dynamicPricingLabel: label }));
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
      <div className="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden animate-fadeIn">
        <div className="p-6 border-b border-slate-200 flex justify-between items-center">
          <h3 className="text-lg font-bold text-slate-800">{appointment ? 'Edit Appointment' : 'New Booking'}</h3>
          <button onClick={onClose} className="text-slate-400 hover:text-slate-600"><X size={20}/></button>
        </div>
        <div className="p-6 space-y-4">
            {/* Service Selection */}
            <div>
                <label className="block text-sm font-medium text-slate-700 mb-1">Service</label>
                <select 
                    className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 outline-none"
                    value={formData.serviceId}
                    onChange={e => {
                        const svc = services.find(s => s.id === e.target.value);
                        if(svc) {
                            setFormData({
                                ...formData, 
                                serviceId: svc.id, 
                                serviceName: svc.title,
                                duration: svc.duration || 60,
                                category: svc.category,
                                price: svc.price
                            });
                        }
                    }}
                >
                    <option value="">Select a service...</option>
                    {services.map(s => (
                        <option key={s.id} value={s.id}>{s.title} ({s.duration}m)</option>
                    ))}
                </select>
            </div>

            {/* Customer Selection */}
            <div>
                <label className="block text-sm font-medium text-slate-700 mb-1">Customer</label>
                <select 
                    className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 outline-none"
                    value={formData.customerId}
                    onChange={e => {
                        const cust = customers.find(c => c.id === e.target.value);
                        if(cust) {
                            setFormData({
                                ...formData,
                                customerId: cust.id,
                                customerName: `${cust.firstName} ${cust.lastName}`
                            });
                        }
                    }}
                >
                    <option value="">Select Customer...</option>
                    {customers.map(c => (
                        <option key={c.id} value={c.id}>{c.firstName} {c.lastName}</option>
                    ))}
                </select>
                {/* Fallback for free text if needed, or just rely on dropdown */}
                {/* <div className="mt-1 text-xs text-brand-600 hover:underline cursor-pointer">Or create new customer</div> */}
            </div>

            {/* Date & Time */}
            <div className="grid grid-cols-2 gap-4">
                <div>
                    <label className="block text-sm font-medium text-slate-700 mb-1">Date</label>
                    <input 
                        type="date" 
                        className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 outline-none"
                        value={formData.date}
                        onChange={e => setFormData({...formData, date: e.target.value})}
                    />
                </div>
                <div>
                    <label className="block text-sm font-medium text-slate-700 mb-1">Time</label>
                    <input 
                        type="time" 
                        className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 outline-none"
                        value={formData.startTime}
                        onChange={e => setFormData({...formData, startTime: e.target.value})}
                    />
                </div>
            </div>

            {/* Price & Status */}
            <div className="grid grid-cols-2 gap-4">
                <div>
                    <label className="block text-sm font-medium text-slate-700 mb-1">Price ({systemCurrency})</label>
                    <div className="relative">
                        <input 
                            type="number" 
                            className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 outline-none"
                            value={formData.price}
                            onChange={e => setFormData({...formData, price: parseFloat(e.target.value)})}
                        />
                        {formData.dynamicPricingLabel && (
                            <span className="absolute right-2 top-2 text-[10px] bg-indigo-100 text-indigo-700 px-1.5 rounded">
                                {formData.dynamicPricingLabel}
                            </span>
                        )}
                    </div>
                </div>
                <div>
                    <label className="block text-sm font-medium text-slate-700 mb-1">Status</label>
                    <select 
                        className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 outline-none"
                        value={formData.status}
                        onChange={e => setFormData({...formData, status: e.target.value as any})}
                    >
                        <option value="Confirmed">Confirmed</option>
                        <option value="Pending">Pending</option>
                        <option value="Cancelled">Cancelled</option>
                        <option value="Completed">Completed</option>
                        <option value="No-Show">No-Show</option>
                    </select>
                </div>
            </div>
            
            {/* Notes */}
            <div>
                <label className="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                <textarea 
                    className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 outline-none"
                    rows={3}
                    value={formData.notes || ''}
                    onChange={e => setFormData({...formData, notes: e.target.value})}
                />
            </div>
        </div>
        <div className="p-4 border-t border-slate-200 bg-slate-50 flex justify-between items-center">
            {onDelete ? (
                <button onClick={() => onDelete(formData.id)} className="text-rose-600 hover:text-rose-700 text-sm font-medium flex items-center gap-1">
                    <Trash2 size={16} /> Cancel Booking
                </button>
            ) : <div></div>}
            <div className="flex gap-2">
                <button onClick={onClose} className="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-white text-sm font-medium">Close</button>
                <button onClick={() => onSave(formData)} className="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 text-sm font-medium flex items-center gap-2">
                    <Save size={16} /> Save
                </button>
            </div>
        </div>
      </div>
    </div>
  );
};

export default AppointmentsModule;
