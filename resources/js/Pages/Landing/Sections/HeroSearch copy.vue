<script setup>
import { Link, router } from "@inertiajs/vue3";
import { computed, onMounted, ref, watch, nextTick } from "vue";
import DatePicker from "@/Components/LandingPage/DatePicker.vue";

/* =========================
   FORM STATE
========================= */
const fromCity = ref("");
const toCity = ref("");
const travelDate = ref("");
const fromQuery = ref("");
const toQuery = ref("");

/* =========================
   DROPDOWN STATE
========================= */
const showFromDropdown = ref(false);
const showToDropdown = ref(false);
const fromActiveIndex = ref(-1);
const toActiveIndex = ref(-1);

/* =========================
   LOADING STATES
========================= */
const loadingMainCities = ref(false);
const loadingMappedCities = ref(false);
const mainCities = ref([]);
const mappedCities = ref([]);
const error = ref(null);

/* =========================
   WATCHERS FOR SYNC
========================= */
watch(fromCity, (v) => {
    fromQuery.value = v;
    fromActiveIndex.value = -1;

    if (toCity.value === v) {
        toCity.value = "";
        toQuery.value = "";
    }

    mappedCities.value = [];
    toCity.value = "";
    toQuery.value = "";
});

watch(toCity, (v) => {
    toQuery.value = v;
    toActiveIndex.value = -1;
});

watch(fromCity, async (newCityName) => {
    if (!newCityName) {
        mappedCities.value = [];
        return;
    }

    const selectedMainCity = mainCities.value.find(
        (city) => city.CityName === newCityName
    );

    if (selectedMainCity && selectedMainCity.Id) {
        await loadMappedCities(selectedMainCity.Id);
    } else {
        mappedCities.value = [];
    }
});

watch(travelDate, (newDate) => {
    if (newDate) {
        const today = new Date(todayISO());
        const selected = new Date(newDate);

        if (
            selected < today &&
            selected.toDateString() !== today.toDateString()
        ) {
            travelDate.value = todayISO();
            message.value = "Please select today or a future date.";
            messageTone.value = "error";

            nextTick(() => {
                const dateInput = document.querySelector('input[type="date"]');
                if (dateInput) dateInput.focus();
            });
        }
    }
});

/* =========================
   API FUNCTIONS
========================= */
const loadMainCities = async () => {
    try {
        loadingMainCities.value = true;
        error.value = null;

        const response = await fetch("/api/city-mappings/main-cities");
        const data = await response.json();

        if (data.success) {
            mainCities.value = data.data;
        } else {
            error.value = data.message;
            console.error("Failed to load main cities:", data.message);
        }
    } catch (err) {
        error.value = "Failed to load cities. Please try again.";
        console.error("Error loading main cities:", err);
    } finally {
        loadingMainCities.value = false;
    }
};

const loadMappedCities = async (cityId) => {
    try {
        loadingMappedCities.value = true;

        const response = await fetch("/api/city-mappings/mapped-cities", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN":
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute("content") || "",
            },
            body: JSON.stringify({ city_id: cityId }),
        });

        const data = await response.json();

        if (data.success) {
            mappedCities.value = data.data;
        } else {
            mappedCities.value = [];
            console.warn("No mapped cities found:", data.message);
        }
    } catch (err) {
        console.error("Error loading mapped cities:", err);
        mappedCities.value = [];
    } finally {
        loadingMappedCities.value = false;
    }
};

/* =========================
   FILTERED CITIES
========================= */
const filteredFromCities = computed(() => {
    if (!fromQuery.value) return mainCities.value.slice(0, 50);
    const query = fromQuery.value.toLowerCase();
    return mainCities.value
        .filter((c) => c.CityName.toLowerCase().includes(query))
        .slice(0, 50);
});

const filteredToCities = computed(() => {
    if (!fromCity.value) return [];
    if (!toQuery.value) return mappedCities.value.slice(0, 50);
    const query = toQuery.value.toLowerCase();
    return mappedCities.value
        .filter((c) => c.CityName.toLowerCase().includes(query))
        .slice(0, 50);
});

/* =========================
   SELECT CITY FUNCTIONS
========================= */
function selectFromCity(city) {
    fromCity.value = city.CityName;
    fromQuery.value = city.CityName;
    fromActiveIndex.value = -1;
    showFromDropdown.value = false;
    toCity.value = "";
    toQuery.value = "";

    nextTick(() => {
        const toInput = document.querySelector("#to-city-input");
        if (toInput) toInput.focus();
    });
}

function selectToCity(city) {
    toCity.value = city.CityName;
    toQuery.value = city.CityName;
    toActiveIndex.value = -1;
    showToDropdown.value = false;

    nextTick(() => {
        const dateInput = document.querySelector("#travel-date-input");
        if (dateInput) dateInput.focus();
    });
}

/* =========================
   INPUT HANDLERS
========================= */
function onFromFocus() {
    showFromDropdown.value = true;
    fromActiveIndex.value = -1;
}

function onFromInput(e) {
    fromQuery.value = e.target.value;
    fromCity.value = e.target.value;
    fromActiveIndex.value = -1;
    showFromDropdown.value = true;
}

function onToFocus() {
    if (fromCity.value) {
        showToDropdown.value = true;
        toActiveIndex.value = -1;
    }
}

function onToInput(e) {
    toQuery.value = e.target.value;
    toCity.value = e.target.value;
    toActiveIndex.value = -1;
    if (fromCity.value) showToDropdown.value = true;
}

/* =========================
   KEYBOARD NAVIGATION
========================= */
function scrollToActiveIndex(dropdownElement, activeIndex) {
    if (!dropdownElement || activeIndex === -1) return;
    const items = dropdownElement.querySelectorAll("li");
    if (items[activeIndex]) {
        items[activeIndex].scrollIntoView({ behavior: "smooth", block: "nearest" });
    }
}

function handleFromKeydown(e) {
    if (!showFromDropdown.value || !filteredFromCities.value.length) return;
    switch (e.key) {
        case "ArrowDown":
            e.preventDefault();
            fromActiveIndex.value = (fromActiveIndex.value + 1) % filteredFromCities.value.length;
            nextTick(() => {
                const d = document.querySelector("[data-from-dropdown]");
                if (d) scrollToActiveIndex(d, fromActiveIndex.value);
            });
            break;
        case "ArrowUp":
            e.preventDefault();
            fromActiveIndex.value = fromActiveIndex.value <= 0
                ? filteredFromCities.value.length - 1
                : fromActiveIndex.value - 1;
            nextTick(() => {
                const d = document.querySelector("[data-from-dropdown]");
                if (d) scrollToActiveIndex(d, fromActiveIndex.value);
            });
            break;
        case "Enter":
            e.preventDefault();
            if (fromActiveIndex.value >= 0) selectFromCity(filteredFromCities.value[fromActiveIndex.value]);
            else if (filteredFromCities.value.length === 1) selectFromCity(filteredFromCities.value[0]);
            else showFromDropdown.value = false;
            break;
        case "Escape":
            e.preventDefault();
            showFromDropdown.value = false;
            fromActiveIndex.value = -1;
            break;
        case "Tab":
            showFromDropdown.value = false;
            fromActiveIndex.value = -1;
            break;
    }
}

function handleToKeydown(e) {
    if (!showToDropdown.value || !filteredToCities.value.length) return;
    switch (e.key) {
        case "ArrowDown":
            e.preventDefault();
            toActiveIndex.value = (toActiveIndex.value + 1) % filteredToCities.value.length;
            nextTick(() => {
                const d = document.querySelector("[data-to-dropdown]");
                if (d) scrollToActiveIndex(d, toActiveIndex.value);
            });
            break;
        case "ArrowUp":
            e.preventDefault();
            toActiveIndex.value = toActiveIndex.value <= 0
                ? filteredToCities.value.length - 1
                : toActiveIndex.value - 1;
            nextTick(() => {
                const d = document.querySelector("[data-to-dropdown]");
                if (d) scrollToActiveIndex(d, toActiveIndex.value);
            });
            break;
        case "Enter":
            e.preventDefault();
            if (toActiveIndex.value >= 0) selectToCity(filteredToCities.value[toActiveIndex.value]);
            else if (filteredToCities.value.length === 1) selectToCity(filteredToCities.value[0]);
            else showToDropdown.value = false;
            break;
        case "Escape":
            e.preventDefault();
            showToDropdown.value = false;
            toActiveIndex.value = -1;
            break;
        case "Tab":
            showToDropdown.value = false;
            toActiveIndex.value = -1;
            break;
    }
}

/* =========================
   CLICK OUTSIDE HANDLER
========================= */
onMounted(() => {
    loadMainCities();

    if (!travelDate.value) travelDate.value = todayISO();

    document.addEventListener("click", (e) => {
        if (!e.target.closest("[data-from-container]")) showFromDropdown.value = false;
        if (!e.target.closest("[data-to-container]")) showToDropdown.value = false;
    });

    window.addEventListener("blur", () => {
        showFromDropdown.value = false;
        showToDropdown.value = false;
        fromActiveIndex.value = -1;
        toActiveIndex.value = -1;
    });
});

/* =========================
   HELPERS
========================= */
function todayISO() {
    const d = new Date();
    const pad = (n) => String(n).padStart(2, "0");
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

const message = ref("");
const messageTone = ref("info");

/* =========================
   SUBMIT FUNCTION
========================= */
function submit() {
    const f = fromCity.value;
    const t = toCity.value;
    const d = travelDate.value;

    if (!f || !t || !d) {
        message.value = "Please select From City, To City and Travel Date.";
        messageTone.value = "error";
        return;
    }

    if (f === t) {
        message.value = "From City and To City cannot be the same.";
        messageTone.value = "error";
        return;
    }

    const today = new Date(todayISO());
    const selected = new Date(d);

    if (selected < today) {
        message.value = "Please select today or a future date.";
        messageTone.value = "error";
        travelDate.value = todayISO();
        return;
    }

    message.value = `Searching buses from ${f} to ${t} on ${d}...`;
    messageTone.value = "info";

    router.get(
        route("booking"),
        { from: f, to: t, date: d },
        { preserveScroll: true, preserveState: false }
    );
}
</script>

<template>
    <!-- Google Fonts: Syne + DM Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500&display=swap"
        rel="stylesheet" />

    <header id="home" class="relative"
        style="background-color: #071520; font-family: 'DM Sans', sans-serif; overflow-x: clip;">
        <!-- ══════════════════════════════
             BACKGROUND ATMOSPHERE
        ══════════════════════════════ -->
        <div class="absolute inset-0 z-0 pointer-events-none select-none">

            <!-- Noise grain texture -->
            <div class="absolute inset-0 opacity-[0.18]" style="
                    background-image: url('data:image/svg+xml,%3Csvg viewBox%3D%220 0 256 256%22 xmlns%3D%22http%3A//www.w3.org/2000/svg%22%3E%3Cfilter id%3D%22noise%22%3E%3CfeTurbulence type%3D%22fractalNoise%22 baseFrequency%3D%220.9%22 numOctaves%3D%224%22 stitchTiles%3D%22stitch%22/%3E%3C/filter%3E%3Crect width%3D%22100%25%22 height%3D%22100%25%22 filter%3D%22url(%23noise)%22 opacity%3D%220.5%22/%3E%3C/svg%3E');
                    background-size: 180px 180px;
                "></div>

            <!-- Primary green glow — top left -->
            <div class="absolute rounded-full" style="
                    width: 700px; height: 700px;
                    top: -280px; left: -200px;
                    background: radial-gradient(circle, rgba(76,187,47,0.14) 0%, transparent 65%);
                "></div>

            <!-- Accent lime glow — bottom right -->
            <div class="absolute rounded-full" style="
                    width: 550px; height: 550px;
                    bottom: -180px; right: -150px;
                    background: radial-gradient(circle, rgba(109,210,48,0.10) 0%, transparent 65%);
                "></div>

            <!-- Navy center deep glow -->
            <div class="absolute rounded-full" style="
                    width: 900px; height: 500px;
                    top: 50%; left: 50%;
                    transform: translate(-50%, -50%);
                    background: radial-gradient(ellipse, rgba(13,43,62,0.7) 0%, transparent 70%);
                "></div>

            <!-- Diagonal green lines -->
            <div class="absolute inset-0" style="
                    opacity: 0.035;
                    background-image: repeating-linear-gradient(
                        -48deg,
                        #4cbb2f 0px, #4cbb2f 1px,
                        transparent 1px, transparent 38px
                    );
                "></div>

            <!-- Horizontal subtle grid -->
            <div class="absolute inset-0" style="
                    opacity: 0.025;
                    background-image: repeating-linear-gradient(
                        0deg,
                        rgba(255,255,255,1) 0px, rgba(255,255,255,1) 1px,
                        transparent 1px, transparent 64px
                    );
                "></div>
        </div>

        <!-- ══════════════════════════════
             MAIN TWO-COLUMN LAYOUT
        ══════════════════════════════ -->
        <div class="relative z-10 flex flex-col lg:flex-row max-w-6xl px-4 mx-auto">

            <!-- ╔══════════════════════════╗
                 ║  LEFT — EDITORIAL PANEL  ║
                 ╚══════════════════════════╝ -->
            <div class="flex flex-col justify-between py-14 lg:py-20 lg:w-1/2 lg:pr-12">

                <!-- Top live badge -->
                <div class="flex items-center gap-3 flex-wrap">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-px" style="background: linear-gradient(90deg, #4cbb2f, transparent);"></div>
                        <span class="text-xs font-semibold uppercase"
                            style="color: #4cbb2f; letter-spacing: 0.18em;">Pakistan's Express Network</span>
                    </div>
                    <span class="inline-flex items-center gap-1.5 text-xs font-bold px-2.5 py-1 rounded-sm" style="
                            background: rgba(76,187,47,0.12);
                            color: #6dd230;
                            border: 1px solid rgba(76,187,47,0.3);
                            font-size: 9px;
                            letter-spacing: 0.16em;
                        ">
                        <span class="w-1.5 h-1.5 rounded-full animate-pulse" style="background: #4cbb2f;"></span>
                        LIVE
                    </span>
                </div>

                <!-- Editorial headline block -->
                <div class="mt-10 lg:mt-0">

                    <h1 class="leading-[0.92]" style="
                            font-family: 'Syne', sans-serif;
                            font-weight: 800;
                            font-size: clamp(3rem, 5vw, 3.5rem);
                            letter-spacing: -0.035em;
                        ">
                        <span class="block" style="color: #e8fce3;">Every</span>
                        <span class="block" style="
                                color: #4cbb2f;
                                text-shadow: 0 0 50px rgba(76,187,47,0.45), 0 0 100px rgba(76,187,47,0.15);
                            ">Route.</span>
                        <span class="block" style="color: #e8fce3;">One Ticket.</span>
                    </h1>

                    <!-- Decorative accent lines -->
                    <div class="flex items-center gap-2 mt-6">
                        <div class="h-0.5 w-14" style="background: #4cbb2f;"></div>
                        <div class="h-0.5 w-6" style="background: rgba(76,187,47,0.4);"></div>
                        <div class="h-0.5 w-3" style="background: rgba(76,187,47,0.2);"></div>
                    </div>

                    <p class="mt-5 max-w-sm leading-relaxed"
                        style="color: rgba(232,252,227,0.45); font-size: 1.05rem; font-weight: 300;">
                        Modern coaches. Real-time schedules. Search any route
                        across Pakistan and reserve your seat in seconds.
                    </p>

                    <!-- Action buttons -->
                    <div class="flex flex-wrap items-center gap-3 mt-8">

                        <!-- Primary CTA — solid green -->
                        <a href="#search"
                            class="group inline-flex items-center gap-2 px-6 py-3 text-sm font-bold transition-all duration-200"
                            style="
                                background: linear-gradient(135deg, #4cbb2f 0%, #6dd230 100%);
                                color: #0d2b3e;
                                border-radius: 6px;
                                box-shadow: 0 4px 22px rgba(76,187,47,0.4);
                                font-family: 'Syne', sans-serif;
                                letter-spacing: 0.04em;
                            "
                            onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 30px rgba(76,187,47,0.55)'"
                            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 22px rgba(76,187,47,0.4)'">
                            Find Buses
                            <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                        </a>

                        <!-- Ghost CTA -->
                        <a href="#routes"
                            class="inline-flex items-center gap-2 px-6 py-3 text-sm font-medium border transition-all duration-200"
                            style="border-color: rgba(76,187,47,0.25); color: rgba(232,252,227,0.65); border-radius: 6px;"
                            onmouseover="this.style.borderColor='rgba(76,187,47,0.55)'; this.style.color='#6dd230'; this.style.background='rgba(76,187,47,0.05)'"
                            onmouseout="this.style.borderColor='rgba(76,187,47,0.25)'; this.style.color='rgba(232,252,227,0.65)'; this.style.background='transparent'">
                            Today's Routes
                        </a>

                        <!-- Auth links -->
                        <div v-if="$page.props.auth?.user">
                            <Link v-if="$page.props.auth.user.IsSuperAdmin" :href="route('admin.dashboard')"
                                class="inline-flex items-center gap-2 px-6 py-3 text-sm font-medium border transition-all duration-200"
                                style="border-color: rgba(76,187,47,0.25); color: rgba(232,252,227,0.65); border-radius: 6px;"
                                onmouseover="this.style.borderColor='rgba(76,187,47,0.55)'; this.style.color='#6dd230'"
                                onmouseout="this.style.borderColor='rgba(76,187,47,0.25)'; this.style.color='rgba(232,252,227,0.65)'">
                                Dashboard
                            </Link>
                            <Link v-else-if="$page.props.auth.user.User_Type === 'WebCustomer'"
                                :href="route('profile.index')"
                                class="inline-flex items-center gap-2 px-6 py-3 text-sm font-medium border transition-all duration-200"
                                style="border-color: rgba(76,187,47,0.25); color: rgba(232,252,227,0.65); border-radius: 6px;"
                                onmouseover="this.style.borderColor='rgba(76,187,47,0.55)'; this.style.color='#6dd230'"
                                onmouseout="this.style.borderColor='rgba(76,187,47,0.25)'; this.style.color='rgba(232,252,227,0.65)'">
                                My Profile
                            </Link>
                        </div>
                        <Link v-else :href="route('login')"
                            class="inline-flex items-center gap-2 px-6 py-3 text-sm font-medium border transition-all duration-200"
                            style="border-color: rgba(76,187,47,0.25); color: rgba(232,252,227,0.65); border-radius: 6px;"
                            onmouseover="this.style.borderColor='rgba(76,187,47,0.55)'; this.style.color='#6dd230'"
                            onmouseout="this.style.borderColor='rgba(76,187,47,0.25)'; this.style.color='rgba(232,252,227,0.65)'">
                            Sign In
                        </Link>
                    </div>
                </div>

                <!-- Stats row -->
                <div class="flex flex-wrap gap-8 pt-8 mt-14 border-t" style="border-color: rgba(76,187,47,0.12);">
                    <div>
                        <p class="text-2xl font-bold"
                            style="font-family:'Syne',sans-serif; color:#4cbb2f; text-shadow: 0 0 20px rgba(76,187,47,0.4);">
                            <span v-if="!loadingMainCities">{{ mainCities.length }}</span>
                            <span v-else class="animate-pulse opacity-50">–</span>
                        </p>
                        <p class="mt-1 text-xs uppercase" style="color:rgba(232,252,227,0.3); letter-spacing:0.15em;">
                            Main Cities</p>
                    </div>
                    <div class="w-px self-stretch" style="background: rgba(76,187,47,0.1);"></div>
                    <div>
                        <p class="text-2xl font-bold"
                            style="font-family:'Syne',sans-serif; color:#4cbb2f; text-shadow: 0 0 20px rgba(76,187,47,0.4);">
                            1,800+</p>
                        <p class="mt-1 text-xs uppercase" style="color:rgba(232,252,227,0.3); letter-spacing:0.15em;">
                            Daily Trips</p>
                    </div>
                    <div class="w-px self-stretch" style="background: rgba(76,187,47,0.1);"></div>
                    <div>
                        <p class="text-2xl font-bold"
                            style="font-family:'Syne',sans-serif; color:#4cbb2f; text-shadow: 0 0 20px rgba(76,187,47,0.4);">
                            24/7</p>
                        <p class="mt-1 text-xs uppercase" style="color:rgba(232,252,227,0.3); letter-spacing:0.15em;">
                            Support</p>
                    </div>
                </div>
            </div>
            <!-- end left column -->

            <!-- ╔═══════════════════════════════╗
                 ║  RIGHT — BOARDING PASS CARD   ║
                 ╚═══════════════════════════════╝ -->
            <div class="flex items-center justify-center py-12 lg:py-20 lg:w-1/2 lg:pl-6">
                <div class="w-full max-w-md" id="search">

                    <!-- ── Card wrapper ── -->
                    <div style="
                            background: #f2fbef;
                            border-radius: 20px;
                            box-shadow:
                                0 0 0 1px rgba(76,187,47,0.18),
                                0 0 0 5px #0d2b3e,
                                0 0 0 6px rgba(76,187,47,0.12),
                                0 40px 80px rgba(0,0,0,0.6),
                                0 8px 32px rgba(76,187,47,0.12);
                            overflow: visible;
                            position: relative;
                        ">

                        <!-- ── Card Header: dark navy stripe ── -->
                        <div class="flex items-center justify-between px-6 py-4" style="
                                background: linear-gradient(135deg, #0d2b3e 0%, #102f46 50%, #0d2b3e 100%);
                                border-radius: 20px 20px 0 0;
                                border-bottom: 3px solid #4cbb2f;
                                position: relative;
                                overflow: hidden;
                            ">
                            <!-- Subtle inner glow on header -->
                            <div class="absolute inset-0 pointer-events-none"
                                style="background: radial-gradient(ellipse at 30% 50%, rgba(76,187,47,0.08) 0%, transparent 60%);">
                            </div>

                            <div class="relative flex items-center gap-3">
                                <!-- Bus icon in green ring -->
                                <div class="flex items-center justify-center w-10 h-10 rounded-full" style="
                                        background: rgba(76,187,47,0.12);
                                        border: 1.5px solid rgba(76,187,47,0.45);
                                        box-shadow: 0 0 12px rgba(76,187,47,0.2);
                                    ">
                                    <svg class="w-5 h-5" style="color:#4cbb2f;" viewBox="0 0 24 24" fill="currentColor">
                                        <path
                                            d="M4 16c0 .88.39 1.67 1 2.22V20a1 1 0 001 1h1a1 1 0 001-1v-1h8v1a1 1 0 001 1h1a1 1 0 001-1v-1.78c.61-.55 1-1.34 1-2.22V6c0-3.5-3.58-4-8-4S4 2.5 4 6v10zm3.5 1a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm9 0a1.5 1.5 0 110-3 1.5 1.5 0 010 3zM6 6h12v5H6V6z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs"
                                        style="color:rgba(76,187,47,0.55); letter-spacing:0.1em; font-size:9px;">TECH
                                        ZONE SOLUTIONS</p>
                                    <p class="text-sm font-bold uppercase"
                                        style="color:#e8fce3; font-family:'Syne',sans-serif; letter-spacing:0.1em;">
                                        Ticket Search</p>
                                </div>
                            </div>

                            <span class="relative text-xs font-bold px-3 py-1.5 rounded-sm" style="
                                    background: linear-gradient(135deg, #4cbb2f, #6dd230);
                                    color: #0d2b3e;
                                    letter-spacing: 0.1em;
                                    font-family:'Syne',sans-serif;
                                    font-size: 10px;
                                    box-shadow: 0 2px 10px rgba(76,187,47,0.4);
                                ">ONE WAY</span>
                        </div>

                        <!-- ── Perforation top ── -->
                        <div class="relative flex items-center" style="height:2px; margin:0;">
                            <div class="absolute -left-4 w-8 h-8 rounded-full"
                                style="background:#071520; border:2px solid rgba(76,187,47,0.1);"></div>
                            <div class="flex-1 border-t-2 border-dashed mx-8"
                                style="border-color: rgba(76,187,47,0.18);"></div>
                            <div class="absolute -right-4 w-8 h-8 rounded-full"
                                style="background:#071520; border:2px solid rgba(76,187,47,0.1);"></div>
                        </div>

                        <!-- ── Form body ── -->
                        <form class="px-6 py-6 grid gap-4" @submit.prevent="submit" autocomplete="off">

                            <!-- FROM CITY -->
                            <div class="relative" data-from-container>
                                <label
                                    class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest mb-2"
                                    style="color:#2d7a1f; letter-spacing:0.14em;">
                                    <span
                                        class="flex items-center justify-center w-4 h-4 rounded-full text-xs font-bold"
                                        style="background:#4cbb2f; color:#0d2b3e; font-size:9px;">↑</span>
                                    Departing From
                                </label>

                                <div class="relative">
                                    <input id="from-city-input" v-model="fromQuery" @focus="onFromFocus"
                                        @input="onFromInput" @keydown="handleFromKeydown"
                                        placeholder="Select origin city"
                                        class="w-full px-4 py-3 text-sm outline-none transition-all duration-200" style="
                                            background:#ffffff;
                                            border: 1.5px solid rgba(13,43,62,0.12);
                                            border-radius: 8px;
                                            color: #0d2b3e;
                                            font-family: 'DM Sans', sans-serif;
                                        "
                                        onfocus="this.style.borderColor='#4cbb2f'; this.style.boxShadow='0 0 0 3px rgba(76,187,47,0.15)'"
                                        onblur="this.style.borderColor='rgba(13,43,62,0.12)'; this.style.boxShadow='none'" />
                                    <div v-if="loadingMainCities"
                                        class="absolute inset-y-0 right-3 flex items-center pointer-events-none">
                                        <svg class="w-4 h-4 animate-spin" style="color:#4cbb2f;" fill="none"
                                            viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                    </div>
                                </div>

                                <!-- From dropdown -->
                                <ul v-if="showFromDropdown && filteredFromCities.length" data-from-dropdown
                                    class="absolute z-50 w-full mt-1 overflow-auto" style="
                                        max-height: 220px;
                                        background: #ffffff;
                                        border: 1.5px solid rgba(76,187,47,0.3);
                                        border-radius: 8px;
                                        box-shadow: 0 16px 48px rgba(13,43,62,0.22), 0 4px 12px rgba(76,187,47,0.08);
                                    ">
                                    <li v-for="(c, i) in filteredFromCities" :key="c.Id"
                                        @mousedown.prevent="selectFromCity(c)"
                                        class="cursor-pointer px-4 py-2.5 text-sm flex items-center gap-2" :style="i === fromActiveIndex
                                            ? 'background:#dcfce7; color:#14532d; font-weight:600;'
                                            : 'color:#1a3a2e;'" onmouseover="this.style.background='#f0fdf4'"
                                        onmouseout="this.style.background=''">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" style="color:#4cbb2f;" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        {{ c.CityName }}
                                    </li>
                                </ul>
                            </div>

                            <!-- Direction arrow divider -->
                            <div class="flex items-center gap-3">
                                <div class="flex-1 h-px"
                                    style="background: linear-gradient(90deg, rgba(76,187,47,0.25), transparent);">
                                </div>
                                <div class="flex items-center justify-center w-9 h-9 rounded-full" style="
                                        background: linear-gradient(135deg, #0d2b3e, #0f3349);
                                        border: 2px solid #4cbb2f;
                                        box-shadow: 0 0 16px rgba(76,187,47,0.35);
                                    ">
                                    <svg class="w-4 h-4" style="color:#4cbb2f;" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M10 3a1 1 0 011 1v10.586l2.293-2.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 14.586V4a1 1 0 011-1z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="flex-1 h-px"
                                    style="background: linear-gradient(90deg, transparent, rgba(76,187,47,0.25));">
                                </div>
                            </div>

                            <!-- TO CITY -->
                            <div class="relative" data-to-container>
                                <label
                                    class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest mb-2"
                                    style="color:#2d7a1f; letter-spacing:0.14em;">
                                    <span
                                        class="flex items-center justify-center w-4 h-4 rounded-full text-xs font-bold"
                                        style="background:#0d2b3e; color:#4cbb2f; font-size:9px; border: 1px solid #4cbb2f;">↓</span>
                                    Arriving At
                                </label>

                                <div class="relative">
                                    <input id="to-city-input" v-model="toQuery" @focus="onToFocus" @input="onToInput"
                                        @keydown="handleToKeydown" :disabled="!fromCity"
                                        :placeholder="fromCity ? 'Select destination' : 'Choose origin first'"
                                        class="w-full px-4 py-3 text-sm outline-none transition-all duration-200 disabled:cursor-not-allowed disabled:opacity-50"
                                        style="
                                            background:#ffffff;
                                            border: 1.5px solid rgba(13,43,62,0.12);
                                            border-radius: 8px;
                                            color: #0d2b3e;
                                            font-family: 'DM Sans', sans-serif;
                                        "
                                        onfocus="this.style.borderColor='#4cbb2f'; this.style.boxShadow='0 0 0 3px rgba(76,187,47,0.15)'"
                                        onblur="this.style.borderColor='rgba(13,43,62,0.12)'; this.style.boxShadow='none'" />
                                    <div v-if="loadingMappedCities && fromCity"
                                        class="absolute inset-y-0 right-3 flex items-center pointer-events-none">
                                        <svg class="w-4 h-4 animate-spin" style="color:#4cbb2f;" fill="none"
                                            viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                    </div>
                                </div>

                                <!-- To dropdown -->
                                <ul v-if="showToDropdown && filteredToCities.length" data-to-dropdown
                                    class="absolute z-50 w-full mt-1 overflow-auto" style="
                                        max-height: 220px;
                                        background: #ffffff;
                                        border: 1.5px solid rgba(76,187,47,0.3);
                                        border-radius: 8px;
                                        box-shadow: 0 16px 48px rgba(13,43,62,0.22), 0 4px 12px rgba(76,187,47,0.08);
                                    ">
                                    <li v-for="(c, i) in filteredToCities" :key="c.Id"
                                        @mousedown.prevent="selectToCity(c)"
                                        class="cursor-pointer px-4 py-2.5 text-sm flex items-center gap-2" :style="i === toActiveIndex
                                            ? 'background:#dcfce7; color:#14532d; font-weight:600;'
                                            : 'color:#1a3a2e;'" onmouseover="this.style.background='#f0fdf4'"
                                        onmouseout="this.style.background=''">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" style="color:#4cbb2f;" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        {{ c.CityName }}
                                    </li>
                                    <li v-if="filteredToCities.length === 0 && !loadingMappedCities && fromCity"
                                        class="px-4 py-3 text-sm italic" style="color:#9ca3af;">
                                        No routes found from {{ fromCity }}
                                    </li>
                                </ul>
                            </div>

                            <!-- TRAVEL DATE -->
                            <div>
                                <label
                                    class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest mb-2"
                                    style="color:#2d7a1f; letter-spacing:0.14em;">
                                    <span style="color:#6dd230; font-size:11px;">✦</span>
                                    Travel Date
                                </label>
                                <DatePicker v-model="travelDate" label="" placeholder="Select date" />
                            </div>

                            <!-- ── Tear perforation before CTA ── -->
                            <div class="relative flex items-center" style="height:2px; margin: 2px -24px;">
                                <div class="absolute -left-4 w-8 h-8 rounded-full"
                                    style="background:#071520; border:2px solid rgba(76,187,47,0.1);"></div>
                                <div class="flex-1 border-t-2 border-dashed mx-8"
                                    style="border-color: rgba(76,187,47,0.18);"></div>
                                <div class="absolute -right-4 w-8 h-8 rounded-full"
                                    style="background:#071520; border:2px solid rgba(76,187,47,0.1);"></div>
                            </div>

                            <!-- SUBMIT BUTTON -->
                            <button type="submit" :disabled="!fromCity || !toCity"
                                class="group w-full flex items-center justify-center gap-2 py-3.5 text-sm font-bold transition-all duration-200"
                                style="
                                    background: linear-gradient(135deg, #0d2b3e 0%, #0f3349 100%);
                                    color: #4cbb2f;
                                    border-radius: 8px;
                                    font-family: 'Syne', sans-serif;
                                    letter-spacing: 0.06em;
                                    border: 1.5px solid rgba(76,187,47,0.4);
                                    box-shadow: 0 4px 20px rgba(0,0,0,0.25), 0 0 0 0 rgba(76,187,47,0);
                                " :class="{ 'opacity-40 cursor-not-allowed': !fromCity || !toCity }"
                                onmouseover="if(!this.disabled){this.style.background='linear-gradient(135deg,#0f3349,#163d58)'; this.style.borderColor='rgba(76,187,47,0.65)'; this.style.boxShadow='0 6px 28px rgba(0,0,0,0.3), 0 0 20px rgba(76,187,47,0.18)'; this.style.color='#6dd230';}"
                                onmouseout="if(!this.disabled){this.style.background='linear-gradient(135deg,#0d2b3e,#0f3349)'; this.style.borderColor='rgba(76,187,47,0.4)'; this.style.boxShadow='0 4px 20px rgba(0,0,0,0.25)'; this.style.color='#4cbb2f';}">
                                Search Available Buses
                                <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </button>

                            <!-- Feedback messages -->
                            <p v-if="message" class="text-xs text-center" :style="{
                                color: messageTone === 'error' ? '#ef4444'
                                    : messageTone === 'success' ? '#22c55e'
                                        : '#4cbb2f',
                            }">{{ message }}</p>
                            <p v-if="error" class="text-xs text-center" style="color:#ef4444;">{{ error }}</p>
                        </form>

                        <!-- ── Card footer stripe ── -->
                        <div class="flex items-center justify-between px-6 py-3" style="
                                background: linear-gradient(135deg, #0d2b3e 0%, #0f3349 100%);
                                border-radius: 0 0 20px 20px;
                                border-top: 2px solid rgba(76,187,47,0.2);
                            ">
                            <span class="flex items-center gap-1.5 text-xs"
                                style="color:rgba(76,187,47,0.55); letter-spacing:0.05em;">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                Secure payments accepted
                            </span>
                            <span class="text-xs" style="color:rgba(76,187,47,0.35); letter-spacing:0.07em;">
                                Visa · Master · Wallet
                            </span>
                        </div>

                    </div>
                    <!-- end card -->
                </div>
            </div>
            <!-- end right column -->

        </div>
    </header>
</template>
