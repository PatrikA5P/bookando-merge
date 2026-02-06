
import React, { useState } from 'react';
import {
  MapPin, Box, LayoutGrid, Wrench, AlertCircle, CheckCircle,
  Calendar, Settings, Plus, MoreHorizontal, Map
} from 'lucide-react';
import { useApp } from '../context/AppContext';
import ModuleLayout from '../components/ModuleLayout';
import { getModuleDesign } from '../utils/designTokens';

const ResourcesModule: React.FC = () => {
  const [activeTab, setActiveTab] = useState<'locations' | 'rooms' | 'equipment'>('locations');
  const { locations, rooms, equipment } = useApp();

  const moduleDesign = getModuleDesign('resources');

  const tabs = [
    { id: 'locations', icon: MapPin, label: 'Locations' },
    { id: 'rooms', icon: LayoutGrid, label: 'Rooms & Spaces' },
    { id: 'equipment', icon: Box, label: 'Equipment' },
  ];

  return (
    <div className="flex flex-col min-h-full">
      <ModuleLayout
        variant="mixed"
        moduleName="Resources"
        hero={{
          icon: Box,
          title: 'Resources',
          description: 'Facilities & Inventory Management',
          gradient: moduleDesign.gradient
        }}
        tabs={tabs}
        activeTab={activeTab}
        onTabChange={(tabId) => setActiveTab(tabId as any)}
      >
        
        {/* Locations */}
        {activeTab === 'locations' && (
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {locations.map(loc => (
              <div key={loc.id} className="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-col">
                <div className="flex justify-between items-start mb-4">
                   <div className="p-3 bg-indigo-50 text-indigo-600 rounded-lg">
                      <Map size={24} />
                   </div>
                   <span className={`px-2 py-1 rounded-full text-xs font-medium ${loc.status === 'Open' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'}`}>
                      {loc.status}
                   </span>
                </div>
                <h3 className="text-lg font-bold text-slate-900 mb-1">{loc.name}</h3>
                <p className="text-slate-500 text-sm mb-4 flex items-center gap-1">
                  <MapPin size={14} /> {loc.address}
                </p>
                <div className="mt-auto pt-4 border-t border-slate-100 flex justify-between items-center">
                   <span className="text-sm font-medium text-slate-600">{loc.rooms} Rooms Configured</span>
                   <button className="text-brand-600 text-sm font-medium hover:underline">Manage Details</button>
                </div>
              </div>
            ))}
             <button className="border-2 border-dashed border-slate-300 rounded-xl p-6 flex flex-col items-center justify-center text-slate-400 hover:border-brand-400 hover:text-brand-600 hover:bg-slate-50 transition-all min-h-[200px]">
                <Plus size={32} className="mb-2" />
                <span className="font-medium">Add New Location</span>
             </button>
          </div>
        )}

        {/* Rooms */}
        {activeTab === 'rooms' && (
          <div className="grid grid-cols-1 xl:grid-cols-2 gap-6">
             {rooms.map(room => (
               <div key={room.id} className="bg-white border border-slate-200 rounded-xl overflow-hidden hover:shadow-md transition-shadow">
                  <div className={`h-2 w-full ${
                    room.status === 'Available' ? 'bg-emerald-500' : 
                    room.status === 'In Use' ? 'bg-amber-500' : 'bg-rose-500'
                  }`}></div>
                  <div className="p-5">
                     <div className="flex justify-between items-center mb-2">
                        <h3 className="font-semibold text-slate-800 text-lg">{room.name}</h3>
                        <MoreHorizontal className="text-slate-400 cursor-pointer hover:text-slate-600" size={18} />
                     </div>
                     <p className="text-xs text-slate-500 mb-4">{room.location}</p>
                     
                     <div className="space-y-2 mb-4">
                        <div className="flex justify-between text-sm">
                           <span className="text-slate-500">Capacity</span>
                           <span className="font-medium text-slate-800">{room.capacity} People</span>
                        </div>
                        <div className="flex justify-between text-sm">
                           <span className="text-slate-500">Status</span>
                           <span className={`font-medium ${
                             room.status === 'Available' ? 'text-emerald-600' : 
                             room.status === 'In Use' ? 'text-amber-600' : 'text-rose-600'
                           }`}>{room.status}</span>
                        </div>
                     </div>
                     
                     <div className="flex gap-2 flex-wrap">
                        {room.features.map((feat, i) => (
                           <span key={i} className="text-[10px] bg-slate-100 text-slate-600 px-2 py-1 rounded border border-slate-200">
                             {feat}
                           </span>
                        ))}
                     </div>
                  </div>
               </div>
             ))}
          </div>
        )}

        {/* Equipment */}
        {activeTab === 'equipment' && (
          <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
             <div className="overflow-x-auto">
                <table className="w-full text-left">
                   <thead className="bg-slate-50 border-b border-slate-200 text-xs uppercase text-slate-500 font-semibold">
                      <tr>
                         <th className="p-4">Item Name</th>
                         <th className="p-4">Category</th>
                         <th className="p-4">Availability</th>
                         <th className="p-4">Condition</th>
                         <th className="p-4 text-right">Action</th>
                      </tr>
                   </thead>
                   <tbody className="divide-y divide-slate-100">
                      {equipment.map(item => (
                         <tr key={item.id} className="hover:bg-slate-50 group">
                            <td className="p-4 font-medium text-slate-800 flex items-center gap-3">
                               <div className="bg-slate-100 p-2 rounded text-slate-500">
                                  <Box size={16} />
                               </div>
                               {item.name}
                            </td>
                            <td className="p-4 text-sm text-slate-600">{item.category}</td>
                            <td className="p-4">
                               <div className="w-32">
                                  <div className="flex justify-between text-xs mb-1">
                                     <span className="font-medium text-slate-700">{item.available}/{item.total}</span>
                                     <span className="text-slate-400">Available</span>
                                  </div>
                                  <div className="w-full bg-slate-100 rounded-full h-1.5">
                                     <div 
                                       className={`h-1.5 rounded-full ${item.available === 0 ? 'bg-rose-400' : 'bg-brand-500'}`} 
                                       style={{ width: `${(item.available / item.total) * 100}%` }}
                                     ></div>
                                  </div>
                               </div>
                            </td>
                            <td className="p-4">
                               <span className={`text-xs font-medium px-2 py-1 rounded-full border ${
                                  item.condition === 'Good' ? 'bg-emerald-50 border-emerald-100 text-emerald-700' : 
                                  item.condition === 'Fair' ? 'bg-amber-50 border-amber-100 text-amber-700' : 
                                  'bg-rose-50 border-rose-100 text-rose-700'
                               }`}>
                                  {item.condition}
                               </span>
                            </td>
                            <td className="p-4 text-right">
                               <button className="text-slate-400 hover:text-brand-600 opacity-0 group-hover:opacity-100 transition-opacity">
                                  <Settings size={18} />
                               </button>
                            </td>
                         </tr>
                      ))}
                   </tbody>
                </table>
             </div>
          </div>
        )}

      </ModuleLayout>
    </div>
  );
};

export default ResourcesModule;
