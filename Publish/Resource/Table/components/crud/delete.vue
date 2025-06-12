<template>
    <!-- Enhanced Delete Button with Professional Styling -->
    <button @click="openModal"
        class="btn btn-error btn-sm gap-2 group relative overflow-hidden shadow-md hover:shadow-lg transition-all duration-300 hover:scale-105"
        aria-label="Delete item">
        <!-- Background Animation -->
        <div
            class="absolute inset-0 bg-gradient-to-r from-error/50 to-warning/50 opacity-0 group-hover:opacity-30 transition-opacity duration-300">
        </div>

        <!-- Icon with Animation -->
        <Trash2
            class="w-4 h-4 transform transition-all duration-300 group-hover:scale-110 group-hover:rotate-12 relative z-10" />

        <!-- Text (Hidden on Mobile) -->
        <span class="hidden sm:inline relative z-10 font-medium">Delete</span>

        <!-- Danger Shimmer Effect -->
        <div
            class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-red-500/20 to-transparent group-hover:translate-x-full transition-transform duration-700 ease-out">
        </div>
    </button>

    <!-- Enhanced Modal with Blur Backdrop -->
    <div v-if="isOpen" class="modal modal-open">
        <!-- Backdrop with Blur -->
        <div class="modal-backdrop bg-black/70 backdrop-blur-sm" @click="closeModal"></div>

        <!-- Modal Container -->
        <div class="modal-box w-full max-w-md relative overflow-hidden border border-error/20">
            <!-- Loading Overlay -->
            <div v-if="isDeleting"
                class="absolute inset-0 bg-base-100/90 backdrop-blur-sm flex items-center justify-center z-50">
                <div class="flex flex-col items-center gap-4">
                    <div class="loading loading-spinner loading-lg text-error drop-shadow-lg"></div>
                    <div class="text-center">
                        <p class="text-lg font-semibold text-base-content">Deleting Record</p>
                        <p class="text-sm text-base-content/60">This action cannot be undone...</p>
                    </div>

                    <!-- Dramatic Progress Bar -->
                    <div class="w-full max-w-xs">
                        <div class="h-2 bg-base-300 rounded-full overflow-hidden">
                            <div
                                class="h-full bg-gradient-to-r from-error to-warning animate-pulse w-full transition-all duration-1000">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Close Button -->
            <button @click="closeModal"
                class="btn btn-sm btn-circle btn-ghost absolute right-4 top-4 hover:btn-error hover:rotate-90 transition-all duration-300 z-10"
                aria-label="Close modal" :disabled="isDeleting">
                <X class="w-4 h-4" />
            </button>

            <!-- Modal Content -->
            <div class="text-center space-y-6 pt-8 pb-4">
                <!-- Animated Warning Icon -->
                <div class="flex justify-center">
                    <div class="relative">
                        <!-- Pulsing Background -->
                        <div class="absolute inset-0 w-24 h-24 rounded-full bg-error/20 animate-ping"></div>
                        <div
                            class="absolute inset-2 w-20 h-20 rounded-full bg-error/30 animate-ping animation-delay-150">
                        </div>

                        <!-- Main Icon Container -->
                        <div
                            class="relative w-24 h-24 rounded-full bg-gradient-to-br from-error/30 via-error/20 to-warning/20 flex items-center justify-center border-4 border-error/30 shadow-lg">
                            <AlertTriangle class="w-12 h-12 text-error animate-pulse" />

                            <!-- Rotating Border -->
                            <div
                                class="absolute inset-0 rounded-full border-2 border-transparent border-t-error/50 animate-spin">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content Section -->
                <div class="space-y-4 px-2">
                    <h3 class="text-2xl font-bold text-error">
                        Confirm Deletion
                    </h3>

                    <!-- Warning Message -->
                    <div class="bg-error/5 border border-error/20 rounded-lg p-4">
                        <p class="text-base-content/80 mb-2">
                            Are you sure you want to delete this record?
                        </p>

                        <!-- Record Info -->
                        <div class="bg-base-200/50 rounded-lg p-3 my-3">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-base-content/60">Record ID:</span>
                                <span class="font-mono font-bold text-error">#{{ props.id }}</span>
                            </div>
                            <div v-if="recordTitle" class="flex items-center justify-between text-sm mt-1">
                                <span class="text-base-content/60">Name:</span>
                                <span class="font-medium text-base-content truncate ml-2">{{ recordTitle }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 justify-center text-error font-semibold">
                            <AlertTriangle class="w-5 h-5" />
                            <span>This action cannot be undone!</span>
                        </div>
                    </div>

                    <!-- Impact Warning -->
                    <div class="flex items-center gap-2 bg-warning/10 border border-warning/20 rounded-lg p-4">
                        <AlertCircle class="w-5 h-5 flex-shrink-0" />
                        <div class="text-left">
                            <h4 class="font-semibold text-sm">Potential Impact</h4>
                            <p class="text-xs opacity-80">Deleting this record may affect related data and cannot be
                                reversed.</p>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3 pt-4">
                    <button @click="closeModal"
                        class="btn btn-ghost flex-1 hover:bg-base-200 border border-base-300 hover:border-base-400"
                        :disabled="isDeleting">
                        <X class="w-4 h-4 mr-2" />
                        Cancel
                    </button>

                    <button @click="deleteItem"
                        class="btn btn-error flex-1 gap-2 relative overflow-hidden group shadow-lg hover:shadow-xl"
                        :disabled="isDeleting">
                        <!-- Button Background Animation -->
                        <div
                            class="absolute inset-0 bg-gradient-to-r from-error to-warning opacity-0 group-hover:opacity-20 transition-opacity duration-300">
                        </div>

                        <Trash2 class="w-4 h-4 relative z-10" :class="{ 'animate-bounce': isDeleting }" />
                        <span class="relative z-10 font-semibold">
                            {{ isDeleting ? 'Deleting...' : 'Delete Forever' }}
                        </span>

                        <!-- Danger Shimmer -->
                        <div
                            class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/20 to-transparent group-hover:translate-x-full transition-transform duration-500 ease-out">
                        </div>
                    </button>
                </div>

                <!-- Additional Safety Message -->
                <div class="text-xs text-base-content/50 italic pt-2 border-t border-base-200">
                    Make sure you have backups before proceeding with this irreversible action
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Toast (Optional Enhancement) -->
    <div v-if="showConfirmation" class="toast toast-top toast-center z-50">
        <div class="alert alert-success shadow-lg animate-bounce">
            <CheckCircle class="w-5 h-5" />
            <span class="font-medium">Record deleted successfully!</span>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { startWindToast } from "@mariojgt/wind-notify/packages/index.js";
import axios from 'axios';
import {
    Trash2,
    X,
    AlertTriangle,
    AlertCircle,
    CheckCircle
} from 'lucide-vue-next';

const isOpen = ref(false);
const isDeleting = ref(false);
const showConfirmation = ref(false);

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
    recordData: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['onDelete']);

// Computed property to get record title/name
const recordTitle = computed(() => {
    const titleFields = ['name', 'title', 'label', 'subject', 'heading'];
    for (const field of titleFields) {
        if (props.recordData[field]) {
            const title = String(props.recordData[field]);
            return title.length > 30 ? title.substring(0, 30) + '...' : title;
        }
    }
    return null;
});

// Methods
const openModal = () => {
    isOpen.value = true;
};

const closeModal = () => {
    if (!isDeleting.value) {
        isOpen.value = false;
    }
};

const deleteItem = async () => {
    if (isDeleting.value) return;

    // Double confirmation for safety
    const userConfirmed = confirm(
        `⚠️ FINAL WARNING ⚠️\n\nYou are about to permanently delete record #${props.id}.\n\nThis action is IRREVERSIBLE and cannot be undone.\n\nType 'DELETE' in the prompt to confirm.`
    );

    if (!userConfirmed) return;

    // Additional safety check - require typing DELETE
    const confirmText = prompt(
        `To proceed with deletion, type "DELETE" (in capital letters):`
    );

    if (confirmText !== 'DELETE') {
        startWindToast('info', 'Deletion cancelled - confirmation text did not match', 'info');
        return;
    }

    try {
        isDeleting.value = true;

        // Add artificial delay for dramatic effect and safety
        await new Promise(resolve => setTimeout(resolve, 2000));

        const response = await axios.post(props.endpoint, {
            model: props.model,
            id: props.id,
            permission: props.permission,
        });

        // Show success state briefly
        showConfirmation.value = true;
        setTimeout(() => {
            showConfirmation.value = false;
        }, 3000);

        startWindToast('success', 'Record deleted successfully! 🗑️', 'success');

        emit('onDelete');
        closeModal();

    } catch (error: any) {
        const errorMessage = error.response?.data?.message || 'An error occurred while deleting the item';

        if (error.response?.data?.errors) {
            const errors = error.response.data.errors;
            Object.values(errors).forEach((errorMsg: any) => {
                if (Array.isArray(errorMsg)) {
                    startWindToast('error', errorMsg[0], 'error');
                } else {
                    startWindToast('error', String(errorMsg), 'error');
                }
            });
        } else {
            startWindToast('error', errorMessage, 'error');
        }
    } finally {
        isDeleting.value = false;
    }
};
</script>

<style scoped>
/* Enhanced Button Animations */
.btn {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn:hover:not(:disabled) {
    transform: translateY(-1px);
}

.btn:active:not(:disabled) {
    transform: translateY(0) scale(0.98);
}

/* Dramatic Modal Animations */
.modal-open .modal-box {
    animation: dangerModalEnter 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes dangerModalEnter {
    0% {
        opacity: 0;
        transform: scale(0.9) translateY(30px);
        filter: blur(4px);
    }

    50% {
        opacity: 0.8;
        transform: scale(1.02) translateY(-5px);
        filter: blur(2px);
    }

    100% {
        opacity: 1;
        transform: scale(1) translateY(0);
        filter: blur(0);
    }
}

/* Enhanced Warning Icon Animations */
@keyframes dramaticPulse {

    0%,
    100% {
        transform: scale(1);
        opacity: 1;
    }

    50% {
        transform: scale(1.05);
        opacity: 0.8;
    }
}

.animate-pulse {
    animation: dramaticPulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Rotating Border Animation */
@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.animate-spin {
    animation: spin 3s linear infinite;
}

/* Ping Animation with Delays */
@keyframes ping {

    75%,
    100% {
        transform: scale(2);
        opacity: 0;
    }
}

.animate-ping {
    animation: ping 2s cubic-bezier(0, 0, 0.2, 1) infinite;
}

.animation-delay-150 {
    animation-delay: 150ms;
}

/* Enhanced Loading Spinner */
.loading-spinner {
    filter: drop-shadow(0 0 15px hsl(var(--er) / 0.4));
}

/* Bounce Animation for Delete Button */
@keyframes bounce {

    0%,
    20%,
    53%,
    80%,
    100% {
        animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
        transform: translate3d(0, 0, 0) scale(1);
    }

    40%,
    43% {
        animation-timing-function: cubic-bezier(0.755, 0.05, 0.855, 0.06);
        transform: translate3d(0, -4px, 0) scale(1.1);
    }

    70% {
        animation-timing-function: cubic-bezier(0.755, 0.05, 0.855, 0.06);
        transform: translate3d(0, -2px, 0) scale(1.05);
    }

    90% {
        transform: translate3d(0, -1px, 0) scale(1.02);
    }
}

.animate-bounce {
    animation: bounce 1s infinite;
}

/* Progress Bar Animation */
@keyframes progressPulse {

    0%,
    100% {
        opacity: 1;
    }

    50% {
        opacity: 0.7;
    }
}

.animate-pulse {
    animation: progressPulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Alert Enhancements */
.alert {
    border-radius: 0.75rem;
    border: 1px solid;
}

.alert-warning {
    border-color: hsl(var(--wa) / 0.3);
    background: hsl(var(--wa) / 0.1);
}

.alert-success {
    border-color: hsl(var(--su) / 0.3);
}

/* Toast Animation */
.toast {
    animation: slideInFromTop 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes slideInFromTop {
    from {
        opacity: 0;
        transform: translateY(-100px) scale(0.8);
    }

    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* Shimmer Effect Enhancement */
.group:hover .group-hover\:translate-x-full {
    transition-delay: 0.1s;
}

/* Record Info Card Enhancement */
.bg-base-200\/50 {
    backdrop-filter: blur(8px);
    border: 1px solid hsl(var(--b3) / 0.3);
}

/* Enhanced Focus States */
.btn:focus-visible {
    outline: 2px solid hsl(var(--er));
    outline-offset: 2px;
}

/* Responsive Enhancements */
@media (max-width: 640px) {
    .modal-box {
        width: 95vw;
        margin: 1rem;
    }

    .text-2xl {
        font-size: 1.5rem;
    }

    .space-y-6>*+* {
        margin-top: 1rem;
    }
}

/* High-contrast mode support */
@media (prefers-contrast: high) {
    .border-error\/20 {
        border-color: hsl(var(--er));
    }

    .bg-error\/5 {
        background-color: hsl(var(--er) / 0.15);
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {

    .animate-ping,
    .animate-pulse,
    .animate-spin,
    .animate-bounce {
        animation: none;
    }

    .transition-all,
    .transition-transform,
    .transition-opacity {
        transition: none;
    }
}

/* Print styles */
@media print {
    .modal {
        display: none !important;
    }
}
</style>
