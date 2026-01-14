<!-- EventListPreview.vue - Live Preview Component for Event List -->
<template>
  <div
    class="event-list-preview"
    :style="cssVariables"
  >
    <!-- Header -->
    <div class="preview-header">
      <h2 class="header-title">
        Kommende Veranstaltungen
      </h2>
      <div class="header-actions">
        <select class="month-select">
          <option>Januar 2025</option>
          <option selected>
            Februar 2025
          </option>
          <option>März 2025</option>
        </select>
        <button class="calendar-toggle">
          <svg
            width="20"
            height="20"
            viewBox="0 0 24 24"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <rect
              x="3"
              y="4"
              width="18"
              height="18"
              rx="2"
              stroke="currentColor"
              stroke-width="2"
            />
            <path
              d="M16 2V6"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
            />
            <path
              d="M8 2V6"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
            />
            <path
              d="M3 10H21"
              stroke="currentColor"
              stroke-width="2"
            />
          </svg>
        </button>
      </div>
    </div>

    <!-- Event List -->
    <div class="event-list">
      <!-- Event Item 1 -->
      <div class="event-item featured">
        <div class="event-date-badge">
          <div class="badge-day">
            15
          </div>
          <div class="badge-month">
            Feb
          </div>
        </div>

        <div class="event-content">
          <div class="event-header">
            <div class="event-time">
              <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
              >
                <circle
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="2"
                />
                <path
                  d="M12 6V12L16 14"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                />
              </svg>
              18:00 - 21:00 Uhr
            </div>
            <div class="event-badge">
              Beliebt
            </div>
          </div>

          <h3 class="event-title">
            Live Jazz Konzert
          </h3>
          <p class="event-description">
            Genießen Sie einen Abend mit erstklassigem Jazz von lokalen Künstlern. Erleben Sie die Magie der Improvisation in gemütlicher Atmosphäre.
          </p>

          <div class="event-meta">
            <div class="meta-item">
              <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path
                  d="M21 10C21 17 12 23 12 23C12 23 3 17 3 10C3 7.61305 3.94821 5.32387 5.63604 3.63604C7.32387 1.94821 9.61305 1 12 1C14.3869 1 16.6761 1.94821 18.364 3.63604C20.0518 5.32387 21 7.61305 21 10Z"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
                <circle
                  cx="12"
                  cy="10"
                  r="3"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>
              <span>Konzerthalle München</span>
            </div>
            <div class="meta-item">
              <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path
                  d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
                <circle
                  cx="9"
                  cy="7"
                  r="4"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
                <path
                  d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
                <path
                  d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>
              <span>45/80 Tickets verfügbar</span>
            </div>
            <div class="meta-item">
              <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path
                  d="M20 7H4C2.89543 7 2 7.89543 2 9V19C2 20.1046 2.89543 21 4 21H20C21.1046 21 22 20.1046 22 19V9C22 7.89543 21.1046 7 20 7Z"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
                <path
                  d="M16 21V5C16 4.46957 15.7893 3.96086 15.4142 3.58579C15.0391 3.21071 14.5304 3 14 3H10C9.46957 3 8.96086 3.21071 8.58579 3.58579C8.21071 3.96086 8 4.46957 8 5V21"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>
              <span>Kategorie: Musik</span>
            </div>
          </div>

          <div class="event-footer">
            <div class="event-price">
              <span class="price-label">Ab</span>
              <span class="price-value">49,00 €</span>
            </div>
            <button class="event-button">
              Tickets buchen
            </button>
          </div>
        </div>
      </div>

      <!-- Event Item 2 -->
      <div class="event-item">
        <div class="event-date-badge">
          <div class="badge-day">
            22
          </div>
          <div class="badge-month">
            Feb
          </div>
        </div>

        <div class="event-content">
          <div class="event-header">
            <div class="event-time">
              <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
              >
                <circle
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="2"
                />
                <path
                  d="M12 6V12L16 14"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                />
              </svg>
              14:00 - 18:00 Uhr
            </div>
          </div>

          <h3 class="event-title">
            Yoga Workshop für Fortgeschrittene
          </h3>
          <p class="event-description">
            Vertiefen Sie Ihre Yoga-Praxis mit fortgeschrittenen Asanas und Atemtechniken. Für Teilnehmer mit mindestens 1 Jahr Erfahrung.
          </p>

          <div class="event-meta">
            <div class="meta-item">
              <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path
                  d="M21 10C21 17 12 23 12 23C12 23 3 17 3 10C3 7.61305 3.94821 5.32387 5.63604 3.63604C7.32387 1.94821 9.61305 1 12 1C14.3869 1 16.6761 1.94821 18.364 3.63604C20.0518 5.32387 21 7.61305 21 10Z"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
                <circle
                  cx="12"
                  cy="10"
                  r="3"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>
              <span>Yoga Studio Berlin</span>
            </div>
            <div class="meta-item">
              <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path
                  d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
                <circle
                  cx="9"
                  cy="7"
                  r="4"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
                <path
                  d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
                <path
                  d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>
              <span>8/15 Tickets verfügbar</span>
            </div>
            <div class="meta-item">
              <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path
                  d="M20 7H4C2.89543 7 2 7.89543 2 9V19C2 20.1046 2.89543 21 4 21H20C21.1046 21 22 20.1046 22 19V9C22 7.89543 21.1046 7 20 7Z"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
                <path
                  d="M16 21V5C16 4.46957 15.7893 3.96086 15.4142 3.58579C15.0391 3.21071 14.5304 3 14 3H10C9.46957 3 8.96086 3.21071 8.58579 3.58579C8.21071 3.96086 8 4.46957 8 5V21"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>
              <span>Kategorie: Wellness</span>
            </div>
          </div>

          <div class="event-footer">
            <div class="event-price">
              <span class="price-value">89,00 €</span>
            </div>
            <button class="event-button">
              Tickets buchen
            </button>
          </div>
        </div>
      </div>

      <!-- Event Item 3 -->
      <div class="event-item">
        <div class="event-date-badge gradient-bg">
          <div class="badge-day">
            28
          </div>
          <div class="badge-month">
            Feb
          </div>
        </div>

        <div class="event-content">
          <div class="event-header">
            <div class="event-time">
              <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
              >
                <circle
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="2"
                />
                <path
                  d="M12 6V12L16 14"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                />
              </svg>
              19:30 - 22:00 Uhr
            </div>
          </div>

          <h3 class="event-title">
            Kulinarisches Dinner Event
          </h3>
          <p class="event-description">
            Ein 5-Gänge-Menü mit saisonalen Zutaten von unserem Sternekoch. Ein unvergessliches kulinarisches Erlebnis.
          </p>

          <div class="event-meta">
            <div class="meta-item">
              <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path
                  d="M21 10C21 17 12 23 12 23C12 23 3 17 3 10C3 7.61305 3.94821 5.32387 5.63604 3.63604C7.32387 1.94821 9.61305 1 12 1C14.3869 1 16.6761 1.94821 18.364 3.63604C20.0518 5.32387 21 7.61305 21 10Z"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
                <circle
                  cx="12"
                  cy="10"
                  r="3"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>
              <span>Restaurant Le Gourmet</span>
            </div>
            <div class="meta-item">
              <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path
                  d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
                <circle
                  cx="9"
                  cy="7"
                  r="4"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
                <path
                  d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
                <path
                  d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>
              <span>12/30 Tickets verfügbar</span>
            </div>
            <div class="meta-item">
              <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path
                  d="M20 7H4C2.89543 7 2 7.89543 2 9V19C2 20.1046 2.89543 21 4 21H20C21.1046 21 22 20.1046 22 19V9C22 7.89543 21.1046 7 20 7Z"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
                <path
                  d="M16 21V5C16 4.46957 15.7893 3.96086 15.4142 3.58579C15.0391 3.21071 14.5304 3 14 3H10C9.46957 3 8.96086 3.21071 8.58579 3.58579C8.21071 3.96086 8 4.46957 8 5V21"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>
              <span>Kategorie: Kulinarik</span>
            </div>
          </div>

          <div class="event-footer">
            <div class="event-price">
              <span class="price-value">129,00 €</span>
            </div>
            <button class="event-button">
              Tickets buchen
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  template: any
  cssVariables: Record<string, string>
}>()

const cssVariables = computed(() => props.cssVariables)
</script>

<style lang="scss">
.event-list-preview {
  padding: 32px;
  min-height: 600px;
  background: var(--bookando-content-bg, #FFFFFF);
  font-family: var(--bookando-font-family, 'Inter');

  /* Header */
  .preview-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 32px;
    flex-wrap: wrap;
    gap: 16px;

    .header-title {
      margin: 0;
      font-size: 28px;
      font-weight: 700;
      color: var(--bookando-content-heading, #354052);
    }

    .header-actions {
      display: flex;
      gap: 12px;
    }

    .month-select {
      padding: 10px 16px;
      border: var(--bookando-border-width, 1px) solid var(--bookando-input-border, #E2E6EC);
      border-radius: var(--bookando-border-radius, 6px);
      background: var(--bookando-input-bg, #FFFFFF);
      color: var(--bookando-input-text, #354052);
      font-size: 14px;
      font-family: inherit;
      cursor: pointer;
      transition: all 0.2s;

      &:focus {
        outline: none;
        border-color: var(--bookando-primary, #1A84EE);
        box-shadow: 0 0 0 3px rgba(26, 132, 238, 0.1);
      }
    }

    .calendar-toggle {
      padding: 10px;
      border: var(--bookando-border-width, 1px) solid var(--bookando-input-border, #E2E6EC);
      border-radius: var(--bookando-border-radius, 6px);
      background: var(--bookando-input-bg, #FFFFFF);
      color: var(--bookando-primary, #1A84EE);
      cursor: pointer;
      transition: all 0.2s;
      display: flex;
      align-items: center;
      justify-content: center;

      &:hover {
        background: var(--bookando-primary, #1A84EE);
        color: #FFFFFF;
      }
    }
  }

  /* Event List */
  .event-list {
    display: flex;
    flex-direction: column;
    gap: 24px;
  }

  /* Event Item */
  .event-item {
    display: flex;
    gap: 24px;
    padding: 24px;
    background: var(--bookando-content-bg, #FFFFFF);
    border: var(--bookando-border-width, 1px) solid var(--bookando-input-border, #E2E6EC);
    border-radius: var(--bookando-border-radius, 6px);
    transition: all 0.3s;

    &:hover {
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
      transform: translateX(4px);
    }

    &.featured {
      border-color: var(--bookando-primary, #1A84EE);
      box-shadow: 0 4px 16px rgba(26, 132, 238, 0.15);
    }

    .event-date-badge {
      flex-shrink: 0;
      width: 80px;
      height: 80px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      background: var(--bookando-primary, #1A84EE);
      border-radius: var(--bookando-border-radius, 6px);
      color: #FFFFFF;

      &.gradient-bg {
        background: var(--bookando-gradient, linear-gradient(135deg, #1A84EE 0%, #A28FF3 100%));
      }

      .badge-day {
        font-size: 32px;
        font-weight: 700;
        line-height: 1;
      }

      .badge-month {
        font-size: 14px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-top: 4px;
      }
    }

    .event-content {
      flex: 1;
      min-width: 0;

      .event-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
        flex-wrap: wrap;
        gap: 12px;

        .event-time {
          display: flex;
          align-items: center;
          gap: 6px;
          font-size: 14px;
          font-weight: 500;
          color: var(--bookando-content-text, #7F8FA4);

          svg {
            color: var(--bookando-primary, #1A84EE);
          }
        }

        .event-badge {
          padding: 4px 12px;
          background: var(--bookando-primary, #1A84EE);
          color: #FFFFFF;
          font-size: 11px;
          font-weight: 600;
          text-transform: uppercase;
          letter-spacing: 0.5px;
          border-radius: calc(var(--bookando-border-radius, 6px) / 2);
        }
      }

      .event-title {
        margin: 0 0 12px 0;
        font-size: 22px;
        font-weight: 600;
        color: var(--bookando-content-heading, #354052);
      }

      .event-description {
        margin: 0 0 16px 0;
        font-size: 14px;
        line-height: 1.6;
        color: var(--bookando-content-text, #7F8FA4);
      }

      .event-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: var(--bookando-border-width, 1px) solid var(--bookando-input-border, #E2E6EC);

        .meta-item {
          display: flex;
          align-items: center;
          gap: 6px;
          font-size: 13px;
          color: var(--bookando-content-text, #7F8FA4);

          svg {
            color: var(--bookando-primary, #1A84EE);
            flex-shrink: 0;
          }
        }
      }

      .event-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;

        .event-price {
          display: flex;
          align-items: baseline;
          gap: 6px;

          .price-label {
            font-size: 14px;
            color: var(--bookando-content-text, #7F8FA4);
          }

          .price-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--bookando-primary, #1A84EE);
          }
        }

        .event-button {
          padding: 12px 28px;
          background: var(--bookando-btn-primary-bg, #1A84EE);
          color: var(--bookando-btn-primary-text, #FFFFFF);
          border: none;
          border-radius: var(--bookando-border-radius, 6px);
          font-size: 14px;
          font-weight: 600;
          font-family: inherit;
          cursor: pointer;
          transition: all 0.2s;
          white-space: nowrap;

          &:hover {
            opacity: 0.9;
            transform: scale(1.05);
          }

          &:active {
            transform: scale(0.98);
          }
        }
      }
    }
  }

  /* Responsive */
  @media (max-width: 768px) {
    padding: 20px;

    .preview-header {
      .header-title {
        font-size: 22px;
      }
    }

    .event-item {
      flex-direction: column;
      padding: 20px;

      .event-date-badge {
        width: 100%;
        height: 60px;
        flex-direction: row;
        justify-content: center;
        gap: 12px;

        .badge-day {
          font-size: 28px;
        }

        .badge-month {
          font-size: 16px;
          margin-top: 0;
        }
      }

      .event-content {
        .event-footer {
          flex-direction: column;
          align-items: flex-start;

          .event-button {
            width: 100%;
          }
        }
      }
    }
  }
}
</style>
