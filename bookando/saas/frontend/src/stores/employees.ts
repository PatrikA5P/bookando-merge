/**
 * Employees Store — Mitarbeiter-Verwaltung
 *
 * Zentraler Pinia Store fuer alle Mitarbeiter-Operationen:
 * - CRUD (Create, Read, Update, Delete)
 * - Such- und Filter-Funktionalitaet
 * - Status-Management
 * - Service-Zuordnung
 *
 * Geld-Betraege in Minor Units (Rappen/Cents).
 */
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/utils/api';

// ============================================================================
// TYPES
// ============================================================================

export type EmployeeStatus = 'ACTIVE' | 'VACATION' | 'SICK_LEAVE' | 'PAUSE' | 'TERMINATED';

export type EmployeeRole = 'ADMIN' | 'MANAGER' | 'EMPLOYEE' | 'TRAINEE';

export interface Employee {
  id: string;
  firstName: string;
  lastName: string;
  email: string;
  phone: string;
  position: string;
  department: string;
  status: EmployeeStatus;
  role: EmployeeRole;
  hireDate: string;
  exitDate?: string;
  avatar?: string;
  bio?: string;

  // Address
  street: string;
  zip: string;
  city: string;
  country: string;

  // HR Data
  /** Monthly salary in minor units (Rappen/Cents) */
  salaryMinor: number;
  /** Total vacation days per year */
  vacationDaysTotal: number;
  /** Vacation days already used */
  vacationDaysUsed: number;
  /** Employment percentage (e.g. 80 = 80%) */
  employmentPercent: number;
  /** Social security number (AHV-Nr.) */
  socialSecurityNumber?: string;

  // Services
  assignedServiceIds: string[];

  // Timestamps
  createdAt: string;
  updatedAt: string;
}

export type EmployeeFormData = Omit<Employee, 'id' | 'createdAt' | 'updatedAt'>;

export interface EmployeeFilters {
  search: string;
  status: EmployeeStatus | '';
  department: string;
  role: EmployeeRole | '';
}

// ============================================================================
// AVAILABLE SERVICES (local reference — can be fetched later)
// ============================================================================

export interface AvailableService {
  id: string;
  name: string;
  category: string;
}

export const AVAILABLE_SERVICES: AvailableService[] = [
  { id: 'svc-001', name: 'Haarschnitt Damen', category: 'Haar' },
  { id: 'svc-002', name: 'Haarschnitt Herren', category: 'Haar' },
  { id: 'svc-003', name: 'Faerben', category: 'Haar' },
  { id: 'svc-004', name: 'Straehnen', category: 'Haar' },
  { id: 'svc-005', name: 'Bartpflege', category: 'Bart' },
  { id: 'svc-006', name: 'Rasur', category: 'Bart' },
  { id: 'svc-007', name: 'Gesichtsbehandlung', category: 'Kosmetik' },
  { id: 'svc-008', name: 'Manikuere', category: 'Kosmetik' },
  { id: 'svc-009', name: 'Pedikuere', category: 'Kosmetik' },
  { id: 'svc-010', name: 'Massage Klassisch', category: 'Wellness' },
  { id: 'svc-011', name: 'Hot-Stone Massage', category: 'Wellness' },
  { id: 'svc-012', name: 'Haarverlaengerung', category: 'Haar' },
];

// ============================================================================
// DEPARTMENTS
// ============================================================================

export const DEPARTMENTS = [
  'Haarstyling',
  'Barbershop',
  'Kosmetik',
  'Wellness',
  'Empfang',
  'Management',
] as const;

// ============================================================================
// STORE
// ============================================================================

export const useEmployeesStore = defineStore('employees', () => {
  // State
  const employees = ref<Employee[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);
  const filters = ref<EmployeeFilters>({
    search: '',
    status: '',
    department: '',
    role: '',
  });

  // Getters
  const filteredEmployees = computed(() => {
    let result = employees.value;

    // Search filter
    if (filters.value.search) {
      const q = filters.value.search.toLowerCase();
      result = result.filter(e =>
        `${e.firstName} ${e.lastName}`.toLowerCase().includes(q) ||
        e.email.toLowerCase().includes(q) ||
        e.position.toLowerCase().includes(q) ||
        e.department.toLowerCase().includes(q)
      );
    }

    // Status filter
    if (filters.value.status) {
      result = result.filter(e => e.status === filters.value.status);
    }

    // Department filter
    if (filters.value.department) {
      result = result.filter(e => e.department === filters.value.department);
    }

    // Role filter
    if (filters.value.role) {
      result = result.filter(e => e.role === filters.value.role);
    }

    return result;
  });

  const activeEmployees = computed(() =>
    employees.value.filter(e => e.status === 'ACTIVE')
  );

  const employeeCount = computed(() => employees.value.length);

  const activeCount = computed(() => activeEmployees.value.length);

  const departmentCounts = computed(() => {
    const counts: Record<string, number> = {};
    for (const emp of employees.value) {
      if (emp.status !== 'TERMINATED') {
        counts[emp.department] = (counts[emp.department] || 0) + 1;
      }
    }
    return counts;
  });

  const statusCounts = computed(() => {
    const counts: Record<EmployeeStatus, number> = {
      ACTIVE: 0,
      VACATION: 0,
      SICK_LEAVE: 0,
      PAUSE: 0,
      TERMINATED: 0,
    };
    for (const emp of employees.value) {
      counts[emp.status]++;
    }
    return counts;
  });

  // Actions

  async function fetchEmployees(): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
      const response = await api.get<{ data: Employee[] }>('/v1/employees', { per_page: 100 });
      employees.value = response.data;
    } catch (e: any) {
      error.value = e.message || 'Failed to load employees';
    } finally {
      loading.value = false;
    }
  }

  function getEmployeeById(id: string): Employee | undefined {
    return employees.value.find(e => e.id === id);
  }

  async function createEmployee(data: EmployeeFormData): Promise<Employee> {
    const response = await api.post<{ data: Employee }>('/v1/employees', data);
    employees.value.push(response.data);
    return response.data;
  }

  async function updateEmployee(id: string, data: Partial<EmployeeFormData>): Promise<Employee | null> {
    const response = await api.put<{ data: Employee }>(`/v1/employees/${id}`, data);
    const index = employees.value.findIndex(e => e.id === id);
    if (index !== -1) {
      employees.value[index] = response.data;
    }
    return response.data;
  }

  async function deleteEmployee(id: string): Promise<boolean> {
    await api.delete(`/v1/employees/${id}`);
    const index = employees.value.findIndex(e => e.id === id);
    if (index === -1) return false;
    employees.value.splice(index, 1);
    return true;
  }

  async function setStatus(id: string, status: EmployeeStatus): Promise<boolean> {
    const response = await api.patch<{ data: Employee }>(`/v1/employees/${id}/status`, { status });
    const employee = employees.value.find(e => e.id === id);
    if (!employee) return false;
    Object.assign(employee, response.data);
    return true;
  }

  function toggleService(employeeId: string, serviceId: string): boolean {
    const employee = employees.value.find(e => e.id === employeeId);
    if (!employee) return false;
    const idx = employee.assignedServiceIds.indexOf(serviceId);
    if (idx >= 0) {
      employee.assignedServiceIds.splice(idx, 1);
    } else {
      employee.assignedServiceIds.push(serviceId);
    }
    employee.updatedAt = new Date().toISOString();
    return true;
  }

  function setFilters(newFilters: Partial<EmployeeFilters>) {
    filters.value = { ...filters.value, ...newFilters };
  }

  function resetFilters() {
    filters.value = { search: '', status: '', department: '', role: '' };
  }

  return {
    // State
    employees,
    loading,
    error,
    filters,

    // Getters
    filteredEmployees,
    activeEmployees,
    employeeCount,
    activeCount,
    departmentCounts,
    statusCounts,

    // Actions
    fetchEmployees,
    getEmployeeById,
    createEmployee,
    updateEmployee,
    deleteEmployee,
    setStatus,
    toggleService,
    setFilters,
    resetFilters,
  };
});
