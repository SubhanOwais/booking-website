<!-- resources/js/Pages/Profile/Sections/PaymentMethods.vue -->
<template>
    <div
        class="overflow-hidden bg-white border border-gray-200 rounded-lg shadow-sm"
    >
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-800">Payment Methods</h3>
                <button
                    @click="$emit('add-method')"
                    class="text-sm text-primary hover:text-primary-dark"
                >
                    <i class="mr-1 fas fa-plus"></i> Add New
                </button>
            </div>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div
                    v-for="card in methods"
                    :key="card.id"
                    class="p-4 transition border border-gray-200 rounded-lg hover:border-gray-300"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center mb-2">
                                <div
                                    class="flex items-center justify-center w-12 h-8 mr-3 bg-gray-100 rounded"
                                >
                                    <i class="text-blue-600 fab fa-cc-visa"></i>
                                </div>
                                <span class="font-medium"
                                    >•••• {{ card.lastFour }}</span
                                >
                            </div>
                            <p class="text-sm text-gray-600">
                                Expires {{ card.expiry }}
                            </p>
                        </div>
                        <button
                            @click="$emit('remove-method', card.id)"
                            class="text-gray-400 transition hover:text-red-600"
                        >
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                    <div class="mt-3">
                        <span
                            v-if="card.isDefault"
                            class="inline-block px-2 py-1 text-xs text-blue-700 rounded bg-blue-50"
                        >
                            Default
                        </span>
                    </div>
                </div>
            </div>

            <div
                v-if="methods.length === 0"
                class="py-4 text-center text-gray-500"
            >
                <div
                    class="inline-flex items-center justify-center w-12 h-12 mb-3 bg-gray-100 rounded-full"
                >
                    <i class="text-gray-400 fas fa-credit-card"></i>
                </div>
                <p class="text-gray-600">No payment methods saved</p>
                <button
                    @click="$emit('add-method')"
                    class="mt-2 text-primary hover:underline"
                >
                    Add a payment method
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    methods: {
        type: Array,
        default: () => [],
    },
});

defineEmits(["add-method", "remove-method"]);
</script>
