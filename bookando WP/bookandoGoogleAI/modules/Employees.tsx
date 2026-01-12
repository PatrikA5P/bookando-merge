import React, { useState, useRef } from 'react';
import { Employee, EmployeeStatus } from '../types';
import { 
  Search, Plus, Filter, Edit2, Trash2, MapPin, Mail, Phone, 
  Briefcase, Shield, Download, X, Check, Eye, EyeOff, User, Camera, Upload
} from 'lucide-react';
import { useApp } from '../context/AppContext';

// Mock Data
const initialEmployees: Employee[] = [
  { 
    id: 'EMP-001', firstName: 'Sarah', lastName: 'Jenkins', email: 'sarah.j@company.com', phone: '+1 555-9988',
    gender: 'Female', birthday: '1990-05-15', position: 'Senior Therapist', department: 'Wellness', role: 'Manager',
    hireDate: '2020-01-10', status: EmployeeStatus.ACTIVE, address: '123 Pine Ln', zip: '10001', city: 'New York', country: 'USA',
    hubPassword: 'password123', badgeId: 'BADGE-99', description: 'Expert in deep tissue.', assignedServices: ['Deep Tissue Massage', 'Physiotherapy Init'],
    avatar: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&q=80&w=150'
  },
  { 
    id: 'EMP-002', firstName: 'Mike', lastName: 'Ross', email: 'mike.r@company.com', phone: '+1 555-7766',
    gender: 'Male', birthday: '1985-11-22', position: 'Instructor', department: 'Fitness', role: 'Employee',
    hireDate: '2021-03-15', status: EmployeeStatus.VACATION, address: '456 Elm St', zip: '10002', city: 'New York', country: 'USA',
    hubPassword: 'securepass', badgeId: 'BADGE-45', assignedServices: ['Yoga for Beginners']
  }
];

const availableServices = [
  'Deep Tissue Massage', 'Yoga for Beginners', 'Nutrition Masterclass', 
  'Physiotherapy Init', 'Weekend Meditation Retreat', 'Personal Training'
];

const EmployeesModule: React.FC = () => {
  const { t } = useApp();
  const [employees, setEmployees] = useState<Employee[]>(initialEmployees);
  const [searchQuery, setSearchQuery] = useState('');
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingEmployee, setEditingEmployee] = useState<Employee | null>(null);

  const handleSave = (employee: Employee) => {
    if (editingEmployee) {
      setEmployees(employees.map(e => e.id === employee.id ? employee : e));
    } else {
      const newEmployee = { 
        ...employee, 
        id: `EMP-${Math.floor(Math.random() * 1000).toString().padStart(3, '0')}` 
      };
      setEmployees([...employees, newEmployee]);
    }
    setIsModalOpen(false);
    setEditingEmployee(null);
  };

  const handleDelete = (id: string) => {
    if (window.confirm('Are you sure you want to remove this employee?')) {
       setEmployees(employees.filter(e => e.id !== id));
    }
  };

  const filteredEmployees = employees.filter(e => 
    e.lastName.toLowerCase().includes(searchQuery.toLowerCase()) ||
    e.firstName.toLowerCase().includes(searchQuery.toLowerCase()) ||
    e.position.toLowerCase().includes(searchQuery.toLowerCase())
  );

  return (
    <div className="space-y-6">
      <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <h2 className="text-2xl font-bold text-slate-800">{t('employees_title')}</h2>
          <p className="text-slate-500">{t('employees_subtitle')}</p>
        </div>
        <button 
          onClick={() => { setEditingEmployee(null); setIsModalOpen(true); }}
          className="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors shadow-sm"
        >
          <Plus size={18} />
          <span>{t('add_employee')}</span>
        </button>
      </div>

      {/* Toolbar */}
      <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col md:flex-row gap-4 justify-between items-center">
        <div className="relative w-full md:w-96">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
          <input 
            type="text" 
            placeholder={t('search_employees')} 
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent"
          />
        </div>
        <div className="flex gap-2 w-full md:w-auto">
           <button className="flex items-center gap-2 px-4 py-2 border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50 text-sm font-medium">
              <Download size={16} /> {t('export')}
           </button>
        </div>
      </div>

      {/* Grid View - Redesigned Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        {filteredEmployees.map(employee => (
          <div key={employee.id} className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden group hover:shadow-md transition-all duration-300 flex flex-col">
            
            {/* Card Header / Banner */}
            <div className="h-24 bg-gradient-to-r from-slate-800 to-slate-900 relative">
               <div className="absolute top-4 right-4">
                  <span className={`
                     px-2.5 py-1 text-[10px] font-bold uppercase rounded-full shadow-sm border border-white/10
                     ${employee.status === EmployeeStatus.ACTIVE ? 'bg-emerald-500 text-white' : 
                       employee.status === EmployeeStatus.VACATION ? 'bg-amber-500 text-white' : 'bg-slate-500 text-white'}
                  `}>
                     {employee.status}
                  </span>
               </div>
            </div>

            {/* Card Content */}
            <div className="px-6 flex-1 flex flex-col relative">
               {/* Avatar - Overlapping Header */}
               <div className="-mt-12 mb-3 flex justify-between items-end">
                  <div className="w-24 h-24 rounded-xl border-4 border-white shadow-md bg-white overflow-hidden flex items-center justify-center relative group/avatar">
                     {employee.avatar ? (
                        <img src={employee.avatar} alt="Avatar" className="w-full h-full object-cover" />
                     ) : (
                        <div className="w-full h-full bg-brand-100 text-brand-600 flex items-center justify-center text-3xl font-bold">
                           {employee.firstName[0]}{employee.lastName[0]}
                        </div>
                     )}
                  </div>
                  <div className="mb-1">
                     <button 
                        onClick={() => { setEditingEmployee(employee); setIsModalOpen(true); }}
                        className="p-2 text-slate-400 hover:text-brand-600 hover:bg-brand-50 rounded-lg transition-colors"
                     >
                        <Edit2 size={18} />
                     </button>
                  </div>
               </div>

               {/* Info */}
               <div className="mb-6">
                  <h3 className="font-bold text-lg text-slate-900 truncate">{employee.firstName} {employee.lastName}</h3>
                  <p className="text-brand-600 font-medium text-sm">{employee.position}</p>
                  
                  <div className="mt-4 space-y-2.5">
                     <div className="flex items-center gap-3 text-sm text-slate-600">
                        <Mail size={16} className="text-slate-400 shrink-0" /> 
                        <span className="truncate">{employee.email}</span>
                     </div>
                     <div className="flex items-center gap-3 text-sm text-slate-600">
                        <Briefcase size={16} className="text-slate-400 shrink-0" /> 
                        <span className="truncate">{employee.department}</span>
                     </div>
                     {employee.phone && (
                        <div className="flex items-center gap-3 text-sm text-slate-600">
                           <Phone size={16} className="text-slate-400 shrink-0" /> 
                           <span className="truncate">{employee.phone}</span>
                        </div>
                     )}
                  </div>
               </div>
            </div>

            {/* Footer */}
            <div className="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-between items-center mt-auto">
               <div className="flex flex-col">
                  <span className="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">{t('joined')}</span>
                  <span className="text-xs font-medium text-slate-700">{employee.hireDate}</span>
               </div>
               {employee.role && (
                  <div className="flex items-center gap-1.5 px-2 py-1 rounded bg-indigo-50 border border-indigo-100 text-indigo-700 text-xs font-bold">
                     <Shield size={12} /> {employee.role}
                  </div>
               )}
            </div>
          </div>
        ))}
      </div>

      {isModalOpen && (
        <EmployeeModal 
          employee={editingEmployee}
          onClose={() => setIsModalOpen(false)}
          onSave={handleSave}
        />
      )}
    </div>
  );
};

// --- Employee Modal ---

const EmployeeModal: React.FC<{
  employee: Employee | null;
  onClose: () => void;
  onSave: (e: Employee) => void;
}> = ({ employee, onClose, onSave }) => {
  const { t, roles } = useApp();
  const [activeTab, setActiveTab] = useState<'profile' | 'address' | 'hr' | 'services'>('profile');
  const [showPassword, setShowPassword] = useState(false);
  const fileInputRef = useRef<HTMLInputElement>(null);
  
  const [formData, setFormData] = useState<Employee>(employee || {
    id: '', firstName: '', lastName: '', email: '', phone: '', gender: 'Male', birthday: '',
    position: '', department: '', hireDate: '', status: EmployeeStatus.ACTIVE, role: 'Employee',
    address: '', zip: '', city: '', country: '',
    assignedServices: [], description: '', hubPassword: '', badgeId: '', avatar: ''
  });

  // Standard input style consistent with Settings -> General
  const inputClass = "w-full border border-slate-300 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm transition-shadow";
  const labelClass = "block text-sm font-medium text-slate-700 mb-1";

  const handleChange = (field: keyof Employee, value: any) => {
    setFormData(prev => ({ ...prev, [field]: value }));
  };

  const handleImageUpload = (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (file) {
      const reader = new FileReader();
      reader.onloadend = () => {
        handleChange('avatar', reader.result as string);
      };
      reader.readAsDataURL(file);
    }
  };

  const toggleService = (service: string) => {
     const current = formData.assignedServices;
     const updated = current.includes(service) 
        ? current.filter(s => s !== service)
        : [...current, service];
     handleChange('assignedServices', updated);
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
       <div className="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden animate-fadeIn">
          {/* Modal Header */}
          <div className="p-6 border-b border-slate-200 flex justify-between items-center bg-white">
             <div>
                <h3 className="text-xl font-bold text-slate-800">
                   {employee ? t('edit') + ' Employee' : t('add_employee')}
                </h3>
                <p className="text-sm text-slate-500">
                   {employee ? `ID: ${employee.id}` : 'Create a new staff profile'}
                </p>
             </div>
             <button onClick={onClose} className="text-slate-400 hover:text-slate-600 p-1 rounded-md hover:bg-slate-100">
                <X size={24} />
             </button>
          </div>

          {/* Modal Tabs */}
          <div className="flex border-b border-slate-200 bg-slate-50 px-6">
             {[
               { id: 'profile', icon: User, label: t('personal_info') },
               { id: 'address', icon: MapPin, label: t('address') },
               { id: 'hr', icon: Shield, label: t('hr_access') },
               { id: 'services', icon: Check, label: 'Services' }
             ].map(tab => (
                <button
                   key={tab.id}
                   onClick={() => setActiveTab(tab.id as any)}
                   className={`
                      py-3 px-4 text-sm font-medium flex items-center gap-2 border-b-2 transition-colors
                      ${activeTab === tab.id ? 'border-brand-600 text-brand-600 bg-white' : 'border-transparent text-slate-500 hover:text-slate-700'}
                   `}
                >
                   <tab.icon size={16} /> {tab.label}
                </button>
             ))}
          </div>

          {/* Scrollable Content */}
          <div className="flex-1 overflow-y-auto p-6 md:p-8 bg-slate-50/30">
             
             {activeTab === 'profile' && (
                <div className="space-y-8 max-w-3xl mx-auto">
                   {/* Avatar Section */}
                   <div className="flex items-center gap-6 p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
                      <div className="w-20 h-20 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center overflow-hidden shrink-0">
                         {formData.avatar ? (
                            <img src={formData.avatar} alt="Profile" className="w-full h-full object-cover" />
                         ) : (
                            <User size={32} className="text-slate-300" />
                         )}
                      </div>
                      <div>
                         <h4 className="font-medium text-slate-800 mb-1">Profile Picture</h4>
                         <p className="text-xs text-slate-500 mb-3">Recommended dimensions: 300x300px.</p>
                         <div className="flex gap-3">
                            <button 
                              onClick={() => fileInputRef.current?.click()}
                              className="px-3 py-1.5 border border-slate-300 rounded-md text-sm font-medium text-slate-700 hover:bg-slate-50 flex items-center gap-2 bg-white"
                            >
                               <Upload size={14} /> Upload
                            </button>
                            {formData.avatar && (
                               <button 
                                 onClick={() => handleChange('avatar', '')}
                                 className="px-3 py-1.5 text-rose-600 hover:text-rose-700 text-sm font-medium hover:bg-rose-50 rounded-md"
                               >
                                 Remove
                               </button>
                            )}
                            <input 
                              type="file" 
                              ref={fileInputRef} 
                              onChange={handleImageUpload} 
                              className="hidden" 
                              accept="image/*"
                            />
                         </div>
                      </div>
                   </div>

                   <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                      <div>
                         <label className={labelClass}>{t('first_name')}</label>
                         <input type="text" className={inputClass} value={formData.firstName} onChange={e => handleChange('firstName', e.target.value)} />
                      </div>
                      <div>
                         <label className={labelClass}>{t('last_name')}</label>
                         <input type="text" className={inputClass} value={formData.lastName} onChange={e => handleChange('lastName', e.target.value)} />
                      </div>
                      <div>
                         <label className={labelClass}>{t('email')}</label>
                         <input type="email" className={inputClass} value={formData.email} onChange={e => handleChange('email', e.target.value)} />
                      </div>
                      <div>
                         <label className={labelClass}>{t('phone')}</label>
                         <input type="tel" className={inputClass} value={formData.phone} onChange={e => handleChange('phone', e.target.value)} />
                      </div>
                      <div>
                         <label className={labelClass}>{t('gender')}</label>
                         <select className={inputClass} value={formData.gender} onChange={e => handleChange('gender', e.target.value)}>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                         </select>
                      </div>
                      <div>
                         <label className={labelClass}>{t('birthday')}</label>
                         <input type="date" className={inputClass} value={formData.birthday} onChange={e => handleChange('birthday', e.target.value)} />
                      </div>
                      <div className="col-span-full">
                         <label className={labelClass}>Description / Bio</label>
                         <textarea className={inputClass} rows={3} value={formData.description} onChange={e => handleChange('description', e.target.value)} />
                      </div>
                   </div>
                </div>
             )}

             {activeTab === 'address' && (
                <div className="max-w-2xl mx-auto space-y-6">
                   <h4 className="font-bold text-slate-800 text-lg mb-4 border-b pb-2">Location Details</h4>
                   <div>
                      <label className={labelClass}>{t('address')}</label>
                      <input type="text" className={inputClass} value={formData.address} onChange={e => handleChange('address', e.target.value)} placeholder="Street and Number" />
                   </div>
                   <div className="grid grid-cols-2 gap-6">
                      <div>
                         <label className={labelClass}>Zip Code</label>
                         <input type="text" className={inputClass} value={formData.zip} onChange={e => handleChange('zip', e.target.value)} />
                      </div>
                      <div>
                         <label className={labelClass}>City</label>
                         <input type="text" className={inputClass} value={formData.city} onChange={e => handleChange('city', e.target.value)} />
                      </div>
                   </div>
                   <div>
                      <label className={labelClass}>Country</label>
                      <input type="text" className={inputClass} value={formData.country} onChange={e => handleChange('country', e.target.value)} />
                   </div>
                </div>
             )}

             {activeTab === 'hr' && (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                   <div className="space-y-6">
                      <h4 className="font-bold text-slate-800 border-b pb-2">{t('employment')}</h4>
                      <div>
                         <label className={labelClass}>{t('position')}</label>
                         <input type="text" className={inputClass} value={formData.position} onChange={e => handleChange('position', e.target.value)} />
                      </div>
                      <div>
                         <label className={labelClass}>{t('department')}</label>
                         <input type="text" className={inputClass} value={formData.department} onChange={e => handleChange('department', e.target.value)} />
                      </div>
                      <div>
                         <label className={labelClass}>Badge ID / Clock-in ID</label>
                         <input type="text" className={inputClass} value={formData.badgeId} onChange={e => handleChange('badgeId', e.target.value)} />
                      </div>
                      <div className="grid grid-cols-2 gap-4">
                         <div>
                            <label className={labelClass}>{t('hire_date')}</label>
                            <input type="date" className={inputClass} value={formData.hireDate} onChange={e => handleChange('hireDate', e.target.value)} />
                         </div>
                         <div>
                            <label className={labelClass}>Exit Date (Optional)</label>
                            <input type="date" className={inputClass} value={formData.exitDate || ''} onChange={e => handleChange('exitDate', e.target.value)} />
                         </div>
                      </div>
                   </div>
                   <div className="space-y-6">
                      <h4 className="font-bold text-slate-800 border-b pb-2">{t('status')} & {t('hr_access')}</h4>
                      <div>
                         <label className={labelClass}>{t('status')}</label>
                         <select className={inputClass} value={formData.status} onChange={e => handleChange('status', e.target.value)}>
                            <option value={EmployeeStatus.ACTIVE}>Active</option>
                            <option value={EmployeeStatus.VACATION}>Vacation</option>
                            <option value={EmployeeStatus.SICK_LEAVE}>Sick Leave</option>
                            <option value={EmployeeStatus.PAUSE}>Pause</option>
                            <option value={EmployeeStatus.TERMINATED}>Terminated</option>
                         </select>
                      </div>
                      <div>
                         <label className={labelClass}>{t('role')}</label>
                         <select 
                            className={inputClass} 
                            value={formData.role || 'Employee'} 
                            onChange={e => handleChange('role', e.target.value)}
                         >
                            {roles.map(role => (
                               <option key={role.id} value={role.name}>{role.name}</option>
                            ))}
                         </select>
                      </div>
                      <div>
                         <label className={labelClass}>Hub Password</label>
                         <div className="relative">
                           <input 
                              type={showPassword ? "text" : "password"} 
                              className={`${inputClass} pr-10`}
                              value={formData.hubPassword} 
                              onChange={e => handleChange('hubPassword', e.target.value)} 
                              placeholder="Set password"
                           />
                           <button 
                              type="button"
                              onClick={() => setShowPassword(!showPassword)}
                              className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                           >
                              {showPassword ? <EyeOff size={16} /> : <Eye size={16} />}
                           </button>
                         </div>
                      </div>
                   </div>
                </div>
             )}

             {activeTab === 'services' && (
                <div className="max-w-3xl mx-auto">
                   <h4 className="font-bold text-slate-800 border-b pb-2 mb-6">Assigned Services & Courses</h4>
                   <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      {availableServices.map(service => (
                         <div 
                           key={service}
                           onClick={() => toggleService(service)}
                           className={`
                              flex items-center gap-3 p-4 rounded-lg border cursor-pointer transition-all shadow-sm
                              ${formData.assignedServices.includes(service) 
                                 ? 'bg-brand-50 border-brand-200 ring-1 ring-brand-200' 
                                 : 'bg-white border-slate-200 hover:border-slate-300 hover:bg-slate-50'}
                           `}
                         >
                            <div className={`
                               w-5 h-5 rounded border flex items-center justify-center shrink-0
                               ${formData.assignedServices.includes(service) ? 'bg-brand-600 border-brand-600 text-white' : 'bg-white border-slate-300'}
                            `}>
                               {formData.assignedServices.includes(service) && <Check size={12} />}
                            </div>
                            <span className={`text-sm font-medium ${formData.assignedServices.includes(service) ? 'text-brand-800' : 'text-slate-700'}`}>
                               {service}
                            </span>
                         </div>
                      ))}
                   </div>
                </div>
             )}

          </div>

          {/* Modal Footer */}
          <div className="p-6 border-t border-slate-200 bg-white flex justify-end gap-3">
             <button onClick={onClose} className="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 font-medium text-sm">
                {t('cancel')}
             </button>
             <button onClick={() => onSave(formData)} className="px-6 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-medium shadow-sm text-sm">
                {t('save_changes')}
             </button>
          </div>
       </div>
    </div>
  );
};

export default EmployeesModule;