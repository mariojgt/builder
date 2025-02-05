# components/ExportData.vue
<template>
  <div class="relative" v-click-outside="closeDropdown">
    <!-- Export Button -->
    <button @click="toggleDropdown" class="btn btn-ghost btn-sm gap-2">
      <DownloadIcon class="w-4 h-4" />
      <span class="hidden sm:inline">Export</span>
      <ChevronDownIcon
        class="w-4 h-4 transition-transform"
        :class="{ 'rotate-180': isOpen }"
      />
    </button>

    <!-- Dropdown Menu -->
    <div v-if="isOpen"
      class="absolute right-0 top-full mt-2 bg-base-100 rounded-lg shadow-lg border border-base-300 p-2 min-w-[200px] z-50">
      <button
        @click="exportToCSV"
        class="btn btn-ghost btn-sm w-full justify-start gap-2 mb-1"
      >
        <FileTextIcon class="w-4 h-4" />
        Export as CSV
      </button>
      <button
        @click="exportToJSON"
        class="btn btn-ghost btn-sm w-full justify-start gap-2"
      >
        <FileJsonIcon class="w-4 h-4" />
        Export as JSON
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import {
  Download as DownloadIcon,
  ChevronDown as ChevronDownIcon,
  FileText as FileTextIcon,
  FileJson as FileJsonIcon
} from 'lucide-vue-next';

interface Column {
  key: string;
  label: string;
  type?: string;
}

interface Props {
  data: Record<string, any>[];
  columns: Column[];
  hiddenColumns: Set<string>;
  filename?: string;
}

const props = withDefaults(defineProps<Props>(), {
  data: () => [],
  columns: () => [],
  hiddenColumns: () => new Set(),
  filename: 'exported-data'
});

const isOpen = ref(false);

// Helper function to get visible columns
const getVisibleColumns = () => {
  return props.columns.filter(col => !props.hiddenColumns.has(col.key));
};

// Helper function to filter object by visible columns
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

  // Get headers from the first item's keys
  const headers = Object.keys(data[0]);

  // Create CSV rows
  const csvRows = [
    // Headers row
    headers.join(','),
    // Data rows
    ...data.map(row =>
      headers.map(header => escapeCSV(row[header])).join(',')
    )
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

// Export functions
const exportToCSV = () => {
  const visibleData = props.data.map(filterDataByVisibleColumns);
  const csv = convertToCSV(visibleData);
  downloadFile(csv, `${props.filename}.csv`, 'text/csv');
  closeDropdown();
};

const exportToJSON = () => {
  const visibleData = props.data.map(filterDataByVisibleColumns);
  const json = JSON.stringify(visibleData, null, 2);
  downloadFile(json, `${props.filename}.json`, 'application/json');
  closeDropdown();
};

// Dropdown controls
const toggleDropdown = () => {
  isOpen.value = !isOpen.value;
};

const closeDropdown = () => {
  isOpen.value = false;
};

// Click outside directive
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
