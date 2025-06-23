<template>
  <div class="w-full">
    <!-- SIMPLE TOGGLE BUTTON -->
    <div class="mb-4">
      <button
        @click="isFilterOpen = !isFilterOpen"
        class="btn btn-outline btn-primary gap-2 transition-all duration-300"
      >
        <Filter class="w-4 h-4" />
        {{ isFilterOpen ? 'Hide Advanced Filters' : 'Show Advanced Filters' }}
        <div v-if="Object.keys(activeFilters).length > 0" class="badge badge-primary">
          {{ Object.keys(activeFilters).length }}
        </div>
        <ChevronDown
          class="w-4 h-4 transition-transform duration-300"
          :class="{ 'rotate-180': isFilterOpen }"
        />
      </button>
    </div>

    <!-- FILTER CONTENT - Hidden by default -->
    <div v-show="isFilterOpen" class="card bg-gradient-to-br from-base-100 via-base-50 to-base-100 shadow-xl border border-base-200 hover:shadow-2xl transition-all duration-500">
      <!-- Desktop Header -->
      <div class="bg-gradient-to-r from-base-200/50 via-base-100 to-base-200/50 px-6 py-4 border-b border-base-200">
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

                  <!-- Enhanced Model Search Filter with Search Capability -->
                  <div v-if="column.type === 'model_search'" class="space-y-3">
                    <!-- Search Input -->
                    <div class="relative">
                      <input
                        type="text"
                        v-model="modelSearchQueries[column.key]"
                        class="input input-bordered w-full pr-10 focus:input-primary transition-all duration-200"
                        :placeholder="`Search ${column.label.toLowerCase()}...`"
                        @input="handleModelSearch(column)"
                        @focus="showModelSearchDropdown[column.key] = true"
                      />
                      <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                        <div v-if="isLoadingOptions[column.key]" class="loading loading-spinner loading-sm"></div>
                        <Search v-else class="w-4 h-4 text-base-content/40" />
                      </div>
                    </div>

                    <!-- Search Results Dropdown -->
                    <div
                      v-if="showModelSearchDropdown[column.key] && (modelSearchResults[column.key]?.length > 0 || modelSearchQueries[column.key])"
                      class="relative"
                    >
                      <div class="absolute top-0 left-0 right-0 z-[9999] max-h-60 overflow-y-auto bg-base-100 border border-base-300 rounded-lg shadow-2xl backdrop-blur-sm">
                        <!-- Loading State -->
                        <div v-if="isLoadingOptions[column.key]" class="p-3 text-center">
                          <div class="flex items-center justify-center gap-2">
                            <div class="loading loading-spinner loading-sm"></div>
                            <span class="text-sm text-base-content/60">Searching...</span>
                          </div>
                        </div>

                        <!-- No Results -->
                        <div v-else-if="modelSearchQueries[column.key] && modelSearchResults[column.key]?.length === 0" class="p-3 text-center text-sm text-base-content/60">
                          No results found for "{{ modelSearchQueries[column.key] }}"
                        </div>

                        <!-- Search Results -->
                        <div v-else class="max-h-48 overflow-y-auto">
                          <button
                            v-for="option in modelSearchResults[column.key] || []"
                            :key="option.id"
                            @click="selectModelSearchOption(column.key, option)"
                            class="w-full text-left px-3 py-2 hover:bg-base-200 transition-colors duration-150 border-b border-base-200 last:border-b-0"
                          >
                            <div class="flex items-center justify-between">
                              <div>
                                <div class="font-medium text-sm">{{ option[column.displayKey] }}</div>
                                <div class="text-xs text-base-content/60">
                                  {{ formatModelSearchPreview(option, column) }}
                                </div>
                              </div>
                              <div v-if="filters[column.key] == option.id" class="text-primary">
                                <Check class="w-4 h-4" />
                              </div>
                            </div>
                          </button>
                        </div>

                        <!-- Clear Selection Option -->
                        <div v-if="filters[column.key]" class="border-t border-base-300">
                          <button
                            @click="clearModelSearchSelection(column.key)"
                            class="w-full text-left px-3 py-2 hover:bg-error/10 text-error transition-colors duration-150"
                          >
                            <div class="flex items-center gap-2">
                              <X class="w-4 h-4" />
                              <span class="text-sm">Clear selection</span>
                            </div>
                          </button>
                        </div>
                      </div>
                    </div>

                    <!-- Selected Item Display -->
                    <div v-if="getSelectedModelSearchItem(column.key, column)" class="mt-2">
                      <div class="card bg-base-200 border border-base-300">
                        <div class="card-body p-3">
                          <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                              <div class="w-8 h-8 rounded-lg bg-primary/20 flex items-center justify-center">
                                <User class="w-4 h-4 text-primary" />
                              </div>
                              <div>
                                <div class="font-medium text-sm">{{ getSelectedModelSearchItem(column.key, column)[column.displayKey] }}</div>
                                <div class="text-xs text-base-content/60">Selected {{ column.label.toLowerCase() }}</div>
                              </div>
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
                  <div v-else-if="column.type === 'date' || column.type === 'timestamp'" class="space-y-2">
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
  User
} from 'lucide-vue-next';

const props = defineProps({
  columns: {
    type: Array,
    required: true
  }
});

const emit = defineEmits(['onFilterChange']);

// State management - HIDDEN BY DEFAULT
const isFilterOpen = ref(false);
const modelOptions = ref({});
const isLoadingOptions = ref({});
const modelSearchQueries = ref({});
const modelSearchResults = ref({});
const showModelSearchDropdown = ref({});
const selectedModelSearchItems = ref({});
const searchTimeouts = ref({});

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
    model_search: 'Search related data',
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

// Enhanced model search functionality
const handleModelSearch = async (column) => {
  const query = modelSearchQueries.value[column.key];

  // Clear previous timeout
  if (searchTimeouts.value[column.key]) {
    clearTimeout(searchTimeouts.value[column.key]);
  }

  // If query is empty, hide dropdown
  if (!query || query.length < 2) {
    modelSearchResults.value[column.key] = [];
    showModelSearchDropdown.value[column.key] = false;
    return;
  }

  // Debounce search
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
      searchColumns: column.columns.map(col => col.key), // Search across all defined columns
      limit: 20 // Limit results for performance
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
  handleFilterChange();
};

const clearModelSearchSelection = (columnKey) => {
  filters.value[columnKey] = '';
  selectedModelSearchItems.value[columnKey] = null;
  modelSearchQueries.value[columnKey] = '';
  showModelSearchDropdown.value[columnKey] = false;
  handleFilterChange();
};

const getSelectedModelSearchItem = (columnKey, column) => {
  return selectedModelSearchItems.value[columnKey];
};

const getColumnByKey = (key) => {
  return props.columns.find(col => col.key === key);
};

const formatModelSearchPreview = (option, column) => {
  // Create a preview string showing other available fields
  const previewFields = [];

  column.columns.forEach(col => {
    if (col.key !== column.displayKey && option[col.key]) {
      previewFields.push(`${col.key}: ${option[col.key]}`);
    }
  });

  return previewFields.slice(0, 2).join(' • ') || `ID: ${option.id}`;
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

  // Clear model search specific state if it's a model_search field
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

  handleFilterChange();
};

const resetAllFilters = () => {
  filters.value = props.columns.reduce((acc, column) => {
    acc[column.key] = column?.type === 'date' ? { from: '', to: '' } : '';
    return acc;
  }, {});
  searchModesState.value = {};

  // Reset model search state
  modelSearchQueries.value = {};
  modelSearchResults.value = {};
  selectedModelSearchItems.value = {};
  showModelSearchDropdown.value = {};

  // Clear any pending timeouts
  Object.values(searchTimeouts.value).forEach(timeout => {
    if (timeout) clearTimeout(timeout);
  });
  searchTimeouts.value = {};

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
        // Initialize state for model search fields
        modelSearchQueries.value[column.key] = '';
        modelSearchResults.value[column.key] = [];
        showModelSearchDropdown.value[column.key] = false;
        selectedModelSearchItems.value[column.key] = null;

        // Optionally load initial options (you might want to skip this for performance)
        // loadModelOptions(column);
      }
    });
  }

  // Close dropdowns when clicking outside
  document.addEventListener('click', (event) => {
    const isModelSearchClick = event.target.closest('.relative');
    if (!isModelSearchClick) {
      Object.keys(showModelSearchDropdown.value).forEach(key => {
        showModelSearchDropdown.value[key] = false;
      });
    }
  });
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

/* Enhanced Dropdown Styling with Higher Z-Index */
.z-\[9999\] {
  z-index: 9999 !important;
  position: relative;
}

.absolute.z-\[9999\] {
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
  backdrop-filter: blur(8px);
  border: 2px solid hsl(var(--b3));
}

/* Ensure dropdown container has proper stacking context */
.relative:has(.z-\[9999\]) {
  z-index: 9998;
  position: relative;
}

/* Search Results Hover Effect */
.hover\:bg-base-200:hover {
  background-color: hsl(var(--b2));
  transform: translateX(2px);
}

/* Selected Item Card Enhancement */
.card.bg-base-200 {
  transition: all 0.3s ease;
}

.card.bg-base-200:hover {
  transform: scale(1.02);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Search Input Enhancement */
.input[type="text"]:focus {
  border-color: hsl(var(--p));
  box-shadow: 0 0 0 3px hsl(var(--p) / 0.1);
}

/* Clear Selection Button */
.btn-ghost.btn-xs {
  border-radius: 50%;
  width: 2rem;
  height: 2rem;
  padding: 0;
}

/* Loading States */
.loading.loading-sm {
  width: 1rem;
  height: 1rem;
}

/* Dropdown Animation */
.absolute.top-0.z-\[9999\] {
  animation: dropdownSlideIn 0.2s ease-out;
}

@keyframes dropdownSlideIn {
  from {
    opacity: 0;
    transform: translateY(-8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Search Preview Text */
.text-xs.text-base-content\/60 {
  font-family: ui-monospace, 'Cascadia Code', 'Source Code Pro', Menlo, Monaco, Consolas, 'DejaVu Sans Mono', monospace;
}

/* Active Filter Enhancement */
.badge.badge-primary.badge-xs {
  animation: gentlePulse 2s ease-in-out infinite;
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

  /* Make dropdowns full width on mobile */
  .absolute.z-\[9999\] {
    left: -1rem;
    right: -1rem;
    max-width: calc(100vw - 2rem);
    z-index: 9999 !important;
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

  .absolute.z-\[9999\] {
    border: 2px solid hsl(var(--bc));
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

  @keyframes dropdownSlideIn {
    from, to {
      opacity: 1;
      transform: none;
    }
  }
}

/* Print Styles */
@media print {
  .btn,
  .loading {
    display: none !important;
  }

  .absolute.z-\[9999\] {
    position: static !important;
    box-shadow: none !important;
    border: 1px solid #000 !important;
  }
}

/* Enhanced accessibility */
.btn:focus-visible {
  outline: 2px solid hsl(var(--p));
  outline-offset: 2px;
}

/* Tooltip enhancement */
[title] {
  position: relative;
}

/* Search result item enhancement */
.w-full.text-left.px-3.py-2 {
  border-radius: 0.375rem;
  margin: 0.125rem;
  width: calc(100% - 0.25rem);
}

/* Clear selection button in dropdown */
.border-t.border-base-300 {
  border-top: 1px solid hsl(var(--b3));
}

.hover\:bg-error\/10:hover {
  background-color: hsl(var(--er) / 0.1);
}

/* Selected item indicator */
.text-primary .w-4.h-4 {
  filter: drop-shadow(0 0 4px hsl(var(--p) / 0.3));
}

/* Scrollbar for dropdown results */
.max-h-48.overflow-y-auto::-webkit-scrollbar {
  width: 4px;
}

.max-h-48.overflow-y-auto::-webkit-scrollbar-track {
  background: hsl(var(--b3));
}

.max-h-48.overflow-y-auto::-webkit-scrollbar-thumb {
  background: hsl(var(--p) / 0.3);
  border-radius: 2px;
}

.max-h-48.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: hsl(var(--p) / 0.5);
}

/* Enhanced z-index management */
.z-\[9999\] {
  z-index: 9999 !important;
}

/* Ensure parent containers don't interfere */
.card.bg-base-100 {
  position: relative;
  z-index: 1;
}

.form-control.group\/filter {
  position: relative;
  z-index: 2;
}

/* Backdrop for mobile */
@media (max-width: 640px) {
  .absolute.z-\[9999\]::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.1);
    z-index: -1;
  }
}
</style>
