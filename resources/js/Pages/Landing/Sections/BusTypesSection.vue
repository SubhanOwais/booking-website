<script setup>
import { ref, onMounted, computed, nextTick } from 'vue';
import CardCarousel from "@/Components/LandingPage/CardCarousel.vue";
import axios from 'axios';

const busTypes = ref([]);
const loading = ref(true);
const error = ref(null);
const showDebug = ref(false); // Add this for debugging

// Fetch bus gallery data from API
const fetchBusGallery = async () => {
    loading.value = true;
    error.value = null;

    try {
        console.log('Fetching bus gallery data...');
        const response = await axios.get('/api/bus-gallery/bus-types');
        console.log('API Response:', response.data);

        if (response.data.status) {
            console.log('Data received:', response.data.data);

            // Transform API data to match component structure
            const transformedData = response.data.data.map(gallery => {
                const item = {
                    id: gallery.id,
                    ServiceType_Id: gallery.ServiceType, // Default to 13 if not provided
                    title: gallery.ServiceName,
                    ServiceType_Name: gallery.ServiceName,
                    SeatNo: extractSeatNumber(gallery),
                    desc: gallery.Paragraph || 'Premium bus service with modern amenities.',
                    tone: getRandomTone(),
                    image: getImageUrl(gallery),
                    features: extractFeatures(gallery),
                    rawData: gallery
                };
                console.log('Transformed item:', item);
                return item;
            });

            busTypes.value = transformedData;
            console.log('Total transformed items:', busTypes.value.length);
        } else {
            error.value = 'Failed to load bus gallery data';
            console.error('API returned false status:', response.data);
        }
    } catch (err) {
        console.error('Error fetching bus gallery:', err);
        error.value = 'Unable to load bus types. Please try again later.';
    } finally {
        loading.value = false;
        // Force re-render after data is loaded
        nextTick(() => {
            console.log('Component re-rendered. Bus types count:', busTypes.value.length);
        });
    }
};

// Helper function to extract seat number
const extractSeatNumber = (gallery) => {
    // Default seat numbers based on service type or name
    const defaultSeats = {
        '13': 49,  // Executive
        '14': 42,  // Executive Plus
        '15': 31,  // AC Sleeper
        '16': 41,  // Executive Plus 41
        '17': 30,  // Premium Business
        '18': 40,  // Premium Business 12X28
        '19': 41,  // Premium Business 9X32
        '20': 48   // Executive Class
    };

    // Try to get from service type
    if (gallery.ServiceType && defaultSeats[gallery.ServiceType]) {
        return defaultSeats[gallery.ServiceType];
    }

    // Default to 40 if not found
    return 40;
};

// Helper function to extract features from tags
const extractFeatures = (gallery) => {
    // if (!gallery.Tags) {
    //     return ['AC', 'WiFi', 'USB Charging'];
    // }

    // Split tags by comma and take first 3
    const tags = gallery.Tags.split(',').map(tag => tag.trim());
    return tags.slice(0, 3);
};

// Helper function to get image URL
const getImageUrl = (gallery) => {
    // Priority: MainImage -> First image from Images -> Fallback
    if (gallery.MainImage) {
        const cleanPath = gallery.MainImage.replace(/\/\//g, '/').replace(/^\//, '');
        return `${window.location.origin}/storage/${cleanPath}`;
    }

    if (gallery.Images) {
        const images = gallery.Images.split(',')
            .map(img => img.trim())
            .filter(img => img);

        if (images.length > 0) {
            // Clean the image path
            let cleanPath = images[0].replace(/\/\//g, '/');
            if (cleanPath.startsWith('/')) {
                cleanPath = cleanPath.substring(1);
            }
            return `${window.location.origin}/storage/${cleanPath}`;
        }
    }

    // Fallback to default images based on service name
    return getDefaultImage(gallery.ServiceName);
};

// Helper function to get default image
const getDefaultImage = (serviceName) => {
    const imageMap = {
        'Premium': 'https://images.unsplash.com/photo-1570125909232-eb263c188f7e?w=400&h=300&fit=crop',
        'Executive': 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=400&h=300&fit=crop',
        'Sleeper': 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=400&h=300&fit=crop',
        'Business': 'https://images.unsplash.com/photo-1568495246076-368b2d7b2e6f?w=400&h=300&fit=crop'
    };

    const lowerName = serviceName.toLowerCase();
    for (const [key, url] of Object.entries(imageMap)) {
        if (lowerName.includes(key.toLowerCase())) {
            return url;
        }
    }

    // Default bus image
    return 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=400&h=300&fit=crop';
};

// Helper function to get random tone for styling
const getRandomTone = () => {
    const tones = ['accent', 'secondary'];
    return tones[Math.floor(Math.random() * tones.length)];
};

// Filtered bus types (for any additional filtering)
const filteredBusTypes = computed(() => {
    console.log('Filtered bus types count:', busTypes.value.length);
    return busTypes.value;
});

// Handle image errors
const handleImageError = (event, fallbackUrl) => {
    console.error('Image failed to load:', event.target.src);
    event.target.src = fallbackUrl || '/images/bus-types/default-bus.jpg';
};

// Handle view details click
const handleViewDetails = (busType) => {
    console.log('View details for:', busType.title);
    // You can navigate to gallery detail page or show modal
    // window.location.href = `/gallery/${busType.id}`;
};

// Fetch data on component mount
onMounted(() => {
    console.log('BusTypes component mounted');
    fetchBusGallery();
});
</script>

<template>
    <section id="bus-types" class="max-w-6xl px-4 py-12 mx-auto sm:py-12">

        <!-- Loading State -->
        <div v-if="loading" class="py-8 text-center">
            <div class="inline-block w-12 h-12 border-b-2 rounded-full animate-spin border-primary"></div>
            <p class="mt-4 text-slate-600">Loading bus gallery...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="py-8 text-center">
            <div class="inline-flex items-center justify-center w-12 h-12 mb-4 text-red-600 bg-red-100 rounded-full">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h3 class="mb-2 text-lg font-semibold text-slate-900">Unable to Load Bus Gallery</h3>
            <p class="mb-4 text-slate-600">{{ error }}</p>
            <button @click="fetchBusGallery"
                class="inline-flex items-center px-4 py-2 text-white transition rounded-lg bg-primary hover:bg-primary-dark">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Try Again
            </button>
        </div>

        <!-- Success State -->
        <div v-else-if="filteredBusTypes.length > 0">
            <!-- With CardCarousel -->
            <CardCarousel aria-label="bus type" :total="filteredBusTypes.length">
                <template #heading>
                    <h2 class="text-2xl font-bold text-slate-900 sm:text-3xl">
                        Bus Gallery
                    </h2>
                    <p class="max-w-2xl mt-2 text-slate-600">
                        Explore our premium bus collection with modern amenities and comfortable seating.
                    </p>
                    <p class="mt-1 text-sm text-slate-500">
                        Showing {{ filteredBusTypes.length }} bus {{ filteredBusTypes.length === 1 ? 'type' : 'types' }}
                    </p>
                </template>
                <article v-for="t in filteredBusTypes" :key="t.id" data-card
                    class="group relative min-w-[280px] overflow-hidden rounded-2xl shadow-sm hover:shadow-md border border-slate-900/10 bg-white transition hover:-translate-y-1 sm:min-w-[320px] lg:min-w-[360px]">
                    <!-- Bus Image -->
                    <div class="relative h-48 overflow-hidden bg-gradient-to-br from-slate-100 to-slate-200">
                        <img :src="t.image" :alt="t.title"
                            class="object-cover w-full h-full transition duration-300 group-hover:scale-110"
                            @error="(e) => handleImageError(e, getDefaultImage(t.title))" />
                        <!-- Overlay gradient -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>

                        <!-- Seats badge on image -->
                        <div
                            class="absolute top-3 right-3 flex items-center gap-1.5 rounded-full bg-white/95 backdrop-blur-sm px-3 py-1.5 shadow-sm">
                            <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span class="text-sm font-semibold text-slate-700">{{ t.SeatNo }} Seats</span>
                        </div>

                        <!-- Type badge on image -->
                        <div
                            class="absolute px-3 py-1 text-xs font-semibold text-white rounded-full shadow-sm top-3 left-3 backdrop-blur-sm bg-primary">
                            {{ t.rawData?.Type || 'Bus Gallery' }}
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900 line-clamp-1">
                                    {{ t.title }}
                                </h3>
                                <p class="mt-1 text-xs text-slate-500">
                                    Service ID: {{ t.ServiceType_Id }}
                                </p>
                            </div>

                            <!-- Bus Icon -->
                            <div class="flex-shrink-0 rounded-xl p-2.5 transition bg-primary/10 text-primary/80" ">
                                <svg class=" w-6 h-6 " viewBox=" 0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M6 16V7.8c0-1.7 1.3-3.1 3-3.3h6c1.7.2 3 1.6 3 3.3V16" stroke="currentColor"
                                    stroke-width="1.6" stroke-linecap="round" />
                                <path d="M6 12h12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                                <path d="M8 16h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                                <path
                                    d="M7 19a1.8 1.8 0 1 0 3.6 0A1.8 1.8 0 0 0 7 19ZM13.4 19a1.8 1.8 0 1 0 3.6 0 1.8 1.8 0 0 0-3.6 0Z"
                                    fill="currentColor" />
                                </svg>
                            </div>
                        </div>

                        <p class="mt-3 text-sm leading-relaxed text-slate-600 line-clamp-2">
                            {{ t.desc }}
                        </p>

                        <!-- Features -->
                        <div class="flex flex-wrap gap-2 mt-4">
                            <span v-for="(feature, index) in t.features" :key="index"
                                class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded-md bg-primary text-white">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd" />
                                </svg>
                                {{ feature }}
                            </span>
                        </div>

                        <!-- Footer -->
                        <div class="flex items-center justify-between pt-4 mt-5 border-t border-slate-100">
                            <span class="text-xs text-primary">
                                {{ t.rawData?.Type || 'Bus Gallery' }}
                            </span>
                            <button @click="handleViewDetails(t)"
                                class="inline-flex items-center gap-1 text-sm font-semibold transition group/btn text-primary hover:text-primary/80">
                                View Gallery
                                <svg class="h-4 w-4 transition group-hover/btn:translate-x-0.5" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </article>
            </CardCarousel>
        </div>

        <!-- Empty State -->
        <div v-else class="py-12 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 mb-4 rounded-full bg-slate-100">
                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                </svg>
            </div>
            <h3 class="mb-2 text-lg font-semibold text-slate-900">No Bus Gallery Available</h3>
            <p class="max-w-md mx-auto text-slate-600">
                Currently, there are no bus gallery items to display. Please check back later.
            </p>
        </div>
    </section>
</template>

<style scoped>
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.group:hover img {
    transform: scale(1.1);
}
</style>
