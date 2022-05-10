<template>
    <div class="my-2 flex sm:flex-row flex-col p-6">
        <div class="flex flex-row mb-1 sm:mb-0">
            <div class="relative pl-4">
                <select class="select select-primary w-full max-w-xs" v-model="perPage">
                    <option disabled selected>Per page</option>
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
            <div class="relative pl-4">
                <select class="select select-primary w-full max-w-xs" v-model="filterBy" @change="filterChange">
                    <option disabled selected>Row</option>
                    <option v-for="(item, index) in filterColumns" :key="index" :value="item.value">
                        {{ item.label }}
                    </option>
                </select>
            </div>
            <div class="relative pl-4">
                <select class="select select-primary w-full max-w-xs" v-model="orderBy">
                    <option disabled selected>Order by</option>
                    <option value="asc">Asc</option>
                    <option value="desc">Desc</option>
                </select>
            </div>
        </div>
        <div class="block relative pl-4">
            <div class="form-control">
                <div class="input-group">
                    <input type="text" placeholder="Search…" class="input input-bordered" v-model="search" />
                    <button class="btn btn-square btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <div class="block relative pl-4">
            <button class="btn btn-outline btn-accent" @click="resetFilter">
                Reset
            </button>
        </div>
    </div>
</template>
<script setup >
// Import vue watch
import { watch } from "vue";

/**
 * Filter props
 */
const props = defineProps({
    columns: {
        type: Array,
        default: () => [],
    },
});

/**
 * Event that we goin to emit in order to update the table
 */
const emit = defineEmits([
    "onPerPage",
    "onOrderBy",
    "onSearch",
    "onFilter",
    "onFilterReset",
]);

let perPage = $ref(10);
let filterBy = $ref("id");
let orderBy = $ref("asc");
let search = $ref("");

/**
 * Peprate the filter section
 */
let filterColumns = $ref([]);
const builderTableFilter = async () => {
    for (const [key, value] of Object.entries(props.columns)) {
        if (value.sortable) {
            if (key == 0) {
                filterBy = value.key;
            }
            filterColumns.push({
                label: value.label,
                value: value.key,
            });
        }
    }
};
builderTableFilter();

/**
 * Watch the perPage value
 */
watch(
    () => perPage,
    (value) => {
        emit("onPerPage", value);
    }
);

/**
 * Watch the orderBy value
 */
watch(
    () => orderBy,
    (value) => {
        emit("onOrderBy", value);
    }
);

/**
 * Watch the filter by value
 */
watch(
    () => filterBy,
    (value) => {
        emit("onFilter", value);
    }
);

/**
 * Watch the search value
 */
let searchDebounce = $ref(null);

watch(
    () => search,
    (value) => {
        // Clear any existing searchDebounce event
        clearTimeout(searchDebounce);
        // Update and log the counts after 500 miliseconds
        searchDebounce = setTimeout(function () {
            emit("onSearch", value);
        }, 500);
    }
);

/**
 * Clear the filter and reset the table back to normal
 */
const resetFilter = async () => {
    perPage = 10;
    filterBy = "id";
    orderBy = "asc";
    search = null;
    emit("onFilterReset", [
        {
            perPage: perPage,
            filterBy: filterBy,
            orderBy: orderBy,
            search: search,
        },
    ]);
};
</script>


