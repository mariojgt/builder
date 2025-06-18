<template>
  <!-- Table View -->
  <template v-if="viewType === 'table'">
    <td
      v-for="(column, index) in displayColumns"
      :key="index"
      class="whitespace-nowrap transition-all duration-200 hover:bg-base-100/50 group"
      :class="[
        getColumnAlignment(column.type),
        getCompactPadding()
      ]"
    >
      <div class="flex items-center" :class="getCompactHeight()">
        <FieldRenderer
          :value="tableData[column.key]"
          :type="column.type || 'text'"
          :options="{
            ...column.options,
            enhanced: !compactMode,
            conditionalStyling: column.conditionalStyling,
            advancedStyling: column.advancedStyling,
            compact: compactMode
          }"
          :link="getFieldLink(column.key)"
          :link-target="getFieldLinkTarget(column.key)"
          :link-style="getFieldLinkStyle(column.key)"
          :row-data="tableData"
          :compact="compactMode"
          :ultraCompact="superCompactMode"
        />
      </div>
    </td>
  </template>

  <!-- Enhanced Card/List View -->
  <template v-else>
    <div
      class="card bg-base-100 shadow-md hover:shadow-xl transition-all duration-300 border border-base-200 hover:border-primary/20 group"
      :class="getCardCompactClasses()"
    >
      <div class="card-body" :class="getCardPadding()">
        <!-- Header Section with Priority Fields -->
        <div class="flex items-start justify-between" :class="getHeaderMargin()">
          <div class="flex-1">
            <h3
              class="card-title font-bold text-base-content group-hover:text-primary transition-colors duration-200"
              :class="getTitleClasses()"
            >
              {{ getCardTitle() }}
            </h3>
            <div
              v-if="getCardSubtitle()"
              class="text-base-content/60"
              :class="getSubtitleClasses()"
            >
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
                  enhanced: !compactMode,
                  conditionalStyling: getStatusColumn()?.conditionalStyling,
                  advancedStyling: getStatusColumn()?.advancedStyling,
                  compact: compactMode
                }"
                :link="getFieldLink(getStatusColumn()?.key)"
                :link-target="getFieldLinkTarget(getStatusColumn()?.key)"
                :row-data="tableData"
                :compact="compactMode"
                :ultraCompact="superCompactMode"
              />
            </div>
          </div>
        </div>

        <!-- Main Content Grid -->
        <div
          class="grid gap-3"
          :class="getGridClasses()"
        >
          <div
            v-for="(column, index) in visibleListColumns"
            :key="index"
            class="field-item group/item"
          >
            <div
              class="flex flex-col space-y-1 rounded-lg bg-base-50 hover:bg-base-100 border border-base-200/50 hover:border-primary/30 transition-all duration-200"
              :class="getFieldPadding()"
            >
              <!-- Field Label with Icon -->
              <div
                class="flex items-center gap-2 font-semibold text-base-content/70 uppercase tracking-wide"
                :class="getLabelClasses()"
              >
                <component
                  :is="getColumnIcon(column)"
                  v-if="getColumnIcon(column) && !superCompactMode"
                  :class="getIconClasses()"
                  class="text-primary/70 group-hover/item:text-primary transition-colors duration-200"
                />
                <span class="group-hover/item:text-base-content transition-colors duration-200">
                  {{ superCompactMode ? getAbbreviatedLabel(column.label) : column.label }}
                </span>
              </div>

              <!-- Field Value with Enhanced Styling -->
              <div
                class="flex-1 flex items-center"
                :class="getValueHeight()"
              >
                <FieldRenderer
                  :value="tableData[column.key]"
                  :type="column.type || 'text'"
                  :options="{
                    ...column.options,
                    truncate: false,
                    enhanced: !compactMode,
                    conditionalStyling: column.conditionalStyling,
                    advancedStyling: column.advancedStyling,
                    compact: compactMode
                  }"
                  :link="getFieldLink(column.key)"
                  :link-target="getFieldLinkTarget(column.key)"
                  :row-data="tableData"
                  :compact="compactMode"
                  :ultraCompact="superCompactMode"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Expandable Section -->
        <div
          v-if="hasHiddenColumns"
          class="border-t border-base-200"
          :class="getExpandableMargin()"
        >
          <div class="collapse collapse-arrow bg-base-50">
            <input
              type="checkbox"
              class="peer"
              :checked="showAll"
              @change="toggleShowAll"
            />
            <div
              class="collapse-title font-medium text-primary flex items-center gap-2 hover:bg-base-100 transition-colors duration-200"
              :class="getCollapseClasses()"
            >
              <component :is="showAll ? ChevronUp : ChevronDown" class="w-4 h-4" />
              <span>
                {{ showAll ? 'Show Less' : `Show ${hiddenColumnsCount} More Fields` }}
              </span>
              <div class="badge badge-primary ml-auto" :class="getBadgeClasses()">
                +{{ hiddenColumnsCount }}
              </div>
            </div>

            <div class="collapse-content bg-base-100">
              <div :class="getCollapseContentPadding()">
                <div
                  class="grid gap-2"
                  :class="getHiddenGridClasses()"
                >
                  <div
                    v-for="(column, index) in hiddenColumns"
                    :key="`hidden-${index}`"
                    class="field-item-secondary"
                  >
                    <div
                      class="flex flex-col space-y-1 rounded border border-base-200/30 hover:border-primary/20 bg-base-50/50 hover:bg-base-100/50 transition-all duration-200"
                      :class="getHiddenFieldPadding()"
                    >
                      <span
                        class="font-medium text-base-content/60 flex items-center gap-1"
                        :class="getHiddenLabelClasses()"
                      >
                        <component
                          :is="getColumnIcon(column)"
                          v-if="getColumnIcon(column)"
                          class="text-primary/50"
                          :class="getHiddenIconClasses()"
                        />
                        {{ superCompactMode ? getAbbreviatedLabel(column.label) : column.label }}
                      </span>
                      <div :class="getHiddenValueClasses()">
                        <FieldRenderer
                          :value="tableData[column.key]"
                          :type="column.type || 'text'"
                          :options="{
                            ...column.options,
                            enhanced: false,
                            conditionalStyling: column.conditionalStyling,
                            advancedStyling: column.advancedStyling,
                            compact: compactMode
                          }"
                          :link="getFieldLink(column.key)"
                          :link-target="getFieldLinkTarget(column.key)"
                          :row-data="tableData"
                          :compact="compactMode"
                          :ultraCompact="superCompactMode"
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
        <div
          v-if="hasMetadata"
          class="border-t border-base-200/50"
          :class="getFooterMargin()"
        >
          <div
            class="flex items-center justify-between text-base-content/50"
            :class="getFooterTextClasses()"
          >
            <span v-if="tableData.created_at" class="flex items-center gap-1">
              <Calendar :class="getFooterIconClasses()" />
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
  compactMode?: boolean;
  superCompactMode?: boolean;
  columnOrder?: string[];
}

const props = withDefaults(defineProps<Props>(), {
  tableData: () => ({}),
  columns: () => [],
  viewType: 'table',
  initialVisibleCount: 6,
  hiddenColumns: () => new Set(),
  compactMode: false,
  superCompactMode: false,
  columnOrder: () => []
});

// State for show more/less functionality
const showAll = ref(false);

// Computed property for ordered and filtered columns
const displayColumns = computed(() => {
  const columnMap = new Map(props.columns.map(col => [col.key, col]));

  let orderedColumns: Column[];

  if (props.columnOrder.length > 0) {
    const ordered = props.columnOrder
      .map(key => columnMap.get(key))
      .filter(Boolean) as Column[];

    const orderedKeys = new Set(props.columnOrder);
    const newColumns = props.columns.filter(col => !orderedKeys.has(col.key));

    orderedColumns = [...ordered, ...newColumns];
  } else {
    orderedColumns = [...props.columns].sort((a, b) => {
      const priorityA = a.priority ?? 999;
      const priorityB = b.priority ?? 999;
      return priorityA - priorityB;
    });
  }

  return orderedColumns.filter(column => !props.hiddenColumns.has(column.key));
});

const visibleListColumns = computed(() => {
  if (showAll.value) return displayColumns.value;
  const count = props.superCompactMode ? 12 : (props.compactMode ? 8 : props.initialVisibleCount);
  return displayColumns.value.slice(0, count);
});

const hiddenColumns = computed(() => {
  if (showAll.value) return [];
  const count = props.superCompactMode ? 12 : (props.compactMode ? 8 : props.initialVisibleCount);
  return displayColumns.value.slice(count);
});

const hasHiddenColumns = computed(() => {
  const count = props.superCompactMode ? 12 : (props.compactMode ? 8 : props.initialVisibleCount);
  return displayColumns.value.length > count;
});

const hiddenColumnsCount = computed(() => {
  const count = props.superCompactMode ? 12 : (props.compactMode ? 8 : props.initialVisibleCount);
  return Math.max(0, displayColumns.value.length - count);
});

const hasMetadata = computed(() => {
  return props.tableData.created_at || props.tableData.updated_at || getIdValue();
});

// ✨ COMPACT MODE STYLING METHODS
function getCompactPadding(): string {
  if (props.superCompactMode) return 'px-1 py-0.5';
  if (props.compactMode) return 'px-2 py-1';
  return 'px-6 py-4';
}

function getCompactHeight(): string {
  if (props.superCompactMode) return 'min-h-[1.25rem]';
  if (props.compactMode) return 'min-h-[1.5rem]';
  return 'min-h-[2.5rem]';
}

function getCardCompactClasses(): string {
  return props.superCompactMode ? 'card-super-compact' : (props.compactMode ? 'card-compact' : '');
}

function getCardPadding(): string {
  if (props.superCompactMode) return 'p-2';
  if (props.compactMode) return 'p-4';
  return 'p-6';
}

function getHeaderMargin(): string {
  if (props.superCompactMode) return 'mb-1';
  if (props.compactMode) return 'mb-2';
  return 'mb-4';
}

function getTitleClasses(): string {
  if (props.superCompactMode) return 'text-sm mb-0.5';
  if (props.compactMode) return 'text-base mb-1';
  return 'text-lg mb-2';
}

function getSubtitleClasses(): string {
  if (props.superCompactMode) return 'text-xs';
  if (props.compactMode) return 'text-xs';
  return 'text-sm';
}

function getGridClasses(): string {
  if (props.superCompactMode) return 'grid-cols-1 sm:grid-cols-4 xl:grid-cols-6';
  if (props.compactMode) return 'grid-cols-1 sm:grid-cols-3 xl:grid-cols-4';
  return 'grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4';
}

function getFieldPadding(): string {
  if (props.superCompactMode) return 'p-1';
  if (props.compactMode) return 'p-2';
  return 'p-3 space-y-2';
}

function getLabelClasses(): string {
  if (props.superCompactMode) return 'text-xs';
  return 'text-xs';
}

function getIconClasses(): string {
  if (props.compactMode) return 'w-3 h-3';
  return 'w-4 h-4';
}

function getValueHeight(): string {
  if (props.superCompactMode) return 'min-h-[1rem]';
  if (props.compactMode) return 'min-h-[1.25rem]';
  return 'min-h-[2rem]';
}

function getExpandableMargin(): string {
  if (props.superCompactMode) return 'mt-1 pt-1';
  if (props.compactMode) return 'mt-3 pt-2';
  return 'mt-6 pt-4';
}

function getCollapseClasses(): string {
  if (props.superCompactMode) return 'text-xs';
  if (props.compactMode) return 'text-xs';
  return 'text-sm';
}

function getBadgeClasses(): string {
  if (props.superCompactMode) return 'badge-xs';
  if (props.compactMode) return 'badge-xs';
  return 'badge-sm';
}

function getCollapseContentPadding(): string {
  if (props.superCompactMode) return 'pt-1';
  if (props.compactMode) return 'pt-2';
  return 'pt-4';
}

function getHiddenGridClasses(): string {
  if (props.superCompactMode) return 'grid-cols-1 sm:grid-cols-5 xl:grid-cols-7';
  if (props.compactMode) return 'grid-cols-1 sm:grid-cols-4 xl:grid-cols-5';
  return 'grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3';
}

function getHiddenFieldPadding(): string {
  if (props.superCompactMode) return 'p-0.5';
  if (props.compactMode) return 'p-1';
  return 'p-2';
}

function getHiddenLabelClasses(): string {
  if (props.superCompactMode) return 'text-xs';
  return 'text-xs';
}

function getHiddenIconClasses(): string {
  if (props.superCompactMode) return 'w-2 h-2';
  if (props.compactMode) return 'w-2 h-2';
  return 'w-3 h-3';
}

function getHiddenValueClasses(): string {
  if (props.superCompactMode) return 'text-xs';
  if (props.compactMode) return 'text-xs';
  return 'text-sm';
}

function getFooterMargin(): string {
  if (props.superCompactMode) return 'mt-1 pt-1';
  if (props.compactMode) return 'mt-2 pt-2';
  return 'mt-4 pt-4';
}

function getFooterTextClasses(): string {
  if (props.superCompactMode) return 'text-xs';
  return 'text-xs';
}

function getFooterIconClasses(): string {
  if (props.superCompactMode) return 'w-2 h-2';
  if (props.compactMode) return 'w-2 h-2';
  return 'w-3 h-3';
}

function getAbbreviatedLabel(label: string): string {
  const abbreviations: Record<string, string> = {
    'Vulnerability Name': 'Name',
    'Severity': 'Sev',
    'CVSS Score': 'CVSS',
    'Status': 'Stat',
    'Component': 'Comp',
    'Version': 'Ver',
    'Discovered': 'Found',
    'Researcher': 'Author',
    'Description': 'Desc',
    'Created At': 'Created',
    'Updated At': 'Updated',
    'Priority': 'Pri',
    'Category': 'Cat',
    'Environment': 'Env'
  };

  return abbreviations[label] || (label.length > 6 ? label.slice(0, 4) : label);
}

// Methods (unchanged from original)
function toggleShowAll() {
  showAll.value = !showAll.value;
}

function getFieldLink(fieldKey?: string): string | null {
  if (!fieldKey) return null;
  const linkKey = `${fieldKey}_link`;
  const linkData = props.tableData[linkKey];
  if (!linkData) return null;
  if (typeof linkData === 'object' && linkData.url) return linkData.url;
  if (typeof linkData === 'string') return linkData;
  return null;
}

function getFieldLinkTarget(fieldKey?: string): string {
  if (!fieldKey) return '_self';
  const linkKey = `${fieldKey}_link`;
  const linkData = props.tableData[linkKey];
  if (linkData !== null && typeof linkData === 'object' && linkData.target) {
    return linkData.target;
  }
  const targetKey = `${fieldKey}_target`;
  return props.tableData[targetKey] || '_self';
}

function getFieldLinkStyle(fieldKey?: string): string {
  if (!fieldKey) return 'default';
  const linkKey = `${fieldKey}_link`;
  const linkData = props.tableData[linkKey];
  if (linkData !== null && typeof linkData === 'object' && linkData.style) {
    return linkData.style;
  }
  const styleKey = `${fieldKey}_style`;
  return props.tableData[styleKey] || 'default';
}

function getIdValue(): any {
  const idColumn = props.columns.find(col =>
    col.key === 'id' ||
    col.key.endsWith('_id') ||
    col.label.toLowerCase().includes('id')
  );
  if (idColumn) {
    const value = props.tableData[idColumn.key];
    if (value !== null && value !== undefined) return value;
  }
  return props.tableData.id || props.tableData.ID;
}

function getStatusColumn(): Column | undefined {
  const statusFields = ['status', 'state', 'condition', 'active'];
  return props.columns.find(column =>
    statusFields.some(field =>
      column.key.toLowerCase().includes(field) ||
      column.label.toLowerCase().includes(field)
    )
  );
}

function getColumnIcon(column: Column) {
  const iconMap: Record<string, any> = {
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
    cvss: Shield,
    severity: AlertTriangle,
    status: Activity,
    vulnerability: Bug,
    component: Code,
    version: FileText,
    researcher: User,
    database: Database,
    default: Info
  };

  const keyLower = column.key.toLowerCase();
  const labelLower = column.label.toLowerCase();

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

function getColumnAlignment(type?: string): string {
  const rightAlignTypes = ['number', 'price', 'rating', 'cvss'];
  const centerAlignTypes = ['boolean', 'media', 'icon'];
  if (rightAlignTypes.includes(type || '')) return 'text-right';
  if (centerAlignTypes.includes(type || '')) return 'text-center';
  return 'text-left';
}

function getCardTitle(): string {
  const titleFields = ['name', 'title', 'label', 'subject', 'heading', 'comp_name'];
  for (const field of titleFields) {
    if (props.tableData[field]) {
      return String(props.tableData[field]);
    }
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

function getCardSubtitle(): string {
  const subtitleFields = ['description', 'subtitle', 'summary', 'excerpt', 'researcher', 'type'];
  for (const field of subtitleFields) {
    if (props.tableData[field]) {
      const text = String(props.tableData[field]);
      const maxLength = props.superCompactMode ? 40 : (props.compactMode ? 60 : 100);
      return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
    }
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
        const maxLength = props.superCompactMode ? 40 : (props.compactMode ? 60 : 100);
        return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
      }
    }
  }
  return '';
}

function getStatusField(): string {
  const statusFields = ['status', 'state', 'condition', 'active'];
  for (const field of statusFields) {
    if (props.tableData[field] !== undefined && props.tableData[field] !== null) {
      return String(props.tableData[field]);
    }
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

/* Super compact mode specific styles */
.card-super-compact {
  font-size: 0.75rem;
  line-height: 1.2;
}

.card-super-compact .card-body {
  font-size: 0.75rem;
}

.card-super-compact .field-item {
  animation-delay: 0.01s;
}

/* Compact mode specific styles */
.card-compact .card-body {
  font-size: 0.875rem;
}

.card-compact .field-item {
  animation-delay: 0.02s;
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

/* Enhanced grid responsiveness for compact modes */
@media (max-width: 640px) {
  .grid {
    grid-template-columns: 1fr;
  }
}

@media (min-width: 1280px) {
  .xl\:grid-cols-4 {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }

  .xl\:grid-cols-5 {
    grid-template-columns: repeat(5, minmax(0, 1fr));
  }

  .xl\:grid-cols-6 {
    grid-template-columns: repeat(6, minmax(0, 1fr));
  }

  .xl\:grid-cols-7 {
    grid-template-columns: repeat(7, minmax(0, 1fr));
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

/* Error handling for missing components */
.field-item .w-4,
.field-item .w-3,
.field-item .w-2 {
  flex-shrink: 0;
}

/* Super compact mode text scaling */
@media (max-width: 768px) {
  .card-super-compact {
    font-size: 0.6875rem;
  }

  .card-compact {
    font-size: 0.8rem;
  }
}

/* Performance optimizations for super compact mode */
.card-super-compact * {
  contain: layout style;
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .field-item,
  .field-item-secondary {
    animation: none;
    opacity: 1;
    transform: none;
  }

  .card:hover {
    transform: none;
  }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .border-base-200 {
    border-color: hsl(var(--bc));
  }

  .bg-base-50 {
    background-color: hsl(var(--b2));
  }
}

/* Print styles */
@media print {
  .card {
    break-inside: avoid;
    box-shadow: none;
    border: 1px solid #000;
  }

  .field-item {
    animation: none;
    opacity: 1;
    transform: none;
  }
}
</style>
