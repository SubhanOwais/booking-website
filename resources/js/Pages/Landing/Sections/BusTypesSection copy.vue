<script setup>
import { ref, onMounted } from 'vue';
import CardCarousel from "@/Components/LandingPage/CardCarousel.vue";
import axios from 'axios';
const busTypes = ref([
    {
        id: 17,
        ServiceType_Id: 17,
        title: "Premium Business",
        ServiceType_Name: "Premium Business",
        SeatNo: 30,
        desc: "Business class luxury with spacious seating and premium services.",
        tone: "secondary",
        image: "https://images.unsplash.com/photo-1570125909232-eb263c188f7e?w=400&h=300&fit=crop&sat=50"
    },
    {
        id: 18,
        ServiceType_Id: 18,
        title: "Premium Business 12X28",
        ServiceType_Name: "Premium Business 12X28",
        SeatNo: 40,
        desc: "Optimized 12x28 layout for ultimate comfort on long-distance routes.",
        tone: "accent",
        image: "https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=400&h=300&fit=crop&sat=-100"
    },
    {
        id: 19,
        ServiceType_Id: 19,
        title: "Premium Business 9X32",
        ServiceType_Name: "Premium Business 9X32",
        SeatNo: 41,
        desc: "Premium 9x32 configuration with extra-wide seats and legroom.",
        tone: "secondary",
        image: "https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=400&h=300&fit=crop"
    },
    {
        id: 20,
        ServiceType_Id: 20,
        title: "Executive Class",
        ServiceType_Name: "Executive Class",
        SeatNo: 48,
        desc: "Top-tier executive experience with luxury amenities and comfort.",
        tone: "accent",
        image: "https://images.unsplash.com/photo-1570125909232-eb263c188f7e?w=400&h=300&fit=crop"
    },
    {
        id: 14,
        ServiceType_Id: 14,
        title: "Executive Plus",
        ServiceType_Name: "Executive Plus",
        SeatNo: 42,
        desc: "Superior comfort with wider seats and advanced amenities for luxury travel.",
        tone: "accent",
        image: "https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=400&h=300&fit=crop&sat=-100"
    },
    {
        id: 13,
        ServiceType_Id: 13,
        title: "Executive",
        ServiceType_Name: "Executive",
        SeatNo: 49,
        desc: "Premium seating with extra legroom and enhanced comfort for business travelers.",
        tone: "secondary",
        image: "https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=400&h=300&fit=crop&sat=-100"
    },
    {
        id: 15,
        ServiceType_Id: 15,
        title: "AC Sleeper",
        ServiceType_Name: "AC SLEEPER",
        SeatNo: 31,
        desc: "Sleep-ready berths with air conditioning for overnight journeys.",
        tone: "secondary",
        image: "https://images.unsplash.com/photo-1570125909232-eb263c188f7e?w=400&h=300&fit=crop&sat=50"
    },
    {
        id: 16,
        ServiceType_Id: 16,
        title: "Executive Plus 41",
        ServiceType_Name: "Executive Plus 41",
        SeatNo: 41,
        desc: "Enhanced executive class with optimized seating for maximum comfort.",
        tone: "accent",
        image: "https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=400&h=300&fit=crop&brightness=110"
    },
]);

// Optional: Fetch from API if needed
// onMounted(async () => {
//     try {
//         const response = await axios.get('/api/service-types');
//         // Filter only IDs 13-20
//         busTypes.value = response.data.filter(type =>
//             type.ServiceType_Id >= 13 && type.ServiceType_Id <= 20
//         );
//     } catch (error) {
//         console.error('Error fetching bus types:', error);
//     }
// });
</script>

<template>
    <section id="bus-types" class="max-w-6xl px-4 py-12 mx-auto sm:py-16">
        <CardCarousel aria-label="bus type" :total="busTypes.length">
            <template #heading>
                <h2 class="text-2xl font-bold text-slate-900 sm:text-3xl">
                    Bus Types
                </h2>
                <p class="max-w-2xl mt-2 text-slate-600">
                    Choose your comfort level — from Executive to Premium Business and Sleeper coaches.
                </p>
            </template>

            <article v-for="t in busTypes" :key="t.ServiceType_Id" data-card
                class="group relative min-w-[280px] overflow-hidden rounded-2xl shadow-sm hover:shadow-md border border-slate-900/10 bg-white transition hover:-translate-y-1 sm:min-w-[320px] lg:min-w-[360px]">
                <!-- Bus Image -->
                <div class="relative h-48 overflow-hidden bg-gradient-to-br from-slate-100 to-slate-200">
                    <img :src="t.image" :alt="t.title"
                        class="object-cover w-full h-full transition duration-300 group-hover:scale-110"
                        @error="(e) => e.target.src = '/images/bus-types/default-bus.jpg'" />
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
                    <div class="absolute px-3 py-1 text-xs font-semibold text-white rounded-full shadow-sm top-3 left-3 backdrop-blur-sm"
                        :class="t.tone === 'accent' ? 'bg-accent/90' : 'bg-secondary/90'">
                        Premium
                    </div>
                </div>

                <!-- Content -->
                <div class="p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">
                                {{ t.title }}
                            </h3>
                            <p class="mt-1 text-xs text-slate-500">
                                Service ID: {{ t.ServiceType_Id }}
                            </p>
                        </div>

                        <!-- Bus Icon -->
                        <div class="flex-shrink-0 rounded-xl p-2.5 transition" :class="t.tone === 'accent'
                            ? 'bg-accent/10 text-accent'
                            : 'bg-secondary/10 text-secondary'
                            ">
                            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
                                aria-hidden="true">
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

                    <p class="mt-3 text-sm leading-relaxed text-slate-600">
                        {{ t.desc }}
                    </p>

                    <!-- Features -->
                    <div class="flex flex-wrap gap-2 mt-4">
                        <span
                            class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded-md bg-slate-100 text-slate-600">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd" />
                            </svg>
                            AC
                        </span>
                        <span
                            class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded-md bg-slate-100 text-slate-600">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd" />
                            </svg>
                            WiFi
                        </span>
                        <span
                            class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded-md bg-slate-100 text-slate-600">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd" />
                            </svg>
                            USB Charging
                        </span>
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-between pt-4 mt-5 border-t border-slate-100">
                        <span class="text-xs text-slate-500">
                            Comfort-focused
                        </span>
                        <button class="inline-flex items-center gap-1 text-sm font-semibold transition group/btn"
                            :class="t.tone === 'accent' ? 'text-accent hover:text-accent/80' : 'text-secondary hover:text-secondary/80'">
                            View Details
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
    </section>
</template>
