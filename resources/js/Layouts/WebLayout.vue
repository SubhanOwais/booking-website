<script setup>
import { ref } from "vue";
import { Link } from "@inertiajs/vue3";
import LandingNavbar from '@/Components/LandingPage/Navbar.vue';
import LandingFooter from '@/Components/LandingPage/Footer.vue';
</script>

<template>
    <div class="">
        <LandingNavbar />
        <!-- Page Content -->
        <main>
            <slot />
        </main>
        <LandingFooter />
    </div>
</template>
