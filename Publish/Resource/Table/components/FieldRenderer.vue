<template>
  <div class="field-renderer" :class="containerClasses">
    <component
      :is="computedLinkUrl && !hasNativeLinkBehavior ? 'a' : 'div'"
      :href="computedLinkUrl && !hasNativeLinkBehavior ? computedLinkUrl : undefined"
      :target="computedLinkUrl && !hasNativeLinkBehavior ? computedLinkTarget : undefined"
      :class="[
        computedLinkUrl && !hasNativeLinkBehavior ? 'field-link' : '',
        computedLinkClasses,
        { 'cursor-pointer': computedLinkUrl && !hasNativeLinkBehavior }
      ]"
      class="block"
      @click="handleWrapperClick"
    >
      <template v-if="ultraCompact">
        <!-- Model Search Data -->
        <div v-if="type === 'model_search'" class="ultra-compact-model-search">
          <div v-if="!showFullText" class="ultra-compact-inline">
            <span class="text-nano text-primary font-medium" :title="getModelSearchSummary()">
              {{ Object.keys(getModelSearchData()).length }} items
            </span>
            <button
              v-if="Object.keys(getModelSearchData()).length > 0"
              @click.stop="showFullText = true"
              class="ultra-compact-btn ml-1"
              title="Show details"
            >
              ▼
            </button>
          </div>
          <div v-else class="ultra-compact-expanded">
            <div class="ultra-compact-content">
              <div v-for="(val, k) in getModelSearchData()" :key="k" class="mb-1 last:mb-0">
                <span class="text-xs font-medium text-base-content/70">{{ formatModelSearchKey(k) }}:</span>
                <span class="text-xs text-base-content ml-1">{{ val }}</span>
              </div>
            </div>
            <button
              @click.stop="showFullText = false"
              class="ultra-compact-close-btn"
              title="Hide details"
            >
              ✕
            </button>
          </div>
        </div>

        <!-- Badge/Status Display -->
        <div v-else-if="shouldRenderAsBadge || hasConditionalStyling" class="ultra-compact-badge">
          <span
            class="badge-micro inline-flex items-center"
            :class="getConditionalClasses()"
            :title="formatValue()"
          >
            <component
              v-if="getStatusIcon()"
              :is="getStatusIcon()"
              class="w-2.5 h-2.5 mr-0.5 opacity-80"
            />
            {{ formatValue() }}
          </span>
        </div>

        <!-- Boolean Display -->
        <div v-else-if="type === 'boolean'" class="ultra-compact-boolean">
          <span
            class="boolean-indicator"
            :class="booleanValue ? 'boolean-true' : 'boolean-false'"
            :title="booleanValue ? 'Yes' : 'No'"
          ></span>
        </div>

        <!-- Date Display -->
        <div v-else-if="type === 'date' || type === 'timestamp'" class="ultra-compact-date">
          <span class="text-nano tabular-nums text-base-content" :title="formatDate(false)">
            {{ formatDateHumanDiff() }}
          </span>
        </div>

        <!-- Email Display -->
        <div v-else-if="type === 'email'" class="ultra-compact-email">
          <a
            v-if="!computedLinkUrl"
            :href="`mailto:${value}`"
            class="text-nano link text-primary hover:text-primary-focus break-all"
            :title="value"
            @click.stop
          >
            {{ value }}
          </a>
          <span v-else class="text-nano break-all" :title="value">
            {{ value }}
          </span>
        </div>

        <!-- URL Display -->
        <div v-else-if="type === 'url'" class="ultra-compact-url">
          <a
            v-if="!computedLinkUrl"
            :href="value"
            target="_blank"
            class="text-nano link text-primary hover:text-primary-focus break-all"
            :title="value"
            @click.stop
          >
            {{ getDomainFromUrl(value) }}
          </a>
          <span v-else class="text-nano break-all" :title="value">
            {{ getDomainFromUrl(value) }}
          </span>
        </div>

        <!-- Media Display -->
        <div v-else-if="type === 'media'" class="ultra-compact-media">
          <img
            v-if="value"
            :src="value"
            class="avatar-micro object-cover"
            :alt="''"
            loading="lazy"
          />
          <span v-else class="text-nano opacity-50">-</span>
        </div>

        <!-- Rating Display -->
        <div v-else-if="type === 'rating'" class="ultra-compact-rating">
          <span class="text-nano tabular-nums font-medium" :title="`${value}/5 stars`">
            ★{{ value }}
          </span>
        </div>

        <!-- Price Display -->
        <div v-else-if="type === 'price'" class="ultra-compact-price">
          <span class="text-nano tabular-nums font-medium text-green-600" :title="formatPrice()">
            {{ formatPriceCompact() }}
          </span>
        </div>

        <!-- Progress Display -->
        <div v-else-if="type === 'progress' || type === 'percentage'" class="ultra-compact-progress">
          <div class="progress-micro">
            <div
              class="progress-bar"
              :class="getProgressBarClasses()"
              :style="{ width: `${Math.min(100, Math.max(0, numericValue || 0))}%` }"
              :title="`${formatValue()}% - ${getProgressLabel()}`"
            ></div>
          </div>
          <span class="text-nano ml-1">{{ Math.round(numericValue || 0) }}%</span>
        </div>

        <!-- Chips/Tags Display -->
        <div v-else-if="type === 'chips' || type === 'tags'" class="ultra-compact-chips">
          <div v-if="!showFullText" class="ultra-compact-inline">
            <span class="text-nano text-primary font-medium" :title="getChipArray().join(', ')">
              {{ getChipArray().length }} tags
            </span>
            <button
              v-if="getChipArray().length > 0"
              @click.stop="showFullText = true"
              class="ultra-compact-btn ml-1"
              title="Show tags"
            >
              ▼
            </button>
          </div>
          <div v-else class="ultra-compact-expanded">
            <div class="ultra-compact-content">
              <div class="flex flex-wrap gap-1">
                <span
                  v-for="(chip, index) in getChipArray()"
                  :key="index"
                  class="badge badge-xs badge-outline"
                >
                  {{ chip }}
                </span>
              </div>
            </div>
            <button
              @click.stop="showFullText = false"
              class="ultra-compact-close-btn"
              title="Hide tags"
            >
              ✕
            </button>
          </div>
        </div>

        <!-- Long Text Display -->
        <div v-else-if="isUltraCompactLongText" class="ultra-compact-text">
          <div v-if="!showFullText" class="ultra-compact-inline">
            <span class="text-nano break-words" :title="formatValue()">
              {{ formatValue() }}
            </span>
            <button
              v-if="String(formatValue()).length > 50"
              @click.stop="showFullText = true"
              class="ultra-compact-btn ml-1"
              title="Show in popup"
            >
              ↗
            </button>
          </div>
          <div v-else class="ultra-compact-expanded">
            <div class="ultra-compact-content">
              <div class="text-xs leading-relaxed break-words whitespace-pre-wrap">
                {{ formatValue() }}
              </div>
            </div>
            <button
              @click.stop="showFullText = false"
              class="ultra-compact-close-btn"
              title="Hide popup"
            >
              ✕
            </button>
          </div>
        </div>

        <!-- Default Text Display -->
        <div v-else class="ultra-compact-default">
          <span v-if="hasValue" class="text-nano" :title="formatValue()">
            {{ formatValue() }}
          </span>
          <span v-else class="text-nano opacity-30">-</span>
        </div>
      </template>

      <template v-else>
        <div v-if="type === 'model_search'" class="flex flex-wrap gap-1">
          <div
            v-for="(val, k) in getModelSearchData()"
            :key="k"
            class="inline-flex items-start rounded-full font-medium transition-all duration-200 hover:scale-105"
            :class="[
              getModelSearchBadgeColor(k),
              compact ? 'px-2 py-0.5 text-xs' : 'px-3 py-1 text-sm'
            ]"
            :title="`${formatModelSearchKey(k)}: ${val}`"
          >
            <component
              v-if="getModelSearchIcon(k)"
              :is="getModelSearchIcon(k)"
              :class="compact ? 'w-3 h-3 mr-1 mt-0.5 flex-shrink-0' : 'w-4 h-4 mr-2'"
            />
            <div class="min-w-0 flex-1">
              <span class="font-medium">{{ formatModelSearchKey(k) }}:</span>
              <span class="ml-1 break-words whitespace-normal">{{ val }}</span>
            </div>
          </div>
        </div>

        <div
          v-else-if="shouldRenderAsBadge"
          class="inline-flex items-center rounded-full font-medium transition-all duration-200 hover:scale-105"
          :class="[
            getConditionalClasses(),
            compact ? 'px-2 py-0.5 text-xs' : 'px-3 py-1 text-sm'
          ]"
        >
          <component
            v-if="getStatusIcon()"
            :is="getStatusIcon()"
            :class="compact ? 'w-3 h-3 mr-1' : 'w-4 h-4 mr-2'"
          />
          {{ formatValue() }}
        </div>

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

        <div v-else-if="type === 'date' || type === 'timestamp'" class="flex items-center" :class="compact ? 'text-xs' : 'text-sm'">
          <Calendar
            :class="compact ? 'w-3 h-3 mr-1' : 'w-4 h-4 mr-2'"
            class="text-base-content/50"
          />
          <div>
            <span>{{ formatDate(true) }}</span>
            <span class="block text-base-content/60" :class="compact ? 'text-xs' : 'text-sm'">
                ({{ formatDateHumanDiff() }})
            </span>
          </div>
        </div>

        <div v-else-if="type === 'email'" class="flex items-center" :class="compact ? 'text-xs' : 'text-sm'">
          <Mail
            :class="compact ? 'w-3 h-3 mr-1' : 'w-4 h-4 mr-2'"
            class="text-primary/70"
          />
          <span v-if="computedLinkUrl">{{ value }}</span>
          <a
            v-else
            :href="`mailto:${value}`"
            class="link link-primary hover:link-secondary transition-colors duration-200 break-all"
            @click.stop
          >
            {{ value }}
          </a>
        </div>

        <div v-else-if="type === 'url'" class="flex items-center" :class="compact ? 'text-xs' : 'text-sm'">
          <ExternalLink
            :class="compact ? 'w-3 h-3 mr-1' : 'w-4 h-4 mr-2'"
            class="text-primary/70"
          />
          <span v-if="computedLinkUrl">{{ compact ? value : value }}</span>
          <a
            v-else
            :href="value"
            target="_blank"
            class="link link-primary hover:link-secondary transition-colors duration-200 break-all"
            @click.stop
          >
            {{ compact ? value : value }}
          </a>
        </div>

        <div v-else-if="type === 'media'" class="flex items-center">
          <div v-if="value" class="avatar">
            <div :class="compact ? 'w-8 h-8 rounded' : 'w-12 h-12 rounded-lg'">
              <img :src="value" :alt="'Media'" class="object-cover" loading="lazy" />
            </div>
          </div>
          <div v-else class="flex items-center justify-center bg-base-200 rounded-lg" :class="compact ? 'w-8 h-8' : 'w-12 h-12'">
            <ImageIcon :class="compact ? 'w-4 h-4' : 'w-6 h-6'" class="text-base-content/40" />
          </div>
        </div>

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

        <div v-else-if="type === 'price'" class="flex items-center font-semibold" :class="compact ? 'text-xs' : 'text-sm'">
          <DollarSign :class="compact ? 'w-3 h-3 mr-0.5' : 'w-4 h-4 mr-1'" class="text-green-600" />
          <span class="tabular-nums">{{ formatPrice() }}</span>
        </div>

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

        <div v-else-if="type === 'chips' || type === 'tags'" class="flex flex-wrap gap-1">
          <div
            v-for="(chip, index) in getChipArray()"
            :key="index"
            class="badge badge-outline hover:badge-primary transition-colors duration-200"
            :class="compact ? 'badge-xs' : 'badge-sm'"
          >
            {{ chip }}
          </div>
        </div>

        <div v-else-if="isLongText" class="group">
          <div class="leading-relaxed break-words whitespace-pre-wrap" :class="compact ? 'text-xs' : 'text-sm'">
            <div v-if="!showFullText && !compact" class="inline">
              <span>{{ truncatedText }}</span>
              <button
                v-if="needsTruncation"
                @click.stop="showFullText = true"
                class="link link-primary ml-1 hover:link-secondary inline-block"
                :class="compact ? 'text-xs' : 'text-xs'"
              >
                {{ compact ? '...' : 'Show more' }}
              </button>
            </div>
            <div v-else class="whitespace-pre-wrap break-words">
              {{ value }}
              <button
                v-if="!compact && showFullText"
                @click.stop="showFullText = false"
                class="link link-primary hover:link-secondary inline-block ml-2"
                :class="compact ? 'text-xs' : 'text-xs'"
              >
                Show less
              </button>
            </div>
          </div>
        </div>

        <div v-else :class="compact ? 'text-xs' : 'text-sm'">
          <span v-if="hasValue">
            <span
              v-if="hasConditionalStyling"
              class="inline-flex items-center rounded transition-all duration-200 break-words"
              :class="[
                getConditionalClasses() || 'text-base-content',
                compact ? 'px-1 py-0.5 text-xs' : 'px-2 py-1 text-sm'
              ]"
            >
              {{ formatValue() }}
            </span>
            <template v-else>
              <div
                v-if="props.linkStyle === 'link-compact' && computedLinkUrl"
                class="inline-flex items-center rounded transition-all duration-200"
                :class="[computedLinkClasses, compact ? 'px-1 py-0.5 text-xs' : 'px-2 py-1 text-sm']"
              >
                <span class="text-base-content">{{ compact ? 'Link' : 'Open Link' }}</span>
                <ExternalLink :class="compact ? 'w-3 h-3 ml-0.5' : 'w-4 h-4 ml-1'" />
              </div>
              <span v-else class="text-base-content break-words">
                {{ formatValue() }}
              </span>
            </template>
          </span>
          <span v-else class="text-base-content/40 italic">
            {{ compact ? 'N/A' : 'No data' }}
          </span>
        </div>
      </template>
    </component>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { format, formatDistanceToNowStrict } from 'date-fns';
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
  Zap,
  User,
  Hash,
  Tag,
  Globe,
  Phone
} from 'lucide-vue-next';

interface ConditionalRule {
  operator: string;
  value?: any;
  min?: number;
  max?: number;
  classes: string;
}

interface Props {
  value: any;
  type?: string;
  options?: {
    conditionalStyling?: {
      conditions: Record<string, string>;
      default?: string;
    };
    advancedStyling?: {
      conditions: ConditionalRule[];
      default?: string;
    };
    enhanced?: boolean;
    truncate?: boolean;
    truncateLength?: number;
    compact?: boolean;
  };
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

const showFullText = ref(false);

// ===========================================================================
// COMPUTED PROPERTIES FOR SIMPLIFIED LOGIC
// ===========================================================================

const numericValue = computed<number | null>(() => {
  const num = parseFloat(props.value);
  return isNaN(num) ? null : num;
});

const booleanValue = computed<boolean>(() => {
  if (typeof props.value === 'boolean') return props.value;
  if (typeof props.value === 'string') {
    return ['true', '1', 'yes', 'on', 'active'].includes(props.value.toLowerCase());
  }
  return Boolean(props.value);
});

const isNumeric = computed<boolean>(() => numericValue.value !== null);

const hasValue = computed<boolean>(() => {
  return props.value !== null && props.value !== undefined && props.value !== '';
});

const hasConditionalStyling = computed<boolean>(() => {
  return !!(props.options.conditionalStyling || props.options.advancedStyling);
});

const shouldRenderAsBadge = computed<boolean>(() => {
  if (!hasValue.value) return false;

  const badgeTypes = ['status', 'badge', 'tag'];
  const statusFields = ['status', 'state', 'condition'];

  return (badgeTypes.includes(props.type || '') ||
         (hasConditionalStyling.value && statusFields.some(field =>
           String(props.value).toLowerCase().includes(field)
         ))) && hasConditionalStyling.value;
});

const isLongText = computed<boolean>(() => {
  const maxLength = props.options.truncateLength || 100;
  return props.type === 'text' && hasValue.value && String(props.value).length > maxLength && !props.compact;
});

const isUltraCompactLongText = computed<boolean>(() => {
  return props.ultraCompact && hasValue.value && String(props.value).length > 12;
});

const needsTruncation = computed<boolean>(() => {
  const maxLength = props.options.truncateLength || 100;
  return hasValue.value && String(props.value).length > maxLength && !props.compact;
});

const needsUltraCompactTruncation = computed<boolean>(() => {
  return props.ultraCompact && hasValue.value && String(props.value).length > 12;
});

const truncatedText = computed<string>(() => {
  if (!hasValue.value) return '';
  const text = String(props.value);
  const maxLength = props.compact ? 50 : (props.options.truncateLength || 100);
  return truncateText(text, maxLength);
});

// Link specific computed properties
const computedLinkUrl = computed<string | null>(() => {
  if (!props.link) return null;
  let url = props.link;
  if (typeof url === 'string') {
    url = url.replace('{value}', encodeURIComponent(props.value || ''));
  }
  return url;
});

const computedLinkTarget = computed<string>(() => {
  return props.linkTarget || '_self';
});

// Flag to prevent the wrapper `<a>` from conflicting with native links inside
const hasNativeLinkBehavior = computed<boolean>(() => {
  const typesWithOwnLinks = ['email', 'url'];
  return typesWithOwnLinks.includes(props.type || '');
});

// Classes for the main field container
const containerClasses = computed<string>(() => {
  const classes: string[] = [];
  if (props.options.enhanced && !props.compact) classes.push('enhanced-field');
  return classes.join(' ');
});

// Classes for the link wrapper if `linkUrl` is present and no native link behavior
const computedLinkClasses = computed<string>(() => {
  if (!computedLinkUrl.value || hasNativeLinkBehavior.value) return '';

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
    default: // 'default' or any unknown style
      return [...baseClasses, 'text-primary', 'hover:text-primary-focus'].join(' ');
  }
});


// ===========================================================================
// HELPER FUNCTIONS (composed for clarity and reusability)
// ===========================================================================

function handleWrapperClick(event: Event) {
  // Prevent propagation for non-link wrappers to avoid unintended parent clicks (e.g., row clicks)
  if (!computedLinkUrl.value || hasNativeLinkBehavior.value) {
    event.stopPropagation();
  }
  // If it's a valid link wrapper, let the browser handle it naturally.
}

// Model Search Functions
function getModelSearchData(): Record<string, any> {
  if (!props.value || typeof props.value !== 'object') return {};
  return Object.fromEntries(
    Object.entries(props.value).filter(([, value]) => value !== null && value !== undefined && value !== '')
  );
}

function getModelSearchSummary(): string {
  const data = getModelSearchData();
  const entries = Object.entries(data);
  if (entries.length === 0) return 'No data';
  return entries.map(([key, value]) => `${formatModelSearchKey(key)}: ${value}`).join(', ');
}

function formatModelSearchKey(key: string): string {
  return key
    .replace(/([A-Z])/g, ' $1')
    .replace(/[_-]/g, ' ')
    .replace(/\b\w/g, l => l.toUpperCase())
    .trim();
}

function getModelSearchIcon(key: string) {
  const normalizedKey = key.toLowerCase();
  const iconMap: Record<string, any> = {
    'name': User, 'username': User, 'user': User, 'email': Mail, 'mail': Mail, 'id': Hash, 'identifier': Hash,
    'tag': Tag, 'tags': Tag, 'category': Tag, 'status': CheckCircle, 'state': CheckCircle, 'url': Globe,
    'website': Globe, 'link': Globe, 'phone': Phone, 'telephone': Phone, 'mobile': Phone, 'date': Calendar,
    'created': Calendar, 'updated': Calendar, 'modified': Calendar,
  };
  return iconMap[normalizedKey] || Tag;
}

function getModelSearchBadgeColor(key: string): string {
  const normalizedKey = key.toLowerCase();
  const colorMap: Record<string, string> = {
    'name': 'bg-blue-100 text-blue-800 border border-blue-200', 'username': 'bg-blue-100 text-blue-800 border border-blue-200', 'user': 'bg-blue-100 text-blue-800 border border-blue-200',
    'email': 'bg-green-100 text-green-800 border border-green-200', 'mail': 'bg-green-100 text-green-800 border border-green-200',
    'id': 'bg-gray-100 text-gray-800 border border-gray-200', 'identifier': 'bg-gray-100 text-gray-800 border border-gray-200',
    'status': 'bg-yellow-100 text-yellow-800 border border-yellow-200', 'state': 'bg-yellow-100 text-yellow-800 border border-yellow-200',
    'tag': 'bg-purple-100 text-purple-800 border border-purple-200', 'tags': 'bg-purple-100 text-purple-800 border border-purple-200', 'category': 'bg-purple-100 text-purple-800 border border-purple-200',
    'url': 'bg-indigo-100 text-indigo-800 border border-indigo-200', 'website': 'bg-indigo-100 text-indigo-800 border border-indigo-200', 'link': 'bg-indigo-100 text-indigo-800 border border-indigo-200',
    'phone': 'bg-teal-100 text-teal-800 border border-teal-200', 'telephone': 'bg-teal-100 text-teal-800 border border-teal-200', 'mobile': 'bg-teal-100 text-teal-800 border border-teal-200',
    'date': 'bg-orange-100 text-orange-800 border border-orange-200', 'created': 'bg-orange-100 text-orange-800 border border-orange-200', 'updated': 'bg-orange-100 text-orange-800 border border-orange-200', 'modified': 'bg-orange-100 text-orange-800 border border-orange-200',
  };
  return colorMap[normalizedKey] || 'bg-gray-100 text-gray-800 border border-gray-200';
}

// Conditional Styling & Rules Evaluation
function evaluateCondition(condition: ConditionalRule): boolean {
  const value = numericValue.value !== null ? numericValue.value : props.value;

  switch (condition.operator) {
    case 'exists': return value !== null && value !== undefined && value !== '' && String(value).trim() !== '';
    case 'not_exists': return value === null || value === undefined || value === '' || String(value).trim() === '';
    case 'equals': return value == condition.value;
    case 'not_equals': return value != condition.value;
    case 'greater_than': return typeof value === 'number' && value > (condition.value as number);
    case 'greater_than_equal': return typeof value === 'number' && value >= (condition.value as number);
    case 'less_than': return typeof value === 'number' && value < (condition.value as number);
    case 'less_than_equal': return typeof value === 'number' && value <= (condition.value as number);
    case 'between': return typeof value === 'number' && value >= (condition.min as number) && value <= (condition.max as number);
    case 'contains': return String(value).toLowerCase().includes(String(condition.value).toLowerCase());
    case 'starts_with': return String(value).toLowerCase().startsWith(String(condition.value).toLowerCase());
    case 'ends_with': return String(value).toLowerCase().endsWith(String(condition.value).toLowerCase());
    default: return false;
  }
}

function getConditionalClasses(): string {
  if (!hasValue.value) return '';

  // Priority to advanced styling
  if (props.options.advancedStyling) {
    for (const condition of props.options.advancedStyling.conditions) {
      if (evaluateCondition(condition)) {
        return condition.classes;
      }
    }
    return props.options.advancedStyling.default || '';
  }

  // Fallback to simple conditional styling
  if (props.options.conditionalStyling) {
    const { conditions, default: defaultStyle } = props.options.conditionalStyling;
    const normalizedValue = String(props.value).toLowerCase().trim();
    return conditions[normalizedValue] || defaultStyle || '';
  }
  return '';
}

function getBooleanClasses(): string {
  return booleanValue.value
    ? 'bg-green-100 text-green-800 border border-green-200'
    : 'bg-red-100 text-red-800 border border-red-200';
}

function getProgressClasses(): string {
  const val = numericValue.value || 0;
  if (val >= 90) return 'bg-green-100 text-green-800';
  if (val >= 70) return 'bg-blue-100 text-blue-800';
  if (val >= 50) return 'bg-yellow-100 text-yellow-800';
  return 'bg-red-100 text-red-800';
}

function getProgressBarClasses(): string {
  const val = numericValue.value || 0;
  if (val >= 90) return 'bg-green-500';
  if (val >= 70) return 'bg-blue-500';
  if (val >= 50) return 'bg-yellow-500';
  return 'bg-red-500';
}

function getProgressLabel(): string {
  const val = numericValue.value || 0;
  if (val >= 90) return 'Excellent';
  if (val >= 70) return 'Good';
  if (val >= 50) return 'Average';
  return 'Poor';
}

function getProgressLabelShort(): string {
  const val = numericValue.value || 0;
  if (val >= 90) return 'Exc';
  if (val >= 70) return 'Good';
  if (val >= 50) return 'Avg';
  return 'Poor';
}

function getStatusIcon() {
  const normalizedValue = String(props.value || '').toLowerCase();
  const iconMap: Record<string, any> = {
    'unpatched': AlertTriangle, 'patched': CheckSquare, 'active': CheckCircle, 'inactive': XCircle,
    'pending': Clock, 'completed': CheckCircle, 'critical': Shield, 'high': Zap,
    'validated': CheckSquare, 'rejected': XCircle,
  };
  return iconMap[normalizedValue];
}

function getScoreLabel(): string {
  if (props.type !== 'number' && !isNumeric.value) return '';
  const val = numericValue.value || 0;
  if (val >= 9.0) return 'Critical';
  if (val >= 7.0) return 'High';
  if (val >= 4.0) return 'Medium';
  if (val > 0) return 'Low';
  return 'None';
}

// Formatting Functions
function formatValue(): string {
  if (!hasValue.value) return '';

  if (props.type === 'model_search') {
    const data = getModelSearchData();
    const entries = Object.entries(data);
    if (entries.length === 0) return 'No data';
    return entries.map(([key, value]) => `${formatModelSearchKey(key)}: ${value}`).join(', ');
  }
  return String(props.value);
}

function formatDate(isCompactMode: boolean): string {
  if (!props.value) return '';
  try {
    const date = new Date(props.value);
    const fmt = isCompactMode ? (props.type === 'timestamp' ? 'MM/dd/yy HH:mm' : 'MM/dd/yy') :
                                (props.type === 'timestamp' ? 'MMM dd, yyyy HH:mm' : 'MMM dd, yyyy');
    return format(date, fmt);
  } catch {
    return String(props.value);
  }
}

function formatDateHumanDiff(): string {
  if (!props.value) return '';
  try {
    const date = new Date(props.value);
    return formatDistanceToNowStrict(date, { addSuffix: true });
  } catch {
    return '';
  }
}

function formatPrice(): string {
  if (!isNumeric.value) return String(props.value);
  const options = props.compact
    ? { style: 'currency', currency: 'USD', notation: 'compact' } as const
    : { style: 'currency', currency: 'USD' } as const;
  return new Intl.NumberFormat('en-US', options).format(numericValue.value || 0);
}

function formatPriceCompact(): string {
  if (!isNumeric.value) return String(props.value);
  const val = numericValue.value || 0;
  if (val >= 1000000) return `${(val / 1000000).toFixed(1)}M`;
  if (val >= 1000) return `${(val / 1000).toFixed(1)}K`;
  return `${val}`;
}

function getDomainFromUrl(url: string): string {
  try {
    return new URL(url).hostname.replace('www.', '');
  } catch {
    return url;
  }
}

function truncateText(text: string, maxLength: number): string {
  if (!text) return '';
  const str = String(text);
  if (str.length <= maxLength) return str;

  const truncated = str.substring(0, maxLength);
  const lastSpace = truncated.lastIndexOf(' ');
  return (lastSpace > maxLength * 0.6) ? truncated.substring(0, lastSpace) + '…' : truncated + '…';
}

function truncateUrl(url: string, isCompactMode: boolean): string {
  if (!url) return '';
  const maxLength = isCompactMode ? 25 : 40;
  return url.length > maxLength ? url.substring(0, maxLength) + '...' : url;
}

function getChipArray(): string[] {
  if (!props.value) return [];
  let chips: string[] = [];

  if (Array.isArray(props.value)) {
    chips = props.value.map(String);
  } else if (typeof props.value === 'string') {
    chips = props.value.split(',').map(s => s.trim()).filter(Boolean);
  } else {
    chips = [String(props.value)];
  }
  // Return all chips for both compact and non-compact modes
  return chips;
}
</script>

<style scoped>
/* Base transitions for all interactable elements */
.field-renderer * {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Enhanced field animations */
.enhanced-field:hover {
  transform: translateY(-1px);
}

/* Base link styling to inherit color and remove default underline */
.field-link {
  text-decoration: none;
  color: inherit;
  display: block; /* Ensure it takes full width for click area */
}

/* Hover effects for the field-link wrapper */
.field-link:hover {
  opacity: 0.9;
  text-decoration: none;
}

.cursor-pointer {
  cursor: pointer;
}

/* DaisyUI Badge hover effects */
.badge:hover {
  transform: scale(1.03); /* Slightly less aggressive than 1.05 */
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

/* Progress bar transitions */
.progress-bar {
  transition: width 0.5s ease-in-out;
}

/* Default DaisyUI Link styling (for inner links not wrapped by field-link) */
.link {
  text-decoration: none; /* Override default underline */
}
.link:hover {
  text-decoration: underline;
  text-decoration-thickness: 2px;
  text-underline-offset: 2px;
}

/* Rating stars shadow */
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

/* Text expansion animations for smoother transitions */
.group span {
  transition: all 0.3s ease;
}

/* Bootstrap-like text wrapping and overflow handling */
.break-words {
  word-wrap: break-word;
  word-break: break-word;
  overflow-wrap: break-word;
  hyphens: auto;
}

.whitespace-pre-wrap {
  white-space: pre-wrap;
}

/* Ensure proper text flow in table cells */
.field-renderer {
  max-width: 100%;
  overflow: visible; /* Allow content to expand vertically */
}

/* Enhanced text container for better wrapping */
.group .leading-relaxed {
  width: 100%;
  max-width: 100%;
  overflow: visible; /* Allow multi-line content */
}

/* Compact mode specific enhancements */
.field-renderer .break-words {
  white-space: normal; /* Allow text to wrap naturally */
  line-height: 1.3; /* Better line spacing for readability */
}

/* Model search badges in compact mode */
.field-renderer .inline-flex .break-words {
  white-space: normal;
  word-break: break-word;
  overflow-wrap: break-word;
}

/* Allow badges to expand vertically when needed */
.badge, .badge-outline {
  white-space: normal;
  height: auto;
  min-height: 1.5rem;
  align-items: flex-start;
  padding-top: 0.25rem;
  padding-bottom: 0.25rem;
}

.badge-xs {
  min-height: 1.25rem;
  padding-top: 0.125rem;
  padding-bottom: 0.125rem;
}

.badge-sm {
  min-height: 1.5rem;
  padding-top: 0.25rem;
  padding-bottom: 0.25rem;
}

/* Button positioning for better flow */
.group .inline-block {
  vertical-align: baseline;
  margin-top: 0.25rem;
}

/* Tabular numbers for better alignment of figures */
.tabular-nums {
  font-variant-numeric: tabular-nums;
}

/* ===========================================================================
// ✨ ULTRA COMPACT MODE SPECIFIC STYLES - REBUILT FOR BETTER UX
// =========================================================================== */

.badge-micro {
  font-size: 0.5rem; /* 8px */
  padding: 0.0625rem 0.25rem; /* Tighter padding */
  height: auto; /* Allow height to grow with content */
  min-height: 0.875rem; /* 14px minimum */
  line-height: 1.2;
  border-radius: 0.125rem; /* 2px */
  white-space: normal; /* Allow text wrapping */
  overflow: visible; /* Show all content */
  text-overflow: clip; /* Don't use ellipsis */
  max-width: none; /* Remove width restriction */
  font-weight: 500;
  display: inline-flex; /* Use flex for icon alignment */
  align-items: center;
  gap: 0.1rem; /* Small gap between icon and text */
  word-wrap: break-word;
  word-break: break-word;
}

.progress-micro {
  height: 0.1875rem; /* 3px, slightly more visible */
  border-radius: 0.09375rem;
  background: hsl(var(--b3));
  overflow: hidden;
  margin: 0.125rem 0;
  width: 100%;
  flex: 1;
}

.progress-micro .progress-bar {
  height: 100%;
  border-radius: 0.09375rem;
}

.avatar-micro {
  width: 1rem !important; /* 16px */
  height: 1rem !important;
  border-radius: 0.125rem;
  object-fit: cover;
}

.boolean-indicator {
  width: 0.75rem; /* 12px */
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

.text-nano {
  font-size: 0.625rem; /* 10px */
  line-height: 1.2;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  text-rendering: optimizeLegibility;
  word-wrap: break-word;
  word-break: break-word;
  overflow-wrap: break-word;
  hyphens: auto;
}

/* Ultra compact layout containers */
.ultra-compact-inline {
  display: inline-flex;
  align-items: flex-start; /* Align to top for better text flow */
  gap: 0.125rem;
  max-width: 100%;
  flex-wrap: wrap; /* Allow wrapping if needed */
}

/* Better text wrapping for ultra compact elements */
.ultra-compact-default,
.ultra-compact-badge,
.ultra-compact-email,
.ultra-compact-url {
  max-width: 100%;
  overflow: visible;
}

.ultra-compact-text .ultra-compact-inline {
  display: block; /* Use block layout for better text flow */
}

.ultra-compact-text .text-nano {
  display: inline;
  white-space: normal;
}

.ultra-compact-expanded {
  position: absolute;
  top: 0;
  left: 0;
  z-index: 1000;
  background: hsl(var(--b1));
  border: 1px solid hsl(var(--b3));
  border-radius: 0.375rem;
  padding: 0.5rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  min-width: 200px;
  max-width: 300px;
  max-height: 200px;
  overflow: auto;
}

.ultra-compact-content {
  margin-bottom: 0.5rem;
  word-wrap: break-word;
  word-break: break-word;
  hyphens: auto;
}

.ultra-compact-btn {
  background: none;
  border: none;
  color: hsl(var(--p));
  cursor: pointer;
  font-size: 0.5rem;
  padding: 0.125rem;
  margin: 0;
  line-height: 1;
  border-radius: 0.125rem;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 0.75rem;
  height: 0.75rem;
}

.ultra-compact-btn:hover {
  background: hsl(var(--p) / 0.1);
  color: hsl(var(--pf));
  transform: scale(1.1);
}

.ultra-compact-close-btn {
  position: absolute;
  top: 0.25rem;
  right: 0.25rem;
  background: hsl(var(--n));
  color: hsl(var(--nc));
  border: none;
  border-radius: 0.25rem;
  cursor: pointer;
  font-size: 0.625rem;
  padding: 0.125rem 0.25rem;
  line-height: 1;
  font-weight: bold;
  transition: all 0.2s ease;
  width: 1.25rem;
  height: 1.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.ultra-compact-close-btn:hover {
  background: hsl(var(--nf));
  transform: scale(1.1);
}

/* Specific ultra compact component styles */
.ultra-compact-model-search,
.ultra-compact-chips,
.ultra-compact-text {
  position: relative;
}

.ultra-compact-badge,
.ultra-compact-boolean,
.ultra-compact-date,
.ultra-compact-email,
.ultra-compact-url,
.ultra-compact-media,
.ultra-compact-rating,
.ultra-compact-price,
.ultra-compact-default {
  display: inline-block;
}

.ultra-compact-progress {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  width: 100%;
  min-width: 3rem;
}

/* Enhanced tooltip for ultra compact elements */
[title] {
  cursor: help;
}

.text-nano .link {
  color: hsl(var(--p));
  text-decoration: none;
  position: relative;
  z-index: 10;
}

.text-nano .link:hover {
  text-decoration: underline;
  color: hsl(var(--pf));
}

/* Mobile responsiveness for ultra compact */
@media (max-width: 640px) {
  .ultra-compact-expanded {
    min-width: 180px;
    max-width: 250px;
    max-height: 150px;
    font-size: 0.55rem;
  }

  .ultra-compact-content {
    font-size: 0.55rem;
  }

  .badge-micro {
    font-size: 0.45rem;
    padding: 0.05rem 0.15rem;
    max-width: 3rem;
  }

  .text-nano {
    font-size: 0.55rem;
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

/* ===========================================================================
// RESPONSIVENESS & ACCESSIBILITY
// =========================================================================== */

/* Responsive compact adjustments (using Tailwind breakpoints implicitly) */
@media (max-width: 640px) { /* Equivalent to sm: breakpoint */
  .field-renderer:not(.compact):not(.ultraCompact) {
    /* Adjust general sizing for smaller screens if not in compact/ultra compact modes */
    font-size: 0.875rem; /* 14px */
  }

  /* Further compress ultra compact elements on very small screens */
  .ultraCompact .badge-micro {
    font-size: 0.45rem; /* 7.2px, even smaller */
    padding: 0.05rem 0.1rem;
    max-width: 3rem;
  }

  .ultraCompact .text-nano {
    font-size: 0.55rem; /* 8.8px */
  }

  .ultraCompact .boolean-indicator {
    width: 0.625rem;
    height: 0.625rem;
  }

  .ultraCompact .avatar-micro {
    width: 0.875rem !important;
    height: 0.875rem !important;
  }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .badge-micro, .badge {
    border: 1px solid currentColor; /* Use current text color for contrast */
  }
  .boolean-indicator {
    border: 1px solid currentColor;
  }
  .progress-micro {
    border: 1px solid currentColor;
  }
  .link, .field-link {
    outline: 2px solid; /* Stronger outline for links */
    outline-offset: 2px;
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .badge-micro, .boolean-indicator, .progress-bar,
  .badge, .avatar img, .link, .field-link,
  .transition-all, .transition-transform, .transition-opacity {
    transition: none !important; /* Disable all transitions */
    animation: none !important; /* Disable all animations */
  }

  .badge-micro:hover, .boolean-indicator:hover, .badge:hover,
  .avatar:hover img, .field-link:hover {
    transform: none !important; /* Disable hover transforms */
    opacity: 1 !important; /* Maintain full opacity */
  }
}

/* Print styles - ensure content is legible on paper */
@media print {
  .field-renderer {
    color: #000 !important; /* Force black text */
    background: #fff !important; /* Force white background */
  }

  .badge-micro, .badge {
    border: 1px solid #ccc !important;
    background: #f0f0f0 !important;
    color: #333 !important;
  }

  .boolean-true, .boolean-false {
    background: #ccc !important; /* Neutral color for print */
    color: #000 !important;
    border: 1px solid #999 !important;
  }

  .link, a {
    text-decoration: underline !important; /* Always show underlines for links */
    color: #0000ee !important; /* Standard blue link color */
  }

  .progress-bar {
    background: #ccc !important; /* Gray for progress bars */
  }

  .avatar img {
    border: 1px solid #eee; /* Light border for images */
  }
}

/* Enhanced focus indicators for keyboard navigation */
.field-renderer:focus-within {
  outline: 2px solid hsl(var(--p)); /* Stronger focus outline */
  outline-offset: 3px;
  border-radius: 0.375rem; /* Slightly larger border-radius for focus */
}
</style>
