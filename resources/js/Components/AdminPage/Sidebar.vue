<template>
    <aside :class="[
        'fixed inset-y-0 left-0 z-[50] w-64 bg-white border-r transform transition-transform duration-200 xl:fixed shadow-sm',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full',
        'xl:translate-x-0 xl:static',
    ]">
        <div class="flex items-center justify-between px-4 border-b h-14">
            <span class="text-lg font-bold text-primary">
                {{ title }}
            </span>
            <button class="px-2 py-1 text-white rounded-md bg-primary xl:hidden" @click="$emit('close-sidebar')">
                ✕
            </button>
        </div>

        <nav class="px-4 py-4 space-y-2 text-sm">
            <Link :href="route('admin.dashboard')" class="block px-3 py-2 rounded-md hover:bg-indigo-50">
                📊 Dashboard
            </Link>

            <Link :href="route('admin.cities')" class="block px-3 py-2 rounded-md hover:bg-indigo-50">
                🏙️ City Management
            </Link>

            <Link :href="route('admin.city-mapping.index')" class="block px-3 py-2 rounded-md hover:bg-indigo-50">
                🔗 City Mapping
            </Link>

            <Link href="#" class="block px-3 py-2 rounded-md hover:bg-indigo-50">
                👤 Users
            </Link>

            <Link :href="route('admin.ticketing.index')" class="block px-3 py-2 rounded-md hover:bg-indigo-50">
                🎫 Ticketing History
            </Link>

            <Link :href="route('admin.refund.index')" class="block px-3 py-2 rounded-md hover:bg-indigo-50">
                💰 Refund Tickets
            </Link>

            <!-- <Link :href="route('admin.discount.index')" class="block px-3 py-2 rounded-md hover:bg-indigo-50">
                🏷️ Discount
            </Link> -->

            <Link :href="route('admin.Gallery.index')" class="block px-3 py-2 rounded-md hover:bg-indigo-50">
                📷 Gallery
            </Link>

            <Link :href="route('admin.partner-requests.index')" :class="[
                'flex items-center gap-2 px-3 py-2 rounded-md transition text-sm font-medium',
                $page.url.startsWith('/admin/partner-requests')
                    ? 'bg-indigo-100 text-indigo-700 font-semibold'
                    : 'hover:bg-indigo-50 text-gray-700'
            ]">
                🤝 Partner Requests
            </Link>

            <Link :href="route('admin.companies.index')"
                :class="[$page.url.startsWith('/admin/companies') ? 'bg-indigo-100 text-indigo-700 font-semibold' : 'hover:bg-indigo-50 text-gray-700', 'flex items-center gap-2 px-3 py-2 rounded-md transition text-sm font-medium']">
                🏢 Companies
            </Link>

            <Link :href="route('admin.websites.index')" class="block px-3 py-2 rounded-md hover:bg-indigo-50">
                🌍 websites
            </Link>

            <Link href="#" class="block px-3 py-2 rounded-md hover:bg-indigo-50">
                ⚙️ Settings
            </Link>
        </nav>
    </aside>
</template>
<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
    title: String,
    sidebarOpen: Boolean
});
</script>
