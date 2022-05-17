<template>
    <!-- The button to open modal -->
    <label for="my-modal-5" class="btn btn-primary modal-button">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
            stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
        </svg>
    </label>

    <!-- Put this part before </body> tag -->
    <input type="checkbox" id="my-modal-5" class="modal-toggle" />
    <div class="modal text-center">
        <!-- Build the form -->
        <div class="modal-box w-11/12 max-w-5xl" v-if="canCreate">
            <h3 class="font-bold text-lg">New</h3>
            <form-builder :columns="props.columns" @onFormUpdate="onFormUpdate" />
            <div class="modal-action">
                <label for="my-modal-5" class="btn btn-error">Close</label>
                <label for="my-modal-5" class="btn btn-success" @click="createNew">Create</label>
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
                    <h2 class="card-title">The create method has been disable</h2>
                    <p>Try to contact the administrator </p>
                </div>
                <div class="modal-action">
                    <label for="my-modal-5" class="btn btn-error">Close</label>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup >
// Import vue watch
import { watch, computed } from "vue";
// Import axios
import axios from "axios";
// Import the form builder
import FormBuilder from "../formHelpers/formbuilder.vue";
// Import naive ui messages
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
    permission: {
        type: String,
        default: null,
    },
});

// Create a computed property to check if there is any collumns that can be created if not so we can dispaly a message to the user
const canCreate = computed(() => {
    // Filter and check if there is any collumns that can be created
    return props.columns.filter(column => column.canCreate).length > 0;
});


let avaliableFields = $ref([]);
// When the form is updated get the data and sync with the avaliable fields
const onFormUpdate = async (formData) => {
    avaliableFields = formData;
};

const emit = defineEmits(["onCreate"]);

const createNew = async () => {
    axios
        .post(props.endpoint, {
            model: props.model, // The model name encrypted
            data: avaliableFields, // Item we want to delete
            permission: props.permission, // Permission
        })
        .then(function (response) {
            message.success(response.data.message);
        })
        .catch(function (error) {
            for (const [key, value] of Object.entries(error.response.data.errors)) {
                message.error(value[0]);
            }
        });
    emit("onCreate");
};
</script>


