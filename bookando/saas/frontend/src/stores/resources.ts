/**
 * Resources Store — Generische Ressourcenverwaltung
 *
 * Refactored gemaess MODUL_ANALYSE.md Abschnitt 2.4:
 * - Einheitliches Resource-Modell mit ResourceType-Discriminator
 * - ResourceReservation mit TENTATIVE → CONFIRMED → RELEASED
 * - AvailabilityRules fuer wiederkehrende Verfuegbarkeit
 * - Typ-spezifische Properties via Discriminated Union
 */
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/utils/api';
import type {
  Resource,
  ResourceType,
  ResourceStatus,
  ResourceVisibility,
  ResourceReservation,
  ReservationStatus,
  ResourceAvailabilityRule,
  ResourceFormData,
  ResourceReservationFormData,
  LocationProperties,
  RoomProperties,
  VehicleProperties,
  EquipmentProperties,
} from '@/types/domain/resources';
import {
  isLocation,
  isRoom,
  isVehicle,
  isEquipment,
  isLocationProperties,
  isRoomProperties,
  isVehicleProperties,
  isEquipmentProperties,
} from '@/types/domain/resources';

// Re-export domain types for backward compatibility
export type {
  Resource,
  ResourceType,
  ResourceStatus,
  ResourceVisibility,
  ResourceReservation,
  ReservationStatus,
  ResourceAvailabilityRule,
  ResourceFormData,
  ResourceReservationFormData,
  LocationProperties,
  RoomProperties,
  VehicleProperties,
  EquipmentProperties,
};

export {
  isLocation,
  isRoom,
  isVehicle,
  isEquipment,
  isLocationProperties,
  isRoomProperties,
  isVehicleProperties,
  isEquipmentProperties,
};

// ============================================================================
// CONSTANTS
// ============================================================================

export const RESOURCE_TYPE_LABELS: Record<ResourceType, string> = {
  LOCATION: 'Standort',
  ROOM: 'Raum',
  VEHICLE: 'Fahrzeug',
  EQUIPMENT: 'Equipment',
};

export const RESOURCE_TYPE_ICONS: Record<ResourceType, string> = {
  LOCATION: 'map-pin',
  ROOM: 'door-open',
  VEHICLE: 'car',
  EQUIPMENT: 'wrench',
};

export const RESOURCE_STATUS_LABELS: Record<ResourceStatus, string> = {
  ACTIVE: 'Aktiv',
  MAINTENANCE: 'Wartung',
  RETIRED: 'Ausser Betrieb',
};

export const RESOURCE_STATUS_COLORS: Record<ResourceStatus, string> = {
  ACTIVE: 'success',
  MAINTENANCE: 'warning',
  RETIRED: 'danger',
};

export const RESERVATION_STATUS_LABELS: Record<ReservationStatus, string> = {
  TENTATIVE: 'Reserviert',
  CONFIRMED: 'Bestaetigt',
  RELEASED: 'Freigegeben',
};

export const RESERVATION_STATUS_COLORS: Record<ReservationStatus, string> = {
  TENTATIVE: 'warning',
  CONFIRMED: 'success',
  RELEASED: 'info',
};

export const EQUIPMENT_CATEGORIES = [
  'Elektronik',
  'Moebel',
  'Werkzeug',
  'Pflege',
  'Hygiene',
  'Fahrzeugzubehoer',
  'Sonstiges',
] as const;

export const VEHICLE_CATEGORIES = [
  'Motorrad',
  'Auto',
  'Lieferwagen',
  'Anhaenger',
  'Sonstiges',
] as const;

// ============================================================================
// FILTERS
// ============================================================================

export interface ResourceFilters {
  search: string;
  resourceType: ResourceType | '';
  status: ResourceStatus | '';
  parentId: string;
  visibility: ResourceVisibility | '';
}

// ============================================================================
// STORE
// ============================================================================

export const useResourcesStore = defineStore('resources', () => {
  // ── State ──────────────────────────────────────────────────────────────
  const resources = ref<Resource[]>([]);
  const reservations = ref<ResourceReservation[]>([]);
  const availabilityRules = ref<ResourceAvailabilityRule[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);
  const filters = ref<ResourceFilters>({
    search: '',
    resourceType: '',
    status: '',
    parentId: '',
    visibility: '',
  });

  // ── Type-filtered Collections ──────────────────────────────────────────
  const locations = computed(() =>
    resources.value.filter(r => r.resourceType === 'LOCATION')
  );

  const rooms = computed(() =>
    resources.value.filter(r => r.resourceType === 'ROOM')
  );

  const vehicles = computed(() =>
    resources.value.filter(r => r.resourceType === 'VEHICLE')
  );

  const equipment = computed(() =>
    resources.value.filter(r => r.resourceType === 'EQUIPMENT')
  );

  // ── Filtered Views ─────────────────────────────────────────────────────
  const filteredResources = computed(() => {
    let result = resources.value;

    if (filters.value.search) {
      const q = filters.value.search.toLowerCase();
      result = result.filter(r =>
        r.name.toLowerCase().includes(q) ||
        (r.description || '').toLowerCase().includes(q) ||
        (r.parentName || '').toLowerCase().includes(q)
      );
    }

    if (filters.value.resourceType) {
      result = result.filter(r => r.resourceType === filters.value.resourceType);
    }

    if (filters.value.status) {
      result = result.filter(r => r.status === filters.value.status);
    }

    if (filters.value.parentId) {
      result = result.filter(r => r.parentId === filters.value.parentId);
    }

    if (filters.value.visibility) {
      result = result.filter(r => r.visibility === filters.value.visibility);
    }

    return result;
  });

  const filteredLocations = computed(() =>
    filteredResources.value.filter(r => r.resourceType === 'LOCATION')
  );

  const filteredRooms = computed(() =>
    filteredResources.value.filter(r => r.resourceType === 'ROOM')
  );

  const filteredVehicles = computed(() =>
    filteredResources.value.filter(r => r.resourceType === 'VEHICLE')
  );

  const filteredEquipment = computed(() =>
    filteredResources.value.filter(r => r.resourceType === 'EQUIPMENT')
  );

  // ── Counts ─────────────────────────────────────────────────────────────
  const locationCount = computed(() => locations.value.length);
  const roomCount = computed(() => rooms.value.length);
  const vehicleCount = computed(() => vehicles.value.length);
  const equipmentCount = computed(() => equipment.value.length);
  const activeCount = computed(() => resources.value.filter(r => r.status === 'ACTIVE').length);

  const availableRoomCount = computed(() =>
    rooms.value.filter(r => r.status === 'ACTIVE').length
  );

  const openLocationCount = computed(() =>
    locations.value.filter(r => r.status === 'ACTIVE').length
  );

  // ── Location Options (for selects) ─────────────────────────────────────
  const locationOptions = computed(() =>
    locations.value.map(l => ({ value: l.id, label: l.name }))
  );

  // ── Reservation Helpers ────────────────────────────────────────────────
  const activeReservations = computed(() =>
    reservations.value.filter(r => r.status !== 'RELEASED')
  );

  const tentativeReservations = computed(() =>
    reservations.value.filter(r => r.status === 'TENTATIVE')
  );

  function getReservationsForResource(resourceId: string): ResourceReservation[] {
    return reservations.value.filter(r => r.resourceId === resourceId && r.status !== 'RELEASED');
  }

  function isResourceAvailable(resourceId: string, startsAt: string, endsAt: string): boolean {
    const start = new Date(startsAt).getTime();
    const end = new Date(endsAt).getTime();

    return !activeReservations.value.some(r => {
      if (r.resourceId !== resourceId) return false;
      const rStart = new Date(r.startsAt).getTime();
      const rEnd = new Date(r.endsAt).getTime();
      return start < rEnd && end > rStart; // overlap check
    });
  }

  // ── Fetch Actions ──────────────────────────────────────────────────────
  async function fetchResources(): Promise<void> {
    try {
      const response = await api.get<{ data: Resource[] }>('/v1/resources', { per_page: 200 });
      resources.value = response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Ressourcen konnten nicht geladen werden';
      error.value = message;
      throw err;
    }
  }

  async function fetchReservations(params?: { resourceId?: string; from?: string; to?: string }): Promise<void> {
    try {
      const response = await api.get<{ data: ResourceReservation[] }>('/v1/resource-reservations', params);
      reservations.value = response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Reservierungen konnten nicht geladen werden';
      error.value = message;
      throw err;
    }
  }

  async function fetchAvailabilityRules(resourceId?: string): Promise<void> {
    try {
      const params = resourceId ? { resourceId } : {};
      const response = await api.get<{ data: ResourceAvailabilityRule[] }>('/v1/resource-availability-rules', params);
      availabilityRules.value = response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Verfuegbarkeitsregeln konnten nicht geladen werden';
      error.value = message;
      throw err;
    }
  }

  async function fetchAll(): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
      await Promise.all([fetchResources(), fetchReservations()]);
    } catch {
      // Individual fetch functions already set error.value
    } finally {
      loading.value = false;
    }
  }

  // ── Lookups ────────────────────────────────────────────────────────────
  function getResourceById(id: string): Resource | undefined {
    return resources.value.find(r => r.id === id);
  }

  function getResourcesByType(type: ResourceType): Resource[] {
    return resources.value.filter(r => r.resourceType === type);
  }

  function getChildResources(parentId: string): Resource[] {
    return resources.value.filter(r => r.parentId === parentId);
  }

  // ── CRUD: Resources ────────────────────────────────────────────────────
  async function createResource(data: ResourceFormData): Promise<Resource> {
    try {
      const response = await api.post<{ data: Resource }>('/v1/resources', data);
      resources.value.push(response.data);

      // Update parent's room count if creating a ROOM under a LOCATION
      if (response.data.resourceType === 'ROOM' && response.data.parentId) {
        updateLocationRoomCount(response.data.parentId);
      }

      return response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Ressource konnte nicht erstellt werden';
      error.value = message;
      throw err;
    }
  }

  async function updateResource(id: string, data: Partial<ResourceFormData>): Promise<Resource> {
    try {
      const response = await api.put<{ data: Resource }>(`/v1/resources/${id}`, data);
      const index = resources.value.findIndex(r => r.id === id);
      if (index !== -1) {
        resources.value[index] = response.data;
      }
      return response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Ressource konnte nicht aktualisiert werden';
      error.value = message;
      throw err;
    }
  }

  async function deleteResource(id: string): Promise<boolean> {
    try {
      const resource = resources.value.find(r => r.id === id);
      await api.delete(`/v1/resources/${id}`);
      resources.value = resources.value.filter(r => r.id !== id);

      // If deleting a LOCATION, also remove child resources locally
      if (resource && resource.resourceType === 'LOCATION') {
        resources.value = resources.value.filter(r => r.parentId !== id);
      }

      // Update parent's room count if deleting a ROOM
      if (resource && resource.resourceType === 'ROOM' && resource.parentId) {
        updateLocationRoomCount(resource.parentId);
      }

      return true;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Ressource konnte nicht geloescht werden';
      error.value = message;
      throw err;
    }
  }

  function updateLocationRoomCount(locationId: string) {
    const loc = resources.value.find(r => r.id === locationId);
    if (loc && isLocationProperties(loc.properties)) {
      (loc.properties as LocationProperties).roomCount = resources.value.filter(
        r => r.resourceType === 'ROOM' && r.parentId === locationId
      ).length;
    }
  }

  // ── CRUD: Reservations ─────────────────────────────────────────────────
  async function createReservation(data: ResourceReservationFormData): Promise<ResourceReservation> {
    try {
      const response = await api.post<{ data: ResourceReservation }>('/v1/resource-reservations', data);
      reservations.value.push(response.data);
      return response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Reservierung konnte nicht erstellt werden';
      error.value = message;
      throw err;
    }
  }

  async function confirmReservation(id: string): Promise<ResourceReservation> {
    try {
      const response = await api.put<{ data: ResourceReservation }>(
        `/v1/resource-reservations/${id}`,
        { status: 'CONFIRMED' }
      );
      const index = reservations.value.findIndex(r => r.id === id);
      if (index !== -1) {
        reservations.value[index] = response.data;
      }
      return response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Reservierung konnte nicht bestaetigt werden';
      error.value = message;
      throw err;
    }
  }

  async function releaseReservation(id: string): Promise<ResourceReservation> {
    try {
      const response = await api.put<{ data: ResourceReservation }>(
        `/v1/resource-reservations/${id}`,
        { status: 'RELEASED' }
      );
      const index = reservations.value.findIndex(r => r.id === id);
      if (index !== -1) {
        reservations.value[index] = response.data;
      }
      return response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Reservierung konnte nicht freigegeben werden';
      error.value = message;
      throw err;
    }
  }

  // ── CRUD: Availability Rules ───────────────────────────────────────────
  async function createAvailabilityRule(data: Omit<ResourceAvailabilityRule, 'id'>): Promise<ResourceAvailabilityRule> {
    try {
      const response = await api.post<{ data: ResourceAvailabilityRule }>('/v1/resource-availability-rules', data);
      availabilityRules.value.push(response.data);
      return response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Verfuegbarkeitsregel konnte nicht erstellt werden';
      error.value = message;
      throw err;
    }
  }

  async function deleteAvailabilityRule(id: string): Promise<boolean> {
    try {
      await api.delete(`/v1/resource-availability-rules/${id}`);
      availabilityRules.value = availabilityRules.value.filter(r => r.id !== id);
      return true;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Verfuegbarkeitsregel konnte nicht geloescht werden';
      error.value = message;
      throw err;
    }
  }

  // ── Filter Actions ─────────────────────────────────────────────────────
  function setFilters(newFilters: Partial<ResourceFilters>) {
    filters.value = { ...filters.value, ...newFilters };
  }

  function resetFilters() {
    filters.value = { search: '', resourceType: '', status: '', parentId: '', visibility: '' };
  }

  // ── Backward Compatibility Helpers ─────────────────────────────────────
  // These allow tabs that import old Location/Room/Equipment types to keep working
  // while the store now works with the unified Resource model.

  function getLocationById(id: string): Resource | undefined {
    return locations.value.find(l => l.id === id);
  }

  function getRoomById(id: string): Resource | undefined {
    return rooms.value.find(r => r.id === id);
  }

  function getVehicleById(id: string): Resource | undefined {
    return vehicles.value.find(v => v.id === id);
  }

  function getEquipmentById(id: string): Resource | undefined {
    return equipment.value.find(e => e.id === id);
  }

  return {
    // State
    resources,
    reservations,
    availabilityRules,
    loading,
    error,
    filters,

    // Type-filtered collections
    locations,
    rooms,
    vehicles,
    equipment,

    // Filtered views
    filteredResources,
    filteredLocations,
    filteredRooms,
    filteredVehicles,
    filteredEquipment,

    // Counts
    locationCount,
    roomCount,
    vehicleCount,
    equipmentCount,
    activeCount,
    availableRoomCount,
    openLocationCount,

    // Options
    locationOptions,

    // Reservation helpers
    activeReservations,
    tentativeReservations,
    getReservationsForResource,
    isResourceAvailable,

    // Fetch
    fetchResources,
    fetchReservations,
    fetchAvailabilityRules,
    fetchAll,

    // Lookups
    getResourceById,
    getResourcesByType,
    getChildResources,
    getLocationById,
    getRoomById,
    getVehicleById,
    getEquipmentById,

    // Resource CRUD
    createResource,
    updateResource,
    deleteResource,

    // Reservation CRUD
    createReservation,
    confirmReservation,
    releaseReservation,

    // Availability Rules
    createAvailabilityRule,
    deleteAvailabilityRule,

    // Filters
    setFilters,
    resetFilters,
  };
});
