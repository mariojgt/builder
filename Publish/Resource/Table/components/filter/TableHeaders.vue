<template>
    <div class="flex items-center gap-2 justify-end">
        <!-- Advanced Filters Toggle -->
        <div class="tooltip" data-tip="Toggle advanced filters">
            <button
                @click="handleAdvancedFiltersToggle"
                :class="[
                    'btn btn-ghost btn-xs w-6 h-6 p-0 min-h-0 relative',
                    showAdvancedFilters ? 'text-primary' : 'text-base-content/60 hover:text-primary'
                ]"
            >
                <FilterIcon class="w-3 h-3" />
                <span
                    v-if="hasAdvancedFiltersActive"
                    class="absolute -top-1 -right-1 badge badge-warning badge-xs w-2 h-2 p-0 rounded-full border-0"
                ></span>
            </button>
        </div>

        <!-- View Mode Toggle -->
        <div class="tooltip" data-tip="Toggle view mode">
            <div class="join">
                <button
                    @click="handleViewModeChange('table')"
                    :class="[
                        'btn btn-ghost btn-xs w-6 h-6 p-0 min-h-0 join-item',
                        viewMode === 'table' ? 'text-primary' : 'text-base-content/60'
                    ]"
                >
                    <TableIcon class="w-3 h-3" />
                </button>
                <button
                    @click="handleViewModeChange('list')"
                    :class="[
                        'btn btn-ghost btn-xs w-6 h-6 p-0 min-h-0 join-item',
                        viewMode === 'list' ? 'text-primary' : 'text-base-content/60'
                    ]"
                >
                    <ListIcon class="w-3 h-3" />
                </button>
            </div>
        </div>

        <!-- Advanced Controls Toggle -->
        <div class="tooltip" data-tip="Open column settings">
            <button
                @click="handleAdvancedToggle"
                :class="[
                    'btn btn-ghost btn-xs w-6 h-6 p-0 min-h-0',
                    showAdvancedControls ? 'text-primary' : 'text-base-content/60'
                ]"
            >
                <SettingsIcon class="w-3 h-3" />
            </button>
        </div>

        <!-- Row Click Navigation Toggle -->
        <div class="tooltip" data-tip="Toggle row click navigation">
            <button
                @click="handleRowClickToggle"
                :class="[
                    'btn btn-ghost btn-xs w-6 h-6 p-0 min-h-0',
                    rowClickNavigationEnabled ? 'text-success' : 'text-base-content/60'
                ]"
            >
                <MousePointerClickIcon class="w-3 h-3" />
            </button>
        </div>

        <!-- Column Settings -->
        <div class="tooltip" data-tip="Column settings">
            <button
                @click="handleColumnSettings"
                class="btn btn-ghost btn-xs w-6 h-6 p-0 min-h-0 text-base-content/60 hover:text-primary"
            >
                <ColumnsIcon class="w-3 h-3" />
            </button>
        </div>

        <!-- Export Data -->
        <div class="tooltip" data-tip="Export table data">
            <button
                @click="handleOpenExportModal"
                class="btn btn-ghost btn-xs w-6 h-6 p-0 min-h-0 text-base-content/60 hover:text-primary"
            >
                <DownloadIcon class="w-3 h-3" />
            </button>
        </div>

        <!-- Reset Filters -->
        <div class="tooltip" data-tip="Reset all filters and settings">
            <button
                @click="handleResetFilters"
                :class="[
                    'btn btn-ghost btn-xs w-6 h-6 p-0 min-h-0',
                    (hasActiveFilters || hasStoredFilters) ? 'text-warning hover:text-warning' : 'text-base-content/30 cursor-not-allowed'
                ]"
                :disabled="!hasActiveFilters && !hasStoredFilters"
            >
                <RotateCcwIcon class="w-3 h-3" />
            </button>
        </div>

        <!-- Icon Size Control -->
        <div class="dropdown dropdown-end">
            <div tabindex="0" role="button" class="tooltip" data-tip="Header icon size">
                <button class="btn btn-ghost btn-xs w-6 h-6 p-0 min-h-0 text-base-content/60 hover:text-primary">
                    <BoltIcon class="w-3 h-3" />
                </button>
            </div>
            <div tabindex="0" class="dropdown-content z-[999] p-3 shadow bg-base-100 rounded-box w-52 border border-base-300">
                <div class="space-y-3">
                    <div class="flex items-center gap-2">
                        <BoltIcon class="w-4 h-4 text-warning" />
                        <span class="text-sm font-medium">Header Icon Size</span>
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-base-content/70">Size:</span>
                            <span class="text-xs font-mono">{{ iconSize }}px</span>
                        </div>
                        <input
                            type="range"
                            v-model="iconSize"
                            @input="updateIconSize"
                            min="12"
                            max="24"
                            step="1"
                            class="range range-primary range-xs"
                        />
                        <div class="flex justify-between text-xs text-base-content/50 px-1">
                            <span>12px</span>
                            <span>18px</span>
                            <span>24px</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Custom Actions Slot -->
        <slot name="custom-actions"></slot>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import {
    Filter as FilterIcon,
    MoreHorizontal as MoreHorizontalIcon,
    X as XIcon,
    Table as TableIcon,
    List as ListIcon,
    Sliders as SlidersIcon,
    Settings as SettingsIcon,
    Download as DownloadIcon,
    RotateCcw as RotateCcwIcon,
    MousePointerClick as MousePointerClickIcon,
    Columns as ColumnsIcon,
    Zap as BoltIcon
} from 'lucide-vue-next';

// Props
interface Props {
  viewMode?: 'table' | 'list';
  showAdvancedControls?: boolean;
  showAdvancedFilters?: boolean;
  hasAdvancedFiltersActive?: boolean;
  canReset?: boolean;
  rowClickNavigationEnabled?: boolean;
  hasActiveFilters?: boolean;
  hasStoredFilters?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  viewMode: 'table',
  showAdvancedControls: false,
  showAdvancedFilters: false,
  hasAdvancedFiltersActive: false,
  canReset: false,
  rowClickNavigationEnabled: true,
  hasActiveFilters: false,
  hasStoredFilters: false
});

// Emits
const emit = defineEmits<{
  'update:viewMode': [mode: 'table' | 'list'];
  'update:showAdvancedControls': [show: boolean];
  'update:showAdvancedFilters': [show: boolean];
  'toggle-row-click': [];
  'reset-filters': [];
  'open-column-settings': [];
  'open-export-modal': [];
}>();

// Icon size management for table headers only
const iconSize = ref(16); // Default 16px

// Load icon size from localStorage on mount
const loadIconSize = () => {
  try {
    const saved = localStorage.getItem('table-header-icon-size');
    if (saved) {
      iconSize.value = parseInt(saved, 10);
    }
  } catch (error) {
    console.warn('Failed to load header icon size from localStorage:', error);
  }
};

// Save icon size to localStorage
const saveIconSize = () => {
  try {
    localStorage.setItem('table-header-icon-size', iconSize.value.toString());
  } catch (error) {
    console.warn('Failed to save header icon size to localStorage:', error);
  }
};

// Update icon size and save to storage (scoped to header icons only)
const updateIconSize = () => {
  saveIconSize();
  // Apply CSS custom property specifically for table header icons
  document.documentElement.style.setProperty('--table-header-icon-size', `${iconSize.value}px`);
};

// Initialize icon size on mount
onMounted(() => {
  loadIconSize();
  updateIconSize();
});

// Methods
const handleViewModeChange = (mode: 'table' | 'list') => {
  emit('update:viewMode', mode);
};

const handleAdvancedToggle = () => {
  emit('update:showAdvancedControls', !props.showAdvancedControls);
};

const handleAdvancedFiltersToggle = () => {
  emit('update:showAdvancedFilters', !props.showAdvancedFilters);
};

const handleRowClickToggle = () => {
  emit('toggle-row-click');
};

const handleResetFilters = () => {
  emit('reset-filters');
};

const handleColumnSettings = () => {
  emit('open-column-settings');
};

const handleOpenExportModal = () => {
  emit('open-export-modal');
};
</script>

<style scoped>
/* Enhanced tooltip positioning */
.tooltip:before {
  z-index: 999;
  font-size: 0.75rem;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  white-space: nowrap;
}

/* Enhanced button hover effects */
.btn:hover {
  transform: scale(1.1);
  transition: all 0.2s ease-in-out;
}

.btn:active {
  transform: scale(0.95);
}

/* Disabled state */
.cursor-not-allowed {
  cursor: not-allowed !important;
}

.cursor-not-allowed:hover {
  transform: none !important;
}

/* Dropdown positioning */
.dropdown-end .dropdown-content {
  right: 0;
  left: auto;
}

/* Icon spacing - use CSS custom property for dynamic sizing (scoped to header icons) */
:deep(.w-3.h-3) {
  width: var(--table-header-icon-size, 0.75rem) !important;
  height: var(--table-header-icon-size, 0.75rem) !important;
}

:deep(.w-4.h-4) {
  width: calc(var(--table-header-icon-size, 1rem) + 2px) !important;
  height: calc(var(--table-header-icon-size, 1rem) + 2px) !important;
}

/* Enhanced icon hover states */
.text-base-content\/60:hover {
  color: hsl(var(--bc) / 0.8);
}

.text-success:hover {
  color: hsl(var(--su) / 0.9);
}

.text-warning:hover {
  color: hsl(var(--wa) / 0.9);
}

.text-primary:hover {
  color: hsl(var(--p) / 0.9);
}
</style>
