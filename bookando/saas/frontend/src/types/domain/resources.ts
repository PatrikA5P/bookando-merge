/**
 * Resources Domain Types
 *
 * SOLL-Architektur gemaess MODUL_ANALYSE.md Abschnitt 2.4
 *
 * Generische Resource-Tabelle mit Typ-Discriminator.
 * ResourceReservation mit Sperrlogik und TTL.
 * AvailabilityRule fuer wiederkehrende Verfuegbarkeit.
 */

// ============================================================================
// ENUMS
// ============================================================================

export type ResourceType = 'LOCATION' | 'ROOM' | 'VEHICLE' | 'EQUIPMENT';

export type ResourceVisibility = 'ADMIN_ONLY' | 'EMPLOYEE' | 'CUSTOMER_VISIBLE' | 'CUSTOMER_BOOKABLE';

export type ResourceStatus = 'ACTIVE' | 'MAINTENANCE' | 'RETIRED';

export type ReservationStatus = 'TENTATIVE' | 'CONFIRMED' | 'RELEASED';

// ============================================================================
// RESOURCE (Generisch)
// ============================================================================

export interface Resource {
  id: string;
  organizationId: string;
  resourceType: ResourceType;

  name: string;
  description?: string;

  /** Kapazitaet: fuer Raeume = Sitzplaetze, fuer Equipment = Stueckzahl */
  capacity: number;

  /** Hierarchie: Room → Location, Equipment → Location */
  parentId?: string;
  parentName?: string;

  /** Typ-spezifische Eigenschaften */
  properties: ResourceProperties;

  /** Sichtbarkeit/Buchbarkeit */
  visibility: ResourceVisibility;

  status: ResourceStatus;

  createdAt: string;
  updatedAt: string;
}

// ============================================================================
// RESOURCE PROPERTIES (typ-spezifisch)
// ============================================================================

export interface LocationProperties {
  address: string;
  city: string;
  zip: string;
  country: string;
  phone?: string;
  email?: string;
  roomCount?: number;
}

export interface RoomProperties {
  features: string[];
  floor?: string;
}

export interface VehicleProperties {
  licensePlate?: string;
  brand?: string;
  model?: string;
  category?: string; // z.B. 'Motorrad', 'Auto', 'Lieferwagen'
  color?: string;
}

export interface EquipmentProperties {
  category: string;
  condition: 'GOOD' | 'FAIR' | 'POOR';
  totalUnits: number;
  availableUnits: number;
  serialNumber?: string;
}

export type ResourceProperties =
  | LocationProperties
  | RoomProperties
  | VehicleProperties
  | EquipmentProperties
  | Record<string, unknown>;

// ============================================================================
// RESOURCE RESERVATION (Sperrung/Buchung)
// ============================================================================

export interface ResourceReservation {
  id: string;
  resourceId: string;
  resourceName?: string;

  /** Zeitfenster */
  startsAt: string;
  endsAt: string;

  /** Quelle der Reservierung */
  bookingId?: string;
  sessionId?: string;
  manualReason?: string;

  status: ReservationStatus;

  /** TTL fuer tentative Reservierungen */
  expiresAt?: string;

  createdAt: string;
}

// ============================================================================
// AVAILABILITY RULES (Wiederkehrend)
// ============================================================================

export interface ResourceAvailabilityRule {
  id: string;
  resourceId: string;

  /** Wochentag-basiert: 0=Mo, 6=So */
  dayOfWeek: number;
  startTime: string; // HH:mm
  endTime: string;   // HH:mm

  /** Gueltigkeitszeitraum */
  validFrom: string;
  validUntil?: string;
}

// ============================================================================
// FORM DATA
// ============================================================================

export type ResourceFormData = Omit<Resource, 'id' | 'organizationId' | 'createdAt' | 'updatedAt' | 'parentName'>;

export interface ResourceReservationFormData {
  resourceId: string;
  startsAt: string;
  endsAt: string;
  bookingId?: string;
  sessionId?: string;
  manualReason?: string;
}

// ============================================================================
// TYPE GUARDS
// ============================================================================

export function isLocation(resource: Resource): boolean {
  return resource.resourceType === 'LOCATION';
}

export function isRoom(resource: Resource): boolean {
  return resource.resourceType === 'ROOM';
}

export function isVehicle(resource: Resource): boolean {
  return resource.resourceType === 'VEHICLE';
}

export function isEquipment(resource: Resource): boolean {
  return resource.resourceType === 'EQUIPMENT';
}

export function isLocationProperties(props: ResourceProperties): props is LocationProperties {
  return 'address' in props && 'city' in props;
}

export function isRoomProperties(props: ResourceProperties): props is RoomProperties {
  return 'features' in props && Array.isArray((props as RoomProperties).features);
}

export function isVehicleProperties(props: ResourceProperties): props is VehicleProperties {
  return 'licensePlate' in props || 'brand' in props;
}

export function isEquipmentProperties(props: ResourceProperties): props is EquipmentProperties {
  return 'condition' in props && 'totalUnits' in props;
}
