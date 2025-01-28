<template>
    <div class="
      px-5
      py-5
      bg-base-200
      border-t
      flex flex-col
      xs:flex-row
      items-center
      xs:justify-between
    ">
      <span class="xs:text-sm text-primary font-bold text-sm md:text-lg">
        Showing {{ currentPage }} to
        {{ Math.min(currentPage * perPage, total) }} of
        {{ total }} Entries
      </span>
      <div class="inline-flex mt-2 xs:mt-0">
        <div class="btn-group">
          <!-- Previous Button -->
          <button
            class="btn btn-md"
            :disabled="currentPage === 1"
            @click="goToPreviousPage"
          >
            Prev
          </button>

          <!-- Page Buttons -->
          <template v-for="(pageNum, index) in pageRange" :key="index">
            <button
              v-if="typeof pageNum === 'number'"
              class="btn btn-md"
              :class="{ 'btn-active': pageNum === currentPage }"
              @click="goToPage(pageNum)"
            >
              {{ pageNum }}
            </button>
            <span
              v-else-if="pageNum === '...'"
              class="btn btn-md btn-disabled"
            >
              ...
            </span>
          </template>

          <!-- Next Button -->
          <button
            class="btn btn-md"
            :disabled="currentPage === lastPage"
            @click="goToNextPage"
          >
            Next
          </button>
        </div>
      </div>
    </div>
  </template>

  <script setup>
  import { computed } from 'vue'

  const props = defineProps({
    paginationInfo: {
      type: Object,
      default: () => ({
        currentPage: 1,
        lastPage: 1,
        perPage: 10,
        total: 0,
        links: []
      })
    },
    endpoint: {
      type: String,
      default: ''
    }
  })

  const emit = defineEmits(['onPagiation'])

  // Computed properties with fallbacks
  const currentPage = computed(() => props.paginationInfo.currentPage || 1)
  const lastPage = computed(() => props.paginationInfo.lastPage || 1)
  const perPage = computed(() => props.paginationInfo.perPage || 10)
  const total = computed(() => props.paginationInfo.total || 0)

  // Generate page range with ellipsis
  const pageRange = computed(() => {
    const current = currentPage.value
    const total = lastPage.value
    const delta = 2
    const left = current - delta
    const right = current + delta + 1
    const range = []
    const rangeWithDots = []
    let l

    // Collect page numbers
    for (let i = 1; i <= total; i++) {
      if (
        i === 1 ||
        i === total ||
        (i >= left && i < right)
      ) {
        range.push(i)
      }
    }

    // Add dots for readability
    for (const i of range) {
      if (l) {
        if (i - l === 2) {
          rangeWithDots.push(l + 1)
        } else if (i - l !== 1) {
          rangeWithDots.push('...')
        }
      }
      rangeWithDots.push(i)
      l = i
    }

    return rangeWithDots
  })

  // Page navigation methods
  const goToPage = (pageNumber) => {
    // Construct URL based on page number if no links provided
    const url = props.endpoint + `?page=${pageNumber}`;
    emit('onPagiation', url)
  }

  const goToNextPage = () => {
    if (currentPage.value < lastPage.value) {
      goToPage(currentPage.value + 1)
    }
  }

  const goToPreviousPage = () => {
    if (currentPage.value > 1) {
      goToPage(currentPage.value - 1)
    }
  }
  </script>
