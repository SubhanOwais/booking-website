<script setup>
import ApplicationLogo from "@/Components/LandingPage/ApplicationLogo.vue";
import { Link } from "@inertiajs/vue3";
import Toast from '@/Components/Toast.vue'
</script>

<template>
    <slot />
    <Toast />
</template>
