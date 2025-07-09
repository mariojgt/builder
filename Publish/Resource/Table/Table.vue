<template>
    <div class="w-full">
        <!-- Main Table Card -->
        <div
            class="relative flex flex-col bg-base-300 rounded-3xl shadow-lg transition-all duration-300 hover:shadow-xl">
            <!-- Table Header Section -->
            <div class="flex flex-col sm:flex-row items-center justify-between p-6 border-b border-base-content/10">
                <!-- Title and Count -->
                <div class="mb-4 sm:mb-0">
                    <h1 class="text-2xl md:text-3xl font-extrabold text-base-content flex items-center gap-3">
                        <TableIcon class="w-8 h-8 text-primary" />
                        {{ props.tableTitle }}
                    </h1>
                    <p v-if="totalItems" class="text-base-content/70 text-sm mt-1 flex items-center gap-2">
                        Showing {{ totalItems.toLocaleString() }} items
                        <!-- Density Indicators -->
                        <span v-if="superCompactMode" class="badge badge-accent badge-xs">Super Compact</span>
                        <span v-else-if="compactMode" class="badge badge-secondary badge-xs">Compact</span>
                        <span v-else class="badge badge-ghost badge-xs">Standard</span>
                        <!-- ✨ NEW: Advanced Filters Indicator -->
                        <span v-if="hasAdvancedFilters" class="badge badge-info badge-xs" title="Advanced filters active">
                            Advanced
                        </span>
                    </p>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-3">
                    <div class="btn-group">
                        <button @click="viewMode = 'table'"
                            :class="['btn btn-sm', viewMode === 'table' ? 'btn-primary' : 'btn-ghost']">
                            <TableIcon class="w-4 h-4" />
                            <span class="hidden sm:inline ml-2">Table</span>
                        </button>
                        <button @click="viewMode = 'list'"
                            :class="['btn btn-sm', viewMode === 'list' ? 'btn-primary' : 'btn-ghost']">
                            <ListIcon class="w-4 h-4" />
                            <span class="hidden sm:inline ml-2">Cards</span>
                        </button>
                    </div>
                    <ColumnVisibilityManager
                        :columns="props.columns"
                        :storage-key="`${props.tableTitle}-table-settings`"
                        @update:hiddenColumns="updateHiddenColumns"
                        @update:compactMode="updateCompactMode"
                        @update:superCompactMode="updateSuperCompactMode"
                        @update:columnOrder="updateColumnOrder"
                    />
                    <ExportData
                        :data="tableData"
                        :columns="displayColumns"
                        :hiddenColumns="hiddenColumns"
                        :filename="props.tableTitle.toLowerCase().replace(/\s+/g, '-')"
                        :total-records="paginationInfo.total"
                    />
                    <slot name="custom-actions"></slot>
                    <slot name="new">
                        <create
                            v-if="!props.custom_create_route"
                            :columns="props.columns"
                            :endpoint="props.endpointCreate"
                            :model="props.model"
                            :permission="props.permission"
                            @onCreate="refreshData"
                        />
                        <Link v-else :href="props.custom_create_route"
                            class="btn btn-primary gap-2 group relative overflow-hidden shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105"
                            :title="superCompactMode ? 'Create' : ''">
                            <SettingsIcon class="w-4 h-4" />
                            <span v-if="!superCompactMode" class="hidden sm:inline">Create</span>
                        </Link>
                    </slot>
                </div>
            </div>

            <!-- Filters Section -->
            <table-filter @onPerPage="handlePerPageChange" @onOrderBy="handleOrderChange" @onSearch="handleSearch"
                @onFilter="handleFilterChange" @onFilterReset="handleFilterReset" :columns="props.columns" />
            <AdvancedFilter class="mt-3" :columns="props.columns" @onFilterChange="handleAdvancedFilterChange" />

            <!-- Loading State -->
            <div v-if="isLoading"
                class="absolute inset-0 bg-base-300/50 backdrop-blur-sm flex items-center justify-center z-50 rounded-3xl">
                <div class="flex flex-col items-center gap-4">
                    <div class="loading loading-spinner loading-lg text-primary"></div>
                    <p class="text-base-content/70">Loading data...</p>
                </div>
            </div>

            <!-- Table Content -->
            <div :class="getContentPadding()">
                <div class="bg-base-100 rounded-xl overflow-hidden border border-base-content/10">
                    <!-- Empty State -->
                    <div v-if="!tableData.length && !isLoading"
                        class="flex flex-col items-center justify-center text-center"
                        :class="getEmptyStatePadding()"
                    >
                        <DatabaseIcon :class="getEmptyStateIconSize()" class="text-base-content/20" />
                        <h3 :class="getEmptyStateTitleSize()" class="font-semibold text-base-content mt-4">
                            No Records Found
                        </h3>
                        <p class="text-base-content/70 mt-1" :class="getEmptyStateTextSize()">
                            {{ search ? 'Try adjusting your search or filters' : 'No data available' }}
                        </p>
                        <button v-if="hasActiveFilters" @click="resetFilters" class="btn btn-ghost btn-sm gap-2 mt-4">
                            <RotateCcwIcon class="w-4 h-4" />
                            Reset Filters
                        </button>
                    </div>

                    <!-- Table View -->
                    <div v-else-if="viewMode === 'table'" class="overflow-x-auto">
                        <table :class="getTableClasses()">
                            <!-- ENHANCED TABLE HEADER WITH SIMPLE SORT INDICATORS -->
                            <thead class="bg-base-200/50">
                                <tr>
                                    <th v-for="column in displayColumns" :key="column.key"
                                        @click="column.sortable && handleSort(column.key)"
                                        :class="getHeaderClasses(column)"
                                        :title="column.sortable ? `Click to sort by ${column.label}` : column.label"
                                    >
                                        <div class="flex items-center justify-between gap-2 w-full">
                                            <!-- Left side: Icon + Label -->
                                            <div class="flex items-center gap-1 min-w-0 flex-1">
                                                <!-- Column icon (hide in super compact) -->
                                                <component
                                                    v-if="getColumnIcon(column) && !superCompactMode"
                                                    :is="getColumnIcon(column)"
                                                    :class="getHeaderIconSize()"
                                                    class="text-primary/70 flex-shrink-0"
                                                />
                                                <span class="truncate">{{ getColumnDisplayName(column) }}</span>
                                            </div>

                                            <!-- Right side: Simple Sort Indicator -->
                                            <div v-if="column.sortable" class="flex-shrink-0">
                                                <span class="sort-indicator text-xs font-mono select-none"
                                                      :class="getSortIndicatorClasses(column.key)">
                                                    {{ getSortText(column.key) }}
                                                </span>
                                            </div>
                                        </div>
                                    </th>
                                    <th :class="getActionsHeaderClasses()">
                                        {{ getActionsHeaderText() }}
                                    </th>
                                </tr>
                            </thead>

                            <!-- Table Body -->
                            <tbody>
                                <tr v-for="(item, index) in tableData" :key="item.id || index"
                                    :class="getRowClasses(item)">
                                    <table-display-data
                                        :tableData="item"
                                        :columns="displayColumns"
                                        :hiddenColumns="hiddenColumns"
                                        :viewType="'table'"
                                        :compactMode="compactMode"
                                        :superCompactMode="superCompactMode"
                                        :columnOrder="columnOrder"
                                    />
                                    <td :class="getActionsCellClasses()" class="text-right">
                                        <div :class="getActionsContainerClasses()">
                                            <!-- Edit Action -->
                                            <template v-if="!custom_edit_route">
                                                <edit :columns="displayColumns" :endpoint="endpointEdit" :model="model"
                                                    :modelValue="item" :id="item[props.defaultIdKey]"
                                                    :permission="permission" @onEdit="refreshData" />
                                            </template>
                                            <template v-else>
                                                <Link :href="custom_edit_route + item.id"
                                                    :class="getActionButtonClasses('edit')"
                                                    :title="superCompactMode ? 'Edit' : ''"
                                                >
                                                    <PencilIcon :class="getActionIconSize()" />
                                                    <span v-if="!superCompactMode" class="hidden sm:inline">Edit</span>
                                                </Link>
                                            </template>

                                            <!-- Custom Action -->
                                            <template v-if="custom_point_route">
                                                <Link :href="custom_point_route + item.id"
                                                    :class="getActionButtonClasses('custom')"
                                                    :title="superCompactMode ? custom_action_name : ''"
                                                >
                                                    <component :is="getCustomActionIcon()" :class="getActionIconSize()" />
                                                    <span v-if="!superCompactMode" class="hidden sm:inline">{{ custom_action_name }}</span>
                                                </Link>
                                            </template>

                                            <!-- Delete Action -->
                                            <delete
                                                v-if="!props.disableDelete"
                                                :id="item[props.defaultIdKey]"
                                                :endpoint="endpointDelete"
                                                :model="model"
                                                :permission="permission"
                                                @onDelete="refreshData"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- List View -->
                    <div v-else :class="getListViewClasses()">
                        <div v-for="(item, index) in tableData" :key="item.id || index"
                            :class="getListItemClasses(item)"
                        >
                            <table-display-data
                                :tableData="item"
                                :columns="displayColumns"
                                :hiddenColumns="hiddenColumns"
                                :viewType="'list'"
                                :compactMode="compactMode"
                                :superCompactMode="superCompactMode"
                                :columnOrder="columnOrder"
                            />
                            <div :class="getListActionsClasses()">
                                <!-- Edit Action -->
                                <template v-if="!custom_edit_route">
                                    <edit :columns="displayColumns" :endpoint="endpointEdit" :model="model" :modelValue="item"
                                        :id="item[props.defaultIdKey]" :permission="permission" @onEdit="refreshData" />
                                </template>
                                <template v-else>
                                    <Link :href="custom_edit_route + item.id"
                                        :class="getActionButtonClasses('edit')">
                                        <PencilIcon :class="getActionIconSize()" />
                                        <span v-if="!superCompactMode" class="hidden sm:inline">Edit</span>
                                    </Link>
                                </template>

                                <!-- Custom Action -->
                                <template v-if="custom_point_route">
                                    <Link :href="custom_point_route + item.id"
                                        :class="getActionButtonClasses('custom')">
                                        <component :is="getCustomActionIcon()" :class="getActionIconSize()" />
                                        <span v-if="!superCompactMode" class="hidden sm:inline">{{ custom_action_name }}</span>
                                    </Link>
                                </template>

                                <!-- Delete Action -->
                                <delete
                                    v-if="!props.disableDelete"
                                    :id="item[props.defaultIdKey]"
                                    :endpoint="endpointDelete"
                                    :model="model"
                                    :permission="permission"
                                    @onDelete="refreshData"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div :class="getPaginationMargin()">
                    <table-pagination v-if="tableData.length" @onPagination="handlePageChange"
                        :paginationInfo="paginationInfo" :endpoint="endpoint" />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { startWindToast } from "@mariojgt/wind-notify/packages/index.js";
import axios from 'axios';
import { Link } from '@inertiajs/vue3';
import {
    Table as TableIcon,
    Database as DatabaseIcon,
    RotateCcw as RotateCcwIcon,
    ArrowUp as ArrowUpIcon,
    ArrowDown as ArrowDownIcon,
    ArrowUpDown as ArrowUpDownIcon,
    Pencil as PencilIcon,
    List as ListIcon,
    Settings as SettingsIcon
} from 'lucide-vue-next';

// Components
import Delete from './components/crud/delete.vue';
import Create from './components/crud/create.vue';
import Edit from './components/crud/edit.vue';
import TableFilter from './components/filter/filter.vue';
import TablePagination from './components/filter/pagination.vue';
import TableDisplayData from './components/tableDataDisplay.vue';
import AdvancedFilter from './components/filter/AdvancedFilter.vue';
import ColumnVisibilityManager from './components/filter/ColumnVisibilityManager.vue';
import ExportData from './components/filter/ExportData.vue';

// Props
const props = defineProps({
    tableTitle: { type: String, default: 'Table' },
    columns: { type: Array, default: () => [] },
    model: { type: String, required: true },
    endpoint: { type: String, required: true },
    endpointDelete: { type: String, required: true },
    endpointCreate: { type: String, required: true },
    endpointEdit: { type: String, required: true },
    permission: { type: String, default: null },
    custom_edit_route: { type: String, default: null },
    custom_create_route: { type: String, default: null },
    custom_point_route: { type: String, default: null },
    custom_action_name: { type: String, default: null },
    defaultIdKey: { type: String, default: 'id' },
    rowStyling: { type: Object, default: () => ({}) },
    disableDelete: { type: Boolean, default: false }, // Disable delete action
    defaultFilters: { type: Object, default: () => ({}) }, // Simple key-value filters
    advancedFilters: { type: Array, default: () => [] }    // ✨ NEW: Advanced filters from FormHelper
});

// State
const isLoading = ref(false);
const tableData = ref([]);
const paginationInfo = ref({
    currentPage: 1,
    lastPage: 1,
    perPage: 10,
    total: 0,
    links: []
});

const perPage = ref(10);
const filterBy = ref(props.defaultIdKey);
const orderBy = ref<string | null>('desc'); // Default to descending order
const search = ref<string | null>(null);
const viewMode = ref('table');

// ✨ Enhanced state for density and column management
const hiddenColumns = ref(new Set<string>());
const compactMode = ref(true); // Default to compact mode
const superCompactMode = ref(false); // Super compact mode
const columnOrder = ref<string[]>([]);

// ✨ NEW: Filter state management
const activeFilters = ref({});

// Computed
const totalItems = computed(() => paginationInfo.value.total);

// ✨ NEW: Check if advanced filters are active
const hasAdvancedFilters = computed(() => {
    return props.advancedFilters && props.advancedFilters.length > 0;
});

const hasActiveFilters = computed(() => {
    const currentFilters = { ...activeFilters.value };
    const defaultFilters = { ...props.defaultFilters };

    // Remove default filters from current filters for comparison
    Object.keys(defaultFilters).forEach(key => {
        if (currentFilters[key] === defaultFilters[key]) {
            delete currentFilters[key];
        }
    });

    return search.value ||
        filterBy.value !== props.defaultIdKey ||
        perPage.value !== 10 ||
        orderBy.value !== null ||
        Object.keys(currentFilters).length > 0;
});

// ✨ NEW: Check if current filters differ from defaults
const hasNonDefaultFilters = computed(() => {
    const currentFilters = { ...activeFilters.value };
    const defaultFilters = { ...props.defaultFilters };

    // Check if there are any filters beyond the defaults
    for (const [key, value] of Object.entries(currentFilters)) {
        if (!(key in defaultFilters) || defaultFilters[key] !== value) {
            return true;
        }
    }

    return false;
});

// Enhanced column management with ordering
const displayColumns = computed(() => {
    const columnMap = new Map(props.columns.map(col => [col.key, col]));

    let orderedColumns;

    if (columnOrder.value.length > 0) {
        const ordered = columnOrder.value
            .map(key => columnMap.get(key))
            .filter(Boolean);

        const orderedKeys = new Set(columnOrder.value);
        const newColumns = props.columns.filter(col => !orderedKeys.has(col.key));

        orderedColumns = [...ordered, ...newColumns];
    } else {
        orderedColumns = [...props.columns].sort((a, b) => {
            const priorityA = a.priority ?? 999;
            const priorityB = b.priority ?? 999;
            return priorityA - priorityB;
        });
    }

    return orderedColumns.filter(column => !hiddenColumns.value.has(column.key));
});

// ✨ SIMPLE SORT INDICATOR METHODS
function getSortText(columnKey: string): string {
    if (filterBy.value === columnKey) {
        return orderBy.value === 'asc' ? '↑' : '↓';
    }
    return '↕';
}

function getSortIndicatorClasses(columnKey: string): string {
    const baseClasses = 'px-1 py-0.5 rounded transition-all duration-200';

    if (filterBy.value === columnKey) {
        return `${baseClasses} bg-primary text-primary-content font-bold`;
    } else {
        return `${baseClasses} text-base-content/40 hover:text-base-content hover:bg-base-200`;
    }
}

// Dynamic styling methods for different density modes
function getContentPadding(): string {
    if (superCompactMode.value) return 'p-2';
    if (compactMode.value) return 'p-3';
    return 'p-6';
}

function getEmptyStatePadding(): string {
    if (superCompactMode.value) return 'p-6';
    if (compactMode.value) return 'p-8';
    return 'p-12';
}

function getEmptyStateIconSize(): string {
    if (superCompactMode.value) return 'w-10 h-10';
    if (compactMode.value) return 'w-12 h-12';
    return 'w-16 h-16';
}

function getEmptyStateTitleSize(): string {
    if (superCompactMode.value) return 'text-sm';
    if (compactMode.value) return 'text-base';
    return 'text-lg';
}

function getEmptyStateTextSize(): string {
    if (superCompactMode.value) return 'text-xs';
    if (compactMode.value) return 'text-xs';
    return 'text-sm';
}

function getTableClasses(): string {
    const baseClasses = 'table w-full';
    if (superCompactMode.value) return `${baseClasses} table-super-compact`;
    if (compactMode.value) return `${baseClasses} table-compact`;
    return baseClasses;
}

function getHeaderClasses(column: any): string {
    const baseClasses = [
        'text-base-content font-medium transition-all duration-200 select-none'
    ];

    // Add sortable styling
    if (column.sortable) {
        baseClasses.push('cursor-pointer hover:bg-base-200/70');

        // Active sort column styling
        if (filterBy.value === column.key) {
            baseClasses.push('bg-primary/10');
        }
    }

    if (superCompactMode.value) {
        return [...baseClasses, 'px-1 py-1 text-xs leading-tight'].join(' ');
    }
    if (compactMode.value) {
        return [...baseClasses, 'px-2 py-1 text-xs'].join(' ');
    }
    return [...baseClasses, 'px-4 py-3 text-sm'].join(' ');
}

function getHeaderIconSize(): string {
    if (compactMode.value) return 'w-3 h-3';
    return 'w-4 h-4';
}

function getActionsHeaderClasses(): string {
    const baseClasses = 'text-base-content text-right font-medium';
    if (superCompactMode.value) {
        return `${baseClasses} px-1 py-1 text-xs w-10`;
    }
    if (compactMode.value) {
        return `${baseClasses} px-2 py-1 text-xs w-16`;
    }
    return `${baseClasses} px-4 py-3 text-sm w-20`;
}

function getActionsHeaderText(): string {
    if (superCompactMode.value) return 'Act';
    if (compactMode.value) return 'Act';
    return 'Actions';
}

function getRowClasses(item: any): string {
    const baseClasses = 'hover:bg-base-200/50 transition-colors duration-200 group';
    const conditionalClasses = getRowConditionalClasses(item);
    const heightClass = superCompactMode.value ? 'h-6' : (compactMode.value ? 'h-8' : 'h-auto');

    return `${baseClasses} ${conditionalClasses} ${heightClass}`;
}

function getActionsCellClasses(): string {
    if (superCompactMode.value) return 'px-1 py-0';
    if (compactMode.value) return 'px-2 py-1';
    return 'px-4 py-3';
}

function getActionsContainerClasses(): string {
    const baseClasses = 'flex items-center justify-end opacity-0 group-hover:opacity-100 transition-opacity duration-200';
    if (superCompactMode.value) return `${baseClasses} gap-0.5`;
    if (compactMode.value) return `${baseClasses} gap-1`;
    return `${baseClasses} gap-2`;
}

function getActionButtonClasses(type: 'edit' | 'custom'): string {
    const baseClasses = 'btn gap-1 transition-all duration-200';
    const typeClasses = type === 'edit' ? 'btn-primary' : 'btn-secondary';

    if (superCompactMode.value) {
        return `${baseClasses} ${typeClasses} btn-xs p-0 w-5 h-5`;
    }
    if (compactMode.value) {
        return `${baseClasses} ${typeClasses} btn-xs`;
    }
    return `${baseClasses} ${typeClasses} btn-sm`;
}

function getActionIconSize(): string {
    if (superCompactMode.value) return 'w-3 h-3';
    if (compactMode.value) return 'w-3 h-3';
    return 'w-4 h-4';
}

function getListViewClasses(): string {
    if (superCompactMode.value) return 'divide-y divide-base-200/30';
    return 'divide-y divide-base-200';
}

function getListItemClasses(item: any): string {
    const baseClasses = 'hover:bg-base-200/50 transition-colors duration-200';
    const conditionalClasses = getRowConditionalClasses(item);
    const paddingClass = superCompactMode.value ? 'p-2' : (compactMode.value ? 'p-3' : 'p-4');

    return `${baseClasses} ${conditionalClasses} ${paddingClass}`;
}

function getListActionsClasses(): string {
    const baseClasses = 'flex justify-end';
    const gapClass = superCompactMode.value ? 'gap-0.5' : (compactMode.value ? 'gap-1' : 'gap-2');
    const marginClass = superCompactMode.value ? 'mt-1' : (compactMode.value ? 'mt-2' : 'mt-4');

    return `${baseClasses} ${gapClass} ${marginClass}`;
}

function getPaginationMargin(): string {
    if (superCompactMode.value) return 'mt-2';
    if (compactMode.value) return 'mt-3';
    return 'mt-6';
}

function getColumnDisplayName(column: any): string {
    if (!compactMode.value) return column.label;

    const abbreviations = {
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
        'Priority': 'Pri'
    };

    if (superCompactMode.value) {
        return abbreviations[column.label] || column.label.slice(0, 3);
    }

    return abbreviations[column.label] || column.label.slice(0, 6);
}

// Row styling method (enhanced)
function getRowConditionalClasses(item: any): string {
    const classes: string[] = [];

    if (props.rowStyling?.conditionalStyling) {
        const { field, conditions, default: defaultStyle } = props.rowStyling.conditionalStyling;
        const fieldValue = getNestedValue(item, field);
        const valueStr = String(fieldValue || '').toLowerCase().trim();

        if (valueStr && conditions[valueStr]) {
            classes.push(conditions[valueStr]);
        } else if (defaultStyle) {
            classes.push(defaultStyle);
        }
    }

    if (props.rowStyling?.advancedStyling) {
        const { conditions, default: defaultStyle } = props.rowStyling.advancedStyling;

        for (const condition of conditions) {
            const fieldValue = getNestedValue(item, condition.field);

            if (checkCondition(condition, fieldValue)) {
                classes.push(condition.classes);
                break;
            }
        }

        if (classes.length === 0 && defaultStyle) {
            classes.push(defaultStyle);
        }
    }

    return classes.join(' ');
}

// Helper function to get nested values
function getNestedValue(obj: any, path: string): any {
    if (!obj || !path) return null;

    if (path.includes('|')) {
        const fallbackKeys = path.split('|').map(k => k.trim());
        for (const fallbackKey of fallbackKeys) {
            const value = getNestedValue(obj, fallbackKey);
            if (value !== null && value !== undefined && value !== '' && value !== 0) {
                return value;
            }
        }
        return null;
    }

    return path.split('.').reduce((current, key) => {
        return current && current[key] !== undefined ? current[key] : null;
    }, obj);
}

// Simple condition checker
function checkCondition(condition: any, value: any): boolean {
    const numValue = parseFloat(value);
    const isNumber = !isNaN(numValue);
    const strValue = String(value || '').toLowerCase();

    switch (condition.operator) {
        case 'exists':
            return value !== null && value !== undefined && value !== '' && strValue.trim() !== '';
        case 'not_exists':
            return value === null || value === undefined || value === '' || strValue.trim() === '';
        case 'equals':
            return String(value).toLowerCase() === String(condition.value).toLowerCase();
        case 'not_equals':
            return String(value).toLowerCase() !== String(condition.value).toLowerCase();
        case 'greater_than':
            return isNumber && numValue > condition.value;
        case 'greater_than_equal':
            return isNumber && numValue >= condition.value;
        case 'less_than':
            return isNumber && numValue < condition.value;
        case 'less_than_equal':
            return isNumber && numValue <= condition.value;
        case 'between':
            return isNumber && numValue >= condition.min && numValue <= condition.max;
        case 'contains':
            return strValue.includes(String(condition.value).toLowerCase());
        case 'starts_with':
            return strValue.startsWith(String(condition.value).toLowerCase());
        case 'ends_with':
            return strValue.endsWith(String(condition.value).toLowerCase());
        case 'in_array':
            return condition.array && condition.array.includes(value);
        default:
            return false;
    }
}

// Methods
function getSortIcon(columnKey: string) {
    if (filterBy.value !== columnKey) return ArrowUpDownIcon;
    return orderBy.value === 'asc' ? ArrowUpIcon : ArrowDownIcon;
}

function handleSort(columnKey: string) {
    if (filterBy.value === columnKey) {
        orderBy.value = orderBy.value === 'asc' ? 'desc' : 'asc';
    } else {
        filterBy.value = columnKey;
        orderBy.value = 'asc';
    }
    fetchData();
}

function getColumnIcon(column: any) {
    // Add your column icon logic here
    return SettingsIcon; // Default icon
}

function getCustomActionIcon() {
    return SettingsIcon; // Customize based on your needs
}

const emit = defineEmits(['tableDataLoaded']);

// ✨ ENHANCED: Fetch data with advanced filters support
const fetchData = async (endpoint = props.endpoint) => {
    try {
        isLoading.value = true;

        // ✨ NEW: Build request payload with advanced filters
        const requestPayload = {
            model: props.model,
            columns: props.columns,
            perPage: perPage.value,
            search: search.value,
            sort: filterBy.value,
            filters: activeFilters.value,
            direction: orderBy.value,
            permission: props.permission
        };

        // ✨ NEW: Add advanced filters if they exist
        if (props.advancedFilters && props.advancedFilters.length > 0) {
            requestPayload.advancedFilters = props.advancedFilters;
        }

        const response = await axios.post(endpoint, requestPayload);

        tableData.value = response.data.data;
        paginationInfo.value = {
            currentPage: response.data.current_page,
            lastPage: response.data.last_page,
            perPage: response.data.per_page,
            total: response.data.total,
            links: response.data.links
        };

        // ADD THIS LINE - emit the table data to parent
        emit('tableDataLoaded', tableData.value);
    } catch (error: any) {
        if (error.response?.data?.errors) {
            Object.values(error.response.data.errors).forEach((errorMessages: any) => {
                if (Array.isArray(errorMessages)) {
                    errorMessages.forEach(msg => {
                        startWindToast('error', msg);
                    });
                }
            });
        } else {
            startWindToast('error', 'Failed to fetch data');
        }
    } finally {
        isLoading.value = false;
    }
};

// ✨ ENHANCED: Reset filters respecting defaults and advanced filters
const resetFilters = () => {
    perPage.value = 10;
    filterBy.value = props.defaultIdKey;
    orderBy.value = 'desc';
    search.value = null;
    // Reset to default filters instead of empty object
    activeFilters.value = { ...props.defaultFilters };
    fetchData();
};

// Event Handlers
const refreshData = () => fetchData();
const handlePageChange = (link: string) => fetchData(link);
const handlePerPageChange = (value: number) => {
    perPage.value = value;
    fetchData();
};

// ✨ ENHANCED: Advanced filter handler merges with defaults
const handleAdvancedFilterChange = (filters) => {
    activeFilters.value = { ...props.defaultFilters, ...filters };
    fetchData();
};

const handleFilterChange = (value: string) => {
    filterBy.value = value;
    fetchData();
};

const handleOrderChange = (value: string) => {
    orderBy.value = value;
    fetchData();
};

const handleSearch = (value: string) => {
    if (value && value.length > 2) {
        search.value = value;
        fetchData();
    }
};

const handleFilterReset = (data: any) => {
    perPage.value = data.perPage;
    filterBy.value = data.filterBy;
    orderBy.value = data.orderBy;
    search.value = data.search;
    fetchData();
};

// Enhanced event handlers for density and column management
const updateHiddenColumns = (newHiddenColumns: Set<string>) => {
    hiddenColumns.value = newHiddenColumns;
};

const updateCompactMode = (newCompactMode: boolean) => {
    compactMode.value = newCompactMode;
    // If compact mode is disabled, also disable super compact
    if (!newCompactMode && superCompactMode.value) {
        superCompactMode.value = false;
    }
};

const updateSuperCompactMode = (newSuperCompactMode: boolean) => {
    superCompactMode.value = newSuperCompactMode;
    // Super compact mode requires compact mode
    if (newSuperCompactMode && !compactMode.value) {
        compactMode.value = true;
    }
};

const updateColumnOrder = (newOrder: string[]) => {
    columnOrder.value = newOrder;
};

// ✨ NEW: Initialize component with default filters and advanced filters
onMounted(() => {
    // Merge default filters with any existing active filters
    if (props.defaultFilters && Object.keys(props.defaultFilters).length > 0) {
        activeFilters.value = { ...props.defaultFilters, ...activeFilters.value };
    }

    // Log advanced filters for debugging
    if (props.advancedFilters && props.advancedFilters.length > 0) {
        console.log('Advanced filters loaded:', props.advancedFilters);
    }

    fetchData();
});
</script>
