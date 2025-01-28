<template>
    <!-- Enhanced Edit Button -->
    <button
      @click="isOpen = true"
      class="btn btn-primary btn-sm gap-2 group relative overflow-hidden hover:shadow-lg transition-all duration-300"
    >
      <Pencil
        class="w-4 h-4 transform transition-all duration-300 group-hover:rotate-12"
      />
      <span class="hidden md:inline">Edit</span>
      <div class="absolute inset-0 bg-white/20 transform translate-y-full group-hover:translate-y-0 transition-transform duration-300"></div>
    </button>

    <!-- Enhanced Modal with improved transitions -->
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
            <div
              class="relative bg-base-100 rounded-none md:rounded-2xl shadow-2xl flex flex-col w-full min-h-screen md:min-h-[80vh] md:max-h-[90vh] md:w-11/12 max-w-7xl transform transition-all"
            >
              <!-- Loading Overlay with improved animation -->
              <div v-if="isSubmitting"
                   class="absolute inset-0 bg-base-100/70 backdrop-blur-sm flex items-center justify-center z-50">
                <div class="flex flex-col items-center gap-4">
                  <div class="loading loading-spinner loading-lg text-primary"></div>
                  <p class="text-sm font-medium text-base-content/70">Saving changes...</p>
                </div>
              </div>

              <!-- Enhanced Header -->
              <div class="sticky top-0 z-10 bg-base-200 px-6 py-4 border-b border-base-content/10">
                <div class="flex flex-col sm:flex-row justify-between gap-4">
                  <h2 class="text-lg font-semibold flex items-center gap-3 text-base-content">
                    <button
                      @click="isOpen = false"
                      class="btn btn-sm btn-circle btn-ghost hover:bg-base-300 hover:rotate-90 transition-all duration-200"
                    >
                      <XIcon class="w-5 h-5" />
                    </button>
                    <div class="flex items-center gap-2">
                      <HashIcon class="w-5 h-5 text-primary" />
                      <span>Edit Item {{ props.id }}</span>
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
                      @click="editData"
                      :disabled="isSubmitting"
                      class="btn btn-primary btn-sm gap-2 min-w-[120px]"
                    >
                      <SaveIcon class="w-4 h-4" :class="{ 'animate-spin': isSubmitting }" />
                      {{ isSubmitting ? 'Saving...' : 'Save Changes' }}
                    </button>
                  </div>
                </div>

                <!-- Enhanced Error Alert -->
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
                    <AlertTriangleIcon class="w-5 h-5 flex-shrink-0" />
                    <div>
                      <h3 class="font-bold">Please correct the following errors</h3>
                      <div class="text-xs opacity-75">Review the highlighted fields below</div>
                    </div>
                  </div>
                </TransitionGroup>
              </div>

              <!-- Enhanced Content Area -->
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
                                <FormBuilder
                                  :columns="item.fields"
                                  :errors="formErrors"
                                  @onFormUpdate="onFormUpdate"
                                  editMode="true"
                                  :modelValue="props.modelValue"
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
                            <FormBuilder
                              :columns="filterSections.fields"
                              :errors="formErrors"
                              @onFormUpdate="onFormUpdate"
                              editMode="true"
                              :modelValue="props.modelValue"
                            />
                          </div>
                        </div>
                      </TransitionGroup>
                    </div>

                    <!-- Enhanced Sidebar -->
                    <div class="lg:col-span-4 space-y-4">
                      <!-- Meta Info Card -->
                      <div class="card bg-base-200 shadow-lg hover:shadow-xl transition-all duration-200">
                        <div class="card-body">
                          <h3 class="text-sm font-medium text-base-content flex items-center gap-2">
                            <InfoIcon class="w-4 h-4 text-primary" />
                            Information
                          </h3>
                          <div class="mt-3 space-y-3 divide-y divide-base-content/10">
                            <div class="flex justify-between items-center py-2">
                              <span class="text-sm text-base-content/70">ID:</span>
                              <span class="font-medium text-base-content">{{ props.id }}</span>
                            </div>
                            <div class="flex justify-between items-center py-2">
                              <span class="text-sm text-base-content/70">Last Updated:</span>
                              <span class="font-medium text-base-content flex items-center gap-2">
                                <CalendarIcon class="w-4 h-4 text-primary" />
                                {{ new Date(props.modelValue?.updated_at).toLocaleDateString() }}
                              </span>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Enhanced Quick Navigation -->
                      <div class="card bg-base-200 shadow-lg hover:shadow-xl transition-all duration-200">
                        <div class="card-body">
                          <h3 class="text-sm font-medium text-base-content flex items-center gap-2">
                            <NavigationIcon class="w-4 h-4 text-primary" />
                            Quick Navigation
                          </h3>
                          <div class="mt-3 space-y-1">
                            <button
                              v-for="field in availableFields"
                              :key="field.key"
                              @click="scrollToField(field.key)"
                              class="w-full text-left px-3 py-2 text-sm rounded-btn transition-all duration-200"
                              :class="[
                                formErrors[field.key]
                                  ? 'text-error bg-error/10 hover:bg-error/20'
                                  : 'text-base-content hover:bg-base-100'
                              ]"
                            >
                              <div class="flex items-center gap-2">
                                <span v-if="formErrors[field.key]" class="text-error">●</span>
                                {{ field.label }}
                              </div>
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </Transition>
  </template>

  <script setup lang="ts">
  import { ref, computed } from 'vue';
  import axios from 'axios';
  import { startWindToast } from "@mariojgt/wind-notify/packages/index.js";
  import FormBuilder from '../formHelpers/FormBuilder.vue';
  import {
    X as XIcon,
    Pencil,
    Save as SaveIcon,
    AlertTriangle as AlertTriangleIcon,
    Info as InfoIcon,
    Calendar as CalendarIcon,
    Hash as HashIcon,
    Navigation as NavigationIcon,
    Folder
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

  const editData = async () => {
    if (isSubmitting.value) return;

    try {
      isSubmitting.value = true;
      formErrors.value = {};

      const response = await axios.post(props.endpoint, {
        model: props.model,
        id: props.id,
        data: availableFields.value,
        permission: props.permission,
      });

      startWindToast('success', response.data.message, 'success');
      emit('onEdit');
      isOpen.value = false;
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

  const scrollToField = (key: string) => {
    const element = document.querySelector(`[data-field-key="${key}"]`);
    if (element) {
      // Find the closest collapse parent if it exists
      const collapseContent = element.closest('.collapse-content');

      // If the field is inside a collapsed section, expand it first
      if (collapseContent) {
        const collapseInput = collapseContent.parentElement?.querySelector('input[type="checkbox"]');
        if (collapseInput && !collapseInput.checked) {
          collapseInput.checked = true;
        }
      }

      // Wait for any collapse animations to complete
      setTimeout(() => {
        element.scrollIntoView({
          behavior: 'smooth',
          block: 'center',
          inline: 'nearest'
        });

        // Add a highlight animation
        element.classList.add('highlight-field');
        setTimeout(() => {
          element.classList.remove('highlight-field');
        }, 2000);
      }, 300);
    }
  };
  </script>

  <style scoped>
  /* Enhanced Field Highlight Animation */
  @keyframes highlightPulse {
    0% {
      box-shadow: 0 0 0 0 hsl(var(--p) / 0.3);
      background-color: hsl(var(--p) / 0.1);
    }
    50% {
      box-shadow: 0 0 0 8px hsl(var(--p) / 0);
      background-color: hsl(var(--p) / 0.15);
    }
    100% {
      box-shadow: 0 0 0 0 hsl(var(--p) / 0);
      background-color: hsl(var(--p) / 0);
    }
  }

  /* Enhanced Custom Scrollbar with DaisyUI colors */
  .custom-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: hsl(var(--p) / 0.3) hsl(var(--b3) / 0.5);
  }

  .custom-scrollbar::-webkit-scrollbar {
    width: 6px;
  }

  .custom-scrollbar::-webkit-scrollbar-track {
    background: hsl(var(--b3) / 0.5);
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

  /* Enhanced Highlight animation class */
  .highlight-field {
    animation: highlightPulse 2s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 0.5rem;
  }

  /* Enhanced Card Transitions */
  .card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }

  .card:hover {
    transform: translateY(-2px);
  }

  /* Enhanced Modal Transitions */
  .modal-enter-active,
  .modal-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }

  .modal-enter-from,
  .modal-leave-to {
    opacity: 0;
    transform: scale(0.95);
  }

  /* Enhanced Button Animation */
  .btn {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }

  .btn:active {
    transform: scale(0.95);
  }

  /* Enhanced Collapse Transitions */
  .collapse-content {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }
  </style>
