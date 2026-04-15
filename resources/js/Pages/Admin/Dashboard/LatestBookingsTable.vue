<script setup>
import DataTable from "@prime/volt/DataTable.vue";
import Column from "primevue/column";

defineProps({
    latestTickets: {
        type: Array,
        default: () => [],
    },
});

// ── Gender badge ──────────────────────────────────────────────────────────────
function genderBadge(g) {
    if (!g) return { label: "—", cls: "bg-gray-100 text-gray-500" };
    const l = g.toLowerCase();
    if (l === "male" || l === "M" || l === "m")
        return { label: "Male", cls: "bg-blue-100 text-blue-700" };
    if (l === "female" || l === "F" || l === "f")
        return { label: "Female", cls: "bg-pink-100 text-pink-700" };
    return { label: g, cls: "bg-gray-100 text-gray-500" };
}
</script>

<template>
    <section
        class="overflow-hidden bg-white border border-gray-200 shadow-sm lg:col-span-4 rounded-2xl"
    >
        <DataTable
            :value="latestTickets"
            pt:table="min-w-full w-full"
            :rows="10"
        >
            <!-- Header -->
            <template #header>
                <div class="flex items-center justify-between px-1 py-1">
                    <h2 class="text-sm font-semibold text-gray-800">
                        Latest Bookings
                    </h2>
                    <span class="text-xs font-medium text-gray-700">
                        Recent 10
                    </span>
                </div>
            </template>

            <!-- PNR -->
            <Column field="pnr" header="PNR">
                <template #body="{ data }">
                    <span
                        class="px-1.5 py-1 font-mono text-xs font-bold text-indigo-600 rounded-md bg-indigo-50"
                    >
                        {{ data.pnr ?? "—" }}
                    </span>
                </template>
            </Column>

            <!-- Passenger -->
            <Column field="passenger" header="Passenger">
                <template #body="{ data }">
                    <div class="flex items-center gap-2">
                        <span
                            class="font-medium text-gray-800 truncate max-w-[110px]"
                        >
                            {{ data.passenger ?? "—" }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ data.contact }}
                    </p>
                </template>
            </Column>

            <!-- Route -->
            <Column field="from" header="Route">
                <template #body="{ data }">
                    <div class="flex items-center gap-1 text-xs text-gray-700">
                        <span class="font-semibold truncate max-w-[60px]">{{
                            data.from
                        }}</span>
                        <svg
                            class="w-3.5 h-3.5 text-gray-300 shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                        <span class="font-semibold truncate max-w-[60px]">{{
                            data.to
                        }}</span>
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ data.bus_service }}
                    </p>
                </template>
            </Column>

            <!-- Travel Date -->
            <Column field="travel_date" header="Travel Date">
                <template #body="{ data }">
                    <span class="text-xs text-gray-600 whitespace-nowrap">
                        {{ data.travel_date }}
                    </span>
                    <p class="text-xs text-gray-400">{{ data.travel_time }}</p>
                </template>
            </Column>

            <!-- Seat -->
            <Column field="seat_no" header="Seat">
                <template #body="{ data }">
                    <span
                        class="inline-block bg-gray-100 text-gray-700 text-xs font-bold rounded px-1.5 py-1"
                    >
                        {{ data.seat_no ?? "—" }}
                    </span>
                    /
                    <span
                        class="text-xs font-bold px-1.5 py-1 rounded"
                        :class="genderBadge(data.gender).cls"
                    >
                        {{ genderBadge(data.gender).label }}
                    </span>
                </template>
            </Column>

            <!-- Fare -->
            <Column field="fare" header="Fare">
                <template #body="{ data }">
                    <span
                        class="font-semibold text-right text-gray-800 whitespace-nowrap block"
                    >
                        ₨ {{ data.fare }}
                    </span>
                </template>
            </Column>

            <!-- Empty State -->
            <template #empty>
                <div class="px-4 py-10 text-sm text-center text-gray-400">
                    No bookings found.
                </div>
            </template>
        </DataTable>
    </section>
</template>
