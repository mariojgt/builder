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
        class="absolute right-0 top-full mt-3 bg-base-100 rounded-xl shadow-2xl border border-base-200 p-0 min-w-[320px] max-w-[400px] z-50 backdrop-blur-sm"
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
                <h3 class="font-semibold text-base-content">Column Visibility</h3>
                <p class="text-xs text-base-content/60">Customize your table view</p>
              </div>
            </div>

            <!-- Quick Stats -->
            <div class="text-right">
              <div class="text-sm font-bold text-primary">{{ visibleCount }}/{{ totalColumns }}</div>
              <div class="text-xs text-base-content/60">visible</div>
            </div>
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

        <!-- Progress Indicator -->
        <div class="px-6 py-2 bg-base-50/30">
          <div class="flex items-center gap-3">
            <div class="flex-1">
              <div class="flex justify-between text-xs text-base-content/60 mb-1">
                <span>Visibility Progress</span>
                <span>{{ Math.round(visibilityPercentage) }}%</span>
              </div>
              <div class="h-2 bg-base-300 rounded-full overflow-hidden">
                <div
                  class="h-full transition-all duration-500 ease-out"
                  :class="getProgressBarColor()"
                  :style="{ width: `${visibilityPercentage}%` }"
                ></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Columns List Section -->
        <div class="px-4 py-2 max-h-80 overflow-auto custom-scrollbar">
          <div class="space-y-1">
            <TransitionGroup
              enter-active-class="transition-all duration-300 ease-out"
              enter-from-class="opacity-0 translate-x-4"
              enter-to-class="opacity-100 translate-x-0"
              leave-active-class="transition-all duration-200 ease-in"
              leave-from-class="opacity-100 translate-x-0"
              leave-to-class="opacity-0 translate-x-4"
            >
              <div
                v-for="(column, index) in sortedColumns"
                :key="column.key"
                class="group/item flex items-center justify-between p-3 rounded-lg transition-all duration-200 hover:bg-base-200/50 border border-transparent hover:border-base-300/30"
                :style="{ animationDelay: `${index * 50}ms` }"
              >
                <!-- Column Info -->
                <div class="flex items-center gap-3 flex-1 min-w-0">
                  <!-- Column Type Icon -->
                  <div class="w-6 h-6 rounded bg-base-200 flex items-center justify-center flex-shrink-0 transition-colors duration-200 group-hover/item:bg-primary/20">
                    <component :is="getColumnIcon(column)" class="w-3 h-3 text-base-content/60 group-hover/item:text-primary transition-colors duration-200" />
                  </div>

                  <!-- Column Details -->
                  <div class="flex-1 min-w-0">
                    <div class="font-medium text-sm text-base-content truncate">{{ column.label }}</div>
                    <div class="text-xs text-base-content/50 truncate">{{ getColumnTypeLabel(column) }}</div>
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
            </TransitionGroup>
          </div>
        </div>

        <!-- Footer Section -->
        <div class="px-6 py-3 bg-base-50/50 rounded-b-xl border-t border-base-200/50">
          <div class="flex items-center justify-between text-xs text-base-content/60">
            <div class="flex items-center gap-2">
              <div class="w-2 h-2 rounded-full bg-success animate-pulse"></div>
              <span>{{ visibleCount }} columns visible</span>
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
  Info
} from 'lucide-vue-next';

interface Column {
  key: string;
  label: string;
  type?: string;
}

const props = defineProps<{
  columns: Column[];
  storageKey?: string;
}>();

const emit = defineEmits(['update:hiddenColumns']);

// Initialize state
const isOpen = ref(false);
const hiddenColumns = ref(new Set<string>());
const hasStoredPreferences = ref(false);

// Computed properties
const totalColumns = computed(() => props.columns.length);
const visibleCount = computed(() => totalColumns.value - hiddenColumns.value.size);
const storageKey = computed(() => props.storageKey || 'table-hidden-columns');
const visibilityPercentage = computed(() =>
  totalColumns.value > 0 ? (visibleCount.value / totalColumns.value) * 100 : 0
);

const sortedColumns = computed(() => {
  return [...props.columns].sort((a, b) => {
    // Sort by visibility first (visible columns first), then by label
    const aVisible = !hiddenColumns.value.has(a.key);
    const bVisible = !hiddenColumns.value.has(b.key);

    if (aVisible !== bVisible) {
      return bVisible ? 1 : -1;
    }

    return a.label.localeCompare(b.label);
  });
});

// Methods for managing visibility
const toggleManager = () => {
  isOpen.value = !isOpen.value;
};

const closeManager = () => {
  isOpen.value = false;
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
  updateHiddenColumns(new Set());
};

// Helper functions
const updateHiddenColumns = (newHiddenColumns: Set<string>) => {
  hiddenColumns.value = newHiddenColumns;
  emit('update:hiddenColumns', newHiddenColumns);
  saveToLocalStorage(newHiddenColumns);
};

const saveToLocalStorage = (columns: Set<string>) => {
  try {
    const columnsArray = Array.from(columns);
    localStorage.setItem(storageKey.value, JSON.stringify(columnsArray));
    hasStoredPreferences.value = columnsArray.length > 0;
  } catch (error) {
    console.error('Error saving column preferences:', error);
  }
};

const loadFromLocalStorage = () => {
  try {
    const stored = localStorage.getItem(storageKey.value);
    if (stored) {
      const columnsArray = JSON.parse(stored);
      const storedColumns = new Set(columnsArray);
      hasStoredPreferences.value = columnsArray.length > 0;
      updateHiddenColumns(storedColumns);
    }
  } catch (error) {
    console.error('Error loading column preferences:', error);
  }
};

// Visual helper functions
const getVisibilityBadgeColor = () => {
  const percentage = visibilityPercentage.value;
  if (percentage === 100) return 'badge-success';
  if (percentage >= 75) return 'badge-info';
  if (percentage >= 50) return 'badge-warning';
  if (percentage > 0) return 'badge-error';
  return 'badge-neutral';
};

const getProgressBarColor = () => {
  const percentage = visibilityPercentage.value;
  if (percentage === 100) return 'bg-gradient-to-r from-success to-success';
  if (percentage >= 75) return 'bg-gradient-to-r from-info to-success';
  if (percentage >= 50) return 'bg-gradient-to-r from-warning to-info';
  if (percentage > 0) return 'bg-gradient-to-r from-error to-warning';
  return 'bg-neutral';
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

// Load stored preferences on mount
onMounted(() => {
  loadFromLocalStorage();
});

// Watch for column changes to update storage
watch(() => props.columns, () => {
  const currentColumns = new Set(props.columns.map(col => col.key));
  const newHiddenColumns = new Set(
    Array.from(hiddenColumns.value).filter(key => currentColumns.has(key))
  );
  if (newHiddenColumns.size !== hiddenColumns.value.size) {
    updateHiddenColumns(newHiddenColumns);
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

/* Progress Bar Animation */
.bg-gradient-to-r {
  background-size: 200% 100%;
  animation: gradientFlow 3s ease-in-out infinite;
}

@keyframes gradientFlow {
  0%, 100% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
}

/* Enhanced Dropdown Animation */
.backdrop-blur-sm {
  backdrop-filter: blur(8px);
}

/* Shimmer Effect */
.group:hover .group-hover\:translate-x-full {
  transition-delay: 0.1s;
}

/* Enhanced Badge Animation */
.badge {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.badge:hover {
  transform: scale(1.05);
}

/* Column Item Hover Effects */
.group\/item:hover {
  transform: translateX(2px);
}

/* Enhanced Pulse Animation */
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

.animate-pulse {
  animation: gentlePulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Toggle Button Enhancement */
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

/* Enhanced Focus States */
.btn:focus-visible {
  outline: 2px solid hsl(var(--p));
  outline-offset: 2px;
}

/* Responsive Design */
@media (max-width: 640px) {
  .min-w-[320px] {
    min-width: 280px;
  }

  .max-w-[400px] {
    max-width: 95vw;
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
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
  .animate-pulse,
  .transition-all,
  .transition-transform {
    animation: none;
    transition: none;
  }

  .bg-gradient-to-r {
    animation: none;
  }
}

/* Print Styles */
@media print {
  .dropdown {
    display: none !important;
  }
}

/* Enhanced Shadow Effects */
.shadow-2xl {
  box-shadow:
    0 25px 50px -12px rgba(0, 0, 0, 0.25),
    0 0 0 1px hsl(var(--b3) / 0.05);
}

/* Column Type Indicator Enhancement */
.w-6.h-6.rounded {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.group\/item:hover .w-6.h-6.rounded {
  transform: rotate(5deg) scale(1.1);
}
</style>
