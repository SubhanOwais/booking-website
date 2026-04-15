<script setup>
import { Head, router, Link } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { ref } from "vue";
import RefundModal from "./RefundModal.vue";

const props = defineProps({
    tickets: Object,
    stats: Object,
    ticketCompanies: Array,
    refundCompanies: Array,
    filters: Object,
});

// Filter state
const filterForm = ref({
    search: props.filters?.search || '',
    company: props.filters?.company || '',
    status: props.filters?.status || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
});

// Modal state
const showRefundModal = ref(false);
const selectedTicket = ref(null);

// Apply filters
const applyFilters = () => {
    const cleanFilters = Object.fromEntries(
        Object.entries(filterForm.value).filter(([_, v]) => v !== '' && v !== null)
    );

    router.get(route('admin.refund.index'), cleanFilters, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Clear filters
const clearFilters = () => {
    filterForm.value = {
        search: '',
        company: '',
        status: '',
        date_from: '',
        date_to: '',
    };

    router.get(route('admin.refund.index'), {}, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Open refund modal
const openRefundModal = (ticket) => {
    selectedTicket.value = ticket;
    showRefundModal.value = true;
};

// Close refund modal
const closeRefundModal = () => {
    showRefundModal.value = false;
    selectedTicket.value = null;
};

// Handle refund success
const handleRefundSuccess = () => {
    closeRefundModal();
    // Reload the page to get updated data
    router.reload({ only: ['tickets', 'stats'] });
};

// Format seat numbers
const formatSeats = (seatNo) => {
    if (!seatNo) return 'N/A';
    try {
        const seats = typeof seatNo === 'string' ? JSON.parse(seatNo) : seatNo;
        return Array.isArray(seats) ? seats.join(', ') : seatNo;
    } catch {
        return seatNo;
    }
};

// Format date
const formatDate = (date) => {
    if (!date) return 'N/A';
    try {
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    } catch {
        return date;
    }
};

// Status badge styling
const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'Pending Refund':
            return 'bg-yellow-100 text-yellow-800 border border-yellow-200';
        case 'Cancelled':
            return 'bg-red-100 text-red-800 border border-red-200';
        default:
            return 'bg-gray-100 text-gray-800 border border-gray-200';
    }
};

// Check if refund button should be shown
const shouldShowRefundButton = (status) => {
    return status === 'Pending Refund';
};
</script>

<template>

    <Head title="Refund Tickets" />
    <AdminLayout title="Royal Movers">
        <div>
            <!-- Header with Stats -->
            <div class="mb-6">
                <h1 class="mb-4 text-2xl font-bold text-gray-800">Refund Tickets</h1>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 gap-4 mb-6 md:grid-cols-3">
                    <div class="p-4 bg-white rounded-lg shadow">
                        <div class="text-sm text-gray-600">Total Cancelled Tickets</div>
                        <div class="text-2xl font-bold text-red-600">{{ stats.total_cancelled }}</div>
                    </div>
                    <div class="p-4 bg-white rounded-lg shadow">
                        <div class="text-sm text-gray-600">Pending Refund</div>
                        <div class="text-2xl font-bold text-yellow-600">{{ stats.pending_refund }}</div>
                    </div>
                    <div class="p-4 bg-white rounded-lg shadow">
                        <div class="text-sm text-gray-600">Total Pending</div>
                        <div class="text-2xl font-bold text-blue-600">{{ stats.total_pending }}</div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="p-4 mb-6 bg-white rounded-lg shadow">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
                    <!-- Search -->
                    <div class="md:col-span-2">
                        <label class="block mb-1 text-sm font-medium text-gray-700">Search</label>
                        <input v-model="filterForm.search" type="text" placeholder="PNR, Name, Contact..."
                            @keyup.enter="applyFilters"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- Company -->
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Company</label>
                        <select v-model="filterForm.company"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Companies</option>
                            <option v-for="company in ticketCompanies" :key="company" :value="company">
                                {{ company }}
                            </option>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Status</label>
                        <select v-model="filterForm.status"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Status</option>
                            <option value="Pending Refund">Pending Refund</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>

                    <!-- Date From -->
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">From Date</label>
                        <input v-model="filterForm.date_from" type="date"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- Filter Actions -->
                <div class="flex gap-2 mt-4">
                    <button @click="applyFilters" class="px-4 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700">
                        Apply Filters
                    </button>
                    <button @click="clearFilters"
                        class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                        Clear
                    </button>
                </div>
            </div>

            <!-- Tickets Table -->
            <div class="overflow-hidden bg-white rounded-lg shadow">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                    PNR No
                                </th>
                                <th
                                    class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                    Passenger
                                </th>
                                <th
                                    class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                    Route
                                </th>
                                <th
                                    class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                    Travel Date
                                </th>
                                <th
                                    class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                    Seats
                                </th>
                                <th
                                    class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                    Fare
                                </th>
                                <th
                                    class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                    Company
                                </th>
                                <th
                                    class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                    Status
                                </th>
                                <th
                                    class="px-4 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover:bg-gray-50">
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ ticket.PNR_No }}
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="text-sm font-medium text-gray-900">{{ ticket.Passenger_Name }}</div>
                                    <div class="text-sm text-gray-500">{{ ticket.Contact_No }}</div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="text-sm text-gray-900">
                                        {{ ticket.from_city?.City_Name || 'N/A' }} →
                                        {{ ticket.to_city?.City_Name || 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ formatDate(ticket.Travel_Date) }}</div>
                                    <div class="text-sm text-gray-500">{{ ticket.Travel_Time }}</div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ formatSeats(ticket.Seat_No) }}</div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">PKR {{ ticket.Fare }}</div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ ticket.Company_Name || 'N/A' }}</div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span
                                        :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusBadgeClass(ticket.Status)]">
                                        {{ ticket.Status }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-sm font-medium text-center whitespace-nowrap">
                                    <!-- Show Refund button only for "Pending Refund" status -->
                                    <button v-if="shouldShowRefundButton(ticket.Status)"
                                        @click="openRefundModal(ticket)"
                                        class="px-3 py-1 text-white bg-red-600 rounded-md hover:bg-red-700">
                                        Refund Ticket
                                    </button>
                                    <span v-else class="text-sm text-gray-500">
                                        Already Processed
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="tickets.data.length === 0">
                                <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                                    No tickets found
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="tickets.data.length > 0" class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="text-sm text-gray-700">
                            Showing {{ tickets.from }} to {{ tickets.to }} of {{ tickets.total }} results
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <template v-for="(link, index) in tickets.links" :key="index">
                                <Link v-if="link.url" :href="link.url" :class="[
                                    'px-3 py-1 text-sm border rounded',
                                    link.active
                                        ? 'bg-blue-600 text-white border-blue-600'
                                        : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                                ]" v-html="link.label">
                                </Link>
                                <span v-else :class="[
                                    'px-3 py-1 text-sm border rounded opacity-50 cursor-not-allowed',
                                    'bg-white text-gray-400 border-gray-300'
                                ]" v-html="link.label">
                                </span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Refund Modal -->
            <RefundModal :show="showRefundModal" :ticket="selectedTicket" :companies="refundCompanies"
                @close="closeRefundModal" @success="handleRefundSuccess" />
        </div>
    </AdminLayout>
</template>
