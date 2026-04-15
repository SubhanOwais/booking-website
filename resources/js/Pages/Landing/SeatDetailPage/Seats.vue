<template>
    <!-- ======================================================================
         SeatGrid.vue
         Main seat-selection panel. Renders the bus plane layout, handles
         seat hold / unhold via API, pops a gender-selection modal before
         confirming a selection, and syncs chosen seats to the parent via emits.
         ====================================================================== -->
    <section class="p-6 bg-white border rounded-2xl border-slate-900/10 shadow-card lg:sticky lg:top-6 lg:self-start">

        <!-- ── Header ──────────────────────────────────────────────────────── -->
        <div class="mb-6">
            <h2 class="text-xl font-bold text-slate-900">
                {{ serviceName }}
            </h2>
            <p class="mt-1 text-sm text-slate-600">
                Click on available seats to select or deselect them
            </p>

            <!-- Loading state — shown on first load only -->
            <div v-if="initialLoading" class="flex items-center justify-center py-8 mt-4">
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 border-b-2 rounded-full animate-spin border-primary"></div>
                    <p class="mt-2 text-sm text-slate-600">Loading seat information...</p>
                </div>
            </div>

            <!-- Error state — shown when API call failed (demo data is used instead) -->
            <div v-if="error && !initialLoading" class="p-4 mt-4 rounded-lg bg-red-50">
                <p class="text-sm text-red-700">{{ error }}</p>
                <p class="mt-1 text-xs text-red-600">
                    Showing demo data. Real-time updates may not work.
                </p>
            </div>
        </div>

        <!-- ── Legend ──────────────────────────────────────────────────────── -->
        <div class="grid grid-cols-2 gap-3 p-4 mb-6 border sm:grid-cols-4 rounded-xl
                    bg-gradient-to-br from-slate-50 to-slate-100 border-slate-200">
            <div class="flex items-center gap-2">
                <SeatIcon color="text-blue-600" />
                <span class="text-xs font-semibold text-slate-700">Available</span>
            </div>
            <div class="flex items-center gap-2">
                <SeatIcon color="text-orange-600" />
                <span class="text-xs font-semibold text-slate-700">Selected</span>
            </div>
            <div class="flex items-center gap-2">
                <SeatIcon color="text-purple-700" />
                <span class="text-xs font-semibold text-slate-700">Male</span>
            </div>
            <div class="flex items-center gap-2">
                <SeatIcon color="text-pink-600" />
                <span class="text-xs font-semibold text-slate-700">Female</span>
            </div>
        </div>

        <!-- ── Seat Layout ─────────────────────────────────────────────────── -->
        <div v-if="!initialLoading && currentBusConfig" class="relative">

            <!-- Background loading overlay — shown during silent background refresh -->
            <div v-if="backgroundLoading"
                class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 rounded-xl">
                <div class="w-6 h-6 border-b-2 border-blue-500 rounded-full animate-spin"></div>
            </div>

            <!-- Seat Sections — iterated from config (can be 1 or 2 sections e.g. Business + Executive) -->
            <div class="space-y-6">
                <div v-for="(section, sectionIndex) in currentBusConfig.sections" :key="sectionIndex"
                    class="p-4 border rounded-xl" :class="getSectionClass(section.type)">
                    <!-- Section Header Label -->
                    <div class="mb-3 text-center">
                        <span
                            class="inline-block px-3 py-1 text-xs font-bold rounded-full shadow-sm bg-white/70 text-slate-700">
                            {{ section.name }}
                        </span>
                    </div>

                    <!-- Seat Grid — horizontally scrollable on small screens -->
                    <div class="flex justify-center pb-2 overflow-x-auto">
                        <div class="inline-block">
                            <div class="space-y-3">
                                <!-- Each row of the bus (front to back) -->
                                <div v-for="(row, rowIndex) in section.rows" :key="rowIndex"
                                    class="flex items-center gap-3">
                                    <!--
                                        Each column cell — either a seat button or an empty spacer.
                                        • null   → invisible spacer (preserves aisle gap)
                                        • string → seat number, resolved to a seat object via getSeatByNumber()
                                    -->
                                    <button v-for="(seatNumber, colIndex) in row.cols" :key="colIndex" type="button"
                                        :class="getSeatClass(getSeatByNumber(seatNumber))"
                                        @click="toggleSeat(getSeatByNumber(seatNumber))" :disabled="!seatNumber ||
                                            !getSeatByNumber(seatNumber)?.isAvailable
                                            ">
                                        <!-- Real seat cell -->
                                        <div v-if="seatNumber" class="relative">
                                            <SeatIcon :color="getSeatColor(getSeatByNumber(seatNumber))"
                                                :seat-number="seatNumber" />
                                            <!-- Orange dot badge when seat is selected by current user -->
                                            <div v-if="getSeatByNumber(seatNumber)?.isSelected"
                                                class="absolute w-3 h-3 bg-orange-500 border-2 border-white rounded-full -top-1 -right-1">
                                            </div>
                                        </div>
                                        <!-- Invisible spacer for null cells -->
                                        <div v-else class="w-10 h-10"></div>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════════════════
             GENDER SELECTION POPUP
             Appears as a full-screen backdrop with a centred card.
             Fires when a user clicks an available seat (before holding it).
             Selecting a gender triggers proceedHoldSeat() which calls the API.
             Clicking outside or pressing × cancels without holding the seat.
             ══════════════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition name="fade">
                <div v-if="showGenderModal"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
                    @click.self="cancelGenderSelect">
                    <div class="bg-white rounded-2xl shadow-2xl p-6 w-72 border border-slate-200 animate-scale-in">

                        <!-- Modal Header -->
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Select Gender</h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Seat
                                    <span class="font-semibold text-slate-700">
                                        {{ pendingSeat?.label }}
                                    </span>
                                </p>
                            </div>
                            <!-- Close / cancel button -->
                            <button @click="cancelGenderSelect" class="w-7 h-7 flex items-center justify-center rounded-full
                                       hover:bg-slate-100 text-slate-400 hover:text-slate-600
                                       transition-colors text-lg leading-none">×</button>
                        </div>

                        <!-- Gender Option Buttons -->
                        <div class="grid grid-cols-2 gap-3">

                            <!-- Male -->
                            <button @click="confirmGenderAndSelect('male')" class="flex flex-col items-center gap-2 p-4 rounded-xl border-2
                                       border-purple-200 bg-purple-50 hover:bg-purple-100
                                       hover:border-purple-400 transition-all group">
                                <div class="w-10 h-10 rounded-full bg-purple-100 group-hover:bg-purple-200
                                            flex items-center justify-center transition-colors">
                                    <!-- Male symbol icon -->
                                    <svg class="w-6 h-6 text-purple-700" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M9 9a3 3 0 1 1 6 0 3 3 0 0 1-6 0zm3-5a5 5 0 1 0 3.536 8.536L17
                                                 14h-2v2h2v2h2v-2h1v-2h-1l-1.464-1.464A5 5 0 0 0 12 4z" />
                                    </svg>
                                </div>
                                <span class="text-sm font-bold text-purple-700">Male</span>
                            </button>

                            <!-- Female -->
                            <button @click="confirmGenderAndSelect('female')" class="flex flex-col items-center gap-2 p-4 rounded-xl border-2
                                       border-pink-200 bg-pink-50 hover:bg-pink-100
                                       hover:border-pink-400 transition-all group">
                                <div class="w-10 h-10 rounded-full bg-pink-100 group-hover:bg-pink-200
                                            flex items-center justify-center transition-colors">
                                    <!-- Female symbol icon -->
                                    <svg class="w-6 h-6 text-pink-600" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2a5 5 0 1 1 0 10A5 5 0 0 1 12 2zm0 12c2.67 0 8 1.34 8
                                                 4v2H4v-2c0-2.66 5.33-4 8-4z" />
                                    </svg>
                                </div>
                                <span class="text-sm font-bold text-pink-600">Female</span>
                            </button>
                        </div>

                        <p class="text-xs text-slate-400 text-center mt-3">
                            Gender is required for ticket issuance
                        </p>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ── Selected Seats Summary ───────────────────────────────────────── -->
        <div v-if="selectedSeats.length > 0" class="p-4 mt-6 border rounded-xl bg-secondary/5 border-secondary/20">
            <p class="mb-2 text-sm font-semibold text-slate-900">
                Selected Seats ({{ selectedSeats.length }})
            </p>

            <!-- Badge list — colour-coded by gender chosen in the popup -->
            <div class="flex flex-wrap gap-2">
                <span v-for="selectedSeat in selectedSeats" :key="selectedSeat.id"
                    class="inline-flex items-center gap-1 px-3 py-1 text-xs font-bold text-white rounded-lg" :class="selectedSeat.gender?.toLowerCase() === 'female'
                        ? 'bg-pink-500'
                        : 'bg-purple-700'
                        ">
                    <!-- Gender icon inside badge -->
                    <svg v-if="selectedSeat.gender?.toLowerCase() === 'female'" class="w-3 h-3" fill="currentColor"
                        viewBox="0 0 24 24">
                        <path
                            d="M12 2a5 5 0 1 1 0 10A5 5 0 0 1 12 2zm0 12c2.67 0 8 1.34 8 4v2H4v-2c0-2.66 5.33-4 8-4z" />
                    </svg>
                    <svg v-else class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M9 9a3 3 0 1 1 6 0 3 3 0 0 1-6 0zm3-5a5 5 0 1 0 3.536 8.536L17
                                 14h-2v2h2v2h2v-2h1v-2h-1l-1.464-1.464A5 5 0 0 0 12 4z" />
                    </svg>

                    {{ selectedSeat.label }}

                    <!-- Remove button inside badge -->
                    <button @click="deselectSeat(selectedSeat)"
                        class="ml-1 hover:bg-white/20 rounded-full p-0.5">×</button>
                </span>
            </div>

            <!-- Total fare row -->
            <div class="pt-3 mt-3 border-t border-secondary/20">
                <p class="text-sm font-semibold text-slate-900">
                    Total Price: {{ priceText }}
                </p>
            </div>

            <!-- Hold timer reminder -->
            <p class="mt-2 text-xs text-slate-600">
                These seats are temporarily held for you. Complete booking within 5 minutes.
            </p>
        </div>

        <!-- ── Live update indicator ────────────────────────────────────────── -->
        <div v-if="!error" class="flex items-center justify-center mt-6">
            <div class="flex items-center gap-2 px-3 py-1 rounded-full bg-slate-100">
                <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                <span class="text-xs text-slate-600">Live seat updates active</span>
            </div>
        </div>

    </section>
</template>

<script setup>
// =============================================================================
// Imports
// =============================================================================
import {
    onMounted,
    ref,
    computed,
    onUnmounted,
    watch,
    onBeforeUnmount,
} from "vue";
import axios from "axios";
import SeatIcon from "./SeatIcon.vue";

// Seat service helpers — fetchSeats, holdSeat, unholdSeat and status constants
import {
    fetchSeats,
    holdSeat,
    unholdSeat,
    SEAT_STATUS,
} from "@/services/seatService";

// ─────────────────────────────────────────────────────────────────────────────
// Bus-plane layout definitions live in a dedicated store file.
// busServiceConfigs  — full config map (kept available if needed elsewhere)
// getConfigByServiceTypeId() — resolves the correct layout by serviceTypeId
// ─────────────────────────────────────────────────────────────────────────────
import {
    busServiceConfigs,
    getConfigByServiceTypeId,
} from "@/services/SeatPlaneDesign";

// =============================================================================
// Component Interface (emits + props)
// =============================================================================
const emit = defineEmits([
    "update:selected-seats",      // primary emit — array of selected seat objects
    "update:selected-seats-data", // mirror emit — kept for backwards compatibility
]);

const props = defineProps({
    /** Currently selected seats array — managed externally by the parent */
    selectedSeats: Array,

    /** Full trip object (optional, kept for backwards compat) */
    tripData: Object,

    /** Origin city ID — used for API call context */
    fromCityId: String,

    /** Destination city ID — used for API call context */
    toCityId: String,

    /** Travel date string — used for API call context */
    travelDate: String,

    /** Departure time string */
    selectedDepartureTime: String,

    /** Human-readable bus service name shown in the section header */
    busServiceName: String,

    /**
     * Service type ID string (e.g. "13", "14" …).
     * Determines which seat-plane layout is rendered from SeatPlaneDesign.js.
     */
    busServiceType: {
        type: String,
        required: true,
    },
});

// =============================================================================
// Reactive State
// =============================================================================

/** Flat array of all seat objects built from the API response / demo data */
const seats = ref([]);

/** True only during the very first load — shows the full-screen spinner */
const initialLoading = ref(true);

/** True during background silent refreshes — shows a subtle overlay */
const backgroundLoading = ref(false);

/** Non-null error string when the API fails on initial load */
const error = ref(null);

// ── Gender Modal State ─────────────────────────────────────────────────────

/** Controls visibility of the gender-selection popup */
const showGenderModal = ref(false);

/** Stores the seat awaiting gender confirmation; cleared on confirm / cancel */
const pendingSeat = ref(null);

// =============================================================================
// Computed Properties
// =============================================================================

/**
 * Resolves the active bus-plane layout config using the busServiceType prop.
 * Delegates to getConfigByServiceTypeId() which returns Executive (id:13)
 * as a safe fallback when no match is found.
 */
const currentBusConfig = computed(() => {
    return getConfigByServiceTypeId(props.busServiceType || "");
});

/** Display name for the bus service — from prop or resolved config */
const serviceName = computed(() => {
    return props.busServiceName || currentBusConfig.value?.name || "Bus Service";
});

/** Safe selectedSeats accessor — always an array even if prop is undefined */
const selectedSeats = computed(() => props.selectedSeats || []);

/** Sum of all selected-seat fares (post-discount) */
const totalFare = computed(() => {
    return selectedSeats.value.reduce((sum, seat) => sum + (seat.fare || 0), 0);
});

/** PKR-formatted total fare display string */
const priceText = computed(() => {
    return new Intl.NumberFormat("en-PK", {
        style: "currency",
        currency: "PKR",
        maximumFractionDigits: 0,
    }).format(totalFare.value);
});

/** Company name decoded from URL search params */
const companyName = computed(() => {
    const urlParams = new URLSearchParams(window.location.search);
    return decodeURIComponent(urlParams.get("company") || "");
});

/** Schedule ID decoded from URL search params */
const scheduleId = computed(() => {
    const urlParams = new URLSearchParams(window.location.search);
    return decodeURIComponent(urlParams.get("scheduleId") || "");
});

// =============================================================================
// API Helper — Convert raw API seat status string → internal SEAT_STATUS constant
// =============================================================================

/**
 * Maps the raw status string from the API response to our internal SEAT_STATUS enum.
 *
 * Handles two field name variants:
 *  • seat_status — newer camelCase API
 *  • Status      — legacy PascalCase API
 *
 * Note: Some operators return "Empty" for available seats, which we normalise
 * to AVAILABLE so the seat renders as clickable.
 *
 * @param {Object} apiSeat — raw seat object from the API response
 * @returns {string}       — one of SEAT_STATUS.AVAILABLE | RESERVED | HOLD | EMPTY
 */
function getSeatStatusFromAPI(apiSeat) {
    if (!apiSeat) return SEAT_STATUS.EMPTY;

    const status = apiSeat.seat_status || apiSeat.Status || "Empty";

    switch (status.toLowerCase()) {
        case "available":
            return SEAT_STATUS.AVAILABLE;
        case "reserved":
        case "booked":
            return SEAT_STATUS.RESERVED;
        case "hold":
            return SEAT_STATUS.HOLD;
        case "empty":
            // Operators sometimes return "Empty" meaning unoccupied / available
            return SEAT_STATUS.AVAILABLE;
        default:
            return SEAT_STATUS.AVAILABLE;
    }
}

// =============================================================================
// API — loadSeats  (initial load + background refresh)
// =============================================================================

/** Handle for the setInterval that triggers background seat refreshes */
let seatInterval = null;
let sessionWatchInterval = null; // NEW

/**
 * Fetches fresh seat availability data from the API.
 *
 * @param {boolean} showLoader
 *   • true  — first mount call; shows full-screen spinner, populates demo on failure
 *   • false — background refresh; silently updates seats without UI disruption
 *
 * Flow:
 *  1. Read all required booking params from URL search params.
 *  2. Validate every param is present — throw early if not.
 *  3. Delegate to fetchSeats() service helper.
 *  4. On success  → processSeatData() then restoreSavedSeats().
 *  5. On failure  → if first load, set error + call initializeDemoSeats().
 */
async function loadSeats(showLoader = false) {
    try {
        if (showLoader) initialLoading.value = true;

        // All booking-context params live in the URL query string
        const urlParams = new URLSearchParams(window.location.search);

        const params = {
            company: decodeURIComponent(urlParams.get("company") || ""),
            operator_id: decodeURIComponent(urlParams.get("operator_id") || ""),
            from: decodeURIComponent(urlParams.get("fromId") || ""),
            to: decodeURIComponent(urlParams.get("toId") || ""),
            date: decodeURIComponent(urlParams.get("date") || ""),
            time: decodeURIComponent(urlParams.get("departureTime") || ""),
            scheduleId: decodeURIComponent(urlParams.get("scheduleId") || ""),
            serviceTypeId: decodeURIComponent(urlParams.get("serviceTypeId") || ""),
        };

        // Guard — every field is mandatory; bail out immediately if any are absent
        if (
            !params.company ||
            !params.operator_id ||
            !params.from ||
            !params.to ||
            !params.date ||
            !params.time ||
            !params.serviceTypeId ||
            !params.scheduleId
        ) {
            throw new Error("Missing required parameters");
        }

        console.log("[SeatGrid] Loading seats with params:", params);

        const result = await fetchSeats(params);

        if (result.success && result.seats) {
            processSeatData(result.seats);
            restoreSavedSeats();
            error.value = null;
        } else {
            throw new Error("Failed to fetch seat data");
        }

    } catch (err) {
        console.error("[SeatGrid] Seat fetch error:", err);

        // Only show error UI and demo data on the initial load;
        // background refreshes fail silently so we don't disrupt the user
        if (showLoader) {
            error.value = "Failed to load seat information.";
            initializeDemoSeats();
            restoreSavedSeats();
        }
    } finally {
        if (showLoader) initialLoading.value = false;
    }
}

// =============================================================================
// Data Processing — Map raw API response into normalised seat objects
// =============================================================================

/**
 * Converts the raw array of API seat objects into the flat reactive `seats` array.
 *
 * Strategy:
 *  1. Build a seatMap keyed by Seat_No for O(1) lookups.
 *  2. Walk every cell in the layout grid from the resolved bus config
 *     (section → row → col).
 *  3. For each non-null cell, look up the matching API seat:
 *     - Found    → create a full seat object with all API metadata.
 *     - Missing  → create a fallback seat (available, fare: 0) so the
 *                  layout is never broken by incomplete API data.
 *  4. null cells → push a lightweight empty spacer object.
 *
 * @param {Array} apiSeats — raw seat list from the API response
 */
function processSeatData(apiSeats) {
    const seatsArray = [];
    const config = currentBusConfig.value;

    if (!config) {
        console.error("[SeatGrid] ❌ No bus configuration found!");
        return;
    }

    // Build fast lookup map: "seatNo" string → raw API seat object
    const seatMap = {};
    apiSeats.forEach((seat) => {
        const seatNo = seat.Seat_No || seat.SeatNo;
        if (seatNo != null) seatMap[String(seatNo)] = seat;
    });

    // Walk the layout grid from the bus config
    config.sections.forEach((section) => {
        section.rows.forEach((row, rowIndex) => {
            row.cols.forEach((layoutSeatNumber, colIndex) => {

                // ── null cell → aisle / gap spacer ───────────────────────
                if (!layoutSeatNumber) {
                    seatsArray.push({
                        id: `empty-${section.type}-${rowIndex}-${colIndex}`,
                        label: null,
                        isEmpty: true,
                        state: SEAT_STATUS.EMPTY,
                    });
                    return;
                }

                const seatNumberStr = String(layoutSeatNumber);
                const apiSeat = seatMap[seatNumberStr];

                if (apiSeat) {
                    // ── Matched API seat ──────────────────────────────────
                    const seatStatus = getSeatStatusFromAPI(apiSeat);
                    const isAvailable =
                        seatStatus === SEAT_STATUS.AVAILABLE ||
                        seatStatus === SEAT_STATUS.EMPTY;

                    seatsArray.push({
                        id: `seat-${apiSeat.seat_id}`,
                        seatId: apiSeat.seat_id,
                        label: seatNumberStr,
                        sectionType: section.type,
                        sectionName: section.name,
                        rowIndex,
                        colIndex,
                        state: seatStatus,
                        gender: apiSeat.Gender || "",
                        isAvailable,
                        isSelected: false,
                        fare: apiSeat.fare || apiSeat.Fare || 0,
                        apiData: apiSeat,
                        originalStatus: apiSeat.seat_status,
                    });

                } else {
                    // ── Seat in layout but absent from API → safe fallback ─
                    console.warn(
                        `[SeatGrid] ⚠️ Seat ${seatNumberStr} not found in API — using fallback`
                    );

                    seatsArray.push({
                        id: `seat-fallback-${seatNumberStr}`,
                        seatId: null,
                        label: seatNumberStr,
                        sectionType: section.type,
                        sectionName: section.name,
                        rowIndex,
                        colIndex,
                        state: SEAT_STATUS.AVAILABLE,
                        gender: "",
                        isAvailable: true,
                        isSelected: false,
                        fare: 0,
                        apiData: null,
                    });
                }
            });
        });
    });

    seats.value = seatsArray;
    console.log(`[SeatGrid] ✅ Processed ${seatsArray.length} seat cells`);
}

// =============================================================================
// Demo Data — Fallback when the API is unreachable on initial load
// =============================================================================

/**
 * Populates `seats` with pseudo-random demo data so the UI still renders
 * when the real API is unavailable during the first load.
 *
 * Seat state distribution (per seat):
 *  • 30% → RESERVED  (gender randomised for legend demonstration)
 *  • 10% → HOLD      (on hold by a simulated other user)
 *  • 60% → AVAILABLE (free to select)
 *
 * Fare value is read from the `price` URL param if present (defaults to 0).
 */
function initializeDemoSeats() {
    console.warn("[SeatGrid] Initialising demo seats (API unavailable)");

    const seatsArray = [];
    const config = currentBusConfig.value;
    if (!config) return;

    const urlParams = new URLSearchParams(window.location.search);
    const price = parseFloat(decodeURIComponent(urlParams.get("price") || "")) || 0;

    config.sections.forEach((section) => {
        section.rows.forEach((row, rowIndex) => {
            row.cols.forEach((seatNumber, colIndex) => {

                // null cell → spacer
                if (!seatNumber) {
                    seatsArray.push({
                        id: `empty-${section.type}-${rowIndex}-${colIndex}`,
                        label: null,
                        isEmpty: true,
                        state: SEAT_STATUS.EMPTY,
                    });
                    return;
                }

                const seatNumberStr = seatNumber.toString();
                const random = Math.random();
                let state, gender, isAvailable;

                if (random < 0.3) {
                    // Reserved — show gender-colour variation in legend
                    state = SEAT_STATUS.RESERVED;
                    gender = Math.random() > 0.5 ? "male" : "female";
                    isAvailable = false;
                } else if (random < 0.4) {
                    // On hold by another user
                    state = SEAT_STATUS.HOLD;
                    gender = "";
                    isAvailable = false;
                } else {
                    // Available for the current user
                    state = SEAT_STATUS.AVAILABLE;
                    gender = "";
                    isAvailable = true;
                }

                seatsArray.push({
                    id: seatNumberStr,
                    seatId: parseInt(seatNumber) + 11700000, // synthetic demo ID
                    sectionType: section.type,
                    sectionName: section.name,
                    rowIndex,
                    colIndex,
                    label: seatNumberStr,
                    state,
                    gender,
                    isAvailable,
                    isSelected: false,
                    fare: price,
                    apiData: null,
                });
            });
        });
    });

    seats.value = seatsArray;
}

// =============================================================================
// Gender Modal Helpers
// =============================================================================

/**
 * Opens the gender-selection popup and stores the target seat in pendingSeat.
 * The seat is not modified until the user confirms a gender choice.
 *
 * @param {Object} seat — the seat object the user clicked
 */
function openGenderModal(seat) {
    pendingSeat.value = seat;
    showGenderModal.value = true;
}

/**
 * Cancels the gender popup without taking any action.
 * Called when clicking the backdrop or the × close button.
 * pendingSeat is cleared so no stale reference is kept.
 */
function cancelGenderSelect() {
    showGenderModal.value = false;
    pendingSeat.value = null;
}

/**
 * Called when the user taps either the Male or Female button.
 * Closes the modal then delegates to proceedHoldSeat() with the chosen gender.
 *
 * @param {'male'|'female'} gender — the gender option the user selected
 */
async function confirmGenderAndSelect(gender) {
    showGenderModal.value = false;

    if (!pendingSeat.value) return;

    const seat = pendingSeat.value;
    pendingSeat.value = null; // clear pending ref before async work

    await proceedHoldSeat(seat, gender);
}

// =============================================================================
// Seat Interaction — toggleSeat  (entry point for all seat-click events)
// =============================================================================

/**
 * Main click handler attached to every seat button in the template.
 *
 * Decision tree:
 *  ① seat is null / missing label   → no-op (safety guard for spacer cells)
 *  ② seat not in local seats array  → no-op
 *  ③ seat.isSelected === true        → DESELECT → proceedUnholdSeat()
 *  ④ seat.isAvailable === false      → no-op    (reserved / on hold)
 *  ⑤ seat.isSelected === false       → SELECT   → openGenderModal()
 *                                               → (user picks gender)
 *                                               → confirmGenderAndSelect()
 *                                               → proceedHoldSeat()
 *
 * @param {Object|null} seat — resolved seat object or null for spacer cells
 */
async function toggleSeat(seat) {
    // ① Guard: spacer / invalid cell
    if (!seat || !seat.label) {
        console.log("[SeatGrid] ❌ toggleSeat: invalid or spacer seat", seat);
        return;
    }

    const seatIndex = seats.value.findIndex((s) => s.id === seat.id);

    // ② Guard: seat not found in reactive array
    if (seatIndex === -1) {
        console.log("[SeatGrid] ❌ toggleSeat: seat not in local array", seat);
        return;
    }

    // ③ DESELECT path — no gender prompt required
    if (seat.isSelected) {
        await proceedUnholdSeat(seat, seatIndex);
        return;
    }

    // ④ Guard: seat is unavailable (reserved / held by someone else)
    if (!seat.isAvailable) {
        console.log("[SeatGrid] ❌ toggleSeat: seat unavailable", seat.label);
        return;
    }

    // ⑤ SELECT path — open gender popup; hold happens after gender confirmed
    openGenderModal(seat);
}

// =============================================================================
// Seat Interaction — proceedUnholdSeat  (deselect + API unhold call)
// =============================================================================

/**
 * Removes a seat from the user's selection and releases the API hold.
 *
 * Optimistic UI strategy:
 *  1. Immediately mark the seat as deselected in the local array.
 *  2. Remove from the emitted selectedSeats list and persist to session.
 *  3. Call unholdSeat() API → on success refresh seat data.
 *  4. On API failure → roll back: re-mark selected, re-emit the seat,
 *     and alert the user.
 *
 * @param {Object} seat        — seat object to deselect / unhold
 * @param {number} [seatIndex] — optional pre-computed index (avoids re-findIndex)
 */
async function proceedUnholdSeat(seat, seatIndex) {
    // Accept pre-computed index from toggleSeat to avoid a second findIndex
    if (seatIndex === undefined) {
        seatIndex = seats.value.findIndex((s) => s.id === seat.id);
    }

    // ── Step 1: Optimistic UI — deselect immediately ─────────────────────
    seats.value[seatIndex].isSelected = false;

    const updatedSeats = selectedSeats.value.filter(
        (s) => s.seatId !== seat.seatId
    );
    emit("update:selected-seats", updatedSeats);
    emit("update:selected-seats-data", updatedSeats);
    saveSeatsToSession(updatedSeats);

    try {
        // ── Step 2: API call ──────────────────────────────────────────────
        const result = await unholdSeat(
            companyName.value,
            scheduleId.value,
            seat.seatId
        );

        if (!result.success) {
            throw new Error(result.message || "Seat unhold failed");
        }

        console.log("[SeatGrid] 🔓 Seat unheld:", seat.label);

        // Refresh seat availability for all other users to see the freed seat
        loadSeats();

    } catch (err) {
        // ── Step 3: Rollback on API failure ──────────────────────────────
        seats.value[seatIndex].isSelected = true;

        const originalFare = seat.apiData?.fare;
        const discountAmount = originalFare - seat.fare;

        const restoredSeats = [
            ...selectedSeats.value,
            {
                id: seat.id,
                seatId: seat.seatId,
                label: seat.label,
                fare: seat.fare,
                original_fare: originalFare,
                discount: discountAmount > 0 ? discountAmount : 0,
                rowIndex: seat.rowIndex,
                colIndex: seat.colIndex,
                gender: seat.gender,
                status: seat.state,
                apiData: seat.apiData,
            },
        ];

        emit("update:selected-seats", restoredSeats);
        emit("update:selected-seats-data", restoredSeats);
        saveSeatsToSession(restoredSeats);

        console.error("[SeatGrid] ❌ Seat unhold failed:", err);
        alert(err.message || "Unable to release seat. Please try again.");
    }
}

// =============================================================================
// Seat Interaction — proceedHoldSeat  (select + API hold call)
// =============================================================================

/**
 * Places a hold on the seat via the API and adds it to the selectedSeats list.
 * Always called after the user has confirmed a gender in the popup.
 *
 * Optimistic UI strategy:
 *  1. Immediately mark the seat as selected and store the chosen gender.
 *  2. Call holdSeat() API → on success emit the full updated seat list.
 *  3. On API failure → roll back the local UI changes and alert the user.
 *
 * The `gender` value is:
 *  • Persisted on the local seat object so the badge colour is correct.
 *  • Included in the emitted seat payload so the booking form receives it.
 *
 * @param {Object}          seat   — seat object to select / hold
 * @param {'male'|'female'} gender — gender confirmed via the modal popup
 */
async function proceedHoldSeat(seat, gender) {
    const seatIndex = seats.value.findIndex((s) => s.id === seat.id);
    if (seatIndex === -1) return;

    // ── Step 1: Optimistic UI — mark selected + store gender ─────────────
    seats.value[seatIndex].isSelected = true;
    seats.value[seatIndex].gender = gender;

    try {
        // ── Step 2: API call ──────────────────────────────────────────────
        const result = await holdSeat(
            companyName.value,
            scheduleId.value,
            seat.seatId
        );

        if (!result.success) {
            throw new Error(result.message || "Seat hold failed");
        }

        // Calculate discount to carry through to booking summary and backend
        const originalFare = seat.apiData?.original_fare || seat.fare;
        const discountAmount = originalFare - seat.fare;

        const newSelectedSeats = [
            ...selectedSeats.value,
            {
                id: seat.id,
                seatId: seat.seatId,
                label: seat.label,
                fare: seat.fare,           // discounted fare for this booking
                original_fare: originalFare,        // pre-discount fare
                discount: discountAmount > 0 ? discountAmount : 0,
                rowIndex: seat.rowIndex,
                colIndex: seat.colIndex,
                gender: gender,              // ← from the gender popup
                status: seat.state,
                apiData: seat.apiData,
            },
        ];

        emit("update:selected-seats", newSelectedSeats);
        emit("update:selected-seats-data", newSelectedSeats);
        saveSeatsToSession(newSelectedSeats);

        // ✅ RESTART THE TIMER AFTER SUCCESSFUL HOLD
        startRefreshTimer()

        console.log(`[SeatGrid] ✅ Seat held: ${seat.seatId} | Gender: ${gender}`);

    } catch (err) {
        // ── Step 3: Rollback on API failure ──────────────────────────────
        seats.value[seatIndex].isSelected = false;
        seats.value[seatIndex].gender = "";
        console.error("[SeatGrid] ❌ Seat hold failed:", err);
        alert(err.message || "Unable to hold seat. Please try again.");
    }
}

// =============================================================================
// Seat Interaction — deselectSeat  (called from summary badge × button)
// =============================================================================

/**
 * Handles the × button inside each selected-seat badge in the summary section.
 *
 * Normal case:
 *  The seat is still in the local `seats` array → delegate to toggleSeat()
 *  which runs the full unhold flow.
 *
 * Edge case (after a background refresh that didn't restore state):
 *  The seat is not found locally → directly emit the trimmed list without
 *  an API call, since the seat is no longer tracked client-side.
 *
 * @param {Object} selectedSeat — seat entry from the emitted selectedSeats list
 */
function deselectSeat(selectedSeat) {
    const seat = seats.value.find((s) => s.seatId === selectedSeat.seatId);

    if (seat) {
        // Normal path: seat found in local array — use full toggle / unhold flow
        toggleSeat(seat);
    } else {
        // Edge case: seat not in local array (e.g. after a page refresh anomaly)
        console.log("[SeatGrid] 🗑️ Deselecting seat not in local array:", selectedSeat);

        const updatedSeats = selectedSeats.value.filter(
            (s) => s.seatId !== selectedSeat.seatId
        );
        emit("update:selected-seats", updatedSeats);
        emit("update:selected-seats-data", updatedSeats);
        saveSeatsToSession(updatedSeats);
    }
}

// =============================================================================
// Session Storage — persist selected seats across page refreshes
// =============================================================================

/**
 * Generates a unique sessionStorage key scoped to the current booking context.
 * Includes company + scheduleId + date so different trips never interfere.
 *
 * Example: "selected_seats_DaewooExpress_SCH1234_2025-08-01"
 *
 * @returns {string}
 */
const getSessionKey = () => {
    const urlParams = new URLSearchParams(window.location.search);
    const company = urlParams.get("company") || "";
    const schId = urlParams.get("scheduleId") || "";
    const date = urlParams.get("date") || "";
    return `selected_seats_${company}_${schId}_${date}`;
};

/**
 * Serialises the current seat selection to sessionStorage with a 30-minute TTL.
 * Called after every seat selection / deselection so the state survives a
 * page reload within the same booking session.
 *
 * @param {Array} seatsToSave — current list of selected seat objects to persist
 */
const saveSeatsToSession = (seatsToSave) => {
    try {
        const sessionKey = getSessionKey();
        const expirationTime = new Date().getTime() + 30 * 60 * 1000; // ⏱ 5 minutes

        sessionStorage.setItem(sessionKey, JSON.stringify({
            seats: seatsToSave,
            expiresAt: expirationTime,
        }));
    } catch (err) {
        console.error("[SeatGrid] ❌ Failed to save seats to session:", err);
    }
};

/**
 * Reads persisted seat data from sessionStorage for the current booking context.
 * Automatically clears and returns [] if data has passed the 30-minute TTL.
 *
 * @returns {Array} — saved seat objects or an empty array
 */
const loadSeatsFromSession = () => {
    try {
        const sessionKey = getSessionKey();
        const savedData = sessionStorage.getItem(sessionKey);

        if (savedData) {
            const parsed = JSON.parse(savedData);
            const currentTime = new Date().getTime();

            // Expired — caller (watcher) is responsible for unholding
            if (parsed.expiresAt && currentTime > parsed.expiresAt) {
                return null; // null signals "expired" to the watcher
            }

            return parsed.seats || [];
        }
    } catch (err) {
        console.error("[SeatGrid] ❌ Failed to load seats from session:", err);
    }
    return [];
};

/**
 * Returns the raw expiry timestamp stored in the session, or null.
 * Used by the watcher to decide when to trigger auto-unhold.
 *
 * @returns {number|null}
 */
const getSessionExpiresAt = () => {
    try {
        const savedData = sessionStorage.getItem(getSessionKey());
        if (!savedData) return null;
        const parsed = JSON.parse(savedData);
        return parsed.expiresAt || null;
    } catch {
        return null;
    }
};

/**
 * Removes the seat session entry for the current booking context.
 * Called when session data is found to be expired or no longer valid.
 */
const clearSeatsFromSession = () => {
    try {
        sessionStorage.removeItem(getSessionKey());
        console.log("[SeatGrid] 🗑️ Session seats cleared.");
    } catch (err) {
        console.error("[SeatGrid] ❌ Failed to clear seats from session:", err);
    }
};

/**
 * Re-applies previously selected seats (from sessionStorage) after the `seats`
 * array has been freshly populated from the API.
 *
 * For each saved seat:
 *  • If the seatId still exists in the fresh API data → mark as selected.
 *  • If no saved seat matches any API seat → session is stale, clear it.
 *
 * Valid restored seats are emitted to the parent so the booking summary
 * and pricing sections immediately reflect the restored state.
 */
const restoreSavedSeats = () => {
    const savedSeats = loadSeatsFromSession();
    // null = expired; [] = nothing saved — either way, nothing to restore
    if (!savedSeats || savedSeats.length === 0) {
        if (savedSeats === null) clearSeatsFromSession(); // remove stale key
        return;
    }

    const validRestoredSeats = [];

    savedSeats.forEach((savedSeat) => {
        const seat = seats.value.find((s) => s.seatId === savedSeat.seatId);
        if (!seat) return;

        const idx = seats.value.findIndex((s) => s.id === seat.id);
        if (idx !== -1) {
            seats.value[idx].isAvailable = true;
            seats.value[idx].isSelected = true;
            validRestoredSeats.push(savedSeat);
        }
    });

    if (validRestoredSeats.length > 0) {
        emit("update:selected-seats", validRestoredSeats);
        emit("update:selected-seats-data", validRestoredSeats);
    } else {
        clearSeatsFromSession();
    }
};

// =============================================================================
// Auto-Unhold — releases all held seats when the 5-minute session expires
// =============================================================================

/**
 * Silently calls unholdSeat() for every seat in the given list.
 * Fires when the session-expiry watcher detects the TTL has passed.
 *
 * Strategy — fire-and-forget for each seat:
 *  • We don't await all promises; seats are released best-effort.
 *  • UI is reset (deselected) before the API calls so the user sees
 *    immediate feedback even on slow connections.
 *  • Session storage is cleared first so the watcher doesn't re-trigger.
 *
 * @param {Array} expiredSeats — seat objects that were held and now need releasing
 */
async function autoUnholdExpiredSeats(expiredSeats) {
    if (!expiredSeats || expiredSeats.length === 0) return;

    console.warn(
        `[SeatGrid] ⏰ Session expired — auto-unholding ${expiredSeats.length} seat(s):`,
        expiredSeats.map((s) => s.label).join(", ")
    );

    // 1️⃣ Clear session immediately so the watcher doesn't fire again
    clearSeatsFromSession();

    // 2️⃣ Reset UI — mark all held seats as deselected
    seats.value.forEach((seat) => {
        if (expiredSeats.some((s) => s.seatId === seat.seatId)) {
            seat.isSelected = false;
            seat.isAvailable = true;
        }
    });

    // 3️⃣ Notify parent — clear the selected seats list
    emit("update:selected-seats", []);
    emit("update:selected-seats-data", []);

    // 4️⃣ Call unhold API for each expired seat (parallel, best-effort)
    const unholdPromises = expiredSeats.map(async (seat) => {
        try {
            const result = await unholdSeat(
                companyName.value,
                scheduleId.value,
                seat.seatId
            );

            if (result.success) {
                console.log(`[SeatGrid] 🔓 Auto-unheld seat: ${seat.label}`);
            } else {
                console.warn(`[SeatGrid] ⚠️ Auto-unhold failed for seat ${seat.label}:`, result.message);
            }
        } catch (err) {
            // Non-fatal — the hold will expire server-side anyway
            console.warn(`[SeatGrid] ⚠️ Auto-unhold API error for seat ${seat.label}:`, err.message);
        }
    });

    // Wait for all unhold calls to finish before refreshing availability
    await Promise.allSettled(unholdPromises);

    // 5️⃣ Refresh seat availability so the freed seats appear for other users
    // loadSeats(false);

    // ✅ RESTART THE TIMER AFTER SUCCESSFUL UNHOLD + REFRESH
    startRefreshTimer();

    console.log("[SeatGrid] ✅ Auto-unhold complete.");
}

/**
 * Starts a 30-second polling loop that checks whether the session TTL has
 * passed.  When it detects expiry it reads the saved seats and triggers
 * autoUnholdExpiredSeats() then stops itself.
 *
 * The interval is intentionally short (30 s) so the unhold fires promptly
 * after the 5-minute mark rather than up to 10 minutes later.
 *
 * Called once in onMounted().  Cleared in onBeforeUnmount().
 */
function startSessionExpiryWatcher() {
    // Clear any previous watcher before starting a fresh one
    if (sessionWatchInterval) {
        clearInterval(sessionWatchInterval);
    }

    sessionWatchInterval = setInterval(async () => {
        const expiresAt = getSessionExpiresAt();

        // No active session → nothing to watch
        if (expiresAt === null) return;

        const now = new Date().getTime();

        // TTL has passed — retrieve held seats before wiping the session
        if (now >= expiresAt) {
            console.log("[SeatGrid] ⏰ Session expiry detected by watcher.");

            // Read seats BEFORE clearing so we know what to unhold
            let expiredSeats = [];
            try {
                const savedData = sessionStorage.getItem(getSessionKey());
                if (savedData) {
                    const parsed = JSON.parse(savedData);
                    expiredSeats = parsed.seats || [];
                }
            } catch {
                expiredSeats = [];
            }

            // Stop the watcher — auto-unhold is a one-shot action
            clearInterval(sessionWatchInterval);
            sessionWatchInterval = null;

            // Fire the unhold sequence
            await autoUnholdExpiredSeats(expiredSeats);
        } else {
            // Still within TTL — log remaining time in development
            const remaining = Math.round((expiresAt - now) / 1000);
            console.log(`[SeatGrid] ⏳ Session expiry watcher: ${remaining}s remaining`);
        }
    }, 30_000); // check every 30 seconds
}

// =============================================================================
// Seat UI Helpers — class / colour / section styling
// =============================================================================

/**
 * Computes the full Tailwind class string for a seat button element.
 *
 * Visibility / interaction rules:
 *  • null / no label              → invisible + non-interactive (spacer placeholder)
 *  • AVAILABLE, EMPTY, isSelected → pointer cursor + scale on hover
 *  • RESERVED, HOLD               → not-allowed cursor (disabled)
 *
 * @param {Object|null} seat — resolved seat object or null
 * @returns {string}          — Tailwind class string
 */
function getSeatClass(seat) {
    const base = "relative flex h-12 w-12 items-center justify-center transition-all duration-200";

    if (!seat || !seat.label) {
        return `${base} opacity-0 cursor-default`;
    }

    if (
        seat.state === SEAT_STATUS.AVAILABLE ||
        seat.state === SEAT_STATUS.EMPTY ||
        seat.isSelected
    ) {
        return `${base} cursor-pointer hover:scale-105`;
    }

    return `${base} cursor-not-allowed`;
}

/**
 * Returns the Tailwind text-colour class passed to the SeatIcon component.
 *
 * Colour semantics:
 *  🟠 orange  — selected by the current user
 *  🔵 blue    — available (free to select)
 *  🟡 yellow  — on hold (temporarily held by another user)
 *  🟣 purple  — reserved (male passenger)
 *  🩷 pink    — reserved (female passenger)
 *  ⚪ gray    — invisible spacer (should never be visible in practice)
 *
 * @param {Object|null} seat — resolved seat object
 * @returns {string}          — Tailwind text-colour class string
 */
function getSeatColor(seat) {
    if (!seat || !seat.label) return "text-gray-300";

    // Selected state overrides all other status colours
    if (seat.isSelected) return "text-orange-500 drop-shadow-lg";

    switch (seat.state) {
        case SEAT_STATUS.AVAILABLE:
            return "text-blue-600";
        case SEAT_STATUS.EMPTY:
            return "text-blue-600";
        case SEAT_STATUS.HOLD:
            return "text-yellow-600";
        case SEAT_STATUS.RESERVED:
            // Gender-coded colouring for reserved seats
            return seat.gender?.toLowerCase() === "female"
                ? "text-pink-600"
                : "text-purple-700";
        default:
            return "text-blue-600";
    }
}

/**
 * Returns the background + border Tailwind classes for a section wrapper card.
 * Different section types get a visually distinct colour theme so Business and
 * Executive sections are immediately distinguishable on multi-section layouts.
 *
 * @param {string} sectionType — e.g. "business", "executive", "Sleeper"
 * @returns {string}            — Tailwind gradient + border class string
 */
function getSectionClass(sectionType) {
    const classes = {
        business: "bg-gradient-to-br from-amber-50  to-amber-100  border-amber-200",
        executive: "bg-gradient-to-br from-blue-50   to-blue-100   border-blue-200",
        Sleeper: "bg-gradient-to-br from-purple-50 to-purple-100 border-purple-200",
    };

    return (
        classes[sectionType] ||
        "bg-gradient-to-br from-slate-50 to-slate-100 border-slate-200"
    );
}

// =============================================================================
// Seat Lookup Helper
// =============================================================================
// Replace the simple let with a ref
const refreshInterval = ref(null);

/**
 * Starts (or restarts) the background seat refresh timer.
 * Clears any existing timer and sets a new one with the specified delay.
 * @param {number} delayMs - delay in milliseconds (default 10000)
 */
function startRefreshTimer(delayMs = 10000) {
  // Clear existing interval
  if (refreshInterval.value) {
    clearInterval(refreshInterval.value);
    refreshInterval.value = null;
  }
  // Start new interval
  refreshInterval.value = setInterval(() => {
    loadSeats(false);
  }, delayMs);
}
/**
 * Resolves a seat number string from the layout config to a full seat object
 * in the reactive `seats` array.  Used in the template v-for bindings.
 *
 * Returns null for:
 *  • null / undefined input (spacer cells)
 *  • seat numbers that exist in the layout but haven't been processed yet
 *
 * @param {string|null} seatNumber — seat-number string from the layout grid
 * @returns {Object|null}           — matching seat object or null
 */
function getSeatByNumber(seatNumber) {
    if (!seatNumber) return null;
    return seats.value.find((s) => s.label === seatNumber.toString()) || null;
}

// =============================================================================
// Lifecycle Hooks
// =============================================================================

onMounted(() => {
  // Initial load – shows full-screen spinner
  loadSeats(true);
  // Start background refresh timer (10 seconds)
  startRefreshTimer(10000);
  // Start session expiry watcher
  startSessionExpiryWatcher();
});

onBeforeUnmount(() => {
  // Clear seat‑refresh interval
  if (refreshInterval.value) {
    clearInterval(refreshInterval.value);
    refreshInterval.value = null;
  }
  // Clear session‑expiry watcher
  if (sessionWatchInterval) {
    clearInterval(sessionWatchInterval);
    sessionWatchInterval = null;
  }
});

onUnmounted(() => {
    // Session storage is intentionally NOT cleared on unmount by default.
    // Un-comment the line below if you want the selection released when
    // the component is torn down (e.g. navigating away from the booking page):
    // clearSeatsFromSession();
});

// =============================================================================
// Watchers
// =============================================================================

/**
 * Keeps the local `seats` array's isSelected flags in sync whenever the
 * parent updates the selectedSeats prop.
 *
 * This covers two scenarios:
 *  1. Multiple seat grids on the same page sharing the same parent state.
 *  2. Parent restoring state after a route change / back navigation.
 *
 * Also persists the latest selection to sessionStorage on every update.
 */
watch(
    () => props.selectedSeats,
    (newSeats) => {
        // Re-sync local isSelected flags
        seats.value.forEach((seat) => {
            seat.isSelected = newSeats?.some((s) => s.seatId === seat.seatId) ?? false;
        });

        // Persist to session whenever the selection changes
        if (newSeats && newSeats.length > 0) {
            saveSeatsToSession(newSeats);
        }
    },
    { deep: true }
);

// =============================================================================
// Exposed Public API — callable by parent via template ref
// =============================================================================
defineExpose({
    /**
     * Allows the parent component to programmatically clear the seat
     * session storage after a successful checkout / booking confirmation.
     *
     * Usage in parent:
     *   const seatGridRef = ref(null);
     *   seatGridRef.value.clearSeatsFromSession();
     */
    clearSeatsFromSession,
});
</script>

<style scoped>
/* ── Gender Modal Transition ─────────────────────────────────────────────── */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* Card pop-in scale animation */
.animate-scale-in {
    animation: scaleIn 0.18s ease-out both;
}

@keyframes scaleIn {
    from {
        transform: scale(0.88);
        opacity: 0;
    }

    to {
        transform: scale(1);
        opacity: 1;
    }
}
</style>
