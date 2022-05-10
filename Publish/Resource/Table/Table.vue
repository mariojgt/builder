<template>
    <div class="w-full mb-12 xl:mb-0">
        <div class="
        relative
        flex flex-col
        min-w-0
        break-words
        bg-neutral-focus
        w-full
        mb-6
        shadow-lg
        rounded
      ">
            <div class="rounded-t mb-0 px-4 py-3 border-0">
                <div class="flex flex-wrap items-center">
                    <div class="relative w-full px-4 max-w-full flex-grow flex-1">
                        <h1 class="text-3xl font-bold">{{ props.tableTitle }}</h1>
                    </div>
                    <div class="relative w-full px-4 max-w-full flex-grow flex-1 text-right">
                        <slot name="new">
                            <create :columns="props.columns" :endpoint="props.endpointCreate" :model="props.model"
                                :permission="props.permission" @onCreate="onCreate" />
                        </slot>
                    </div>
                </div>
            </div>

            <!-- Table filter -->
            <table-filter @onPerPage="onPerPage" @onOrderBy="onOrderBy" @onSearch="onSearch" @onFilter="onFilter"
                @onFilterReset="onFilterReset" :columns="props.columns" />

            <div class="overflow-x-auto p-6">
                <div class="overflow-x-auto">
                    <table class="table table-compact w-full">
                        <thead>
                            <tr>
                                <th v-for="(item, index) in columns" :key="index">
                                    {{ item.label }}
                                </th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(tableItem, tableKey) in tableData" :key="tableItem">
                                <table-display-data :tableData="tableItem" />
                                <th>
                                    <div class="
                      preview
                      border-base-300
                      bg-base-200
                      rounded-b-box rounded-tr-box
                      flex
                      min-h-[6rem] min-w-[18rem]
                      max-w-4xl
                      flex-wrap
                      items-center
                      justify-center
                      overflow-x-hidden
                      border
                      bg-cover bg-top
                      p-4
                      undefined
                      gap-4
                    ">
                                        <delete :id="tableItem.id" :endpoint="props.endpointDelete" :model="props.model"
                                            :permission="props.permission" @onDelete="onDelete" />
                                        <edit :columns="props.columns" :endpoint="props.endpointEdit"
                                            :model="props.model" :modelValue="tableItem" :id="tableItem.id"
                                            :permission="props.permission" @onEdit="onEdit" />
                                    </div>
                                </th>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th v-for="(item, index) in columns" :key="index">
                                    {{ item.label }}
                                </th>
                                <th>Action</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Table Pagination -->
                <table-pagination @onPagiation="onPagiation" :paginationInfo="paginationInfo" />
            </div>
        </div>
    </div>
</template>
<script setup >
// Import axios
import axios from "axios";
// improt flash message
import { useMessage } from "naive-ui";
const message = useMessage();

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
 * Data we goin to display in the table
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
            model: props.model,        // The model name encrypted
            columns: props.columns,      // columns to display
            perPage: perPage,            // per page
            search: search,             // Search
            sort: filterBy,           // Filter example : name
            direction: orderBy,            // Asc or desc
            permission: props.permission,   // Permission
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
            for (const [key, value] of Object.entries(error.response.data.errors)) {
                message.error(value[0]);
            }
        });
};
fetchData();
</script>

