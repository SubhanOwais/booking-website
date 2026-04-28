<script setup>
import { ref, onMounted, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import axios from 'axios'
import CompanyLayout from '@/Layouts/CompanyLayout.vue'

const props = defineProps({
    tickets: Object,
    filters: Object,
    companyName: String,
})

// Reactive filter state
const filterForm = ref({
    search: props.filters.search || '',
    refund_from: props.filters.refund_from || '',
    refund_to: props.filters.refund_to || '',
    travel_from: props.filters.travel_from || '',
    travel_to: props.filters.travel_to || '',
    refund_percent_range: props.filters.refund_percent_range || '',
})

// Filter panel open/close
const filterOpen = ref(false)

// View modal
const showViewModal = ref(false)
const selectedTicket = ref(null)

// Format helpers
const formatSeats = (seatNo) => {
    if (!seatNo) return 'N/A'
    try {
        const seats = typeof seatNo === 'string' ? JSON.parse(seatNo) : seatNo
        return Array.isArray(seats) ? seats.join(', ') : seatNo
    } catch {
        return seatNo
    }
}

const formatDate = (dateStr) => {
    if (!dateStr) return 'N/A'
    const d = new Date(dateStr)
    return d.toLocaleDateString('en-PK', { year: 'numeric', month: 'short', day: 'numeric' })
}

const formatDateTime = (dateStr) => {
    if (!dateStr) return 'N/A'
    const d = new Date(dateStr)
    return d.toLocaleString('en-PK', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const percentColorClass = (percent) => {
    if (percent >= 90) return 'bg-red-100 text-red-800'
    if (percent >= 75) return 'bg-orange-100 text-orange-800'
    if (percent >= 50) return 'bg-yellow-100 text-yellow-800'
    return 'bg-green-100 text-green-800'
}

// Active filter count
const activeFilterCount = () => {
    let count = 0
    if (filterForm.value.search) count++
    if (filterForm.value.refund_from) count++
    if (filterForm.value.refund_to) count++
    if (filterForm.value.travel_from) count++
    if (filterForm.value.travel_to) count++
    if (filterForm.value.refund_percent_range) count++
    return count
}

// Apply filters (reload page via Inertia)
const applyFilters = () => {
    filterOpen.value = false
    router.get(route('company.refund-report'), filterForm.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}

const clearFilters = () => {
    filterForm.value = {
        search: '',
        refund_from: '',
        refund_to: '',
        travel_from: '',
        travel_to: '',
        refund_percent_range: '',
    }
    applyFilters()
}

// Pagination
const goToPage = (url) => {
    if (!url) return
    router.get(url, filterForm.value, { preserveState: true, preserveScroll: true })
}

// View modal
const openViewModal = (ticket) => {
    selectedTicket.value = ticket
    showViewModal.value = true
}

const closeViewModal = () => {
    showViewModal.value = false
    selectedTicket.value = null
}

// Watch for browser back/forward
onMounted(() => {
    // If filters in URL but not in form, sync
    // Already handled by props
})

const isExporting = ref(false)

const exportTickets = async () => {
    isExporting.value = true
    try {
        // Build query string from current filters
        const params = new URLSearchParams()
        if (filterForm.value.search) params.append('search', filterForm.value.search)
        if (filterForm.value.refund_from) params.append('refund_from', filterForm.value.refund_from)
        if (filterForm.value.refund_to) params.append('refund_to', filterForm.value.refund_to)
        if (filterForm.value.travel_from) params.append('travel_from', filterForm.value.travel_from)
        if (filterForm.value.travel_to) params.append('travel_to', filterForm.value.travel_to)
        if (filterForm.value.refund_percent_range) params.append('refund_percent_range', filterForm.value.refund_percent_range)

        const url = route('company.refund-report.export') + '?' + params.toString()

        // Trigger download via hidden link
        const link = document.createElement('a')
        link.href = url
        link.download = ''
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
    } catch (error) {
        console.error('Export failed:', error)
        alert('Failed to export data. Please try again.')
    } finally {
        isExporting.value = false
    }
}
</script>

<template>

    <Head title="Refund Report" />
    <CompanyLayout>
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-xl font-bold text-slate-800">Refund Report</h1>
                <p class="text-sm text-slate-500 mt-0.5">View all cancelled tickets with refund details</p>
            </div>
            <div class="flex items-center gap-2">
                <!-- Export CSV -->
                <button @click="exportTickets" :disabled="isExporting"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors disabled:opacity-70 disabled:cursor-not-allowed">

                    <!-- Spinner when exporting -->
                    <svg v-if="isExporting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                    </svg>

                    <!-- Download icon when idle -->
                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>

                    {{ isExporting ? 'Exporting...' : 'Export Excel' }}
                </button>
                <!-- Filter Button & Dropdown -->
                <div class="relative">
                    <button @click="filterOpen = !filterOpen"
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

                    <!-- Dropdown Panel -->
                    <div v-if="filterOpen"
                        class="absolute right-0 z-50 mt-2 bg-white border border-gray-200 rounded-xl shadow-xl w-96">
                        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                            <span class="text-sm font-semibold text-gray-700">Filter Refund Reports</span>
                            <button @click="filterOpen = false" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="p-4 space-y-3">
                            <div>
                                <label class="block mb-1 text-xs font-medium text-gray-600">Search</label>
                                <input v-model="filterForm.search" type="text" placeholder="PNR, Passenger, Contact..."
                                    @keyup.enter="applyFilters"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />
                            </div>
                            <div>
                                <label class="block mb-1 text-xs font-medium text-gray-600">Refund Date Range</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <input v-model="filterForm.refund_from" type="date"
                                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg" />
                                    <input v-model="filterForm.refund_to" type="date"
                                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg" />
                                </div>
                            </div>
                            <div>
                                <label class="block mb-1 text-xs font-medium text-gray-600">Travel Date Range</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <input v-model="filterForm.travel_from" type="date"
                                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg" />
                                    <input v-model="filterForm.travel_to" type="date"
                                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg" />
                                </div>
                            </div>
                            <div>
                                <label class="block mb-1 text-xs font-medium text-gray-600">Refund % Range</label>
                                <select v-model="filterForm.refund_percent_range"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
                                    <option value="">All</option>
                                    <option value="90-100">90% - 100%</option>
                                    <option value="75-89">75% - 89%</option>
                                    <option value="50-74">50% - 74%</option>
                                    <option value="1-49">1% - 49%</option>
                                    <option value="0">0% (No Refund)</option>
                                </select>
                            </div>
                        </div>

                        <div
                            class="flex items-center justify-between gap-2 px-4 py-3 border-t border-gray-100 bg-gray-50 rounded-b-xl">
                            <button @click="clearFilters"
                                class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-100">Reset</button>
                            <button @click="applyFilters"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Apply
                                Filters</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">PNR</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">
                                Passenger
                            </th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Route</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">
                                Travel Date
                            </th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Seats</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">
                                Fare (PKR)
                            </th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Refund Amount
                            </th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Refund %</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">
                                Refund Date
                            </th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">
                                Refund By
                            </th>
                            <th class="text-center px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 font-mono font-semibold text-slate-800">{{ ticket.PNR_No }}</td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-800">{{ ticket.Passenger_Name }}</p>
                                <p class="text-xs text-slate-400">{{ ticket.Contact_No }}</p>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ ticket.from_city_name }} <span class="text-slate-400 mx-1">→</span> {{
                                    ticket.to_city_name }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ formatDate(ticket.Travel_Date) }}
                                <span class="text-xs text-slate-400 ml-1">{{ ticket.Travel_Time?.slice(0, 5) }}</span>
                            </td>
                            <td class="px-4 py-3">{{ formatSeats(ticket.Seat_No) }}</td>
                            <td class="px-4 py-3 font-semibold">{{ ticket.Fare }}</td>
                            <td class="px-4 py-3 font-bold text-red-600">{{ ticket.Refund_Amount }}</td>
                            <td class="px-4 py-3">
                                <span
                                    :class="['px-2 py-1 text-xs font-medium rounded-full', percentColorClass(ticket.calculated_percent)]">
                                    {{ ticket.calculated_percent }}%
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-slate-600">
                                {{ formatDate(ticket.Refund_Date) }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700">
                                    {{ ticket.Refunded_By }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button @click="openViewModal(ticket)" class="inline-flex items-center gap-1.5 px-2 py-1 text-xs font-medium rounded-full
               text-blue-700 bg-blue-100 hover:bg-blue-200 hover:text-blue-900
               transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-400">
                                    View
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!tickets.data?.length">
                            <td colspan="10" class="py-12 text-center text-slate-500">
                                No refund records found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="tickets.data?.length"
                class="px-4 py-3 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <p class="text-xs text-slate-400">
                    Showing {{ tickets.from }}–{{ tickets.to }} of {{ tickets.total }} records
                </p>
                <div class="flex flex-wrap gap-1.5">
                    <template v-for="(link, i) in tickets.links" :key="i">
                        <button v-if="link.url" @click="goToPage(link.url)" :class="[
                            'px-3 py-1 text-xs border rounded-lg transition-colors',
                            link.active ? 'bg-primary text-white border-primary' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'
                        ]" v-html="link.label" />
                        <span v-else
                            class="px-3 py-1 text-xs border rounded-lg bg-white text-slate-300 border-slate-200 cursor-not-allowed"
                            v-html="link.label" />
                    </template>
                </div>
            </div>
        </div>

        <!-- View Modal -->
        <Teleport to="body">
            <div v-if="showViewModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
                @click.self="closeViewModal">
                <div class="w-full max-w-2xl bg-white rounded-2xl shadow-2xl max-h-[90vh] flex flex-col">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                        <div>
                            <h3 class="text-base font-bold text-slate-800">Cancelled Ticket Details</h3>
                            <p class="text-xs text-slate-400">PNR: {{ selectedTicket?.PNR_No }}</p>
                        </div>
                        <button @click="closeViewModal"
                            class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-slate-100">
                            <i class="bi bi-x-lg text-sm"></i>
                        </button>
                    </div>
                    <div class="overflow-y-auto flex-1 p-6 space-y-4" v-if="selectedTicket">
                        <!-- Ticket Info -->
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div><span class="text-slate-500">Passenger:</span> <span class="font-medium">{{
                                selectedTicket.Passenger_Name }}</span></div>
                            <div><span class="text-slate-500">Contact:</span> <span class="font-medium">{{
                                selectedTicket.Contact_No }}</span></div>
                            <div><span class="text-slate-500">Route:</span> <span class="font-medium">{{
                                selectedTicket.from_city_name }} → {{ selectedTicket.to_city_name }}</span></div>
                            <div><span class="text-slate-500">Travel:</span> <span class="font-medium">{{
                                formatDate(selectedTicket.Travel_Date) }} {{ selectedTicket.Travel_Time?.slice(0, 5)
                                    }}</span></div>
                            <div><span class="text-slate-500">Seats:</span> <span class="font-medium">{{
                                formatSeats(selectedTicket.Seat_No) }}</span></div>
                            <div><span class="text-slate-500">Fare:</span> <span class="font-medium">PKR {{
                                selectedTicket.Fare
                                    }}</span></div>
                        </div>
                        <!-- Refund Info -->
                        <div class="border-t border-slate-100 pt-3">
                            <h4 class="font-semibold text-slate-700 mb-2">Refund Details</h4>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div><span class="text-slate-500">Refund Amount:</span> <span
                                        class="font-bold text-red-600">PKR
                                        {{ selectedTicket.Refund_Amount }}</span></div>
                                <div><span class="text-slate-500">Refund Percentage:</span> <span class="font-medium">{{
                                    selectedTicket.calculated_percent }}%</span></div>
                                <div><span class="text-slate-500">Refund Date:</span> <span class="font-medium">{{
                                    formatDateTime(selectedTicket.Refund_Date) }}</span></div>
                                <div><span class="text-slate-500">Processed By:</span> <span class="font-medium">{{
                                    selectedTicket.Refunded_By }}</span></div>
                            </div>
                            <div v-if="selectedTicket.Refund_Reason"
                                class="mt-3 p-3 bg-slate-50 border border-gray-200 rounded-lg">
                                <p class="text-xs text-slate-500 uppercase">Refund Reason</p>
                                <p class="text-sm text-slate-700 mt-1">{{ selectedTicket.Refund_Reason }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 rounded-b-2xl flex justify-end">
                        <button @click="closeViewModal"
                            class="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </CompanyLayout>
</template>
