<script setup>
import { Head, Link } from "@inertiajs/vue3";
import CompanyLayout from "@/Layouts/CompanyLayout.vue";

const props = defineProps({
    ticket: Object,
});

const formatSeats = (seatNo) => {
    if (!seatNo) return 'N/A';
    try {
        const seats = typeof seatNo === 'string' ? JSON.parse(seatNo) : seatNo;
        return Array.isArray(seats) ? seats.join(', ') : seatNo;
    } catch {
        return seatNo;
    }
};

const formatDate = (date) => {
    if (!date) return 'N/A';
    try {
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    } catch {
        return date;
    }
};

const formatDateTime = (dateTime) => {
    if (!dateTime) return 'N/A';
    try {
        return new Date(dateTime).toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch {
        return dateTime;
    }
};
</script>

<template>

    <Head :title="`Ticket - ${ticket.PNR_No}`" />

    <CompanyLayout title="Royal Movers">
        <div>
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <Link :href="route('company.ticketing.index')" class="text-sm text-blue-600 hover:text-blue-900">
                        ← Back to Tickets
                    </Link>
                    <h1 class="mt-2 text-2xl font-bold text-gray-800">Ticket Details</h1>
                </div>
                <span :class="[
                    'px-4 py-2 rounded-full text-sm font-medium',
                    ticket.Status === 'Confirmed' ? 'bg-green-100 text-green-800' :
                        ticket.Status === 'Pending' ? 'bg-yellow-100 text-yellow-800' :
                            'bg-red-100 text-red-800'
                ]">
                    {{ ticket.Status }}
                </span>
            </div>

            <!-- Main Info -->
            <div class="grid grid-cols-1 gap-6 mb-6 lg:grid-cols-2">
                <!-- Passenger Information -->
                <div class="p-6 bg-white rounded-lg shadow">
                    <h2 class="mb-4 text-lg font-semibold text-gray-800">Passenger Information</h2>
                    <dl class="space-y-3">
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600">PNR Number:</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ ticket.PNR_No }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600">Name:</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ ticket.Passenger_Name }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600">Contact:</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ ticket.Contact_No }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600">Emergency Contact:</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ ticket.Emergency_Contact || 'N/A' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600">CNIC:</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ ticket.CNIC || 'N/A' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600">Gender:</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ ticket.Gender || 'N/A' }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Journey Information -->
                <div class="p-6 bg-white rounded-lg shadow">
                    <h2 class="mb-4 text-lg font-semibold text-gray-800">Journey Information</h2>
                    <dl class="space-y-3">
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600">From:</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ ticket.from_city?.City_Name || 'N/A' }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600">To:</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ ticket.to_city?.City_Name || 'N/A' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600">Travel Date:</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ formatDate(ticket.Travel_Date) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600">Travel Time:</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ ticket.Travel_Time }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600">Seat Number(s):</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ formatSeats(ticket.Seat_No) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600">Company:</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ ticket.Company_Name || 'N/A' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Payment Information -->
            <div class="grid grid-cols-1 gap-6 mb-6 lg:grid-cols-2">
                <div class="p-6 bg-white rounded-lg shadow">
                    <h2 class="mb-4 text-lg font-semibold text-gray-800">Payment Information</h2>
                    <dl class="space-y-3">
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600">Fare:</dt>
                            <dd class="text-sm font-medium text-gray-900">PKR {{ ticket.Fare }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600">Discount:</dt>
                            <dd class="text-sm font-medium text-gray-900">PKR {{ ticket.Discount || 0 }}</dd>
                        </div>
                        <div class="flex justify-between pt-2 border-t">
                            <dt class="text-sm font-semibold text-gray-600">Total Amount:</dt>
                            <dd class="text-sm font-bold text-gray-900">
                                PKR {{ (parseFloat(ticket.Fare || 0) - parseFloat(ticket.Discount || 0)).toFixed(2) }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600">Payment Date:</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ formatDateTime(ticket.PaymentDate) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600">Invoice:</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ ticket.Invoice || 'N/A' }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Additional Information -->
                <div class="p-6 bg-white rounded-lg shadow">
                    <h2 class="mb-4 text-lg font-semibold text-gray-800">Additional Information</h2>
                    <dl class="space-y-3">
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600">Issue Date:</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ formatDateTime(ticket.Issue_Date) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600">Issue Terminal:</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ ticket.Issue_Terminal || 'N/A' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600">Issued By:</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ ticket.Issued_By || 'N/A' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600">SMS Sent:</dt>
                            <dd class="text-sm font-medium text-gray-900">
                                <span :class="[
                                    'px-2 py-1 rounded text-xs',
                                    ticket.Is_SMS_Sent ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                                ]">
                                    {{ ticket.Is_SMS_Sent ? 'Yes' : 'No' }}
                                </span>
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600">Return Ticket:</dt>
                            <dd class="text-sm font-medium text-gray-900">
                                <span :class="[
                                    'px-2 py-1 rounded text-xs',
                                    ticket.Is_Return ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'
                                ]">
                                    {{ ticket.Is_Return ? 'Yes' : 'No' }}
                                </span>
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600">Loyalty Points:</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ ticket.Points || 0 }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </CompanyLayout>
</template>
