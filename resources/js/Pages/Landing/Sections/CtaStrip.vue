<template>
    <section class="mx-auto max-w-6xl px-4 py-12 sm:py-16">
        <div
            class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-primary to-slate-900 p-8 shadow-soft sm:p-10">
            <div class="absolute -right-24 -top-24 h-64 w-64 rounded-full bg-secondary/25 blur-3xl"></div>
            <div class="absolute -bottom-24 -left-24 h-64 w-64 rounded-full bg-accent/25 blur-3xl"></div>

            <div class="relative z-10 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-xl font-semibold text-white sm:text-2xl">
                        Ready to book your next trip?
                    </h3>
                    <p class="mt-1 text-white/80">
                        Search routes, compare times, and reserve seats with a
                        modern experience.
                    </p>
                </div>

                <div class="flex gap-3">
                    <a href="#search"
                        class="inline-flex items-center justify-center rounded-xl bg-amber-300 px-4 py-3 text-sm font-bold text-primary transition hover:-translate-y-0.5">
                        Search Now
                    </a>
                    <a href="#bus-types"
                        class="inline-flex items-center justify-center rounded-xl border border-white/25 bg-white/10 px-4 py-3 text-sm font-semibold text-white backdrop-blur transition hover:-translate-y-0.5 hover:bg-white/15">
                        View Bus Types
                    </a>
                </div>
            </div>
        </div>
    </section>
</template>
