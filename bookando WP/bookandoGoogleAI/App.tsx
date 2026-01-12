import React, { useState, useEffect } from 'react';
import { 
  LayoutDashboard, 
  Users, 
  Briefcase, 
  Calendar, 
  Banknote, 
  Tag, 
  GraduationCap, 
  Box, 
  Clock, 
  Network, 
  Wrench, 
  Settings as SettingsIcon 
} from 'lucide-react';
import { NavItem, ModuleName } from './types';
import Sidebar from './components/Sidebar';
import Header from './components/Header';
import { AppProvider } from './context/AppContext';

// Module Imports
import DashboardModule from './modules/Dashboard';
import CustomersModule from './modules/Customers';
import EmployeesModule from './modules/Employees';
import FinanceModule from './modules/Finance';
import PartnerHubModule from './modules/PartnerHub';
import WorkdayModule from './modules/Workday';
import OffersModule from './modules/Offers';
import AcademyModule from './modules/Academy';
import ResourcesModule from './modules/Resources';
import ToolsModule from './modules/Tools';
import SettingsModule from './modules/Settings';

const AppContent: React.FC = () => {
  const [activeModule, setActiveModule] = useState<ModuleName>(ModuleName.DASHBOARD);
  // Initialize sidebar: Open on Desktop, Closed on Mobile by default
  const [isSidebarOpen, setIsSidebarOpen] = useState(() => window.innerWidth >= 768);

  const navItems: NavItem[] = [
    { name: ModuleName.DASHBOARD, icon: LayoutDashboard },
    { name: ModuleName.CUSTOMERS, icon: Users },
    { name: ModuleName.EMPLOYEES, icon: Briefcase },
    { name: ModuleName.WORKDAY, icon: Clock, badge: 3 }, // Workday now holds appointments
    { name: ModuleName.FINANCE, icon: Banknote },
    { name: ModuleName.OFFERS, icon: Tag },
    { name: ModuleName.ACADEMY, icon: GraduationCap },
    { name: ModuleName.RESOURCES, icon: Box },
    { name: ModuleName.PARTNER_HUB, icon: Network, badge: 1 },
    { name: ModuleName.TOOLS, icon: Wrench },
    { name: ModuleName.SETTINGS, icon: SettingsIcon },
  ];

  const renderModule = () => {
    switch (activeModule) {
      case ModuleName.DASHBOARD:
        return <DashboardModule />;
      case ModuleName.CUSTOMERS:
        return <CustomersModule />;
      case ModuleName.EMPLOYEES:
        return <EmployeesModule />;
      case ModuleName.FINANCE:
        return <FinanceModule />;
      case ModuleName.PARTNER_HUB:
        return <PartnerHubModule />;
      case ModuleName.WORKDAY:
        return <WorkdayModule />;
      case ModuleName.OFFERS:
        return <OffersModule />;
      case ModuleName.ACADEMY:
        return <AcademyModule />;
      case ModuleName.RESOURCES:
        return <ResourcesModule />;
      case ModuleName.TOOLS:
        return <ToolsModule />;
      case ModuleName.SETTINGS:
        return <SettingsModule />;
      default:
        return (
          <div className="flex flex-col items-center justify-center h-[70vh] text-slate-400">
            <Wrench className="w-16 h-16 mb-4 opacity-20" />
            <h2 className="text-2xl font-semibold mb-2">Module Under Construction</h2>
            <p className="max-w-md text-center">The {activeModule} module is currently being initialized. Please check back later.</p>
          </div>
        );
    }
  };

  return (
    <div className="flex flex-col md:flex-row h-screen bg-slate-50 overflow-hidden">
      {/* Sidebar - Top on Mobile, Left on Desktop */}
      <Sidebar 
        navItems={navItems} 
        activeModule={activeModule} 
        onNavigate={setActiveModule} 
        isOpen={isSidebarOpen}
        toggleSidebar={() => setIsSidebarOpen(!isSidebarOpen)}
      />

      {/* Main Content Area */}
      <div className="flex-1 flex flex-col min-w-0 overflow-hidden relative z-0 transition-all duration-300">
        <Header title={activeModule} />
        
        <main className="flex-1 overflow-y-auto p-4 md:p-8 scroll-smooth">
          <div className="max-w-7xl mx-auto animate-fadeIn">
             {renderModule()}
          </div>
        </main>
      </div>
    </div>
  );
};

const App: React.FC = () => {
  return (
    <AppProvider>
      <AppContent />
    </AppProvider>
  );
};

export default App;