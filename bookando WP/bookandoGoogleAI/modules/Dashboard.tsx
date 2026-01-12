
import React, { useState, useMemo } from 'react';
import { 
  BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, AreaChart, Area 
} from 'recharts';
import { 
  ArrowUpRight, ArrowDownRight, Users, Banknote, CalendarCheck, Clock, 
  Settings, X, Plus, Move, GripVertical, ChevronUp, ChevronDown, Bell, Check, AlertTriangle
} from 'lucide-react';
import { useApp } from '../context/AppContext';

// --- Mock Data ---
const chartData = [
  { name: 'Mon', revenue: 4000, appointments: 24 },
  { name: 'Tue', revenue: 3000, appointments: 18 },
  { name: 'Wed', revenue: 2000, appointments: 32 },
  { name: 'Thu', revenue: 2780, appointments: 20 },
  { name: 'Fri', revenue: 1890, appointments: 28 },
  { name: 'Sat', revenue: 2390, appointments: 15 },
  { name: 'Sun', revenue: 3490, appointments: 22 },
];

// --- Widget Components ---

const StatWidget: React.FC<{ 
  title: string; 
  value: string; 
  change: string; 
  isPositive: boolean; 
  icon: any;
  colorClass?: string; 
}> = ({ title, value, change, isPositive, icon: Icon, colorClass = "text-slate-600" }) => {
  const { t } = useApp();
  return (
    <div className="h-full flex flex-col justify-between">
      <div className="flex justify-between items-start">
        <div>
          <p className="text-sm font-medium text-slate-500 mb-1">{title}</p>
          <h3 className="text-2xl font-bold text-slate-900">{value}</h3>
        </div>
        <div className={`p-2 rounded-lg bg-slate-50 ${colorClass}`}>
          <Icon size={20} />
        </div>
      </div>
      <div className="mt-4 flex items-center gap-2">
        <span className={`flex items-center text-xs font-semibold ${isPositive ? 'text-emerald-600' : 'text-rose-600'}`}>
          {isPositive ? <ArrowUpRight size={14} className="mr-1" /> : <ArrowDownRight size={14} className="mr-1" />}
          {change}
        </span>
        <span className="text-xs text-slate-400">{t('vs_last_month')}</span>
      </div>
    </div>
  );
};

const RevenueChartWidget = () => {
  const { t } = useApp();
  return (
    <div className="h-full flex flex-col">
      <h3 className="text-base font-semibold text-slate-800 mb-4">{t('revenue_analytics')}</h3>
      <div className="flex-1 min-h-[200px]">
        <ResponsiveContainer width="100%" height="100%">
          <AreaChart data={chartData} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
            <defs>
              <linearGradient id="colorRevenue" x1="0" y1="0" x2="0" y2="1">
                <stop offset="5%" stopColor="#0ea5e9" stopOpacity={0.1}/>
                <stop offset="95%" stopColor="#0ea5e9" stopOpacity={0}/>
              </linearGradient>
            </defs>
            <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e2e8f0" />
            <XAxis dataKey="name" axisLine={false} tickLine={false} tick={{fill: '#64748b', fontSize: 12}} dy={10} />
            <YAxis axisLine={false} tickLine={false} tick={{fill: '#64748b', fontSize: 12}} tickFormatter={(value) => `$${value}`} />
            <Tooltip 
              contentStyle={{ backgroundColor: '#fff', borderRadius: '8px', border: '1px solid #e2e8f0', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' }}
              itemStyle={{ color: '#0f172a', fontWeight: 600 }}
            />
            <Area type="monotone" dataKey="revenue" stroke="#0ea5e9" strokeWidth={2} fillOpacity={1} fill="url(#colorRevenue)" />
          </AreaChart>
        </ResponsiveContainer>
      </div>
    </div>
  );
};

const AppointmentChartWidget = () => {
  const { t } = useApp();
  return (
    <div className="h-full flex flex-col">
      <h3 className="text-base font-semibold text-slate-800 mb-4">{t('weekly_appointments')}</h3>
      <div className="flex-1 min-h-[200px]">
        <ResponsiveContainer width="100%" height="100%">
          <BarChart data={chartData} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
            <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e2e8f0" />
            <XAxis dataKey="name" axisLine={false} tickLine={false} tick={{fill: '#64748b', fontSize: 12}} dy={10} />
            <Tooltip 
                cursor={{fill: '#f1f5f9'}}
                contentStyle={{ backgroundColor: '#fff', borderRadius: '8px', border: '1px solid #e2e8f0' }}
            />
            <Bar dataKey="appointments" fill="#3b82f6" radius={[4, 4, 0, 0]} barSize={30} />
          </BarChart>
        </ResponsiveContainer>
      </div>
    </div>
  );
};

const ListWidget: React.FC<{ title: string; items: any[]; renderItem: (item: any) => React.ReactNode }> = ({ title, items, renderItem }) => (
  <div className="h-full flex flex-col">
    <h3 className="text-base font-semibold text-slate-800 mb-4">{title}</h3>
    <div className="flex-1 overflow-y-auto pr-2 space-y-3">
      {items.map((item, i) => (
        <div key={i} className="border-b border-slate-100 last:border-0 pb-3 last:pb-0">
          {renderItem(item)}
        </div>
      ))}
    </div>
  </div>
);

const InfocenterWidget = () => {
  const { t, alerts, acknowledgeAlert } = useApp();
  const activeAlerts = alerts.filter(a => !a.acknowledged);

  if (activeAlerts.length === 0) {
    return (
      <div className="h-full flex flex-col items-center justify-center text-center p-4">
        <div className="w-12 h-12 bg-emerald-50 rounded-full flex items-center justify-center mb-3">
          <Check size={24} className="text-emerald-500" />
        </div>
        <h3 className="text-base font-semibold text-slate-800">{t('all_clear')}</h3>
        <p className="text-xs text-slate-500">{t('no_alerts')}</p>
      </div>
    );
  }

  return (
    <div className="h-full flex flex-col">
      <div className="flex justify-between items-center mb-4">
        <h3 className="text-base font-semibold text-slate-800 flex items-center gap-2">
          <Bell size={16} className="text-rose-500" /> {t('infocenter')}
        </h3>
        <span className="text-xs font-bold bg-rose-100 text-rose-700 px-2 py-0.5 rounded-full">
          {activeAlerts.length} {t('new')}
        </span>
      </div>
      <div className="flex-1 overflow-y-auto pr-2 space-y-3">
        {activeAlerts.map(alert => (
          <div key={alert.id} className="p-3 bg-rose-50 border border-rose-100 rounded-lg">
            <div className="flex justify-between items-start mb-1">
              <div className="flex items-center gap-2 text-sm font-bold text-rose-800">
                <AlertTriangle size={14} /> {alert.title}
              </div>
              <button onClick={() => acknowledgeAlert(alert.id)} className="text-rose-400 hover:text-rose-700" title="Acknowledge">
                <Check size={14} />
              </button>
            </div>
            <p className="text-xs text-rose-700 mb-2 leading-relaxed">
              {alert.message}
            </p>
            <div className="text-[10px] text-rose-400 text-right">
              {alert.timestamp}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

// --- Dashboard Module ---

type WidgetSize = 'small' | 'medium' | 'large';

interface WidgetDef {
  id: string;
  title: string;
  component: React.FC<any>;
  props?: any;
  size: WidgetSize;
}

const DashboardModule: React.FC = () => {
  const { t, formatPrice } = useApp();
  const [widgetIds, setWidgetIds] = useState<string[]>([
    'infocenter',
    'stat-revenue', 
    'stat-customers', 
    'stat-appointments', 
    'stat-time', 
    'chart-revenue', 
    'list-activity'
  ]);

  const [isCustomizing, setIsCustomizing] = useState(false);

  // Memoize widgets to react to language changes
  const widgets = useMemo<Record<string, WidgetDef>>(() => {
    const recentActivity = [
      { id: 1, user: 'Alice Freeman', action: t('action_booked'), time: '2 mins ago' },
      { id: 2, user: 'Bob Smith', action: t('action_paid') + ' #INV-002', time: '1 hour ago' },
      { id: 3, user: 'New Partner', action: t('action_connection'), time: '3 hours ago' },
      { id: 4, user: 'System', action: t('action_backup'), time: '5 hours ago' },
    ];

    const upcomingAppointments = [
      { id: 1, client: 'Sarah Connor', time: '14:00 Today', type: 'Consultation' },
      { id: 2, client: 'John Wick', time: '16:30 Today', type: 'Emergency' },
      { id: 3, client: 'Ellen Ripley', time: '09:00 Tomorrow', type: 'Follow-up' },
    ];

    return {
        'infocenter': {
            id: 'infocenter',
            title: t('infocenter'),
            component: InfocenterWidget,
            size: 'medium'
        },
        'stat-revenue': {
            id: 'stat-revenue',
            title: t('total_revenue'),
            component: StatWidget,
            props: { title: t('total_revenue'), value: formatPrice(45231.89), change: "+20.1%", isPositive: true, icon: Banknote, colorClass: "text-emerald-600" },
            size: 'small'
        },
        'stat-customers': {
            id: 'stat-customers',
            title: t('active_customers'),
            component: StatWidget,
            props: { title: t('active_customers'), value: "2,345", change: "+15.3%", isPositive: true, icon: Users, colorClass: "text-blue-600" },
            size: 'small'
        },
        'stat-appointments': {
            id: 'stat-appointments',
            title: t('appointments'),
            component: StatWidget,
            props: { title: t('appointments'), value: "452", change: "-4.5%", isPositive: false, icon: CalendarCheck, colorClass: "text-violet-600" },
            size: 'small'
        },
        'stat-time': {
            id: 'stat-time',
            title: t('avg_session_time'),
            component: StatWidget,
            props: { title: t('avg_session_time'), value: "58m", change: "+1.2%", isPositive: true, icon: Clock, colorClass: "text-amber-600" },
            size: 'small'
        },
        'chart-revenue': {
            id: 'chart-revenue',
            title: t('revenue_analytics'),
            component: RevenueChartWidget,
            size: 'large'
        },
        'chart-appointments': {
            id: 'chart-appointments',
            title: t('weekly_appointments'),
            component: AppointmentChartWidget,
            size: 'medium'
        },
        'list-activity': {
            id: 'list-activity',
            title: t('recent_activity'),
            component: ListWidget,
            props: {
            title: t('recent_activity'),
            items: recentActivity,
            renderItem: (item: any) => (
                <div className="text-sm">
                <p className="font-medium text-slate-800">{item.user}</p>
                <p className="text-slate-500 text-xs">{item.action}</p>
                <p className="text-slate-400 text-[10px] mt-1">{item.time}</p>
                </div>
            )
            },
            size: 'medium'
        },
        'list-upcoming': {
            id: 'list-upcoming',
            title: t('next_up'),
            component: ListWidget,
            props: {
            title: t('next_up'),
            items: upcomingAppointments,
            renderItem: (item: any) => (
                <div className="flex justify-between items-center text-sm">
                <div>
                    <p className="font-medium text-slate-800">{item.client}</p>
                    <p className="text-slate-500 text-xs">{item.type}</p>
                </div>
                <div className="text-right bg-slate-100 px-2 py-1 rounded text-xs font-medium text-slate-600">
                    {item.time}
                </div>
                </div>
            )
            },
            size: 'medium'
        }
    };
  }, [t, formatPrice]);

  const toggleWidget = (id: string) => {
    if (widgetIds.includes(id)) {
      setWidgetIds(widgetIds.filter(w => w !== id));
    } else {
      setWidgetIds([...widgetIds, id]);
    }
  };

  const moveWidget = (index: number, direction: 'up' | 'down') => {
    const newWidgets = [...widgetIds];
    if (direction === 'up' && index > 0) {
      [newWidgets[index], newWidgets[index - 1]] = [newWidgets[index - 1], newWidgets[index]];
    } else if (direction === 'down' && index < newWidgets.length - 1) {
      [newWidgets[index], newWidgets[index + 1]] = [newWidgets[index + 1], newWidgets[index]];
    }
    setWidgetIds(newWidgets);
  };

  const getGridSpanClass = (size: WidgetSize) => {
    switch (size) {
      case 'large': return 'col-span-1 md:col-span-2 lg:col-span-2 xl:col-span-3';
      case 'medium': return 'col-span-1 md:col-span-1 lg:col-span-1';
      case 'small': return 'col-span-1';
      default: return 'col-span-1';
    }
  };

  return (
    <div className="space-y-6 relative">
      {/* Header */}
      <div className="flex justify-between items-end">
        <div>
          <h2 className="text-lg font-semibold text-slate-800">{t('overview')}</h2>
          <p className="text-sm text-slate-500">{t('overview_subtitle')}</p>
        </div>
        <div className="flex gap-2">
           <button 
             onClick={() => setIsCustomizing(!isCustomizing)}
             className={`
               flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors
               ${isCustomizing ? 'bg-indigo-600 text-white' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50'}
             `}
           >
             {isCustomizing ? <X size={16} /> : <Settings size={16} />}
             {isCustomizing ? t('done') : t('customize')}
           </button>
        </div>
      </div>

      {/* Customization Drawer */}
      {isCustomizing && (
        <div className="bg-white p-4 rounded-xl border border-indigo-100 shadow-lg mb-6 animate-fadeIn">
          <h3 className="font-semibold text-slate-800 mb-3">{t('manage_widgets')}</h3>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            {(Object.values(widgets) as WidgetDef[]).map((widget) => {
              const isActive = widgetIds.includes(widget.id);
              const index = widgetIds.indexOf(widget.id);
              return (
                <div key={widget.id} className={`
                  flex items-center justify-between p-3 rounded-lg border transition-all
                  ${isActive ? 'bg-indigo-50 border-indigo-200' : 'bg-slate-50 border-slate-200 opacity-70 hover:opacity-100'}
                `}>
                  <div className="flex items-center gap-3">
                    <button 
                      onClick={() => toggleWidget(widget.id)}
                      className={`
                        w-5 h-5 rounded border flex items-center justify-center transition-colors
                        ${isActive ? 'bg-indigo-600 border-indigo-600' : 'bg-white border-slate-300'}
                      `}
                    >
                      {isActive && <Plus size={12} className="text-white transform rotate-45" />}
                    </button>
                    <span className={`text-sm font-medium ${isActive ? 'text-indigo-900' : 'text-slate-600'}`}>
                      {widget.title}
                    </span>
                  </div>
                  
                  {isActive && (
                    <div className="flex items-center gap-1">
                      <button 
                        disabled={index === 0}
                        onClick={() => moveWidget(index, 'up')}
                        className="p-1 hover:bg-indigo-200 rounded text-indigo-600 disabled:opacity-30"
                      >
                        <ChevronUp size={16} />
                      </button>
                      <button 
                        disabled={index === widgetIds.length - 1}
                        onClick={() => moveWidget(index, 'down')}
                        className="p-1 hover:bg-indigo-200 rounded text-indigo-600 disabled:opacity-30"
                      >
                        <ChevronDown size={16} />
                      </button>
                    </div>
                  )}
                </div>
              );
            })}
          </div>
          <p className="text-xs text-slate-500 mt-3">{t('reorder_hint')}</p>
        </div>
      )}

      {/* Widget Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        {widgetIds.map((id) => {
          const widget = widgets[id];
          if (!widget) return null;
          
          const WidgetComponent = widget.component;
          
          return (
            <div key={id} className={`
              bg-white p-6 rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-all duration-300
              ${getGridSpanClass(widget.size)}
              animate-fadeIn
            `}>
              <WidgetComponent {...widget.props} />
            </div>
          );
        })}
        
        {widgetIds.length === 0 && (
          <div className="col-span-full py-12 text-center text-slate-400 bg-slate-50 rounded-xl border border-dashed border-slate-300">
            <Settings className="mx-auto mb-3 opacity-50" size={32} />
            <p>{t('no_widgets')}</p>
          </div>
        )}
      </div>
    </div>
  );
};

export default DashboardModule;
