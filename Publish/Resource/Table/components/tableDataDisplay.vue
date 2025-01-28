<template>
    <!-- Table Row Data -->
    <td v-for="(column, index) in columns" :key="index" class="p-4">
      <!-- Field Renderer Component -->
      <FieldRenderer
        :value="tableData[column.key]"
        :type="column.type || 'text'"
        :options="column.options || {}"
      />
    </td>
  </template>

  <script setup>
  import { computed } from 'vue';
  import FieldRenderer from './FieldRenderer.vue';

  // Props definition
  const props = defineProps({
    tableData: {
      type: Object,
      required: true,
      default: () => ({})
    },
    columns: {
      type: Array,
      required: true,
      default: () => []
    }
  });
  </script>
