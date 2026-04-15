<script setup>
import { ref, onUnmounted, watch } from "vue";
import axios from "axios";

// Toast system
const toasts = ref([]);
let toastId = 0;

// Form data
const name = ref("");
const email = ref("");
const phone_number = ref("");
const password = ref("");
const confirm_password = ref("");
const address = ref("");
const cnic = ref("");
const acceptedTerms = ref(false);
const profile_picture = ref(null);
const userImage = ref(null);

// UI state
const loading = ref(false);
const errors = ref({});
const showProfileModal = ref(false);
const selectedProfileAvatar = ref(null);
const imageProfilePicPreview = ref(null);
const registrationSuccess = ref(false);
const registeredEmail = ref("");

// Default avatars
const defaultAvatars = [
    "avatar1.png",
    "avatar2.png",
    "avatar3.png",
    "avatar4.png",
    "avatar5.png",
    "avatar6.png",
    "avatar7.png",
    "avatar8.png",
    "avatar9.png",
    "avatar10.png",
    "avatar11.png",
    "avatar12.png",
    "avatar13.png",
    "avatar14.png",
    "avatar15.png",
    "avatar16.png",
    "avatar17.png",
    "avatar18.png",
    "avatar19.png",
    "avatar20.png",
    "avatar21.png",
    "avatar22.png",
    "avatar23.png",
    "avatar24.png",
    "avatar25.png",
    "avatar26.png",
    "avatar27.png",
    "avatar28.png",
    "avatar29.png",
    "avatar30.png",
    "avatar31.png",
    "avatar32.png",
    "avatar33.png",
    "avatar34.png",
    "avatar35.png",
    "avatar36.png",
    "avatar37.png",
    "avatar38.png",
    "avatar39.png",
    "avatar40.png",
    "avatar41.png",
    "avatar42.png",
    "avatar43.png",
    "avatar44.png",
    "avatar45.png",
    "avatar46.png",
    "avatar47.png",
    "avatar48.png",
    "avatar49.png",
    "avatar50.png",
];

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

watch(
    () => cnic.value,
    (newVal) => {
        const formatted = formatCnic(newVal);
        if (formatted !== newVal) {
            cnic.value = formatted;
        }
    }
);

// Toast functions
const showToast = (type, summary, detail, duration = 5000) => {
    const id = ++toastId;
    const toast = {
        id,
        type,
        summary,
        detail,
        duration,
        visible: true,
    };

    toasts.value.push(toast);

    setTimeout(() => {
        removeToast(id);
    }, duration);

    return id;
};

const removeToast = (id) => {
    const index = toasts.value.findIndex((t) => t.id === id);
    if (index !== -1) {
        toasts.value[index].visible = false;
        setTimeout(() => {
            toasts.value.splice(index, 1);
        }, 300);
    }
};

// Open profile picture modal
const openProfileModal = () => {
    showProfileModal.value = true;
};

// Close profile picture modal
const closeProfileModal = () => {
    showProfileModal.value = false;
};

// Select default avatar
const selectAvatar = (avatar) => {
    selectedProfileAvatar.value = avatar;
    userImage.value = null;
    imageProfilePicPreview.value = null;
    profile_picture.value = `@img/avatars/${avatar}`;
};

// Handle image upload
const handleImageUpload = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    if (file.size > 2 * 1024 * 1024) {
        showToast(
            "error",
            "File too large",
            "Please select an image smaller than 2MB",
            3000
        );
        return;
    }

    if (!file.type.match("image.*")) {
        showToast(
            "error",
            "Invalid file type",
            "Please select an image file",
            3000
        );
        return;
    }

    userImage.value = file;
    selectedProfileAvatar.value = null;

    const reader = new FileReader();
    reader.onload = (e) => {
        imageProfilePicPreview.value = e.target.result;
        profile_picture.value = e.target.result;
    };
    reader.readAsDataURL(file);
};

// Remove profile image
const removeProfileImage = () => {
    userImage.value = null;
    profile_picture.value = null;
    imageProfilePicPreview.value = null;
    selectedProfileAvatar.value = null;
    showToast(
        "info",
        "Profile Picture Removed",
        "Profile picture has been removed",
        2000
    );
};

// Save profile picture
const saveProfilePicture = () => {
    closeProfileModal();
    showToast("success", "Success", "Profile picture updated", 2000);
};

// Clear error for a specific field
const clearError = (field) => {
    if (errors.value[field]) {
        delete errors.value[field];
    }
};

// Validate form
const validateForm = () => {
    errors.value = {};

    if (!name.value.trim()) {
        errors.value.name = "Name is required";
    } else if (name.value.trim().length < 2) {
        errors.value.name = "Name must be at least 2 characters";
    }

    if (!email.value.trim()) {
        errors.value.email = "Email is required";
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
        errors.value.email = "Please enter a valid email address";
    }

    if (!phone_number.value.trim()) {
        errors.value.phone_number = "Phone number is required";
    } else if (
        !/^(03\d{9}|\+92\d{10})$/.test(phone_number.value.replace(/\D/g, ""))
    ) {
        errors.value.phone_number = "Please enter a valid phone number";
    }

    if (!cnic.value.trim()) {
        errors.value.cnic = "CNIC is required";
    } else if (!/^\d{5}-\d{7}-\d$/.test(cnic.value)) {
        errors.value.cnic =
            "Please enter a valid CNIC format (e.g., 12345-6789012-3)";
    }

    if (!password.value) {
        errors.value.password = "Password is required";
    } else if (password.value.length < 6) {
        errors.value.password = "Password must be at least 6 characters";
    }

    if (!confirm_password.value) {
        errors.value.confirm_password = "Please confirm your password";
    } else if (password.value !== confirm_password.value) {
        errors.value.confirm_password = "Passwords do not match";
    }

    if (!acceptedTerms.value) {
        showToast(
            "error",
            "Terms Required",
            "You must accept the terms and conditions",
            3000
        );
        return false;
    }

    return Object.keys(errors.value).length === 0;
};

// Submit form
const submitForm = async () => {
    if (!validateForm()) {
        return;
    }

    loading.value = true;

    try {
        const formData = new FormData();

        formData.append("name", name.value.trim());
        formData.append("email", email.value.trim());
        formData.append("phone_number", phone_number.value.trim());
        formData.append("password", password.value);
        formData.append("password_confirmation", confirm_password.value);
        formData.append("address", address.value.trim());
        formData.append("cnic", unformatCnic(cnic.value));
        formData.append("user_type", "WebCustomer");

        if (userImage.value) {
            formData.append("profile_picture", userImage.value);
        } else if (selectedProfileAvatar.value) {
            formData.append("avatar", selectedProfileAvatar.value);
        }

        const response = await axios.post("/auth/register", formData, {
            headers: {
                "Content-Type": "multipart/form-data",
                Accept: "application/json",
            },
        });

        // Success - Show verification message
        registrationSuccess.value = true;
        registeredEmail.value = response.data.email;

        showToast(
            "success",
            "Registration Successful!",
            "Please check your email to verify your account.",
            10000
        );

    } catch (error) {
        console.error("Registration error:", error);

        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {};

            const firstError = Object.values(errors.value)[0]?.[0];
            if (firstError) {
                showToast("error", "Validation Error", firstError, 5000);
            }
        } else if (error.response?.status === 409) {
            showToast(
                "error",
                "Registration Failed",
                error.response.data.message ||
                "User already exists with this email or phone",
                5000
            );
        } else {
            showToast(
                "error",
                "Error",
                "An error occurred. Please try again later.",
                5000
            );
        }
    } finally {
        loading.value = false;
    }
};

// Resend verification email
const resendVerification = async () => {
    loading.value = true;

    try {
        await axios.post("/auth/resend-verification", {
            email: registeredEmail.value
        });

        showToast(
            "success",
            "Email Sent",
            "Verification email has been resent. Please check your inbox.",
            5000
        );
    } catch (error) {
        showToast(
            "error",
            "Error",
            "Failed to resend verification email. Please try again.",
            5000
        );
    } finally {
        loading.value = false;
    }
};

// Clean up toasts on unmount
onUnmounted(() => {
    toasts.value.forEach((toast) => {
        if (toast.timeoutId) {
            clearTimeout(toast.timeoutId);
        }
    });
});
</script>

<template>
    <!-- Toast Container -->
    <div class="fixed top-4 right-4 z-[100] space-y-2">
        <div v-for="toast in toasts" :key="toast.id" :class="[
            'flex items-center p-4 rounded-lg shadow-lg border-l-4 transition-all duration-300 transform',
            toast.type === 'success'
                ? 'bg-green-50 border-green-500 text-green-800'
                : '',
            toast.type === 'error'
                ? 'bg-red-50 border-red-500 text-red-800'
                : '',
            toast.type === 'info'
                ? 'bg-blue-50 border-blue-500 text-blue-800'
                : '',
            toast.type === 'warning'
                ? 'bg-yellow-50 border-yellow-500 text-yellow-800'
                : '',
            toast.visible
                ? 'translate-x-0 opacity-100'
                : 'translate-x-full opacity-0',
        ]">
            <!-- Toast Icon -->
            <div class="flex-shrink-0 mr-3">
                <svg v-if="toast.type === 'success'" class="w-5 h-5 text-green-500" fill="currentColor"
                    viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                        clip-rule="evenodd" />
                </svg>
                <svg v-else-if="toast.type === 'error'" class="w-5 h-5 text-red-500" fill="currentColor"
                    viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                        clip-rule="evenodd" />
                </svg>
                <svg v-else-if="toast.type === 'warning'" class="w-5 h-5 text-yellow-500" fill="currentColor"
                    viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                        clip-rule="evenodd" />
                </svg>
                <svg v-else class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                        clip-rule="evenodd" />
                </svg>
            </div>

            <!-- Toast Content -->
            <div class="flex-1">
                <div class="font-medium">{{ toast.summary }}</div>
                <div class="text-sm">{{ toast.detail }}</div>
            </div>

            <!-- Close Button -->
            <button @click="removeToast(toast.id)" class="ml-4 text-gray-400 hover:text-gray-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Email Verification Success Message -->
    <div v-if="registrationSuccess" class="flex items-center justify-center min-h-screen p-4 bg-gray-50">
        <div class="w-full max-w-md p-8 bg-white border border-gray-300 shadow-lg rounded-2xl">
            <div class="text-center">
                <!-- Success Icon -->
                <div class="flex justify-center mb-6">
                    <div class="flex items-center justify-center w-20 h-20 bg-green-100 rounded-full">
                        <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>

                <h2 class="mb-4 text-2xl font-bold text-gray-800">Check Your Email!</h2>

                <p class="mb-6 text-gray-600">
                    We've sent a verification email to:
                </p>

                <p class="mb-6 text-lg font-semibold text-blue-600">
                    {{ registeredEmail }}
                </p>

                <p class="mb-6 text-sm text-gray-600">
                    Please click the verification link in the email to activate your account.
                    The link will expire in 24 hours.
                </p>

                <div class="space-y-3">
                    <button @click="resendVerification" :disabled="loading"
                        class="w-full px-6 py-3 text-white transition bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50">
                        <span v-if="loading">Sending...</span>
                        <span v-else">Resend Verification Email</span>
                    </button>


                    <a href="/login"
                        class="block w-full px-6 py-3 text-center text-gray-700 transition bg-gray-100 rounded-lg hover:bg-gray-200">
                        Go to Login
                    </a>
                </div>

                <p class="mt-6 text-xs text-gray-500">
                    Didn't receive the email? Check your spam folder or click resend.
                </p>
            </div>
        </div>
    </div>

    <!-- Registration Form (shown when not successful) -->
    <div v-else class="flex items-center justify-center min-h-screen p-4 bg-gray-50">
        <div class="relative w-full max-w-2xl p-6 bg-white border border-gray-300 shadow-lg rounded-2xl">
            <div class="flex flex-wrap items-center pb-4 border-b border-gray-200">
                <!-- <img src="@img/logo.jpg" alt="Royal Bus Logo" class="object-contain w-12 h-12 rounded-full" /> -->
                 <div class="flex items-center justify-center h-12 w-12 rounded-xl bg-primary/10 ring-1 ring-primary/15">
                    <svg class="w-10 h-10 text-primary" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
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
                <div class="px-3">
                    <h1 class="text-2xl font-bold text-gray-800">
                        Create Account
                    </h1>
                    <p class="text-sm text-gray-600">Join our platform today</p>
                </div>
                <a href="/" class="">
                    <div class="remove-button !top-3 !right-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                            viewBox="0 0 16 16">
                            <path
                                d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.500 0 0 1 0-.708z" />
                        </svg>
                    </div>
                </a>
            </div>

            <!-- Registration Form -->
            <form @submit.prevent="submitForm" class="pt-3">
                <div class="flex flex-wrap">
                    <!-- Profile Picture Section -->
                    <div class="w-full mb-2">
                        <label for="profilePicture" class="block mb-2 text-sm font-medium text-gray-900">
                            Profile Picture
                        </label>
                        <div class="flex items-center space-x-4">
                            <div class="w-28 h-28">
                                <img v-if="!profile_picture" src="https://placehold.co/150x150" alt="Profile"
                                    class="object-cover w-full h-full rounded-full shadow" />
                                <img v-else :src="profile_picture" alt="Profile"
                                    class="object-cover w-full h-full border border-gray-300 rounded-full shadow" />
                            </div>
                            <div class="flex flex-col">
                                <button type="button" @click="openProfileModal"
                                    class="font-medium rounded-lg mb-3 w-28 text-sm px-5 py-2.5 text-center text-white bg-secondary hover:bg-primary flex justify-center items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                        class="mr-2 bi bi-image-fill" viewBox="0 0 16 16">
                                        <path
                                            d="M.002 3a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-12a2 2 0 0 1-2-2zm1 9v1a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V9.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062zm5-6.5a1.5 1.5 0 1 0-3 0 1.5 1.5 0 0 0 3 0" />
                                    </svg>
                                    Change
                                </button>
                                <button type="button" @click="removeProfileImage"
                                    class="font-medium rounded-lg w-28 text-sm px-5 py-2.5 text-center text-red-500 bg-red-100 hover:bg-red-200 flex justify-center items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                        class="mr-2 bi bi-trash3-fill" viewBox="0 0 16 16">
                                        <path
                                            d="M11 1.5v1h3.5a.5.5 0 0 1 0 1h-.538l-.853 10.66A2 2 0 0 1 11.115 16h-6.23a2 2 0 0 1-1.994-1.84L2.038 3.5H1.5a.5.5 0 0 1 0-1H5v-1A1.5 1.5 0 0 1 6.5 0h3A1.5 1.5 0 0 1 11 1.5m-5 0v1h4v-1a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5M4.5 5.029l.5 8.5a.5.5 0 1 0 .998-.06l-.5-8.5a.5.5 0 1 0-.998.06m6.53-.528a.5.5 0 0 0-.528.47l-.5 8.5a.5.5 0 0 0 .998.058l.5-8.5a.5.5 0 0 0-.47-.528M8 4.5a.5.5 0 0 0-.5.5v8.5a.5.5 0 0 0 1 0V5a.5.5 0 0 0-.5-.5" />
                                    </svg>
                                    Remove
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Name Field -->
                    <div class="w-full p-2 sm:w-1/2">
                        <label for="name" class="block mb-2 text-sm font-medium text-gray-900">
                            Your Name
                            <span class="text-xs text-red-600">(Required)</span>
                        </label>
                        <input type="text" v-model="name" id="name" @input="clearError('name')"
                            class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg block w-full p-2.5"
                            placeholder="Your Name" :class="errors.name ? 'border-red-500' : ''" />
                        <span v-if="errors.name" class="text-sm text-red-500">
                            {{ errors.name }}
                        </span>
                    </div>

                    <!-- Email Field -->
                    <div class="w-full p-2 sm:w-1/2">
                        <label for="email" class="block mb-2 text-sm font-medium text-gray-900">
                            Your Email
                            <span class="text-xs text-red-600">(Required)</span>
                        </label>
                        <input type="email" v-model="email" id="email" @input="clearError('email')"
                            class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg block w-full p-2.5"
                            placeholder="name@company.com" :class="errors.email ? 'border-red-500' : ''" />
                        <span v-if="errors.email" class="text-sm text-red-500">
                            {{ errors.email }}
                        </span>
                    </div>

                    <!-- Phone Number -->
                    <div class="w-full p-2 sm:w-1/2">
                        <label for="phone_number" class="block mb-2 text-sm font-medium text-gray-900">
                            Phone Number
                            <span class="text-xs text-red-600">(Required)</span>
                        </label>
                        <div class="relative w-full">
                            <div class="absolute inset-y-0 start-0 top-0 flex items-center ps-3.5 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 19 18">
                                    <path
                                        d="M18 13.446a3.02 3.02 0 0 0-.946-1.985l-1.4-1.4a3.054 3.054 0 0 0-4.218 0l-.7.7a.983.983 0 0 1-1.39 0l-2.1-2.1a.983.983 0 0 1 0-1.389l.7-.7a2.98 2.98 0 0 0 0-4.217l-1.4-1.4a2.824 2.824 0 0 0-4.218 0c-3.619 3.619-3 8.229 1.752 12.979C6.785 16.639 9.45 18 11.912 18a7.175 7.175 0 0 0 5.139-2.325A2.9 2.9 0 0 0 18 13.446Z" />
                                </svg>
                            </div>
                            <input type="text" id="phone_number" v-model="phone_number"
                                @input="clearError('phone_number')"
                                class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg block w-full p-2.5 pl-9"
                                placeholder="123-456-7890" :class="errors.phone_number ? 'border-red-500' : ''
                                    " />
                        </div>
                        <span v-if="errors.phone_number" class="text-sm text-red-500">
                            {{ errors.phone_number }}
                        </span>
                    </div>

                    <div class="w-full p-2 sm:w-1/2">
                        <label for="cnic" class="block mb-2 text-sm font-semibold text-slate-900">
                            CNIC Number
                            <span class="text-xs text-red-500">(Required)</span>
                        </label>
                        <input id="cnic" v-model="cnic" type="text" autocomplete="off" placeholder="12345-1234567-1"
                            class="w-full px-4 py-3 text-sm transition border rounded-xl text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2"
                            :class="errors.cnic
                                ? 'border-red-500 bg-red-50 focus:border-red-500 focus:ring-red-200'
                                : 'border-slate-300 bg-white focus:border-secondary focus:ring-secondary/20'
                                " />
                        <span v-if="errors.cnic" class="text-sm text-red-500">
                            {{ errors.cnic }}
                        </span>
                    </div>

                    <!-- Password Field -->
                    <div class="w-full p-2 sm:w-1/2">
                        <label for="password" class="block mb-2 text-sm font-medium text-gray-900">
                            Password
                            <span class="text-xs text-red-600">(Required)</span>
                        </label>
                        <input type="password" v-model="password" id="password" @input="clearError('password')"
                            placeholder="••••••••"
                            class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg block w-full p-2.5"
                            :class="errors.password ? 'border-red-500' : ''" />
                        <span v-if="errors.password" class="text-sm text-red-500">
                            {{ errors.password }}
                        </span>
                    </div>

                    <!-- Confirm Password Field -->
                    <div class="w-full p-2 sm:w-1/2">
                        <label for="confirm_password" class="block mb-2 text-sm font-medium text-gray-900">
                            Confirm Password
                            <span class="text-xs text-red-600">(Required)</span>
                        </label>
                        <input type="password" v-model="confirm_password" id="confirm_password"
                            @input="clearError('confirm_password')" placeholder="••••••••"
                            class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg block w-full p-2.5"
                            :class="errors.confirm_password ? 'border-red-500' : ''
                                " />
                        <span v-if="errors.confirm_password" class="text-sm text-red-500">
                            {{ errors.confirm_password }}
                        </span>
                    </div>

                    <!-- Address Field -->
                    <div class="w-full p-2">
                        <label for="address" class="block mb-2 text-sm font-medium text-gray-900">
                            Your Address
                        </label>
                        <input type="text" v-model="address" id="address" @input="clearError('address')"
                            class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg block w-full p-2.5"
                            placeholder="Office location" />
                    </div>

                    <!-- Terms and Conditions -->
                    <div class="w-full p-2">
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input id="terms" v-model="acceptedTerms" type="checkbox"
                                    class="w-4 h-4 border border-gray-300 rounded bg-gray-50 focus:ring-3 focus:ring-blue-300" />
                            </div>
                            <label for="terms" class="ml-2 text-sm text-gray-600">
                                I agree to the

                                <a href="#" class="text-secondary hover:underline">Terms and Conditions</a>
                            </label>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="w-full p-2">
                        <button type="submit"
                            class="w-full px-6 py-3 text-lg font-medium text-white transition bg-secondary rounded-lg hover:bg-primary focus:ring-4 focus:ring-secondary/30 disabled:opacity-50"
                            :disabled="loading">
                            <span v-if="loading">Creating Account...</span>
                            <span v-else>Create Account</span>
                        </button>

                        <!-- Login Link -->
                        <p class="mt-2 text-center text-gray-600">
                            Already have an account?

                            <a href="/login" class="font-medium text-secondary hover:underline">
                                Sign in
                            </a>
                        </p>

                        <p class="mt-2 text-center text-gray-600">
                            Partner with Safar

                            <a href="/partner-request" class="font-medium text-secondary hover:underline">
                                Become a Partner
                            </a>
                        </p>
                    </div>


                    <!-- Become a Partner -->
                    <div class="w-full mt-1 pt-2 border-t border-gray-300 bg-white/5 ring-1 ring-white/10 hidden">
                        <div class="grid items-center gap-4 md:grid-cols-7">
                            <!-- Left: Text -->
                            <div class="md:col-span-4">
                                <h3 class="text-lg font-semibold text-secondary">
                                    Partner with Safar
                                </h3>
                                <p class="mt-1 text-sm text-gray-800/70">
                                    Grow your bus business by listing routes, managing schedules,
                                    and reaching thousands of daily travelers.
                                </p>
                            </div>

                            <!-- Right: Button -->
                            <div class="flex md:justify-end md:col-span-3">
                                <a
                                    href="/become-partner"
                                    class="inline-flex items-center justify-center w-full px-4 py-3 text-base font-semibold text-white transition rounded-lg md:w-auto bg-secondary hover:bg-primary focus:ring-4 focus:ring-secondary/30"
                                >
                                    Become a Partner
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Profile Picture Modal -->
    <div v-if="showProfileModal" class="relative z-[70]">
        <div class="fixed inset-0 transition-opacity bg-black/40 backdrop-blur-sm"></div>

        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex items-center justify-center min-h-full p-4 text-center sm:items-center sm:p-0">
                <div
                    class="relative overflow-hidden text-left transition-all transform bg-white shadow-xl rounded-2xl sm:my-8 sm:w-full sm:max-w-2xl">
                    <!-- Modal Header -->
                    <div class="flex justify-between px-4 py-3 bg-gray-100 border-b border-gray-200 sm:px-6">
                        <h2 class="text-lg font-bold">Profile Picture</h2>
                        <button @click="closeProfileModal" class="text-gray-500 hover:text-gray-900">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="p-3 bg-white md:p-5">
                        <div class="flex flex-wrap">
                            <!-- Image Upload Section -->
                            <div class="w-full mb-1 avatar-section">
                                <label class="block mb-2 text-sm font-medium text-gray-900">
                                    Choose Avatar
                                </label>
                                <div class="p-1 pl-0 border border-gray-200 rounded-md">
                                    <div class="avatar-grid overflow-y-auto max-h-[410px]">
                                        <div v-for="(
avatar, index
                                            ) in defaultAvatars" :key="index" class="avatar-item" :class="{
                                                selected:
                                                    selectedProfileAvatar ===
                                                    avatar,
                                            }" @click="selectAvatar(avatar)">
                                            <img :src="'/images/avatars/' + avatar
                                                " class="border border-gray-300 rounded-full shadow"
                                                :alt="'Avatar ' + (index + 1)" />
                                        </div>

                                        <!-- Custom Upload Option -->
                                        <div class="avatar-item upload-option">
                                            <input type="file" @change="handleImageUpload" accept="image/*"
                                                id="custom-avatar-upload" class="hidden" />
                                            <label for="custom-avatar-upload"
                                                class="border border-gray-300 rounded-full shadow upload-label" :class="{
                                                    selected:
                                                        imageProfilePicPreview,
                                                }">
                                                <div v-if="
                                                    imageProfilePicPreview
                                                " class="preview-container">
                                                    <img :src="imageProfilePicPreview
                                                        " alt="Custom avatar" />
                                                </div>
                                                <div v-else class="upload-placeholder">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                        fill="currentColor" class="bi bi-plus-lg" viewBox="0 0 16 16">
                                                        <path fill-rule="evenodd"
                                                            d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2" />
                                                    </svg>
                                                </div>
                                            </label>
                                            <div class="remove-button" v-if="imageProfilePicPreview" @click.prevent="
                                                removeProfileImage
                                            ">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                                    fill="currentColor" viewBox="0 0 16 16">
                                                    <path
                                                        d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.500 0 0 1 0-.708z" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-end px-4 py-3 bg-gray-100 border-t border-gray-200 sm:px-6">
                        <button @click="saveProfilePicture"
                            class="px-4 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                            Save
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.avatar-section h3 {
    margin-bottom: 1rem;
    font-size: 1.1rem;
    color: #333;
}

.avatar-grid {
    place-items: flex-end;
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.avatar-item {
    aspect-ratio: 1;
    cursor: pointer;
    overflow: hidden;
    border: 3px solid transparent;
}

.avatar-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

.avatar-item.selected {
    border-color: #2563eb;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
    border-radius: 50%;
}

.upload-option {
    justify-self: stretch;
    position: relative;
}

.upload-label {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    background: #e2e8f0;
    cursor: pointer;
}

.upload-placeholder {
    font-size: 0.8rem;
    color: #64748b;
    text-align: center;
}

.preview-container {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    overflow: hidden;
}

.preview-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.remove-button {
    position: absolute;
    z-index: 50;
    top: 2px;
    right: 2px;
    background: #ef4444;
    color: white;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    transition: all 0.2s ease;
}

.remove-button:hover {
    background: #dc2626;
    transform: scale(1.1);
}

.hidden {
    display: none;
}

@media (max-width: 768px) {
    .avatar-grid {
        grid-template-columns: repeat(6, 1fr);
    }
}
</style>
