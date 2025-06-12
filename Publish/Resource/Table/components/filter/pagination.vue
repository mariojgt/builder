<template>
  <div class="bg-gradient-to-r from-base-100 via-base-50 to-base-100 border-t border-base-300/50 px-6 py-4 shadow-inner">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
      <!-- Enhanced Pagination Info with Analytics -->
      <div class="text-sm text-base-content/70 order-2 lg:order-1">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
          <!-- Results Summary -->
          <div class="flex items-center gap-2 bg-base-200/50 px-4 py-2 rounded-lg border border-base-300/30">
            <div class="w-2 h-2 rounded-full bg-primary animate-pulse"></div>
            <p class="font-medium">
              Showing
              <span class="font-bold text-primary mx-1">{{ startItem }}</span>
              to
              <span class="font-bold text-primary mx-1">{{ endItem }}</span>
              of
              <span class="font-bold text-primary mx-1">{{ total }}</span>
              results
            </p>
          </div>

          <!-- Advanced Stats -->
          <div class="flex items-center gap-4 text-xs">
            <!-- Current Page Indicator -->
            <div class="flex items-center gap-1 bg-primary/10 px-2 py-1 rounded border border-primary/20">
              <List class="w-3 h-3 text-primary" />
              <span>Page {{ currentPage }} of {{ lastPage }}</span>
            </div>

            <!-- Items Per Page -->
            <div class="flex items-center gap-1 bg-info/10 px-2 py-1 rounded border border-info/20">
              <List class="w-3 h-3 text-info" />
              <span>{{ perPage }} per page</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Enhanced Pagination Controls -->
      <nav
        class="flex items-center justify-center order-1 lg:order-2"
        aria-label="Pagination Navigation"
        role="navigation"
      >
        <div class="flex items-center bg-base-200/50 rounded-xl p-1 border border-base-300/50 shadow-sm">

          <!-- First Page Button -->
          <button
            @click="goToFirstPage"
            :disabled="currentPage === 1"
            class="btn btn-ghost btn-sm px-3 rounded-lg transition-all duration-200 group"
            :class="[
              currentPage === 1
                ? 'cursor-not-allowed text-base-content/30'
                : 'text-base-content hover:text-primary hover:bg-primary/10'
            ]"
            :aria-label="`Go to first page`"
          >
            <ChevronsLeft class="w-4 h-4 group-hover:scale-110 transition-transform duration-200" />
            <span class="sr-only">First</span>
          </button>

          <!-- Previous Page Button -->
          <button
            @click="goToPreviousPage"
            :disabled="currentPage === 1"
            class="btn btn-ghost btn-sm px-3 rounded-lg transition-all duration-200 group"
            :class="[
              currentPage === 1
                ? 'cursor-not-allowed text-base-content/30'
                : 'text-base-content hover:text-primary hover:bg-primary/10'
            ]"
            :aria-label="`Go to previous page`"
          >
            <ChevronLeft class="w-4 h-4 group-hover:-translate-x-0.5 transition-transform duration-200" />
            <span class="sr-only">Previous</span>
          </button>

          <!-- Page Numbers Container -->
          <div class="flex items-center px-2">
            <TransitionGroup
              enter-active-class="transition-all duration-300 ease-out"
              enter-from-class="opacity-0 scale-90"
              enter-to-class="opacity-100 scale-100"
              leave-active-class="transition-all duration-200 ease-in"
              leave-from-class="opacity-100 scale-100"
              leave-to-class="opacity-0 scale-90"
              mode="out-in"
            >
              <template v-for="(pageNum, index) in pageRange" :key="`page-${pageNum}-${index}`">
                <!-- Page Number Button -->
                <button
                  v-if="typeof pageNum === 'number'"
                  @click="goToPage(pageNum)"
                  class="btn btn-sm min-w-[2.5rem] h-10 mx-0.5 transition-all duration-200 relative overflow-hidden group"
                  :class="[
                    pageNum === currentPage
                      ? 'btn-primary text-primary-content shadow-lg scale-110 z-10'
                      : 'btn-ghost text-base-content hover:text-primary hover:bg-primary/10 hover:scale-105'
                  ]"
                  :aria-label="`Go to page ${pageNum}`"
                  :aria-current="pageNum === currentPage ? 'page' : false"
                >
                  <!-- Active Page Background Animation -->
                  <div
                    v-if="pageNum === currentPage"
                    class="absolute inset-0 bg-gradient-to-r from-primary via-secondary to-primary animate-gradient-x"
                  ></div>

                  <!-- Page Number -->
                  <span class="relative z-10 font-semibold">{{ pageNum }}</span>

                  <!-- Hover Shimmer Effect -->
                  <div
                    v-if="pageNum !== currentPage"
                    class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-primary/20 to-transparent group-hover:translate-x-full transition-transform duration-500 ease-out"
                  ></div>
                </button>

                <!-- Ellipsis with Animation -->
                <div
                  v-else
                  class="flex items-center justify-center min-w-[2.5rem] h-10 mx-0.5"
                  :key="`ellipsis-${index}`"
                >
                  <div class="flex items-center gap-1">
                    <div class="w-1 h-1 rounded-full bg-base-content/40 animate-bounce" style="animation-delay: 0ms"></div>
                    <div class="w-1 h-1 rounded-full bg-base-content/40 animate-bounce" style="animation-delay: 150ms"></div>
                    <div class="w-1 h-1 rounded-full bg-base-content/40 animate-bounce" style="animation-delay: 300ms"></div>
                  </div>
                </div>
              </template>
            </TransitionGroup>
          </div>

          <!-- Next Page Button -->
          <button
            @click="goToNextPage"
            :disabled="currentPage === lastPage"
            class="btn btn-ghost btn-sm px-3 rounded-lg transition-all duration-200 group"
            :class="[
              currentPage === lastPage
                ? 'cursor-not-allowed text-base-content/30'
                : 'text-base-content hover:text-primary hover:bg-primary/10'
            ]"
            :aria-label="`Go to next page`"
          >
            <ChevronRight class="w-4 h-4 group-hover:translate-x-0.5 transition-transform duration-200" />
            <span class="sr-only">Next</span>
          </button>

          <!-- Last Page Button -->
          <button
            @click="goToLastPage"
            :disabled="currentPage === lastPage"
            class="btn btn-ghost btn-sm px-3 rounded-lg transition-all duration-200 group"
            :class="[
              currentPage === lastPage
                ? 'cursor-not-allowed text-base-content/30'
                : 'text-base-content hover:text-primary hover:bg-primary/10'
            ]"
            :aria-label="`Go to last page`"
          >
            <ChevronsRight class="w-4 h-4 group-hover:scale-110 transition-transform duration-200" />
            <span class="sr-only">Last</span>
          </button>
        </div>

        <!-- Page Jump Input (Desktop Only) -->
        <div class="hidden lg:flex items-center gap-2 ml-6 bg-base-200/50 px-3 py-2 rounded-lg border border-base-300/30">
          <span class="text-xs text-base-content/70 whitespace-nowrap">Jump to:</span>
          <input
            v-model="jumpToPage"
            @keyup.enter="handlePageJump"
            @blur="handlePageJump"
            type="number"
            :min="1"
            :max="lastPage"
            class="input input-xs w-16 text-center bg-base-100 border-base-300/50 focus:border-primary/50 focus:outline-none"
            :placeholder="currentPage.toString()"
          />
          <button
            @click="handlePageJump"
            class="btn btn-xs btn-primary"
            :disabled="!isValidJumpPage"
          >
            <ArrowRight class="w-3 h-3" />
          </button>
        </div>
      </nav>

      <!-- Quick Page Size Selector (Mobile Hidden) -->
      <div class="hidden sm:flex items-center gap-2 text-sm order-3">
        <span class="text-base-content/70 whitespace-nowrap">Per page:</span>
        <div class="dropdown dropdown-top dropdown-end">
          <label tabindex="0" class="btn btn-ghost btn-sm gap-1 bg-base-200/50 border border-base-300/30 hover:border-primary/30">
            <span class="font-semibold">{{ perPage }}</span>
            <ChevronDown class="w-3 h-3" />
          </label>
          <ul tabindex="0" class="dropdown-content z-20 menu p-2 shadow-xl bg-base-100 rounded-box w-32 border border-base-300/50">
            <li v-for="size in pageSizeOptions" :key="size">
              <button
                @click="changePageSize(size)"
                :class="{ 'active': size === perPage }"
                class="flex items-center justify-between"
              >
                <span>{{ size }}</span>
                <Check v-if="size === perPage" class="w-4 h-4 text-success" />
              </button>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Mobile Page Size Selector -->
    <div class="sm:hidden mt-4 pt-4 border-t border-base-300/30">
      <div class="flex items-center justify-between">
        <span class="text-sm text-base-content/70">Items per page:</span>
        <div class="flex gap-1">
          <button
            v-for="size in pageSizeOptions"
            :key="size"
            @click="changePageSize(size)"
            class="btn btn-xs"
            :class="[
              size === perPage
                ? 'btn-primary'
                : 'btn-ghost border border-base-300/30'
            ]"
          >
            {{ size }}
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State Overlay -->
    <Transition
      enter-active-class="transition-opacity duration-300"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="isLoading" class="absolute inset-0 bg-base-100/80 backdrop-blur-sm flex items-center justify-center z-30">
        <div class="flex items-center gap-3 bg-base-200 px-4 py-2 rounded-lg shadow-lg border border-base-300/50">
          <div class="loading loading-spinner loading-sm text-primary"></div>
          <span class="text-sm font-medium text-base-content">Loading...</span>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import {
  ChevronLeft,
  ChevronRight,
  ChevronsLeft,
  ChevronsRight,
  ChevronDown,
  ArrowRight,
  Check,
  List
} from 'lucide-vue-next';

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
  isLoading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  endpoint: '',
  isLoading: false,
  paginationInfo: () => ({
    currentPage: 1,
    lastPage: 1,
    perPage: 10,
    total: 0,
    links: []
  })
});

const emit = defineEmits<{
  'onPagination': [url: string];
  'onPageSizeChange': [size: number];
}>();

// Local state
const jumpToPage = ref<number | null>(null);
const pageSizeOptions = [10, 25, 50, 100];

// Computed properties
const currentPage = computed(() => props.paginationInfo.currentPage || 1);
const lastPage = computed(() => props.paginationInfo.lastPage || 1);
const perPage = computed(() => props.paginationInfo.perPage || 10);
const total = computed(() => props.paginationInfo.total || 0);

const startItem = computed(() => {
  if (total.value === 0) return 0;
  return Math.max(1, (currentPage.value - 1) * perPage.value + 1);
});

const endItem = computed(() => {
  return Math.min(currentPage.value * perPage.value, total.value);
});

const isValidJumpPage = computed(() => {
  return jumpToPage.value &&
         jumpToPage.value >= 1 &&
         jumpToPage.value <= lastPage.value &&
         jumpToPage.value !== currentPage.value;
});

// Generate smart page range with ellipsis
const pageRange = computed(() => {
  const current = currentPage.value;
  const total = lastPage.value;
  const delta = 2; // Number of pages to show around current page
  const range: (number | string)[] = [];

  // Always show first page
  range.push(1);

  // Calculate start and end of middle range
  const rangeStart = Math.max(2, current - delta);
  const rangeEnd = Math.min(total - 1, current + delta);

  // Add ellipsis after first page if needed
  if (rangeStart > 2) {
    range.push('...');
  }

  // Add middle range
  for (let i = rangeStart; i <= rangeEnd; i++) {
    range.push(i);
  }

  // Add ellipsis before last page if needed
  if (rangeEnd < total - 1) {
    range.push('...');
  }

  // Always show last page (if different from first)
  if (total > 1) {
    range.push(total);
  }

  // Remove duplicates while preserving order
  const uniqueRange: (number | string)[] = [];
  let lastAdded: number | string | null = null;

  for (const item of range) {
    if (item !== lastAdded) {
      uniqueRange.push(item);
      lastAdded = item;
    }
  }

  return uniqueRange;
});

// Navigation methods
const goToPage = (pageNumber: number) => {
  if (pageNumber === currentPage.value || pageNumber < 1 || pageNumber > lastPage.value) return;

  const url = `${props.endpoint}?page=${pageNumber}`;
  emit('onPagination', url);
};

const goToFirstPage = () => goToPage(1);
const goToLastPage = () => goToPage(lastPage.value);
const goToNextPage = () => goToPage(currentPage.value + 1);
const goToPreviousPage = () => goToPage(currentPage.value - 1);

const handlePageJump = () => {
  if (isValidJumpPage.value && jumpToPage.value) {
    goToPage(jumpToPage.value);
    jumpToPage.value = null;
  }
};

const changePageSize = (size: number) => {
  emit('onPageSizeChange', size);
};

// Clear jump input when page changes
watch(currentPage, () => {
  jumpToPage.value = null;
});
</script>

<style scoped>
/* Enhanced Gradient Animation */
@keyframes gradient-x {
  0%, 100% {
    background-position: 0% 50%;
  }
  50% {
    background-position: 100% 50%;
  }
}

.animate-gradient-x {
  background-size: 200% 200%;
  animation: gradient-x 3s ease infinite;
}

/* Enhanced Button Transitions */
.btn {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn:hover:not(:disabled) {
  transform: translateY(-1px);
}

.btn:active:not(:disabled) {
  transform: translateY(0) scale(0.98);
}

/* Shimmer Effect for Page Numbers */
.group:hover .group-hover\:translate-x-full {
  transition-delay: 0.1s;
}

/* Enhanced Loading Spinner */
.loading-spinner {
  filter: drop-shadow(0 0 8px hsl(var(--p) / 0.3));
}

/* Page Number Hover Effects */
.btn-sm {
  position: relative;
  overflow: hidden;
}

.btn-sm::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
  transition: left 0.5s ease;
}

.btn-sm:hover::before {
  left: 100%;
}

/* Enhanced Bounce Animation for Ellipsis */
@keyframes bounce {
  0%, 20%, 50%, 80%, 100% {
    transform: translateY(0);
  }
  40% {
    transform: translateY(-4px);
  }
  60% {
    transform: translateY(-2px);
  }
}

.animate-bounce {
  animation: bounce 1.5s ease-in-out infinite;
}

/* Enhanced Dropdown Animation */
.dropdown-content {
  transform-origin: top center;
  animation: dropdownSlide 0.2s ease-out;
}

@keyframes dropdownSlide {
  from {
    opacity: 0;
    transform: translateY(-10px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* Enhanced Input Focus */
.input:focus {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px hsl(var(--p) / 0.15);
}

/* Mobile Responsiveness */
@media (max-width: 640px) {
  .btn-sm {
    min-width: 2rem;
    padding-left: 0.5rem;
    padding-right: 0.5rem;
  }

  .px-6 {
    padding-left: 1rem;
    padding-right: 1rem;
  }
}

/* Enhanced Accessibility */
.btn:focus-visible {
  outline: 2px solid hsl(var(--p));
  outline-offset: 2px;
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
  .border-base-300\/50 {
    border-color: hsl(var(--bc));
  }

  .bg-base-200\/50 {
    background-color: hsl(var(--b2));
  }
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
  .animate-bounce,
  .animate-gradient-x,
  .animate-pulse {
    animation: none;
  }

  .transition-all,
  .transition-transform,
  .transition-opacity {
    transition: none;
  }
}

/* Print Styles */
@media print {
  .dropdown,
  .loading {
    display: none !important;
  }
}

/* Custom Scrollbar for Dropdown */
.dropdown-content::-webkit-scrollbar {
  width: 4px;
}

.dropdown-content::-webkit-scrollbar-track {
  background: hsl(var(--b3));
}

.dropdown-content::-webkit-scrollbar-thumb {
  background: hsl(var(--bc) / 0.3);
  border-radius: 2px;
}

.dropdown-content::-webkit-scrollbar-thumb:hover {
  background: hsl(var(--bc) / 0.5);
}
</style>
