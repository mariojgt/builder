<template>
  <div class="w-full">
    <div class="mb-6">
      <button
        @click="isFilterOpen = !isFilterOpen"
        class="btn btn-outline btn-primary gap-2 text-sm font-semibold transition-all duration-300 transform hover:-translate-y-0.5 shadow-md hover:shadow-lg"
      >
        <Filter class="w-4 h-4" />
        {{ isFilterOpen ? 'Hide Filters' : 'Show Advanced Filters' }}
        <div v-if="Object.keys(activeFilters).length > 0" class="badge badge-primary badge-sm">
          {{ Object.keys(activeFilters).length }}
        </div>
        <ChevronDown
          class="w-4 h-4 transition-transform duration-300"
          :class="{ 'rotate-180': isFilterOpen }"
        />
      </button>
    </div>

    <div v-show="isFilterOpen" class="card bg-base-100 shadow-lg border border-base-200 transition-all duration-500 ease-out p-0 rounded-box">
      <div class="bg-base-200/40 px-6 py-4 border-b border-base-200 flex items-center justify-between rounded-t-box">
        <div class="flex items-center gap-3">
          <div class="flex-shrink-0 w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center">
            <Filter class="w-4 h-4 text-primary" />
          </div>
          <div>
            <h3 class="text-lg font-bold text-base-content leading-tight">Advanced Filters</h3>
            <p class="text-xs text-base-content/60">Refine your data with powerful search options</p>
          </div>
        </div>
        <div class="flex items-center gap-4 text-sm">
          <div class="flex items-center gap-1.5 text-base-content/70">
            <span class="font-medium">Active:</span>
            <span class="font-bold text-primary">{{ Object.keys(activeFilters).length }}</span>
          </div>
          <div class="flex items-center gap-1.5 text-base-content/70">
            <span class="font-medium">Available:</span>
            <span class="font-bold text-secondary">{{ filterableColumns.length }}</span>
          </div>
        </div>
      </div>

      <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
          <TransitionGroup
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-2 scale-98"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-2 scale-98"
          >
            <div
              v-for="(column, index) in filterableColumns"
              :key="column.key"
              class="form-control bg-base-50 p-4 rounded-lg border border-base-200 transition-all duration-300 hover:shadow-md hover:border-primary/30"
              :style="{ animationDelay: `${index * 50}ms` }"
            >
              <div class="flex items-center gap-3 mb-3">
                <div class="flex-shrink-0 w-6 h-6 rounded-md bg-primary/10 flex items-center justify-center">
                  <component :is="getFilterIcon(column.type)" class="w-3.5 h-3.5 text-primary" />
                </div>
                <div class="flex-1">
                  <label class="label p-0">
                    <span class="label-text font-semibold text-base-content text-sm">{{ column.label }}</span>
                    <span v-if="hasActiveFilter(column.key)" class="label-text-alt">
                      <div class="badge badge-primary badge-xs animate-pulse">active</div>
                    </span>
                  </label>
                  <div class="text-xs text-base-content/50 leading-tight">{{ getFilterTypeDescription(column.type) }}</div>
                </div>
              </div>

              <div v-if="column.type === 'model_search'" class="space-y-3">
                <div class="relative">
                  <input
                    type="text"
                    v-model="modelSearchQueries[column.key]"
                    class="input input-bordered input-sm w-full pr-10 focus:input-primary transition-all duration-200"
                    :placeholder="`Search ${column.label.toLowerCase()}...`"
                    @input="handleModelSearch(column)"
                    @focus="showModelSearchDropdown[column.key] = true"
                  />
                  <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                    <div v-if="isLoadingOptions[column.key]" class="loading loading-spinner loading-xs"></div>
                    <Search v-else class="w-3.5 h-3.5 text-base-content/40" />
                  </div>
                </div>

                <div
                  v-if="showModelSearchDropdown[column.key] && (modelSearchResults[column.key]?.length > 0 || modelSearchQueries[column.key])"
                  class="absolute top-full left-0 right-0 z-50 mt-1 bg-base-100 border border-base-300 rounded-lg shadow-xl overflow-hidden max-h-60"
                  @click.stop
                >
                  <div v-if="isLoadingOptions[column.key]" class="p-3 text-center">
                    <div class="flex items-center justify-center gap-2">
                      <div class="loading loading-spinner loading-sm"></div>
                      <span class="text-sm text-base-content/60">Searching...</span>
                    </div>
                  </div>
                  <div v-else-if="modelSearchQueries[column.key] && modelSearchResults[column.key]?.length === 0" class="p-3 text-center text-sm text-base-content/60">
                    No results for "{{ modelSearchQueries[column.key] }}"
                  </div>
                  <div v-else class="max-h-48 overflow-y-auto custom-scrollbar">
                    <button
                      v-for="option in modelSearchResults[column.key] || []"
                      :key="option.id"
                      @click="selectModelSearchOption(column.key, option)"
                      class="w-full text-left px-3 py-2 hover:bg-base-200 transition-colors duration-150 border-b border-base-200 last:border-b-0 text-sm"
                    >
                      <div class="flex items-center justify-between">
                        <div>
                          <div class="font-medium">{{ option[column.displayKey] }}</div>
                          <div class="text-xs text-base-content/60">{{ formatModelSearchPreview(option, column) }}</div>
                        </div>
                        <Check v-if="filters[column.key] == option.id" class="w-4 h-4 text-primary" />
                      </div>
                    </button>
                  </div>
                  <div v-if="filters[column.key]" class="border-t border-base-300">
                    <button
                      @click="clearModelSearchSelection(column.key)"
                      class="w-full text-left px-3 py-2 hover:bg-error/10 text-error transition-colors duration-150 text-sm flex items-center gap-2"
                    >
                      <X class="w-4 h-4" />
                      Clear selection
                    </button>
                  </div>
                </div>

                <div v-if="getSelectedModelSearchItem(column.key)" class="mt-2">
                  <div class="bg-base-200 border border-base-300 p-2 rounded-lg text-sm flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                      <div class="w-6 h-6 rounded-md bg-primary/10 flex items-center justify-center">
                        <User class="w-3 h-3 text-primary" />
                      </div>
                      <span class="font-medium">{{ getSelectedModelSearchItem(column.key)[column.displayKey] }}</span>
                    </div>
                    <button
                      @click="clearModelSearchSelection(column.key)"
                      class="btn btn-ghost btn-xs text-error hover:bg-error/10"
                    >
                      <X class="w-3 h-3" />
                    </button>
                  </div>
                </div>
              </div>

              <div v-else-if="column.type === 'boolean'">
                <select
                  v-model="filters[column.key]"
                  class="select select-bordered select-sm w-full focus:select-primary transition-all duration-200"
                  @change="handleFilterChange(column.key, filters[column.key], false)"
                >
                  <option value="">All Values</option>
                  <option value="true">✓ True / Yes / Active</option>
                  <option value="false">✗ False / No / Inactive</option>
                </select>
              </div>

              <div v-else-if="column.type === 'select' && column.options">
                <select
                  v-model="filters[column.key]"
                  class="select select-bordered select-sm w-full focus:select-primary transition-all duration-200"
                  @change="handleFilterChange(column.key, filters[column.key], false)"
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

              <div v-else-if="column.type === 'date' || column.type === 'timestamp'" class="space-y-2">
                <div class="grid grid-cols-2 gap-2">
                  <div>
                    <label class="label py-1">
                      <span class="label-text-alt text-xs">From</span>
                    </label>
                    <input
                      type="date"
                      v-model="filters[column.key].from"
                      class="input input-bordered input-sm w-full focus:input-primary transition-all duration-200"
                      @change="handleFilterChange(column.key, filters[column.key], false)"
                    />
                  </div>
                  <div>
                    <label class="label py-1">
                      <span class="label-text-alt text-xs">To</span>
                    </label>
                    <input
                      type="date"
                      v-model="filters[column.key].to"
                      class="input input-bordered input-sm w-full focus:input-primary transition-all duration-200"
                      @change="handleFilterChange(column.key, filters[column.key], false)"
                    />
                  </div>
                </div>

                <div class="flex gap-1 flex-wrap pt-1">
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

              <div v-else class="space-y-2">
                <div class="relative">
                  <input
                    type="text"
                    v-model="filters[column.key]"
                    class="input input-bordered input-sm w-full pr-10 focus:input-primary transition-all duration-200"
                    @input="handleFilterChange(column.key, filters[column.key], true)"
                    :placeholder="`Search ${column.label.toLowerCase()}...`"
                  />
                  <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                    <Search class="w-3.5 h-3.5 text-base-content/40" />
                  </div>
                </div>

                <div class="flex gap-1 flex-wrap pt-1">
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

              <div v-if="hasActiveFilter(column.key)" class="pt-3 border-t border-base-200 mt-4">
                <button
                  @click="clearFilter(column.key)"
                  class="btn btn-xs btn-error btn-outline w-full gap-1 hover:btn-error transition-all duration-200"
                >
                  <X class="w-3 h-3" />
                  Clear Filter
                </button>
              </div>
            </div>
          </TransitionGroup>
        </div>

        <Transition
          enter-active-class="transition-all duration-300 ease-out"
          enter-from-class="opacity-0 translate-y-4"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition-all duration-200 ease-in"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 translate-y-4"
        >
          <div v-if="hasActiveFilters" class="mt-8 p-6 bg-primary/5 rounded-xl border border-primary/20 shadow-inner">
            <div class="flex items-center justify-between mb-4">
              <h4 class="text-lg font-semibold text-base-content flex items-center gap-2">
                <Filter class="w-5 h-5 text-primary" />
                Active Filters
                <div class="badge badge-primary badge-sm">{{ Object.keys(activeFilters).length }}</div>
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
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0 scale-90"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition-all duration-150 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-90"
              >
                <div
                  v-for="(value, key) in activeFilters"
                  :key="key"
                  class="badge badge-lg gap-3 bg-base-200 text-base-content border border-primary/30 text-sm px-4 py-3 rounded-full shadow-sm hover:scale-[1.02] transition-all duration-200 cursor-default"
                >
                  <div class="flex items-center gap-2">
                    <span class="font-medium">{{ getColumnLabel(key) }}:</span>
                    <span class="font-bold text-primary">{{ formatFilterValue(key, value) }}</span>
                  </div>
                  <button
                    @click="clearFilter(key)"
                    class="btn btn-ghost btn-xs text-error hover:bg-error/10 rounded-full"
                  >
                    <X class="w-3 h-3" />
                  </button>
                </div>
              </TransitionGroup>
            </div>
          </div>
        </Transition>

        <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mt-8 pt-6 border-t border-base-200">
          <div class="flex items-center gap-2 text-sm text-base-content/60">
            <div class="w-2 h-2 rounded-full bg-primary animate-pulse-slow"></div>
            <span>Filters apply automatically on change</span>
          </div>

          <div class="flex gap-3">
            <button
              v-if="hasActiveFilters"
              @click="resetAllFilters"
              class="btn btn-ghost gap-2 hover:btn-error transition-all duration-200"
            >
              <RotateCcw class="w-4 h-4" />
              Reset All
            </button>
            <button
              @click="applyFilters"
              class="btn btn-primary gap-2 shadow-lg hover:shadow-xl transition-all duration-200"
              :class="{ 'btn-disabled': !hasActiveFilters }"
            >
              <Filter class="w-4 h-4" />
              Apply Filters
              <div v-if="hasActiveFilters" class="badge badge-primary-content">{{ Object.keys(activeFilters).length }}</div>
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
  List,
  Check,
  User,
  Info
} from 'lucide-vue-next';

const props = defineProps({
  columns: {
    type: Array,
    required: true
  }
});

const emit = defineEmits(['onFilterChange']);

// State management
const isFilterOpen = ref(false); // Filter panel visibility
const isLoadingOptions = ref({}); // Loading state for model search options
const modelSearchQueries = ref({}); // Current input query for model search
const modelSearchResults = ref({}); // Results from model search API
const showModelSearchDropdown = ref({}); // Controls visibility of model search dropdown
const selectedModelSearchItems = ref({}); // Stores the selected object from model search
const searchTimeouts = ref({}); // For debouncing model search input
const filterChangeTimeouts = ref({}); // For debouncing general filter changes

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

// Initialize filters based on columns prop
const filters = ref(
  props.columns.reduce((acc, column) => {
    acc[column.key] = ['date', 'timestamp'].includes(column.type) ? { from: '', to: '' } : '';
    return acc;
  }, {})
);

const searchModesState = ref({}); // Stores search mode per text filter

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
      } else if (typeof value === 'object' && !Array.isArray(value)) { // Ensure it's not an array
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
    model_search: User, // Changed to User for model search
    price: DollarSign,
    rating: Star,
    default: Filter
  };
  return iconMap[type] || iconMap.default;
};

const getFilterTypeDescription = (type) => {
  const descriptions = {
    date: 'Filter by date range',
    timestamp: 'Filter by date & time range',
    number: 'Filter numeric values',
    text: 'Search text (contains, exact, starts with)',
    boolean: 'Filter by Yes/No',
    select: 'Select from predefined options',
    model_search: 'Search and select a related item',
    price: 'Filter by price range',
    rating: 'Filter by star rating',
    default: 'General text filter'
  };
  return descriptions[type] || descriptions.default;
};

const getSearchMode = (key) => {
  return searchModesState.value[key] || 'contains';
};

const setSearchMode = (key, mode) => {
  searchModesState.value[key] = mode;
  handleFilterChange(key, filters.value[key], false); // No debounce needed for mode change
};

// Model Search Functions
const handleModelSearch = async (column) => {
  const query = modelSearchQueries.value[column.key];

  if (searchTimeouts.value[column.key]) {
    clearTimeout(searchTimeouts.value[column.key]);
  }

  if (!query || query.length < 2) {
    modelSearchResults.value[column.key] = [];
    showModelSearchDropdown.value[column.key] = false;
    // Potentially clear selected model search if query is empty
    if(filters.value[column.key] !== '') {
        clearModelSearchSelection(column.key);
    }
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
  modelSearchQueries.value[columnKey] = option[getColumnByKey(columnKey)?.displayKey || 'name'];
  showModelSearchDropdown.value[columnKey] = false;
  handleFilterChange(columnKey, filters.value[columnKey], false); // No debounce for selection
};

const clearModelSearchSelection = (columnKey) => {
  filters.value[columnKey] = '';
  selectedModelSearchItems.value[columnKey] = null;
  modelSearchQueries.value[columnKey] = '';
  showModelSearchDropdown.value[columnKey] = false;
  handleFilterChange(columnKey, filters.value[columnKey], false); // No debounce for clear
};

const getSelectedModelSearchItem = (columnKey) => {
  return selectedModelSearchItems.value[columnKey];
};

const getColumnByKey = (key) => {
  return props.columns.find(col => col.key === key);
};

const formatModelSearchPreview = (option, column) => {
  const previewFields = [];
  column.columns.forEach(col => {
    if (col.key !== column.displayKey && option[col.key]) {
      previewFields.push(`${col.key}: ${option[col.key]}`);
    }
  });
  return previewFields.slice(0, 2).join(' • ') || `ID: ${option.id}`;
};

// General Filter Management
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
    case 'timestamp':
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
    case 'model_search':
      const selectedItem = selectedModelSearchItems.value[key];
      return selectedItem ? selectedItem[column.displayKey] : `ID: ${value}`;
    default:
      return String(value).length > 20 ? String(value).substring(0, 20) + '...' : String(value);
  }
};

const applyDatePreset = (columnKey, preset) => {
  const today = new Date();
  let fromDate = new Date();
  let toDate = new Date(today);

  if (preset.type === 'year') {
    fromDate = new Date(today.getFullYear(), 0, 1);
    toDate = new Date(today.getFullYear(), 11, 31);
  } else if (preset.type === 'last_year') {
    fromDate = new Date(today.getFullYear() - 1, 0, 1);
    toDate = new Date(today.getFullYear() - 1, 11, 31);
  }
  else if (preset.days !== undefined) {
    fromDate.setDate(today.getDate() - preset.days);
  }

  filters.value[columnKey] = {
    from: fromDate.toISOString().split('T')[0],
    to: toDate.toISOString().split('T')[0]
  };
  handleFilterChange(columnKey, filters.value[columnKey], false); // No debounce for date presets
};

const handleFilterChange = (key, value, isTextChange) => {
  if (filterChangeTimeouts.value[key]) {
    clearTimeout(filterChangeTimeouts.value[key]);
  }

  if (isTextChange) {
    filterChangeTimeouts.value[key] = setTimeout(() => {
      emit('onFilterChange', activeFilters.value);
    }, 300); // Debounce for 300ms
  } else {
    // For select, boolean, date range changes, apply immediately
    emit('onFilterChange', activeFilters.value);
  }
};

const clearFilter = (key) => {
  const column = props.columns.find(col => col.key === key);
  filters.value[key] = ['date', 'timestamp'].includes(column?.type) ? { from: '', to: '' } : '';

  if (column?.type === 'model_search') {
    modelSearchQueries.value[key] = '';
    modelSearchResults.value[key] = [];
    selectedModelSearchItems.value[key] = null;
    showModelSearchDropdown.value[key] = false;
    if (searchTimeouts.value[key]) {
      clearTimeout(searchTimeouts.value[key]);
      searchTimeouts.value[key] = null;
    }
  }

  if (filterChangeTimeouts.value[key]) {
    clearTimeout(filterChangeTimeouts.value[key]);
    filterChangeTimeouts.value[key] = null;
  }
  emit('onFilterChange', activeFilters.value); // Emit immediately on clear
};

const resetAllFilters = () => {
  filters.value = props.columns.reduce((acc, column) => {
    acc[column.key] = ['date', 'timestamp'].includes(column.type) ? { from: '', to: '' } : '';
    return acc;
  }, {});
  searchModesState.value = {};

  modelSearchQueries.value = {};
  modelSearchResults.value = {};
  selectedModelSearchItems.value = {};
  showModelSearchDropdown.value = {};

  Object.values(searchTimeouts.value).forEach(timeout => {
    if (timeout) clearTimeout(timeout);
  });
  searchTimeouts.value = {};

  Object.values(filterChangeTimeouts.value).forEach(timeout => {
    if (timeout) clearTimeout(timeout);
  });
  filterChangeTimeouts.value = {};

  emit('onFilterChange', activeFilters.value); // Emit immediately on reset
};

const applyFilters = () => {
  // This function is still present for the explicit "Apply" button.
  // It will just re-emit the current state, potentially forcing any pending debounced changes.
  emit('onFilterChange', activeFilters.value);
};

// Lifecycle Hooks
onMounted(() => {
  props.columns.forEach(column => {
    if (column.type === 'model_search' && column.filterable) {
      modelSearchQueries.value[column.key] = '';
      modelSearchResults.value[column.key] = [];
      showModelSearchDropdown.value[column.key] = false;
      selectedModelSearchItems.value[column.key] = null;
    }
  });

  // Close dropdowns when clicking outside
  document.addEventListener('click', (event) => {
    // Only close if the click is outside any model search dropdown or its input
    const target = event.target;
    let isClickInsideModelSearch = false;
    for (const key in showModelSearchDropdown.value) {
      if (showModelSearchDropdown.value[key]) {
        // Find the specific input and its associated dropdown element
        const inputElement = document.querySelector(`input[v-model="modelSearchQueries['${key}']"]`);
        // We need a more reliable way to select the dropdown for that specific key
        // Assign a ref or a unique data-attribute to the dropdown itself for robust selection
        // For now, rely on parent-child relationship or a very specific class structure if available.
        // Assuming the dropdown is always a direct sibling or child of a common ancestor that we can identify
        // For now, let's just check if the target is within any 'form-control' that might contain a model_search dropdown
        const formControlParent = target.closest('.form-control');
        if (formControlParent && formControlParent.querySelector('.absolute.top-full.left-0.right-0')) {
             // This is a rough check, ideally dropdowns would have unique refs or IDs
             isClickInsideModelSearch = true;
             break;
        }

        if (inputElement && inputElement.contains(target)) {
          isClickInsideModelSearch = true;
          break;
        }
      }
    }

    if (!isClickInsideModelSearch) {
      Object.keys(showModelSearchDropdown.value).forEach(key => {
        showModelSearchDropdown.value[key] = false;
      });
    }
  });
});
</script>

<style scoped>
/* Base Styling & General Enhancements */
.card {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.input, .select {
  transition: all 0.2s ease-in-out;
}

/* Custom Scrollbar for dropdowns */
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: hsl(var(--b3));
  border-radius: 2px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: hsl(var(--p) / 0.4);
  border-radius: 2px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: hsl(var(--p) / 0.6);
}

/* Animations */
@keyframes pulse-slow {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}
.animate-pulse-slow {
  animation: pulse-slow 3s infinite ease-in-out;
}

/* Model Search Dropdown Position (adjusting for simplified container) */
.absolute.top-full {
  /* This ensures the dropdown positions correctly relative to its parent .relative container */
  position: absolute;
  width: 100%;
}

/* Ensure filter fields have relative positioning for dropdowns */
.form-control {
  position: relative;
  z-index: 1; /* Default stacking context */
}

/* Higher z-index for active dropdowns */
.form-control .absolute {
  z-index: 10; /* Make dropdown appear above other filter cards */
}

/* Specific button styling adjustments */
.btn-ghost:hover {
  background-color: hsl(var(--b3));
}

/* Text filter search mode buttons */
.btn-primary {
  color: hsl(var(--pc)); /* Primary content color */
}

.btn-ghost.hover\:btn-primary:hover {
  background-color: hsl(var(--p) / 0.1);
  color: hsl(var(--p));
}

/* Badge active animation */
.badge.badge-primary.badge-xs.animate-pulse {
  animation: gentlePulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
@keyframes gentlePulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}

/* Clear selection button in dropdown */
.hover\:bg-error\/10:hover {
  background-color: hsl(var(--er) / 0.1);
}

/* General Input focus styles */
.input:focus, .select:focus {
  border-color: hsl(var(--p));
  box-shadow: 0 0 0 2px hsl(var(--p) / 0.2);
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .card .px-6 {
    padding-left: 1rem;
    padding-right: 1rem;
  }
  .card .py-4 {
    padding-top: 0.75rem;
    padding-bottom: 0.75rem;
  }
  .grid.grid-cols-1.md\:grid-cols-2 {
    grid-template-columns: 1fr;
  }
  .stats-horizontal {
    display: none; /* Hide stats on small screens to simplify header */
  }
  .absolute.top-full.left-0.right-0 {
    left: -1rem; /* Adjust for full width */
    right: -1rem;
    max-width: calc(100% + 2rem);
  }
}
</style>
