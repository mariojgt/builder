<template>
    <div class="field-renderer">
      <!-- Media Field -->
      <div v-if="type === 'media'" class="flex -space-x-2">
        <template v-if="Array.isArray(value) && value.length > 0">
          <div
            v-for="(item, index) in displayMedia"
            :key="index"
            class="relative group"
          >
            <img
              v-if="item?.url"
              :src="getMediaUrl(item)"
              :alt="item?.name || `Image ${index + 1}`"
              class="w-8 h-8 rounded-full border-2 border-base-100 object-cover transition-transform duration-200 group-hover:scale-110"
              :title="item?.name"
            />
          </div>
          <div
            v-if="remainingMediaCount > 0"
            class="w-8 h-8 rounded-full bg-base-300 border-2 border-base-100 flex items-center justify-center transition-transform hover:scale-110"
          >
            <span class="text-xs font-medium">+{{ remainingMediaCount }}</span>
          </div>
        </template>
        <div v-else class="text-base-content/50">—</div>
      </div>

      <!-- Boolean Field -->
      <div
        v-else-if="type === 'boolean'"
        :class="[
          'px-2 py-1 rounded-full text-xs font-medium inline-flex items-center gap-1.5 transition-colors duration-200',
          value ? 'bg-success/20 text-success hover:bg-success/30' : 'bg-error/20 text-error hover:bg-error/30'
        ]"
      >
        <span
          :class="[
            'w-2 h-2 rounded-full transition-transform duration-200',
            value ? 'bg-success animate-pulse' : 'bg-error'
          ]"
        ></span>
        {{ value ? 'Yes' : 'No' }}
      </div>

      <!-- Date Field -->
      <div
        v-else-if="type === 'date' || type === 'timestamp'"
        class="flex flex-col group cursor-default"
      >
        <span class="text-sm font-medium">{{ formatDate(value) }}</span>
        <span class="text-xs text-base-content/70 group-hover:text-base-content transition-colors duration-200">
          {{ formatRelativeDate(value) }}
        </span>
      </div>

      <!-- Number Field -->
      <div
        v-else-if="type === 'number'"
        class="font-mono text-base-content/90 hover:text-base-content transition-colors duration-200"
      >
        {{ formatNumber(value) }}
      </div>

      <!-- Model Search Field -->
      <div v-else-if="type === 'model_search'" class="flex flex-wrap gap-1.5">
        <template v-if="isValidModelSearch">
          <div
            v-for="(val, key) in value"
            :key="key"
            class="badge badge-sm transition-all duration-200 hover:scale-105"
            :class="getModelSearchBadgeColor(key)"
          >
            <span class="opacity-60 text-[10px] uppercase mr-1">{{ key }}:</span>
            <span class="font-medium">{{ val }}</span>
          </div>
        </template>
        <span v-else class="text-base-content/50">—</span>
      </div>

      <!-- Price Field -->
      <div
        v-else-if="type === 'price'"
        class="font-mono font-medium text-base-content/90 hover:text-base-content transition-colors duration-200"
      >
        {{ formatPrice(value) }}
      </div>

      <!-- Icon Field -->
      <div v-else-if="type === 'icon'" class="flex items-center justify-center">
        <div
          v-if="value"
          class="w-8 h-8 flex items-center justify-center transition-transform hover:scale-110"
          v-html="value"
        ></div>
        <span v-else class="text-base-content/50">—</span>
      </div>

      <!-- Default Text Field -->
      <div
        v-else
        class="relative group"
        :class="{ 'cursor-help': showTooltip }"
      >
        <span
          :class="[
            'transition-colors duration-200',
            !value ? 'text-base-content/50' : 'text-base-content/90 group-hover:text-base-content',
            truncateText ? 'truncate block' : '',
            options.enhanced ? 'text-base font-medium' : ''
          ]"
        >
          {{ formatDefaultValue(value) }}
        </span>

        <!-- Tooltip for long text -->
        <div
          v-if="showTooltip"
          class="absolute z-50 bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 pointer-events-none"
        >
          <div
            class="bg-base-300 text-base-content px-3 py-2 rounded-lg shadow-lg max-w-xs
                   opacity-0 invisible -translate-y-2
                   group-hover:opacity-100 group-hover:visible group-hover:translate-y-0
                   transition-all duration-200"
          >
            {{ value }}
            <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-2 h-2 bg-base-300 rotate-45"></div>
          </div>
        </div>
      </div>
    </div>
  </template>

  <script setup lang="ts">
  import { computed } from 'vue';
  import { format, formatDistanceToNow } from 'date-fns';

  interface MediaItem {
    url?: string | { default: string };
    name?: string;
  }

  interface Props {
    value: any;
    type?: string;
    options?: Record<string, any>;
  }

  const props = withDefaults(defineProps<Props>(), {
    type: 'text',
    options: () => ({})
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

  const truncateText = computed(() => {
    return props.options?.truncate ?? true;
  });

  // Helper functions
  function getMediaUrl(item: MediaItem): string {
    if (!item?.url) return '';
    return typeof item.url === 'string' ? item.url : item.url?.default || '';
  }

  function formatDate(date: string | null): string {
    if (!date) return '—';
    try {
      return format(new Date(date), 'MMM d, yyyy');
    } catch (e) {
      return '—';
    }
  }

  function formatRelativeDate(date: string | null): string {
    if (!date) return '';
    try {
      return formatDistanceToNow(new Date(date), { addSuffix: true });
    } catch (e) {
      return '';
    }
  }

  function formatNumber(num: number | null): string {
    if (num === null || num === undefined) return '—';
    try {
      return new Intl.NumberFormat().format(num);
    } catch (e) {
      return '—';
    }
  }

  function formatPrice(price: number | null): string {
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

  function getModelSearchBadgeColor(key: string): string {
    const colors: Record<string, string> = {
      status: 'badge-info',
      type: 'badge-warning',
      category: 'badge-success',
      priority: 'badge-error',
      label: 'badge-primary'
    };
    return colors[key.toLowerCase()] || 'badge-neutral';
  }

  function formatDefaultValue(value: any): string {
    if (value === null || value === undefined || value === '') return '—';
    return String(value);
  }
  </script>

  <style scoped>
    @reference "@css/app.css";
  .field-renderer {
    @apply min-w-0;
  }

  @keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
  }

  .animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
  }
  </style>
