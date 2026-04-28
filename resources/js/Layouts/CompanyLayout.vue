<script setup>
import { ref, computed } from "vue";
import { onMounted } from 'vue';
import { Head } from "@inertiajs/vue3";
import Sidebar from "@/Components/CompanyPage/Sidebar.vue";
import { useCompanyDataStore } from '@/stores/companyData';
import { useUserStore } from "@/stores/userData";
import Toast from '@/Components/Toast.vue'

const companyData = useCompanyDataStore();
const userData = useUserStore()

onMounted(() => {
    // Fetch data when the component mounts
    companyData.fetchCompanyData();
    userData.fetchUser();
});

const props = defineProps({
    user: Object,
    company: Object,
});

// Get the correct role label for the logged‑in user
const userRoleLabel = computed(() => {
    if (userData.isCompanyOwner) return "Company Owner";
    if (userData.isCompanyUser) return "Company User";
    if (userData.isSuperAdmin) return "Super Admin";
    return "User";
});

const sidebarOpen = ref(false);

function toggleSidebar() {
    sidebarOpen.value = !sidebarOpen.value;
}
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <Sidebar :user="props.user" :company="props.company" :sidebarOpen="sidebarOpen"
            @close-sidebar="sidebarOpen = false" />

        <div class="flex flex-col flex-1 max-w-[-webkit-fill-available] xl:ml-[256px]">

            <Head title="Company Dashboard" />

            <!-- Top Bar -->
            <header
                class="sticky top-0 z-[40] flex items-center justify-between px-6 py-4 bg-white border-b border-slate-200">
                <div class="flex items-center">
                    <button class="px-2 py-1 mr-3 text-white rounded-md bg-primary xl:hidden"
                        @click="sidebarOpen = true">
                        ☰
                    </button>
                    <div>
                        <h1 class="text-lg font-bold text-slate-800">Dashboard</h1>
                        <p class="text-xs text-slate-500">
                            Welcome back, {{ props.user?.name }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2 px-3 py-1.5 text-xs font-medium rounded-full"
                    :class="props.user?.userType === 'CompanyOwner' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'">
                    {{ props.user?.userType === 'CompanyOwner' ? "Company Owner" : "Company User" }}
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-8 min-h-screen bg-gray-50">
                <slot />
            </main>
            <Toast />
        </div>
    </div>
</template>
