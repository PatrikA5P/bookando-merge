/**
 * Appointments Store — Terminverwaltung
 *
 * Pinia Store fuer:
 * - CRUD-Operationen fuer Termine
 * - Filter nach Datum, Mitarbeiter, Status, Kunde
 * - Mock-Daten mit realistischen Schweizer Terminen
 * - Computed: todayAppointments, upcomingAppointments, getByDate
 *
 * Verbesserung gegenueber Referenz:
 * - Pinia statt monolithischem Context
 * - Typsichere Interfaces
 * - Betraege in Minor Units (Rappen)
 */
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

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

function tomorrowStr(): string {
  const d = new Date();
  d.setDate(d.getDate() + 1);
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

function dayAfterTomorrowStr(): string {
  const d = new Date();
  d.setDate(d.getDate() + 2);
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

function yesterdayStr(): string {
  const d = new Date();
  d.setDate(d.getDate() - 1);
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

function weekFromNowStr(): string {
  const d = new Date();
  d.setDate(d.getDate() + 5);
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

// Mock-Dienstleistungen
const MOCK_SERVICES: Service[] = [
  { id: 'svc-1', name: 'Haarschnitt Damen', duration: 60, priceMinor: 8500, category: 'Haare', description: 'Waschen, Schneiden, Styling' },
  { id: 'svc-2', name: 'Haarschnitt Herren', duration: 30, priceMinor: 4500, category: 'Haare', description: 'Klassischer Herrenschnitt' },
  { id: 'svc-3', name: 'Farbe & Straehnen', duration: 120, priceMinor: 18000, category: 'Haare', description: 'Komplette Faerbung oder Straehnen' },
  { id: 'svc-4', name: 'Manikuere', duration: 45, priceMinor: 6500, category: 'Nails', description: 'Pflege und Lackierung' },
  { id: 'svc-5', name: 'Gesichtsbehandlung', duration: 90, priceMinor: 15000, category: 'Kosmetik', description: 'Tiefenreinigung und Pflege' },
  { id: 'svc-6', name: 'Massage 60min', duration: 60, priceMinor: 12000, category: 'Wellness', description: 'Entspannende Ganzkörpermassage' },
  { id: 'svc-7', name: 'Bart-Trimm & Rasur', duration: 30, priceMinor: 3500, category: 'Haare', description: 'Professionelle Bartpflege' },
  { id: 'svc-8', name: 'Braut-Styling', duration: 180, priceMinor: 35000, category: 'Special', description: 'Komplettes Styling fuer den grossen Tag' },
];

// Mock-Mitarbeiter
const MOCK_EMPLOYEES: Employee[] = [
  { id: 'emp-1', name: 'Anna Mueller', position: 'Senior Stylistin', avatar: '', serviceIds: ['svc-1', 'svc-3', 'svc-8'] },
  { id: 'emp-2', name: 'Marco Bernasconi', position: 'Barber', avatar: '', serviceIds: ['svc-2', 'svc-7'] },
  { id: 'emp-3', name: 'Sophie Dubois', position: 'Kosmetikerin', avatar: '', serviceIds: ['svc-4', 'svc-5'] },
  { id: 'emp-4', name: 'Lena Fischer', position: 'Masseurin', avatar: '', serviceIds: ['svc-6'] },
];

// Mock-Kunden
const MOCK_CUSTOMERS: Customer[] = [
  { id: 'cust-1', name: 'Maria Schneider', email: 'maria.schneider@bluewin.ch', phone: '+41 79 123 45 67' },
  { id: 'cust-2', name: 'Peter Huber', email: 'peter.huber@gmail.com', phone: '+41 78 234 56 78' },
  { id: 'cust-3', name: 'Sabine Keller', email: 'sabine.keller@sunrise.ch', phone: '+41 76 345 67 89' },
  { id: 'cust-4', name: 'Thomas Brunner', email: 'thomas.brunner@swisscom.ch', phone: '+41 79 456 78 90' },
  { id: 'cust-5', name: 'Laura Zimmermann', email: 'laura.z@gmx.ch', phone: '+41 78 567 89 01' },
  { id: 'cust-6', name: 'Michael Gerber', email: 'm.gerber@bluewin.ch', phone: '+41 76 678 90 12' },
];

// Mock-Termine
function createMockAppointments(): Appointment[] {
  const today = todayStr();
  const tomorrow = tomorrowStr();
  const dayAfter = dayAfterTomorrowStr();
  const yesterday = yesterdayStr();
  const nextWeek = weekFromNowStr();

  return [
    {
      id: 'apt-1',
      customerId: 'cust-1', customerName: 'Maria Schneider',
      employeeId: 'emp-1', employeeName: 'Anna Mueller',
      serviceId: 'svc-1', serviceName: 'Haarschnitt Damen',
      date: today, startTime: '09:00', endTime: '10:00', duration: 60,
      status: 'CONFIRMED', priceMinor: 8500, currency: 'CHF',
      notes: 'Stammkundin, bevorzugt Schichtschnitt', locationId: 'loc-1', roomId: 'room-1',
    },
    {
      id: 'apt-2',
      customerId: 'cust-2', customerName: 'Peter Huber',
      employeeId: 'emp-2', employeeName: 'Marco Bernasconi',
      serviceId: 'svc-2', serviceName: 'Haarschnitt Herren',
      date: today, startTime: '10:30', endTime: '11:00', duration: 30,
      status: 'CONFIRMED', priceMinor: 4500, currency: 'CHF',
      notes: '', locationId: 'loc-1', roomId: 'room-2',
    },
    {
      id: 'apt-3',
      customerId: 'cust-3', customerName: 'Sabine Keller',
      employeeId: 'emp-3', employeeName: 'Sophie Dubois',
      serviceId: 'svc-5', serviceName: 'Gesichtsbehandlung',
      date: today, startTime: '11:00', endTime: '12:30', duration: 90,
      status: 'PENDING', priceMinor: 15000, currency: 'CHF',
      notes: 'Empfindliche Haut, bitte hypoallergene Produkte', locationId: 'loc-1', roomId: 'room-3',
    },
    {
      id: 'apt-4',
      customerId: 'cust-4', customerName: 'Thomas Brunner',
      employeeId: 'emp-2', employeeName: 'Marco Bernasconi',
      serviceId: 'svc-7', serviceName: 'Bart-Trimm & Rasur',
      date: today, startTime: '14:00', endTime: '14:30', duration: 30,
      status: 'CONFIRMED', priceMinor: 3500, currency: 'CHF',
      notes: '', locationId: 'loc-1', roomId: 'room-2',
    },
    {
      id: 'apt-5',
      customerId: 'cust-5', customerName: 'Laura Zimmermann',
      employeeId: 'emp-1', employeeName: 'Anna Mueller',
      serviceId: 'svc-3', serviceName: 'Farbe & Straehnen',
      date: tomorrow, startTime: '09:00', endTime: '11:00', duration: 120,
      status: 'CONFIRMED', priceMinor: 18000, currency: 'CHF',
      notes: 'Blonde Straehnen, Balayage-Technik', locationId: 'loc-1', roomId: 'room-1',
    },
    {
      id: 'apt-6',
      customerId: 'cust-6', customerName: 'Michael Gerber',
      employeeId: 'emp-4', employeeName: 'Lena Fischer',
      serviceId: 'svc-6', serviceName: 'Massage 60min',
      date: tomorrow, startTime: '13:00', endTime: '14:00', duration: 60,
      status: 'PENDING', priceMinor: 12000, currency: 'CHF',
      notes: 'Rueckenprobleme, bitte vorsichtig im Lendenwirbelbereich', locationId: 'loc-1', roomId: 'room-4',
    },
    {
      id: 'apt-7',
      customerId: 'cust-1', customerName: 'Maria Schneider',
      employeeId: 'emp-3', employeeName: 'Sophie Dubois',
      serviceId: 'svc-4', serviceName: 'Manikuere',
      date: dayAfter, startTime: '10:00', endTime: '10:45', duration: 45,
      status: 'CONFIRMED', priceMinor: 6500, currency: 'CHF',
      notes: '', locationId: 'loc-1', roomId: 'room-3',
    },
    {
      id: 'apt-8',
      customerId: 'cust-3', customerName: 'Sabine Keller',
      employeeId: 'emp-1', employeeName: 'Anna Mueller',
      serviceId: 'svc-8', serviceName: 'Braut-Styling',
      date: nextWeek, startTime: '08:00', endTime: '11:00', duration: 180,
      status: 'CONFIRMED', priceMinor: 35000, currency: 'CHF',
      notes: 'Hochzeit am Samstag, Probetermin war letzte Woche', locationId: 'loc-1', roomId: 'room-1',
    },
    {
      id: 'apt-9',
      customerId: 'cust-2', customerName: 'Peter Huber',
      employeeId: 'emp-2', employeeName: 'Marco Bernasconi',
      serviceId: 'svc-2', serviceName: 'Haarschnitt Herren',
      date: yesterday, startTime: '15:00', endTime: '15:30', duration: 30,
      status: 'COMPLETED', priceMinor: 4500, currency: 'CHF',
      notes: '', locationId: 'loc-1', roomId: 'room-2',
    },
    {
      id: 'apt-10',
      customerId: 'cust-4', customerName: 'Thomas Brunner',
      employeeId: 'emp-4', employeeName: 'Lena Fischer',
      serviceId: 'svc-6', serviceName: 'Massage 60min',
      date: yesterday, startTime: '10:00', endTime: '11:00', duration: 60,
      status: 'NO_SHOW', priceMinor: 12000, currency: 'CHF',
      notes: 'Nicht erschienen, keine Absage', locationId: 'loc-1', roomId: 'room-4',
    },
  ];
}

export const useAppointmentsStore = defineStore('appointments', () => {
  // State
  const appointments = ref<Appointment[]>(createMockAppointments());
  const services = ref<Service[]>(MOCK_SERVICES);
  const employees = ref<Employee[]>(MOCK_EMPLOYEES);
  const customers = ref<Customer[]>(MOCK_CUSTOMERS);
  const isLoading = ref(false);
  const filters = ref<AppointmentFilters>({
    dateFrom: '',
    dateTo: '',
    employeeId: '',
    status: '',
    customerId: '',
    search: '',
  });

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
  function createAppointment(data: Omit<Appointment, 'id'>): Appointment {
    const newId = `apt-${Date.now()}`;
    const appointment: Appointment = { ...data, id: newId };
    appointments.value.push(appointment);
    return appointment;
  }

  function updateAppointment(id: string, data: Partial<Appointment>): boolean {
    const index = appointments.value.findIndex(a => a.id === id);
    if (index === -1) return false;
    appointments.value[index] = { ...appointments.value[index], ...data };
    return true;
  }

  function deleteAppointment(id: string): boolean {
    const index = appointments.value.findIndex(a => a.id === id);
    if (index === -1) return false;
    appointments.value.splice(index, 1);
    return true;
  }

  function updateStatus(id: string, status: AppointmentStatus): boolean {
    return updateAppointment(id, { status });
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
    filters,

    // Getters
    filteredAppointments,
    todayAppointments,
    upcomingAppointments,

    // Functions
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
