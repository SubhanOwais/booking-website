<script setup>
import { ref } from "vue";
import { Link, router } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";

const props = defineProps({
    companies: Object,
    counts: Object,
});

/* =========================
   VIEW MODAL
========================= */
const showModal = ref(false);
const modalData = ref(null);

function openModal(company) {
    modalData.value = company;
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    setTimeout(() => { modalData.value = null; }, 300);
}

/* =========================
   HELPERS
========================= */
const typeConfig = {
    bus: { label: "Bus Company", icon: "🚌", bg: "bg-blue-100", text: "text-blue-700" },
    hotel: { label: "Hotel", icon: "🏨", bg: "bg-purple-100", text: "text-purple-700" },
    car_rental: { label: "Car Rental", icon: "🚗", bg: "bg-orange-100", text: "text-orange-700" },
    tour: { label: "Tour Operator", icon: "🗺️", bg: "bg-green-100", text: "text-green-700" },
    other: { label: "Other", icon: "📦", bg: "bg-slate-100", text: "text-slate-700" },
};

function formatDate(date) {
    if (!date) return "—";
    return new Date(date).toLocaleDateString("en-PK", {
        year: "numeric", month: "short", day: "numeric",
    });
}

function toggleStatus(company) {
    router.patch(route("admin.companies.toggle", company.id), {}, {
        preserveScroll: true,
    });
}
</script>

<template>
    <AdminLayout title="Companies">

        <!-- Flash -->
        <div v-if="$page.props.flash?.success"
            class="mb-6 flex items-center gap-3 p-4 rounded-xl bg-green-50 border border-green-200 text-green-800 text-sm font-medium">
            {{ $page.props.flash.success }}
        </div>

        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Companies</h1>
                <p class="mt-1 text-sm text-slate-500">All registered partner companies on the platform.</p>
            </div>
            <Link :href="route('admin.partner-requests.index')"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-primary text-white text-sm font-semibold hover:-translate-y-0.5 transition shadow-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add via Partner Request
            </Link>
        </div>

        <!-- Stat Cards -->
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
                <p class="text-xs font-semibold text-slate-400 uppercase">Total</p>
                <p class="text-3xl font-bold text-slate-900 mt-1">{{ counts.total }}</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
                <p class="text-xs font-semibold text-green-500 uppercase">Active</p>
                <p class="text-3xl font-bold text-slate-900 mt-1">{{ counts.active }}</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
                <p class="text-xs font-semibold text-slate-400 uppercase">Inactive</p>
                <p class="text-3xl font-bold text-slate-900 mt-1">{{ counts.inactive }}</p>
            </div>
        </div>

        <!-- Companies Grid -->
        <div v-if="companies.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <div v-for="company in companies" :key="company.id"
                class="bg-white rounded-xl border border-slate-200 overflow-hidden hover:shadow-md transition group">
                <!-- Card Header -->
                <div class="relative p-5 border-b border-slate-100">
                    <div class="flex items-start gap-3">
                        <!-- Logo -->
                        <div
                            class="w-14 h-14 p-1 rounded-xl overflow-hidden border border-slate-100 bg-slate-50 flex items-center justify-center flex-shrink-0">
                            <img v-if="company.logo_url" :src="company.logo_url" :alt="company.company_name"
                                class="w-auto h-full object-cover" />
                            <span v-else class="text-2xl">{{ typeConfig[company.company_type]?.icon ?? '🏢' }}</span>
                        </div>

                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-slate-900 truncate">{{ company.company_name }}</h3>
                            <span
                                :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium mt-1', typeConfig[company.company_type]?.bg, typeConfig[company.company_type]?.text]">
                                {{ typeConfig[company.company_type]?.icon }}
                                {{ typeConfig[company.company_type]?.label }}
                            </span>
                        </div>
                    </div>

                    <!-- Active badge -->
                    <div class="absolute top-4 right-4">
                        <span
                            :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold', company.is_active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500']">
                            <span
                                :class="['w-1.5 h-1.5 rounded-full', company.is_active ? 'bg-green-500' : 'bg-slate-400']"></span>
                            {{ company.is_active ? "Active" : "Inactive" }}
                        </span>
                    </div>
                </div>

                <!-- Card Body -->
                <div class="p-5 space-y-2 text-sm text-slate-600">
                    <div v-if="company.city" class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        </svg>
                        {{ company.city }}
                    </div>
                    <div v-if="company.company_email" class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <span class="truncate">{{ company.company_email }}</span>
                    </div>
                    <div v-if="company.owner" class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span class="truncate">{{ company.owner.Full_Name }}</span>
                    </div>
                    <p class="text-xs text-slate-400">Created {{ formatDate(company.created_at) }}</p>
                </div>

                <!-- Card Footer -->
                <div class="px-5 pb-5 flex items-center gap-2">
                    <button @click="openModal(company)"
                        class="flex-1 py-2 rounded-xl bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-slate-200 transition">
                        View Details
                    </button>
                    <button @click="toggleStatus(company)"
                        :class="['px-3 py-2 rounded-xl text-xs font-semibold transition', company.is_active ? 'bg-red-50 text-red-600 hover:bg-red-100' : 'bg-green-50 text-green-600 hover:bg-green-100']">
                        {{ company.is_active ? "Deactivate" : "Activate" }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-else class="bg-white rounded-xl border border-slate-200 p-16 text-center">
            <span class="text-5xl">🏢</span>
            <h3 class="mt-4 font-bold text-slate-700">No companies yet</h3>
            <p class="text-sm text-slate-500 mt-1">Accept a partner request and create a company from there.</p>
            <Link :href="route('admin.partner-requests.index')"
                class="inline-flex items-center gap-2 mt-4 px-4 py-2 rounded-xl bg-primary text-white text-sm font-semibold hover:-translate-y-0.5 transition">
                View Partner Requests
            </Link>
        </div>

        <!-- Pagination -->
        <!-- <div v-if="companies.last_page > 1" class="mt-6 flex items-center justify-between text-sm text-slate-500">
            <span>Showing {{ companies.from }}–{{ companies.to }} of {{ companies.total }}</span>
            <div class="flex gap-1">
                <Link v-for="link in companies.links" :key="link.label" :href="link.url ?? '#'" v-html="link.label"
                    :class="[
                        'px-3 py-1.5 rounded-lg text-xs transition',
                        link.active ? 'bg-primary text-white font-semibold' : link.url ? 'hover:bg-slate-100' : 'text-slate-300 cursor-not-allowed'
                    ]" />
            </div>
        </div> -->

        <!-- ================================================================
            VIEW DETAIL MODAL (FULL DETAILS)
        =============================================================== -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showModal && modalData" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeModal"></div>

                    <div
                        class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">

                        <!-- Header with logo & name -->
                        <div class="flex items-center gap-4 p-6 py-3 border-b border-slate-100">
                            <div
                                class="w-14 h-14 p-1 rounded-xl overflow-hidden bg-slate-50 border border-slate-100 flex items-center justify-center flex-shrink-0">
                                <img v-if="modalData.logo_url" :src="modalData.logo_url"
                                    class="w-auto h-full object-cover" />
                                <span v-else class="text-2xl">{{ typeConfig[modalData.company_type]?.icon }}</span>
                            </div>
                            <div class="flex-1">
                                <h2 class="text-lg font-bold text-slate-900">{{ modalData.company_name }}</h2>
                                <span
                                    :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium', typeConfig[modalData.company_type]?.bg, typeConfig[modalData.company_type]?.text]">
                                    {{ typeConfig[modalData.company_type]?.icon }} {{
                                        typeConfig[modalData.company_type]?.label }}
                                </span>
                            </div>
                            <button @click="closeModal"
                                class="p-2 rounded-lg hover:bg-slate-100 text-slate-400 transition">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Scrollable body -->
                        <div class="overflow-y-auto p-6 space-y-6">

                            <!-- ========== COMPANY DETAILS CARD ========== -->
                            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                                <div class="px-4 py-3 bg-slate-50 border-b border-slate-100 flex items-center gap-2">
                                    <span class="text-lg">🏢</span>
                                    <h3 class="font-semibold text-slate-700">Company Information</h3>
                                </div>
                                <div class="p-4 grid grid-cols-2 gap-4 text-sm">
                                    <!-- Row 1 -->
                                    <div>
                                        <p class="text-xs text-slate-400">Email</p>
                                        <p class="font-medium text-slate-800">{{ modalData.company_email || '—' }}</p>
                                        <p v-if="modalData.company_email_verified_at"
                                            class="text-xs text-green-600 mt-0.5">✅ Verified</p>
                                        <p v-else class="text-xs text-amber-600 mt-0.5">⏳ Unverified</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-400">Phone</p>
                                        <p class="font-medium text-slate-800">{{ modalData.company_phone || '—' }}</p>
                                    </div>
                                    <!-- Row 2 -->
                                    <div>
                                        <p class="text-xs text-slate-400">Helpline</p>
                                        <p class="font-medium text-slate-800">{{ modalData.helpline_number || '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-400">City</p>
                                        <p class="font-medium text-slate-800">{{ modalData.city || '—' }}</p>
                                    </div>
                                    <!-- Row 3 - Address full width -->
                                    <div class="col-span-2">
                                        <p class="text-xs text-slate-400">Address</p>
                                        <p class="font-medium text-slate-800">{{ modalData.address || '—' }}</p>
                                    </div>
                                    <!-- Row 4 -->
                                    <div>
                                        <p class="text-xs text-slate-400">Commission %</p>
                                        <p class="font-medium text-slate-800">{{ modalData.percentage ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-400">Status</p>
                                        <p class="font-medium"
                                            :class="modalData.is_active ? 'text-green-600' : 'text-slate-500'">
                                            {{ modalData.is_active ? 'Active' : 'Inactive' }}
                                        </p>
                                    </div>
                                    <!-- Row 5 - Created at & by -->
                                    <div>
                                        <p class="text-xs text-slate-400">Created</p>
                                        <p class="font-medium text-slate-800">{{ formatDate(modalData.created_at) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-400">Created by</p>
                                        <p class="font-medium text-slate-800">{{ modalData.created_by?.Full_Name ||
                                            'System' }}</p>
                                    </div>
                                </div>
                                <!-- Description block (if any) -->
                                <div v-if="modalData.description" class="px-4 pb-4">
                                    <div class="bg-blue-50 border border-blue-100 rounded-xl p-3">
                                        <p class="text-xs font-semibold text-blue-500 uppercase mb-1">Description</p>
                                        <p class="text-sm text-slate-700">{{ modalData.description }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- ========== OWNER DETAILS CARD ========== -->
                            <div v-if="modalData.owner"
                                class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                                <div class="px-4 py-3 bg-slate-50 border-b border-slate-100 flex items-center gap-2">
                                    <span class="text-lg">👤</span>
                                    <h3 class="font-semibold text-slate-700">Company Owner</h3>
                                </div>
                                <div class="p-4 grid grid-cols-2 gap-4 text-sm bg-white">
                                    <!-- Profile picture (if exists) -->
                                    <div v-if="modalData.owner.Profile_Picture"
                                        class="col-span-2 flex items-center gap-3 pb-2 border-b border-slate-100">
                                        <img :src="modalData.owner.profile_picture_url"
                                            class="w-12 h-12 rounded-full object-cover border border-slate-200" />
                                        <div>
                                            <p class="font-semibold text-slate-800">{{ modalData.owner.Full_Name }}</p>
                                            <p class="text-xs text-slate-400">Owner</p>
                                        </div>
                                    </div>

                                    <!-- Basic info -->
                                    <div>
                                        <p class="text-xs text-slate-400">Full Name</p>
                                        <p class="font-medium text-slate-800">{{ modalData.owner.Full_Name }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-400">Username</p>
                                        <p class="font-medium text-slate-800">{{ modalData.owner.User_Name }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-400">Email</p>
                                        <p class="font-medium text-slate-800">{{ modalData.owner.Email }}</p>
                                        <p v-if="modalData.owner.email_verified_at"
                                            class="text-xs text-green-600 mt-0.5">✅ Verified</p>
                                        <p v-else class="text-xs text-amber-600 mt-0.5">⏳ Unverified</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-400">Phone</p>
                                        <p class="font-medium text-slate-800">{{ modalData.owner.Phone_Number }}</p>
                                    </div>
                                    <!-- CNIC -->
                                    <div>
                                        <p class="text-xs text-slate-400">CNIC</p>
                                        <p class="font-medium text-slate-800">{{ modalData.owner.CNIC || '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-400">User Type</p>
                                        <p class="font-medium text-slate-800">{{ modalData.owner.User_Type }}</p>
                                    </div>
                                    <!-- Status & Meta -->
                                    <div>
                                        <p class="text-xs text-slate-400">Account Status</p>
                                        <p class="font-medium"
                                            :class="modalData.owner.Is_Active ? 'text-green-600' : 'text-slate-500'">
                                            {{ modalData.owner.Is_Active ? 'Active' : 'Inactive' }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-400">Created</p>
                                        <p class="font-medium text-slate-800">{{ formatDate(modalData.owner.Created_On)
                                            }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- If no owner loaded -->
                            <div v-else
                                class="bg-amber-50 border border-amber-100 rounded-xl p-4 text-center text-sm text-amber-700">
                                No owner account linked to this company.
                            </div>

                            <!-- Partner request link (if any) -->
                            <div v-if="modalData.partner_request"
                                class="bg-purple-50 border border-purple-100 rounded-xl p-3 text-sm">
                                <span class="font-semibold text-purple-700">Partner Request #{{
                                    modalData.partner_request.id }}</span>
                                <span class="text-purple-600 ml-2">({{ modalData.partner_request.status }})</span>
                            </div>
                        </div>

                        <!-- Footer actions -->
                        <div class="p-5 border-t border-slate-100 flex justify-end gap-2">
                            <button @click="toggleStatus(modalData); closeModal()"
                                :class="['px-4 py-2 rounded-xl text-sm font-semibold transition', modalData.is_active ? 'bg-red-50 text-red-600 hover:bg-red-100' : 'bg-green-50 text-green-600 hover:bg-green-100']">
                                {{ modalData.is_active ? "Deactivate" : "Activate" }}
                            </button>
                            <button @click="closeModal"
                                class="px-4 py-2 rounded-xl bg-slate-100 text-slate-600 text-sm font-semibold hover:bg-slate-200 transition">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

    </AdminLayout>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
