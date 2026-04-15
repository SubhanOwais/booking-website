<!-- resources/js/Pages/Profile/Sections/TravelHistory.vue -->
<template>
    <div class="divide-y divide-gray-100">
        <div v-for="trip in displayedHistory" :key="trip.id"
            class="p-6 transition bg-white rounded-lg hover:bg-gray-50">
            <div class="flex flex-col justify-between md:flex-row md:items-center">
                <div class="mb-4 md:mb-0">
                    <div class="flex items-center">
                        <!-- <div class="flex items-center justify-center w-12 h-12 mr-3 bg-gray-200 rounded-full">
                                <i class="text-gray-600 fas fa-bus"></i>
                            </div> -->
                        <img :src="trip.logo || '/images/logo.jpg'" class="w-12 h-auto mr-3 rounded-lg"  />
                        <div>
                            <h4 class="font-bold text-gray-800">
                                {{ trip.from }} → {{ trip.to }}
                            </h4>
                            <p class="text-sm text-gray-600">
                                {{ trip.date }} • {{ trip.time }} • Seat No.
                                <span class="block">{{ trip.seats_display }}</span>
                            </p>
                            <p class="text-sm text-gray-500">
                                {{ trip.passenger_name }} • PNR: {{ trip.base_pnr }} • Total Seats:
                                {{ trip.total_seats }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col md:items-end">
                    <span class="mb-1 font-bold text-gray-800">Rs. {{ formatNumber(trip.total_price) }}</span>
                    <span class="px-3 py-1 text-sm text-green-800 bg-green-100 border border-green-200 rounded-full">
                        Completed
                    </span>
                </div>
            </div>
            <div class="flex flex-wrap gap-2 mt-4">
                <button @click="openViewDetailsModal(trip)"
                    class="px-4 py-2 text-sm text-white rounded bg-primary hover:bg-primary-dark">
                    View Details
                </button>
            </div>
        </div>

        <div v-if="displayedHistory.length === 0" class="p-6 text-center text-gray-500">
            <i class="mb-2 text-3xl fas fa-history"></i>
            <p>No travel history</p>
        </div>
    </div>

    <!-- View Details Modal -->
    <div v-if="showViewDetailsModal" class="fixed inset-0 z-50 overflow-y-auto">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="closeViewDetailsModal"></div>

        <!-- Modal Content -->
        <div class="relative flex items-center justify-center min-h-screen p-0">
            <div class="relative w-full max-w-3xl overflow-hidden bg-white shadow-2xl rounded-2xl">
                <!-- Header -->
                <div
                    class="sticky top-0 z-10 px-6 py-4 border-b border-gray-200 bg-gradient-to-br from-primary via-primary to-primary/90">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-white">
                                Trip Details
                            </h2>
                            <p class="text-white">
                                Complete booking information
                            </p>
                        </div>
                        <button @click="closeViewDetailsModal"
                            class="p-2 text-white transition-colors rounded-full hover:bg-white/10">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="flex items-center gap-4 mt-2">
                        <span class="font-mono font-bold text-white">PNR: {{ selectedTrip?.base_pnr }}</span>
                        <span class="px-2 py-1 text-xs rounded-lg" :class="{
                            'bg-green-100 text-green-800':
                                selectedTrip?.status?.toLowerCase() ===
                                'confirmed',
                            'bg-yellow-100 text-yellow-800':
                                selectedTrip?.status?.toLowerCase() ===
                                'pending',
                            'bg-blue-100 text-blue-800':
                                selectedTrip?.status?.toLowerCase() ===
                                'booked',
                            'bg-red-100 text-red-800':
                                selectedTrip?.status?.toLowerCase() ===
                                'cancelled',
                        }">
                            {{ selectedTrip?.status }}
                        </span>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-6 py-4 max-h-[70vh] overflow-y-auto">
                    <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                        <!-- Left Column: Passenger & Journey Info -->
                        <div>
                            <!-- Passenger Information -->
                            <div class="p-4 mb-4 bg-gray-100 rounded-lg">
                                <h3 class="flex items-center pb-2 mb-2 text-lg font-semibold text-gray-800">
                                    <svg class="inline-block w-5 h-5 mr-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    Passenger Information
                                </h3>
                                <div class="space-y-3 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Full Name</span>
                                        <span class="font-medium">{{
                                            selectedTrip?.passenger_name
                                        }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">CNIC</span>
                                        <span class="font-mono">
                                            {{ formatCnic(selectedTrip?.CNIC) }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Gender</span>
                                        <span class="font-medium capitalize">{{
                                            selectedTrip?.Gender
                                        }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Contact</span>
                                        <span class="font-medium">{{
                                            selectedTrip?.Contact_No
                                        }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Emergency Contact</span>
                                        <span class="font-medium">{{
                                            selectedTrip?.Emergency_Contact
                                        }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Journey Information -->
                            <div class="p-4 bg-gray-100 rounded-lg">
                                <h3 class="pb-2 mb-2 text-lg font-semibold text-gray-800">
                                    <svg class="inline-block w-5 h-5 mr-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Journey Information
                                </h3>
                                <div class="text-sm">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="text-start">
                                            <p class="font-bold text-gray-800 text-md">
                                                {{ selectedTrip?.from }}
                                            </p>
                                            <p class="text-sm text-gray-600">
                                                {{ selectedTrip?.date }}
                                            </p>
                                            <p class="text-sm font-semibold text-blue-600">
                                                {{ selectedTrip?.time }}
                                            </p>
                                        </div>
                                        <div class="flex items-center justify-center flex-1 px-4">
                                            <i class="bi bi-arrow-right"></i>
                                        </div>
                                        <div class="text-end">
                                            <p class="font-bold text-gray-800 text-md">
                                                {{ selectedTrip?.to }}
                                            </p>
                                            <p class="text-sm text-gray-600">
                                                Arrival
                                            </p>
                                            <p class="text-sm font-semibold text-blue-600">
                                                {{
                                                    calculateArrivalTime(
                                                        selectedTrip?.Travel_Time
                                                    )
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap gap-2 text-sm">
                                        <div class="flex justify-between items-center w-full">
                                            <p class="text-sm text-gray-600">
                                                Company:
                                            </p>
                                            <span
                                                class="inline-flex items-center px-3 py-1 text-sm text-primary bg-primary/10 rounded-lg">
                                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path
                                                        d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                                                    <path
                                                        d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1v-1h4v1a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H20a1 1 0 001-1V5a1 1 0 00-1-1H3z" />
                                                </svg>
                                                {{ selectedTrip?.company_name }}
                                            </span>
                                        </div>
                                        <div class="">
                                            <p class="text-sm text-gray-600">
                                                Seats:
                                            </p>
                                            <span
                                                class="inline-flex items-center p-2 text-sm text-primary bg-primary/10 rounded-lg">
                                                {{ selectedTrip?.seats_display }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Booking & Payment Info -->
                        <div>
                            <!-- Booking Information -->
                            <div class="p-4 bg-gray-100 rounded-lg mb-4">
                                <h3 class="pb-2 mb-2 text-lg font-semibold text-gray-800">
                                    <svg class="inline-block w-5 h-5 mr-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    Booking Information
                                </h3>
                                <div class="space-y-3 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Booking Date</span>
                                        <span class="font-medium">{{
                                            formatDate(selectedTrip?.Issue_Date)
                                            }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Issued By</span>
                                        <span class="font-medium">{{
                                            selectedTrip?.Issued_By ||
                                            "Online Booking"
                                            }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Issue Terminal</span>
                                        <span class="font-medium">{{
                                            selectedTrip?.Issue_Terminal ||
                                            "N/A"
                                            }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Collection Point</span>
                                        <span class="font-medium">
                                            {{
                                                selectedTrip?.CollectionPoint ||
                                                "Not specified"
                                            }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Invoice</span>
                                        <span class="font-medium">{{
                                            selectedTrip?.Invoice || "N/A"
                                            }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Information -->
                            <div class="p-4 mb-4 bg-gray-100 rounded-lg">
                                <h3 class="pb-2 mb-2 text-lg font-semibold text-gray-800">
                                    <svg class="inline-block w-5 h-5 mr-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Payment Information
                                </h3>
                                <div class="space-y-3 text-sm">

                                    <!-- Base Fare -->
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Base Fare</span>
                                        <span class="font-medium">
                                            Rs. {{ formatNumber(selectedTrip?.total_original_fare) }}
                                        </span>
                                    </div>

                                    <!-- Discount (only show if there's a discount) -->
                                    <div v-if="selectedTrip?.total_discount > 0" class="flex justify-between">
                                        <span class="text-gray-600">Discount</span>
                                        <span class="font-medium text-green-600">
                                            -Rs. {{ formatNumber(selectedTrip?.total_discount) }}
                                        </span>
                                    </div>

                                    <!-- Total Amount -->
                                    <div class="flex justify-between pt-3 border-t">
                                        <span class="text-lg font-bold text-gray-800">Total Amount</span>
                                        <span class="text-xl font-bold text-blue-700">
                                            Rs. {{ formatNumber(selectedTrip?.total_price) }}
                                        </span>
                                    </div>

                                    <!-- Savings Badge (only show if discount exists) -->
                                    <div v-if="selectedTrip?.total_discount > 0"
                                        class="flex items-center justify-between px-3 py-2 rounded-lg bg-green-50">
                                        <span class="text-sm font-medium text-green-700">🎉 You saved</span>
                                        <span class="text-sm font-bold text-green-700">
                                            Rs. {{ formatNumber(selectedTrip?.total_discount) }}
                                        </span>
                                    </div>

                                    <!-- Payment Status -->
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Payment Status</span>
                                        <span class="font-medium" :class="{
                                            'text-green-600': selectedTrip?.status === 'Confirmed',
                                            'text-yellow-600': selectedTrip?.status === 'pending',
                                            'text-red-600': selectedTrip?.status === 'Cancelled',
                                        }">
                                            {{ getPaymentStatusText(selectedTrip?.status) }}
                                        </span>
                                    </div>

                                    <!-- Payment Date -->
                                    <div v-if="selectedTrip?.PaymentDate" class="flex justify-between">
                                        <span class="text-gray-600">Payment Date</span>
                                        <span class="font-medium">
                                            {{ formatDate(selectedTrip?.PaymentDate) }}
                                        </span>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3 p-4 text-base border-t">
                    <button @click="downloadTicket(selectedTrip?.base_pnr)" :disabled="isLoadingTicket"
                        class="flex items-center justify-center px-4 py-2 text-white rounded-lg bg-primary hover:bg-primary-dark disabled:opacity-70 disabled:cursor-not-allowed">
                        <!-- Show loader when loading -->
                        <svg v-if="isLoadingTicket" class="inline-block w-5 h-5 mr-2 animate-spin" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>

                        <!-- Show download icon when not loading -->
                        <svg v-else class="inline-block w-5 h-5 mr-2" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>

                        <!-- Text changes based on loading state -->
                        <span>
                            {{
                                isLoadingTicket
                                    ? "Generating Ticket..."
                                    : "Download Ticket"
                            }}
                        </span>
                    </button>
                    <button @click="shareTicket(selectedTrip)"
                        class="flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                        <svg class="inline-block w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                        </svg>
                        Share Ticket
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from "vue";

const props = defineProps({
    history: {
        type: Array,
        default: () => [],
    },
});

const showAll = ref(false);
const showViewDetailsModal = ref(false);
const selectedTrip = ref(null);

// Helper functions
const formatNumber = (value) => {
    if (value === null || value === undefined || value === "") {
        return "0.00";
    }
    const num = parseFloat(value);
    if (isNaN(num)) {
        return "0.00";
    }
    return num.toFixed(2);
};
const formatCnic = (cnic) => {
    if (!cnic || cnic.length !== 13) return cnic || "";
    return `${cnic.substring(0, 5)}-${cnic.substring(5, 12)}-${cnic.substring(
        12
    )}`;
};
const isLoadingTicket = ref(false);
// Update the download ticket function
const downloadTicket = async (pnr) => {
    if (!pnr) {
        console.error("❌ No PNR provided for ticket download");
        toast.warning("Ticket PNR not found", {
            position: "top-right",
            duration: 3000,
            dismissible: true,
        });
        return;
    }

    // Set loading to true at the beginning
    isLoadingTicket.value = true;

    try {
        console.log("🔄 Generating ticket for PNR:", pnr);

        const response = await axios.post("/api/bookings/generate-ticket", {
            pnr: pnr,
        });

        if (response.data.success) {
            const ticketUrl = response.data.data.ticket_url;
            console.log("✅ Ticket generated:", ticketUrl);

            // Create a temporary link element and trigger download
            const link = document.createElement("a");
            link.href = ticketUrl;
            link.download = `ticket_${pnr}.jpg`;
            link.target = "_blank";

            // Append to body, click, and remove
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            // Clean up the URL object if needed
            URL.revokeObjectURL(ticketUrl);

            console.log("📥 Download initiated for ticket");
        } else {
            console.error(
                "❌ Ticket generation failed:",
                response.data.message
            );
            toast.warning(
                "Failed to generate ticket: " + response.data.message,
                {
                    position: "top-right",
                    duration: 3000,
                    dismissible: true,
                }
            );
        }
    } catch (error) {
        console.error("❌ Ticket generation error:", error);
        toast.warning(
            "Error generating ticket: " +
            (error.response?.data?.message || error.message),
            {
                position: "top-right",
                duration: 3000,
                dismissible: true,
            }
        );
    } finally {
        // Set loading to false when done
        isLoadingTicket.value = false;
    }
};

const formatDate = (dateString) => {
    if (!dateString) return "N/A";
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) {
            return "Invalid Date";
        }
        return date.toLocaleDateString("en-US", {
            year: "numeric",
            month: "short",
            day: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        });
    } catch (error) {
        console.error("Date formatting error:", error);
        return "Invalid Date";
    }
};

const getPaymentStatusText = (status) => {
    if (!status) return "Unknown";
    switch (status.toLowerCase()) {
        case "confirmed":
            return "Paid";
        case "pending":
            return "Pending";
        case "cancelled":
            return "Cancelled";
        case "booked":
            return "Booked";
        default:
            return status;
    }
};
const calculateArrivalTime = (departureTime) => {
    if (!departureTime) return "N/A";
    try {
        const [hours, minutes] = departureTime.split(":").map(Number);
        if (isNaN(hours) || isNaN(minutes)) {
            return "Invalid Time";
        }
        const arrivalHours = (hours + 5) % 24; // Assuming 5 hour journey
        const arrivalMinutes = minutes;
        return `${String(arrivalHours).padStart(2, "0")}:${String(
            arrivalMinutes
        ).padStart(2, "0")}`;
    } catch (error) {
        console.error("Time calculation error:", error);
        return "Invalid Time";
    }
};

const calculateTotalAmount = () => {
    if (!selectedTrip.value) return 0;
    const price = parseNumber(
        selectedTrip.value.total_price || selectedTrip.value.price
    );
    const discount = parseNumber(selectedTrip.value.Discount);
    return price - discount;
};

const parseNumber = (value) => {
    if (value === null || value === undefined || value === "") {
        return 0;
    }
    const num = parseFloat(value);
    return isNaN(num) ? 0 : num;
};


const openViewDetailsModal = (trip) => {
    selectedTrip.value = trip;
    showViewDetailsModal.value = true;
    document.body.style.overflow = "hidden";
};

const closeViewDetailsModal = () => {
    showViewDetailsModal.value = false;
    selectedTrip.value = null;
    document.body.style.overflow = "";
};

const displayedHistory = computed(() => {
    return showAll.value ? props.history : props.history.slice(0, 3);
});
</script>
