<template>
  <!-- Modal -->
  <div v-if="showModal" class="fixed inset-0 z-[10000] flex items-center justify-center p-4">
    <!-- Backdrop -->
    <div
      class="absolute inset-0 bg-black/50 backdrop-blur-sm"
      @click="closeModal"
    ></div>

    <!-- Modal Content -->
    <div class="relative bg-base-100 rounded-lg shadow-xl border border-base-300 w-full max-w-3xl max-h-[90vh] flex flex-col">
      <!-- Minimal Header -->
      <div class="flex items-center justify-between p-4 border-b border-base-300">
        <div>
          <h3 class="text-lg font-semibold text-base-content">Table Settings</h3>
          <p class="text-sm text-base-content/60">{{ visibleCount }} of {{ totalColumns }} columns visible</p>
        </div>
        <button
          @click="closeModal"
          class="btn btn-sm btn-ghost btn-circle"
          aria-label="Close"
        >
          <XIcon class="w-4 h-4" />
        </button>
      </div>

      <!-- Tabs -->
      <div class="px-4 pt-4">
        <div class="tabs tabs-boxed">
          <button
            @click="activeTab = 'columns'"
            class="tab"
            :class="{ 'tab-active': activeTab === 'columns' }"
          >
            Columns
          </button>
          <button
            @click="activeTab = 'hidden'"
            class="tab"
            :class="{ 'tab-active': activeTab === 'hidden' }"
          >
            Hidden Fields
            <span v-if="availableHiddenFields.length > 0" class="badge badge-sm ml-1">{{ enabledHiddenFields.size }}</span>
          </button>
          <button
            @click="activeTab = 'display'"
            class="tab"
            :class="{ 'tab-active': activeTab === 'display' }"
          >
            Display
          </button>
        </div>
      </div>

      <!-- Tab Content -->
      <div class="flex-1 overflow-hidden">
        <!-- Columns Tab -->
        <div v-show="activeTab === 'columns'" class="h-full flex flex-col">
          <!-- Actions -->
          <div class="p-4 border-b border-base-300">
            <div class="flex gap-2 flex-wrap">
              <button @click="showAllColumns" class="btn btn-sm btn-success">Show All</button>
              <button @click="hideAllColumns" class="btn btn-sm btn-warning">Hide All</button>
              <button @click="resetToDefault" class="btn btn-sm btn-ghost">Reset</button>
              <button @click="autoArrangeColumns" class="btn btn-sm btn-info">Auto Arrange</button>
            </div>
          </div>

          <!-- Columns List -->
          <div class="flex-1 overflow-y-auto p-4">
            <div class="space-y-2">
              <div
                v-for="(column, index) in orderedColumns"
                :key="column.key"
                class="flex items-center gap-3 p-3 border border-base-300 rounded-lg hover:bg-base-50"
                :class="{
                  'opacity-50': isDragging && draggedIndex === index,
                  'border-primary bg-primary/5': isDragging && dragOverIndex === index
                }"
                :draggable="true"
                @dragstart="handleDragStart($event, index)"
                @dragover.prevent="handleDragOver($event, index)"
                @dragenter.prevent="handleDragEnter(index)"
                @drop="handleDrop($event, index)"
                @dragend="handleDragEnd"
              >
                <!-- Drag Handle -->
                <div class="cursor-move text-base-content/40 hover:text-base-content">
                  <Move class="w-4 h-4" />
                </div>

                <!-- Position -->
                <div class="w-6 h-6 rounded-full bg-base-200 flex items-center justify-center text-xs font-medium">
                  {{ index + 1 }}
                </div>

                <!-- Column Info -->
                <div class="flex-1 min-w-0">
                  <div class="font-medium text-sm">{{ column.label }}</div>
                  <div class="text-xs text-base-content/60">{{ getColumnTypeLabel(column) }}</div>
                </div>

                <!-- Toggle -->
                <label class="cursor-pointer">
                  <input
                    type="checkbox"
                    :checked="!hiddenColumns.has(column.key)"
                    @change="toggleColumnVisibility(column.key)"
                    class="toggle toggle-sm"
                  >
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- Hidden Fields Tab -->
        <div v-show="activeTab === 'hidden'" class="h-full flex flex-col">
          <!-- Info Header -->
          <div class="p-4 border-b border-base-300">
            <div class="mb-2">
              <h4 class="font-medium text-base-content">Hidden Fields</h4>
              <p class="text-sm text-base-content/60">
                Toggle hidden fields marked in form configuration to display them temporarily.
              </p>
            </div>

            <!-- Actions -->
            <div class="flex gap-2 flex-wrap">
              <button @click="showAllHiddenFields" class="btn btn-sm btn-success" :disabled="availableHiddenFields.length === 0">
                Show All Hidden
              </button>
              <button @click="hideAllHiddenFields" class="btn btn-sm btn-warning" :disabled="enabledHiddenFields.size === 0">
                Hide All Hidden
              </button>
              <span class="text-xs text-base-content/50 flex items-center">
                {{ enabledHiddenFields.size }} of {{ availableHiddenFields.length }} enabled
              </span>
            </div>
          </div>

          <!-- Hidden Fields List -->
          <div class="flex-1 overflow-y-auto p-4">
            <div v-if="availableHiddenFields.length === 0" class="text-center py-8">
              <div class="text-base-content/40 mb-2">
                <EyeOffIcon class="w-12 h-12 mx-auto mb-3" />
              </div>
              <p class="text-base-content/60">No hidden fields defined</p>
              <p class="text-sm text-base-content/40">Hidden fields are marked with 'hidden: true' in the form configuration</p>
            </div>

            <div v-else class="space-y-2">
              <div
                v-for="column in availableHiddenFields"
                :key="column.key"
                class="flex items-center gap-3 p-3 border border-base-300 rounded-lg hover:bg-base-50"
              >
                <!-- Column Icon -->
                <component
                  :is="getColumnIcon(column)"
                  class="w-5 h-5 text-base-content/60"
                />

                <!-- Column Info -->
                <div class="flex-1 min-w-0">
                  <div class="font-medium text-sm">{{ column.label }}</div>
                  <div class="text-xs text-base-content/60">{{ column.key }} • {{ getColumnTypeLabel(column) }}</div>
                </div>

                <!-- Toggle -->
                <label class="cursor-pointer">
                  <input
                    type="checkbox"
                    :checked="enabledHiddenFields.has(column.key)"
                    @change="toggleHiddenFieldVisibility(column.key)"
                    class="toggle toggle-sm"
                  >
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- Display Tab -->
        <div v-show="activeTab === 'display'" class="h-full overflow-y-auto p-4">
          <div class="space-y-4">
            <!-- Compact Mode -->
            <div class="flex items-center justify-between p-3 border border-base-300 rounded-lg">
              <div>
                <div class="font-medium">Compact Mode</div>
                <div class="text-sm text-base-content/60">Reduce row spacing</div>
              </div>
              <input
                type="checkbox"
                :checked="isCompactMode"
                @change="toggleCompactMode"
                class="toggle"
              >
            </div>

            <!-- Super Compact Mode -->
            <div class="flex items-center justify-between p-3 border border-base-300 rounded-lg">
              <div>
                <div class="font-medium">Ultra Compact</div>
                <div class="text-sm text-base-content/60">Maximum density</div>
              </div>
              <input
                type="checkbox"
                :checked="isSuperCompactMode"
                @change="toggleSuperCompactMode"
                class="toggle"
              >
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import {
  Eye as EyeIcon,
  EyeOff as EyeOffIcon,
  ChevronDown as ChevronDownIcon,
  RotateCcw as RotateCcwIcon,
  Columns,
  Save,
  Type,
  Hash,
  Calendar,
  Image,
  ToggleRight,
  DollarSign,
  Star,
  Tag,
  Info,
  Minimize2,
  Check,
  Zap,
  Move,
  X as XIcon,
  Settings,
  Shuffle
} from 'lucide-vue-next';

interface Column {
  key: string;
  label: string;
  type?: string;
  priority?: number;
  hidden?: boolean;
}

const props = defineProps<{
  columns: Column[];
  storageKey?: string;
  showModal?: boolean;
}>();

const emit = defineEmits(['update:hiddenColumns', 'update:compactMode', 'update:superCompactMode', 'update:columnOrder', 'update:enabledHiddenFields', 'close']);

// Initialize state
const hiddenColumns = ref(new Set<string>());
const enabledHiddenFields = ref(new Set<string>());
const isCompactMode = ref(true); // Default to compact mode
const isSuperCompactMode = ref(false);
const columnOrder = ref<string[]>([]);
const hasStoredPreferences = ref(false);
const activeTab = ref('columns'); // Active tab state

// Drag and drop state
const isDragging = ref(false);
const draggedIndex = ref<number | null>(null);
const dragOverIndex = ref<number | null>(null);
const showDropZone = ref(false);

// Computed properties
const totalColumns = computed(() => props.columns.filter(col => !col.hidden).length);
const visibleCount = computed(() => totalColumns.value - hiddenColumns.value.size);
const storageKey = computed(() => props.storageKey || 'table-settings');

const orderedColumns = computed(() => {
  const columnMap = new Map(props.columns.map(col => [col.key, col]));

  if (columnOrder.value.length > 0) {
    const ordered = columnOrder.value
      .map(key => columnMap.get(key))
      .filter(Boolean) as Column[];

    const orderedKeys = new Set(columnOrder.value);
    const newColumns = props.columns.filter(col => !orderedKeys.has(col.key));

    return [...ordered, ...newColumns].filter(col => !col.hidden); // Filter out columns marked as hidden in FormHelper
  }

  return [...props.columns]
    .filter(col => !col.hidden) // Filter out columns marked as hidden in FormHelper
    .sort((a, b) => {
      const priorityA = a.priority ?? 999;
      const priorityB = b.priority ?? 999;
      return priorityA - priorityB;
    });
});

const hasCustomOrder = computed(() => {
  return columnOrder.value.length > 0;
});

// Hidden fields computed properties
const availableHiddenFields = computed(() => {
  return props.columns.filter(col => col.hidden === true);
});

// Methods for managing settings
const closeModal = () => {
  emit('close');
};

const toggleCompactMode = () => {
  isCompactMode.value = !isCompactMode.value;
  emit('update:compactMode', isCompactMode.value);
  saveSettings();
};

const toggleSuperCompactMode = () => {
  isSuperCompactMode.value = !isSuperCompactMode.value;
  // Super compact mode implies compact mode
  if (isSuperCompactMode.value && !isCompactMode.value) {
    isCompactMode.value = true;
    emit('update:compactMode', true);
  }
  emit('update:superCompactMode', isSuperCompactMode.value);
  saveSettings();
};

const toggleColumnVisibility = (columnKey: string) => {
  const newHiddenColumns = new Set(hiddenColumns.value);
  if (newHiddenColumns.has(columnKey)) {
    newHiddenColumns.delete(columnKey);
  } else {
    newHiddenColumns.add(columnKey);
  }
  updateHiddenColumns(newHiddenColumns);
};

const showAllColumns = () => {
  updateHiddenColumns(new Set());
};

const hideAllColumns = () => {
  const allColumnKeys = new Set(props.columns.map(col => col.key));
  updateHiddenColumns(allColumnKeys);
};

const resetToDefault = () => {
  localStorage.removeItem(storageKey.value);
  hasStoredPreferences.value = false;
  isCompactMode.value = true;
  isSuperCompactMode.value = false;
  columnOrder.value = [];
  updateHiddenColumns(new Set());
  updateEnabledHiddenFields(new Set());
  emit('update:compactMode', true);
  emit('update:superCompactMode', false);
  emit('update:columnOrder', []);
};

const autoArrangeColumns = () => {
  // Auto arrange columns by priority and type
  const sortedColumns = [...props.columns]
    .filter(col => !col.hidden)
    .sort((a, b) => {
      // First by priority (lower numbers first)
      const priorityA = a.priority ?? 999;
      const priorityB = b.priority ?? 999;
      if (priorityA !== priorityB) return priorityA - priorityB;

      // Then by type importance
      const typeOrder = { text: 1, number: 2, date: 3, boolean: 4, media: 5 };
      const typeA = typeOrder[a.type as keyof typeof typeOrder] ?? 10;
      const typeB = typeOrder[b.type as keyof typeof typeOrder] ?? 10;
      if (typeA !== typeB) return typeA - typeB;

      // Finally alphabetically
      return a.label.localeCompare(b.label);
    });

  const newOrder = sortedColumns.map(col => col.key);
  columnOrder.value = newOrder;
  emit('update:columnOrder', newOrder);
  saveSettings();
};

// Hidden fields management methods
const toggleHiddenFieldVisibility = (columnKey: string) => {
  const newEnabledHiddenFields = new Set(enabledHiddenFields.value);
  if (newEnabledHiddenFields.has(columnKey)) {
    newEnabledHiddenFields.delete(columnKey);
  } else {
    newEnabledHiddenFields.add(columnKey);
  }
  updateEnabledHiddenFields(newEnabledHiddenFields);
};

const showAllHiddenFields = () => {
  const allHiddenKeys = new Set(availableHiddenFields.value.map(col => col.key));
  updateEnabledHiddenFields(allHiddenKeys);
};

const hideAllHiddenFields = () => {
  updateEnabledHiddenFields(new Set());
};

const updateEnabledHiddenFields = (newEnabledHiddenFields: Set<string>) => {
  enabledHiddenFields.value = newEnabledHiddenFields;
  emit('update:enabledHiddenFields', newEnabledHiddenFields);
  saveSettings();
};

// Enhanced drag and drop methods
const handleDragStart = (event: DragEvent, index: number) => {
  isDragging.value = true;
  draggedIndex.value = index;

  if (event.dataTransfer) {
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/html', '');

    // Create a custom drag image
    const dragImage = document.createElement('div');
    dragImage.textContent = orderedColumns.value[index].label;
    dragImage.className = 'px-3 py-2 bg-primary text-primary-content rounded-lg shadow-lg text-sm font-medium';
    dragImage.style.position = 'absolute';
    dragImage.style.top = '-1000px';
    document.body.appendChild(dragImage);
    event.dataTransfer.setDragImage(dragImage, 0, 0);

    setTimeout(() => document.body.removeChild(dragImage), 0);
  }
};

const handleDragOver = (event: DragEvent, index: number) => {
  event.preventDefault();
  if (event.dataTransfer) {
    event.dataTransfer.dropEffect = 'move';
  }
  dragOverIndex.value = index;
  showDropZone.value = true;
};

const handleDragEnter = (index: number) => {
  dragOverIndex.value = index;
};

const handleDragLeave = () => {
  // Small delay to prevent flickering
  setTimeout(() => {
    if (!isDragging.value) return;
    dragOverIndex.value = null;
    showDropZone.value = false;
  }, 50);
};

const handleDrop = (event: DragEvent, dropIndex: number) => {
  event.preventDefault();

  if (draggedIndex.value === null || draggedIndex.value === dropIndex) {
    handleDragEnd();
    return;
  }

  const newOrder = [...orderedColumns.value];
  const draggedItem = newOrder.splice(draggedIndex.value, 1)[0];
  newOrder.splice(dropIndex, 0, draggedItem);

  const newColumnOrder = newOrder.map(col => col.key);
  columnOrder.value = newColumnOrder;
  emit('update:columnOrder', newColumnOrder);
  saveSettings();

  handleDragEnd();
};

const handleDragEnd = () => {
  isDragging.value = false;
  draggedIndex.value = null;
  dragOverIndex.value = null;
  showDropZone.value = false;
};

// Helper functions
const updateHiddenColumns = (newHiddenColumns: Set<string>) => {
  hiddenColumns.value = newHiddenColumns;
  emit('update:hiddenColumns', newHiddenColumns);
  saveSettings();
};

const saveSettings = () => {
  try {
    const settings = {
      hiddenColumns: Array.from(hiddenColumns.value),
      enabledHiddenFields: Array.from(enabledHiddenFields.value),
      compactMode: isCompactMode.value,
      superCompactMode: isSuperCompactMode.value,
      columnOrder: columnOrder.value
    };
    localStorage.setItem(storageKey.value, JSON.stringify(settings));
    hasStoredPreferences.value = settings.hiddenColumns.length > 0 ||
                                 settings.enabledHiddenFields.length > 0 ||
                                 settings.columnOrder.length > 0 ||
                                 !settings.compactMode ||
                                 settings.superCompactMode;
  } catch (error) {
    console.error('Error saving table settings:', error);
  }
};

const loadSettings = () => {
  try {
    const stored = localStorage.getItem(storageKey.value);
    if (stored) {
      const settings = JSON.parse(stored);

      if (settings.hiddenColumns) {
        const storedColumns = new Set(settings.hiddenColumns);
        hiddenColumns.value = storedColumns;
        emit('update:hiddenColumns', storedColumns);
      }

      if (settings.enabledHiddenFields) {
        const storedEnabledHiddenFields = new Set(settings.enabledHiddenFields);
        enabledHiddenFields.value = storedEnabledHiddenFields;
        emit('update:enabledHiddenFields', storedEnabledHiddenFields);
      }

      if (settings.compactMode !== undefined) {
        isCompactMode.value = settings.compactMode;
        emit('update:compactMode', settings.compactMode);
      }

      if (settings.superCompactMode !== undefined) {
        isSuperCompactMode.value = settings.superCompactMode;
        emit('update:superCompactMode', settings.superCompactMode);
      }

      if (settings.columnOrder) {
        columnOrder.value = settings.columnOrder;
        emit('update:columnOrder', settings.columnOrder);
      }

      hasStoredPreferences.value = settings.hiddenColumns?.length > 0 ||
                                   settings.enabledHiddenFields?.length > 0 ||
                                   settings.columnOrder?.length > 0 ||
                                   !settings.compactMode ||
                                   settings.superCompactMode;
    } else {
      // Default state
      emit('update:compactMode', true);
    }
  } catch (error) {
    console.error('Error loading table settings:', error);
  }
};

// Visual helper functions
const getVisibilityBadgeColor = () => {
  const percentage = (visibleCount.value / totalColumns.value) * 100;
  if (percentage === 100) return 'badge-success';
  if (percentage >= 75) return 'badge-info';
  if (percentage >= 50) return 'badge-warning';
  if (percentage > 0) return 'badge-error';
  return 'badge-neutral';
};

const getColumnIcon = (column: Column) => {
  const iconMap: Record<string, any> = {
    text: Type,
    number: Hash,
    date: Calendar,
    timestamp: Calendar,
    media: Image,
    boolean: ToggleRight,
    price: DollarSign,
    rating: Star,
    model_search: Tag,
    default: Info
  };
  return iconMap[column.type || 'default'];
};

const getColumnTypeLabel = (column: Column) => {
  const typeLabels: Record<string, string> = {
    text: 'Text',
    number: 'Number',
    date: 'Date',
    timestamp: 'DateTime',
    media: 'Media',
    boolean: 'Boolean',
    price: 'Currency',
    rating: 'Rating',
    model_search: 'Relation',
    default: 'General'
  };
  return typeLabels[column.type || 'default'];
};

// Click outside directive
const vClickOutside = {
  mounted(el: HTMLElement, binding: any) {
    el.clickOutsideEvent = (event: Event) => {
      if (!(el === event.target || el.contains(event.target as Node))) {
        binding.value();
      }
    };
    document.addEventListener('click', el.clickOutsideEvent);
  },
  unmounted(el: HTMLElement) {
    document.removeEventListener('click', el.clickOutsideEvent);
  }
};

// Load settings on mount
onMounted(() => {
  loadSettings();
});

// Watch for column changes
watch(() => props.columns, () => {
  const currentColumns = new Set(props.columns.map(col => col.key));
  const newHiddenColumns = new Set(
    Array.from(hiddenColumns.value).filter(key => currentColumns.has(key))
  );
  const newColumnOrder = columnOrder.value.filter(key => currentColumns.has(key));

  if (newHiddenColumns.size !== hiddenColumns.value.size) {
    updateHiddenColumns(newHiddenColumns);
  }

  if (newColumnOrder.length !== columnOrder.value.length) {
    columnOrder.value = newColumnOrder;
    emit('update:columnOrder', newColumnOrder);
    saveSettings();
  }
}, { deep: true });
</script>

<style scoped>
/* Enhanced Custom Scrollbar */
.custom-scrollbar {
  scrollbar-width: thin;
  scrollbar-color: hsl(var(--p) / 0.3) hsl(var(--b3) / 0.5);
}

.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: hsl(var(--b3) / 0.3);
  border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: linear-gradient(45deg, hsl(var(--p) / 0.4), hsl(var(--s) / 0.4));
  border-radius: 10px;
  border: 1px solid hsl(var(--b1));
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(45deg, hsl(var(--p) / 0.6), hsl(var(--s) / 0.6));
}

/* Modern Toggle Switch Styling */
.peer:checked + div {
  background: linear-gradient(45deg, hsl(var(--su)), hsl(var(--su) / 0.8));
  box-shadow: 0 0 0 2px hsl(var(--su) / 0.2);
}

.peer:focus + div {
  box-shadow: 0 0 0 4px hsl(var(--p) / 0.25);
}

.peer + div::after {
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Enhanced Drag Handle */
.drag-handle {
  padding: 0.5rem;
  border-radius: 0.375rem;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  touch-action: manipulation;
}

.drag-handle:hover {
  background-color: hsl(var(--p) / 0.1);
  transform: scale(1.05);
}

.drag-handle:active {
  cursor: grabbing !important;
  transform: scale(0.95);
}

/* Enhanced Drag States */
.group\/item.opacity-50 {
  background: linear-gradient(135deg, hsl(var(--p) / 0.1), hsl(var(--s) / 0.1));
  transform: rotate(1deg) scale(0.98);
  box-shadow: 0 4px 12px hsl(var(--p) / 0.2);
}

.group\/item.border-primary {
  border-color: hsl(var(--p));
  box-shadow: 0 0 0 3px hsl(var(--p) / 0.15);
  background: linear-gradient(135deg, hsl(var(--p) / 0.05), hsl(var(--s) / 0.05));
}

/* Drop Zone Animation */
.h-1.bg-gradient-to-r {
  animation: shimmer 1.5s ease-in-out infinite;
}

@keyframes shimmer {
  0%, 100% {
    opacity: 0.5;
    transform: scaleY(1) scaleX(0.8);
  }
  50% {
    opacity: 1;
    transform: scaleY(1.5) scaleX(1);
  }
}

/* Enhanced Button Animations */
.btn {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.btn::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
  transition: left 0.5s ease;
  z-index: 1;
}

.btn:hover::before {
  left: 100%;
}

.btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 6px 20px hsl(var(--b3) / 0.3);
}

.btn:active:not(:disabled) {
  transform: translateY(0) scale(0.98);
}

/* Enhanced Modal Animation */
@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: scale(0.9) translateY(-20px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

.transform.transition-all {
  animation: modalSlideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Enhanced Backdrop */
.backdrop-blur-sm {
  backdrop-filter: blur(12px) saturate(150%);
}

/* Progress Bar Animation */
.bg-gradient-to-r.from-primary.to-secondary {
  background-size: 200% 100%;
  animation: progressGlow 2s ease-in-out infinite;
}

@keyframes progressGlow {
  0%, 100% {
    background-position: 0% 50%;
  }
  50% {
    background-position: 100% 50%;
  }
}

/* Enhanced Badge Animations */
.badge {
  animation: fadeInUp 0.3s ease-out;
  transition: all 0.2s ease;
}

.badge:hover {
  transform: scale(1.05);
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(10px) scale(0.9);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* Enhanced Column Item Hover */
.group\/item {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.group\/item:hover {
  transform: translateX(4px);
  box-shadow: 0 4px 16px hsl(var(--b3) / 0.2);
}

/* Mobile Optimizations - Full Screen */
@media (max-width: 640px) {
  .fixed.inset-0 {
    padding: 0;
  }

  .sm\:rounded-2xl {
    border-radius: 0;
  }

  .sm\:w-\[90vw\] {
    width: 100vw;
  }

  .sm\:h-\[85vh\] {
    height: 100vh;
  }

  .px-4 {
    padding-left: 0.75rem;
    padding-right: 0.75rem;
  }

  .gap-3 {
    gap: 0.5rem;
  }

  .text-lg {
    font-size: 1rem;
  }

  .w-10.h-10 {
    width: 2rem;
    height: 2rem;
  }

  .w-5.h-5 {
    width: 1rem;
    height: 1rem;
  }

  /* Improve touch targets */
  .btn-sm {
    min-height: 2.5rem;
    padding: 0.5rem 0.75rem;
  }

  .drag-handle {
    padding: 0.75rem;
    min-width: 2rem;
    min-height: 2rem;
  }

  /* Better grid on mobile */
  .grid-cols-2 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
    gap: 0.5rem;
  }

  /* Single column layout for columns */
  .lg\:grid-cols-2 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }

  /* Improved toggle sizing */
  .w-14.h-7 {
    width: 3rem;
    height: 1.5rem;
  }

  .after\:h-5.after\:w-5::after {
    height: 1.25rem;
    width: 1.25rem;
  }

  /* Tab adjustments */
  .tabs {
    flex-direction: column;
  }

  .tab {
    flex: 1;
    justify-content: center;
  }
}

/* Tablet Optimizations */
@media (min-width: 641px) and (max-width: 1024px) {
  .sm\:max-w-4xl {
    max-width: 95vw;
  }

  .grid-cols-4 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .lg\:grid-cols-2 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }
}

/* Desktop Optimizations */
@media (min-width: 1025px) {
  .lg\:grid-cols-2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
  .border-base-200,
  .border-base-200\/50 {
    border-color: hsl(var(--bc));
    border-width: 2px;
  }

  .bg-base-200\/50,
  .bg-base-200\/30 {
    background-color: hsl(var(--b2));
  }

  .text-base-content\/60 {
    color: hsl(var(--bc));
  }

  .drag-handle {
    border: 2px solid hsl(var(--bc) / 0.3);
  }
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
  .animate-pulse,
  .transition-all,
  .transition-transform,
  .transition-colors,
  .group\/item,
  .btn,
  .drag-handle {
    animation: none !important;
    transition: none !important;
  }

  .btn:hover:not(:disabled),
  .group\/item:hover {
    transform: none !important;
  }

  .bg-gradient-to-r.from-primary.to-secondary {
    animation: none !important;
  }

  @keyframes modalSlideIn {
    from, to {
      opacity: 1;
      transform: none;
    }
  }

  @keyframes shimmer {
    from, to {
      opacity: 1;
      transform: none;
    }
  }

  @keyframes progressGlow {
    from, to {
      opacity: 1;
      transform: none;
    }
  }

  @keyframes fadeInUp {
    from, to {
      opacity: 1;
      transform: none;
    }
  }
}

/* Dark Mode Enhancements */
@media (prefers-color-scheme: dark) {
  .shadow-2xl {
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
  }

  .backdrop-blur-sm {
    backdrop-filter: blur(12px) saturate(120%) brightness(0.8);
  }
}

/* Focus Enhancements */
.btn:focus-visible,
.drag-handle:focus-visible,
input:focus-visible + div {
  outline: 3px solid hsl(var(--p));
  outline-offset: 2px;
  box-shadow: 0 0 0 6px hsl(var(--p) / 0.2);
}

/* Enhanced Tooltip */
.tooltip:before {
  font-size: 0.75rem;
  max-width: 240px;
  white-space: normal;
  line-height: 1.4;
  padding: 0.5rem 0.75rem;
  border-radius: 0.5rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Enhanced Section Dividers */
.w-1.h-5.bg-gradient-to-b {
  box-shadow: 0 0 8px hsl(var(--p) / 0.3);
}

/* Touch-friendly improvements */
@media (pointer: coarse) {
  .btn,
  .drag-handle {
    min-height: 2.75rem;
    min-width: 2.75rem;
  }

  .group\/item {
    padding: 1rem;
  }

  .w-12.h-6 {
    width: 3.5rem;
    height: 2rem;
  }

  .after\:h-5.after\:w-5::after {
    height: 1.5rem;
    width: 1.5rem;
  }
}

/* Performance optimizations */
.group\/item,
.drag-handle,
.btn,
.badge {
  will-change: transform;
}

/* Enhanced visual feedback for interactions */
.group\/item:active {
  transform: scale(0.98);
}

.btn:active {
  transform: scale(0.95);
}

/* Improved accessibility */
@media (prefers-reduced-motion: no-preference) {
  .animate-pulse {
    animation: gentlePulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
  }
}

@keyframes gentlePulse {
  0%, 100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.8;
    transform: scale(1.02);
  }
}

/* Enhanced scroll behavior */
.overflow-auto {
  scroll-behavior: smooth;
}

/* Modern gradient effects */
.bg-gradient-to-br.from-primary\/5 {
  background-image: linear-gradient(to bottom right, hsl(var(--p) / 0.05), transparent, hsl(var(--s) / 0.05));
}

.bg-gradient-to-br.from-primary {
  background-image: linear-gradient(to bottom right, hsl(var(--p)), hsl(var(--s)));
}

/* Enhanced border radius for modern look */
.rounded-2xl {
  border-radius: 1rem;
}

.rounded-xl {
  border-radius: 0.75rem;
}

/* Grid improvements */
.grid.gap-3 {
  gap: 0.75rem;
}

@media (max-width: 640px) {
  .grid.gap-3 {
    gap: 0.5rem;
  }
}

/* Enhanced shadow system */
.shadow-sm {
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.1);
}

.shadow-md {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07), 0 2px 4px rgba(0, 0, 0, 0.06);
}

.hover\:shadow-sm:hover {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0, 0, 0, 0.12);
}

/* Print styles */
@media print {
  .fixed.inset-0 {
    display: none !important;
  }
}

/* Enhanced modal sizing */
.sm\:w-\[90vw\] {
  width: 90vw;
}

.sm\:h-\[85vh\] {
  height: 85vh;
}

.sm\:max-w-4xl {
  max-width: 72rem;
}

/* Tab system styling */
.tabs-boxed {
  background: hsl(var(--b2) / 0.5);
  border-radius: 0.75rem;
  padding: 0.25rem;
}

.tab {
  border-radius: 0.5rem;
  transition: all 0.2s ease;
  font-weight: 500;
}

.tab-active {
  background: hsl(var(--p));
  color: hsl(var(--pc));
  box-shadow: 0 2px 4px hsl(var(--p) / 0.2);
}

/* Enhanced column grid for large screens */
.lg\:grid-cols-2 {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

/* Fullscreen modal adjustments */
.h-full.flex.flex-col {
  min-height: 0;
}

.flex-1.overflow-hidden {
  display: flex;
  flex-direction: column;
  min-height: 0;
}

/* Improved drag and drop for wider layout */
.group\/item {
  min-height: 4rem;
}

.drag-handle {
  min-width: 2.5rem;
  min-height: 2.5rem;
}
</style>
