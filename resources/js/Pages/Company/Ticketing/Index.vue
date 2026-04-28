<script setup>
import { Head, router, Link } from "@inertiajs/vue3";
import CompanyLayout from "@/Layouts/CompanyLayout.vue";
import { ref } from "vue";
import toast from '@/Services/toast'

const props = defineProps({
    tickets: Object,
    stats: Object,
    companies: Array,
    filters: Object,
});

// Filter state
const filterForm = ref({
    status: props.filters?.status || "",
    date_from: props.filters?.date_from || "",
    date_to: props.filters?.date_to || "",
    booked_from: props.filters?.booked_from || "",
    booked_to: props.filters?.booked_to || "",
    search: props.filters?.search || "",
    company: props.filters?.company || "",
});

// ── Filter dropdown ───────────────────────────────────────────────────────────
const filterOpen = ref(false);
const toggleFilter = () => (filterOpen.value = !filterOpen.value);

const activeFilterCount = () =>
    Object.values(filterForm.value).filter((v) => v !== "" && v !== null).length;

const applyFilters = () => {
    const cleanFilters = Object.fromEntries(
        Object.entries(filterForm.value).filter(([_, v]) => v !== "" && v !== null)
    );
    router.get(route("company.ticketing.index"), cleanFilters, {
        preserveState: true,
        preserveScroll: true,
    });
    filterOpen.value = false;
};

const clearFilters = () => {
    filterForm.value = { status: "", date_from: "", date_to: "", booked_from: "", booked_to: "", search: "", company: "" };
    router.get(route("company.ticketing.index"), {}, { preserveState: true, preserveScroll: true });
    filterOpen.value = false;
};

// ── Export tickets ──────────────────────────────────────────────────────────
const isExporting = ref(false);
const exportTickets = async () => {
    isExporting.value = true

    try {
        const params = new URLSearchParams()

        if (filterForm.value.status) params.append('status', filterForm.value.status)
        if (filterForm.value.date_from) params.append('date_from', filterForm.value.date_from)
        if (filterForm.value.date_to) params.append('date_to', filterForm.value.date_to)
        if (filterForm.value.booked_from) params.append('booked_from', filterForm.value.booked_from)
        if (filterForm.value.booked_to) params.append('booked_to', filterForm.value.booked_to)
        if (filterForm.value.search) params.append('search', filterForm.value.search)
        if (filterForm.value.company) params.append('company', filterForm.value.company)

        const url = route('company.ticketing.export') + (params.toString() ? '?' + params.toString() : '')

        const link = document.createElement('a')
        link.href = url
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)

        toast.success('Export Started', 'Tickets export download has started.')
    } catch (error) {
        console.error('Export failed:', error)
        toast.error('Export Failed', 'Unable to export tickets. Try again.')
    } finally {
        setTimeout(() => {
            isExporting.value = false
        }, 2000)
    }
}

// ── Ticket Detail Modal ───────────────────────────────────────────────────────
const modalOpen = ref(false);
const selectedTicket = ref(null);
const modalLoading = ref(false);

const openTicketModal = async (ticketId) => {
    modalOpen.value = true
    modalLoading.value = true
    selectedTicket.value = null

    try {
        const response = await fetch(route("company.ticketing.show", ticketId), {
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
        })

        const data = await response.json()
        selectedTicket.value = data.ticket ?? data
    } catch (e) {
        console.error("Failed to load ticket", e)
        toast.error('Error', 'Failed to load ticket details')
    } finally {
        modalLoading.value = false
    }
}

const closeModal = () => {
    modalOpen.value = false;
    selectedTicket.value = null;
};

// ── Helpers ───────────────────────────────────────────────────────────────────
const getStatusColor = (status) => {
    const colors = {
        Pending: "bg-yellow-100 text-yellow-800 border border-yellow-300",
        "Pending Refund": "bg-orange-100 text-orange-800 border border-orange-300",
        Confirmed: "bg-green-100 text-green-800 border border-green-300",
        Cancelled: "bg-red-100 text-red-800 border border-red-300",
    };
    return colors[status] || "bg-gray-100 text-gray-800";
};

const getStatusBadgeColor = (status) => {
    if (status === "Confirmed") return "bg-green-100 text-green-800";
    if (status === "Pending") return "bg-yellow-100 text-yellow-800";
    if (status === "Cancelled") return "bg-red-100 text-red-800";
    return "bg-orange-100 text-orange-800";
};

const formatSeats = (seatNo) => {
    if (!seatNo) return "N/A";
    try {
        const seats = typeof seatNo === "string" ? JSON.parse(seatNo) : seatNo;
        return Array.isArray(seats) ? seats.join(", ") : seatNo;
    } catch { return seatNo; }
};

const formatDate = (date) => {
    if (!date) return "N/A";
    try {
        return new Date(date).toLocaleDateString("en-US", { year: "numeric", month: "short", day: "numeric" });
    } catch { return date; }
};

const formatDateTime = (dateTime) => {
    if (!dateTime) return "N/A";
    try {
        return new Date(dateTime).toLocaleString("en-US", {
            year: "numeric", month: "short", day: "numeric", hour: "2-digit", minute: "2-digit",
        });
    } catch { return dateTime; }
};

const isLoadingTicket = ref(false);
// Update the download ticket function
const downloadTicket = async (pnr, customerId) => {
    if (!pnr) {
        toast.error('Missing Data', 'PNR not found for ticket download')
        return
    }

    isLoadingTicket.value = true

    try {
        const response = await axios.post("/api/bookings/generate-ticket", {
            pnr,
            customer_id: customerId,
        })

        if (response.data.success) {
            const ticketUrl = response.data.data.ticket_url

            const link = document.createElement("a")
            link.href = ticketUrl
            link.download = `ticket_${pnr}.jpg`
            link.target = "_blank"

            document.body.appendChild(link)
            link.click()
            document.body.removeChild(link)

            URL.revokeObjectURL(ticketUrl)

            toast.success('Download Started', 'Ticket download has started.')
        } else {
            toast.error('Failed', response.data.message || 'Ticket generation failed.')
        }
    } catch (error) {
        console.error("Ticket generation error:", error)
        toast.error('Error', 'Something went wrong while generating ticket.')
    } finally {
        isLoadingTicket.value = false
    }
}
</script>

<template>

    <Head title="Ticketing History" />

    <CompanyLayout title="Royal Movers">
        <div>
            <!-- ── Header ─────────────────────────────────────────────────── -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h1 class="text-2xl font-bold text-gray-800">Ticketing History</h1>

                    <div class="flex items-center gap-2">
                        <!-- Export CSV -->
                        <button @click="exportTickets" :disabled="isExporting"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-green-700 rounded-lg hover:bg-green-800 transition-colors disabled:opacity-70 disabled:cursor-not-allowed">

                            <!-- Spinner when exporting -->
                            <svg v-if="isExporting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                            </svg>

                            <!-- Download icon when idle -->
                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>

                            {{ isExporting ? 'Exporting...' : 'Export Excel' }}
                        </button>

                        <!-- Filter button -->
                        <div class="relative">
                            <button @click="toggleFilter"
                                class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                                :class="filterOpen ? 'ring-2 ring-blue-500 border-blue-500' : ''">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z" />
                                </svg>
                                Filters
                                <span v-if="activeFilterCount() > 0"
                                    class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-blue-600 rounded-full">
                                    {{ activeFilterCount() }}
                                </span>
                            </button>

                            <!-- Dropdown Filter Panel -->
                            <div v-if="filterOpen"
                                class="absolute right-0 z-50 mt-2 bg-white border border-gray-200 rounded-xl shadow-xl w-80">
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

                                <div class="p-4 space-y-3">
                                    <div>
                                        <label class="block mb-1 text-xs font-medium text-gray-600">Search</label>
                                        <input v-model="filterForm.search" type="text"
                                            placeholder="PNR, Name, Contact..." @keyup.enter="applyFilters"
                                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" />
                                    </div>
                                    <div>
                                        <label class="block mb-1 text-xs font-medium text-gray-600">Status</label>
                                        <select v-model="filterForm.status"
                                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                                            <option value="">All Status</option>
                                            <option value="Pending">Pending</option>
                                            <option value="Confirmed">Confirmed</option>
                                            <option value="Pending Refund">Pending Refund</option>
                                            <option value="Cancelled">Cancelled</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block mb-1 text-xs font-medium text-gray-600">Company</label>
                                        <select v-model="filterForm.company"
                                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                                            <option value="">All Companies</option>
                                            <option v-for="company in companies" :key="company" :value="company">{{
                                                company }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block mb-1 text-xs font-medium text-gray-600">Travel Date
                                            Range</label>
                                        <div class="grid grid-cols-2 gap-2">
                                            <input v-model="filterForm.date_from" type="date"
                                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" />
                                            <input v-model="filterForm.date_to" type="date"
                                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" />
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block mb-1 text-xs font-medium text-gray-600">Booking Date
                                            Range</label>
                                        <div class="grid grid-cols-2 gap-2">
                                            <input v-model="filterForm.booked_from" type="date"
                                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" />
                                            <input v-model="filterForm.booked_to" type="date"
                                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" />
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="flex items-center justify-between gap-2 px-4 py-3 border-t border-gray-100 bg-gray-50 rounded-b-xl">
                                    <button @click="clearFilters"
                                        class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors">Reset</button>
                                    <button @click="applyFilters"
                                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">Apply
                                        Filters</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-2 gap-4 mb-6 md:grid-cols-4">

                    <!-- Total Tickets -->
                    <div
                        class="relative p-5 bg-white border border-gray-100 rounded shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 overflow-hidden">
                        <div class="absolute top-0 left-0 w-1 h-full bg-blue-500"></div>

                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm font-medium text-gray-500">Total Tickets</div>
                                <div class="mt-1 text-3xl font-bold text-gray-900 tracking-tight">
                                    {{ stats.total }}
                                </div>
                            </div>
                            <div class="p-3 text-blue-600 bg-blue-50 rounded-xl border boder-blue-100">
                                <i class="bi bi-ticket-perforated text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Pending -->
                    <div
                        class="relative p-5 bg-white border border-gray-100 rounded shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 overflow-hidden">
                        <div class="absolute top-0 left-0 w-1 h-full bg-yellow-400"></div>

                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm font-medium text-gray-500">Pending</div>
                                <div class="mt-1 text-3xl font-bold text-yellow-600 tracking-tight">
                                    {{ stats.pending }}
                                </div>
                            </div>
                            <div class="p-3 text-yellow-600 bg-yellow-50 rounded-xl border boder-yellow-100">
                                <i class="bi bi-hourglass-split text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Confirmed -->
                    <div
                        class="relative p-5 bg-white border border-gray-100 rounded shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 overflow-hidden">
                        <div class="absolute top-0 left-0 w-1 h-full bg-green-500"></div>

                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm font-medium text-gray-500">Confirmed</div>
                                <div class="mt-1 text-3xl font-bold text-green-600 tracking-tight">
                                    {{ stats.confirmed }}
                                </div>
                            </div>
                            <div class="p-3 text-green-600 bg-green-50 rounded-xl border border-green-100">
                                <i class="bi bi-check-circle text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Cancelled -->
                    <div
                        class="relative p-5 bg-white border border-gray-100 rounded shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 overflow-hidden">
                        <div class="absolute top-0 left-0 w-1 h-full bg-red-500"></div>

                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm font-medium text-gray-500">Cancelled</div>
                                <div class="mt-1 text-3xl font-bold text-red-600 tracking-tight">
                                    {{ stats.cancelled }}
                                </div>
                            </div>
                            <div class="p-3 text-red-600 bg-red-50 rounded-xl border border-red-100">
                                <i class="bi bi-x-circle text-xl"></i>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Tickets Table -->
            <div class="overflow-hidden bg-white border border-gray-200 rounded-lg shadow">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-3 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                    PNR No</th>
                                <th
                                    class="px-3 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                    Passenger</th>
                                <th
                                    class="px-3 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                    Route</th>
                                <th
                                    class="px-3 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                    Travel Date</th>
                                <th
                                    class="px-3 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                    Booking Date</th>
                                <th
                                    class="px-3 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                    Seats</th>
                                <th
                                    class="px-3 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                    Fare</th>
                                <th
                                    class="px-3 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">
                                    Status</th>
                                <!-- <th
                                    class="px-3 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                    Company
                                </th> -->
                                <th
                                    class="px-3 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover:bg-gray-50">
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <div class="text-xs font-medium text-gray-900 sm:text-sm">{{ ticket.PNR_No }}</div>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="text-xs font-medium text-gray-900 sm:text-sm">
                                        {{ ticket.Passenger_Name }}
                                    </div>
                                    <div class="text-xs text-gray-500">{{ ticket.Contact_No }}</div>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="text-xs text-gray-900 sm:text-sm">
                                        {{ ticket.fromCity?.City_Name || "N/A" }} →
                                        {{ ticket.toCity?.City_Name || "N/A" }}
                                    </div>
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <div class="text-xs text-gray-900 sm:text-sm">{{ formatDate(ticket.Travel_Date) }}
                                    </div>
                                    <div class="text-xs text-gray-500">{{ ticket.Travel_Time }}</div>
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <div class="text-xs text-gray-900 sm:text-sm">
                                        {{ formatDateTime(ticket.created_at) }}
                                    </div>
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <div class="text-xs text-gray-900 sm:text-sm">{{ formatSeats(ticket.Seat_No) }}
                                    </div>
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <div class="text-xs font-medium text-gray-900 sm:text-sm">PKR {{ ticket.Fare }}
                                    </div>
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <div class="flex justify-center">
                                        <span
                                            :class="['inline-flex items-center px-3 py-1 text-xs font-medium rounded-full', getStatusColor(ticket.Status)]">
                                            {{ ticket.Status }}
                                        </span>
                                    </div>
                                </td>
                                <!-- <td class="px-3 py-3 whitespace-nowrap">
                                    <div class="text-xs text-gray-900 sm:text-sm">{{ ticket.Company_Name || "N/A" }}
                                    </div>
                                </td> -->
                                <!-- View Details → opens modal -->
                                <td class="px-3 py-3 text-center whitespace-nowrap">
                                    <button @click="openTicketModal(ticket.id)"
                                        class="text-sm font-medium text-blue-600 hover:text-blue-900 hover:underline transition-colors">
                                        View Details
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="tickets.data.length === 0">
                                <td colspan="9" class="px-4 py-8 text-center text-gray-500">No tickets found</td>
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

        <!-- ── Ticket Detail Modal ─────────────────────────────────────────── -->
        <Teleport to="body">
            <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeModal" />

                <!-- Modal Panel -->
                <div
                    class="relative w-full max-w-4xl max-h-[90vh] bg-white rounded-2xl shadow-2xl flex flex-col overflow-hidden">

                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 shrink-0">
                        <div class="flex items-center gap-3">
                            <h2 class="text-lg font-bold text-gray-800">Ticket Details</h2>
                            <span v-if="selectedTicket"
                                :class="['px-3 py-1 rounded-full text-xs font-semibold', getStatusBadgeColor(selectedTicket.Status)]">
                                {{ selectedTicket.Status }}
                            </span>
                        </div>
                        <button @click="closeModal"
                            class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="overflow-y-auto flex-1 p-6">

                        <!-- Loading -->
                        <div v-if="modalLoading" class="flex items-center justify-center py-16">
                            <div class="flex flex-col items-center gap-3">
                                <svg class="w-8 h-8 text-blue-600 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                                </svg>
                                <p class="text-sm text-gray-500">Loading ticket details...</p>
                            </div>
                        </div>

                        <!-- Content -->
                        <div v-else-if="selectedTicket" class="space-y-5">

                            <!-- Row 1: Passenger + Journey -->
                            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                                <!-- Passenger -->
                                <div class="p-5 bg-gray-50 border border-gray-200 rounded-xl">
                                    <h3 class="mb-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        Passenger
                                        Information</h3>
                                    <dl class="space-y-2.5">
                                        <div class="flex justify-between">
                                            <dt class="text-sm text-gray-500">PNR Number</dt>
                                            <dd class="text-sm font-bold text-red-700 font-mono">{{
                                                selectedTicket.PNR_No }}
                                            </dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-sm text-gray-500">Name</dt>
                                            <dd class="text-sm font-medium text-gray-900">{{
                                                selectedTicket.Passenger_Name }}
                                            </dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-sm text-gray-500">Contact</dt>
                                            <dd class="text-sm font-medium text-gray-900">{{ selectedTicket.Contact_No
                                                }}</dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-sm text-gray-500">Emergency Contact</dt>
                                            <dd class="text-sm font-medium text-gray-900">{{
                                                selectedTicket.Emergency_Contact ||
                                                'N/A' }}</dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-sm text-gray-500">CNIC</dt>
                                            <dd class="text-sm font-medium text-gray-900">{{ selectedTicket.CNIC ||
                                                'N/A' }}
                                            </dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-sm text-gray-500">Gender</dt>
                                            <dd class="text-sm font-medium text-gray-900">{{ selectedTicket.Gender ||
                                                'N/A' }}
                                            </dd>
                                        </div>
                                    </dl>
                                </div>

                                <!-- Journey -->
                                <div class="p-5 bg-gray-50 border border-gray-200 rounded-xl">
                                    <h3 class="mb-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        Journey
                                        Information</h3>
                                    <dl class="space-y-2.5">
                                        <div class="flex justify-between">
                                            <dt class="text-sm text-gray-500">From</dt>
                                            <!-- supports both snake_case (index) and camelCase (show controller) -->
                                            <dd class="text-sm font-medium text-gray-900">
                                                {{ selectedTicket.from_city?.City_Name ||
                                                    selectedTicket.fromCity?.City_Name ||
                                                    'N/A' }}
                                            </dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-sm text-gray-500">To</dt>
                                            <dd class="text-sm font-medium text-gray-900">
                                                {{ selectedTicket.to_city?.City_Name || selectedTicket.toCity?.City_Name
                                                    ||
                                                    'N/A' }}
                                            </dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-sm text-gray-500">Travel Date</dt>
                                            <dd class="text-sm font-medium text-gray-900">{{
                                                formatDate(selectedTicket.Travel_Date) }}</dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-sm text-gray-500">Travel Time</dt>
                                            <dd class="text-sm font-medium text-gray-900">{{ selectedTicket.Travel_Time
                                                }}</dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-sm text-gray-500">Seat Number</dt>
                                            <dd class="text-sm font-medium text-gray-900">{{
                                                formatSeats(selectedTicket.Seat_No)
                                                }}</dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-sm text-gray-500">Company</dt>
                                            <dd class="text-sm font-medium text-gray-900">{{ selectedTicket.Company_Name
                                                ||
                                                'N/A' }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>

                            <!-- Row 2: Payment + Additional -->
                            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                                <!-- Payment -->
                                <div class="p-5 bg-gray-50 border border-gray-200 rounded-xl">
                                    <h3 class="mb-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        Payment
                                        Information</h3>
                                    <dl class="space-y-2.5">
                                        <div class="flex justify-between">
                                            <dt class="text-sm text-gray-500">Fare</dt>
                                            <dd class="text-sm font-medium text-gray-900">PKR {{ selectedTicket.Fare }}
                                            </dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-sm text-gray-500">Discount</dt>
                                            <dd class="text-sm font-medium text-gray-900">PKR {{ selectedTicket.Discount
                                                || 0 }}
                                            </dd>
                                        </div>
                                        <div class="flex justify-between pt-2 border-t border-gray-200">
                                            <dt class="text-sm font-semibold text-gray-700">Total Amount</dt>
                                            <dd class="text-sm font-bold text-gray-900">
                                                PKR {{ (parseFloat(selectedTicket.Fare || 0) -
                                                    parseFloat(selectedTicket.Discount || 0)).toFixed(2) }}
                                            </dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-sm text-gray-500">Payment Date</dt>
                                            <dd class="text-sm font-medium text-gray-900">{{
                                                formatDateTime(selectedTicket.PaymentDate) }}</dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-sm text-gray-500">Invoice</dt>
                                            <dd class="text-sm font-medium text-gray-900">{{ selectedTicket.Invoice ||
                                                'N/A' }}
                                            </dd>
                                        </div>
                                    </dl>
                                </div>

                                <!-- Additional -->
                                <div class="p-5 bg-gray-50 border border-gray-200 rounded-xl">
                                    <h3 class="mb-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        Additional
                                        Information</h3>
                                    <dl class="space-y-2.5">
                                        <div class="flex justify-between">
                                            <dt class="text-sm text-gray-500">Booking Date</dt>
                                            <dd class="text-sm font-medium text-gray-900">{{
                                                formatDateTime(selectedTicket.Issue_Date) }}</dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-sm text-gray-500">Boarding Terminal</dt>
                                            <dd class="text-sm font-medium text-gray-900">{{
                                                selectedTicket.Boarding_Terminal ||
                                                'N/A' }}</dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-sm text-gray-500">Issued By</dt>
                                            <dd class="text-sm font-medium text-gray-900">{{ selectedTicket.Issued_By ||
                                                'N/A'
                                                }}</dd>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <dt class="text-sm text-gray-500">SMS Sent</dt>
                                            <dd>
                                                <span
                                                    :class="['px-2 py-1 rounded text-xs font-medium', selectedTicket.Is_SMS_Sent ? 'bg-primary text-white' : 'bg-secondary text-white']">
                                                    {{ selectedTicket.Is_SMS_Sent ? 'Yes' : 'No' }}
                                                </span>
                                            </dd>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <dt class="text-sm text-gray-500">Return Ticket</dt>
                                            <dd>
                                                <span
                                                    :class="['px-2 py-1 rounded text-xs font-medium', selectedTicket.Is_Return ? 'bg-primary text-white' : 'bg-secondary text-white']">
                                                    {{ selectedTicket.Is_Return ? 'Yes' : 'No' }}
                                                </span>
                                            </dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-sm text-gray-500">Loyalty Points</dt>
                                            <dd class="text-sm font-medium text-gray-900">{{ selectedTicket.Points || 0
                                                }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <!-- Error -->
                        <div v-else class="py-16 text-center text-gray-400">
                            <p class="text-sm">Failed to load ticket details. Please try again.</p>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div
                        class="px-6 py-4 border-t border-gray-200 bg-gray-50 shrink-0 flex justify-between items-center">
                        <button @click="downloadTicket(selectedTicket.Invoice, selectedTicket.Customer_Id)"
                            :disabled="isLoadingTicket"
                            class="flex items-center justify-center px-4 py-2 text-white rounded-lg bg-primary hover:bg-primary-dark disabled:opacity-70 disabled:cursor-not-allowed">
                            <!-- Show loader when loading -->
                            <svg v-if="isLoadingTicket" class="inline-block w-5 h-5 mr-2 animate-spin" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4">
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
                        <button @click="closeModal"
                            class="px-5 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </CompanyLayout>
</template>
