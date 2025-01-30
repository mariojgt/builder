<template>
    <div class="bg-base-100 border-t px-4 py-3 sm:px-6">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <!-- Pagination Info -->
        <div class="text-sm text-base-content/70">
          <p class="font-medium">
            Showing
            <span class="font-semibold text-primary">{{ Math.max(1, (currentPage - 1) * perPage + 1) }}</span>
            to
            <span class="font-semibold text-primary">{{ Math.min(currentPage * perPage, total) }}</span>
            of
            <span class="font-semibold text-primary">{{ total }}</span>
            results
          </p>
        </div>

        <!-- Pagination Controls -->
        <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
          <!-- Previous Page -->
          <button
            @click="goToPreviousPage"
            :disabled="currentPage === 1"
            class="relative inline-flex items-center rounded-l-md px-3 py-2 text-sm font-medium ring-1 ring-inset ring-base-300 hover:bg-base-200 focus:z-20 focus:outline-offset-0 transition-colors"
            :class="[
              currentPage === 1
                ? 'cursor-not-allowed text-base-content/30'
                : 'text-base-content hover:text-primary'
            ]"
          >
            <ChevronLeft class="h-4 w-4" />
            <span class="sr-only">Previous</span>
          </button>

          <!-- Page Numbers -->
          <template v-for="(pageNum, index) in pageRange" :key="index">
            <!-- Page Number Button -->
            <button
              v-if="typeof pageNum === 'number'"
              @click="goToPage(pageNum)"
              :class="[
                pageNum === currentPage
                  ? 'z-10 bg-primary text-primary-content focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary'
                  : 'text-base-content hover:bg-base-200',
                'relative inline-flex items-center px-4 py-2 text-sm font-medium ring-1 ring-inset ring-base-300 focus:z-20 focus:outline-offset-0 transition-colors'
              ]"
            >
              {{ pageNum }}
            </button>

            <!-- Ellipsis -->
            <span
              v-else
              class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-base-content ring-1 ring-inset ring-base-300 focus:outline-offset-0"
            >
              ...
            </span>
          </template>

          <!-- Next Page -->
          <button
            @click="goToNextPage"
            :disabled="currentPage === lastPage"
            class="relative inline-flex items-center rounded-r-md px-3 py-2 text-sm font-medium ring-1 ring-inset ring-base-300 hover:bg-base-200 focus:z-20 focus:outline-offset-0 transition-colors"
            :class="[
              currentPage === lastPage
                ? 'cursor-not-allowed text-base-content/30'
                : 'text-base-content hover:text-primary'
            ]"
          >
            <ChevronRight class="h-4 w-4" />
            <span class="sr-only">Next</span>
          </button>
        </nav>
      </div>
    </div>
  </template>

  <script setup lang="ts">
  import { computed } from 'vue';
  import { ChevronLeft, ChevronRight } from 'lucide-vue-next';

  interface PaginationInfo {
    currentPage: number;
    lastPage: number;
    perPage: number;
    total: number;
    links: any[];
  }

  interface Props {
    paginationInfo: PaginationInfo;
    endpoint?: string;
  }

  const props = withDefaults(defineProps<Props>(), {
    endpoint: '',
    paginationInfo: () => ({
      currentPage: 1,
      lastPage: 1,
      perPage: 10,
      total: 0,
      links: []
    })
  });

  const emit = defineEmits<{
    'onPagiation': [url: string];
  }>();

  // Computed properties
  const currentPage = computed(() => props.paginationInfo.currentPage || 1);
  const lastPage = computed(() => props.paginationInfo.lastPage || 1);
  const perPage = computed(() => props.paginationInfo.perPage || 10);
  const total = computed(() => props.paginationInfo.total || 0);

  // Generate page range with ellipsis
  const pageRange = computed(() => {
    const current = currentPage.value;
    const total = lastPage.value;
    const delta = 2;
    const range: (number | string)[] = [];
    const rangeWithDots: (number | string)[] = [];
    let l: number | undefined;

    // Always show first and last pages
    range.push(1);

    // Add pages around current page
    for (let i = Math.max(2, current - delta); i <= Math.min(total - 1, current + delta); i++) {
      range.push(i);
    }

    // Add last page if not already included
    if (total > 1) {
      range.push(total);
    }

    // Add dots where needed
    let prev: number | undefined;
    for (const i of range) {
      if (typeof prev === 'number') {
        if (i - prev === 2) {
          rangeWithDots.push(prev + 1);
        } else if (i - prev !== 1) {
          rangeWithDots.push('...');
        }
      }
      rangeWithDots.push(i);
      prev = typeof i === 'number' ? i : prev;
    }

    return rangeWithDots;
  });

  // Navigation methods
  const goToPage = (pageNumber: number) => {
    if (pageNumber === currentPage.value) return;
    const url = props.endpoint + `?page=${pageNumber}`;
    emit('onPagiation', url);
  };

  const goToNextPage = () => {
    if (currentPage.value < lastPage.value) {
      goToPage(currentPage.value + 1);
    }
  };

  const goToPreviousPage = () => {
    if (currentPage.value > 1) {
      goToPage(currentPage.value - 1);
    }
  };
  </script>
