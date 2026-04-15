<template>
    <WebLayout class="bg-slate-50 text-ink">

        <!-- ── HERO HEADER ── -->
        <header class="relative overflow-hidden">
            <div class="absolute inset-0 z-0">
                <div class="absolute inset-0 bg-gradient-to-br from-primary via-primary to-slate-900"></div>
                <div class="absolute rounded-full -top-40 -left-32 h-96 w-96 bg-secondary/25 blur-3xl"></div>
                <div class="absolute rounded-full -bottom-40 -right-32 h-96 w-96 bg-accent/25 blur-3xl"></div>
                <div class="absolute inset-0 opacity-20" style="
                        background-image: radial-gradient(
                            circle at 1px 1px,
                            rgba(255, 255, 255, 0.12) 1px,
                            transparent 0
                        );
                        background-size: 22px 22px;
                    "></div>
            </div>

            <div class="relative z-20 max-w-6xl px-4 py-10 mx-auto sm:py-12">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm text-white/80">Charter & Private Hire</p>
                        <h1 class="mt-1 text-2xl font-bold tracking-tight text-white sm:text-3xl">
                            Special Bus Booking
                        </h1>
                        <p class="max-w-2xl mt-2 text-white/75">
                            Fill in the form below and our team will contact you within 2 hours.
                        </p>
                    </div>

                    <Link :href="route('home')"
                        class="inline-flex items-center justify-center rounded-xl border border-white/25 bg-white/10 px-4 py-2 text-sm font-semibold text-white backdrop-blur transition hover:-translate-y-0.5 hover:bg-white/15">
                        Back to Home
                    </Link>
                </div>
            </div>
        </header>

        <!-- ── MAIN CONTENT ── -->
        <div class="mx-auto max-w-6xl px-4 py-10">

            <!-- Success Banner -->
            <Transition name="fade">
                <div v-if="successData" class="mb-8 rounded-2xl border border-green-200 bg-green-50 p-6">
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-green-100">
                            <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" stroke-width="2.5"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-semibold text-green-800">Booking Request Submitted!</h3>
                            <p class="mt-0.5 text-sm text-green-700">{{ successData.message }}</p>
                            <!-- Multiple references (new) -->
                            <div class="mt-3 flex flex-wrap gap-2">
                                <div v-for="ref in successData.references" :key="ref"
                                    class="inline-flex items-center gap-2 rounded-lg bg-green-100 px-4 py-2">
                                    <span class="text-sm text-green-700">Ref:</span>
                                    <span class="font-mono font-bold text-green-900">{{ ref }}</span>
                                </div>
                            </div>
                        </div>
                        <button @click="successData = null" class="text-green-400 hover:text-green-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </Transition>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">

                <!-- ── LEFT: FORM ── -->
                <div class="lg:col-span-2">
                    <form @submit.prevent="submitForm"
                        class="rounded-2xl border border-gray-200 bg-white p-3 shadow-sm sm:p-6">

                        <h2 class="mb-2 text-lg font-semibold text-gray-900">Request Details</h2>

                        <!-- Passenger Info -->
                        <div class="mb-4 bg-gray-100 p-4 rounded-lg border border-e-gray-200">
                            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-600">
                                Passenger Information
                            </h3>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <div>
                                    <label class="sb-label">Full Name <span class="text-red-500">*</span></label>
                                    <input v-model="form.passenger_name" type="text" placeholder="Your full name"
                                        class="sb-input" :class="{ 'sb-input-error': errors.passenger_name }" />
                                    <p v-if="errors.passenger_name" class="sb-error">{{ errors.passenger_name[0] }}</p>
                                </div>
                                <div>
                                    <label class="sb-label">Phone Number <span class="text-red-500">*</span></label>
                                    <input v-model="form.passenger_phone" type="tel" placeholder="+92 300 0000000"
                                        class="sb-input" :class="{ 'sb-input-error': errors.passenger_phone }" />
                                    <p v-if="errors.passenger_phone" class="sb-error">{{ errors.passenger_phone[0] }}
                                    </p>
                                </div>
                                <div>
                                    <label class="sb-label">Email Address</label>
                                    <input v-model="form.passenger_email" type="email" placeholder="optional@email.com"
                                        class="sb-input" />
                                </div>
                            </div>
                        </div>

                        <!-- Route -->
                        <div class="mb-4 bg-gray-100 p-4 rounded-lg border border-e-gray-200">
                            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-600">
                                Route & Schedule
                            </h3>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <!-- From City -->
                                <div>
                                    <label class="sb-label">From City <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <!-- <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="3" />
                                            <path stroke-linecap="round" d="M12 2v3m0 14v3m10-10h-3M5 12H2" />
                                        </svg> -->
                                        <select v-model="form.from_city_id" class="sb-select pl-9"
                                            :class="{ 'sb-input-error': errors.from_city_id }">
                                            <option value="" disabled>Select origin city</option>
                                            <option v-for="city in cities" :key="city.City_Id" :value="city.City_Id">
                                                {{ city.City_Name }}
                                            </option>
                                        </select>
                                    </div>
                                    <p v-if="errors.from_city_id" class="sb-error">{{ errors.from_city_id[0] }}</p>
                                </div>

                                <!-- To City -->
                                <div>
                                    <label class="sb-label">To City <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <!-- <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg> -->
                                        <select v-model="form.to_city_id" class="sb-select pl-9"
                                            :class="{ 'sb-input-error': errors.to_city_id }">
                                            <option value="" disabled>Select destination city</option>
                                            <option v-for="city in cities" :key="city.City_Id" :value="city.City_Id"
                                                :disabled="city.City_Id === form.from_city_id">
                                                {{ city.City_Name }}
                                            </option>
                                        </select>
                                    </div>
                                    <p v-if="errors.to_city_id" class="sb-error">{{ errors.to_city_id[0] }}</p>
                                </div>

                                <!-- Travel Date -->
                                <div>
                                    <label class="sb-label">Travel Date <span class="text-red-500">*</span></label>
                                    <input v-model="form.travel_date" type="date" :min="todayStr" class="sb-input"
                                        :class="{ 'sb-input-error': errors.travel_date }" />
                                    <p v-if="errors.travel_date" class="sb-error">{{ errors.travel_date[0] }}</p>
                                </div>

                                <!-- Preferred Time -->
                                <div>
                                    <label class="sb-label">Pickup Time</label>
                                    <input v-model="form.preferred_time" type="time" class="sb-input" />
                                </div>
                            </div>
                        </div>

                        <!-- Bus Details -->
                        <div class="mb-6 bg-gray-100 p-4 rounded-lg border border-e-gray-200">
                            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-600">
                                Bus Details
                            </h3>

                            <!-- Bus Type Cards -->
                            <div class="mb-4 grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-4">
                                <button v-for="bus in busTypes" :key="bus.value" type="button"
                                    @click="form.bus_type = bus.value" :class="[
                                        'rounded-xl border p-3 text-left transition-all duration-150',
                                        form.bus_type === bus.value
                                            ? 'border-primary bg-white text-white ring-2 ring-primary/10'
                                            : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50'
                                    ]">
                                    <div :class="[
                                        'mb-2 flex h-8 w-8 items-center justify-center rounded-lg',
                                        form.bus_type === bus.value ? 'bg-primary' : 'bg-gray-100'
                                    ]">
                                        <svg class="h-4 w-4"
                                            :class="form.bus_type === bus.value ? 'text-white' : 'text-gray-500'"
                                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <rect x="1" y="3" width="22" height="13" rx="2" />
                                            <path stroke-linecap="round" d="M5 17h14M7 17v2m10-2v2" />
                                        </svg>
                                    </div>
                                    <div
                                        :class="['text-xs font-semibold', form.bus_type === bus.value ? 'text-primary' : 'text-gray-700']">
                                        {{ bus.label }}
                                    </div>
                                    <div class="mt-0.5 text-xs text-gray-400">{{ bus.capacity }}</div>
                                </button>
                            </div>
                            <p v-if="errors.bus_type" class="sb-error">{{ errors.bus_type[0] }}</p>

                            <!-- Company -->
                            <!-- Company Multi-Select -->
                            <div>
                                <div class="flex justify-between items-center">
                                    <label class="sb-label">Select Company</label>

                                    <!-- Send to All toggle -->
                                    <label class="mb-3 flex cursor-pointer items-center gap-3">
                                        <div class="relative">
                                            <input type="checkbox" v-model="form.send_to_all" class="sr-only" />
                                            <div :class="[
                                                'h-5 w-9 rounded-full transition-colors duration-200',
                                                form.send_to_all ? 'bg-primary' : 'bg-gray-300'
                                            ]"></div>
                                            <div :class="[
                                                'absolute top-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform duration-200',
                                                form.send_to_all ? 'translate-x-4' : 'translate-x-0.5'
                                            ]"></div>
                                        </div>
                                        <div>
                                            <span class="text-sm font-semibold text-gray-800">Send to All
                                                Companies</span>
                                            <span class="ml-2 text-xs text-gray-500">({{ companies.length }}
                                                available)</span>
                                        </div>
                                    </label>
                                </div>

                                <!-- Individual checkboxes (hidden when Send to All is ON) -->
                                <div v-if="!form.send_to_all">
                                    <div v-if="companies.length === 0" class="py-3 text-center text-sm text-gray-400">
                                        No companies available.
                                    </div>

                                    <!-- Scrollable checkbox list -->
                                    <div class="max-h-48 overflow-y-auto rounded-xl border border-gray-200 bg-white">
                                        <label v-for="co in companies" :key="co.id"
                                            class="flex cursor-pointer items-center gap-3 border-b border-gray-100 px-4 py-2.5 last:border-0 transition hover:bg-gray-50"
                                            :class="{ 'bg-primary/5': form.company_ids.includes(co.id) }">
                                            <input type="checkbox" :value="co.id" v-model="form.company_ids"
                                                class="h-4 w-4 rounded border-gray-300 accent-primary" />
                                            <span class="flex-1 text-sm text-gray-700 font-medium">{{ co.name }}</span>
                                            <span v-if="co.city" class="text-xs text-gray-400">{{ co.city }}</span>
                                        </label>
                                    </div>

                                    <!-- Selected count badge -->
                                    <p v-if="form.company_ids.length > 0" class="mt-2 text-xs text-primary font-medium">
                                        ✓ {{ form.company_ids.length }} {{ form.company_ids.length === 1 ? 'company' :
                                            'companies' }} selected
                                        — {{ form.company_ids.length }} booking {{ form.company_ids.length === 1 ?
                                            'record' : 'records' }} will be created
                                    </p>
                                </div>

                                <!-- Send-to-all summary -->
                                <div v-else
                                    class="mt-2 rounded-lg bg-primary/10 px-4 py-2 text-xs text-primary font-medium">
                                    ✓ Request will be sent to all {{ companies.length }} companies —
                                    {{ companies.length }} booking records will be created
                                </div>
                            </div>
                        </div>

                        <!-- Special Notes -->
                        <div class="mb-3">
                            <label class="sb-label">Special Notes / Requirements</label>
                            <textarea v-model="form.special_notes" rows="3"
                                placeholder="Any special requirements, stops, accessibility needs, decorations for wedding, etc."
                                class="sb-input resize-none"></textarea>
                        </div>

                        <!-- Submit -->
                        <div class="flex items-center justify-between gap-4 border-t border-gray-100 pt-0">
                            <div
                                class="flex items-center gap-2 p-3 rounded-lg bg-orange-50 border border-orange-200 text-orange-700 text-sm">

                                <svg class="h-4 w-4 text-orange-500 flex-shrink-0" fill="none" stroke="currentColor"
                                    stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>

                                <span class="font-medium">
                                    We'll contact you within <span class="font-semibold text-orange-600">2 hours</span>
                                </span>

                            </div>
                            <button type="submit" :disabled="submitting"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-7 py-3 text-sm font-bold text-white transition hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-60 disabled:translate-y-0">
                                <svg v-if="!submitting" class="h-4 w-4" fill="none" stroke="currentColor"
                                    stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                                <svg v-else class="h-4 w-4 animate-spin" fill="none" stroke="currentColor"
                                    stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                {{ submitting ? 'Submitting...' : 'Submit Request' }}
                            </button>
                        </div>

                    </form>
                </div>

                <!-- ── RIGHT: INFO SIDEBAR ── -->
                <div class="space-y-5">

                    <!-- Why Charter -->
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 class="mb-4 font-semibold text-gray-900">Why Charter a Bus?</h3>
                        <ul class="space-y-3">
                            <li v-for="item in whyItems" :key="item.text" class="flex items-start gap-3">
                                <div
                                    class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-lg bg-primary">
                                    <svg class="h-3.5 w-3.5 text-white" fill="none" stroke="currentColor"
                                        stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <span class="text-sm text-gray-600">{{ item.text }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- How it works -->
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 class="mb-4 font-semibold text-gray-900">How It Works</h3>
                        <ol class="space-y-4">
                            <li v-for="(step, i) in steps" :key="i" class="flex items-start gap-3">
                                <span
                                    class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-lg bg-primary text-xs font-bold text-white">
                                    {{ i + 1 }}
                                </span>
                                <span class="text-sm text-gray-600">{{ step }}</span>
                            </li>
                        </ol>
                    </div>

                    <!-- Contact -->
                    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-primary to-slate-800 p-6">
                        <div class="absolute -right-8 -top-8 h-24 w-24 rounded-full bg-secondary/20 blur-2xl"></div>
                        <h3 class="mb-1 font-semibold text-white">Need Help?</h3>
                        <p class="mb-4 text-sm text-white/70">Call us directly for urgent requests.</p>
                        <a href="tel:+921234567890"
                            class="inline-flex items-center gap-2 rounded-xl bg-secondary px-4 py-2.5 text-sm font-bold text-primary transition">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            Call Now
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </WebLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, usePage, Link } from '@inertiajs/vue3'
import WebLayout from "@/Layouts/WebLayout.vue";

// ── Props from controller ─────────────────────────────────────────────────────

const props = defineProps({
    cities: { type: Array, default: () => [] },
    companies: { type: Array, default: () => [] },
    busTypes: { type: Array, default: () => [] },
})

// ── State ─────────────────────────────────────────────────────────────────────

const submitting = ref(false)
const successData = ref(null)
const errors = ref({})

const page = usePage()

const form = ref({
    passenger_name: '',
    passenger_phone: '',
    passenger_email: '',
    from_city_id: '',
    to_city_id: '',
    travel_date: '',
    preferred_time: '',
    send_to_all: false,      // ← new
    company_ids: [],         // ← replaces company_id
    bus_type: 'standard',
    special_notes: '',
})

const todayStr = computed(() => new Date().toISOString().split('T')[0])

// ── Sidebar content ───────────────────────────────────────────────────────────

const whyItems = [
    { text: 'Flexible pickup & drop-off points' },
    { text: 'Ideal for weddings, corporate events & tours' },
    { text: 'Dedicated bus just for your group' },
    { text: 'Professional, experienced drivers' },
    { text: 'Competitive pricing for any group size' },
]

const steps = [
    'Fill out the booking request form.',
    'Our team reviews and contacts you within 2 hours.',
    'Confirm the details and make payment.',
    'Your bus arrives on the scheduled date.',
]

// ── Submit ────────────────────────────────────────────────────────────────────

function submitForm() {
    submitting.value = true
    errors.value = {}
    successData.value = null

    router.post('/special-booking', form.value, {
        onSuccess: () => {
            successData.value = page.props.flash?.success || {
                message: 'Request submitted successfully!',
                references: [],
            }
            resetForm()
        },
        onError: (errs) => {
            errors.value = errs
        },
        onFinish: () => {
            submitting.value = false
        },
    })
}

function resetForm() {
    form.value = {
        passenger_name: '',
        passenger_phone: '',
        passenger_email: '',
        from_city_id: '',
        to_city_id: '',
        travel_date: '',
        preferred_time: '',
        send_to_all: false,
        company_ids: [],
        bus_type: 'standard',
        special_notes: '',
    }
}
</script>

<style scoped>
.sb-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: #6b7280;
    margin-bottom: 5px;
}

.sb-input,
.sb-select {
    width: 100%;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 9px 13px;
    font-size: 0.875rem;
    color: #111827;
    background: #fff;
    outline: none;
    transition: border-color 0.15s, box-shadow 0.15s;
}

.sb-input:focus,
.sb-select:focus {
    border-color: #fbbf24;
    box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.15);
}

.sb-input::placeholder {
    color: #9ca3af;
}

.sb-input-error {
    border-color: #f87171 !important;
}

.sb-error {
    color: #ef4444;
    font-size: 0.75rem;
    margin-top: 3px;
}

.sb-select {
    cursor: pointer;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
