<template>
  <div v-if="advancedFilters && advancedFilters.length > 0" class="mb-6">
    <!-- Toggle Button with Enhanced Information -->
    <div class="mb-4">
      <button
        @click="isOpen = !isOpen"
        class="btn btn-outline btn-secondary gap-2 text-sm font-semibold transition-all duration-300 transform hover:-translate-y-0.5 shadow-md hover:shadow-lg"
      >
        <Settings class="w-4 h-4" />
        {{ isOpen ? 'Hide Backend Filters' : 'Show Backend Filters' }}

        <!-- Enhanced Status Badge -->
        <div class="flex items-center gap-1">
          <div class="badge badge-success badge-sm" v-if="activeCount > 0">
            {{ activeCount }} Active
          </div>
          <div class="badge badge-warning badge-sm" v-if="disabledCount > 0">
            {{ disabledCount }} Disabled
          </div>
          <div class="badge badge-ghost badge-sm">
            {{ totalCount }} Total
          </div>
        </div>

        <ChevronDown
          class="w-4 h-4 transition-transform duration-300"
          :class="{ 'rotate-180': isOpen }"
        />
      </button>

      <!-- ✨ NEW: Summary Information (always visible) -->
      <div v-if="!isOpen && hasFilters" class="mt-2 text-xs text-base-content/60">
        <div class="flex flex-wrap items-center gap-2">
          <span class="font-medium">Active filters:</span>
          <div class="flex flex-wrap gap-1">
            <span
              v-for="(filter, index) in activeFiltersPreview"
              :key="index"
              class="badge badge-success badge-xs"
              :title="getFilterTooltip(filter)"
            >
              {{ formatFilterPreview(filter) }}
            </span>
            <span
              v-if="activeCount > maxPreviewItems"
              class="badge badge-ghost badge-xs"
            >
              +{{ activeCount - maxPreviewItems }} more
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Collapsible Content -->
    <Transition
      enter-active-class="transition-all duration-500 ease-out"
      enter-from-class="opacity-0 max-h-0"
      enter-to-class="opacity-100 max-h-screen"
      leave-active-class="transition-all duration-300 ease-in"
      leave-from-class="opacity-100 max-h-screen"
      leave-to-class="opacity-0 max-h-0"
    >
      <div v-show="isOpen" class="overflow-hidden">
        <div class="card bg-base-100 shadow-lg border border-base-200 rounded-box">
          <!-- Header with Statistics -->
          <div class="bg-base-200/40 px-6 py-4 border-b border-base-200 rounded-t-box">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
              <div class="flex items-center gap-3">
                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-secondary/10 flex items-center justify-center">
                  <Settings class="w-4 h-4 text-secondary" />
                </div>
                <div>
                  <h3 class="text-lg font-bold text-base-content leading-tight">Backend Advanced Filters</h3>
                  <p class="text-xs text-base-content/60">Manage predefined filters from the backend</p>
                </div>
              </div>

              <!-- Enhanced Statistics -->
              <div class="flex flex-wrap items-center gap-4 text-sm">
                <div class="stats stats-horizontal shadow-sm bg-base-200/50">
                  <div class="stat px-4 py-2">
                    <div class="stat-title text-xs">Total</div>
                    <div class="stat-value text-lg text-base-content">{{ totalCount }}</div>
                  </div>
                  <div class="stat px-4 py-2">
                    <div class="stat-title text-xs">Active</div>
                    <div class="stat-value text-lg text-success">{{ activeCount }}</div>
                  </div>
                  <div class="stat px-4 py-2">
                    <div class="stat-title text-xs">Disabled</div>
                    <div class="stat-value text-lg text-warning">{{ disabledCount }}</div>
                  </div>
                  <div class="stat px-4 py-2">
                    <div class="stat-title text-xs">Impact</div>
                    <div class="stat-value text-lg text-info">{{ impactPercentage }}%</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Quick Actions Row -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mt-4 pt-4 border-t border-base-300">
              <div class="flex items-center gap-2 text-xs text-base-content/60">
                <div class="w-2 h-2 rounded-full bg-secondary animate-pulse-slow"></div>
                <span>Changes apply immediately to data fetching</span>
              </div>

              <div class="flex gap-2">
                <button
                  @click="disableAll"
                  class="btn btn-warning btn-outline btn-xs gap-1 hover:btn-warning transition-all duration-200"
                  :disabled="activeCount === 0"
                >
                  <X class="w-3 h-3" />
                  Disable All
                </button>
                <button
                  @click="enableAll"
                  class="btn btn-success btn-outline btn-xs gap-1 hover:btn-success transition-all duration-200"
                  :disabled="activeCount === totalCount"
                >
                  <CheckCircle class="w-3 h-3" />
                  Enable All
                </button>
                <button
                  @click="resetToDefaults"
                  class="btn btn-primary btn-xs gap-1 shadow-lg hover:shadow-xl transition-all duration-200"
                  :disabled="!hasChanges"
                >
                  <RotateCcw class="w-3 h-3" />
                  Reset
                </button>
              </div>
            </div>
          </div>

          <!-- Filter Cards Grid -->
          <div class="p-6">
            <!-- Search/Filter for large lists -->
            <div v-if="totalCount > 6" class="mb-4">
              <div class="relative">
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="Search filters..."
                  class="input input-bordered input-sm w-full pr-10"
                >
                <Search class="w-4 h-4 absolute right-3 top-1/2 transform -translate-y-1/2 text-base-content/40" />
              </div>
            </div>

            <!-- Filters Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
              <TransitionGroup
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0 translate-y-2 scale-98"
                enter-to-class="opacity-100 translate-y-0 scale-100"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100 translate-y-0 scale-100"
                leave-to-class="opacity-0 translate-y-2 scale-98"
              >
                <div
                  v-for="(filter, index) in filteredFilters"
                  :key="`filter-${index}`"
                  class="card bg-base-50 border transition-all duration-300 hover:shadow-lg cursor-pointer"
                  :class="getFilterCardClasses(filter, index)"
                  @click="toggleFilter(index)"
                >
                  <div class="card-body p-4">
                    <!-- Header with toggle -->
                    <div class="flex items-start justify-between mb-3">
                      <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                          <component
                            :is="getFilterIcon(filter.operator)"
                            class="w-4 h-4 flex-shrink-0"
                            :class="getFilterIconClasses(filter, index)"
                          />
                          <h4 class="font-semibold text-sm text-base-content">
                            {{ formatFieldName(filter.field) }}
                          </h4>
                        </div>
                        <p class="text-xs text-base-content/60 leading-relaxed">
                          {{ getFilterDescription(filter) }}
                        </p>
                      </div>

                      <div class="form-control">
                        <label class="cursor-pointer label p-0">
                          <input
                            type="checkbox"
                            class="toggle toggle-success toggle-sm"
                            :checked="isFilterEnabled(index)"
                            @change="toggleFilter(index)"
                            @click.stop
                          />
                        </label>
                      </div>
                    </div>

                    <!-- Filter Details -->
                    <div class="space-y-2">
                      <!-- Operator Badge -->
                      <div class="flex items-center gap-2 text-xs">
                        <span class="badge badge-outline badge-sm font-mono">
                          {{ filter.operator }}
                        </span>
                        <span v-if="hasFilterValue(filter)" class="text-base-content/70 font-medium">
                          {{ formatFilterValue(filter) }}
                        </span>
                      </div>

                      <!-- Additional Options -->
                      <div v-if="filter.options && Object.keys(filter.options).length > 0" class="text-xs">
                        <div class="flex flex-wrap gap-1">
                          <span class="text-base-content/50">Options:</span>
                          <span
                            v-for="(value, key) in filter.options"
                            :key="key"
                            class="badge badge-ghost badge-xs"
                          >
                            {{ key }}: {{ formatOptionValue(value) }}
                          </span>
                        </div>
                      </div>

                      <!-- Impact Indicator -->
                      <div class="flex items-center gap-2 text-xs">
                        <span class="text-base-content/50">Impact:</span>
                        <div class="flex items-center gap-1">
                          <div
                            class="w-3 h-1 rounded-full"
                            :class="getImpactColorClass(filter)"
                          ></div>
                          <span class="font-medium">{{ getImpactLevel(filter) }}</span>
                        </div>
                      </div>
                    </div>

                    <!-- Status Footer -->
                    <div class="card-actions justify-end mt-3 pt-3 border-t border-base-300">
                      <div class="badge badge-sm" :class="getStatusBadgeClasses(filter, index)">
                        {{ isFilterEnabled(index) ? 'Active' : 'Disabled' }}
                      </div>
                    </div>
                  </div>
                </div>
              </TransitionGroup>
            </div>

            <!-- Empty State for Search -->
            <div v-if="searchQuery && filteredFilters.length === 0" class="text-center py-8">
              <Search class="w-12 h-12 mx-auto text-base-content/20 mb-4" />
              <p class="text-base-content/60">No filters match "{{ searchQuery }}"</p>
              <button @click="searchQuery = ''" class="btn btn-ghost btn-sm mt-2">Clear search</button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import {
  Settings,
  ChevronDown,
  X,
  CheckCircle,
  RotateCcw,
  Search,
  Database,
  Zap,
  AlertCircle,
  FileText,
  Target,
  Filter,
  Calendar
} from 'lucide-vue-next';

const props = defineProps({
  advancedFilters: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['update:filters']);

// State
const isOpen = ref(false);
const searchQuery = ref('');
const currentFilters = ref([]);
const originalFilters = ref([]);
const maxPreviewItems = ref(3);

// Computed Properties
const hasFilters = computed(() => props.advancedFilters && props.advancedFilters.length > 0);
const totalCount = computed(() => currentFilters.value.length);
const activeCount = computed(() => currentFilters.value.filter(f => f.enabled !== false).length);
const disabledCount = computed(() => currentFilters.value.filter(f => f.enabled === false).length);
const impactPercentage = computed(() => totalCount.value > 0 ? Math.round((activeCount.value / totalCount.value) * 100) : 0);
const hasChanges = computed(() => {
  return currentFilters.value.some((current, index) => {
    const original = originalFilters.value[index];
    return original && current.enabled !== (original.enabled !== false);
  });
});

const activeFiltersPreview = computed(() => {
  return currentFilters.value
    .filter(f => f.enabled !== false)
    .slice(0, maxPreviewItems.value);
});

const filteredFilters = computed(() => {
  if (!searchQuery.value) return currentFilters.value;

  const query = searchQuery.value.toLowerCase();
  return currentFilters.value.filter(filter =>
    formatFieldName(filter.field).toLowerCase().includes(query) ||
    filter.operator.toLowerCase().includes(query) ||
    getFilterDescription(filter).toLowerCase().includes(query)
  );
});

// Methods
const formatFieldName = (field) => {
  return field.split('_').map(word =>
    word.charAt(0).toUpperCase() + word.slice(1)
  ).join(' ');
};

const getFilterDescription = (filter) => {
  const descriptions = {
    whereNotIn: `Exclude records where ${formatFieldName(filter.field)} is in the specified list`,
    whereIn: `Include only records where ${formatFieldName(filter.field)} is in the specified list`,
    whereBetween: `Include records where ${formatFieldName(filter.field)} is between specified values`,
    whereNotBetween: `Exclude records where ${formatFieldName(filter.field)} is between specified values`,
    whereNull: `Include only records where ${formatFieldName(filter.field)} is null/empty`,
    whereNotNull: `Include only records where ${formatFieldName(filter.field)} is not null/empty`,
    where: `Filter ${formatFieldName(filter.field)} using custom operator`,
    whereDate: `Filter ${formatFieldName(filter.field)} by date`,
    whereMonth: `Filter ${formatFieldName(filter.field)} by month`,
    whereYear: `Filter ${formatFieldName(filter.field)} by year`,
    whereHas: `Filter by related ${formatFieldName(filter.field)} records`
  };

  return descriptions[filter.operator] || `Custom filter on ${formatFieldName(filter.field)}`;
};

const getFilterIcon = (operator) => {
  const iconMap = {
    whereNotIn: X,
    whereIn: CheckCircle,
    whereBetween: Target,
    whereNotBetween: AlertCircle,
    whereNull: Database,
    whereNotNull: Zap,
    where: Filter,
    whereDate: Calendar,
    whereMonth: Calendar,
    whereYear: Calendar,
    whereHas: FileText
  };

  return iconMap[operator] || Filter;
};

const formatFilterPreview = (filter) => {
  const field = formatFieldName(filter.field);
  if (field.length > 12) {
    return field.substring(0, 12) + '...';
  }
  return field;
};

const getFilterTooltip = (filter) => {
  return `${formatFieldName(filter.field)} (${filter.operator})${filter.value ? ': ' + formatFilterValue(filter) : ''}`;
};

const hasFilterValue = (filter) => {
  return filter.value !== null && filter.value !== undefined;
};

const formatFilterValue = (filter) => {
  if (filter.value === null || filter.value === undefined) return '';

  if (Array.isArray(filter.value)) {
    if (filter.value.length <= 2) {
      return filter.value.join(', ');
    } else {
      return `${filter.value.slice(0, 2).join(', ')} +${filter.value.length - 2} more`;
    }
  }

  if (typeof filter.value === 'object') {
    return JSON.stringify(filter.value);
  }

  const valueStr = String(filter.value);
  return valueStr.length > 20 ? valueStr.substring(0, 20) + '...' : valueStr;
};

const formatOptionValue = (value) => {
  if (typeof value === 'string' && value.length > 10) {
    return value.substring(0, 10) + '...';
  }
  return String(value);
};

const getImpactLevel = (filter) => {
  // You can customize this based on your filter types
  const highImpactOperators = ['whereNotIn', 'whereIn', 'whereNotNull'];
  const mediumImpactOperators = ['where', 'whereBetween', 'whereDate'];

  if (highImpactOperators.includes(filter.operator)) return 'High';
  if (mediumImpactOperators.includes(filter.operator)) return 'Medium';
  return 'Low';
};

const getImpactColorClass = (filter) => {
  const level = getImpactLevel(filter);
  switch (level) {
    case 'High': return 'bg-error';
    case 'Medium': return 'bg-warning';
    default: return 'bg-success';
  }
};

const isFilterEnabled = (index) => {
  return currentFilters.value[index]?.enabled !== false;
};

const getFilterCardClasses = (filter, index) => {
  const enabled = isFilterEnabled(index);
  return {
    'border-success bg-success/5 hover:bg-success/10': enabled,
    'border-warning bg-warning/5 hover:bg-warning/10 opacity-70': !enabled
  };
};

const getFilterIconClasses = (filter, index) => {
  const enabled = isFilterEnabled(index);
  return {
    'text-success': enabled,
    'text-warning': !enabled
  };
};

const getStatusBadgeClasses = (filter, index) => {
  const enabled = isFilterEnabled(index);
  return {
    'badge-success': enabled,
    'badge-warning': !enabled
  };
};

// Actions
const toggleFilter = (index) => {
  if (currentFilters.value[index]) {
    currentFilters.value[index].enabled = !currentFilters.value[index].enabled;
    emitChange();
  }
};

const enableAll = () => {
  currentFilters.value.forEach(filter => {
    filter.enabled = true;
  });
  emitChange();
};

const disableAll = () => {
  currentFilters.value.forEach(filter => {
    filter.enabled = false;
  });
  emitChange();
};

const resetToDefaults = () => {
  currentFilters.value = originalFilters.value.map(filter => ({
    ...filter,
    enabled: filter.enabled !== false
  }));
  emitChange();
};

const emitChange = () => {
  const enabledFilters = currentFilters.value.filter(f => f.enabled !== false);
  emit('update:filters', enabledFilters);
};

// Watch for prop changes
watch(() => props.advancedFilters, (newFilters) => {
  if (newFilters && newFilters.length > 0) {
    originalFilters.value = [...newFilters];
    currentFilters.value = newFilters.map(filter => ({
      ...filter,
      enabled: filter.enabled !== false
    }));
  }
}, { immediate: true, deep: true });
</script>

<style scoped>
/* Smooth transitions */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

/* Pulse animation */
@keyframes pulse-slow {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}
.animate-pulse-slow {
  animation: pulse-slow 3s infinite ease-in-out;
}

/* Enhanced card hover effects */
.card:hover {
  transform: translateY(-2px);
}

/* Toggle styling */
.toggle:checked {
  background-color: hsl(var(--su));
}

/* Stats styling */
.stats-horizontal .stat {
  place-items: center;
}

/* Search input focus */
.input:focus {
  border-color: hsl(var(--s));
  box-shadow: 0 0 0 2px hsl(var(--s) / 0.2);
}

/* Badge improvements */
.badge {
  font-weight: 500;
  letter-spacing: 0.025em;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .stats-horizontal {
    flex-direction: column;
  }

  .grid {
    grid-template-columns: 1fr;
  }
}
</style>
