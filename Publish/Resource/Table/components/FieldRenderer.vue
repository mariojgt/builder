<template>
  <div class="field-renderer" :class="containerClasses">
    <!-- Status/Badge Fields with Conditional Styling -->
    <div
      v-if="shouldRenderAsBadge"
      class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium transition-all duration-200 hover:scale-105"
      :class="getConditionalClasses()"
    >
      <component
        v-if="getStatusIcon()"
        :is="getStatusIcon()"
        class="w-4 h-4 mr-2"
      />
      {{ formatValue() }}
    </div>

    <!-- Text Fields with Conditional Background -->
    <div
      v-else-if="type === 'text' && hasConditionalStyling"
      class="inline-flex items-center px-2 py-1 rounded text-sm transition-all duration-200"
      :class="getConditionalClasses()"
    >
      {{ formatValue() }}
    </div>

    <!-- Number Fields with Conditional Styling (like CVSS scores) -->
    <div
      v-else-if="(type === 'number' || isNumeric) && hasConditionalStyling"
      class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-semibold transition-all duration-200"
      :class="getConditionalClasses()"
    >
      <span class="tabular-nums">{{ formatValue() }}</span>
      <span v-if="getScoreLabel()" class="ml-2 text-xs opacity-80">
        {{ getScoreLabel() }}
      </span>
    </div>

    <!-- Boolean Fields -->
    <div v-else-if="type === 'boolean'" class="flex items-center">
      <div
        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium transition-all duration-200"
        :class="getBooleanClasses()"
      >
        <component :is="booleanValue ? CheckCircle : XCircle" class="w-4 h-4 mr-1" />
        {{ booleanValue ? 'Yes' : 'No' }}
      </div>
    </div>

    <!-- Date/Timestamp Fields -->
    <div v-else-if="type === 'date' || type === 'timestamp'" class="flex items-center text-sm">
      <Calendar class="w-4 h-4 mr-2 text-base-content/50" />
      <span>{{ formatDate() }}</span>
    </div>

    <!-- Email Fields -->
    <div v-else-if="type === 'email'" class="flex items-center text-sm">
      <Mail class="w-4 h-4 mr-2 text-primary/70" />
      <a :href="`mailto:${value}`" class="link link-primary hover:link-secondary transition-colors duration-200">
        {{ value }}
      </a>
    </div>

    <!-- URL Fields -->
    <div v-else-if="type === 'url'" class="flex items-center text-sm">
      <ExternalLink class="w-4 h-4 mr-2 text-primary/70" />
      <a :href="value" target="_blank" class="link link-primary hover:link-secondary transition-colors duration-200">
        {{ truncateUrl(value) }}
      </a>
    </div>

    <!-- Media/Image Fields -->
    <div v-else-if="type === 'media'" class="flex items-center">
      <div v-if="value" class="avatar">
        <div class="w-12 h-12 rounded-lg">
          <img :src="value" :alt="'Media'" class="object-cover" />
        </div>
      </div>
      <div v-else class="flex items-center justify-center w-12 h-12 bg-base-200 rounded-lg">
        <ImageIcon class="w-6 h-6 text-base-content/40" />
      </div>
    </div>

    <!-- Rating/Stars -->
    <div v-else-if="type === 'rating'" class="flex items-center">
      <div class="rating rating-sm">
        <Star
          v-for="star in 5"
          :key="star"
          class="w-4 h-4"
          :class="star <= (numericValue || 0) ? 'text-yellow-400 fill-current' : 'text-base-300'"
        />
      </div>
      <span class="ml-2 text-sm text-base-content/60">{{ value }}/5</span>
    </div>

    <!-- Price/Currency -->
    <div v-else-if="type === 'price'" class="flex items-center text-sm font-semibold">
      <DollarSign class="w-4 h-4 mr-1 text-green-600" />
      <span class="tabular-nums">{{ formatPrice() }}</span>
    </div>

    <!-- Progress/Percentage -->
    <div v-else-if="type === 'progress' || type === 'percentage'" class="w-full">
      <div class="flex items-center justify-between mb-1">
        <span class="text-sm">{{ formatValue() }}%</span>
        <span
          class="text-xs px-2 py-1 rounded"
          :class="getProgressClasses()"
        >
          {{ getProgressLabel() }}
        </span>
      </div>
      <div class="w-full bg-base-300 rounded-full h-2">
        <div
          class="h-2 rounded-full transition-all duration-500"
          :class="getProgressBarClasses()"
          :style="{ width: `${Math.min(100, Math.max(0, numericValue || 0))}%` }"
        ></div>
      </div>
    </div>

    <!-- Chip/Tag Lists -->
    <div v-else-if="type === 'chips' || type === 'tags'" class="flex flex-wrap gap-1">
      <div
        v-for="(chip, index) in getChipArray()"
        :key="index"
        class="badge badge-sm badge-outline hover:badge-primary transition-colors duration-200"
      >
        {{ chip }}
      </div>
    </div>

    <!-- Long Text with Truncation -->
    <div v-else-if="isLongText" class="group">
      <div class="text-sm leading-relaxed">
        <span v-if="!showFullText">
          {{ truncatedText }}
          <button
            v-if="needsTruncation"
            @click="showFullText = true"
            class="link link-primary text-xs ml-1 hover:link-secondary"
          >
            Show more
          </button>
        </span>
        <span v-else>
          {{ value }}
          <button
            @click="showFullText = false"
            class="link link-primary text-xs ml-1 hover:link-secondary"
          >
            Show less
          </button>
        </span>
      </div>
    </div>

    <!-- Default Text -->
    <div v-else class="text-sm">
      <span v-if="value !== null && value !== undefined && value !== ''">
        <span
          v-if="hasConditionalStyling"
          class="inline-flex items-center px-2 py-1 rounded text-sm transition-all duration-200"
          :class="getConditionalClasses() || 'text-base-content'"
        >
          {{ formatValue() }}
        </span>
        <span v-else>
          {{ formatValue() }}
        </span>
      </span>
      <span v-else class="text-base-content/40 italic">
        No data
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { format, formatDistanceToNow } from 'date-fns';
import {
  CheckCircle,
  XCircle,
  Calendar,
  Mail,
  ExternalLink,
  Image as ImageIcon,
  Star,
  DollarSign,
  Shield,
  AlertTriangle,
  CheckSquare,
  Clock,
  Zap
} from 'lucide-vue-next';

interface Props {
  value: any;
  type?: string;
  options?: {
    conditionalStyling?: {
      conditions: Record<string, string>;
      default?: string;
    };
    advancedStyling?: {
      conditions: Array<{
        operator: string;
        value?: any;
        min?: number;
        max?: number;
        classes: string;
      }>;
      default?: string;
    };
    enhanced?: boolean;
    truncate?: boolean;
    truncateLength?: number;
  };
}

const props = withDefaults(defineProps<Props>(), {
  type: 'text',
  options: () => ({})
});

// State for text expansion
const showFullText = ref(false);

// Computed properties
const numericValue = computed(() => {
  const num = parseFloat(props.value);
  return isNaN(num) ? null : num;
});

const booleanValue = computed(() => {
  if (typeof props.value === 'boolean') return props.value;
  if (typeof props.value === 'string') {
    return ['true', '1', 'yes', 'on', 'active'].includes(props.value.toLowerCase());
  }
  return Boolean(props.value);
});

const isNumeric = computed(() => numericValue.value !== null);

const hasConditionalStyling = computed(() => {
  return props.options.conditionalStyling || props.options.advancedStyling;
});

const shouldRenderAsBadge = computed(() => {
  // Only render as badge if there's a value AND conditional styling
  if (!props.value || (props.value === null || props.value === undefined || props.value === '')) {
    return false;
  }

  const badgeTypes = ['status', 'badge', 'tag'];
  const statusFields = ['status', 'state', 'condition'];

  return (badgeTypes.includes(props.type || '') ||
         (hasConditionalStyling.value && statusFields.some(field =>
           String(props.value).toLowerCase().includes(field)
         ))) && hasConditionalStyling.value;
});

const isLongText = computed(() => {
  return props.type === 'text' &&
         props.value &&
         String(props.value).length > (props.options.truncateLength || 100);
});

const needsTruncation = computed(() => {
  return props.value && String(props.value).length > (props.options.truncateLength || 100);
});

const truncatedText = computed(() => {
  if (!props.value) return '';
  const text = String(props.value);
  const maxLength = props.options.truncateLength || 100;
  return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
});

const containerClasses = computed(() => {
  return props.options.enhanced ? 'enhanced-field' : '';
});

// Methods
function getConditionalClasses(): string {
  // Handle simple conditional styling
  if (props.options.conditionalStyling) {
    const { conditions, default: defaultStyle } = props.options.conditionalStyling;
    const normalizedValue = String(props.value || '').toLowerCase().trim();

    // Return empty string if no value to avoid styling empty content
    if (!normalizedValue) return '';

    for (const [condition, classes] of Object.entries(conditions)) {
      if (normalizedValue === condition.toLowerCase()) {
        return classes;
      }
    }
    return defaultStyle || '';
  }

  // Handle advanced conditional styling
  if (props.options.advancedStyling) {
    const { conditions, default: defaultStyle } = props.options.advancedStyling;

    // Return empty string if no value to avoid styling empty content
    if (props.value === null || props.value === undefined || props.value === '') {
      return '';
    }

    for (const condition of conditions) {
      if (evaluateCondition(condition)) {
        return condition.classes;
      }
    }
    return defaultStyle || '';
  }

  return '';
}

function evaluateCondition(condition: any): boolean {
  const value = numericValue.value !== null ? numericValue.value : props.value;

  switch (condition.operator) {
    case 'equals':
      return value == condition.value;
    case 'not_equals':
      return value != condition.value;
    case 'greater_than':
      return typeof value === 'number' && value > condition.value;
    case 'greater_than_equal':
      return typeof value === 'number' && value >= condition.value;
    case 'less_than':
      return typeof value === 'number' && value < condition.value;
    case 'less_than_equal':
      return typeof value === 'number' && value <= condition.value;
    case 'between':
      return typeof value === 'number' &&
             value >= condition.min &&
             value <= condition.max;
    case 'contains':
      return String(value).toLowerCase().includes(String(condition.value).toLowerCase());
    case 'starts_with':
      return String(value).toLowerCase().startsWith(String(condition.value).toLowerCase());
    case 'ends_with':
      return String(value).toLowerCase().endsWith(String(condition.value).toLowerCase());
    default:
      return false;
  }
}

function getBooleanClasses(): string {
  if (booleanValue.value) {
    return 'bg-green-100 text-green-800 border border-green-200';
  } else {
    return 'bg-red-100 text-red-800 border border-red-200';
  }
}

function getProgressClasses(): string {
  const value = numericValue.value || 0;
  if (value >= 90) return 'bg-green-100 text-green-800';
  if (value >= 70) return 'bg-blue-100 text-blue-800';
  if (value >= 50) return 'bg-yellow-100 text-yellow-800';
  return 'bg-red-100 text-red-800';
}

function getProgressBarClasses(): string {
  const value = numericValue.value || 0;
  if (value >= 90) return 'bg-green-500';
  if (value >= 70) return 'bg-blue-500';
  if (value >= 50) return 'bg-yellow-500';
  return 'bg-red-500';
}

function getProgressLabel(): string {
  const value = numericValue.value || 0;
  if (value >= 90) return 'Excellent';
  if (value >= 70) return 'Good';
  if (value >= 50) return 'Average';
  return 'Poor';
}

function getStatusIcon() {
  const normalizedValue = String(props.value || '').toLowerCase();

  const iconMap: Record<string, any> = {
    'unpatched': AlertTriangle,
    'patched': CheckSquare,
    'active': CheckCircle,
    'inactive': XCircle,
    'pending': Clock,
    'completed': CheckCircle,
    'critical': Shield,
    'high': Zap,
    'validated': CheckSquare,
    'rejected': XCircle,
  };

  return iconMap[normalizedValue];
}

function getScoreLabel(): string {
  if (props.type !== 'number' && !isNumeric.value) return '';

  const value = numericValue.value || 0;

  // CVSS Score labels
  if (value >= 9.0) return 'Critical';
  if (value >= 7.0) return 'High';
  if (value >= 4.0) return 'Medium';
  if (value > 0) return 'Low';
  return 'None';
}

function formatValue(): string {
  if (props.value === null || props.value === undefined) return '';
  return String(props.value);
}

function formatDate(): string {
  if (!props.value) return '';

  try {
    const date = new Date(props.value);
    if (props.type === 'timestamp') {
      return format(date, 'MMM dd, yyyy HH:mm');
    }
    return format(date, 'MMM dd, yyyy');
  } catch {
    return String(props.value);
  }
}

function formatPrice(): string {
  if (!isNumeric.value) return String(props.value);

  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(numericValue.value || 0);
}

function truncateUrl(url: string): string {
  if (!url) return '';
  const maxLength = 40;
  return url.length > maxLength ? url.substring(0, maxLength) + '...' : url;
}

function getChipArray(): string[] {
  if (!props.value) return [];

  if (Array.isArray(props.value)) {
    return props.value.map(String);
  }

  if (typeof props.value === 'string') {
    return props.value.split(',').map(s => s.trim()).filter(Boolean);
  }

  return [String(props.value)];
}
</script>

<style scoped>
/* Enhanced field animations */
.enhanced-field {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.enhanced-field:hover {
  transform: translateY(-1px);
}

/* Badge hover effects */
.badge:hover {
  transform: scale(1.05);
}

/* Progress bar animations */
.h-2 {
  transition: width 0.5s ease-in-out;
}

/* Link hover effects */
.link {
  transition: all 0.2s ease;
}

.link:hover {
  text-decoration: underline;
  text-decoration-thickness: 2px;
  text-underline-offset: 2px;
}

/* Rating stars */
.rating .text-yellow-400 {
  filter: drop-shadow(0 0 2px rgba(251, 191, 36, 0.3));
}

/* Avatar image hover */
.avatar img {
  transition: transform 0.2s ease;
}

.avatar:hover img {
  transform: scale(1.1);
}

/* Chip animations */
.badge {
  transition: all 0.2s ease;
}

.badge:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Text expansion animations */
.group span {
  transition: all 0.3s ease;
}

/* Tabular numbers for better number alignment */
.tabular-nums {
  font-variant-numeric: tabular-nums;
}
</style>
