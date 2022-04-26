<template>
  <div v-for="(item, index) in avaliableFields" :key="index">
    <div v-if="item.type == 'text'">
      <input-field
        type="text"
        v-model="avaliableFields[index].value"
        :label="item.label"
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
      <input-field
        type="datetime-local"
        v-model="avaliableFields[index].value"
        :label="item.label"
      />
    </div>
  </div>
</template>
<script setup >
// Import vue watch
import { watch } from "vue";
// Import the javascrpt functions for formatting the data
import { formatDate, formatTimestamp, makeString } from "./formHelper.js";

// Import the from components
import {
  InputField,
  InputPassword,
  Submit,
  LinkButton,
} from "@mariojgt/masterui/packages/index";

const props = defineProps({
  columns: {
    type: Array,
    default: () => [],
  },
  modelValue: {
    type: Array,
    default: () => [],
  },
  editMode: {
    type: String,
    default: "false",
  },
});

let avaliableFields = $ref([]);

// This fuction will loop the columns and create the fields
const createFields = () => {
  // Empty any fields
  avaliableFields = [];
  // Loop the table columns
  for (const [key, value] of Object.entries(props.columns)) {
    // Variable that will hold the final value after the cast
    let finalValue = null;
    // If can be edited we need to get the value from the model and convert it
    if (value.canEdit) {
      // Switch the type of the value
      switch (value.type) {
        case "date":
          // Format to yyyy-mm-dd
          finalValue = formatDate(props.modelValue[value.key]);
          break;
        case "timestamp":
          finalValue = formatTimestamp(props.modelValue[value.key]);
          break;
        default:
          // Cast to string
          finalValue = makeString(props.modelValue[value.key]);
          break;
      }
    }
    // Push the field formate with the type and the right values for the field
    avaliableFields.push({
      key: value.key,
      label: value.label,
      type: value.type,
      value: finalValue,
    });
  }
};

// Call the fuction to build the fields
createFields();

const emit = defineEmits(["onFormUpdate"]);

// Debounce
let debounce = $ref(null);

// Watch any change in the avaliable fields
watch(
  () => avaliableFields,
  () => {
    // Clear any existing debounce event
    clearTimeout(debounce);
    // Update and log the counts after 500 miliseconds
    debounce = setTimeout(function () {
      emit("onFormUpdate", avaliableFields);
    }, 500);
  },
  { deep: true }
);
</script>


