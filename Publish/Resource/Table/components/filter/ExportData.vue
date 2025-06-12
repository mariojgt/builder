<template>
  <div class="relative export-data-container" v-click-outside="closeDropdown">
    <button
      @click="toggleDropdown"
      class="btn btn-secondary gap-2 group relative overflow-hidden shadow-md hover:shadow-lg transition-all duration-300 hover:scale-105"
      :disabled="isExporting || totalRecords === 0"
    >
      <div class="absolute inset-0 bg-gradient-to-r from-secondary/50 to-accent/50 opacity-0 group-hover:opacity-30 transition-opacity duration-300"></div>

      <DownloadIcon class="w-4 h-4 relative z-10 transition-all duration-300" :class="[
        isExporting ? 'animate-bounce text-secondary-content' : 'group-hover:text-secondary-content'
      ]" />

      <span class="relative z-10 font-medium transition-colors duration-300 group-hover:text-secondary-content">
        {{ isExporting ? 'Exporting...' : 'Export Data' }}
      </span>

      <div v-if="totalRecords > 0" class="relative z-10">
        <div class="badge badge-secondary-content badge-sm">
          {{ formatNumber(totalRecords) }}
        </div>
      </div>

      <div v-if="isExporting" class="loading loading-spinner loading-sm relative z-10 ml-1"></div>

      <div class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/20 to-transparent group-hover:translate-x-full transition-transform duration-700 ease-out"></div>
    </button>

    <Transition
      enter-active-class="transition-all duration-200 ease-out"
      enter-from-class="opacity-0 scale-95 -translate-y-2"
      enter-to-class="opacity-100 scale-100 translate-y-0"
      leave-active-class="transition-all duration-150 ease-in"
      leave-from-class="opacity-100 scale-100 translate-y-0"
      leave-to-class="opacity-0 scale-95 -translate-y-2"
    >
      <div v-if="isOpen"
        class="absolute right-0 top-full mt-2 bg-base-100 rounded-lg shadow-xl border border-base-200 p-2 min-w-[200px] z-50">
        <div class="px-3 py-2 text-sm text-base-content/70 font-semibold border-b border-base-200 mb-1">
            Export Options
        </div>

        <button
          @click="exportToCSV"
          :disabled="isExporting"
          class="btn btn-ghost btn-sm w-full justify-start gap-3 rounded-md mb-1 text-base-content hover:bg-primary/10 hover:text-primary transition-colors duration-200"
        >
          <FileTextIcon class="w-4 h-4 text-primary" />
          Export as CSV
          <span class="badge badge-xs badge-outline badge-primary ml-auto">Fast</span>
        </button>

        <button
          @click="exportToJSON"
          :disabled="isExporting"
          class="btn btn-ghost btn-sm w-full justify-start gap-3 rounded-md text-base-content hover:bg-secondary/10 hover:text-secondary transition-colors duration-200"
        >
          <FileJsonIcon class="w-4 h-4 text-secondary" />
          Export as JSON
          <span class="badge badge-xs badge-outline badge-secondary ml-auto">Clean</span>
        </button>

        <button
          disabled
          class="btn btn-ghost btn-sm w-full justify-start gap-3 rounded-md mt-1 text-base-content/60"
        >
          <MoreHorizontalIcon class="w-4 h-4" />
          More Formats (Coming Soon)
        </button>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { startWindToast } from "@mariojgt/wind-notify/packages/index.js"; // Assuming you want toasts
import {
  Download as DownloadIcon,
  ChevronDown as ChevronDownIcon,
  FileText as FileTextIcon,
  FileJson as FileJsonIcon,
  MoreHorizontal as MoreHorizontalIcon // New icon for "More Formats"
} from 'lucide-vue-next';

interface Column {
  key: string;
  label: string;
  type?: string;
  exportable?: boolean; // Added this for robustness, though not used in your current filter
}

interface Props {
  data: Record<string, any>[]; // The actual data to be exported (current page or all data)
  columns: Column[]; // All available columns definition
  hiddenColumns: Set<string>; // Set of keys of currently hidden columns
  filename?: string; // Base filename for export
  totalRecords?: number; // Total number of records available (for disabling button if 0)
}

const props = withDefaults(defineProps<Props>(), {
  data: () => [],
  columns: () => [],
  hiddenColumns: () => new Set(),
  filename: 'exported-data',
  totalRecords: 0 // Default to 0, will be passed from parent (e.g., paginationInfo.total)
});

// State management
const isOpen = ref(false);
const isExporting = ref(false); // To show loading state during export

// Helper function to get visible columns (and exportable ones)
const getVisibleColumns = () => {
  // Filter by columns that are not hidden AND are marked as exportable (if the prop exists)
  return props.columns.filter(col => !props.hiddenColumns.has(col.key) && col.exportable !== false);
};

// Helper function to filter object by visible columns and map to labels
const filterDataByVisibleColumns = (item: Record<string, any>) => {
  const visibleColumns = getVisibleColumns();
  return visibleColumns.reduce((acc, col) => {
    // Use column label as header, value from item's key
    acc[col.label] = item[col.key];
    return acc;
  }, {} as Record<string, any>);
};

// Helper function to escape CSV values
const escapeCSV = (value: any): string => {
  if (value === null || value === undefined) return '';
  const stringValue = String(value);
  // Check if string contains comma, double quote, or newline
  if (stringValue.includes(',') || stringValue.includes('"') || stringValue.includes('\n')) {
    // Enclose in double quotes and escape existing double quotes
    return `"${stringValue.replace(/"/g, '""')}"`;
  }
  return stringValue;
};

// Helper function to convert data to CSV
const convertToCSV = (data: Record<string, any>[]): string => {
  if (data.length === 0) return '';

  // Get headers from the keys of the first processed item
  // We need to process one item first to get the headers based on visible columns
  const firstProcessedItem = filterDataByVisibleColumns(data[0]); // Pass the actual data array
  const headers = Object.keys(firstProcessedItem);

  // Create CSV rows
  const csvRows = [
    // Headers row
    headers.join(','),
    // Data rows
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

// Helper for number formatting (e.g., 10000 -> 10,000)
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
  await new Promise(resolve => setTimeout(resolve, 500)); // Simulate a small delay for UI feedback
  try {
    const csv = convertToCSV(props.data); // Pass the raw data prop
    downloadFile(csv, `${props.filename}.csv`, 'text/csv');
    startWindToast('success', 'CSV export completed successfully!', 'success');
  } catch (error) {
    console.error('Error exporting CSV:', error);
    startWindToast('error', 'Failed to export CSV. Please try again.', 'error');
  } finally {
    isExporting.value = false;
    closeDropdown();
  }
};

const exportToJSON = async () => {
  if (props.data.length === 0) {
    startWindToast('info', 'No data to export.', 'info');
    return;
  }
  isExporting.value = true;
  await new Promise(resolve => setTimeout(resolve, 500)); // Simulate a small delay for UI feedback
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
    closeDropdown();
  }
};

// Dropdown controls
const toggleDropdown = () => {
  isOpen.value = !isOpen.value;
};

const closeDropdown = () => {
  isOpen.value = false;
};

// Click outside directive (remains the same)
const vClickOutside = {
  mounted(el: HTMLElement, binding: any) {
    el.clickOutsideEvent = (event: Event) => {
      if (!(el === event.target || el.contains(event.target as Node))) {
        binding.value();
      }
    };
    document.addEventListener('click', el.clickOutsideEvent);
  },
  unmounted(el: HTMLElement) {
    document.removeEventListener('click', el.clickOutsideEvent);
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
  .min-w-[200px] {
    min-width: 180px;
  }
}
</style>
