<template>
  <!-- Table View -->
  <template v-if="viewType === 'table'">
    <td
      v-for="(column, index) in visibleColumns"
      :key="index"
      class="px-6 py-4 whitespace-nowrap transition-all duration-200 hover:bg-base-100/50 group"
      :class="getColumnAlignment(column.type)"
    >
      <div class="flex items-center min-h-[2.5rem]">
        <FieldRenderer
          :value="tableData[column.key]"
          :type="column.type || 'text'"
          :options="{
            ...column.options,
            enhanced: true,
            conditionalStyling: column.conditionalStyling,
            advancedStyling: column.advancedStyling
          }"
        />
      </div>
    </td>
  </template>

  <!-- Enhanced Card/List View -->
  <template v-else>
    <div class="card bg-base-100 shadow-md hover:shadow-xl transition-all duration-300 border border-base-200 hover:border-primary/20 group">
      <div class="card-body p-6">
        <!-- Header Section with Priority Fields -->
        <div class="flex items-start justify-between mb-4">
          <div class="flex-1">
            <h3 class="card-title text-lg font-bold text-base-content mb-2 group-hover:text-primary transition-colors duration-200">
              {{ getCardTitle() }}
            </h3>
            <div v-if="getCardSubtitle()" class="text-sm text-base-content/60">
              {{ getCardSubtitle() }}
            </div>
          </div>

          <!-- Quick Actions Badge with Enhanced Styling -->
          <div class="flex items-center gap-2">
            <div v-if="getStatusField()" class="transition-all duration-200 hover:scale-105">
              <FieldRenderer
                :value="getStatusField()"
                type="text"
                :options="{
                  enhanced: true,
                  conditionalStyling: getStatusColumn()?.conditionalStyling,
                  advancedStyling: getStatusColumn()?.advancedStyling
                }"
              />
            </div>
          </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
          <div
            v-for="(column, index) in visibleListColumns"
            :key="index"
            class="field-item group/item"
          >
            <div class="flex flex-col space-y-2 p-3 rounded-lg bg-base-50 hover:bg-base-100 border border-base-200/50 hover:border-primary/30 transition-all duration-200">
              <!-- Field Label with Icon -->
              <div class="flex items-center gap-2 text-xs font-semibold text-base-content/70 uppercase tracking-wide">
                <component
                  :is="getColumnIcon(column)"
                  v-if="getColumnIcon(column)"
                  class="w-4 h-4 text-primary/70 group-hover/item:text-primary transition-colors duration-200"
                />
                <span class="group-hover/item:text-base-content transition-colors duration-200">
                  {{ column.label }}
                </span>
              </div>

              <!-- Field Value with Enhanced Styling -->
              <div class="flex-1 min-h-[2rem] flex items-center">
                <FieldRenderer
                  :value="tableData[column.key]"
                  :type="column.type || 'text'"
                  :options="{
                    ...column.options,
                    truncate: false,
                    enhanced: true,
                    conditionalStyling: column.conditionalStyling,
                    advancedStyling: column.advancedStyling
                  }"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Expandable Section -->
        <div v-if="hasHiddenColumns" class="mt-6 pt-4 border-t border-base-200">
          <div class="collapse collapse-arrow bg-base-50">
            <input
              type="checkbox"
              class="peer"
              :checked="showAll"
              @change="toggleShowAll"
            />
            <div class="collapse-title text-sm font-medium text-primary flex items-center gap-2 hover:bg-base-100 transition-colors duration-200">
              <component :is="showAll ? ChevronUp : ChevronDown" class="w-4 h-4" />
              <span>
                {{ showAll ? 'Show Less' : `Show ${hiddenColumnsCount} More Fields` }}
              </span>
              <div class="badge badge-primary badge-sm ml-auto">
                +{{ hiddenColumnsCount }}
              </div>
            </div>

            <div class="collapse-content bg-base-100">
              <div class="pt-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
                  <div
                    v-for="(column, index) in hiddenColumns"
                    :key="`hidden-${index}`"
                    class="field-item-secondary"
                  >
                    <div class="flex flex-col space-y-1 p-2 rounded border border-base-200/30 hover:border-primary/20 bg-base-50/50 hover:bg-base-100/50 transition-all duration-200">
                      <span class="text-xs font-medium text-base-content/60 flex items-center gap-1">
                        <component
                          :is="getColumnIcon(column)"
                          v-if="getColumnIcon(column)"
                          class="w-3 h-3 text-primary/50"
                        />
                        {{ column.label }}
                      </span>
                      <div class="text-sm">
                        <FieldRenderer
                          :value="tableData[column.key]"
                          :type="column.type || 'text'"
                          :options="{
                            ...column.options,
                            enhanced: false,
                            conditionalStyling: column.conditionalStyling,
                            advancedStyling: column.advancedStyling
                          }"
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Card Footer with Metadata -->
        <div v-if="hasMetadata" class="mt-4 pt-4 border-t border-base-200/50">
          <div class="flex items-center justify-between text-xs text-base-content/50">
            <span v-if="tableData.created_at" class="flex items-center gap-1">
              <Calendar class="w-3 h-3" />
              Created {{ formatRelativeDate(tableData.created_at) }}
            </span>
            <span v-if="getIdValue()" class="font-mono">
              ID: {{ getIdValue() }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </template>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import FieldRenderer from './FieldRenderer.vue';
import { formatDistanceToNow } from 'date-fns';
import {
  Calendar,
  Hash,
  Type,
  Image as ImageIcon,
  Check,
  DollarSign,
  Star,
  Tag,
  ChevronDown,
  ChevronUp,
  Info,
  Clock,
  User,
  Mail,
  Phone,
  MapPin,
  Globe,
  Settings,
  Activity,
  Shield,
  Zap,
  Layers,
  AlertTriangle,
  CheckCircle,
  FileText,
  Database,
  Code,
  Bug
} from 'lucide-vue-next';

interface Column {
  key: string;
  label: string;
  type?: string;
  options?: Record<string, any>;
  priority?: number;
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
}

interface Props {
  tableData: Record<string, any>;
  columns: Column[];
  viewType?: 'table' | 'list';
  initialVisibleCount?: number;
  hiddenColumns?: Set<string>;
}

const props = withDefaults(defineProps<Props>(), {
  tableData: () => ({}),
  columns: () => [],
  viewType: 'table',
  initialVisibleCount: 6,
  hiddenColumns: () => new Set()
});

// State for show more/less functionality
const showAll = ref(false);

// Computed properties
const sortedColumns = computed(() => {
  return [...props.columns]
    .filter(column => !props.hiddenColumns.has(column.key))
    .sort((a, b) => {
      const priorityA = a.priority ?? 999;
      const priorityB = b.priority ?? 999;
      return priorityA - priorityB;
    });
});

const visibleColumns = computed(() => sortedColumns.value);

const visibleListColumns = computed(() => {
  if (showAll.value) return visibleColumns.value;
  return visibleColumns.value.slice(0, props.initialVisibleCount);
});

const hiddenColumns = computed(() => {
  if (showAll.value) return [];
  return visibleColumns.value.slice(props.initialVisibleCount);
});

const hasHiddenColumns = computed(() => {
  return visibleColumns.value.length > props.initialVisibleCount;
});

const hiddenColumnsCount = computed(() => {
  return Math.max(0, visibleColumns.value.length - props.initialVisibleCount);
});

const hasMetadata = computed(() => {
  return props.tableData.created_at || props.tableData.updated_at || getIdValue();
});

// Methods
function toggleShowAll() {
  showAll.value = !showAll.value;
}

// Handle fallback relationships - get the first non-empty value
function getFieldValue(column: Column): any {
  const key = column.key;

  // Handle fallback relationships (separated by |)
  if (key.includes('|')) {
    const fallbackKeys = key.split('|').map(k => k.trim());

    for (const fallbackKey of fallbackKeys) {
      const value = getNestedValue(props.tableData, fallbackKey);
      // More robust check for empty values
      if (value !== null && value !== undefined && value !== '' && value !== 0) {
        return value;
      }
    }
    return null; // All fallbacks were empty
  }

  // Regular field access
  return getNestedValue(props.tableData, key);
}

// Get nested value from object using dot notation
function getNestedValue(obj: any, path: string): any {
  if (!obj || !path) return null;

  return path.split('.').reduce((current, key) => {
    return current && current[key] !== undefined ? current[key] : null;
  }, obj);
}

// Get ID value (supports custom ID fields)
function getIdValue(): any {
  // Try to find ID column first
  const idColumn = props.columns.find(col =>
    col.key === 'id' ||
    col.key.endsWith('_id') ||
    col.label.toLowerCase().includes('id')
  );

  if (idColumn) {
    const value = props.tableData[idColumn.key];
    if (value !== null && value !== undefined) return value;
  }

  // Fallback to direct access
  return props.tableData.id || props.tableData.ID;
}

// Get status column for header badge styling
function getStatusColumn(): Column | undefined {
  const statusFields = ['status', 'state', 'condition', 'active'];

  return props.columns.find(column =>
    statusFields.some(field =>
      column.key.toLowerCase().includes(field) ||
      column.label.toLowerCase().includes(field)
    )
  );
}

// Enhanced icon detection with more specific mappings
function getColumnIcon(column: Column) {
  const iconMap: Record<string, any> = {
    // Field types
    date: Calendar,
    timestamp: Clock,
    number: Hash,
    text: Type,
    media: ImageIcon,
    boolean: Check,
    price: DollarSign,
    rating: Star,
    model_search: Tag,
    email: Mail,
    phone: Phone,
    url: Globe,
    location: MapPin,
    user: User,
    settings: Settings,
    activity: Activity,
    security: Shield,
    performance: Zap,
    category: Layers,

    // Security specific
    cvss: Shield,
    severity: AlertTriangle,
    status: Activity,
    vulnerability: Bug,
    component: Code,
    version: FileText,
    researcher: User,
    database: Database,

    // Default
    default: Info
  };

  // Check for special keywords in column key or label
  const keyLower = column.key.toLowerCase();
  const labelLower = column.label.toLowerCase();

  // Security field detection
  if (keyLower.includes('cvss') || labelLower.includes('cvss')) return Shield;
  if (keyLower.includes('severity') || labelLower.includes('severity')) return AlertTriangle;
  if (keyLower.includes('status') || labelLower.includes('status')) return Activity;
  if (keyLower.includes('priority') || labelLower.includes('priority')) return Star;
  if (keyLower.includes('vuln') || labelLower.includes('vulner')) return Bug;
  if (keyLower.includes('component') || labelLower.includes('comp_')) return Code;
  if (keyLower.includes('version') || labelLower.includes('version')) return FileText;
  if (keyLower.includes('researcher') || labelLower.includes('researcher')) return User;
  if (keyLower.includes('validation') || labelLower.includes('validation')) return CheckCircle;
  if (keyLower.includes('patch') || labelLower.includes('patch')) return Shield;
  if (keyLower.includes('contact') || labelLower.includes('contact')) return Mail;

  return iconMap[column.type || 'default'];
}

// Get column alignment based on type
function getColumnAlignment(type?: string): string {
  const rightAlignTypes = ['number', 'price', 'rating', 'cvss'];
  const centerAlignTypes = ['boolean', 'media', 'icon'];

  if (rightAlignTypes.includes(type || '')) return 'text-right';
  if (centerAlignTypes.includes(type || '')) return 'text-center';
  return 'text-left';
}

// Get card title from priority fields with better relationship support
function getCardTitle(): string {
  const titleFields = ['name', 'title', 'label', 'subject', 'heading', 'comp_name'];

  for (const field of titleFields) {
    // Check direct access first
    if (props.tableData[field]) {
      return String(props.tableData[field]);
    }

    // Check nested relationships with more flexible matching
    const column = props.columns.find(col => {
      const key = col.key.toLowerCase();
      return key.endsWith(`.${field}`) ||
             key === field ||
             key.includes(`_${field}`) ||
             key.includes(`${field}_`);
    });

    if (column) {
      const value = props.tableData[column.key];
      if (value && String(value).trim()) return String(value);
    }
  }

  return `Item #${getIdValue() || 'Unknown'}`;
}

// Get card subtitle with better relationship support
function getCardSubtitle(): string {
  const subtitleFields = ['description', 'subtitle', 'summary', 'excerpt', 'researcher', 'type'];

  for (const field of subtitleFields) {
    // Check direct access first
    if (props.tableData[field]) {
      const text = String(props.tableData[field]);
      return text.length > 100 ? text.substring(0, 100) + '...' : text;
    }

    // Check nested relationships with more flexible matching
    const column = props.columns.find(col => {
      const key = col.key.toLowerCase();
      return key.endsWith(`.${field}`) ||
             key === field ||
             key.includes(`_${field}`) ||
             key.includes(`${field}_`);
    });

    if (column) {
      const value = props.tableData[column.key];
      if (value && String(value).trim()) {
        const text = String(value);
        return text.length > 100 ? text.substring(0, 100) + '...' : text;
      }
    }
  }

  return '';
}

// Get status field for badge with improved detection
function getStatusField(): string {
  const statusFields = ['status', 'state', 'condition', 'active'];

  for (const field of statusFields) {
    // Check direct access first
    if (props.tableData[field] !== undefined && props.tableData[field] !== null) {
      return String(props.tableData[field]);
    }

    // Check nested relationships and columns with improved matching
    const column = props.columns.find(col => {
      const key = col.key.toLowerCase();
      const label = col.label.toLowerCase();
      return key.includes(field) ||
             label.includes(field) ||
             key === field ||
             label === field;
    });

    if (column) {
      const value = props.tableData[column.key];
      if (value !== null && value !== undefined && String(value).trim()) {
        return String(value);
      }
    }
  }

  return '';
}

// Format relative date with error handling
function formatRelativeDate(date: string): string {
  try {
    if (!date) return 'Unknown';
    const parsedDate = new Date(date);
    if (isNaN(parsedDate.getTime())) return 'Invalid date';
    return formatDistanceToNow(parsedDate, { addSuffix: true });
  } catch (error) {
    console.warn('Date formatting error:', error);
    return 'Unknown';
  }
}
</script>

<style scoped>
/* Enhanced field item animations */
.field-item {
  opacity: 0;
  transform: translateY(20px);
  animation: slideInUp 0.3s ease-out forwards;
}

.field-item:nth-child(1) { animation-delay: 0.05s; }
.field-item:nth-child(2) { animation-delay: 0.1s; }
.field-item:nth-child(3) { animation-delay: 0.15s; }
.field-item:nth-child(4) { animation-delay: 0.2s; }
.field-item:nth-child(5) { animation-delay: 0.25s; }
.field-item:nth-child(6) { animation-delay: 0.3s; }

.field-item-secondary {
  opacity: 0;
  transform: translateY(10px);
  animation: slideInUp 0.2s ease-out forwards;
}

@keyframes slideInUp {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Enhanced hover effects */
.card:hover {
  transform: translateY(-2px);
}

/* Custom collapse animation */
.collapse-content {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Smooth table cell transitions */
td {
  position: relative;
  overflow: hidden;
}

td::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
  transition: left 0.5s ease;
}

td:hover::before {
  left: 100%;
}

/* Enhanced grid responsiveness */
@media (max-width: 640px) {
  .grid {
    grid-template-columns: 1fr;
  }
}

@media (min-width: 1280px) {
  .xl\:grid-cols-3 {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

/* Custom scrollbar for overflow content */
.overflow-auto::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}

.overflow-auto::-webkit-scrollbar-track {
  background: hsl(var(--b3));
  border-radius: 3px;
}

.overflow-auto::-webkit-scrollbar-thumb {
  background: hsl(var(--bc) / 0.2);
  border-radius: 3px;
}

.overflow-auto::-webkit-scrollbar-thumb:hover {
  background: hsl(var(--bc) / 0.3);
}

/* Enhanced badge styling */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 200ms;
}

/* Conditional styling support */
.field-renderer {
  width: 100%;
}

/* Error handling for missing components */
.field-item .w-4,
.field-item .w-3 {
  flex-shrink: 0;
}
</style>
