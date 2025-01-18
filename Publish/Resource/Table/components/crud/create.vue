# Template Section
<template>
  <!-- Create Button with improved animation -->
  <button
    @click="isOpen = true"
    class="btn btn-primary gap-2 group relative overflow-hidden hover:shadow-lg transition-all duration-300"
  >
    <PlusCircle
      class="w-5 h-5 transform transition-all duration-300 group-hover:rotate-90"
    />
    <span class="hidden md:inline">Create New</span>
    <div class="absolute inset-0 bg-white/20 transform translate-y-full group-hover:translate-y-0 transition-transform duration-300"></div>
  </button>

  <!-- Modal with improved transitions -->
  <Transition
    enter-active-class="transition-all duration-300 ease-out"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition-all duration-200 ease-in"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div v-if="isOpen" class="fixed inset-0 z-50 overflow-hidden">
      <!-- Enhanced Backdrop with blur -->
      <div
        class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity"
        @click="isOpen = false"
      />

      <!-- Responsive Modal Container -->
      <div class="relative min-h-screen flex items-center justify-center p-0 md:p-4">
        <Transition
          enter-active-class="transition-all duration-300 ease-out"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
          leave-active-class="transition-all duration-200 ease-in"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
        >
          <div v-if="canCreate"
               class="relative bg-base-100 rounded-none md:rounded-2xl shadow-2xl flex flex-col w-full min-h-screen md:min-h-[80vh] md:max-h-[90vh] md:w-11/12 max-w-7xl transform transition-all">

            <!-- Loading Overlay with improved animation -->
            <div v-if="isSubmitting"
                 class="absolute inset-0 bg-base-100/70 backdrop-blur-sm flex items-center justify-center z-50">
              <div class="flex flex-col items-center gap-4">
                <div class="loading loading-spinner loading-lg text-primary"></div>
                <p class="text-sm font-medium text-base-content/70">Processing...</p>
              </div>
            </div>

            <!-- Enhanced Header with better spacing -->
            <div class="sticky top-0 z-10 bg-base-200 px-6 py-4 border-b border-base-content/10">
              <div class="flex flex-col sm:flex-row justify-between gap-4">
                <h2 class="text-lg font-semibold flex items-center gap-3 text-base-content">
                  <button
                    @click="isOpen = false"
                    class="btn btn-sm btn-circle btn-ghost hover:bg-base-300 hover:rotate-90 transition-all duration-200"
                  >
                    <X class="w-5 h-5" />
                  </button>
                  <div class="flex items-center gap-2">
                    <FileEdit class="w-5 h-5 text-primary" />
                    <span>Create New Entry</span>
                  </div>
                </h2>

                <div class="flex items-center gap-3">
                  <button
                    @click="isOpen = false"
                    class="btn btn-ghost btn-sm hover:bg-base-300"
                  >
                    Cancel
                  </button>
                  <button
                    @click="createNew"
                    :disabled="isSubmitting"
                    class="btn btn-primary btn-sm gap-2 min-w-[100px]"
                  >
                    <Save class="w-4 h-4" :class="{ 'animate-spin': isSubmitting }" />
                    {{ isSubmitting ? 'Creating...' : 'Create' }}
                  </button>
                </div>
              </div>

              <!-- Improved Error Alert -->
              <TransitionGroup
                enter-active-class="transition-all duration-300"
                enter-from-class="opacity-0 -translate-y-4"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-200"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-4"
              >
                <div
                  v-if="Object.keys(formErrors).length > 0"
                  class="mt-4 alert alert-error shadow-lg"
                  key="error-alert"
                >
                  <AlertTriangle class="w-5 h-5 flex-shrink-0" />
                  <div>
                    <h3 class="font-bold">Please correct the following errors</h3>
                    <div class="text-xs opacity-75">Review the highlighted fields below</div>
                  </div>
                </div>
              </TransitionGroup>
            </div>

            <!-- Enhanced Content Area with better scrolling -->
            <div class="flex-1 overflow-auto p-6 custom-scrollbar">
              <div class="container mx-auto max-w-5xl">
                <div class="grid gap-6 lg:grid-cols-12">
                  <!-- Form Fields Section -->
                  <div class="lg:col-span-8 space-y-6">
                    <TransitionGroup
                      enter-active-class="transition-all duration-300"
                      enter-from-class="opacity-0 translate-y-4"
                      enter-to-class="opacity-100 translate-y-0"
                      leave-active-class="transition-all duration-200"
                      leave-from-class="opacity-100 translate-y-0"
                      leave-to-class="opacity-0 translate-y-4"
                    >
                      <!-- Enhanced Sections -->
                      <div
                        v-if="filterSections.sectionsWithFields.length > 0"
                        class="w-full space-y-4"
                      >
                        <div
                          v-for="(item, index) in filterSections.sectionsWithFields"
                          :key="item.section"
                          class="collapse collapse-plus bg-base-200 rounded-box border border-base-content/10 hover:border-primary/30 transition-all duration-200 shadow-sm hover:shadow-md"
                        >
                          <input type="checkbox" :checked="index === 0" />
                          <div class="collapse-title text-lg font-medium flex items-center gap-3 pr-12">
                            <Folder class="w-5 h-5 text-primary" />
                            {{ item.section }}
                          </div>
                          <div class="collapse-content bg-base-100/50">
                            <div class="pt-4">
                              <form-builder
                                :columns="item.fields"
                                :errors="formErrors"
                                @onFormUpdate="onFormUpdate"
                              />
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Enhanced Standard Fields -->
                      <div
                        v-if="filterSections.fields.length > 0"
                        class="card bg-base-200 shadow-lg border border-base-content/10 hover:shadow-xl transition-all duration-200"
                      >
                        <div class="card-body">
                          <form-builder
                            :columns="filterSections.fields"
                            :errors="formErrors"
                            @onFormUpdate="onFormUpdate"
                          />
                        </div>
                      </div>
                    </TransitionGroup>
                  </div>

                  <!-- Enhanced Sidebar -->
                  <div class="lg:col-span-4 space-y-4">
                    <!-- Info Card -->
                    <div class="card bg-base-200 shadow-lg hover:shadow-xl transition-all duration-200">
                      <div class="card-body">
                        <h3 class="text-sm font-medium text-base-content flex items-center gap-2">
                          <Info class="w-4 h-4 text-primary" />
                          Information
                        </h3>
                        <div class="text-sm text-base-content/70 mt-2 space-y-2">
                          <p>Fill in the required information to create a new entry.</p>
                          <p>All fields marked with <span class="text-error">*</span> are required.</p>
                        </div>
                      </div>
                    </div>

                    <!-- Enhanced Quick Tips -->
                    <div class="card bg-base-200 shadow-lg hover:shadow-xl transition-all duration-200">
                      <div class="card-body">
                        <h3 class="text-sm font-medium text-base-content flex items-center gap-2">
                          <HelpCircle class="w-4 h-4 text-primary" />
                          Quick Tips
                        </h3>
                        <ul class="text-sm text-base-content/70 mt-2 space-y-3">
                          <li class="flex items-start gap-2">
                            <div class="rounded-full bg-success/20 p-1 mt-0.5">
                              <Check class="w-3 h-3 text-success" />
                            </div>
                            <span>Use sections to organize related fields</span>
                          </li>
                          <li class="flex items-start gap-2">
                            <div class="rounded-full bg-success/20 p-1 mt-0.5">
                              <Check class="w-3 h-3 text-success" />
                            </div>
                            <span>Preview changes before submitting</span>
                          </li>
                          <li class="flex items-start gap-2">
                            <div class="rounded-full bg-success/20 p-1 mt-0.5">
                              <Check class="w-3 h-3 text-success" />
                            </div>
                            <span>All changes are saved automatically</span>
                          </li>
                        </ul>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Enhanced No Permission Message -->
          <div v-else class="modal-box w-11/12 max-w-lg bg-base-100">
            <div class="card shadow-xl">
              <div class="card-body items-center text-center space-y-6">
                <div class="w-20 h-20 rounded-full bg-warning/20 flex items-center justify-center animate-pulse">
                  <ShieldAlert class="w-10 h-10 text-warning" />
                </div>
                <div class="space-y-2">
                  <h2 class="text-2xl font-bold text-error">Access Denied</h2>
                  <p class="text-base-content/80">You don't have permission to create new entries.</p>
                  <p class="text-base-content/60 text-sm">Please contact your system administrator for access.</p>
                </div>
                <button
                  @click="isOpen = false"
                  class="btn btn-ghost btn-sm hover:bg-base-200"
                >
                  Close
                </button>
              </div>
            </div>
          </div>
        </Transition>
      </div>
    </div>
  </Transition>
</template>

# Script Section
<script setup lang="ts">
import { ref, computed } from 'vue';
import { useMessage } from 'naive-ui';
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
  Check
} from 'lucide-vue-next';

const message = useMessage();
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
  if (isSubmitting.value) return;

  try {
    isSubmitting.value = true;
    formErrors.value = {};

    const response = await axios.post(props.endpoint, {
      model: props.model,
      data: availableFields.value,
      permission: props.permission,
    });

    message.success(response.data.message);
    emit('onCreate');
    isOpen.value = false;
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
        // Handle object-style errors
        Object.entries(errors).forEach(([key, value]) => {
          if (Array.isArray(value) && value.length > 0) {
            formErrors.value[key] = value[0];
          }
        });
      }

      // Display error messages using notification system
      Object.values(formErrors.value).forEach((errorMsg: string) => {
        message.error(errorMsg);
      });
    } else if (error.message) {
      // Handle generic error
      message.error('An error occurred while creating the entry. Please try again.');
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
  scrollbar-color: hsl(var(--p) / 0.3) hsl(var(--b2) / 0.5);
}

.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: hsl(var(--b2) / 0.5);
  border-radius: 8px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: hsl(var(--p) / 0.3);
  border-radius: 8px;
  border: 2px solid transparent;
  transition: background-color 0.2s ease;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background-color: hsl(var(--p) / 0.5);
}

/* Smooth Section Transitions */
.section-enter-active,
.section-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.section-enter-from,
.section-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}

/* Card Hover Effects */
.card {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.card:hover {
  transform: translateY(-2px);
}

/* Modal Transitions */
.modal-enter-active,
.modal-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
  transform: scale(0.95);
}

/* Button Animation */
.btn {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn:active {
  transform: scale(0.95);
}
</style>
