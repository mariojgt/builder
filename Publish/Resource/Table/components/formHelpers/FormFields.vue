<template>
  <div class="space-y-6">
    <TransitionGroup
      enter-active-class="transition-all duration-500 ease-out"
      enter-from-class="opacity-0 translate-y-6 scale-95"
      enter-to-class="opacity-100 translate-y-0 scale-100"
      leave-active-class="transition-all duration-300 ease-in"
      leave-from-class="opacity-100 translate-y-0 scale-100"
      leave-to-class="opacity-0 translate-y-6 scale-95"
    >
      <div
        v-for="(item, index) in fields"
        :key="item.key"
        class="form-field-container group"
        :style="{ animationDelay: `${index * 100}ms` }"
        :data-field-key="item.key"
      >
        <!-- Enhanced Text Input -->
        <div v-if="item.type === 'text'" class="form-control">
          <div class="relative">
            <!-- Enhanced Label -->
            <label class="label group/label cursor-pointer">
              <span class="label-text font-semibold text-base-content flex items-center gap-2 group-hover/label:text-primary transition-colors duration-200">
                <Type class="w-4 h-4 opacity-70" />
                {{ item.label }}
                <span v-if="item.required" class="text-error text-sm">*</span>
              </span>
              <span v-if="item.description" class="label-text-alt text-xs text-base-content/60">
                {{ item.description }}
              </span>
            </label>

            <!-- Enhanced Input Field -->
            <div class="relative">
              <InputField
                type="text"
                v-model="fields[index].value"
                :label="null"
                :error="item.error"
                :class="[
                  'input-enhanced transition-all duration-300',
                  item.error ? 'input-error animate-shake' : 'focus:input-primary'
                ]"
                :placeholder="`Enter ${item.label.toLowerCase()}...`"
                @keyup="textFieldKeyup($event.target.value, item.type, item.key)"
              />

              <!-- Character Count -->
              <div v-if="item.maxLength" class="absolute -bottom-6 right-0 text-xs text-base-content/50">
                {{ (fields[index].value || '').length }}/{{ item.maxLength }}
              </div>
            </div>
          </div>
        </div>

        <!-- Enhanced Password Input -->
        <div v-else-if="item.type === 'password'" class="form-control">
          <div class="relative">
            <label class="label group/label cursor-pointer">
              <span class="label-text font-semibold text-base-content flex items-center gap-2 group-hover/label:text-primary transition-colors duration-200">
                <Lock class="w-4 h-4 opacity-70" />
                {{ item.label }}
                <span v-if="item.required" class="text-error text-sm">*</span>
              </span>
              <div class="flex items-center gap-2">
                <!-- Password Strength Indicator -->
                <div v-if="fields[index].value" class="flex gap-1">
                  <div
                    v-for="i in 4"
                    :key="i"
                    class="w-2 h-2 rounded-full transition-colors duration-200"
                    :class="getPasswordStrengthColor(fields[index].value, i)"
                  ></div>
                </div>
                <span class="label-text-alt text-xs text-base-content/60">
                  {{ getPasswordStrengthText(fields[index].value) }}
                </span>
              </div>
            </label>

            <InputPassword
              v-model="fields[index].value"
              :label="null"
              :error="item.error"
              :class="[
                'input-enhanced transition-all duration-300',
                item.error ? 'input-error animate-shake' : 'focus:input-primary'
              ]"
              :placeholder="'Enter secure password...'"
            />
          </div>
        </div>

        <!-- Enhanced Email Input -->
        <div v-else-if="item.type === 'email'" class="form-control">
          <div class="relative">
            <label class="label group/label cursor-pointer">
              <span class="label-text font-semibold text-base-content flex items-center gap-2 group-hover/label:text-primary transition-colors duration-200">
                <Mail class="w-4 h-4 opacity-70" />
                {{ item.label }}
                <span v-if="item.required" class="text-error text-sm">*</span>
              </span>
              <span v-if="isValidEmail(fields[index].value)" class="label-text-alt">
                <div class="badge badge-success badge-xs gap-1">
                  <Check class="w-3 h-3" />
                  Valid
                </div>
              </span>
            </label>

            <InputField
              type="email"
              v-model="fields[index].value"
              :label="null"
              :error="item.error"
              :class="[
                'input-enhanced transition-all duration-300',
                item.error ? 'input-error animate-shake' :
                isValidEmail(fields[index].value) ? 'input-success' : 'focus:input-primary'
              ]"
              :placeholder="'user@example.com'"
            />
          </div>
        </div>

        <!-- Enhanced Date Input -->
        <div v-else-if="item.type === 'date'" class="form-control">
          <div class="relative">
            <label class="label group/label cursor-pointer">
              <span class="label-text font-semibold text-base-content flex items-center gap-2 group-hover/label:text-primary transition-colors duration-200">
                <Calendar class="w-4 h-4 opacity-70" />
                {{ item.label }}
                <span v-if="item.required" class="text-error text-sm">*</span>
              </span>
              <span v-if="fields[index].value" class="label-text-alt text-xs text-base-content/60">
                {{ formatRelativeDate(fields[index].value) }}
              </span>
            </label>

            <InputField
              type="date"
              v-model="fields[index].value"
              :label="null"
              :error="item.error"
              :class="[
                'input-enhanced transition-all duration-300',
                item.error ? 'input-error animate-shake' : 'focus:input-primary'
              ]"
            />
          </div>
        </div>

        <!-- Enhanced Timestamp Input -->
        <div v-else-if="item.type === 'timestamp'" class="form-control">
          <div class="relative">
            <label class="label group/label cursor-pointer">
              <span class="label-text font-semibold text-base-content flex items-center gap-2 group-hover/label:text-primary transition-colors duration-200">
                <Clock class="w-4 h-4 opacity-70" />
                {{ item.label }}
                <span v-if="item.required" class="text-error text-sm">*</span>
              </span>
            </label>

            <Timestamp
              :label="null"
              :name="item.key"
              :id="item.key"
              v-model="fields[index].value"
              placeholder="Select date and time"
              :required="item.required"
              :error="item.error"
              min="2024-01-16T00:00"
              max="2025-12-31T23:59"
              :class="[
                'input-enhanced transition-all duration-300',
                item.error ? 'input-error animate-shake' : 'focus:input-primary'
              ]"
            />
          </div>
        </div>

        <!-- Enhanced Slug Input -->
        <div v-else-if="item.type === 'slug'" class="form-control">
          <div class="relative">
            <label class="label group/label cursor-pointer">
              <span class="label-text font-semibold text-base-content flex items-center gap-2 group-hover/label:text-primary transition-colors duration-200">
                <Link class="w-4 h-4 opacity-70" />
                {{ item.label }}
                <span v-if="item.required" class="text-error text-sm">*</span>
              </span>
              <span class="label-text-alt text-xs text-base-content/60">
                Auto-generated from name/title
              </span>
            </label>

            <div class="relative">
              <InputField
                type="text"
                v-model="fields[index].value"
                :label="null"
                :error="item.error"
                :class="[
                  'input-enhanced font-mono transition-all duration-300',
                  item.error ? 'input-error animate-shake' : 'focus:input-primary'
                ]"
                :placeholder="'auto-generated-slug'"
                readonly
              />
              <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                <Globe class="w-4 h-4 text-base-content/40" />
              </div>
            </div>
          </div>
        </div>

        <!-- Enhanced Media Upload -->
        <div v-else-if="item.type === 'media'" class="form-control">
          <div class="relative">
            <label class="label group/label cursor-pointer">
              <span class="label-text font-semibold text-base-content flex items-center gap-2 group-hover/label:text-primary transition-colors duration-200">
                <ImageIcon class="w-4 h-4 opacity-70" />
                {{ item.label }}
                <span v-if="item.required" class="text-error text-sm">*</span>
              </span>
              <span class="label-text-alt text-xs text-base-content/60">
                Upload or select media files
              </span>
            </label>

            <div class="card bg-base-100 border-2 border-dashed border-base-300 hover:border-primary/50 transition-all duration-300 group-hover:shadow-lg">
              <div class="card-body p-4">
                <Image
                  :label="null"
                  placeholder="Select or upload media"
                  v-model="fields[index].value"
                  :loadData="fields[index].value"
                  :endpoint="item.endpoint"
                  :error="item.error"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Enhanced Number Input -->
        <div v-else-if="item.type === 'number'" class="form-control">
          <div class="relative">
            <label class="label group/label cursor-pointer">
              <span class="label-text font-semibold text-base-content flex items-center gap-2 group-hover/label:text-primary transition-colors duration-200">
                <Hash class="w-4 h-4 opacity-70" />
                {{ item.label }}
                <span v-if="item.required" class="text-error text-sm">*</span>
              </span>
              <span v-if="fields[index].value" class="label-text-alt text-xs text-base-content/60">
                {{ formatNumber(fields[index].value) }}
              </span>
            </label>

            <div class="relative">
              <InputField
                type="number"
                v-model="fields[index].value"
                :label="null"
                :min="item.min"
                :max="item.max"
                :step="item.step || 1"
                :error="item.error"
                :class="[
                  'input-enhanced font-mono transition-all duration-300',
                  item.error ? 'input-error animate-shake' : 'focus:input-primary'
                ]"
                :placeholder="`Enter ${item.label.toLowerCase()}...`"
                @keyup="textFieldKeyup($event.target.value, item.type, item.key)"
              />
              <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                <Calculator class="w-4 h-4 text-base-content/40" />
              </div>
            </div>
          </div>
        </div>

        <!-- Enhanced Model Search -->
        <div v-else-if="item.type === 'model_search'" class="form-control">
          <div class="relative">
            <label class="label group/label cursor-pointer">
              <span class="label-text font-semibold text-base-content flex items-center gap-2 group-hover/label:text-primary transition-colors duration-200">
                <Search class="w-4 h-4 opacity-70" />
                {{ item.label }}
                <span v-if="item.required" class="text-error text-sm">*</span>
              </span>
              <span class="label-text-alt text-xs text-base-content/60">
                {{ item.singleSearch ? 'Select one item' : 'Select multiple items' }}
              </span>
            </label>

            <div class="card bg-base-100 border border-base-300 hover:border-primary/50 transition-all duration-300">
              <div class="card-body p-4">
                <TextMultipleSelector
                  :label="null"
                  placeholder="Search and select..."
                  :model="item.model"
                  :columns="item.columns"
                  :singleMode="item.singleSearch"
                  v-model="fields[index].value"
                  :loadData="fields[index].value"
                  :endpoint="item.endpoint"
                  :displayKey="item.displayKey"
                  :error="item.error"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Enhanced Pivot Model -->
        <div v-else-if="item.type === 'pivot_model'" class="form-control">
          <div class="relative">
            <label class="label group/label cursor-pointer">
              <span class="label-text font-semibold text-base-content flex items-center gap-2 group-hover/label:text-primary transition-colors duration-200">
                <Network class="w-4 h-4 opacity-70" />
                {{ item.label }}
                <span v-if="item.required" class="text-error text-sm">*</span>
              </span>
              <span class="label-text-alt text-xs text-base-content/60">
                Manage relationships
              </span>
            </label>

            <div class="card bg-base-100 border border-base-300 hover:border-primary/50 transition-all duration-300">
              <div class="card-body p-4">
                <TextMultipleSelector
                  :label="null"
                  placeholder="Search and manage relationships..."
                  :model="item.model"
                  :columns="item.columns"
                  :singleMode="item.singleSearch"
                  v-model="fields[index].value"
                  :loadData="fields[index].value"
                  :endpoint="item.endpoint"
                  :displayKey="item.displayKey"
                  :error="item.error"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Enhanced Editor -->
        <div v-else-if="item.type === 'editor'" class="form-control">
          <div class="relative">
            <label class="label group/label cursor-pointer">
              <span class="label-text font-semibold text-base-content flex items-center gap-2 group-hover/label:text-primary transition-colors duration-200">
                <FileText class="w-4 h-4 opacity-70" />
                {{ item.label }}
                <span v-if="item.required" class="text-error text-sm">*</span>
              </span>
              <span class="label-text-alt text-xs text-base-content/60">
                Rich text content
              </span>
            </label>

            <div class="card bg-base-100 border border-base-300 hover:border-primary/50 transition-all duration-300">
              <div class="card-body p-4">
                <Editor
                  :label="null"
                  :name="item.key"
                  :id="item.key + '-editor'"
                  placeholder="Start typing your content..."
                  v-model="fields[index].value"
                  :required="item.required"
                  :minLength="item.minLength || 10"
                  :maxLength="item.maxLength || 1000"
                  :error="item.error"
                />

                <!-- Word Count -->
                <div class="flex justify-between items-center mt-2 text-xs text-base-content/50">
                  <span>Rich text editor</span>
                  <span>{{ getWordCount(fields[index].value) }} words</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Enhanced Toggle -->
        <div v-else-if="item.type === 'Toggle' || item.type === 'boolean'" class="form-control">
          <div class="relative">
            <label class="label group/label cursor-pointer hover:bg-base-200/50 rounded-lg p-3 -m-3 transition-all duration-200">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary/20 to-secondary/20 flex items-center justify-center">
                  <ToggleLeft class="w-5 h-5 text-primary" />
                </div>
                <div class="flex-1">
                  <span class="label-text font-semibold text-base-content flex items-center gap-2">
                    {{ item.label }}
                    <span v-if="item.required" class="text-error text-sm">*</span>
                  </span>
                  <div class="text-xs text-base-content/60 mt-1">
                    {{ fields[index].value ? 'Enabled' : 'Disabled' }}
                  </div>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <Toggle
                  v-model="fields[index].value"
                  :label="null"
                  :error="item.error"
                />
              </div>
            </label>
          </div>
        </div>

        <!-- Enhanced Chips Input -->
        <div v-else-if="item.type === 'chips'" class="form-control">
          <div class="relative">
            <label class="label group/label cursor-pointer">
              <span class="label-text font-semibold text-base-content flex items-center gap-2 group-hover/label:text-primary transition-colors duration-200">
                <Tags class="w-4 h-4 opacity-70" />
                {{ item.label }}
                <span v-if="item.required" class="text-error text-sm">*</span>
              </span>
              <span class="label-text-alt text-xs text-base-content/60">
                Add tags separated by Enter
              </span>
            </label>

            <div class="card bg-base-100 border border-base-300 hover:border-primary/50 transition-all duration-300">
              <div class="card-body p-4">
                <Chips
                  v-model="fields[index].value"
                  :label="null"
                  :error="item.error"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Enhanced Icon Input -->
        <div v-else-if="item.type === 'icon'" class="form-control">
          <div class="relative">
            <label class="label group/label cursor-pointer">
              <span class="label-text font-semibold text-base-content flex items-center gap-2 group-hover/label:text-primary transition-colors duration-200">
                <Palette class="w-4 h-4 opacity-70" />
                {{ item.label }}
                <span v-if="item.required" class="text-error text-sm">*</span>
              </span>
              <span class="label-text-alt text-xs text-base-content/60">
                SVG icon code
              </span>
            </label>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
              <!-- Icon Code Input -->
              <div class="form-control">
                <textarea
                  class="textarea textarea-bordered h-32 font-mono text-sm transition-all duration-300 focus:textarea-primary"
                  :class="{ 'textarea-error animate-shake': item.error }"
                  @keyup="textFieldKeyup($event.target.value, item.type, item.key)"
                  v-model="fields[index].value"
                  placeholder="<svg>...</svg>"
                />
              </div>

              <!-- Icon Preview -->
              <div class="card bg-base-200 border border-base-300">
                <div class="card-body items-center justify-center p-8">
                  <div class="text-center space-y-3">
                    <div class="text-xs text-base-content/60 uppercase tracking-wide font-medium">Preview</div>
                    <div
                      v-if="fields[index].value"
                      class="w-16 h-16 flex items-center justify-center bg-base-100 rounded-xl border border-base-300 hover:scale-110 transition-transform duration-200"
                      v-html="fields[index].value"
                    ></div>
                    <div v-else class="w-16 h-16 flex items-center justify-center bg-base-300 rounded-xl border border-dashed border-base-400 text-base-content/40">
                      <Palette class="w-8 h-8" />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Enhanced Select Input -->
        <div v-else-if="item.type === 'select'" class="form-control">
          <div class="relative">
            <label class="label group/label cursor-pointer">
              <span class="label-text font-semibold text-base-content flex items-center gap-2 group-hover/label:text-primary transition-colors duration-200">
                <List class="w-4 h-4 opacity-70" />
                {{ item.label }}
                <span v-if="item.required" class="text-error text-sm">*</span>
              </span>
              <span v-if="item.select_options" class="label-text-alt text-xs text-base-content/60">
                {{ item.select_options.length }} options available
              </span>
            </label>

            <SelectInput
              v-model="fields[index].value"
              :label="null"
              :options="formatSelectOptions(item.select_options)"
              :error="item.error"
              :class="[
                'select-enhanced transition-all duration-300',
                item.error ? 'select-error animate-shake' : 'focus:select-primary'
              ]"
            />
          </div>
        </div>

        <!-- Enhanced Error Display -->
        <Transition
          enter-active-class="transition-all duration-300 ease-out"
          enter-from-class="opacity-0 translate-y-2"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition-all duration-200 ease-in"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 translate-y-2"
        >
          <div v-if="item.error" class="mt-2">
            <div class="alert alert-error py-2 px-3 text-sm shadow-lg">
              <AlertTriangle class="w-4 h-4 flex-shrink-0" />
              <span>{{ item.error }}</span>
            </div>
          </div>
        </Transition>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
import { watch } from "vue";
import { formatDistanceToNow } from 'date-fns';
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

import {
  Type,
  Lock,
  Mail,
  Calendar,
  Clock,
  Link,
  Globe,
  ImageIcon,
  Hash,
  Calculator,
  Search,
  Network,
  FileText,
  ToggleLeft,
  Tags,
  Palette,
  List,
  Check,
  AlertTriangle
} from 'lucide-vue-next';

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

// Helper functions
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

const isValidEmail = (email) => {
  if (!email) return false;
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
};

const formatRelativeDate = (date) => {
  if (!date) return '';
  try {
    return formatDistanceToNow(new Date(date), { addSuffix: true });
  } catch {
    return '';
  }
};

const formatNumber = (num) => {
  if (!num) return '';
  return new Intl.NumberFormat().format(num);
};

const getPasswordStrength = (password) => {
  if (!password) return 0;
  let strength = 0;
  if (password.length >= 8) strength++;
  if (/[a-z]/.test(password)) strength++;
  if (/[A-Z]/.test(password)) strength++;
  if (/[0-9]/.test(password)) strength++;
  if (/[^A-Za-z0-9]/.test(password)) strength++;
  return Math.min(strength, 4);
};

const getPasswordStrengthColor = (password, index) => {
  const strength = getPasswordStrength(password);
  if (index <= strength) {
    if (strength <= 1) return 'bg-error';
    if (strength <= 2) return 'bg-warning';
    if (strength <= 3) return 'bg-info';
    return 'bg-success';
  }
  return 'bg-base-300';
};

const getPasswordStrengthText = (password) => {
  const strength = getPasswordStrength(password);
  if (!password) return '';
  if (strength <= 1) return 'Weak';
  if (strength <= 2) return 'Fair';
  if (strength <= 3) return 'Good';
  return 'Strong';
};

const getWordCount = (content) => {
  if (!content) return 0;
  // Remove HTML tags and count words
  const text = content.replace(/<[^>]*>/g, '');
  return text.trim().split(/\s+/).filter(word => word.length > 0).length;
};

const formatSelectOptions = (optionsData) => {
  let actualOptions = optionsData;

  // Handle the nested case
  if (optionsData && optionsData.select_options) {
    actualOptions = optionsData.select_options;
  }

  // Ensure actualOptions is an array
  if (!actualOptions || !Array.isArray(actualOptions)) {
    return {};
  }

  // Map to an object: { value: label }
  return actualOptions.reduce((acc, option) => {
    if (option && option.value && option.label) {
      acc[option.value] = option.label;
    }
    return acc;
  }, {});
};
</script>

<style scoped>
/* Enhanced Form Field Container */
.form-field-container {
  opacity: 0;
  animation: slideInUp 0.5s ease-out forwards;
}

@keyframes slideInUp {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Enhanced Input Styling */
.input-enhanced,
.select-enhanced {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.input-enhanced:focus,
.select-enhanced:focus {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px hsl(var(--p) / 0.15);
}

/* Shake Animation for Errors */
@keyframes shake {
  0%, 100% { transform: translateX(0); }
  10%, 30%, 50%, 70%, 90% { transform: translateX(-2px); }
  20%, 40%, 60%, 80% { transform: translateX(2px); }
}

.animate-shake {
  animation: shake 0.5s ease-in-out;
}

/* Enhanced Label Animations */
.group\/label:hover .label-text {
  transform: translateX(2px);
}

/* Card Hover Effects */
.card {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.card:hover {
  transform: translateY(-2px);
}

/* Enhanced Toggle Container */
.form-control .label.group\/label.cursor-pointer {
  transition: all 0.3s ease;
  border-radius: 0.75rem;
}

.form-control .label.group\/label.cursor-pointer:hover {
  background-color: hsl(var(--b2) / 0.5);
  transform: scale(1.02);
}

/* Icon Preview Animation */
.w-16.h-16 {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Enhanced Password Strength Indicators */
.w-2.h-2.rounded-full {
  transition: all 0.3s ease;
}

/* Media Upload Card Enhancement */
.border-dashed {
  transition: all 0.3s ease;
}

.border-dashed:hover {
  border-style: solid;
  background-color: hsl(var(--p) / 0.05);
}

/* Enhanced Focus States */
.input:focus-visible,
.select:focus-visible,
.textarea:focus-visible {
  outline: 2px solid hsl(var(--p));
  outline-offset: 2px;
}

/* Character Count Animation */
.absolute.-bottom-6 {
  transition: all 0.3s ease;
}

/* Alert Animation Enhancement */
.alert {
  border-radius: 0.75rem;
  border-left: 4px solid;
}

.alert-error {
  border-left-color: hsl(var(--er));
  background: linear-gradient(90deg, hsl(var(--er) / 0.1), hsl(var(--er) / 0.05));
}

/* Word Count Enhancement */
.flex.justify-between.items-center.mt-2 {
  padding-top: 0.5rem;
  border-top: 1px solid hsl(var(--b3) / 0.3);
}

/* Enhanced Grid Layout for Icon */
.grid.grid-cols-1.lg\:grid-cols-2 {
  gap: 1.5rem;
}

/* Password Strength Animation */
@keyframes strengthGlow {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}

.bg-success,
.bg-info,
.bg-warning,
.bg-error {
  animation: strengthGlow 2s ease-in-out infinite;
}

/* Enhanced Textarea */
.textarea {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  resize: vertical;
  min-height: 8rem;
}

.textarea:focus {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px hsl(var(--p) / 0.15);
}

/* Label Enhancement */
.label {
  transition: all 0.3s ease;
  margin-bottom: 0.5rem;
}

.label-text {
  transition: all 0.3s ease;
  font-weight: 600;
}

/* Icon Container Enhancement */
.w-10.h-10.rounded-xl {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.group\/label:hover .w-10.h-10.rounded-xl {
  transform: rotate(5deg) scale(1.1);
}

/* Custom Scrollbar for Textarea */
.textarea::-webkit-scrollbar {
  width: 6px;
}

.textarea::-webkit-scrollbar-track {
  background: hsl(var(--b3));
  border-radius: 3px;
}

.textarea::-webkit-scrollbar-thumb {
  background: hsl(var(--p) / 0.3);
  border-radius: 3px;
}

.textarea::-webkit-scrollbar-thumb:hover {
  background: hsl(var(--p) / 0.5);
}

/* Responsive Design */
@media (max-width: 640px) {
  .grid.grid-cols-1.lg\:grid-cols-2 {
    grid-template-columns: 1fr;
  }

  .form-field-container {
    margin-bottom: 2rem;
  }
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
  .border-base-300 {
    border-color: hsl(var(--bc));
  }

  .bg-base-100 {
    background-color: hsl(var(--b1));
  }
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
  .animate-shake,
  .transition-all,
  .transition-transform {
    animation: none;
    transition: none;
  }

  .bg-success,
  .bg-info,
  .bg-warning,
  .bg-error {
    animation: none;
  }
}

/* Print Styles */
@media print {
  .card {
    border: 1px solid #000;
    box-shadow: none;
  }

  .btn {
    display: none;
  }
}

/* Enhanced Badge Styling */
.badge {
  transition: all 0.2s ease;
}

.badge:hover {
  transform: scale(1.05);
}

/* Loading State Enhancement */
.loading {
  filter: drop-shadow(0 0 8px hsl(var(--p) / 0.3));
}

/* Form Control Spacing */
.form-control {
  margin-bottom: 1.5rem;
}

.form-control:last-child {
  margin-bottom: 0;
}

/* Enhanced Input Group Styling */
.relative .absolute {
  transition: all 0.3s ease;
}

/* Validation Success State */
.input-success {
  border-color: hsl(var(--su));
  background-color: hsl(var(--su) / 0.05);
}

.input-success:focus {
  border-color: hsl(var(--su));
  box-shadow: 0 0 0 3px hsl(var(--su) / 0.1);
}

/* Required Field Indicator */
.text-error {
  font-weight: bold;
}

/* Enhanced Description Text */
.label-text-alt {
  transition: all 0.3s ease;
}

.group\/label:hover .label-text-alt {
  color: hsl(var(--bc) / 0.8);
}
</style>
