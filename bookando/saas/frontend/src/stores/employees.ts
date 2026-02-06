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
// AVAILABLE SERVICES (mock)
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
  { id: 'svc-008', name: 'Maniküre', category: 'Kosmetik' },
  { id: 'svc-009', name: 'Pediküre', category: 'Kosmetik' },
  { id: 'svc-010', name: 'Massage Klassisch', category: 'Wellness' },
  { id: 'svc-011', name: 'Hot-Stone Massage', category: 'Wellness' },
  { id: 'svc-012', name: 'Haarverlängerung', category: 'Haar' },
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
// MOCK DATA
// ============================================================================

const MOCK_EMPLOYEES: Employee[] = [
  {
    id: 'emp-001',
    firstName: 'Lisa',
    lastName: 'Weber',
    email: 'lisa@beispiel.ch',
    phone: '+41 79 111 22 33',
    position: 'Senior Friseurin',
    department: 'Haarstyling',
    status: 'ACTIVE',
    role: 'EMPLOYEE',
    hireDate: '2021-03-15',
    avatar: '',
    bio: 'Spezialisiert auf Balayage und moderne Schnitte.',
    street: 'Bahnhofstrasse 12',
    zip: '8001',
    city: 'Zürich',
    country: 'CH',
    salaryMinor: 580000,
    vacationDaysTotal: 25,
    vacationDaysUsed: 8,
    employmentPercent: 100,
    socialSecurityNumber: '756.1234.5678.90',
    assignedServiceIds: ['svc-001', 'svc-003', 'svc-004', 'svc-012'],
    createdAt: '2021-03-15T10:00:00Z',
    updatedAt: '2025-11-20T14:30:00Z',
  },
  {
    id: 'emp-002',
    firstName: 'Marco',
    lastName: 'Bianchi',
    email: 'marco@beispiel.ch',
    phone: '+41 79 222 33 44',
    position: 'Barbier',
    department: 'Barbershop',
    status: 'ACTIVE',
    role: 'EMPLOYEE',
    hireDate: '2022-06-01',
    avatar: '',
    bio: 'Traditionelle und moderne Barber-Techniken.',
    street: 'Langstrasse 45',
    zip: '8004',
    city: 'Zürich',
    country: 'CH',
    salaryMinor: 520000,
    vacationDaysTotal: 25,
    vacationDaysUsed: 12,
    employmentPercent: 100,
    socialSecurityNumber: '756.2345.6789.01',
    assignedServiceIds: ['svc-002', 'svc-005', 'svc-006'],
    createdAt: '2022-06-01T08:00:00Z',
    updatedAt: '2025-10-15T09:00:00Z',
  },
  {
    id: 'emp-003',
    firstName: 'Sarah',
    lastName: 'Keller',
    email: 'sarah@beispiel.ch',
    phone: '+41 79 333 44 55',
    position: 'Kosmetikerin',
    department: 'Kosmetik',
    status: 'VACATION',
    role: 'EMPLOYEE',
    hireDate: '2020-01-10',
    avatar: '',
    bio: 'Expertin fuer Gesichtsbehandlungen und Hautpflege.',
    street: 'Seestrasse 78',
    zip: '8002',
    city: 'Zürich',
    country: 'CH',
    salaryMinor: 550000,
    vacationDaysTotal: 25,
    vacationDaysUsed: 18,
    employmentPercent: 80,
    socialSecurityNumber: '756.3456.7890.12',
    assignedServiceIds: ['svc-007', 'svc-008', 'svc-009'],
    createdAt: '2020-01-10T09:00:00Z',
    updatedAt: '2025-12-01T11:00:00Z',
  },
  {
    id: 'emp-004',
    firstName: 'Thomas',
    lastName: 'Brunner',
    email: 'thomas@beispiel.ch',
    phone: '+41 79 444 55 66',
    position: 'Masseur',
    department: 'Wellness',
    status: 'PAUSE',
    role: 'EMPLOYEE',
    hireDate: '2023-09-01',
    avatar: '',
    bio: 'Klassische Massage und Hot-Stone-Therapie.',
    street: 'Limmatquai 22',
    zip: '8001',
    city: 'Zürich',
    country: 'CH',
    salaryMinor: 480000,
    vacationDaysTotal: 25,
    vacationDaysUsed: 5,
    employmentPercent: 60,
    socialSecurityNumber: '756.4567.8901.23',
    assignedServiceIds: ['svc-010', 'svc-011'],
    createdAt: '2023-09-01T08:00:00Z',
    updatedAt: '2025-11-10T16:00:00Z',
  },
  {
    id: 'emp-005',
    firstName: 'Anna',
    lastName: 'Meier',
    email: 'anna@beispiel.ch',
    phone: '+41 79 555 66 77',
    position: 'Salon-Managerin',
    department: 'Management',
    status: 'ACTIVE',
    role: 'MANAGER',
    hireDate: '2019-04-01',
    avatar: '',
    bio: 'Leitung des Teams und strategische Planung.',
    street: 'Rämistrasse 5',
    zip: '8001',
    city: 'Zürich',
    country: 'CH',
    salaryMinor: 720000,
    vacationDaysTotal: 28,
    vacationDaysUsed: 10,
    employmentPercent: 100,
    socialSecurityNumber: '756.5678.9012.34',
    assignedServiceIds: ['svc-001', 'svc-002', 'svc-003'],
    createdAt: '2019-04-01T08:00:00Z',
    updatedAt: '2025-12-05T10:00:00Z',
  },
  {
    id: 'emp-006',
    firstName: 'Julia',
    lastName: 'Schmidt',
    email: 'julia@beispiel.ch',
    phone: '+41 79 666 77 88',
    position: 'Lernende Friseurin',
    department: 'Haarstyling',
    status: 'ACTIVE',
    role: 'TRAINEE',
    hireDate: '2025-08-01',
    avatar: '',
    bio: 'Im 1. Lehrjahr, lernt grundlegende Schnitttechniken.',
    street: 'Birmensdorferstrasse 99',
    zip: '8003',
    city: 'Zürich',
    country: 'CH',
    salaryMinor: 120000,
    vacationDaysTotal: 25,
    vacationDaysUsed: 2,
    employmentPercent: 100,
    socialSecurityNumber: '756.6789.0123.45',
    assignedServiceIds: ['svc-001', 'svc-002'],
    createdAt: '2025-08-01T08:00:00Z',
    updatedAt: '2025-12-01T08:00:00Z',
  },
  {
    id: 'emp-007',
    firstName: 'Peter',
    lastName: 'Huber',
    email: 'peter@beispiel.ch',
    phone: '+41 79 777 88 99',
    position: 'Empfangsmitarbeiter',
    department: 'Empfang',
    status: 'SICK_LEAVE',
    role: 'EMPLOYEE',
    hireDate: '2024-02-15',
    avatar: '',
    bio: 'Kundenempfang und Terminmanagement.',
    street: 'Hardstrasse 33',
    zip: '8005',
    city: 'Zürich',
    country: 'CH',
    salaryMinor: 450000,
    vacationDaysTotal: 25,
    vacationDaysUsed: 3,
    employmentPercent: 100,
    socialSecurityNumber: '756.7890.1234.56',
    assignedServiceIds: [],
    createdAt: '2024-02-15T08:00:00Z',
    updatedAt: '2025-12-03T09:00:00Z',
  },
  {
    id: 'emp-008',
    firstName: 'Elena',
    lastName: 'Rossi',
    email: 'elena@beispiel.ch',
    phone: '+41 79 888 99 00',
    position: 'Friseurin',
    department: 'Haarstyling',
    status: 'TERMINATED',
    role: 'EMPLOYEE',
    hireDate: '2020-06-01',
    exitDate: '2025-09-30',
    avatar: '',
    bio: '',
    street: 'Zähringerstrasse 11',
    zip: '8001',
    city: 'Zürich',
    country: 'CH',
    salaryMinor: 540000,
    vacationDaysTotal: 25,
    vacationDaysUsed: 25,
    employmentPercent: 100,
    socialSecurityNumber: '756.8901.2345.67',
    assignedServiceIds: ['svc-001', 'svc-003'],
    createdAt: '2020-06-01T08:00:00Z',
    updatedAt: '2025-09-30T17:00:00Z',
  },
];

// ============================================================================
// STORE
// ============================================================================

export const useEmployeesStore = defineStore('employees', () => {
  // State
  const employees = ref<Employee[]>([...MOCK_EMPLOYEES]);
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
  function getEmployeeById(id: string): Employee | undefined {
    return employees.value.find(e => e.id === id);
  }

  function createEmployee(data: EmployeeFormData): Employee {
    const now = new Date().toISOString();
    const newEmployee: Employee = {
      ...data,
      id: `emp-${String(employees.value.length + 1).padStart(3, '0')}`,
      createdAt: now,
      updatedAt: now,
    };
    employees.value.push(newEmployee);
    return newEmployee;
  }

  function updateEmployee(id: string, data: Partial<EmployeeFormData>): Employee | null {
    const index = employees.value.findIndex(e => e.id === id);
    if (index === -1) return null;

    const updated: Employee = {
      ...employees.value[index],
      ...data,
      updatedAt: new Date().toISOString(),
    };
    employees.value[index] = updated;
    return updated;
  }

  function deleteEmployee(id: string): boolean {
    const index = employees.value.findIndex(e => e.id === id);
    if (index === -1) return false;
    employees.value.splice(index, 1);
    return true;
  }

  function setStatus(id: string, status: EmployeeStatus): boolean {
    const employee = employees.value.find(e => e.id === id);
    if (!employee) return false;
    employee.status = status;
    employee.updatedAt = new Date().toISOString();
    if (status === 'TERMINATED' && !employee.exitDate) {
      employee.exitDate = new Date().toISOString().split('T')[0];
    }
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
