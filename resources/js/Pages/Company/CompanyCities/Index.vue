<script setup>
import { Head, useForm, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import { onMounted, onUnmounted } from 'vue';
import CompanyLayout from "@/Layouts/CompanyLayout.vue";
import toast from '@/Services/toast'

const props = defineProps({
    companyCities: Array,
    availableCities: Array,
});

// ✅ Read DIRECTLY from props — no local ref copies
// Inertia updates props automatically after every back() reload

// Filters
const searchName = ref("");
const statusFilter = ref("");

// Modal states
const showModal = ref(false);
const modalType = ref("create");
const editRecord = ref(null);

// Form
const cityForm = useForm({
    global_city_id: "",
    company_city_id: "",
    active: true,
});

// Flash message
const flashMessage = ref("");
const flashType = ref("success");
const showFlash = ref(false);

// Filter dropdown state
const showFilterDropdown = ref(false);
const filterDropdownRef = ref(null);

// Close filter dropdown when clicking outside
const handleClickOutside = (event) => {
    if (filterDropdownRef.value && !filterDropdownRef.value.contains(event.target)) {
        showFilterDropdown.value = false;
    }
};
onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

const triggerFlash = (message, type = "success") => {
    flashMessage.value = message;
    flashType.value = type;
    showFlash.value = true;
    setTimeout(() => (showFlash.value = false), 3000);
};

// ✅ filteredCities reads from props.companyCities directly
const filteredCities = computed(() => {
    return (props.companyCities ?? []).filter((c) => {
        const matchesName = searchName.value
            ? c.city_name.toLowerCase().includes(searchName.value.toLowerCase())
            : true;
        const matchesStatus = statusFilter.value
            ? statusFilter.value === "active" ? c.active : !c.active
            : true;
        return matchesName && matchesStatus;
    });
});

const hasActiveFilters = computed(() => searchName.value || statusFilter.value);

const clearFilters = () => {
    searchName.value = "";
    statusFilter.value = "";
};

// ✅ dropdownCities reads from props.availableCities directly
const dropdownCities = computed(() => {
    if (modalType.value === "edit" && editRecord.value) {
        const current = {
            id: editRecord.value.global_city_id,
            City_Name: editRecord.value.city_name,
        };
        const alreadyIn = (props.availableCities ?? []).find(
            (c) => c.id === current.id
        );
        return alreadyIn
            ? props.availableCities
            : [current, ...props.availableCities];
    }
    return props.availableCities ?? [];
});

// Modal handlers
const openCreateModal = () => {
    cityForm.reset();
    cityForm.active = true;
    modalType.value = "create";
    showModal.value = true;
};

const openEditModal = (record) => {
    editRecord.value = record;
    cityForm.global_city_id = record.global_city_id;
    cityForm.company_city_id = record.company_city_id;
    cityForm.active = record.active;
    modalType.value = "edit";
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editRecord.value = null;
    cityForm.reset();
    cityForm.clearErrors();
};

// ✅ No manual router.reload() needed — Inertia's back() does it automatically
const submitForm = () => {
    if (modalType.value === "create") {
        cityForm.post(route("company.cities.store"), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                toast.success('Success', 'City added successfully!')
            },
            onError: (errors) => {
                if (errors.global_city_id)
                    triggerFlash(errors.global_city_id, "error");
                if (errors.company_city_id)
                    triggerFlash(errors.company_city_id, "error");
            },
        });
    } else {
        cityForm.put(route("company.cities.update", editRecord.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                toast.success('Success', 'City added successfully!')
            },
            onError: (errors) => {
                if (errors.global_city_id)
                    triggerFlash(errors.global_city_id, "error");
                if (errors.company_city_id)
                    triggerFlash(errors.company_city_id, "error");
            },
        });
    }
};

// Delete
const deleteRecord = ref(null);
const showDeleteModal = ref(false);

const openDeleteModal = (record) => {
    deleteRecord.value = record;
    showDeleteModal.value = true;
};

// ✅ No manual allCities filtering — Inertia reload updates props automatically
const confirmDelete = () => {
    router.delete(route("company.cities.destroy", deleteRecord.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            deleteRecord.value = null
            toast.success('Deleted', 'City removed successfully!')
        },
        onError: () => {
            toast.error('Error', 'Failed to delete city')
        }
    })
}

// Toggle active
const toggleActive = (record) => {
    router.post(route("company.cities.toggle-active", record.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.info('Updated', 'Status updated successfully')
        },
        onError: () => {
            toast.error('Error', 'Failed to update status')
        }
    })
}
</script>

<template>

    <Head title="Company Cities" />
    <CompanyLayout>
        <div>
            <!-- Flash Message -->
            <transition name="fade">
                <div v-if="showFlash" :class="[
                    'fixed top-6 right-6 z-50 px-5 py-3 rounded-lg shadow-lg text-white text-sm font-medium',
                    flashType === 'success' ? 'bg-green-600' : 'bg-red-600'
                ]">
                    {{ flashMessage }}
                </div>
            </transition>

            <!-- Header with Filter & Add buttons -->
            <div class="flex flex-col justify-between gap-4 mb-5 md:flex-row md:items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Company Cities</h1>
                    <p class="mt-1 text-sm text-gray-500">Manage cities available for your company</p>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Filter Button -->
                    <div class="relative" ref="filterDropdownRef">
                        <button @click="showFilterDropdown = !showFilterDropdown"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            Filter
                            <span v-if="hasActiveFilters"
                                class="ml-1 px-1.5 py-0.5 text-xs font-bold text-white bg-indigo-500 rounded-full">
                                {{ (searchName ? 1 : 0) + (statusFilter ? 1 : 0) }}
                            </span>
                        </button>

                        <!-- Filter Dropdown -->
                        <div v-if="showFilterDropdown"
                            class="absolute right-0 z-20 w-80 mt-2 bg-white rounded-xl shadow-xl border-2 border-gray-200 p-4">
                            <div class="space-y-4">
                                <!-- Search City -->
                                <div>
                                    <label class="block mb-1 text-xs font-semibold text-gray-500 uppercase">Search
                                        City</label>
                                    <input v-model="searchName" type="text" placeholder="Search by city name..."
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500" />
                                </div>
                                <!-- Status Filter -->
                                <div>
                                    <label
                                        class="block mb-1 text-xs font-semibold text-gray-500 uppercase">Status</label>
                                    <select v-model="statusFilter"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                        <option value="">All Status</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                                <!-- Reset Button -->
                                <button v-if="hasActiveFilters" @click="clearFilters"
                                    class="w-full px-3 py-2 text-sm text-red-600 bg-red-200 rounded-lg hover:bg-red-300">
                                    Clear all filters
                                </button>
                                <p class="text-xs text-gray-400 text-center pt-1 border-t border-gray-100 mt-2">
                                    Showing {{ filteredCities.length }} of {{ props.companyCities?.length ?? 0 }} cities
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Add City Button -->
                    <button @click="openCreateModal"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary/90 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add City
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 text-xs font-semibold text-left text-slate-500 uppercase">#</th>
                            <th class="px-6 py-3 text-xs font-semibold text-left text-slate-500 uppercase">City Name
                            </th>
                            <th class="px-6 py-3 text-xs font-semibold text-left text-slate-500 uppercase">Company City
                                ID</th>
                            <th class="px-6 py-3 text-xs font-semibold text-left text-slate-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-xs font-semibold text-left text-slate-500 uppercase">Created By
                            </th>
                            <th class="px-6 py-3 text-xs font-semibold text-left text-slate-500 uppercase">Added On</th>
                            <th class="px-6 py-3 text-xs font-semibold text-left text-slate-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-if="filteredCities.length === 0">
                            <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                No cities found.
                            </td>
                        </tr>
                        <tr v-for="(city, index) in filteredCities" :key="city.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-400">{{ index + 1 }}</td>
                            <td class="px-6 py-4 text-sm font-semibold text-gray-800">{{ city.city_name }}</td>
                            <td class="px-6 py-4 text-sm text-red-600 font-bold">{{ city.company_city_id }}</td>
                            <td class="px-6 py-4">
                                <span @click="toggleActive(city)" :class="[
                                    'inline-flex px-2.5 py-1 text-xs font-semibold rounded-full cursor-pointer transition',
                                    city.active
                                        ? 'bg-green-100 text-green-700 hover:bg-green-200 border border-green-300'
                                        : 'bg-red-100 text-red-700 hover:bg-red-200 border border-red-300',
                                ]">
                                    {{ city.active ? "Active" : "Inactive" }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-800">{{ city.created_by }}</td>
                            <td class="px-6 py-4 text-sm text-gray-800">{{ city.created_at }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <button @click="openEditModal(city)"
                                        class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Edit</button>
                                    <span class="text-gray-200">|</span>
                                    <button @click="openDeleteModal(city)"
                                        class="text-sm font-medium text-red-500 hover:text-red-700">Remove</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create / Edit Modal -->
        <transition name="modal">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center px-4">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeModal"></div>
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl p-6 z-10">
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="text-lg font-bold text-gray-900">
                            {{ modalType === "create" ? "Add City to Company" : "Edit Company City" }}
                        </h3>
                        <button @click="closeModal"
                            class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <form @submit.prevent="submitForm" class="space-y-4">
                        <!-- Global City Dropdown -->
                        <div>
                            <label class="block mb-1.5 text-sm font-medium text-gray-700">
                                Select Global City <span class="text-red-500">*</span>
                            </label>
                            <select v-model="cityForm.global_city_id" required
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                :class="{ 'border-red-400': cityForm.errors.global_city_id }">
                                <option value="" disabled>-- Choose a city --</option>
                                <option v-for="city in dropdownCities" :key="city.id" :value="city.id">
                                    {{ city.City_Name }}
                                </option>
                            </select>
                            <p v-if="cityForm.errors.global_city_id" class="mt-1 text-xs text-red-500">
                                {{ cityForm.errors.global_city_id }}
                            </p>
                        </div>

                        <!-- Company City ID -->
                        <div>
                            <label class="block mb-1.5 text-sm font-medium text-gray-700">
                                Company City ID <span class="text-red-500">*</span>
                            </label>
                            <input type="number" v-model="cityForm.company_city_id" required
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                :class="{ 'border-red-400': cityForm.errors.company_city_id }"
                                placeholder="e.g., 56 or 85" />
                            <p v-if="cityForm.errors.company_city_id" class="mt-1 text-xs text-red-500">
                                {{ cityForm.errors.company_city_id }}
                            </p>
                            <p class="mt-1 text-xs text-gray-400">Your company's internal ID for this city.</p>
                        </div>

                        <!-- Active Toggle -->
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
                            <button type="button" @click="cityForm.active = !cityForm.active" :class="[
                                'relative inline-flex w-11 h-6 rounded-full transition-colors duration-200',
                                cityForm.active ? 'bg-green-700' : 'bg-red-700'
                            ]">
                                <span :class="[
                                    'absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200',
                                    cityForm.active ? 'translate-x-5' : 'translate-x-0'
                                ]"></span>
                            </button>
                            <span class="text-sm font-medium flex items-center gap-2">

                                <!-- Status badge -->
                                <span
                                    :class="cityForm.active
                                        ? 'text-green-700 bg-green-100 border border-green-300 px-2 py-0.5 rounded-full text-xs font-semibold'
                                        : 'text-red-700 bg-red-100 border border-red-200 px-2 py-0.5 rounded-full text-xs font-semibold'">
                                    {{ cityForm.active ? 'Active' : 'Inactive' }}
                                </span>

                                <!-- Helper text -->
                                <span class="text-gray-500 text-xs">
                                    {{ cityForm.active
                                        ? 'This city is visible in search results'
                                        : 'This city is hidden from search results'
                                    }}
                                </span>

                            </span>
                        </div>

                        <!-- Buttons -->
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="closeModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit" :disabled="cityForm.processing"
                                class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                                {{ cityForm.processing ? "Saving..." : modalType === "create" ? "Add City" :
                                    "Save Changes" }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </transition>

        <!-- Delete Confirmation Modal -->
        <transition name="modal">
            <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center px-4">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showDeleteModal = false"></div>
                <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl p-6 z-10">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <h3 class="mb-1 text-base font-bold text-center text-gray-900">Remove City</h3>
                    <p class="mb-5 text-sm text-center text-gray-500">
                        Are you sure you want to remove
                        <span class="font-semibold text-gray-800">{{ deleteRecord?.city_name }}</span>
                        from your company?
                    </p>
                    <div class="flex gap-3">
                        <button @click="showDeleteModal = false"
                            class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            Cancel
                        </button>
                        <button @click="confirmDelete"
                            class="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                            Remove
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </CompanyLayout>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
