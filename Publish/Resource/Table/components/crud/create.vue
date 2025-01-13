<template>
    <!-- Create Button -->
    <label
      for="my-modal-5"
      class="btn btn-primary modal-button flex items-center space-x-2 group"
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="h-6 w-6 transition-transform group-hover:rotate-45"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        stroke-width="2"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M12 4v16m8-8H4"
        />
      </svg>
      <span class="hidden md:inline">Create New</span>
    </label>

    <!-- Modal Structure -->
    <input type="checkbox" id="my-modal-5" class="modal-toggle" />
    <div class="modal text-left">
      <!-- Modal Box for Create Permissions -->
      <div
        v-if="canCreate"
        class="modal-box w-11/12 max-w-5xl border border-primary shadow-primary shadow-2xl relative"
      >
        <!-- Modal Header -->
        <div class="modal-header flex justify-between items-center mb-4">
          <h3 class="font-bold text-lg text-base-content">Create New Entry</h3>
          <label
            for="my-modal-5"
            class="btn btn-sm btn-circle absolute right-2 top-2 hover:bg-base-200"
          >
            ✕
          </label>
        </div>

        <!-- Sections Container -->
        <div class="w-full space-y-4">
          <!-- Sectioned Fields -->
          <div
            v-if="filterSections.sectionsWithFields.length > 0"
            class="w-full rounded-lg"
          >
            <Disclosure
              v-for="(item, index) in filterSections.sectionsWithFields"
              :key="index"
              as="div"
              class="mb-2"
              v-slot="{ open }"
            >
              <DisclosureButton
                class="btn btn-ghost w-full justify-between hover:bg-base-200"
              >
                <span class="text-left">{{ item.section }}</span>
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-6 w-6 transition-transform"
                  :class="{ 'rotate-180': open }"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.5"
                    d="M19 9l-7 7-7-7"
                  />
                </svg>
              </DisclosureButton>
              <DisclosurePanel class="p-2 bg-base-100 rounded-b-lg">
                <form-builder
                  :columns="item.fields"
                  @onFormUpdate="onFormUpdate"
                />
              </DisclosurePanel>
            </Disclosure>
          </div>

          <!-- Standard Fields -->
          <div
            v-if="filterSections.fields.length > 0"
            class="w-full bg-base-300 border border-secondary rounded-3xl text-neutral-content p-6"
          >
            <form-builder
              :columns="filterSections.fields"
              @onFormUpdate="onFormUpdate"
            />
          </div>
        </div>

        <!-- Modal Actions -->
        <div class="modal-action flex justify-end gap-2 pt-3">
          <label
            for="my-modal-5"
            class="btn btn-secondary text-white"
          >
            Close
          </label>
          <button
            @click="createNew"
            class="btn btn-primary text-white flex items-center space-x-2"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-5 w-5"
              viewBox="0 0 20 20"
              fill="currentColor"
            >
              <path
                fill-rule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z"
                clip-rule="evenodd"
              />
            </svg>
            <span>Create</span>
          </button>
        </div>
      </div>

      <!-- No Create Permission Message -->
      <div
        v-else
        class="modal-box w-11/12 max-w-5xl"
      >
        <div class="card w-full bg-base-100 shadow-xl">
          <figure class="px-10 pt-10">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-12 w-12 text-warning"
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
            <h2 class="card-title text-error">Creation Disabled</h2>
            <p class="text-base-content">You do not have permission to create entries.</p>
            <p class="text-base-content">Please contact your administrator.</p>
          </div>
        </div>
      </div>
    </div>
  </template>

  <script setup>
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
      permission: {
          type: String,
          default: null,
      },
  });

  const emit = defineEmits(["onCreate"]);

  // Create ability check
  const canCreate = computed(() => {
      return props.columns.some(column => column.canCreate);
  });

  // Section filtering
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

  // Available fields for creation
  let avaliableFields = $ref(props.columns.filter(column => column.canCreate));

  // Form update handler
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

  // Create new entry
  const createNew = async () => {
      try {
          const response = await axios.post(props.endpoint, {
              model: props.model,
              data: avaliableFields,
              permission: props.permission,
          });

          message.success(response.data.message);
          emit("onCreate");

          // Programmatically close the modal
          document.getElementById('my-modal-5').checked = false;
      } catch (error) {
          const errors = error.response?.data?.errors || {};
          Object.values(errors).forEach(errorMsg => {
              message.error(errorMsg[0]);
          });
      }
  };
  </script>
