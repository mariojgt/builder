<template>
    <div class="field-renderer">
      <!-- Media Field -->
      <div v-if="type === 'media'" class="flex -space-x-2">
        <template v-if="Array.isArray(value) && value.length > 0">
          <div v-for="(item, index) in displayMedia" :key="index" class="relative">
            <img
              v-if="item?.url"
              :src="getMediaUrl(item)"
              :alt="item?.name || `Image ${index + 1}`"
              class="w-8 h-8 rounded-full border-2 border-base-100 object-cover"
            />
          </div>
          <div v-if="remainingMediaCount > 0"
            class="w-8 h-8 rounded-full bg-base-300 border-2 border-base-100 flex items-center justify-center"
          >
            <span class="text-xs">+{{ remainingMediaCount }}</span>
          </div>
        </template>
        <div v-else class="text-base-content/50">—</div>
      </div>

      <!-- Boolean Field -->
      <div v-else-if="type === 'boolean'"
        :class="[
          'px-2 py-1 rounded-full text-xs font-medium inline-flex items-center gap-1',
          value ? 'bg-success/10 text-success' : 'bg-error/10 text-error'
        ]"
      >
        <div :class="[
          'w-1.5 h-1.5 rounded-full',
          value ? 'bg-success' : 'bg-error'
        ]"></div>
        {{ value ? 'Yes' : 'No' }}
      </div>

      <!-- Date Field -->
      <div v-else-if="type === 'date' || type === 'timestamp'" class="flex flex-col">
        <span class="text-sm">{{ formatDate(value) }}</span>
        <span class="text-xs opacity-70">{{ formatRelativeDate(value) }}</span>
      </div>

      <!-- Number Field -->
      <div v-else-if="type === 'number'" class="font-mono">
        {{ formatNumber(value) }}
      </div>

      <!-- Model Search Field -->
      <div v-else-if="type === 'model_search'" class="flex flex-wrap gap-1">
        <template v-if="isValidModelSearch">
          <div v-for="(val, key) in value" :key="key"
            class="badge badge-sm"
            :class="getModelSearchBadgeColor(key)"
          >
            <span class="opacity-60 text-[10px] uppercase mr-1">{{ key }}:</span>
            <span class="font-medium">{{ val }}</span>
          </div>
        </template>
        <span v-else class="text-base-content/50">—</span>
      </div>

      <!-- Price Field -->
      <div v-else-if="type === 'price'" class="font-mono">
        {{ formatPrice(value) }}
      </div>

      <!-- Icon Field -->
      <div v-else-if="type === 'icon'" class="flex items-center justify-center">
        <div v-if="value" class="w-8 h-8 flex items-center justify-center" v-html="value"></div>
        <span v-else class="text-base-content/50">—</span>
      </div>

      <!-- Default Text Field -->
      <div v-else class="relative group">
        <span :class="{ 'text-base-content/50': !value }">
          {{ formatDefaultValue(value) }}
        </span>

        <!-- Tooltip for long text -->
        <div v-if="showTooltip"
          class="absolute z-50 bottom-full left-1/2 -translate-x-1/2 mb-2 opacity-0 invisible
                 group-hover:opacity-100 group-hover:visible transition-all duration-200"
        >
          <div class="bg-base-300 text-base-content px-3 py-2 rounded shadow-lg max-w-xs">
            {{ value }}
          </div>
        </div>
      </div>
    </div>
  </template>

  <script setup>
  import { computed } from 'vue';
  import { format, formatDistanceToNow } from 'date-fns';

  const props = defineProps({
    value: {
      type: [String, Number, Boolean, Array, Object],
      default: null
    },
    type: {
      type: String,
      default: 'text'
    },
    options: {
      type: Object,
      default: () => ({})
    }
  });

  // Computed properties
  const displayMedia = computed(() => {
    if (!Array.isArray(props.value)) return [];
    return props.value.slice(0, 3);
  });

  const remainingMediaCount = computed(() => {
    if (!Array.isArray(props.value)) return 0;
    return Math.max(0, props.value.length - 3);
  });

  const isValidModelSearch = computed(() => {
    return props.value && typeof props.value === 'object' && !Array.isArray(props.value);
  });

  const showTooltip = computed(() => {
    return props.value && typeof props.value === 'string' && props.value.length > 30;
  });

  // Helper functions
  function getMediaUrl(item) {
    if (!item?.url) return '';
    return typeof item.url === 'string' ? item.url : item.url?.default || '';
  }

  function formatDate(date) {
    if (!date) return '—';
    try {
      return format(new Date(date), 'MMM d, yyyy');
    } catch (e) {
      return '—';
    }
  }

  function formatRelativeDate(date) {
    if (!date) return '';
    try {
      return formatDistanceToNow(new Date(date), { addSuffix: true });
    } catch (e) {
      return '';
    }
  }

  function formatNumber(num) {
    if (num === null || num === undefined) return '—';
    try {
      return new Intl.NumberFormat().format(num);
    } catch (e) {
      return '—';
    }
  }

  function formatPrice(price) {
    if (price === null || price === undefined) return '—';
    try {
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
      }).format(price);
    } catch (e) {
      return '—';
    }
  }

  function getModelSearchBadgeColor(key) {
    const colors = {
      status: 'badge-info',
      type: 'badge-warning',
      category: 'badge-success',
      priority: 'badge-error',
      label: 'badge-primary'
    };
    return colors[key.toLowerCase()] || 'badge-neutral';
  }

  function formatDefaultValue(value) {
    if (value === null || value === undefined || value === '') return '—';
    return String(value);
  }
  </script>

  <style scoped>
  .field-renderer {
    min-width: 0; /* Ensures proper text truncation */
  }
  </style>
