<template>
    <div class="w-full">
        <!-- Main Table Card -->
        <div
            class="relative flex flex-col bg-base-300 rounded-3xl shadow-lg transition-all duration-300 hover:shadow-xl">
            <!-- Enhanced Table Header Section -->
            <div class="relative">
                <!-- Primary Header Row -->
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between p-4 lg:p-6 border-b border-base-content/10">
                    <!-- Title Section -->
                    <div class="flex-1 min-w-0 mb-2 lg:mb-0">
                        <!-- Title Row with Status on Right -->
                        <div class="flex items-center justify-between gap-4 mb-1">
                            <div class="flex items-center gap-2 min-w-0">
                                <TableIcon class="w-4 h-4 lg:w-5 lg:h-5 text-primary flex-shrink-0" />
                                <h1 class="text-base lg:text-lg xl:text-xl font-bold text-base-content truncate">
                                    {{ props.tableTitle }}
                                </h1>
                            </div>

                            <!-- Compact/Advanced Indicators on Right -->
                            <div class="flex items-center gap-1 flex-shrink-0">
                                <span v-if="totalItems" class="text-xs text-base-content/70 font-medium bg-base-100/50 px-2 py-0.5 rounded-full">
                                    {{ totalItems.toLocaleString() }} items
                                </span>
                                <span v-if="superCompactMode" class="badge badge-accent badge-sm px-2 py-1 font-semibold shadow-sm">Ultra</span>
                                <span v-else-if="compactMode" class="badge badge-secondary badge-sm px-2 py-1 font-semibold shadow-sm">Compact</span>
                                <span v-else class="badge badge-primary badge-sm px-2 py-1 font-semibold shadow-sm">Standard</span>
                                <span v-if="hasActiveAdvancedFilters"
                                      class="badge badge-info badge-sm px-2 py-1 font-semibold shadow-sm"
                                      :title="`${enabledAdvancedFiltersCount} of ${totalAdvancedFiltersCount} advanced filters active`">
                                    Filters ({{ enabledAdvancedFiltersCount }}/{{ totalAdvancedFiltersCount }})
                                </span>
                            </div>
                        </div>

                        <!-- Subheader -->
                        <p v-if="props.subheader" class="text-base-content/70 text-xs mb-1">
                            {{ props.subheader }}
                        </p>

                        <!-- Other Status Indicators Row -->
                        <div class="flex flex-wrap items-center gap-2 text-xs text-base-content/70">
                            <!-- Other indicators that don't go on title row -->
                            <div class="flex flex-wrap gap-1">
                                <span v-if="hasModelScopes" class="badge badge-success badge-sm px-2 py-1 font-medium shadow-sm"
                                      :title="`${props.modelScopes.length} model scope(s) active: ${getScopesTitle}`">
                                    <ScopeIcon class="w-3 h-3 mr-1" />
                                    Scoped ({{ props.modelScopes.length }})
                                </span>

                                <span v-if="hasNonDefaultFilters" class="badge badge-warning badge-sm px-2 py-1 font-medium shadow-sm" title="User filters active">
                                    <FilterIcon class="w-3 h-3 mr-1" />
                                    Filtered
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Primary Actions Section -->
                    <div class="flex items-center gap-2 w-full lg:w-auto">
                        <!-- Mobile Menu Toggle -->
                        <button
                            @click="mobileMenuOpen = !mobileMenuOpen"
                            class="btn btn-ghost btn-sm lg:hidden order-last"
                            :class="{ 'btn-active': mobileMenuOpen }"
                        >
                            <MenuIcon v-if="!mobileMenuOpen" class="w-4 h-4" />
                            <XIcon v-else class="w-4 h-4" />
                        </button>

                        <!-- Primary Create Button -->
                        <slot name="new">
                            <create
                                v-if="!props.custom_create_route"
                                :columns="props.columns"
                                :endpoint="props.endpointCreate"
                                :model="props.model"
                                :permission="props.permission"
                                @onCreate="refreshData"
                                class="btn btn-primary btn-sm lg:btn-md gap-2 shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105"
                            />
                            <Link v-else :href="props.custom_create_route"
                                class="btn btn-primary btn-sm lg:btn-md gap-2 shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105">
                                <PlusIcon class="w-4 h-4" />
                                <span class="hidden sm:inline">Create</span>
                            </Link>
                        </slot>                        <!-- Filters Toggle - Mobile/Tablet -->
                        <button
                            @click="showFilters = !showFilters"
                            class="btn btn-ghost btn-sm lg:hidden gap-2"
                            :class="{ 'btn-active': showFilters, 'btn-warning': hasActiveFilters }"
                        >
                            <FilterIcon class="w-4 h-4" />
                            <span v-if="hasActiveFilters" class="badge badge-warning badge-xs">!</span>
                        </button>
                    </div>
                </div>

                <!-- Mobile Filters Panel -->
                <div v-show="showFilters" class="lg:hidden border-b border-base-content/10 bg-base-200/30">
                    <div class="p-4">
                        <!-- Basic Filters -->
                        <table-filter
                            @onPerPage="handlePerPageChange"
                            @onOrderBy="handleOrderChange"
                            @onSearch="handleSearch"
                            @onFilter="handleFilterChange"
                            @onFilterReset="handleFilterReset"
                            :columns="props.columns"
                            :currentPerPage="perPage"
                            :currentFilterBy="filterBy"
                            :currentOrderBy="orderBy"
                            :currentSearch="search"
                        />
                    </div>
                </div>
            </div>

            <!-- Desktop Filters Section -->
            <div class="hidden lg:block">
                <table-filter
                    @onPerPage="handlePerPageChange"
                    @onOrderBy="handleOrderChange"
                    @onSearch="handleSearch"
                    @onFilter="handleFilterChange"
                    @onFilterReset="handleFilterReset"
                    :columns="props.columns"
                    :currentPerPage="perPage"
                    :currentFilterBy="filterBy"
                    :currentOrderBy="orderBy"
                    :currentSearch="search"
                />

                <!-- Table Headers - Positioned after filters -->
                <div class="px-6 py-2 border-b border-base-content/5 bg-base-50">
                    <TableHeaders
                        :view-mode="viewMode"
                        :show-advanced-controls="showAdvancedControls"
                        :show-advanced-filters="showAdvancedFilters"
                        :row-click-navigation-enabled="rowClickNavigationEnabled"
                        :has-active-filters="hasActiveFilters"
                        :has-stored-filters="hasStoredFilters"
                        :has-advanced-filters-active="hasActiveAdvancedFilters"
                        @update:view-mode="viewMode = $event"
                        @update:show-advanced-controls="showColumnSettingsModal = true"
                        @update:show-advanced-filters="showAdvancedFilters = $event"
                        @toggle-row-click="toggleRowClickNavigation"
                        @toggle-cache="handleCacheToggle"
                        @reset-filters="resetAllFilters"
                        @open-column-settings="showColumnSettingsModal = true"
                        @open-export-modal="showExportModal = true"
                    />
                </div>
            </div>

            <!-- Advanced Filters Panel -->
            <div v-show="showAdvancedFilters" class="border-b border-base-content/5 bg-base-100/30">
                <div class="p-4">
                    <AdvancedFilter
                        :columns="props.columns"
                        :advanced-filters="props.advancedFilters"
                        @onFilterChange="handleAdvancedFilterChange"
                        @on-icon-size-change="handleIconSizeChange"
                    />
                </div>
            </div>

            <!-- Advanced Controls Panel (Desktop Only) -->
            <!-- This panel is now replaced by direct modal opening -->
            <div v-show="false" class="hidden lg:block border-b border-base-content/5 bg-base-100/50">
                <div class="p-4">
                    <div class="flex items-center justify-center">
                        <button
                            @click="showColumnSettingsModal = true; showAdvancedControls = false"
                            class="btn btn-primary btn-sm gap-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path>
                            </svg>
                            Open Column Settings
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile Advanced Menu Modal -->
            <div v-show="mobileMenuOpen" class="lg:hidden fixed inset-0 z-50 bg-black/50" @click="mobileMenuOpen = false">
                <div class="absolute right-0 top-0 h-full w-80 max-w-[90vw] bg-base-100 shadow-2xl" @click.stop>
                    <div class="p-4 border-b border-base-content/10">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-lg">Table Options</h3>
                            <button @click="mobileMenuOpen = false" class="btn btn-ghost btn-sm btn-circle">
                                <XIcon class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <div class="p-4 space-y-4 overflow-y-auto">
                        <!-- View Mode -->
                        <div>
                            <label class="text-sm font-medium text-base-content/70 block mb-2">View Mode</label>
                            <div class="btn-group w-full">
                                <button @click="viewMode = 'table'; mobileMenuOpen = false"
                                    :class="['btn btn-sm flex-1', viewMode === 'table' ? 'btn-primary' : 'btn-ghost']">
                                    <TableIcon class="w-4 h-4 mr-2" />
                                    Table
                                </button>
                                <button @click="viewMode = 'list'; mobileMenuOpen = false"
                                    :class="['btn btn-sm flex-1', viewMode === 'list' ? 'btn-primary' : 'btn-ghost']">
                                    <ListIcon class="w-4 h-4 mr-2" />
                                    Cards
                                </button>
                            </div>
                        </div>

                        <!-- Row Click Navigation -->
                        <div>
                            <label class="text-sm font-medium text-base-content/70 block mb-2">Navigation</label>
                            <button
                                @click="toggleRowClickNavigation"
                                :class="[
                                    'btn btn-sm w-full gap-2',
                                    rowClickNavigationEnabled ? 'btn-success' : 'btn-ghost'
                                ]"
                            >
                                <User class="w-4 h-4" />
                                {{ rowClickNavigationEnabled ? 'Click to Edit: ON' : 'Click to Edit: OFF' }}
                            </button>
                        </div>

                        <!-- Column Visibility -->
                        <div>
                            <label class="text-sm font-medium text-base-content/70 block mb-2">Column Settings</label>
                            <button
                                @click="showColumnSettingsModal = true; mobileMenuOpen = false"
                                class="btn btn-sm btn-outline w-full"
                            >
                                Open Column Settings
                            </button>
                        </div>

                        <!-- Export Data -->
                        <div>
                            <label class="text-sm font-medium text-base-content/70 block mb-2">Data Export</label>
                            <button
                                @click="showExportModal = true"
                                :disabled="!tableData.length"
                                class="btn btn-sm w-full justify-start gap-2 bg-gradient-to-r from-primary/10 to-primary/20 border-primary/30 hover:from-primary/20 hover:to-primary/30 text-primary"
                                :class="{ 'opacity-50 cursor-not-allowed': !tableData.length }"
                            >
                                <Download class="w-4 h-4" />
                                Export Data
                                <span class="badge badge-primary badge-sm ml-auto">{{ formatNumber(paginationInfo.total) }}</span>
                            </button>
                        </div>

                        <!-- Reset -->
                        <div>
                            <label class="text-sm font-medium text-base-content/70 block mb-2">Reset Options</label>
                            <button
                                @click="resetAllFilters(); mobileMenuOpen = false"
                                :class="[
                                    'btn btn-warning btn-sm w-full gap-2',
                                    { 'btn-disabled': !hasActiveFilters && !hasStoredFilters }
                                ]"
                                :disabled="!hasActiveFilters && !hasStoredFilters"
                            >
                                <RotateCcwIcon class="w-4 h-4" />
                                Reset All Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Content -->
            <div :class="getContentPadding()">
                <!-- Subtle Loading Indicator -->
                <div v-if="isLoading" class="mb-3 flex items-center justify-center gap-3 p-3 bg-base-200/50 rounded-lg border border-primary/20">
                    <div class="loading loading-spinner loading-sm text-primary"></div>
                    <p class="text-sm text-base-content/70">Loading data...</p>
                </div>

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
                        <button v-if="hasStoredFilters" @click="clearAllStoredFilters" class="btn btn-outline btn-sm gap-2 mt-2">
                            <DatabaseIcon class="w-4 h-4" />
                            Clear Saved Settings
                        </button>
                    </div>

                    <!-- Table View -->
                    <div v-else-if="viewMode === 'table'" class="overflow-x-auto">
                        <table :class="getTableClasses()">
                            <!-- ENHANCED TABLE HEADER WITH SIMPLE SORT INDICATORS -->
                            <thead class="bg-base-200/50">
                                <tr>
                                    <th v-for="(column, index) in displayColumns" :key="column.key"
                                        @click="column.sortable && handleSort(column.key)"
                                        :class="getHeaderClasses(column)"
                                        :title="column.sortable ? `Click to sort by ${column.label}` : column.label"
                                        :style="getColumnStyle(column, index)"
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
                                    :class="getRowClasses(item)"
                                    class="group cursor-pointer"
                                    @click="handleRowClick(item, $event)"
                                >
                                    <table-display-data
                                        :tableData="item"
                                        :columns="displayColumns"
                                        :hiddenColumns="hiddenColumns"
                                        :enabledHiddenFields="enabledHiddenFields"
                                        :viewType="'table'"
                                        :compactMode="compactMode"
                                        :superCompactMode="superCompactMode"
                                        :columnOrder="columnOrder"
                                        :isFirstColumn="true"
                                        :rowClickEnabled="rowClickNavigationEnabled"
                                        :showSettings="false"
                                        @column-click="handleColumnClick"
                                    />
                                    <td :class="getActionsCellClasses()" class="text-right">
                                        <div :class="getActionsContainerClasses()" @click.stop>
                                            <!-- Enhanced Action Buttons - Always Visible -->
                            <!-- Edit Action -->
                            <template v-if="!props.custom_edit_route">
                                <edit
                                    :columns="displayColumns"
                                    :endpoint="props.endpointEdit"
                                    :model="props.model"
                                    :modelValue="item"
                                    :id="item[props.defaultIdKey]"
                                    :permission="props.permission"
                                    @onEdit="refreshData"
                                    :class="getEnhancedActionButtonClasses('edit')"
                                />
                            </template>
                            <template v-else>
                                <Link :href="props.custom_edit_route + (props.custom_edit_route_field ? item[props.custom_edit_route_field] : item[props.defaultIdKey])"
                                    :class="getEnhancedActionButtonClasses('edit')"
                                    :title="superCompactMode ? 'Edit' : ''"
                                    @click.stop
                                >
                                    <PencilIcon :class="getActionIconSize()" />
                                    <span v-if="!superCompactMode" class="hidden sm:inline">Edit</span>
                                </Link>
                            </template>                                            <!-- Custom Action -->
                                            <template v-if="props.custom_point_route">
                                                <Link :href="props.custom_point_route + item[props.defaultIdKey]"
                                                    :class="getEnhancedActionButtonClasses('custom')"
                                                    :title="superCompactMode ? props.custom_action_name : ''"
                                                    @click.stop
                                                >
                                                    <component :is="getCustomActionIcon()" :class="getActionIconSize()" />
                                                    <span v-if="!superCompactMode" class="hidden sm:inline">{{ props.custom_action_name }}</span>
                                                </Link>
                                            </template>

                                            <!-- Custom Links -->
                                            <template v-if="props.customLinks && props.customLinks.length > 0">
                                                <template v-for="(link, index) in props.customLinks" :key="`custom-link-${index}`">
                                                    <a :href="link.url + item[link.field]"
                                                        :target="link.newTab ? '_blank' : '_self'"
                                                        :class="getEnhancedActionButtonClasses('custom')"
                                                        :title="superCompactMode ? link.name : ''"
                                                        @click.stop
                                                    >
                                                        <component :is="getCustomActionIcon()" :class="getActionIconSize()" />
                                                        <span v-if="!superCompactMode" class="hidden sm:inline">{{ link.name }}</span>
                                                    </a>
                                                </template>
                                            </template>

                                            <!-- Delete Action -->
                                            <delete
                                                v-if="!props.disableDelete"
                                                :id="item[props.defaultIdKey]"
                                                :endpoint="props.endpointDelete"
                                                :model="props.model"
                                                :permission="props.permission"
                                                @onDelete="refreshData"
                                                :class="getEnhancedActionButtonClasses('delete')"
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
                            class="cursor-pointer"
                            @click="handleRowClick(item, $event)"
                        >
                            <table-display-data
                                :tableData="item"
                                :columns="displayColumns"
                                :hiddenColumns="hiddenColumns"
                                :enabledHiddenFields="enabledHiddenFields"
                                :viewType="'list'"
                                :compactMode="compactMode"
                                :superCompactMode="superCompactMode"
                                :columnOrder="columnOrder"
                                :rowClickEnabled="rowClickNavigationEnabled"
                                :showSettings="false"
                            />
                            <div :class="getListActionsClasses()" @click.stop>
                                <!-- Enhanced List Actions - Always Visible -->
                                <!-- Edit Action -->
                                <template v-if="!props.custom_edit_route">
                                    <edit
                                        :columns="displayColumns"
                                        :endpoint="props.endpointEdit"
                                        :model="props.model"
                                        :modelValue="item"
                                        :id="item[props.defaultIdKey]"
                                        :permission="props.permission"
                                        @onEdit="refreshData"
                                        :class="getEnhancedActionButtonClasses('edit')"
                                    />
                                </template>
                                <template v-else>
                                    <Link :href="props.custom_edit_route + (props.custom_edit_route_field ? item[props.custom_edit_route_field] : item[props.defaultIdKey])"
                                        :class="getEnhancedActionButtonClasses('edit')"
                                        @click.stop
                                    >
                                        <PencilIcon :class="getActionIconSize()" />
                                        <span v-if="!superCompactMode" class="hidden sm:inline">Edit</span>
                                    </Link>
                                </template>

                                <!-- Custom Action -->
                                <template v-if="props.custom_point_route">
                                    <Link :href="props.custom_point_route + item[props.defaultIdKey]"
                                        :class="getEnhancedActionButtonClasses('custom')"
                                        @click.stop
                                    >
                                        <component :is="getCustomActionIcon()" :class="getActionIconSize()" />
                                        <span v-if="!superCompactMode" class="hidden sm:inline">{{ props.custom_action_name }}</span>
                                    </Link>
                                </template>

                                <!-- Custom Links -->
                                <template v-if="props.customLinks && props.customLinks.length > 0">
                                    <template v-for="(link, index) in props.customLinks" :key="`list-custom-link-${index}`">
                                        <a :href="link.url + item[link.field]"
                                            :target="link.newTab ? '_blank' : '_self'"
                                            :class="getEnhancedActionButtonClasses('custom')"
                                            :title="superCompactMode ? link.name : ''"
                                            @click.stop
                                        >
                                            <component :is="getCustomActionIcon()" :class="getActionIconSize()" />
                                            <span v-if="!superCompactMode" class="hidden sm:inline">{{ link.name }}</span>
                                        </a>
                                    </template>
                                </template>

                                <!-- Delete Action -->
                                <delete
                                    v-if="!props.disableDelete"
                                    :id="item[props.defaultIdKey]"
                                    :endpoint="props.endpointDelete"
                                    :model="props.model"
                                    :permission="props.permission"
                                    @onDelete="refreshData"
                                    :class="getEnhancedActionButtonClasses('delete')"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div :class="getPaginationMargin()">
                    <table-pagination
                        v-if="tableData.length"
                        @onPagination="handlePageChange"
                        @onPageSizeChange="handlePerPageChange"
                        :paginationInfo="paginationInfo"
                        :endpoint="props.endpoint"
                    />
                </div>
            </div>
        </div>
    </div>

    <!-- Column Settings Modal -->
    <ColumnVisibilityManager
        :columns="props.columns"
        :storage-key="`${props.tableTitle}-table-settings`"
        :show-modal="showColumnSettingsModal"
        @update:hiddenColumns="updateHiddenColumns"
        @update:compactMode="updateCompactMode"
        @update:superCompactMode="updateSuperCompactMode"
        @update:columnOrder="updateColumnOrder"
        @update:enabledHiddenFields="updateEnabledHiddenFields"
        @close="showColumnSettingsModal = false"
    />

    <!-- Export Data Modal -->
    <ExportData
        v-if="showExportModal"
        :data="tableData"
        :columns="displayColumns"
        :hiddenColumns="hiddenColumns"
        :filename="props.tableTitle.toLowerCase().replace(/\s+/g, '-')"
        :total-records="paginationInfo.total"
        :is-modal-open="showExportModal"
        @close-modal="showExportModal = false"
    />
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
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
    Settings as SettingsIcon,
    User,
    Menu as MenuIcon,
    X as XIcon,
    Filter as FilterIcon,
    Search as SearchIcon,
    Plus as PlusIcon,
    ChevronDown as ChevronDownIcon,
    ChevronUp as ChevronUpIcon,
    Download,
    Target as ScopeIcon
} from 'lucide-vue-next';

// Components
import Delete from './components/crud/delete.vue';
import Create from './components/crud/create.vue';
import Edit from './components/crud/edit.vue';
import TableFilter from './components/filter/filter.vue';
import TablePagination from './components/filter/pagination.vue';
import TableDisplayData from './components/tableDataDisplay.vue';
import ColumnVisibilityManager from './components/filter/ColumnVisibilityManager.vue';
import ExportData from './components/filter/ExportData.vue';
import TableHeaders from './components/filter/TableHeaders.vue';
import AdvancedFilter from './components/filter/advancedFilter.vue';
import { useTableCache } from './composables/useTableCache';

// ✨ ENHANCED: Props with model scopes support
const props = defineProps({
    tableTitle: { type: String, default: 'Table' },
    subheader: { type: String, default: null },
    columns: { type: Array, default: () => [] },
    model: { type: String, required: true },
    endpoint: { type: String, required: true },
    endpointDelete: { type: String, required: true },
    endpointCreate: { type: String, required: true },
    endpointEdit: { type: String, required: true },
    permission: { type: String, default: null },
    custom_edit_route: { type: String, default: null },
    custom_edit_route_field: { type: String, default: null },
    custom_create_route: { type: String, default: null },
    custom_point_route: { type: String, default: null },
    custom_action_name: { type: String, default: null },
    defaultIdKey: { type: String, default: 'id' },
    rowStyling: { type: Object, default: () => ({}) },
    disableDelete: { type: Boolean, default: false },
    defaultFilters: { type: Object, default: () => ({}) },
    advancedFilters: { type: Array, default: () => [] },
    modelScopes: { type: Array, default: () => [] },
    customLinks: { type: Array, default: () => [] }
});

// State
const isLoading = ref(false);
let currentRequestController: AbortController | null = null;
let fetchSeq = 0; // monotonically increasing fetch id
let fetchTimeout: ReturnType<typeof setTimeout> | null = null;

function scheduleFetch(delay = 150) {
    if (fetchTimeout) clearTimeout(fetchTimeout as any);
    fetchTimeout = setTimeout(() => {
        fetchTimeout = null;
        fetchData();
    }, delay);
}
const tableData = ref([]);
const paginationInfo = ref({
    currentPage: 1,
    lastPage: 1,
    perPage: 10,
    total: 0,
    links: []
});

// ✨ NEW: Storage key for persistent filters
const filterStorageKey = computed(() => `table-filters-${props.tableTitle.toLowerCase().replace(/\s+/g, '-')}`);

// ✨ NEW: Check if filters exist in storage BEFORE initializing reactive refs
const getStoredFilters = () => {
    try {
        const savedFilters = localStorage.getItem(filterStorageKey.value);
        return savedFilters ? JSON.parse(savedFilters) : null;
    } catch (error) {
        console.warn('Failed to read stored filters:', error);
        return null;
    }
};

// ✨ ENHANCED: Initialize state with stored values first, then defaults
const storedFilters = getStoredFilters();
console.log('Checking for stored filters on init:', storedFilters);

const perPage = ref(storedFilters?.perPage ?? 10);
const filterBy = ref(storedFilters?.filterBy ?? props.defaultIdKey);
const orderBy = ref(storedFilters?.orderBy ?? 'desc');
const search = ref(storedFilters?.search ?? null);
const viewMode = ref(storedFilters?.viewMode ?? 'table');

const currentAdvancedFilters = ref(storedFilters?.currentAdvancedFilters ?? []);

// ✨ Enhanced state for density and column management - Initialize with stored values
const hiddenColumns = ref(new Set<string>(storedFilters?.hiddenColumns ?? []));
const enabledHiddenFields = ref(new Set<string>(storedFilters?.enabledHiddenFields ?? []));
const compactMode = ref(storedFilters?.compactMode ?? true);
const superCompactMode = ref(storedFilters?.superCompactMode ?? false);
const columnOrder = ref<string[]>(storedFilters?.columnOrder ?? []);

// ✨ NEW: Row click navigation state
const rowClickNavigationEnabled = ref((() => {
    const stored = localStorage.getItem('table-row-click-navigation');
    return stored ? JSON.parse(stored) : false; // Default is false
})());

// ✨ NEW: Initialize cache composable
const {
    cacheEnabled,
    generateCacheKey,
    getCachedData,
    setCachedData,
    clearCacheEntry,
    toggleCache
} = useTableCache({ enabled: true });

// ✨ NEW: Header organization state
const showAdvancedControls = ref(false);
const showAdvancedFilters = ref(false);
const showFilters = ref(false);
const mobileMenuOpen = ref(false);
const showColumnSettingsModal = ref(false);
const showExportModal = ref(false);

// ✨ Filter state management - Initialize with stored or default filters
const activeFilters = ref({
    ...props.defaultFilters,
    ...(storedFilters?.activeFilters ?? {})
});

// ✨ NEW: Filter persistence methods
const saveFiltersToStorage = () => {
    const filtersToSave = {
        perPage: perPage.value,
        filterBy: filterBy.value,
        orderBy: orderBy.value,
        search: search.value,
        viewMode: viewMode.value,
        activeFilters: activeFilters.value,
        currentAdvancedFilters: currentAdvancedFilters.value,
        hiddenColumns: Array.from(hiddenColumns.value),
        compactMode: compactMode.value,
        superCompactMode: superCompactMode.value,
        columnOrder: columnOrder.value
    };

    try {
        localStorage.setItem(filterStorageKey.value, JSON.stringify(filtersToSave));
        console.log('Filters saved to storage:', filtersToSave);
    } catch (error) {
        console.warn('Failed to save filters to localStorage:', error);
    }
};

const loadFiltersFromStorage = () => {
    try {
        const savedFilters = localStorage.getItem(filterStorageKey.value);
        if (savedFilters) {
            const parsedFilters = JSON.parse(savedFilters);
            console.log('Re-loading filters from storage during runtime:', parsedFilters);

            // Apply saved values (this is now mainly for runtime updates)
            perPage.value = parsedFilters.perPage !== undefined ? parsedFilters.perPage : perPage.value;
            filterBy.value = parsedFilters.filterBy !== undefined ? parsedFilters.filterBy : filterBy.value;
            orderBy.value = parsedFilters.orderBy !== undefined ? parsedFilters.orderBy : orderBy.value;
            search.value = parsedFilters.search !== undefined ? parsedFilters.search : search.value;
            viewMode.value = parsedFilters.viewMode !== undefined ? parsedFilters.viewMode : viewMode.value;

            // Merge saved active filters with default filters
            activeFilters.value = {
                ...props.defaultFilters,
                ...(parsedFilters.activeFilters || {})
            };

            // Restore advanced filters
            if (parsedFilters.currentAdvancedFilters) {
                currentAdvancedFilters.value = parsedFilters.currentAdvancedFilters;
            }

            // Restore column settings
            if (parsedFilters.hiddenColumns) {
                hiddenColumns.value = new Set(parsedFilters.hiddenColumns);
            }
            if (parsedFilters.compactMode !== undefined) {
                compactMode.value = parsedFilters.compactMode;
            }
            if (parsedFilters.superCompactMode !== undefined) {
                superCompactMode.value = parsedFilters.superCompactMode;
            }
            if (parsedFilters.columnOrder) {
                columnOrder.value = parsedFilters.columnOrder;
            }

            console.log('Filters re-loaded successfully:', {
                perPage: perPage.value,
                filterBy: filterBy.value,
                orderBy: orderBy.value,
                search: search.value,
                viewMode: viewMode.value
            });

            return true;
        }
    } catch (error) {
        console.warn('Failed to load filters from localStorage:', error);
    }
    return false;
};

const clearStoredFilters = () => {
    try {
        localStorage.removeItem(filterStorageKey.value);
        console.log('Stored filters cleared');
    } catch (error) {
        console.warn('Failed to clear stored filters:', error);
    }
};

// Helper function for number formatting
const formatNumber = (num: number): string => {
    if (num === null || num === undefined) return '0';
    return new Intl.NumberFormat().format(num);
};

// Computed
const totalItems = computed(() => paginationInfo.value.total);

const hasModelScopes = computed(() => {
    return props.modelScopes && props.modelScopes.length > 0;
});

const getScopesTitle = computed(() => {
    if (!props.modelScopes || props.modelScopes.length === 0) return '';
    return props.modelScopes.map(scope => scope.name).join(', ');
});

const hasActiveAdvancedFilters = computed(() => {
    return currentAdvancedFilters.value && currentAdvancedFilters.value.length > 0;
});

const enabledAdvancedFiltersCount = computed(() => {
    return currentAdvancedFilters.value.filter(f => f.enabled !== false).length;
});

const totalAdvancedFiltersCount = computed(() => {
    return props.advancedFilters ? props.advancedFilters.length : 0;
});

// Export-related computed properties
const exportColumns = computed(() => {
    return props.columns || [];
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

// ✨ NEW: Check if there are stored filters
const hasStoredFilters = computed(() => {
    try {
        const savedFilters = localStorage.getItem(filterStorageKey.value);
        return savedFilters !== null && savedFilters !== undefined;
    } catch {
        return false;
    }
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

    return orderedColumns
        .filter(column => !hiddenColumns.value.has(column.key))
        .filter(column => !column.hidden || enabledHiddenFields.value.has(column.key)); // Include hidden fields if they're enabled
});

// ✨ NEW: Advanced filter change handler
const handleAdvancedFilterChange = (userFilters, modifiedAdvancedFilters) => {
    // Update the advanced filters state
    currentAdvancedFilters.value = modifiedAdvancedFilters || [];

    // Merge user filters with default filters
    activeFilters.value = { ...props.defaultFilters, ...userFilters };

    // ✨ NEW: Save filters after advanced filter change
    saveFiltersToStorage();

    // Refresh the data
    scheduleFetch();
};

// ✨ Enhanced row interaction
function handleRowClick(item: any, event?: Event) {
    // If row click navigation is disabled, do nothing
    if (!rowClickNavigationEnabled.value) {
        return;
    }

    // Check if the click came from a link, button, or other interactive element
    if (event) {
        const target = event.target as HTMLElement;

        // If clicking on a link, button, or other interactive element, don't navigate
        if (target.tagName === 'A' ||
            target.tagName === 'BUTTON' ||
            target.closest('a') ||
            target.closest('button') ||
            target.closest('[role="button"]') ||
            target.classList.contains('btn') ||
            target.closest('.btn') ||
            target.closest('.modal') ||
            target.closest('.dropdown') ||
            target.closest('[data-no-row-click]') ||
            target.closest('.actions-container')) {
            // Allow the original action to proceed
            return;
        }
    }

    // Navigate to edit/detail page when row is clicked
    const route = props.custom_edit_route ?
        props.custom_edit_route + (props.custom_edit_route_field ? item[props.custom_edit_route_field] : item[props.defaultIdKey]) :
        props.custom_point_route ?
        props.custom_point_route + item[props.defaultIdKey] : null;

    if (route) {
        // Use Inertia.js navigation
        window.location.href = route;
    }
}

function handleColumnClick(event: Event, column: any, item: any) {
    // Handle column-specific clicks (like ID column)
    handleRowClick(item, event);
}

// ✨ Enhanced column width management
function getColumnStyle(column: any, index: number): string {
    // Auto-expand columns to use full width on larger screens
    const styles: string[] = [];

    // Set minimum widths based on column type and content
    if (index === 0) {
        // First column (usually ID) - smaller width
        styles.push('min-width: 80px; max-width: 120px;');
    } else if (column.type === 'text' && column.key.includes('description')) {
        // Description columns get more space
        styles.push('min-width: 200px; max-width: 400px;');
    } else if (column.type === 'number' || column.key.includes('score')) {
        // Number columns are narrower
        styles.push('min-width: 80px; max-width: 120px;');
    } else {
        // Default column width
        styles.push('min-width: 120px; max-width: 250px;');
    }

    // Allow columns to expand on larger screens
    styles.push('width: auto;');

    return styles.join(' ');
}

// ✨ Enhanced action button styling - Always visible
function getEnhancedActionButtonClasses(type: 'edit' | 'custom' | 'delete'): string {
    const baseClasses = 'btn transition-all duration-200 shadow-md hover:shadow-lg';
    let typeClasses = '';

    switch (type) {
        case 'edit':
            typeClasses = 'btn-primary hover:btn-primary-focus';
            break;
        case 'custom':
            typeClasses = 'btn-secondary hover:btn-secondary-focus';
            break;
        case 'delete':
            typeClasses = 'btn-error hover:btn-error-focus';
            break;
    }

    if (superCompactMode.value) {
        return `${baseClasses} ${typeClasses} btn-xs px-2`;
    }
    if (compactMode.value) {
        return `${baseClasses} ${typeClasses} btn-sm px-3`;
    }
    return `${baseClasses} ${typeClasses} btn-sm px-4`;
}

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
    const baseClasses = 'table w-full table-auto'; // Added table-auto for auto-sizing
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
        return [...baseClasses, 'px-2 py-1 text-xs leading-tight'].join(' ');
    }
    if (compactMode.value) {
        return [...baseClasses, 'px-3 py-2 text-xs'].join(' ');
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
        return `${baseClasses} px-2 py-1 text-xs w-24`;
    }
    if (compactMode.value) {
        return `${baseClasses} px-3 py-2 text-xs w-32`;
    }
    return `${baseClasses} px-4 py-3 text-sm w-40`;
}

function getActionsHeaderText(): string {
    if (superCompactMode.value) return 'Actions';
    if (compactMode.value) return 'Actions';
    return 'Actions';
}

function getRowClasses(item: any): string {
    const baseClasses = 'hover:bg-base-200/50 transition-all duration-200 group hover:shadow-sm';
    const conditionalClasses = getRowConditionalClasses(item);
    const heightClass = superCompactMode.value ? 'h-8' : (compactMode.value ? 'h-10' : 'h-auto');

    return `${baseClasses} ${conditionalClasses} ${heightClass}`;
}

function getActionsCellClasses(): string {
    if (superCompactMode.value) return 'px-2 py-1';
    if (compactMode.value) return 'px-3 py-2';
    return 'px-4 py-3';
}

function getActionsContainerClasses(): string {
    // Actions are now always visible, not just on hover
    const baseClasses = 'flex items-center justify-end transition-all duration-200 actions-container';
    if (superCompactMode.value) return `${baseClasses} gap-1`;
    if (compactMode.value) return `${baseClasses} gap-2`;
    return `${baseClasses} gap-3`;
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
    const baseClasses = 'hover:bg-base-200/50 transition-all duration-200 hover:shadow-sm';
    const conditionalClasses = getRowConditionalClasses(item);
    const paddingClass = superCompactMode.value ? 'p-2' : (compactMode.value ? 'p-3' : 'p-4');

    return `${baseClasses} ${conditionalClasses} ${paddingClass}`;
}

function getListActionsClasses(): string {
    const baseClasses = 'flex justify-end actions-container';
    const gapClass = superCompactMode.value ? 'gap-1' : (compactMode.value ? 'gap-2' : 'gap-3');
    const marginClass = superCompactMode.value ? 'mt-2' : (compactMode.value ? 'mt-3' : 'mt-4');

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

    // ✨ NEW: Save filters after sorting
    saveFiltersToStorage();
    scheduleFetch();
}

function getColumnIcon(column: any) {
    // Add your column icon logic here
    return SettingsIcon; // Default icon
}

function getCustomActionIcon() {
    return SettingsIcon; // Customize based on your needs
}

const emit = defineEmits(['tableDataLoaded']);

// ✨ ENHANCED: Fetch data with CLIENT-SIDE caching
const fetchData = async (endpoint = props.endpoint) => {
    let seq = 0;
    try {
        // Cancel any in-flight request
        try {
            currentRequestController?.abort();
        } catch {}

        seq = ++fetchSeq;
        const controller = new AbortController();
        currentRequestController = controller;

        // ✨ ENHANCED: Build request payload with model scopes support
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

        // ✨ NEW: Add model scopes if they exist
        if (props.modelScopes && props.modelScopes.length > 0) {
            requestPayload.modelScopes = props.modelScopes;
        }

        // ✨ ENHANCED: Use current advanced filters instead of props
        if (currentAdvancedFilters.value && currentAdvancedFilters.value.length > 0) {
            requestPayload.advancedFilters = currentAdvancedFilters.value;
        }

        // ✨ CLIENT-SIDE CACHE: Check if we have cached data FIRST
        const clientCacheKey = generateCacheKey(requestPayload, props.tableTitle || 'default', endpoint);
        const cached = clientCacheKey ? getCachedData(clientCacheKey) : null;

        // Load cached data immediately for instant display (with validation)
        if (cached && cached.responseData && cached.responseData.data) {
            console.log('⚡ Loading from cache instantly');
            tableData.value = cached.responseData.data;
            paginationInfo.value = {
                currentPage: cached.responseData.current_page,
                lastPage: cached.responseData.last_page,
                perPage: cached.responseData.per_page,
                total: cached.responseData.total,
                links: cached.responseData.links
            };
            isLoading.value = false;
            emit('tableDataLoaded', tableData.value);
        } else {
            // Clear invalid cache using composable
            if (cached && clientCacheKey) {
                console.log('🗑️ Clearing invalid cache');
                clearCacheEntry(clientCacheKey);
            }
            isLoading.value = true;
        }

        // Always check server for updates (but UI already shows cached data)
        const response = await axios.post(endpoint, requestPayload, { signal: controller.signal });

        // Ignore out-of-order responses
        if (seq !== fetchSeq) return;

        // Check if cache is still valid
        if (cached && cached.timestamp === response.data.cache_timestamp) {
            console.log('✅ Cache is still valid, no update needed');
            isLoading.value = false;
            return; // Data hasn't changed, keep using cache
        }

        // Data changed or no cache - update with fresh data
        console.log(cached ? '🔄 Cache outdated, updating with fresh data' : '💾 No cache, storing fresh data');

        tableData.value = response.data.data;
        paginationInfo.value = {
            currentPage: response.data.current_page,
            lastPage: response.data.last_page,
            perPage: response.data.per_page,
            total: response.data.total,
            links: response.data.links
        };

        // ✨ CLIENT-SIDE CACHE: Store the complete response if cacheable
        if (clientCacheKey && response.data.cache_timestamp) {
            setCachedData(clientCacheKey, response.data, response.data.cache_timestamp);
        }

        // Emit the table data to parent
        emit('tableDataLoaded', tableData.value);
    } catch (error: any) {
        console.error('Fetch data error:', error);

        // Ignore cancellation errors
        if (axios.isCancel?.(error) || error?.name === 'CanceledError' || error?.name === 'AbortError' || error?.code === 'ERR_CANCELED') {
            return;
        }

        if (error.response?.data?.errors) {
            Object.values(error.response.data.errors).forEach((errorMessages: any) => {
                if (Array.isArray(errorMessages)) {
                    errorMessages.forEach(msg => {
                        startWindToast('error', msg);
                    });
                } else {
                    startWindToast('error', errorMessages);
                }
            });
        } else if (error.response?.data?.message) {
            startWindToast('error', error.response.data.message);
        } else {
            startWindToast('error', 'Failed to fetch data');
        }
    } finally {
        // Clear loading only if this was the latest request
        if (seq === fetchSeq) {
            isLoading.value = false;
        }
    }
};

// ✨ ENHANCED: Reset filters respecting defaults and advanced filters
const resetFilters = () => {
    perPage.value = 10;
    filterBy.value = props.defaultIdKey;
    orderBy.value = 'desc';
    search.value = null;
    viewMode.value = 'table';
    // Reset to default filters instead of empty object
    activeFilters.value = { ...props.defaultFilters };

    // ✨ NEW: Reset advanced filters to original state
    if (props.advancedFilters && props.advancedFilters.length > 0) {
        currentAdvancedFilters.value = props.advancedFilters.map(filter => ({
            ...filter,
            enabled: filter.enabled !== false
        }));
    } else {
        currentAdvancedFilters.value = [];
    }

    // ✨ NEW: Save the reset state
    saveFiltersToStorage();
    scheduleFetch();
};

// ✨ NEW: Reset all filters and clear storage
const resetAllFilters = () => {
    resetFilters();
    clearStoredFilters();
};

// ✨ NEW: Clear all stored filters (for empty state button)
const clearAllStoredFilters = () => {
    clearStoredFilters();
    resetFilters();
};

// Event Handlers
const refreshData = () => fetchData();
const handlePageChange = (link: string) => fetchData(link);
const handlePerPageChange = (value: number) => {
    perPage.value = value;
    // ✨ NEW: Save filters after per page change
    saveFiltersToStorage();
    scheduleFetch();
};

const handleFilterChange = (value: string) => {
    filterBy.value = value;
    // ✨ NEW: Save filters after filter change
    saveFiltersToStorage();
    scheduleFetch();
};

const handleOrderChange = (value: string) => {
    orderBy.value = value;
    // ✨ NEW: Save filters after order change
    saveFiltersToStorage();
    scheduleFetch();
};

const handleSearch = (value: string) => {
    if (value && value.length > 2) {
        search.value = value;
    // ✨ NEW: Save filters after search
    saveFiltersToStorage();
    scheduleFetch();
    } else if (!value) {
        search.value = null;
        // ✨ NEW: Save filters after search clear
        saveFiltersToStorage();
    scheduleFetch();
    }
};

const handleFilterReset = (data: any) => {
    perPage.value = data.perPage;
    filterBy.value = data.filterBy;
    orderBy.value = data.orderBy;
    search.value = data.search;
    // ✨ NEW: Save filters after reset
    saveFiltersToStorage();
    scheduleFetch();
};

// Enhanced event handlers for density and column management
const updateHiddenColumns = (newHiddenColumns: Set<string>) => {
    hiddenColumns.value = newHiddenColumns;
    // ✨ NEW: Save filters after column visibility change
    saveFiltersToStorage();
};

const updateEnabledHiddenFields = (newEnabledHiddenFields: Set<string>) => {
    enabledHiddenFields.value = newEnabledHiddenFields;
    // ✨ NEW: Save filters after enabled hidden fields change
    saveFiltersToStorage();
};

const updateCompactMode = (newCompactMode: boolean) => {
    compactMode.value = newCompactMode;
    // If compact mode is disabled, also disable super compact
    if (!newCompactMode && superCompactMode.value) {
        superCompactMode.value = false;
    }
    // ✨ NEW: Save filters after compact mode change
    saveFiltersToStorage();
};

const updateSuperCompactMode = (newSuperCompactMode: boolean) => {
    superCompactMode.value = newSuperCompactMode;
    // Super compact mode requires compact mode
    if (newSuperCompactMode && !compactMode.value) {
        compactMode.value = true;
    }
    // ✨ NEW: Save filters after super compact mode change
    saveFiltersToStorage();
};

const updateColumnOrder = (newOrder: string[]) => {
    columnOrder.value = newOrder;
    // ✨ NEW: Save filters after column order change
    saveFiltersToStorage();
};

// ✨ NEW: Row click navigation toggle
const toggleRowClickNavigation = () => {
    rowClickNavigationEnabled.value = !rowClickNavigationEnabled.value;
    localStorage.setItem('table-row-click-navigation', JSON.stringify(rowClickNavigationEnabled.value));
};

// ✨ NEW: Cache toggle handler - Uses composable
const handleCacheToggle = (enabled: boolean) => {
    toggleCache(enabled, () => {
        // Refresh data after cache is toggled
        fetchData();
    });
};

// ✨ NEW: Handle export data callback
const handleExportData = () => {
    // This method will be called by TableHeaders component when export is triggered
    // The actual export functionality is handled by the ExportData component
    console.log('Export data triggered');
};

// Handle icon size changes from advanced filter
const handleIconSizeChange = (size: number) => {
    // Apply the icon size globally to the entire table
    document.documentElement.style.setProperty('--table-icon-size', `${size}px`);

    // Optional: Save to component storage or emit to parent if needed
    console.log('Icon size changed to:', size);
};

// ✨ NEW: Watch for changes in advancedFilters prop
watch(() => props.advancedFilters, (newFilters) => {
    if (newFilters && newFilters.length > 0) {
        currentAdvancedFilters.value = newFilters.map(filter => ({
            ...filter,
            enabled: filter.enabled !== false
        }));
        console.log('Advanced filters prop changed:', newFilters);
    }
}, { immediate: true, deep: true });

// ✨ NEW: Watch for view mode changes and save to storage
watch(viewMode, (newViewMode) => {
    saveFiltersToStorage();
    console.log('View mode changed and saved:', newViewMode);
});

// ✨ NEW: Close mobile menu when clicking outside or changing view
watch(viewMode, () => {
    mobileMenuOpen.value = false;
});

// ✨ NEW: Close mobile menu on window resize to desktop
if (typeof window !== 'undefined') {
    const handleResize = () => {
        if (window.innerWidth >= 1024) {
            mobileMenuOpen.value = false;
            showFilters.value = false;
        }
    };

    onMounted(() => {
        window.addEventListener('resize', handleResize);
    });
}

// ✨ ENHANCED: Initialize component - filters are already loaded during ref initialization
onMounted(() => {
    console.log('Component mounting...');

    // Log what we started with (already loaded from storage or defaults)
    console.log('Initial state (from storage or defaults):', {
        perPage: perPage.value,
        filterBy: filterBy.value,
        orderBy: orderBy.value,
        search: search.value,
        viewMode: viewMode.value,
        activeFilters: activeFilters.value,
        hasStoredFilters: hasStoredFilters.value
    });

    // ✨ NEW: Handle advanced filters initialization
    if (!storedFilters && props.advancedFilters && props.advancedFilters.length > 0) {
        // Only set default advanced filters if no stored filters exist
        currentAdvancedFilters.value = props.advancedFilters.map(filter => ({
            ...filter,
            enabled: filter.enabled !== false
        }));
        console.log('Advanced filters initialized with defaults:', currentAdvancedFilters.value);
    } else if (storedFilters) {
        console.log('Advanced filters loaded from storage:', currentAdvancedFilters.value);
    }

    // ✨ NEW: Log model scopes for debugging
    if (props.modelScopes && props.modelScopes.length > 0) {
        console.log('Model scopes loaded:', props.modelScopes);
        console.log('Model scopes details:', props.modelScopes.map(scope => ({
            name: scope.name,
            parameters: scope.parameters || []
        })));
    }

    // Always ensure we have default filters merged
    if (props.defaultFilters && Object.keys(props.defaultFilters).length > 0) {
        activeFilters.value = {
            ...props.defaultFilters,
            ...activeFilters.value
        };
        console.log('Final active filters with defaults merged:', activeFilters.value);
    }

    console.log('Ready to fetch data with state:', {
        perPage: perPage.value,
        filterBy: filterBy.value,
        orderBy: orderBy.value,
        search: search.value,
        viewMode: viewMode.value,
        activeFilters: activeFilters.value
    });

    // Fetch data with the properly initialized state
    fetchData();
});

onUnmounted(() => {
    if (fetchTimeout) clearTimeout(fetchTimeout as any);
    try { currentRequestController?.abort(); } catch {}
});
</script>

<style scoped>
/* ===================================================================
   ENHANCED TABLE STYLING FOR BETTER VISIBILITY & USABILITY
   =================================================================== */

/* Base table improvements */
.table-auto {
    table-layout: auto;
    border-spacing: 0;
    border-collapse: separate;
}

/* Enhanced table container */
.overflow-x-auto {
    border-radius: 0.75rem;
    background: hsl(var(--b1));
    box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
}

/* ===================================================================
   IMPROVED ROW AND CELL STYLING
   =================================================================== */

/* Enhanced row hover effects with better contrast */
.group:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    background: hsl(var(--b2)) !important;
    transition: all 0.2s ease-in-out;
}

/* Base table cell improvements */
th, td {
    border-bottom: 1px solid hsl(var(--b3));
    position: relative;
    transition: all 0.15s ease-in-out;
}

/* Header improvements */
th {
    background: hsl(var(--b2));
    font-weight: 600;
    font-size: 0.875rem;
    color: hsl(var(--bc));
    text-transform: none;
    letter-spacing: 0.025em;
    padding: 1rem;
    border-bottom: 2px solid hsl(var(--primary) / 0.2);
}

/* Data cell improvements */
td {
    background: hsl(var(--b1));
    padding: 0.875rem 1rem;
    font-size: 0.875rem;
    line-height: 1.5;
    vertical-align: middle;
}

/* ===================================================================
   COMPACT MODE ENHANCEMENTS
   =================================================================== */

/* Standard compact mode - improved readability */
.table-compact th {
    padding: 0.75rem 0.875rem;
    font-size: 0.8125rem;
    font-weight: 600;
    background: hsl(var(--b2));
    border-bottom: 2px solid hsl(var(--primary) / 0.3);
}

.table-compact td {
    padding: 0.75rem 0.875rem;
    font-size: 0.8125rem;
    line-height: 1.4;
    min-height: 2.5rem;
}

/* Super compact mode - maximum data density while maintaining readability */
.table-super-compact th {
    padding: 0.5rem 0.625rem;
    font-size: 0.75rem;
    font-weight: 600;
    background: hsl(var(--b2));
    border-bottom: 1px solid hsl(var(--primary) / 0.4);
    line-height: 1.2;
}

.table-super-compact td {
    padding: 0.5rem 0.625rem;
    font-size: 0.75rem;
    line-height: 1.3;
    min-height: 2rem;
    vertical-align: top;
}

/* ===================================================================
   ENHANCED INTERACTIVE ELEMENTS
   =================================================================== */

/* Action button enhancements */
.btn {
    border: 1px solid transparent;
    font-weight: 500;
    transition: all 0.2s ease-in-out;
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
}

.btn:focus {
    outline: 2px solid hsl(var(--primary));
    outline-offset: 2px;
    box-shadow: 0 0 0 3px hsl(var(--primary) / 0.2);
}

/* Enhanced cursor styling */
.cursor-pointer {
    cursor: pointer;
    transition: all 0.15s ease-in-out;
}

.cursor-pointer:hover {
    background: hsl(var(--b2)) !important;
}

/* ===================================================================
   BADGE AND STATUS IMPROVEMENTS
   =================================================================== */

/* Enhanced badge visibility */
.badge {
    font-weight: 600;
    letter-spacing: 0.025em;
    border: 1px solid currentColor;
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    transition: all 0.2s ease-in-out;
}

.badge:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
}

/* ===================================================================
   RESPONSIVE DESIGN IMPROVEMENTS
   =================================================================== */

/* Desktop enhancements */
@media (min-width: 1024px) {
    /* Standard mode desktop */
    .table:not(.table-compact):not(.table-super-compact) th {
        padding: 1.25rem 1.5rem;
        font-size: 0.9375rem;
    }

    .table:not(.table-compact):not(.table-super-compact) td {
        padding: 1.25rem 1.5rem;
        font-size: 0.9375rem;
    }

    /* Improved text handling for desktop */
    th, td {
        overflow: visible;
        text-overflow: clip;
        white-space: normal;
        word-wrap: break-word;
    }
}

/* Tablet improvements */
@media (min-width: 768px) and (max-width: 1023px) {
    th, td {
        padding: 0.75rem;
        font-size: 0.8125rem;
    }
}

/* Mobile improvements */
@media (max-width: 767px) {
    .table-responsive {
        font-size: 0.75rem;
    }

    th, td {
        padding: 0.5rem;
        min-width: 80px;
    }

    /* Force super compact on mobile for better fit */
    .table th {
        padding: 0.375rem 0.5rem;
        font-size: 0.6875rem;
    }

    .table td {
        padding: 0.375rem 0.5rem;
        font-size: 0.6875rem;
        line-height: 1.2;
    }
}

/* ===================================================================
   ACCESSIBILITY AND READABILITY IMPROVEMENTS
   =================================================================== */

/* Improved text contrast and readability */
.text-base-content\/70 {
  color: hsl(var(--bc) / 0.8) !important; /* Increased from 0.7 to 0.8 for better contrast */
}

.text-base-content\/60 {
  color: hsl(var(--bc) / 0.75) !important; /* Increased from 0.6 to 0.75 for better contrast */
}

/* Enhanced form controls visibility */
input, select, textarea {
  border: 1px solid hsl(var(--b3)) !important;
  background: hsl(var(--b1)) !important;
  transition: all 0.2s ease-in-out;
}

input:focus, select:focus, textarea:focus {
  border-color: hsl(var(--primary)) !important;
  box-shadow: 0 0 0 2px hsl(var(--primary) / 0.2) !important;
  outline: none !important;
}

/* Enhanced mobile experience */
@media (max-width: 768px) {
  /* Ensure minimum touch targets on mobile */
  .btn {
    min-height: 2.75rem;
    min-width: 2.75rem;
    font-size: 0.875rem;
  }

  .badge {
    font-size: 0.75rem;
    padding: 0.375rem 0.75rem;
    min-height: 1.5rem;
  }

  /* Improve card spacing on mobile */
  .card {
    margin-bottom: 1rem;
  }

  .card-body {
    padding: 1rem !important;
  }
}

/* Dark mode optimizations */
@media (prefers-color-scheme: dark) {
  .badge {
    border-width: 1px;
    border-style: solid;
  }

  .card {
    border-color: hsl(var(--b3) / 0.8);
  }

  th {
    background: hsl(var(--b2) / 0.8);
  }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    th {
        background: hsl(var(--b3));
        border-bottom: 3px solid hsl(var(--bc));
        color: hsl(var(--bc));
    }

    td {
        border-bottom: 1px solid hsl(var(--bc) / 0.3);
    }

    .group:hover {
        background: hsl(var(--b3)) !important;
        outline: 2px solid hsl(var(--primary));
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .group:hover,
    .btn:hover,
    .badge:hover,
    .cursor-pointer:hover {
        transform: none;
        transition: none;
    }
}

/* Focus improvements for keyboard navigation */
tr:focus-within {
    outline: 2px solid hsl(var(--primary));
    outline-offset: -2px;
    background: hsl(var(--b2)) !important;
}

/* ===================================================================
   ENHANCED VISUAL HIERARCHY
   =================================================================== */

/* First column (usually ID) styling */
td:first-child {
    font-weight: 600;
    background: hsl(var(--b1));
    border-right: 1px solid hsl(var(--b3) / 0.5);
    position: sticky;
    left: 0;
    z-index: 10;
}

th:first-child {
    position: sticky;
    left: 0;
    z-index: 11;
    border-right: 1px solid hsl(var(--primary) / 0.3);
}

/* Enhanced alternating row colors for better scanning */
tr:nth-child(even) td {
    background: hsl(var(--b1) / 0.5);
}

tr:nth-child(odd) td {
    background: hsl(var(--b1));
}

/* ===================================================================
   LOADING AND EMPTY STATES
   =================================================================== */

/* Loading state styling */
.loading-row {
    background: linear-gradient(90deg,
        hsl(var(--b2)) 25%,
        hsl(var(--b3)) 50%,
        hsl(var(--b2)) 75%
    );
    background-size: 200% 100%;
    animation: loading-shimmer 1.5s infinite;
}

@keyframes loading-shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

/* Empty state styling */
.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    color: hsl(var(--bc) / 0.6);
}

/* ===================================================================
   PRINT STYLES
   =================================================================== */

@media print {
    .table {
        background: white !important;
        box-shadow: none !important;
    }

    th, td {
        background: white !important;
        color: black !important;
        border: 1px solid #ccc !important;
        padding: 0.5rem !important;
        font-size: 0.75rem !important;
    }

    .btn, .badge {
        display: none !important;
    }

    /* Hide interactive elements */
    .cursor-pointer::after {
        display: none !important;
    }
}

.cursor-pointer:hover {
    background-color: rgba(var(--primary-rgb), 0.05);
}

/* Action column styling */
.text-right {
    text-align: right;
}

/* Smooth transitions */
* {
    transition: all 0.2s ease-in-out;
}

/* Focus styles for accessibility */
.btn:focus-visible,
.cursor-pointer:focus-visible {
    outline: 2px solid hsl(var(--primary));
    outline-offset: 2px;
}

/* Loading state overlay */
.loading {
    border-radius: inherit;
}

/* Enhanced card hover in list view */
.cursor-pointer:hover {
    border-color: hsl(var(--primary) / 0.3);
}

/* Sort indicator styles */
.sort-indicator {
    transition: all 0.2s ease-in-out;
    min-width: 16px;
    text-align: center;
    display: inline-block;
}

/* Enhanced badge styling */
.badge {
    font-size: 0.7rem;
    font-weight: 500;
    letter-spacing: 0.025em;
}

/* Improved action button spacing and alignment */
.actions-container {
    min-width: fit-content;
}

/* Better responsive behavior */
@media (max-width: 640px) {
    .table-responsive {
        font-size: 0.875rem;
    }

    .actions-container {
        flex-direction: column;
        gap: 0.25rem;
    }
}

/* Loading spinner customization */
.loading-spinner {
    border-color: hsl(var(--primary));
    border-top-color: transparent;
}

/* Enhanced empty state styling */
.empty-state {
    min-height: 300px;
}

/* Better focus management */
.table th:focus-within,
.table td:focus-within {
    outline: 2px solid hsl(var(--primary));
    outline-offset: -2px;
}

/* ✨ NEW: Enhanced Header Styles */
.mobile-menu-overlay {
    backdrop-filter: blur(4px);
}

.mobile-menu-panel {
    transform: translateX(100%);
    transition: transform 0.3s ease-in-out;
}

.mobile-menu-panel.open {
    transform: translateX(0);
}

/* Enhanced search input styling */
.input-group .input {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
    border-right: none;
}

.input-group .btn {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
    border-left: 1px solid hsl(var(--border-color, var(--fallback-b2)));
}

/* Status indicators responsive layout */
.status-indicators {
    flex-wrap: wrap;
    gap: 0.25rem;
}

@media (max-width: 640px) {
    .status-indicators {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
}

/* Advanced controls panel animation */
.advanced-panel {
    transition: all 0.3s ease-in-out;
    overflow: hidden;
}

.advanced-panel.collapsed {
    max-height: 0;
    padding-top: 0;
    padding-bottom: 0;
}

.advanced-panel.expanded {
    max-height: 200px;
}

/* Mobile filter panel slide animation */
.mobile-filters {
    transition: all 0.3s ease-in-out;
    overflow: hidden;
}

.mobile-filters.hidden {
    max-height: 0;
    padding-top: 0;
    padding-bottom: 0;
}

.mobile-filters.visible {
    max-height: 500px;
}

/* Enhanced button group styling */
.btn-group .btn {
    position: relative;
    z-index: 1;
}

.btn-group .btn:hover {
    z-index: 2;
}

/* Tooltip enhancements for mobile */
@media (max-width: 1024px) {
    .tooltip:before,
    .tooltip:after {
        display: none;
    }
}

/* Header gradient backgrounds */
.header-primary {
    background: linear-gradient(135deg, hsl(var(--base-300)) 0%, hsl(var(--base-200)) 100%);
}

.header-secondary {
    background: linear-gradient(135deg, hsl(var(--base-200)) 0%, hsl(var(--base-100)) 100%);
}

/* Enhanced mobile menu styling */
.mobile-menu {
    box-shadow: -10px 0 25px -5px rgba(0, 0, 0, 0.1), -4px 0 10px -2px rgba(0, 0, 0, 0.05);
}

/* Better scrollbar styling for mobile menu */
.mobile-menu::-webkit-scrollbar {
    width: 4px;
}

.mobile-menu::-webkit-scrollbar-track {
    background: hsl(var(--base-200));
}

.mobile-menu::-webkit-scrollbar-thumb {
    background: hsl(var(--base-content) / 0.2);
    border-radius: 2px;
}

.mobile-menu::-webkit-scrollbar-thumb:hover {
    background: hsl(var(--base-content) / 0.3);
}
</style>
