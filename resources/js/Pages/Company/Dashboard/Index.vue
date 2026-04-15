<script setup>
import CompanyLayout from "@/Layouts/CompanyLayout.vue";
import DataTable from "@prime/volt/DataTable.vue";
import ActiveDiscounts from "./ActiveDiscounts.vue";
import LatestBookingsTable from "./LatestBookingsTable.vue";
import Column from "primevue/column";

// Props from Inertia
const props = defineProps({
    user: Object,
    company: Object,
    latestTickets: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            totalBookings: 0,
            pendingBookings: 0,
            activeSchedules: 0,
            revenue: 0,
        }),
    },
    activeDiscounts: {
        type: Array,
        default: () => [],
    },
});

// ── Gender badge helper ─────────────────────────────────────────────
function genderBadge(g) {
    if (!g) return { label: "—", cls: "bg-gray-100 text-gray-500" };
    const l = g.toLowerCase();
    if (l === "male" || l === "m") return { label: "Male", cls: "bg-blue-100 text-blue-700" };
    if (l === "female" || l === "f") return { label: "Female", cls: "bg-pink-100 text-pink-700" };
    return { label: g, cls: "bg-gray-100 text-gray-500" };
}
</script>

<template>
    <CompanyLayout :user="props.user" :company="props.company">

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mt-4">
            <div class="p-5 bg-white rounded-2xl border border-slate-200 shadow-sm">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Total Ticketing</p>
                <p class="mt-2 text-3xl font-bold text-slate-800">{{ stats.totalBookings || 0 }}</p>
                <p class="mt-1 text-xs text-slate-400">Last 30 days</p>
            </div>

            <div class="p-5 bg-white rounded-2xl border border-slate-200 shadow-sm">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Canceled Ticketing</p>
                <p class="mt-2 text-3xl font-bold text-slate-800">{{ stats.pendingBookings || 0 }}</p>
                <p class="mt-1 text-xs text-slate-400">Last 30 days</p>
            </div>

            <div class="p-5 bg-white rounded-2xl border border-slate-200 shadow-sm">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Active Schedules</p>
                <p class="mt-2 text-3xl font-bold text-slate-800">{{ stats.activeSchedules || 0 }}</p>
                <p class="mt-1 text-xs text-slate-400">Running today</p>
            </div>

            <div class="p-5 bg-white rounded-2xl border border-slate-200 shadow-sm">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Revenue</p>
                <p class="mt-2 text-3xl font-bold text-slate-800">Rs. {{ stats.last30dAmount || 0 }}</p>
                <p class="mt-1 text-xs text-slate-400">Last 30 days</p>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-6 mt-6">
            <!-- Latest Ticketing Table -->
            <LatestBookingsTable :latest-tickets="latestTickets" />

            <!-- Active Discounts -->
            <ActiveDiscounts :active-discounts="activeDiscounts" />
        </div>

    </CompanyLayout>
</template>
