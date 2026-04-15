<template>
    <div class="gallery-container" v-if="galleryData || loading">
        <!-- Gallery Section -->
        <div v-if="!loading && processedImages.length > 0">
            <h2 class="text-lg md:text-2xl font-bold text-[#1C231F] mb-2">
                {{ galleryData.ServiceName }} Gallery
            </h2>
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4 lg:grid-cols-6">
                <div
                    v-for="(image, index) in processedImages"
                    :key="index"
                    class="relative overflow-hidden rounded-lg cursor-pointer group aspect-square"
                    @click="showImg(index)"
                >
                    <img
                        :src="image.src"
                        :alt="image.title || `Image ${index + 1}`"
                        class="object-cover w-full h-full transition-transform duration-300 group-hover:scale-110"
                    />
                    <div
                        class="absolute inset-0 flex items-center justify-center transition-opacity duration-300 bg-black opacity-0 bg-opacity-40 group-hover:opacity-100"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-8 h-8 text-white"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"
                            />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="py-4">
            <h2 class="text-lg md:text-2xl font-bold text-[#1C231F] mb-2">
                Gallery
            </h2>
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4 lg:grid-cols-6">
                <div
                    v-for="i in 6"
                    :key="i"
                    class="bg-gray-200 rounded-lg aspect-square animate-pulse"
                ></div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!loading && processedImages.length === 0" class="py-4">
            <h2 class="text-lg md:text-2xl font-bold text-[#1C231F] mb-2">
                Gallery
            </h2>
            <div
                class="p-8 text-center border border-gray-300 border-dashed rounded-lg"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="w-12 h-12 mx-auto text-gray-400"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                    />
                </svg>
                <p class="mt-2 text-gray-500">No images available</p>
            </div>
        </div>

        <!-- Lightbox -->
        <vue-easy-lightbox
            :visible="visible"
            :imgs="lightboxImages"
            :index="currentIndex"
            :moveDisabled="false"
            :enableZoom="true"
            :pinchZoom="true"
            :scrollDisabled="true"
            :rotatable="true"
            :downloadable="true"
            :loop="true"
            escDisabled
            @hide="handleHide"
        >
            <!-- Prev Button -->
            <template #prev-btn="{ prev }">
                <button @click="prev" class="custom-button left-4">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-8 h-8"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 19l-7-7 7-7"
                        />
                    </svg>
                </button>
            </template>

            <!-- Next Button -->
            <template #next-btn="{ next }">
                <button @click="next" class="custom-button right-4">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-8 h-8"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5l7 7-7 7"
                        />
                    </svg>
                </button>
            </template>

            <!-- Custom Toolbar -->
            <template #toolbar="{ toolbarMethods }">
                <div class="custom-toolbar">
                    <button
                        @click="toolbarMethods.zoomIn"
                        class="toolbar-btn"
                        title="Zoom In"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-6 h-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"
                            />
                        </svg>
                    </button>
                    <button
                        @click="toolbarMethods.zoomOut"
                        class="toolbar-btn"
                        title="Zoom Out"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-6 h-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM5 10h14"
                            />
                        </svg>
                    </button>
                    <button
                        @click="toolbarMethods.rotate"
                        class="toolbar-btn"
                        title="Rotate"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-6 h-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                            />
                        </svg>
                    </button>
                    <button
                        @click="downloadCurrentImage"
                        class="toolbar-btn"
                        title="Download"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-6 h-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                            />
                        </svg>
                    </button>
                </div>
            </template>
        </vue-easy-lightbox>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import VueEasyLightbox from "vue-easy-lightbox";
import axios from "axios";

const props = defineProps({
    serviceId: {
        type: [Number, String],
        required: true,
    },
});

// State
const loading = ref(false);
const galleryData = ref(null);
const visible = ref(false);
const currentIndex = ref(0);

// Fetch gallery data
const fetchGallery = async () => {
    if (!props.serviceId) return;

    loading.value = true;
    galleryData.value = null;

    try {
        // ✅ ServiceType in API is a string — always pass as string
        // const response = await axios.get(
        //     `/api/web-bus-gallery/${props.serviceId}`
        // );

        console.log("API Response:", response.data);

        if (response.data.status && response.data.data) {
            // ✅ Store the full data object (has Images, ServiceName, etc.)
            galleryData.value = response.data.data;
        } else {
            galleryData.value = null;
        }
    } catch (error) {
        console.error("Error fetching gallery:", error);
        galleryData.value = null;
    } finally {
        loading.value = false;
    }
};

// Process gallery images from comma-separated string
const processedImages = computed(() => {
    if (!galleryData.value?.Images) return [];

    try {
        return galleryData.value.Images
            .split(",")
            .map((img) => img.trim())
            .filter(Boolean)
            .map((img, index) => {
                // Clean double slashes and leading slash
                let cleanPath = img.replace(/\/\//g, "/").replace(/^\//, "");

                const filename = cleanPath.split("/").pop();
                const serviceName = galleryData.value?.ServiceName || "Bus";

                return {
                    src:      `${window.location.origin}/storage/${cleanPath}`,
                    title:    `${serviceName} - Image ${index + 1}`,
                    filename: filename,
                };
            });
    } catch (e) {
        console.error("Error processing images:", e);
        return [];
    }
});

// Lightbox images (just URLs)
const lightboxImages = computed(() => {
    return processedImages.value.map((img) => img.src);
});

// Lightbox methods
const showImg = (index) => {
    currentIndex.value = index;
    visible.value = true;
};

const handleHide = () => {
    visible.value = false;
    currentIndex.value = 0;
};

// Download current image
const downloadCurrentImage = () => {
    if (
        processedImages.value.length === 0 ||
        currentIndex.value >= processedImages.value.length
    ) {
        return;
    }

    const image = processedImages.value[currentIndex.value];
    const link = document.createElement("a");
    link.href = image.src;
    link.download = image.filename || `bus-image-${currentIndex.value + 1}`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

// Fetch gallery when component mounts or serviceId changes
onMounted(() => {
    fetchGallery();
});

watch(
    () => props.serviceId,
    () => {
        fetchGallery();
    }
);
</script>

<style scoped>
.gallery-container {
    @apply mt-6;
}

.custom-button {
    @apply absolute top-1/2 transform -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white rounded-full p-2 transition-all duration-200 z-50;
}

.custom-toolbar {
    @apply absolute bottom-4 left-1/2 transform -translate-x-1/2 flex gap-2 bg-black/50 backdrop-blur-sm rounded-full p-2 z-50;
}

.toolbar-btn {
    @apply bg-white/10 hover:bg-white/20 text-white rounded-full p-2 transition-all duration-200 flex items-center justify-center;
}

/* Aspect ratio for gallery items */
.aspect-square {
    aspect-ratio: 1 / 1;
}

/* Hover effects */
.group:hover img {
    transform: scale(1.1);
}
</style>
