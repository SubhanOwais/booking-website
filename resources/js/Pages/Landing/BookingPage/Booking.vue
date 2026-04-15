<script setup>
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { computed, onMounted, ref, onUnmounted, watch, nextTick } from "vue";
import axios from "axios";

import SpecialBooking from "./SpecialBooking.vue";
import TripCard from "./TripCard.vue";
import LoginModal from "./LoginModal.vue";
import DatePicker from "@/Components/LandingPage/DatePicker.vue";
import WebLayout from "@/Layouts/WebLayout.vue";
import { searchBuses } from "@/services/busSearchService";

/* =========================
   PAGE / AUTH
========================= */
const page = usePage();

/* =========================
   CITIES - Using City Mappings
========================= */
const mainCities = ref([]); // Cities that have mappings (for FROM dropdown)
const mappedCities = ref([]); // Mapped cities based on selected FROM city (for TO dropdown)
const loadingCities = ref(false);
const loadingMappedCities = ref(false);

// Fetch main cities (cities with mappings)
async function fetchMainCities() {
    loadingCities.value = true;
    try {
        const response = await axios.get("/api/city-mappings/main-cities");
        if (response.data.success) {
            mainCities.value = response.data.data;
        }
    } catch (error) {
        console.error("Error fetching main cities:", error);
    } finally {
        loadingCities.value = false;
    }
}

// Fetch mapped cities for selected FROM city
async function fetchMappedCities(cityId) {
    if (!cityId) {
        mappedCities.value = [];
        return;
    }

    loadingMappedCities.value = true;
    try {
        const response = await axios.post("/api/city-mappings/mapped-cities", {
            city_id: cityId,
        });

        if (response.data.success) {
            mappedCities.value = response.data.data;
        } else {
            mappedCities.value = [];
        }
    } catch (error) {
        console.error("Error fetching mapped cities:", error);
        mappedCities.value = [];
    } finally {
        loadingMappedCities.value = false;
    }
}

/* =========================
   DROPDOWN STATE
========================= */
const showFromDropdown = ref(false);
const showToDropdown = ref(false);
const fromActiveIndex = ref(-1);
const toActiveIndex = ref(-1);

/* =========================
   FORM STATE
========================= */
const fromCity = ref("");
const toCity = ref("");
const travelDate = ref("");
const fromQuery = ref("");
const toQuery = ref("");
const selectedFromCityId = ref(null); // Store the selected FROM city ID

// Watch fromCity changes
watch(fromCity, async (newValue, oldValue) => {
    fromQuery.value = newValue;
    fromActiveIndex.value = -1;

    // Avoid double‑fetch if the value didn't really change
    if (newValue === oldValue) return;

    // Find the city object by name
    const city = mainCities.value.find(
        (c) => c.CityName.toLowerCase() === newValue.toLowerCase()
    );

    if (city) {
        selectedFromCityId.value = city.Id;
        await fetchMappedCities(city.Id);
    } else {
        selectedFromCityId.value = null;
        mappedCities.value = [];
    }

    // If the "To" city is the same as the new "From" city, clear it
    if (toCity.value.toLowerCase() === newValue.toLowerCase()) {
        toCity.value = "";
        toQuery.value = "";
    }
});

watch(toCity, (v) => {
    toQuery.value = v;
    toActiveIndex.value = -1;
});

/* =========================
   FILTERED CITIES
========================= */
const filteredFromCities = computed(() => {
    if (!fromQuery.value) {
        return mainCities.value.slice(0, 50);
    }

    const query = fromQuery.value.toLowerCase();
    return mainCities.value
        .filter((c) => c.CityName.toLowerCase().includes(query))
        .slice(0, 50);
});

const filteredToCities = computed(() => {
    let list = mappedCities.value;

    // Filter out the "from" city
    if (fromCity.value) {
        list = list.filter(
            (c) => c.CityName.toLowerCase() !== fromCity.value.toLowerCase()
        );
    }

    if (!toQuery.value) {
        return list.slice(0, 50);
    }

    const query = toQuery.value.toLowerCase();
    return list
        .filter((c) => c.CityName.toLowerCase().includes(query))
        .slice(0, 50);
});

/* =========================
   SELECT CITY
========================= */
async function selectFromCity(city) {
    fromCity.value = city.CityName;
    fromQuery.value = city.CityName;
    selectedFromCityId.value = city.Id;
    fromActiveIndex.value = -1;
    showFromDropdown.value = false;

    if (toCity.value === city.CityName) {
        toCity.value = "";
        toQuery.value = "";
    }

    await fetchMappedCities(city.Id);

    await nextTick();

    // ✅ Clear toQuery so all mapped cities are visible
    toQuery.value = "";

    const toInput = document.querySelector("[data-to-input]");
    if (toInput) toInput.focus();

    if (mappedCities.value.length > 0) {
        showToDropdown.value = true;
        toActiveIndex.value = -1;
    }
}

function selectToCity(city) {
    toCity.value = city.CityName;
    toQuery.value = city.CityName;
    toActiveIndex.value = -1;
    showToDropdown.value = false;

    // Focus date field
    nextTick(() => {
        const dateInput = document.querySelector('input[type="date"]');
        if (dateInput) {
            dateInput.focus();
        }
    });
}

/* =========================
   INPUT HANDLERS
========================= */
function onFromFocus() {
    fromQuery.value = "";
    showFromDropdown.value = true;
    fromActiveIndex.value = -1;

    if (!fromQuery.value) {
        fromQuery.value = "";
    }
}

function onFromInput(e) {
    const value = e.target.value;
    fromQuery.value = value;
    fromCity.value = value;
    fromActiveIndex.value = -1;
    showFromDropdown.value = true;
}

function onToFocus() {
    // Only show dropdown if we have a valid "From" city and mapped cities exist
    if (selectedFromCityId.value && mappedCities.value.length > 0) {
        toQuery.value = "";          // clear any filter so all mapped cities appear
        showToDropdown.value = true;
        toActiveIndex.value = -1;
    } else if (!selectedFromCityId.value) {
        // Optionally show a message or just do nothing
        console.warn("No 'From' city selected yet");
    }
}

function onToInput(e) {
    const value = e.target.value;
    toQuery.value = value;
    toCity.value = value;
    toActiveIndex.value = -1;

    // Show dropdown only if we have a valid "From" city and mapped cities exist
    if (selectedFromCityId.value && mappedCities.value.length > 0) {
        showToDropdown.value = true;
    } else {
        showToDropdown.value = false;
    }
}

/* =========================
   KEYBOARD NAVIGATION
========================= */
function scrollToActiveIndex(dropdownElement, activeIndex) {
    if (!dropdownElement || activeIndex === -1) return;

    const items = dropdownElement.querySelectorAll("li");
    if (items[activeIndex]) {
        items[activeIndex].scrollIntoView({
            behavior: "smooth",
            block: "nearest",
        });
    }
}

function handleFromKeydown(e) {
    if (!showFromDropdown.value) return;
    if (!filteredFromCities.value.length) {
        if (e.key === "Enter") {
            showFromDropdown.value = false;
        }
        return;
    }

    switch (e.key) {
        case "ArrowDown":
            e.preventDefault();
            fromActiveIndex.value =
                (fromActiveIndex.value + 1) % filteredFromCities.value.length;
            nextTick(() => {
                const dropdown = document.querySelector("[data-from-dropdown]");
                if (dropdown)
                    scrollToActiveIndex(dropdown, fromActiveIndex.value);
            });
            break;

        case "ArrowUp":
            e.preventDefault();
            fromActiveIndex.value =
                fromActiveIndex.value <= 0
                    ? filteredFromCities.value.length - 1
                    : fromActiveIndex.value - 1;
            nextTick(() => {
                const dropdown = document.querySelector("[data-from-dropdown]");
                if (dropdown)
                    scrollToActiveIndex(dropdown, fromActiveIndex.value);
            });
            break;

        case "Enter":
            e.preventDefault();
            if (fromActiveIndex.value >= 0) {
                selectFromCity(filteredFromCities.value[fromActiveIndex.value]);
            } else if (filteredFromCities.value.length === 1) {
                selectFromCity(filteredFromCities.value[0]);
            } else {
                showFromDropdown.value = false;
                fromActiveIndex.value = -1;
            }
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
    if (!showToDropdown.value) return;
    if (!filteredToCities.value.length) {
        if (e.key === "Enter") {
            showToDropdown.value = false;
        }
        return;
    }

    switch (e.key) {
        case "ArrowDown":
            e.preventDefault();
            toActiveIndex.value =
                (toActiveIndex.value + 1) % filteredToCities.value.length;
            nextTick(() => {
                const dropdown = document.querySelector("[data-to-dropdown]");
                if (dropdown)
                    scrollToActiveIndex(dropdown, toActiveIndex.value);
            });
            break;

        case "ArrowUp":
            e.preventDefault();
            toActiveIndex.value =
                toActiveIndex.value <= 0
                    ? filteredToCities.value.length - 1
                    : toActiveIndex.value - 1;
            nextTick(() => {
                const dropdown = document.querySelector("[data-to-dropdown]");
                if (dropdown)
                    scrollToActiveIndex(dropdown, toActiveIndex.value);
            });
            break;

        case "Enter":
            e.preventDefault();
            if (toActiveIndex.value >= 0) {
                selectToCity(filteredToCities.value[toActiveIndex.value]);
            } else if (filteredToCities.value.length === 1) {
                selectToCity(filteredToCities.value[0]);
            } else {
                showToDropdown.value = false;
                toActiveIndex.value = -1;
            }
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
   CLOSE DROPDOWN ON CLICK OUTSIDE
========================= */
onMounted(() => {
    if (!travelDate.value) {
        travelDate.value = todayISO();
    }

    // Close dropdowns when clicking outside
    document.addEventListener("click", (e) => {
        const fromInput = document.querySelector("[data-from-input]");
        const toInput = document.querySelector("[data-to-input]");

        const fromDropdown = e.target.closest("[data-from-dropdown]");
        const toDropdown = e.target.closest("[data-to-dropdown]");

        if (fromInput && !fromInput.contains(e.target) && !fromDropdown) {
            showFromDropdown.value = false;
        }

        if (toInput && !toInput.contains(e.target) && !toDropdown) {
            showToDropdown.value = false;
        }
    });

    // Clear previous state first
    clearAllState();

    // Your existing URL handling code...
    const q = getQuery();

    if (q.from && q.to && q.date) {
        // Only populate if URL has all parameters
        fromCity.value = q.from;
        fromQuery.value = q.from;
        toCity.value = q.to;
        toQuery.value = q.to;
        travelDate.value = q.date;

        // Find the city ID and fetch mapped cities
        const city = mainCities.value.find(
            (c) => c.CityName.toLowerCase() === q.from.toLowerCase()
        );
        if (city) {
            selectedFromCityId.value = city.Id;
            fetchMappedCities(city.Id);
        }

        // Auto-run search
        setTimeout(() => {
            const fromId = getCityIdByName(q.from);
            const toId = getCityIdByName(q.to);
            const date = q.date;

            if (fromId && toId && date) {
                runSearch({ auto: true });
            }
        }, 500);
    }
});

/* =========================
   CLEAR STATE ON NAVIGATION
========================= */

function clearAllState() {
    fromCity.value = "";
    toCity.value = "";
    travelDate.value = todayISO();
    fromQuery.value = "";
    toQuery.value = "";
    selectedFromCityId.value = null;
    mappedCities.value = [];
    showFromDropdown.value = false;
    showToDropdown.value = false;
    results.value = [];
    pendingTrip.value = null;
    statusText.value = "";
}

/* =========================
   STATUS
========================= */
const statusText = ref("");
const statusTone = ref("info");

function setStatus(text, tone = "info") {
    statusText.value = text;
    statusTone.value = tone;
}

/* =========================
   HELPERS
========================= */
function todayISO() {
    const d = new Date();
    const pad = (n) => String(n).padStart(2, "0");
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

watch(travelDate, (newDate) => {
    if (newDate) {
        const today = new Date(todayISO());
        const selected = new Date(newDate);

        if (
            selected < today &&
            selected.toDateString() !== today.toDateString()
        ) {
            travelDate.value = todayISO();
            setStatus("Please select today or a future date", "warning");

            nextTick(() => {
                const dateInput = document.querySelector('input[type="date"]');
                if (dateInput) {
                    dateInput.focus();
                }
            });
        }
    }
});

function getCityIdByName(value) {
    if (!value) return null;

    if (typeof value === "number") {
        return value;
    }

    if (typeof value === "object" && value.Id) {
        return value.Id;
    }

    if (typeof value === "string") {
        // Check in main cities first
        let city = mainCities.value.find(
            (c) =>
                c.CityName.trim().toLowerCase() === value.trim().toLowerCase()
        );

        // If not found, check in mapped cities
        if (!city) {
            city = mappedCities.value.find(
                (c) =>
                    c.CityName.trim().toLowerCase() ===
                    value.trim().toLowerCase()
            );
        }

        return city ? city.Id : null;
    }

    return null;
}

function updateUrlQuery({ fromName, toName, date }) {
    router.get(
        window.location.pathname,
        {
            from: fromName,
            to: toName,
            date: date,
        },
        {
            replace: true,
            preserveState: true,
            preserveScroll: true,
        }
    );
}

watch([fromCity, toCity, travelDate], ([from, to, date]) => {
    if (!from || !to || !date) return;

    updateUrlQuery({
        fromName: from,
        toName: to,
        date: date,
    });
});

/* =========================
   SEARCH API
========================= */
const results = ref([]);
const loadingResults = ref(false);
const loadingCompanies = ref([]);
const failedCompanies = ref([]);
let cancelSearch = null;

function runSearch({ auto = false } = {}) {
    if (loadingCities.value) {
        setStatus("Cities are still loading, please wait", "info");
        return;
    }

    if (travelDate.value) {
        const today = new Date(todayISO());
        const selected = new Date(travelDate.value);
        if (selected < today) {
            setStatus("Please select a future date", "error");
            return;
        }
    }

    const fromId = getCityIdByName(fromCity.value);
    const toId = getCityIdByName(toCity.value);
    const date = travelDate.value;

    if (!fromId || !toId || !date) {
        setStatus("Please select From, To and Date", "error");
        return;
    }
    if (fromId === toId) {
        setStatus("From and To cannot be the same", "error");
        return;
    }

    // Cancel any previous stream
    if (cancelSearch) { cancelSearch(); cancelSearch = null; }

    // Reset
    results.value = [];
    failedCompanies.value = [];
    loadingCompanies.value = [];
    loadingResults.value = true;
    setStatus("Connecting to providers...", "info");

    cancelSearch = searchBuses(fromId, toId, date, {

        onFetching({ company }) {
            loadingCompanies.value = [...loadingCompanies.value, company];
            setStatus(`Checking ${company}...`, "info");
        },

        onCompanyResult({ company, trips }) {
            // Add a unique ID to each trip (company name + serviceTypeId + index)
            const tripsWithUniqueId = trips.map((trip, idx) => ({
                ...trip,
                uniqueId: `${company}_${trip.serviceTypeId}_${idx}_${Date.now()}_${Math.random()}`
            }));
            loadingCompanies.value = loadingCompanies.value.filter(c => c !== company);
            results.value = [...results.value, ...tripsWithUniqueId];
            setStatus(`Found ${results.value.length} trip(s) so far...`, "info");
        },

        onDone({ total_trips }) {
            loadingResults.value = false;
            loadingCompanies.value = [];
            cancelSearch = null;

            setStatus(
                total_trips > 0
                    ? `Found ${total_trips} trip(s) available.`
                    : "No trips found for the selected route and date.",
                total_trips > 0 ? "success" : "info"
            );
        },

        onError({ company, reason }) {
            if (company) {
                loadingCompanies.value = loadingCompanies.value.filter(c => c !== company);
                failedCompanies.value = [...failedCompanies.value, company];
            }
            console.warn(`Provider failed: ${company} — ${reason}`);
        },
    });
}

/* =========================
   LOGIN / SEAT FLOW
========================= */
const showLoginModal = ref(false);
const pendingTrip = ref(null);

function handleSelectSeat(payload) {
    const trip = payload.trip || payload;
    const time =
        payload.time ||
        trip.departureTime ||
        (trip.departureTimes && trip.departureTimes[0]) ||
        trip.departure;

    const scheduleId = payload.scheduleId || trip.scheduleId;
    const price = payload.price || trip.price;
    const seatsAvailable = payload.seatsAvailable || trip.seatsLeft;

    const fromId = trip.fromCityId || getCityIdByName(fromCity.value);
    const toId = trip.toCityId || getCityIdByName(toCity.value);

    console.log("Seat selection data:", {
        trip,
        time,
        scheduleId,
        price,
        seatsAvailable,
        fromId,
        toId,
        fromCity: fromCity.value,
        toCity: toCity.value,
        date: travelDate.value,
    });

    const isAuthenticated = page.props.auth?.user;

    if (!isAuthenticated) {
        pendingTrip.value = {
            trip,
            time,
            scheduleId,
            price,
            seatsAvailable,
            fromId,
            toId,
            fromCity: fromCity.value,
            toCity: toCity.value,
            date: travelDate.value,
        };
        showLoginModal.value = true;
        return;
    }

    // Build query parameters – clean, raw values
    const queryParams = {
        date: travelDate.value,
        from: fromCity.value,
        to: toCity.value,
        serviceTypeId: trip.serviceTypeId || '',
        departureTime: time,
        scheduleId: scheduleId || '',
        price: price || 0,
        seatsAvailable: seatsAvailable || 0,
        busType: trip.busType || '',
        busService: trip.busService || trip.company || '',
        fromId: fromId || '',
        toId: toId || '',
        company: trip.company || trip.busService || '',
        operator_id: trip.operator_id || '',
        companylogo: trip.logo || '',
        discount: trip.discount || null,
        discounted_price: trip.discounted_price || null,
        total_fare: trip.total_fare || null,
        seat_20_fare: trip.seat_20_fare || null,
        seat_4_fare: trip.seat_4_fare || null,
        discount_percentage: trip.discount_percentage || null,
        extra_fare: trip.extra_fare || null,
    };

    // Remove empty values
    Object.keys(queryParams).forEach(key => {
        const val = queryParams[key];
        if (val === '' || val === null || val === undefined) {
            delete queryParams[key];
        }
    });

    console.log("Navigating to seat-detail with params:", queryParams);

    // Try Inertia navigation; if it fails, fallback to window.location
    try {
        // Use router.visit for more control (preserveState: false ensures a fresh page)
        router.visit(route('seat-detail'), {
            method: 'get',
            data: queryParams,
            preserveState: false,
            preserveScroll: false,
            replace: false,
            onError: (errors) => {
                console.error("Inertia navigation error:", errors);
                // Fallback to traditional navigation
                const url = new URL(route('seat-detail'), window.location.origin);
                Object.keys(queryParams).forEach(key => url.searchParams.append(key, queryParams[key]));
                window.location.href = url.toString();
            }
        });
    } catch (error) {
        console.error("Error during navigation:", error);
        // Fallback
        const url = new URL(route('seat-detail'), window.location.origin);
        Object.keys(queryParams).forEach(key => url.searchParams.append(key, queryParams[key]));
        window.location.href = url.toString();
    }
}

function handleLoginSuccess() {
    if (!pendingTrip.value) return;

    const {
        trip,
        time,
        scheduleId,
        price,
        seatsAvailable,
        fromId,
        toId,
        fromCity: fromCityName,
        toCity: toCityName,
        date,
    } = pendingTrip.value;

    const queryParams = {
        date: date || travelDate.value,
        from: fromCityName || fromCity.value,
        to: toCityName || toCity.value,
        serviceTypeId: trip.serviceTypeId || '',
        departureTime: time,
        scheduleId: scheduleId || '',
        price: price || 0,
        seatsAvailable: seatsAvailable || 0,
        busType: trip.busType || '',
        busService: trip.busService || trip.company || '',
        fromId: fromId || trip.fromCityId || getCityIdByName(fromCityName || fromCity.value) || '',
        toId: toId || trip.toCityId || getCityIdByName(toCityName || toCity.value) || '',
        company: trip.company || trip.busService || '',
        operator_id: trip.operator_id || '',
        companylogo: trip.logo || '',
        discount: trip.discount || null,
        discounted_price: trip.discounted_price || null,
        total_fare: trip.total_fare || null,
        seat_20_fare: trip.seat_20_fare || null,
        seat_4_fare: trip.seat_4_fare || null,
        discount_percentage: trip.discount_percentage || null,
        extra_fare: trip.extra_fare || null,
    };

    Object.keys(queryParams).forEach(key => {
        const val = queryParams[key];
        if (val === '' || val === null || val === undefined) {
            delete queryParams[key];
        }
    });

    console.log("Login success, navigating to seat-detail:", queryParams);

    try {
        router.visit(route('seat-detail'), {
            method: 'get',
            data: queryParams,
            preserveState: false,
            preserveScroll: false,
            replace: false,
            onError: (errors) => {
                console.error("Inertia navigation error:", errors);
                const url = new URL(route('seat-detail'), window.location.origin);
                Object.keys(queryParams).forEach(key => url.searchParams.append(key, queryParams[key]));
                window.location.href = url.toString();
            }
        });
    } catch (error) {
        console.error("Error during navigation:", error);
        const url = new URL(route('seat-detail'), window.location.origin);
        Object.keys(queryParams).forEach(key => url.searchParams.append(key, queryParams[key]));
        window.location.href = url.toString();
    }

    pendingTrip.value = null;
}

/* =========================
   INIT FROM URL
========================= */
function getQuery() {
    const p = new URLSearchParams(window.location.search);
    return {
        from: p.get("from") || "",
        to: p.get("to") || "",
        date: p.get("date") || "",
    };
}

watch(
    () => [fromCity.value, toCity.value, travelDate.value],
    ([newFrom, newTo, newDate]) => {
        if (newFrom && newTo && newDate) {
            updateUrlQuery({
                fromName: newFrom,
                toName: newTo,
                date: newDate,
            });
        }
    },
    { immediate: true }
);

// onMounted — same fix
onMounted(async () => {
    await fetchMainCities();

    const q = getQuery();

    // If URL has a "from" parameter, set it and load its mapped cities
    if (q.from) {
        fromCity.value = q.from;
        fromQuery.value = q.from;

        // Find the city object and set ID + fetch mapped cities
        const city = mainCities.value.find(
            c => c.CityName.toLowerCase() === q.from.toLowerCase()
        );
        if (city) {
            selectedFromCityId.value = city.Id;
            await fetchMappedCities(city.Id);
        }
    }

    if (q.to) {
        toCity.value = q.to;
        toQuery.value = q.to;
    }

    if (q.date) travelDate.value = q.date;

    // Wait for mapped cities to load before running search
    await nextTick();

    const fromId = getCityIdByName(q.from);
    const toId = getCityIdByName(q.to);

    if (fromId && toId && q.date) {
        setTimeout(() => runSearch({ auto: true }), 300);
    }

    // Handle browser back/forward
    window.addEventListener("popstate", async () => {
        setTimeout(async () => {
            const q = getQuery();
            fromCity.value = q.from || "";
            toCity.value = q.to || "";
            travelDate.value = q.date || todayISO();
            fromQuery.value = q.from || "";
            toQuery.value = q.to || "";

            // Re‑fetch mapped cities when "from" changes via popstate
            if (q.from) {
                const city = mainCities.value.find(
                    c => c.CityName.toLowerCase() === q.from.toLowerCase()
                );
                if (city) {
                    selectedFromCityId.value = city.Id;
                    await fetchMappedCities(city.Id);
                }
            } else {
                selectedFromCityId.value = null;
                mappedCities.value = [];
            }

            await nextTick();
            const fromId = getCityIdByName(q.from);
            const toId = getCityIdByName(q.to);
            if (fromId && toId && q.date) runSearch({ auto: true });
        }, 150);
    });

    // Close dropdowns when clicking outside
    document.addEventListener("click", (e) => {
        const fromInput = document.querySelector("[data-from-input]");
        const toInput = document.querySelector("[data-to-input]");

        const fromDropdown = e.target.closest("[data-from-dropdown]");
        const toDropdown = e.target.closest("[data-to-dropdown]");

        if (fromInput && !fromInput.contains(e.target) && !fromDropdown) {
            showFromDropdown.value = false;
        }

        if (toInput && !toInput.contains(e.target) && !toDropdown) {
            showToDropdown.value = false;
        }
    });
});

const dateBadge = computed(() => travelDate.value || "—");
const resultsCountText = computed(() => {
    if (loadingResults.value) return "Loading...";
    if (results.value.length === 0) return "No trips found";
    return `${results.value.length} trip${results.value.length > 1 ? "s" : ""
        } found`;
});

const expandedCardId = ref(null);

function handleCardToggle(tripUniqueId) {
    expandedCardId.value = expandedCardId.value === tripUniqueId ? null : tripUniqueId;
}
</script>

<template>
    <WebLayout class="bg-slate-50 text-ink">
        <!-- Header strip -->
        <header class="relative overflow-hidden">
            <div class="absolute inset-0 z-0">
                <div class="absolute inset-0 bg-gradient-to-br from-primary via-primary to-slate-900"></div>
                <div class="absolute rounded-full -top-40 -left-32 h-96 w-96 bg-secondary/25 blur-3xl"></div>
                <div class="absolute rounded-full -bottom-40 -right-32 h-96 w-96 bg-accent/25 blur-3xl"></div>
                <div class="absolute inset-0 opacity-20" style="
                        background-image: radial-gradient(
                            circle at 1px 1px,
                            rgba(255, 255, 255, 0.12) 1px,
                            transparent 0
                        );
                        background-size: 22px 22px;
                    "></div>
            </div>

            <div class="relative z-20 max-w-6xl px-4 py-10 mx-auto sm:py-12">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm text-white/80">Booking</p>
                        <h1 class="mt-1 text-2xl font-bold tracking-tight text-white sm:text-3xl">
                            Search & Book Buses
                        </h1>
                        <p class="max-w-2xl mt-2 text-white/75">
                            Select route and date to view available trips,
                            pricing, and amenities.
                        </p>
                    </div>

                    <Link :href="route('home')"
                        class="inline-flex items-center justify-center rounded-xl border border-white/25 bg-white/10 px-4 py-2 text-sm font-semibold text-white backdrop-blur transition hover:-translate-y-0.5 hover:bg-white/15">
                        Back to Home
                    </Link>
                </div>
            </div>
        </header>

        <main class="max-w-6xl px-4 py-10 mx-auto">
            <!-- SEARCH PANEL -->
            <section class="p-6 bg-white border rounded-2xl shadow-soft">
                <form class="grid gap-4 md:grid-cols-12" @submit.prevent="runSearch({ auto: false })">
                    <!-- FROM CITY -->
                    <div class="relative md:col-span-3">
                        <label class="block text-sm font-semibold text-slate-700">
                            From City
                        </label>

                        <input v-model="fromCity" @focus="onFromFocus" @input="onFromInput" @keydown="handleFromKeydown"
                            @blur="showFromDropdown = false" placeholder="Search city"
                            class="w-full px-4 py-3 mt-1 transition bg-white border outline-none rounded-xl border-slate-900/10 focus:border-secondary/60 focus:ring-2 focus:ring-secondary/25" />

                        <ul v-if="showFromDropdown && filteredFromCities.length" data-from-dropdown
                            class="absolute z-50 w-full mt-1 overflow-auto bg-white border max-h-56 rounded-xl border-slate-900/10 shadow-card">
                            <li v-for="(c, i) in filteredFromCities" :key="c.id" @click="selectFromCity(c, $event)"
                                @mousedown.prevent :class="[
                                    'cursor-pointer px-4 py-2 text-sm',
                                    i === fromActiveIndex
                                        ? 'bg-secondary/10'
                                        : 'hover:bg-secondary/10',
                                ]">
                                {{ c.CityName }}
                            </li>
                        </ul>
                    </div>

                    <!-- TO CITY -->
                    <div class="relative md:col-span-3">
                        <label class="block text-sm font-semibold text-slate-700">
                            To City
                        </label>

                        <input v-model="toCity" @focus="onToFocus" @input="onToInput" @keydown="handleToKeydown"
                            @blur="showToDropdown = false" :disabled="!fromCity" placeholder="Search city"
                            class="w-full px-4 py-3 mt-1 transition bg-white border outline-none rounded-xl border-slate-900/10 focus:border-secondary/60 focus:ring-2 focus:ring-secondary/25 disabled:bg-slate-100" />

                        <ul v-if="showToDropdown && filteredToCities.length" data-to-dropdown
                            class="absolute z-50 w-full mt-1 overflow-auto bg-white border max-h-56 rounded-xl border-slate-900/10 shadow-card">
                            <li v-for="(c, i) in filteredToCities" :key="c.id" @click="selectToCity(c, $event)"
                                @mousedown.prevent :class="[
                                    'cursor-pointer px-4 py-2 text-sm',
                                    i === toActiveIndex
                                        ? 'bg-secondary/10'
                                        : 'hover:bg-secondary/10',
                                ]">
                                {{ c.CityName }}
                            </li>
                        </ul>
                    </div>

                    <div class="md:col-span-4">
                        <div class="md:col-span-3">
                            <label class="block text-sm font-semibold mb-0.5 text-slate-700">
                                Date
                            </label>
                            <DatePicker v-model="travelDate" label="Travel Date" placeholder="Select date" />
                        </div>
                    </div>

                    <div class="flex items-end md:col-span-2">
                        <button type="submit"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-[14px] text-sm font-semibold text-white shadow-soft transition hover:-translate-y-0.5 hover:shadow-card">
                            Search
                        </button>
                    </div>
                </form>
            </section>

            <!-- RESULTS -->
            <section class="mt-8">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold">
                        {{ resultsCountText }}
                    </span>
                    <span class="px-2 py-1 font-mono text-sm font-medium text-white rounded-md shadow-sm bg-secondary">
                        {{ dateBadge }}
                    </span>
                </div>

                <div v-if="loadingResults && results.length === 0" class="mt-6 space-y-4">
                    <!-- Loading trips... -->
                    <div v-for="i in 3" :key="i"
                        class="p-5 bg-white border animate-pulse rounded-2xl border-slate-200 shadow-soft">
                        <div class="flex justify-between">
                            <div class="space-y-3">
                                <div class="w-40 h-4 rounded bg-slate-200"></div>
                                <div class="h-3 rounded w-28 bg-slate-200"></div>
                            </div>
                            <div class="w-20 h-8 rounded bg-slate-200"></div>
                        </div>
                        <div class="w-full h-3 mt-4 rounded bg-slate-200"></div>
                    </div>
                </div>

                <!-- Update your results section -->
                <div v-if="results.length > 0" class="grid gap-4 mt-5">
                    <TripCard v-for="t in results" :key="t.serviceTypeId" :trip="t" :travel-date="travelDate"
                        :open="expandedCardId === t.serviceTypeId" @toggle="handleCardToggle(t.serviceTypeId)"
                        @select-seat="handleSelectSeat" />
                </div>

                <div v-if="!loadingResults && results.length === 0"
                    class="p-6 mt-6 text-center bg-white rounded-2xl shadow-soft">
                    <p class="font-semibold">No results found</p>
                    <p class="text-sm text-slate-600">
                        No trips available for your search criteria.
                    </p>
                    <p class="mt-2 text-xs text-slate-500">
                        From: {{ fromCity?.CityName || fromCity }} → To:
                        {{ toCity?.CityName || toCity }} on {{ travelDate }}
                    </p>
                </div>
            </section>

            <SpecialBooking />
        </main>

        <LoginModal :show="showLoginModal" @close="showLoginModal = false" @success="handleLoginSuccess" />
    </WebLayout>
</template>
