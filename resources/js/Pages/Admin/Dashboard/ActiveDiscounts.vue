<script setup>
import { ref, onMounted, onUnmounted } from "vue";

const props = defineProps({
    activeDiscounts: {
        type: Array,
        default: () => [],
    },
});

// ── Countdown Timers ──────────────────────────────────────────────────────────
const countdowns = ref({});

function computeCountdown(isoString) {
    if (!isoString) return null;
    const diff = Math.max(
        0,
        Math.floor((new Date(isoString) - Date.now()) / 1000)
    );
    const d = Math.floor(diff / 86400);
    const h = Math.floor((diff % 86400) / 3600);
    const m = Math.floor((diff % 3600) / 60);
    const s = diff % 60;
    return { d, h, m, s, total: diff };
}

function refreshCountdowns() {
    props.activeDiscounts.forEach((disc) => {
        if (disc.ends_at_iso) {
            countdowns.value[disc.id] = computeCountdown(disc.ends_at_iso);
        }
    });
}

let timer = null;
onMounted(() => {
    refreshCountdowns();
    timer = setInterval(refreshCountdowns, 1000);
});
onUnmounted(() => clearInterval(timer));

function pad(n) {
    return String(n).padStart(2, "0");
}

function urgencyClass(cd) {
    if (!cd) return "text-gray-400";
    if (cd.total < 3600) return "text-red-600 animate-pulse font-bold";
    if (cd.total < 86400) return "text-orange-500 font-semibold";
    return "text-emerald-600 font-semibold";
}

function urgencyBg(cd) {
    if (!cd) return "bg-gray-100";
    if (cd.total < 3600) return "bg-red-50 border-red-200";
    if (cd.total < 86400) return "bg-orange-50 border-orange-200";
    return "bg-emerald-50 border-emerald-200";
}
</script>

<template>
    <section
        class="flex flex-col overflow-hidden bg-white border border-gray-200 shadow-sm h-fit lg:col-span-2 rounded-2xl"
    >
        <div
            class="flex items-center justify-between px-5 py-4 border-b border-gray-200"
        >
            <h2 class="text-sm font-semibold text-gray-800">
                Active Discounts
            </h2>
            <span
                class="text-xs font-semibold bg-emerald-100 text-emerald-700 rounded-full px-2.5 py-0.5"
            >
                {{ activeDiscounts.length }} live
            </span>
        </div>

        <div
            class="flex-1 overflow-y-auto divide-y divide-gray-200 max-h-[460px]"
        >
            <div
                v-for="disc in activeDiscounts"
                :key="disc.id"
                class="p-4 transition-colors hover:bg-gray-50/60"
            >
                <!-- Name + % badge -->
                <div class="flex items-start justify-between gap-2 mb-2">
                    <div class="min-w-0">
                        <p
                            class="text-sm font-semibold text-gray-800 truncate"
                        >
                            {{ disc.name }}
                        </p>
                        <p class="text-xs text-gray-600 truncate mt-0.5">
                            {{ disc.main_city }} &bull;
                            {{ disc.mapped_city_count }} cities
                        </p>
                    </div>
                    <span
                        class="shrink-0 inline-flex items-center gap-1 bg-primary text-white text-xs font-extrabold rounded-full px-2.5 py-1"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="12"
                            height="12"
                            fill="currentColor"
                            class="bi bi-patch-check-fill"
                            viewBox="0 0 16 16"
                        >
                            <path
                                d="M10.067.87a2.89 2.89 0 0 0-4.134 0l-.622.638-.89-.011a2.89 2.89 0 0 0-2.924 2.924l.01.89-.636.622a2.89 2.89 0 0 0 0 4.134l.637.622-.011.89a2.89 2.89 0 0 0 2.924 2.924l.89-.01.622.636a2.89 2.89 0 0 0 4.134 0l.622-.637.89.011a2.89 2.89 0 0 0 2.924-2.924l-.01-.89.636-.622a2.89 2.89 0 0 0 0-4.134l-.637-.622.011-.89a2.89 2.89 0 0 0-2.924-2.924l-.89.01zm.287 5.984-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7 8.793l2.646-2.647a.5.5 0 0 1 .708.708"
                            />
                        </svg>
                        {{ disc.discount_percentage }}% OFF
                    </span>
                </div>

                <!-- Companies -->
                <div
                    v-if="disc.company_names && disc.company_names.length"
                    class="flex flex-wrap gap-1 mb-3"
                >
                    <span
                        v-for="c in disc.company_names"
                        :key="c"
                        class="text-xs bg-indigo-50 text-blue-700 rounded px-2 py-0.5 font-medium"
                    >
                        {{ c }}
                    </span>
                </div>

                <!-- Countdown Timer -->
                <div
                    class="flex items-center justify-between gap-3 px-2 py-1 transition-all border rounded"
                    :class="urgencyBg(countdowns[disc.id])"
                >
                    <!-- Left: Icon + Label -->
                    <div class="flex items-center gap-2">
                        <div
                            class="flex items-center justify-center w-8 h-8 rounded-lg"
                            :class="urgencyBg(countdowns[disc.id])"
                        >
                            <svg
                                class="w-4 h-4"
                                :class="urgencyClass(countdowns[disc.id])"
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

                        <div class="leading-tight">
                            <p
                                class="text-xs font-semibold tracking-wide uppercase sm:text-base"
                                :class="urgencyClass(countdowns[disc.id])"
                            >
                                Ends In
                            </p>
                            <p
                                v-if="!disc.ends_at_iso"
                                class="text-xs font-medium text-gray-400 sm:text-base"
                            >
                                No end date
                            </p>
                        </div>
                    </div>

                    <!-- Right: Live Countdown -->
                    <div
                        v-if="
                            countdowns[disc.id] &&
                            countdowns[disc.id].total > 0
                        "
                        class="flex items-center gap-1.5 font-cursive"
                        :class="urgencyClass(countdowns[disc.id])"
                    >
                        <template v-if="countdowns[disc.id].d > 0">
                            <div>
                                <span class="text-sm font-extrabold">
                                    {{ countdowns[disc.id].d }}
                                </span>
                                <span class="text-[10px] font-semibold opacity-70">
                                    d
                                </span>
                            </div>
                        </template>

                        <div>
                            <span class="text-sm font-extrabold">
                                {{ pad(countdowns[disc.id].h) }}
                            </span>
                            <span class="text-[10px] font-semibold opacity-70">
                                h
                            </span>
                        </div>

                        <div>
                            <span class="text-sm font-extrabold">
                                {{ pad(countdowns[disc.id].m) }}
                            </span>
                            <span class="text-[10px] font-semibold opacity-70">
                                m
                            </span>
                        </div>

                        <div>
                            <span class="text-sm font-extrabold">
                                {{ pad(countdowns[disc.id].s) }}
                            </span>
                            <span class="text-[10px] font-semibold opacity-70">
                                s
                            </span>
                        </div>
                    </div>

                    <!-- Expired -->
                    <span
                        v-else
                        class="text-xs font-bold tracking-wide text-red-600 uppercase"
                    >
                        Expired
                    </span>
                </div>

                <!-- Date range -->
                <p class="mt-2 text-xs text-right text-gray-400">
                    {{ disc.start_date }} → {{ disc.end_date }}
                </p>
            </div>

            <div
                v-if="!activeDiscounts.length"
                class="py-10 text-sm text-center text-gray-400"
            >
                No active discounts.
            </div>
        </div>
    </section>
</template>
