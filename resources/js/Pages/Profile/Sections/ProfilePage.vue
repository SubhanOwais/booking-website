<!-- resources/js/Pages/Profile/Sections/ProfilePage.vue -->
<template>
    <WebLayout>
        <div class="min-h-screen p-4 bg-gray-50 md:p-6">
            <div class="mx-auto max-w-7xl">
                <!-- Loading State -->
                <div v-if="!customer || loading" class="py-12 text-center">
                    <div class="inline-block w-12 h-12 mb-4 border-b-2 border-blue-600 rounded-full animate-spin"></div>
                    <p class="text-gray-600">Loading profile...</p>
                </div>

                <!-- Success Message -->
                <div v-if="$page.props.flash?.success" class="p-4 mb-6 border border-green-200 rounded-lg bg-green-50">
                    <div class="flex items-center text-green-800">
                        <i class="mr-2 fas fa-check-circle"></i>
                        {{ $page.props.flash.success }}
                    </div>
                </div>

                <!-- Main Content -->
                <div v-if="customer && !loading" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Left Column -->
                    <div class="space-y-6 lg:col-span-1">
                        <ProfileSidebar :customer="customer" @edit-profile="showEditModal = true" />
                        <ProfileStats :stats="stats" />
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-6 lg:col-span-2">
                        <!-- Tabs Navigation -->
                        <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                            <div class="border-b border-gray-200">
                                <nav class="flex -mb-px">
                                    <button @click="activeTab = 'upcoming'" :class="[
                                        'px-4 py-3 font-medium text-sm border-b-2 transition-colors',
                                        activeTab === 'upcoming'
                                            ? 'border-primary text-primary'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    ]">
                                        Upcoming Trips
                                        <span v-if="upcomingTrips.length > 0"
                                            class="px-2 py-1 ml-2 text-xs text-white rounded-full bg-primary">
                                            {{ upcomingTrips.length }}
                                        </span>
                                    </button>
                                    <button @click="activeTab = 'history'" :class="[
                                        'px-4 py-3 font-medium text-sm border-b-2 transition-colors',
                                        activeTab === 'history'
                                            ? 'border-primary text-primary'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    ]">
                                        Travel History
                                        <span v-if="travelHistory.length > 0"
                                            class="px-2 py-1 ml-2 text-xs text-white rounded-full bg-primary">
                                            {{ travelHistory.length }}
                                        </span>
                                    </button>
                                    <button @click="activeTab = 'cancelled'" :class="[
                                        'px-4 py-3 font-medium text-sm border-b-2 transition-colors',
                                        activeTab === 'cancelled'
                                            ? 'border-primary text-primary'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    ]">
                                        Cancelled/Refunded
                                        <span v-if="cancelledHistory.length > 0"
                                            class="px-2 py-1 ml-2 text-xs text-white rounded-full bg-primary">
                                            {{ cancelledHistory.length }}
                                        </span>
                                    </button>
                                </nav>
                            </div>

                            <!-- Tab Content -->
                            <div class="">
                                <!-- Upcoming Trips Tab -->
                                <div v-show="activeTab === 'upcoming'">
                                    <UpcomingTrips :trips="upcomingTrips" @view-details="viewTicket"
                                        @modify-trip="modifyTrip" />
                                </div>

                                <!-- Travel History Tab -->
                                <div v-show="activeTab === 'history'">
                                    <TravelHistory :history="travelHistory" @view-details="viewTicket" />
                                </div>

                                <!-- Cancelled/Refunded Tab -->
                                <div v-show="activeTab === 'cancelled'">
                                    <CancelledTrips :trips="cancelledHistory" @view-details="viewTicket" />
                                </div>
                            </div>
                        </div>

                        <!-- <PaymentMethods
                            :methods="paymentMethods"
                            @add-method="addPaymentMethod"
                            @remove-method="removePaymentMethod"
                        /> -->
                    </div>
                </div>
            </div>
        </div>
    </WebLayout>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import WebLayout from "@/Layouts/WebLayout.vue";
import ProfileSidebar from "./ProfileSidebar.vue";
import ProfileStats from "./ProfileStats.vue";
import UpcomingTrips from "./UpcomingTrips.vue";
import TravelHistory from "./TravelHistory.vue";
import CancelledTrips from "./CancelledTrips.vue"; // New component
import PaymentMethods from "./PaymentMethods.vue";

const props = defineProps({
    customer: Object,
    stats: Object,
    upcomingTrips: Array,
    travelHistory: Array,
    cancelledHistory: Array, // New prop
    paymentMethods: Array,
});

console.log("Profile props:", props);

// State
const showEditModal = ref(false);
const selectedTicket = ref(null);
const tripToCancel = ref(null);
const loading = ref(false);
const activeTab = ref('upcoming'); // Default active tab

// Ensure customer has default values
const customer = ref({
    name: "",
    email: "",
    phone: "",
    location: "",
    cnic: "",
    memberSince: "",
    ...props.customer,
});

// Ensure stats have default values
const stats = ref({
    totalBookings: 0,
    upcomingTrips: 0,
    cancelled: 0,
    loyaltyPoints: 0,
    ...props.stats,
});

// Ensure arrays have default values
const upcomingTrips = ref(
    Array.isArray(props.upcomingTrips) ? props.upcomingTrips : []
);
const travelHistory = ref(
    Array.isArray(props.travelHistory) ? props.travelHistory : []
);
const cancelledHistory = ref(
    Array.isArray(props.cancelledHistory) ? props.cancelledHistory : []
);
const paymentMethods = ref(
    Array.isArray(props.paymentMethods) ? props.paymentMethods : []
);

// Methods
const viewTicket = (trip) => {
    console.log("View ticket for trip:", trip);
    if (trip && trip.id) {
        router.get(
            `/profile/ticket/${trip.id}`,
            {},
            {
                preserveState: true,
                onSuccess: (data) => {
                    console.log("Ticket data received:", data);
                    selectedTicket.value = data.ticket || data;
                },
                onError: (error) => {
                    console.error("Error loading ticket:", error);
                    alert("Failed to load ticket details");
                },
            }
        );
    }
};

const modifyTrip = (tripId) => {
    alert(`Modify trip ${tripId} - This feature is coming soon!`);
};

const addPaymentMethod = () => {
    alert("Add payment method - This feature is coming soon!");
};

const removePaymentMethod = (cardId) => {
    if (confirm("Are you sure you want to remove this payment method?")) {
        alert(`Payment method ${cardId} removed - This is dummy data`);
    }
};

// Set active tab based on data availability
onMounted(() => {
    console.log("Profile page mounted with props:", props);

    // Update local state when props change
    if (props.customer) {
        customer.value = { ...customer.value, ...props.customer };
    }
    if (props.stats) {
        stats.value = { ...stats.value, ...props.stats };
    }
    if (props.upcomingTrips) {
        upcomingTrips.value = [...props.upcomingTrips];
    }
    if (props.travelHistory) {
        travelHistory.value = [...props.travelHistory];
    }
    if (props.cancelledHistory) {
        cancelledHistory.value = [...props.cancelledHistory];
    }
    if (props.paymentMethods) {
        paymentMethods.value = [...props.paymentMethods];
    }

    // Auto-select tab with data if current tab is empty
    if (upcomingTrips.value.length === 0 && travelHistory.value.length > 0) {
        activeTab.value = 'history';
    } else if (upcomingTrips.value.length === 0 && cancelledHistory.value.length > 0) {
        activeTab.value = 'cancelled';
    }
});
</script>
