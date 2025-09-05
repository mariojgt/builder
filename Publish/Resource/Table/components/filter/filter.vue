<template>
    <div class="bg-base-100/95 backdrop-blur-sm border border-base-300 rounded-lg shadow-sm relative z-10">
      <div class="p-2">
        <!-- Mobile Filter Toggle -->
        <div class="lg:hidden w-full mb-2">
          <button
            @click="isFilterOpen = !isFilterOpen"
            class="btn btn-ghost btn-xs w-full justify-between h-8"
          >
            <span class="flex items-center gap-1.5">
              <SlidersHorizontal class="w-3 h-3 text-primary" />
              <span class="font-medium text-xs">{{ isFilterOpen ? 'Hide Filters' : 'Show Filters' }}</span>
            </span>
            <ChevronDown
              class="w-3 h-3 transition-transform duration-200"
              :class="{ 'rotate-180': isFilterOpen }"
            />
          </button>
        </div>

        <!-- Desktop Main Controls Row -->
        <div class="hidden lg:flex items-start justify-between gap-2">
          <!-- Left Side: Search & Active Filters -->
          <div class="flex-1 max-w-2xl">
            <!-- Search Input -->
            <div class="relative mb-2">
              <input
                v-model="search"
                type="text"
                placeholder="Search records..."
                class="input input-bordered input-xs w-full pl-8 pr-8 h-8 text-xs focus:input-primary"
                @keyup.enter="triggerSearch"
              />
              <SearchIcon class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3 h-3 text-base-content/40" />
              <button
                v-if="search"
                @click="clearSearch"
                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-base-content/40 hover:text-error transition-colors p-0.5"
              >
                <X class="w-3 h-3" />
              </button>
            </div>

            <!-- Active Filters Display (Inline) -->
            <div v-if="hasActiveFilters" class="flex flex-wrap items-center gap-1.5">
              <span class="text-xs font-medium text-base-content/70 mr-1">Active:</span>
              <div v-if="search" class="badge badge-primary gap-1 px-2 py-1 text-xs">
                <SearchIcon class="w-3 h-3" />
                <span class="font-medium">Search:</span>
                <span>{{ search.length > 15 ? search.substring(0, 15) + '...' : search }}</span>
                <button @click="clearSearch" class="hover:text-primary-content/70 ml-0.5">
                  <X class="w-2.5 h-2.5" />
                </button>
              </div>

              <div v-if="filterBy && selectedFilterLabel" class="badge badge-secondary gap-1 px-2 py-1 text-xs">
                <FilterIcon class="w-3 h-3" />
                <span class="font-medium">Filter:</span>
                <span>{{ selectedFilterLabel }}</span>
                <button @click="filterBy = ''" class="hover:text-secondary-content/70 ml-0.5">
                  <X class="w-2.5 h-2.5" />
                </button>
              </div>

              <div v-if="perPage !== 10" class="badge badge-accent gap-1 px-2 py-1 text-xs">
                <ListFilter class="w-3 h-3" />
                <span class="font-medium">Show:</span>
                <span>{{ perPage }}</span>
                <button @click="perPage = 10" class="hover:text-accent-content/70 ml-0.5">
                  <X class="w-2.5 h-2.5" />
                </button>
              </div>

              <div v-if="orderBy !== 'asc'" class="badge badge-warning gap-1 px-2 py-1 text-xs">
                <ArrowDownIcon class="w-3 h-3" />
                <span class="font-medium">Sort:</span>
                <span>Z-A</span>
                <button @click="orderBy = 'asc'" class="hover:text-warning-content/70 ml-0.5">
                  <X class="w-2.5 h-2.5" />
                </button>
              </div>
            </div>
          </div>

          <!-- Right Side: Filter Controls -->
          <div class="flex items-center gap-1.5 mt-0">
            <!-- Per Page -->
            <div class="dropdown dropdown-hover">
              <label tabindex="0" class="btn btn-xs btn-outline gap-1.5 min-w-[4.5rem] h-8">
                <ListFilter class="w-3 h-3 text-primary" />
                <span class="font-medium text-xs">{{ perPage }}</span>
              </label>
              <ul tabindex="0" class="dropdown-content z-[9999] menu p-1.5 shadow-lg bg-base-100 rounded-box w-28">
                <li v-for="count in [10, 25, 50, 100]" :key="count">
                  <button
                    @click="perPage = count"
                    :class="{ 'active': perPage === count }"
                    class="flex items-center gap-1.5 py-1 px-2 text-xs"
                  >
                    <Check v-if="perPage === count" class="w-3 h-3 text-primary" />
                    <span class="w-3" v-else></span>
                    <span>{{ count }}</span>
                  </button>
                </li>
              </ul>
            </div>

            <!-- Filter By -->
            <div class="dropdown dropdown-hover" v-if="filterColumns.length > 0">
              <label tabindex="0" class="btn btn-xs btn-outline gap-1.5 min-w-[6rem] h-8">
                <FilterIcon class="w-3 h-3 text-secondary" />
                <span class="font-medium truncate text-xs">{{ selectedFilterLabel || 'Filter' }}</span>
              </label>
              <ul tabindex="0" class="dropdown-content z-[9999] menu p-1.5 shadow-lg bg-base-100 rounded-box w-48">
                <li v-for="column in filterColumns" :key="column.value">
                  <button
                    @click="filterBy = column.value"
                    :class="{ 'active': filterBy === column.value }"
                    class="flex items-center gap-1.5 text-left py-1 px-2 text-xs"
                  >
                    <Check v-if="filterBy === column.value" class="w-3 h-3 text-secondary" />
                    <span class="w-3" v-else></span>
                    <span>{{ column.label }}</span>
                  </button>
                </li>
              </ul>
            </div>

            <!-- Sort Direction -->
            <div class="dropdown dropdown-hover">
              <label tabindex="0" class="btn btn-xs btn-outline gap-1.5 min-w-[5rem] h-8">
                <component :is="orderBy === 'asc' ? ArrowUpIcon : ArrowDownIcon" class="w-3 h-3 text-accent" />
                <span class="font-medium text-xs">{{ orderBy === 'asc' ? 'A-Z' : 'Z-A' }}</span>
              </label>
              <ul tabindex="0" class="dropdown-content z-[9999] menu p-1.5 shadow-lg bg-base-100 rounded-box w-36">
                <li>
                  <button
                    @click="orderBy = 'asc'"
                    :class="{ 'active': orderBy === 'asc' }"
                    class="flex items-center gap-1.5 py-1 px-2 text-xs"
                  >
                    <ArrowUpIcon class="w-3 h-3 text-accent" />
                    <span>A-Z</span>
                  </button>
                </li>
                <li>
                  <button
                    @click="orderBy = 'desc'"
                    :class="{ 'active': orderBy === 'desc' }"
                    class="flex items-center gap-1.5 py-1 px-2 text-xs"
                  >
                    <ArrowDownIcon class="w-3 h-3 text-accent" />
                    <span>Z-A</span>
                  </button>
                </li>
              </ul>
            </div>

            <!-- Reset Button -->
            <button
              class="btn btn-xs gap-1.5 min-w-[4rem] h-8"
              :class="hasActiveFilters ? 'btn-warning' : 'btn-ghost'"
              @click="resetFilter"
              :disabled="!hasActiveFilters"
            >
              <RotateCcwIcon class="w-3 h-3" />
              <span class="font-medium text-xs">Reset</span>
            </button>
          </div>
        </div>

        <!-- Mobile Expanded View -->
        <div
          class="lg:hidden transition-all duration-300"
          :class="isFilterOpen ? 'block' : 'hidden'"
        >
          <!-- Mobile Search -->
          <div class="mb-3">
            <label class="label py-1">
              <span class="label-text font-medium text-xs">Search</span>
            </label>
            <div class="relative">
              <input
                v-model="search"
                type="text"
                placeholder="Search records..."
                class="input input-bordered input-xs w-full pl-8 pr-8 h-8 focus:input-primary"
                @keyup.enter="triggerSearch"
              />
              <SearchIcon class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3 h-3 text-base-content/40" />
              <button
                v-if="search"
                @click="clearSearch"
                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-base-content/40 hover:text-error p-0.5"
              >
                <X class="w-3 h-3" />
              </button>
            </div>
          </div>

          <!-- Mobile Controls -->
          <div class="space-y-3">
            <!-- Per Page -->
            <div>
              <label class="label py-1">
                <span class="label-text font-medium text-xs">Items per page</span>
              </label>
              <div class="dropdown dropdown-hover w-full">
                <label tabindex="0" class="btn btn-outline btn-xs w-full justify-between h-8">
                  <span class="flex items-center gap-1.5">
                    <ListFilter class="w-3 h-3 text-primary" />
                    <span class="text-xs">{{ perPage }} items</span>
                  </span>
                  <ChevronDown class="w-3 h-3" />
                </label>
                <ul tabindex="0" class="dropdown-content z-[9999] menu p-1.5 shadow-lg bg-base-100 rounded-box w-full">
                  <li v-for="count in [10, 25, 50, 100]" :key="count">
                    <button @click="perPage = count" class="py-1 px-2 text-xs">{{ count }} items</button>
                  </li>
                </ul>
              </div>
            </div>

            <!-- Filter By -->
            <div v-if="filterColumns.length > 0">
              <label class="label py-1">
                <span class="label-text font-medium text-xs">Filter by column</span>
              </label>
              <div class="dropdown dropdown-hover w-full">
                <label tabindex="0" class="btn btn-outline btn-xs w-full justify-between h-8">
                  <span class="flex items-center gap-1.5">
                    <FilterIcon class="w-3 h-3 text-secondary" />
                    <span class="text-xs">{{ selectedFilterLabel || 'Choose column' }}</span>
                  </span>
                  <ChevronDown class="w-3 h-3" />
                </label>
                <ul tabindex="0" class="dropdown-content z-[9999] menu p-1.5 shadow-lg bg-base-100 rounded-box w-full">
                  <li v-for="column in filterColumns" :key="column.value">
                    <button @click="filterBy = column.value" class="py-1 px-2 text-xs">{{ column.label }}</button>
                  </li>
                </ul>
              </div>
            </div>

            <!-- Sort -->
            <div>
              <label class="label py-1">
                <span class="label-text font-medium text-xs">Sort order</span>
              </label>
              <div class="dropdown dropdown-hover w-full">
                <label tabindex="0" class="btn btn-outline btn-xs w-full justify-between h-8">
                  <span class="flex items-center gap-1.5">
                    <component :is="orderBy === 'asc' ? ArrowUpIcon : ArrowDownIcon" class="w-3 h-3 text-accent" />
                    <span class="text-xs">{{ orderBy === 'asc' ? 'A-Z' : 'Z-A' }}</span>
                  </span>
                  <ChevronDown class="w-3 h-3" />
                </label>
                <ul tabindex="0" class="dropdown-content z-[9999] menu p-1.5 shadow-lg bg-base-100 rounded-box w-full">
                  <li>
                    <button @click="orderBy = 'asc'" class="flex items-center gap-1.5 py-1 px-2 text-xs">
                      <ArrowUpIcon class="w-3 h-3" /> A-Z
                    </button>
                  </li>
                  <li>
                    <button @click="orderBy = 'desc'" class="flex items-center gap-1.5 py-1 px-2 text-xs">
                      <ArrowDownIcon class="w-3 h-3" /> Z-A
                    </button>
                  </li>
                </ul>
              </div>
            </div>

            <!-- Mobile Reset -->
            <div class="pt-1">
              <button
                class="btn btn-xs w-full gap-1.5 h-8"
                :class="hasActiveFilters ? 'btn-warning' : 'btn-outline btn-disabled'"
                @click="resetFilter"
                :disabled="!hasActiveFilters"
              >
                <RotateCcwIcon class="w-3 h-3" />
                <span class="text-xs">Reset All Filters</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>

  <script setup lang="ts">
  import { ref, computed, watch, onMounted } from 'vue';
  import {
    Search as SearchIcon,
    Filter as FilterIcon,
    ArrowUp as ArrowUpIcon,
    ArrowDown as ArrowDownIcon,
    RotateCcw as RotateCcwIcon,
    SlidersHorizontal,
    ChevronDown,
    ListFilter,
    Check,
    X
  } from 'lucide-vue-next';

  interface Column {
    key: string;
    label: string;
    sortable: boolean;
  }

  interface Props {
    columns: Column[];
    currentPerPage?: number;
    currentFilterBy?: string;
    currentOrderBy?: string;
    currentSearch?: string;
  }

  const props = defineProps<Props>();

  const emit = defineEmits<{
    'onPerPage': [value: number];
    'onOrderBy': [value: string];
    'onSearch': [value: string];
    'onFilter': [value: string];
    'onFilterReset': [value: { perPage: number; filterBy: string; orderBy: string; search: null }];
  }>();

  // State - Initialize with props values
  const isFilterOpen = ref(false);
  const perPage = ref<number>(props.currentPerPage ?? 10);
  const filterBy = ref<string>(props.currentFilterBy ?? '');
  const orderBy = ref<string>(props.currentOrderBy ?? 'asc');
  const search = ref<string>(props.currentSearch ?? '');
  const filterColumns = ref<{ label: string; value: string; }[]>([]);

  // Computed
  const selectedFilterLabel = computed(() => {
    const column = filterColumns.value.find(col => col.value === filterBy.value);
    return column?.label || '';
  });

  const hasActiveFilters = computed(() => {
    return search.value || filterBy.value || perPage.value !== 10 || orderBy.value !== 'asc';
  });

  // Methods
  const debounce = (func: Function, delay: number) => {
    let timeoutId: NodeJS.Timeout;
    return (...args: any[]) => {
      clearTimeout(timeoutId);
      timeoutId = setTimeout(() => func(...args), delay);
    };
  };

  const builderTableFilter = () => {
    const sortableColumns = props.columns.filter(col => col.sortable);
    filterColumns.value = sortableColumns.map(col => ({
      label: col.label,
      value: col.key
    }));
  };

  const clearSearch = () => {
    search.value = '';
    emit('onSearch', '');
  };

  const triggerSearch = () => {
    if (search.value) {
      debouncedSearch(search.value);
    }
  };

  const resetFilter = () => {
    perPage.value = 10;
    filterBy.value = '';
    orderBy.value = 'asc';
    search.value = '';
    isFilterOpen.value = false;

    emit('onFilterReset', {
      perPage: 10,
      filterBy: '',
      orderBy: 'asc',
      search: null
    });
  };

  // Debounced search
  const debouncedSearch = debounce((value: string) => {
    emit('onSearch', value);
  }, 300);

  // Watchers for local state changes (emit to parent)
  watch(perPage, (value) => emit('onPerPage', value));
  watch(orderBy, (value) => emit('onOrderBy', value));
  watch(filterBy, (value) => emit('onFilter', value));
  watch(search, (value) => {
    if (value) debouncedSearch(value);
  });

  // Watchers for prop changes (update local state)
  watch(() => props.currentPerPage, (newValue) => {
    if (newValue !== undefined && newValue !== perPage.value) {
      perPage.value = newValue;
    }
  });

  watch(() => props.currentFilterBy, (newValue) => {
    if (newValue !== undefined && newValue !== filterBy.value) {
      filterBy.value = newValue;
    }
  });

  watch(() => props.currentOrderBy, (newValue) => {
    if (newValue !== undefined && newValue !== orderBy.value) {
      orderBy.value = newValue;
    }
  });

  watch(() => props.currentSearch, (newValue) => {
    if (newValue !== search.value) {
      search.value = newValue ?? '';
    }
  });

  // Initialize
  onMounted(builderTableFilter);
  </script>

  <style scoped>
  /* DaisyUI compatible enhancements */
  .btn {
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  }

  .btn:hover {
    transform: translateY(-1px);
  }

  .btn:active {
    transform: translateY(0);
  }

  .input:focus {
    transform: translateY(-1px);
  }

  /* Enhanced dropdown styling */
  .dropdown-content {
    position: absolute;
    z-index: 9999 !important;
    animation: slideDown 0.2s ease-out;
    backdrop-filter: blur(8px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  }

  /* Ensure dropdown-end positioning works correctly */
  .dropdown-end .dropdown-content {
    right: 0;
    left: auto;
  }

  @keyframes slideDown {
    from {
      opacity: 0;
      transform: translateY(-8px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  /* Badge enhancements */
  .badge {
    transition: all 0.2s ease;
  }

  .badge:hover {
    transform: translateY(-1px);
  }

  /* Custom min-width utilities for better control layout */
  .min-w-\[5rem\] {
    min-width: 5rem;
  }

  .min-w-\[6rem\] {
    min-width: 6rem;
  }

  .min-w-\[7rem\] {
    min-width: 7rem;
  }

  .min-w-\[8rem\] {
    min-width: 8rem;
  }

  /* Enhanced z-index for better layering */
  .z-\[9999\] {
    z-index: 9999;
  }

  .z-\[30\] {
    z-index: 30;
  }

  /* Dropdown positioning improvements */
  .dropdown {
    position: relative;
  }

  .dropdown-content {
    position: absolute;
    z-index: 9999 !important;
    animation: slideDown 0.2s ease-out;
    backdrop-filter: blur(8px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  }

  /* Ensure dropdown-end positioning works correctly */
  .dropdown-end .dropdown-content {
    right: 0;
    left: auto;
  }

  /* Space utilities */
  .space-y-4 > * + * {
    margin-top: 1rem;
  }

  /* Responsive adjustments */
  @media (max-width: 1024px) {
    .btn-sm {
      height: 2.75rem;
      min-height: 2.75rem;
    }

    .input {
      height: 2.75rem;
    }
  }

  /* Active state for menu items */
  .menu li > .active {
    background-color: hsl(var(--p) / 0.1);
    color: hsl(var(--p));
  }

  /* Backdrop blur effect */
  .backdrop-blur-sm {
    backdrop-filter: blur(4px);
  }

  /* Enhanced focus states for accessibility */
  .btn:focus-visible,
  .input:focus-visible {
    outline: 2px solid hsl(var(--p));
    outline-offset: 2px;
  }

  /* Animation performance */
  .btn,
  .input,
  .dropdown-content,
  .badge {
    will-change: transform;
  }

  /* Disabled button states */
  .btn:disabled {
    transform: none;
  }

  .btn:disabled:hover {
    transform: none;
  }
  </style>
