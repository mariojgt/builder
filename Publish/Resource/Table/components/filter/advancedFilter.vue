<template>
  <div class="card bg-gradient-to-br from-base-100 via-base-50 to-base-100 shadow-xl border border-base-200 hover:shadow-2xl transition-all duration-500">
    <!-- Mobile Toggle Header -->
    <div class="md:hidden">
      <button
        @click="isFilterOpen = !isFilterOpen"
        class="btn btn-ghost w-full justify-between group p-6 hover:bg-primary/5 transition-all duration-300"
      >
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary/20 to-secondary/20 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
            <SlidersHorizontal class="w-5 h-5 text-primary" />
          </div>
          <div class="text-left">
            <span class="font-semibold text-lg">{{ isFilterOpen ? 'Hide Filters' : 'Advanced Filters' }}</span>
            <div class="text-sm text-base-content/60">
              {{ Object.keys(activeFilters).length }} active filter{{ Object.keys(activeFilters).length !== 1 ? 's' : '' }}
            </div>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <div v-if="Object.keys(activeFilters).length > 0" class="badge badge-primary badge-sm">
            {{ Object.keys(activeFilters).length }}
          </div>
          <ChevronDown
            class="w-5 h-5 transition-transform duration-300 group-hover:text-primary"
            :class="{ 'rotate-180': isFilterOpen }"
          />
        </div>
      </button>
    </div>

    <!-- Filter Content -->
    <div
      class="transition-all duration-500 ease-out overflow-hidden"
      :class="[isFilterOpen ? 'max-h-[2000px] opacity-100' : 'max-h-0 opacity-0 md:max-h-none md:opacity-100']"
    >
      <!-- Desktop Header -->
      <div class="hidden md:block bg-gradient-to-r from-base-200/50 via-base-100 to-base-200/50 px-6 py-4 border-b border-base-200">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary/20 to-secondary/20 flex items-center justify-center">
              <Filter class="w-5 h-5 text-primary" />
            </div>
            <div>
              <h3 class="text-lg font-bold text-base-content">Advanced Filters</h3>
              <p class="text-sm text-base-content/60">Refine your search with precision controls</p>
            </div>
          </div>

          <!-- Filter Statistics -->
          <div class="flex items-center gap-4">
            <div class="stats stats-horizontal shadow bg-base-200/50">
              <div class="stat py-2 px-4">
                <div class="stat-title text-xs">Active</div>
                <div class="stat-value text-lg text-primary">{{ Object.keys(activeFilters).length }}</div>
              </div>
              <div class="stat py-2 px-4">
                <div class="stat-title text-xs">Available</div>
                <div class="stat-value text-lg text-secondary">{{ filterableColumns.length }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Filter Grid -->
      <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
          <TransitionGroup
            enter-active-class="transition-all duration-500 ease-out"
            enter-from-class="opacity-0 translate-y-4 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition-all duration-300 ease-in"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-4 scale-95"
          >
            <div
              v-for="(column, index) in filterableColumns"
              :key="column.key"
              class="form-control group/filter"
              :style="{ animationDelay: `${index * 100}ms` }"
            >
              <!-- Enhanced Filter Card -->
              <div class="card bg-base-100 shadow-md border border-base-200 hover:shadow-lg hover:border-primary/30 transition-all duration-300 group-hover/filter:scale-[1.02]">
                <div class="card-body p-4">
                  <!-- Filter Header -->
                  <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-primary/20 to-secondary/20 flex items-center justify-center">
                      <component :is="getFilterIcon(column.type)" class="w-4 h-4 text-primary" />
                    </div>
                    <div class="flex-1">
                      <label class="label p-0">
                        <span class="label-text font-semibold text-base-content">{{ column.label }}</span>
                        <span v-if="hasActiveFilter(column.key)" class="label-text-alt">
                          <div class="badge badge-primary badge-xs animate-pulse">active</div>
                        </span>
                      </label>
                      <div class="text-xs text-base-content/50">{{ getFilterTypeDescription(column.type) }}</div>
                    </div>
                  </div>

                  <!-- Filter Input Based on Type -->

                  <!-- Model Search Filter -->
                  <div v-if="column.type === 'model_search'" class="space-y-2">
                    <select
                      v-model="filters[column.key]"
                      class="select select-bordered w-full focus:select-primary transition-all duration-200"
                      @change="handleFilterChange"
                    >
                      <option value="">All {{ column.label }}</option>
                      <option
                        v-for="option in modelOptions[column.key] || []"
                        :key="option.id"
                        :value="option.id"
                      >
                        {{ option[column.displayKey] }}
                      </option>
                    </select>
                    <div v-if="isLoadingOptions[column.key]" class="flex items-center gap-2 text-xs text-base-content/50">
                      <div class="loading loading-spinner loading-xs"></div>
                      <span>Loading options...</span>
                    </div>
                  </div>

                  <!-- Boolean Filter -->
                  <div v-else-if="column.type === 'boolean'" class="space-y-2">
                    <select
                      v-model="filters[column.key]"
                      class="select select-bordered w-full focus:select-primary transition-all duration-200"
                      @change="handleFilterChange"
                    >
                      <option value="">All Values</option>
                      <option value="true">✓ True / Yes / Active</option>
                      <option value="false">✗ False / No / Inactive</option>
                    </select>
                  </div>

                  <!-- Select Filter -->
                  <div v-else-if="column.type === 'select' && column.options" class="space-y-2">
                    <select
                      v-model="filters[column.key]"
                      class="select select-bordered w-full focus:select-primary transition-all duration-200"
                      @change="handleFilterChange"
                    >
                      <option value="">All Options</option>
                      <option
                        v-for="option in column.options.select_options"
                        :key="option.value"
                        :value="option.value"
                      >
                        {{ option.label }}
                      </option>
                    </select>
                  </div>

                  <!-- Enhanced Date Range Filter -->
                  <div v-else-if="column.type === 'date'" class="space-y-3">
                    <div class="grid grid-cols-2 gap-2">
                      <div>
                        <label class="label py-1">
                          <span class="label-text-alt text-xs">From Date</span>
                        </label>
                        <input
                          type="date"
                          v-model="filters[column.key].from"
                          class="input input-bordered input-sm w-full focus:input-primary transition-all duration-200"
                          @change="handleFilterChange"
                        />
                      </div>
                      <div>
                        <label class="label py-1">
                          <span class="label-text-alt text-xs">To Date</span>
                        </label>
                        <input
                          type="date"
                          v-model="filters[column.key].to"
                          class="input input-bordered input-sm w-full focus:input-primary transition-all duration-200"
                          @change="handleFilterChange"
                        />
                      </div>
                    </div>

                    <!-- Quick Date Presets -->
                    <div class="flex gap-1 flex-wrap">
                      <button
                        v-for="preset in datePresets"
                        :key="preset.label"
                        @click="applyDatePreset(column.key, preset)"
                        class="btn btn-xs btn-ghost hover:btn-primary transition-all duration-200"
                      >
                        {{ preset.label }}
                      </button>
                    </div>
                  </div>

                  <!-- Enhanced Text Filter -->
                  <div v-else class="space-y-2">
                    <div class="relative">
                      <input
                        type="text"
                        v-model="filters[column.key]"
                        class="input input-bordered w-full pr-10 focus:input-primary transition-all duration-200"
                        @input="handleFilterChange"
                        :placeholder="`Search ${column.label.toLowerCase()}...`"
                      />
                      <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                        <Search class="w-4 h-4 text-base-content/40" />
                      </div>
                    </div>

                    <!-- Search Mode Toggle -->
                    <div class="flex gap-1">
                      <button
                        v-for="mode in searchModes"
                        :key="mode.value"
                        @click="setSearchMode(column.key, mode.value)"
                        class="btn btn-xs transition-all duration-200"
                        :class="[
                          getSearchMode(column.key) === mode.value
                            ? 'btn-primary'
                            : 'btn-ghost hover:btn-primary'
                        ]"
                      >
                        {{ mode.label }}
                      </button>
                    </div>
                  </div>

                  <!-- Clear Filter Button -->
                  <div v-if="hasActiveFilter(column.key)" class="pt-2">
                    <button
                      @click="clearFilter(column.key)"
                      class="btn btn-xs btn-error btn-outline w-full gap-1 hover:btn-error transition-all duration-200"
                    >
                      <X class="w-3 h-3" />
                      Clear Filter
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </TransitionGroup>
        </div>

        <!-- Active Filters Summary -->
        <Transition
          enter-active-class="transition-all duration-500 ease-out"
          enter-from-class="opacity-0 translate-y-4"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition-all duration-300 ease-in"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 translate-y-4"
        >
          <div v-if="hasActiveFilters" class="mt-8 p-6 bg-gradient-to-r from-primary/5 via-secondary/5 to-primary/5 rounded-xl border border-primary/20">
            <div class="flex items-center justify-between mb-4">
              <h4 class="text-lg font-semibold text-base-content flex items-center gap-2">
                <Filter class="w-5 h-5 text-primary" />
                Active Filters
                <div class="badge badge-primary">{{ Object.keys(activeFilters).length }}</div>
              </h4>
              <button
                @click="resetAllFilters"
                class="btn btn-sm btn-error btn-outline gap-2 hover:btn-error transition-all duration-200"
              >
                <RotateCcw class="w-4 h-4" />
                Clear All
              </button>
            </div>

            <div class="flex flex-wrap gap-3">
              <TransitionGroup
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0 scale-90"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-90"
              >
                <div
                  v-for="(value, key) in activeFilters"
                  :key="key"
                  class="badge badge-lg gap-3 bg-base-100 text-base-content border border-primary/30 hover:scale-105 transition-all duration-200 cursor-default"
                >
                  <div class="flex items-center gap-2">
                    <span class="font-medium">{{ getColumnLabel(key) }}:</span>
                    <span class="font-bold text-primary">{{ formatFilterValue(key, value) }}</span>
                  </div>
                  <button
                    @click="clearFilter(key)"
                    class="hover:text-error transition-colors duration-200 hover:scale-110"
                  >
                    <X class="w-4 h-4" />
                  </button>
                </div>
              </TransitionGroup>
            </div>
          </div>
        </Transition>

        <!-- Filter Actions -->
        <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mt-8 pt-6 border-t border-base-200">
          <div class="flex items-center gap-2 text-sm text-base-content/60">
            <div class="w-2 h-2 rounded-full bg-primary animate-pulse"></div>
            <span>Filters are applied automatically</span>
          </div>

          <div class="flex gap-3">
            <button
              v-if="hasActiveFilters"
              @click="resetAllFilters"
              class="btn btn-ghost gap-2 hover:btn-error transition-all duration-200"
            >
              <RotateCcw class="w-4 h-4" />
              Reset All Filters
            </button>
            <button
              @click="applyFilters"
              class="btn btn-primary gap-2 shadow-lg hover:shadow-xl transition-all duration-200"
              :class="{ 'btn-disabled': !hasActiveFilters }"
            >
              <Filter class="w-4 h-4" />
              Apply Filters
              <div v-if="hasActiveFilters" class="badge badge-primary-content">
                {{ Object.keys(activeFilters).length }}
              </div>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import {
  SlidersHorizontal,
  ChevronDown,
  Filter,
  RotateCcw,
  X,
  Search,
  Calendar,
  Hash,
  Type,
  ToggleRight,
  Tag,
  Clock,
  DollarSign,
  Star,
  List
} from 'lucide-vue-next';

const props = defineProps({
  columns: {
    type: Array,
    required: true
  }
});

const emit = defineEmits(['onFilterChange']);

// State management
const isFilterOpen = ref(false);
const modelOptions = ref({});
const isLoadingOptions = ref({});
const searchModes = ref([
  { label: 'Contains', value: 'contains' },
  { label: 'Exact', value: 'exact' },
  { label: 'Starts', value: 'starts' }
]);

const datePresets = ref([
  { label: 'Today', days: 0 },
  { label: '7 days', days: 7 },
  { label: '30 days', days: 30 },
  { label: '90 days', days: 90 }
]);

// Initialize filters
const filters = ref(
  props.columns.reduce((acc, column) => {
    acc[column.key] = column.type === 'date' ? { from: '', to: '' } : '';
    return acc;
  }, {})
);

const searchModesState = ref({});

// Computed properties
const filterableColumns = computed(() => {
  return props.columns.filter(column => column.filterable !== false);
});

const activeFilters = computed(() => {
  return Object.entries(filters.value).reduce((acc, [key, value]) => {
    if (value !== null && value !== undefined) {
      if (typeof value === 'string') {
        if (value.trim()) acc[key] = value;
      } else if (typeof value === 'number') {
        acc[key] = value;
      } else if (typeof value === 'object') {
        if (value.from || value.to) acc[key] = value;
      }
    }
    return acc;
  }, {});
});

const hasActiveFilters = computed(() => {
  return Object.keys(activeFilters.value).length > 0;
});

// Helper functions
const hasActiveFilter = (key) => {
  return activeFilters.value.hasOwnProperty(key);
};

const getFilterIcon = (type) => {
  const iconMap = {
    date: Calendar,
    timestamp: Clock,
    number: Hash,
    text: Type,
    boolean: ToggleRight,
    select: List,
    model_search: Tag,
    price: DollarSign,
    rating: Star,
    default: Filter
  };
  return iconMap[type] || iconMap.default;
};

const getFilterTypeDescription = (type) => {
  const descriptions = {
    date: 'Select date range',
    timestamp: 'Filter by date and time',
    number: 'Numeric comparison',
    text: 'Text search with modes',
    boolean: 'True/False selection',
    select: 'Choose from options',
    model_search: 'Related data filter',
    price: 'Currency amount filter',
    rating: 'Star rating filter',
    default: 'General filter'
  };
  return descriptions[type] || descriptions.default;
};

const getSearchMode = (key) => {
  return searchModesState.value[key] || 'contains';
};

const setSearchMode = (key, mode) => {
  searchModesState.value[key] = mode;
  handleFilterChange();
};

const loadModelOptions = async (column) => {
  if (!column.endpoint) return;

  try {
    isLoadingOptions.value[column.key] = true;
    const response = await axios.post(column.endpoint, {
      model: column.model,
      columns: column.columns,
    });
    modelOptions.value[column.key] = response.data.data;
  } catch (error) {
    console.error('Error loading model options:', error);
  } finally {
    isLoadingOptions.value[column.key] = false;
  }
};

const getColumnLabel = (key) => {
  const column = props.columns.find(col => col.key === key);
  return column ? column.label : key;
};

const formatFilterValue = (key, value) => {
  const column = props.columns.find(col => col.key === key);
  if (!column) return value;

  switch (column.type) {
    case 'boolean':
      return value === 'true' ? 'Yes' : 'No';
    case 'date':
      if (value.from && value.to) {
        return `${value.from} to ${value.to}`;
      } else if (value.from) {
        return `From ${value.from}`;
      } else if (value.to) {
        return `Until ${value.to}`;
      }
      return 'Date range';
    case 'select':
      const option = column.options?.select_options?.find(opt => opt.value === value);
      return option ? option.label : value;
    default:
      return String(value).length > 20 ? String(value).substring(0, 20) + '...' : String(value);
  }
};

const applyDatePreset = (columnKey, preset) => {
  const today = new Date();
  const fromDate = new Date();
  fromDate.setDate(today.getDate() - preset.days);

  filters.value[columnKey] = {
    from: fromDate.toISOString().split('T')[0],
    to: today.toISOString().split('T')[0]
  };
  handleFilterChange();
};

const handleFilterChange = () => {
  emit('onFilterChange', activeFilters.value);
};

const clearFilter = (key) => {
  const column = props.columns.find(col => col.key === key);
  filters.value[key] = column?.type === 'date' ? { from: '', to: '' } : '';
  handleFilterChange();
};

const resetAllFilters = () => {
  filters.value = props.columns.reduce((acc, column) => {
    acc[column.key] = column.type === 'date' ? { from: '', to: '' } : '';
    return acc;
  }, {});
  searchModesState.value = {};
  handleFilterChange();
};

const applyFilters = () => {
  handleFilterChange();
};

// Load model options on mount
onMounted(() => {
  if (props.columns) {
    props.columns.forEach(column => {
      if (column.type === 'model_search' && column.filterable) {
        loadModelOptions(column);
      }
    });
  }
});
</script>

<style scoped>
/* Enhanced Card Animations */
.card {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.card:hover {
  transform: translateY(-2px);
}

/* Enhanced Input Focus Effects */
.input:focus,
.select:focus {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px hsl(var(--p) / 0.15);
}

/* Button Hover Enhancements */
.btn {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn:hover:not(:disabled) {
  transform: translateY(-1px);
}

.btn:active:not(:disabled) {
  transform: translateY(0) scale(0.98);
}

/* Badge Hover Effects */
.badge {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.badge:hover {
  transform: scale(1.05);
}

/* Enhanced Pulse Animation */
@keyframes gentlePulse {
  0%, 100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.7;
    transform: scale(1.05);
  }
}

.animate-pulse {
  animation: gentlePulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Loading Spinner Enhancement */
.loading-spinner {
  filter: drop-shadow(0 0 8px hsl(var(--p) / 0.3));
}

/* Enhanced Stats Component */
.stats {
  transition: all 0.3s ease;
}

.stats:hover {
  transform: scale(1.02);
}

/* Custom Scrollbar */
.overflow-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-auto::-webkit-scrollbar-track {
  background: hsl(var(--b3));
  border-radius: 3px;
}

.overflow-auto::-webkit-scrollbar-thumb {
  background: hsl(var(--p) / 0.3);
  border-radius: 3px;
}

.overflow-auto::-webkit-scrollbar-thumb:hover {
  background: hsl(var(--p) / 0.5);
}

/* Enhanced Focus States */
.btn:focus-visible,
.input:focus-visible,
.select:focus-visible {
  outline: 2px solid hsl(var(--p));
  outline-offset: 2px;
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
  .grid-cols-1 {
    grid-template-columns: 1fr;
  }

  .stats-horizontal {
    display: grid;
    grid-template-columns: 1fr 1fr;
  }
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
  .border-base-200 {
    border-color: hsl(var(--bc));
  }

  .bg-gradient-to-r {
    background: hsl(var(--b2));
  }
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
  .animate-pulse,
  .transition-all,
  .transition-transform {
    animation: none;
    transition: none;
  }
}

/* Print Styles */
@media print {
  .btn,
  .loading {
    display: none !important;
  }
}
</style>
