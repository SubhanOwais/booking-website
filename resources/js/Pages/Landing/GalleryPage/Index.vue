<template>
    <WebLayout class="min-h-screen bg-gray-50">

        <!-- Hero Section -->
        <header class="relative overflow-hidden">
            <div class="absolute inset-0 z-0">
                <div class="absolute inset-0 bg-gradient-to-br from-primary via-primary to-slate-900"></div>
                <div class="absolute rounded-full -top-40 -left-32 h-96 w-96 bg-secondary/25 blur-3xl"></div>
                <div class="absolute rounded-full -bottom-40 -right-32 h-96 w-96 bg-accent/25 blur-3xl"></div>
                <div class="absolute inset-0 opacity-20" style="
                        background-image: radial-gradient(
                            circle at 1px 1px,
                            rgba(255, 255, 255, 0.12) 1px,
                            transparent 0
                        );
                        background-size: 22px 22px;
                    "></div>
            </div>

            <div class="relative z-20 max-w-6xl px-4 py-10 mx-auto sm:py-12">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm text-white/80">Safar Galleries</p>
                        <h1 class="mt-1 text-2xl font-bold tracking-tight text-white sm:text-3xl">
                            Galleries
                        </h1>
                        <p class="max-w-2xl mt-2 text-white/75">
                            Explore our collection of buses, events, and upcoming additions
                        </p>
                    </div>

                    <Link :href="route('home')"
                        class="inline-flex items-center justify-center rounded-xl border border-white/25 bg-white/10 px-4 py-2 text-sm font-semibold text-white backdrop-blur transition hover:-translate-y-0.5 hover:bg-white/15">
                        Back to Home
                    </Link>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-6xl px-4 py-10 mx-auto">
            <!-- Filter Tabs -->
            <div class="mb-8">
                <div class="sm:hidden">
                    <select v-model="activeTab"
                        class="block w-full py-2 pl-3 pr-10 text-base border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                        <option value="all">All ({{ totalCount }})</option>
                        <option value="Bus Gallery">Bus Gallery ({{ typeCounts['Bus Gallery'] || 0 }})</option>
                        <option value="Events">Events ({{ typeCounts['Events'] || 0 }})</option>
                        <option value="Up Coming Buses">Upcoming Buses ({{ typeCounts['Up Coming Buses'] || 0 }})
                        </option>
                    </select>
                </div>

                <div class="hidden sm:block">
                    <div class="border-b border-gray-200">
                        <nav class="flex -mb-px space-x-4">
                            <button @click="setActiveTab('all')" :class="[
                                'whitespace-nowrap py-2 px-4 border-b-2 font-medium text-sm filex justify-between items-center',
                                activeTab === 'all'
                                    ? 'border-primary text-white bg-primary rounded-lg mb-1'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            ]">
                                All Galleries
                                <span :class="['ml-2 py-0.5 px-2 rounded-full text-xs',
                                    activeTab === 'all'
                                        ? ' bg-white text-gray-900'
                                        : ' bg-secondary text-gray-900'
                                ]">
                                    {{ totalCount }}
                                </span>
                            </button>

                            <button @click="setActiveTab('Bus Gallery')" :class="[
                                'whitespace-nowrap p-2 border-b-2 font-medium text-sm filex justify-between items-center',
                                activeTab === 'Bus Gallery'
                                    ? 'border-primary text-white bg-primary rounded-lg mb-1'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            ]">
                                Bus Gallery
                                <span :class="['ml-2 py-0.5 px-2 rounded-full text-xs',
                                    activeTab === 'Bus Gallery'
                                        ? ' bg-white text-gray-900'
                                        : ' bg-secondary text-gray-900'
                                ]">
                                    {{ typeCounts['Bus Gallery'] || 0 }}
                                </span>
                            </button>

                            <button @click="setActiveTab('Events')" :class="[
                                'whitespace-nowrap p-2 border-b-2 font-medium text-sm filex justify-between items-center',
                                activeTab === 'Events'
                                    ? 'border-primary text-white bg-primary rounded-lg mb-1'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            ]">
                                Events
                                <span :class="['ml-2 py-0.5 px-2 rounded-full text-xs',
                                    activeTab === 'Events'
                                        ? ' bg-white text-gray-900'
                                        : ' bg-secondary text-gray-900'
                                ]">
                                    {{ typeCounts['Events'] || 0 }}
                                </span>
                            </button>

                            <button @click="setActiveTab('Up Coming Buses')" :class="[
                                'whitespace-nowrap p-2 border-b-2 font-medium text-sm filex justify-between items-center',
                                activeTab === 'Up Coming Buses'
                                    ? 'border-primary text-white bg-primary rounded-lg mb-1'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            ]">
                                Upcoming Buses
                                <span :class="['ml-2 py-0.5 px-2 rounded-full text-xs',
                                    activeTab === 'Up Coming Buses'
                                        ? ' bg-white text-gray-900'
                                        : ' bg-secondary text-gray-900'
                                ]">
                                    {{ typeCounts['Up Coming Buses'] || 0 }}
                                </span>
                            </button>
                        </nav>
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="i in 6" :key="i" class="overflow-hidden bg-white rounded-lg shadow animate-pulse">
                    <div class="h-48 bg-gray-300"></div>
                    <div class="p-4">
                        <div class="w-3/4 h-4 mb-2 bg-gray-200 rounded"></div>
                        <div class="w-1/2 h-3 bg-gray-200 rounded"></div>
                    </div>
                </div>
            </div>

            <!-- Gallery Grid -->
            <div v-else-if="filteredGalleries.length > 0" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="gallery in filteredGalleries" :key="gallery.id"
                    class="overflow-hidden transition-shadow duration-300 bg-white rounded-lg shadow hover:shadow-xl">

                    <!-- Image Section -->
                    <div class="relative h-48 overflow-hidden cursor-pointer group" @click="openGallery(gallery)">
                        <img :src="getMainImage(gallery)" :alt="gallery.ServiceName"
                            class="object-cover w-full h-full transition-transform duration-500 group-hover:scale-110" />
                        <div
                            class="absolute inset-0 flex items-center justify-center transition-all duration-300 bg-black bg-opacity-0 group-hover:bg-opacity-30">
                            <div
                                class="transition-transform duration-300 transform translate-y-4 opacity-0 group-hover:translate-y-0 group-hover:opacity-100">
                                <span
                                    class="px-3 py-1 text-sm font-semibold text-white bg-black bg-opacity-50 rounded-full">
                                    View Gallery
                                </span>
                            </div>
                        </div>

                        <!-- Type Badge -->
                        <div class="absolute top-3 left-3">
                            <span :class="[
                                'px-2 py-1 text-xs font-semibold rounded-full',
                                getTypeBadgeClass(gallery.Type)
                            ]">
                                {{ gallery.Type }}
                            </span>
                        </div>
                    </div>

                    <!-- Content Section -->
                    <div class="p-4">
                        <h3 class="mb-2 text-lg font-semibold text-gray-900">
                            {{ gallery.ServiceName }}
                        </h3>

                        <p class="mb-3 text-sm text-gray-600 line-clamp-2">
                            {{ truncateText(gallery.Paragraph, 100) }}
                        </p>

                        <!-- Image Count -->
                        <div class="flex items-center text-sm text-gray-500">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span>{{ getImageCount(gallery) }} images</span>
                        </div>

                        <!-- Tags -->
                        <div v-if="gallery.Tags" class="mt-3">
                            <div class="flex flex-wrap gap-1">
                                <span v-for="(tag, index) in gallery.Tags.split(',')" :key="index"
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-secondary text-gray-800">
                                    {{ tag.trim() }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="py-12 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 mb-4 bg-gray-100 rounded-full">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <h3 class="mb-2 text-lg font-medium text-gray-900">No galleries found</h3>
                <p class="max-w-md mx-auto text-gray-500">
                    {{ activeTab === 'all'
                        ? 'No galleries available at the moment.'
                        : `No ${activeTab} found. Try selecting a different category.`
                    }}
                </p>
                <button v-if="activeTab !== 'all'" @click="setActiveTab('all')"
                    class="inline-flex items-center px-4 py-2 mt-4 text-sm font-medium text-white border border-transparent rounded-md shadow-sm bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    View All Galleries
                </button>
            </div>
        </main>

        <!-- Lightbox Modal -->
        <vue-easy-lightbox :visible="showLightbox" :imgs="currentGalleryImages" :index="lightboxIndex"
            @hide="closeLightbox">
            <template #toolbar="{ toolbarMethods }">
                <div class="custom-toolbar">
                    <button @click="toolbarMethods.zoomIn" class="toolbar-btn" title="Zoom In">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                        </svg>
                    </button>
                    <button @click="toolbarMethods.zoomOut" class="toolbar-btn" title="Zoom Out">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM5 10h14" />
                        </svg>
                    </button>
                    <button @click="toolbarMethods.rotate" class="toolbar-btn" title="Rotate">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                </div>
            </template>
        </vue-easy-lightbox>
    </WebLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';
import VueEasyLightbox from 'vue-easy-lightbox';
import axios from 'axios';
import WebLayout from "@/Layouts/WebLayout.vue";

// State
const loading = ref(true);
const galleries = ref([]);
const typeCounts = ref({});
const totalCount = ref(0);
const activeTab = ref('all');
const showLightbox = ref(false);
const currentGalleryImages = ref([]);
const lightboxIndex = ref(0);

// Fetch all galleries
const fetchGalleries = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/galleries');

        if (response.data.status) {
            galleries.value = response.data.data;
            typeCounts.value = response.data.type_counts || {};
            totalCount.value = response.data.total || 0;
        }
    } catch (error) {
        console.error('Error fetching galleries:', error);
        galleries.value = [];
    } finally {
        loading.value = false;
    }
};

// Filter galleries based on active tab
const filteredGalleries = computed(() => {
    if (activeTab.value === 'all') {
        return galleries.value;
    }
    return galleries.value.filter(gallery => gallery.Type === activeTab.value);
});

// Helper functions
const getMainImage = (gallery) => {
    if (gallery.MainImage) {
        return `${window.location.origin}/storage/${gallery.MainImage}`;
    }

    // If no main image, try to get first image from Images
    if (gallery.Images) {
        const images = gallery.Images.split(',').map(img => img.trim()).filter(img => img);
        if (images.length > 0) {
            return `${window.location.origin}/storage/${images[0]}`;
        }
    }

    // Fallback placeholder
    return 'https://via.placeholder.com/400x300?text=No+Image';
};

const getImageCount = (gallery) => {
    if (!gallery.Images) return 0;
    const images = gallery.Images.split(',').map(img => img.trim()).filter(img => img);
    return images.length;
};

const truncateText = (text, length) => {
    if (!text) return '';
    return text.length > length ? text.substring(0, length) + '...' : text;
};

const getTypeBadgeClass = (type) => {
    const classes = {
        'Bus Gallery': 'bg-blue-100 text-blue-800',
        'Events': 'bg-green-100 text-green-800',
        'Up Coming Buses': 'bg-purple-100 text-purple-800'
    };
    return classes[type] || 'bg-gray-100 text-gray-800';
};

// Open gallery in lightbox
const openGallery = (gallery) => {
    if (!gallery.Images) return;

    const images = gallery.Images.split(',')
        .map(img => img.trim())
        .filter(img => img)
        .map(img => ({
            src: `${window.location.origin}/storage/${img}`,
            title: gallery.ServiceName
        }));

    if (images.length === 0) return;

    currentGalleryImages.value = images.map(img => img.src);
    lightboxIndex.value = 0;
    showLightbox.value = true;
};

const closeLightbox = () => {
    showLightbox.value = false;
    currentGalleryImages.value = [];
    lightboxIndex.value = 0;
};

const setActiveTab = (tab) => {
    activeTab.value = tab;
};

// Initialize
onMounted(() => {
    fetchGalleries();
});
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.custom-toolbar {
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 10px;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 8px 16px;
    z-index: 1000;
}

.toolbar-btn {
    background: transparent;
    border: none;
    color: white;
    padding: 8px;
    border-radius: 50%;
    cursor: pointer;
    transition: background-color 0.2s;
}

.toolbar-btn:hover {
    background: rgba(255, 255, 255, 0.2);
}
</style>
