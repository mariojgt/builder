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
                    <p v-if="totalItems" class="text-base-content/70 text-sm mt-1">
                        Showing {{ totalItems.toLocaleString() }} items
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
                            <span class="hidden sm:inline ml-2">List</span>
                        </button>
                    </div>
                    <ColumnVisibilityManager
                        :columns="props.columns"
                        :storage-key="`${props.tableTitle}-hidden-columns`"
                        @update:hiddenColumns="updateHiddenColumns"
                    />
                    <ExportData
                            :data="tableData"
                            :columns="props.columns"
                            :hiddenColumns="hiddenColumns"
                            :filename="props.tableTitle.toLowerCase().replace(/\s+/g, '-')"
                            :total-records="paginationInfo.total"
                        />
                    <slot name="custom-actions"></slot>
                    <slot name="new">
                        <create :columns="props.columns" :endpoint="props.endpointCreate" :model="props.model"
                            :permission="props.permission" @onCreate="refreshData" />
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
            <div class="p-6">
                <div class="bg-base-100 rounded-xl overflow-hidden border border-base-content/10">
                    <!-- Empty State -->
                    <div v-if="!tableData.length && !isLoading"
                        class="flex flex-col items-center justify-center p-12 text-center">
                        <DatabaseIcon class="w-16 h-16 text-base-content/20" />
                        <h3 class="text-lg font-semibold text-base-content mt-4">
                            No Records Found
                        </h3>
                        <p class="text-base-content/70 mt-1">
                            {{ search ? 'Try adjusting your search or filters' : 'No data available' }}
                        </p>
                        <button v-if="hasActiveFilters" @click="resetFilters" class="btn btn-ghost btn-sm gap-2 mt-4">
                            <RotateCcwIcon class="w-4 h-4" />
                            Reset Filters
                        </button>
                    </div>

                    <!-- Table View -->
                    <div v-else-if="viewMode === 'table'" class="overflow-x-auto">
                        <table class="table table-compact w-full">
                            <!-- Table Header -->
                            <thead class="bg-base-200/50">
                                <tr>
                                    <th v-for="column in visibleColumns" :key="column.key"
                                        @click="column.sortable && handleSort(column.key)"
                                        :class="['text-base-content', { 'cursor-pointer hover:bg-base-200': column.sortable }]">
                                        <div class="flex items-center gap-2">
                                            {{ column.label }}
                                            <button v-if="column.sortable"
                                                class="btn btn-ghost btn-xs btn-square opacity-50 hover:opacity-100">
                                                <component :is="getSortIcon(column.key)" class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </th>
                                    <th class="text-base-content text-right">Actions</th>
                                </tr>
                            </thead>

                            <!-- Table Body -->
                            <tbody>
                                <tr v-for="(item, index) in tableData" :key="item.id || index"
                                    :class="[
                                        'hover:bg-base-200/50 transition-colors duration-200 group',
                                        getRowClasses(item)
                                    ]">
                                    <table-display-data :tableData="item" :columns="visibleColumns"
                                        :hiddenColumns="hiddenColumns" viewType="table" />
                                    <td class="text-right">
                                        <div
                                            class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                            <!-- Edit Action -->
                                            <template v-if="!custom_edit_route">
                                                <edit :columns="visibleColumns" :endpoint="endpointEdit" :model="model"
                                                    :modelValue="item" :id="item[props.defaultIdKey]"
                                                    :permission="permission" @onEdit="refreshData" />
                                            </template>
                                            <template v-else>
                                                <Link :href="custom_edit_route + item.id"
                                                    class="btn btn-primary btn-sm gap-2">
                                                <PencilIcon class="w-4 h-4" />
                                                <span class="hidden sm:inline">Edit</span>
                                                </Link>
                                            </template>
                                            <!-- Custom Action -->
                                             <template v-if="custom_point_route">
                                                <Link :href="custom_point_route + item.id"
                                                    class="btn btn-secondary btn-sm gap-2">
                                                <PencilIcon class="w-4 h-4" />
                                                <span class="hidden sm:inline">{{ custom_action_name }}</span>
                                                </Link>
                                            </template>
                                            <!-- Delete Action -->
                                            <delete :id="item[props.defaultIdKey]" :endpoint="endpointDelete"
                                                :model="model" :permission="permission" @onDelete="refreshData" />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- List View -->
                    <div v-else class="divide-y divide-base-200">
                        <div v-for="(item, index) in tableData" :key="item.id || index"
                            :class="[
                                'p-4 hover:bg-base-200/50 transition-colors duration-200',
                                getRowClasses(item)
                            ]">
                            <table-display-data :tableData="item" :columns="visibleColumns"
                                :hiddenColumns="hiddenColumns" viewType="list" />
                            <div class="mt-4 flex justify-end gap-2">
                                <!-- Edit Action -->
                                <template v-if="!custom_edit_route">
                                    <edit :columns="visibleColumns" :endpoint="endpointEdit" :model="model" :modelValue="item"
                                        :id="item[props.defaultIdKey]" :permission="permission" @onEdit="refreshData" />
                                </template>
                                <template v-else>
                                    <Link :href="custom_edit_route + item.id" class="btn btn-primary btn-sm gap-2">
                                    <PencilIcon class="w-4 h-4" />
                                    <span class="hidden sm:inline">Edit</span>
                                    </Link>
                                </template>

                                <!-- Delete Action -->
                                <delete :id="item[props.defaultIdKey]" :endpoint="endpointDelete" :model="model"
                                    :permission="permission" @onDelete="refreshData" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    <table-pagination v-if="tableData.length" @onPagination="handlePageChange"
                        :paginationInfo="paginationInfo" :endpoint="endpoint" />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
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
    List as ListIcon
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
    custom_point_route: { type: String, default: null },
    custom_action_name: { type: String, default: null },
    defaultIdKey: { type: String, default: 'id' },
    rowStyling: { type: Object, default: () => ({}) }
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
const orderBy = ref<string | null>(null);
const search = ref<string | null>(null);
const viewMode = ref('table');

// Computed
const totalItems = computed(() => paginationInfo.value.total);

const hasActiveFilters = computed(() => {
    return search.value ||
        filterBy.value !== 'id' ||
        perPage.value !== 10 ||
        orderBy.value !== null;
});

// Add these new refs and computed properties
const hiddenColumns = ref(new Set<string>());

const visibleColumns = computed(() => {
    return props.columns.filter(column => !hiddenColumns.value.has(column.key));
});

// Row styling method
const getRowClasses = (item: any): string => {
    const classes: string[] = [];

    // Simple conditional styling: field value => class
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

    // Advanced styling with operators
    if (props.rowStyling?.advancedStyling) {
        const { conditions, default: defaultStyle } = props.rowStyling.advancedStyling;

        for (const condition of conditions) {
            const fieldValue = getNestedValue(item, condition.field);

            if (checkCondition(condition, fieldValue)) {
                classes.push(condition.classes);
                break; // Use first matching condition
            }
        }

        // If no conditions matched, use default
        if (classes.length === 0 && defaultStyle) {
            classes.push(defaultStyle);
        }
    }

    return classes.join(' ');
};

// Helper function to get nested values
const getNestedValue = (obj: any, path: string): any => {
    if (!obj || !path) return null;

    // Handle fallback relationships (separated by |)
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

    // Regular nested access
    return path.split('.').reduce((current, key) => {
        return current && current[key] !== undefined ? current[key] : null;
    }, obj);
};

// Simple condition checker
const checkCondition = (condition: any, value: any): boolean => {
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
};

// Methods
const getSortIcon = (columnKey: string) => {
    if (filterBy.value !== columnKey) return ArrowUpDownIcon;
    return orderBy.value === 'asc' ? ArrowUpIcon : ArrowDownIcon;
};

const handleSort = (columnKey: string) => {
    if (filterBy.value === columnKey) {
        orderBy.value = orderBy.value === 'asc' ? 'desc' : 'asc';
    } else {
        filterBy.value = columnKey;
        orderBy.value = 'asc';
    }
    fetchData();
};

const activeFilters = ref({});

const fetchData = async (endpoint = props.endpoint) => {
    try {
        isLoading.value = true;
        const response = await axios.post(endpoint, {
            model: props.model,
            columns: props.columns,
            perPage: perPage.value,
            search: search.value,
            sort: filterBy.value,
            filters: activeFilters.value,
            direction: orderBy.value,
            permission: props.permission
        });

        tableData.value = response.data.data;
        paginationInfo.value = {
            currentPage: response.data.current_page,
            lastPage: response.data.last_page,
            perPage: response.data.per_page,
            total: response.data.total,
            links: response.data.links
        };
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

const resetFilters = () => {
    perPage.value = 10;
    filterBy.value = 'id';
    orderBy.value = null;
    search.value = null;
    fetchData();
};

// Event Handlers
const refreshData = () => fetchData();
const handlePageChange = (link: string) => fetchData(link);
const handlePerPageChange = (value: number) => {
    perPage.value = value;
    fetchData();
};

const handleAdvancedFilterChange = (filters) => {
    activeFilters.value = filters;
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

// Add this new method
const updateHiddenColumns = (newHiddenColumns: Set<string>) => {
    hiddenColumns.value = newHiddenColumns;
};

// Initialize
fetchData();
</script>

<style scoped>
/* Table Animations */
.table tr {
    transition: all 0.2s ease;
}

/* Custom Scrollbar */
.overflow-x-auto {
    scrollbar-width: thin;
    scrollbar-color: hsl(var(--p) / 0.3) hsl(var(--b2) / 0.5);
}

.overflow-x-auto::-webkit-scrollbar {
    height: 6px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: hsl(var(--b2) / 0.5);
    border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background-color: hsl(var(--p) / 0.3);
    border-radius: 4px;
    transition: background-color 0.2s ease;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
    background-color: hsl(var(--p) / 0.5);
}

/* Loading Animation */
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.loading {
    animation: pulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Row styling enhancements */
.table tr {
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Enhanced row hover effects */
.hover\:bg-red-100:hover {
    background-color: rgb(254 226 226);
}

.hover\:bg-green-100:hover {
    background-color: rgb(220 252 231);
}

.hover\:bg-yellow-100:hover {
    background-color: rgb(254 249 195);
}

.hover\:bg-blue-100:hover {
    background-color: rgb(219 234 254);
}

.hover\:bg-gray-100:hover {
    background-color: rgb(243 244 246);
}
</style>
