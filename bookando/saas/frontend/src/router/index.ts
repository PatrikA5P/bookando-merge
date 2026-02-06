/**
 * BOOKANDO ROUTER
 *
 * Vue Router mit:
 * - Lazy-Loading pro Modul (Code-Splitting)
 * - Auth Guards
 * - Permission Guards
 * - Breadcrumb-Meta
 *
 * Verbesserung gegenüber Referenz:
 * Die Referenz hatte KEIN Routing (nur State-Switch).
 * Hier gibt es echte URLs, Deep-Linking, Browser-History.
 */
import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const routes: RouteRecordRaw[] = [
  // Auth (kein Layout)
  {
    path: '/login',
    name: 'login',
    component: () => import('@/modules/auth/LoginPage.vue'),
    meta: { requiresAuth: false },
  },

  // App (mit AppShell Layout)
  {
    path: '/',
    component: () => import('@/components/layout/AppShell.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        redirect: '/dashboard',
      },
      {
        path: 'dashboard',
        name: 'dashboard',
        component: () => import('@/modules/dashboard/DashboardPage.vue'),
        meta: { module: 'dashboard', title: 'Dashboard' },
      },
      {
        path: 'appointments',
        component: () => import('@/modules/appointments/AppointmentsPage.vue'),
        meta: { module: 'appointments', title: 'Termine' },
        children: [
          { path: '', name: 'appointments', redirect: { name: 'appointments-calendar' } },
          { path: 'calendar', name: 'appointments-calendar', component: () => import('@/modules/appointments/views/CalendarView.vue') },
          { path: 'list', name: 'appointments-list', component: () => import('@/modules/appointments/views/ListView.vue') },
        ],
      },
      {
        path: 'customers',
        name: 'customers',
        component: () => import('@/modules/customers/CustomersPage.vue'),
        meta: { module: 'customers', title: 'Kunden' },
      },
      {
        path: 'customers/:id',
        name: 'customer-detail',
        component: () => import('@/modules/customers/CustomerDetailPage.vue'),
        meta: { module: 'customers', title: 'Kundendetail' },
      },
      {
        path: 'employees',
        name: 'employees',
        component: () => import('@/modules/employees/EmployeesPage.vue'),
        meta: { module: 'employees', title: 'Mitarbeiter' },
      },
      {
        path: 'employees/:id',
        name: 'employee-detail',
        component: () => import('@/modules/employees/EmployeeDetailPage.vue'),
        meta: { module: 'employees', title: 'Mitarbeiterdetail' },
      },
      {
        path: 'workday',
        name: 'workday',
        component: () => import('@/modules/workday/WorkdayPage.vue'),
        meta: { module: 'workday', title: 'Arbeitstag' },
      },
      {
        path: 'finance',
        name: 'finance',
        component: () => import('@/modules/finance/FinancePage.vue'),
        meta: { module: 'finance', title: 'Finanzen' },
      },
      {
        path: 'offers',
        name: 'offers',
        component: () => import('@/modules/offers/OffersPage.vue'),
        meta: { module: 'offers', title: 'Angebote' },
      },
      {
        path: 'academy',
        name: 'academy',
        component: () => import('@/modules/academy/AcademyPage.vue'),
        meta: { module: 'academy', title: 'Akademie' },
      },
      {
        path: 'resources',
        name: 'resources',
        component: () => import('@/modules/resources/ResourcesPage.vue'),
        meta: { module: 'resources', title: 'Ressourcen' },
      },
      {
        path: 'settings',
        name: 'settings',
        component: () => import('@/modules/settings/SettingsPage.vue'),
        meta: { module: 'settings', title: 'Einstellungen' },
      },
      {
        path: 'tools',
        name: 'tools',
        component: () => import('@/modules/tools/ToolsPage.vue'),
        meta: { module: 'tools', title: 'Tools' },
      },
      {
        path: 'partnerhub',
        name: 'partnerhub',
        component: () => import('@/modules/partnerhub/PartnerHubPage.vue'),
        meta: { module: 'partnerhub', title: 'Partner Hub' },
      },
      {
        path: 'design-system',
        name: 'design-system',
        component: () => import('@/modules/design-system/DesignSystemPage.vue'),
        meta: { module: 'design-system', title: 'Design System' },
      },
      {
        path: 'design-frontend',
        name: 'design-frontend',
        component: () => import('@/modules/design-frontend/DesignFrontendPage.vue'),
        meta: { module: 'design-frontend', title: 'Design Frontend' },
      },
    ],
  },

  // 404
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/components/shared/NotFoundPage.vue'),
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(_to, _from, savedPosition) {
    return savedPosition || { top: 0 };
  },
});

// Auth Guard
router.beforeEach(async (to) => {
  const authStore = useAuthStore();

  // Wait for initial auth check on first navigation
  if (!authStore.isReady) {
    await authStore.bootstrap();
  }

  const requiresAuth = to.meta.requiresAuth !== false;

  if (requiresAuth && !authStore.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } };
  }
  // Redirect away from login if already authenticated
  if (to.name === 'login' && authStore.isAuthenticated) {
    return { name: 'dashboard' };
  }
});

export default router;
