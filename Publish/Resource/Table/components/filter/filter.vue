<template>
    <div class="bg-base-100 shadow-sm rounded-lg p-4 mb-4">
      <div class="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0 md:space-x-4">
        <!-- Left Side: Per Page and Filter Options -->
        <div class="flex flex-wrap gap-3 items-center w-full md:w-auto">
          <!-- Per Page Selector -->
          <div class="relative w-full md:w-44">
            <select
              class="select select-primary w-full"
              v-model="perPage"
              aria-label="Items per page"
            >
              <option disabled value="">Per page</option>
              <option value="10">10 Items</option>
              <option value="25">25 Items</option>
              <option value="50">50 Items</option>
              <option value="100">100 Items</option>
            </select>
            <ListIcon class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none text-primary" />
          </div>

          <!-- Filter By Selector -->
          <div class="relative w-full md:w-44">
            <select
              class="select select-primary w-full"
              v-model="filterBy"
              aria-label="Filter by column"
            >
              <option disabled value="">Filter By</option>
              <option
                v-for="(item, index) in filterColumns"
                :key="index"
                :value="item.value"
              >
                {{ item.label }}
              </option>
            </select>
            <FilterIcon class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none text-primary" />
          </div>

          <!-- Order By Selector -->
          <div class="relative w-full md:w-44">
            <select
              class="select select-primary w-full"
              v-model="orderBy"
              aria-label="Sort order"
            >
              <option disabled value="">Sort Order</option>
              <option value="asc">Ascending</option>
              <option value="desc">Descending</option>
            </select>
            <component
              :is="orderBy === 'asc' ? ArrowUpIcon : ArrowDownIcon"
              class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none text-primary"
            />
          </div>
        </div>

        <!-- Search and Reset Section -->
        <div class="flex w-full md:w-auto">
          <div class="join w-full">
            <div class="relative w-full">
              <input
                class="input input-bordered join-item w-full pr-10"
                v-model="search"
                placeholder="Search..."
                @keyup.enter="triggerSearch"
                aria-label="Search input"
              />
              <SearchIcon class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-base-content/40 pointer-events-none" />
            </div>

            <button
              class="btn btn-primary join-item"
              @click="triggerSearch"
              aria-label="Search"
            >
              <SearchIcon class="w-5 h-5" />
            </button>

            <button
              class="btn btn-secondary join-item"
              @click="resetFilter"
              aria-label="Reset filters"
            >
              <RotateCcwIcon class="w-5 h-5" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </template>

  <script setup lang="ts">
  import { ref, onMounted, watch } from 'vue';
  import {
    Search as SearchIcon,
    List as ListIcon,
    Filter as FilterIcon,
    ArrowUp as ArrowUpIcon,
    ArrowDown as ArrowDownIcon,
    RotateCcw as RotateCcwIcon
  } from 'lucide-vue-next';

  interface Column {
    key: string;
    label: string;
    sortable: boolean;
  }

  interface FilterColumn {
    label: string;
    value: string;
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

  // Reactive state
  const perPage = ref<number>(10);
  const filterBy = ref<string>('');
  const orderBy = ref<string>('');
  const search = ref<string>('');
  const filterColumns = ref<FilterColumn[]>([]);

  // Custom debounce function
  const debounce = (func: Function, delay: number) => {
    let timeoutId: NodeJS.Timeout;
    return (...args: any[]) => {
      if (timeoutId) {
        clearTimeout(timeoutId);
      }
      timeoutId = setTimeout(() => {
        func(...args);
      }, delay);
    };
  };

  // Generate filter columns from sortable columns
  const builderTableFilter = () => {
    const sortableColumns = props.columns.filter(col => col.sortable);

    if (sortableColumns.length > 0) {
      filterBy.value = sortableColumns[0].key;
    }

    filterColumns.value = sortableColumns.map(col => ({
      label: col.label,
      value: col.key
    }));
  };

  // Debounced search
  const debouncedSearch = debounce((value: string) => {
    emit('onSearch', value);
  }, 500);

  // Watchers
  watch(perPage, (value) => {
    emit('onPerPage', value);
  });

  watch(orderBy, (value) => {
    emit('onOrderBy', value);
  });

  watch(filterBy, (value) => {
    emit('onFilter', value);
  });

  watch(search, (value) => {
    if (value && value.length > 0) {
      debouncedSearch(value);
    }
  });

  // Trigger search method
  const triggerSearch = () => {
    if (search.value && search.value.length > 0) {
      emit('onSearch', search.value);
    }
  };

  // Reset filter method
  const resetFilter = () => {
    perPage.value = 10;
    filterBy.value = filterColumns.value[0]?.value || 'id';
    orderBy.value = 'asc';
    search.value = '';

    emit('onFilterReset', {
      perPage: perPage.value,
      filterBy: filterBy.value,
      orderBy: orderBy.value,
      search: null
    });
  };

  // Initialize filter columns on component mount
  onMounted(builderTableFilter);
  </script>

  <style scoped>
  /* Responsive adjustments */
  @media (max-width: 768px) {
    .join {
      flex-direction: column;
      width: 100%;
    }

    .join .input,
    .join .btn {
      width: 100%;
      margin-bottom: 0.5rem;
    }
  }

  /* Subtle animations */
  .select,
  .input,
  .btn {
    transition: all 0.2s ease-in-out;
  }

  .select:focus,
  .input:focus,
  .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  }
  </style>
