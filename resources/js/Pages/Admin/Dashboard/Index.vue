<script setup>
import { Head } from "@inertiajs/vue3";
import Button from "@prime/volt/Button.vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";

import Stats from "./Stats.vue";
import LatestBookingsTable from "./LatestBookingsTable.vue";
import ActiveDiscounts from "./ActiveDiscounts.vue";

// ── Props ─────────────────────────────────────────────────────────────────────
defineProps({
    stats: {
        type: Object,
        default: () => ({
            last24hAmount: "0",
            last7dAmount: "0",
            last30dAmount: "0",
            totalBookings: 0,
            todayBookings: 0,
            pendingBookings: 0,
        }),
    },
    latestTickets: {
        type: Array,
        default: () => [],
    },
    activeDiscounts: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Admin Dashboard" />

    <AdminLayout title="Dashboard">
        <!-- ── Header ─────────────────────────────────────────────────────── -->
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">
                    Dashboard
                </h1>
                <p class="mt-1 text-sm text-gray-500">
                    Welcome back, Super Admin 👋
                </p>
            </div>
            <span
                class="inline-flex items-center gap-2 text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-full px-3 py-1.5"
            >
                <span
                    class="inline-block w-2 h-2 bg-indigo-500 rounded-full animate-pulse"
                ></span>
                Live Data
            </span>
        </div>

        <!-- ── Stats (Revenue + Booking) ──────────────────────────────────── -->
        <Stats :stats="stats" />

        <!-- ── Two-Column: Tickets + Discounts ────────────────────────────── -->
        <div class="grid gap-4 lg:grid-cols-6">
            <LatestBookingsTable :latest-tickets="latestTickets" />
            <ActiveDiscounts :active-discounts="activeDiscounts" />
        </div>
    </AdminLayout>
</template>
