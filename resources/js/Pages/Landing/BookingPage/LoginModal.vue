<script setup>
import { useForm } from "@inertiajs/vue3";
import { onMounted, watch, ref } from "vue";
import InputError from "@/Components/LandingPage/InputError.vue";
import InputLabel from "@/Components/LandingPage/InputLabel.vue";
import TextInput from "@/Components/LandingPage/TextInput.vue";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["close", "success"]);

const form = useForm({
    login: "", // ✅ SAME AS MAIN LOGIN
    password: "",
    is_modal: true,
});

const showPassword = ref(false);

function close() {
    form.reset();
    form.clearErrors();
    emit("close");
}

const submit = () => {
    form.post(route("login"), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            emit("success");
            close();
        },
        onFinish: () => form.reset("password"),
    });
};

// ESC close
function handleEscape(e) {
    if (e.key === "Escape" && props.show) close();
}

// Backdrop close
function handleBackdropClick(e) {
    if (e.target === e.currentTarget) close();
}

onMounted(() => {
    window.addEventListener("keydown", handleEscape);
});

watch(
    () => props.show,
    (open) => {
        if (open) {
            form.reset();
            form.clearErrors();
            form.is_modal = true;
        }
    }
);
</script>

<template>
    <Teleport to="body">
        <Transition enter-active-class="transition-opacity duration-300" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition-opacity duration-200"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="show"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                @click="handleBackdropClick">
                <Transition enter-active-class="transition-all duration-300"
                    enter-from-class="scale-95 translate-y-4 opacity-0"
                    enter-to-class="scale-100 translate-y-0 opacity-100"
                    leave-active-class="transition-all duration-200"
                    leave-from-class="scale-100 translate-y-0 opacity-100"
                    leave-to-class="scale-95 translate-y-4 opacity-0">
                    <div class="relative w-full max-w-md p-6 bg-white shadow-xl rounded-2xl" @click.stop>
                        <!-- Close -->
                        <button class="absolute text-gray-400 right-4 top-4 hover:text-gray-600" @click="close">
                            ✕
                        </button>

                        <!-- Header -->
                        <h2 class="text-xl font-bold text-gray-900">
                            Login Required
                        </h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Please login to continue seat selection
                        </p>

                        <!-- Form -->
                        <form class="mt-6 space-y-4" @submit.prevent="submit">
                            <!-- Login -->
                            <div>
                                <InputLabel for="login" value="Username, Phone, or Email" />
                                <TextInput id="login" v-model="form.login" class="w-full mt-1" autofocus required
                                    autocomplete="username" placeholder="Enter username, phone, or email" />
                                <InputError class="mt-1" :message="form.errors.login" />
                            </div>

                            <!-- Password -->
                            <div>
                                <InputLabel for="password" value="Password" />
                                <div class="relative mt-1">
                                    <TextInput id="password" :type="showPassword ? 'text' : 'password'
                                        " v-model="form.password" class="w-full pr-10" required
                                        autocomplete="current-password" placeholder="Enter your password" />
                                    <button type="button" class="absolute inset-y-0 right-0 px-3 text-gray-400"
                                        @click="showPassword = !showPassword">
                                        {{ showPassword ? "🙈" : "👁️" }}
                                    </button>
                                </div>
                                <InputError class="mt-1" :message="form.errors.password" />
                            </div>

                            <!-- Actions -->
                            <div class="flex justify-end gap-3 pt-4">
                                <button type="button" class="px-5 py-2 text-sm font-semibold bg-gray-200 rounded-xl"
                                    @click="close">
                                    Cancel
                                </button>

                                <button type="submit" :disabled="form.processing"
                                    class="px-6 py-2 text-sm font-semibold text-white rounded-xl bg-primary disabled:opacity-50">
                                    {{
                                        form.processing
                                            ? "Logging in…"
                                            : "Login"
                                    }}
                                </button>
                            </div>
                        </form>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
