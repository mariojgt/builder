<template>
    <!-- The button to open modal -->
    <label :for="'edit-data-' + props.id" class="btn btn-info modal-button">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
            stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
        </svg>
    </label>

    <!-- Put this part before </body> tag -->
    <input type="checkbox" :id="'edit-data-' + props.id" class="modal-toggle" />
    <div class="modal text-center">

        <!-- Build the form -->
        <div class="modal-box w-11/12 max-w-5xl" v-if="canEdit">
            <h3 class="font-bold text-lg">Edit</h3>

            <!-- Handle sections if avalible -->
            <div class="w-full">
                <div class="w-full rounded-2xl bg-white p-2">
                    <Disclosure as="div" class="mt-2" v-slot="{ open }"
                        v-for="(item, index) in filterSections.sectionsWithFields" :key="index">
                        <DisclosureButton
                            class="flex w-full justify-between rounded-lg bg-purple-100 px-4 py-2 text-left text-sm font-medium text-purple-900 hover:bg-purple-200 focus:outline-none focus-visible:ring focus-visible:ring-purple-500 focus-visible:ring-opacity-75">
                            <span>{{ item.section }}</span>
                        </DisclosureButton>
                        <DisclosurePanel class="px-4 pt-4 pb-2 text-sm text-gray-500">
                            <form-builder :columns="item.fields" @onFormUpdate="onFormUpdate" :editMode="'true'"
                                :modelValue="props.modelValue" />
                        </DisclosurePanel>
                    </Disclosure>
                </div>
            </div>
            <!-- Handle normal inputs -->
            <form-builder :columns="filterSections.fields" @onFormUpdate="onFormUpdate" :editMode="'true'"
                :modelValue="props.modelValue" />

            <div class="modal-action">
                <label :for="'edit-data-' + props.id" class="btn btn-error">Close</label>
                <label :for="'edit-data-' + props.id" class="btn btn-success" @click="editData">Edit</label>
            </div>
        </div>
        <!-- Display a error message in case the form has no create permission -->
        <div class="modal-box w-11/12 max-w-5xl" v-else>
            <div class="card w-full bg-base-100 shadow-xl">
                <figure class="px-10 pt-10">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 rounded-xl" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                </figure>
                <div class="card-body items-center text-center">
                    <h2 class="card-title">The edit method has been disable</h2>
                    <p>Try to contact the administrator </p>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup >
import {
    Disclosure,
    DisclosureButton,
    DisclosurePanel,
} from '@headlessui/vue';
// Import vue watch
import { watch, computed } from "vue";
// Import axios
import axios from "axios";
// Import the form builder
import FormBuilder from "../formHelpers/formbuilder.vue";

import { useMessage } from "naive-ui";

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
        type: String,
        default: "0",
    },
    modelValue: {
        type: Array,
        default: () => [],
    },
    permission: {
        type: String,
        default: null,
    },
});

// Create a computed property to check if there is any collumns that can be created if not so we can dispaly a message to the user
const canEdit = computed(() => {
    // Filter and check if there is any collumns that can be created
    return props.columns.filter(column => column.canEdit).length > 0;
});

// Create a computed property to filter the sections and the fields inside them
const filterSections = computed(() => {
    const collumnsWithSections = props.columns.filter(column => column.section);
    const collumnsWithoutSections = props.columns.filter(column => !column.section);
    // Now we need to get the sections and the fields inside them and return them as an object
    const sections = collumnsWithSections.map(column => column.section);
    const uniqueSections = [...new Set(sections)];
    const sectionsWithFields = [];
    uniqueSections.forEach(section => {
        const fields = collumnsWithSections.filter(column => column.section == section);
        sectionsWithFields.push({
            section: section,
            fields: fields
        });
    });
    // Return the sections and the fields inside them
    return {
        sections: uniqueSections,
        fields: collumnsWithoutSections,
        sectionsWithFields: sectionsWithFields
    };
});

// AvaliableFields is what we will send to the server but first we need to get the data from the form
let avaliableFields = $ref(props.columns.filter(column => column.canEdit));
// When the form is updated get the data and sync with the avaliable fields
const onFormUpdate = async (formData) => {
    if (avaliableFields.length > 0) {
        for (const [formDataIndex, formDataValue] of Object.entries(formData)) {
            for (const [key, value] of Object.entries(avaliableFields)) {
                if (formDataValue.key == value.key) {
                    avaliableFields[key] = formDataValue;
                }
            }
        }
    } else {
        avaliableFields = formData;
    }
};

const emit = defineEmits(["onEdit"]);

const editData = async () => {
    axios
        .post(props.endpoint, {
            model: props.model, // The model name encrypted
            id: props.id, // The model name id
            data: avaliableFields, // Item we want to delete
            permission: props.permission, // Permission
        })
        .then(function (response) {
            message.success(response.data.message);
            emit("onEdit");
        })
        .catch(function (error) {
            for (const [key, value] of Object.entries(error.response.data.errors)) {
                message.error(value[0]);
            }
        });
};
</script>


