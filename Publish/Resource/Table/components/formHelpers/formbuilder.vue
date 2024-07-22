<template>
    <div v-for="(item, index) in avaliableFields" :key="index">
        <div v-if="item.type == 'text'">
            <input-field type="text" v-model="avaliableFields[index].value" :label="item.label"
                @keyup="textFieldKeyup($event.target.value, item.type, item.key)" />
        </div>
        <div v-else-if="item.type == 'password'">
            <input-password v-model="avaliableFields[index].value" :label="item.label" />
        </div>
        <div v-else-if="item.type == 'email'">
            <input-field type="email" v-model="avaliableFields[index].value" :label="item.label" />
        </div>
        <div v-else-if="item.type == 'date'">
            <input-field type="date" v-model="avaliableFields[index].value" :label="item.label" />
        </div>
        <div v-else-if="item.type == 'timestamp'">
            <input-field type="datetime-local" v-model="avaliableFields[index].value" :label="item.label" />
        </div>
        <div v-else-if="item.type == 'slug'">
            <input-field type="text" v-model="avaliableFields[index].value" :label="item.label" />
        </div>
        <div v-else-if="item.type == 'media'">
            <Image label="image" placeholder="search" v-model="avaliableFields[index].value"
                :loadData="avaliableFields[index].value" :endpoint="item.endpoint" />
        </div>
        <div v-else-if="item.type == 'number'">
            <input-field type="number" v-model="avaliableFields[index].value" :label="item.label"
                @keyup="textFieldKeyup($event.target.value, item.type, item.key)" />
        </div>
        <div v-else-if="item.type == 'model_search'">
            <TextMultipleSelector :label="item.label" placeholder="search" :model="item.model" :columns="item.columns"
                :singleMode="item.singleSearch" v-model="avaliableFields[index].value"
                :loadData="avaliableFields[index].value" :endpoint="item.endpoint" :displayKey="item.displayKey" />
        </div>
        <div v-else-if="item.type == 'pivot_model'">
            <TextMultipleSelector :label="item.label" placeholder="search" :model="item.model" :columns="item.columns"
                :singleMode="item.singleSearch" v-model="avaliableFields[index].value"
                :loadData="avaliableFields[index].value" :endpoint="item.endpoint" :displayKey="item.displayKey" />
        </div>
        <div v-else-if="item.type == 'Toggle'">
            <Toggle v-model="avaliableFields[index].value" :label="item.label" />
        </div>
        <div v-else-if="item.type == 'icon'">
            <label class="form-control mt-1">
                <div class="label">
                    <span class="label-text">{{ item.label }}</span>
                </div>
                <textarea class="textarea textarea-primary h-24" @keyup="textFieldKeyup($event.target.value, item.type, item.key)" v-model="avaliableFields[index].value"  placeholder="Bio"></textarea>
            </label>
            <div class="flex justify-center bg-base-100 rounded mt-5">
                <div class="flex p-10 w-52" v-html="avaliableFields[index].value"></div>
            </div>
        </div>
        <div v-else-if="item.type == 'select'">
            <select-input v-model="avaliableFields[index].value" :label="item.label" :options="item.select_options" />
        </div>
    </div>
</template>
<script setup >
// Import vue watch
import { watch, computed } from "vue";
// Import the javascrpt functions for formatting the data
import { formatDate, formatTimestamp, makeString } from "./formHelper.js";
// Import the from components
import {
    InputField,
    InputPassword,
    SelectInput,
    TextMultipleSelector,
    Image,
    Toggle
} from "@mariojgt/masterui/packages/index";

const props = defineProps({
    columns: {
        type: Array,
        default: () => [],
    },
    modelValue: {
        type: Object,
        default: () => [],
    },
    editMode: {
        type: String,
        default: "false",
    },
});
const emit = defineEmits(["onFormUpdate"]);
let avaliableFields = $ref([]);
// This fuction will loop the columns and create the fields
const createFields = () => {
    // Empty any fields
    avaliableFields = [];
    // Loop the table columns
    for (const [key, value] of Object.entries(props.columns)) {
        // Check if the form is in edit mode or create mode
        if (props.editMode == "false") {
            if (value.canCreate) {
                let FieldValue = "";
                if (value.type == "Toggle") {
                    FieldValue = false;
                }

                // Create mode
                avaliableFields.push({
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
                    value: FieldValue,
                    select_options: value?.select_options,
                });
            }
        } else {
            // Edit mode
            if (value.canEdit) {
                // Variable that will hold the final value after the cast
                let finalValue = null;
                // Switch the type of the value
                switch (value.type) {
                    case "date":
                        // Format to yyyy-mm-dd
                        finalValue = formatDate(props.modelValue[value.key]);
                        break;
                    case "timestamp":
                        finalValue = formatTimestamp(props.modelValue[value.key]);
                        break;
                    case "media":
                        finalValue = props.modelValue[value.key];
                        break;
                    case "model_search":
                        finalValue = props.modelValue[value.key];
                        break;
                    case "pivot_model":
                        finalValue = props.modelValue[value.key];
                        break;
                    default:
                        // Cast to string
                        finalValue = makeString(props.modelValue[value.key]);
                        break;
                }
                // Push the field formate with the type and the right values for the field
                avaliableFields.push({
                    key: value.key,
                    label: value.label,
                    type: value.type,
                    nullable: value?.nullable,
                    displayKey: value?.displayKey,
                    unique: value?.unique,
                    value: finalValue,
                    endpoint: value?.endpoint,
                    columns: value?.columns,
                    model: value?.model,
                    singleSearch: value?.singleSearch,
                    relation: value?.relation,
                    select_options: value?.select_options,
                });
            }
        }
    }
    if (props.editMode == "true") {
        emit("onFormUpdate", avaliableFields);
    }
};
// Call the fuction to build the fields
createFields();
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

// We goin to use this to setup field like the slug field
const textFieldKeyup = async (value, type, fieldName) => {
    if (fieldName == 'name' || fieldName == 'title') {
        // Find the slug field array index based in the key
        const slugFieldIndex = avaliableFields.findIndex((item) => item.key == 'slug');
        // Update the value of the slug field
        if (avaliableFields[slugFieldIndex]) {
            // Set the value of the slug field and slugify it
            avaliableFields[slugFieldIndex].value = value
                .toLowerCase()
                .trim()
                .replace(/[^\w\s-]/g, '')
                .replace(/[\s_-]+/g, '-')
                .replace(/^-+|-+$/g, '');
        }
    }
};
</script>
