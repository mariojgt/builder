<template>
    <div>
        <div v-for="(item, index) in fields" :key="index">
            <!-- Text Input -->
            <div v-if="item.type == 'text'">
                <input-field type="text" v-model="fields[index].value" :label="item.label" :error="item.error"
                    @keyup="textFieldKeyup($event.target.value, item.type, item.key)" />
            </div>

            <!-- Password Input -->
            <div v-else-if="item.type == 'password'">
                <input-password v-model="fields[index].value" :label="item.label" :error="item.error" />
            </div>

            <!-- Email Input -->
            <div v-else-if="item.type == 'email'">
                <input-field type="email" v-model="fields[index].value" :label="item.label" :error="item.error" />
            </div>

            <!-- Date Input -->
            <div v-else-if="item.type == 'date'">
                <input-field type="date" v-model="fields[index].value" :label="item.label" :error="item.error" />
            </div>

            <!-- Timestamp Input -->
            <div v-else-if="item.type == 'timestamp'">
                <Timestamp :label="item.label" :name="item.key" :id="item.key" v-model="fields[index].value"
                    placeholder="Select date and time" required :error="item.error" min="2024-01-16T00:00"
                    max="2025-12-31T23:59" />
            </div>

            <!-- Slug Input -->
            <div v-else-if="item.type == 'slug'">
                <input-field type="text" v-model="fields[index].value" :label="item.label" :error="item.error" />
            </div>

            <!-- Media Upload -->
            <div v-else-if="item.type == 'media'">
                <Image label="image" placeholder="search" v-model="fields[index].value" :loadData="fields[index].value"
                    :endpoint="item.endpoint" :error="item.error" />
            </div>

            <!-- Number Input -->
            <div v-else-if="item.type == 'number'">
                <input-field type="number" v-model="fields[index].value" :label="item.label"
                    @keyup="textFieldKeyup($event.target.value, item.type, item.key)" :error="item.error" />
            </div>

            <!-- Model Search -->
            <div v-else-if="item.type == 'model_search'">
                <TextMultipleSelector :label="item.label" placeholder="search" :model="item.model"
                    :columns="item.columns" :singleMode="item.singleSearch" v-model="fields[index].value"
                    :loadData="fields[index].value" :endpoint="item.endpoint" :displayKey="item.displayKey"
                    :error="item.error" />
            </div>

            <!-- Pivot Model -->
            <div v-else-if="item.type == 'pivot_model'">
                <TextMultipleSelector :label="item.label" placeholder="search" :model="item.model"
                    :columns="item.columns" :singleMode="item.singleSearch" v-model="fields[index].value"
                    :loadData="fields[index].value" :endpoint="item.endpoint" :displayKey="item.displayKey"
                    :error="item.error" />
            </div>

            <!-- Editor -->
            <div v-else-if="item.type == 'editor'">
                <editor :label="item.label" :name="item.key" :id="item.key + '-editor'" placeholder="Start typing..."
                    v-model="fields[index].value" required :minLength="10" :maxLength="1000" :error="item.error" />
            </div>

            <!-- Toggle -->
            <div v-else-if="item.type == 'Toggle'">
                <Toggle v-model="fields[index].value" :label="item.label" :error="item.error" />
            </div>

            <!-- Boolean Toggle -->
            <div v-else-if="item.type == 'boolean'">
                <Toggle v-model="fields[index].value" :label="item.label" :error="item.error" />
            </div>

            <!-- Chips Input -->
            <div v-else-if="item.type == 'chips'">
                <Chips v-model="fields[index].value" :label="item.label" :error="item.error" />
            </div>

            <!-- Icon Input -->
            <div v-else-if="item.type == 'icon'">
                <label class="form-control mt-1">
                    <div class="label">
                        <span class="label-text">{{ item.label }}</span>
                    </div>
                    <textarea class="textarea textarea-primary h-24"
                        @keyup="textFieldKeyup($event.target.value, item.type, item.key)" v-model="fields[index].value"
                        placeholder="Bio" :error="item.error"></textarea>
                </label>
                <div class="flex justify-center bg-base-100 rounded mt-5">
                    <div class="flex p-10 w-52" v-html="fields[index].value"></div>
                </div>
            </div>
            <!-- Select Input -->
            <div v-else-if="item.type == 'select'">
                <select-input v-model="fields[index].value" :label="item.label" :options="item.select_options"
                    :error="item.error" />
            </div>
            <div v-if="item.error" class="text-error text-sm mt-1">
                {{ item.error }}
            </div>
        </div>
    </div>
</template>

<script setup>
import { watch } from "vue";
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

const props = defineProps({
    fields: {
        type: Array,
        required: true
    }
});

const emit = defineEmits(["update:fields"]);

// Watch for changes in fields
watch(
    () => props.fields,
    (newValue) => {
        emit("update:fields", newValue);
    },
    { deep: true }
);

// Text field keyup handler (slug generation)
const textFieldKeyup = (value, type, fieldName) => {
    if (fieldName === 'name' || fieldName === 'title') {
        const slugFieldIndex = props.fields.findIndex(
            item => item.key === 'slug'
        );
        if (slugFieldIndex !== -1) {
            props.fields[slugFieldIndex].value = value
                .toLowerCase()
                .trim()
                .replace(/[^\w\s-]/g, '')
                .replace(/[\s_-]+/g, '-')
                .replace(/^-+|-+$/g, '');
        }
    }
};
</script>

<style scoped>
.form-control {
    @apply space-y-2;
}

.textarea-primary {
    @apply focus:outline-none focus:ring-2 focus:ring-primary;
}
</style>
