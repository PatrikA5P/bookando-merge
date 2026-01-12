<!-- AppTableStickyTest.vue -->
<template>
  <div class="tst-host">
    <div class="tst-wrap">
      <div
        class="tst-scroll"
        role="region"
        aria-label="Kundentabelle"
      >
        <table
          class="tst-table"
          role="table"
        >
          <!-- Spaltenbreiten festlegen: erzwingt Überbreite = horizontaler Scroll -->
          <colgroup>
            <col style="width:50px"> <!-- Checkbox -->
            <col
              v-for="col in midColumns"
              :key="col.key"
              :style="{ width: (col.min ?? defaultMin) + 'px' }"
            >
            <col style="width:145px"> <!-- Aktionen -->
          </colgroup>

          <thead>
            <tr>
              <!-- Sticky Checkbox (links) -->
              <th class="tst-col tst-col--checkbox tst-sticky-left">
                <input
                  type="checkbox"
                  :checked="allChecked"
                  aria-label="Alle auswählen"
                  @change="toggleAll(($event.target as HTMLInputElement).checked)"
                >
              </th>

              <!-- Mittelspalten -->
              <th
                v-for="col in midColumns"
                :key="col.key"
                class="tst-col tst-col--mid"
              >
                <span
                  class="tst-th-text"
                  :title="col.label"
                >{{ col.label }}</span>
              </th>

              <!-- Sticky Actions (rechts) -->
              <th class="tst-col tst-col--actions tst-sticky-right">
                Aktionen
              </th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="row in rows"
              :key="row.id"
              class="tst-row"
            >
              <!-- Sticky Checkbox -->
              <td
                class="tst-col tst-col--checkbox tst-sticky-left"
                @click.stop
              >
                <input
                  type="checkbox"
                  :checked="checkedIds.has(row.id)"
                  :aria-label="`Zeile ${row.id} auswählen`"
                  @change="toggleOne(row.id, ($event.target as HTMLInputElement).checked)"
                >
              </td>

              <!-- Kunde (darf umbrechen, Breite kommt aus colgroup) -->
              <td class="tst-col tst-col--mid tst-col--customer">
                <div class="tst-customer">
                  <div class="tst-customer-name">
                    {{ row.last_name }}, {{ row.first_name }}
                  </div>
                  <div
                    v-if="row.email"
                    class="tst-customer-sub"
                  >
                    <a :href="`mailto:${row.email}`">{{ row.email }}</a>
                  </div>
                  <div
                    v-if="row.phone"
                    class="tst-customer-sub"
                  >
                    <a :href="`tel:${normalizePhone(row.phone)}`">{{ row.phone }}</a>
                  </div>
                </div>
              </td>

              <!-- restliche Mittelspalten (einzeilig + Ellipsis) -->
              <td
                v-for="col in otherMidColumns"
                :key="col.key + '-' + row.id"
                class="tst-col tst-col--mid tst-ellipsis"
              >
                <span :title="String(row[col.key] ?? '')">{{ row[col.key] ?? '–' }}</span>
              </td>

              <!-- Sticky Actions (rechts) -->
              <td
                class="tst-col tst-col--actions"
                @click.stop
              >
                <div class="tst-sticky-right">
                  <button
                    class="tst-btn tst-btn--icon"
                    aria-label="Bearbeiten"
                    @click="onEdit(row)"
                  >
                    ✏️
                  </button>

                  <div
                    :ref="setDropdownRef(row.id)"
                    class="tst-dropdown"
                  >
                    <button
                      class="tst-btn tst-btn--icon"
                      aria-haspopup="menu"
                      :aria-expanded="openDropdownId === row.id"
                      aria-label="Mehr Aktionen"
                      @click="toggleDropdown(row.id)"
                    >
                      ⋮
                    </button>

                    <ul
                      v-if="openDropdownId === row.id"
                      class="tst-menu"
                      role="menu"
                    >
                      <li role="menuitem">
                        <button
                          class="tst-menu-item"
                          @click="onDelete(row)"
                        >
                          Löschen
                        </button>
                      </li>
                      <li role="menuitem">
                        <button
                          class="tst-menu-item"
                          @click="onToggleActive(row)"
                        >
                          {{ row.active ? 'Deaktivieren' : 'Aktivieren' }}
                        </button>
                      </li>
                      <li role="menuitem">
                        <button
                          class="tst-menu-item"
                          @click="onEmail(row)"
                        >
                          E-Mail senden
                        </button>
                      </li>
                      <li role="menuitem">
                        <button
                          class="tst-menu-item"
                          @click="onCall(row)"
                        >
                          Anrufen
                        </button>
                      </li>
                    </ul>
                  </div>
                </div>
              </td>
            </tr>

            <!-- Leere-State -->
            <tr v-if="!rows.length">
              <td
                class="tst-empty"
                :colspan="2 + midColumns.length"
              >
                Keine Einträge
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>



<script setup lang="ts">
import { computed, onMounted, onBeforeUnmount, reactive, ref } from 'vue'

type MidColumn = { key: string; label: string; min?: number }

/** Breiten für die sticky Randspalten */
const CHECKBOX_W = 50
const ACTIONS_W = 145

/** Standardbreite je Daten-Spalte (kann je Spalte via `min` überschrieben werden) */
const defaultMin = 160

/** Spalten-Definitionen – inkl. 10 zusätzlicher Test-Spalten */
const customerCol: MidColumn = { key: 'customer', label: 'Kunde', min: 240 }
const midColumns: MidColumn[] = [
  customerCol,
  { key: 'email', label: 'E-Mail', min: 200 },
  { key: 'phone', label: 'Telefon', min: 160 },
  { key: 'city', label: 'Ort', min: 160 },
  { key: 'country', label: 'Land', min: 140 },
  { key: 'status', label: 'Status', min: 140 },
  { key: 'note', label: 'Notiz', min: 260 },
  { key: 'created_at', label: 'Erstellt', min: 160 },
  { key: 'updated_at', label: 'Aktualisiert', min: 160 },

  // 10 Extra-Spalten (zum Erzwingen von Überbreite)
  { key: 'department', label: 'Abteilung', min: 180 },
  { key: 'loyalty_points', label: 'Punkte', min: 140 },
  { key: 'last_login', label: 'Letzter Login', min: 180 },
  { key: 'newsletter', label: 'Newsletter', min: 160 },
  { key: 'account_manager', label: 'Betreuer', min: 200 },
  { key: 'customer_type', label: 'Typ', min: 160 },
  { key: 'preferred_contact', label: 'Kontaktweg', min: 180 },
  { key: 'discount', label: 'Rabatt', min: 140 },
  { key: 'street', label: 'Strasse', min: 220 },
  { key: 'house_no', label: 'Nr.', min: 100 }
]

const otherMidColumns = computed(() => midColumns.filter(c => c.key !== 'customer'))

/** Dummy-Daten */
const rows = reactive(Array.from({ length: 18 }).map((_, i) => ({
  id: i + 1,
  first_name: 'Max',
  last_name: 'Muster' + (i + 1),
  email: `max${i + 1}@beispiel.ch`,
  phone: '+41 44 123 45 ' + String(10 + i),
  city: ['Zürich', 'Bern', 'Basel', 'Luzern'][i % 4],
  country: ['CH', 'DE', 'AT'][i % 3],
  status: ['active', 'inactive'][i % 2],
  note: i % 3 === 0 ? 'Langer Hinweistext der abgeschnitten werden soll, um die Tabellenhöhe klein zu halten.' : '—',
  created_at: '2025-05-0' + ((i % 9) + 1),
  updated_at: '2025-07-1' + ((i % 9) + 1),
  active: i % 2 === 0,

  department: ['Sales', 'Support', 'IT', 'HR'][i % 4],
  loyalty_points: 1000 + i * 7,
  last_login: `2025-07-${String((i % 28) + 1).padStart(2, '0')} 12:${String(i % 60).padStart(2, '0')}`,
  newsletter: i % 2 === 0 ? 'abonniert' : '—',
  account_manager: ['Anna Keller', 'Jonas Frei', 'Lena Roth'][i % 3],
  customer_type: ['B2C', 'B2B'][i % 2],
  preferred_contact: ['E-Mail', 'Telefon', 'SMS'][i % 3],
  discount: (i % 5) * 5 + ' %',
  street: ['Bahnhofstrasse', 'Hauptstrasse', 'Seestrasse'][i % 3],
  house_no: String(10 + (i % 25))
})))

/** Auswahl (Checkboxen) */
const checkedIds = reactive(new Set<number>())
const allChecked = computed(() => rows.length > 0 && rows.every(r => checkedIds.has(r.id)))
function toggleAll(val: boolean) {
  checkedIds.clear()
  if (val) rows.forEach(r => checkedIds.add(r.id))
}
function toggleOne(id: number, val: boolean) {
  if (val) checkedIds.add(id); else checkedIds.delete(id)
}

/** Actions */
function onEdit(row: any) { console.log('edit', row) }
function onDelete(row: any) { console.log('delete', row); closeDropdown() }
function onToggleActive(row: any) { row.active = !row.active; console.log('toggleActive', row); closeDropdown() }
function onEmail(row: any) { console.log('email', row.email); closeDropdown() }
function onCall(row: any) { console.log('call', row.phone); closeDropdown() }
function normalizePhone(p: string) { return String(p).replace(/\s+/g, '') }

/** Dropdown-Logik */
const openDropdownId = ref<number | null>(null)
function toggleDropdown(id: number) { openDropdownId.value = openDropdownId.value === id ? null : id }
function closeDropdown() { openDropdownId.value = null }

/** Click-Outside */
const dropdownRefs = new Map<number, HTMLElement>()
function setDropdownRef(id: number) {
  return (el: HTMLElement | null) => { if (el) dropdownRefs.set(id, el); else dropdownRefs.delete(id) }
}
function onDocumentClick(event: MouseEvent) {
  if (openDropdownId.value == null) return
  const el = dropdownRefs.get(openDropdownId.value)
  if (el && !el.contains(event.target as Node)) closeDropdown()
}
onMounted(() => document.addEventListener('click', onDocumentClick))
onBeforeUnmount(() => document.removeEventListener('click', onDocumentClick))
</script>

<style scoped>
/* Host folgt dem Eltern-Container */
.tst-host { display: block; max-width: 100%; overflow: visible; }

/* Der Wrapper "tst-wrap" ist die MASK, die alles auf die Containerbreite beschneidet,
   aber Vertikal-Overflow (Dropdowns) erlaubt. */
.tst-wrap {
  width: 100%;
  background: #fff;
  border-radius: 8px;
  /* WICHTIG: nur horizontal beschneiden! */
  overflow-x: clip;     /* oder: hidden; clip vermeidet Scrollbars zuverlässig */
  overflow-y: visible;
}

/* Scroll-Ebene */
.tst-scroll {
  position: relative;
  width: 100%;
  box-sizing: border-box;
  overflow-x: auto;
  overflow-y: hidden;
  -webkit-overflow-scrolling: touch;
  scrollbar-gutter: stable both-edges;
  overscroll-behavior-x: contain;

  /* Platz für Sticky links/rechts */
  padding-left: 50px;    /* = CHECKBOX_W */
  padding-right: 145px;  /* = ACTIONS_W */
}

/* Tabelle: darf breiter werden, aber nur innerhalb von .tst-scroll */
.tst-table {
  border-collapse: separate;
  table-layout: fixed;
  width: max-content !important;  /* gegen evtl. globales table{width:100%} */
  min-width: 100%;
}

/* NIE umbrechen (außer Kunde) – stärkerer Selektor + !important */
.tst-table th,
.tst-table td {
  padding: 8px 12px;
  border-bottom: 1px solid #e6e6e6;
  text-align: left;
  vertical-align: top;
  background: #fff;
  white-space: nowrap !important;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Ellipsis auch auf dem inneren <span> erzwingen */
.tst-ellipsis,
.tst-ellipsis > span {
  display: block;
  white-space: nowrap !important;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Nur die Kunden-Spalte darf umbrochen werden */
.tst-col--customer,
.tst-col--customer * {
  white-space: normal !important;
}

/* Sticky links (Header + Zellen) */
.tst-sticky-left {
  position: sticky;
  left: 0;
  z-index: 5;
  background: #fff;
  box-shadow: 2px 0 0 rgba(0,0,0,.04);
}

/* Sticky rechts (Header + Zellen) */
.tst-sticky-right {
  position: sticky;
  right: 0;
  z-index: 7;
  background: #fff;
  box-shadow: -2px 0 0 rgba(0,0,0,.04);
  overflow: visible !important;
}

/* Fixbreiten außen (müssen mit <colgroup> matchen) */
.tst-col--checkbox {
  width: 50px; min-width: 50px; max-width: 50px;
  text-align: center; padding: 0 !important; overflow: visible !important;
}
.tst-col--actions {
  width: 145px; min-width: 145px; max-width: 145px;
  padding: 0; text-align: right; overflow: visible !important; position: relative;
}

/* Innerer Sticky-Container in der Actions-Zelle (für Dropdown/Shadow) */
.tst-col--actions .tst-sticky-right {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 6px;
  padding: 8px 12px;
  z-index: 8;
}

/* Head-Titel */
.tst-th-text { display: inline-block; max-width: 100%; overflow: hidden; text-overflow: ellipsis; }

/* Buttons/Dropdown */
.tst-btn { border: 1px solid #dcdcdc; background: #fff; border-radius: 6px; padding: 6px 8px; cursor: pointer; }
.tst-btn:hover { background: #f6f6f6; }
.tst-btn--icon { width: 32px; height: 32px; display: inline-grid; place-items: center; padding: 0; }
.tst-dropdown { display: inline-block; position: relative; margin-left: 6px; }

.tst-menu {
  position: absolute; top: 36px; right: 0;
  min-width: 220px; border: 1px solid #e6e6e6; border-radius: 8px;
  background: #fff; box-shadow: 0 8px 20px rgba(0,0,0,0.08);
  padding: 6px 0; z-index: 1000;
}
.tst-menu-item {
  display: flex; align-items: center; gap: 8px;
  padding: 8px 12px; cursor: pointer; white-space: nowrap; width: 100%;
  background: none; border: 0; text-align: left;
}
.tst-menu-item:hover { background: #f7f7f7; }

/* Nur innerhalb deines Moduls – verhindert seitliches Body-Scrolling */
:global(#bookando-customers-root) {
  overflow-x: clip;
}


</style>
