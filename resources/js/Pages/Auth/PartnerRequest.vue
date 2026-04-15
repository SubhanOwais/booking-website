<script setup>
import { ref, computed } from "vue";
import { useForm, Link, Head } from "@inertiajs/vue3";

/* =========================
   FORM
========================= */
const form = useForm({
    company_name: "",
    contact_person: "",
    email: "",
    phone: "",
    city: "",
    company_type: "",
    company_detail: "",
});

const submitted = ref(false);

const companyTypes = [
    { value: "bus", label: "Bus Company", icon: "🚌", desc: "Intercity & long-route bus services" },
    { value: "hotel", label: "Hotel", icon: "🏨", desc: "Accommodation & hospitality" },
    { value: "car_rental", label: "Car Rental", icon: "🚗", desc: "Vehicle hire & transfers" },
    { value: "tour", label: "Tour Operator", icon: "🗺️", desc: "Travel packages & guided tours" },
    { value: "other", label: "Other", icon: "📦", desc: "Other travel-related services" },
];

const selectedTypeLabel = computed(() =>
    companyTypes.find((t) => t.value === form.company_type)?.label ?? ""
);

function selectType(value) {
    form.company_type = value;
}

function submit() {
    form.post(route("partner.request"), {
        onSuccess: () => {
            submitted.value = true;
            form.reset();
        },
    });
}
</script>

<template>

    <Head title="Become a Partner" />

    <div class="min-h-screen bg-gradient-to-br from-primary via-primary to-slate-900">
        <!-- Background decoration -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute rounded-full -top-40 -left-32 h-96 w-96 bg-secondary/20 blur-3xl"></div>
            <div class="absolute rounded-full -bottom-40 -right-32 h-96 w-96 bg-accent/20 blur-3xl"></div>
            <div class="absolute inset-0"
                style="background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.15) 1px, transparent 0); background-size: 24px 24px; position: fixed;">
            </div>
        </div>

        <div class="relative z-10 min-h-screen flex flex-col">
            <!-- Top nav -->
            <nav class="p-6 py-4 flex items-center justify-between max-w-7xl mx-auto w-full">
                <Link :href="route('home')" class="flex items-center gap-2 text-white">
                    <div class="flex items-center justify-center h-9 w-9 rounded-xl bg-white/10 ring-1 ring-white/15">
                        <svg class="w-7 h-7 text-white" viewBox="0 0 24 24" fill="none"
                            xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M6 16V7.8c0-1.7 1.3-3.1 3-3.3h6c1.7.2 3 1.6 3 3.3V16" stroke="currentColor"
                                stroke-width="1.6" stroke-linecap="round" />
                            <path d="M6 12h12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                            <path d="M8 16h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                            <path
                                d="M7 19a1.8 1.8 0 1 0 3.6 0A1.8 1.8 0 0 0 7 19ZM13.4 19a1.8 1.8 0 1 0 3.6 0 1.8 1.8 0 0 0-3.6 0Z"
                                fill="currentColor" />
                        </svg>
                    </div>
                    <!-- <div class="rounded-xl bg-white/10 ring-1 ring-white/15 p-[2px] hidden">
                        <img src="@img/logo.png" alt="Royal Bus Logo" class="object-contain rounded-full h-9 w-9" />
                    </div> -->

                    <div class="text-left">
                        <p class="text-sm font-semibold text-white">Safar</p>
                        <p class="-mt-0.5 text-xs text-white/70">
                            Safar with us
                        </p>
                    </div>
                </Link>



                <div class="flex justify-end items-center gap-3">
                    <!-- Back link -->
                    <Link :href="route('home')"
                        class="inline-flex items-center text-white/70 hover:text-white text-sm transition">
                        Back to Home
                    </Link>
                    <Link :href="route('login')" class="text-sm text-white/70 hover:text-white transition">
                        Already a partner? <span class="font-semibold text-white underline">Sign in</span>
                    </Link>
                </div>
            </nav>

            <!-- Main content -->
            <div class="flex-1 flex items-center justify-center px-4 pb-8">
                <div class="w-full max-w-2xl">

                    <!-- SUCCESS STATE -->
                    <div v-if="submitted" class="mb-6 rounded-2xl bg-white p-6 sm:p-10 shadow-xl text-center max-w-lg"
                        style="place-self: center;">
                        <!-- Icon -->
                        <div
                            class="mx-auto mb-6 inline-flex h-24 w-24 items-center justify-center rounded-full bg-green-100 ring-1 ring-green-200">
                            <svg class="h-12 w-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </div>

                        <!-- Heading -->
                        <h2 class="mb-3 text-2xl font-bold text-slate-800">
                            Request Submitted!
                        </h2>

                        <!-- Description -->
                        <p class="mx-auto mb-8 max-w-md text-sm text-slate-600">
                            Thank you for your interest in partnering with us. Our team will review your request and
                            contact you within
                            <span class="font-semibold text-slate-800">2–3 business days</span>.
                        </p>

                        <!-- Actions -->
                        <div class="flex flex-col justify-center gap-3 sm:flex-row">
                            <Link :href="route('home')"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5">
                                Back to Home
                            </Link>

                            <button @click="submitted = false"
                                class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 px-6 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                                Submit Another Request
                            </button>
                        </div>
                    </div>

                    <!-- FORM STATE -->
                    <div v-else>
                        <!-- Header -->
                        <div class="text-center mb-6">
                            <span
                                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 text-white/80 text-sm ring-1 ring-white/15 mb-3">
                                <span class="w-2 h-2 rounded-full bg-accent animate-pulse"></span>
                                Partnership Program
                            </span>
                            <h1 class="text-3xl sm:text-4xl font-bold text-white mb-1">
                                Become a Partner
                            </h1>
                            <p class="text-gray-200 max-w-md mx-auto">
                                Join our platform and reach thousands of travelers daily. Fill in your details and we'll
                                get back to you.
                            </p>
                        </div>

                        <!-- Card -->
                        <div class="bg-white rounded-2xl mb-6 shadow-2xl overflow-hidden">

                            <div class="p-4 sm:p-6">

                                <!-- Global error -->
                                <div v-if="form.errors.email && form.errors.email.includes('already')"
                                    class="mb-6 flex items-start gap-3 p-4 rounded-xl bg-amber-50 border border-amber-200">
                                    <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="text-sm text-amber-700">{{ form.errors.email }}</p>
                                </div>

                                <form @submit.prevent="submit" class="space-y-4">

                                    <!-- COMPANY TYPE SELECTOR -->
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                                            Company Type <span
                                                class="text-xs text-red-600 font-medium">(Required)</span>
                                        </label>
                                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 sm:gap-3">
                                            <button v-for="type in companyTypes" :key="type.value" type="button"
                                                @click="selectType(type.value)" :class="[
                                                    'relative flex items-center gap-3 p-3 sm:p-4 rounded-xl border-2 transition text-left',
                                                    form.company_type === type.value
                                                        ? 'border-primary bg-primary/5 shadow-sm'
                                                        : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'
                                                ]">
                                                <!-- Icon -->
                                                <span class="text-2xl shrink-0">
                                                    {{ type.icon }}
                                                </span>

                                                <!-- Text -->
                                                <div class="flex flex-col">
                                                    <span class="text-sm font-semibold text-slate-700">
                                                        {{ type.label }}
                                                    </span>
                                                    <span class="text-xs text-slate-400 leading-tight">
                                                        {{ type.desc }}
                                                    </span>
                                                </div>

                                                <!-- Check mark -->
                                                <div v-if="form.company_type === type.value"
                                                    class="absolute top-2 right-2 w-5 h-5 rounded-full bg-primary flex items-center justify-center">
                                                    <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="3" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </div>
                                            </button>
                                        </div>
                                        <p v-if="form.errors.company_type" class="mt-1.5 text-xs text-red-600">
                                            {{ form.errors.company_type }}
                                        </p>
                                    </div>

                                    <!-- DIVIDER -->
                                    <div class="border-t border-slate-100"></div>

                                    <!-- ROW: Company Name + Contact Person -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                                Company Name <span
                                                    class="text-xs text-red-600 font-medium">(Required)</span>
                                            </label>
                                            <input v-model="form.company_name" type="text"
                                                placeholder="e.g. Royal Travels" :class="[
                                                    'w-full px-4 py-3 rounded-xl border outline-none transition text-sm',
                                                    form.errors.company_name
                                                        ? 'border-red-400 bg-red-50 focus:ring-2 focus:ring-red-200'
                                                        : 'border-slate-200 focus:border-primary/60 focus:ring-2 focus:ring-primary/20'
                                                ]" />
                                            <p v-if="form.errors.company_name" class="mt-1 text-xs text-red-600">
                                                {{ form.errors.company_name }}
                                            </p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                                Contact Person <span
                                                    class="text-xs text-red-600 font-medium">(Required)</span>
                                            </label>
                                            <input v-model="form.contact_person" type="text"
                                                placeholder="Your full name" :class="[
                                                    'w-full px-4 py-3 rounded-xl border outline-none transition text-sm',
                                                    form.errors.contact_person
                                                        ? 'border-red-400 bg-red-50 focus:ring-2 focus:ring-red-200'
                                                        : 'border-slate-200 focus:border-primary/60 focus:ring-2 focus:ring-primary/20'
                                                ]" />
                                            <p v-if="form.errors.contact_person" class="mt-1 text-xs text-red-600">
                                                {{ form.errors.contact_person }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- ROW: Email + Phone -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                                Email Address <span
                                                    class="text-xs text-red-600 font-medium">(Required)</span>
                                            </label>
                                            <input v-model="form.email" type="email" placeholder="company@example.com"
                                                :class="[
                                                    'w-full px-4 py-3 rounded-xl border outline-none transition text-sm',
                                                    form.errors.email
                                                        ? 'border-red-400 bg-red-50 focus:ring-2 focus:ring-red-200'
                                                        : 'border-slate-200 focus:border-primary/60 focus:ring-2 focus:ring-primary/20'
                                                ]" />
                                            <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">
                                                {{ form.errors.email }}
                                            </p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                                Phone Number <span
                                                    class="text-xs text-red-600 font-medium">(Required)</span>
                                            </label>
                                            <input v-model="form.phone" type="tel" placeholder="03XX-XXXXXXX" :class="[
                                                'w-full px-4 py-3 rounded-xl border outline-none transition text-sm',
                                                form.errors.phone
                                                    ? 'border-red-400 bg-red-50 focus:ring-2 focus:ring-red-200'
                                                    : 'border-slate-200 focus:border-primary/60 focus:ring-2 focus:ring-primary/20'
                                            ]" />
                                            <p v-if="form.errors.phone" class="mt-1 text-xs text-red-600">
                                                {{ form.errors.phone }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- City -->
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                            City <span class="text-xs text-red-600 font-medium">(Required)</span>
                                        </label>
                                        <input v-model="form.city" type="text"
                                            placeholder="e.g. Lahore, Karachi, Islamabad" :class="[
                                                'w-full px-4 py-3 rounded-xl border outline-none transition text-sm',
                                                form.errors.city
                                                    ? 'border-red-400 bg-red-50 focus:ring-2 focus:ring-red-200'
                                                    : 'border-slate-200 focus:border-primary/60 focus:ring-2 focus:ring-primary/20'
                                            ]" />
                                        <p v-if="form.errors.city" class="mt-1 text-xs text-red-600">
                                            {{ form.errors.city }}
                                        </p>
                                    </div>

                                    <!-- Company Detail -->
                                    <div>
                                        <label class="text-sm flex justify-between font-semibold text-slate-700 mb-1.5">
                                            <div>
                                                Company Details
                                                <span class="text-slate-400 font-normal">(optional)</span>
                                            </div>

                                            <p class="text-xs text-slate-400 ml-auto">
                                                {{ form.company_detail.length }} / 3000
                                            </p>
                                        </label>
                                        <textarea v-model="form.company_detail" rows="4"
                                            placeholder="Tell us about your company — services offered, number of vehicles/rooms, routes covered, API integration details, or anything else relevant..."
                                            :class="[
                                                'w-full px-4 py-3 rounded-xl border outline-none transition text-sm resize-none',
                                                form.errors.company_detail
                                                    ? 'border-red-400 bg-red-50 focus:ring-2 focus:ring-red-200'
                                                    : 'border-slate-200 focus:border-primary/60 focus:ring-2 focus:ring-primary/20'
                                            ]"></textarea>
                                        <div class="mt-1">
                                            <p v-if="form.errors.company_detail" class="text-xs text-red-600">
                                                {{ form.errors.company_detail }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- SUBMIT -->
                                    <button type="submit" :disabled="form.processing || !form.company_type"
                                        class="w-full flex items-center justify-center gap-2 py-3.5 px-6 rounded-xl bg-primary text-white font-semibold text-sm shadow-lg transition hover:-translate-y-0.5 hover:shadow-xl disabled:opacity-60 disabled:cursor-not-allowed disabled:transform-none">
                                        <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none"
                                            viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4" />
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                        </svg>
                                        <span>{{ form.processing ? "Submitting..." : "Submit Partnership Request"
                                        }}</span>
                                        <svg v-if="!form.processing" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                        </svg>
                                    </button>

                                    <p class="text-center text-xs text-slate-400">
                                        By submitting, you agree to our
                                        <a href="#" class="text-primary hover:underline">Terms of Service</a>
                                        and
                                        <a href="#" class="text-primary hover:underline">Privacy Policy</a>
                                    </p>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
