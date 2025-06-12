<template>
  <!-- Enhanced Create Button with Professional Styling -->
  <button
    @click="openModal"
    class="btn btn-primary gap-2 group relative overflow-hidden shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105"
    :disabled="!canCreate"
  >
    <!-- Background Animation -->
    <div class="absolute inset-0 bg-gradient-to-r from-primary to-secondary opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>

    <!-- Icon with Animation -->
    <PlusCircle class="w-5 h-5 transform transition-all duration-300 group-hover:rotate-90 relative z-10" />

    <!-- Text -->
    <span class="hidden md:inline relative z-10 font-semibold">Create New</span>

    <!-- Shimmer Effect -->
    <div class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/20 to-transparent group-hover:translate-x-full transition-transform duration-700 ease-out"></div>
  </button>

  <!-- Enhanced Modal with Blur Backdrop -->
  <div v-if="isOpen" class="modal modal-open">
    <!-- Backdrop with Blur -->
    <div
      class="modal-backdrop bg-black/60 backdrop-blur-sm"
      @click="closeModal"
    ></div>

    <!-- Modal Container -->
    <div class="modal-box w-full max-w-6xl h-[90vh] max-h-[90vh] p-0 relative overflow-hidden">
      <!-- Loading Overlay -->
      <div
        v-if="isSubmitting"
        class="absolute inset-0 bg-base-100/80 backdrop-blur-sm flex items-center justify-center z-50"
      >
        <div class="flex flex-col items-center gap-4">
          <div class="loading loading-spinner loading-lg text-primary"></div>
          <div class="text-center">
            <p class="text-lg font-semibold text-base-content">Creating Entry</p>
            <p class="text-sm text-base-content/60">Please wait while we process your request...</p>
          </div>
        </div>
      </div>

      <!-- Permission Check -->
      <div v-if="!canCreate" class="flex items-center justify-center h-full">
        <div class="text-center space-y-6 p-8">
          <div class="w-24 h-24 mx-auto rounded-full bg-warning/20 flex items-center justify-center">
            <ShieldAlert class="w-12 h-12 text-warning" />
          </div>
          <div class="space-y-2">
            <h2 class="text-2xl font-bold text-error">Access Denied</h2>
            <p class="text-base-content/70 max-w-md">You don't have permission to create new entries. Please contact your system administrator for access.</p>
          </div>
          <button @click="closeModal" class="btn btn-neutral">
            Close
          </button>
        </div>
      </div>

      <!-- Main Content -->
      <div v-else class="flex flex-col h-full">
        <!-- Enhanced Header -->
        <div class="sticky top-0 z-40 bg-gradient-to-r from-base-200 to-base-300 border-b border-base-content/10 px-6 py-4">
          <div class="flex items-center justify-between">
            <!-- Title Section -->
            <div class="flex items-center gap-4">
              <button
                @click="closeModal"
                class="btn btn-sm btn-circle btn-ghost hover:btn-error hover:rotate-90 transition-all duration-300"
              >
                <X class="w-5 h-5" />
              </button>

              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-primary/20 flex items-center justify-center">
                  <FileEdit class="w-5 h-5 text-primary" />
                </div>
                <div>
                  <h2 class="text-xl font-bold text-base-content">Create New Entry</h2>
                  <p class="text-sm text-base-content/60">Fill in the information below to create a new record</p>
                </div>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-3">
              <button
                @click="closeModal"
                class="btn btn-ghost btn-sm hover:btn-error"
                :disabled="isSubmitting"
              >
                <X class="w-4 h-4" />
                Cancel
              </button>
              <button
                @click="createNew"
                :disabled="isSubmitting || !isFormValid"
                class="btn btn-primary btn-sm gap-2 min-w-[120px] shadow-lg hover:shadow-xl"
                :class="{ 'btn-disabled': !isFormValid }"
              >
                <Save class="w-4 h-4" :class="{ 'animate-spin': isSubmitting }" />
                {{ isSubmitting ? 'Creating...' : 'Create Entry' }}
              </button>
            </div>
          </div>

          <!-- Progress Indicator -->
          <div class="mt-4">
            <div class="flex items-center gap-2 text-sm text-base-content/60">
              <div class="flex items-center gap-1">
                <div class="w-2 h-2 rounded-full bg-success"></div>
                <span>{{ completedFieldsCount }}/{{ totalRequiredFields }} required fields completed</span>
              </div>
              <div class="flex-1 mx-4">
                <progress
                  class="progress progress-success w-full h-2"
                  :value="completedFieldsCount"
                  :max="totalRequiredFields"
                ></progress>
              </div>
              <span class="font-semibold">{{ Math.round((completedFieldsCount / totalRequiredFields) * 100) }}%</span>
            </div>
          </div>

          <!-- Error Alert -->
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
                  <X class="w-4 h-4" />
                </button>
              </div>
            </div>
          </Transition>
        </div>

        <!-- Content Area -->
        <div class="flex-1 overflow-hidden">
          <div class="h-full flex">
            <!-- Main Form Area -->
            <div class="flex-1 overflow-auto custom-scrollbar">
              <div class="p-6">
                <!-- Form Sections -->
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
                        <!-- Section Header -->
                        <div class="flex items-center gap-3 mb-4 pb-3 border-b border-base-200">
                          <div class="w-8 h-8 rounded-lg bg-primary/20 flex items-center justify-center">
                            <Folder class="w-4 h-4 text-primary" />
                          </div>
                          <div>
                            <h3 class="text-lg font-semibold text-base-content">{{ section.section }}</h3>
                            <p class="text-sm text-base-content/60">{{ section.fields.length }} fields</p>
                          </div>
                        </div>

                        <!-- Section Fields -->
                        <FormBuilder
                          :columns="section.fields"
                          :errors="formErrors"
                          @onFormUpdate="onFormUpdate"
                        />
                      </div>
                    </div>
                  </TransitionGroup>
                </div>

                <!-- Standard Fields -->
                <div v-if="filterSections.fields.length > 0" class="space-y-6">
                  <div class="card bg-base-100 shadow-lg border border-base-200 hover:shadow-xl hover:border-primary/30 transition-all duration-300">
                    <div class="card-body">
                      <div class="flex items-center gap-3 mb-4 pb-3 border-b border-base-200">
                        <div class="w-8 h-8 rounded-lg bg-secondary/20 flex items-center justify-center">
                          <Settings class="w-4 h-4 text-secondary" />
                        </div>
                        <div>
                          <h3 class="text-lg font-semibold text-base-content">General Information</h3>
                          <p class="text-sm text-base-content/60">Basic details and settings</p>
                        </div>
                      </div>

                      <FormBuilder
                        :columns="filterSections.fields"
                        :errors="formErrors"
                        @onFormUpdate="onFormUpdate"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Enhanced Sidebar -->
            <div class="w-80 bg-base-200/50 border-l border-base-200 p-6 overflow-auto custom-scrollbar">
              <!-- Quick Stats -->
              <div class="card bg-base-100 shadow-md mb-6">
                <div class="card-body">
                  <h3 class="card-title text-sm flex items-center gap-2">
                    <Info class="w-4 h-4 text-primary" />
                    Form Progress
                  </h3>
                  <div class="space-y-3 mt-4">
                    <div class="flex justify-between text-sm">
                      <span class="text-base-content/70">Completed</span>
                      <span class="font-semibold text-success">{{ completedFieldsCount }}/{{ totalRequiredFields }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                      <span class="text-base-content/70">Optional</span>
                      <span class="font-semibold text-info">{{ optionalFieldsCount }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                      <span class="text-base-content/70">Errors</span>
                      <span class="font-semibold text-error">{{ Object.keys(formErrors).length }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Quick Tips -->
              <div class="card bg-base-100 shadow-md mb-6">
                <div class="card-body">
                  <h3 class="card-title text-sm flex items-center gap-2">
                    <HelpCircle class="w-4 h-4 text-primary" />
                    Quick Tips
                  </h3>
                  <ul class="space-y-3 mt-4">
                    <li class="flex items-start gap-2 text-sm">
                      <Check class="w-4 h-4 text-success mt-0.5 flex-shrink-0" />
                      <span class="text-base-content/70">Fill required fields marked with <span class="text-error">*</span></span>
                    </li>
                    <li class="flex items-start gap-2 text-sm">
                      <Check class="w-4 h-4 text-success mt-0.5 flex-shrink-0" />
                      <span class="text-base-content/70">Use sections to organize related information</span>
                    </li>
                    <li class="flex items-start gap-2 text-sm">
                      <Check class="w-4 h-4 text-success mt-0.5 flex-shrink-0" />
                      <span class="text-base-content/70">Changes are validated in real-time</span>
                    </li>
                    <li class="flex items-start gap-2 text-sm">
                      <Check class="w-4 h-4 text-success mt-0.5 flex-shrink-0" />
                      <span class="text-base-content/70">Save drafts by clicking Create</span>
                    </li>
                  </ul>
                </div>
              </div>

              <!-- Field Navigation -->
              <div class="card bg-base-100 shadow-md">
                <div class="card-body">
                  <h3 class="card-title text-sm flex items-center gap-2">
                    <Navigation class="w-4 h-4 text-primary" />
                    Field Navigation
                  </h3>
                  <div class="space-y-2 mt-4">
                    <button
                      v-for="field in availableFields.slice(0, 8)"
                      :key="field.key"
                      @click="scrollToField(field.key)"
                      class="w-full text-left px-3 py-2 text-xs rounded-lg transition-all duration-200 flex items-center gap-2"
                      :class="[
                        formErrors[field.key]
                          ? 'bg-error/10 text-error hover:bg-error/20 border border-error/30'
                          : 'hover:bg-base-200 text-base-content/70 hover:text-base-content'
                      ]"
                    >
                      <div
                        :class="[
                          'w-2 h-2 rounded-full',
                          formErrors[field.key] ? 'bg-error animate-pulse' : 'bg-base-300'
                        ]"
                      ></div>
                      <span class="truncate">{{ field.label }}</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { startWindToast } from "@mariojgt/wind-notify/packages/index.js";
import axios from 'axios';
import FormBuilder from '../formHelpers/FormBuilder.vue';
import {
  PlusCircle,
  X,
  Save,
  FileEdit,
  Folder,
  AlertTriangle,
  ShieldAlert,
  Info,
  HelpCircle,
  Check,
  Settings,
  Navigation
} from 'lucide-vue-next';

const isOpen = ref(false);
const isSubmitting = ref(false);
const formErrors = ref({});

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
  permission: {
    type: String,
    default: null,
  },
});

const emit = defineEmits(['onCreate']);

// Computed properties
const canCreate = computed(() => {
  return props.columns.some(column => column.canCreate);
});

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

const availableFields = ref(props.columns.filter(column => column.canCreate));

const totalRequiredFields = computed(() => {
  return availableFields.value.filter(field => field.required).length;
});

const completedFieldsCount = computed(() => {
  return availableFields.value.filter(field =>
    field.required && field.value && field.value !== ''
  ).length;
});

const optionalFieldsCount = computed(() => {
  return availableFields.value.filter(field => !field.required).length;
});

const isFormValid = computed(() => {
  const requiredFields = availableFields.value.filter(field => field.required);
  return requiredFields.every(field => field.value && field.value !== '');
});

// Methods
const openModal = () => {
  if (canCreate.value) {
    isOpen.value = true;
  }
};

const closeModal = () => {
  isOpen.value = false;
  formErrors.value = {};
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

const createNew = async () => {
  if (isSubmitting.value || !isFormValid.value) return;

  try {
    isSubmitting.value = true;
    formErrors.value = {};

    const response = await axios.post(props.endpoint, {
      model: props.model,
      data: availableFields.value,
      permission: props.permission,
    });

    startWindToast('success', 'Entry created successfully! 🎉', 'success');
    emit('onCreate');
    closeModal();
  } catch (error: any) {
    formErrors.value = {};
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
      } else if (typeof errors === 'object') {
        Object.entries(errors).forEach(([key, value]) => {
          if (Array.isArray(value) && value.length > 0) {
            formErrors.value[key] = value[0];
          }
        });
      }

      Object.values(formErrors.value).forEach((errorMsg: string) => {
        startWindToast('error', errorMsg, 'error');
      });
    } else if (error.message) {
      startWindToast('error', error.message, 'error');
    }
  } finally {
    isSubmitting.value = false;
  }
};

const scrollToField = (fieldKey: string) => {
  const element = document.querySelector(`[data-field-key="${fieldKey}"]`);
  if (element) {
    element.scrollIntoView({
      behavior: 'smooth',
      block: 'center'
    });

    // Add highlight effect
    element.classList.add('highlight-field');
    setTimeout(() => {
      element.classList.remove('highlight-field');
    }, 2000);
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

/* Progress Animation */
.progress {
  transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Enhanced Alert Animations */
.alert {
  border-left: 4px solid;
}

.alert-error {
  border-left-color: hsl(var(--er));
}

/* Loading Animation Enhancement */
.loading-spinner {
  filter: drop-shadow(0 0 10px hsl(var(--p) / 0.3));
}

/* Responsive Design */
@media (max-width: 1024px) {
  .w-80 {
    width: 100%;
    border-left: none;
    border-top: 1px solid hsl(var(--b3));
  }

  .h-full.flex {
    flex-direction: column;
  }
}

@media (max-width: 640px) {
  .modal-box {
    width: 100%;
    height: 100vh;
    max-height: 100vh;
    border-radius: 0;
  }
}
</style>
