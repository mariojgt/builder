<template>
    <div class="bg-base-100 rounded-xl shadow-lg transition-all duration-300 hover:shadow-xl">
      <div class="p-4">
        <!-- Mobile Filter Toggle -->
        <div class="md:hidden w-full mb-4">
          <button
            @click="isFilterOpen = !isFilterOpen"
            class="btn btn-ghost w-full justify-between group"
          >
            <span class="flex items-center gap-2">
              <SlidersHorizontal class="w-4 h-4 text-primary" />
              <span>{{ isFilterOpen ? 'Hide Filters' : 'Show Filters' }}</span>
            </span>
            <ChevronDown
              class="w-4 h-4 transition-transform duration-300"
              :class="{ 'rotate-180': isFilterOpen }"
            />
          </button>
        </div>

        <!-- Filter Content -->
        <div
          class="grid gap-4 transition-all duration-300"
          :class="[
            'md:grid-cols-[1fr_auto]',
            isFilterOpen ? 'grid-cols-1' : 'hidden md:grid'
          ]"
        >
          <!-- Left Side: Filters -->
          <div class="grid gap-3 sm:grid-cols-3">
            <!-- Per Page Dropdown -->
            <div class="dropdown dropdown-hover">
              <label tabindex="0" class="btn btn-ghost w-full justify-between gap-2 normal-case">
                <div class="flex items-center gap-2 text-base-content/70">
                  <ListFilter class="w-4 h-4" />
                  <span>{{ perPage }} per page</span>
                </div>
                <ChevronDown class="w-4 h-4" />
              </label>
              <ul tabindex="0" class="dropdown-content z-10 menu p-2 shadow-lg bg-base-200 rounded-box w-52">
                <li v-for="count in [10, 25, 50, 100]" :key="count">
                  <button
                    @click="perPage = count"
                    :class="{ 'active': perPage === count }"
                    class="flex items-center gap-2"
                  >
                    <Check v-if="perPage === count" class="w-4 h-4" />
                    <span>{{ count }} items</span>
                  </button>
                </li>
              </ul>
            </div>

            <!-- Filter By Dropdown -->
            <div class="dropdown dropdown-hover">
              <label tabindex="0" class="btn btn-ghost w-full justify-between gap-2 normal-case">
                <div class="flex items-center gap-2 text-base-content/70">
                  <FilterIcon class="w-4 h-4" />
                  <span class="truncate">{{ selectedFilterLabel || 'Filter by' }}</span>
                </div>
                <ChevronDown class="w-4 h-4" />
              </label>
              <ul tabindex="0" class="dropdown-content z-10 menu p-2 shadow-lg bg-base-200 rounded-box w-52">
                <li v-for="column in filterColumns" :key="column.value">
                  <button
                    @click="filterBy = column.value"
                    :class="{ 'active': filterBy === column.value }"
                    class="flex items-center gap-2"
                  >
                    <Check v-if="filterBy === column.value" class="w-4 h-4" />
                    <span>{{ column.label }}</span>
                  </button>
                </li>
              </ul>
            </div>

            <!-- Order By Dropdown -->
            <div class="dropdown dropdown-hover">
              <label tabindex="0" class="btn btn-ghost w-full justify-between gap-2 normal-case">
                <div class="flex items-center gap-2 text-base-content/70">
                  <component :is="orderBy === 'asc' ? ArrowUpIcon : ArrowDownIcon" class="w-4 h-4" />
                  <span>{{ orderBy === 'asc' ? 'Ascending' : 'Descending' }}</span>
                </div>
                <ChevronDown class="w-4 h-4" />
              </label>
              <ul tabindex="0" class="dropdown-content z-10 menu p-2 shadow-lg bg-base-200 rounded-box w-52">
                <li>
                  <button
                    @click="orderBy = 'asc'"
                    :class="{ 'active': orderBy === 'asc' }"
                    class="flex items-center gap-2"
                  >
                    <ArrowUpIcon class="w-4 h-4" />
                    <span>Ascending</span>
                  </button>
                </li>
                <li>
                  <button
                    @click="orderBy = 'desc'"
                    :class="{ 'active': orderBy === 'desc' }"
                    class="flex items-center gap-2"
                  >
                    <ArrowDownIcon class="w-4 h-4" />
                    <span>Descending</span>
                  </button>
                </li>
              </ul>
            </div>
          </div>

          <!-- Right Side: Search -->
          <div class="join w-full sm:w-auto">
            <div class="relative flex-1">
              <input
                v-model="search"
                type="text"
                placeholder="Search..."
                class="input input-bordered join-item w-full pl-10"
                :class="{ 'pr-9': search }"
                @keyup.enter="triggerSearch"
              />
              <SearchIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-base-content/40" />
              <button
                v-if="search"
                @click="clearSearch"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-base-content/40 hover:text-base-content transition-colors"
              >
                <X class="w-4 h-4" />
              </button>
            </div>

            <button
              class="btn btn-primary join-item"
              @click="triggerSearch"
            >
              <SearchIcon class="w-4 h-4" />
              <span class="hidden sm:inline">Search</span>
            </button>

            <button
              class="btn btn-secondary join-item group"
              @click="resetFilter"
              :disabled="!hasActiveFilters"
            >
              <RotateCcwIcon class="w-4 h-4 group-hover:rotate-180 transition-transform duration-300" />
              <span class="hidden sm:inline">Reset</span>
            </button>
          </div>
        </div>

        <!-- Active Filters Display -->
        <TransitionGroup
          enter-active-class="transition-all duration-300 ease-out"
          enter-from-class="opacity-0 -translate-y-2"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition-all duration-200 ease-in"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 -translate-y-2"
          class="flex flex-wrap gap-2 mt-4"
        >
          <div
            v-if="search"
            :key="'search'"
            class="badge badge-primary gap-2 p-3"
          >
            <span class="text-xs opacity-70">Search:</span>
            <span>{{ search }}</span>
            <button @click="clearSearch" class="hover:text-error">
              <X class="w-3 h-3" />
            </button>
          </div>

          <div
            v-if="filterBy"
            :key="'filter'"
            class="badge badge-secondary gap-2 p-3"
          >
            <span class="text-xs opacity-70">Filter:</span>
            <span>{{ selectedFilterLabel }}</span>
            <button @click="filterBy = ''" class="hover:text-error">
              <X class="w-3 h-3" />
            </button>
          </div>

          <div
            v-if="perPage !== 10"
            :key="'perPage'"
            class="badge badge-accent gap-2 p-3"
          >
            <span class="text-xs opacity-70">Show:</span>
            <span>{{ perPage }} items</span>
            <button @click="perPage = 10" class="hover:text-error">
              <X class="w-3 h-3" />
            </button>
          </div>
        </TransitionGroup>
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
  }

  const props = defineProps<Props>();

  const emit = defineEmits<{
    'onPerPage': [value: number];
    'onOrderBy': [value: string];
    'onSearch': [value: string];
    'onFilter': [value: string];
    'onFilterReset': [value: { perPage: number; filterBy: string; orderBy: string; search: null }];
  }>();

  // State
  const isFilterOpen = ref(false);
  const perPage = ref<number>(10);
  const filterBy = ref<string>('');
  const orderBy = ref<string>('asc');
  const search = ref<string>('');
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

  // Watchers
  watch(perPage, (value) => emit('onPerPage', value));
  watch(orderBy, (value) => emit('onOrderBy', value));
  watch(filterBy, (value) => emit('onFilter', value));
  watch(search, (value) => {
    if (value) debouncedSearch(value);
  });

  // Initialize
  onMounted(builderTableFilter);
  </script>

  <style scoped>
  .dropdown-content {
    transform-origin: top;
    animation: dropdownEnter 0.2s ease-out;
  }

  @keyframes dropdownEnter {
    from {
      opacity: 0;
      transform: translateY(-10px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .btn {
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  }

  .btn:active:not(:disabled) {
    transform: scale(0.95);
  }

  .input {
    transition: all 0.2s ease;
  }

  .input:focus {
    transform: translateY(-1px);
  }

  @media (max-width: 640px) {
    .join {
      display: grid;
      grid-template-columns: 1fr auto auto;
      gap: 0.5rem;
    }

    .join > * {
      width: 100%;
    }
  }
  </style>
