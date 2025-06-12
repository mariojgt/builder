<template>
  <div class="field-renderer">
    <!-- Media Field with enhanced styling -->
    <div v-if="type === 'media'" class="flex items-center -space-x-2">
      <template v-if="Array.isArray(value) && value.length > 0">
        <div
          v-for="(item, index) in displayMedia"
          :key="index"
          class="relative group tooltip"
          :data-tip="item?.name || `Image ${index + 1}`"
        >
          <div class="avatar">
            <div class="w-10 h-10 rounded-full ring-2 ring-base-100 ring-offset-2 ring-offset-base-200">
              <img
                v-if="item?.url"
                :src="getMediaUrl(item)"
                :alt="item?.name || `Image ${index + 1}`"
                class="object-cover transition-all duration-300 group-hover:scale-110"
                loading="lazy"
              />
              <div v-else class="w-full h-full bg-gradient-to-br from-base-300 to-base-400 flex items-center justify-center">
                <svg class="w-5 h-5 text-base-content/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
              </div>
            </div>
          </div>
        </div>
        <div
          v-if="remainingMediaCount > 0"
          class="avatar placeholder tooltip"
          :data-tip="`${remainingMediaCount} more items`"
        >
          <div class="w-10 h-10 rounded-full bg-primary/20 text-primary ring-2 ring-base-100 ring-offset-2 ring-offset-base-200">
            <span class="text-xs font-bold">+{{ remainingMediaCount }}</span>
          </div>
        </div>
      </template>
      <div v-else class="text-base-content/50 italic">No media</div>
    </div>

    <!-- Enhanced Boolean Field -->
    <div v-else-if="type === 'boolean'" class="inline-flex">
      <div
        :class="[
          'badge gap-2 transition-all duration-300 hover:scale-105',
          value
            ? 'badge-success badge-outline hover:badge-success'
            : 'badge-error badge-outline hover:badge-error'
        ]"
      >
        <div
          :class="[
            'w-2 h-2 rounded-full',
            value ? 'bg-success animate-pulse' : 'bg-error'
          ]"
        ></div>
        <span class="font-medium">{{ value ? 'Active' : 'Inactive' }}</span>
      </div>
    </div>

    <!-- Enhanced Date Field -->
    <div
      v-else-if="type === 'date' || type === 'timestamp'"
      class="flex flex-col space-y-1 group cursor-default"
    >
      <div class="flex items-center gap-2">
        <div class="w-4 h-4 rounded bg-primary/20 flex items-center justify-center">
          <svg class="w-3 h-3 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </div>
        <span class="font-semibold text-base-content">{{ formatDate(value) }}</span>
      </div>
      <span class="text-xs text-base-content/60 pl-6 group-hover:text-base-content/80 transition-colors duration-200">
        {{ formatRelativeDate(value) }}
      </span>
    </div>

    <!-- Enhanced Number Field -->
    <div
      v-else-if="type === 'number'"
      class="font-mono font-medium text-base-content bg-base-200/50 px-3 py-1 rounded-lg border border-base-300/50 hover:border-primary/30 hover:bg-primary/5 transition-all duration-200"
    >
      {{ formatNumber(value) }}
    </div>

    <!-- Enhanced Model Search Field -->
    <div v-else-if="type === 'model_search'" class="flex flex-wrap gap-1.5">
      <template v-if="isValidModelSearch">
        <div
          v-for="(val, key) in value"
          :key="key"
          class="badge badge-sm gap-1.5 transition-all duration-200 hover:scale-105 cursor-default"
          :class="getModelSearchBadgeColor(key)"
        >
          <span class="opacity-70 text-[10px] uppercase font-bold tracking-wide">{{ key }}:</span>
          <span class="font-semibold">{{ val }}</span>
        </div>
      </template>
      <span v-else class="text-base-content/50 italic">No data</span>
    </div>

    <!-- Enhanced Price Field -->
    <div
      v-else-if="type === 'price'"
      class="flex items-center gap-2"
    >
      <div class="w-5 h-5 rounded bg-success/20 flex items-center justify-center">
        <svg class="w-3 h-3 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
        </svg>
      </div>
      <span class="font-mono font-bold text-lg text-success">{{ formatPrice(value) }}</span>
    </div>

    <!-- Enhanced Icon Field -->
    <div v-else-if="type === 'icon'" class="flex items-center justify-center">
      <div
        v-if="value"
        class="w-10 h-10 flex items-center justify-center bg-base-200 rounded-lg border border-base-300 hover:border-primary/50 hover:bg-primary/10 transition-all duration-200 hover:scale-110"
        v-html="value"
      ></div>
      <div v-else class="w-10 h-10 flex items-center justify-center bg-base-200/50 rounded-lg border border-dashed border-base-300">
        <svg class="w-5 h-5 text-base-content/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2m-9 0h10m-10 0l1 18h8l1-18" />
        </svg>
      </div>
    </div>

    <!-- Enhanced Default Text Field -->
    <div v-else class="relative group">
      <div
        :class="[
          'transition-all duration-200 min-h-[1.5rem] flex items-center',
          !value ? 'text-base-content/50' : 'text-base-content group-hover:text-base-content',
          truncateText ? 'truncate' : '',
          options.enhanced ? 'text-base font-semibold bg-base-200/30 px-2 py-1 rounded border border-base-300/30' : '',
          showTooltip ? 'cursor-help' : ''
        ]"
      >
        <span v-if="!value" class="italic">No data</span>
        <span v-else>{{ formatDefaultValue(value) }}</span>
      </div>

      <!-- Enhanced Tooltip -->
      <div
        v-if="showTooltip"
        class="absolute z-50 bottom-full left-1/2 -translate-x-1/2 mb-3 px-1 pointer-events-none"
      >
        <div
          class="tooltip tooltip-top"
          :data-tip="value"
        >
          <div class="opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300">
          </div>
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
  return props.value && typeof props.value === 'string' && props.value.length > 50;
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
  if (!date) return 'No date';
  try {
    return format(new Date(date), 'MMM d, yyyy');
  } catch (e) {
    return 'Invalid date';
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
  if (num === null || num === undefined) return '0';
  try {
    return new Intl.NumberFormat('en-US', {
      notation: 'compact',
      compactDisplay: 'short'
    }).format(num);
  } catch (e) {
    return '0';
  }
}

function formatPrice(price: number | null): string {
  if (price === null || price === undefined) return '$0.00';
  try {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD',
      minimumFractionDigits: 2
    }).format(price);
  } catch (e) {
    return '$0.00';
  }
}

function getModelSearchBadgeColor(key: string): string {
  const colors: Record<string, string> = {
    status: 'badge-info',
    type: 'badge-warning',
    category: 'badge-success',
    priority: 'badge-error',
    label: 'badge-primary',
    tag: 'badge-secondary',
    level: 'badge-accent'
  };
  return colors[key.toLowerCase()] || 'badge-neutral';
}

function formatDefaultValue(value: any): string {
  if (value === null || value === undefined || value === '') return '';
  if (typeof value === 'object') return JSON.stringify(value);
  return String(value);
}
</script>

<style scoped>

.field-renderer {
  min-width: 0;
  max-width: 100%;
}

/* Custom pulse animation for boolean indicators */
@keyframes gentle-pulse {
  0%, 100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.7;
    transform: scale(1.1);
  }
}

.animate-pulse {
  animation: gentle-pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Enhanced hover effects */
.group:hover .group-hover\:scale-105 {
  transform: scale(1.05);
}

.group:hover .group-hover\:scale-110 {
  transform: scale(1.1);
}

/* Smooth transitions for all interactive elements */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 200ms;
}

/* Custom scrollbar for tooltip content */
::-webkit-scrollbar {
  width: 4px;
  height: 4px;
}

::-webkit-scrollbar-track {
  background: transparent;
}

::-webkit-scrollbar-thumb {
  background: hsl(var(--bc) / 0.2);
  border-radius: 2px;
}

::-webkit-scrollbar-thumb:hover {
  background: hsl(var(--bc) / 0.3);
}
</style>
