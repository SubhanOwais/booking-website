<script setup>
import { useForm, Link, router } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { ref, computed } from "vue";

const props = defineProps({
    partnerRequest: Object,
});

const logoPreview = ref(null);

const form = useForm({
    partner_request_id: props.partnerRequest.id,

    // Company fields
    company_name: props.partnerRequest.company_name,
    company_type: props.partnerRequest.company_type,
    company_email: props.partnerRequest.email,
    company_phone: props.partnerRequest.phone,
    helpline_number: "",
    city: props.partnerRequest.city,
    address: "",
    description: props.partnerRequest.company_detail || "",
    is_active: true,
    company_logo: null,
    percentage: "",

    // Owner fields
    owner_full_name: props.partnerRequest.contact_person,
    owner_username: "",
    owner_email: props.partnerRequest.email,
    owner_phone: props.partnerRequest.phone,
    owner_cnic: "",
    owner_password: "",
    owner_password_confirmation: "",
});

const typeLabels = {
    bus: { label: "Bus Company", icon: "🚌" },
    hotel: { label: "Hotel", icon: "🏨" },
    car_rental: { label: "Car Rental", icon: "🚗" },
    tour: { label: "Tour Operator", icon: "🗺️" },
    other: { label: "Other", icon: "📦" },
};

const showPassword = ref(false);
const showConfirm = ref(false);

function handleLogoChange(e) {
    const file = e.target.files[0];
    if (!file) return;
    form.company_logo = file;
    const reader = new FileReader();
    reader.onload = (ev) => { logoPreview.value = ev.target.result; };
    reader.readAsDataURL(file);
}

function removeLogo() {
    form.company_logo = null;
    logoPreview.value = null;
}

function submit() {
    form.post(route("admin.companies.store"), {
        forceFormData: true,
    });
}

const passwordStrength = computed(() => {
    const p = form.owner_password;
    if (!p) return { score: 0, label: "", color: "" };
    let score = 0;
    if (p.length >= 8) score++;
    if (/[A-Z]/.test(p)) score++;
    if (/[0-9]/.test(p)) score++;
    if (/[^A-Za-z0-9]/.test(p)) score++;
    const map = [
        { label: "", color: "" },
        { label: "Weak", color: "bg-red-400" },
        { label: "Fair", color: "bg-yellow-400" },
        { label: "Good", color: "bg-blue-400" },
        { label: "Strong", color: "bg-green-500" },
    ];
    return { score, ...map[score] };
});
</script>

<template>
    <AdminLayout title="Create Company">

        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-sm text-slate-500 mb-6">
            <Link :href="route('admin.partner-requests.index')" class="hover:text-primary transition">
                Partner Requests
            </Link>
            <span>/</span>
            <Link :href="route('admin.companies.index')" class="hover:text-primary transition">
                Companies
            </Link>
            <span>/</span>
            <span class="text-slate-800 font-medium">Create Company</span>
        </div>

        <!-- Partner Request Info Banner -->
        <div class="mb-6 p-4 rounded-xl bg-blue-50 border border-blue-100 flex items-start gap-3">
            <span class="text-2xl">🤝</span>
            <div>
                <p class="font-semibold text-blue-900 text-sm">
                    Creating company from accepted partner request
                </p>
                <p class="text-xs text-blue-700 mt-0.5">
                    <span class="font-medium">{{ partnerRequest.company_name }}</span>
                    submitted by {{ partnerRequest.contact_person }} ({{ partnerRequest.email }})
                </p>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- LEFT COLUMN — Company Info -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Company Details Card -->
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-2">
                            <span class="text-lg">🏢</span>
                            <h2 class="font-semibold text-slate-900">Company Information</h2>
                        </div>

                        <div class="p-6 space-y-4">

                            <!-- Company Name + Type -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                        Company Name <span class="text-xs text-red-600 font-medium">(Required)</span>
                                    </label>
                                    <input v-model="form.company_name" type="text" placeholder="e.g. Royal Travels"
                                        :class="[
                                            'w-full px-4 py-2.5 rounded-xl border text-sm outline-none transition',
                                            form.errors.company_name
                                                ? 'border-red-400 bg-red-50'
                                                : 'border-slate-200 focus:border-primary/60 focus:ring-2 focus:ring-primary/20'
                                        ]" />
                                    <p v-if="form.errors.company_name" class="mt-1 text-xs text-red-600">{{
                                        form.errors.company_name }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                        Company Type <span class="text-xs text-red-600 font-medium">(Required)</span>
                                    </label>
                                    <select v-model="form.company_type" :class="[
                                        'w-full px-4 py-2.5 rounded-xl border text-sm outline-none transition',
                                        form.errors.company_type
                                            ? 'border-red-400 bg-red-50'
                                            : 'border-slate-200 focus:border-primary/60 focus:ring-2 focus:ring-primary/20'
                                    ]">
                                        <option value="" disabled>Select type</option>
                                        <option v-for="(t, key) in typeLabels" :key="key" :value="key">
                                            {{ t.icon }} {{ t.label }}
                                        </option>
                                    </select>
                                    <p v-if="form.errors.company_type" class="mt-1 text-xs text-red-600">{{
                                        form.errors.company_type }}</p>
                                </div>
                            </div>

                            <!-- Email + Phone -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                        Company Email
                                    </label>
                                    <input v-model="form.company_email" type="email" placeholder="info@company.com"
                                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm outline-none focus:border-primary/60 focus:ring-2 focus:ring-primary/20 transition" />
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                        Company Phone <span class="text-xs text-red-600 font-medium">(Required)</span>
                                    </label>
                                    <input v-model="form.company_phone" type="tel" placeholder="03XX-XXXXXXX"
                                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm outline-none focus:border-primary/60 focus:ring-2 focus:ring-primary/20 transition" />
                                </div>
                            </div>

                            <!-- Helpline + City -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                        Helpline Number <span class="text-xs text-red-600 font-medium">(Required)</span>
                                    </label>
                                    <input v-model="form.helpline_number" type="tel" placeholder="0800-XXXXX"
                                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm outline-none focus:border-primary/60 focus:ring-2 focus:ring-primary/20 transition" />
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">City</label>
                                    <input v-model="form.city" type="text" placeholder="Lahore"
                                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm outline-none focus:border-primary/60 focus:ring-2 focus:ring-primary/20 transition" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Address -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                        Address <span class="text-xs text-red-600 font-medium">(Required)</span>
                                    </label>
                                    <input v-model="form.address" type="text" placeholder="Full company address"
                                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm outline-none focus:border-primary/60 focus:ring-2 focus:ring-primary/20 transition" />
                                </div>
                                <!-- Percentage -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                        Commission Percentage (%) <span
                                            class="text-xs text-red-600 font-medium">(Required)</span>
                                    </label>
                                    <input v-model="form.percentage" type="number" step="0.01" min="0" max="100"
                                        placeholder="e.g. 10.00"
                                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm outline-none focus:border-primary/60 focus:ring-2 focus:ring-primary/20 transition" />
                                    <p v-if="form.errors.percentage" class="mt-1 text-xs text-red-600">
                                        {{ form.errors.percentage }}
                                    </p>
                                </div>
                            </div>

                            <!-- Description -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Description</label>
                                <textarea v-model="form.description" rows="3"
                                    placeholder="Brief description about the company..."
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm outline-none focus:border-primary/60 focus:ring-2 focus:ring-primary/20 transition resize-none"></textarea>
                            </div>

                            <!-- Active toggle -->
                            <div class="flex items-center gap-3 p-4 rounded-xl bg-slate-50 border border-slate-200">
                                <button type="button" @click="form.is_active = !form.is_active" :class="[
                                    'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                                    form.is_active ? 'bg-primary' : 'bg-slate-300'
                                ]">
                                    <span
                                        :class="['inline-block h-4 w-4 rounded-full bg-white shadow transition-transform', form.is_active ? 'translate-x-6' : 'translate-x-1']"></span>
                                </button>
                                <div>
                                    <p class="text-sm font-semibold text-slate-700">
                                        {{ form.is_active ? "Active" : "Inactive" }}
                                    </p>
                                    <p class="text-xs text-slate-400">Company will {{ form.is_active ? "" : "not " }}be
                                        visible on the platform</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Owner / User Card -->
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-2">
                            <span class="text-lg">👤</span>
                            <h2 class="font-semibold text-slate-900">Company Owner Account</h2>
                        </div>

                        <div class="p-6 space-y-4">
                            <p class="text-xs text-slate-500 bg-amber-50 border border-amber-100 rounded-lg p-3">
                                ⚠️ This will create a login account for the company owner. Share credentials with them
                                securely.
                            </p>

                            <!-- Full Name + Username -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                        Full Name <span class="text-xs text-red-600 font-medium">(Required)</span>
                                    </label>
                                    <input v-model="form.owner_full_name" type="text" placeholder="Owner full name"
                                        :class="[
                                            'w-full px-4 py-2.5 rounded-xl border text-sm outline-none transition',
                                            form.errors.owner_full_name
                                                ? 'border-red-400 bg-red-50'
                                                : 'border-slate-200 focus:border-primary/60 focus:ring-2 focus:ring-primary/20'
                                        ]" />
                                    <p v-if="form.errors.owner_full_name" class="mt-1 text-xs text-red-600">{{
                                        form.errors.owner_full_name }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                        Username <span class="text-xs text-red-600 font-medium">(Required)</span>
                                    </label>
                                    <input v-model="form.owner_username" type="text" placeholder="unique_username"
                                        :class="[
                                            'w-full px-4 py-2.5 rounded-xl border text-sm outline-none transition',
                                            form.errors.owner_username
                                                ? 'border-red-400 bg-red-50'
                                                : 'border-slate-200 focus:border-primary/60 focus:ring-2 focus:ring-primary/20'
                                        ]" />
                                    <p v-if="form.errors.owner_username" class="mt-1 text-xs text-red-600">{{
                                        form.errors.owner_username }}</p>
                                </div>
                            </div>

                            <!-- Email + Phone -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                        Email <span class="text-xs text-red-600 font-medium">(Required)</span>
                                    </label>
                                    <input v-model="form.owner_email" type="email" placeholder="owner@company.com"
                                        :class="[
                                            'w-full px-4 py-2.5 rounded-xl border text-sm outline-none transition',
                                            form.errors.owner_email
                                                ? 'border-red-400 bg-red-50'
                                                : 'border-slate-200 focus:border-primary/60 focus:ring-2 focus:ring-primary/20'
                                        ]" />
                                    <p v-if="form.errors.owner_email" class="mt-1 text-xs text-red-600">{{
                                        form.errors.owner_email }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                        Phone <span class="text-xs text-red-600 font-medium">(Required)</span>
                                    </label>
                                    <input v-model="form.owner_phone" type="tel" placeholder="03XX-XXXXXXX" :class="[
                                        'w-full px-4 py-2.5 rounded-xl border text-sm outline-none transition',
                                        form.errors.owner_phone
                                            ? 'border-red-400 bg-red-50'
                                            : 'border-slate-200 focus:border-primary/60 focus:ring-2 focus:ring-primary/20'
                                    ]" />
                                    <p v-if="form.errors.owner_phone" class="mt-1 text-xs text-red-600">{{
                                        form.errors.owner_phone }}</p>
                                </div>
                            </div>

                            <!-- NEW: CNIC field (full width) -->
                            <div class="mt-4">
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                    CNIC <span class="text-xs text-red-600 font-medium">(Required)</span>
                                </label>
                                <input v-model="form.owner_cnic" type="text" placeholder="e.g. 12345-6789012-3" :class="[
                                    'w-full px-4 py-2.5 rounded-xl border text-sm outline-none transition',
                                    form.errors.owner_cnic
                                        ? 'border-red-400 bg-red-50'
                                        : 'border-slate-200 focus:border-primary/60 focus:ring-2 focus:ring-primary/20'
                                ]" />
                                <p v-if="form.errors.owner_cnic" class="mt-1 text-xs text-red-600">
                                    {{ form.errors.owner_cnic }}
                                </p>
                            </div>

                            <!-- Password -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                        Password <span class="text-xs text-red-600 font-medium">(Required)</span>
                                    </label>
                                    <div class="relative">
                                        <input v-model="form.owner_password" :type="showPassword ? 'text' : 'password'"
                                            placeholder="Min. 8 characters" :class="[
                                                'w-full px-4 py-2.5 pr-10 rounded-xl border text-sm outline-none transition',
                                                form.errors.owner_password
                                                    ? 'border-red-400 bg-red-50'
                                                    : 'border-slate-200 focus:border-primary/60 focus:ring-2 focus:ring-primary/20'
                                            ]" />
                                        <button type="button" @click="showPassword = !showPassword"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                            <svg v-if="!showPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Password strength -->
                                    <div v-if="form.owner_password" class="mt-2">
                                        <div class="flex gap-1 mb-1">
                                            <div v-for="i in 4" :key="i"
                                                :class="['h-1 flex-1 rounded-full transition-all', i <= passwordStrength.score ? passwordStrength.color : 'bg-slate-200']">
                                            </div>
                                        </div>
                                        <p class="text-xs text-slate-500">{{ passwordStrength.label }}</p>
                                    </div>
                                    <p v-if="form.errors.owner_password" class="mt-1 text-xs text-red-600">{{
                                        form.errors.owner_password }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                        Confirm Password <span
                                            class="text-xs text-red-600 font-medium">(Required)</span>
                                    </label>
                                    <div class="relative">
                                        <input v-model="form.owner_password_confirmation"
                                            :type="showConfirm ? 'text' : 'password'" placeholder="Repeat password"
                                            :class="[
                                                'w-full px-4 py-2.5 pr-10 rounded-xl border text-sm outline-none transition',
                                                form.errors.owner_password_confirmation
                                                    ? 'border-red-400 bg-red-50'
                                                    : 'border-slate-200 focus:border-primary/60 focus:ring-2 focus:ring-primary/20'
                                            ]" />
                                        <button type="button" @click="showConfirm = !showConfirm"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                            <svg v-if="!showConfirm" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                            </svg>
                                        </button>
                                    </div>
                                    <p v-if="form.errors.owner_password_confirmation" class="mt-1 text-xs text-red-600">
                                        {{ form.errors.owner_password_confirmation }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN — Logo + Submit -->
                <div class="space-y-6">

                    <!-- Logo Upload -->
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-2">
                            <span class="text-lg">🖼️</span>
                            <h2 class="font-semibold text-slate-900">Company Logo</h2>
                        </div>
                        <div class="p-6">
                            <!-- Preview -->
                            <div class="flex flex-col items-center gap-4">
                                <div
                                    class="w-28 h-28 rounded-2xl border-2 border-dashed border-slate-200 flex items-center justify-center overflow-hidden bg-slate-50">
                                    <img v-if="logoPreview" :src="logoPreview"
                                        class="w-full h-full object-cover rounded-2xl" />
                                    <div v-else class="text-center">
                                        <span class="text-3xl">{{ typeLabels[form.company_type]?.icon ?? '🏢' }}</span>
                                        <p class="text-xs text-slate-400 mt-1">No logo</p>
                                    </div>
                                </div>

                                <label class="cursor-pointer w-full">
                                    <div
                                        class="w-full py-2.5 px-4 rounded-xl border border-slate-200 text-center text-sm text-slate-600 hover:bg-slate-50 transition">
                                        {{ logoPreview ? "Change Logo" : "Upload Logo" }}
                                    </div>
                                    <input type="file" class="hidden" accept="image/*" @change="handleLogoChange" />
                                </label>

                                <button v-if="logoPreview" type="button" @click="removeLogo"
                                    class="text-xs text-red-500 hover:text-red-700 transition">
                                    Remove logo
                                </button>

                                <p v-if="form.errors.company_logo" class="text-xs text-red-600">{{
                                    form.errors.company_logo }}</p>
                                <p class="text-xs text-slate-400 text-center">PNG, JPG, WEBP up to 2MB</p>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Card -->
                    <div class="bg-slate-50 rounded-xl border border-slate-200 p-5 space-y-3 text-sm">
                        <h3 class="font-semibold text-slate-700 text-xs uppercase tracking-wide">Summary</h3>
                        <div class="space-y-2 text-slate-600">
                            <div class="flex justify-between">
                                <span>From request</span>
                                <span class="font-medium text-slate-800">#{{ partnerRequest.id }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Company</span>
                                <span class="font-medium text-slate-800 text-right max-w-32 truncate">{{
                                    form.company_name || "—" }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Type</span>
                                <span class="font-medium text-slate-800">{{ typeLabels[form.company_type]?.icon }} {{
                                    typeLabels[form.company_type]?.label || "—" }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Owner</span>
                                <span class="font-medium text-slate-800 text-right max-w-32 truncate">{{
                                    form.owner_full_name || "—" }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Status</span>
                                <span :class="['font-semibold', form.is_active ? 'text-green-600' : 'text-slate-500']">
                                    {{ form.is_active ? "Active" : "Inactive" }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" :disabled="form.processing"
                        class="w-full py-3 rounded-xl bg-primary text-white font-semibold text-sm shadow-lg hover:-translate-y-0.5 hover:shadow-xl transition disabled:opacity-60 disabled:cursor-not-allowed disabled:transform-none flex items-center justify-center gap-2">
                        <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        {{ form.processing ? "Creating..." : "Create Company & Owner Account" }}
                    </button>

                    <Link :href="route('admin.partner-requests.index')"
                        class="block text-center text-sm text-slate-500 hover:text-slate-700 transition">
                        Cancel
                    </Link>
                </div>
            </div>
        </form>
    </AdminLayout>
</template>
