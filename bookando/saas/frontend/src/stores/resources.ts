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
import api from '@/utils/api';

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
// STORE
// ============================================================================

export const useResourcesStore = defineStore('resources', () => {
  // State
  const locations = ref<Location[]>([]);
  const rooms = ref<Room[]>([]);
  const equipment = ref<Equipment[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);
  const filters = ref<ResourceFilters>({
    search: '',
    locationId: '',
    status: '',
  });

  // ========================================================================
  // FETCH ACTIONS
  // ========================================================================

  async function fetchLocations(): Promise<void> {
    try {
      const response = await api.get<{ data: Location[] }>('/v1/locations', { per_page: 100 });
      locations.value = response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Standorte konnten nicht geladen werden';
      error.value = message;
      throw err;
    }
  }

  async function fetchRooms(): Promise<void> {
    try {
      const response = await api.get<{ data: Room[] }>('/v1/rooms', { per_page: 100 });
      rooms.value = response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Raeume konnten nicht geladen werden';
      error.value = message;
      throw err;
    }
  }

  async function fetchEquipment(): Promise<void> {
    try {
      const response = await api.get<{ data: Equipment[] }>('/v1/equipment', { per_page: 100 });
      equipment.value = response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Equipment konnte nicht geladen werden';
      error.value = message;
      throw err;
    }
  }

  async function fetchAll(): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
      await Promise.all([fetchLocations(), fetchRooms(), fetchEquipment()]);
    } catch {
      // Individual fetch functions already set error.value
    } finally {
      loading.value = false;
    }
  }

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

  async function createLocation(data: LocationFormData): Promise<Location> {
    try {
      const response = await api.post<{ data: Location }>('/v1/locations', data);
      locations.value.push(response.data);
      return response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Standort konnte nicht erstellt werden';
      error.value = message;
      throw err;
    }
  }

  async function updateLocation(id: string, data: Partial<LocationFormData>): Promise<Location | null> {
    try {
      const response = await api.put<{ data: Location }>(`/v1/locations/${id}`, data);
      const index = locations.value.findIndex(l => l.id === id);
      if (index !== -1) {
        locations.value[index] = response.data;
      }
      return response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Standort konnte nicht aktualisiert werden';
      error.value = message;
      throw err;
    }
  }

  async function deleteLocation(id: string): Promise<boolean> {
    try {
      await api.delete(`/v1/locations/${id}`);
      const index = locations.value.findIndex(l => l.id === id);
      if (index === -1) return false;
      locations.value.splice(index, 1);
      // Also remove associated rooms from local state
      rooms.value = rooms.value.filter(r => r.locationId !== id);
      return true;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Standort konnte nicht geloescht werden';
      error.value = message;
      throw err;
    }
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

  async function createRoom(data: RoomFormData): Promise<Room> {
    try {
      const response = await api.post<{ data: Room }>('/v1/rooms', data);
      rooms.value.push(response.data);
      // Update room count on location
      const loc = locations.value.find(l => l.id === data.locationId);
      if (loc) loc.roomCount++;
      return response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Raum konnte nicht erstellt werden';
      error.value = message;
      throw err;
    }
  }

  async function updateRoom(id: string, data: Partial<RoomFormData>): Promise<Room | null> {
    try {
      const response = await api.put<{ data: Room }>(`/v1/rooms/${id}`, data);
      const index = rooms.value.findIndex(r => r.id === id);
      if (index !== -1) {
        rooms.value[index] = response.data;
      }
      return response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Raum konnte nicht aktualisiert werden';
      error.value = message;
      throw err;
    }
  }

  async function deleteRoom(id: string): Promise<boolean> {
    try {
      const room = rooms.value.find(r => r.id === id);
      await api.delete(`/v1/rooms/${id}`);
      const index = rooms.value.findIndex(r => r.id === id);
      if (index === -1) return false;
      rooms.value.splice(index, 1);
      // Update room count on location
      if (room) {
        const loc = locations.value.find(l => l.id === room.locationId);
        if (loc && loc.roomCount > 0) loc.roomCount--;
      }
      return true;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Raum konnte nicht geloescht werden';
      error.value = message;
      throw err;
    }
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

  async function createEquipment(data: EquipmentFormData): Promise<Equipment> {
    try {
      const response = await api.post<{ data: Equipment }>('/v1/equipment', data);
      equipment.value.push(response.data);
      return response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Equipment konnte nicht erstellt werden';
      error.value = message;
      throw err;
    }
  }

  async function updateEquipment(id: string, data: Partial<EquipmentFormData>): Promise<Equipment | null> {
    try {
      const response = await api.put<{ data: Equipment }>(`/v1/equipment/${id}`, data);
      const index = equipment.value.findIndex(eq => eq.id === id);
      if (index !== -1) {
        equipment.value[index] = response.data;
      }
      return response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Equipment konnte nicht aktualisiert werden';
      error.value = message;
      throw err;
    }
  }

  async function deleteEquipment(id: string): Promise<boolean> {
    try {
      await api.delete(`/v1/equipment/${id}`);
      const index = equipment.value.findIndex(eq => eq.id === id);
      if (index === -1) return false;
      equipment.value.splice(index, 1);
      return true;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Equipment konnte nicht geloescht werden';
      error.value = message;
      throw err;
    }
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

    // Fetch actions
    fetchLocations,
    fetchRooms,
    fetchEquipment,
    fetchAll,

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
