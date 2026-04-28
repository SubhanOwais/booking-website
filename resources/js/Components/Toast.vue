<script setup>
import { Transition } from 'vue'
import toast from '@/Services/toast'

const toastColor = (type) => ({
    success: 'bg-green-100/50 backdrop-blur border-green-500 text-green-800',
    error: 'bg-red-100/50 backdrop-blur border-red-500 text-red-800',
    warning: 'bg-yellow-100/50 backdrop-blur border-yellow-500 text-yellow-800',
    info: 'bg-blue-100/50 backdrop-blur border-blue-500 text-blue-800',
}[type] || 'bg-gray-100/50 backdrop-blur border-gray-400 text-gray-800')
</script>

<template>
    <div class="fixed top-4 right-4 z-[999] space-y-2 w-72">
        <template v-for="t in toast.state.toasts" :key="t.id">
            <Transition enter-active-class="transition duration-300 ease-out"
                enter-from-class="translate-x-full opacity-0" enter-to-class="translate-x-0 opacity-100"
                leave-active-class="transition duration-200 ease-in" leave-from-class="translate-x-0 opacity-100"
                leave-to-class="translate-x-full opacity-0">
                <div v-if="t.visible"
                    :class="['flex items-start gap-3 p-4 rounded shadow-lg border-l-4', toastColor(t.severity)]">

                    <div class="flex-1">
                        <p class="font-semibold text-sm">{{ t.summary }}</p>
                        <p class="text-xs mt-1 opacity-80">{{ t.detail }}</p>
                    </div>

                    <button @click="toast.remove(t.id)" class="self-start text-3xl leading-none">
                        &times;
                    </button>
                </div>
            </Transition>
        </template>
    </div>
</template>
