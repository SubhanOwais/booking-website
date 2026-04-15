<script setup>
import { ref, computed, watch } from "vue";
import { router, useForm, Link } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";

const props = defineProps({
    requests: Object,
    counts: Object,
    filters: Object,
});

/* =========================
   FILTERS
========================= */
const search = ref(props.filters.search || "");
const activeTab = ref(props.filters.status || "all");

const tabs = [
    { key: "all", label: "All", color: "gray" },
    { key: "pending", label: "Pending", color: "yellow" },
    { key: "reviewing", label: "Reviewing", color: "blue" },
    { key: "accepted", label: "Accepted", color: "green" },
    { key: "rejected", label: "Rejected", color: "red" },
];

function applyFilter(status) {
    activeTab.value = status;
    router.get(route("admin.partner-requests.index"), {
        status: status !== "all" ? status : undefined,
        search: search.value || undefined,
    }, { preserveState: true, replace: true });
}

let searchTimer = null;
watch(search, (val) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get(route("admin.partner-requests.index"), {
            status: activeTab.value !== "all" ? activeTab.value : undefined,
            search: val || undefined,
        }, { preserveState: true, replace: true });
    }, 400);
});

/* =========================
   VIEW MODAL
========================= */
const showModal = ref(false);
const modalData = ref(null);

function openModal(request) {
    modalData.value = request;
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    setTimeout(() => { modalData.value = null; }, 300);
}

/* =========================
   ACTION MODAL (accept/reject)
========================= */
const showActionModal = ref(false);
const actionType = ref(""); // 'accept' | 'reject' | 'reviewing'
const actionTarget = ref(null);

const actionForm = useForm({
    admin_notes: "",
});

function openAction(type, request) {
    actionType.value = type;
    actionTarget.value = request;
    actionForm.admin_notes = request.admin_notes || "";
    showActionModal.value = true;
}

function closeAction() {
    showActionModal.value = false;
    setTimeout(() => {
        actionTarget.value = null;
        actionForm.reset();
    }, 300);
}

function submitAction() {
    const id = actionTarget.value.id;
    const routeMap = {
        accept: route("admin.partner-requests.accept", id),
        reject: route("admin.partner-requests.reject", id),
        reviewing: route("admin.partner-requests.reviewing", id),
    };

    actionForm.patch(routeMap[actionType.value], {
        onSuccess: () => closeAction(),
    });
}

/* =========================
   HELPERS
========================= */
const typeIcons = {
    bus: "🚌",
    hotel: "🏨",
    car_rental: "🚗",
    tour: "🗺️",
    other: "📦",
};

const typeLabels = {
    bus: "Bus Company",
    hotel: "Hotel",
    car_rental: "Car Rental",
    tour: "Tour Operator",
    other: "Other",
};

const statusConfig = {
    pending: { label: "Pending", bg: "bg-yellow-100", text: "text-yellow-700", dot: "bg-yellow-500", border: "border-yellow-200" },
    reviewing: { label: "Reviewing", bg: "bg-blue-100", text: "text-blue-700", dot: "bg-blue-500", border: "border-blue-200" },
    accepted: { label: "Accepted", bg: "bg-green-100", text: "text-green-700", dot: "bg-green-500", border: "border-green-200" },
    rejected: { label: "Rejected", bg: "bg-red-100", text: "text-red-700", dot: "bg-red-500", border: "border-red-200" },
};

function formatDate(date) {
    if (!date) return "—";
    return new Date(date).toLocaleDateString("en-PK", {
        year: "numeric", month: "short", day: "numeric",
    });
}

const actionConfig = computed(() => ({
    accept: {
        title: "Accept Partner Request",
        description: `Accept ${actionTarget.value?.company_name} as a partner?`,
        btnLabel: "Accept Request",
        btnClass: "bg-green-600 hover:bg-green-700",
        icon: "✅",
        notesLabel: "Welcome message / notes for company (optional)",
    },
    reject: {
        title: "Reject Partner Request",
        description: `Reject the request from ${actionTarget.value?.company_name}?`,
        btnLabel: "Reject Request",
        btnClass: "bg-red-600 hover:bg-red-700",
        icon: "❌",
        notesLabel: "Reason for rejection (optional)",
    },
    reviewing: {
        title: "Mark as Under Review",
        description: `Mark ${actionTarget.value?.company_name} as currently being reviewed?`,
        btnLabel: "Mark Reviewing",
        btnClass: "bg-blue-600 hover:bg-blue-700",
        icon: "🔍",
        notesLabel: "Internal notes (optional)",
    },
}[actionType.value] ?? {}));
</script>

<template>
    <AdminLayout title="Partner Requests">

        <!-- Flash -->
        <div v-if="$page.props.flash?.success"
            class="mb-6 flex items-center gap-3 p-4 rounded-xl bg-green-50 border border-green-200 text-green-800 text-sm font-medium">
            <span>{{ $page.props.flash.success }}</span>
        </div>

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Partner Requests</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Review and manage incoming partnership applications.
                </p>
            </div>
            <div class="flex items-center gap-2 text-sm">
                <span class="px-3 py-1.5 rounded-full bg-yellow-100 text-yellow-700 font-semibold">
                    {{ counts.pending }} pending
                </span>
            </div>
        </div>

        <!-- Stat Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
            <div v-for="tab in tabs.filter(t => t.key !== 'all')" :key="tab.key"
                class="bg-white rounded-xl border border-slate-200 p-4 cursor-pointer hover:shadow-md transition"
                @click="applyFilter(tab.key)">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">{{ tab.label }}</p>
                <p class="text-2xl font-bold text-slate-900 mt-1">{{ counts[tab.key] }}</p>
            </div>
        </div>

        <!-- Filters Bar -->
        <div
            class="bg-white rounded-xl border border-slate-200 p-4 mb-6 flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">

            <!-- Status Tabs -->
            <div class="flex flex-wrap gap-1.5">
                <button v-for="tab in tabs" :key="tab.key" @click="applyFilter(tab.key)" :class="[
                    'px-3 py-1.5 rounded-lg text-sm font-medium transition',
                    activeTab === tab.key
                        ? 'bg-primary text-white shadow-sm'
                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                ]">
                    {{ tab.label }}
                    <span class="ml-1 text-xs opacity-75">({{ counts[tab.key] }})</span>
                </button>
            </div>

            <!-- Search -->
            <div class="relative w-full sm:w-64">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input v-model="search" type="text" placeholder="Search company, email, city..."
                    class="w-full pl-9 pr-4 py-2 text-sm border border-slate-200 rounded-lg outline-none focus:border-primary/60 focus:ring-2 focus:ring-primary/20" />
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th
                                class="text-left px-5 py-3.5 font-semibold text-slate-600 text-xs uppercase tracking-wide">
                                #</th>
                            <th
                                class="text-left px-5 py-3.5 font-semibold text-slate-600 text-xs uppercase tracking-wide">
                                Company</th>
                            <th
                                class="text-left px-5 py-3.5 font-semibold text-slate-600 text-xs uppercase tracking-wide">
                                Type</th>
                            <th
                                class="text-left px-5 py-3.5 font-semibold text-slate-600 text-xs uppercase tracking-wide">
                                Contact</th>
                            <th
                                class="text-left px-5 py-3.5 font-semibold text-slate-600 text-xs uppercase tracking-wide">
                                City</th>
                            <th
                                class="text-left px-5 py-3.5 font-semibold text-slate-600 text-xs uppercase tracking-wide">
                                Status</th>
                            <th
                                class="text-left px-5 py-3.5 font-semibold text-slate-600 text-xs uppercase tracking-wide">
                                Submitted</th>
                            <th
                                class="text-left px-5 py-3.5 font-semibold text-slate-600 text-xs uppercase tracking-wide">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="requests.data.length === 0">
                            <td colspan="8" class="px-5 py-16 text-center">
                                <div class="flex flex-col items-center gap-2 text-slate-400">
                                    <span class="text-4xl">🤝</span>
                                    <p class="font-medium text-slate-600">No partner requests found</p>
                                    <p class="text-xs">Try adjusting your filters</p>
                                </div>
                            </td>
                        </tr>

                        <tr v-for="(req, i) in requests.data" :key="req.id" class="hover:bg-slate-50 transition">
                            <!-- # -->
                            <td class="px-5 py-4 text-slate-400 text-xs">
                                {{ (requests.current_page - 1) * requests.per_page + i + 1 }}
                            </td>

                            <!-- Company -->
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-900">{{ req.company_name }}</p>
                                <p class="text-xs text-slate-400 mt-0.5">{{ req.email }}</p>
                            </td>

                            <!-- Type -->
                            <td class="px-5 py-4">
                                <span
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-medium">
                                    {{ typeIcons[req.company_type] }}
                                    {{ typeLabels[req.company_type] }}
                                </span>
                            </td>

                            <!-- Contact -->
                            <td class="px-5 py-4">
                                <p class="text-slate-700">{{ req.contact_person }}</p>
                                <p class="text-xs text-slate-400">{{ req.phone }}</p>
                            </td>

                            <!-- City -->
                            <td class="px-5 py-4 text-slate-600">{{ req.city }}</td>

                            <!-- Status -->
                            <td class="px-5 py-4">
                                <span v-if="statusConfig[req.status]" :class="[
                                    'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border',
                                    statusConfig[req.status].bg,
                                    statusConfig[req.status].text,
                                    statusConfig[req.status].border,
                                ]">
                                    <span :class="['w-1.5 h-1.5 rounded-full', statusConfig[req.status].dot]"></span>
                                    {{ statusConfig[req.status].label }}
                                </span>
                            </td>

                            <!-- Date -->
                            <td class="px-5 py-4 text-slate-500 text-xs">{{ formatDate(req.created_at) }}</td>

                            <!-- Actions -->
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-1.5">
                                    <!-- View -->
                                    <button @click="openModal(req)"
                                        class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition"
                                        title="View Details">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>

                                    <!-- Reviewing -->
                                    <button v-if="req.status === 'pending'" @click="openAction('reviewing', req)"
                                        class="p-1.5 rounded-lg text-blue-500 hover:bg-blue-50 transition"
                                        title="Mark as Reviewing">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </button>

                                    <!-- Accept -->
                                    <button v-if="req.status === 'pending' || req.status === 'reviewing'"
                                        @click="openAction('accept', req)"
                                        class="p-1.5 rounded-lg text-green-600 hover:bg-green-50 transition"
                                        title="Accept">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>

                                    <!-- Reject -->
                                    <button v-if="req.status === 'pending' || req.status === 'reviewing'"
                                        @click="openAction('reject', req)"
                                        class="p-1.5 rounded-lg text-red-500 hover:bg-red-50 transition" title="Reject">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>

                                    <Link v-if="req.status === 'accepted' && !req.company_id"
                                        :href="route('admin.companies.create', req.id)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-primary text-white text-xs font-semibold hover:bg-primary/90 transition"
                                        title="Create Company">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4v16m8-8H4" />
                                        </svg>
                                        Create Company
                                    </Link>

                                    <!-- Already created badge -->
                                    <span v-if="req.status === 'accepted' && req.company_id"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-green-100 text-green-700 text-xs font-semibold">
                                        ✅ Company Created
                                    </span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="requests.last_page > 1"
                class="px-5 py-4 border-t border-slate-100 flex items-center justify-between text-sm text-slate-500">
                <span>Showing {{ requests.from }}–{{ requests.to }} of {{ requests.total }}</span>
                <div class="flex gap-1">
                    <Link v-for="link in requests.links" :key="link.label" :href="link.url ?? '#'" v-html="link.label"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-xs transition',
                            link.active
                                ? 'bg-primary text-white font-semibold'
                                : link.url
                                    ? 'hover:bg-slate-100 text-slate-600'
                                    : 'text-slate-300 cursor-not-allowed'
                        ]" />
                </div>
            </div>
        </div>

        <!-- ================================================================
             VIEW DETAIL MODAL
        ================================================================ -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showModal && modalData" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <!-- Backdrop -->
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeModal"></div>

                    <!-- Modal -->
                    <div
                        class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">

                        <!-- Header -->
                        <div class="flex items-start justify-between p-6 border-b border-slate-100">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-2xl">
                                    {{ typeIcons[modalData.company_type] }}
                                </div>
                                <div>
                                    <h2 class="text-lg font-bold text-slate-900">{{ modalData.company_name }}</h2>
                                    <span :class="[
                                        'inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-semibold border',
                                        statusConfig[modalData.status]?.bg,
                                        statusConfig[modalData.status]?.text,
                                        statusConfig[modalData.status]?.border,
                                    ]">
                                        <span
                                            :class="['w-1.5 h-1.5 rounded-full', statusConfig[modalData.status]?.dot]"></span>
                                        {{ statusConfig[modalData.status]?.label }}
                                    </span>
                                </div>
                            </div>
                            <button @click="closeModal"
                                class="p-2 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="overflow-y-auto p-6 space-y-5">

                            <!-- Info Grid -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-slate-50 rounded-xl p-4">
                                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Contact
                                        Person</p>
                                    <p class="font-semibold text-slate-900">{{ modalData.contact_person }}</p>
                                </div>
                                <div class="bg-slate-50 rounded-xl p-4">
                                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Company
                                        Type</p>
                                    <p class="font-semibold text-slate-900">
                                        {{ typeIcons[modalData.company_type] }} {{ typeLabels[modalData.company_type] }}
                                    </p>
                                </div>
                                <div class="bg-slate-50 rounded-xl p-4">
                                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Email
                                    </p>
                                    <a :href="`mailto:${modalData.email}`"
                                        class="font-semibold text-primary hover:underline">
                                        {{ modalData.email }}
                                    </a>
                                </div>
                                <div class="bg-slate-50 rounded-xl p-4">
                                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Phone
                                    </p>
                                    <a :href="`tel:${modalData.phone}`" class="font-semibold text-slate-900">
                                        {{ modalData.phone }}
                                    </a>
                                </div>
                                <div class="bg-slate-50 rounded-xl p-4">
                                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">City
                                    </p>
                                    <p class="font-semibold text-slate-900">{{ modalData.city }}</p>
                                </div>
                                <div class="bg-slate-50 rounded-xl p-4">
                                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">
                                        Submitted On</p>
                                    <p class="font-semibold text-slate-900">{{ formatDate(modalData.created_at) }}</p>
                                </div>
                            </div>

                            <!-- Company Detail -->
                            <div v-if="modalData.company_detail"
                                class="bg-blue-50 border border-blue-100 rounded-xl p-4">
                                <p class="text-xs font-semibold text-blue-500 uppercase tracking-wide mb-2">Company
                                    Details</p>
                                <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-wrap">{{
                                    modalData.company_detail }}</p>
                            </div>

                            <!-- Admin Notes -->
                            <div v-if="modalData.admin_notes"
                                class="bg-amber-50 border border-amber-100 rounded-xl p-4">
                                <p class="text-xs font-semibold text-amber-600 uppercase tracking-wide mb-2">Admin Notes
                                </p>
                                <p class="text-sm text-slate-700 leading-relaxed">{{ modalData.admin_notes }}</p>
                            </div>

                            <!-- Reviewed by -->
                            <div v-if="modalData.reviewed_at" class="flex items-center gap-2 text-xs text-slate-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Reviewed on {{ formatDate(modalData.reviewed_at) }}
                                <span v-if="modalData.reviewer">by {{ modalData.reviewer.name }}</span>
                            </div>
                        </div>

                        <!-- Footer actions -->
                        <div class="p-6 border-t border-slate-100 flex flex-wrap gap-2 justify-end">
                            <button v-if="modalData.status === 'pending' || modalData.status === 'reviewing'"
                                @click="closeModal(); openAction('reject', modalData)"
                                class="px-4 py-2 rounded-xl bg-red-50 text-red-600 border border-red-200 text-sm font-semibold hover:bg-red-100 transition">
                                ❌ Reject
                            </button>
                            <button v-if="modalData.status === 'pending'"
                                @click="closeModal(); openAction('reviewing', modalData)"
                                class="px-4 py-2 rounded-xl bg-blue-50 text-blue-600 border border-blue-200 text-sm font-semibold hover:bg-blue-100 transition">
                                🔍 Mark Reviewing
                            </button>
                            <button v-if="modalData.status === 'pending' || modalData.status === 'reviewing'"
                                @click="closeModal(); openAction('accept', modalData)"
                                class="px-4 py-2 rounded-xl bg-green-600 text-white text-sm font-semibold hover:bg-green-700 transition">
                                ✅ Accept
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

        <!-- ================================================================
             ACTION MODAL (accept / reject / reviewing)
        ================================================================ -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showActionModal && actionTarget && actionConfig.title"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeAction"></div>

                    <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden">
                        <div class="p-6">
                            <div class="text-4xl mb-3">{{ actionConfig.icon }}</div>
                            <h3 class="text-lg font-bold text-slate-900 mb-1">{{ actionConfig.title }}</h3>
                            <p class="text-sm text-slate-500 mb-5">{{ actionConfig.description }}</p>

                            <div class="mb-5">
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    {{ actionConfig.notesLabel }}
                                </label>
                                <textarea v-model="actionForm.admin_notes" rows="3" placeholder="Add a note..."
                                    class="w-full px-4 py-3 text-sm border border-slate-200 rounded-xl outline-none focus:border-primary/60 focus:ring-2 focus:ring-primary/20 resize-none"></textarea>
                                <p v-if="actionForm.errors.admin_notes" class="mt-1 text-xs text-red-600">
                                    {{ actionForm.errors.admin_notes }}
                                </p>
                            </div>

                            <div class="flex gap-3">
                                <button @click="closeAction"
                                    class="flex-1 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-sm font-semibold hover:bg-slate-50 transition">
                                    Cancel
                                </button>
                                <button @click="submitAction" :disabled="actionForm.processing"
                                    :class="['flex-1 py-2.5 rounded-xl text-white text-sm font-semibold transition disabled:opacity-60', actionConfig.btnClass]">
                                    {{ actionForm.processing ? "Saving..." : actionConfig.btnLabel }}
                                </button>
                            </div>
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

.modal-enter-active .relative,
.modal-leave-active .relative {
    transition: transform 0.2s ease;
}

.modal-enter-from .relative,
.modal-leave-to .relative {
    transform: scale(0.95);
}
</style>
