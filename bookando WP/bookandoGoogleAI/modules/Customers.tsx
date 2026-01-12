
import React, { useState } from 'react';
import { Customer, CustomerStatus, CustomField, Booking, Appointment } from '../types';
import { 
  Search, Plus, Filter, MoreHorizontal, Mail, Phone, MapPin, 
  Trash2, Edit2, Download, X, Save, Archive, Calendar, CheckCircle, Clock, XCircle, AlertCircle
} from 'lucide-react';
import { useApp } from '../context/AppContext';

const CustomersModule: React.FC = () => {
  const { customers, addCustomer, updateCustomer, deleteCustomer, appointments } = useApp();
  const [searchQuery, setSearchQuery] = useState('');
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingCustomer, setEditingCustomer] = useState<Customer | null>(null);
  const [viewingBookingsFor, setViewingBookingsFor] = useState<Customer | null>(null);

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
    const headers = ['ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Status', 'Address', 'Notes'];
    const rows = customers.map(c => [
      c.id, c.firstName, c.lastName, c.email, c.phone, c.status, c.address || '', c.notes || ''
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

  const filteredCustomers = customers.filter(c => {
    const matchesSearch = 
      c.firstName.toLowerCase().includes(searchQuery.toLowerCase()) ||
      c.lastName.toLowerCase().includes(searchQuery.toLowerCase()) ||
      c.email.toLowerCase().includes(searchQuery.toLowerCase());
    // Optionally hide deleted customers from main view, but for this demo we show them with a tag
    return matchesSearch;
  });

  // Helper to get booking count
  const getBookingCount = (customerId: string) => {
      return appointments.filter(a => a.customerId === customerId).length;
  };

  return (
    <div className="space-y-6 relative">
      <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <h2 className="text-2xl font-bold text-slate-800">Customers</h2>
          <p className="text-slate-500">Manage your client relationships and data.</p>
        </div>
        <button 
          onClick={() => { setEditingCustomer(null); setIsModalOpen(true); }}
          className="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors shadow-sm"
        >
          <Plus size={18} />
          <span>Add Customer</span>
        </button>
      </div>

      {/* Toolbar */}
      <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col md:flex-row gap-4 justify-between items-center">
        <div className="relative w-full md:w-96">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
          <input 
            type="text" 
            placeholder="Search by name, email or phone..." 
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent"
          />
        </div>
        <div className="flex gap-2 w-full md:w-auto">
          <button className="flex-1 md:flex-none px-4 py-2 border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50 text-sm font-medium flex items-center justify-center gap-2">
            <Filter size={16} />
            Filter
          </button>
          <button 
            onClick={handleExportCSV}
            className="flex-1 md:flex-none px-4 py-2 border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50 text-sm font-medium flex items-center gap-2"
          >
            <Download size={16} />
            Export CSV
          </button>
        </div>
      </div>

      {/* Customer List */}
      <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="bg-slate-50 border-b border-slate-200">
                <th className="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Customer</th>
                <th className="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Contact</th>
                <th className="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                <th className="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Bookings</th>
                <th className="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-200">
              {filteredCustomers.map((customer) => (
                <tr key={customer.id} className={`hover:bg-slate-50 transition-colors group ${customer.status === CustomerStatus.DELETED ? 'opacity-60 bg-slate-50' : ''}`}>
                  <td className="p-4">
                    <div className="flex items-center gap-3">
                      <div className="w-10 h-10 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center font-semibold text-sm uppercase">
                        {customer.firstName[0]}{customer.lastName[0]}
                      </div>
                      <div>
                        <div className="font-medium text-slate-900">{customer.firstName} {customer.lastName}</div>
                        <div className="text-xs text-slate-500">ID: #{customer.id}</div>
                      </div>
                    </div>
                  </td>
                  <td className="p-4">
                    <div className="flex flex-col gap-1 text-sm text-slate-600">
                      <div className="flex items-center gap-2">
                        <Mail size={14} className="text-slate-400" />
                        {customer.email}
                      </div>
                      <div className="flex items-center gap-2">
                        <Phone size={14} className="text-slate-400" />
                        {customer.phone}
                      </div>
                    </div>
                  </td>
                  <td className="p-4">
                    <span className={`
                      inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                      ${customer.status === CustomerStatus.ACTIVE ? 'bg-emerald-100 text-emerald-800' : ''}
                      ${customer.status === CustomerStatus.BLOCKED ? 'bg-rose-100 text-rose-800' : ''}
                      ${customer.status === CustomerStatus.DELETED ? 'bg-slate-200 text-slate-600' : ''}
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
            </tbody>
          </table>
        </div>
      </div>

      {/* Edit/Create Modal */}
      {isModalOpen && (
        <CustomerModal 
          customer={editingCustomer} 
          onClose={() => setIsModalOpen(false)} 
          onSave={handleSave} 
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

// --- Bookings History Modal ---

const BookingsHistoryModal: React.FC<{
    customer: Customer;
    onClose: () => void;
}> = ({ customer, onClose }) => {
    const { appointments } = useApp();
    // Filter global appointments by customer ID
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

// --- Customer Modal Component ---

const CustomerModal: React.FC<{ 
  customer: Customer | null; 
  onClose: () => void; 
  onSave: (c: Customer) => void; 
}> = ({ customer, onClose, onSave }) => {
  const [formData, setFormData] = useState<Customer>(customer || {
    id: '',
    firstName: '',
    lastName: '',
    email: '',
    phone: '',
    status: CustomerStatus.ACTIVE,
    bookings: [],
    address: '',
    birthday: '',
    gender: 'Prefer not to say',
    notes: '',
    customFields: []
  });

  const [newCustomField, setNewCustomField] = useState({ key: '', value: '' });

  const handleChange = (field: keyof Customer, value: any) => {
    setFormData(prev => ({ ...prev, [field]: value }));
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

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
      <div className="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto flex flex-col">
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
                <input 
                  type="tel" 
                  className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none"
                  value={formData.phone}
                  onChange={e => handleChange('phone', e.target.value)}
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-1">Birthday</label>
                <input 
                  type="date" 
                  className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none"
                  value={formData.birthday}
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

           {/* Address */}
           <div>
              <label className="block text-sm font-medium text-slate-700 mb-1">Address</label>
              <input 
                type="text" 
                className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none"
                value={formData.address}
                onChange={e => handleChange('address', e.target.value)}
                placeholder="Street, City, Zip"
              />
           </div>

           {/* Status & Notes */}
           <div className="grid grid-cols-1 gap-4">
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

           {/* Custom Fields */}
           <div className="border-t border-slate-100 pt-4">
              <label className="block text-sm font-medium text-slate-700 mb-2">Custom Fields</label>
              <div className="space-y-2 mb-3">
                 {formData.customFields?.map((field, idx) => (
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
