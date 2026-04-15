<script setup>
import { Head, useForm, router } from "@inertiajs/vue3";
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import AdminLayout from "@/Layouts/AdminLayout.vue";

// Props
const props = defineProps({
    cities: Array,
});

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

// Refs
const searchName = ref("");
const searchId = ref("");
const statusFilter = ref("");
const showModal = ref(false);
const modalType = ref("create");
const editCity = ref(null);

// Form
const cityForm = useForm({
    City_Name: "",
    City_Abbr: "",
    Active: true,
});

// Local reactive copy
const allCities = ref([...props.cities]);

// Filtered cities (client-side)
const filteredCities = computed(() => {
    return allCities.value.filter((city) => {
        const matchesId = searchId.value
            ? city.id.toString().includes(searchId.value)
            : true;

        const matchesName = searchName.value
            ? city.City_Name.toLowerCase().includes(searchName.value.toLowerCase())
            : true;

        const matchesStatus = statusFilter.value
            ? statusFilter.value === "active"
                ? city.Active
                : !city.Active
            : true;

        return matchesId && matchesName && matchesStatus;
    });
});

// Check if filters are active
const hasActiveFilters = computed(() => searchName.value || searchId.value || statusFilter.value);

// Clear individual filter
const clearFilter = (type) => {
    if (type === "search_name") searchName.value = "";
    else if (type === "search_id") searchId.value = "";
    else if (type === "status") statusFilter.value = "";
};

// Clear all filters
const clearFilters = () => {
    searchName.value = "";
    searchId.value = "";
    statusFilter.value = "";
};

// Open create modal
const openCreateModal = () => {
    cityForm.reset();
    modalType.value = "create";
    showModal.value = true;
};

// Open edit modal
const openEditModal = (city) => {
    editCity.value = city;
    cityForm.City_Name = city.City_Name;
    cityForm.City_Abbr = city.City_Abbr ?? "";
    cityForm.Active = city.Active;
    modalType.value = "edit";
    showModal.value = true;
};

// Delete city
const openDeleteModal = (city) => {
    if (confirm(`Are you sure you want to delete "${city.City_Name}"?`)) {
        allCities.value = allCities.value.filter((c) => c.id !== city.id);
        router.delete(route("admin.cities.destroy", city.id), {
            preserveScroll: true,
        });
    }
};

// Submit create/edit form
const submitForm = () => {
    if (modalType.value === "create") {
        cityForm.post(route("admin.cities.store"), {
            onSuccess: () => {
                showModal.value = false;
                cityForm.reset();
                router.reload({ only: ["cities"] });
            },
        });
    } else {
        cityForm.put(route("admin.cities.update", editCity.value.id), {
            onSuccess: () => {
                showModal.value = false;

                // Optimistic update
                const index = allCities.value.findIndex(
                    (c) => c.id === editCity.value.id
                );
                if (index !== -1) {
                    allCities.value[index] = {
                        ...allCities.value[index],
                        City_Name: cityForm.City_Name,
                        City_Abbr: cityForm.City_Abbr,
                        Active: cityForm.Active,
                    };
                }

                editCity.value = null;
            },
        });
    }
};

// Toggle city status
const toggleStatus = (city) => {
    city.Active = !city.Active; // Optimistic update
    router.post(
        route("admin.cities.toggle-status", city.id),
        {},
        { preserveScroll: true }
    );
};
</script>

<template>

    <Head title="City Management" />

    <AdminLayout title="City Management">
        <div>
            <!-- Header -->
            <!-- Header with Filter & Add buttons -->
            <div class="flex flex-col justify-between gap-4 mb-6 md:flex-row md:items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">City Management</h1>
                    <p class="mt-1 text-sm text-gray-500">Manage cities used across the platform</p>
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
                                {{ (searchId ? 1 : 0) + (searchName ? 1 : 0) + (statusFilter ? 1 : 0) }}
                            </span>
                        </button>

                        <!-- Filter Dropdown -->
                        <div v-if="showFilterDropdown"
                            class="absolute right-0 z-20 w-80 mt-2 bg-white rounded-xl shadow-xl border-2 border-gray-200 p-4">
                            <div class="space-y-4">
                                <!-- City ID Filter -->
                                <div>
                                    <label class="block mb-1 text-xs font-semibold text-gray-500 uppercase">City
                                        ID</label>
                                    <input v-model="searchId" type="number" min="1" placeholder="Enter City ID..."
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500" />
                                </div>
                                <!-- City Name Filter -->
                                <div>
                                    <label class="block mb-1 text-xs font-semibold text-gray-500 uppercase">City
                                        Name</label>
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
                                    Showing {{ filteredCities.length }} of {{ allCities.length }} cities
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Add New City Button -->
                    <button @click="openCreateModal"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add New City
                    </button>
                </div>
            </div>

            <!-- Active Filter Tags (optional, but keeps user aware) -->
            <div v-if="hasActiveFilters" class="flex flex-wrap items-center gap-2 mb-4">
                <span class="text-sm text-gray-600">Active filters:</span>
                <span v-if="searchId"
                    class="inline-flex items-center px-3 py-1 text-sm font-medium text-purple-700 bg-purple-100 rounded-full">
                    City ID: {{ searchId }}
                    <button @click="clearFilter('search_id')"
                        class="ml-1.5 text-purple-500 hover:text-purple-700">×</button>
                </span>
                <span v-if="searchName"
                    class="inline-flex items-center px-3 py-1 text-sm font-medium text-blue-700 bg-blue-100 rounded-full">
                    Name: "{{ searchName }}"
                    <button @click="clearFilter('search_name')"
                        class="ml-1.5 text-blue-500 hover:text-blue-700">×</button>
                </span>
                <span v-if="statusFilter"
                    class="inline-flex items-center px-3 py-1 text-sm font-medium text-green-700 bg-green-100 rounded-full">
                    Status: {{ statusFilter === "active" ? "Active" : "Inactive" }}
                    <button @click="clearFilter('status')" class="ml-1.5 text-green-500 hover:text-green-700">×</button>
                </span>
                <button @click="clearFilters" class="text-xs text-red-500 hover:text-red-700">Clear all</button>
            </div>

            <!-- Cities Table -->
            <div class="overflow-x-auto bg-white rounded-lg shadow">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">#
                            </th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                City Name</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                Abbreviation</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                Status</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                Created By</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-if="filteredCities.length === 0">
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                No cities found.
                                <button v-if="hasActiveFilters" @click="clearFilters"
                                    class="ml-2 text-indigo-600 hover:text-indigo-800">
                                    Clear filters to see all cities
                                </button>
                            </td>
                        </tr>
                        <tr v-for="city in filteredCities" :key="city.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                {{ city.id }}
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 whitespace-nowrap">
                                {{ city.City_Name }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                {{ city.City_Abbr || "-" }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span @click="toggleStatus(city)" :class="[
                                    'inline-flex px-2 py-1 text-xs font-semibold rounded-full cursor-pointer',
                                    city.Active
                                        ? 'bg-green-100 text-green-800 hover:bg-green-200'
                                        : 'bg-red-100 text-red-800 hover:bg-red-200',
                                ]">
                                    {{ city.Active ? "Active" : "Inactive" }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                {{ city.created_by || "System" }}
                            </td>
                            <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                <button @click="openEditModal(city)" class="mr-4 text-indigo-600 hover:text-indigo-900">
                                    Edit
                                </button>
                                <button @click="openDeleteModal(city)" class="text-red-600 hover:text-red-900">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create / Edit Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div
                    class="inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-md sm:w-full sm:p-6">
                    <h3 class="mb-4 text-lg font-medium text-gray-900">
                        {{ modalType === "create" ? "Add New City" : "Edit City" }}
                    </h3>

                    <form @submit.prevent="submitForm">
                        <div class="space-y-4">

                            <!-- City Name -->
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700">
                                    City Name <span class="text-red-500">*</span>
                                </label>
                                <input v-model="cityForm.City_Name" type="text" required
                                    placeholder="Enter city name..."
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    :class="{ 'border-red-300': cityForm.errors.City_Name }" />
                                <p v-if="cityForm.errors.City_Name" class="mt-1 text-sm text-red-600">
                                    {{ cityForm.errors.City_Name }}
                                </p>
                            </div>

                            <!-- City Abbreviation -->
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700">
                                    Abbreviation
                                </label>
                                <input v-model="cityForm.City_Abbr" type="text" maxlength="10"
                                    placeholder="e.g. KHI, LHR..."
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    :class="{ 'border-red-300': cityForm.errors.City_Abbr }" />
                                <p v-if="cityForm.errors.City_Abbr" class="mt-1 text-sm text-red-600">
                                    {{ cityForm.errors.City_Abbr }}
                                </p>
                            </div>

                            <!-- Active Checkbox -->
                            <div class="flex items-center">
                                <input v-model="cityForm.Active" type="checkbox" id="active"
                                    class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500" />
                                <label for="active" class="block ml-2 text-sm text-gray-900">
                                    Active
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end mt-6 space-x-3">
                            <button type="button" @click="showModal = false"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit" :disabled="cityForm.processing"
                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 disabled:opacity-50">
                                {{ cityForm.processing ? "Saving..." : "Save City" }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
