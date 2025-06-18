<template>
  <div class="field-renderer" :class="containerClasses">
    <!-- ✨ ULTRA COMPACT MODE - Maximum Data Density -->
    <template v-if="ultraCompact">
      <!-- ✨ Wrap everything in link if linkUrl exists -->
      <component
        :is="linkUrl ? 'a' : 'div'"
        :href="linkUrl"
        :target="linkTarget"
        :class="[linkUrl ? 'field-link' : '', linkClasses]"
        class="block"
      >
        <!-- Ultra Compact Status/Badge -->
        <span
          v-if="shouldRenderAsBadge"
          class="badge-micro inline-block"
          :class="getConditionalClasses()"
          :title="formatValue()"
        >
          {{ truncateText(formatValue(), 3) }}
        </span>

        <!-- Ultra Compact Numbers -->
        <span
          v-else-if="(type === 'number' || isNumeric) && hasConditionalStyling"
          class="badge-micro inline-block tabular-nums font-medium"
          :class="getConditionalClasses()"
          :title="`${formatValue()} - ${getScoreLabel()}`"
        >
          {{ formatValue() }}
        </span>

        <!-- Ultra Compact Boolean -->
        <span
          v-else-if="type === 'boolean'"
          class="boolean-indicator"
          :class="booleanValue ? 'boolean-true' : 'boolean-false'"
          :title="booleanValue ? 'Yes' : 'No'"
        ></span>

        <!-- Ultra Compact Date -->
        <span
          v-else-if="type === 'date' || type === 'timestamp'"
          class="date-compact tabular-nums"
          :title="formatDate()"
        >
          {{ formatDateUltraCompact() }}
        </span>

        <!-- Ultra Compact Email -->
        <span
          v-else-if="type === 'email'"
          class="text-nano"
          :title="value"
        >
          <a v-if="!linkUrl" :href="`mailto:${value}`" class="link">
            {{ truncateText(value.split('@')[0], 4) }}
          </a>
          <span v-else>{{ truncateText(value.split('@')[0], 4) }}</span>
        </span>

        <!-- Ultra Compact URL -->
        <span
          v-else-if="type === 'url'"
          class="text-nano"
          :title="value"
        >
          <a v-if="!linkUrl" :href="value" target="_blank" class="link">
            {{ truncateText(getDomainFromUrl(value), 6) }}
          </a>
          <span v-else>{{ truncateText(getDomainFromUrl(value), 6) }}</span>
        </span>

        <!-- Ultra Compact Media -->
        <img
          v-if="type === 'media' && value"
          :src="value"
          class="avatar-micro object-cover"
          :alt="''"
          loading="lazy"
        />
        <span v-else-if="type === 'media'" class="text-nano opacity-50">-</span>

        <!-- Ultra Compact Rating -->
        <span
          v-else-if="type === 'rating'"
          class="text-nano tabular-nums"
          :title="`${value}/5 stars`"
        >
          {{ value }}/5
        </span>

        <!-- Ultra Compact Price -->
        <span
          v-else-if="type === 'price'"
          class="text-nano tabular-nums font-medium"
          :title="formatPrice()"
        >
          {{ formatPriceCompact() }}
        </span>

        <!-- Ultra Compact Progress -->
        <div v-else-if="type === 'progress' || type === 'percentage'" class="w-full">
          <div class="progress-micro">
            <div
              class="progress-bar"
              :class="getProgressBarClasses()"
              :style="{ width: `${Math.min(100, Math.max(0, numericValue || 0))}%` }"
              :title="`${formatValue()}% - ${getProgressLabel()}`"
            ></div>
          </div>
        </div>

        <!-- Ultra Compact Chips/Tags -->
        <span
          v-else-if="type === 'chips' || type === 'tags'"
          class="text-nano"
          :title="getChipArray().join(', ')"
        >
          {{ getChipArray().length }}
        </span>

        <!-- Ultra Compact Text with Conditional Styling -->
        <span
          v-else-if="hasConditionalStyling"
          class="badge-micro inline-block"
          :class="getConditionalClasses()"
          :title="formatValue()"
        >
          {{ truncateText(formatValue(), 4) }}
        </span>

        <!-- Ultra Compact Default Text -->
        <span
          v-else
          class="text-nano"
          :title="formatValue()"
        >
          <span v-if="value !== null && value !== undefined && value !== ''">
            <a v-if="linkUrl" :href="linkUrl" :target="linkTarget" class="link">
              {{ truncateText(formatValue(), 6) }}
            </a>
            <span v-else>{{ truncateText(formatValue(), 8) }}</span>
          </span>
          <span v-else class="opacity-30">-</span>
        </span>
      </component>
    </template>

    <!-- ✨ REGULAR COMPACT MODE -->
    <template v-else>
      <!-- ✨ Wrap everything in link if linkUrl exists -->
      <component
        :is="linkUrl ? 'a' : 'div'"
        :href="linkUrl"
        :target="linkTarget"
        :class="[linkUrl ? 'field-link' : '', linkClasses]"
        class="block"
      >
        <!-- Status/Badge Fields with Conditional Styling -->
        <div
          v-if="shouldRenderAsBadge"
          class="inline-flex items-center rounded-full font-medium transition-all duration-200 hover:scale-105"
          :class="[
            getConditionalClasses(),
            compact ? 'px-2 py-0.5 text-xs' : 'px-3 py-1 text-sm'
          ]"
        >
          <component
            v-if="getStatusIcon() && !compact"
            :is="getStatusIcon()"
            class="w-4 h-4 mr-2"
          />
          <component
            v-else-if="getStatusIcon()"
            :is="getStatusIcon()"
            class="w-3 h-3 mr-1"
          />
          {{ formatValue() }}
        </div>

        <!-- Text Fields with Conditional Background -->
        <div
          v-else-if="type === 'text' && hasConditionalStyling"
          class="inline-flex items-center rounded transition-all duration-200"
          :class="[
            getConditionalClasses(),
            compact ? 'px-1 py-0.5 text-xs' : 'px-2 py-1 text-sm'
          ]"
        >
          {{ formatValue() }}
        </div>

        <!-- Number Fields with Conditional Styling (like CVSS scores) -->
        <div
          v-else-if="(type === 'number' || isNumeric) && hasConditionalStyling"
          class="inline-flex items-center rounded-lg font-semibold transition-all duration-200"
          :class="[
            getConditionalClasses(),
            compact ? 'px-2 py-0.5 text-xs' : 'px-3 py-1 text-sm'
          ]"
        >
          <span class="tabular-nums">{{ formatValue() }}</span>
          <span v-if="getScoreLabel() && !compact" class="ml-2 text-xs opacity-80">
            {{ getScoreLabel() }}
          </span>
        </div>

        <!-- Boolean Fields -->
        <div v-else-if="type === 'boolean'" class="flex items-center">
          <div
            class="inline-flex items-center rounded-full font-medium transition-all duration-200"
            :class="[
              getBooleanClasses(),
              compact ? 'px-1 py-0.5 text-xs' : 'px-2 py-1 text-xs'
            ]"
          >
            <component
              :is="booleanValue ? CheckCircle : XCircle"
              :class="compact ? 'w-3 h-3 mr-0.5' : 'w-4 h-4 mr-1'"
            />
            {{ booleanValue ? 'Yes' : 'No' }}
          </div>
        </div>

        <!-- Date/Timestamp Fields -->
        <div v-else-if="type === 'date' || type === 'timestamp'" class="flex items-center" :class="compact ? 'text-xs' : 'text-sm'">
          <Calendar
            :class="compact ? 'w-3 h-3 mr-1' : 'w-4 h-4 mr-2'"
            class="text-base-content/50"
          />
          <span>{{ formatDate() }}</span>
        </div>

        <!-- Email Fields -->
        <div v-else-if="type === 'email'" class="flex items-center" :class="compact ? 'text-xs' : 'text-sm'">
          <Mail
            :class="compact ? 'w-3 h-3 mr-1' : 'w-4 h-4 mr-2'"
            class="text-primary/70"
          />
          <!-- ✨ Don't nest links - show email as text when wrapped in link -->
          <span v-if="linkUrl">{{ compact ? truncateText(value, 20) : value }}</span>
          <a v-else :href="`mailto:${value}`" class="link link-primary hover:link-secondary transition-colors duration-200">
            {{ compact ? truncateText(value, 20) : value }}
          </a>
        </div>

        <!-- URL Fields -->
        <div v-else-if="type === 'url'" class="flex items-center" :class="compact ? 'text-xs' : 'text-sm'">
          <ExternalLink
            :class="compact ? 'w-3 h-3 mr-1' : 'w-4 h-4 mr-2'"
            class="text-primary/70"
          />
          <!-- ✨ Don't nest links - show URL as text when wrapped in link -->
          <span v-if="linkUrl">{{ truncateUrl(value) }}</span>
          <a v-else :href="value" target="_blank" class="link link-primary hover:link-secondary transition-colors duration-200">
            {{ truncateUrl(value) }}
          </a>
        </div>

        <!-- Media/Image Fields -->
        <div v-else-if="type === 'media'" class="flex items-center">
          <div v-if="value" class="avatar">
            <div :class="compact ? 'w-8 h-8 rounded' : 'w-12 h-12 rounded-lg'">
              <img :src="value" :alt="'Media'" class="object-cover" />
            </div>
          </div>
          <div v-else class="flex items-center justify-center bg-base-200 rounded-lg" :class="compact ? 'w-8 h-8' : 'w-12 h-12'">
            <ImageIcon :class="compact ? 'w-4 h-4' : 'w-6 h-6'" class="text-base-content/40" />
          </div>
        </div>

        <!-- Rating/Stars -->
        <div v-else-if="type === 'rating'" class="flex items-center">
          <div class="rating" :class="compact ? 'rating-xs' : 'rating-sm'">
            <Star
              v-for="star in 5"
              :key="star"
              :class="[
                compact ? 'w-3 h-3' : 'w-4 h-4',
                'transition-colors duration-200',
                star <= (numericValue || 0) ? 'text-yellow-400 fill-current' : 'text-base-300'
              ]"
            />
          </div>
          <span class="ml-2 text-base-content/60" :class="compact ? 'text-xs' : 'text-sm'">
            {{ value }}/5
          </span>
        </div>

        <!-- Price/Currency -->
        <div v-else-if="type === 'price'" class="flex items-center font-semibold" :class="compact ? 'text-xs' : 'text-sm'">
          <DollarSign :class="compact ? 'w-3 h-3 mr-0.5' : 'w-4 h-4 mr-1'" class="text-green-600" />
          <span class="tabular-nums">{{ formatPrice() }}</span>
        </div>

        <!-- Progress/Percentage -->
        <div v-else-if="type === 'progress' || type === 'percentage'" class="w-full">
          <div class="flex items-center justify-between" :class="compact ? 'mb-0.5' : 'mb-1'">
            <span :class="compact ? 'text-xs' : 'text-sm'">{{ formatValue() }}%</span>
            <span
              class="text-xs px-2 py-1 rounded"
              :class="[getProgressClasses(), compact ? 'text-xs px-1 py-0.5' : '']"
            >
              {{ compact ? getProgressLabelShort() : getProgressLabel() }}
            </span>
          </div>
          <div class="w-full bg-base-300 rounded-full" :class="compact ? 'h-1' : 'h-2'">
            <div
              class="rounded-full transition-all duration-500"
              :class="[getProgressBarClasses(), compact ? 'h-1' : 'h-2']"
              :style="{ width: `${Math.min(100, Math.max(0, numericValue || 0))}%` }"
            ></div>
          </div>
        </div>

        <!-- Chip/Tag Lists -->
        <div v-else-if="type === 'chips' || type === 'tags'" class="flex flex-wrap gap-1">
          <div
            v-for="(chip, index) in getChipArray()"
            :key="index"
            class="badge badge-outline hover:badge-primary transition-colors duration-200"
            :class="compact ? 'badge-xs' : 'badge-sm'"
          >
            {{ compact ? truncateText(chip, 10) : chip }}
          </div>
        </div>

        <!-- Long Text with Truncation -->
        <div v-else-if="isLongText" class="group">
          <div class="leading-relaxed" :class="compact ? 'text-xs' : 'text-sm'">
            <span v-if="!showFullText">
              {{ truncatedText }}
              <button
                v-if="needsTruncation"
                @click.stop="showFullText = true"
                class="link link-primary ml-1 hover:link-secondary"
                :class="compact ? 'text-xs' : 'text-xs'"
              >
                {{ compact ? '...' : 'Show more' }}
              </button>
            </span>
            <span v-else>
              {{ value }}
              <button
                @click.stop="showFullText = false"
                class="link link-primary ml-1 hover:link-secondary"
                :class="compact ? 'text-xs' : 'text-xs'"
              >
                {{ compact ? 'Less' : 'Show less' }}
              </button>
            </span>
          </div>
        </div>

        <!-- Default Text -->
        <div v-else :class="compact ? 'text-xs' : 'text-sm'">
          <span v-if="value !== null && value !== undefined && value !== ''">
            <span
              v-if="hasConditionalStyling"
              class="inline-flex items-center rounded transition-all duration-200"
              :class="[
                getConditionalClasses() || 'text-base-content',
                compact ? 'px-1 py-0.5 text-xs' : 'px-2 py-1 text-sm'
              ]"
            >
              {{ compact ? truncateText(formatValue(), 25) : formatValue() }}
            </span>
            <template v-else>
              <div
                v-if="props.linkStyle === 'link-compact'"
                class="inline-flex items-center rounded transition-all duration-200"
                :class="[linkClasses, compact ? 'px-1 py-0.5 text-xs' : 'px-2 py-1 text-sm']"
              >
                <span class="text-base-content">{{ compact ? 'Link' : 'Open Link' }}</span>
                <ExternalLink :class="compact ? 'w-3 h-3 ml-0.5' : 'w-4 h-4 ml-1'" />
              </div>
              <span v-else class="text-base-content">
                {{ compact ? truncateText(formatValue(), 25) : formatValue() }}
              </span>
            </template>
          </span>
          <span v-else class="text-base-content/40 italic">
            {{ compact ? 'N/A' : 'No data' }}
          </span>
        </div>
      </component>
    </template>
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
    compact?: boolean;
  };
  // ✨ NEW: Add link props
  link?: string | null;
  linkTarget?: string;
  linkStyle?: string;
  rowData?: Record<string, any>;
  compact?: boolean;
  ultraCompact?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  type: 'text',
  options: () => ({}),
  link: null,
  linkTarget: '_self',
  linkStyle: 'default',
  rowData: () => ({}),
  compact: false,
  ultraCompact: false
});

// State for text expansion
const showFullText = ref(false);

// ✨ NEW: Link computed properties
const linkUrl = computed(() => {
  if (!props.link) return null;

  let url = props.link;

  // Replace {value} with actual field value
  if (typeof url === 'string') {
    url = url.replace('{value}', encodeURIComponent(props.value || ''));
  }

  return url;
});

const linkTarget = computed(() => {
  return props.linkTarget || '_self';
});

// Existing computed properties with compact mode considerations
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
  const maxLength = props.compact ? 50 : (props.options.truncateLength || 100);
  return props.type === 'text' &&
         props.value &&
         String(props.value).length > maxLength;
});

const needsTruncation = computed(() => {
  const maxLength = props.compact ? 50 : (props.options.truncateLength || 100);
  return props.value && String(props.value).length > maxLength;
});

const truncatedText = computed(() => {
  if (!props.value) return '';
  const text = String(props.value);
  const maxLength = props.compact ? 50 : (props.options.truncateLength || 100);
  return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
});

const containerClasses = computed(() => {
  const classes = [];
  if (props.options.enhanced && !props.compact) classes.push('enhanced-field');
  if (linkUrl.value) classes.push('cursor-pointer');
  return classes.join(' ');
});

// ✨ NEW: Link style classes with compact mode support
const linkClasses = computed(() => {
  if (!linkUrl.value) return '';

  const baseClasses = ['transition-all', 'duration-200'];
  const compactSuffix = props.compact ? '-sm' : '';

  switch (props.linkStyle) {
    case 'button':
      return [...baseClasses, 'btn', `btn${compactSuffix}`, 'btn-outline'].join(' ');

    case 'button-primary':
      return [...baseClasses, 'btn', `btn${compactSuffix}`, 'btn-primary'].join(' ');

    case 'button-secondary':
      return [...baseClasses, 'btn', `btn${compactSuffix}`, 'btn-secondary'].join(' ');

    case 'badge':
      return [...baseClasses, 'badge', props.compact ? 'badge-xs' : 'badge-sm', 'badge-primary', 'hover:badge-secondary'].join(' ');

    case 'underline':
      return [...baseClasses, 'underline', 'hover:no-underline', 'text-primary', 'hover:text-primary-focus'].join(' ');

    case 'none':
      return [...baseClasses, 'no-underline', 'hover:opacity-80'].join(' ');

    case 'link-compact':
    case 'link':
      return [...baseClasses, 'link', 'link-primary', 'hover:link-secondary'].join(' ');

    default: // 'default'
      return props.linkStyle || [...baseClasses, 'text-primary', 'hover:text-primary-focus'].join(' ');
  }
});

// ✨ ULTRA COMPACT HELPER FUNCTIONS
// Ultra compact date formatting
function formatDateUltraCompact(): string {
  if (!props.value) return '';

  try {
    const date = new Date(props.value);
    const now = new Date();
    const diffTime = Math.abs(now.getTime() - date.getTime());
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays < 7) {
      return `${diffDays}d`;
    } else if (diffDays < 30) {
      return `${Math.floor(diffDays / 7)}w`;
    } else if (diffDays < 365) {
      return `${Math.floor(diffDays / 30)}m`;
    } else {
      return `${Math.floor(diffDays / 365)}y`;
    }
  } catch {
    return '';
  }
}

// Ultra compact price formatting
function formatPriceCompact(): string {
  if (!isNumeric.value) return String(props.value);

  const value = numericValue.value || 0;

  if (value >= 1000000) {
    return `$${(value / 1000000).toFixed(1)}M`;
  } else if (value >= 1000) {
    return `$${(value / 1000).toFixed(1)}K`;
  } else {
    return `$${value}`;
  }
}

// Extract domain from URL
function getDomainFromUrl(url: string): string {
  try {
    return new URL(url).hostname.replace('www.', '');
  } catch {
    return url;
  }
}

// Enhanced truncate function with intelligent word breaking
function truncateText(text: string, maxLength: number): string {
  if (!text) return '';
  const str = String(text);

  if (str.length <= maxLength) return str;

  // Try to break at word boundaries for better readability
  const truncated = str.substring(0, maxLength);
  const lastSpace = truncated.lastIndexOf(' ');

  if (lastSpace > maxLength * 0.6) {
    return truncated.substring(0, lastSpace) + '…';
  }

  return truncated + '…';
}

// All existing methods with compact mode considerations
function getConditionalClasses(): string {
  if (props.options.conditionalStyling) {
    const { conditions, default: defaultStyle } = props.options.conditionalStyling;
    const normalizedValue = String(props.value || '').toLowerCase().trim();

    if (!normalizedValue) return '';

    for (const [condition, classes] of Object.entries(conditions)) {
      if (normalizedValue === condition.toLowerCase()) {
        return classes;
      }
    }
    return defaultStyle || '';
  }

  if (props.options.advancedStyling) {
    const { conditions, default: defaultStyle } = props.options.advancedStyling;

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
    case 'exists':
      return value !== null &&
             value !== undefined &&
             value !== '' &&
             String(value).trim() !== '';

    case 'not_exists':
      return value === null ||
             value === undefined ||
             value === '' ||
             String(value).trim() === '';

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

function getProgressLabelShort(): string {
  const value = numericValue.value || 0;
  if (value >= 90) return 'Exc';
  if (value >= 70) return 'Good';
  if (value >= 50) return 'Avg';
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
      return format(date, props.compact ? 'MM/dd/yy HH:mm' : 'MMM dd, yyyy HH:mm');
    }
    return format(date, props.compact ? 'MM/dd/yy' : 'MMM dd, yyyy');
  } catch {
    return String(props.value);
  }
}

function formatPrice(): string {
  if (!isNumeric.value) return String(props.value);

  const options = props.compact
    ? { style: 'currency', currency: 'USD', notation: 'compact' } as const
    : { style: 'currency', currency: 'USD' } as const;

  return new Intl.NumberFormat('en-US', options).format(numericValue.value || 0);
}

function truncateUrl(url: string): string {
  if (!url) return '';
  const maxLength = props.compact ? 25 : 40;
  return url.length > maxLength ? url.substring(0, maxLength) + '...' : url;
}

function getChipArray(): string[] {
  if (!props.value) return [];

  if (Array.isArray(props.value)) {
    return props.compact ? props.value.slice(0, 3).map(String) : props.value.map(String);
  }

  if (typeof props.value === 'string') {
    const chips = props.value.split(',').map(s => s.trim()).filter(Boolean);
    return props.compact ? chips.slice(0, 3) : chips;
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

/* ✨ NEW: Link styling */
.field-link {
  text-decoration: none;
  color: inherit;
  transition: all 0.2s ease;
}

.field-link:hover {
  text-decoration: none;
  opacity: 0.8;
}

.cursor-pointer {
  cursor: pointer;
}

/* Badge hover effects */
.badge:hover {
  transform: scale(1.05);
}

/* Progress bar animations */
.h-2, .h-1 {
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

/* Compact mode specific styles */
.rating-xs .w-3 {
  width: 0.75rem;
  height: 0.75rem;
}

/* ✨ ULTRA COMPACT MODE STYLES */
.badge-micro {
  font-size: 0.5rem; /* 8px */
  padding: 0.0625rem 0.125rem;
  height: 0.875rem;
  min-height: 0.875rem;
  line-height: 1;
  border-radius: 0.125rem;
  display: inline-block;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 4rem;
  font-weight: 500;
}

.progress-micro {
  height: 0.125rem;
  border-radius: 0.0625rem;
  background: hsl(var(--b3));
  overflow: hidden;
  margin: 0.125rem 0;
  width: 100%;
}

.progress-micro .progress-bar {
  height: 100%;
  border-radius: 0.0625rem;
  transition: width 0.3s ease;
}

.avatar-micro {
  width: 1rem !important;
  height: 1rem !important;
  border-radius: 0.125rem;
  object-fit: cover;
}

.boolean-indicator {
  width: 0.75rem;
  height: 0.75rem;
  border-radius: 50%;
  display: inline-block;
}

.boolean-true {
  background-color: #22c55e; /* green-500 */
}

.boolean-false {
  background-color: #ef4444; /* red-500 */
}

.date-compact {
  font-variant-numeric: tabular-nums;
  font-size: 0.625rem;
  line-height: 1;
  font-weight: 500;
}

.text-micro {
  font-size: 0.5rem;
  line-height: 1;
}

.text-nano {
  font-size: 0.625rem;
  line-height: 1.1;
}

.text-pico {
  font-size: 0.6875rem;
  line-height: 1.2;
}

/* Ultra compact hover effects */
.badge-micro:hover {
  transform: scale(1.05);
}

.boolean-indicator:hover {
  transform: scale(1.1);
}

/* Tooltip enhancements for ultra compact mode */
[title] {
  cursor: help;
}

/* Ultra compact link styling */
.text-nano .link,
.text-micro .link {
  text-decoration: none;
  color: hsl(var(--p));
}

.text-nano .link:hover,
.text-micro .link:hover {
  text-decoration: underline;
  color: hsl(var(--pf));
}

/* Progress bar color variants */
.progress-bar.bg-green-500 {
  background-color: #22c55e;
}

.progress-bar.bg-blue-500 {
  background-color: #3b82f6;
}

.progress-bar.bg-yellow-500 {
  background-color: #eab308;
}

.progress-bar.bg-red-500 {
  background-color: #ef4444;
}

/* Enhanced accessibility for ultra compact elements */
.badge-micro:focus,
.boolean-indicator:focus {
  outline: 1px solid hsl(var(--p));
  outline-offset: 1px;
}

/* Responsive compact adjustments */
@media (max-width: 640px) {
  .field-renderer {
    font-size: 0.8rem;
  }

  .badge-micro {
    font-size: 0.45rem;
    padding: 0.0625rem 0.0625rem;
    max-width: 3rem;
  }

  .text-nano {
    font-size: 0.55rem;
  }

  .text-micro {
    font-size: 0.45rem;
  }

  .boolean-indicator {
    width: 0.625rem;
    height: 0.625rem;
  }

  .avatar-micro {
    width: 0.875rem !important;
    height: 0.875rem !important;
  }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .badge-micro {
    border: 1px solid;
  }

  .boolean-indicator {
    border: 1px solid;
  }

  .progress-micro {
    border: 1px solid;
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .badge-micro,
  .boolean-indicator,
  .progress-bar {
    transition: none;
  }

  .badge-micro:hover,
  .boolean-indicator:hover {
    transform: none;
  }
}

/* Print styles */
@media print {
  .badge-micro,
  .text-nano,
  .text-micro {
    color: black !important;
    background: white !important;
  }

  .boolean-true {
    background: #000 !important;
  }

  .boolean-false {
    background: #666 !important;
  }
}

/* Enhanced focus indicators */
.field-renderer:focus-within {
  outline: 1px solid hsl(var(--p));
  outline-offset: 2px;
  border-radius: 0.25rem;
}

/* Ensure text readability at micro sizes */
.text-micro,
.text-nano {
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  text-rendering: optimizeLegibility;
}

/* Status color coding for ultra compact badges */
.badge-critical {
  background: #dc2626;
  color: white;
}

.badge-high {
  background: #ea580c;
  color: white;
}

.badge-medium {
  background: #ca8a04;
  color: white;
}

.badge-low {
  background: #16a34a;
  color: white;
}

.badge-info {
  background: #2563eb;
  color: white;
}

/* Ensure consistent spacing in ultra compact mode */
.field-renderer > * {
  margin: 0;
}

/* Optimize for maximum data density */
.ultra-compact-container {
  line-height: 1;
  letter-spacing: -0.025em;
}
</style>
