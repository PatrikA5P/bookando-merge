
import React, { useState, useEffect } from 'react';
import { 
  Clock, Calendar, User, AlertCircle, CalendarDays, Timer, 
  Briefcase, CheckCircle, XCircle, Plus, Download, MoreHorizontal,
  ChevronLeft, ChevronRight, Users, Filter, Plane, Baby, Stethoscope,
  Moon, Sun, Sunset, Search, BarChart3, ArrowLeft, Edit2, Trash2, Save, RefreshCw,
  Layout
} from 'lucide-react';
import AppointmentsModule from './Appointments';
import { TimeEntry, WorkShift, ShiftType, AbsenceRequest, AbsenceType, Employee } from '../types';
import { useApp } from '../context/AppContext';

// --- Mock Data ---

const mockEmployees: Employee[] = [
    { id: 'e1', firstName: 'Sarah', lastName: 'Jenkins', email: 'sarah@example.com', phone: '', gender: 'Female', birthday: '', position: 'Therapist', department: 'Wellness', hireDate: '', status: 'Active' as any, address: '', zip: '', city: '', country: '', assignedServices: [] },
    { id: 'e2', firstName: 'Mike', lastName: 'Ross', email: 'mike@example.com', phone: '', gender: 'Male', birthday: '', position: 'Instructor', department: 'Fitness', hireDate: '', status: 'Active' as any, address: '', zip: '', city: '', country: '', assignedServices: [] },
    { id: 'e3', firstName: 'Jessica', lastName: 'Pearson', email: 'jessica@example.com', phone: '', gender: 'Female', birthday: '', position: 'Manager', department: 'Admin', hireDate: '', status: 'Active' as any, address: '', zip: '', city: '', country: '', assignedServices: [] },
];

const initialTimeEntries: TimeEntry[] = [
    { id: 't1', employeeId: 'e1', date: '2023-10-24', startTime: '08:00', endTime: '12:00', type: 'Work', status: 'Approved' },
    { id: 't2', employeeId: 'e1', date: '2023-10-24', startTime: '12:00', endTime: '13:00', type: 'Break', status: 'Approved' },
    { id: 't3', employeeId: 'e1', date: '2023-10-24', startTime: '13:00', endTime: '17:00', type: 'Work', status: 'Approved' },
    // Entries for Mike
    { id: 't5', employeeId: 'e2', date: '2023-10-24', startTime: '09:00', endTime: '12:15', type: 'Work', status: 'Approved' },
    { id: 't6', employeeId: 'e2', date: '2023-10-24', startTime: '12:15', endTime: '13:00', type: 'Break', status: 'Approved' },
    // Current open session
    { id: 't4', employeeId: 'current_user', date: new Date().toISOString().split('T')[0], startTime: '08:55', type: 'Work', status: 'Pending' },
];

// Mock Team Status for Overview
const mockTeamStatus = [
    { id: 'e1', name: 'Sarah Jenkins', department: 'Wellness', status: 'Working', startTime: '08:00', duration: '4h 30m', avatar: null },
    { id: 'e2', name: 'Mike Ross', department: 'Fitness', status: 'Break', startTime: '09:00', duration: '3h 15m', avatar: null },
    { id: 'e3', name: 'Jessica Pearson', department: 'Admin', status: 'Out', startTime: '-', duration: '0h 00m', avatar: null },
];

const initialShifts: WorkShift[] = [
    { id: 's1', employeeId: 'e1', date: '2023-10-24', type: 'Early', startTime: '08:00', endTime: '16:00' },
    { id: 's2', employeeId: 'e2', date: '2023-10-24', type: 'Late', startTime: '12:00', endTime: '20:00' },
];

const initialAbsences: AbsenceRequest[] = [
    { id: 'a1', employeeId: 'e1', employeeName: 'Sarah Jenkins', type: 'Vacation', startDate: '2023-11-10', endDate: '2023-11-15', reason: 'Family trip', status: 'Pending', requestedOn: '2023-10-20' },
    { id: 'a2', employeeId: 'e2', employeeName: 'Mike Ross', type: 'Sick', startDate: '2023-10-01', endDate: '2023-10-03', reason: 'Flu', status: 'Approved', requestedOn: '2023-10-01' },
];

// --- Helper Components ---

const ShiftBadge: React.FC<{ type: ShiftType; start: string; end: string }> = ({ type, start, end }) => {
    let color = 'bg-slate-100 text-slate-700 border-slate-200';
    let icon = <Sun size={12} />;
    
    if (type === 'Early') { color = 'bg-sky-50 text-sky-700 border-sky-200'; icon = <Sun size={12} />; }
    if (type === 'Late') { color = 'bg-amber-50 text-amber-700 border-amber-200'; icon = <Sunset size={12} />; }
    if (type === 'Night') { color = 'bg-indigo-50 text-indigo-700 border-indigo-200'; icon = <Moon size={12} />; }
    if (type === 'Off') { return <div className="text-xs text-slate-300 text-center py-1">OFF</div>; }

    return (
        <div className={`flex flex-col items-center justify-center px-1 py-1.5 rounded border ${color} text-[10px] font-medium cursor-pointer hover:brightness-95 transition-all h-full`}>
            <div className="flex items-center gap-1 mb-0.5">{icon} {type}</div>
            <div>{start}-{end}</div>
        </div>
    );
};

// --- WORKDAY MODULE MAIN ---

const WorkdayModule: React.FC = () => {
  const [activeTab, setActiveTab] = useState<'appointments' | 'timetracking' | 'shifts' | 'absences' | 'planner'>('appointments');

  return (
    <div className="flex flex-col md:flex-row h-full gap-6">
      {/* Sidebar Navigation */}
      <div className="w-full md:w-64 lg:w-72 flex-shrink-0 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden h-fit">
          <div className="p-4 border-b border-slate-100 bg-slate-50">
             <h2 className="text-base md:text-lg font-bold text-slate-800">Workday Hub</h2>
             <p className="text-xs text-slate-500">Daily Operations & HR</p>
          </div>
          <nav className="p-2 space-y-1">
             <button
               onClick={() => setActiveTab('appointments')}
               className={`w-full text-left px-4 py-2.5 rounded-md text-sm font-medium flex items-center gap-3 transition-all ${
                 activeTab === 'appointments' ? 'bg-brand-50 text-brand-700 shadow-sm ring-1 ring-brand-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
               }`}
             >
               <CalendarDays size={18} /> Appointments
             </button>
             <button
               onClick={() => setActiveTab('timetracking')}
               className={`w-full text-left px-4 py-2.5 rounded-md text-sm font-medium flex items-center gap-3 transition-all ${
                 activeTab === 'timetracking' ? 'bg-brand-50 text-brand-700 shadow-sm ring-1 ring-brand-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
               }`}
             >
               <Timer size={18} /> Time Tracking
             </button>
             <button
               onClick={() => setActiveTab('shifts')}
               className={`w-full text-left px-4 py-2.5 rounded-md text-sm font-medium flex items-center gap-3 transition-all ${
                 activeTab === 'shifts' ? 'bg-brand-50 text-brand-700 shadow-sm ring-1 ring-brand-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
               }`}
             >
               <Users size={18} /> Shift Planner
             </button>
             <button
               onClick={() => setActiveTab('absences')}
               className={`w-full text-left px-4 py-2.5 rounded-md text-sm font-medium flex items-center gap-3 transition-all ${
                 activeTab === 'absences' ? 'bg-brand-50 text-brand-700 shadow-sm ring-1 ring-brand-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
               }`}
             >
               <Briefcase size={18} /> Absences
             </button>
             <button
               onClick={() => setActiveTab('planner')}
               className={`w-full text-left px-4 py-2.5 rounded-md text-sm font-medium flex items-center gap-3 transition-all ${
                 activeTab === 'planner' ? 'bg-brand-50 text-brand-700 shadow-sm ring-1 ring-brand-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
               }`}
             >
               <Layout size={18} /> Course Planner
             </button>
          </nav>
      </div>

      {/* Main Content Area */}
      <div className="flex-1 min-w-0 animate-fadeIn h-full flex flex-col">
         {activeTab === 'appointments' && (
             <div className="h-full flex flex-col">
                 <AppointmentsModule />
             </div>
         )}

         {activeTab === 'timetracking' && <TimeTrackingTab />}
         {activeTab === 'shifts' && <ShiftPlannerTab />}
         {activeTab === 'absences' && <AbsenceTab />}
         {activeTab === 'planner' && <CoursePlannerTab />}
      </div>
    </div>
  );
};

// --- SUB-MODULES ---

const TimeTrackingTab: React.FC = () => {
    const [viewMode, setViewMode] = useState<'personal' | 'team'>('personal');
    const [entries, setEntries] = useState<TimeEntry[]>(initialTimeEntries);
    const [isClockedIn, setIsClockedIn] = useState(true);
    const [elapsedTime, setElapsedTime] = useState<string>('00:00:00');
    const [teamQuery, setTeamQuery] = useState('');
    
    // Team Management State
    const [selectedEmployeeId, setSelectedEmployeeId] = useState<string | null>(null);
    const [editingEntryId, setEditingEntryId] = useState<string | null>(null);
    const [editForm, setEditForm] = useState<Partial<TimeEntry>>({});

    // Simulation of permission check (e.g., is user a Manager?)
    const hasTeamPermission = true; 

    // Simulation of clock ticking
    useEffect(() => {
        let interval: any;
        if (isClockedIn) {
            const start = new Date();
            start.setHours(8, 55, 0); // Mock start time
            interval = setInterval(() => {
                const now = new Date();
                const diff = now.getTime() - start.getTime();
                const hours = Math.floor(diff / (1000 * 60 * 60));
                const mins = Math.floor((diff / (1000 * 60)) % 60);
                const secs = Math.floor((diff / 1000) % 60);
                setElapsedTime(`${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`);
            }, 1000);
        }
        return () => clearInterval(interval);
    }, [isClockedIn]);

    const handleClockAction = () => {
        if (isClockedIn) {
            // Clock Out
            const now = new Date();
            const timeStr = `${now.getHours()}:${now.getMinutes()}`;
            setEntries(prev => prev.map(e => e.id === 't4' ? {...e, endTime: timeStr, status: 'Approved'} : e));
            setIsClockedIn(false);
        } else {
            // Clock In
            const now = new Date();
            const timeStr = `${now.getHours()}:${now.getMinutes()}`;
            setEntries([...entries, { id: `t_${Date.now()}`, employeeId: 'current_user', date: now.toISOString().split('T')[0], startTime: timeStr, type: 'Work', status: 'Pending' }]);
            setIsClockedIn(true);
        }
    };

    const handleSaveEntry = (id: string) => {
        setEntries(prev => prev.map(e => e.id === id ? { ...e, ...editForm } : e));
        setEditingEntryId(null);
    };

    const handleAddManualEntry = () => {
        const newEntry: TimeEntry = {
            id: `t_new_${Date.now()}`,
            employeeId: selectedEmployeeId || 'current_user',
            date: new Date().toISOString().split('T')[0],
            startTime: '09:00',
            endTime: '17:00',
            type: 'Work',
            status: 'Approved'
        };
        setEntries([newEntry, ...entries]);
        setEditingEntryId(newEntry.id);
        setEditForm(newEntry);
    };

    const handleDeleteEntry = (id: string) => {
        if(confirm("Delete this time entry?")) {
            setEntries(prev => prev.filter(e => e.id !== id));
        }
    };

    const getStatusBadge = (status: string) => {
        switch(status) {
            case 'Working': return <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700"><span className="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>Working</span>;
            case 'Break': return <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700"><span className="w-2 h-2 bg-amber-500 rounded-full"></span>Break</span>;
            case 'Out': return <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-500"><span className="w-2 h-2 bg-slate-400 rounded-full"></span>Out</span>;
            default: return null;
        }
    }

    return (
        <div className="space-y-6 overflow-y-auto h-full p-1">
             
             {/* Navigation for Sub-Tabs */}
             <div className="flex items-center justify-between border-b border-slate-200 pb-2">
                 <div className="flex gap-4">
                    <button 
                        onClick={() => { setViewMode('personal'); setSelectedEmployeeId(null); }}
                        className={`pb-2 text-sm font-medium transition-colors relative ${viewMode === 'personal' ? 'text-brand-600' : 'text-slate-500 hover:text-slate-700'}`}
                    >
                        My Time
                        {viewMode === 'personal' && <div className="absolute bottom-0 left-0 right-0 h-0.5 bg-brand-600 rounded-t-full"></div>}
                    </button>
                    {hasTeamPermission && (
                        <button 
                            onClick={() => setViewMode('team')}
                            className={`pb-2 text-sm font-medium transition-colors relative ${viewMode === 'team' ? 'text-brand-600' : 'text-slate-500 hover:text-slate-700'}`}
                        >
                            Team Overview
                            {viewMode === 'team' && <div className="absolute bottom-0 left-0 right-0 h-0.5 bg-brand-600 rounded-t-full"></div>}
                        </button>
                    )}
                 </div>
                 {/* Global Action: Sync Calendar */}
                 <button className="text-xs font-medium text-brand-600 flex items-center gap-1 hover:underline">
                    <RefreshCw size={14} /> Sync Calendars
                 </button>
             </div>

             {viewMode === 'personal' && (
                 <div className="space-y-6 animate-fadeIn">
                    {/* Status Header */}
                    <div className="bg-slate-900 text-white rounded-xl p-6 shadow-lg flex flex-col md:flex-row items-center justify-between">
                        <div className="flex items-center gap-4">
                            <div className={`p-3 rounded-full ${isClockedIn ? 'bg-brand-600 animate-pulse' : 'bg-slate-700'}`}>
                                <Clock size={24} />
                            </div>
                            <div>
                                <p className="text-slate-400 text-sm">Current Status</p>
                                <h3 className="text-xl font-bold">{isClockedIn ? 'Clocked In' : 'Clocked Out'}</h3>
                                {isClockedIn && <p className="text-xs text-brand-300">Started today at 08:55 AM</p>}
                            </div>
                        </div>
                        <div className="text-center md:text-right mt-4 md:mt-0">
                            <div className="text-4xl font-mono font-bold tracking-widest mb-3">{isClockedIn ? elapsedTime : '--:--:--'}</div>
                            <button 
                                onClick={handleClockAction}
                                className={`px-6 py-2 rounded-lg text-sm font-medium transition-colors shadow-lg ${
                                    isClockedIn ? 'bg-rose-500 hover:bg-rose-600 text-white' : 'bg-emerald-500 hover:bg-emerald-600 text-white'
                                }`}
                            >
                                {isClockedIn ? 'Clock Out' : 'Clock In'}
                            </button>
                        </div>
                    </div>

                    {/* Stats Row */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                            <div className="text-sm text-slate-500 font-medium mb-1">Hours Today</div>
                            <div className="text-2xl font-bold text-slate-800">4h 12m</div>
                            <div className="text-xs text-emerald-600 font-medium">On Track (Target 8h)</div>
                        </div>
                        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                            <div className="text-sm text-slate-500 font-medium mb-1">Weekly Overtime</div>
                            <div className="text-2xl font-bold text-amber-600">+1h 30m</div>
                            <div className="text-xs text-slate-400">Calculated vs 40h week</div>
                        </div>
                        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                            <div className="text-sm text-slate-500 font-medium mb-1">Absence Balance</div>
                            <div className="text-2xl font-bold text-slate-800">21.5 Days</div>
                            <div className="text-xs text-slate-400">Vacation Remaining</div>
                        </div>
                    </div>

                    {/* Time Sheet */}
                    <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                        <div className="p-4 border-b border-slate-100 flex justify-between items-center">
                            <h3 className="font-bold text-slate-800">Timesheet (This Week)</h3>
                            <button onClick={handleAddManualEntry} className="text-brand-600 text-sm font-medium flex items-center gap-1 hover:bg-brand-50 px-2 py-1 rounded">
                                <Plus size={16} /> Manual Entry
                            </button>
                        </div>
                        <table className="w-full text-left">
                            <thead className="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase">
                                <tr>
                                    <th className="p-4">Date</th>
                                    <th className="p-4">Time Range</th>
                                    <th className="p-4">Type</th>
                                    <th className="p-4">Duration</th>
                                    <th className="p-4">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 text-sm">
                                {entries.filter(e => e.employeeId === 'current_user' || e.employeeId === 'e1').map(entry => (
                                    <tr key={entry.id} className="hover:bg-slate-50">
                                        <td className="p-4 font-medium text-slate-800">{entry.date}</td>
                                        <td className="p-4 text-slate-600">{entry.startTime} - {entry.endTime || '...'}</td>
                                        <td className="p-4">
                                            <span className={`px-2 py-1 rounded text-xs font-medium ${
                                                entry.type === 'Break' ? 'bg-amber-50 text-amber-700' : 'bg-blue-50 text-blue-700'
                                            }`}>
                                                {entry.type}
                                            </span>
                                        </td>
                                        <td className="p-4 font-mono text-slate-600">
                                            {entry.endTime ? '4h 00m' : 'In Progress'}
                                        </td>
                                        <td className="p-4">
                                            {entry.status === 'Approved' && <CheckCircle size={16} className="text-emerald-500" />}
                                            {entry.status === 'Pending' && <Clock size={16} className="text-amber-500" />}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                 </div>
             )}

             {viewMode === 'team' && !selectedEmployeeId && (
                 <div className="space-y-6 animate-fadeIn">
                    {/* Team Filters */}
                    <div className="flex flex-col md:flex-row gap-4 justify-between items-center bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                        <div className="relative w-full md:w-96">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
                            <input 
                                type="text" 
                                placeholder="Search employee..." 
                                value={teamQuery}
                                onChange={(e) => setTeamQuery(e.target.value)}
                                className="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                            />
                        </div>
                        <div className="flex gap-2 w-full md:w-auto">
                            <button className="flex items-center gap-2 px-4 py-2 border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50 text-sm font-medium">
                                <Filter size={16} /> Department
                            </button>
                            <button className="flex items-center gap-2 px-4 py-2 border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50 text-sm font-medium">
                                <Download size={16} /> Report
                            </button>
                        </div>
                    </div>

                    {/* Team Stats Overview */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
                            <div className="p-3 rounded-lg bg-emerald-50 text-emerald-600">
                                <Users size={20} />
                            </div>
                            <div>
                                <p className="text-xs text-slate-500 font-bold uppercase">Active Now</p>
                                <h4 className="text-xl font-bold text-slate-800">12 <span className="text-xs font-normal text-slate-400">/ 18</span></h4>
                            </div>
                        </div>
                        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
                            <div className="p-3 rounded-lg bg-amber-50 text-amber-600">
                                <Clock size={20} />
                            </div>
                            <div>
                                <p className="text-xs text-slate-500 font-bold uppercase">On Break</p>
                                <h4 className="text-xl font-bold text-slate-800">2</h4>
                            </div>
                        </div>
                        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
                            <div className="p-3 rounded-lg bg-blue-50 text-blue-600">
                                <BarChart3 size={20} />
                            </div>
                            <div>
                                <p className="text-xs text-slate-500 font-bold uppercase">Total Hrs Today</p>
                                <h4 className="text-xl font-bold text-slate-800">48h 20m</h4>
                            </div>
                        </div>
                    </div>

                    {/* Team Grid */}
                    <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                        <table className="w-full text-left">
                            <thead className="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase">
                                <tr>
                                    <th className="p-4">Employee</th>
                                    <th className="p-4">Department</th>
                                    <th className="p-4">Live Status</th>
                                    <th className="p-4">Started At</th>
                                    <th className="p-4">Duration</th>
                                    <th className="p-4 text-right">Details</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 text-sm">
                                {mockTeamStatus.filter(e => e.name.toLowerCase().includes(teamQuery.toLowerCase())).map(emp => (
                                    <tr key={emp.id} onClick={() => setSelectedEmployeeId(emp.id)} className="hover:bg-slate-50 cursor-pointer group">
                                        <td className="p-4">
                                            <div className="flex items-center gap-3">
                                                <div className="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold">
                                                    {emp.name.split(' ').map(n => n[0]).join('')}
                                                </div>
                                                <span className="font-medium text-slate-800 group-hover:text-brand-600 transition-colors">{emp.name}</span>
                                            </div>
                                        </td>
                                        <td className="p-4 text-slate-600">{emp.department}</td>
                                        <td className="p-4">
                                            {getStatusBadge(emp.status)}
                                        </td>
                                        <td className="p-4 font-mono text-slate-600">
                                            {emp.startTime}
                                        </td>
                                        <td className="p-4 font-mono text-slate-800 font-medium">
                                            {emp.duration}
                                        </td>
                                        <td className="p-4 text-right">
                                            <ChevronRight size={16} className="text-slate-400" />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                 </div>
             )}

             {viewMode === 'team' && selectedEmployeeId && (
                <div className="space-y-6 animate-fadeIn">
                    <div className="flex items-center gap-4">
                        <button onClick={() => setSelectedEmployeeId(null)} className="p-2 hover:bg-slate-100 rounded-full transition-colors">
                            <ArrowLeft size={20} className="text-slate-600" />
                        </button>
                        <div>
                            <h3 className="text-lg font-bold text-slate-800">
                                {mockTeamStatus.find(e => e.id === selectedEmployeeId)?.name}'s Timesheet
                            </h3>
                            <p className="text-xs text-slate-500">Managing time entries and approvals</p>
                        </div>
                    </div>

                    <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                        <div className="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                           <div className="text-sm font-bold text-slate-700">October 2023</div>
                           <button 
                                onClick={handleAddManualEntry}
                                className="px-3 py-1.5 bg-white border border-slate-300 rounded text-xs font-medium shadow-sm hover:bg-slate-50 flex items-center gap-1"
                           >
                              <Plus size={14} /> Add Manual Entry
                           </button>
                        </div>
                        <table className="w-full text-left">
                            <thead className="bg-white border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase">
                                <tr>
                                    <th className="p-4">Date</th>
                                    <th className="p-4">Time Range</th>
                                    <th className="p-4">Type</th>
                                    <th className="p-4">Total</th>
                                    <th className="p-4">Status</th>
                                    <th className="p-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 text-sm">
                                {entries.filter(e => e.employeeId === selectedEmployeeId).map(entry => (
                                    <tr key={entry.id} className="hover:bg-slate-50 group">
                                        <td className="p-4 font-medium text-slate-800">{entry.date}</td>
                                        <td className="p-4 text-slate-600">
                                            {editingEntryId === entry.id ? (
                                                <div className="flex items-center gap-2">
                                                    <input className="w-16 p-1 border rounded text-xs" defaultValue={entry.startTime} onChange={e => setEditForm({...editForm, startTime: e.target.value})} />
                                                    -
                                                    <input className="w-16 p-1 border rounded text-xs" defaultValue={entry.endTime} onChange={e => setEditForm({...editForm, endTime: e.target.value})} />
                                                </div>
                                            ) : (
                                                <span>{entry.startTime} - {entry.endTime}</span>
                                            )}
                                        </td>
                                        <td className="p-4">
                                            <span className={`px-2 py-1 rounded text-xs font-medium ${
                                                entry.type === 'Break' ? 'bg-amber-50 text-amber-700' : 'bg-blue-50 text-blue-700'
                                            }`}>
                                                {entry.type}
                                            </span>
                                        </td>
                                        <td className="p-4 font-mono text-slate-600">4h 00m</td>
                                        <td className="p-4">
                                            <span className="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">{entry.status}</span>
                                        </td>
                                        <td className="p-4 text-right">
                                            {editingEntryId === entry.id ? (
                                                <div className="flex justify-end gap-2">
                                                    <button onClick={() => handleSaveEntry(entry.id)} className="p-1.5 bg-emerald-100 text-emerald-600 rounded hover:bg-emerald-200"><Save size={14} /></button>
                                                    <button onClick={() => setEditingEntryId(null)} className="p-1.5 bg-slate-100 text-slate-600 rounded hover:bg-slate-200"><XCircle size={14} /></button>
                                                </div>
                                            ) : (
                                                <div className="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <button onClick={() => { setEditingEntryId(entry.id); setEditForm({}); }} className="p-1.5 bg-white border border-slate-200 text-slate-500 rounded hover:text-brand-600 hover:border-brand-300"><Edit2 size={14} /></button>
                                                    <button onClick={() => handleDeleteEntry(entry.id)} className="p-1.5 bg-white border border-slate-200 text-slate-500 rounded hover:text-rose-600 hover:border-rose-300"><Trash2 size={14} /></button>
                                                </div>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                                {entries.filter(e => e.employeeId === selectedEmployeeId).length === 0 && (
                                    <tr>
                                        <td colSpan={6} className="p-8 text-center text-slate-400">
                                            No entries found for this employee.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
             )}
        </div>
    );
};

const ShiftPlannerTab: React.FC = () => {
    const [currentDate, setCurrentDate] = useState(new Date());
    const days = Array.from({length: 7}, (_, i) => {
        const d = new Date(currentDate);
        d.setDate(d.getDate() - d.getDay() + 1 + i); // Start Mon
        return d;
    });

    // Mock assignment logic
    const getShiftFor = (empId: string, dateStr: string) => {
        return initialShifts.find(s => s.employeeId === empId && s.date === dateStr);
    };

    return (
        <div className="flex flex-col h-full">
            {/* Toolbar */}
            <div className="flex flex-col sm:flex-row justify-between items-center gap-4 mb-4">
                <div className="flex items-center gap-4 bg-white p-2 rounded-lg border border-slate-200 shadow-sm">
                    <button className="p-1 hover:bg-slate-100 rounded text-slate-600"><ChevronLeft size={20}/></button>
                    <div className="text-sm font-bold text-slate-800 w-32 text-center">
                        Oct 23 - Oct 29
                    </div>
                    <button className="p-1 hover:bg-slate-100 rounded text-slate-600"><ChevronRight size={20}/></button>
                </div>
                <div className="flex gap-2">
                    <button className="px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm font-medium hover:bg-slate-50 text-slate-600">
                        Load Template
                    </button>
                    <button className="px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm font-medium hover:bg-slate-50 text-slate-600">
                        Copy Last Week
                    </button>
                    <button className="px-3 py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700 shadow-sm">
                        Publish
                    </button>
                </div>
            </div>

            {/* Grid */}
            <div className="flex-1 bg-white rounded-xl border border-slate-200 shadow-sm overflow-auto">
                <table className="w-full border-collapse min-w-[800px]">
                    <thead className="bg-slate-50 sticky top-0 z-10">
                        <tr>
                            <th className="p-4 text-left text-xs font-bold text-slate-500 uppercase border-b border-r border-slate-200 w-48 bg-slate-50 z-20 sticky left-0">Employee</th>
                            {days.map(d => (
                                <th key={d.toISOString()} className="p-3 text-center border-b border-slate-200 min-w-[100px]">
                                    <div className="text-xs font-medium text-slate-500">{d.toLocaleDateString('en-US', {weekday: 'short'})}</div>
                                    <div className="text-sm font-bold text-slate-800">{d.getDate()}</div>
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {mockEmployees.map(emp => (
                            <tr key={emp.id}>
                                <td className="p-4 border-b border-r border-slate-200 bg-white sticky left-0 z-10">
                                    <div className="flex items-center gap-3">
                                        <div className="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold">
                                            {emp.firstName[0]}{emp.lastName[0]}
                                        </div>
                                        <div>
                                            <div className="font-medium text-sm text-slate-900">{emp.firstName} {emp.lastName}</div>
                                            <div className="text-xs text-slate-500">{emp.position}</div>
                                        </div>
                                    </div>
                                </td>
                                {days.map(d => {
                                    const dateStr = d.toISOString().split('T')[0]; // Simple ISO Date
                                    // In real app, fix timezone issues with date strings
                                    const shift = getShiftFor(emp.id, '2023-10-24'); // Mock check against single date for demo
                                    const isToday = d.getDate() === 24; // Mock

                                    return (
                                        <td key={dateStr} className="p-1 border-b border-slate-100 text-center h-16 hover:bg-slate-50 transition-colors">
                                            {isToday && shift ? (
                                                <ShiftBadge type={shift.type} start={shift.startTime} end={shift.endTime} />
                                            ) : (
                                                <div className="w-full h-full rounded border-2 border-dashed border-transparent hover:border-slate-200 flex items-center justify-center opacity-0 hover:opacity-100 cursor-pointer">
                                                    <Plus size={14} className="text-slate-400" />
                                                </div>
                                            )}
                                        </td>
                                    );
                                })}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            <div className="mt-4 flex gap-4 text-xs text-slate-500">
                <div className="flex items-center gap-1"><div className="w-3 h-3 bg-sky-50 border border-sky-200 rounded"></div> Early (06:00 - 14:00)</div>
                <div className="flex items-center gap-1"><div className="w-3 h-3 bg-amber-50 border border-amber-200 rounded"></div> Late (14:00 - 22:00)</div>
                <div className="flex items-center gap-1"><div className="w-3 h-3 bg-indigo-50 border border-indigo-200 rounded"></div> Night (22:00 - 06:00)</div>
            </div>
        </div>
    );
};

const AbsenceTab: React.FC = () => {
    const [requests, setRequests] = useState<AbsenceRequest[]>(initialAbsences);
    const [isModalOpen, setIsModalOpen] = useState(false);

    return (
        <div className="flex flex-col h-full space-y-6">
            <div className="flex justify-between items-center">
                <h3 className="font-bold text-slate-800 text-lg">Absence Management</h3>
                <button 
                    onClick={() => setIsModalOpen(true)}
                    className="bg-brand-600 text-white px-4 py-2 rounded-lg hover:bg-brand-700 text-sm font-medium shadow-sm flex items-center gap-2"
                >
                    <Plus size={16} /> Request Absence
                </button>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
                    <div className="p-3 rounded-full bg-emerald-100 text-emerald-600"><Plane size={20}/></div>
                    <div>
                        <div className="text-xs text-slate-500 font-bold uppercase">Vacation Balance</div>
                        <div className="text-xl font-bold text-slate-800">21.5 <span className="text-xs font-normal text-slate-400">/ 25 Days</span></div>
                    </div>
                </div>
                <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
                    <div className="p-3 rounded-full bg-rose-100 text-rose-600"><Stethoscope size={20}/></div>
                    <div>
                        <div className="text-xs text-slate-500 font-bold uppercase">Sick Days (YTD)</div>
                        <div className="text-xl font-bold text-slate-800">3 <span className="text-xs font-normal text-slate-400">Days</span></div>
                    </div>
                </div>
                <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
                    <div className="p-3 rounded-full bg-indigo-100 text-indigo-600"><Baby size={20}/></div>
                    <div>
                        <div className="text-xs text-slate-500 font-bold uppercase">Other Leave</div>
                        <div className="text-xl font-bold text-slate-800">0 <span className="text-xs font-normal text-slate-400">Days</span></div>
                    </div>
                </div>
            </div>

            <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex-1">
                <table className="w-full text-left">
                    <thead className="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase">
                        <tr>
                            <th className="p-4">Employee</th>
                            <th className="p-4">Type</th>
                            <th className="p-4">Dates</th>
                            <th className="p-4">Duration</th>
                            <th className="p-4">Reason</th>
                            <th className="p-4">Status</th>
                            <th className="p-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100 text-sm">
                        {requests.map(req => (
                            <tr key={req.id} className="hover:bg-slate-50">
                                <td className="p-4 font-bold text-slate-700">{req.employeeName}</td>
                                <td className="p-4">
                                    <span className={`px-2 py-1 rounded-full text-xs font-medium 
                                        ${req.type === 'Vacation' ? 'bg-emerald-50 text-emerald-700' :
                                          req.type === 'Sick' ? 'bg-rose-50 text-rose-700' : 'bg-slate-100 text-slate-700'}
                                    `}>
                                        {req.type}
                                    </span>
                                </td>
                                <td className="p-4 text-slate-600">{req.startDate} <span className="text-slate-400 px-1">to</span> {req.endDate}</td>
                                <td className="p-4 text-slate-600">5 Days</td>
                                <td className="p-4 text-slate-500 italic">{req.reason}</td>
                                <td className="p-4">
                                    <span className={`flex items-center gap-1 font-bold text-xs 
                                        ${req.status === 'Approved' ? 'text-emerald-600' : 
                                          req.status === 'Pending' ? 'text-amber-600' : 'text-rose-600'}
                                    `}>
                                        {req.status === 'Approved' ? <CheckCircle size={12}/> : 
                                         req.status === 'Pending' ? <Clock size={12}/> : <XCircle size={12}/>}
                                        {req.status}
                                    </span>
                                </td>
                                <td className="p-4 text-right">
                                    {req.status === 'Pending' && (
                                        <div className="flex justify-end gap-2">
                                            <button className="p-1.5 bg-emerald-50 text-emerald-600 rounded hover:bg-emerald-100"><CheckCircle size={16}/></button>
                                            <button className="p-1.5 bg-rose-50 text-rose-600 rounded hover:bg-rose-100"><XCircle size={16}/></button>
                                        </div>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {isModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
                    <div className="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6">
                        <div className="flex justify-between items-center mb-4 border-b pb-2">
                            <h3 className="font-bold text-lg">New Absence Request</h3>
                            <button onClick={() => setIsModalOpen(false)}><XCircle className="text-slate-400" /></button>
                        </div>
                        <div className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">Absence Type</label>
                                <select className="w-full border border-slate-300 rounded-lg px-3 py-2">
                                    <option value="Vacation">Vacation (Urlaub)</option>
                                    <option value="Sick">Sick (Krank)</option>
                                    <option value="NBU">Non-Occupational Accident (NBU)</option>
                                    <option value="BU">Occupational Accident (BU)</option>
                                    <option value="Maternity">Maternity/Paternity</option>
                                    <option value="Unpaid">Unpaid Leave</option>
                                </select>
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-1">Start Date</label>
                                    <input type="date" className="w-full border border-slate-300 rounded-lg px-3 py-2" />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-1">End Date</label>
                                    <input type="date" className="w-full border border-slate-300 rounded-lg px-3 py-2" />
                                </div>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">Reason / Notes</label>
                                <textarea className="w-full border border-slate-300 rounded-lg px-3 py-2" rows={3}></textarea>
                            </div>
                            <div className="flex justify-end gap-2 pt-2">
                                <button onClick={() => setIsModalOpen(false)} className="px-4 py-2 text-slate-600 hover:bg-slate-50 rounded-lg">Cancel</button>
                                <button onClick={() => setIsModalOpen(false)} className="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700">Submit Request</button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

const CoursePlannerTab = () => {
  const { t } = useApp();
  return (
    <div className="flex-1 flex flex-col h-full">
      <div className="flex justify-between items-center mb-4">
        <h3 className="text-lg font-bold text-slate-800">{t('course_planner')}</h3>
        <div className="flex gap-2">
          <div className="flex bg-white rounded-lg border border-slate-200 p-1">
            <button className="px-3 py-1 text-xs font-medium bg-slate-100 rounded text-slate-800">Week</button>
            <button className="px-3 py-1 text-xs font-medium text-slate-500 hover:bg-slate-50">Month</button>
          </div>
          <button className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">Save Schedule</button>
        </div>
      </div>
      
      <div className="flex-1 flex overflow-hidden border border-slate-200 rounded-xl">
        {/* Resources Sidebar */}
        <div className="w-48 border-r border-slate-200 p-4 bg-slate-50 overflow-y-auto flex-shrink-0">
          <h4 className="text-xs font-bold text-slate-500 uppercase mb-3">Draggable Courses</h4>
          <div className="space-y-2">
            {['Yoga Basics', 'HIIT Advanced', 'Meditation', 'Spin Class'].map(c => (
              <div key={c} className="p-3 bg-white border border-slate-200 rounded-lg shadow-sm cursor-move hover:border-brand-400 text-sm font-medium text-slate-700">
                {c}
              </div>
            ))}
          </div>
          
          <h4 className="text-xs font-bold text-slate-500 uppercase mt-6 mb-3">Instructors</h4>
          <div className="space-y-2">
            {['Sarah J.', 'Mike R.', 'Emma W.'].map(i => (
              <div key={i} className="flex items-center gap-2 p-2 hover:bg-slate-100 rounded cursor-pointer">
                <div className="w-6 h-6 bg-brand-100 rounded-full text-brand-600 flex items-center justify-center text-xs font-bold">
                  {i[0]}
                </div>
                <span className="text-sm text-slate-600">{i}</span>
              </div>
            ))}
          </div>
        </div>

        {/* Grid Area */}
        <div className="flex-1 overflow-auto bg-white relative">
          <div className="min-w-[800px]">
             {/* Header Days */}
             <div className="grid grid-cols-8 border-b border-slate-200 sticky top-0 bg-white z-10">
                <div className="p-3 text-xs font-bold text-slate-400 border-r">Time / Room</div>
                {['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].map(d => (
                   <div key={d} className="p-3 text-center text-sm font-bold text-slate-700 border-r bg-slate-50">{d}</div>
                ))}
             </div>
             {/* Rooms Rows */}
             {['Room A (Large)', 'Room B (Quiet)', 'Spin Studio'].map(room => (
                <div key={room} className="grid grid-cols-8 border-b border-slate-100 min-h-[100px]">
                   <div className="p-3 text-xs font-medium text-slate-500 border-r bg-slate-50/50">{room}</div>
                   {/* Cells */}
                   {Array(7).fill(null).map((_, i) => (
                      <div key={i} className="border-r border-slate-50 relative p-1 hover:bg-slate-50 transition-colors">
                         {i === 1 && room.includes('Room A') && (
                            <div className="absolute top-2 left-2 right-2 bg-blue-100 border border-blue-200 text-blue-700 p-2 rounded text-xs cursor-pointer">
                               <div className="font-bold">Yoga Basics</div>
                               <div className="opacity-75">09:00 - 10:30</div>
                            </div>
                         )}
                         {i === 3 && room.includes('Spin') && (
                            <div className="absolute top-10 left-2 right-2 bg-purple-100 border border-purple-200 text-purple-700 p-2 rounded text-xs cursor-pointer">
                               <div className="font-bold">Spin Class</div>
                               <div className="opacity-75">18:00 - 19:00</div>
                            </div>
                         )}
                      </div>
                   ))}
                </div>
             ))}
          </div>
        </div>
      </div>
    </div>
  );
};

export default WorkdayModule;
