<script setup>
import { Head, router } from "@inertiajs/vue3";
import { computed, onMounted, ref } from "vue";
import SeatGrid from "./Seats.vue";
import PassengerForm from "./Form.vue";
import { useToast } from "vue-toast-notification";
import WebLayout from "@/Layouts/WebLayout.vue";

import { usePage } from "@inertiajs/vue3";
import { values } from "lodash";
import Gallery from "./Gallery.vue";
const toast = useToast();

const page = usePage();
// Get trip data from URL
const tripData = computed(() => {
    const params = new URLSearchParams(window.location.search);

    const trip = {
        date: params.get("date") || "",
        from: params.get("from") || "",
        to: params.get("to") || "",
        serviceTypeId: params.get("serviceTypeId") || "",
        departure: decodeURIComponent(params.get("departureTime") || ""),
        scheduleId: params.get("scheduleId") || "",
        price: parseFloat(params.get("price")) || 0,
        seatsAvailable: parseInt(params.get("seatsAvailable")) || 0,
        busType: params.get("busType") || "",
        busService: params.get("busService") || "",
        fromId: params.get("from"),
        toId: params.get("to"),
        companylogo: params.get("companylogo") || "",
    };

    // console.log("Trip data from URL:", trip);
    return trip;
});

const travelDate = computed(() => {
    const params = new URLSearchParams(window.location.search);
    return params.get("date") || new Date().toISOString().split("T")[0];
});

const selectedDepartureTime = computed(() => {
    if (tripData.value) {
        return (
            tripData.value.departure ||
            (tripData.value.departureTimes &&
                tripData.value.departureTimes[0]) ||
            ""
        );
    }
    return "";
});

// Shared state
const selectedSeats = ref([]);

// Total price based on selected seats
const totalPrice = computed(() => {
    if (!selectedSeats.value || selectedSeats.value.length === 0) return 0;

    return selectedSeats.value.reduce((sum, seat) => {
        return sum + (seat.fare || 0); // sum each seat's fare
    }, 0);
});

// Formatted price text
const priceText = computed(() => {
    return new Intl.NumberFormat("en-PK", {
        style: "currency",
        currency: "PKR",
        maximumFractionDigits: 0,
    }).format(totalPrice.value);
});

function goBack() {
    window.history.back();
}

function handleSeatSelection(seats) {
    selectedSeats.value = seats;
}

async function handleBookingConfirm(formData) {
    if (selectedSeats.value.length === 0) {
        toast.warning("Please select at least one seat.", {
            position: "top-right",
            duration: 3000,
            dismissible: true,
        });
        return;
    }

    const user = page.props.auth?.user || null;
    const customerId = user ? user.id : null;

    // ❌ Only block if user is NOT logged in
    if (!user) {
        toast.warning("Please login to continue booking", {
            position: "top-right",
            duration: 3000,
            dismissible: true,
        });
        return;
    }

    // Get URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const scheduleId = decodeURIComponent(urlParams.get("scheduleId") || "");
    const companyName = decodeURIComponent(urlParams.get("company") || "");
    const fromId = decodeURIComponent(urlParams.get("fromId") || "");
    const toId = decodeURIComponent(urlParams.get("toId") || "");
    const travelDate = decodeURIComponent(urlParams.get("date") || "");
    const travelTime = decodeURIComponent(urlParams.get("departureTime") || "");
    const operator_id = urlParams.get("operator_id") || "";
    const serviceType = decodeURIComponent(
        urlParams.get("serviceTypeId") || ""
    );

    // Calculate total discount from all selected seats
    const totalDiscount = selectedSeats.value.reduce((sum, seat) => {
        const discount = seat.discount || 0;
        return sum + discount;
    }, 0);

    // Calculate discount per seat (average)
    const discountPerSeat =
        selectedSeats.value.length > 0
            ? totalDiscount / selectedSeats.value.length
            : 0;

    // Prepare seats with complete discount information
    const seatsWithDiscountInfo = selectedSeats.value.map((seat) => ({
        seatId: seat.seatId,
        label: seat.label,
        fare: seat.fare,                       // discounted fare
        original_fare: seat.original_fare || seat.fare, // pre-discount fare
        discount: seat.discount || 0,              // per-seat discount amount
        gender: seat.gender || "male",           // ← per-seat gender from the popup
    }));

    try {
        const response = await axios.post("/api/bookings/create", {
            fullName: formData.fullName,
            email: formData.email,
            phone: formData.phone,
            cnic: formData.cnic,
            gender: formData.gender,
            pickupTerminalId: formData.pickupTerminalId,
            pickupTerminal: formData.pickupTerminal,
            emergencyContact: formData.emergencyContact,
            specialRequests: formData.specialRequests,
            selectedSeats: seatsWithDiscountInfo,
            scheduleId: scheduleId,
            companyName: companyName,
            operator_id: operator_id,
            sourceId: fromId,
            destinationId: toId,
            departureTime: selectedDepartureTime.value,
            travelDate: travelDate,
            customerId: customerId,
            serviceType: serviceType,
            discount: totalDiscount > 0 ? totalDiscount : null,
            discountPerSeat: discountPerSeat > 0 ? discountPerSeat : null,
        });

        if (response.data.success) {
            // Store booking data and show payment modal
            bookingData.value = response.data.data;

            showPaymentModal.value = true;
        } else {
            throw new Error(response.data.message || "Booking failed");
        }
    } catch (error) {
        console.error("❌ Booking failed:", error);

        let errorMessage = "Failed to create booking. Please try again.";

        if (error.response?.data?.message) {
            errorMessage = error.response.data.message;
        } else if (error.response?.data?.errors) {
            const errors = Object.values(error.response.data.errors).flat();
            errorMessage = errors.join("\n");
        } else if (error.message) {
            errorMessage = error.message;
        }

        toast.warning(`Booking Error:\n\n${errorMessage}`, {
            position: "top-right",
            duration: 3000,
            dismissible: true,
        });
    }
}

const TripDetail = ref({});
const initialLoading = ref(true);

onMounted(() => {
    const params = new URLSearchParams(window.location.search);

    TripDetail.value = {
        total_fare: Number(params.get("total_fare")) || 0,
        discount: params.get("discount") || null,
        seat_20_fare: Number(params.get("seat_20_fare")) || 0,
        seat_4_fare: Number(params.get("seat_4_fare")) || 0,
        discounted_price: params.has("discounted_price")
            ? Number(params.get("discounted_price"))
            : null,
        discount_percentage: Number(params.get("discount_percentage")) || 0,
        extra_fare: params.get("extra_fare") || null,
    };

    console.log("TripDetail from URL:", TripDetail.value);

    initialLoading.value = false;
});

const decodeText = (text) => {
    try {
        return decodeURIComponent(text || "");
    } catch {
        return text;
    }
};

// pyment script
// Payment Modal State
const showPaymentModal = ref(false);
const selectedPaymentMethod = ref("");
const isProcessingPayment = ref(false);
const bookingData = ref({});

// Format currency helper
const formatCurrency = (amount) => {
    return new Intl.NumberFormat("en-PK", {
        style: "currency",
        currency: "PKR",
        maximumFractionDigits: 0,
    }).format(amount);
};

// Close Payment Modal
const closePaymentModal = () => {
    if (isProcessingPayment.value) {
        return; // Prevent closing while processing
    }

    showPaymentModal.value = false;
    selectedPaymentMethod.value = "";
    bookingData.value = {};
};

const paymentCode = ref("");
const showCongratulationsModal = ref(false);

// const ConfirmedPNR = ref("");
const ConfirmedPNR = ref("RM82459572");
const ticketImageUrl = ref("");
const isLoadingTicket = ref(false);

// Proceed to Payment
const proceedToPayment = async () => {
    if (!paymentCode.value) {
        toast.warning("Please enter a payment/discount code", {
            position: "top-right",
            duration: 3000,
            dismissible: true,
        });
        return;
    }

    if (!selectedPaymentMethod.value) {
        toast.warning("Please select a payment method", {
            position: "top-right",
            duration: 3000,
            dismissible: true,
        });
        return;
    }

    const params = new URLSearchParams(window.location.search);
    const companyName = params.get("company") || "";
    const scheduleId = params.get("scheduleId") || "";
    const operator_id = params.get("operator_id") || "";

    isProcessingPayment.value = true;

    try {
        console.log(
            "Processing payment with method:",
            selectedPaymentMethod.value
        );
        console.log("Booking Data:", bookingData.value);

        // Check for discount code
        if (paymentCode.value === "Subhan3485") {
            const apiData = {
                pnr: bookingData.value.pnr,
                totalPrice: bookingData.value.total_fare,
                companyName: companyName,
                operator_id: operator_id,
            };

            console.log("📡 Calling booking confirmation API:", apiData);

            const response = await axios.post("/api/bookings/confirm", apiData);

            if (!response.data.success) {
                throw new Error(
                    response.data.message || "Booking confirmation failed"
                );
            }

            const { external_api_results } = response.data.data;

            const failedSeats = external_api_results.filter(
                (r) => r.result.status === "FAILED"
            );

            if (failedSeats.length > 0) {
                const failedSeatNumbers = failedSeats
                    .map((s) => s.seat_no)
                    .join(", ");
                throw new Error(
                    `External API failed for seat(s): ${failedSeatNumbers}. Booking may be incomplete.`
                );
            }

            // ✅ Booking confirmed
            ConfirmedPNR.value = response.data.data.base_pnr;
            console.log("ConfirmedPNR:", ConfirmedPNR.value);

            // 🧹 Clear session data
            const sessionKey = `selected_seats_${companyName}_${scheduleId}_${travelDate.value}`;
            sessionStorage.removeItem(sessionKey);
            clearBookingSession();
            clearSeatsFromSession();

            // 🔥 IMPORTANT ORDER
            closePaymentModal();              // 1️⃣ close payment modal
            showCongratulationsModal.value = true; // 2️⃣ open success modal

            // 🎟 Generate ticket (non-blocking)
            try {
                await generateTicketImage(ConfirmedPNR.value);
            } catch (e) {
                console.error("Ticket generation failed:", e);
            }

            toast.success("Booking confirmed successfully!", {
                position: "top-right",
                duration: 4000,
                dismissible: true,
            });
        } else {
            // Normal payment gateway flow
            await new Promise((resolve) => setTimeout(resolve, 2000));
            toast.warning("Redirecting to payment gateway...", {
                position: "top-right",
                duration: 3000,
                dismissible: true,
            });
        }
    } catch (error) {
        console.error("❌ Payment / Booking confirmation error:", error);
        toast.error(
            error?.response?.data?.message ||
            error.message ||
            "Payment or booking failed. Please try again.",
            { position: "top-right", duration: 4000, dismissible: true }
        );
    } finally {
        isProcessingPayment.value = false;
    }
};

const reloadPdf = async () => {
    if (!ConfirmedPNR.value) return;

    showCongratulationsModal.value = true;

    // Generate ticket FIRST
    await generateTicketImage(ConfirmedPNR.value);
};

// Generate ticket image
const generateTicketImage = async (pnr) => {
    try {
        isLoadingTicket.value = true;

        const response = await axios.post("/api/bookings/generate-ticket", {
            pnr: pnr,
        });

        if (response.data.success) {
            ticketImageUrl.value = response.data.data.ticket_url;
            console.log("✅ Ticket generated:", ticketImageUrl.value);
        } else {
            console.error(
                "❌ Ticket generation failed:",
                response.data.message
            );
        }
    } catch (error) {
        console.error("❌ Ticket generation error:", error);
    } finally {
        isLoadingTicket.value = false;
    }
};

const closeCongratulationsModal = () => {
    showCongratulationsModal.value = false;
    ticketImageUrl.value = "";
};

const downloadTicket = () => {
    if (!ticketImageUrl.value) {
        toast.warning("Ticket is still loading, please wait...", {
            position: "top-right",
            duration: 3000,
            dismissible: true,
        });
        return;
    }

    // Create a temporary link element and trigger download
    const link = document.createElement("a");
    link.href = ticketImageUrl.value;
    link.download = `ticket_${ConfirmedPNR.value}.jpg`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    console.log("Downloading ticket...");
};

const goToProfile = () => {
    window.location.href = "http://127.0.0.1:8000/profile";
};

const goToBookings = () => {
    window.location.href = "http://127.0.0.1:8000/booking";
};

// Add this function if not already in your code
function clearBookingSession() {
    try {
        const params = new URLSearchParams(window.location.search);
        const companyName = params.get("busService") || "";
        const scheduleId = params.get("scheduleId") || "";

        const sessionKey = `selected_seats_${companyName}_${scheduleId.value}_${travelDate.value}`;
        sessionStorage.removeItem(sessionKey);

        console.log("🗑️ Cleared all booking session data");
    } catch (error) {
        console.error("Failed to clear session:", error);
    }
}

const clearSeatsFromSession = () => {
    try {
        const sessionKey = getSessionKey();
        sessionStorage.removeItem(sessionKey);
        console.log("🗑️ Seats cleared from session");
    } catch (error) {
        console.error("❌ Failed to clear seats from session:", error);
    }
};

const getSessionKey = () => {
    const urlParams = new URLSearchParams(window.location.search);
    const company = urlParams.get("company") || "";
    const scheduleId = urlParams.get("scheduleId") || "";
    const date = urlParams.get("date") || "";
    return `selected_seats_${company}_${scheduleId}_${date}`;
};
</script>

<template>
    <WebLayout class="min-h-screen bg-slate-50 text-ink">
        <!-- Header -->
        <header class="relative overflow-hidden bg-gradient-to-br from-primary via-primary to-slate-900">
            <div class="absolute inset-0 opacity-20" style="
                    background-image: radial-gradient(
                        circle at 1px 1px,
                        rgba(255, 255, 255, 0.12) 1px,
                        transparent 0
                    );
                    background-size: 22px 22px;
                "></div>
            <div class="relative z-10 px-4 py-8 mx-auto max-w-7xl sm:py-10">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm text-white/80">Step 2 of 3</p>
                        <h1 class="mt-1 text-2xl font-bold tracking-tight text-white sm:text-3xl">
                            Choose Your Seats
                        </h1>
                        <p v-if="tripData" class="mt-2 text-white/75">
                            {{ decodeText(tripData.from) }} →
                            {{ decodeText(tripData.to) }} •
                            {{ travelDate }}
                        </p>
                    </div>
                    <button @click="goBack"
                        class="inline-flex items-center justify-center rounded-xl border border-white/25 bg-white/10 px-4 py-2 text-sm font-semibold text-white backdrop-blur transition hover:-translate-y-0.5 hover:bg-white/15">
                        ← Back to Search
                    </button>
                </div>
            </div>
        </header>

        <main class="px-4 py-8 mx-auto max-w-7xl sm:py-10">
            <!-- Trip Info Banner -->
            <div
                class="p-5 mb-6 border rounded-2xl border-slate-900/10 bg-gradient-to-br from-white to-slate-50 shadow-card">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <!-- <img
                            src="/images/logo.jpg"
                            class="flex items-center justify-center w-16 h-16 border-4 border-gray-300 rounded-full bg-secondary"
                            alt=""
                        /> -->
                        <img :src="tripData.companylogo" class="flex items-center justify-center w-auto h-16" alt="" />
                        <div>
                            <p class="text-base font-bold text-slate-900">
                                {{ tripData.company || tripData.busService }}
                            </p>
                            <p class="text-xs text-slate-600">
                                Bus Type: {{ tripData.busType }}
                            </p>
                            <p class="mt-1 text-xs font-semibold text-secondary">
                                🕐 Departure: {{ selectedDepartureTime }}
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <!-- <p class="text-xs font-semibold text-slate-500">
                            Price per seat
                        </p>
                        <p class="mt-1 text-2xl font-bold text-slate-900">
                            {{
                                new Intl.NumberFormat("en-PK", {
                                    style: "currency",
                                    currency: "PKR",
                                    maximumFractionDigits: 0,
                                }).format(tripData.price)
                            }}
                        </p> -->
                        <!-- Loading Skeleton -->
                        <div v-if="initialLoading" class="flex flex-col h-full gap-2 animate-pulse">
                            <div class="w-40 h-5 rounded bg-slate-200"></div>
                            <div class="w-40 rounded h-9 bg-slate-200"></div>
                        </div>

                        <div v-else class="text-right">
                            <p class="text-xs font-semibold text-slate-900">
                                Starting from
                                <!-- Optional: Show discount percentage badge -->
                                <span v-if="TripDetail.discount_percentage"
                                    class="px-2 py-0.5 mt-1 text-xs font-medium text-white bg-primary rounded">
                                    {{ TripDetail.discount_percentage }}% off
                                </span>
                            </p>
                            <p class="mt-1 text-xl font-bold text-slate-900">
                                <small class="text-xs text-slate-600">per seat</small>
                                <!-- Show discounted price if available, otherwise business fare -->
                                {{
                                    formatCurrency(
                                        TripDetail.discounted_price ||
                                        TripDetail.seat_20_fare
                                    )
                                }}
                                <!-- Show extra fare if exists -->
                                <span v-if="TripDetail.extra_fare">
                                    + {{ TripDetail.extra_fare }}</span>
                            </p>

                            <!-- Show original price crossed out only if discounted -->
                            <div v-if="
                                TripDetail.discounted_price &&
                                TripDetail.discounted_price <
                                TripDetail.business_fare
                            " class="mt-1 text-xs line-through text-slate-400">
                                {{ formatCurrency(TripDetail.business_fare) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content: Split Layout -->
            <div class="grid gap-6 lg:grid-cols-2 lg:gap-8">
                <!-- Left Side: Seat Selection -->
                <!-- <SeatGrid
                    :selected-seats="selectedSeats"
                    @update:selected-seats="handleSeatSelection"
                /> -->
                <!-- Left Side: Seat Selection -->
                <SeatGrid :selected-seats="selectedSeats" :from-city-id="tripData.fromId" :to-city-id="tripData.toId"
                    :bus-service-type="tripData.serviceTypeId" :travel-date="tripData.date"
                    :company-name="tripData.busService" @update:selected-seats="handleSeatSelection" />

                <!-- Right Side: Passenger Form & Summary -->
                <PassengerForm :trip-data="tripData" :travel-date="travelDate"
                    :selected-departure-time="selectedDepartureTime" :selected-seats="selectedSeats"
                    :total-price="bookingData.total_fare" :price-text="priceText"
                    @confirm-booking="handleBookingConfirm" @toggle-seat="
                        (seat) => {
                            const index = selectedSeats.findIndex(
                                (s) => s.id === seat.id
                            );
                            if (index > -1) {
                                selectedSeats.splice(index, 1);
                            }
                        }
                    " />
            </div>

            <Gallery :serviceId="tripData.serviceTypeId" />
        </main>

        <!-- Payment Modal -->
        <div v-if="showPaymentModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
            @click.self="closePaymentModal">
            <div class="relative w-full max-w-2xl p-6 mx-4 bg-white shadow-2xl rounded-2xl">
                <!-- Close Button -->
                <button @click="closePaymentModal" class="absolute text-gray-400 top-4 right-4 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Success Icon -->
                <div class="flex justify-center mb-4">
                    <div class="flex items-center justify-center w-16 h-16 bg-green-100 rounded-full">
                        <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </div>

                <!-- Booking Details -->
                <div class="mb-6 text-center">
                    <h3 class="mb-2 text-2xl font-bold text-gray-900">
                        Booking Confirmed!
                    </h3>
                    <p class="text-sm text-gray-600">
                        Your seats have been reserved
                    </p>
                </div>

                <!-- Booking Information -->
                <div class="p-4 mb-6 space-y-3 border border-gray-200 rounded-lg bg-gray-50">
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-600">PNR Number:</span>
                        <span class="text-sm font-bold text-gray-900">{{
                            bookingData.pnr
                        }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-600">Passenger:</span>
                        <span class="text-sm font-semibold text-gray-900">
                            {{ bookingData.passenger_name }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-600">Seats:</span>
                        <span class="text-sm font-semibold text-gray-900">{{
                            bookingData.seats?.join(", ")
                        }}</span>
                    </div>
                    <div class="flex justify-between pt-3 border-t border-gray-200">
                        <span class="text-base font-semibold text-gray-900">Total Amount:</span>
                        <span class="text-lg font-bold text-primary">{{
                            formatCurrency(bookingData.total_fare)
                        }}</span>
                    </div>
                </div>

                <!-- Payment Method Selection -->
                <div class="mb-6">
                    <label class="block mb-3 text-sm font-semibold text-gray-700">Select Payment Method</label>
                    <div class="grid grid-cols-2 gap-3">
                        <!-- EasyPaisa -->
                        <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50" :class="selectedPaymentMethod === 'easypaisa'
                            ? 'border-primary bg-primary/5'
                            : 'border-gray-200'
                            ">
                            <input type="radio" v-model="selectedPaymentMethod" value="easypaisa"
                                class="w-5 h-5 text-primary focus:ring-primary" />
                            <div class="ml-3">
                                <div class="font-semibold text-gray-900">
                                    EasyPaisa
                                </div>
                                <div class="text-xs text-gray-600">
                                    Pay via EasyPaisa wallet
                                </div>
                            </div>
                        </label>

                        <!-- JazzCash -->
                        <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50" :class="selectedPaymentMethod === 'jazzcash'
                            ? 'border-primary bg-primary/5'
                            : 'border-gray-200'
                            ">
                            <input type="radio" v-model="selectedPaymentMethod" value="jazzcash"
                                class="w-5 h-5 text-primary focus:ring-primary" />
                            <div class="ml-3">
                                <div class="font-semibold text-gray-900">
                                    JazzCash
                                </div>
                                <div class="text-xs text-gray-600">
                                    Pay via JazzCash wallet
                                </div>
                            </div>
                        </label>

                        <!-- Bank Transfer -->
                        <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50" :class="selectedPaymentMethod === 'bank'
                            ? 'border-primary bg-primary/5'
                            : 'border-gray-200'
                            ">
                            <input type="radio" v-model="selectedPaymentMethod" value="bank"
                                class="w-5 h-5 text-primary focus:ring-primary" />
                            <div class="ml-3">
                                <div class="font-semibold text-gray-900">
                                    Bank Transfer
                                </div>
                                <div class="text-xs text-gray-600">
                                    Direct bank transfer
                                </div>
                            </div>
                        </label>

                        <!-- Credit/Debit Card -->
                        <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50" :class="selectedPaymentMethod === 'card'
                            ? 'border-primary bg-primary/5'
                            : 'border-gray-200'
                            ">
                            <input type="radio" v-model="selectedPaymentMethod" value="card"
                                class="w-5 h-5 text-primary focus:ring-primary" />
                            <div class="ml-3">
                                <div class="font-semibold text-gray-900">
                                    Credit/Debit Card
                                </div>
                                <div class="text-xs text-gray-600">
                                    Pay with your card
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Add this before the Payment Method Selection section -->
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-semibold text-gray-700">
                        Payment/Discount Code
                        <span class="text-xs text-red-500">(Required)</span>
                    </label>
                    <input type="text" v-model="paymentCode" placeholder="Enter discount code"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                        required />
                    <p class="mt-1 text-xs text-gray-500">
                        Enter "Subhan3485" to process booking immediately
                    </p>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3">
                    <button @click="closePaymentModal"
                        class="flex-1 px-4 py-3 text-sm font-semibold text-gray-700 transition-colors bg-gray-200 rounded-lg hover:bg-gray-300">
                        Cancel
                    </button>
                    <button @click="proceedToPayment" :disabled="!selectedPaymentMethod ||
                        !paymentCode ||
                        isProcessingPayment
                        "
                        class="flex-1 px-4 py-3 text-sm font-semibold text-white transition-colors rounded-lg bg-primary hover:bg-primary-dark disabled:opacity-50 disabled:cursor-not-allowed">
                        <span v-if="!isProcessingPayment">Proceed to Pay</span>
                        <span v-else class="flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            Processing...
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Congratulations Modal -->
        <Transition enter-active-class="transition-opacity duration-300"
            leave-active-class="transition-opacity duration-300" enter-from-class="opacity-0"
            leave-to-class="opacity-0">
            <div v-if="showCongratulationsModal"
                class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-[9999] p-5">
                <div
                    class="bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl text-center h-auto animate-[bounceIn_0.7s_ease-out_0.2s_both]">
                    <!-- Congratulations Content -->
                    <div>
                        <div class="flex justify-between items-center">
                            <h2 class="text-3xl font-bold text-gray-800">
                                🎉 Congratulations!
                            </h2>
                            <!-- Reload Button -->
                            <button @click="reloadPdf"
                                class="mx-2 text-gray-500 w-auto flex justify-center items-center hover:text-gray-700 bg-gray-100 py-2.5 border border-gray-200 px-5 rounded-xl"
                                :disabled="isPdfLoading">

                                <!-- Refresh Icon -->
                                <svg v-if="!isPdfLoading" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                    fill="currentColor" class="bi bi-arrow-clockwise" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd"
                                        d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2z" />
                                    <path
                                        d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466" />
                                </svg>

                                <!-- Loader -->
                                <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                    fill="currentColor" class="bi bi-arrow-clockwise animate-spin" viewBox=" 0 0 16
                                    16">
                                    <path fill-rule="evenodd"
                                        d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2z" />
                                    <path
                                        d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466" />
                                </svg>

                            </button>
                        </div>

                        <div
                            class="my-4 overflow-y-auto max-h-[60vh] flex justify-center items-center bg-gray-200 rounded-lg p-1">
                            <!-- Ticket Image Preview -->
                            <div v-if="ticketImageUrl" class="">
                                <div class="relative">
                                    <img :src="ticketImageUrl" alt="Ticket"
                                        class="object-fill border border-gray-300 rounded-lg" loading="lazy" />
                                </div>
                            </div>

                            <!-- Loading State -->
                            <div v-else-if="isLoadingTicket" class="">
                                <div class="flex items-center justify-center gap-3 py-8">
                                    <svg class="w-8 h-8 animate-spin text-emerald-500"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    <span class="text-gray-600">Generating your ticket...</span>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-col gap-3">
                            <button @click="downloadTicket" :disabled="!ticketImageUrl || isLoadingTicket" :class="[
                                'flex items-center justify-center gap-2.5 py-3.5 px-6 rounded-xl font-semibold transition-all duration-300',
                                ticketImageUrl && !isLoadingTicket
                                    ? 'bg-emerald-500 hover:bg-emerald-600 text-white hover:-translate-y-0.5 hover:shadow-lg cursor-pointer'
                                    : 'bg-gray-300 text-gray-500 cursor-not-allowed',
                            ]">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                    <polyline points="7 10 12 15 17 10" />
                                    <line x1="12" y1="15" x2="12" y2="3" />
                                </svg>
                                {{
                                    isLoadingTicket
                                        ? "Generating Ticket..."
                                        : "Download Ticket"
                                }}
                            </button>

                            <div class="grid grid-cols-2 gap-2">
                                <button @click="goToBookings"
                                    class="flex items-center justify-center gap-2 py-3.5 px-4 bg-secondary text-white rounded-xl font-semibold transition-all duration-300 hover:-translate-y-0.5">
                                    Return to Travel Plans
                                </button>
                                <button @click="goToProfile"
                                    class="flex items-center justify-center gap-2 py-3.5 px-4 bg-primary text-white rounded-xl font-semibold transition-all duration-300 hover:-translate-y-0.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                        <circle cx="12" cy="7" r="4" />
                                    </svg>
                                    View Profile
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </WebLayout>
</template>

<style scoped></style>
