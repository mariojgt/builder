<template>
    <td v-for="(item, index) in tableDisplayInformation" :key="index" class="p-4 align-middle">
        <!-- Media Gallery Display -->
        <div v-if="item.type === 'media'" class="relative">
            <!-- Empty State -->
            <div v-if="!item.value || item.value.length === 0"
                class="flex items-center justify-center h-20 w-full bg-base-200 rounded-lg">
                <ImageOff class="w-5 h-5 text-base-content/30" />
            </div>

            <!-- Gallery Grid -->
            <div v-else class="grid grid-cols-3 gap-2 w-fit">
                <div v-for="(mediaItem, mediaIndex) in item.value.slice(0, showAllMedia ? item.value.length : 3)"
                    :key="mediaIndex" class="relative group aspect-square w-20 overflow-hidden rounded-lg bg-base-200">
                    <!-- Image -->
                    <img :src="mediaItem.url?.default || mediaItem.url"
                        :alt="mediaItem.name || `Image ${mediaIndex + 1}`"
                        class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110"
                        loading="lazy" />

                    <!-- Overlay -->
                    <div
                        class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/0 opacity-0 group-hover:opacity-100 transition-all duration-300">
                        <!-- Actions -->
                        <div class="absolute bottom-2 right-2 flex gap-1">
                            <button @click.stop="openImageModal(mediaItem)"
                                class="btn btn-circle btn-ghost btn-xs bg-base-200/30 hover:bg-base-200/50 text-white"
                                title="View larger">
                                <Maximize2 class="w-3 h-3" />
                            </button>
                            <button v-if="mediaItem.url?.download" @click.stop="downloadImage(mediaItem)"
                                class="btn btn-circle btn-ghost btn-xs bg-base-200/30 hover:bg-base-200/50 text-white"
                                title="Download">
                                <Download class="w-3 h-3" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Show More Button -->
                <div v-if="item.value.length > 3 && !showAllMedia" @click="showAllMedia = true"
                    class="relative aspect-square w-20 bg-base-200 rounded-lg cursor-pointer group overflow-hidden">
                    <div
                        class="absolute inset-0 flex items-center justify-center bg-base-300/50 group-hover:bg-base-300/70 transition-colors">
                        <div class="flex flex-col items-center text-base-content/70">
                            <Plus class="w-5 h-5" />
                            <span class="text-xs">{{ item.value.length - 3 }} more</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status/Toggle Display -->
        <div v-else-if="item.type === 'toggle'" class="flex items-center gap-2">
            <div :class="[
                'px-3 py-1 rounded-full text-xs font-medium inline-flex items-center gap-2 transition-all duration-300',
                item.value ?
                    'bg-success/10 text-success hover:bg-success/20' :
                    'bg-error/10 text-error hover:bg-error/20'
            ]">
                <div :class="[
                    'w-2 h-2 rounded-full',
                    item.value ? 'bg-success animate-pulse' : 'bg-error'
                ]"></div>
                {{ item.value ? 'Active' : 'Inactive' }}
            </div>
        </div>

        <!-- Model Search/Tags Display -->
        <div v-else-if="item.type === 'model_search'" class="flex flex-wrap gap-1.5 max-w-xs">
            <div v-for="(searchItem, searchKey) in item.value" :key="searchKey" class="group/tag cursor-default">
                <!-- Tag -->
                <div class="badge badge-sm gap-1.5 transition-all duration-300" :class="getTagColor(String(searchKey))">
                    <span class="opacity-60 text-[10px] uppercase">{{ searchKey }}</span>
                    <span class="font-medium">{{ searchItem }}</span>
                </div>

                <!-- Tooltip -->
                <div
                    class="fixed z-50 bottom-full left-1/2 -translate-x-1/2 mb-2 opacity-0 invisible group-hover/tag:opacity-100 group-hover/tag:visible transition-all duration-200 pointer-events-none">
                    <div class="bg-base-300 text-base-content px-2 py-1 rounded text-xs shadow-lg">
                        {{ searchKey }}: {{ searchItem }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Boolean Display -->
        <div v-else-if="item.type === 'boolean'" class="flex items-center justify-center">
            <div :class="[
                'w-8 h-8 rounded-full flex items-center justify-center transition-all duration-300',
                item.value
                    ? 'bg-success/10 text-success hover:bg-success/20'
                    : 'bg-error/10 text-error hover:bg-error/20'
            ]">
                <CheckCircle2 v-if="item.value" class="w-5 h-5" />
                <XCircle v-else class="w-5 h-5" />
            </div>
        </div>

        <!-- Progress Display -->
        <div v-else-if="item.type === 'progress'" class="w-full max-w-xs">
            <div class="flex justify-between items-center mb-1">
                <span class="text-sm">{{ item.value }}%</span>
                <span :class="[
                    'text-xs px-1.5 py-0.5 rounded',
                    getProgressStatus(item.value).class
                ]">{{ getProgressStatus(item.value).text }}</span>
            </div>
            <div class="w-full bg-base-200 rounded-full h-2">
                <div class="rounded-full h-2 transition-all duration-500"
                    :class="getProgressStatus(item.value).barClass" :style="{ width: `${item.value}%` }"></div>
            </div>
        </div>

        <!-- Date Display -->
        <div v-else-if="item.type === 'date'" class="flex items-center gap-2">
            <Calendar class="w-4 h-4 text-primary/70" />
            <div class="flex flex-col">
                <span class="text-sm">{{ formatDate(item.value) }}</span>
                <span class="text-xs text-base-content/60">{{ formatRelativeDate(item.value) }}</span>
            </div>
        </div>

        <!-- Price Display -->
        <div v-else-if="item.type === 'price'" class="font-mono text-base-content/90">
            <span class="text-xs text-base-content/60">$</span>
            {{ formatPrice(item.value) }}
        </div>

        <!-- Number/Stats Display -->
        <div v-else-if="item.type === 'number'" class="flex items-baseline gap-1">
            <span class="text-base font-semibold">
                {{ formatNumber(item.value) }}
            </span>
            <span v-if="item.trend" :class="[
                'text-xs flex items-center gap-0.5',
                item.trend > 0 ? 'text-success' : 'text-error'
            ]">
                <TrendingUp v-if="item.trend > 0" class="w-3 h-3" />
                <TrendingDown v-else class="w-3 h-3" />
                {{ Math.abs(item.trend) }}%
            </span>
        </div>

        <!-- Icon Display -->
        <div v-else-if="item.type === 'icon'"
            class="w-10 h-10 rounded-lg flex items-center justify-center bg-base-200/50 hover:bg-base-200 transition-colors"
            v-html="item.value"></div>

        <!-- Default Text Display -->
        <div v-else class="group relative max-w-xs">
            <!-- Text with Truncation -->
            <div class="truncate" :class="{ 'text-base-content/50': !item.value }">
                {{ item.value || '—' }}
            </div>

            <!-- Full Text Tooltip -->
            <div v-if="item.value && item.value.length > 30"
                class="fixed z-50 bottom-full left-1/2 -translate-x-1/2 mb-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 pointer-events-none">
                <div class="bg-base-300 text-base-content px-3 py-2 rounded shadow-lg max-w-xs">
                    {{ item.value }}
                </div>
            </div>
        </div>
    </td>

    <!-- Enhanced Image Modal -->
    <Teleport to="body">
        <TransitionRoot appear :show="!!expandedImage" as="template">
            <Dialog as="div" @close="closeImageModal" class="relative z-50">
                <TransitionChild enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100"
                    leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-black/80 backdrop-blur-sm" />
                </TransitionChild>

                <div class="fixed inset-0 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4">
                        <TransitionChild enter="ease-out duration-300" enter-from="opacity-0 scale-95"
                            enter-to="opacity-100 scale-100" leave="ease-in duration-200"
                            leave-from="opacity-100 scale-100" leave-to="opacity-0 scale-95">
                            <DialogPanel class="relative bg-base-100 rounded-xl shadow-2xl">
                                <!-- Image -->
                                <div class="relative">
                                    <img :src="expandedImage?.url?.default || expandedImage?.url"
                                        :alt="expandedImage?.name || 'Preview'"
                                        class="max-w-3xl max-h-[70vh] object-contain rounded-t-xl" />

                                    <!-- Actions -->
                                    <div class="absolute top-4 right-4 flex gap-2">
                                        <button v-if="expandedImage?.url?.download"
                                            @click="downloadImage(expandedImage)"
                                            class="btn btn-circle btn-sm bg-base-200/30 hover:bg-base-200/50 text-white">
                                            <Download class="w-4 h-4" />
                                        </button>
                                        <button @click="closeImageModal"
                                            class="btn btn-circle btn-sm bg-base-200/30 hover:bg-base-200/50 text-white">
                                            <X class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>

                                <!-- Image Info -->
                                <div class="p-4 border-t border-base-200">
                                    <h3 class="font-medium">
                                        {{ expandedImage?.name || 'Image Preview' }}
                                    </h3>
                                    <p class="text-sm text-base-content/70 mt-1">
                                        {{ formatFileSize(expandedImage?.size) }}
                                    </p>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, watch, onUnmounted } from 'vue';
import { Dialog, DialogPanel, TransitionRoot, TransitionChild } from '@headlessui/vue';
import {
    ImageOff, Maximize2, Download, Plus, Calendar,
    TrendingUp, TrendingDown, X, CheckCircle2, XCircle
} from 'lucide-vue-next';
import { format, formatDistanceToNow } from 'date-fns';

// Types
interface MediaItem {
    url: string | {
        default: string;
        download?: string;
    };
    name?: string;
    size?: number;
    type?: string;
}

interface Column {
    label: string;
    key: string;
    type?: string;
}

// Props
const props = defineProps({
    tableData: {
        type: Object as () => Record<string, any>,
        default: () => ({})
    },
    columns: {
        type: Array as () => Column[],
        default: () => []
    }
});

// State
const expandedImage = ref<MediaItem | null>(null);
const showAllMedia = ref(false);

// Methods
const openImageModal = (image: MediaItem) => {
    expandedImage.value = image;
};

const closeImageModal = () => {
    expandedImage.value = null;
};

const downloadImage = (image: MediaItem) => {
    const downloadUrl = typeof image.url === 'string' ? image.url : image.url?.download;
    if (downloadUrl) {
        window.open(downloadUrl, '_blank');
    }
};

const formatRelativeDate = (date: string): string => {
    if (!date) return '';
    return formatDistanceToNow(new Date(date), { addSuffix: true });
};

const formatPrice = (price: number): string => {
    if (!price) return '0.00';
    return price.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
};

const formatFileSize = (bytes?: number): string => {
    if (!bytes) return '';
    const units = ['B', 'KB', 'MB', 'GB'];
    let size = bytes;
    let unitIndex = 0;

    while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024;
        unitIndex++;
    }

    return `${size.toFixed(1)} ${units[unitIndex]}`;
};

const getTagColor = (key: any): string => {
    if (!key || typeof key !== 'string') return 'badge-neutral';

    const colors = {
        status: 'badge-info',
        type: 'badge-warning',
        category: 'badge-success',
        priority: 'badge-error',
        label: 'badge-primary',
        default: 'badge-neutral'
    };

    return colors[key.toLowerCase()] || colors.default;
};

const getProgressStatus = (value: number) => {
    if (value >= 80) return {
        text: 'Completed',
        class: 'bg-success/20 text-success',
        barClass: 'bg-success'
    };
    if (value >= 50) return {
        text: 'In Progress',
        class: 'bg-info/20 text-info',
        barClass: 'bg-info'
    };
    if (value >= 25) return {
        text: 'Started',
        class: 'bg-warning/20 text-warning',
        barClass: 'bg-warning'
    };
    return {
        text: 'Pending',
        class: 'bg-error/20 text-error',
        barClass: 'bg-error'
    };
};

// Format byte sizes
const formatBytes = (bytes: number, decimals = 2) => {
    if (!bytes) return '0 Bytes';

    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return `${parseFloat((bytes / Math.pow(k, i)).toFixed(decimals))} ${sizes[i]}`;
};

// Format dates
const formatDate = (date: string) => {
    if (!date) return '';
    try {
        const d = new Date(date);
        return d.toLocaleDateString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    } catch (e) {
        return date;
    }
};

// Format numbers with commas
const formatNumber = (num: number) => {
    return new Intl.NumberFormat().format(num);
};

// Computed property for table display data
const tableDisplayInformation = computed(() => {
    return props.columns.map(column => ({
        label: column.label,
        type: column.type || 'default',
        value: (() => {
            const value = props.tableData[column.key];

            try {
                switch (column.type) {
                    case 'media':
                        // Handle media arrays and single items
                        return Array.isArray(value) ? value : (value ? [value] : []);

                    case 'model_search':
                        // Handle search results and ensure object type
                        if (!value) return {};
                        return typeof value === 'object' ? value : { value };

                    case 'number':
                        // Format numbers
                        return typeof value === 'number' ? formatNumber(value) : value;
                    case 'timestamp':
                        // Format laravel timestamps
                        return typeof value === 'string' ? formatDate(value) : '';
                    case 'date':
                        // Format dates
                        return typeof value === 'string' ? formatDate(value) : '';

                    case 'filesize':
                        // Format file sizes
                        return typeof value === 'number' ? formatBytes(value) : 0;
                    case 'boolean':
                        // Handle boolean values
                        return typeof value === 'boolean' ? value :
                            value === 1 ? true :
                                value === '1' ? true :
                                    value === 'true' ? true : false;
                    default:
                        return value ?? '';
                }
            } catch (error) {
                console.error(`Error processing column ${column.key}:`, error);
                return '';
            }
        })(),
        trend: typeof props.tableData[`${column.key}_trend`] === 'number'
            ? props.tableData[`${column.key}_trend`]
            : null
    }));
});

// Watch for modal state changes
watch(expandedImage, (newVal) => {
    if (newVal) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
});

// Clean up on component unmount
onUnmounted(() => {
    document.body.style.overflow = '';
});
</script>

<style scoped>
/* Enhanced Custom Scrollbar */
.custom-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: hsl(var(--p) / 0.3) hsl(var(--b2) / 0.5);
}

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: hsl(var(--b2) / 0.5);
    border-radius: 8px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: hsl(var(--p) / 0.3);
    border-radius: 8px;
    border: 2px solid transparent;
    transition: background-color 0.2s ease;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background-color: hsl(var(--p) / 0.5);
}

/* Media Gallery Animations */
.media-enter-active,
.media-leave-active {
    transition: all 0.3s ease;
    transform-origin: center;
}

.media-enter-from,
.media-leave-to {
    opacity: 0;
    transform: scale(0.95);
}

/* Progress Bar Animation */
.progress-bar {
    transition: width 0.6s ease;
}

/* Modal Transitions */
.modal-enter-active,
.modal-leave-active {
    transition: all 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
    transform: scale(0.95);
}

/* Hover Effects */
.hover-scale {
    transition: transform 0.2s ease;
}

.hover-scale:hover {
    transform: scale(1.05);
}

/* Tag Animations */
.tag-enter-active,
.tag-leave-active {
    transition: all 0.3s ease;
}

.tag-enter-from,
.tag-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

/* Image Hover Effects */
.image-hover {
    transition: all 0.3s ease;
}

.image-hover:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Status Badge Pulse */
@keyframes status-pulse {

    0%,
    100% {
        opacity: 1;
    }

    50% {
        opacity: 0.6;
    }
}

.status-active {
    animation: status-pulse 2s infinite;
}
</style>
