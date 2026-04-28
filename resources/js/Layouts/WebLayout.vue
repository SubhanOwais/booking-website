<script setup>
import { ref } from "vue";
import { Link } from "@inertiajs/vue3";
import LandingNavbar from '@/Components/LandingPage/Navbar.vue';
import LandingFooter from '@/Components/LandingPage/Footer.vue';
import Toast from '@/Components/Toast.vue'
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
    <Toast />
</template>
