<template>
  <!-- Export Modal -->
  <div
    v-if="props.isModalOpen"
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
  >
    <!-- Backdrop -->
    <div
      class="absolute inset-0 bg-black/50 backdrop-blur-sm"
      @click="closeModal"
    ></div>

    <!-- Modal Content -->
    <div class="relative bg-base-100 rounded-2xl shadow-2xl border border-base-200 w-full max-w-md mx-4 overflow-hidden">
      <!-- Header -->
      <div class="bg-gradient-to-r from-secondary/20 via-accent/20 to-secondary/20 px-6 py-4 border-b border-base-200">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-secondary/20 flex items-center justify-center">
              <DownloadIcon class="w-5 h-5 text-secondary" />
            </div>
            <div>
              <h3 class="font-bold text-lg text-base-content">Export Data</h3>
              <p class="text-sm text-base-content/60">Choose your preferred format</p>
            </div>
          </div>
          <button
            @click="closeModal"
            class="btn btn-sm btn-ghost btn-circle group hover:bg-error/10 hover:border-error/20"
            aria-label="Close export modal"
          >
            <XIcon class="w-4 h-4 group-hover:text-error transition-colors duration-200" />
          </button>
        </div>

        <!-- Stats -->
        <div class="mt-4 p-3 bg-base-100/50 rounded-lg border border-base-200/50">
          <div class="flex items-center justify-between text-sm">
            <span class="text-base-content/70">Records to export:</span>
            <span class="font-semibold text-primary">{{ formatNumber(props.data.length) }} items</span>
          </div>
          <div class="flex items-center justify-between text-sm mt-1">
            <span class="text-base-content/70">Visible columns:</span>
            <span class="font-semibold text-secondary">{{ getVisibleColumns().length }} columns</span>
          </div>
        </div>
      </div>

      <!-- Export Options -->
      <div class="p-6 space-y-4">
        <!-- CSV Export -->
        <div class="group relative">
          <button
            @click="exportToCSV"
            :disabled="isExporting"
            class="w-full p-4 bg-gradient-to-r from-primary/5 to-primary/10 hover:from-primary/10 hover:to-primary/20 rounded-xl border border-primary/20 hover:border-primary/40 transition-all duration-300 text-left group-hover:shadow-lg group-hover:scale-[1.02]"
            :class="{ 'opacity-50 cursor-not-allowed': isExporting }"
          >
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 rounded-xl bg-primary/20 flex items-center justify-center group-hover:bg-primary/30 transition-colors duration-300">
                <FileTextIcon class="w-6 h-6 text-primary" />
              </div>
              <div class="flex-1">
                <h4 class="font-semibold text-base-content mb-1">Export as CSV</h4>
                <p class="text-sm text-base-content/60">Comma-separated values, perfect for Excel</p>
                <div class="flex items-center gap-2 mt-2">
                  <span class="badge badge-primary badge-sm">Fast</span>
                  <span class="badge badge-outline badge-sm">Excel Compatible</span>
                </div>
              </div>
              <div class="text-primary/60 group-hover:text-primary transition-colors duration-300">
                <ChevronRightIcon class="w-5 h-5" />
              </div>
            </div>
          </button>
        </div>

        <!-- JSON Export -->
        <div class="group relative">
          <button
            @click="exportToJSON"
            :disabled="isExporting"
            class="w-full p-4 bg-gradient-to-r from-secondary/5 to-secondary/10 hover:from-secondary/10 hover:to-secondary/20 rounded-xl border border-secondary/20 hover:border-secondary/40 transition-all duration-300 text-left group-hover:shadow-lg group-hover:scale-[1.02]"
            :class="{ 'opacity-50 cursor-not-allowed': isExporting }"
          >
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 rounded-xl bg-secondary/20 flex items-center justify-center group-hover:bg-secondary/30 transition-colors duration-300">
                <FileJsonIcon class="w-6 h-6 text-secondary" />
              </div>
              <div class="flex-1">
                <h4 class="font-semibold text-base-content mb-1">Export as JSON</h4>
                <p class="text-sm text-base-content/60">Structured data format for developers</p>
                <div class="flex items-center gap-2 mt-2">
                  <span class="badge badge-secondary badge-sm">Clean</span>
                  <span class="badge badge-outline badge-sm">Developer Friendly</span>
                </div>
              </div>
              <div class="text-secondary/60 group-hover:text-secondary transition-colors duration-300">
                <ChevronRightIcon class="w-5 h-5" />
              </div>
            </div>
          </button>
        </div>

        <!-- Coming Soon Option -->
        <div class="relative">
          <button
            disabled
            class="w-full p-4 bg-base-200/30 rounded-xl border border-base-200 text-left opacity-60 cursor-not-allowed"
          >
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 rounded-xl bg-base-300 flex items-center justify-center">
                <MoreHorizontalIcon class="w-6 h-6 text-base-content/40" />
              </div>
              <div class="flex-1">
                <h4 class="font-semibold text-base-content/60 mb-1">More Formats</h4>
                <p class="text-sm text-base-content/40">PDF, Excel, XML and more formats</p>
                <div class="flex items-center gap-2 mt-2">
                  <span class="badge badge-ghost badge-sm">Coming Soon</span>
                </div>
              </div>
            </div>
          </button>
          <!-- Coming Soon Overlay -->
          <div class="absolute inset-0 flex items-center justify-center bg-base-100/80 backdrop-blur-sm rounded-xl">
            <span class="text-sm font-medium text-base-content/60">Coming Soon</span>
          </div>
        </div>
      </div>

      <!-- Loading Overlay -->
      <div v-if="isExporting" class="absolute inset-0 bg-base-100/90 backdrop-blur-sm flex items-center justify-center z-50">
        <div class="text-center">
          <div class="loading loading-spinner loading-lg text-primary mb-4"></div>
          <h4 class="font-semibold text-base-content mb-2">Preparing Export...</h4>
          <p class="text-sm text-base-content/60">This will only take a moment</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { startWindToast } from "@mariojgt/wind-notify/packages/index.js";
import {
  Download as DownloadIcon,
  ChevronDown as ChevronDownIcon,
  ChevronRight as ChevronRightIcon,
  FileText as FileTextIcon,
  FileJson as FileJsonIcon,
  MoreHorizontal as MoreHorizontalIcon,
  X as XIcon
} from 'lucide-vue-next';

interface Column {
  key: string;
  label: string;
  type?: string;
  exportable?: boolean;
}

interface Props {
  data: Record<string, any>[];
  columns: Column[];
  hiddenColumns: Set<string>;
  filename?: string;
  totalRecords?: number;
  isModalOpen?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  data: () => [],
  columns: () => [],
  hiddenColumns: () => new Set(),
  filename: 'exported-data',
  totalRecords: 0,
  isModalOpen: false
});

const emit = defineEmits<{
  closeModal: [];
}>();

// State management
const isExporting = ref(false);

// Modal controls
const closeModal = () => {
  emit('closeModal');
};

// Helper function to get visible columns
const getVisibleColumns = () => {
  return props.columns.filter(col => !props.hiddenColumns.has(col.key) && col.exportable !== false);
};

// Helper function to filter object by visible columns and map to labels
const filterDataByVisibleColumns = (item: Record<string, any>) => {
  const visibleColumns = getVisibleColumns();
  return visibleColumns.reduce((acc, col) => {
    acc[col.label] = item[col.key];
    return acc;
  }, {} as Record<string, any>);
};

// Helper function to escape CSV values
const escapeCSV = (value: any): string => {
  if (value === null || value === undefined) return '';
  const stringValue = String(value);
  if (stringValue.includes(',') || stringValue.includes('"') || stringValue.includes('\n')) {
    return `"${stringValue.replace(/"/g, '""')}"`;
  }
  return stringValue;
};

// Helper function to convert data to CSV
const convertToCSV = (data: Record<string, any>[]): string => {
  if (data.length === 0) return '';

  const firstProcessedItem = filterDataByVisibleColumns(data[0]);
  const headers = Object.keys(firstProcessedItem);

  const csvRows = [
    headers.join(','),
    ...data.map(item => {
      const processedItem = filterDataByVisibleColumns(item);
      return headers.map(header => escapeCSV(processedItem[header])).join(',');
    })
  ];

  return csvRows.join('\n');
};

// Helper function to download file
const downloadFile = (content: string, filename: string, type: string) => {
  const blob = new Blob([content], { type });
  const url = window.URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  window.URL.revokeObjectURL(url);
};

// Helper for number formatting
const formatNumber = (num: number): string => {
  if (num === null || num === undefined) return '0';
  return new Intl.NumberFormat().format(num);
};

// Export functions
const exportToCSV = async () => {
  if (props.data.length === 0) {
    startWindToast('info', 'No data to export.', 'info');
    return;
  }
  isExporting.value = true;
  await new Promise(resolve => setTimeout(resolve, 800));
  try {
    const csv = convertToCSV(props.data);
    downloadFile(csv, `${props.filename}.csv`, 'text/csv');
    startWindToast('success', 'CSV export completed successfully!', 'success');
  } catch (error) {
    console.error('Error exporting CSV:', error);
    startWindToast('error', 'Failed to export CSV. Please try again.', 'error');
  } finally {
    isExporting.value = false;
    closeModal();
  }
};

const exportToJSON = async () => {
  if (props.data.length === 0) {
    startWindToast('info', 'No data to export.', 'info');
    return;
  }
  isExporting.value = true;
  await new Promise(resolve => setTimeout(resolve, 800));
  try {
    const visibleData = props.data.map(filterDataByVisibleColumns);
    const json = JSON.stringify(visibleData, null, 2);
    downloadFile(json, `${props.filename}.json`, 'application/json');
    startWindToast('success', 'JSON export completed successfully!', 'success');
  } catch (error) {
    console.error('Error exporting JSON:', error);
    startWindToast('error', 'Failed to export JSON. Please try again.', 'error');
  } finally {
    isExporting.value = false;
    closeModal();
  }
};
</script>

<style scoped>
/* Enhanced Button Animations */
.btn {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn:hover:not(:disabled) {
  transform: translateY(-1px);
}

.btn:active:not(:disabled) {
  transform: translateY(0) scale(0.98);
}

/* Loading Spinner Enhancement */
.loading-spinner {
  filter: drop-shadow(0 0 8px hsl(var(--s) / 0.3));
}

/* Enhanced Dropdown Shadow */
.shadow-xl {
  box-shadow:
    0 20px 25px -5px rgba(0, 0, 0, 0.1),
    0 10px 10px -5px rgba(0, 0, 0, 0.04),
    0 0 0 1px hsl(var(--b3) / 0.05); /* subtle border alternative */
}

/* Specific button hover effects within dropdown */
.btn-ghost.hover\:bg-primary\/10:hover {
  background-color: hsl(var(--p) / 0.1);
}
.btn-ghost.hover\:text-primary:hover {
  color: hsl(var(--p));
}
.btn-ghost.hover\:bg-secondary\/10:hover {
  background-color: hsl(var(--s) / 0.1);
}
.btn-ghost.hover\:text-secondary:hover {
  color: hsl(var(--s));
}

/* Responsive adjustments if needed */
@media (max-width: 640px) {
  .export-card {
    min-width: 180px;
  }
}
</style>
