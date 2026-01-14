<!-- ServiceCatalogPreview.vue - Live Preview Component for Service Catalog -->
<template>
  <div
    class="service-catalog-preview"
    :style="cssVariables"
  >
    <!-- Header/Sidebar -->
    <div class="preview-sidebar">
      <h3 class="sidebar-title">
        Kategorien
      </h3>
      <ul class="category-list">
        <li class="category-item active">
          Alle Dienstleistungen
        </li>
        <li class="category-item">
          Massage
        </li>
        <li class="category-item">
          Kosmetik
        </li>
        <li class="category-item">
          Fitness
        </li>
      </ul>
    </div>

    <!-- Main Content -->
    <div class="preview-content">
      <!-- Search & Filters -->
      <div class="content-header">
        <div class="search-bar">
          <input
            type="text"
            class="search-input"
            placeholder="Dienstleistung suchen..."
          >
        </div>

        <select class="filter-dropdown">
          <option>Nach Mitarbeiter filtern</option>
          <option>John Doe</option>
          <option>Jane Smith</option>
        </select>
      </div>

      <!-- Service Cards -->
      <div class="service-grid">
        <div class="service-card">
          <div class="service-badge">
            Beliebt
          </div>
          <div class="service-image">
            <div class="service-image-placeholder">
              <svg
                width="40"
                height="40"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path
                  d="M21 16V8C21 6.9 20.1 6 19 6H5C3.9 6 3 6.9 3 8V16C3 17.1 3.9 18 5 18H19C20.1 18 21 17.1 21 16Z"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
                <path
                  d="M7 10L12 13L17 10"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>
            </div>
          </div>

          <div class="service-content">
            <h4 class="service-title">
              Entspannungsmassage
            </h4>
            <p class="service-description">
              Eine wohltuende Massage für Körper und Geist. Ideal zum Entspannen nach einem stressigen Tag.
            </p>

            <div class="service-meta">
              <span class="meta-item">
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
                60 Min
              </span>
              <span class="meta-item">
                <svg
                  width="16"
                  height="16"
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
                Studio A
              </span>
            </div>

            <div class="service-footer">
              <div class="service-price">
                89,00 €
              </div>
              <button class="service-button">
                Jetzt buchen
              </button>
            </div>
          </div>
        </div>

        <div class="service-card">
          <div class="service-image">
            <div class="service-image-placeholder">
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
          </div>

          <div class="service-content">
            <h4 class="service-title">
              Gesichtsbehandlung
            </h4>
            <p class="service-description">
              Professionelle Gesichtspflege mit hochwertigen Produkten für strahlende Haut.
            </p>

            <div class="service-meta">
              <span class="meta-item">
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
                45 Min
              </span>
              <span class="meta-item">
                <svg
                  width="16"
                  height="16"
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
                Studio B
              </span>
            </div>

            <div class="service-footer">
              <div class="service-price">
                120,00 €
              </div>
              <button class="service-button">
                Jetzt buchen
              </button>
            </div>
          </div>
        </div>

        <div class="service-card">
          <div class="service-image">
            <div class="service-image-placeholder gradient-bg">
              <svg
                width="40"
                height="40"
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

          <div class="service-content">
            <h4 class="service-title">
              Personal Training
            </h4>
            <p class="service-description">
              Individuelles Training mit erfahrenem Coach für maximale Erfolge.
            </p>

            <div class="service-meta">
              <span class="meta-item">
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
                90 Min
              </span>
              <span class="meta-item">
                <svg
                  width="16"
                  height="16"
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
                Fitnessstudio
              </span>
            </div>

            <div class="service-footer">
              <div class="service-price">
                150,00 €
              </div>
              <button class="service-button">
                Jetzt buchen
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
/* Live Preview uses CSS Variables for real-time updates */
.service-catalog-preview {
  display: flex;
  min-height: 600px;
  font-family: var(--bookando-font-family, 'Inter');

  /* Sidebar */
  .preview-sidebar {
    width: 250px;
    background: var(--bookando-sidebar-bg, #1F2937);
    color: var(--bookando-sidebar-text, #F9FAFB);
    padding: 24px;
    border-right: var(--bookando-border-width, 1px) solid var(--bookando-input-border, #E2E6EC);

    .sidebar-title {
      margin: 0 0 20px 0;
      font-size: 18px;
      font-weight: 500;
      color: var(--bookando-sidebar-text, #F9FAFB);
    }

    .category-list {
      list-style: none;
      padding: 0;
      margin: 0;

      .category-item {
        padding: 12px 16px;
        margin-bottom: 4px;
        border-radius: var(--bookando-border-radius, 6px);
        cursor: pointer;
        transition: all 0.2s;
        color: var(--bookando-sidebar-text, #F9FAFB);
        opacity: 0.7;

        &:hover {
          opacity: 1;
          background: rgba(255, 255, 255, 0.1);
        }

        &.active {
          opacity: 1;
          background: var(--bookando-primary, #1A84EE);
          color: #ffffff;
        }
      }
    }
  }

  /* Main Content */
  .preview-content {
    flex: 1;
    background: var(--bookando-content-bg, #FFFFFF);
    padding: 24px;

    .content-header {
      display: flex;
      gap: 12px;
      margin-bottom: 24px;

      .search-bar {
        flex: 1;
      }

      .search-input,
      .filter-dropdown {
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

      .filter-dropdown {
        min-width: 200px;
      }
    }

    /* Service Grid */
    .service-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 20px;

      .service-card {
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

        .service-badge {
          position: absolute;
          top: 12px;
          right: 12px;
          z-index: 10;
          padding: 4px 12px;
          background: var(--bookando-primary, #1A84EE);
          color: #ffffff;
          font-size: 11px;
          font-weight: 600;
          text-transform: uppercase;
          letter-spacing: 0.5px;
          border-radius: calc(var(--bookando-border-radius, 6px) / 2);
        }

        .service-image {
          width: 100%;
          height: 180px;
          background: linear-gradient(135deg, #f9f9f9 0%, #f0f0f0 100%);
          display: flex;
          align-items: center;
          justify-content: center;

          &.gradient-bg {
            background: var(--bookando-gradient, linear-gradient(135deg, #1A84EE 0%, #A28FF3 100%));
          }

          .service-image-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--bookando-content-text, #7F8FA4);
            opacity: 0.5;

            &.gradient-bg {
              color: #ffffff;
              opacity: 1;
            }
          }
        }

        .service-content {
          padding: 20px;

          .service-title {
            margin: 0 0 12px 0;
            font-size: 18px;
            font-weight: 600;
            color: var(--bookando-content-heading, #354052);
          }

          .service-description {
            margin: 0 0 16px 0;
            font-size: 14px;
            line-height: 1.6;
            color: var(--bookando-content-text, #7F8FA4);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
          }

          .service-meta {
            display: flex;
            gap: 16px;
            margin-bottom: 16px;

            .meta-item {
              display: flex;
              align-items: center;
              gap: 6px;
              font-size: 13px;
              color: var(--bookando-content-text, #7F8FA4);

              svg {
                color: var(--bookando-primary, #1A84EE);
              }
            }
          }

          .service-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 16px;
            border-top: var(--bookando-border-width, 1px) solid var(--bookando-input-border, #E2E6EC);

            .service-price {
              font-size: 20px;
              font-weight: 700;
              color: var(--bookando-primary, #1A84EE);
            }

            .service-button {
              padding: 10px 20px;
              background: var(--bookando-btn-primary-bg, #1A84EE);
              color: var(--bookando-btn-primary-text, #FFFFFF);
              border: none;
              border-radius: var(--bookando-border-radius, 6px);
              font-size: 14px;
              font-weight: 500;
              font-family: inherit;
              cursor: pointer;
              transition: all 0.2s;

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
    }
  }

  /* Responsive */
  @media (max-width: 768px) {
    flex-direction: column;

    .preview-sidebar {
      width: 100%;
      border-right: none;
      border-bottom: var(--bookando-border-width, 1px) solid var(--bookando-input-border, #E2E6EC);
    }

    .preview-content {
      .content-header {
        flex-direction: column;

        .filter-dropdown {
          min-width: 100%;
        }
      }

      .service-grid {
        grid-template-columns: 1fr;
      }
    }
  }
}
</style>
