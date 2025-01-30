<template>
    <div class="card bg-base-100 shadow-sm">
      <!-- Mobile Toggle -->
      <div class="md:hidden w-full">
        <button @click="isFilterOpen = !isFilterOpen" class="btn btn-ghost w-full justify-between">
          <span class="flex items-center gap-2">
            <SlidersHorizontal class="w-4 h-4" />
            <span>{{ isFilterOpen ? 'Hide Filters' : 'Show Filters' }}</span>
          </span>
          <ChevronDown class="w-4 h-4" :class="{ 'rotate-180': isFilterOpen }" />
        </button>
      </div>

      <!-- Filter Content -->
      <div
        class="p-4"
        :class="[isFilterOpen ? 'block' : 'hidden md:block']"
      >
        <!-- Filter Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <template v-for="column in filterableColumns" :key="column.key">
            <div class="form-control">
              <label class="label">
                <span class="label-text">{{ column.label }}</span>
              </label>

              <!-- Model Search Filter -->
              <select
                v-if="column.type === 'model_search'"
                v-model="filters[column.key]"
                class="select select-bordered w-full"
                @change="handleFilterChange"
              >
                <option value="">All</option>
                <option
                  v-for="option in modelOptions[column.key] || []"
                  :key="option.id"
                  :value="option.id"
                >
                  {{ option[column.displayKey] }}
                </option>
              </select>

              <!-- Boolean Filter -->
              <select
                v-else-if="column.type === 'boolean'"
                v-model="filters[column.key]"
                class="select select-bordered w-full"
                @change="handleFilterChange"
              >
                <option value="">All</option>
                <option value="true">Yes</option>
                <option value="false">No</option>
              </select>

              <!-- Select Filter -->
              <select
                v-else-if="column.type === 'select' && column.options"
                v-model="filters[column.key]"
                class="select select-bordered w-full"
                @change="handleFilterChange"
              >
                <option value="">All</option>
                <option
                  v-for="option in column.options.select_options"
                  :key="option.value"
                  :value="option.value"
                >
                  {{ option.label }}
                </option>
              </select>

              <!-- Date Filter -->
              <div v-else-if="column.type === 'date'" class="flex gap-2">
                <input
                  type="date"
                  v-model="filters[column.key].from"
                  class="input input-bordered flex-1"
                  @change="handleFilterChange"
                />
                <input
                  type="date"
                  v-model="filters[column.key].to"
                  class="input input-bordered flex-1"
                  @change="handleFilterChange"
                />
              </div>

              <!-- Default Text Filter -->
              <input
                v-else
                type="text"
                v-model="filters[column.key]"
                class="input input-bordered w-full"
                @input="handleFilterChange"
                :placeholder="`Filter by ${column.label}`"
              />
            </div>
          </template>
        </div>

        <!-- Active Filters -->
        <div v-if="hasActiveFilters" class="flex flex-wrap gap-2 mt-4">
          <div
            v-for="(value, key) in activeFilters"
            :key="key"
            class="badge badge-primary gap-2 p-3"
          >
            <span class="text-xs opacity-70">{{ getColumnLabel(key) }}:</span>
            <span>{{ formatFilterValue(key, value) }}</span>
            <button @click="clearFilter(key)" class="hover:text-error">
              <X class="w-3 h-3" />
            </button>
          </div>
        </div>

        <!-- Filter Actions -->
        <div class="flex justify-end gap-2 mt-4">
          <button
            v-if="hasActiveFilters"
            @click="resetFilters"
            class="btn btn-ghost gap-2"
          >
            <RotateCcw class="w-4 h-4" />
            Reset Filters
          </button>
          <button
            @click="applyFilters"
            class="btn btn-primary gap-2"
          >
            <Filter class="w-4 h-4" />
            Apply Filters
          </button>
        </div>
      </div>
    </div>
  </template>

  <script setup>
  import { ref, computed } from 'vue';
  import axios from 'axios';
  import {
    SlidersHorizontal,
    ChevronDown,
    Filter,
    RotateCcw,
    X
  } from 'lucide-vue-next';

  const props = defineProps({
    columns: {
      type: Array,
      required: true
    }
  });

  const emit = defineEmits(['onFilterChange']);

  // Initialize all state at once
  const isFilterOpen = ref(false);
  const modelOptions = ref({});
  const filters = ref(
    props.columns.reduce((acc, column) => {
      acc[column.key] = column.type === 'date' ? { from: '', to: '' } : '';
      return acc;
    }, {})
  );

  // Computed properties
  const filterableColumns = computed(() => {
    return props.columns.filter(column => column.filterable !== false);
  });

  const activeFilters = computed(() => {
    return Object.entries(filters.value).reduce((acc, [key, value]) => {
      if (value) {
        if (typeof value === 'string' && value.trim()) {
          acc[key] = value;
        } else if (typeof value === 'object') {
          if (value.from || value.to) {
            acc[key] = value;
          }
        }
      }
      return acc;
    }, {});
  });

  const hasActiveFilters = computed(() => {
    return Object.keys(activeFilters.value).length > 0;
  });

  // Methods
  const loadModelOptions = async (column) => {
    try {
      const response = await axios.post(column.endpoint, {
        model: column.model,
        columns: column.columns,
      });
      modelOptions.value[column.key] = response.data.data;
    } catch (error) {
      console.error('Error loading model options:', error);
    }
  };

  const getColumnLabel = (key) => {
    const column = props.columns.find(col => col.key === key);
    return column ? column.label : key;
  };

  const formatFilterValue = (key, value) => {
    const column = props.columns.find(col => col.key === key);
    if (!column) return value;

    switch (column.type) {
      case 'boolean':
        return value === 'true' ? 'Yes' : 'No';
      case 'date':
        return `${value.from || ''} - ${value.to || ''}`;
      case 'select':
        const option = column.options?.select_options?.find(opt => opt.value === value);
        return option ? option.label : value;
      default:
        return value;
    }
  };

  const handleFilterChange = () => {
    emit('onFilterChange', activeFilters.value);
  };

  const clearFilter = (key) => {
    filters.value[key] = props.columns.find(col => col.key === key)?.type === 'date'
      ? { from: '', to: '' }
      : '';
    handleFilterChange();
  };

  const resetFilters = () => {
    // Reset to initial state
    filters.value = props.columns.reduce((acc, column) => {
      acc[column.key] = column.type === 'date' ? { from: '', to: '' } : '';
      return acc;
    }, {});
    handleFilterChange();
  };

  const applyFilters = () => {
    handleFilterChange();
  };

  // Load model options on mount
  if (props.columns) {
    props.columns.forEach(column => {
      if (column.type === 'model_search' && column.filterable) {
        loadModelOptions(column);
      }
    });
  }
  </script>
