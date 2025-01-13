<template>
    <div class="bg-base-100 shadow-sm rounded-lg p-4 mb-4">
      <div class="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0 md:space-x-4">
        <!-- Left Side: Per Page and Filter Options -->
        <div class="flex flex-wrap gap-3 items-center w-full md:w-auto">
          <!-- Per Page Selector -->
          <div class="relative w-full md:w-auto">
            <select
              class="select select-primary w-full md:w-44"
              v-model="perPage"
              aria-label="Items per page"
            >
              <option disabled selected>Per page</option>
              <option :value="10">10 Items</option>
              <option :value="25">25 Items</option>
              <option :value="50">50 Items</option>
              <option :value="100">100 Items</option>
            </select>
          </div>

          <!-- Filter By Selector -->
          <div class="relative w-full md:w-auto">
            <select
              class="select select-primary w-full md:w-44"
              v-model="filterBy"
              aria-label="Filter by column"
            >
              <option disabled selected>Filter By</option>
              <option
                v-for="(item, index) in filterColumns"
                :key="index"
                :value="item.value"
              >
                {{ item.label }}
              </option>
            </select>
          </div>

          <!-- Order By Selector -->
          <div class="relative w-full md:w-auto">
            <select
              class="select select-primary w-full md:w-44"
              v-model="orderBy"
              aria-label="Sort order"
            >
              <option disabled selected>Sort Order</option>
              <option value="asc">
                <span class="flex items-center">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                  </svg>
                  Ascending
                </span>
              </option>
              <option value="desc">
                <span class="flex items-center">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 17a1 1 0 01-.707-.293l-3-3a1 1 0 011.414-1.414L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3A1 1 0 0110 17zm-3.707-9.293a1 1 0 011.414 0L10 5.414l2.293 2.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 000 1.414z" clip-rule="evenodd" />
                  </svg>
                  Descending
                </span>
              </option>
            </select>
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
              <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                </svg>
              </div>
            </div>

            <button
              class="btn btn-primary join-item px-4"
              @click="triggerSearch"
              aria-label="Search"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
              </svg>
            </button>

            <button
              class="btn btn-secondary join-item px-4"
              @click="resetFilter"
              aria-label="Reset filters"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>
  </template>

  <script setup>
  import { ref, onMounted, watch } from 'vue'

  const props = defineProps({
    columns: {
      type: Array,
      default: () => []
    }
  })

  const emit = defineEmits([
    'onPerPage',
    'onOrderBy',
    'onSearch',
    'onFilter',
    'onFilterReset'
  ])

  // Reactive state
  const perPage = ref(10)
  const filterBy = ref('id')
  const orderBy = ref('asc')
  const search = ref('')

  // Filter columns generation
  const filterColumns = ref([])

  // Custom debounce function
  const debounce = (func, delay) => {
    let timeoutId
    return (...args) => {
      if (timeoutId) {
        clearTimeout(timeoutId)
      }
      timeoutId = setTimeout(() => {
        func(...args)
      }, delay)
    }
  }

  // Generate filter columns from sortable columns
  const builderTableFilter = () => {
    const sortableColumns = props.columns.filter(col => col.sortable)

    // If sortable columns exist, set initial filterBy to first sortable column
    if (sortableColumns.length > 0) {
      filterBy.value = sortableColumns[0].key
    }

    // Map sortable columns to filter columns
    filterColumns.value = sortableColumns.map(col => ({
      label: col.label,
      value: col.key
    }))
  }

  // Debounced search with native JavaScript
  const debouncedSearch = debounce((value) => {
    emit('onSearch', value)
  }, 500)

  // Watchers
  watch(perPage, (value) => {
    emit('onPerPage', value)
  })

  watch(orderBy, (value) => {
    emit('onOrderBy', value)
  })

  watch(filterBy, (value) => {
    emit('onFilter', value)
  })

  watch(search, (value) => {
    if (value && value.length > 0) {
      debouncedSearch(value)
    }
  })

  // Trigger search method
  const triggerSearch = () => {
    if (search.value && search.value.length > 0) {
      emit('onSearch', search.value)
    }
  }

  // Reset filter method
  const resetFilter = () => {
    perPage.value = 10
    filterBy.value = filterColumns.value[0]?.value || 'id'
    orderBy.value = 'asc'
    search.value = ''

    emit('onFilterReset', {
      perPage: perPage.value,
      filterBy: filterBy.value,
      orderBy: orderBy.value,
      search: null
    })
  }

  // Initialize filter columns on component mount
  onMounted(builderTableFilter)
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
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  }
  </style>
