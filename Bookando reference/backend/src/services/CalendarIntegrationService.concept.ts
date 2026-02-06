/**
 * CALENDAR INTEGRATION - Konzept & Architektur
 *
 * Ähnlich wie Calendly: Mitarbeiter-Kalender synchronisieren
 *
 * SUPPORTED CALENDAR PROVIDERS:
 * - Google Calendar (OAuth 2.0, Calendar API v3)
 * - Outlook/Microsoft Exchange (OAuth 2.0, Microsoft Graph API)
 * - Apple Calendar (CalDAV protocol, iCloud)
 *
 * FUNKTIONALITÄT:
 * 1. Verfügbarkeit abrufen (nur Busy/Free, NICHT Eintragsdetails)
 * 2. Neue Termine schreiben bei Buchungsbestätigung
 * 3. Termine aktualisieren bei Buchungsänderung
 * 4. Termine löschen bei Stornierung
 * 5. 2-Wege-Sync: Wenn Mitarbeiter extern blockt, System erkennt Konflikt
 */

/**
 * Calendar Providers
 */
export enum CalendarProvider {
  GOOGLE = 'GOOGLE',
  MICROSOFT = 'MICROSOFT', // Outlook, Office 365, Exchange
  APPLE = 'APPLE', // iCloud Calendar
}

/**
 * Calendar Connection per Employee
 * Stored in database
 */
interface CalendarConnection {
  id: string;
  employeeId: string;
  organizationId: string;
  provider: CalendarProvider;
  isActive: boolean;

  // OAuth Tokens (encrypted in database)
  accessToken: string;
  refreshToken: string;
  tokenExpiry: Date;

  // Provider-specific identifiers
  calendarId: string; // Which calendar to use (users can have multiple)
  email: string; // Provider account email

  // Settings
  syncDirection: 'READ_ONLY' | 'WRITE_ONLY' | 'TWO_WAY';
  autoCreateEvents: boolean; // Auto-create calendar event on booking
  autoUpdateEvents: boolean; // Auto-update on booking change
  autoDeleteEvents: boolean; // Auto-delete on cancellation

  createdAt: Date;
  updatedAt: Date;
}

/**
 * Calendar Event
 * Represents a synced event
 */
interface CalendarEvent {
  id: string;
  connectionId: string;
  bookingId?: string; // Link to our booking
  externalEventId: string; // Provider's event ID

  title: string;
  description?: string;
  startTime: Date;
  endTime: Date;
  location?: string;

  // For tracking sync status
  lastSyncedAt: Date;
  syncStatus: 'SYNCED' | 'PENDING' | 'FAILED';
  syncError?: string;
}

/**
 * Free/Busy Time Slot
 */
interface TimeSlot {
  start: Date;
  end: Date;
  isBusy: boolean;
}

/**
 * Calendar Integration Service (To Be Implemented)
 *
 * This service handles all calendar synchronization logic
 */
export class CalendarIntegrationService {
  /**
   * Authenticate and connect calendar
   * Returns OAuth URL for user to authorize
   */
  async initiateConnection(
    employeeId: string,
    organizationId: string,
    provider: CalendarProvider
  ): Promise<string> {
    // TODO: Implement OAuth flow
    // 1. Generate state token (CSRF protection)
    // 2. Build OAuth URL with redirect_uri
    // 3. Return URL for frontend to redirect user
    // 4. After auth, callback will save tokens

    const redirectUri = `${process.env.API_URL}/api/calendar/callback`;

    switch (provider) {
      case CalendarProvider.GOOGLE:
        // Google OAuth: https://developers.google.com/identity/protocols/oauth2
        return `https://accounts.google.com/o/oauth2/v2/auth?` +
          `client_id=${process.env.GOOGLE_CLIENT_ID}&` +
          `redirect_uri=${redirectUri}&` +
          `response_type=code&` +
          `scope=https://www.googleapis.com/auth/calendar.readonly https://www.googleapis.com/auth/calendar.events&` +
          `access_type=offline&` +
          `state=${employeeId}`;

      case CalendarProvider.MICROSOFT:
        // Microsoft OAuth: https://docs.microsoft.com/en-us/graph/auth-v2-user
        return `https://login.microsoftonline.com/common/oauth2/v2.0/authorize?` +
          `client_id=${process.env.MICROSOFT_CLIENT_ID}&` +
          `redirect_uri=${redirectUri}&` +
          `response_type=code&` +
          `scope=Calendars.ReadWrite offline_access&` +
          `state=${employeeId}`;

      case CalendarProvider.APPLE:
        // Apple: CalDAV requires username/password or app-specific password
        // More complex, requires different approach
        throw new Error('Apple Calendar integration requires CalDAV setup');

      default:
        throw new Error('Unsupported calendar provider');
    }
  }

  /**
   * Handle OAuth callback and save tokens
   */
  async handleCallback(code: string, state: string, provider: CalendarProvider): Promise<CalendarConnection> {
    // TODO: Implement
    // 1. Exchange code for access_token and refresh_token
    // 2. Get user's calendar list
    // 3. Save connection in database (encrypt tokens!)
    // 4. Return connection
    throw new Error('Not implemented');
  }

  /**
   * Get employee's availability from their connected calendar
   * ONLY returns busy/free times, NOT event details (privacy!)
   */
  async getAvailability(
    employeeId: string,
    startDate: Date,
    endDate: Date
  ): Promise<TimeSlot[]> {
    // TODO: Implement
    // 1. Get employee's calendar connections
    // 2. For each connection, fetch free/busy info
    // 3. Merge all busy periods
    // 4. Return array of time slots

    // Example for Google Calendar API:
    // POST https://www.googleapis.com/calendar/v3/freeBusy
    // {
    //   "timeMin": "2026-01-15T00:00:00Z",
    //   "timeMax": "2026-01-15T23:59:59Z",
    //   "items": [{ "id": "primary" }]
    // }
    // Response contains calendars[].busy[] with start/end times

    throw new Error('Not implemented');
  }

  /**
   * Check if employee is available at a specific time
   * Used by booking auto-assignment
   */
  async isAvailable(
    employeeId: string,
    startTime: Date,
    endTime: Date
  ): Promise<boolean> {
    const availability = await this.getAvailability(employeeId, startTime, endTime);

    // Check if any busy slot overlaps with requested time
    return !availability.some((slot) => {
      return slot.isBusy && (
        (startTime >= slot.start && startTime < slot.end) ||
        (endTime > slot.start && endTime <= slot.end) ||
        (startTime <= slot.start && endTime >= slot.end)
      );
    });
  }

  /**
   * Create calendar event when booking is confirmed
   */
  async createEvent(bookingId: string): Promise<void> {
    // TODO: Implement
    // 1. Get booking details (service, customer, employee, time)
    // 2. Get employee's calendar connections where autoCreateEvents = true
    // 3. For each connection, create event via provider API
    // 4. Save CalendarEvent record for tracking

    // Example for Google Calendar:
    // POST https://www.googleapis.com/calendar/v3/calendars/{calendarId}/events
    // {
    //   "summary": "Fahrstunde mit Max Mustermann",
    //   "description": "Motorrad 90 Min - Buchung BKG-A7K9M2",
    //   "start": { "dateTime": "2026-01-15T14:00:00+01:00" },
    //   "end": { "dateTime": "2026-01-15T15:30:00+01:00" },
    //   "location": "Fahrschule Standort"
    // }

    throw new Error('Not implemented');
  }

  /**
   * Update calendar event when booking is modified
   */
  async updateEvent(bookingId: string): Promise<void> {
    // TODO: Implement
    // Similar to createEvent, but uses PATCH/PUT on existing event
    throw new Error('Not implemented');
  }

  /**
   * Delete calendar event when booking is cancelled
   */
  async deleteEvent(bookingId: string): Promise<void> {
    // TODO: Implement
    // 1. Find CalendarEvent records for this booking
    // 2. Delete via provider API
    // 3. Update CalendarEvent status or delete record
    throw new Error('Not implemented');
  }

  /**
   * Refresh access token when expired
   * Called automatically before API requests
   */
  private async refreshAccessToken(connection: CalendarConnection): Promise<string> {
    // TODO: Implement
    // 1. Use refresh_token to get new access_token
    // 2. Update connection in database
    // 3. Return new access_token

    // Google: POST https://oauth2.googleapis.com/token
    // Microsoft: POST https://login.microsoftonline.com/common/oauth2/v2.0/token

    throw new Error('Not implemented');
  }

  /**
   * Sync changes from external calendar (2-way sync)
   * Called periodically by cron job
   */
  async syncFromExternal(employeeId: string): Promise<void> {
    // TODO: Implement
    // 1. Get employee's connections with TWO_WAY sync
    // 2. Fetch events modified since last sync
    // 3. Check if any conflict with our bookings
    // 4. Update availability or notify admin of conflicts
    throw new Error('Not implemented');
  }

  /**
   * Disconnect calendar
   */
  async disconnect(connectionId: string): Promise<void> {
    // TODO: Implement
    // 1. Revoke OAuth tokens with provider
    // 2. Delete connection from database
    // 3. Optionally delete all synced events
    throw new Error('Not implemented');
  }

  /**
   * List all calendar connections for an employee
   */
  async getConnections(employeeId: string): Promise<CalendarConnection[]> {
    // TODO: Implement
    // Query database for connections
    throw new Error('Not implemented');
  }
}

/**
 * Integration with Booking System
 *
 * In BookingService.autoAssignEmployee(), add calendar availability check:
 *
 * const availableEmployees = employees.filter((employee) => {
 *   // Existing time conflict check with bookings
 *   const hasBookingConflict = ...;
 *
 *   // NEW: Check calendar availability
 *   const hasCalendarConflict = await calendarService.isAvailable(
 *     employee.id,
 *     startTime,
 *     endTime
 *   );
 *
 *   return !hasBookingConflict && hasCalendarConflict;
 * });
 */

/**
 * Integration with Event System
 *
 * In eventHandlers.ts:
 *
 * bookingEvents.on('booking:confirmed', async (data) => {
 *   // Create calendar event for assigned employee
 *   if (data.booking.employeeId) {
 *     await calendarService.createEvent(data.booking.id);
 *   }
 * });
 *
 * bookingEvents.on('booking:updated', async (data) => {
 *   await calendarService.updateEvent(data.booking.id);
 * });
 *
 * bookingEvents.on('booking:cancelled', async (data) => {
 *   await calendarService.deleteEvent(data.booking.id);
 * });
 */

/**
 * DATABASE SCHEMA ADDITIONS NEEDED:
 *
 * model CalendarConnection {
 *   id                String            @id @default(cuid())
 *   employeeId        String
 *   employee          Employee          @relation(fields: [employeeId], references: [id])
 *   organizationId    String
 *   provider          CalendarProvider
 *   isActive          Boolean           @default(true)
 *   accessToken       String            @db.Text // Encrypted
 *   refreshToken      String            @db.Text // Encrypted
 *   tokenExpiry       DateTime
 *   calendarId        String
 *   email             String
 *   syncDirection     String            @default("TWO_WAY")
 *   autoCreateEvents  Boolean           @default(true)
 *   autoUpdateEvents  Boolean           @default(true)
 *   autoDeleteEvents  Boolean           @default(true)
 *   lastSyncedAt      DateTime?
 *   createdAt         DateTime          @default(now())
 *   updatedAt         DateTime          @updatedAt
 *   events            CalendarEvent[]
 * }
 *
 * model CalendarEvent {
 *   id                String              @id @default(cuid())
 *   connectionId      String
 *   connection        CalendarConnection  @relation(fields: [connectionId], references: [id])
 *   bookingId         String?             @unique
 *   booking           Booking?            @relation(fields: [bookingId], references: [id])
 *   externalEventId   String
 *   title             String
 *   description       String?
 *   startTime         DateTime
 *   endTime           DateTime
 *   location          String?
 *   lastSyncedAt      DateTime
 *   syncStatus        String              @default("SYNCED")
 *   syncError         String?
 *   createdAt         DateTime            @default(now())
 *   updatedAt         DateTime            @updatedAt
 * }
 *
 * enum CalendarProvider {
 *   GOOGLE
 *   MICROSOFT
 *   APPLE
 * }
 */

/**
 * REQUIRED ENVIRONMENT VARIABLES:
 *
 * # Google Calendar
 * GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
 * GOOGLE_CLIENT_SECRET=your-client-secret
 *
 * # Microsoft Graph (Outlook/Office 365)
 * MICROSOFT_CLIENT_ID=your-application-id
 * MICROSOFT_CLIENT_SECRET=your-client-secret
 * MICROSOFT_TENANT_ID=common (or specific tenant)
 *
 * # Apple Calendar (CalDAV)
 * # Requires different approach - app-specific passwords
 */

/**
 * REQUIRED NPM PACKAGES:
 *
 * npm install googleapis          // Google Calendar API
 * npm install @microsoft/microsoft-graph-client  // Microsoft Graph
 * npm install tsdav              // CalDAV for Apple Calendar
 */

export const calendarIntegrationService = new CalendarIntegrationService();
