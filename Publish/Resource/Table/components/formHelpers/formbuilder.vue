<template>
    <div>
      <!-- Tabs Navigation if tabs exist -->
      <div v-if="hasTabs" class="mb-4">
        <div class="tabs tabs-boxed bg-base-300 p-1">
          <button
            v-for="tab in tabList"
            :key="tab"
            class="tab flex-1 transition-all duration-200"
            :class="{ 'tab-active': currentTab === tab }"
            @click="currentTab = tab"
          >
            {{ tab }}
          </button>
        </div>

        <!-- Tab Content -->
        <div class="mt-4">
          <div v-for="tab in tabList" :key="tab" v-show="currentTab === tab">
            <FormFields
              :fields="getFieldsByTab(tab)"
              @update:fields="handleFieldsUpdate"
            />
          </div>
        </div>
      </div>

      <!-- No tabs - show all fields -->
      <div v-else>
        <FormFields
          :fields="avaliableFields"
          @update:fields="handleFieldsUpdate"
        />
      </div>
    </div>
  </template>

  <script setup>
import { ref, computed, watch, onMounted } from "vue";
import FormFields from './FormFields.vue';
import {
  formatDate,
  formatTimestamp,
  makeString
} from "./formHelper.js";

const props = defineProps({
  columns: {
    type: Array,
    default: () => [],
  },
  modelValue: {
    type: Object,
    default: () => ({}),
  },
  editMode: {
    type: String,
    default: "false",
  },
  errors: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(["onFormUpdate"]);

// Make avaliableFields reactive using ref instead of $ref
const avaliableFields = ref([]);
const currentTab = ref('');

// Computed properties for tabs
const hasTabs = computed(() => {
  return avaliableFields.value.some(field => field.tab);
});

const tabList = computed(() => {
  const tabs = [...new Set(avaliableFields.value.filter(field => field.tab).map(field => field.tab))];
  if (tabs.length > 0 && !currentTab.value) {
    currentTab.value = tabs[0];
  }
  return tabs;
});

// Get fields for a specific tab
const getFieldsByTab = (tab) => {
  return avaliableFields.value.filter(field => field.tab === tab);
};

// Handle fields updates from child component
const handleFieldsUpdate = (updatedFields) => {
  updatedFields.forEach(updatedField => {
    const index = avaliableFields.value.findIndex(field => field.key === updatedField.key);
    if (index !== -1) {
      avaliableFields.value[index] = updatedField;
    }
  });

  emit("onFormUpdate", avaliableFields.value);
};

// Field creation function
const createFields = () => {
  const fields = [];

  props.columns.forEach(value => {
    const isCreateMode = props.editMode === "false";
    const isEditMode = props.editMode === "true";

    const shouldInclude = (isCreateMode && value.canCreate) ||
                         (isEditMode && value.canEdit);

    if (shouldInclude) {
      let fieldValue = "";

      if (isCreateMode) {
        if (value.type === "Toggle" || value.type === "boolean") {
          fieldValue = false;
        }
      } else if (isEditMode) {
        fieldValue = determineFieldValue(value, props.modelValue);
      }

      const field = {
        ...value,
        value: fieldValue,
        select_options: value?.options?.options || value?.options,
        error: props.errors[value.key.toLowerCase()] || null
      };

      fields.push(field);
    }
  });

  avaliableFields.value = fields;

  if (props.editMode === "true") {
    emit("onFormUpdate", avaliableFields.value);
  }
};

// Watch for error changes
watch(
  () => props.errors,
  (newErrors) => {
    if (Object.keys(newErrors).length > 0) {
      const updatedFields = avaliableFields.value.map(field => ({
        ...field,
        error: newErrors[field.key.toLowerCase()] || null
      }));
      avaliableFields.value = updatedFields;
      emit("onFormUpdate", avaliableFields.value);
    }
  },
  { deep: true, immediate: true }
);

// Determine field value for edit mode
const determineFieldValue = (value, modelValue) => {
  switch (value.type) {
    case "date":
      return formatDate(modelValue[value.key]);
    case "timestamp":
      return formatTimestamp(modelValue[value.key]);
    case "media":
    case "model_search":
    case "pivot_model":
    case "boolean":
    case "chips":
      return modelValue[value.key];
    default:
      return makeString(modelValue[value.key]);
  }
};

// Initialize fields on mount
onMounted(createFields);
  </script>

  <style scoped>
  .tabs-boxed .tab-active {
    @apply bg-primary text-white;
  }

  .tab {
    @apply font-medium;
  }

  .tab:not(.tab-active):hover {
    @apply bg-base-100/[0.12];
  }
  </style>
