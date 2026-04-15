<script setup>
import GuestLayout from "@/Layouts/GuestLayout.vue";
import InputError from "@/Components/LandingPage/InputError.vue";
import InputLabel from "@/Components/LandingPage/InputLabel.vue";
import PrimaryButton from "@/Components/LandingPage/PrimaryButton.vue";
import TextInput from "@/Components/LandingPage/TextInput.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";
// import { useRoute } from 'vue-router'; // or use window.location if not using Vue Router

// const route = useRoute();
const verificationMessage = ref('');
const verificationType = ref(''); // success, error, info

onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const verified = urlParams.get('verified');

    if (verified === 'success') {
        verificationMessage.value = 'Email verified successfully! You can now log in.';
        verificationType.value = 'success';
    } else if (verified === 'invalid') {
        verificationMessage.value = 'Invalid verification link. Please request a new one.';
        verificationType.value = 'error';
    } else if (verified === 'expired') {
        verificationMessage.value = 'Verification link has expired. Please request a new one.';
        verificationType.value = 'error';
    }
});

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    login: "", // Accepts username, phone, or email
    password: "",
});

const showPassword = ref(false);

const submit = () => {
    form.post(route("login"), {
        onFinish: () => form.reset("password"),
    });
};
</script>

<template>
    <GuestLayout>

        <Head title="Customer Login" />

        <!-- Show verification message at top of login form -->
        <div v-if="verificationMessage" :class="[
            'p-4 fixed right-5 top-5 rounded-lg',
            verificationType === 'success' ? 'bg-green-100 text-green-800 border border-green-300' : '',
            verificationType === 'error' ? 'bg-red-100 text-red-800 border border-red-300' : ''
        ]">
            {{ verificationMessage }}
        </div>

        <div
            class="flex items-center justify-center min-h-screen px-4 py-12 bg-gradient-to-br from-blue-50 to-gray-100 sm:px-6 lg:px-8">
            <div class="w-full max-w-md p-8 space-y-8 bg-white shadow-xl rounded-2xl">
                <!-- Header -->
                <div class="text-center">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-blue-500 rounded-full">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900">
                        Customer Login
                    </h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Welcome back! Please sign in to your account
                    </p>
                </div>

                <!-- Status Message -->
                <div v-if="status" class="px-4 py-3 text-green-700 border border-green-200 rounded-lg bg-green-50">
                    {{ status }}
                </div>

                <!-- Login Form -->
                <form @submit.prevent="submit" class="mt-8 space-y-6">
                    <!-- Login Field -->
                    <div class="space-y-4">
                        <div>
                            <InputLabel for="login" value="Username, Phone, or Email"
                                class="font-medium text-gray-700" />
                            <div class="relative mt-1">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <TextInput id="login" type="text"
                                    class="block w-full pl-10 pr-3 py-2.5 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                                    v-model="form.login" required autofocus autocomplete="username"
                                    placeholder="Enter username, phone number, or email" :class="form.errors.login
                                        ? 'border-red-300'
                                        : ''
                                        " />
                            </div>
                            <InputError class="mt-2" :message="form.errors.login" />
                        </div>

                        <!-- Password Field -->
                        <div>
                            <InputLabel for="password" value="Password" class="font-medium text-gray-700" />
                            <div class="relative mt-1">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <TextInput id="password" :type="showPassword ? 'text' : 'password'"
                                    class="block w-full pl-10 pr-10 py-2.5 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                                    v-model="form.password" required autocomplete="current-password" :class="form.errors.password
                                        ? 'border-red-300'
                                        : ''
                                        " placeholder="Enter your password" />
                                <button type="button" class="absolute inset-y-0 right-0 flex items-center pr-3"
                                    @click="showPassword = !showPassword">
                                    <svg v-if="showPassword" class="w-5 h-5 text-gray-400" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L6.59 6.59m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                    </svg>
                                    <svg v-else class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </div>
                            <InputError class="mt-2" :message="form.errors.password" />
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-4">
                        <PrimaryButton
                            class="w-full justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200 transform hover:-translate-y-0.5"
                            :class="{
                                'opacity-50 cursor-not-allowed':
                                    form.processing,
                            }" :disabled="form.processing">
                            <span v-if="!form.processing" class="flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                </svg>
                                Sign In
                            </span>
                            <span v-else class="flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2 text-white animate-spin" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                Signing in...
                            </span>
                        </PrimaryButton>

                        <p class="mt-3 text-sm text-center text-gray-600">
                            Don't have an account?
                            <a href="/register" class="text-blue-500 hover:underline">
                                Register here
                            </a>
                        </p>
                    </div>

                    <!-- Additional Info -->
                    <div class="pt-4 text-center border-t border-gray-200">
                        <p class="text-xs text-gray-500">
                            Need help? Contact our support team
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </GuestLayout>
</template>
