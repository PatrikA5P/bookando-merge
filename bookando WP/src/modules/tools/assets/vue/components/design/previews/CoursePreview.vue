<!-- CoursePreview.vue - Live Preview Component for Course Catalog -->
<template>
  <div
    class="course-preview"
    :style="cssVariables"
  >
    <!-- Header -->
    <div class="preview-header">
      <h2 class="header-title">
        Kurse & Workshops
      </h2>
      <p class="header-subtitle">
        Entdecken Sie unsere Kursangebote
      </p>
    </div>

    <!-- Filters & Search -->
    <div class="preview-filters">
      <div class="search-wrapper">
        <input
          type="text"
          class="search-input"
          placeholder="Kurse durchsuchen..."
        >
      </div>

      <div class="filter-buttons">
        <button class="filter-btn active">
          Alle Kurse
        </button>
        <button class="filter-btn">
          Yoga
        </button>
        <button class="filter-btn">
          Fitness
        </button>
        <button class="filter-btn">
          Wellness
        </button>
      </div>
    </div>

    <!-- Course Grid -->
    <div class="course-grid">
      <!-- Course Card 1 -->
      <div class="course-card featured">
        <div class="course-badge">
          Beliebt
        </div>
        <div class="course-image">
          <div class="course-image-placeholder">
            <svg
              width="48"
              height="48"
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
        </div>

        <div class="course-content">
          <div class="course-category">
            Yoga
          </div>
          <h3 class="course-title">
            Hatha Yoga für Anfänger
          </h3>
          <p class="course-description">
            Lernen Sie die Grundlagen des Hatha Yoga in einem entspannten, anfängerfreundlichen Kurs.
          </p>

          <div class="course-meta">
            <div class="meta-item">
              <svg
                width="16"
                height="16"
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
              <span>8 Termine</span>
            </div>
            <div class="meta-item">
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
              <span>90 Min</span>
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
              <span>12/15 Plätze</span>
            </div>
          </div>

          <div class="course-footer">
            <div class="course-info">
              <div class="course-price">
                289,00 €
              </div>
              <div class="course-instructor">
                mit Maria Schmidt
              </div>
            </div>
            <button class="course-button">
              Anmelden
            </button>
          </div>
        </div>
      </div>

      <!-- Course Card 2 -->
      <div class="course-card">
        <div class="course-image gradient-bg">
          <div class="course-image-placeholder">
            <svg
              width="48"
              height="48"
              viewBox="0 0 24 24"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path
                d="M22 12H18L15 21L9 3L6 12H2"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
          </div>
        </div>

        <div class="course-content">
          <div class="course-category">
            Fitness
          </div>
          <h3 class="course-title">
            HIIT Intensivkurs
          </h3>
          <p class="course-description">
            Hochintensives Intervalltraining für maximale Fettverbrennung und Muskelaufbau.
          </p>

          <div class="course-meta">
            <div class="meta-item">
              <svg
                width="16"
                height="16"
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
              <span>6 Termine</span>
            </div>
            <div class="meta-item">
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
              <span>60 Min</span>
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
              <span>8/10 Plätze</span>
            </div>
          </div>

          <div class="course-footer">
            <div class="course-info">
              <div class="course-price">
                199,00 €
              </div>
              <div class="course-instructor">
                mit Tom Fischer
              </div>
            </div>
            <button class="course-button">
              Anmelden
            </button>
          </div>
        </div>
      </div>

      <!-- Course Card 3 -->
      <div class="course-card">
        <div class="course-image">
          <div class="course-image-placeholder">
            <svg
              width="48"
              height="48"
              viewBox="0 0 24 24"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path
                d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
              <path
                d="M12 16V12"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
              <path
                d="M12 8H12.01"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
          </div>
        </div>

        <div class="course-content">
          <div class="course-category">
            Wellness
          </div>
          <h3 class="course-title">
            Meditation & Achtsamkeit
          </h3>
          <p class="course-description">
            Finden Sie innere Ruhe durch geführte Meditation und Achtsamkeitspraktiken.
          </p>

          <div class="course-meta">
            <div class="meta-item">
              <svg
                width="16"
                height="16"
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
              <span>4 Termine</span>
            </div>
            <div class="meta-item">
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
              <span>45 Min</span>
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
              <span>6/8 Plätze</span>
            </div>
          </div>

          <div class="course-footer">
            <div class="course-info">
              <div class="course-price">
                149,00 €
              </div>
              <div class="course-instructor">
                mit Lisa Weber
              </div>
            </div>
            <button class="course-button">
              Anmelden
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
.course-preview {
  padding: 32px;
  min-height: 600px;
  background: var(--bookando-content-bg, #FFFFFF);
  font-family: var(--bookando-font-family, 'Inter');

  /* Header */
  .preview-header {
    text-align: center;
    margin-bottom: 32px;

    .header-title {
      margin: 0 0 8px 0;
      font-size: 32px;
      font-weight: 700;
      color: var(--bookando-content-heading, #354052);
    }

    .header-subtitle {
      margin: 0;
      font-size: 16px;
      color: var(--bookando-content-text, #7F8FA4);
    }
  }

  /* Filters */
  .preview-filters {
    display: flex;
    gap: 16px;
    margin-bottom: 32px;
    flex-wrap: wrap;

    .search-wrapper {
      flex: 1;
      min-width: 250px;
    }

    .search-input {
      width: 100%;
      padding: 12px 16px;
      border: var(--bookando-border-width, 1px) solid var(--bookando-input-border, #E2E6EC);
      border-radius: var(--bookando-border-radius, 6px);
      background: var(--bookando-input-bg, #FFFFFF);
      color: var(--bookando-input-text, #354052);
      font-size: 14px;
      font-family: inherit;
      transition: all 0.2s;

      &::placeholder {
        color: var(--bookando-input-placeholder, rgba(127, 143, 164, 0.5));
      }

      &:focus {
        outline: none;
        border-color: var(--bookando-primary, #1A84EE);
        box-shadow: 0 0 0 3px rgba(26, 132, 238, 0.1);
      }
    }

    .filter-buttons {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .filter-btn {
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

      &:hover {
        border-color: var(--bookando-primary, #1A84EE);
        color: var(--bookando-primary, #1A84EE);
      }

      &.active {
        background: var(--bookando-primary, #1A84EE);
        border-color: var(--bookando-primary, #1A84EE);
        color: #FFFFFF;
      }
    }
  }

  /* Course Grid */
  .course-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 24px;
  }

  /* Course Card */
  .course-card {
    position: relative;
    background: var(--bookando-content-bg, #FFFFFF);
    border: var(--bookando-border-width, 1px) solid var(--bookando-input-border, #E2E6EC);
    border-radius: var(--bookando-border-radius, 6px);
    overflow: hidden;
    transition: all 0.3s;

    &:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    }

    &.featured {
      border-color: var(--bookando-primary, #1A84EE);
      box-shadow: 0 4px 16px rgba(26, 132, 238, 0.15);
    }

    .course-badge {
      position: absolute;
      top: 16px;
      right: 16px;
      z-index: 10;
      padding: 6px 14px;
      background: var(--bookando-primary, #1A84EE);
      color: #FFFFFF;
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      border-radius: calc(var(--bookando-border-radius, 6px) / 2);
    }

    .course-image {
      width: 100%;
      height: 200px;
      background: linear-gradient(135deg, #f9f9f9 0%, #f0f0f0 100%);
      display: flex;
      align-items: center;
      justify-content: center;

      &.gradient-bg {
        background: var(--bookando-gradient, linear-gradient(135deg, #1A84EE 0%, #A28FF3 100%));
      }

      .course-image-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--bookando-content-text, #7F8FA4);
        opacity: 0.5;
      }

      &.gradient-bg .course-image-placeholder {
        color: #FFFFFF;
        opacity: 1;
      }
    }

    .course-content {
      padding: 24px;

      .course-category {
        display: inline-block;
        padding: 4px 12px;
        margin-bottom: 12px;
        background: rgba(26, 132, 238, 0.1);
        color: var(--bookando-primary, #1A84EE);
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-radius: calc(var(--bookando-border-radius, 6px) / 2);
      }

      .course-title {
        margin: 0 0 12px 0;
        font-size: 20px;
        font-weight: 600;
        color: var(--bookando-content-heading, #354052);
      }

      .course-description {
        margin: 0 0 16px 0;
        font-size: 14px;
        line-height: 1.6;
        color: var(--bookando-content-text, #7F8FA4);
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
      }

      .course-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
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

      .course-footer {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 16px;

        .course-info {
          flex: 1;

          .course-price {
            font-size: 24px;
            font-weight: 700;
            color: var(--bookando-primary, #1A84EE);
            margin-bottom: 4px;
          }

          .course-instructor {
            font-size: 13px;
            color: var(--bookando-content-text, #7F8FA4);
          }
        }

        .course-button {
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
        font-size: 24px;
      }
    }

    .preview-filters {
      flex-direction: column;

      .search-wrapper {
        width: 100%;
      }
    }

    .course-grid {
      grid-template-columns: 1fr;
    }

    .course-card {
      .course-content {
        .course-footer {
          flex-direction: column;
          align-items: flex-start;

          .course-button {
            width: 100%;
          }
        }
      }
    }
  }
}
</style>
