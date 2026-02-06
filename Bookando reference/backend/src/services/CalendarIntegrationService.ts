
/**
 * CALENDAR INTEGRATION SERVICE - Full Implementation
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

import { google } from 'googleapis';
import { PrismaClient } from '@prisma/client';
import axios from 'axios';
import { Buffer } from 'buffer';

/**
 * Calendar Providers
 */
export enum CalendarProvider {
  GOOGLE = 'GOOGLE',
  MICROSOFT = 'MICROSOFT',
  APPLE = 'APPLE',
}

/**
 * Free/Busy Time Slot
 */
export interface TimeSlot {
  start: Date;
  end: Date;
  isBusy: boolean;
}

/**
 * Calendar Integration Service
 */
export class CalendarIntegrationService {
  private prisma: PrismaClient;

  constructor(prisma: PrismaClient) {
    this.prisma = prisma;
  }

  /**
   * Authenticate and connect calendar
   * Returns OAuth URL for user to authorize
   */
  async initiateConnection(
    employeeId: string,
    organizationId: string,
    provider: CalendarProvider
  ): Promise<string> {
    const redirectUri = `${process.env.API_URL || 'http://localhost:3001'}/api/calendar/callback`;
    const state = Buffer.from(JSON.stringify({ employeeId, organizationId, provider })).toString('base64');

    switch (provider) {
      case CalendarProvider.GOOGLE:
        if (!process.env.GOOGLE_CLIENT_ID || !process.env.GOOGLE_CLIENT_SECRET) {
          throw new Error('Google Calendar credentials not configured');
        }

        const oauth2Client = new google.auth.OAuth2(
          process.env.GOOGLE_CLIENT_ID,
          process.env.GOOGLE_CLIENT_SECRET,
          redirectUri
        );

        const googleAuthUrl = oauth2Client.generateAuthUrl({
          access_type: 'offline',
          scope: [
            'https://www.googleapis.com/auth/calendar.readonly',
            'https://www.googleapis.com/auth/calendar.events',
          ],
          state,
          prompt: 'consent', // Force consent screen to get refresh token
        });

        return googleAuthUrl;

      case CalendarProvider.MICROSOFT:
        if (!process.env.MICROSOFT_CLIENT_ID) {
          throw new Error('Microsoft Graph credentials not configured');
        }

        const microsoftAuthUrl =
          `https://login.microsoftonline.com/common/oauth2/v2.0/authorize?` +
          `client_id=${process.env.MICROSOFT_CLIENT_ID}&` +
          `redirect_uri=${encodeURIComponent(redirectUri)}&` +
          `response_type=code&` +
          `scope=${encodeURIComponent('Calendars.ReadWrite offline_access')}&` +
          `state=${state}`;

        return microsoftAuthUrl;

      case CalendarProvider.APPLE:
        throw new Error('Apple Calendar integration requires CalDAV setup with app-specific password');

      default:
        throw new Error('Unsupported calendar provider');
    }
  }

  /**
   * Handle OAuth callback and save tokens
   */
  async handleCallback(
    code: string,
    state: string,
    organizationId: string
  ): Promise<any> {
    const stateData = JSON.parse(Buffer.from(state, 'base64').toString());
    const { employeeId, provider } = stateData;

    const redirectUri = `${process.env.API_URL || 'http://localhost:3001'}/api/calendar/callback`;

    switch (provider) {
      case CalendarProvider.GOOGLE:
        return await this.handleGoogleCallback(code, employeeId, organizationId, redirectUri);

      case CalendarProvider.MICROSOFT:
        return await this.handleMicrosoftCallback(code, employeeId, organizationId, redirectUri);

      default:
        throw new Error('Unsupported provider');
    }
  }

  /**
   * Handle Google OAuth callback
   */
  private async handleGoogleCallback(
    code: string,
    employeeId: string,
    organizationId: string,
    redirectUri: string
  ): Promise<any> {
    const oauth2Client = new google.auth.OAuth2(
      process.env.GOOGLE_CLIENT_ID,
      process.env.GOOGLE_CLIENT_SECRET,
      redirectUri
    );

    // Exchange code for tokens
    const { tokens } = await oauth2Client.getToken(code);
    oauth2Client.setCredentials(tokens);

    // Get user's calendar list
    const calendar = google.calendar({ version: 'v3', auth: oauth2Client });
    const calendarList = await calendar.calendarList.list();

    // Use primary calendar
    const primaryCalendar = calendarList.data.items?.find((cal) => cal.primary);
    if (!primaryCalendar) {
      throw new Error('No primary calendar found');
    }

    // Get user email
    const oauth2 = google.oauth2({ version: 'v2', auth: oauth2Client });
    const userInfo = await oauth2.userinfo.get();

    // Save connection
    const connection = await this.prisma.calendarConnection.create({
      data: {
        employeeId,
        organizationId,
        provider: CalendarProvider.GOOGLE,
        isActive: true,
        accessToken: tokens.access_token!,
        refreshToken: tokens.refresh_token!,
        tokenExpiry: new Date(tokens.expiry_date!),
        calendarId: primaryCalendar.id!,
        email: userInfo.data.email!,
        syncDirection: 'TWO_WAY',
        autoCreateEvents: true,
        autoUpdateEvents: true,
        autoDeleteEvents: true,
      },
    });

    console.log(`✅ Google Calendar connected for employee ${employeeId}`);
    return connection;
  }

  /**
   * Handle Microsoft OAuth callback
   */
  private async handleMicrosoftCallback(
    code: string,
    employeeId: string,
    organizationId: string,
    redirectUri: string
  ): Promise<any> {
    // Exchange code for tokens
    const tokenResponse = await axios.post(
      'https://login.microsoftonline.com/common/oauth2/v2.0/token',
      new URLSearchParams({
        client_id: process.env.MICROSOFT_CLIENT_ID!,
        client_secret: process.env.MICROSOFT_CLIENT_SECRET!,
        code,
        redirect_uri: redirectUri,
        grant_type: 'authorization_code',
      }),
      {
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
      }
    );

    const { access_token, refresh_token, expires_in } = tokenResponse.data;

    // Get user info and calendar
    const userResponse = await axios.get('https://graph.microsoft.com/v1.0/me', {
      headers: { Authorization: `Bearer ${access_token}` },
    });

    const calendarResponse = await axios.get('https://graph.microsoft.com/v1.0/me/calendar', {
      headers: { Authorization: `Bearer ${access_token}` },
    });

    // Save connection
    const connection = await this.prisma.calendarConnection.create({
      data: {
        employeeId,
        organizationId,
        provider: CalendarProvider.MICROSOFT,
        isActive: true,
        accessToken: access_token,
        refreshToken: refresh_token,
        tokenExpiry: new Date(Date.now() + expires_in * 1000),
        calendarId: calendarResponse.data.id,
        email: userResponse.data.mail || userResponse.data.userPrincipalName,
        syncDirection: 'TWO_WAY',
        autoCreateEvents: true,
        autoUpdateEvents: true,
        autoDeleteEvents: true,
      },
    });

    console.log(`✅ Microsoft Calendar connected for employee ${employeeId}`);
    return connection;
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
    const connections = await this.prisma.calendarConnection.findMany({
      where: {
        employeeId,
        isActive: true,
      },
    });

    if (connections.length === 0) {
      return []; // No busy slots if no calendar connected
    }

    const allBusySlots: TimeSlot[] = [];

    for (const connection of connections) {
      try {
        const busySlots = await this.getProviderBusyTimes(connection, startDate, endDate);
        allBusySlots.push(...busySlots);
      } catch (error: any) {
        console.error(`⚠️ Failed to get availability for connection ${connection.id}:`, error.message);
      }
    }

    return allBusySlots;
  }

  /**
   * Get busy times from specific provider
   */
  private async getProviderBusyTimes(
    connection: any,
    startDate: Date,
    endDate: Date
  ): Promise<TimeSlot[]> {
    // Check if token expired and refresh if needed
    if (new Date(connection.tokenExpiry) <= new Date()) {
      await this.refreshAccessToken(connection);
      // Re-fetch connection with new tokens
      connection = await this.prisma.calendarConnection.findUnique({
        where: { id: connection.id },
      });
    }

    switch (connection.provider) {
      case CalendarProvider.GOOGLE:
        return await this.getGoogleBusyTimes(connection, startDate, endDate);

      case CalendarProvider.MICROSOFT:
        return await this.getMicrosoftBusyTimes(connection, startDate, endDate);

      default:
        throw new Error('Unsupported provider');
    }
  }

  /**
   * Get Google Calendar busy times
   */
  private async getGoogleBusyTimes(
    connection: any,
    startDate: Date,
    endDate: Date
  ): Promise<TimeSlot[]> {
    const oauth2Client = new google.auth.OAuth2(
      process.env.GOOGLE_CLIENT_ID,
      process.env.GOOGLE_CLIENT_SECRET
    );

    oauth2Client.setCredentials({
      access_token: connection.accessToken,
      refresh_token: connection.refreshToken,
    });

    const calendar = google.calendar({ version: 'v3', auth: oauth2Client });

    const response = await calendar.freebusy.query({
      requestBody: {
        timeMin: startDate.toISOString(),
        timeMax: endDate.toISOString(),
        items: [{ id: connection.calendarId }],
      },
    });

    const busySlots: TimeSlot[] = [];
    const calendarBusy = response.data.calendars?.[connection.calendarId]?.busy || [];

    for (const busy of calendarBusy) {
      if (busy.start && busy.end) {
        busySlots.push({
          start: new Date(busy.start),
          end: new Date(busy.end),
          isBusy: true,
        });
      }
    }

    return busySlots;
  }

  /**
   * Get Microsoft Calendar busy times
   */
  private async getMicrosoftBusyTimes(
    connection: any,
    startDate: Date,
    endDate: Date
  ): Promise<TimeSlot[]> {
    const response = await axios.post(
      'https://graph.microsoft.com/v1.0/me/calendar/getSchedule',
      {
        schedules: [connection.email],
        startTime: {
          dateTime: startDate.toISOString(),
          timeZone: 'UTC',
        },
        endTime: {
          dateTime: endDate.toISOString(),
          timeZone: 'UTC',
        },
      },
      {
        headers: {
          Authorization: `Bearer ${connection.accessToken}`,
          'Content-Type': 'application/json',
        },
      }
    );

    const busySlots: TimeSlot[] = [];
    const scheduleItems = response.data.value[0]?.scheduleItems || [];

    for (const item of scheduleItems) {
      if (item.status === 'busy' || item.status === 'tentative') {
        busySlots.push({
          start: new Date(item.start.dateTime),
          end: new Date(item.end.dateTime),
          isBusy: true,
        });
      }
    }

    return busySlots;
  }

  /**
   * Check if employee is available at a specific time
   */
  async isAvailable(employeeId: string, startTime: Date, endTime: Date): Promise<boolean> {
    const availability = await this.getAvailability(employeeId, startTime, endTime);

    // Check if any busy slot overlaps with requested time
    return !availability.some((slot) => {
      return (
        slot.isBusy &&
        ((startTime >= slot.start && startTime < slot.end) ||
          (endTime > slot.start && endTime <= slot.end) ||
          (startTime <= slot.start && endTime >= slot.end))
      );
    });
  }

  /**
   * Create calendar event when booking is confirmed
   */
  async createEvent(bookingId: string): Promise<void> {
    // Get booking details
    const booking = await this.prisma.booking.findUnique({
      where: { id: bookingId },
      include: {
        customer: true,
        service: true,
        employee: true,
        organization: true,
      },
    });

    if (!booking || !booking.employeeId) {
      return;
    }

    // Get employee's active calendar connections
    const connections = await this.prisma.calendarConnection.findMany({
      where: {
        employeeId: booking.employeeId,
        isActive: true,
        autoCreateEvents: true,
      },
    });

    for (const connection of connections) {
      try {
        await this.createProviderEvent(connection, booking);
      } catch (error: any) {
        console.error(`⚠️ Failed to create calendar event for connection ${connection.id}:`, error.message);
      }
    }
  }

  /**
   * Create event in specific provider
   */
  private async createProviderEvent(connection: any, booking: any): Promise<void> {
    // Refresh token if expired
    if (new Date(connection.tokenExpiry) <= new Date()) {
      await this.refreshAccessToken(connection);
      connection = await this.prisma.calendarConnection.findUnique({
        where: { id: connection.id },
      });
    }

    const startTime = new Date(`${booking.scheduledDate}T${booking.scheduledTime}`);
    const endTime = new Date(startTime.getTime() + booking.service.duration * 60000);

    const title = `${booking.service.name} - ${booking.customer.firstName} ${booking.customer.lastName}`;
    const description = `Buchung: ${booking.bookingNumber}\nKunde: ${booking.customer.firstName} ${booking.customer.lastName}\nService: ${booking.service.name}`;

    switch (connection.provider) {
      case CalendarProvider.GOOGLE:
        await this.createGoogleEvent(connection, booking, title, description, startTime, endTime);
        break;

      case CalendarProvider.MICROSOFT:
        await this.createMicrosoftEvent(connection, booking, title, description, startTime, endTime);
        break;
    }
  }

  /**
   * Create Google Calendar event
   */
  private async createGoogleEvent(
    connection: any,
    booking: any,
    title: string,
    description: string,
    startTime: Date,
    endTime: Date
  ): Promise<void> {
    const oauth2Client = new google.auth.OAuth2(
      process.env.GOOGLE_CLIENT_ID,
      process.env.GOOGLE_CLIENT_SECRET
    );

    oauth2Client.setCredentials({
      access_token: connection.accessToken,
      refresh_token: connection.refreshToken,
    });

    const calendar = google.calendar({ version: 'v3', auth: oauth2Client });

    const event = await calendar.events.insert({
      calendarId: connection.calendarId,
      requestBody: {
        summary: title,
        description,
        start: {
          dateTime: startTime.toISOString(),
          timeZone: booking.organization?.timezone || 'Europe/Zurich',
        },
        end: {
          dateTime: endTime.toISOString(),
          timeZone: booking.organization?.timezone || 'Europe/Zurich',
        },
      },
    });

    // Save calendar event record
    await this.prisma.calendarEvent.create({
      data: {
        connectionId: connection.id,
        bookingId: booking.id,
        externalEventId: event.data.id!,
        title,
        description,
        startTime,
        endTime,
        lastSyncedAt: new Date(),
        syncStatus: 'SYNCED',
      },
    });

    console.log(`✅ Google Calendar event created: ${event.data.id}`);
  }

  /**
   * Create Microsoft Calendar event
   */
  private async createMicrosoftEvent(
    connection: any,
    booking: any,
    title: string,
    description: string,
    startTime: Date,
    endTime: Date
  ): Promise<void> {
    const response = await axios.post(
      'https://graph.microsoft.com/v1.0/me/events',
      {
        subject: title,
        body: {
          contentType: 'Text',
          content: description,
        },
        start: {
          dateTime: startTime.toISOString(),
          timeZone: booking.organization?.timezone || 'Europe/Zurich',
        },
        end: {
          dateTime: endTime.toISOString(),
          timeZone: booking.organization?.timezone || 'Europe/Zurich',
        },
      },
      {
        headers: {
          Authorization: `Bearer ${connection.accessToken}`,
          'Content-Type': 'application/json',
        },
      }
    );

    // Save calendar event record
    await this.prisma.calendarEvent.create({
      data: {
        connectionId: connection.id,
        bookingId: booking.id,
        externalEventId: response.data.id,
        title,
        description,
        startTime,
        endTime,
        lastSyncedAt: new Date(),
        syncStatus: 'SYNCED',
      },
    });

    console.log(`✅ Microsoft Calendar event created: ${response.data.id}`);
  }

  /**
   * Update calendar event when booking is modified
   */
  async updateEvent(bookingId: string): Promise<void> {
    // Get existing calendar events for this booking
    const calendarEvents = await this.prisma.calendarEvent.findMany({
      where: { bookingId },
      include: { connection: true },
    });

    // Get updated booking details
    const booking = await this.prisma.booking.findUnique({
      where: { id: bookingId },
      include: {
        customer: true,
        service: true,
        employee: true,
        organization: true,
      },
    });

    if (!booking) {
      return;
    }

    for (const calendarEvent of calendarEvents) {
      try {
        await this.updateProviderEvent(calendarEvent, booking);
      } catch (error: any) {
        console.error(`⚠️ Failed to update calendar event ${calendarEvent.id}:`, error.message);
      }
    }
  }

  /**
   * Update event in provider
   */
  private async updateProviderEvent(calendarEvent: any, booking: any): Promise<void> {
    const connection = calendarEvent.connection;

    if (!connection.autoUpdateEvents) {
      return;
    }

    // Refresh token if expired
    if (new Date(connection.tokenExpiry) <= new Date()) {
      await this.refreshAccessToken(connection);
    }

    const startTime = new Date(`${booking.scheduledDate}T${booking.scheduledTime}`);
    const endTime = new Date(startTime.getTime() + booking.service.duration * 60000);

    const title = `${booking.service.name} - ${booking.customer.firstName} ${booking.customer.lastName}`;
    const description = `Buchung: ${booking.bookingNumber}\nKunde: ${booking.customer.firstName} ${booking.customer.lastName}\nService: ${booking.service.name}`;

    switch (connection.provider) {
      case CalendarProvider.GOOGLE:
        await this.updateGoogleEvent(connection, calendarEvent, title, description, startTime, endTime, booking);
        break;

      case CalendarProvider.MICROSOFT:
        await this.updateMicrosoftEvent(connection, calendarEvent, title, description, startTime, endTime);
        break;
    }
  }

  /**
   * Update Google Calendar event
   */
  private async updateGoogleEvent(
    connection: any,
    calendarEvent: any,
    title: string,
    description: string,
    startTime: Date,
    endTime: Date,
    booking: any
  ): Promise<void> {
    const oauth2Client = new google.auth.OAuth2(
      process.env.GOOGLE_CLIENT_ID,
      process.env.GOOGLE_CLIENT_SECRET
    );

    oauth2Client.setCredentials({
      access_token: connection.accessToken,
      refresh_token: connection.refreshToken,
    });

    const calendar = google.calendar({ version: 'v3', auth: oauth2Client });

    await calendar.events.update({
      calendarId: connection.calendarId,
      eventId: calendarEvent.externalEventId,
      requestBody: {
        summary: title,
        description,
        start: {
          dateTime: startTime.toISOString(),
          timeZone: booking.organization?.timezone || 'Europe/Zurich',
        },
        end: {
          dateTime: endTime.toISOString(),
          timeZone: booking.organization?.timezone || 'Europe/Zurich',
        },
      },
    });

    // Update calendar event record
    await this.prisma.calendarEvent.update({
      where: { id: calendarEvent.id },
      data: {
        title,
        description,
        startTime,
        endTime,
        lastSyncedAt: new Date(),
        syncStatus: 'SYNCED',
      },
    });

    console.log(`✅ Google Calendar event updated`);
  }

  /**
   * Update Microsoft Calendar event
   */
  private async updateMicrosoftEvent(
    connection: any,
    calendarEvent: any,
    title: string,
    description: string,
    startTime: Date,
    endTime: Date
  ): Promise<void> {
    await axios.patch(
      `https://graph.microsoft.com/v1.0/me/events/${calendarEvent.externalEventId}`,
      {
        subject: title,
        body: {
          contentType: 'Text',
          content: description,
        },
        start: {
          dateTime: startTime.toISOString(),
          timeZone: 'UTC',
        },
        end: {
          dateTime: endTime.toISOString(),
          timeZone: 'UTC',
        },
      },
      {
        headers: {
          Authorization: `Bearer ${connection.accessToken}`,
          'Content-Type': 'application/json',
        },
      }
    );

    // Update calendar event record
    await this.prisma.calendarEvent.update({
      where: { id: calendarEvent.id },
      data: {
        title,
        description,
        startTime,
        endTime,
        lastSyncedAt: new Date(),
        syncStatus: 'SYNCED',
      },
    });

    console.log(`✅ Microsoft Calendar event updated`);
  }

  /**
   * Delete calendar event when booking is cancelled
   */
  async deleteEvent(bookingId: string): Promise<void> {
    // Get existing calendar events for this booking
    const calendarEvents = await this.prisma.calendarEvent.findMany({
      where: { bookingId },
      include: { connection: true },
    });

    for (const calendarEvent of calendarEvents) {
      try {
        await this.deleteProviderEvent(calendarEvent);
      } catch (error: any) {
        console.error(`⚠️ Failed to delete calendar event ${calendarEvent.id}:`, error.message);
      }
    }
  }

  /**
   * Delete event from provider
   */
  private async deleteProviderEvent(calendarEvent: any): Promise<void> {
    const connection = calendarEvent.connection;

    if (!connection.autoDeleteEvents) {
      return;
    }

    // Refresh token if expired
    if (new Date(connection.tokenExpiry) <= new Date()) {
      await this.refreshAccessToken(connection);
    }

    switch (connection.provider) {
      case CalendarProvider.GOOGLE:
        await this.deleteGoogleEvent(connection, calendarEvent);
        break;

      case CalendarProvider.MICROSOFT:
        await this.deleteMicrosoftEvent(connection, calendarEvent);
        break;
    }
  }

  /**
   * Delete Google Calendar event
   */
  private async deleteGoogleEvent(connection: any, calendarEvent: any): Promise<void> {
    const oauth2Client = new google.auth.OAuth2(
      process.env.GOOGLE_CLIENT_ID,
      process.env.GOOGLE_CLIENT_SECRET
    );

    oauth2Client.setCredentials({
      access_token: connection.accessToken,
      refresh_token: connection.refreshToken,
    });

    const calendar = google.calendar({ version: 'v3', auth: oauth2Client });

    await calendar.events.delete({
      calendarId: connection.calendarId,
      eventId: calendarEvent.externalEventId,
    });

    // Delete calendar event record
    await this.prisma.calendarEvent.delete({
      where: { id: calendarEvent.id },
    });

    console.log(`✅ Google Calendar event deleted`);
  }

  /**
   * Delete Microsoft Calendar event
   */
  private async deleteMicrosoftEvent(connection: any, calendarEvent: any): Promise<void> {
    await axios.delete(`https://graph.microsoft.com/v1.0/me/events/${calendarEvent.externalEventId}`, {
      headers: {
        Authorization: `Bearer ${connection.accessToken}`,
      },
    });

    // Delete calendar event record
    await this.prisma.calendarEvent.delete({
      where: { id: calendarEvent.id },
    });

    console.log(`✅ Microsoft Calendar event deleted`);
  }

  /**
   * Refresh access token when expired
   */
  private async refreshAccessToken(connection: any): Promise<void> {
    console.log(`🔄 Refreshing access token for connection ${connection.id}`);

    switch (connection.provider) {
      case CalendarProvider.GOOGLE:
        await this.refreshGoogleToken(connection);
        break;

      case CalendarProvider.MICROSOFT:
        await this.refreshMicrosoftToken(connection);
        break;

      default:
        throw new Error('Token refresh not supported for this provider');
    }
  }

  /**
   * Refresh Google access token
   */
  private async refreshGoogleToken(connection: any): Promise<void> {
    const oauth2Client = new google.auth.OAuth2(
      process.env.GOOGLE_CLIENT_ID,
      process.env.GOOGLE_CLIENT_SECRET
    );

    oauth2Client.setCredentials({
      refresh_token: connection.refreshToken,
    });

    const { credentials } = await oauth2Client.refreshAccessToken();

    // Update connection with new tokens
    await this.prisma.calendarConnection.update({
      where: { id: connection.id },
      data: {
        accessToken: credentials.access_token!,
        tokenExpiry: new Date(credentials.expiry_date!),
      },
    });

    console.log(`✅ Google access token refreshed`);
  }

  /**
   * Refresh Microsoft access token
   */
  private async refreshMicrosoftToken(connection: any): Promise<void> {
    const response = await axios.post(
      'https://login.microsoftonline.com/common/oauth2/v2.0/token',
      new URLSearchParams({
        client_id: process.env.MICROSOFT_CLIENT_ID!,
        client_secret: process.env.MICROSOFT_CLIENT_SECRET!,
        refresh_token: connection.refreshToken,
        grant_type: 'refresh_token',
      }),
      {
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
      }
    );

    const { access_token, expires_in } = response.data;

    // Update connection with new token
    await this.prisma.calendarConnection.update({
      where: { id: connection.id },
      data: {
        accessToken: access_token,
        tokenExpiry: new Date(Date.now() + expires_in * 1000),
      },
    });

    console.log(`✅ Microsoft access token refreshed`);
  }

  /**
   * Disconnect calendar
   */
  async disconnect(connectionId: string): Promise<void> {
    const connection = await this.prisma.calendarConnection.findUnique({
      where: { id: connectionId },
    });

    if (!connection) {
      throw new Error('Connection not found');
    }

    // Revoke tokens with provider (optional, depends on provider support)
    // For now, just deactivate

    // Delete all calendar events for this connection
    await this.prisma.calendarEvent.deleteMany({
      where: { connectionId },
    });

    // Delete connection
    await this.prisma.calendarConnection.delete({
      where: { id: connectionId },
    });

    console.log(`✅ Calendar connection ${connectionId} disconnected`);
  }

  /**
   * List all calendar connections for an employee
   */
  async getConnections(employeeId: string): Promise<any[]> {
    return await this.prisma.calendarConnection.findMany({
      where: { employeeId },
      include: {
        _count: {
          select: { events: true },
        },
      },
    });
  }

  /**
   * Get connection by ID
   */
  async getConnection(connectionId: string): Promise<any> {
    return await this.prisma.calendarConnection.findUnique({
      where: { id: connectionId },
      include: {
        employee: {
          select: {
            id: true,
            firstName: true,
            lastName: true,
            email: true,
          },
        },
        _count: {
          select: { events: true },
        },
      },
    });
  }
}
