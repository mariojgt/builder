<template>
    <!-- The button to open modal -->
    <label
      :for="'edit-data-' + props.id"
      class="btn btn-info modal-button group transition-all duration-300 hover:scale-105"
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="h-6 w-6 group-hover:rotate-6 transition-transform"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        stroke-width="2"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
        />
      </svg>
    </label>

    <!-- Modal Checkbox -->
    <input
      type="checkbox"
      :id="'edit-data-' + props.id"
      class="modal-toggle"
    />

    <!-- Modal Container -->
    <div class="modal">
      <!-- Edit Form Modal -->
      <div
        v-if="canEdit"
        class="modal-box w-11/12 max-w-5xl
               border border-primary
               shadow-lg shadow-primary
               text-neutral-content
               relative"
      >
        <!-- Modal Header -->
        <div class="modal-header flex justify-between items-center mb-4">
          <h3 class="font-bold text-lg flex items-center space-x-2">
            <span>Edit Item #{{ props.id }}</span>
          </h3>

          <label
            :for="'edit-data-' + props.id"
            class="btn btn-sm btn-circle absolute right-2 top-2 hover:bg-base-200"
          >
            ✕
          </label>
        </div>

        <!-- Sections Container -->
        <div class="w-full mt-2">
          <!-- Sectioned Fields -->
          <div
            v-if="filterSections.sectionsWithFields.length > 0"
            class="w-full rounded-lg p-2 space-y-2"
          >
            <Disclosure
              v-for="(item, index) in filterSections.sectionsWithFields"
              :key="index"
              as="div"
              class="mt-2"
              v-slot="{ open }"
              :defaultOpen="true"
            >
              <DisclosureButton
                class="btn btn-primary w-full flex justify-between items-center"
              >
                <span>{{ item.section }}</span>
                <svg
                  v-if="open"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke-width="1.5"
                  stroke="currentColor"
                  class="w-6 h-6 transition-transform"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M11.25 4.5l7.5 7.5-7.5 7.5m-6-15l7.5 7.5-7.5 7.5"
                  />
                </svg>
                <svg
                  v-else
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke-width="1.5"
                  stroke="currentColor"
                  class="w-6 h-6 transition-transform"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M19.5 5.25l-7.5 7.5-7.5-7.5m15 6l-7.5 7.5-7.5-7.5"
                  />
                </svg>
              </DisclosureButton>

              <DisclosurePanel
                class="px-4 pt-4 pb-2 text-sm text-gray-500 transition-all duration-300"
              >
                <form-builder
                  :columns="item.fields"
                  @onFormUpdate="onFormUpdate"
                  :editMode="'true'"
                  :modelValue="props.modelValue"
                />
              </DisclosurePanel>
            </Disclosure>
          </div>

          <!-- Standard Fields -->
          <div
            v-if="filterSections.fields.length > 0"
            class="w-full bg-base-300 border border-secondary rounded-3xl text-neutral-content p-6 mt-4"
          >
            <form-builder
              :columns="filterSections.fields"
              @onFormUpdate="onFormUpdate"
              :editMode="'true'"
              :modelValue="props.modelValue"
            />
          </div>
        </div>

        <!-- Modal Actions -->
        <div class="flex justify-end gap-4 mt-5">
          <label
            :for="'edit-data-' + props.id"
            class="btn btn-primary font-bold text-lg text-white hover:bg-primary-focus transition-colors"
          >
            Close
          </label>
          <label
            :for="'edit-data-' + props.id"
            class="btn btn-secondary font-bold text-lg text-white hover:bg-secondary-focus transition-colors"
            @click="editData"
          >
            Submit
          </label>
        </div>
      </div>

      <!-- No Edit Permission Message -->
      <div
        v-else
        class="modal-box w-11/12 max-w-5xl"
      >
        <div class="card w-full bg-base-100 shadow-xl">
          <figure class="px-10 pt-10 animate-pulse">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-12 w-12 text-warning rounded-xl"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="2"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"
              />
            </svg>
          </figure>
          <div class="card-body items-center text-center">
            <h2 class="card-title text-error">Edit Method Disabled</h2>
            <p class="text-base-content">You do not have permission to edit this item.</p>
            <p class="text-base-content">Please contact your administrator for access.</p>
          </div>
        </div>
      </div>
    </div>
  </template>

  <script setup lang="ts">
  import {
      Disclosure,
      DisclosureButton,
      DisclosurePanel,
  } from '@headlessui/vue';
  import { computed } from "vue";
  import axios from "axios";
  import { useMessage } from "naive-ui";

  import FormBuilder from "../formHelpers/formbuilder.vue";

  const message = useMessage();

  const props = defineProps({
      columns: {
          type: Array,
          default: () => [],
      },
      model: {
          type: String,
          default: () => [],
      },
      endpoint: {
          type: String,
          default: () => [],
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

  const emit = defineEmits(["onEdit"]);

  // Check if editing is possible
  const canEdit = computed(() => {
      return props.columns.some(column => column.canEdit);
  });

  // Filter sections for organized form display
  const filterSections = computed(() => {
      const columnsWithSections = props.columns.filter(column => column.section);
      const columnsWithoutSections = props.columns.filter(column => !column.section);

      const sections = [...new Set(columnsWithSections.map(column => column.section))];

      const sectionsWithFields = sections.map(section => ({
          section,
          fields: columnsWithSections.filter(column => column.section === section)
      }));

      return {
          sections,
          fields: columnsWithoutSections,
          sectionsWithFields
      };
  });

  // Reactive fields for editing
  let avaliableFields = $ref(props.columns.filter(column => column.canEdit));

  // Update form fields
  const onFormUpdate = (formData) => {
      if (avaliableFields.length > 0) {
          formData.forEach(formDataValue => {
              const index = avaliableFields.findIndex(value => value.key === formDataValue.key);
              if (index !== -1) {
                  avaliableFields[index] = formDataValue;
              }
          });
      } else {
          avaliableFields = formData;
      }
  };

  // Submit edit changes
  const editData = async () => {
      try {
          const response = await axios.post(props.endpoint, {
              model: props.model,
              id: props.id,
              data: avaliableFields,
              permission: props.permission,
          });

          message.success(response.data.message);
          emit("onEdit");

          // Programmatically close the modal
          const modalCheckbox = document.getElementById(`edit-data-${props.id}`) as HTMLInputElement;
          if (modalCheckbox) {
              modalCheckbox.checked = false;
          }
      } catch (error: any) {
          const errors = error.response?.data?.errors || {};
          Object.values(errors).forEach((errorMsg: any) => {
              message.error(errorMsg[0]);
          });
      }
  };
  </script>
