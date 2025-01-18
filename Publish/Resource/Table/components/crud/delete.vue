<template>
    <!-- Delete Button with improved hover effect -->
    <button
      @click="isOpen = true"
      class="btn btn-error btn-sm gap-2 group relative overflow-hidden hover:shadow-lg transition-all duration-300"
      aria-label="Delete item"
    >
      <Trash2
        class="w-4 h-4 transform transition-all duration-300 group-hover:scale-110"
      />
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

        <!-- Modal Container with improved animation -->
        <div class="fixed inset-0 flex items-center justify-center p-4">
          <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
          >
            <div
              v-if="isOpen"
              class="relative bg-base-100 rounded-2xl shadow-2xl w-full max-w-md transform transition-all"
            >
              <!-- Loading Overlay -->
              <div v-if="isDeleting"
                   class="absolute inset-0 bg-base-100/70 backdrop-blur-sm flex items-center justify-center z-50 rounded-2xl">
                <div class="flex flex-col items-center gap-4">
                  <div class="loading loading-spinner loading-lg text-error"></div>
                  <p class="text-sm font-medium text-base-content/70">Deleting...</p>
                </div>
              </div>

              <!-- Modal Content -->
              <div class="p-6">
                <!-- Close Button -->
                <button
                  @click="isOpen = false"
                  class="btn btn-sm btn-circle btn-ghost absolute right-4 top-4 hover:rotate-90 transition-transform duration-200"
                  aria-label="Close modal"
                >
                  <X class="w-4 h-4" />
                </button>

                <!-- Warning Icon -->
                <div class="flex justify-center mb-6">
                  <div class="w-20 h-20 rounded-full bg-error/20 flex items-center justify-center animate-pulse">
                    <AlertTriangle class="w-10 h-10 text-error" />
                  </div>
                </div>

                <!-- Content -->
                <div class="text-center space-y-4">
                  <h3 class="text-2xl font-bold text-base-content">
                    Confirm Deletion
                  </h3>
                  <p class="text-base-content/70">
                    Are you sure you want to delete item #{{ props.id }}?
                  </p>
                  <p class="text-sm text-error font-medium">
                    This action cannot be undone.
                  </p>
                </div>

                <!-- Action Buttons -->
                <div class="mt-8 flex gap-4">
                  <button
                    @click="isOpen = false"
                    class="btn btn-ghost flex-1 hover:bg-base-200"
                    :disabled="isDeleting"
                  >
                    Cancel
                  </button>
                  <button
                    @click="deleteItem"
                    class="btn btn-error flex-1 gap-2"
                    :disabled="isDeleting"
                  >
                    <Trash2 class="w-4 h-4" :class="{ 'animate-spin': isDeleting }" />
                    {{ isDeleting ? 'Deleting...' : 'Delete' }}
                  </button>
                </div>
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </Transition>
  </template>

  <script setup lang="ts">
  import { ref } from 'vue';
  import { useMessage } from 'naive-ui';
  import axios from 'axios';
  import {
    Trash2,
    X,
    AlertTriangle
  } from 'lucide-vue-next';

  const message = useMessage();
  const isOpen = ref(false);
  const isDeleting = ref(false);

  const props = defineProps({
    id: {
      type: Number,
      default: 0,
    },
    model: {
      type: String,
      default: '',
    },
    endpoint: {
      type: String,
      default: '',
    },
    permission: {
      type: String,
      default: null,
    },
  });

  const emit = defineEmits(['onDelete']);

  const deleteItem = async () => {
    if (isDeleting.value) return;

    try {
      isDeleting.value = true;

      const response = await axios.post(props.endpoint, {
        model: props.model,
        id: props.id,
        permission: props.permission,
      });

      message.success(response.data.message);

      emit('onDelete');
      isOpen.value = false;
    } catch (error: any) {
      const errorMessage = error.response?.data?.message || 'An error occurred while deleting the item';

      if (error.response?.data?.errors) {
        const errors = error.response.data.errors;
        Object.values(errors).forEach((errorMsg: any) => {
          if (Array.isArray(errorMsg)) {
            message.error(errorMsg[0]);
          }
        });
      } else {
        message.error(errorMessage);
      }
    } finally {
      isDeleting.value = false;
    }
  };
  </script>

  <style scoped>
  /* Enhanced animations */
  @keyframes pulse {
    0%, 100% {
      opacity: 1;
      transform: scale(1);
    }
    50% {
      opacity: 0.5;
      transform: scale(1.05);
    }
  }

  .btn {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }

  .btn:active {
    transform: scale(0.95);
  }

  /* Modal animations */
  .modal-enter-active,
  .modal-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }

  .modal-enter-from,
  .modal-leave-to {
    opacity: 0;
    transform: scale(0.95);
  }
  </style>
