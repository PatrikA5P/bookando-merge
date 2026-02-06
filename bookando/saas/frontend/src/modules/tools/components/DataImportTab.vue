<script setup lang="ts">
/**
 * DataImportTab -- Datenimport mit CSV/Excel
 *
 * Features:
 * - 3 Import-Kategorien: Kunden, Mitarbeiter, Finanzdaten
 * - Drag & Drop Upload-Zone
 * - Import-Stepper (Upload > Validierung > Vorschau > Import)
 * - Vorschau-Tabelle mit ersten 5 Zeilen
 * - Validierungsfehler-Liste
 * - Vorlagen-Download
 */
import { ref, computed } from 'vue';
import BButton from '@/components/ui/BButton.vue';
import BTable from '@/components/ui/BTable.vue';
import BBadge from '@/components/ui/BBadge.vue';
import { useBreakpoint } from '@/composables/useBreakpoint';
import { useToast } from '@/composables/useToast';
import { useI18n } from '@/composables/useI18n';
import { BUTTON_STYLES, CARD_STYLES, BADGE_STYLES, GRID_STYLES, TABLE_STYLES } from '@/design';

const { t } = useI18n();
const toast = useToast();
const { isMobile } = useBreakpoint();

interface ImportCategory {
  id: string;
  title: string;
  description: string;
  icon: string;
  color: string;
  templateName: string;
}

const importCategories: ImportCategory[] = [
  {
    id: 'customers',
    title: t('tools.import.customers') || 'Kunden',
    description: t('tools.import.customersDesc') || 'Kundenstammdaten aus CSV oder Excel importieren',
    icon: 'users',
    color: 'emerald',
    templateName: 'kunden_vorlage.csv',
  },
  {
    id: 'employees',
    title: t('tools.import.employees') || 'Mitarbeiter',
    description: t('tools.import.employeesDesc') || 'Mitarbeiterdaten und Dienstplandaten importieren',
    icon: 'user-group',
    color: 'blue',
    templateName: 'mitarbeiter_vorlage.csv',
  },
  {
    id: 'finance',
    title: t('tools.import.finance') || 'Finanzdaten',
    description: t('tools.import.financeDesc') || 'Rechnungen, Zahlungen und Buchhaltungsdaten importieren',
    icon: 'banknote',
    color: 'purple',
    templateName: 'finanzdaten_vorlage.csv',
  },
];

// Active import state
const activeImport = ref<string | null>(null);
const importStep = ref(0); // 0=Upload, 1=Validierung, 2=Vorschau, 3=Import
const isDragging = ref(false);
const uploadedFile = ref<string | null>(null);
const importProgress = ref(0);

const steps = [
  { label: t('tools.import.stepUpload') || 'Upload', icon: 'upload' },
  { label: t('tools.import.stepValidation') || 'Validierung', icon: 'check' },
  { label: t('tools.import.stepPreview') || 'Vorschau', icon: 'eye' },
  { label: t('tools.import.stepImport') || 'Import', icon: 'download' },
];

// Mock preview data
const previewColumns = [
  { key: 'row', label: '#', width: '50px' },
  { key: 'firstName', label: t('tools.import.firstName') || 'Vorname' },
  { key: 'lastName', label: t('tools.import.lastName') || 'Nachname' },
  { key: 'email', label: t('tools.import.email') || 'E-Mail' },
  { key: 'phone', label: t('tools.import.phone') || 'Telefon' },
  { key: 'status', label: 'Status' },
];

const previewData = ref<Record<string, unknown>[]>([
  { id: '1', row: 1, firstName: 'Max', lastName: 'Muster', email: 'max@beispiel.ch', phone: '+41 79 123 45 67', status: 'valid' },
  { id: '2', row: 2, firstName: 'Anna', lastName: 'Mueller', email: 'anna@beispiel.ch', phone: '+41 78 234 56 78', status: 'valid' },
  { id: '3', row: 3, firstName: 'Peter', lastName: '', email: 'peter@beispiel', phone: '+41 76 345', status: 'error' },
  { id: '4', row: 4, firstName: 'Sarah', lastName: 'Keller', email: 'sarah@beispiel.ch', phone: '+41 79 456 78 90', status: 'valid' },
  { id: '5', row: 5, firstName: 'Thomas', lastName: 'Brunner', email: 'thomas@beispiel.ch', phone: '+41 79 567 89 01', status: 'valid' },
]);

interface ValidationError {
  row: number;
  field: string;
  message: string;
}

const validationErrors = ref<ValidationError[]>([
  { row: 3, field: t('tools.import.lastName') || 'Nachname', message: t('tools.import.errorRequired') || 'Pflichtfeld ist leer' },
  { row: 3, field: t('tools.import.email') || 'E-Mail', message: t('tools.import.errorInvalidEmail') || 'Ungueltiges E-Mail-Format' },
  { row: 3, field: t('tools.import.phone') || 'Telefon', message: t('tools.import.errorInvalidPhone') || 'Ungueltiges Telefonnummer-Format' },
]);

function startImport(categoryId: string) {
  activeImport.value = categoryId;
  importStep.value = 0;
  uploadedFile.value = null;
  importProgress.value = 0;
}

function downloadTemplate(templateName: string) {
  toast.info(`${t('tools.import.downloadingTemplate') || 'Vorlage wird heruntergeladen'}: ${templateName}`);
}

function onDragOver(e: DragEvent) {
  e.preventDefault();
  isDragging.value = true;
}

function onDragLeave() {
  isDragging.value = false;
}

function onDrop(e: DragEvent) {
  e.preventDefault();
  isDragging.value = false;
  const files = e.dataTransfer?.files;
  if (files && files.length > 0) {
    handleFileUpload(files[0]);
  }
}

function onFileSelect(e: Event) {
  const input = e.target as HTMLInputElement;
  if (input.files && input.files.length > 0) {
    handleFileUpload(input.files[0]);
  }
}

function handleFileUpload(file: File) {
  uploadedFile.value = file.name;
  importStep.value = 1;

  // Simulate validation
  setTimeout(() => {
    importStep.value = 2;
  }, 1500);
}

function executeImport() {
  importStep.value = 3;
  importProgress.value = 0;

  const interval = setInterval(() => {
    importProgress.value += 10;
    if (importProgress.value >= 100) {
      clearInterval(interval);
      toast.success(`${t('tools.import.importSuccess') || 'Import erfolgreich'}: 4 ${t('tools.import.recordsImported') || 'Eintraege importiert'}`);
      setTimeout(() => {
        activeImport.value = null;
        importStep.value = 0;
      }, 1000);
    }
  }, 300);
}

function cancelImport() {
  activeImport.value = null;
  importStep.value = 0;
  uploadedFile.value = null;
}
</script>

<template>
  <div class="space-y-6">
    <!-- Import Active View -->
    <template v-if="activeImport">
      <div :class="CARD_STYLES.base" class="overflow-hidden">
        <!-- Header -->
        <div class="p-5 border-b border-slate-200 flex items-center justify-between">
          <div>
            <h3 class="text-sm font-semibold text-slate-900">
              {{ importCategories.find(c => c.id === activeImport)?.title }} {{ t('tools.import.importTitle') || 'importieren' }}
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">
              {{ uploadedFile ? uploadedFile : t('tools.import.selectFile') || 'Datei auswaehlen oder per Drag & Drop hochladen' }}
            </p>
          </div>
          <BButton variant="ghost" size="sm" @click="cancelImport">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </BButton>
        </div>

        <!-- Stepper -->
        <div class="px-5 py-4 bg-slate-50 border-b border-slate-100">
          <div class="flex items-center justify-between max-w-lg mx-auto">
            <template v-for="(step, idx) in steps" :key="step.label">
              <div class="flex items-center gap-2">
                <div
                  :class="[
                    'w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-colors',
                    idx < importStep ? 'bg-emerald-500 text-white' : '',
                    idx === importStep ? 'bg-brand-600 text-white' : '',
                    idx > importStep ? 'bg-slate-200 text-slate-500' : '',
                  ]"
                >
                  <svg v-if="idx < importStep" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  <span v-else>{{ idx + 1 }}</span>
                </div>
                <span
                  :class="[
                    'text-xs font-medium hidden sm:inline',
                    idx <= importStep ? 'text-slate-900' : 'text-slate-400',
                  ]"
                >
                  {{ step.label }}
                </span>
              </div>
              <div
                v-if="idx < steps.length - 1"
                :class="[
                  'flex-1 h-0.5 mx-2',
                  idx < importStep ? 'bg-emerald-400' : 'bg-slate-200',
                ]"
              />
            </template>
          </div>
        </div>

        <!-- Step Content -->
        <div class="p-5">
          <!-- Step 0: Upload -->
          <div v-if="importStep === 0">
            <div
              :class="[
                'border-2 border-dashed rounded-xl p-10 text-center transition-colors cursor-pointer',
                isDragging ? 'border-brand-400 bg-brand-50' : 'border-slate-300 hover:border-slate-400 hover:bg-slate-50',
              ]"
              @dragover="onDragOver"
              @dragleave="onDragLeave"
              @drop="onDrop"
              @click="($refs.fileInput as HTMLInputElement)?.click()"
            >
              <input
                ref="fileInput"
                type="file"
                accept=".csv,.xlsx,.xls"
                class="hidden"
                @change="onFileSelect"
              />
              <svg class="w-12 h-12 mx-auto text-slate-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
              </svg>
              <p class="text-sm font-medium text-slate-700">
                {{ t('tools.import.dragDrop') || 'Datei hierher ziehen oder klicken' }}
              </p>
              <p class="text-xs text-slate-400 mt-1">CSV, XLSX, XLS (max. 10 MB)</p>
            </div>
          </div>

          <!-- Step 1: Validierung -->
          <div v-else-if="importStep === 1" class="flex flex-col items-center py-8">
            <svg class="w-12 h-12 text-brand-500 animate-spin mb-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
            </svg>
            <p class="text-sm font-medium text-slate-700">{{ t('tools.import.validating') || 'Datei wird validiert...' }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ uploadedFile }}</p>
          </div>

          <!-- Step 2: Vorschau -->
          <div v-else-if="importStep === 2" class="space-y-4">
            <!-- Summary -->
            <div class="flex flex-wrap gap-3">
              <div :class="CARD_STYLES.ghost" class="px-4 py-2 flex items-center gap-2">
                <span class="text-xs text-slate-500">{{ t('tools.import.totalRows') || 'Zeilen' }}:</span>
                <span class="text-sm font-semibold text-slate-900">5</span>
              </div>
              <div :class="CARD_STYLES.ghost" class="px-4 py-2 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500" />
                <span class="text-xs text-slate-500">{{ t('tools.import.valid') || 'Gueltig' }}:</span>
                <span class="text-sm font-semibold text-emerald-700">4</span>
              </div>
              <div :class="CARD_STYLES.ghost" class="px-4 py-2 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-red-500" />
                <span class="text-xs text-slate-500">{{ t('tools.import.errors') || 'Fehler' }}:</span>
                <span class="text-sm font-semibold text-red-700">1</span>
              </div>
            </div>

            <!-- Preview Table -->
            <BTable
              :columns="previewColumns"
              :data="previewData"
              :empty-title="t('tools.import.noData') || 'Keine Daten'"
            >
              <template #cell-status="{ row }">
                <BBadge :variant="(row as any).status === 'valid' ? 'success' : 'danger'">
                  {{ (row as any).status === 'valid'
                    ? (t('tools.import.valid') || 'Gueltig')
                    : (t('tools.import.errorLabel') || 'Fehler')
                  }}
                </BBadge>
              </template>
            </BTable>

            <!-- Validation Errors -->
            <div v-if="validationErrors.length > 0" class="mt-4">
              <h4 class="text-sm font-semibold text-red-700 mb-2">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
                {{ t('tools.import.validationErrors') || 'Validierungsfehler' }} ({{ validationErrors.length }})
              </h4>
              <div class="space-y-1">
                <div
                  v-for="(error, idx) in validationErrors"
                  :key="idx"
                  class="flex items-center gap-3 text-xs bg-red-50 border border-red-100 rounded-lg px-3 py-2"
                >
                  <span class="text-red-500 font-medium">{{ t('tools.import.row') || 'Zeile' }} {{ error.row }}</span>
                  <span class="text-red-400">{{ error.field }}</span>
                  <span class="text-red-600">{{ error.message }}</span>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
              <BButton variant="secondary" @click="cancelImport">
                {{ t('common.cancel') || 'Abbrechen' }}
              </BButton>
              <BButton variant="primary" @click="executeImport">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                {{ t('tools.import.startImport') || '4 Eintraege importieren' }}
              </BButton>
            </div>
          </div>

          <!-- Step 3: Import Progress -->
          <div v-else-if="importStep === 3" class="flex flex-col items-center py-8">
            <div class="w-full max-w-xs mb-4">
              <div class="flex items-center justify-between text-xs text-slate-600 mb-1">
                <span>{{ t('tools.import.importing') || 'Importiere...' }}</span>
                <span>{{ importProgress }}%</span>
              </div>
              <div class="h-2 bg-slate-200 rounded-full overflow-hidden">
                <div
                  class="h-full bg-brand-600 rounded-full transition-all duration-300"
                  :style="{ width: `${importProgress}%` }"
                />
              </div>
            </div>
            <p v-if="importProgress >= 100" class="text-sm font-medium text-emerald-600">
              <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              {{ t('tools.import.importComplete') || 'Import abgeschlossen!' }}
            </p>
          </div>
        </div>
      </div>
    </template>

    <!-- Import Category Cards -->
    <template v-else>
      <div :class="GRID_STYLES.cols3">
        <div
          v-for="cat in importCategories"
          :key="cat.id"
          :class="CARD_STYLES.hover"
          class="p-6"
        >
          <div class="flex items-start gap-4">
            <div
              :class="[
                'w-12 h-12 rounded-xl flex items-center justify-center shrink-0',
                cat.color === 'emerald' ? 'bg-emerald-100 text-emerald-600' : '',
                cat.color === 'blue' ? 'bg-blue-100 text-blue-600' : '',
                cat.color === 'purple' ? 'bg-purple-100 text-purple-600' : '',
              ]"
            >
              <!-- Users icon -->
              <svg v-if="cat.icon === 'users'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <!-- User-Group icon -->
              <svg v-else-if="cat.icon === 'user-group'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
              <!-- Banknote icon -->
              <svg v-else-if="cat.icon === 'banknote'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <h3 class="text-sm font-semibold text-slate-900">{{ cat.title }}</h3>
              <p class="text-xs text-slate-500 mt-1">{{ cat.description }}</p>
            </div>
          </div>

          <!-- Drag & Drop Zone -->
          <div
            class="mt-4 border-2 border-dashed border-slate-200 rounded-lg p-4 text-center hover:border-slate-400 hover:bg-slate-50 transition-colors cursor-pointer"
            @click="startImport(cat.id)"
          >
            <svg class="w-8 h-8 mx-auto text-slate-300 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
            </svg>
            <p class="text-xs text-slate-500">{{ t('tools.import.dropHere') || 'CSV/Excel hierher ziehen' }}</p>
          </div>

          <!-- Actions -->
          <div class="mt-4 flex items-center gap-2">
            <BButton variant="secondary" size="sm" class="flex-1 !text-xs" @click="downloadTemplate(cat.templateName)">
              <svg class="w-3.5 h-3.5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              {{ t('tools.import.downloadTemplate') || 'Vorlage herunterladen' }}
            </BButton>
            <BButton variant="primary" size="sm" class="flex-1 !text-xs" @click="startImport(cat.id)">
              <svg class="w-3.5 h-3.5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
              </svg>
              {{ t('tools.import.import') || 'Importieren' }}
            </BButton>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
