<template>
  <div class="w-full">
    <!-- Compact Header with Toggle -->
    <div class="flex items-center justify-between gap-2 mb-3 p-2 bg-base-100 rounded-lg border border-base-200">
      <button
        @click="isFilterOpen = !isFilterOpen"
        class="btn btn-xs btn-ghost gap-1.5 flex-1 justify-start text-xs font-semibold"
      >
        <Filter class="w-3.5 h-3.5" />
        {{ isFilterOpen ? 'Hide' : 'Filters' }}
        <span v-if="Object.keys(activeFilters).length > 0" class="badge badge-xs badge-primary ml-auto">
          {{ Object.keys(activeFilters).length }}
        </span>
      </button>

      <div v-if="hasActiveFilters" class="divider divider-horizontal m-0 h-4"></div>

      <button
        v-if="hasActiveFilters"
        @click="resetAllFilters"
        class="btn btn-xs btn-ghost gap-1 text-error hover:text-error hover:bg-error/10"
        title="Clear all filters"
      >
        <RotateCcw class="w-3.5 h-3.5" />
        Reset
      </button>
    </div>

    <!-- Compact Filter Grid -->
    <Transition
      enter-active-class="transition-all duration-200 ease-out"
      enter-from-class="opacity-0 max-h-0"
      enter-to-class="opacity-100 max-h-96"
      leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100 max-h-96"
      leave-to-class="opacity-0 max-h-0"
    >
      <div v-if="isFilterOpen" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-1.5 mb-3 p-2 bg-base-50 rounded-lg border border-base-200">
        <div
          v-for="column in filterableColumns"
          :key="column.key"
          class="form-control text-xs"
        >
          <!-- Compact Label with Badge -->
          <div class="flex items-center justify-between mb-1">
            <label class="label p-0 h-auto gap-1">
              <span class="label-text text-[11px] font-semibold truncate">{{ column.label }}</span>
              <span v-if="hasActiveFilter(column.key)" class="badge badge-xs badge-primary">✓</span>
            </label>
          </div>

          <!-- Text & Number Input -->
          <template v-if="['text', 'number'].includes(column.type)">
            <div class="space-y-1">
              <!-- Input with optional toggle for search modes -->
              <div class="relative flex items-stretch gap-1">
                <input
                  v-model="filters[column.key]"
                  :type="column.type === 'number' ? 'number' : 'text'"
                  :placeholder="column.type === 'number' ? '0' : 'Search...'"
                  @input="handleFilterChange(column.key, filters[column.key], true)"
                  :class="['input input-bordered input-xs flex-1 pl-2 text-xs placeholder:text-[11px]', column.type === 'text' ? 'pr-2' : 'pr-7']"
                />
                <button
                  v-if="filters[column.key]"
                  @click="filters[column.key] = ''; handleFilterChange(column.key, '', false)"
                  class="btn btn-ghost btn-xs p-0 h-6 w-6 text-xs hover:text-error"
                >
                  ✕
                </button>
                <!-- Toggle button for search modes (text only) -->
                <button
                  v-if="column.type === 'text'"
                  @click="showAdvancedOptions[column.key] = !showAdvancedOptions[column.key]"
                  :class="['btn btn-ghost btn-xs p-0 h-6 w-6', showAdvancedOptions[column.key] ? 'text-primary' : '']"
                  title="Toggle search modes"
                >
                  ⚙
                </button>
              </div>
              <!-- Search Mode Buttons (Text Only, Hidden by Default) -->
              <div v-if="column.type === 'text' && showAdvancedOptions[column.key]" class="flex gap-0.5">
                <button
                  v-for="mode in searchModes"
                  :key="mode.value"
                  @click="setSearchMode(column.key, mode.value)"
                  :class="[
                    'btn btn-xs flex-1',
                    searchModesState[column.key] === mode.value ? 'btn-primary' : 'btn-ghost'
                  ]"
                  :title="mode.label"
                >
                  {{ mode.label.substring(0, 3) }}
                </button>
              </div>
            </div>
          </template>

          <!-- Boolean Select -->
          <template v-else-if="column.type === 'boolean'">
            <select
              v-model="filters[column.key]"
              @change="handleFilterChange(column.key, filters[column.key], false)"
              class="select select-bordered select-xs w-full text-xs"
            >
              <option value="">All</option>
              <option value="true">Yes</option>
              <option value="false">No</option>
            </select>
          </template>

          <!-- Select Dropdown -->
          <template v-else-if="column.type === 'select'">
            <select
              v-model="filters[column.key]"
              @change="handleFilterChange(column.key, filters[column.key], false)"
              class="select select-bordered select-xs w-full text-xs"
            >
              <option value="">All</option>
              <option
                v-for="opt in column.options?.select_options || []"
                :key="opt.value"
                :value="opt.value"
              >
                {{ opt.label }}
              </option>
            </select>
          </template>

          <!-- Date Input -->
          <template v-else-if="column.type === 'date' || column.type === 'timestamp'">
            <div class="space-y-1">
              <!-- Date Range Inputs -->
              <div class="flex gap-1">
                <input
                  v-model="filters[column.key].from"
                  :type="column.type === 'date' ? 'date' : 'datetime-local'"
                  @input="handleFilterChange(column.key, filters[column.key], false)"
                  class="input input-bordered input-xs flex-1 text-xs"
                  placeholder="From"
                />
                <input
                  v-model="filters[column.key].to"
                  :type="column.type === 'date' ? 'date' : 'datetime-local'"
                  @input="handleFilterChange(column.key, filters[column.key], false)"
                  class="input input-bordered input-xs flex-1 text-xs"
                  placeholder="To"
                />
              </div>
              <!-- Toggle button for date presets -->
              <button
                @click="showAdvancedOptions[column.key] = !showAdvancedOptions[column.key]"
                :class="['btn btn-ghost btn-xs w-full', showAdvancedOptions[column.key] ? 'text-primary' : '']"
                title="Toggle date presets"
              >
                ⚙ Presets
              </button>
              <!-- Date Presets (Hidden by Default) -->
              <div v-if="showAdvancedOptions[column.key]" class="flex gap-0.5 flex-wrap">
                <button
                  v-for="preset in datePresets"
                  :key="preset.label"
                  @click="applyDatePreset(column.key, preset)"
                  class="btn btn-xs btn-ghost text-[10px] px-1 py-0.5 h-auto"
                  :title="preset.label"
                >
                  {{ preset.label.split(' ')[0] }}
                </button>
              </div>
            </div>
          </template>

          <!-- Model Search (Compact) -->
          <template v-else-if="column.type === 'model_search'">
            <div class="relative">
              <input
                v-model="modelSearchQueries[column.key]"
                type="text"
                placeholder="Search..."
                @input="handleModelSearch(column)"
                @focus="showModelSearchDropdown[column.key] = true"
                class="input input-bordered input-xs w-full pr-7 text-xs placeholder:text-[11px]"
              />
              <div v-if="isLoadingOptions[column.key]" class="absolute right-2 top-1/2 -translate-y-1/2">
                <span class="loading loading-spinner loading-xs"></span>
              </div>

              <!-- Compact Dropdown -->
              <div
                v-if="showModelSearchDropdown[column.key] && (modelSearchResults[column.key]?.length > 0 || modelSearchQueries[column.key])"
                class="absolute top-full left-0 right-0 z-50 mt-0.5 bg-base-100 border border-base-300 rounded shadow-lg max-h-40 overflow-y-auto"
                @click.stop
              >
                <div v-if="isLoadingOptions[column.key]" class="p-1 text-center text-xs">Loading...</div>
                <div v-else-if="modelSearchQueries[column.key] && modelSearchResults[column.key]?.length === 0" class="p-1 text-center text-xs text-base-content/60">
                  No results
                </div>
                <button
                  v-for="option in modelSearchResults[column.key]"
                  :key="option.id"
                  @click="selectModelSearchOption(column.key, option)"
                  class="w-full text-left px-2 py-1 hover:bg-base-200 transition-colors text-xs border-b border-base-200 last:border-0"
                >
                  {{ option[column.displayKey] || option.name }}
                </button>
              </div>

              <!-- Selected Item Badge -->
              <div v-if="getSelectedModelSearchItem(column.key)" class="mt-1">
                <div class="badge badge-sm bg-primary/10 text-primary border-0 text-xs gap-1.5 w-full justify-between px-1.5 py-0.5">
                  <span class="truncate text-[10px]">{{ getSelectedModelSearchItem(column.key)[column.displayKey] }}</span>
                  <button
                    @click="clearModelSearchSelection(column.key)"
                    class="btn btn-ghost btn-xs p-0 h-4 w-4 text-xs hover:text-error"
                  >
                    ✕
                  </button>
                </div>
              </div>
            </div>
          </template>
        </div>
      </div>
    </Transition>

    <!-- Active Filters Summary (Compact) -->
    <div v-if="hasActiveFilters && isFilterOpen" class="flex flex-wrap gap-1.5 p-2 bg-primary/5 rounded-lg border border-primary/20">
      <div
        v-for="(value, key) in activeFilters"
        :key="key"
        class="badge badge-sm bg-primary text-primary-content gap-1 text-[11px] px-1.5 py-0.5"
      >
        <span class="truncate">{{ getColumnLabel(key) }}: {{ formatFilterValue(key, value) }}</span>
        <button
          @click="clearFilter(key)"
          class="btn btn-ghost btn-xs p-0 h-3 w-3 hover:text-error"
        >
          ✕
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import { Filter, RotateCcw } from 'lucide-vue-next';

const props = defineProps({
  columns: {
    type: Array,
    required: true
  }
});

const emit = defineEmits(['onFilterChange']);

// Search modes and date presets constants
const searchModes = ref([
  { label: 'Contains', value: 'contains' },
  { label: 'Exact', value: 'exact' },
  { label: 'Starts', value: 'starts' }
]);

const datePresets = ref([
  { label: 'Today', days: 0 },
  { label: '7 days', days: 7 },
  { label: '30 days', days: 30 },
  { label: '90 days', days: 90 },
  { label: 'This Year', type: 'year' },
  { label: 'Last Year', type: 'last_year' },
]);

// State
const isFilterOpen = ref(true);
const filters = ref(
  props.columns.reduce((acc, column) => {
    if (['date', 'timestamp'].includes(column.type)) {
      acc[column.key] = { from: '', to: '' };
    } else {
      acc[column.key] = '';
    }
    return acc;
  }, {})
);

// Search modes state for text filters
const searchModesState = ref({});

// Advanced options visibility toggle (hidden by default)
const showAdvancedOptions = ref({});

// Model search state
const modelSearchQueries = ref({});
const modelSearchResults = ref({});
const showModelSearchDropdown = ref({});
const selectedModelSearchItems = ref({});
const isLoadingOptions = ref({});
const searchTimeouts = ref({});
const filterChangeTimeouts = ref({});

// Computed
const filterableColumns = computed(() => {
  return props.columns.filter(column => column.filterable !== false);
});

const activeFilters = computed(() => {
  const result = {};
  Object.entries(filters.value).forEach(([key, value]) => {
    if (value === null || value === undefined) return;

    const column = props.columns.find(col => col.key === key);
    if (!column) return;

    // For date/timestamp: check if from or to is set
    if (['date', 'timestamp'].includes(column.type)) {
      if (value.from || value.to) {
        result[key] = value;
      }
    }
    // For model_search: include if selected
    else if (column.type === 'model_search') {
      if (value) {
        result[key] = value;
      }
    }
    // For other types: include if not empty string
    else if (value !== '') {
      result[key] = {
        value: value,
        searchMode: column.type === 'select' ? 'exact' : (searchModesState.value[key] || 'contains')
      };
    }
  });
  return result;
});

const hasActiveFilters = computed(() => {
  return Object.keys(activeFilters.value).length > 0;
});

// Methods
const hasActiveFilter = (key) => {
  return activeFilters.value.hasOwnProperty(key);
};

const getColumnLabel = (key) => {
  const column = props.columns.find(col => col.key === key);
  return column ? column.label : key;
};

const formatFilterValue = (key, value) => {
  const column = props.columns.find(col => col.key === key);
  if (!column) return value;

  // Handle date/timestamp objects
  if (typeof value === 'object' && value.from !== undefined) {
    if (value.from && value.to) {
      return `${value.from} to ${value.to}`;
    } else if (value.from) {
      return `From ${value.from}`;
    } else if (value.to) {
      return `Until ${value.to}`;
    }
    return 'Date range';
  }

  // Handle value/searchMode objects
  let displayValue = value;
  let searchMode = null;
  if (typeof value === 'object' && value.value !== undefined) {
    displayValue = value.value;
    searchMode = value.searchMode;
  }

  switch (column.type) {
    case 'boolean':
      return displayValue === 'true' ? 'Yes' : 'No';
    case 'select':
      const option = column.options?.select_options?.find(opt => opt.value === displayValue);
      return option ? option.label : displayValue;
    case 'model_search':
      const selectedItem = selectedModelSearchItems.value[key];
      return selectedItem ? selectedItem[column.displayKey] : displayValue;
    default:
      const valueStr = String(displayValue);
      let result = valueStr.length > 15 ? valueStr.substring(0, 15) + '...' : valueStr;
      if (searchMode && searchMode !== 'contains') {
        const modeLabel = searchModes.value.find(mode => mode.value === searchMode)?.label;
        result += ` (${modeLabel})`;
      }
      return result;
  }
};

const handleFilterChange = (key, value, isTextChange) => {
  if (filterChangeTimeouts.value[key]) {
    clearTimeout(filterChangeTimeouts.value[key]);
  }

  if (isTextChange) {
    filterChangeTimeouts.value[key] = setTimeout(() => {
      emitFilterChange();
    }, 300);
  } else {
    emitFilterChange();
  }
};

const emitFilterChange = () => {
  emit('onFilterChange', activeFilters.value);
};

const clearFilter = (key) => {
  const column = props.columns.find(col => col.key === key);

  if (['date', 'timestamp'].includes(column?.type)) {
    filters.value[key] = { from: '', to: '' };
  } else {
    filters.value[key] = '';
  }

  if (column?.type === 'model_search') {
    modelSearchQueries.value[key] = '';
    modelSearchResults.value[key] = [];
    selectedModelSearchItems.value[key] = null;
    showModelSearchDropdown.value[key] = false;
  }

  emitFilterChange();
};

const resetAllFilters = () => {
  filters.value = props.columns.reduce((acc, column) => {
    if (['date', 'timestamp'].includes(column.type)) {
      acc[column.key] = { from: '', to: '' };
    } else {
      acc[column.key] = '';
    }
    return acc;
  }, {});

  searchModesState.value = {};
  modelSearchQueries.value = {};
  modelSearchResults.value = {};
  selectedModelSearchItems.value = {};
  showModelSearchDropdown.value = {};

  Object.values(searchTimeouts.value).forEach(timeout => clearTimeout(timeout));
  Object.values(filterChangeTimeouts.value).forEach(timeout => clearTimeout(timeout));

  searchTimeouts.value = {};
  filterChangeTimeouts.value = {};

  emitFilterChange();
};

// Model Search
const handleModelSearch = async (column) => {
  const query = modelSearchQueries.value[column.key];

  if (searchTimeouts.value[column.key]) {
    clearTimeout(searchTimeouts.value[column.key]);
  }

  if (!query || query.length < 2) {
    modelSearchResults.value[column.key] = [];
    showModelSearchDropdown.value[column.key] = false;
    return;
  }

  searchTimeouts.value[column.key] = setTimeout(async () => {
    await searchModelOptions(column, query);
  }, 300);
};

const searchModelOptions = async (column, query) => {
  if (!column.endpoint) return;

  try {
    isLoadingOptions.value[column.key] = true;
    showModelSearchDropdown.value[column.key] = true;

    const response = await axios.post(column.endpoint, {
      model: column.model,
      columns: column.columns,
      search: query,
      searchColumns: column.columns.map(col => col.key),
      limit: 20
    });

    modelSearchResults.value[column.key] = response.data.data || [];
  } catch (error) {
    console.error('Error searching model options:', error);
    modelSearchResults.value[column.key] = [];
  } finally {
    isLoadingOptions.value[column.key] = false;
  }
};

const selectModelSearchOption = (columnKey, option) => {
  filters.value[columnKey] = option.id;
  selectedModelSearchItems.value[columnKey] = option;
  modelSearchQueries.value[columnKey] = option[props.columns.find(c => c.key === columnKey)?.displayKey] || option.name;
  showModelSearchDropdown.value[columnKey] = false;
  handleFilterChange(columnKey, filters.value[columnKey], false);
};

const clearModelSearchSelection = (columnKey) => {
  filters.value[columnKey] = '';
  selectedModelSearchItems.value[columnKey] = null;
  modelSearchQueries.value[columnKey] = '';
  showModelSearchDropdown.value[columnKey] = false;
  emitFilterChange();
};

const getSelectedModelSearchItem = (columnKey) => {
  return selectedModelSearchItems.value[columnKey];
};

// Search mode management for text filters
const setSearchMode = (key, mode) => {
  searchModesState.value[key] = mode;
  handleFilterChange(key, filters.value[key], false);
};

// Date preset application
const applyDatePreset = (columnKey, preset) => {
  const column = props.columns.find(col => col.key === columnKey);
  const isTimestamp = column?.type === 'timestamp';

  const today = new Date();
  let fromDate = new Date(today);
  let toDate = new Date(today);

  if (preset.type === 'year') {
    fromDate = new Date(today.getFullYear(), 0, 1);
    toDate = new Date(today.getFullYear(), 11, 31, 23, 59, 59);
  } else if (preset.type === 'last_year') {
    const lastYear = today.getFullYear() - 1;
    fromDate = new Date(lastYear, 0, 1);
    toDate = new Date(lastYear, 11, 31, 23, 59, 59);
  } else if (preset.days !== undefined) {
    fromDate.setDate(today.getDate() - preset.days);
    if (preset.days === 0) { // Today
        fromDate.setHours(0, 0, 0, 0);
        toDate.setHours(23, 59, 59, 999);
    }
  }

  const formatForInput = (date) => {
    const pad = (num) => num.toString().padStart(2, '0');
    const year = date.getFullYear();
    const month = pad(date.getMonth() + 1);
    const day = pad(date.getDate());
    if (isTimestamp) {
      const hours = pad(date.getHours());
      const minutes = pad(date.getMinutes());
      return `${year}-${month}-${day}T${hours}:${minutes}`;
    }
    return `${year}-${month}-${day}`;
  };

  // Re-assigning the object to ensure reactivity on nested properties
  filters.value[columnKey] = {
    from: formatForInput(fromDate),
    to: formatForInput(toDate)
  };

  // Emit change immediately to update parent
  emitFilterChange();
};
</script>

<style scoped>
/* Smooth animations */
.transition-all {
  transition: all 0.2s ease-in-out;
}

/* Compact scrollbar */
::-webkit-scrollbar {
  width: 4px;
}

::-webkit-scrollbar-track {
  background: hsl(var(--b2));
}

::-webkit-scrollbar-thumb {
  background: hsl(var(--p) / 0.4);
  border-radius: 2px;
}

::-webkit-scrollbar-thumb:hover {
  background: hsl(var(--p) / 0.6);
}

/* Input focus states */
.input:focus,
.select:focus {
  border-color: hsl(var(--p));
}

/* Compact button fixes */
.btn-xs {
  min-height: 1.5rem;
  padding: 0.25rem 0.5rem;
}

/* Badge compact */
.badge-xs {
  padding: 0.125rem 0.375rem;
  font-size: 0.7rem;
}
</style>
