<template>
    <th
      v-for="(item, index) in tableDisplayInformation"
      :key="index"
      class="px-4 py-2"
    >
      <!-- Media Type with Expandable Images -->
      <div v-if="item.type === 'media'" class="flex items-center space-x-2">
        <div class="carousel rounded-box w-96 max-h-24 overflow-x-auto">
          <div
            v-for="(mediaItem, mediaIndex) in item.value"
            :key="mediaIndex"
            class="carousel-item w-1/2 mr-2 relative group"
          >
            <img
              :src="mediaItem.url?.default || mediaItem.url"
              :alt="`Media ${mediaIndex + 1}`"
              class="w-full h-20 object-cover rounded-md cursor-pointer transition-transform group-hover:scale-105"
              @click="openImageModal(mediaItem.url?.default || mediaItem.url)"
            />
            <!-- Expand Icon -->
            <div
              class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity bg-black/50 rounded-full p-1"
              @click="openImageModal(mediaItem.url?.default || mediaItem.url)"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5 text-white cursor-pointer"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fill-rule="evenodd"
                  d="M4 5a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V7a2 2 0 00-2-2h-1.586a1 1 0 01-.707-.293l-1.414-1.414A1 1 0 0011.586 3H8.414a1 1 0 00-.707.293L6.293 4.707A1 1 0 015.586 5H4zm6 9a3 3 0 100-6 3 3 0 000 6z"
                  clip-rule="evenodd"
                />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Other types remain the same as in previous component -->
      <div v-else-if="item.type === 'icon'"
        class="bg-base-100 rounded-lg flex justify-center items-center p-2"
        v-html="item.value"
      ></div>

      <div v-else-if="item.type === 'toggle'">
        <span
          :class="[
            'badge mt-3',
            item.value == 1 ? 'badge-success' : 'badge-error'
          ]"
        >
          {{ item.value == 1 ? 'Enable' : 'Disable' }}
        </span>
      </div>

      <div v-else-if="item.type === 'model_search'" class="flex flex-col space-y-1">
        <span
          v-for="(searchItem, searchIndex) in item.value"
          :key="searchIndex"
          class="badge badge-primary mt-1"
        >
          {{ searchIndex }} > {{ searchItem }}
        </span>
      </div>

      <div
        v-else
        class="truncate max-w-xs"
        :title="item.value"
        v-html="item.value"
      ></div>
    </th>

    <!-- Image Modal -->
    <Teleport to="body">
      <div
        v-if="expandedImage"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-75 p-4"
        @click="closeImageModal"
      >
        <div
          class="max-w-full max-h-full flex items-center justify-center relative"
          @click.stop
        >
          <img
            :src="expandedImage"
            alt="Expanded Image"
            class="max-w-full max-h-[90vh] object-contain rounded-lg shadow-2xl"
          />
          <button
            @click="closeImageModal"
            class="absolute top-4 right-4 bg-white/20 hover:bg-white/40 rounded-full p-2 transition-colors"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-6 w-6 text-white"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>
      </div>
    </Teleport>
  </template>

  <script setup lang="ts">
  import { computed, ref } from 'vue'

  // Define types for more robust type checking
  interface Column {
    label: string
    key: string
    type?: 'media' | 'icon' | 'toggle' | 'model_search' | string
  }

  interface MediaItem {
    url: {
      default?: string
      [key: string]: string
    }
  }

  // Reactive state for image expansion
  const expandedImage = ref<string | null>(null)

  // Open image modal
  const openImageModal = (imageUrl: string) => {
    expandedImage.value = imageUrl
  }

  // Close image modal
  const closeImageModal = () => {
    expandedImage.value = null
  }

  const props = defineProps({
    tableData: {
      type: Object as () => Record<string, any>,
      default: () => ({})
    },
    columns: {
      type: Array as () => Column[],
      default: () => []
    }
  })

  // Improved computed property with more robust type handling
  const tableDisplayInformation = computed(() => {
    return props.columns.map(column => ({
      label: column.label,
      type: column.type || 'default',
      value: (() => {
        const value = props.tableData[column.key]

        // Special handling for different types
        switch (column.type) {
          case 'media':
            // Ensure media items are always an array
            return Array.isArray(value) ? value : (value ? [value] : [])
          case 'model_search':
            // Handle both array and single item cases
            return Array.isArray(value) ? value : (value ? [value] : [])
          default:
            return value
        }
      })()
    }))
  })
  </script>

  <style scoped>
  /* Existing styles from previous component */
  .carousel-item {
    position: relative;
  }

  .carousel-item img {
    transition: transform 0.3s ease;
  }

  .carousel-item img:hover {
    transform: scale(1.05);
  }

  /* Modal transition */
  .v-enter-active,
  .v-leave-active {
    transition: opacity 0.3s ease;
  }

  .v-enter-from,
  .v-leave-to {
    opacity: 0;
  }
  </style>
