<script setup>
import { Head } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, nextTick } from 'vue';

import HeroSearch from '@/Pages/Landing/Sections/HeroSearch.vue';
import BusTypesSection from '@/Pages/Landing/Sections/BusTypesSection.vue';
import TopRoutesSection from '@/Pages/Landing/Sections/TopRoutesSection.vue';
import CtaStrip from '@/Pages/Landing/Sections/CtaStrip.vue';
import WebLayout from "@/Layouts/WebLayout.vue";
import DiscountOffer from '@/Pages/Landing/Sections/DiscountOffer.vue'

function scrollToTarget() {
    const fromSession = (() => {
        try {
            return sessionStorage.getItem('royalBusScrollTo') || '';
        } catch {
            return '';
        }
    })();

    const fromHash = (window.location.hash || '').replace('#', '');
    const id = fromSession || fromHash;
    if (!id) return;

    // Clear stored value so it doesn't re-scroll unexpectedly.
    try {
        sessionStorage.removeItem('royalBusScrollTo');
    } catch {
        // ignore
    }

    nextTick(() => {
        // Small delay to ensure sections are rendered.
        setTimeout(() => {
            const el = document.getElementById(id);
            el?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 80);
    });
}

function onHashChange() {
    scrollToTarget();
}

onMounted(() => {
    scrollToTarget();
    window.addEventListener('hashchange', onHashChange);
});

onBeforeUnmount(() => {
    window.removeEventListener('hashchange', onHashChange);
});
</script>

<template>
    <WebLayout class="bg-slate-50 text-ink">
        <HeroSearch />
        <DiscountOffer />
        <BusTypesSection />
        <TopRoutesSection />
        <CtaStrip />
    </WebLayout>
</template>
