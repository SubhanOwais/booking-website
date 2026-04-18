<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'

const props = defineProps({
    show: Boolean,
    ticket: Object,
    companyConfig: Object,
})

const emit = defineEmits(['close', 'success', 'conflict'])

const loading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const customRefundPercentage = ref(null)

/* ─── Refund Rules ─────────────────────────────────────────────────── */
const REFUND_RULES = [
    { minHours: 48, percentage: 90 },
    { minHours: 24, percentage: 75 },
]

const parseNumber = (v) => Number(v) || 0

/* ─── Trip DateTime ────────────────────────────────────────────────── */
const tripDateTime = computed(() => {
    if (!props.ticket?.Travel_Date || !props.ticket?.Travel_Time) return null
    const d = new Date(`${props.ticket.Travel_Date}T${props.ticket.Travel_Time}`)
    return isNaN(d.getTime()) ? null : d
})

/* ─── Auto refund % ────────────────────────────────────────────────── */
const baseRefundPercentage = computed(() => {
    if (!tripDateTime.value) return 0
    const hoursLeft = (tripDateTime.value.getTime() - Date.now()) / 3_600_000
    for (const rule of REFUND_RULES) {
        if (hoursLeft > rule.minHours) return rule.percentage
    }
    return 0
})

/* ─── Final % ──────────────────────────────────────────────────────── */
const refundPercentage = computed(() =>
    customRefundPercentage.value !== null
        ? Math.max(parseNumber(customRefundPercentage.value), 0)
        : Math.max(baseRefundPercentage.value, 0)
)

/* ─── Amount ───────────────────────────────────────────────────────── */
const refundAmount = computed(() => {
    if (!props.ticket) return '0.00'
    return ((parseNumber(props.ticket.Fare) * refundPercentage.value) / 100).toFixed(2)
})

/* ─── Dropdown options ─────────────────────────────────────────────── */
const refundOptions = computed(() => [
    { label: `Auto (${baseRefundPercentage.value}%)`, value: null },
    { label: '90% Refund', value: 90 },
    { label: '80% Refund', value: 80 },
    { label: '75% Refund', value: 75 },
    { label: '70% Refund', value: 70 },
    { label: '60% Refund', value: 60 },
    { label: '50% Refund', value: 50 },
    { label: '40% Refund', value: 40 },
    { label: '30% Refund', value: 30 },
    { label: '25% Refund', value: 25 },
    { label: 'No Refund (0%)', value: 0 },
])

/* ─── Actions ──────────────────────────────────────────────────────── */
const closeModal = () => {
    loading.value = false
    errorMessage.value = ''
    successMessage.value = ''
    customRefundPercentage.value = null
    emit('close')
}

const submitRefund = async () => {
    if (!props.ticket) return

    loading.value = true
    errorMessage.value = ''
    successMessage.value = ''

    try {
        const response = await axios.post(route('company.refund.process'), {
            pnr_no: props.ticket.PNR_No,
            refund_percentage: refundPercentage.value,
            refund_amount: refundAmount.value,
        })

        if (response.data.success) {
            successMessage.value = response.data.message || 'Refund processed successfully.'
            setTimeout(() => {
                emit('success', response.data.message)
                closeModal()
            }, 1500)
        } else {
            errorMessage.value = response.data.message || 'Refund failed.'
        }

    } catch (error) {
        // 409 Conflict = another agent already processed this ticket
        if (error.response?.status === 409) {
            emit('conflict')
            closeModal()
            return
        }
        errorMessage.value =
            error.response?.data?.message ||
            error.message ||
            'Unexpected error occurred.'
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0"
        enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100"
        leave-to-class="opacity-0">

        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
            @click.self="closeModal">

            <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl flex flex-col max-h-[90vh]">

                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Confirm Refund</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Review details before processing</p>
                    </div>
                    <button @click="closeModal"
                        class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-slate-100 text-slate-500 transition-colors">
                        <i class="bi bi-x-lg text-sm"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="overflow-y-auto flex-1 px-6 py-4 space-y-4">

                    <!-- Alerts -->
                    <div v-if="errorMessage"
                        class="flex items-start gap-2 p-3 text-sm text-red-800 bg-red-50 border border-red-200 rounded-lg">
                        <i class="bi bi-exclamation-circle-fill mt-0.5 flex-shrink-0"></i>
                        <span>{{ errorMessage }}</span>
                    </div>
                    <div v-if="successMessage"
                        class="flex items-start gap-2 p-3 text-sm text-green-800 bg-green-50 border border-green-200 rounded-lg">
                        <i class="bi bi-check-circle-fill mt-0.5 flex-shrink-0"></i>
                        <span>{{ successMessage }}</span>
                    </div>

                    <!-- Ticket Details -->
                    <div v-if="ticket" class="rounded-xl border border-amber-200 bg-amber-50 overflow-hidden">
                        <div class="px-4 py-2 bg-amber-100 border-b border-amber-200">
                            <p class="text-xs font-semibold text-amber-700 uppercase tracking-wide">Ticket Details</p>
                        </div>
                        <div class="px-4 py-3 space-y-1.5 text-sm">
                            <div class="flex justify-between">
                                <span class="text-slate-500">PNR</span>
                                <span class="font-semibold text-slate-800">{{ ticket.PNR_No }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Passenger</span>
                                <span class="font-semibold text-slate-800">{{ ticket.Passenger_Name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Route</span>
                                <!-- ✅ Flat city name strings from mapTicket() -->
                                <span class="font-semibold text-slate-800">
                                    {{ ticket.from_city_name }} → {{ ticket.to_city_name }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Seats</span>
                                <span class="font-semibold text-slate-800">{{ ticket.Seat_No }}</span>
                            </div>
                            <div class="flex justify-between border-t border-amber-200 pt-1.5 mt-1.5">
                                <span class="text-slate-600 font-medium">Total Fare</span>
                                <span class="font-bold text-slate-800">PKR {{ ticket.Fare }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Refund Reason -->
                    <div v-if="ticket?.Refund_Reason"
                        class="p-3 text-sm border border-slate-200 rounded-xl bg-slate-50">
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Refund Reason</p>
                        <p class="text-slate-700 leading-relaxed">{{ ticket.Refund_Reason }}</p>
                    </div>

                    <!-- Percentage selector -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Select Refund
                            Percentage</label>
                        <select v-model="customRefundPercentage"
                            class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option v-for="opt in refundOptions" :key="String(opt.value)" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                        <p class="text-xs text-slate-400 mt-1">
                            Auto applies the standard policy based on hours until travel.
                        </p>
                    </div>

                    <!-- Refund Amount -->
                    <div class="p-4 rounded-xl border border-red-200 bg-red-50 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-red-500 uppercase tracking-wide">Refund Amount</p>
                            <p class="text-2xl font-bold text-red-700 mt-0.5">PKR {{ refundAmount }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-red-500">Percentage</p>
                            <p class="text-xl font-bold text-red-600">{{ refundPercentage }}%</p>
                        </div>
                    </div>

                    <!-- Rules reference -->
                    <div class="space-y-1.5">
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Standard Rules</p>
                        <div
                            class="flex justify-between items-center px-3 py-2 border border-slate-200 rounded-lg text-sm">
                            <span class="text-slate-600">Before 48 hours</span>
                            <span class="font-semibold text-green-600">90% refund</span>
                        </div>
                        <div
                            class="flex justify-between items-center px-3 py-2 border border-slate-200 rounded-lg text-sm">
                            <span class="text-slate-600">24–48 hours before</span>
                            <span class="font-semibold text-yellow-600">75% refund</span>
                        </div>
                        <div
                            class="flex justify-between items-center px-3 py-2 border border-slate-200 rounded-lg text-sm">
                            <span class="text-slate-600">Within 24 hours</span>
                            <span class="font-semibold text-red-600">No refund</span>
                        </div>
                    </div>

                    <!-- Warning -->
                    <div
                        class="flex items-start gap-2 p-3 text-sm text-red-800 bg-red-100 border border-red-200 rounded-lg">
                        <i class="bi bi-exclamation-triangle-fill mt-0.5 flex-shrink-0"></i>
                        <span>This action will permanently cancel the ticket and cannot be undone.</span>
                    </div>

                </div>

                <!-- Footer -->
                <div
                    class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50 rounded-b-2xl">
                    <button @click="closeModal"
                        class="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
                        Cancel
                    </button>
                    <button @click="submitRefund" :disabled="loading"
                        class="px-5 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors disabled:opacity-60 disabled:cursor-not-allowed flex items-center gap-2 shadow-sm">
                        <svg v-if="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                        </svg>
                        {{ loading ? 'Processing...' : 'Confirm Refund' }}
                    </button>
                </div>

            </div>
        </div>
    </Transition>
</template>
