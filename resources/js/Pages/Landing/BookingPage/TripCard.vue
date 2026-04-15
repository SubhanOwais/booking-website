<template>
    <article role="button" tabindex="0"
        class="group rounded-2xl border border-slate-900/10 bg-white p-5 shadow-soft transition hover:-translate-y-0.5 hover:shadow-card focus:outline-none focus:ring-2 focus:ring-secondary/30"
        :aria-expanded="open" @click="emit('toggle')" @keydown.enter.prevent="emit('toggle')"
        @keydown.space.prevent="emit('toggle')">
        <div class="grid gap-5 capitalize md:grid-cols-12 md:items-start">
            <!-- Left: company + type + departures badge -->
            <div class="flex flex-row gap-3 my-auto md:col-span-4">
                <div class="flex items-start gap-4">
                    <div class="rounded-xl bg-white/10 ring-1 ring-white/15 p-[2px]">
                        <img :src="trip.logo" alt="Royal Bus Logo"
                            class="object-contain h-14 w-auto lg:w-auto lg:h-16" />
                    </div>

                    <div>
                        <p class="text-sm font-semibold text-slate-900">
                            {{ trip.company }}
                        </p>
                        <p class="mt-0.5 text-xs text-slate-600">
                            {{ trip.busType }}
                        </p>

                        <div
                            class="inline-flex items-center gap-1 px-3 py-1 mt-2 text-xs font-semibold text-orange-600 bg-orange-100 border border-orange-600 rounded-full">
                            <svg class="w-4 h-4 xl:block hidden" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M6 16V7.8c0-1.7 1.3-3.1 3-3.3h6c1.7.2 3 1.6 3 3.3V16" stroke="currentColor"
                                    stroke-width="1.6" stroke-linecap="round" />
                                <path d="M6 12h12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                                <path
                                    d="M7 19a1.8 1.8 0 1 0 3.6 0A1.8 1.8 0 0 0 7 19ZM13.4 19a1.8 1.8 0 1 0 3.6 0 1.8 1.8 0 0 0-3.6 0Z"
                                    fill="currentColor" />
                            </svg>
                            <span>
                                {{ trip.departureTimes?.length || 1 }}
                                departure{{
                                    (trip.departureTimes?.length || 1) > 1
                                        ? "s"
                                        : ""
                                }}
                            </span>
                        </div>
                    </div>
                </div>
                <img v-if="trip.discount_percentage" src="@img/offer.png" class="w-auto h-16">
            </div>

            <!-- Middle: route + amenities -->
            <div class="my-auto md:col-span-4">
                <div class="flex flex-wrap items-center justify-center gap-2 text-sm font-semibold">
                    <span class="inline-flex items-center gap-1 text-slate-700">
                        <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M10 2a6 6 0 00-6 6c0 4.2 6 10 6 10s6-5.8 6-10a6 6 0 00-6-6zm0 8a2 2 0 110-4 2 2 0 010 4z"
                                clip-rule="evenodd" />
                        </svg>
                        {{ trip.from }}
                    </span>

                    <span v-if="trip.duration"
                        class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full bg-slate-900/5 text-slate-700">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
                            aria-hidden="true">
                            <path
                                d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z"
                                stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                            <path d="M12 6V12L16 14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                        </svg>
                        {{ trip.duration }}
                    </span>

                    <span class="inline-flex items-center gap-1 text-slate-700">
                        <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M10 2a6 6 0 00-6 6c0 4.2 6 10 6 10s6-5.8 6-10a6 6 0 00-6-6zm0 8a2 2 0 110-4 2 2 0 010 4z"
                                clip-rule="evenodd" />
                        </svg>
                        {{ trip.to }}
                    </span>
                </div>

                <!-- Show departure times -->
                <div v-if="departureTimesList.length > 0" class="flex flex-wrap justify-center gap-2 mt-4">
                    <button v-for="time in departureTimesList" :key="time" type="button"
                        @click.stop="selectedTime = time"
                        class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold rounded-full" :class="selectedTime === time
                            ? 'bg-primary text-white'
                            : 'bg-white text-slate-700 ring-1 ring-slate-900/10'
                            ">
                        {{ time }}
                        <span v-if="getSeatsForTime(time) >= 0" class="text-xs opacity-75">
                            ({{ getSeatsForTime(time) }})
                        </span>
                    </button>
                </div>
            </div>

            <!-- Right: price + CTA -->
            <div class="md:col-span-4">
                <div
                    class="flex flex-row items-center justify-between gap-3 mt-5 md:mt-0 md:flex-col md:items-end md:justify-center">
                    <div class="text-right">
                        <p class="text-xs font-semibold text-slate-900">
                            Starting from
                            <!-- Optional: Show discount percentage badge -->
                            <span v-if="trip.discount_percentage"
                                class="px-2 py-0.5 mt-1 text-xs font-medium text-white bg-primary rounded">
                                {{ trip.discount_percentage }}% off
                            </span>
                        </p>
                        <p class="flex gap-1 mt-1 text-xl font-bold text-slate-900">
                            <small class="mt-auto text-xs text-slate-600">per seat</small>
                            <!-- Show discounted price if available, otherwise business fare -->
                            {{
                                formatCurrency(
                                    trip.discounted_price || trip.price
                                )
                            }}
                            <!-- Show extra fare if exists -->
                            <span v-if="trip.extra_fare">
                                + {{ trip.extra_fare }}
                            </span>
                            <!-- Show original price crossed out only if discounted -->
                        <div v-if="trip.discounted_price && trip.discounted_price < trip.total_fare"
                            class="text-xs line-through text-slate-400">
                            {{ formatCurrency(trip.total_fare) }}
                        </div>
                        </p>
                    </div>

                    <button type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white shadow-soft transition hover:-translate-y-0.5 hover:shadow-card"
                        @click.stop="emit('toggle')">
                        {{ open ? "Hide" : "View" }} Timings
                        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-90': open }" viewBox="0 0 20 20"
                            fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Accordion: trip details + seat CTA -->
        <div class="overflow-hidden transition-all duration-300"
            :class="open ? 'mt-4 max-h-auto opacity-100' : 'max-h-0 opacity-0'">
            <div class="p-4 rounded-2xl bg-slate-100 ring-1 ring-slate-900/5">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-semibold text-slate-900">
                        Trip Details
                    </h4>
                </div>

                <!-- Seat Layout Preview -->
                <div v-if="
                    trip.departureDetails &&
                    trip.departureDetails.length > 0
                " class="">
                    <template v-if="departureTimesList.length > 0">
                        <div v-for="time in departureTimesList" :key="time"
                            class="flex items-center justify-between gap-4 px-3 py-3 mt-2 bg-white rounded-lg">
                            <div class="flex items-center gap-6">
                                <div>
                                    <p class="text-xs font-semibold text-slate-500">
                                        Departure
                                    </p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">
                                        {{ time }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-slate-500">
                                        Available Seats
                                    </p>
                                    <p class="flex items-center gap-1 mt-1">
                                        <span class="text-sm font-semibold text-slate-900">
                                            {{ getSeatsForTime(time) }}
                                        </span>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-slate-500">
                                        Fare
                                    </p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900" v-if="trip.discounted_price">
                                        <span class="px-1 text-gray-400 line-through">
                                            {{ getPriceForTime(time) }}
                                        </span>
                                        <span>
                                            {{
                                                formatCurrency(
                                                    trip.discounted_price ||
                                                    trip.business_fare
                                                )
                                            }}
                                        </span>
                                    </p>
                                    <p v-else class="mt-1 text-sm font-semibold text-slate-900">
                                        {{ getPriceForTime(time) }}
                                    </p>
                                </div>
                                <div v-if="
                                    trip.seat_4_fare !== trip.seat_20_fare
                                ">
                                    <p class="text-xs font-semibold text-slate-500">
                                        Business fare
                                    </p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900" v-if="trip.discounted_price">
                                        <span class="px-1 text-gray-400 line-through">
                                            {{
                                                formatCurrency(trip.total_fare)
                                            }}
                                        </span>
                                        <span>
                                            {{
                                                formatCurrency(trip.seat_4_fare)
                                            }}
                                        </span>
                                    </p>
                                    <p v-else class="mt-1 text-sm font-semibold text-slate-900">
                                        {{ formatCurrency(trip.total_fare) }}
                                    </p>
                                </div>
                                <div v-if="trip.discount_percentage">
                                    <p class="text-xs font-semibold text-slate-500">
                                        Dicount
                                    </p>
                                    <!-- Optional discount badge -->
                                    <span v-if="trip.discount_percentage" class="text-xs font-bold text-green-600">
                                        {{ trip.discount_percentage }}% OFF
                                    </span>
                                </div>
                            </div>

                            <button type="button"
                                class="inline-flex items-center justify-center rounded-xl bg-secondary px-4 py-2 text-sm font-semibold text-white shadow-soft transition hover:-translate-y-0.5 hover:shadow-card"
                                :disabled="getSeatsForTime(time) === 0" :class="{
                                    'opacity-50 cursor-not-allowed':
                                        getSeatsForTime(time) === 0,
                                }" @click.stop="
                                    emit('select-seat', {
                                        trip,
                                        time: time,
                                        scheduleId: getScheduleIdForTime(time),
                                        price: getPriceValueForTime(time),
                                        seatsAvailable: getSeatsForTime(time),
                                    })
                                    ">
                                <template v-if="getSeatsForTime(time) > 0">
                                    Select Seat
                                </template>
                                <template v-else> Sold Out </template>
                            </button>
                        </div>
                    </template>

                    <template v-else>
                        <div class="flex items-center justify-between gap-4 px-3 py-3 bg-white rounded-lg">
                            <div class="flex items-center gap-6">
                                <div>
                                    <p class="text-xs font-semibold text-slate-500">
                                        Departure
                                    </p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">
                                        {{ trip.departureTime || "N/A" }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-slate-500">
                                        Available Seats
                                    </p>
                                    <p class="flex items-center gap-1 mt-1">
                                        <span class="text-sm font-semibold text-slate-900">
                                            {{ trip.seatsLeft || 0 }}
                                        </span>
                                        <span class="text-xs text-slate-500">
                                            / {{ trip.totalSeats || 0 }}
                                        </span>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-slate-500">
                                        Fare
                                    </p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">
                                        {{ priceShort }}
                                    </p>
                                </div>
                            </div>

                            <!-- <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-xl bg-secondary px-4 py-2 text-sm font-semibold text-white shadow-soft transition hover:-translate-y-0.5 hover:shadow-card"
                                :disabled="trip.seatsLeft === 0"
                                :class="{
                                    'opacity-50 cursor-not-allowed':
                                        trip.seatsLeft === 0,
                                }"
                                @click.stop="
                                    emit('select-seat', {
                                        trip,
                                        time: trip.departureTime,
                                        scheduleId: trip.scheduleId,
                                        price: trip.price,
                                        seatsAvailable: trip.seatsLeft,
                                    })
                                "
                            >
                                <template v-if="trip.seatsLeft > 0">
                                    Select Seat
                                </template>
<template v-else> Sold Out </template>
</button> -->
                            <button type="button"
                                class="inline-flex items-center justify-center rounded-xl bg-secondary px-4 py-2 text-sm font-semibold text-white shadow-soft transition hover:-translate-y-0.5 hover:shadow-card"
                                :disabled="getSeatsForTime(time) === 0" :class="{
                                    'opacity-50 cursor-not-allowed':
                                        getSeatsForTime(time) === 0,
                                }" @click.stop="
                                    emit('select-seat', {
                                        company: trip.company,
                                        companylogo: trip.logo,
                                        from: trip.from,
                                        to: trip.to,
                                        date: travelDate,
                                        time: time,
                                        serviceTypeId: trip.serviceTypeId,
                                        scheduleId: getScheduleIdForTime(time),
                                        price: getPriceValueForTime(time),
                                        seatsAvailable: getSeatsForTime(time),
                                        busType: trip.busType,
                                        busService: trip.busService,
                                    })
                                    ">
                                <template v-if="getSeatsForTime(time) > 0">
                                    Select Seat
                                </template>
                                <template v-else> Sold Out </template>
                            </button>
                        </div>
                    </template>
                </div>

                <!-- Close button -->
                <!-- <div class="flex justify-end mt-4">
                    <button type="button" class="text-xs font-medium text-slate-600 hover:text-slate-900"
                        @click.stop="emit('toggle')">
                        Close Details
                    </button>
                </div> -->
            </div>
        </div>
    </article>
</template>

<script setup>
import { computed, ref } from "vue";

const emit = defineEmits(["toggle", "select-seat"]);

const props = defineProps({
    trip: {
        type: Object,
        required: true,
    },
    open: {
        type: Boolean,
        default: false,
    },
    travelDate: {
        type: String,
        default: "",
    },
});

// console.log("Testing:", props.trip);

const priceText = computed(() => {
    const price = props.trip.price || 0;
    return new Intl.NumberFormat("en-PK", {
        style: "currency",
        currency: "PKR",
        maximumFractionDigits: 0,
    }).format(price);
});

const priceShort = computed(() => {
    const price = props.trip.price || 0;
    return new Intl.NumberFormat("en-PK", {
        style: "currency",
        currency: "PKR",
        maximumFractionDigits: 0,
        minimumFractionDigits: 0,
    }).format(price);
});

const departureTimesList = computed(() => {
    if (Array.isArray(props.trip.departureTimes)) {
        return props.trip.departureTimes;
    } else if (props.trip.departureTime) {
        return [props.trip.departureTime];
    }
    return [];
});

const selectedTime = ref(departureTimesList.value[0] || "");

const formatCurrency = (amount) => {
    const numAmount = Number(amount) || 0;
    return new Intl.NumberFormat("en-PK", {
        style: "currency",
        currency: "PKR",
        maximumFractionDigits: 0,
        minimumFractionDigits: 0,
    }).format(numAmount);
};

const getSeatsForTime = (time) => {
    const detail = props.trip.departureDetails?.find((d) => d.time === time);
    return detail
        ? detail.seatsAvailable || detail.seatsLeft || 0
        : props.trip.seatsLeft || 0;
};

const getPriceForTime = (time) => {
    const detail = props.trip.departureDetails?.find((d) => d.time === time);
    const price = detail
        ? detail.price || detail.fare || props.trip.price
        : props.trip.price;
    return formatCurrency(price);
};

const getPriceValueForTime = (time) => {
    const detail = props.trip.departureDetails?.find((d) => d.time === time);
    return detail
        ? detail.price || detail.fare || props.trip.price
        : props.trip.price;
};

const getScheduleIdForTime = (time) => {
    const detail = props.trip.departureDetails?.find((d) => d.time === time);
    return detail?.scheduleId || props.trip.scheduleId;
};
</script>
