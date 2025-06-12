<template>
  <div class="form-builder-container">
    <!-- Enhanced Tabs Navigation -->
    <div v-if="hasTabs" class="mb-8">
      <!-- Tabs Header -->
      <div class="bg-gradient-to-r from-base-200 via-base-100 to-base-200 rounded-t-xl p-6 border-b border-base-200">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary/20 to-secondary/20 flex items-center justify-center">
              <Layers class="w-5 h-5 text-primary" />
            </div>
            <div>
              <h3 class="text-lg font-bold text-base-content">Form Sections</h3>
              <p class="text-sm text-base-content/60">Organize your form fields into logical groups</p>
            </div>
          </div>

          <!-- Tab Statistics -->
          <div class="stats stats-horizontal shadow bg-base-200/50">
            <div class="stat py-2 px-4">
              <div class="stat-title text-xs">Sections</div>
              <div class="stat-value text-lg text-primary">{{ tabList.length }}</div>
            </div>
            <div class="stat py-2 px-4">
              <div class="stat-title text-xs">Current</div>
              <div class="stat-value text-lg text-secondary">{{ currentTabIndex + 1 }}</div>
            </div>
          </div>
        </div>

        <!-- Enhanced Tab Navigation -->
        <div class="relative">
          <!-- Tab Buttons -->
          <div class="flex gap-2 overflow-x-auto custom-scrollbar pb-2">
            <TransitionGroup
              enter-active-class="transition-all duration-300 ease-out"
              enter-from-class="opacity-0 scale-95"
              enter-to-class="opacity-100 scale-100"
              leave-active-class="transition-all duration-200 ease-in"
              leave-from-class="opacity-100 scale-100"
              leave-to-class="opacity-0 scale-95"
            >
              <button
                v-for="(tab, index) in tabList"
                :key="tab"
                @click="setCurrentTab(tab, index)"
                class="btn btn-sm relative overflow-hidden group transition-all duration-300 min-w-max"
                :class="[
                  currentTab === tab
                    ? 'btn-primary shadow-lg scale-105'
                    : 'btn-ghost hover:btn-primary hover:scale-105'
                ]"
              >
                <!-- Background Animation -->
                <div
                  v-if="currentTab === tab"
                  class="absolute inset-0 bg-gradient-to-r from-primary via-secondary to-primary animate-gradient-x"
                ></div>

                <!-- Tab Content -->
                <div class="flex items-center gap-2 relative z-10">
                  <component :is="getTabIcon(index)" class="w-4 h-4" />
                  <span class="font-medium">{{ tab }}</span>
                  <div class="badge badge-xs" :class="[
                    currentTab === tab ? 'badge-primary-content' : 'badge-neutral'
                  ]">
                    {{ getTabFieldCount(tab) }}
                  </div>
                </div>

                <!-- Hover Shimmer -->
                <div
                  v-if="currentTab !== tab"
                  class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/20 to-transparent group-hover:translate-x-full transition-transform duration-500 ease-out"
                ></div>
              </button>
            </TransitionGroup>
          </div>

          <!-- Progress Indicator -->
          <div class="mt-4">
            <div class="flex items-center gap-2 text-sm text-base-content/60 mb-2">
              <span>Form Progress:</span>
              <span class="font-semibold">{{ completedTabsCount }}/{{ tabList.length }} sections completed</span>
            </div>
            <div class="h-2 bg-base-300 rounded-full overflow-hidden">
              <div
                class="h-full bg-gradient-to-r from-success via-info to-success transition-all duration-500"
                :style="{ width: `${(completedTabsCount / tabList.length) * 100}%` }"
              ></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Enhanced Tab Content -->
      <div class="bg-base-100 rounded-b-xl shadow-lg border border-base-200 border-t-0">
        <TransitionGroup
          enter-active-class="transition-all duration-500 ease-out"
          enter-from-class="opacity-0 translate-x-8"
          enter-to-class="opacity-100 translate-x-0"
          leave-active-class="transition-all duration-300 ease-in"
          leave-from-class="opacity-100 translate-x-0"
          leave-to-class="opacity-0 -translate-x-8"
          mode="out-in"
        >
          <div
            v-for="(tab, index) in tabList"
            :key="tab"
            v-show="currentTab === tab"
            class="tab-content"
          >
            <!-- Tab Header with Navigation -->
            <div class="bg-gradient-to-r from-primary/5 via-secondary/5 to-primary/5 px-6 py-4 border-b border-base-200">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-primary/20 to-secondary/20 flex items-center justify-center">
                    <component :is="getTabIcon(index)" class="w-4 h-4 text-primary" />
                  </div>
                  <div>
                    <h4 class="text-lg font-semibold text-base-content">{{ tab }}</h4>
                    <p class="text-sm text-base-content/60">
                      {{ getTabFieldCount(tab) }} field{{ getTabFieldCount(tab) !== 1 ? 's' : '' }} in this section
                    </p>
                  </div>
                </div>

                <!-- Tab Navigation Controls -->
                <div class="flex items-center gap-2">
                  <button
                    @click="navigateToPreviousTab"
                    :disabled="index === 0"
                    class="btn btn-sm btn-ghost gap-2"
                    :class="{ 'btn-disabled': index === 0 }"
                  >
                    <ChevronLeft class="w-4 h-4" />
                    <span class="hidden sm:inline">Previous</span>
                  </button>

                  <div class="badge badge-primary badge-sm">
                    {{ index + 1 }} / {{ tabList.length }}
                  </div>

                  <button
                    @click="navigateToNextTab"
                    :disabled="index === tabList.length - 1"
                    class="btn btn-sm btn-ghost gap-2"
                    :class="{ 'btn-disabled': index === tabList.length - 1 }"
                  >
                    <span class="hidden sm:inline">Next</span>
                    <ChevronRight class="w-4 h-4" />
                  </button>
                </div>
              </div>

              <!-- Tab Completion Status -->
              <div class="mt-3">
                <div class="flex items-center gap-4">
                  <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full" :class="[
                      isTabCompleted(tab) ? 'bg-success animate-pulse' : 'bg-warning'
                    ]"></div>
                    <span class="text-sm text-base-content/70">
                      {{ isTabCompleted(tab) ? 'Section completed' : 'In progress' }}
                    </span>
                  </div>

                  <div class="flex items-center gap-2">
                    <BarChart3 class="w-4 h-4 text-base-content/50" />
                    <span class="text-sm text-base-content/70">
                      {{ getTabCompletionPercentage(tab) }}% complete
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Tab Fields Content -->
            <div class="p-6">
              <FormFields
                :fields="getFieldsByTab(tab)"
                @update:fields="handleFieldsUpdate"
              />

              <!-- Tab Footer Navigation -->
              <div class="mt-8 pt-6 border-t border-base-200 flex items-center justify-between">
                <button
                  v-if="index > 0"
                  @click="navigateToPreviousTab"
                  class="btn btn-ghost gap-2 hover:btn-primary"
                >
                  <ChevronLeft class="w-4 h-4" />
                  Back to {{ tabList[index - 1] }}
                </button>
                <div v-else></div>

                <button
                  v-if="index < tabList.length - 1"
                  @click="navigateToNextTab"
                  class="btn btn-primary gap-2 shadow-lg hover:shadow-xl"
                  :class="{ 'btn-disabled': !isTabCompleted(tab) && isStrictNavigation }"
                >
                  Continue to {{ tabList[index + 1] }}
                  <ChevronRight class="w-4 h-4" />
                </button>
                <div v-else class="flex items-center gap-2">
                  <CheckCircle class="w-5 h-5" />
                  <span class="font-medium">Form Complete!</span>
                </div>
              </div>
            </div>
          </div>
        </TransitionGroup>
      </div>
    </div>

    <!-- Enhanced No-Tabs Layout -->
    <div v-else>
      <!-- Form Header -->
      <div class="bg-gradient-to-r from-base-200 via-base-100 to-base-200 rounded-t-xl p-6 border-b border-base-200 mb-6">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary/20 to-secondary/20 flex items-center justify-center">
              <FileText class="w-5 h-5 text-primary" />
            </div>
            <div>
              <h3 class="text-lg font-bold text-base-content">Form Fields</h3>
              <p class="text-sm text-base-content/60">Complete all required fields to proceed</p>
            </div>
          </div>

          <!-- Form Statistics -->
          <div class="stats stats-horizontal shadow bg-base-200/50">
            <div class="stat py-2 px-4">
              <div class="stat-title text-xs">Fields</div>
              <div class="stat-value text-lg text-primary">{{ avaliableFields.length }}</div>
            </div>
            <div class="stat py-2 px-4">
              <div class="stat-title text-xs">Required</div>
              <div class="stat-value text-lg text-secondary">{{ requiredFieldsCount }}</div>
            </div>
            <div class="stat py-2 px-4">
              <div class="stat-title text-xs">Completed</div>
              <div class="stat-value text-lg text-success">{{ completedFieldsCount }}</div>
            </div>
          </div>
        </div>

        <!-- Overall Progress -->
        <div class="mt-4">
          <div class="flex items-center gap-2 text-sm text-base-content/60 mb-2">
            <span>Overall Progress:</span>
            <span class="font-semibold">{{ Math.round(overallProgress) }}% complete</span>
          </div>
          <div class="h-2 bg-base-300 rounded-full overflow-hidden">
            <div
              class="h-full bg-gradient-to-r from-primary via-secondary to-primary transition-all duration-500"
              :style="{ width: `${overallProgress}%` }"
            ></div>
          </div>
        </div>
      </div>

      <!-- Form Content -->
      <div class="bg-base-100 rounded-xl shadow-lg border border-base-200 p-6">
        <FormFields
          :fields="avaliableFields"
          @update:fields="handleFieldsUpdate"
        />
      </div>
    </div>

    <!-- Form Validation Summary -->
    <Transition
      enter-active-class="transition-all duration-500 ease-out"
      enter-from-class="opacity-0 translate-y-4"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition-all duration-300 ease-in"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 translate-y-4"
    >
      <div v-if="hasValidationErrors" class="mt-6">
        <div class="alert alert-warning shadow-lg">
          <AlertTriangle class="w-5 h-5 flex-shrink-0" />
          <div class="flex-1">
            <h4 class="font-bold text-sm">Form Validation</h4>
            <div class="text-xs mt-1">
              {{ validationErrorsCount }} field{{ validationErrorsCount !== 1 ? 's' : '' }} need{{ validationErrorsCount === 1 ? 's' : '' }} attention
            </div>
          </div>
          <button
            @click="scrollToFirstError"
            class="btn btn-sm btn-warning btn-outline gap-2"
          >
            <Eye class="w-4 h-4" />
            Show Errors
          </button>
        </div>
      </div>
    </Transition>

    <!-- Form Success State -->
    <Transition
      enter-active-class="transition-all duration-500 ease-out"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition-all duration-300 ease-in"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div v-if="isFormValid && avaliableFields.length > 0" class="mt-6">
        <div class="alert alert-success shadow-lg">
          <CheckCircle class="w-5 h-5 flex-shrink-0" />
          <div class="flex-1">
            <h4 class="font-bold text-sm">Form Complete!</h4>
            <div class="text-xs mt-1">All required fields have been completed successfully</div>
          </div>
          <div class="badge badge-success badge-sm gap-1">
            <Check class="w-3 h-3" />
            Ready
          </div>
        </div>
      </div>
    </Transition>
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
  Layers,
  ChevronLeft,
  ChevronRight,
  CheckCircle,
  FileText,
  BarChart3,
  AlertTriangle,
  Eye,
  Check,
  Folder,
  Settings,
  User,
  Calendar,
  Image,
  Globe,
  Shield,
  Zap
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
  },
  strictNavigation: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(["onFormUpdate"]);

// Reactive state
const avaliableFields = ref([]);
const currentTab = ref('');
const currentTabIndex = ref(0);
const isStrictNavigation = ref(props.strictNavigation);

// Computed properties for tabs
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

// Form statistics
const requiredFieldsCount = computed(() => {
  return avaliableFields.value.filter(field => field.required).length;
});

const completedFieldsCount = computed(() => {
  return avaliableFields.value.filter(field =>
    field.required && field.value && field.value !== ''
  ).length;
});

const overallProgress = computed(() => {
  if (requiredFieldsCount.value === 0) return 100;
  return (completedFieldsCount.value / requiredFieldsCount.value) * 100;
});

const completedTabsCount = computed(() => {
  return tabList.value.filter(tab => isTabCompleted(tab)).length;
});

// Validation
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

// Tab management methods
const getFieldsByTab = (tab) => {
  return avaliableFields.value.filter(field => field.tab === tab);
};

const getTabFieldCount = (tab) => {
  return getFieldsByTab(tab).length;
};

const getTabIcon = (index) => {
  const icons = [Folder, Settings, User, Calendar, Image, Globe, Shield, Zap];
  return icons[index % icons.length];
};

const isTabCompleted = (tab) => {
  const tabFields = getFieldsByTab(tab);
  const requiredTabFields = tabFields.filter(field => field.required);
  if (requiredTabFields.length === 0) return true;
  return requiredTabFields.every(field => field.value && field.value !== '');
};

const getTabCompletionPercentage = (tab) => {
  const tabFields = getFieldsByTab(tab);
  const requiredTabFields = tabFields.filter(field => field.required);
  if (requiredTabFields.length === 0) return 100;

  const completedTabFields = requiredTabFields.filter(field => field.value && field.value !== '');
  return Math.round((completedTabFields.length / requiredTabFields.length) * 100);
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

        element.classList.add('highlight-field');
        setTimeout(() => {
          element.classList.remove('highlight-field');
        }, 2000);
      }, 300);
    }
  }
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
/* Enhanced Gradient Animation */
@keyframes gradient-x {
  0%, 100% {
    background-position: 0% 50%;
  }
  50% {
    background-position: 100% 50%;
  }
}

.animate-gradient-x {
  background-size: 200% 200%;
  animation: gradient-x 3s ease infinite;
}

/* Enhanced Tab Transitions */
.tab-content {
  animation: slideInRight 0.5s ease-out;
}

@keyframes slideInRight {
  from {
    opacity: 0;
    transform: translateX(20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

/* Enhanced Button Animations */
.btn {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn:hover:not(:disabled) {
  transform: translateY(-1px);
}

.btn:active:not(:disabled) {
  transform: translateY(0) scale(0.98);
}

/* Enhanced Stats Component */
.stats {
  transition: all 0.3s ease;
}

.stats:hover {
  transform: scale(1.02);
}

/* Custom Scrollbar for Tab Navigation */
.custom-scrollbar {
  scrollbar-width: thin;
  scrollbar-color: hsl(var(--p) / 0.3) hsl(var(--b3) / 0.5);
}

.custom-scrollbar::-webkit-scrollbar {
  height: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: hsl(var(--b3) / 0.3);
  border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: linear-gradient(45deg, hsl(var(--p) / 0.4), hsl(var(--s) / 0.4));
  border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(45deg, hsl(var(--p) / 0.6), hsl(var(--s) / 0.6));
}

/* Enhanced Progress Bar */
.h-2 {
  transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Enhanced Alert Styling */
.alert {
  border-radius: 1rem;
  border-left: 4px solid;
}

.alert-warning {
  border-left-color: hsl(var(--wa));
}

.alert-success {
  border-left-color: hsl(var(--su));
}

/* Enhanced Badge Animation */
.badge {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.badge:hover {
  transform: scale(1.05);
}

/* Enhanced Pulse Animation */
@keyframes gentlePulse {
  0%, 100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.7;
    transform: scale(1.1);
  }
}

.animate-pulse {
  animation: gentlePulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Tab Navigation Enhancement */
.min-w-max {
  white-space: nowrap;
}

/* Enhanced Focus States */
.btn:focus-visible {
  outline: 2px solid hsl(var(--p));
  outline-offset: 2px;
}

/* Icon Container Enhancement */
.w-10.h-10.rounded-xl,
.w-8.h-8.rounded-lg {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.w-10.h-10.rounded-xl:hover,
.w-8.h-8.rounded-lg:hover {
  transform: rotate(5deg) scale(1.1);
}

/* Form Builder Container */
.form-builder-container {
  max-width: 100%;
  margin: 0 auto;
}

/* Responsive Design */
@media (max-width: 768px) {
  .stats-horizontal {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  }

  .flex.gap-2.overflow-x-auto {
    padding-bottom: 0.5rem;
  }

  .btn-sm {
    padding: 0.375rem 0.75rem;
  }
}

@media (max-width: 640px) {
  .stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
  }

  .hidden.sm\:inline {
    display: none;
  }
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
  .border-base-200 {
    border-color: hsl(var(--bc));
  }

  .bg-gradient-to-r {
    background: hsl(var(--b2));
  }
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
  .animate-gradient-x,
  .animate-pulse,
  .transition-all,
  .transition-transform {
    animation: none;
    transition: none;
  }
}

/* Print Styles */
@media print {
  .btn,
  .stats,
  .badge {
    display: none !important;
  }

  .alert {
    border: 1px solid #000;
    box-shadow: none;
  }
}

/* Enhanced Card Styling */
.bg-base-100.rounded-xl.shadow-lg {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.bg-base-100.rounded-xl.shadow-lg:hover {
  transform: translateY(-2px);
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

/* Tab Completion Status Enhancement */
.w-2.h-2.rounded-full {
  transition: all 0.3s ease;
}
</style>
