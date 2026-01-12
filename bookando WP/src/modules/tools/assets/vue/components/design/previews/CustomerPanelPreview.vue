<!-- CustomerPanelPreview.vue - Live Preview Component for Customer Panel -->
<template>
  <div
    class="customer-panel-preview"
    :style="cssVariables"
  >
    <!-- Sidebar -->
    <div class="panel-sidebar">
      <div class="user-profile">
        <div class="user-avatar">
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
        <div class="user-info">
          <div class="user-name">
            Max Mustermann
          </div>
          <div class="user-email">
            max@example.com
          </div>
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
          <span>Meine Termine</span>
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
          <span>Buchungsverlauf</span>
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
              d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
            <circle
              cx="12"
              cy="7"
              r="4"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </svg>
          <span>Profil</span>
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
      <!-- Welcome Header -->
      <div class="content-header">
        <div>
          <h2 class="header-title">
            Willkommen zurück, Max!
          </h2>
          <p class="header-subtitle">
            Verwalten Sie Ihre Termine und Buchungen
          </p>
        </div>
        <button class="new-booking-btn">
          <svg
            width="20"
            height="20"
            viewBox="0 0 24 24"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <line
              x1="12"
              y1="5"
              x2="12"
              y2="19"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
            />
            <line
              x1="5"
              y1="12"
              x2="19"
              y2="12"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
            />
          </svg>
          Neue Buchung
        </button>
      </div>

      <!-- Stats Cards -->
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
              Anstehend
            </div>
            <div class="stat-value">
              3
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
                d="M22 11.08V12C21.9988 14.1564 21.3005 16.2547 20.0093 17.9818C18.7182 19.7088 16.9033 20.9725 14.8354 21.5839C12.7674 22.1953 10.5573 22.1219 8.53447 21.3746C6.51168 20.6273 4.78465 19.2461 3.61096 17.4371C2.43727 15.628 1.87979 13.4881 2.02168 11.3363C2.16356 9.18455 2.99721 7.13631 4.39828 5.49706C5.79935 3.85781 7.69279 2.71537 9.79619 2.24013C11.8996 1.7649 14.1003 1.98232 16.07 2.85999"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
              <path
                d="M22 4L12 14.01L9 11.01"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">
              Abgeschlossen
            </div>
            <div class="stat-value">
              12
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
              <path
                d="M12 2L2 7L12 12L22 7L12 2Z"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
              <path
                d="M2 17L12 22L22 17"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
              <path
                d="M2 12L12 17L22 12"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">
              Treue-Punkte
            </div>
            <div class="stat-value">
              248
            </div>
          </div>
        </div>
      </div>

      <!-- Upcoming Appointments -->
      <div class="section">
        <h3 class="section-title">
          Anstehende Termine
        </h3>

        <div class="appointments-list">
          <div class="appointment-card">
            <div class="appointment-date">
              <div class="date-day">
                Mo
              </div>
              <div class="date-number">
                15
              </div>
              <div class="date-month">
                Feb
              </div>
            </div>

            <div class="appointment-content">
              <h4 class="appointment-title">
                Entspannungsmassage
              </h4>
              <div class="appointment-details">
                <div class="detail-item">
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
                  14:00 - 15:00 Uhr
                </div>
                <div class="detail-item">
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
                  Maria Schmidt
                </div>
              </div>
            </div>

            <div class="appointment-actions">
              <button class="action-btn">
                Stornieren
              </button>
              <button class="action-btn primary">
                Details
              </button>
            </div>
          </div>

          <div class="appointment-card">
            <div class="appointment-date">
              <div class="date-day">
                Mi
              </div>
              <div class="date-number">
                17
              </div>
              <div class="date-month">
                Feb
              </div>
            </div>

            <div class="appointment-content">
              <h4 class="appointment-title">
                Yoga Kurs
              </h4>
              <div class="appointment-details">
                <div class="detail-item">
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
                  18:00 - 19:30 Uhr
                </div>
                <div class="detail-item">
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
                  Lisa Weber
                </div>
              </div>
            </div>

            <div class="appointment-actions">
              <button class="action-btn">
                Stornieren
              </button>
              <button class="action-btn primary">
                Details
              </button>
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
.customer-panel-preview {
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

    .user-profile {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 16px;
      background: rgba(255, 255, 255, 0.05);
      border-radius: var(--bookando-border-radius, 6px);
      margin-bottom: 24px;

      .user-avatar {
        width: 48px;
        height: 48px;
        background: var(--bookando-primary, #1A84EE);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #FFFFFF;
        flex-shrink: 0;
      }

      .user-info {
        flex: 1;
        min-width: 0;

        .user-name {
          font-size: 15px;
          font-weight: 600;
          color: var(--bookando-sidebar-text, #F9FAFB);
          margin-bottom: 2px;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
        }

        .user-email {
          font-size: 12px;
          color: rgba(249, 250, 251, 0.7);
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
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
      flex-wrap: wrap;
      gap: 16px;

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

      .new-booking-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        background: var(--bookando-btn-primary-bg, #1A84EE);
        color: var(--bookando-btn-primary-text, #FFFFFF);
        border: none;
        border-radius: var(--bookando-border-radius, 6px);
        font-size: 14px;
        font-weight: 600;
        font-family: inherit;
        cursor: pointer;
        transition: all 0.2s;

        &:hover {
          opacity: 0.9;
          transform: translateY(-1px);
        }
      }
    }

    /* Stats Grid */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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

      .section-title {
        margin: 0 0 20px 0;
        font-size: 20px;
        font-weight: 600;
        color: var(--bookando-content-heading, #354052);
      }
    }

    /* Appointments List */
    .appointments-list {
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .appointment-card {
      display: flex;
      align-items: center;
      gap: 20px;
      padding: 20px;
      background: var(--bookando-content-bg, #FFFFFF);
      border: var(--bookando-border-width, 1px) solid var(--bookando-input-border, #E2E6EC);
      border-radius: var(--bookando-border-radius, 6px);
      transition: all 0.3s;

      &:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      }

      .appointment-date {
        width: 70px;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 12px;
        background: var(--bookando-primary, #1A84EE);
        color: #FFFFFF;
        border-radius: var(--bookando-border-radius, 6px);
        flex-shrink: 0;

        .date-day {
          font-size: 12px;
          font-weight: 600;
          text-transform: uppercase;
          opacity: 0.9;
        }

        .date-number {
          font-size: 28px;
          font-weight: 700;
          line-height: 1;
          margin: 4px 0;
        }

        .date-month {
          font-size: 12px;
          font-weight: 600;
          text-transform: uppercase;
          opacity: 0.9;
        }
      }

      .appointment-content {
        flex: 1;
        min-width: 0;

        .appointment-title {
          margin: 0 0 8px 0;
          font-size: 18px;
          font-weight: 600;
          color: var(--bookando-content-heading, #354052);
        }

        .appointment-details {
          display: flex;
          flex-wrap: wrap;
          gap: 16px;

          .detail-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            color: var(--bookando-content-text, #7F8FA4);

            svg {
              color: var(--bookando-primary, #1A84EE);
              flex-shrink: 0;
            }
          }
        }
      }

      .appointment-actions {
        display: flex;
        gap: 8px;
        flex-shrink: 0;

        .action-btn {
          padding: 10px 20px;
          border: var(--bookando-border-width, 1px) solid var(--bookando-input-border, #E2E6EC);
          border-radius: var(--bookando-border-radius, 6px);
          background: var(--bookando-content-bg, #FFFFFF);
          color: var(--bookando-content-text, #7F8FA4);
          font-size: 14px;
          font-weight: 500;
          font-family: inherit;
          cursor: pointer;
          transition: all 0.2s;
          white-space: nowrap;

          &:hover {
            border-color: var(--bookando-primary, #1A84EE);
            color: var(--bookando-primary, #1A84EE);
          }

          &.primary {
            background: var(--bookando-primary, #1A84EE);
            border-color: var(--bookando-primary, #1A84EE);
            color: #FFFFFF;

            &:hover {
              opacity: 0.9;
            }
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

      .appointment-card {
        flex-direction: column;
        align-items: flex-start;

        .appointment-date {
          width: 100%;
          flex-direction: row;
          justify-content: center;
          gap: 12px;
          padding: 12px;
        }

        .appointment-actions {
          width: 100%;

          .action-btn {
            flex: 1;
          }
        }
      }
    }
  }
}
</style>
