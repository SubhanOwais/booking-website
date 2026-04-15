<script setup>
import { computed, onMounted } from "vue";
import { router, Link, usePage } from "@inertiajs/vue3";
import { useCompanyDataStore } from "@/stores/companyData";
import axios from 'axios'; // only if you plan to fetch data directly

const props = defineProps({
    user: Object,
    company: Object,
    sidebarOpen: Boolean
});

const store = useCompanyDataStore();

// If the parent layout passes company and user props, you can initialize the store with them
onMounted(() => {
    if (props.company && props.user) {
        // The company object might already have the owner inside it (as in your data example)
        // We assume props.company includes 'owner' and 'users'
        store.setInitialData({
            company: props.company,
            owner: props.company?.owner || null,
            users: props.company?.users || []
        });
    } else {
        // Optionally fetch fresh data if not provided via props
        store.fetchCompanyData();
    }
});

const isOwner = computed(() => props.user?.userType === "CompanyOwner");

function logout() {
    router.post(route("logout"));
}

// Get current page URL
const page = usePage();
const currentUrl = computed(() => page.url);

// Helper to check if a route is active
const isActive = (name) => {
    return route().current(name);
};
</script>


<template>
    <aside :class="[
        'fixed inset-y-0 flex flex-col left-0 z-[50] w-64 bg-white border-r transform transition-transform duration-200 xl:fixed shadow-sm',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full',
        'xl:translate-x-0 xl:static',
    ]">
        <!-- Logo -->
        <div class="flex relative items-center gap-3 px-6 py-5 border-b border-slate-100">
            <!-- Company logo (if available) -->
            <img v-if="store.company?.logo_url" :src="store.company.logo_url" class="w-9 h-auto rounded object-cover"
                alt="logo">
            <div v-else class="flex items-center justify-center w-9 h-9 rounded-xl bg-primary">

                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            <div>
                <p class="text-xs text-slate-500">Company Panel</p>
                <p class="text-sm font-bold text-slate-800 truncate max-w-[140px]">
                    {{ store.company?.company_name ?? props.company?.name ?? "My Company" }}
                </p>
            </div>
            <button class="px-2 py-1 absolute top-1 right-1 text-white rounded-md bg-primary xl:hidden"
                @click="$emit('close-sidebar')">✕</button>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
            <Link :href="route('company.dashboard')" :class="[
                'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm cursor-pointer',
                isActive('company.dashboard')
                    ? 'bg-primary/10 text-primary font-semibold'
                    : 'text-slate-600 hover:bg-slate-100'
            ]">
                <!-- Dashboard Icon -->
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Dashboard
            </Link>

            <!-- Other links -->
            <Link :href="route('company.ticketing.index')" :class="[
                'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm cursor-pointer',
                isActive('company.ticketing.index')
                    ? 'bg-primary/10 text-primary font-semibold'
                    : 'text-slate-600 hover:bg-slate-100'
            ]">
                <i class="bi bi-ticket-detailed text-lg"></i>
                Ticketing
            </Link>

            <Link :href="route('company.discount.index')" :class="[
                'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm cursor-pointer',
                isActive('company.discount.index')
                    ? 'bg-primary/10 text-primary font-semibold'
                    : 'text-slate-600 hover:bg-slate-100'
            ]">
                <i class="bi bi-coin text-lg"></i>
                Discounts
            </Link>

            <Link :href="route('company.cities.index')" :class="[
                'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm cursor-pointer',
                isActive('company.cities.index')
                    ? 'bg-primary/10 text-primary font-semibold'
                    : 'text-slate-600 hover:bg-slate-100'
            ]">
                <i class="bi bi-building-add text-lg"></i>
                Company Cities
            </Link>

            <Link :href="route('company.users.index')" :class="[
                'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm cursor-pointer',
                isActive('company.users.index')
                    ? 'bg-primary/10 text-primary font-semibold'
                    : 'text-slate-600 hover:bg-slate-100'
            ]">
                <i class="bi bi-people text-lg"></i>
                User Managment
            </Link>

            <Link :href="route('company.roles.index')" :class="[
                'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm cursor-pointer',
                isActive('company.roles.index')
                    ? 'bg-primary/10 text-primary font-semibold'
                    : 'text-slate-600 hover:bg-slate-100'
            ]">
                <i class="bi bi-shield-lock text-lg"></i>
                Roles Managment
            </Link>

            <a href="#" :class="[
                'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm cursor-pointer',
                isActive('#')
                    ? 'bg-primary/10 text-primary font-semibold'
                    : 'text-slate-600 hover:bg-slate-100'
            ]">
                Schedules
            </a>

            <a href="#" :class="[
                'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm cursor-pointer',
                isActive('#')
                    ? 'bg-primary/10 text-primary font-semibold'
                    : 'text-slate-600 hover:bg-slate-100'
            ]">
                Passengers
            </a>

            <a href="#" :class="[
                'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm cursor-pointer',
                isActive('#')
                    ? 'bg-primary/10 text-primary font-semibold'
                    : 'text-slate-600 hover:bg-slate-100'
            ]">
                <i class="bi bi-gear text-lg"></i>
                Settings
            </a>
        </nav>

        <!-- User info + logout -->
        <div class="px-4 py-4 mt-auto border-t border-slate-100">
            <div class="flex items-center gap-3 mb-3">
                <div
                    class="flex items-center justify-center w-9 h-9 rounded-full bg-primary/10 text-primary font-bold text-sm">
                    {{ props.user?.name?.charAt(0) ?? store.owner?.Full_Name?.charAt(0) ?? "U" }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-slate-800 truncate">
                        {{ props.user?.name ?? store.owner?.Full_Name ?? "User" }}
                    </p>
                    <p class="text-xs text-slate-500 truncate">
                        {{ store.owner ? "Company Owner" : "User" }}
                    </p>
                </div>
            </div>
            <hr class="my-3 border-slate-100" />
            <button @click="logout"
                class="flex items-center justify-center w-full gap-2 px-3 py-2 text-sm font-medium text-red-600 transition rounded-xl bg-red-50 hover:bg-red-100">
                Logout
            </button>
        </div>
    </aside>
</template>
