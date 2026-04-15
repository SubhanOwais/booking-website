<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'

const props = defineProps({
    show: Boolean,
    ticket: Object,
})

const emit = defineEmits(['close', 'success'])

const loading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const customRefundPercentage = ref(null) // For manual override

const parseNumber = (value) => Number(value) || 0

/* ================================
   Refund Configuration (EDIT HERE)
================================ */
const REFUND_RULES = [
    { minHours: 48, percentage: 90 }, // before 48 hours
    { minHours: 24, percentage: 75 }, // 24–48 hours
]

const ADMIN_CUT = 0

/* ================================
   Trip DateTime
================================ */
const tripDateTime = computed(() => {
    if (!props.ticket?.Travel_Date || !props.ticket?.Travel_Time) return null

    const date = new Date(`${props.ticket.Travel_Date}T${props.ticket.Travel_Time}`)
    return isNaN(date.getTime()) ? null : date
})

/* ================================
   Base Refund Percentage
================================ */
const baseRefundPercentage = computed(() => {
    if (!tripDateTime.value) return 0

    const hoursLeft =
        (tripDateTime.value.getTime() - Date.now()) / (1000 * 60 * 60)

    for (const rule of REFUND_RULES) {
        if (hoursLeft > rule.minHours) {
            return rule.percentage
        }
    }

    return 0
})

/* ================================
   Final Refund Percentage (with custom override)
================================ */
const refundPercentage = computed(() => {
    // If custom percentage is set, use it
    if (customRefundPercentage.value !== null) {
        return Math.max(parseNumber(customRefundPercentage.value), 0)
    }
    // Otherwise use calculated percentage
    return Math.max(baseRefundPercentage.value - ADMIN_CUT, 0)
})

/* ================================
   Refund Amount
================================ */
const refundAmount = computed(() => {
    if (!props.ticket) return '0.00'

    const totalFare = parseNumber(props.ticket.Fare)
    return ((totalFare * refundPercentage.value) / 100).toFixed(2)
})

/* ================================
   Refund Options for Dropdown
================================ */
const refundOptions = computed(() => {
    const autoPercentage = Math.max(baseRefundPercentage.value - ADMIN_CUT, 0)

    return [
        { label: `Auto (${autoPercentage}%)`, value: null },
        { label: '90% Refund', value: 90 },
        { label: '80% Refund', value: 80 },
        { label: '75% Refund', value: 75 },
        { label: '65% Refund (40% after service)', value: 65 },
        { label: '50% Refund', value: 50 },
        { label: '25% Refund', value: 25 },
        { label: 'No Refund (0%)', value: 0 },
    ]
})

// ---------- Actions ----------
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
        const response = await axios.post(route('admin.refund.process'), {
            pnr_no: props.ticket.PNR_No,
            company_id: props.ticket.Company_Id || props.ticket.company_id, // Handle
            refund_percentage: refundPercentage.value,
            refund_amount: refundAmount.value,
            company_name: props.ticket.company_name || props.ticket.Company_Name, // Add company name
        })

        if (response.data.success) {
            successMessage.value = response.data.message || 'Refund processed successfully'

            setTimeout(() => {
                emit('success')
                closeModal()
            }, 2000)
        } else {
            errorMessage.value = response.data.message || 'Refund failed'
        }
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message ||
            error.message ||
            'Unexpected error occurred'
        console.error(error)
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100"
        leave-active-class="transition duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
            @click.self="closeModal">
            <div class="w-full max-w-xl p-6 bg-white rounded-lg shadow-xl">
                <!-- Header -->
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Confirm Refund</h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                        ✕
                    </button>
                </div>

                <!-- Messages -->
                <div v-if="errorMessage" class="p-3 mb-3 text-sm text-red-800 bg-red-100 rounded">
                    {{ errorMessage }}
                </div>

                <div v-if="successMessage" class="p-3 mb-3 text-sm text-green-800 bg-green-100 rounded">
                    {{ successMessage }}
                </div>

                <!-- Ticket Details -->
                <div v-if="ticket" class="p-3 mb-0 text-sm border border-orange-200 bg-orange-50">
                    <div class="flex justify-between"><span class="font-semibold text-orange-700">PNR</span><b>{{
                        ticket.PNR_No }}</b>
                    </div>
                    <div class="flex justify-between"><span class="font-semibold text-orange-700">Passenger</span><b>{{
                        ticket.Passenger_Name }}</b></div>
                    <div class="flex justify-between">
                        <span class="font-semibold text-orange-700">Route</span>
                        <b>{{ ticket.from_city?.City_Name }} → {{ ticket.to_city?.City_Name }}</b>
                    </div>
                    <div class="flex justify-between"><span class="font-semibold text-orange-700">Seats</span><b>{{
                        ticket.Seat_No
                            }}</b></div>
                    <div class="flex justify-between"><span class="font-semibold text-orange-700">Total
                            Fare</span><b>PKR {{
                                ticket.Fare }}</b></div>
                </div>

                <!-- Refund Reason -->
                <div v-if="ticket?.Refund_Reason"
                    class="p-3 mb-3 text-sm border border-orange-200 border-t-transparent bg-orange-50">
                    <p class="mb-1 font-semibold text-orange-700">
                        📝 Refund Reason
                    </p>
                    <p class="leading-relaxed text-gray-700">
                        {{ ticket.Refund_Reason }}
                    </p>
                </div>


                <!-- Custom Refund Percentage Selector -->
                <div class="mb-3">
                    <label class="block mb-1 text-sm font-medium text-gray-700">
                        Select Refund Percentage
                    </label>
                    <select v-model="customRefundPercentage"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option v-for="option in refundOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">
                        Select a custom refund percentage or use the automatic calculation
                    </p>
                </div>

                <!-- Refund Amount -->
                <div class="p-3 mb-4 border border-red-200 rounded bg-red-50">
                    <p class="font-semibold text-red-700">
                        Refund Amount:
                        <span class="ml-1 text-2xl">Rs. {{ refundAmount }}</span>
                    </p>
                    <p class="text-sm text-red-600">
                        ({{ refundPercentage }}% of total fare)
                    </p>
                </div>

                <!-- Refund Rules -->
                <div class="mb-4 space-y-2 text-sm">
                    <p class="text-sm font-medium text-gray-700">
                        Standard Refund Rules:
                    </p>

                    <div class="flex justify-between p-2 border rounded">
                        <span>Before 48 hours</span>
                        <span class="font-semibold text-green-600">90% refund</span>
                    </div>

                    <div class="flex justify-between p-2 border rounded">
                        <span>24–48 hours before</span>
                        <span class="font-semibold text-yellow-600">75% refund</span>
                    </div>

                    <div class="flex justify-between p-2 border rounded">
                        <span>Within 24 hours</span>
                        <span class="font-semibold text-red-600">No refund</span>
                    </div>

                    <!-- <p class="text-xs italic text-gray-500">
                        * 10% admin service charges already deducted
                    </p> -->
                </div>

                <!-- Warning -->
                <div class="p-3 mb-4 text-sm text-red-800 bg-red-100 border border-red-200 rounded">
                    ⚠️ This action will cancel the ticket and cannot be undone.
                </div>

                <!-- Buttons -->
                <div class="flex justify-end gap-3">
                    <button @click="closeModal" class="px-4 py-2 text-sm bg-gray-100 rounded hover:bg-gray-200">
                        Cancel
                    </button>

                    <button @click="submitRefund" :disabled="loading"
                        class="px-4 py-2 text-sm text-white bg-red-600 rounded hover:bg-red-700 disabled:opacity-50">
                        {{ loading ? 'Processing...' : 'Confirm Refund' }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>
