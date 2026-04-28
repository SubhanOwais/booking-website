<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import axios from 'axios'
import CompanyLayout from '@/Layouts/CompanyLayout.vue'
import CompanyRefundModal from './RefundModal.vue'
import toast from '@/Services/toast'

// ── Props (page shell only – no ticket data from server) ──────────────────
const props = defineProps({
    stats: Object,
    companyConfig: Object,
})

// ── State ─────────────────────────────────────────────────────────────────
const tickets = ref(null)          // null = initial loading
const stats = ref(props.stats)
const pageLoading = ref(true)          // first load spinner
const liveLoading = ref(false)         // background poll indicator
const lastUpdated = ref(new Date())
const showModal = ref(false)
const selectedTicket = ref(null)
const filterOpen = ref(false)         // controls dropdown visibility

const filterForm = ref({
    search: '',
    status: '',
    date_from: '',
    date_to: '',
})

// Polling every 8 seconds
let pollInterval = null
const POLL_MS = 8000

// ── Helper: active filter count (for badge) ──────────────────────────────
const activeFilterCount = () => {
    let count = 0
    if (filterForm.value.search) count++
    if (filterForm.value.status) count++
    if (filterForm.value.date_from) count++
    if (filterForm.value.date_to) count++
    return count
}

// ── Data fetching ─────────────────────────────────────────────────────────
const fetchData = async (showPageLoader = false) => {
    if (showPageLoader) pageLoading.value = true
    try {
        const params = Object.fromEntries(
            Object.entries(filterForm.value).filter(([, v]) => v !== '')
        )
        const { data } = await axios.get(route('company.refund.data'), { params })
        tickets.value = data.tickets
        stats.value = data.stats
        lastUpdated.value = new Date()
        toast.add({
            severity: 'info',
            summary: 'Success',
            detail: 'Refund tickets loaded Successfully.',
            life: 3000,
        })
    } catch (e) {
        console.error('Refund data fetch error:', e.response?.data || e.message)
        const errorMsg = e.response?.data?.message || e.response?.data?.error || e.message
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: `Failed to load refund tickets: ${errorMsg}`,
            life: 3000,
        })
    } finally {
        pageLoading.value = false
    }
}

const pollLive = async () => {
    try {
        liveLoading.value = true
        const params = Object.fromEntries(
            Object.entries(filterForm.value).filter(([, v]) => v !== '')
        )
        const { data } = await axios.get(route('company.refund.live'), { params })
        tickets.value = data.tickets
        stats.value = data.stats
        lastUpdated.value = new Date()
    } catch {
        // silently ignore – next poll will retry
    } finally {
        liveLoading.value = false
    }
}

const startPolling = () => { stopPolling(); pollInterval = setInterval(pollLive, POLL_MS) }
const stopPolling = () => { if (pollInterval) { clearInterval(pollInterval); pollInterval = null } }

onMounted(async () => {
    await fetchData(true)
    startPolling()
})

onUnmounted(() => stopPolling())

// ── Filters ───────────────────────────────────────────────────────────────
const applyFilters = async () => {
    filterOpen.value = false
    await fetchData()
}

const clearFilters = async () => {
    filterForm.value = { search: '', status: '', date_from: '', date_to: '' }
    filterOpen.value = false
    await fetchData()
}

// ── Pagination ────────────────────────────────────────────────────────────
const goToPage = async (url) => {
    if (!url) return
    try {
        liveLoading.value = true
        const params = Object.fromEntries(
            Object.entries(filterForm.value).filter(([, v]) => v !== '')
        )
        const { data } = await axios.get(url, { params })
        tickets.value = data.tickets
        stats.value = data.stats
        lastUpdated.value = new Date()
    } catch {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to load page.',
            life: 3000,
        })
    } finally {
        liveLoading.value = false
    }
}

// ── Modal ─────────────────────────────────────────────────────────────────
const openModal = (ticket) => { selectedTicket.value = ticket; showModal.value = true }
const closeModal = () => { showModal.value = false; selectedTicket.value = null }

const handleRefundSuccess = (message) => {
    closeModal()
    toast.add({
        severity: 'success',
        summary: 'Refund Processed',
        detail: message || 'Refund completed successfully.',
        life: 3000,
    })
    pollLive()
}

const handleRefundConflict = () => {
    closeModal()
    toast.add({
        severity: 'warning',
        summary: 'Already Processed',
        detail: 'This ticket was already processed by another user.',
        life: 3000,
    })
    pollLive()
}

// ── Formatters ────────────────────────────────────────────────────────────
const formatSeats = (s) => {
    if (!s) return 'N/A'
    try {
        const arr = typeof s === 'string' ? JSON.parse(s) : s
        return Array.isArray(arr) ? arr.join(', ') : s
    } catch { return s }
}

const formatDate = (d) => {
    if (!d) return 'N/A'
    try {
        return new Date(d).toLocaleDateString('en-US', {
            year: 'numeric', month: 'short', day: 'numeric',
        })
    } catch { return d }
}

const statusClass = (status) => {
    if (status === 'Pending Refund') return 'bg-yellow-100 text-yellow-800 border border-yellow-200'
    if (status === 'Cancelled') return 'bg-red-100 text-red-800 border border-red-200'
    return 'bg-gray-100 text-gray-700 border border-gray-200'
}

</script>

<template>

    <Head title="Refund Tickets" />
    <CompanyLayout>

        <!-- ── Header ────────────────────────────────────────────────────── -->
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-xl font-bold text-slate-800">Refund Tickets</h1>
                <p class="text-sm text-slate-500 mt-0.5">Process refund requests for your company</p>
            </div>
            <div class="flex items-center gap-3">
                <!-- Live indicator -->
                <div
                    class="flex items-center gap-2 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-500 shadow-sm">
                    <span class="relative flex h-2 w-2">
                        <span
                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                    </span>
                    Live &nbsp;·&nbsp; {{ lastUpdated.toLocaleTimeString() }}
                    <svg v-if="liveLoading" class="animate-spin w-3 h-3 ml-1 text-slate-400" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                    </svg>
                </div>
                <!-- Refresh Button -->
                <button @click="fetchData(true)"
                    class="flex items-center gap-2 px-3 py-2 text-sm text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                    <i class="bi bi-arrow-clockwise" :class="{ 'animate-spin': loading }"></i>
                    Refresh
                </button>
                <!-- ── Filter Dropdown (replaces old filter bar) ──────────────────── -->
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
                        class="absolute right-0 z-50 mt-2 bg-white border border-gray-200 rounded-xl shadow-xl w-80">
                        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                            <span class="text-sm font-semibold text-gray-700">Filter Tickets</span>
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
                                <input v-model="filterForm.search" type="text" placeholder="PNR, Name, Contact..."
                                    @keyup.enter="applyFilters"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" />
                            </div>
                            <div>
                                <label class="block mb-1 text-xs font-medium text-gray-600">Status</label>
                                <select v-model="filterForm.status"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                                    <option value="">All Status</option>
                                    <option value="Pending Refund">Pending Refund</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div>
                                <label class="block mb-1 text-xs font-medium text-gray-600">Travel Date Range</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <input v-model="filterForm.date_from" type="date"
                                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" />
                                    <input v-model="filterForm.date_to" type="date"
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

        <!-- ── Stats ─────────────────────────────────────────────────────── -->
        <div class="grid grid-cols-2 gap-3 mb-5">
            <div class="bg-white rounded-xl border border-slate-200 px-4 py-3">
                <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Pending Refund</p>
                <p class="text-2xl font-bold text-yellow-500 mt-1">{{ stats?.pending_refund ?? 0 }}</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 px-4 py-3">
                <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Total Cancelled</p>
                <p class="text-2xl font-bold text-red-500 mt-1">{{ stats?.total_cancelled ?? 0 }}</p>
            </div>
        </div>

        <!-- ── Table ──────────────────────────────────────────────────────── -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">

            <!-- Initial page load skeleton -->
            <div v-if="pageLoading" class="p-8 space-y-4">
                <div v-for="i in 5" :key="i" class="flex items-center gap-4">
                    <div class="h-3 bg-slate-200 rounded animate-pulse w-16"></div>
                    <div class="h-3 bg-slate-200 rounded animate-pulse w-24"></div>
                    <div class="h-3 bg-slate-200 rounded animate-pulse flex-1"></div>
                    <div class="h-3 bg-slate-200 rounded animate-pulse w-20"></div>
                    <div class="h-6 bg-slate-200 rounded-full animate-pulse w-16"></div>
                    <div class="h-7 bg-slate-200 rounded-lg animate-pulse w-16"></div>
                </div>
            </div>

            <template v-else>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th
                                    class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide w-10">
                                    #</th>
                                <th
                                    class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">
                                    PNR</th>
                                <th
                                    class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">
                                    Passenger</th>
                                <th
                                    class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">
                                    Route</th>
                                <th
                                    class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">
                                    Travel</th>
                                <th
                                    class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">
                                    Seats</th>
                                <th
                                    class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">
                                    Fare</th>
                                <th
                                    class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">
                                    Status</th>
                                <th
                                    class="text-center px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">
                                    Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">

                            <tr v-for="(ticket, idx) in tickets?.data" :key="ticket.id"
                                class="hover:bg-slate-50/70 transition-colors"
                                :class="{ 'opacity-100': ticket.Status !== 'Pending Refund' }">

                                <td class="px-4 py-3 text-slate-400 text-xs">
                                    {{ (tickets.current_page - 1) * tickets.per_page + idx + 1 }}
                                </td>

                                <td class="px-4 py-3 font-semibold text-slate-800 whitespace-nowrap">
                                    {{ ticket.PNR_No }}
                                </td>

                                <td class="px-4 py-3">
                                    <p class="font-medium text-slate-800 leading-tight">{{ ticket.Passenger_Name }}</p>
                                    <p class="text-xs text-slate-400 mt-0.5">{{ ticket.Contact_No }}</p>
                                </td>

                                <!-- Uses flat string fields from mapTicket() -->
                                <td class="px-4 py-3 whitespace-nowrap text-slate-700">
                                    {{ ticket.from_city_name }}
                                    <span class="text-slate-400 mx-1">→</span>
                                    {{ ticket.to_city_name }}
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap">
                                    <p class="text-slate-800">{{ formatDate(ticket.Travel_Date) }}</p>
                                    <p class="text-xs text-slate-400">{{ (ticket.Travel_Time || '').slice(0, 5) }}</p>
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap text-slate-700">
                                    {{ formatSeats(ticket.Seat_No) }}
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap font-semibold text-slate-800">
                                    PKR {{ ticket.Fare }}
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span
                                        :class="['px-2 py-1 text-xs font-medium rounded-full', statusClass(ticket.Status)]">
                                        {{ ticket.Status }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <button v-if="ticket.Status === 'Pending Refund'" @click="openModal(ticket)"
                                        class="px-3 py-1.5 text-xs font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors shadow-sm">
                                        <i class="bi bi-arrow-return-left mr-1"></i>
                                        Refund
                                    </button>
                                    <div v-else class="flex flex-col items-center gap-0.5">
                                        <span class="text-xs text-slate-400 font-medium">Processed</span>
                                        <span v-if="ticket.Refund_Amount" class="text-xs text-green-600 font-semibold">
                                            PKR {{ ticket.Refund_Amount }}
                                        </span>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty state -->
                            <tr v-if="!tickets?.data?.length">
                                <td colspan="9" class="py-16 text-center">
                                    <div class="flex flex-col items-center">
                                        <div
                                            class="w-14 h-14 bg-slate-100 rounded-full flex items-center justify-center mb-3">
                                            <i class="bi bi-receipt text-2xl text-slate-400"></i>
                                        </div>
                                        <p class="text-sm font-semibold text-slate-600">No refund tickets found</p>
                                        <p class="text-xs text-slate-400 mt-1">Adjust your filters or wait for new
                                            requests.</p>
                                    </div>
                                </td>
                            </tr>

                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="tickets?.data?.length"
                    class="px-4 py-3 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <p class="text-xs text-slate-400">
                        Showing {{ tickets.from }}–{{ tickets.to }} of {{ tickets.total }} tickets
                    </p>
                    <div class="flex flex-wrap gap-1.5">
                        <template v-for="(link, i) in tickets.links" :key="i">
                            <button v-if="link.url" @click="goToPage(link.url)" :class="[
                                'px-3 py-1 text-xs border rounded-lg transition-colors',
                                link.active
                                    ? 'bg-primary text-white border-primary'
                                    : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50',
                            ]" v-html="link.label" />
                            <span v-else
                                class="px-3 py-1 text-xs border rounded-lg bg-white text-slate-300 border-slate-200 cursor-not-allowed"
                                v-html="link.label" />
                        </template>
                    </div>
                </div>
            </template>
        </div>

        <!-- ── Refund Modal ────────────────────────────────────────────────── -->
        <CompanyRefundModal :show="showModal" :ticket="selectedTicket" :company-config="companyConfig"
            @close="closeModal" @success="handleRefundSuccess" @conflict="handleRefundConflict" />

    </CompanyLayout>
</template>
