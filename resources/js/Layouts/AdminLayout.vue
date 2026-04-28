<script setup>
import { ref } from "vue";
import { Link } from "@inertiajs/vue3";
import Sidebar from "@/Components/AdminPage/Sidebar.vue";
import Toast from '@/Components/Toast.vue'

defineProps({
    title: {
        type: String,
        default: "Admin Panel"
    }
});

const sidebarOpen = ref(false);
</script>

<template>
    <div class="flex min-h-screen bg-gray-100">
        <!-- Sidebar -->
        <Sidebar :title="title" :sidebarOpen="sidebarOpen" @close-sidebar="sidebarOpen = false" />

        <!-- Main Content -->
        <div class="flex flex-col flex-1 max-w-[-webkit-fill-available] xl:ml-[256px]">
            <!-- Topbar -->
            <header
                class="flex items-center justify-between px-4 bg-white border-b h-14 fixed w-[-webkit-fill-available] z-[40] shadow-sm">
                <div class="flex items-center justify-center">
                    <button class="px-2 py-1 text-white rounded-md bg-primary xl:hidden" @click="sidebarOpen = true">
                        ☰
                    </button>
                    <span class="block ml-2 text-xl font-semibold xl:hidden text-primary">
                        {{ title }}
                    </span>
                </div>

                <div class="flex items-center gap-3 ml-auto">
                    <span class="px-3 py-1.5 text-sm text-white rounded-md bg-primary">
                        Super Admin
                    </span>

                    <a href="/" method="post" as="button"
                        class="px-3 py-1.5 text-sm border rounded-md text-white bg-secondary">
                        Back To Home
                    </a>
                    <Link :href="route('logout')" method="post" as="button"
                        class="px-3 py-1.5 text-sm border rounded-md">
                        Logout
                    </Link>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-6 mt-[56px]">
                <slot />
            </main>
        </div>
    </div>

    <Toast />
</template>
