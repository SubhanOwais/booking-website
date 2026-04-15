<script setup>
import { ref, onMounted, watch, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import axios from "axios";
import { useToast } from "vue-toast-notification";

const page = usePage();

// Get URL parameters
const companyName = computed(() => {
    const urlParams = new URLSearchParams(window.location.search);
    return decodeURIComponent(urlParams.get("company") || "");
});

const scheduleId = computed(() => {
    const urlParams = new URLSearchParams(window.location.search);
    return decodeURIComponent(urlParams.get("scheduleId") || "");
});

const fromId = computed(() => {
    const urlParams = new URLSearchParams(window.location.search);
    return decodeURIComponent(urlParams.get("fromId") || "");
});

const serviceTypeId = computed(() => {
    const urlParams = new URLSearchParams(window.location.search);
    return decodeURIComponent(urlParams.get("serviceTypeId") || "");
});

/* ===================== PROPS ===================== */
const props = defineProps({
    tripData: Object,
    travelDate: String,
    selectedDepartureTime: String,
    selectedSeats: {
        type: Array,
        default: () => [],
    },
    totalPrice: Number,
    priceText: String,
});

watch(
    () => props,
    (newVal) => {
        // console.log("Props changed:", { ...newVal });
    },
    { deep: true, immediate: true }
);

const emit = defineEmits(["confirmBooking"]);

/* ===================== STATE ===================== */
const formData = ref({
    fullName: "",
    email: "",
    phone: "",
    cnic: "",
    gender: "",
    pickupTerminalId: "",
    pickupTerminal: "",
    emergencyContact: "",
    specialRequests: "",
});

const formErrors = ref({});
const touched = ref({});

// Terminal state
const terminals = ref([]);
const loadingTerminals = ref(false);
const terminalError = ref("");

// Computed terminal
const selectedTerminal = computed(() => {
    if (!formData.value.pickupTerminalId) return null;
    return terminals.value.find(
        (t) => t.terminalId.toString() === formData.value.pickupTerminalId
    );
});

/* ===================== HELPERS ===================== */
function formatCnic(value) {
    if (!value) return "";

    const digits = value.replace(/\D/g, "").slice(0, 13);

    if (digits.length <= 5) return digits;
    if (digits.length <= 12) return `${digits.slice(0, 5)}-${digits.slice(5)}`;

    return `${digits.slice(0, 5)}-${digits.slice(5, 12)}-${digits.slice(12)}`;
}

function unformatCnic(value) {
    return value.replace(/\D/g, "");
}

function markAsTouched(field) {
    touched.value[field] = true;
}

const decodeText = (text) => {
    try {
        return decodeURIComponent(text || "");
    } catch {
        return text;
    }
};

/* ===================== FORM SESSION STORAGE ===================== */

// Generate a unique session key for this booking
const getFormSessionKey = () => {
    const urlParams = new URLSearchParams(window.location.search);
    const company = urlParams.get("company") || "";
    const scheduleId = urlParams.get("scheduleId") || "";
    const date = urlParams.get("date") || "";
    return `booking_form_${company}_${scheduleId}_${date}`;
};

// Save all form data to session storage
const saveFormToSession = () => {
    try {
        const sessionKey = getFormSessionKey();
        const expirationTime = new Date().getTime() + 30 * 60 * 1000; // 30 minutes

        const dataToSave = {
            ...formData.value,
            // Save raw CNIC without formatting for consistency
            cnicRaw: unformatCnic(formData.value.cnic),
            touched: { ...touched.value },
            expiresAt: expirationTime,
            timestamp: new Date().toISOString(),
        };

        sessionStorage.setItem(sessionKey, JSON.stringify(dataToSave));
        // console.log("💾 Form saved to session:", dataToSave);
    } catch (error) {
        console.error("❌ Failed to save form data:", error);
    }
};

// Load form data from session storage
const loadFormFromSession = () => {
    try {
        const sessionKey = getFormSessionKey();
        const savedData = sessionStorage.getItem(sessionKey);

        if (!savedData) return false;

        const parsed = JSON.parse(savedData);
        const currentTime = new Date().getTime();

        // Check if session expired
        if (parsed.expiresAt && currentTime > parsed.expiresAt) {
            sessionStorage.removeItem(sessionKey);
            console.log("⏰ Form session expired");
            return false;
        }

        // Restore form data
        formData.value = {
            fullName: parsed.fullName || "",
            email: parsed.email || "",
            phone: parsed.phone || "",
            cnic: parsed.cnic || formatCnic(parsed.cnicRaw || ""),
            gender: parsed.gender || "",
            pickupTerminalId: parsed.pickupTerminalId || "",
            pickupTerminal: parsed.pickupTerminal || "", // This now stores the name
            emergencyContact: parsed.emergencyContact || "",
            specialRequests: parsed.specialRequests || "",
        };

        // Restore touched state
        if (parsed.touched) {
            touched.value = { ...parsed.touched };
        }

        // console.log("📂 Form loaded from session:", formData.value);
        return true;
    } catch (error) {
        console.error("❌ Failed to load form data:", error);
        return false;
    }
};

// Clear form data from session
const clearFormFromSession = () => {
    const sessionKey = getFormSessionKey();
    sessionStorage.removeItem(sessionKey);
    // console.log("🗑️ Form session cleared");
};

// Auto-save form when data changes
watch(
    formData,
    () => {
        saveFormToSession();
    },
    { deep: true, immediate: false }
);

// Watch for touched changes as well
watch(
    touched,
    () => {
        saveFormToSession();
    },
    { deep: true }
);

/* ===================== WATCHERS ===================== */
watch(
    () => formData.value.cnic,
    (newVal) => {
        const formatted = formatCnic(newVal);
        if (formatted !== newVal) {
            formData.value.cnic = formatted;
        }

        if (touched.value.cnic) validateCnic();
    }
);

/* ===================== VALIDATION ===================== */
function validateEmail() {
    if (!formData.value.email.trim()) {
        formErrors.value.email = "Email is required";
        return false;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.value.email)) {
        formErrors.value.email = "Invalid email format";
        return false;
    }
    delete formErrors.value.email;
    return true;
}

function validatePhone() {
    if (!formData.value.phone.trim()) {
        formErrors.value.phone = "Phone number is required";
        return false;
    }
    delete formErrors.value.phone;
    return true;
}

function validateCnic() {
    const raw = unformatCnic(formData.value.cnic);

    if (!raw) {
        formErrors.value.cnic = "CNIC is required";
        return false;
    }
    if (!/^\d{13}$/.test(raw)) {
        formErrors.value.cnic = "CNIC must be 13 digits";
        return false;
    }

    delete formErrors.value.cnic;
    return true;
}

function validateGender() {
    if (!formData.value.gender) {
        formErrors.value.gender = "Gender is required";
        return false;
    }
    delete formErrors.value.gender;
    return true;
}

function validateTerminal() {
    if (!formData.value.pickupTerminalId) {
        formErrors.value.pickupTerminal = "Pickup terminal is required";
        return false;
    }
    delete formErrors.value.pickupTerminal;
    return true;
}

function validateForm() {
    touched.value = {
        fullName: true,
        email: true,
        phone: true,
        cnic: true,
        gender: true,
        pickupTerminal: true,
    };

    formErrors.value = {};

    if (!formData.value.fullName.trim()) {
        formErrors.value.fullName = "Full name is required";
        showToast("Validation Error", formErrors.value.fullName);
    }

    if (!validateEmail()) showToast("Validation Error", formErrors.value.email);
    if (!validatePhone()) showToast("Validation Error", formErrors.value.phone);
    if (!validateCnic()) showToast("Validation Error", formErrors.value.cnic);
    if (!validateGender())
        showToast("Validation Error", formErrors.value.gender);
    if (!validateTerminal())
        showToast("Validation Error", formErrors.value.pickupTerminal);

    return Object.keys(formErrors.value).length === 0;
}

function showToast(title = "", message = "", type = "error", duration = 3000) {
    // Create toast container if it doesn't exist
    let container = document.getElementById("toast-container");
    if (!container) {
        container = document.createElement("div");
        container.id = "toast-container";
        container.className = "fixed top-20 right-5 flex flex-col gap-2 z-50";
        document.body.appendChild(container);
    }

    // Create the toast
    const toast = document.createElement("div");
    toast.className = `
        max-w-sm px-4 py-3 rounded shadow-md text-sm font-medium
        ${
            type === "error"
                ? "bg-red-500 text-white"
                : "bg-green-500 text-white"
        }
        animate-slideIn
    `;

    // Add title
    if (title) {
        const titleEl = document.createElement("div");
        titleEl.className = "font-semibold mb-1";
        titleEl.innerText = title;
        toast.appendChild(titleEl);
    }

    // Add message
    if (message) {
        const messageEl = document.createElement("div");
        messageEl.className = "text-xs";
        messageEl.innerText = message;
        toast.appendChild(messageEl);
    }

    container.appendChild(toast);

    // Remove after duration
    setTimeout(() => {
        toast.classList.add("opacity-0", "transition", "duration-500");
        setTimeout(() => container.removeChild(toast), 500);
    }, duration);
}

/* ===================== AUTO FILL ===================== */
function autoFillForm() {
    const user = page.props.auth?.user;

    if (!user) return;

    // Only auto-fill if not already filled from session
    if (!formData.value.fullName)
        formData.value.fullName = user.Full_Name || "";
    if (!formData.value.email) formData.value.email = user.Email || "";
    if (!formData.value.phone) formData.value.phone = user.Phone_Number || "";
    if (!formData.value.emergencyContact)
        formData.value.emergencyContact = user.Emergency_Number || "";

    if (user.CNIC && !formData.value.cnic) {
        formData.value.cnic = formatCnic(user.CNIC);
    }

    // Auto-select gender if not set
    if (user.Gender && !formData.value.gender) {
        formData.value.gender = user.Gender.toLowerCase();
    } else if (!formData.value.gender) {
        formData.value.gender = "male";
    }
}

/* ===================== TERMINALS ===================== */
async function fetchTerminals() {
    if (
        !companyName.value ||
        !scheduleId.value ||
        !fromId.value ||
        !serviceTypeId.value
    ) {
        terminalError.value = "Missing required parameters to fetch terminals";
        return;
    }

    loadingTerminals.value = true;
    terminalError.value = "";

    try {
        const response = await axios.get("/api/terminals", {
            params: {
                company: companyName.value,
                scheduleId: scheduleId.value,
                fromId: fromId.value,
                departureTime: props.selectedDepartureTime,
                serviceTypeId: serviceTypeId.value,
            },
        });

        // console.log('Terminals API response:', response.data);

        if (response.data.success) {
            terminals.value = response.data.terminals;

            // Check if we have a saved terminal ID from form session
            if (formData.value.pickupTerminalId) {
                const savedTerminal = terminals.value.find(
                    (t) =>
                        t.terminalId.toString() ===
                        formData.value.pickupTerminalId.toString()
                );

                // If saved terminal exists, update both ID and name
                if (savedTerminal) {
                    formData.value.pickupTerminal = savedTerminal.terminalName;
                } else {
                    // If saved terminal doesn't exist in current list, clear both
                    formData.value.pickupTerminalId = "";
                    formData.value.pickupTerminal = "";
                }
            }

            // If no terminal selected yet, select first one
            if (
                !formData.value.pickupTerminalId &&
                terminals.value.length > 0
            ) {
                const firstTerminal = terminals.value[0];
                formData.value.pickupTerminalId =
                    firstTerminal.terminalId.toString();
                formData.value.pickupTerminal = firstTerminal.terminalName; // Store name
                markAsTouched("pickupTerminal");
            }
        } else {
            terminalError.value =
                response.data.message || "Failed to fetch terminals";
            terminals.value = [];
        }
    } catch (error) {
        console.error("Error fetching terminals:", error);
        terminalError.value =
            error.response?.data?.message || error.message || "Network error";
        terminals.value = [];
    } finally {
        loadingTerminals.value = false;
    }
}

function onTerminalSelect(terminal) {
    formData.value.pickupTerminalId = terminal.terminalId.toString();
    formData.value.pickupTerminal = terminal.terminalName; // Store the name
    markAsTouched("pickupTerminal");
}

/* ===================== SUBMIT ===================== */
function handleSubmit() {
    if (props.selectedSeats.length === 0) {
        toast.warning("Please select at least one seat.", {
            position: "top-right",
            duration: 3000,
            dismissible: true,
        });
        return;
    }

    if (!validateForm()) return;

    // Clear form session on successful submit
    clearFormFromSession();

    emit("confirmBooking", {
        ...formData.value,
        cnic: unformatCnic(formData.value.cnic),
    });
}

/* ===================== LIFECYCLE ===================== */
onMounted(() => {
    const loadedFromSession = loadFormFromSession();

    if (!loadedFromSession) {
        autoFillForm();
    }
});

// Watch for URL parameter changes
watch(
    [companyName, scheduleId, fromId, serviceTypeId],
    () => {
        fetchTerminals();
    },
    { immediate: true }
);

// Clear session when component is unmounted (optional)
// import { onUnmounted } from 'vue';
// onUnmounted(() => {
//     // Only clear if needed
//     // clearFormFromSession();
// });
</script>

<template>
    <section class="space-y-6">
        <!-- Passenger Details Form -->
        <div
            class="p-6 bg-white border rounded-2xl border-slate-900/10 shadow-card"
        >
            <h2 class="mb-1 text-xl font-bold text-slate-900">
                Passenger Details
            </h2>
            <p class="mb-6 text-sm text-slate-600">
                Please provide your information for booking
            </p>

            <form @submit.prevent="handleSubmit" class="space-y-3">
                <div class="flex flex-wrap items-start -m-2 sm:flex-nowrap">
                    <!-- Full Name -->
                    <div class="w-full m-2 sm:w-1/2">
                        <label
                            for="fullName"
                            class="block mb-2 text-sm font-semibold text-slate-900"
                        >
                            Full Name
                            <span class="text-xs text-red-500">(Required)</span>
                        </label>
                        <input
                            id="fullName"
                            v-model="formData.fullName"
                            type="text"
                            placeholder="John Doe"
                            @blur="markAsTouched('fullName')"
                            class="w-full px-4 py-3 text-sm transition border rounded-xl text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2"
                            :class="
                                formErrors.fullName
                                    ? 'border-red-500 bg-red-50 focus:border-red-500 focus:ring-red-200'
                                    : 'border-slate-300 bg-white focus:border-secondary focus:ring-secondary/20'
                            "
                        />
                        <p
                            v-if="formErrors.fullName"
                            class="flex items-center gap-1 mt-2 text-xs text-red-600"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            {{ formErrors.fullName }}
                        </p>
                    </div>

                    <!-- Email -->
                    <div class="w-full m-2 sm:w-1/2">
                        <label
                            for="email"
                            class="block mb-2 text-sm font-semibold text-slate-900"
                        >
                            Email Address
                            <span class="text-xs text-red-500">(Required)</span>
                        </label>
                        <input
                            id="email"
                            v-model="formData.email"
                            type="email"
                            placeholder="john@example.com"
                            @blur="markAsTouched('email')"
                            class="w-full px-4 py-3 text-sm transition border rounded-xl text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2"
                            :class="
                                formErrors.email
                                    ? 'border-red-500 bg-red-50 focus:border-red-500 focus:ring-red-200'
                                    : 'border-slate-300 bg-white focus:border-secondary focus:ring-secondary/20'
                            "
                        />
                        <p
                            v-if="formErrors.email"
                            class="flex items-center gap-1 mt-2 text-xs text-red-600"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            {{ formErrors.email }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-start -m-2 sm:flex-nowrap">
                    <!-- CNIC -->
                    <div class="w-full m-2 sm:w-1/2">
                        <label
                            for="cnic"
                            class="block mb-2 text-sm font-semibold text-slate-900"
                        >
                            CNIC Number
                            <span class="text-xs text-red-500">(Required)</span>
                        </label>
                        <input
                            id="cnic"
                            v-model="formData.cnic"
                            type="text"
                            autocomplete="off"
                            placeholder="12345-1234567-1"
                            @blur="markAsTouched('cnic')"
                            class="w-full px-4 py-3 text-sm transition border rounded-xl text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2"
                            :class="
                                formErrors.cnic
                                    ? 'border-red-500 bg-red-50 focus:border-red-500 focus:ring-red-200'
                                    : 'border-slate-300 bg-white focus:border-secondary focus:ring-secondary/20'
                            "
                        />
                        <p
                            v-if="formErrors.cnic"
                            class="flex items-center gap-1 mt-2 text-xs text-red-600"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            {{ formErrors.cnic }}
                        </p>
                    </div>

                    <!-- Gender Selection -->
                    <div class="w-full m-2 sm:w-1/2">
                        <label
                            class="block mb-2 text-sm font-semibold text-slate-900"
                        >
                            Gender
                        </label>
                        <ul
                            class="items-center w-full text-sm font-medium bg-white border rounded-xl text-slate-900 border-slate-300 sm:flex"
                        >
                            <li
                                class="w-full border-b border-slate-300 sm:border-b-0 sm:border-r"
                            >
                                <div class="flex items-center ps-3">
                                    <input
                                        id="gender-male"
                                        v-model="formData.gender"
                                        type="radio"
                                        value="male"
                                        name="gender-radio"
                                        @change="markAsTouched('gender')"
                                        class="w-4 h-4 border rounded-full appearance-none border-slate-400 bg-slate-100 checked:border-secondary focus:ring-2 focus:outline-none focus:ring-secondary/20"
                                        :class="
                                            formErrors.gender
                                                ? 'border-red-500'
                                                : ''
                                        "
                                    />
                                    <label
                                        for="gender-male"
                                        class="w-full py-3 text-sm font-medium select-none ms-2 text-slate-900"
                                    >
                                        Male
                                    </label>
                                </div>
                            </li>
                            <li class="w-full">
                                <div class="flex items-center ps-3">
                                    <input
                                        id="gender-female"
                                        v-model="formData.gender"
                                        type="radio"
                                        value="female"
                                        name="gender-radio"
                                        @change="markAsTouched('gender')"
                                        class="w-4 h-4 border rounded-full appearance-none border-slate-400 bg-slate-100 checked:border-secondary focus:ring-2 focus:outline-none focus:ring-secondary/20"
                                        :class="
                                            formErrors.gender
                                                ? 'border-red-500'
                                                : ''
                                        "
                                    />
                                    <label
                                        for="gender-female"
                                        class="w-full py-3 text-sm font-medium select-none ms-2 text-slate-900"
                                    >
                                        Female
                                    </label>
                                </div>
                            </li>
                        </ul>
                        <p
                            v-if="formErrors.gender"
                            class="flex items-center gap-1 mt-2 text-xs text-red-600"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            {{ formErrors.gender }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap -m-2 sm:flex-nowrap">
                    <!-- Phone -->
                    <div class="w-full m-2 sm:w-1/2">
                        <label
                            for="phone"
                            class="block mb-2 text-sm font-semibold text-slate-900"
                        >
                            Phone Number
                            <span class="text-xs text-red-500">(Required)</span>
                        </label>
                        <input
                            id="phone"
                            v-model="formData.phone"
                            type="tel"
                            placeholder="+92 300 1234567"
                            @blur="markAsTouched('phone')"
                            class="w-full px-4 py-3 text-sm transition border rounded-xl text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2"
                            :class="
                                formErrors.phone
                                    ? 'border-red-500 bg-red-50 focus:border-red-500 focus:ring-red-200'
                                    : 'border-slate-300 bg-white focus:border-secondary focus:ring-secondary/20'
                            "
                        />
                        <p
                            v-if="formErrors.phone"
                            class="flex items-center gap-1 mt-2 text-xs text-red-600"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            {{ formErrors.phone }}
                        </p>
                    </div>

                    <!-- Emergency Contact -->
                    <div class="w-full m-2 sm:w-1/2">
                        <label
                            for="emergencyContact"
                            class="block mb-2 text-sm font-semibold text-slate-900"
                        >
                            Emergency Contact
                        </label>
                        <input
                            id="emergencyContact"
                            v-model="formData.emergencyContact"
                            type="tel"
                            placeholder="+92 300 7654321"
                            class="w-full px-4 py-3 text-sm transition bg-white border rounded-xl border-slate-300 text-slate-900 placeholder:text-slate-400 focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/20"
                        />
                    </div>
                </div>

                <!-- Pickup Terminal -->
                <div>
                    <label
                        class="block mb-2 text-sm font-semibold text-slate-900"
                    >
                        Pickup Terminal
                        <span class="text-xs text-red-500">(Required)</span>
                    </label>

                    <!-- Loading State -->
                    <div
                        v-if="loadingTerminals"
                        class="flex items-center justify-center py-8"
                    >
                        <div class="flex flex-col items-center gap-2">
                            <div
                                class="w-8 h-8 border-4 rounded-full border-secondary border-t-transparent animate-spin"
                            ></div>
                            <p class="text-sm text-slate-600">
                                Loading terminals...
                            </p>
                        </div>
                    </div>

                    <!-- Error State -->
                    <div
                        v-else-if="terminalError"
                        class="p-4 border border-red-200 rounded-lg bg-red-50"
                    >
                        <p class="text-sm text-red-700">{{ terminalError }}</p>
                    </div>

                    <!-- Terminal List -->
                    <ul
                        v-else-if="terminals.length > 0"
                        class="flex flex-wrap items-start"
                    >
                        <li
                            v-for="terminal in terminals"
                            :key="terminal.terminalId"
                            class="w-1/2"
                        >
                            <div
                                class="relative flex items-start transition-all duration-200 border border-gray-200 ps-2 sm:ps-4"
                                :class="[
                                    formErrors.pickupTerminalId
                                        ? 'border-red-500 bg-red-50'
                                        : '',
                                    formData.pickupTerminalId ===
                                    terminal.terminalId.toString()
                                        ? 'border-2 border-primary bg-primary/5'
                                        : 'hover:border-primary/50 hover:bg-primary/2',
                                ]"
                            >
                                <!-- Checkmark for selected terminal -->
                                <div
                                    v-if="
                                        formData.pickupTerminalId ===
                                        terminal.terminalId.toString()
                                    "
                                    class="absolute top-0 right-0 p-1 text-white rounded-bl-lg bg-primary"
                                >
                                    <svg
                                        class="w-3 h-3"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </div>

                                <input
                                    :id="`terminal-${terminal.terminalId}`"
                                    v-model="formData.pickupTerminal"
                                    type="radio"
                                    :value="terminal.terminalId.toString()"
                                    name="terminal-radio"
                                    @change="onTerminalSelect(terminal)"
                                    class="flex-shrink-0 w-4 h-4 mt-5 bg-white border-2 border-gray-300 rounded-full appearance-none checked:bg-primary checked:border-primary focus:ring-2 focus:outline-none focus:ring-primary/50"
                                />

                                <label
                                    :for="`terminal-${terminal.terminalId}`"
                                    class="w-full py-4 cursor-pointer select-none ms-3"
                                >
                                    <div class="flex flex-col">
                                        <span
                                            class="text-sm font-semibold"
                                            :class="
                                                formData.pickupTerminal ===
                                                terminal.terminalId.toString()
                                                    ? 'text-primary'
                                                    : 'text-heading'
                                            "
                                        >
                                            {{ terminal.terminalName }}
                                        </span>

                                        <span
                                            v-if="terminal.address"
                                            class="mt-1 text-xs"
                                            :class="
                                                formData.pickupTerminal ===
                                                terminal.terminalId.toString()
                                                    ? 'text-primary/80'
                                                    : 'text-slate-600'
                                            "
                                        >
                                            {{ terminal.address }}
                                        </span>

                                        <div
                                            v-if="terminal.departureTime"
                                            class="flex items-center gap-1 mt-1"
                                        >
                                            <svg
                                                class="w-3 h-3"
                                                :class="
                                                    formData.pickupTerminal ===
                                                    terminal.terminalId.toString()
                                                        ? 'text-primary'
                                                        : 'text-slate-500'
                                                "
                                                fill="currentColor"
                                                viewBox="0 0 20 20"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                            <span
                                                class="text-xs"
                                                :class="
                                                    formData.pickupTerminal ===
                                                    terminal.terminalId.toString()
                                                        ? 'text-primary'
                                                        : 'text-slate-600'
                                                "
                                            >
                                                Departure:
                                                {{ terminal.departureTime }}
                                            </span>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </li>
                    </ul>

                    <!-- No Terminals -->
                    <div
                        v-else
                        class="p-4 border border-yellow-200 rounded-lg bg-yellow-50"
                    >
                        <p class="text-sm text-yellow-700">
                            No terminals available for this route.
                        </p>
                    </div>

                    <p
                        v-if="formErrors.pickupTerminal"
                        class="flex items-center gap-1 mt-2 text-xs text-red-600"
                    >
                        <svg
                            class="w-4 h-4"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        {{ formErrors.pickupTerminal }}
                    </p>
                </div>

                <!-- Terminal Info Card (when terminal is selected) -->
                <!-- <div v-if="selectedTerminal"
                    class="p-4 transition-all duration-300 border border-orange-300 rounded-xl bg-orange-50">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0">
                            <div
                                class="flex items-center justify-center w-10 h-10 bg-orange-100 border border-orange-200 rounded-lg">
                                <svg class="w-5 h-5 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-orange-900">
                                {{ selectedTerminal.terminalName }}
                                <span v-if="selectedTerminal.departureTime" class="text-xs font-medium text-orange-600">
                                    (Departure Time: {{ selectedTerminal.departureTime }})
                                </span>
                            </h3>
                            <p v-if="selectedTerminal.address" class="mt-1 text-xs text-orange-700">
                                {{ selectedTerminal.address }}
                            </p>
                            <p class="mt-1 text-xs italic text-orange-600">
                                Note: {{ selectedTerminal.instructions || 'Check-in opens 1 hour before departure' }}
                            </p>
                        </div>
                    </div>
                </div> -->

                <!-- Special Requests -->
                <!-- <div>
                    <label for="specialRequests" class="block mb-2 text-sm font-semibold text-slate-900">
                        Special Requests
                    </label>
                    <textarea id="specialRequests" v-model="formData.specialRequests" rows="3"
                        placeholder="Any special requirements or requests..."
                        class="w-full px-4 py-3 text-sm transition bg-white border resize-none rounded-xl border-slate-300 text-slate-900 placeholder:text-slate-400 focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/20"></textarea>
                </div> -->
            </form>
        </div>

        <!-- Booking Summary -->
        <div
            class="p-6 border rounded-2xl border-slate-900/10 bg-gradient-to-br from-white to-slate-50 shadow-card"
        >
            <h2 class="mb-4 text-xl font-bold text-slate-900">
                Booking Summary
            </h2>
            <div class="space-y-3">
                <div
                    class="flex items-center justify-between py-2 border-b border-slate-200"
                >
                    <span class="text-sm text-slate-600">Route</span>
                    <span class="text-sm font-semibold text-slate-900">
                        {{ decodeText(tripData.from) }} →
                        {{ decodeText(tripData.to) }}
                    </span>
                </div>
                <div
                    class="flex items-center justify-between py-2 border-b border-slate-200"
                >
                    <span class="text-sm text-slate-600">Travel Date</span>
                    <span class="text-sm font-semibold text-slate-900">{{
                        travelDate
                    }}</span>
                </div>
                <div
                    class="flex items-center justify-between py-2 border-b border-slate-200"
                >
                    <span class="text-sm text-slate-600">Departure Time</span>
                    <span class="text-sm font-semibold text-slate-900">{{
                        selectedDepartureTime
                    }}</span>
                </div>
                <div
                    class="flex items-center justify-between py-2 border-b border-slate-200"
                >
                    <span class="text-sm text-slate-600">Pickup Terminal</span>
                    <span
                        class="text-sm font-semibold"
                        :class="
                            !formData.pickupTerminal
                                ? 'text-red-500'
                                : 'text-slate-900'
                        "
                    >
                        {{
                            selectedTerminal
                                ? selectedTerminal.terminalName ||
                                  selectedTerminal.name
                                : "Not Selected"
                        }}
                    </span>
                </div>
                <div
                    class="flex items-center justify-between py-2 border-b border-slate-200"
                >
                    <span class="text-sm text-slate-600">Selected Seats</span>
                    <span
                        class="text-sm font-semibold"
                        :class="
                            selectedSeats.length === 0
                                ? 'text-red-500'
                                : 'text-slate-900'
                        "
                    >
                        {{
                            selectedSeats.length > 0
                                ? selectedSeats.map((s) => s.label).join(", ")
                                : "None Selected"
                        }}
                    </span>
                </div>
                <!-- <div class="flex items-center justify-between py-2 border-b border-slate-200">
                    <span class="text-sm text-slate-600">Number of Seats</span>
                    <span class="text-sm font-semibold text-slate-900">
                        {{ selectedSeats.length }}
                        {{ selectedSeats.length === 1 ? "seat" : "seats" }}
                    </span>
                </div> -->
                <div class="flex items-center justify-between pt-4 mt-2">
                    <span class="text-lg font-bold text-slate-900"
                        >Total Amount</span
                    >
                    <span
                        class="text-2xl font-bold text-transparent bg-primary bg-clip-text"
                        >{{ priceText }}</span
                    >
                </div>
            </div>

            <button
                type="button"
                :disabled="selectedSeats.length === 0"
                @click="handleSubmit"
                class="inline-flex items-center justify-center w-full gap-2 px-6 py-4 mt-6 text-base font-bold text-white transition-all shadow-lg rounded-xl bg-primary hover:-translate-y-1 hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0 disabled:hover:shadow-lg"
            >
                <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                    <path
                        fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd"
                    />
                </svg>
                Confirm & Proceed to Payment
            </button>

            <p class="mt-3 text-xs text-center text-slate-500">
                By confirming, you agree to our terms and conditions
            </p>
        </div>
    </section>
</template>
<style scoped>
@keyframes slideIn {
    0% {
        transform: translateX(100%);
        opacity: 0;
    }

    100% {
        transform: translateX(0);
        opacity: 1;
    }
}

.animate-slideIn {
    animation: slideIn 0.3s ease forwards;
}

/* Custom radio button styles */
input[type="radio"] {
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    background-color: #fff;
    border: 2px solid #d1d5db;
}

input[type="radio"]:checked {
    background-color: #3b82f6;
    border-color: #3b82f6;
    background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3ccircle cx='8' cy='8' r='3'/%3e%3c/svg%3e");
    background-position: center;
    background-repeat: no-repeat;
    background-size: 0.6em 0.6em;
}

/* For Firefox */
input[type="radio"]::-moz-focus-inner {
    border: 0;
}
</style>
