<template>
  <button
    @click="openModal"
    class="btn btn-primary btn-sm gap-2 group relative overflow-hidden shadow-md hover:shadow-lg transition-all duration-300 hover:scale-105"
  >
    <div class="absolute inset-0 bg-gradient-to-r from-primary/50 to-secondary/50 opacity-0 group-hover:opacity-30 transition-opacity duration-300"></div>

    <Pencil class="w-4 h-4 transform transition-all duration-300 group-hover:rotate-12 relative z-10" />

    <span class="hidden md:inline relative z-10 font-medium">Edit</span>

    <div class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/20 to-transparent group-hover:translate-x-full transition-transform duration-700 ease-out"></div>
  </button>

  <Teleport to="body">
    <div v-if="isOpen" class="modal modal-open">
      <div
        class="modal-backdrop bg-black/60 backdrop-blur-sm"
        @click="closeModal"
      ></div>

      <div class="modal-box w-full max-w-7xl h-[95vh] max-h-[95vh] p-0 relative overflow-hidden">
        <div
          v-if="isSubmitting"
          class="absolute inset-0 bg-base-100/85 backdrop-blur-sm flex items-center justify-center z-50"
        >
          <div class="flex flex-col items-center gap-4">
            <div class="loading loading-spinner loading-lg text-primary drop-shadow-lg"></div>
            <div class="text-center">
              <p class="text-lg font-semibold text-base-content">Saving Changes</p>
              <p class="text-sm text-base-content/60">Please wait while we update the record...</p>
            </div>
          </div>
        </div>

        <div class="flex flex-col h-full">
          <div class="sticky top-0 z-40 bg-gradient-to-r from-base-200 via-base-300 to-base-200 border-b border-base-content/10 px-6 py-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-4">
                <button
                  @click="closeModal"
                  class="btn btn-sm btn-circle btn-ghost hover:btn-error hover:rotate-90 transition-all duration-300"
                >
                  <XIcon class="w-5 h-5" />
                </button>

                <div class="flex items-center gap-3">
                  <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-primary/20 to-secondary/20 flex items-center justify-center">
                    <HashIcon class="w-6 h-6 text-primary" />
                  </div>
                  <div>
                    <h2 class="text-xl font-bold text-base-content flex items-center gap-2">
                      Edit Record
                      <div class="badge badge-secondary badge-sm font-mono">#{{props.id}}</div>
                    </h2>
                    <p class="text-sm text-base-content/60">Modify the information below to update this record</p>
                  </div>
                </div>
              </div>

              <div class="flex items-center gap-3">
                <button
                  @click="closeModal"
                  class="btn btn-ghost btn-sm hover:btn-error"
                  :disabled="isSubmitting"
                >
                  <XIcon class="w-4 h-4" />
                  Cancel
                </button>
                <button
                  @click="saveChanges"
                  :disabled="isSubmitting || !hasChanges"
                  class="btn btn-primary btn-sm gap-2 min-w-[130px] shadow-lg hover:shadow-xl"
                  :class="{ 'btn-disabled': !hasChanges }"
                >
                  <SaveIcon class="w-4 h-4" :class="{ 'animate-spin': isSubmitting }" />
                  {{ isSubmitting ? 'Saving...' : 'Save Changes' }}
                </button>
              </div>
            </div>

            <div class="mt-4 flex items-center gap-6 text-sm text-base-content/60">
              <div class="flex items-center gap-2">
                <CalendarIcon class="w-4 h-4" />
                <span>Last updated {{ formatRelativeDate(props.modelValue?.updated_at) }}</span>
              </div>
              <div class="flex items-center gap-2">
                <UserIcon class="w-4 h-4" />
                <span>Created {{ formatRelativeDate(props.modelValue?.created_at) }}</span>
              </div>
              <div v-if="hasChanges" class="flex items-center gap-2 text-warning">
                <AlertCircle class="w-4 h-4" />
                <span class="font-medium">Unsaved changes</span>
              </div>
            </div>

            <div v-if="hasChanges" class="mt-3">
              <div class="flex items-center gap-2 text-sm">
                <div class="flex items-center gap-1">
                  <div class="w-2 h-2 rounded-full bg-warning animate-pulse"></div>
                  <span class="text-base-content/70">{{ changedFieldsCount }} field(s) modified</span>
                </div>
                <div class="flex-1 mx-4">
                  <div class="h-1 bg-base-300 rounded-full overflow-hidden">
                    <div
                      class="h-full bg-warning transition-all duration-500"
                      :style="{ width: `${Math.min(100, (changedFieldsCount / totalFields) * 100)}%` }"
                    ></div>
                  </div>
                </div>
                <button
                  @click="showChangeSummary = !showChangeSummary"
                  class="btn btn-ghost btn-xs gap-1"
                >
                  <Eye class="w-3 h-3" />
                  {{ showChangeSummary ? 'Hide' : 'Review' }}
                </button>
              </div>
            </div>

            <Transition
              enter-active-class="transition-all duration-300 ease-out"
              enter-from-class="opacity-0 -translate-y-4 scale-95"
              enter-to-class="opacity-100 translate-y-0 scale-100"
              leave-active-class="transition-all duration-200 ease-in"
              leave-from-class="opacity-100 translate-y-0 scale-100"
              leave-to-class="opacity-0 -translate-y-4 scale-95"
            >
              <div v-if="Object.keys(formErrors).length > 0" class="mt-4">
                <div class="alert alert-error shadow-lg">
                  <AlertTriangle class="w-5 h-5 flex-shrink-0" />
                  <div class="flex-1">
                    <h3 class="font-bold text-sm">Please correct the following errors:</h3>
                    <div class="text-xs opacity-80 mt-1">
                      {{ Object.keys(formErrors).length }} field(s) need attention
                    </div>
                  </div>
                  <button
                    @click="formErrors = {}"
                    class="btn btn-sm btn-circle btn-ghost hover:btn-error"
                  >
                    <XIcon class="w-4 h-4" />
                  </button>
                </div>
              </div>
            </Transition>

            <Transition
              enter-active-class="transition-all duration-300 ease-out"
              enter-from-class="opacity-0 -translate-y-2"
              enter-to-class="opacity-100 translate-y-0"
              leave-active-class="transition-all duration-200 ease-in"
              leave-from-class="opacity-100 translate-y-0"
              leave-to-class="opacity-0 -translate-y-2"
            >
              <div v-if="showChangeSummary && hasChanges" class="mt-4">
                <div class="card bg-warning/5 border border-warning/20">
                  <div class="card-body py-3">
                    <h4 class="text-sm font-semibold text-warning mb-2">Changed Fields:</h4>
                    <div class="flex flex-wrap gap-2">
                      <div
                        v-for="field in changedFields"
                        :key="field.key"
                        class="badge badge-warning badge-sm gap-1"
                      >
                        {{ field.label }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </Transition>
          </div>

          <div class="flex-1 overflow-auto p-6">
            <div v-if="filterSections.sectionsWithFields.length > 0" class="space-y-6">
              <TransitionGroup
                enter-active-class="transition-all duration-500 ease-out"
                enter-from-class="opacity-0 translate-y-8"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-300 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 translate-y-8"
              >
                <div
                  v-for="(section, index) in filterSections.sectionsWithFields"
                  :key="section.section"
                  class="card bg-base-100 shadow-lg border border-base-200 hover:shadow-xl hover:border-primary/30 transition-all duration-300"
                  :style="{ animationDelay: `${index * 100}ms` }"
                >
                  <div class="card-body">
                    <div class="flex items-center gap-3 mb-4 pb-3 border-b border-base-200">
                      <div class="w-8 h-8 rounded-lg bg-primary/20 flex items-center justify-center">
                        <Folder class="w-4 h-4 text-primary" />
                      </div>
                      <div class="flex-1">
                        <h3 class="text-lg font-semibold text-base-content">{{ section.section }}</h3>
                        <p class="text-sm text-base-content/60">{{ section.fields.length }} fields</p>
                      </div>
                      <div v-if="getSectionChanges(section.section) > 0" class="badge badge-warning badge-sm">
                        {{ getSectionChanges(section.section) }} changed
                      </div>
                    </div>

                    <FormBuilder
                      :columns="section.fields"
                      :errors="formErrors"
                      @onFormUpdate="onFormUpdate"
                      editMode="true"
                      :modelValue="props.modelValue"
                    />
                  </div>
                </div>
              </TransitionGroup>
            </div>

            <div v-if="filterSections.fields.length > 0" class="space-y-6">
              <div class="card bg-base-100 shadow-lg border border-base-200 hover:shadow-xl hover:border-primary/30 transition-all duration-300">
                <div class="card-body">
                  <div class="flex items-center gap-3 mb-4 pb-3 border-b border-base-200">
                    <div class="w-8 h-8 rounded-lg bg-secondary/20 flex items-center justify-center">
                      <Settings class="w-4 h-4 text-secondary" />
                    </div>
                    <div class="flex-1">
                      <h3 class="text-lg font-semibold text-base-content">General Information</h3>
                      <p class="text-sm text-base-content/60">Basic details and settings</p>
                    </div>
                    <div v-if="getSectionChanges('general') > 0" class="badge badge-warning badge-sm">
                      {{ getSectionChanges('general') }} changed
                    </div>
                  </div>

                  <FormBuilder
                    :columns="filterSections.fields"
                    :errors="formErrors"
                    @onFormUpdate="onFormUpdate"
                    editMode="true"
                    :modelValue="props.modelValue"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import axios from 'axios';
import { startWindToast } from "@mariojgt/wind-notify/packages/index.js";
import { formatDistanceToNow } from 'date-fns';
import FormBuilder from '../formHelpers/formbuilder.vue';
import {
  X as XIcon,
  Pencil,
  Save as SaveIcon,
  AlertTriangle,
  Calendar as CalendarIcon,
  Hash as HashIcon,
  Folder,
  Settings,
  Eye,
  RotateCcw,
  User as UserIcon,
  AlertCircle
} from 'lucide-vue-next';

const isOpen = ref(false);
const isSubmitting = ref(false);
const formErrors = ref({});
const showChangeSummary = ref(false);
const originalValues = ref({});

const props = defineProps({
  columns: {
    type: Array,
    default: () => [],
  },
  model: {
    type: String,
    default: '',
  },
  endpoint: {
    type: String,
    default: '',
  },
  id: {
    type: Number,
    default: 0,
  },
  modelValue: {
    type: Object,
    default: () => ({}),
  },
  permission: {
    type: String,
    default: null,
  },
});

const emit = defineEmits(['onEdit']);

const availableFields = ref(props.columns.filter(column => column.canEdit));

// Store original values when modal opens
const storeOriginalValues = () => {
  originalValues.value = {};
  availableFields.value.forEach(field => {
    originalValues.value[field.key] = props.modelValue[field.key];
  });
};

// Computed properties
const filterSections = computed(() => {
  const columnsWithSections = props.columns.filter(column => column.section);
  const columnsWithoutSections = props.columns.filter(column => !column.section);
  const sections = [...new Set(columnsWithSections.map(column => column.section))];

  return {
    sections,
    fields: columnsWithoutSections,
    sectionsWithFields: sections.map(section => ({
      section,
      fields: columnsWithSections.filter(column => column.section === section)
    }))
  };
});

const totalFields = computed(() => availableFields.value.length);

const changedFields = computed(() => {
  return availableFields.value.filter(field =>
    isFieldChanged(field.key)
  );
});

const changedFieldsCount = computed(() => changedFields.value.length);

const hasChanges = computed(() => changedFieldsCount.value > 0);

// Helper functions
const isFieldChanged = (fieldKey: string): boolean => {
  const currentField = availableFields.value.find(f => f.key === fieldKey);
  const originalValue = originalValues.value[fieldKey];
  const currentValue = currentField?.value;

  return originalValue !== currentValue;
};

const getSectionChanges = (sectionName: string): number => {
  const sectionFields = sectionName === 'general'
    ? filterSections.value.fields
    : filterSections.value.sectionsWithFields.find(s => s.section === sectionName)?.fields || [];

  return sectionFields.filter(field => isFieldChanged(field.key)).length;
};

const formatRelativeDate = (date: string): string => {
  if (!date) return 'Unknown';
  try {
    return formatDistanceToNow(new Date(date), { addSuffix: true });
  } catch {
    return 'Unknown';
  }
};

// Methods
const openModal = () => {
  isOpen.value = true;
  storeOriginalValues();
};

const closeModal = () => {
  if (hasChanges.value) {
    if (confirm('You have unsaved changes. Are you sure you want to close?')) {
      isOpen.value = false;
      formErrors.value = {};
      showChangeSummary.value = false;
    }
  } else {
    isOpen.value = false;
    formErrors.value = {};
    showChangeSummary.value = false;
  }
};

const resetChanges = () => {
  if (confirm('Are you sure you want to reset all changes?')) {
    availableFields.value.forEach(field => {
      field.value = originalValues.value[field.key];
    });
    formErrors.value = {};
    showChangeSummary.value = false;
  }
};

const onFormUpdate = (formData) => {
  if (availableFields.value.length > 0) {
    formData.forEach(formDataValue => {
      const index = availableFields.value.findIndex(value => value.key === formDataValue.key);
      if (index !== -1) {
        availableFields.value[index] = formDataValue;
      }
    });
  } else {
    availableFields.value = formData;
  }
};

const saveChanges = async () => {
  if (isSubmitting.value || !hasChanges.value) return;

  try {
    isSubmitting.value = true;
    formErrors.value = {};

    const response = await axios.post(props.endpoint, {
      model: props.model,
      id: props.id,
      data: availableFields.value,
      permission: props.permission,
    });

    startWindToast('success', 'Changes saved successfully! ✨', 'success');
    emit('onEdit');

    // Update original values to current values
    storeOriginalValues();
    showChangeSummary.value = false;

    setTimeout(() => {
      closeModal();
    }, 1000);
  } catch (error: any) {
    if (error.response?.data?.errors) {
      const errors = error.response.data.errors;

      if (Array.isArray(errors)) {
        errors.forEach((errorArray) => {
          if (Array.isArray(errorArray) && errorArray.length > 0) {
            const errorMessage = errorArray[0];
            const fieldMatch = errorMessage.match(/The (.*?) (field )?(is required|must be|invalid|already exists)/i);
            if (fieldMatch) {
              const fieldName = fieldMatch[1].toLowerCase().replace(' ', '_');
              formErrors.value[fieldName] = errorMessage;
            }
          }
        });
      } else {
        Object.entries(errors).forEach(([key, messages]) => {
          if (Array.isArray(messages) && messages.length > 0) {
            formErrors.value[key.toLowerCase()] = messages[0];
          }
        });
      }

      Object.values(formErrors.value).forEach((errorMsg: string) => {
        startWindToast('error', errorMsg, 'error');
      });
    } else {
      startWindToast('error', 'An error occurred while saving changes. Please try again.', 'error');
    }
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<style scoped>
/* Enhanced Custom Scrollbar */
.custom-scrollbar {
  scrollbar-width: thin;
  scrollbar-color: hsl(var(--p) / 0.3) hsl(var(--b3) / 0.5);
}

.custom-scrollbar::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: hsl(var(--b3) / 0.3);
  border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: linear-gradient(45deg, hsl(var(--p) / 0.3), hsl(var(--s) / 0.3));
  border-radius: 10px;
  border: 2px solid hsl(var(--b1));
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(45deg, hsl(var(--p) / 0.5), hsl(var(--s) / 0.5));
}

/* Field Highlight Animation */
@keyframes highlightPulse {
  0% {
    box-shadow: 0 0 0 0 hsl(var(--p) / 0.4);
    background-color: hsl(var(--p) / 0.1);
  }
  50% {
    box-shadow: 0 0 0 10px hsl(var(--p) / 0);
    background-color: hsl(var(--p) / 0.2);
  }
  100% {
    box-shadow: 0 0 0 0 hsl(var(--p) / 0);
    background-color: hsl(var(--p) / 0);
  }
}

.highlight-field {
  animation: highlightPulse 2s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: 0.75rem;
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

/* Card Hover Effects */
.card {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.card:hover {
  transform: translateY(-2px);
}

/* Modal Animations */
.modal-open .modal-box {
  animation: modalSlideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: scale(0.95) translateY(20px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

/* Change indicator animations */
.animate-pulse {
  animation: gentle-pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes gentle-pulse {
  0%, 100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.7;
    transform: scale(1.1);
  }
}

/* Enhanced Alert Animations */
.alert {
  border-left: 4px solid;
}

.alert-error {
  border-left-color: hsl(var(--er));
}

/* Progress bar for changes */
.h-1 {
  transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Loading Animation Enhancement */
.loading-spinner {
  filter: drop-shadow(0 0 10px hsl(var(--p) / 0.3));
}

/* Badge animations */
.badge {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.badge:hover {
  transform: scale(1.05);
}

/* Change summary animations */
.card {
  border-radius: 1rem;
}

.card-body {
  transition: all 0.3s ease;
}

/* Navigation item hover effects */
button:hover .w-3 {
  transform: scale(1.2);
}

/* Responsive Design Enhancements */
@media (max-width: 1024px) {
  .w-80 {
    width: 100%;
    border-left: none;
    border-top: 1px solid hsl(var(--b3));
  }

  .h-full.flex {
    flex-direction: column;
  }

  .modal-box {
    width: 95vw;
    height: 95vh;
  }
}

@media (max-width: 640px) {
  .modal-box {
    width: 100vw;
    height: 100vh;
    max-height: 100vh;
    border-radius: 0;
  }

  .px-6 {
    padding-left: 1rem;
    padding-right: 1rem;
  }

  .gap-4 {
    gap: 0.75rem;
  }
}

/* Enhanced focus states for accessibility */
.btn:focus-visible {
  outline: 2px solid hsl(var(--p));
  outline-offset: 2px;
}

/* Smooth state transitions */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 300ms;
}

/* Custom animations for change indicators */
@keyframes fadeInScale {
  from {
    opacity: 0;
    transform: scale(0.8);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.badge-warning {
  animation: fadeInScale 0.3s ease-out;
}
</style>
