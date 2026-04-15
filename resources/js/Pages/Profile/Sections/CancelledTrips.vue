<!-- resources/js/Pages/Profile/Sections/CancelledTrips.vue -->
<template>
    <div class="divide-y divide-gray-100">
        <div v-for="trip in trips" :key="trip.base_pnr" class="p-6 transition bg-white rounded-lg hover:bg-gray-50">
            <div class="flex flex-col justify-between md:flex-row md:items-center">
                <div class="mb-4 md:mb-0">
                    <div class="flex items-center">
                        <img :src="trip.logo || '/images/logo.jpg'" class="w-12 h-auto mr-3" />
                        <div>
                            <h4 class="font-bold text-gray-800">
                                {{ trip.from }} → {{ trip.to }}
                            </h4>
                            <p class="text-sm text-gray-600">
                                {{ trip.date }} • {{ trip.time }} • Seat No.
                                {{ trip.seats_display }}
                            </p>
                            <p class="text-sm text-gray-500">
                                Passenger: {{ trip.passenger_name }}
                            </p>
                            <p class="text-sm text-gray-500">
                                PNR: {{ trip.base_pnr }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col md:items-end">
                    <span class="mb-1 font-bold text-gray-800">Rs. {{ formatNumber(trip.total_price) }}</span>
                    <span class="px-3 py-1 text-sm rounded-full" :class="{
                        'bg-red-100 text-red-800':
                            trip.status === 'Cancelled',
                        'bg-orange-100 text-orange-800':
                            trip.status === 'Pending Refund',
                        'bg-purple-100 text-purple-800':
                            trip.status === 'Mixed Status',
                    }">
                        {{
                            trip.status === "Mixed Status"
                                ? "Multiple Statuses"
                                : trip.status
                        }}
                    </span>
                </div>
            </div>

            <!-- Show seat-wise status for mixed status trips -->
            <div v-if="trip.status === 'Mixed Status' && trip.seat_statuses"
                class="p-4 mt-2 mb-2 border rounded-lg bg-gray-50">
                <h5 class="mb-2 font-semibold text-gray-700">
                    Seat-wise Status:
                </h5>
                <div class="flex flex-wrap gap-2">
                    <div v-for="(status, seatNumber) in trip.seat_statuses" :key="seatNumber"
                        class="inline-flex items-center px-3 py-1 text-sm rounded-full" :class="{
                            'bg-red-100 text-red-800': status === 'Cancelled',
                            'bg-orange-100 text-orange-800':
                                status === 'Pending Refund',
                        }">
                        <span>Seat {{ seatNumber }}: {{ status }}</span>
                    </div>
                </div>
            </div>

            <!-- Cancellation info for Pending Refund -->
            <div v-if="
                trip.status === 'Pending Refund' ||
                (trip.status === 'Mixed Status' &&
                    hasPendingRefundSeats(trip))
            " class="p-4 mt-2 text-red-700 bg-red-100 border border-red-400 rounded-lg">
                <p class="mb-2 font-semibold">
                    ⏳ Seat Refund Request in Progress
                </p>
                <p class="text-sm leading-relaxed">
                    <span v-if="trip.status === 'Pending Refund'">Your seat cancellation request</span>
                    <span v-if="trip.status === 'Mixed Status'">Some seats in this booking</span>
                    has been successfully submitted and is currently
                    <span class="font-semibold">Pending approval from our team</span>.
                </p>
                <p class="mt-2 text-sm leading-relaxed">
                    Once the request is approved, your refund amount will be
                    processed and sent back to your original payment method.
                </p>
                <p class="mt-2 text-sm italic">
                    Thank you for your patience — we'll notify you as soon as
                    the refund is completed.
                </p>
            </div>
        </div>

        <div v-if="trips.length === 0" class="p-6 text-center text-gray-500">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="mb-2 text-lg font-medium text-gray-600">
                No Cancelled or Refunded Trips
            </p>
            <p class="text-sm text-gray-500">
                You haven't cancelled or requested refunds for any trips yet.
            </p>
        </div>
    </div>
</template>

<script setup>
defineProps({
    trips: {
        type: Array,
        default: () => [],
    },
});

defineEmits(["view-details"]);

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

// Helper functions for seat status filtering
const hasPendingRefundSeats = (trip) => {
    if (!trip.ticket_details) return false;
    return trip.ticket_details.some(
        (ticket) => ticket.status === "Pending Refund"
    );
};

const hasCancelledSeats = (trip) => {
    if (!trip.ticket_details) return false;
    return trip.ticket_details.some((ticket) => ticket.status === "Cancelled");
};
</script>
