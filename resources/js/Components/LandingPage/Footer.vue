<script setup>
const year = new Date().getFullYear();
</script>

<template>
    <footer id="footer" class="relative text-white overflow-hidden">
        <!-- Footer background -->
        <div class="absolute inset-0 z-0 overflow-hidden">
            <!-- Main gradient -->
            <div class="absolute inset-0 bg-gradient-to-br from-primary via-primary to-slate-900"></div>

            <!-- Glow elements -->
            <div class="absolute -top-40 -left-32 h-96 w-96 rounded-full bg-secondary/25 blur-3xl"></div>
            <div class="absolute -bottom-20 -right-32 h-96 w-96 rounded-full bg-accent/25 blur-3xl"></div>

            <!-- Dot pattern -->
            <div
                class="absolute inset-0 opacity-20"
                style="
                    background-image: radial-gradient(
                        circle at 1px 1px,
                        rgba(255, 255, 255, 0.12) 1px,
                        transparent 0
                    );
                    background-size: 22px 22px;
                "
            ></div>
        </div>
        <div class="max-w-6xl px-4 py-12 mx-auto relative z-10">
            <div class="grid gap-10 lg:grid-cols-12">
                <div class="lg:col-span-4">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex items-center justify-center w-10 h-10 rounded-xl bg-white/10 ring-1 ring-white/10">
                            <svg class="w-8 h-8 text-white" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M6 16V7.8c0-1.7 1.3-3.1 3-3.3h6c1.7.2 3 1.6 3 3.3V16" stroke="currentColor"
                                    stroke-width="1.6" stroke-linecap="round" />
                                <path d="M6 12h12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                                <path d="M8 16h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                                <path
                                    d="M7 19a1.8 1.8 0 1 0 3.6 0A1.8 1.8 0 0 0 7 19ZM13.4 19a1.8 1.8 0 1 0 3.6 0 1.8 1.8 0 0 0-3.6 0Z"
                                    fill="currentColor" />
                            </svg>
                        </div>
                        <!-- <div class="hidden w-12 h-12 p-1 rounded-xl bg-white/10 ring-1 ring-white/15">
                            <img src="@img/icon2.png" alt="Royal Bus Logo" class="object-contain" />
                        </div> -->

                        <div class="text-left">
                            <p class="text-base font-semibold text-white">
                                Safar
                            </p>
                            <p class="-mt-0.5 text-sm text-white/70">
                                Safar with us
                            </p>
                        </div>
                    </div>

                    <p class="max-w-sm mt-4 text-sm text-white/70">
                        A modern bus ticket booking experience with premium
                        coach options, clear schedules, and responsive UI.
                    </p>

                    <div class="flex items-center gap-3 mt-5">
                        <a class="social" href="#" aria-label="Facebook">
                            <svg viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                                <path
                                    d="M22 12a10 10 0 1 0-11.56 9.87v-6.99H7.9V12h2.54V9.8c0-2.5 1.5-3.89 3.78-3.89 1.1 0 2.25.2 2.25.2v2.46h-1.27c-1.25 0-1.64.78-1.64 1.58V12h2.8l-.45 2.88h-2.35v6.99A10 10 0 0 0 22 12z" />
                            </svg>
                        </a>
                        <a class="social" href="#" aria-label="Twitter">
                            <svg viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                                <path
                                    d="M18.9 2H22l-6.8 7.8L23.2 22h-6.7l-5.3-6.5L5.7 22H2.5l7.3-8.4L1 2h6.9l4.8 5.8L18.9 2zm-1.2 18h1.7L6.1 3.9H4.3L17.7 20z" />
                            </svg>
                        </a>
                        <a class="social" href="#" aria-label="Instagram">
                            <svg viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                                <path
                                    d="M7 2C4.2 2 2 4.2 2 7v10c0 2.8 2.2 5 5 5h10c2.8 0 5-2.2 5-5V7c0-2.8-2.2-5-5-5H7zm10 2a3 3 0 0 1 3 3v10a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h10zm-5 3.5A4.5 4.5 0 1 0 16.5 12 4.51 4.51 0 0 0 12 7.5zm0 7.3A2.8 2.8 0 1 1 14.8 12 2.8 2.8 0 0 1 12 14.8zM17.6 6.2a1 1 0 1 0 1 1 1 1 0 0 0-1-1z" />
                            </svg>
                        </a>
                        <a class="social" href="#" aria-label="YouTube">
                            <svg viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                                <path
                                    d="M21.6 7.2a3 3 0 0 0-2.1-2.1C17.8 4.6 12 4.6 12 4.6s-5.8 0-7.5.5A3 3 0 0 0 2.4 7.2 31.2 31.2 0 0 0 2 12a31.2 31.2 0 0 0 .4 4.8 3 3 0 0 0 2.1 2.1c1.7.5 7.5.5 7.5.5s5.8 0 7.5-.5a3 3 0 0 0 2.1-2.1A31.2 31.2 0 0 0 22 12a31.2 31.2 0 0 0-.4-4.8zM10 15.5v-7l6 3.5-6 3.5z" />
                            </svg>
                        </a>
                    </div>
                </div>

                <div class="lg:col-span-8">
                    <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <p class="text-sm font-semibold">Navbar Links</p>
                            <ul class="mt-3 space-y-2 text-sm text-white/70">
                                <li>
                                    <a class="foot-link" href="#home">Home</a>
                                </li>
                                <li>
                                    <a class="foot-link" href="#search">Search</a>
                                </li>
                                <li>
                                    <a class="foot-link" href="#bus-types">Bus Types</a>
                                </li>
                                <li>
                                    <a class="foot-link" href="#routes">Top Routes</a>
                                </li>
                            </ul>
                        </div>

                        <div>
                            <p class="text-sm font-semibold">Sections</p>
                            <ul class="mt-3 space-y-2 text-sm text-white/70">
                                <li>
                                    <a class="foot-link" href="#search">Search Form</a>
                                </li>
                                <li>
                                    <a class="foot-link" href="#bus-types">Bus Slider</a>
                                </li>
                                <li>
                                    <a class="foot-link" href="#routes">Routes Slider</a>
                                </li>
                                <li>
                                    <a class="foot-link" href="#footer">Footer</a>
                                </li>
                            </ul>
                        </div>

                        <div>
                            <p class="text-sm font-semibold">Quick Links</p>
                            <ul class="mt-3 space-y-2 text-sm text-white/70">
                                <li>
                                    <a class="foot-link" href="#">Offers</a>
                                </li>
                                <li><a class="foot-link" href="#">Terms</a></li>
                                <li>
                                    <a class="foot-link" href="#">Privacy</a>
                                </li>
                                <li>
                                    <a class="foot-link" href="#">Company</a>
                                </li>
                            </ul>
                        </div>

                        <div>
                            <p class="text-sm font-semibold">Support</p>
                            <ul class="mt-3 space-y-2 text-sm text-white/70">
                                <li>
                                    <a class="foot-link" href="#">Help Center</a>
                                </li>
                                <li>
                                    <a class="foot-link" href="#">Contact</a>
                                </li>
                                <li>
                                    <a class="foot-link" href="#">Refund Policy</a>
                                </li>
                                <li>
                                    <a class="foot-link" href="#">Report Issue</a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="p-5 mt-10 rounded-2xl bg-white/5 ring-1 ring-white/10">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-sm text-white/70">
                                Get travel updates & offers
                            </p>
                            <form class="flex w-full gap-2 sm:w-auto">
                                <input
                                    class="w-full px-4 py-2 text-sm text-white border outline-none rounded-xl border-white/10 bg-white/10 placeholder:text-white/40 focus:ring-2 focus:ring-secondary/60"
                                    placeholder="Email address" />
                                <button type="button"
                                    class="rounded-xl bg-secondary px-4 py-2 text-sm font-semibold text-white transition hover:-translate-y-0.5">
                                    Subscribe
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-6 mt-10 text-xs border-t border-white/10 text-white/60">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <p>© {{ year }} Royal Bus. All rights reserved.</p>
                    <p>Built with Laravel + Inertia + Vue + Tailwind</p>
                </div>
            </div>
        </div>
    </footer>
</template>

<style scoped>
.foot-link {
    transition: color 160ms ease;
}

.foot-link:hover {
    color: white;
}

.social {
    display: inline-flex;
    height: 2.25rem;
    width: 2.25rem;
    align-items: center;
    justify-content: center;
    border-radius: 0.9rem;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.85);
    transition: transform 160ms ease, background 160ms ease;
}

.social:hover {
    transform: translateY(-1px);
    background: rgba(255, 255, 255, 0.12);
    color: white;
}
</style>
