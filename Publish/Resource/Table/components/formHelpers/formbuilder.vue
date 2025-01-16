<template>
    <div>
      <div v-for="(item, index) in avaliableFields" :key="index">
        <div v-if="item.type == 'text'">
          <input-field
            type="text"
            v-model="avaliableFields[index].value"
            :label="item.label"
            @keyup="textFieldKeyup($event.target.value, item.type, item.key)"
          />
        </div>
        <div v-else-if="item.type == 'password'">
          <input-password
            v-model="avaliableFields[index].value"
            :label="item.label"
          />
        </div>
        <div v-else-if="item.type == 'email'">
          <input-field
            type="email"
            v-model="avaliableFields[index].value"
            :label="item.label"
          />
        </div>
        <div v-else-if="item.type == 'date'">
          <input-field
            type="date"
            v-model="avaliableFields[index].value"
            :label="item.label"
          />
        </div>
        <div v-else-if="item.type == 'timestamp'">
          <Timestamp
                :label="item.label"
                :name="item.key"
                id="scheduled_at"
                v-model="avaliableFields[index].value"
                placeholder="Select date and time"
                required
                min="2024-01-16T00:00"
                max="2025-12-31T23:59"
            />
        </div>
        <div v-else-if="item.type == 'slug'">
          <input-field
            type="text"
            v-model="avaliableFields[index].value"
            :label="item.label"
          />
        </div>
        <div v-else-if="item.type == 'media'">
          <Image
            label="image"
            placeholder="search"
            v-model="avaliableFields[index].value"
            :loadData="avaliableFields[index].value"
            :endpoint="item.endpoint"
          />
        </div>
        <div v-else-if="item.type == 'number'">
          <input-field
            type="number"
            v-model="avaliableFields[index].value"
            :label="item.label"
            @keyup="textFieldKeyup($event.target.value, item.type, item.key)"
          />
        </div>
        <div v-else-if="item.type == 'model_search'">
          <TextMultipleSelector
            :label="item.label"
            placeholder="search"
            :model="item.model"
            :columns="item.columns"
            :singleMode="item.singleSearch"
            v-model="avaliableFields[index].value"
            :loadData="avaliableFields[index].value"
            :endpoint="item.endpoint"
            :displayKey="item.displayKey"
          />
        </div>
        <div v-else-if="item.type == 'pivot_model'">
          <TextMultipleSelector
            :label="item.label"
            placeholder="search"
            :model="item.model"
            :columns="item.columns"
            :singleMode="item.singleSearch"
            v-model="avaliableFields[index].value"
            :loadData="avaliableFields[index].value"
            :endpoint="item.endpoint"
            :displayKey="item.displayKey"
          />
        </div>
        <div v-else-if="item.type == 'editor'">
            <editor
                label="Content"
                name="content"
                id="content-editor"
                placeholder="Start typing..."
                v-model="avaliableFields[index].value"
                required
                :minLength="10"
                :maxLength="1000"
            />
        </div>
        <div v-else-if="item.type == 'Toggle'">
          <Toggle
            v-model="avaliableFields[index].value"
            :label="item.label"
          />
        </div>
        <div v-else-if="item.type == 'boolean'">
          <Toggle
            v-model="avaliableFields[index].value"
            :label="item.label"
          />
        </div>
        <div v-else-if="item.type == 'chips'">
          <Chips
            v-model="avaliableFields[index].value"
            :label="item.label"
          />
        </div>
        <div v-else-if="item.type == 'icon'">
          <label class="form-control mt-1">
            <div class="label">
              <span class="label-text">{{ item.label }}</span>
            </div>
            <textarea
              class="textarea textarea-primary h-24"
              @keyup="textFieldKeyup($event.target.value, item.type, item.key)"
              v-model="avaliableFields[index].value"
              placeholder="Bio"
            ></textarea>
          </label>
          <div class="flex justify-center bg-base-100 rounded mt-5">
            <div class="flex p-10 w-52" v-html="avaliableFields[index].value"></div>
          </div>
        </div>
        <div v-else-if="item.type == 'select'">
          <select-input
            v-model="avaliableFields[index].value"
            :label="item.label"
            :options="item.select_options"
          />
        </div>
      </div>
    </div>
  </template>

  <script setup>
  import { watch, onMounted } from "vue";
  import {
    formatDate,
    formatTimestamp,
    makeString
  } from "./formHelper.js";

  import {
    InputField,
    InputPassword,
    SelectInput,
    TextMultipleSelector,
    Image,
    Toggle,
    Chips,
    Editor,
    Timestamp
  } from "@mariojgt/masterui/packages/index";

  // Props definition
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
  });

  // Emit definition
  const emit = defineEmits(["onFormUpdate"]);

  // Reactive fields using $ref
  let avaliableFields = $ref([]);

  // Field creation function
  const createFields = () => {
    // Reset fields
    avaliableFields = [];

    // Loop through columns
    props.columns.forEach(value => {
      const isCreateMode = props.editMode === "false";
      const isEditMode = props.editMode === "true";

      // Determine if field should be included
      const shouldInclude = (isCreateMode && value.canCreate) ||
                            (isEditMode && value.canEdit);

      if (shouldInclude) {
        // Determine initial value
        let fieldValue = "";

        if (isCreateMode) {
          // Default values for specific types
          if (value.type === "Toggle" || value.type === "boolean") {
            fieldValue = false;
          }
        } else if (isEditMode) {
          // Determine value for edit mode
          fieldValue = determineFieldValue(value, props.modelValue);
        }

        // Determine options
        const options = value?.options?.options ||
                        value?.options;

        // Create field object
        const field = {
          key: value.key,
          label: value.label,
          type: value.type,
          nullable: value?.nullable,
          unique: value?.unique,
          endpoint: value?.endpoint,
          displayKey: value?.displayKey,
          columns: value?.columns,
          model: value?.model,
          singleSearch: value?.singleSearch,
          relation: value?.relation,
          value: fieldValue,
          select_options: options,
        };

        avaliableFields.push(field);
      }
    });

    // Emit form update for edit mode
    if (props.editMode === "true") {
      emit("onFormUpdate", avaliableFields);
    }
  };

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

  // Debounce variable
  let debounce = $ref(null);

  // Watch for changes in available fields
  watch(
    () => avaliableFields,
    () => {
      // Clear existing debounce
      clearTimeout(debounce);

      // Set new debounce
      debounce = setTimeout(() => {
        emit("onFormUpdate", avaliableFields);
      }, 500);
    },
    { deep: true }
  );

  // Text field keyup handler (slug generation)
  const textFieldKeyup = (value, type, fieldName) => {
    if (fieldName === 'name' || fieldName === 'title') {
      // Find slug field index
      const slugFieldIndex = avaliableFields.findIndex(
        item => item.key === 'slug'
      );

      // Update slug if field exists
      if (slugFieldIndex !== -1) {
        avaliableFields[slugFieldIndex].value = value
          .toLowerCase()
          .trim()
          .replace(/[^\w\s-]/g, '')
          .replace(/[\s_-]+/g, '-')
          .replace(/^-+|-+$/g, '');
      }
    }
  };

  // Initialize fields on mount
  onMounted(createFields);
  </script>
