<template>
    <div class="w-full">
        <div
            class="relative flex flex-col min-w-0 break-words bg-base-300 w-full mb-6 shadow-lg rounded"
        >
            <div class="flex flex-wrap items-center mt-5">
                <div class="relative w-full px-4 max-w-full flex-grow flex-1">
                    <h1 class="text-3xl font-extrabold text-base-content">
                        {{ props.tableTitle }}
                    </h1>
                </div>
                <div
                    class="relative w-full px-4 max-w-full flex-grow flex-1 text-right"
                >
                    <slot name="new">
                        <create
                            :columns="props.columns"
                            :endpoint="props.endpointCreate"
                            :model="props.model"
                            :permission="props.permission"
                            @onCreate="onCreate"
                        />
                    </slot>
                </div>
            </div>

            <!-- Table filter -->
            <table-filter
                @onPerPage="onPerPage"
                @onOrderBy="onOrderBy"
                @onSearch="onSearch"
                @onFilter="onFilter"
                @onFilterReset="onFilterReset"
                :columns="props.columns"
            />

            <div class="overflow-x-auto p-6">
                <div class="overflow-x-auto bg-base-100">
                    <table class="table table-compact w-full">
                        <thead class="font-bold bg-primary text-neutral">
                            <tr>
                                <th
                                    v-for="(item, index) in columns"
                                    :key="index"
                                >
                                    {{ item.label }}
                                </th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(tableItem, tableKey) in tableData"
                                :key="tableItem"
                                class="font-thin bg-base-300 hover:bg-secondary hover:text-neutral hover:font-bold"
                            >
                                <table-display-data
                                    :tableData="tableItem"
                                    :columns="columns"
                                />
                                <th>
                                    <div
                                        class="flex justify-start overflow-x-hidden gap-2"
                                    >
                                        <edit
                                            :columns="props.columns"
                                            :endpoint="props.endpointEdit"
                                            :model="props.model"
                                            :modelValue="tableItem"
                                            :id="tableItem.id"
                                            :permission="props.permission"
                                            @onEdit="onEdit"
                                            v-if="!custom_edit_route"
                                        />
                                        <div v-else>
                                            <Link
                                                :href="
                                                    custom_edit_route +
                                                    tableItem.id
                                                "
                                            >
                                                <label
                                                    :for="
                                                        'edit-data-' +
                                                        tableItem.id
                                                    "
                                                    class="btn btn-info modal-button"
                                                >
                                                    <svg
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        class="h-6 w-6"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="2"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                                        />
                                                    </svg>
                                                </label>
                                            </Link>
                                        </div>
                                        <delete
                                            :id="tableItem.id"
                                            :endpoint="props.endpointDelete"
                                            :model="props.model"
                                            :permission="props.permission"
                                            @onDelete="onDelete"
                                        />
                                    </div>
                                </th>
                            </tr>
                        </tbody>
                        <tfoot class="font-bold bg-primary text-neutral">
                            <tr>
                                <th
                                    v-for="(item, index) in columns"
                                    :key="index"
                                >
                                    {{ item.label }}
                                </th>
                                <th>Action</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Table Pagination -->
                <table-pagination
                    @onPagiation="onPagiation"
                    :paginationInfo="paginationInfo"
                />
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
// Import axios
import axios from "axios";
// improt flash message
import { useMessage } from "naive-ui";
const message = useMessage();

import { Link } from "@inertiajs/vue3";
// Import the delete component
import Delete from "./components/crud/delete.vue";
// Import the create component
import Create from "./components/crud/create.vue";
// Import the edit component
import Edit from "./components/crud/edit.vue";
// Include filter
import TableFilter from "./components/filter/filter.vue";
import TablePagination from "./components/filter/pagination.vue";
// Import the table display component
import TableDisplayData from "./components/tableDataDisplay.vue";
/**
 * Props required in order to the table to work properly
 */
const props = defineProps({
    tableTitle: {
        type: String,
        default: "Table name",
    },
    columns: {
        type: Array,
        default: () => [],
    },
    model: {
        type: String,
        default: "",
    },
    endpoint: {
        type: String,
        default: "",
    },
    endpointDelete: {
        type: String,
        default: "",
    },
    endpointCreate: {
        type: String,
        default: "",
    },
    endpointEdit: {
        type: String,
        default: "",
    },
    permission: {
        type: String,
        default: null,
    },
    custom_edit_route: {
        type: String,
        default: null,
    },
});

/**
 * ON EVENTS METHODS BEING ⚡⚡⚡⚡⚡
 */

/**
 * When the user clicks on the pagination button
 */
const onPagiation = async (paginationLink) => {
    fetchData(paginationLink);
};

/**
 * When the user cahgne per page
 */
let perPage = $ref(10);
const onPerPage = async (onPerPage) => {
    perPage = onPerPage;
    fetchData();
};

/**
 * On sort field
 */
let filterBy = $ref("id");
const onFilter = async (onFilter) => {
    filterBy = onFilter;
    fetchData();
};

/**
 * When the user change order by
 */
let orderBy = $ref(null);
const onOrderBy = async (onOrderBy) => {
    orderBy = onOrderBy;
    fetchData();
};

/**
 * Search field
 */
let search = $ref(null);

/**
 * On user search
 */
const onSearch = async (onSearch) => {
    // check if length is greater than 3
    if (onSearch && onSearch.length > 3) {
        search = onSearch;
        fetchData();
    }
};

/**
 * On delete we reload the page
 */
const onDelete = async () => {
    fetchData();
};

/**
 * On create new
 */
const onCreate = async () => {
    fetchData();
};

/**
 * On edit new
 */
const onEdit = async () => {
    fetchData();
};

/**
 * On filters get reset
 */
const onFilterReset = async (data) => {
    perPage = data.perPage;
    filterBy = data.filterBy;
    orderBy = data.orderBy;
    search = data.search;
    fetchData();
};

/**
 * ON EVENTS METHODS END ⚡⚡⚡⚡⚡
 */

/**
 * Data we goin to display in the table as a object
 */
let tableData = $ref([]);

/**
 * Current page
 */
let paginationInfo = $ref([]);

/**
 * This fuction will return the data from the endpoint with the filters and etc
 */
const fetchData = async (newEndPoint = null) => {
    // If the endpoint is not defined, we use the default endpoint
    if (newEndPoint === null) {
        newEndPoint = props.endpoint;
    }

    axios
        .post(newEndPoint, {
            model: props.model, // The model name encrypted
            columns: props.columns, // columns to display
            perPage: perPage, // per page
            search: search, // Search
            sort: filterBy, // Filter example : name
            direction: orderBy, // Asc or desc
            permission: props.permission, // Permission
        })
        .then(function (response) {
            tableData = response.data.data;

            paginationInfo = {
                currentPage: response.data.current_page,
                lastPage: response.data.last_page,
                perPage: response.data.per_page,
                total: response.data.total,
                links: response.data.links,
            };
        })
        .catch(function (error) {
            for (const [key, value] of Object.entries(
                error.response.data.errors
            )) {
                message.error(value[0]);
            }
        });
};
fetchData();
</script>
