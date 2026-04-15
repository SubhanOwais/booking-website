<script setup>
import { Link, router, usePage } from "@inertiajs/vue3";
import { computed, nextTick, ref, onMounted, onUnmounted } from "vue";
import { useCityStore } from "@/stores/cityStore";
import { useDiscountStore } from "@/stores/discountStore";


const cityStore = useCityStore();
const discountStore = useDiscountStore();

// ✅ Fetch only once (persist handles reload)
onMounted(() => {
    discountStore.fetchDiscounts();
});

const isNavOpen = ref(false);
const page = usePage();

const isHome = computed(
    () => page.component === "Home" || page.component?.value === "Home"
);

function openNav() {
    isNavOpen.value = true;
    // Prevent body scroll when drawer is open
    document.body.style.overflow = 'hidden';
}

function closeNav() {
    isNavOpen.value = false;
    // Restore body scroll
    document.body.style.overflow = '';
}

// Close drawer on escape key press
onMounted(() => {
    const handleEscape = (e) => {
        if (e.key === 'Escape' && isNavOpen.value) {
            closeNav();
        }
    };

    window.addEventListener('keydown', handleEscape);

    onUnmounted(() => {
        window.removeEventListener('keydown', handleEscape);
        document.body.style.overflow = ''; // Clean up on unmount
    });
});

function scrollToSection(id) {
    if (!id) return;
    const el = document.getElementById(id);
    if (!el) return;
    el.scrollIntoView({ behavior: "smooth", block: "start" });
}

function goSection(id) {
    closeNav();

    if (isHome.value) {
        // Keep URL hash in sync and scroll.
        window.location.hash = `#${id}`;
        nextTick(() => scrollToSection(id));
        return;
    }

    // If not on home, first go to '/', then auto-scroll to section.
    try {
        sessionStorage.setItem("royalBusScrollTo", id);
    } catch {
        // ignore
    }

    router.visit(route("home"));
}
</script>

<template>
    <nav
        class="sticky top-0 z-50 border-b border-white/10 bg-primary/70 backdrop-blur supports-[backdrop-filter]:bg-primary/55">
        <div class="flex items-center justify-between max-w-6xl px-4 py-3 mx-auto">
            <button type="button" class="flex items-center gap-2" @click="goSection('home')">
                <div class="flex items-center justify-center h-9 w-9 rounded-xl bg-white/10 ring-1 ring-white/15">
                    <svg class="w-7 h-7 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
                        aria-hidden="true">
                        <path d="M6 16V7.8c0-1.7 1.3-3.1 3-3.3h6c1.7.2 3 1.6 3 3.3V16" stroke="currentColor"
                            stroke-width="1.6" stroke-linecap="round" />
                        <path d="M6 12h12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                        <path d="M8 16h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                        <path
                            d="M7 19a1.8 1.8 0 1 0 3.6 0A1.8 1.8 0 0 0 7 19ZM13.4 19a1.8 1.8 0 1 0 3.6 0 1.8 1.8 0 0 0-3.6 0Z"
                            fill="currentColor" />
                    </svg>
                </div>
                <!-- <div class="rounded-xl bg-white/10 ring-1 ring-white/15 p-[2px] hidden">
                    <img src="@img/logo.png" alt="Royal Bus Logo" class="object-contain rounded-full h-9 w-9" />
                </div> -->

                <div class="text-left">
                    <p class="text-sm font-semibold text-white">Safar</p>
                    <p class="-mt-0.5 text-xs text-white/70">
                        Safar with us
                    </p>
                </div>
            </button>

            <div class="items-center hidden gap-2 md:flex">
                <button type="button" class="nav-link" @click="goSection('home')">
                    Home
                </button>
                <Link class="nav-link" :href="route('booking')">Booking</Link>
                <button type="button" class="nav-link" @click="goSection('bus-types')">
                    Bus Types
                </button>
                <button type="button" class="nav-link" @click="goSection('routes')">
                    Top Routes
                </button>
                <Link class="nav-link" :href="route('gallery')">Gallery</Link>
                <button type="button" class="nav-link" @click="goSection('footer')">
                    Contact
                </button>
            </div>

            <div class="flex items-center gap-2">
                <Link :href="route('booking')"
                    class="hidden rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white shadow-soft transition hover:-translate-y-0.5 hover:shadow-card sm:inline-flex">
                    Book Now
                </Link>

                <div v-if="$page.props.auth?.user">
                    <!-- Show Dashboard for SuperAdmin -->
                    <Link v-if="$page.props.auth.user.IsSuperAdmin" :href="route('admin.dashboard')"
                        class="rounded-xl bg-secondary px-4 py-2 text-sm font-semibold text-white shadow-soft transition hover:-translate-y-0.5 hover:shadow-card sm:inline-flex">
                        Dashboard
                    </Link>

                    <!-- Show Profile for WebCustomer -->
                    <Link v-else-if="
                        $page.props.auth.user.User_Type === 'WebCustomer'
                    " :href="route('profile.index')"
                        class="rounded-xl bg-secondary px-4 py-2 text-sm font-semibold text-white shadow-soft transition hover:-translate-y-0.5 hover:shadow-card sm:inline-flex">
                        Profile
                    </Link>
                </div>

                <!-- Show Login for guests -->
                <Link v-else :href="route('login')"
                    class="rounded-xl bg-secondary px-4 py-2 text-sm font-semibold text-white shadow-soft transition hover:-translate-y-0.5 hover:shadow-card sm:inline-flex">
                    Login
                </Link>

                <!-- Mobile menu button -->
                <button type="button"
                    class="inline-flex items-center justify-center w-10 h-10 text-white rounded-xl bg-white/10 ring-1 ring-white/15 md:hidden"
                    aria-label="Open menu" @click="openNav">
                    <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M3 5h14a1 1 0 010 2H3a1 1 0 010-2zm0 4h14a1 1 0 010 2H3a1 1 0 010-2zm0 4h14a1 1 0 010 2H3a1 1 0 010-2z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>
    </nav>
    <!-- Navbar drawer -->
    <div v-if="isNavOpen" class="fixed inset-0 z-[9999999] flex md:hidden">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="closeNav"></div>

        <!-- Drawer content -->
        <div class="relative z-50 w-64 h-full border-r-2 shadow-xl bg-gray-100/90 backdrop-blur-md border-primary">
            <div class="flex flex-col h-full">
                <!-- Header -->
                <div class="flex items-center justify-between p-4 border-b border-gray-300/50">
                    <div class="flex items-center">
                        <div class="bg-gray-200 shrink-0">
                            <img src="@img/logo.png" class="object-cover w-12 h-auto" alt="Royal Bus Logo" />
                        </div>
                        <div class="ml-3 text-left">
                            <p class="text-sm font-semibold text-gray-900">Royal Movers</p>
                            <p class="text-xs text-gray-600">Transport Booking</p>
                        </div>
                    </div>

                    <button type="button" @click="closeNav"
                        class="p-2 text-white transition-colors rounded-full shadow-md bg-primary hover:bg-primary/90">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                            class="bi bi-x" viewBox="0 0 16 16">
                            <path
                                d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708" />
                        </svg>
                    </button>
                </div>

                <!-- Navigation links -->
                <div class="flex-1 overflow-y-auto">
                    <nav class="p-4">
                        <div class="space-y-2">
                            <button type="button" @click="goSection('home')"
                                class="flex items-center w-full px-4 py-3 text-sm font-semibold text-gray-700 transition-colors rounded-lg hover:bg-primary hover:text-white">
                                Home
                            </button>

                            <Link :href="route('booking')" @click="closeNav"
                                class="flex items-center w-full px-4 py-3 text-sm font-semibold text-gray-700 transition-colors rounded-lg hover:bg-primary hover:text-white">
                                Booking
                            </Link>

                            <button type="button" @click="goSection('bus-types')"
                                class="flex items-center w-full px-4 py-3 text-sm font-semibold text-gray-700 transition-colors rounded-lg hover:bg-primary hover:text-white">
                                Bus Types
                            </button>

                            <button type="button" @click="goSection('routes')"
                                class="flex items-center w-full px-4 py-3 text-sm font-semibold text-gray-700 transition-colors rounded-lg hover:bg-primary hover:text-white">
                                Top Routes
                            </button>
                            <Link :href="route('gallery')" @click="closeNav"
                                class="flex items-center w-full px-4 py-3 text-sm font-semibold text-gray-700 transition-colors rounded-lg hover:bg-primary hover:text-white">
                                Gallery
                            </Link>

                            <button type="button" @click="goSection('footer')"
                                class="flex items-center w-full px-4 py-3 text-sm font-semibold text-gray-700 transition-colors rounded-lg hover:bg-primary hover:text-white">
                                Contact
                            </button>
                        </div>
                    </nav>
                </div>

                <!-- Auth buttons -->
                <div class="p-4 mt-auto border-t border-gray-300/50">
                    <div class="space-y-3">
                        <Link :href="route('booking')" @click="closeNav"
                            class="inline-flex justify-center w-full px-4 py-3 text-sm font-semibold text-center text-white transition-colors shadow-md rounded-xl bg-primary hover:bg-primary/90">
                            Book Now
                        </Link>

                        <div v-if="$page.props.auth?.user">
                            <!-- Dashboard for SuperAdmin -->
                            <Link v-if="$page.props.auth.user.IsSuperAdmin" :href="route('admin.dashboard')"
                                @click="closeNav"
                                class="inline-flex justify-center w-full px-4 py-3 text-sm font-semibold text-center text-white transition-colors shadow-md rounded-xl bg-secondary hover:bg-secondary/90">
                                Dashboard
                            </Link>

                            <!-- Profile for WebCustomer -->
                            <Link v-else-if="$page.props.auth.user.User_Type === 'WebCustomer'"
                                :href="route('profile.index')" @click="closeNav"
                                class="inline-flex justify-center w-full px-4 py-3 text-sm font-semibold text-center text-white transition-colors shadow-md rounded-xl bg-secondary hover:bg-secondary/90">
                                Profile
                            </Link>
                        </div>

                        <!-- Login for guests -->
                        <Link v-else :href="route('login')" @click="closeNav"
                            class="inline-flex justify-center w-full px-4 py-3 text-sm font-semibold text-center text-white transition-colors shadow-md rounded-xl bg-secondary hover:bg-secondary/90">
                            Login
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>

</template>

<style scoped>
.nav-link {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    border-radius: 0.75rem;
    padding: 0.5rem 0.75rem;
    font-size: 0.95rem;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.85);
    transition: background 160ms ease, color 160ms ease, transform 160ms ease;
}

.nav-link:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    transform: translateY(-1px);
}

/* Smooth transitions */
.fixed {
    transition: opacity 0.3s ease;
}

.bg-black\/50 {
    transition: background-color 0.3s ease;
}

.w-64 {
    transform: translateX(0);
    transition: transform 0.3s ease;
}

/* Ensure drawer content doesn't overflow */
.h-full {
    max-height: 100vh;
}
</style>
