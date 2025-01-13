<template>
    <!-- Delete Button -->
    <label
      :for="'my-modal-delete-' + props.id"
      class="btn btn-error modal-button group transition-all duration-300 hover:scale-105"
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="h-6 w-6 group-hover:animate-pulse"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        stroke-width="2"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
        />
      </svg>
    </label>

    <!-- Modal Checkbox Trigger -->
    <input
      type="checkbox"
      :id="'my-modal-delete-' + props.id"
      class="modal-toggle"
    />

    <!-- Modal Container -->
    <div class="modal text-neutral-content">
      <div
        class="modal-box relative flex flex-col items-center justify-center
               w-11/12 max-w-md
               bg-white dark:bg-gray-800
               border-2 border-red-500
               rounded-2xl
               shadow-2xl
               text-center
               p-8
               space-y-6"
      >
        <!-- Close Button -->
        <button
          :for="'my-modal-delete-' + props.id"
          class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3
                 hover:bg-red-50 dark:hover:bg-red-900"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-6 w-6 text-gray-500 hover:text-red-600"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>

        <!-- Delete Confirmation Content -->
        <div class="space-y-4">
          <!-- Warning Icon -->
          <div class="flex justify-center mb-4">
            <div
              class="bg-red-100 dark:bg-red-900/30
                     text-red-600 dark:text-red-400
                     rounded-full
                     p-4
                     inline-flex
                     items-center
                     justify-center
                     animate-bounce"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-12 w-12"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                />
              </svg>
            </div>
          </div>

          <!-- Confirmation Text -->
          <h2
            class="text-2xl font-bold
                   text-gray-800 dark:text-gray-200"
          >
            Confirm Deletion
          </h2>
          <p
            class="text-gray-600 dark:text-gray-400
                   text-sm md:text-base"
          >
            Are you sure you want to delete item #{{ props.id }}?
            This action cannot be undone.
          </p>
        </div>

        <!-- Action Buttons -->
        <div
          class="flex w-full justify-between space-x-4"
        >
          <label
            :for="'my-modal-delete-' + props.id"
            class="btn btn-outline btn-primary flex-1"
          >
            Cancel
          </label>
          <button
            @click="deleteItem"
            class="btn btn-error flex-1
                   hover:bg-red-700
                   transition-colors
                   duration-300
                   group"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-5 w-5 mr-2
                     group-hover:animate-pulse"
              viewBox="0 0 20 20"
              fill="currentColor"
            >
              <path
                fill-rule="evenodd"
                d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                clip-rule="evenodd"
              />
            </svg>
            Delete
          </button>
        </div>
      </div>
    </div>
  </template>

  <script setup lang="ts">
  import axios from "axios";
  import { useMessage } from "naive-ui";

  const message = useMessage();

  const props = defineProps({
    id: {
      type: Number,
      default: 0,
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

  const emit = defineEmits(["onDelete"]);

  const deleteItem = async () => {
    try {
      const response = await axios.post(props.endpoint, {
        model: props.model,
        id: props.id,
        permission: props.permission,
      });

      message.success(response.data.message);
      emit("onDelete");

      // Programmatically close the modal
      const modalCheckbox = document.getElementById(`my-modal-delete-${props.id}`) as HTMLInputElement;
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

  <style scoped>
  /* Additional subtle animations and transitions */
  .modal-box {
    transition: all 0.3s ease-in-out;
  }

  .modal-box:hover {
    transform: translateY(-5px);
  }
  </style>
