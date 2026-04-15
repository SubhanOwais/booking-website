<script setup>
import CardCarousel from "@/Components/LandingPage/CardCarousel.vue";

const routes = [
    {
        from: "Karachi",
        to: "Lahore",
        depart: "07:30 AM",
        arrive: "03:15 PM",
        duration: "7h 45m",
    },
    {
        from: "Islamabad",
        to: "Peshawar",
        depart: "09:00 AM",
        arrive: "11:15 AM",
        duration: "2h 15m",
    },
    {
        from: "Lahore",
        to: "Multan",
        depart: "01:30 PM",
        arrive: "05:10 PM",
        duration: "3h 40m",
    },
    {
        from: "Karachi",
        to: "Hyderabad",
        depart: "06:45 AM",
        arrive: "09:05 AM",
        duration: "2h 20m",
    },
    {
        from: "Faisalabad",
        to: "Lahore",
        depart: "04:10 PM",
        arrive: "06:20 PM",
        duration: "2h 10m",
    },
    {
        from: "Quetta",
        to: "Karachi",
        depart: "08:15 PM",
        arrive: "06:10 AM",
        duration: "9h 55m",
    },
];

function bookNow(r) {
    // Demo stub — replace with real booking flow.
    // eslint-disable-next-line no-alert
    alert(`Booking: ${r.from} → ${r.to} (${r.depart})`);
}
</script>

<template>
    <section id="routes" class="bg-white">
        <div class="max-w-6xl px-4 py-12 mx-auto sm:py-16">
            <CardCarousel aria-label="route" :total="routes.length">
                <template #heading>
                    <h2 class="text-2xl font-bold text-slate-900 sm:text-3xl">
                        Today’s Top Routes & Times
                    </h2>
                    <p class="max-w-2xl mt-2 text-slate-600">
                        Popular departures with estimated arrival and duration.
                    </p>
                </template>

                <article v-for="r in routes" :key="`${r.from}-${r.to}-${r.depart}`" data-card
                    class="min-w-[280px] rounded-2xl border border-slate-900/10 bg-white p-5 shadow-sm my-3 transition hover:-translate-y-0.5 hover:shadow-md sm:min-w-[320px] lg:min-w-[360px]">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">
                                {{ r.from }} → {{ r.to }}
                            </h3>
                            <p class="mt-1 text-sm text-slate-600">
                                Popular route • Reliable timing
                            </p>
                        </div>
                        <span
                            class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-secondary/10 text-secondary">
                            Today
                        </span>
                    </div>

                    <div class="grid grid-cols-3 gap-3 mt-5">
                        <div class="p-3 rounded-xl bg-slate-50 ring-1 ring-slate-900/5">
                            <p class="text-xs text-slate-500">Depart</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">
                                {{ r.depart }}
                            </p>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50 ring-1 ring-slate-900/5">
                            <p class="text-xs text-slate-500">Arrive</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">
                                {{ r.arrive }}
                            </p>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50 ring-1 ring-slate-900/5">
                            <p class="text-xs text-slate-500">Duration</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">
                                {{ r.duration }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between mt-5">
                        <span class="text-sm text-slate-600">Seats available</span>
                        <button type="button"
                            class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white shadow-soft transition hover:-translate-y-0.5 hover:shadow-card"
                            @click="bookNow(r)">
                            Book Now
                            <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd"
                                    d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </article>
            </CardCarousel>
        </div>
    </section>
</template>
