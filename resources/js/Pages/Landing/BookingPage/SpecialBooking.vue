<script setup>
import { Head, Link, router, usePage } from "@inertiajs/vue3";
</script>
<template>
    <section class="mt-4 md:mt-6">
        <div
            class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-primary to-slate-900 p-8 shadow-soft sm:p-10">
            <div class="absolute -right-24 -top-24 h-64 w-64 rounded-full bg-secondary/25 blur-3xl"></div>
            <div class="absolute -bottom-24 -left-24 h-64 w-64 rounded-full bg-accent/25 blur-3xl"></div>

            <div class="relative z-10 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <span
                        class="mb-2 inline-block rounded-full border border-secondary bg-secondary px-3 py-0.5 text-xs font-semibold uppercase tracking-widest text-primary">
                        Charter & Private Hire
                    </span>
                    <h3 class="mt-2 text-xl font-semibold text-white sm:text-2xl">
                        Need a Special Bus Booking?
                    </h3>
                    <p class="mt-1 text-white/80">
                        Plan private group travel, corporate trips, weddings or tours.
                        Our team will contact you within 2 hours.
                    </p>
                </div>

                <div class="flex-shrink-0">
                    <a href="/special-booking"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-secondary px-5 py-3 text-sm font-bold text-primary transition hover:-translate-y-0.5">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 5H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                        Book a Special Bus
                    </a>
                </div>
            </div>
        </div>
    </section>
</template>
