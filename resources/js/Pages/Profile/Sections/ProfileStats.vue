<!-- resources/js/Pages/Profile/Sections/ProfileStats.vue -->
<template>
    <div class="p-5 bg-white border border-gray-200 rounded-lg shadow-sm">
        <h3 class="mb-3 text-lg font-bold text-gray-800">Travel Stats</h3>
        <div class="grid grid-cols-2 gap-3">

            <!-- Total Trips (past completed) -->
            <div class="p-3 text-center bg-gray-100 border border-gray-300 rounded-lg flex justify-around items-center flex-col">
                <div class="text-2xl font-bold text-gray-800">
                    {{ stats.totalTrips }}
                </div>
                <div class="text-sm text-gray-600">Total Trips</div>
            </div>

            <!-- Upcoming Trips -->
            <div class="p-3 text-center bg-blue-50 border border-blue-200 rounded-lg flex justify-around items-center flex-col">
                <div class="text-2xl font-bold text-blue-700">
                    {{ stats.upcomingTrips }}
                </div>
                <div class="text-sm text-blue-600">Upcoming Trips</div>
            </div>

            <!-- Cancelled -->
            <div class="p-3 text-center bg-red-50 border border-red-200 rounded-lg flex justify-around items-center flex-col">
                <div class="text-2xl font-bold text-red-600">
                    {{ stats.cancelled }}
                </div>
                <div class="text-sm text-red-500">Cancelled</div>
            </div>

            <!-- Total Spent -->
            <div class="p-3 text-center bg-gray-100 border border-gray-300 rounded-lg flex justify-around items-center flex-col">
                <div class="text-xl font-bold text-gray-800">
                    Rs. {{ formatNumber(stats.totalSpent) }}
                </div>
                <div class="text-sm text-gray-600">Total Spent</div>
            </div>

            <!-- Total Saved — full width -->
            <div class="col-span-2 p-3 text-center bg-green-50 border border-green-200 rounded-lg flex justify-around items-center flex-col">
                <div class="text-2xl font-bold text-green-600">
                    Rs. {{ formatNumber(stats.totalSaved) }}
                </div>
                <div class="text-sm text-green-600">🎉 Total Saved (Discounts)</div>
            </div>

        </div>
    </div>
</template>

<script setup>
defineProps({
    stats: {
        type: Object,
        required: true,
    },
});

const formatNumber = (value) => {
    if (value === null || value === undefined || value === '') return '0.00';
    const num = parseFloat(value);
    return isNaN(num) ? '0.00' : num.toFixed(2);
};
</script>
