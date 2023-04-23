<template>
    <th v-for="(item, index) in tableDisplayInformation" :key="index">
        <div v-if="item.type === 'media'">
            <div class="carousel rounded-box w-96">
                <div class="carousel-item w-1/2" v-for="(item, index) in item.value">
                    <img :src="item.url.default" class="w-full" />
                </div>
            </div>
        </div>
        <div v-else>
            <div v-html="item.value" v-if="item.type != 'icon'" ></div>
            <div v-html="item.value" class="bg-white flex justify-center p-10" v-else ></div>
        </div>
    </th>
</template>
<script setup >
// Import vue watch
import { watch, computed } from "vue";

const props = defineProps({
    tableData: {
        type: Array,
        default: () => [],
    },
    columns: {
        type: Array,
        default: () => [],
    },
});

// Create data computed property
const tableDisplayInformation = computed(() => {
    let displayData = [];
    for (const [key, value] of Object.entries(props.columns)) {
        displayData.push({
            label: value.label,
            type: value.type,
            value: props.tableData[value.key],
        });
    }

    return displayData;
});
</script>


