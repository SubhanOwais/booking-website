<script setup>
import { computed, onMounted, ref, watch, nextTick } from "vue";
import { Link, router } from "@inertiajs/vue3";
import DatePicker from "@/Components/LandingPage/DatePicker.vue";
import { MapPin, Calendar, Search, CheckCircle2, ChevronLeft, ChevronRight, Bus } from "lucide-vue-next";

/* =========================
   OFFER SLIDER STATE (NEW)
========================= */
const offers = [
    { id: 1, title: "First Ride 50% OFF", code: "WELCOME50", image: "https://picsum.photos/seed/bus1/800/400" },
    { id: 2, title: "Lahore to Karachi", code: "EXPRESS20", image: "https://picsum.photos/seed/bus2/800/400" },
    { id: 3, title: "Safe Travels Insurance", code: "FREE", image: "https://picsum.photos/seed/bus3/800/400" },
];
const currentOffer = ref(0);

/* =========================
   FORM STATE (FROM USER)
========================= */
const fromCity = ref("");
const toCity = ref("");
const travelDate = ref("");
const fromQuery = ref("");
const toQuery = ref("");
const tripType = ref("one-way");

/* =========================
   DROPDOWN STATE (FROM USER)
========================= */
const showFromDropdown = ref(false);
const showToDropdown = ref(false);
const fromActiveIndex = ref(-1);
const toActiveIndex = ref(-1);

/* =========================
   LOADING STATES (FROM USER)
========================= */
const loadingMainCities = ref(false);
const loadingMappedCities = ref(false);
const mainCities = ref([]);
const mappedCities = ref([]);
const error = ref(null);
const message = ref("");
const messageTone = ref("info");

/* =========================
   WATCHERS FOR SYNC (FROM USER)
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
        if (selected < today && selected.toDateString() !== today.toDateString()) {
            travelDate.value = todayISO();
            message.value = "Please select today or a future date.";
            messageTone.value = "error";
        }
    }
});

/* =========================
   API FUNCTIONS (FROM USER)
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
        }
    } catch (err) {
        error.value = "Failed to load cities. Please try again.";
    } finally {
        loadingMainCities.value = false;
    }
};

const loadMappedCities = async (cityId) => {
    try {
        loadingMappedCities.value = true;
        const response = await fetch("/api/city-mappings/mapped-cities", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ city_id: cityId }),
        });
        const data = await response.json();
        if (data.success) {
            mappedCities.value = data.data;
        } else {
            mappedCities.value = [];
        }
    } catch (err) {
        mappedCities.value = [];
    } finally {
        loadingMappedCities.value = false;
    }
};

/* =========================
   FILTERED CITIES (FROM USER)
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
   SELECT CITY FUNCTIONS (FROM USER)
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
   HELPERS (FROM USER)
========================= */
function todayISO() {
    const d = new Date();
    const pad = (n) => String(n).padStart(2, "0");
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

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

/* =========================
   LIFECYCLE (FROM USER)
========================= */
onMounted(() => {
    loadMainCities();
    if (!travelDate.value) travelDate.value = todayISO();

    setInterval(() => {
        currentOffer.value = (currentOffer.value + 1) % offers.length;
    }, 5000);

    document.addEventListener("click", (e) => {
        if (!e.target.closest("[data-from-container]")) showFromDropdown.value = false;
        if (!e.target.closest("[data-to-container]")) showToDropdown.value = false;
    });
});
</script>

<template>
    <!-- Google Fonts: Syne + DM Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500&display=swap"
        rel="stylesheet" />

    <div class=" bg-[#071520] text-[#e8fce3] font-['DM_Sans'] relative overflow-x-clip">
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

        <div class="relative z-10 py-14 lg:py-20 max-w-6xl px-4 mx-auto">

            <!-- ROW 1: HEADING & OFFER SLIDER -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 mb-16 items-center">

                <!-- HEADING BLOCK -->
                <div class="">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-12 h-px bg-[#4cbb2f]"></div>
                        <span class="text-xs font-bold uppercase tracking-[0.2em] text-[#4cbb2f]">
                            Pakistan's Express Network
                        </span>
                    </div>

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

                <!-- OFFER SLIDER BLOCK -->
                <div class="w-full">
                    <div
                        class="relative w-full h-64 lg:h-80 rounded-2xl overflow-hidden border border-[#4cbb2f]/20 shadow-2xl group">
                        <transition name="fade" mode="out-in">
                            <div :key="currentOffer" class="absolute inset-0">
                                <img :src="offers[currentOffer].image" :alt="offers[currentOffer].title"
                                    class="w-full h-full object-cover" referrerPolicy="no-referrer" />
                                <div
                                    class="absolute inset-0 bg-gradient-to-t from-[#071520] via-transparent to-transparent">
                                </div>
                                <div class="absolute bottom-6 left-6 right-6">
                                    <span
                                        class="px-3 py-1 bg-[#4cbb2f] text-[#071520] text-[10px] font-bold rounded-full uppercase tracking-widest mb-2 inline-block">
                                        Limited Offer
                                    </span>
                                    <h3 class="text-2xl font-bold font-['Syne'] text-[#e8fce3] mb-1">{{
                                        offers[currentOffer].title }}</h3>
                                    <p class="text-[#4cbb2f] font-mono font-bold">Use Code: {{ offers[currentOffer].code
                                    }}</p>
                                </div>
                            </div>
                        </transition>

                        <!-- SLIDER CONTROLS -->
                        <div
                            class="absolute top-1/2 -translate-y-1/2 left-4 right-4 flex justify-between opacity-0 group-hover:opacity-100 transition-opacity">
                            <button @click="currentOffer = (currentOffer - 1 + offers.length) % offers.length"
                                class="p-2 bg-[#0d2b3e]/50 backdrop-blur-md rounded-full text-[#e8fce3] hover:bg-[#4cbb2f] hover:text-[#071520] transition-all">
                                <ChevronLeft class="w-5 h-5" />
                            </button>
                            <button @click="currentOffer = (currentOffer + 1) % offers.length"
                                class="p-2 bg-[#0d2b3e]/50 backdrop-blur-md rounded-full text-[#e8fce3] hover:bg-[#4cbb2f] hover:text-[#071520] transition-all">
                                <ChevronRight class="w-5 h-5" />
                            </button>
                        </div>

                        <!-- SLIDER DOTS -->
                        <div class="absolute bottom-4 right-6 flex gap-2">
                            <div v-for="(_, i) in offers" :key="i" class="w-2 h-2 rounded-full transition-all"
                                :class="i === currentOffer ? 'bg-[#4cbb2f] w-6' : 'bg-[#e8fce3]/30'"></div>
                        </div>
                    </div>
                    <!-- STATS SECTION -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mt-6 pt-3 border-t border-white/5">
                        <div v-for="(stat, i) in [
                            { label: 'Main Cities', value: mainCities.length || '10+' },
                            { label: 'Daily Trips', value: '1,800+' },
                            { label: 'Happy Users', value: '50k+' },
                            { label: 'Support', value: '24/7' },
                        ]" :key="i" class="text-center">
                            <p class="text-3xl font-bold font-['Syne'] text-[#4cbb2f] mb-1">{{ stat.value }}</p>
                            <p class="text-[10px] uppercase tracking-[0.2em] text-white/20">{{ stat.label }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SIMPLE SEARCH FORM -->
            <div class="mt-10">
                <div class="bg-white rounded-lg shadow-lg p-4 lg:p-6 grid grid-cols-1 lg:grid-cols-4 gap-4">

                    <!-- FROM -->
                    <div class="relative" data-from-container>
                        <label class="text-xs text-gray-500 mb-1 block">From</label>
                        <input type="text" v-model="fromQuery" @focus="showFromDropdown = true"
                            @input="showFromDropdown = true; fromCity = fromQuery" placeholder="Enter city"
                            class="w-full border border-gray-200 rounded-lg px-4 py-3 outline-none focus:ring-2  text-primary focus:ring-green-500" />

                        <!-- Dropdown -->
                        <ul v-if="showFromDropdown && filteredFromCities.length"
                            class="absolute z-50 bg-white border text-primary mt-1 w-full rounded-lg shadow max-h-52 overflow-y-auto">
                            <li v-for="city in filteredFromCities" :key="city.Id" @click="selectFromCity(city)"
                                class="px-4 py-2 hover:bg-gray-100 cursor-pointer text-sm">
                                {{ city.CityName }}
                            </li>
                        </ul>
                    </div>

                    <!-- TO -->
                    <div class="relative" data-to-container>
                        <label class="text-xs text-gray-500 mb-1 block">To</label>
                        <input type="text" v-model="toQuery" :disabled="!fromCity"
                            @focus="fromCity && (showToDropdown = true)"
                            @input="showToDropdown = true; toCity = toQuery" placeholder="Enter destination"
                            class="w-full border border-gray-200 rounded-lg px-4 py-3 outline-none focus:ring-2  text-primary focus:ring-green-500 disabled:bg-gray-100" />

                        <!-- Dropdown -->
                        <ul v-if="showToDropdown && filteredToCities.length"
                            class="absolute z-50 bg-white text-primary border mt-1 w-full rounded-lg shadow max-h-52 overflow-y-auto">
                            <li v-for="city in filteredToCities" :key="city.Id" @click="selectToCity(city)"
                                class="px-4 py-2 hover:bg-gray-100 cursor-pointer text-sm">
                                {{ city.CityName }}
                            </li>
                        </ul>
                    </div>

                    <!-- DATE -->
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Date</label>
                        <DatePicker v-model="travelDate" placeholder="Select date" />
                    </div>

                    <!-- BUTTON -->
                    <div class="flex items-end">
                        <button @click="submit" :disabled="!fromCity || !toCity"
                            class="w-full bg-green-600 text-white py-3 rounded-lg font-semibold hover:bg-green-700 transition disabled:opacity-50">
                            Search
                        </button>
                    </div>
                </div>

                <!-- MESSAGE -->
                <div v-if="message" class="mt-4 text-center text-sm font-medium"
                    :class="messageTone === 'error' ? 'text-red-500' : 'text-green-600'">
                    {{ message }}
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.8s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-up-enter-active,
.slide-up-leave-active {
    transition: all 0.3s ease-out;
}

.slide-up-enter-from,
.slide-up-leave-to {
    opacity: 0;
    transform: translateY(10px);
}
</style>
