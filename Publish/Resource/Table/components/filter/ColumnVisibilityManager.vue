<template>
  <div class="relative" v-click-outside="closeManager">
    <!-- Enhanced Trigger Button -->
    <button
      @click="toggleManager"
      class="btn btn-ghost btn-sm gap-2 group relative overflow-hidden bg-base-200/50 border border-base-300/50 hover:border-primary/30 hover:bg-primary/5 transition-all duration-300"
    >
      <!-- Background Animation -->
      <div class="absolute inset-0 bg-gradient-to-r from-primary/10 to-secondary/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

      <!-- Icon with Animation -->
      <EyeIcon class="w-4 h-4 relative z-10 transition-all duration-300" :class="[
        isOpen ? 'text-primary rotate-12' : 'text-base-content/70 group-hover:text-primary'
      ]" />

      <!-- Text with Counter -->
      <span class="relative z-10 font-medium text-base-content/80 group-hover:text-base-content transition-colors duration-300">
        Columns
      </span>

      <!-- Advanced Counter Badge -->
      <div class="relative z-10 flex items-center gap-1">
        <div class="badge badge-sm gap-1 transition-all duration-300" :class="[
          getVisibilityBadgeColor()
        ]">
          <span class="font-bold">{{ visibleCount }}</span>
          <span class="opacity-70">/</span>
          <span class="text-xs">{{ totalColumns }}</span>
        </div>
        <!-- Compact Mode Indicator -->
        <div v-if="isCompactMode" class="badge badge-xs badge-secondary">C</div>
        <!-- Super Compact Mode Indicator -->
        <div v-if="isSuperCompactMode" class="badge badge-xs badge-accent">SC</div>
      </div>

      <!-- Chevron with Smooth Rotation -->
      <ChevronDownIcon
        class="w-4 h-4 relative z-10 transition-all duration-300 ease-out"
        :class="{ 'rotate-180': isOpen }"
      />

      <!-- Shimmer Effect -->
      <div class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/20 to-transparent group-hover:translate-x-full transition-transform duration-700 ease-out"></div>
    </button>

    <!-- Enhanced Dropdown Panel -->
    <Transition
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="opacity-0 scale-95 -translate-y-2"
      enter-to-class="opacity-100 scale-100 translate-y-0"
      leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100 scale-100 translate-y-0"
      leave-to-class="opacity-0 scale-95 -translate-y-2"
    >
      <div
        v-if="isOpen"
        class="absolute right-0 top-full mt-3 bg-base-100 rounded-xl shadow-2xl border border-base-200 p-0 min-w-[360px] max-w-[420px] z-50 backdrop-blur-sm"
        style="filter: drop-shadow(0 25px 50px rgba(0, 0, 0, 0.15))"
      >
        <!-- Header Section -->
        <div class="bg-gradient-to-r from-base-200 via-base-100 to-base-200 px-6 py-4 rounded-t-xl border-b border-base-200">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-lg bg-primary/20 flex items-center justify-center">
                <Columns class="w-4 h-4 text-primary" />
              </div>
              <div>
                <h3 class="font-semibold text-base-content">Table Settings</h3>
                <p class="text-xs text-base-content/60">Customize view, order & density</p>
              </div>
            </div>

            <!-- Quick Stats -->
            <div class="text-right">
              <div class="text-sm font-bold text-primary">{{ visibleCount }}/{{ totalColumns }}</div>
              <div class="text-xs text-base-content/60">visible</div>
            </div>
          </div>
        </div>

        <!-- Settings Toggles -->
        <div class="px-6 py-3 bg-base-50/50 border-b border-base-200/50 space-y-3">
          <!-- Compact Mode Toggle -->
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <Minimize2 class="w-4 h-4 text-base-content/60" />
              <span class="font-medium text-sm">Compact Mode</span>
            </div>
            <button
              @click="toggleCompactMode"
              class="btn btn-xs btn-circle transition-all duration-200"
              :class="isCompactMode ? 'btn-primary' : 'btn-ghost border border-base-300'"
            >
              <Check v-if="isCompactMode" class="w-3 h-3" />
            </button>
          </div>

          <!-- Super Compact Mode Toggle -->
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <Zap class="w-4 h-4 text-base-content/60" />
              <span class="font-medium text-sm">Super Compact</span>
              <div class="tooltip tooltip-top" data-tip="Maximum data density - fits 2x more data">
                <Info class="w-3 h-3 text-base-content/40" />
              </div>
            </div>
            <button
              @click="toggleSuperCompactMode"
              class="btn btn-xs btn-circle transition-all duration-200"
              :class="isSuperCompactMode ? 'btn-accent' : 'btn-ghost border border-base-300'"
            >
              <Check v-if="isSuperCompactMode" class="w-3 h-3" />
            </button>
          </div>
        </div>

        <!-- Action Buttons Section -->
        <div class="px-6 py-3 bg-base-50/50 border-b border-base-200/50">
          <div class="flex items-center justify-between gap-3">
            <button
              @click="showAllColumns"
              :disabled="visibleCount === totalColumns"
              class="btn btn-sm btn-success gap-2 flex-1 group transition-all duration-200"
              :class="{ 'btn-disabled': visibleCount === totalColumns }"
            >
              <EyeIcon class="w-3 h-3 group-hover:scale-110 transition-transform duration-200" />
              <span class="font-medium">Show All</span>
            </button>

            <button
              @click="hideAllColumns"
              :disabled="visibleCount === 0"
              class="btn btn-sm btn-warning gap-2 flex-1 group transition-all duration-200"
              :class="{ 'btn-disabled': visibleCount === 0 }"
            >
              <EyeOffIcon class="w-3 h-3 group-hover:scale-110 transition-transform duration-200" />
              <span class="font-medium">Hide All</span>
            </button>

            <button
              @click="resetToDefault"
              :disabled="!hasStoredPreferences"
              class="btn btn-sm btn-ghost gap-2 group transition-all duration-200"
              :class="{ 'btn-disabled': !hasStoredPreferences }"
            >
              <RotateCcwIcon class="w-3 h-3 group-hover:rotate-180 transition-transform duration-300" />
              <span class="font-medium">Reset</span>
            </button>
          </div>
        </div>

        <!-- Reordering Instructions -->
        <div class="px-6 py-2 bg-blue-50/50 border-b border-base-200/50">
          <div class="flex items-center gap-2 text-xs text-blue-700">
            <Move class="w-3 h-3" />
            <span>Drag the grip handles to reorder columns</span>
          </div>
        </div>

        <!-- Columns List Section with Enhanced Drag & Drop -->
        <div class="px-4 py-2 max-h-80 overflow-auto custom-scrollbar">
          <div class="space-y-1">
            <div
              v-for="(column, index) in orderedColumns"
              :key="column.key"
              class="group/item flex items-center justify-between p-3 rounded-lg transition-all duration-200 hover:bg-base-200/50 border border-transparent hover:border-base-300/30"
              :class="[
                isDragging && draggedIndex === index ? 'opacity-50 scale-95 shadow-lg' : '',
                isDragging && dragOverIndex === index ? 'border-primary bg-primary/10' : '',
                'cursor-move'
              ]"
              :draggable="true"
              @dragstart="handleDragStart($event, index)"
              @dragover.prevent="handleDragOver($event, index)"
              @dragenter.prevent="handleDragEnter(index)"
              @drop="handleDrop($event, index)"
              @dragend="handleDragEnd"
              @dragleave="handleDragLeave"
            >
              <!-- Enhanced Drag Handle & Column Info -->
              <div class="flex items-center gap-3 flex-1 min-w-0">
                <!-- Enhanced Drag Handle -->
                <div class="drag-handle flex flex-col gap-0.5 text-base-content/30 group-hover/item:text-primary/60 transition-colors duration-200 cursor-grab active:cursor-grabbing">
                  <div class="flex gap-0.5">
                    <div class="w-0.5 h-0.5 bg-current rounded-full"></div>
                    <div class="w-0.5 h-0.5 bg-current rounded-full"></div>
                  </div>
                  <div class="flex gap-0.5">
                    <div class="w-0.5 h-0.5 bg-current rounded-full"></div>
                    <div class="w-0.5 h-0.5 bg-current rounded-full"></div>
                  </div>
                  <div class="flex gap-0.5">
                    <div class="w-0.5 h-0.5 bg-current rounded-full"></div>
                    <div class="w-0.5 h-0.5 bg-current rounded-full"></div>
                  </div>
                </div>

                <!-- Column Position Indicator -->
                <div class="flex items-center justify-center w-6 h-6 rounded-full bg-base-200 text-xs font-bold text-base-content/60 group-hover/item:bg-primary/20 group-hover/item:text-primary transition-all duration-200">
                  {{ index + 1 }}
                </div>

                <!-- Column Type Icon -->
                <div class="w-6 h-6 rounded bg-base-200 flex items-center justify-center flex-shrink-0 transition-colors duration-200 group-hover/item:bg-primary/20">
                  <component :is="getColumnIcon(column)" class="w-3 h-3 text-base-content/60 group-hover/item:text-primary transition-colors duration-200" />
                </div>

                <!-- Column Details -->
                <div class="flex-1 min-w-0">
                  <div class="font-medium text-sm text-base-content truncate">{{ column.label }}</div>
                  <div class="text-xs text-base-content/50 truncate flex items-center gap-1">
                    <span>{{ getColumnTypeLabel(column) }}</span>
                    <!-- Priority indicator -->
                    <span v-if="column.priority" class="badge badge-xs badge-ghost">P{{ column.priority }}</span>
                  </div>
                </div>
              </div>

              <!-- Toggle Button -->
              <button
                @click.stop="toggleColumnVisibility(column.key)"
                class="btn btn-xs btn-circle relative overflow-hidden group/toggle transition-all duration-200 hover:scale-110"
                :class="[
                  !hiddenColumns.has(column.key)
                    ? 'btn-success hover:btn-success'
                    : 'btn-ghost hover:btn-error border border-base-300/30'
                ]"
                :aria-label="`${!hiddenColumns.has(column.key) ? 'Hide' : 'Show'} ${column.label} column`"
              >
                <!-- Background Animation -->
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover/toggle:translate-x-full transition-transform duration-500"></div>

                <!-- Icon -->
                <component
                  :is="!hiddenColumns.has(column.key) ? EyeIcon : EyeOffIcon"
                  class="w-3 h-3 relative z-10 transition-all duration-200"
                  :class="[
                    !hiddenColumns.has(column.key)
                      ? 'text-success-content'
                      : 'text-base-content/50 group-hover/toggle:text-error'
                  ]"
                />
              </button>
            </div>
          </div>

          <!-- Drop zone indicator -->
          <div
            v-if="isDragging"
            class="h-1 bg-primary rounded-full opacity-0 transition-opacity duration-200"
            :class="{ 'opacity-100': showDropZone }"
          ></div>
        </div>

        <!-- Footer Section -->
        <div class="px-6 py-3 bg-base-50/50 rounded-b-xl border-t border-base-200/50">
          <div class="flex items-center justify-between text-xs text-base-content/60">
            <div class="flex items-center gap-2">
              <div class="w-2 h-2 rounded-full bg-success animate-pulse"></div>
              <span>{{ visibleCount }} columns visible</span>
              <span v-if="hasCustomOrder" class="badge badge-xs badge-info">Custom Order</span>
            </div>
            <div class="flex items-center gap-2">
              <Save class="w-3 h-3" />
              <span>Auto-saved</span>
            </div>
          </div>
        </div>
      </div>
    </Transition>
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
  Move
} from 'lucide-vue-next';

interface Column {
  key: string;
  label: string;
  type?: string;
  priority?: number;
}

const props = defineProps<{
  columns: Column[];
  storageKey?: string;
}>();

const emit = defineEmits(['update:hiddenColumns', 'update:compactMode', 'update:superCompactMode', 'update:columnOrder']);

// Initialize state
const isOpen = ref(false);
const hiddenColumns = ref(new Set<string>());
const isCompactMode = ref(true); // Default to compact mode
const isSuperCompactMode = ref(false);
const columnOrder = ref<string[]>([]);
const hasStoredPreferences = ref(false);

// Drag and drop state
const isDragging = ref(false);
const draggedIndex = ref<number | null>(null);
const dragOverIndex = ref<number | null>(null);
const showDropZone = ref(false);

// Computed properties
const totalColumns = computed(() => props.columns.length);
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

    return [...ordered, ...newColumns];
  }

  return [...props.columns].sort((a, b) => {
    const priorityA = a.priority ?? 999;
    const priorityB = b.priority ?? 999;
    return priorityA - priorityB;
  });
});

const hasCustomOrder = computed(() => {
  return columnOrder.value.length > 0;
});

// Methods for managing settings
const toggleManager = () => {
  isOpen.value = !isOpen.value;
};

const closeManager = () => {
  isOpen.value = false;
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
  emit('update:compactMode', true);
  emit('update:superCompactMode', false);
  emit('update:columnOrder', []);
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
      compactMode: isCompactMode.value,
      superCompactMode: isSuperCompactMode.value,
      columnOrder: columnOrder.value
    };
    localStorage.setItem(storageKey.value, JSON.stringify(settings));
    hasStoredPreferences.value = settings.hiddenColumns.length > 0 ||
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

/* Enhanced Drag and Drop Styles */
.drag-handle {
  padding: 0.25rem;
  border-radius: 0.25rem;
  transition: all 0.2s ease;
}

.drag-handle:hover {
  background-color: hsl(var(--b2));
  transform: scale(1.1);
}

.drag-handle:active {
  cursor: grabbing;
  transform: scale(0.95);
}

/* Drag states */
.group\/item.opacity-50 {
  background-color: hsl(var(--b2) / 0.5);
  transform: rotate(2deg);
}

.group\/item.border-primary {
  border-color: hsl(var(--p));
  box-shadow: 0 0 0 2px hsl(var(--p) / 0.2);
}

/* Drop zone animation */
.h-1.bg-primary {
  animation: pulse 1s infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 0.5;
    transform: scaleY(1);
  }
  50% {
    opacity: 1;
    transform: scaleY(1.5);
  }
}

/* Enhanced position indicator */
.w-6.h-6.rounded-full {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.group\/item:hover .w-6.h-6.rounded-full {
  transform: scale(1.2);
  font-weight: 700;
}

/* Enhanced Button Animations */
.btn {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px hsl(var(--b3) / 0.3);
}

.btn:active:not(:disabled) {
  transform: translateY(0) scale(0.98);
}

/* Enhanced Dropdown Animation */
.backdrop-blur-sm {
  backdrop-filter: blur(8px);
}

/* Badge animations */
.badge-xs,
.badge-sm {
  animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: scale(0.8); }
  to { opacity: 1; transform: scale(1); }
}

/* Tooltip styling */
.tooltip:before {
  font-size: 0.75rem;
  max-width: 200px;
  white-space: normal;
}

/* Enhanced focus states */
.btn:focus-visible,
.drag-handle:focus-visible {
  outline: 2px solid hsl(var(--p));
  outline-offset: 2px;
}

/* Column item animations */
.group\/item {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.group\/item:hover {
  transform: translateX(2px);
  box-shadow: 0 2px 8px hsl(var(--b3) / 0.2);
}

/* Priority badge styling */
.badge-ghost {
  background-color: hsl(var(--b2));
  border: 1px solid hsl(var(--b3));
}

/* Super compact mode indicator */
.badge-accent {
  background-color: hsl(var(--a));
  color: hsl(var(--ac));
}

/* Toggle button enhancements */
.btn-circle {
  position: relative;
  overflow: hidden;
}

.btn-circle::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
  transition: left 0.5s ease;
}

.btn-circle:hover::before {
  left: 100%;
}

/* Responsive Design */
@media (max-width: 640px) {
  .min-w-\[360px\] {
    min-width: 320px;
  }

  .max-w-\[420px\] {
    max-width: 95vw;
  }

  .px-6 {
    padding-left: 1rem;
    padding-right: 1rem;
  }

  .gap-3 {
    gap: 0.5rem;
  }
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
  .border-base-200 {
    border-color: hsl(var(--bc));
  }

  .bg-base-200\/50 {
    background-color: hsl(var(--b2));
  }

  .drag-handle {
    border: 1px solid hsl(var(--bc) / 0.3);
  }
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
  .animate-pulse,
  .transition-all,
  .transition-transform,
  .group\/item {
    animation: none;
    transition: none;
  }

  .bg-gradient-to-r {
    animation: none;
  }

  .btn:hover:not(:disabled) {
    transform: none;
  }

  .group\/item:hover {
    transform: none;
  }
}

/* Print Styles */
@media print {
  .relative {
    display: none !important;
  }
}

/* Enhanced Shadow Effects */
.shadow-2xl {
  box-shadow:
    0 25px 50px -12px rgba(0, 0, 0, 0.25),
    0 0 0 1px hsl(var(--b3) / 0.05);
}

/* Drag feedback improvements */
.cursor-move:hover {
  cursor: grab;
}

.cursor-move:active {
  cursor: grabbing;
}

/* Settings section styling */
.space-y-3 > * + * {
  margin-top: 0.75rem;
}

/* Enhanced instruction styling */
.bg-blue-50\/50 {
  background-color: rgb(239 246 255 / 0.5);
}

.text-blue-700 {
  color: rgb(29 78 216);
}

/* Custom order badge */
.badge-info {
  background-color: hsl(var(--in));
  color: hsl(var(--inc));
}

/* Auto-save indicator animation */
.animate-pulse {
  animation: gentlePulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes gentlePulse {
  0%, 100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.7;
    transform: scale(1.05);
  }
}

/* Shimmer effect enhancement */
.group:hover .group-hover\:translate-x-full {
  transition-delay: 0.1s;
}

/* Enhanced grid layout for smaller screens */
@media (max-width: 480px) {
  .flex.gap-3 {
    flex-direction: column;
    gap: 0.5rem;
  }

  .flex-1 {
    width: 100%;
  }
}

/* Accessibility improvements */
[aria-label] {
  position: relative;
}

.btn:focus-visible[aria-label]::after {
  content: attr(aria-label);
  position: absolute;
  bottom: 100%;
  left: 50%;
  transform: translateX(-50%);
  background: hsl(var(--b1));
  border: 1px solid hsl(var(--b3));
  border-radius: 0.25rem;
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
  white-space: nowrap;
  z-index: 100;
  pointer-events: none;
}

/* Performance optimizations */
.group\/item,
.drag-handle,
.btn-circle {
  will-change: transform;
}

/* Enhanced visual feedback for dragging */
.group\/item[draggable="true"]:hover {
  box-shadow: 0 4px 12px hsl(var(--p) / 0.2);
}

.group\/item[draggable="true"]:active {
  box-shadow: 0 8px 25px hsl(var(--p) / 0.3);
}
</style>
