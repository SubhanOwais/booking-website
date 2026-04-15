<script setup>
import { Head, router, Link } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { ref } from "vue";

const props = defineProps({
    tickets: Object,
    stats: Object,
    companies: Array,
    filters: Object,
});

// Filter state - Initialize with empty strings to prevent null errors
const filterForm = ref({
    status: props.filters?.status || "",
    date_from: props.filters?.date_from || "",
    date_to: props.filters?.date_to || "",
    booked_from: props.filters?.booked_from || "",
    booked_to: props.filters?.booked_to || "",
    search: props.filters?.search || "",
    company: props.filters?.company || "",
});

// ── Filter dropdown open/close state ─────────────────────────────────────────
const filterOpen = ref(false);
const toggleFilter = () => (filterOpen.value = !filterOpen.value);

// Count active filters for badge
const activeFilterCount = () => {
    return Object.values(filterForm.value).filter((v) => v !== "" && v !== null).length;
};

// Apply filters
const applyFilters = () => {
    const cleanFilters = Object.fromEntries(
        Object.entries(filterForm.value).filter(
            ([_, v]) => v !== "" && v !== null
        )
    );
    router.get(route("admin.ticketing.index"), cleanFilters, {
        preserveState: true,
        preserveScroll: true,
    });
    filterOpen.value = false;
};

// Clear filters
const clearFilters = () => {
    filterForm.value = {
        status: "",
        date_from: "",
        date_to: "",
        booked_from: "",
        booked_to: "",
        search: "",
        company: "",
    };
    router.get(
        route("admin.ticketing.index"),
        {},
        { preserveState: true, preserveScroll: true }
    );
    filterOpen.value = false;
};

// Export tickets
const exportTickets = () => {
    const cleanFilters = Object.fromEntries(
        Object.entries(filterForm.value).filter(
            ([_, v]) => v !== "" && v !== null
        )
    );
    const queryString = new URLSearchParams(cleanFilters).toString();
    window.location.href =
        route("admin.ticketing.export") +
        (queryString ? "?" + queryString : "");
};

// Status badge color
const getStatusColor = (status) => {
    const colors = {
        Pending: "bg-yellow-100 text-yellow-800 border border-yellow-300",
        "Pending Refund": "bg-orange-100 text-orange-800 border border-orange-300",
        Confirmed: "bg-green-100 text-green-800 border border-green-300",
        Cancelled: "bg-red-100 text-red-800 border border-red-300",
    };
    return colors[status] || "bg-gray-100 text-gray-800";
};

// Format seat numbers
const formatSeats = (seatNo) => {
    if (!seatNo) return "N/A";
    try {
        const seats = typeof seatNo === "string" ? JSON.parse(seatNo) : seatNo;
        return Array.isArray(seats) ? seats.join(", ") : seatNo;
    } catch {
        return seatNo;
    }
};

// Format date — used for Travel_Date display
const formatDate = (date) => {
    if (!date) return "N/A";
    try {
        return new Date(date).toLocaleDateString("en-US", {
            year: "numeric",
            month: "short",
            day: "numeric",
        });
    } catch {
        return date;
    }
};

// Format booking/created date — used for created_at display
const formatBookingDate = (date) => {
    if (!date) return "N/A";
    try {
        return new Date(date).toLocaleString("en-US", {
            year: "numeric",
            month: "short",
            day: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        });
    } catch {
        return date;
    }
};
</script>

<template>

    <Head title="Ticketing History" />

    <AdminLayout title="Royal Movers">
        <div>
            <!-- ── Header with Stats ───────────────────────────────────────── -->
            <div class="mb-6">
                <!-- Title row + action buttons -->
                <div class="flex items-center justify-between mb-4">
                    <h1 class="text-2xl font-bold text-gray-800">
                        Ticketing History
                    </h1>

                    <!-- Top-right action buttons -->
                    <div class="flex items-center gap-2">
                        <!-- Export CSV -->
                        <button @click="exportTickets"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Export CSV
                        </button>

                        <!-- Filter button with badge -->
                        <div class="relative">
                            <button @click="toggleFilter"
                                class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                                :class="filterOpen ? 'ring-2 ring-blue-500 border-blue-500' : ''">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z" />
                                </svg>
                                Filters
                                <!-- Active count badge -->
                                <span v-if="activeFilterCount() > 0"
                                    class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-blue-600 rounded-full">
                                    {{ activeFilterCount() }}
                                </span>
                            </button>

                            <!-- ── Dropdown Filter Panel ───────────────────── -->
                            <div v-if="filterOpen"
                                class="absolute right-0 z-50 mt-2 bg-white border border-gray-200 rounded-xl shadow-xl w-80">
                                <!-- Dropdown header -->
                                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                                    <span class="text-sm font-semibold text-gray-700">Filter Tickets</span>
                                    <button @click="filterOpen = false"
                                        class="text-gray-400 hover:text-gray-600 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Filter fields -->
                                <div class="p-4 space-y-3">
                                    <!-- Search -->
                                    <div>
                                        <label class="block mb-1 text-xs font-medium text-gray-600">
                                            Search
                                        </label>
                                        <input v-model="filterForm.search" type="text"
                                            placeholder="PNR, Name, Contact..." @keyup.enter="applyFilters"
                                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" />
                                    </div>

                                    <!-- Status -->
                                    <div>
                                        <label class="block mb-1 text-xs font-medium text-gray-600">
                                            Status
                                        </label>
                                        <select v-model="filterForm.status"
                                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                                            <option value="">All Status</option>
                                            <option value="Pending">Pending</option>
                                            <option value="Confirmed">Confirmed</option>
                                            <option value="Pending Refund">Pending Refund</option>
                                            <option value="Cancelled">Cancelled</option>
                                        </select>
                                    </div>

                                    <!-- Company -->
                                    <div>
                                        <label class="block mb-1 text-xs font-medium text-gray-600">
                                            Company
                                        </label>
                                        <select v-model="filterForm.company"
                                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                                            <option value="">All Companies</option>
                                            <option v-for="company in companies" :key="company" :value="company">
                                                {{ company }}
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Date Range -->
                                    <div>
                                        <label class="block mb-1 text-xs font-medium text-gray-600">
                                            Travel Date Range
                                        </label>
                                        <div class="grid grid-cols-2 gap-2">
                                            <input v-model="filterForm.date_from" type="date" placeholder="From"
                                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" />
                                            <input v-model="filterForm.date_to" type="date" placeholder="To"
                                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" />
                                        </div>
                                    </div>

                                    <!-- Booking Date Range -->
                                    <div>
                                        <label class="block mb-1 text-xs font-medium text-gray-600">
                                            Booking Date Range
                                        </label>
                                        <div class="grid grid-cols-2 gap-2">
                                            <input v-model="filterForm.booked_from" type="date" placeholder="From"
                                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" />
                                            <input v-model="filterForm.booked_to" type="date" placeholder="To"
                                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Dropdown footer actions -->
                                <div
                                    class="flex items-center justify-between gap-2 px-4 py-3 border-t border-gray-100 bg-gray-50 rounded-b-xl">
                                    <button @click="clearFilters"
                                        class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors">
                                        Reset
                                    </button>
                                    <button @click="applyFilters"
                                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                                        Apply Filters
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards (unchanged) -->
                <div class="grid grid-cols-2 gap-4 mb-6 md:grid-cols-4">
                    <div class="p-4 bg-white rounded-lg shadow">
                        <div class="text-sm text-gray-600">Total Tickets</div>
                        <div class="text-2xl font-bold text-gray-900">{{ stats.total }}</div>
                    </div>
                    <div class="p-4 bg-white rounded-lg shadow">
                        <div class="text-sm text-gray-600">Pending</div>
                        <div class="text-2xl font-bold text-yellow-600">{{ stats.pending }}</div>
                    </div>
                    <div class="p-4 bg-white rounded-lg shadow">
                        <div class="text-sm text-gray-600">Confirmed</div>
                        <div class="text-2xl font-bold text-green-600">{{ stats.confirmed }}</div>
                    </div>
                    <div class="p-4 bg-white rounded-lg shadow">
                        <div class="text-sm text-gray-600">Cancelled</div>
                        <div class="text-2xl font-bold text-red-600">{{ stats.cancelled }}</div>
                    </div>
                </div>
            </div>

            <!-- Tickets Table (unchanged) -->
            <div class="overflow-hidden bg-white rounded-lg shadow">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                    PNR No</th>
                                <th
                                    class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                    Passenger</th>
                                <th
                                    class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                    Route</th>
                                <th
                                    class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                    Travel Date</th>
                                <th
                                    class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                    Seats</th>
                                <th
                                    class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                    Fare</th>
                                <th
                                    class="px-4 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">
                                    Status</th>
                                <th
                                    class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                    Company</th>
                                <th
                                    class="px-4 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover:bg-gray-50">
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="text-xs font-medium text-gray-900 sm:text-sm md:text-base">{{
                                        ticket.PNR_No }}</div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="text-xs font-medium text-gray-900 sm:text-sm md:text-base">{{
                                        ticket.Passenger_Name }}</div>
                                    <div class="text-xs text-gray-500 sm:text-sm md:text-base">{{ ticket.Contact_No }}
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="text-xs text-gray-900 sm:text-sm md:text-base">
                                        {{ ticket.from_city?.City_Name || "N/A" }} → {{ ticket.to_city?.City_Name ||
                                        "N/A" }}
                                    </div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="text-xs text-gray-900 sm:text-sm md:text-base">{{
                                        formatDate(ticket.Travel_Date) }}</div>
                                    <div class="text-xs text-gray-500 sm:text-sm md:text-base">{{ ticket.Travel_Time }}
                                    </div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="text-xs text-gray-900 sm:text-sm md:text-base">{{
                                        formatSeats(ticket.Seat_No) }}</div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="text-xs font-medium text-gray-900 sm:text-sm md:text-base">PKR {{
                                        ticket.Fare }}</div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="flex justify-center">
                                        <span
                                            :class="['inline-flex items-center px-3 py-1 text-xs font-medium rounded-full', getStatusColor(ticket.Status)]">
                                            {{ ticket.Status }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="text-xs text-gray-900 sm:text-sm md:text-base">{{ ticket.Company_Name ||
                                        "N/A" }}</div>
                                </td>
                                <td
                                    class="px-4 py-4 text-xs font-medium text-center sm:text-sm md:text-base whitespace-nowrap">
                                    <Link :href="route('admin.ticketing.show', ticket.id)"
                                        class="text-blue-600 hover:text-blue-900">
                                        View Details
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="tickets.data.length === 0">
                                <td colspan="9" class="px-4 py-8 text-center text-gray-500">No tickets found</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination (unchanged) -->
                <div v-if="tickets.data.length > 0" class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="text-sm text-gray-700">
                            Showing {{ tickets.from }} to {{ tickets.to }} of {{ tickets.total }} results
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <template v-for="(link, index) in tickets.links" :key="index">
                                <Link v-if="link.url" :href="link.url"
                                    :class="['px-3 py-1 text-sm border rounded', link.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50']"
                                    v-html="link.label" />
                                <span v-else
                                    class="px-3 py-1 text-sm border rounded opacity-50 cursor-not-allowed bg-white text-gray-400 border-gray-300"
                                    v-html="link.label" />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
