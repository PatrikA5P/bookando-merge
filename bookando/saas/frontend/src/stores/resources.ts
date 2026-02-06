/**
 * Resources Store — Standorte, Raeume & Equipment
 *
 * Zentraler Pinia Store fuer alle Ressourcen-Operationen:
 * - CRUD fuer Standorte, Raeume und Equipment
 * - Such- und Filter-Funktionalitaet
 * - Status-Management
 * - Standort-Zuordnung
 */
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

// ============================================================================
// TYPES
// ============================================================================

export type LocationStatus = 'OPEN' | 'CLOSED';

export type RoomStatus = 'AVAILABLE' | 'IN_USE' | 'CLOSED' | 'MAINTENANCE';

export type EquipmentCondition = 'GOOD' | 'FAIR' | 'POOR';

export interface Location {
  id: string;
  name: string;
  address: string;
  city: string;
  zip: string;
  country: string;
  status: LocationStatus;
  roomCount: number;
  phone?: string;
  email?: string;
  createdAt: string;
  updatedAt: string;
}

export interface Room {
  id: string;
  locationId: string;
  locationName: string;
  name: string;
  capacity: number;
  status: RoomStatus;
  features: string[];
  createdAt: string;
  updatedAt: string;
}

export interface Equipment {
  id: string;
  name: string;
  category: string;
  available: number;
  total: number;
  condition: EquipmentCondition;
  locationId?: string;
  locationName?: string;
  createdAt: string;
  updatedAt: string;
}

export type LocationFormData = Omit<Location, 'id' | 'createdAt' | 'updatedAt' | 'roomCount'>;
export type RoomFormData = Omit<Room, 'id' | 'createdAt' | 'updatedAt'>;
export type EquipmentFormData = Omit<Equipment, 'id' | 'createdAt' | 'updatedAt'>;

export interface ResourceFilters {
  search: string;
  locationId: string;
  status: string;
}

// ============================================================================
// EQUIPMENT CATEGORIES
// ============================================================================

export const EQUIPMENT_CATEGORIES = [
  'Elektronik',
  'Moebel',
  'Werkzeug',
  'Pflege',
  'Hygiene',
  'Sonstiges',
] as const;

// ============================================================================
// MOCK DATA
// ============================================================================

const MOCK_LOCATIONS: Location[] = [
  {
    id: 'loc-001',
    name: 'Hauptstandort Zuerich',
    address: 'Bahnhofstrasse 42',
    city: 'Zuerich',
    zip: '8001',
    country: 'CH',
    status: 'OPEN',
    roomCount: 3,
    phone: '+41 44 123 45 67',
    email: 'zuerich@bookando.ch',
    createdAt: '2023-01-15T10:00:00Z',
    updatedAt: '2025-11-20T14:30:00Z',
  },
  {
    id: 'loc-002',
    name: 'Filiale Bern',
    address: 'Kramgasse 18',
    city: 'Bern',
    zip: '3011',
    country: 'CH',
    status: 'OPEN',
    roomCount: 2,
    phone: '+41 31 234 56 78',
    email: 'bern@bookando.ch',
    createdAt: '2023-06-01T08:00:00Z',
    updatedAt: '2025-10-15T09:00:00Z',
  },
  {
    id: 'loc-003',
    name: 'Pop-up Basel',
    address: 'Freie Strasse 7',
    city: 'Basel',
    zip: '4001',
    country: 'CH',
    status: 'CLOSED',
    roomCount: 1,
    phone: '+41 61 345 67 89',
    createdAt: '2024-03-01T09:00:00Z',
    updatedAt: '2025-12-01T11:00:00Z',
  },
];

const MOCK_ROOMS: Room[] = [
  {
    id: 'room-001',
    locationId: 'loc-001',
    locationName: 'Hauptstandort Zuerich',
    name: 'Salon A',
    capacity: 8,
    status: 'AVAILABLE',
    features: ['Spiegel', 'Waschbecken', 'Klimaanlage'],
    createdAt: '2023-01-20T10:00:00Z',
    updatedAt: '2025-11-20T14:30:00Z',
  },
  {
    id: 'room-002',
    locationId: 'loc-001',
    locationName: 'Hauptstandort Zuerich',
    name: 'Wellness-Raum',
    capacity: 3,
    status: 'IN_USE',
    features: ['Liege', 'Dimmbar', 'Musikanlage', 'Aromatherapie'],
    createdAt: '2023-01-20T10:00:00Z',
    updatedAt: '2025-11-20T14:30:00Z',
  },
  {
    id: 'room-003',
    locationId: 'loc-001',
    locationName: 'Hauptstandort Zuerich',
    name: 'VIP-Bereich',
    capacity: 2,
    status: 'AVAILABLE',
    features: ['Minibar', 'TV', 'Privat', 'Klimaanlage'],
    createdAt: '2023-03-15T10:00:00Z',
    updatedAt: '2025-10-01T12:00:00Z',
  },
  {
    id: 'room-004',
    locationId: 'loc-002',
    locationName: 'Filiale Bern',
    name: 'Salon Hauptraum',
    capacity: 6,
    status: 'AVAILABLE',
    features: ['Spiegel', 'Waschbecken'],
    createdAt: '2023-06-05T08:00:00Z',
    updatedAt: '2025-09-15T16:00:00Z',
  },
  {
    id: 'room-005',
    locationId: 'loc-002',
    locationName: 'Filiale Bern',
    name: 'Kosmetik-Kabine',
    capacity: 2,
    status: 'MAINTENANCE',
    features: ['Liege', 'Vergroesserungsspiegel', 'LED-Licht'],
    createdAt: '2023-06-05T08:00:00Z',
    updatedAt: '2025-12-01T10:00:00Z',
  },
  {
    id: 'room-006',
    locationId: 'loc-003',
    locationName: 'Pop-up Basel',
    name: 'Offener Bereich',
    capacity: 4,
    status: 'CLOSED',
    features: ['Spiegel', 'Waschbecken'],
    createdAt: '2024-03-05T09:00:00Z',
    updatedAt: '2025-12-01T11:00:00Z',
  },
];

const MOCK_EQUIPMENT: Equipment[] = [
  {
    id: 'eq-001',
    name: 'Haartrockner Dyson Supersonic',
    category: 'Elektronik',
    available: 5,
    total: 6,
    condition: 'GOOD',
    locationId: 'loc-001',
    locationName: 'Hauptstandort Zuerich',
    createdAt: '2023-02-01T10:00:00Z',
    updatedAt: '2025-11-01T12:00:00Z',
  },
  {
    id: 'eq-002',
    name: 'Friseurstuhl Premium',
    category: 'Moebel',
    available: 7,
    total: 8,
    condition: 'GOOD',
    locationId: 'loc-001',
    locationName: 'Hauptstandort Zuerich',
    createdAt: '2023-01-15T10:00:00Z',
    updatedAt: '2025-10-20T14:00:00Z',
  },
  {
    id: 'eq-003',
    name: 'Glätteisen GHD Platinum+',
    category: 'Elektronik',
    available: 3,
    total: 4,
    condition: 'FAIR',
    locationId: 'loc-001',
    locationName: 'Hauptstandort Zuerich',
    createdAt: '2023-04-10T08:00:00Z',
    updatedAt: '2025-11-15T09:00:00Z',
  },
  {
    id: 'eq-004',
    name: 'Massageliege Elektrisch',
    category: 'Moebel',
    available: 2,
    total: 2,
    condition: 'GOOD',
    locationId: 'loc-001',
    locationName: 'Hauptstandort Zuerich',
    createdAt: '2023-05-20T10:00:00Z',
    updatedAt: '2025-09-01T16:00:00Z',
  },
  {
    id: 'eq-005',
    name: 'Sterilisator UV',
    category: 'Hygiene',
    available: 2,
    total: 3,
    condition: 'FAIR',
    locationId: 'loc-002',
    locationName: 'Filiale Bern',
    createdAt: '2023-07-01T08:00:00Z',
    updatedAt: '2025-10-15T11:00:00Z',
  },
  {
    id: 'eq-006',
    name: 'Lockenstab Set',
    category: 'Elektronik',
    available: 4,
    total: 5,
    condition: 'GOOD',
    locationId: 'loc-002',
    locationName: 'Filiale Bern',
    createdAt: '2023-07-15T08:00:00Z',
    updatedAt: '2025-11-10T10:00:00Z',
  },
  {
    id: 'eq-007',
    name: 'Handtuchwaermer',
    category: 'Elektronik',
    available: 1,
    total: 2,
    condition: 'POOR',
    locationId: 'loc-001',
    locationName: 'Hauptstandort Zuerich',
    createdAt: '2023-03-01T10:00:00Z',
    updatedAt: '2025-12-01T09:00:00Z',
  },
  {
    id: 'eq-008',
    name: 'Scherenset Professionell',
    category: 'Werkzeug',
    available: 10,
    total: 12,
    condition: 'GOOD',
    createdAt: '2023-01-15T10:00:00Z',
    updatedAt: '2025-11-20T14:00:00Z',
  },
];

// ============================================================================
// STORE
// ============================================================================

export const useResourcesStore = defineStore('resources', () => {
  // State
  const locations = ref<Location[]>([...MOCK_LOCATIONS]);
  const rooms = ref<Room[]>([...MOCK_ROOMS]);
  const equipment = ref<Equipment[]>([...MOCK_EQUIPMENT]);
  const loading = ref(false);
  const error = ref<string | null>(null);
  const filters = ref<ResourceFilters>({
    search: '',
    locationId: '',
    status: '',
  });

  // ========================================================================
  // LOCATION GETTERS & ACTIONS
  // ========================================================================

  const filteredLocations = computed(() => {
    let result = locations.value;

    if (filters.value.search) {
      const q = filters.value.search.toLowerCase();
      result = result.filter(l =>
        l.name.toLowerCase().includes(q) ||
        l.city.toLowerCase().includes(q) ||
        l.address.toLowerCase().includes(q)
      );
    }

    if (filters.value.status) {
      result = result.filter(l => l.status === filters.value.status);
    }

    return result;
  });

  const locationCount = computed(() => locations.value.length);
  const openLocationCount = computed(() => locations.value.filter(l => l.status === 'OPEN').length);

  function getLocationById(id: string): Location | undefined {
    return locations.value.find(l => l.id === id);
  }

  function createLocation(data: LocationFormData): Location {
    const now = new Date().toISOString();
    const newLocation: Location = {
      ...data,
      id: `loc-${String(locations.value.length + 1).padStart(3, '0')}`,
      roomCount: 0,
      createdAt: now,
      updatedAt: now,
    };
    locations.value.push(newLocation);
    return newLocation;
  }

  function updateLocation(id: string, data: Partial<LocationFormData>): Location | null {
    const index = locations.value.findIndex(l => l.id === id);
    if (index === -1) return null;

    const updated: Location = {
      ...locations.value[index],
      ...data,
      updatedAt: new Date().toISOString(),
    };
    locations.value[index] = updated;
    return updated;
  }

  function deleteLocation(id: string): boolean {
    const index = locations.value.findIndex(l => l.id === id);
    if (index === -1) return false;
    locations.value.splice(index, 1);
    // Also remove associated rooms
    rooms.value = rooms.value.filter(r => r.locationId !== id);
    return true;
  }

  // ========================================================================
  // ROOM GETTERS & ACTIONS
  // ========================================================================

  const filteredRooms = computed(() => {
    let result = rooms.value;

    if (filters.value.search) {
      const q = filters.value.search.toLowerCase();
      result = result.filter(r =>
        r.name.toLowerCase().includes(q) ||
        r.locationName.toLowerCase().includes(q)
      );
    }

    if (filters.value.locationId) {
      result = result.filter(r => r.locationId === filters.value.locationId);
    }

    if (filters.value.status) {
      result = result.filter(r => r.status === filters.value.status);
    }

    return result;
  });

  const roomCount = computed(() => rooms.value.length);
  const availableRoomCount = computed(() => rooms.value.filter(r => r.status === 'AVAILABLE').length);

  function getRoomById(id: string): Room | undefined {
    return rooms.value.find(r => r.id === id);
  }

  function createRoom(data: RoomFormData): Room {
    const now = new Date().toISOString();
    const newRoom: Room = {
      ...data,
      id: `room-${String(rooms.value.length + 1).padStart(3, '0')}`,
      createdAt: now,
      updatedAt: now,
    };
    rooms.value.push(newRoom);
    // Update room count on location
    const loc = locations.value.find(l => l.id === data.locationId);
    if (loc) loc.roomCount++;
    return newRoom;
  }

  function updateRoom(id: string, data: Partial<RoomFormData>): Room | null {
    const index = rooms.value.findIndex(r => r.id === id);
    if (index === -1) return null;

    const updated: Room = {
      ...rooms.value[index],
      ...data,
      updatedAt: new Date().toISOString(),
    };
    rooms.value[index] = updated;
    return updated;
  }

  function deleteRoom(id: string): boolean {
    const room = rooms.value.find(r => r.id === id);
    if (!room) return false;
    const index = rooms.value.indexOf(room);
    rooms.value.splice(index, 1);
    // Update room count on location
    const loc = locations.value.find(l => l.id === room.locationId);
    if (loc && loc.roomCount > 0) loc.roomCount--;
    return true;
  }

  // ========================================================================
  // EQUIPMENT GETTERS & ACTIONS
  // ========================================================================

  const filteredEquipment = computed(() => {
    let result = equipment.value;

    if (filters.value.search) {
      const q = filters.value.search.toLowerCase();
      result = result.filter(eq =>
        eq.name.toLowerCase().includes(q) ||
        eq.category.toLowerCase().includes(q)
      );
    }

    if (filters.value.locationId) {
      result = result.filter(eq => eq.locationId === filters.value.locationId);
    }

    return result;
  });

  const equipmentCount = computed(() => equipment.value.length);
  const totalEquipmentItems = computed(() =>
    equipment.value.reduce((sum, eq) => sum + eq.total, 0)
  );

  function getEquipmentById(id: string): Equipment | undefined {
    return equipment.value.find(eq => eq.id === id);
  }

  function createEquipment(data: EquipmentFormData): Equipment {
    const now = new Date().toISOString();
    const newEquipment: Equipment = {
      ...data,
      id: `eq-${String(equipment.value.length + 1).padStart(3, '0')}`,
      createdAt: now,
      updatedAt: now,
    };
    equipment.value.push(newEquipment);
    return newEquipment;
  }

  function updateEquipment(id: string, data: Partial<EquipmentFormData>): Equipment | null {
    const index = equipment.value.findIndex(eq => eq.id === id);
    if (index === -1) return null;

    const updated: Equipment = {
      ...equipment.value[index],
      ...data,
      updatedAt: new Date().toISOString(),
    };
    equipment.value[index] = updated;
    return updated;
  }

  function deleteEquipment(id: string): boolean {
    const index = equipment.value.findIndex(eq => eq.id === id);
    if (index === -1) return false;
    equipment.value.splice(index, 1);
    return true;
  }

  // ========================================================================
  // FILTER ACTIONS
  // ========================================================================

  function setFilters(newFilters: Partial<ResourceFilters>) {
    filters.value = { ...filters.value, ...newFilters };
  }

  function resetFilters() {
    filters.value = { search: '', locationId: '', status: '' };
  }

  // Location options for selects
  const locationOptions = computed(() =>
    locations.value.map(l => ({ value: l.id, label: l.name }))
  );

  return {
    // State
    locations,
    rooms,
    equipment,
    loading,
    error,
    filters,

    // Location getters & actions
    filteredLocations,
    locationCount,
    openLocationCount,
    getLocationById,
    createLocation,
    updateLocation,
    deleteLocation,

    // Room getters & actions
    filteredRooms,
    roomCount,
    availableRoomCount,
    getRoomById,
    createRoom,
    updateRoom,
    deleteRoom,

    // Equipment getters & actions
    filteredEquipment,
    equipmentCount,
    totalEquipmentItems,
    getEquipmentById,
    createEquipment,
    updateEquipment,
    deleteEquipment,

    // Filter actions
    setFilters,
    resetFilters,
    locationOptions,
  };
});
