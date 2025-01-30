<template>
    <template v-if="viewType === 'table'">
      <td
        v-for="(column, index) in columns"
        :key="index"
        class="p-4 transition-colors duration-200"
      >
        <FieldRenderer
          :value="tableData[column.key]"
          :type="column.type || 'text'"
          :options="column.options || {}"
        />
      </td>
    </template>
    <template v-else>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 p-4">
        <div
          v-for="(column, index) in visibleColumns"
          :key="index"
          class="flex flex-col space-y-2 bg-base-200/50 rounded-lg p-3 hover:bg-base-200 transition-colors duration-200"
        >
          <!-- Field Label -->
          <span class="text-sm font-medium text-base-content/70 flex items-center gap-2">
            <component
              :is="getColumnIcon(column)"
              v-if="getColumnIcon(column)"
              class="w-4 h-4 text-primary"
            />
            {{ column.label }}
          </span>

          <!-- Field Value with Enhanced Visibility -->
          <div class="flex-1">
            <FieldRenderer
              :value="tableData[column.key]"
              :type="column.type || 'text'"
              :options="{
                ...column.options,
                truncate: false,
                enhanced: true
              }"
            />
          </div>
        </div>

        <!-- Show More/Less Toggle -->
        <div v-if="hasHiddenColumns" class="col-span-full">
          <button
            @click="toggleShowAll"
            class="btn btn-ghost btn-sm gap-2 text-primary"
          >
            <component :is="showAll ? ChevronUp : ChevronDown" class="w-4 h-4" />
            {{ showAll ? 'Show Less' : `Show ${columns.length - visibleColumns.length} More Fields` }}
          </button>
        </div>
      </div>
    </template>
  </template>

  <script setup lang="ts">
  import { ref, computed } from 'vue';
  import FieldRenderer from './FieldRenderer.vue';
  import {
    Calendar,
    Hash,
    Type,
    Image as ImageIcon,
    Check,
    DollarSign,
    Star,
    Tag,
    ChevronDown,
    ChevronUp,
    Info
  } from 'lucide-vue-next';

  interface Column {
    key: string;
    label: string;
    type?: string;
    options?: Record<string, any>;
    priority?: number;
  }

  interface Props {
    tableData: Record<string, any>;
    columns: Column[];
    viewType?: 'table' | 'list';
    initialVisibleCount?: number;
  }

  const props = withDefaults(defineProps<Props>(), {
    tableData: () => ({}),
    columns: () => [],
    viewType: 'table',
    initialVisibleCount: 6
  });

  // State for show more/less functionality
  const showAll = ref(false);

  // Computed properties
  const sortedColumns = computed(() => {
    return [...props.columns].sort((a, b) => {
      const priorityA = a.priority ?? 999;
      const priorityB = b.priority ?? 999;
      return priorityA - priorityB;
    });
  });

  const visibleColumns = computed(() => {
    if (showAll.value) return sortedColumns.value;
    return sortedColumns.value.slice(0, props.initialVisibleCount);
  });

  const hasHiddenColumns = computed(() => {
    return props.columns.length > props.initialVisibleCount;
  });

  // Helper function to get appropriate icon for column type
  function getColumnIcon(column: Column) {
    const iconMap: Record<string, any> = {
      date: Calendar,
      timestamp: Calendar,
      number: Hash,
      text: Type,
      media: ImageIcon,
      boolean: Check,
      price: DollarSign,
      rating: Star,
      model_search: Tag,
      default: Info
    };

    return iconMap[column.type || 'default'];
  }

  // Methods
  function toggleShowAll() {
    showAll.value = !showAll.value;
  }
  </script>
