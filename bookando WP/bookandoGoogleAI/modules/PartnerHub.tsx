import React, { useState } from 'react';
import { Partner } from '../types';
import { 
  Shield, CheckCircle, AlertTriangle, FileText, Share2, Search, Globe, Lock, Settings, RefreshCw,
  Code, Key, Copy, Terminal, Eye, EyeOff, Webhook, BookOpen
} from 'lucide-react';

const mockPartners: Partner[] = [
  { id: 'P-001', companyName: 'Yoga Studio Downtown', type: 'Service Provider', status: 'Active', gdprSigned: true, revenueShare: 15 },
  { id: 'P-002', companyName: 'Fitness Equipment Co', type: 'Reseller', status: 'Active', gdprSigned: true, revenueShare: 10 },
  { id: 'P-003', companyName: 'Wellness Apps Inc', type: 'Service Provider', status: 'Pending', gdprSigned: false, revenueShare: 20 },
];

const mockApiKeys = [
  { id: 'key_live_1', name: 'Production App', prefix: 'pk_live_...', created: '2023-09-15', lastUsed: '2 mins ago', status: 'Active' },
  { id: 'key_test_1', name: 'Development', prefix: 'pk_test_...', created: '2023-10-01', lastUsed: 'Never', status: 'Active' },
];

const PartnerHubModule: React.FC = () => {
  const [activeTab, setActiveTab] = useState<'network' | 'gdpr' | 'api' | 'settings'>('network');
  const [showKey, setShowKey] = useState<string | null>(null);

  return (
    <div className="flex flex-col md:flex-row h-[calc(100vh-140px)] gap-6">
       {/* Fixed Sidebar Nav */}
       <div className="w-full md:w-64 lg:w-72 flex-shrink-0">
          <div className="bg-indigo-900 text-white p-6 rounded-xl relative overflow-hidden shadow-lg mb-4">
             <div className="relative z-10">
                <div className="flex items-center gap-3 mb-2">
                   <Share2 className="text-indigo-300" size={24} />
                   <h2 className="text-xl font-bold">Partner Hub</h2>
                </div>
                <p className="text-indigo-200 text-xs max-w-2xl">
                   Connect & Expand securely. GDPR Compliant.
                </p>
             </div>
             <div className="absolute right-0 top-0 opacity-10">
                <Globe size={100} />
             </div>
          </div>

          <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
             <nav className="p-2 space-y-1">
               {[
                  { id: 'network', icon: Share2, label: 'Partner Network' },
                  { id: 'gdpr', icon: Shield, label: 'Data Privacy & GDPR' },
                  { id: 'api', icon: Code, label: 'API & Developers' },
                  { id: 'settings', icon: Settings, label: 'Hub Settings' }
               ].map((tab) => (
                  <button
                  key={tab.id}
                  onClick={() => setActiveTab(tab.id as any)}
                  className={`
                     w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors text-sm font-medium
                     ${activeTab === tab.id 
                        ? 'bg-indigo-50 text-indigo-700' 
                        : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'}
                  `}
                  >
                  <tab.icon size={18} />
                  {tab.label}
                  </button>
               ))}
             </nav>
          </div>
       </div>

       {/* Main Content Area */}
       <div className="flex-1 overflow-y-auto bg-white rounded-xl border border-slate-200 shadow-sm p-0 min-w-0">
          
          {/* Network Tab */}
          {activeTab === 'network' && (
             <div className="flex flex-col h-full animate-fadeIn">
                <div className="p-6 border-b border-slate-100 flex justify-between items-center">
                   <h3 className="font-bold text-lg text-slate-800">Connected Partners</h3>
                   <button className="text-sm text-slate-500 hover:text-indigo-600 flex items-center gap-1">
                      <RefreshCw size={14} /> Sync All
                   </button>
                </div>
                <div className="flex-1 overflow-auto">
                   <table className="w-full text-left">
                   <thead className="bg-slate-50 border-b border-slate-200">
                      <tr>
                         <th className="p-4 text-xs text-slate-500 uppercase font-semibold">Company</th>
                         <th className="p-4 text-xs text-slate-500 uppercase font-semibold">Type</th>
                         <th className="p-4 text-xs text-slate-500 uppercase font-semibold">GDPR Status</th>
                         <th className="p-4 text-xs text-slate-500 uppercase font-semibold">Rev Share</th>
                         <th className="p-4 text-xs text-slate-500 uppercase font-semibold text-right">Status</th>
                      </tr>
                   </thead>
                   <tbody className="divide-y divide-slate-100">
                      {mockPartners.map(partner => (
                         <tr key={partner.id} className="hover:bg-slate-50">
                         <td className="p-4 font-medium text-slate-900">{partner.companyName}</td>
                         <td className="p-4 text-slate-600 text-sm">{partner.type}</td>
                         <td className="p-4">
                           {partner.gdprSigned ? (
                              <span className="flex items-center gap-1.5 text-emerald-600 text-xs font-medium bg-emerald-50 px-2 py-1 rounded-full w-fit">
                                 <CheckCircle size={12} /> Signed (AVV)
                              </span>
                           ) : (
                              <span className="flex items-center gap-1.5 text-amber-600 text-xs font-medium bg-amber-50 px-2 py-1 rounded-full w-fit">
                                 <AlertTriangle size={12} /> Pending
                              </span>
                           )}
                         </td>
                         <td className="p-4 text-slate-600 text-sm">{partner.revenueShare}%</td>
                         <td className="p-4 text-right">
                           <span className={`
                              inline-block w-2 h-2 rounded-full mr-2
                              ${partner.status === 'Active' ? 'bg-emerald-500' : 'bg-slate-300'}
                           `}></span>
                           <span className="text-sm text-slate-600">{partner.status}</span>
                         </td>
                         </tr>
                      ))}
                   </tbody>
                   </table>
                </div>
             </div>
          )}

          {/* GDPR Tab */}
          {activeTab === 'gdpr' && (
             <div className="p-6 animate-fadeIn space-y-6">
                <div className="border-b border-slate-100 pb-4">
                   <h3 className="text-lg font-bold text-slate-800 flex items-center gap-2">
                      <Shield className="text-emerald-500" /> Compliance (Article 28)
                   </h3>
                </div>
                
                <div className="grid grid-cols-1 xl:grid-cols-2 gap-6">
                   <div className="space-y-4">
                      <div className="p-4 bg-slate-50 rounded-lg border border-slate-200">
                         <h4 className="font-bold text-slate-800 text-sm mb-2">Data Processing Agreement (AVV)</h4>
                         <p className="text-xs text-slate-500 mb-3">Your standard contract for partners.</p>
                         <button className="text-indigo-600 text-sm font-medium hover:underline flex items-center gap-1">
                            <FileText size={14} /> View Template
                         </button>
                      </div>
                      
                      <div className="p-4 bg-slate-50 rounded-lg border border-slate-200">
                         <div className="flex justify-between items-center mb-2">
                            <h4 className="font-bold text-slate-800 text-sm">Data Minimization</h4>
                            <div className="w-8 h-4 bg-emerald-500 rounded-full relative cursor-pointer">
                               <div className="absolute right-0.5 top-0.5 w-3 h-3 bg-white rounded-full"></div>
                            </div>
                         </div>
                         <p className="text-xs text-slate-500">Enforce strict field sharing. Partners only receive data necessary for service fulfillment.</p>
                      </div>
                   </div>

                   <div className="bg-white rounded-lg border border-slate-200">
                      <div className="p-3 bg-slate-50 border-b border-slate-200 text-sm font-bold text-slate-700">Audit Trail (Recent)</div>
                      <div className="divide-y divide-slate-100">
                         {[
                           { action: 'Data Shared', partner: 'Yoga Studio', time: '10:32 AM' },
                           { action: 'AVV Signed', partner: 'Fitness Co', time: 'Yesterday' },
                           { action: 'Revoked', partner: 'Wellness Apps', time: '2 days ago' },
                         ].map((log, i) => (
                           <div key={i} className="p-3 text-sm flex justify-between">
                              <div>
                                 <span className="font-medium text-slate-900">{log.action}</span>
                                 <span className="text-slate-400 mx-2">-</span>
                                 <span className="text-slate-600">{log.partner}</span>
                              </div>
                              <span className="text-slate-400 text-xs">{log.time}</span>
                           </div>
                         ))}
                      </div>
                   </div>
                </div>
             </div>
          )}

          {/* API Tab */}
          {activeTab === 'api' && (
             <div className="flex flex-col h-full animate-fadeIn">
                <div className="p-6 border-b border-slate-100 flex justify-between items-center">
                   <h3 className="font-bold text-lg text-slate-800">API & Developers</h3>
                   <button className="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700">
                      + New Key
                   </button>
                </div>
                <div className="p-6 space-y-8">
                   <div>
                      <h4 className="font-bold text-slate-700 text-sm mb-3">Active API Keys</h4>
                      <div className="border border-slate-200 rounded-lg overflow-hidden">
                         <table className="w-full text-left">
                            <thead className="bg-slate-50 text-xs uppercase text-slate-500 font-semibold border-b border-slate-200">
                               <tr>
                                  <th className="p-3">Name</th>
                                  <th className="p-3">Prefix</th>
                                  <th className="p-3">Last Used</th>
                               </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 text-sm">
                               {mockApiKeys.map(key => (
                                  <tr key={key.id}>
                                     <td className="p-3 font-medium">{key.name}</td>
                                     <td className="p-3 font-mono text-slate-500 bg-slate-50 w-fit px-2 rounded">{key.prefix}</td>
                                     <td className="p-3 text-slate-500">{key.lastUsed}</td>
                                  </tr>
                               ))}
                            </tbody>
                         </table>
                      </div>
                   </div>

                   <div>
                      <h4 className="font-bold text-slate-700 text-sm mb-3">Webhooks</h4>
                      <div className="p-4 border border-slate-200 rounded-lg bg-slate-50 flex items-center justify-between">
                         <div className="flex items-center gap-3">
                            <Webhook className="text-indigo-500" size={20} />
                            <span className="text-sm font-mono text-slate-700">https://api.yourservice.com/webhooks/bookando</span>
                         </div>
                         <span className="px-2 py-0.5 bg-emerald-100 text-emerald-700 text-xs rounded-full font-bold">Active</span>
                      </div>
                   </div>
                </div>
             </div>
          )}
       </div>
    </div>
  );
};

export default PartnerHubModule;