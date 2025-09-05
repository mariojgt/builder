<template>
  <div class="form-builder">
    <!-- Tab Navigation (Simple) -->
    <div v-if="hasTabs" class="mb-6">
      <div class="tabs tabs-boxed bg-base-200 p-1">
        <button
          v-for="(tab, index) in tabList"
          :key="tab"
          @click="setCurrentTab(tab, index)"
          class="tab transition-all duration-200"
          :class="[
            currentTab === tab ? 'tab-active' : 'hover:tab-active'
          ]"
        >
          {{ tab }}
          <div class="badge badge-sm ml-2" :class="[
            currentTab === tab ? 'badge-primary' : 'badge-neutral'
          ]">
            {{ getTabFieldCount(tab) }}
          </div>
        </button>
      </div>

      <!-- Simple Progress Bar -->
      <div v-if="hasRequiredFields" class="mt-4">
        <div class="flex items-center justify-between text-sm text-base-content/60 mb-2">
          <span>Progress: {{ completedFieldsCount }}/{{ requiredFieldsCount }} required fields</span>
          <span class="font-medium">{{ Math.round(overallProgress) }}%</span>
        </div>
        <progress
          class="progress progress-primary w-full h-2"
          :value="completedFieldsCount"
          :max="requiredFieldsCount"
        ></progress>
      </div>
    </div>

    <!-- Form Content -->
    <div class="space-y-6">
      <!-- Tab Content -->
      <div v-if="hasTabs">
        <div
          v-for="(tab, index) in tabList"
          :key="tab"
          v-show="currentTab === tab"
          class="space-y-4"
        >
          <FormFields
            :fields="getFieldsByTab(tab)"
            @update:fields="handleFieldsUpdate"
          />

          <!-- Simple Tab Navigation -->
          <div v-if="tabList.length > 1" class="flex justify-between pt-4 border-t border-base-200">
            <button
              v-if="index > 0"
              @click="navigateToPreviousTab"
              class="btn btn-ghost btn-sm"
            >
              ← Previous
            </button>
            <div v-else></div>

            <button
              v-if="index < tabList.length - 1"
              @click="navigateToNextTab"
              class="btn btn-primary btn-sm"
            >
              Next →
            </button>
            <div v-else></div>
          </div>
        </div>
      </div>

      <!-- No Tabs Content -->
      <div v-else>
        <FormFields
          :fields="avaliableFields"
          @update:fields="handleFieldsUpdate"
        />
      </div>
    </div>

    <!-- Error Summary (Simple) -->
    <div v-if="hasValidationErrors" class="mt-6">
      <div class="alert alert-warning">
        <AlertTriangle class="w-5 h-5" />
        <span>{{ validationErrorsCount }} field(s) need attention</span>
        <button @click="scrollToFirstError" class="btn btn-sm btn-warning">
          Fix Errors
        </button>
      </div>
    </div>

    <!-- Success State (Simple) -->
    <div v-if="isFormValid && hasRequiredFields" class="mt-6">
      <div class="alert alert-success">
        <CheckCircle class="w-5 h-5" />
        <span>All required fields completed!</span>
      </div>
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
import {
  AlertTriangle,
  CheckCircle
} from 'lucide-vue-next';

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

// Reactive state
const avaliableFields = ref([]);
const currentTab = ref('');
const currentTabIndex = ref(0);

// Computed properties
const hasTabs = computed(() => {
  return avaliableFields.value.some(field => field.tab);
});

const tabList = computed(() => {
  const tabs = [...new Set(avaliableFields.value.filter(field => field.tab).map(field => field.tab))];
  if (tabs.length > 0 && !currentTab.value) {
    currentTab.value = tabs[0];
    currentTabIndex.value = 0;
  }
  return tabs;
});

const requiredFieldsCount = computed(() => {
  return avaliableFields.value.filter(field => field.required).length;
});

const completedFieldsCount = computed(() => {
  return avaliableFields.value.filter(field =>
    field.required && field.value && field.value !== ''
  ).length;
});

const hasRequiredFields = computed(() => {
  return requiredFieldsCount.value > 0;
});

const overallProgress = computed(() => {
  if (requiredFieldsCount.value === 0) return 100;
  return (completedFieldsCount.value / requiredFieldsCount.value) * 100;
});

const hasValidationErrors = computed(() => {
  return Object.keys(props.errors).length > 0;
});

const validationErrorsCount = computed(() => {
  return Object.keys(props.errors).length;
});

const isFormValid = computed(() => {
  const requiredFields = avaliableFields.value.filter(field => field.required);
  return requiredFields.every(field => field.value && field.value !== '') && !hasValidationErrors.value;
});

// Tab methods
const getFieldsByTab = (tab) => {
  return avaliableFields.value.filter(field => field.tab === tab);
};

const getTabFieldCount = (tab) => {
  return getFieldsByTab(tab).length;
};

const setCurrentTab = (tab, index) => {
  currentTab.value = tab;
  currentTabIndex.value = index;
};

const navigateToNextTab = () => {
  const nextIndex = currentTabIndex.value + 1;
  if (nextIndex < tabList.value.length) {
    setCurrentTab(tabList.value[nextIndex], nextIndex);
  }
};

const navigateToPreviousTab = () => {
  const prevIndex = currentTabIndex.value - 1;
  if (prevIndex >= 0) {
    setCurrentTab(tabList.value[prevIndex], prevIndex);
  }
};

// Handle field updates
const handleFieldsUpdate = (updatedFields) => {
  updatedFields.forEach(updatedField => {
    const index = avaliableFields.value.findIndex(field => field.key === updatedField.key);
    if (index !== -1) {
      avaliableFields.value[index] = updatedField;
    }
  });

  emit("onFormUpdate", avaliableFields.value);
};

// Error handling
const scrollToFirstError = () => {
  const firstErrorKey = Object.keys(props.errors)[0];
  if (firstErrorKey) {
    const element = document.querySelector(`[data-field-key="${firstErrorKey}"]`);
    if (element) {
      // Find the tab that contains this field
      const field = avaliableFields.value.find(f => f.key === firstErrorKey);
      if (field && field.tab && field.tab !== currentTab.value) {
        const tabIndex = tabList.value.indexOf(field.tab);
        if (tabIndex !== -1) {
          setCurrentTab(field.tab, tabIndex);
        }
      }

      setTimeout(() => {
        element.scrollIntoView({
          behavior: 'smooth',
          block: 'center'
        });
      }, 300);
    }
  }
};

// Field creation
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
.form-builder {
  max-width: 100%;
}

.tab {
  transition: all 0.2s ease;
}

.progress {
  transition: all 0.3s ease;
}

.alert {
  border-radius: 0.5rem;
}

@media (max-width: 640px) {
  .tabs-boxed {
    flex-wrap: wrap;
  }

  .tab {
    font-size: 0.875rem;
  }
}
</style>
