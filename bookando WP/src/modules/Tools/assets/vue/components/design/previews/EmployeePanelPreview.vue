<!-- EmployeePanelPreview.vue - Live Preview Component for Employee Panel -->
<template>
  <div
    class="employee-panel-preview"
    :style="cssVariables"
  >
    <!-- Sidebar -->
    <div class="panel-sidebar">
      <div class="employee-profile">
        <div class="employee-avatar">
          <svg
            width="40"
            height="40"
            viewBox="0 0 24 24"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <circle
              cx="12"
              cy="8"
              r="4"
              stroke="currentColor"
              stroke-width="2"
            />
            <path
              d="M4 20C4 16.6863 6.68629 14 10 14H14C17.3137 14 20 16.6863 20 20"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
            />
          </svg>
        </div>
        <div class="employee-info">
          <div class="employee-name">
            Maria Schmidt
          </div>
          <div class="employee-role">
            Masseurin
          </div>
        </div>
        <div class="employee-status online">
          <span class="status-dot" />
          <span class="status-text">Verfügbar</span>
        </div>
      </div>

      <nav class="sidebar-nav">
        <a
          href="#"
          class="nav-item active"
        >
          <svg
            width="20"
            height="20"
            viewBox="0 0 24 24"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <rect
              x="3"
              y="3"
              width="7"
              height="7"
              stroke="currentColor"
              stroke-width="2"
            />
            <rect
              x="14"
              y="3"
              width="7"
              height="7"
              stroke="currentColor"
              stroke-width="2"
            />
            <rect
              x="14"
              y="14"
              width="7"
              height="7"
              stroke="currentColor"
              stroke-width="2"
            />
            <rect
              x="3"
              y="14"
              width="7"
              height="7"
              stroke="currentColor"
              stroke-width="2"
            />
          </svg>
          <span>Dashboard</span>
        </a>
        <a
          href="#"
          class="nav-item"
        >
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
          <span>Terminkalender</span>
        </a>
        <a
          href="#"
          class="nav-item"
        >
          <svg
            width="20"
            height="20"
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
          <span>Kunden</span>
        </a>
        <a
          href="#"
          class="nav-item"
        >
          <svg
            width="20"
            height="20"
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
          <span>Arbeitszeiten</span>
        </a>
        <a
          href="#"
          class="nav-item"
        >
          <svg
            width="20"
            height="20"
            viewBox="0 0 24 24"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <circle
              cx="12"
              cy="12"
              r="3"
              stroke="currentColor"
              stroke-width="2"
            />
            <path
              d="M19.4 15C19.1277 15.6171 19.2583 16.3378 19.73 16.82L19.79 16.88C20.1656 17.2551 20.3766 17.7642 20.3766 18.295C20.3766 18.8258 20.1656 19.3349 19.79 19.71C19.4149 20.0856 18.9058 20.2966 18.375 20.2966C17.8442 20.2966 17.3351 20.0856 16.96 19.71L16.9 19.65C16.4178 19.1783 15.6971 19.0477 15.08 19.32C14.4755 19.5791 14.0826 20.1724 14.08 20.83V21C14.08 22.1046 13.1846 23 12.08 23C10.9754 23 10.08 22.1046 10.08 21V20.91C10.0642 20.2327 9.63587 19.6339 9 19.4C8.38291 19.1277 7.66219 19.2583 7.18 19.73L7.12 19.79C6.74486 20.1656 6.23582 20.3766 5.705 20.3766C5.17418 20.3766 4.66514 20.1656 4.29 19.79C3.91445 19.4149 3.70343 18.9058 3.70343 18.375C3.70343 17.8442 3.91445 17.3351 4.29 16.96L4.35 16.9C4.82167 16.4178 4.95235 15.6971 4.68 15.08C4.42093 14.4755 3.82764 14.0826 3.17 14.08H3C1.89543 14.08 1 13.1846 1 12.08C1 10.9754 1.89543 10.08 3 10.08H3.09C3.76733 10.0642 4.36613 9.63587 4.6 9C4.87235 8.38291 4.74167 7.66219 4.27 7.18L4.21 7.12C3.83445 6.74486 3.62343 6.23582 3.62343 5.705C3.62343 5.17418 3.83445 4.66514 4.21 4.29C4.58514 3.91445 5.09418 3.70343 5.625 3.70343C6.15582 3.70343 6.66486 3.91445 7.04 4.29L7.1 4.35C7.58219 4.82167 8.30291 4.95235 8.92 4.68H9C9.60447 4.42093 9.99738 3.82764 10 3.17V3C10 1.89543 10.8954 1 12 1C13.1046 1 14 1.89543 14 3V3.09C14.0026 3.74764 14.3955 4.34093 15 4.6C15.6171 4.87235 16.3378 4.74167 16.82 4.27L16.88 4.21C17.2551 3.83445 17.7642 3.62343 18.295 3.62343C18.8258 3.62343 19.3349 3.83445 19.71 4.21C20.0856 4.58514 20.2966 5.09418 20.2966 5.625C20.2966 6.15582 20.0856 6.66486 19.71 7.04L19.65 7.1C19.1783 7.58219 19.0477 8.30291 19.32 8.92V9C19.5791 9.60447 20.1724 9.99738 20.83 10H21C22.1046 10 23 10.8954 23 12C23 13.1046 22.1046 14 21 14H20.91C20.2524 14.0026 19.6591 14.3955 19.4 15Z"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </svg>
          <span>Einstellungen</span>
        </a>
      </nav>
    </div>

    <!-- Main Content -->
    <div class="panel-content">
      <!-- Header -->
      <div class="content-header">
        <div>
          <h2 class="header-title">
            Guten Tag, Maria!
          </h2>
          <p class="header-subtitle">
            {{ new Date().toLocaleDateString('de-DE', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
          </p>
        </div>
        <div class="header-actions">
          <button class="action-btn">
            <svg
              width="20"
              height="20"
              viewBox="0 0 24 24"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path
                d="M18 8C18 6.4087 17.3679 4.88258 16.2426 3.75736C15.1174 2.63214 13.5913 2 12 2C10.4087 2 8.88258 2.63214 7.75736 3.75736C6.63214 4.88258 6 6.4087 6 8C6 15 3 17 3 17H21C21 17 18 15 18 8Z"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
              <path
                d="M13.73 21C13.5542 21.3031 13.3019 21.5547 12.9982 21.7295C12.6946 21.9044 12.3504 21.9965 12 21.9965C11.6496 21.9965 11.3054 21.9044 11.0018 21.7295C10.6982 21.5547 10.4458 21.3031 10.27 21"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
          </button>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon">
            <svg
              width="24"
              height="24"
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
          </div>
          <div class="stat-content">
            <div class="stat-label">
              Termine Heute
            </div>
            <div class="stat-value">
              8
            </div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon">
            <svg
              width="24"
              height="24"
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
          </div>
          <div class="stat-content">
            <div class="stat-label">
              Kunden Heute
            </div>
            <div class="stat-value">
              6
            </div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon gradient-bg">
            <svg
              width="24"
              height="24"
              viewBox="0 0 24 24"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
            >
              <line
                x1="12"
                y1="1"
                x2="12"
                y2="23"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
              <path
                d="M17 5H9.5C8.57174 5 7.6815 5.36875 7.02513 6.02513C6.36875 6.6815 6 7.57174 6 8.5C6 9.42826 6.36875 10.3185 7.02513 10.9749C7.6815 11.6313 8.57174 12 9.5 12H14.5C15.4283 12 16.3185 12.3687 16.9749 13.0251C17.6313 13.6815 18 14.5717 18 15.5C18 16.4283 17.6313 17.3185 16.9749 17.9749C16.3185 18.6313 15.4283 19 14.5 19H6"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">
              Umsatz Heute
            </div>
            <div class="stat-value">
              712 €
            </div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon">
            <svg
              width="24"
              height="24"
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
          </div>
          <div class="stat-content">
            <div class="stat-label">
              Arbeitsstunden
            </div>
            <div class="stat-value">
              6.5h
            </div>
          </div>
        </div>
      </div>

      <!-- Today's Schedule -->
      <div class="section">
        <div class="section-header">
          <h3 class="section-title">
            Heutiger Terminplan
          </h3>
          <button class="section-action">
            Alle anzeigen
          </button>
        </div>

        <div class="schedule-timeline">
          <!-- Appointment 1 -->
          <div class="timeline-item">
            <div class="timeline-time">
              <div class="time-label">
                09:00
              </div>
              <div class="time-duration">
                60 Min
              </div>
            </div>
            <div class="timeline-content">
              <div class="appointment-header">
                <h4 class="appointment-title">
                  Entspannungsmassage
                </h4>
                <div class="appointment-badge confirmed">
                  Bestätigt
                </div>
              </div>
              <div class="appointment-customer">
                <svg
                  width="16"
                  height="16"
                  viewBox="0 0 24 24"
                  fill="none"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <circle
                    cx="12"
                    cy="8"
                    r="4"
                    stroke="currentColor"
                    stroke-width="2"
                  />
                  <path
                    d="M4 20C4 16.6863 6.68629 14 10 14H14C17.3137 14 20 16.6863 20 20"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                  />
                </svg>
                <span>Max Mustermann</span>
              </div>
            </div>
          </div>

          <!-- Appointment 2 -->
          <div class="timeline-item active">
            <div class="timeline-time">
              <div class="time-label">
                10:30
              </div>
              <div class="time-duration">
                90 Min
              </div>
            </div>
            <div class="timeline-content">
              <div class="appointment-header">
                <h4 class="appointment-title">
                  Sportmassage & Physiotherapie
                </h4>
                <div class="appointment-badge active">
                  Läuft
                </div>
              </div>
              <div class="appointment-customer">
                <svg
                  width="16"
                  height="16"
                  viewBox="0 0 24 24"
                  fill="none"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <circle
                    cx="12"
                    cy="8"
                    r="4"
                    stroke="currentColor"
                    stroke-width="2"
                  />
                  <path
                    d="M4 20C4 16.6863 6.68629 14 10 14H14C17.3137 14 20 16.6863 20 20"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                  />
                </svg>
                <span>Anna Müller</span>
              </div>
            </div>
          </div>

          <!-- Break -->
          <div class="timeline-item break">
            <div class="timeline-time">
              <div class="time-label">
                12:00
              </div>
              <div class="time-duration">
                60 Min
              </div>
            </div>
            <div class="timeline-content">
              <div class="appointment-header">
                <h4 class="appointment-title">
                  Mittagspause
                </h4>
              </div>
            </div>
          </div>

          <!-- Appointment 3 -->
          <div class="timeline-item">
            <div class="timeline-time">
              <div class="time-label">
                13:00
              </div>
              <div class="time-duration">
                45 Min
              </div>
            </div>
            <div class="timeline-content">
              <div class="appointment-header">
                <h4 class="appointment-title">
                  Gesichtsmassage
                </h4>
                <div class="appointment-badge confirmed">
                  Bestätigt
                </div>
              </div>
              <div class="appointment-customer">
                <svg
                  width="16"
                  height="16"
                  viewBox="0 0 24 24"
                  fill="none"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <circle
                    cx="12"
                    cy="8"
                    r="4"
                    stroke="currentColor"
                    stroke-width="2"
                  />
                  <path
                    d="M4 20C4 16.6863 6.68629 14 10 14H14C17.3137 14 20 16.6863 20 20"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                  />
                </svg>
                <span>Lisa Weber</span>
              </div>
            </div>
          </div>

          <!-- Appointment 4 -->
          <div class="timeline-item">
            <div class="timeline-time">
              <div class="time-label">
                14:00
              </div>
              <div class="time-duration">
                60 Min
              </div>
            </div>
            <div class="timeline-content">
              <div class="appointment-header">
                <h4 class="appointment-title">
                  Thai-Massage
                </h4>
                <div class="appointment-badge confirmed">
                  Bestätigt
                </div>
              </div>
              <div class="appointment-customer">
                <svg
                  width="16"
                  height="16"
                  viewBox="0 0 24 24"
                  fill="none"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <circle
                    cx="12"
                    cy="8"
                    r="4"
                    stroke="currentColor"
                    stroke-width="2"
                  />
                  <path
                    d="M4 20C4 16.6863 6.68629 14 10 14H14C17.3137 14 20 16.6863 20 20"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                  />
                </svg>
                <span>Tom Fischer</span>
              </div>
            </div>
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
.employee-panel-preview {
  display: flex;
  min-height: 600px;
  font-family: var(--bookando-font-family, 'Inter');
  background: var(--bookando-content-bg, #F9FAFB);

  /* Sidebar */
  .panel-sidebar {
    width: 280px;
    background: var(--bookando-sidebar-bg, #1F2937);
    color: var(--bookando-sidebar-text, #F9FAFB);
    padding: 24px;
    display: flex;
    flex-direction: column;

    .employee-profile {
      padding: 20px;
      background: rgba(255, 255, 255, 0.05);
      border-radius: var(--bookando-border-radius, 6px);
      margin-bottom: 24px;

      .employee-avatar {
        width: 64px;
        height: 64px;
        background: var(--bookando-gradient, linear-gradient(135deg, #1A84EE 0%, #A28FF3 100%));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #FFFFFF;
        margin: 0 auto 16px auto;
      }

      .employee-info {
        text-align: center;
        margin-bottom: 12px;

        .employee-name {
          font-size: 17px;
          font-weight: 600;
          color: var(--bookando-sidebar-text, #F9FAFB);
          margin-bottom: 4px;
        }

        .employee-role {
          font-size: 13px;
          color: rgba(249, 250, 251, 0.7);
        }
      }

      .employee-status {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 8px 16px;
        background: rgba(16, 185, 129, 0.15);
        border-radius: calc(var(--bookando-border-radius, 6px) / 2);

        &.online {
          .status-dot {
            width: 8px;
            height: 8px;
            background: #10B981;
            border-radius: 50%;
            animation: pulse 2s ease-in-out infinite;
          }

          .status-text {
            font-size: 13px;
            font-weight: 600;
            color: #10B981;
          }
        }
      }
    }

    .sidebar-nav {
      display: flex;
      flex-direction: column;
      gap: 4px;

      .nav-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        border-radius: var(--bookando-border-radius, 6px);
        color: rgba(249, 250, 251, 0.8);
        text-decoration: none;
        transition: all 0.2s;
        font-size: 14px;
        font-weight: 500;

        svg {
          flex-shrink: 0;
        }

        &:hover {
          background: rgba(255, 255, 255, 0.05);
          color: var(--bookando-sidebar-text, #F9FAFB);
        }

        &.active {
          background: var(--bookando-primary, #1A84EE);
          color: #FFFFFF;
        }
      }
    }
  }

  /* Main Content */
  .panel-content {
    flex: 1;
    padding: 32px;
    overflow-y: auto;

    .content-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 32px;

      .header-title {
        margin: 0 0 4px 0;
        font-size: 28px;
        font-weight: 700;
        color: var(--bookando-content-heading, #354052);
      }

      .header-subtitle {
        margin: 0;
        font-size: 15px;
        color: var(--bookando-content-text, #7F8FA4);
      }

      .header-actions {
        .action-btn {
          width: 48px;
          height: 48px;
          display: flex;
          align-items: center;
          justify-content: center;
          border: var(--bookando-border-width, 1px) solid var(--bookando-input-border, #E2E6EC);
          border-radius: var(--bookando-border-radius, 6px);
          background: var(--bookando-content-bg, #FFFFFF);
          color: var(--bookando-primary, #1A84EE);
          cursor: pointer;
          transition: all 0.2s;

          &:hover {
            background: var(--bookando-primary, #1A84EE);
            color: #FFFFFF;
          }
        }
      }
    }

    /* Stats Grid */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 20px;
      margin-bottom: 32px;
    }

    .stat-card {
      display: flex;
      align-items: center;
      gap: 16px;
      padding: 20px;
      background: var(--bookando-content-bg, #FFFFFF);
      border: var(--bookando-border-width, 1px) solid var(--bookando-input-border, #E2E6EC);
      border-radius: var(--bookando-border-radius, 6px);
      transition: all 0.3s;

      &:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        transform: translateY(-2px);
      }

      .stat-icon {
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(26, 132, 238, 0.1);
        color: var(--bookando-primary, #1A84EE);
        border-radius: var(--bookando-border-radius, 6px);
        flex-shrink: 0;

        &.gradient-bg {
          background: var(--bookando-gradient, linear-gradient(135deg, #1A84EE 0%, #A28FF3 100%));
          color: #FFFFFF;
        }
      }

      .stat-content {
        flex: 1;

        .stat-label {
          font-size: 13px;
          color: var(--bookando-content-text, #7F8FA4);
          margin-bottom: 4px;
        }

        .stat-value {
          font-size: 28px;
          font-weight: 700;
          color: var(--bookando-content-heading, #354052);
        }
      }
    }

    /* Section */
    .section {
      margin-bottom: 32px;

      .section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;

        .section-title {
          margin: 0;
          font-size: 20px;
          font-weight: 600;
          color: var(--bookando-content-heading, #354052);
        }

        .section-action {
          padding: 8px 16px;
          border: none;
          background: transparent;
          color: var(--bookando-primary, #1A84EE);
          font-size: 14px;
          font-weight: 500;
          font-family: inherit;
          cursor: pointer;
          transition: all 0.2s;

          &:hover {
            text-decoration: underline;
          }
        }
      }
    }

    /* Schedule Timeline */
    .schedule-timeline {
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .timeline-item {
      display: flex;
      gap: 20px;
      padding: 20px;
      background: var(--bookando-content-bg, #FFFFFF);
      border: var(--bookando-border-width, 1px) solid var(--bookando-input-border, #E2E6EC);
      border-left: 4px solid var(--bookando-input-border, #E2E6EC);
      border-radius: var(--bookando-border-radius, 6px);
      transition: all 0.3s;

      &:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      }

      &.active {
        border-left-color: var(--bookando-primary, #1A84EE);
        background: rgba(26, 132, 238, 0.02);
      }

      &.break {
        border-left-color: #F59E0B;
        background: rgba(245, 158, 11, 0.02);
      }

      .timeline-time {
        width: 80px;
        text-align: center;
        flex-shrink: 0;

        .time-label {
          font-size: 18px;
          font-weight: 700;
          color: var(--bookando-content-heading, #354052);
          margin-bottom: 4px;
        }

        .time-duration {
          font-size: 12px;
          color: var(--bookando-content-text, #7F8FA4);
        }
      }

      .timeline-content {
        flex: 1;
        min-width: 0;

        .appointment-header {
          display: flex;
          align-items: center;
          justify-content: space-between;
          margin-bottom: 8px;
          gap: 12px;

          .appointment-title {
            margin: 0;
            font-size: 17px;
            font-weight: 600;
            color: var(--bookando-content-heading, #354052);
            flex: 1;
          }

          .appointment-badge {
            padding: 4px 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-radius: calc(var(--bookando-border-radius, 6px) / 2);
            flex-shrink: 0;

            &.confirmed {
              background: rgba(16, 185, 129, 0.1);
              color: #10B981;
            }

            &.active {
              background: var(--bookando-primary, #1A84EE);
              color: #FFFFFF;
            }
          }
        }

        .appointment-customer {
          display: flex;
          align-items: center;
          gap: 8px;
          font-size: 14px;
          color: var(--bookando-content-text, #7F8FA4);

          svg {
            color: var(--bookando-primary, #1A84EE);
            flex-shrink: 0;
          }
        }
      }
    }
  }

  /* Responsive */
  @media (max-width: 768px) {
    flex-direction: column;

    .panel-sidebar {
      width: 100%;
    }

    .panel-content {
      padding: 20px;

      .content-header {
        .header-title {
          font-size: 22px;
        }
      }

      .stats-grid {
        grid-template-columns: 1fr;
      }

      .timeline-item {
        flex-direction: column;

        .timeline-time {
          width: 100%;
          text-align: left;
          display: flex;
          align-items: center;
          justify-content: space-between;
        }
      }
    }
  }
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.7;
    transform: scale(1.1);
  }
}
</style>
