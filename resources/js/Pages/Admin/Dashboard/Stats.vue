<script setup>
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
});
</script>

<template>
    <!-- ── Revenue Stats ───────────────────────────────────────────────── -->
    <section class="mb-4">
        <div class="grid gap-4 sm:grid-cols-3">
            <!-- Last 24 Hours -->
            <div
                class="flex items-center gap-4 p-5 bg-white border border-gray-200 shadow-sm rounded-2xl"
            >
                <div
                    class="flex items-center justify-center w-12 h-12 border border-indigo-200 rounded-xl bg-indigo-50 shrink-0"
                >
                    <svg
                        class="w-6 h-6 text-indigo-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500">
                        Last 24 Hours
                    </p>
                    <p class="text-2xl font-bold text-gray-900">
                        {{ stats.last24hAmount }}
                    </p>
                </div>
            </div>

            <!-- Last 7 Days -->
            <div
                class="flex items-center gap-4 p-5 bg-white border border-gray-200 shadow-sm rounded-2xl"
            >
                <div
                    class="flex items-center justify-center w-12 h-12 border rounded-xl bg-violet-50 border-violet-200 shrink-0"
                >
                    <svg
                        class="w-6 h-6 text-violet-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                        />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500">
                        Last 7 Days
                    </p>
                    <p class="text-2xl font-bold text-gray-900">
                        {{ stats.last7dAmount }}
                    </p>
                </div>
            </div>

            <!-- Last 30 Days -->
            <div
                class="flex items-center gap-4 p-5 bg-white border border-gray-200 shadow-sm rounded-2xl"
            >
                <div
                    class="flex items-center justify-center w-12 h-12 border border-blue-200 rounded-xl bg-blue-50 shrink-0"
                >
                    <svg
                        class="w-6 h-6 text-blue-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                        />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500">
                        Last 30 Days
                    </p>
                    <p class="text-2xl font-bold text-gray-900">
                        {{ stats.last30dAmount }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- ── Booking Stats ───────────────────────────────────────────────── -->
    <section class="mb-4">
        <div class="grid gap-4 sm:grid-cols-3">
            <!-- Today's Confirmed Seats -->
            <div
                class="flex items-center gap-4 p-5 bg-white border border-gray-200 shadow-sm rounded-2xl"
            >
                <div
                    class="flex items-center justify-center w-12 h-12 border border-green-200 rounded-xl bg-green-50 shrink-0"
                >
                    <svg
                        class="w-6 h-6 text-green-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                        />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500">
                        Today's Confirmed Seats
                    </p>
                    <p class="text-2xl font-bold text-gray-900">
                        {{ stats.todayBookings.toLocaleString() }}
                    </p>
                </div>
            </div>

            <!-- Total Confirmed Seats -->
            <div
                class="flex items-center gap-4 p-5 bg-white border border-gray-200 shadow-sm rounded-2xl"
            >
                <div
                    class="flex items-center justify-center w-12 h-12 border border-blue-200 rounded-xl bg-blue-50 shrink-0"
                >
                    <svg
                        class="w-6 h-6 text-blue-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                        />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500">
                        Total Confirmed Seats
                    </p>
                    <p class="text-2xl font-bold text-gray-900">
                        {{ stats.totalBookings.toLocaleString() }}
                    </p>
                </div>
            </div>

            <!-- Pending Seats -->
            <div
                class="flex items-center gap-4 p-5 bg-white border border-gray-200 shadow-sm rounded-2xl"
            >
                <div
                    class="flex items-center justify-center w-12 h-12 border rounded-xl border-amber-200 bg-amber-50 shrink-0"
                >
                    <svg
                        class="w-6 h-6 text-amber-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500">
                        Pending Seats
                    </p>
                    <p class="text-2xl font-bold text-gray-900">
                        {{ stats.pendingBookings.toLocaleString() }}
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>
