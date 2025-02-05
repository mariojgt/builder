<template>
  <div class="relative" v-click-outside="closeManager">
    <button @click="toggleManager" class="btn btn-ghost btn-sm gap-2">
      <EyeIcon class="w-4 h-4" />
      <span>Columns ({{ visibleCount }}/{{ totalColumns }})</span>
      <ChevronDownIcon
        class="w-4 h-4 transition-transform"
        :class="{ 'rotate-180': isOpen }"
      />
    </button>

    <div v-if="isOpen"
      class="absolute right-0 top-full mt-2 bg-base-100 rounded-lg shadow-lg border border-base-300 p-4 min-w-[240px] z-50">
      <!-- Actions -->
      <div class="flex justify-between items-center mb-4 pb-2 border-b border-base-200">
        <button
          @click="showAllColumns"
          class="btn btn-ghost btn-xs gap-1"
          :disabled="visibleCount === totalColumns"
        >
          <EyeIcon class="w-3 h-3" />
          Show All
        </button>
        <button
          @click="resetToDefault"
          class="btn btn-ghost btn-xs gap-1"
          :disabled="!hasStoredPreferences"
        >
          <RotateCcwIcon class="w-3 h-3" />
          Reset
        </button>
      </div>

      <!-- Column List -->
      <div class="space-y-2">
        <div
          v-for="column in columns"
          :key="column.key"
          class="flex items-center justify-between hover:bg-base-200 rounded-lg p-2 transition-colors"
        >
          <span class="text-sm">{{ column.label }}</span>
          <button
            @click.stop="toggleColumnVisibility(column.key)"
            :class="[
              'btn btn-ghost btn-xs',
              !hiddenColumns.has(column.key) ? 'text-primary' : 'text-base-content/50'
            ]"
          >
            <component
              :is="!hiddenColumns.has(column.key) ? EyeIcon : EyeOffIcon"
              class="w-4 h-4"
            />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import {
  Eye as EyeIcon,
  EyeOff as EyeOffIcon,
  ChevronDown as ChevronDownIcon,
  RotateCcw as RotateCcwIcon
} from 'lucide-vue-next';

interface Column {
  key: string;
  label: string;
  type?: string;
}

const props = defineProps<{
  columns: Column[];
  storageKey?: string;
}>();

const emit = defineEmits(['update:hiddenColumns']);

// Initialize state
const isOpen = ref(false);
const hiddenColumns = ref(new Set<string>());
const hasStoredPreferences = ref(false);

// Computed properties
const totalColumns = computed(() => props.columns.length);
const visibleCount = computed(() => totalColumns.value - hiddenColumns.value.size);
const storageKey = computed(() => props.storageKey || 'table-hidden-columns');

// Methods for managing visibility
const toggleManager = () => {
  isOpen.value = !isOpen.value;
};

const closeManager = () => {
  isOpen.value = false;
};

const toggleColumnVisibility = (columnKey: string) => {
  const newHiddenColumns = new Set(hiddenColumns.value);
  if (newHiddenColumns.has(columnKey)) {
    newHiddenColumns.delete(columnKey);
  } else {
    newHiddenColumns.add(columnKey);
  }
  updateHiddenColumns(newHiddenColumns);
};

const showAllColumns = () => {
  updateHiddenColumns(new Set());
};

const resetToDefault = () => {
  localStorage.removeItem(storageKey.value);
  hasStoredPreferences.value = false;
  updateHiddenColumns(new Set());
};

// Helper functions
const updateHiddenColumns = (newHiddenColumns: Set<string>) => {
  hiddenColumns.value = newHiddenColumns;
  emit('update:hiddenColumns', newHiddenColumns);
  saveToLocalStorage(newHiddenColumns);
};

const saveToLocalStorage = (columns: Set<string>) => {
  try {
    const columnsArray = Array.from(columns);
    localStorage.setItem(storageKey.value, JSON.stringify(columnsArray));
    hasStoredPreferences.value = columnsArray.length > 0;
  } catch (error) {
    console.error('Error saving column preferences:', error);
  }
};

const loadFromLocalStorage = () => {
  try {
    const stored = localStorage.getItem(storageKey.value);
    if (stored) {
      const columnsArray = JSON.parse(stored);
      const storedColumns = new Set(columnsArray);
      hasStoredPreferences.value = columnsArray.length > 0;
      updateHiddenColumns(storedColumns);
    }
  } catch (error) {
    console.error('Error loading column preferences:', error);
  }
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

// Load stored preferences on mount
onMounted(() => {
  loadFromLocalStorage();
});

// Watch for column changes to update storage
watch(() => props.columns, () => {
  // Optionally handle column changes
  // For example, remove hidden columns that no longer exist
  const currentColumns = new Set(props.columns.map(col => col.key));
  const newHiddenColumns = new Set(
    Array.from(hiddenColumns.value).filter(key => currentColumns.has(key))
  );
  if (newHiddenColumns.size !== hiddenColumns.value.size) {
    updateHiddenColumns(newHiddenColumns);
  }
}, { deep: true });
</script>
