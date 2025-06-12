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
          :options="{ ...column.options, enhanced: true }"
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

          <!-- Quick Actions Badge -->
          <div class="flex items-center gap-2">
            <div v-if="getStatusField()" class="badge badge-sm" :class="getStatusBadgeClass()">
              {{ getStatusField() }}
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

              <!-- Field Value -->
              <div class="flex-1 min-h-[2rem] flex items-center">
                <FieldRenderer
                  :value="tableData[column.key]"
                  :type="column.type || 'text'"
                  :options="{
                    ...column.options,
                    truncate: false,
                    enhanced: true
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
                          :options="{ ...column.options, enhanced: false }"
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
            <span v-if="tableData.id" class="font-mono">
              ID: {{ tableData.id }}
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
  Layers
} from 'lucide-vue-next';

interface Column {
  key: string;
  label: string;
  type?: string;
  options?: Record<string, any>;
  priority?: number;
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
  return props.tableData.created_at || props.tableData.updated_at || props.tableData.id;
});

// Helper function to get appropriate icon for column type
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
    default: Info
  };
  return iconMap[column.type || 'default'];
}

// Get column alignment based on type
function getColumnAlignment(type?: string): string {
  const rightAlignTypes = ['number', 'price', 'rating'];
  const centerAlignTypes = ['boolean', 'media', 'icon'];

  if (rightAlignTypes.includes(type || '')) return 'text-right';
  if (centerAlignTypes.includes(type || '')) return 'text-center';
  return 'text-left';
}

// Get card title from priority fields
function getCardTitle(): string {
  const titleFields = ['name', 'title', 'label', 'subject', 'heading'];
  for (const field of titleFields) {
    if (props.tableData[field]) {
      return String(props.tableData[field]);
    }
  }
  return `Item #${props.tableData.id || 'Unknown'}`;
}

// Get card subtitle
function getCardSubtitle(): string {
  const subtitleFields = ['description', 'subtitle', 'summary', 'excerpt'];
  for (const field of subtitleFields) {
    if (props.tableData[field]) {
      const text = String(props.tableData[field]);
      return text.length > 100 ? text.substring(0, 100) + '...' : text;
    }
  }
  return '';
}

// Get status field for badge
function getStatusField(): string {
  const statusFields = ['status', 'state', 'condition', 'active'];
  for (const field of statusFields) {
    if (props.tableData[field] !== undefined) {
      return String(props.tableData[field]);
    }
  }
  return '';
}

// Get status badge class
function getStatusBadgeClass(): string {
  const status = getStatusField().toLowerCase();
  const statusClasses: Record<string, string> = {
    'active': 'badge-success',
    'inactive': 'badge-error',
    'pending': 'badge-warning',
    'draft': 'badge-info',
    'published': 'badge-success',
    'archived': 'badge-neutral',
    'completed': 'badge-success',
    'in-progress': 'badge-warning',
    'cancelled': 'badge-error',
    'true': 'badge-success',
    'false': 'badge-error'
  };
  return statusClasses[status] || 'badge-neutral';
}

// Format relative date
function formatRelativeDate(date: string): string {
  try {
    return formatDistanceToNow(new Date(date), { addSuffix: true });
  } catch {
    return 'Unknown';
  }
}

// Methods
function toggleShowAll() {
  showAll.value = !showAll.value;
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
</style>
