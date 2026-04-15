<!-- resources/js/Pages/Profile/Sections/UpcomingTrips.vue -->
<template>
    <div class="divide-y divide-gray-100">
        <div v-for="trip in trips" :key="trip.id" class="p-6 transition bg-white rounded-lg hover:bg-gray-50">
            <div class="flex flex-col justify-between md:flex-row md:items-center">
                <div class="mb-4 md:mb-0">
                    <div class="flex items-center">
                        <img :src="trip.logo || '/images/logo.jpg'" class="w-12 h-auto mr-3 rounded-lg" />
                        <div>
                            <h4 class="font-bold text-gray-800">
                                {{ trip.from }} → {{ trip.to }}
                            </h4>
                            <p class="text-sm text-gray-600">
                                {{ trip.date }} • {{ trip.time }} • Seat No.
                                <span class="block">{{ trip.seats_display }}</span>
                            </p>
                            <p class="text-sm text-gray-500">
                                Passenger: {{ trip.passenger_name }}
                            </p>
                            <p class="text-sm text-gray-500">
                                PNR: {{ trip.base_pnr }} • Total Seats:
                                {{ trip.total_seats }}
                            </p>
                            <p class="text-sm text-gray-500"></p>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col md:items-end">
                    <span class="mb-1 font-bold text-gray-800">Rs. {{ formatNumber(trip.total_price) }}</span>
                    <span class="px-3 py-1 text-sm rounded-full" :class="{
                        'bg-green-100 text-green-800':
                            trip.status === 'Confirmed',
                        'bg-yellow-100 text-yellow-800':
                            trip.status === 'Pending',
                        'bg-blue-100 text-blue-800':
                            trip.status === 'booked',
                        'bg-orange-100 text-orange-800':
                            trip.status === 'Pending Refund',
                        'bg-red-100 text-red-800':
                            trip.status === 'Cancelled',
                    }">
                        {{ trip.status }}
                    </span>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 mt-4">
                <button @click="openViewDetailsModal(trip)"
                    class="px-4 py-2 text-sm text-white rounded bg-primary hover:bg-primary-dark">
                    View Details
                </button>
                <button v-if="
                    trip.status !== 'Cancelled' &&
                    trip.status !== 'Pending Refund'
                " @click="openCancelTripModal(trip)"
                    class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">
                    Cancel Seats
                </button>
                <!-- ADD THIS BELOW -->
                <p class="mt-1 text-sm font-medium ml-auto py-2 px-3 rounded" :class="getTimerClass(trip)">
                    <span v-if="getCountdown(trip)">
                        🕐 Departs in: {{ getCountdown(trip) }}
                    </span>
                    <span v-else class="text-gray-400">
                        Trip Departed
                    </span>
                </p>
            </div>
        </div>

        <div v-if="trips.length === 0" class="p-6 text-center text-gray-500">
            <i class="mb-2 text-3xl fas fa-calendar-times"></i>
            <p>No upcoming trips</p>
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

    <!-- Cancel Trip Modal -->
    <div v-if="showCancelTripModal" class="fixed inset-0 z-50 overflow-y-auto">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="closeCancelTripModal"></div>

        <!-- Modal Content -->
        <div class="relative flex items-center justify-center min-h-screen p-4">
            <div class="relative w-full max-w-2xl overflow-hidden bg-white shadow-2xl rounded-2xl">
                <!-- Header -->
                <div
                    class="sticky top-0 z-10 p-6 border-b border-gray-200 bg-gradient-to-br from-primary via-primary to-primary/90">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-white">
                                Cancel Seats
                            </h2>
                            <p class="text-red-100">
                                Select seats to cancel and review cancellation
                                policy
                            </p>
                        </div>
                        <button @click="closeCancelTripModal"
                            class="p-2 text-white transition-colors rounded-full hover:bg-white/10">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Content -->
                <div class="px-6 py-4 max-h-[70vh] overflow-y-auto">
                    <!-- Trip Summary -->
                    <div class="p-4 mb-3 rounded-lg bg-gray-50">
                        <h3 class="mb-1 font-semibold text-gray-800">
                            Trip Summary
                        </h3>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium">
                                    {{ selectedTrip?.from }} →
                                    {{ selectedTrip?.to }}
                                </p>
                                <p class="text-sm text-gray-600">
                                    {{ selectedTrip?.date }} •
                                    {{ selectedTrip?.time }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    PNR: {{ selectedTrip?.base_pnr }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-gray-800">
                                    Rs.
                                    {{
                                        formatNumber(selectedTrip?.total_price)
                                    }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    Total Seats: {{ selectedTrip?.total_seats }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Seat Selection -->
                    <div class="mb-6">
                        <h3 class="mb-3 text-lg font-semibold text-gray-800">
                            Select Seats to Cancel
                            <span class="text-sm font-normal text-gray-600">(Select one or more seats)</span>
                        </h3>

                        <div v-if="selectedTrip" class="grid grid-cols-4 gap-3 sm:grid-cols-6 md:grid-cols-8">
                            <div v-for="seatNumber in selectedTrip.seat_numbers" :key="seatNumber"
                                @click="toggleSeatSelection(seatNumber)"
                                class="relative p-4 text-center transition-all border-2 rounded-lg cursor-pointer"
                                :class="{
                                    'border-red-500 bg-red-50 text-red-700':
                                        selectedSeats.includes(seatNumber),
                                    'border-gray-300 bg-gray-50 text-gray-700 hover:bg-gray-100':
                                        !selectedSeats.includes(seatNumber),
                                }">
                                <div class="text-lg font-bold">
                                    {{ seatNumber }}
                                </div>
                                <div class="text-xs text-gray-500">Seat</div>

                                <!-- Checkmark for selected -->
                                <div v-if="selectedSeats.includes(seatNumber)"
                                    class="absolute top-0 right-0 p-1 transform translate-x-1/2 -translate-y-1/2 bg-red-500 rounded-full">
                                    <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div v-if="selectedTrip" class="mt-3">
                            <p class="text-sm text-gray-600">
                                Selected:
                                <span class="font-semibold">{{
                                    selectedSeats.join(", ") || "None"
                                }}</span>
                            </p>
                            <p class="text-sm text-gray-600">
                                Remaining:
                                <span class="font-semibold">{{
                                    getRemainingSeats().join(", ")
                                }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Cancellation Reason -->
                    <div class="mb-3">
                        <h3 class="mb-1 font-semibold text-gray-800">
                            Reason for Cancellation
                            <span class="text-xs text-red-500">(Required)</span>
                        </h3>
                        <textarea v-model="cancellationReason" :class="[
                            'w-full p-3 border rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent',
                            cancellationError
                                ? 'border-red-500 bg-red-50'
                                : 'border-gray-300',
                        ]" rows="3" placeholder="Please tell us why you are cancelling these seats..."
                            @input="clearCancellationError"></textarea>
                        <p v-if="cancellationError" class="mt-0 text-sm text-red-600">
                            {{ cancellationError }}
                        </p>
                    </div>

                    <!-- Refund Summary -->
                    <div class="p-4 mb-4 border border-red-200 rounded-lg bg-red-50">
                        <h4 class="mb-3 font-semibold text-red-700">
                            Refund Summary
                        </h4>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-700">Selected Seats:</span>
                                <span class="font-semibold">{{ selectedSeats.length }} seat(s)</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-700">Total Fare for Selected Seats:</span>
                                <span class="font-semibold">Rs.
                                    {{
                                        formatNumber(
                                            calculateSelectedSeatsFare()
                                        )
                                    }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-700">Refund Percentage:</span>
                                <span class="font-semibold text-green-600">{{ calculateRefundPercentage() }}%</span>
                            </div>
                            <div class="flex justify-between pt-2 border-t border-red-200">
                                <span class="text-lg font-bold text-gray-800">Estimated Refund:</span>
                                <span class="text-2xl font-bold text-green-600">Rs.
                                    {{
                                        formatNumber(calculateRefundAmount())
                                    }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Cancellation Policy -->
                    <div class="mb-6">
                        <h3 class="mb-3 text-lg font-semibold text-red-700">
                            <svg class="inline-block w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd" />
                            </svg>
                            Cancellation & Refund Policy
                        </h3>

                        <div class="space-y-3">
                            <div class="flex justify-between p-3 border rounded-lg">
                                <span class="text-gray-700">Cancellation before 48 hours</span>
                                <span class="font-semibold text-green-600">90% refund</span>
                            </div>
                            <div class="flex justify-between p-3 border rounded-lg">
                                <span class="text-gray-700">Cancellation 24-48 hours before
                                    departure</span>
                                <span class="font-semibold text-yellow-600">75% refund</span>
                            </div>
                            <div class="flex justify-between p-3 border rounded-lg">
                                <span class="text-gray-700">Cancellation within 24 hours</span>
                                <span class="font-semibold text-red-600">No refund</span>
                            </div>
                        </div>

                        <div class="p-4 mt-4 rounded-lg bg-blue-50">
                            <h4 class="mb-2 font-semibold text-blue-800">
                                Important Notes:
                            </h4>
                            <ul class="pl-5 space-y-1 text-sm text-blue-700 list-disc">
                                <li>
                                    Refunds are processed within 5-7 business
                                    days
                                </li>
                                <li>
                                    Refund amount will be credited to your
                                    original payment method
                                </li>
                                <li>
                                    Service charges may apply for cancellation
                                    processing
                                </li>
                                <li>
                                    Cancellation is irreversible once confirmed
                                </li>
                                <li>
                                    Partial cancellation is allowed for group
                                    bookings
                                </li>
                                <li>
                                    Remaining seats will keep their confirmed
                                    status
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Helpline Information -->
                    <div class="p-4 mb-6 bg-gray-100 rounded-lg">
                        <h4 class="mb-3 font-semibold text-gray-800">
                            Need Help?
                        </h4>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-3 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                                </svg>
                                <div>
                                    <p class="text-sm text-gray-600">
                                        Customer Care
                                    </p>
                                    <p class="font-semibold text-gray-800">
                                        0800-12345
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                </svg>
                                <div>
                                    <p class="text-sm text-gray-600">
                                        Email Support
                                    </p>
                                    <p class="font-semibold text-gray-800">
                                        support@royalmovers.com
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="px-4 py-3 text-sm border-t border-gray-200 bg-gray-50">
                    <div class="flex flex-col gap-3 sm:flex-row sm:justify-between">
                        <button @click="closeCancelTripModal"
                            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">
                            Go Back
                        </button>
                        <div class="flex gap-3">
                            <button @click="selectAllSeats" :class="[
                                'px-4 py-2 border rounded-lg',
                                selectedSeats.length ===
                                    selectedTrip?.seat_numbers.length
                                    ? 'bg-gray-600 text-white border-gray-600 hover:bg-gray-700'
                                    : 'text-gray-600 border-gray-300 hover:bg-gray-100',
                            ]">
                                {{
                                    selectedSeats.length ===
                                        selectedTrip?.seat_numbers.length
                                        ? "Deselect All"
                                        : "Select All"
                                }}
                            </button>
                            <button @click="requestCallBack"
                                class="px-4 py-2 text-blue-600 border border-blue-500 rounded-lg hover:bg-blue-50">
                                Request Call Back
                            </button>
                            <button @click="confirmCancellation"
                                class="px-4 py-2 text-white bg-red-600 rounded-lg hover:bg-red-700" :disabled="processingCancellation ||
                                    selectedSeats.length === 0
                                    " :class="{
                                        'opacity-50 cursor-not-allowed':
                                            processingCancellation ||
                                            selectedSeats.length === 0,
                                    }">
                                <svg v-if="processingCancellation" class="inline-block w-5 h-5 mr-2 animate-spin"
                                    fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                {{
                                    processingCancellation
                                        ? "Processing..."
                                        : "Cancel Selected Seats"
                                }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, computed, onMounted, onUnmounted } from "vue";
import axios from "axios";
import { useToast } from "vue-toast-notification";

const toast = useToast();

const props = defineProps({
    trips: {
        type: Array,
        default: () => [],
    },
});

// Modal states
const showViewDetailsModal = ref(false);
const showCancelTripModal = ref(false);
const selectedTrip = ref(null);
const cancellationReason = ref("");
const processingCancellation = ref(false);
const cancellationError = ref("");
const selectedSeats = ref([]);


// Timer state
const now = ref(new Date());
let timerInterval = null;

onMounted(() => {
    timerInterval = setInterval(() => {
        now.value = new Date();
    }, 1000);
});

onUnmounted(() => {
    clearInterval(timerInterval);
});

// Calculate countdown for a trip
const getCountdown = (trip) => {
    if (!trip.date || !trip.time) return null;

    // Parse "Jan 25, 2026" + "10:30 AM" into a Date
    const departureDate = new Date(`${trip.date} ${trip.time}`);
    if (isNaN(departureDate.getTime())) return null;

    const diff = departureDate - now.value;
    if (diff <= 0) return null;

    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((diff % (1000 * 60)) / 1000);

    if (days > 0) return `${days}d ${hours}h ${minutes}m ${seconds}s`;
    if (hours > 0) return `${hours}h ${minutes}m ${seconds}s`;
    if (minutes > 0) return `${minutes}m ${seconds}s`;
    return `${seconds}s`;
};

// Color based on urgency
const getTimerClass = (trip) => {
    if (!trip.date || !trip.time) return 'text-gray-400';

    const departureDate = new Date(`${trip.date} ${trip.time}`);
    const diff = departureDate - now.value;

    if (diff <= 0) return 'text-gray-400 bg-gray-100 border border-gray-300';           // departed
    if (diff <= 1000 * 60 * 60) return 'text-red-600 animate-pulse bg-red-50 border border-red-200'; // < 1 hour
    if (diff <= 1000 * 60 * 60 * 24) return 'text-orange-500 bg-orange-50 border border-orange-200';           // < 24 hours
    if (diff <= 1000 * 60 * 60 * 48) return 'text-yellow-600 bg-yellow-50 border border-yellow-200';           // < 48 hours
    return 'text-green-600 bg-green-50 border border-green-200';                                                // > 48 hours
};

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

// Add this computed property to get the PNR from selected trip
const ConfirmedPNR = computed(() => {
    return selectedTrip?.value?.base_pnr || "";
});
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

const clearCancellationError = () => {
    cancellationError.value = "";
};

// Get fare per seat from the trip
const getFarePerSeat = () => {
    if (!selectedTrip.value) return 0;
    return selectedTrip.value.total_price / selectedTrip.value.total_seats;
};

// Get remaining seats (not selected for cancellation)
const getRemainingSeats = () => {
    if (!selectedTrip.value) return [];
    return selectedTrip.value.seat_numbers.filter(
        (seat) => !selectedSeats.value.includes(seat)
    );
};

// Calculate fare for selected seats
const calculateSelectedSeatsFare = () => {
    if (!selectedTrip.value || selectedSeats.value.length === 0) return 0;
    const farePerSeat = getFarePerSeat();
    return farePerSeat * selectedSeats.value.length;
};

// Toggle seat selection
const toggleSeatSelection = (seatNumber) => {
    const index = selectedSeats.value.indexOf(seatNumber);
    if (index === -1) {
        selectedSeats.value.push(seatNumber);
    } else {
        selectedSeats.value.splice(index, 1);
    }
};

// Select/Deselect all seats
const selectAllSeats = () => {
    if (!selectedTrip.value) return;

    if (selectedSeats.value.length === selectedTrip.value.seat_numbers.length) {
        // If all are selected, deselect all
        selectedSeats.value = [];
    } else {
        // Otherwise select all
        selectedSeats.value = [...selectedTrip.value.seat_numbers];
    }
};

// Refund percentage calculation based on time
const calculateRefundPercentage = () => {
    if (!selectedTrip.value) return 0;

    try {
        const tripDate = new Date(
            selectedTrip.value.date + " " + selectedTrip.value.time
        );
        if (isNaN(tripDate.getTime())) {
            return 0;
        }

        const now = new Date();
        const hoursDifference = (tripDate - now) / (1000 * 60 * 60);

        if (hoursDifference > 48) return 90;
        if (hoursDifference > 24) return 75;
        return 0;
    } catch (error) {
        console.error("Refund percentage calculation error:", error);
        return 0;
    }
};

// Calculate refund amount for selected seats
const calculateRefundAmount = () => {
    if (!selectedTrip.value || selectedSeats.value.length === 0) return 0;
    const percentage = calculateRefundPercentage();
    const selectedFare = calculateSelectedSeatsFare();
    return (selectedFare * percentage) / 100;
};

// Modal functions
const openCancelTripModal = (trip) => {
    if (trip.status === "Cancelled" || trip.status === "Pending Refund") {
        return;
    }

    selectedTrip.value = trip;
    selectedSeats.value = []; // Reset selection
    cancellationReason.value = "";
    cancellationError.value = "";
    showCancelTripModal.value = true;
    document.body.style.overflow = "hidden";
};

const closeCancelTripModal = () => {
    showCancelTripModal.value = false;
    selectedTrip.value = null;
    selectedSeats.value = [];
    cancellationReason.value = "";
    document.body.style.overflow = "";
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

// Close modals on escape key
document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
        if (showViewDetailsModal.value) closeViewDetailsModal();
        if (showCancelTripModal.value) closeCancelTripModal();
    }
});

// Prevent body scroll when modals are open
watch([showViewDetailsModal, showCancelTripModal], () => {
    if (showViewDetailsModal.value || showCancelTripModal.value) {
        document.body.style.overflow = "hidden";
    } else {
        document.body.style.overflow = "";
    }
});

// Request callback
const requestCallBack = () => {
    toast.success(
        "Callback requested! Our customer care will contact you shortly.",
        {
            position: "top-right",
            duration: 3000,
            dismissible: true,
        }
    );
};

// Confirm cancellation with multiple seats
const confirmCancellation = async () => {
    if (!selectedTrip.value) return;

    // Validate selection
    if (selectedSeats.value.length === 0) {
        cancellationError.value = "Please select at least one seat to cancel";
        return;
    }

    // Validate reason
    if (!cancellationReason.value || cancellationReason.value.trim() === "") {
        cancellationError.value = "Cancellation reason is required";
        return;
    }

    // Clear any previous error
    cancellationError.value = "";
    processingCancellation.value = true;

    // Debug log
    console.log("Sending cancellation request:", {
        base_pnr: selectedTrip.value.base_pnr,
        seat_numbers: selectedSeats.value,
        reason: cancellationReason.value,
        trip: selectedTrip.value,
    });

    try {
        // Send request with base PNR and selected seats
        const response = await axios.post("/api/profile/cancel-ticket", {
            base_pnr: selectedTrip.value.base_pnr,
            seat_numbers: selectedSeats.value,
            reason: cancellationReason.value,
        });

        if (response.data.success) {
            // Show success message
            toast.success(
                `Successfully cancelled ${response.data.data.updated_count} seat(s)`,
                {
                    position: "top-right",
                    duration: 3000,
                    dismissible: true,
                }
            );

            // Close modal
            closeCancelTripModal();

            // Reload page or update data
            window.location.reload();
        } else {
            // Show detailed error
            console.error("Cancellation failed:", response.data);
            throw new Error(response.data.message || "Cancellation failed");
        }
    } catch (error) {
        console.error("Cancellation error:", error);

        // Show detailed error message
        let errorMessage = "Cancellation failed: ";
        if (error.response?.data?.message) {
            errorMessage += error.response.data.message;
            if (error.response.data.debug) {
                console.log("Debug info:", error.response.data.debug);
                errorMessage += "\n\nDebug info available in console.";
            }
        } else {
            errorMessage += error.message;
        }

        toast.warning(errorMessage, {
            position: "top-right",
            duration: 5000,
            dismissible: true,
        });
    } finally {
        processingCancellation.value = false;
    }
};

// Close modal on escape key
document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && showCancelTripModal.value) {
        closeCancelTripModal();
    }
});

// Prevent body scroll when modal is open
watch(showCancelTripModal, () => {
    if (showCancelTripModal.value) {
        document.body.style.overflow = "hidden";
    } else {
        document.body.style.overflow = "";
    }
});
</script>

<style scoped>
.animate-spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }

    to {
        transform: rotate(360deg);
    }
}
</style>
