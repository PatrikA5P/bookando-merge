/**
 * Appointments Store — Terminverwaltung
 *
 * Pinia Store fuer:
 * - CRUD-Operationen fuer Termine
 * - Filter nach Datum, Mitarbeiter, Status, Kunde
 * - Computed: todayAppointments, upcomingAppointments, getByDate
 *
 * Verbesserung gegenueber Referenz:
 * - Pinia statt monolithischem Context
 * - Typsichere Interfaces
 * - Betraege in Minor Units (Rappen)
 */
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/utils/api';

export type AppointmentStatus = 'PENDING' | 'CONFIRMED' | 'COMPLETED' | 'CANCELLED' | 'NO_SHOW';

export interface Appointment {
  id: string;
  customerId: string;
  customerName: string;
  employeeId: string;
  employeeName: string;
  serviceId: string;
  serviceName: string;
  date: string; // YYYY-MM-DD
  startTime: string; // HH:mm
  endTime: string; // HH:mm
  duration: number; // Minuten
  status: AppointmentStatus;
  priceMinor: number; // Rappen/Cents
  currency: string;
  notes: string;
  locationId: string;
  roomId: string;
}

export interface AppointmentFilters {
  dateFrom: string;
  dateTo: string;
  employeeId: string;
  status: AppointmentStatus | '';
  customerId: string;
  search: string;
}

export interface Service {
  id: string;
  name: string;
  duration: number;
  priceMinor: number;
  category: string;
  description: string;
}

export interface Employee {
  id: string;
  name: string;
  position: string;
  avatar: string;
  serviceIds: string[];
}

export interface Customer {
  id: string;
  name: string;
  email: string;
  phone: string;
}

function todayStr(): string {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

export const useAppointmentsStore = defineStore('appointments', () => {
  // State
  const appointments = ref<Appointment[]>([]);
  const services = ref<Service[]>([]);
  const employees = ref<Employee[]>([]);
  const customers = ref<Customer[]>([]);
  const isLoading = ref(false);
  const error = ref<string | null>(null);
  const filters = ref<AppointmentFilters>({
    dateFrom: '',
    dateTo: '',
    employeeId: '',
    status: '',
    customerId: '',
    search: '',
  });

  // Fetch functions

  async function fetchAppointments(): Promise<void> {
    isLoading.value = true;
    error.value = null;
    try {
      const response = await api.get<{ data: Appointment[] }>('/v1/appointments', { per_page: 100 });
      appointments.value = response.data;
    } catch (e: any) {
      error.value = e.message || 'Failed to load appointments';
    } finally {
      isLoading.value = false;
    }
  }

  async function fetchServices(): Promise<void> {
    try {
      const response = await api.get<{ data: Service[] }>('/v1/services', { per_page: 100 });
      services.value = response.data;
    } catch {
      // Services are supplementary; keep existing data on failure
    }
  }

  async function fetchEmployees(): Promise<void> {
    try {
      const response = await api.get<{ data: Employee[] }>('/v1/employees', { per_page: 100 });
      employees.value = response.data;
    } catch {
      // Employees are supplementary; keep existing data on failure
    }
  }

  async function fetchCustomers(): Promise<void> {
    try {
      const response = await api.get<{ data: Customer[] }>('/v1/customers', { per_page: 100 });
      customers.value = response.data;
    } catch {
      // Customers are supplementary; keep existing data on failure
    }
  }

  async function fetchAll(): Promise<void> {
    isLoading.value = true;
    error.value = null;
    try {
      await Promise.all([
        fetchAppointments(),
        fetchServices(),
        fetchEmployees(),
        fetchCustomers(),
      ]);
    } catch (e: any) {
      error.value = e.message || 'Failed to load data';
    } finally {
      isLoading.value = false;
    }
  }

  // Getters
  const filteredAppointments = computed(() => {
    let result = [...appointments.value];

    if (filters.value.dateFrom) {
      result = result.filter(a => a.date >= filters.value.dateFrom);
    }
    if (filters.value.dateTo) {
      result = result.filter(a => a.date <= filters.value.dateTo);
    }
    if (filters.value.employeeId) {
      result = result.filter(a => a.employeeId === filters.value.employeeId);
    }
    if (filters.value.status) {
      result = result.filter(a => a.status === filters.value.status);
    }
    if (filters.value.customerId) {
      result = result.filter(a => a.customerId === filters.value.customerId);
    }
    if (filters.value.search) {
      const q = filters.value.search.toLowerCase();
      result = result.filter(a =>
        a.customerName.toLowerCase().includes(q) ||
        a.employeeName.toLowerCase().includes(q) ||
        a.serviceName.toLowerCase().includes(q) ||
        a.notes.toLowerCase().includes(q)
      );
    }

    // Sort by date + startTime
    result.sort((a, b) => {
      const dateComp = a.date.localeCompare(b.date);
      if (dateComp !== 0) return dateComp;
      return a.startTime.localeCompare(b.startTime);
    });

    return result;
  });

  const todayAppointments = computed(() => {
    const today = todayStr();
    return appointments.value
      .filter(a => a.date === today)
      .sort((a, b) => a.startTime.localeCompare(b.startTime));
  });

  const upcomingAppointments = computed(() => {
    const today = todayStr();
    return appointments.value
      .filter(a => a.date >= today && a.status !== 'CANCELLED' && a.status !== 'COMPLETED' && a.status !== 'NO_SHOW')
      .sort((a, b) => {
        const dateComp = a.date.localeCompare(b.date);
        if (dateComp !== 0) return dateComp;
        return a.startTime.localeCompare(b.startTime);
      });
  });

  function getByDate(date: string): Appointment[] {
    return appointments.value
      .filter(a => a.date === date)
      .sort((a, b) => a.startTime.localeCompare(b.startTime));
  }

  function getById(id: string): Appointment | undefined {
    return appointments.value.find(a => a.id === id);
  }

  function getAppointmentsForWeek(startDate: Date): Appointment[] {
    const dates: string[] = [];
    for (let i = 0; i < 7; i++) {
      const d = new Date(startDate);
      d.setDate(d.getDate() + i);
      dates.push(`${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`);
    }
    return appointments.value
      .filter(a => dates.includes(a.date))
      .sort((a, b) => {
        const dateComp = a.date.localeCompare(b.date);
        if (dateComp !== 0) return dateComp;
        return a.startTime.localeCompare(b.startTime);
      });
  }

  function getAppointmentCountForDate(date: string): number {
    return appointments.value.filter(a => a.date === date).length;
  }

  // Actions
  async function createAppointment(data: Omit<Appointment, 'id'>): Promise<Appointment> {
    const response = await api.post<{ data: Appointment }>('/v1/appointments', data);
    appointments.value.push(response.data);
    return response.data;
  }

  async function updateAppointment(id: string, data: Partial<Appointment>): Promise<boolean> {
    const response = await api.put<{ data: Appointment }>(`/v1/appointments/${id}`, data);
    const index = appointments.value.findIndex(a => a.id === id);
    if (index === -1) return false;
    appointments.value[index] = response.data;
    return true;
  }

  async function deleteAppointment(id: string): Promise<boolean> {
    await api.delete(`/v1/appointments/${id}`);
    const index = appointments.value.findIndex(a => a.id === id);
    if (index === -1) return false;
    appointments.value.splice(index, 1);
    return true;
  }

  async function updateStatus(id: string, status: AppointmentStatus): Promise<boolean> {
    const response = await api.patch<{ data: Appointment }>(`/v1/appointments/${id}/status`, { status });
    const index = appointments.value.findIndex(a => a.id === id);
    if (index === -1) return false;
    appointments.value[index] = response.data;
    return true;
  }

  function setFilters(newFilters: Partial<AppointmentFilters>) {
    filters.value = { ...filters.value, ...newFilters };
  }

  function resetFilters() {
    filters.value = {
      dateFrom: '',
      dateTo: '',
      employeeId: '',
      status: '',
      customerId: '',
      search: '',
    };
  }

  function getEmployeesForService(serviceId: string): Employee[] {
    return employees.value.filter(e => e.serviceIds.includes(serviceId));
  }

  function getAvailableTimeSlots(date: string, employeeId: string, duration: number): string[] {
    const dayAppointments = appointments.value.filter(
      a => a.date === date && a.employeeId === employeeId && a.status !== 'CANCELLED'
    );

    const slots: string[] = [];
    // Working hours: 07:00 - 20:00, 30-minute intervals
    for (let hour = 7; hour < 20; hour++) {
      for (const minutes of [0, 30]) {
        const startMinutes = hour * 60 + minutes;
        const endMinutes = startMinutes + duration;

        // Ensure appointment ends within working hours
        if (endMinutes > 20 * 60) continue;

        const startStr = `${String(hour).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
        const endStr = `${String(Math.floor(endMinutes / 60)).padStart(2, '0')}:${String(endMinutes % 60).padStart(2, '0')}`;

        // Check for conflicts
        const hasConflict = dayAppointments.some(a => {
          return startStr < a.endTime && endStr > a.startTime;
        });

        if (!hasConflict) {
          slots.push(startStr);
        }
      }
    }

    return slots;
  }

  return {
    // State
    appointments,
    services,
    employees,
    customers,
    isLoading,
    error,
    filters,

    // Getters
    filteredAppointments,
    todayAppointments,
    upcomingAppointments,

    // Functions
    fetchAll,
    fetchAppointments,
    fetchServices,
    fetchEmployees,
    fetchCustomers,
    getByDate,
    getById,
    getAppointmentsForWeek,
    getAppointmentCountForDate,
    createAppointment,
    updateAppointment,
    deleteAppointment,
    updateStatus,
    setFilters,
    resetFilters,
    getEmployeesForService,
    getAvailableTimeSlots,
  };
});
