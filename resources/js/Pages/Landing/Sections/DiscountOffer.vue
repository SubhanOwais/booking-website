<script setup>
import { computed, onMounted } from "vue";
import { router }              from "@inertiajs/vue3";
import { useDiscountStore }    from "@/stores/discountStore";

// ── Props ──────────────────────────────────────────────────────────────────
const props = defineProps({
    cityId: {
        type:    [Number, String],
        default: null,
    },
});

// ── Store ──────────────────────────────────────────────────────────────────
const discountStore = useDiscountStore();

// ── Computed — filter by cityId prop if provided ───────────────────────────
const discounts = computed(() => {
    // If Navbar already fetched — this is instant (no API call)
    const all = props.cityId
        ? discountStore.discounts.filter(
              (d) => String(d.main_city_id) === String(props.cityId)
          )
        : discountStore.discounts;

    return all;
});

const loading = computed(() => discountStore.loading);
const error   = computed(() => discountStore.error);

// ── Lifecycle — only fetches if Navbar hasn't already ─────────────────────
onMounted(() => {
    discountStore.fetchDiscounts(); // guard inside: skips if already loaded
});

// ── Helpers ────────────────────────────────────────────────────────────────
const formatDate = (dateString) => {
    if (!dateString) return "N/A";
    return new Date(dateString).toLocaleDateString("en-US", {
        year:  "numeric",
        month: "short",
        day:   "numeric",
    });
};

const getStatusText = (status) => ({
    active:   "Active Now",
    upcoming: "Coming Soon",
    expired:  "Expired",
    inactive: "Inactive",
}[status] ?? "Inactive");

const getButtonText = (status) => ({
    active:   "Apply Discount",
    upcoming: "Coming Soon",
    expired:  "Expired",
    inactive: "Not Available",
}[status] ?? "Not Available");

// ── Apply discount → navigate to booking ──────────────────────────────────
const applyDiscount = (discount) => {
    if (discount.status !== "active") return;

    const twoDaysFromNow = new Date();
    twoDaysFromNow.setDate(twoDaysFromNow.getDate() + 2);
    const date = twoDaysFromNow.toISOString().split("T")[0];

    const from = discount.main_city_name;
    const to   = discount.mapped_city_names_array?.length > 0
        ? discount.mapped_city_names_array[0]
        : "";

    if (!to) {
        console.warn("No destination city found for discount:", discount);
        return;
    }

    // ✅ Use Inertia router instead of window.location — preserves SPA behaviour
    router.get(route("booking"), { from, to, date });
};
</script>

<template>
    <section
        v-if="discounts.length > 0 || loading"
        class="max-w-6xl px-4 py-12 mx-auto sm:py-16"
    >
        <!-- Section Header -->
        <div class="mb-6 text-center">
            <span class="inline-block px-4 py-2 mb-3 text-sm font-semibold tracking-wider uppercase rounded-full text-primary bg-primary/10">
                Limited Time Offers
            </span>
            <h2 class="mb-2 text-4xl font-bold text-gray-900">
                Exclusive Discount Offers
            </h2>
            <p class="max-w-2xl mx-auto text-lg text-gray-600">
                Save big on your rides with our special city-wide discounts. Don't miss out!
            </p>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex flex-col items-center justify-center py-20">
            <div class="w-16 h-16 border-4 rounded-full border-primary/20 border-t-primary animate-spin"></div>
            <p class="mt-4 text-gray-600">Loading amazing offers...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="max-w-md p-6 mx-auto border border-red-200 rounded-lg bg-red-50">
            <div class="flex items-center">
                <svg class="w-6 h-6 mr-3 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-red-800">{{ error }}</p>
            </div>
        </div>

        <!-- Discounts Grid -->
        <div v-else-if="discounts.length > 0" class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="discount in discounts"
                :key="discount.id"
                class="relative overflow-hidden transition-all duration-300 transform bg-white border-2 shadow-lg border-primary group rounded-2xl hover:shadow-2xl hover:-translate-y-2"
                :class="{ 'opacity-60': discount.status !== 'active' }"
            >
                <!-- Discount Badge -->
                <div class="absolute z-10 top-4 right-4">
                    <div class="relative">
                        <div class="flex z-[60] relative flex-col items-center justify-center w-20 h-20 text-white transition-transform duration-300 transform rounded-full shadow bg-secondary group-hover:scale-110">
                            <span class="text-2xl font-bold text-white">{{ discount.discount_percentage }}%</span>
                            <span class="text-xs font-semibold text-white">OFF</span>
                        </div>
                        <div class="absolute inset-0 rounded-full opacity-50 bg-primary blur-xl animate-pulse"></div>
                    </div>
                </div>

                <!-- Card Content -->
                <div class="p-6 h-[-webkit-fill-available] flex flex-col">
                    <!-- Status Badge -->
                    <div class="mb-4">
                        <span class="inline-flex items-center px-3 py-1 text-xs font-semibold text-green-800 bg-green-100 border border-green-400 rounded-full">
                            <span class="w-2 h-2 mr-2 bg-green-800 rounded-full animate-pulse"></span>
                            {{ getStatusText(discount.status) }}
                        </span>
                    </div>

                    <!-- Discount Name -->
                    <h3 class="pr-16 mb-4 text-xl font-bold text-gray-900">{{ discount.name }}</h3>

                    <!-- City Information -->
                    <div class="mb-4 space-y-2">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-primary mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-gray-900">{{ discount.main_city_name }}</p>
                                <p v-if="discount.mapped_city_names_array?.length > 0" class="text-xs text-gray-500">
                                    + {{ discount.mapped_city_names_array.length }}
                                    {{ discount.mapped_city_names_array.length === 1 ? "city" : "cities" }}
                                </p>
                            </div>
                        </div>

                        <!-- Mapped city tags -->
                        <div v-if="discount.mapped_city_names_array?.length > 0" class="flex flex-wrap gap-1 mt-2">
                            <span
                                v-for="city in discount.mapped_city_names_array.slice(0, 3)"
                                :key="city"
                                class="inline-block px-2 py-1 text-xs rounded text-primary bg-primary/10"
                            >
                                {{ city }}
                            </span>
                            <span
                                v-if="discount.mapped_city_names_array.length > 3"
                                class="inline-block px-2 py-1 text-xs rounded text-primary bg-primary/10"
                            >
                                +{{ discount.mapped_city_names_array.length - 3 }} more
                            </span>
                        </div>
                    </div>

                    <!-- End Date -->
                    <div v-if="discount.end_date" class="mb-3">
                        <div class="flex items-center text-sm text-gray-600">
                            <svg class="w-4 h-4 mr-2 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-xs">
                                Ends <span class="font-semibold text-gray-900">{{ formatDate(discount.end_date) }}</span>
                            </span>
                        </div>
                    </div>

                    <!-- Company tags -->
                    <div v-if="discount.company_names?.length" class="flex flex-wrap gap-2 mb-3">
                        <span
                            v-for="(company, i) in discount.company_names"
                            :key="i"
                            class="inline-flex items-center px-3 py-1 text-xs font-semibold text-primary bg-primary/10 border border-primary/20 rounded-full"
                        >
                            <span class="w-2 h-2 mr-2 bg-primary rounded-full animate-pulse"></span>
                            {{ company }}
                        </span>
                    </div>

                    <!-- Action Button -->
                    <button
                        @click="applyDiscount(discount)"
                        :disabled="discount.status !== 'active'"
                        class="flex items-center justify-center w-full px-4 py-3 mt-auto text-sm font-semibold text-white transition-all duration-300 transform rounded-lg shadow-lg bg-primary hover:bg-secondary hover:shadow-xl hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none disabled:hover:bg-primary"
                    >
                        <span>{{ getButtonText(discount.status) }}</span>
                        <svg v-if="discount.status === 'active'" class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </button>
                </div>

                <!-- Decorative bottom border -->
                <div class="absolute bottom-0 left-0 w-full h-1 transition-transform duration-300 origin-left transform scale-x-0 bg-primary group-hover:scale-x-100"></div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="py-20 text-center">
            <div class="inline-flex items-center justify-center w-20 h-20 mb-6 bg-gray-100 rounded-full">
                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h3 class="mb-2 text-2xl font-bold text-gray-900">No Discounts Available</h3>
            <p class="text-gray-600">Check back soon for exciting offers!</p>
        </div>
    </section>
</template>
