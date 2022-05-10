<template>
    <th v-for="(item, index) in tableData" :key="index">
        {{ item }}
    </th>
</template>
<script setup >
// Import vue watch
import { watch } from "vue";

const props = defineProps({
    tableData: {
        type: Array,
        default: () => [],
    },
});
</script>


